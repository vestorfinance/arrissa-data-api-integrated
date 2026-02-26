//+------------------------------------------------------------------+
//|                                     Richchild Quaters Theory.mq5 |
//|                            Copyright 2025, Arrissa Technologies. |
//|                                            https://arrissa.trade |
//+------------------------------------------------------------------+
#property copyright "Copyright 2026, Arrissa Technologies, Flowbase."
#property link      "https://flowbase.store"
#property version   "1.1"
#property indicator_chart_window
#property indicator_buffers 0
#property indicator_plots   0
#property strict
//#property icon "\\Images\\RichchildQuatersTheory.ico"

//--- input parameters for period range calculation
input int InpPeriodsLookback = 30;        // Periods to look back for average calculation
input bool InpIgnoreSunday = true;        // Ignore Sundays in calculation (for traditional markets)
bool InpDebugMode = false;          // Enable debug output

//--- input parameters for indicator operation
input int DayStartHour = 0;               // Day start hour (broker time)
input bool EnableAlerts = true;           // Enable alerts
input bool EnableTerminalAlerts = true;   // Enable terminal alerts
input bool EnableEmailAlerts = false;     // Enable email alerts

//--- input parameters for visual separators
input bool ShowPeriodSeparators = true;   // Show period separators
input int PeriodSeparatorThickness = 5;   // Period separator thickness (1-5)
input bool ShowQuarterSeparators = true;  // Show quarter separators within periods
input color QuarterSeparatorColor = clrDarkGray; // Quarter separator color
input int QuarterSeparatorThickness = 3;  // Quarter separator thickness (1-5)

//--- input parameters for data collection
input bool DataCollection = false;        // Enable JSON data collection
input string DataFilePrefix = "RQT_Data"; // JSON file prefix for data collection

//--- global variables
ENUM_TIMEFRAMES InpRangeTimeframe = PERIOD_H4; // Current timeframe for range calculation
datetime last_processed_period = 0;
bool show_high_lines = true;
bool show_low_lines = true;
bool is_dark_theme = true;

// Alert tracking variables
bool high_25_alert_triggered = false;
bool low_25_alert_triggered = false;
double last_high_25_price = 0;
double last_low_25_price = 0;
datetime last_high_time = 0;
datetime last_low_time = 0;

// Dynamic quota variables
double g_averagePeriodRange = 0.0;
double g_dynamicQuotaValue = 0.0;
int g_totalPeriodsAnalyzed = 0;
bool g_calculationComplete = false;  // Flag to ensure calculation is done before drawing

// Countdown update variables
datetime last_countdown_update = 0;

// Data collection variables
datetime last_data_save_time = 0;
string current_json_filename = "";

// Symbol restriction variables
bool g_allowWork = true;  // Controls whether the indicator functions

// ✅ CRITICAL FIX: Add variables to track when lines need redrawing
datetime g_lastBarTime = 0;           // Track last bar time
datetime g_lastPeriodCalculated = 0;  // Track last period we calculated lines for
bool g_forceRedraw = false;           // Force redraw flag

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

//+------------------------------------------------------------------+
//| Debug Print Function - MOVED TO TOP                             |
//+------------------------------------------------------------------+
void DebugPrint(string message)
{
    if(InpDebugMode)
    {
        Print("DEBUG: ", message);
    }
}

//--- FIXED: Check symbol but don't fail initialization
bool IsSymbolAllowed()
{
    string current_symbol = _Symbol;
    
    // Check if symbol is exactly EURUSD OR exactly "Volatility 50 (1s) Index"
    if(current_symbol == "EURUSD" || current_symbol == "Volatility 50 (1s) Index")
    {
        return true;
    }
    
    return false;
}

//+------------------------------------------------------------------+
//| Custom indicator initialization function                         |
//+------------------------------------------------------------------+
int OnInit()
{
    /* // ✅ FIXED: Check symbol but ALWAYS return INIT_SUCCEEDED
    if(!IsSymbolAllowed())
    {
        g_allowWork = false;
        Comment("Free version: Works only on EURUSD or Volatility 50 (1s) Index\nCurrent symbol: " + _Symbol);
        Print("Richchild Quaters Theory: Limited to EURUSD and Volatility 50 (1s) Index in free version");
        return(INIT_SUCCEEDED); // ✅ CRITICAL: Never return INIT_FAILED
    }*/
    
    g_allowWork = true;
    Comment(""); // Clear any restriction message
    
    // Reset calculation flag
    g_calculationComplete = false;
    
    // ✅ CRITICAL FIX: Initialize tracking variables
    g_lastBarTime = 0;
    g_lastPeriodCalculated = 0;
    g_forceRedraw = true; // Force initial draw
    
    // ONLY show initialization message
    Print("Richchild Quaters Theory Indicator initialized");
    
    // Only show detailed info if debug is enabled
    DebugPrint("Analysis Timeframe: " + EnumToString(InpRangeTimeframe));
    DebugPrint("Periods lookback: " + IntegerToString(InpPeriodsLookback) + " periods of " + EnumToString(InpRangeTimeframe));
    DebugPrint("Day starts at: " + IntegerToString(DayStartHour) + ":00");
    
    // Validate thickness inputs
    if(PeriodSeparatorThickness < 1 || PeriodSeparatorThickness > 5)
    {
        DebugPrint("Warning: Period separator thickness must be 1-5. Using default value 5.");
    }
    if(QuarterSeparatorThickness < 1 || QuarterSeparatorThickness > 5)
    {
        DebugPrint("Warning: Quarter separator thickness must be 1-5. Using default value 3.");
    }
    
    // Initialize timeframe data array
    InitializeTimeframeData();
    
    // CRITICAL: Wait for data availability and calculate quota SAFELY
    DebugPrint("Calculating Average Period Range for " + EnumToString(InpRangeTimeframe) + "...");
    
    // ENHANCED: Wait for data availability before calculation
    if(!WaitForDataAvailability())
    {
        DebugPrint("WARNING: Data not immediately available. Will retry in OnCalculate.");
        // Set safe default values
        g_averagePeriodRange = 100.0;
        g_dynamicQuotaValue = 25.0;
        g_totalPeriodsAnalyzed = 0;
        g_calculationComplete = false;
    }
    else
    {
        CalculateDynamicQuota();
    }
    
    // Always create UI elements regardless of calculation status
    CreateToggleButtons();
    CreateTimeframeButtons();
    
    // Apply initial theme
    ApplyTheme();
    
    // Set indicator short name
    IndicatorSetString(INDICATOR_SHORTNAME, "Richchild Quaters Theory (" + EnumToString(InpRangeTimeframe) + ")");
    
    // Only draw if calculation is complete AND symbol is allowed
    if(g_allowWork && g_calculationComplete && g_averagePeriodRange > 0 && g_dynamicQuotaValue > 0)
    {
        DebugPrint("Calculation successful. Drawing initial elements...");
        DrawPeriodSeparators();
        FindAndMarkPeriodHighLow();
        
        // ENSURE DISPLAY IS CREATED ON INIT - BACKGROUND FIRST, THEN TEXT
        UpdateAllTimeframeData();
        DisplayAllTimeframeInfo();
        
        // Initialize countdown update timer
        last_countdown_update = TimeCurrent();
        
        // Force chart redraw
        ChartRedraw();
    }
    else
    {
        DebugPrint("Calculation not complete yet or symbol restricted. Will complete in OnCalculate.");
        // Still create basic display if symbol is allowed
        if(g_allowWork)
        {
            UpdateAllTimeframeData();
            DisplayAllTimeframeInfo();
        }
    }
    
    return(INIT_SUCCEEDED); // ✅ ALWAYS return success
}

//+------------------------------------------------------------------+
//| Wait for data availability (NEW FUNCTION)                       |
//+------------------------------------------------------------------+
bool WaitForDataAvailability()
{
    int max_attempts = 5;
    int attempt = 0;
    
    while(attempt < max_attempts)
    {
        // Check if we can get at least some basic data
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
            Sleep(200); // Wait 200ms between attempts
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
                    InpRangeTimeframe = loaded_tf;
                    DebugPrint("Loaded timeframe from file: " + EnumToString(InpRangeTimeframe));
                }
            }
        }
        FileClose(file_handle);
    }
    else
    {
        DebugPrint("Settings file not found, using default timeframe: " + EnumToString(InpRangeTimeframe));
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
    
    return PERIOD_CURRENT; // Invalid timeframe
}

//+------------------------------------------------------------------+
//| Write JSON data to sequential files                              |
//+------------------------------------------------------------------+
void WriteJSONData()
{
    if(!DataCollection) return;
    
    // Get current OHLC
    MqlRates rates[];
    if(CopyRates(_Symbol, PERIOD_M1, 0, 1, rates) <= 0) return;
    
    datetime current_time = rates[0].time;
    
    // Check if minute has changed
    if(current_time == last_data_save_time) return;
    last_data_save_time = current_time;
    
    // Generate readable filename with date and time
    MqlDateTime dt;
    TimeToStruct(current_time, dt);
    
    string readable_datetime = StringFormat("%04d-%02d-%02d_%02d-%02d-%02d",
                                             dt.year, dt.mon, dt.day,
                                             dt.hour, dt.min, dt.sec);
    
    string request_id = DataFilePrefix + "_" + GenerateRequestId();
    string filename = DataFilePrefix + "_" + readable_datetime + ".json";
    
    // Build JSON structure matching the example format
    string json = "[\n  {\n";
    json += "    \"arrissa_data\": {\n";
    json += "      \"request_id\": \"" + request_id + "\",\n";
    json += "      \"symbol\": \"" + _Symbol + "\",\n";
    json += "      \"quarters_data\": {\n";
    json += "        \"timestamp\": \"" + TimeToString(current_time, TIME_DATE|TIME_MINUTES) + "\",\n";
    json += "        \"analysis_timeframe\": \"" + EnumToString(InpRangeTimeframe) + "\",\n";
    json += "        \"average_range\": " + DoubleToString(g_averagePeriodRange, 0) + ",\n";
    json += "        \"quota_value\": " + DoubleToString(g_dynamicQuotaValue, 0) + ",\n";
    json += "        \"timeframes\": [\n";
    
    // Add timeframe data (M15, M30, H1, H4, H6, H12, D1, W1, MN1)
    int tf_indices[] = {2, 3, 4, 5, 6, 7, 8, 9, 10};
    string tf_names[] = {"M15", "M30", "H1", "H4", "H6", "H12", "D1", "W1", "MN1"};
    
    for(int i = 0; i < ArraySize(tf_indices); i++)
    {
        int idx = tf_indices[i];
        json += "          {\n";
        json += "            \"timeframe\": \"" + tf_names[i] + "\",\n";
        json += "            \"high\": " + DoubleToString(tf_data[idx].high, _Digits) + ",\n";
        json += "            \"low\": " + DoubleToString(tf_data[idx].low, _Digits) + ",\n";
        json += "            \"low_percentage\": " + DoubleToString(tf_data[idx].low_percentage, 0) + ",\n";
        json += "            \"high_percentage\": " + DoubleToString(tf_data[idx].high_percentage, 0) + ",\n";
        json += "            \"time_quarter\": \"" + tf_data[idx].time_quarter + "\",\n";
        json += "            \"countdown\": \"" + tf_data[idx].countdown + "\",\n";
        json += "            \"quota_value\": " + DoubleToString(tf_data[idx].quota_value, 1) + "\n";
        json += "          }";
        if(i < ArraySize(tf_indices) - 1) json += ",";
        json += "\n";
    }
    
    json += "        ]\n";
    json += "      },\n";
    json += "      \"timestamp\": \"" + TimeToString(TimeCurrent(), TIME_DATE) + " " + TimeToString(TimeCurrent(), TIME_MINUTES|TIME_SECONDS) + "\"\n";
    json += "    }\n";
    json += "  }\n";
    json += "]\n";
    
    // Write to file
    int file_handle = FileOpen(filename, FILE_WRITE|FILE_TXT|FILE_ANSI);
    if(file_handle != INVALID_HANDLE)
    {
        FileWriteString(file_handle, json);
        FileClose(file_handle);
        DebugPrint("JSON data saved to: " + filename);
    }
    else
    {
        DebugPrint("Error saving JSON file: " + filename + " Error code: " + IntegerToString(GetLastError()));
    }
}

