<?php
/**
 * Network Stats Data API — returns instance heartbeat data as JSON.
 * Only active on arrissadata.com. Requires session auth.
 */
header('Content-Type: application/json');

$host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
if ($host !== 'arrissadata.com' && $host !== 'www.arrissadata.com') {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Database.php';

Auth::check();

$db = Database::getInstance();

$db->query("
    CREATE TABLE IF NOT EXISTS instance_heartbeats (
        id            INTEGER PRIMARY KEY AUTOINCREMENT,
        instance_key  TEXT    NOT NULL UNIQUE,
        instance_url  TEXT    NOT NULL,
        instance_name TEXT,
        php_version   TEXT,
        os_platform   TEXT,
        cpu_load      REAL,
        ram_total     INTEGER,
        ram_used      INTEGER,
        ram_pct       REAL,
        disk_total    INTEGER,
        disk_used     INTEGER,
        disk_pct      REAL,
        uptime_s      INTEGER,
        app_version   TEXT,
        first_seen    DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_seen     DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

$now       = time();
$instances = $db->fetchAll("SELECT * FROM instance_heartbeats ORDER BY last_seen DESC");

foreach ($instances as &$inst) {
    $lastTs          = strtotime($inst['last_seen']);
    $diffSec         = $now - $lastTs;
    $inst['online']  = $diffSec <= 360;
    $inst['diff_sec'] = $diffSec;
    if ($diffSec < 60)        $inst['ago'] = 'Just now';
    elseif ($diffSec < 3600)  $inst['ago'] = round($diffSec / 60) . 'm ago';
    elseif ($diffSec < 86400) $inst['ago'] = round($diffSec / 3600) . 'h ago';
    else                      $inst['ago'] = round($diffSec / 86400) . 'd ago';
}
unset($inst);

$online  = count(array_filter($instances, fn($i) => $i['online']));
$total   = count($instances);

echo json_encode([
    'total'     => $total,
    'online'    => $online,
    'offline'   => $total - $online,
    'instances' => $instances,
    'server_ts' => $now,
]);
