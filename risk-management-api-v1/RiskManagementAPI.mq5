//+------------------------------------------------------------------+
//|                                        RiskManagementAPI.mq5    |
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
//  On-demand SL/TP calculator via HTTP queue API.
//  Polls /risk-management-api-v1/risk-management-api.php every
//  InpApiPollingSeconds seconds. On a pending request the EA reads
//  symbol, direction (BUY/SELL), and trade_type (scalp/swing/long-term),
//  then calculates structurally-placed SL and TP and POSTs the result back.
//
//  Philosophy:
//    SL  — placed just beyond the nearest swing that INVALIDATES the trade.
//          For BUY: nearest confirmed swing LOW below entry (if price breaks
//          this, the trade is wrong). SL = that_low - buffer.
//          For SELL: nearest confirmed swing HIGH above entry.
//          SL = that_high + buffer.
//          An ATR floor ensures the stop is never irrationally tight.
//
//    TP  — placed just BEFORE the nearest structure where the market is
//          likely to TURN AND REJECT. NOT a fixed R:R multiple.
//          For BUY: nearest confirmed swing HIGH above entry (resistance).
//          TP = that_high - buffer (just before the rejection zone).
//          For SELL: nearest confirmed swing LOW below entry (support).
//          TP = that_low + buffer.
//          R:R is reported as a result, never used to set TP.
//
//  Timeframes per trade type:
//    scalp     — M5   (SL lookback 15 bars, TP lookback 30 bars)
//    swing     — H1   (SL lookback 20 bars, TP lookback 50 bars)
//    long-term — H4   (SL lookback 30 bars, TP lookback 60 bars)
//
//+------------------------------------------------------------------+
#property copyright "Copyright 2026, Arrissa Technologies."
#property link      "https://arrissa.trade"
#property version   "2.00"

input string AppBaseURL           = "http://127.0.0.1"; // Base URL (leave default for localhost)
input bool   InpEnableApi         = true;               // Enable API communication
input int    InpApiPollingSeconds = 1;                  // Polling interval in seconds
input bool   InpDebugMode         = false;              // Enable debug output

string   InpApiUrl           = "";
datetime last_api_poll_time  = 0;
bool     api_processing_lock = false;

//+------------------------------------------------------------------+
void DebugPrint(string msg)
{
    if(InpDebugMode) Print("DEBUG: ", msg);
}

//+------------------------------------------------------------------+
int OnInit()
{
    InpApiUrl = AppBaseURL + "/risk-management-api-v1/risk-management-api.php";
    Print("Risk Management API EA v2 initialized. Endpoint: ", InpApiUrl);
    EventSetTimer(1);
    last_api_poll_time = TimeCurrent();
    return INIT_SUCCEEDED;
}

//+------------------------------------------------------------------+
void OnDeinit(const int reason)
{
    EventKillTimer();
    Print("Risk Management API EA deinitialized.");
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
        if(StringFind(response, "request_id") >= 0 && StringFind(response, "direction") >= 0)
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
    string request_id  = ExtractJsonValue(request_json, "request_id");
    string symbol      = ExtractJsonValue(request_json, "symbol");
    string direction   = ExtractJsonValue(request_json, "direction");
    string trade_type  = ExtractJsonValue(request_json, "trade_type");

    if(request_id == "" || symbol == "" || direction == "" || trade_type == "")
    {
        DebugPrint("ProcessApiRequest: missing required fields");
        return;
    }

    DebugPrint("Processing request_id=" + request_id
               + " symbol=" + symbol
               + " direction=" + direction
               + " trade_type=" + trade_type);

    double sl_price = 0, tp_price = 0, sl_pips = 0, tp_pips = 0, rr_ratio = 0;
    double entry_price = 0, atr_value = 0;
    string sl_method = "", tp_method = "";

    bool ok = CalculateSLTP(symbol, direction, trade_type,
                             sl_price, tp_price, sl_pips, tp_pips,
                             rr_ratio, entry_price, atr_value,
                             sl_method, tp_method);

    if(!ok)
    {
        DebugPrint("CalculateSLTP failed for " + symbol);
        return;
    }

    int digits = (int)SymbolInfoInteger(symbol, SYMBOL_DIGITS);

    string payload = "{\n";
    payload += "  \"symbol\":      \""  + symbol                                    + "\",\n";
    payload += "  \"direction\":   \""  + direction                                  + "\",\n";
    payload += "  \"trade_type\":  \""  + trade_type                                 + "\",\n";
    payload += "  \"entry_price\": "   + DoubleToString(entry_price, digits)         + ",\n";
    payload += "  \"sl\":          "   + DoubleToString(sl_price,    digits)         + ",\n";
    payload += "  \"tp\":          "   + DoubleToString(tp_price,    digits)         + ",\n";
    payload += "  \"sl_pips\":     "   + DoubleToString(sl_pips,     1)              + ",\n";
    payload += "  \"tp_pips\":     "   + DoubleToString(tp_pips,     1)              + ",\n";
    payload += "  \"rr_ratio\":    "   + DoubleToString(rr_ratio,    2)              + ",\n";
    payload += "  \"atr_value\":   "   + DoubleToString(atr_value,   digits)         + ",\n";
    payload += "  \"sl_method\":   \""  + sl_method                                  + "\",\n";
    payload += "  \"tp_method\":   \""  + tp_method                                  + "\"\n";
    payload += "}";

    SendDataToApi(request_id, symbol, payload);
}

