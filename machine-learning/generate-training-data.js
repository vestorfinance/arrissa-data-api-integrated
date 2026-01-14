const fs = require('fs');
const path = require('path');
const http = require('http');

// Configuration
const API_BASE_URL = 'http://127.0.0.1/news-api-v1/news-api.php';
const API_KEY = 'arr_b03bcfb76b4e904d';
const EVENT_IDS = 'VPRWG,JCDYM,ZBEYU,GKORG,LUXEM,YHIYY,MXSBY,PIIRP,EEWPQ,ASXLP,ISFDE,YFVMV,YPAXY,RQUMP,WDLPA,FCHGZ,HKJPS,LUNIH,LIFLX,ZVAPL,HMUGW,ZORRY,ALMVK,BRHWF,IEDWO,BZLYI,XWBVZ,ZJOIV,FLPVV,ZGZEB,PEZJS,OMHBG,COJRY,FLANG,VLBVK,RIHAG,LOYOG,YCGKV,ZNLMN,VLJYS,VCOYI,LCSFP,KWQNJ,NPAUL,MEVPW,KSCVD,AGYKR,LCYVM,JMVUQ,PQRUD,ISCRG,FJRUD,GTPRZ,UYGJS,FNCVQ,MUQEC,OPLIH,YKFVQ,MXCLU,WSESU,OSYCZ,YFWBM,LTLVK,VYXGI,LCFMG,LHXZU,JXZHS,SKLTV,DEVZR,FUHNP,YJLZW,WBZHM,QSBCI,BRIOI';
const CSV_FILE = 'XAU_USD Historical Data.csv';
const OUTPUT_DIR = 'preprocessed_data';
const OUTPUT_FILE = path.join(OUTPUT_DIR, 'training-data.csv');
const TEST_MODE = false; // Set to true to test with 5 entries
const TEST_ENTRIES = 5;

// Event impact mapping from important-events.md
const EVENT_IMPACT_MAP = {
    'VPRWG': { weight: 9.00, positive: 'higher' },
    'ZBEYU': { weight: 7.79, positive: 'higher' },
    'GKORG': { weight: 9.00, positive: 'higher' },
    'JCDYM': { weight: 8.06, positive: 'lower' },
    'LUXEM': { weight: 6.00, positive: 'lower' },
    'EEWPQ': { weight: 5.33, positive: 'lower' },
    'PIIRP': { weight: 7.10, positive: 'lower' },
    'YHIYY': { weight: 4.00, positive: 'higher' },
    'MXSBY': { weight: 6.40, positive: 'higher' },
    'ASXLP': { weight: 10.00, positive: 'higher' },
    'YFVMV': { weight: 10.00, positive: 'higher' },
    'FCHGZ': { weight: 8.20, positive: 'higher' },
    'RQUMP': { weight: 9.00, positive: 'higher' },
    'ISFDE': { weight: 7.00, positive: 'higher' },
    'YPAXY': { weight: 7.00, positive: 'higher' },
    'LUNIH': { weight: 7.00, positive: 'higher' },
    'LIFLX': { weight: 7.00, positive: 'higher' },
    'HKJPS': { weight: 5.03, positive: 'higher' },
    'WDLPA': { weight: 8.63, positive: 'higher' },
    'ZORRY': { weight: 10.00, positive: 'higher' },
    'HMUGW': { weight: 9.00, positive: 'higher' },
    'ZVAPL': { weight: 10.00, positive: 'higher' },
    'ALMVK': { weight: 8.00, positive: 'higher' },
    'BRHWF': { weight: 8.00, positive: 'higher' },
    'BZLYI': { weight: 8.00, positive: 'higher' },
    'IEDWO': { weight: 7.00, positive: 'higher' },
    'XWBVZ': { weight: 3.47, positive: 'higher' },
    'ZJOIV': { weight: 6.00, positive: 'higher' },
    'FLPVV': { weight: 7.53, positive: 'higher' },
};;;;;;;

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
                originalDate: dateStr,
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
async function fetchEvents(date, period) {
    const url = `${API_BASE_URL}?api_key=${API_KEY}&period=future&future_limit=${period}&pretend_date=${date}&pretend_time=00:01&currency=USD&time_zone=NY&must_have=forecast_value,previous_value&avoid_duplicated=true&ignore_weekends=true&event_id=${EVENT_IDS}`;
    
    try {
        const data = await httpGet(url);
        return data;
    } catch (error) {
        console.error(`[ERROR] Fetching events for ${date} (${period}):`, error.message);
        return null;
    }
}

