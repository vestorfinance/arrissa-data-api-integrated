const fs = require('fs');

// Read the NFP data
const data = JSON.parse(fs.readFileSync('nfp_data.json', 'utf8'));

// Helper function to check if event is inverse
function isInverseEvent(eventName) {
  return eventName && eventName.toLowerCase().includes('unemployment');
}

// Helper function to get XAUUSD prediction
function getXAUUSDPrediction(forecast, previous, eventName) {
  const isInverse = isInverseEvent(eventName);
  
  if (forecast > previous) {
    return isInverse ? 'UP' : 'DOWN';
  } else if (forecast < previous) {
    return isInverse ? 'DOWN' : 'UP';
  }
  return 'NEUTRAL';
}

// Helper function to get actual direction
function getActualDirection(firstMinData) {
  if (!firstMinData) return 'UNKNOWN';
  
  const upMove = firstMinData.high - firstMinData.open;
  const downMove = firstMinData.open - firstMinData.low;
  
  if (upMove > downMove) return 'UP';
  if (downMove > upMove) return 'DOWN';
  return 'NEUTRAL';
}

// Analyze first candle
function analyzeFirstCandle(xauusdData) {
  if (!xauusdData || xauusdData.length === 0) return null;
  return xauusdData[0];
}

// Collect all occurrences with unemployment predictions
const unemploymentData = [];

Object.values(data.vestor_data).forEach(occurrence => {
  const unemploymentEvent = occurrence.events.find(e => e.consistent_event_id === 'JCDYM');
  const nfpEvent = occurrence.events.find(e => e.consistent_event_id === 'VPRWG');
  const earningsEvent = occurrence.events.find(e => e.consistent_event_id === 'ZBEYU');
  
  if (!unemploymentEvent || !nfpEvent || !earningsEvent) return;
  
  const firstCandle = analyzeFirstCandle(occurrence.xauusd_data);
  if (!firstCandle) return;
  
  const unemploymentPrediction = getXAUUSDPrediction(
    unemploymentEvent.forecast_value,
    unemploymentEvent.previous_value,
    unemploymentEvent.event_name
  );
  
  const nfpPrediction = getXAUUSDPrediction(
    nfpEvent.forecast_value,
    nfpEvent.previous_value,
    nfpEvent.event_name
  );
  
  const earningsPrediction = getXAUUSDPrediction(
    earningsEvent.forecast_value,
    earningsEvent.previous_value,
    earningsEvent.event_name
  );
  
  const actualDirection = getActualDirection(firstCandle);
  
  const unemploymentCorrect = unemploymentPrediction === actualDirection;
  
  // Calculate magnitudes and surprises
  const unemploymentMagnitude = Math.abs(unemploymentEvent.forecast_value - unemploymentEvent.previous_value);
  const unemploymentMagnitudePct = (unemploymentMagnitude / Math.abs(unemploymentEvent.previous_value)) * 100;
  const unemploymentSurprise = Math.abs(unemploymentEvent.actual_value - unemploymentEvent.forecast_value);
  const unemploymentSurprisePct = (unemploymentSurprise / Math.abs(unemploymentEvent.forecast_value)) * 100;
  
  const nfpMagnitude = Math.abs(nfpEvent.forecast_value - nfpEvent.previous_value);
  const nfpMagnitudePct = (nfpMagnitude / Math.abs(nfpEvent.previous_value)) * 100;
  const nfpSurprise = Math.abs(nfpEvent.actual_value - nfpEvent.forecast_value);
  const nfpSurprisePct = (nfpSurprise / Math.abs(nfpEvent.forecast_value)) * 100;
  
  const earningsMagnitude = Math.abs(earningsEvent.forecast_value - earningsEvent.previous_value);
  const earningsMagnitudePct = (earningsMagnitude / Math.abs(earningsEvent.previous_value)) * 100;
  const earningsSurprise = Math.abs(earningsEvent.actual_value - earningsEvent.forecast_value);
  const earningsSurprisePct = (earningsSurprise / Math.abs(earningsEvent.forecast_value)) * 100;
  
  // Market volatility indicators
  const candleRange = firstCandle.high - firstCandle.low;
  const candleBody = Math.abs(firstCandle.close - firstCandle.open);
  const upperWick = firstCandle.high - Math.max(firstCandle.open, firstCandle.close);
  const lowerWick = Math.min(firstCandle.open, firstCandle.close) - firstCandle.low;
  
  unemploymentData.push({
    date: occurrence.occurrence_date,
    month: new Date(occurrence.occurrence_date).toLocaleString('en-US', { month: 'long' }),
    unemploymentCorrect,
    unemploymentPrediction,
    actualDirection,
    events: {
      unemployment: {
        forecast: unemploymentEvent.forecast_value,
        previous: unemploymentEvent.previous_value,
        actual: unemploymentEvent.actual_value,
        impact: unemploymentEvent.impact_level,
        magnitude: unemploymentMagnitude,
        magnitudePct: unemploymentMagnitudePct,
        surprise: unemploymentSurprise,
        surprisePct: unemploymentSurprisePct
      },
      nfp: {
        forecast: nfpEvent.forecast_value,
        previous: nfpEvent.previous_value,
        actual: nfpEvent.actual_value,
        impact: nfpEvent.impact_level,
        magnitude: nfpMagnitude,
        magnitudePct: nfpMagnitudePct,
        surprise: nfpSurprise,
        surprisePct: nfpSurprisePct,
        prediction: nfpPrediction,
        correct: nfpPrediction === actualDirection
      },
      earnings: {
        forecast: earningsEvent.forecast_value,
        previous: earningsEvent.previous_value,
        actual: earningsEvent.actual_value,
        impact: earningsEvent.impact_level,
        magnitude: earningsMagnitude,
        magnitudePct: earningsMagnitudePct,
        surprise: earningsSurprise,
        surprisePct: earningsSurprisePct,
        prediction: earningsPrediction,
        correct: earningsPrediction === actualDirection
      }
    },
    candle: {
      range: candleRange,
      body: candleBody,
      upperWick,
      lowerWick,
      wickRatio: upperWick + lowerWick > 0 ? (upperWick / (upperWick + lowerWick)) : 0.5
    }
  });
});

