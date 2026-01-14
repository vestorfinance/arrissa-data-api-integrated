const fs = require("fs");
const path = require("path");

const filePath = path.join(__dirname, "nfp_data.json");
const rawData = fs.readFileSync(filePath, "utf-8");
const data = JSON.parse(rawData).vestor_data;

const significantEventIds = {
  unemployment: "JCDYM",
  nonfarm: "VPRWG",
  earnings: "ZBEYU"
};

function isInverseEvent(eventName) {
  return eventName && eventName.toLowerCase().includes("unemployment");
}

// Simple: Actual vs Forecast predicts continuation
function getActualBasedPrediction(actual, forecast, eventName) {
  if (actual === null || forecast === null) return "NEUTRAL";
  const a = parseFloat(actual);
  const f = parseFloat(forecast);
  const isInverse = isInverseEvent(eventName);
  
  if (a > f) {
    // Actual beat forecast - positive surprise
    return isInverse ? "UP" : "DOWN";
  } else if (a < f) {
    // Actual missed forecast - negative surprise
    return isInverse ? "DOWN" : "UP";
  }
  return "NEUTRAL";
}

function getDirectionAtMinute(occData, minutes) {
  if (minutes <= 5) {
    const xauusdData = occData.xauusd_data;
    if (!xauusdData || xauusdData.length < minutes) return null;
    
    const candles = xauusdData.slice(0, minutes);
    const open = candles[0].open;
    
    let highestHigh = candles[0].high;
    let lowestLow = candles[0].low;
    
    candles.forEach(candle => {
      if (candle.high > highestHigh) highestHigh = candle.high;
      if (candle.low < lowestLow) lowestLow = candle.low;
    });
    
    const upMove = highestHigh - open;
    const downMove = open - lowestLow;
    
    return {
      direction: upMove >= downMove ? "UP" : "DOWN",
      upMove,
      downMove,
      range: highestHigh - lowestLow
    };
  }
  
  if (minutes === 30) {
    const candle = occData.candle_30min;
    if (!candle) return null;
    
    const upMove = candle.high - candle.open;
    const downMove = candle.open - candle.low;
    
    return {
      direction: upMove >= downMove ? "UP" : "DOWN",
      upMove,
      downMove,
      range: candle.high - candle.low
    };
  }
  
  return null;
}

// Test each event individually
const results = {
  unemployment: {
    exit3Min: { wins: 0, losses: 0, neutral: 0 },
    exit4Min: { wins: 0, losses: 0, neutral: 0 },
    exit5Min: { wins: 0, losses: 0, neutral: 0 }
  },
  nonfarm: {
    exit3Min: { wins: 0, losses: 0, neutral: 0 },
    exit4Min: { wins: 0, losses: 0, neutral: 0 },
    exit5Min: { wins: 0, losses: 0, neutral: 0 }
  },
  earnings: {
    exit3Min: { wins: 0, losses: 0, neutral: 0 },
    exit4Min: { wins: 0, losses: 0, neutral: 0 },
    exit5Min: { wins: 0, losses: 0, neutral: 0 }
  }
};

// Monthly tracking
const monthlyResults = {
  unemployment: {},
  nonfarm: {},
  earnings: {}
};

const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

let processedCount = 0;

Object.values(data).forEach(occ => {
  const unemploymentEvent = occ.events.find(e => e.consistent_event_id === significantEventIds.unemployment);
  const nfpEvent = occ.events.find(e => e.consistent_event_id === significantEventIds.nonfarm);
  const earningsEvent = occ.events.find(e => e.consistent_event_id === significantEventIds.earnings);
  
  if (!unemploymentEvent || !nfpEvent || !earningsEvent) return;
  if (!occ.xauusd_data || occ.xauusd_data.length < 5) return;
  
  // Get month from occurrence_date (format: YYYY-MM-DD)
  const monthNum = parseInt(occ.occurrence_date.substring(5, 7), 10);
  const monthName = monthNames[monthNum - 1];
  
  // Initialize monthly tracking if needed
  if (!monthlyResults.unemployment[monthName]) {
    monthlyResults.unemployment[monthName] = { wins: 0, losses: 0, neutral: 0 };
  }
  if (!monthlyResults.nonfarm[monthName]) {
    monthlyResults.nonfarm[monthName] = { wins: 0, losses: 0, neutral: 0 };
  }
  if (!monthlyResults.earnings[monthName]) {
    monthlyResults.earnings[monthName] = { wins: 0, losses: 0, neutral: 0 };
  }
  
  // Get predictions based on ACTUAL values
  const unemploymentPred = getActualBasedPrediction(
    unemploymentEvent.actual_value,
    unemploymentEvent.forecast_value,
    unemploymentEvent.event_name
  );
  
  const nfpPred = getActualBasedPrediction(
    nfpEvent.actual_value,
    nfpEvent.forecast_value,
    nfpEvent.event_name
  );
  
  const earningsPred = getActualBasedPrediction(
    earningsEvent.actual_value,
    earningsEvent.forecast_value,
    earningsEvent.event_name
  );
  
  // Get actual market direction at different timeframes
  const result3Min = getDirectionAtMinute(occ, 3);
  const result4Min = getDirectionAtMinute(occ, 4);
  const result5Min = getDirectionAtMinute(occ, 5);
  
  if (!result3Min || !result4Min || !result5Min) return;
  
  processedCount++;
  
  // Test Unemployment (using 4-min exit for monthly tracking)
  if (unemploymentPred === "NEUTRAL") {
    results.unemployment.exit3Min.neutral++;
    results.unemployment.exit4Min.neutral++;
    results.unemployment.exit5Min.neutral++;
    monthlyResults.unemployment[monthName].neutral++;
  } else {
    if (unemploymentPred === result3Min.direction) results.unemployment.exit3Min.wins++;
    else results.unemployment.exit3Min.losses++;
    
    if (unemploymentPred === result4Min.direction) {
      results.unemployment.exit4Min.wins++;
      monthlyResults.unemployment[monthName].wins++;
    } else {
      results.unemployment.exit4Min.losses++;
      monthlyResults.unemployment[monthName].losses++;
    }
    
    if (unemploymentPred === result5Min.direction) results.unemployment.exit5Min.wins++;
    else results.unemployment.exit5Min.losses++;
  }
  
  // Test NFP (using 4-min exit for monthly tracking)
  if (nfpPred === "NEUTRAL") {
    results.nonfarm.exit3Min.neutral++;
    results.nonfarm.exit4Min.neutral++;
    results.nonfarm.exit5Min.neutral++;
    monthlyResults.nonfarm[monthName].neutral++;
  } else {
    if (nfpPred === result3Min.direction) results.nonfarm.exit3Min.wins++;
    else results.nonfarm.exit3Min.losses++;
    
    if (nfpPred === result4Min.direction) {
      results.nonfarm.exit4Min.wins++;
      monthlyResults.nonfarm[monthName].wins++;
    } else {
      results.nonfarm.exit4Min.losses++;
      monthlyResults.nonfarm[monthName].losses++;
    }
    
    if (nfpPred === result5Min.direction) results.nonfarm.exit5Min.wins++;
    else results.nonfarm.exit5Min.losses++;
  }
  
  // Test Earnings (using 4-min exit for monthly tracking)
  if (earningsPred === "NEUTRAL") {
    results.earnings.exit3Min.neutral++;
    results.earnings.exit4Min.neutral++;
    results.earnings.exit5Min.neutral++;
    monthlyResults.earnings[monthName].neutral++;
  } else {
    if (earningsPred === result3Min.direction) results.earnings.exit3Min.wins++;
    else results.earnings.exit3Min.losses++;
    
    if (earningsPred === result4Min.direction) {
      results.earnings.exit4Min.wins++;
      monthlyResults.earnings[monthName].wins++;
    } else {
      results.earnings.exit4Min.losses++;
      monthlyResults.earnings[monthName].losses++;
    }
    
    if (earningsPred === result5Min.direction) results.earnings.exit5Min.wins++;
    else results.earnings.exit5Min.losses++;
  }
});

