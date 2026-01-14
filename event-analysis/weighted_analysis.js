const fs = require("fs");
const path = require("path");

// Load data and month psychology
const filePath = path.join(__dirname, "nfp_data.json");
const rawData = fs.readFileSync(filePath, "utf-8");
const data = JSON.parse(rawData).vestor_data;
const { monthProfiles, calculateWeightedPrediction } = require("./month_psychology.js");

// Get prediction logic from evalate_quantify
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

// Statistics tracking
const standardStats = { correct: 0, wrong: 0 };
const weightedStats = { correct: 0, wrong: 0 };
const monthlyComparison = {};

console.log("\n" + "=".repeat(120));
console.log("WEIGHTED PREDICTION ANALYSIS vs STANDARD EQUAL-WEIGHT APPROACH");
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
      total: 0
    };
  }
  
  // Check if all 3 events are present
  const presentEvents = { unemployment: false, nonfarm: false, earnings: false };
  const predictions = { unemployment: null, nonfarm: null, earnings: null };
  
  occ.events.forEach(ev => {
    const id = ev.consistent_event_id || ev.event_id;
    if (id === significantEventIds.unemployment) {
      presentEvents.unemployment = true;
      predictions.unemployment = getXAUUSDPrediction(ev.forecast_value, ev.previous_value, ev.event_name, ev.impact_level);
    } else if (id === significantEventIds.nonfarm) {
      presentEvents.nonfarm = true;
      predictions.nonfarm = getXAUUSDPrediction(ev.forecast_value, ev.previous_value, ev.event_name, ev.impact_level);
    } else if (id === significantEventIds.earnings) {
      presentEvents.earnings = true;
      predictions.earnings = getXAUUSDPrediction(ev.forecast_value, ev.previous_value, ev.event_name, ev.impact_level);
    }
  });
  
  // Only analyze when all 3 are present
  if (!presentEvents.unemployment || !presentEvents.nonfarm || !presentEvents.earnings) {
    continue;
  }
  
  monthlyComparison[monthName].total++;
  
  // STANDARD APPROACH: Equal weight consensus (simple majority)
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
  
  // WEIGHTED APPROACH: Month-specific weights
  const weightedResult = calculateWeightedPrediction(predictions, monthName);
  if (weightedResult && weightedResult.direction === actualDirection) {
    weightedStats.correct++;
    monthlyComparison[monthName].weighted.correct++;
  } else {
    weightedStats.wrong++;
    monthlyComparison[monthName].weighted.wrong++;
  }
}

// Display overall results
console.log("\n=== OVERALL ACCURACY COMPARISON ===\n");
const standardTotal = standardStats.correct + standardStats.wrong;
const weightedTotal = weightedStats.correct + weightedStats.wrong;
const standardAccuracy = standardTotal > 0 ? ((standardStats.correct / standardTotal) * 100).toFixed(2) : 0;
const weightedAccuracy = weightedTotal > 0 ? ((weightedStats.correct / weightedTotal) * 100).toFixed(2) : 0;
const improvement = (weightedAccuracy - standardAccuracy).toFixed(2);

console.log("STANDARD EQUAL-WEIGHT APPROACH:");
console.log(`  Correct: ${standardStats.correct} | Wrong: ${standardStats.wrong} | Total: ${standardTotal}`);
console.log(`  Accuracy: ${standardAccuracy}%`);

console.log("\nWEIGHTED MONTH-PSYCHOLOGY APPROACH:");
console.log(`  Correct: ${weightedStats.correct} | Wrong: ${weightedStats.wrong} | Total: ${weightedTotal}`);
console.log(`  Accuracy: ${weightedAccuracy}%`);

console.log(`\n🎯 IMPROVEMENT: ${improvement > 0 ? '+' : ''}${improvement}%`);

// Display monthly breakdown
console.log("\n" + "=".repeat(120));
console.log("MONTHLY BREAKDOWN - Standard vs Weighted Approach");
console.log("=".repeat(120));
console.log(
  "Month".padEnd(12) +
  "Total".padEnd(8) +
  "Std Correct".padEnd(14) +
  "Std Accuracy".padEnd(16) +
  "Weighted Correct".padEnd(18) +
  "Weighted Accuracy".padEnd(18) +
  "Improvement"
);
console.log("-".repeat(120));

// Sort by month number
const sortedMonths = Object.entries(monthlyComparison).sort((a, b) => a[1].month - b[1].month);

sortedMonths.forEach(([month, stats]) => {
  const stdAcc = stats.total > 0 ? ((stats.standard.correct / stats.total) * 100).toFixed(1) : "0.0";
  const wtAcc = stats.total > 0 ? ((stats.weighted.correct / stats.total) * 100).toFixed(1) : "0.0";
  const diff = (parseFloat(wtAcc) - parseFloat(stdAcc)).toFixed(1);
  const diffStr = diff > 0 ? `+${diff}%` : `${diff}%`;
  
  console.log(
    month.padEnd(12) +
    stats.total.toString().padEnd(8) +
    stats.standard.correct.toString().padEnd(14) +
    `${stdAcc}%`.padEnd(16) +
    stats.weighted.correct.toString().padEnd(18) +
    `${wtAcc}%`.padEnd(18) +
    diffStr
  );
});

console.log("=".repeat(120));

// Highlight 100% months
console.log("\n🌟 100% ACCURACY MONTHS IN WEIGHTED APPROACH:");
sortedMonths.forEach(([month, stats]) => {
  const wtAcc = stats.total > 0 ? ((stats.weighted.correct / stats.total) * 100) : 0;
  if (wtAcc === 100 && stats.total > 0) {
    const profile = monthProfiles[month];
    const dominantKey = profile.dominant_driver.toLowerCase();
    const weightValue = dominantKey === 'nfp' ? profile.weights.nonfarm : 
                        dominantKey === 'mixed' ? 35 : 
                        profile.weights[dominantKey];
    console.log(`  ✓ ${month}: ${stats.total} occurrences - ${profile.dominant_driver} weighted at ${(weightValue * 100).toFixed(0)}%`);
  }
});

console.log("\n📈 BIGGEST IMPROVEMENTS:");
const improvements = sortedMonths
  .map(([month, stats]) => {
    const stdAcc = stats.total > 0 ? ((stats.standard.correct / stats.total) * 100) : 0;
    const wtAcc = stats.total > 0 ? ((stats.weighted.correct / stats.total) * 100) : 0;
    return { month, diff: wtAcc - stdAcc, total: stats.total };
  })
  .filter(m => m.total > 0)
  .sort((a, b) => b.diff - a.diff)
  .slice(0, 5);

improvements.forEach(({ month, diff }) => {
  if (diff > 0) {
    console.log(`  ✓ ${month}: +${diff.toFixed(1)}% improvement`);
  }
});

console.log("\n" + "=".repeat(120));
