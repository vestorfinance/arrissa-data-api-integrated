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

$title = 'Market Data API Guide';
$page = 'market-data-api';
ob_start();
?>

<style>
.example-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.example-card:hover {
    transform: translateY(-4px);
    border-color: var(--accent);
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
</style>

<div class="p-8 max-w-[1600px] mx-auto">
    <!-- EA Requirement Notice -->
    <div class="mb-6 p-5 rounded-2xl" style="background-color: rgba(79, 70, 229, 0.1); border: 1px solid var(--accent);">
        <div class="flex items-start">
            <div class="flex-shrink-0 mr-4">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, var(--accent), var(--success));">
                    <i data-feather="alert-circle" style="width: 20px; height: 20px; color: white;"></i>
                </div>
            </div>
            <div class="flex-1">
                <h3 class="text-base font-semibold mb-2" style="color: var(--text-primary);">MT5 Expert Advisor Required</h3>
                <p class="text-sm mb-3" style="color: var(--text-secondary);">This API requires the Market Data EA to be running on an MT5 chart. The EA serves real-time and historical OHLC candle data with optional tick volume.</p>
                <a href="/download-eas" class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium transition-colors" style="background-color: var(--accent); color: white;">
                    <i data-feather="download" class="mr-2" style="width: 16px; height: 16px;"></i>
                    Download Market Data EA
                </a>
            </div>
        </div>
    </div>

    <!-- Hero Header -->
    <div class="mb-10">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-4xl font-bold mb-3 tracking-tight" style="color: var(--text-primary);">
                    MT5 Market Data API
                    <span class="section-badge ml-3" style="background-color: var(--success); color: var(--bg-primary);">v1.4</span>
                </h1>
                <p class="text-lg" style="color: var(--text-secondary);">Comprehensive MT5 data retrieval with advanced technical indicators and volume analysis</p>
            </div>
        </div>
        
        <!-- What's New Banner -->
        <div class="p-6 rounded-2xl gradient-bg" style="border: 1px solid var(--border);">
            <div class="flex items-start">
                <div class="flex-shrink-0 mr-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background-color: var(--success);">
                        <i data-feather="zap" style="width: 24px; height: 24px; color: white;"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">What's New in v1.4</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm" style="color: var(--text-secondary);">
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span><strong style="color: var(--text-primary);">Multiple Volume Parameters:</strong> Support for candle-volume, candlevolume, and volume</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span><strong style="color: var(--text-primary);">Enhanced Validation:</strong> Better error handling for all parameter formats</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span><strong style="color: var(--text-primary);">Fixed dataField:</strong> Corrected single field output handling</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span><strong style="color: var(--text-primary);">Backward Compatible:</strong> All v1.3 calls work unchanged</span>
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
                <?php echo htmlspecialchars($baseUrl); ?>/market-data-api-v1/market-data-api.php
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
                <pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word;"><?php echo htmlspecialchars($baseUrl); ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&symbol=EURUSD&timeframe=M5&count=10&rsi=14&ema_1=e,20&candle-volume=true</pre>
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
                <p class="text-sm" style="color: var(--text-secondary);">Essential parameters for API requests</p>
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
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Your unique API key for authentication and quota tracking</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">symbol</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">string</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--success); color: white;">Always</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Trading symbol (e.g., EURUSD). Max 20 characters</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">timeframe</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">string</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--warning); color: white;">Conditional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Candle timeframe: M1, M5, M15, M30, H1, H4, D1, W1, MN1</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">count</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">integer (1-5000)</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--warning); color: white;">Conditional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Number of candles to return. Range: 1-5000</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">rangeType</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">string</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Time-based ranges: "last-hour", "today", "last-X-minutes", "future"</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">dataField</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">string</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Return only specific field: open, high, low, close, volume</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">pretend_date</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">YYYY-MM-DD</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Backtest date (requires pretend_time)</td>
                        </tr>
                        <tr>
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">pretend_time</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">HH:MM:SS</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Backtest time (requires pretend_date)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Volume Parameters Section -->
    <div class="mb-10">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, var(--success), var(--accent));">
                <i data-feather="bar-chart-2" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Volume Parameters</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Enhanced volume support with multiple parameter formats <span class="section-badge ml-2" style="background-color: var(--success); color: white;">NEW v1.4</span></p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-base font-semibold mb-4 flex items-center" style="color: var(--text-primary);">
                    <i data-feather="tag" class="mr-2" style="width: 18px; height: 18px; color: var(--accent);"></i>
                    Parameter Names
                </h3>
                <div class="space-y-3">
                    <div class="flex items-center p-3 rounded-xl" style="background-color: var(--input-bg);">
                        <code class="text-sm font-semibold" style="color: var(--accent);">candle-volume</code>
                        <span class="ml-auto text-xs" style="color: var(--text-secondary);">Primary (with hyphen)</span>
                    </div>
                    <div class="flex items-center p-3 rounded-xl" style="background-color: var(--input-bg);">
                        <code class="text-sm font-semibold" style="color: var(--accent);">candlevolume</code>
                        <span class="ml-auto text-xs" style="color: var(--text-secondary);">Alternative (no hyphen)</span>
                    </div>
                    <div class="flex items-center p-3 rounded-xl" style="background-color: var(--input-bg);">
                        <code class="text-sm font-semibold" style="color: var(--accent);">volume</code>
                        <span class="ml-auto text-xs" style="color: var(--text-secondary);">Short form</span>
                    </div>
                </div>
            </div>

            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-base font-semibold mb-4 flex items-center" style="color: var(--text-primary);">
                    <i data-feather="check-square" class="mr-2" style="width: 18px; height: 18px; color: var(--success);"></i>
                    Parameter Values
                </h3>
                <div class="space-y-3">
                    <div class="flex items-center p-3 rounded-xl" style="background-color: var(--input-bg);">
                        <code class="text-sm font-semibold" style="color: var(--success);">true</code>
                        <span class="ml-auto text-xs" style="color: var(--text-secondary);">Enable tick volume</span>
                    </div>
                    <div class="flex items-center p-3 rounded-xl" style="background-color: var(--input-bg);">
                        <code class="text-sm font-semibold" style="color: var(--danger);">false</code>
                        <span class="ml-auto text-xs" style="color: var(--text-secondary);">Disable tick volume</span>
                    </div>
                    <div class="flex items-center p-3 rounded-xl" style="background-color: var(--input-bg);">
                        <code class="text-sm font-semibold" style="color: var(--success);">1 / 0</code>
                        <span class="ml-auto text-xs" style="color: var(--text-secondary);">Alternative format</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6 rounded-2xl highlight-box" style="background-color: rgba(79, 70, 229, 0.05); border: 1px solid var(--accent);">
            <h4 class="text-sm font-semibold mb-3 flex items-center" style="color: var(--accent);">
                <i data-feather="info" class="mr-2" style="width: 16px; height: 16px;"></i>
                Volume Usage Tips
            </h4>
            <ul class="space-y-2 text-sm" style="color: var(--text-secondary);">
                <li class="flex items-start">
                    <i data-feather="chevron-right" class="mr-2 flex-shrink-0 mt-0.5" style="width: 14px; height: 14px; color: var(--accent);"></i>
                    <span>Use <code style="background-color: var(--input-bg); padding: 2px 6px; border-radius: 4px;">candle-volume=true</code> to add volume field to each candle object</span>
                </li>
                <li class="flex items-start">
                    <i data-feather="chevron-right" class="mr-2 flex-shrink-0 mt-0.5" style="width: 14px; height: 14px; color: var(--accent);"></i>
                    <span>Use <code style="background-color: var(--input-bg); padding: 2px 6px; border-radius: 4px;">dataField=volume</code> to return only volume values as array</span>
                </li>
                <li class="flex items-start">
                    <i data-feather="chevron-right" class="mr-2 flex-shrink-0 mt-0.5" style="width: 14px; height: 14px; color: var(--accent);"></i>
                    <span>All three parameter names work identically - choose based on your preference</span>
                </li>
            </ul>
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
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Live API Examples</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Click any example to test the API in real-time</p>
            </div>
        </div>

        <!-- Basic Usage Examples -->
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-5" style="color: var(--text-primary);">Basic Candle Data</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php
                $basicExamples = [
                    ['title' => 'EURUSD M5 - Last 50 Candles', 'url' => "{$baseUrl}/market-data-api-v1/market-data-api.php?api_key={$apiKey}&symbol=EURUSD&timeframe=M5&count=50", 'desc' => 'Basic OHLC candle data'],
                    ['title' => 'EURUSD M5 - With Volume', 'url' => "{$baseUrl}/market-data-api-v1/market-data-api.php?api_key={$apiKey}&symbol=EURUSD&timeframe=M5&count=50&candle-volume=true", 'desc' => 'Candles with tick volume'],
                    ['title' => 'GBPUSD H1 - Volume Only', 'url' => "{$baseUrl}/market-data-api-v1/market-data-api.php?api_key={$apiKey}&symbol=GBPUSD&timeframe=H1&count=100&dataField=volume", 'desc' => 'Volume data array'],
                    ['title' => 'USDJPY D1 - Close Prices', 'url' => "{$baseUrl}/market-data-api-v1/market-data-api.php?api_key={$apiKey}&symbol=USDJPY&timeframe=D1&count=30&dataField=close", 'desc' => 'Close prices only'],
                    ['title' => 'Last Hour - EURUSD M5', 'url' => "{$baseUrl}/market-data-api-v1/market-data-api.php?api_key={$apiKey}&symbol=EURUSD&rangeType=last-hour&timeframe=M5", 'desc' => 'Time-based range'],
                    ['title' => 'Today - USDJPY H1', 'url' => "{$baseUrl}/market-data-api-v1/market-data-api.php?api_key={$apiKey}&symbol=USDJPY&rangeType=today&timeframe=H1", 'desc' => 'Today\'s candles'],
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
                        <span>Try Example</span>
                        <i data-feather="arrow-right" class="ml-2" style="width: 14px; height: 14px;"></i>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Moving Averages Examples -->
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-5" style="color: var(--text-primary);">Moving Averages</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php
                $maExamples = [
                    ['title' => 'SMA 20 - EURUSD H1', 'url' => "{$baseUrl}/market-data-api-v1/market-data-api.php?api_key={$apiKey}&symbol=EURUSD&timeframe=H1&count=100&sma_1=20", 'desc' => 'Simple Moving Average'],
                    ['title' => 'EMA 20 with Volume', 'url' => "{$baseUrl}/market-data-api-v1/market-data-api.php?api_key={$apiKey}&symbol=GBPUSD&timeframe=H1&count=100&ema_1=20&candlevolume=true", 'desc' => 'Exponential MA + Volume'],
                    ['title' => 'Triple EMA (20,50,200)', 'url' => "{$baseUrl}/market-data-api-v1/market-data-api.php?api_key={$apiKey}&symbol=EURUSD&timeframe=H1&count=200&ema_1=20&ema_2=50&ema_3=200&candle-volume=true", 'desc' => 'Multi-period trend analysis'],
                    ['title' => 'Mixed MA Types', 'url' => "{$baseUrl}/market-data-api-v1/market-data-api.php?api_key={$apiKey}&symbol=GBPUSD&timeframe=H1&count=200&ma_1=e,20&ma_2=s,50&ma_3=l,100", 'desc' => 'EMA, SMA, LWMA combined'],
                    ['title' => 'Enhanced MA Format', 'url' => "{$baseUrl}/market-data-api-v1/market-data-api.php?api_key={$apiKey}&symbol=USDJPY&timeframe=H1&count=150&ma_1=e,20&ema_2=50&sma_1=200&volume=true", 'desc' => 'Advanced MA configuration'],
                    ['title' => 'Scalping MAs (8,21,50)', 'url' => "{$baseUrl}/market-data-api-v1/market-data-api.php?api_key={$apiKey}&symbol=AUDUSD&timeframe=M5&count=300&ema_1=8&ema_2=21&ema_3=50", 'desc' => 'Fast-period scalping setup'],
                ];
                foreach ($maExamples as $example):
                ?>
                <a href="<?php echo htmlspecialchars($example['url']); ?>" target="_blank" class="example-card block p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                    <div class="flex items-start justify-between mb-3">
                        <h4 class="text-sm font-semibold flex-1" style="color: var(--text-primary);"><?php echo $example['title']; ?></h4>
                        <i data-feather="external-link" style="width: 16px; height: 16px; color: var(--text-secondary);"></i>
                    </div>
                    <p class="text-xs mb-3" style="color: var(--text-secondary);"><?php echo $example['desc']; ?></p>
                    <div class="flex items-center text-xs font-medium" style="color: var(--accent);">
                        <span>Try Example</span>
                        <i data-feather="arrow-right" class="ml-2" style="width: 14px; height: 14px;"></i>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Oscillators Examples -->
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-5" style="color: var(--text-primary);">Oscillators & Momentum</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php
                $oscExamples = [
                    ['title' => 'RSI 14 - EURUSD H1', 'url' => "{$baseUrl}/market-data-api-v1/market-data-api.php?api_key={$apiKey}&symbol=EURUSD&timeframe=H1&count=100&rsi=14", 'desc' => 'Relative Strength Index'],
                    ['title' => 'RSI + Stochastic', 'url' => "{$baseUrl}/market-data-api-v1/market-data-api.php?api_key={$apiKey}&symbol=EURUSD&timeframe=H1&count=100&rsi=14&stoch=14,3,3", 'desc' => 'Dual oscillator setup'],
                    ['title' => 'Multiple RSI Periods', 'url' => "{$baseUrl}/market-data-api-v1/market-data-api.php?api_key={$apiKey}&symbol=GBPUSD&timeframe=H1&count=100&rsi=9&rsi1=14&rsi2=21", 'desc' => 'Fast, standard, slow RSI'],
                    ['title' => 'Stochastic 14,3,3', 'url' => "{$baseUrl}/market-data-api-v1/market-data-api.php?api_key={$apiKey}&symbol=USDJPY&timeframe=H1&count=100&stoch=14,3,3", 'desc' => 'Stochastic oscillator'],
                    ['title' => 'CCI 14 - USDCAD', 'url' => "{$baseUrl}/market-data-api-v1/market-data-api.php?api_key={$apiKey}&symbol=USDCAD&timeframe=H1&count=100&cci=14", 'desc' => 'Commodity Channel Index'],
                    ['title' => 'Williams %R 14', 'url' => "{$baseUrl}/market-data-api-v1/market-data-api.php?api_key={$apiKey}&symbol=NZDUSD&timeframe=H1&count=100&wpr=14", 'desc' => 'Williams Percent Range'],
                ];
                foreach ($oscExamples as $example):
                ?>
                <a href="<?php echo htmlspecialchars($example['url']); ?>" target="_blank" class="example-card block p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                    <div class="flex items-start justify-between mb-3">
                        <h4 class="text-sm font-semibold flex-1" style="color: var(--text-primary);"><?php echo $example['title']; ?></h4>
                        <i data-feather="external-link" style="width: 16px; height: 16px; color: var(--text-secondary);"></i>
                    </div>
                    <p class="text-xs mb-3" style="color: var(--text-secondary);"><?php echo $example['desc']; ?></p>
                    <div class="flex items-center text-xs font-medium" style="color: var(--accent);">
                        <span>Try Example</span>
                        <i data-feather="arrow-right" class="ml-2" style="width: 14px; height: 14px;"></i>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Volatility Indicators -->
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-5" style="color: var(--text-primary);">Volatility Indicators</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php
                $volExamples = [
                    ['title' => 'Bollinger Bands 20,2.0', 'url' => "{$baseUrl}/market-data-api-v1/market-data-api.php?api_key={$apiKey}&symbol=EURUSD&timeframe=H1&count=100&bb=20,0,2.0", 'desc' => 'Standard BB configuration'],
                    ['title' => 'Multiple BB (2.0, 1.5)', 'url' => "{$baseUrl}/market-data-api-v1/market-data-api.php?api_key={$apiKey}&symbol=EURUSD&timeframe=H1&count=100&bb=20,0,2.0&bb1=20,0,1.5", 'desc' => 'Dual Bollinger Bands'],
                    ['title' => 'ATR 14 - USDJPY', 'url' => "{$baseUrl}/market-data-api-v1/market-data-api.php?api_key={$apiKey}&symbol=USDJPY&timeframe=H1&count=100&atr=14", 'desc' => 'Average True Range'],
                    ['title' => 'Multiple ATR (7,14,21)', 'url' => "{$baseUrl}/market-data-api-v1/market-data-api.php?api_key={$apiKey}&symbol=GBPUSD&timeframe=H1&count=100&atr=7&atr1=14&atr2=21", 'desc' => 'Multi-period ATR'],
                    ['title' => 'Volatility Suite', 'url' => "{$baseUrl}/market-data-api-v1/market-data-api.php?api_key={$apiKey}&symbol=USDJPY&timeframe=H1&count=100&bb=20,0,2.0&atr=14&stddev=20", 'desc' => 'BB + ATR + StdDev'],
                    ['title' => 'BB + Envelopes + Volume', 'url' => "{$baseUrl}/market-data-api-v1/market-data-api.php?api_key={$apiKey}&symbol=AUDUSD&timeframe=H1&count=100&bb=20,0,2.0&envelopes=14,0.1&volume=true", 'desc' => 'Complete volatility analysis'],
                ];
                foreach ($volExamples as $example):
                ?>
                <a href="<?php echo htmlspecialchars($example['url']); ?>" target="_blank" class="example-card block p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                    <div class="flex items-start justify-between mb-3">
                        <h4 class="text-sm font-semibold flex-1" style="color: var(--text-primary);"><?php echo $example['title']; ?></h4>
                        <i data-feather="external-link" style="width: 16px; height: 16px; color: var(--text-secondary);"></i>
                    </div>
                    <p class="text-xs mb-3" style="color: var(--text-secondary);"><?php echo $example['desc']; ?></p>
                    <div class="flex items-center text-xs font-medium" style="color: var(--accent);">
                        <span>Try Example</span>
                        <i data-feather="arrow-right" class="ml-2" style="width: 14px; height: 14px;"></i>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Trading Strategies -->
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-5" style="color: var(--text-primary);">Complete Trading Strategies</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php
                $strategyExamples = [
                    ['title' => 'Golden Cross Strategy', 'subtitle' => 'EURUSD H1', 'url' => "{$baseUrl}/market-data-api-v1/market-data-api.php?api_key={$apiKey}&symbol=EURUSD&timeframe=H1&count=200&ema_1=20&ema_2=50&ema_3=200&atr=14&candle-volume=true", 'desc' => 'EMA 20>50>200 alignment + ATR for stops + Volume confirmation'],
                    ['title' => 'RSI Divergence Setup', 'subtitle' => 'EURUSD M15', 'url' => "{$baseUrl}/market-data-api-v1/market-data-api.php?api_key={$apiKey}&symbol=EURUSD&timeframe=M15&count=200&rsi=14&rsi1=9&bb=20,0,2.0&momentum=14&atr=14&candlevolume=true", 'desc' => 'Dual RSI divergence + BB extremes + Momentum + Volume'],
                    ['title' => 'Volatility Breakout', 'subtitle' => 'EURUSD M5', 'url' => "{$baseUrl}/market-data-api-v1/market-data-api.php?api_key={$apiKey}&symbol=EURUSD&timeframe=M5&count=300&bb=20,0,2.0&bb1=20,0,1.5&atr=14&atr1=7&momentum=10&candle-volume=true", 'desc' => 'Dual BB breakout + ATR expansion + Volume spike + Momentum'],
                    ['title' => 'EMA Scalping with Volume', 'subtitle' => 'EURUSD M1', 'url' => "{$baseUrl}/market-data-api-v1/market-data-api.php?api_key={$apiKey}&symbol=EURUSD&timeframe=M1&count=500&ema_1=8&ema_2=21&rsi=9&bb=20,0,1.5&atr=7&candle-volume=true", 'desc' => 'Fast EMA cross + RSI 9 + Tight BB + Fast ATR + Volume'],
                    ['title' => 'Bitcoin Trend Analysis', 'subtitle' => 'BTCUSD H1', 'url' => "{$baseUrl}/market-data-api-v1/market-data-api.php?api_key={$apiKey}&symbol=BTCUSD&timeframe=H1&count=200&ema_1=20&ema_2=50&rsi=14&bb=20,0,2.0&atr=14&candle-volume=true", 'desc' => 'EMA trend + RSI momentum + BB volatility + Volume analysis'],
                    ['title' => 'Ichimoku Complete', 'subtitle' => 'USDJPY H4', 'url' => "{$baseUrl}/market-data-api-v1/market-data-api.php?api_key={$apiKey}&symbol=USDJPY&timeframe=H4&count=200&ichimoku=9,26,52&atr=14&rsi=14", 'desc' => 'Full Ichimoku analysis + RSI momentum + ATR volatility'],
                ];
                foreach ($strategyExamples as $example):
                ?>
                <a href="<?php echo htmlspecialchars($example['url']); ?>" target="_blank" class="example-card block p-6 rounded-2xl" style="background: linear-gradient(135deg, rgba(79, 70, 229, 0.05) 0%, rgba(16, 185, 129, 0.05) 100%); border: 1px solid var(--border);">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <div class="flex items-center mb-2">
                                <h4 class="text-base font-bold" style="color: var(--text-primary);"><?php echo $example['title']; ?></h4>
                                <span class="ml-2 section-badge" style="background-color: var(--accent); color: white;">STRATEGY</span>
                            </div>
                            <p class="text-xs font-medium mb-2" style="color: var(--accent);"><?php echo $example['subtitle']; ?></p>
                        </div>
                        <i data-feather="external-link" style="width: 18px; height: 18px; color: var(--text-secondary);"></i>
                    </div>
                    <p class="text-sm mb-4 leading-relaxed" style="color: var(--text-secondary);"><?php echo $example['desc']; ?></p>
                    <div class="flex items-center text-sm font-semibold" style="color: var(--accent);">
                        <span>View Strategy</span>
                        <i data-feather="arrow-right" class="ml-2" style="width: 16px; height: 16px;"></i>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Best Practices Section -->
    <div class="mb-10">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, var(--success), var(--accent));">
                <i data-feather="check-circle" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Best Practices</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Optimize your API usage for best results</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-base font-semibold mb-4 flex items-center" style="color: var(--text-primary);">
                    <i data-feather="zap" class="mr-2" style="width: 18px; height: 18px; color: var(--accent);"></i>
                    Performance Tips
                </h3>
                <ul class="space-y-3 text-sm" style="color: var(--text-secondary);">
                    <li class="flex items-start">
                        <i data-feather="chevron-right" class="mr-2 flex-shrink-0 mt-0.5" style="width: 14px; height: 14px; color: var(--accent);"></i>
                        <span>Use direct URL parameters for better performance</span>
                    </li>
                    <li class="flex items-start">
                        <i data-feather="chevron-right" class="mr-2 flex-shrink-0 mt-0.5" style="width: 14px; height: 14px; color: var(--accent);"></i>
                        <span>Maximum 30 indicators per request for optimal performance</span>
                    </li>
                    <li class="flex items-start">
                        <i data-feather="chevron-right" class="mr-2 flex-shrink-0 mt-0.5" style="width: 14px; height: 14px; color: var(--accent);"></i>
                        <span>API validates all parameters - check error messages</span>
                    </li>
                </ul>
            </div>

            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-base font-semibold mb-4 flex items-center" style="color: var(--text-primary);">
                    <i data-feather="trending-up" class="mr-2" style="width: 18px; height: 18px; color: var(--success);"></i>
                    Strategy Development
                </h3>
                <ul class="space-y-3 text-sm" style="color: var(--text-secondary);">
                    <li class="flex items-start">
                        <i data-feather="chevron-right" class="mr-2 flex-shrink-0 mt-0.5" style="width: 14px; height: 14px; color: var(--success);"></i>
                        <span>Combine complementary indicators (trend + momentum + volatility)</span>
                    </li>
                    <li class="flex items-start">
                        <i data-feather="chevron-right" class="mr-2 flex-shrink-0 mt-0.5" style="width: 14px; height: 14px; color: var(--success);"></i>
                        <span>Use pretend_date/time for accurate backtesting</span>
                    </li>
                    <li class="flex items-start">
                        <i data-feather="chevron-right" class="mr-2 flex-shrink-0 mt-0.5" style="width: 14px; height: 14px; color: var(--success);"></i>
                        <span>Adjust indicator periods to match your timeframe</span>
                    </li>
                </ul>
            </div>

            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-base font-semibold mb-4 flex items-center" style="color: var(--text-primary);">
                    <i data-feather="bar-chart-2" class="mr-2" style="width: 18px; height: 18px; color: var(--warning);"></i>
                    Volume Analysis
                </h3>
                <ul class="space-y-3 text-sm" style="color: var(--text-secondary);">
                    <li class="flex items-start">
                        <i data-feather="chevron-right" class="mr-2 flex-shrink-0 mt-0.5" style="width: 14px; height: 14px; color: var(--warning);"></i>
                        <span>Use any volume parameter name - all work identically</span>
                    </li>
                    <li class="flex items-start">
                        <i data-feather="chevron-right" class="mr-2 flex-shrink-0 mt-0.5" style="width: 14px; height: 14px; color: var(--warning);"></i>
                        <span><code>dataField=volume</code> for arrays, <code>candle-volume=true</code> for objects</span>
                    </li>
                    <li class="flex items-start">
                        <i data-feather="chevron-right" class="mr-2 flex-shrink-0 mt-0.5" style="width: 14px; height: 14px; color: var(--warning);"></i>
                        <span>Combine volume with momentum indicators for confirmation</span>
                    </li>
                </ul>
            </div>

            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-base font-semibold mb-4 flex items-center" style="color: var(--text-primary);">
                    <i data-feather="layers" class="mr-2" style="width: 18px; height: 18px; color: var(--danger);"></i>
                    Advanced Usage
                </h3>
                <ul class="space-y-3 text-sm" style="color: var(--text-secondary);">
                    <li class="flex items-start">
                        <i data-feather="chevron-right" class="mr-2 flex-shrink-0 mt-0.5" style="width: 14px; height: 14px; color: var(--danger);"></i>
                        <span>Use numbered suffixes for multiple indicators: <code>rsi=14&rsi1=9</code></span>
                    </li>
                    <li class="flex items-start">
                        <i data-feather="chevron-right" class="mr-2 flex-shrink-0 mt-0.5" style="width: 14px; height: 14px; color: var(--danger);"></i>
                        <span>Enhanced MAs: <code>ma_1=e,20&ma_2=s,50</code> for precise control</span>
                    </li>
                    <li class="flex items-start">
                        <i data-feather="chevron-right" class="mr-2 flex-shrink-0 mt-0.5" style="width: 14px; height: 14px; color: var(--danger);"></i>
                        <span>Dynamic ranges: <code>last-X-minutes</code> where X is 1-1440</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Footer Note -->
    <div class="p-8 rounded-2xl text-center" style="background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(16, 185, 129, 0.1) 100%); border: 1px solid var(--border);">
        <div class="mb-4">
            <span class="section-badge" style="background-color: var(--accent); color: white;">API v1.4</span>
        </div>
        <h3 class="text-lg font-semibold mb-2" style="color: var(--text-primary);">Ready to Start Trading?</h3>
        <p class="text-sm mb-4" style="color: var(--text-secondary);">All examples use your configured API key and endpoint. Click any example above to test in real-time.</p>
        <div class="text-xs" style="color: var(--text-secondary);">
            <p class="mb-1"><strong style="color: var(--text-primary);">New Features:</strong> Multiple volume parameter names, enhanced validation, fixed dataField support, improved parameter detection</p>
            <p><strong style="color: var(--text-primary);">Compatibility:</strong> Fully backward compatible with v1.3, v1.2, and v1.1</p>
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
