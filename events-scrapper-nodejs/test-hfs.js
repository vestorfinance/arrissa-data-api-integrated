/**
 * Test High Frequency Scraping Mechanism
 * Tests the complete HFS flow including:
 * - Browser initialization
 * - Event extraction
 * - Database saving with unique event_ids (including currency)
 * - Multiple scrape cycles
 */

const puppeteer = require('puppeteer');
const sqlite3 = require('sqlite3').verbose();
const crypto = require('crypto');
const path = require('path');

const DB_PATH = path.join(__dirname, '..', 'database', 'app.db');

const allowedCurrencies = new Set(['USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'CNY']);
const requiredImpact = new Set(['High', 'Moderate']);

// Database helpers
function dbGet(db, sql, params = []) {
  return new Promise((resolve, reject) => {
    db.get(sql, params, (err, row) => {
      if (err) reject(err);
      else resolve(row);
    });
  });
}

function dbRun(db, sql, params = []) {
  return new Promise((resolve, reject) => {
    db.run(sql, params, function(err) {
      if (err) reject(err);
      else resolve(this);
    });
  });
}

function getDB() {
  return new Promise((resolve, reject) => {
    const db = new sqlite3.Database(DB_PATH, (err) => {
      if (err) reject(err);
      else resolve(db);
    });
  });
}

function dbClose(db) {
  return new Promise((resolve) => {
    db.close(() => resolve());
  });
}

function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

// Generate event IDs with currency included
function generateEventIds(event, date, time) {
  // Include currency to ensure uniqueness across different countries
  const baseId = `${event.eventName}-${event.currency}-${date}-${time}`;
  const event_id = crypto.createHash('sha1').update(baseId).digest('hex');
  
  const cleanName = event.eventName
    .replace(/\s*\((?!(?:MoM|YoY|QoQ)\))[^)]*\)/g, '')
    .trim();
  
  const hashBuffer = crypto.createHash('md5').update(cleanName).digest();
  let consistentId = '';
  for (let i = 0; i < 5; i++) {
    const charCode = 65 + (hashBuffer[i] % 26);
    consistentId += String.fromCharCode(charCode);
  }
  
  return { event_id, consistentId };
}

function toNumber(val) {
  if (!val) return null;
  let sanitized = val.replace(/[\s,%]+/g, '');
  if (sanitized === '-' || sanitized === '') return null;
  let multiplier = 1;
  if (/M/i.test(sanitized)) {
    multiplier = 1e6;
    sanitized = sanitized.replace(/M/i, '');
  } else if (/K/i.test(sanitized)) {
    multiplier = 1e3;
    sanitized = sanitized.replace(/K/i, '');
  }
  const num = parseFloat(sanitized);
  return isNaN(num) ? null : num * multiplier;
}

function parseDate(rawDateStr) {
  const monthMap = {
    january: '01', february: '02', march: '03', april: '04',
    may: '05', june: '06', july: '07', august: '08',
    september: '09', october: '10', november: '11', december: '12'
  };

  let cleanStr = rawDateStr.replace(/^[A-Za-z]+,\s*/, '').trim();

  if (cleanStr.includes(',')) {
    const parts = cleanStr.split(',');
    if (parts.length >= 2) {
      const datePart = parts[0].trim();
      const yearPart = parts[1].trim();
      
      const dateComponents = datePart.split(/\s+/);
      if (dateComponents.length >= 2) {
        const monthName = dateComponents[0].toLowerCase();
        const day = parseInt(dateComponents[1]);
        const year = parseInt(yearPart);
        
        const monthNum = monthMap[monthName];
        
        if (monthNum && day >= 1 && day <= 31 && year > 1900) {
          return `${year}-${monthNum}-${String(day).padStart(2, '0')}`;
        }
      }
    }
  }
  
  return null;
}

function parseEventTime(event, lastKnownDate) {
  let date = null;
  if (event.currentDateStr && event.currentDateStr.trim() !== '') {
    date = parseDate(event.currentDateStr);
  } else if (lastKnownDate) {
    date = parseDate(lastKnownDate);
  }
  
  if (!date) {
    date = new Date().toISOString().split('T')[0];
  }
  
  let time = '00:00:00';
  if (event.timeText) {
    const cleanTime = event.timeText.replace(/\s+/g, '');
    const match = cleanTime.match(/^(\d{1,2}):(\d{2})$/);
    if (match) {
      const hour = match[1].padStart(2, '0');
      const minute = match[2];
      time = `${hour}:${minute}:00`;
    }
  }
  
  return { date, time };
}

