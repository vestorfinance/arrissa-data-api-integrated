//+------------------------------------------------------------------+
//|                             Richchild Quarters Theory EA.mq5     |
//|                            Copyright 2025, Arrissa Technologies. |
//|                                            https://arrissa.trade |
//+------------------------------------------------------------------+
#property copyright "Copyright 2026, Arrissa Technologies, Flowbase."
#property link      "https://flowbase.store"
#property version   "2.0"
#property strict

//--- input parameters for period range calculation
input int InpPeriodsLookback = 30;        // Periods to look back for average calculation
input bool InpIgnoreSunday = true;        // Ignore Sundays in calculation (for traditional markets)
input bool InpDebugMode = false;          // Enable debug output

//--- input parameters for API
input string InpApiUrl = "http://localhost/quarters-theory-api-v1/quarters-theory-api.php"; // API URL
input bool InpEnableApi = true;           // Enable API communication
input int InpApiPollingSeconds = 2;       // API polling interval in seconds

//--- global variables (no longer need InpRangeTimeframe - each TF calculates independently)

// Dynamic quota variables (removed - each timeframe calculates independently)

// Timeframe data storage
struct TimeframeData
{
    ENUM_TIMEFRAMES timeframe;
    double high;
    double low;
    double low_percentage;     // Position from low (0-100%)
    double high_percentage;    // Position from high (negative when below)
    string time_quarter;       // 1st, 2nd, 3rd, 4th
    string countdown;          // Countdown to end of period
    double quota_value;        // Historical average quota for this timeframe (calculated at init)
};

TimeframeData tf_data[];

// File handling removed - no settings file needed

// API control variables
datetime last_api_poll_time = 0;
bool api_processing_lock = false;  // Prevent overlapping API requests

// Pretend date/time variables (for historical snapshots)
datetime g_pretend_datetime = 0;  // 0 means use real time
bool g_use_pretend_time = false;

// Debug data structure
struct DebugInfo
{
    string timeframe;
    int bars_scanned;
    int bars_skipped_future;
    int bars_skipped_past;
    int bars_skipped_outside_period;
    int bars_processed;
    datetime first_bar_time;
    datetime last_bar_time;
    datetime period_start;
    datetime period_end;
    datetime end_time;
    double result_high;
    double result_low;
    bool fallback_used;
};

DebugInfo g_debug_data[];

//+------------------------------------------------------------------+
//| Get Effective Current Time (pretend or real)                     |
//+------------------------------------------------------------------+
datetime GetEffectiveCurrentTime()
{
    if(g_use_pretend_time && g_pretend_datetime > 0)
    {
        return g_pretend_datetime;
    }
    return TimeCurrent();
}

//+------------------------------------------------------------------+
//| Debug Print Function                                             |
//+------------------------------------------------------------------+
void DebugPrint(string message)
{
    if(InpDebugMode)
    {
        Print("DEBUG: ", message);
    }
}

//+------------------------------------------------------------------+
//| Expert initialization function                                   |
//+------------------------------------------------------------------+
int OnInit()
{
    if(InpDebugMode)
        Print("Richchild Quarters Theory EA initialized");
    
    DebugPrint("Periods lookback: " + IntegerToString(InpPeriodsLookback) + " periods per timeframe");
    
    // Initialize timeframe data array
    InitializeTimeframeData();
    
    // Initialize API polling timer
    last_api_poll_time = TimeCurrent();
    
    // Set timer to trigger every second
    EventSetTimer(1);
    
    return(INIT_SUCCEEDED);
}

//+------------------------------------------------------------------+
//| Expert deinitialization function                                 |
//+------------------------------------------------------------------+
void OnDeinit(const int reason)
{
    // Kill the timer
    EventKillTimer();
    
    if(InpDebugMode)
        Print("Richchild Quarters Theory EA deinitialized");
}

//+------------------------------------------------------------------+
//| Expert tick function                                             |
//+------------------------------------------------------------------+
void OnTick()
{
    // EA runs on timer, not on tick
}

//+------------------------------------------------------------------+
//| Timer function - triggers every second                           |
//+------------------------------------------------------------------+
void OnTimer()
{
    // Check if it's time to poll API
    datetime current_time = TimeCurrent();
    if(InpEnableApi && current_time - last_api_poll_time >= InpApiPollingSeconds)
    {
        last_api_poll_time = current_time;
        PollApiForRequests();
    }
}

//+------------------------------------------------------------------+
//| Initialize timeframe data array                                 |
//+------------------------------------------------------------------+
void InitializeTimeframeData()
{
    ArrayResize(tf_data, 11);
    tf_data[0].timeframe = PERIOD_M1;
    tf_data[1].timeframe = PERIOD_M5;
    tf_data[2].timeframe = PERIOD_M15;
    tf_data[3].timeframe = PERIOD_M30;
    tf_data[4].timeframe = PERIOD_H1;
    tf_data[5].timeframe = PERIOD_H4;
    tf_data[6].timeframe = PERIOD_H6;
    tf_data[7].timeframe = PERIOD_H12;
    tf_data[8].timeframe = PERIOD_D1;
    tf_data[9].timeframe = PERIOD_W1;
    tf_data[10].timeframe = PERIOD_MN1;
    
    // Calculate historical average quota for EACH timeframe at init
    // Following indicator logic: CalculateQuotaForTimeframe
    for(int i = 0; i < 11; i++)
    {
        tf_data[i].quota_value = CalculateQuotaForTimeframe(tf_data[i].timeframe);
        DebugPrint(EnumToString(tf_data[i].timeframe) + " quota: " + DoubleToString(tf_data[i].quota_value, 1));
    }
}

