# NFP Debug Instructions

## Problem
Some NFP (Nonfarm Payrolls) events are showing incorrect UTC times (11:30:00 instead of 12:30:00 or 13:30:00).

## Root Cause Investigation

NFP is always released at **8:30 AM US Eastern Time**:
- **During EST** (Nov-Mar): 8:30 AM EST = **13:30 UTC** (UTC-5)
- **During EDT** (Mar-Nov): 8:30 AM EDT = **12:30 UTC** (UTC-4)

Times showing **11:30 UTC** are incorrect.

## Solution: Debug and Fix

### Step 1: Clear Database
```bash
cd c:\wamp64\www\database
php clear-all-events.php
```

Type `yes` to confirm deletion.

### Step 2: Run Scraper with NFP Debug Logs
```bash
cd c:\wamp64\www\events-scrapper-nodejs
node events.js
```

The scraper will now:
- **Silent mode** for all regular events (no spam)
- **Detailed debug logs ONLY for NFP** events showing:
  - Raw scraped data (date, time from website)
  - Timezone conversion steps
  - DST detection (EDT vs EST)
  - Expected vs actual UTC time
  - Final saved values

### Step 3: Look for Debug Output

Watch for these NFP-specific logs:
```
🔍 NFP DEBUG - STEP 1: Using eventTimestamp
   eventTimestamp: "2024-04-05 08:30:00"
   Parsed dateStr: 2024-04-05, timeStr: 08:30:00

🔍 NFP DEBUG - STEP 3: Before UTC conversion
   Input dateStr: 2024-04-05, timeStr: 08:30:00
   Assuming timezone: America/New_York (EST/EDT)

🔍 DETAILED TIMEZONE DEBUG:
   Input: 2024-04-05 08:30:00 (America/New_York)
   NY DateTime: 2024-04-05 08:30:00 -0400
   Is DST: YES (EDT, UTC-4)
   UTC Offset: -4 hours
   UTC DateTime: 2024-04-05 12:30:00 +0000
   Result: 2024-04-05 12:30:00
   Expected: 12:30:00 should be 12:30:00 UTC

🔍 NFP DEBUG - STEP 4: After UTC conversion
   Output date: 2024-04-05, time: 12:30:00
   ⚠️  Expected: Time should be 12:30:00 or 13:30:00 UTC

💾 SAVING NFP: "Nonfarm Payrolls (Mar)" with date=2024-04-05, time=12:30:00
```

### Step 4: Verify Correct Times

After scraping completes, check if NFP times are correct:
```bash
cd c:\wamp64\www\database
php -f check-nfp-times.php
```

Or query directly:
```bash
SELECT event_name, event_date, event_time 
FROM economic_events 
WHERE event_name LIKE '%Nonfarm%' 
ORDER BY event_date DESC 
LIMIT 12;
```

### Expected Results

All NFP events should show either:
- **12:30:00 UTC** (during DST period: March-November)
- **13:30:00 UTC** (during standard time: November-March)

**NEVER 11:30:00 UTC** - this is incorrect!

## If Times Are Still Wrong

1. Check the debug output to see where the conversion fails
2. Verify `moment-timezone` is installed: `npm install moment-timezone`
3. Check if the website scraped data is in EST/EDT or already UTC
4. Look at the `eventTimestamp` field from the scraper

## Files Modified

- `events-scrapper-nodejs/events.js` - Added NFP-specific debug logs
- `database/clear-all-events.php` - Script to clear database
- Debug output disabled for all non-NFP events to reduce noise
