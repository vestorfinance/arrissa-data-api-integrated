<?php
/**
 * TMP Categories API
 * GET /api/tmp-categories?api_key={key}
 *
 * Returns all tool categories with their tool counts.
 * Requires a valid api_key.
 */

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    require_once __DIR__ . '/../../app/Database.php';

    $db  = Database::getInstance();
    $pdo = $db->getConnection();

    // ── Auth ──────────────────────────────────────────────────────────────────
    $api_key = $_GET['api_key'] ?? null;

    if (!$api_key) {
        http_response_code(401);
        echo json_encode(['error' => 'Missing api_key parameter']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT value FROM settings WHERE key = 'api_key'");
    $stmt->execute();
    $setting = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$setting || $setting['value'] !== $api_key) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid API key']);
        exit;
    }

    // ── Query ─────────────────────────────────────────────────────────────────
    $rows = $pdo->query("
        SELECT
            tc.id,
            tc.name,
            tc.description,
            tc.endpoint_base,
            tc.requires_ea,
            tc.ea_name,
            tc.created_at,
            COUNT(t.id)         AS tool_count,
            SUM(t.enabled)      AS enabled_count
        FROM tool_categories tc
        LEFT JOIN tools t ON tc.id = t.category_id
        GROUP BY tc.id
        ORDER BY tc.id
    ")->fetchAll(PDO::FETCH_ASSOC);

    $categories = array_map(function ($r) {
        return [
            'name'        => $r['name'],
            'description' => $r['description'],
        ];
    }, $rows);

    echo json_encode([
        'status'     => 'success',
        'count'      => count($categories),
        'categories' => $categories,
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'message' => $e->getMessage()]);
}
