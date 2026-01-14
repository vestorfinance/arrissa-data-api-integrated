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

// Collect all occurrences
const allCases = [];
let unemploymentAloneCorrect = 0;
let nfpEarningsTogetherCorrect = 0;

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
  
  // Check if NFP and Earnings agree on same direction
  if (nfpPrediction === earningsPrediction && nfpPrediction !== 'NEUTRAL') {
    // Check if Unemployment predicts opposite
    if (unemploymentPrediction !== nfpPrediction && unemploymentPrediction !== 'NEUTRAL') {
      const unemploymentCorrect = unemploymentPrediction === actualDirection;
      const nfpEarningsCorrect = nfpPrediction === actualDirection;
      
      allCases.push({
        date: occurrence.occurrence_date,
        month: new Date(occurrence.occurrence_date).toLocaleString('en-US', { month: 'long' }),
        unemploymentPrediction,
        nfpPrediction,
        earningsPrediction,
        actualDirection,
        unemploymentCorrect,
        nfpEarningsCorrect,
        unemploymentAlone: unemploymentCorrect && !nfpEarningsCorrect,
        unemployment: {
          forecast: unemploymentEvent.forecast_value,
          previous: unemploymentEvent.previous_value,
          actual: unemploymentEvent.actual_value,
          magnitude: Math.abs(unemploymentEvent.forecast_value - unemploymentEvent.previous_value),
          magnitudePct: (Math.abs(unemploymentEvent.forecast_value - unemploymentEvent.previous_value) / Math.abs(unemploymentEvent.previous_value)) * 100
        },
        nfp: {
          forecast: nfpEvent.forecast_value,
          previous: nfpEvent.previous_value,
          actual: nfpEvent.actual_value,
          magnitude: Math.abs(nfpEvent.forecast_value - nfpEvent.previous_value),
          magnitudePct: (Math.abs(nfpEvent.forecast_value - nfpEvent.previous_value) / Math.abs(nfpEvent.previous_value)) * 100
        },
        earnings: {
          forecast: earningsEvent.forecast_value,
          previous: earningsEvent.previous_value,
          actual: earningsEvent.actual_value,
          magnitude: Math.abs(earningsEvent.forecast_value - earningsEvent.previous_value),
          magnitudePct: (Math.abs(earningsEvent.forecast_value - earningsEvent.previous_value) / Math.abs(earningsEvent.previous_value)) * 100
        }
      });
      
      if (unemploymentCorrect && !nfpEarningsCorrect) {
        unemploymentAloneCorrect++;
      } else if (nfpEarningsCorrect && !unemploymentCorrect) {
        nfpEarningsTogetherCorrect++;
      }
    }
  }
});

console.log('='.repeat(80));
console.log('UNEMPLOYMENT vs NFP+EARNINGS (When They Disagree)');
console.log('='.repeat(80));
console.log(`\nTotal Cases Where NFP & Earnings AGREE but Unemployment DISAGREES: ${allCases.length}`);
console.log('\n📊 Results:');
console.log(`  ✅ Unemployment ALONE was correct (NFP+Earnings wrong): ${unemploymentAloneCorrect} (${(unemploymentAloneCorrect/allCases.length*100).toFixed(1)}%)`);
console.log(`  ✅ NFP+Earnings TOGETHER were correct (Unemployment wrong): ${nfpEarningsTogetherCorrect} (${(nfpEarningsTogetherCorrect/allCases.length*100).toFixed(1)}%)`);
console.log(`  ⚖️  All three were wrong: ${allCases.length - unemploymentAloneCorrect - nfpEarningsTogetherCorrect}`);

if (unemploymentAloneCorrect > 0) {
  console.log('\n' + '='.repeat(80));
  console.log('🚨 CASES WHERE UNEMPLOYMENT WAS RIGHT, NFP+EARNINGS WERE WRONG');
  console.log('='.repeat(80));
  console.log('(These are LOSSES if we followed majority/NFP+Earnings)\n');
  
  const unemploymentWins = allCases.filter(c => c.unemploymentAlone);
  unemploymentWins.forEach((c, idx) => {
    console.log(`${idx + 1}. ${c.date} (${c.month})`);
    console.log(`   Unemployment: ${c.unemploymentPrediction} ✅ CORRECT`);
    console.log(`   NFP+Earnings: ${c.nfpPrediction} ❌ WRONG`);
    console.log(`   Actual Market: ${c.actualDirection}`);
    console.log(`   
   Unemployment: Forecast ${c.unemployment.forecast} vs Previous ${c.unemployment.previous}`);
    console.log(`                 Magnitude: ${c.unemployment.magnitudePct.toFixed(2)}%`);
    console.log(`   NFP:          Forecast ${c.nfp.forecast} vs Previous ${c.nfp.previous}`);
    console.log(`                 Magnitude: ${c.nfp.magnitudePct.toFixed(2)}%`);
    console.log(`   Earnings:     Forecast ${c.earnings.forecast} vs Previous ${c.earnings.previous}`);
    console.log(`                 Magnitude: ${c.earnings.magnitudePct.toFixed(2)}%`);
    console.log('');
  });
  
  // Calculate average magnitudes
  const avgUnemploymentMag = unemploymentWins.reduce((sum, c) => sum + c.unemployment.magnitudePct, 0) / unemploymentWins.length;
  const avgNfpMag = unemploymentWins.reduce((sum, c) => sum + c.nfp.magnitudePct, 0) / unemploymentWins.length;
  const avgEarningsMag = unemploymentWins.reduce((sum, c) => sum + c.earnings.magnitudePct, 0) / unemploymentWins.length;
  
  console.log('💡 PATTERN ANALYSIS (When Unemployment Wins Alone):');
  console.log(`   Avg Unemployment Magnitude: ${avgUnemploymentMag.toFixed(2)}%`);
  console.log(`   Avg NFP Magnitude: ${avgNfpMag.toFixed(2)}%`);
  console.log(`   Avg Earnings Magnitude: ${avgEarningsMag.toFixed(2)}%`);
  console.log('');
}

