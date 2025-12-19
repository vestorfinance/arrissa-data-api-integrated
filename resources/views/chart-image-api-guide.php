<?php
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Database.php';

$db = Database::getInstance();

// Get base URL from database
$stmt = $db->query("SELECT value FROM settings WHERE key = 'app_base_url'");
$result = $stmt->fetch();
$baseUrl = $result ? $result['value'] : 'http://localhost:8000';

// Get API key from database
$stmt = $db->query("SELECT value FROM settings WHERE key = 'api_key'");
$result = $stmt->fetch();
$apiKey = $result ? $result['value'] : '';

// Check if GD extension is loaded
$gdEnabled = extension_loaded('gd');

// Check if required font files exist
$fontDir = __DIR__ . '/../../chart-image-api-v1/fonts/';
$requiredFonts = ['Manrope-Regular.ttf', 'Manrope-Medium.ttf', 'Manrope-SemiBold.ttf', 'Manrope-Bold.ttf'];
$fontsExist = true;
$missingFonts = [];
foreach ($requiredFonts as $font) {
    if (!file_exists($fontDir . $font)) {
        $fontsExist = false;
        $missingFonts[] = $font;
    }
}

$title = 'Chart Image API Guide';
$page = 'chart-image-api';
ob_start();
?>

<style>
.example-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.example-card:hover {
    transform: translateY(-4px);
}
.section-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 14px;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.025em;
}
.gradient-bg {
    background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(16, 185, 129, 0.1) 100%);
}
.divider {
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--border), transparent);
    margin: 3rem 0;
}
.api-code {
    font-family: 'Fira Code', 'Consolas', monospace;
    font-size: 0.8125rem;
    line-height: 1.6;
}
.highlight-box {
    border-left: 3px solid var(--accent);
    padding-left: 1rem;
}
.chart-preview {
    border-radius: 1rem;
    overflow: hidden;
    border: 2px solid var(--border);
}
</style>

