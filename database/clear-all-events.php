<?php
/**
 * Clear All Events from Database
 * Use this to reset the database before running the scraper
 */

require_once __DIR__ . '/../app/Database.php';

$pdo = Database::getInstance()->getConnection();

// Count events before deletion
$stmt = $pdo->query("SELECT COUNT(*) as count FROM economic_events");
$count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

echo "Current events in database: {$count}\n\n";

if ($count === 0) {
    echo "✅ Database is already empty.\n";
    exit(0);
}

$input = readline("Are you sure you want to delete ALL {$count} events? (yes/no): ");

if (strtolower(trim($input)) !== 'yes') {
    echo "❌ Operation cancelled.\n";
    exit(0);
}

// Delete all events
$pdo->exec("DELETE FROM economic_events");

// Verify deletion
$stmt = $pdo->query("SELECT COUNT(*) as count FROM economic_events");
$remaining = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

if ($remaining === 0) {
    echo "✅ Successfully deleted all events from database.\n";
    echo "✅ Database is now empty and ready for scraper.\n";
} else {
    echo "❌ Failed to delete all events. {$remaining} events remaining.\n";
}
