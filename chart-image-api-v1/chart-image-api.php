<?php 
/**
 * ------------------------------------------------------------------------
 *  Author : Ngonidzashe Jiji
 *  Handles: Instagram: @davidrichchild
 *           Telegram: t.me/david_richchild
 *           TikTok: davidrichchild
 *  URLs    : https://arrissadata.com
 *            https://arrissatechnologies.com
 *            https://arrissa.trade
 *
 *  Course  : https://www.udemy.com/course/6804721
 *
 *  Permission:
 *    You are granted permission to use, copy, modify, and distribute this
 *    software and its source code for personal or commercial projects,
 *    provided that the author details above remain intact and visible in
 *    the distributed software (including any compiled or minified form).
 *
 *  Requirements:
 *    - Keep the author name, handles, URLs, and course link in this header
 *      (or an equivalent attribution location in distributed builds).
 *    - You may NOT remove or obscure the attribution.
 *
 *  Disclaimer:
 *    This software is provided "AS IS", without warranty of any kind,
 *    express or implied. The author is not liable for any claim, damages,
 *    or other liability arising from the use of this software.
 *
 *  Version: 1.0
 *  Date:    2025-09-20
 * ------------------------------------------------------------------------
 */


// chart-api.php
// --------------
// HTTP GET params:
//   api_key       (string, required)
//   symbol        (string, required)
//   timeframe     (string, required)
//   count         (int, default 100)
//   pretend_date  (YYYY-MM-DD, optional)
//   pretend_time  (HH:MM, optional)
//   rangeType     (string, optional)
//   ema1_period   (int, optional)
//   ema2_period   (int, optional)
//   atr           (int, optional)
//   data          (string, optional)
//   fib           (boolean, optional) - draws Fibonacci retracement levels
//   period_separators (string, optional) - can be 5M,15M,30M,1H,4H,day,week,month,year
//   high_low      (boolean, optional) - draws high/low lines for each period segment
// Fetches from vestorfinance API and draws a 16:9 candlestick chart via GD,
// with optional EMAs, ATR, Fibonacci levels, dynamic height based solely on candle range,
// padding on the right, full-price precision, left-aligned X-axis labels,
// and a guaranteed current-price line (always dashed) and label.

require_once __DIR__ . '/../app/Database.php';

// Get base URL and API key from database
$db = Database::getInstance();
$pdo = $db->getConnection();

$stmt = $pdo->prepare("SELECT value FROM settings WHERE key = 'app_base_url'");
$stmt->execute();
$result = $stmt->fetch();
$BASE_URL = $result ? $result['value'] : '';

if (!$BASE_URL) {
    header('Content-Type: text/plain', true, 500);
    exit('Base URL not configured in database settings');
}

///////////////////////
// 1) Collect & validate
///////////////////////
$apiKey      = $_GET['api_key']      ?? null;
$symbol      = $_GET['symbol']       ?? null;
$timeframe   = $_GET['timeframe']    ?? null;
$count       = isset($_GET['count']) ? (int)$_GET['count'] : 100;
$pretendDate = $_GET['pretend_date'] ?? null;
$pretendTime = $_GET['pretend_time'] ?? null;
$rangeType   = $_GET['rangeType']    ?? null;
$ema1Period  = isset($_GET['ema1_period']) ? (int)$_GET['ema1_period'] : 0;
$ema2Period  = isset($_GET['ema2_period']) ? (int)$_GET['ema2_period'] : 0;
$atrPeriod   = isset($_GET['atr'])         ? (int)$_GET['atr']         : 0;
$dataField   = $_GET['data']         ?? null;
$showFib     = isset($_GET['fib']) && $_GET['fib'] === 'true';
$periodSeparators = $_GET['period_separators'] ?? null;
$showHighLow = isset($_GET['high_low']) && $_GET['high_low'] === 'true';
$streaming   = $_GET['streaming'] ?? null;
$theme       = $_GET['theme'] ?? 'light';

if (!$apiKey || !$symbol || !$timeframe) {
    header('Content-Type: text/plain', true, 400);
    exit('Missing api_key, symbol, or timeframe');
}

// Handle streaming modes
if ($streaming === 'redirect') {
    // Redirect directly to the streaming page
    $streamUrl = '/chart-image-api-v1/chart-stream.php?' . http_build_query($_GET);
    header('Location: ' . $streamUrl);
    exit;
} elseif ($streaming === 'url') {
    // Generate short code and return minified URL
    $shortCode = substr(md5(json_encode($_GET) . time()), 0, 8);
    
    // Store in database
    $stmt = $pdo->prepare("INSERT OR REPLACE INTO chart_stream_urls (short_code, params) VALUES (?, ?)");
    $stmt->execute([$shortCode, json_encode($_GET)]);
    
    $streamUrl = $BASE_URL . '/chart-image-api-v1/s/' . $shortCode;
    header('Content-Type: application/json');
    echo json_encode([
        'stream' => true,
        'url' => $streamUrl,
        'message' => 'Chart streaming enabled. Access the URL to view live updates.'
    ]);
    exit;
}

