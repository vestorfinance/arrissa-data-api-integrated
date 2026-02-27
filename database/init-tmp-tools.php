<?php
/**
 * TMP (Tool Management Protocol) — Database initializer + seeder
 * Creates tool_categories and tools tables, then seeds all 60 tools.
 *
 * Run once:  php database/init-tmp-tools.php
 */

$dbPath = __DIR__ . '/app.db';

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage() . "\n");
}

// ─── SCHEMA ──────────────────────────────────────────────────────────────────

$db->exec("
    CREATE TABLE IF NOT EXISTS tool_categories (
        id            INTEGER PRIMARY KEY AUTOINCREMENT,
        name          TEXT    NOT NULL UNIQUE,
        description   TEXT,
        endpoint_base TEXT,
        requires_ea   INTEGER DEFAULT 0,
        ea_name       TEXT,
        created_at    TEXT    DEFAULT (datetime('now'))
    )
");

$db->exec("
    CREATE TABLE IF NOT EXISTS tools (
        id                 INTEGER PRIMARY KEY AUTOINCREMENT,
        category_id        INTEGER NOT NULL REFERENCES tool_categories(id),
        tool_name          TEXT    NOT NULL UNIQUE,
        tool_format        TEXT    NOT NULL,
        inputs_explanation TEXT,
        description        TEXT,
        search_phrase      TEXT    NOT NULL UNIQUE,
        auth_method        TEXT    DEFAULT 'api_key_query',
        response_type      TEXT    DEFAULT 'json',
        enabled            INTEGER DEFAULT 1,
        created_at         TEXT    DEFAULT (datetime('now'))
    )
");

$db->exec("CREATE INDEX IF NOT EXISTS idx_tools_category  ON tools(category_id)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_tools_enabled   ON tools(enabled)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_tools_search    ON tools(search_phrase)");

echo "Schema ready.\n";

// ─── CATEGORIES ──────────────────────────────────────────────────────────────

$categories = [
    [
        'name'          => 'market-data',
        'description'   => 'OHLC candlestick data from MT5 via EA queue',
        'endpoint_base' => '/market-data-api-v1/market-data-api.php',
        'requires_ea'   => 1,
        'ea_name'       => 'Arrissa Data MT5 Market Data API.ex5',
    ],
    [
        'name'          => 'chart-images',
        'description'   => 'PNG candlestick chart generation with optional indicators',
        'endpoint_base' => '/chart-image-api-v1/chart-image-api.php',
        'requires_ea'   => 1,
        'ea_name'       => 'Arrissa Data MT5 Market Data API.ex5',
    ],
    [
        'name'          => 'economic-calendar',
        'description'   => 'Economic events, news calendar data and similar-scene analysis',
        'endpoint_base' => '/news-api-v1/news-api.php',
        'requires_ea'   => 0,
        'ea_name'       => null,
    ],
    [
        'name'          => 'orders',
        'description'   => 'MT5 trade execution, management, history and profit queries',
        'endpoint_base' => '/orders-api-v1/orders-api.php',
        'requires_ea'   => 1,
        'ea_name'       => 'Arrissa Data MT5 Orders API.ex5',
    ],
    [
        'name'          => 'market-analysis',
        'description'   => 'TMA+CG zones, Quarters Theory levels, and Symbol behaviour analysis',
        'endpoint_base' => '/tma-cg-api-v1/tma-cg-api.php',
        'requires_ea'   => 1,
        'ea_name'       => 'Multiple — see individual tools',
    ],
    [
        'name'          => 'web-content',
        'description'   => 'Fetch and extract text content from any URL with multi-method auth',
        'endpoint_base' => '/url-api-v1/url-api.php',
        'requires_ea'   => 0,
        'ea_name'       => null,
    ],
];

$insertCat = $db->prepare("
    INSERT OR IGNORE INTO tool_categories (name, description, endpoint_base, requires_ea, ea_name)
    VALUES (:name, :description, :endpoint_base, :requires_ea, :ea_name)
");
foreach ($categories as $cat) {
    $insertCat->execute($cat);
}

// Build category name → id map
$catMap = [];
foreach ($db->query("SELECT id, name FROM tool_categories") as $row) {
    $catMap[$row['name']] = (int)$row['id'];
}

echo "Categories seeded: " . count($catMap) . "\n";

// ─── TOOLS ───────────────────────────────────────────────────────────────────

$tools = [

    // =========================================================
    // CATEGORY: market-data  (tools 1-8)
    // =========================================================
    [
        'category'          => 'market-data',
        'tool_name'         => 'get_candles_by_count',
        'tool_format'       => '{base_url}/market-data-api-v1/market-data-api.php?api_key={api_key}&symbol={symbol}&timeframe={timeframe}&count={count}',
        'inputs_explanation'=> "symbol    = trading instrument\ntimeframe = M1 | M5 | M15 | M30 | H1 | H4 | D1 | W1 | MN1\ncount     = number of candles to retrieve (integer, 1-5000)",
        'description'       => 'Get the most recent N OHLC candles for a symbol and timeframe',
        'search_phrase'     => 'get number of candles',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'market-data',
        'tool_name'         => 'get_candles_by_range',
        'tool_format'       => '{base_url}/market-data-api-v1/market-data-api.php?api_key={api_key}&symbol={symbol}&timeframe={timeframe}&rangeType={rangeType}',
        'inputs_explanation'=> "symbol    = trading instrument\ntimeframe = M1 | M5 | M15 | M30 | H1 | H4 | D1 | W1 | MN1\nrangeType = last-five-minutes | last-hour | last-6-hours | last-12-hours | last-48-hours | last-3-days | last-4-days | last-5-days | last-7-days | last-14-days | last-30-days | today | yesterday | this-week | last-week | this-month | last-month | last-3-months | last-6-months | this-year | last-12-months | future",
        'description'       => 'Get candles for a named time range (today, last-week, this-month, etc.)',
        'search_phrase'     => 'get candles by time range name',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'market-data',
        'tool_name'         => 'get_candles_last_x_minutes',
        'tool_format'       => '{base_url}/market-data-api-v1/market-data-api.php?api_key={api_key}&symbol={symbol}&timeframe={timeframe}&rangeType=last-{minutes}-minutes',
        'inputs_explanation'=> "symbol    = trading instrument\ntimeframe = M1 | M5 | M15 | M30 | H1 | H4 | D1 | W1 | MN1\nminutes   = any integer between 1 and 1440 (1 minute to 24 hours) e.g. rangeType=last-15-minutes, rangeType=last-90-minutes",
        'description'       => 'Get candles covering the last X minutes (dynamic minute range 1-1440)',
        'search_phrase'     => 'get candles for last X minutes',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'market-data',
        'tool_name'         => 'get_candles_with_indicators',
        'tool_format'       => '{base_url}/market-data-api-v1/market-data-api.php?api_key={api_key}&symbol={symbol}&timeframe={timeframe}&count={count}&{indicator_params}',
        'inputs_explanation'=> "symbol    = trading instrument\ntimeframe = M1 | M5 | M15 | M30 | H1 | H4 | D1 | W1 | MN1\ncount     = number of candles (1-5000) OR replace with rangeType=<range>\nrangeType = last-five-minutes | last-hour | last-6-hours | last-12-hours | last-48-hours | last-3-days | last-4-days | last-5-days | last-7-days | last-14-days | last-30-days | today | yesterday | this-week | last-week | this-month | last-month | last-3-months | last-6-months | this-year | last-12-months | future\nOSCILLATORS: rsi={period} | stoch={k,d,slowing} | cci={period} | wpr={period} | mfi={period} | momentum={period} | demarker={period}\nTREND: macd={fast,slow,signal} | sar={step,maximum} | ichimoku={tenkan,kijun,senkou}\nVOLATILITY: bb={period,shift,deviation} | atr={period} | envelopes={period,deviation} | stddev={period}\nVOLUME: obv={volume_type} (0=tick, 1=real)\nBILL WILLIAMS: ac | ao | alligator={jaw,teeth,lips} | fractals\nMax 30 indicators per request",
        'description'       => 'Get candles with one or more technical indicators appended (up to 30 indicators per request)',
        'search_phrase'     => 'get candles with technical indicators',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'market-data',
        'tool_name'         => 'get_candles_with_moving_averages',
        'tool_format'       => '{base_url}/market-data-api-v1/market-data-api.php?api_key={api_key}&symbol={symbol}&timeframe={timeframe}&count={count}&ma_1={type,period}&ma_2={type,period}',
        'inputs_explanation'=> "symbol    = trading instrument\ntimeframe = M1 | M5 | M15 | M30 | H1 | H4 | D1 | W1 | MN1\ncount     = number of candles (1-5000) OR replace with rangeType=<range>\nrangeType = last-five-minutes | last-hour | last-6-hours | last-12-hours | last-48-hours | last-3-days | last-4-days | last-5-days | last-7-days | last-14-days | last-30-days | today | yesterday | this-week | last-week | this-month | last-month | last-3-months | last-6-months | this-year | last-12-months | future\nma_1..ma_N = type,period where type: e=EMA | s=SMA (default) | sm=SMMA | l=LWMA\nExamples: ma_1=e,20  ma_2=50  ema_1=20&ema_2=50  sma_1=20&lwma_1=l,50\nUp to 10 MA parameters supported",
        'description'       => 'Get candles with up to 10 moving averages of any type (EMA/SMA/SMMA/LWMA)',
        'search_phrase'     => 'get candles with multiple moving averages',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'market-data',
        'tool_name'         => 'get_candles_with_volume',
        'tool_format'       => '{base_url}/market-data-api-v1/market-data-api.php?api_key={api_key}&symbol={symbol}&timeframe={timeframe}&count={count}&volume=true',
        'inputs_explanation'=> "symbol    = trading instrument\ntimeframe = M1 | M5 | M15 | M30 | H1 | H4 | D1 | W1 | MN1\ncount     = number of candles (1-5000) OR replace with rangeType=<range>\nrangeType = last-five-minutes | last-hour | last-6-hours | last-12-hours | last-48-hours | last-3-days | last-4-days | last-5-days | last-7-days | last-14-days | last-30-days | today | yesterday | this-week | last-week | this-month | last-month | last-3-months | last-6-months | this-year | last-12-months | future\nvolume    = true | false  (aliases also accepted: candle-volume=true  or  candlevolume=true)",
        'description'       => 'Get candles with tick volume included in each candle object',
        'search_phrase'     => 'get candles with tick volume data',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'market-data',
        'tool_name'         => 'get_single_price_field_array',
        'tool_format'       => '{base_url}/market-data-api-v1/market-data-api.php?api_key={api_key}&symbol={symbol}&timeframe={timeframe}&count={count}&dataField={field}',
        'inputs_explanation'=> "symbol    = trading instrument\ntimeframe = M1 | M5 | M15 | M30 | H1 | H4 | D1 | W1 | MN1\ncount     = number of candles (1-5000) OR replace with rangeType=<range>\nrangeType = last-five-minutes | last-hour | last-6-hours | last-12-hours | last-48-hours | last-3-days | last-4-days | last-5-days | last-7-days | last-14-days | last-30-days | today | yesterday | this-week | last-week | this-month | last-month | last-3-months | last-6-months | this-year | last-12-months | future\ndataField = open | high | low | close | volume\nReturns ONLY that field as a flat array of values instead of full candle objects",
        'description'       => 'Get a single OHLCV field as a plain array (e.g., only close prices, only volumes)',
        'search_phrase'     => 'get single price field as array',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'market-data',
        'tool_name'         => 'get_candles_backtest_mode',
        'tool_format'       => '{base_url}/market-data-api-v1/market-data-api.php?api_key={api_key}&symbol={symbol}&timeframe={timeframe}&count={count}&pretend_date={YYYY-MM-DD}&pretend_time={HH:MM}',
        'inputs_explanation'=> "symbol       = trading instrument\ntimeframe    = M1 | M5 | M15 | M30 | H1 | H4 | D1 | W1 | MN1\ncount        = number of candles (1-5000) OR replace with rangeType=<range>\nrangeType    = last-five-minutes | last-hour | last-6-hours | last-12-hours | last-48-hours | last-3-days | last-4-days | last-5-days | last-7-days | last-14-days | last-30-days | today | yesterday | this-week | last-week | this-month | last-month | last-3-months | last-6-months | this-year | last-12-months | future\npretend_date = simulate this date as \"now\"  format: YYYY-MM-DD  e.g. 2025-12-31\npretend_time = simulate this time as \"now\"  format: HH:MM  e.g. 14:30  (required with pretend_date)",
        'description'       => 'Get candles as if the current moment were a specific historical date/time (backtesting / replay mode)',
        'search_phrase'     => 'get historical candles at a specific past date and time',
        'response_type'     => 'json',
    ],

    // =========================================================
    // CATEGORY: chart-images  (tools 9-17)
    // =========================================================
    [
        'category'          => 'chart-images',
        'tool_name'         => 'generate_chart_image',
        'tool_format'       => '{base_url}/chart-image-api-v1/chart-image-api.php?api_key={api_key}&symbol={symbol}&timeframe={timeframe}&count={count}',
        'inputs_explanation'=> "symbol    = trading instrument\ntimeframe = M1 | M5 | M15 | M30 | H1 | H4 | D1 | W1 | MN1\ncount     = number of candles to display (default 100)",
        'description'       => 'Generate a 16:9 PNG candlestick chart for a symbol and timeframe',
        'search_phrase'     => 'generate candlestick chart image',
        'response_type'     => 'image/png',
    ],
    [
        'category'          => 'chart-images',
        'tool_name'         => 'generate_chart_with_emas',
        'tool_format'       => '{base_url}/chart-image-api-v1/chart-image-api.php?api_key={api_key}&symbol={symbol}&timeframe={timeframe}&count={count}&ema1_period={period}&ema2_period={period}',
        'inputs_explanation'=> "symbol      = trading instrument\ntimeframe   = M1 | M5 | M15 | M30 | H1 | H4 | D1 | W1 | MN1\ncount       = number of candles (default 100)\nema1_period = period for the first EMA line  e.g. 20\nema2_period = period for the second EMA line e.g. 50  (optional)",
        'description'       => 'Generate a chart image with one or two EMA lines drawn as overlays',
        'search_phrase'     => 'generate chart image with EMA overlay lines',
        'response_type'     => 'image/png',
    ],
    [
        'category'          => 'chart-images',
        'tool_name'         => 'generate_chart_with_fibonacci',
        'tool_format'       => '{base_url}/chart-image-api-v1/chart-image-api.php?api_key={api_key}&symbol={symbol}&timeframe={timeframe}&count={count}&fib=true',
        'inputs_explanation'=> "symbol    = trading instrument\ntimeframe = M1 | M5 | M15 | M30 | H1 | H4 | D1 | W1 | MN1\ncount     = number of candles (default 100)\nfib       = true  → draw Fibonacci retracement levels on the chart",
        'description'       => 'Generate a chart image with Fibonacci retracement levels overlaid',
        'search_phrase'     => 'generate chart image with Fibonacci retracement levels',
        'response_type'     => 'image/png',
    ],
    [
        'category'          => 'chart-images',
        'tool_name'         => 'generate_chart_with_atr',
        'tool_format'       => '{base_url}/chart-image-api-v1/chart-image-api.php?api_key={api_key}&symbol={symbol}&timeframe={timeframe}&count={count}&atr={period}',
        'inputs_explanation'=> "symbol    = trading instrument\ntimeframe = M1 | M5 | M15 | M30 | H1 | H4 | D1 | W1 | MN1\ncount     = number of candles (default 100)\natr       = ATR indicator period  e.g. 14",
        'description'       => 'Generate a chart image with the Average True Range (ATR) indicator displayed',
        'search_phrase'     => 'generate chart image with ATR indicator',
        'response_type'     => 'image/png',
    ],
    [
        'category'          => 'chart-images',
        'tool_name'         => 'generate_chart_with_period_separators',
        'tool_format'       => '{base_url}/chart-image-api-v1/chart-image-api.php?api_key={api_key}&symbol={symbol}&timeframe={timeframe}&count={count}&period_separators={periods}&high_low={bool}',
        'inputs_explanation'=> "symbol            = trading instrument\ntimeframe         = M1 | M5 | M15 | M30 | H1 | H4 | D1 | W1 | MN1\ncount             = number of candles (default 100)\nperiod_separators = comma-separated: 5M | 15M | 30M | 1H | 4H | day | week | month | year  e.g. period_separators=1H,day\nhigh_low          = true → draw high/low price lines for each period segment (optional)",
        'description'       => 'Generate a chart image with vertical period separator lines and optional high/low markers per segment',
        'search_phrase'     => 'generate chart image with period separator lines',
        'response_type'     => 'image/png',
    ],
    [
        'category'          => 'chart-images',
        'tool_name'         => 'generate_chart_dark_theme',
        'tool_format'       => '{base_url}/chart-image-api-v1/chart-image-api.php?api_key={api_key}&symbol={symbol}&timeframe={timeframe}&count={count}&theme=dark',
        'inputs_explanation'=> "symbol    = trading instrument\ntimeframe = M1 | M5 | M15 | M30 | H1 | H4 | D1 | W1 | MN1\ncount     = number of candles (default 100)\ntheme     = light (default) | dark",
        'description'       => 'Generate a candlestick chart image with a dark background theme',
        'search_phrase'     => 'generate dark theme chart image',
        'response_type'     => 'image/png',
    ],
    [
        'category'          => 'chart-images',
        'tool_name'         => 'generate_chart_rangeType',
        'tool_format'       => '{base_url}/chart-image-api-v1/chart-image-api.php?api_key={api_key}&symbol={symbol}&timeframe={timeframe}&rangeType={rangeType}',
        'inputs_explanation'=> "symbol    = trading instrument\ntimeframe = M1 | M5 | M15 | M30 | H1 | H4 | D1 | W1 | MN1\nrangeType = same range names as Market Data API (today, last-hour, this-week, etc.)",
        'description'       => 'Generate a chart image covering a named time range instead of a fixed candle count',
        'search_phrase'     => 'generate chart image for a time range',
        'response_type'     => 'image/png',
    ],
    [
        'category'          => 'chart-images',
        'tool_name'         => 'get_streaming_chart_url',
        'tool_format'       => '{base_url}/chart-image-api-v1/chart-image-api.php?api_key={api_key}&symbol={symbol}&timeframe={timeframe}&count={count}&streaming=url',
        'inputs_explanation'=> "symbol    = trading instrument\ntimeframe = M1 | M5 | M15 | M30 | H1 | H4 | D1 | W1 | MN1\ncount     = number of candles\nstreaming = url → returns JSON with a short shareable link that auto-refreshes the chart live",
        'description'       => 'Get a short shareable URL for a live auto-updating streaming chart (returns JSON, not image)',
        'search_phrase'     => 'get live streaming chart URL',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'chart-images',
        'tool_name'         => 'redirect_to_streaming_chart',
        'tool_format'       => '{base_url}/chart-image-api-v1/chart-image-api.php?api_key={api_key}&symbol={symbol}&timeframe={timeframe}&count={count}&streaming=redirect',
        'inputs_explanation'=> "symbol    = trading instrument\ntimeframe = M1 | M5 | M15 | M30 | H1 | H4 | D1 | W1 | MN1\ncount     = number of candles\nstreaming = redirect → HTTP redirects directly to the streaming chart page",
        'description'       => 'Redirect browser directly to the live streaming chart page',
        'search_phrase'     => 'open live streaming chart page',
        'response_type'     => 'redirect',
    ],

    // =========================================================
    // CATEGORY: economic-calendar  (tools 18-31)
    // =========================================================
    [
        'category'          => 'economic-calendar',
        'tool_name'         => 'get_economic_events_by_period',
        'tool_format'       => '{base_url}/news-api-v1/news-api.php?api_key={api_key}&period={period}',
        'inputs_explanation'=> "period = Named period — any of:\ntoday | yesterday | this-week | last-week | this-month | last-month | last-3-months | last-6-months | last-7-days | last-14-days | last-30-days | this-year | last-12-months | last-2-years | future\nDynamic: last-{N}-hours | last-{N}-days | last-{N}-weeks | last-{N}-months | last-{N}-years  e.g. last-3-hours, last-21-days",
        'description'       => 'Get all economic events for a named time period',
        'search_phrase'     => 'get economic events by period',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'economic-calendar',
        'tool_name'         => 'get_economic_events_by_date_range',
        'tool_format'       => '{base_url}/news-api-v1/news-api.php?api_key={api_key}&start_date={YYYY-MM-DD}&end_date={YYYY-MM-DD}&start_time={HH:MM:SS}&end_time={HH:MM:SS}',
        'inputs_explanation'=> "start_date = start date  format: YYYY-MM-DD  (required if no period param)\nend_date   = end date    format: YYYY-MM-DD  (required if no period param)\nstart_time = start time  format: HH:MM:SS    (optional, default 00:00:00)\nend_time   = end time    format: HH:MM:SS    (optional, default 23:59:59)",
        'description'       => 'Get economic events between two explicit date/time boundaries',
        'search_phrase'     => 'get economic events between two dates',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'economic-calendar',
        'tool_name'         => 'get_economic_events_by_currency',
        'tool_format'       => '{base_url}/news-api-v1/news-api.php?api_key={api_key}&period={period}&currency={currency}',
        'inputs_explanation'=> "period   = see get_economic_events_by_period for options\ncurrency = USD | EUR | GBP | JPY | CAD | AUD | NZD | CHF",
        'description'       => 'Get economic events filtered to a specific currency',
        'search_phrase'     => 'get economic events for a currency',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'economic-calendar',
        'tool_name'         => 'get_economic_events_by_impact',
        'tool_format'       => '{base_url}/news-api-v1/news-api.php?api_key={api_key}&period={period}&impact={impact}',
        'inputs_explanation'=> "period = see get_economic_events_by_period for options\nimpact = High | Medium | Low\n         comma-separated for multiple: impact=High,Medium",
        'description'       => 'Get economic events filtered by impact level (High / Medium / Low)',
        'search_phrase'     => 'get economic events by impact level',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'economic-calendar',
        'tool_name'         => 'get_future_economic_events',
        'tool_format'       => '{base_url}/news-api-v1/news-api.php?api_key={api_key}&period=future&future_limit={limit}',
        'inputs_explanation'=> "future_limit = Limit the future window to:\ntoday | tomorrow | next-2-days | this-week | next-week | next-2-weeks | next-month\nDynamic: next-{N}-hours | next-{N}-days | next-{N}-weeks | next-{N}-months  e.g. next-3-days, next-2-weeks",
        'description'       => 'Get upcoming future economic events with an optional time window cap',
        'search_phrase'     => 'get upcoming future economic events',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'economic-calendar',
        'tool_name'         => 'get_economic_events_by_event_id',
        'tool_format'       => '{base_url}/news-api-v1/news-api.php?api_key={api_key}&event_id={event_id}&period={period}',
        'inputs_explanation'=> "event_id = consistent_event_id(s) — comma-separated for multiple\n           e.g. NFP_USD   or   NFP_USD,UNEMPLOYMENT_USD\nperiod   = optional time period to filter occurrences",
        'description'       => 'Get all historical occurrences of a specific economic event by its consistent ID',
        'search_phrase'     => 'get economic event occurrences by event ID',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'economic-calendar',
        'tool_name'         => 'get_economic_events_with_timezone',
        'tool_format'       => '{base_url}/news-api-v1/news-api.php?api_key={api_key}&period={period}&time_zone={timezone}',
        'inputs_explanation'=> "period    = see get_economic_events_by_period for options\ntime_zone = Shorthand: NY | LA | LON | TYO | SYD\n            Or any valid PHP timezone: America/New_York | Europe/London | Asia/Tokyo | etc.",
        'description'       => 'Get economic events with all date/times converted to a specified timezone',
        'search_phrase'     => 'get economic events in a specific timezone',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'economic-calendar',
        'tool_name'         => 'get_economic_events_minimal',
        'tool_format'       => '{base_url}/news-api-v1/news-api.php?api_key={api_key}&period={period}&display=min',
        'inputs_explanation'=> "period  = see get_economic_events_by_period for options\ndisplay = min → returns only: event_name, event_date, event_time, currency\n          + forecast/actual/previous only when available",
        'description'       => 'Get economic events in minimal format with only essential fields',
        'search_phrase'     => 'get economic events minimal fields only',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'economic-calendar',
        'tool_name'         => 'get_economic_events_without_duplicates',
        'tool_format'       => '{base_url}/news-api-v1/news-api.php?api_key={api_key}&period={period}&avoid_duplicates=true',
        'inputs_explanation'=> "period           = see get_economic_events_by_period for options\navoid_duplicates = true → returns only the first occurrence of each consistent_event_id",
        'description'       => 'Get economic events with duplicate event types removed (one record per unique event ID)',
        'search_phrase'     => 'get unique economic events without duplicates',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'economic-calendar',
        'tool_name'         => 'get_economic_events_with_actuals',
        'tool_format'       => '{base_url}/news-api-v1/news-api.php?api_key={api_key}&period={period}&must_have=actual_value',
        'inputs_explanation'=> "period    = see get_economic_events_by_period for options\nmust_have = actual_value | forecast_value | previous_value\n            comma-separated for multiple: must_have=actual_value,forecast_value",
        'description'       => 'Get only economic events that have specific field values populated (e.g., only events with actual values released)',
        'search_phrase'     => 'get economic events that have actual values released',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'economic-calendar',
        'tool_name'         => 'get_economic_events_exclude_currencies',
        'tool_format'       => '{base_url}/news-api-v1/news-api.php?api_key={api_key}&period={period}&currency_exclude={currencies}',
        'inputs_explanation'=> "period           = see get_economic_events_by_period for options\ncurrency_exclude = comma-separated currency codes to exclude  e.g. currency_exclude=EUR,GBP",
        'description'       => 'Get all economic events excluding events for specified currencies',
        'search_phrase'     => 'get economic events excluding specific currencies',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'economic-calendar',
        'tool_name'         => 'get_economic_events_future_with_tbd',
        'tool_format'       => '{base_url}/news-api-v1/news-api.php?api_key={api_key}&period={period}&tbd=true',
        'inputs_explanation'=> "period = see get_economic_events_by_period for options (works best with future or today+spit_out=all)\ntbd    = true → replaces actual_value with \"TBD\" for all events (useful for forward-looking display)",
        'description'       => 'Get economic events with actual values masked as "TBD" (for scheduled/future event display)',
        'search_phrase'     => 'get economic events with TBD actual values',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'economic-calendar',
        'tool_name'         => 'get_economic_events_all_including_future',
        'tool_format'       => '{base_url}/news-api-v1/news-api.php?api_key={api_key}&period={period}&spit_out=all',
        'inputs_explanation'=> "period   = today | this-week | this-month | this-year  (period-based only)\nspit_out = all → returns ALL events in the period including future ones (not capped at current time)",
        'description'       => 'Get all economic events for a period including scheduled future events within that period',
        'search_phrase'     => 'get all economic events including future scheduled events',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'economic-calendar',
        'tool_name'         => 'get_similar_market_scenes',
        'tool_format'       => '{base_url}/news-api-v1/similar-scene-api.php?api_key={api_key}&event_id={event_id}&symbol={symbol}&period={period}',
        'inputs_explanation'=> "event_id = consistent_event_id(s) comma-separated (required)  e.g. NFP_USD  or  CPI_USD,UNEMPLOYMENT_USD\nsymbol   = trading instrument to fetch market data at each event occurrence (default: XAUUSD)\nperiod   = time window for historical occurrences  e.g. last-3-months, last-6-months, last-12-months\ndisplay  = min → minimal event fields (optional)\ncurrency = filter events by currency  e.g. USD (optional)\nimpact   = filter events by impact level: High | Medium | Low (optional)\noutput   = all → return all events at each occurrence's timestamp (optional)\ntbd      = true → mask actual_value as \"TBD\" (optional)",
        'description'       => 'Get historical market conditions (candles) at every past occurrence of a specific economic event, used to find repeating market patterns around news events',
        'search_phrase'     => 'get historical similar market scenes at economic events',
        'response_type'     => 'json',
    ],

    // =========================================================
    // CATEGORY: orders  (tools 32-51)
    // =========================================================
    [
        'category'          => 'orders',
        'tool_name'         => 'open_buy_market_order',
        'tool_format'       => '{base_url}/orders-api-v1/orders-api.php?api_key={api_key}&action=BUY&symbol={symbol}&volume={volume}&sl={sl}&tp={tp}',
        'inputs_explanation'=> "symbol = trading instrument\nvolume = lot size  e.g. 0.01 | 0.1 | 1.0\nsl     = stop loss price  (0 = no stop loss)\ntp     = take profit price  (0 = no take profit)",
        'description'       => 'Open a buy market order at the current ask price',
        'search_phrase'     => 'open buy market order',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'orders',
        'tool_name'         => 'open_sell_market_order',
        'tool_format'       => '{base_url}/orders-api-v1/orders-api.php?api_key={api_key}&action=SELL&symbol={symbol}&volume={volume}&sl={sl}&tp={tp}',
        'inputs_explanation'=> "symbol = trading instrument\nvolume = lot size  e.g. 0.01 | 0.1 | 1.0\nsl     = stop loss price  (0 = no stop loss)\ntp     = take profit price  (0 = no take profit)",
        'description'       => 'Open a sell market order at the current bid price',
        'search_phrase'     => 'open sell market order',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'orders',
        'tool_name'         => 'place_buy_limit_order',
        'tool_format'       => '{base_url}/orders-api-v1/orders-api.php?api_key={api_key}&action=BUY_LIMIT&symbol={symbol}&volume={volume}&price={price}&sl={sl}&tp={tp}',
        'inputs_explanation'=> "symbol = trading instrument\nvolume = lot size  e.g. 0.01\nprice  = limit entry price  (must be below current market for buy limit)\nsl     = stop loss price  (0 = none)\ntp     = take profit price  (0 = none)",
        'description'       => 'Place a buy limit pending order at a price below the current market',
        'search_phrase'     => 'place buy limit pending order',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'orders',
        'tool_name'         => 'place_sell_limit_order',
        'tool_format'       => '{base_url}/orders-api-v1/orders-api.php?api_key={api_key}&action=SELL_LIMIT&symbol={symbol}&volume={volume}&price={price}&sl={sl}&tp={tp}',
        'inputs_explanation'=> "symbol = trading instrument\nvolume = lot size  e.g. 0.01\nprice  = limit entry price  (must be above current market for sell limit)\nsl     = stop loss price  (0 = none)\ntp     = take profit price  (0 = none)",
        'description'       => 'Place a sell limit pending order at a price above the current market',
        'search_phrase'     => 'place sell limit pending order',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'orders',
        'tool_name'         => 'place_buy_stop_order',
        'tool_format'       => '{base_url}/orders-api-v1/orders-api.php?api_key={api_key}&action=BUY_STOP&symbol={symbol}&volume={volume}&price={price}&sl={sl}&tp={tp}',
        'inputs_explanation'=> "symbol = trading instrument\nvolume = lot size  e.g. 0.01\nprice  = stop entry price  (must be above current market for buy stop)\nsl     = stop loss price  (0 = none)\ntp     = take profit price  (0 = none)",
        'description'       => 'Place a buy stop pending order at a price above the current market',
        'search_phrase'     => 'place buy stop pending order',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'orders',
        'tool_name'         => 'place_sell_stop_order',
        'tool_format'       => '{base_url}/orders-api-v1/orders-api.php?api_key={api_key}&action=SELL_STOP&symbol={symbol}&volume={volume}&price={price}&sl={sl}&tp={tp}',
        'inputs_explanation'=> "symbol = trading instrument\nvolume = lot size  e.g. 0.01\nprice  = stop entry price  (must be below current market for sell stop)\nsl     = stop loss price  (0 = none)\ntp     = take profit price  (0 = none)",
        'description'       => 'Place a sell stop pending order at a price below the current market',
        'search_phrase'     => 'place sell stop pending order',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'orders',
        'tool_name'         => 'close_position_by_ticket',
        'tool_format'       => '{base_url}/orders-api-v1/orders-api.php?api_key={api_key}&action=CLOSE&ticket={ticket}',
        'inputs_explanation'=> "ticket = MT5 position ticket number to close  e.g. 1234567",
        'description'       => 'Close a specific open position by its MT5 ticket number',
        'search_phrase'     => 'close specific position by ticket number',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'orders',
        'tool_name'         => 'close_all_positions',
        'tool_format'       => '{base_url}/orders-api-v1/orders-api.php?api_key={api_key}&action=CLOSE_ALL&symbol={symbol}',
        'inputs_explanation'=> "symbol = ALL (close all symbols)  OR  a specific symbol",
        'description'       => 'Close all currently open positions (or all positions for a specific symbol)',
        'search_phrase'     => 'close all open positions',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'orders',
        'tool_name'         => 'close_losing_positions',
        'tool_format'       => '{base_url}/orders-api-v1/orders-api.php?api_key={api_key}&action=CLOSE_LOSS&symbol={symbol}',
        'inputs_explanation'=> "symbol = ALL  OR  a specific symbol",
        'description'       => 'Close only the losing (negative P&L) positions',
        'search_phrase'     => 'close all losing positions',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'orders',
        'tool_name'         => 'close_profitable_positions',
        'tool_format'       => '{base_url}/orders-api-v1/orders-api.php?api_key={api_key}&action=CLOSE_PROFIT&symbol={symbol}',
        'inputs_explanation'=> "symbol = ALL  OR  a specific symbol",
        'description'       => 'Close only the profitable (positive P&L) positions',
        'search_phrase'     => 'close all profitable positions',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'orders',
        'tool_name'         => 'move_position_to_break_even',
        'tool_format'       => '{base_url}/orders-api-v1/orders-api.php?api_key={api_key}&action=BREAK_EVEN&ticket={ticket}',
        'inputs_explanation'=> "ticket = MT5 position ticket number",
        'description'       => 'Move the stop loss of a specific position to its entry price (break even)',
        'search_phrase'     => 'move position stop loss to break even',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'orders',
        'tool_name'         => 'move_all_positions_to_break_even',
        'tool_format'       => '{base_url}/orders-api-v1/orders-api.php?api_key={api_key}&action=BREAK_EVEN_ALL&symbol={symbol}',
        'inputs_explanation'=> "symbol = ALL  OR  specific symbol",
        'description'       => 'Move all open positions\' stop losses to their respective entry prices',
        'search_phrase'     => 'move all positions to break even',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'orders',
        'tool_name'         => 'modify_take_profit',
        'tool_format'       => '{base_url}/orders-api-v1/orders-api.php?api_key={api_key}&action=MODIFY_TP&ticket={ticket}&new_value={price}',
        'inputs_explanation'=> "ticket    = MT5 position ticket number\nnew_value = new take profit price level",
        'description'       => 'Modify the take profit price of a specific open position',
        'search_phrase'     => 'modify take profit of open position',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'orders',
        'tool_name'         => 'modify_stop_loss',
        'tool_format'       => '{base_url}/orders-api-v1/orders-api.php?api_key={api_key}&action=MODIFY_SL&ticket={ticket}&new_value={price}',
        'inputs_explanation'=> "ticket    = MT5 position ticket number\nnew_value = new stop loss price level",
        'description'       => 'Modify the stop loss price of a specific open position',
        'search_phrase'     => 'modify stop loss of open position',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'orders',
        'tool_name'         => 'delete_pending_order',
        'tool_format'       => '{base_url}/orders-api-v1/orders-api.php?api_key={api_key}&action=DELETE_ORDER&ticket={ticket}',
        'inputs_explanation'=> "ticket = MT5 pending order ticket number to delete",
        'description'       => 'Delete a specific pending (limit or stop) order by ticket number',
        'search_phrase'     => 'delete pending order by ticket',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'orders',
        'tool_name'         => 'delete_all_pending_orders',
        'tool_format'       => '{base_url}/orders-api-v1/orders-api.php?api_key={api_key}&action=DELETE_ALL_ORDERS',
        'inputs_explanation'=> "No additional parameters required.",
        'description'       => 'Delete all pending (limit and stop) orders on the account',
        'search_phrase'     => 'delete all pending orders',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'orders',
        'tool_name'         => 'trail_stop_loss',
        'tool_format'       => '{base_url}/orders-api-v1/orders-api.php?api_key={api_key}&action=TRAIL_SL&ticket={ticket}&new_value={points}',
        'inputs_explanation'=> "ticket    = MT5 position ticket number\nnew_value = trailing distance in points  e.g. 50  (50 points trailing distance)",
        'description'       => 'Apply a trailing stop loss to a position by a specified number of points',
        'search_phrase'     => 'set trailing stop loss on position',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'orders',
        'tool_name'         => 'get_open_orders',
        'tool_format'       => '{base_url}/orders-api-v1/orders-api.php?api_key={api_key}&filter_running=true',
        'inputs_explanation'=> "filter_running = true → include open/running positions\nfilter_pending = true → include pending orders\nfilter_profit  = true → include only profitable positions\nfilter_loss    = true → include only losing positions\nfilter_symbol  = filter by specific symbol\nfilter_comment = filter by trade comment text\n(combine multiple filters as needed)",
        'description'       => 'Get currently open/running positions with optional filters',
        'search_phrase'     => 'get all open orders and positions',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'orders',
        'tool_name'         => 'get_trade_history',
        'tool_format'       => '{base_url}/orders-api-v1/orders-api.php?api_key={api_key}&history={period}',
        'inputs_explanation'=> "history = today | last-hour | last-10 | last-20 | last-7days | last-30days",
        'description'       => 'Get closed trade history for a specified time period',
        'search_phrase'     => 'get closed trade history',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'orders',
        'tool_name'         => 'get_profit_summary',
        'tool_format'       => '{base_url}/orders-api-v1/orders-api.php?api_key={api_key}&profit={period}',
        'inputs_explanation'=> "profit = today | last-hour | this-week | this-month | last-7days | last-30days",
        'description'       => 'Get a profit and loss summary for a specified time period',
        'search_phrase'     => 'get profit and loss summary',
        'response_type'     => 'json',
    ],

    // =========================================================
    // CATEGORY: market-analysis  (tools 52-54)
    // =========================================================
    [
        'category'          => 'market-analysis',
        'tool_name'         => 'get_symbol_behavior_analysis',
        'tool_format'       => '{base_url}/symbol-info-api-v1/symbol-info-api.php?api_key={api_key}&symbol={symbol}&timeframe={timeframe}&lookback={lookback}',
        'inputs_explanation'=> "symbol        = trading instrument\ntimeframe     = M5 | M15 | M30 | H1 | H4 | H8 | H12 | D1 | W1 | M\nlookback      = number of historical periods to analyze (timeframe-specific max):\n                M5=2000 | M15=1000 | M30=500 | H1=500 | H4=200 | H8=100 | H12=60 | D1=1000 | W1=200 | M=120\n                (omit to use default for the timeframe)\nignore_sunday = true (default) | false → exclude Sunday candles from analysis\npretend_date  = YYYY-MM-DD → backtesting mode (optional, requires pretend_time)\npretend_time  = HH:MM → backtesting mode (optional, requires pretend_date)",
        'description'       => 'Get comprehensive symbol behavior and volatility analysis — daily averages, typical ranges, directional bias, and statistical data for a trading instrument',
        'search_phrase'     => 'get symbol behavior volatility analysis',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'market-analysis',
        'tool_name'         => 'get_tma_cg_zone',
        'tool_format'       => '{base_url}/tma-cg-api-v1/tma-cg-api.php?api_key={api_key}&symbol={symbol}&timeframe={timeframe}',
        'inputs_explanation'=> "symbol       = trading instrument\ntimeframe    = M1 (default) | M5 | M15 | M30 | H1 | H4 | D1\npretend_date = YYYY-MM-DD → backtesting mode (optional, requires pretend_time)\npretend_time = HH:MM → backtesting mode (optional)",
        'description'       => 'Get TMA+CG zone classification (premium / discount / equilibrium) and the percentage position of the current price within the TMA bands',
        'search_phrase'     => 'get TMA CG premium discount zone and percentage',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'market-analysis',
        'tool_name'         => 'get_quarters_theory_data',
        'tool_format'       => '{base_url}/quarters-theory-api-v1/quarters-theory-api.php?api_key={api_key}&symbol={symbol}',
        'inputs_explanation'=> "symbol       = trading instrument\npretend_date = YYYY-MM-DD → backtesting mode (optional, requires pretend_time)\npretend_time = HH:MM → backtesting mode (optional)",
        'description'       => 'Get Quarters Theory key price levels (quarter boundaries at every 0.25 unit) and the current zone position for a trading instrument',
        'search_phrase'     => 'get quarters theory price levels and zones',
        'response_type'     => 'json',
    ],

    // =========================================================
    // CATEGORY: web-content  (tools 55-60)
    // =========================================================
    [
        'category'          => 'web-content',
        'tool_name'         => 'fetch_url_content',
        'tool_format'       => '{base_url}/url-api-v1/url-api.php?api_key={api_key}&url={target_url}',
        'inputs_explanation'=> "url = complete target URL to fetch content from  (must start with http:// or https://)\n      e.g. url=https://example.com/page",
        'description'       => 'Fetch and extract meaningful text content (page title + main body text) from any public web page URL',
        'search_phrase'     => 'fetch web page content from URL',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'web-content',
        'tool_name'         => 'fetch_url_with_basic_auth',
        'tool_format'       => '{base_url}/url-api-v1/url-api.php?api_key={api_key}&url={target_url}&auth_user={username}&auth_pass={password}',
        'inputs_explanation'=> "url       = complete target URL\nauth_user = HTTP basic authentication username\nauth_pass = HTTP basic authentication password",
        'description'       => 'Fetch content from a URL that is protected by HTTP Basic Authentication',
        'search_phrase'     => 'fetch URL with basic authentication username and password',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'web-content',
        'tool_name'         => 'fetch_url_with_bearer_token',
        'tool_format'       => '{base_url}/url-api-v1/url-api.php?api_key={api_key}&url={target_url}&bearer_token={token}',
        'inputs_explanation'=> "url          = complete target URL\nbearer_token = OAuth or JWT bearer token sent as  Authorization: Bearer {token}",
        'description'       => 'Fetch content from a URL using Bearer token authentication (OAuth / JWT)',
        'search_phrase'     => 'fetch URL with bearer token authentication',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'web-content',
        'tool_name'         => 'fetch_url_with_target_api_key',
        'tool_format'       => '{base_url}/url-api-v1/url-api.php?api_key={api_key}&url={target_url}&target_key={key}&api_key_name={header_name}',
        'inputs_explanation'=> "url          = complete target URL\ntarget_key   = the API key to send to the target URL\napi_key_name = the HTTP header name for the key (default: X-API-Key)\n               e.g. api_key_name=X-Api-Key  or  api_key_name=Authorization",
        'description'       => 'Fetch content from a URL that requires an API key passed via a custom HTTP header',
        'search_phrase'     => 'fetch URL with API key header authentication',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'web-content',
        'tool_name'         => 'fetch_url_with_session_cookie',
        'tool_format'       => '{base_url}/url-api-v1/url-api.php?api_key={api_key}&url={target_url}&session_cookie={cookie_string}',
        'inputs_explanation'=> "url            = complete target URL\nsession_cookie = session cookie string in standard format  e.g. session_id=abc123def456",
        'description'       => 'Fetch content from a URL using a session cookie for authentication',
        'search_phrase'     => 'fetch URL with session cookie authentication',
        'response_type'     => 'json',
    ],
    [
        'category'          => 'web-content',
        'tool_name'         => 'fetch_url_with_custom_headers',
        'tool_format'       => '{base_url}/url-api-v1/url-api.php?api_key={api_key}&url={target_url}&custom_headers={json_headers}',
        'inputs_explanation'=> "url            = complete target URL\ncustom_headers = JSON-encoded object of headers to inject into the request\n                 e.g. {\"Authorization\":\"Bearer token123\",\"X-Custom-Header\":\"value\",\"Accept\":\"application/json\"}",
        'description'       => 'Fetch content from a URL by injecting custom HTTP headers (for any authentication method or special header requirements)',
        'search_phrase'     => 'fetch URL with custom HTTP headers',
        'response_type'     => 'json',
    ],
];

// ─── SEED TOOLS ──────────────────────────────────────────────────────────────

$insertTool = $db->prepare("
    INSERT OR IGNORE INTO tools
        (category_id, tool_name, tool_format, inputs_explanation, description, search_phrase, auth_method, response_type, enabled)
    VALUES
        (:category_id, :tool_name, :tool_format, :inputs_explanation, :description, :search_phrase, :auth_method, :response_type, 1)
");

$seeded  = 0;
$skipped = 0;

foreach ($tools as $t) {
    $catId = $catMap[$t['category']] ?? null;
    if (!$catId) {
        echo "  WARNING: unknown category '{$t['category']}' for tool '{$t['tool_name']}'\n";
        $skipped++;
        continue;
    }
    $insertTool->execute([
        ':category_id'        => $catId,
        ':tool_name'          => $t['tool_name'],
        ':tool_format'        => $t['tool_format'],
        ':inputs_explanation' => $t['inputs_explanation'],
        ':description'        => $t['description'],
        ':search_phrase'      => $t['search_phrase'],
        ':auth_method'        => 'api_key_query',
        ':response_type'      => $t['response_type'],
    ]);
    if ($db->lastInsertId()) {
        $seeded++;
    } else {
        $skipped++;
    }
}

echo "Tools seeded: $seeded  (skipped/already exist: $skipped)\n";

// ─── VERIFY ──────────────────────────────────────────────────────────────────

echo "\n=== VERIFICATION ===\n";
foreach ($db->query("SELECT tc.name AS category, COUNT(t.id) AS total, SUM(t.enabled) AS enabled FROM tool_categories tc LEFT JOIN tools t ON tc.id = t.category_id GROUP BY tc.id ORDER BY tc.id") as $row) {
    printf("  %-22s  %d tools  (%d enabled)\n", $row['category'], $row['total'], $row['enabled']);
}
$total = $db->query("SELECT COUNT(*) AS n FROM tools")->fetch()['n'];
echo "\n  TOTAL: $total tools\n";
echo "\nDone. Database: $dbPath\n";
