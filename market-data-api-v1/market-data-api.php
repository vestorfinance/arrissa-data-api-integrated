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
 *  
 *  Version: 1.1
 *  Date:    2025-01-XX
 *  - Added support for last-x-minutes format in rangeType
 *  - Enhanced validation for dynamic minute ranges
 *  
 *  Version: 1.2
 *  Date:    2025-01-XX
 *  - Added direct URL parameter indicator support
 *  - Enhanced parameter validation for indicators
 *  - Support for multiple indicators of same type (ema1, ema2, etc.)
 *  
 *  Version: 1.3
 *  Date:    2025-01-XX
 *  - Fixed tick volume output and dataField functionality
 *  - Removed volumes indicator to avoid confusion with basic volume
 *  - Enhanced parameter validation and error handling
 *  
 *  Version: 1.4
 *  Date:    2025-01-XX
 *  - Added multiple volume parameter support (candle-volume, candlevolume, volume)
 *  - Enhanced volume parameter handling and validation
 *  - Improved parameter forwarding to EA
 * ------------------------------------------------------------------------
 */

// Toggle debug logging
$debugEnabled = false;

// Always return JSON
header('Content-Type: application/json');

// Load database connection
require_once __DIR__ . '/../app/Database.php';
$db = Database::getInstance();
$pdo = $db->getConnection();

$baseDir   = __DIR__ . '/';
$queueDir  = $baseDir . 'queue';
$debugFile = $baseDir . 'mt5-debug.log';

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

// ENHANCED: Volume parameter detection and validation
function detect_volume_parameter($params) {
    $volumeParams = ['candle-volume', 'candlevolume', 'volume'];
    $detectedParam = null;
    $volumeEnabled = false;
    
    foreach ($volumeParams as $param) {
        if (isset($params[$param])) {
            $detectedParam = $param;
            $volumeEnabled = ($params[$param] === 'true' || $params[$param] === '1');
            debug_log("Volume parameter detected: $param = " . $params[$param] . " (enabled: " . ($volumeEnabled ? 'true' : 'false') . ")");
            break; // Use first found parameter
        }
    }
    
    return [
        'detected' => $detectedParam !== null,
        'parameter' => $detectedParam,
        'enabled' => $volumeEnabled
    ];
}

// Enhanced validation for multiple indicators including MA patterns (FIXED: removed 'volumes')
function validate_direct_indicators($params) {
    $validIndicators = [
        'rsi', 'bb', 'bands', 'macd', 'stoch', 'stochastic', 'atr', 'sar', 
        'ichimoku', 'momentum', 'envelopes', 'cci', 'demarker', 'wpr', 
        'stddev', 'alligator', 'fractals', 'ac', 'ao', 'obv', 'mfi'
        // REMOVED: 'volumes' to avoid confusion with basic tick volume
    ];

    $errors = [];
    $indicatorCount = 0;
    
    // ENHANCED: Skip volume parameters in indicator validation
    $volumeParams = ['candle-volume', 'candlevolume', 'volume'];
    
    foreach ($params as $key => $value) {
        // Skip non-indicator parameters including volume parameters
        if (in_array($key, array_merge(['api_key', 'symbol', 'timeframe', 'rangeType', 'count', 'pretend_date', 'pretend_time', 'data', 'dataField'], $volumeParams))) {
            continue;
        }
        
        // Check for MA patterns (ma_1, ema_2, etc.)
        if (preg_match('/^(ma|ema|sma|smma|lwma)_\d+$/', $key)) {
            $indicatorCount++;
            // Validate parameter format for MA indicators
            if (!empty($value) && !preg_match('/^[a-z,\d.]+$/i', $value)) {
                $errors[] = "Invalid parameters for '$key': '$value'. Use format like 'e,20' or '20'";
            }
            continue;
        }
        
        // Check if it's a valid standard indicator
        $baseIndicator = preg_replace('/\d+$/', '', $key);
        if (in_array($baseIndicator, $validIndicators) || in_array($key, $validIndicators)) {
            $indicatorCount++;
            
            // Validate parameter format (comma-separated numbers and decimals)
            if (!empty($value) && !preg_match('/^[\d.,]+$/', $value)) {
                $errors[] = "Invalid parameters for '$key': '$value'. Use comma-separated numbers";
            }
        }
    }
    
    if ($indicatorCount > 30) {
        $errors[] = "Too many indicators requested ($indicatorCount). Maximum 30 indicators per request.";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'indicator_count' => $indicatorCount
    ];
}

// Validate last-x-minutes format
function validate_last_minutes_format($rangeType) {
    // Pattern: last-{number}-minutes where number is 1-1440 (24 hours max)
    if (preg_match('/^last-(\d+)-minutes$/', $rangeType, $matches)) {
        $minutes = (int)$matches[1];
        // Validate reasonable range: 1 minute to 24 hours (1440 minutes)
        if ($minutes >= 1 && $minutes <= 1440) {
            return [
                'valid' => true,
                'minutes' => $minutes,
                'type' => 'last-x-minutes'
            ];
        } else {
            return [
                'valid' => false,
                'error' => "Minutes value must be between 1 and 1440 (24 hours). Got: $minutes"
            ];
        }
    }
    return ['valid' => true, 'type' => 'standard']; // Not a last-x-minutes format, but valid
}