// Validate API key
$stmt = $pdo->prepare("SELECT value FROM settings WHERE key = 'api_key'");
$stmt->execute();
$result = $stmt->fetch();
$validApiKey = $result ? $result['value'] : '';

if ($apiKey !== $validApiKey) {
    header('Content-Type: text/plain', true, 401);
    exit('Invalid API key');
}

//////////////////////////
// 2) Font Setup
//////////////////////////
$fontDir = __DIR__ . '/fonts/';
$fontRegular = $fontDir . 'Manrope-Regular.ttf';
$fontMedium = $fontDir . 'Manrope-Medium.ttf';
$fontSemiBold = $fontDir . 'Manrope-SemiBold.ttf';
$fontBold = $fontDir . 'Manrope-Bold.ttf';

// Check if fonts exist
if (!file_exists($fontRegular)) {
    header('Content-Type: text/plain', true, 500);
    exit('Font file not found: ' . $fontRegular);
}

//////////////////////////
// 3) Fetch from API
//////////////////////////
$params = [
    'api_key'   => $apiKey,
    'symbol'    => $symbol,
    'timeframe' => $timeframe,
    'count'     => $count,
];
if ($pretendDate)  $params['pretend_date'] = $pretendDate;
if ($pretendTime)  $params['pretend_time'] = $pretendTime;
if ($rangeType)    $params['rangeType']   = $rangeType;
if ($dataField)    $params['data']        = $dataField;

$apiUrl = $BASE_URL . '/market-data-api-v1/market-data-api?' . http_build_query($params);
$raw    = @file_get_contents($apiUrl);
if ($raw === false) {
    header('Content-Type: text/plain', true, 502);
    exit('Failed to fetch market data');
}
$json = json_decode($raw, true);

// Handle vestor_data wrapper
$data = null;
if (isset($json['vestor_data'])) {
    $data = $json['vestor_data'];
} else {
    // Fallback for old format
    $data = $json;
}

if (!isset($data['candles']) || !is_array($data['candles'])) {
    header('Content-Type: text/plain', true, 502);
    exit('Invalid JSON from market-data-api - no candles data found');
}

//////////////////////////
// 4) Prepare candle data - CORRECT ORDERING FOR CHART DISPLAY
//////////////////////////
$origCandles = $data['candles'];

// Keep original order - API returns oldest first, we want oldest on left, newest on right
$candles = $origCandles;
$n       = count($candles);
if ($n === 0) {
    header('Content-Type: text/plain', true, 502);
    exit('No candle data returned');
}

// For range text - oldest to newest display
$firstCandle = $candles[0];      // Oldest candle (leftmost on chart)
$lastCandle  = $candles[$n-1];   // Newest candle (rightmost on chart)

$closes = array_column($candles, 'close');

// Current price should be the most recent close (last candle)
$currentPrice = isset($data['currentPrice'])
    ? (float)$data['currentPrice']
    : (float)$candles[$n-1]['close'];  // Most recent candle (last in array)

//////////////////////////
// 5) Period Separators Helper Functions
//////////////////////////
function parseTimeString($timeStr) {
    // Handle various time formats more aggressively
    $timeStr = trim($timeStr);
    
    // Try different parsing methods
    $patterns = [
        '/(\d{4})[.-](\d{2})[.-](\d{2})\s+(\d{2}):(\d{2})/',  // YYYY-MM-DD HH:MM or YYYY.MM.DD HH:MM
        '/(\d{4})[.-](\d{2})[.-](\d{2})T(\d{2}):(\d{2})/',    // YYYY-MM-DDTHH:MM
        '/(\d{4})[.-](\d{2})[.-](\d{2})/',                    // YYYY-MM-DD only
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $timeStr, $matches)) {
            if (count($matches) >= 6) {
                // Date and time
                $dateTime = $matches[1] . '-' . $matches[2] . '-' . $matches[3] . ' ' . $matches[4] . ':' . $matches[5];
                return strtotime($dateTime);
            } elseif (count($matches) >= 4) {
                // Date only
                $date = $matches[1] . '-' . $matches[2] . '-' . $matches[3];
                return strtotime($date);
            }
        }
    }
    
    // Fallback to general strtotime
    $timestamp = strtotime($timeStr);
    return $timestamp !== false ? $timestamp : null;
}