// Separate into correct and incorrect unemployment predictions
const unemploymentCorrect = unemploymentData.filter(d => d.unemploymentCorrect);
const unemploymentWrong = unemploymentData.filter(d => !d.unemploymentCorrect);

console.log('='.repeat(80));
console.log('UNEMPLOYMENT RIGHT vs WRONG - DATA FLAGS ANALYSIS');
console.log('='.repeat(80));
console.log(`\nTotal Occurrences: ${unemploymentData.length}`);
console.log(`Unemployment Correct: ${unemploymentCorrect.length} (${(unemploymentCorrect.length/unemploymentData.length*100).toFixed(1)}%)`);
console.log(`Unemployment Wrong: ${unemploymentWrong.length} (${(unemploymentWrong.length/unemploymentData.length*100).toFixed(1)}%)`);

// Calculate average statistics for each group
function calculateStats(dataArray, label) {
  const stats = {
    unemployment: {
      avgMagnitudePct: 0,
      avgSurprisePct: 0,
      highImpactCount: 0
    },
    nfp: {
      avgMagnitudePct: 0,
      avgSurprisePct: 0,
      highImpactCount: 0,
      correctRate: 0
    },
    earnings: {
      avgMagnitudePct: 0,
      avgSurprisePct: 0,
      highImpactCount: 0,
      correctRate: 0
    },
    candle: {
      avgRange: 0,
      avgBody: 0,
      avgUpperWick: 0,
      avgLowerWick: 0
    }
  };
  
  dataArray.forEach(d => {
    stats.unemployment.avgMagnitudePct += d.events.unemployment.magnitudePct;
    stats.unemployment.avgSurprisePct += d.events.unemployment.surprisePct;
    if (d.events.unemployment.impact === 3) stats.unemployment.highImpactCount++;
    
    stats.nfp.avgMagnitudePct += d.events.nfp.magnitudePct;
    stats.nfp.avgSurprisePct += d.events.nfp.surprisePct;
    if (d.events.nfp.impact === 3) stats.nfp.highImpactCount++;
    if (d.events.nfp.correct) stats.nfp.correctRate++;
    
    stats.earnings.avgMagnitudePct += d.events.earnings.magnitudePct;
    stats.earnings.avgSurprisePct += d.events.earnings.surprisePct;
    if (d.events.earnings.impact === 3) stats.earnings.highImpactCount++;
    if (d.events.earnings.correct) stats.earnings.correctRate++;
    
    stats.candle.avgRange += d.candle.range;
    stats.candle.avgBody += d.candle.body;
    stats.candle.avgUpperWick += d.candle.upperWick;
    stats.candle.avgLowerWick += d.candle.lowerWick;
  });
  
  const count = dataArray.length;
  stats.unemployment.avgMagnitudePct /= count;
  stats.unemployment.avgSurprisePct /= count;
  stats.nfp.avgMagnitudePct /= count;
  stats.nfp.avgSurprisePct /= count;
  stats.nfp.correctRate = (stats.nfp.correctRate / count) * 100;
  stats.earnings.avgMagnitudePct /= count;
  stats.earnings.avgSurprisePct /= count;
  stats.earnings.correctRate = (stats.earnings.correctRate / count) * 100;
  stats.candle.avgRange /= count;
  stats.candle.avgBody /= count;
  stats.candle.avgUpperWick /= count;
  stats.candle.avgLowerWick /= count;
  
  return stats;
}