//+------------------------------------------------------------------+
//| Generate unique request ID                                       |
//+------------------------------------------------------------------+
string GenerateRequestId()
{
    datetime current = TimeCurrent();
    MathSrand((int)current);
    double random = MathRand() / 32767.0;
    return "quarters_" + IntegerToString(current, 16) + DoubleToString(random, 8);
}

//+------------------------------------------------------------------+
//| Custom indicator deinitialization function                      |
//+------------------------------------------------------------------+
void OnDeinit(const int reason)
{
    // Clean up objects when indicator is removed
    ObjectsDeleteAll(0, "DynamicRange_");
    ObjectDelete(0, "ToggleHighBtn");
    ObjectDelete(0, "ToggleLowBtn");
    ObjectDelete(0, "ThemeToggleBtn");
    
    // Delete timeframe buttons
    string timeframes[] = {"1M", "5M", "15M", "30M", "1H", "4H", "6H", "12H", "D1", "W1", "MN1"};
    for(int i = 0; i < ArraySize(timeframes); i++)
    {
        ObjectDelete(0, "TF_" + timeframes[i] + "_Btn");
    }
}

//+------------------------------------------------------------------+
//| ✅ CRITICAL FIX: Enhanced OnCalculate with proper line redrawing |
//+------------------------------------------------------------------+
int OnCalculate(const int rates_total,
                const int prev_calculated,
                const datetime &time[],
                const double &open[],
                const double &high[],
                const double &low[],
                const double &close[],
                const long &tick_volume[],
                const long &volume[],
                const int &spread[])
{
    // ✅ FIXED: Check if work is allowed, but don't fail
    if(!g_allowWork)
    {
        return(rates_total); // Do nothing but don't fail
    }
    
    // RETRY CALCULATION IF NOT COMPLETE
    if(!g_calculationComplete)
    {
        DebugPrint("Retrying quota calculation in OnCalculate...");
        if(WaitForDataAvailability())
        {
            CalculateDynamicQuota();
            if(g_calculationComplete)
            {
                DebugPrint("Quota calculation completed successfully in OnCalculate");
                g_forceRedraw = true; // Force redraw after successful calculation
            }
        }
    }
    
    // Only proceed with normal operations if calculation is complete
    if(!g_calculationComplete || g_averagePeriodRange <= 0 || g_dynamicQuotaValue <= 0)
    {
        DebugPrint("Skipping OnCalculate - calculation not complete or invalid values");
        return(rates_total);
    }
    
    // ✅ CRITICAL FIX: Detect new bars and period changes
    datetime current_time = TimeCurrent();
    datetime current_bar_time = (rates_total > 0) ? time[rates_total-1] : current_time;
    datetime current_period = GetCurrentPeriodStart();
    
    // Check for new bar
    bool new_bar_detected = (current_bar_time != g_lastBarTime);
    
    // Check for new period
    bool new_period_detected = (current_period != g_lastPeriodCalculated);
    
    // Check if we need to redraw lines
    bool should_redraw_lines = g_forceRedraw || new_period_detected || new_bar_detected;
    
    if(new_bar_detected)
    {
        DebugPrint("NEW BAR DETECTED: " + TimeToString(current_bar_time) + " (Previous: " + TimeToString(g_lastBarTime) + ")");
        g_lastBarTime = current_bar_time;
    }
    
    if(new_period_detected)
    {
        DebugPrint("NEW PERIOD DETECTED: " + TimeToString(current_period) + " (Previous: " + TimeToString(g_lastPeriodCalculated) + ")");
        g_lastPeriodCalculated = current_period;
        
        // Recalculate dynamic quota for new period
        DebugPrint("Recalculating quota for new period...");
        CalculateDynamicQuota();
    }
    
    // ✅ CRITICAL FIX: Always redraw lines when needed
    if(should_redraw_lines)
    {
        DebugPrint("REDRAWING LINES - Reason: " + 
                  (g_forceRedraw ? "Force " : "") +
                  (new_period_detected ? "NewPeriod " : "") +
                  (new_bar_detected ? "NewBar " : ""));
        
        // Clear force redraw flag
        g_forceRedraw = false;
        
        // Redraw all elements
        DrawPeriodSeparators();
        FindAndMarkPeriodHighLow();
        
        DebugPrint("Lines redrawn successfully");
    }
    
    // DETECT TIMEFRAME CHANGE AND RECREATE DISPLAY
    static ENUM_TIMEFRAMES last_chart_timeframe = PERIOD_CURRENT;
    ENUM_TIMEFRAMES current_chart_timeframe = Period();
    
    if(current_chart_timeframe != last_chart_timeframe)
    {
        DebugPrint("Chart timeframe changed from " + EnumToString(last_chart_timeframe) + " to " + EnumToString(current_chart_timeframe));
        DebugPrint("Recreating display objects...");
        
        // Force recreation of all display objects
        ForceRecreateDisplay();
        g_forceRedraw = true; // Force line redraw too
        
        last_chart_timeframe = current_chart_timeframe;
    }
    
    // CHECK IF DISPLAY OBJECTS EXIST - RECREATE IF MISSING
    if(ObjectFind(0, "DynamicRange_Panel_Background") < 0 || 
       ObjectFind(0, "DynamicRange_Table_Header") < 0 ||
       ObjectFind(0, "DynamicRange_Table_Row_0") < 0)
    {
        DebugPrint("Display objects missing - recreating...");
        ForceRecreateDisplay();
    }
    
    // Update countdown every second
    if(current_time != last_countdown_update)
    {
        last_countdown_update = current_time;
        
        // Update all timeframe data including countdown
        UpdateAllTimeframeData();
        DisplayAllTimeframeInfo();
        
        // Write JSON data every minute
        WriteJSONData();
        
        // Force chart redraw to show updated countdown
        ChartRedraw();
    }
    
    // Check alerts
    CheckAlerts();
    
    // Return value of prev_calculated for next call
    return(rates_total);
}

