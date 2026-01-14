const moment = require('moment-timezone');
const sqlite3 = require('sqlite3').verbose();
const path = require('path');

// Test the conversion function
function convertNYTimeToUTC(dateStr, timeStr) {
  try {
    const nyDateTime = moment.tz(`${dateStr} ${timeStr}`, 'America/New_York');
    const utcDateTime = nyDateTime.clone().tz('UTC');
    
    return {
      date: utcDateTime.format('YYYY-MM-DD'),
      time: utcDateTime.format('HH:mm:ss')
    };
  } catch (err) {
    return { date: dateStr, time: timeStr };
  }
}

// Test conversion
console.log('\n=== Testing Timezone Conversion ===\n');
const testCases = [
  { date: '2026-01-09', time: '08:30:00', name: 'Nonfarm Payrolls' },
  { date: '2026-01-09', time: '03:30:00', name: 'Other Event' }
];

testCases.forEach(test => {
  const result = convertNYTimeToUTC(test.date, test.time);
  console.log(`${test.name}:`);
  console.log(`  Input:  ${test.date} ${test.time} EST`);
  console.log(`  Output: ${result.date} ${result.time} UTC`);
  console.log('');
});

// Now check what's in the database
const dbPath = path.join(__dirname, '..', 'database', 'app.db');
const db = new sqlite3.Database(dbPath);

console.log('=== Database Check ===\n');
db.all(`SELECT event_name, event_date, event_time, currency 
        FROM economic_events 
        WHERE event_date = '2026-01-09' AND event_time = '08:30:00'
        LIMIT 5`, (err, rows) => {
  if (err) {
    console.error('Error:', err);
  } else if (rows.length > 0) {
    console.log('Events in database with time 08:30:00:');
    rows.forEach(r => {
      console.log(`  ${r.event_name} | ${r.event_date} ${r.event_time} | ${r.currency}`);
    });
    console.log('\n❌ PROBLEM: Database contains EST times (08:30), not UTC times (13:30)');
  } else {
    console.log('No events found with 08:30:00');
  }
  
  // Check if UTC times exist
  db.all(`SELECT event_name, event_date, event_time, currency 
          FROM economic_events 
          WHERE event_date = '2026-01-09' AND event_time = '13:30:00'
          LIMIT 5`, (err, rows) => {
    console.log('\nEvents in database with time 13:30:00 (correct UTC):');
    if (err) {
      console.error('Error:', err);
    } else if (rows.length > 0) {
      rows.forEach(r => {
        console.log(`  ${r.event_name} | ${r.event_date} ${r.event_time} | ${r.currency}`);
      });
      console.log('\n✅ GOOD: Database contains properly converted UTC times');
    } else {
      console.log('  None found');
      console.log('\n❌ CONFIRMED: Scraper is NOT saving converted UTC times');
    }
    db.close();
  });
});
