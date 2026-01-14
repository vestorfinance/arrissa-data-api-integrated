const fs = require('fs');
const path = require('path');

// Path to your JSON file
const DATA_FILE = path.join(__dirname, 'nfp_data.json');

// Read JSON file
const rawData = fs.readFileSync(DATA_FILE);
const vestorData = JSON.parse(rawData).vestor_data;

// Function to determine initial spike
function getInitialSpike(xauusdData) {
    // Take the first 3 minutes only
    const first3 = xauusdData.slice(0, 3);
    if (first3.length === 0) return null;

    const initialOpen = first3[0].open;
    const highs = first3.map(c => c.high);
    const lows = first3.map(c => c.low);

    const spikeUp = Math.max(...highs) - initialOpen;
    const spikeDown = initialOpen - Math.min(...lows);

    return {
        direction: spikeUp >= spikeDown ? 'UP' : 'DOWN',
        spikeUp,
        spikeDown,
        initialOpen,
        high: Math.max(...highs),
        low: Math.min(...lows)
    };
}

// Simple pre-release bias function based on forecast vs previous
function getPreReleaseBias(events) {
    const biases = events.map(event => {
        if (!event.forecast_value || !event.previous_value) return null;
        const forecast = parseFloat(event.forecast_value);
        const previous = parseFloat(event.previous_value);
        if (forecast > previous) return { event: event.event_name, bias: 'BUY' };
        if (forecast < previous) return { event: event.event_name, bias: 'SELL' };
        return { event: event.event_name, bias: 'NEUTRAL' };
    });
    return biases.filter(b => b !== null);
}

// Analyze each occurrence
for (const [occurrenceKey, occurrence] of Object.entries(vestorData)) {
    const spike = getInitialSpike(occurrence.xauusd_data);
    const preReleaseBiases = getPreReleaseBias(occurrence.events);

    console.log(`\nOccurrence: ${occurrenceKey} (${occurrence.occurrence_date} ${occurrence.occurrence_time})`);
    console.log(`Initial spike: ${spike.direction} (Up: ${spike.spikeUp.toFixed(2)}, Down: ${spike.spikeDown.toFixed(2)})`);
    console.log(`Pre-release biases:`);
    preReleaseBiases.forEach(b => {
        console.log(`  - ${b.event}: ${b.bias}`);
    });

    // Check which event bias matched initial spike
    const matchingEvents = preReleaseBiases.filter(b => 
        (spike.direction === 'UP' && b.bias === 'BUY') ||
        (spike.direction === 'DOWN' && b.bias === 'SELL')
    );

    console.log(`Events consistent with spike:`);
    if (matchingEvents.length === 0) {
        console.log('  None detected');
    } else {
        matchingEvents.forEach(b => console.log(`  - ${b.event}`));
    }
}