function shouldDrawSeparator($currentTime, $prevTime, $separatorType) {
    if (!$currentTime || !$prevTime) return false;
    
    switch ($separatorType) {
        case '5M':
            return floor($currentTime / 300) != floor($prevTime / 300);
        case '15M':
            return floor($currentTime / 900) != floor($prevTime / 900);
        case '30M':
            return floor($currentTime / 1800) != floor($prevTime / 1800);
        case '1H':
            return date('Y-m-d H', $currentTime) != date('Y-m-d H', $prevTime);
        case '4H':
            $currentHour = date('H', $currentTime);
            $prevHour = date('H', $prevTime);
            $current4H = floor($currentHour / 4);
            $prev4H = floor($prevHour / 4);
            return (date('Y-m-d', $currentTime) . '-' . $current4H) != (date('Y-m-d', $prevTime) . '-' . $prev4H);
        case 'day':
            return date('Y-m-d', $currentTime) != date('Y-m-d', $prevTime);
        case 'week':
            return date('Y-W', $currentTime) != date('Y-W', $prevTime);
        case 'month':
            return date('Y-m', $currentTime) != date('Y-m', $prevTime);
        case 'year':
            return date('Y', $currentTime) != date('Y', $prevTime);
        default:
            return false;
    }
}

function getSeparatorLabel($timestamp, $separatorType) {
    switch ($separatorType) {
        case '5M':
        case '15M':
        case '30M':
            return date('H:i', $timestamp);
        case '1H':
            return date('H:00', $timestamp);
        case '4H':
            $hour = date('H', $timestamp);
            $fourHourSlot = floor($hour / 4) * 4;
            return sprintf('%02d:00', $fourHourSlot);
        case 'day':
            // Return day abbreviation: MON, TUE, WED, THU, FRI, SAT, SUN
            $dayNumber = date('w', $timestamp); // 0 = Sunday, 1 = Monday, etc.
            $dayNames = ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'];
            return $dayNames[$dayNumber];
        case 'week':
            return 'W' . date('W', $timestamp);
        case 'month':
            return date('M', $timestamp);
        case 'year':
            return date('Y', $timestamp);
        default:
            return '';
    }
}

//////////////////////////
// 6) Calculate Period Separators
//////////////////////////
$separatorPositions = [];
$separatorLabels = [];

if ($periodSeparators && in_array($periodSeparators, ['5M','15M','30M','1H','4H','day','week','month','year'])) {
    // Always add the first candle label
    $firstTime = parseTimeString($candles[0]['time']);
    if ($firstTime) {
        $separatorPositions[] = 0;
        $separatorLabels[] = getSeparatorLabel($firstTime, $periodSeparators);
    }
    
    // Check for separators in the rest of the data
    for ($i = 1; $i < $n; $i++) {
        $currentTime = parseTimeString($candles[$i]['time']);
        $prevTime = parseTimeString($candles[$i-1]['time']);
        
        if ($currentTime && $prevTime && shouldDrawSeparator($currentTime, $prevTime, $periodSeparators)) {
            $separatorPositions[] = $i;
            $separatorLabels[] = getSeparatorLabel($currentTime, $periodSeparators);
        }
    }
    
    // Fallback: If no separators found and we're looking for days, create them based on timeframe
    if (count($separatorPositions) <= 1 && $periodSeparators === 'day') {
        // Clear existing and create fallback separators
        $separatorPositions = [0]; // Keep first
        $separatorLabels = [];
        
        // Add first label
        if ($firstTime) {
            $separatorLabels[] = getSeparatorLabel($firstTime, $periodSeparators);
        }
        
        // For H1 timeframe, approximately every 24 candles is a new day
        // For other timeframes, adjust accordingly
        $candlesPerDay = 24; // Default for H1
        if (strpos(strtolower($timeframe), 'm') !== false) {
            // Minutes timeframe
            $minutes = (int)str_replace(['M', 'm'], '', $timeframe);
            $candlesPerDay = 1440 / $minutes; // 1440 minutes in a day
        } elseif (strpos(strtolower($timeframe), 'h') !== false) {
            // Hours timeframe
            $hours = (int)str_replace(['H', 'h'], '', $timeframe);
            $candlesPerDay = 24 / $hours;
        }
        
        // Add separator every estimated day
        $dayNames = ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'];
        $startDayIndex = $firstTime ? date('w', $firstTime) : 3; // Default to Wednesday if can't parse
        
        for ($i = $candlesPerDay; $i < $n; $i += $candlesPerDay) {
            if ($i < $n) {
                $separatorPositions[] = $i;
                $dayIndex = ($startDayIndex + count($separatorPositions) - 1) % 7;
                $separatorLabels[] = $dayNames[$dayIndex];
            }
        }
    }
}

//////////////////////////
// 7) Calculate High/Low for each period segment - EXCLUDE LAST SEGMENT
//////////////////////////
$periodHighLows = [];

