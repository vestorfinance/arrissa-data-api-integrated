//+------------------------------------------------------------------+
//|                                        MarketsBrainAPI.mq5       |
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
//  On-demand 22-module neural brain readout via HTTP queue API.
//  Polls /markets-brain-api-v1/markets-brain-api.php every
//  InpApiPollingSeconds seconds. On a pending request the EA reads
//  the requested symbol, runs all 22 neural brain modules, and
//  POSTs raw market-state scores and thoughts as JSON.
//
//  OUTPUT: raw neural state only — NO BUY / SELL signals.
//
//+------------------------------------------------------------------+
#property copyright "Copyright 2026, Arrissa Technologies."
#property link      "https://arrissa.trade"
#property version   "1.00"

input string          AppBaseURL           = "http://127.0.0.1"; // Base URL
input bool            InpEnableApi         = true;               // Enable API
input int             InpApiPollingSeconds = 1;                  // Poll interval (s)
input bool            InpDebugMode         = false;              // Debug output
input ENUM_TIMEFRAMES InpPrimaryTF         = PERIOD_H1;          // Primary timeframe
input ENUM_TIMEFRAMES InpHTF               = PERIOD_H4;          // Higher timeframe
input ENUM_TIMEFRAMES InpMTF               = PERIOD_M15;         // Mid timeframe

//=======================================================================
// THOUGHT BANKS
//=======================================================================
string TH_TICK[10] = {
   "Last bar registered directional close. Movement logged.",
   "Small-range bar. Volume-to-range ratio elevated relative to recent average.",
   "Directional bar without sustained follow-through. Momentum not maintained.",
   "Last 5 bars: bearish close count = 5. No bullish bar recorded in sequence.",
   "Single high-volume bar printed. Volume exceeds recent average significantly.",
   "Price range contracting. ATR compression present.",
   "Bar pace declining. Volume-to-range ratio elevated. Absorption pattern detected.",
   "High-volume bar on offer side. Ask-side volume concentration logged.",
   "Uptick attempts absorbed at offer. Resistance at ask level active.",
   "Downtick attempts absorbed at bid. Support at bid level active. Range locked."
};
string TH_MEMORY[10] = {
   "Current price overlaps previously traded zone. Historical activity at this level on record.",
   "Price at zone with prior reversal recorded. Historical: directional rejection occurred here.",
   "Prior instance of this setup type resulted in failure. Historical reference logged.",
   "Price consolidated at this level for extended period in prior session data.",
   "Prior session data: aggressive buying at this time window preceded reversal. Reference logged.",
   "Swing low confirmed at this level in prior session. Structural anchor point recorded.",
   "Historical data: momentum at this magnitude exhausted within 5 bars on multiple prior occurrences.",
   "Prior sharp sell-off originated near current level. Historical sell-side activity at this zone.",
   "Failed breakout recorded at this zone in historical data. Prior long positions above current price.",
   "Stop run above prior session high confirmed in historical data. Structural reference logged."
};
string TH_ANTICIPATE[10] = {
   "5-bar ATR below 10-bar ATR. Range compression detected.",
   "ATR compression ratio below threshold. Volatility contraction in progress.",
   "5-bar average range below 10-bar average range. Coiling pattern present.",
   "Level proximity: within 1x ATR. Distance to structural level: minimal.",
   "Volume increasing within compressed range. Participation rate rising.",
   "Sell-side volume declining across recent bars. Supply pressure contracting.",
   "No historical volume in zone above current price. Order book gap detected.",
   "Module alignment score elevated on prevailing side. Directional consensus present.",
   "Directional volume on next bar required to confirm current pressure reading.",
   "Directional resolution pending. Insufficient data to determine which side acts next."
};
string TH_UNCERTAIN[10] = {
   "Directional consensus: insufficient. No dominant signal detected.",
   "Opposing module scores both above threshold. Conflict score: elevated.",
   "Pattern quality score: high. Conflict metric: unchanged.",
   "Prior directional event: recalibration triggered. Bias reset initiated.",
   "Market data does not confirm directional necessity in either direction.",
   "Prior move directional weighting flagged for recalibration. Data reset in progress.",
   "Historical pattern match: partial. Current conditions may deviate from prior outcomes.",
   "Module alignment: mixed. No consensus direction established.",
   "Neutral module count: 5 or more. Directional signal below threshold.",
   "Module scores: opposing directions both active. Market state: unresolved."
};
string TH_MOMENTUM[10] = {
   "RSI shifted direction. MACD crossover in progress. Momentum indicator change logged.",
   "RSI ascending. ROC positive. Momentum indicators: bullish alignment recorded.",
   "Sell-side momentum declining. Successive bearish bars recording decreasing range.",
   "Price velocity increasing. Indicator reading above prior period baseline.",
   "Momentum indicator at extended level. RSI above 76. Overbought zone.",
   "High-velocity bar on below-average volume. Momentum not volume-supported.",
   "Slow directional grind. Bar count: steady. Sustained low-volatility directional pressure.",
   "Intrabar direction reversal detected. Bar closed in opposing direction to open.",
   "RSI direction diverging from price direction. Divergence status: active.",
   "Momentum indicator and price direction: opposing. Divergence reading logged."
};
string TH_VOLUME[10] = {
   "Volume spike detected. Price range minimal relative to volume. Absorption ratio: elevated.",
   "Retracement bar volume below 20-bar average. Volume contraction on pullback recorded.",
   "Breakout bar volume above 1.5x average. Volume-confirmed directional move logged.",
   "Elevated volume at price highs. Limited upward range on high volume. Distribution signature.",
   "Successive bullish bars: volume increasing per bar. Participation expanding.",
   "Successive bearish bars: volume decreasing per bar. Sell-side participation contracting.",
   "Volume above 2.5x average with extended range. Climactic bar recorded.",
   "Volume between 40-100% of 20-bar average. Moderate participation. Directional lean follows price.",
   "Current volume below 40% of average. Low-participation session detected.",
   "Volume and price direction: aligned. Directional volume confirmation recorded."
};
string TH_SESSION[10] = {
   "Time window: opening range period. Session structure: not yet established.",
   "Time window: post-first-hour. Opening range boundaries defined.",
   "Time window: midday session. Historical volume profile for this period: below average.",
   "Time window: afternoon session. Historical volume profile for this period: rising.",
   "Time window: pre-close. Historical pattern for this period: position reduction active.",
   "Time window: pre-market. Reference level established. Regular session anchor set.",
   "Time window: New York open. Historical volatility for this period: elevated.",
   "Day of week: Friday. Historical carry-over positioning for this day: reduced.",
   "Time elapsed: 3 hours. Session range established. Level proximity conditions present.",
   "Time reference logged. Non-movement noted relative to historical activity at this window."
};
string TH_SR[10] = {
   "Price at resistance level. Level holding. Historical seller activity at this zone.",
   "Resistance level breached. Prior structural level invalidated.",
   "Third test of level recorded. Historical test count at this level: 3.",
   "Prior support retested from above as resistance. Role reversal confirmed.",
   "Round number within 1x ATR of current price. Round number proximity logged.",
   "Trendline: second test recorded. Historical hold count: 2.",
   "Prior resistance retested as support from below. Level confirmation in progress.",
   "Stop cluster zone: above recent swing high. Liquidity concentration mapped.",
   "Price within zone, not at discrete level. Zone boundaries: active range.",
   "Level holding. Directional pressure at level declining. Structural strength declining."
};
string TH_PATTERN[10] = {
   "Consolidation above prior support detected. Bull flag formation in progress.",
   "Dual equal highs detected. Volume on second high: declining. Double-top formation.",
   "Rejection bar detected. Wick extends beyond body by 2.5x. Directional rejection at level.",
   "Higher lows: count = 3. Structural floor: rising. Successive higher-low sequence.",
   "Structure: higher high, higher low. Uptrend structure present.",
   "Structure: lower high, lower low. Downtrend structure present.",
   "Converging highs and lows detected. Wedge formation in progress.",
   "Inside bar detected. Range fully contained within prior bar. No directional commitment.",
   "Pin bar at resistance level. Upper wick 2.5x body. Level rejection bar at resistance.",
   "Pattern formation: in progress. Completion criteria not yet met. Insufficient bar count."
};
string TH_FLOW[10] = {
   "Large resting order detected at level. Price advance constrained at boundary.",
   "Market orders hitting ask. Urgency buy flow recorded at offer.",
   "Market orders hitting bid. Urgency sell flow recorded at bid.",
   "Order book: thin above current price. Low resting sell volume in zone above.",
   "Passive buy orders absorbing sell pressure. Price held at level. Bid defense active.",
   "Passive sell orders absorbing buy pressure. Price capped at level. Offer defense active.",
   "Prior long entry zone: overhead. Historical long positions above current price.",
   "Prior short entry zone: below. Historical short positions below current price.",
   "Cumulative delta diverging from price. Order flow direction opposing price direction.",
   "Order flow data recorded. Note: order flow is subject to spoofing."
};
string TH_TRAP[10] = {
   "Breakout: clean structure. Historical data: clean breakouts show elevated failure rate.",
   "Pattern visibility: high. Widely-observed setups show elevated historical failure rate.",
   "Price exceeded level then reversed within 2 bars. Stop hunt signature detected.",
   "Bull trap pattern: price closed above prior high then closed back below it.",
   "Bear trap pattern: price closed below prior low then closed back above it.",
   "Textbook pattern formation detected. Historical data: textbook patterns show elevated trap rate.",
   "Consensus breakout positioning detected. Historical failure rate for consensus breakouts: elevated.",
   "Consensus directional trade: evident. Historical failure rate for consensus trades: elevated.",
   "Obvious entry signal present. Historical contrarian outcome rate for obvious setups: noted.",
   "Prior trap event recorded in historical data. Reference logged."
};
string TH_TREND[10] = {
   "EMA stack: fast > slow > long. Uptrend structure present.",
   "EMA stack: fast < slow < long. Downtrend structure present.",
   "EMA stack: no alignment. Range-bound structure present.",
   "EMA alignment change detected. Trend status updated.",
   "Counter-trend price move within trend structure. Primary trend structure: unchanged.",
   "Trend duration: extended. ATR declining relative to historical average.",
   "Trend duration: recent. ATR elevated relative to historical average.",
   "Retracement depth: deep. 50%+ of prior trend leg retraced.",
   "Trendline: second test failed. Structural level under pressure.",
   "Trend structure: dominant current parameter. Secondary signals logged."
};
string TH_MTF[10] = {
   "Timeframe conflict: higher timeframe and lower timeframe showing opposing signals.",
   "Higher timeframe structure: dominant reference. Lower timeframe signals: secondary.",
   "Lower timeframe: directional signal present. Higher timeframe: trend intact. Conflict logged.",
   "Daily timeframe: trend intact. Lower timeframe: counter-trend move in progress.",
   "Lower timeframe: directional signal within opposing higher timeframe trend. Conflict recorded.",
   "All three timeframes: bullish alignment. Multi-timeframe consensus: bullish.",
   "All three timeframes: bearish alignment. Multi-timeframe consensus: bearish.",
   "Timeframes: conflicting. No consensus established. Resolution pending.",
   "Higher timeframe review complete. Context logged for reference.",
   "Lower timeframe leading indicator change. Higher timeframe alignment shift in progress."
};
string TH_LIQUIDITY[10] = {
   "Current liquidity below recent average. Order book depth reduced.",
   "Spread widened above baseline. Liquidity provider participation: reduced.",
   "Liquidity pool: mapped above recent swing highs. Stop concentration zone above.",
   "Liquidity pool: mapped below recent swing lows. Stop concentration zone below.",
   "Market depth: thin. Large order market impact: above average estimate.",
   "Thin-volume zone detected above current price. Low historical order concentration.",
   "Thin-volume zone detected below current price. Low historical order concentration.",
   "Old highs: mapped as stop reference. Liquidity concentration at prior swing high.",
   "Spread normalized. Liquidity provider participation returned to baseline.",
   "Liquidity distribution: historically concentrated at range extremes."
};
string TH_ACCUM[10] = {
   "Quiet price action detected. Volume-to-range ratio below average. Possible accumulation pattern.",
   "Multiple lower boundary tests without breach. Buyer activity at support level logged.",
   "Volume rising. Price static. Absorption pattern detected. Volume-to-range ratio elevated.",
   "Dip-buying present. Immediate recovery from lows recorded. Active bid defense at level.",
   "Range narrowing at upper boundary. Highs contracting. Lows rising.",
   "Price held below level. Range formation at depressed price level detected.",
   "Successive higher lows. Range floor rising. Structural floor elevation recorded.",
   "Low-volume retracements, high-volume advances. Volume asymmetry: accumulation signature.",
   "High selling effort, low downward result. Buying absorption of sell pressure recorded.",
   "Accumulation pattern parameters: met. Structural conditions consistent with accumulation phase."
};
string TH_DIST[10] = {
   "Rally attempts absorbed at upper boundary. Repeated rejection at highs recorded.",
   "Volume asymmetry: high on bearish bars, low on bullish bars. Distribution signature.",
   "Successive lower highs. Range ceiling falling. Structural ceiling degradation recorded.",
   "Incremental sell volume at highs detected. Stealth distribution pattern.",
   "Upthrust pattern: price briefly above resistance then closed back inside range.",
   "Breakout above resistance failed. Return to range. False breakout confirmed.",
   "Low-volume rallies, high-volume drops. Volume asymmetry: distribution signature.",
   "High buying effort, low upward result. Selling absorption of buy pressure recorded.",
   "Lower high confirmed. Structural transition from prior advance to distribution recorded.",
   "Prior lower high confirmed. Distribution phase present."
};
string TH_BREAK[10] = {
   "Breakout with volume above 1.5x average. Volume-confirmed directional move logged.",
   "Breakout without volume support. Volume below average. Unconfirmed breakout recorded.",
   "Breakout on below-average volume. Volume confirmation absent.",
   "Breakout on volume above 2x average. Strong volume confirmation logged.",
   "Retest of breakout level in progress. Level conversion confirmation pending.",
   "Retest held. Level converted from resistance to support. Breakout confirmed.",
   "Retest failed. Price returned below breakout level. Breakout failure recorded.",
   "Price broke above level then reversed immediately. Breakout failure pattern.",
   "Level held for 3 sessions: now broken. Extended resistance level breached.",
   "Third breakout attempt of this level in progress. Attempt count: 3."
};
string TH_DEVIL[10] = {
   "Directional score: elevated. Opposing case evaluation in progress.",
   "Non-directional assessment initiated. Cross-checking for confirmation bias.",
   "Opposing scenario: under construction. Reasons for alternate outcome being enumerated.",
   "Data review without directional assumption in progress.",
   "Selection bias check: initiated. Reviewing data for confirmation filtering.",
   "Pattern vs noise evaluation: in progress. Signal confirmation pending.",
   "High score detected. Contradiction check and gap analysis initiated.",
   "Strong directional narrative detected. Counterarguments being evaluated.",
   "Strongest opposing argument under review.",
   "Obvious entry signal present. Historical failure rate for obvious signals: elevated."
};
string TH_REVERSAL[10] = {
   "Directional bar ratio shift detected. Character of tape changing.",
   "Advance bars: losing range. Decline bars: gaining range. Character shift logged.",
   "Climactic bar recorded. Volume spike with extended range. Momentum exhaustion signature.",
   "New extreme by single tick. Marginal new extreme. Exhaustion pattern.",
   "Retracement depth from extreme: exceeds 50% of prior directional leg. Deep pullback logged.",
   "Reversal pattern formation: complete. Follow-through confirmation pending.",
   "Reversal pattern: confirmed by follow-through bar. Structural change logged.",
   "V-shape recovery detected. Sharp decline followed by sharp recovery.",
   "Key level breached. Structural reversal parameters met.",
   "Current bar: retracement or reversal status pending. Insufficient bar count for confirmation."
};
string TH_PATFAIL[10] = {
   "Pattern formed then failed. Failure direction recorded for analysis.",
   "Pattern failure detected. Historical data: failed patterns generate elevated move in failure direction.",
   "Bull flag failed. Prior long positions above current price. Failure direction: bearish.",
   "Breakout failed. Prior long positions above current price. Failure direction: bearish.",
   "Breakdown failed. Prior short positions below current price. Failure direction: bullish.",
   "Pattern failure: confirmed. Failure direction logged.",
   "Pattern failure identified. Directional shift recorded.",
   "Pattern failure at major structural level. Significance: elevated vs mid-range failure.",
   "Pattern failure: logged. Applied to directional score update.",
   "Patterns are probabilistic. Current failure: within expected distribution."
};
string TH_SD[10] = {
   "Demand volume exceeding supply volume. Imbalance ratio: positive.",
   "Supply volume exceeding demand volume. Imbalance ratio: negative.",
   "Supply and demand volume: near equilibrium. No significant imbalance detected.",
   "Demand shock: high-volume bullish bar above 3x average. Demand surge recorded.",
   "Supply shock: high-volume bearish bar above 3x average. Supply surge recorded.",
   "Demand zone: three tests without breach. Buyer activity confirmed at level.",
   "Supply zone: three tests without breach. Seller activity confirmed at level.",
   "Fresh demand at current price. New buy volume, not retest. Demand zone origin.",
   "Fresh supply at current price. New sell volume, not retest. Supply zone origin.",
   "Imbalance differential increasing. Volume divergence between buy and sell side logged."
};

