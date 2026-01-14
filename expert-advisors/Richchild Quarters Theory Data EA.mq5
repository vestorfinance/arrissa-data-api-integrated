//+------------------------------------------------------------------+
//|                             Richchild Quarters Theory EA.mq5     |
//|                            Copyright 2025, Arrissa Technologies. |
//|                                            https://arrissa.trade |
//+------------------------------------------------------------------+
#property copyright "Copyright 2026, Arrissa Technologies, Flowbase."
#property link      "https://flowbase.store"
#property version   "1.2"
#property strict

//--- input parameters for period range calculation
input int InpPeriodsLookback = 30;        // Periods to look back for average calculation
input bool InpIgnoreSunday = true;        // Ignore Sundays in calculation (for traditional markets)
input bool InpDebugMode = false;          // Enable debug output
input int PrintIntervalSeconds = 60;      // Print interval in seconds

//--- input parameters for API
input string InpApiUrl = "http://localhost/quarters-theory-api-v1/quarters-theory-api.php"; // API URL
input bool InpEnableApi = true;           // Enable API communication
input int InpApiPollingSeconds = 2;       // API polling interval in seconds

//--- input parameters for indicator operation
input int DayStartHour = 0;               // Day start hour (broker time)

//--- global variables
ENUM_TIMEFRAMES InpRangeTimeframe = PERIOD_H4; // Current timeframe for range calculation
ENUM_TIMEFRAMES SavedRangeTimeframe = PERIOD_H4; // Saved timeframe to persist across chart changes

// Dynamic quota variables
double g_averagePeriodRange = 0.0;
double g_dynamicQuotaValue = 0.0;
int g_totalPeriodsAnalyzed = 0;
bool g_calculationComplete = false;

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

// File handling constants
string SETTINGS_FILENAME = "DynamicRange_Settings.txt";

// Print control variables
datetime last_print_time = 0;

// API control variables
datetime last_api_poll_time = 0;
bool api_processing_lock = false;  // Prevent overlapping API requests

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
    // Reset calculation flag
    g_calculationComplete = false;
    
    // Load saved timeframe from file
    LoadTimeframeFromFile();
    InpRangeTimeframe = SavedRangeTimeframe;
    
    if(InpDebugMode)
        Print("Richchild Quarters Theory EA initialized");
    
    DebugPrint("Analysis Timeframe: " + EnumToString(InpRangeTimeframe));
    DebugPrint("Periods lookback: " + IntegerToString(InpPeriodsLookback) + " periods of " + EnumToString(InpRangeTimeframe));
    DebugPrint("Day starts at: " + IntegerToString(DayStartHour) + ":00");
    
    // Initialize timeframe data array
    InitializeTimeframeData();
    
    // Wait for data availability and calculate quota
    DebugPrint("Calculating Average Period Range for " + EnumToString(InpRangeTimeframe) + "...");
    
    if(!WaitForDataAvailability())
    {
        DebugPrint("WARNING: Data not immediately available. Will retry in OnTick.");
        g_averagePeriodRange = 100.0;
        g_dynamicQuotaValue = 25.0;
        g_totalPeriodsAnalyzed = 0;
        g_calculationComplete = false;
    }
    else
    {
        CalculateDynamicQuota();
    }
    
    // Initialize print timer
    last_print_time = TimeCurrent();
    
    // Initialize API polling timer
    last_api_poll_time = TimeCurrent();
    
    // Set timer to trigger every second for reliable printing
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
    
    // Save current timeframe selection to file
    SaveTimeframeToFile();
    
    if(InpDebugMode)
        Print("Richchild Quarters Theory EA deinitialized");
}

//+------------------------------------------------------------------+
//| Expert tick function                                             |
//+------------------------------------------------------------------+
void OnTick()
{
    // Retry calculation if not complete
    if(!g_calculationComplete)
    {
        DebugPrint("Retrying quota calculation in OnTick...");
        if(WaitForDataAvailability())
        {
            CalculateDynamicQuota();
            if(g_calculationComplete)
            {
                DebugPrint("Quota calculation completed successfully in OnTick");
            }
        }
    }
}

