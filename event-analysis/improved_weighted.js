const fs = require('fs');
const monthProfiles = require('./month_psychology.js').monthProfiles;

// Read the NFP data
const data = JSON.parse(fs.readFileSync('nfp_data.json', 'utf8'));

// Load enhanced weighted function from enhanced_weighted.js
function calculateEnhancedWeightedPrediction(predictions, eventData, month) {
  const profile = monthProfiles[month];
  if (!profile) return null;
  
  const weights = profile.weights;
  
  // Calculate forecast-previous magnitude and DIRECTION for each event
  const signals = {
    unemployment: { magnitude: 0, direction: 0, weight: 0 },
    nonfarm: { magnitude: 0, direction: 0, weight: 0 },
    earnings: { magnitude: 0, direction: 0, weight: 0 }
  };
  
  if (eventData.unemployment && predictions.unemployment) {
    const f = parseFloat(eventData.unemployment.forecast || 0);
    const p = parseFloat(eventData.unemployment.previous || 0);
    const diff = f - p;
    const absDiff = Math.abs(diff);
    signals.unemployment.magnitude = p !== 0 ? (absDiff / Math.abs(p)) * 100 : 0;
    signals.unemployment.direction = predictions.unemployment === "UP" ? 1 : -1;
    
    const magnitudeMultiplier = 1 + (signals.unemployment.magnitude / 10);
    signals.unemployment.weight = weights.unemployment * Math.min(magnitudeMultiplier, 2.0);
  } else {
    signals.unemployment.weight = 0;
    signals.unemployment.direction = 0;
  }
  
  if (eventData.nonfarm && predictions.nonfarm) {
    const f = parseFloat(eventData.nonfarm.forecast || 0);
    const p = parseFloat(eventData.nonfarm.previous || 0);
    const diff = f - p;
    const absDiff = Math.abs(diff);
    signals.nonfarm.magnitude = p !== 0 ? (absDiff / Math.abs(p)) * 100 : 0;
    signals.nonfarm.direction = predictions.nonfarm === "UP" ? 1 : -1;
    
    const magnitudeMultiplier = 1 + (signals.nonfarm.magnitude / 20);
    signals.nonfarm.weight = weights.nonfarm * Math.min(magnitudeMultiplier, 2.0);
  } else {
    signals.nonfarm.weight = 0;
    signals.nonfarm.direction = 0;
  }
  
  if (eventData.earnings && predictions.earnings) {
    const f = parseFloat(eventData.earnings.forecast || 0);
    const p = parseFloat(eventData.earnings.previous || 0);
    const diff = f - p;
    const absDiff = Math.abs(diff);
    signals.earnings.magnitude = p !== 0 ? (absDiff / Math.abs(p)) * 100 : 0;
    signals.earnings.direction = predictions.earnings === "UP" ? 1 : -1;
    
    const magnitudeMultiplier = 1 + (signals.earnings.magnitude / 15);
    signals.earnings.weight = weights.earnings * Math.min(magnitudeMultiplier, 2.0);
  } else {
    signals.earnings.weight = 0;
    signals.earnings.direction = 0;
  }
  
  // Calculate weighted score
  let score = 0;
  score += signals.unemployment.weight * signals.unemployment.direction;
  score += signals.nonfarm.weight * signals.nonfarm.direction;
  score += signals.earnings.weight * signals.earnings.direction;
  
  const direction = score > 0 ? "UP" : "DOWN";
  return direction;
}

// Helper function to check if event is inverse
function isInverseEvent(eventName) {
  const inverse = [
    "unemployment", "jobless claims", "continuing jobless", "initial jobless",
    "participation rate", "u6 unemployment", "trade balance", "trade deficit",
    "imports"
  ];
  const nameLower = eventName.toLowerCase();
  return inverse.some(keyword => nameLower.includes(keyword));
}

// Helper function to get XAUUSD prediction
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

// Helper function to get actual direction
function getActualDirection(firstMinData) {
  if (!firstMinData) return null;
  const open = firstMinData.open;
  const high = firstMinData.high;
  const low = firstMinData.low;
  const upMove = high - open;
  const downMove = open - low;
  
  return upMove >= downMove ? "UP" : "DOWN";
}

// Analyze first candle
function analyzeFirstCandle(xauusdData) {
  if (!xauusdData || xauusdData.length === 0) return null;
  return xauusdData[0];
}