//=======================================================================
// ENUMS AND STRUCTURES
//=======================================================================
enum ETYPE   { BULL=0, BEAR=1, NEUTRAL=2, WARNING=3 };
enum EREGIME { RANGING=0, TREND_UP=1, TREND_DOWN=2, VOLATILE=3 };

#define NMODS 22

struct SMod {
   string  name;
   double  score;
   double  prev;
   double  weight;
   string  thought;
   ETYPE   ttype;
};

struct SBrain {
   double   score;
   double   conf;
   double   conflict;
   EREGIME  regime;
   string   dominant;
   bool     trap;
   double   trapScore;
   int      barsSince;
};

//=======================================================================
// GLOBALS
//=======================================================================
SMod   g_m[NMODS];
SBrain g_b;

// Per-request indicator handles
int g_h_ef   = INVALID_HANDLE;
int g_h_es   = INVALID_HANDLE;
int g_h_et   = INVALID_HANDLE;
int g_h_rsi  = INVALID_HANDLE;
int g_h_atr  = INVALID_HANDLE;
int g_h_macd = INVALID_HANDLE;
int g_h_hf   = INVALID_HANDLE;
int g_h_hs   = INVALID_HANDLE;
int g_h_mf   = INVALID_HANDLE;

// Per-request context
string          g_symbol = "";
ENUM_TIMEFRAMES g_tf, g_htf, g_mtf;
double          g_atr    = 0;
double          g_avgVol = 0;
int             g_shift     = 0;   // primary TF bar offset (0 = live)
int             g_shift_htf = 0;   // HTF bar offset
int             g_shift_mtf = 0;   // MTF bar offset
datetime        g_pretendDT = 0;   // pretend datetime (0 = live)

// API state
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
    InpApiUrl = AppBaseURL + "/markets-brain-api-v1/markets-brain-api.php";
    g_tf  = InpPrimaryTF;
    g_htf = InpHTF;
    g_mtf = InpMTF;
    Print("Markets Brain API EA v1 initialized. Endpoint: ", InpApiUrl);
    EventSetTimer(1);
    last_api_poll_time = TimeCurrent();
    return INIT_SUCCEEDED;
}

