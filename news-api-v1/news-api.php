<?php
/**
 * Economic News API v1.0
 * Provides comprehensive access to economic event data
 */

// Set timeout to 30 seconds
ini_set('max_execution_time', 30);

header('Content-Type: application/json');

// Load database connection
require_once __DIR__ . '/../app/Database.php';

$pdo = Database::getInstance()->getConnection();

// Helper: authenticate API key without consuming quota
function authenticate() {
    global $pdo;
    $api_key = $_GET['api_key'] ?? null;
    
    if (!$api_key) {
        echo json_encode(["vestor_data" => ["error" => "Missing API key."]], JSON_PRETTY_PRINT);
        exit;
    }
    
    // Get API key from settings
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE key = 'api_key'");
    $stmt->execute();
    $result = $stmt->fetch();
    $validApiKey = $result ? $result['value'] : '';
    
    if ($api_key !== $validApiKey) {
        echo json_encode(["vestor_data" => ["error" => "Invalid API key."]], JSON_PRETTY_PRINT);
        exit;
    }
}

// Helper: parse custom period format like "last-3-hours", "last-21-months", etc.
function parseCustomPeriod($period, $now) {
    if (preg_match('/^last-(\d+)-(hours?|days?|weeks?|months?|years?)$/i', $period, $matches)) {
        $number = (int)$matches[1];
        $unit = strtolower(rtrim($matches[2], 's')); // Remove 's' to normalize
        
        $start = clone $now;
        
        switch ($unit) {
            case 'hour':
                $start->modify("-{$number} hours");
                break;
            case 'day':
                $start->modify("-{$number} days");
                break;
            case 'week':
                $start->modify("-{$number} weeks");
                break;
            case 'month':
                $start->modify("-{$number} months");
                break;
            case 'year':
                $start->modify("-{$number} years");
                break;
            default:
                return false;
        }
        
        return [
            'start' => $start,
            'end' => clone $now
        ];
    }
    
    return false;
}

// Helper: parse custom future limit format like "next-3-days", "next-2-weeks", etc.
function parseCustomFutureLimit($futureLimit, $now) {
    if (preg_match('/^next-(\d+)-(hours?|days?|weeks?|months?|years?)$/i', $futureLimit, $matches)) {
        $number = (int)$matches[1];
        $unit = strtolower(rtrim($matches[2], 's')); // Remove 's' to normalize
        
        $start = clone $now;
        $end = clone $now;
        
        switch ($unit) {
            case 'hour':
                $end->modify("+{$number} hours");
                break;
            case 'day':
                $end->modify("+{$number} days")->setTime(23, 59, 59);
                break;
            case 'week':
                $end->modify("+{$number} weeks")->setTime(23, 59, 59);
                break;
            case 'month':
                $end->modify("+{$number} months")->setTime(23, 59, 59);
                break;
            case 'year':
                $end->modify("+{$number} years")->setTime(23, 59, 59);
                break;
            default:
                return false;
        }
        
        return [
            'start' => $start,
            'end' => $end
        ];
    }
    
    return false;
}

// Retrieve GET parameters
$api_key           = $_GET['api_key'] ?? null;
$start_date        = $_GET['start_date'] ?? null;
$start_time        = $_GET['start_time'] ?? null;
$end_date          = $_GET['end_date'] ?? null;
$end_time          = $_GET['end_time'] ?? null;
$period            = $_GET['period'] ?? null;
$currency          = $_GET['currency'] ?? null;
$currency_exclude  = $_GET['currency_exclude'] ?? null;
$event_id          = $_GET['event_id'] ?? null;
$display           = $_GET['display'] ?? null;
$pretend_date      = $_GET['pretend_now_date'] ?? null;
$pretend_time      = $_GET['pretend_now_time'] ?? null;
$future_limit      = $_GET['future_limit'] ?? null;
$spit_out          = $_GET['spit_out'] ?? null;

// If no period is provided, require explicit date parameters (time is optional)
if (empty($period) && (!$start_date || !$end_date)) {
    echo json_encode([
        "vestor_data" => [
            "error" => "Missing required parameters. Use start_date and end_date (with optional start_time and end_time), or provide a period parameter."
        ]
    ], JSON_PRETTY_PRINT);
    exit;
}

