const fs = require("fs");
const path = require("path");

// Load data and month psychology
const filePath = path.join(__dirname, "nfp_data.json");
const rawData = fs.readFileSync(filePath, "utf-8");
const data = JSON.parse(rawData).vestor_data;
const { monthProfiles } = require("./month_psychology.js");

// Prediction logic
function isInverseEvent(eventName) {
  const inverse = [
    "unemployment", "jobless claims", "continuing jobless", "initial jobless",
    "participation rate", "u6 unemployment", "trade balance", "trade deficit",
    "imports"
  ];
  const nameLower = eventName.toLowerCase();
  return inverse.some(keyword => nameLower.includes(keyword));
}

function getXAUUSDPrediction(forecast, previous, eventName, impactLevel) {
  if (forecast === null || previous === null) return "DOWN";
  const f = parseFloat(forecast);
  const p = parseFloat(previous);
  const isInverse = isInverseEvent(eventName);
  
  if (f > p) {
    return isInverse ? "UP" : "DOWN";
  } else if (f < p) {
    return isInverse ? "DOWN" : "UP";
  } else {
    return (impactLevel === "High") ? "DOWN" : "UP";
  }
}

// NEW: Predict continuation based on actual vs forecast (surprise direction)
function getContinuationPrediction(actual, forecast, eventName) {
  if (actual === null || forecast === null) return "NEUTRAL";
  const a = parseFloat(actual);
  const f = parseFloat(forecast);
  const isInverse = isInverseEvent(eventName);
  
  // If actual is better than forecast, market should continue in that direction
  if (a > f) {
    // Actual beat forecast
    return isInverse ? "UP" : "DOWN"; // Better = continue same direction
  } else if (a < f) {
    // Actual missed forecast
    return isInverse ? "DOWN" : "UP"; // Worse = reverse
  }
  return "NEUTRAL";
}

function getDirectionAtMinute(occData, minutes) {
  // For 1-5 minutes: use xauusd_data array
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
      range: highestHigh - lowestLow,
      close: candles[candles.length - 1].close,
      pnl: upMove >= downMove ? upMove : -downMove
    };
  }
  
  // For 30 minutes: use candle_30min property
  if (minutes === 30) {
    const candle = occData.candle_30min;
    if (!candle) return null;
    
    const upMove = candle.high - candle.open;
    const downMove = candle.open - candle.low;
    
    return {
      direction: upMove >= downMove ? "UP" : "DOWN",
      upMove,
      downMove,
      range: candle.high - candle.low,
      close: candle.close,
      pnl: upMove >= downMove ? upMove : -downMove
    };
  }
  
  return null;
}

