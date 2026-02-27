<?php
/**
 * ------------------------------------------------------------------------
 *  Author : Ngonidzashe Jiji
 *  Handles: Instagram: @davidrichchild
 *           Telegram: t.me/david_richchild
 *           TikTok: davidrichchild
 *  URLs    : https://arrissadata.com
 *            https://arrissatechnologies.com
 *            https://arrissa.trade
 *
 *  Course  : https://www.udemy.com/course/6804721
 *
 *  Permission:
 *    You are granted permission to use, copy, modify, and distribute this
 *    software and its source code for personal or commercial projects,
 *    provided that the author details above remain intact and visible in
 *    the distributed software (including any compiled or minified form).
 *
 *  Requirements:
 *    - Keep the author name, handles, URLs, and course link in this header
 *      (or an equivalent attribution location in distributed builds).
 *    - You may NOT remove or obscure the attribution.
 *
 *  Disclaimer:
 *    This software is provided "AS IS", without warranty of any kind,
 *    express or implied. The author is not liable for any claim, damages,
 *    or other liability arising from the use of this software.
 *
 *  Version: 1.0
 *  Date:    2025-01-27
 * ------------------------------------------------------------------------
 */

// symbol-info-api.php

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
$debugFile = $baseDir . 'daily-average-debug.log';

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

// Validate timeframe parameter
function validate_timeframe($timeframe) {
    $validTimeframes = ['M5', 'M15', 'M30', 'H1', 'H4', 'H8', 'H12', 'D1', 'W1', 'M'];
    return in_array($timeframe, $validTimeframes);
}

// Get default lookback for timeframe
function get_default_lookback($timeframe) {
    $defaults = [
        'M5' => 288,  // 1 day worth of 5-minute periods
        'M15' => 96,  // 1 day worth of 15-minute periods
        'M30' => 48,  // 1 day worth of 30-minute periods
        'H1' => 24,   // 1 day worth of hourly periods
        'H4' => 30,   // ~5 days worth of 4-hour periods
        'H8' => 21,   // ~7 days worth of 8-hour periods
        'H12' => 14,  // ~7 days worth of 12-hour periods
        'D1' => 30,   // 30 daily periods
        'W1' => 12,   // 12 weekly periods
        'M' => 12     // 12 monthly periods
    ];
    return $defaults[$timeframe] ?? 30;
}

// Validate lookback parameter (timeframe-specific periods)
function validate_lookback($lookback, $timeframe) {
    if (!is_numeric($lookback) || $lookback <= 0) {
        return false;
    }
    
    // Set reasonable limits based on timeframe
    $maxLimits = [
        'M5' => 2000,   // ~7 days max
        'M15' => 1000,  // ~10 days max
        'M30' => 500,   // ~10 days max
        'H1' => 500,    // ~20 days max
        'H4' => 200,    // ~33 days max
        'H8' => 100,    // ~33 days max
        'H12' => 60,    // ~30 days max
        'D1' => 1000,   // ~3 years max
        'W1' => 200,    // ~4 years max
        'M' => 120      // ~10 years max
    ];
    
    $maxLimit = $maxLimits[$timeframe] ?? 1000;
    return $lookback <= $maxLimit;
}

// Validate ignore_sunday parameter
function validate_ignore_sunday($ignore_sunday) {
    return in_array(strtolower($ignore_sunday), ['true', 'false', '1', '0']);
}

// Convert ignore_sunday to boolean
function parse_ignore_sunday($ignore_sunday) {
    return in_array(strtolower($ignore_sunday), ['true', '1']);
}

