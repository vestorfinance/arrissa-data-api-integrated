/**
 * ------------------------------------------------------------------------
 *  Author : Ngonidzashe Jiji
 *  Handles: Instagram: @davidrichchild
 *           Telegram: t.me/david_richchild
 *           TikTok: davidrichchild
 *  URLs    : https://arrissadata.com
 *            https://arrissatechnologies.com
 *            https://arrissa.trade
 *
 *  Course  : https://www.udemy.com/course/6804721
 *
 *  Permission:
 *    You are granted permission to use, copy, modify, and distribute this
 *    code for personal or commercial projects, provided that the author
 *    details above remain intact and visible in the distributed code or
 *    accompanying documentation.
 *
 *  Requirements:
 *    - Keep this header (author details, URLs, and course link) in the
 *      distributed source or bundled output. Do not remove or hide it.
 *
 *  Disclaimer:
 *    This software is provided "AS IS", without any warranty. The author
 *    will not be liable for any damages or claims arising from its use.
 *
 *  Version: 1.0
 *  Date:    2025-09-20
 * ------------------------------------------------------------------------
 */
 const puppeteer = require('puppeteer');
const sqlite3 = require('sqlite3').verbose();
const moment = require('moment-timezone');
const crypto = require('crypto');
const cron = require('node-cron');
const readline = require('readline');
const fs = require('fs');
const path = require('path');

// ✅ ATTRIBUTION VERIFICATION - DO NOT REMOVE
const AUTHOR_SIGNATURE = 'Ngonidzashe_Jiji_@davidrichchild_6804721';
function verifyAttribution() {
  const sourceCode = fs.readFileSync(__filename, 'utf8');
  const required = ['Ngonidzashe Jiji', '@davidrichchild', '6804721'];
  for (const check of required) {
    if (!sourceCode.includes(check)) {
      console.error('\n❌ ATTRIBUTION ERROR: Required author information has been removed.');
      console.error('This software requires proper attribution to function.\n');
      process.exit(1);
    }
  }
}

function displayBanner() {
  console.log('\n' + '═'.repeat(60));
  console.log('  Economic Events Scraper');
  console.log('  By Ngonidzashe Jiji');
  console.log('  Instagram: @davidrichchild');
  console.log('═'.repeat(60) + '\n');
}

/// ✅ SQLite database settings
const dbPath = path.join(__dirname, '..', 'database', 'app.db');

// Helper function to get database connection
function getDB() {
  return new Promise((resolve, reject) => {
    const db = new sqlite3.Database(dbPath, (err) => {
      if (err) reject(err);
      else resolve(db);
    });
  });
}

// Helper function to run SQL with parameters
function dbRun(db, sql, params = []) {
  return new Promise((resolve, reject) => {
    db.run(sql, params, function(err) {
      if (err) reject(err);
      else resolve({ lastID: this.lastID, changes: this.changes });
    });
  });
}

// Helper function to query SQL
function dbAll(db, sql, params = []) {
  return new Promise((resolve, reject) => {
    db.all(sql, params, (err, rows) => {
      if (err) reject(err);
      else resolve(rows);
    });
  });
}

// Helper function to get single row
function dbGet(db, sql, params = []) {
  return new Promise((resolve, reject) => {
    db.get(sql, params, (err, row) => {
      if (err) reject(err);
      else resolve(row);
    });
  });
}

// Helper function to close database
function dbClose(db) {
  return new Promise((resolve, reject) => {
    db.close((err) => {
      if (err) reject(err);
      else resolve();
    });
  });
}

// Allowed currencies and required impact level
const allowedCurrencies = new Set([
  'USD','CHF','EUR','GBP','AUD','NZD','CAD','JPY','CNY','MXN','INR','ZAR','KRW'
]);
const requiredImpact = new Set(['High','Moderate']);

// Global flags
let noUpcomingEventLogged = false;
let nextWeekScrapedToday = false;
let isHighFrequencyMode = false;
let isBrowserActive = false;
let activeBrowserInstance = null;

// Helper sleep function
function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

// Force garbage collection and memory cleanup
async function cleanupMemory() {
  if (global.gc) {
    global.gc();
    console.log('✓ Memory cleaned');
  }
  await sleep(2000); // Give time for cleanup
}

// Wait for browser to be available
async function waitForBrowserAvailability(maxWaitTime = 300000) {
  const startTime = Date.now();
  while (isBrowserActive) {
    if (Date.now() - startTime > maxWaitTime) {
      throw new Error('Browser lock timeout - another scraper is running too long');
    }
    console.log('⏳ Waiting for browser to be available...');
    await sleep(5000);
  }
}

// Acquire browser lock
async function acquireBrowserLock(scraperName) {
  await waitForBrowserAvailability();
  isBrowserActive = true;
  console.log(`🔒 Browser locked by: ${scraperName}`);
}

// Release browser lock with cleanup
async function releaseBrowserLock(browser, scraperName) {
  if (browser) {
    try {
      await browser.close();
      console.log(`✓ Browser closed by: ${scraperName}`);
    } catch (e) {
      console.error(`Error closing browser: ${e.message}`);
    }
  }
  activeBrowserInstance = null;
  isBrowserActive = false;
  console.log(`🔓 Browser lock released by: ${scraperName}`);
  await cleanupMemory();
}

