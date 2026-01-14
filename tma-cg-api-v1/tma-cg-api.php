<?php
/**
 * TMA+CG API Endpoint
 * Returns premium/discount zone data with percentage
 */

// Increase max execution time
set_time_limit(60);

// Toggle debug logging
$debugEnabled = false;

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Always return JSON
header('Content-Type: application/json');

// Load database
require_once __DIR__ . '/../app/Database.php';
$db = Database::getInstance();

$baseDir   = __DIR__ . '/';
$queueDir  = $baseDir . 'queue';
$debugFile = $baseDir . 'tma-cg-debug.log';

// Ensure queue directory exists
if (!is_dir($queueDir)) {
    mkdir($queueDir, 0755, true);
}

// Append to debug log with timestamp
function debug_log($message) {
    global $debugFile, $debugEnabled;
    if (!$debugEnabled) return;
    $time = date('Y-m-d H:i:s');
    @file_put_contents($debugFile, "[$time] $message\n", FILE_APPEND | LOCK_EX);
}

// Authentication function
function authenticate() {
    global $db;
    $api_key = $_GET['api_key'] ?? null;
    if (!$api_key) {
        http_response_code(401);
        echo json_encode(['error'=>'Missing API key']);
        exit;
    }
    
    $result = $db->fetchOne("SELECT value FROM settings WHERE key = 'api_key'");
    $validKey = $result ? $result['value'] : '';
    
    if ($api_key !== $validKey) {
        http_response_code(401);
        echo json_encode(['error'=>'Invalid API key']);
        exit;
    }
}

// Garbage-collect stale files older than 60 s
foreach (glob("$queueDir/*.req.json") as $f) {
    if (!is_file($f) || filemtime($f) === false) {
        continue;
    }
    if (filemtime($f) < time() - 60) {
        @unlink($f);
        @unlink(str_replace('.req.json', '.res.json', $f));
    }
}
foreach (glob("$queueDir/*.res.json") as $f) {
    if (!is_file($f) || filemtime($f) === false) {
        continue;
    }
    if (filemtime($f) < time() - 60) {
        @unlink($f);
    }
}

//--------------------------------------------
// 1) EA POST: receive TMA+CG data & write response
//--------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'] ?? '';
    $symbol = $_POST['symbol'] ?? '';
    $tma_data = $_POST['tma_data'] ?? '';

    if (!$request_id || !$tma_data) {
        http_response_code(400);
        debug_log("EA POST missing request_id or tma_data: request_id='$request_id'");
        echo json_encode(['error'=>'Missing request_id or tma_data']);
        exit;
    }

    // Parse the JSON string into an object
    $tma_data_parsed = json_decode($tma_data, true);
    
    // If parsing failed, use the raw string
    if (json_last_error() !== JSON_ERROR_NONE) {
        debug_log("Failed to parse tma_data JSON: " . json_last_error_msg());
        $tma_data_parsed = $tma_data;
    }

    // Build response
    $responseData = [
        'request_id' => $request_id,
        'symbol' => $symbol,
        'tma_data' => $tma_data_parsed,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    // Store in queue
    $resFile = "$queueDir/{$request_id}.res.json";
    file_put_contents($resFile, json_encode($responseData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    debug_log("EA POST wrote TMA+CG response for request_id=$request_id symbol=$symbol");

    // Return to EA
    echo json_encode(['status'=>'ok']);
    exit;
}

//--------------------------------------------
// 2) Client GET: TMA+CG Data Request
//--------------------------------------------
$symbol = $_GET['symbol'] ?? null;
$timeframe = $_GET['timeframe'] ?? 'M1';

if ($symbol) {
    // Authenticate API request
    authenticate();

    $request_id = uniqid('tmacg_', true);
    $reqFile = "$queueDir/{$request_id}.req.json";
    $resFile = "$queueDir/{$request_id}.res.json";

    $requestData = [
        'request_id' => $request_id,
        'symbol' => $symbol,
        'timeframe' => $timeframe
    ];

    // Store request
    file_put_contents($reqFile, json_encode($requestData, JSON_UNESCAPED_SLASHES));
    debug_log("Client GET enqueued TMA+CG request_id=$request_id for symbol=$symbol timeframe=$timeframe");

    $start = time();
    $timeout = 15;
    while (time() - $start < $timeout) {
        if (file_exists($resFile)) {
            $response = json_decode(file_get_contents($resFile), true);
            if (!empty($response['request_id']) && $response['request_id'] === $request_id) {
                @unlink($reqFile);
                @unlink($resFile);
                
                // Extract TMA data
                $tmaData = $response['tma_data'] ?? [];
                
                // Return formatted response
                echo json_encode([
                    'status' => 'success',
                    'symbol' => $tmaData['symbol'] ?? $symbol,
                    'timeframe' => $tmaData['timeframe'] ?? $timeframe,
                    'zone' => $tmaData['zone'] ?? 'unknown',
                    'percentage' => $tmaData['percentage'] ?? 0.0,
                    'current_price' => $tmaData['current_price'] ?? 0.0,
                    'tma_middle' => $tmaData['tma_middle'] ?? 0.0,
                    'upper_band_1' => $tmaData['upper_band_1'] ?? 0.0,
                    'lower_band_1' => $tmaData['lower_band_1'] ?? 0.0,
                    'upper_band_7' => $tmaData['upper_band_7'] ?? 0.0,
                    'lower_band_7' => $tmaData['lower_band_7'] ?? 0.0,
                    'timestamp' => $tmaData['timestamp'] ?? $response['timestamp']
                ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
                exit;
            }
        }
        usleep(200000); // 200ms
    }

    http_response_code(503);
    debug_log("Client GET timeout waiting for EA for request_id=$request_id");
    echo json_encode([
        'status' => 'timeout',
        'error'=>'MT5 Data Server not connected',
        'message'=>'No Expert Advisor (EA) is currently running to process this TMA+CG request. Please ensure your MT5 terminal is running with the TMA CG Data EA attached.'
    ]);
    exit;
}

//--------------------------------------------
// 3) EA polling GET (no params) → return next pending request
//--------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_GET)) {
    $pending = glob("$queueDir/*.req.json");
    if (!empty($pending)) {
        sort($pending); // oldest first
        $file = $pending[0];
        $raw = file_get_contents($file);
        debug_log("EA polling, returning pending TMA+CG request: $raw");
        @unlink($file); // hand it out only once
        // Return raw request data to EA
        echo $raw;
        exit;
    }
}

//--------------------------------------------
// 4) No params & no pending request
//--------------------------------------------
debug_log("No pending request and no query params");
echo json_encode(['status' => 'polling', 'message'=>'No pending TMA+CG requests']);
exit;