//+------------------------------------------------------------------+
//| Timer function - triggers every second                           |
//+------------------------------------------------------------------+
void OnTimer()
{
    // Retry calculation if not complete
    if(!g_calculationComplete)
    {
        if(WaitForDataAvailability())
        {
            CalculateDynamicQuota();
        }
    }
    
    // Only proceed if calculation is complete
    if(!g_calculationComplete || g_averagePeriodRange <= 0 || g_dynamicQuotaValue <= 0)
    {
        return;
    }
    
    // Check if it's time to print
    datetime current_time = TimeCurrent();
    if(current_time - last_print_time >= PrintIntervalSeconds)
    {
        last_print_time = current_time;
        
        // Update all timeframe data
        UpdateAllTimeframeData();
        
        // Print the data
        PrintAllTimeframeData();
    }
    
    // Check if it's time to poll API
    if(InpEnableApi && current_time - last_api_poll_time >= InpApiPollingSeconds)
    {
        last_api_poll_time = current_time;
        PollApiForRequests();
    }
}

//+------------------------------------------------------------------+
//| Print all timeframe information                                  |
//+------------------------------------------------------------------+
void PrintAllTimeframeData()
{
    if(!InpDebugMode)
        return;
        
    Print("============================================");
    Print("Richchild Quarters Theory Data - ", TimeToString(TimeCurrent(), TIME_DATE|TIME_MINUTES));
    Print("Analysis Timeframe: ", EnumToString(InpRangeTimeframe));
    Print("Average Range: ", DoubleToString(g_averagePeriodRange, 1), " pts | Quota: ", DoubleToString(g_dynamicQuotaValue, 1), " pts");
    Print("--------------------------------------------");
    Print("TF       L%      H%    Quarter   Countdown");
    Print("--------------------------------------------");
    
    // Filter timeframes - remove M1 and M5, start from M15
    string timeframe_names[] = {"M15", "M30", "H1", "H4", "H6", "H12", "D1", "W1", "MN1"};
    int tf_indices[] = {2, 3, 4, 5, 6, 7, 8, 9, 10};
    
    for(int i = 0; i < ArraySize(timeframe_names); i++)
    {
        int tf_index = tf_indices[i];
        
        string display_text = StringFormat(
            "%-4s %6.0f%% %6.0f%%   %-9s %s",
            timeframe_names[i],
            tf_data[tf_index].low_percentage,
            tf_data[tf_index].high_percentage,
            tf_data[tf_index].time_quarter,
            tf_data[tf_index].countdown
        );
        
        Print(display_text);
    }
    
    Print("============================================");
}

//+------------------------------------------------------------------+
//| Wait for data availability                                       |
//+------------------------------------------------------------------+
bool WaitForDataAvailability()
{
    int max_attempts = 5;
    int attempt = 0;
    
    while(attempt < max_attempts)
    {
        MqlRates test_rates[];
        int copied = CopyRates(_Symbol, InpRangeTimeframe, 0, 10, test_rates);
        
        if(copied > 0)
        {
            DebugPrint("Data available after " + IntegerToString(attempt + 1) + " attempts");
            return true;
        }
        
        attempt++;
        if(attempt < max_attempts)
        {
            DebugPrint("Data not available, attempt " + IntegerToString(attempt) + "/" + IntegerToString(max_attempts) + ". Waiting...");
            Sleep(200);
        }
    }
    
    DebugPrint("WARNING: Could not verify data availability after " + IntegerToString(max_attempts) + " attempts");
    return false;
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
    for(int i = 0; i < 11; i++)
    {
        tf_data[i].quota_value = CalculateQuotaForTimeframe(tf_data[i].timeframe);
    }
}

