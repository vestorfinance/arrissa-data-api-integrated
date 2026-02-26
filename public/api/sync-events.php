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
// then advance the window and repeat — only as many calls as actually needed.
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

        // If we received fewer events than the cap, we have everything — stop.
        if ($count < $PAGE_CAP) {
            break;
        }

        // Find the latest event date in this page (TradingView returns ascending order,
        // so the last element is the newest — but we scan all to be safe).
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
            break; // can't determine where we are — stop safely
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

// ===== HELPER FUNCTIONS =====
require_once __DIR__ . '/sync-events-helpers.php';

    $apiUrl = 'https://economic-calendar.tradingview.com/events?' . http_build_query([
        'from'          => $from->format('c'),
        'to'            => $to->format('c'),
        'countries'     => $countriesStr,
        'minImportance' => 0,
    ]);

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER     => ['Origin: https://in.tradingview.com'],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) {
        return [[], "HTTP $httpCode: $curlErr"];
    }

    $data = json_decode($response, true);
    return [$data['result'] ?? [], null];
}

/**
 * Persist a batch of TradingView events into economic_events.
 * Returns [ saved_count, updated_count, errors_array ].
 */
function saveEventsToDB(PDO $db, array $events): array {
    $saved   = 0;
    $updated = 0;
    $errors  = [];

    $selectStmt = $db->prepare(
        'SELECT event_id FROM economic_events
         WHERE event_name = ? AND currency = ? AND event_date = ? AND event_time = ?'
    );
    $updateStmt = $db->prepare(
        'UPDATE economic_events
         SET forecast_value = ?, actual_value = ?, previous_value = ?, impact_level = ?
         WHERE event_name = ? AND currency = ? AND event_date = ? AND event_time = ?'
    );
    $insertStmt = $db->prepare(
        'INSERT INTO economic_events
         (event_id, event_name, event_date, event_time, currency,
          forecast_value, actual_value, previous_value, impact_level, consistent_event_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    foreach ($events as $event) {
        try {
            $eventName     = $event['title'] ?? '';
            $currency      = $event['currency'] ?? '';
            $parsed        = parseTradingViewDateTime($event['date'] ?? '');
            $eventDate     = $parsed['date'];
            $eventTime     = $parsed['time'];
            $forecastValue = parseValue($event['forecastRaw'] ?? $event['forecast'] ?? null);
            $actualValue   = parseValue($event['actualRaw']   ?? $event['actual']   ?? null);
            $previousValue = parseValue($event['previousRaw'] ?? $event['previous'] ?? null);
            $impactLevel   = mapImpactLevel($event['importance'] ?? 0);
            $eventId       = generateEventId($eventName, $currency, $eventDate, $eventTime);
            $consistentId  = generateConsistentId($eventName);

            $selectStmt->execute([$eventName, $currency, $eventDate, $eventTime]);
            $existing = $selectStmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $updateStmt->execute([
                    $forecastValue, $actualValue, $previousValue, $impactLevel,
                    $eventName, $currency, $eventDate, $eventTime,
                ]);
                $updated++;
            } else {
                $insertStmt->execute([
                    $eventId, $eventName, $eventDate, $eventTime, $currency,
                    $forecastValue, $actualValue, $previousValue, $impactLevel, $consistentId,
                ]);
                $saved++;
            }
        } catch (Exception $e) {
            $errors[] = "Event '{$event['title']}': " . $e->getMessage();
        }
    }

    return [$saved, $updated, $errors];
}

function getCurrenciesToCountries($currenciesStr) {
    if (!$currenciesStr) {
        return 'US,CA,JP,DE,FR,CH,AU,NZ,GB';
    }
    
    $currencyMap = [
        'USD' => 'US',
        'CAD' => 'CA',
        'JPY' => 'JP',
        'EUR' => 'DE,FR',
        'CHF' => 'CH',
        'AUD' => 'AU',
        'NZD' => 'NZ',
        'GBP' => 'GB',
    ];
    
    $currencies = array_map('trim', explode(',', $currenciesStr));
    $countries = [];
    
    foreach ($currencies as $cur) {
        $cur = strtoupper($cur);
        if (isset($currencyMap[$cur])) {
            $countryStr = $currencyMap[$cur];
            $countries = array_merge($countries, explode(',', $countryStr));
        }
    }
    
    return implode(',', array_unique($countries));
}

/**
 * Parse TradingView ISO datetime (e.g. "2026-02-20T07:00:00.000Z") into
 * separate date (YYYY-MM-DD) and time (HH:MM:SS) strings.
 */
function parseTradingViewDateTime($isoDateStr) {
    if (!$isoDateStr || trim($isoDateStr) === '') {
        return ['date' => date('Y-m-d'), 'time' => '00:00:00'];
    }
    try {
        $dt = new DateTime($isoDateStr, new DateTimeZone('UTC'));
        return [
            'date' => $dt->format('Y-m-d'),
            'time' => $dt->format('H:i:s'),
        ];
    } catch (Exception $e) {
        return ['date' => date('Y-m-d'), 'time' => '00:00:00'];
    }
}

/**
 * Generate unique event_id matching Node.js:
 *   SHA1 of "eventName-currency-date-time"
 */
function generateEventId($name, $currency, $date, $time) {
    $baseId = "$name-$currency-$date-$time";
    return hash('sha1', $baseId);
}

/**
 * Generate consistent_event_id matching Node.js:
 *   - Strip parenthetical suffixes except (MoM), (YoY), (QoQ)
 *   - MD5 hash the cleaned name
 *   - Take first 5 bytes, convert each to uppercase letter: chr(65 + byte % 26)
 *   Result: 5-letter uppercase string, same for every occurrence of the same event
 */
function generateConsistentId($name) {
    // Remove parenthetical content EXCEPT MoM, YoY, QoQ — same regex as Node.js
    $cleanName = preg_replace('/\s*\((?!(?:MoM|YoY|QoQ)\))[^)]*\)/', '', $name);
    $cleanName = trim($cleanName);

    // MD5 → raw 16-byte binary
    $hashRaw = md5($cleanName, true);

    $consistentId = '';
    for ($i = 0; $i < 5; $i++) {
        $byte = ord($hashRaw[$i]);
        $consistentId .= chr(65 + ($byte % 26));
    }
    return $consistentId;
}

/**
 * Convert a value to a float.
 * TradingView Raw fields are already numeric — pass them through directly.
 * For string values (legacy / other sources) handle K/M/B abbreviations.
 */
function parseValue($value) {
    if ($value === null || $value === '') return null;

    // Already numeric (TradingView Raw fields)
    if (is_int($value) || is_float($value)) {
        return $value;
    }

    $str = trim((string)$value);
    if ($str === '' || $str === '-' || strtolower($str) === 'n/a') return null;

    // Remove spaces, commas, percent signs
    $sanitized = preg_replace('/[\s,%]+/', '', $str);

    // Handle K / M / B multipliers
    if (preg_match('/^([+-]?[\d.]+)[Kk]$/', $sanitized, $m)) return (float)$m[1] * 1e3;
    if (preg_match('/^([+-]?[\d.]+)[Mm]$/', $sanitized, $m)) return (float)$m[1] * 1e6;
    if (preg_match('/^([+-]?[\d.]+)[Bb]$/', $sanitized, $m)) return (float)$m[1] * 1e9;

    $num = (float)$sanitized;
    return is_nan($num) ? null : $num;
}

/**
 * Map TradingView importance integer to label.
 * TradingView: -1 = low, 0 = moderate/medium, 1 = high
 * Also handles legacy string values for safety.
 */
function mapImpactLevel($importance) {
    // Handle integer (TradingView API)
    if (is_numeric($importance)) {
        $val = (int)$importance;
        if ($val >= 1)  return 'High';
        if ($val === 0) return 'Moderate';
        return 'Low';
    }
    // Handle legacy string values
    $s = strtolower(trim((string)$importance));
    if (strpos($s, 'high') !== false)                              return 'High';
    if (strpos($s, 'medium') !== false || strpos($s, 'moderate') !== false) return 'Moderate';
    if (strpos($s, 'low') !== false)                               return 'Low';
    return 'Moderate';
}
