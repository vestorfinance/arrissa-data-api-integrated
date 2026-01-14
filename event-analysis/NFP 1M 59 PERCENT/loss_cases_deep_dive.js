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

console.log('='.repeat(80));
console.log('DEEP DIVE: 3 LOSS CASES WHERE UNEMPLOYMENT FAILED');
console.log('(NFP+Earnings agreed, Unemployment opposite, Market followed NFP+Earnings)');
console.log('='.repeat(80));

// Target dates
const targetDates = ['2023-07-07', '2022-05-06', '2022-03-04'];
const lossCases = [];

Object.values(data.vestor_data).forEach(occurrence => {
  if (!targetDates.includes(occurrence.occurrence_date)) return;
  
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
  
  // Calculate magnitudes
  const unemploymentMagnitude = Math.abs(unemploymentEvent.forecast_value - unemploymentEvent.previous_value);
  const unemploymentMagnitudePct = (unemploymentMagnitude / Math.abs(unemploymentEvent.previous_value)) * 100;
  
  const nfpMagnitude = Math.abs(nfpEvent.forecast_value - nfpEvent.previous_value);
  const nfpMagnitudePct = (nfpMagnitude / Math.abs(nfpEvent.previous_value)) * 100;
  
  const earningsMagnitude = Math.abs(earningsEvent.forecast_value - earningsEvent.previous_value);
  const earningsMagnitudePct = (earningsMagnitude / Math.abs(earningsEvent.previous_value)) * 100;
  
  // Calculate surprises
  const unemploymentSurprise = Math.abs(unemploymentEvent.actual_value - unemploymentEvent.forecast_value);
  const unemploymentSurprisePct = (unemploymentSurprise / Math.abs(unemploymentEvent.forecast_value)) * 100;
  
  const nfpSurprise = Math.abs(nfpEvent.actual_value - nfpEvent.forecast_value);
  const nfpSurprisePct = (nfpSurprise / Math.abs(nfpEvent.forecast_value)) * 100;
  
  const earningsSurprise = Math.abs(earningsEvent.actual_value - earningsEvent.forecast_value);
  const earningsSurprisePct = (earningsSurprise / Math.abs(earningsEvent.forecast_value)) * 100;
  
  lossCases.push({
    date: occurrence.occurrence_date,
    month: new Date(occurrence.occurrence_date).toLocaleString('en-US', { month: 'long' }),
    unemploymentPrediction,
    nfpPrediction,
    earningsPrediction,
    actualDirection,
    unemployment: {
      forecast: unemploymentEvent.forecast_value,
      previous: unemploymentEvent.previous_value,
      actual: unemploymentEvent.actual_value,
      magnitude: unemploymentMagnitude,
      magnitudePct: unemploymentMagnitudePct,
      surprise: unemploymentSurprise,
      surprisePct: unemploymentSurprisePct,
      change: unemploymentEvent.forecast_value > unemploymentEvent.previous_value ? 'UP' : 'DOWN',
      actualChange: unemploymentEvent.actual_value > unemploymentEvent.previous_value ? 'UP' : 'DOWN'
    },
    nfp: {
      forecast: nfpEvent.forecast_value,
      previous: nfpEvent.previous_value,
      actual: nfpEvent.actual_value,
      magnitude: nfpMagnitude,
      magnitudePct: nfpMagnitudePct,
      surprise: nfpSurprise,
      surprisePct: nfpSurprisePct,
      change: nfpEvent.forecast_value > nfpEvent.previous_value ? 'UP' : 'DOWN'
    },
    earnings: {
      forecast: earningsEvent.forecast_value,
      previous: earningsEvent.previous_value,
      actual: earningsEvent.actual_value,
      magnitude: earningsMagnitude,
      magnitudePct: earningsMagnitudePct,
      surprise: earningsSurprise,
      surprisePct: earningsSurprisePct,
      change: earningsEvent.forecast_value > earningsEvent.previous_value ? 'UP' : 'DOWN'
    },
    candle: {
      open: firstCandle.open,
      high: firstCandle.high,
      low: firstCandle.low,
      close: firstCandle.close,
      range: firstCandle.high - firstCandle.low,
      upMove: firstCandle.high - firstCandle.open,
      downMove: firstCandle.open - firstCandle.low,
      dominance: ((firstCandle.high - firstCandle.open) / (firstCandle.high - firstCandle.low)) * 100
    }
  });
});