//+------------------------------------------------------------------+
//| Calculate countdown to end of period for specific timeframe     |
//+------------------------------------------------------------------+
string CalculateCountdown(ENUM_TIMEFRAMES timeframe)
{
    datetime current_time = GetEffectiveCurrentTime();
    datetime period_start = GetPeriodStart(timeframe);
    datetime period_end = GetPeriodEnd(timeframe);
    
    int remaining_seconds = (int)(period_end - current_time);
    
    if(remaining_seconds <= 0)
        return "00:00";
    
    switch(timeframe)
    {
        case PERIOD_M1:
        case PERIOD_M5:
        case PERIOD_M15:
        case PERIOD_M30:
        {
            int minutes = remaining_seconds / 60;
            int seconds = remaining_seconds % 60;
            return StringFormat("%02d:%02d", minutes, seconds);
        }
        
        case PERIOD_H1:
        case PERIOD_H4:
        case PERIOD_H6:
        case PERIOD_H12:
        {
            int hours = remaining_seconds / 3600;
            int minutes = (remaining_seconds % 3600) / 60;
            return StringFormat("%02d:%02d", hours, minutes);
        }
        
        case PERIOD_D1:
        {
            int hours = remaining_seconds / 3600;
            int minutes = (remaining_seconds % 3600) / 60;
            return StringFormat("%02d:%02d", hours, minutes);
        }
        
        case PERIOD_W1:
        {
            int days = remaining_seconds / 86400;
            int hours = (remaining_seconds % 86400) / 3600;
            int minutes = (remaining_seconds % 3600) / 60;
            
            if(days > 0)
                return StringFormat("%dD %02d:%02d", days, hours, minutes);
            else
                return StringFormat("%02d:%02d", hours, minutes);
        }
        
        case PERIOD_MN1:
        {
            int days = remaining_seconds / 86400;
            int hours = (remaining_seconds % 86400) / 3600;
            int minutes = (remaining_seconds % 3600) / 60;
            
            if(days > 0)
                return StringFormat("%dD %02d:%02d", days, hours, minutes);
            else
                return StringFormat("%02d:%02d", hours, minutes);
        }
        
        default:
            return "00:00";
    }
}

//+------------------------------------------------------------------+
//| Update all timeframe data                                        |
//+------------------------------------------------------------------+
void UpdateAllTimeframeData()
{
    double current_price = SymbolInfoDouble(_Symbol, SYMBOL_BID);
    
    for(int i = 0; i < ArraySize(tf_data); i++)
    {
        datetime period_start = GetPeriodStart(tf_data[i].timeframe);
        datetime period_end = GetPeriodEnd(tf_data[i].timeframe);
        
        tf_data[i].high = GetPeriodHighLowForTimeframe(period_start, period_end, tf_data[i].timeframe, true);
        tf_data[i].low = GetPeriodHighLowForTimeframe(period_start, period_end, tf_data[i].timeframe, false);
        
        // Calculate countdown
        tf_data[i].countdown = CalculateCountdown(tf_data[i].timeframe);
        
        // Calculate position percentages based on quota lines
        if(tf_data[i].high > tf_data[i].low)
        {
            double quota_points = tf_data[i].quota_value;
            double quota_price = PointsToPrice(quota_points);
            
            // Low %: How many quotas UP from the LOW
            double low_distance = current_price - tf_data[i].low;
            double low_quotas = low_distance / quota_price;
            tf_data[i].low_percentage = (low_quotas / 4.0) * 100.0;
            
            // High %: How many quotas DOWN from the HIGH
            double high_distance = current_price - tf_data[i].high;
            double high_quotas = high_distance / quota_price;
            tf_data[i].high_percentage = (high_quotas / 4.0) * 100.0;
            
            // Calculate TIME quarter position
            datetime current_time = GetEffectiveCurrentTime();
            double time_progress = (double)(current_time - period_start) / (double)(period_end - period_start);
            time_progress = MathMax(0.0, MathMin(1.0, time_progress));
            
            if(time_progress <= 0.25)
                tf_data[i].time_quarter = "1st";
            else if(time_progress <= 0.50)
                tf_data[i].time_quarter = "2nd";
            else if(time_progress <= 0.75)
                tf_data[i].time_quarter = "3rd";
            else
                tf_data[i].time_quarter = "4th";
        }
        else
        {
            tf_data[i].low_percentage = 50.0;
            tf_data[i].high_percentage = -50.0;
            tf_data[i].time_quarter = "2nd";
        }
    }
}