// Authentication function
function authenticate() {
    global $db;
    $api_key = $_GET['api_key'] ?? null;
    if (!$api_key) {
        http_response_code(401);
        echo json_encode(['arrissa_data' => ['error'=>'Missing API key']]);
        exit;
    }
    
    $result = $db->fetchOne("SELECT value FROM settings WHERE key = 'api_key'");
    $validKey = $result ? $result['value'] : '';
    
    if ($api_key !== $validKey) {
        http_response_code(401);
        echo json_encode(['arrissa_data' => ['error'=>'Invalid API key']]);
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
// 1) EA POST: receive analysis data & write response
//--------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'] ?? '';
    $symbol = $_POST['symbol'] ?? '';
    $timeframe = $_POST['timeframe'] ?? 'D1';
    $lookback = $_POST['lookback'] ?? '';
    $ignore_sunday = $_POST['ignore_sunday'] ?? 'true';
    $behaviorData = $_POST['symbol_behaviour_data'] ?? '';

    if (!$request_id || !$behaviorData) {
        http_response_code(400);
        debug_log("EA POST missing request_id or symbol_behaviour_data: request_id='$request_id'");
        echo json_encode(['arrissa_data' => ['error'=>'Missing request_id or symbol_behaviour_data']]);
        exit;
    }

    // Build response
    $responseData = [
        'request_id' => $request_id,
        'symbol' => $symbol,
        'timeframe' => $timeframe,
        'lookback' => $lookback,
        'ignore_sunday' => $ignore_sunday,
        'symbol_behaviour_data' => $behaviorData
    ];

    // Add pretend date/time if provided
    foreach (['pretend_date','pretend_time'] as $k) {
        if (isset($_POST[$k])) {
            $responseData[$k] = $_POST[$k];
        }
    }

    // Store in queue WITHOUT arrissa_data wrapper (internal communication)
    $resFile = "$queueDir/{$request_id}.res.json";
    file_put_contents($resFile, json_encode($responseData, JSON_UNESCAPED_SLASHES));
    debug_log("EA POST wrote analysis response for request_id=$request_id timeframe=$timeframe lookback=$lookback periods ignore_sunday=$ignore_sunday");

    // Return to EA WITH arrissa_data wrapper
    echo json_encode(['arrissa_data' => ['status'=>'ok']]);
    exit;
}

//--------------------------------------------
// 2) Client GET: Multi-Parameter Analysis Request
//--------------------------------------------
// GET Request Handling
$symbol = $_GET['symbol'] ?? null;
$timeframe = $_GET['timeframe'] ?? 'D1'; // Default to D1 if not specified
$lookback = $_GET['lookback'] ?? ''; // Will be set to default based on timeframe
$ignore_sunday = $_GET['ignore_sunday'] ?? 'true'; // Default to true if not specified
$pretend_date = $_GET['pretend_date'] ?? null;
$pretend_time = $_GET['pretend_time'] ?? null;

if ($symbol) {
    // Authenticate API request (only for client requests with symbol)
    authenticate();
    // Validate timeframe first
    if (!validate_timeframe($timeframe)) {
        debug_log("Invalid timeframe provided: $timeframe");
        echo json_encode(['arrissa_data' => ['success' => false, 'error' => 'Invalid timeframe — please check your parameters', 'hint' => 'Valid options: M5, M15, M30, H1, H4, H8, H12, D1, W1, M']]);
        exit;
    }

    // Set default lookback if not provided
    if (empty($lookback)) {
        $lookback = get_default_lookback($timeframe);
    }

    // Validate lookback (timeframe-specific)
    if (!validate_lookback($lookback, $timeframe)) {
        debug_log("Invalid lookback provided: $lookback for timeframe: $timeframe");
        
        $maxLimits = [
            'M5' => 2000, 'M15' => 1000, 'M30' => 500, 'H1' => 500,
            'H4' => 200, 'H8' => 100, 'H12' => 60, 'D1' => 1000,
            'W1' => 200, 'M' => 120
        ];
        $maxLimit = $maxLimits[$timeframe] ?? 1000;
        
        echo json_encode(['arrissa_data' => ['success' => false, 'error' => "Invalid lookback — please check your parameters", 'hint' => "For $timeframe timeframe, lookback must be between 1 and $maxLimit periods"]]);
        exit;
    }

    // Validate ignore_sunday
    if (!validate_ignore_sunday($ignore_sunday)) {
        debug_log("Invalid ignore_sunday provided: $ignore_sunday");
        echo json_encode(['arrissa_data' => ['success' => false, 'error' => 'Invalid ignore_sunday — please check your parameters', 'hint' => 'Valid options: true, false, 1, 0']]);
        exit;
    }

    // No authentication for this standalone version
    // authenticate(); // Uncomment if using with user authentication

    $request_id = uniqid('analysis_', true);
    $reqFile = "$queueDir/{$request_id}.req.json";
    $resFile = "$queueDir/{$request_id}.res.json";

    $requestData = [
        'request_id' => $request_id,
        'symbol' => $symbol,
        'timeframe' => $timeframe,
        'lookback' => (int)$lookback,
        'ignore_sunday' => parse_ignore_sunday($ignore_sunday)
    ];

    // Handle pretend mode vs legacy mode
    if ($pretend_date && $pretend_time) {
        $requestData['pretend_date'] = $pretend_date;
        $requestData['pretend_time'] = $pretend_time;
        debug_log("Client GET analysis request with pretend date/time: $pretend_date $pretend_time timeframe: $timeframe lookback: $lookback periods ignore_sunday: $ignore_sunday");
    } else {
        debug_log("Client GET analysis request in legacy mode (current time) timeframe: $timeframe lookback: $lookback periods ignore_sunday: $ignore_sunday");
    }

    // Store request WITHOUT arrissa_data wrapper (internal communication)
    file_put_contents($reqFile, json_encode($requestData, JSON_UNESCAPED_SLASHES));
    debug_log("Client GET enqueued analysis request_id=$request_id for symbol=$symbol timeframe=$timeframe lookback=$lookback periods ignore_sunday=$ignore_sunday");

    $start = time();
    $timeout = 10;
    while (time() - $start < $timeout) {
        if (file_exists($resFile)) {
            $response = json_decode(file_get_contents($resFile), true);
            if (!empty($response['request_id']) && $response['request_id'] === $request_id) {
                @unlink($reqFile);
                @unlink($resFile);
                // Return to client WITH arrissa_data wrapper
                echo json_encode(['arrissa_data' => $response], JSON_UNESCAPED_SLASHES);
                exit;
            }
        }
        usleep(200000); // 200ms
    }

    http_response_code(503);
    debug_log("Client GET timeout waiting for EA for request_id=$request_id timeframe=$timeframe lookback=$lookback periods ignore_sunday=$ignore_sunday");
    echo json_encode(['arrissa_data' => [
        'error'=>'MT5 Data Server not connected',
        'message'=>'No Expert Advisor (EA) is currently running to process this analysis request. Please ensure your MT5 terminal is running with the Symbol Info EA attached.'
    ]]);
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
        debug_log("EA polling, returning pending analysis request: $raw");
        @unlink($file); // hand it out only once
        // Return raw request data to EA (no arrissa_data wrapper for internal communication)
        echo $raw;
        exit;
    }
}

//--------------------------------------------
// 4) No params & no pending request
//--------------------------------------------
debug_log("No pending request and no query params");
echo json_encode(['arrissa_data' => ['status' => 'polling', 'message'=>'No pending analysis requests']]);
exit;