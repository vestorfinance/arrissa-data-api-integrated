const fs = require("fs");
const path = require("path");

// Load data
const filePath = path.join(__dirname, "nfp_data.json");
const rawData = fs.readFileSync(filePath, "utf-8");
const data = JSON.parse(rawData).vestor_data;

// Prediction logic
function isInverseEvent(eventName) {
  const inverse = ["unemployment", "jobless claims", "continuing jobless", "initial jobless"];
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

// Analyze each occurrence
const detailedAnalysis = [];

for (const occKey in data) {
  const occ = data[occKey];
  const firstMinData = occ.xauusd_data.slice(0, 1);
  if (firstMinData.length === 0) continue;

  const actualResult = getActualDirection(firstMinData);
  if (!actualResult) continue;
  
  const actualDirection = actualResult.direction;
  
  // Extract month
  const occDate = occ.occurrence_date || "";
  const monthMatch = occDate.match(/-(\d{2})-/);
  const monthNum = monthMatch ? parseInt(monthMatch[1]) : 0;
  const monthName = monthNum > 0 ? new Date(2000, monthNum - 1).toLocaleString('en', { month: 'short' }) : "Unknown";
  
  // Check if all 3 events are present
  const presentEvents = { unemployment: false, nonfarm: false, earnings: false };
  const predictions = { unemployment: null, nonfarm: null, earnings: null };
  const eventDetails = { unemployment: null, nonfarm: null, earnings: null };
  
  occ.events.forEach(ev => {
    const id = ev.consistent_event_id || ev.event_id;
    if (id === significantEventIds.unemployment) {
      presentEvents.unemployment = true;
      predictions.unemployment = getXAUUSDPrediction(ev.forecast_value, ev.previous_value, ev.event_name, ev.impact_level);
      eventDetails.unemployment = {
        forecast: ev.forecast_value,
        previous: ev.previous_value,
        actual: ev.actual_value
      };
    } else if (id === significantEventIds.nonfarm) {
      presentEvents.nonfarm = true;
      predictions.nonfarm = getXAUUSDPrediction(ev.forecast_value, ev.previous_value, ev.event_name, ev.impact_level);
      eventDetails.nonfarm = {
        forecast: ev.forecast_value,
        previous: ev.previous_value,
        actual: ev.actual_value
      };
    } else if (id === significantEventIds.earnings) {
      presentEvents.earnings = true;
      predictions.earnings = getXAUUSDPrediction(ev.forecast_value, ev.previous_value, ev.event_name, ev.impact_level);
      eventDetails.earnings = {
        forecast: ev.forecast_value,
        previous: ev.previous_value,
        actual: ev.actual_value
      };
    }
  });
  
  // Only analyze when all 3 are present
  if (!presentEvents.unemployment || !presentEvents.nonfarm || !presentEvents.earnings) {
    continue;
  }
  
  // Check correctness of each prediction
  const results = {
    unemployment: predictions.unemployment === actualDirection,
    nonfarm: predictions.nonfarm === actualDirection,
    earnings: predictions.earnings === actualDirection
  };
  
  detailedAnalysis.push({
    key: occKey,
    date: occDate,
    month: monthName,
    actualDirection,
    predictions,
    results,
    eventDetails
  });
}

// Group by unemployment correctness
const unemploymentCorrect = detailedAnalysis.filter(d => d.results.unemployment);
const unemploymentWrong = detailedAnalysis.filter(d => !d.results.unemployment);

console.log("\n" + "=".repeat(120));
console.log("ANALYSIS: WHEN UNEMPLOYMENT RATE PREDICTION WAS WRONG");
console.log("=".repeat(120));

console.log(`\nTotal Occurrences: ${detailedAnalysis.length}`);
console.log(`Unemployment Correct: ${unemploymentCorrect.length} (${((unemploymentCorrect.length/detailedAnalysis.length)*100).toFixed(1)}%)`);
console.log(`Unemployment Wrong: ${unemploymentWrong.length} (${((unemploymentWrong.length/detailedAnalysis.length)*100).toFixed(1)}%)`);

// When unemployment was wrong, check if NFP or Earnings were correct
console.log("\n" + "=".repeat(120));
console.log("WHEN UNEMPLOYMENT WAS WRONG - Which other events were CORRECT?");
console.log("=".repeat(120));

let nfpCorrectWhenUnempWrong = 0;
let earningsCorrectWhenUnempWrong = 0;
let bothCorrectWhenUnempWrong = 0;
let neitherCorrectWhenUnempWrong = 0;

unemploymentWrong.forEach(d => {
  if (d.results.nonfarm && d.results.earnings) {
    bothCorrectWhenUnempWrong++;
  } else if (d.results.nonfarm) {
    nfpCorrectWhenUnempWrong++;
  } else if (d.results.earnings) {
    earningsCorrectWhenUnempWrong++;
  } else {
    neitherCorrectWhenUnempWrong++;
  }
});

console.log(`\nOut of ${unemploymentWrong.length} times Unemployment was wrong:`);
console.log(`  ✓ NFP alone was correct: ${nfpCorrectWhenUnempWrong} (${((nfpCorrectWhenUnempWrong/unemploymentWrong.length)*100).toFixed(1)}%)`);
console.log(`  ✓ Earnings alone was correct: ${earningsCorrectWhenUnempWrong} (${((earningsCorrectWhenUnempWrong/unemploymentWrong.length)*100).toFixed(1)}%)`);
console.log(`  ✓ BOTH NFP and Earnings were correct: ${bothCorrectWhenUnempWrong} (${((bothCorrectWhenUnempWrong/unemploymentWrong.length)*100).toFixed(1)}%)`);
console.log(`  ✗ Neither NFP nor Earnings was correct: ${neitherCorrectWhenUnempWrong} (${((neitherCorrectWhenUnempWrong/unemploymentWrong.length)*100).toFixed(1)}%)`);

// Show detailed examples
console.log("\n" + "=".repeat(120));
console.log("DETAILED EXAMPLES: When Unemployment Wrong but Other Events Correct");
console.log("=".repeat(120));

const examplesWithOthersCorrect = unemploymentWrong.filter(d => d.results.nonfarm || d.results.earnings);

examplesWithOthersCorrect.forEach(d => {
  console.log(`\n${d.key} - ${d.date} (${d.month}) | Actual: ${d.actualDirection}`);
  console.log(`  Unemployment: ${d.predictions.unemployment} ${d.results.unemployment ? '✓' : '✗'} (F:${d.eventDetails.unemployment.forecast} P:${d.eventDetails.unemployment.previous})`);
  console.log(`  NFP: ${d.predictions.nonfarm} ${d.results.nonfarm ? '✓' : '✗'} (F:${d.eventDetails.nonfarm.forecast} P:${d.eventDetails.nonfarm.previous})`);
  console.log(`  Earnings: ${d.predictions.earnings} ${d.results.earnings ? '✓' : '✗'} (F:${d.eventDetails.earnings.forecast} P:${d.eventDetails.earnings.previous})`);
});

// Monthly breakdown
console.log("\n" + "=".repeat(120));
console.log("MONTHLY BREAKDOWN: Unemployment Wrong Cases");
console.log("=".repeat(120));

const monthlyBreakdown = {};
unemploymentWrong.forEach(d => {
  if (!monthlyBreakdown[d.month]) {
    monthlyBreakdown[d.month] = {
      total: 0,
      nfpCorrect: 0,
      earningsCorrect: 0,
      bothCorrect: 0,
      neitherCorrect: 0
    };
  }
  monthlyBreakdown[d.month].total++;
  if (d.results.nonfarm && d.results.earnings) {
    monthlyBreakdown[d.month].bothCorrect++;
  } else if (d.results.nonfarm) {
    monthlyBreakdown[d.month].nfpCorrect++;
  } else if (d.results.earnings) {
    monthlyBreakdown[d.month].earningsCorrect++;
  } else {
    monthlyBreakdown[d.month].neitherCorrect++;
  }
});

console.log("\nMonth     UnempWrong  NFP✓Alone  Earn✓Alone  Both✓   Neither✓");
console.log("-".repeat(80));
Object.entries(monthlyBreakdown).sort().forEach(([month, stats]) => {
  console.log(
    month.padEnd(10) +
    stats.total.toString().padEnd(12) +
    stats.nfpCorrect.toString().padEnd(11) +
    stats.earningsCorrect.toString().padEnd(12) +
    stats.bothCorrect.toString().padEnd(8) +
    stats.neitherCorrect.toString()
  );
});

console.log("\n" + "=".repeat(120));
console.log("KEY INSIGHT:");
console.log("=".repeat(120));
const othersCorrectRate = ((nfpCorrectWhenUnempWrong + earningsCorrectWhenUnempWrong + bothCorrectWhenUnempWrong) / unemploymentWrong.length * 100).toFixed(1);
console.log(`When Unemployment prediction is wrong, at least ONE other event is correct ${othersCorrectRate}% of the time!`);
console.log(`This is why weighted approach works - we can rely on NFP or Earnings when Unemployment fails.`);
console.log("=".repeat(120));