//+------------------------------------------------------------------+
//| Get period start for specific timeframe                         |
//+------------------------------------------------------------------+
datetime GetPeriodStart(ENUM_TIMEFRAMES timeframe)
{
    datetime current_time = GetEffectiveCurrentTime();
    MqlDateTime dt;
    TimeToStruct(current_time, dt);
    
    switch(timeframe)
    {
        case PERIOD_W1:
        {
            dt.hour = 0;
            dt.min = 0;
            dt.sec = 0;
            datetime day_start = StructToTime(dt);
            int days_since_sunday = dt.day_of_week;
            datetime week_start = day_start - (days_since_sunday * 86400);
            return week_start;
        }
            
        case PERIOD_MN1:
        {
            dt.day = 1;
            dt.hour = 0;
            dt.min = 0;
            dt.sec = 0;
            datetime month_start = StructToTime(dt);
            return month_start;
        }
            
        case PERIOD_D1:
        {
            dt.hour = 0;
            dt.min = 0;
            dt.sec = 0;
            datetime day_start_time = StructToTime(dt);
            if(current_time < day_start_time)
                day_start_time -= 86400;
            return day_start_time;
        }
            
        default:
        {
            MqlDateTime dt_current;
            TimeToStruct(current_time, dt_current);
            dt_current.hour = 0;
            dt_current.min = 0;
            dt_current.sec = 0;
            datetime day_start_base = StructToTime(dt_current);
            
            int period_seconds = PeriodSeconds(timeframe);
            int seconds_since_midnight = (int)(current_time - day_start_base);
            int period_number = seconds_since_midnight / period_seconds;
            
            datetime period_start = day_start_base + (period_number * period_seconds);
            
            return period_start;
        }
    }
}

//+------------------------------------------------------------------+
//| Get period end for specific timeframe                           |
//+------------------------------------------------------------------+
datetime GetPeriodEnd(ENUM_TIMEFRAMES timeframe)
{
    datetime period_start = GetPeriodStart(timeframe);
    
    switch(timeframe)
    {
        case PERIOD_W1:
            return period_start + (7 * 86400);
            
        case PERIOD_MN1:
        {
            MqlDateTime dt;
            TimeToStruct(period_start, dt);
            dt.mon++;
            if(dt.mon > 12)
            {
                dt.mon = 1;
                dt.year++;
            }
            return StructToTime(dt);
        }
            
        default:
            return period_start + PeriodSeconds(timeframe);
    }
}

//+------------------------------------------------------------------+
//| Calculate quota for any timeframe (FOLLOWS INDICATOR LOGIC)     |
//+------------------------------------------------------------------+
double CalculateQuotaForTimeframe(ENUM_TIMEFRAMES timeframe)
{
    MqlRates rates[];
    int periods_to_request = InpPeriodsLookback + 50;
    int copied = CopyRates(_Symbol, timeframe, 0, periods_to_request, rates);
    
    if(copied <= 0) 
    {
        // Fallback to current range / 4 if data not available
        datetime period_start = GetPeriodStart(timeframe);
        datetime period_end = GetPeriodEnd(timeframe);
        double high = GetPeriodHighLowForTimeframe(period_start, period_end, timeframe, true);
        double low = GetPeriodHighLowForTimeframe(period_start, period_end, timeframe, false);
        double range_in_points = (high - low) / _Point;
        double fallback_quota = range_in_points / 4.0;
        
        DebugPrint(EnumToString(timeframe) + " fallback quota: " + DoubleToString(fallback_quota, 1));
        return fallback_quota;
    }
    
    double totalRange = 0.0;
    int validPeriods = 0;
    
    // CRITICAL: Start from i=1 to skip current incomplete bar (INDICATOR LOGIC)
    for(int i = 1; i < copied; i++)
    {
        // Only skip Sunday for intraday timeframes (not weekly/monthly)
        if(InpIgnoreSunday && timeframe < PERIOD_W1)
        {
            MqlDateTime dt;
            TimeToStruct(rates[i].time, dt);
            if(dt.day_of_week == 0) continue;
        }
        
        double periodRange = (rates[i].high - rates[i].low) / _Point;
        if(periodRange > 0)
        {
            totalRange += periodRange;
            validPeriods++;
        }
        
        if(validPeriods >= InpPeriodsLookback) break;
    }
    
    if(validPeriods > 0)
    {
        double avgRange = totalRange / validPeriods;
        double quota = avgRange / 4.0;
        
        DebugPrint(EnumToString(timeframe) + " quota: avg=" + DoubleToString(avgRange, 1) + " / 4 = " + DoubleToString(quota, 1) + " (" + IntegerToString(validPeriods) + " periods)");
        return quota;
    }
    
    return 25.0; // Safe fallback
}

//+------------------------------------------------------------------+
//| Convert points to price difference                              |
//+------------------------------------------------------------------+
double PointsToPrice(double points)
{
    double symbolPoint = SymbolInfoDouble(_Symbol, SYMBOL_POINT);
    return points * symbolPoint;
}

