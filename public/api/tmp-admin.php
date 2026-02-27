<?php
/**
 * TMP Admin API  —  unified CRUD for tools & categories
 * Protected by session auth (same as all admin pages)
 *
 * GET  /api/tmp-admin?action=get_all
 * POST /api/tmp-admin  {action, ...payload}
 *
 * Actions:
 *   toggle_tool, add_tool, update_tool, delete_tool
 *   add_category, update_category, delete_category
 */

// Prevent PHP notices/warnings from corrupting the JSON response
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../../app/Auth.php';
Auth::check();

header('Content-Type: application/json');

try {

$pdo = new PDO('sqlite:' . __DIR__ . '/../../database/app.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Auto-migrate: add columns that may be missing from older DB installs
$existingCols = array_column(
    $pdo->query("PRAGMA table_info(tools)")->fetchAll(PDO::FETCH_ASSOC),
    'name'
);
if (!in_array('auth_method', $existingCols)) {
    $pdo->exec("ALTER TABLE tools ADD COLUMN auth_method TEXT DEFAULT 'api_key_query'");
}
if (!in_array('response_type', $existingCols)) {
    $pdo->exec("ALTER TABLE tools ADD COLUMN response_type TEXT DEFAULT 'json'");
}

$method = $_SERVER['REQUEST_METHOD'];

// ── GET: read data ─────────────────────────────────────────────────────────
if ($method === 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action === 'get_all') {
        // Build optional exclude filter  e.g. ?exclude=chart-images,orders
        $excludeRaw = trim($_GET['exclude'] ?? '');
        $excludeList = $excludeRaw !== ''
            ? array_map('trim', explode(',', $excludeRaw))
            : [];

        if ($excludeList) {
            $placeholders = implode(',', array_fill(0, count($excludeList), '?'));
            $stmt = $pdo->prepare("
                SELECT tc.*, COUNT(t.id) AS tool_count
                FROM tool_categories tc
                LEFT JOIN tools t ON t.category_id = tc.id
                WHERE tc.name NOT IN ($placeholders)
                GROUP BY tc.id
                ORDER BY tc.id
            ");
            $stmt->execute($excludeList);
            $cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $cats = $pdo->query("
                SELECT tc.*, COUNT(t.id) AS tool_count
                FROM tool_categories tc
                LEFT JOIN tools t ON t.category_id = tc.id
                GROUP BY tc.id
                ORDER BY tc.id
            ")->fetchAll(PDO::FETCH_ASSOC);
        }

        foreach ($cats as &$cat) {
            $s = $pdo->prepare("SELECT * FROM tools WHERE category_id = ? ORDER BY id");
            $s->execute([$cat['id']]);
            $cat['tools'] = $s->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode(['status' => 'success', 'categories' => $cats]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Unknown GET action']);
    exit;
}

// ── POST: mutations ────────────────────────────────────────────────────────
if ($method === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $body['action'] ?? '';

    switch ($action) {

        // ── Toggle tool enabled / disabled ──────────────────────────────
        case 'toggle_tool': {
            $id = (int)($body['id'] ?? 0);
            if (!$id) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }
            $pdo->prepare("UPDATE tools SET enabled = CASE WHEN enabled=1 THEN 0 ELSE 1 END WHERE id = ?")->execute([$id]);
            $s = $pdo->prepare("SELECT enabled FROM tools WHERE id = ?");
            $s->execute([$id]);
            echo json_encode(['status' => 'success', 'enabled' => (int)$s->fetchColumn()]);
            break;
        }

        // ── Add new tool ─────────────────────────────────────────────────
        case 'add_tool': {
            $catId = (int)($body['category_id'] ?? 0);
            $name  = trim($body['tool_name'] ?? '');
            $phrase = trim($body['search_phrase'] ?? '');
            $format = trim($body['tool_format'] ?? '');
            if (!$catId || !$name || !$phrase || !$format) {
                http_response_code(400);
                echo json_encode(['error' => 'category_id, tool_name, search_phrase, tool_format are required']);
                exit;
            }
            $pdo->prepare("
                INSERT INTO tools
                  (category_id, tool_name, search_phrase, tool_format,
                   inputs_explanation, description, auth_method, response_type,
                   enabled, created_at)
                VALUES (?,?,?,?,?,?,?,?,1,datetime('now'))
            ")->execute([
                $catId,
                $name,
                $phrase,
                $format,
                trim($body['inputs_explanation'] ?? ''),
                trim($body['description'] ?? ''),
                trim($body['auth_method'] ?? 'api_key'),
                trim($body['response_type'] ?? 'JSON'),
            ]);
            echo json_encode(['status' => 'success', 'id' => (int)$pdo->lastInsertId()]);
            break;
        }

        // ── Update existing tool ─────────────────────────────────────────
        case 'update_tool': {
            $id    = (int)($body['id'] ?? 0);
            $catId = (int)($body['category_id'] ?? 0);
            $name  = trim($body['tool_name'] ?? '');
            $phrase = trim($body['search_phrase'] ?? '');
            $format = trim($body['tool_format'] ?? '');
            if (!$id || !$catId || !$name || !$phrase || !$format) {
                http_response_code(400);
                echo json_encode(['error' => 'id, category_id, tool_name, search_phrase, tool_format are required']);
                exit;
            }
            $pdo->prepare("
                UPDATE tools SET
                  category_id=?, tool_name=?, search_phrase=?, tool_format=?,
                  inputs_explanation=?, description=?, auth_method=?, response_type=?
                WHERE id=?
            ")->execute([
                $catId,
                $name,
                $phrase,
                $format,
                trim($body['inputs_explanation'] ?? ''),
                trim($body['description'] ?? ''),
                trim($body['auth_method'] ?? 'api_key'),
                trim($body['response_type'] ?? 'JSON'),
                $id,
            ]);
            echo json_encode(['status' => 'success']);
            break;
        }

        // ── Delete tool ──────────────────────────────────────────────────
        case 'delete_tool': {
            $id = (int)($body['id'] ?? 0);
            if (!$id) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }
            $pdo->prepare("DELETE FROM tools WHERE id = ?")->execute([$id]);
            echo json_encode(['status' => 'success']);
            break;
        }

        // ── Add new category ─────────────────────────────────────────────
        case 'add_category': {
            $name = trim($body['name'] ?? '');
            if (!$name) { http_response_code(400); echo json_encode(['error' => 'name is required']); exit; }
            $pdo->prepare("
                INSERT INTO tool_categories
                  (name, description, endpoint_base, requires_ea, ea_name, created_at)
                VALUES (?,?,?,?,?,datetime('now'))
            ")->execute([
                $name,
                trim($body['description'] ?? ''),
                trim($body['endpoint_base'] ?? ''),
                (int)($body['requires_ea'] ?? 0),
                trim($body['ea_name'] ?? ''),
            ]);
            echo json_encode(['status' => 'success', 'id' => (int)$pdo->lastInsertId()]);
            break;
        }

        // ── Update existing category ─────────────────────────────────────
        case 'update_category': {
            $id   = (int)($body['id'] ?? 0);
            $name = trim($body['name'] ?? '');
            if (!$id || !$name) { http_response_code(400); echo json_encode(['error' => 'id and name are required']); exit; }
            $pdo->prepare("
                UPDATE tool_categories
                SET name=?, description=?, endpoint_base=?, requires_ea=?, ea_name=?
                WHERE id=?
            ")->execute([
                $name,
                trim($body['description'] ?? ''),
                trim($body['endpoint_base'] ?? ''),
                (int)($body['requires_ea'] ?? 0),
                trim($body['ea_name'] ?? ''),
                $id,
            ]);
            echo json_encode(['status' => 'success']);
            break;
        }

        // ── Delete category (and all its tools) ──────────────────────────
        case 'delete_category': {
            $id = (int)($body['id'] ?? 0);
            if (!$id) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }
            $pdo->prepare("DELETE FROM tools WHERE category_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM tool_categories WHERE id = ?")->execute([$id]);
            echo json_encode(['status' => 'success']);
            break;
        }

        default:
            http_response_code(400);
            echo json_encode(['error' => "Unknown action: {$action}"]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
