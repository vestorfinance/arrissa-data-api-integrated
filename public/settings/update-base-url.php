<?php
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Database.php';
Auth::check();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::getInstance();
    $appBaseUrl = $_POST['app_base_url'] ?? 'http://' . $_SERVER['HTTP_HOST'];
    
    $db->query(
        "INSERT OR REPLACE INTO settings (key, value, updated_at) VALUES (?, ?, datetime('now'))",
        ['app_base_url', $appBaseUrl]
    );
    
    header('Location: /settings?success=base_url');
    exit;
}

header('Location: /settings');
exit;