//+------------------------------------------------------------------+
//| Get Period High or Low for specific timeframe                   |
//+------------------------------------------------------------------+
double GetPeriodHighLowForTimeframe(datetime period_start, datetime period_end, ENUM_TIMEFRAMES timeframe, bool get_high)
{
    double result = get_high ? 0 : DBL_MAX;
    
    if(timeframe == PERIOD_W1 || timeframe == PERIOD_MN1)
    {
        MqlRates rates[];
        int bars_to_copy = (int)((period_end - period_start) / PeriodSeconds(timeframe)) + 5;
        int copied = CopyRates(_Symbol, timeframe, period_start, bars_to_copy, rates);
        
        if(copied > 0)
        {
            for(int i = 0; i < copied; i++)
            {
                if(rates[i].time >= period_start && rates[i].time < period_end)
                {
                    if(get_high)
                    {
                        if(rates[i].high > result) 
                            result = rates[i].high;
                    }
                    else
                    {
                        if(rates[i].low < result) 
                            result = rates[i].low;
                    }
                }
            }
        }
    }
    
    if((get_high && result == 0) || (!get_high && result == DBL_MAX))
    {
        result = get_high ? 0 : DBL_MAX;
        int max_bars = (timeframe == PERIOD_W1 || timeframe == PERIOD_MN1) ? 50000 : 10000;
        
        for(int i = 0; i < max_bars; i++)
        {
            datetime bar_time = iTime(_Symbol, PERIOD_CURRENT, i);
            if(bar_time == 0 || bar_time < period_start) break;
            if(bar_time >= period_end) continue;
            
            if(get_high)
            {
                double high_price = iHigh(_Symbol, PERIOD_CURRENT, i);
                if(high_price > result) result = high_price;
            }
            else
            {
                double low_price = iLow(_Symbol, PERIOD_CURRENT, i);
                if(low_price < result) result = low_price;
            }
        }
    }
    
    return result;
}
//+------------------------------------------------------------------+
//| Poll API for pending requests                                   |
//+------------------------------------------------------------------+
void PollApiForRequests()
{
    if(!InpEnableApi)
        return;
    
    // Prevent overlapping requests - skip if already processing
    if(api_processing_lock)
    {
        DebugPrint("API request already in progress, skipping poll");
        return;
    }
        
    char result_data[];
    char post_data[];
    string result_headers;
    int timeout = 15000;  // Increased from 3000ms to 15000ms (15 seconds) to allow for symbol data loading and calculations
    
    // Poll for requests (GET with no parameters)
    int res = WebRequest(
        "GET",
        InpApiUrl,
        "",
        NULL,
        timeout,
        post_data,
        0,
        result_data,
        result_headers
    );
    
    if(res == -1)
    {
        int error = GetLastError();
        if(error == 4060)
        {
            DebugPrint("WebRequest not allowed. Add URL to allowed list: Tools->Options->Expert Advisors");
            DebugPrint("Add to allowed URLs: " + InpApiUrl);
        }
        else
        {
            DebugPrint("WebRequest error polling API: " + IntegerToString(error));
        }
        return;
    }
    
    if(res == 200)
    {
        string response = CharArrayToString(result_data, 0, WHOLE_ARRAY, CP_UTF8);
        DebugPrint("API Poll Response: " + response);
        
        // Check if there's a pending request
        if(StringFind(response, "request_id") >= 0 && StringFind(response, "symbol") >= 0)
        {
            // Set lock before processing
            api_processing_lock = true;
            
            // Process the request
            ProcessApiRequest(response);
            
            // Release lock after processing
            api_processing_lock = false;
        }
    }
}

//+------------------------------------------------------------------+
//| Process API request and send data                               |
//+------------------------------------------------------------------+
void ProcessApiRequest(string request_json)
{
    DebugPrint("Processing API request: " + request_json);
    
    // Parse request to get request_id and symbol
    string request_id = ExtractJsonValue(request_json, "request_id");
    string requested_symbol = ExtractJsonValue(request_json, "symbol");
    
    if(request_id == "" || requested_symbol == "")
    {
        DebugPrint("Invalid request: missing request_id or symbol");
        return;
    }
    
    // Parse pretend parameters if provided
    string pretend_date = ExtractJsonValue(request_json, "pretend_date");
    string pretend_time = ExtractJsonValue(request_json, "pretend_time");
    
    // Reset pretend time settings
    g_use_pretend_time = false;
    g_pretend_datetime = 0;
    
    // If pretend parameters are provided, parse and set them
    if(pretend_date != "")
    {
        string datetime_string = pretend_date;
        if(pretend_time != "")
        {
            datetime_string += " " + pretend_time;
        }
        else
        {
            datetime_string += " 00:00";
        }
        
        g_pretend_datetime = StringToTime(datetime_string);
        if(g_pretend_datetime > 0)
        {
            g_use_pretend_time = true;
            DebugPrint("Using pretend time: " + datetime_string + " (" + TimeToString(g_pretend_datetime, TIME_DATE|TIME_MINUTES) + ")");
        }
    }
    
    DebugPrint("Request for symbol: " + requested_symbol);
    
    // Calculate data for the requested symbol
    string quarters_json = BuildQuartersJsonForSymbol(requested_symbol);
    
    // Reset pretend time after processing
    g_use_pretend_time = false;
    g_pretend_datetime = 0;
    
    // Send data to API
    SendDataToApi(request_id, requested_symbol, quarters_json);
}

