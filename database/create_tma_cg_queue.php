<?php
/**
 * Create TMA+CG Queue Table
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Database.php';

$db = new Database();
$pdo = $db->getConnection();

$sql = "CREATE TABLE IF NOT EXISTS tma_cg_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id VARCHAR(255) UNIQUE NOT NULL,
    symbol VARCHAR(50) NOT NULL,
    timeframe VARCHAR(10) NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'error') DEFAULT 'pending',
    response_data TEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_request_id (request_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

try {
    $pdo->exec($sql);
    echo "✓ TMA+CG queue table created successfully\n";
} catch (PDOException $e) {
    echo "✗ Error creating table: " . $e->getMessage() . "\n";
}