// 🔥 HARDCODED DATE PARSER
function parseRawDateString(rawDateStr) {
  if (!rawDateStr || rawDateStr.trim() === '') {
    throw new Error('CRITICAL: Empty date string - cannot proceed without valid date');
  }

  // Month name to number mapping
  const monthMap = {
    'january': '01', 'jan': '01',
    'february': '02', 'feb': '02',
    'march': '03', 'mar': '03',
    'april': '04', 'apr': '04',
    'may': '05',
    'june': '06', 'jun': '06',
    'july': '07', 'jul': '07',
    'august': '08', 'aug': '08',
    'september': '09', 'sep': '09',
    'october': '10', 'oct': '10',
    'november': '11', 'nov': '11',
    'december': '12', 'dec': '12'
  };

  try {
    // Remove day name if present: "Sunday, September 14, 2025" → "September 14, 2025"
    let cleanStr = rawDateStr.replace(/^[A-Za-z]+,\s*/, '').trim();

    // Handle format: "September 14, 2025"
    if (cleanStr.includes(',')) {
      const parts = cleanStr.split(',');
      if (parts.length >= 2) {
        const datePart = parts[0].trim(); // "September 14"
        const yearPart = parts[1].trim(); // "2025"
        
        const dateComponents = datePart.split(/\s+/); // ["September", "14"]
        if (dateComponents.length >= 2) {
          const monthName = dateComponents[0].toLowerCase();
          const day = parseInt(dateComponents[1]);
          const year = parseInt(yearPart);
          
          const monthNum = monthMap[monthName];
          
          if (monthNum && day >= 1 && day <= 31 && year > 1900) {
            const formattedDate = `${year}-${monthNum}-${String(day).padStart(2, '0')}`;
            return formattedDate;
          }
        }
      }
    }
    
    // Handle format: "2025-09-17" or "2025/09/17"
    if (cleanStr.match(/^\d{4}[-\/]\d{1,2}[-\/]\d{1,2}$/)) {
      const parts = cleanStr.split(/[-\/]/);
      const year = parseInt(parts[0]);
      const month = parseInt(parts[1]);
      const day = parseInt(parts[2]);
      
      if (year > 1900 && month >= 1 && month <= 12 && day >= 1 && day <= 31) {
        const formattedDate = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        return formattedDate;
      }
    }
    
    // Handle format: "16/09/2025" or "16-09-2025"
    if (cleanStr.match(/^\d{1,2}[-\/]\d{1,2}[-\/]\d{4}$/)) {
      const parts = cleanStr.split(/[-\/]/);
      const day = parseInt(parts[0]);
      const month = parseInt(parts[1]);
      const year = parseInt(parts[2]);
      
      if (year > 1900 && month >= 1 && month <= 12 && day >= 1 && day <= 31) {
        const formattedDate = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        return formattedDate;
      }
    }

    throw new Error('No matching date format found');
    
  } catch (err) {
    throw new Error(`CRITICAL: Failed to parse date "${rawDateStr}" - ${err.message}`);
  }
}

