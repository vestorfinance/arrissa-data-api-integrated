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
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        exit;
    }
    
    // Get API key from settings
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE key = 'api_key'");
    $stmt->execute();
    $result = $stmt->fetch();
    $validApiKey = $result ? $result['value'] : '';
    
    if ($api_key !== $validApiKey) {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
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

// Helper: skip weekends for trading days
function skipWeekends($date, $direction = 'forward') {
    $dayOfWeek = (int)$date->format('w');
    
    if ($direction === 'forward') {
        // Skip Saturday (6) and Sunday (0)
        if ($dayOfWeek === 6) {
            $date->modify('+2 days');
        } elseif ($dayOfWeek === 0) {
            $date->modify('+1 day');
        }
    } else {
        // Skip Sunday (0) and Saturday (6)
        if ($dayOfWeek === 0) {
            $date->modify('-2 days');
        } elseif ($dayOfWeek === 6) {
            $date->modify('-1 day');
        }
    }
    
    return $date;
}

// Helper: parse custom future limit format like "next-3-days", "next-2-weeks", etc.
function parseCustomFutureLimit($futureLimit, $now) {
    // Format: next-{number}-{unit}  e.g. next-7-days, next-2-weeks, next-3-months
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

// Helper: convert UTC datetime to specified timezone
function convertToTimezone($utcDate, $utcTime, $targetTimezone, $debug = false) {
    $debugInfo = [];
    
    try {
        // Map shorthand timezone names
        $timezoneMap = [
            'NY' => 'America/New_York',
            'LA' => 'America/Los_Angeles',
            'LON' => 'Europe/London',
            'TYO' => 'Asia/Tokyo',
            'SYD' => 'Australia/Sydney'
        ];
        
        // Get the full timezone name
        $tzName = $timezoneMap[strtoupper($targetTimezone)] ?? $targetTimezone;
        
        if ($debug) {
            $debugInfo['input_utc_date'] = $utcDate;
            $debugInfo['input_utc_time'] = $utcTime;
            $debugInfo['target_timezone_shorthand'] = $targetTimezone;
            $debugInfo['target_timezone_full'] = $tzName;
        }
        
        // Create DateTime from UTC
        $utcDateTime = new DateTime($utcDate . ' ' . $utcTime, new DateTimeZone('UTC'));
        
        if ($debug) {
            $debugInfo['utc_datetime'] = $utcDateTime->format('Y-m-d H:i:s T (P)');
        }
        
        // Convert to target timezone
        $targetTz = new DateTimeZone($tzName);
        $utcDateTime->setTimezone($targetTz);
        
        if ($debug) {
            $debugInfo['converted_datetime'] = $utcDateTime->format('Y-m-d H:i:s T (P)');
        }
        
        $result = [
            'date' => $utcDateTime->format('Y-m-d'),
            'time' => $utcDateTime->format('H:i:s')
        ];
        
        if ($debug) {
            $debugInfo['output_date'] = $result['date'];
            $debugInfo['output_time'] = $result['time'];
            $result['debug'] = $debugInfo;
        }
        
        return $result;
    } catch (Exception $e) {
        // Return original values if conversion fails
        $result = [
            'date' => $utcDate,
            'time' => $utcTime
        ];
        
        if ($debug) {
            $result['debug'] = [
                'error' => $e->getMessage(),
                'fallback_used' => true
            ];
        }
        
        return $result;
    }
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
$pretend_date      = $_GET['pretend_date'] ?? null;
$pretend_time      = $_GET['pretend_time'] ?? null;
$future_limit      = $_GET['future_limit'] ?? null;
$spit_out          = $_GET['spit_out'] ?? null;
$time_zone         = $_GET['time_zone'] ?? null;
$must_have         = $_GET['must_have'] ?? null;
$debug             = $_GET['debug'] ?? null;
$avoid_duplicates  = $_GET['avoid_duplicates'] ?? null;
$ignore_weekends   = $_GET['ignore_weekends'] ?? null;
$impact            = $_GET['impact'] ?? null;
$tbd               = ($_GET['tbd'] ?? '') === 'true';

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
    
    // If timezone is specified, interpret pretend time in that timezone first
    if (!empty($time_zone)) {
        // Map shorthand timezone names
        $timezoneMap = [
            'NY' => 'America/New_York',
            'LA' => 'America/Los_Angeles',
            'LON' => 'Europe/London',
            'TYO' => 'Asia/Tokyo',
            'SYD' => 'Australia/Sydney'
        ];
        $tzName = $timezoneMap[strtoupper($time_zone)] ?? $time_zone;
        
        try {
            // Create DateTime in the specified timezone
            $now = DateTime::createFromFormat(
                'Y-m-d H:i:s',
                "{$pretend_date} {$timePart}",
                new DateTimeZone($tzName)
            );
            // Convert to UTC for internal calculations
            $now->setTimezone(new DateTimeZone('UTC'));
        } catch (Exception $e) {
            // Fallback to UTC if timezone is invalid
            $now = DateTime::createFromFormat(
                'Y-m-d H:i:s',
                "{$pretend_date} {$timePart}",
                new DateTimeZone('UTC')
            );
        }
    } else {
        // No timezone specified, treat pretend time as UTC
        $now = DateTime::createFromFormat(
            'Y-m-d H:i:s',
            "{$pretend_date} {$timePart}",
            new DateTimeZone('UTC')
        );
    }
} else {
    $now = new DateTime('now', new DateTimeZone('UTC'));
}

// Determine the working timezone for period calculations
$workingTimezone = 'UTC';
if (!empty($time_zone)) {
    $timezoneMap = [
        'NY' => 'America/New_York',
        'LA' => 'America/Los_Angeles',
        'LON' => 'Europe/London',
        'TYO' => 'Asia/Tokyo',
        'SYD' => 'Australia/Sydney'
    ];
    $workingTimezone = $timezoneMap[strtoupper($time_zone)] ?? $time_zone;
}

// Determine boundaries using the period parameter if provided; otherwise, use explicit date/time.
if (!empty($period)) {
    $period_lc = strtolower(trim($period));
    
    // Convert $now to working timezone for period calculations
    $nowInUserTz = clone $now;
    if ($workingTimezone !== 'UTC') {
        try {
            $nowInUserTz->setTimezone(new DateTimeZone($workingTimezone));
        } catch (Exception $e) {
            // Keep UTC if timezone is invalid
        }
    }
    
    // First, try to parse custom period format
    $customPeriod = parseCustomPeriod($period_lc, $nowInUserTz);
    if ($customPeriod !== false) {
        $start = $customPeriod['start'];
        $end = $customPeriod['end'];
        // Convert back to UTC for database query
        if ($workingTimezone !== 'UTC') {
            $start->setTimezone(new DateTimeZone('UTC'));
            $end->setTimezone(new DateTimeZone('UTC'));
        }
    } else {
        // Handle predefined periods (calculate in user's timezone)
        switch ($period_lc) {
            case 'today':
                $start = (clone $nowInUserTz)->setTime(0, 0, 0);
                if (!empty($spit_out) && strtolower($spit_out) === 'all') {
                    $end = (clone $nowInUserTz)->setTime(23, 59, 59);
                } else {
                    $end = clone $nowInUserTz;
                }
                // Convert to UTC for database query
                if ($workingTimezone !== 'UTC') {
                    $start->setTimezone(new DateTimeZone('UTC'));
                    $end->setTimezone(new DateTimeZone('UTC'));
                }
                break;
            case 'yesterday':
                $start = (clone $nowInUserTz)->modify('-1 day')->setTime(0, 0, 0);
                if (!empty($ignore_weekends) && strtolower($ignore_weekends) === 'true') {
                    $start = skipWeekends($start, 'backward');
                }
                $end   = (clone $start)->setTime(23, 59, 59);
                if ($workingTimezone !== 'UTC') {
                    $start->setTimezone(new DateTimeZone('UTC'));
                    $end->setTimezone(new DateTimeZone('UTC'));
                }
                break;
            case 'this-week':
                $today     = (clone $nowInUserTz)->setTime(0, 0, 0);
                $dayOfWeek = (int)$today->format('w');
                $start     = (clone $today)->modify("-{$dayOfWeek} days")->setTime(0, 0, 0);
                if (!empty($spit_out) && strtolower($spit_out) === 'all') {
                    $end = (clone $start)->modify('+6 days')->setTime(23, 59, 59);
                } else {
                    $end = clone $nowInUserTz;
                }
                if ($workingTimezone !== 'UTC') {
                    $start->setTimezone(new DateTimeZone('UTC'));
                    $end->setTimezone(new DateTimeZone('UTC'));
                }
                break;
            case 'last-week':
                $today     = (clone $nowInUserTz)->setTime(0, 0, 0);
                $dayOfWeek = (int)$today->format('w');
                $start     = (clone $today)->modify('-' . ($dayOfWeek + 7) . ' days')->setTime(0, 0, 0);
                $end       = (clone $start)->modify('+6 days')->setTime(23, 59, 59);
                if ($workingTimezone !== 'UTC') {
                    $start->setTimezone(new DateTimeZone('UTC'));
                    $end->setTimezone(new DateTimeZone('UTC'));
                }
                break;
            case 'this-month':
                $start = DateTime::createFromFormat('Y-m-d H:i:s', $nowInUserTz->format('Y-m-01 00:00:00'), new DateTimeZone($workingTimezone));
                if (!empty($spit_out) && strtolower($spit_out) === 'all') {
                    $end = DateTime::createFromFormat('Y-m-d H:i:s', $nowInUserTz->format('Y-m-t 23:59:59'), new DateTimeZone($workingTimezone));
                } else {
                    $end = clone $nowInUserTz;
                }
                if ($workingTimezone !== 'UTC') {
                    $start->setTimezone(new DateTimeZone('UTC'));
                    $end->setTimezone(new DateTimeZone('UTC'));
                }
                break;
            case 'last-month':
                $start = (clone $nowInUserTz)->modify('first day of last month')->setTime(0, 0, 0);
                $end   = (clone $nowInUserTz)->modify('last day of last month')->setTime(23, 59, 59);
                if ($workingTimezone !== 'UTC') {
                    $start->setTimezone(new DateTimeZone('UTC'));
                    $end->setTimezone(new DateTimeZone('UTC'));
                }
                break;
            case 'last-3-months':
                $end   = clone $nowInUserTz;
                $start = (clone $nowInUserTz)->modify('-3 months')->setTime(0, 0, 0);
                if ($workingTimezone !== 'UTC') {
                    $start->setTimezone(new DateTimeZone('UTC'));
                    $end->setTimezone(new DateTimeZone('UTC'));
                }
                break;
            case 'last-6-months':
                $end   = clone $nowInUserTz;
                $start = (clone $nowInUserTz)->modify('-6 months')->setTime(0, 0, 0);
                if ($workingTimezone !== 'UTC') {
                    $start->setTimezone(new DateTimeZone('UTC'));
                    $end->setTimezone(new DateTimeZone('UTC'));
                }
                break;
            case 'last-7-days':
                $end   = clone $nowInUserTz;
                $start = (clone $nowInUserTz)->modify('-7 days')->setTime(0, 0, 0);
                if ($workingTimezone !== 'UTC') {
                    $start->setTimezone(new DateTimeZone('UTC'));
                    $end->setTimezone(new DateTimeZone('UTC'));
                }
                break;
            case 'last-14-days':
                $end   = clone $nowInUserTz;
                $start = (clone $nowInUserTz)->modify('-14 days')->setTime(0, 0, 0);
                if ($workingTimezone !== 'UTC') {
                    $start->setTimezone(new DateTimeZone('UTC'));
                    $end->setTimezone(new DateTimeZone('UTC'));
                }
                break;
            case 'last-30-days':
                $end   = clone $nowInUserTz;
                $start = (clone $nowInUserTz)->modify('-30 days')->setTime(0, 0, 0);
                if ($workingTimezone !== 'UTC') {
                    $start->setTimezone(new DateTimeZone('UTC'));
                    $end->setTimezone(new DateTimeZone('UTC'));
                }
                break;
            case 'this-year':
                $year  = $nowInUserTz->format('Y');
                $start = new DateTime("{$year}-01-01 00:00:00", new DateTimeZone($workingTimezone));
                if (!empty($spit_out) && strtolower($spit_out) === 'all') {
                    $end = new DateTime("{$year}-12-31 23:59:59", new DateTimeZone($workingTimezone));
                } else {
                    $end = clone $nowInUserTz;
                }
                if ($workingTimezone !== 'UTC') {
                    $start->setTimezone(new DateTimeZone('UTC'));
                    $end->setTimezone(new DateTimeZone('UTC'));
                }
                break;
            case 'last-12-months':
                $end   = clone $nowInUserTz;
                $start = (clone $nowInUserTz)->modify('-12 months')->setTime(0, 0, 0);
                if ($workingTimezone !== 'UTC') {
                    $start->setTimezone(new DateTimeZone('UTC'));
                    $end->setTimezone(new DateTimeZone('UTC'));
                }
                break;
            case 'last-2-years':
                $year  = $nowInUserTz->format('Y');
                $start = new DateTime(($year - 2) . "-01-01 00:00:00", new DateTimeZone($workingTimezone));
                $end   = new DateTime(($year - 1) . "-12-31 23:59:59", new DateTimeZone($workingTimezone));
                if ($workingTimezone !== 'UTC') {
                    $start->setTimezone(new DateTimeZone('UTC'));
                    $end->setTimezone(new DateTimeZone('UTC'));
                }
                break;
            case 'future':
                $start = clone $nowInUserTz;
                $end   = new DateTime("9999-12-31 23:59:59", new DateTimeZone($workingTimezone));
                if ($workingTimezone !== 'UTC') {
                    $start->setTimezone(new DateTimeZone('UTC'));
                    $end->setTimezone(new DateTimeZone('UTC'));
                }
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
        $customFutureLimit = parseCustomFutureLimit($fl, $nowInUserTz);
        if ($customFutureLimit !== false) {
            $start = $customFutureLimit['start'];
            $end = $customFutureLimit['end'];
            // Convert to UTC for database query
            if ($workingTimezone !== 'UTC') {
                $start->setTimezone(new DateTimeZone('UTC'));
                $end->setTimezone(new DateTimeZone('UTC'));
            }
        } else {
            // Handle predefined future limits (calculate in user's timezone)
            switch ($fl) {
                case 'today':
                    $start = clone $nowInUserTz;
                    $end   = (clone $nowInUserTz)->setTime(23, 59, 59);
                    if ($workingTimezone !== 'UTC') {
                        $start->setTimezone(new DateTimeZone('UTC'));
                        $end->setTimezone(new DateTimeZone('UTC'));
                    }
                    break;
                case 'tomorrow':
                    $start = (clone $nowInUserTz)->modify('+1 day')->setTime(0, 0, 0);
                    if (!empty($ignore_weekends) && strtolower($ignore_weekends) === 'true') {
                        $start = skipWeekends($start, 'forward');
                    }
                    $end   = (clone $start)->setTime(23, 59, 59);
                    if ($workingTimezone !== 'UTC') {
                        $start->setTimezone(new DateTimeZone('UTC'));
                        $end->setTimezone(new DateTimeZone('UTC'));
                    }
                    break;
                case 'next-2-days':
                    $start = (clone $nowInUserTz)->modify('+1 day')->setTime(0, 0, 0);
                    $end   = (clone $start)->modify('+1 day')->setTime(23, 59, 59);
                    if ($workingTimezone !== 'UTC') {
                        $start->setTimezone(new DateTimeZone('UTC'));
                        $end->setTimezone(new DateTimeZone('UTC'));
                    }
                    break;
                case 'this-week':
                    $today        = (clone $nowInUserTz)->setTime(0, 0, 0);
                    $dayOfWeek    = (int)$today->format('w');
                    $daysToEndSat = 6 - $dayOfWeek;
                    $start        = clone $nowInUserTz;
                    $end          = (clone $today)->modify("+{$daysToEndSat} days")->setTime(23, 59, 59);
                    if ($workingTimezone !== 'UTC') {
                        $start->setTimezone(new DateTimeZone('UTC'));
                        $end->setTimezone(new DateTimeZone('UTC'));
                    }
                    break;
                case 'next-week':
                    $today            = (clone $nowInUserTz)->setTime(0, 0, 0);
                    $dayOfWeek        = (int)$today->format('w');
                    $daysToNextSunday = ($dayOfWeek === 0 ? 7 : 7 - $dayOfWeek);
                    $start            = (clone $today)->modify("+{$daysToNextSunday} days")->setTime(0, 0, 0);
                    $end              = (clone $start)->modify('+6 days')->setTime(23, 59, 59);
                    if ($workingTimezone !== 'UTC') {
                        $start->setTimezone(new DateTimeZone('UTC'));
                        $end->setTimezone(new DateTimeZone('UTC'));
                    }
                    break;
                case 'next-2-weeks':
                    $start = clone $nowInUserTz;
                    $end   = (clone $nowInUserTz)->modify('+2 weeks')->setTime(23, 59, 59);
                    if ($workingTimezone !== 'UTC') {
                        $start->setTimezone(new DateTimeZone('UTC'));
                        $end->setTimezone(new DateTimeZone('UTC'));
                    }
                    break;
                case 'next-month':
                    // First day of next calendar month → last day of next calendar month
                    $start = (clone $nowInUserTz)->modify('first day of next month')->setTime(0, 0, 0);
                    $end   = (clone $start)->modify('last day of this month')->setTime(23, 59, 59);
                    if ($workingTimezone !== 'UTC') {
                        $start->setTimezone(new DateTimeZone('UTC'));
                        $end->setTimezone(new DateTimeZone('UTC'));
                    }
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

    // Build SQL - Compare dates and times separately for better compatibility
    $sql    = "SELECT * FROM economic_events 
               WHERE (event_date > :start_date OR (event_date = :start_date AND event_time >= :start_time))
               AND (event_date < :end_date OR (event_date = :end_date AND event_time <= :end_time))";
    
    $startDate = substr($start_datetime, 0, 10);
    $startTime = substr($start_datetime, 11, 8);
    $endDate = substr($end_datetime, 0, 10);
    $endTime = substr($end_datetime, 11, 8);
    
    $params = [
        ':start_date' => $startDate,
        ':start_time' => $startTime,
        ':end_date' => $endDate,
        ':end_time' => $endTime
    ];

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

    if (!empty($must_have)) {
        $mustHaveFields = array_map('trim', explode(',', $must_have));
        $validFields = ['forecast_value', 'actual_value', 'previous_value'];
        
        foreach ($mustHaveFields as $field) {
            if (in_array($field, $validFields)) {
                $sql .= " AND {$field} IS NOT NULL AND {$field} != ''";
            }
        }
    }

    if (!empty($impact)) {
        $impactLevels = array_map('trim', explode(',', $impact));
        $impactPlaceholders = [];
        foreach ($impactLevels as $i => $level) {
            $key = ":imp{$i}";
            $impactPlaceholders[] = $key;
            $params[$key] = ucfirst(strtolower($level));
        }
        $sql .= " AND impact_level IN (" . implode(',', $impactPlaceholders) . ")";
    }

    $sql  .= " ORDER BY event_date, event_time";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll();

    // Apply TBD replacement if requested (before debug capture)
    if ($tbd) {
        foreach ($events as &$event) {
            $event['actual_value'] = 'TBD';
        }
        unset($event);
    }

    // Store original database values for debug BEFORE any processing
    if (!empty($debug)) {
        foreach ($events as &$ev) {
            $ev['original_database_record'] = [
                'event_id' => $ev['event_id'],
                'event_name' => $ev['event_name'],
                'event_date' => $ev['event_date'],
                'event_time' => $ev['event_time'],
                'currency' => $ev['currency'],
                'forecast_value' => $ev['forecast_value'],
                'actual_value' => $ev['actual_value'],
                'previous_value' => $ev['previous_value'],
                'impact_level' => $ev['impact_level'],
                'consistent_event_id' => $ev['consistent_event_id'],
                'stored_timezone' => 'UTC'
            ];
        }
        unset($ev);
    }

    // For all future events, ensure actual_value is 'TBD'
    foreach ($events as &$ev) {
        $eventDT = DateTime::createFromFormat('Y-m-d H:i:s', $ev['event_date'].' '.$ev['event_time'], new DateTimeZone('UTC'));
        if ($eventDT > $now) {
            $ev['actual_value'] = 'TBD';
        }
        
        // Convert timezone if time_zone parameter is provided
        if (!empty($time_zone)) {
            $converted = convertToTimezone($ev['event_date'], $ev['event_time'], $time_zone, !empty($debug));
            $ev['event_date'] = $converted['date'];
            $ev['event_time'] = $converted['time'];
            
            // Add debug info to event if debug mode is enabled
            if (!empty($debug) && isset($converted['debug'])) {
                $ev['timezone_conversion_debug'] = $converted['debug'];
            }
        }
    }
    unset($ev);

    // Remove duplicates based on consistent_event_id if avoid_duplicates is true
    if (!empty($avoid_duplicates) && strtolower($avoid_duplicates) === 'true') {
        $seen = [];
        $uniqueEvents = [];
        foreach ($events as $ev) {
            $consistentId = $ev['consistent_event_id'];
            if (!isset($seen[$consistentId])) {
                $seen[$consistentId] = true;
                $uniqueEvents[] = $ev;
            }
        }
        $events = $uniqueEvents;
    }

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
