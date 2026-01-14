const fs = require("fs");
const path = require("path");

// ---------------------------
// Load JSON data and month psychology
// ---------------------------
const filePath = path.join(__dirname, "nfp_data.json");
const rawData = fs.readFileSync(filePath, "utf-8");
const data = JSON.parse(rawData).vestor_data;

const { monthProfiles, calculateWeightedPrediction } = require("./month_psychology.js");

// ---------------------------
// Determine event type: USD-positive or USD-negative
// ---------------------------
function isInverseEvent(eventName) {
  const inverse = [
    "unemployment", "jobless claims", "continuing jobless", "initial jobless",
    "participation rate", "u6 unemployment", "trade balance", "trade deficit",
    "imports"
  ];
  const nameLower = eventName.toLowerCase();
  return inverse.some(keyword => nameLower.includes(keyword));
}

// ---------------------------
// Get XAUUSD direction prediction based on USD event
// USD strengthens = XAUUSD DOWN
// USD weakens = XAUUSD UP
// ---------------------------
function getXAUUSDPrediction(forecast, previous, eventName, impactLevel) {
  if (forecast === null || previous === null) return "DOWN"; // fallback
  const f = parseFloat(forecast);
  const p = parseFloat(previous);

  const isInverse = isInverseEvent(eventName);
  
  // For inline events (jobs, earnings, sales, etc.):
  // forecast > previous = USD strength = XAUUSD DOWN
  // forecast < previous = USD weakness = XAUUSD UP
  
  // For inverse events (unemployment, jobless claims):
  // forecast > previous = USD weakness = XAUUSD UP
  // forecast < previous = USD strength = XAUUSD DOWN
  
  if (f > p) {
    return isInverse ? "UP" : "DOWN";
  } else if (f < p) {
    return isInverse ? "DOWN" : "UP";
  } else {
    // When forecast equals previous, use impact level as tiebreaker
    // High impact = assume bullish USD = XAUUSD DOWN
    return (impactLevel === "High") ? "DOWN" : "UP";
  }
}

// ---------------------------
// Determine actual XAUUSD spike direction (first minute)
// ---------------------------
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

// ---------------------------
// Analyze first candle movement
// ---------------------------
function analyzeFirstCandle(xauusdData) {
  if (xauusdData.length < 1) return null;
  
  const candle = xauusdData[0];
  const candleMove = candle.close - candle.open;
  const candleDirection = candleMove >= 0 ? "UP" : "DOWN";
  const bodySize = Math.abs(candleMove);
  const upperWick = candle.high - Math.max(candle.open, candle.close);
  const lowerWick = Math.min(candle.open, candle.close) - candle.low;
  const totalRange = candle.high - candle.low;
  
  return {
    minute: 1,
    open: candle.open,
    high: candle.high,
    low: candle.low,
    close: candle.close,
    direction: candleDirection,
    bodySize: parseFloat(bodySize.toFixed(2)),
    upperWick: parseFloat(upperWick.toFixed(2)),
    lowerWick: parseFloat(lowerWick.toFixed(2)),
    totalRange: parseFloat(totalRange.toFixed(2)),
    move: parseFloat(candleMove.toFixed(2)),
    netMove: parseFloat(Math.abs(candleMove).toFixed(2))
  };
}

// ---------------------------
// Initialize event stats
// ---------------------------
const eventStats = {};

// ---------------------------
// Main processing
// ---------------------------
let occurrenceCount = 0;

