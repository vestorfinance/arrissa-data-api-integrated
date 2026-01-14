<?php 
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Debug file logging
$debugFile = __DIR__ . '/similar-scene-debug.log';
function debugLog($message) {
    global $debugFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($debugFile, "[$timestamp] $message\n", FILE_APPEND);
    error_log($message);
}

// Clear debug file on each request
file_put_contents($debugFile, "=== NEW REQUEST ===\n");
debugLog("Script started");

// Load database connection
require_once __DIR__ . '/../app/Database.php';

$pdo = Database::getInstance()->getConnection();

// Helper: authenticate API key
function authenticate() {
    global $pdo;
    $api_key = $_GET['api_key'] ?? null;
    
    if (!$api_key) {
        echo json_encode(["vestor_data" => ["error" => "Missing API key."]], JSON_PRETTY_PRINT);
        exit;
    }
    
    // Get API key from settings
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE key = 'api_key'");
    $stmt->execute();
    $result = $stmt->fetch();
    $validApiKey = $result ? $result['value'] : '';
    
    if ($api_key !== $validApiKey) {
        echo json_encode(["vestor_data" => ["error" => "Invalid API key."]], JSON_PRETTY_PRINT);
        exit;
    }
}

// Authenticate the request
authenticate();
debugLog("Authentication successful");

// Helper: fetch market data from Market Data API
function fetch_market_data($symbol, $endDT, $api_key, $count, $timeframe = 'M1') {
    // Build dynamic base URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base_url = $protocol . '://' . $host . '/market-data-api-v1/market-data-api.php';
    
    // Parse end datetime
    $end_ts = strtotime($endDT);
    
    // Build request URL
    $url = $base_url . '?' . http_build_query([
        'api_key' => $api_key,
        'symbol' => $symbol,
        'count' => $count,
        'timeframe' => $timeframe,
        'pretend_date' => date('Y-m-d', $end_ts),
        'pretend_time' => date('H:i', $end_ts)
    ]);
    
    debugLog("Calling Market Data API: $url");
    
    // Fetch data using cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    debugLog("Executing cURL request...");
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    debugLog("cURL response received - HTTP Code: $httpCode");
    
    if ($httpCode !== 200 || !$response) {
        debugLog("Market Data API error - HTTP $httpCode");
        return ['error' => 'Failed to fetch market data', 'http_code' => $httpCode];
    }
    
    $data = json_decode($response, true);
    if (!isset($data['vestor_data']['candles'])) {
        debugLog("Invalid response structure from Market Data API");
        return ['error' => 'Invalid response from Market Data API'];
    }
    
    debugLog("Successfully fetched " . count($data['vestor_data']['candles']) . " candles");
    return $data['vestor_data']['candles'];
}

// Retrieve GET parameters
$event_id    = $_GET['event_id']    ?? null;  // comma-separated list of consistent_event_ids
$period      = $_GET['period']      ?? null;  // e.g. "today", "last-3-months", etc.
$start_date  = $_GET['start_date']  ?? null;  // optional explicit range
$start_time  = $_GET['start_time']  ?? null;
$end_date    = $_GET['end_date']    ?? null;
$end_time    = $_GET['end_time']    ?? null;
$display     = $_GET['display']     ?? null;  // If "min", return minimal fields
$outputMode  = $_GET['output']      ?? null;  // If "all", output all events at occurrence
// New: symbol parameter for market data (default "XAUUSD")
$symbol      = strtoupper($_GET['symbol'] ?? 'XAUUSD');
// New: currency filter for events, e.g. currency=USD
$currency    = !empty($_GET['currency']) ? strtoupper($_GET['currency']) : null;
// New: impact filter for events, e.g. impact=High or impact=High,Medium
$impact      = !empty($_GET['impact']) ? $_GET['impact'] : null;
// New: tbd parameter - if true, replace actual_value with "TBD"
$tbd         = ($_GET['tbd'] ?? '') === 'true';

if (empty($event_id)) {
    echo json_encode([
        "vestor_data" => [
            "error" => "Missing required parameter: event_id"
        ]
    ]);
    exit;
}

