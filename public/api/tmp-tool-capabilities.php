<?php
/**
 * TMP Tool Capabilities API
 * GET /api/tmp-tool-capabilities?api_key={key}
 * GET /api/tmp-tool-capabilities?api_key={key}&category_name={name}
 *
 * Returns a list of search_phrase values.
 * - With category_name: only phrases for that category
 * - Without:           all phrases grouped by category
 */

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    require_once __DIR__ . '/../../app/Database.php';

    $pdo = Database::getInstance()->getConnection();

    // ── Auth ──────────────────────────────────────────────────────────────────
    $api_key = $_GET['api_key'] ?? null;

    if (!$api_key) {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT value FROM settings WHERE key = 'api_key'");
    $stmt->execute();
    $setting = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$setting || $setting['value'] !== $api_key) {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        exit;
    }

    // ── Filter by category_name? ──────────────────────────────────────────────
    $categoryName = trim($_GET['category_name'] ?? '');

    if ($categoryName !== '') {
        // Validate the category exists
        $catStmt = $pdo->prepare("SELECT id, name FROM tool_categories WHERE name = ?");
        $catStmt->execute([$categoryName]);
        $cat = $catStmt->fetch(PDO::FETCH_ASSOC);

        if (!$cat) {
            http_response_code(200);
            echo json_encode([
                'success' => false,
                'error'   => "Category '{$categoryName}' not found",
                'hint'    => 'Call /api/tmp-categories to see all available category names.',
            ]);
            exit;
        }

        $rows = $pdo->prepare("
            SELECT search_phrase
            FROM tools
            WHERE category_id = ? AND enabled = 1
            ORDER BY id
        ");
        $rows->execute([$cat['id']]);
        $phrases = array_column($rows->fetchAll(PDO::FETCH_ASSOC), 'search_phrase');

        echo json_encode([
            'status'        => 'success',
            'category'      => $categoryName,
            'count'         => count($phrases),
            'search_phrases' => $phrases,
        ], JSON_PRETTY_PRINT);

    } else {
        // Return all, grouped by category
        $rows = $pdo->query("
            SELECT tc.name AS category, t.search_phrase
            FROM tools t
            JOIN tool_categories tc ON tc.id = t.category_id
            WHERE t.enabled = 1
            ORDER BY tc.id, t.id
        ")->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['category']][] = $row['search_phrase'];
        }

        $result = [];
        foreach ($grouped as $cat => $phrases) {
            $result[] = [
                'category'       => $cat,
                'count'          => count($phrases),
                'search_phrases' => $phrases,
            ];
        }

        $total = array_sum(array_column($result, 'count'));

        echo json_encode([
            'status'     => 'success',
            'total'      => $total,
            'categories' => $result,
        ], JSON_PRETTY_PRINT);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'message' => $e->getMessage()]);
}