//+------------------------------------------------------------------+
//| Build quarters theory JSON data for specific symbol             |
//+------------------------------------------------------------------+
string BuildQuartersJsonForSymbol(string symbol)
{
    // Clear debug data
    if(InpDebugMode)
    {
        ArrayResize(g_debug_data, 0);
    }
    
    // Get current or historical price for the symbol
    double current_price;
    string price_debug = "";
    if(g_use_pretend_time && g_pretend_datetime > 0)
    {
        // At pretend time, use CLOSE of the last COMPLETED bar before pretend moment
        // Find the bar at or just before pretend time
        int shift = iBarShift(symbol, PERIOD_M1, g_pretend_datetime, true);
        
        if(shift >= 0)
        {
            datetime bar_time = iTime(symbol, PERIOD_M1, shift);
            
            // If bar opened exactly at pretend time, it's the NEW bar (not yet formed)
            // So we need the CLOSE of the PREVIOUS bar (shift + 1)
            if(bar_time == g_pretend_datetime)
            {
                // Use close of previous completed bar
                current_price = iClose(symbol, PERIOD_M1, shift + 1);
                price_debug = "Exact time: using close of bar at " + TimeToString(iTime(symbol, PERIOD_M1, shift + 1), TIME_DATE|TIME_MINUTES);
            }
            else
            {
                // Pretend time is during this bar - use close of this completed bar
                current_price = iClose(symbol, PERIOD_M1, shift);
                price_debug = "During bar: using close of bar at " + TimeToString(bar_time, TIME_DATE|TIME_MINUTES);
            }
            
            if(InpDebugMode)
            {
                DebugPrint("Current Price Selection: " + price_debug + " = " + DoubleToString(current_price, (int)SymbolInfoInteger(symbol, SYMBOL_DIGITS)));
            }
        }
        else
        {
            // Fallback to current bid if historical price not found
            current_price = SymbolInfoDouble(symbol, SYMBOL_BID);
            if(InpDebugMode) DebugPrint("Current Price: Fallback to current bid = " + DoubleToString(current_price, (int)SymbolInfoInteger(symbol, SYMBOL_DIGITS)));
        }
    }
    else
    {
        current_price = SymbolInfoDouble(symbol, SYMBOL_BID);
    }
    double point = SymbolInfoDouble(symbol, SYMBOL_POINT);
    int digits = (int)SymbolInfoInteger(symbol, SYMBOL_DIGITS);
    
    string json = "{";
    json += "\"timestamp\":\"" + TimeToString(GetEffectiveCurrentTime(), TIME_DATE|TIME_MINUTES) + "\",";
    json += "\"current_price\":" + DoubleToString(current_price, digits) + ",";
    json += "\"price_selection\":\"" + price_debug + "\",";
    json += "\"timeframes\":[";
    
    // Timeframes to analyze
    ENUM_TIMEFRAMES timeframes[] = {PERIOD_M15, PERIOD_M30, PERIOD_H1, PERIOD_H4, PERIOD_H6, PERIOD_H12, PERIOD_D1, PERIOD_W1, PERIOD_MN1};
    string timeframe_names[] = {"M15", "M30", "H1", "H4", "H6", "H12", "D1", "W1", "MN1"};
    
    for(int i = 0; i < ArraySize(timeframes); i++)
    {
        if(i > 0) json += ",";
        
        ENUM_TIMEFRAMES tf = timeframes[i];
        
        // Get period boundaries
        datetime period_start = GetPeriodStart(tf);
        datetime period_end = GetPeriodEnd(tf);
        
        // Get high/low for this period and symbol
        double high = GetPeriodHighLowForSymbol(symbol, period_start, period_end, tf, true, current_price);
        double low = GetPeriodHighLowForSymbol(symbol, period_start, period_end, tf, false, current_price);
        
        // Calculate quota for THIS SPECIFIC TIMEFRAME (following indicator logic)
        double tf_quota = CalculateAverageRangeForSymbol(symbol, tf) / 4.0;
        double quota_price = tf_quota * point;
        
        // Also calculate average range for this timeframe
        double avg_range = CalculateAverageRangeForSymbol(symbol, tf);
        
        // Calculate percentages
        double low_percentage = 50.0;
        double high_percentage = -50.0;
        
        if(high > low && quota_price > 0)
        {
            double low_distance = current_price - low;
            double low_quotas = low_distance / quota_price;
            low_percentage = (low_quotas / 4.0) * 100.0;
            
            double high_distance = current_price - high;
            double high_quotas = high_distance / quota_price;
            high_percentage = (high_quotas / 4.0) * 100.0;
        }
        
        // Calculate time quarter
        datetime current_time = GetEffectiveCurrentTime();
        double time_progress = (double)(current_time - period_start) / (double)(period_end - period_start);
        time_progress = MathMax(0.0, MathMin(1.0, time_progress));
        
        string time_quarter = "2nd";
        if(time_progress <= 0.25) time_quarter = "1st";
        else if(time_progress <= 0.50) time_quarter = "2nd";
        else if(time_progress <= 0.75) time_quarter = "3rd";
        else time_quarter = "4th";
        
        // Calculate countdown
        string countdown = CalculateCountdown(tf);
        
        json += "{";
        json += "\"timeframe\":\"" + timeframe_names[i] + "\",";
        json += "\"high\":" + DoubleToString(high, digits) + ",";
        json += "\"low\":" + DoubleToString(low, digits) + ",";
        json += "\"low_percentage\":" + DoubleToString(low_percentage, 0) + ",";
        json += "\"high_percentage\":" + DoubleToString(high_percentage, 0) + ",";
        json += "\"time_quarter\":\"" + time_quarter + "\",";
        json += "\"countdown\":\"" + countdown + "\",";
        json += "\"average_range\":" + DoubleToString(avg_range, 1) + ",";
        json += "\"quota_value\":" + DoubleToString(tf_quota, 1);
        json += "}";
    }
    
    json += "]";
    
    // Add debug information if in debug mode
    if(InpDebugMode && ArraySize(g_debug_data) > 0)
    {
        json += ",\"debug\":[";
        
        for(int d = 0; d < ArraySize(g_debug_data); d++)
        {
            if(d > 0) json += ",";
            
            json += "{";
            json += "\"timeframe\":\"" + g_debug_data[d].timeframe + "\",";
            json += "\"bars_scanned\":" + IntegerToString(g_debug_data[d].bars_scanned) + ",";
            json += "\"bars_skipped_future\":" + IntegerToString(g_debug_data[d].bars_skipped_future) + ",";
            json += "\"bars_skipped_past\":" + IntegerToString(g_debug_data[d].bars_skipped_past) + ",";
            json += "\"bars_skipped_outside_period\":" + IntegerToString(g_debug_data[d].bars_skipped_outside_period) + ",";
            json += "\"bars_processed\":" + IntegerToString(g_debug_data[d].bars_processed) + ",";
            json += "\"first_bar_time\":\"" + TimeToString(g_debug_data[d].first_bar_time, TIME_DATE|TIME_MINUTES) + "\",";
            json += "\"last_bar_time\":\"" + TimeToString(g_debug_data[d].last_bar_time, TIME_DATE|TIME_MINUTES) + "\",";
            json += "\"period_start\":\"" + TimeToString(g_debug_data[d].period_start, TIME_DATE|TIME_MINUTES) + "\",";
            json += "\"period_end\":\"" + TimeToString(g_debug_data[d].period_end, TIME_DATE|TIME_MINUTES) + "\",";
            json += "\"end_time\":\"" + TimeToString(g_debug_data[d].end_time, TIME_DATE|TIME_MINUTES) + "\",";
            json += "\"result_high\":" + DoubleToString(g_debug_data[d].result_high, digits) + ",";
            json += "\"result_low\":" + DoubleToString(g_debug_data[d].result_low, digits) + ",";
            json += "\"fallback_used\":" + (g_debug_data[d].fallback_used ? "true" : "false");
            json += "}";
        }
        
        json += "]";
    }
    
    json += "}";
    
    return json;
}

