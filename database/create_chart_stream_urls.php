<?php
/**
 * Database migration: Create chart_stream_urls table
 */

require_once __DIR__ . '/../app/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

$sql = "CREATE TABLE IF NOT EXISTS chart_stream_urls (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    short_code TEXT UNIQUE NOT NULL,
    params TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";

try {
    $pdo->exec($sql);
    echo "Table 'chart_stream_urls' created successfully!\n";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