//+------------------------------------------------------------------+
void OnDeinit(const int reason)
{
    EventKillTimer();
    ReleaseHandles();
    Print("Markets Brain API EA deinitialized.");
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

    if(request_id == "" || symbol == "")
    {
        DebugPrint("ProcessApiRequest: missing required fields");
        return;
    }

    DebugPrint("Processing request_id=" + request_id + " symbol=" + symbol);

    g_symbol = symbol;

    // Pretend date/time: shift all data reads into history
    g_shift = 0; g_shift_htf = 0; g_shift_mtf = 0; g_pretendDT = 0;
    string pretend_date = ExtractJsonValue(request_json, "pretend_date");
    string pretend_time = ExtractJsonValue(request_json, "pretend_time");
    if(StringLen(pretend_date) >= 8 && StringLen(pretend_time) >= 3) {
        string y   = StringSubstr(pretend_date, 0, 4);
        string mon = StringSubstr(pretend_date, 5, 2);
        string d   = StringSubstr(pretend_date, 8, 2);
        string isoDT = y + "." + mon + "." + d + " " + pretend_time;
        g_pretendDT = StringToTime(isoDT);
        if(g_pretendDT > 0) {
            g_shift     = (int)iBarShift(symbol, g_tf,  g_pretendDT, true);
            g_shift_htf = (int)iBarShift(symbol, g_htf, g_pretendDT, true);
            g_shift_mtf = (int)iBarShift(symbol, g_mtf, g_pretendDT, true);
            if(g_shift     < 0) g_shift     = 0;
            if(g_shift_htf < 0) g_shift_htf = 0;
            if(g_shift_mtf < 0) g_shift_mtf = 0;
            DebugPrint("Pretend mode: " + isoDT + " shift=" + IntegerToString(g_shift));
        }
    }

    if(!CreateHandles(symbol))
    {
        DebugPrint("CreateHandles failed for " + symbol);
        return;
    }

    InitMods();
    ZeroMemory(g_b);
    g_b.regime    = RANGING;
    g_b.barsSince = 5;  // avoid first-bar guard in Uncertainty module

    RunBrain();
    Synthesize();

    string payload = BuildBrainPayload(symbol);
    ReleaseHandles();

    SendDataToApi(request_id, symbol, payload);
}

//+------------------------------------------------------------------+
bool CreateHandles(string symbol)
{
    g_h_ef   = iMA(symbol,   g_tf,  8,  0, MODE_EMA, PRICE_CLOSE);
    g_h_es   = iMA(symbol,   g_tf,  21, 0, MODE_EMA, PRICE_CLOSE);
    g_h_et   = iMA(symbol,   g_tf,  50, 0, MODE_EMA, PRICE_CLOSE);
    g_h_rsi  = iRSI(symbol,  g_tf,  14, PRICE_CLOSE);
    g_h_atr  = iATR(symbol,  g_tf,  14);
    g_h_macd = iMACD(symbol, g_tf,  12, 26, 9, PRICE_CLOSE);
    g_h_hf   = iMA(symbol,   g_htf, 8,  0, MODE_EMA, PRICE_CLOSE);
    g_h_hs   = iMA(symbol,   g_htf, 21, 0, MODE_EMA, PRICE_CLOSE);
    g_h_mf   = iMA(symbol,   g_mtf, 21, 0, MODE_EMA, PRICE_CLOSE);

    if(g_h_ef   == INVALID_HANDLE || g_h_es  == INVALID_HANDLE ||
       g_h_et   == INVALID_HANDLE || g_h_rsi == INVALID_HANDLE ||
       g_h_atr  == INVALID_HANDLE || g_h_macd== INVALID_HANDLE ||
       g_h_hf   == INVALID_HANDLE || g_h_hs  == INVALID_HANDLE ||
       g_h_mf   == INVALID_HANDLE)
    {
        ReleaseHandles();
        return false;
    }
    return true;
}

//+------------------------------------------------------------------+
void ReleaseHandles()
{
    if(g_h_ef   != INVALID_HANDLE) { IndicatorRelease(g_h_ef);   g_h_ef   = INVALID_HANDLE; }
    if(g_h_es   != INVALID_HANDLE) { IndicatorRelease(g_h_es);   g_h_es   = INVALID_HANDLE; }
    if(g_h_et   != INVALID_HANDLE) { IndicatorRelease(g_h_et);   g_h_et   = INVALID_HANDLE; }
    if(g_h_rsi  != INVALID_HANDLE) { IndicatorRelease(g_h_rsi);  g_h_rsi  = INVALID_HANDLE; }
    if(g_h_atr  != INVALID_HANDLE) { IndicatorRelease(g_h_atr);  g_h_atr  = INVALID_HANDLE; }
    if(g_h_macd != INVALID_HANDLE) { IndicatorRelease(g_h_macd); g_h_macd = INVALID_HANDLE; }
    if(g_h_hf   != INVALID_HANDLE) { IndicatorRelease(g_h_hf);   g_h_hf   = INVALID_HANDLE; }
    if(g_h_hs   != INVALID_HANDLE) { IndicatorRelease(g_h_hs);   g_h_hs   = INVALID_HANDLE; }
    if(g_h_mf   != INVALID_HANDLE) { IndicatorRelease(g_h_mf);   g_h_mf   = INVALID_HANDLE; }
}

//+------------------------------------------------------------------+
void InitMods()
{
    string nm[NMODS] = {
        "TICK_SENSE","MEMORY","ANTICIPATION","UNCERTAINTY","MOMENTUM","VOLUME",
        "SESSION","SR_LEVELS","PATTERN","ORDER_FLOW","TRAP_SENSE","TREND",
        "MULTI_TF","LIQUIDITY","ACCUMULATION","DISTRIBUTION","BREAKOUT",
        "DEVILS_EYE","REVERSALS","PATTERN_FAIL","SUPPLY_DEMAND","SYNTHESIS"
    };
    double wt[NMODS] = {
        0.7,0.8,0.7,0.6,1.0,0.9,0.5,1.0,0.8,0.9,0.9,1.0,
        1.0,0.7,0.8,0.8,0.9,0.7,0.9,0.8,0.9,0.0
    };
    for(int i = 0; i < NMODS; i++) {
        g_m[i].name    = nm[i];
        g_m[i].weight  = wt[i];
        g_m[i].score   = 0;
        g_m[i].prev    = 0;
        g_m[i].thought = "Sensing...";
        g_m[i].ttype   = NEUTRAL;
    }
}

//=======================================================================
// BRAIN RUNNER
//=======================================================================
void RunBrain()
{
    // ATR first — other modules depend on g_atr
    double ab[]; ArraySetAsSeries(ab, true);
    if(CopyBuffer(g_h_atr, 0, g_shift, 5, ab) >= 3) g_atr = ab[1];

    // First pass — Volume first so g_avgVol is set before any other module
    Mod_Volume();
    Mod_TickSense();
    Mod_Memory();
    Mod_Anticipation();
    Mod_Session();
    Mod_SR();
    Mod_Pattern();
    Mod_OrderFlow();
    Mod_Trend();
    Mod_MultiTF();
    Mod_Liquidity();
    Mod_Accumulation();
    Mod_Distribution();
    Mod_Breakout();
    Mod_Reversals();
    Mod_PatternFail();
    Mod_SupplyDemand();

    // Pre-synthesis score for context-sensitive modules (active modules only)
    double ts = 0, tw = 0;
    for(int i = 0; i < 17; i++) {
        if(MathAbs(g_m[i].score) < 0.05) continue;
        ts += g_m[i].score * g_m[i].weight;
        tw += g_m[i].weight;
    }
    g_b.score = (tw > 0) ? ts / tw : 0;

    // Second pass
    Mod_Momentum();
    Mod_Uncertainty();
    Mod_TrapSense();
    Mod_DevilAdvocate();
}

//+------------------------------------------------------------------+
void Synthesize()
{
    // Only count modules that actually fired — zeros dilute the signal
    double ts = 0, tw = 0;
    int active = 0;
    for(int i = 0; i < 21; i++) {
        if(MathAbs(g_m[i].score) < 0.05) continue;
        ts += g_m[i].score * g_m[i].weight;
        tw += g_m[i].weight;
        active++;
    }
    g_b.score = (tw > 0) ? ts / tw : 0;

    // Conflict measured across active modules only
    double var = 0;
    for(int i = 0; i < 21; i++) {
        if(MathAbs(g_m[i].score) < 0.05) continue;
        var += MathPow(g_m[i].score - g_b.score, 2);
    }
    g_b.conflict = (active > 1) ? MathSqrt(var / active) : 0;

    // Confidence: strength of signal × agreement factor × active-module factor
    double activeFactor = MathMin(1.0, active / 7.0);
    g_b.conf = MathAbs(g_b.score) * (1.0 - g_b.conflict * 0.4) * activeFactor;

    // VOLATILE: high conflict + elevated ATR
    if(g_b.conflict > 0.45 && g_b.regime != RANGING) {
        double atrBuf[]; ArraySetAsSeries(atrBuf, true);
        if(CopyBuffer(g_h_atr, 0, 0, 20, atrBuf) >= 20) {
            double atrOld = 0;
            for(int i = 10; i < 20; i++) atrOld += atrBuf[i];
            atrOld /= 10.0;
            if(atrBuf[1] > atrOld * 1.3) g_b.regime = VOLATILE;
        }
    }

    double mx = 0; int dom = 21;
    for(int i = 0; i < 21; i++) {
        double inf = MathAbs(g_m[i].score) * g_m[i].weight;
        if(inf > mx) { mx = inf; dom = i; }
    }
    g_b.dominant = g_m[dom].thought;

    // Module 21 — synthesis state (no directional signal)
    g_m[21].score = g_b.score;
    g_m[21].thought = "22-module weighted synthesis complete."
        + " Score: " + DoubleToString(g_b.score, 3)
        + ". Confidence: " + IntegerToString((int)(g_b.conf * 100)) + "%"
        + ". Conflict: " + DoubleToString(g_b.conflict, 2)
        + ". Regime: " + RegimeStr() + ".";
    g_m[21].ttype = (g_b.score > 0.1) ? BULL : (g_b.score < -0.1) ? BEAR : NEUTRAL;
}

//=======================================================================
// MODULES (adapted from LivingOrganism_NeuralBrain.mq5)
// Uses g_symbol/g_tf instead of _Symbol/PERIOD_CURRENT
// Uses g_h_* handles instead of module-level h_* globals
//=======================================================================

