<?php
/**
 * Cron Job: Smart Economic Events Updater
 *
 * Run every minute:  * * * * * php /path/to/database/cron-sync-events.php
 *
 * Triggers an update when EITHER condition is true:
 *   1. One or more events existed in the DB that occurred within the last 60 minutes,
 *      the most recent one happened 3+ minutes ago, AND we haven't synced since that event
 *      (captures actual data after it's published on TradingView)
 *   2. Last sync was more than 12 hours ago (keeps everything fresh regardless)
 *
 * Sync window: from last known update timestamp to next 6 months
 * Falls back to -7 days start if never synced before.
 */

define('CRON_MODE', true);

$dbPath = __DIR__ . '/../database/app.db';
if (!file_exists($dbPath)) {
    $dbPath = __DIR__ . '/app.db';
}

// Shared helpers (fetchFromTradingView, saveEventsToDB, persistLastUpdated, etc.)
require_once __DIR__ . '/../public/api/sync-events-helpers.php';

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $now   = new DateTime('now', new DateTimeZone('UTC'));
    $nowTs = $now->getTimestamp();

    // Read last sync time
    $stmt = $db->prepare("SELECT value FROM settings WHERE key = 'events_last_updated'");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $lastUpdatedTs = ($row && !empty($row['value'])) ? strtotime($row['value']) : 0;

    // Decision: should we run an update?
    $shouldUpdate = false;
    $reason       = '';

    // Condition A: last sync was more than 12 hours ago
    if (($nowTs - $lastUpdatedTs) > 12 * 3600) {
        $shouldUpdate = true;
        $reason = 'last sync was more than 12 hours ago';
    }

    // Condition B: an event occurred in the last 60 minutes, is 3+ min in the past,
    //              and we have not synced since that event fired
    if (!$shouldUpdate) {
        $stmt = $db->prepare("
            SELECT MAX(event_date || ' ' || event_time) AS last_event_dt
            FROM economic_events
            WHERE (event_date || ' ' || event_time) >= datetime('now', '-60 minutes')
              AND (event_date || ' ' || event_time) <= datetime('now')
        ");
        $stmt->execute();
        $row2 = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row2 && !empty($row2['last_event_dt'])) {
            $lastEventTs       = strtotime($row2['last_event_dt'] . ' UTC');
            $minutesSinceEvent = ($nowTs - $lastEventTs) / 60;

            if ($minutesSinceEvent >= 3 && $lastUpdatedTs < $lastEventTs) {
                $shouldUpdate = true;
                $reason = 'event(s) occurred ~' . round($minutesSinceEvent) . ' min ago — actual data should now be available';
            }
        }
    }

    if (!$shouldUpdate) {
        logCron('No update needed — skipping');
        exit(0);
    }

    logCron("Update triggered: $reason");

    // Sync window: from last known update (or -7 days) to next 6 months
    $fromDate = ($lastUpdatedTs > 0)
        ? new DateTime('@' . $lastUpdatedTs, new DateTimeZone('UTC'))
        : (clone $now)->modify('-7 days')->setTime(0, 0, 0);

    $toDate = (clone $now)->modify('+6 months');

    $result = fetchAndSaveEvents($db, $fromDate, $toDate);

    logCron("Done: {$result['saved']} new, {$result['updated']} updated");

} catch (Exception $e) {
    logCron('Error: ' . $e->getMessage());
    error_log('Cron sync error: ' . $e->getMessage());
    exit(1);
}

exit(0);

// ============================================================

function fetchAndSaveEvents(PDO $db, DateTime $fromDate, DateTime $toDate): array {
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

        // Advance cursor past the last received event
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

function logCron(string $message): void {
    $logFile = __DIR__ . '/cron-sync-events.log';
    file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, FILE_APPEND);
}