//+------------------------------------------------------------------+
// Returns the MOST RECENT confirmed swing low below entry (BUY SL)
// or most recent confirmed swing high above entry (SELL SL).
// Scans from bar 2 (newest confirmed) outward — first hit wins.
// Returns -1 if no qualifying swing found within lookback.
//+------------------------------------------------------------------+
double FindInvalidationSwing(string symbol, ENUM_TIMEFRAMES tf,
                              int lookback, bool is_buy, double entry)
{
    for(int i = 2; i <= lookback; i++)
    {
        if(is_buy)
        {
            double lo      = iLow(symbol, tf, i);
            double lo_prev = iLow(symbol, tf, i + 1);
            double lo_next = iLow(symbol, tf, i - 1);
            if(lo < lo_prev && lo < lo_next && lo < entry)
                return lo;   // most recent swing low below entry — stop here
        }
        else
        {
            double hi      = iHigh(symbol, tf, i);
            double hi_prev = iHigh(symbol, tf, i + 1);
            double hi_next = iHigh(symbol, tf, i - 1);
            if(hi > hi_prev && hi > hi_next && hi > entry)
                return hi;   // most recent swing high above entry — stop here
        }
    }
    return -1.0;
}

//+------------------------------------------------------------------+
// Returns the MOST RECENT confirmed swing high above entry (BUY TP)
// or most recent confirmed swing low below entry (SELL TP).
// Scans from bar 2 outward — first hit wins.
// Returns -1 if no qualifying structure found within lookback.
//+------------------------------------------------------------------+
double FindTargetStructure(string symbol, ENUM_TIMEFRAMES tf,
                            int lookback, bool is_buy, double entry)
{
    for(int i = 2; i <= lookback; i++)
    {
        if(is_buy)
        {
            double hi      = iHigh(symbol, tf, i);
            double hi_prev = iHigh(symbol, tf, i + 1);
            double hi_next = iHigh(symbol, tf, i - 1);
            if(hi > hi_prev && hi > hi_next && hi > entry)
                return hi;   // most recent resistance above entry — stop here
        }
        else
        {
            double lo      = iLow(symbol, tf, i);
            double lo_prev = iLow(symbol, tf, i + 1);
            double lo_next = iLow(symbol, tf, i - 1);
            if(lo < lo_prev && lo < lo_next && lo < entry)
                return lo;   // most recent support below entry — stop here
        }
    }
    return -1.0;
}