// IMPROVED: Calculate weighted prediction with conflict adjustment
function calculateImprovedWeightedPrediction(predictions, eventData, month) {
  const monthProfile = monthProfiles[month];
  if (!monthProfile) return 'NEUTRAL';
  
  const baseWeights = monthProfile.weights;
  const sensitivity = { unemployment: 10, nonfarm: 20, earnings: 15 };
  
  let weights = { unemployment: 0, nonfarm: 0, earnings: 0 };
  let magnitudes = { unemployment: 0, nonfarm: 0, earnings: 0 };
  
  // Calculate magnitudes and dynamic weights
  Object.keys(predictions).forEach(event => {
    if (predictions[event] === 'NEUTRAL' || !eventData[event]) return;
    
    const magnitude = Math.abs(eventData[event].forecast - eventData[event].previous);
    const magnitudePct = (magnitude / Math.abs(eventData[event].previous)) * 100;
    magnitudes[event] = magnitudePct;
    
    const baseWeight = baseWeights[event] || 0.33;
    const multiplier = 1 + (magnitudePct / sensitivity[event]);
    const cappedMultiplier = Math.min(multiplier, 2);
    
    weights[event] = baseWeight * cappedMultiplier;
  });
  
  // IMPROVEMENT 1: Detect conflict pattern (unemployment vs NFP+Earnings)
  const unemploymentPred = predictions.unemployment;
  const nfpPred = predictions.nonfarm;
  const earningsPred = predictions.earnings;
  
  const nfpEarningsAgree = nfpPred === earningsPred && nfpPred !== 'NEUTRAL';
  const unemploymentConflicts = unemploymentPred !== nfpPred && unemploymentPred !== 'NEUTRAL';
  
  if (nfpEarningsAgree && unemploymentConflicts) {
    // Check if it's the loss pattern we identified
    const unemploymentMag = magnitudes.unemployment;
    const nfpMag = magnitudes.nonfarm;
    const earningsMag = magnitudes.earnings;
    const combinedMag = nfpMag + earningsMag;
    
    // RED FLAG CONDITIONS
    const weakUnemployment = unemploymentMag < 3;
    const strongCombined = combinedMag > 30;
    const ratioFlag = unemploymentMag > 0 && (combinedMag / unemploymentMag) > 10;
    
    if (weakUnemployment && (strongCombined || ratioFlag)) {
      // CONFLICT PENALTY: Reduce unemployment weight drastically
      weights.unemployment *= 0.2; // 80% reduction
      
      // CONFLICT BOOST: Increase NFP+Earnings weights
      weights.nonfarm *= 1.5;
      weights.earnings *= 1.5;
    }
  }
  
  // IMPROVEMENT 2: Double confirmation boost
  // When any two events agree AND both have substantial magnitude
  let doubleConfirmations = 0;
  if (unemploymentPred === nfpPred && unemploymentPred !== 'NEUTRAL') {
    if (magnitudes.unemployment > 2 && magnitudes.nonfarm > 8) {
      weights.unemployment *= 1.2;
      weights.nonfarm *= 1.2;
      doubleConfirmations++;
    }
  }
  if (unemploymentPred === earningsPred && unemploymentPred !== 'NEUTRAL') {
    if (magnitudes.unemployment > 2 && magnitudes.earnings > 8) {
      weights.unemployment *= 1.2;
      weights.earnings *= 1.2;
      doubleConfirmations++;
    }
  }
  if (nfpPred === earningsPred && nfpPred !== 'NEUTRAL') {
    if (magnitudes.nonfarm > 8 && magnitudes.earnings > 8) {
      weights.nonfarm *= 1.2;
      weights.earnings *= 1.2;
      doubleConfirmations++;
    }
  }
  
  // IMPROVEMENT 3: Triple agreement mega boost
  if (unemploymentPred === nfpPred && nfpPred === earningsPred && unemploymentPred !== 'NEUTRAL') {
    // All three agree - very strong signal
    weights.unemployment *= 1.5;
    weights.nonfarm *= 1.5;
    weights.earnings *= 1.5;
  }
  
  // Calculate weighted scores
  let upScore = 0;
  let downScore = 0;
  
  Object.keys(predictions).forEach(event => {
    if (predictions[event] === 'UP') {
      upScore += weights[event];
    } else if (predictions[event] === 'DOWN') {
      downScore += weights[event];
    }
  });
  
  if (upScore > downScore) return 'UP';
  if (downScore > upScore) return 'DOWN';
  return 'NEUTRAL';
}

// Collect all results
const results = {
  enhanced: { correct: 0, total: 0, details: [] },
  improved: { correct: 0, total: 0, details: [] }
};