//+------------------------------------------------------------------+
//| Force recreation of display objects                             |
//+------------------------------------------------------------------+
void ForceRecreateDisplay()
{
    // Remove all existing display objects
    for(int i = ObjectsTotal(0) - 1; i >= 0; i--)
    {
        string obj_name = ObjectName(0, i);
        if(StringFind(obj_name, "DynamicRange_Table") == 0 || 
           StringFind(obj_name, "DynamicRange_Panel") == 0)
        {
            ObjectDelete(0, obj_name);
        }
    }
    
    // Force immediate recreation
    UpdateAllTimeframeData();
    DisplayAllTimeframeInfo();
    ChartRedraw();
    
    DebugPrint("Display objects recreated successfully");
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
            // For minutes: show MM:SS
            int minutes = remaining_seconds / 60;
            int seconds = remaining_seconds % 60;
            return StringFormat("%02d:%02d", minutes, seconds);
        }
        
        case PERIOD_H1:
        case PERIOD_H4:
        case PERIOD_H6:
        case PERIOD_H12:
        {
            // For hours: show HH:MM
            int hours = remaining_seconds / 3600;
            int minutes = (remaining_seconds % 3600) / 60;
            return StringFormat("%02d:%02d", hours, minutes);
        }
        
        case PERIOD_D1:
        {
            // For daily: show HH:MM
            int hours = remaining_seconds / 3600;
            int minutes = (remaining_seconds % 3600) / 60;
            return StringFormat("%02d:%02d", hours, minutes);
        }
        
        case PERIOD_W1:
        {
            // For weekly: show XD HH:MM
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
            // For monthly: show XD HH:MM
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
//| Update all timeframe data (ENHANCED WITH COUNTDOWN)             |
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
        
        // Calculate quota dynamically for THIS timeframe (not using stored value)
        tf_data[i].quota_value = CalculateQuotaForTimeframe(tf_data[i].timeframe);
        
        // Calculate countdown - THIS UPDATES EVERY SECOND
        tf_data[i].countdown = CalculateCountdown(tf_data[i].timeframe);
        
        // Calculate position percentages based on quota lines
        if(tf_data[i].high > tf_data[i].low)
        {
            // Use the pre-calculated historical average quota (from init)
            double quota_points = tf_data[i].quota_value;
            double quota_price = PointsToPrice(quota_points);
            
            // Low %: How many quotas UP from the LOW (0%, 25%, 50%, 75%, 100%)
            double low_distance = current_price - tf_data[i].low;
            double low_quotas = low_distance / quota_price;
            tf_data[i].low_percentage = (low_quotas / 4.0) * 100.0; // 4 quotas = 100%
            
            // High %: How many quotas DOWN from the HIGH (0%, -25%, -50%, -75%, -100%)
            double high_distance = current_price - tf_data[i].high;
            double high_quotas = high_distance / quota_price;
            tf_data[i].high_percentage = (high_quotas / 4.0) * 100.0; // 4 quotas = 100%
            
            // Calculate TIME quarter position (how far through the time period)
            datetime current_time = TimeCurrent();
            double time_progress = (double)(current_time - period_start) / (double)(period_end - period_start);
            time_progress = MathMax(0.0, MathMin(1.0, time_progress)); // Clamp between 0 and 1
            
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
//| Display all timeframe information (FIXED Z-ORDER)              |
//+------------------------------------------------------------------+
void DisplayAllTimeframeInfo()
{
    // Filter timeframes - remove M1 and M5, start from M15
    string timeframe_names[] = {"M15", "M30", "H1", "H4", "H6", "H12", "D1", "W1", "MN1"};
    int tf_indices[] = {2, 3, 4, 5, 6, 7, 8, 9, 10}; // Corresponding indices in tf_data array
    int num_rows = ArraySize(timeframe_names);
    
    // Calculate dynamic width based on content (Courier New font, 11pt)
    // Header: "TF    L%     H%     Quarter  Countdown"
    // Approximate: 42 characters * 7.5 pixels per char + padding
    int panel_width = 440;
    int header_height = 22;  
    int row_height = 20;     
    int separator_height = 15;
    int padding = 18;        
    int panel_height = header_height + separator_height + (num_rows * row_height) + (padding * 2) + 10;
    
    int panel_x = 10;
    int panel_y = 80;
    
    // STEP 1: CREATE PANEL BACKGROUND (HIGH Z-ORDER - IN FRONT OF ALL OTHER OBJECTS)
    string panel_name = "DynamicRange_Panel_Background";
    if(ObjectFind(0, panel_name) < 0)
    {
        ObjectCreate(0, panel_name, OBJ_RECTANGLE_LABEL, 0, 0, 0);
        ObjectSetInteger(0, panel_name, OBJPROP_CORNER, CORNER_LEFT_UPPER);
        ObjectSetInteger(0, panel_name, OBJPROP_BACK, false);  // FOREGROUND - IN FRONT OF CHART
        ObjectSetInteger(0, panel_name, OBJPROP_SELECTABLE, false);
        ObjectSetInteger(0, panel_name, OBJPROP_ZORDER, 1000); // HIGH Z-ORDER - IN FRONT OF ALL OTHER OBJECTS
    }
    // Update properties (including size)
    ObjectSetInteger(0, panel_name, OBJPROP_XDISTANCE, panel_x);
    ObjectSetInteger(0, panel_name, OBJPROP_YDISTANCE, panel_y);
    ObjectSetInteger(0, panel_name, OBJPROP_XSIZE, panel_width);
    ObjectSetInteger(0, panel_name, OBJPROP_YSIZE, panel_height);
    ObjectSetInteger(0, panel_name, OBJPROP_BGCOLOR, clrBlack);
    ObjectSetInteger(0, panel_name, OBJPROP_BORDER_TYPE, BORDER_FLAT);
    ObjectSetInteger(0, panel_name, OBJPROP_COLOR, is_dark_theme ? clrGray : clrDarkGray);
    ObjectSetInteger(0, panel_name, OBJPROP_WIDTH, 1);
    
    // STEP 2: CREATE HEADER (HIGHEST Z-ORDER - ON TOP OF PANEL)
    string header_name = "DynamicRange_Table_Header";
    if(ObjectFind(0, header_name) < 0)
    {
        string header_text = "TF       L%      H%    Quarter   Countdown";
        ObjectCreate(0, header_name, OBJ_LABEL, 0, 0, 0);
        ObjectSetInteger(0, header_name, OBJPROP_CORNER, CORNER_LEFT_UPPER);
        ObjectSetString(0, header_name, OBJPROP_TEXT, header_text);
        ObjectSetString(0, header_name, OBJPROP_FONT, "Courier New");
        ObjectSetInteger(0, header_name, OBJPROP_SELECTABLE, false);
        ObjectSetInteger(0, header_name, OBJPROP_BACK, false);  // FOREGROUND
        ObjectSetInteger(0, header_name, OBJPROP_ZORDER, 2000); // HIGHEST Z-ORDER - ON TOP OF PANEL
    }
    // Update properties
    ObjectSetInteger(0, header_name, OBJPROP_XDISTANCE, panel_x + padding);
    ObjectSetInteger(0, header_name, OBJPROP_YDISTANCE, panel_y + padding);
    ObjectSetInteger(0, header_name, OBJPROP_COLOR, clrWhite);
    ObjectSetInteger(0, header_name, OBJPROP_FONTSIZE, 11);
    
    // STEP 3: CREATE SEPARATOR (HIGHEST Z-ORDER - ON TOP OF PANEL)
    string separator_name = "DynamicRange_Table_Separator";
    if(ObjectFind(0, separator_name) < 0)
    {
        string separator_text = "----------------------------------------------------------";
        ObjectCreate(0, separator_name, OBJ_LABEL, 0, 0, 0);
        ObjectSetInteger(0, separator_name, OBJPROP_CORNER, CORNER_LEFT_UPPER);
        ObjectSetString(0, separator_name, OBJPROP_TEXT, separator_text);
        ObjectSetString(0, separator_name, OBJPROP_FONT, "Courier New");
        ObjectSetInteger(0, separator_name, OBJPROP_SELECTABLE, false);
        ObjectSetInteger(0, separator_name, OBJPROP_BACK, false);  // FOREGROUND
        ObjectSetInteger(0, separator_name, OBJPROP_ZORDER, 2000); // HIGHEST Z-ORDER - ON TOP OF PANEL
    }
    // Update properties
    ObjectSetInteger(0, separator_name, OBJPROP_XDISTANCE, panel_x + padding);
    ObjectSetInteger(0, separator_name, OBJPROP_YDISTANCE, panel_y + padding + header_height);
    ObjectSetInteger(0, separator_name, OBJPROP_COLOR, clrGray);
    ObjectSetInteger(0, separator_name, OBJPROP_FONTSIZE, 8);
    
    // STEP 4: CREATE OR UPDATE DATA ROWS (HIGHEST Z-ORDER - ON TOP OF PANEL)
    for(int i = 0; i < num_rows; i++)
    {
        int tf_index = tf_indices[i];
        
string display_text = StringFormat(
   "%-4s %6.0f%% %6.0f%%   %-9s %s",
   timeframe_names[i],                    // TF
   tf_data[tf_index].low_percentage,      // L%
   tf_data[tf_index].high_percentage,     // H%
   tf_data[tf_index].time_quarter,        // Quarter
   tf_data[tf_index].countdown            // Countdown
);
      
        
        string info_name = "DynamicRange_Table_Row_" + IntegerToString(i);
        
        // CREATE ROW ONLY IF IT DOESN'T EXIST (HIGHEST Z-ORDER - ON TOP OF PANEL)
        if(ObjectFind(0, info_name) < 0)
        {
            ObjectCreate(0, info_name, OBJ_LABEL, 0, 0, 0);
            ObjectSetInteger(0, info_name, OBJPROP_CORNER, CORNER_LEFT_UPPER);
            ObjectSetString(0, info_name, OBJPROP_FONT, "Courier New");
            ObjectSetInteger(0, info_name, OBJPROP_SELECTABLE, false);
            ObjectSetInteger(0, info_name, OBJPROP_BACK, false);  // FOREGROUND
            ObjectSetInteger(0, info_name, OBJPROP_ZORDER, 2000); // HIGHEST Z-ORDER - ON TOP OF PANEL
        }
        
        // ALWAYS UPDATE TEXT, POSITION, AND COLOR (NO FLICKER)
        ObjectSetInteger(0, info_name, OBJPROP_XDISTANCE, panel_x + padding);
        ObjectSetInteger(0, info_name, OBJPROP_YDISTANCE, panel_y + padding + header_height + separator_height + (i * row_height));
        ObjectSetInteger(0, info_name, OBJPROP_FONTSIZE, 11);
        ObjectSetString(0, info_name, OBJPROP_TEXT, display_text);
        color text_color = GetThemeAwareColorForBlackPanel(tf_data[tf_index].low_percentage);
        ObjectSetInteger(0, info_name, OBJPROP_COLOR, text_color);
    }
}

//+------------------------------------------------------------------+
//| Get theme-aware color for black panel background               |
//+------------------------------------------------------------------+
color GetThemeAwareColorForBlackPanel(double low_percentage)
{
    if(low_percentage <= 25.0)
        return clrRed;        
    else if(low_percentage <= 50.0)
        return clrOrange;     
    else if(low_percentage <= 75.0)
        return clrYellow;     
    else
        return clrLime;       
}

//+------------------------------------------------------------------+
//| Get theme-aware color based on position                        |
//+------------------------------------------------------------------+
color GetThemeAwareColor(double low_percentage)
{
    if(is_dark_theme)
    {
        // Dark theme - bright colors
        if(low_percentage <= 25.0)
            return clrRed;        // Q1 - Red
        else if(low_percentage <= 50.0)
            return clrOrange;     // Q2 - Orange
        else if(low_percentage <= 75.0)
            return clrYellow;     // Q3 - Yellow
        else
            return clrLime;       // Q4 - Green
    }
    else
    {
        // Light theme - darker colors for contrast
        if(low_percentage <= 25.0)
            return clrDarkRed;    // Q1 - Dark Red
        else if(low_percentage <= 50.0)
            return clrDarkOrange; // Q2 - Dark Orange
        else if(low_percentage <= 75.0)
            return clrGold;       // Q3 - Gold (darker yellow)
        else
            return clrDarkGreen;  // Q4 - Dark Green
    }
}

//+------------------------------------------------------------------+
//| Get period start for specific timeframe (FIXED FOR CONTINUOUS) |
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
            // For continuous instruments like BTC, use Sunday as week start
            // This ensures proper weekly calculations for 24/7 markets
            dt.hour = 0;
            dt.min = 0;
            dt.sec = 0;
            datetime day_start = StructToTime(dt);
            
            // Calculate days since Sunday (Sunday = 0)
            int days_since_sunday = dt.day_of_week;
            
            datetime week_start = day_start - (days_since_sunday * 86400);
            
            DebugPrint("Weekly period start calculation (CONTINUOUS):");
            DebugPrint("Current time: " + TimeToString(current_time));
            DebugPrint("Day of week: " + IntegerToString(dt.day_of_week) + " (0=Sunday)");
            DebugPrint("Days since Sunday: " + IntegerToString(days_since_sunday));
            DebugPrint("Week start (Sunday): " + TimeToString(week_start));
            
            return week_start;
        }
            
        case PERIOD_MN1:
        {
            // Find start of current month
            dt.day = 1;
            dt.hour = 0;
            dt.min = 0;
            dt.sec = 0;
            datetime month_start = StructToTime(dt);
            
            DebugPrint("Monthly period start: " + TimeToString(month_start));
            return month_start;
        }
            
        case PERIOD_D1:
        {
            // Daily period - for continuous markets, use midnight UTC
            dt.hour = 0; // Use 0 for continuous markets instead of DayStartHour
            dt.min = 0;
            dt.sec = 0;
            datetime day_start_time = StructToTime(dt);
            if(current_time < day_start_time)
                day_start_time -= 86400;
            return day_start_time;
        }
            
        default:
        {
            // For intraday periods - get CURRENT period
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
//| Get period end for specific timeframe (FIXED FOR CONTINUOUS)   |
//+------------------------------------------------------------------+
datetime GetPeriodEnd(ENUM_TIMEFRAMES timeframe)
{
    datetime period_start = GetPeriodStart(timeframe);
    
    switch(timeframe)
    {
        case PERIOD_W1:
        {
            // For continuous markets, week ends after 7 full days
            datetime week_end = period_start + (7 * 86400);
            DebugPrint("Weekly period end (CONTINUOUS): " + TimeToString(week_end));
            return week_end;
        }
            
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
            datetime month_end = StructToTime(dt);
            DebugPrint("Monthly period end: " + TimeToString(month_end));
            return month_end;
        }
            
        default:
            return period_start + PeriodSeconds(timeframe);
    }
}

//+------------------------------------------------------------------+
//| Get Current Period Start based on analysis timeframe           |
//+------------------------------------------------------------------+
datetime GetCurrentPeriodStart()
{
    return GetPeriodStart(InpRangeTimeframe);
}

//+------------------------------------------------------------------+
//| Get Current Period End based on analysis timeframe             |
//+------------------------------------------------------------------+
datetime GetCurrentPeriodEnd()
{
    return GetPeriodEnd(InpRangeTimeframe);
}

//+------------------------------------------------------------------+
//| Draw Period and Quarter Separators                             |
//+------------------------------------------------------------------+
void DrawPeriodSeparators()
{
    // Don't draw if calculation not complete
    if(!g_calculationComplete || g_averagePeriodRange <= 0)
    {
        DebugPrint("Skipping separator drawing - calculation not complete");
        return;
    }
    
    if(!ShowPeriodSeparators && !ShowQuarterSeparators) return;
    
    // Remove previous separators
    for(int i = ObjectsTotal(0) - 1; i >= 0; i--)
    {
        string obj_name = ObjectName(0, i);
        if(StringFind(obj_name, "DynamicRange_Sep_") == 0)
        {
            ObjectDelete(0, obj_name);
        }
    }
    
    // Validate thickness values
    int period_thickness = MathMax(1, MathMin(5, PeriodSeparatorThickness));
    int quarter_thickness = MathMax(1, MathMin(5, QuarterSeparatorThickness));
    
    // Set period separator color based on theme
    color period_separator_color = is_dark_theme ? clrYellow : clrBlue;
    color quarter_separator_color = is_dark_theme ? QuarterSeparatorColor : clrNavy;
    
    int separator_count = 0;
    
    // Draw separators for multiple periods
    for(int p = -10; p <= 2 && separator_count < 200; p++)
    {
        datetime this_period_start = GetPeriodStartByOffset(p);
        datetime this_period_end = GetPeriodEndByOffset(p);
        
        DebugPrint("Drawing separator " + IntegerToString(p) + " from " + TimeToString(this_period_start) + " to " + TimeToString(this_period_end));
        
        if(ShowPeriodSeparators)
        {
            // Main period separators (thick dashed lines)
            string sep_name = "DynamicRange_Sep_Period_" + IntegerToString(separator_count);
            ObjectCreate(0, sep_name, OBJ_VLINE, 0, this_period_start, 0);
            ObjectSetInteger(0, sep_name, OBJPROP_COLOR, period_separator_color);
            ObjectSetInteger(0, sep_name, OBJPROP_STYLE, STYLE_DASH);
            ObjectSetInteger(0, sep_name, OBJPROP_WIDTH, period_thickness);
            ObjectSetInteger(0, sep_name, OBJPROP_BACK, true);
            ObjectSetInteger(0, sep_name, OBJPROP_SELECTABLE, false);
        }
        
        if(ShowQuarterSeparators)
        {
            // Calculate quarter separators based on actual period duration and bars
            DrawQuarterSeparators(this_period_start, this_period_end, separator_count, quarter_thickness, quarter_separator_color);
        }
        
        separator_count++;
    }
    
    DebugPrint("Drew " + IntegerToString(separator_count) + " period separators for " + EnumToString(InpRangeTimeframe));
}

//+------------------------------------------------------------------+
//| Draw Quarter Separators for a specific period (IMPROVED)       |
//+------------------------------------------------------------------+
void DrawQuarterSeparators(datetime period_start, datetime period_end, int period_index, int quarter_thickness, color quarter_color)
{
    switch(InpRangeTimeframe)
    {
        case PERIOD_W1:
        {
            // For weekly: divide 7 days into 4 quarters (improved for continuous markets)
            int total_seconds = (int)(period_end - period_start);
            int quarter_seconds = total_seconds / 4;
            
            DebugPrint("Weekly quarters for period " + IntegerToString(period_index) + ": " + IntegerToString(total_seconds) + " total seconds");
            
            for(int q = 1; q < 4; q++)
            {
                datetime quarter_time = period_start + (q * quarter_seconds);
                string quarter_name = "DynamicRange_Sep_Quarter_" + IntegerToString(period_index) + "_" + IntegerToString(q);
                ObjectCreate(0, quarter_name, OBJ_VLINE, 0, quarter_time, 0);
                ObjectSetInteger(0, quarter_name, OBJPROP_COLOR, quarter_color);
                ObjectSetInteger(0, quarter_name, OBJPROP_STYLE, STYLE_DOT);
                ObjectSetInteger(0, quarter_name, OBJPROP_WIDTH, quarter_thickness);
                ObjectSetInteger(0, quarter_name, OBJPROP_BACK, true);
                ObjectSetInteger(0, quarter_name, OBJPROP_SELECTABLE, false);
                
                DebugPrint("Week Quarter " + IntegerToString(q) + " at " + TimeToString(quarter_time));
            }
            break;
        }
        
        case PERIOD_MN1:
        {
            // For monthly: divide into 4 weeks approximately
            int total_seconds = (int)(period_end - period_start);
            int quarter_seconds = total_seconds / 4;
            
            for(int q = 1; q < 4; q++)
            {
                datetime quarter_time = period_start + (q * quarter_seconds);
                string quarter_name = "DynamicRange_Sep_Quarter_" + IntegerToString(period_index) + "_" + IntegerToString(q);
                ObjectCreate(0, quarter_name, OBJ_VLINE, 0, quarter_time, 0);
                ObjectSetInteger(0, quarter_name, OBJPROP_COLOR, quarter_color);
                ObjectSetInteger(0, quarter_name, OBJPROP_STYLE, STYLE_DOT);
                ObjectSetInteger(0, quarter_name, OBJPROP_WIDTH, quarter_thickness);
                ObjectSetInteger(0, quarter_name, OBJPROP_BACK, true);
                ObjectSetInteger(0, quarter_name, OBJPROP_SELECTABLE, false);
                
                DebugPrint("Month Quarter " + IntegerToString(q) + " at " + TimeToString(quarter_time));
            }
            break;
        }
        
        case PERIOD_D1:
        {
            // For daily: divide into 4 quarters (6 hours each for 24h day)
            for(int q = 1; q < 4; q++)
            {
                datetime quarter_time = period_start + (q * 6 * 3600);
                if(quarter_time < period_end)
                {
                    string quarter_name = "DynamicRange_Sep_Quarter_" + IntegerToString(period_index) + "_" + IntegerToString(q);
                    ObjectCreate(0, quarter_name, OBJ_VLINE, 0, quarter_time, 0);
                    ObjectSetInteger(0, quarter_name, OBJPROP_COLOR, quarter_color);
                    ObjectSetInteger(0, quarter_name, OBJPROP_STYLE, STYLE_DOT);
                    ObjectSetInteger(0, quarter_name, OBJPROP_WIDTH, quarter_thickness);
                    ObjectSetInteger(0, quarter_name, OBJPROP_BACK, true);
                    ObjectSetInteger(0, quarter_name, OBJPROP_SELECTABLE, false);
                }
            }
            break;
        }
        
        default:
        {
            // For intraday periods: simple time-based quarters
            int quarter_seconds = (int)(period_end - period_start) / 4;
            for(int q = 1; q < 4; q++)
            {
                datetime quarter_time = period_start + (q * quarter_seconds);
                string quarter_name = "DynamicRange_Sep_Quarter_" + IntegerToString(period_index) + "_" + IntegerToString(q);
                ObjectCreate(0, quarter_name, OBJ_VLINE, 0, quarter_time, 0);
                ObjectSetInteger(0, quarter_name, OBJPROP_COLOR, quarter_color);
                ObjectSetInteger(0, quarter_name, OBJPROP_STYLE, STYLE_DOT);
                ObjectSetInteger(0, quarter_name, OBJPROP_WIDTH, quarter_thickness);
                ObjectSetInteger(0, quarter_name, OBJPROP_BACK, true);
                ObjectSetInteger(0, quarter_name, OBJPROP_SELECTABLE, false);
            }
            break;
        }
    }
}

//+------------------------------------------------------------------+
//| Get Period Start by Offset (FIXED FOR CONTINUOUS)              |
//+------------------------------------------------------------------+
datetime GetPeriodStartByOffset(int offset)
{
    datetime current_period = GetCurrentPeriodStart();
    
    switch(InpRangeTimeframe)
    {
        case PERIOD_W1:
        {
            // For continuous markets, simply add/subtract weeks
            datetime result = current_period + (offset * 7 * 86400);
            DebugPrint("Weekly offset " + IntegerToString(offset) + ": " + TimeToString(result));
            return result;
        }
            
        case PERIOD_MN1:
        {
            MqlDateTime dt;
            TimeToStruct(current_period, dt);
            dt.mon += offset;
            while(dt.mon <= 0)
            {
                dt.mon += 12;
                dt.year--;
            }
            while(dt.mon > 12)
            {
                dt.mon -= 12;
                dt.year++;
            }
            datetime result = StructToTime(dt);
            DebugPrint("Monthly offset " + IntegerToString(offset) + ": " + TimeToString(result));
            return result;
        }
            
        default:
            return current_period + (offset * PeriodSeconds(InpRangeTimeframe));
    }
}

//+------------------------------------------------------------------+
//| Get Period End by Offset                                        |
//+------------------------------------------------------------------+
datetime GetPeriodEndByOffset(int offset)
{
    datetime period_start = GetPeriodStartByOffset(offset);
    
    switch(InpRangeTimeframe)
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
            return period_start + PeriodSeconds(InpRangeTimeframe);
    }
}

