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
   "Price just moved. Something shifted.",
   "That was a small tick but it felt heavy.",
   "That move had no follow-through. Suspicious.",
   "The last five ticks were all sellers. No buyers stepping in.",
   "A single large print just crossed. Someone committed.",
   "Price is hovering. This stillness is not peace. It is tension.",
   "The pace of ticks just slowed. Absorption happening.",
   "One large print at the offer. Initiative buying.",
   "Every uptick is being sold immediately.",
   "Every downtick is being bought immediately. Range locked."
};
string TH_MEMORY[10] = {
   "This price level — I have been here before.",
   "Last time price came to this zone it reversed hard.",
   "I remember when this same setup failed. I must not be overconfident.",
   "Price consolidated at this level for hours before breaking. That memory is strong.",
   "The last time buyers were this aggressive at this hour it was a trap.",
   "A swing low was made here six sessions back. It is an anchor point.",
   "The last three times momentum was this strong it exhausted within five bars.",
   "The market sold off aggressively from near here. The sellers who lost are waiting.",
   "A failed breakout happened at this exact zone. The trapped longs are overhead.",
   "There was a stop run above last session high. Same mechanics could repeat."
};
string TH_ANTICIPATE[10] = {
   "Something is about to give. The pressure is building.",
   "A big move is coming. I do not know which way.",
   "This consolidation cannot last much longer. The coil is tight.",
   "If they break this level it will be fast and ugly.",
   "I feel the buyers gathering. They are not here yet but they are coming.",
   "The sellers are losing conviction. A squeeze is possible.",
   "There is a vacuum above. If price gets there nothing will stop it quickly.",
   "I sense a trap being set for the obvious side.",
   "If volume picks up on this next push I will trust it.",
   "Something is about to be revealed by whoever moves next."
};
string TH_UNCERTAIN[10] = {
   "I do not know. That is honest.",
   "Both directions are possible right now. I must sit with that.",
   "The setup looks perfect. That alone makes me cautious.",
   "My certainty is highest right before I am most wrong.",
   "There are a hundred reasons to go one way. That is sometimes the wrong way.",
   "I am biased from the last move. I must reset.",
   "The market is under no obligation to do what it usually does.",
   "I see a pattern but patterns are just the past trying to rhyme with now.",
   "I must stay fluid. The moment I become rigid I am finished.",
   "Every signal I have points in opposite directions. The market is unresolved."
};
string TH_MOMENTUM[10] = {
   "The momentum just tilted. Something changed.",
   "Buyers are accelerating. This is real buying.",
   "Sellers are losing steam. Each push down goes a little less far.",
   "The velocity of this move is increasing. It wants to run.",
   "Momentum is stalling at resistance. No surprise.",
   "The upthrust was fast but thin. That is momentum without substance.",
   "A slow grind up is more powerful than a fast spike. Slow means accumulation.",
   "Momentum reversed mid-bar. Something happened.",
   "Momentum is diverging from price. This will resolve soon and it will matter.",
   "The momentum is telling a different story than the price. One of them is lying."
};
string TH_VOLUME[10] = {
   "Volume just spiked and price barely moved. Absorption.",
   "Low volume on this pullback. The trend is intact.",
   "Volume is confirming the breakout. I trust this one.",
   "Volume dried up at the highs. Distribution possible.",
   "The buying volume is increasing on each push. Trend is strengthening.",
   "Selling volume is decreasing on each push down. Sellers are tired.",
   "A climactic volume bar just printed. A turn is near.",
   "Volume is above average for this time of day. Big players are active.",
   "Volume is well below average. This is just noise probably.",
   "Volume is telling me this move is real. Price just has not caught up yet."
};
string TH_SESSION[10] = {
   "This is the opening range. Everything is still being established.",
   "The first hour is almost over. The range is set.",
   "It is the lunch hour. Volume dries up. Be cautious of false moves.",
   "The afternoon session is beginning. Big players returning.",
   "The last thirty minutes of the session. Positions being squared.",
   "Pre-market established a key level. The regular session will test it.",
   "The New York open is the most volatile moment. I must not be caught leaning.",
   "End of week. No one wants to hold over the weekend.",
   "Three hours in and price has not moved much. Anticipation building.",
   "Time itself is a signal. When something should have happened and has not I pay attention."
};
string TH_SR[10] = {
   "This level is holding. Someone is defending it.",
   "This level just cracked. The defenders gave up.",
   "Price is testing a level for the third time. Third tests often fail.",
   "Support became resistance. The old buyers are now sellers.",
   "The round number above is a target. Round numbers attract price.",
   "A trendline is being tested. It has held twice. Third test is critical.",
   "Former resistance became support on the retest. Classic behavior.",
   "There are stops clustered just above that high. Price can smell them.",
   "Price is in a zone not at a line. Zones are messier but more powerful.",
   "I sense the level is weakening even though it has not yet broken."
};
string TH_PATTERN[10] = {
   "That looks like a bull flag forming.",
   "I see a double top. Two equal highs with lower volume on the second.",
   "That wick tells a story. Price went there and was violently rejected.",
   "Three rising lows. The buyers are not giving up.",
   "A higher high and higher low. Uptrend structure intact.",
   "A lower high and lower low. Downtrend structure intact.",
   "A wedge is forming. Compression before expansion.",
   "An inside bar. The market is deciding. I must be ready for either direction.",
   "A pin bar at resistance. Sellers showing their hand.",
   "The pattern is forming but it is not complete. Patience required."
};
string TH_FLOW[10] = {
   "There is a large resting order at that price. It is acting as a ceiling.",
   "Aggressive market orders are hitting the ask. Real urgency buying.",
   "Aggressive market orders are hitting the bid. Real urgency selling.",
   "The order book is thin above. A small push could move price dramatically.",
   "Passive buyers are absorbing every sell. The price is going up.",
   "Passive sellers are absorbing every buy. The price is going down.",
   "I see trapped longs overhead. If price revisits that level they will exit.",
   "I see trapped shorts below. If price revisits that level they will cover.",
   "The cumulative delta is diverging from price. A warning signal.",
   "I read the order flow but order flow can be spoofed. I stay skeptical."
};
string TH_TRAP[10] = {
   "That breakout looked too clean. Breakouts that look too clean are often traps.",
   "Everyone sees the same setup. When everyone sees it it usually fails.",
   "A stop run just happened. Price went just far enough then reversed.",
   "The move above the high was brief and closed back below it. Bull trap.",
   "The move below the low was brief and closed back above it. Bear trap.",
   "The pattern is textbook perfect. Textbook perfect patterns are bait.",
   "Retail is piling in on this breakout. That is often a signal to fade it.",
   "I sense the market makers are on the other side of the obvious trade.",
   "The easy entry is a trap. The hard entry against the obvious direction is real.",
   "I have been trapped before. That experience is part of how I think now."
};
string TH_TREND[10] = {
   "The trend is up. I should not be looking for reasons to sell.",
   "The trend is down. I should not be looking for reasons to buy.",
   "There is no trend right now. I am in a range. Different rules apply.",
   "The trend changed today. I am recalibrating.",
   "Counter-trend moves in a strong trend are opportunities not reversals.",
   "The trend is old and tired. It may be nearing its end.",
   "The trend is young and strong. It will likely continue.",
   "The pullback in this uptrend is deep. Maybe more than a pullback.",
   "The trend line failed on the second test. The trend is challenged.",
   "The trend is my environment. I swim with it or I am pushed back."
};
string TH_MTF[10] = {
   "The daily chart says one thing. The five-minute chart says another.",
   "I must honor the higher timeframe structure first.",
   "On the one-minute chart this looks like a reversal. On the hourly it is noise.",
   "The daily trend is intact even though the hourly looks terrible.",
   "The hourly gave a buy signal inside a daily downtrend. I take it smaller.",
   "All three timeframes are aligned up. The signal is strong.",
   "All three timeframes are aligned down. The signal is strong.",
   "The timeframes are conflicting. I wait for them to resolve.",
   "I zoom out when I feel confused. The higher timeframe almost always clarifies.",
   "The lower timeframe is leading the higher timeframe. A shift is coming."
};
string TH_LIQUIDITY[10] = {
   "Liquidity is thin right now. I must be careful about size.",
   "The spread widened suddenly. Liquidity providers stepped back.",
   "Liquidity pools above the highs. Price will seek it.",
   "Liquidity pools below the lows. Price will seek it.",
   "A large order would move this market significantly right now.",
   "I sense a liquidity vacuum above. If price gets there the move will be extreme.",
   "I sense a liquidity vacuum below. If price gets there the fall will be fast.",
   "The market is using old highs as a liquidity reference. Stops sit there.",
   "Liquidity returned after the news. The spread normalized.",
   "The market is its own best liquidity provider over time. Extremes attract participation."
};
string TH_ACCUM[10] = {
   "I sense accumulation happening quietly.",
   "Price is holding above a key level despite multiple tests. Buyers accumulating.",
   "Volume is rising but price is not. Absorption accumulation.",
   "The dips are being bought immediately. Aggressive accumulation.",
   "The range is narrowing at the highs. Markup phase approaching.",
   "Price is being held down while positions are built. The range is artificial.",
   "The lows of the range are getting higher. Accumulation pushing the floor up.",
   "Low volume pullbacks and high volume pushes. Classic accumulation signature.",
   "The effort versus result is telling. Much effort to push down little result.",
   "Accumulation complete. The next move should be up."
};
string TH_DIST[10] = {
   "I sense distribution happening at these highs.",
   "Every rally attempt is being sold into. Classic distribution.",
   "Volume is high on the down bars and low on the up bars. Distribution signature.",
   "The highs are getting lower. Distribution is pushing the ceiling down.",
   "I sense a large seller is active but careful. They do not want to show their hand.",
   "Price pops above resistance then falls back inside the range. Upthrust distribution.",
   "Smart money is unloading to retail who is buying the breakout that is not real.",
   "Low volume rallies and high volume drops. Classic distribution signature.",
   "Much effort to push up little result. Sellers absorbing every buy.",
   "The top is in. The first lower high confirmed it."
};
string TH_BREAK[10] = {
   "This breakout is real. Volume confirms it.",
   "This breakout is false. Volume does not confirm it.",
   "The breakout happened on thin volume. High risk of failure.",
   "The breakout happened on massive volume. Trust it.",
   "A retest of the breakout level is happening. This is healthy.",
   "The retest held. The breakout is confirmed.",
   "The retest failed. The breakout was false.",
   "Price broke out but immediately reversed. Bull trap complete.",
   "We broke above a level that held for three sessions. This is meaningful.",
   "The third attempt at this breakout is happening. These often work."
};
string TH_DEVIL[10] = {
   "I am bullish. Let me now make the strongest possible bear case.",
   "What would I think if I had no position? How would I see this then?",
   "Why might I be wrong? Let me actually enumerate the reasons.",
   "What does the market know that I do not?",
   "I am seeing what I want to see. Let me try to see only what is there.",
   "Is this a genuine pattern or am I pattern-matching onto noise?",
   "I am excited. Excitement is often a warning. What am I missing?",
   "I built a strong narrative. Strong narratives can be dangerous.",
   "The best argument against my position is the one I just talked myself out of.",
   "The obvious trade is often the expensive trade."
};
string TH_REVERSAL[10] = {
   "I sense a reversal coming. The character of the tape is changing.",
   "The up bars are losing ground. The down bars are getting larger.",
   "A climax bar just printed. Emotion at its peak often marks the end.",
   "Price made a new high but only by a single tick. Exhaustion.",
   "The pullback from that high was deep. Not typical of a healthy trend.",
   "The reversal pattern completed. I wait for confirmation.",
   "Confirmation came. The reversal is real.",
   "I sense a V-bottom forming. Sharp drop followed by sharp recovery.",
   "The key level broke. The reversal is confirmed by structure.",
   "Reversals are easier to see in hindsight. In the moment they look like retracements."
};
string TH_PATFAIL[10] = {
   "The pattern set up perfectly and then failed. What does that tell me?",
   "When a textbook pattern fails it generates the strongest move in the other direction.",
   "The failed bull flag becomes a bear flag. Trapped longs become fuel for the drop.",
   "The failed breakout becomes a breakdown. Trapped longs accelerate the decline.",
   "The failed breakdown becomes a breakout. Trapped shorts accelerate the rally.",
   "Pattern failure is not noise. It is a signal.",
   "I must be quick to recognize pattern failure and pivot.",
   "A pattern failure at a major level is more significant than in mid range.",
   "I trade pattern failures as aggressively as pattern completions.",
   "Patterns are tendencies. Not laws. Never laws."
};
string TH_SD[10] = {
   "Demand is overwhelming supply right now. Price must move up to find sellers.",
   "Supply is overwhelming demand right now. Price must move down to find buyers.",
   "Supply and demand are roughly equal. Price will stay in range.",
   "A sudden demand shock entered. Someone needs this asset at any price.",
   "A sudden supply shock entered. Someone needs out at any price.",
   "The demand zone held on three tests. Buyers committed to defend it.",
   "The supply zone held on three tests. Sellers committed to defend it.",
   "Fresh demand entered at this price. Not a retest. New buyers.",
   "Fresh supply entered at this price. Not a retest. New sellers.",
   "The imbalance is building. When it unwinds it will be sharp."
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
    if(CopyBuffer(g_h_atr, 0, 0, 5, ab) >= 3) g_atr = ab[1];

    // First pass
    Mod_TickSense();
    Mod_Memory();
    Mod_Anticipation();
    Mod_Volume();
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

    // Pre-synthesis score for context-sensitive modules
    double ts = 0, tw = 0;
    for(int i = 0; i < 17; i++) { ts += g_m[i].score * g_m[i].weight; tw += g_m[i].weight; }
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
    double ts = 0, tw = 0, var = 0;
    for(int i = 0; i < 21; i++) { ts += g_m[i].score * g_m[i].weight; tw += g_m[i].weight; }
    g_b.score    = (tw > 0) ? ts / tw : 0;
    g_b.conf     = MathAbs(g_b.score);

    for(int i = 0; i < 21; i++) var += MathPow(g_m[i].score - g_b.score, 2);
    g_b.conflict  = MathSqrt(var / 21.0);
    g_b.conf     *= (1.0 - g_b.conflict * 0.4);

    double mx = 0; int dom = 21;
    for(int i = 0; i < 21; i++) {
        double inf = MathAbs(g_m[i].score) * g_m[i].weight;
        if(inf > mx) { mx = inf; dom = i; }
    }
    g_b.dominant = g_m[dom].thought;

    // Module 21 — synthesis state (no directional signal)
    g_m[21].score = 0;
    if(g_b.score > 0.1)
        g_m[21].thought = "SYNTHESIS: Lean is BULLISH at " + IntegerToString((int)(g_b.conf * 100)) + "% confidence.";
    else if(g_b.score < -0.1)
        g_m[21].thought = "SYNTHESIS: Lean is BEARISH at " + IntegerToString((int)(g_b.conf * 100)) + "% confidence.";
    else if(g_b.conf > 0.35)
        g_m[21].thought = "SYNTHESIS: Approaching threshold at " + IntegerToString((int)(g_b.conf * 100)) + "%. Watching.";
    else
        g_m[21].thought = "SYNTHESIS: No edge detected. The market is unresolved. I wait.";
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
    if(CopyHigh(g_symbol,g_tf,0,6,hi)<6 || CopyLow(g_symbol,g_tf,0,6,lo)<6 ||
       CopyClose(g_symbol,g_tf,0,6,cl)<6 || CopyOpen(g_symbol,g_tf,0,6,op)<6 ||
       CopyTickVolume(g_symbol,g_tf,0,6,vl)<6) return;

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
    if(CopyHigh(g_symbol,g_tf,0,60,hi)<60 || CopyLow(g_symbol,g_tf,0,60,lo)<60 ||
       CopyClose(g_symbol,g_tf,0,60,cl)<60 || CopyTickVolume(g_symbol,g_tf,0,60,vl)<60) return;

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
    if(CopyHigh(g_symbol,g_tf,0,20,hi)<20 || CopyLow(g_symbol,g_tf,0,20,lo)<20 ||
       CopyTickVolume(g_symbol,g_tf,0,20,vl)<20) return;

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
    if(CopyBuffer(g_h_rsi,0,0,6,rsi)<6 || CopyBuffer(g_h_macd,0,0,6,mc)<6 ||
       CopyBuffer(g_h_macd,1,0,6,ms)<6) return;

    double s = (rsi[1]-50.0)/50.0*0.5;
    s += (mc[1]>ms[1]) ? 0.2 : -0.2;
    s += (mc[1]>0) ? 0.1 : -0.1;

    double c1 = iClose(g_symbol,g_tf,1);
    double c4 = iClose(g_symbol,g_tf,4);
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
    if(CopyTickVolume(g_symbol,g_tf,0,50,vl)<25) return;

    double avg = 0;
    for(int i = 1; i <= 20; i++) avg += (double)vl[i];
    avg /= 20.0; g_avgVol = avg;

    double cur   = (double)vl[1];
    double ratio = cur / (avg > 0 ? avg : 1.0);
    bool bull = (iClose(g_symbol,g_tf,1) > iOpen(g_symbol,g_tf,1));

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
    else                     { g_m[5].thought=TH_VOLUME[7]; g_m[5].ttype=NEUTRAL; }

    if(ratio > 3.0) { g_m[5].thought=TH_VOLUME[6]; g_m[5].ttype=WARNING; }
    g_m[5].score = MathMax(-1.0, MathMin(1.0, s));
}

