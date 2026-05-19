//+------------------------------------------------------------------+
//|                                              TimeMachineAPI.mq5  |
//|                          Copyright 2026, Arrissa Technologies.   |
//|                                          https://arrissa.trade   |
//+------------------------------------------------------------------+
//
//  Author : Ngonidzashe Jiji
//  Handles: Instagram: @davidrichchild
//           Telegram: t.me/david_richchild
//           TikTok: davidrichchild
//  URLs   : https://arrissadata.com
//           https://arrissatechnologies.com
//           https://arrissa.trade
//
//  On-demand Time Machine ML payload via HTTP queue API.
//  Polls /time-machine-ml-api-v1/time-machine-ml-api.php every
//  InpApiPollingSeconds seconds. On a pending request the EA builds
//  the JSON payload and POSTs it back to the same endpoint.
//
//+------------------------------------------------------------------+
#property copyright "Copyright 2026, Arrissa Technologies."
#property link      "https://arrissa.trade"
#property version   "2.00"

input string AppBaseURL            = "http://127.0.0.1"; // Base URL (leave default for localhost)
input bool   InpEnableApi          = true;               // Enable API communication
input int    InpApiPollingSeconds   = 1;                  // Polling interval in seconds
input bool   InpDebugMode          = false;              // Enable debug output

string   InpApiUrl           = "";
datetime last_api_poll_time  = 0;
bool     api_processing_lock = false;

// Pretend-time state (set per request, reset after)
datetime g_pretend_datetime = 0;
bool     g_use_pretend_time = false;

//+------------------------------------------------------------------+
datetime GetEffectiveTime()
{
    return (g_use_pretend_time && g_pretend_datetime > 0) ? g_pretend_datetime : TimeCurrent();
}

//+------------------------------------------------------------------+
void DebugPrint(string msg)
{
    if(InpDebugMode) Print("DEBUG: ", msg);
}

//+------------------------------------------------------------------+
int OnInit()
{
    InpApiUrl = AppBaseURL + "/time-machine-ml-api-v1/time-machine-ml-api.php";
    Print("TimeMachine ML API EA v2 initialized. Endpoint: ", InpApiUrl);
    EventSetTimer(1);
    last_api_poll_time = TimeCurrent();
    return INIT_SUCCEEDED;
}

//+------------------------------------------------------------------+
void OnDeinit(const int reason)
{
    EventKillTimer();
    Print("TimeMachine ML API EA deinitialized.");
}

//+------------------------------------------------------------------+
void OnTick() {}

//+------------------------------------------------------------------+
void OnTimer()
{
    datetime now = TimeCurrent();
    if(InpEnableApi && (now - last_api_poll_time) >= InpApiPollingSeconds)
    {
        last_api_poll_time = now;
        PollApiForRequests();
    }
}

//+------------------------------------------------------------------+
string Fmt(datetime t)
{
    MqlDateTime s; TimeToStruct(t, s);
    return StringFormat("%04d-%02d-%02d %02d:%02d:%02d",
                        s.year, s.mon, s.day, s.hour, s.min, s.sec);
}

//+------------------------------------------------------------------+
string Dbl(double v, int digits)
{
    return DoubleToString(v, digits);
}

