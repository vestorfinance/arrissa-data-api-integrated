//+------------------------------------------------------------------+
//|                                              TrendBiasAPI.mq5    |
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
//  Polls /trend-bias-api-v1/trend-bias-api.php for pending requests.
//  On a request the EA determines BULLISH or BEARISH bias for the
//  requested symbol and timeframe using ZigZag-style swing detection
//  and POSTs the result back.
//
//  Algorithm — no-lookahead ZigZag swing detection:
//    For each bar in [fromBar+1+depth .. fromBar+lookback-depth]:
//      A local HIGH if high[i] is the maximum in [i-depth .. i+depth].
//      A local LOW  if low[i]  is the minimum in [i-depth .. i+depth].
//    fromBar=0 in live mode. In pretend mode, fromBar = iBarShift of
//    the pretend datetime — only bars before that point are used,
//    eliminating all look-ahead bias.
//
//  Bias logic (mirrors ZigZagBias.mq5):
//    s0 = newest confirmed swing in window (may still be unfolding)
//    s1 = second confirmed swing (fully committed corner)
//    BULLISH  if s1 was a LOW  (price reversed upward after it)
//    BEARISH  if s1 was a HIGH (price reversed downward after it)
//
//+------------------------------------------------------------------+
#property copyright "Copyright 2026, Arrissa Technologies."
#property link      "https://arrissa.trade"
#property version   "1.00"

input string AppBaseURL           = "http://127.0.0.1"; // Base URL (leave default for localhost)
input int    InpDepth             = 12;                  // Swing depth — bars each side for confirmation
input int    InpLookback          = 500;                 // Bars to scan back for swings
input bool   InpEnableApi         = true;                // Enable API communication
input int    InpApiPollingSeconds = 1;                   // Polling interval in seconds
input bool   InpDebugMode         = false;               // Enable debug output

string   InpApiUrl          = "";
datetime last_api_poll_time = 0;
bool     api_lock           = false;

struct SwingPt
{
    double   price;
    bool     isHigh;
    datetime time;
};

//+------------------------------------------------------------------+
void DebugPrint(string msg)
{
    if(InpDebugMode) Print("DEBUG: ", msg);
}

//+------------------------------------------------------------------+
int OnInit()
{
    InpApiUrl = AppBaseURL + "/trend-bias-api-v1/trend-bias-api.php";
    Print("Trend Bias API EA initialized. Endpoint: ", InpApiUrl);
    EventSetTimer(1);
    last_api_poll_time = TimeCurrent();
    return INIT_SUCCEEDED;
}

//+------------------------------------------------------------------+
void OnDeinit(const int reason)
{
    EventKillTimer();
    Print("Trend Bias API EA deinitialized.");
}

//+------------------------------------------------------------------+
void OnTick() {}

//+------------------------------------------------------------------+
void OnTimer()
{
    datetime now = TimeCurrent();
    if(InpEnableApi && !api_lock && (now - last_api_poll_time) >= InpApiPollingSeconds)
    {
        last_api_poll_time = now;
        PollApi();
    }
}

//+------------------------------------------------------------------+
void PollApi()
{
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
        if(StringFind(response, "request_id") >= 0 && StringFind(response, "timeframe") >= 0)
        {
            api_lock = true;
            ProcessRequest(response);
            api_lock = false;
        }
    }
}

//+------------------------------------------------------------------+
void ProcessRequest(string json)
{
    string request_id = ExtractJsonValue(json, "request_id");
    string symbol     = ExtractJsonValue(json, "symbol");
    string tf_str     = ExtractJsonValue(json, "timeframe");
    string pre_date   = ExtractJsonValue(json, "pretend_date");
    string pre_time   = ExtractJsonValue(json, "pretend_time");

    if(request_id == "" || symbol == "" || tf_str == "")
    {
        DebugPrint("ProcessRequest: missing required fields");
        return;
    }

    ENUM_TIMEFRAMES tf = StringToTF(tf_str);

    //--- Resolve pretend datetime and bar offset ---
    datetime pretendDT           = 0;
    int      fromBar             = 0;
    string   pretend_datetime_str = "";

    if(pre_date != "")
    {
        int h = 0, m = 0;
        if(pre_time != "")
        {
            string tp[];
            StringSplit(pre_time, ':', tp);
            if(ArraySize(tp) > 0) h = (int)StringToInteger(tp[0]);
            if(ArraySize(tp) > 1) m = (int)StringToInteger(tp[1]);
        }

        pretendDT = StringToTime(pre_date);
        MqlDateTime mdt;
        TimeToStruct(pretendDT, mdt);
        mdt.hour = h; mdt.min = m; mdt.sec = 0;
        pretendDT = StructToTime(mdt);

        pretend_datetime_str = TimeToString(pretendDT, TIME_DATE | TIME_MINUTES | TIME_SECONDS);

        fromBar = iBarShift(symbol, tf, pretendDT, true);
        if(fromBar < 0)
            fromBar = iBarShift(symbol, tf, pretendDT, false);

        if(fromBar < 0)
        {
            DebugPrint("iBarShift failed for pretend time " + pretend_datetime_str);
            PostError(request_id, symbol, "No bar found at pretend datetime: " + pretend_datetime_str);
            return;
        }

        DebugPrint("Pretend mode — pretendDT=" + pretend_datetime_str + " fromBar=" + (string)fromBar);
    }

    //--- Find last two confirmed swings (no lookahead) ---
    SwingPt s0, s1;
    bool found = FindLastTwoSwings(symbol, tf, fromBar, InpLookback, InpDepth, s0, s1);

    if(!found)
    {
        PostError(request_id, symbol,
            "Not enough swing data. Increase lookback or use a symbol with more history.");
        return;
    }

    //--- Determine bias from the fully confirmed swing (s1) ---
    // s1 is the last corner that committed the current trend direction.
    // A LOW corner means price reversed up after it   → BULLISH
    // A HIGH corner means price reversed down after it → BEARISH
    string bias = s1.isHigh ? "BEARISH" : "BULLISH";

    int digits = (int)SymbolInfoInteger(symbol, SYMBOL_DIGITS);

    string payload = "{\n";
    payload += "  \"symbol\":                \"" + symbol                                                      + "\",\n";
    payload += "  \"timeframe\":             \"" + tf_str                                                      + "\",\n";
    payload += "  \"bias\":                  \"" + bias                                                        + "\",\n";
    payload += "  \"pretend_datetime\":      \"" + pretend_datetime_str                                        + "\",\n";
    payload += "  \"confirmed_swing_price\": "   + DoubleToString(s1.price, digits)                           + ",\n";
    payload += "  \"confirmed_swing_time\":  \"" + TimeToString(s1.time, TIME_DATE | TIME_MINUTES | TIME_SECONDS) + "\",\n";
    payload += "  \"confirmed_swing_type\":  \"" + (s1.isHigh ? "HIGH" : "LOW")                               + "\",\n";
    payload += "  \"forming_swing_price\":   "   + DoubleToString(s0.price, digits)                           + ",\n";
    payload += "  \"forming_swing_time\":    \"" + TimeToString(s0.time, TIME_DATE | TIME_MINUTES | TIME_SECONDS) + "\",\n";
    payload += "  \"forming_swing_type\":    \"" + (s0.isHigh ? "HIGH" : "LOW")                               + "\"\n";
    payload += "}";

    DebugPrint("Bias=" + bias + " s1=" + (s1.isHigh ? "HIGH" : "LOW") + "@" + DoubleToString(s1.price, digits)
               + " " + TimeToString(s1.time, TIME_DATE | TIME_MINUTES));

    SendDataToApi(request_id, symbol, payload);
}