void Mod_Session()
{
    MqlDateTime dt; TimeCurrent(dt);
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
    if(CopyHigh(g_symbol,g_tf,0,50,hi)<50 || CopyLow(g_symbol,g_tf,0,50,lo)<50 ||
       CopyClose(g_symbol,g_tf,0,50,cl)<50) return;

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
    if(CopyOpen(g_symbol,g_tf,0,8,op)<8 || CopyHigh(g_symbol,g_tf,0,8,hi)<8 ||
       CopyLow(g_symbol,g_tf,0,8,lo)<8  || CopyClose(g_symbol,g_tf,0,8,cl)<8) return;

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
    if(CopyHigh(g_symbol,g_tf,0,5,hi)<5 || CopyLow(g_symbol,g_tf,0,5,lo)<5 ||
       CopyClose(g_symbol,g_tf,0,5,cl)<5 || CopyOpen(g_symbol,g_tf,0,5,op)<5 ||
       CopyTickVolume(g_symbol,g_tf,0,5,vl)<5) return;

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
    if(CopyHigh(g_symbol,g_tf,0,12,hi)<12 || CopyLow(g_symbol,g_tf,0,12,lo)<12 ||
       CopyClose(g_symbol,g_tf,0,12,cl)<12 || CopyOpen(g_symbol,g_tf,0,12,op)<12 ||
       CopyTickVolume(g_symbol,g_tf,0,12,vl)<12) return;

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
    if(CopyBuffer(g_h_ef,0,0,5,ef)<5 || CopyBuffer(g_h_es,0,0,5,es)<5 ||
       CopyBuffer(g_h_et,0,0,5,et)<5) return;

    double close = iClose(g_symbol,g_tf,1);
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
    if(CopyBuffer(g_h_atr,0,0,20,atr2)>=20) {
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
    if(CopyBuffer(g_h_hf,0,0,3,hf)<3 || CopyBuffer(g_h_hs,0,0,3,hs)<3 ||
       CopyBuffer(g_h_mf,0,0,3,mf)<3) return;

    double hc = iClose(g_symbol,g_htf,1);
    double mc = iClose(g_symbol,g_mtf,1);
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
    if(CopyHigh(g_symbol,g_tf,0,30,hi)<30 || CopyLow(g_symbol,g_tf,0,30,lo)<30 ||
       CopyTickVolume(g_symbol,g_tf,0,30,vl)<30) return;

    double atr = g_atr > 0 ? g_atr : 0.001;
    double cur = iClose(g_symbol,g_tf,1);

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
    if(CopyHigh(g_symbol,g_tf,0,30,hi)<30 || CopyLow(g_symbol,g_tf,0,30,lo)<30 ||
       CopyClose(g_symbol,g_tf,0,30,cl)<30 || CopyTickVolume(g_symbol,g_tf,0,30,vl)<30) return;

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
    if(CopyHigh(g_symbol,g_tf,0,30,hi)<30 || CopyLow(g_symbol,g_tf,0,30,lo)<30 ||
       CopyClose(g_symbol,g_tf,0,30,cl)<30 || CopyTickVolume(g_symbol,g_tf,0,30,vl)<30) return;

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
    if(CopyHigh(g_symbol,g_tf,0,20,hi)<20 || CopyLow(g_symbol,g_tf,0,20,lo)<20 ||
       CopyClose(g_symbol,g_tf,0,20,cl)<20 || CopyTickVolume(g_symbol,g_tf,0,20,vl)<20) return;

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
    if(CopyHigh(g_symbol,g_tf,0,10,hi)<10 || CopyLow(g_symbol,g_tf,0,10,lo)<10 ||
       CopyClose(g_symbol,g_tf,0,10,cl)<10 || CopyOpen(g_symbol,g_tf,0,10,op)<10 ||
       CopyTickVolume(g_symbol,g_tf,0,10,vl)<10) return;

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
    double prevPat = g_m[8].prev;
    double curPat  = g_m[8].score;
    double delta   = curPat - prevPat;

    g_m[19].prev = g_m[19].score;
    double s = 0;

    if(prevPat>0.5 && curPat<0)       { s=-0.6; g_m[19].thought=TH_PATFAIL[2]; g_m[19].ttype=BEAR; }
    else if(prevPat<-0.5 && curPat>0) { s=0.6;  g_m[19].thought=TH_PATFAIL[4]; g_m[19].ttype=BULL; }
    else if(MathAbs(delta)>0.6)       { s=-delta*0.5; g_m[19].thought=TH_PATFAIL[1]; g_m[19].ttype=WARNING; }
    else                              { s=0;    g_m[19].thought=TH_PATFAIL[5]; g_m[19].ttype=NEUTRAL; }

    g_m[19].score = MathMax(-1.0, MathMin(1.0, s));
}

void Mod_SupplyDemand()
{
    double hi[], lo[], cl[], op[]; long vl[];
    ArraySetAsSeries(hi,true); ArraySetAsSeries(lo,true);
    ArraySetAsSeries(cl,true); ArraySetAsSeries(op,true); ArraySetAsSeries(vl,true);
    if(CopyHigh(g_symbol,g_tf,0,25,hi)<25 || CopyLow(g_symbol,g_tf,0,25,lo)<25 ||
       CopyClose(g_symbol,g_tf,0,25,cl)<25 || CopyOpen(g_symbol,g_tf,0,25,op)<25 ||
       CopyTickVolume(g_symbol,g_tf,0,25,vl)<25) return;

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
        case TREND_UP:   return "TREND_UP";
        case TREND_DOWN: return "TREND_DOWN";
        case VOLATILE:   return "VOLATILE";
        default:         return "RANGING";
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
    double bid    = SymbolInfoDouble(symbol, SYMBOL_BID);
    double ask    = SymbolInfoDouble(symbol, SYMBOL_ASK);
    double spread = ask - bid;

    string j = "{\n";
    j += "  \"symbol\": \""      + symbol + "\",\n";
    j += "  \"server_time\": \"" + TimeToString(TimeCurrent(), TIME_DATE|TIME_SECONDS) + "\",\n";
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