for (const occKey in data) {
  occurrenceCount++;
  
  const occ = data[occKey];

  // Use first minute only
  const firstMinData = occ.xauusd_data.slice(0, 1);
  if (firstMinData.length === 0) continue;

  const actualResult = getActualDirection(firstMinData);
  if (!actualResult) continue;
  
  const actualDirection = actualResult.direction;
  const spikeMagnitude = actualResult.magnitude;
  
  // Count co-occurring events
  const numEvents = occ.events.length;

  // Process each event
  occ.events.forEach(ev => {
    const id = ev.consistent_event_id || ev.event_id;
    
    if (!eventStats[id]) {
      eventStats[id] = {
        eventName: ev.event_name,
        correctUp: 0,
        incorrectUp: 0,
        correctDown: 0,
        incorrectDown: 0,
        correctUpDiffs: [],
        incorrectUpDiffs: [],
        correctDownDiffs: [],
        incorrectDownDiffs: [],
        correctSpikes: [],
        incorrectSpikes: [],
        correctCoEvents: [],
        incorrectCoEvents: [],
        monthlyAccuracy: {}, // Track accuracy by month
        impactLevels: {
          High: { correct: 0, incorrect: 0 },
          Moderate: { correct: 0, incorrect: 0 },
          Low: { correct: 0, incorrect: 0 }
        }
      };
    }

    // Get XAUUSD prediction based on event forecast/previous
    const prediction = getXAUUSDPrediction(ev.forecast_value, ev.previous_value, ev.event_name, ev.impact_level);

    // Calculate forecast vs previous difference (signed, not absolute)
    const f = parseFloat(ev.forecast_value || 0);
    const p = parseFloat(ev.previous_value || 0);
    const diff = f - p;
    
    const impactLevel = ev.impact_level || "Low";
    
    // Extract month from occurrence date
    const occDate = occ.occurrence_date || "";
    const monthMatch = occDate.match(/-(\d{2})-/);
    const month = monthMatch ? parseInt(monthMatch[1]) : 0;
    const monthName = month > 0 ? new Date(2000, month - 1).toLocaleString('en', { month: 'short' }) : "Unknown";
    
    // Initialize month stats if needed
    if (!eventStats[id].monthlyAccuracy[monthName]) {
      eventStats[id].monthlyAccuracy[monthName] = { correct: 0, incorrect: 0, month: month };
    }

    const isCorrect = prediction === actualDirection;
    
    // Track monthly accuracy
    if (isCorrect) {
      eventStats[id].monthlyAccuracy[monthName].correct++;
    } else {
      eventStats[id].monthlyAccuracy[monthName].incorrect++;
    }
    
    if (prediction === actualDirection) {
        // Correct prediction
        eventStats[id].correctSpikes.push(spikeMagnitude);
        eventStats[id].correctCoEvents.push(numEvents);
        eventStats[id].impactLevels[impactLevel].correct += 1;
        
        if (prediction === "UP") {
          eventStats[id].correctUp += 1;
          eventStats[id].correctUpDiffs.push(diff);
        } else {
          eventStats[id].correctDown += 1;
          eventStats[id].correctDownDiffs.push(diff);
        }
      } else {
        // Incorrect prediction
        eventStats[id].incorrectSpikes.push(spikeMagnitude);
        eventStats[id].incorrectCoEvents.push(numEvents);
        eventStats[id].impactLevels[impactLevel].incorrect += 1;
        
        if (prediction === "UP") {
          eventStats[id].incorrectUp += 1;
          eventStats[id].incorrectUpDiffs.push(diff);
        } else {
          eventStats[id].incorrectDown += 1;
          eventStats[id].incorrectDownDiffs.push(diff);
        }
      }
  });
}