// 🔥 HARDCODED TIME PARSER
function parseRawTimeString(rawTimeStr) {
  if (!rawTimeStr || rawTimeStr.trim() === '') {
    return '00:00:00';
  }

  try {
    let cleanTime = rawTimeStr.trim();
    
    // Handle "14:30" format
    if (cleanTime.match(/^\d{1,2}:\d{2}$/)) {
      const parts = cleanTime.split(':');
      const hours = parseInt(parts[0]);
      const minutes = parseInt(parts[1]);
      
      if (hours >= 0 && hours <= 23 && minutes >= 0 && minutes <= 59) {
        return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:00`;
      }
    }
    
    // Handle "14:30:45" format
    if (cleanTime.match(/^\d{1,2}:\d{2}:\d{2}$/)) {
      const parts = cleanTime.split(':');
      const hours = parseInt(parts[0]);
      const minutes = parseInt(parts[1]);
      const seconds = parseInt(parts[2]);
      
      if (hours >= 0 && hours <= 23 && minutes >= 0 && minutes <= 59 && seconds >= 0 && seconds <= 59) {
        return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
      }
    }
    
    // Handle single number like "14" (assume it's hours)
    if (cleanTime.match(/^\d{1,2}$/)) {
      const hours = parseInt(cleanTime);
      if (hours >= 0 && hours <= 23) {
        return `${String(hours).padStart(2, '0')}:00:00`;
      }
    }

    throw new Error('No matching time format found');
    
  } catch (err) {
    return '00:00:00';
  }
}

// 🔥 FIXED TIMEZONE CONVERSION - Website stores times in EST (UTC-5) ALWAYS
function convertNYTimeToUTC(dateStr, timeStr) {
  try {
    // IMPORTANT: The website stores ALL times in fixed EST (UTC-5) offset
    // regardless of DST. Simply add 5 hours to get UTC.
    // 07:30 EST + 5 hours = 12:30 UTC
    // 08:30 EST + 5 hours = 13:30 UTC
    
    const [hours, minutes, seconds] = timeStr.split(':').map(Number);
    const utcHours = (hours + 5) % 24;
    let utcDate = dateStr;
    
    // Handle day rollover if adding 5 hours crosses midnight
    if (hours + 5 >= 24) {
      const dateMoment = moment(dateStr).add(1, 'day');
      utcDate = dateMoment.format('YYYY-MM-DD');
    }
    
    const utcTimeStr = `${String(utcHours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds || 0).padStart(2, '0')}`;
    
    return {
      date: utcDate,
      time: utcTimeStr
    };
    
  } catch (err) {
    console.error(`❌ Timezone conversion error: ${err.message}`);
    return { date: dateStr, time: timeStr };
  }
}

// 🔥 BULLETPROOF DATE/TIME CONVERSION WITH PROPER DATE TRACKING
function parseEventTime(event, lastKnownDate) {
  let dateStr, timeStr;
  
  // 🛡️ STEP 1: Try to use eventTimestamp first
  if (event.eventTimestamp && 
      event.eventTimestamp !== 'null' && 
      event.eventTimestamp !== null &&
      event.eventTimestamp.trim() !== '') {
    
    const parts = event.eventTimestamp.split(' ');
    if (parts.length >= 2) {
      dateStr = parts[0];
      timeStr = parts[1];
    }
  }
  
  // 🛡️ STEP 2: Parse using hardcoded functions
  if (!dateStr || !timeStr) {
    // Parse date - use currentDateStr if available, otherwise use lastKnownDate
    if (event.currentDateStr && event.currentDateStr.trim() !== '') {
      dateStr = parseRawDateString(event.currentDateStr);
    } else if (lastKnownDate && lastKnownDate.trim() !== '') {
      dateStr = parseRawDateString(lastKnownDate);
    }
    
    // Continue parsing if not already done
    if (!dateStr) {
      if (event.currentDateStr && event.currentDateStr.trim() !== '') {
        dateStr = parseRawDateString(event.currentDateStr);
      } else if (lastKnownDate && lastKnownDate.trim() !== '') {
        dateStr = parseRawDateString(lastKnownDate);
      } else {
        throw new Error(`CRITICAL: No date available for event "${event.eventName}"`);
      }
    }
    
    timeStr = parseRawTimeString(event.timeText);
  }
  
  // 🛡️ STEP 3: Convert EST time to UTC (all events: +5 hours)
  const converted = convertNYTimeToUTC(dateStr, timeStr);
  // All other events: silent (no debug output)
  
  return {
    date: converted.date,
    time: converted.time
  };
}

// Unified function to generate unique event IDs
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

// Convert shorthand numeric values (e.g., "1.2M", "222K") to numbers
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

// Enhanced function to save current week events - ALWAYS REPLACES for current week
async function saveCurrentWeekEventToDB(event, lastKnownDate) {
  const { date, time } = parseEventTime(event, lastKnownDate);
  const { event_id, consistentId } = generateEventIds(event, date, time);
  
  // Only log NFP events
  if (event.eventName.includes('Nonfarm')) {
    console.log(`💾 SAVING NFP: "${event.eventName}" with date=${date}, time=${time} (should be UTC)`);
  }
  
  const db = await getDB();
  try {
    const forecast = toNumber(event.forecastValue);
    const actual = toNumber(event.actualValue);
    const previous = toNumber(event.previousValue);
    
    // DEBUG: Log the values being processed
    if (event.eventName.includes('Nonfarm')) {
      console.log(`🔍 DEBUG Nonfarm Payrolls:
        Raw forecastValue: "${event.forecastValue}"
        Converted forecast: ${forecast}
        Raw actualValue: "${event.actualValue}"
        Converted actual: ${actual}
        Date: ${date}, Time: ${time}`);
    }
    
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
      
      if (event.eventName.includes('Nonfarm')) {
        console.log(`✅ UPDATED Nonfarm with forecast=${forecast}, actual=${actual}`);
      }
      // Silent for other events
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
    
  } catch (err) {
    // Only log errors for NFP events
    if (event.eventName && event.eventName.includes('Nonfarm')) {
      console.error(`❌ Error saving NFP event: ${err.message}`);
      console.error(`   Event: "${event.eventName}", Date: ${date}, Time: ${time}, Currency: ${event.currency}`);
    }
  } finally {
    await dbClose(db);
  }
}

// Standard function for other events (next week, history) - prevents duplicates
async function saveEventToDB(event, lastKnownDate) {
  const { date, time } = parseEventTime(event, lastKnownDate);
  const { event_id, consistentId } = generateEventIds(event, date, time);
  
  const db = await getDB();
  try {
    // Check for exact duplicates using event_name, currency, date AND time
    const existing = await dbGet(db,
      `SELECT event_id FROM economic_events 
       WHERE event_name = ? AND currency = ? AND event_date = ? AND event_time = ?`,
      [event.eventName, event.currency, date, time]
    );
    
    if (existing) {
      // Silent: duplicate prevented
      return;
    }
    
    const forecast = toNumber(event.forecastValue);
    const actual = toNumber(event.actualValue);
    const previous = toNumber(event.previousValue);
    
    const sql = `
      INSERT OR REPLACE INTO economic_events 
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
    
    // Silent for non-NFP events
    
  } catch (err) {
    // Silent error for non-NFP events
  } finally {
    await dbClose(db);
  }
}

// User interaction functions
function askUserChoice() {
  const rl = readline.createInterface({
    input: process.stdin,
    output: process.stdout
  });
  return new Promise(resolve => {
    console.log("\n=== Economic Events Scraper ===");
    console.log("1. Run current week + next week scrapers (sequential)");
    console.log("2. Run events history scraper only");
    console.log("3. Run all scrapers (history separate)");
    console.log("4. Run recent events scraper only");
    console.log("5. Run next week only events scraper");
    console.log("6. Run this week only events scraper");
    console.log("7. Run current week + next week (one-time, no monitoring)");
    rl.question("Please select an option (1-7): ", answer => {
      rl.close();
      resolve(answer.trim());
    });
  });
}

function askRemoveEvents(weekType) {
  const rl = readline.createInterface({
    input: process.stdin,
    output: process.stdout
  });
  return new Promise(resolve => {
    rl.question(`Do you want to remove all events for ${weekType} before scraping? (yes/no): `, answer => {
      rl.close();
      resolve(answer.trim().toLowerCase());
    });
  });
}

// Database cleaning functions
async function cleanNextWeekDatabase() {
  const db = await getDB();
  try {
    const answer = await askRemoveEvents("next week");
    if (answer === 'yes') {
      const nextWeek = moment().add(1, 'week');
      const startOfWeek = nextWeek.clone().startOf('week').format('YYYY-MM-DD');
      const endOfWeek = nextWeek.clone().endOf('week').format('YYYY-MM-DD');
      
      await dbRun(db,
        "DELETE FROM economic_events WHERE event_date BETWEEN ? AND ?",
        [startOfWeek, endOfWeek]
      );
      console.log("All events for next week have been removed.");
    } else {
      console.log("No events were removed for next week.");
    }
  } catch (err) {
    console.error("Error cleaning next week database:", err.message);
  } finally {
    await dbClose(db);
  }
}

async function cleanCurrentWeekDatabase() {
  const db = await getDB();
  try {
    const answer = await askRemoveEvents("this week");
    if (answer === 'yes') {
      const startOfWeek = moment().startOf('week').format('YYYY-MM-DD');
      const endOfWeek = moment().endOf('week').format('YYYY-MM-DD');
      
      await dbRun(db,
        "DELETE FROM economic_events WHERE event_date BETWEEN ? AND ?",
        [startOfWeek, endOfWeek]
      );
      console.log("All events for this week have been removed.");
    } else {
      console.log("No events were removed for this week.");
    }
  } catch (err) {
    console.error("Error cleaning current week database:", err.message);
  } finally {
    await dbClose(db);
  }
}

async function cleanRecentEventsDatabase() {
  const db = await getDB();
  try {
    const answer = await askRemoveEvents("recent events (last 7 days)");
    if (answer === 'yes') {
      const sevenDaysAgo = moment().subtract(7, 'days').format('YYYY-MM-DD');
      
      await dbRun(db,
        "DELETE FROM economic_events WHERE event_date >= ?",
        [sevenDaysAgo]
      );
      console.log("All recent events (last 7 days) have been removed.");
    } else {
      console.log("No recent events were removed.");
    }
  } catch (err) {
    console.error("Error cleaning recent events database:", err.message);
  } finally {
    await dbClose(db);
  }
}

// Current week scraper functions
async function createCurrentWeekBrowser() {
  const browser = await puppeteer.launch({ 
    headless: true,
    args: [
      '--no-sandbox', 
      '--disable-setuid-sandbox', 
      '--disable-dev-shm-usage',
      '--disable-blink-features=AutomationControlled',
      '--disable-features=IsolateOrigins,site-per-process'
    ]
  });
  const page = await browser.newPage();
  
  // Set more realistic browser fingerprint
  await page.evaluateOnNewDocument(() => {
    Object.defineProperty(navigator, 'webdriver', { get: () => false });
    Object.defineProperty(navigator, 'plugins', { get: () => [1, 2, 3, 4, 5] });
    Object.defineProperty(navigator, 'languages', { get: () => ['en-US', 'en'] });
    window.chrome = { runtime: {} };
  });
  
  await page.setViewport({ width: 1920, height: 1080 });
  await page.setRequestInterception(true);
  
  page.on('request', req => {
    if (['image', 'stylesheet', 'font', 'media'].includes(req.resourceType())) {
      req.abort();
    } else {
      req.continue({
        headers: {
          ...req.headers(),
          'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
          'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
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

  console.log('Navigating to economic calendar...');
  try {
    await page.goto('https://sslecal2.forexprostools.com/', { 
      waitUntil: 'domcontentloaded', 
      timeout: 180000  // Increased to 3 minutes
    });
  } catch (error) {
    console.log('⚠️ Navigation timed out, trying with networkidle2...');
    await page.goto('https://sslecal2.forexprostools.com/', { 
      waitUntil: 'networkidle2', 
      timeout: 180000 
    });
  }
  console.log('Page loaded, waiting for table...');
  await page.waitForSelector('#ecEventsTable', { timeout: 120000 });  // Increased to 2 minutes
  return { browser, page };
}

// 🔥 Helper function to refresh the current week page with retry logic
async function refreshCurrentWeekPage(page, retries = 3) {
  for (let attempt = 1; attempt <= retries; attempt++) {
    try {
      await page.reload({ 
        waitUntil: 'domcontentloaded', 
        timeout: 180000  // Increased to 3 minutes
      });
      await page.waitForSelector('#ecEventsTable', { timeout: 120000 });  // Increased to 2 minutes
      return true;
    } catch (error) {
      if (attempt === retries) {
        throw new Error(`Failed to refresh page after ${retries} attempts`);
      }
      await sleep(2000 * attempt);
    }
  }
  return false;
}

// 🔥 FIXED CURRENT WEEK EVENT EXTRACTION WITH PROPER DATE TRACKING
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
      // 🔥 PROPERLY DETECT DATE ROWS
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
        currentDateStr, // This will be empty for events under a date, but we track it properly now
        eventName,
        currency,
        actualValue,
        forecastValue,
        previousValue,
        impactLevel,
        eventTimestamp: null,
        lastKnownDate: currentDateStr // Pass the last known date
      });
    });
    return events;
  });
}

// Next week scraper functions
async function createNextWeekBrowser(retries = 3) {
  for (let attempt = 1; attempt <= retries; attempt++) {
    try {
      const browser = await puppeteer.launch({ 
        headless: true,
        args: [
          '--no-sandbox', 
          '--disable-setuid-sandbox',
          '--disable-dev-shm-usage',
          '--disable-accelerated-2d-canvas',
          '--disable-gpu'
        ]
      });
      const page = await browser.newPage();

      await page.setUserAgent(
        'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) ' +
        'AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.0 Mobile/15E148 Safari/604.1'
      );
      await page.setViewport({ width: 375, height: 812, isMobile: true });

      // Block images on mobile to prevent slow loading
      await page.setRequestInterception(true);
      page.on('request', req => {
        if (['image', 'stylesheet', 'font', 'media'].includes(req.resourceType())) {
          req.abort();
        } else {
          req.continue();
        }
      });

      await page.goto('https://m.investing.com/economic-calendar/', {
        waitUntil: 'domcontentloaded',
        timeout: 180000
      });

      await page.waitForSelector('section#ec_wrapper', { timeout: 90000 });
      const sel = 'ul#economic-calendar_links a[data-name="nextWeek"]';
      await page.waitForSelector(sel, { timeout: 90000 });
      await page.click(sel);

      await sleep(8000);
      await page.waitForSelector('section#ec_wrapper article.js-link-item', { timeout: 90000 });
      
      return { browser, page };
    } catch (err) {
      console.error(`Next week browser attempt ${attempt}/${retries} failed: ${err.message}`);
      if (attempt === retries) {
        throw err;
      }
      await sleep(5000 * attempt);
    }
  }
}

async function extractNextWeekEvents(page) {
  const events = await page.evaluate(() => {
    const out = [];
    let currentDate = '';
    const wrapper = document.querySelector('#ec_wrapper');
    if (!wrapper) return out;

    for (const child of wrapper.children) {
      if (child.classList?.contains('theDay')) {
        currentDate = child.innerText.trim();
      }
      if (child.tagName === 'ARTICLE' && child.classList.contains('js-link-item')) {
        const darkCount = child.querySelectorAll('.smallDarkBull, .darkBull').length;
        if (darkCount < 2) continue;

        const timeEl = child.querySelector('.time p');
        const currEl = child.querySelector('.curr');
        const nameEl = child.querySelector('.rightSide p');
        const actualEl = child.querySelector('.bold.act');
        const prevEl = child.querySelector('.prev');

        out.push({
          timeText: timeEl?.innerText.trim() || '',
          currentDateStr: currentDate,
          eventName: nameEl?.innerText.trim() || '',
          currency: currEl?.innerText.trim().split(' ')[0] || '',
          actualValue: actualEl?.innerText.trim() || '',
          forecastValue: '',
          previousValue: prevEl?.innerText.trim() || '',
          impactLevel: darkCount >= 3 ? 'High' : 'Moderate',
          eventTimestamp: null
        });
      }
    }
    return out;
  });
  return events;
}

// History scraper functions
async function createHistoryBrowser(htmlFilePath, retries = 3) {
  for (let attempt = 1; attempt <= retries; attempt++) {
    try {
      const browser = await puppeteer.launch({ 
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage']
      });
      const page = await browser.newPage();
      await page.setViewport({ width: 1280, height: 800 });
      await page.setRequestInterception(true);
      
      page.on('request', req => {
        if (['image', 'stylesheet', 'font'].includes(req.resourceType())) {
          req.abort();
        } else {
          req.continue({
            headers: {
              ...req.headers(),
              'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
              'Accept-Language': 'en-US,en;q=0.9',
              'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
              'Connection': 'keep-alive',
              'Referer': 'https://www.google.com/'
            }
          });
        }
      });

      console.log(`📖 Loading history file: ${path.basename(htmlFilePath)} (attempt ${attempt}/${retries})...`);
      const fileUrl = `file:///${htmlFilePath.replace(/\\/g, '/')}`;
      await page.goto(fileUrl, { 
        waitUntil: 'domcontentloaded',
        timeout: 120000 
      });
      await page.waitForSelector('#economicCalendarData', { timeout: 90000 });
      console.log(`✓ History file loaded successfully: ${path.basename(htmlFilePath)}`);
      return { browser, page };
    } catch (err) {
      console.error(`History browser attempt ${attempt}/${retries} failed for ${path.basename(htmlFilePath)}: ${err.message}`);
      if (attempt === retries) {
        throw err;
      }
      await sleep(3000 * attempt);
    }
  }
}

async function extractHistoryEvents(page) {
  return await page.evaluate(() => {
    let currentDateStr = "";
    const events = [];
    const rows = document.querySelectorAll("#economicCalendarData tr");

    function mapSentimentTitleToImpact(title) {
      const t = (title || "").trim().toLowerCase();
      if (t.includes("high")) return "High";
      if (t.includes("medium") || t.includes("moderate")) return "Moderate";
      return "Low";
    }

    rows.forEach(row => {
      const dayCell = row.querySelector('td.theDay');
      if (dayCell) {
        currentDateStr = dayCell.innerText.trim();
        return;
      }

      if (row.id && row.id.startsWith('eventRowId')) {
        const sentimentCell = row.querySelector('.sentiment');
        if (!sentimentCell) return;

        const sentimentTitle = (sentimentCell.getAttribute('title') || "").trim();
        const impactLevel = mapSentimentTitleToImpact(sentimentTitle);

        if (!(impactLevel === "High" || impactLevel === "Moderate")) return;

        const timeCell = row.querySelector('.time');
        const timeText = timeCell ? timeCell.innerText.trim() : "";

        const currencyCell = row.querySelector('.flagCur');
        let currency = "";
        if (currencyCell) {
          currency = currencyCell.innerText.trim().split(' ')[0];
        }

        const eventCell = row.querySelector('.event');
        const eventName = eventCell ? eventCell.innerText.trim() : "";

        const actEl = row.querySelector('.act');
        const foreEl = row.querySelector('.fore');
        const prevEl = row.querySelector('.prev');

        const actualValue = actEl ? actEl.innerText.trim() : "";
        const forecastValue = foreEl ? foreEl.innerText.trim() : "";
        const previousValue = prevEl ? prevEl.innerText.trim() : "";

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
      }
    });

    return events;
  });
}

// Main scraper functions
async function runCurrentWeekScraper() {
  if (isHighFrequencyMode) {
    console.log("⚠️  Current week scraper postponed - High frequency mode is active");
    return;
  }
  
  await acquireBrowserLock('CURRENT_WEEK');
  let browser = null;
  
  try {
    console.log("=== Current Week Scraper Started ===");
    
    const browserData = await createCurrentWeekBrowser();
    browser = browserData.browser;
    const page = browserData.page;
    activeBrowserInstance = browser;
    
    const events = await extractCurrentWeekEvents(page);
    let lastKnownDate = null;
    
    for (const event of events) {
      // Update lastKnownDate when we encounter a new date
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
        } catch (err) {
          console.error(`❌ CRITICAL ERROR processing event "${event.eventName}": ${err.message}`);
          console.error(`   Event data: currentDateStr="${event.currentDateStr}", lastKnownDate="${lastKnownDate}"`);
          // Continue processing other events instead of crashing
          continue;
        }
      }
    }
    
    console.log("=== Current Week Scraper Completed ===");
    return events;
  } finally {
    await releaseBrowserLock(browser, 'CURRENT_WEEK');
  }
}

