<?php
/**
 * Sync Economic Events API
 * Fetches economic events from TradingView API and saves to database
 * 
 * Usage:
 *   GET /api/sync-events?range=past-1-month
 *   GET /api/sync-events?range=past-1-month&currencies=USD,EUR
 */

header('Content-Type: application/json');

// Verify auth
require_once __DIR__ . '/../../app/Auth.php';
Auth::check();

// Allow long-running batch syncs to complete
set_time_limit(0);
ini_set('memory_limit', '256M');

// Load shared helper functions (fetchFromTradingView, saveEventsToDB, etc.)
require_once __DIR__ . '/sync-events-helpers.php';

// Get query params
$range = $_GET['range'] ?? 'past-1-month';
$currencies = $_GET['currencies'] ?? null;

// Map range to date parameters
$dateRanges = [
    'past-5-years' => '-5 years',
    'past-2-years' => '-2 years',
    'past-1-year'  => '-1 year',
    'past-6-months' => '-6 months',
    'past-3-months' => '-3 months',
    'past-1-month'  => '-1 month',
    'past-week'     => '-7 days',
    'today'         => '0 days', // Same day
    'all-future'    => '+5 years'
];

if (!isset($dateRanges[$range])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid range. Allowed: ' . implode(', ', array_keys($dateRanges))]);
    exit;
}

// Calculate date range
$now = new DateTime('now', new DateTimeZone('UTC'));
$modifier = $dateRanges[$range];

if ($range === 'today') {
    $fromDate = clone $now;
    $fromDate->setTime(0, 0, 0);
    $toDate = clone $now;
    $toDate->setTime(23, 59, 59);
} elseif ($range === 'all-future') {
    $fromDate = clone $now;
    $toDate = clone $now;
    $toDate->modify('+5 years');
} else {
    $fromDate = clone $now;
    $fromDate->modify($modifier);
    $toDate = clone $now;
}

// Paginated fetch: request the full range, detect the cap from the last event's date,
// then advance the window and repeat â€” only as many calls as actually needed.
$countriesStr = getCurrenciesToCountries($currencies);

$totalFetched = 0;
$saved        = 0;
$updated      = 0;
$errors       = [];
$batchResults = [];

try {
    $db = new PDO('sqlite:' . __DIR__ . '/../../database/app.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $cursor  = clone $fromDate;
    $hardEnd = clone $toDate;
    $page    = 0;
    $maxPages = 120; // absolute safety cap

    // If TradingView returns >= this count we assume there are more events to fetch.
    // Use 195 to stay safely below any round-number cap (200 is the known limit).
    $PAGE_CAP = 195;

    while ($cursor < $hardEnd && $page < $maxPages) {
        $page++;

        [$pageEvents, $fetchError] = fetchFromTradingView($cursor, $hardEnd, $countriesStr);

        $count = count($pageEvents);

        $batchResults[] = [
            'page'    => $page,
            'from'    => $cursor->format('Y-m-d H:i:s'),
            'to'      => $hardEnd->format('Y-m-d H:i:s'),
            'fetched' => $count,
            'error'   => $fetchError,
        ];

        if ($fetchError) {
            $errors[] = "Page $page ({$cursor->format('Y-m-d')}): $fetchError";
            break; // don't keep hammering on error
        }

        if ($count === 0) {
            break; // nothing left
        }

        $totalFetched += $count;
        [$bSaved, $bUpdated, $bErrors] = saveEventsToDB($db, $pageEvents);
        $saved   += $bSaved;
        $updated += $bUpdated;
        $errors   = array_merge($errors, $bErrors);

        $batchResults[$page - 1]['saved']   = $bSaved;
        $batchResults[$page - 1]['updated'] = $bUpdated;

        // If we received fewer events than the cap, we have everything â€” stop.
        if ($count < $PAGE_CAP) {
            break;
        }

        // Find the latest event date in this page (TradingView returns ascending order,
        // so the last element is the newest â€” but we scan all to be safe).
        $lastTimestamp = null;
        foreach ($pageEvents as $ev) {
            if (!empty($ev['date'])) {
                $ts = strtotime($ev['date']);
                if ($ts !== false && ($lastTimestamp === null || $ts > $lastTimestamp)) {
                    $lastTimestamp = $ts;
                }
            }
        }

        if ($lastTimestamp === null) {
            break; // can't determine where we are â€” stop safely
        }

        $lastEventDt = new DateTime('@' . $lastTimestamp, new DateTimeZone('UTC'));

        // If the last event is at or past our target end, we're done.
        if ($lastEventDt >= $hardEnd) {
            break;
        }

        // Advance cursor to 1 second after the last received event.
        $cursor = clone $lastEventDt;
        $cursor->modify('+1 second');

        // Polite delay between pages (0.5 s) to avoid triggering rate limits.
        usleep(500000);
    }

    echo json_encode([
        'success'       => true,
        'range'         => $range,
        'from'          => $fromDate->format('Y-m-d'),
        'to'            => $toDate->format('Y-m-d'),
        'pages'         => $page,
        'total_fetched' => $totalFetched,
        'saved'         => $saved,
        'updated'       => $updated,
        'errors'        => $errors,
        'page_details'  => $batchResults,
        'message'       => "Fetched $totalFetched events in $page request(s): $saved new, $updated updated",
    ]);

    // --- Persist last known update timestamp ---
    persistLastUpdated($db);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