// Validate rangeType parameter
function validate_range_type($rangeType) {
    if (empty($rangeType)) {
        return ['valid' => false, 'error' => 'rangeType cannot be empty'];
    }

    // Check for last-x-minutes format first
    $minutesCheck = validate_last_minutes_format($rangeType);
    if (!$minutesCheck['valid']) {
        return $minutesCheck;
    }
    
    if ($minutesCheck['type'] === 'last-x-minutes') {
        debug_log("Validated last-x-minutes format: {$rangeType} = {$minutesCheck['minutes']} minutes");
        return $minutesCheck;
    }

    // Standard rangeType validation
    $validRangeTypes = [
        // Time-based ranges
        'last-five-minutes', 'last-hour', 'last-6-hours', 'last-12-hours',
        'last-48-hours', 'last-3-days', 'last-4-days', 'last-5-days',
        'last-7-days', 'last-14-days', 'last-30-days',
        
        // Calendar-based ranges
        'today', 'yesterday', 'this-week', 'last-week',
        'this-month', 'last-month', 'last-3-months', 'last-6-months',
        'this-year', 'last-12-months',
        
        // Special ranges
        'future'
    ];

    if (in_array($rangeType, $validRangeTypes)) {
        return ['valid' => true, 'type' => 'standard'];
    }

    return [
        'valid' => false, 
        'error' => "Invalid rangeType '$rangeType'. Supported: " . implode(', ', $validRangeTypes) . ", or last-X-minutes format (where X is 1-1440)"
    ];
}

// Validate timeframe parameter
function validate_timeframe($timeframe) {
    if (empty($timeframe)) {
        return ['valid' => true]; // Optional parameter
    }

    $validTimeframes = ['M1', 'M5', 'M15', 'M30', 'H1', 'H4', 'D1', 'W1', 'MN1'];
    
    if (in_array($timeframe, $validTimeframes)) {
        return ['valid' => true];
    }

    return [
        'valid' => false,
        'error' => "Invalid timeframe '$timeframe'. Supported: " . implode(', ', $validTimeframes)
    ];
}

// Validate symbol parameter
function validate_symbol($symbol) {
    if (empty($symbol)) {
        return ['valid' => false, 'error' => 'Symbol is required'];
    }

    // Basic symbol validation - alphanumeric and common forex/crypto patterns
    if (!preg_match('/^[A-Za-z0-9._-]+$/', $symbol)) {
        return ['valid' => false, 'error' => 'Invalid symbol format'];
    }

    if (strlen($symbol) > 20) {
        return ['valid' => false, 'error' => 'Symbol too long (max 20 characters)'];
    }

    return ['valid' => true];
}

// Validate count parameter
function validate_count($count) {
    if ($count === null) {
        return ['valid' => true]; // Optional for most requests
    }

    $countInt = (int)$count;
    if ($countInt < 1 || $countInt > 5000) {
        return [
            'valid' => false,
            'error' => "Count must be between 1 and 5000. Got: $countInt"
        ];
    }

    return ['valid' => true, 'count' => $countInt];
}

// Validate pretend date format (YYYY-MM-DD)
function validate_pretend_date($date) {
    if (empty($date)) {
        return ['valid' => true]; // Optional parameter
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return ['valid' => false, 'error' => 'Invalid pretend_date format. Use YYYY-MM-DD'];
    }

    $parts = explode('-', $date);
    if (!checkdate($parts[1], $parts[2], $parts[0])) {
        return ['valid' => false, 'error' => 'Invalid pretend_date value'];
    }

    return ['valid' => true];
}

// Validate pretend time format (HH:MM)
function validate_pretend_time($time) {
    if (empty($time)) {
        return ['valid' => true]; // Optional parameter
    }

    if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
        return ['valid' => false, 'error' => 'Invalid pretend_time format. Use HH:MM'];
    }

    $parts = explode(':', $time);
    $hour = (int)$parts[0];
    $minute = (int)$parts[1];

    if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
        return ['valid' => false, 'error' => 'Invalid pretend_time value'];
    }

    return ['valid' => true];
}

// FIXED: Validate dataField parameter (changed from validate_data_field)
function validate_data_field($dataField) {
    if (empty($dataField)) {
        return ['valid' => true]; // Optional parameter
    }

    $validDataFields = ['open', 'high', 'low', 'close', 'volume'];
    
    if (in_array($dataField, $validDataFields)) {
        return ['valid' => true];
    }

    return [
        'valid' => false,
        'error' => "Invalid dataField '$dataField'. Supported: " . implode(', ', $validDataFields)
    ];
}

// ENHANCED: Volume parameter validation
function validate_volume_parameter($params) {
    $volumeCheck = detect_volume_parameter($params);
    
    if ($volumeCheck['detected']) {
        $param = $volumeCheck['parameter'];
        $value = $params[$param];
        
        // Validate volume parameter value
        if (!in_array($value, ['true', 'false', '1', '0'])) {
            return [
                'valid' => false,
                'error' => "Invalid value for '$param': '$value'. Use 'true' or 'false'"
            ];
        }
        
        debug_log("Volume parameter validated: $param = $value");
    }
    
    return ['valid' => true];
}