async function runNextWeekScraper() {
  if (isHighFrequencyMode) {
    console.log("⚠️  Next week scraper postponed - High frequency mode is active");
    return;
  }
  
  if (nextWeekScrapedToday) {
    return;
  }
  
  await acquireBrowserLock('NEXT_WEEK');
  let browser = null;
  
  try {
    console.log("=== Next Week Scraper Started ===");
    
    const browserData = await createNextWeekBrowser();
    browser = browserData.browser;
    const page = browserData.page;
    activeBrowserInstance = browser;
    
    const events = await extractNextWeekEvents(page);
    
    for (const ev of events) {
      if (allowedCurrencies.has(ev.currency) && requiredImpact.has(ev.impactLevel) && !ev.eventName.startsWith('CFTC')) {
        await saveEventToDB(ev, null);
      }
    }
    
    nextWeekScrapedToday = true;
    console.log("=== Next Week Scraper Completed ===");
  } catch (err) {
    console.error('❌ Next week scraper failed after retries:', err.message);
    console.log('⚠️  Continuing without next week data...');
  } finally {
    await releaseBrowserLock(browser, 'NEXT_WEEK');
  }
}

async function runHistoryScraper() {
  if (isHighFrequencyMode) {
    console.log("⚠️  History scraper postponed - High frequency mode is active");
    return;
  }
  
  await acquireBrowserLock('HISTORY');
  let browser = null;
  
  try {
    console.log("=== History Scraper Started ===");
    
    // Get all HTML files from the history folder
    const historyFolder = path.join(__dirname, 'history');
    
    // Check if history folder exists
    if (!fs.existsSync(historyFolder)) {
      console.error(`❌ History folder not found: ${historyFolder}`);
      console.log('⚠️  Please ensure the history folder exists with HTML files');
      return;
    }
    
    // Read all files in the history folder
    const allFiles = fs.readdirSync(historyFolder);
    
    // Filter only .html files
    const htmlFiles = allFiles.filter(file => file.toLowerCase().endsWith('.html'));
    
    if (htmlFiles.length === 0) {
      console.log('⚠️  No HTML files found in history folder');
      return;
    }
    
    console.log(`📂 Found ${htmlFiles.length} HTML file(s) in history folder`);
    htmlFiles.forEach((file, index) => {
      console.log(`   ${index + 1}. ${file}`);
    });
    
    let totalEventsProcessed = 0;
    
    // Process each HTML file one by one
    for (let i = 0; i < htmlFiles.length; i++) {
      const htmlFile = htmlFiles[i];
      const htmlFilePath = path.join(historyFolder, htmlFile);
      
      console.log(`\n📄 Processing file ${i + 1}/${htmlFiles.length}: ${htmlFile}`);
      
      try {
        const browserData = await createHistoryBrowser(htmlFilePath);
        browser = browserData.browser;
        const page = browserData.page;
        activeBrowserInstance = browser;
        
        const events = await extractHistoryEvents(page);
        console.log(`📖 Found ${events.length} events in ${htmlFile}`);
        
        let fileEventsProcessed = 0;
        
        for (const event of events) {
          if (
            allowedCurrencies.has(event.currency) &&
            !event.eventName.startsWith("CFTC") &&
            requiredImpact.has(event.impactLevel)
          ) {
            // All events now use fixed EST (UTC-5) conversion
            
            await saveEventToDB(event, null);
            fileEventsProcessed++;
          }
        }
        
        totalEventsProcessed += fileEventsProcessed;
        console.log(`✅ Processed ${fileEventsProcessed} valid events from ${htmlFile}`);
        
        // Close browser after processing each file
        await browser.close();
        browser = null;
        activeBrowserInstance = null;
        
        // Add a small delay between files to prevent resource exhaustion
        if (i < htmlFiles.length - 1) {
          console.log('⏳ Waiting 2 seconds before next file...');
          await sleep(2000);
        }
        
      } catch (err) {
        console.error(`❌ Error processing ${htmlFile}: ${err.message}`);
        console.log(`⚠️  Skipping ${htmlFile} and continuing with next file...`);
        
        // Try to close browser if it's still open
        if (browser) {
          try {
            await browser.close();
          } catch (e) {
            // Ignore close errors
          }
          browser = null;
          activeBrowserInstance = null;
        }
        
        continue; // Continue with next file
      }
    }
    
    console.log(`\n=== History Scraper Completed ===`);
    console.log(`📊 Total events processed: ${totalEventsProcessed} from ${htmlFiles.length} file(s)`);
    
  } catch (err) {
    console.error('❌ History scraper failed:', err.message);
    console.log('⚠️  Check if history folder exists and contains valid HTML files');
  } finally {
    await releaseBrowserLock(browser, 'HISTORY');
  }
}

