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

$title = 'Trend Bias API Guide';
$page  = 'trend-bias-api-guide';
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
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
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
    border-left: 3px solid #6366F1;
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
    max-height: 460px;
    overflow-y: auto;
}
.payload-field-badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 9999px;
    font-size: 0.7rem;
    font-weight: 700;
    background: linear-gradient(135deg, #6366F1, #8B5CF6);
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
.bias-bull {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(16, 185, 129, 0.05));
    border-left: 3px solid #10B981;
}
.bias-bear {
    background: linear-gradient(135deg, rgba(244, 67, 54, 0.15), rgba(244, 67, 54, 0.05));
    border-left: 3px solid #F44336;
}
</style>

<div class="p-8 max-w-[1600px] mx-auto">

    <!-- EA Requirement Notice -->
    <div class="mb-6 p-5 rounded-2xl" style="background-color: rgba(99, 102, 241, 0.1); border: 1px solid #6366F1;">
        <div class="flex items-start">
            <div class="flex-shrink-0 mr-4">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, #6366F1, #8B5CF6);">
                    <i data-feather="alert-circle" style="width: 20px; height: 20px; color: white;"></i>
                </div>
            </div>
            <div class="flex-1">
                <h3 class="text-base font-semibold mb-2" style="color: var(--text-primary);">MT5 Expert Advisor Required</h3>
                <p class="text-sm mb-3" style="color: var(--text-secondary);">This API requires the <strong style="color: var(--text-primary);">Trend Bias API EA</strong> to be running on an MT5 chart. The EA polls every second, runs a no-lookahead ZigZag swing analysis on demand, and returns the BULLISH or BEARISH bias for any symbol and timeframe you request.</p>
                <a href="/download-eas" class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium transition-colors" style="background: linear-gradient(135deg, #6366F1, #8B5CF6); color: white;">
                    <i data-feather="download" class="mr-2" style="width: 16px; height: 16px;"></i>
                    Download Trend Bias EA
                </a>
            </div>
        </div>
    </div>

    <!-- Hero Header -->
    <div class="mb-10">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-4xl font-bold mb-3 tracking-tight" style="color: var(--text-primary);">
                    Trend Bias API
                    <span class="section-badge ml-3" style="background: linear-gradient(135deg, #6366F1, #8B5CF6); color: white;">v1.0</span>
                </h1>
                <p class="text-lg" style="color: var(--text-secondary);">ZigZag swing-point bias detection — BULLISH or BEARISH for any symbol and timeframe, with full pretend-date support</p>
            </div>
        </div>

        <!-- Features Banner -->
        <div class="p-6 rounded-2xl gradient-bg" style="border: 1px solid var(--border);">
            <div class="flex items-start">
                <div class="flex-shrink-0 mr-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background: linear-gradient(135deg, #6366F1, #8B5CF6);">
                        <i data-feather="trending-up" style="width: 24px; height: 24px; color: white;"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">What is the Trend Bias API?</h3>
                    <p class="text-sm mb-4" style="color: var(--text-secondary);">Send a symbol and timeframe — the EA scans back through ZigZag swing points and returns whether the most recent confirmed market structure corner is a LOW (price reversed up → BULLISH) or a HIGH (price reversed down → BEARISH). The response also includes the exact price and timestamp of the confirmed swing so you know precisely when the bias was established.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm" style="color: var(--text-secondary);">
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #6366F1;"></i>
                            <span><strong>No Lookahead Bias:</strong> Swing confirmation uses only bars that existed at or before the pretend datetime — safe for backtesting</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #6366F1;"></i>
                            <span><strong>Any Timeframe:</strong> M1 through MN1 — use D1 for macro bias, H4 for session, M15 for entry confirmation</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #6366F1;"></i>
                            <span><strong>Precise Timestamps:</strong> Know exactly when the confirmed swing occurred — the moment the bias was structurally locked in</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #6366F1;"></i>
                            <span><strong>Multi-Timeframe Ready:</strong> Call once per TF and stack HTF bias with LTF entry signals in your automation pipeline</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- How Bias Works -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-6" style="color: var(--text-primary);">How Bias is Determined</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

            <!-- Bullish -->
            <div class="p-6 rounded-2xl bias-bull" style="border: 1px solid var(--border);">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center mr-3" style="background: #10B981;">
                        <i data-feather="trending-up" style="width: 18px; height: 18px; color: white;"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold" style="color: #10B981;">BULLISH</h3>
                        <p class="text-xs" style="color: var(--text-secondary);">Last confirmed corner is a LOW</p>
                    </div>
                </div>
                <p class="text-sm" style="color: var(--text-secondary);">The ZigZag's most recent fully committed swing point is a <strong style="color: var(--text-primary);">swing LOW</strong>. This means price reversed upward after forming that trough, and the current move is pushing higher. The market is in an upward structural phase on the requested timeframe.</p>
                <div class="mt-4 p-3 rounded-xl text-xs font-mono" style="background-color: rgba(0,0,0,0.2); color: #10B981;">
                    confirmed_swing_type: "LOW" → bias: "BULLISH"
                </div>
            </div>

            <!-- Bearish -->
            <div class="p-6 rounded-2xl bias-bear" style="border: 1px solid var(--border);">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center mr-3" style="background: #F44336;">
                        <i data-feather="trending-down" style="width: 18px; height: 18px; color: white;"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold" style="color: #F44336;">BEARISH</h3>
                        <p class="text-xs" style="color: var(--text-secondary);">Last confirmed corner is a HIGH</p>
                    </div>
                </div>
                <p class="text-sm" style="color: var(--text-secondary);">The ZigZag's most recent fully committed swing point is a <strong style="color: var(--text-primary);">swing HIGH</strong>. Price reversed downward after forming that peak, and the current move is pushing lower. The market is in a downward structural phase on the requested timeframe.</p>
                <div class="mt-4 p-3 rounded-xl text-xs font-mono" style="background-color: rgba(0,0,0,0.2); color: #F44336;">
                    confirmed_swing_type: "HIGH" → bias: "BEARISH"
                </div>
            </div>
        </div>

        <!-- Swing Detection -->
        <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <h3 class="text-base font-semibold mb-4" style="color: var(--text-primary);">Swing Detection Algorithm</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <div class="flex items-center mb-2">
                        <span class="method-badge mr-2" style="background: rgba(99,102,241,0.2); color: #6366F1;">Depth = 12</span>
                    </div>
                    <p class="text-xs" style="color: var(--text-secondary);">A bar is confirmed as a swing HIGH/LOW only if its high/low is the extreme among the 12 bars on each side. This filters noise while capturing meaningful structural pivots.</p>
                </div>
                <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <div class="flex items-center mb-2">
                        <span class="method-badge mr-2" style="background: rgba(99,102,241,0.2); color: #6366F1;">s0 vs s1</span>
                    </div>
                    <p class="text-xs" style="color: var(--text-secondary);"><code>s0</code> = the newest confirmed swing (may be still unfolding near the pretend time). <code>s1</code> = the second swing — the fully committed corner that definitively set the current direction. Bias is read from <code>s1</code>.</p>
                </div>
                <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <div class="flex items-center mb-2">
                        <span class="method-badge mr-2" style="background: rgba(255,152,0,0.2); color: #FF9800;">No Lookahead</span>
                    </div>
                    <p class="text-xs" style="color: var(--text-secondary);">When using <code>pretend_date</code>, the swing scan starts at <code>pretend_bar + depth + 1</code>. The confirmation window on the "new" side never touches bars after the pretend datetime — eliminating all future-bar contamination.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Payload Fields -->
    <div class="mb-8 p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
        <h3 class="text-xl font-semibold mb-6" style="color: var(--text-primary);">Response Payload Overview</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                <div class="payload-field-badge mb-3">Core</div>
                <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Bias Result</h4>
                <ul class="text-xs space-y-1" style="color: var(--text-secondary);">
                    <li><code>bias</code> — BULLISH or BEARISH</li>
                    <li><code>symbol</code></li>
                    <li><code>timeframe</code></li>
                    <li><code>pretend_datetime</code></li>
                </ul>
            </div>
            <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                <div class="payload-field-badge mb-3">Confirmed</div>
                <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Committed Corner (s1)</h4>
                <ul class="text-xs space-y-1" style="color: var(--text-secondary);">
                    <li><code>confirmed_swing_price</code></li>
                    <li><code>confirmed_swing_time</code></li>
                    <li><code>confirmed_swing_type</code></li>
                </ul>
            </div>
            <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                <div class="payload-field-badge mb-3">Forming</div>
                <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Newest Swing (s0)</h4>
                <ul class="text-xs space-y-1" style="color: var(--text-secondary);">
                    <li><code>forming_swing_price</code></li>
                    <li><code>forming_swing_time</code></li>
                    <li><code>forming_swing_type</code></li>
                </ul>
            </div>
            <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                <div class="payload-field-badge mb-3">Meta</div>
                <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Request Info</h4>
                <ul class="text-xs space-y-1" style="color: var(--text-secondary);">
                    <li><code>request_id</code></li>
                    <li><code>timestamp</code></li>
                </ul>
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
                    <?php echo $baseUrl; ?>/trend-bias-api-v1/trend-bias-api.php
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
                        <td class="px-6 py-4"><code class="text-sm" style="color: #6366F1;">symbol</code></td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">string</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--success);">✅ Yes</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">Trading symbol (e.g. <code>EURUSD</code>, <code>XAUUSD</code>). Case-insensitive.</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td class="px-6 py-4"><code class="text-sm" style="color: #6366F1;">timeframe</code></td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">string</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--success);">✅ Yes</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">One of <code>M1</code> <code>M5</code> <code>M15</code> <code>M30</code> <code>H1</code> <code>H4</code> <code>D1</code> <code>W1</code> <code>MN1</code>. Use <code>D1</code> for macro bias, <code>H4</code> for session bias, <code>M15</code> for entry-level bias.</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td class="px-6 py-4"><code class="text-sm" style="color: #6366F1;">api_key</code></td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">string</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--success);">✅ Yes</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">Your API authentication key (also accepted as <code>X-Api-Key</code> header).</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td class="px-6 py-4"><code class="text-sm" style="color: #FF9800;">pretend_date</code></td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">string</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">❌ No</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">Historical date in <code>YYYY-MM-DD</code> format. EA calculates bias as if it were that date — swing detection is anchored to that bar via <code>iBarShift()</code> with no lookahead.</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4"><code class="text-sm" style="color: #FF9800;">pretend_time</code></td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">string</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">❌ No</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">Historical time in <code>H:MM</code> or <code>HH:MM</code> format (e.g. <code>02:15</code>). Combined with <code>pretend_date</code> for precise intra-day historical control. Defaults to <code>00:00</code> if omitted.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="divider"></div>

    <!-- cURL Examples -->
    <div class="mb-12">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, #6366F1, #8B5CF6);">
                <i data-feather="terminal" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">cURL Examples</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Copy and paste these commands into your terminal</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6">

            <!-- D1 Examples -->
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">
                    <span class="method-badge mr-2" style="background: rgba(99,102,241,0.2); color: #6366F1;">D1</span>
                    Daily Bias — Macro Trend Direction
                </h3>
                <div class="space-y-4">
                    <?php
                    $d1 = [
                        ['EUR/USD — Daily bias', 'EURUSD', 'D1'],
                        ['GBP/USD — Daily bias', 'GBPUSD', 'D1'],
                        ['XAU/USD — Daily bias', 'XAUUSD', 'D1'],
                    ];
                    foreach ($d1 as $ex):
                        $url = "{$baseUrl}/trend-bias-api-v1/trend-bias-api.php?symbol={$ex[1]}&timeframe={$ex[2]}&api_key={$apiKey}";
                    ?>
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold" style="color: var(--text-secondary);"><?php echo $ex[0]; ?></label>
                            <button onclick="copyToClipboard('curl &quot;<?= htmlspecialchars($url) ?>&quot;')" class="text-xs px-2 py-1 rounded-lg transition-colors flex items-center gap-1" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);">
                                <i data-feather="copy" style="width: 12px; height: 12px;"></i>
                                Copy
                            </button>
                        </div>
                        <div class="p-3 rounded-lg" style="background-color: var(--input-bg); border: 1px solid var(--input-border); font-family: 'Fira Code', monospace; font-size: 0.75rem; overflow-x: auto;">
                            <code style="color: var(--text-primary);">curl "<?= htmlspecialchars($url) ?>"</code>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- H4 Examples -->
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">
                    <span class="method-badge mr-2" style="background: rgba(14,165,233,0.2); color: #0EA5E9;">H4</span>
                    4-Hour Bias — Session Trend Direction
                </h3>
                <div class="space-y-4">
                    <?php
                    $h4 = [
                        ['GBP/USD — H4 bias', 'GBPUSD', 'H4'],
                        ['EUR/USD — H4 bias', 'EURUSD', 'H4'],
                        ['USD/JPY — H4 bias', 'USDJPY', 'H4'],
                    ];
                    foreach ($h4 as $ex):
                        $url = "{$baseUrl}/trend-bias-api-v1/trend-bias-api.php?symbol={$ex[1]}&timeframe={$ex[2]}&api_key={$apiKey}";
                    ?>
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold" style="color: var(--text-secondary);"><?php echo $ex[0]; ?></label>
                            <button onclick="copyToClipboard('curl &quot;<?= htmlspecialchars($url) ?>&quot;')" class="text-xs px-2 py-1 rounded-lg transition-colors flex items-center gap-1" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);">
                                <i data-feather="copy" style="width: 12px; height: 12px;"></i>
                                Copy
                            </button>
                        </div>
                        <div class="p-3 rounded-lg" style="background-color: var(--input-bg); border: 1px solid var(--input-border); font-family: 'Fira Code', monospace; font-size: 0.75rem; overflow-x: auto;">
                            <code style="color: var(--text-primary);">curl "<?= htmlspecialchars($url) ?>"</code>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- M15 Examples -->
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">
                    <span class="method-badge mr-2" style="background: rgba(16,185,129,0.2); color: #10B981;">M15</span>
                    15-Minute Bias — Entry-Level Confirmation
                </h3>
                <div class="space-y-4">
                    <?php
                    $m15 = [
                        ['EUR/USD — M15 bias', 'EURUSD', 'M15'],
                        ['GBP/USD — M15 bias', 'GBPUSD', 'M15'],
                        ['XAU/USD — M15 bias', 'XAUUSD', 'M15'],
                    ];
                    foreach ($m15 as $ex):
                        $url = "{$baseUrl}/trend-bias-api-v1/trend-bias-api.php?symbol={$ex[1]}&timeframe={$ex[2]}&api_key={$apiKey}";
                    ?>
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold" style="color: var(--text-secondary);"><?php echo $ex[0]; ?></label>
                            <button onclick="copyToClipboard('curl &quot;<?= htmlspecialchars($url) ?>&quot;')" class="text-xs px-2 py-1 rounded-lg transition-colors flex items-center gap-1" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);">
                                <i data-feather="copy" style="width: 12px; height: 12px;"></i>
                                Copy
                            </button>
                        </div>
                        <div class="p-3 rounded-lg" style="background-color: var(--input-bg); border: 1px solid var(--input-border); font-family: 'Fira Code', monospace; font-size: 0.75rem; overflow-x: auto;">
                            <code style="color: var(--text-primary);">curl "<?= htmlspecialchars($url) ?>"</code>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Historical Examples -->
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">
                    <span style="background: linear-gradient(135deg, #FF9800, #FFA726); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                        Historical Backtesting (Pretend Date &amp; Time)
                    </span>
                </h3>
                <div class="mb-4 p-4 rounded-lg" style="background: linear-gradient(135deg, rgba(255,152,0,0.1), rgba(255,167,38,0.1)); border-left: 3px solid #FF9800;">
                    <p class="text-sm mb-1" style="color: var(--text-primary);"><strong>Time Travel!</strong> Get the exact bias that would have been computed at any past datetime — with zero lookahead contamination.</p>
                    <p class="text-xs" style="color: var(--text-secondary);">The EA uses <code>iBarShift()</code> to pin the swing scan to the pretend bar. The confirmation window on the newer side is strictly bounded to bars that existed at the pretend time, so no future price action leaks in.</p>
                </div>
                <div class="space-y-4">
                    <?php
                    $hist = [
                        ['EURUSD D1 bias — June 10 2026 02:15',    'EURUSD', 'D1',  '2026-06-10', '2:15'],
                        ['GBPUSD H4 bias — London open May 19 2025 09:00', 'GBPUSD', 'H4',  '2025-05-19', '9:00'],
                        ['XAUUSD D1 bias — NFP day Jan 10 2025 14:30',    'XAUUSD', 'D1',  '2025-01-10', '14:30'],
                        ['USDJPY M15 bias — NY open Mar 3 2025 14:00',    'USDJPY', 'M15', '2025-03-03', '14:00'],
                    ];
                    foreach ($hist as $ex):
                        $url = "{$baseUrl}/trend-bias-api-v1/trend-bias-api.php?symbol={$ex[1]}&timeframe={$ex[2]}&pretend_date={$ex[3]}&pretend_time={$ex[4]}&api_key={$apiKey}";
                    ?>
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold" style="color: var(--text-secondary);"><?php echo $ex[0]; ?></label>
                            <button onclick="copyToClipboard('curl &quot;<?= htmlspecialchars($url) ?>&quot;')" class="text-xs px-2 py-1 rounded-lg transition-colors flex items-center gap-1" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);">
                                <i data-feather="copy" style="width: 12px; height: 12px;"></i>
                                Copy
                            </button>
                        </div>
                        <div class="p-3 rounded-lg" style="background-color: var(--input-bg); border: 1px solid var(--input-border); font-family: 'Fira Code', monospace; font-size: 0.75rem; overflow-x: auto;">
                            <code style="color: var(--text-primary);">curl "<?= htmlspecialchars($url) ?>"</code>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="mt-3 text-xs" style="color: var(--text-secondary);">
                        <strong>Backtesting use cases:</strong>
                        <ul class="list-disc list-inside mt-2 space-y-1">
                            <li>Build a dataset of D1 bias at every historical trade entry to use as an ML feature</li>
                            <li>Verify whether D1 and H4 bias agreed at a specific signal timestamp</li>
                            <li>Replay session opens to study how bias aligned with your strategy's signals</li>
                            <li>Label historical candles with their bias at that exact moment in time</li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Live Test -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-6" style="color: var(--text-primary);">Live Test</h2>
        <div class="grid grid-cols-1 gap-6">
            <?php
            $testExamples = [
                ['EUR/USD — Daily Bias',     'EURUSD', 'D1'],
                ['GBP/USD — H4 Bias',        'GBPUSD', 'H4'],
                ['XAU/USD — Daily Bias',     'XAUUSD', 'D1'],
                ['USD/JPY — M15 Bias',       'USDJPY', 'M15'],
            ];
            foreach ($testExamples as $idx => $ex):
                $url = "{$baseUrl}/trend-bias-api-v1/trend-bias-api.php?symbol={$ex[1]}&timeframe={$ex[2]}&api_key={$apiKey}";
            ?>
            <div class="p-6 rounded-2xl example-card cursor-pointer" style="background-color: var(--card-bg); border: 1px solid var(--border);" onclick="testAPI(event.currentTarget, '<?php echo htmlspecialchars($url); ?>')">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3" style="background: linear-gradient(135deg, #6366F1, #8B5CF6);">
                            <span class="text-sm font-bold text-white"><?php echo $idx + 1; ?></span>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold" style="color: var(--text-primary);"><?php echo $ex[0]; ?></h3>
                            <span class="text-xs" style="color: var(--text-secondary);">timeframe=<?php echo $ex[2]; ?></span>
                        </div>
                    </div>
                    <button class="px-3 py-1 rounded-lg text-xs font-medium" style="background: linear-gradient(135deg, #6366F1, #8B5CF6); color: white;" onclick="event.stopPropagation(); testAPI(event.currentTarget.closest('.example-card'), '<?php echo htmlspecialchars($url); ?>')">
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
        <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <h3 class="text-base font-semibold mb-4" style="color: var(--text-primary);">Success Response</h3>
            <div class="response-preview" style="color: var(--text-primary);">
<pre>{
  "arrissa_data": {
    "request_id": "tb_6849f2abc1234.5678",
    "symbol": "EURUSD",
    "payload": {
      "symbol":                "EURUSD",
      "timeframe":             "D1",
      "bias":                  "BULLISH",           // BULLISH or BEARISH
      "pretend_datetime":      "",                   // empty = live; "2026-06-10 02:15:00" = historical
      "confirmed_swing_price": 1.07823,             // price of the committed corner that set the bias
      "confirmed_swing_time":  "2026-05-12 00:00:00", // when that corner formed
      "confirmed_swing_type":  "LOW",               // LOW → BULLISH | HIGH → BEARISH
      "forming_swing_price":   1.13456,             // price of the newest (still unfolding) swing
      "forming_swing_time":    "2026-06-09 00:00:00",
      "forming_swing_type":    "HIGH"
    },
    "timestamp": "2026-06-21 10:30:00"
  }
}</pre>
            </div>
        </div>

        <div class="mt-4 p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <h3 class="text-base font-semibold mb-4" style="color: var(--text-primary);">Response Fields</h3>
            <div class="overflow-hidden rounded-xl" style="border: 1px solid var(--border);">
                <table class="w-full text-sm">
                    <thead style="background-color: var(--bg-secondary);">
                        <tr>
                            <th class="px-5 py-3 text-left font-semibold" style="color: var(--text-primary);">Field</th>
                            <th class="px-5 py-3 text-left font-semibold" style="color: var(--text-primary);">Type</th>
                            <th class="px-5 py-3 text-left font-semibold" style="color: var(--text-primary);">Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="px-5 py-3"><code style="color: #6366F1;">bias</code></td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">string</td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);"><code style="color: #10B981;">BULLISH</code> or <code style="color: #F44336;">BEARISH</code>. Derived from the confirmed swing type — the committed structural corner that defines current direction.</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="px-5 py-3"><code style="color: #6366F1;">pretend_datetime</code></td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">string</td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">The datetime used for the bias calculation. Empty string in live mode. <code>"YYYY-MM-DD HH:MM:SS"</code> in historical mode.</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="px-5 py-3"><code style="color: #6366F1;">confirmed_swing_price</code></td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">float</td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">Price of the fully committed ZigZag corner (s1). This is the structural level that locked in the current bias direction.</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="px-5 py-3"><code style="color: #6366F1;">confirmed_swing_time</code></td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">string</td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">The bar open time when the confirmed swing occurred. This is the <strong>precise moment the bias was established</strong>.</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="px-5 py-3"><code style="color: #6366F1;">confirmed_swing_type</code></td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">string</td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);"><code>LOW</code> = swing low (bias is BULLISH) / <code>HIGH</code> = swing high (bias is BEARISH).</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="px-5 py-3"><code style="color: #6366F1;">forming_swing_price</code></td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">float</td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">Price of the newest ZigZag swing (s0). This is the current move's leading edge — still developing, may shift as more bars form.</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="px-5 py-3"><code style="color: #6366F1;">forming_swing_time</code></td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">string</td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">Bar open time of the newest (forming) swing point.</td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3"><code style="color: #6366F1;">forming_swing_type</code></td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">string</td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);"><code>HIGH</code> or <code>LOW</code> — the type of the newest swing point. In a BULLISH bias this is typically a HIGH (price is currently pushing up).</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4 p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <h3 class="text-base font-semibold mb-4" style="color: var(--text-primary);">Error Responses</h3>
            <div class="space-y-3">
                <div class="p-4 rounded-xl" style="background-color: rgba(244,67,54,0.08); border: 1px solid rgba(244,67,54,0.3);">
                    <div class="font-mono text-xs mb-2" style="color: #F44336;">HTTP 503 — MT5 Data Server not connected</div>
                    <p class="text-xs" style="color: var(--text-secondary);">EA is not running or did not respond within 10 seconds. Attach <code>TrendBiasAPI.mq5</code> to any MT5 chart.</p>
                </div>
                <div class="p-4 rounded-xl" style="background-color: rgba(244,67,54,0.08); border: 1px solid rgba(244,67,54,0.3);">
                    <div class="font-mono text-xs mb-2" style="color: #F44336;">HTTP 400 — Invalid timeframe</div>
                    <p class="text-xs" style="color: var(--text-secondary);"><code>timeframe</code> must be exactly one of: M1, M5, M15, M30, H1, H4, D1, W1, MN1.</p>
                </div>
                <div class="p-4 rounded-xl" style="background-color: rgba(244,67,54,0.08); border: 1px solid rgba(244,67,54,0.3);">
                    <div class="font-mono text-xs mb-2" style="color: #F44336;">HTTP 404 — Not found</div>
                    <p class="text-xs" style="color: var(--text-secondary);">Missing or invalid <code>api_key</code>. Check your key in Settings.</p>
                </div>
                <div class="p-4 rounded-xl" style="background-color: rgba(244,67,54,0.08); border: 1px solid rgba(244,67,54,0.3);">
                    <div class="font-mono text-xs mb-2" style="color: #F44336;">payload.error — No bar found at pretend datetime</div>
                    <p class="text-xs" style="color: var(--text-secondary);">The pretend datetime falls outside the available bar history (e.g. a weekend with no D1 bar). Adjust the date or time.</p>
                </div>
                <div class="p-4 rounded-xl" style="background-color: rgba(244,67,54,0.08); border: 1px solid rgba(244,67,54,0.3);">
                    <div class="font-mono text-xs mb-2" style="color: #F44336;">payload.error — Not enough swing data</div>
                    <p class="text-xs" style="color: var(--text-secondary);">The lookback window (500 bars) could not find 2 confirmed swings. This can happen for very new symbols or extremely high-depth settings.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Usage Tips -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-6" style="color: var(--text-primary);">Usage Tips</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-start">
                    <i data-feather="layers" class="mr-3 flex-shrink-0" style="width: 18px; height: 18px; color: #6366F1; margin-top: 2px;"></i>
                    <div>
                        <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Stack multiple timeframes</h4>
                        <p class="text-xs" style="color: var(--text-secondary);">Call once for D1, once for H4, and once for M15. Only take trades where all three agree. When they diverge, wait — a conflicting HTF bias is the market telling you the move isn't confirmed yet.</p>
                    </div>
                </div>
            </div>
            <div class="p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-start">
                    <i data-feather="clock" class="mr-3 flex-shrink-0" style="width: 18px; height: 18px; color: #6366F1; margin-top: 2px;"></i>
                    <div>
                        <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Use confirmed_swing_time to time entries</h4>
                        <p class="text-xs" style="color: var(--text-secondary);"><code>confirmed_swing_time</code> tells you exactly when the bias was established. A bias that just flipped hours ago is more potent than one confirmed weeks ago — recency matters for momentum-based entries.</p>
                    </div>
                </div>
            </div>
            <div class="p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-start">
                    <i data-feather="database" class="mr-3 flex-shrink-0" style="width: 18px; height: 18px; color: #6366F1; margin-top: 2px;"></i>
                    <div>
                        <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Build an ML feature with pretend_date</h4>
                        <p class="text-xs" style="color: var(--text-secondary);">Loop through every historical trade entry, call with <code>pretend_date</code> + <code>pretend_time</code>, and get the bias that was objectively true at that moment. This is a clean, lookahead-free feature for any supervised learning model.</p>
                    </div>
                </div>
            </div>
            <div class="p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-start">
                    <i data-feather="shield" class="mr-3 flex-shrink-0" style="width: 18px; height: 18px; color: #6366F1; margin-top: 2px;"></i>
                    <div>
                        <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Use as a filter, not a signal</h4>
                        <p class="text-xs" style="color: var(--text-secondary);">Bias tells you the structural direction — it is not an entry trigger. Combine it with a Markets Brain signal or your own confluence checklist. The bias is your gate: only pass through trades that agree with structure.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="p-6 rounded-2xl" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1)); border: 1px solid var(--border);">
        <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">Ready to Get Started?</h3>
        <div class="flex flex-wrap gap-3">
            <a href="/download-eas" class="inline-flex items-center px-5 py-2.5 rounded-full text-sm font-medium" style="background: linear-gradient(135deg, #6366F1, #8B5CF6); color: white;">
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
            <a href="/time-machine-ml-api-guide" class="inline-flex items-center px-5 py-2.5 rounded-full text-sm font-medium" style="background-color: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border);">
                <i data-feather="clock" class="mr-2" style="width: 16px; height: 16px;"></i>
                Time Machine ML API
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
    responseDiv.innerHTML = '<div style="padding: 12px; background-color: var(--bg-secondary); border-radius: 8px; color: var(--text-secondary);"><i data-feather="loader" style="width: 14px; height: 14px; display: inline; animation: spin 1s linear infinite;"></i> Loading… (up to 10 s while EA processes)</div>';
    feather.replace();

    fetch(url)
        .then(r => r.json())
        .then(data => {
            const payload = data.arrissa_data && data.arrissa_data.payload;
            if (data.arrissa_data && (data.arrissa_data.error || (payload && payload.error))) {
                const err = data.arrissa_data.error || payload.error;
                const msg = data.arrissa_data.message || '';
                responseDiv.innerHTML = '<div style="padding: 12px; background-color: rgba(244,67,54,0.1); border-radius: 8px; border: 1px solid #F44336; color: #F44336;"><div style="font-weight:600;margin-bottom:8px;"><i data-feather="alert-triangle" style="width:14px;height:14px;display:inline;"></i> ' + err + '</div>' + (msg ? '<div style="font-size:0.75rem;opacity:0.9;">' + msg + '</div>' : '') + '</div>';
                feather.replace();
            } else if (payload && payload.bias) {
                const isBull = payload.bias === 'BULLISH';
                const biasColor = isBull ? '#10B981' : '#F44336';
                const biasIcon = isBull ? '▲' : '▼';
                responseDiv.innerHTML = '<div style="padding: 12px; background-color: var(--bg-primary); border-radius: 8px; border: 1px solid var(--border);">'
                    + '<div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">'
                    + '<div style="font-size:1.5rem;font-weight:900;color:' + biasColor + ';">' + biasIcon + ' ' + payload.bias + '</div>'
                    + '<div style="font-size:0.75rem;color:var(--text-secondary);">' + payload.timeframe + ' · ' + payload.symbol + '</div>'
                    + '</div>'
                    + '<pre style="margin:0;max-height:300px;overflow-y:auto;color:var(--text-secondary);font-size:0.75rem;">' + JSON.stringify(data, null, 2) + '</pre></div>';
            } else {
                responseDiv.innerHTML = '<div style="padding: 12px; background-color: var(--bg-primary); border-radius: 8px; border: 1px solid var(--border);"><pre style="margin:0;max-height:300px;overflow-y:auto;color:var(--text-secondary);font-size:0.75rem;">' + JSON.stringify(data, null, 2) + '</pre></div>';
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
