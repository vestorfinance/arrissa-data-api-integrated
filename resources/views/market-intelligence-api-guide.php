<?php
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Database.php';

$db = Database::getInstance();

$stmt = $db->query("SELECT value FROM settings WHERE key = 'app_base_url'");
$result = $stmt->fetch();
$baseUrl = $result ? $result['value'] : 'http://localhost:8000';

$stmt = $db->query("SELECT value FROM settings WHERE key = 'api_key'");
$result = $stmt->fetch();
$apiKey = $result ? $result['value'] : '';

$title = 'Market Intelligence API Guide';
$page  = 'market-intelligence-api-guide';
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
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(14, 165, 233, 0.1) 100%);
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
    border-left: 3px solid #10B981;
    padding-left: 1rem;
}
.response-preview {
    background-color: var(--input-bg);
    border: 1px solid var(--input-border);
    border-radius: 16px;
    padding: 1.5rem;
    font-family: 'Fira Code', monospace;
    font-size: 0.8125rem;
    overflow-x: auto;
    max-height: 520px;
    overflow-y: auto;
}
.payload-field-badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 9999px;
    font-size: 0.7rem;
    font-weight: 700;
    background: linear-gradient(135deg, #10B981, #0EA5E9);
    color: white;
}
.method-badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 9999px;
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.05em;
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}
</style>