//+------------------------------------------------------------------+
//| Calculate quota for any timeframe (historical average)         |
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
        
        return fallback_quota; // Return in POINTS
    }
    
    double totalRange = 0.0;
    int validPeriods = 0;
    int period_seconds = PeriodSeconds(timeframe);
    
    for(int i = 1; i < copied; i++)
    {
        // Only skip Sunday for intraday timeframes (not weekly/monthly)
        // Weekly bars have timestamps on Sunday but represent the whole week
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
        
        return quota; // Return quota (avg range / 4)
    }
    
    return 25.0; // Safe fallback
}

//+------------------------------------------------------------------+
//| Calculate Dynamic Quota Value (SILENT VERSION)                 |
//+------------------------------------------------------------------+
void CalculateDynamicQuota()
{
    // Reset calculation flag
    g_calculationComplete = false;
    
    DebugPrint("=== CALCULATING DYNAMIC QUOTA ===");
    DebugPrint("Lookback: " + IntegerToString(InpPeriodsLookback) + " periods");
    DebugPrint("Timeframe: " + EnumToString(InpRangeTimeframe));
    DebugPrint("Ignore Sunday: " + (InpIgnoreSunday ? "true" : "false"));
    
    // Request enough bars to ensure we get the required periods
    int periods_to_request = InpPeriodsLookback + 50; // Add buffer for safety
    
    // ENHANCED: Wait for data to be available with better error handling
    int attempts = 0;
    int copied = 0;
    MqlRates rates[];
    
    while(attempts < 15 && copied <= 0) // Increased attempts to 15
    {
        ResetLastError(); // Clear any previous errors
        copied = CopyRates(_Symbol, InpRangeTimeframe, 0, periods_to_request, rates);
        int error_code = GetLastError();
        
        if(copied <= 0)
        {
            if(error_code == 4401) // ERR_HISTORY_NOT_FOUND
            {
                DebugPrint("History data not available for " + EnumToString(InpRangeTimeframe) + ". Requesting data...");
                // Try to request the data
                SeriesInfoInteger(_Symbol, InpRangeTimeframe, SERIES_TERMINAL_FIRSTDATE);
                SeriesInfoInteger(_Symbol, InpRangeTimeframe, SERIES_LASTBAR_DATE);
            }
            
            Sleep(500); // Wait longer between attempts
            attempts++;
            DebugPrint("Attempt " + IntegerToString(attempts) + " to copy rates. Error: " + IntegerToString(error_code));
        }
    }
    
    if(copied <= 0)
    {
        int final_error = GetLastError();
        DebugPrint("ERROR: Could not copy rates for quota calculation after " + IntegerToString(attempts) + " attempts. Error: " + IntegerToString(final_error));
        
        // Set safe fallback values based on timeframe
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
        g_calculationComplete = true; // Allow operation with fallback values
        
        DebugPrint("Using fallback values: APR=" + DoubleToString(g_averagePeriodRange, 1) + " Quota=" + DoubleToString(g_dynamicQuotaValue, 1));
        return;
    }
    
    DebugPrint("Successfully copied " + IntegerToString(copied) + " bars for quota calculation");
    
    double symbolPoint = SymbolInfoDouble(_Symbol, SYMBOL_POINT);
    if(symbolPoint <= 0)
    {
        DebugPrint("ERROR: Invalid symbol point value: " + DoubleToString(symbolPoint, 8));
        return;
    }
    
    double totalRange = 0.0;
    int validPeriods = 0;
    int sundaysSkipped = 0;
    
    // Check if this is a continuous market (crypto)
    string symbol_name = _Symbol;
    bool isContinuousMarket = (StringFind(symbol_name, "BTC") >= 0 || 
                              StringFind(symbol_name, "ETH") >= 0 || 
                              StringFind(symbol_name, "CRYPTO") >= 0 ||
                              StringFind(symbol_name, "Volatility") >= 0);
    
    DebugPrint("Symbol: " + symbol_name + " | Continuous Market: " + (isContinuousMarket ? "YES" : "NO"));
    
    // Process periods from most recent backwards
    for(int i = 0; i < copied && validPeriods < InpPeriodsLookback; i++)
    {
        MqlDateTime dt;
        TimeToStruct(rates[i].time, dt);
        
        // Check if we should skip Sunday for traditional markets
        bool skipSunday = false;
        if(InpIgnoreSunday && !isContinuousMarket && dt.day_of_week == 0)
        {
            skipSunday = true;
            sundaysSkipped++;
            DebugPrint("Skipping Sunday: " + TimeToString(rates[i].time, TIME_DATE));
            continue;
        }
        
        // Validate rates data
        if(rates[i].high <= 0 || rates[i].low <= 0 || rates[i].high < rates[i].low)
        {
            DebugPrint("Skipping invalid rates data at: " + TimeToString(rates[i].time, TIME_DATE));
            continue;
        }
        
        // Calculate range for this period
        double periodRange = (rates[i].high - rates[i].low) / symbolPoint;
        
        // Skip periods with zero range (data issues)
        if(periodRange <= 0)
        {
            DebugPrint("Skipping period with zero range: " + TimeToString(rates[i].time, TIME_DATE));
            continue;
        }
        
        totalRange += periodRange;
        validPeriods++;
        
        DebugPrint("Period " + IntegerToString(validPeriods) + " (" + TimeToString(rates[i].time, TIME_DATE|TIME_MINUTES) + 
                  "): H=" + DoubleToString(rates[i].high, _Digits) + 
                  " L=" + DoubleToString(rates[i].low, _Digits) + 
                  " Range=" + DoubleToString(periodRange, 1) + " points");
    }
    
    if(validPeriods > 0 && totalRange > 0)
    {
        g_averagePeriodRange = totalRange / validPeriods;
        g_dynamicQuotaValue = g_averagePeriodRange / 4.0;
        g_totalPeriodsAnalyzed = validPeriods;
        
        // Validate calculated values
        if(g_averagePeriodRange > 0 && g_dynamicQuotaValue > 0)
        {
            g_calculationComplete = true;
            
            DebugPrint("=== QUOTA CALCULATION RESULTS ===");
            DebugPrint("Valid periods analyzed: " + IntegerToString(validPeriods));
            DebugPrint("Sundays skipped: " + IntegerToString(sundaysSkipped));
            DebugPrint("Total range sum: " + DoubleToString(totalRange, 1) + " points");
            DebugPrint("Average period range: " + DoubleToString(g_averagePeriodRange, 1) + " points");
            DebugPrint("Dynamic quota value (range/4): " + DoubleToString(g_dynamicQuotaValue, 1) + " points");
            DebugPrint("Calculation: " + DoubleToString(totalRange, 1) + " / " + IntegerToString(validPeriods) + " = " + DoubleToString(g_averagePeriodRange, 1));
            
            // ONLY print if debug mode is enabled
            if(InpDebugMode)
            {
                Print("=== QUOTA CALCULATION COMPLETE ===");
                Print("Timeframe: ", EnumToString(InpRangeTimeframe));
                Print("Periods analyzed: ", validPeriods, " out of ", InpPeriodsLookback, " requested");
                Print("Average ", EnumToString(InpRangeTimeframe), " Range: ", DoubleToString(g_averagePeriodRange, 1), " points");
                Print("Dynamic Quota Value: ", DoubleToString(g_dynamicQuotaValue, 1), " points");
                Print("Calculation: ", DoubleToString(totalRange, 1), " total points / ", validPeriods, " periods = ", DoubleToString(g_averagePeriodRange, 1), " points average");
                if(sundaysSkipped > 0)
                    Print("Sundays skipped: ", sundaysSkipped);
            }
        }
        else
        {
            DebugPrint("ERROR: Calculated invalid values - APR:" + DoubleToString(g_averagePeriodRange, 1) + " Quota:" + DoubleToString(g_dynamicQuotaValue, 1));
        }
    }
    else
    {
        DebugPrint("ERROR: No valid periods found for quota calculation. ValidPeriods:" + IntegerToString(validPeriods) + " TotalRange:" + DoubleToString(totalRange, 1));
        g_averagePeriodRange = 100.0;
        g_dynamicQuotaValue = 25.0;
        g_totalPeriodsAnalyzed = 0;
        g_calculationComplete = true; // Allow operation with fallback values
    }
}