async function runRecentEventsScraper() {
  if (isHighFrequencyMode) {
    console.log("⚠️  Recent events scraper postponed - High frequency mode is active");
    return;
  }
  
  await acquireBrowserLock('RECENT_EVENTS');
  let browser = null;
  
  try {
    console.log("=== Recent Events Scraper Started ===");
    
    const browserData = await createCurrentWeekBrowser();
    browser = browserData.browser;
    const page = browserData.page;
    activeBrowserInstance = browser;
    
    const events = await extractCurrentWeekEvents(page);
    let lastKnownDate = null;
    
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
          await saveEventToDB(event, lastKnownDate);
        } catch (err) {
          console.error(`❌ ERROR processing recent event "${event.eventName}": ${err.message}`);
          continue;
        }
      }
    }
    
    console.log("=== Recent Events Scraper Completed ===");
  } finally {
    await releaseBrowserLock(browser, 'RECENT_EVENTS');
  }
}

async function runNextWeekOnlyScraper() {
  if (isHighFrequencyMode) {
    console.log("⚠️  Next week only scraper postponed - High frequency mode is active");
    return;
  }
  
  await acquireBrowserLock('NEXT_WEEK_ONLY');
  let browser = null;
  
  try {
    console.log("=== Next Week Only Scraper Started ===");
    
    const browserData = await createNextWeekBrowser();
    browser = browserData.browser;
    const page = browserData.page;
    activeBrowserInstance = browser;
    
    const events = await extractNextWeekEvents(page);
    
    for (const ev of events) {
      if (allowedCurrencies.has(ev.currency) && requiredImpact.has(ev.impactLevel) && !ev.eventName.startsWith('CFTC')) {
        await saveEventToDB(ev, null);
      }
    }
    
    console.log("=== Next Week Only Scraper Completed ===");
  } catch (err) {
    console.error('❌ Next week only scraper failed after retries:', err.message);
    console.log('⚠️  Check your internet connection or try again later.');
  } finally {
    await releaseBrowserLock(browser, 'NEXT_WEEK_ONLY');
  }
}

