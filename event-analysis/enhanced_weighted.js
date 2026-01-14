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

function getActualDirection(firstMinData) {
  if (firstMinData.length === 0) return null;
  const open = firstMinData[0].open;
  const close = firstMinData[0].close;
  const high = firstMinData[0].high;
  const low = firstMinData[0].low;
  const upMove = high - open;
  const downMove = open - low;
  
  return {
    direction: upMove >= downMove ? "UP" : "DOWN",
    magnitude: upMove >= downMove ? upMove : downMove
  };
}

// Significant event IDs
const significantEventIds = {
  unemployment: "JCDYM",
  nonfarm: "VPRWG",
  earnings: "ZBEYU"
};

// ENHANCED WEIGHTED PREDICTION - using ONLY forecast/previous data (no outcome knowledge)
function calculateEnhancedWeightedPrediction(predictions, eventData, month) {
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
    const diff = f - p; // Signed difference
    const absDiff = Math.abs(diff);
    signals.unemployment.magnitude = p !== 0 ? (absDiff / Math.abs(p)) * 100 : 0;
    signals.unemployment.direction = predictions.unemployment === "UP" ? 1 : -1;
    
    // Dynamic weight adjustment based on magnitude
    // Larger forecast changes = more weight for that event
    const magnitudeMultiplier = 1 + (signals.unemployment.magnitude / 10); // +10% weight per 1% change
    signals.unemployment.weight = weights.unemployment * Math.min(magnitudeMultiplier, 2.0); // Cap at 2x
  }
  
  if (eventData.nonfarm && predictions.nonfarm) {
    const f = parseFloat(eventData.nonfarm.forecast || 0);
    const p = parseFloat(eventData.nonfarm.previous || 0);
    const diff = f - p;
    const absDiff = Math.abs(diff);
    signals.nonfarm.magnitude = p !== 0 ? (absDiff / Math.abs(p)) * 100 : 0;
    signals.nonfarm.direction = predictions.nonfarm === "UP" ? 1 : -1;
    
    const magnitudeMultiplier = 1 + (signals.nonfarm.magnitude / 20); // +5% weight per 1% change
    signals.nonfarm.weight = weights.nonfarm * Math.min(magnitudeMultiplier, 2.0);
  }
  
  if (eventData.earnings && predictions.earnings) {
    const f = parseFloat(eventData.earnings.forecast || 0);
    const p = parseFloat(eventData.earnings.previous || 0);
    const diff = f - p;
    const absDiff = Math.abs(diff);
    signals.earnings.magnitude = p !== 0 ? (absDiff / Math.abs(p)) * 100 : 0;
    signals.earnings.direction = predictions.earnings === "UP" ? 1 : -1;
    
    const magnitudeMultiplier = 1 + (signals.earnings.magnitude / 15); // +6.7% weight per 1% change
    signals.earnings.weight = weights.earnings * Math.min(magnitudeMultiplier, 2.0);
  }
  
  // IMPROVEMENT 1: Detect conflict pattern (unemployment vs NFP+Earnings)
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
    
    // RED FLAG CONDITIONS: Weak unemployment, strong NFP+Earnings
    const weakUnemployment = unemploymentMag < 3;
    const strongCombined = combinedMag > 30;
    const ratioFlag = unemploymentMag > 0 && (combinedMag / unemploymentMag) > 10;
    
    if (weakUnemployment && (strongCombined || ratioFlag)) {
      // CONFLICT PENALTY: Reduce unemployment weight drastically
      signals.unemployment.weight *= 0.2; // 80% reduction
      
      // CONFLICT BOOST: Increase NFP+Earnings weights
      signals.nonfarm.weight *= 1.5;
      signals.earnings.weight *= 1.5;
    }
  }
  
  // IMPROVEMENT 2: Double confirmation boost
  // When any two events agree AND both have substantial magnitude
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
    // All three agree - very strong signal
    signals.unemployment.weight *= 1.5;
    signals.nonfarm.weight *= 1.5;
    signals.earnings.weight *= 1.5;
  }
  
  // Calculate weighted score with dynamic weights
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

// Statistics tracking
const standardStats = { correct: 0, wrong: 0 };
const weightedStats = { correct: 0, wrong: 0 };
const enhancedStats = { correct: 0, wrong: 0 };
const monthlyComparison = {};

console.log("\n" + "=".repeat(120));
console.log("ENHANCED WEIGHTED PREDICTION ANALYSIS");
console.log("=".repeat(120));

