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
 *  Date:    2026-05-20
 * ------------------------------------------------------------------------
 */

// risk-management-api.php
//
// Queue-based bridge between the Risk Management EA (MT5) and API clients.
//
// Flow:
//   Client → GET  ?symbol=GBPUSD&direction=BUY&trade_type=scalp&api_key=XXX
//          → creates .req.json, polls for .res.json
//   EA     → GET  (no params)              → receives next .req.json
//   EA     → POST request_id + payload     → writes .res.json
//   Client ← receives arrissa_data with sl, tp, sl_pips, tp_pips, rr_ratio
//

set_time_limit(60);

$debugEnabled = false;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

require_once __DIR__ . '/../app/Database.php';
$db = Database::getInstance();

$baseDir   = __DIR__ . '/';
$queueDir  = "{$baseDir}queue";
$debugFile = "{$baseDir}risk-management-debug.log";

if (!is_dir($queueDir)) {
    mkdir($queueDir, 0755, true);
}

function debug_log($message)
{
    global $debugFile, $debugEnabled;
    if (!$debugEnabled) return;
    $time = date('Y-m-d H:i:s');
    @file_put_contents($debugFile, "[$time] $message\n", FILE_APPEND | LOCK_EX);
}

function authenticate()
{
    global $db;
    $api_key = $_GET['api_key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? null;
    if (!$api_key) {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        exit;
    }
    $result   = $db->fetchOne("SELECT value FROM settings WHERE key = 'api_key'");
    $validKey = $result ? $result['value'] : '';
    if ($api_key !== $validKey) {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        exit;
    }
}

// Garbage-collect stale queue files older than 60 s
foreach (glob("$queueDir/*.req.json") as $f) {
    if (is_file($f) && filemtime($f) < time() - 60) {
        @unlink($f);
        @unlink(str_replace('.req.json', '.res.json', $f));
    }
}
foreach (glob("$queueDir/*.res.json") as $f) {
    if (is_file($f) && filemtime($f) < time() - 60) {
        @unlink($f);
    }
}

//--------------------------------------------------------------------
// 1) EA POST: receive calculated SL/TP payload and store as response
//--------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $request_id = $_POST['request_id'] ?? '';
    $symbol     = $_POST['symbol']     ?? '';
    $payload    = $_POST['payload']    ?? '';

    if (!$request_id || !$payload) {
        http_response_code(400);
        debug_log("EA POST missing request_id or payload: request_id='$request_id'");
        echo json_encode(['arrissa_data' => ['error' => 'Missing request_id or payload']]);
        exit;
    }

    $payload_parsed = json_decode($payload, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        debug_log("Failed to parse payload JSON: " . json_last_error_msg());
        $payload_parsed = $payload;
    }

    $responseData = [
        'request_id' => $request_id,
        'symbol'     => $symbol,
        'payload'    => $payload_parsed,
        'timestamp'  => date('Y-m-d H:i:s'),
    ];

    $resFile = "$queueDir/{$request_id}.res.json";
    file_put_contents($resFile, json_encode($responseData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    debug_log("EA POST wrote response for request_id=$request_id symbol=$symbol");

    echo json_encode(['arrissa_data' => ['status' => 'ok']]);
    exit;
}

//--------------------------------------------------------------------
// 2) Client GET with ?symbol=: enqueue and wait for EA response
//--------------------------------------------------------------------
$symbol = $_GET['symbol'] ?? null;

if ($symbol) {

    authenticate();

    $symbol     = strtoupper(trim($symbol));
    $direction  = strtoupper(trim($_GET['direction']  ?? ''));
    $trade_type = strtolower(trim($_GET['trade_type'] ?? ''));

    $valid_directions  = ['BUY', 'SELL'];
    $valid_trade_types = ['scalp', 'swing', 'long-term'];

    if (!in_array($direction, $valid_directions)) {
        http_response_code(400);
        echo json_encode(['arrissa_data' => [
            'error'   => 'Invalid direction',
            'message' => 'direction must be BUY or SELL',
        ]]);
        exit;
    }

    if (!in_array($trade_type, $valid_trade_types)) {
        http_response_code(400);
        echo json_encode(['arrissa_data' => [
            'error'   => 'Invalid trade_type',
            'message' => 'trade_type must be scalp, swing, or long-term',
        ]]);
        exit;
    }

    $pretend_date = $_GET['pretend_date'] ?? null;
    $pretend_time = $_GET['pretend_time'] ?? null;

    $request_id = uniqid('rm_', true);
    $reqFile    = "$queueDir/{$request_id}.req.json";
    $resFile    = "$queueDir/{$request_id}.res.json";

    $requestData = [
        'request_id' => $request_id,
        'symbol'     => $symbol,
        'direction'  => $direction,
        'trade_type' => $trade_type,
    ];

    if ($pretend_date) {
        $requestData['pretend_date'] = $pretend_date;
    }
    if ($pretend_time) {
        $requestData['pretend_time'] = $pretend_time;
    }

    file_put_contents($reqFile, json_encode($requestData, JSON_UNESCAPED_SLASHES));
    debug_log("Client GET enqueued request_id=$request_id symbol=$symbol direction=$direction trade_type=$trade_type");

    $start   = time();
    $timeout = 10;

    while (time() - $start < $timeout) {
        if (file_exists($resFile)) {
            $response = json_decode(file_get_contents($resFile), true);
            if (!empty($response['request_id']) && $response['request_id'] === $request_id) {
                @unlink($reqFile);
                @unlink($resFile);
                echo json_encode(
                    ['arrissa_data' => $response],
                    JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
                );
                exit;
            }
        }
        usleep(200000); // 200 ms
    }

    http_response_code(503);
    @unlink($reqFile);
    debug_log("Timeout waiting for EA for request_id=$request_id");
    echo json_encode(['arrissa_data' => [
        'error'   => 'MT5 Data Server not connected',
        'message' => 'No Expert Advisor is currently running to process this request. Attach the Risk Management API EA in your MT5 terminal.',
    ]]);
    exit;
}

//--------------------------------------------------------------------
// 3) EA polling GET (no params) → hand out the next pending request
//--------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_GET)) {
    $pending = glob("$queueDir/*.req.json");
    if (!empty($pending)) {
        sort($pending); // oldest first
        $file = $pending[0];
        $raw  = file_get_contents($file);
        debug_log("EA polling — returning pending request: $raw");
        @unlink($file); // hand out once only
        echo $raw;
        exit;
    }
}

//--------------------------------------------------------------------
// 4) Idle — nothing pending
//--------------------------------------------------------------------
debug_log("No pending requests");
echo json_encode(['arrissa_data' => [
    'status'  => 'polling',
    'message' => 'No pending Risk Management requests',
]]);
exit;