//+------------------------------------------------------------------+
//| Calculate average range for specific symbol and timeframe       |
//+------------------------------------------------------------------+
double CalculateAverageRangeForSymbol(string symbol, ENUM_TIMEFRAMES timeframe)
{
    MqlRates rates[];
    int periods_to_request = InpPeriodsLookback + 50;
    
    // When using pretend time, get bars from BEFORE pretend moment
    int copied;
    if(g_use_pretend_time && g_pretend_datetime > 0)
    {
        // Find the bar at pretend time and copy from there backward
        int start_bar = iBarShift(symbol, timeframe, g_pretend_datetime, true);
        if(start_bar < 0) start_bar = 0;
        
        // Copy historical bars starting from pretend time bar
        copied = CopyRates(symbol, timeframe, start_bar, periods_to_request, rates);
    }
    else
    {
        // Normal mode: get recent bars
        copied = CopyRates(symbol, timeframe, 0, periods_to_request, rates);
    }
    
    if(copied <= 0)
    {
        return 100.0; // Fallback value
    }
    
    double point = SymbolInfoDouble(symbol, SYMBOL_POINT);
    if(point <= 0) return 100.0;
    
    double totalRange = 0.0;
    int validPeriods = 0;
    
    // When using pretend time with historical bars, start from i=0
    // When in normal mode, also start from i=0 for completed periods
    // (matches original indicator logic)
    for(int i = 0; i < copied && validPeriods < InpPeriodsLookback; i++)
    {
        // Skip bars at or after pretend time
        if(g_use_pretend_time && rates[i].time >= g_pretend_datetime)
            continue;
            
        if(InpIgnoreSunday && timeframe < PERIOD_W1)
        {
            MqlDateTime dt;
            TimeToStruct(rates[i].time, dt);
            if(dt.day_of_week == 0) continue;
        }
        
        double periodRange = (rates[i].high - rates[i].low) / point;
        if(periodRange > 0)
        {
            totalRange += periodRange;
            validPeriods++;
        }
    }
    
    if(validPeriods > 0)
    {
        return totalRange / validPeriods;
    }
    
    return 100.0;
}

