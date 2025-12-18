<?php
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Database.php';
Auth::check();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::getInstance();
    $newApiKey = 'arr_' . bin2hex(random_bytes(8));
    
    $db->query(
        "INSERT OR REPLACE INTO settings (key, value, updated_at) VALUES (?, ?, datetime('now'))",
        ['api_key', $newApiKey]
    );
    
    header('Location: /settings?success=api_key');
    exit;
}

header('Location: /settings');
exit;
