<?php
/**
 * TMP Get Tool API
 * GET /api/tmp-get-tool?api_key={key}&search_phrase={phrase}
 *
 * Returns tool_format and inputs_explanation for the tool matching
 * the given search_phrase (exact match, case-insensitive).
 */

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    require_once __DIR__ . '/../../app/Database.php';

    $pdo = Database::getInstance()->getConnection();

    // ── Load settings (api_key + app_base_url) ───────────────────────────────
    $settingsStmt = $pdo->query("SELECT key, value FROM settings WHERE key IN ('api_key','app_base_url')");
    $settings = [];
    foreach ($settingsStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $settings[$row['key']] = $row['value'];
    }

    $storedApiKey = $settings['api_key'] ?? null;
    $baseUrl      = rtrim($settings['app_base_url'] ?? '', '/');

    // ── Auth ──────────────────────────────────────────────────────────────────
    $api_key = $_GET['api_key'] ?? null;

    if (!$api_key) {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        exit;
    }

    if (!$storedApiKey || $storedApiKey !== $api_key) {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        exit;
    }

    // ── Require search_phrase ─────────────────────────────────────────────────
    $searchPhrase = trim($_GET['search_phrase'] ?? '');

    if ($searchPhrase === '') {
        http_response_code(200);
        echo json_encode([
            'success' => false,
            'error'   => 'Missing search_phrase parameter',
            'hint'    => 'Add &search_phrase={phrase} to your request. Call /api/tmp-tool-capabilities to list all valid search_phrase values.',
        ]);
        exit;
    }

    // ── Lookup tool ───────────────────────────────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT t.tool_name,
               t.search_phrase,
               t.tool_format,
               t.inputs_explanation,
               t.description,
               tc.name AS category
        FROM tools t
        JOIN tool_categories tc ON tc.id = t.category_id
        WHERE LOWER(t.search_phrase) = LOWER(?)
          AND t.enabled = 1
        LIMIT 1
    ");
    $stmt->execute([$searchPhrase]);
    $tool = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tool) {
        http_response_code(200);
        echo json_encode([
            'success'       => false,
            'error'         => "No tool found for search_phrase: \"{$searchPhrase}\"",
            'hint'          => 'Your search_phrase did not match any tool exactly. Call /api/tmp-tool-capabilities to retrieve the full list of valid search_phrase values, then retry with the correct one.',
        ]);
        exit;
    }

    echo json_encode([
        'search_phrase'      => $tool['search_phrase'],
        'tool_url'           => str_replace(['{base_url}', '{api_key}'], [$baseUrl, $storedApiKey], $tool['tool_format']),
        'inputs_explanation' => array_values(array_filter(array_map('trim', explode("\n", $tool['inputs_explanation'])))),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
