<?php
$db = new PDO('sqlite:' . __DIR__ . '/database/app.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Check total events
$stmt = $db->query("SELECT COUNT(*) as cnt FROM economic_events");
$count = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Total events: " . $count['cnt'] . "\n\n";

// Check sample events
echo "Sample events:\n";
$stmt = $db->query("SELECT event_date, event_time, event_name FROM economic_events LIMIT 5");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($events as $e) {
    $datetime = $e['event_date'] . ' ' . $e['event_time'];
    echo "Date: {$e['event_date']} | Time: {$e['event_time']} | Combined: $datetime | Name: {$e['event_name']}\n";
}

// Check date range in database
echo "\nDate range in database:\n";
$stmt = $db->query("SELECT MIN(event_date) as min_date, MAX(event_date) as max_date FROM economic_events");
$range = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Min date: {$range['min_date']} | Max date: {$range['max_date']}\n";

// Check what last-7-days should return
$now = new DateTime('now', new DateTimeZone('UTC'));
$start = (clone $now)->modify('-7 days')->setTime(0, 0, 0);
$end = clone $now;

$startStr = $start->format("Y-m-d H:i:s");
$endStr = $end->format("Y-m-d H:i:s");

echo "\nLast 7 days range:\n";
echo "Start: $startStr\n";
echo "End: $endStr\n";

// Test the actual query from news-api.php
echo "\nTesting query with last-7-days range:\n";
$sql = "SELECT COUNT(*) as cnt FROM economic_events 
        WHERE (event_date || ' ' || event_time) BETWEEN :start AND :end";
$stmt = $db->prepare($sql);
$stmt->execute([':start' => $startStr, ':end' => $endStr]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Events found: " . $result['cnt'] . "\n";

// Test without time concatenation
echo "\nTesting with date only (no time concat):\n";
$sql2 = "SELECT COUNT(*) as cnt FROM economic_events 
         WHERE event_date BETWEEN :start AND :end";
$stmt2 = $db->prepare($sql2);
$stmt2->execute([':start' => $start->format('Y-m-d'), ':end' => $end->format('Y-m-d')]);
$result2 = $stmt2->fetch(PDO::FETCH_ASSOC);
echo "Events found: " . $result2['cnt'] . "\n";

?>
