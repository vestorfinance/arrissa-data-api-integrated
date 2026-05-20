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

$title = 'Risk Management API Guide';
$page  = 'risk-management-api-guide';
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
                <p class="text-sm mb-3" style="color: var(--text-secondary);">This API requires the <strong style="color: var(--text-primary);">Risk Management API EA</strong> to be running on an MT5 chart. The EA polls every second and computes optimal SL/TP on demand using ATR and swing-point analysis for any requested symbol, direction, and trade type.</p>
                <a href="/download-eas" class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium transition-colors" style="background: linear-gradient(135deg, #10B981, #0EA5E9); color: white;">
                    <i data-feather="download" class="mr-2" style="width: 16px; height: 16px;"></i>
                    Download Risk Management EA
                </a>
            </div>
        </div>
    </div>

    <!-- Hero Header -->
    <div class="mb-10">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-4xl font-bold mb-3 tracking-tight" style="color: var(--text-primary);">
                    Risk Management API
                    <span class="section-badge ml-3" style="background: linear-gradient(135deg, #10B981, #0EA5E9); color: white;">v1.0</span>
                </h1>
                <p class="text-lg" style="color: var(--text-secondary);">On-demand optimal SL &amp; TP calculator — ATR + swing-point analysis per trade type</p>
            </div>
        </div>

        <!-- Features Banner -->
        <div class="p-6 rounded-2xl gradient-bg" style="border: 1px solid var(--border);">
            <div class="flex items-start">
                <div class="flex-shrink-0 mr-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background: linear-gradient(135deg, #10B981, #0EA5E9);">
                        <i data-feather="shield" style="width: 24px; height: 24px; color: white;"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">What is the Risk Management API?</h3>
                    <p class="text-sm mb-4" style="color: var(--text-secondary);">Send a symbol, a trade direction (BUY or SELL), and a trade type (scalp, swing, long-term) — the EA responds with a statistically sound stop-loss and take-profit level derived from live MT5 data. The EA uses ATR(14) on the appropriate timeframe as a volatility baseline, then locates the most recent confirmed swing low (for BUY) or swing high (for SELL) and places the SL beyond that structure with a buffer. TP is set at the R:R ratio suited to each trade type.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm" style="color: var(--text-secondary);">
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #10B981;"></i>
                            <span><strong>Swing-Point SL:</strong> SL lands beyond the last confirmed swing structure, not in the middle of price action</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #10B981;"></i>
                            <span><strong>ATR Volatility Floor:</strong> Minimum SL distance enforced by current ATR so stops are never too tight</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #10B981;"></i>
                            <span><strong>Trade-Type Profiles:</strong> Scalp, swing, and long-term each use a dedicated timeframe, R:R, and lookback</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #10B981;"></i>
                            <span><strong>Pip Distances &amp; R:R Returned:</strong> Response includes absolute prices, pip values, and the actual ratio</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payload Fields Visual -->
    <div class="mb-8 p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
        <h3 class="text-xl font-semibold mb-6" style="color: var(--text-primary);">Response Payload Overview</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                <div class="payload-field-badge mb-3">Request</div>
                <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Trade Identity</h4>
                <ul class="text-xs space-y-1" style="color: var(--text-secondary);">
                    <li><code>symbol</code></li>
                    <li><code>direction</code></li>
                    <li><code>trade_type</code></li>
                    <li><code>entry_price</code></li>
                </ul>
            </div>
            <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                <div class="payload-field-badge mb-3">Levels</div>
                <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">SL &amp; TP Prices</h4>
                <ul class="text-xs space-y-1" style="color: var(--text-secondary);">
                    <li><code>sl</code></li>
                    <li><code>tp</code></li>
                    <li><code>sl_pips</code></li>
                    <li><code>tp_pips</code></li>
                </ul>
            </div>
            <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                <div class="payload-field-badge mb-3">Analysis</div>
                <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Metrics</h4>
                <ul class="text-xs space-y-1" style="color: var(--text-secondary);">
                    <li><code>rr_ratio</code></li>
                    <li><code>atr_value</code></li>
                </ul>
            </div>
            <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                <div class="payload-field-badge mb-3">Method</div>
                <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">SL Calculation</h4>
                <ul class="text-xs space-y-1" style="color: var(--text-secondary);">
                    <li><code>sl_method</code></li>
                    <li class="opacity-60">swing_low</li>
                    <li class="opacity-60">swing_high</li>
                    <li class="opacity-60">atr_based</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- SL/TP Methodology -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-6" style="color: var(--text-primary);">SL/TP Methodology by Trade Type</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">

            <!-- Scalp -->
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center mr-3" style="background: linear-gradient(135deg, #10B981, #0EA5E9);">
                        <i data-feather="zap" style="width: 18px; height: 18px; color: white;"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold" style="color: var(--text-primary);">Scalp</h3>
                        <span class="method-badge" style="background: rgba(16,185,129,0.15); color: #10B981;">trade_type=scalp</span>
                    </div>
                </div>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between py-2" style="border-bottom: 1px solid var(--border);">
                        <span style="color: var(--text-secondary);">Timeframe</span>
                        <strong style="color: var(--text-primary);">M1</strong>
                    </div>
                    <div class="flex justify-between py-2" style="border-bottom: 1px solid var(--border);">
                        <span style="color: var(--text-secondary);">ATR Period</span>
                        <strong style="color: var(--text-primary);">14</strong>
                    </div>
                    <div class="flex justify-between py-2" style="border-bottom: 1px solid var(--border);">
                        <span style="color: var(--text-secondary);">Swing Lookback</span>
                        <strong style="color: var(--text-primary);">10 bars</strong>
                    </div>
                    <div class="flex justify-between py-2" style="border-bottom: 1px solid var(--border);">
                        <span style="color: var(--text-secondary);">Min SL</span>
                        <strong style="color: var(--text-primary);">1.0 × ATR</strong>
                    </div>
                    <div class="flex justify-between py-2" style="border-bottom: 1px solid var(--border);">
                        <span style="color: var(--text-secondary);">ATR Buffer</span>
                        <strong style="color: var(--text-primary);">0.2 × ATR</strong>
                    </div>
                    <div class="flex justify-between py-2">
                        <span style="color: var(--text-secondary);">R:R Ratio</span>
                        <strong style="color: #10B981;">1.5 : 1</strong>
                    </div>
                </div>
            </div>

            <!-- Swing -->
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center mr-3" style="background: linear-gradient(135deg, #0EA5E9, #6366F1);">
                        <i data-feather="bar-chart-2" style="width: 18px; height: 18px; color: white;"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold" style="color: var(--text-primary);">Swing</h3>
                        <span class="method-badge" style="background: rgba(14,165,233,0.15); color: #0EA5E9;">trade_type=swing</span>
                    </div>
                </div>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between py-2" style="border-bottom: 1px solid var(--border);">
                        <span style="color: var(--text-secondary);">Timeframe</span>
                        <strong style="color: var(--text-primary);">M15</strong>
                    </div>
                    <div class="flex justify-between py-2" style="border-bottom: 1px solid var(--border);">
                        <span style="color: var(--text-secondary);">ATR Period</span>
                        <strong style="color: var(--text-primary);">14</strong>
                    </div>
                    <div class="flex justify-between py-2" style="border-bottom: 1px solid var(--border);">
                        <span style="color: var(--text-secondary);">Swing Lookback</span>
                        <strong style="color: var(--text-primary);">20 bars</strong>
                    </div>
                    <div class="flex justify-between py-2" style="border-bottom: 1px solid var(--border);">
                        <span style="color: var(--text-secondary);">Min SL</span>
                        <strong style="color: var(--text-primary);">1.5 × ATR</strong>
                    </div>
                    <div class="flex justify-between py-2" style="border-bottom: 1px solid var(--border);">
                        <span style="color: var(--text-secondary);">ATR Buffer</span>
                        <strong style="color: var(--text-primary);">0.3 × ATR</strong>
                    </div>
                    <div class="flex justify-between py-2">
                        <span style="color: var(--text-secondary);">R:R Ratio</span>
                        <strong style="color: #0EA5E9;">2.5 : 1</strong>
                    </div>
                </div>
            </div>

            <!-- Long-term -->
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center mr-3" style="background: linear-gradient(135deg, #6366F1, #8B5CF6);">
                        <i data-feather="trending-up" style="width: 18px; height: 18px; color: white;"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold" style="color: var(--text-primary);">Long-term</h3>
                        <span class="method-badge" style="background: rgba(99,102,241,0.15); color: #6366F1;">trade_type=long-term</span>
                    </div>
                </div>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between py-2" style="border-bottom: 1px solid var(--border);">
                        <span style="color: var(--text-secondary);">Timeframe</span>
                        <strong style="color: var(--text-primary);">M30</strong>
                    </div>
                    <div class="flex justify-between py-2" style="border-bottom: 1px solid var(--border);">
                        <span style="color: var(--text-secondary);">ATR Period</span>
                        <strong style="color: var(--text-primary);">14</strong>
                    </div>
                    <div class="flex justify-between py-2" style="border-bottom: 1px solid var(--border);">
                        <span style="color: var(--text-secondary);">Swing Lookback</span>
                        <strong style="color: var(--text-primary);">30 bars</strong>
                    </div>
                    <div class="flex justify-between py-2" style="border-bottom: 1px solid var(--border);">
                        <span style="color: var(--text-secondary);">Min SL</span>
                        <strong style="color: var(--text-primary);">2.0 × ATR</strong>
                    </div>
                    <div class="flex justify-between py-2" style="border-bottom: 1px solid var(--border);">
                        <span style="color: var(--text-secondary);">ATR Buffer</span>
                        <strong style="color: var(--text-primary);">0.5 × ATR</strong>
                    </div>
                    <div class="flex justify-between py-2">
                        <span style="color: var(--text-secondary);">R:R Ratio</span>
                        <strong style="color: #6366F1;">3.5 : 1</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- SL / TP Method Explanation -->
        <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <h3 class="text-base font-semibold mb-2" style="color: var(--text-primary);">Core Philosophy</h3>
            <p class="text-sm mb-5" style="color: var(--text-secondary);">R:R is <strong style="color: var(--text-primary);">never used to place TP</strong>. Both SL and TP are anchored to real market structure. R:R is reported as a result so you know what the market is offering — not as a target you force.</p>

            <h4 class="text-sm font-semibold mb-3" style="color: var(--text-primary);">SL methods — <span style="color: var(--text-secondary); font-weight: 400;">placed just BEYOND the level that invalidates the trade</span></h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
                <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border-left: 3px solid #F44336;">
                    <div class="flex items-center mb-2">
                        <span class="method-badge mr-2" style="background: rgba(244,67,54,0.15); color: #F44336;">swing_low</span>
                        <span class="text-xs" style="color: var(--text-secondary);">BUY trades</span>
                    </div>
                    <p class="text-xs" style="color: var(--text-secondary);">Nearest confirmed swing LOW below entry. If price breaks this, the setup is invalid. SL = that_low − buffer. This is the structurally correct invalidation point.</p>
                </div>
                <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border-left: 3px solid #F44336;">
                    <div class="flex items-center mb-2">
                        <span class="method-badge mr-2" style="background: rgba(244,67,54,0.15); color: #F44336;">swing_high</span>
                        <span class="text-xs" style="color: var(--text-secondary);">SELL trades</span>
                    </div>
                    <p class="text-xs" style="color: var(--text-secondary);">Nearest confirmed swing HIGH above entry. If price breaks above this, the sell premise is wrong. SL = that_high + buffer.</p>
                </div>
                <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border-left: 3px solid #FF9800;">
                    <div class="flex items-center mb-2">
                        <span class="method-badge mr-2" style="background: rgba(255,152,0,0.15); color: #FF9800;">atr_based / atr_floor</span>
                    </div>
                    <p class="text-xs" style="color: var(--text-secondary);"><code>atr_based</code>: no qualifying swing in the lookback — pure ATR fallback. <code>atr_floor</code>: swing found but was too close to entry; ATR minimum applied instead.</p>
                </div>
            </div>

            <h4 class="text-sm font-semibold mb-3" style="color: var(--text-primary);">TP methods — <span style="color: var(--text-secondary); font-weight: 400;">placed just BEFORE the level the market is likely to turn</span></h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border-left: 3px solid #10B981;">
                    <div class="flex items-center mb-2">
                        <span class="method-badge mr-2" style="background: rgba(16,185,129,0.15); color: #10B981;">swing_high</span>
                        <span class="text-xs" style="color: var(--text-secondary);">BUY trades</span>
                    </div>
                    <p class="text-xs" style="color: var(--text-secondary);">Nearest confirmed swing HIGH above entry — the resistance where the market previously rejected. TP = that_high − buffer. Take profit before the rejection zone, not into it.</p>
                </div>
                <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border-left: 3px solid #10B981;">
                    <div class="flex items-center mb-2">
                        <span class="method-badge mr-2" style="background: rgba(16,185,129,0.15); color: #10B981;">swing_low</span>
                        <span class="text-xs" style="color: var(--text-secondary);">SELL trades</span>
                    </div>
                    <p class="text-xs" style="color: var(--text-secondary);">Nearest confirmed swing LOW below entry — the support where the market previously bounced. TP = that_low + buffer. Exit before the bounce, not after it.</p>
                </div>
                <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border-left: 3px solid #FF9800;">
                    <div class="flex items-center mb-2">
                        <span class="method-badge mr-2" style="background: rgba(255,152,0,0.15); color: #FF9800;">fallback_rr</span>
                    </div>
                    <p class="text-xs" style="color: var(--text-secondary);">No qualifying TP structure found in the lookback window. A multiple of SL distance is used as a reasonable minimum target. <em>Consider waiting for clearer structure before entering.</em></p>
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
                    <?php echo $baseUrl; ?>/risk-management-api-v1/risk-management-api.php
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
                        <td class="px-6 py-4 text-sm" style="color: var(--success);">✅ Yes</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">Trading symbol (e.g. <code>GBPUSD</code>, <code>XAUUSD</code>). Case-insensitive.</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td class="px-6 py-4"><code class="text-sm" style="color: #10B981;">direction</code></td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">string</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--success);">✅ Yes</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">Trade direction: <code>BUY</code> or <code>SELL</code>. Determines swing low vs swing high for SL placement.</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td class="px-6 py-4"><code class="text-sm" style="color: #10B981;">trade_type</code></td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">string</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--success);">✅ Yes</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">One of <code>scalp</code>, <code>swing</code>, or <code>long-term</code>. Selects timeframe, ATR multipliers, and R:R profile.</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td class="px-6 py-4"><code class="text-sm" style="color: #10B981;">api_key</code></td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">string</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--success);">✅ Yes</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">Your API authentication key (also accepted as <code>X-Api-Key</code> header).</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td class="px-6 py-4"><code class="text-sm" style="color: #FF9800;">pretend_date</code></td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">string</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">❌ No</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">Historical date in <code>YYYY-MM-DD</code> format. EA calculates SL/TP as if it were that date — all bar reads are offset via <code>iBarShift()</code>.</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4"><code class="text-sm" style="color: #FF9800;">pretend_time</code></td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">string</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">❌ No</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">Historical time in <code>H:MM</code> or <code>HH:MM</code> format (e.g. <code>09:30</code>). Combined with <code>pretend_date</code> for full historical control. Defaults to <code>00:00</code> if omitted.</td>
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
            <!-- Scalp Examples -->
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">
                    <span class="method-badge mr-2" style="background: rgba(16,185,129,0.15); color: #10B981;">scalp</span>
                    Scalp Trades (M1)
                </h3>
                <div class="space-y-4">
                    <?php
                    $scalp = [
                        ['EUR/USD — Scalp BUY',  'EURUSD', 'BUY',  'scalp'],
                        ['GBP/USD — Scalp SELL', 'GBPUSD', 'SELL', 'scalp'],
                        ['USD/JPY — Scalp BUY',  'USDJPY', 'BUY',  'scalp'],
                    ];
                    foreach ($scalp as $ex):
                        $url = "{$baseUrl}/risk-management-api-v1/risk-management-api.php?symbol={$ex[1]}&direction={$ex[2]}&trade_type={$ex[3]}&api_key={$apiKey}";
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

            <!-- Swing Examples -->
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">
                    <span class="method-badge mr-2" style="background: rgba(14,165,233,0.15); color: #0EA5E9;">swing</span>
                    Swing Trades (M15)
                </h3>
                <div class="space-y-4">
                    <?php
                    $swing = [
                        ['GBP/USD — Swing BUY',   'GBPUSD', 'BUY',  'swing'],
                        ['EUR/USD — Swing SELL',   'EURUSD', 'SELL', 'swing'],
                        ['XAU/USD — Swing BUY',   'XAUUSD', 'BUY',  'swing'],
                    ];
                    foreach ($swing as $ex):
                        $url = "{$baseUrl}/risk-management-api-v1/risk-management-api.php?symbol={$ex[1]}&direction={$ex[2]}&trade_type={$ex[3]}&api_key={$apiKey}";
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

            <!-- Long-term Examples -->
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">
                    <span class="method-badge mr-2" style="background: rgba(99,102,241,0.15); color: #6366F1;">long-term</span>
                    Long-Term Trades (M30)
                </h3>
                <div class="space-y-4">
                    <?php
                    $longterm = [
                        ['XAU/USD — Long-term SELL', 'XAUUSD', 'SELL', 'long-term'],
                        ['GBP/USD — Long-term BUY',  'GBPUSD', 'BUY',  'long-term'],
                        ['EUR/USD — Long-term SELL', 'EURUSD', 'SELL', 'long-term'],
                    ];
                    foreach ($longterm as $ex):
                        $url = "{$baseUrl}/risk-management-api-v1/risk-management-api.php?symbol={$ex[1]}&direction={$ex[2]}&trade_type={$ex[3]}&api_key={$apiKey}";
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
        </div>
    </div>

            <!-- Historical / Backtesting -->
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">
                    <span style="background: linear-gradient(135deg, #FF9800, #FFA726); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                        Historical Backtesting (Pretend Date &amp; Time)
                    </span>
                </h3>
                <div class="mb-4 p-4 rounded-lg" style="background: linear-gradient(135deg, rgba(255,152,0,0.1), rgba(255,167,38,0.1)); border-left: 3px solid #FF9800;">
                    <p class="text-sm mb-1" style="color: var(--text-primary);"><strong>Time Travel!</strong> Get the exact SL/TP levels that would have been calculated at any past moment.</p>
                    <p class="text-xs" style="color: var(--text-secondary);">The EA uses <code>iBarShift()</code> to offset all bar indices to the pretend timestamp — entry price, ATR, and every swing scan are anchored to that historical moment.</p>
                </div>
                <div class="space-y-4">
                    <?php
                    $histExamples = [
                        ['GBPUSD swing BUY — London open May 19 2025 09:00',   'GBPUSD', 'BUY',  'swing',     '2025-05-19', '9:00'],
                        ['XAUUSD long-term SELL — NFP day Jan 10 2025 14:30',  'XAUUSD', 'SELL', 'long-term', '2025-01-10', '14:30'],
                        ['EURUSD scalp BUY — NY open Mar 3 2025 14:00',        'EURUSD', 'BUY',  'scalp',     '2025-03-03', '14:00'],
                        ['USDJPY swing SELL — Tokyo open Apr 7 2025 00:00',    'USDJPY', 'SELL', 'swing',     '2025-04-07', '0:00'],
                    ];
                    foreach ($histExamples as $ex):
                        $url = "{$baseUrl}/risk-management-api-v1/risk-management-api.php?symbol={$ex[1]}&direction={$ex[2]}&trade_type={$ex[3]}&pretend_date={$ex[4]}&pretend_time={$ex[5]}&api_key={$apiKey}";
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
                            <li>Validate whether the structurally-placed SL/TP would have worked at a specific setup</li>
                            <li>Study how swing structures looked at key session opens (London, NY, Tokyo)</li>
                            <li>Build historical datasets for strategy research and model training</li>
                            <li>Replay high-impact news events to see what levels the EA would have given</li>
                        </ul>
                    </div>
                </div>
            </div>

    <!-- Interactive Live Test -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-6" style="color: var(--text-primary);">Live Test</h2>
        <div class="grid grid-cols-1 gap-6">
            <?php
            $testExamples = [
                ['GBP/USD — Swing BUY',  'GBPUSD', 'BUY',  'swing'],
                ['EUR/USD — Scalp SELL', 'EURUSD', 'SELL', 'scalp'],
                ['XAU/USD — Long-term BUY', 'XAUUSD', 'BUY', 'long-term'],
                ['USD/JPY — Scalp BUY',  'USDJPY', 'BUY',  'scalp'],
            ];
            foreach ($testExamples as $idx => $ex):
                $url = "{$baseUrl}/risk-management-api-v1/risk-management-api.php?symbol={$ex[1]}&direction={$ex[2]}&trade_type={$ex[3]}&api_key={$apiKey}";
            ?>
            <div class="p-6 rounded-2xl example-card cursor-pointer" style="background-color: var(--card-bg); border: 1px solid var(--border);" onclick="testAPI(event.currentTarget, '<?php echo htmlspecialchars($url); ?>')">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3" style="background: linear-gradient(135deg, #10B981, #0EA5E9);">
                            <span class="text-sm font-bold text-white"><?php echo $idx + 1; ?></span>
                        </div>
                        <h3 class="text-lg font-semibold" style="color: var(--text-primary);"><?php echo $ex[0]; ?></h3>
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
        <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <h3 class="text-base font-semibold mb-4" style="color: var(--text-primary);">Success Response</h3>
            <div class="response-preview" style="color: var(--text-primary);">
<pre>{
  "arrissa_data": {
    "request_id": "rm_6834f2abc1234.5678",
    "symbol": "GBPUSD",
    "payload": {
      "symbol":           "GBPUSD",
      "direction":        "BUY",
      "trade_type":       "swing",
      "pretend_datetime": "",          // empty = live; "2025-05-19 09:00:00" = historical
      "entry_price":      1.27345,
      "sl":          1.26790,   // just beyond the swing low that invalidates the trade
      "tp":          1.28490,   // just before the nearest resistance the market may reject at
      "sl_pips":     55.5,
      "tp_pips":     114.5,
      "rr_ratio":    2.06,      // actual R:R from structure — reported, never forced
      "atr_value":   0.00890,
      "sl_method":   "swing_low",   // SL anchored to real structural invalidation
      "tp_method":   "swing_high"   // TP anchored to real structural turning point
    },
    "timestamp": "2026-05-20 14:32:11"
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
                            <td class="px-5 py-3"><code style="color: #10B981;">pretend_datetime</code></td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">string</td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">The pretend timestamp used (e.g. <code>"2025-05-19 09:00:00"</code>). Empty string in live mode.</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="px-5 py-3"><code style="color: #10B981;">entry_price</code></td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">float</td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">Current ask (BUY) or bid (SELL) used as the reference entry point</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="px-5 py-3"><code style="color: #10B981;">sl</code></td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">float</td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">Absolute stop-loss price ready to paste into your order</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="px-5 py-3"><code style="color: #10B981;">tp</code></td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">float</td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">Absolute take-profit price ready to paste into your order</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="px-5 py-3"><code style="color: #10B981;">sl_pips</code></td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">float</td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">SL distance in pips (accounts for 3/5-digit broker normalisation)</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="px-5 py-3"><code style="color: #10B981;">tp_pips</code></td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">float</td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">TP distance in pips</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="px-5 py-3"><code style="color: #10B981;">rr_ratio</code></td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">float</td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">The actual R:R the market structure delivers — calculated from SL and TP positions, never used to set TP</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="px-5 py-3"><code style="color: #10B981;">atr_value</code></td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">float</td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">ATR(14) on the trade-type timeframe — the volatility baseline used</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="px-5 py-3"><code style="color: #10B981;">sl_method</code></td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">string</td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);"><code>swing_low</code> / <code>swing_high</code> — SL placed beyond a structural invalidation level. <code>atr_floor</code> — swing too close, ATR minimum applied. <code>atr_based</code> — no swing found, pure ATR fallback.</td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3"><code style="color: #10B981;">tp_method</code></td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">string</td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);"><code>swing_high</code> (BUY) / <code>swing_low</code> (SELL) — TP placed just before the nearest structural turning point. <code>fallback_rr</code> — no TP structure found, a minimum target multiple was used instead.</td>
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
                    <p class="text-xs" style="color: var(--text-secondary);">EA is not running or did not respond within 10 seconds. Attach <code>RiskManagementAPI.mq5</code> to any MT5 chart.</p>
                </div>
                <div class="p-4 rounded-xl" style="background-color: rgba(244,67,54,0.08); border: 1px solid rgba(244,67,54,0.3);">
                    <div class="font-mono text-xs mb-2" style="color: #F44336;">HTTP 400 — Invalid direction</div>
                    <p class="text-xs" style="color: var(--text-secondary);"><code>direction</code> must be exactly <code>BUY</code> or <code>SELL</code>.</p>
                </div>
                <div class="p-4 rounded-xl" style="background-color: rgba(244,67,54,0.08); border: 1px solid rgba(244,67,54,0.3);">
                    <div class="font-mono text-xs mb-2" style="color: #F44336;">HTTP 400 — Invalid trade_type</div>
                    <p class="text-xs" style="color: var(--text-secondary);"><code>trade_type</code> must be <code>scalp</code>, <code>swing</code>, or <code>long-term</code>.</p>
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
                        <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">sl_method tells you confidence</h4>
                        <p class="text-xs" style="color: var(--text-secondary);">When <code>sl_method</code> is <code>swing_low</code> or <code>swing_high</code>, your SL is behind a real price structure. When it falls back to <code>atr_based</code>, consider if the setup is ready — no clear swing may mean the market is in a choppy phase.</p>
                    </div>
                </div>
            </div>
            <div class="p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-start">
                    <i data-feather="info" class="mr-3 flex-shrink-0" style="width: 18px; height: 18px; color: #10B981; margin-top: 2px;"></i>
                    <div>
                        <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Position size from sl_pips</h4>
                        <p class="text-xs" style="color: var(--text-secondary);">Use <code>sl_pips</code> directly in your lot-size calculator. Position size = (Account risk $) ÷ (sl_pips × pip value per lot).</p>
                    </div>
                </div>
            </div>
            <div class="p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-start">
                    <i data-feather="info" class="mr-3 flex-shrink-0" style="width: 18px; height: 18px; color: #0EA5E9; margin-top: 2px;"></i>
                    <div>
                        <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">EA can serve any chart symbol</h4>
                        <p class="text-xs" style="color: var(--text-secondary);">The EA only needs to be attached to one chart. It reads market data for any requested symbol from MT5's internal feed — no need to open separate charts.</p>
                    </div>
                </div>
            </div>
            <div class="p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-start">
                    <i data-feather="info" class="mr-3 flex-shrink-0" style="width: 16px; height: 16px; color: #6366F1; margin-top: 2px;"></i>
                    <div>
                        <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Combine with Time Machine API</h4>
                        <p class="text-xs" style="color: var(--text-secondary);">Pair this API with the Time Machine ML API — use Time Machine to build your ML feature vector, make a prediction, then call Risk Management to size your SL/TP before sending the order.</p>
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
            <a href="/time-machine-ml-api-guide" class="inline-flex items-center px-5 py-2.5 rounded-full text-sm font-medium" style="background-color: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border);">
                <i data-feather="clock" class="mr-2" style="width: 16px; height: 16px;"></i>
                Time Machine ML API
            </a>
            <a href="/orders-api-guide" class="inline-flex items-center px-5 py-2.5 rounded-full text-sm font-medium" style="background-color: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border);">
                <i data-feather="shopping-cart" class="mr-2" style="width: 16px; height: 16px;"></i>
                Orders API
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
            if (data.arrissa_data && data.arrissa_data.error) {
                responseDiv.innerHTML = '<div style="padding: 12px; background-color: rgba(244,67,54,0.1); border-radius: 8px; border: 1px solid #F44336; color: #F44336;"><div style="font-weight:600;margin-bottom:8px;"><i data-feather="alert-triangle" style="width:14px;height:14px;display:inline;"></i> ' + data.arrissa_data.error + '</div>' + (data.arrissa_data.message ? '<div style="font-size:0.75rem;opacity:0.9;">' + data.arrissa_data.message + '</div>' : '') + '</div>';
                feather.replace();
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
