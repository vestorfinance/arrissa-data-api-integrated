//+------------------------------------------------------------------+
//|                              MarketIntelligenceAPI.mq5           |
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
//  On-demand monthly market intelligence readout via HTTP queue API.
//  Polls /market-intelligence-api-v1/market-intelligence-api.php every
//  InpApiPollingSeconds seconds. On a pending request the EA reads
//  the requested symbol, runs full monthly analysis and POSTs the
//  structured report as JSON.
//
//+------------------------------------------------------------------+
#property copyright "Copyright 2026, Arrissa Technologies."
#property link      "https://arrissa.trade"
#property version   "1.00"

input string AppBaseURL           = "http://127.0.0.1"; // Base URL
input bool   InpEnableApi         = true;               // Enable API
input int    InpApiPollingSeconds = 1;                  // Poll interval (s)
input bool   InpDebugMode         = false;              // Debug output
input int    InpLookbackMonths    = 24;                 // Lookback months
input int    InpShortMAPeriod     = 6;                  // Short MA period (months)
input int    InpLongMAPeriod      = 12;                 // Long MA period (months)

//=======================================================================
// GLOBALS
//=======================================================================
string   g_symbol    = "";
int      g_shift_mn  = 0;
datetime g_pretendDT = 0;

string   InpApiUrl           = "";
datetime last_api_poll_time  = 0;
bool     api_processing_lock = false;