async function runThisWeekOnlyScraper() {
  if (isHighFrequencyMode) {
    console.log("⚠️  This week only scraper postponed - High frequency mode is active");
    return;
  }
  
  await acquireBrowserLock('THIS_WEEK_ONLY');
  let browser = null;
  
  try {
    console.log("=== Running This Week Only Scraper ===");
    
    const browserData = await createCurrentWeekBrowser();
    browser = browserData.browser;
    const page = browserData.page;
    activeBrowserInstance = browser;
    console.log("This week only scraping started...");
    
    const events = await extractCurrentWeekEvents(page);
    let lastKnownDate = null;
    
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
        } catch (err) {
          console.error(`❌ ERROR processing this week event "${event.eventName}": ${err.message}`);
          continue;
        }
      }
    }
    
    console.log("This week only scraping completed.");
  } finally {
    await releaseBrowserLock(browser, 'THIS_WEEK_ONLY');
  }
}

// Sequential scrapers function with delays for resource management
async function runSequentialScrapers() {
  console.log("=== Running Current Week + Next Week Scrapers Sequentially ===");
  
  console.log("🔄 Step 1: Running Current Week Scraper...");
  await runCurrentWeekScraper();
  
  console.log("⏳ Waiting 3 seconds before next scraper...");
  await sleep(3000);
  
  console.log("🔄 Step 2: Running Next Week Scraper...");
  await runNextWeekScraper();
  
  console.log("=== Both scrapers completed sequentially ===");
}