//+------------------------------------------------------------------+
//| Chart event handler (ENHANCED WITH SAFER ONINIT CALLS)         |
//+------------------------------------------------------------------+
void OnChartEvent(const int id, const long &lparam, const double &dparam, const string &sparam)
{
    if(id == CHARTEVENT_OBJECT_CLICK)
    {
        if(sparam == "ToggleHighBtn")
        {
            show_high_lines = !show_high_lines;
            UpdateButtonText("ToggleHighBtn", show_high_lines ? "HIGH ON" : "HIGH OFF", 
                           show_high_lines ? clrLime : clrRed);
            ObjectSetInteger(0, "ToggleHighBtn", OBJPROP_STATE, false);
            
            // ✅ CRITICAL FIX: Force redraw instead of calling OnInit
            DebugPrint("HIGH button pressed - forcing redraw...");
            g_forceRedraw = true;
        }
        else if(sparam == "ToggleLowBtn")
        {
            show_low_lines = !show_low_lines;
            UpdateButtonText("ToggleLowBtn", show_low_lines ? "LOW ON" : "LOW OFF",
                           show_low_lines ? clrLime : clrRed);
            ObjectSetInteger(0, "ToggleLowBtn", OBJPROP_STATE, false);
            
            // ✅ CRITICAL FIX: Force redraw instead of calling OnInit
            DebugPrint("LOW button pressed - forcing redraw...");
            g_forceRedraw = true;
        }
        else if(sparam == "ThemeToggleBtn")
        {
            is_dark_theme = !is_dark_theme;
            ApplyTheme();
            UpdateButtonText("ThemeToggleBtn", is_dark_theme ? "DARK" : "LIGHT",
                           is_dark_theme ? clrBlack : clrWhite);
            ObjectSetInteger(0, "ThemeToggleBtn", OBJPROP_STATE, false);
            
            // ✅ CRITICAL FIX: Force redraw instead of calling OnInit
            DebugPrint("THEME button pressed - forcing redraw...");
            ForceRecreateDisplay();
            g_forceRedraw = true;
        }
        // Handle timeframe button clicks
        else if(StringFind(sparam, "TF_") == 0)
        {
            HandleTimeframeButtonClick(sparam);
        }
    }
}

//+------------------------------------------------------------------+
//| Handle timeframe button clicks (ENHANCED WITH SAFER APPROACH)  |
//+------------------------------------------------------------------+
void HandleTimeframeButtonClick(string button_name)
{
    ENUM_TIMEFRAMES new_timeframe = PERIOD_H4;
    
    if(button_name == "TF_1M_Btn") new_timeframe = PERIOD_M1;
    else if(button_name == "TF_5M_Btn") new_timeframe = PERIOD_M5;
    else if(button_name == "TF_15M_Btn") new_timeframe = PERIOD_M15;
    else if(button_name == "TF_30M_Btn") new_timeframe = PERIOD_M30;
    else if(button_name == "TF_1H_Btn") new_timeframe = PERIOD_H1;
    else if(button_name == "TF_4H_Btn") new_timeframe = PERIOD_H4;
    else if(button_name == "TF_6H_Btn") new_timeframe = PERIOD_H6;
    else if(button_name == "TF_12H_Btn") new_timeframe = PERIOD_H12;
    else if(button_name == "TF_D1_Btn") new_timeframe = PERIOD_D1;
    else if(button_name == "TF_W1_Btn") new_timeframe = PERIOD_W1;
    else if(button_name == "TF_MN1_Btn") new_timeframe = PERIOD_MN1;
    
    // Reset button state immediately
    ObjectSetInteger(0, button_name, OBJPROP_STATE, false);
    
    if(new_timeframe != InpRangeTimeframe)
    {
        DebugPrint("=== SWITCHING TIMEFRAME ===");
        DebugPrint("From: " + EnumToString(InpRangeTimeframe) + " To: " + EnumToString(new_timeframe));
        
        // Update timeframe variables
        InpRangeTimeframe = new_timeframe;
        
        // ✅ CRITICAL FIX: Reset tracking variables and force complete redraw
        g_calculationComplete = false;
        g_lastBarTime = 0;
        g_lastPeriodCalculated = 0;
        g_forceRedraw = true;
        
        // Update button colors immediately
        UpdateTimeframeButtonColors();
        
        // Clear old objects
        ClearCalculationObjects();
        
        DebugPrint("Timeframe switched. Calculation and redraw will be performed in OnCalculate.");
        ChartRedraw();
    }
    else
    {
        DebugPrint("Same timeframe selected: " + EnumToString(new_timeframe));
    }
}