if (nfpEarningsTogetherCorrect > 0) {
  console.log('\n' + '='.repeat(80));
  console.log('✅ CASES WHERE NFP+EARNINGS WERE RIGHT, UNEMPLOYMENT WAS WRONG');
  console.log('='.repeat(80));
  console.log('(These are WINS if we followed majority/NFP+Earnings)\n');
  
  const nfpEarningsWins = allCases.filter(c => c.nfpEarningsCorrect && !c.unemploymentCorrect);
  
  // Show first 5 examples
  nfpEarningsWins.slice(0, 5).forEach((c, idx) => {
    console.log(`${idx + 1}. ${c.date} (${c.month})`);
    console.log(`   NFP+Earnings: ${c.nfpPrediction} ✅ CORRECT`);
    console.log(`   Unemployment: ${c.unemploymentPrediction} ❌ WRONG`);
    console.log(`   Actual Market: ${c.actualDirection}`);
    console.log(`   Unemployment Magnitude: ${c.unemployment.magnitudePct.toFixed(2)}%`);
    console.log(`   NFP Magnitude: ${c.nfp.magnitudePct.toFixed(2)}%`);
    console.log(`   Earnings Magnitude: ${c.earnings.magnitudePct.toFixed(2)}%`);
    console.log('');
  });
  
  if (nfpEarningsWins.length > 5) {
    console.log(`   ... and ${nfpEarningsWins.length - 5} more cases\n`);
  }
  
  // Calculate average magnitudes
  const avgUnemploymentMag = nfpEarningsWins.reduce((sum, c) => sum + c.unemployment.magnitudePct, 0) / nfpEarningsWins.length;
  const avgNfpMag = nfpEarningsWins.reduce((sum, c) => sum + c.nfp.magnitudePct, 0) / nfpEarningsWins.length;
  const avgEarningsMag = nfpEarningsWins.reduce((sum, c) => sum + c.earnings.magnitudePct, 0) / nfpEarningsWins.length;
  
  console.log('💡 PATTERN ANALYSIS (When NFP+Earnings Win Together):');
  console.log(`   Avg Unemployment Magnitude: ${avgUnemploymentMag.toFixed(2)}%`);
  console.log(`   Avg NFP Magnitude: ${avgNfpMag.toFixed(2)}%`);
  console.log(`   Avg Earnings Magnitude: ${avgEarningsMag.toFixed(2)}%`);
  console.log('');
}

console.log('\n' + '='.repeat(80));
console.log('SUMMARY');
console.log('='.repeat(80));
console.log(`\n🎯 When NFP & Earnings AGREE but Unemployment DISAGREES:`);
console.log(`   - Follow NFP+Earnings (majority): ${nfpEarningsTogetherCorrect}/${allCases.length} wins (${(nfpEarningsTogetherCorrect/allCases.length*100).toFixed(1)}%)`);
console.log(`   - Follow Unemployment (lone voice): ${unemploymentAloneCorrect}/${allCases.length} wins (${(unemploymentAloneCorrect/allCases.length*100).toFixed(1)}%)`);

if (nfpEarningsTogetherCorrect > unemploymentAloneCorrect) {
  console.log(`\n✅ MAJORITY WINS! Better to follow NFP+Earnings when they agree`);
  console.log(`   Win rate improves by ${((nfpEarningsTogetherCorrect - unemploymentAloneCorrect) / allCases.length * 100).toFixed(1)}% when following the pair`);
} else if (unemploymentAloneCorrect > nfpEarningsTogetherCorrect) {
  console.log(`\n⚠️  LONE VOICE WINS! Unemployment is more reliable even when alone`);
  console.log(`   But this is RARE - only ${unemploymentAloneCorrect} cases`);
} else {
  console.log(`\n⚖️  TIE! No clear winner`);
}

console.log('\n');