// Comprehensive parameter validation
function validate_request_parameters($params) {
    $errors = [];

    // Validate symbol (required for both modes)
    if (isset($params['symbol'])) {
        $symbolCheck = validate_symbol($params['symbol']);
        if (!$symbolCheck['valid']) {
            $errors[] = $symbolCheck['error'];
        }
    }

    // Validate rangeType (for expanded mode)
    if (isset($params['rangeType'])) {
        $rangeCheck = validate_range_type($params['rangeType']);
        if (!$rangeCheck['valid']) {
            $errors[] = $rangeCheck['error'];
        }
    }

    // Validate timeframe
    if (isset($params['timeframe'])) {
        $timeframeCheck = validate_timeframe($params['timeframe']);
        if (!$timeframeCheck['valid']) {
            $errors[] = $timeframeCheck['error'];
        }
    }

    // Validate count
    if (isset($params['count'])) {
        $countCheck = validate_count($params['count']);
        if (!$countCheck['valid']) {
            $errors[] = $countCheck['error'];
        }
    }

    // Validate pretend_date
    if (isset($params['pretend_date'])) {
        $dateCheck = validate_pretend_date($params['pretend_date']);
        if (!$dateCheck['valid']) {
            $errors[] = $dateCheck['error'];
        }
    }

    // Validate pretend_time
    if (isset($params['pretend_time'])) {
        $timeCheck = validate_pretend_time($params['pretend_time']);
        if (!$timeCheck['valid']) {
            $errors[] = $timeCheck['error'];
        }
    }

    // FIXED: Validate dataField (was checking 'data' parameter)
    if (isset($params['dataField'])) {
        $dataCheck = validate_data_field($params['dataField']);
        if (!$dataCheck['valid']) {
            $errors[] = $dataCheck['error'];
        }
    }
    
    // Also check for 'data' parameter for backward compatibility
    if (isset($params['data'])) {
        $dataCheck = validate_data_field($params['data']);
        if (!$dataCheck['valid']) {
            $errors[] = $dataCheck['error'];
        }
    }

    // ENHANCED: Validate volume parameters
    $volumeCheck = validate_volume_parameter($params);
    if (!$volumeCheck['valid']) {
        $errors[] = $volumeCheck['error'];
    }

    // Validate direct indicators
    $indicatorCheck = validate_direct_indicators($params);
    if (!$indicatorCheck['valid']) {
        $errors = array_merge($errors, $indicatorCheck['errors']);
    }

    // Special validation: pretend_date and pretend_time should be used together
    if (!empty($params['pretend_date']) && empty($params['pretend_time'])) {
        $errors[] = 'pretend_time is required when pretend_date is specified';
    }
    if (!empty($params['pretend_time']) && empty($params['pretend_date'])) {
        $errors[] = 'pretend_date is required when pretend_time is specified';
    }

    return $errors;
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
// 1) EA POST: receive candle data & write response
//--------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id   = $_POST['request_id']   ?? '';
    $symbol       = $_POST['symbol']       ?? '';
    $candlesRaw   = $_POST['candles']      ?? '';
    $currentPrice = $_POST['currentPrice'] ?? null;

    if (!$request_id || !$candlesRaw) {
        http_response_code(400);
        debug_log("EA POST missing request_id or candles: request_id='$request_id'");
        echo json_encode(['vestor_data' => ['error'=>'Missing request_id or candles']]);
        exit;
    }

    $candles = json_decode($candlesRaw, true);
    if (!is_array($candles)) {
        http_response_code(400);
        debug_log("EA POST invalid candles JSON");
        echo json_encode(['vestor_data' => ['error'=>'Invalid candles JSON']]);
        exit;
    }

    // build response (keep internal format for queue)
    $responseData = [
        'request_id' => $request_id,
        'symbol'     => $symbol,
        'candles'    => $candles
    ];
    if ($currentPrice !== null) {
        $responseData['currentPrice'] = (float)$currentPrice;
    }
    
    // Include additional metadata from EA
    $additionalFields = [
        'rangeType', 'timeframe', 'dataField', 'pretend_date', 'pretend_time',
        'count', 'ignoreSunday', 'dayOfWeek', 'candleVolume', 'isCrypto', 
        'partialCandle'
    ];
    
    foreach ($additionalFields as $field) {
        if (isset($_POST[$field])) {
            $responseData[$field] = $_POST[$field];
        }
    }

    // Special handling for last-x-minutes format
    if (isset($_POST['rangeType'])) {
        $rangeType = $_POST['rangeType'];
        $minutesCheck = validate_last_minutes_format($rangeType);
        if ($minutesCheck['valid'] && $minutesCheck['type'] === 'last-x-minutes') {
            $responseData['minutes_requested'] = $minutesCheck['minutes'];
            $responseData['range_format'] = 'last-x-minutes';
            debug_log("EA POST processed last-x-minutes: {$rangeType} = {$minutesCheck['minutes']} minutes");
        }
    }

    // Store in queue WITHOUT vestor_data wrapper (internal communication)
    $resFile = "$queueDir/{$request_id}.res.json";
    file_put_contents($resFile, json_encode($responseData, JSON_UNESCAPED_SLASHES));
    debug_log("EA POST wrote response for request_id=$request_id with " . count($candles) . " candles");

    // Return to EA WITH vestor_data wrapper
    echo json_encode(['vestor_data' => ['status'=>'ok', 'candles_received' => count($candles)]]);
    exit;
}