const correctStats = calculateStats(unemploymentCorrect, 'CORRECT');
const wrongStats = calculateStats(unemploymentWrong, 'WRONG');

console.log('\n' + '='.repeat(80));
console.log('1. FORECAST-PREVIOUS MAGNITUDE (Prediction Input)');
console.log('='.repeat(80));
console.log('\nWhen Unemployment is CORRECT:');
console.log(`  Unemployment Magnitude: ${correctStats.unemployment.avgMagnitudePct.toFixed(2)}%`);
console.log(`  NFP Magnitude: ${correctStats.nfp.avgMagnitudePct.toFixed(2)}%`);
console.log(`  Earnings Magnitude: ${correctStats.earnings.avgMagnitudePct.toFixed(2)}%`);

console.log('\nWhen Unemployment is WRONG:');
console.log(`  Unemployment Magnitude: ${wrongStats.unemployment.avgMagnitudePct.toFixed(2)}%`);
console.log(`  NFP Magnitude: ${wrongStats.nfp.avgMagnitudePct.toFixed(2)}%`);
console.log(`  Earnings Magnitude: ${wrongStats.earnings.avgMagnitudePct.toFixed(2)}%`);

console.log('\n💡 KEY INSIGHT:');
if (correctStats.unemployment.avgMagnitudePct > wrongStats.unemployment.avgMagnitudePct) {
  console.log(`   Unemployment predictions are MORE RELIABLE when magnitude is HIGHER`);
  console.log(`   (${correctStats.unemployment.avgMagnitudePct.toFixed(2)}% vs ${wrongStats.unemployment.avgMagnitudePct.toFixed(2)}%)`);
} else {
  console.log(`   Unemployment predictions are MORE RELIABLE when magnitude is LOWER`);
  console.log(`   (${correctStats.unemployment.avgMagnitudePct.toFixed(2)}% vs ${wrongStats.unemployment.avgMagnitudePct.toFixed(2)}%)`);
}

console.log('\n' + '='.repeat(80));
console.log('2. ACTUAL-FORECAST SURPRISE (Post-Event Data)');
console.log('='.repeat(80));
console.log('\nWhen Unemployment is CORRECT:');
console.log(`  Unemployment Surprise: ${correctStats.unemployment.avgSurprisePct.toFixed(2)}%`);
console.log(`  NFP Surprise: ${correctStats.nfp.avgSurprisePct.toFixed(2)}%`);
console.log(`  Earnings Surprise: ${correctStats.earnings.avgSurprisePct.toFixed(2)}%`);

