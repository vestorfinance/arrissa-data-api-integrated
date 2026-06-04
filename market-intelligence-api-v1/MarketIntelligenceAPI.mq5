//+------------------------------------------------------------------+
//|                          MarketIntelligenceAPI.mq5               |
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
//  On-demand multi-timeframe market intelligence via HTTP queue API.
//  Polls /market-intelligence-api-v1/market-intelligence-api.php every
//  InpApiPollingSeconds seconds. On a pending request the EA reads the
//  requested symbol and timeframe (MN1/W1/D1/H4/H1/M30/M15/M5/M1 or
//  "all"), runs full analysis and POSTs the structured report as JSON.
//
//+------------------------------------------------------------------+
#property copyright "Copyright 2026, Arrissa Technologies."
#property link      "https://arrissa.trade"
#property version   "2.00"

//=======================================================================
// INPUTS
//=======================================================================
input string AppBaseURL           = "http://127.0.0.1"; // Base URL
input bool   InpEnableApi         = true;               // Enable API
input int    InpApiPollingSeconds = 1;                  // Poll interval (s)
input bool   InpDebugMode         = false;              // Debug output
input int    InpShortMAPeriod     = 10;                 // Short MA period (bars)
input int    InpLongMAPeriod      = 20;                 // Long MA period (bars)
input int    InpLookbackMN1       = 24;                 // MN1 lookback (bars)
input int    InpLookbackW1        = 52;                 // W1  lookback (bars)
input int    InpLookbackD1        = 252;                // D1  lookback (bars)
input int    InpLookbackH4        = 180;                // H4  lookback (bars)
input int    InpLookbackH1        = 168;                // H1  lookback (bars)
input int    InpLookbackM30       = 200;                // M30 lookback (bars)
input int    InpLookbackM15       = 200;                // M15 lookback (bars)
input int    InpLookbackM5        = 200;                // M5  lookback (bars)
input int    InpLookbackM1        = 200;                // M1  lookback (bars)

//=======================================================================
// GLOBALS
//=======================================================================
string   g_symbol        = "";
datetime g_pretendDT     = 0;
string   InpApiUrl       = "";
datetime last_api_poll_time  = 0;
bool     api_processing_lock = false;

//=======================================================================
// UTILITY HELPERS
//=======================================================================
void DebugPrint(string msg)
{
    if(InpDebugMode) Print("DEBUG: ", msg);
}

ENUM_TIMEFRAMES TFStringToEnum(string tf)
{
    if(tf == "W1")  return PERIOD_W1;
    if(tf == "D1")  return PERIOD_D1;
    if(tf == "H4")  return PERIOD_H4;
    if(tf == "H1")  return PERIOD_H1;
    if(tf == "M30") return PERIOD_M30;
    if(tf == "M15") return PERIOD_M15;
    if(tf == "M5")  return PERIOD_M5;
    if(tf == "M1")  return PERIOD_M1;
    return PERIOD_MN1;
}

int TFLookback(ENUM_TIMEFRAMES tf)
{
    if(tf == PERIOD_W1)  return InpLookbackW1;
    if(tf == PERIOD_D1)  return InpLookbackD1;
    if(tf == PERIOD_H4)  return InpLookbackH4;
    if(tf == PERIOD_H1)  return InpLookbackH1;
    if(tf == PERIOD_M30) return InpLookbackM30;
    if(tf == PERIOD_M15) return InpLookbackM15;
    if(tf == PERIOD_M5)  return InpLookbackM5;
    if(tf == PERIOD_M1)  return InpLookbackM1;
    return InpLookbackMN1;
}

string TFName(ENUM_TIMEFRAMES tf)
{
    if(tf == PERIOD_W1)  return "W1";
    if(tf == PERIOD_D1)  return "D1";
    if(tf == PERIOD_H4)  return "H4";
    if(tf == PERIOD_H1)  return "H1";
    if(tf == PERIOD_M30) return "M30";
    if(tf == PERIOD_M15) return "M15";
    if(tf == PERIOD_M5)  return "M5";
    if(tf == PERIOD_M1)  return "M1";
    return "MN1";
}

string TFBarLabel(ENUM_TIMEFRAMES tf)
{
    if(tf == PERIOD_W1)  return "weeks";
    if(tf == PERIOD_D1)  return "days";
    if(tf == PERIOD_H4)  return "H4 bars";
    if(tf == PERIOD_H1)  return "hours";
    if(tf == PERIOD_M30) return "M30 bars";
    if(tf == PERIOD_M15) return "M15 bars";
    if(tf == PERIOD_M5)  return "M5 bars";
    if(tf == PERIOD_M1)  return "minutes";
    return "months";
}

string TFAdj(ENUM_TIMEFRAMES tf)
{
    if(tf == PERIOD_W1)  return "Weekly";
    if(tf == PERIOD_D1)  return "Daily";
    if(tf == PERIOD_H4)  return "4-hour";
    if(tf == PERIOD_H1)  return "Hourly";
    if(tf == PERIOD_M30) return "30-minute";
    if(tf == PERIOD_M15) return "15-minute";
    if(tf == PERIOD_M5)  return "5-minute";
    if(tf == PERIOD_M1)  return "1-minute";
    return "Monthly";
}

string TFBarSingular(ENUM_TIMEFRAMES tf)
{
    if(tf == PERIOD_W1)  return "week";
    if(tf == PERIOD_D1)  return "day";
    if(tf == PERIOD_H4)  return "H4";
    if(tf == PERIOD_H1)  return "hour";
    if(tf == PERIOD_M30) return "M30";
    if(tf == PERIOD_M15) return "M15";
    if(tf == PERIOD_M5)  return "M5";
    if(tf == PERIOD_M1)  return "minute";
    return "month";
}

