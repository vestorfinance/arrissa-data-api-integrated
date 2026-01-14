<?php
require_once __DIR__ . '/app/Database.php';

$pdo = Database::getInstance()->getConnection();

// Check forecast values for today's events
$stmt = $pdo->query("
    SELECT event_name, event_date, event_time, forecast_value, actual_value, previous_value 
    FROM economic_events 
    WHERE event_date = '2026-01-09' 
    AND event_name LIKE '%Nonfarm%'
    LIMIT 5
");

echo "Database Check for Nonfarm Payrolls on 2026-01-09:\n";
echo "=================================================\n\n";

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Event: " . $row['event_name'] . "\n";
    echo "Date: " . $row['event_date'] . " " . $row['event_time'] . "\n";
    echo "Forecast: " . ($row['forecast_value'] ?? 'NULL') . " (empty? " . (empty($row['forecast_value']) ? 'YES' : 'NO') . ")\n";
    echo "Actual: " . ($row['actual_value'] ?? 'NULL') . "\n";
    echo "Previous: " . ($row['previous_value'] ?? 'NULL') . "\n";
    echo "\n";
}
?>