//+------------------------------------------------------------------+
//| Clear calculation-dependent objects                             |
//+------------------------------------------------------------------+
void ClearCalculationObjects()
{
    // Remove all dynamic range objects except buttons and display
    for(int i = ObjectsTotal(0) - 1; i >= 0; i--)
    {
        string obj_name = ObjectName(0, i);
        if(StringFind(obj_name, "DynamicRange_") == 0 && 
           StringFind(obj_name, "DynamicRange_Table") != 0 &&
           StringFind(obj_name, "DynamicRange_Panel") != 0)
        {
            ObjectDelete(0, obj_name);
        }
    }
}

//+------------------------------------------------------------------+
//| Update timeframe button colors (FIXED)                          |
//+------------------------------------------------------------------+
void UpdateTimeframeButtonColors()
{
    string timeframes[] = {"1M", "5M", "15M", "30M", "1H", "4H", "6H", "12H", "D1", "W1", "MN1"};
    ENUM_TIMEFRAMES tf_values[] = {PERIOD_M1, PERIOD_M5, PERIOD_M15, PERIOD_M30, PERIOD_H1, PERIOD_H4, PERIOD_H6, PERIOD_H12, PERIOD_D1, PERIOD_W1, PERIOD_MN1};
    
    for(int i = 0; i < ArraySize(timeframes); i++)
    {
        string btn_name = "TF_" + timeframes[i] + "_Btn";
        color bg_color = (tf_values[i] == InpRangeTimeframe) ? clrLime : clrGray;
        ObjectSetInteger(0, btn_name, OBJPROP_BGCOLOR, bg_color);
        
        DebugPrint("Button " + btn_name + " color set to " + (tf_values[i] == InpRangeTimeframe ? "GREEN" : "GRAY"));
    }
}

//+------------------------------------------------------------------+
//| Apply Theme                                                      |
//+------------------------------------------------------------------+
void ApplyTheme()
{
    if(is_dark_theme)
    {
        ChartSetInteger(0, CHART_COLOR_BACKGROUND, clrBlack);
        ChartSetInteger(0, CHART_COLOR_FOREGROUND, clrWhite);
        ChartSetInteger(0, CHART_COLOR_GRID, clrNONE); // Disable grid
        ChartSetInteger(0, CHART_COLOR_CHART_UP, clrLime);
        ChartSetInteger(0, CHART_COLOR_CHART_DOWN, clrRed);
        ChartSetInteger(0, CHART_COLOR_CANDLE_BULL, clrLime);
        ChartSetInteger(0, CHART_COLOR_CANDLE_BEAR, clrRed);
    }
    else
    {
        ChartSetInteger(0, CHART_COLOR_BACKGROUND, clrWhite);
        ChartSetInteger(0, CHART_COLOR_FOREGROUND, clrBlack);
        ChartSetInteger(0, CHART_COLOR_GRID, clrNONE); // Disable grid
        ChartSetInteger(0, CHART_COLOR_CHART_UP, clrBlue);
        ChartSetInteger(0, CHART_COLOR_CHART_DOWN, clrRed);
        ChartSetInteger(0, CHART_COLOR_CANDLE_BULL, clrBlue);
        ChartSetInteger(0, CHART_COLOR_CANDLE_BEAR, clrRed);
    }
    
    // Disable grid lines
    ChartSetInteger(0, CHART_SHOW_GRID, false);
    
    ChartRedraw();
}

//+------------------------------------------------------------------+
//| Create toggle buttons                                           |
//+------------------------------------------------------------------+
void CreateToggleButtons()
{
    // High lines toggle button
    ObjectCreate(0, "ToggleHighBtn", OBJ_BUTTON, 0, 0, 0);
    ObjectSetInteger(0, "ToggleHighBtn", OBJPROP_XDISTANCE, 10);
    ObjectSetInteger(0, "ToggleHighBtn", OBJPROP_YDISTANCE, ChartGetInteger(0, CHART_HEIGHT_IN_PIXELS) - 80);
    ObjectSetInteger(0, "ToggleHighBtn", OBJPROP_XSIZE, 80);
    ObjectSetInteger(0, "ToggleHighBtn", OBJPROP_YSIZE, 30);
    ObjectSetString(0, "ToggleHighBtn", OBJPROP_TEXT, "HIGH ON");
    ObjectSetInteger(0, "ToggleHighBtn", OBJPROP_COLOR, clrWhite);
    ObjectSetInteger(0, "ToggleHighBtn", OBJPROP_BGCOLOR, clrLime);
    ObjectSetInteger(0, "ToggleHighBtn", OBJPROP_FONTSIZE, 8);
    
    // Low lines toggle button
    ObjectCreate(0, "ToggleLowBtn", OBJ_BUTTON, 0, 0, 0);
    ObjectSetInteger(0, "ToggleLowBtn", OBJPROP_XDISTANCE, 100);
    ObjectSetInteger(0, "ToggleLowBtn", OBJPROP_YDISTANCE, ChartGetInteger(0, CHART_HEIGHT_IN_PIXELS) - 80);
    ObjectSetInteger(0, "ToggleLowBtn", OBJPROP_XSIZE, 80);
    ObjectSetInteger(0, "ToggleLowBtn", OBJPROP_YSIZE, 30);
    ObjectSetString(0, "ToggleLowBtn", OBJPROP_TEXT, "LOW ON");
    ObjectSetInteger(0, "ToggleLowBtn", OBJPROP_COLOR, clrWhite);
    ObjectSetInteger(0, "ToggleLowBtn", OBJPROP_BGCOLOR, clrLime);
    ObjectSetInteger(0, "ToggleLowBtn", OBJPROP_FONTSIZE, 8);
    
    // Theme toggle button
    ObjectCreate(0, "ThemeToggleBtn", OBJ_BUTTON, 0, 0, 0);
    ObjectSetInteger(0, "ThemeToggleBtn", OBJPROP_XDISTANCE, 190);
    ObjectSetInteger(0, "ThemeToggleBtn", OBJPROP_YDISTANCE, ChartGetInteger(0, CHART_HEIGHT_IN_PIXELS) - 80);
    ObjectSetInteger(0, "ThemeToggleBtn", OBJPROP_XSIZE, 80);
    ObjectSetInteger(0, "ThemeToggleBtn", OBJPROP_YSIZE, 30);
    ObjectSetString(0, "ThemeToggleBtn", OBJPROP_TEXT, "DARK");
    ObjectSetInteger(0, "ThemeToggleBtn", OBJPROP_COLOR, clrWhite);
    ObjectSetInteger(0, "ThemeToggleBtn", OBJPROP_BGCOLOR, clrBlack);
    ObjectSetInteger(0, "ThemeToggleBtn", OBJPROP_FONTSIZE, 8);
}

//+------------------------------------------------------------------+
//| Create timeframe buttons                                        |
//+------------------------------------------------------------------+
void CreateTimeframeButtons()
{
    string timeframes[] = {"1M", "5M", "15M", "30M", "1H", "4H", "6H", "12H", "D1", "W1", "MN1"};
    
    for(int i = 0; i < ArraySize(timeframes); i++)
    {
        string btn_name = "TF_" + timeframes[i] + "_Btn";
        ObjectCreate(0, btn_name, OBJ_BUTTON, 0, 0, 0);
        ObjectSetInteger(0, btn_name, OBJPROP_XDISTANCE, 280 + (i * 45));
        ObjectSetInteger(0, btn_name, OBJPROP_YDISTANCE, ChartGetInteger(0, CHART_HEIGHT_IN_PIXELS) - 80);
        ObjectSetInteger(0, btn_name, OBJPROP_XSIZE, 40);
        ObjectSetInteger(0, btn_name, OBJPROP_YSIZE, 30);
        ObjectSetString(0, btn_name, OBJPROP_TEXT, timeframes[i]);
        ObjectSetInteger(0, btn_name, OBJPROP_COLOR, clrWhite);
        ObjectSetInteger(0, btn_name, OBJPROP_BGCOLOR, clrGray);
        ObjectSetInteger(0, btn_name, OBJPROP_FONTSIZE, 7);
    }
    
    UpdateTimeframeButtonColors();
}

//+------------------------------------------------------------------+
//| Update button text and color                                    |
//+------------------------------------------------------------------+
void UpdateButtonText(string button_name, string text, color bg_color)
{
    ObjectSetString(0, button_name, OBJPROP_TEXT, text);
    ObjectSetInteger(0, button_name, OBJPROP_BGCOLOR, bg_color);
}

//+------------------------------------------------------------------+
//| Check for alerts on quota lines                                |
//+------------------------------------------------------------------+
void CheckAlerts()
{
    if(!EnableAlerts || !g_calculationComplete) return;
    
    double close_price = iClose(_Symbol, PERIOD_CURRENT, 1);
    
    double current_high_25 = GetCurrentQuotaHighLevel();
    double current_low_25 = GetCurrentQuotaLowLevel();
    
    bool new_levels = (current_high_25 != last_high_25_price || current_low_25 != last_low_25_price);
    
    if(new_levels)
    {
        high_25_alert_triggered = false;
        low_25_alert_triggered = false;
        last_high_25_price = current_high_25;
        last_low_25_price = current_low_25;
        DebugPrint("New quota levels detected. Alert flags reset.");
    }
    
    // Check for close below high quota line
    if(current_high_25 > 0 && close_price < current_high_25)
    {
        if(!high_25_alert_triggered)
        {
            high_25_alert_triggered = true;
            SendAlert("BREAK BELOW", "High quota line", current_high_25, close_price);
        }
    }
    else if(current_high_25 > 0 && close_price > current_high_25 && high_25_alert_triggered)
    {
        high_25_alert_triggered = false;
        DebugPrint("Price returned above High quota line. Alert reset for next break.");
    }
    
    // Check for close above low quota line
    if(current_low_25 > 0 && close_price > current_low_25)
    {
        if(!low_25_alert_triggered)
        {
            low_25_alert_triggered = true;
            SendAlert("BREAK ABOVE", "Low quota line", current_low_25, close_price);
        }
    }
    else if(current_low_25 > 0 && close_price < current_low_25 && low_25_alert_triggered)
    {
        low_25_alert_triggered = false;
        DebugPrint("Price returned below Low quota line. Alert reset for next break.");
    }
}

//+------------------------------------------------------------------+
//| Get current quota high level                                   |
//+------------------------------------------------------------------+
double GetCurrentQuotaHighLevel()
{
    if(!g_calculationComplete) return 0;
    
    datetime period_start = GetCurrentPeriodStart();
    datetime period_end = GetCurrentPeriodEnd();
    double period_high = GetPeriodHighLow(period_start, period_end, true);
    
    return period_high - PointsToPrice(g_dynamicQuotaValue);
}

//+------------------------------------------------------------------+
//| Get current quota low level                                    |
//+------------------------------------------------------------------+
double GetCurrentQuotaLowLevel()
{
    if(!g_calculationComplete) return 0;
    
    datetime period_start = GetCurrentPeriodStart();
    datetime period_end = GetCurrentPeriodEnd();
    double period_low = GetPeriodHighLow(period_start, period_end, false);
    
    return period_low + PointsToPrice(g_dynamicQuotaValue);
}