if ($showHighLow && $periodSeparators && !empty($separatorPositions)) {
    $totalSeparators = count($separatorPositions);
    
    // Only process segments up to the second-to-last separator (exclude the last ongoing segment)
    for ($seg = 0; $seg < $totalSeparators - 1; $seg++) {
        $startIdx = $separatorPositions[$seg];
        $endIdx = $separatorPositions[$seg + 1] - 1; // End at the candle before next separator
        
        $segmentLabel = $separatorLabels[$seg] ?? 'SEG' . $seg;
        
        // Find high and low in this segment
        $segmentHigh = -PHP_FLOAT_MAX;
        $segmentLow = PHP_FLOAT_MAX;
        $highIdx = $startIdx;
        $lowIdx = $startIdx;
        
        for ($i = $startIdx; $i <= $endIdx; $i++) {
            $high = (float)$candles[$i]['high'];
            $low = (float)$candles[$i]['low'];
            
            if ($high > $segmentHigh) {
                $segmentHigh = $high;
                $highIdx = $i;
            }
            if ($low < $segmentLow) {
                $segmentLow = $low;
                $lowIdx = $i;
            }
        }
        
        $periodHighLows[] = [
            'label' => $segmentLabel,
            'high' => $segmentHigh,
            'low' => $segmentLow,
            'high_idx' => $highIdx,
            'low_idx' => $lowIdx,
            'start_idx' => $startIdx,
            'end_idx' => $endIdx
        ];
    }
    
    // Note: We deliberately skip the last segment (from last separator to end of data)
    // because it's the current/ongoing period
}

//////////////////////////
// 8) Determine decimal precision
//////////////////////////
$precision = 0;
foreach ($candles as $c) {
    foreach (['open','high','low','close'] as $k) {
        $s = (string)$c[$k];
        if (false !== ($pos = strpos($s, '.'))) {
            $dp = strlen($s) - $pos - 1;
            if ($dp > $precision) $precision = $dp;
        }
    }
}
if (false !== ($pos = strpos((string)$currentPrice, '.'))) {
    $dp = strlen((string)$currentPrice) - $pos - 1;
    if ($dp > $precision) $precision = $dp;
}

//////////////////////////
// 9) EMA & ATR helpers
//////////////////////////
function computeEMA(array $data, int $period): array {
    $ema = []; $k = 2/($period+1);
    $prev = array_sum(array_slice($data,0,$period))/$period;
    for ($i=0; $i<count($data); $i++) {
        if ($i < $period-1) {
            $ema[] = null;
        } elseif ($i === $period-1) {
            $ema[] = $prev;
        } else {
            $prev = $data[$i]*$k + $prev*(1-$k);
            $ema[] = $prev;
        }
    }
    return $ema;
}
function computeATR(array $c, int $p): array {
    $tr = [];
    for ($i=0; $i<count($c); $i++) {
        $h = $c[$i]['high']; $l = $c[$i]['low'];
        if ($i===0) {
            $tr[] = $h - $l;
        } else {
            $pc = $c[$i-1]['close'];
            $tr[] = max($h-$l, abs($h-$pc), abs($l-$pc));
        }
    }
    return computeEMA($tr, $p);
}

// For indicators, candles are already in correct order (oldest to newest)
$ema1 = $ema1Period>1 ? computeEMA($closes, $ema1Period) : [];
$ema2 = $ema2Period>1 ? computeEMA($closes, $ema2Period) : [];
$atr  = $atrPeriod>1  ? computeATR($candles, $atrPeriod)   : [];

//////////////////////////
// 10) Compute price extents (candles only)
//////////////////////////
$highs = array_column($candles, 'high');
$lows  = array_column($candles, 'low');
$candleMaxP  = max($highs);  // Actual highest candle point
$candleMinP  = min($lows);   // Actual lowest candle point
$candleRange = $candleMaxP - $candleMinP;
if ($candleRange <= 0) $candleRange = 1;

// Add vertical padding (5% top and bottom) - extends the scale beyond candle range
$verticalPadding = $candleRange * 0.05;
$maxP = $candleMaxP + $verticalPadding;  // Scale extends above highest candle
$minP = $candleMinP - $verticalPadding;  // Scale extends below lowest candle
$range = $maxP - $minP;  // Total chart range including padding

// Add horizontal padding (right side) - reserve space for additional candles
$horizontalPaddingCandles = ceil($n * 0.05); // 5% padding in terms of candle count

//////////////////////////
// 11) Calculate Fibonacci levels
//////////////////////////
$fibLevels = [];
$fibLevelsToDisplay = [];
if ($showFib) {
    // Determine trend: compare first and last candle close
    $firstClose = (float)$candles[0]['close'];
    $lastClose = (float)$candles[$n-1]['close'];
    $isUptrend = $lastClose > $firstClose;
    
    // Calculate all levels based on ACTUAL candle range (not padded)
    $fibLevels = [
        '0%'    => $candleMinP,
        '23.6%' => $candleMinP + $candleRange * 0.236,
        '38.2%' => $candleMinP + $candleRange * 0.382,
        '50%'   => $candleMinP + $candleRange * 0.5,
        '61.8%' => $candleMinP + $candleRange * 0.618,
        '76.4%' => $candleMinP + $candleRange * 0.764,
        '100%'  => $candleMaxP
    ];
    
    // Display levels based on trend direction
    if ($isUptrend) {
        // Uptrend: 0% at low, 100% at high
        $fibLevelsToDisplay = [
            '0%'    => $candleMinP,
            '23.6%' => $candleMinP + $candleRange * 0.236,
            '38.2%' => $candleMinP + $candleRange * 0.382,
            '50%'   => $candleMinP + $candleRange * 0.5,
            '61.8%' => $candleMinP + $candleRange * 0.618,
            '76.4%' => $candleMinP + $candleRange * 0.764,
            '100%'  => $candleMaxP
        ];
    } else {
        // Downtrend: 0% at high, 100% at low
        $fibLevelsToDisplay = [
            '0%'    => $candleMaxP,
            '23.6%' => $candleMaxP - $candleRange * 0.236,
            '38.2%' => $candleMaxP - $candleRange * 0.382,
            '50%'   => $candleMaxP - $candleRange * 0.5,
            '61.8%' => $candleMaxP - $candleRange * 0.618,
            '76.4%' => $candleMaxP - $candleRange * 0.764,
            '100%'  => $candleMinP
        ];
    }
}