double CalcSMA(const MqlRates &rates[], int start, int period)
{
    if(period <= 0) return 0;
    double s = 0;
    for(int i = start; i < start + period; i++) s += rates[i].close;
    return s / period;
}

double CalcStdDev(const double &arr[], int start, int count)
{
    if(count < 2) return 0;
    double mean = 0;
    for(int i = start; i < start + count; i++) mean += arr[i];
    mean /= count;
    double v = 0;
    for(int i = start; i < start + count; i++) v += (arr[i] - mean) * (arr[i] - mean);
    return MathSqrt(v / count);
}

string MonthName(int m)
{
    string n[] = {"","January","February","March","April","May","June",
                  "July","August","September","October","November","December"};
    return (m >= 1 && m <= 12) ? n[m] : "?";
}

string QuarterLabel(int q)
{
    string n[] = {"","Q1 (Jan-Mar)","Q2 (Apr-Jun)","Q3 (Jul-Sep)","Q4 (Oct-Dec)"};
    return (q >= 1 && q <= 4) ? n[q] : "?";
}

string EscapeJson(string s)
{
    StringReplace(s, "\\", "\\\\");
    StringReplace(s, "\"", "\\\"");
    StringReplace(s, "\n", "\\n");
    StringReplace(s, "\r", "\\r");
    return s;
}

string UrlEncode(string str)
{
    string result = "";
    int len = StringLen(str);
    for(int i = 0; i < len; i++) {
        ushort ch = StringGetCharacter(str, i);
        if((ch>='A'&&ch<='Z')||(ch>='a'&&ch<='z')||(ch>='0'&&ch<='9')||
            ch=='-'||ch=='_'||ch=='.'||ch=='~')
            result += ShortToString(ch);
        else if(ch == ' ')
            result += "+";
        else
            result += StringFormat("%%%02X", ch);
    }
    return result;
}

string ExtractJsonValue(string json, string key)
{
    string search_key = "\"" + key + "\"";
    int pos = StringFind(json, search_key);
    if(pos < 0) return "";
    int colon_pos = StringFind(json, ":", pos);
    if(colon_pos < 0) return "";
    int value_start = colon_pos + 1;
    while(value_start < StringLen(json) &&
          (StringGetCharacter(json,value_start)==' '  ||
           StringGetCharacter(json,value_start)=='\t' ||
           StringGetCharacter(json,value_start)=='\n'))
        value_start++;
    bool is_string = (StringGetCharacter(json, value_start) == '"');
    if(is_string) value_start++;
    int value_end = value_start;
    if(is_string) {
        while(value_end < StringLen(json) && StringGetCharacter(json,value_end) != '"') {
            if(StringGetCharacter(json,value_end) == '\\') value_end++;
            value_end++;
        }
    } else {
        while(value_end < StringLen(json) &&
              StringGetCharacter(json,value_end) != ',' &&
              StringGetCharacter(json,value_end) != '}' &&
              StringGetCharacter(json,value_end) != '\n')
            value_end++;
    }
    return StringSubstr(json, value_start, value_end - value_start);
}

//=======================================================================
// LIFECYCLE
//=======================================================================
int OnInit()
{
    InpApiUrl = AppBaseURL + "/market-intelligence-api-v1/market-intelligence-api.php";
    Print("Market Intelligence API EA v2 initialized. Endpoint: ", InpApiUrl);
    EventSetTimer(1);
    last_api_poll_time = TimeCurrent();
    return INIT_SUCCEEDED;
}

void OnDeinit(const int reason) { EventKillTimer(); }
void OnTick() {}

void OnTimer()
{
    datetime now = TimeCurrent();
    if(InpEnableApi && (now - last_api_poll_time) >= InpApiPollingSeconds) {
        last_api_poll_time = now;
        PollApiForRequests();
    }
}

//=======================================================================
// POLLING
//=======================================================================
void PollApiForRequests()
{
    if(!InpEnableApi || api_processing_lock) return;
    char result_data[], post_data[];
    string result_headers;
    int res = WebRequest("GET", InpApiUrl, "", NULL, 15000, post_data, 0, result_data, result_headers);
    if(res == -1) {
        int err = GetLastError();
        if(err == 4060)
            DebugPrint("WebRequest not allowed — add " + InpApiUrl + " in Tools > Options > Expert Advisors");
        return;
    }
    if(res == 200) {
        string response = CharArrayToString(result_data, 0, WHOLE_ARRAY, CP_UTF8);
        if(StringFind(response, "request_id") >= 0 && StringFind(response, "symbol") >= 0
           && StringFind(response, "polling") < 0) {
            api_processing_lock = true;
            ProcessApiRequest(response);
            api_processing_lock = false;
        }
    }
}

