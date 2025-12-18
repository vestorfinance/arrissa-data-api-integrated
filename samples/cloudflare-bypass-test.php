<?php
require_once __DIR__ . '/../app/CloudflareScraper.php';

$url = 'https://sslecal2.forexprostools.com/';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Cloudflare Bypass Test</title>";
echo "<style>
body { font-family: 'Courier New', monospace; background: #1a1a1a; color: #0f0; padding: 20px; }
h1 { color: #0ff; border-bottom: 2px solid #0ff; padding-bottom: 10px; }
h2 { color: #ff0; margin-top: 30px; }
.success { background: #002200; border-left: 4px solid #0f0; padding: 15px; margin: 10px 0; }
.error { background: #220000; border-left: 4px solid #f00; padding: 15px; margin: 10px 0; color: #f00; }
.info { background: #222200; border-left: 4px solid #ff0; padding: 15px; margin: 10px 0; color: #ff0; }
pre { background: #000; border: 1px solid #0f0; padding: 15px; overflow-x: auto; color: #0f0; }
.stat { display: inline-block; margin-right: 20px; padding: 10px; background: #003300; border-radius: 5px; }
</style></head><body>";

echo "<h1>🛡️ Cloudflare Bypass Attempt - Pure PHP</h1>";
echo "<p class='info'>Target: <strong>" . htmlspecialchars($url) . "</strong></p>";

$scraper = new CloudflareScraper();

// Method 1: Direct access with all Client Hints
echo "<h2>Method 1: Direct Access with Full Client Hints</h2>";
try {
    $html = $scraper->fetch($url);
    echo "<div class='success'>";
    echo "✅ <strong>SUCCESS!</strong><br>";
    echo "<div class='stat'>Size: " . number_format(strlen($html)) . " bytes</div>";
    echo "<div class='stat'>Has &lt;table&gt;: " . (stripos($html, '<table') !== false ? 'Yes' : 'No') . "</div>";
    echo "<div class='stat'>Has calendar: " . (stripos($html, 'calendar') !== false ? 'Yes' : 'No') . "</div>";
    echo "</div>";
    echo "<p><strong>First 1500 characters:</strong></p>";
    echo "<pre>" . htmlspecialchars(substr($html, 0, 1500)) . "...</pre>";
} catch (Exception $e) {
    echo "<div class='error'>❌ " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Method 2: Visit investing.com first, then target
echo "<h2>Method 2: Two-Step with Referer Chain</h2>";
$scraper->clearCookies();
try {
    $html = $scraper->fetchWithReferer($url, 'https://www.investing.com/economic-calendar/');
    echo "<div class='success'>";
    echo "✅ <strong>SUCCESS!</strong><br>";
    echo "<div class='stat'>Size: " . number_format(strlen($html)) . " bytes</div>";
    echo "<div class='stat'>Has &lt;table&gt;: " . (stripos($html, '<table') !== false ? 'Yes' : 'No') . "</div>";
    echo "<div class='stat'>Has calendar: " . (stripos($html, 'calendar') !== false ? 'Yes' : 'No') . "</div>";
    echo "</div>";
    echo "<p><strong>First 1500 characters:</strong></p>";
    echo "<pre>" . htmlspecialchars(substr($html, 0, 1500)) . "...</pre>";
} catch (Exception $e) {
    echo "<div class='error'>❌ " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Method 3: Using HTTP/1.1 instead of HTTP/2
echo "<h2>Method 3: Alternative - Check if Cloudflare Challenge Page</h2>";
try {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false, // Don't follow redirects
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html',
            'Referer: https://www.investing.com/'
        ],
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
    ]);
    
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<div class='info'>";
    echo "HTTP Code: $httpCode<br>";
    
    if (stripos($html, 'cloudflare') !== false) {
        echo "🛡️ Cloudflare detected in response<br>";
    }
    if (stripos($html, 'challenge') !== false || stripos($html, 'checking your browser') !== false) {
        echo "⚠️ Cloudflare JavaScript challenge required<br>";
        echo "<strong>This site REQUIRES a real browser with JavaScript to bypass Cloudflare.</strong><br>";
    }
    if (stripos($html, 'captcha') !== false) {
        echo "🔒 CAPTCHA protection detected<br>";
    }
    echo "</div>";
    
    echo "<p><strong>Response preview:</strong></p>";
    echo "<pre>" . htmlspecialchars(substr($html, 0, 1500)) . "...</pre>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Conclusion
echo "<hr>";
echo "<h2>📊 Conclusion</h2>";
echo "<div class='info'>";
echo "<strong>The site uses Cloudflare's JavaScript challenge.</strong><br><br>";
echo "Pure PHP solutions:<br>";
echo "1. ❌ Cannot bypass Cloudflare JS challenge<br>";
echo "2. ✅ Can scrape if Cloudflare whitelists server IP<br>";
echo "3. ✅ Can use Cloudflare-scraper libraries that simulate browsers<br><br>";
echo "<strong>Recommended solutions:</strong><br>";
echo "• Use a headless browser (Selenium, Puppeteer via PHP exec)<br>";
echo "• Use a scraping service (ScraperAPI, Bright Data)<br>";
echo "• Contact site owner for API access<br>";
echo "• Use browser automation with PHP (symfony/panther)<br>";
echo "</div>";

echo "</body></html>";
?>
