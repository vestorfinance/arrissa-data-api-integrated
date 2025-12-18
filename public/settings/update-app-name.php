<?php
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Database.php';
Auth::check();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::getInstance();
    $appName = $_POST['app_name'] ?? 'Arrissa Data API';
    
    $db->query(
        "INSERT OR REPLACE INTO settings (key, value, updated_at) VALUES (?, ?, datetime('now'))",
        ['app_name', $appName]
    );
    
    header('Location: /settings?success=app_name');
    exit;
}

header('Location: /settings');
exit;
