<?php
/**
 * Run Cron API  —  trigger the smart event-sync cron via HTTP
 *
 * Protected by api_key (same key stored in settings).
 * Useful for n8n, Make, Zapier, or any HTTP-based scheduler.
 *
 * Usage:
 *   GET  /api/run-cron?api_key={key}           — respects smart timing (may skip)
 *   GET  /api/run-cron?api_key={key}&force=true — always runs regardless of timing
 *
 * Response (JSON):
 *   { "status": "ran"|"skipped", "reason": "...", "saved": N, "updated": N, "duration_ms": N }
 */

error_reporting(0);
ini_set('display_errors', 0);
set_time_limit(0);
ini_set('memory_limit', '256M');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$t0 = microtime(true);

// ── API key auth ─────────────────────────────────────────────────────────────
try {
    require_once __DIR__ . '/../../app/Database.php';
    $pdo = Database::getInstance()->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$settingsStmt = $pdo->query("SELECT key, value FROM settings WHERE key IN ('api_key','app_base_url')");
$settings = [];
foreach ($settingsStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $settings[$row['key']] = $row['value'];
}

$storedKey = $settings['api_key'] ?? null;
$givenKey  = $_GET['api_key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? null;

if (!$givenKey) {
    http_response_code(401);
    echo json_encode(['error' => 'Missing api_key. Pass as ?api_key=... or X-Api-Key header.']);
    exit;
}

if (!$storedKey || $storedKey !== $givenKey) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid api_key']);
    exit;
}

// ── Load shared helpers ───────────────────────────────────────────────────────
require_once __DIR__ . '/sync-events-helpers.php';

// ── Decision: should we run? ─────────────────────────────────────────────────
$force = !empty($_GET['force']) && in_array(strtolower($_GET['force']), ['1', 'true', 'yes']);

$nowTs = time();
$now   = new DateTime('now', new DateTimeZone('UTC'));

$stmt = $pdo->prepare("SELECT value FROM settings WHERE key = 'events_last_updated'");
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$lastUpdatedTs = ($row && !empty($row['value'])) ? strtotime($row['value']) : 0;

$shouldRun = false;
$reason    = '';

if ($force) {
    $shouldRun = true;
    $reason    = 'forced via ?force=true';
} elseif (($nowTs - $lastUpdatedTs) > 12 * 3600) {
    $shouldRun = true;
    $reason    = 'last sync was more than 12 hours ago';
} else {
    // Check if an event fired in the last 60 min that we haven't captured yet
    $s = $pdo->prepare("
        SELECT MAX(event_date || ' ' || event_time) AS last_event_dt
        FROM economic_events
        WHERE (event_date || ' ' || event_time) >= datetime('now', '-60 minutes')
          AND (event_date || ' ' || event_time) <= datetime('now')
    ");
    $s->execute();
    $r = $s->fetch(PDO::FETCH_ASSOC);

    if ($r && !empty($r['last_event_dt'])) {
        $lastEventTs       = strtotime($r['last_event_dt'] . ' UTC');
        $minutesSinceEvent = ($nowTs - $lastEventTs) / 60;

        if ($minutesSinceEvent >= 3 && $lastUpdatedTs < $lastEventTs) {
            $shouldRun = true;
            $reason = 'event(s) occurred ~' . round($minutesSinceEvent) . ' min ago — actuals should be available';
        }
    }
}

if (!$shouldRun) {
    echo json_encode([
        'status'      => 'skipped',
        'reason'      => 'no update needed — conditions not met. Use ?force=true to override.',
        'last_sync'   => $lastUpdatedTs > 0 ? date('Y-m-d H:i:s', $lastUpdatedTs) . ' UTC' : 'never',
        'duration_ms' => round((microtime(true) - $t0) * 1000),
    ]);
    exit;
}

// ── Run the sync ──────────────────────────────────────────────────────────────
$fromDate = ($lastUpdatedTs > 0)
    ? new DateTime('@' . $lastUpdatedTs, new DateTimeZone('UTC'))
    : (clone $now)->modify('-7 days')->setTime(0, 0, 0);

$toDate = (clone $now)->modify('+6 months');

try {
    $result = fetchAndSaveEvents($pdo, $fromDate, $toDate);

    echo json_encode([
        'status'      => 'ran',
        'reason'      => $reason,
        'saved'       => $result['saved'],
        'updated'     => $result['updated'],
        'sync_from'   => $fromDate->format('Y-m-d H:i:s') . ' UTC',
        'sync_to'     => $toDate->format('Y-m-d H:i:s') . ' UTC',
        'duration_ms' => round((microtime(true) - $t0) * 1000),
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage(),
        'duration_ms' => round((microtime(true) - $t0) * 1000),
    ]);
}
exit;

// ── fetchAndSaveEvents (mirrors cron-sync-events.php) ────────────────────────
function fetchAndSaveEvents(PDO $db, DateTime $fromDate, DateTime $toDate): array
{
    $countriesStr = getCurrenciesToCountries(null);

    $cursor   = clone $fromDate;
    $hardEnd  = clone $toDate;
    $page     = 0;
    $PAGE_CAP = 195;
    $saved    = 0;
    $updated  = 0;

    while ($cursor < $hardEnd && $page < 120) {
        $page++;
        [$pageEvents, $fetchError] = fetchFromTradingView($cursor, $hardEnd, $countriesStr);

        if ($fetchError || count($pageEvents) === 0) break;

        [$bSaved, $bUpdated] = saveEventsToDB($db, $pageEvents);
        $saved   += $bSaved;
        $updated += $bUpdated;

        if (count($pageEvents) < $PAGE_CAP) break;

        $lastTs = null;
        foreach ($pageEvents as $ev) {
            if (!empty($ev['date'])) {
                $ts = strtotime($ev['date']);
                if ($ts !== false && ($lastTs === null || $ts > $lastTs)) $lastTs = $ts;
            }
        }
        if ($lastTs === null) break;
        $last = new DateTime('@' . $lastTs, new DateTimeZone('UTC'));
        if ($last >= $hardEnd) break;
        $cursor = clone $last;
        $cursor->modify('+1 second');
        usleep(500000);
    }

    persistLastUpdated($db);

    return ['saved' => $saved, 'updated' => $updated];
}
