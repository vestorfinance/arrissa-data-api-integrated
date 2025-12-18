<?php
require_once __DIR__ . '/../app/WebScraper.php';

// Example usage of the WebScraper class

$scraper = new WebScraper();

// Configure scraper settings
$scraper->setTimeout(30)
        ->setFollowRedirects(true);

// Example 1: Fetch HTML content from a URL
try {
    echo "<h2>Example 1: Fetch HTML Content</h2>";
    
    $url = 'https://httpbin.org/html';
    $html = $scraper->fetchHtml($url);
    
    echo "<pre>";
    echo "Retrieved " . strlen($html) . " bytes of HTML content\n";
    echo "First 500 characters:\n";
    echo htmlspecialchars(substr($html, 0, 500));
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Example 2: Extract page title
try {
    echo "<h2>Example 2: Extract Page Title</h2>";
    
    $url = 'https://httpbin.org/html';
    $html = $scraper->fetchHtml($url);
    $title = $scraper->extractTitle($html);
    
    echo "<p><strong>Page Title:</strong> " . htmlspecialchars($title) . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Example 3: Extract all links from a page
try {
    echo "<h2>Example 3: Extract All Links</h2>";
    
    $url = 'https://httpbin.org/links/10';
    $html = $scraper->fetchHtml($url);
    $links = $scraper->extractLinks($html, $url);
    
    echo "<p>Found " . count($links) . " links:</p>";
    echo "<ul>";
    foreach (array_slice($links, 0, 10) as $link) {
        echo "<li><a href='" . htmlspecialchars($link['url']) . "' target='_blank'>" . 
             htmlspecialchars($link['text'] ?: $link['url']) . "</a></li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Example 4: Extract all images
try {
    echo "<h2>Example 4: Extract All Images</h2>";
    
    $url = 'https://httpbin.org/html';
    $html = $scraper->fetchHtml($url);
    $images = $scraper->extractImages($html, $url);
    
    echo "<p>Found " . count($images) . " images:</p>";
    echo "<ul>";
    foreach (array_slice($images, 0, 10) as $img) {
        echo "<li>" . htmlspecialchars($img['src']);
        if ($img['alt']) {
            echo " - Alt: " . htmlspecialchars($img['alt']);
        }
        echo "</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Example 5: Extract meta tags
try {
    echo "<h2>Example 5: Extract Meta Tags</h2>";
    
    $url = 'https://httpbin.org/html';
    $html = $scraper->fetchHtml($url);
    $metas = $scraper->extractMetaTags($html);
    
    echo "<p>Found " . count($metas) . " meta tags:</p>";
    echo "<ul>";
    foreach (array_slice($metas, 0, 10, true) as $name => $content) {
        echo "<li><strong>" . htmlspecialchars($name) . ":</strong> " . 
             htmlspecialchars($content) . "</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Example 6: Extract specific elements using XPath
try {
    echo "<h2>Example 6: Extract Specific Elements (XPath)</h2>";
    
    $url = 'https://httpbin.org/html';
    $html = $scraper->fetchHtml($url);
    
    // Extract all h1 headings
    $h1s = $scraper->extractBySelector($html, '//h1');
    
    echo "<p>Found " . count($h1s) . " H1 headings:</p>";
    echo "<ul>";
    foreach ($h1s as $heading) {
        echo "<li>" . htmlspecialchars($heading) . "</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Example 7: Fetch JSON from API
try {
    echo "<h2>Example 7: Fetch JSON Data</h2>";
    
    $url = 'https://jsonplaceholder.typicode.com/posts/1';
    $data = $scraper->fetchJson($url);
    
    echo "<p>JSON Response:</p>";
    echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Example 8: POST request
try {
    echo "<h2>Example 8: POST Request</h2>";
    
    $url = 'https://jsonplaceholder.typicode.com/posts';
    $postData = [
        'title' => 'Test Post',
        'body' => 'This is a test post',
        'userId' => 1
    ];
    
    $response = $scraper->fetchHtml($url, [
        'post_data' => http_build_query($postData),
        'headers' => [
            'Content-Type: application/x-www-form-urlencoded'
        ]
    ]);
    
    echo "<p>POST Response:</p>";
    echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Example 9: Custom headers and referer
try {
    echo "<h2>Example 9: Custom Headers & Referer</h2>";
    
    $url = 'https://httpbin.org/headers';
    $html = $scraper->fetchHtml($url, [
        'referer' => 'https://google.com',
        'headers' => [
            'X-Custom-Header: CustomValue'
        ]
    ]);
    
    echo "<p>Successfully fetched page with custom headers</p>";
    echo "<p>Content length: " . strlen($html) . " bytes</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Example 10: Download file
try {
    echo "<h2>Example 10: Download File</h2>";
    
    $url = 'https://example.com/image.jpg';
    $savePath = __DIR__ . '/downloaded_image.jpg';
    
    // Note: This will only work if the URL is valid
    // $scraper->downloadFile($url, $savePath);
    
    echo "<p>File download example (commented out)</p>";
    echo "<p>Usage: \$scraper->downloadFile('URL', '/path/to/save/file.ext');</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background: #f5f5f5;
}
h2 {
    color: #333;
    border-bottom: 2px solid #4f46e5;
    padding-bottom: 10px;
}
pre {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    overflow-x: auto;
}
ul {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px 40px;
}
li {
    margin: 5px 0;
}
hr {
    margin: 30px 0;
    border: none;
    border-top: 1px solid #ddd;
}
</style>