void Mod_TickSense()
{
    double hi[], lo[], cl[], op[]; long vl[];
    ArraySetAsSeries(hi,true); ArraySetAsSeries(lo,true);
    ArraySetAsSeries(cl,true); ArraySetAsSeries(op,true); ArraySetAsSeries(vl,true);
    if(CopyHigh(g_symbol,g_tf,g_shift,6,hi)<6 || CopyLow(g_symbol,g_tf,g_shift,6,lo)<6 ||
       CopyClose(g_symbol,g_tf,g_shift,6,cl)<6 || CopyOpen(g_symbol,g_tf,g_shift,6,op)<6 ||
       CopyTickVolume(g_symbol,g_tf,g_shift,6,vl)<6) return;

    int bulls = 0;
    for(int i = 1; i <= 5; i++) if(cl[i] > op[i]) bulls++;

    double spread    = SymbolInfoDouble(g_symbol,SYMBOL_ASK) - SymbolInfoDouble(g_symbol,SYMBOL_BID);
    double avgSpread = g_atr * 0.05;
    bool bigVol   = ((double)vl[1] > g_avgVol * 1.5);
    bool noFollow = (cl[1] > op[1] && cl[1] < (hi[1]+lo[1])/2.0);

    g_m[0].prev = g_m[0].score;

    if(bulls >= 4 && bigVol)     { g_m[0].score=0.9;  g_m[0].thought=TH_TICK[7]; g_m[0].ttype=BULL; }
    else if(bulls >= 4)          { g_m[0].score=0.7;  g_m[0].thought=TH_TICK[1]; g_m[0].ttype=BULL; }
    else if(bulls == 3)          { g_m[0].score=0.2;  g_m[0].thought=TH_TICK[0]; g_m[0].ttype=NEUTRAL; }
    else if(bulls == 2)          { g_m[0].score=0.0;  g_m[0].thought=TH_TICK[9]; g_m[0].ttype=NEUTRAL; }
    else if(bulls == 1)          { g_m[0].score=-0.5; g_m[0].thought=TH_TICK[8]; g_m[0].ttype=BEAR; }
    else                         { g_m[0].score=-0.9; g_m[0].thought=TH_TICK[3]; g_m[0].ttype=BEAR; }

    if(noFollow)                 { g_m[0].score*=0.5; g_m[0].thought=TH_TICK[2]; g_m[0].ttype=WARNING; }
    if(spread > avgSpread * 2.5) { g_m[0].thought=TH_TICK[5]; g_m[0].ttype=WARNING; }
    if((double)vl[1] < g_avgVol * 0.3) { g_m[0].score*=0.3; g_m[0].thought=TH_TICK[6]; g_m[0].ttype=WARNING; }
}

void Mod_Memory()
{
    double hi[], lo[], cl[]; long vl[];
    ArraySetAsSeries(hi,true); ArraySetAsSeries(lo,true);
    ArraySetAsSeries(cl,true); ArraySetAsSeries(vl,true);
    if(CopyHigh(g_symbol,g_tf,g_shift,60,hi)<60 || CopyLow(g_symbol,g_tf,g_shift,60,lo)<60 ||
       CopyClose(g_symbol,g_tf,g_shift,60,cl)<60 || CopyTickVolume(g_symbol,g_tf,g_shift,60,vl)<60) return;

    double cur = cl[1];
    double atr = g_atr > 0 ? g_atr : 0.001;

    int volSpikeBar = -1; double maxVol = 0;
    for(int i = 5; i < 55; i++) if((double)vl[i] > maxVol) { maxVol=(double)vl[i]; volSpikeBar=i; }

    double volSpikePrice = (volSpikeBar > 0) ? (hi[volSpikeBar]+lo[volSpikeBar])/2.0 : 0;
    double distToSpike   = (volSpikePrice > 0) ? MathAbs(cur-volSpikePrice)/atr : 99;

    double rHi = hi[1], rLo = lo[1];
    for(int i = 2; i < 30; i++) { if(hi[i]>rHi) rHi=hi[i]; if(lo[i]<rLo) rLo=lo[i]; }
    double mid = (rHi + rLo) / 2.0;

    g_m[1].prev = g_m[1].score;

    if(distToSpike < 0.5)           { g_m[1].score=0.0;  g_m[1].thought=TH_MEMORY[1]; g_m[1].ttype=WARNING; }
    else if(MathAbs(cur-rHi)<atr*0.3){ g_m[1].score=-0.4; g_m[1].thought=TH_MEMORY[9]; g_m[1].ttype=WARNING; }
    else if(MathAbs(cur-rLo)<atr*0.3){ g_m[1].score=0.4;  g_m[1].thought=TH_MEMORY[7]; g_m[1].ttype=BULL; }
    else if(cur > mid)               { g_m[1].score=0.2;  g_m[1].thought=TH_MEMORY[0]; g_m[1].ttype=NEUTRAL; }
    else                             { g_m[1].score=-0.2; g_m[1].thought=TH_MEMORY[2]; g_m[1].ttype=NEUTRAL; }
}

void Mod_Anticipation()
{
    double hi[], lo[]; long vl[];
    ArraySetAsSeries(hi,true); ArraySetAsSeries(lo,true); ArraySetAsSeries(vl,true);
    if(CopyHigh(g_symbol,g_tf,g_shift,20,hi)<20 || CopyLow(g_symbol,g_tf,g_shift,20,lo)<20 ||
       CopyTickVolume(g_symbol,g_tf,g_shift,20,vl)<20) return;

    double rng5=0, rng10=0;
    for(int i=1;i<=5;i++)  rng5 +=(hi[i]-lo[i]);
    for(int i=1;i<=10;i++) rng10+=(hi[i]-lo[i]);
    rng5/=5.0; rng10/=10.0;
    double compression = rng5 / (rng10 > 0 ? rng10 : rng5);

    double vol5=0, vol10=0;
    for(int i=1;i<=5;i++)  vol5 +=(double)vl[i];
    for(int i=1;i<=10;i++) vol10+=(double)vl[i];
    vol5/=5.0; vol10/=10.0;
    bool volRising = (vol5 > vol10 * 1.1);

    g_m[2].prev = g_m[2].score;
    double s = 0;

    if(compression<0.7 && volRising)      { s=0.3; g_m[2].thought=TH_ANTICIPATE[2]; g_m[2].ttype=NEUTRAL; }
    else if(compression < 0.7)            { s=0.1; g_m[2].thought=TH_ANTICIPATE[1]; g_m[2].ttype=NEUTRAL; }
    else if(volRising && compression>1.2) { s=0.4; g_m[2].thought=TH_ANTICIPATE[4]; g_m[2].ttype=BULL; }
    else if(compression > 1.3)            { s=0.0; g_m[2].thought=TH_ANTICIPATE[0]; g_m[2].ttype=NEUTRAL; }
    else                                  { s=0.0; g_m[2].thought=TH_ANTICIPATE[9]; g_m[2].ttype=NEUTRAL; }

    g_m[2].score = MathMax(-1.0, MathMin(1.0, s));
}

void Mod_Uncertainty()
{
    double posScores=0, negScores=0, midScores=0;
    for(int i = 0; i < 9; i++) {
        if(g_m[i].score > 0.2)       posScores += g_m[i].score;
        else if(g_m[i].score < -0.2) negScores += MathAbs(g_m[i].score);
        else                          midScores++;
    }
    double conflict = (posScores>0 && negScores>0) ?
        MathMin(posScores,negScores)/(posScores+negScores) : 0;

    g_m[3].prev = g_m[3].score;

    if(conflict > 0.4)      { g_m[3].score=0; g_m[3].thought=TH_UNCERTAIN[9]; g_m[3].ttype=WARNING; }
    else if(conflict > 0.25){ g_m[3].score=0; g_m[3].thought=TH_UNCERTAIN[0]; g_m[3].ttype=WARNING; }
    else if(midScores >= 5) { g_m[3].score=0; g_m[3].thought=TH_UNCERTAIN[8]; g_m[3].ttype=NEUTRAL; }
    else if(g_b.barsSince<2){ g_m[3].score=0; g_m[3].thought=TH_UNCERTAIN[4]; g_m[3].ttype=NEUTRAL; }
    else                    { g_m[3].score=0; g_m[3].thought=TH_UNCERTAIN[7]; g_m[3].ttype=NEUTRAL; }
}

void Mod_Momentum()
{
    double rsi[], mc[], ms[];
    ArraySetAsSeries(rsi,true); ArraySetAsSeries(mc,true); ArraySetAsSeries(ms,true);
    if(CopyBuffer(g_h_rsi,0,g_shift,6,rsi)<6 || CopyBuffer(g_h_macd,0,g_shift,6,mc)<6 ||
       CopyBuffer(g_h_macd,1,g_shift,6,ms)<6) return;

    double s = (rsi[1]-50.0)/50.0*0.5;
    s += (mc[1]>ms[1]) ? 0.2 : -0.2;
    s += (mc[1]>0) ? 0.1 : -0.1;

    double c1 = iClose(g_symbol,g_tf,g_shift+1);
    double c4 = iClose(g_symbol,g_tf,g_shift+4);
    bool bearDiv  = (c1>c4 && rsi[1]<rsi[4]);
    bool bullDiv  = (c1<c4 && rsi[1]>rsi[4]);
    bool macdCross= (mc[1]>ms[1] && mc[2]<=ms[2]);

    g_m[4].prev  = g_m[4].score;
    g_m[4].score = MathMax(-1.0, MathMin(1.0, s));

    if(bearDiv)           { g_m[4].score*=0.3; g_m[4].thought=TH_MOMENTUM[9]; g_m[4].ttype=WARNING; }
    else if(bullDiv)      { g_m[4].score*=0.3; g_m[4].thought=TH_MOMENTUM[9]; g_m[4].ttype=WARNING; }
    else if(rsi[1]>76)    { g_m[4].thought=TH_MOMENTUM[4]; g_m[4].ttype=WARNING; }
    else if(rsi[1]<24)    { g_m[4].thought=TH_MOMENTUM[3]; g_m[4].ttype=WARNING; }
    else if(s>0.5&&macdCross){ g_m[4].thought=TH_MOMENTUM[1]; g_m[4].ttype=BULL; }
    else if(s > 0.3)      { g_m[4].thought=TH_MOMENTUM[0]; g_m[4].ttype=BULL; }
    else if(s > 0.0)      { g_m[4].thought=TH_MOMENTUM[6]; g_m[4].ttype=BULL; }
    else if(s > -0.3)     { g_m[4].thought=TH_MOMENTUM[8]; g_m[4].ttype=NEUTRAL; }
    else if(s > -0.5)     { g_m[4].thought=TH_MOMENTUM[2]; g_m[4].ttype=BEAR; }
    else                  { g_m[4].thought=TH_MOMENTUM[7]; g_m[4].ttype=BEAR; }
}

