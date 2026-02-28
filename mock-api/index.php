<?php
/**
 * Mock API - Friendly Data Provider
 * Endpoint: /mock-api/?data=<type>
 *
 * Available types:
 *   market-data-H1  – Hourly OHLC candle data
 *   market-data-H4  – 4-hour OHLC candle data
 *   market-data-D1  – Daily OHLC candle data
 *   calendar-data   – Economic calendar events
 *   chart-image     – Sample chart image (PNG)
 *
 * Security:
 *   - Strict whitelist; no user-supplied paths reach the filesystem
 *   - Data files are stored in data/ with their own .htaccess deny-all
 *   - Directory listing is disabled via mock-api/.htaccess
 */

// ─── Strict whitelist ──────────────────────────────────────────────────────
$DATA_DIR = __DIR__ . '/data/';

$allowedData = [
    'market-data-H1' => 'market-data-H1.json',
    'market-data-H4' => 'market-data-H4.json',
    'market-data-D1' => 'market-data-D1.json',
    'calendar-data'  => 'calendar-data.json',
    'chart-image'    => 'chart-image.png',
];

// ─── Read & sanitise the ?data= parameter ─────────────────────────────────
$requested = isset($_GET['data']) ? trim($_GET['data']) : '';

// ─── Helper: build a JSON error / welcome response ─────────────────────────
function jsonResponse(bool $success, string $message, $data = null): void
{
    $body = [
        'success'   => $success,
        'message'   => $message,
        'timestamp' => date('Y-m-d H:i:s'),
    ];

    if ($data !== null) {
        $body['data'] = $data;
    }

    if (!$success) {
        global $allowedData;
        $body['available_endpoints'] = [
            'market-data-H1' => '?data=market-data-H1  –  Hourly market data',
            'market-data-H4' => '?data=market-data-H4  –  4-hour market data',
            'market-data-D1' => '?data=market-data-D1  –  Daily market data',
            'calendar-data'  => '?data=calendar-data   –  Economic calendar events',
            'chart-image'    => '?data=chart-image     –  Chart image (PNG)',
        ];
    }

    echo json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// ─── Handle chart-image before JSON headers ────────────────────────────────
if ($requested === 'chart-image') {
    $filePath = $DATA_DIR . $allowedData['chart-image'];

    if (!file_exists($filePath)) {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        jsonResponse(false, "Chart image file is missing. Try again later.");
    }

    $imageInfo = @getimagesize($filePath);
    if ($imageInfo === false) {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        jsonResponse(false, "Chart image appears to be corrupted. Try again later.");
    }

    header('Content-Type: ' . $imageInfo['mime']);
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: public, max-age=3600');
    header('Access-Control-Allow-Origin: *');
    readfile($filePath);
    exit;
}

// ─── Set JSON headers for everything else ─────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// ─── Welcome message when no ?data= provided ──────────────────────────────
if ($requested === '') {
    jsonResponse(false, "Welcome to the Mock API! Please supply a ?data= parameter.");
}

// ─── Reject anything not on the whitelist ─────────────────────────────────
if (!array_key_exists($requested, $allowedData)) {
    jsonResponse(false, "Unknown data type '$requested'. See available_endpoints below.");
}

// ─── Resolve the absolute path (whitelist only – no user input in path) ────
$filePath = $DATA_DIR . $allowedData[$requested];

if (!file_exists($filePath)) {
    jsonResponse(false, "Data file is temporarily unavailable. Try another endpoint.");
}

$raw = file_get_contents($filePath);
if ($raw === false) {
    jsonResponse(false, "Unable to read the data file right now. Please try again.");
}

$jsonData = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    jsonResponse(false, "Data file has a formatting issue. Our team will fix it soon.");
}

// ─── Success ───────────────────────────────────────────────────────────────
$response = [
    'success'  => true,
    'message'  => 'Data retrieved successfully.',
    'metadata' => [
        'data_type' => $requested,
        'file_size' => strlen($raw),
        'timestamp' => date('Y-m-d H:i:s'),
    ],
    'data' => $jsonData,
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