<div class="p-8 max-w-[1600px] mx-auto">

    <!-- EA Requirement Notice -->
    <div class="mb-6 p-5 rounded-2xl" style="background-color: rgba(16, 185, 129, 0.1); border: 1px solid #10B981;">
        <div class="flex items-start">
            <div class="flex-shrink-0 mr-4">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, #10B981, #0EA5E9);">
                    <i data-feather="alert-circle" style="width: 20px; height: 20px; color: white;"></i>
                </div>
            </div>
            <div class="flex-1">
                <h3 class="text-base font-semibold mb-2" style="color: var(--text-primary);">MT5 Expert Advisor Required</h3>
                <p class="text-sm mb-3" style="color: var(--text-secondary);">This API requires the <strong style="color: var(--text-primary);">Market Intelligence API EA</strong> to be running on an MT5 chart. The EA polls every second and runs the full market intelligence analysis on any requested symbol and timeframe — returning a structured markdown report and all underlying numeric data. No trade signals are generated.</p>
                <a href="/download-eas" class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium transition-colors" style="background: linear-gradient(135deg, #10B981, #0EA5E9); color: white;">
                    <i data-feather="download" class="mr-2" style="width: 16px; height: 16px;"></i>
                    Download Market Intelligence EA
                </a>
            </div>
        </div>
    </div>

    <!-- Hero Header -->
    <div class="mb-10">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-4xl font-bold mb-3 tracking-tight" style="color: var(--text-primary);">
                    Market Intelligence API
                    <span class="section-badge ml-3" style="background: linear-gradient(135deg, #10B981, #0EA5E9); color: white;">v1.0</span>
                </h1>
                <p class="text-lg" style="color: var(--text-secondary);">Multi-timeframe analysis (MN1 → M1) — price history, structure, volatility, seasonality and drawdown for any symbol and timeframe, on demand</p>
            </div>
        </div>

        <!-- Features Banner -->
        <div class="p-6 rounded-2xl gradient-bg" style="border: 1px solid var(--border);">
            <div class="flex items-start">
                <div class="flex-shrink-0 mr-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background: linear-gradient(135deg, #10B981, #0EA5E9);">
                        <i data-feather="bar-chart-2" style="width: 24px; height: 24px; color: white;"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">What is the Market Intelligence API?</h3>
                    <p class="text-sm mb-4" style="color: var(--text-secondary);">Send a symbol and a timeframe — the EA reads bars from MT5 and returns a full structured report for that timeframe. Pass <code>timeframe=all</code> to get all 9 timeframes in one call. It covers where price sits in its dataset range, trailing drawdown history, volatility regime, moving average alignment, candle behaviour patterns, and (for MN1) seasonal statistics. <strong style="color: var(--text-primary);">No BUY/SELL signals</strong> — only factual data observations for you to reason from.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm" style="color: var(--text-secondary);">
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #10B981;"></i>
                            <span><strong>9 Timeframes:</strong> MN1, W1, D1, H4, H1, M30, M15, M5, M1 — single call or all at once with <code>timeframe=all</code></span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #10B981;"></i>
                            <span><strong>Structured Report + Raw Data:</strong> Returns a human-readable markdown report AND a full numeric data block for programmatic use</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #10B981;"></i>
                            <span><strong>Seasonal Statistics:</strong> Historical win rate, average move, and direction bias for the current calendar month and quarter (MN1 only)</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #10B981;"></i>
                            <span><strong>Backtesting Mode:</strong> Pass <code>pretend_date</code> + <code>pretend_time</code> to re-run the analysis as it would have read at any historical moment — all timeframes supported</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payload Overview -->
    <div class="mb-8 p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
        <h3 class="text-xl font-semibold mb-6" style="color: var(--text-primary);">Response Payload Overview</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                <div class="payload-field-badge mb-3">Symbol</div>
                <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Metadata</h4>
                <ul class="text-xs space-y-1" style="color: var(--text-secondary);">
                    <li><code>payload.symbol</code></li>
                    <li><code>payload.timeframe</code></li>
                    <li><code>payload.server_time</code></li>
                    <li><code>payload.pretend_mode</code></li>
                    <li class="opacity-60 italic">or <code>payload.timeframes</code> (all mode)</li>
                </ul>
            </div>
            <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                <div class="payload-field-badge mb-3">Report</div>
                <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Text Report Sections</h4>
                <ul class="text-xs space-y-1" style="color: var(--text-secondary);">
                    <li><code>report.price_history</code></li>
                    <li><code>report.market_structure</code></li>
                    <li><code>report.range_position</code></li>
                    <li><code>report.percentile_ranking</code></li>
                    <li><code>report.drawdown</code></li>
                    <li><code>report.volatility</code></li>
                    <li><code>report.moving_averages</code></li>
                    <li><code>report.candle_behaviour</code></li>
                    <li><code>report.seasonal_month</code></li>
                    <li><code>report.seasonal_quarter</code></li>
                </ul>
            </div>
            <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                <div class="payload-field-badge mb-3">Data</div>
                <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Numeric Data Fields</h4>
                <ul class="text-xs space-y-1" style="color: var(--text-secondary);">
                    <li><code>data.current_close</code></li>
                    <li><code>data.dataset_high</code></li>
                    <li><code>data.dataset_low</code></li>
                    <li><code>data.pct_from_dataset_high</code></li>
                    <li><code>data.percentile</code></li>
                    <li><code>data.current_volatility</code></li>
                    <li><code>data.volatility_percentile</code></li>
                    <li><code>data.short_ma</code></li>
                    <li><code>data.long_ma</code></li>
                    <li><code>data.largest_dd</code></li>
                </ul>
            </div>
            <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                <div class="payload-field-badge mb-3">Output</div>
                <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Response Modes</h4>
                <ul class="text-xs space-y-1" style="color: var(--text-secondary);">
                    <li class="opacity-70">Default → <code>text/plain</code></li>
                    <li class="opacity-70">Formatted markdown report</li>
                    <li class="opacity-70">One section per heading</li>
                    <li><code>?format=json</code> → full JSON</li>
                    <li class="opacity-70">Includes report + data</li>
                    <li class="opacity-70">Machine-readable numbers</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Report Sections -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-6" style="color: var(--text-primary);">Report Sections</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">

            <?php
            $sections = [
                ['price_history',    'trending-up',    '#10B981', '#0EA5E9', 'Dataset high, dataset low, and percentage distance from each — the broadest price context across the full lookback for the requested timeframe'],
                ['market_structure', 'activity',       '#0EA5E9', '#6366F1', 'Consecutive higher-highs, higher-lows, lower-highs, and lower-lows over recent bars — trend structure for the requested timeframe'],
                ['range_position',   'maximize',       '#6366F1', '#8B5CF6', 'Where current price sits within the lookback high-to-low range, expressed as a percentage from the bottom of that range'],
                ['percentile_ranking','bar-chart-2',   '#8B5CF6', '#EC4899', 'Price percentile across the full dataset — how current price compares to every bar close in the lookback window'],
                ['drawdown',         'trending-down',  '#EF4444', '#F97316', 'Count, largest, and average peak-to-trough drawdowns in the dataset — measures the severity of past corrections for this symbol and timeframe'],
                ['volatility',       'wind',           '#F59E0B', '#10B981', 'Current bar range vs. historical average — labels the volatility regime as ELEVATED, SUPPRESSED, or NORMAL with a percentile rank'],
                ['moving_averages',  'sliders',        '#10B981', '#0EA5E9', 'Short and long SMA values (periods configurable in EA inputs) and their crossover state — momentum direction on the requested timeframe'],
                ['candle_behaviour', 'grid',           '#0EA5E9', '#6366F1', 'Bullish vs bearish candle ratio and recent directional streaks across the lookback window'],
                ['last_candle',      'check-square',   '#10B981', '#34D399', 'Open, high, low, close, and range of the most recently completed bar on the requested timeframe'],
                ['current_candle',   'loader',         '#F59E0B', '#F97316', 'Open, current high, current low, and live close of the still-forming bar on the requested timeframe'],
                ['seasonal_month',   'calendar',       '#8B5CF6', '#6366F1', 'Historical stats for this calendar month across past years — win rate, average move, and directional bias (MN1 only)'],
                ['seasonal_quarter', 'layers',         '#6366F1', '#8B5CF6', 'Historical stats for this calendar quarter across past years — win rate, average move, and directional bias (MN1 only)'],
            ];
            foreach ($sections as $sec):
            ?>
            <div class="p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-center mb-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center mr-3 flex-shrink-0" style="background: linear-gradient(135deg, <?= $sec[2] ?>, <?= $sec[3] ?>);">
                        <i data-feather="<?= $sec[1] ?>" style="width: 16px; height: 16px; color: white;"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold" style="color: var(--text-primary);"><?= $sec[0] ?></h3>
                    </div>
                </div>
                <p class="text-xs" style="color: var(--text-secondary);"><?= $sec[4] ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Numeric data fields -->
        <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <h3 class="text-base font-semibold mb-2" style="color: var(--text-primary);">Numeric Data Block</h3>
            <p class="text-sm mb-5" style="color: var(--text-secondary);">When using <code>?format=json</code>, the <code>data</code> object contains all the underlying numbers that power the text report — useful for custom display, charting, or strategy logic.</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border-left: 3px solid #10B981;">
                    <div class="flex items-center mb-2">
                        <span class="method-badge mr-2" style="background: rgba(16,185,129,0.15); color: #10B981;">Price</span>
                        <span class="text-xs" style="color: var(--text-secondary);">Dataset High / Low / Percentile</span>
                    </div>
                    <p class="text-xs" style="color: var(--text-secondary);"><code>current_close</code>, <code>dataset_high</code>, <code>dataset_low</code>, <code>pct_from_dataset_high</code>, <code>pct_from_dataset_low</code>, <code>percentile</code>, <code>candles_copied</code></p>
                </div>
                <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border-left: 3px solid #0EA5E9;">
                    <div class="flex items-center mb-2">
                        <span class="method-badge mr-2" style="background: rgba(14,165,233,0.15); color: #0EA5E9;">Volatility &amp; MA</span>
                        <span class="text-xs" style="color: var(--text-secondary);">Regime numbers</span>
                    </div>
                    <p class="text-xs" style="color: var(--text-secondary);"><code>current_volatility</code>, <code>historical_volatility</code>, <code>volatility_percentile</code>, <code>short_ma</code>, <code>long_ma</code>, <code>range_high_lb</code>, <code>range_low_lb</code>, <code>pct_in_lb_range</code></p>
                </div>
                <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border-left: 3px solid #8B5CF6;">
                    <div class="flex items-center mb-2">
                        <span class="method-badge mr-2" style="background: rgba(139,92,246,0.15); color: #8B5CF6;">Structure &amp; DD</span>
                        <span class="text-xs" style="color: var(--text-secondary);">Candles &amp; drawdown</span>
                    </div>
                    <p class="text-xs" style="color: var(--text-secondary);"><code>cons_hh</code>, <code>cons_hl</code>, <code>cons_lh</code>, <code>cons_ll</code>, <code>dd_count</code>, <code>largest_dd</code>, <code>avg_dd</code>, <code>recent_up</code>, <code>recent_down</code></p>
                </div>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- API Endpoint -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-6" style="color: var(--text-primary);">API Endpoint</h2>
        <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="mb-4">
                <label class="text-sm font-medium mb-2 block" style="color: var(--text-secondary);">Base URL</label>
                <div class="p-4 rounded-xl font-mono text-sm" style="background-color: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-primary);">
                    <?php echo $baseUrl; ?>/market-intelligence-api-v1/market-intelligence-api.php
                </div>
            </div>
            <div>
                <label class="text-sm font-medium mb-2 block" style="color: var(--text-secondary);">Your API Key</label>
                <div class="p-4 rounded-xl font-mono text-sm break-all" style="background-color: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-primary);">
                    <?php echo $apiKey ?: 'Not configured — visit Settings'; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Parameters -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-6" style="color: var(--text-primary);">API Parameters</h2>
        <div class="overflow-hidden rounded-2xl" style="border: 1px solid var(--border);">
            <table class="w-full">
                <thead style="background-color: var(--bg-secondary);">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold" style="color: var(--text-primary);">Parameter</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold" style="color: var(--text-primary);">Type</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold" style="color: var(--text-primary);">Required</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold" style="color: var(--text-primary);">Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td class="px-6 py-4"><code class="text-sm" style="color: #10B981;">symbol</code></td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">string</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--success);">&#10003; Yes</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">Trading symbol to analyze (e.g. <code>GBPUSD</code>, <code>XAUUSD</code>, <code>EURUSD</code>). Case-insensitive.</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td class="px-6 py-4"><code class="text-sm" style="color: #10B981;">api_key</code></td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">string</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--success);">&#10003; Yes</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">Your API authentication key (also accepted as <code>X-Api-Key</code> header).</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td class="px-6 py-4"><code class="text-sm" style="color: #F59E0B;">timeframe</code></td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">string</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-muted);">Optional</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">Timeframe to analyse. Single value: <code>MN1</code> (default), <code>W1</code>, <code>D1</code>, <code>H4</code>, <code>H1</code>, <code>M30</code>, <code>M15</code>, <code>M5</code>, <code>M1</code>. Pass <code>all</code> for all 9 timeframes. Pass a <strong>comma-separated list</strong> (e.g. <code>H1,M30,M15,M1</code>) to get only those timeframes in a single call. Case-insensitive.</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td class="px-6 py-4"><code class="text-sm" style="color: #F59E0B;">pretend_date</code></td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">string</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-muted);">Optional</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">Backtesting date in <code>YYYY-MM-DD</code> format (e.g. <code>2025-01-15</code>). Must be paired with <code>pretend_time</code>. The EA uses <code>iBarShift()</code> on the monthly timeframe to locate the correct historical starting bar.</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td class="px-6 py-4"><code class="text-sm" style="color: #F59E0B;">pretend_time</code></td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">string</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-muted);">Optional</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">Backtesting time in <code>HH:MM</code> format (e.g. <code>14:30</code>, broker server time). Must be paired with <code>pretend_date</code>. Response includes <code>"pretend_mode": true</code> and seasonal stats use the historical month/quarter.</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4"><code class="text-sm" style="color: #F59E0B;">format</code></td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">string</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-muted);">Optional</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">Default output is <strong>plain text</strong> — a formatted markdown report with section headings. Set to <code>json</code> to receive the full JSON payload including the numeric <code>data</code> block.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="divider"></div>

    <!-- cURL Examples -->
    <div class="mb-12">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, #10B981, #0EA5E9);">
                <i data-feather="terminal" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">cURL Examples</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Copy and paste these commands into your terminal</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6">

            <?php
            // Helper to emit a curl example block
            function curlBlock($label, $url) {
                $escaped = htmlspecialchars($url);
                echo '<div>';
                echo '<div class="flex items-center justify-between mb-2">';
                echo '<label class="text-xs font-semibold" style="color: var(--text-secondary);">' . htmlspecialchars($label) . '</label>';
                echo '<button onclick="copyToClipboard(\'' . str_replace("'", "\\'", 'curl "' . $url . '"') . '\')" class="text-xs px-2 py-1 rounded-lg transition-colors flex items-center gap-1" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);">';
                echo '<i data-feather="copy" style="width: 12px; height: 12px;"></i> Copy</button>';
                echo '</div>';
                echo '<div class="p-3 rounded-lg" style="background-color: var(--input-bg); border: 1px solid var(--input-border); font-family: \'Fira Code\', monospace; font-size: 0.75rem; overflow-x: auto;">';
                echo '<code style="color: var(--text-primary);">curl "' . $escaped . '"</code>';
                echo '</div>';
                echo '</div>';
            }
            $base = "{$baseUrl}/market-intelligence-api-v1/market-intelligence-api.php";
            $k    = $apiKey;
            ?>

            <!-- Single Timeframe — Monthly (default) -->
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">
                    <span class="method-badge mr-2" style="background: rgba(16,185,129,0.15); color: #10B981;">Default</span>
                    Single Timeframe — MN1 (monthly, default)
                </h3>
                <div class="space-y-4">
                    <?php
                    curlBlock('EUR/USD — Monthly analysis (plain text, default)',  "{$base}?symbol=EURUSD&api_key={$k}");
                    curlBlock('GBP/USD — Monthly analysis (JSON)',                  "{$base}?symbol=GBPUSD&api_key={$k}&format=json");
                    curlBlock('XAU/USD — Monthly analysis — explicit timeframe',    "{$base}?symbol=XAUUSD&timeframe=MN1&api_key={$k}");
                    ?>
                </div>
            </div>

            <!-- Single Timeframe — Lower TFs -->
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">
                    <span class="method-badge mr-2" style="background: rgba(14,165,233,0.15); color: #0EA5E9;">Timeframes</span>
                    Single Timeframe — W1 to M1
                </h3>
                <div class="space-y-4">
                    <?php
                    curlBlock('GBP/USD — Weekly analysis (W1)',           "{$base}?symbol=GBPUSD&timeframe=W1&api_key={$k}");
                    curlBlock('EUR/USD — Daily analysis (D1)',             "{$base}?symbol=EURUSD&timeframe=D1&api_key={$k}");
                    curlBlock('XAU/USD — H4 analysis (JSON)',              "{$base}?symbol=XAUUSD&timeframe=H4&api_key={$k}&format=json");
                    curlBlock('GBP/JPY — H1 analysis',                    "{$base}?symbol=GBPJPY&timeframe=H1&api_key={$k}");
                    curlBlock('EUR/USD — M30 analysis',                    "{$base}?symbol=EURUSD&timeframe=M30&api_key={$k}");
                    curlBlock('USD/JPY — M15 analysis',                    "{$base}?symbol=USDJPY&timeframe=M15&api_key={$k}");
                    curlBlock('GBP/USD — M5 analysis (JSON)',              "{$base}?symbol=GBPUSD&timeframe=M5&api_key={$k}&format=json");
                    curlBlock('EUR/USD — M1 analysis',                     "{$base}?symbol=EURUSD&timeframe=M1&api_key={$k}");
                    ?>
                </div>
            </div>

            <!-- All Timeframes in One Call -->
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-lg font-semibold mb-2" style="color: var(--text-primary);">
                    <span class="method-badge mr-2" style="background: rgba(139,92,246,0.15); color: #8B5CF6;">all</span>
                    All 9 Timeframes in One Call
                </h3>
                <p class="text-sm mb-4" style="color: var(--text-secondary);">Pass <code>timeframe=all</code> to get MN1, W1, D1, H4, H1, M30, M15, M5, and M1 in a single response. In plain-text mode each timeframe is emitted as its own report block separated by <code>---</code>. In JSON mode the payload contains a <code>timeframes</code> object keyed by timeframe name. Allow up to 45 seconds for the EA to compute all 9 analyses.</p>
                <div class="space-y-4">
                    <?php
                    curlBlock('GBP/USD — All timeframes (plain text)',  "{$base}?symbol=GBPUSD&timeframe=all&api_key={$k}");
                    curlBlock('XAU/USD — All timeframes (JSON)',         "{$base}?symbol=XAUUSD&timeframe=all&api_key={$k}&format=json");
                    curlBlock('EUR/USD — All timeframes at historical date', "{$base}?symbol=EURUSD&timeframe=all&pretend_date=2025-01-15&pretend_time=14:30&api_key={$k}");
                    ?>
                </div>
            </div>

            <!-- Comma-separated subset -->
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-lg font-semibold mb-2" style="color: var(--text-primary);">
                    <span class="method-badge mr-2" style="background: rgba(16,185,129,0.15); color: #10B981;">Multi-TF</span>
                    Comma-Separated Timeframe Subset
                </h3>
                <p class="text-sm mb-4" style="color: var(--text-secondary);">Pass a comma-separated list to get only the timeframes you need in a single call — no need to fetch all 9. The response uses the same <code>timeframes</code> object structure as <code>timeframe=all</code>, but only contains the requested keys.</p>
                <div class="space-y-4">
                    <?php
                    curlBlock('GBP/USD — Intraday TFs only (H1, M30, M15, M1)',    "{$base}?symbol=GBPUSD&timeframe=H1,M30,M15,M1&api_key={$k}");
                    curlBlock('EUR/USD — HTF context (MN1, W1, D1)',                "{$base}?symbol=EURUSD&timeframe=MN1,W1,D1&api_key={$k}");
                    curlBlock('XAU/USD — Scalping TFs (M5, M1) in JSON',           "{$base}?symbol=XAUUSD&timeframe=M5,M1&api_key={$k}&format=json");
                    curlBlock('GBP/JPY — Mixed session pack (H4, H1, M30)',         "{$base}?symbol=GBPJPY&timeframe=H4,H1,M30&api_key={$k}");
                    ?>
                </div>
            </div>

            <!-- Backtesting / Pretend Mode -->
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-lg font-semibold mb-2" style="color: var(--text-primary);">
                    <span class="method-badge mr-2" style="background: rgba(245,158,11,0.15); color: #F59E0B;">Backtesting</span>
                    Pretend Date &amp; Time
                </h3>
                <p class="text-sm mb-4" style="color: var(--text-secondary);">Add <code>pretend_date</code> and <code>pretend_time</code> to run the analysis at any historical moment. The EA calls <code>iBarShift()</code> per timeframe to locate the correct bar offset — all report sections including seasonality reflect what existed at that point in history.</p>
                <div class="space-y-4">
                    <?php
                    curlBlock('XAUUSD — Monthly at 2025-01-15 14:30',              "{$base}?symbol=XAUUSD&timeframe=MN1&pretend_date=2025-01-15&pretend_time=14:30&api_key={$k}");
                    curlBlock('GBPUSD — H4 at 2025-03-01 09:00',                   "{$base}?symbol=GBPUSD&timeframe=H4&pretend_date=2025-03-01&pretend_time=09:00&api_key={$k}");
                    curlBlock('EURUSD — All TFs at 2024-11-05 (US Election)',       "{$base}?symbol=EURUSD&timeframe=all&pretend_date=2024-11-05&pretend_time=14:00&api_key={$k}");
                    curlBlock('GBPUSD — Intraday subset at 2025-06-01 08:00',       "{$base}?symbol=GBPUSD&timeframe=H1,M30,M15&pretend_date=2025-06-01&pretend_time=08:00&api_key={$k}");
                    ?>
                </div>
            </div>

        </div>
    </div>

    <!-- Interactive Live Test -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-6" style="color: var(--text-primary);">Live Test</h2>
        <div class="grid grid-cols-1 gap-6">
            <?php
            $testExamples = [
                ['GBP/USD — Monthly (MN1)',  'GBPUSD', 'MN1'],
                ['EUR/USD — H4 Intelligence','EURUSD',  'H4'],
                ['XAU/USD — Daily (D1)',     'XAUUSD',  'D1'],
                ['USD/JPY — H1 Intelligence','USDJPY',  'H1'],
            ];
            foreach ($testExamples as $idx => $ex):
                $url = "{$baseUrl}/market-intelligence-api-v1/market-intelligence-api.php?symbol={$ex[1]}&timeframe={$ex[2]}&api_key={$apiKey}&format=json";
            ?>
            <div class="p-6 rounded-2xl example-card cursor-pointer" style="background-color: var(--card-bg); border: 1px solid var(--border);" onclick="testAPI(event.currentTarget, '<?php echo htmlspecialchars($url); ?>')">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3" style="background: linear-gradient(135deg, #10B981, #0EA5E9);">
                            <span class="text-sm font-bold text-white"><?php echo $idx + 1; ?></span>
                        </div>
                        <h3 class="text-lg font-semibold" style="color: var(--text-primary);"><?php echo htmlspecialchars($ex[0]); ?></h3>
                    </div>
                    <button class="px-3 py-1 rounded-lg text-xs font-medium" style="background: linear-gradient(135deg, #10B981, #0EA5E9); color: white;" onclick="event.stopPropagation(); testAPI(event.currentTarget.closest('.example-card'), '<?php echo htmlspecialchars($url); ?>')">
                        Test Request
                    </button>
                </div>
                <div class="p-4 rounded-xl font-mono text-sm overflow-x-auto" style="background-color: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-primary);">
                    <?php echo htmlspecialchars($url); ?>
                </div>
                <div class="test-response mt-4" style="display: none;"></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Response Format -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-6" style="color: var(--text-primary);">Response Format</h2>

        <!-- Plain text default -->
        <div class="p-6 rounded-2xl mb-4" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="flex items-center mb-4">
                <span class="method-badge mr-3" style="background: rgba(16,185,129,0.15); color: #10B981;">Default</span>
                <h3 class="text-base font-semibold" style="color: var(--text-primary);">Plain Text — Formatted Markdown Report</h3>
            </div>
            <p class="text-sm mb-4" style="color: var(--text-secondary);">Without <code>?format=json</code> the API returns <code>text/plain</code> — a structured markdown report you can display directly in an AI prompt, a terminal, or a markdown renderer.</p>
            <div class="response-preview" style="color: var(--text-primary);">
<pre># Market Intelligence Report — GBPUSD [MN1] — 2026-06-05

## Price History

Dataset high (24 months): 1.31800 | Dataset low (24 months): 1.18500
Current close: 1.27340 — 3.4% below dataset high, 7.5% above dataset low

## Market Structure

Consecutive higher highs: 3 | Consecutive higher lows: 2
Consecutive lower highs: 0 | Consecutive lower lows: 0
Structure: Uptrend building on monthly timeframe

## Range Position

24-month range: 1.18500 – 1.31800 (range: 1330 pips)
Current price at 67.2% of 24-month range (near upper half)

## Percentile Ranking

Price percentile across 24 monthly closes: 41.3%
Sits in the lower-mid range of dataset distribution

## Drawdown

Monthly drawdowns from peak in dataset: 5
Largest: 18.5% | Average: 11.2%
Current drawdown from recent high: 2.3%

## Volatility

Current monthly range: 390 pips
Historical average monthly range: 320 pips
Volatility regime: ELEVATED (volatility percentile: 74.2%)

## Moving Averages

Short MA (10): 1.26120 | Long MA (20): 1.24880
Short MA is ABOVE long MA — bullish Monthly alignment

## Candle Behaviour

Bullish candles: 14 | Bearish candles: 10 (lookback: 24 bars)
Recent 6 bars: 4 up, 2 down

## Seasonal Statistics

June (last 12 years): Bullish 7/12 (58.3%)
Average June move: +180 pips | Bias: mildly bullish

Q2 (last 12 years): Bullish 8/12 (66.7%)
Average Q2 move: +290 pips | Bias: bullish

---

Dataset: 24 monthly bars used</pre>
            </div>
        </div>

        <!-- JSON format -->
        <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="flex items-center mb-4">
                <span class="method-badge mr-3" style="background: rgba(14,165,233,0.15); color: #0EA5E9;">?format=json</span>
                <h3 class="text-base font-semibold" style="color: var(--text-primary);">JSON — Full Structured Payload</h3>
            </div>
            <div class="response-preview" style="color: var(--text-primary);">
<pre>{
  "arrissa_data": {
    "request_id": "mi_6834f2abc1234.5678",
    "symbol": "GBPUSD",
    "payload": {
      "symbol":      "GBPUSD",
      "timeframe":   "MN1",
      "server_time": "2026-06-05 14:32:11",
      "pretend_mode": false,
      "report": {
        "price_history":      "Dataset high (24 months): 1.31800 | Dataset low: 1.18500\nCurrent close: 1.27340 — 3.4% below dataset high",
        "market_structure":   "Consecutive higher highs: 3 | Consecutive higher lows: 2\nStructure: Uptrend building on monthly timeframe",
        "range_position":     "24-month range: 1.18500 – 1.31800\nCurrent price at 67.2% of 24-month range (near upper half)",
        "percentile_ranking": "Price percentile across 24 monthly closes: 41.3%",
        "drawdown":           "Monthly drawdowns from peak: 5 | Largest: 18.5% | Average: 11.2%",
        "drawdown_context":   "Current drawdown from recent high: 2.3%",
        "volatility":         "Current monthly range: 390 pips | Historical avg: 320 pips\nVolatility regime: ELEVATED (percentile: 74.2%)",
        "moving_averages":    "Short MA (10): 1.26120 | Long MA (20): 1.24880\nShort MA is ABOVE long MA — bullish Monthly alignment",
        "candle_behaviour":   "Bullish: 14 | Bearish: 10 over 24 bars\nRecent 6 bars: 4 up, 2 down",
        "last_candle":        "May 2026 — O: 1.25180 H: 1.29640 L: 1.24500 C: 1.27340 (Range: 514 pips, Bullish)",
        "current_candle":     "Jun 2026 (forming) — O: 1.27340 H: 1.28100 L: 1.26400 C: 1.27820",
        "seasonal_month":     "June (last 12 years): Bullish 7/12 (58.3%) | Avg move: +180 pips",
        "seasonal_quarter":   "Q2 (last 12 years): Bullish 8/12 (66.7%) | Avg move: +290 pips",
        "dataset_note":       "Dataset: 24 monthly bars used"
      },
      "data": {
        "current_close":          1.27340,
        "dataset_high":           1.31800,
        "dataset_low":            1.18500,
        "pct_from_dataset_high":  3.4,
        "pct_from_dataset_low":   7.5,
        "candles_copied":         24,
        "percentile":             41.3,
        "current_volatility":     390.0,
        "historical_volatility":  320.0,
        "volatility_percentile":  74.2,
        "short_ma":               1.26120,
        "long_ma":                1.24880,
        "range_high_lb":          1.31800,
        "range_low_lb":           1.18500,
        "pct_in_lb_range":        67.2,
        "cons_hh":                3,
        "cons_hl":                2,
        "cons_lh":                0,
        "cons_ll":                0,
        "dd_count":               5,
        "largest_dd":             18.5,
        "avg_dd":                 11.2,
        "recent_up":              4,
        "recent_down":            2
      }
    },
    "timestamp": "2026-06-05 14:32:11"
  }
}</pre>
            </div>
        </div>

        <!-- timeframe=all JSON structure -->
        <div class="mt-4 p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="flex items-center mb-4">
                <span class="method-badge mr-3" style="background: rgba(139,92,246,0.15); color: #8B5CF6;">?timeframe=all</span>
                <h3 class="text-base font-semibold" style="color: var(--text-primary);">All-Timeframes JSON Structure</h3>
            </div>
            <p class="text-sm mb-4" style="color: var(--text-secondary);">When <code>timeframe=all&format=json</code> is used, the payload contains a <code>timeframes</code> object keyed by timeframe label. Each value is the same <code>{report, data}</code> structure as a single-TF response.</p>
            <div class="response-preview" style="color: var(--text-primary);">
<pre>{
  "arrissa_data": {
    "payload": {
      "symbol":      "GBPUSD",
      "server_time": "2026-06-05 14:32:11",
      "pretend_mode": false,
      "timeframes": {
        "MN1": {
          "report": { "price_history": "...", "seasonal_month": "...", ... },
          "data":   { "current_close": 1.27340, "dataset_high": 1.31800, ... }
        },
        "W1": {
          "report": { "price_history": "...", ... },
          "data":   { "current_close": 1.27340, "dataset_high": 1.28500, ... }
        },
        "D1":  { "report": { ... }, "data": { ... } },
        "H4":  { "report": { ... }, "data": { ... } },
        "H1":  { "report": { ... }, "data": { ... } },
        "M30": { "report": { ... }, "data": { ... } },
        "M15": { "report": { ... }, "data": { ... } },
        "M5":  { "report": { ... }, "data": { ... } },
        "M1":  { "report": { ... }, "data": { ... } }
      }
    }
  }
}</pre>
            </div>
        </div>

        <div class="mt-4 p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <h3 class="text-base font-semibold mb-4" style="color: var(--text-primary);">Error Responses</h3>
            <div class="space-y-3">
                <div class="p-4 rounded-xl" style="background-color: rgba(244,67,54,0.08); border: 1px solid rgba(244,67,54,0.3);">
                    <div class="font-mono text-xs mb-2" style="color: #F44336;">HTTP 503 — MT5 Data Server not connected</div>
                    <p class="text-xs" style="color: var(--text-secondary);">EA is not running or did not respond within the timeout (20 s for single TF, 45 s for <code>timeframe=all</code>). Attach <code>MarketIntelligenceAPI.mq5</code> to any MT5 chart.</p>
                </div>
                <div class="p-4 rounded-xl" style="background-color: rgba(244,67,54,0.08); border: 1px solid rgba(244,67,54,0.3);">
                    <div class="font-mono text-xs mb-2" style="color: #F44336;">HTTP 400 — Missing symbol</div>
                    <p class="text-xs" style="color: var(--text-secondary);"><code>symbol</code> parameter is required. Pass any valid MT5 symbol (e.g. <code>GBPUSD</code>, <code>XAUUSD</code>).</p>
                </div>
                <div class="p-4 rounded-xl" style="background-color: rgba(244,67,54,0.08); border: 1px solid rgba(244,67,54,0.3);">
                    <div class="font-mono text-xs mb-2" style="color: #F44336;">HTTP 400 — EA returned error</div>
                    <p class="text-xs" style="color: var(--text-secondary);">Symbol not available in the broker's feed, or insufficient history for the requested timeframe (<code>CopyRates()</code> returned 0 bars). Verify the symbol name matches exactly what is in MT5 and scroll back on the relevant chart to cache the history.</p>
                </div>
                <div class="p-4 rounded-xl" style="background-color: rgba(244,67,54,0.08); border: 1px solid rgba(244,67,54,0.3);">
                    <div class="font-mono text-xs mb-2" style="color: #F44336;">HTTP 404 — Not found</div>
                    <p class="text-xs" style="color: var(--text-secondary);">Missing or invalid <code>api_key</code>. Check your key in Settings.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Tips -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-6" style="color: var(--text-primary);">Usage Tips</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-start">
                    <i data-feather="info" class="mr-3 flex-shrink-0" style="width: 18px; height: 18px; color: #10B981; margin-top: 2px;"></i>
                    <div>
                        <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Use plain text output for AI prompts</h4>
                        <p class="text-xs" style="color: var(--text-secondary);">The default <code>text/plain</code> response is designed to be dropped directly into a language model prompt. The structured headings give the model clear sections to reason over without you needing to format the data yourself.</p>
                    </div>
                </div>
            </div>
            <div class="p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-start">
                    <i data-feather="info" class="mr-3 flex-shrink-0" style="width: 18px; height: 18px; color: #10B981; margin-top: 2px;"></i>
                    <div>
                        <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Seasonal stats are calendar-aware in pretend mode</h4>
                        <p class="text-xs" style="color: var(--text-secondary);">When using <code>pretend_date</code>, the seasonal stats use the historical month and quarter — so a pretend date of <code>2024-11-05</code> gives you November and Q4 seasonality as it would have read on that day.</p>
                    </div>
                </div>
            </div>
            <div class="p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-start">
                    <i data-feather="info" class="mr-3 flex-shrink-0" style="width: 18px; height: 18px; color: #0EA5E9; margin-top: 2px;"></i>
                    <div>
                        <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Bars need to be loaded in MT5 per timeframe</h4>
                        <p class="text-xs" style="color: var(--text-secondary);">If the EA returns fewer bars than expected for a timeframe, open the symbol on that timeframe chart in MT5 and scroll back to cache the history. MT5 downloads bars on demand — once cached they stay available to the EA across all timeframes.</p>
                    </div>
                </div>
            </div>
            <div class="p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-start">
                    <i data-feather="info" class="mr-3 flex-shrink-0" style="width: 16px; height: 16px; color: #6366F1; margin-top: 2px;"></i>
                    <div>
                        <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Combine with Markets Brain API for full context</h4>
                        <p class="text-xs" style="color: var(--text-secondary);">Use the Market Intelligence API to establish the monthly macro backdrop — range position, structure, seasonality. Then call the Markets Brain API for real-time intraday state. Together they give you both the "where are we in the big picture" and "what is price doing right now" views.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="p-6 rounded-2xl" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(14, 165, 233, 0.1)); border: 1px solid var(--border);">
        <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">Ready to Get Started?</h3>
        <div class="flex flex-wrap gap-3">
            <a href="/download-eas" class="inline-flex items-center px-5 py-2.5 rounded-full text-sm font-medium" style="background: linear-gradient(135deg, #10B981, #0EA5E9); color: white;">
                <i data-feather="download" class="mr-2" style="width: 16px; height: 16px;"></i>
                Download EA
            </a>
            <a href="/settings" class="inline-flex items-center px-5 py-2.5 rounded-full text-sm font-medium" style="background-color: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border);">
                <i data-feather="settings" class="mr-2" style="width: 16px; height: 16px;"></i>
                Configure API Settings
            </a>
            <a href="/markets-brain-api-guide" class="inline-flex items-center px-5 py-2.5 rounded-full text-sm font-medium" style="background-color: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border);">
                <i data-feather="cpu" class="mr-2" style="width: 16px; height: 16px;"></i>
                Markets Brain API
            </a>
            <a href="/risk-management-api-guide" class="inline-flex items-center px-5 py-2.5 rounded-full text-sm font-medium" style="background-color: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border);">
                <i data-feather="shield" class="mr-2" style="width: 16px; height: 16px;"></i>
                Risk Management API
            </a>
        </div>
    </div>

</div>

<script>
function testAPI(element, url) {
    let card = element;
    if (element && element.classList && element.classList.contains('example-card')) {
        card = element;
    } else if (element && element.closest) {
        card = element.closest('.example-card');
    }
    if (!card) return;

    const responseDiv = card.querySelector('.test-response');
    if (!responseDiv) return;

    responseDiv.style.display = 'block';
    responseDiv.innerHTML = '<div style="padding: 12px; background-color: var(--bg-secondary); border-radius: 8px; color: var(--text-secondary);"><i data-feather="loader" style="width: 14px; height: 14px; display: inline; animation: spin 1s linear infinite;"></i> Loading… (up to 20 s — 45 s for timeframe=all)</div>';
    feather.replace();

    fetch(url)
        .then(r => r.json())
        .then(data => {
            if (data.arrissa_data && data.arrissa_data.error) {
                responseDiv.innerHTML = '<div style="padding: 12px; background-color: rgba(244,67,54,0.1); border-radius: 8px; border: 1px solid #F44336; color: #F44336;"><div style="font-weight:600;margin-bottom:8px;"><i data-feather="alert-triangle" style="width:14px;height:14px;display:inline;"></i> ' + data.arrissa_data.error + '</div>' + (data.arrissa_data.message ? '<div style="font-size:0.75rem;opacity:0.9;">' + data.arrissa_data.message + '</div>' : '') + '</div>';
                feather.replace();
            } else {
                responseDiv.innerHTML = '<div style="padding: 12px; background-color: var(--bg-primary); border-radius: 8px; border: 1px solid var(--border);"><pre style="margin:0;max-height:400px;overflow-y:auto;color:var(--text-secondary);font-size:0.75rem;">' + JSON.stringify(data, null, 2) + '</pre></div>';
            }
        })
        .catch(err => {
            responseDiv.innerHTML = '<div style="padding: 12px; background-color: rgba(244,67,54,0.1); border-radius: 8px; border: 1px solid #F44336; color: #F44336;"><i data-feather="alert-circle" style="width:14px;height:14px;display:inline;"></i> Error: ' + err.message + '</div>';
            feather.replace();
        });
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => { alert('Copied to clipboard!'); });
}

feather.replace();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/app.php';
?>