void Mod_Volume()
{
    long vl[]; ArraySetAsSeries(vl,true);
    if(CopyTickVolume(g_symbol,g_tf,g_shift,50,vl)<25) return;

    double avg = 0;
    for(int i = 1; i <= 20; i++) avg += (double)vl[i];
    avg /= 20.0; g_avgVol = avg;

    double cur   = (double)vl[1];
    double ratio = cur / (avg > 0 ? avg : 1.0);
    bool bull = (iClose(g_symbol,g_tf,g_shift+1) > iOpen(g_symbol,g_tf,g_shift+1));

    double vol3 = ((double)vl[1]+(double)vl[2]+(double)vl[3])/3.0;
    double vol6 = ((double)vl[4]+(double)vl[5]+(double)vl[6])/3.0;
    bool volFading = (vol3 < vol6 * 0.7);

    g_m[5].prev = g_m[5].score;
    double s = 0;

    if(ratio>2.5 && bull)    { s=0.9;  g_m[5].thought=TH_VOLUME[2]; g_m[5].ttype=BULL; }
    else if(ratio>1.8&&bull) { s=0.6;  g_m[5].thought=TH_VOLUME[4]; g_m[5].ttype=BULL; }
    else if(ratio>2.5&&!bull){ s=-0.9; g_m[5].thought=TH_VOLUME[6]; g_m[5].ttype=BEAR; }
    else if(ratio>1.8&&!bull){ s=-0.6; g_m[5].thought=TH_VOLUME[5]; g_m[5].ttype=BEAR; }
    else if(volFading&&bull) { s=0.2;  g_m[5].thought=TH_VOLUME[1]; g_m[5].ttype=BULL; }
    else if(ratio<0.4&&bull) { s=0.1;  g_m[5].thought=TH_VOLUME[8]; g_m[5].ttype=WARNING; }
    else if(ratio<0.4&&!bull){ s=-0.1; g_m[5].thought=TH_VOLUME[8]; g_m[5].ttype=NEUTRAL; }
    else if(ratio>1.0&&bull) { s=0.3;  g_m[5].thought=TH_VOLUME[9]; g_m[5].ttype=BULL; }
    else if(ratio>1.0&&!bull){ s=-0.3; g_m[5].thought=TH_VOLUME[9]; g_m[5].ttype=BEAR; }
    else if(bull)            { s=0.1;  g_m[5].thought=TH_VOLUME[7]; g_m[5].ttype=NEUTRAL; }
    else if(!bull)           { s=-0.1; g_m[5].thought=TH_VOLUME[7]; g_m[5].ttype=NEUTRAL; }
    else                     { s=0;    g_m[5].thought=TH_VOLUME[7]; g_m[5].ttype=NEUTRAL; }

    if(ratio > 3.0) { g_m[5].thought=TH_VOLUME[6]; g_m[5].ttype=WARNING; }
    g_m[5].score = MathMax(-1.0, MathMin(1.0, s));
}

void Mod_Session()
{
    MqlDateTime dt;
    TimeToStruct((g_pretendDT > 0) ? g_pretendDT : TimeCurrent(), dt);
    int h = dt.hour; double s = 0;

    if(h>=0&&h<7)        { s=-0.2; g_m[6].thought=TH_SESSION[0]; g_m[6].ttype=WARNING; }
    else if(h>=7&&h<9)   { s=0.0;  g_m[6].thought=TH_SESSION[5]; g_m[6].ttype=NEUTRAL; }
    else if(h>=9&&h<12)  { s=0.2;  g_m[6].thought=TH_SESSION[0]; g_m[6].ttype=NEUTRAL; }
    else if(h>=12&&h<13) { s=-0.1; g_m[6].thought=TH_SESSION[2]; g_m[6].ttype=WARNING; }
    else if(h>=13&&h<16) { s=0.2;  g_m[6].thought=TH_SESSION[6]; g_m[6].ttype=BULL; }
    else if(h>=16&&h<18) { s=0.0;  g_m[6].thought=TH_SESSION[4]; g_m[6].ttype=WARNING; }
    else if(h>=18&&h<21) { s=-0.1; g_m[6].thought=TH_SESSION[3]; g_m[6].ttype=NEUTRAL; }
    else                 { s=-0.2; g_m[6].thought=TH_SESSION[9]; g_m[6].ttype=WARNING; }

    if(dt.day_of_week==1) { s=0.0; g_m[6].thought=TH_SESSION[0]; g_m[6].ttype=NEUTRAL; }
    if(dt.day_of_week==5) { s-=0.1; g_m[6].thought=TH_SESSION[7]; g_m[6].ttype=WARNING; }

    g_m[6].prev  = g_m[6].score;
    g_m[6].score = MathMax(-0.3, MathMin(0.3, s));
}

void Mod_SR()
{
    double hi[], lo[], cl[];
    ArraySetAsSeries(hi,true); ArraySetAsSeries(lo,true); ArraySetAsSeries(cl,true);
    if(CopyHigh(g_symbol,g_tf,g_shift,50,hi)<50 || CopyLow(g_symbol,g_tf,g_shift,50,lo)<50 ||
       CopyClose(g_symbol,g_tf,g_shift,50,cl)<50) return;

    double atr = g_atr > 0 ? g_atr : 0.001;
    double cur = cl[1];
    double sHi = hi[1], sLo = lo[1];
    for(int i=2;i<40;i++) { if(hi[i]>sHi) sHi=hi[i]; if(lo[i]<sLo) sLo=lo[i]; }

    double dHi = (sHi-cur)/atr;
    double dLo = (cur-sLo)/atr;

    double point = SymbolInfoDouble(g_symbol,SYMBOL_POINT);
    double rnd   = MathRound(cur/(point*1000))*(point*1000);
    double dRnd  = MathAbs(cur-rnd)/atr;

    g_m[7].prev = g_m[7].score;
    double s = 0;

    if(dHi<0.2)      { s=-0.7; g_m[7].thought=TH_SR[1]; g_m[7].ttype=WARNING; }
    else if(dHi<0.6) { s=-0.4; g_m[7].thought=TH_SR[3]; g_m[7].ttype=BEAR; }
    else if(dLo<0.2) { s=0.7;  g_m[7].thought=TH_SR[2]; g_m[7].ttype=BULL; }
    else if(dLo<0.6) { s=0.4;  g_m[7].thought=TH_SR[7]; g_m[7].ttype=BULL; }
    else if(dRnd<0.3){ s=0.0;  g_m[7].thought=TH_SR[4]; g_m[7].ttype=NEUTRAL; }
    else             { s=0.0;  g_m[7].thought=TH_SR[9]; g_m[7].ttype=NEUTRAL; }

    int testCount = 0;
    for(int i=3;i<40;i++)
        if(MathAbs(hi[i]-sHi)<atr*0.3 || MathAbs(lo[i]-sLo)<atr*0.3) testCount++;
    if(testCount>=3 && dHi<0.6) { g_m[7].thought=TH_SR[8]; g_m[7].ttype=WARNING; }

    g_m[7].score = MathMax(-1.0, MathMin(1.0, s));
}

void Mod_Pattern()
{
    double op[], hi[], lo[], cl[];
    ArraySetAsSeries(op,true); ArraySetAsSeries(hi,true);
    ArraySetAsSeries(lo,true); ArraySetAsSeries(cl,true);
    if(CopyOpen(g_symbol,g_tf,g_shift,8,op)<8 || CopyHigh(g_symbol,g_tf,g_shift,8,hi)<8 ||
       CopyLow(g_symbol,g_tf,g_shift,8,lo)<8  || CopyClose(g_symbol,g_tf,g_shift,8,cl)<8) return;

    double atr = g_atr > 0 ? g_atr : MathAbs(cl[1]-op[1]);
    double b1  = MathAbs(cl[1]-op[1]);
    double r1  = hi[1]-lo[1];
    double uw  = hi[1]-MathMax(cl[1],op[1]);
    double lw  = MathMin(cl[1],op[1])-lo[1];
    bool bul  = (cl[1]>op[1]);
    bool big  = (b1>atr*0.6);
    bool bul2 = (cl[2]>op[2]);
    bool hh   = (hi[1]>hi[3]&&hi[3]>hi[6]);
    bool hl   = (lo[1]>lo[3]&&lo[3]>lo[6]);
    bool ll   = (lo[1]<lo[3]&&lo[3]<lo[6]);
    bool lh   = (hi[1]<hi[3]&&hi[3]<hi[6]);

    g_m[8].prev = g_m[8].score;
    double s = 0;

    if(lw>b1*2.5&&lw>uw*3.0&&r1>atr*0.4)
        { s=0.7;  g_m[8].thought=TH_PATTERN[8]; g_m[8].ttype=BULL; }
    else if(uw>b1*2.5&&uw>lw*3.0&&r1>atr*0.4)
        { s=-0.7; g_m[8].thought=TH_PATTERN[8]; g_m[8].ttype=BEAR; }
    else if(bul&&!bul2&&cl[1]>op[2]&&op[1]<cl[2]&&big)
        { s=0.8;  g_m[8].thought=TH_PATTERN[5]; g_m[8].ttype=BULL; }
    else if(!bul&&bul2&&op[1]>cl[2]&&cl[1]<op[2]&&big)
        { s=-0.8; g_m[8].thought=TH_PATTERN[5]; g_m[8].ttype=BEAR; }
    else if(hi[1]<hi[2]&&lo[1]>lo[2])
        { s=0.0;  g_m[8].thought=TH_PATTERN[7]; g_m[8].ttype=NEUTRAL; }
    else if(hh&&hl)
        { s=0.5;  g_m[8].thought=TH_PATTERN[4]; g_m[8].ttype=BULL; }
    else if(ll&&lh)
        { s=-0.5; g_m[8].thought=TH_PATTERN[5]; g_m[8].ttype=BEAR; }
    else if(b1<r1*0.1&&r1>atr*0.3)
        { s=0.0;  g_m[8].thought=TH_PATTERN[9]; g_m[8].ttype=NEUTRAL; }
    else if(bul&&big&&lw<b1*0.15)
        { s=0.5;  g_m[8].thought=TH_PATTERN[0]; g_m[8].ttype=BULL; }
    else if(!bul&&big&&uw<b1*0.15)
        { s=-0.5; g_m[8].thought=TH_PATTERN[1]; g_m[8].ttype=BEAR; }
    else
        { s=0.0;  g_m[8].thought=TH_PATTERN[9]; g_m[8].ttype=NEUTRAL; }

    g_m[8].score = MathMax(-1.0, MathMin(1.0, s));
}

