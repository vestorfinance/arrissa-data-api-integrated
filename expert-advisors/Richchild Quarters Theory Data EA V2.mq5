//+------------------------------------------------------------------+
//|                    Richchild Quarters Theory Data EA V2.mq5      |
//|                            Copyright 2026, Arrissa Technologies. |
//|                                            https://arrissa.trade |
//+------------------------------------------------------------------+
#property copyright "Copyright 2026, Arrissa Technologies, Flowbase."
#property link      "https://flowbase.store"
#property version   "2.0"
#property strict

//--- input parameters
input int InpPeriodsLookback = 30;        // Periods to look back for average calculation
input bool InpIgnoreSunday = true;        // Ignore Sundays in calculation
input bool InpDebugMode = false;          // Enable debug output
input string AppBaseURL = "http://127.0.0.1"; //Base URL. Leave default if localhost or enter domain: https://arrissadata.com
string InpApiUrl = ""; // API URL (built in OnInit from AppBaseURL)
input bool InpEnableApi = true;           // Enable API communication
input int InpApiPollingSeconds = 2;       // API polling interval in seconds

//--- global variables
datetime last_api_poll_time = 0;
bool api_processing_lock = false;
datetime g_pretend_datetime = 0;
bool g_use_pretend_time = false;

// Timeframe data storage
struct TimeframeData
{
    ENUM_TIMEFRAMES timeframe;
    double quota_value;  // Pre-calculated quota for this timeframe
};

TimeframeData tf_data[];

// Debug data structure
struct DebugInfo
{
    string timeframe;
    int bars_processed;
    datetime period_start;
    datetime period_end;
    double result_high;
    double result_low;
};

DebugInfo g_debug_data[];

//+------------------------------------------------------------------+
//| Debug Print Function                                             |
//+------------------------------------------------------------------+
void DebugPrint(string message)
{
    if(InpDebugMode)
        Print("DEBUG: ", message);
}

//+------------------------------------------------------------------+
//| Get Effective Current Time                                       |
//+------------------------------------------------------------------+
datetime GetEffectiveCurrentTime()
{
    return (g_use_pretend_time && g_pretend_datetime > 0) ? g_pretend_datetime : TimeCurrent();
}

//+------------------------------------------------------------------+
//| Expert initialization function                                   |
//+------------------------------------------------------------------+
int OnInit()
{
    InpApiUrl = AppBaseURL + "/quarters-theory-api-v1/quarters-theory-api.php";
    Print("Richchild Quarters Theory EA V2 initialized");
    DebugPrint("Each timeframe calculates independently");
    
    // Initialize timeframe data
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
    
    // Calculate quota for each timeframe at init (following indicator)
    for(int i = 0; i < 11; i++)
    {
        tf_data[i].quota_value = CalculateQuotaForTimeframe(tf_data[i].timeframe);
        DebugPrint(EnumToString(tf_data[i].timeframe) + " quota: " + DoubleToString(tf_data[i].quota_value, 1));
    }
    
    last_api_poll_time = TimeCurrent();
    EventSetTimer(1);
    
    return(INIT_SUCCEEDED);
}

//+------------------------------------------------------------------+
//| Expert deinitialization function                                 |
//+------------------------------------------------------------------+
void OnDeinit(const int reason)
{
    EventKillTimer();
    Print("Richchild Quarters Theory EA V2 deinitialized");
}

//+------------------------------------------------------------------+
//| Expert tick function                                             |
//+------------------------------------------------------------------+
void OnTick()
{
    // EA runs on timer
}

//+------------------------------------------------------------------+
//| Timer function                                                   |
//+------------------------------------------------------------------+
void OnTimer()
{
    datetime current_time = TimeCurrent();
    if(InpEnableApi && current_time - last_api_poll_time >= InpApiPollingSeconds)
    {
        last_api_poll_time = current_time;
        PollApiForRequests();
    }
}

//+------------------------------------------------------------------+
//| Calculate quota for timeframe (INDICATOR LOGIC: starts at i=1)  |
//+------------------------------------------------------------------+
double CalculateQuotaForTimeframe(ENUM_TIMEFRAMES timeframe)
{
    MqlRates rates[];
    int periods_to_request = InpPeriodsLookback + 50;
    int copied = CopyRates(_Symbol, timeframe, 0, periods_to_request, rates);
    
    if(copied <= 0)
        return 25.0; // Fallback
    
    double totalRange = 0.0;
    int validPeriods = 0;
    
    // START FROM i=1 (skip current incomplete bar) - INDICATOR LOGIC
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
        return (totalRange / validPeriods) / 4.0; // quota = avg / 4
    
    return 25.0;
}