//=======================================================================
// REQUEST DISPATCHER
//=======================================================================
void ProcessApiRequest(string request_json)
{
    string request_id = ExtractJsonValue(request_json, "request_id");
    string symbol     = ExtractJsonValue(request_json, "symbol");
    string tf_str     = ExtractJsonValue(request_json, "timeframe");
    if(tf_str == "") tf_str = "MN1";
    StringToUpper(tf_str);

    if(request_id == "" || symbol == "") {
        DebugPrint("ProcessApiRequest: missing required fields");
        return;
    }

    g_symbol    = symbol;
    g_pretendDT = 0;

    string pretend_date = ExtractJsonValue(request_json, "pretend_date");
    string pretend_time_str = ExtractJsonValue(request_json, "pretend_time");
    if(StringLen(pretend_date) >= 8 && StringLen(pretend_time_str) >= 3) {
        string y   = StringSubstr(pretend_date, 0, 4);
        string mon = StringSubstr(pretend_date, 5, 2);
        string d   = StringSubstr(pretend_date, 8, 2);
        string isoDT = y + "." + mon + "." + d + " " + pretend_time_str;
        g_pretendDT = StringToTime(isoDT);
        if(g_pretendDT <= 0) g_pretendDT = 0;
    }

    bool   isPretend = (g_pretendDT > 0);
    string timeStr   = isPretend
        ? TimeToString(g_pretendDT, TIME_DATE|TIME_SECONDS)
        : TimeToString(TimeCurrent(), TIME_DATE|TIME_SECONDS);

    string payload;

    if(tf_str == "ALL") {
        ENUM_TIMEFRAMES tfs[9];
        tfs[0]=PERIOD_MN1; tfs[1]=PERIOD_W1;  tfs[2]=PERIOD_D1;
        tfs[3]=PERIOD_H4;  tfs[4]=PERIOD_H1;  tfs[5]=PERIOD_M30;
        tfs[6]=PERIOD_M15; tfs[7]=PERIOD_M5;  tfs[8]=PERIOD_M1;

        string j = "{\n";
        j += "  \"symbol\": \""      + g_symbol + "\",\n";
        j += "  \"server_time\": \"" + timeStr  + "\",\n";
        if(isPretend) j += "  \"pretend_mode\": true,\n";
        j += "  \"timeframes\": {\n";

        for(int t = 0; t < 9; t++) {
            int lb = TFLookback(tfs[t]);
            int sh = 0;
            if(isPretend) {
                sh = (int)iBarShift(g_symbol, tfs[t], g_pretendDT, true);
                if(sh < 0) sh = 0;
            }
            string inner = AnalyseTF(tfs[t], sh, lb);
            j += "    \"" + TFName(tfs[t]) + "\": " + inner;
            if(t < 8) j += ",";
            j += "\n";
        }
        j += "  }\n}";
        payload = j;

    } else {
        ENUM_TIMEFRAMES tf = TFStringToEnum(tf_str);
        int lb = TFLookback(tf);
        int sh = 0;
        if(isPretend) {
            sh = (int)iBarShift(g_symbol, tf, g_pretendDT, true);
            if(sh < 0) sh = 0;
        }
        string inner = AnalyseTF(tf, sh, lb);

        // Embed inner {"report":{...},"data":{...}} into outer wrapper
        // inner format: {\n  "report":...\n  "data":...\n}
        // Strip outer braces and first/last newline
        string content = StringSubstr(inner, 1, StringLen(inner) - 2);
        string contentTrimmed = StringSubstr(content, 1, StringLen(content) - 2);

        string j = "{\n";
        j += "  \"symbol\": \""      + g_symbol + "\",\n";
        j += "  \"server_time\": \"" + timeStr  + "\",\n";
        j += "  \"timeframe\": \""   + tf_str   + "\",\n";
        if(isPretend) j += "  \"pretend_mode\": true,\n";
        j += contentTrimmed + "\n}";
        payload = j;
    }

    DebugPrint("Sending payload for " + symbol + " tf=" + tf_str + " id=" + request_id);
    SendDataToApi(request_id, symbol, payload);
}