void Mod_OrderFlow()
{
    double hi[], lo[], cl[], op[]; long vl[];
    ArraySetAsSeries(hi,true); ArraySetAsSeries(lo,true);
    ArraySetAsSeries(cl,true); ArraySetAsSeries(op,true); ArraySetAsSeries(vl,true);
    if(CopyHigh(g_symbol,g_tf,g_shift,5,hi)<5 || CopyLow(g_symbol,g_tf,g_shift,5,lo)<5 ||
       CopyClose(g_symbol,g_tf,g_shift,5,cl)<5 || CopyOpen(g_symbol,g_tf,g_shift,5,op)<5 ||
       CopyTickVolume(g_symbol,g_tf,g_shift,5,vl)<5) return;

    double cf = 0;
    for(int i=1;i<=4;i++) {
        double r = hi[i]-lo[i];
        cf += (r>0) ? (cl[i]-lo[i])/r : 0.5;
    }
    cf /= 4.0;

    double delta=0, tv=0;
    for(int i=1;i<=3;i++) {
        delta += (cl[i]>op[i]) ? (double)vl[i] : -(double)vl[i];
        tv    += (double)vl[i];
    }
    double s = (cf-0.5)*1.5 + (tv>0 ? delta/tv*0.3 : 0);

    g_m[9].prev  = g_m[9].score;
    g_m[9].score = MathMax(-1.0, MathMin(1.0, s));

    if(cf>0.75)      { g_m[9].thought=TH_FLOW[1]; g_m[9].ttype=BULL; }
    else if(cf>0.55) { g_m[9].thought=TH_FLOW[4]; g_m[9].ttype=BULL; }
    else if(cf>0.45) { g_m[9].thought=TH_FLOW[9]; g_m[9].ttype=NEUTRAL; }
    else if(cf>0.25) { g_m[9].thought=TH_FLOW[5]; g_m[9].ttype=BEAR; }
    else             { g_m[9].thought=TH_FLOW[2]; g_m[9].ttype=BEAR; }

    if(delta>0 && g_m[9].score<0) { g_m[9].thought=TH_FLOW[8]; g_m[9].ttype=WARNING; }
}

void Mod_TrapSense()
{
    double hi[], lo[], cl[], op[]; long vl[];
    ArraySetAsSeries(hi,true); ArraySetAsSeries(lo,true);
    ArraySetAsSeries(cl,true); ArraySetAsSeries(op,true); ArraySetAsSeries(vl,true);
    if(CopyHigh(g_symbol,g_tf,g_shift,12,hi)<12 || CopyLow(g_symbol,g_tf,g_shift,12,lo)<12 ||
       CopyClose(g_symbol,g_tf,g_shift,12,cl)<12 || CopyOpen(g_symbol,g_tf,g_shift,12,op)<12 ||
       CopyTickVolume(g_symbol,g_tf,g_shift,12,vl)<12) return;

    double ts=0; bool bTrap=false, beTrap=false;
    double rHi=0, rLo=9e9;
    for(int i=3;i<=10;i++) { if(hi[i]>rHi) rHi=hi[i]; if(lo[i]<rLo) rLo=lo[i]; }

    if(hi[2]>rHi && cl[2]<rHi) { bTrap=true;  ts+=0.5; }
    if(lo[2]<rLo && cl[2]>rLo) { beTrap=true; ts+=0.5; }

    double av = g_avgVol > 0 ? g_avgVol : 1.0;
    bool hvRev = ((double)vl[2]>av*2.0 && ((cl[2]>op[2])!=(cl[1]>op[1])));
    if(hvRev) ts+=0.4;

    double align=0;
    for(int i=0;i<7;i++) align+=MathAbs(g_m[i].score);
    align/=7.0;
    if(align > 0.7) ts+=0.2;

    g_b.trapScore = MathMin(1.0, ts);
    g_b.trap      = (g_b.trapScore > 0.4);

    g_m[10].prev  = g_m[10].score;
    double dir    = g_b.score;
    g_m[10].score = -dir * g_b.trapScore * 0.6;

    if(bTrap)         { g_m[10].thought=TH_TRAP[3]; g_m[10].ttype=BEAR; }
    else if(beTrap)   { g_m[10].thought=TH_TRAP[4]; g_m[10].ttype=BULL; }
    else if(align>0.7){ g_m[10].thought=TH_TRAP[0]; g_m[10].ttype=WARNING; }
    else if(g_b.trap) { g_m[10].thought=TH_TRAP[1]; g_m[10].ttype=WARNING; }
    else              { g_m[10].thought=TH_TRAP[9]; g_m[10].ttype=NEUTRAL; }
}

void Mod_Trend()
{
    double ef[], es[], et[];
    ArraySetAsSeries(ef,true); ArraySetAsSeries(es,true); ArraySetAsSeries(et,true);
    if(CopyBuffer(g_h_ef,0,g_shift,5,ef)<5 || CopyBuffer(g_h_es,0,g_shift,5,es)<5 ||
       CopyBuffer(g_h_et,0,g_shift,5,et)<5) return;

    double close = iClose(g_symbol,g_tf,g_shift+1);
    double slope = (es[1]-es[4])/(es[4]>0?es[4]:1.0)*10000.0;
    double s = 0;

    if(ef[1]>es[1]&&es[1]>et[1])      { s=0.7;  g_b.regime=TREND_UP;   g_m[11].thought=TH_TREND[0]; g_m[11].ttype=BULL; }
    else if(ef[1]<es[1]&&es[1]<et[1]) { s=-0.7; g_b.regime=TREND_DOWN; g_m[11].thought=TH_TREND[1]; g_m[11].ttype=BEAR; }
    else if(ef[1]>es[1])               { s=0.3;  g_m[11].thought=TH_TREND[6]; g_m[11].ttype=BULL; }
    else if(ef[1]<es[1])               { s=-0.3; g_m[11].thought=TH_TREND[7]; g_m[11].ttype=BEAR; }
    else                               { s=0.0;  g_b.regime=RANGING;    g_m[11].thought=TH_TREND[2]; g_m[11].ttype=NEUTRAL; }

    s += (close>et[1]) ? 0.2 : -0.2;
    s += MathMax(-0.1, MathMin(0.1, slope*0.05));

    double atr2[]; ArraySetAsSeries(atr2,true);
    if(CopyBuffer(g_h_atr,0,g_shift,20,atr2)>=20) {
        double atrNow=atr2[1];
        double atrOld=0; for(int i=10;i<20;i++) atrOld+=atr2[i]; atrOld/=10.0;
        if(atrNow<atrOld*0.5 && MathAbs(s)>0.4) { g_m[11].thought=TH_TREND[5]; g_m[11].ttype=WARNING; }
    }

    g_m[11].prev  = g_m[11].score;
    g_m[11].score = MathMax(-1.0, MathMin(1.0, s));
}

void Mod_MultiTF()
{
    double hf[], hs[], mf[];
    ArraySetAsSeries(hf,true); ArraySetAsSeries(hs,true); ArraySetAsSeries(mf,true);
    if(CopyBuffer(g_h_hf,0,g_shift_htf,3,hf)<3 || CopyBuffer(g_h_hs,0,g_shift_htf,3,hs)<3 ||
       CopyBuffer(g_h_mf,0,g_shift_mtf,3,mf)<3) return;

    double hc = iClose(g_symbol,g_htf,g_shift_htf+1);
    double mc = iClose(g_symbol,g_mtf,g_shift_mtf+1);
    bool hB  = (hf[1]>hs[1] && hc>hs[1]);
    bool hBr = (hf[1]<hs[1] && hc<hs[1]);
    bool mB  = (mc>mf[1]);
    bool mBr = (mc<mf[1]);

    double s = 0;
    if(hB)  s+=0.5; if(hBr) s-=0.5;
    if(mB)  s+=0.3; if(mBr) s-=0.3;
    if(hB&&mB)   s+=0.2;
    if(hBr&&mBr) s-=0.2;

    g_m[12].prev  = g_m[12].score;
    g_m[12].score = MathMax(-1.0, MathMin(1.0, s));

    if(hB&&mB)     { g_m[12].thought=TH_MTF[5]; g_m[12].ttype=BULL; }
    else if(hBr&&mBr){ g_m[12].thought=TH_MTF[6]; g_m[12].ttype=BEAR; }
    else if(hB&&mBr) { g_m[12].thought=TH_MTF[3]; g_m[12].ttype=NEUTRAL; }
    else if(hBr&&mB) { g_m[12].thought=TH_MTF[7]; g_m[12].ttype=WARNING; }
    else             { g_m[12].thought=TH_MTF[9]; g_m[12].ttype=NEUTRAL; }
}

