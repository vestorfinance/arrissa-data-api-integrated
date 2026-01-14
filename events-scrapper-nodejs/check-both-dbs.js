const sqlite3 = require('sqlite3').verbose();
const path = require('path');
const fs = require('fs');

// Check both possible database locations
const dbPath1 = path.join(__dirname, '..', 'database', 'app.db');
const dbPath2 = path.join(__dirname, '..', 'economic_events.db');

console.log('\n=== Checking Database Locations ===\n');

function checkDatabase(dbPath, label) {
  return new Promise((resolve) => {
    if (!fs.existsSync(dbPath)) {
      console.log(`${label}: NOT FOUND at ${dbPath}`);
      resolve();
      return;
    }
    
    const db = new sqlite3.Database(dbPath);
    db.get(`SELECT COUNT(*) as count FROM economic_events`, (err, row) => {
      if (err) {
        console.log(`${label}: ERROR - ${err.message}`);
        db.close();
        resolve();
        return;
      }
      
      console.log(`${label}: ${dbPath}`);
      console.log(`   Total events: ${row.count}`);
      
      db.all(`SELECT event_name, event_date, event_time, currency 
              FROM economic_events 
              WHERE event_name LIKE '%Nonfarm%' 
              ORDER BY event_date DESC 
              LIMIT 3`, (err, rows) => {
        if (err) {
          console.log(`   Error querying: ${err.message}`);
        } else if (rows.length > 0) {
          console.log(`   Sample Nonfarm Payrolls events:`);
          rows.forEach(r => {
            console.log(`   - ${r.event_name} | ${r.event_date} ${r.event_time}`);
          });
        } else {
          console.log(`   No Nonfarm events found`);
        }
        console.log('');
        db.close();
        resolve();
      });
    });
  });
}

async function main() {
  await checkDatabase(dbPath1, 'DATABASE 1 (scraper path)');
  await checkDatabase(dbPath2, 'DATABASE 2 (production path)');
}

main();