// Determine whether to apply a date filter
$usePeriod = false;
if (!empty($period) || ($start_date && $start_time && $end_date && $end_time)) {
    $usePeriod = true;
    if (!empty($period)) {
        $p   = strtolower(trim($period));
        $now = new DateTime("now");
        switch ($p) {
            case 'today':
                $start = (new DateTime("today"))->setTime(0,0,0);
                $end   = (new DateTime("today"))->setTime(23,59,59);
                break;
            case 'yesterday':
                $start = (new DateTime("yesterday"))->setTime(0,0,0);
                $end   = (clone $start)->setTime(23,59,59);
                break;
            case 'this-week':
                $today = new DateTime("today");
                $dow   = (int)$today->format('w');
                $start = (clone $today)->modify("-{$dow} days")->setTime(0,0,0);
                $end   = new DateTime("now");
                break;
            case 'last-week':
                $today = new DateTime("today");
                $dow   = (int)$today->format('w');
                $start = (clone $today)->modify("-" . ($dow+7) . " days")->setTime(0,0,0);
                $end   = (clone $start)->modify("+6 days")->setTime(23,59,59);
                break;
            case 'this-month':
                $start = (new DateTime("first day of this month"))->setTime(0,0,0);
                $end   = new DateTime("now");
                break;
            case 'last-month':
                $start = (new DateTime("first day of last month"))->setTime(0,0,0);
                $end   = (new DateTime("last day of last month"))->setTime(23,59,59);
                break;
            case 'last-3-months':
                $end   = new DateTime("now");
                $start = (clone $end)->modify("-3 months")->setTime(0,0,0);
                break;
            case 'last-6-months':
                $end   = new DateTime("now");
                $start = (clone $end)->modify("-6 months")->setTime(0,0,0);
                break;
            case 'last-7-days':
                $end   = new DateTime("now");
                $start = (clone $end)->modify("-7 days")->setTime(0,0,0);
                break;
            case 'last-14-days':
                $end   = new DateTime("now");
                $start = (clone $end)->modify("-14 days")->setTime(0,0,0);
                break;
            case 'last-30-days':
                $end   = new DateTime("now");
                $start = (clone $end)->modify("-30 days")->setTime(0,0,0);
                break;
            case 'this-year':
                $year  = $now->format("Y");
                $start = new DateTime("$year-01-01 00:00:00");
                $end   = new DateTime("now");
                break;
            case 'last-12-months':
                $end   = new DateTime("now");
                $start = (clone $end)->modify("-12 months")->setTime(0,0,0);
                break;
            case 'last-2-years':
                $year  = $now->format("Y");
                $start = new DateTime(($year-2) . "-01-01 00:00:00");
                $end   = new DateTime(($year-1) . "-12-31 23:59:59");
                break;
            case 'future':
                $start = new DateTime("now");
                $end   = new DateTime("9999-12-31 23:59:59");
                break;
            default:
                echo json_encode([
                    "vestor_data" => ["error" => "Invalid period parameter."]
                ]);
                exit;
        }
    } else {
        // Add seconds if not provided (accept both H:i and H:i:s formats)
        $start_time_full = (strlen($start_time) == 5) ? $start_time . ':00' : $start_time;
        $end_time_full   = (strlen($end_time) == 5) ? $end_time . ':00' : $end_time;
        
        $start = DateTime::createFromFormat('Y-m-d H:i:s', "$start_date $start_time_full");
        $end   = DateTime::createFromFormat('Y-m-d H:i:s', "$end_date $end_time_full");
        
        // Validate DateTime creation
        if ($start === false || $end === false) {
            echo json_encode([
                "vestor_data" => ["error" => "Invalid date/time format. Use YYYY-MM-DD for dates and HH:MM or HH:MM:SS for times."]
            ]);
            exit;
        }
    }
    $start_datetime = $start->format('Y-m-d H:i:s');
    $end_datetime   = $end->format('Y-m-d H:i:s');
}

debugLog("Parameters parsed - event_id: $event_id, period: $period, symbol: $symbol, impact: " . ($impact ?? 'none'));