// Analyze each occurrence
for (const occKey in data) {
  const occ = data[occKey];
  const firstMinData = occ.xauusd_data.slice(0, 1);
  if (firstMinData.length === 0) continue;

  const actualResult = getActualDirection(firstMinData);
  if (!actualResult) continue;
  
  const actualDirection = actualResult.direction;
  const spikeMagnitude = actualResult.magnitude;
  
  // Extract month
  const occDate = occ.occurrence_date || "";
  const monthMatch = occDate.match(/-(\d{2})-/);
  const monthNum = monthMatch ? parseInt(monthMatch[1]) : 0;
  const monthName = monthNum > 0 ? new Date(2000, monthNum - 1).toLocaleString('en', { month: 'short' }) : "Unknown";
  
  // Initialize month stats
  if (!monthlyComparison[monthName]) {
    monthlyComparison[monthName] = {
      month: monthNum,
      standard: { correct: 0, wrong: 0 },
      weighted: { correct: 0, wrong: 0 },
      enhanced: { correct: 0, wrong: 0 },
      total: 0
    };
  }
  
  // Check if all 3 events are present
  const presentEvents = { unemployment: false, nonfarm: false, earnings: false };
  const predictions = { unemployment: null, nonfarm: null, earnings: null };
  const eventData = { unemployment: null, nonfarm: null, earnings: null };
  
  occ.events.forEach(ev => {
    const id = ev.consistent_event_id || ev.event_id;
    if (id === significantEventIds.unemployment) {
      presentEvents.unemployment = true;
      predictions.unemployment = getXAUUSDPrediction(ev.forecast_value, ev.previous_value, ev.event_name, ev.impact_level);
      eventData.unemployment = { forecast: ev.forecast_value, previous: ev.previous_value };
    } else if (id === significantEventIds.nonfarm) {
      presentEvents.nonfarm = true;
      predictions.nonfarm = getXAUUSDPrediction(ev.forecast_value, ev.previous_value, ev.event_name, ev.impact_level);
      eventData.nonfarm = { forecast: ev.forecast_value, previous: ev.previous_value };
    } else if (id === significantEventIds.earnings) {
      presentEvents.earnings = true;
      predictions.earnings = getXAUUSDPrediction(ev.forecast_value, ev.previous_value, ev.event_name, ev.impact_level);
      eventData.earnings = { forecast: ev.forecast_value, previous: ev.previous_value };
    }
  });
  
  // Only analyze when all 3 are present
  if (!presentEvents.unemployment || !presentEvents.nonfarm || !presentEvents.earnings) {
    continue;
  }
  
  monthlyComparison[monthName].total++;
  
  // 1. STANDARD APPROACH: Equal weight consensus
  let upVotes = 0;
  if (predictions.unemployment === "UP") upVotes++;
  if (predictions.nonfarm === "UP") upVotes++;
  if (predictions.earnings === "UP") upVotes++;
  
  const standardPrediction = upVotes >= 2 ? "UP" : "DOWN";
  
  if (standardPrediction === actualDirection) {
    standardStats.correct++;
    monthlyComparison[monthName].standard.correct++;
  } else {
    standardStats.wrong++;
    monthlyComparison[monthName].standard.wrong++;
  }
  
  // 2. BASIC WEIGHTED APPROACH
  const profile = monthProfiles[monthName];
  let weightedScore = 0;
  if (predictions.unemployment) weightedScore += predictions.unemployment === "UP" ? profile.weights.unemployment : -profile.weights.unemployment;
  if (predictions.nonfarm) weightedScore += predictions.nonfarm === "UP" ? profile.weights.nonfarm : -profile.weights.nonfarm;
  if (predictions.earnings) weightedScore += predictions.earnings === "UP" ? profile.weights.earnings : -profile.weights.earnings;
  const weightedPrediction = weightedScore > 0 ? "UP" : "DOWN";
  
  if (weightedPrediction === actualDirection) {
    weightedStats.correct++;
    monthlyComparison[monthName].weighted.correct++;
  } else {
    weightedStats.wrong++;
    monthlyComparison[monthName].weighted.wrong++;
  }
  
  // 3. ENHANCED WEIGHTED APPROACH with magnitude boosting
  const enhancedResult = calculateEnhancedWeightedPrediction(predictions, eventData, monthName);
  
  if (enhancedResult.direction === actualDirection) {
    enhancedStats.correct++;
    monthlyComparison[monthName].enhanced.correct++;
  } else {
    enhancedStats.wrong++;
    monthlyComparison[monthName].enhanced.wrong++;
  }
}