//+------------------------------------------------------------------+
//| Load timeframe setting from file                                |
//+------------------------------------------------------------------+
void LoadTimeframeFromFile()
{
    string filename = SETTINGS_FILENAME;
    int file_handle = FileOpen(filename, FILE_READ|FILE_TXT|FILE_COMMON);
    
    if(file_handle != INVALID_HANDLE)
    {
        if(!FileIsEnding(file_handle))
        {
            string line = FileReadString(file_handle);
            if(StringFind(line, "TIMEFRAME=") == 0)
            {
                string tf_string = StringSubstr(line, 10);
                ENUM_TIMEFRAMES loaded_tf = StringToTimeframe(tf_string);
                if(loaded_tf != PERIOD_CURRENT)
                {
                    SavedRangeTimeframe = loaded_tf;
                    DebugPrint("Loaded timeframe from file: " + EnumToString(SavedRangeTimeframe));
                }
            }
        }
        FileClose(file_handle);
    }
    else
    {
        DebugPrint("Settings file not found, using default timeframe: " + EnumToString(SavedRangeTimeframe));
    }
}

//+------------------------------------------------------------------+
//| Save timeframe setting to file                                  |
//+------------------------------------------------------------------+
void SaveTimeframeToFile()
{
    string filename = SETTINGS_FILENAME;
    int file_handle = FileOpen(filename, FILE_WRITE|FILE_TXT|FILE_COMMON);
    
    if(file_handle != INVALID_HANDLE)
    {
        FileWriteString(file_handle, "TIMEFRAME=" + EnumToString(InpRangeTimeframe));
        FileClose(file_handle);
        DebugPrint("Saved timeframe to file: " + EnumToString(InpRangeTimeframe));
    }
    else
    {
        DebugPrint("Error saving timeframe to file. Error code: " + IntegerToString(GetLastError()));
    }
}

//+------------------------------------------------------------------+
//| Convert string to timeframe                                     |
//+------------------------------------------------------------------+
ENUM_TIMEFRAMES StringToTimeframe(string tf_string)
{
    if(tf_string == "PERIOD_M1") return PERIOD_M1;
    if(tf_string == "PERIOD_M5") return PERIOD_M5;
    if(tf_string == "PERIOD_M15") return PERIOD_M15;
    if(tf_string == "PERIOD_M30") return PERIOD_M30;
    if(tf_string == "PERIOD_H1") return PERIOD_H1;
    if(tf_string == "PERIOD_H4") return PERIOD_H4;
    if(tf_string == "PERIOD_H6") return PERIOD_H6;
    if(tf_string == "PERIOD_H12") return PERIOD_H12;
    if(tf_string == "PERIOD_D1") return PERIOD_D1;
    if(tf_string == "PERIOD_W1") return PERIOD_W1;
    if(tf_string == "PERIOD_MN1") return PERIOD_MN1;
    
    return PERIOD_CURRENT;
}