console.log('\nWhen Unemployment is WRONG:');
console.log(`  Unemployment Surprise: ${wrongStats.unemployment.avgSurprisePct.toFixed(2)}%`);
console.log(`  NFP Surprise: ${wrongStats.nfp.avgSurprisePct.toFixed(2)}%`);
console.log(`  Earnings Surprise: ${wrongStats.earnings.avgSurprisePct.toFixed(2)}%`);

console.log('\n💡 KEY INSIGHT:');
if (wrongStats.unemployment.avgSurprisePct > correctStats.unemployment.avgSurprisePct) {
  console.log(`   When unemployment is WRONG, the surprise is BIGGER`);
  console.log(`   (${wrongStats.unemployment.avgSurprisePct.toFixed(2)}% vs ${correctStats.unemployment.avgSurprisePct.toFixed(2)}%)`);
  console.log(`   Market may have been pricing in the forecast, but actual deviated significantly`);
}

console.log('\n' + '='.repeat(80));
console.log('3. IMPACT LEVELS');
console.log('='.repeat(80));
console.log('\nWhen Unemployment is CORRECT:');
console.log(`  Unemployment High Impact (3): ${correctStats.unemployment.highImpactCount}/${unemploymentCorrect.length}`);
console.log(`  NFP High Impact (3): ${correctStats.nfp.highImpactCount}/${unemploymentCorrect.length}`);
console.log(`  Earnings High Impact (3): ${correctStats.earnings.highImpactCount}/${unemploymentCorrect.length}`);

console.log('\nWhen Unemployment is WRONG:');
console.log(`  Unemployment High Impact (3): ${wrongStats.unemployment.highImpactCount}/${unemploymentWrong.length}`);
console.log(`  NFP High Impact (3): ${wrongStats.nfp.highImpactCount}/${unemploymentWrong.length}`);
console.log(`  Earnings High Impact (3): ${wrongStats.earnings.highImpactCount}/${unemploymentWrong.length}`);

console.log('\n' + '='.repeat(80));
console.log('4. OTHER EVENTS BEHAVIOR');
console.log('='.repeat(80));
console.log('\nWhen Unemployment is CORRECT:');
console.log(`  NFP Also Correct: ${correctStats.nfp.correctRate.toFixed(1)}%`);
console.log(`  Earnings Also Correct: ${correctStats.earnings.correctRate.toFixed(1)}%`);

console.log('\nWhen Unemployment is WRONG:');
console.log(`  NFP Correct (rescue): ${wrongStats.nfp.correctRate.toFixed(1)}%`);
console.log(`  Earnings Correct (rescue): ${wrongStats.earnings.correctRate.toFixed(1)}%`);

console.log('\n💡 KEY INSIGHT:');
console.log(`   When unemployment is right, other events agree ${Math.min(correctStats.nfp.correctRate, correctStats.earnings.correctRate).toFixed(1)}% of the time`);
console.log(`   When unemployment is wrong, other events rescue ${Math.max(wrongStats.nfp.correctRate, wrongStats.earnings.correctRate).toFixed(1)}% of the time`);

console.log('\n' + '='.repeat(80));
console.log('5. MARKET VOLATILITY (First Candle)');
console.log('='.repeat(80));
console.log('\nWhen Unemployment is CORRECT:');
console.log(`  Avg Candle Range: ${correctStats.candle.avgRange.toFixed(2)}`);
console.log(`  Avg Candle Body: ${correctStats.candle.avgBody.toFixed(2)}`);
console.log(`  Avg Upper Wick: ${correctStats.candle.avgUpperWick.toFixed(2)}`);
console.log(`  Avg Lower Wick: ${correctStats.candle.avgLowerWick.toFixed(2)}`);

console.log('\nWhen Unemployment is WRONG:');
console.log(`  Avg Candle Range: ${wrongStats.candle.avgRange.toFixed(2)}`);
console.log(`  Avg Candle Body: ${wrongStats.candle.avgBody.toFixed(2)}`);
console.log(`  Avg Upper Wick: ${wrongStats.candle.avgUpperWick.toFixed(2)}`);
console.log(`  Avg Lower Wick: ${wrongStats.candle.avgLowerWick.toFixed(2)}`);

