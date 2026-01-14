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

$title = 'Similar Scene API Guide';
$page = 'similar-scene-api-guide';
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
    background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(236, 72, 153, 0.1) 100%);
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
.timeframe-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    margin: 2px;
}
</style>

<div class="p-8 max-w-[1600px] mx-auto">
    <!-- Hero Header -->
    <div class="mb-10">
        <div class="flex items-center justify-between mb-4">
            <div class="flex-1">
                <h1 class="text-4xl font-bold mb-3 tracking-tight" style="color: var(--text-primary);">
                    Similar Scene API
                    <span class="section-badge ml-3" style="background: linear-gradient(135deg, #8b5cf6, #ec4899); color: white;">v1.0</span>
                </h1>
                <p class="text-lg" style="color: var(--text-secondary);">Historical event pattern matching with synchronized multi-timeframe market data for backtesting and analysis</p>
            </div>
            <div>
                <a href="/event-id-reference" class="inline-flex items-center px-6 py-3 rounded-full font-semibold transition-all" style="background: linear-gradient(135deg, #8b5cf6, #ec4899); color: white; text-decoration: none; box-shadow: 0 4px 6px rgba(139, 92, 246, 0.3);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 16px rgba(139, 92, 246, 0.4)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px rgba(139, 92, 246, 0.3)';">
                    <i data-feather="list" style="width: 18px; height: 18px; margin-right: 8px;"></i>
                    Event ID Reference
                </a>
            </div>
        </div>
        
        <!-- What's New Banner -->
        <div class="p-6 rounded-2xl gradient-bg" style="border: 1px solid var(--border);">
            <div class="flex items-start">
                <div class="flex-shrink-0 mr-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background: linear-gradient(135deg, #8b5cf6, #ec4899);">
                        <i data-feather="layers" style="width: 24px; height: 24px; color: white;"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">Key Features</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm" style="color: var(--text-secondary);">
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #8b5cf6;"></i>
                            <span><strong style="color: var(--text-primary);">Pattern Matching:</strong> Find historical occurrences of event combinations</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #8b5cf6;"></i>
                            <span><strong style="color: var(--text-primary);">Multi-Timeframe Data:</strong> M1, M30, H1, H4, D1 candles synchronized</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #8b5cf6;"></i>
                            <span><strong style="color: var(--text-primary);">Market Data API Integration:</strong> Real MT5 data via queue system</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #8b5cf6;"></i>
                            <span><strong style="color: var(--text-primary);">Symbol Flexibility:</strong> Any trading instrument (XAUUSD, EURUSD, US30, etc.)</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #8b5cf6;"></i>
                            <span><strong style="color: var(--text-primary);">ML Training Ready:</strong> Perfect for machine learning datasets</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #8b5cf6;"></i>
                            <span><strong style="color: var(--text-primary);">Backtesting Support:</strong> Historical market reactions to events</span>
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
                <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3" style="background-color: #8b5cf6; opacity: 0.2;">
                    <i data-feather="link" style="width: 20px; height: 20px; color: #8b5cf6;"></i>
                </div>
                <h3 class="text-lg font-semibold" style="color: var(--text-primary);">API Endpoint</h3>
            </div>
            <p class="text-xs uppercase tracking-wider mb-2" style="color: var(--text-secondary);">Base URL</p>
            <div class="p-4 rounded-xl api-code break-all" style="background-color: var(--input-bg); color: #8b5cf6; border: 1px solid var(--input-border);">
                <?php echo htmlspecialchars($baseUrl); ?>/news-api-v1/similar-scene-api.php
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

    <!-- How It Works Section -->
    <div class="mb-10">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, #8b5cf6, #ec4899);">
                <i data-feather="info" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">How It Works</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Understanding the similar scene matching algorithm</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="p-6 rounded-2xl example-card" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="w-12 h-12 rounded-full flex items-center justify-center mb-4" style="background-color: #8b5cf6; opacity: 0.2;">
                    <div class="text-2xl font-bold" style="color: #8b5cf6;">1</div>
                </div>
                <h3 class="text-lg font-semibold mb-2" style="color: var(--text-primary);">Event Matching</h3>
                <p class="text-sm" style="color: var(--text-secondary);">Searches historical database for dates/times where ALL specified event IDs occurred simultaneously (e.g., CPI and Core CPI released together)</p>
            </div>

            <div class="p-6 rounded-2xl example-card" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="w-12 h-12 rounded-full flex items-center justify-center mb-4" style="background-color: #ec4899; opacity: 0.2;">
                    <div class="text-2xl font-bold" style="color: #ec4899;">2</div>
                </div>
                <h3 class="text-lg font-semibold mb-2" style="color: var(--text-primary);">Market Data Fetch</h3>
                <p class="text-sm" style="color: var(--text-secondary);">For each occurrence, fetches synchronized market data via Market Data API: 6-minute window (T-1 to T+5) plus specific candles at M30, H1, H4, and D1</p>
            </div>

            <div class="p-6 rounded-2xl example-card" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="w-12 h-12 rounded-full flex items-center justify-center mb-4" style="background-color: #10b981; opacity: 0.2;">
                    <div class="text-2xl font-bold" style="color: #10b981;">3</div>
                </div>
                <h3 class="text-lg font-semibold mb-2" style="color: var(--text-primary);">Response Assembly</h3>
                <p class="text-sm" style="color: var(--text-secondary);">Returns structured JSON with event details and market OHLC data, ready for analysis, backtesting, or machine learning training</p>
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
                <pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word;"><?php echo htmlspecialchars($baseUrl); ?>/news-api-v1/similar-scene-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&event_id=CPI_US,CORECPI_US&symbol=XAUUSD</pre>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Core Parameters Section -->
    <div class="mb-10">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, #8b5cf6, #ec4899);">
                <i data-feather="sliders" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Request Parameters</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Configure your historical scene query</p>
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
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: #8b5cf6; color: white;">api_key</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">string</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--success); color: white;">Yes</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Your unique API key for authentication</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: #8b5cf6; color: white;">event_id</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">string</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--success); color: white;">Yes</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Comma-separated event IDs to match (e.g., CPI_US,CORECPI_US). Returns only occurrences where ALL IDs appeared together</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">symbol</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">string</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Trading symbol for market data (default: XAUUSD). Supports EURUSD, GBPUSD, US30, etc.</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">period</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">string</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Predefined time range (today, last-month, last-3-months, etc.)</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">start_date</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">YYYY-MM-DD</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Custom range start date (requires start_time, end_date, end_time)</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">currency</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">string</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Filter economic events by currency (e.g., USD, EUR, GBP)</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">display</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">string</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Set to "min" for minimal output (essential fields only)</td>
                        </tr>
                        <tr>
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">output</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">string</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Set to "all" to include ALL events at occurrence time (not just specified IDs)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Market Data Windows Section -->
    <div class="mb-10">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, #10b981, #06b6d4);">
                <i data-feather="bar-chart" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Market Data Windows</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Multi-timeframe candle data synchronized to each event occurrence</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4">
            <!-- Main Window -->
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 2px solid #8b5cf6;">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center">
                        <span class="timeframe-badge" style="background-color: #8b5cf6; color: white;">M1</span>
                        <h3 class="text-lg font-semibold ml-3" style="color: var(--text-primary);">Main Window</h3>
                    </div>
                    <code class="text-xs font-semibold" style="color: #8b5cf6;">{symbol}_data</code>
                </div>
                <p class="text-sm" style="color: var(--text-secondary);">1-minute candles from T-1 minute to T+5 minutes (6-minute window around event time). Captures immediate market reaction.</p>
            </div>

            <!-- Additional Candles -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                    <span class="timeframe-badge mb-3" style="background-color: #10b981; color: white;">M30</span>
                    <h4 class="font-semibold mb-2 mt-2" style="color: var(--text-primary);">30-Minute</h4>
                    <p class="text-xs mb-2" style="color: var(--text-secondary);">Candle 30 minutes after event</p>
                    <code class="text-xs" style="color: #10b981;">candle_30min</code>
                </div>

                <div class="p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                    <span class="timeframe-badge mb-3" style="background-color: #3b82f6; color: white;">H1</span>
                    <h4 class="font-semibold mb-2 mt-2" style="color: var(--text-primary);">1-Hour</h4>
                    <p class="text-xs mb-2" style="color: var(--text-secondary);">Candle 1 hour after event</p>
                    <code class="text-xs" style="color: #3b82f6;">candle_1h</code>
                </div>

                <div class="p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                    <span class="timeframe-badge mb-3" style="background-color: #f59e0b; color: white;">H4</span>
                    <h4 class="font-semibold mb-2 mt-2" style="color: var(--text-primary);">4-Hour</h4>
                    <p class="text-xs mb-2" style="color: var(--text-secondary);">Candle 4 hours after event</p>
                    <code class="text-xs" style="color: #f59e0b;">candle_4h</code>
                </div>

                <div class="p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                    <span class="timeframe-badge mb-3" style="background-color: #ec4899; color: white;">D1</span>
                    <h4 class="font-semibold mb-2 mt-2" style="color: var(--text-primary);">Daily</h4>
                    <p class="text-xs mb-2" style="color: var(--text-secondary);">Close-of-day candle</p>
                    <code class="text-xs" style="color: #ec4899;">candle_close_day</code>
                </div>
            </div>
        </div>

        <div class="mt-6 p-6 rounded-2xl highlight-box" style="background-color: rgba(139, 92, 246, 0.05); border: 1px solid #8b5cf6;">
            <div class="flex items-start">
                <i data-feather="info" class="mr-3 flex-shrink-0" style="width: 20px; height: 20px; color: #8b5cf6;"></i>
                <div>
                    <p class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Market Data Source</p>
                    <p class="text-sm" style="color: var(--text-secondary);">All candles are fetched from the Market Data API (queue-based MT5 integration). Each candle includes: <strong>date, time, open, high, low, close</strong></p>
                </div>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Example Requests Section -->
    <div class="mb-10">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, #06b6d4, #0ea5e9);">
                <i data-feather="code" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Example Requests</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Common usage scenarios and patterns</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4">
            <!-- Example 1 -->
            <div class="p-6 rounded-2xl example-card" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-base font-semibold" style="color: var(--text-primary);">
                        <span class="section-badge mr-2" style="background-color: #8b5cf6; color: white;">1</span>
                        Average Hourly Earnings (MoM) Pattern
                    </h3>
                    <a href="<?php echo htmlspecialchars($baseUrl); ?>/news-api-v1/similar-scene-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&event_id=ZBEYU" target="_blank" class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-semibold transition-all" style="background-color: #8b5cf6; color: white; text-decoration: none;" onmouseover="this.style.backgroundColor='#7c3aed';" onmouseout="this.style.backgroundColor='#8b5cf6';">
                        <i data-feather="external-link" style="width: 14px; height: 14px; margin-right: 6px;"></i>
                        Try it
                    </a>
                </div>
                <p class="text-sm mb-3" style="color: var(--text-secondary);">Find historical occurrences of Average Hourly Earnings (MoM) releases with XAUUSD market reactions</p>
                <div class="p-4 rounded-xl api-code text-xs overflow-x-auto" style="background-color: var(--bg-primary); border: 1px solid var(--input-border);">
                    <pre style="margin: 0; color: var(--text-secondary);"><?php echo htmlspecialchars($baseUrl); ?>/news-api-v1/similar-scene-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&event_id=ZBEYU</pre>
                </div>
            </div>

            <!-- Example 2 -->
            <div class="p-6 rounded-2xl example-card" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-base font-semibold" style="color: var(--text-primary);">
                        <span class="section-badge mr-2" style="background-color: #10b981; color: white;">2</span>
                        CB Consumer Confidence with EURUSD
                    </h3>
                    <a href="<?php echo htmlspecialchars($baseUrl); ?>/news-api-v1/similar-scene-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&event_id=RIHAG&symbol=EURUSD" target="_blank" class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-semibold transition-all" style="background-color: #10b981; color: white; text-decoration: none;" onmouseover="this.style.backgroundColor='#059669';" onmouseout="this.style.backgroundColor='#10b981';">
                        <i data-feather="external-link" style="width: 14px; height: 14px; margin-right: 6px;"></i>
                        Try it
                    </a>
                </div>
                <p class="text-sm mb-3" style="color: var(--text-secondary);">Analyze Consumer Confidence releases with EUR/USD pair reactions</p>
                <div class="p-4 rounded-xl api-code text-xs overflow-x-auto" style="background-color: var(--bg-primary); border: 1px solid var(--input-border);">
                    <pre style="margin: 0; color: var(--text-secondary);"><?php echo htmlspecialchars($baseUrl); ?>/news-api-v1/similar-scene-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&event_id=RIHAG&symbol=EURUSD</pre>
                </div>
            </div>

            <!-- Example 3 -->
            <div class="p-6 rounded-2xl example-card" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-base font-semibold" style="color: var(--text-primary);">
                        <span class="section-badge mr-2" style="background-color: #ec4899; color: white;">3</span>
                        Building Permits + Business Inventories
                    </h3>
                    <a href="<?php echo htmlspecialchars($baseUrl); ?>/news-api-v1/similar-scene-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&event_id=ISCRG,JCGGJ&display=min" target="_blank" class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-semibold transition-all" style="background-color: #ec4899; color: white; text-decoration: none;" onmouseover="this.style.backgroundColor='#db2777';" onmouseout="this.style.backgroundColor='#ec4899';">
                        <i data-feather="external-link" style="width: 14px; height: 14px; margin-right: 6px;"></i>
                        Try it
                    </a>
                </div>
                <p class="text-sm mb-3" style="color: var(--text-secondary);">Multi-event pattern: Find occurrences where Building Permits and Business Inventories released together</p>
                <div class="p-4 rounded-xl api-code text-xs overflow-x-auto" style="background-color: var(--bg-primary); border: 1px solid var(--input-border);">
                    <pre style="margin: 0; color: var(--text-secondary);"><?php echo htmlspecialchars($baseUrl); ?>/news-api-v1/similar-scene-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&event_id=ISCRG,JCGGJ&display=min</pre>
                </div>
            </div>

            <!-- Example 4 -->
            <div class="p-6 rounded-2xl example-card" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-base font-semibold" style="color: var(--text-primary);">
                        <span class="section-badge mr-2" style="background-color: #f59e0b; color: white;">4</span>
                        Beige Book with US30 Index
                    </h3>
                    <a href="<?php echo htmlspecialchars($baseUrl); ?>/news-api-v1/similar-scene-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&event_id=FLPVV&symbol=US30" target="_blank" class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-semibold transition-all" style="background-color: #f59e0b; color: white; text-decoration: none;" onmouseover="this.style.backgroundColor='#d97706';" onmouseout="this.style.backgroundColor='#f59e0b';">
                        <i data-feather="external-link" style="width: 14px; height: 14px; margin-right: 6px;"></i>
                        Try it
                    </a>
                </div>
                <p class="text-sm mb-3" style="color: var(--text-secondary);">Dow Jones (US30) reactions to Federal Reserve Beige Book releases</p>
                <div class="p-4 rounded-xl api-code text-xs overflow-x-auto" style="background-color: var(--bg-primary); border: 1px solid var(--input-border);">
                    <pre style="margin: 0; color: var(--text-secondary);"><?php echo htmlspecialchars($baseUrl); ?>/news-api-v1/similar-scene-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&event_id=FLPVV&symbol=US30</pre>
                </div>
            </div>

            <!-- Example 5 -->
            <div class="p-6 rounded-2xl example-card" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-base font-semibold" style="color: var(--text-primary);">
                        <span class="section-badge mr-2" style="background-color: #3b82f6; color: white;">5</span>
                        Crude Oil Stock with Last 3 Months
                    </h3>
                    <a href="<?php echo htmlspecialchars($baseUrl); ?>/news-api-v1/similar-scene-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&event_id=YFWBM&period=last-3-months" target="_blank" class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-semibold transition-all" style="background-color: #3b82f6; color: white; text-decoration: none;" onmouseover="this.style.backgroundColor='#2563eb';" onmouseout="this.style.backgroundColor='#3b82f6';">
                        <i data-feather="external-link" style="width: 14px; height: 14px; margin-right: 6px;"></i>
                        Try it
                    </a>
                </div>
                <p class="text-sm mb-3" style="color: var(--text-secondary);">API Weekly Crude Oil Stock releases from the last 3 months with period filter</p>
                <div class="p-4 rounded-xl api-code text-xs overflow-x-auto" style="background-color: var(--bg-primary); border: 1px solid var(--input-border);">
                    <pre style="margin: 0; color: var(--text-secondary);"><?php echo htmlspecialchars($baseUrl); ?>/news-api-v1/similar-scene-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&event_id=YFWBM&period=last-3-months</pre>
                </div>
            </div>

            <!-- Example 6 -->
            <div class="p-6 rounded-2xl example-card" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-base font-semibold" style="color: var(--text-primary);">
                        <span class="section-badge mr-2" style="background-color: #06b6d4; color: white;">6</span>
                        Atlanta Fed GDPNow Custom Range
                    </h3>
                    <a href="<?php echo htmlspecialchars($baseUrl); ?>/news-api-v1/similar-scene-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&event_id=OMHBG&start_date=2025-01-01&start_time=00:00:00&end_date=2025-12-31&end_time=23:59:59" target="_blank" class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-semibold transition-all" style="background-color: #06b6d4; color: white; text-decoration: none;" onmouseover="this.style.backgroundColor='#0891b2';" onmouseout="this.style.backgroundColor='#06b6d4';">
                        <i data-feather="external-link" style="width: 14px; height: 14px; margin-right: 6px;"></i>
                        Try it
                    </a>
                </div>
                <p class="text-sm mb-3" style="color: var(--text-secondary);">Atlanta Fed GDPNow forecasts for 2025 with custom date range filter</p>
                <div class="p-4 rounded-xl api-code text-xs overflow-x-auto" style="background-color: var(--bg-primary); border: 1px solid var(--input-border);">
                    <pre style="margin: 0; color: var(--text-secondary);"><?php echo htmlspecialchars($baseUrl); ?>/news-api-v1/similar-scene-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&event_id=OMHBG&start_date=2025-01-01&start_time=00:00:00&end_date=2025-12-31&end_time=23:59:59</pre>
                </div>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Response Structure Section -->
    <div class="mb-10">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, #10b981, #06b6d4);">
                <i data-feather="file-text" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Response Structure</h2>
                <p class="text-sm" style="color: var(--text-secondary);">JSON format with occurrence-based grouping</p>
            </div>
        </div>

        <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <p class="text-sm mb-4 font-medium" style="color: var(--text-primary);">Sample Response (display=min):</p>
            <div class="p-5 rounded-xl api-code text-xs overflow-x-auto" style="background-color: var(--bg-primary); color: var(--text-primary); border: 1px solid var(--input-border); max-height: 500px;">
                <pre style="margin: 0;">{
  "vestor_data": {
    "occurrence_1": {
      "occurrence_date": "2025-04-10",
      "occurrence_time": "12:30:00",
      "events": [
        {
          "event_name": "Core CPI (MoM) (Mar)",
          "forecast_value": "0.3",
          "actual_value": "0.1",
          "previous_value": "0.2"
        },
        {
          "event_name": "CPI (MoM) (Mar)",
          "forecast_value": "0.1",
          "actual_value": "-0.1",
          "previous_value": "0.2"
        }
      ],
      "xauusd_data": [
        {
          "time": "12:29:00",
          "open": "2034.50",
          "high": "2035.80",
          "low": "2033.20",
          "close": "2034.90"
        },
        {
          "time": "12:30:00",
          "open": "2034.90",
          "high": "2038.50",
          "low": "2034.50",
          "close": "2037.20"
        }
      ],
      "candle_30min": {
        "time": "13:00:00",
        "open": "2037.20",
        "high": "2040.10",
        "low": "2036.50",
        "close": "2039.80"
      },
      "candle_1h": {
        "time": "13:30:00",
        "open": "2039.80",
        "high": "2042.30",
        "low": "2038.90",
        "close": "2041.50"
      },
      "candle_4h": {
        "time": "16:30:00",
        "open": "2041.50",
        "high": "2045.70",
        "low": "2040.20",
        "close": "2044.30"
      },
      "candle_close_day": {
        "time": "23:59:00",
        "open": "2030.10",
        "high": "2045.70",
        "low": "2028.50",
        "close": "2043.20"
      }
    },
    "occurrence_2": {
      ...
    }
  }
}</pre>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Use Cases Section -->
    <div class="mb-10">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, #ec4899, #ef4444);">
                <i data-feather="target" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Use Cases</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Real-world applications for this API</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="p-6 rounded-2xl example-card" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3" style="background-color: #8b5cf6; opacity: 0.2;">
                        <i data-feather="trending-up" style="width: 20px; height: 20px; color: #8b5cf6;"></i>
                    </div>
                    <h3 class="text-lg font-semibold" style="color: var(--text-primary);">Pattern Analysis</h3>
                </div>
                <p class="text-sm" style="color: var(--text-secondary);">Study how markets historically react to specific event combinations. Compare immediate reactions (M1), short-term trends (M30, H1), and daily closes.</p>
            </div>

            <div class="p-6 rounded-2xl example-card" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3" style="background-color: #10b981; opacity: 0.2;">
                        <i data-feather="cpu" style="width: 20px; height: 20px; color: #10b981;"></i>
                    </div>
                    <h3 class="text-lg font-semibold" style="color: var(--text-primary);">ML Training Data</h3>
                </div>
                <p class="text-sm" style="color: var(--text-secondary);">Build machine learning datasets with labeled event-market pairs. Perfect for training predictive models on economic event outcomes.</p>
            </div>

            <div class="p-6 rounded-2xl example-card" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3" style="background-color: #3b82f6; opacity: 0.2;">
                        <i data-feather="activity" style="width: 20px; height: 20px; color: #3b82f6;"></i>
                    </div>
                    <h3 class="text-lg font-semibold" style="color: var(--text-primary);">Backtesting Strategies</h3>
                </div>
                <p class="text-sm" style="color: var(--text-secondary);">Test trading strategies against historical scenarios. Evaluate if your strategy would have profited from previous event-driven market movements.</p>
            </div>

            <div class="p-6 rounded-2xl example-card" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3" style="background-color: #ec4899; opacity: 0.2;">
                        <i data-feather="git-merge" style="width: 20px; height: 20px; color: #ec4899;"></i>
                    </div>
                    <h3 class="text-lg font-semibold" style="color: var(--text-primary);">Event Correlation</h3>
                </div>
                <p class="text-sm" style="color: var(--text-secondary);">Find and analyze simultaneous event releases. Understand combined market impact when multiple high-importance events occur together.</p>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Error Responses Section -->
    <div class="mb-10">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                <i data-feather="alert-circle" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Error Responses</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Common error messages and troubleshooting</p>
            </div>
        </div>

        <div class="space-y-3">
            <div class="p-4 rounded-xl flex items-start" style="background-color: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444;">
                <code class="flex-1 text-xs api-code" style="color: #ef4444;">{"vestor_data":{"error":"Missing API key."}}</code>
                <span class="ml-4 text-xs" style="color: var(--text-secondary);">No api_key parameter provided</span>
            </div>
            <div class="p-4 rounded-xl flex items-start" style="background-color: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444;">
                <code class="flex-1 text-xs api-code" style="color: #ef4444;">{"vestor_data":{"error":"Invalid API key."}}</code>
                <span class="ml-4 text-xs" style="color: var(--text-secondary);">Incorrect api_key value</span>
            </div>
            <div class="p-4 rounded-xl flex items-start" style="background-color: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444;">
                <code class="flex-1 text-xs api-code" style="color: #ef4444;">{"vestor_data":{"error":"Missing required parameter: event_id"}}</code>
                <span class="ml-4 text-xs" style="color: var(--text-secondary);">No event_id specified</span>
            </div>
            <div class="p-4 rounded-xl flex items-start" style="background-color: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444;">
                <code class="flex-1 text-xs api-code" style="color: #ef4444;">{"vestor_data":{"error":"Failed to fetch market data","http_code":500}}</code>
                <span class="ml-4 text-xs" style="color: var(--text-secondary);">Market Data API error</span>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Related APIs Footer -->
    <div class="p-6 rounded-2xl" style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.05), rgba(236, 72, 153, 0.05)); border: 1px solid var(--border);">
        <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">Related APIs</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <a href="/market-data-api-guide" class="flex items-center p-3 rounded-xl transition-all" style="background-color: var(--card-bg); color: var(--text-secondary); text-decoration: none;" onmouseover="this.style.backgroundColor='var(--bg-secondary)';" onmouseout="this.style.backgroundColor='var(--card-bg)';">
                <i data-feather="trending-up" class="mr-3" style="width: 18px; height: 18px; color: var(--accent);"></i>
                <div>
                    <div class="font-semibold" style="color: var(--text-primary);">Market Data API</div>
                    <div class="text-xs">Direct OHLC candle access</div>
                </div>
            </a>
            <a href="/news-api-guide" class="flex items-center p-3 rounded-xl transition-all" style="background-color: var(--card-bg); color: var(--text-secondary); text-decoration: none;" onmouseover="this.style.backgroundColor='var(--bg-secondary)';" onmouseout="this.style.backgroundColor='var(--card-bg)';">
                <i data-feather="file-text" class="mr-3" style="width: 18px; height: 18px; color: var(--accent);"></i>
                <div>
                    <div class="font-semibold" style="color: var(--text-primary);">News API</div>
                    <div class="text-xs">Economic calendar events</div>
                </div>
            </a>
            <a href="/event-id-reference" class="flex items-center p-3 rounded-xl transition-all" style="background-color: var(--card-bg); color: var(--text-secondary); text-decoration: none;" onmouseover="this.style.backgroundColor='var(--bg-secondary)';" onmouseout="this.style.backgroundColor='var(--card-bg)';">
                <i data-feather="list" class="mr-3" style="width: 18px; height: 18px; color: var(--accent);"></i>
                <div>
                    <div class="font-semibold" style="color: var(--text-primary);">Event ID Reference</div>
                    <div class="text-xs">All consistent event IDs</div>
                </div>
            </a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/app.php';
?>