//+------------------------------------------------------------------+
//| Calculate countdown to end of period for specific timeframe     |
//+------------------------------------------------------------------+
string CalculateCountdown(ENUM_TIMEFRAMES timeframe)
{
    datetime current_time = TimeCurrent();
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
            datetime current_time = TimeCurrent();
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
    datetime current_time = TimeCurrent();
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
//| Calculate quota for any timeframe                               |
//+------------------------------------------------------------------+
double CalculateQuotaForTimeframe(ENUM_TIMEFRAMES timeframe)
{
    MqlRates rates[];
    int periods_to_request = InpPeriodsLookback + 50;
    int copied = CopyRates(_Symbol, timeframe, 0, periods_to_request, rates);
    
    if(copied <= 0) 
    {
        datetime period_start = GetPeriodStart(timeframe);
        datetime period_end = GetPeriodEnd(timeframe);
        double high = GetPeriodHighLowForTimeframe(period_start, period_end, timeframe, true);
        double low = GetPeriodHighLowForTimeframe(period_start, period_end, timeframe, false);
        double range_in_points = (high - low) / _Point;
        double fallback_quota = range_in_points / 4.0;
        
        return fallback_quota;
    }
    
    double totalRange = 0.0;
    int validPeriods = 0;
    
    for(int i = 1; i < copied; i++)
    {
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
        return quota;
    }
    
    return 25.0;
}

//+------------------------------------------------------------------+
//| Calculate Dynamic Quota Value                                   |
//+------------------------------------------------------------------+
void CalculateDynamicQuota()
{
    g_calculationComplete = false;
    
    DebugPrint("=== CALCULATING DYNAMIC QUOTA ===");
    DebugPrint("Lookback: " + IntegerToString(InpPeriodsLookback) + " periods");
    DebugPrint("Timeframe: " + EnumToString(InpRangeTimeframe));
    
    int periods_to_request = InpPeriodsLookback + 50;
    int attempts = 0;
    int copied = 0;
    MqlRates rates[];
    
    while(attempts < 15 && copied <= 0)
    {
        ResetLastError();
        copied = CopyRates(_Symbol, InpRangeTimeframe, 0, periods_to_request, rates);
        int error_code = GetLastError();
        
        if(copied <= 0)
        {
            if(error_code == 4401)
            {
                DebugPrint("History data not available. Requesting data...");
                SeriesInfoInteger(_Symbol, InpRangeTimeframe, SERIES_TERMINAL_FIRSTDATE);
                SeriesInfoInteger(_Symbol, InpRangeTimeframe, SERIES_LASTBAR_DATE);
            }
            
            Sleep(500);
            attempts++;
        }
    }
    
    if(copied <= 0)
    {
        DebugPrint("ERROR: Could not copy rates after " + IntegerToString(attempts) + " attempts");
        
        switch(InpRangeTimeframe)
        {
            case PERIOD_M1:
            case PERIOD_M5:
            case PERIOD_M15:
            case PERIOD_M30:
                g_averagePeriodRange = 50.0;
                g_dynamicQuotaValue = 12.5;
                break;
            case PERIOD_H1:
            case PERIOD_H4:
            case PERIOD_H6:
            case PERIOD_H12:
                g_averagePeriodRange = 100.0;
                g_dynamicQuotaValue = 25.0;
                break;
            default:
                g_averagePeriodRange = 200.0;
                g_dynamicQuotaValue = 50.0;
                break;
        }
        
        g_totalPeriodsAnalyzed = 0;
        g_calculationComplete = true;
        return;
    }
    
    double symbolPoint = SymbolInfoDouble(_Symbol, SYMBOL_POINT);
    if(symbolPoint <= 0)
    {
        DebugPrint("ERROR: Invalid symbol point value");
        return;
    }
    
    double totalRange = 0.0;
    int validPeriods = 0;
    
    string symbol_name = _Symbol;
    bool isContinuousMarket = (StringFind(symbol_name, "BTC") >= 0 || 
                              StringFind(symbol_name, "ETH") >= 0 || 
                              StringFind(symbol_name, "CRYPTO") >= 0 ||
                              StringFind(symbol_name, "Volatility") >= 0);
    
    for(int i = 0; i < copied && validPeriods < InpPeriodsLookback; i++)
    {
        MqlDateTime dt;
        TimeToStruct(rates[i].time, dt);
        
        if(InpIgnoreSunday && !isContinuousMarket && dt.day_of_week == 0)
            continue;
        
        if(rates[i].high <= 0 || rates[i].low <= 0 || rates[i].high < rates[i].low)
            continue;
        
        double periodRange = (rates[i].high - rates[i].low) / symbolPoint;
        
        if(periodRange <= 0)
            continue;
        
        totalRange += periodRange;
        validPeriods++;
    }
    
    if(validPeriods > 0 && totalRange > 0)
    {
        g_averagePeriodRange = totalRange / validPeriods;
        g_dynamicQuotaValue = g_averagePeriodRange / 4.0;
        g_totalPeriodsAnalyzed = validPeriods;
        
        if(g_averagePeriodRange > 0 && g_dynamicQuotaValue > 0)
        {
            g_calculationComplete = true;
            
            DebugPrint("=== QUOTA CALCULATION RESULTS ===");
            DebugPrint("Valid periods: " + IntegerToString(validPeriods));
            DebugPrint("Average range: " + DoubleToString(g_averagePeriodRange, 1) + " points");
            DebugPrint("Quota value: " + DoubleToString(g_dynamicQuotaValue, 1) + " points");
        }
    }
    else
    {
        g_averagePeriodRange = 100.0;
        g_dynamicQuotaValue = 25.0;
        g_totalPeriodsAnalyzed = 0;
        g_calculationComplete = true;
    }
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
    
    DebugPrint("Request for symbol: " + requested_symbol);
    
    // Calculate data for the requested symbol
    string quarters_json = BuildQuartersJsonForSymbol(requested_symbol);
    
    // Send data to API
    SendDataToApi(request_id, requested_symbol, quarters_json);
}

//+------------------------------------------------------------------+
//| Build quarters theory JSON data for specific symbol             |
//+------------------------------------------------------------------+
string BuildQuartersJsonForSymbol(string symbol)
{
    // Get current price for the symbol
    double current_price = SymbolInfoDouble(symbol, SYMBOL_BID);
    double point = SymbolInfoDouble(symbol, SYMBOL_POINT);
    int digits = (int)SymbolInfoInteger(symbol, SYMBOL_DIGITS);
    
    string json = "{";
    json += "\"timestamp\":\"" + TimeToString(TimeCurrent(), TIME_DATE|TIME_MINUTES) + "\",";
    json += "\"analysis_timeframe\":\"" + EnumToString(InpRangeTimeframe) + "\",";
    
    // Calculate average range for this symbol
    double avg_range = CalculateAverageRangeForSymbol(symbol, InpRangeTimeframe);
    double quota = avg_range / 4.0;
    
    json += "\"average_range\":" + DoubleToString(avg_range, 1) + ",";
    json += "\"quota_value\":" + DoubleToString(quota, 1) + ",";
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
        double high = GetPeriodHighLowForSymbol(symbol, period_start, period_end, tf, true);
        double low = GetPeriodHighLowForSymbol(symbol, period_start, period_end, tf, false);
        
        // Calculate quota for this timeframe
        double tf_quota = CalculateAverageRangeForSymbol(symbol, tf) / 4.0;
        double quota_price = tf_quota * point;
        
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
        datetime current_time = TimeCurrent();
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
        json += "\"quota_value\":" + DoubleToString(tf_quota, 1);
        json += "}";
    }
    
    json += "]";
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
    int copied = CopyRates(symbol, timeframe, 0, periods_to_request, rates);
    
    if(copied <= 0)
    {
        return 100.0; // Fallback value
    }
    
    double point = SymbolInfoDouble(symbol, SYMBOL_POINT);
    if(point <= 0) return 100.0;
    
    double totalRange = 0.0;
    int validPeriods = 0;
    
    for(int i = 1; i < copied && validPeriods < InpPeriodsLookback; i++)
    {
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
double GetPeriodHighLowForSymbol(string symbol, datetime period_start, datetime period_end, ENUM_TIMEFRAMES timeframe, bool get_high)
{
    double result = get_high ? 0 : DBL_MAX;
    
    // Try using CopyRates for the period
    if(timeframe == PERIOD_W1 || timeframe == PERIOD_MN1)
    {
        MqlRates rates[];
        int bars_to_copy = (int)((period_end - period_start) / PeriodSeconds(timeframe)) + 5;
        int copied = CopyRates(symbol, timeframe, period_start, bars_to_copy, rates);
        
        if(copied > 0)
        {
            for(int i = 0; i < copied; i++)
            {
                if(rates[i].time >= period_start && rates[i].time < period_end)
                {
                    if(get_high)
                    {
                        if(rates[i].high > result) result = rates[i].high;
                    }
                    else
                    {
                        if(rates[i].low < result) result = rates[i].low;
                    }
                }
            }
        }
    }
    
    // Optimized fallback: use the timeframe's own bars instead of M1 for much faster processing
    if((get_high && result == 0) || (!get_high && result == DBL_MAX))
    {
        result = get_high ? 0 : DBL_MAX;
        
        // Use the timeframe we're analyzing instead of M1 - MUCH faster
        ENUM_TIMEFRAMES scan_tf = (timeframe == PERIOD_W1 || timeframe == PERIOD_MN1) ? PERIOD_H4 : timeframe;
        int total_bars = iBars(symbol, scan_tf);
        int max_bars = MathMin(total_bars, 500);  // Reduced from 2000 and using higher timeframe
        
        for(int i = 0; i < max_bars; i++)
        {
            datetime bar_time = iTime(symbol, scan_tf, i);
            if(bar_time == 0 || bar_time < period_start) break;
            if(bar_time >= period_end) continue;
            
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