// Monitoring functions
async function monitorNextEvent() {
  const db = await getDB();
  try {
    const now = moment.utc();
    const rows = await dbAll(db,
      "SELECT *, event_date || ' ' || event_time as eventDateTime FROM economic_events"
    );

    const upcoming = rows.filter(row =>
      moment.utc(row.eventDateTime, 'YYYY-MM-DD HH:mm:ss').isAfter(now) &&
      requiredImpact.has(row.impact_level) &&
      allowedCurrencies.has(row.currency) &&
      !row.event_name.startsWith("CFTC")
    );

    if (upcoming.length === 0) {
      if (!noUpcomingEventLogged) {
        console.log("No more upcoming events waiting to discover");
        console.log("Waiting 12 hours before next scrape…");
        noUpcomingEventLogged = true;
      }
      setTimeout(async () => {
        noUpcomingEventLogged = false;
        console.log("Re-running current week scrape…");
        await runCurrentWeekScraper();
        monitorNextEvent();
      }, 12 * 60 * 60 * 1000);
      return;
    } else {
      noUpcomingEventLogged = false;
    }

    upcoming.sort((a, b) =>
      moment.utc(a.eventDateTime).diff(moment.utc(b.eventDateTime))
    );
    const earliestTime = upcoming[0].eventDateTime;
    const earliestMoment = moment.utc(earliestTime);
    const eventsAtSameTime = upcoming.filter(ev => ev.eventDateTime === earliestTime);

    console.log(`Next event${eventsAtSameTime.length > 1 ? 's' : ''} at ${earliestMoment.toISOString()}:`);
    eventsAtSameTime.forEach(ev => {
      console.log(`  - "${ev.event_name}" (Currency: ${ev.currency})`);
    });

    const startScrapingTime = earliestMoment.clone().subtract(30, 'seconds');
    const endScrapingTime = earliestMoment.clone().add(2, 'minutes');
    const delayToStart = startScrapingTime.diff(now);

    if (delayToStart > 0) {
      console.log(`High frequency scraping will start in ${Math.round(delayToStart/1000)} seconds.`);
      setTimeout(() => {
        highFrequencyScrapingWindow(endScrapingTime);
      }, delayToStart);
    } else if (moment.utc().isBefore(endScrapingTime)) {
      console.log("Starting high frequency scraping immediately.");
      highFrequencyScrapingWindow(endScrapingTime);
    } else {
      console.log("Next event is already past. Retrying in 5 seconds.");
      await sleep(5000);
      return monitorNextEvent();
    }
  } catch (error) {
    console.error("Error in monitorNextEvent:", error.message);
  } finally {
    await dbClose(db);
  }
}

