<?php
require_once __DIR__ . '/../app/WebScraper.php';

$url = 'https://sslecal2.forexprostools.com/';

echo "<h1>Testing: " . htmlspecialchars($url) . "</h1>";
echo "<style>body { font-family: monospace; background: #1a1a1a; color: #0f0; padding: 20px; } h2 { color: #0ff; } pre { background: #000; padding: 15px; border: 1px solid #0f0; overflow-x: auto; } .error { color: #f00; } .success { color: #0f0; } .info { color: #ff0; }</style>";

// Create a fresh cookie file
$cookieFile = sys_get_temp_dir() . '/forex_scraper_' . time() . '.txt';

// Test 1: Visit investing.com first to get cookies, then visit target
echo "<h2>Test 1: Two-Step with Cookie Persistence</h2>";
try {
    $ch = curl_init();
    
    // Step 1: Visit investing.com to establish session
    echo "<p class='info'>Step 1: Visiting investing.com to get cookies...</p>";
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://www.investing.com/economic-calendar/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_ENCODING => 'gzip, deflate'
    ]);
    
    curl_exec($ch);
    echo "<p class='success'>✅ Cookies saved</p>";
    
    // Step 2: Now visit the target URL with cookies
    echo "<p class='info'>Step 2: Visiting target URL with cookies...</p>";
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.5',
            'Accept-Encoding: gzip, deflate',
            'Referer: https://www.investing.com/economic-calendar/',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1'
        ]
    ]);
    
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        echo "<p class='success'>✅ SUCCESS - HTTP " . $httpCode . " - Retrieved " . strlen($html) . " bytes</p>";
        echo "<pre>" . htmlspecialchars(substr($html, 0, 1000)) . "...</pre>";
    } else {
        echo "<p class='error'>❌ HTTP Error: " . $httpCode . "</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ FAILED: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 2: Direct access with file_get_contents and stream context
echo "<h2>Test 2: file_get_contents with Stream Context</h2>";
try {
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "Accept-language: en\r\n" .
                        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36\r\n" .
                        "Referer: https://www.investing.com/\r\n"
        ],
        "ssl" => [
            "verify_peer" => false,
            "verify_peer_name" => false
        ]
    ];
    
    $context = stream_context_create($opts);
    $html = @file_get_contents($url, false, $context);
    
    if ($html !== false) {
        echo "<p class='success'>✅ SUCCESS - Retrieved " . strlen($html) . " bytes</p>";
        echo "<pre>" . htmlspecialchars(substr($html, 0, 1000)) . "...</pre>";
    } else {
        echo "<p class='error'>❌ FAILED - No response</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ FAILED: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 3: Try with additional security headers
echo "<h2>Test 3: Enhanced Security Headers</h2>";
try {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9',
            'Accept-Encoding: gzip, deflate',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'Referer: https://www.investing.com/',
            'Origin: https://www.investing.com',
            'Sec-Fetch-Dest: iframe',
            'Sec-Fetch-Mode: navigate',
            'Sec-Fetch-Site: cross-site',
            'Connection: keep-alive'
        ],
        CURLOPT_ENCODING => 'gzip, deflate',
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile
    ]);
    
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode == 200) {
        echo "<p class='success'>✅ SUCCESS - HTTP " . $httpCode . " - Retrieved " . strlen($html) . " bytes</p>";
        echo "<pre>" . htmlspecialchars(substr($html, 0, 1000)) . "...</pre>";
    } else {
        echo "<p class='error'>❌ HTTP Error: " . $httpCode . ($error ? " - " . $error : "") . "</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ FAILED: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 4: Check what headers the site expects
echo "<h2>Test 4: Analyzing Response Headers</h2>";
try {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_HTTPHEADER => [
            'Referer: https://www.investing.com/'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    
    curl_close($ch);
    
    echo "<p class='info'>HTTP Code: " . $httpCode . "</p>";
    echo "<p class='info'>Response Headers:</p>";
    echo "<pre>" . htmlspecialchars($headers) . "</pre>";
    
    if (stripos($headers, 'cloudflare') !== false) {
        echo "<p class='error'>🛡️ Cloudflare detected</p>";
    }
    if (stripos($headers, 'set-cookie') !== false) {
        echo "<p class='info'>🍪 Cookies are being set</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ FAILED: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Cleanup
@unlink($cookieFile);

echo "<hr><p class='info'>🔍 Investigation complete. Check results above.</p>";
?>

// Test 1: Basic request
echo "<h2>Test 1: Basic Request</h2>";
try {
    $html = $scraper->fetchHtml($url);
    echo "<p class='success'>✅ SUCCESS - Retrieved " . strlen($html) . " bytes</p>";
    echo "<pre>" . htmlspecialchars(substr($html, 0, 1000)) . "...</pre>";
} catch (Exception $e) {
    echo "<p class='error'>❌ FAILED: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 2: With referer
echo "<h2>Test 2: With Referer Header</h2>";
try {
    $html = $scraper->fetchHtml($url, [
        'referer' => 'https://www.investing.com/'
    ]);
    echo "<p class='success'>✅ SUCCESS - Retrieved " . strlen($html) . " bytes</p>";
    echo "<pre>" . htmlspecialchars(substr($html, 0, 1000)) . "...</pre>";
} catch (Exception $e) {
    echo "<p class='error'>❌ FAILED: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 3: With origin header
echo "<h2>Test 3: With Origin & Referer</h2>";
try {
    $html = $scraper->fetchHtml($url, [
        'referer' => 'https://www.investing.com/',
        'headers' => [
            'Origin: https://www.investing.com',
            'Sec-Ch-Ua: "Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
            'Sec-Ch-Ua-Mobile: ?0',
            'Sec-Ch-Ua-Platform: "Windows"'
        ]
    ]);
    echo "<p class='success'>✅ SUCCESS - Retrieved " . strlen($html) . " bytes</p>";
    echo "<pre>" . htmlspecialchars(substr($html, 0, 1000)) . "...</pre>";
} catch (Exception $e) {
    echo "<p class='error'>❌ FAILED: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 4: Using cURL directly with more headers
echo "<h2>Test 4: Direct cURL with Full Browser Headers</h2>";
try {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.5',
            'Accept-Encoding: gzip, deflate',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1',
            'Cache-Control: max-age=0',
            'Referer: https://www.investing.com/'
        ],
        CURLOPT_ENCODING => 'gzip, deflate'
    ]);
    
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "<p class='error'>❌ cURL Error: " . htmlspecialchars($error) . "</p>";
    } else if ($httpCode >= 400) {
        echo "<p class='error'>❌ HTTP Error: " . $httpCode . "</p>";
    } else {
        echo "<p class='success'>✅ SUCCESS - HTTP " . $httpCode . " - Retrieved " . strlen($html) . " bytes</p>";
        echo "<pre>" . htmlspecialchars(substr($html, 0, 1000)) . "...</pre>";
        
        // Extract some useful info
        if (preg_match('/<title>(.*?)<\/title>/i', $html, $matches)) {
            echo "<p class='info'>📄 Page Title: " . htmlspecialchars($matches[1]) . "</p>";
        }
        
        // Count tables (economic calendar likely has tables)
        $tableCount = substr_count(strtolower($html), '<table');
        echo "<p class='info'>📊 Tables found: " . $tableCount . "</p>";
        
        // Look for calendar data
        if (stripos($html, 'calendar') !== false || stripos($html, 'event') !== false) {
            echo "<p class='success'>✅ Looks like calendar data is present!</p>";
        }
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ EXCEPTION: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 5: Check for JavaScript requirement
echo "<h2>Test 5: Checking for JavaScript/Dynamic Content</h2>";
try {
    $html = $scraper->fetchHtml($url, [
        'referer' => 'https://www.investing.com/'
    ]);
    
    if (stripos($html, 'javascript') !== false && stripos($html, 'document.write') !== false) {
        echo "<p class='info'>⚠️ Page may require JavaScript to load content</p>";
    }
    
    if (stripos($html, 'cloudflare') !== false) {
        echo "<p class='error'>🛡️ Cloudflare protection detected!</p>";
    }
    
    if (stripos($html, 'Just a moment') !== false || stripos($html, 'checking your browser') !== false) {
        echo "<p class='error'>🚫 Bot detection active - requires browser automation</p>";
    }
    
    // Check if it's an iframe-friendly widget
    if (strlen($html) < 5000 && (stripos($html, 'iframe') !== false || stripos($html, 'widget') !== false)) {
        echo "<p class='info'>📦 This appears to be an embeddable widget</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>Test 5 failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr><p class='info'>🔍 Investigation complete. Check results above.</p>";
?>
