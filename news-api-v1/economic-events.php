<?php
/**
 * Fetch economic calendar events from TradingView and save as JSON.
 *
 * Usage:
 *   php economic-events.php                          # defaults: last 7 days, all currencies, medium+high
 *   php economic-events.php --from=2026-02-20 --to=2026-02-27
 *   php economic-events.php --currencies=USD,EUR --importance=1
 *
 * Query params (when served via web):
 *   ?from=2026-02-20&to=2026-02-27&currencies=USD,EUR&importance=0
 *
 * importance: -1 = all, 0 = medium+high (default), 1 = high only
 */

define('ECONOMIC_CALENDAR_URL', 'https://economic-calendar.tradingview.com/events');
define('OUTPUT_FILE', __DIR__ . '/economic_events.json');

$CURRENCY_TO_COUNTRIES = [
    'USD' => ['US'],
    'CAD' => ['CA'],
    'JPY' => ['JP'],
    'EUR' => ['DE', 'FR'],
    'CHF' => ['CH'],
    'AUD' => ['AU'],
    'NZD' => ['NZ'],
    'GBP' => ['GB'],
];

$SUPPORTED_CURRENCIES = array_keys($CURRENCY_TO_COUNTRIES);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function currencies_to_countries(array $currencies, array $map): array {
    $countries = [];
    foreach ($currencies as $cur) {
        $cur = strtoupper(trim($cur));
        if (isset($map[$cur])) {
            $countries = array_merge($countries, $map[$cur]);
        }
    }
    return array_values(array_unique($countries));
}

function parse_args(): array {
    // CLI
    if (php_sapi_name() === 'cli') {
        $opts = getopt('', ['from:', 'to:', 'currencies:', 'importance:']);
        return [
            'from'       => $opts['from']       ?? null,
            'to'         => $opts['to']         ?? null,
            'currencies' => $opts['currencies'] ?? null,
            'importance' => $opts['importance']  ?? null,
        ];
    }
    // Web
    return [
        'from'       => $_GET['from']       ?? null,
        'to'         => $_GET['to']         ?? null,
        'currencies' => $_GET['currencies'] ?? null,
        'importance' => $_GET['importance']  ?? null,
    ];
}

// ---------------------------------------------------------------------------
// Main
// ---------------------------------------------------------------------------

$args = parse_args();

// Dates — default to last 7 days
$toDate   = $args['to']   ? new DateTime($args['to'], new DateTimeZone('UTC'))   : new DateTime('now', new DateTimeZone('UTC'));
$fromDate = $args['from'] ? new DateTime($args['from'], new DateTimeZone('UTC')) : (clone $toDate)->modify('-7 days');

// Currencies
if ($args['currencies']) {
    $currencies = array_map('trim', explode(',', $args['currencies']));
} else {
    $currencies = $SUPPORTED_CURRENCIES;
}
$countries = currencies_to_countries($currencies, $CURRENCY_TO_COUNTRIES);

// Importance
$importance = $args['importance'] !== null ? (int) $args['importance'] : 0;

// Build URL
$query = http_build_query([
    'from'          => $fromDate->format('c'),
    'to'            => $toDate->format('c'),
    'countries'     => implode(',', $countries),
    'minImportance' => $importance,
]);

$url = ECONOMIC_CALENDAR_URL . '?' . $query;

// Fetch
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_HTTPHEADER     => [
        'Origin: https://in.tradingview.com',
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error    = curl_error($ch);
curl_close($ch);

if ($httpCode !== 200 || $response === false) {
    $msg = "Error fetching events (HTTP $httpCode): $error";
    if (php_sapi_name() === 'cli') {
        fwrite(STDERR, $msg . PHP_EOL);
        exit(1);
    }
    http_response_code(500);
    echo json_encode(['error' => $msg]);
    exit;
}

$data   = json_decode($response, true);
$events = $data['result'] ?? [];

// Save to JSON file
$json = json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
file_put_contents(OUTPUT_FILE, $json);

$count = count($events);

if (php_sapi_name() === 'cli') {
    echo "Fetched $count events → saved to " . OUTPUT_FILE . PHP_EOL;
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'count'      => $count,
        'saved_to'   => OUTPUT_FILE,
        'events'     => $events,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
