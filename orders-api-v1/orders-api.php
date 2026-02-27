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
 *  Date:    2025-09-20
 * ------------------------------------------------------------------------
 */
 
// Set headers first
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Disable error display to prevent HTML in JSON responses
error_reporting(0);
ini_set('display_errors', 0);

// Create queue directory
$queueDir = __DIR__ . '/queue';
if (!is_dir($queueDir)) {
    if (!mkdir($queueDir, 0755, true)) {
        echo json_encode(['error' => 'Failed to create queue directory']);
        exit;
    }
}

// Clean old files
$files = glob("$queueDir/*.json");
if ($files) {
    foreach ($files as $f) {
        if (is_file($f) && filemtime($f) < time() - 60) {
            @unlink($f);
        }
    }
}

// Database connection
require_once __DIR__ . '/../app/Database.php';
$db = Database::getInstance();
$pdo = $db->getConnection();

// Get API key from database
$stmt = $pdo->prepare("SELECT value FROM settings WHERE key = 'api_key'");
$stmt->execute();
$result = $stmt->fetch();
$VALID_API_KEY = $result ? $result['value'] : '';

if (!$VALID_API_KEY) {
    echo json_encode(['error' => 'API key not configured in database settings']);
    exit;
}

// EA POST: receive data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = isset($_POST['request_id']) ? $_POST['request_id'] : '';
    $request_type = isset($_POST['request_type']) ? $_POST['request_type'] : '';

    if (!$request_id) {
        http_response_code(400);
        echo json_encode(['error'=>'Missing request_id']);
        exit;
    }

    if ($request_type === 'orders') {
        $ordersRaw = isset($_POST['orders']) ? $_POST['orders'] : '';
        if (!$ordersRaw) {
            http_response_code(400);
            echo json_encode(['error'=>'Missing orders data']);
            exit;
        }

        $orders = json_decode($ordersRaw, true);
        if (!is_array($orders)) {
            http_response_code(400);
            echo json_encode(['error'=>'Invalid orders JSON']);
            exit;
        }

        $responseData = [
            'request_id' => $request_id,
            'request_type' => 'orders',
            'orders' => $orders,
            'total_orders' => count($orders)
        ];

        $resFile = "$queueDir/{$request_id}.res.json";
        if (file_put_contents($resFile, json_encode($responseData, JSON_UNESCAPED_SLASHES)) === false) {
            echo json_encode(['error' => 'Failed to write response file']);
            exit;
        }
        
        echo json_encode(['status'=>'ok']);
        exit;
    }
    
    if ($request_type === 'trade') {
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        $success = isset($_POST['success']) ? $_POST['success'] : 'false';
        $result = isset($_POST['result']) ? $_POST['result'] : '';
        $error = isset($_POST['error']) ? $_POST['error'] : '';

        $responseData = [
            'request_id' => $request_id,
            'request_type' => 'trade',
            'action' => $action,
            'success' => $success === 'true',
            'result' => $result,
            'error' => $error
        ];

        $resFile = "$queueDir/{$request_id}.res.json";
        if (file_put_contents($resFile, json_encode($responseData, JSON_UNESCAPED_SLASHES)) === false) {
            echo json_encode(['error' => 'Failed to write response file']);
            exit;
        }
        
        echo json_encode(['status'=>'ok']);
        exit;
    }

    if ($request_type === 'history') {
        $historyRaw = isset($_POST['history']) ? $_POST['history'] : '';
        if (!$historyRaw) {
            http_response_code(400);
            echo json_encode(['error'=>'Missing history data']);
            exit;
        }

        $history = json_decode($historyRaw, true);
        if (!is_array($history)) {
            http_response_code(400);
            echo json_encode(['error'=>'Invalid history JSON']);
            exit;
        }

        $responseData = [
            'request_id' => $request_id,
            'request_type' => 'history',
            'history' => $history,
            'total_records' => count($history)
        ];

        $resFile = "$queueDir/{$request_id}.res.json";
        if (file_put_contents($resFile, json_encode($responseData, JSON_UNESCAPED_SLASHES)) === false) {
            echo json_encode(['error' => 'Failed to write response file']);
            exit;
        }
        
        echo json_encode(['status'=>'ok']);
        exit;
    }

    if ($request_type === 'profit') {
        $profitRaw = isset($_POST['profit_data']) ? $_POST['profit_data'] : '';
        if (!$profitRaw) {
            http_response_code(400);
            echo json_encode(['error'=>'Missing profit data']);
            exit;
        }

        $profitData = json_decode($profitRaw, true);
        if (!is_array($profitData)) {
            http_response_code(400);
            echo json_encode(['error'=>'Invalid profit JSON']);
            exit;
        }

        $responseData = [
            'request_id' => $request_id,
            'request_type' => 'profit',
            'profit_data' => $profitData
        ];

        $resFile = "$queueDir/{$request_id}.res.json";
        if (file_put_contents($resFile, json_encode($responseData, JSON_UNESCAPED_SLASHES)) === false) {
            echo json_encode(['error' => 'Failed to write response file']);
            exit;
        }
        
        echo json_encode(['status'=>'ok']);
        exit;
    }
}