//+------------------------------------------------------------------+
//| Get Period High or Low                                          |
//+------------------------------------------------------------------+
double GetPeriodHighLow(datetime period_start, datetime period_end, bool get_high)
{
    return GetPeriodHighLowForTimeframe(period_start, period_end, InpRangeTimeframe, get_high);
}

//+------------------------------------------------------------------+
//| Get Period High or Low for specific timeframe (IMPROVED)       |
//+------------------------------------------------------------------+
double GetPeriodHighLowForTimeframe(datetime period_start, datetime period_end, ENUM_TIMEFRAMES timeframe, bool get_high)
{
    double result = get_high ? 0 : DBL_MAX;
    
    // Use appropriate scan timeframe based on target timeframe (same as EA logic)
    ENUM_TIMEFRAMES scan_tf = PERIOD_M1;
    if(timeframe == PERIOD_MN1)
        scan_tf = PERIOD_D1;  // Use D1 for monthly to get enough bars
    else if(timeframe == PERIOD_W1)
        scan_tf = PERIOD_H1;  // Use H1 for weekly to get enough bars
    else
        scan_tf = PERIOD_M1;  // Use M1 for everything else
    
    DebugPrint("Getting " + (get_high ? "HIGH" : "LOW") + " for " + EnumToString(timeframe) + " using " + EnumToString(scan_tf) + " from " + TimeToString(period_start) + " to " + TimeToString(period_end));
    
    datetime end_time = TimeCurrent();
    int start_bar = 0;
    int total_bars = iBars(_Symbol, scan_tf);
    int max_bars = MathMin(total_bars - start_bar, 10000);
    bool found_any = false;
    
    for(int i = start_bar; i < start_bar + max_bars; i++)
    {
        datetime bar_time = iTime(_Symbol, scan_tf, i);
        if(bar_time == 0)
            break;
        
        // Skip bars outside the period range
        if(bar_time < period_start)
            break;  // Gone too far back
        if(bar_time >= end_time)
            continue;  // Haven't reached the period yet
        if(bar_time >= period_end)
            continue;  // Beyond the period end
        
        found_any = true;
        
        if(get_high)
        {
            double high_price = iHigh(_Symbol, scan_tf, i);
            if(high_price > result) result = high_price;
        }
        else
        {
            double low_price = iLow(_Symbol, scan_tf, i);
            if(low_price < result) result = low_price;
        }
    }
    
    // If no data found, use current price as fallback
    if(!found_any || (get_high && result == 0) || (!get_high && result == DBL_MAX))
    {
        result = SymbolInfoDouble(_Symbol, SYMBOL_BID);
        DebugPrint("No data found, using current price as fallback: " + DoubleToString(result, _Digits));
    }
    
    DebugPrint("Final result for " + EnumToString(timeframe) + " " + (get_high ? "HIGH" : "LOW") + ": " + DoubleToString(result, _Digits));
    
    return result;
}