//+------------------------------------------------------------------+
//| Calculate average range for symbol (starts at i=0 for historical)|
//+------------------------------------------------------------------+
double CalculateAverageRangeForSymbol(string symbol, ENUM_TIMEFRAMES timeframe)
{
    MqlRates rates[];
    int periods_to_request = InpPeriodsLookback + 50;
    
    int copied;
    if(g_use_pretend_time && g_pretend_datetime > 0)
    {
        int start_bar = iBarShift(symbol, timeframe, g_pretend_datetime, true);
        if(start_bar < 0) start_bar = 0;
        copied = CopyRates(symbol, timeframe, start_bar, periods_to_request, rates);
    }
    else
    {
        copied = CopyRates(symbol, timeframe, 0, periods_to_request, rates);
    }
    
    if(copied <= 0)
        return 100.0;
    
    double point = SymbolInfoDouble(symbol, SYMBOL_POINT);
    if(point <= 0) return 100.0;
    
    double totalRange = 0.0;
    int validPeriods = 0;
    
    // START FROM i=1 for normal mode (skip current incomplete bar - INDICATOR LOGIC)
    // START FROM i=0 for pretend mode (all bars are complete historical bars)
    int start_index = g_use_pretend_time ? 0 : 1;
    
    for(int i = start_index; i < copied && validPeriods < InpPeriodsLookback; i++)
    {
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
        return totalRange / validPeriods;
    
    return 100.0;
}

//+------------------------------------------------------------------+
//| Get period start                                                 |
//+------------------------------------------------------------------+
datetime GetPeriodStart(ENUM_TIMEFRAMES timeframe)
{
    datetime current_time = GetEffectiveCurrentTime();
    MqlDateTime dt;
    TimeToStruct(current_time, dt);
    
    switch(timeframe)
    {
        case PERIOD_W1:
            dt.hour = 0;
            dt.min = 0;
            dt.sec = 0;
            return StructToTime(dt) - (dt.day_of_week * 86400);
            
        case PERIOD_MN1:
            dt.day = 1;
            dt.hour = 0;
            dt.min = 0;
            dt.sec = 0;
            return StructToTime(dt);
            
        case PERIOD_D1:
        {
            dt.hour = 0;
            dt.min = 0;
            dt.sec = 0;
            datetime day_start = StructToTime(dt);
            if(current_time < day_start)
                day_start -= 86400;
            return day_start;
        }
            
        default:
        {
            dt.hour = 0;
            dt.min = 0;
            dt.sec = 0;
            datetime day_base = StructToTime(dt);
            int period_seconds = PeriodSeconds(timeframe);
            int seconds_since_midnight = (int)(current_time - day_base);
            int period_number = seconds_since_midnight / period_seconds;
            return day_base + (period_number * period_seconds);
        }
    }
}

//+------------------------------------------------------------------+
//| Get period end                                                   |
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
//| Calculate countdown                                              |
//+------------------------------------------------------------------+
string CalculateCountdown(ENUM_TIMEFRAMES timeframe)
{
    datetime current_time = GetEffectiveCurrentTime();
    datetime period_end = GetPeriodEnd(timeframe);
    int remaining_seconds = (int)(period_end - current_time);
    
    if(remaining_seconds <= 0)
        return "00:00";
    
    if(timeframe <= PERIOD_M30)
    {
        int minutes = remaining_seconds / 60;
        int seconds = remaining_seconds % 60;
        return StringFormat("%02d:%02d", minutes, seconds);
    }
    else if(timeframe <= PERIOD_H12 || timeframe == PERIOD_D1)
    {
        int hours = remaining_seconds / 3600;
        int minutes = (remaining_seconds % 3600) / 60;
        return StringFormat("%02d:%02d", hours, minutes);
    }
    else
    {
        int days = remaining_seconds / 86400;
        int hours = (remaining_seconds % 86400) / 3600;
        int minutes = (remaining_seconds % 3600) / 60;
        
        if(days > 0)
            return StringFormat("%dD %02d:%02d", days, hours, minutes);
        else
            return StringFormat("%02d:%02d", hours, minutes);
    }
}

//+------------------------------------------------------------------+
//| Get period high/low for symbol                                   |
//+------------------------------------------------------------------+
double GetPeriodHighLowForSymbol(string symbol, datetime period_start, datetime period_end, bool get_high, double fallback_price, ENUM_TIMEFRAMES timeframe)
{
    double result = get_high ? 0 : DBL_MAX;
    
    // Use appropriate scan timeframe based on target timeframe
    ENUM_TIMEFRAMES scan_tf = PERIOD_M1;
    if(timeframe == PERIOD_MN1)
        scan_tf = PERIOD_D1;  // Use D1 for monthly
    else if(timeframe == PERIOD_W1)
        scan_tf = PERIOD_H1;  // Use H1 for weekly
    else
        scan_tf = PERIOD_M1;  // Use M1 for everything else
    datetime end_time = g_use_pretend_time ? g_pretend_datetime : TimeCurrent();
    
    int start_bar = 0;
    if(g_use_pretend_time)
    {
        start_bar = iBarShift(symbol, scan_tf, g_pretend_datetime, true);
        if(start_bar < 0) start_bar = 0;
    }
    
    int total_bars = iBars(symbol, scan_tf);
    int max_bars = MathMin(total_bars - start_bar, 10000);
    bool found_any = false;
    
    for(int i = start_bar; i < start_bar + max_bars; i++)
    {
        datetime bar_time = iTime(symbol, scan_tf, i);
        if(bar_time == 0)
            break;  // No more bars
        
        // We scan from newest to oldest (bar 0 = newest)
        // Skip bars that are too new (after period_end or after end_time)
        if(bar_time >= period_end || bar_time >= end_time)
            continue;
        
        // Stop when we reach bars before the period started
        if(bar_time < period_start)
            break;
        
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
    
    if(!found_any || (get_high && result == 0) || (!get_high && result == DBL_MAX))
        result = fallback_price;
    
    return result;
}

//+------------------------------------------------------------------+
//| Poll API for pending requests                                    |
//+------------------------------------------------------------------+
void PollApiForRequests()
{
    if(!InpEnableApi || api_processing_lock)
        return;
        
    char result_data[];
    char post_data[];
    string result_headers;
    
    int res = WebRequest("GET", InpApiUrl, "", NULL, 15000, post_data, 0, result_data, result_headers);
    
    if(res == -1)
    {
        int error = GetLastError();
        if(error == 4060)
            DebugPrint("WebRequest not allowed. Add URL to Tools->Options->Expert Advisors");
        return;
    }
    
    if(res == 200)
    {
        string response = CharArrayToString(result_data, 0, WHOLE_ARRAY, CP_UTF8);
        
        if(StringFind(response, "request_id") >= 0 && StringFind(response, "symbol") >= 0)
        {
            api_processing_lock = true;
            ProcessApiRequest(response);
            api_processing_lock = false;
        }
    }
}

//+------------------------------------------------------------------+
//| Process API request                                              |
//+------------------------------------------------------------------+
void ProcessApiRequest(string request_json)
{
    string request_id = ExtractJsonValue(request_json, "request_id");
    string requested_symbol = ExtractJsonValue(request_json, "symbol");
    
    if(request_id == "" || requested_symbol == "")
        return;
    
    // Parse pretend parameters
    string pretend_date = ExtractJsonValue(request_json, "pretend_date");
    string pretend_time = ExtractJsonValue(request_json, "pretend_time");
    
    g_use_pretend_time = false;
    g_pretend_datetime = 0;
    
    if(pretend_date != "")
    {
        string datetime_string = pretend_date + " " + (pretend_time != "" ? pretend_time : "00:00");
        g_pretend_datetime = StringToTime(datetime_string);
        if(g_pretend_datetime > 0)
        {
            g_use_pretend_time = true;
            DebugPrint("Using pretend time: " + datetime_string);
        }
    }
    
    // Build and send response
    string quarters_json = BuildQuartersJsonForSymbol(requested_symbol);
    SendDataToApi(request_id, requested_symbol, quarters_json);
    
    // Reset pretend time
    g_use_pretend_time = false;
    g_pretend_datetime = 0;
}

//+------------------------------------------------------------------+
//| Build quarters JSON for symbol                                   |
//+------------------------------------------------------------------+
string BuildQuartersJsonForSymbol(string symbol)
{
    // Get current price
    double current_price;
    if(g_use_pretend_time && g_pretend_datetime > 0)
    {
        int shift = iBarShift(symbol, PERIOD_M1, g_pretend_datetime, true);
        if(shift >= 0)
        {
            datetime bar_time = iTime(symbol, PERIOD_M1, shift);
            current_price = (bar_time == g_pretend_datetime) ? 
                           iClose(symbol, PERIOD_M1, shift + 1) : 
                           iClose(symbol, PERIOD_M1, shift);
        }
        else
        {
            current_price = SymbolInfoDouble(symbol, SYMBOL_BID);
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
    json += "\"timeframes\":[";
    
    ENUM_TIMEFRAMES timeframes[] = {PERIOD_M15, PERIOD_M30, PERIOD_H1, PERIOD_H4, PERIOD_H6, PERIOD_H12, PERIOD_D1, PERIOD_W1, PERIOD_MN1};
    string timeframe_names[] = {"M15", "M30", "H1", "H4", "H6", "H12", "D1", "W1", "MN1"};
    
    for(int i = 0; i < ArraySize(timeframes); i++)
    {
        if(i > 0) json += ",";
        
        ENUM_TIMEFRAMES tf = timeframes[i];
        datetime period_start = GetPeriodStart(tf);
        datetime period_end = GetPeriodEnd(tf);
        
        double high = GetPeriodHighLowForSymbol(symbol, period_start, period_end, true, current_price, tf);
        double low = GetPeriodHighLowForSymbol(symbol, period_start, period_end, false, current_price, tf);
        
        // Calculate quota for THIS timeframe
        double avg_range = CalculateAverageRangeForSymbol(symbol, tf);
        double tf_quota = avg_range / 4.0;
        double quota_price = tf_quota * point;
        
        // Calculate percentages
        double low_percentage = 50.0;
        double high_percentage = -50.0;
        
        if(high > low && quota_price > 0)
        {
            // Calculate how many quotas from low/high (INDICATOR LOGIC)
            double low_distance = current_price - low;
            double low_quotas = low_distance / quota_price;
            low_percentage = (low_quotas / 4.0) * 100.0;  // 4 quotas = 100%
            
            double high_distance = current_price - high;
            double high_quotas = high_distance / quota_price;
            high_percentage = (high_quotas / 4.0) * 100.0;  // 4 quotas = 100%
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
    
    json += "]}";
    
    return json;
}

//+------------------------------------------------------------------+
//| Send data to API                                                 |
//+------------------------------------------------------------------+
void SendDataToApi(string request_id, string symbol, string quarters_json)
{
    char result_data[];
    string result_headers;
    
    string post_data = "request_id=" + request_id + "&symbol=" + symbol + "&quarters_data=" + UrlEncode(quarters_json);
    
    char post_array[];
    StringToCharArray(post_data, post_array, 0, StringLen(post_data), CP_UTF8);
    
    int res = WebRequest("POST", InpApiUrl, "Content-Type: application/x-www-form-urlencoded\r\n", 
                        NULL, 20000, post_array, ArraySize(post_array), result_data, result_headers);
    
    if(res == 200)
        DebugPrint("Data sent successfully for request_id: " + request_id);
    else
        DebugPrint("Send failed with status: " + IntegerToString(res));
}

//+------------------------------------------------------------------+
//| Extract JSON value                                               |
//+------------------------------------------------------------------+
string ExtractJsonValue(string json, string key)
{
    string search_key = "\"" + key + "\"";
    int pos = StringFind(json, search_key);
    if(pos < 0) return "";
    
    int colon_pos = StringFind(json, ":", pos);
    if(colon_pos < 0) return "";
    
    int value_start = colon_pos + 1;
    while(value_start < StringLen(json) && 
          (StringGetCharacter(json, value_start) == ' ' || 
           StringGetCharacter(json, value_start) == '\t' ||
           StringGetCharacter(json, value_start) == '\n'))
    {
        value_start++;
    }
    
    bool is_string = (StringGetCharacter(json, value_start) == '"');
    if(is_string) value_start++;
    
    int value_end = value_start;
    if(is_string)
    {
        while(value_end < StringLen(json) && StringGetCharacter(json, value_end) != '"')
        {
            if(StringGetCharacter(json, value_end) == '\\')
                value_end++;
            value_end++;
        }
    }
    else
    {
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
//| URL Encode                                                        |
//+------------------------------------------------------------------+
string UrlEncode(string str)
{
    string result = "";
    int len = StringLen(str);
    
    for(int i = 0; i < len; i++)
    {
        ushort ch = StringGetCharacter(str, i);
        
        if((ch >= 'A' && ch <= 'Z') || (ch >= 'a' && ch <= 'z') || (ch >= '0' && ch <= '9') ||
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