//////////////////////////
// 12) Helper function for dashed lines (cross-platform compatible)
//////////////////////////
function drawDashedLine($img, $x1, $y1, $x2, $y2, $color, $dashLength = 5, $gapLength = 3) {
    $distance = sqrt(pow($x2 - $x1, 2) + pow($y2 - $y1, 2));
    $dx = ($x2 - $x1) / $distance;
    $dy = ($y2 - $y1) / $distance;
    
    $drawn = 0;
    $drawing = true;
    
    for ($i = 0; $i < $distance; $i++) {
        if ($drawing) {
            $drawn++;
            $x = intval($x1 + $dx * $i);
            $y = intval($y1 + $dy * $i);
            imagesetpixel($img, $x, $y, $color);
            
            if ($drawn >= $dashLength) {
                $drawing = false;
                $drawn = 0;
            }
        } else {
            $drawn++;
            if ($drawn >= $gapLength) {
                $drawing = true;
                $drawn = 0;
            }
        }
    }
}

//////////////////////////
// 13) Setup image (16:9)
//////////////////////////
$W   = 1600; $H = 900;
$img = imagecreatetruecolor($W, $H);
imageantialias($img, true);

// Theme-based colors
if ($theme === 'dark') {
    // Dark theme: dark background matching sidebar
    $bgColor   = imagecolorallocate($img, 20, 20, 20);  // rgba(20, 20, 20, 0.95)
    $fgColor   = imagecolorallocate($img,255,255,255);  // White
    $gridColor = imagecolorallocate($img, 40, 40, 40);  // Dark gray for grid
    $textColor = imagecolorallocate($img,255,255,255);  // White text
    $lightGray = imagecolorallocate($img, 60, 60, 60);  // Darker gray
    $darkGray  = imagecolorallocate($img,180,180,180);  // Lighter gray (inverted)
} else {
    // Light theme: white background, black foreground (default)
    $bgColor   = imagecolorallocate($img,255,255,255);  // White
    $fgColor   = imagecolorallocate($img,  0,  0,  0);  // Black
    $gridColor = imagecolorallocate($img,230,230,230);  // Light gray for grid
    $textColor = imagecolorallocate($img,  0,  0,  0);  // Black text
    $lightGray = imagecolorallocate($img,230,230,230);
    $darkGray  = imagecolorallocate($img, 80, 80, 80);
}

// Common colors (always the same)
$white     = imagecolorallocate($img,255,255,255);
$black     = imagecolorallocate($img,  0,  0,  0);
$upColor   = imagecolorallocate($img, 38,166,154);   // Keep candles as-is
$dnColor   = imagecolorallocate($img,239, 83, 80);   // Keep candles as-is
$ema1Col   = imagecolorallocate($img,237,125, 49);
$ema2Col   = imagecolorallocate($img,165,165,165);
$atrCol    = imagecolorallocate($img, 75,  0,130);
$fibCol    = imagecolorallocate($img, 30, 144, 255); // Blue for Fibonacci
$red       = imagecolorallocate($img,255,  0,  0);
$separatorCol = imagecolorallocate($img, 100, 100, 100);
$highCol   = imagecolorallocate($img, 0, 255, 0);   // Bright green for highs
$lowCol    = imagecolorallocate($img, 255, 0, 255); // Bright magenta for lows

imagefilledrectangle($img,0,0,$W,$H,$bgColor);

//////////////////////////
// 13) Font size definitions
//////////////////////////
$titleFontSize = 14;
$labelFontSize = 12;
$xAxisLabelSize = 9;  // Slightly smaller font for date/time labels
$rangeFontSize = 10;
$separatorLabelSize = 10;
$highLowLabelSize = 9;

//////////////////////////
// 14) Helper function to get text dimensions
//////////////////////////
function getTextDimensions($text, $font, $size) {
    $bbox = imagettfbbox($size, 0, $font, $text);
    return [
        'width' => $bbox[4] - $bbox[0],
        'height' => $bbox[1] - $bbox[7]
    ];
}