//+------------------------------------------------------------------+
void DebugPrint(string msg)
{
    if(InpDebugMode) Print("DEBUG: ", msg);
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

//+------------------------------------------------------------------+
int OnInit()
{
    InpApiUrl = AppBaseURL + "/market-intelligence-api-v1/market-intelligence-api.php";
    Print("Market Intelligence API EA v1 initialized. Endpoint: ", InpApiUrl);
    EventSetTimer(1);
    last_api_poll_time = TimeCurrent();
    return INIT_SUCCEEDED;
}

void OnDeinit(const int reason)
{
    EventKillTimer();
    Print("Market Intelligence API EA deinitialized.");
}

void OnTick() {}

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

    if(res == -1) {
        int err = GetLastError();
        if(err == 4060)
            DebugPrint("WebRequest not allowed — add " + InpApiUrl + " in Tools > Options > Expert Advisors");
        return;
    }

    if(res == 200) {
        string response = CharArrayToString(result_data, 0, WHOLE_ARRAY, CP_UTF8);
        if(StringFind(response, "request_id") >= 0 && StringFind(response, "symbol") >= 0
           && StringFind(response, "polling") < 0)
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
    string request_id = ExtractJsonValue(request_json, "request_id");
    string symbol     = ExtractJsonValue(request_json, "symbol");

    if(request_id == "" || symbol == "") {
        DebugPrint("ProcessApiRequest: missing required fields");
        return;
    }

    DebugPrint("Processing request_id=" + request_id + " symbol=" + symbol);

    g_symbol    = symbol;
    g_shift_mn  = 0;
    g_pretendDT = 0;

    string pretend_date = ExtractJsonValue(request_json, "pretend_date");
    string pretend_time = ExtractJsonValue(request_json, "pretend_time");
    if(StringLen(pretend_date) >= 8 && StringLen(pretend_time) >= 3) {
        string y   = StringSubstr(pretend_date, 0, 4);
        string mon = StringSubstr(pretend_date, 5, 2);
        string d   = StringSubstr(pretend_date, 8, 2);
        string isoDT = y + "." + mon + "." + d + " " + pretend_time;
        g_pretendDT = StringToTime(isoDT);
        if(g_pretendDT > 0) {
            g_shift_mn = (int)iBarShift(symbol, PERIOD_MN1, g_pretendDT, true);
            if(g_shift_mn < 0) g_shift_mn = 0;
            DebugPrint("Pretend mode: " + isoDT + " shift_mn=" + IntegerToString(g_shift_mn));
        }
    }

    string payload = AnalyseMonthly();
    SendDataToApi(request_id, symbol, payload);
}

//+------------------------------------------------------------------+
string AnalyseMonthly()
{
    int totalBars = iBars(g_symbol, PERIOD_MN1);
    if(totalBars < 6)
        return "{\"error\": \"Not enough monthly candles for " + g_symbol + "\"}";

    int copyCount = MathMin(totalBars - g_shift_mn, 600);
    if(copyCount < 6)
        return "{\"error\": \"Not enough data at requested date for " + g_symbol + "\"}";

    MqlRates rates[];
    ArraySetAsSeries(rates, true);
    int copied = CopyRates(g_symbol, PERIOD_MN1, g_shift_mn, copyCount, rates);
    if(copied <= 0)
        return "{\"error\": \"CopyRates failed. Error: " + IntegerToString(GetLastError()) + "\"}";

    int digits = (int)SymbolInfoInteger(g_symbol, SYMBOL_DIGITS);

    double currentClose = rates[0].close;
    double currentOpen  = rates[0].open;
    double currentHigh  = rates[0].high;
    double currentLow   = rates[0].low;
    double prevClose    = rates[1].close;
    double prevOpen     = rates[1].open;
    datetime firstTime  = rates[copied - 1].time;
    double   firstOpen  = rates[copied - 1].open;

    double allTimeHigh = 0, allTimeLow = DBL_MAX;
    int athBar = 0, atlBar = 0;
    for(int i = 0; i < copied; i++) {
        if(rates[i].high > allTimeHigh) { allTimeHigh = rates[i].high; athBar = i; }
        if(rates[i].low  < allTimeLow)  { allTimeLow  = rates[i].low;  atlBar = i; }
    }

    double netMove    = currentClose - firstOpen;
    double netMovePct = (firstOpen > 0) ? (netMove / firstOpen) * 100.0 : 0;

    int lookback = MathMin(InpLookbackMonths, copied - 1);
    int half     = MathMax(lookback / 2, 2);
    double recentHigh = 0, recentLow = DBL_MAX;
    double olderHigh  = 0, olderLow  = DBL_MAX;
    for(int i = 1; i <= half; i++) {
        if(rates[i].high > recentHigh) recentHigh = rates[i].high;
        if(rates[i].low  < recentLow)  recentLow  = rates[i].low;
    }
    for(int i = half + 1; i <= lookback; i++) {
        if(rates[i].high > olderHigh) olderHigh = rates[i].high;
        if(rates[i].low  < olderLow)  olderLow  = rates[i].low;
    }

    double shortMA   = CalcSMA(rates, 1, MathMin(InpShortMAPeriod, copied - 1));
    double longMA    = CalcSMA(rates, 1, MathMin(InpLongMAPeriod,  copied - 1));
    double vsShortMA = currentClose - shortMA;
    double vsLongMA  = currentClose - longMA;

    int recentWindow = MathMin(6, copied - 1);
    int upCount = 0, downCount = 0;
    string recentBars = "";
    for(int i = recentWindow; i >= 1; i--) {
        bool up = (rates[i].close > rates[i].open);
        if(up) upCount++; else downCount++;
        recentBars += (up ? "UP" : "DN");
        if(i > 1) recentBars += " -> ";
    }

    double rangeHigh  = MathMax(recentHigh, olderHigh);
    double rangeLow   = MathMin(recentLow,  olderLow);
    double rangeSize  = rangeHigh - rangeLow;
    double pctInRange = (rangeSize > 0) ? ((currentClose - rangeLow) / rangeSize) * 100.0 : 50.0;

    double pctFromATH = (allTimeHigh > 0) ? ((allTimeHigh - currentClose) / allTimeHigh) * 100.0 : 0.0;
    double pctFromATL = (allTimeLow  > 0) ? ((currentClose - allTimeLow)  / allTimeLow)  * 100.0 : 0.0;

    double barRange        = currentHigh - currentLow;
    double bodySize        = MathAbs(currentClose - currentOpen);
    double bodyPct         = (barRange > 0) ? (bodySize / barRange) * 100.0 : 0.0;
    double upperWick       = currentHigh - MathMax(currentClose, currentOpen);
    double lowerWick       = MathMin(currentClose, currentOpen) - currentLow;
    double moveFromPrev    = currentClose - prevClose;
    double moveFromPrevPct = (prevClose > 0) ? (moveFromPrev / prevClose) * 100.0 : 0.0;

    int totalCloses = copied - 1;
    double closes[];
    ArrayResize(closes, totalCloses);
    for(int i = 0; i < totalCloses; i++) closes[i] = rates[i + 1].close;
    ArraySort(closes);
    int closeRank = 0;
    for(int i = 0; i < totalCloses; i++) if(closes[i] < currentClose) closeRank++;
    double percentile = (totalCloses > 0) ? ((double)closeRank / totalCloses) * 100.0 : 50.0;

    double runHigh     = rates[copied - 1].high;
    double peakForDD   = runHigh;
    double ddLow       = rates[copied - 1].low;
    bool   inDD        = false;
    double largestDD   = 0;
    double sumDDs      = 0;
    int    ddCount     = 0;
    int    deepDDCount = 0;

    for(int i = copied - 2; i >= 1; i--) {
        if(rates[i + 1].close > 0) {
            double mc = ((rates[i].close - rates[i + 1].close) / rates[i + 1].close) * 100.0;
            if(mc < 0 && -mc > 0) {}  // largestMonthDrop tracking (unused in narrative)
        }
        if(rates[i].high > runHigh) {
            if(inDD && peakForDD > 0) {
                double ddPct = (peakForDD - ddLow) / peakForDD * 100.0;
                if(ddPct > 3.0) {
                    sumDDs += ddPct; ddCount++;
                    if(ddPct > largestDD) largestDD = ddPct;
                    if(pctFromATH > 1.0 && ddPct >= pctFromATH) deepDDCount++;
                }
            }
            runHigh = rates[i].high; peakForDD = runHigh; ddLow = rates[i].low; inDD = false;
        } else { inDD = true; if(rates[i].low < ddLow) ddLow = rates[i].low; }
    }
    double avgDD = (ddCount > 0) ? sumDDs / ddCount : 0;

    int consHH = 0, consHL = 0, consLH = 0, consLL = 0;
    for(int i = 1; i < copied - 1; i++) { if(rates[i].high > rates[i+1].high) consHH++; else break; }
    for(int i = 1; i < copied - 1; i++) { if(rates[i].low  > rates[i+1].low)  consHL++; else break; }
    for(int i = 1; i < copied - 1; i++) { if(rates[i].high < rates[i+1].high) consLH++; else break; }
    for(int i = 1; i < copied - 1; i++) { if(rates[i].low  < rates[i+1].low)  consLL++; else break; }

    int retCount = MathMin(copied - 2, 120);
    double returns[];
    ArrayResize(returns, retCount);
    for(int i = 0; i < retCount; i++)
        returns[i] = (rates[i+1].close > 0) ? ((rates[i].close - rates[i+1].close) / rates[i+1].close) * 100.0 : 0;
    int    recentVolWin  = MathMin(12, retCount);
    double currentVol    = CalcStdDev(returns, 0, recentVolWin);
    double historicalVol = CalcStdDev(returns, 0, retCount);
    double maxVol = 0, minVol = DBL_MAX;
    for(int i = 0; i <= retCount - 12; i++) {
        double v = CalcStdDev(returns, i, 12);
        if(v > maxVol) maxVol = v;
        if(v < minVol) minVol = v;
    }
    double volPercentile = (maxVol - minVol > 0) ? ((currentVol - minVol) / (maxVol - minVol)) * 100.0 : 50.0;

    int    rangeMonths = MathMin(24, copied - 1);
    double high24m     = 0, low24m = DBL_MAX;
    int    high24mBar  = 0, low24mBar = 0;
    for(int i = 1; i <= rangeMonths; i++) {
        if(rates[i].high > high24m) { high24m = rates[i].high; high24mBar = i; }
        if(rates[i].low  < low24m)  { low24m  = rates[i].low;  low24mBar  = i; }
    }
    double distFromHigh24m = (high24m > 0)  ? ((high24m - currentClose) / high24m) * 100.0  : 0;
    double distFromLow24m  = (low24m  > 0)  ? ((currentClose - low24m)  / low24m)  * 100.0  : 0;
    double range24m        = high24m - low24m;
    double pctIn24mRange   = (range24m > 0) ? ((currentClose - low24m)  / range24m) * 100.0 : 50.0;

    MqlDateTime nowDt;
    datetime refTime = (g_pretendDT > 0) ? g_pretendDT : TimeCurrent();
    TimeToStruct(refTime, nowDt);
    int currentMonth   = nowDt.mon;
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

    int    sqCount = 0, sqUp = 0, sqDn = 0;
    double sqSum = 0, sqBest = -DBL_MAX, sqWorst = DBL_MAX;
    int    sqBestYear = 0, sqWorstYear = 0;
    int    firstMonthOfQ = (currentQuarter - 1) * 3 + 1;
    int    lastMonthOfQ  = firstMonthOfQ + 2;
    for(int i = copied - 1; i >= 3; i--) {
        MqlDateTime dt; TimeToStruct(rates[i].time, dt);
        if(dt.mon != firstMonthOfQ) continue;
        MqlDateTime dt1, dt2;
        TimeToStruct(rates[i-1].time, dt1);
        TimeToStruct(rates[i-2].time, dt2);
        if(dt1.year != dt.year || dt1.mon != firstMonthOfQ + 1) continue;
        if(dt2.year != dt.year || dt2.mon != lastMonthOfQ)       continue;
        double qOpen  = rates[i].open;
        double qClose = rates[i-2].close;
        double pct    = (qOpen > 0) ? ((qClose - qOpen) / qOpen) * 100.0 : 0;
        sqCount++;
        sqSum += pct;
        if(pct >= 0) sqUp++; else sqDn++;
        if(pct > sqBest)  { sqBest  = pct; sqBestYear  = dt.year; }
        if(pct < sqWorst) { sqWorst = pct; sqWorstYear = dt.year; }
    }
    double sqAvg = (sqCount > 0) ? sqSum / sqCount : 0;

    //=======================================================================
    // NARRATIVE ENGINE
    //=======================================================================
    string s_history;
    if(athBar == 0)
        s_history = StringFormat(
            "**%s** is currently at the highest price ever recorded in this dataset (**%s**), up **%.1f%%** from its starting point of **%s** on %s.",
            g_symbol, DoubleToString(currentClose, digits), netMovePct,
            DoubleToString(firstOpen, digits), TimeToString(firstTime, TIME_DATE));
    else if(pctFromATH < 5.0)
        s_history = StringFormat(
            "**%s** is near the top of its recorded history, having pulled back just **%.1f%%** from the all-time high of **%s** reached **%d months ago**.",
            g_symbol, pctFromATH, DoubleToString(allTimeHigh, digits), athBar);
    else if(pctFromATH < 25.0)
        s_history = StringFormat(
            "**%s** is in a corrective phase from its historical peak of **%s** — currently **%.1f%%** below that level, reached **%d months ago**.",
            g_symbol, DoubleToString(allTimeHigh, digits), pctFromATH, athBar);
    else
        s_history = StringFormat(
            "**%s** is well below its historical peak — **%.1f%%** from the all-time high of **%s** set **%d months ago**, and **%.1f%%** above the all-time low of **%s**.",
            g_symbol, pctFromATH, DoubleToString(allTimeHigh, digits), athBar,
            pctFromATL, DoubleToString(allTimeLow, digits));

    string s_structure;
    bool hhOn = (consHH > 0), hlOn = (consHL > 0), lhOn = (consLH > 0), llOn = (consLL > 0);
    if(hhOn && hlOn)
        s_structure = StringFormat(
            "Momentum is intact — **%d** consecutive months have produced both higher highs and higher lows, with the full range climbing. The most recent **%d months** have also printed higher peaks and higher troughs than the **%d months** before them.",
            MathMin(consHH, consHL), half, half);
    else if(lhOn && llOn)
        s_structure = StringFormat(
            "Momentum is fading — **%d** consecutive months have produced both lower highs and lower lows, with the full range declining. The most recent **%d months** have also printed lower peaks and lower troughs than the **%d months** before them.",
            MathMin(consLH, consLL), half, half);
    else if(hhOn && llOn)
        s_structure = StringFormat(
            "The range is expanding in both directions — **%d** consecutive months of higher highs alongside **%d** months of lower lows, with no directional commitment from the market.",
            consHH, consLL);
    else if(lhOn && hlOn)
        s_structure = StringFormat(
            "The range is contracting — falling highs for **%d** months while lows have been rising for **%d** months, compressing price into a narrowing corridor.",
            consLH, consHL);
    else if(hhOn)
        s_structure = StringFormat(
            "Peaks have been climbing for **%d** consecutive months, though the lows have not confirmed a consistent direction alongside them.", consHH);
    else if(lhOn)
        s_structure = StringFormat(
            "Peaks have been declining for **%d** consecutive months, though the lows have not confirmed a consistent direction.", consLH);
    else if(hlOn)
        s_structure = StringFormat(
            "The floor has been rising for **%d** consecutive months, though the highs have not established a consistent pattern.", consHL);
    else if(llOn)
        s_structure = StringFormat(
            "The floor has been declining for **%d** consecutive months, though the highs have not established a consistent pattern.", consLL);
    else
        s_structure = "No consecutive sequence in either highs or lows — structure is currently mixed with no clear directional pattern.";

    string s_range;
    if(pctIn24mRange >= 80)
        s_range = StringFormat(
            "Price is pressing against the upper end of its two-year range, sitting just **%.1f%%** from the two-year high of **%s** set **%d months ago**.",
            distFromHigh24m, DoubleToString(high24m, digits), high24mBar);
    else if(pctIn24mRange >= 60)
        s_range = StringFormat(
            "Price is in the upper portion of its two-year range (**%s** to **%s**), **%.1f%%** from the recent high.",
            DoubleToString(low24m, digits), DoubleToString(high24m, digits), distFromHigh24m);
    else if(pctIn24mRange >= 40)
        s_range = StringFormat(
            "Price is near the midpoint of its two-year range (**%s** to **%s**), **%.1f%%** from each extreme.",
            DoubleToString(low24m, digits), DoubleToString(high24m, digits),
            MathMin(distFromHigh24m, distFromLow24m));
    else if(pctIn24mRange >= 20)
        s_range = StringFormat(
            "Price is in the lower portion of its two-year range (**%s** to **%s**), **%.1f%%** from the recent low.",
            DoubleToString(low24m, digits), DoubleToString(high24m, digits), distFromLow24m);
    else
        s_range = StringFormat(
            "Price is pressing against the lower end of its two-year range, sitting just **%.1f%%** from the two-year low of **%s** set **%d months ago**.",
            distFromLow24m, DoubleToString(low24m, digits), low24mBar);

    string s_percentile;
    if(percentile >= 97)
        s_percentile = StringFormat(
            "This price level is at the extreme upper end of the full historical record — above **%.0f%%** of all monthly closes ever recorded in this dataset.", percentile);
    else if(percentile >= 85)
        s_percentile = StringFormat(
            "Price remains historically elevated — higher than **%.0f%%** of all monthly closes across **%d months** of data.", percentile, totalCloses);
    else if(percentile >= 60)
        s_percentile = StringFormat(
            "Price sits in the upper portion of its long-term historical distribution (**%.0fth percentile** of **%d** recorded closes).", percentile, totalCloses);
    else if(percentile >= 40)
        s_percentile = StringFormat(
            "Price is near the long-term median of all historical closes (**%.0fth percentile** of **%d** recorded months).", percentile, totalCloses);
    else if(percentile >= 15)
        s_percentile = StringFormat(
            "Price sits in the lower portion of its long-term historical distribution (**%.0fth percentile** of **%d** recorded closes).", percentile, totalCloses);
    else if(percentile >= 3)
        s_percentile = StringFormat(
            "Price is historically depressed — below **%.0f%%** of all monthly closes across **%d months** of data.", 100.0 - percentile, totalCloses);
    else
        s_percentile = StringFormat(
            "This price level is at the extreme lower end of the full historical record — below **%.0f%%** of all monthly closes ever recorded.", 100.0 - percentile);

    string s_dd, s_ddContext = "";
    if(pctFromATH < 1.0)
        s_dd = "There is no current drawdown — price is at its all-time high.";
    else if(ddCount == 0 || pctFromATH < avgDD * 0.6)
        s_dd = StringFormat(
            "The current pullback of **%.1f%%** is shallow relative to this market's history. *(Average correction on record: **%.1f%%**, deepest: **%.1f%%.)*",
            pctFromATH, avgDD, largestDD);
    else if(pctFromATH < avgDD)
        s_dd = StringFormat(
            "The current pullback of **%.1f%%** is approaching, but has not yet reached, the typical correction depth of **%.1f%%** recorded in this dataset.",
            pctFromATH, avgDD);
    else if(pctFromATH < largestDD)
        s_dd = StringFormat(
            "The current pullback of **%.1f%%** has exceeded the average correction depth (**%.1f%%**) but remains below the deepest drawdown on record (**%.1f%%**).",
            pctFromATH, avgDD, largestDD);
    else
        s_dd = StringFormat(
            "The current pullback of **%.1f%%** matches or exceeds the deepest correction ever recorded in this dataset (**%.1f%%**).",
            pctFromATH, largestDD);
    if(pctFromATH > 1.0)
        s_ddContext = (deepDDCount > 0)
            ? StringFormat("A pullback of this depth has occurred **%d** time(s) in **%.0f years** of history.", deepDDCount, copied / 12.0)
            : StringFormat("No previous correction in **%.0f years** of history has reached this depth.", copied / 12.0);

    string s_vol;
    if(volPercentile >= 90)
        s_vol = StringFormat(
            "The market is in a high-activity regime — monthly swings over the past year are larger than at nearly any point in this dataset's history. *(Volatility: **%.2f%%** vs average **%.2f%%.)*",
            currentVol, historicalVol);
    else if(volPercentile >= 65)
        s_vol = StringFormat(
            "Activity is running above the historical norm — monthly swings have been wider than usual. *(Volatility: **%.2f%%** vs average **%.2f%%.)*",
            currentVol, historicalVol);
    else if(volPercentile <= 10)
        s_vol = StringFormat(
            "The market is in an unusually quiet regime — monthly swings are narrower than at nearly any point in this dataset's history. *(Volatility: **%.2f%%** vs average **%.2f%%.)*",
            currentVol, historicalVol);
    else if(volPercentile <= 35)
        s_vol = StringFormat(
            "Activity is running below the historical norm — monthly swings have been calmer than usual. *(Volatility: **%.2f%%** vs average **%.2f%%.)*",
            currentVol, historicalVol);
    else
        s_vol = StringFormat(
            "Activity is running close to the historical norm — monthly swings are typical for this market. *(Volatility: **%.2f%%** vs average **%.2f%%.)*",
            currentVol, historicalVol);

    string s_ma;
    if(vsShortMA > 0 && vsLongMA > 0)
        s_ma = StringFormat(
            "Price is above both its **%d-month** (**%s**) and **%d-month** (**%s**) averages.",
            InpShortMAPeriod, DoubleToString(shortMA, digits), InpLongMAPeriod, DoubleToString(longMA, digits));
    else if(vsShortMA < 0 && vsLongMA < 0)
        s_ma = StringFormat(
            "Price has fallen below both its **%d-month** (**%s**) and **%d-month** (**%s**) averages.",
            InpShortMAPeriod, DoubleToString(shortMA, digits), InpLongMAPeriod, DoubleToString(longMA, digits));
    else if(vsShortMA > 0 && vsLongMA < 0)
        s_ma = StringFormat(
            "Price is above the **%d-month** average (**%s**) but has not recovered past the **%d-month** average (**%s**).",
            InpShortMAPeriod, DoubleToString(shortMA, digits), InpLongMAPeriod, DoubleToString(longMA, digits));
    else
        s_ma = StringFormat(
            "Price has slipped below the **%d-month** average (**%s**) while remaining above the **%d-month** average (**%s**).",
            InpShortMAPeriod, DoubleToString(shortMA, digits), InpLongMAPeriod, DoubleToString(longMA, digits));

    string s_seq;
    if(upCount > downCount + 1)
        s_seq = StringFormat(
            "Recent months have leaned toward up-closes — **%d** up and **%d** down across the last **%d** completed candles: **%s**.",
            upCount, downCount, recentWindow, recentBars);
    else if(downCount > upCount + 1)
        s_seq = StringFormat(
            "Recent months have leaned toward down-closes — **%d** down and **%d** up across the last **%d** completed candles: **%s**.",
            downCount, upCount, recentWindow, recentBars);
    else
        s_seq = StringFormat(
            "Recent months have been evenly split with no directional lean across **%d** completed candles: **%s**.",
            recentWindow, recentBars);

    double prevPct = (prevOpen > 0) ? ((prevClose - prevOpen) / prevOpen) * 100.0 : 0.0;
    string s_last_candle = StringFormat(
        "Opened at **%s**, closed at **%s** (**%.2f%%**).",
        DoubleToString(prevOpen, digits), DoubleToString(prevClose, digits), prevPct);

    string s_current_candle = StringFormat(
        "Opened at **%s**. High: **%s**. Low: **%s**. Currently at **%s** (**%.2f%%** from last month's close). Body: **%.0f%%** of total range. Upper wick: **%s**. Lower wick: **%s**.",
        DoubleToString(currentOpen, digits), DoubleToString(currentHigh, digits),
        DoubleToString(currentLow, digits),  DoubleToString(currentClose, digits),
        moveFromPrevPct, bodyPct, DoubleToString(upperWick, digits), DoubleToString(lowerWick, digits));

    string s_month;
    if(smCount < 5)
        s_month = StringFormat(
            "Only **%d** recorded instances of **%s** in this dataset — not enough to identify seasonal patterns.",
            smCount, MonthName(currentMonth));
    else {
        double smUpRate = (double)smUp / smCount * 100.0;
        if(smUpRate > 65)
            s_month = StringFormat(
                "**%s** has historically leaned toward up-closes (**%d** of **%d** instances, **%.0f%%**), with an average recorded change of **%.2f%%** and a range from **+%.2f%%** (%d) to **%.2f%%** (%d).",
                MonthName(currentMonth), smUp, smCount, smUpRate, smAvg, smBest, smBestYear, smWorst, smWorstYear);
        else if(smUpRate < 35)
            s_month = StringFormat(
                "**%s** has historically leaned toward down-closes (**%d** of **%d** instances, **%.0f%%**), with an average recorded change of **%.2f%%** and a range from **+%.2f%%** (%d) to **%.2f%%** (%d).",
                MonthName(currentMonth), smDn, smCount, 100.0-smUpRate, smAvg, smBest, smBestYear, smWorst, smWorstYear);
        else
            s_month = StringFormat(
                "**%s** shows no consistent directional tendency across **%d** instances — roughly balanced between up and down, averaging **%.2f%%**, ranging from **+%.2f%%** (%d) to **%.2f%%** (%d).",
                MonthName(currentMonth), smCount, smAvg, smBest, smBestYear, smWorst, smWorstYear);
    }

    string s_quarter;
    if(sqCount < 4)
        s_quarter = StringFormat(
            "Only **%d** complete instances of **%s** — not enough to identify seasonal patterns.",
            sqCount, QuarterLabel(currentQuarter));
    else {
        double sqUpRate = (double)sqUp / sqCount * 100.0;
        if(sqUpRate > 65)
            s_quarter = StringFormat(
                "**%s** has historically leaned toward positive closes (**%d** of **%d** instances, **%.0f%%**), averaging **%.2f%%**, ranging from **+%.2f%%** (%d) to **%.2f%%** (%d).",
                QuarterLabel(currentQuarter), sqUp, sqCount, sqUpRate, sqAvg, sqBest, sqBestYear, sqWorst, sqWorstYear);
        else if(sqUpRate < 35)
            s_quarter = StringFormat(
                "**%s** has historically leaned toward negative closes (**%d** of **%d** instances, **%.0f%%**), averaging **%.2f%%**, ranging from **+%.2f%%** (%d) to **%.2f%%** (%d).",
                QuarterLabel(currentQuarter), sqDn, sqCount, 100.0-sqUpRate, sqAvg, sqBest, sqBestYear, sqWorst, sqWorstYear);
        else
            s_quarter = StringFormat(
                "**%s** shows no consistent directional tendency across **%d** instances — roughly balanced, averaging **%.2f%%**, ranging from **+%.2f%%** (%d) to **%.2f%%** (%d).",
                QuarterLabel(currentQuarter), sqCount, sqAvg, sqBest, sqBestYear, sqWorst, sqWorstYear);
    }

    string s_dataset = StringFormat(
        "*Dataset: **%d monthly candles**, **%.1f years** (%s – %s). Closes: **%d**. Corrections: **%d**. Vol windows: **%d**. %s instances: **%d**. %s instances: **%d**.*",
        copied, copied / 12.0,
        TimeToString(firstTime, TIME_DATE), TimeToString(refTime, TIME_DATE),
        totalCloses, ddCount, MathMax(0, retCount - 11),
        MonthName(currentMonth), smCount, QuarterLabel(currentQuarter), sqCount);

    //=======================================================================
    // BUILD JSON PAYLOAD
    //=======================================================================
    bool isPretend = (g_pretendDT > 0);
    string timeStr = isPretend
        ? TimeToString(g_pretendDT, TIME_DATE|TIME_SECONDS)
        : TimeToString(TimeCurrent(), TIME_DATE|TIME_SECONDS);

    string j = "{\n";
    j += "  \"symbol\": \""      + g_symbol  + "\",\n";
    j += "  \"server_time\": \"" + timeStr   + "\",\n";
    if(isPretend) j += "  \"pretend_mode\": true,\n";
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
    j += "    \"all_time_high\":         " + DoubleToString(allTimeHigh,   digits) + ",\n";
    j += "    \"all_time_low\":          " + DoubleToString(allTimeLow,    digits) + ",\n";
    j += "    \"pct_from_ath\":          " + DoubleToString(pctFromATH,    2)      + ",\n";
    j += "    \"pct_from_atl\":          " + DoubleToString(pctFromATL,    2)      + ",\n";
    j += "    \"candles_copied\":        " + IntegerToString(copied)               + ",\n";
    j += "    \"percentile\":            " + DoubleToString(percentile,    2)      + ",\n";
    j += "    \"current_volatility\":    " + DoubleToString(currentVol,    4)      + ",\n";
    j += "    \"historical_volatility\": " + DoubleToString(historicalVol, 4)      + ",\n";
    j += "    \"volatility_percentile\": " + DoubleToString(volPercentile, 2)      + ",\n";
    j += "    \"short_ma\":              " + DoubleToString(shortMA,       digits) + ",\n";
    j += "    \"long_ma\":               " + DoubleToString(longMA,        digits) + ",\n";
    j += "    \"range_high_24m\":        " + DoubleToString(high24m,       digits) + ",\n";
    j += "    \"range_low_24m\":         " + DoubleToString(low24m,        digits) + ",\n";
    j += "    \"pct_in_24m_range\":      " + DoubleToString(pctIn24mRange, 2)      + ",\n";
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
