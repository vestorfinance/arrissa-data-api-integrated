<?php
/**
 * Latest Economic Events API
 *
 * Returns the most recent occurrence of EVERY distinct event type
 * (grouped by consistent_event_id) up to the given date/time cutoff.
 *
 * Parameters:
 *   api_key        (required)
 *   currency       optional  – comma-separated e.g. USD,EUR
 *   event_id       optional  – comma-separated consistent_event_ids e.g. USD_NFP,EUR_CPI
 *   impact         optional  – comma-separated impact levels: High, Medium, Low
 *   pretend_date   optional  – treat this date as "today"  YYYY-MM-DD
 *   pretend_time   optional  – used with pretend_date       HH:MM or HH:MM:SS  (UTC)
 *   must_have      optional  – require actual_value: "actual"
 *
 * Response: JSON array of one record per event type (latest occurrence only)
 */

ini_set('max_execution_time', 30);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../app/Database.php';

// ── Auth ─────────────────────────────────────────────────────────────────────
$pdo = Database::getInstance()->getConnection();

$apiKeyParam = $_GET['api_key'] ?? null;
if (!$apiKeyParam) {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    exit;
}
$stmt = $pdo->prepare("SELECT value FROM settings WHERE key = 'api_key'");
$stmt->execute();
$validKey = ($stmt->fetch()['value'] ?? '');
if ($apiKeyParam !== $validKey) {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    exit;
}

// ── Parameters ───────────────────────────────────────────────────────────────
$currency     = trim($_GET['currency']     ?? '');
$event_id     = trim($_GET['event_id']     ?? '');
$impact       = trim($_GET['impact']       ?? '');
$must_have    = trim($_GET['must_have']    ?? '');
$pretend_date = trim($_GET['pretend_date'] ?? '');
$pretend_time = trim($_GET['pretend_time'] ?? '');

// ── Cutoff datetime (UTC) ─────────────────────────────────────────────────────
if ($pretend_date) {
    $timePart = $pretend_time ?: '23:59:59';
    if (substr_count($timePart, ':') === 1) { $timePart .= ':00'; }
    $cutoff = DateTime::createFromFormat('Y-m-d H:i:s', "{$pretend_date} {$timePart}", new DateTimeZone('UTC'));
    if (!$cutoff) {
        echo json_encode(['error' => 'Invalid pretend_date/pretend_time format. Use YYYY-MM-DD and HH:MM:SS']);
        exit;
    }
} else {
    $cutoff = new DateTime('now', new DateTimeZone('UTC'));
}

$cutoffDatetime = $cutoff->format('Y-m-d') . 'T' . $cutoff->format('H:i:s');

// ── Build filters for the inner subquery ─────────────────────────────────────
$filterSql    = '';
$filterParams = [];

// Currency
if ($currency !== '') {
    $codes   = array_filter(array_map('strtoupper', array_map('trim', explode(',', $currency))));
    $holders = [];
    foreach ($codes as $i => $c) {
        $key            = ":cur{$i}";
        $holders[]      = $key;
        $filterParams[$key] = $c;
    }
    if ($holders) {
        $filterSql .= " AND currency IN (" . implode(',', $holders) . ")";
    }
}

// Event ID (consistent_event_id)
if ($event_id !== '') {
    $ids     = array_filter(array_map('strtoupper', array_map('trim', explode(',', $event_id))));
    $holders = [];
    foreach ($ids as $i => $id) {
        $key            = ":eid{$i}";
        $holders[]      = $key;
        $filterParams[$key] = $id;
    }
    if ($holders) {
        $filterSql .= " AND consistent_event_id IN (" . implode(',', $holders) . ")";
    }
}

// Impact
if ($impact !== '') {
    $levels  = array_filter(array_map('trim', explode(',', $impact)));
    $holders = [];
    foreach ($levels as $i => $lv) {
        $key            = ":imp{$i}";
        $holders[]      = $key;
        $filterParams[$key] = ucfirst(strtolower($lv));
    }
    if ($holders) {
        $filterSql .= " AND impact_level IN (" . implode(',', $holders) . ")";
    }
}

// must_have actual_value
if (strtolower($must_have) === 'actual') {
    $filterSql .= " AND actual_value IS NOT NULL AND actual_value != '' AND actual_value != 'TBD'";
}

// ── Main query ────────────────────────────────────────────────────────────────
//
//  For each consistent_event_id, find the row whose (event_date || T || event_time)
//  is the maximum that is still <= the cutoff.
//
//  We use a self-join: inner subquery finds the max datetime per group,
//  outer query fetches the full row for that datetime.
//
$sql = "
    SELECT e.*
    FROM economic_events e
    INNER JOIN (
        SELECT
            consistent_event_id,
            MAX(event_date || 'T' || event_time) AS max_dt
        FROM economic_events
        WHERE (event_date || 'T' || event_time) <= :cutoff
          AND consistent_event_id IS NOT NULL
          AND consistent_event_id != ''
          {$filterSql}
        GROUP BY consistent_event_id
    ) latest
      ON  e.consistent_event_id = latest.consistent_event_id
      AND (e.event_date || 'T' || e.event_time) = latest.max_dt
    ORDER BY e.currency ASC, e.event_name ASC
";

$params              = $filterParams;
$params[':cutoff']   = $cutoffDatetime;

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed: ' . $e->getMessage()]);
    exit;
}

// ── Build response ────────────────────────────────────────────────────────────
$events = [];
foreach ($rows as $row) {
    $events[] = [
        'consistent_event_id' => $row['consistent_event_id'],
        'event_name'          => $row['event_name'],
        'currency'            => $row['currency'],
        'impact_level'        => $row['impact_level'],
        'event_date'          => $row['event_date'],
        'event_time'          => $row['event_time'],
        'actual_value'        => $row['actual_value'],
        'forecast_value'      => $row['forecast_value'],
        'previous_value'      => $row['previous_value'],
    ];
}

echo json_encode([
    'success'          => true,
    'cutoff_datetime'  => $cutoff->format('Y-m-d H:i:s') . ' UTC',
    'total_event_types'=> count($events),
    'filters'          => [
        'currency' => $currency  ?: null,
        'event_id' => $event_id  ?: null,
        'impact'   => $impact    ?: null,
        'must_have'=> $must_have ?: null,
    ],
    'events'           => $events,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