//+------------------------------------------------------------------+
//| Send alert notification                                         |
//+------------------------------------------------------------------+
void SendAlert(string action, string level_name, double level_price, double close_price)
{
    string message = StringFormat("%s %s ALERT (%s): %s at %s (Level: %s, Close: %s, Quota: %s pts)",
                                _Symbol, action, EnumToString(InpRangeTimeframe), level_name, TimeToString(TimeCurrent()),
                                DoubleToString(level_price, _Digits),
                                DoubleToString(close_price, _Digits),
                                DoubleToString(g_dynamicQuotaValue, 1));
    
    Print(message);
    
    if(EnableTerminalAlerts)
    {
        Alert(message);
    }
    
    if(EnableEmailAlerts)
    {
        SendMail("Richchild Quaters Theory Indicator Alert", message);
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
//| Create trend line from specific point to infinity               |
//+------------------------------------------------------------------+
void CreateQuotaLine(string name, datetime start_time, double price, color line_color, string label_text, bool is_special_level = false)
{
    datetime end_time = start_time + PeriodSeconds(InpRangeTimeframe) * 10;
    
    ObjectCreate(0, name, OBJ_TREND, 0, start_time, price, end_time, price);
    ObjectSetInteger(0, name, OBJPROP_COLOR, line_color);
    ObjectSetInteger(0, name, OBJPROP_WIDTH, is_special_level ? 4 : 3); // Thicker fib lines
    ObjectSetInteger(0, name, OBJPROP_STYLE, STYLE_SOLID);
    ObjectSetInteger(0, name, OBJPROP_RAY_RIGHT, true);
    ObjectSetInteger(0, name, OBJPROP_RAY_LEFT, false);
    ObjectSetInteger(0, name, OBJPROP_BACK, true);
    ObjectSetInteger(0, name, OBJPROP_SELECTABLE, false);
    
    string label_name = name + "_Label";
    double label_price = price + PointsToPrice(5);
    
    ObjectCreate(0, label_name, OBJ_TEXT, 0, start_time, label_price);
    ObjectSetString(0, label_name, OBJPROP_TEXT, label_text);
    ObjectSetInteger(0, label_name, OBJPROP_COLOR, line_color);
    ObjectSetInteger(0, label_name, OBJPROP_FONTSIZE, 7);
    ObjectSetInteger(0, label_name, OBJPROP_ANCHOR, ANCHOR_LEFT_LOWER);
    ObjectSetInteger(0, label_name, OBJPROP_BACK, false);
    ObjectSetInteger(0, label_name, OBJPROP_SELECTABLE, false);
}

//+------------------------------------------------------------------+
//| Draw quota lines from high point                                |
//+------------------------------------------------------------------+
void DrawHighQuotaLines(datetime high_time, double period_high)
{
    if(!show_high_lines || !g_calculationComplete) return;
    
    int total_levels = (int)(g_averagePeriodRange / g_dynamicQuotaValue) + 1;
    if(total_levels > 20) total_levels = 20;
    
    for(int i = 0; i < total_levels; i++)
    {
        double line_price = period_high - (i * PointsToPrice(g_dynamicQuotaValue));
        string line_name = "DynamicRange_High_Q" + IntegerToString(i);
        string label_text = "H-" + DoubleToString(i * g_dynamicQuotaValue, 1) + "p";
        
        color line_color;
        bool is_special = false;
        
        // Theme-aware colors for high fib lines
        if(is_dark_theme)
        {
            switch(i % 12)
            {
                case 0: line_color = clrRed; break;
                case 1: line_color = clrGold; is_special = true; break;
                case 2: line_color = clrOrangeRed; break;
                case 3: line_color = clrCoral; break;
                case 4: line_color = clrLightCoral; break;
                case 5: line_color = clrMistyRose; break;
                case 6: line_color = clrPink; break;
                case 7: line_color = clrLightPink; break;
                case 8: line_color = clrHotPink; break;
                case 9: line_color = clrDeepPink; break;
                case 10: line_color = clrMediumVioletRed; break;
                case 11: line_color = clrPaleVioletRed; break;
                default: line_color = clrRed; break;
            }
        }
        else
        {
            // Light theme - darker colors for contrast
            switch(i % 12)
            {
                case 0: line_color = clrDarkRed; break;
                case 1: line_color = clrGoldenrod; is_special = true; break;
                case 2: line_color = clrBrown; break;
                case 3: line_color = clrMaroon; break;
                case 4: line_color = clrFireBrick; break;
                case 5: line_color = clrIndianRed; break;
                case 6: line_color = clrCrimson; break;
                case 7: line_color = clrDarkSalmon; break;
                case 8: line_color = clrTomato; break;
                case 9: line_color = clrOrangeRed; break;
                case 10: line_color = clrDarkViolet; break;
                case 11: line_color = clrPurple; break;
                default: line_color = clrDarkRed; break;
            }
        }
        
        CreateQuotaLine(line_name, high_time, line_price, line_color, label_text, is_special);
    }
}

//+------------------------------------------------------------------+
//| Draw quota lines from low point                                 |
//+------------------------------------------------------------------+
void DrawLowQuotaLines(datetime low_time, double period_low)
{
    if(!show_low_lines || !g_calculationComplete) return;
    
    int total_levels = (int)(g_averagePeriodRange / g_dynamicQuotaValue) + 1;
    if(total_levels > 20) total_levels = 20;
    
    for(int i = 0; i < total_levels; i++)
    {
        double line_price = period_low + (i * PointsToPrice(g_dynamicQuotaValue));
        string line_name = "DynamicRange_Low_Q" + IntegerToString(i);
        string label_text = "L+" + DoubleToString(i * g_dynamicQuotaValue, 1) + "p";
        
        color line_color;
        bool is_special = false;
        
        // Theme-aware colors for low fib lines
        if(is_dark_theme)
        {
            switch(i % 12)
            {
                case 0: line_color = clrBlue; break;
                case 1: line_color = clrLime; is_special = true; break;
                case 2: line_color = clrDodgerBlue; break;
                case 3: line_color = clrDeepSkyBlue; break;
                case 4: line_color = clrLightSkyBlue; break;
                case 5: line_color = clrAliceBlue; break;
                case 6: line_color = clrLightBlue; break;
                case 7: line_color = clrPowderBlue; break;
                case 8: line_color = clrSkyBlue; break;
                case 9: line_color = clrLightSteelBlue; break;
                case 10: line_color = clrSteelBlue; break;
                case 11: line_color = clrSlateBlue; break;
                default: line_color = clrBlue; break;
            }
        }
        else
        {
            // Light theme - darker colors for contrast
            switch(i % 12)
            {
                case 0: line_color = clrDarkBlue; break;
                case 1: line_color = clrDarkGreen; is_special = true; break;
                case 2: line_color = clrNavy; break;
                case 3: line_color = clrMidnightBlue; break;
                case 4: line_color = clrRoyalBlue; break;
                case 5: line_color = clrMediumBlue; break;
                case 6: line_color = clrBlue; break;
                case 7: line_color = clrCornflowerBlue; break;
                case 8: line_color = clrSteelBlue; break;
                case 9: line_color = clrSlateBlue; break;
                case 10: line_color = clrDarkSlateBlue; break;
                case 11: line_color = clrIndigo; break;
                default: line_color = clrDarkBlue; break;
            }
        }
        
        CreateQuotaLine(line_name, low_time, line_price, line_color, label_text, is_special);
    }
}

//+------------------------------------------------------------------+
//| ✅ CRITICAL FIX: Enhanced FindAndMarkPeriodHighLow with proper redrawing |
//+------------------------------------------------------------------+
void FindAndMarkPeriodHighLow()
{
    // Don't draw if calculation not complete
    if(!g_calculationComplete || g_averagePeriodRange <= 0 || g_dynamicQuotaValue <= 0)
    {
        DebugPrint("Skipping drawing - calculation not complete");
        return;
    }
    
    // ✅ CRITICAL FIX: Remove previous objects (except buttons and separators) EVERY TIME
    for(int i = ObjectsTotal(0) - 1; i >= 0; i--)
    {
        string obj_name = ObjectName(0, i);
        if(StringFind(obj_name, "DynamicRange_") == 0 && 
           StringFind(obj_name, "DynamicRange_Sep_") != 0 &&
           StringFind(obj_name, "DynamicRange_Table") != 0 &&
           StringFind(obj_name, "DynamicRange_Panel") != 0)
        {
            ObjectDelete(0, obj_name);
            DebugPrint("Deleted old object: " + obj_name);
        }
    }
    
    datetime period_start = GetCurrentPeriodStart();
    datetime period_end = GetCurrentPeriodEnd();
    
    double period_high = GetPeriodHighLow(period_start, period_end, true);
    double period_low = GetPeriodHighLow(period_start, period_end, false);
    
    // Validate period high/low before proceeding
    if(period_high <= 0 || period_low <= 0 || period_high <= period_low)
    {
        DebugPrint("ERROR: Invalid period high/low values. High:" + DoubleToString(period_high, _Digits) + " Low:" + DoubleToString(period_low, _Digits));
        return;
    }
    
    DebugPrint("Drawing lines for period: " + TimeToString(period_start) + " to " + TimeToString(period_end));
    DebugPrint("Period High: " + DoubleToString(period_high, _Digits) + " | Period Low: " + DoubleToString(period_low, _Digits));
    
    datetime high_time = 0;
    datetime low_time = 0;
    
    // Find the exact time of high and low using improved method
    if(InpRangeTimeframe == PERIOD_W1 || InpRangeTimeframe == PERIOD_MN1)
    {
        // For weekly/monthly, try timeframe-specific search first
        MqlRates rates[];
        int bars_to_copy = (int)((period_end - period_start) / PeriodSeconds(InpRangeTimeframe)) + 5;
        int copied = CopyRates(_Symbol, InpRangeTimeframe, period_start, bars_to_copy, rates);
        
        if(copied > 0)
        {
            for(int i = 0; i < copied; i++)
            {
                if(rates[i].time >= period_start && rates[i].time < period_end)
                {
                    if(high_time == 0 && MathAbs(rates[i].high - period_high) < SymbolInfoDouble(_Symbol, SYMBOL_POINT))
                        high_time = rates[i].time;
                    if(low_time == 0 && MathAbs(rates[i].low - period_low) < SymbolInfoDouble(_Symbol, SYMBOL_POINT))
                        low_time = rates[i].time;
                }
            }
        }
        
        // Fallback to current timeframe if not found
        if(high_time == 0 || low_time == 0)
        {
            for(int i = 0; i < 50000; i++) // More bars for continuous markets
            {
                datetime bar_time = iTime(_Symbol, PERIOD_CURRENT, i);
                if(bar_time == 0 || bar_time < period_start) break;
                if(bar_time >= period_end) continue;
                
                if(high_time == 0 && MathAbs(iHigh(_Symbol, PERIOD_CURRENT, i) - period_high) < SymbolInfoDouble(_Symbol, SYMBOL_POINT))
                    high_time = bar_time;
                if(low_time == 0 && MathAbs(iLow(_Symbol, PERIOD_CURRENT, i) - period_low) < SymbolInfoDouble(_Symbol, SYMBOL_POINT))
                    low_time = bar_time;
                
                if(high_time > 0 && low_time > 0) break;
            }
        }
    }
    else
    {
        // For intraday periods, search in current timeframe
        for(int i = 0; i < 2000; i++)
        {
            datetime bar_time = iTime(_Symbol, PERIOD_CURRENT, i);
            if(bar_time == 0 || bar_time < period_start) break;
            if(bar_time >= period_end) continue;
            
            if(high_time == 0 && MathAbs(iHigh(_Symbol, PERIOD_CURRENT, i) - period_high) < SymbolInfoDouble(_Symbol, SYMBOL_POINT))
                high_time = bar_time;
            if(low_time == 0 && MathAbs(iLow(_Symbol, PERIOD_CURRENT, i) - period_low) < SymbolInfoDouble(_Symbol, SYMBOL_POINT))
                low_time = bar_time;
            
            if(high_time > 0 && low_time > 0) break;
        }
    }
    
    // Use period start as fallback if exact time not found
    if(high_time == 0) high_time = period_start;
    if(low_time == 0) low_time = period_start;
    
    DebugPrint("High time: " + TimeToString(high_time) + " | Low time: " + TimeToString(low_time));
    
    // ✅ CRITICAL FIX: Always create new objects with fresh names
    // Mark period high point - arrow at -25% line (HIGH - 1 quota)
    if(period_high > 0)
    {
        double high_arrow_price = period_high - PointsToPrice(g_dynamicQuotaValue);
        
        string high_name = "DynamicRange_High_" + IntegerToString(TimeCurrent()); // Unique name
        ObjectCreate(0, high_name, OBJ_ARROW_DOWN, 0, high_time, high_arrow_price);
        ObjectSetInteger(0, high_name, OBJPROP_COLOR, clrRed);
        ObjectSetInteger(0, high_name, OBJPROP_WIDTH, 4);
        ObjectSetInteger(0, high_name, OBJPROP_ARROWCODE, 242);
        ObjectSetInteger(0, high_name, OBJPROP_BACK, false);
        ObjectSetInteger(0, high_name, OBJPROP_SELECTABLE, false);
        
        // Rename to standard name after creation
        ObjectSetString(0, high_name, OBJPROP_NAME, "DynamicRange_High");
        
        string high_text = "DynamicRange_HighText_" + IntegerToString(TimeCurrent()); // Unique name
        ObjectCreate(0, high_text, OBJ_TEXT, 0, high_time, period_high + PointsToPrice(10));
        ObjectSetString(0, high_text, OBJPROP_TEXT, EnumToString(InpRangeTimeframe) + " High: " + DoubleToString(period_high, _Digits));
        ObjectSetInteger(0, high_text, OBJPROP_COLOR, is_dark_theme ? clrRed : clrDarkRed);
        ObjectSetInteger(0, high_text, OBJPROP_FONTSIZE, 8);
        ObjectSetInteger(0, high_text, OBJPROP_ANCHOR, ANCHOR_LEFT_LOWER);
        ObjectSetInteger(0, high_text, OBJPROP_SELECTABLE, false);
        
        // Rename to standard name after creation
        ObjectSetString(0, high_text, OBJPROP_NAME, "DynamicRange_HighText");
        
        DebugPrint("Created High marker at: " + DoubleToString(period_high, _Digits));
        
        DrawHighQuotaLines(high_time, period_high);
    }
    
    // Mark period low point - arrow at +25% line (LOW + 1 quota)
    if(period_low > 0 && period_low != DBL_MAX)
    {
        double low_arrow_price = period_low + PointsToPrice(g_dynamicQuotaValue);
        
        string low_name = "DynamicRange_Low_" + IntegerToString(TimeCurrent()); // Unique name
        ObjectCreate(0, low_name, OBJ_ARROW_UP, 0, low_time, low_arrow_price);
        ObjectSetInteger(0, low_name, OBJPROP_COLOR, is_dark_theme ? clrLime : clrGreen);
        ObjectSetInteger(0, low_name, OBJPROP_WIDTH, 4);
        ObjectSetInteger(0, low_name, OBJPROP_ARROWCODE, 241);
        ObjectSetInteger(0, low_name, OBJPROP_BACK, false);
        ObjectSetInteger(0, low_name, OBJPROP_SELECTABLE, false);
        
        // Rename to standard name after creation
        ObjectSetString(0, low_name, OBJPROP_NAME, "DynamicRange_Low");
        
        string low_text = "DynamicRange_LowText_" + IntegerToString(TimeCurrent()); // Unique name
        ObjectCreate(0, low_text, OBJ_TEXT, 0, low_time, period_low - PointsToPrice(10));
        ObjectSetString(0, low_text, OBJPROP_TEXT, EnumToString(InpRangeTimeframe) + " Low: " + DoubleToString(period_low, _Digits));
        ObjectSetInteger(0, low_text, OBJPROP_COLOR, is_dark_theme ? clrBlue : clrDarkBlue);
        ObjectSetInteger(0, low_text, OBJPROP_FONTSIZE, 8);
        ObjectSetInteger(0, low_text, OBJPROP_ANCHOR, ANCHOR_LEFT_UPPER);
        ObjectSetInteger(0, low_text, OBJPROP_SELECTABLE, false);
        
        // Rename to standard name after creation
        ObjectSetString(0, low_text, OBJPROP_NAME, "DynamicRange_LowText");
        
        DebugPrint("Created Low marker at: " + DoubleToString(period_low, _Digits));
        
        DrawLowQuotaLines(low_time, period_low);
    }
    
    // Add quota information to the display
    string quota_info = "DynamicRange_QuotaInfo_" + IntegerToString(TimeCurrent()); // Unique name
    ObjectCreate(0, quota_info, OBJ_LABEL, 0, 0, 0);
    ObjectSetInteger(0, quota_info, OBJPROP_CORNER, CORNER_LEFT_UPPER);
    ObjectSetInteger(0, quota_info, OBJPROP_XDISTANCE, 10);
    ObjectSetInteger(0, quota_info, OBJPROP_YDISTANCE, 30);
    ObjectSetString(0, quota_info, OBJPROP_TEXT, 
                   EnumToString(InpRangeTimeframe) + " - Avg Range: " + DoubleToString(g_averagePeriodRange, 1) + 
                   " pts | Quota: " + DoubleToString(g_dynamicQuotaValue, 1) + " pts (" + IntegerToString(g_totalPeriodsAnalyzed) + " periods)");
    ObjectSetInteger(0, quota_info, OBJPROP_COLOR, is_dark_theme ? clrYellow : clrDarkGoldenrod);
    ObjectSetInteger(0, quota_info, OBJPROP_FONTSIZE, 9);
    ObjectSetInteger(0, quota_info, OBJPROP_SELECTABLE, false);
    
    // Rename to standard name after creation
    ObjectSetString(0, quota_info, OBJPROP_NAME, "DynamicRange_QuotaInfo");
    
    // Add period time info
    string period_info = "DynamicRange_PeriodInfo_" + IntegerToString(TimeCurrent()); // Unique name
    ObjectCreate(0, period_info, OBJ_LABEL, 0, 0, 0);
    ObjectSetInteger(0, period_info, OBJPROP_CORNER, CORNER_LEFT_UPPER);
    ObjectSetInteger(0, period_info, OBJPROP_XDISTANCE, 10);
    ObjectSetInteger(0, period_info, OBJPROP_YDISTANCE, 50);
    ObjectSetString(0, period_info, OBJPROP_TEXT, 
                   "Current Period: " + TimeToString(period_start, TIME_DATE|TIME_MINUTES) + 
                   " - " + TimeToString(period_end, TIME_DATE|TIME_MINUTES));
    ObjectSetInteger(0, period_info, OBJPROP_COLOR, is_dark_theme ? clrLightBlue : clrDarkBlue);
    ObjectSetInteger(0, period_info, OBJPROP_FONTSIZE, 8);
    ObjectSetInteger(0, period_info, OBJPROP_SELECTABLE, false);
    
    // Rename to standard name after creation
    ObjectSetString(0, period_info, OBJPROP_NAME, "DynamicRange_PeriodInfo");
    
    DebugPrint("✅ Lines redrawn successfully for " + EnumToString(InpRangeTimeframe) + " period");
}
//+------------------------------------------------------------------+