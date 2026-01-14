const fs = require('fs');

// Read the NFP data
const data = JSON.parse(fs.readFileSync('nfp_data.json', 'utf-8')).vestor_data;

const significantEventIds = {
  unemployment: "JCDYM",
  nonfarm: "VPRWG",
  earnings: "ZBEYU"
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
  const nfpEvent = occ.events.find(e => e.consistent_event_id === significantEventIds.nonfarm);
  
  if (!nfpEvent) return;
  if (!occ.xauusd_data || occ.xauusd_data.length < 5) return;
  
  const nfpPred = getActualBasedPrediction(
    nfpEvent.actual_value,
    nfpEvent.forecast_value,
    nfpEvent.event_name
  );
  
  if (nfpPred === "NEUTRAL") return;
  
  const result4Min = getDirectionAtMinute(occ, 4);
  if (!result4Min) return;
  
  const won = nfpPred === result4Min.direction;
  
  signals.push({
    date: occ.occurrence_date,
    time: occ.occurrence_time,
    entryTime: occ.xauusd_data[0].time, // First minute
    exitTime: occ.xauusd_data[3].time, // Fourth minute
    direction: nfpPred,
    entryPrice: occ.xauusd_data[0].close,
    exitPrice: occ.xauusd_data[3].close,
    result: won ? "WIN" : "LOSS",
    pips: result4Min.pips.toFixed(3),
    nfpActual: nfpEvent.actual_value,
    nfpForecast: nfpEvent.forecast_value
  });
});

console.log(`\nTotal Signals Generated: ${signals.length}`);
console.log(`Wins: ${signals.filter(s => s.result === "WIN").length}`);
console.log(`Losses: ${signals.filter(s => s.result === "LOSS").length}`);
console.log(`Win Rate: ${(signals.filter(s => s.result === "WIN").length / signals.length * 100).toFixed(2)}%\n`);

// Save to JSON for MQL5
fs.writeFileSync('nfp_signals.json', JSON.stringify(signals, null, 2));

// Generate MQL5 code
console.log("\n=== SIGNALS FOR MQL5 EA ===\n");
signals.forEach((s, idx) => {
  console.log(`// Signal ${idx + 1}: ${s.date} - ${s.direction} - ${s.result}`);
  console.log(`{datetime("${s.date} ${s.time}"), "${s.direction}", ${s.entryPrice}, ${s.exitPrice}},`);
});

console.log("\n✓ Signals saved to nfp_signals.json");