console.log('\n💡 KEY INSIGHT:');
const correctVolatility = correctStats.candle.avgRange;
const wrongVolatility = wrongStats.candle.avgRange;
if (wrongVolatility > correctVolatility * 1.1) {
  console.log(`   When unemployment is WRONG, market is MORE VOLATILE`);
  console.log(`   (${wrongVolatility.toFixed(2)} vs ${correctVolatility.toFixed(2)})`);
  console.log(`   Higher volatility = mixed signals = harder to predict`);
} else if (correctVolatility > wrongVolatility * 1.1) {
  console.log(`   When unemployment is CORRECT, market is MORE VOLATILE`);
  console.log(`   (${correctVolatility.toFixed(2)} vs ${wrongVolatility.toFixed(2)})`);
  console.log(`   Market moves decisively when unemployment prediction is right`);
} else {
  console.log(`   Volatility is similar in both cases`);
}

console.log('\n' + '='.repeat(80));
console.log('6. DETAILED FLAGS - When Unemployment is WRONG');
console.log('='.repeat(80));

// Find specific patterns in wrong unemployment predictions
const wrongWithLowMagnitude = unemploymentWrong.filter(d => d.events.unemployment.magnitudePct < 5);
const wrongWithHighMagnitude = unemploymentWrong.filter(d => d.events.unemployment.magnitudePct >= 10);
const wrongWithHighSurprise = unemploymentWrong.filter(d => d.events.unemployment.surprisePct >= 10);
const wrongWithConflictingSignals = unemploymentWrong.filter(d => 
  d.events.nfp.prediction !== d.events.unemployment.prediction || 
  d.events.earnings.prediction !== d.events.unemployment.prediction
);

console.log(`\n🚩 FLAG 1: Low Magnitude (<5%) - ${wrongWithLowMagnitude.length}/${unemploymentWrong.length} cases`);
if (wrongWithLowMagnitude.length > 0) {
  console.log('   Small forecast changes may indicate low conviction');
}

console.log(`\n🚩 FLAG 2: High Magnitude (>=10%) - ${wrongWithHighMagnitude.length}/${unemploymentWrong.length} cases`);
if (wrongWithHighMagnitude.length > 0) {
  console.log('   Big forecast changes that still failed - market may have priced in differently');
}

console.log(`\n🚩 FLAG 3: High Surprise (>=10%) - ${wrongWithHighSurprise.length}/${unemploymentWrong.length} cases`);
if (wrongWithHighSurprise.length > 0) {
  console.log('   Large deviation from forecast - actual data surprised the market');
}

console.log(`\n🚩 FLAG 4: Conflicting Signals - ${wrongWithConflictingSignals.length}/${unemploymentWrong.length} cases`);
if (wrongWithConflictingSignals.length > 0) {
  console.log('   NFP or Earnings predicted opposite direction from Unemployment');
  console.log('   Mixed signals = harder to predict which event market will follow');
}

console.log('\n' + '='.repeat(80));
console.log('SUMMARY');
console.log('='.repeat(80));
console.log('\n📊 Key Differentiators:');
console.log(`   1. Magnitude: ${correctStats.unemployment.avgMagnitudePct > wrongStats.unemployment.avgMagnitudePct ? 'Higher when RIGHT' : 'Lower when RIGHT'}`);
console.log(`   2. Surprise: ${wrongStats.unemployment.avgSurprisePct > correctStats.unemployment.avgSurprisePct ? 'Higher when WRONG' : 'Similar'}`);
console.log(`   3. Conflicting Signals: ${(wrongWithConflictingSignals.length/unemploymentWrong.length*100).toFixed(1)}% when WRONG`);
console.log(`   4. Rescue Rate by Others: NFP ${wrongStats.nfp.correctRate.toFixed(1)}%, Earnings ${wrongStats.earnings.correctRate.toFixed(1)}%`);
console.log('\n');