try {
    // Prepare event_id placeholders
    $ids          = array_map('trim', explode(',', $event_id));
    $placeholders = [];
    $params       = [];
    foreach ($ids as $i => $id) {
        $ph              = ":id{$i}";
        $placeholders[]  = $ph;
        $params[$ph]     = strtoupper($id);
    }

    // Build SQL for events (filtered by argument IDs and optional currency)
    $sql = "SELECT * FROM economic_events
            WHERE consistent_event_id IN (" . implode(',', $placeholders) . ")";

    if ($currency) {
        $sql .= " AND currency = :currency";
        $params[':currency'] = $currency;
    }

    if ($usePeriod) {
        $sql .= " AND (event_date || ' ' || event_time) BETWEEN :start AND :end";
        $params[':start'] = $start_datetime;
        $params[':end']   = $end_datetime;
    } else {
        $sql .= " AND datetime(event_date || ' ' || event_time) < datetime('now')";
    }
    $sql .= " ORDER BY event_date DESC, event_time DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    debugLog("SQL query executed - found " . count($rows) . " rows");

    // Group by date+time
    $groups = [];
    foreach ($rows as $row) {
        $key = $row['event_date'] . ' ' . $row['event_time'];
        $groups[$key][] = $row;
    }

    // Keep only instances where all IDs appear
    $occurrences = [];
    
    // If only one event_id requested, all occurrences are valid
    if (count($ids) === 1) {
        $occurrences = $groups;
    } else {
        // Multiple IDs: keep only instances where ALL IDs appear together
        foreach ($groups as $dt => $eventsAtDt) {
            $idsInGroup = array_map(fn($e) => strtoupper($e['consistent_event_id']), $eventsAtDt);
            $ok = true;
            foreach ($ids as $want) {
                if (!in_array(strtoupper($want), $idsInGroup, true)) {
                    $ok = false;
                    break;
                }
            }
            if ($ok) {
                $occurrences[$dt] = $eventsAtDt;
            }
        }
    }

    debugLog("Found " . count($occurrences) . " occurrences after filtering");

    // Build response, enriching each occurrence with data
    $output = [];
    foreach (array_keys($occurrences) as $idx => $dt) {
        debugLog("Processing occurrence " . ($idx + 1) . " at $dt");
        list($occ_date, $occ_time) = explode(' ', $dt);

        // Fetch ALL events that happened at this occurrence time (similar scene)
        $allEventsSql = "
            SELECT * FROM economic_events
             WHERE event_date = :date
               AND event_time = :time";
        
        $allEventsParams = [':date' => $occ_date, ':time' => $occ_time];
        
        // Apply impact filter if provided
        if ($impact) {
            $impactLevels = array_map('trim', explode(',', $impact));
            $impactPlaceholders = [];
            foreach ($impactLevels as $i => $level) {
                $ph = ":impact{$i}";
                $impactPlaceholders[] = $ph;
                $allEventsParams[$ph] = ucfirst(strtolower($level));
            }
            $allEventsSql .= " AND impact_level IN (" . implode(',', $impactPlaceholders) . ")";
        }
        
        $allEventsSql .= " ORDER BY impact_level DESC, event_name ASC";
        
        $allStmt = $pdo->prepare($allEventsSql);
        $allStmt->execute($allEventsParams);
        $eventsToFormat = $allStmt->fetchAll(PDO::FETCH_ASSOC);

        // —— original event formatting —— 
        $items = [];
        foreach ($eventsToFormat as $ev) {
            // Apply TBD replacement if requested
            if ($tbd) {
                $ev["actual_value"] = "TBD";
            }
            
            if ($display === 'min') {
                $item = [
                    "event_name"     => $ev["event_name"],
                    "forecast_value" => $ev["forecast_value"],
                    "actual_value"   => $ev["actual_value"],
                    "previous_value" => $ev["previous_value"]
                ];
                if ($ev["currency"] !== "USD") {
                    $item["currency"] = $ev["currency"];
                }
            } else {
                $item = $ev;
                unset($item['event_date'], $item['event_time']);
            }
            $items[] = $item;
        }

        // —— fetch T-1 to T+5-minute window using Market Data API —— 
        $tsEvent = strtotime("$occ_date $occ_time");
        // End time is T+5 (5 minutes after event)
        $endDT = date('Y-m-d H:i:s', $tsEvent + 300);

        // Get API key for internal market data request
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE key = 'api_key'");
        $stmt->execute();
        $result = $stmt->fetch();
        $apiKey = $result ? $result['value'] : '';

        debugLog("Fetching 6 M1 candles (T-1 to T+5) ending at $endDT");
        
        // Fetch 6 candles: T-1, T, T+1, T+2, T+3, T+4, T+5
        $candlesData = fetch_market_data($symbol, $endDT, $apiKey, 6, 'M1');
        
        debugLog("Market data window fetched - " . (isset($candlesData['error']) ? "ERROR: " . $candlesData['error'] : count($candlesData) . " candles"));
        
        if (isset($candlesData['error'])) {
            $window = [];
        } else {
            // Format candles based on display mode
            $window = [];
            foreach ($candlesData as $candle) {
                if ($display === 'min') {
                    $window[] = [
                        'time' => $candle['time'] ?? ($candle['date'] . ' ' . $candle['time']),
                        'open' => $candle['open'],
                        'high' => $candle['high'],
                        'low' => $candle['low'],
                        'close' => $candle['close']
                    ];
                } else {
                    $window[] = $candle;
                }
            }
        }

        // —— extra candles: 30m, 1h, 4h, close-of-day using Market Data API —— 
        debugLog("Fetching additional timeframe candles using M1 aggregation");
        
        // Calculate precise end times for each timeframe from event time
        $candles = [];
        
        // 30min candle: fetch 30 M1 candles from T to T+30
        $end30 = date('Y-m-d H:i:s', $tsEvent + 30*60);
        debugLog("Fetching 30 M1 candles for candle_30min ending at $end30");
        $m1_30min = fetch_market_data($symbol, $end30, $apiKey, 30, 'M1');
        if (!isset($m1_30min['error']) && !empty($m1_30min)) {
            // Aggregate 30 M1 candles into one 30min candle
            $open = $m1_30min[0]['open'];
            $close = end($m1_30min)['close'];
            $high = max(array_column($m1_30min, 'high'));
            $low = min(array_column($m1_30min, 'low'));
            
            if ($display === 'min') {
                $candles['candle_30min'] = [
                    'time' => $end30,
                    'open' => $open,
                    'high' => $high,
                    'low' => $low,
                    'close' => $close
                ];
            } else {
                $candles['candle_30min'] = [
                    'time' => $end30,
                    'open' => $open,
                    'high' => $high,
                    'low' => $low,
                    'close' => $close,
                    'tick_volume' => array_sum(array_column($m1_30min, 'tick_volume'))
                ];
            }
        } else {
            $candles['candle_30min'] = null;
        }
        
        // 1h candle: fetch 60 M1 candles from T to T+1h
        $end1h = date('Y-m-d H:i:s', $tsEvent + 3600);
        debugLog("Fetching 60 M1 candles for candle_1h ending at $end1h");
        $m1_1h = fetch_market_data($symbol, $end1h, $apiKey, 60, 'M1');
        if (!isset($m1_1h['error']) && !empty($m1_1h)) {
            // Aggregate 60 M1 candles into one 1h candle
            $open = $m1_1h[0]['open'];
            $close = end($m1_1h)['close'];
            $high = max(array_column($m1_1h, 'high'));
            $low = min(array_column($m1_1h, 'low'));
            
            if ($display === 'min') {
                $candles['candle_1h'] = [
                    'time' => $end1h,
                    'open' => $open,
                    'high' => $high,
                    'low' => $low,
                    'close' => $close
                ];
            } else {
                $candles['candle_1h'] = [
                    'time' => $end1h,
                    'open' => $open,
                    'high' => $high,
                    'low' => $low,
                    'close' => $close,
                    'tick_volume' => array_sum(array_column($m1_1h, 'tick_volume'))
                ];
            }
        } else {
            $candles['candle_1h'] = null;
        }
        
        // 4h candle: fetch 240 M1 candles from T to T+4h
        $end4h = date('Y-m-d H:i:s', $tsEvent + 4*3600);
        debugLog("Fetching 240 M1 candles for candle_4h ending at $end4h");
        $m1_4h = fetch_market_data($symbol, $end4h, $apiKey, 240, 'M1');
        if (!isset($m1_4h['error']) && !empty($m1_4h)) {
            // Aggregate 240 M1 candles into one 4h candle
            $open = $m1_4h[0]['open'];
            $close = end($m1_4h)['close'];
            $high = max(array_column($m1_4h, 'high'));
            $low = min(array_column($m1_4h, 'low'));
            
            if ($display === 'min') {
                $candles['candle_4h'] = [
                    'time' => $end4h,
                    'open' => $open,
                    'high' => $high,
                    'low' => $low,
                    'close' => $close
                ];
            } else {
                $candles['candle_4h'] = [
                    'time' => $end4h,
                    'open' => $open,
                    'high' => $high,
                    'low' => $low,
                    'close' => $close,
                    'tick_volume' => array_sum(array_column($m1_4h, 'tick_volume'))
                ];
            }
        } else {
            $candles['candle_4h'] = null;
        }
        
        debugLog("Fetching close-of-day candle");
        // close-of-day - fetch 1 daily candle ending at end of event day
        $endOfDay = strtotime("$occ_date 23:59:59");
        $closeDayCandles = fetch_market_data($symbol, date('Y-m-d H:i:s', $endOfDay), $apiKey, 1, 'D1');
        
        if (!isset($closeDayCandles['error']) && !empty($closeDayCandles)) {
            $lastCandle = end($closeDayCandles);
            if ($display === 'min') {
                $candles['candle_close_day'] = [
                    'time' => $lastCandle['time'] ?? ($lastCandle['date'] . ' ' . $lastCandle['time']),
                    'open' => $lastCandle['open'],
                    'high' => $lastCandle['high'],
                    'low' => $lastCandle['low'],
                    'close' => $lastCandle['close']
                ];
            } else {
                $candles['candle_close_day'] = $lastCandle;
            }
        } else {
            $candles['candle_close_day'] = null;
        }

        // —— assemble this occurrence's entry —— 
        debugLog("Assembling occurrence " . ($idx + 1) . " response");
        $output["occurrence_" . ($idx + 1)] = array_merge([
            'occurrence_date' => $occ_date,
            'occurrence_time' => $occ_time,
            'events'          => $items,
            strtolower($symbol) . '_data' => $window,
        ], $candles);
    }

    debugLog("All occurrences processed, sending JSON response");
    echo json_encode(['vestor_data' => $output], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode(['vestor_data' => ['error' => $e->getMessage()]]);
}
?>