async function saveCurrentWeekEventToDB(event, lastKnownDate) {
  const { date, time } = parseEventTime(event, lastKnownDate);
  const { event_id, consistentId } = generateEventIds(event, date, time);
  
  const db = await getDB();
  try {
    const forecast = toNumber(event.forecastValue);
    const actual = toNumber(event.actualValue);
    const previous = toNumber(event.previousValue);
    
    // Check for existing events with same name, currency, date AND time
    const existing = await dbGet(db,
      `SELECT event_id FROM economic_events 
       WHERE event_name = ? AND currency = ? AND event_date = ? AND event_time = ?`,
      [event.eventName, event.currency, date, time]
    );
    
    if (existing) {
      // UPDATE only the data fields, keep the original event_id
      const updateSql = `
        UPDATE economic_events 
        SET forecast_value = ?, 
            actual_value = ?, 
            previous_value = ?, 
            impact_level = ?
        WHERE event_name = ? AND currency = ? AND event_date = ? AND event_time = ?
      `;
      
      await dbRun(db, updateSql, [
        forecast,
        actual,
        previous,
        event.impactLevel,
        event.eventName,
        event.currency,
        date,
        time
      ]);
      
      console.log(`  ✅ UPDATED: "${event.eventName}" (${event.currency}) on ${date} ${time}`);
      return;
    }
    
    // Insert new event if it doesn't exist
    const sql = `
      INSERT INTO economic_events 
      (event_id, event_name, event_date, event_time, currency, forecast_value, actual_value, previous_value, impact_level, consistent_event_id)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    `;
    
    await dbRun(db, sql, [
      event_id,
      event.eventName,
      date,
      time,
      event.currency,
      forecast,
      actual,
      previous,
      event.impactLevel,
      consistentId
    ]);
    
    console.log(`  ✅ INSERTED: "${event.eventName}" (${event.currency}) on ${date} ${time}`);
    
  } catch (err) {
    console.error(`  ❌ Error saving: ${err.message}`);
    console.error(`     Event: "${event.eventName}", Currency: ${event.currency}, Date: ${date}, Time: ${time}`);
  } finally {
    await dbClose(db);
  }
}

async function createBrowser() {
  const browser = await puppeteer.launch({
    headless: true,
    args: [
      '--no-sandbox',
      '--disable-setuid-sandbox',
      '--disable-dev-shm-usage',
      '--disable-blink-features=AutomationControlled'
    ]
  });
  
  const page = await browser.newPage();
  await page.setViewport({ width: 1920, height: 1080 });
  
  // Spoof browser fingerprint
  await page.evaluateOnNewDocument(() => {
    Object.defineProperty(navigator, 'webdriver', { get: () => false });
    Object.defineProperty(navigator, 'plugins', { get: () => [1, 2, 3, 4, 5] });
    Object.defineProperty(navigator, 'languages', { get: () => ['en-US', 'en'] });
  });
  
  await page.setRequestInterception(true);
  page.on('request', req => {
    if (['image', 'stylesheet', 'font'].includes(req.resourceType())) {
      req.abort();
    } else {
      req.continue({
        headers: {
          ...req.headers(),
          'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
          'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
          'Accept-Language': 'en-US,en;q=0.9',
          'Accept-Encoding': 'gzip, deflate, br',
          'Connection': 'keep-alive',
          'Upgrade-Insecure-Requests': '1',
          'Sec-Fetch-Dest': 'document',
          'Sec-Fetch-Mode': 'navigate',
          'Sec-Fetch-Site': 'none',
          'Sec-Ch-Ua': '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
          'Sec-Ch-Ua-Mobile': '?0',
          'Sec-Ch-Ua-Platform': '"Windows"',
          'Cache-Control': 'max-age=0',
          'Referer': 'https://www.google.com/'
        }
      });
    }
  });
  
  console.log('📡 Navigating to economic calendar...');
  await page.goto('https://sslecal2.forexprostools.com/', {
    waitUntil: 'domcontentloaded',
    timeout: 180000
  });
  
  await page.waitForSelector('#ecEventsTable', { timeout: 120000 });
  console.log('✅ Page loaded successfully\n');
  
  return { browser, page };
}

async function refreshPage(page) {
  await page.reload({ 
    waitUntil: 'domcontentloaded', 
    timeout: 180000 
  });
  await page.waitForSelector('#ecEventsTable', { timeout: 120000 });
}

async function extractCurrentWeekEvents(page) {
  return await page.evaluate(() => {
    let currentDateStr = "";
    const events = [];
    const rows = document.querySelectorAll("#ecEventsTable tr");

    function mapSentimentTitleToImpact(title) {
      const t = (title || "").trim().toLowerCase();
      if (t.includes("high")) return "High";
      if (t.includes("moderate") || t.includes("medium")) return "Moderate";
      return "Low";
    }

    rows.forEach(row => {
      if (row.classList.contains('theDay') || row.querySelector('td.theDay')) {
        const dayCell = row.querySelector('td.theDay') || row;
        currentDateStr = dayCell.innerText.trim();
        return;
      }

      if (!(row.id && row.id.startsWith('eventRowId'))) return;

      const sentimentCell = row.querySelector('.sentiment');
      if (!sentimentCell) return;

      const sentimentText = sentimentCell.getAttribute('title') || "";
      const impactLevel = mapSentimentTitleToImpact(sentimentText);

      const timeCell = row.querySelector('.time');
      const timeText = timeCell ? timeCell.innerText.trim() : "";

      const currencyCell = row.querySelector('.flagCur');
      let currency = "";
      if (currencyCell) {
        currency = currencyCell.innerText.trim().split(/\s+/)[0];
      }

      const eventCell = row.querySelector('.event');
      const eventName = eventCell ? eventCell.innerText.trim() : "";

      const actualCell = row.querySelector('.act');
      const actualValue = actualCell ? actualCell.innerText.trim() : "";

      const forecastCell = row.querySelector('.fore');
      const forecastValue = forecastCell ? forecastCell.innerText.trim() : "";

      const previousCell = row.querySelector('.prev');
      const previousValue = previousCell ? previousCell.innerText.trim() : "";

      events.push({
        timeText,
        currentDateStr,
        eventName,
        currency,
        actualValue,
        forecastValue,
        previousValue,
        impactLevel,
        eventTimestamp: null
      });
    });

    return events;
  });
}