// Default times if dates are provided but times are not
if (!empty($start_date) && empty($start_time)) {
    $start_time = '00:00:00';
}
if (!empty($end_date) && empty($end_time)) {
    $end_time = '23:59:59';
}

// Determine the "now" reference in UTC (using pretend_date if provided)
if ($pretend_date) {
    $timePart = $pretend_time ?: '23:59:59';
    if (substr_count($timePart, ':') === 1) {
        $timePart .= ':00';
    }
    $now = DateTime::createFromFormat(
        'Y-m-d H:i:s',
        "{$pretend_date} {$timePart}",
        new DateTimeZone('UTC')
    );
} else {
    $now = new DateTime('now', new DateTimeZone('UTC'));
}

// Determine boundaries using the period parameter if provided; otherwise, use explicit date/time.
if (!empty($period)) {
    $period_lc = strtolower(trim($period));
    
    // First, try to parse custom period format
    $customPeriod = parseCustomPeriod($period_lc, $now);
    if ($customPeriod !== false) {
        $start = $customPeriod['start'];
        $end = $customPeriod['end'];
    } else {
        // Handle predefined periods
        switch ($period_lc) {
            case 'today':
                $start = (clone $now)->setTime(0, 0, 0);
                if (!empty($spit_out) && strtolower($spit_out) === 'all') {
                    $end = (clone $now)->setTime(23, 59, 59);
                } else {
                    $end = clone $now;
                }
                break;
            case 'yesterday':
                $start = (clone $now)->modify('-1 day')->setTime(0, 0, 0);
                $end   = (clone $start)->setTime(23, 59, 59);
                break;
            case 'this-week':
                $today     = (clone $now)->setTime(0, 0, 0);
                $dayOfWeek = (int)$today->format('w');
                $start     = (clone $today)->modify("-{$dayOfWeek} days")->setTime(0, 0, 0);
                if (!empty($spit_out) && strtolower($spit_out) === 'all') {
                    $end = (clone $start)->modify('+6 days')->setTime(23, 59, 59);
                } else {
                    $end = clone $now;
                }
                break;
            case 'last-week':
                $today     = (clone $now)->setTime(0, 0, 0);
                $dayOfWeek = (int)$today->format('w');
                $start     = (clone $today)->modify('-' . ($dayOfWeek + 7) . ' days')->setTime(0, 0, 0);
                $end       = (clone $start)->modify('+6 days')->setTime(23, 59, 59);
                break;
            case 'this-month':
                $start = DateTime::createFromFormat('Y-m-d H:i:s', $now->format('Y-m-01 00:00:00'), new DateTimeZone('UTC'));
                if (!empty($spit_out) && strtolower($spit_out) === 'all') {
                    $end = DateTime::createFromFormat('Y-m-d H:i:s', $now->format('Y-m-t 23:59:59'), new DateTimeZone('UTC'));
                } else {
                    $end = clone $now;
                }
                break;
            case 'last-month':
                $start = (clone $now)->modify('first day of last month')->setTime(0, 0, 0);
                $end   = (clone $now)->modify('last day of last month')->setTime(23, 59, 59);
                break;
            case 'last-3-months':
                $end   = clone $now;
                $start = (clone $now)->modify('-3 months')->setTime(0, 0, 0);
                break;
            case 'last-6-months':
                $end   = clone $now;
                $start = (clone $now)->modify('-6 months')->setTime(0, 0, 0);
                break;
            case 'last-7-days':
                $end   = clone $now;
                $start = (clone $now)->modify('-7 days')->setTime(0, 0, 0);
                break;
            case 'last-14-days':
                $end   = clone $now;
                $start = (clone $now)->modify('-14 days')->setTime(0, 0, 0);
                break;
            case 'last-30-days':
                $end   = clone $now;
                $start = (clone $now)->modify('-30 days')->setTime(0, 0, 0);
                break;
            case 'this-year':
                $year  = $now->format('Y');
                $start = new DateTime("{$year}-01-01 00:00:00", new DateTimeZone('UTC'));
                if (!empty($spit_out) && strtolower($spit_out) === 'all') {
                    $end = new DateTime("{$year}-12-31 23:59:59", new DateTimeZone('UTC'));
                } else {
                    $end = clone $now;
                }
                break;
            case 'last-12-months':
                $end   = clone $now;
                $start = (clone $now)->modify('-12 months')->setTime(0, 0, 0);
                break;
            case 'last-2-years':
                $year  = $now->format('Y');
                $start = new DateTime(($year - 2) . "-01-01 00:00:00", new DateTimeZone('UTC'));
                $end   = new DateTime(($year - 1) . "-12-31 23:59:59", new DateTimeZone('UTC'));
                break;
            case 'future':
                $start = clone $now;
                $end   = new DateTime("9999-12-31 23:59:59", new DateTimeZone('UTC'));
                break;
            default:
                echo json_encode(["vestor_data" => ["error" => "Invalid period parameter: {$period}"]], JSON_PRETTY_PRINT);
                exit;
        }
    }

    // Apply future_limit if given
    if (!empty($future_limit)) {
        $fl = strtolower($future_limit);
        
        // First, try to parse custom future limit format
        $customFutureLimit = parseCustomFutureLimit($fl, $now);
        if ($customFutureLimit !== false) {
            $start = $customFutureLimit['start'];
            $end = $customFutureLimit['end'];
        } else {
            // Handle predefined future limits
            switch ($fl) {
                case 'today':
                    $start = clone $now;
                    $end   = (clone $now)->setTime(23, 59, 59);
                    break;
                case 'tomorrow':
                    $start = (clone $now)->modify('+1 day')->setTime(0, 0, 0);
                    $end   = (clone $start)->setTime(23, 59, 59);
                    break;
                case 'next-2-days':
                    $start = (clone $now)->modify('+1 day')->setTime(0, 0, 0);
                    $end   = (clone $start)->modify('+1 day')->setTime(23, 59, 59);
                    break;
                case 'this-week':
                    $today        = (clone $now)->setTime(0, 0, 0);
                    $dayOfWeek    = (int)$today->format('w');
                    $daysToEndSat = 6 - $dayOfWeek;
                    $start        = clone $now;
                    $end          = (clone $today)->modify("+{$daysToEndSat} days")->setTime(23, 59, 59);
                    break;
                case 'next-week':
                    $today            = (clone $now)->setTime(0, 0, 0);
                    $dayOfWeek        = (int)$today->format('w');
                    $daysToNextSunday = ($dayOfWeek === 0 ? 7 : 7 - $dayOfWeek);
                    $start            = (clone $today)->modify("+{$daysToNextSunday} days")->setTime(0, 0, 0);
                    $end              = (clone $start)->modify('+6 days')->setTime(23, 59, 59);
                    break;
                default:
                    echo json_encode(["vestor_data" => ["error" => "Invalid future_limit parameter: {$future_limit}"]], JSON_PRETTY_PRINT);
                    exit;
            }
        }
    }

    $start_datetime = $start->format("Y-m-d H:i:s");
    $end_datetime   = $end->format("Y-m-d H:i:s");
} else {
    // explicit start/end
    $start_datetime = $start_date . ' ' . $start_time;
    $end_datetime   = $end_date   . ' ' . $end_time;
}