// Display results
console.log("\n" + "=".repeat(120));
console.log("OVERALL ACCURACY COMPARISON");
console.log("=".repeat(120));

function displayStats(name, stats) {
  const total = stats.correct + stats.wrong;
  const accuracy = total > 0 ? ((stats.correct / total) * 100).toFixed(2) : 0;
  
  console.log(`\n${name}:`);
  console.log(`  Correct: ${stats.correct} | Wrong: ${stats.wrong}`);
  console.log(`  Total: ${total}`);
  console.log(`  Accuracy: ${accuracy}%`);
  return parseFloat(accuracy);
}

const stdAcc = displayStats("1. STANDARD EQUAL-WEIGHT", standardStats);
const wtAcc = displayStats("2. BASIC WEIGHTED", weightedStats);
const enhAcc = displayStats("3. IMPROVED WEIGHTED (magnitude + conflict adjustment)", enhancedStats);

console.log("\n" + "=".repeat(120));
console.log("🎯 IMPROVEMENTS:");
console.log(`  Basic Weighted vs Standard: ${(wtAcc - stdAcc).toFixed(2)}%`);
console.log(`  Improved vs Standard: ${(enhAcc - stdAcc).toFixed(2)}%`);
console.log(`  Improved vs Basic Weighted: ${(enhAcc - wtAcc).toFixed(2)}%`);
console.log("\n🔧 CONFLICT ADJUSTMENTS APPLIED:");
console.log("  • Conflict Penalty: 80% reduction when unemployment <3% magnitude conflicts with NFP+Earnings >30% combined");
console.log("  • Double Confirmation: 20% boost when 2 events agree with substantial magnitudes");
console.log("  • Triple Agreement: 50% boost when all 3 events agree");
console.log("=".repeat(120));

// Monthly breakdown
console.log("\n" + "=".repeat(120));
console.log("MONTHLY BREAKDOWN");
console.log("=".repeat(120));
console.log(
  "Month".padEnd(10) +
  "Total".padEnd(8) +
  "Std Acc".padEnd(12) +
  "Wtd Acc".padEnd(12) +
  "Enh Acc".padEnd(12) +
  "Improvement"
);
console.log("-".repeat(120));

const sortedMonths = Object.entries(monthlyComparison).sort((a, b) => a[1].month - b[1].month);

sortedMonths.forEach(([month, stats]) => {
  const stdTotal = stats.standard.correct + stats.standard.wrong;
  const wtTotal = stats.weighted.correct + stats.weighted.wrong;
  const enhTotal = stats.enhanced.correct + stats.enhanced.wrong;
  
  const stdAcc = stdTotal > 0 ? ((stats.standard.correct / stdTotal) * 100).toFixed(0) : "0";
  const wtAcc = wtTotal > 0 ? ((stats.weighted.correct / wtTotal) * 100).toFixed(0) : "0";
  const enhAcc = enhTotal > 0 ? ((stats.enhanced.correct / enhTotal) * 100).toFixed(0) : "0";
  
  const improvement = enhTotal > 0 && stdTotal > 0 
    ? (parseFloat(enhAcc) - parseFloat(stdAcc)).toFixed(0)
    : "0";
  const improvementStr = improvement > 0 ? `+${improvement}%` : `${improvement}%`;
  
  console.log(
    month.padEnd(10) +
    stats.total.toString().padEnd(8) +
    `${stdAcc}%`.padEnd(12) +
    `${wtAcc}%`.padEnd(12) +
    `${enhAcc}%`.padEnd(12) +
    improvementStr
  );
});

console.log("=".repeat(120));

console.log("\n🌟 MONTHS WITH 100% ACCURACY (Enhanced Approach):");
sortedMonths.forEach(([month, stats]) => {
  const enhTotal = stats.enhanced.correct + stats.enhanced.wrong;
  const enhAcc = enhTotal > 0 ? ((stats.enhanced.correct / enhTotal) * 100) : 0;
  if (enhAcc === 100 && enhTotal > 0) {
    console.log(`  ✓ ${month}: ${stats.enhanced.correct}/${enhTotal} correct`);
  }
});

console.log("\n" + "=".repeat(120));
