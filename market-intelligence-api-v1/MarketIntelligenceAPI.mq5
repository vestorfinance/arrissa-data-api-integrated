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
    if(tf == PERIOD_M1)  return "M1 bars";
    return "months";
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

    } else if(StringFind(tf_str, ",") >= 0) {
        // Comma-separated subset: e.g. timeframe=H1,M30,M15,M1
        string tfNames[];
        int count = StringSplit(tf_str, StringGetCharacter(",", 0), tfNames);

        string j = "{\n";
        j += "  \"symbol\": \""      + g_symbol + "\",\n";
        j += "  \"server_time\": \"" + timeStr  + "\",\n";
        if(isPretend) j += "  \"pretend_mode\": true,\n";
        j += "  \"timeframes\": {\n";

        for(int t = 0; t < count; t++) {
            string tfName = tfNames[t];
            StringTrimLeft(tfName);
            StringTrimRight(tfName);
            ENUM_TIMEFRAMES tf = TFStringToEnum(tfName);
            int lb = TFLookback(tf);
            int sh = 0;
            if(isPretend) {
                sh = (int)iBarShift(g_symbol, tf, g_pretendDT, true);
                if(sh < 0) sh = 0;
            }
            string inner = AnalyseTF(tf, sh, lb);
            j += "    \"" + TFName(tf) + "\": " + inner;
            if(t < count - 1) j += ",";
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

        // Strip outer {} from inner block and embed into outer wrapper
        string content        = StringSubstr(inner, 1, StringLen(inner) - 2);
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

        if(smCount < 2)
            s_month = StringFormat("**%d** recorded instance(s) of **%s** in this dataset.", smCount, MonthName(currentMonth));
        else
            s_month = StringFormat(
                "**%s** across **%d** recorded instances: **%d** closed above open, **%d** closed below open. Average change: **%.2f%%**. Best: **%+.2f%%** (%d). Worst: **%.2f%%** (%d).",
                MonthName(currentMonth), smCount, smUp, smDn, smAvg, smBest, smBestYear, smWorst, smWorstYear);

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

        if(sqCount < 2)
            s_quarter = StringFormat("**%d** complete instance(s) of **%s** in this dataset.", sqCount, QuarterLabel(currentQuarter));
        else
            s_quarter = StringFormat(
                "**%s** across **%d** recorded instances: **%d** closed positive, **%d** closed negative. Average change: **%.2f%%**. Best: **%+.2f%%** (%d). Worst: **%.2f%%** (%d).",
                QuarterLabel(currentQuarter), sqCount, sqUp, sqDn, sqAvg, sqBest, sqBestYear, sqWorst, sqWorstYear);
    }

    //=======================================================================
    // NARRATIVE ENGINE
    //=======================================================================
    string s_history = StringFormat(
        "**%s** is at **%s**. Dataset high: **%s** (**%.1f%%** from current, **%d %s** ago). Dataset low: **%s** (**%.1f%%** from current, **%d %s** ago). Net change from dataset open (**%s** on %s): **%+.1f%%**.",
        g_symbol, DoubleToString(currentClose, digits),
        DoubleToString(allTimeHigh, digits), pctFromATH, athBar, barLabel,
        DoubleToString(allTimeLow,  digits), pctFromATL, atlBar, barLabel,
        DoubleToString(firstOpen,   digits), TimeToString(firstTime, TIME_DATE), netMovePct);

    string s_structure = StringFormat(
        "Consecutive higher highs: **%d %s**. Consecutive higher lows: **%d %s**. Consecutive lower highs: **%d %s**. Consecutive lower lows: **%d %s**.",
        consHH, barLabel, consHL, barLabel, consLH, barLabel, consLL, barLabel);

    string s_range = StringFormat(
        "Current price is at **%.1f%%** of the %s range (**%s** – **%s**). Distance from range high (**%s**, **%d %s** ago): **%.1f%%**. Distance from range low (**%s**, **%d %s** ago): **%.1f%%**.",
        pctInLBRange, rangeDesc,
        DoubleToString(lowLB,  digits), DoubleToString(highLB, digits),
        DoubleToString(highLB, digits), highLBBar, barLabel, distFromHighLB,
        DoubleToString(lowLB,  digits), lowLBBar,  barLabel, distFromLowLB);

    string s_percentile = StringFormat(
        "Current close ranks at the **%.0fth percentile** of **%d** recorded %s closes.",
        percentile, totalCloses, barLabel);

    string s_dd = StringFormat(
        "Distance from dataset high: **%.1f%%**. Recorded corrections >1%%: **%d**, average depth **%.1f%%**, deepest **%.1f%%**.",
        pctFromATH, ddCount, avgDD, largestDD);
    string s_ddContext = "";
    if(pctFromATH > 0.5)
        s_ddContext = (deepDDCount > 0)
            ? StringFormat("**%d** previous correction(s) in this **%d-bar** dataset reached or exceeded the current depth.", deepDDCount, copied)
            : StringFormat("No previous correction in this **%d-bar** dataset reached the current depth.", copied);

    string s_vol = StringFormat(
        "Current volatility: **%.3f%%**. Historical average: **%.3f%%**. Volatility percentile within rolling distribution: **%.0fth**.",
        currentVol, historicalVol, volPercentile);

    string s_seq = StringFormat(
        "Last **%d** completed %s: **%d** closed above their open, **%d** closed below their open. Sequence: **%s**.",
        recentWindow, barLabel, upCount, downCount, recentBars);

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
