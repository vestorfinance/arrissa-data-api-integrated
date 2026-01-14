const fs = require('fs');

// Load data
const rawData = fs.readFileSync('nfp_data.json', 'utf8');
const data = JSON.parse(rawData);

// Month psychology data
const monthPsychology = {
  "01": { weights: { unemployment: 0.25, nfp: 0.50, earnings: 0.25 } },
  "02": { weights: { unemployment: 0.40, nfp: 0.35, earnings: 0.25 } },
  "03": { weights: { unemployment: 0.30, nfp: 0.45, earnings: 0.25 } },
  "04": { weights: { unemployment: 0.25, nfp: 0.50, earnings: 0.25 } },
  "05": { weights: { unemployment: 0.20, nfp: 0.60, earnings: 0.20 } },
  "06": { weights: { unemployment: 0.20, nfp: 0.20, earnings: 0.60 } },
  "07": { weights: { unemployment: 0.40, nfp: 0.30, earnings: 0.30 } },
  "08": { weights: { unemployment: 0.35, nfp: 0.35, earnings: 0.30 } },
  "09": { weights: { unemployment: 0.30, nfp: 0.45, earnings: 0.25 } },
  "10": { weights: { unemployment: 0.50, nfp: 0.30, earnings: 0.20 } },
  "11": { weights: { unemployment: 0.35, nfp: 0.35, earnings: 0.30 } },
  "12": { weights: { unemployment: 0.50, nfp: 0.25, earnings: 0.25 } }
};

function isInverseEvent(eventName) {
  return eventName.toLowerCase().includes('unemployment');
}

function getActualBasedPrediction(actual, forecast, eventName) {
  const difference = actual - forecast;
  const isInverse = isInverseEvent(eventName);
  
  if (Math.abs(difference) < 0.01) return null;
  
  if (isInverse) {
    return difference > 0 ? 'UP' : 'DOWN';
  } else {
    return difference > 0 ? 'DOWN' : 'UP';
  }
}

function calculateEnhancedWeightedPrediction(occurrence) {
  const date = new Date(occurrence.occurrence_date);
  const month = (date.getMonth() + 1).toString().padStart(2, '0');
  const profile = monthPsychology[month];
  
  if (!profile) return null;
  
  let scores = { UP: 0, DOWN: 0 };
  let eventData = {};
  
  occurrence.events.forEach(event => {
    const prediction = getActualBasedPrediction(event.actual, event.forecast, event.event_name);
    if (prediction) {
      const magnitude = Math.abs(event.actual - event.forecast);
      eventData[event.event_name] = {
        prediction: prediction,
        magnitude: magnitude
      };
      
      let weight = 1;
      if (event.event_name.includes('Unemployment')) {
        weight = profile.weights.unemployment;
      } else if (event.event_name.includes('Nonfarm')) {
        weight = profile.weights.nfp;
      } else if (event.event_name.includes('Earnings')) {
        weight = profile.weights.earnings;
      }
      
      let adjustedWeight = weight * (1 + magnitude * 0.5);
      scores[prediction] += adjustedWeight;
    }
  });
  
  // Conflict adjustment
  const unemploymentKey = Object.keys(eventData).find(k => k.includes('Unemployment'));
  const nfpKey = Object.keys(eventData).find(k => k.includes('Nonfarm'));
  const earningsKey = Object.keys(eventData).find(k => k.includes('Earnings'));
  
  if (unemploymentKey && nfpKey && earningsKey) {
    const unempData = eventData[unemploymentKey];
    const nfpData = eventData[nfpKey];
    const earningsData = eventData[earningsKey];
    
    if (unempData.prediction !== nfpData.prediction &&
        unempData.prediction !== earningsData.prediction &&
        unempData.magnitude < 0.3 &&
        (nfpData.magnitude + earningsData.magnitude) > 30) {
      const penalty = scores[unempData.prediction] * 0.8;
      scores[unempData.prediction] -= penalty;
    }
  }
  
  return scores.UP > scores.DOWN ? 'UP' : 'DOWN';
}

// Generate signals
let signals = [];
let wins = 0;
let losses = 0;

// Access vestor_data instead of nfp_data
const occurrences = Object.values(data.vestor_data);

occurrences.forEach((occurrence, index) => {
  const prediction = calculateEnhancedWeightedPrediction(occurrence);
  if (!prediction) return;
  
  const actualDirection = occurrence.xauusd_data[0].close > occurrence.xauusd_data[0].open ? 'UP' : 'DOWN';
  const isWin = prediction === actualDirection;
  
  if (isWin) wins++;
  else losses++;
  
  // Use HIGH for UP signals (BUY exit), LOW for DOWN signals (SELL exit)
  const exitPrice = prediction === 'UP' ? occurrence.xauusd_data[0].high : occurrence.xauusd_data[0].low;
  
  signals.push({
    index: index + 1,
    date: occurrence.occurrence_date,
    time: occurrence.occurrence_time,
    direction: prediction,
    entryPrice: occurrence.xauusd_data[0].open,
    exitPrice: exitPrice,
    result: isWin ? 'WIN' : 'LOSS'
  });
});

console.log('\n========================================');
console.log('59% SYSTEM SIGNALS FOR MQL5');
console.log('========================================');
console.log('Total Signals:', signals.length);
console.log('Wins:', wins);
console.log('Losses:', losses);
console.log('Win Rate:', ((wins / (wins + losses)) * 100).toFixed(2) + '%');
console.log('========================================\n');

// Output MQL5 format
signals.forEach(signal => {
  console.log(`   // Signal ${signal.index}: ${signal.date} - ${signal.direction} - ${signal.result}`);
  console.log(`   signalTimes[${signal.index - 1}] = StringToTime("${signal.date} ${signal.time}");`);
  console.log(`   signalDirections[${signal.index - 1}] = "${signal.direction}";`);
  console.log(`   entryPrices[${signal.index - 1}] = ${signal.entryPrice};`);
  console.log(`   exitPrices[${signal.index - 1}] = ${signal.exitPrice};`);
  console.log('');
});

// Save to JSON
fs.writeFileSync('signals_59_percent.json', JSON.stringify(signals, null, 2));
console.log('Signals saved to signals_59_percent.json');