//+------------------------------------------------------------------+
//  No-lookahead ZigZag-style swing detector.
//
//  Scans bars from [fromBar + 1 + depth] to [fromBar + lookback - depth].
//  For each bar i:
//    isHigh if high[i] > high[j] for all j in [i-depth .. i+depth], j != i
//    isLow  if low[i]  < low[j]  for all j in [i-depth .. i+depth], j != i
//
//  The "new side" check (j = i-d) accesses bars i-1 .. i-depth.
//  Starting at i = fromBar + 1 + depth ensures i-depth >= fromBar+1,
//  so no bar after the pretend time is ever read.
//
//  s0 = newest confirmed swing (may still be unfolding near pretend time)
//  s1 = second confirmed swing (the committed corner that sets direction)
//
//  Returns false if fewer than 2 swings are found.
//+------------------------------------------------------------------+
bool FindLastTwoSwings(string symbol, ENUM_TIMEFRAMES tf,
                        int fromBar, int lookback, int depth,
                        SwingPt &s0, SwingPt &s1)
{
    int count    = 0;
    int startBar = fromBar + 1 + depth;   // need `depth` closed bars on the new side
    int maxBars  = Bars(symbol, tf) - 1;
    int endBar   = fromBar + lookback - depth;
    if(endBar > maxBars - depth) endBar = maxBars - depth;

    for(int i = startBar; i <= endBar && count < 2; i++)
    {
        double h_i = iHigh(symbol, tf, i);
        double l_i = iLow(symbol, tf, i);
        bool   isH = true;
        bool   isL = true;

        for(int d = 1; d <= depth && (isH || isL); d++)
        {
            if(iHigh(symbol, tf, i - d) >= h_i) isH = false;
            if(iHigh(symbol, tf, i + d) >= h_i) isH = false;
            if(iLow(symbol, tf,  i - d) <= l_i) isL = false;
            if(iLow(symbol, tf,  i + d) <= l_i) isL = false;
        }

        if(isH || isL)
        {
            SwingPt sw;
            sw.isHigh = isH;
            sw.price  = isH ? h_i : l_i;
            sw.time   = iTime(symbol, tf, i);

            if(count == 0) s0 = sw;
            else           s1 = sw;
            count++;
        }
    }

    return count >= 2;
}

//+------------------------------------------------------------------+
ENUM_TIMEFRAMES StringToTF(string s)
{
    if(s == "M1")  return PERIOD_M1;
    if(s == "M5")  return PERIOD_M5;
    if(s == "M15") return PERIOD_M15;
    if(s == "M30") return PERIOD_M30;
    if(s == "H1")  return PERIOD_H1;
    if(s == "H4")  return PERIOD_H4;
    if(s == "D1")  return PERIOD_D1;
    if(s == "W1")  return PERIOD_W1;
    if(s == "MN1") return PERIOD_MN1;
    return PERIOD_D1;
}

//+------------------------------------------------------------------+
void PostError(string request_id, string symbol, string msg)
{
    string payload = "{ \"error\": \"" + msg + "\" }";
    SendDataToApi(request_id, symbol, payload);
}

//+------------------------------------------------------------------+
void SendDataToApi(string request_id, string symbol, string payload)
{
    char result_data[];
    string result_headers;

    string post_str = "request_id=" + request_id
                    + "&symbol="    + symbol
                    + "&payload="   + UrlEncode(payload);

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
        DebugPrint("Sent — request_id=" + request_id + " symbol=" + symbol);
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