//--------------------------------------------
// Helpers: authenticate (no consume) & consume_quota
//--------------------------------------------
function authenticate() {
    global $pdo;
    $api_key = $_GET['api_key'] ?? null;
    if (!$api_key) {
        echo json_encode(['vestor_data' => ['error'=>'Missing API key']]);
        exit;
    }
    $stmt = $pdo->prepare("
        SELECT value
        FROM settings
        WHERE key = 'api_key'
    ");
    $stmt->execute();
    $setting = $stmt->fetch();
    if (!$setting || $setting['value'] !== $api_key) {
        echo json_encode(['vestor_data' => ['error'=>'Invalid API key']]);
        exit;
    }
}

function consume_quota() {
    // No quota tracking
}

//--------------------------------------------
// 2) Client GET: expanded‐mode with direct indicator parameters
//--------------------------------------------
$symbol     = $_GET['symbol']     ?? null;
$rangeType  = $_GET['rangeType']  ?? null;
$timeframe  = $_GET['timeframe']  ?? null;
$count      = $_GET['count']      ?? null;
$pretend_d  = $_GET['pretend_date']  ?? null;
$pretend_t  = $_GET['pretend_time']  ?? null;
$dataField  = $_GET['dataField']  ?? $_GET['data'] ?? null; // FIXED: Support both dataField and data

if ($symbol && $rangeType) {
    authenticate();

    // Validate all parameters before processing
    $validationErrors = validate_request_parameters($_GET);
    if (!empty($validationErrors)) {
        debug_log("Client GET validation errors: " . implode(', ', $validationErrors));
        echo json_encode([
            'vestor_data' => [
                'success' => false,
                'error' => 'Invalid request parameters — please check your parameters',
                'details' => $validationErrors
            ]
        ]);
        exit;
    }

    // Special handling for last-x-minutes format
    $minutesInfo = validate_last_minutes_format($rangeType);
    if ($minutesInfo['valid'] && $minutesInfo['type'] === 'last-x-minutes') {
        debug_log("Client GET processing last-x-minutes: {$rangeType} = {$minutesInfo['minutes']} minutes");
    }

    // ENHANCED: Detect volume parameter
    $volumeInfo = detect_volume_parameter($_GET);
    if ($volumeInfo['detected']) {
        debug_log("Client GET detected volume parameter: {$volumeInfo['parameter']} = " . ($volumeInfo['enabled'] ? 'true' : 'false'));
    }

    // FIXED: Extract indicator parameters including MA patterns (removed 'volumes')
    $indicatorParams = [];
    $validIndicators = [
        'rsi', 'bb', 'bands', 'macd', 'stoch', 'stochastic', 'atr', 'sar', 
        'ichimoku', 'momentum', 'envelopes', 'cci', 'demarker', 'wpr', 
        'stddev', 'alligator', 'fractals', 'ac', 'ao', 'obv', 'mfi'
        // REMOVED: 'volumes' to avoid confusion with basic tick volume
    ];

    $volumeParams = ['candle-volume', 'candlevolume', 'volume'];

    foreach ($_GET as $key => $value) {
        // Skip volume parameters in indicator processing
        if (in_array($key, $volumeParams)) {
            continue;
        }
        
        // Check for MA patterns (ma_1, ema_2, etc.)
        if (preg_match('/^(ma|ema|sma|smma|lwma)_\d+$/', $key)) {
            $indicatorParams[$key] = $value;
            debug_log("Found MA indicator parameter: $key = $value");
            continue;
        }
        
        $baseIndicator = preg_replace('/\d+$/', '', $key);
        if (in_array($baseIndicator, $validIndicators) || in_array($key, $validIndicators)) {
            $indicatorParams[$key] = $value;
            debug_log("Found indicator parameter: $key = $value");
        }
    }

    $request_id = uniqid('req_', true);
    $reqFile    = "$queueDir/{$request_id}.req.json";
    $resFile    = "$queueDir/{$request_id}.res.json";

    $requestData = [
        'request_id' => $request_id,
        'symbol'     => $symbol,
        'rangeType'  => $rangeType
    ];
    
    // Add optional parameters
    if ($pretend_d) $requestData['pretend_date'] = $pretend_d;
    if ($pretend_t) $requestData['pretend_time'] = $pretend_t;
    if ($dataField) $requestData['dataField']    = $dataField;
    if ($timeframe) $requestData['timeframe']    = $timeframe;
    if ($count)     $requestData['count']        = (int)$count;

    // ENHANCED: Add volume parameter to request data
    if ($volumeInfo['detected']) {
        $requestData[$volumeInfo['parameter']] = $volumeInfo['enabled'] ? 'true' : 'false';
        debug_log("Added volume parameter to request: {$volumeInfo['parameter']} = " . ($volumeInfo['enabled'] ? 'true' : 'false'));
    }

    // Add indicator parameters directly to request data
    foreach ($indicatorParams as $key => $value) {
        $requestData[$key] = $value;
    }

    // Add metadata for last-x-minutes requests
    if ($minutesInfo['valid'] && $minutesInfo['type'] === 'last-x-minutes') {
        $requestData['minutes_requested'] = $minutesInfo['minutes'];
        $requestData['range_format'] = 'last-x-minutes';
    }

    // Store request WITHOUT vestor_data wrapper (internal communication)
    file_put_contents($reqFile, json_encode($requestData, JSON_UNESCAPED_SLASHES));
    debug_log("Client GET enqueued expanded request_id=$request_id for $symbol $rangeType" . 
              (!empty($indicatorParams) ? " with indicators: " . implode(',', array_keys($indicatorParams)) : "") .
              ($volumeInfo['detected'] ? " with volume: {$volumeInfo['parameter']}=" . ($volumeInfo['enabled'] ? 'true' : 'false') : ""));

    $start   = time();
    $timeout = 240;
    while (time() - $start < $timeout) {
        if (file_exists($resFile)) {
            $response = json_decode(file_get_contents($resFile), true);
            if (!empty($response['request_id']) && $response['request_id'] === $request_id) {
                @unlink($resFile);
                debug_log("Client GET returning expanded data for request_id=$request_id with " . count($response['candles'] ?? []) . " candles");
                consume_quota();
                
                // Add request metadata to response
                $response['request_metadata'] = [
                    'symbol' => $symbol,
                    'rangeType' => $rangeType,
                    'timeframe' => $timeframe,
                    'requested_at' => date('Y-m-d H:i:s'),
                    'processing_time_ms' => (time() - $start) * 1000
                ];
                
                if (!empty($indicatorParams)) {
                    $response['request_metadata']['indicators_requested'] = array_keys($indicatorParams);
                }
                
                if ($volumeInfo['detected']) {
                    $response['request_metadata']['volume_parameter'] = $volumeInfo['parameter'];
                    $response['request_metadata']['volume_enabled'] = $volumeInfo['enabled'];
                }
                
                if ($minutesInfo['valid'] && $minutesInfo['type'] === 'last-x-minutes') {
                    $response['request_metadata']['minutes_requested'] = $minutesInfo['minutes'];
                    $response['request_metadata']['range_format'] = 'last-x-minutes';
                }
                
                // Return to client WITH vestor_data wrapper
                echo json_encode(['vestor_data' => $response], JSON_UNESCAPED_SLASHES);
                exit;
            }
        }
        usleep(200000);
    }

    http_response_code(504);
    debug_log("Client GET timeout waiting for EA for request_id=$request_id");
    echo json_encode(['vestor_data' => ['error'=>'Timeout waiting for Data Server']]);
    exit;
}

//--------------------------------------------
// 2b) Client GET: legacy‐mode (symbol + timeframe + count) with direct indicators
//--------------------------------------------
elseif ($symbol && $timeframe && $count) {
    authenticate();

    // Validate parameters for legacy mode
    $legacyParams = [
        'symbol' => $symbol,
        'timeframe' => $timeframe,
        'count' => $count
    ];
    if ($pretend_d) $legacyParams['pretend_date'] = $pretend_d;
    if ($pretend_t) $legacyParams['pretend_time'] = $pretend_t;
    if ($dataField) $legacyParams['dataField'] = $dataField; // FIXED: Use dataField consistently

    // ENHANCED: Detect volume parameter for legacy mode
    $volumeInfo = detect_volume_parameter($_GET);
    if ($volumeInfo['detected']) {
        debug_log("Legacy GET detected volume parameter: {$volumeInfo['parameter']} = " . ($volumeInfo['enabled'] ? 'true' : 'false'));
        $legacyParams[$volumeInfo['parameter']] = $volumeInfo['enabled'] ? 'true' : 'false';
    }

    // FIXED: Add indicator parameters to validation including MA patterns (removed 'volumes')
    $validIndicators = [
        'rsi', 'bb', 'bands', 'macd', 'stoch', 'stochastic', 'atr', 'sar', 
        'ichimoku', 'momentum', 'envelopes', 'cci', 'demarker', 'wpr', 
        'stddev', 'alligator', 'fractals', 'ac', 'ao', 'obv', 'mfi'
        // REMOVED: 'volumes' to avoid confusion with basic tick volume
    ];

    $volumeParams = ['candle-volume', 'candlevolume', 'volume'];
    $indicatorParams = [];
    
    foreach ($_GET as $key => $value) {
        // Skip volume parameters in indicator processing
        if (in_array($key, $volumeParams)) {
            continue;
        }
        
        // Check for MA patterns (ma_1, ema_2, etc.)
        if (preg_match('/^(ma|ema|sma|smma|lwma)_\d+$/', $key)) {
            $legacyParams[$key] = $value;
            $indicatorParams[$key] = $value;
            continue;
        }
        
        $baseIndicator = preg_replace('/\d+$/', '', $key);
        if (in_array($baseIndicator, $validIndicators) || in_array($key, $validIndicators)) {
            $legacyParams[$key] = $value;
            $indicatorParams[$key] = $value;
        }
    }

    $validationErrors = validate_request_parameters($legacyParams);
    if (!empty($validationErrors)) {
        debug_log("Client GET legacy validation errors: " . implode(', ', $validationErrors));
        echo json_encode([
            'vestor_data' => [
                'success' => false,
                'error' => 'Invalid request parameters — please check your parameters',
                'details' => $validationErrors
            ]
        ]);
        exit;
    }

    $request_id = uniqid('req_', true);
    $reqFile    = "$queueDir/{$request_id}.req.json";
    $resFile    = "$queueDir/{$request_id}.res.json";

    $requestData = [
        'request_id' => $request_id,
        'symbol'     => $symbol,
        'timeframe'  => $timeframe,
        'count'      => (int)$count
    ];
    if ($pretend_d) $requestData['pretend_date'] = $pretend_d;
    if ($pretend_t) $requestData['pretend_time'] = $pretend_t;
    if ($dataField) $requestData['dataField']    = $dataField;

    // ENHANCED: Add volume parameter to legacy request data
    if ($volumeInfo['detected']) {
        $requestData[$volumeInfo['parameter']] = $volumeInfo['enabled'] ? 'true' : 'false';
        debug_log("Added volume parameter to legacy request: {$volumeInfo['parameter']} = " . ($volumeInfo['enabled'] ? 'true' : 'false'));
    }

    // Add indicator parameters directly to request data
    foreach ($indicatorParams as $key => $value) {
        $requestData[$key] = $value;
    }

    // Store request WITHOUT vestor_data wrapper (internal communication)
    file_put_contents($reqFile, json_encode($requestData, JSON_UNESCAPED_SLASHES));
    debug_log("Client GET enqueued legacy request_id=$request_id for $symbol $timeframe count=$count" . 
              (!empty($indicatorParams) ? " with indicators: " . implode(',', array_keys($indicatorParams)) : "") .
              ($volumeInfo['detected'] ? " with volume: {$volumeInfo['parameter']}=" . ($volumeInfo['enabled'] ? 'true' : 'false') : ""));

    $start   = time();
    $timeout = 240;
    while (time() - $start < $timeout) {
        if (file_exists($resFile)) {
            $response = json_decode(file_get_contents($resFile), true);
            if (!empty($response['request_id']) && $response['request_id'] === $request_id) {
                @unlink($resFile);
                debug_log("Client GET returning legacy data for request_id=$request_id with " . count($response['candles'] ?? []) . " candles");
                consume_quota();
                
                // Add request metadata to response
                $response['request_metadata'] = [
                    'symbol' => $symbol,
                    'timeframe' => $timeframe,
                    'count' => (int)$count,
                    'mode' => 'legacy',
                    'requested_at' => date('Y-m-d H:i:s'),
                    'processing_time_ms' => (time() - $start) * 1000
                ];
                
                if (!empty($indicatorParams)) {
                    $response['request_metadata']['indicators_requested'] = array_keys($indicatorParams);
                }
                
                if ($volumeInfo['detected']) {
                    $response['request_metadata']['volume_parameter'] = $volumeInfo['parameter'];
                    $response['request_metadata']['volume_enabled'] = $volumeInfo['enabled'];
                }
                
                // Return to client WITH vestor_data wrapper
                echo json_encode(['vestor_data' => $response], JSON_UNESCAPED_SLASHES);
                exit;
            }
        }
        usleep(200000);
    }

    http_response_code(504);
    debug_log("Client GET timeout waiting for EA for request_id=$request_id");
    echo json_encode(['vestor_data' => ['error'=>'Timeout waiting for Data Server']]);
    exit;
}

//--------------------------------------------
// 2c) Invalid parameters - provide helpful error message with enhanced volume support
//--------------------------------------------
elseif (!empty($_GET) && !isset($_GET['api_key'])) {
    echo json_encode([
        'vestor_data' => [
            'error' => 'Invalid request parameters',
            'usage' => [
                'expanded_mode' => 'GET ?api_key=xxx&symbol=EURUSD&rangeType=last-hour&timeframe=M5&ema_1=e,20&ema_2=50&rsi=14&candle-volume=true',
                'legacy_mode' => 'GET ?api_key=xxx&symbol=EURUSD&timeframe=M5&count=100&ma_1=s,50&ma_2=e,20&atr=14&volume=true',
                'last_x_minutes' => 'GET ?api_key=xxx&symbol=EURUSD&rangeType=last-15-minutes&timeframe=M1&rsi=9&candlevolume=true',
                'data_field_only' => 'GET ?api_key=xxx&symbol=EURUSD&rangeType=last-hour&timeframe=M1&dataField=volume',
                'supported_ranges' => 'last-X-minutes (where X is 1-1440), last-hour, today, yesterday, this-week, etc.',
                'supported_timeframes' => 'M1, M5, M15, M30, H1, H4, D1, W1, MN1',
                'data_fields' => 'open, high, low, close, volume (tick volume)',
                'volume_parameters' => [
                    'candle-volume' => '&candle-volume=true (include tick volume in each candle)',
                    'candlevolume' => '&candlevolume=true (alternative without hyphen)',
                    'volume' => '&volume=true (short form)',
                    'note' => 'Any of these three parameters will enable tick volume in candle output'
                ],
                'volume_examples' => [
                    'with_volume' => '?api_key=xxx&symbol=EURUSD&timeframe=H1&count=100&candle-volume=true',
                    'without_volume' => '?api_key=xxx&symbol=EURUSD&timeframe=H1&count=100',
                    'volume_only' => '?api_key=xxx&symbol=EURUSD&timeframe=H1&count=100&dataField=volume'
                ],
                'multiple_ma_indicators' => [
                    'format' => 'ma_1=type,period or ma_1=period (defaults to SMA)',
                    'types' => 'e=EMA, s=SMA, sm=SMMA, l=LWMA',
                    'examples' => [
                        'ma_1=e,20' => 'EMA with period 20',
                        'ma_2=50' => 'SMA with period 50 (default)',
                        'ema_1=20&ema_2=50' => 'Two EMAs with periods 20 and 50',
                        'sma_1=s,20&ema_1=e,50' => 'SMA 20 and EMA 50'
                    ]
                ],
                'direct_indicators' => [
                    'moving_averages' => '&ma_1=period, &ema_1=period, &sma_1=period, &smma_1=period, &lwma_1=period',
                    'oscillators' => '&rsi=period, &stoch=k,d,slowing, &cci=period, &wpr=period, &mfi=period',
                    'trend_indicators' => '&macd=fast,slow,signal, &sar=step,maximum, &ichimoku=tenkan,kijun,senkou',
                    'volatility' => '&bb=period,shift,deviation, &atr=period, &envelopes=period,deviation',
                    'volume_indicators' => '&obv=volume_type (0=tick, 1=real)',
                    'bill_williams' => '&ac, &ao, &alligator=jaw,teeth,lips, &fractals',
                    'other' => '&momentum=period, &demarker=period, &stddev=period'
                ]
            ]
        ]
    ]);
    exit;
}

//--------------------------------------------
// 3) EA polling GET (no params) → return next pending request
//--------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_GET)) {
    $pending = glob("$queueDir/*.req.json");
    if (!empty($pending)) {
        sort($pending);             // oldest first
        $file = $pending[0];
        $raw  = file_get_contents($file);
        debug_log("EA polling, returning pending request: $raw");
        @unlink($file);             // hand it out only once
        // Return raw request data to EA (no vestor_data wrapper for internal communication)
        echo $raw;
        exit;
    }
}

