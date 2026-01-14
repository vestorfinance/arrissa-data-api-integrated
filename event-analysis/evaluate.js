const fs = require("fs");
const path = require("path");

// Load JSON data
const filePath = path.join(__dirname, "nfp_data.json");
const rawData = fs.readFileSync(filePath, "utf-8");
const data = JSON.parse(rawData).vestor_data;

// Helper: Determine bias (force BUY/SELL, never NEUTRAL)
function getBias(forecast, previous, highImpact = true) {
  if (forecast === null || previous === null) return "BUY"; // fallback
  const f = parseFloat(forecast);
  const p = parseFloat(previous);

  if (f > p) return "BUY";
  if (f < p) return "SELL";
  
  // If equal, use heuristic: assume BUY if high impact, else SELL
  return highImpact ? "BUY" : "SELL";
}

// Helper: Determine spike direction
function getSpikeDirection(first3MinData) {
  let upMax = 0;
  let downMax = 0;
  const firstClose = first3MinData[0].close;

  first3MinData.forEach(c => {
    const upMove = c.high - firstClose;
    const downMove = firstClose - c.low;
    if (upMove > upMax) upMax = upMove;
    if (downMove > downMax) downMax = downMove;
  });

  const direction = upMax >= downMax ? "UP" : "DOWN";
  return { direction, upMax: parseFloat(upMax.toFixed(2)), downMax: parseFloat(downMax.toFixed(2)) };
}

// Main processing
for (const occKey in data) {
  const occ = data[occKey];
  const first3MinData = occ.xauusd_data.slice(0, 3); // first 3 minutes

  const spike = getSpikeDirection(first3MinData);

  console.log(`Occurrence: ${occKey} (${occ.occurrence_date} ${occ.occurrence_time})`);
  console.log(`Initial spike: ${spike.direction} (Up: ${spike.upMax}, Down: ${spike.downMax})`);

  console.log("Pre-release biases:");
  const biases = {};
  occ.events.forEach(ev => {
    const bias = getBias(ev.forecast_value, ev.previous_value);
    biases[ev.event_name] = bias;
    console.log(`  - ${ev.event_name}: ${bias}`);
  });

  // Find events consistent with spike
  const consistentEvents = [];
  for (const evName in biases) {
    if ((spike.direction === "UP" && biases[evName] === "BUY") ||
        (spike.direction === "DOWN" && biases[evName] === "SELL")) {
      consistentEvents.push(evName);
    }
  }

  console.log("Events consistent with spike:");
  if (consistentEvents.length > 0) {
    consistentEvents.forEach(ev => console.log(`  - ${ev}`));
  } else {
    console.log("  None detected");
  }

  console.log("\n--------------------------------------\n");
}