Object.values(data.vestor_data).forEach(occurrence => {
  const unemploymentEvent = occurrence.events.find(e => e.consistent_event_id === 'JCDYM');
  const nfpEvent = occurrence.events.find(e => e.consistent_event_id === 'VPRWG');
  const earningsEvent = occurrence.events.find(e => e.consistent_event_id === 'ZBEYU');
  
  if (!unemploymentEvent || !nfpEvent || !earningsEvent) return;
  
  const firstCandle = analyzeFirstCandle(occurrence.xauusd_data);
  if (!firstCandle) return;
  
  const predictions = {
    unemployment: getXAUUSDPrediction(unemploymentEvent.forecast_value, unemploymentEvent.previous_value, unemploymentEvent.event_name, unemploymentEvent.impact_level),
    nonfarm: getXAUUSDPrediction(nfpEvent.forecast_value, nfpEvent.previous_value, nfpEvent.event_name, nfpEvent.impact_level),
    earnings: getXAUUSDPrediction(earningsEvent.forecast_value, earningsEvent.previous_value, earningsEvent.event_name, earningsEvent.impact_level)
  };
  
  const eventData = {
    unemployment: {
      forecast: unemploymentEvent.forecast_value,
      previous: unemploymentEvent.previous_value
    },
    nonfarm: {
      forecast: nfpEvent.forecast_value,
      previous: nfpEvent.previous_value
    },
    earnings: {
      forecast: earningsEvent.forecast_value,
      previous: earningsEvent.previous_value
    }
  };
  
  const actualDirection = getActualDirection(firstCandle);
  const month = new Date(occurrence.occurrence_date).toLocaleString('en-US', { month: 'long' });
  
  // Enhanced prediction (original 59.57%)
  const enhancedPrediction = calculateEnhancedWeightedPrediction(predictions, eventData, month);
  
  // Improved prediction (with conflict adjustment)
  const improvedPrediction = calculateImprovedWeightedPrediction(predictions, eventData, month);
  
  results.enhanced.total++;
  results.improved.total++;
  
  const enhancedCorrect = enhancedPrediction === actualDirection;
  const improvedCorrect = improvedPrediction === actualDirection;
  
  if (enhancedCorrect) results.enhanced.correct++;
  if (improvedCorrect) results.improved.correct++;
  
  // Track cases where improved differs from enhanced
  if (enhancedPrediction !== improvedPrediction) {
    results.improved.details.push({
      date: occurrence.occurrence_date,
      month,
      enhanced: enhancedPrediction,
      improved: improvedPrediction,
      actual: actualDirection,
      enhancedCorrect,
      improvedCorrect,
      change: improvedCorrect && !enhancedCorrect ? 'IMPROVED' : 
              !improvedCorrect && enhancedCorrect ? 'WORSENED' : 'NO_CHANGE',
      predictions,
      magnitudes: {
        unemployment: (Math.abs(eventData.unemployment.forecast - eventData.unemployment.previous) / Math.abs(eventData.unemployment.previous)) * 100,
        nonfarm: (Math.abs(eventData.nonfarm.forecast - eventData.nonfarm.previous) / Math.abs(eventData.nonfarm.previous)) * 100,
        earnings: (Math.abs(eventData.earnings.forecast - eventData.earnings.previous) / Math.abs(eventData.earnings.previous)) * 100
      }
    });
  }
});

console.log('='.repeat(80));
console.log('IMPROVED WEIGHTING SYSTEM WITH CONFLICT ADJUSTMENT');
console.log('='.repeat(80));

console.log('\n📊 OVERALL RESULTS:');
console.log(`\nEnhanced Weighted (Original):`);
console.log(`  Correct: ${results.enhanced.correct}/${results.enhanced.total}`);
console.log(`  Accuracy: ${(results.enhanced.correct/results.enhanced.total*100).toFixed(2)}%`);

console.log(`\nImproved Weighted (With Conflict Logic):`);
console.log(`  Correct: ${results.improved.correct}/${results.improved.total}`);
console.log(`  Accuracy: ${(results.improved.correct/results.improved.total*100).toFixed(2)}%`);

const improvement = results.improved.correct - results.enhanced.correct;
const improvementPct = ((results.improved.correct/results.improved.total) - (results.enhanced.correct/results.enhanced.total)) * 100;

console.log(`\n📈 IMPROVEMENT:`);
if (improvement > 0) {
  console.log(`  +${improvement} more correct predictions`);
  console.log(`  +${improvementPct.toFixed(2)}% accuracy improvement`);
  console.log(`  ✅ IMPROVED SYSTEM WINS!`);
} else if (improvement < 0) {
  console.log(`  ${improvement} fewer correct predictions`);
  console.log(`  ${improvementPct.toFixed(2)}% accuracy change`);
  console.log(`  ❌ Original was better`);
} else {
  console.log(`  No change in accuracy`);
  console.log(`  Same results`);
}