//--------------------------------------------
// 4) No params & no pending request - provide enhanced API documentation
//--------------------------------------------
debug_log("No pending request and no query params - returning API documentation");
echo json_encode([
    'vestor_data' => [
        'message' => 'Market Data API v1.4 - Enhanced Volume Parameter Support',
        'status' => 'active',
        'documentation' => [
            'expanded_mode' => [
                'url' => '?api_key=YOUR_KEY&symbol=SYMBOL&rangeType=RANGE&timeframe=TF&INDICATORS&VOLUME',
                'example' => '?api_key=abc123&symbol=EURUSD&rangeType=last-30-minutes&timeframe=M1&rsi=14&ema_1=e,20&ema_2=50&atr=14&candle-volume=true',
                'data_field_example' => '?api_key=abc123&symbol=EURUSD&rangeType=last-hour&timeframe=M1&dataField=volume',
                'supported_ranges' => [
                    'dynamic' => 'last-X-minutes (where X is 1-1440, e.g., last-5-minutes, last-30-minutes)',
                    'fixed' => 'last-hour, today, yesterday, this-week, last-week, this-month, etc.'
                ]
            ],
            'legacy_mode' => [
                'url' => '?api_key=YOUR_KEY&symbol=SYMBOL&timeframe=TF&count=NUMBER&INDICATORS&VOLUME',
                'example' => '?api_key=abc123&symbol=EURUSD&timeframe=M5&count=100&ma_1=s,50&ema_1=e,20&bb=20,0,2.0&volume=true',
                'data_field_example' => '?api_key=abc123&symbol=EURUSD&timeframe=M5&count=100&dataField=close'
            ],
            'volume_parameter_support' => [
                'description' => 'Multiple parameter names supported for volume control',
                'parameters' => [
                    'candle-volume' => 'Primary parameter name (with hyphen)',
                    'candlevolume' => 'Alternative without hyphen',
                    'volume' => 'Short form parameter'
                ],
                'values' => [
                    'true' => 'Enable tick volume in each candle',
                    'false' => 'Disable tick volume (default)',
                    '1' => 'Enable tick volume (alternative)',
                    '0' => 'Disable tick volume (alternative)'
                ],
                'examples' => [
                    'enable_volume_1' => '&candle-volume=true',
                    'enable_volume_2' => '&candlevolume=true',
                    'enable_volume_3' => '&volume=true',
                    'disable_volume_1' => '&candle-volume=false',
                    'disable_volume_2' => 'omit parameter (default)',
                    'volume_only_data' => '&dataField=volume (returns only volume array)'
                ]
            ],
            'optional_parameters' => [
                'pretend_date' => 'YYYY-MM-DD format for backtesting',
                'pretend_time' => 'HH:MM format (required with pretend_date)',
                'dataField' => 'open|high|low|close|volume (return single field only as array)',
                'volume_control' => 'candle-volume=true|false (include tick volume in each candle when dataField not used)'
            ],
            'data_field_functionality' => [
                'description' => 'When dataField is specified, returns ONLY that field as an array of values',
                'volume_note' => 'dataField=volume returns tick volume values',
                'examples' => [
                    'dataField=open' => 'Returns [1.2345, 1.2346, 1.2347, ...]',
                    'dataField=close' => 'Returns [1.2350, 1.2355, 1.2360, ...]',
                    'dataField=volume' => 'Returns [123, 456, 789, ...] (tick volume)',
                    'no_dataField_with_volume' => 'Returns full OHLC candle objects with volume field when volume parameter is true',
                    'no_dataField_without_volume' => 'Returns full OHLC candle objects without volume field (default)'
                ]
            ],
            'enhanced_ma_support' => [
                'format' => 'ma_X=type,period or ma_X=period (where X is 1-10)',
                'types' => [
                    'e' => 'EMA (Exponential Moving Average)',
                    's' => 'SMA (Simple Moving Average) - default',
                    'sm' => 'SMMA (Smoothed Moving Average)',
                    'l' => 'LWMA (Linear Weighted Moving Average)'
                ],
                'examples' => [
                    'ma_1=e,20&ma_2=50' => 'EMA 20 and SMA 50',
                    'ema_1=20&ema_2=50' => 'Two EMAs with periods 20 and 50',
                    'sma_1=20&lwma_1=l,50' => 'SMA 20 and LWMA 50'
                ]
            ],
            'direct_indicator_parameters' => [
                'format' => 'Add indicators directly as URL parameters: &rsi=14&ema_1=e,20&atr=14',
                'moving_averages' => [
                    'ma_X' => '&ma_1=period or &ma_1=type,period (Multiple MAs supported)',
                    'ema_X' => '&ema_1=period (Multiple EMAs supported)',
                    'sma_X' => '&sma_1=period (Multiple SMAs supported)',
                    'smma_X' => '&smma_1=period (Multiple SMMAs supported)',
                    'lwma_X' => '&lwma_1=period (Multiple LWMAs supported)'
                ],
                'oscillators' => [
                    'rsi' => '&rsi=period (e.g., &rsi=14)',
                    'stoch' => '&stoch=k_period,d_period,slowing (e.g., &stoch=5,3,3)',
                    'cci' => '&cci=period (e.g., &cci=14)',
                    'wpr' => '&wpr=period (e.g., &wpr=14)',
                    'mfi' => '&mfi=period (e.g., &mfi=14)',
                    'momentum' => '&momentum=period (e.g., &momentum=14)'
                ],
                'trend_indicators' => [
                    'macd' => '&macd=fast,slow,signal (e.g., &macd=12,26,9)',
                    'sar' => '&sar=step,maximum (e.g., &sar=0.02,0.2)',
                    'ichimoku' => '&ichimoku=tenkan,kijun,senkou (e.g., &ichimoku=9,26,52)'
                ],
                'volatility_indicators' => [
                    'bb' => '&bb=period,shift,deviation (e.g., &bb=20,0,2.0)',
                    'atr' => '&atr=period (e.g., &atr=14)',
                    'envelopes' => '&envelopes=period,deviation (e.g., &envelopes=14,0.1)',
                    'stddev' => '&stddev=period (e.g., &stddev=20)'
                ],
                'volume_indicators' => [
                    'obv' => '&obv=volume_type (e.g., &obv=0 for tick, &obv=1 for real)'
                ],
                'bill_williams' => [
                    'ac' => '&ac (no parameters)',
                    'ao' => '&ao (no parameters)',
                    'alligator' => '&alligator=jaw,teeth,lips (e.g., &alligator=13,8,5)',
                    'fractals' => '&fractals (no parameters)'
                ],
                'other_indicators' => [
                    'demarker' => '&demarker=period (e.g., &demarker=14)'
                ]
            ],
            'complete_examples' => [
                'basic_with_volume' => '?api_key=abc123&symbol=EURUSD&timeframe=H1&count=100&candle-volume=true',
                'basic_without_volume' => '?api_key=abc123&symbol=EURUSD&timeframe=H1&count=100',
                'volume_data_only' => '?api_key=abc123&symbol=EURUSD&timeframe=H1&count=100&dataField=volume',
                'complex_with_indicators_and_volume' => '?api_key=abc123&symbol=EURUSD&rangeType=last-hour&timeframe=M5&rsi=14&ema_1=e,20&bb=20,0,2.0&candlevolume=true',
                'trading_strategy_setup' => '?api_key=abc123&symbol=EURUSD&rangeType=today&timeframe=M15&ema_1=e,20&ema_2=e,50&macd=12,26,9&rsi=14&atr=14&volume=true'
            ],
            'new_features_v1_4' => [
                'multiple_volume_parameters' => 'Support for candle-volume, candlevolume, and volume parameters',
                'enhanced_volume_validation' => 'Proper validation and error handling for volume parameters',
                'volume_parameter_detection' => 'Automatic detection and forwarding of volume parameters to EA',
                'improved_logging' => 'Enhanced debug logging for volume parameter processing',
                'backward_compatibility' => 'Full backward compatibility with existing volume functionality'
            ]
        ]
    ]
], JSON_PRETTY_PRINT);
exit;
?>