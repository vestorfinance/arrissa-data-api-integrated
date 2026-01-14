<?php
/**
 * Fix NFP Times in Database
 * 
 * NFP (Nonfarm Payrolls) is always released at 8:30 AM US Eastern Time
 * - During EST (Nov-Mar): 8:30 AM EST = 13:30 UTC
 * - During EDT (Mar-Nov): 8:30 AM EDT = 12:30 UTC
 * 
 * This script corrects events stored with 11:30 UTC (incorrect) to 12:30 UTC
 */

require_once __DIR__ . '/../app/Database.php';

$pdo = Database::getInstance()->getConnection();

// Get all NFP events with time 11:30:00
$stmt = $pdo->prepare("
    SELECT event_id, event_name, event_date, event_time 
    FROM economic_events 
    WHERE consistent_event_id = 'VPRWG' 
    AND event_time = '11:30:00'
    ORDER BY event_date
");
$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($events)) {
    echo "✅ No NFP events found with incorrect time 11:30:00 UTC\n";
    exit(0);
}

echo "Found " . count($events) . " NFP events with incorrect time 11:30:00 UTC:\n\n";

foreach ($events as $event) {
    $date = new DateTime($event['event_date']);
    $year = (int)$date->format('Y');
    $month = (int)$date->format('m');
    $day = (int)$date->format('d');
    
    // Determine if date is during DST (2nd Sunday of March to 1st Sunday of November)
    // For simplicity, approximate: March 8 - November 7 (varies by year)
    $isDST = false;
    if ($month > 3 && $month < 11) {
        $isDST = true;
    } elseif ($month == 3 && $day >= 8) {
        $isDST = true;
    } elseif ($month == 11 && $day < 7) {
        $isDST = true;
    }
    
    $correctTime = $isDST ? '12:30:00' : '13:30:00';
    
    echo "- {$event['event_name']} ({$event['event_date']}): ";
    echo "11:30:00 → {$correctTime} " . ($isDST ? "(DST)" : "(EST)") . "\n";
}

echo "\n";
$input = readline("Do you want to fix these times? (yes/no): ");

if (strtolower(trim($input)) !== 'yes') {
    echo "❌ Operation cancelled.\n";
    exit(0);
}

// Update all NFP events with time 11:30:00 to 12:30:00
// (Most will be during DST period, so 12:30 UTC is correct)
$updateStmt = $pdo->prepare("
    UPDATE economic_events 
    SET event_time = '12:30:00' 
    WHERE consistent_event_id = 'VPRWG' 
    AND event_time = '11:30:00'
");

$result = $updateStmt->execute();
$count = $updateStmt->rowCount();

if ($result) {
    echo "✅ Successfully updated {$count} NFP events to 12:30:00 UTC\n";
    echo "\n⚠️  NOTE: If any of these events occurred during EST period (Jan-Mar, Nov-Dec),\n";
    echo "   they should be manually updated to 13:30:00 UTC instead.\n";
} else {
    echo "❌ Failed to update events\n";
}

// Show updated records
echo "\n📊 Updated records:\n";
$stmt = $pdo->prepare("
    SELECT event_id, event_name, event_date, event_time 
    FROM economic_events 
    WHERE consistent_event_id = 'VPRWG' 
    AND event_date IN (SELECT event_date FROM economic_events WHERE consistent_event_id = 'VPRWG' AND event_time = '12:30:00')
    ORDER BY event_date
");
$stmt->execute();
$updatedEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($updatedEvents as $event) {
    echo "✓ {$event['event_name']} | {$event['event_date']} | {$event['event_time']}\n";
}

echo "\n";