// Calculate state of economy
function calculateEconomyState(eventsData) {
    if (!eventsData || !eventsData.vestor_data) {
        return {
            aggregate_weight: 0,
            should_be_weight: 0,
            state_percentage: 0,
            event_count: 0,
            events_detail: []
        };
    }
    
    let aggregateWeight = 0;
    let eventCount = 0;
    const eventsDetail = [];
    
    // Iterate through all event categories
    for (const category in eventsData.vestor_data) {
        const categoryData = eventsData.vestor_data[category];
        
        if (categoryData.events && Array.isArray(categoryData.events)) {
            for (const event of categoryData.events) {
                const eventId = event.consistent_event_id;
                
                if (!EVENT_IMPACT_MAP[eventId]) {
                    continue; // Skip if not in our mapping
                }
                
                const eventInfo = EVENT_IMPACT_MAP[eventId];
                const forecastValue = parseFloat(event.forecast_value);
                const previousValue = parseFloat(event.previous_value);
                
                if (isNaN(forecastValue) || isNaN(previousValue)) {
                    continue;
                }
                
                // Skip unchanged entries (forecast == previous)
                if (forecastValue === previousValue) {
                    continue;
                }
                
                let signedWeight = 0;
                let state = 'neutral';
                
                if (eventInfo.positive === 'neutral') {
                    // Neutral events don't contribute to aggregate
                    signedWeight = 0;
                    state = 'neutral';
                } else if (eventInfo.positive === 'higher') {
                    // Positive when forecast > previous
                    if (forecastValue > previousValue) {
                        signedWeight = eventInfo.weight;
                        state = 'positive';
                    } else if (forecastValue < previousValue) {
                        signedWeight = -eventInfo.weight;
                        state = 'negative';
                    }
                } else if (eventInfo.positive === 'lower') {
                    // Positive when forecast < previous
                    if (forecastValue < previousValue) {
                        signedWeight = eventInfo.weight;
                        state = 'positive';
                    } else if (forecastValue > previousValue) {
                        signedWeight = -eventInfo.weight;
                        state = 'negative';
                    }
                }
                
                aggregateWeight += signedWeight;
                if (eventInfo.positive !== 'neutral') {
                    eventCount++;
                }
                
                eventsDetail.push({
                    event_name: event.event_name,
                    event_id: eventId,
                    forecast: forecastValue,
                    previous: previousValue,
                    weight: eventInfo.weight,
                    signed_weight: signedWeight,
                    state: state
                });
            }
        }
    }
    
    // Calculate should be weight (assuming max weight of 10 per event)
    // Always use positive for shouldBeWeight so the sign of aggregateWeight is preserved in percentage
    const shouldBeWeight = eventCount > 0 ? eventCount * 10 : 0;
    
    // Calculate state percentage (will be negative if aggregateWeight is negative)
    const statePercentage = shouldBeWeight !== 0 ? (aggregateWeight / shouldBeWeight) * 100 : 0;
    
    return {
        aggregate_weight: aggregateWeight,
        should_be_weight: shouldBeWeight,
        state_percentage: parseFloat(statePercentage.toFixed(2)),
        event_count: eventCount,
        events_detail: eventsDetail
    };
}

// Get price movement status
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

// CSV helper functions
function escapeCSV(value) {
    if (value === null || value === undefined) return '';
    const str = String(value);
    if (str.includes(',') || str.includes('"') || str.includes('\n')) {
        return `"${str.replace(/"/g, '""')}"`;
    }
    return str;
}

function writeCSVHeader(filePath) {
    const headers = [
        'date',
        'today_state_of_the_economy',
        'tomorrow_state_of_the_economy',
        'next_week_state_of_the_economy',
        'direction',
        'magnitude'
    ];
    
    fs.writeFileSync(filePath, headers.join(',') + '\n');
}