//+------------------------------------------------------------------+
//| Get Period High or Low for specific symbol                      |
//+------------------------------------------------------------------+
double GetPeriodHighLowForSymbol(string symbol, datetime period_start, datetime period_end, ENUM_TIMEFRAMES timeframe, bool get_high, double current_price)
{
    double result = get_high ? 0 : DBL_MAX;
    
    // Debug tracking
    int bars_scanned = 0;
    int bars_skipped_future = 0;
    int bars_skipped_past = 0;
    int bars_skipped_outside_period = 0;
    int bars_processed = 0;
    datetime first_bar_processed = 0;
    datetime last_bar_processed = 0;
    
    // Use M1 bars to scan for high/low (same as original GetPeriodHighLowForTimeframe logic)
    ENUM_TIMEFRAMES scan_tf = PERIOD_M1;
    
    // When using pretend time, only scan bars that existed before pretend moment
    datetime end_time = g_use_pretend_time ? g_pretend_datetime : TimeCurrent();
    
    // Find starting bar index based on end_time
    // In MT5, bar 0 = most recent, higher indices = older bars
    // So we need to start from the bar at end_time and scan backward (increasing indices)
    int start_bar = 0;
    if(g_use_pretend_time)
    {
        // Find the bar that was forming at pretend time (or the closest bar before it)
        start_bar = iBarShift(symbol, scan_tf, g_pretend_datetime, true);
        if(start_bar < 0) start_bar = 0;  // Fallback if bar not found
    }
    
    // Scan M1 bars for high/low within the period
    int total_bars = iBars(symbol, scan_tf);
    int max_bars = MathMin(total_bars - start_bar, 10000);  // Limit scan from starting point
    bool found_any = false;
    
    for(int i = start_bar; i < start_bar + max_bars; i++)
    {
        datetime bar_time = iTime(symbol, scan_tf, i);
        bars_scanned++;
        
        // Stop if bar time is invalid
        if(bar_time == 0)
            break;
        
        // Stop if bar is too old (before period start)
        if(bar_time < period_start)
        {
            bars_skipped_past++;
            break;
        }
            
        // Skip bars that start at or after end_time (not formed yet at pretend moment)
        if(bar_time >= end_time)
        {
            bars_skipped_future++;
            continue;
        }
            
        // Skip bars outside our period (after period end)
        if(bar_time >= period_end)
        {
            bars_skipped_outside_period++;
            continue;
        }
        
        // Track first and last processed bars
        if(bars_processed == 0)
            first_bar_processed = bar_time;
        last_bar_processed = bar_time;
        
        bars_processed++;
        found_any = true;
        
        if(get_high)
        {
            double high_price = iHigh(symbol, scan_tf, i);
            if(high_price > result) result = high_price;
        }
        else
        {
            double low_price = iLow(symbol, scan_tf, i);
            if(low_price < result) result = low_price;
        }
    }
    
    bool fallback_used = false;
    
    // If no bars found in period (period just started), use current price
    if(!found_any || (get_high && result == 0) || (!get_high && result == DBL_MAX))
    {
        result = current_price;
        fallback_used = true;
    }
    
    // Store debug info if in debug mode
    if(InpDebugMode)
    {
        int idx = ArraySize(g_debug_data);
        ArrayResize(g_debug_data, idx + 1);
        g_debug_data[idx].timeframe = EnumToString(timeframe) + (get_high ? "_HIGH" : "_LOW");
        g_debug_data[idx].bars_scanned = bars_scanned;
        g_debug_data[idx].bars_skipped_future = bars_skipped_future;
        g_debug_data[idx].bars_skipped_past = bars_skipped_past;
        g_debug_data[idx].bars_skipped_outside_period = bars_skipped_outside_period;
        g_debug_data[idx].bars_processed = bars_processed;
        g_debug_data[idx].first_bar_time = first_bar_processed;
        g_debug_data[idx].last_bar_time = last_bar_processed;
        g_debug_data[idx].period_start = period_start;
        g_debug_data[idx].period_end = period_end;
        g_debug_data[idx].end_time = end_time;
        g_debug_data[idx].result_high = get_high ? result : 0;
        g_debug_data[idx].result_low = get_high ? 0 : result;
        g_debug_data[idx].fallback_used = fallback_used;
    }
    
    return result;
}

