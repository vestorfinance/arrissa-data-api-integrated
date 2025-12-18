<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Scraper Demo</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        h1 {
            font-size: 2rem;
            margin-bottom: 30px;
            color: #60a5fa;
        }
        .form-card {
            background: #1e293b;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid #334155;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #cbd5e1;
            font-weight: 500;
        }
        input[type="text"], select {
            width: 100%;
            padding: 12px 16px;
            background: #0f172a;
            border: 1px solid #475569;
            border-radius: 8px;
            color: #e2e8f0;
            font-size: 14px;
        }
        input[type="text"]:focus, select:focus {
            outline: none;
            border-color: #60a5fa;
        }
        .btn {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            padding: 12px 32px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
        }
        .results {
            background: #1e293b;
            border-radius: 12px;
            padding: 30px;
            border: 1px solid #334155;
            margin-top: 30px;
        }
        .result-section {
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid #334155;
        }
        .result-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .result-section h3 {
            color: #60a5fa;
            margin-bottom: 15px;
            font-size: 1.25rem;
        }
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: #0f172a;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #475569;
        }
        .stat-label {
            color: #94a3b8;
            font-size: 0.875rem;
            margin-bottom: 5px;
        }
        .stat-value {
            color: #60a5fa;
            font-size: 1.5rem;
            font-weight: 700;
        }
        .list-item {
            background: #0f172a;
            padding: 12px 16px;
            margin-bottom: 10px;
            border-radius: 8px;
            border: 1px solid #475569;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .list-item a {
            color: #60a5fa;
            text-decoration: none;
            flex: 1;
            margin-right: 10px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .list-item a:hover {
            color: #93c5fd;
        }
        .badge {
            background: #4f46e5;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        pre {
            background: #0f172a;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            border: 1px solid #475569;
            color: #94a3b8;
            font-size: 0.875rem;
            line-height: 1.6;
        }
        .error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid #ef4444;
            color: #fca5a5;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid #10b981;
            color: #6ee7b7;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 768px) {
            .grid-2 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🕷️ Web Scraper Tool</h1>
        
        <div class="form-card">
            <form method="POST">
                <div class="form-group">
                    <label>Enter URL to Scrape:</label>
                    <input type="text" name="url" value="<?php echo htmlspecialchars($_POST['url'] ?? 'https://news.ycombinator.com'); ?>" placeholder="https://example.com" required>
                </div>
                
                <div class="grid-2">
                    <div class="form-group">
                        <label>Scrape Type:</label>
                        <select name="type">
                            <option value="full">Full Analysis</option>
                            <option value="links">Links Only</option>
                            <option value="images">Images Only</option>
                            <option value="text">Text Content</option>
                            <option value="meta">Meta Data</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Timeout (seconds):</label>
                        <input type="text" name="timeout" value="<?php echo $_POST['timeout'] ?? '30'; ?>" placeholder="30">
                    </div>
                </div>
                
                <button type="submit" class="btn">🚀 Start Scraping</button>
            </form>
        </div>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['url'])) {
    require_once __DIR__ . '/../app/WebScraper.php';
    
    $url = $_POST['url'];
    $type = $_POST['type'] ?? 'full';
    $timeout = intval($_POST['timeout'] ?? 30);
    
    $scraper = new WebScraper();
    $scraper->setTimeout($timeout);
    
    try {
        $startTime = microtime(true);
        $html = $scraper->fetchHtml($url);
        $fetchTime = round((microtime(true) - $startTime) * 1000, 2);
        
        echo '<div class="results">';
        
        // Statistics
        echo '<div class="result-section">';
        echo '<h3>📊 Statistics</h3>';
        echo '<div class="stat-grid">';
        echo '<div class="stat-box"><div class="stat-label">Content Size</div><div class="stat-value">' . number_format(strlen($html)) . '</div><div class="stat-label">bytes</div></div>';
        echo '<div class="stat-box"><div class="stat-label">Fetch Time</div><div class="stat-value">' . $fetchTime . '</div><div class="stat-label">ms</div></div>';
        echo '<div class="stat-box"><div class="stat-label">Page Title</div><div class="stat-value" style="font-size: 1rem; margin-top: 5px;">' . htmlspecialchars($scraper->extractTitle($html)) . '</div></div>';
        echo '</div>';
        echo '</div>';
        
        if ($type === 'full' || $type === 'links') {
            $links = $scraper->extractLinks($html, $url);
            echo '<div class="result-section">';
            echo '<h3>🔗 Links Found (' . count($links) . ')</h3>';
            if (count($links) > 0) {
                echo '<div style="max-height: 400px; overflow-y: auto;">';
                foreach (array_slice($links, 0, 50) as $link) {
                    $text = $link['text'] ?: basename($link['url']);
                    echo '<div class="list-item">';
                    echo '<a href="' . htmlspecialchars($link['url']) . '" target="_blank">' . htmlspecialchars($text) . '</a>';
                    echo '<span class="badge">Link</span>';
                    echo '</div>';
                }
                if (count($links) > 50) {
                    echo '<div class="success">+ ' . (count($links) - 50) . ' more links (showing first 50)</div>';
                }
                echo '</div>';
            } else {
                echo '<p style="color: #94a3b8;">No links found</p>';
            }
            echo '</div>';
        }
        
        if ($type === 'full' || $type === 'images') {
            $images = $scraper->extractImages($html, $url);
            echo '<div class="result-section">';
            echo '<h3>🖼️ Images Found (' . count($images) . ')</h3>';
            if (count($images) > 0) {
                echo '<div style="max-height: 400px; overflow-y: auto;">';
                foreach (array_slice($images, 0, 30) as $img) {
                    echo '<div class="list-item">';
                    echo '<a href="' . htmlspecialchars($img['src']) . '" target="_blank">' . htmlspecialchars($img['src']) . '</a>';
                    if ($img['alt']) {
                        echo '<span style="color: #94a3b8; font-size: 0.875rem; margin-left: 10px;">' . htmlspecialchars(substr($img['alt'], 0, 50)) . '</span>';
                    }
                    echo '</div>';
                }
                if (count($images) > 30) {
                    echo '<div class="success">+ ' . (count($images) - 30) . ' more images (showing first 30)</div>';
                }
                echo '</div>';
            } else {
                echo '<p style="color: #94a3b8;">No images found</p>';
            }
            echo '</div>';
        }
        
        if ($type === 'full' || $type === 'meta') {
            $meta = $scraper->extractMetaTags($html);
            echo '<div class="result-section">';
            echo '<h3>📝 Meta Tags (' . count($meta) . ')</h3>';
            if (count($meta) > 0) {
                echo '<div style="max-height: 300px; overflow-y: auto;">';
                foreach ($meta as $name => $content) {
                    echo '<div class="list-item">';
                    echo '<strong style="color: #60a5fa;">' . htmlspecialchars($name) . ':</strong> ';
                    echo '<span style="color: #cbd5e1;">' . htmlspecialchars($content) . '</span>';
                    echo '</div>';
                }
                echo '</div>';
            } else {
                echo '<p style="color: #94a3b8;">No meta tags found</p>';
            }
            echo '</div>';
        }
        
        if ($type === 'full' || $type === 'text') {
            $headings = $scraper->extractBySelector($html, '//h1 | //h2 | //h3');
            echo '<div class="result-section">';
            echo '<h3>📄 Headings Found (' . count($headings) . ')</h3>';
            if (count($headings) > 0) {
                echo '<div style="max-height: 300px; overflow-y: auto;">';
                foreach (array_slice($headings, 0, 30) as $heading) {
                    if (trim($heading)) {
                        echo '<div class="list-item">' . htmlspecialchars($heading) . '</div>';
                    }
                }
                if (count($headings) > 30) {
                    echo '<div class="success">+ ' . (count($headings) - 30) . ' more headings (showing first 30)</div>';
                }
                echo '</div>';
            } else {
                echo '<p style="color: #94a3b8;">No headings found</p>';
            }
            echo '</div>';
        }
        
        // Raw HTML preview
        if ($type === 'full') {
            echo '<div class="result-section">';
            echo '<h3>📋 Raw HTML Preview</h3>';
            echo '<pre>' . htmlspecialchars(substr($html, 0, 2000)) . '</pre>';
            if (strlen($html) > 2000) {
                echo '<div class="success">Showing first 2000 characters of ' . number_format(strlen($html)) . ' total</div>';
            }
            echo '</div>';
        }
        
        echo '<div class="success">✅ Scraping completed successfully!</div>';
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
?>

    </div>
</body>
</html>
