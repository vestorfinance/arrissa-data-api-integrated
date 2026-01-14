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

function getActualDirection1Min(firstMinData) {
  if (firstMinData.length === 0) return null;
  const open = firstMinData[0].open;
  const high = firstMinData[0].high;
  const low = firstMinData[0].low;
  const upMove = high - open;
  const downMove = open - low;
  
  return upMove >= downMove ? "UP" : "DOWN";
}

function getActualDirection2Min(xauusdData) {
  if (!xauusdData || xauusdData.length < 2) return null;
  
  // Take first 2 candles
  const first2 = xauusdData.slice(0, 2);
  const open = first2[0].open;
  
  // Find highest high and lowest low across 2 minutes
  let highestHigh = first2[0].high;
  let lowestLow = first2[0].low;
  
  first2.forEach(candle => {
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
    dominance: upMove >= downMove ? (upMove / (upMove + downMove)) * 100 : (downMove / (upMove + downMove)) * 100
  };
}

function getActualDirection5Min(xauusdData) {
  if (!xauusdData || xauusdData.length < 5) return null;
  
  // Take first 5 candles
  const first5 = xauusdData.slice(0, 5);
  const open = first5[0].open;
  
  // Find highest high and lowest low across 5 minutes
  let highestHigh = first5[0].high;
  let lowestLow = first5[0].low;
  
  first5.forEach(candle => {
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
    dominance: upMove >= downMove ? (upMove / (upMove + downMove)) * 100 : (downMove / (upMove + downMove)) * 100
  };
}

// IMPROVED: Calculate weighted prediction with conflict adjustment
function calculateImprovedWeightedPrediction(predictions, eventData, month) {
  const profile = monthProfiles[month];
  if (!profile) return null;
  
  const weights = profile.weights;
  let score = 0;
  
  // Calculate forecast-previous magnitude and DIRECTION for each event
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
  
  // IMPROVEMENT 1: Detect conflict pattern
  const unemploymentPred = predictions.unemployment;
  const nfpPred = predictions.nonfarm;
  const earningsPred = predictions.earnings;
  
  const nfpEarningsAgree = nfpPred === earningsPred && nfpPred !== null;
  const unemploymentConflicts = unemploymentPred !== nfpPred && unemploymentPred !== null;
  
  if (nfpEarningsAgree && unemploymentConflicts) {
    const unemploymentMag = signals.unemployment.magnitude;
    const nfpMag = signals.nonfarm.magnitude;
    const earningsMag = signals.earnings.magnitude;
    const combinedMag = nfpMag + earningsMag;
    
    const weakUnemployment = unemploymentMag < 3;
    const strongCombined = combinedMag > 30;
    const ratioFlag = unemploymentMag > 0 && (combinedMag / unemploymentMag) > 10;
    
    if (weakUnemployment && (strongCombined || ratioFlag)) {
      signals.unemployment.weight *= 0.2;
      signals.nonfarm.weight *= 1.5;
      signals.earnings.weight *= 1.5;
    }
  }
  
  // IMPROVEMENT 2: Double confirmation boost
  if (unemploymentPred === nfpPred && unemploymentPred !== null) {
    if (signals.unemployment.magnitude > 2 && signals.nonfarm.magnitude > 8) {
      signals.unemployment.weight *= 1.2;
      signals.nonfarm.weight *= 1.2;
    }
  }
  if (unemploymentPred === earningsPred && unemploymentPred !== null) {
    if (signals.unemployment.magnitude > 2 && signals.earnings.magnitude > 8) {
      signals.unemployment.weight *= 1.2;
      signals.earnings.weight *= 1.2;
    }
  }
  if (nfpPred === earningsPred && nfpPred !== null) {
    if (signals.nonfarm.magnitude > 8 && signals.earnings.magnitude > 8) {
      signals.nonfarm.weight *= 1.2;
      signals.earnings.weight *= 1.2;
    }
  }
  
  // IMPROVEMENT 3: Triple agreement mega boost
  if (unemploymentPred === nfpPred && nfpPred === earningsPred && unemploymentPred !== null) {
    signals.unemployment.weight *= 1.5;
    signals.nonfarm.weight *= 1.5;
    signals.earnings.weight *= 1.5;
  }
  
  // Calculate weighted score
  score = (signals.unemployment.direction * signals.unemployment.weight) +
          (signals.nonfarm.direction * signals.nonfarm.weight) +
          (signals.earnings.direction * signals.earnings.weight);
  
  const direction = score > 0 ? "UP" : "DOWN";
  
  return {
    direction,
    rawScore: score,
    signals
  };
}

// Significant event IDs
const significantEventIds = {
  unemployment: "JCDYM",
  nonfarm: "VPRWG",
  earnings: "ZBEYU"
};

// Analyze all occurrences
const wrongCases = [];
let total1Min = 0;
let correct1Min = 0;
let total2Min = 0;
let correct2Min = 0;
let total5Min = 0;
let correct5Min = 0;
let rescued2Min = 0; // Wrong at 1min but right at 2min
let rescued5Min = 0; // Wrong at 1min but right at 5min

Object.values(data).forEach(occ => {
  const unemploymentEvent = occ.events.find(e => e.consistent_event_id === significantEventIds.unemployment);
  const nfpEvent = occ.events.find(e => e.consistent_event_id === significantEventIds.nonfarm);
  const earningsEvent = occ.events.find(e => e.consistent_event_id === significantEventIds.earnings);
  
  if (!unemploymentEvent || !nfpEvent || !earningsEvent) return;
  if (!occ.xauusd_data || occ.xauusd_data.length < 5) return;
  
  const predictions = {
    unemployment: getXAUUSDPrediction(unemploymentEvent.forecast_value, unemploymentEvent.previous_value, unemploymentEvent.event_name, unemploymentEvent.impact_level),
    nonfarm: getXAUUSDPrediction(nfpEvent.forecast_value, nfpEvent.previous_value, nfpEvent.event_name, nfpEvent.impact_level),
    earnings: getXAUUSDPrediction(earningsEvent.forecast_value, earningsEvent.previous_value, earningsEvent.event_name, earningsEvent.impact_level)
  };
  
  const eventData = {
    unemployment: { forecast: unemploymentEvent.forecast_value, previous: unemploymentEvent.previous_value },
    nonfarm: { forecast: nfpEvent.forecast_value, previous: nfpEvent.previous_value },
    earnings: { forecast: earningsEvent.forecast_value, previous: earningsEvent.previous_value }
  };
  
  const monthNum = parseInt(occ.occurrence_date.substring(5, 7));
  const monthName = new Date(2000, monthNum - 1).toLocaleString('en', { month: 'short' });
  
  const improvedResult = calculateImprovedWeightedPrediction(predictions, eventData, monthName);
  const actual1Min = getActualDirection1Min(occ.xauusd_data);
  const actual2Min = getActualDirection2Min(occ.xauusd_data);
  const actual5Min = getActualDirection5Min(occ.xauusd_data);
  
  total1Min++;
  const correct1MinResult = improvedResult.direction === actual1Min;
  if (correct1MinResult) correct1Min++;
  
  total2Min++;
  const correct2MinResult = improvedResult.direction === actual2Min.direction;
  if (correct2MinResult) correct2Min++;
  
  total5Min++;
  const correct5MinResult = improvedResult.direction === actual5Min.direction;
  if (correct5MinResult) correct5Min++;
  
  // Track cases that were wrong at 1min
  if (!correct1MinResult) {
    const isRescued2Min = improvedResult.direction === actual2Min.direction;
    const isRescued5Min = improvedResult.direction === actual5Min.direction;
    if (isRescued2Min) rescued2Min++;
    if (isRescued5Min) rescued5Min++;
    
    wrongCases.push({
      date: occ.occurrence_date,
      month: monthName,
      prediction: improvedResult.direction,
      actual1Min,
      actual2Min: actual2Min.direction,
      actual5Min: actual5Min.direction,
      correct1Min: correct1MinResult,
      correct2Min: correct2MinResult,
      correct5Min: correct5MinResult,
      rescued2Min: isRescued2Min,
      rescued5Min: isRescued5Min,
      twoMinDetails: actual2Min,
      fiveMinDetails: actual5Min,
      predictions,
      magnitudes: {
        unemployment: improvedResult.signals.unemployment.magnitude,
        nonfarm: improvedResult.signals.nonfarm.magnitude,
        earnings: improvedResult.signals.earnings.magnitude
      }
    });
  }
});

console.log('='.repeat(80));
console.log('ANALYZING WRONG PREDICTIONS: 1-MIN vs 2-MIN vs 5-MIN EVALUATION');
console.log('='.repeat(80));

console.log('\n📊 OVERALL RESULTS:');
console.log(`\n1-MINUTE EVALUATION (Current):`);
console.log(`  Correct: ${correct1Min}/${total1Min}`);
console.log(`  Accuracy: ${(correct1Min/total1Min*100).toFixed(2)}%`);
console.log(`  Wrong: ${total1Min - correct1Min}`);

console.log(`\n2-MINUTE EVALUATION:`);
console.log(`  Correct: ${correct2Min}/${total2Min}`);
console.log(`  Accuracy: ${(correct2Min/total2Min*100).toFixed(2)}%`);
console.log(`  Wrong: ${total2Min - correct2Min}`);

console.log(`\n5-MINUTE EVALUATION (Extended):`);
console.log(`  Correct: ${correct5Min}/${total5Min}`);
console.log(`  Accuracy: ${(correct5Min/total5Min*100).toFixed(2)}%`);
console.log(`  Wrong: ${total5Min - correct5Min}`);

const improvement2Min = correct2Min - correct1Min;
const improvementPct2Min = ((correct2Min/total2Min) - (correct1Min/total1Min)) * 100;

const improvement5Min = correct5Min - correct1Min;
const improvementPct5Min = ((correct5Min/total5Min) - (correct1Min/total1Min)) * 100;

console.log(`\n📈 IMPROVEMENT WITH 2-MINUTE WINDOW:`);
console.log(`  Additional Correct Predictions: ${improvement2Min}`);
console.log(`  Accuracy Improvement: ${improvementPct2Min >= 0 ? '+' : ''}${improvementPct2Min.toFixed(2)}%`);

console.log(`\n📈 IMPROVEMENT WITH 5-MINUTE WINDOW:`);
console.log(`  Additional Correct Predictions: ${improvement5Min}`);
console.log(`  Accuracy Improvement: ${improvementPct5Min >= 0 ? '+' : ''}${improvementPct5Min.toFixed(2)}%`);

console.log(`\n🔄 RESCUED TRADES:`);
console.log(`  Cases wrong at 1-min but RIGHT at 2-min: ${rescued2Min}/${wrongCases.length}`);
console.log(`  Rescue Rate (2-min): ${(rescued2Min/wrongCases.length*100).toFixed(1)}%`);
console.log(`  Cases wrong at 1-min but RIGHT at 5-min: ${rescued5Min}/${wrongCases.length}`);
console.log(`  Rescue Rate (5-min): ${(rescued5Min/wrongCases.length*100).toFixed(1)}%`);

if (rescued2Min > 0) {
  console.log('\n' + '='.repeat(80));
  console.log(`✅ RESCUED CASES AT 2-MINUTES (Wrong at 1-min, Right at 2-min) - ${rescued2Min} cases`);
  console.log('='.repeat(80));
  
  const rescuedCases2Min = wrongCases.filter(c => c.rescued2Min);
  rescuedCases2Min.forEach((c, idx) => {
    console.log(`\n${idx + 1}. ${c.date} (${c.month})`);
    console.log(`   Prediction: ${c.prediction}`);
    console.log(`   1-Minute: ${c.actual1Min} ❌ WRONG`);
    console.log(`   2-Minute: ${c.actual2Min} ✅ CORRECT`);
    console.log(`   2-Min Details: ${c.twoMinDetails.dominance.toFixed(1)}% dominance`);
    console.log(`                  Up Move: ${c.twoMinDetails.upMove.toFixed(2)} | Down Move: ${c.twoMinDetails.downMove.toFixed(2)}`);
    console.log(`   Event Predictions: Unemployment=${c.predictions.unemployment}, NFP=${c.predictions.nonfarm}, Earnings=${c.predictions.earnings}`);
  });
}

if (rescued5Min > 0) {
  console.log('\n' + '='.repeat(80));
  console.log(`✅ RESCUED CASES AT 5-MINUTES (Wrong at 1-min, Right at 5-min) - ${rescued5Min} cases`);
  console.log('='.repeat(80));
  
  const rescuedCases5Min = wrongCases.filter(c => c.rescued5Min);
  rescuedCases5Min.forEach((c, idx) => {
    console.log(`\n${idx + 1}. ${c.date} (${c.month})`);
    console.log(`   Prediction: ${c.prediction}`);
    console.log(`   1-Minute: ${c.actual1Min} ❌`);
    console.log(`   2-Minute: ${c.actual2Min} ${c.rescued2Min ? '✅' : '❌'}`);
    console.log(`   5-Minute: ${c.actual5Min} ✅ CORRECT`);
    console.log(`   5-Min Details: ${c.fiveMinDetails.dominance.toFixed(1)}% dominance`);
    console.log(`                  Up Move: ${c.fiveMinDetails.upMove.toFixed(2)} | Down Move: ${c.fiveMinDetails.downMove.toFixed(2)}`);
  });
}

const stillWrong = wrongCases.filter(c => !c.rescued2Min && !c.rescued5Min);
if (stillWrong.length > 0) {
  console.log('\n' + '='.repeat(80));
  console.log(`❌ STILL WRONG AT BOTH 2-MIN AND 5-MIN - ${stillWrong.length} cases`);
  console.log('='.repeat(80));
  
  stillWrong.slice(0, 5).forEach((c, idx) => {
    console.log(`\n${idx + 1}. ${c.date} (${c.month})`);
    console.log(`   Prediction: ${c.prediction}`);
    console.log(`   1-Minute: ${c.actual1Min} ❌`);
    console.log(`   2-Minute: ${c.actual2Min} ❌`);
    console.log(`   5-Minute: ${c.actual5Min} ❌`);
    console.log(`   2-Min: ${c.twoMinDetails.dominance.toFixed(1)}% dominance | 5-Min: ${c.fiveMinDetails.dominance.toFixed(1)}% dominance`);
  });
  
  if (stillWrong.length > 5) {
    console.log(`\n   ... and ${stillWrong.length - 5} more cases`);
  }
}

console.log('\n' + '='.repeat(80));
console.log('SUMMARY');
console.log('='.repeat(80));

console.log(`\n💰 PROFITABILITY ANALYSIS:`);
const profit1Min = correct1Min;
const loss1Min = total1Min - correct1Min;

const profit2Min = correct2Min;
const loss2Min = total2Min - correct2Min;

const profit5Min = correct5Min;
const loss5Min = total5Min - correct5Min;

console.log(`\n  1-Minute Window:`);
console.log(`    Wins: ${profit1Min} | Losses: ${loss1Min}`);
console.log(`    Win Rate: ${(profit1Min/total1Min*100).toFixed(2)}%`);
console.log(`    Net Result: ${profit1Min - loss1Min > 0 ? '+' : ''}${profit1Min - loss1Min} (assuming 1:1 risk/reward)`);

console.log(`\n  2-Minute Window:`);
console.log(`    Wins: ${profit2Min} | Losses: ${loss2Min}`);
console.log(`    Win Rate: ${(profit2Min/total2Min*100).toFixed(2)}%`);
console.log(`    Net Result: ${profit2Min - loss2Min > 0 ? '+' : ''}${profit2Min - loss2Min} (assuming 1:1 risk/reward)`);

console.log(`\n  5-Minute Window:`);
console.log(`    Wins: ${profit5Min} | Losses: ${loss5Min}`);
console.log(`    Win Rate: ${(profit5Min/total5Min*100).toFixed(2)}%`);
console.log(`    Net Result: ${profit5Min - loss5Min > 0 ? '+' : ''}${profit5Min - loss5Min} (assuming 1:1 risk/reward)`);

console.log(`\n🎯 CONCLUSION:`);

// Find best performer
const best = Math.max(correct1Min, correct2Min, correct5Min);
let bestWindow = '';
let bestAccuracy = 0;

if (correct1Min === best) {
  bestWindow = '1-MINUTE';
  bestAccuracy = (correct1Min/total1Min*100);
} else if (correct2Min === best) {
  bestWindow = '2-MINUTE';
  bestAccuracy = (correct2Min/total2Min*100);
} else {
  bestWindow = '5-MINUTE';
  bestAccuracy = (correct5Min/total5Min*100);
}

console.log(`  🏆 BEST PERFORMER: ${bestWindow} with ${bestAccuracy.toFixed(2)}% accuracy`);

if (improvementPct2Min > 0) {
  console.log(`  ✅ 2-minute window improves by ${improvementPct2Min.toFixed(2)}% vs 1-minute`);
  console.log(`  ✅ ${rescued2Min} trades rescued at 2 minutes`);
} else if (improvementPct2Min < 0) {
  console.log(`  ❌ 2-minute window decreases by ${Math.abs(improvementPct2Min).toFixed(2)}% vs 1-minute`);
}

if (improvementPct5Min > 0) {
  console.log(`  ✅ 5-minute window improves by ${improvementPct5Min.toFixed(2)}% vs 1-minute`);
} else if (improvementPct5Min < 0) {
  console.log(`  ❌ 5-minute window decreases by ${Math.abs(improvementPct5Min).toFixed(2)}% vs 1-minute`);
}

console.log(`\n  💡 RECOMMENDATION: Use ${bestWindow} evaluation for maximum profitability`);

console.log('\n');