//+------------------------------------------------------------------+
//| Build quarters theory JSON data                                 |
//+------------------------------------------------------------------+
string BuildQuartersJson()
{
    string json = "{";
    json += "\"timestamp\":\"" + TimeToString(TimeCurrent(), TIME_DATE|TIME_MINUTES) + "\",";
    json += "\"analysis_timeframe\":\"" + EnumToString(InpRangeTimeframe) + "\",";
    json += "\"average_range\":" + DoubleToString(g_averagePeriodRange, 1) + ",";
    json += "\"quota_value\":" + DoubleToString(g_dynamicQuotaValue, 1) + ",";
    json += "\"timeframes\":[";
    
    // Filter timeframes - remove M1 and M5, start from M15
    string timeframe_names[] = {"M15", "M30", "H1", "H4", "H6", "H12", "D1", "W1", "MN1"};
    int tf_indices[] = {2, 3, 4, 5, 6, 7, 8, 9, 10};
    
    for(int i = 0; i < ArraySize(timeframe_names); i++)
    {
        if(i > 0) json += ",";
        
        int tf_index = tf_indices[i];
        
        json += "{";
        json += "\"timeframe\":\"" + timeframe_names[i] + "\",";
        json += "\"high\":" + DoubleToString(tf_data[tf_index].high, _Digits) + ",";
        json += "\"low\":" + DoubleToString(tf_data[tf_index].low, _Digits) + ",";
        json += "\"low_percentage\":" + DoubleToString(tf_data[tf_index].low_percentage, 0) + ",";
        json += "\"high_percentage\":" + DoubleToString(tf_data[tf_index].high_percentage, 0) + ",";
        json += "\"time_quarter\":\"" + tf_data[tf_index].time_quarter + "\",";
        json += "\"countdown\":\"" + tf_data[tf_index].countdown + "\",";
        json += "\"quota_value\":" + DoubleToString(tf_data[tf_index].quota_value, 1);
        json += "}";
    }
    
    json += "]";
    json += "}";
    
    return json;
}

//+------------------------------------------------------------------+
//| Send data to API                                                 |
//+------------------------------------------------------------------+
void SendDataToApi(string request_id, string symbol, string quarters_json)
{
    char result_data[];
    string result_headers;
    int timeout = 20000;  // Increased from 5000ms to 20000ms (20 seconds) for sending large JSON payloads
    
    // Build POST data
    string post_data = "request_id=" + request_id;
    post_data += "&symbol=" + symbol;
    post_data += "&quarters_data=" + UrlEncode(quarters_json);
    
    char post_array[];
    StringToCharArray(post_data, post_array, 0, StringLen(post_data), CP_UTF8);
    
    DebugPrint("Sending data to API for request_id: " + request_id);
    
    int res = WebRequest(
        "POST",
        InpApiUrl,
        "Content-Type: application/x-www-form-urlencoded\r\n",
        NULL,
        timeout,
        post_array,
        ArraySize(post_array),
        result_data,
        result_headers
    );
    
    if(res == -1)
    {
        int error = GetLastError();
        DebugPrint("WebRequest error sending data: " + IntegerToString(error));
        return;
    }
    
    if(res == 200)
    {
        string response = CharArrayToString(result_data, 0, WHOLE_ARRAY, CP_UTF8);
        DebugPrint("API Send Response: " + response);
    }
    else
    {
        DebugPrint("API returned status code: " + IntegerToString(res));
    }
}

//+------------------------------------------------------------------+
//| Extract value from JSON string (simple parser)                  |
//+------------------------------------------------------------------+
string ExtractJsonValue(string json, string key)
{
    string search_key = "\"" + key + "\"";
    int pos = StringFind(json, search_key);
    
    if(pos < 0)
        return "";
    
    // Find the value after the colon
    int colon_pos = StringFind(json, ":", pos);
    if(colon_pos < 0)
        return "";
    
    // Skip whitespace and quotes
    int value_start = colon_pos + 1;
    while(value_start < StringLen(json) && 
          (StringGetCharacter(json, value_start) == ' ' || 
           StringGetCharacter(json, value_start) == '\t' ||
           StringGetCharacter(json, value_start) == '\n'))
    {
        value_start++;
    }
    
    bool is_string = false;
    if(StringGetCharacter(json, value_start) == '"')
    {
        is_string = true;
        value_start++;
    }
    
    // Find the end of the value
    int value_end = value_start;
    if(is_string)
    {
        // Find closing quote
        while(value_end < StringLen(json) && StringGetCharacter(json, value_end) != '"')
        {
            if(StringGetCharacter(json, value_end) == '\\')
                value_end++; // Skip escaped character
            value_end++;
        }
    }
    else
    {
        // Find comma, closing brace, or end of string
        while(value_end < StringLen(json) && 
              StringGetCharacter(json, value_end) != ',' && 
              StringGetCharacter(json, value_end) != '}' &&
              StringGetCharacter(json, value_end) != '\n')
        {
            value_end++;
        }
    }
    
    return StringSubstr(json, value_start, value_end - value_start);
}

//+------------------------------------------------------------------+
//| URL Encode string                                                |
//+------------------------------------------------------------------+
string UrlEncode(string str)
{
    string result = "";
    int len = StringLen(str);
    
    for(int i = 0; i < len; i++)
    {
        ushort ch = StringGetCharacter(str, i);
        
        if((ch >= 'A' && ch <= 'Z') || 
           (ch >= 'a' && ch <= 'z') || 
           (ch >= '0' && ch <= '9') ||
           ch == '-' || ch == '_' || ch == '.' || ch == '~')
        {
            result += ShortToString(ch);
        }
        else if(ch == ' ')
        {
            result += "+";
        }
        else
        {
            result += StringFormat("%%%02X", ch);
        }
    }
    
    return result;
}
//+------------------------------------------------------------------+
