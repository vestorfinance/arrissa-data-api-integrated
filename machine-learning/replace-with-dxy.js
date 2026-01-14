const fs = require('fs');
const path = require('path');

// File paths
const TRAINING_DATA_FILE = 'preprocessed_data/training-data.csv';
const DXY_FILE = 'US Dollar Index Historical Data.csv'; // US Dollar Index historical data
const OUTPUT_FILE = 'preprocessed_data/training-data-dxy.csv';

console.log('Starting DXY data replacement...\n');

// Parse CSV file
function parseCSV(filePath) {
    const content = fs.readFileSync(filePath, 'utf-8');
    const lines = content.split('\n').filter(line => line.trim());
    const headers = lines[0].split(',');
    
    const data = [];
    for (let i = 1; i < lines.length; i++) {
        const values = lines[i].split(',');
        const row = {};
        headers.forEach((header, index) => {
            row[header.trim()] = values[index] ? values[index].trim() : '';
        });
        data.push(row);
    }
    
    return { headers, data };
}

// Parse DXY historical data
function parseDXYData(filePath) {
    const content = fs.readFileSync(filePath, 'utf-8');
    const lines = content.split('\n').filter(line => line.trim());
    
    const dxyMap = new Map();
    
    for (let i = 1; i < lines.length; i++) {
        const match = lines[i].match(/"([^"]+)","([^"]+)","([^"]+)","([^"]+)","([^"]+)","([^"]*)","([^"]*)"/);
        
        if (match) {
            const dateStr = match[1]; // MM/DD/YYYY format
            const changePercent = match[7]; // Change %
            
            // Convert MM/DD/YYYY to YYYY-MM-DD
            const [month, day, year] = dateStr.split('/');
            const isoDate = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
            
            // Determine direction and magnitude
            if (changePercent && changePercent !== '') {
                const value = parseFloat(changePercent.replace('%', ''));
                const direction = value >= 0 ? 'UP' : 'DOWN';
                const magnitude = Math.abs(value);
                
                dxyMap.set(isoDate, {
                    direction: direction,
                    magnitude: magnitude.toFixed(2)
                });
            }
        }
    }
    
    console.log(`\nParsed ${dxyMap.size} DXY records`);
    console.log('Sample DXY dates:', Array.from(dxyMap.keys()).slice(0, 5));
    
    return dxyMap;
}

// Escape CSV field
function escapeCSV(field) {
    const str = String(field);
    if (str.includes(',') || str.includes('"') || str.includes('\n')) {
        return `"${str.replace(/"/g, '""')}"`;
    }
    return str;
}

console.log(`Reading training data from: ${TRAINING_DATA_FILE}`);
const trainingData = parseCSV(TRAINING_DATA_FILE);
console.log(`Found ${trainingData.data.length} rows in training data`);
console.log('Sample training dates:', trainingData.data.slice(0, 5).map(r => r[0]));

console.log(`\nReading DXY data from: ${DXY_FILE}`);
const dxyData = parseDXYData(DXY_FILE);
console.log(`Found ${dxyData.size} dates in DXY data`);

// Replace direction and magnitude with DXY values
let matchedCount = 0;
let unmatchedCount = 0;

console.log('\nReplacing direction and magnitude with DXY values...');

trainingData.data.forEach((row, index) => {
    const date = row.date;
    
    if (dxyData.has(date)) {
        const dxy = dxyData.get(date);
        row.direction = dxy.direction;
        row.magnitude = dxy.magnitude;
        matchedCount++;
    } else {
        unmatchedCount++;
        console.log(`[WARNING] No DXY data found for date: ${date}`);
    }
});

console.log(`\nMatched: ${matchedCount} dates`);
console.log(`Unmatched: ${unmatchedCount} dates`);

// Write updated CSV
console.log(`\nWriting updated data to: ${OUTPUT_FILE}`);

const outputLines = [];
// Write header
outputLines.push(trainingData.headers.map(escapeCSV).join(','));

// Write data rows
trainingData.data.forEach(row => {
    const values = trainingData.headers.map(header => escapeCSV(row[header]));
    outputLines.push(values.join(','));
});

fs.writeFileSync(OUTPUT_FILE, outputLines.join('\n'));

console.log('\n========================================');
console.log('DXY replacement complete!');
console.log(`Output saved to: ${OUTPUT_FILE}`);
console.log('========================================');

// Show sample of replaced data
console.log('\nSample of replaced data (first 5 rows):');
console.log('Date\t\t| Today State | Tomorrow State | Next Week State | Direction | Magnitude');
console.log('-'.repeat(90));
for (let i = 0; i < Math.min(5, trainingData.data.length); i++) {
    const row = trainingData.data[i];
    console.log(`${row.date}\t| ${row.today_state_of_the_economy}%\t| ${row.tomorrow_state_of_the_economy}%\t| ${row.next_week_state_of_the_economy}%\t| ${row.direction}\t| ${row.magnitude}%`);
}