// Calculate improved weighted prediction
function calculateImprovedWeightedPrediction(predictions, eventData, month) {
  const profile = monthProfiles[month];
  if (!profile) return null;
  
  const weights = profile.weights;
  const signals = {
    unemployment: { magnitude: 0, direction: 0, weight: 0 },
    nonfarm: { magnitude: 0, direction: 0, weight: 0 },
    earnings: { magnitude: 0, direction: 0, weight: 0 }
  };
  
  if (eventData.unemployment && predictions.unemployment) {
    const f = parseFloat(eventData.unemployment.forecast || 0);
    const p = parseFloat(eventData.unemployment.previous || 0);
    const absDiff = Math.abs(f - p);
    signals.unemployment.magnitude = p !== 0 ? (absDiff / Math.abs(p)) * 100 : 0;
    signals.unemployment.direction = predictions.unemployment === "UP" ? 1 : -1;
    const magnitudeMultiplier = 1 + (signals.unemployment.magnitude / 10);
    signals.unemployment.weight = weights.unemployment * Math.min(magnitudeMultiplier, 2.0);
  }
  
  if (eventData.nonfarm && predictions.nonfarm) {
    const f = parseFloat(eventData.nonfarm.forecast || 0);
    const p = parseFloat(eventData.nonfarm.previous || 0);
    const absDiff = Math.abs(f - p);
    signals.nonfarm.magnitude = p !== 0 ? (absDiff / Math.abs(p)) * 100 : 0;
    signals.nonfarm.direction = predictions.nonfarm === "UP" ? 1 : -1;
    const magnitudeMultiplier = 1 + (signals.nonfarm.magnitude / 20);
    signals.nonfarm.weight = weights.nonfarm * Math.min(magnitudeMultiplier, 2.0);
  }
  
  if (eventData.earnings && predictions.earnings) {
    const f = parseFloat(eventData.earnings.forecast || 0);
    const p = parseFloat(eventData.earnings.previous || 0);
    const absDiff = Math.abs(f - p);
    signals.earnings.magnitude = p !== 0 ? (absDiff / Math.abs(p)) * 100 : 0;
    signals.earnings.direction = predictions.earnings === "UP" ? 1 : -1;
    const magnitudeMultiplier = 1 + (signals.earnings.magnitude / 15);
    signals.earnings.weight = weights.earnings * Math.min(magnitudeMultiplier, 2.0);
  }
  
  // Conflict adjustments
  const unemploymentPred = predictions.unemployment;
  const nfpPred = predictions.nonfarm;
  const earningsPred = predictions.earnings;
  
  const nfpEarningsAgree = nfpPred === earningsPred && nfpPred !== null;
  const unemploymentConflicts = unemploymentPred !== nfpPred && unemploymentPred !== null;
  
  if (nfpEarningsAgree && unemploymentConflicts) {
    const unemploymentMag = signals.unemployment.magnitude;
    const combinedMag = signals.nonfarm.magnitude + signals.earnings.magnitude;
    const weakUnemployment = unemploymentMag < 3;
    const strongCombined = combinedMag > 30;
    const ratioFlag = unemploymentMag > 0 && (combinedMag / unemploymentMag) > 10;
    
    if (weakUnemployment && (strongCombined || ratioFlag)) {
      signals.unemployment.weight *= 0.2;
      signals.nonfarm.weight *= 1.5;
      signals.earnings.weight *= 1.5;
    }
  }
  
  if (unemploymentPred === nfpPred && unemploymentPred !== null && signals.unemployment.magnitude > 2 && signals.nonfarm.magnitude > 8) {
    signals.unemployment.weight *= 1.2;
    signals.nonfarm.weight *= 1.2;
  }
  if (unemploymentPred === earningsPred && unemploymentPred !== null && signals.unemployment.magnitude > 2 && signals.earnings.magnitude > 8) {
    signals.unemployment.weight *= 1.2;
    signals.earnings.weight *= 1.2;
  }
  if (nfpPred === earningsPred && nfpPred !== null && signals.nonfarm.magnitude > 8 && signals.earnings.magnitude > 8) {
    signals.nonfarm.weight *= 1.2;
    signals.earnings.weight *= 1.2;
  }
  
  if (unemploymentPred === nfpPred && nfpPred === earningsPred && unemploymentPred !== null) {
    signals.unemployment.weight *= 1.5;
    signals.nonfarm.weight *= 1.5;
    signals.earnings.weight *= 1.5;
  }
  
  const score = (signals.unemployment.direction * signals.unemployment.weight) +
                (signals.nonfarm.direction * signals.nonfarm.weight) +
                (signals.earnings.direction * signals.earnings.weight);
  
  return score > 0 ? "UP" : "DOWN";
}

// Significant event IDs
const significantEventIds = {
  unemployment: "JCDYM",
  nonfarm: "VPRWG",
  earnings: "ZBEYU"
};

// Track results for different strategies
const strategies = {
  exit1Min: { wins: 0, losses: 0, totalPnL: 0, trades: [] },
  exit3Min: { wins: 0, losses: 0, totalPnL: 0, trades: [] },
  exit4Min: { wins: 0, losses: 0, totalPnL: 0, trades: [] },
  exit5Min: { wins: 0, losses: 0, totalPnL: 0, trades: [] },
  exit30Min: { wins: 0, losses: 0, totalPnL: 0, trades: [] },
  actualExit3Min: { wins: 0, losses: 0, totalPnL: 0, trades: [] },
  actualExit4Min: { wins: 0, losses: 0, totalPnL: 0, trades: [] },
  actualExit5Min: { wins: 0, losses: 0, totalPnL: 0, trades: [] },
  actualExit30Min: { wins: 0, losses: 0, totalPnL: 0, trades: [] }
};

console.log('='.repeat(80));
console.log('OPTIMIZING EXIT TIMING: Using ACTUAL Values After First Minute');
console.log('='.repeat(80));

let processedCount = 0;
let skippedCount = 0;

