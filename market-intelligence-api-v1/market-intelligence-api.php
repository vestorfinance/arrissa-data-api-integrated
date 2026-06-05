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
 *  Date:    2026-06-04
 * ------------------------------------------------------------------------
 */

// market-intelligence-api.php
//
// Queue-based bridge between the Market Intelligence EA (MT5) and API clients.
//
// Flow:
//   Client → GET  ?symbol=GBPUSD&api_key=XXX
//          → creates .req.json, polls for .res.json
//   EA     → GET  (no params)    → receives next .req.json
//   EA     → POST request_id + payload → writes .res.json
//   Client ← receives arrissa_data with full monthly report + numeric data
//
// Output modes:
//   Default (text/plain): formatted markdown report
//   ?format=json        : full JSON payload with report sections and data
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
$debugFile = "{$baseDir}market-intelligence-debug.log";

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
// 1) EA POST: receive computed payload and store as response
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

    $symbol = strtoupper(trim($symbol));

    $request_id = uniqid('mi_', true);
    $reqFile    = "$queueDir/{$request_id}.req.json";
    $resFile    = "$queueDir/{$request_id}.res.json";

    // Normalise timeframe: upper-case, strip spaces around commas
    $timeframe = strtoupper(trim($_GET['timeframe'] ?? 'MN1'));
    if ($timeframe === '') $timeframe = 'MN1';
    // Tidy comma-separated list: "H1, M30 , M15" → "H1,M30,M15"
    if (strpos($timeframe, ',') !== false) {
        $timeframe = implode(',', array_map('trim', explode(',', $timeframe)));
    }

    $requestData = [
        'request_id' => $request_id,
        'symbol'     => $symbol,
        'timeframe'  => $timeframe,
    ];

    // Optional backtesting params — passed through to the EA unchanged
    if (!empty($_GET['pretend_date'])) $requestData['pretend_date'] = $_GET['pretend_date'];
    if (!empty($_GET['pretend_time'])) $requestData['pretend_time'] = $_GET['pretend_time'];

    file_put_contents($reqFile, json_encode($requestData, JSON_UNESCAPED_SLASHES));
    debug_log("Client GET enqueued request_id=$request_id symbol=$symbol");

    $start   = time();
    $isMultiTF = ($timeframe === 'ALL' || strpos($timeframe, ',') !== false);
    $tfCount   = $isMultiTF ? ($timeframe === 'ALL' ? 9 : substr_count($timeframe, ',') + 1) : 1;
    $timeout   = $tfCount > 1 ? max(25, min(45, 10 + $tfCount * 4)) : 20;

    $jsonMode = (strtolower($_GET['format'] ?? '') === 'json');

    while (time() - $start < $timeout) {
        if (file_exists($resFile)) {
            $response = json_decode(file_get_contents($resFile), true);
            if (!empty($response['request_id']) && $response['request_id'] === $request_id) {
                @unlink($reqFile);
                @unlink($resFile);

                if ($jsonMode) {
                    echo json_encode(
                        ['arrissa_data' => $response],
                        JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
                    );
                    exit;
                }

                // Default: formatted markdown report
                header('Content-Type: text/plain; charset=utf-8');

                $payload    = $response['payload'] ?? [];
                $sym        = $payload['symbol']      ?? $symbol;
                $serverTime = $payload['server_time'] ?? date('Y-m-d H:i:s');
                $date       = substr($serverTime, 0, 10);

                $lines = [];

                // --- helper: emit one TF report block ---
                $emitReport = function(string $tfLabel, array $report) use (&$lines, $sym, $date) {
                    $lines[] = "# Market Intelligence Report — {$sym} [{$tfLabel}] — {$date}";
                    $lines[] = '';

                    if (!empty($report['price_history'])) {
                        $lines[] = '## Price History';
                        $lines[] = '';
                        $lines[] = $report['price_history'];
                        $lines[] = '';
                    }
                    if (!empty($report['market_structure'])) {
                        $lines[] = '## Market Structure';
                        $lines[] = '';
                        $lines[] = $report['market_structure'];
                        $lines[] = '';
                    }
                    if (!empty($report['range_position'])) {
                        $lines[] = '## Range Position';
                        $lines[] = '';
                        $lines[] = $report['range_position'];
                        $lines[] = '';
                    }
                    if (!empty($report['percentile_ranking'])) {
                        $lines[] = '## Percentile Ranking';
                        $lines[] = '';
                        $lines[] = $report['percentile_ranking'];
                        $lines[] = '';
                    }
                    if (!empty($report['drawdown'])) {
                        $lines[] = '## Drawdown';
                        $lines[] = '';
                        $lines[] = $report['drawdown'];
                        if (!empty($report['drawdown_context'])) {
                            $lines[] = $report['drawdown_context'];
                        }
                        $lines[] = '';
                    }
                    if (!empty($report['volatility'])) {
                        $lines[] = '## Volatility';
                        $lines[] = '';
                        $lines[] = $report['volatility'];
                        $lines[] = '';
                    }
                    if (!empty($report['moving_averages'])) {
                        $lines[] = '## Moving Averages';
                        $lines[] = '';
                        $lines[] = $report['moving_averages'];
                        $lines[] = '';
                    }
                    if (!empty($report['candle_behaviour'])) {
                        $lines[] = '## Candle Behaviour';
                        $lines[] = '';
                        $lines[] = $report['candle_behaviour'];
                        $lines[] = '';
                        if (!empty($report['last_candle'])) {
                            $lines[] = '**Last completed candle:**';
                            $lines[] = $report['last_candle'];
                            $lines[] = '';
                        }
                        if (!empty($report['current_candle'])) {
                            $lines[] = '**Current candle (still forming):**';
                            $lines[] = $report['current_candle'];
                            $lines[] = '';
                        }
                    }
                    if (!empty($report['seasonal_month']) || !empty($report['seasonal_quarter'])) {
                        $lines[] = '## Seasonal Statistics';
                        $lines[] = '';
                        if (!empty($report['seasonal_month']))   { $lines[] = $report['seasonal_month'];   $lines[] = ''; }
                        if (!empty($report['seasonal_quarter'])) { $lines[] = $report['seasonal_quarter']; $lines[] = ''; }
                    }
                    if (!empty($report['dataset_note'])) {
                        $lines[] = $report['dataset_note'];
                        $lines[] = '';
                    }
                    $lines[] = '---';
                    $lines[] = '';
                };

                // --- "all" mode: payload has 'timeframes' => ['MN1'=>{report,data}, ...] ---
                if (!empty($payload['timeframes']) && is_array($payload['timeframes'])) {
                    foreach ($payload['timeframes'] as $tfLabel => $tfBlock) {
                        $report = $tfBlock['report'] ?? [];
                        $emitReport((string)$tfLabel, $report);
                    }
                } else {
                    // Single TF mode
                    $tfLabel = $payload['timeframe'] ?? 'MN1';
                    $report  = $payload['report']    ?? [];
                    $emitReport($tfLabel, $report);
                }

                echo implode("\n", $lines);
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
        'message' => 'No Expert Advisor is currently running to process this request. Attach the Market Intelligence API EA in your MT5 terminal.',
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
        @unlink($file);
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
    'message' => 'No pending Market Intelligence requests',
]]);
exit;
