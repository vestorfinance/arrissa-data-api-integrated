<?php
require_once __DIR__ . '/../app/Database.php';

$db = Database::getInstance();

// Get base URL
$stmt = $db->query("SELECT value FROM settings WHERE key = 'app_base_url'");
$result = $stmt->fetch();
$baseUrl = $result ? $result['value'] : '';

// Get API key
$stmt = $db->query("SELECT value FROM settings WHERE key = 'api_key'");
$result = $stmt->fetch();
$apiKey = $result ? $result['value'] : '';

// Build test URL
$testUrl = $baseUrl . '/chart-image-api-v1/chart-image-api.php?api_key=' . urlencode($apiKey) . '&symbol=EURUSD&timeframe=H1';

echo "Testing Chart Image API\n";
echo "=======================\n\n";
echo "Base URL: " . $baseUrl . "\n";
echo "API Key: " . $apiKey . "\n";
echo "Test URL: " . $testUrl . "\n\n";

// Make request
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'ignore_errors' => true
    ]
]);

$response = file_get_contents($testUrl, false, $context);

// Check response
if ($response === false) {
    echo "ERROR: Failed to fetch chart\n";
    exit(1);
}

// Check if it's an image
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->buffer($response);

echo "Response MIME type: " . $mimeType . "\n";

if (strpos($mimeType, 'image') !== false) {
    echo "✓ Chart image generated successfully!\n";
    echo "Image size: " . strlen($response) . " bytes\n";
} else {
    echo "ERROR: Response is not an image\n";
    echo "Response content: " . substr($response, 0, 500) . "\n";
}