console.log('\n' + '='.repeat(80));
console.log('RAW ACCURACY TEST: EACH EVENT INDIVIDUALLY (Using Actuals)');
console.log('='.repeat(80));
console.log(`\nProcessed: ${processedCount} occurrences\n`);

function displayEventResults(eventName, eventResults) {
  console.log('='.repeat(80));
  console.log(`${eventName.toUpperCase()}`);
  console.log('='.repeat(80));
  
  ['exit3Min', 'exit4Min', 'exit5Min'].forEach(exit => {
    const r = eventResults[exit];
    const total = r.wins + r.losses;
    const winRate = total > 0 ? (r.wins / total * 100).toFixed(2) : 0;
    
    const exitLabel = exit === 'exit3Min' ? '3-Minute' : exit === 'exit4Min' ? '4-Minute' : '5-Minute';
    
    console.log(`\n${exitLabel} Exit:`);
    console.log(`  Wins: ${r.wins} | Losses: ${r.losses} | Neutral: ${r.neutral} | Total: ${total}`);
    console.log(`  Win Rate: ${winRate}%`);
    console.log(`  Net Result: ${r.wins > r.losses ? '+' : ''}${r.wins - r.losses} trades`);
  });
  console.log('');
}

displayEventResults('UNEMPLOYMENT RATE', results.unemployment);
displayEventResults('NONFARM PAYROLLS', results.nonfarm);
displayEventResults('AVERAGE HOURLY EARNINGS', results.earnings);

// Summary comparison
console.log('='.repeat(80));
console.log('COMPARISON: Which event predicts best at 4-minute exit?');
console.log('='.repeat(80));

const events = [
  { name: 'Unemployment Rate', results: results.unemployment.exit4Min },
  { name: 'Nonfarm Payrolls', results: results.nonfarm.exit4Min },
  { name: 'Avg Hourly Earnings', results: results.earnings.exit4Min }
];

events.forEach((e, idx) => {
  const total = e.results.wins + e.results.losses;
  const winRate = total > 0 ? (e.results.wins / total * 100).toFixed(2) : 0;
  console.log(`${idx + 1}. ${e.name}: ${winRate}% (${e.results.wins}W/${e.results.losses}L)`);
});

console.log('\n');

// Monthly breakdown
console.log('='.repeat(80));
console.log('MONTHLY BREAKDOWN (4-Minute Exit)');
console.log('='.repeat(80));

function displayMonthlyResults(eventName, monthData) {
  console.log(`\n${eventName.toUpperCase()}:`);
  console.log('-'.repeat(80));
  
  const sortedMonths = monthNames.filter(month => monthData[month]);
  
  if (sortedMonths.length === 0) {
    console.log('  No data available');
    return;
  }
  
  sortedMonths.forEach(month => {
    const m = monthData[month];
    const total = m.wins + m.losses;
    const winRate = total > 0 ? (m.wins / total * 100).toFixed(2) : 0;
    
    console.log(`  ${month}: ${winRate}% (${m.wins}W/${m.losses}L${m.neutral > 0 ? `, ${m.neutral}N` : ''})`);
  });
}

displayMonthlyResults('UNEMPLOYMENT RATE', monthlyResults.unemployment);
displayMonthlyResults('NONFARM PAYROLLS', monthlyResults.nonfarm);
displayMonthlyResults('AVERAGE HOURLY EARNINGS', monthlyResults.earnings);

console.log('\n');