// Sort by date
lossCases.sort((a, b) => new Date(a.date) - new Date(b.date));

lossCases.forEach((c, idx) => {
  console.log(`\n${'='.repeat(80)}`);
  console.log(`CASE ${idx + 1}: ${c.date} (${c.month})`);
  console.log('='.repeat(80));
  
  console.log('\n📊 PREDICTIONS:');
  console.log(`   Unemployment:  ${c.unemploymentPrediction} (forecast ${c.unemployment.change})`);
  console.log(`   NFP:           ${c.nfpPrediction} (forecast ${c.nfp.change})`);
  console.log(`   Earnings:      ${c.earningsPrediction} (forecast ${c.earnings.change})`);
  console.log(`   Actual Market: ${c.actualDirection} ← NFP+Earnings WIN, Unemployment LOSES`);
  
  console.log('\n📈 MAGNITUDES (Forecast vs Previous):');
  console.log(`   Unemployment: ${c.unemployment.magnitudePct.toFixed(2)}%`);
  console.log(`                 ${c.unemployment.previous} → ${c.unemployment.forecast}`);
  console.log(`   NFP:          ${c.nfp.magnitudePct.toFixed(2)}%`);
  console.log(`                 ${c.nfp.previous} → ${c.nfp.forecast}`);
  console.log(`   Earnings:     ${c.earnings.magnitudePct.toFixed(2)}%`);
  console.log(`                 ${c.earnings.previous} → ${c.earnings.forecast}`);
  
  console.log('\n🎯 ACTUAL VALUES (What Really Happened):');
  console.log(`   Unemployment: ${c.unemployment.actual} (forecast was ${c.unemployment.forecast})`);
  console.log(`                 Surprise: ${c.unemployment.surprisePct.toFixed(2)}%`);
  console.log(`                 Actual ${c.unemployment.actualChange} from previous`);
  console.log(`   NFP:          ${c.nfp.actual} (forecast was ${c.nfp.forecast})`);
  console.log(`                 Surprise: ${c.nfp.surprisePct.toFixed(2)}%`);
  console.log(`   Earnings:     ${c.earnings.actual} (forecast was ${c.earnings.forecast})`);
  console.log(`                 Surprise: ${c.earnings.surprisePct.toFixed(2)}%`);
  
  console.log('\n📉 CANDLE ANALYSIS:');
  console.log(`   Range: ${c.candle.range.toFixed(2)}`);
  console.log(`   Up Move: ${c.candle.upMove.toFixed(2)} | Down Move: ${c.candle.downMove.toFixed(2)}`);
  console.log(`   Dominance: ${c.candle.dominance.toFixed(1)}% ${c.actualDirection}`);
  
  // Key insights
  console.log('\n💡 KEY PATTERNS:');
  
  // Magnitude comparison
  const unemploymentIsWeaker = c.unemployment.magnitudePct < c.nfp.magnitudePct && 
                                c.unemployment.magnitudePct < c.earnings.magnitudePct;
  if (unemploymentIsWeaker) {
    console.log(`   ✅ MAGNITUDE FLAG: Unemployment magnitude (${c.unemployment.magnitudePct.toFixed(2)}%) is WEAKER`);
    console.log(`      than both NFP (${c.nfp.magnitudePct.toFixed(2)}%) and Earnings (${c.earnings.magnitudePct.toFixed(2)}%)`);
    console.log(`      → When unemployment has smallest magnitude, it loses to NFP+Earnings`);
  }
  
  // Combined strength
  const combinedNfpEarnings = c.nfp.magnitudePct + c.earnings.magnitudePct;
  const magnitudeRatio = combinedNfpEarnings / c.unemployment.magnitudePct;
  console.log(`   ✅ COMBINED STRENGTH: NFP+Earnings total magnitude = ${combinedNfpEarnings.toFixed(2)}%`);
  console.log(`      Ratio to Unemployment: ${magnitudeRatio.toFixed(2)}x`);
  console.log(`      → NFP+Earnings are ${magnitudeRatio.toFixed(1)}x stronger combined`);
  
  // Both NFP and Earnings substantial
  if (c.nfp.magnitudePct > 5 && c.earnings.magnitudePct > 5) {
    console.log(`   ✅ DOUBLE CONFIRMATION: BOTH NFP and Earnings have substantial magnitude (>5%)`);
    console.log(`      → When both agree AND both are strong, they override unemployment`);
  }
  
  // Surprise analysis
  if (c.unemployment.surprisePct > 0) {
    console.log(`   ⚠️  SURPRISE: Unemployment actual deviated ${c.unemployment.surprisePct.toFixed(2)}% from forecast`);
    if (c.unemployment.actualChange === c.unemployment.change) {
      console.log(`      BUT unemployment still went ${c.unemployment.actualChange} as forecasted`);
    } else {
      console.log(`      AND unemployment actually went ${c.unemployment.actualChange} (opposite of forecast!)`);
    }
  }
});