//////////////////////////
// 15) Compute margins & chart area
//////////////////////////
// Y-axis label width (including Fibonacci levels if enabled)
$maxLabelW = 0;
for ($i=0; $i<=10; $i++) {
    $p   = $maxP - ($range/10)*$i;
    $lbl = number_format($p, $precision, '.', '');
    $dims = getTextDimensions($lbl, $fontRegular, $labelFontSize);
    $maxLabelW = max($maxLabelW, $dims['width']);
}

// Check Fibonacci label widths (only for displayed levels)
if ($showFib) {
    foreach ($fibLevelsToDisplay as $level => $price) {
        $lbl = number_format($price, $precision, '.', '') . ' (' . $level . ')';
        $dims = getTextDimensions($lbl, $fontRegular, $labelFontSize);
        $maxLabelW = max($maxLabelW, $dims['width']);
    }
}

// Check high/low label widths
if ($showHighLow && !empty($periodHighLows)) {
    foreach ($periodHighLows as $hl) {
        $highLabel = $hl['label'] . '-HIGH ' . number_format($hl['high'], $precision, '.', '');
        $lowLabel = $hl['label'] . '-LOW ' . number_format($hl['low'], $precision, '.', '');
        $highDims = getTextDimensions($highLabel, $fontRegular, $highLowLabelSize);
        $lowDims = getTextDimensions($lowLabel, $fontRegular, $highLowLabelSize);
        $maxLabelW = max($maxLabelW, $highDims['width'], $lowDims['width']);
    }
}

// Current price text width
$priceText = number_format($currentPrice, $precision, '.', '');
$priceDims = getTextDimensions($priceText, $fontMedium, $labelFontSize);
$priceTextW = $priceDims['width'];

// X-axis label width (for ~5 labels)
$stepCount  = max(1, floor($n/5));
$maxXLabelW = 0;
for ($i=0; $i<$n; $i+=$stepCount) {
    $lbl = $candles[$i]['time'];
    $dims = getTextDimensions($lbl, $fontRegular, $xAxisLabelSize);
    $maxXLabelW = max($maxXLabelW, $dims['width']);
}

// margins - price scale on right side like trading charts
$marginLeft   = max(40, $maxXLabelW + 10); // Minimal left margin
$marginRight  = max($maxLabelW + 20, 150, $priceTextW + 20, $maxXLabelW + 10); // Price labels on right
$marginTop    = 80; // Increased for separator labels
$marginBottom = 80;

// chart dims
$chartWFull = $W - $marginLeft - $marginRight; // Total width available
$chartW     = $chartWFull; // Full width for grid and axes
$rightPaddingPercent = 0.05; // 5% padding between last candle and price axis
$candleAreaW = $chartW * (1 - $rightPaddingPercent); // Width for candles only (95%)
$xStepFull  = $candleAreaW / $n; // Step size for candles
$chartX0    = $marginLeft;
$chartY0    = $marginTop;
$chartH     = $H - $marginTop - $marginBottom;

//////////////////////////
// 16) Draw period separators (behind everything else)
//////////////////////////
$xStep = $xStepFull; // Use consistent step size

if ($periodSeparators && !empty($separatorPositions)) {
    for ($j = 0; $j < count($separatorPositions); $j++) {
        $i = $separatorPositions[$j];
        $label = $separatorLabels[$j] ?? '';
        
        // Calculate separator line position
        $x = $chartX0 + $i * $xStep;
        
        // Draw vertical separator line (behind candlesticks) - skip for position 0
        if ($i > 0) {
            imagesetthickness($img, 2);
            imageline($img, intval($x), $chartY0, intval($x), intval($chartY0 + $chartH), $separatorCol);
            imagesetthickness($img, 1);
        }
        
        // Draw separator label at top of chart
        if (!empty($label)) {
            $labelDims = getTextDimensions($label, $fontMedium, $separatorLabelSize);
            $labelX = $x - $labelDims['width'] / 2;
            $labelY = $chartY0 - 15;
            
            // For position 0, align label to the left edge
            if ($i == 0) {
                $labelX = $chartX0 + 5;
            }
            
            // Add background rectangle for better visibility
            $bgPadding = 4;
            imagefilledrectangle($img, 
                intval($labelX - $bgPadding), 
                intval($labelY - $labelDims['height'] - $bgPadding),
                intval($labelX + $labelDims['width'] + $bgPadding), 
                intval($labelY + $bgPadding),
                $white
            );
            
            // Draw border around label
            imagerectangle($img, 
                intval($labelX - $bgPadding), 
                intval($labelY - $labelDims['height'] - $bgPadding),
                intval($labelX + $labelDims['width'] + $bgPadding), 
                intval($labelY + $bgPadding),
                $separatorCol
            );
            
            imagettftext($img, $separatorLabelSize, 0, intval($labelX), intval($labelY), $separatorCol, $fontMedium, $label);
        }
    }
}

//////////////////////////
// 17) Calculate Y-scale factor for later use
//////////////////////////
$ysf = $chartH / $range;