function appendCSVRow(filePath, data) {
    const row = [
        data.date,
        data.today_state_of_the_economy,
        data.tomorrow_state_of_the_economy,
        data.next_week_state_of_the_economy,
        data.direction,
        data.magnitude
    ];
    
    fs.appendFileSync(filePath, row.map(escapeCSV).join(',') + '\n');
}

// Process a single date
async function processDate(dateObj) {
    const [todayEvents, tomorrowEvents, nextWeekEvents] = await Promise.all([
        fetchEvents(dateObj.date, 'today'),
        fetchEvents(dateObj.date, 'tomorrow'),
        fetchEvents(dateObj.date, 'next-week')
    ]);
    
    const todayState = calculateEconomyState(todayEvents);
    const tomorrowState = calculateEconomyState(tomorrowEvents);
    const nextWeekState = calculateEconomyState(nextWeekEvents);
    const priceStatus = getPriceStatus(dateObj.changePercent);
    
    return {
        date: dateObj.date,
        today_state_of_the_economy: todayState.state_percentage,
        today_event_count: todayState.event_count,
        tomorrow_state_of_the_economy: tomorrowState.state_percentage,
        tomorrow_event_count: tomorrowState.event_count,
        next_week_state_of_the_economy: nextWeekState.state_percentage,
        next_week_event_count: nextWeekState.event_count,
        direction: priceStatus.status,
        magnitude: priceStatus.magnitude
    };
}

// Main function
async function main() {
    console.log('Starting data processing...');
    console.log(`Test Mode: ${TEST_MODE ? `YES (${TEST_ENTRIES} entries)` : 'NO (full dataset)'}`);
    console.log('Reading CSV file...');
    
    const csvData = parseCSV(CSV_FILE);
    console.log(`Found ${csvData.length} dates in CSV`);
    
    // Filter data from 2014 to 2024
    let filteredData = csvData.filter(item => {
        const year = parseInt(item.date.substring(0, 4));
        return year >= 2014 && year <= 2024;
    });
    
    // REVERSE to go from oldest to newest
    filteredData.reverse();
    console.log(`Processing ${filteredData.length} dates from 2014 to 2024 (OLDEST to NEWEST)`);
    
    // Limit to test entries if in test mode
    if (TEST_MODE) {
        filteredData = filteredData.slice(0, TEST_ENTRIES);
        console.log(`[TEST MODE] Limited to first ${filteredData.length} entries`);
    }
    
    // Create output directory if it doesn't exist
    if (!fs.existsSync(OUTPUT_DIR)) {
        fs.mkdirSync(OUTPUT_DIR, { recursive: true });
        console.log(`Created directory: ${OUTPUT_DIR}`);
    }
    
    // Write CSV header
    writeCSVHeader(OUTPUT_FILE);
    console.log(`Created CSV file: ${OUTPUT_FILE}`);
    console.log('\n');
    
    // Process dates sequentially and save as we go
    let savedCount = 0;
    let skippedCount = 0;
    
    for (let i = 0; i < filteredData.length; i++) {
        const result = await processDate(filteredData[i]);
        
        // Skip if all periods have 0 events
        if (result.today_event_count === 0 && result.tomorrow_event_count === 0 && result.next_week_event_count === 0) {
            skippedCount++;
        } else {
            // Append to CSV immediately
            appendCSVRow(OUTPUT_FILE, result);
            savedCount++;
            if (savedCount === 1 || savedCount % 100 === 0) {
                const percentage = ((i + 1) / filteredData.length * 100).toFixed(1);
                console.log(`Progress: ${savedCount} rows saved (${i + 1}/${filteredData.length} processed) - ${percentage}%`);
            }
        }
        
        // Add a small delay to avoid hammering the API
        await new Promise(resolve => setTimeout(resolve, 50));
    }
    
    console.log('\n========================================');
    console.log(`Done! Output saved to ${OUTPUT_FILE}`);
    console.log(`Total dates processed: ${filteredData.length}`);
    console.log(`Rows saved: ${savedCount}`);
    console.log(`Rows skipped (no events): ${skippedCount}`);
    console.log('========================================');
}

// Run the script
main().catch(error => {
    console.error('Fatal error:', error);
    process.exit(1);
});