Object.values(data).forEach(occ => {
  const unemploymentEvent = occ.events.find(e => e.consistent_event_id === significantEventIds.unemployment);
  const nfpEvent = occ.events.find(e => e.consistent_event_id === significantEventIds.nonfarm);
  const earningsEvent = occ.events.find(e => e.consistent_event_id === significantEventIds.earnings);
  
  if (!unemploymentEvent || !nfpEvent || !earningsEvent) {
    skippedCount++;
    return;
  }
  if (!occ.xauusd_data || occ.xauusd_data.length < 5) {
    skippedCount++;
    console.log(`Skipped ${occ.occurrence_date}: Insufficient candle data (${occ.xauusd_data ? occ.xauusd_data.length : 0} candles)`);
    return;
  }
  
  processedCount++;
  //console.log(`Processing ${occ.occurrence_date}...`);
  
  const predictions = {
    unemployment: getXAUUSDPrediction(unemploymentEvent.forecast_value, unemploymentEvent.previous_value, unemploymentEvent.event_name, unemploymentEvent.impact_level),
    nonfarm: getXAUUSDPrediction(nfpEvent.forecast_value, nfpEvent.previous_value, nfpEvent.event_name, nfpEvent.impact_level),
    earnings: getXAUUSDPrediction(earningsEvent.forecast_value, earningsEvent.previous_value, earningsEvent.event_name, earningsEvent.impact_level)
  };
  
  const eventData = {
    unemployment: { 
      forecast: unemploymentEvent.forecast_value, 
      previous: unemploymentEvent.previous_value,
      actual: unemploymentEvent.actual_value 
    },
    nonfarm: { 
      forecast: nfpEvent.forecast_value, 
      previous: nfpEvent.previous_value,
      actual: nfpEvent.actual_value 
    },
    earnings: { 
      forecast: earningsEvent.forecast_value, 
      previous: earningsEvent.previous_value,
      actual: earningsEvent.actual_value 
    }
  };
  
  const monthNum = parseInt(occ.occurrence_date.substring(5, 7));
  const monthName = new Date(2000, monthNum - 1).toLocaleString('en', { month: 'short' });
  
  const entryPrediction = calculateImprovedWeightedPrediction(predictions, eventData, monthName);
  
  // Get continuation predictions using ACTUAL values
  const continuationPredictions = {
    unemployment: getContinuationPrediction(eventData.unemployment.actual, eventData.unemployment.forecast, unemploymentEvent.event_name),
    nonfarm: getContinuationPrediction(eventData.nonfarm.actual, eventData.nonfarm.forecast, nfpEvent.event_name),
    earnings: getContinuationPrediction(eventData.earnings.actual, eventData.earnings.forecast, earningsEvent.event_name)
  };
  
  const continuationPrediction = calculateImprovedWeightedPrediction(continuationPredictions, eventData, monthName);
  
  // Get actual market direction at different timeframes
  const result1Min = getDirectionAtMinute(occ, 1);
  const result3Min = getDirectionAtMinute(occ, 3);
  const result4Min = getDirectionAtMinute(occ, 4);
  const result5Min = getDirectionAtMinute(occ, 5);
  const result30Min = getDirectionAtMinute(occ, 30);
  
  if (!result1Min || !result3Min || !result4Min || !result5Min || !result30Min) return;
  
  // Strategy 1-5: Hold position from entry to X minutes
  const checkResult = (strategy, result, prediction) => {
    if (!result) return;
    const correct = prediction === result.direction;
    const pnl = correct ? result.range : -result.range;
    
    if (correct) {
      strategy.wins++;
    } else {
      strategy.losses++;
    }
    strategy.totalPnL += pnl;
    strategy.trades.push({
      date: occ.occurrence_date,
      prediction,
      actual: result.direction,
      correct,
      pnl
    });
  };
  
  checkResult(strategies.exit1Min, result1Min, entryPrediction);
  checkResult(strategies.exit3Min, result3Min, entryPrediction);
  checkResult(strategies.exit4Min, result4Min, entryPrediction);
  checkResult(strategies.exit5Min, result5Min, entryPrediction);
  checkResult(strategies.exit30Min, result30Min, entryPrediction);

  // Strategy 6-9: Use ACTUAL values after 1min to predict continuation
  checkResult(strategies.actualExit3Min, result3Min, continuationPrediction);
  checkResult(strategies.actualExit4Min, result4Min, continuationPrediction);
  checkResult(strategies.actualExit5Min, result5Min, continuationPrediction);
  checkResult(strategies.actualExit30Min, result30Min, continuationPrediction);
});