console.log(`\n📋 CASES WHERE PREDICTION CHANGED: ${results.improved.details.length}`);

// Analyze the changes
const improved = results.improved.details.filter(d => d.change === 'IMPROVED');
const worsened = results.improved.details.filter(d => d.change === 'WORSENED');
const noChange = results.improved.details.filter(d => d.change === 'NO_CHANGE');

console.log(`  Improved (wrong → right): ${improved.length}`);
console.log(`  Worsened (right → wrong): ${worsened.length}`);
console.log(`  No impact (both wrong or both right): ${noChange.length}`);

if (improved.length > 0) {
  console.log('\n' + '='.repeat(80));
  console.log('✅ CASES THAT IMPROVED (Enhanced Wrong → Improved Right)');
  console.log('='.repeat(80));
  
  improved.forEach((d, idx) => {
    console.log(`\n${idx + 1}. ${d.date} (${d.month})`);
    console.log(`   Enhanced: ${d.enhanced} ❌ → Improved: ${d.improved} ✅ (Actual: ${d.actual})`);
    console.log(`   Predictions: Unemployment=${d.predictions.unemployment}, NFP=${d.predictions.nonfarm}, Earnings=${d.predictions.earnings}`);
    console.log(`   Magnitudes: Unemployment=${d.magnitudes.unemployment.toFixed(2)}%, NFP=${d.magnitudes.nonfarm.toFixed(2)}%, Earnings=${d.magnitudes.earnings.toFixed(2)}%`);
    
    // Check if conflict pattern
    const nfpEarningsAgree = d.predictions.nonfarm === d.predictions.earnings;
    const unemploymentConflicts = d.predictions.unemployment !== d.predictions.nonfarm;
    if (nfpEarningsAgree && unemploymentConflicts) {
      const ratio = d.magnitudes.unemployment > 0 ? (d.magnitudes.nonfarm + d.magnitudes.earnings) / d.magnitudes.unemployment : 999;
      console.log(`   🚩 CONFLICT PATTERN DETECTED: NFP+Earnings agree (${d.predictions.nonfarm}), Unemployment disagrees (${d.predictions.unemployment})`);
      console.log(`      Magnitude ratio: ${ratio.toFixed(1)}x`);
    }
  });
}

if (worsened.length > 0) {
  console.log('\n' + '='.repeat(80));
  console.log('❌ CASES THAT WORSENED (Enhanced Right → Improved Wrong)');
  console.log('='.repeat(80));
  
  worsened.forEach((d, idx) => {
    console.log(`\n${idx + 1}. ${d.date} (${d.month})`);
    console.log(`   Enhanced: ${d.enhanced} ✅ → Improved: ${d.improved} ❌ (Actual: ${d.actual})`);
    console.log(`   Predictions: Unemployment=${d.predictions.unemployment}, NFP=${d.predictions.nonfarm}, Earnings=${d.predictions.earnings}`);
    console.log(`   Magnitudes: Unemployment=${d.magnitudes.unemployment.toFixed(2)}%, NFP=${d.magnitudes.nonfarm.toFixed(2)}%, Earnings=${d.magnitudes.earnings.toFixed(2)}%`);
  });
}

console.log('\n' + '='.repeat(80));
console.log('SUMMARY OF IMPROVEMENTS');
console.log('='.repeat(80));

console.log('\n🔧 Conflict Adjustments Applied:');
console.log('   1. When NFP+Earnings agree but Unemployment disagrees:');
console.log('      - IF unemployment magnitude < 3%');
console.log('      - AND combined NFP+Earnings magnitude > 30% OR ratio > 10x');
console.log('      - THEN reduce unemployment weight by 80%');
console.log('      - AND boost NFP+Earnings weights by 50%');
console.log('');
console.log('   2. Double Confirmation Boost:');
console.log('      - When any 2 events agree with substantial magnitudes');
console.log('      - Boost both agreeing events by 20%');
console.log('');
console.log('   3. Triple Agreement Mega Boost:');
console.log('      - When all 3 events agree');
console.log('      - Boost all weights by 50%');

console.log(`\n🎯 Net Result: ${improvement >= 0 ? 'SUCCESS' : 'NEEDS REFINEMENT'}`);
console.log(`   Final Accuracy: ${(results.improved.correct/results.improved.total*100).toFixed(2)}%`);

console.log('\n');