void Mod_Liquidity()
{
    double hi[], lo[]; long vl[];
    ArraySetAsSeries(hi,true); ArraySetAsSeries(lo,true); ArraySetAsSeries(vl,true);
    if(CopyHigh(g_symbol,g_tf,g_shift,30,hi)<30 || CopyLow(g_symbol,g_tf,g_shift,30,lo)<30 ||
       CopyTickVolume(g_symbol,g_tf,g_shift,30,vl)<30) return;

    double atr = g_atr > 0 ? g_atr : 0.001;
    double cur = iClose(g_symbol,g_tf,g_shift+1);

    double sHi=hi[2]; int hiTests=0;
    for(int i=3;i<28;i++) if(MathAbs(hi[i]-sHi)<atr*0.5) hiTests++;

    double sLo=lo[2]; int loTests=0;
    for(int i=3;i<28;i++) if(MathAbs(lo[i]-sLo)<atr*0.5) loTests++;

    double distHi   = (sHi-cur)/atr;
    double distLo   = (cur-sLo)/atr;
    bool spreadWide = ((SymbolInfoDouble(g_symbol,SYMBOL_ASK)-SymbolInfoDouble(g_symbol,SYMBOL_BID))>g_atr*0.03);

    g_m[13].prev = g_m[13].score;
    double s = 0;

    if(spreadWide)                    { s=0;    g_m[13].thought=TH_LIQUIDITY[1]; g_m[13].ttype=WARNING; }
    else if(distHi<1.0&&hiTests>=2)  { s=-0.3; g_m[13].thought=TH_LIQUIDITY[2]; g_m[13].ttype=NEUTRAL; }
    else if(distLo<1.0&&loTests>=2)  { s=0.3;  g_m[13].thought=TH_LIQUIDITY[3]; g_m[13].ttype=NEUTRAL; }
    else if(distHi<2.0)              { s=-0.1; g_m[13].thought=TH_LIQUIDITY[5]; g_m[13].ttype=NEUTRAL; }
    else if(distLo<2.0)              { s=0.1;  g_m[13].thought=TH_LIQUIDITY[6]; g_m[13].ttype=NEUTRAL; }
    else                             { s=0;    g_m[13].thought=TH_LIQUIDITY[9]; g_m[13].ttype=NEUTRAL; }

    g_m[13].score = MathMax(-1.0, MathMin(1.0, s));
}

void Mod_Accumulation()
{
    double hi[], lo[], cl[]; long vl[];
    ArraySetAsSeries(hi,true); ArraySetAsSeries(lo,true);
    ArraySetAsSeries(cl,true); ArraySetAsSeries(vl,true);
    if(CopyHigh(g_symbol,g_tf,g_shift,30,hi)<30 || CopyLow(g_symbol,g_tf,g_shift,30,lo)<30 ||
       CopyClose(g_symbol,g_tf,g_shift,30,cl)<30 || CopyTickVolume(g_symbol,g_tf,g_shift,30,vl)<30) return;

    bool loRising  = (lo[1]>lo[5]&&lo[5]>lo[10]&&lo[10]>lo[20]);
    bool hiFlat    = (MathAbs(hi[1]-hi[5])<g_atr&&MathAbs(hi[5]-hi[10])<g_atr);

    double upVol=0, dnVol=0;
    for(int i=1;i<15;i++) {
        if(cl[i]>hi[i+1]) upVol+=(double)vl[i];
        else               dnVol+=(double)vl[i];
    }
    bool highVolUp = (upVol > dnVol * 1.3);

    g_m[14].prev = g_m[14].score;
    double s = 0;

    if(loRising&&hiFlat&&highVolUp){ s=0.7; g_m[14].thought=TH_ACCUM[8]; g_m[14].ttype=BULL; }
    else if(loRising&&hiFlat)      { s=0.5; g_m[14].thought=TH_ACCUM[6]; g_m[14].ttype=BULL; }
    else if(loRising)              { s=0.3; g_m[14].thought=TH_ACCUM[1]; g_m[14].ttype=BULL; }
    else if(highVolUp)             { s=0.2; g_m[14].thought=TH_ACCUM[7]; g_m[14].ttype=BULL; }
    else                           { s=0.0; g_m[14].thought=TH_ACCUM[0]; g_m[14].ttype=NEUTRAL; }

    g_m[14].score = MathMax(-1.0, MathMin(1.0, s));
}

void Mod_Distribution()
{
    double hi[], lo[], cl[]; long vl[];
    ArraySetAsSeries(hi,true); ArraySetAsSeries(lo,true);
    ArraySetAsSeries(cl,true); ArraySetAsSeries(vl,true);
    if(CopyHigh(g_symbol,g_tf,g_shift,30,hi)<30 || CopyLow(g_symbol,g_tf,g_shift,30,lo)<30 ||
       CopyClose(g_symbol,g_tf,g_shift,30,cl)<30 || CopyTickVolume(g_symbol,g_tf,g_shift,30,vl)<30) return;

    bool hiFalling = (hi[1]<hi[5]&&hi[5]<hi[10]);
    bool loFlat    = (MathAbs(lo[1]-lo[5])<g_atr&&MathAbs(lo[5]-lo[10])<g_atr);

    double upVol=0, dnVol=0;
    for(int i=1;i<15;i++) {
        if(cl[i]>cl[i+1]) upVol+=(double)vl[i];
        else               dnVol+=(double)vl[i];
    }
    bool highVolDn = (dnVol > upVol * 1.3);

    g_m[15].prev = g_m[15].score;
    double s = 0;

    if(hiFalling&&loFlat&&highVolDn){ s=-0.7; g_m[15].thought=TH_DIST[2]; g_m[15].ttype=BEAR; }
    else if(hiFalling&&loFlat)      { s=-0.5; g_m[15].thought=TH_DIST[3]; g_m[15].ttype=BEAR; }
    else if(hiFalling)              { s=-0.3; g_m[15].thought=TH_DIST[9]; g_m[15].ttype=BEAR; }
    else if(highVolDn)              { s=-0.2; g_m[15].thought=TH_DIST[1]; g_m[15].ttype=BEAR; }
    else                            { s=0.0;  g_m[15].thought=TH_DIST[0]; g_m[15].ttype=NEUTRAL; }

    g_m[15].score = MathMax(-1.0, MathMin(1.0, s));
}

void Mod_Breakout()
{
    double hi[], lo[], cl[]; long vl[];
    ArraySetAsSeries(hi,true); ArraySetAsSeries(lo,true);
    ArraySetAsSeries(cl,true); ArraySetAsSeries(vl,true);
    if(CopyHigh(g_symbol,g_tf,g_shift,20,hi)<20 || CopyLow(g_symbol,g_tf,g_shift,20,lo)<20 ||
       CopyClose(g_symbol,g_tf,g_shift,20,cl)<20 || CopyTickVolume(g_symbol,g_tf,g_shift,20,vl)<20) return;

    double rngHi=hi[2], rngLo=lo[2];
    for(int i=3;i<18;i++) { if(hi[i]>rngHi) rngHi=hi[i]; if(lo[i]<rngLo) rngLo=lo[i]; }

    bool brkUp    = (cl[1]>rngHi);
    bool brkDn    = (cl[1]<rngLo);
    bool hiVol    = ((double)vl[1]>g_avgVol*1.5);
    bool retestUp = (brkUp && MathAbs(cl[1]-rngHi)<g_atr*0.5);

    g_m[16].prev = g_m[16].score;
    double s = 0;

    if(brkUp&&hiVol&&!retestUp){ s=0.8;  g_m[16].thought=TH_BREAK[3]; g_m[16].ttype=BULL; }
    else if(brkUp&&!hiVol)     { s=0.2;  g_m[16].thought=TH_BREAK[2]; g_m[16].ttype=WARNING; }
    else if(retestUp&&hiVol)   { s=0.6;  g_m[16].thought=TH_BREAK[5]; g_m[16].ttype=BULL; }
    else if(brkDn&&hiVol)      { s=-0.8; g_m[16].thought=TH_BREAK[8]; g_m[16].ttype=BEAR; }
    else if(brkDn&&!hiVol)     { s=-0.2; g_m[16].thought=TH_BREAK[1]; g_m[16].ttype=WARNING; }
    else                       { s=0.0;  g_m[16].thought=TH_BREAK[9]; g_m[16].ttype=NEUTRAL; }

    g_m[16].score = MathMax(-1.0, MathMin(1.0, s));
}

void Mod_DevilAdvocate()
{
    double mainScore = g_b.score;
    double conf      = MathAbs(mainScore);
    g_m[17].prev = g_m[17].score;

    if(conf > 0.7)      { g_m[17].score=-mainScore*0.2; g_m[17].thought=TH_DEVIL[0]; g_m[17].ttype=WARNING; }
    else if(conf > 0.5) { g_m[17].score=-mainScore*0.1; g_m[17].thought=TH_DEVIL[3]; g_m[17].ttype=WARNING; }
    else                { g_m[17].score=0;              g_m[17].thought=TH_DEVIL[9]; g_m[17].ttype=NEUTRAL; }
}