async function highFrequencyScrapingWindow(endTime) {
  // HIGH FREQUENCY MODE HAS HIGHEST PRIORITY - WAIT FOR ANY ACTIVE BROWSER
  await waitForBrowserAvailability();
  
  isHighFrequencyMode = true;
  isBrowserActive = true;
  let browser = null;
  let page = null;
  let consecutiveErrors = 0;
  const MAX_CONSECUTIVE_ERRORS = 5;
  let scrapeCycleCount = 0;
  let eventsProcessedCount = 0;
  
  try {
    console.log("🚀 High-Frequency Mode - Acquiring browser lock...");
    const browserData = await createCurrentWeekBrowser();
    browser = browserData.browser;
    page = browserData.page;
    activeBrowserInstance = browser;
    console.log("🚀 High-Frequency Mode Started - ALL OTHER SCRAPERS BLOCKED");
  } catch (error) {
    console.error("❌ Failed to initialize high-frequency mode:", error.message);
    isHighFrequencyMode = false;
    isBrowserActive = false;
    activeBrowserInstance = null;
    monitorNextEvent();
    return;
  }
  
  while (moment.utc().isBefore(endTime)) {
    try {
      await sleep(5000);
      scrapeCycleCount++;
      
      // Refresh the page to get latest data
      try {
        await refreshCurrentWeekPage(page);
      } catch (refreshError) {
        consecutiveErrors++;
        
        if (consecutiveErrors >= MAX_CONSECUTIVE_ERRORS) {
          console.log("🔄 Recreating browser due to errors...");
          try {
            await browser.close();
          } catch (e) { /* ignore */ }
          
          await cleanupMemory();
          await sleep(3000);
          
          const browserData = await createCurrentWeekBrowser();
          browser = browserData.browser;
          page = browserData.page;
          activeBrowserInstance = browser;
          consecutiveErrors = 0;
          console.log("✓ Browser recreated successfully");
        }
        continue;
      }
      
      const events = await extractCurrentWeekEvents(page);
      let lastKnownDate = null;
      let cycleEventCount = 0;
      
      for (const event of events) {
        // Update lastKnownDate when we encounter a new date
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
            cycleEventCount++;
          } catch (err) {
            continue;
          }
        }
      }
      
      eventsProcessedCount += cycleEventCount;
      
      // Log status every 10 cycles (every 50 seconds)
      if (scrapeCycleCount % 10 === 0) {
        const timeRemaining = Math.round(moment.duration(endTime.diff(moment.utc())).asSeconds());
        console.log(`📊 Cycle ${scrapeCycleCount} | Events: ${eventsProcessedCount} | Remaining: ${timeRemaining}s`);
      }
      
      // Reset error counter on success
      consecutiveErrors = 0;
      
    } catch (error) {
      consecutiveErrors++;
      
      if (consecutiveErrors >= MAX_CONSECUTIVE_ERRORS) {
        break;
      }
    }
  }
  
  try {
    await browser.close();
    console.log("✓ High-frequency browser closed");
  } catch (e) {
    console.error("Error closing browser:", e.message);
  }
  
  activeBrowserInstance = null;
  isBrowserActive = false;
  isHighFrequencyMode = false;
  
  console.log(`🏁 High-Frequency Mode Ended - ${scrapeCycleCount} cycles, ${eventsProcessedCount} events`);
  console.log("🔓 Browser lock released - Other scrapers can now run");
  
  await cleanupMemory();
  await sleep(3000); // Give system time to recover
  
  monitorNextEvent();
}

// Function to setup cron jobs safely
function setupCronJobs() {
  try {
    process.env.TZ = 'UTC';
    
    cron.schedule('0 */12 * * *', async () => {
      try {
        if (isHighFrequencyMode) {
          console.log("⏳ Scheduled current week scrape postponed - High frequency mode active");
          return;
        }
        await runCurrentWeekScraper();
      } catch (error) {
        console.error("Error in scheduled current week scrape:", error.message);
      }
    }, {
      timezone: "UTC"
    });

    cron.schedule('0 0 * * *', async () => {
      try {
        if (isHighFrequencyMode) {
          console.log("⏳ Scheduled next week scrape postponed - High frequency mode active");
          return;
        }
        nextWeekScrapedToday = false;
        await runNextWeekScraper();
      } catch (error) {
        console.error("Error in scheduled next week scrape:", error.message);
      }
    }, {
      timezone: "UTC"
    });
    
    console.log("✓ Cron jobs scheduled");
  } catch (error) {
    console.error("Error setting up cron jobs:", error);
    console.log("Continuing without scheduled tasks...");
  }
}

// Main execution
(async () => {
  try {
    verifyAttribution();
    displayBanner();
    setupCronJobs();
    
    // Check for command-line arguments
    const args = process.argv.slice(2);
    let choice = args[0];
    
    // If no argument provided, ask user
    if (!choice) {
      choice = await askUserChoice();
    } else {
      console.log(`Running in non-interactive mode with option: ${choice}\n`);
    }
    
    console.log(""); // blank line for better readability
    
    switch(choice) {
      case '1':
        console.log("Starting: Current Week + Next Week + Monitoring...\n");
        await runSequentialScrapers();
        console.log("\n🎯 Starting event monitoring for high frequency scraping...");
        monitorNextEvent();
        break;
      case '2':
        console.log("Starting: History Scraper + Monitoring...\n");
        await runHistoryScraper();
        console.log("\n🎯 Starting event monitoring for high frequency scraping...");
        monitorNextEvent();
        break;
      case '3':
        console.log("Starting: All Scrapers + Monitoring...\n");
        await runSequentialScrapers();
        await runHistoryScraper();
        console.log("\n🎯 Starting event monitoring for high frequency scraping...");
        monitorNextEvent();
        break;
      case '4':
        console.log("Starting: Recent Events Scraper + Monitoring...\n");
        await runRecentEventsScraper();
        console.log("\n🎯 Starting event monitoring for high frequency scraping...");
        monitorNextEvent();
        break;
      case '5':
        console.log("Starting: Next Week Only + Monitoring...\n");
        await runNextWeekOnlyScraper();
        console.log("\n🎯 Starting event monitoring for high frequency scraping...");
        monitorNextEvent();
        break;
      case '6':
        console.log("Starting: This Week Only + Monitoring...\n");
        await runThisWeekOnlyScraper();
        console.log("\n🎯 Starting event monitoring for high frequency scraping...");
        monitorNextEvent();
        break;
      case '7':
        console.log("Starting: Current Week + Next Week (One-time run)...\n");
        await runSequentialScrapers();
        console.log("\n✅ Scraping completed successfully!");
        console.log("Exiting...");
        process.exit(0);
        break;
      default:
        console.log("Invalid choice. Running sequential scrapers by default.\n");
        await runSequentialScrapers();
        console.log("\n🎯 Starting event monitoring for high frequency scraping...");
        monitorNextEvent();
        break;
    }
  } catch (err) {
    console.error("Fatal error:", err);
    process.exit(1);
  }
})();