async function testHighFrequencyScraping() {
  console.log('🧪 Testing High Frequency Scraping Mechanism\n');
  console.log('=' .repeat(60));
  
  let browser, page;
  
  try {
    // Initialize browser
    const browserData = await createBrowser();
    browser = browserData.browser;
    page = browserData.page;
    
    // Run 3 scrape cycles (simulating HFS)
    const CYCLES = 3;
    const DELAY_BETWEEN_CYCLES = 5000; // 5 seconds
    
    for (let cycle = 1; cycle <= CYCLES; cycle++) {
      console.log(`\n🔄 Cycle ${cycle}/${CYCLES}`);
      console.log('-'.repeat(60));
      
      // Extract events
      const events = await extractCurrentWeekEvents(page);
      let lastKnownDate = null;
      let savedCount = 0;
      let errorCount = 0;
      
      // Process events
      for (const event of events) {
        if (event.currentDateStr && event.currentDateStr.trim() !== '') {
          lastKnownDate = event.currentDateStr;
        }
        
        if (
          allowedCurrencies.has(event.currency) &&
          !event.eventName.startsWith("CFTC") &&
          requiredImpact.has(event.impactLevel)
        ) {
          try {
            await saveCurrentWeekEventToDB(event, lastKnownDate);
            savedCount++;
          } catch (err) {
            errorCount++;
          }
        }
      }
      
      console.log(`\n📊 Cycle ${cycle} Summary: ${savedCount} events processed, ${errorCount} errors`);
      
      // Refresh page for next cycle (except last cycle)
      if (cycle < CYCLES) {
        console.log(`\n⏳ Waiting ${DELAY_BETWEEN_CYCLES/1000}s before next cycle...`);
        await sleep(DELAY_BETWEEN_CYCLES);
        console.log('🔄 Refreshing page...');
        await refreshPage(page);
      }
    }
    
    // Verify database
    console.log('\n' + '='.repeat(60));
    console.log('🔍 Verifying Database\n');
    
    const db = await getDB();
    const totalEvents = await dbGet(db, 'SELECT COUNT(*) as count FROM economic_events');
    const usdEvents = await dbGet(db, 'SELECT COUNT(*) as count FROM economic_events WHERE currency = "USD"');
    const cadEvents = await dbGet(db, 'SELECT COUNT(*) as count FROM economic_events WHERE currency = "CAD"');
    const duplicates = await dbGet(db, 
      'SELECT COUNT(*) as count FROM (SELECT event_id, COUNT(*) FROM economic_events GROUP BY event_id HAVING COUNT(*) > 1)'
    );
    
    // Check for events with same name at same time but different currencies
    const multiCurrencyEvents = await dbGet(db, `
      SELECT event_name, event_date, event_time, COUNT(DISTINCT currency) as currency_count
      FROM economic_events 
      GROUP BY event_name, event_date, event_time 
      HAVING currency_count > 1
      LIMIT 1
    `);
    
    await dbClose(db);
    
    console.log(`Total Events: ${totalEvents.count}`);
    console.log(`USD Events: ${usdEvents.count}`);
    console.log(`CAD Events: ${cadEvents.count}`);
    console.log(`Duplicate event_ids: ${duplicates.count}`);
    
    if (multiCurrencyEvents) {
      console.log(`\n✅ Multi-currency event found: "${multiCurrencyEvents.event_name}"`);
      console.log(`   Date/Time: ${multiCurrencyEvents.event_date} ${multiCurrencyEvents.event_time}`);
      console.log(`   Currencies: ${multiCurrencyEvents.currency_count} different currencies`);
    }
    
    console.log('\n' + '='.repeat(60));
    if (duplicates.count === 0) {
      console.log('✅ TEST PASSED: No duplicate event_ids found!');
      console.log('✅ High Frequency Scraping mechanism is working correctly!');
    } else {
      console.log('❌ TEST FAILED: Duplicate event_ids found!');
    }
    
  } catch (error) {
    console.error('\n❌ Test failed:', error.message);
  } finally {
    if (browser) {
      await browser.close();
      console.log('\n🔒 Browser closed');
    }
  }
}

// Run the test
testHighFrequencyScraping().then(() => {
  console.log('\n✅ Test completed');
  process.exit(0);
}).catch(err => {
  console.error('❌ Test error:', err);
  process.exit(1);
});
