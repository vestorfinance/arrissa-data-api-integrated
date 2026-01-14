const fs = require('fs');
const data = JSON.parse(fs.readFileSync('./nfp_data.json', 'utf-8')).vestor_data;

console.log("\n=== ANALYZING 100% ACCURACY MONTHS ===\n");

console.log("UNEMPLOYMENT RATE - OCTOBER (100% - 3/3 correct):");
console.log("-".repeat(80));
Object.entries(data).forEach(([key, occ]) => {
  const month = occ.occurrence_date.split('-')[1];
  if (month === '10') {
    occ.events.forEach(ev => {
      if (ev.consistent_event_id === 'JCDYM') {
        console.log(`${occ.occurrence_date}: Forecast=${ev.forecast_value}, Previous=${ev.previous_value}, Actual=${ev.actual_value}`);
        const f = parseFloat(ev.forecast_value);
        const p = parseFloat(ev.previous_value);
        const prediction = f > p ? "UP" : (f < p ? "DOWN" : "DOWN");
        console.log(`  Prediction: ${prediction} (${f > p ? 'Forecast > Previous (unemployment rising)' : 'Forecast < Previous (unemployment falling)'})`);
      }
    });
  }
});

console.log("\nUNEMPLOYMENT RATE - DECEMBER (100% - 3/3 correct):");
console.log("-".repeat(80));
Object.entries(data).forEach(([key, occ]) => {
  const month = occ.occurrence_date.split('-')[1];
  if (month === '12') {
    occ.events.forEach(ev => {
      if (ev.consistent_event_id === 'JCDYM') {
        console.log(`${occ.occurrence_date}: Forecast=${ev.forecast_value}, Previous=${ev.previous_value}, Actual=${ev.actual_value}`);
        const f = parseFloat(ev.forecast_value);
        const p = parseFloat(ev.previous_value);
        const prediction = f > p ? "UP" : (f < p ? "DOWN" : "DOWN");
        console.log(`  Prediction: ${prediction} (${f > p ? 'Forecast > Previous (unemployment rising)' : 'Forecast < Previous (unemployment falling)'})`);
      }
    });
  }
});

console.log("\nAVERAGE HOURLY EARNINGS - JUNE (100% - 4/4 correct):");
console.log("-".repeat(80));
Object.entries(data).forEach(([key, occ]) => {
  const month = occ.occurrence_date.split('-')[1];
  if (month === '06') {
    occ.events.forEach(ev => {
      if (ev.consistent_event_id === 'ZBEYU') {
        console.log(`${occ.occurrence_date}: Forecast=${ev.forecast_value}, Previous=${ev.previous_value}, Actual=${ev.actual_value}`);
        const f = parseFloat(ev.forecast_value);
        const p = parseFloat(ev.previous_value);
        const prediction = f > p ? "DOWN" : (f < p ? "UP" : "DOWN");
        console.log(`  Prediction: ${prediction} (${f > p ? 'Forecast > Previous (wages rising)' : 'Forecast < Previous (wages falling)'})`);
      }
    });
  }
});

console.log("\nNONFARM PAYROLLS - MAY (100% - 4/4 correct):");
console.log("-".repeat(80));
Object.entries(data).forEach(([key, occ]) => {
  const month = occ.occurrence_date.split('-')[1];
  if (month === '05') {
    occ.events.forEach(ev => {
      if (ev.consistent_event_id === 'VPRWG') {
        console.log(`${occ.occurrence_date}: Forecast=${ev.forecast_value}, Previous=${ev.previous_value}, Actual=${ev.actual_value}`);
        const f = parseFloat(ev.forecast_value);
        const p = parseFloat(ev.previous_value);
        const prediction = f > p ? "DOWN" : (f < p ? "UP" : "DOWN");
        console.log(`  Prediction: ${prediction} (${f > p ? 'Forecast > Previous (jobs rising)' : 'Forecast < Previous (jobs falling)'})`);
      }
    });
  }
});

console.log("\n=== SEASONAL PATTERNS & ECONOMIC CONTEXT ===\n");

console.log("OCTOBER (Unemployment Rate 100%):");
console.log("  - Pre-holiday season hiring begins");
console.log("  - Q4 starts, companies finalize year-end staffing");
console.log("  - Seasonal adjustment factors can be volatile");
console.log("  - Election year considerations (some years)");

console.log("\nDECEMBER (Unemployment Rate 100%):");
console.log("  - Holiday retail hiring peak (temporary workers)");
console.log("  - End of fiscal year for many companies");
console.log("  - Winter weather impacts some sectors");
console.log("  - Year-end data often revised in following months");

console.log("\nJUNE (Average Hourly Earnings 100%):");
console.log("  - Mid-year salary reviews/raises common");
console.log("  - Summer hiring season for students/seasonal workers");
console.log("  - Q2 ends, mid-year bonuses may be included");
console.log("  - Graduation influx of new workers");

console.log("\nMAY (Nonfarm Payrolls 100%):");
console.log("  - Spring hiring peak across multiple sectors");
console.log("  - Construction/outdoor seasonal jobs ramp up");
console.log("  - Retail preparing for summer season");
console.log("  - Q2 begins with renewed hiring budgets");