void Mod_Reversals()
{
    double hi[], lo[], cl[], op[]; long vl[];
    ArraySetAsSeries(hi,true); ArraySetAsSeries(lo,true);
    ArraySetAsSeries(cl,true); ArraySetAsSeries(op,true); ArraySetAsSeries(vl,true);
    if(CopyHigh(g_symbol,g_tf,g_shift,10,hi)<10 || CopyLow(g_symbol,g_tf,g_shift,10,lo)<10 ||
       CopyClose(g_symbol,g_tf,g_shift,10,cl)<10 || CopyOpen(g_symbol,g_tf,g_shift,10,op)<10 ||
       CopyTickVolume(g_symbol,g_tf,g_shift,10,vl)<10) return;

    double atr = g_atr > 0 ? g_atr : 0.001;
    bool shrinkingHi = (hi[1]-hi[2]<atr*0.15 && hi[2]-hi[3]>atr*0.3 && cl[1]>cl[3]);
    bool shrinkingLo = (lo[3]-lo[2]>atr*0.3  && lo[2]-lo[1]<atr*0.15 && cl[1]<cl[3]);

    double r1 = hi[1]-lo[1];
    bool climaxUp = ((double)vl[1]>g_avgVol*2.5 && r1>atr*1.5 && (cl[1]-lo[1])<r1*0.4);
    bool climaxDn = ((double)vl[1]>g_avgVol*2.5 && r1>atr*1.5 && (hi[1]-cl[1])<r1*0.4);

    bool charChangeUp = (!(cl[2]>op[2]) && !(cl[3]>op[3]) && (cl[1]>op[1]) && MathAbs(cl[1]-op[1])>atr*0.5);
    bool charChangeDn = ((cl[2]>op[2])  && (cl[3]>op[3])  && !(cl[1]>op[1]) && MathAbs(cl[1]-op[1])>atr*0.5);

    g_m[18].prev = g_m[18].score;
    double s = 0;

    if(climaxUp)      { s=-0.5; g_m[18].thought=TH_REVERSAL[1]; g_m[18].ttype=WARNING; }
    else if(climaxDn) { s=0.5;  g_m[18].thought=TH_REVERSAL[7]; g_m[18].ttype=WARNING; }
    else if(shrinkingHi){ s=-0.3; g_m[18].thought=TH_REVERSAL[3]; g_m[18].ttype=WARNING; }
    else if(shrinkingLo){ s=0.3;  g_m[18].thought=TH_REVERSAL[0]; g_m[18].ttype=WARNING; }
    else if(charChangeUp){ s=0.4; g_m[18].thought=TH_REVERSAL[6]; g_m[18].ttype=BULL; }
    else if(charChangeDn){ s=-0.4;g_m[18].thought=TH_REVERSAL[9]; g_m[18].ttype=BEAR; }
    else               { s=0;    g_m[18].thought=TH_REVERSAL[9]; g_m[18].ttype=NEUTRAL; }

    g_m[18].score = MathMax(-1.0, MathMin(1.0, s));
}

void Mod_PatternFail()
{
    // Compare pattern direction against dominant trend — patterns against trend fail more often
    double patScore   = g_m[8].score;
    double trendScore = g_m[11].score;
    double mtfScore   = g_m[12].score;
    bool contra = (patScore * trendScore < 0) && (patScore * mtfScore < 0);  // pattern vs both trend layers

    g_m[19].prev = g_m[19].score;
    double s = 0;

    if(contra && MathAbs(patScore) >= 0.6)
        { s=-patScore*0.7; g_m[19].thought=TH_PATFAIL[2]; g_m[19].ttype=WARNING; }
    else if(contra && MathAbs(patScore) >= 0.3)
        { s=-patScore*0.4; g_m[19].thought=TH_PATFAIL[0]; g_m[19].ttype=WARNING; }
    else if(patScore*trendScore < 0 && MathAbs(patScore) >= 0.4)
        { s=-patScore*0.3; g_m[19].thought=TH_PATFAIL[7]; g_m[19].ttype=WARNING; }
    else
        { s=0;             g_m[19].thought=TH_PATFAIL[9]; g_m[19].ttype=NEUTRAL; }

    g_m[19].score = MathMax(-1.0, MathMin(1.0, s));
}

void Mod_SupplyDemand()
{
    double hi[], lo[], cl[], op[]; long vl[];
    ArraySetAsSeries(hi,true); ArraySetAsSeries(lo,true);
    ArraySetAsSeries(cl,true); ArraySetAsSeries(op,true); ArraySetAsSeries(vl,true);
    if(CopyHigh(g_symbol,g_tf,g_shift,25,hi)<25 || CopyLow(g_symbol,g_tf,g_shift,25,lo)<25 ||
       CopyClose(g_symbol,g_tf,g_shift,25,cl)<25 || CopyOpen(g_symbol,g_tf,g_shift,25,op)<25 ||
       CopyTickVolume(g_symbol,g_tf,g_shift,25,vl)<25) return;

    double demandVol=0, supplyVol=0;
    for(int i=1;i<20;i++) {
        if(cl[i]>op[i]) demandVol+=(double)vl[i];
        else             supplyVol+=(double)vl[i];
    }
    double total = demandVol + supplyVol;
    double imbal = (total>0) ? (demandVol-supplyVol)/total : 0;

    bool demandShock = ((double)vl[1]>g_avgVol*3.0 && cl[1]>op[1]);
    bool supplyShock = ((double)vl[1]>g_avgVol*3.0 && cl[1]<op[1]);

    g_m[20].prev = g_m[20].score;
    double s = imbal * 0.8;

    if(demandShock)    { s=0.9;  g_m[20].thought=TH_SD[3]; g_m[20].ttype=BULL; }
    else if(supplyShock){ s=-0.9; g_m[20].thought=TH_SD[4]; g_m[20].ttype=BEAR; }
    else if(imbal>0.3) { s=0.6;  g_m[20].thought=TH_SD[0]; g_m[20].ttype=BULL; }
    else if(imbal>0.1) { s=0.3;  g_m[20].thought=TH_SD[7]; g_m[20].ttype=BULL; }
    else if(imbal<-0.3){ s=-0.6; g_m[20].thought=TH_SD[1]; g_m[20].ttype=BEAR; }
    else if(imbal<-0.1){ s=-0.3; g_m[20].thought=TH_SD[8]; g_m[20].ttype=BEAR; }
    else               { s=0.0;  g_m[20].thought=TH_SD[2]; g_m[20].ttype=NEUTRAL; }

    if(MathAbs(imbal)>0.2 && g_m[5].score*s<0)
        { g_m[20].thought=TH_SD[9]; g_m[20].ttype=WARNING; }

    g_m[20].score = MathMax(-1.0, MathMin(1.0, s));
}

//=======================================================================
// JSON BUILDER
//=======================================================================
string EscapeJson(string s)
{
    StringReplace(s, "\\", "\\\\");
    StringReplace(s, "\"", "\\\"");
    StringReplace(s, "\n", "\\n");
    StringReplace(s, "\r", "\\r");
    return s;
}

string RegimeStr()
{
    switch(g_b.regime) {
        case TREND_UP:   return "EMA_SHORT_ABOVE_LONG";
        case TREND_DOWN: return "EMA_LONG_ABOVE_SHORT";
        case VOLATILE:   return "ATR_ELEVATED";
        default:         return "EMA_MIXED";
    }
}

string EtypeStr(ETYPE t)
{
    switch(t) {
        case BULL:    return "BULL";
        case BEAR:    return "BEAR";
        case WARNING: return "WARNING";
        default:      return "NEUTRAL";
    }
}

string BuildBrainPayload(string symbol)
{
    int    digits = (int)SymbolInfoInteger(symbol, SYMBOL_DIGITS);
    double bid, ask, spread;
    bool   isPretend = (g_pretendDT > 0 && g_shift >= 0);
    if(isPretend) {
        bid    = iClose(symbol, g_tf, g_shift + 1);
        ask    = bid;
        spread = 0;
    } else {
        bid    = SymbolInfoDouble(symbol, SYMBOL_BID);
        ask    = SymbolInfoDouble(symbol, SYMBOL_ASK);
        spread = ask - bid;
    }
    string timeStr = isPretend
        ? TimeToString(g_pretendDT, TIME_DATE|TIME_SECONDS)
        : TimeToString(TimeCurrent(), TIME_DATE|TIME_SECONDS);

    string j = "{\n";
    j += "  \"symbol\": \""      + symbol + "\",\n";
    j += "  \"server_time\": \"" + timeStr + "\",\n";
    if(isPretend)
        j += "  \"pretend_mode\": true,\n";
    j += "  \"price\": {\n";
    j += "    \"bid\":    " + DoubleToString(bid,    digits) + ",\n";
    j += "    \"ask\":    " + DoubleToString(ask,    digits) + ",\n";
    j += "    \"spread\": " + DoubleToString(spread, digits) + "\n";
    j += "  },\n";
    j += "  \"brain\": {\n";
    j += "    \"score\":           " + DoubleToString(g_b.score,     4) + ",\n";
    j += "    \"confidence\":      " + DoubleToString(g_b.conf,      4) + ",\n";
    j += "    \"conflict\":        " + DoubleToString(g_b.conflict,  4) + ",\n";
    j += "    \"regime\":          \"" + RegimeStr() + "\",\n";
    j += "    \"trap_active\":     " + (g_b.trap ? "true" : "false") + ",\n";
    j += "    \"trap_score\":      " + DoubleToString(g_b.trapScore, 4) + ",\n";
    j += "    \"atr\":             " + DoubleToString(g_atr,         digits) + ",\n";
    j += "    \"dominant_thought\": \"" + EscapeJson(g_b.dominant) + "\"\n";
    j += "  },\n";
    j += "  \"modules\": [\n";

    for(int i = 0; i < NMODS; i++) {
        j += "    {\n";
        j += "      \"id\":      " + IntegerToString(i) + ",\n";
        j += "      \"name\":    \"" + g_m[i].name + "\",\n";
        j += "      \"score\":   " + DoubleToString(g_m[i].score,  4) + ",\n";
        j += "      \"weight\":  " + DoubleToString(g_m[i].weight, 2) + ",\n";
        j += "      \"etype\":   \"" + EtypeStr(g_m[i].ttype) + "\",\n";
        j += "      \"thought\": \"" + EscapeJson(g_m[i].thought) + "\"\n";
        j += "    }" + (i < NMODS-1 ? "," : "") + "\n";
    }

    j += "  ]\n";
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
string ExtractJsonValue(string json, string key)
{
    string search_key = "\"" + key + "\"";
    int pos = StringFind(json, search_key);
    if(pos < 0) return "";

    int colon_pos = StringFind(json, ":", pos);
    if(colon_pos < 0) return "";

    int value_start = colon_pos + 1;
    while(value_start < StringLen(json) &&
          (StringGetCharacter(json,value_start)==' ' ||
           StringGetCharacter(json,value_start)=='\t'||
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
//+------------------------------------------------------------------+
