const sqlite3 = require('sqlite3').verbose();
const path = require('path');

const dbPath = path.join(__dirname, '..', 'database', 'app.db');
const db = new sqlite3.Database(dbPath);

console.log('\n=== Checking Event Times ===\n');

db.all(`SELECT event_name, event_date, event_time, currency 
        FROM economic_events 
        WHERE event_date = '2026-01-09' 
        ORDER BY event_time`, 
(err, rows) => {
  if (err) {
    console.error('Error:', err.message);
  } else if (rows.length === 0) {
    console.log('No events found on 2026-01-09');
  } else {
    console.log('Events on 2026-01-09:');
    rows.forEach(r => {
      let status = '';
      if (r.event_time === '13:30:00') status = ' ✅ UTC';
      else if (r.event_time === '08:30:00') status = ' ❌ EST (should be 13:30)';
      console.log(`  ${r.event_time}${status} | ${r.event_name} (${r.currency})`);
    });
  }
  db.close();
});
