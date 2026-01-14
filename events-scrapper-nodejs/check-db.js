const sqlite3 = require('sqlite3').verbose();
const path = require('path');

// Check both database locations
const dbPath1 = path.join(__dirname, '..', 'database', 'app.db');
const dbPath2 = path.join(__dirname, '..', 'economic_events.db');

console.log('Checking database locations:');
console.log('1. database/app.db');
console.log('2. economic_events.db\n');

const dbPath = dbPath1; // Use the scraper's database first
const db = new sqlite3.Database(dbPath);

db.all(`SELECT event_name, event_date, event_time, currency 
        FROM economic_events 
        WHERE event_name LIKE '%Nonfarm%' 
        ORDER BY event_date DESC 
        LIMIT 5`, (err, rows) => {
  if (err) {
    console.error('Error:', err);
  } else {
    console.log('\nNonfarm Payrolls events in database:');
    console.log('=====================================');
    rows.forEach(r => {
      console.log(`${r.event_name} | ${r.event_date} ${r.event_time} | ${r.currency}`);
    });
  }
  db.close();
});