console.log(`\nProcessed: ${processedCount} occurrences | Skipped: ${skippedCount} occurrences`);

console.log('\n📊 STRATEGY COMPARISON:');
console.log('='.repeat(80));

const displayStrategy = (name, strategy) => {
  const total = strategy.wins + strategy.losses;
  const winRate = total > 0 ? (strategy.wins / total * 100).toFixed(2) : 0;
  const avgPnL = total > 0 ? (strategy.totalPnL / total).toFixed(2) : 0;
  
  console.log(`\n${name}:`);
  console.log(`  Wins: ${strategy.wins} | Losses: ${strategy.losses} | Total: ${total}`);
  console.log(`  Win Rate: ${winRate}%`);
  console.log(`  Total P&L: ${strategy.totalPnL.toFixed(2)} pips`);
  console.log(`  Avg P&L per Trade: ${avgPnL} pips`);
  console.log(`  Net Result: ${strategy.wins - strategy.losses > 0 ? '+' : ''}${strategy.wins - strategy.losses} trades`);
  
  return { name, winRate: parseFloat(winRate), totalPnL: strategy.totalPnL, avgPnL: parseFloat(avgPnL), total };
};

console.log('\n🎯 HOLDING INITIAL PREDICTION:');
const r1 = displayStrategy('1-Minute Exit (Current)', strategies.exit1Min);
const r3 = displayStrategy('3-Minute Exit', strategies.exit3Min);
const r4 = displayStrategy('4-Minute Exit', strategies.exit4Min);
const r5 = displayStrategy('5-Minute Exit', strategies.exit5Min);
const r30 = displayStrategy('30-Minute Exit', strategies.exit30Min);

console.log('\n\n🔥 USING ACTUAL VALUES AFTER 1ST MINUTE:');
const a3 = displayStrategy('Actual-Based 3-Min Exit', strategies.actualExit3Min);
const a4 = displayStrategy('Actual-Based 4-Min Exit', strategies.actualExit4Min);
const a5 = displayStrategy('Actual-Based 5-Min Exit', strategies.actualExit5Min);
const a30 = displayStrategy('Actual-Based 30-Min Exit', strategies.actualExit30Min);

console.log('\n' + '='.repeat(80));
console.log('🏆 BEST STRATEGIES RANKED BY WIN RATE:');
console.log('='.repeat(80));

const allResults = [r1, r3, r4, r5, r30, a3, a4, a5, a30];
allResults.sort((a, b) => b.winRate - a.winRate);

allResults.forEach((r, idx) => {
  console.log(`${idx + 1}. ${r.name}: ${r.winRate}% win rate | ${r.totalPnL.toFixed(2)} pips total | ${r.avgPnL} avg`);
});

console.log('\n' + '='.repeat(80));
console.log('🏆 BEST STRATEGIES RANKED BY TOTAL P&L:');
console.log('='.repeat(80));

allResults.sort((a, b) => b.totalPnL - a.totalPnL);

allResults.forEach((r, idx) => {
  console.log(`${idx + 1}. ${r.name}: ${r.totalPnL.toFixed(2)} pips | ${r.winRate}% win rate`);
});

console.log('\n' + '='.repeat(80));
console.log('💡 RECOMMENDATION:');
console.log('='.repeat(80));

const best = allResults[0];
console.log(`\n✅ OPTIMAL STRATEGY: ${best.name}`);
console.log(`   Win Rate: ${best.winRate}%`);
console.log(`   Total P&L: ${best.totalPnL.toFixed(2)} pips`);
console.log(`   Average: ${best.avgPnL} pips per trade`);

if (best.name.includes('Actual-Based')) {
  console.log(`\n🔥 KEY INSIGHT: Using ACTUAL values after 1st minute IMPROVES results!`);
  console.log(`   The market continues in the direction of the surprise (actual vs forecast)`);
} else {
  console.log(`\n💡 KEY INSIGHT: Initial prediction holds best, no need to re-evaluate with actuals`);
}

console.log('\n');
