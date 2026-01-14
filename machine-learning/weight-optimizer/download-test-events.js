const fs = require('fs');
const path = require('path');
const http = require('http');

// Configuration
const API_BASE_URL = 'http://127.0.0.1/news-api-v1/news-api.php';
const API_KEY = 'arr_b03bcfb76b4e904d';
const EVENT_IDS = 'VPRWG,JCDYM,ZBEYU,GKORG,LUXEM,YHIYY,MXSBY,PIIRP,EEWPQ,ASXLP,ISFDE,YFVMV,YPAXY,RQUMP,WDLPA,FCHGZ,HKJPS,LUNIH,LIFLX,ZVAPL,HMUGW,ZORRY,ALMVK,BRHWF,IEDWO,BZLYI,XWBVZ,ZJOIV,FLPVV,ZGZEB,PEZJS,OMHBG,COJRY,FLANG,VLBVK,RIHAG,LOYOG,YCGKV,ZNLMN,VLJYS,VCOYI,LCSFP,KWQNJ,NPAUL,MEVPW,KSCVD,AGYKR,LCYVM,JMVUQ,PQRUD,ISCRG,FJRUD,GTPRZ,UYGJS,FNCVQ,MUQEC,OPLIH,YKFVQ,MXCLU,WSESU,OSYCZ,YFWBM,LTLVK,VYXGI,LCFMG,LHXZU,JXZHS,SKLTV,DEVZR,FUHNP,YJLZW,WBZHM,QSBCI,BRIOI';
const CSV_FILE = '../XAU_USD Historical Data.csv';
const OUTPUT_FILE = 'test_events_data.json';

// Parse CSV file
function parseCSV(filePath) {
    const content = fs.readFileSync(filePath, 'utf-8');
    const lines = content.split('\n');
    const data = [];
    
    for (let i = 1; i < lines.length; i++) {
        const line = lines[i].trim();
        if (!line) continue;
        
        const match = line.match(/"([^"]+)","([^"]+)","([^"]+)","([^"]+)","([^"]+)","([^"]*)","([^"]+)"/);
        if (match) {
            const dateStr = match[1];
            const changePercent = match[7];
            
            data.push({
                date: convertToISODate(dateStr),
                changePercent: changePercent
            });
        }
    }
    
    return data;
}

// Convert MM/DD/YYYY to YYYY-MM-DD
function convertToISODate(dateStr) {
    const [month, day, year] = dateStr.split('/');
    return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
}

// Make HTTP GET request
function httpGet(url) {
    return new Promise((resolve, reject) => {
        http.get(url, (res) => {
            let data = '';
            
            res.on('data', (chunk) => {
                data += chunk;
            });
            
            res.on('end', () => {
                try {
                    resolve(JSON.parse(data));
                } catch (e) {
                    reject(new Error(`Failed to parse JSON: ${e.message}`));
                }
            });
        }).on('error', (err) => {
            reject(err);
        });
    });
}

// Fetch events from API
async function fetchEvents(date) {
    const url = `${API_BASE_URL}?api_key=${API_KEY}&period=future&future_limit=next-week&pretend_date=${date}&pretend_time=00:01&currency=USD&time_zone=NY&must_have=forecast_value,previous_value&avoid_duplicated=true&ignore_weekends=true&event_id=${EVENT_IDS}`;
    
    try {
        const data = await httpGet(url);
        return data;
    } catch (error) {
        console.error(`[ERROR] Fetching events for ${date}:`, error.message);
        return null;
    }
}

// Get price movement
function getPriceStatus(changePercent) {
    if (!changePercent) {
        return { status: 'UNCHANGED', magnitude: 0 };
    }
    
    const value = parseFloat(changePercent.replace('%', ''));
    
    if (value > 0) {
        return { status: 'UP', magnitude: value };
    } else if (value < 0) {
        return { status: 'DOWN', magnitude: Math.abs(value) };
    } else {
        return { status: 'UNCHANGED', magnitude: 0 };
    }
}

// Main function
async function main() {
    console.log('========================================');
    console.log('DOWNLOADING TEST EVENT DATA FROM API');
    console.log('========================================');
    console.log('Reading XAU/USD historical data...');
    
    const csvData = parseCSV(CSV_FILE);
    console.log(`Found ${csvData.length} dates in CSV`);
    
    // Filter 2025-2026
    let filteredData = csvData.filter(item => {
        const year = parseInt(item.date.substring(0, 4));
        return year >= 2025 && year <= 2026;
    });
    
    filteredData.reverse(); // Oldest to newest
    console.log(`Processing ${filteredData.length} dates from 2025 to 2026`);
    console.log('\n');
    
    const allData = [];
    let processedCount = 0;
    
    for (let i = 0; i < filteredData.length; i++) {
        const item = filteredData[i];
        const events = await fetchEvents(item.date);
        const priceStatus = getPriceStatus(item.changePercent);
        
        allData.push({
            date: item.date,
            events: events,
            direction: priceStatus.status,
            magnitude: priceStatus.magnitude
        });
        
        processedCount++;
        if (processedCount % 50 === 0 || processedCount === 1) {
            const pct = ((processedCount / filteredData.length) * 100).toFixed(1);
            console.log(`Progress: ${processedCount}/${filteredData.length} (${pct}%)`);
        }
        
        // Small delay
        await new Promise(resolve => setTimeout(resolve, 50));
    }
    
    // Save to JSON
    fs.writeFileSync(OUTPUT_FILE, JSON.stringify(allData, null, 2));
    
    console.log('\n========================================');
    console.log('DOWNLOAD COMPLETE');
    console.log('========================================');
    console.log(`Saved ${allData.length} records to: ${OUTPUT_FILE}`);
    console.log(`File size: ${(fs.statSync(OUTPUT_FILE).size / 1024 / 1024).toFixed(2)} MB`);
    console.log('========================================');
}

main().catch(error => {
    console.error('Fatal error:', error);
    process.exit(1);
});