// ---------------------------
// Calculate final Event Scores
// ---------------------------
const results = [];
for (const eventId in eventStats) {
  const e = eventStats[eventId];
  const total = e.correctUp + e.incorrectUp + e.correctDown + e.incorrectDown;
  const correct = e.correctUp + e.correctDown;
  const incorrect = e.incorrectUp + e.incorrectDown;
  const accuracy = total > 0 ? ((correct / total) * 100).toFixed(2) : 0;

  // Calculate average differences for each category
  const avgCorrectUpDiff = e.correctUpDiffs.length > 0 
    ? (e.correctUpDiffs.reduce((a, b) => a + b, 0) / e.correctUpDiffs.length).toFixed(4)
    : "N/A";
  
  const avgIncorrectUpDiff = e.incorrectUpDiffs.length > 0
    ? (e.incorrectUpDiffs.reduce((a, b) => a + b, 0) / e.incorrectUpDiffs.length).toFixed(4)
    : "N/A";

  const avgCorrectDownDiff = e.correctDownDiffs.length > 0 
    ? (e.correctDownDiffs.reduce((a, b) => a + b, 0) / e.correctDownDiffs.length).toFixed(4)
    : "N/A";
  
  const avgIncorrectDownDiff = e.incorrectDownDiffs.length > 0
    ? (e.incorrectDownDiffs.reduce((a, b) => a + b, 0) / e.incorrectDownDiffs.length).toFixed(4)
    : "N/A";

  // Calculate spike magnitudes
  const avgCorrectSpike = e.correctSpikes.length > 0
    ? (e.correctSpikes.reduce((a, b) => a + b, 0) / e.correctSpikes.length).toFixed(2)
    : "N/A";

  const avgIncorrectSpike = e.incorrectSpikes.length > 0
    ? (e.incorrectSpikes.reduce((a, b) => a + b, 0) / e.incorrectSpikes.length).toFixed(2)
    : "N/A";

  // Calculate co-occurring events
  const avgCorrectCoEvents = e.correctCoEvents.length > 0
    ? (e.correctCoEvents.reduce((a, b) => a + b, 0) / e.correctCoEvents.length).toFixed(1)
    : "N/A";

  const avgIncorrectCoEvents = e.incorrectCoEvents.length > 0
    ? (e.incorrectCoEvents.reduce((a, b) => a + b, 0) / e.incorrectCoEvents.length).toFixed(1)
    : "N/A";

  // Impact level breakdown
  const impactBreakdown = `H:${e.impactLevels.High.correct}/${e.impactLevels.High.incorrect} M:${e.impactLevels.Moderate.correct}/${e.impactLevels.Moderate.incorrect} L:${e.impactLevels.Low.correct}/${e.impactLevels.Low.incorrect}`;

  results.push({
    eventName: e.eventName,
    correct: correct,
    incorrect: incorrect,
    total: total,
    accuracy: parseFloat(accuracy),
    avgCorrectUpDiff: avgCorrectUpDiff,
    avgIncorrectUpDiff: avgIncorrectUpDiff,
    avgCorrectDownDiff: avgCorrectDownDiff,
    avgIncorrectDownDiff: avgIncorrectDownDiff,
    avgCorrectSpike: avgCorrectSpike,
    avgIncorrectSpike: avgIncorrectSpike,
    avgCorrectCoEvents: avgCorrectCoEvents,
    avgIncorrectCoEvents: avgIncorrectCoEvents,
    impactBreakdown: impactBreakdown
  });
}

// ---------------------------
// Filter for statistically significant events (minimum 5 occurrences)
// ---------------------------
const significantResults = results.filter(r => r.total >= 5);
const opportunisticResults = results.filter(r => r.total < 5);

// Sort by accuracy descending
significantResults.sort((a, b) => b.accuracy - a.accuracy);
opportunisticResults.sort((a, b) => b.accuracy - a.accuracy);

// ---------------------------
// Analyze alignment of significant events
// ---------------------------
const significantEventIds = {
  unemployment: "JCDYM",    // Unemployment Rate
  nonfarm: "VPRWG",          // Nonfarm Payrolls
  earnings: "ZBEYU"          // Average Hourly Earnings (MoM)
};

// Track alignment scenarios
const alignmentStats = {
  all3Correct: { count: 0, spikes: [], total: 0 },
  twoCorrect: { count: 0, spikes: [], total: 0 },
  oneCorrect: { count: 0, spikes: [], total: 0 },
  noneCorrect: { count: 0, spikes: [], total: 0 }
};

// Track consensus predictions
const consensusStats = {
  all3AgreeUp: { correct: 0, incorrect: 0, spikes: [] },
  all3AgreeDown: { correct: 0, incorrect: 0, spikes: [] },
  mixed: { count: 0, spikes: [] }
};

