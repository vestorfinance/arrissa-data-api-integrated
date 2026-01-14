const fs = require('fs');

// Read the NFP data
const data = JSON.parse(fs.readFileSync('nfp_data.json', 'utf-8')).vestor_data;

const significantEventIds = {
  unemployment: "JCDYM",
  nonfarm: "VPRWG",
  earnings: "ZBEYU"
};

// Month psychology weights (from enhanced_weighted.js)
const monthWeights = {
  1: { unemployment: 0.25, nonfarm: 0.40, earnings: 0.35 },   // January
  2: { unemployment: 0.30, nonfarm: 0.35, earnings: 0.35 },   // February
  3: { unemployment: 0.35, nonfarm: 0.30, earnings: 0.35 },   // March
  4: { unemployment: 0.30, nonfarm: 0.40, earnings: 0.30 },   // April
  5: { unemployment: 0.35, nonfarm: 0.35, earnings: 0.30 },   // May
  6: { unemployment: 0.25, nonfarm: 0.40, earnings: 0.35 },   // June
  7: { unemployment: 0.30, nonfarm: 0.40, earnings: 0.30 },   // July
  8: { unemployment: 0.35, nonfarm: 0.35, earnings: 0.30 },   // August
  9: { unemployment: 0.35, nonfarm: 0.35, earnings: 0.30 },   // September
  10: { unemployment: 0.35, nonfarm: 0.35, earnings: 0.30 },  // October
  11: { unemployment: 0.30, nonfarm: 0.40, earnings: 0.30 },  // November
  12: { unemployment: 0.35, nonfarm: 0.30, earnings: 0.35 }   // December
};

function isInverseEvent(eventName) {
  return eventName && eventName.toLowerCase().includes('unemployment');
}

function getActualBasedPrediction(actual, forecast, eventName) {
  const isInverse = isInverseEvent(eventName);
  
  if (actual === forecast) return "NEUTRAL";
  
  if (actual > forecast) {
    return isInverse ? "UP" : "DOWN";
  } else {
    return isInverse ? "DOWN" : "UP";
  }
}

function calculateMagnitudeAdjustedWeight(event, baseWeight) {
  const magnitude = Math.abs(event.actual_value - event.forecast_value);
  const sensitivity = event.consistent_event_id === significantEventIds.unemployment ? 10 :
                     event.consistent_event_id === significantEventIds.nonfarm ? 20 : 15;
  
  const adjustment = magnitude / sensitivity;
  return baseWeight * (1 + adjustment);
}

function getEnhancedWeightedPrediction(occ, unemploymentEvent, nfpEvent, earningsEvent) {
  const month = parseInt(occ.occurrence_date.substring(5, 7), 10);
  const weights = monthWeights[month];
  
  let adjustedWeights = {
    unemployment: calculateMagnitudeAdjustedWeight(unemploymentEvent, weights.unemployment),
    nonfarm: calculateMagnitudeAdjustedWeight(nfpEvent, weights.nonfarm),
    earnings: calculateMagnitudeAdjustedWeight(earningsEvent, weights.earnings)
  };
  
  // Conflict detection and adjustment
  const unemploymentPred = getActualBasedPrediction(unemploymentEvent.actual_value, unemploymentEvent.forecast_value, unemploymentEvent.event_name);
  const nfpPred = getActualBasedPrediction(nfpEvent.actual_value, nfpEvent.forecast_value, nfpEvent.event_name);
  const earningsPred = getActualBasedPrediction(earningsEvent.actual_value, earningsEvent.forecast_value, earningsEvent.event_name);
  
  if (unemploymentPred !== "NEUTRAL" && nfpPred !== "NEUTRAL" && earningsPred !== "NEUTRAL") {
    if (nfpPred === earningsPred && unemploymentPred !== nfpPred) {
      adjustedWeights.unemployment *= 0.2; // 80% reduction
    }
  }
  
  const totalWeight = adjustedWeights.unemployment + adjustedWeights.nonfarm + adjustedWeights.earnings;
  
  let upScore = 0;
  let downScore = 0;
  
  if (unemploymentPred === "UP") upScore += adjustedWeights.unemployment;
  else if (unemploymentPred === "DOWN") downScore += adjustedWeights.unemployment;
  
  if (nfpPred === "UP") upScore += adjustedWeights.nonfarm;
  else if (nfpPred === "DOWN") downScore += adjustedWeights.nonfarm;
  
  if (earningsPred === "UP") upScore += adjustedWeights.earnings;
  else if (earningsPred === "DOWN") downScore += adjustedWeights.earnings;
  
  if (upScore > downScore) return "UP";
  if (downScore > upScore) return "DOWN";
  return "NEUTRAL";
}

