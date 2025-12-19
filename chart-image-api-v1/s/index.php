<?php
/**
 * Short URL handler for chart streaming
 * Redirects /chart-image-api-v1/s/{code} to the full streaming URL
 */

require_once __DIR__ . '/../../app/Database.php';

// Get short code from URL
$path = $_SERVER['REQUEST_URI'];
preg_match('/\/chart-image-api-v1\/s\/([a-zA-Z0-9]+)/', $path, $matches);
$shortCode = $matches[1] ?? null;

if (!$shortCode) {
    header('HTTP/1.1 404 Not Found');
    echo 'Invalid streaming URL';
    exit;
}

// Look up in database
$db = Database::getInstance();
$pdo = $db->getConnection();

$stmt = $pdo->prepare("SELECT params FROM chart_stream_urls WHERE short_code = ?");
$stmt->execute([$shortCode]);
$result = $stmt->fetch();

if (!$result) {
    header('HTTP/1.1 404 Not Found');
    echo 'Streaming URL not found or expired';
    exit;
}

// Decode params and redirect to streaming page
$params = json_decode($result['params'], true);
$queryString = http_build_query($params);

// Redirect to the actual streaming page
header('Location: /chart-image-api-v1/chart-stream.php?' . $queryString);
exit;