// Build the response_for descriptor
if (!empty($period)) {
    $response_for = ($currency ? strtoupper($currency) . ' ' : '') . strtolower(trim($period)) . ' events';
    if (!empty($spit_out) && strtolower($spit_out) === 'all') {
        $response_for .= ' (all events - past and future)';
    }
    if (!empty($future_limit)) {
        $response_for .= ' (limited to ' . strtolower($future_limit) . ')';
    }
} else {
    $response_for = ($currency ? strtoupper($currency) . ' events from ' : 'events from ') . $start_datetime . ' to ' . $end_datetime;
}

try {
    // Authenticate before running query
    authenticate();

    // Build SQL - SQLite uses || for concatenation
    $sql    = "SELECT * FROM economic_events 
               WHERE (event_date || ' ' || event_time) BETWEEN :start AND :end";
    $params = [':start' => $start_datetime, ':end' => $end_datetime];

    if (!empty($currency)) {
        $sql                .= " AND currency = :currency";
        $params[':currency'] = strtoupper($currency);
    }

    if (!empty($currency_exclude)) {
        $excludes         = array_map('trim', explode(',', $currency_exclude));
        $placeholdersExcl = [];
        foreach ($excludes as $i => $code) {
            $key                = ":ce{$i}";
            $placeholdersExcl[] = $key;
            $params[$key]       = strtoupper($code);
        }
        $sql .= " AND currency NOT IN (" . implode(',', $placeholdersExcl) . ")";
    }

    if (!empty($event_id)) {
        $eventIds     = array_map('trim', explode(',', $event_id));
        $placeholders = [];
        foreach ($eventIds as $i => $id) {
            $key            = ":eid{$i}";
            $placeholders[] = $key;
            $params[$key]   = strtoupper($id);
        }
        $sql .= " AND consistent_event_id IN (" . implode(',', $placeholders) . ")";
    }

    $sql  .= " ORDER BY event_date, event_time";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll();

    // For all future events, ensure actual_value is 'TBD'
    foreach ($events as &$ev) {
        $eventDT = DateTime::createFromFormat('Y-m-d H:i:s', $ev['event_date'].' '.$ev['event_time'], new DateTimeZone('UTC'));
        if ($eventDT > $now) {
            $ev['actual_value'] = 'TBD';
        }
    }
    unset($ev);

    // Build output
    if (!empty($event_id)) {
        $grouped = [];
        foreach ($eventIds as $eid) {
            $matches = array_values(array_filter($events, function($ev) use ($eid) {
                return strtoupper($ev['consistent_event_id']) === strtoupper($eid);
            }));
            if (empty($matches)) continue;

            $cleanName = preg_replace('/[^a-z0-9]+/', '_',
                          trim(strtolower(preg_replace('/\s*\([^)]*\)/','',$matches[0]['event_name'])),'_'));

            $grouped[$cleanName] = ['count' => count($matches), 'events' => []];
            foreach ($matches as $ev) {
                if ($display === 'min') {
                    $item = [
                        "event_name" => $ev["event_name"],
                        "event_date" => $ev["event_date"],
                        "event_time" => $ev["event_time"],
                        "currency"   => $ev["currency"]
                    ];
                    if (!is_null($ev["forecast_value"]) && $ev["forecast_value"] !== '') {
                        $item["forecast_value"] = $ev["forecast_value"];
                    }
                    if (!is_null($ev["actual_value"]) && $ev["actual_value"] !== '') {
                        $item["actual_value"] = $ev["actual_value"];
                    }
                    if (!is_null($ev["previous_value"]) && $ev["previous_value"] !== '') {
                        $item["previous_value"] = $ev["previous_value"];
                    }
                    $grouped[$cleanName]['events'][] = $item;
                } else {
                    $grouped[$cleanName]['events'][] = $ev;
                }
            }
        }
        $vestor_data = $grouped;
    } else {
        $flat = [];
        foreach ($events as $ev) {
            if ($display === 'min') {
                $item = [
                    "event_name" => $ev["event_name"],
                    "event_date" => $ev["event_date"],
                    "event_time" => $ev["event_time"],
                    "currency"   => $ev["currency"]
                ];
                if (!is_null($ev["forecast_value"]) && $ev["forecast_value"] !== '') {
                    $item["forecast_value"] = $ev["forecast_value"];
                }
                if (!is_null($ev["actual_value"]) && $ev["actual_value"] !== '') {
                    $item["actual_value"] = $ev["actual_value"];
                }
                if (!is_null($ev["previous_value"]) && $ev["previous_value"] !== '') {
                    $item["previous_value"] = $ev["previous_value"];
                }
                $flat[] = $item;
            } else {
                $flat[] = $ev;
            }
        }
        $vestor_data = ['count' => count($flat), 'events' => $flat];
    }

    echo json_encode([
        'response_for' => $response_for,
        'vestor_data'  => $vestor_data
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode([
        'response_for' => $response_for ?? null,
        'vestor_data'  => ['error' => $e->getMessage()]
    ], JSON_PRETTY_PRINT);
}
?>