// Authentication function - simplified
function authenticate() {
    global $VALID_API_KEY;
    
    $api_key = isset($_GET['api_key']) ? $_GET['api_key'] : null;
    if (!$api_key) {
        http_response_code(404);
        echo json_encode(['error'=>'Not found']);
        exit;
    }
    
    if ($api_key !== $VALID_API_KEY) {
        http_response_code(404);
        echo json_encode(['error'=>'Not found']);
        exit;
    }
    
    return true;
}

// EA polling - this is what the EA calls when it has no parameters
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_GET)) {
    $pending = glob("$queueDir/*.req.json");
    if (!empty($pending)) {
        sort($pending);
        $file = $pending[0];
        $raw = file_get_contents($file);
        @unlink($file);
        echo $raw;
        exit;
    }
    
    // Return a valid JSON response for EA polling
    echo json_encode(['message'=>'No request made yet', 'status' => 'polling']);
    exit;
}

// Client requests
if (isset($_GET['api_key']) || isset($_GET['action']) || isset($_GET['filter_running']) || 
    isset($_GET['history']) || isset($_GET['profit'])) {
    // Authenticate all client requests
    authenticate();

    // Handle history requests
    if (isset($_GET['history'])) {
        $request_id = uniqid('history_', true);
        $reqFile = "$queueDir/{$request_id}.req.json";
        $resFile = "$queueDir/{$request_id}.res.json";

        $requestData = [
            'request_id' => $request_id, 
            'request_type' => 'history',
            'history_type' => $_GET['history']
        ];

        if (file_put_contents($reqFile, json_encode($requestData, JSON_UNESCAPED_SLASHES)) === false) {
            echo json_encode(['error' => 'Failed to write request file']);
            exit;
        }

        $start = time();
        $timeout = 30;
        while (time() - $start < $timeout) {
            if (file_exists($resFile)) {
                $response = json_decode(file_get_contents($resFile), true);
                if (!empty($response['request_id']) && $response['request_id'] === $request_id) {
                    @unlink($resFile);
                    echo json_encode($response, JSON_UNESCAPED_SLASHES);
                    exit;
                }
            }
            usleep(200000);
        }

        http_response_code(504);
        echo json_encode(['error'=>'Timeout waiting for history data']);
        exit;
    }

    // Handle profit requests
    if (isset($_GET['profit'])) {
        $request_id = uniqid('profit_', true);
        $reqFile = "$queueDir/{$request_id}.req.json";
        $resFile = "$queueDir/{$request_id}.res.json";

        $requestData = [
            'request_id' => $request_id, 
            'request_type' => 'profit',
            'profit_type' => $_GET['profit']
        ];

        if (file_put_contents($reqFile, json_encode($requestData, JSON_UNESCAPED_SLASHES)) === false) {
            echo json_encode(['error' => 'Failed to write request file']);
            exit;
        }

        $start = time();
        $timeout = 30;
        while (time() - $start < $timeout) {
            if (file_exists($resFile)) {
                $response = json_decode(file_get_contents($resFile), true);
                if (!empty($response['request_id']) && $response['request_id'] === $request_id) {
                    @unlink($resFile);
                    echo json_encode($response, JSON_UNESCAPED_SLASHES);
                    exit;
                }
            }
            usleep(200000);
        }

        http_response_code(504);
        echo json_encode(['error'=>'Timeout waiting for profit data']);
        exit;
    }

    // Check if this is a trade request
    if (isset($_GET['action'])) {
        // Trade request
        $request_id = uniqid('trade_', true);
        $reqFile = "$queueDir/{$request_id}.req.json";
        $resFile = "$queueDir/{$request_id}.res.json";

        // Safely get all parameters with defaults
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        $symbol = isset($_GET['symbol']) ? $_GET['symbol'] : '';
        $volume = isset($_GET['volume']) ? (string)$_GET['volume'] : '0.01';
        $sl = isset($_GET['sl']) ? (string)$_GET['sl'] : '0';
        $tp = isset($_GET['tp']) ? (string)$_GET['tp'] : '0';
        $ticket = isset($_GET['ticket']) ? (string)$_GET['ticket'] : '0';
        $new_value = isset($_GET['new_value']) ? (string)$_GET['new_value'] : '0';
        
        // Handle price parameter safely
        $price = '0';
        if (isset($_GET['price'])) {
            if ($_GET['price'] === 'current') {
                $price = '0';
            } else {
                $price = (string)$_GET['price'];
            }
        }

        $requestData = [
            'request_id' => $request_id,
            'request_type' => 'trade',
            'action' => $action,
            'symbol' => $symbol,
            'volume' => $volume,
            'sl' => $sl,
            'tp' => $tp,
            'price' => $price,
            'ticket' => $ticket,
            'new_value' => $new_value
        ];

        if (file_put_contents($reqFile, json_encode($requestData, JSON_UNESCAPED_SLASHES)) === false) {
            echo json_encode(['error' => 'Failed to write request file']);
            exit;
        }

        $start = time();
        $timeout = 30;
        while (time() - $start < $timeout) {
            if (file_exists($resFile)) {
                $response = json_decode(file_get_contents($resFile), true);
                if (!empty($response['request_id']) && $response['request_id'] === $request_id) {
                    @unlink($resFile);
                    echo json_encode($response, JSON_UNESCAPED_SLASHES);
                    exit;
                }
            }
            usleep(200000);
        }

        http_response_code(504);
        echo json_encode(['error'=>'Timeout waiting for trade execution']);
        exit;
    } else {
        // Orders request
        $request_id = uniqid('orders_', true);
        $reqFile = "$queueDir/{$request_id}.req.json";
        $resFile = "$queueDir/{$request_id}.res.json";

        $requestData = ['request_id' => $request_id, 'request_type' => 'orders'];
        
        if (isset($_GET['filter_running']) && $_GET['filter_running'] === 'true') {
            $requestData['filter_running'] = true;
        }
        if (isset($_GET['filter_pending']) && $_GET['filter_pending'] === 'true') {
            $requestData['filter_pending'] = true;
        }
        if (isset($_GET['filter_closed']) && $_GET['filter_closed'] === 'true') {
            $requestData['filter_closed'] = true;
        }
        if (isset($_GET['filter_profit']) && $_GET['filter_profit'] === 'true') {
            $requestData['filter_profit'] = true;
        }
        if (isset($_GET['filter_loss']) && $_GET['filter_loss'] === 'true') {
            $requestData['filter_loss'] = true;
        }
        if (isset($_GET['filter_symbol']) && !empty($_GET['filter_symbol'])) {
            $requestData['filter_symbol'] = $_GET['filter_symbol'];
        }
        if (isset($_GET['filter_comment']) && !empty($_GET['filter_comment'])) {
            $requestData['filter_comment'] = $_GET['filter_comment'];
        }

        if (file_put_contents($reqFile, json_encode($requestData, JSON_UNESCAPED_SLASHES)) === false) {
            echo json_encode(['error' => 'Failed to write request file']);
            exit;
        }

        $start = time();
        $timeout = 30;
        while (time() - $start < $timeout) {
            if (file_exists($resFile)) {
                $response = json_decode(file_get_contents($resFile), true);
                if (!empty($response['request_id']) && $response['request_id'] === $request_id) {
                    @unlink($resFile);
                    echo json_encode($response, JSON_UNESCAPED_SLASHES);
                    exit;
                }
            }
            usleep(200000);
        }

        http_response_code(504);
        echo json_encode(['error'=>'Timeout waiting for orders data']);
        exit;
    }
}