// Re-analyze occurrences to check alignment
for (const occKey in data) {
  const occ = data[occKey];
  const firstMinData = occ.xauusd_data.slice(0, 1);
  if (firstMinData.length === 0) continue;

  const actualResult = getActualDirection(firstMinData);
  if (!actualResult) continue;
  
  const actualDirection = actualResult.direction;
  const spikeMagnitude = actualResult.magnitude;

  // Check if all 3 significant events are present
  const presentEvents = {
    unemployment: false,
    nonfarm: false,
    earnings: false
  };
  
  const predictions = {
    unemployment: null,
    nonfarm: null,
    earnings: null
  };

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

  // Only analyze occurrences where all 3 are present
  if (presentEvents.unemployment && presentEvents.nonfarm && presentEvents.earnings) {
    let correctCount = 0;
    if (predictions.unemployment === actualDirection) correctCount++;
    if (predictions.nonfarm === actualDirection) correctCount++;
    if (predictions.earnings === actualDirection) correctCount++;

    // Check consensus
    if (predictions.unemployment === predictions.nonfarm && predictions.nonfarm === predictions.earnings) {
      // All 3 agree
      const consensusDirection = predictions.unemployment;
      if (consensusDirection === actualDirection) {
        if (consensusDirection === "UP") {
          consensusStats.all3AgreeUp.correct++;
          consensusStats.all3AgreeUp.spikes.push(spikeMagnitude);
        } else {
          consensusStats.all3AgreeDown.correct++;
          consensusStats.all3AgreeDown.spikes.push(spikeMagnitude);
        }
      } else {
        if (consensusDirection === "UP") {
          consensusStats.all3AgreeUp.incorrect++;
          consensusStats.all3AgreeUp.spikes.push(spikeMagnitude);
        } else {
          consensusStats.all3AgreeDown.incorrect++;
          consensusStats.all3AgreeDown.spikes.push(spikeMagnitude);
        }
      }
    } else {
      // Mixed predictions
      consensusStats.mixed.count++;
      consensusStats.mixed.spikes.push(spikeMagnitude);
    }

    if (correctCount === 3) {
      alignmentStats.all3Correct.count++;
      alignmentStats.all3Correct.spikes.push(spikeMagnitude);
      alignmentStats.all3Correct.total++;
    } else if (correctCount === 2) {
      alignmentStats.twoCorrect.count++;
      alignmentStats.twoCorrect.spikes.push(spikeMagnitude);
      alignmentStats.twoCorrect.total++;
    } else if (correctCount === 1) {
      alignmentStats.oneCorrect.count++;
      alignmentStats.oneCorrect.spikes.push(spikeMagnitude);
      alignmentStats.oneCorrect.total++;
    } else {
      alignmentStats.noneCorrect.count++;
      alignmentStats.noneCorrect.spikes.push(spikeMagnitude);
      alignmentStats.noneCorrect.total++;
    }
  }
}

// ---------------------------
// Display results in readable format
// ---------------------------
console.log("\n" + "=".repeat(200));
console.log("STATISTICALLY SIGNIFICANT EVENTS (5+ OCCURRENCES)");
console.log("=".repeat(200));
console.log(
  "Event Name".padEnd(50) +
  "Correct".padEnd(10) +
  "Wrong".padEnd(10) +
  "Total".padEnd(10) +
  "Accuracy".padEnd(12) +
  "UP✓Diff".padEnd(13) +
  "UP✗Diff".padEnd(13) +
  "DOWN✓Diff".padEnd(13) +
  "DOWN✗Diff".padEnd(13) +
  "Spike✓".padEnd(10) +
  "Spike✗".padEnd(10) +
  "CoEvt✓".padEnd(10) +
  "CoEvt✗".padEnd(10) +
  "Impact(✓/✗)"
);
console.log("-".repeat(200));

significantResults.forEach(r => {
  console.log(
    r.eventName.padEnd(50) +
    r.correct.toString().padEnd(10) +
    r.incorrect.toString().padEnd(10) +
    r.total.toString().padEnd(10) +
    (r.accuracy + "%").padEnd(12) +
    r.avgCorrectUpDiff.toString().padEnd(13) +
    r.avgIncorrectUpDiff.toString().padEnd(13) +
    r.avgCorrectDownDiff.toString().padEnd(13) +
    r.avgIncorrectDownDiff.toString().padEnd(13) +
    r.avgCorrectSpike.toString().padEnd(10) +
    r.avgIncorrectSpike.toString().padEnd(10) +
    r.avgCorrectCoEvents.toString().padEnd(10) +
    r.avgIncorrectCoEvents.toString().padEnd(10) +
    r.impactBreakdown
  );
});

console.log("=".repeat(200));