//+------------------------------------------------------------------+
void PollApiForRequests()
{
    if(!InpEnableApi || api_processing_lock) return;

    char result_data[];
    char post_data[];
    string result_headers;

    int res = WebRequest("GET", InpApiUrl, "", NULL, 15000, post_data, 0, result_data, result_headers);

    if(res == -1)
    {
        int err = GetLastError();
        if(err == 4060)
            DebugPrint("WebRequest not allowed — add " + InpApiUrl + " in Tools > Options > Expert Advisors");
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
void ProcessApiRequest(string request_json)
{
    string request_id   = ExtractJsonValue(request_json, "request_id");
    string symbol       = ExtractJsonValue(request_json, "symbol");
    string pretend_date = ExtractJsonValue(request_json, "pretend_date");
    string pretend_time = ExtractJsonValue(request_json, "pretend_time");

    if(request_id == "" || symbol == "")
    {
        DebugPrint("ProcessApiRequest: missing request_id or symbol");
        return;
    }

    // Configure pretend time if supplied
    g_use_pretend_time = false;
    g_pretend_datetime = 0;

    if(pretend_date != "")
    {
        string dt_str = pretend_date + " " + (pretend_time != "" ? pretend_time : "00:00");
        g_pretend_datetime = StringToTime(dt_str);
        if(g_pretend_datetime > 0)
        {
            g_use_pretend_time = true;
            DebugPrint("Pretend time set: " + dt_str);
        }
    }

    DebugPrint("Processing request_id=" + request_id + " symbol=" + symbol
               + (g_use_pretend_time ? " pretend=" + Fmt(g_pretend_datetime) : " live"));

    string payload = BuildPayload(symbol);
    SendDataToApi(request_id, symbol, payload);

    // Reset pretend state
    g_use_pretend_time = false;
    g_pretend_datetime = 0;
}

//+------------------------------------------------------------------+
string BuildPayload(string symbol)
{
    int digits = (int)SymbolInfoInteger(symbol, SYMBOL_DIGITS);

    // Effective time — live or pretend
    datetime now     = GetEffectiveTime();
    datetime h1_open = (datetime)(MathFloor((double)now / 3600.0) * 3600);

    // Bar shifts: 0 in live mode; historical offsets in pretend mode
    int h1_shift = 0;
    int m1_shift = 0;

    if(g_use_pretend_time && g_pretend_datetime > 0)
    {
        h1_shift = iBarShift(symbol, PERIOD_H1, g_pretend_datetime, true);
        if(h1_shift < 0) h1_shift = 0;

        m1_shift = iBarShift(symbol, PERIOD_M1, g_pretend_datetime, true);
        if(m1_shift < 0) m1_shift = 0;
    }

    // ATR14 on H1 — confirmed bar just before the effective H1 bar
    double atr_val = 0;
    {
        int atr_handle = iATR(symbol, PERIOD_H1, 14);
        if(atr_handle != INVALID_HANDLE)
        {
            double buf[1];
            if(CopyBuffer(atr_handle, 0, h1_shift + 1, 1, buf) > 0) atr_val = buf[0];
            IndicatorRelease(atr_handle);
        }
    }

    // Previous 3 completed H1 candles (relative to effective H1 bar)
    string prev_h1 = "";
    for(int i = 1; i <= 3; i++)
    {
        string comma = (i < 3) ? "," : "";
        prev_h1 += StringFormat(
            "    {\"open\":%s,\"high\":%s,\"low\":%s,\"close\":%s}%s\n",
            Dbl(iOpen (symbol, PERIOD_H1, h1_shift + i), digits),
            Dbl(iHigh (symbol, PERIOD_H1, h1_shift + i), digits),
            Dbl(iLow  (symbol, PERIOD_H1, h1_shift + i), digits),
            Dbl(iClose(symbol, PERIOD_H1, h1_shift + i), digits),
            comma
        );
    }

    // Last 5 M1 bars immediately before the effective H1 opened
    int bars_into_hour = (int)((now - h1_open) / 60);
    string pre_m1 = "";
    for(int i = 0; i < 5; i++)
    {
        int    idx   = m1_shift + bars_into_hour + 5 - i;
        string comma = (i < 4) ? "," : "";
        pre_m1 += StringFormat(
            "    {\"high\":%s,\"low\":%s}%s\n",
            Dbl(iHigh(symbol, PERIOD_M1, idx), digits),
            Dbl(iLow (symbol, PERIOD_M1, idx), digits),
            comma
        );
    }

    // Current H1 developing range at effective time
    double run_high   = iHigh (symbol, PERIOD_H1, h1_shift);
    double run_low    = iLow  (symbol, PERIOD_H1, h1_shift);
    double cur_close  = iClose(symbol, PERIOD_M1, m1_shift + 1);
    double close_3ago = iClose(symbol, PERIOD_M1, m1_shift + 4);

    string json = "{\n";
    json += "  \"symbol\":       \"" + symbol + "\",\n";
    json += "  \"broker_time\":  \"" + Fmt(now)     + "\",\n";
    json += "  \"h1_open_time\": \"" + Fmt(h1_open) + "\",\n";
    json += "  \"h1_open\":      "   + Dbl(iOpen(symbol, PERIOD_H1, h1_shift), digits) + ",\n";
    json += "  \"atr14\":        "   + DoubleToString(atr_val, digits) + ",\n";
    json += "\n";
    json += "  \"prev_h1\": [\n" + prev_h1 + "  ],\n";
    json += "\n";
    json += "  \"pre_hour_m1\": [\n" + pre_m1 + "  ],\n";
    json += "\n";
    json += "  \"current_h1_m1\": {\n";
    json += "    \"running_high\":     " + Dbl(run_high,   digits) + ",\n";
    json += "    \"running_low\":      " + Dbl(run_low,    digits) + ",\n";
    json += "    \"current_close\":    " + Dbl(cur_close,  digits) + ",\n";
    json += "    \"close_3_bars_ago\": " + Dbl(close_3ago, digits) + "\n";
    json += "  }\n";
    json += "}";

    return json;
}

//+------------------------------------------------------------------+
void SendDataToApi(string request_id, string symbol, string payload)
{
    char result_data[];
    string result_headers;

    string post_str = "request_id=" + request_id
                    + "&symbol="     + symbol
                    + "&payload="    + UrlEncode(payload);

    char post_array[];
    StringToCharArray(post_str, post_array, 0, StringLen(post_str), CP_UTF8);

    int res = WebRequest(
        "POST", InpApiUrl,
        "Content-Type: application/x-www-form-urlencoded\r\n",
        NULL, 20000,
        post_array, ArraySize(post_array),
        result_data, result_headers
    );

    if(res == 200)
        DebugPrint("Payload sent — request_id=" + request_id + " symbol=" + symbol);
    else
        DebugPrint("Send failed — HTTP " + IntegerToString(res));
}

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
          (StringGetCharacter(json, value_start) == ' '  ||
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
            if(StringGetCharacter(json, value_end) == '\\') value_end++;
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
string UrlEncode(string str)
{
    string result = "";
    int len = StringLen(str);
    for(int i = 0; i < len; i++)
    {
        ushort ch = StringGetCharacter(str, i);
        if((ch >= 'A' && ch <= 'Z') || (ch >= 'a' && ch <= 'z') ||
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