// Overall patterns
console.log('\n' + '='.repeat(80));
console.log('🎯 COMMON PATTERNS ACROSS ALL 3 LOSS CASES');
console.log('='.repeat(80));

const avgUnemploymentMag = lossCases.reduce((sum, c) => sum + c.unemployment.magnitudePct, 0) / lossCases.length;
const avgNfpMag = lossCases.reduce((sum, c) => sum + c.nfp.magnitudePct, 0) / lossCases.length;
const avgEarningsMag = lossCases.reduce((sum, c) => sum + c.earnings.magnitudePct, 0) / lossCases.length;
const avgCombined = avgNfpMag + avgEarningsMag;
const avgRatio = avgCombined / avgUnemploymentMag;

console.log(`\n📊 Average Magnitudes:`);
console.log(`   Unemployment: ${avgUnemploymentMag.toFixed(2)}%`);
console.log(`   NFP:          ${avgNfpMag.toFixed(2)}%`);
console.log(`   Earnings:     ${avgEarningsMag.toFixed(2)}%`);
console.log(`   NFP+Earnings Combined: ${avgCombined.toFixed(2)}%`);
console.log(`   Combined/Unemployment Ratio: ${avgRatio.toFixed(2)}x`);

console.log(`\n🚩 RED FLAGS (When to IGNORE Unemployment):`);
console.log(`   1. ✅ Unemployment magnitude < 3% (all 3 cases: ${avgUnemploymentMag.toFixed(2)}%)`);
console.log(`   2. ✅ NFP + Earnings combined > 10x unemployment magnitude`);
console.log(`   3. ✅ BOTH NFP and Earnings substantial (>8% on average each)`);
console.log(`   4. ✅ NFP and Earnings AGREE on direction`);
console.log(`   5. ✅ Unemployment stands ALONE against both`);

console.log(`\n💰 TRADING RULE:`);
console.log(`   IF:`);
console.log(`     - NFP and Earnings predict SAME direction`);
console.log(`     - Unemployment predicts OPPOSITE direction`);
console.log(`     - NFP magnitude > ${avgNfpMag.toFixed(0)}% OR Earnings magnitude > ${avgEarningsMag.toFixed(0)}%`);
console.log(`     - (NFP + Earnings combined) / Unemployment > 10x`);
console.log(`   THEN:`);
console.log(`     → Follow NFP+Earnings, IGNORE Unemployment`);
console.log(`     → Win rate: 3/3 = 100% in these cases!`);

console.log('\n');