<div class="p-8 max-w-[1600px] mx-auto">
    <!-- Hero Header -->
    <div class="mb-10">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-4xl font-bold mb-3 tracking-tight" style="color: var(--text-primary);">
                    Chart Image API
                    <span class="section-badge ml-3" style="background-color: var(--success); color: var(--bg-primary);">v1.0</span>
                </h1>
                <p class="text-lg" style="color: var(--text-secondary);">Professional 16:9 candlestick charts with advanced technical analysis features</p>
            </div>
        </div>
        
        <!-- GD Extension Status -->
        <?php if ($gdEnabled): ?>
        <div class="p-6 rounded-2xl mb-6" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.1) 100%); border: 1px solid var(--success);">
            <div class="flex items-start">
                <div class="flex-shrink-0 mr-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background-color: var(--success);">
                        <i data-feather="check-circle" style="width: 24px; height: 24px; color: white;"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold mb-2" style="color: var(--success);">GD Extension Enabled</h3>
                    <p class="text-sm" style="color: var(--text-secondary);">
                        The PHP GD extension is installed and active. Chart generation is fully operational.
                    </p>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="p-6 rounded-2xl mb-6" style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%); border: 1px solid var(--danger);">
            <div class="flex items-start">
                <div class="flex-shrink-0 mr-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background-color: var(--danger);">
                        <i data-feather="alert-triangle" style="width: 24px; height: 24px; color: white;"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold mb-3" style="color: var(--danger);">GD Extension Not Found</h3>
                    <p class="text-sm mb-4" style="color: var(--text-secondary);">
                        The PHP GD extension is required for chart generation but is not currently enabled. Follow these steps to enable it:
                    </p>
                    <div class="space-y-2 text-sm" style="color: var(--text-secondary);">
                        <div class="flex items-start">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full mr-3 flex-shrink-0" style="background-color: var(--danger); color: white; font-size: 0.75rem; font-weight: 600;">1</span>
                            <span>Locate your <code style="background-color: var(--input-bg); padding: 2px 6px; border-radius: 4px;">php.ini</code> file (typically in <code style="background-color: var(--input-bg); padding: 2px 6px; border-radius: 4px;">C:\wamp64\bin\php\php8.x.xx\</code>)</span>
                        </div>
                        <div class="flex items-start">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full mr-3 flex-shrink-0" style="background-color: var(--danger); color: white; font-size: 0.75rem; font-weight: 600;">2</span>
                            <span>Find the line <code style="background-color: var(--input-bg); padding: 2px 6px; border-radius: 4px;">;extension=gd</code> (note the semicolon)</span>
                        </div>
                        <div class="flex items-start">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full mr-3 flex-shrink-0" style="background-color: var(--danger); color: white; font-size: 0.75rem; font-weight: 600;">3</span>
                            <span>Remove the semicolon to uncomment: <code style="background-color: var(--input-bg); padding: 2px 6px; border-radius: 4px;">extension=gd</code></span>
                        </div>
                        <div class="flex items-start">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full mr-3 flex-shrink-0" style="background-color: var(--danger); color: white; font-size: 0.75rem; font-weight: 600;">4</span>
                            <span>Save the file and restart your WAMP/Apache server</span>
                        </div>
                        <div class="flex items-start">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full mr-3 flex-shrink-0" style="background-color: var(--danger); color: white; font-size: 0.75rem; font-weight: 600;">5</span>
                            <span>Refresh this page to verify the extension is loaded</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Font Files Status -->
        <?php if (!$fontsExist): ?>
        <div class="p-6 rounded-2xl mb-6" style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%); border: 1px solid var(--danger);">
            <div class="flex items-start">
                <div class="flex-shrink-0 mr-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background-color: var(--danger);">
                        <i data-feather="alert-triangle" style="width: 24px; height: 24px; color: white;"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold mb-3" style="color: var(--danger);">Missing Font Files</h3>
                    <p class="text-sm mb-4" style="color: var(--text-secondary);">
                        The Chart Image API requires Manrope font files to render professional charts. The following files are missing:
                    </p>
                    <div class="mb-4">
                        <?php foreach ($missingFonts as $font): ?>
                        <div class="text-sm mb-1" style="color: var(--text-secondary);">
                            <code style="background-color: var(--input-bg); padding: 2px 8px; border-radius: 4px; color: var(--danger);"><?php echo $font; ?></code>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="space-y-2 text-sm" style="color: var(--text-secondary);">
                        <p class="font-semibold" style="color: var(--text-primary);">Installation Steps:</p>
                        <div class="flex items-start">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full mr-3 flex-shrink-0" style="background-color: var(--danger); color: white; font-size: 0.75rem; font-weight: 600;">1</span>
                            <span>Download Manrope font from <a href="https://fonts.google.com/specimen/Manrope" target="_blank" style="color: var(--accent); text-decoration: underline;">Google Fonts</a></span>
                        </div>
                        <div class="flex items-start">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full mr-3 flex-shrink-0" style="background-color: var(--danger); color: white; font-size: 0.75rem; font-weight: 600;">2</span>
                            <span>Create directory: <code style="background-color: var(--input-bg); padding: 2px 6px; border-radius: 4px;">C:\wamp64\www\chart-image-api-v1\fonts\</code></span>
                        </div>
                        <div class="flex items-start">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full mr-3 flex-shrink-0" style="background-color: var(--danger); color: white; font-size: 0.75rem; font-weight: 600;">3</span>
                            <span>Extract and copy these .ttf files: Manrope-Regular.ttf, Manrope-Medium.ttf, Manrope-SemiBold.ttf, Manrope-Bold.ttf</span>
                        </div>
                        <div class="flex items-start">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full mr-3 flex-shrink-0" style="background-color: var(--danger); color: white; font-size: 0.75rem; font-weight: 600;">4</span>
                            <span>Refresh this page to verify the fonts are loaded</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- What's New Banner -->
        <div class="p-6 rounded-2xl gradient-bg" style="border: 1px solid var(--border);">
            <div class="flex items-start">
                <div class="flex-shrink-0 mr-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background-color: var(--accent);">
                        <i data-feather="zap" style="width: 24px; height: 24px; color: white;"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">Key Features</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm" style="color: var(--text-secondary);">
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--accent);"></i>
                            <span><strong style="color: var(--text-primary);">Professional Charts:</strong> High-quality 16:9 candlestick charts</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--accent);"></i>
                            <span><strong style="color: var(--text-primary);">Technical Indicators:</strong> EMA, ATR, Fibonacci retracement</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--accent);"></i>
                            <span><strong style="color: var(--text-primary);">Period Separators:</strong> Visual markers for time periods</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--accent);"></i>
                            <span><strong style="color: var(--text-primary);">Backtesting Support:</strong> Historical chart generation</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Reference Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10">
        <!-- API Endpoint Card -->
        <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="flex items-center mb-4">
                <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3" style="background-color: var(--accent); opacity: 0.2;">
                    <i data-feather="link" style="width: 20px; height: 20px; color: var(--accent);"></i>
                </div>
                <h3 class="text-lg font-semibold" style="color: var(--text-primary);">API Endpoint</h3>
            </div>
            <p class="text-xs uppercase tracking-wider mb-2" style="color: var(--text-secondary);">Base URL</p>
            <div class="p-4 rounded-xl api-code break-all" style="background-color: var(--input-bg); color: var(--accent); border: 1px solid var(--input-border);">
                <?php echo htmlspecialchars($baseUrl); ?>/chart-image-api-v1/chart-image-api.php
            </div>
        </div>

        <!-- API Key Card -->
        <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="flex items-center mb-4">
                <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3" style="background-color: var(--success); opacity: 0.2;">
                    <i data-feather="key" style="width: 20px; height: 20px; color: var(--success);"></i>
                </div>
                <h3 class="text-lg font-semibold" style="color: var(--text-primary);">Your API Key</h3>
            </div>
            <p class="text-xs uppercase tracking-wider mb-2" style="color: var(--text-secondary);">Current Key</p>
            <div class="p-4 rounded-xl api-code break-all" style="background-color: var(--input-bg); color: var(--success); border: 1px solid var(--input-border);">
                <?php echo htmlspecialchars($apiKey); ?>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Authentication Section -->
    <div class="mb-10">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, var(--accent), var(--success));">
                <i data-feather="lock" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Authentication</h2>
                <p class="text-sm" style="color: var(--text-secondary);">All requests require an API key parameter</p>
            </div>
        </div>
        
        <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <p class="text-sm mb-4 font-medium" style="color: var(--text-primary);">Example Request:</p>
            <div class="p-5 rounded-xl api-code text-xs overflow-x-auto" style="background-color: var(--bg-primary); color: var(--text-primary); border: 1px solid var(--input-border);">
                <pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word;"><?php echo htmlspecialchars($baseUrl); ?>/chart-image-api-v1/chart-image-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&symbol=EURUSD&timeframe=H1</pre>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Core Parameters Section -->
    <div class="mb-10">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, var(--accent), var(--success));">
                <i data-feather="sliders" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Core Parameters</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Essential parameters for chart generation</p>
            </div>
        </div>

        <div class="rounded-2xl overflow-hidden" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr style="background-color: var(--bg-secondary); border-bottom: 2px solid var(--border);">
                            <th class="text-left py-4 px-6 font-semibold" style="color: var(--text-primary);">Parameter</th>
                            <th class="text-left py-4 px-6 font-semibold" style="color: var(--text-primary);">Type</th>
                            <th class="text-left py-4 px-6 font-semibold" style="color: var(--text-primary);">Required</th>
                            <th class="text-left py-4 px-6 font-semibold" style="color: var(--text-primary);">Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--accent); color: white;">api_key</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">string</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--success); color: white;">Always</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Your unique API key for authentication</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">symbol</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">string</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--success); color: white;">Always</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Trading symbol (e.g., EURUSD, GBPJPY, XAUUSD)</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">timeframe</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">string</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--success); color: white;">Always</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Chart timeframe: M1, M5, M15, M30, H1, H4, D1</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">count</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">integer</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Number of candles to display (default: 100)</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">ema1_period</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">integer</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">First EMA period (orange line)</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">ema2_period</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">integer</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Second EMA period (gray line)</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">atr</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">integer</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">ATR (Average True Range) period (purple line)</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">fib</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">boolean</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Show Fibonacci retracement levels (true/false)</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">period_separators</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">string</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Comma-separated: 5M,15M,30M,1H,4H,day,week,month,year</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">high_low</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">boolean</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Show high/low lines for each period segment (true/false)</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">theme</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">string</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Chart theme: light (default) or dark (black background, white text)</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">streaming</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">string</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Streaming mode: <strong>redirect</strong> (opens streaming page) or <strong>url</strong> (returns minified URL)</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">pretend_date</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">YYYY-MM-DD</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Historical date for backtesting charts</td>
                        </tr>
                        <tr>
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">pretend_time</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">HH:MM</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Historical time for backtesting charts</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Streaming Examples Section -->
    <div class="mb-10">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                <i data-feather="activity" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Live Streaming</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Auto-refreshing charts that update every 500ms</p>
            </div>
        </div>

        <!-- Mode 1: Redirect -->
        <div class="mb-6 p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="flex items-center mb-4">
                <span class="section-badge" style="background-color: var(--accent); color: white;">Mode 1: Redirect</span>
            </div>
            <h3 class="text-lg font-semibold mb-2" style="color: var(--text-primary);">Direct Browser Redirect</h3>
            <p class="text-sm mb-4" style="color: var(--text-secondary);">Opens the live streaming page immediately in the browser.</p>
            
            <p class="text-xs font-semibold mb-2" style="color: var(--text-primary);">Example URL:</p>
            <div class="p-4 rounded-xl api-code text-xs overflow-x-auto mb-4" style="background-color: var(--bg-primary); color: var(--text-primary); border: 1px solid var(--input-border);">
                <pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word;"><?php echo htmlspecialchars($baseUrl); ?>/chart-image-api-v1/chart-image-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&symbol=EURUSD&timeframe=M1&streaming=redirect</pre>
            </div>
            
            <div class="flex items-start p-4 rounded-xl" style="background-color: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3);">
                <i data-feather="info" style="width: 16px; height: 16px; margin-right: 8px; margin-top: 2px; color: #3b82f6; flex-shrink: 0;"></i>
                <p class="text-xs" style="color: var(--text-secondary);">Use this mode when you want users to be redirected directly to the streaming page. Perfect for clickable links.</p>
            </div>
        </div>

        <!-- Mode 2: URL -->
        <div class="mb-6 p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="flex items-center mb-4">
                <span class="section-badge" style="background-color: var(--success); color: white;">Mode 2: URL</span>
            </div>
            <h3 class="text-lg font-semibold mb-2" style="color: var(--text-primary);">Get Minified Streaming URL</h3>
            <p class="text-sm mb-4" style="color: var(--text-secondary);">Returns a JSON response with a short, shareable streaming URL.</p>
            
            <p class="text-xs font-semibold mb-2" style="color: var(--text-primary);">Example Request:</p>
            <div class="p-4 rounded-xl api-code text-xs overflow-x-auto mb-3" style="background-color: var(--bg-primary); color: var(--text-primary); border: 1px solid var(--input-border);">
                <pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word;"><?php echo htmlspecialchars($baseUrl); ?>/chart-image-api-v1/chart-image-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&symbol=EURUSD&timeframe=M1&streaming=url</pre>
            </div>
            
            <p class="text-xs font-semibold mb-2" style="color: var(--text-primary);">Example Response:</p>
            <div class="p-4 rounded-xl api-code text-xs overflow-x-auto mb-4" style="background-color: var(--bg-primary); color: var(--success); border: 1px solid var(--input-border);">
                <pre style="margin: 0;">{
  "stream": true,
  "url": "<?php echo htmlspecialchars($baseUrl); ?>/chart-image-api-v1/s/a3f7b2c1",
  "message": "Chart streaming enabled. Access the URL to view live updates."
}</pre>
            </div>
            
            <div class="flex items-start p-4 rounded-xl" style="background-color: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3);">
                <i data-feather="info" style="width: 16px; height: 16px; margin-right: 8px; margin-top: 2px; color: #10b981; flex-shrink: 0;"></i>
                <p class="text-xs" style="color: var(--text-secondary);">Use this mode for API integrations where you need to store or share the streaming URL. The URL is minified for easy sharing.</p>
            </div>
        </div>

        <!-- Streaming Features -->
        <div class="p-6 rounded-2xl" style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%); border: 1px solid var(--border);">
            <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">Streaming Features</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex items-start">
                    <i data-feather="zap" style="width: 16px; height: 16px; margin-right: 8px; margin-top: 2px; color: var(--accent); flex-shrink: 0;"></i>
                    <div>
                        <p class="text-sm font-semibold" style="color: var(--text-primary);">Real-time Updates</p>
                        <p class="text-xs" style="color: var(--text-secondary);">Charts refresh every 500ms automatically</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <i data-feather="pause-circle" style="width: 16px; height: 16px; margin-right: 8px; margin-top: 2px; color: var(--accent); flex-shrink: 0;"></i>
                    <div>
                        <p class="text-sm font-semibold" style="color: var(--text-primary);">Pause/Resume Controls</p>
                        <p class="text-xs" style="color: var(--text-secondary);">Control streaming with buttons or spacebar</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <i data-feather="maximize" style="width: 16px; height: 16px; margin-right: 8px; margin-top: 2px; color: var(--accent); flex-shrink: 0;"></i>
                    <div>
                        <p class="text-sm font-semibold" style="color: var(--text-primary);">Fullscreen Mode</p>
                        <p class="text-xs" style="color: var(--text-secondary);">View charts in immersive fullscreen</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <i data-feather="bar-chart-2" style="width: 16px; height: 16px; margin-right: 8px; margin-top: 2px; color: var(--accent); flex-shrink: 0;"></i>
                    <div>
                        <p class="text-sm font-semibold" style="color: var(--text-primary);">Update Counter</p>
                        <p class="text-xs" style="color: var(--text-secondary);">Track total number of chart refreshes</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Live Examples Section -->
    <div class="mb-10">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, var(--accent), var(--success));">
                <i data-feather="code" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Live Chart Examples</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Click any example to view the generated chart</p>
            </div>
        </div>

        <!-- Basic Charts -->
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-5" style="color: var(--text-primary);">Basic Charts</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php
                $basicExamples = [
                    ['title' => 'EURUSD H1', 'url' => "{$baseUrl}/chart-image-api-v1/chart-image-api.php?api_key={$apiKey}&symbol=EURUSD&timeframe=H1", 'desc' => 'Basic 1-hour chart'],
                    ['title' => 'GBPJPY M15', 'url' => "{$baseUrl}/chart-image-api-v1/chart-image-api.php?api_key={$apiKey}&symbol=GBPJPY&timeframe=M15", 'desc' => '15-minute timeframe'],
                    ['title' => 'XAUUSD H4', 'url' => "{$baseUrl}/chart-image-api-v1/chart-image-api.php?api_key={$apiKey}&symbol=XAUUSD&timeframe=H4", 'desc' => 'Gold 4-hour chart'],
                ];
                foreach ($basicExamples as $example):
                ?>
                <a href="<?php echo htmlspecialchars($example['url']); ?>" target="_blank" class="example-card block p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                    <div class="flex items-start justify-between mb-3">
                        <h4 class="text-sm font-semibold flex-1" style="color: var(--text-primary);"><?php echo $example['title']; ?></h4>
                        <i data-feather="external-link" style="width: 16px; height: 16px; color: var(--text-secondary);"></i>
                    </div>
                    <p class="text-xs mb-3" style="color: var(--text-secondary);"><?php echo $example['desc']; ?></p>
                    <div class="flex items-center text-xs font-medium" style="color: var(--accent);">
                        <span>View Chart</span>
                        <i data-feather="arrow-right" class="ml-2" style="width: 14px; height: 14px;"></i>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Technical Analysis -->
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-5" style="color: var(--text-primary);">With Technical Indicators</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php
                $technicalExamples = [
                    ['title' => 'EURUSD with EMAs', 'url' => "{$baseUrl}/chart-image-api-v1/chart-image-api.php?api_key={$apiKey}&symbol=EURUSD&timeframe=H1&ema1_period=20&ema2_period=50", 'desc' => 'Dual EMA overlay'],
                    ['title' => 'GBPUSD with Fibonacci', 'url' => "{$baseUrl}/chart-image-api-v1/chart-image-api.php?api_key={$apiKey}&symbol=GBPUSD&timeframe=H4&fib=true", 'desc' => 'Fibonacci retracement levels'],
                    ['title' => 'USDJPY with ATR', 'url' => "{$baseUrl}/chart-image-api-v1/chart-image-api.php?api_key={$apiKey}&symbol=USDJPY&timeframe=H1&atr=14", 'desc' => 'Average True Range indicator'],
                    ['title' => 'Complete Analysis', 'url' => "{$baseUrl}/chart-image-api-v1/chart-image-api.php?api_key={$apiKey}&symbol=EURUSD&timeframe=H4&ema1_period=20&ema2_period=50&atr=14&fib=true", 'desc' => 'EMAs, ATR, and Fibonacci'],
                    ['title' => 'With Period Separators', 'url' => "{$baseUrl}/chart-image-api-v1/chart-image-api.php?api_key={$apiKey}&symbol=EURUSD&timeframe=M15&period_separators=1H,4H", 'desc' => 'Hour and 4-hour markers'],
                    ['title' => 'High/Low Markers', 'url' => "{$baseUrl}/chart-image-api-v1/chart-image-api.php?api_key={$apiKey}&symbol=GBPUSD&timeframe=H1&high_low=true&period_separators=day", 'desc' => 'Period high/low lines'],
                    ['title' => 'Dark Theme', 'url' => "{$baseUrl}/chart-image-api-v1/chart-image-api.php?api_key={$apiKey}&symbol=EURUSD&timeframe=H1&theme=dark&ema1_period=20&ema2_period=50", 'desc' => 'Black background theme'],
                    ['title' => 'Live Streaming (Direct)', 'url' => "{$baseUrl}/chart-image-api-v1/chart-image-api.php?api_key={$apiKey}&symbol=GBPUSD&timeframe=M1&streaming=redirect", 'desc' => 'Opens streaming page directly'],
                    ['title' => 'Live Streaming (URL)', 'url' => "{$baseUrl}/chart-image-api-v1/chart-image-api.php?api_key={$apiKey}&symbol=GBPUSD&timeframe=M1&streaming=url", 'desc' => 'Returns minified URL'],
                ];
                foreach ($technicalExamples as $example):
                ?>
                <a href="<?php echo htmlspecialchars($example['url']); ?>" target="_blank" class="example-card block p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                    <div class="flex items-start justify-between mb-3">
                        <h4 class="text-sm font-semibold flex-1" style="color: var(--text-primary);"><?php echo $example['title']; ?></h4>
                        <i data-feather="external-link" style="width: 16px; height: 16px; color: var(--text-secondary);"></i>
                    </div>
                    <p class="text-xs mb-3" style="color: var(--text-secondary);"><?php echo $example['desc']; ?></p>
                    <div class="flex items-center text-xs font-medium" style="color: var(--accent);">
                        <span>View Chart</span>
                        <i data-feather="arrow-right" class="ml-2" style="width: 14px; height: 14px;"></i>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Footer Note -->
    <div class="p-8 rounded-2xl text-center" style="background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(16, 185, 129, 0.1) 100%); border: 1px solid var(--border);">
        <div class="mb-4">
            <span class="section-badge" style="background-color: var(--accent); color: white;">API v1.0</span>
        </div>
        <h3 class="text-lg font-semibold mb-2" style="color: var(--text-primary);">Ready to Generate Charts?</h3>
        <p class="text-sm mb-4" style="color: var(--text-secondary);">All examples use your configured API key and endpoint. Click any example above to view the generated chart.</p>
        <div class="text-xs" style="color: var(--text-secondary);">
            <p><strong style="color: var(--text-primary);">Features:</strong> 16:9 aspect ratio, professional styling, EMA/ATR indicators, Fibonacci levels, period separators, live streaming (500ms refresh), dark/light themes, backtesting support</p>
        </div>
    </div>
</div>

<script>
    feather.replace();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layouts/app.php';
?>
