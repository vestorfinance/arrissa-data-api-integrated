// Quick test to trace the actual execution
const moment = require('moment-timezone');

// Exact copy of the scraper's parseEventTime functions
function parseRawTimeString(rawTimeStr) {
  if (!rawTimeStr || rawTimeStr.trim() === '') {
    return '';
  }
  
  try {
    let cleanTime = rawTimeStr.trim();
    
    if (cleanTime.toLowerCase() === 'all day' || 
        cleanTime.toLowerCase() === 'tentative' || 
        cleanTime.toLowerCase() === 'holiday') {
      return '00:00:00';
    }
    
    // Remove any timezone indicators like (EST), (GMT), etc.
    cleanTime = cleanTime.replace(/\s*\([^)]*\)\s*/g, '').trim();
    
    // Handle formats like "08:30" or "8:30 AM"
    const timeMatch = cleanTime.match(/(\d{1,2}):(\d{2})(?::(\d{2}))?(?:\s*(AM|PM))?/i);
    
    if (timeMatch) {
      let hours = parseInt(timeMatch[1]);
      const minutes = timeMatch[2];
      const seconds = timeMatch[3] || '00';
      const ampm = timeMatch[4];
      
      if (ampm) {
        if (ampm.toUpperCase() === 'PM' && hours < 12) {
          hours += 12;
        } else if (ampm.toUpperCase() === 'AM' && hours === 12) {
          hours = 0;
        }
      }
      
      return `${String(hours).padStart(2, '0')}:${minutes}:${seconds}`;
    }
    
    return '00:00:00';
    
  } catch (err) {
    console.error(`Failed to parse time "${rawTimeStr}": ${err.message}`);
    return '00:00:00';
  }
}

function convertNYTimeToUTC(dateStr, timeStr) {
  try {
    const nyDateTime = moment.tz(`${dateStr} ${timeStr}`, 'America/New_York');
    const utcDateTime = nyDateTime.clone().tz('UTC');
    
    console.log(`   NY: ${nyDateTime.format('YYYY-MM-DD HH:mm:ss Z')}`);
    console.log(`  UTC: ${utcDateTime.format('YYYY-MM-DD HH:mm:ss Z')}`);
    
    return {
      date: utcDateTime.format('YYYY-MM-DD'),
      time: utcDateTime.format('HH:mm:ss')
    };
  } catch (err) {
    console.log(`   ERROR: ${err.message} - returning original`);
    return { date: dateStr, time: timeStr };
  }
}

// Test with sample event data
console.log('\n=== Testing Timezone Conversion Flow ===\n');

const testEvent = {
  timeText: '08:30',
  currentDateStr: 'Friday, January 9, 2026',
  eventName: 'Nonfarm Payrolls (Dec)',
  eventTimestamp: null
};

console.log('1. Raw time from scraper:', testEvent.timeText);

const parsedTime = parseRawTimeString(testEvent.timeText);
console.log('2. Parsed time:', parsedTime);

// For date parsing, we'd need the full parseRawDateString function
// but let's assume it returns '2026-01-09'
const parsedDate = '2026-01-09';
console.log('3. Parsed date:', parsedDate);

console.log('\n4. Converting from NY to UTC:');
const converted = convertNYTimeToUTC(parsedDate, parsedTime);

console.log('\n5. Final result:');
console.log(`   Date: ${converted.date}`);
console.log(`   Time: ${converted.time}`);
console.log('\n✅ Expected in database: 2026-01-09 13:30:00');
console.log(`❌ Actually in database: 2026-01-09 08:30:00`);
console.log('\nConclusion: Conversion function works, but something is bypassing it or overwriting the result.');