console.log("\n" + "=".repeat(200));
console.log("OPPORTUNISTIC EVENTS (<5 OCCURRENCES) - STATISTICALLY UNRELIABLE");
console.log("=".repeat(200));
console.log(
  "Event Name".padEnd(50) +
  "Correct".padEnd(10) +
  "Wrong".padEnd(10) +
  "Total".padEnd(10) +
  "Accuracy".padEnd(12) +
  "UP✓Diff".padEnd(13) +
  "UP✗Diff".padEnd(13) +
  "DOWN✓Diff".padEnd(13) +
  "DOWN✗Diff".padEnd(13) +
  "Spike✓".padEnd(10) +
  "Spike✗".padEnd(10) +
  "CoEvt✓".padEnd(10) +
  "CoEvt✗".padEnd(10) +
  "Impact(✓/✗)"
);
console.log("-".repeat(200));

opportunisticResults.forEach(r => {
  console.log(
    r.eventName.padEnd(50) +
    r.correct.toString().padEnd(10) +
    r.incorrect.toString().padEnd(10) +
    r.total.toString().padEnd(10) +
    (r.accuracy + "%").padEnd(12) +
    r.avgCorrectUpDiff.toString().padEnd(13) +
    r.avgIncorrectUpDiff.toString().padEnd(13) +
    r.avgCorrectDownDiff.toString().padEnd(13) +
    r.avgIncorrectDownDiff.toString().padEnd(13) +
    r.avgCorrectSpike.toString().padEnd(10) +
    r.avgIncorrectSpike.toString().padEnd(10) +
    r.avgCorrectCoEvents.toString().padEnd(10) +
    r.avgIncorrectCoEvents.toString().padEnd(10) +
    r.impactBreakdown
  );
});

console.log("=".repeat(200));

// ---------------------------
// Display Monthly Accuracy Correlation
// ---------------------------
console.log("\n" + "=".repeat(120));
console.log("MONTHLY ACCURACY CORRELATION - Prediction Performance by Month");
console.log("=".repeat(120));

significantResults.forEach(result => {
  const eventId = Object.keys(eventStats).find(id => eventStats[id].eventName === result.eventName);
  if (!eventId) return;
  
  const monthlyData = eventStats[eventId].monthlyAccuracy;
  const months = Object.keys(monthlyData).sort((a, b) => monthlyData[a].month - monthlyData[b].month);
  
  if (months.length === 0) return;
  
  console.log(`\n${result.eventName}:`);
  console.log(
    "  Month".padEnd(12) +
    "Correct".padEnd(10) +
    "Wrong".padEnd(10) +
    "Total".padEnd(10) +
    "Accuracy"
  );
  console.log("  " + "-".repeat(50));
  
  months.forEach(month => {
    const data = monthlyData[month];
    const total = data.correct + data.incorrect;
    const accuracy = total > 0 ? ((data.correct / total) * 100).toFixed(1) + "%" : "N/A";
    
    console.log(
      "  " + month.padEnd(10) +
      data.correct.toString().padEnd(10) +
      data.incorrect.toString().padEnd(10) +
      total.toString().padEnd(10) +
      accuracy
    );
  });
});

console.log("\n" + "=".repeat(120));

// Display alignment analysis
console.log("\n" + "=".repeat(120));
console.log("ALIGNMENT ANALYSIS - WHEN ALL 3 SIGNIFICANT EVENTS OCCUR TOGETHER");
console.log("=".repeat(120));
console.log(
  "Scenario".padEnd(30) +
  "Occurrences".padEnd(15) +
  "Accuracy".padEnd(15) +
  "Avg Spike (pips)".padEnd(20) +
  "Min Spike".padEnd(15) +
  "Max Spike"
);
console.log("-".repeat(120));

const scenarios = [
  { name: "All 3 Correct", data: alignmentStats.all3Correct },
  { name: "2 of 3 Correct", data: alignmentStats.twoCorrect },
  { name: "1 of 3 Correct", data: alignmentStats.oneCorrect },
  { name: "None Correct", data: alignmentStats.noneCorrect }
];

const totalOccurrences = alignmentStats.all3Correct.total + alignmentStats.twoCorrect.total + 
                         alignmentStats.oneCorrect.total + alignmentStats.noneCorrect.total;

