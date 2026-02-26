<?php
/**
 * Update Events API
 * Syncs from the last known update date through the next 6 months.
 * The "last known update" is stored in settings.events_last_updated.
 * Falls back to 7 days ago if never synced before.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../app/Auth.php';
Auth::check();

set_time_limit(0);
ini_set('memory_limit', '256M');

// Bring in shared helpers from sync-events.php
require_once __DIR__ . '/sync-events-helpers.php';

try {
    $db = new PDO('sqlite:' . __DIR__ . '/../../database/app.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $now = new DateTime('now', new DateTimeZone('UTC'));

    // --- Determine start: last known update date ---
    $stmt = $db->prepare("SELECT value FROM settings WHERE key = 'events_last_updated'");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && !empty($row['value'])) {
        $fromDate = new DateTime($row['value'], new DateTimeZone('UTC'));
    } else {
        // Never synced — start from 7 days ago
        $fromDate = (clone $now)->modify('-7 days')->setTime(0, 0, 0);
    }

    // End: 6 months into the future (load upcoming events without over-fetching)
    $toDate = (clone $now)->modify('+6 months');

    $countriesStr = getCurrenciesToCountries(null); // All currencies

    $totalFetched = 0;
    $saved        = 0;
    $updated      = 0;
    $errors       = [];
    $pageResults  = [];

    $cursor   = clone $fromDate;
    $hardEnd  = clone $toDate;
    $page     = 0;
    $maxPages = 120;
    $PAGE_CAP = 195;

    while ($cursor < $hardEnd && $page < $maxPages) {
        $page++;

        [$pageEvents, $fetchError] = fetchFromTradingView($cursor, $hardEnd, $countriesStr);
        $count = count($pageEvents);

        $pageResults[] = [
            'page'    => $page,
            'from'    => $cursor->format('Y-m-d H:i:s'),
            'fetched' => $count,
            'error'   => $fetchError,
        ];

        if ($fetchError) {
            $errors[] = "Page $page ({$cursor->format('Y-m-d')}): $fetchError";
            break;
        }

        if ($count === 0) break;

        $totalFetched += $count;
        [$bSaved, $bUpdated, $bErrors] = saveEventsToDB($db, $pageEvents);
        $saved   += $bSaved;
        $updated += $bUpdated;
        $errors   = array_merge($errors, $bErrors);

        $pageResults[$page - 1]['saved']   = $bSaved;
        $pageResults[$page - 1]['updated'] = $bUpdated;

        if ($count < $PAGE_CAP) break;

        // Advance cursor to 1 second after the last event received
        $lastTimestamp = null;
        foreach ($pageEvents as $ev) {
            if (!empty($ev['date'])) {
                $ts = strtotime($ev['date']);
                if ($ts !== false && ($lastTimestamp === null || $ts > $lastTimestamp)) {
                    $lastTimestamp = $ts;
                }
            }
        }

        if ($lastTimestamp === null) break;

        $lastEventDt = new DateTime('@' . $lastTimestamp, new DateTimeZone('UTC'));
        if ($lastEventDt >= $hardEnd) break;

        $cursor = clone $lastEventDt;
        $cursor->modify('+1 second');

        usleep(500000); // 0.5 s polite delay
    }

    // --- Persist last known update timestamp ---
    $nowStr = $now->format('Y-m-d H:i:s');
    $db->prepare(
        "INSERT OR REPLACE INTO settings (key, value, updated_at) VALUES ('events_last_updated', ?, ?)"
    )->execute([$nowStr, $nowStr]);

    echo json_encode([
        'success'        => true,
        'synced_from'    => $fromDate->format('Y-m-d H:i:s'),
        'synced_to'      => $toDate->format('Y-m-d'),
        'pages'          => $page,
        'total_fetched'  => $totalFetched,
        'saved'          => $saved,
        'updated'        => $updated,
        'errors'         => $errors,
        'page_details'   => $pageResults,
        'last_updated_at'=> $nowStr,
        'message'        => "Updated from {$fromDate->format('Y-m-d')} → future: $saved new, $updated updated ($page request(s))",
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
