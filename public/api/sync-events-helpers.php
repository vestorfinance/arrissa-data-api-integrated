<?php
/**
 * Shared helpers for sync-events.php, update-events.php and cron-sync-events.php.
 * Included by those files — do not call directly.
 */

function fetchFromTradingView(DateTime $from, DateTime $to, string $countriesStr): array {
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

function persistLastUpdated(PDO $db): string {
    $nowStr = (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    $db->prepare(
        "INSERT OR REPLACE INTO settings (key, value, updated_at) VALUES ('events_last_updated', ?, ?)"
    )->execute([$nowStr, $nowStr]);
    return $nowStr;
}

function getCurrenciesToCountries(?string $currenciesStr): string {
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
    $countries  = [];

    foreach ($currencies as $cur) {
        $cur = strtoupper($cur);
        if (isset($currencyMap[$cur])) {
            $countries = array_merge($countries, explode(',', $currencyMap[$cur]));
        }
    }

    return implode(',', array_unique($countries));
}

function parseTradingViewDateTime(string $isoDateStr): array {
    if (!$isoDateStr || trim($isoDateStr) === '') {
        return ['date' => date('Y-m-d'), 'time' => '00:00:00'];
    }
    try {
        $dt = new DateTime($isoDateStr, new DateTimeZone('UTC'));
        return ['date' => $dt->format('Y-m-d'), 'time' => $dt->format('H:i:s')];
    } catch (Exception $e) {
        return ['date' => date('Y-m-d'), 'time' => '00:00:00'];
    }
}

function generateEventId(string $name, string $currency, string $date, string $time): string {
    return hash('sha1', "$name-$currency-$date-$time");
}

function generateConsistentId(string $name): string {
    $clean   = trim(preg_replace('/\s*\((?!(?:MoM|YoY|QoQ)\))[^)]*\)/', '', $name));
    $hashRaw = md5($clean, true);
    $id = '';
    for ($i = 0; $i < 5; $i++) {
        $id .= chr(65 + (ord($hashRaw[$i]) % 26));
    }
    return $id;
}

function parseValue($value) {
    if ($value === null || $value === '') return null;
    if (is_int($value) || is_float($value)) return $value;
    $str = trim((string)$value);
    if ($str === '' || $str === '-' || strtolower($str) === 'n/a') return null;
    $s = preg_replace('/[\s,%]+/', '', $str);
    if (preg_match('/^([+-]?[\d.]+)[Kk]$/', $s, $m)) return (float)$m[1] * 1e3;
    if (preg_match('/^([+-]?[\d.]+)[Mm]$/', $s, $m)) return (float)$m[1] * 1e6;
    if (preg_match('/^([+-]?[\d.]+)[Bb]$/', $s, $m)) return (float)$m[1] * 1e9;
    $num = (float)$s;
    return is_nan($num) ? null : $num;
}

function mapImpactLevel($importance): string {
    if (is_numeric($importance)) {
        $v = (int)$importance;
        if ($v >= 1)  return 'High';
        if ($v === 0) return 'Moderate';
        return 'Low';
    }
    $s = strtolower(trim((string)$importance));
    if (strpos($s, 'high') !== false) return 'High';
    if (strpos($s, 'medium') !== false || strpos($s, 'moderate') !== false) return 'Moderate';
    if (strpos($s, 'low') !== false) return 'Low';
    return 'Moderate';
}