// Default response - this is what you see when you visit the URL in browser
echo json_encode([
    'message' => 'Complete Trading API v1.0 - All MT5 Actions Available',
    'status' => 'online',
    'timestamp' => date('Y-m-d H:i:s'),
    'available_actions' => [
        'BUY' => 'Open buy position at market price',
        'SELL' => 'Open sell position at market price', 
        'BUY_LIMIT' => 'Place buy limit order',
        'SELL_LIMIT' => 'Place sell limit order',
        'BUY_STOP' => 'Place buy stop order',
        'SELL_STOP' => 'Place sell stop order',
        'CLOSE' => 'Close position by ticket or symbol',
        'CLOSE_ALL' => 'Close all positions',
        'CLOSE_LOSS' => 'Close only losing positions',
        'CLOSE_PROFIT' => 'Close only profitable positions',
        'BREAK_EVEN' => 'Move position to break even',
        'BREAK_EVEN_ALL' => 'Move all positions to break even',
        'MODIFY_TP' => 'Modify take profit',
        'MODIFY_SL' => 'Modify stop loss',
        'DELETE_ORDER' => 'Delete pending order',
        'DELETE_ALL_ORDERS' => 'Delete all pending orders',
        'TRAIL_SL' => 'Trail stop loss by points'
    ],
    'history_queries' => [
        'today' => '?api_key=KEY&history=today',
        'last-hour' => '?api_key=KEY&history=last-hour',
        'last-10' => '?api_key=KEY&history=last-10',
        'last-20' => '?api_key=KEY&history=last-20',
        'last-7days' => '?api_key=KEY&history=last-7days',
        'last-30days' => '?api_key=KEY&history=last-30days'
    ],
    'profit_queries' => [
        'today' => '?api_key=KEY&profit=today',
        'last-hour' => '?api_key=KEY&profit=last-hour',
        'this-week' => '?api_key=KEY&profit=this-week',
        'this-month' => '?api_key=KEY&profit=this-month',
        'last-7days' => '?api_key=KEY&profit=last-7days',
        'last-30days' => '?api_key=KEY&profit=last-30days'
    ],
    'examples' => [
        'Open BUY' => '?api_key=KEY&action=BUY&symbol=EURUSD&volume=0.01',
        'Close losses' => '?api_key=KEY&action=CLOSE_LOSS&symbol=ALL',
        'Break even all' => '?api_key=KEY&action=BREAK_EVEN_ALL&symbol=ALL',
        'Trail SL' => '?api_key=KEY&action=TRAIL_SL&ticket=123456&new_value=50',
        'Today history' => '?api_key=KEY&history=today',
        'Today profit' => '?api_key=KEY&profit=today'
    ]
]);
?>