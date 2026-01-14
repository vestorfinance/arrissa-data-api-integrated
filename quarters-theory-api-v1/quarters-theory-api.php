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
 *  Date:    2026-01-13
 * ------------------------------------------------------------------------
 */

// quarters-theory-api.php

// Increase max execution time for heavy calculations
set_time_limit(60);  // 60 seconds

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
$debugFile = $baseDir . 'quarters-theory-debug.log';

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
// 1) EA POST: receive quarters theory data & write response
//--------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'] ?? '';
    $symbol = $_POST['symbol'] ?? '';
    $quarters_data = $_POST['quarters_data'] ?? '';

    if (!$request_id || !$quarters_data) {
        http_response_code(400);
        debug_log("EA POST missing request_id or quarters_data: request_id='$request_id'");
        echo json_encode(['arrissa_data' => ['error'=>'Missing request_id or quarters_data']]);
        exit;
    }

    // Parse the JSON string into an object
    $quarters_data_parsed = json_decode($quarters_data, true);
    
    // If parsing failed, use the raw string
    if (json_last_error() !== JSON_ERROR_NONE) {
        debug_log("Failed to parse quarters_data JSON: " . json_last_error_msg());
        $quarters_data_parsed = $quarters_data;
    }

    // Build response
    $responseData = [
        'request_id' => $request_id,
        'symbol' => $symbol,
        'quarters_data' => $quarters_data_parsed,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    // Store in queue WITHOUT arrissa_data wrapper (internal communication)
    $resFile = "$queueDir/{$request_id}.res.json";
    file_put_contents($resFile, json_encode($responseData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    debug_log("EA POST wrote quarters theory response for request_id=$request_id symbol=$symbol");

    // Return to EA WITH arrissa_data wrapper
    echo json_encode(['arrissa_data' => ['status'=>'ok']]);
    exit;
}

//--------------------------------------------
// 2) Client GET: Quarters Theory Data Request
//--------------------------------------------
// GET Request Handling
$symbol = $_GET['symbol'] ?? null;

if ($symbol) {
    // Authenticate API request (only for client requests with symbol)
    authenticate();

    $request_id = uniqid('quarters_', true);
    $reqFile = "$queueDir/{$request_id}.req.json";
    $resFile = "$queueDir/{$request_id}.res.json";

    $requestData = [
        'request_id' => $request_id,
        'symbol' => $symbol
    ];

    // Store request WITHOUT arrissa_data wrapper (internal communication)
    file_put_contents($reqFile, json_encode($requestData, JSON_UNESCAPED_SLASHES));
    debug_log("Client GET enqueued quarters theory request_id=$request_id for symbol=$symbol");

    $start = time();
    $timeout = 10;
    while (time() - $start < $timeout) {
        if (file_exists($resFile)) {
            $response = json_decode(file_get_contents($resFile), true);
            if (!empty($response['request_id']) && $response['request_id'] === $request_id) {
                @unlink($reqFile);
                @unlink($resFile);
                // Return to client WITH arrissa_data wrapper and pretty printing
                echo json_encode(['arrissa_data' => $response], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
                exit;
            }
        }
        usleep(200000); // 200ms
    }

    http_response_code(503);
    debug_log("Client GET timeout waiting for EA for request_id=$request_id");
    echo json_encode(['arrissa_data' => [
        'error'=>'MT5 Data Server not connected',
        'message'=>'No Expert Advisor (EA) is currently running to process this quarters theory request. Please ensure your MT5 terminal is running with the Richchild Quarters Theory EA attached.'
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
        debug_log("EA polling, returning pending quarters theory request: $raw");
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
echo json_encode(['arrissa_data' => ['status' => 'polling', 'message'=>'No pending quarters theory requests']]);
exit;