scenarios.forEach(scenario => {
  const accuracy = totalOccurrences > 0
    ? ((scenario.data.count / totalOccurrences) * 100).toFixed(2) + "%"
    : "N/A";

  const avgSpike = scenario.data.spikes.length > 0
    ? (scenario.data.spikes.reduce((a, b) => a + b, 0) / scenario.data.spikes.length).toFixed(2)
    : "N/A";
  
  const minSpike = scenario.data.spikes.length > 0
    ? Math.min(...scenario.data.spikes).toFixed(2)
    : "N/A";
  
  const maxSpike = scenario.data.spikes.length > 0
    ? Math.max(...scenario.data.spikes).toFixed(2)
    : "N/A";

  console.log(
    scenario.name.padEnd(30) +
    scenario.data.count.toString().padEnd(15) +
    accuracy.toString().padEnd(15) +
    avgSpike.toString().padEnd(20) +
    minSpike.toString().padEnd(15) +
    maxSpike.toString()
  );
});

console.log("-".repeat(120));
console.log("TOTAL".padEnd(30) + totalOccurrences.toString());
console.log("=".repeat(120));

// Display consensus analysis
console.log("\n" + "=".repeat(120));
console.log("CONSENSUS PREDICTION ACCURACY - WHEN ALL 3 EVENTS AGREE ON DIRECTION");
console.log("=".repeat(120));
console.log(
  "Consensus Direction".padEnd(30) +
  "Correct".padEnd(15) +
  "Wrong".padEnd(15) +
  "Total".padEnd(15) +
  "Accuracy".padEnd(15) +
  "Avg Spike"
);
console.log("-".repeat(120));

const upTotal = consensusStats.all3AgreeUp.correct + consensusStats.all3AgreeUp.incorrect;
const downTotal = consensusStats.all3AgreeDown.correct + consensusStats.all3AgreeDown.incorrect;
const consensusTotal = upTotal + downTotal;

const upAccuracy = upTotal > 0 ? ((consensusStats.all3AgreeUp.correct / upTotal) * 100).toFixed(2) + "%" : "N/A";
const downAccuracy = downTotal > 0 ? ((consensusStats.all3AgreeDown.correct / downTotal) * 100).toFixed(2) + "%" : "N/A";
const overallAccuracy = consensusTotal > 0 ? (((consensusStats.all3AgreeUp.correct + consensusStats.all3AgreeDown.correct) / consensusTotal) * 100).toFixed(2) + "%" : "N/A";

const upAvgSpike = consensusStats.all3AgreeUp.spikes.length > 0
  ? (consensusStats.all3AgreeUp.spikes.reduce((a, b) => a + b, 0) / consensusStats.all3AgreeUp.spikes.length).toFixed(2)
  : "N/A";

const downAvgSpike = consensusStats.all3AgreeDown.spikes.length > 0
  ? (consensusStats.all3AgreeDown.spikes.reduce((a, b) => a + b, 0) / consensusStats.all3AgreeDown.spikes.length).toFixed(2)
  : "N/A";

const mixedAvgSpike = consensusStats.mixed.spikes.length > 0
  ? (consensusStats.mixed.spikes.reduce((a, b) => a + b, 0) / consensusStats.mixed.spikes.length).toFixed(2)
  : "N/A";

console.log(
  "All 3 Predict UP".padEnd(30) +
  consensusStats.all3AgreeUp.correct.toString().padEnd(15) +
  consensusStats.all3AgreeUp.incorrect.toString().padEnd(15) +
  upTotal.toString().padEnd(15) +
  upAccuracy.toString().padEnd(15) +
  upAvgSpike.toString()
);

console.log(
  "All 3 Predict DOWN".padEnd(30) +
  consensusStats.all3AgreeDown.correct.toString().padEnd(15) +
  consensusStats.all3AgreeDown.incorrect.toString().padEnd(15) +
  downTotal.toString().padEnd(15) +
  downAccuracy.toString().padEnd(15) +
  downAvgSpike.toString()
);

console.log("-".repeat(120));

