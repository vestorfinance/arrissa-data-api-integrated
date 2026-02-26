# TMA+CG API Pretend Time Feature

## Overview
The TMA+CG API now supports historical backtesting through `pretend_date` and `pretend_time` parameters, allowing you to retrieve TMA+CG data as if the current time was a specific historical moment.

## Usage

### API Request Format

```
GET http://localhost/tma-cg-api-v1/tma-cg-api.php?api_key=YOUR_KEY&symbol=NAS100&timeframe=M1&pretend_date=2026-01-15&pretend_time=14:30
```

### Parameters

- **symbol** (required): Trading symbol (e.g., `NAS100`, `EURUSD`)
- **timeframe** (optional): Timeframe for analysis (default: `M1`)
- **pretend_date** (optional): Historical date in format `YYYY-MM-DD`
- **pretend_time** (optional): Time in format `HH:MM` (default: `00:00` if date provided)
- **api_key** (required): Your API authentication key

### Examples

#### Current Time (Normal Mode)
```bash
curl "http://localhost/tma-cg-api-v1/tma-cg-api.php?api_key=YOUR_KEY&symbol=NAS100&timeframe=M1"
```

#### Historical Snapshot (Pretend Time Mode)
```bash
curl "http://localhost/tma-cg-api-v1/tma-cg-api.php?api_key=YOUR_KEY&symbol=NAS100&timeframe=M1&pretend_date=2026-01-15&pretend_time=14:30"
```

This retrieves TMA+CG data as if the current time was January 15, 2026 at 14:30.

## Response Format

```json
{
    "status": "success",
    "symbol": "NAS100",
    "timeframe": "M1",
    "zone": "premium",
    "percentage": 45.23,
    "current_price": 4760.50,
    "tma_middle": 4750.00,
    "upper_band_1": 4755.00,
    "lower_band_1": 4745.00,
    "upper_band_7": 4775.00,
    "lower_band_7": 4725.00,
    "timestamp": "2026-01-15 14:30"
}
```

## How It Works

1. **Client Request**: You send a GET request with `pretend_date` and `pretend_time` parameters
2. **API Queuing**: The API stores the request including pretend time parameters in the queue
3. **EA Processing**: The TMA CG Data EA polls the API, retrieves the request
4. **Historical Data**: The EA:
   - Uses pretend time to calculate the correct historical price from M1 bars
   - Reads TMA indicator values at that historical moment
   - Calculates zone (premium/discount) and percentage relative to TMA middle
5. **API Response**: Returns the historical snapshot to the client

## Key Features

- **Accurate Historical Price**: Uses M1 bar data to get exact historical prices
- **Historical TMA Values**: Reads indicator buffers at the specified historical moment
- **Zone Calculation**: Premium/discount zones calculated accurately for that moment
- **Timestamp Reflection**: Response timestamp shows the pretend time used

## EA Configuration

The EA automatically handles pretend time requests. No special configuration needed.

### Debug Mode
Enable debug mode in the EA to see pretend time processing:
```cpp
input bool InpDebugMode = true;
```

You'll see logs like:
```
Using pretend time: 2026-01-15 14:30
Calculated TMA+CG for NAS100: Zone=premium (45.23%)
```

## Use Cases

1. **Historical Backtesting**: Test trading strategies with historical TMA+CG data
2. **Performance Analysis**: Analyze how TMA zones evolved at specific historical moments
3. **Strategy Validation**: Validate entry/exit signals from past price action
4. **Research**: Study TMA behavior during specific market conditions

## Technical Implementation

### API Changes (tma-cg-api.php)
- Added `pretend_date` and `pretend_time` parameter extraction
- Parameters stored in request queue JSON
- Passed to EA for processing

### EA Changes (TMA CG Data EA.mq5)
- Added global variables: `g_pretend_datetime`, `g_use_pretend_time`
- Added `GetEffectiveCurrentTime()` function
- Modified `ProcessApiRequest()` to parse pretend parameters
- Updated `BuildTmaCGJson()` and `CalculateTmaCGForSymbol()` to use historical prices
- Historical price retrieval via `iBarShift()` and `iClose()`
- Pretend time automatically resets after each request

## Compatibility

- Works with all trading symbols
- Compatible with all timeframes
- No impact on normal (non-pretend) API requests
- EA continues normal operations between API requests

## Notes

- Historical data must exist in MT5 for the specified date/time
- M1 (1-minute) bars used for precise historical price lookup
- If pretend time bar not found, falls back to current BID price
- Pretend time only affects the specific API request, not live EA operations
