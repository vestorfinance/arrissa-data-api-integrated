<?php
/**
 * Truncate Economic Events API
 * Permanently deletes all events from the database
 * 
 * Requires authentication
 */

header('Content-Type: application/json');

// Verify auth
require_once __DIR__ . '/../../app/Auth.php';
Auth::check();

try {
    $db = new PDO('sqlite:' . __DIR__ . '/../../database/app.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get count before deletion
    $stmt = $db->query("SELECT COUNT(*) as count FROM economic_events");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $deletedCount = $result['count'] ?? 0;
    
    // Delete all events
    $db->exec("DELETE FROM economic_events");
    
    // Get count after deletion (should be 0)
    $stmt = $db->query("SELECT COUNT(*) as count FROM economic_events");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $remainingCount = $result['count'] ?? 0;
    
    echo json_encode([
        'success'   => true,
        'deleted'   => $deletedCount,
        'remaining' => $remainingCount,
        'message'   => "Successfully deleted $deletedCount events from the database"
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Database error: ' . $e->getMessage()
    ]);
}