console.log(
  "OVERALL (All Agree)".padEnd(30) +
  (consensusStats.all3AgreeUp.correct + consensusStats.all3AgreeDown.correct).toString().padEnd(15) +
  (consensusStats.all3AgreeUp.incorrect + consensusStats.all3AgreeDown.incorrect).toString().padEnd(15) +
  consensusTotal.toString().padEnd(15) +
  overallAccuracy.toString().padEnd(15)
);

console.log(
  "Mixed Predictions".padEnd(30) +
  "N/A".padEnd(15) +
  "N/A".padEnd(15) +
  consensusStats.mixed.count.toString().padEnd(15) +
  "N/A".padEnd(15) +
  mixedAvgSpike.toString()
);

console.log("=".repeat(120));

// Display detailed candle analysis for first few occurrences
console.log("\n" + "=".repeat(150));
console.log("DETAILED FIRST CANDLE ANALYSIS (First 10 Occurrences)");
console.log("=".repeat(150));

let occCount = 0;
for (const occKey in data) {
  if (occCount >= 10) break;
  
  const occ = data[occKey];
  const firstCandle = analyzeFirstCandle(occ.xauusd_data);
  
  if (!firstCandle) continue;
  
  console.log("\n" + "-".repeat(150));
  console.log(`Occurrence: ${occKey} | Date: ${occ.occurrence_date} | Time: ${occ.occurrence_time}`);
  console.log("-".repeat(150));
  
  console.log(
    "Min".padEnd(8) +
    "Open".padEnd(12) +
    "High".padEnd(12) +
    "Low".padEnd(12) +
    "Close".padEnd(12) +
    "Direction".padEnd(12) +
    "Body".padEnd(10) +
    "UpWick".padEnd(10) +
    "DnWick".padEnd(10) +
    "Range"
  );
  
  console.log(
    firstCandle.minute.toString().padEnd(8) +
    firstCandle.open.toFixed(2).padEnd(12) +
    firstCandle.high.toFixed(2).padEnd(12) +
    firstCandle.low.toFixed(2).padEnd(12) +
    firstCandle.close.toFixed(2).padEnd(12) +
    firstCandle.direction.padEnd(12) +
    firstCandle.bodySize.toFixed(2).padEnd(10) +
    firstCandle.upperWick.toFixed(2).padEnd(10) +
    firstCandle.lowerWick.toFixed(2).padEnd(10) +
    firstCandle.totalRange.toFixed(2)
  );
  
  console.log("\n" + `First Candle Movement: ${firstCandle.direction} | Net Move: ${firstCandle.netMove} pips | High: ${firstCandle.high.toFixed(2)} | Low: ${firstCandle.low.toFixed(2)}`);
  
  // Show event predictions
  console.log("\nEvent Predictions:");
  let foundSignificantEvents = false;
  occ.events.forEach(ev => {
    const id = ev.consistent_event_id || ev.event_id;
    // Check if this is one of the 3 significant events
    if (id === significantEventIds.unemployment) {
      const pred = getXAUUSDPrediction(ev.forecast_value, ev.previous_value, ev.event_name, ev.impact_level);
      console.log(`  Unemployment Rate: ${pred} (Forecast: ${ev.forecast_value}, Previous: ${ev.previous_value})`);
      foundSignificantEvents = true;
    } else if (id === significantEventIds.nonfarm) {
      const pred = getXAUUSDPrediction(ev.forecast_value, ev.previous_value, ev.event_name, ev.impact_level);
      console.log(`  Nonfarm Payrolls: ${pred} (Forecast: ${ev.forecast_value}, Previous: ${ev.previous_value})`);
      foundSignificantEvents = true;
    } else if (id === significantEventIds.earnings) {
      const pred = getXAUUSDPrediction(ev.forecast_value, ev.previous_value, ev.event_name, ev.impact_level);
      console.log(`  Avg Hourly Earnings: ${pred} (Forecast: ${ev.forecast_value}, Previous: ${ev.previous_value})`);
      foundSignificantEvents = true;
    }
  });
  
  if (!foundSignificantEvents) {
    console.log("  None of the 3 significant events present in this occurrence");
  }
  
  occCount++;
}

console.log("\n" + "=".repeat(150));