//////////////////////////
// 18) Draw grid & axes
//////////////////////////
// horizontal (price) grid: 10 divisions
for ($i=0; $i<=10; $i++) {
    $y = intval($chartY0 + $i*$chartH/10);
    imageline($img,$chartX0,$y,intval($chartX0+$chartW),$y,$gridColor);
}
// vertical (time) grid: 5 divisions
for ($i=0; $i<=5; $i++) {
    $x = intval($chartX0 + $i*$chartW/5);
    imageline($img,$x,$chartY0,$x,intval($chartY0+$chartH),$gridColor);
}
// axes borders
for ($dx=0; $dx<2; $dx++){
    imageline($img,$chartX0+$dx,$chartY0,$chartX0+$dx,intval($chartY0+$chartH),$textColor);
    imageline($img,$chartX0,intval($chartY0+$chartH)-$dx,intval($chartX0+$chartW),intval($chartY0+$chartH)-$dx,$textColor);
}

//////////////////////////
// 19) Title
//////////////////////////
$title = "{$symbol} {$timeframe} Chart by flowbase.com";
$titleDims = getTextDimensions($title, $fontSemiBold, $titleFontSize);
$titleX = intval(($W - $titleDims['width']) / 2);
imagettftext($img, $titleFontSize, 0, $titleX, 35, $textColor, $fontSemiBold, $title);

//////////////////////////
// 20) Y-axis labels (price scale on right)
//////////////////////////
for ($i=0; $i<=10; $i++){
    $p   = $maxP - ($range/10)*$i;
    $lbl = number_format($p, $precision, '.', '');
    $dims = getTextDimensions($lbl, $fontRegular, $labelFontSize);
    $y   = intval($chartY0 + $i*$chartH/10 + $dims['height']/2);
    $x   = intval($chartX0 + $chartW + 10); // Right side of chart
    imagettftext($img, $labelFontSize, 0, $x, $y, $textColor, $fontRegular, $lbl);
}

//////////////////////////
// 21) X-axis labels (oldest to newest left to right)
//////////////////////////
$step  = max(1, floor($n/5));
for ($i=0; $i<$n; $i+=$step) {
    // API data is oldest to newest, perfect for left to right display
    $raw = $candles[$i]['time'];
    $dims = getTextDimensions($raw, $fontRegular, $xAxisLabelSize);
    $x   = intval($chartX0 + $i*$xStep + $xStep/2 - $dims['width']/2);
    $y   = intval($chartY0 + $chartH + 20);
    imagettftext($img, $xAxisLabelSize, 0, $x, $y, $textColor, $fontRegular, $raw);
}

//////////////////////////
// 22) Candlesticks (oldest to newest left to right)
//////////////////////////
$bodyW = max(2, intval($xStep*0.8));
for ($i=0; $i<$n; $i++){
    $c   = $candles[$i];  // API data is already oldest to newest
    $o   = (float)$c['open'];
    $h   = (float)$c['high'];
    $l   = (float)$c['low'];
    $c1  = (float)$c['close'];
    $cx  = intval($chartX0 + $i*$xStep + $xStep/2);
    $yH  = intval($chartY0 + ($maxP-$h)*$ysf);
    $yL  = intval($chartY0 + ($maxP-$l)*$ysf);
    $yO  = intval($chartY0 + ($maxP-$o)*$ysf);
    $yC  = intval($chartY0 + ($maxP-$c1)*$ysf);

    // choose color based on bullish or bearish
    $col = $o < $c1 ? $upColor : $dnColor;

    // draw wick in same color as body
    imageline($img, $cx, $yH, $cx, $yL, $col);

    // draw body
    imagefilledrectangle(
        $img,
        intval($cx - $bodyW/2), min($yO,$yC),
        intval($cx + $bodyW/2), max($yO,$yC),
        $col
    );
}

//////////////////////////
// 23) Draw indicators
//////////////////////////
function drawLine($vals, $col) {
    global $img, $chartX0, $chartY0, $xStep, $ysf, $maxP;
    $px = null; $py = null;
    foreach ($vals as $i=>$v) {
        if ($v !== null) {
            $x = intval($chartX0 + $i*$xStep + $xStep/2);
            $y = intval($chartY0 + ($maxP-$v)*$ysf);
            if ($px !== null) imageline($img, $px, $py, $x, $y, $col);
            $px = $x; $py = $y;
        }
    }
}
if ($ema1) drawLine($ema1, $ema1Col);
if ($ema2) drawLine($ema2, $ema2Col);
if ($atr)  drawLine($atr,  $atrCol);