function getDirectionAtMinute(occData, minutes) {
  if (minutes <= 5 && occData.xauusd_data && occData.xauusd_data.length >= minutes) {
    const candle = occData.xauusd_data[minutes - 1];
    const open = candle.open;
    const close = candle.close;
    
    if (close > open) return { direction: "UP", pips: close - open };
    if (close < open) return { direction: "DOWN", pips: open - close };
    return { direction: "NEUTRAL", pips: 0 };
  }
  
  return null;
}

const signals = [];

Object.values(data).forEach(occ => {
  const unemploymentEvent = occ.events.find(e => e.consistent_event_id === significantEventIds.unemployment);
  const nfpEvent = occ.events.find(e => e.consistent_event_id === significantEventIds.nonfarm);
  const earningsEvent = occ.events.find(e => e.consistent_event_id === significantEventIds.earnings);
  
  if (!unemploymentEvent || !nfpEvent || !earningsEvent) return;
  if (!occ.xauusd_data || occ.xauusd_data.length < 5) return;
  
  // Get enhanced weighted prediction
  const prediction = getEnhancedWeightedPrediction(occ, unemploymentEvent, nfpEvent, earningsEvent);
  
  if (prediction === "NEUTRAL") return;
  
  // Get actual market direction at 4 minutes
  const result4Min = getDirectionAtMinute(occ, 4);
  if (!result4Min) return;
  
  const won = prediction === result4Min.direction;
  
  signals.push({
    date: occ.occurrence_date,
    time: occ.occurrence_time,
    entryTime: occ.xauusd_data[0].time,
    exitTime: occ.xauusd_data[3].time,
    direction: prediction,
    entryPrice: occ.xauusd_data[0].close,
    exitPrice: occ.xauusd_data[3].close,
    result: won ? "WIN" : "LOSS",
    pips: result4Min.pips.toFixed(3),
    actualDirection: result4Min.direction
  });
});

console.log(`\nOptimized Signals Generated: ${signals.length}`);
console.log(`Wins: ${signals.filter(s => s.result === "WIN").length}`);
console.log(`Losses: ${signals.filter(s => s.result === "LOSS").length}`);
console.log(`Win Rate: ${(signals.filter(s => s.result === "WIN").length / signals.length * 100).toFixed(2)}%\n`);

// Save to JSON
fs.writeFileSync('optimized_signals.json', JSON.stringify(signals, null, 2));

// Generate MQL5 signal data
console.log("\n=== OPTIMIZED SIGNALS FOR MQL5 EA ===\n");
console.log(`Total Signals: ${signals.length}\n`);

signals.forEach((s, idx) => {
  console.log(`   // Signal ${idx + 1}: ${s.date} - ${s.direction} - ${s.result}`);
  console.log(`   signalTimes[${idx}] = StringToTime("${s.date} ${s.time}");`);
  console.log(`   signalDirections[${idx}] = "${s.direction}";`);
  console.log(`   entryPrices[${idx}] = ${s.entryPrice};`);
  console.log(`   exitPrices[${idx}] = ${s.exitPrice};`);
  console.log('');
});

console.log("\n✓ Optimized signals saved to optimized_signals.json");
console.log("✓ Ready for MQL5 EA implementation");