//+------------------------------------------------------------------+
// Core SL/TP calculation.
//
// SL: just beyond the nearest structural level that invalidates the trade.
// TP: just before the nearest structural level the market might reject at.
// R:R is a reported result — it is NEVER used to position TP.
//
// Returns false only if critical market data is unavailable.
//+------------------------------------------------------------------+
bool CalculateSLTP(string    symbol,
                   string    direction,
                   string    trade_type,
                   double   &sl_price,
                   double   &tp_price,
                   double   &sl_pips,
                   double   &tp_pips,
                   double   &rr_ratio,
                   double   &entry_price,
                   double   &atr_value,
                   string   &sl_method,
                   string   &tp_method)
{
    bool is_buy = (direction == "BUY");

    // --- Symbol info ---
    int    digits   = (int)SymbolInfoInteger(symbol, SYMBOL_DIGITS);
    double point    = SymbolInfoDouble(symbol, SYMBOL_POINT);
    // Pip = 10 points on 5/3-digit brokers, 1 point on 4/2-digit brokers.
    double pip_size = (digits == 5 || digits == 3) ? point * 10.0 : point;

    // --- Trade-type config ---
    // sl_lookback : bars back to scan for the structural SL level
    // tp_lookback : bars back to scan for the structural TP level
    //               (wider than SL — TP target can be a few bars further)
    // atr_sl_mult : ATR floor — SL is never tighter than this (protects against
    //               a swing that's basically AT entry price)
    // buffer_mult : fraction of ATR used as margin just beyond each structure
    // fallback_rr : R:R multiple used ONLY when no TP structure is found at all
    ENUM_TIMEFRAMES tf;
    int    sl_lookback, tp_lookback;
    double atr_sl_mult, buffer_mult, fallback_rr;

    if(trade_type == "scalp")
    {
        tf           = PERIOD_M1;
        sl_lookback  = 10;    // last ~10 mins — very recent structure only
        tp_lookback  = 20;    // last ~20 mins
        atr_sl_mult  = 0.8;   // floor: just enough to clear the spread
        buffer_mult  = 0.1;   // tight buffer — scalp precision
        fallback_rr  = 1.5;
    }
    else if(trade_type == "swing")
    {
        tf           = PERIOD_M15;
        sl_lookback  = 20;    // last ~5 h of M15 structure
        tp_lookback  = 35;    // last ~9 h
        atr_sl_mult  = 1.0;
        buffer_mult  = 0.2;
        fallback_rr  = 2.0;
    }
    else // long-term
    {
        tf           = PERIOD_M30;
        sl_lookback  = 24;    // last ~12 h of M30 structure
        tp_lookback  = 48;    // last ~24 h
        atr_sl_mult  = 1.2;
        buffer_mult  = 0.3;
        fallback_rr  = 2.5;
    }

    // --- Entry price ---
    entry_price = is_buy
        ? SymbolInfoDouble(symbol, SYMBOL_ASK)
        : SymbolInfoDouble(symbol, SYMBOL_BID);

    if(entry_price <= 0)
    {
        DebugPrint("CalculateSLTP: zero entry price for " + symbol);
        return false;
    }

    // --- ATR(14) on trade-type timeframe (confirmed bar, shift=1) ---
    atr_value = 0;
    {
        int atr_handle = iATR(symbol, tf, 14);
        if(atr_handle == INVALID_HANDLE)
        {
            DebugPrint("CalculateSLTP: iATR failed for " + symbol);
            return false;
        }
        double buf[1];
        if(CopyBuffer(atr_handle, 0, 1, 1, buf) > 0)
            atr_value = buf[0];
        IndicatorRelease(atr_handle);
    }

    if(atr_value <= 0)
    {
        DebugPrint("CalculateSLTP: ATR returned zero for " + symbol);
        return false;
    }

    double buffer      = buffer_mult * atr_value;
    double min_sl_dist = atr_sl_mult * atr_value;

    // ================================================================
    // STOP LOSS — just beyond the most recent structural level that
    // invalidates the trade. No artificial cap — structure decides.
    // ================================================================

    double inv_swing = FindInvalidationSwing(symbol, tf, sl_lookback, is_buy, entry_price);

    if(inv_swing > 0.0)
    {
        double candidate_sl   = is_buy ? inv_swing - buffer : inv_swing + buffer;
        double candidate_dist = MathAbs(entry_price - candidate_sl);

        if(candidate_dist < min_sl_dist)
        {
            // Swing found but basically at entry — widen to ATR floor so we
            // clear the spread and avoid a guaranteed stop-out
            sl_price  = is_buy ? entry_price - min_sl_dist : entry_price + min_sl_dist;
            sl_method = "atr_floor";
        }
        else
        {
            sl_price  = candidate_sl;
            sl_method = is_buy ? "swing_low" : "swing_high";
        }
    }
    else
    {
        // No qualifying swing in lookback window — ATR fallback
        sl_price  = is_buy ? entry_price - min_sl_dist : entry_price + min_sl_dist;
        sl_method = "atr_based";
    }

    sl_price = NormalizeDouble(sl_price, digits);
    double sl_dist = MathAbs(entry_price - sl_price);
    sl_pips = sl_dist / pip_size;

    // ================================================================
    // TAKE PROFIT — just before the nearest structure the market may
    // turn and reject at. R:R is a result, not a target.
    // ================================================================

    double tgt_struct = FindTargetStructure(symbol, tf, tp_lookback, is_buy, entry_price);

    bool tp_set = false;

    if(tgt_struct > 0.0)
    {
        double tp_candidate;
        if(is_buy)
        {
            // TP just before the resistance — slightly below the swing high
            tp_candidate = tgt_struct - buffer;
            tp_method    = "swing_high";
        }
        else
        {
            // TP just before the support — slightly above the swing low
            tp_candidate = tgt_struct + buffer;
            tp_method    = "swing_low";
        }

        // Validate: TP must be meaningfully beyond entry (at least 1 buffer away)
        double tp_dist_from_entry = MathAbs(tp_candidate - entry_price);
        bool   correct_side = is_buy ? (tp_candidate > entry_price)
                                      : (tp_candidate < entry_price);

        if(correct_side && tp_dist_from_entry > buffer)
        {
            tp_price = tp_candidate;
            tp_set   = true;
        }
    }

    if(!tp_set)
    {
        // No usable structure found — fallback to a multiple of SL distance
        tp_price  = is_buy
                    ? entry_price + sl_dist * fallback_rr
                    : entry_price - sl_dist * fallback_rr;
        tp_method = "fallback_rr";
    }

    tp_price = NormalizeDouble(tp_price, digits);
    double tp_dist = MathAbs(tp_price - entry_price);
    tp_pips = tp_dist / pip_size;

    // R:R is reported as the actual outcome, never used to place TP
    rr_ratio = (sl_dist > 0) ? tp_dist / sl_dist : 0.0;

    DebugPrint(StringFormat(
        "SL/TP — entry=%.5f  sl=%.5f (%s, %.1f pips)  tp=%.5f (%s, %.1f pips)  R:R=%.2f  ATR=%.5f",
        entry_price, sl_price, sl_method, sl_pips,
        tp_price,    tp_method, tp_pips,
        rr_ratio, atr_value
    ));

    return true;
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