//////////////////////////
// 24) Draw Fibonacci levels (dashed blue lines) - Only display specified levels
//////////////////////////
if ($showFib) {
    foreach ($fibLevelsToDisplay as $level => $price) {
        $yFib = intval($chartY0 + ($maxP - $price) * $ysf);
        
        // Draw thicker dashed line by drawing multiple lines
        for ($offset = -1; $offset <= 1; $offset++) {
            drawDashedLine($img, $chartX0, $yFib + $offset, intval($chartX0 + $chartW), $yFib + $offset, $fibCol);
        }
        
        // Draw Fibonacci level label (smaller text, no background, on left side)
        $fibLabel = number_format($price, $precision, '.', '') . ' (' . $level . ')';
        $fibFontSize = 9; // Slightly larger font for Fibonacci labels
        $fibDims = getTextDimensions($fibLabel, $fontRegular, $fibFontSize);
        
        // Position text on left side
        $fibTextX = intval($chartX0 - $fibDims['width'] - 6);
        
        // Draw text (no rectangle background)
        imagettftext($img, $fibFontSize, 0,
            $fibTextX,
            intval($yFib + $fibDims['height']/2),
            $textColor,
            $fontRegular,
            $fibLabel
        );
    }
}

//////////////////////////
// 25) Draw high/low lines for each period segment (EXCLUDING LAST SEGMENT)
//////////////////////////
if ($showHighLow && !empty($periodHighLows)) {
    foreach ($periodHighLows as $hl) {
        // Calculate Y positions
        $yHigh = intval($chartY0 + ($maxP - $hl['high']) * $ysf);
        $yLow = intval($chartY0 + ($maxP - $hl['low']) * $ysf);
        
        // Calculate X positions - draw from segment start to segment end
        $xStart = intval($chartX0 + $hl['start_idx'] * $xStep);
        $xEnd = intval($chartX0 + $hl['end_idx'] * $xStep + $xStep);
        
        // Draw thick high line (dashed green) - draw multiple lines for thickness
        for ($offset = -1; $offset <= 1; $offset++) {
            drawDashedLine($img, $xStart, $yHigh + $offset, $xEnd, $yHigh + $offset, $highCol);
        }
        
        // Draw thick low line (dashed purple) - draw multiple lines for thickness
        for ($offset = -1; $offset <= 1; $offset++) {
            drawDashedLine($img, $xStart, $yLow + $offset, $xEnd, $yLow + $offset, $lowCol);
        }
        
        // Draw high label on the right
        $highLabel = $hl['label'] . '-HIGH ' . number_format($hl['high'], $precision, '.', '');
        $highDims = getTextDimensions($highLabel, $fontRegular, $highLowLabelSize);
        imagettftext($img, $highLowLabelSize, 0,
            intval($xEnd + 6),
            intval($yHigh + $highDims['height']/2),
            $highCol,
            $fontRegular,
            $highLabel
        );
        
        // Draw low label on the right
        $lowLabel = $hl['label'] . '-LOW ' . number_format($hl['low'], $precision, '.', '');
        $lowDims = getTextDimensions($lowLabel, $fontRegular, $highLowLabelSize);
        imagettftext($img, $highLowLabelSize, 0,
            intval($xEnd + 6),
            intval($yLow + $lowDims['height']/2),
            $lowCol,
            $fontRegular,
            $lowLabel
        );
    }
}

//////////////////////////
// 26) Current price line & label with red triangle
//////////////////////////
$yCurrent = intval($chartY0 + ($maxP - $currentPrice) * $ysf);
$lineCol  = imagecolorallocate($img,200,0,0);
// Draw dashed red line
drawDashedLine($img, $chartX0, $yCurrent, intval($chartX0 + $chartW), $yCurrent, $lineCol);

// Draw current price in red rectangle with white text
$label = number_format($currentPrice, $precision, '.', '');
$labelDims = getTextDimensions($label, $fontMedium, $labelFontSize);

// Calculate rectangle position
$rectX = intval($chartX0 + $chartW) + 6;
$rectPadding = 4;
$rectWidth = $labelDims['width'] + ($rectPadding * 2);
$rectHeight = $labelDims['height'] + ($rectPadding * 2);

// Draw red filled rectangle
imagefilledrectangle($img,
    $rectX,
    intval($yCurrent - $rectHeight/2),
    intval($rectX + $rectWidth),
    intval($yCurrent + $rectHeight/2),
    $red
);

// Draw white text inside rectangle
imagettftext($img, $labelFontSize, 0,
    intval($rectX + $rectPadding),
    intval($yCurrent + $labelDims['height']/2),
    $white,
    $fontMedium,
    $label
);

//////////////////////////
// 27) Bottom text: range display (oldest to newest)
//////////////////////////
// $firstCandle is oldest (leftmost), $lastCandle is newest (rightmost)
$rangeText = $firstCandle['time'] . ' - ' . $lastCandle['time'];
$rangeDims = getTextDimensions($rangeText, $fontRegular, $rangeFontSize);
$rangeX = intval(($W - $rangeDims['width']) / 2);
imagettftext($img, $rangeFontSize, 0, $rangeX, $H - 20, $textColor, $fontRegular, $rangeText);

//////////////////////////
// 28) Output PNG
//////////////////////////
header('Content-Type: image/png');
imagepng($img);
imagedestroy($img);
?>