//=======================================================================
// CORE ANALYSIS  — returns {"report":{...},"data":{...}}
//=======================================================================
string AnalyseTF(ENUM_TIMEFRAMES tf, int shift, int lookback)
{
    string barLabel   = TFBarLabel(tf);
    string barSing    = TFBarSingular(tf);
    string tfAdj      = TFAdj(tf);
    bool   isHighTF   = (tf == PERIOD_MN1 || tf == PERIOD_W1 || tf == PERIOD_D1);
    string highLabel  = isHighTF ? "all-time high" : "dataset high";
    string lowLabel   = isHighTF ? "all-time low"  : "dataset low";

    int totalBars = iBars(g_symbol, tf);
    if(totalBars < 6)
        return "{\"error\": \"Not enough " + TFName(tf) + " bars for " + g_symbol + "\"}";

    int copyCount = MathMin(totalBars - shift, 2000);
    if(copyCount < 6)
        return "{\"error\": \"Not enough data at requested date for " + g_symbol + " " + TFName(tf) + "\"}";
    copyCount = MathMin(copyCount, lookback + 50);

    MqlRates rates[];
    ArraySetAsSeries(rates, true);
    int copied = CopyRates(g_symbol, tf, shift, copyCount, rates);
    if(copied <= 0)
        return "{\"error\": \"CopyRates failed for " + TFName(tf) + ". Error: " + IntegerToString(GetLastError()) + "\"}";

    int digits = (int)SymbolInfoInteger(g_symbol, SYMBOL_DIGITS);

    double currentClose = rates[0].close;
    double currentOpen  = rates[0].open;
    double currentHigh  = rates[0].high;
    double currentLow   = rates[0].low;
    double prevClose    = rates[1].close;
    double prevOpen     = rates[1].open;
    datetime firstTime  = rates[copied - 1].time;
    double   firstOpen  = rates[copied - 1].open;

    // --- ATH / ATL across full dataset ---
    double allTimeHigh = 0, allTimeLow = DBL_MAX;
    int athBar = 0, atlBar = 0;
    for(int i = 0; i < copied; i++) {
        if(rates[i].high > allTimeHigh) { allTimeHigh = rates[i].high; athBar = i; }
        if(rates[i].low  < allTimeLow)  { allTimeLow  = rates[i].low;  atlBar = i; }
    }

    double netMove    = currentClose - firstOpen;
    double netMovePct = (firstOpen > 0) ? (netMove / firstOpen) * 100.0 : 0;

    // --- Range over lookback window ---
    int    rangeLBBars = MathMin(lookback, copied - 1);
    double highLB = 0, lowLB = DBL_MAX;
    int    highLBBar = 0, lowLBBar = 0;
    for(int i = 1; i <= rangeLBBars; i++) {
        if(rates[i].high > highLB) { highLB = rates[i].high; highLBBar = i; }
        if(rates[i].low  < lowLB)  { lowLB  = rates[i].low;  lowLBBar  = i; }
    }
    double rangeLB        = highLB - lowLB;
    double pctInLBRange   = (rangeLB > 0) ? ((currentClose - lowLB)  / rangeLB) * 100.0 : 50.0;
    double distFromHighLB = (highLB > 0)  ? ((highLB - currentClose) / highLB)  * 100.0 : 0;
    double distFromLowLB  = (lowLB  > 0)  ? ((currentClose - lowLB)  / lowLB)   * 100.0 : 0;

    // Range description for narratives
    string rangeDesc;
    if(tf == PERIOD_MN1)      rangeDesc = IntegerToString(lookback) + "-month";
    else if(tf == PERIOD_W1)  rangeDesc = IntegerToString(lookback) + "-week";
    else if(tf == PERIOD_D1)  rangeDesc = IntegerToString(lookback) + "-day";
    else                      rangeDesc = IntegerToString(lookback) + "-bar";

    // --- ATH/ATL distance ---
    double pctFromATH = (allTimeHigh > 0) ? ((allTimeHigh - currentClose) / allTimeHigh) * 100.0 : 0.0;
    double pctFromATL = (allTimeLow  > 0) ? ((currentClose - allTimeLow)  / allTimeLow)  * 100.0 : 0.0;

    // --- MA ---
    int shortP = MathMin(InpShortMAPeriod, copied - 1);
    int longP  = MathMin(InpLongMAPeriod,  copied - 1);
    double shortMA  = CalcSMA(rates, 1, shortP);
    double longMA   = CalcSMA(rates, 1, longP);
    double vsShortMA = currentClose - shortMA;
    double vsLongMA  = currentClose - longMA;

    // --- Recent candle sequence ---
    int recentWindow = MathMax(5, MathMin(lookback / 4, 20));
    recentWindow = MathMin(recentWindow, copied - 1);
    int upCount = 0, downCount = 0;
    string recentBars = "";
    for(int i = recentWindow; i >= 1; i--) {
        bool up = (rates[i].close > rates[i].open);
        if(up) upCount++; else downCount++;
        recentBars += (up ? "UP" : "DN");
        if(i > 1) recentBars += " -> ";
    }

    // --- Percentile ranking ---
    int totalCloses = copied - 1;
    double closes[];
    ArrayResize(closes, totalCloses);
    for(int i = 0; i < totalCloses; i++) closes[i] = rates[i + 1].close;
    ArraySort(closes);
    int closeRank = 0;
    for(int i = 0; i < totalCloses; i++) if(closes[i] < currentClose) closeRank++;
    double percentile = (totalCloses > 0) ? ((double)closeRank / totalCloses) * 100.0 : 50.0;

    // --- Drawdown ---
    double runHigh = rates[copied - 1].high, peakForDD = runHigh, ddLow = rates[copied - 1].low;
    bool   inDD = false;
    double largestDD = 0, sumDDs = 0;
    int    ddCount = 0, deepDDCount = 0;
    for(int i = copied - 2; i >= 1; i--) {
        if(rates[i].high > runHigh) {
            if(inDD && peakForDD > 0) {
                double ddPct = (peakForDD - ddLow) / peakForDD * 100.0;
                if(ddPct > 1.0) {
                    sumDDs += ddPct; ddCount++;
                    if(ddPct > largestDD) largestDD = ddPct;
                    if(pctFromATH > 0.5 && ddPct >= pctFromATH) deepDDCount++;
                }
            }
            runHigh = rates[i].high; peakForDD = runHigh; ddLow = rates[i].low; inDD = false;
        } else { inDD = true; if(rates[i].low < ddLow) ddLow = rates[i].low; }
    }
    double avgDD = (ddCount > 0) ? sumDDs / ddCount : 0;

    // --- Consecutive HH/HL/LH/LL ---
    int consHH = 0, consHL = 0, consLH = 0, consLL = 0;
    for(int i = 1; i < copied - 1; i++) { if(rates[i].high > rates[i+1].high) consHH++; else break; }
    for(int i = 1; i < copied - 1; i++) { if(rates[i].low  > rates[i+1].low)  consHL++; else break; }
    for(int i = 1; i < copied - 1; i++) { if(rates[i].high < rates[i+1].high) consLH++; else break; }
    for(int i = 1; i < copied - 1; i++) { if(rates[i].low  < rates[i+1].low)  consLL++; else break; }
    int half = MathMax(lookback / 2, 2);

    // --- Volatility (return StdDev) ---
    int retCount = MathMin(copied - 2, lookback);
    double returns[];
    ArrayResize(returns, retCount);
    for(int i = 0; i < retCount; i++)
        returns[i] = (rates[i+1].close > 0) ? ((rates[i].close - rates[i+1].close) / rates[i+1].close) * 100.0 : 0;
    int    volWindow     = (tf == PERIOD_MN1) ? 12 : MathMax(5, MathMin(lookback / 8, 20));
    int    recentVolWin  = MathMin(volWindow, retCount);
    double currentVol    = CalcStdDev(returns, 0, recentVolWin);
    double historicalVol = CalcStdDev(returns, 0, retCount);
    double maxVol = 0, minVol = DBL_MAX;
    if(retCount >= volWindow) {
        for(int i = 0; i <= retCount - volWindow; i++) {
            double v = CalcStdDev(returns, i, volWindow);
            if(v > maxVol) maxVol = v;
            if(v < minVol && v > 0) minVol = v;
        }
    } else { maxVol = currentVol; minVol = currentVol; }
    if(minVol == DBL_MAX) minVol = 0;
    double volPercentile = (maxVol - minVol > 0) ? ((currentVol - minVol) / (maxVol - minVol)) * 100.0 : 50.0;

    // --- Current bar details ---
    double barRange        = currentHigh - currentLow;
    double bodySize        = MathAbs(currentClose - currentOpen);
    double bodyPct         = (barRange > 0) ? (bodySize / barRange) * 100.0 : 0.0;
    double upperWick       = currentHigh - MathMax(currentClose, currentOpen);
    double lowerWick       = MathMin(currentClose, currentOpen) - currentLow;
    double moveFromPrev    = currentClose - prevClose;
    double moveFromPrevPct = (prevClose > 0) ? (moveFromPrev / prevClose) * 100.0 : 0.0;
    double prevPct         = (prevOpen  > 0) ? ((prevClose - prevOpen) / prevOpen) * 100.0 : 0.0;

    // --- Seasonal stats — MN1 only ---
    string s_month = "", s_quarter = "";
    if(tf == PERIOD_MN1) {
        MqlDateTime refDt;
        datetime refTime = (g_pretendDT > 0) ? g_pretendDT : TimeCurrent();
        TimeToStruct(refTime, refDt);
        int currentMonth   = refDt.mon;
        int currentQuarter = (currentMonth - 1) / 3 + 1;

        int    smCount = 0, smUp = 0, smDn = 0;
        double smSum = 0, smBest = -DBL_MAX, smWorst = DBL_MAX;
        int    smBestYear = 0, smWorstYear = 0;
        for(int i = 1; i < copied; i++) {
            MqlDateTime dt; TimeToStruct(rates[i].time, dt);
            if(dt.mon != currentMonth) continue;
            double pct = (rates[i].open > 0) ? ((rates[i].close - rates[i].open) / rates[i].open) * 100.0 : 0;
            smCount++;
            smSum += pct;
            if(pct >= 0) smUp++; else smDn++;
            if(pct > smBest)  { smBest  = pct; smBestYear  = dt.year; }
            if(pct < smWorst) { smWorst = pct; smWorstYear = dt.year; }
        }
        double smAvg = (smCount > 0) ? smSum / smCount : 0;

        if(smCount < 5)
            s_month = StringFormat(
                "Only **%d** recorded instances of **%s** — not enough to identify seasonal patterns.",
                smCount, MonthName(currentMonth));
        else {
            double smUpRate = (double)smUp / smCount * 100.0;
            if(smUpRate > 65)
                s_month = StringFormat(
                    "**%s** has historically leaned toward up-closes (**%d** of **%d** instances, **%.0f%%**), averaging **%.2f%%**, from **+%.2f%%** (%d) to **%.2f%%** (%d).",
                    MonthName(currentMonth), smUp, smCount, smUpRate, smAvg, smBest, smBestYear, smWorst, smWorstYear);
            else if(smUpRate < 35)
                s_month = StringFormat(
                    "**%s** has historically leaned toward down-closes (**%d** of **%d** instances, **%.0f%%**), averaging **%.2f%%**, from **+%.2f%%** (%d) to **%.2f%%** (%d).",
                    MonthName(currentMonth), smDn, smCount, 100.0-smUpRate, smAvg, smBest, smBestYear, smWorst, smWorstYear);
            else
                s_month = StringFormat(
                    "**%s** shows no consistent directional tendency across **%d** instances — balanced, averaging **%.2f%%**, from **+%.2f%%** (%d) to **%.2f%%** (%d).",
                    MonthName(currentMonth), smCount, smAvg, smBest, smBestYear, smWorst, smWorstYear);
        }

        int    sqCount = 0, sqUp = 0, sqDn = 0;
        double sqSum = 0, sqBest = -DBL_MAX, sqWorst = DBL_MAX;
        int    sqBestYear = 0, sqWorstYear = 0;
        int    fmQ = (currentQuarter - 1) * 3 + 1;
        int    lmQ = fmQ + 2;
        for(int i = copied - 1; i >= 3; i--) {
            MqlDateTime dt; TimeToStruct(rates[i].time, dt);
            if(dt.mon != fmQ) continue;
            MqlDateTime dt1, dt2;
            TimeToStruct(rates[i-1].time, dt1);
            TimeToStruct(rates[i-2].time, dt2);
            if(dt1.year != dt.year || dt1.mon != fmQ + 1) continue;
            if(dt2.year != dt.year || dt2.mon != lmQ)      continue;
            double qOpen = rates[i].open, qClose = rates[i-2].close;
            double pct   = (qOpen > 0) ? ((qClose - qOpen) / qOpen) * 100.0 : 0;
            sqCount++;
            sqSum += pct;
            if(pct >= 0) sqUp++; else sqDn++;
            if(pct > sqBest)  { sqBest  = pct; sqBestYear  = dt.year; }
            if(pct < sqWorst) { sqWorst = pct; sqWorstYear = dt.year; }
        }
        double sqAvg = (sqCount > 0) ? sqSum / sqCount : 0;

        if(sqCount < 4)
            s_quarter = StringFormat(
                "Only **%d** complete instances of **%s** — not enough to identify seasonal patterns.",
                sqCount, QuarterLabel(currentQuarter));
        else {
            double sqUpRate = (double)sqUp / sqCount * 100.0;
            if(sqUpRate > 65)
                s_quarter = StringFormat(
                    "**%s** has historically leaned toward positive closes (**%d** of **%d**, **%.0f%%**), averaging **%.2f%%**, from **+%.2f%%** (%d) to **%.2f%%** (%d).",
                    QuarterLabel(currentQuarter), sqUp, sqCount, sqUpRate, sqAvg, sqBest, sqBestYear, sqWorst, sqWorstYear);
            else if(sqUpRate < 35)
                s_quarter = StringFormat(
                    "**%s** has historically leaned toward negative closes (**%d** of **%d**, **%.0f%%**), averaging **%.2f%%**, from **+%.2f%%** (%d) to **%.2f%%** (%d).",
                    QuarterLabel(currentQuarter), sqDn, sqCount, 100.0-sqUpRate, sqAvg, sqBest, sqBestYear, sqWorst, sqWorstYear);
            else
                s_quarter = StringFormat(
                    "**%s** shows no consistent directional tendency across **%d** instances — balanced, averaging **%.2f%%**, from **+%.2f%%** (%d) to **%.2f%%** (%d).",
                    QuarterLabel(currentQuarter), sqCount, sqAvg, sqBest, sqBestYear, sqWorst, sqWorstYear);
        }
    }

    //=======================================================================
    // NARRATIVE ENGINE
    //=======================================================================
    string s_history;
    if(athBar == 0)
        s_history = StringFormat(
            "**%s** is at the %s of this dataset (**%s**), up **%.1f%%** from the start of the dataset at **%s** on %s.",
            g_symbol, highLabel, DoubleToString(currentClose, digits), netMovePct,
            DoubleToString(firstOpen, digits), TimeToString(firstTime, TIME_DATE));
    else if(pctFromATH < 5.0)
        s_history = StringFormat(
            "**%s** is near the %s of **%s**, having pulled back just **%.1f%%** from that level reached **%d %s ago**.",
            g_symbol, highLabel, DoubleToString(allTimeHigh, digits), pctFromATH, athBar, barLabel);
    else if(pctFromATH < 30.0)
        s_history = StringFormat(
            "**%s** is in a corrective phase from its %s of **%s** — currently **%.1f%%** below that level, reached **%d %s ago**.",
            g_symbol, highLabel, DoubleToString(allTimeHigh, digits), pctFromATH, athBar, barLabel);
    else
        s_history = StringFormat(
            "**%s** is well below its %s — **%.1f%%** from the %s of **%s** (%d %s ago), and **%.1f%%** above the %s of **%s**.",
            g_symbol, highLabel, pctFromATH, highLabel, DoubleToString(allTimeHigh, digits), athBar, barLabel,
            pctFromATL, lowLabel, DoubleToString(allTimeLow, digits));

    string s_structure;
    bool hhOn = (consHH > 0), hlOn = (consHL > 0), lhOn = (consLH > 0), llOn = (consLL > 0);
    if(hhOn && hlOn)
        s_structure = StringFormat(
            "Momentum is intact — **%d** consecutive %s of both higher highs and higher lows. The most recent **%d %s** have printed higher peaks and troughs than the **%d %s** before them.",
            MathMin(consHH, consHL), barLabel, half, barLabel, half, barLabel);
    else if(lhOn && llOn)
        s_structure = StringFormat(
            "Momentum is fading — **%d** consecutive %s of both lower highs and lower lows. The most recent **%d %s** have printed lower peaks and troughs than the **%d %s** before them.",
            MathMin(consLH, consLL), barLabel, half, barLabel, half, barLabel);
    else if(hhOn && llOn)
        s_structure = StringFormat(
            "The range is expanding — **%d** consecutive %s of higher highs alongside **%d** %s of lower lows, with no directional commitment.",
            consHH, barLabel, consLL, barLabel);
    else if(lhOn && hlOn)
        s_structure = StringFormat(
            "The range is contracting — falling highs for **%d %s** while lows have been rising for **%d %s**, compressing into a narrowing corridor.",
            consLH, barLabel, consHL, barLabel);
    else if(hhOn)
        s_structure = StringFormat("Peaks have been climbing for **%d** consecutive %s, but lows have not confirmed a consistent direction.", consHH, barLabel);
    else if(lhOn)
        s_structure = StringFormat("Peaks have been declining for **%d** consecutive %s, but lows have not confirmed a consistent direction.", consLH, barLabel);
    else if(hlOn)
        s_structure = StringFormat("The floor has been rising for **%d** consecutive %s, but highs have not established a consistent pattern.", consHL, barLabel);
    else if(llOn)
        s_structure = StringFormat("The floor has been declining for **%d** consecutive %s, but highs have not established a consistent pattern.", consLL, barLabel);
    else
        s_structure = "No consecutive sequence in either highs or lows — structure is currently mixed with no clear directional pattern.";

    string s_range;
    if(pctInLBRange >= 80)
        s_range = StringFormat(
            "Price is pressing against the upper end of its %s range, just **%.1f%%** from the range high of **%s** (%d %s ago).",
            rangeDesc, distFromHighLB, DoubleToString(highLB, digits), highLBBar, barLabel);
    else if(pctInLBRange >= 60)
        s_range = StringFormat(
            "Price is in the upper portion of its %s range (**%s** to **%s**), **%.1f%%** from the range high.",
            rangeDesc, DoubleToString(lowLB, digits), DoubleToString(highLB, digits), distFromHighLB);
    else if(pctInLBRange >= 40)
        s_range = StringFormat(
            "Price is near the midpoint of its %s range (**%s** to **%s**), **%.1f%%** from each extreme.",
            rangeDesc, DoubleToString(lowLB, digits), DoubleToString(highLB, digits),
            MathMin(distFromHighLB, distFromLowLB));
    else if(pctInLBRange >= 20)
        s_range = StringFormat(
            "Price is in the lower portion of its %s range (**%s** to **%s**), **%.1f%%** from the range low.",
            rangeDesc, DoubleToString(lowLB, digits), DoubleToString(highLB, digits), distFromLowLB);
    else
        s_range = StringFormat(
            "Price is pressing against the lower end of its %s range, just **%.1f%%** from the range low of **%s** (%d %s ago).",
            rangeDesc, distFromLowLB, DoubleToString(lowLB, digits), lowLBBar, barLabel);

    string s_percentile;
    if(percentile >= 97)
        s_percentile = StringFormat("This price is at the extreme upper end of the dataset — above **%.0f%%** of all %s closes recorded.", percentile, barLabel);
    else if(percentile >= 85)
        s_percentile = StringFormat("Price is historically elevated — higher than **%.0f%%** of all %s closes across **%d bars** of data.", percentile, barLabel, totalCloses);
    else if(percentile >= 60)
        s_percentile = StringFormat("Price sits in the upper portion of its historical distribution (**%.0fth percentile** of **%d** %s closes).", percentile, totalCloses, barLabel);
    else if(percentile >= 40)
        s_percentile = StringFormat("Price is near the historical median (**%.0fth percentile** of **%d** %s closes).", percentile, totalCloses, barLabel);
    else if(percentile >= 15)
        s_percentile = StringFormat("Price sits in the lower portion of its historical distribution (**%.0fth percentile** of **%d** %s closes).", percentile, totalCloses, barLabel);
    else
        s_percentile = StringFormat("Price is historically depressed — below **%.0f%%** of all %s closes across **%d bars** of data.", 100.0 - percentile, barLabel, totalCloses);

    string s_dd, s_ddContext = "";
    if(pctFromATH < 0.5)
        s_dd = "There is no current drawdown — price is at the dataset high.";
    else if(ddCount == 0 || pctFromATH < avgDD * 0.6)
        s_dd = StringFormat("The current pullback of **%.1f%%** is shallow relative to this market's history. *(Average correction: **%.1f%%**, deepest: **%.1f%%.)*", pctFromATH, avgDD, largestDD);
    else if(pctFromATH < avgDD)
        s_dd = StringFormat("The current pullback of **%.1f%%** is approaching but has not yet reached the typical correction depth of **%.1f%%** in this dataset.", pctFromATH, avgDD);
    else if(pctFromATH < largestDD)
        s_dd = StringFormat("The current pullback of **%.1f%%** has exceeded the average correction depth (**%.1f%%**) but remains below the deepest on record (**%.1f%%**).", pctFromATH, avgDD, largestDD);
    else
        s_dd = StringFormat("The current pullback of **%.1f%%** matches or exceeds the deepest correction recorded in this dataset (**%.1f%%**).", pctFromATH, largestDD);
    if(pctFromATH > 0.5)
        s_ddContext = (deepDDCount > 0)
            ? StringFormat("A pullback of this depth has occurred **%d** time(s) in this dataset of **%d bars**.", deepDDCount, copied)
            : StringFormat("No previous correction in this dataset of **%d bars** has reached this depth.", copied);

    string s_vol;
    if(volPercentile >= 90)
        s_vol = StringFormat("The market is in a high-activity regime — %s swings are larger than at nearly any point in this dataset. *(Volatility: **%.3f%%** vs avg **%.3f%%.)*", tfAdj, currentVol, historicalVol);
    else if(volPercentile >= 65)
        s_vol = StringFormat("Activity is running above the historical norm — %s swings have been wider than usual. *(Volatility: **%.3f%%** vs avg **%.3f%%.)*", tfAdj, currentVol, historicalVol);
    else if(volPercentile <= 10)
        s_vol = StringFormat("The market is in an unusually quiet regime — %s swings are narrower than at nearly any point in this dataset. *(Volatility: **%.3f%%** vs avg **%.3f%%.)*", tfAdj, currentVol, historicalVol);
    else if(volPercentile <= 35)
        s_vol = StringFormat("Activity is running below the historical norm — %s swings have been calmer than usual. *(Volatility: **%.3f%%** vs avg **%.3f%%.)*", tfAdj, currentVol, historicalVol);
    else
        s_vol = StringFormat("Activity is running close to the historical norm — %s swings are typical for this market. *(Volatility: **%.3f%%** vs avg **%.3f%%.)*", tfAdj, currentVol, historicalVol);

    string s_ma;
    if(vsShortMA > 0 && vsLongMA > 0)
        s_ma = StringFormat("Price is above both the **%d-%s** SMA (**%s**) and the **%d-%s** SMA (**%s**).",
            InpShortMAPeriod, barSing, DoubleToString(shortMA, digits), InpLongMAPeriod, barSing, DoubleToString(longMA, digits));
    else if(vsShortMA < 0 && vsLongMA < 0)
        s_ma = StringFormat("Price has fallen below both the **%d-%s** SMA (**%s**) and the **%d-%s** SMA (**%s**).",
            InpShortMAPeriod, barSing, DoubleToString(shortMA, digits), InpLongMAPeriod, barSing, DoubleToString(longMA, digits));
    else if(vsShortMA > 0 && vsLongMA < 0)
        s_ma = StringFormat("Price is above the **%d-%s** SMA (**%s**) but below the **%d-%s** SMA (**%s**).",
            InpShortMAPeriod, barSing, DoubleToString(shortMA, digits), InpLongMAPeriod, barSing, DoubleToString(longMA, digits));
    else
        s_ma = StringFormat("Price has slipped below the **%d-%s** SMA (**%s**) while remaining above the **%d-%s** SMA (**%s**).",
            InpShortMAPeriod, barSing, DoubleToString(shortMA, digits), InpLongMAPeriod, barSing, DoubleToString(longMA, digits));

    string s_seq;
    if(upCount > downCount + 1)
        s_seq = StringFormat("Recent %s have leaned toward up-closes — **%d** up and **%d** down across the last **%d** completed bars: **%s**.", barLabel, upCount, downCount, recentWindow, recentBars);
    else if(downCount > upCount + 1)
        s_seq = StringFormat("Recent %s have leaned toward down-closes — **%d** down and **%d** up across the last **%d** completed bars: **%s**.", barLabel, downCount, upCount, recentWindow, recentBars);
    else
        s_seq = StringFormat("Recent %s are evenly split with no directional lean across **%d** completed bars: **%s**.", barLabel, recentWindow, recentBars);

    string s_last_candle = StringFormat(
        "Opened at **%s**, closed at **%s** (**%.2f%%**).",
        DoubleToString(prevOpen, digits), DoubleToString(prevClose, digits), prevPct);

    string s_current_candle = StringFormat(
        "Opened at **%s**. High: **%s**. Low: **%s**. Currently at **%s** (**%.2f%%** from prior bar's close). Body: **%.0f%%** of total range. Upper wick: **%s**. Lower wick: **%s**.",
        DoubleToString(currentOpen, digits), DoubleToString(currentHigh, digits),
        DoubleToString(currentLow, digits),  DoubleToString(currentClose, digits),
        moveFromPrevPct, bodyPct, DoubleToString(upperWick, digits), DoubleToString(lowerWick, digits));

    string s_dataset = StringFormat(
        "*Dataset: **%d %s bars** copied (%s – %s). Lookback: **%d**. Closes: **%d**. Corrections: **%d**.*",
        copied, TFName(tf),
        TimeToString(firstTime, TIME_DATE), TimeToString((g_pretendDT > 0) ? g_pretendDT : TimeCurrent(), TIME_DATE),
        lookback, totalCloses, ddCount);

    //=======================================================================
    // BUILD JSON
    //=======================================================================
    string j = "{\n";
    j += "  \"report\": {\n";
    j += "    \"price_history\":      \"" + EscapeJson(s_history)        + "\",\n";
    j += "    \"market_structure\":   \"" + EscapeJson(s_structure)      + "\",\n";
    j += "    \"range_position\":     \"" + EscapeJson(s_range)          + "\",\n";
    j += "    \"percentile_ranking\": \"" + EscapeJson(s_percentile)     + "\",\n";
    j += "    \"drawdown\":           \"" + EscapeJson(s_dd)             + "\",\n";
    j += "    \"drawdown_context\":   \"" + EscapeJson(s_ddContext)      + "\",\n";
    j += "    \"volatility\":         \"" + EscapeJson(s_vol)            + "\",\n";
    j += "    \"moving_averages\":    \"" + EscapeJson(s_ma)             + "\",\n";
    j += "    \"candle_behaviour\":   \"" + EscapeJson(s_seq)            + "\",\n";
    j += "    \"last_candle\":        \"" + EscapeJson(s_last_candle)    + "\",\n";
    j += "    \"current_candle\":     \"" + EscapeJson(s_current_candle) + "\",\n";
    j += "    \"seasonal_month\":     \"" + EscapeJson(s_month)          + "\",\n";
    j += "    \"seasonal_quarter\":   \"" + EscapeJson(s_quarter)        + "\",\n";
    j += "    \"dataset_note\":       \"" + EscapeJson(s_dataset)        + "\"\n";
    j += "  },\n";
    j += "  \"data\": {\n";
    j += "    \"current_close\":         " + DoubleToString(currentClose,  digits) + ",\n";
    j += "    \"dataset_high\":          " + DoubleToString(allTimeHigh,   digits) + ",\n";
    j += "    \"dataset_low\":           " + DoubleToString(allTimeLow,    digits) + ",\n";
    j += "    \"pct_from_dataset_high\": " + DoubleToString(pctFromATH,    2)      + ",\n";
    j += "    \"pct_from_dataset_low\":  " + DoubleToString(pctFromATL,    2)      + ",\n";
    j += "    \"candles_copied\":        " + IntegerToString(copied)               + ",\n";
    j += "    \"percentile\":            " + DoubleToString(percentile,    2)      + ",\n";
    j += "    \"current_volatility\":    " + DoubleToString(currentVol,    4)      + ",\n";
    j += "    \"historical_volatility\": " + DoubleToString(historicalVol, 4)      + ",\n";
    j += "    \"volatility_percentile\": " + DoubleToString(volPercentile, 2)      + ",\n";
    j += "    \"short_ma\":              " + DoubleToString(shortMA,       digits) + ",\n";
    j += "    \"long_ma\":               " + DoubleToString(longMA,        digits) + ",\n";
    j += "    \"range_high_lb\":         " + DoubleToString(highLB,        digits) + ",\n";
    j += "    \"range_low_lb\":          " + DoubleToString(lowLB,         digits) + ",\n";
    j += "    \"pct_in_lb_range\":       " + DoubleToString(pctInLBRange,  2)      + ",\n";
    j += "    \"cons_hh\":               " + IntegerToString(consHH)               + ",\n";
    j += "    \"cons_hl\":               " + IntegerToString(consHL)               + ",\n";
    j += "    \"cons_lh\":               " + IntegerToString(consLH)               + ",\n";
    j += "    \"cons_ll\":               " + IntegerToString(consLL)               + ",\n";
    j += "    \"dd_count\":              " + IntegerToString(ddCount)              + ",\n";
    j += "    \"largest_dd\":            " + DoubleToString(largestDD,     2)      + ",\n";
    j += "    \"avg_dd\":                " + DoubleToString(avgDD,         2)      + ",\n";
    j += "    \"recent_up\":             " + IntegerToString(upCount)              + ",\n";
    j += "    \"recent_down\":           " + IntegerToString(downCount)            + "\n";
    j += "  }\n";
    j += "}";
    return j;
}

//=======================================================================
// SEND RESPONSE TO PHP BRIDGE
//=======================================================================
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
        NULL, 30000,
        post_array, ArraySize(post_array),
        result_data, result_headers
    );
    if(res == 200)
        DebugPrint("Payload sent — request_id=" + request_id + " symbol=" + symbol);
    else
        DebugPrint("Send failed — HTTP " + IntegerToString(res));
}
//+------------------------------------------------------------------+
