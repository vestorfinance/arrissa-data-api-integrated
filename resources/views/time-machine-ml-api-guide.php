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

$title = 'Time Machine ML API Guide';
$page  = 'time-machine-ml-api-guide';
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
    background: linear-gradient(135deg, rgba(14, 165, 233, 0.1) 0%, rgba(99, 102, 241, 0.1) 100%);
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
    border-left: 3px solid #0EA5E9;
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
    background: linear-gradient(135deg, #0EA5E9, #6366F1);
    color: white;
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}
</style>

<div class="p-8 max-w-[1600px] mx-auto">

    <!-- EA Requirement Notice -->
    <div class="mb-6 p-5 rounded-2xl" style="background-color: rgba(14, 165, 233, 0.1); border: 1px solid #0EA5E9;">
        <div class="flex items-start">
            <div class="flex-shrink-0 mr-4">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, #0EA5E9, #6366F1);">
                    <i data-feather="alert-circle" style="width: 20px; height: 20px; color: white;"></i>
                </div>
            </div>
            <div class="flex-1">
                <h3 class="text-base font-semibold mb-2" style="color: var(--text-primary);">MT5 Expert Advisor Required</h3>
                <p class="text-sm mb-3" style="color: var(--text-secondary);">This API requires the <strong style="color: var(--text-primary);">TimeMachine ML API EA</strong> to be running on an MT5 chart. The EA polls every 2 seconds and builds the full JSON payload on demand for any requested symbol.</p>
                <a href="/download-eas" class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium transition-colors" style="background: linear-gradient(135deg, #0EA5E9, #6366F1); color: white;">
                    <i data-feather="download" class="mr-2" style="width: 16px; height: 16px;"></i>
                    Download Time Machine EA
                </a>
            </div>
        </div>
    </div>

    <!-- Hero Header -->
    <div class="mb-10">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-4xl font-bold mb-3 tracking-tight" style="color: var(--text-primary);">
                    Time Machine ML API
                    <span class="section-badge ml-3" style="background: linear-gradient(135deg, #0EA5E9, #6366F1); color: white;">v2.0</span>
                </h1>
                <p class="text-lg" style="color: var(--text-secondary);">On-demand H1 context snapshots for machine-learning models — live or historical via pretend time</p>
            </div>
        </div>

        <!-- Features Banner -->
        <div class="p-6 rounded-2xl gradient-bg" style="border: 1px solid var(--border);">
            <div class="flex items-start">
                <div class="flex-shrink-0 mr-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background: linear-gradient(135deg, #0EA5E9, #6366F1);">
                        <i data-feather="clock" style="width: 24px; height: 24px; color: white;"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">What is the Time Machine ML API?</h3>
                    <p class="text-sm mb-4" style="color: var(--text-secondary);">A broker-time snapshot of the current H1 context designed as a feature vector for ML inference. Every API call triggers the EA to build the payload in real time directly from MT5's tick data. Use pretend time to replay any historical moment for backtesting or model training.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm" style="color: var(--text-secondary);">
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #0EA5E9;"></i>
                            <span><strong>3 Previous H1 Candles:</strong> Full OHLC for context leading into the current hour</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #0EA5E9;"></i>
                            <span><strong>5 Pre-Hour M1 Bars:</strong> High/Low of the 5 M1 bars just before the current H1 opened</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #0EA5E9;"></i>
                            <span><strong>Developing H1 Range:</strong> Running high/low plus recent close context within current hour</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #0EA5E9;"></i>
                            <span><strong>ATR14 (H1):</strong> Confirmed volatility measure on the H1 timeframe</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payload Fields Visual -->
    <div class="mb-8 p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
        <h3 class="text-xl font-semibold mb-6" style="color: var(--text-primary);">Payload Structure Overview</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                <div class="payload-field-badge mb-3">Context</div>
                <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Symbol & Time</h4>
                <ul class="text-xs space-y-1" style="color: var(--text-secondary);">
                    <li><code>symbol</code></li>
                    <li><code>broker_time</code></li>
                    <li><code>h1_open_time</code></li>
                    <li><code>h1_open</code></li>
                    <li><code>atr14</code></li>
                </ul>
            </div>
            <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                <div class="payload-field-badge mb-3">History</div>
                <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">prev_h1 ×3</h4>
                <ul class="text-xs space-y-1" style="color: var(--text-secondary);">
                    <li><code>open</code></li>
                    <li><code>high</code></li>
                    <li><code>low</code></li>
                    <li><code>close</code></li>
                    <li class="opacity-60">— for each of 3 bars</li>
                </ul>
            </div>
            <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                <div class="payload-field-badge mb-3">Transition</div>
                <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">pre_hour_m1 ×5</h4>
                <ul class="text-xs space-y-1" style="color: var(--text-secondary);">
                    <li><code>high</code></li>
                    <li><code>low</code></li>
                    <li class="opacity-60">— for each of 5 M1 bars</li>
                    <li class="opacity-60">— immediately before</li>
                    <li class="opacity-60">— the H1 opened</li>
                </ul>
            </div>
            <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                <div class="payload-field-badge mb-3">Now</div>
                <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">current_h1_m1</h4>
                <ul class="text-xs space-y-1" style="color: var(--text-secondary);">
                    <li><code>running_high</code></li>
                    <li><code>running_low</code></li>
                    <li><code>current_close</code></li>
                    <li><code>close_3_bars_ago</code></li>
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
                    <?php echo $baseUrl; ?>/time-machine-ml-api-v1/time-machine-ml-api.php
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
                        <td class="px-6 py-4"><code class="text-sm" style="color: #0EA5E9;">symbol</code></td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">string</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--success);">✅ Yes</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">Trading symbol (e.g. <code>EURUSD</code>, <code>XAUUSD</code>). Case-insensitive.</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td class="px-6 py-4"><code class="text-sm" style="color: #0EA5E9;">api_key</code></td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">string</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--success);">✅ Yes</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">Your API authentication key (also accepted as <code>X-Api-Key</code> header)</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td class="px-6 py-4"><code class="text-sm" style="color: #FF9800;">pretend_date</code></td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">string</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">❌ No</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">Historical date in <code>YYYY-MM-DD</code> format. EA calculates payload as if it were that date.</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4"><code class="text-sm" style="color: #FF9800;">pretend_time</code></td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">string</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">❌ No</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">Historical time in <code>H:MM</code> or <code>HH:MM</code> format (e.g. <code>10:17</code>). Combined with <code>pretend_date</code> for full backtesting control.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="divider"></div>

    <!-- cURL Examples -->
    <div class="mb-12">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, #0EA5E9, #6366F1);">
                <i data-feather="terminal" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">cURL Examples</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Copy and paste these commands into your terminal</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <!-- Live Examples -->
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">Live Data</h3>
                <div class="space-y-4">
                    <?php
                    $liveExamples = [
                        ['Get Time Machine payload for EUR/USD', 'EURUSD'],
                        ['Get Time Machine payload for Gold (XAU/USD)', 'XAUUSD'],
                        ['Get Time Machine payload for GBP/USD', 'GBPUSD'],
                        ['Get Time Machine payload for USD/JPY', 'USDJPY'],
                    ];
                    foreach ($liveExamples as $ex):
                        $url = "{$baseUrl}/time-machine-ml-api-v1/time-machine-ml-api.php?symbol={$ex[1]}&api_key={$apiKey}";
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

            <!-- Historical / Backtesting -->
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">
                    <span style="background: linear-gradient(135deg, #FF9800, #FFA726); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                        🕐 Historical Backtesting (Pretend Time)
                    </span>
                </h3>
                <div class="mb-4 p-4 rounded-lg" style="background: linear-gradient(135deg, rgba(255, 152, 0, 0.1), rgba(255, 167, 38, 0.1)); border-left: 3px solid #FF9800;">
                    <p class="text-sm mb-1" style="color: var(--text-primary);"><strong>Time Travel!</strong> Get the exact payload the EA would have built at any historical moment.</p>
                    <p class="text-xs" style="color: var(--text-secondary);">The EA uses <code>iBarShift</code> to offset all bar indices to the pretend timestamp — giving you the authentic H1 context as it existed then.</p>
                </div>
                <div class="space-y-4">
                    <?php
                    $histExamples = [
                        ['EURUSD at May 19 2025 10:17 AM (London open context)', 'EURUSD', '2025-05-19', '10:17'],
                        ['XAUUSD at NFP day Jan 10 2025 14:30 (New York open)', 'XAUUSD', '2025-01-10', '14:30'],
                        ['GBPUSD at Dec 25 2024 08:00 (Low liquidity context)', 'GBPUSD', '2024-12-25', '8:00'],
                    ];
                    foreach ($histExamples as $ex):
                        $url = "{$baseUrl}/time-machine-ml-api-v1/time-machine-ml-api.php?symbol={$ex[1]}&api_key={$apiKey}&pretend_date={$ex[2]}&pretend_time={$ex[3]}";
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
                        <strong>💡 Backtesting use cases:</strong>
                        <ul class="list-disc list-inside mt-2 space-y-1">
                            <li>Build ML training datasets by looping over historical timestamps</li>
                            <li>Replay the exact market context at any high-impact news event</li>
                            <li>Validate model predictions against known outcomes</li>
                            <li>Study how the H1 context looked at specific session opens (London, NY, Tokyo)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Interactive Test Examples -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-6" style="color: var(--text-primary);">Live Test</h2>
        <div class="grid grid-cols-1 gap-6">
            <?php
            $testExamples = [
                ['EUR/USD', 'EURUSD'],
                ['Gold (XAU/USD)', 'XAUUSD'],
                ['GBP/USD', 'GBPUSD'],
                ['USD/JPY', 'USDJPY'],
            ];
            foreach ($testExamples as $idx => $ex):
                $url = "{$baseUrl}/time-machine-ml-api-v1/time-machine-ml-api.php?symbol={$ex[1]}&api_key={$apiKey}";
            ?>
            <div class="p-6 rounded-2xl example-card cursor-pointer" style="background-color: var(--card-bg); border: 1px solid var(--border);" onclick="testAPI(event.currentTarget, '<?php echo htmlspecialchars($url); ?>')">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3" style="background: linear-gradient(135deg, #0EA5E9, #6366F1);">
                            <span class="text-sm font-bold text-white"><?php echo $idx + 1; ?></span>
                        </div>
                        <h3 class="text-lg font-semibold" style="color: var(--text-primary);">Time Machine Payload — <?php echo $ex[0]; ?></h3>
                    </div>
                    <button class="px-3 py-1 rounded-lg text-xs font-medium" style="background: linear-gradient(135deg, #0EA5E9, #6366F1); color: white;" onclick="event.stopPropagation(); testAPI(event.currentTarget.closest('.example-card'), '<?php echo htmlspecialchars($url); ?>')">
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
    "request_id": "tm_6844c3a1a2f4b3.59447851",
    "symbol": "EURUSD",
    "payload": {
      "symbol":       "EURUSD",
      "broker_time":  "2025-05-19 10:17:35",
      "h1_open_time": "2025-05-19 10:00:00",
      "h1_open":      1.08420,
      "atr14":        0.00045,

      "prev_h1": [
        { "open": 1.08380, "high": 1.08550, "low": 1.08310, "close": 1.08490 },
        { "open": 1.08290, "high": 1.08420, "low": 1.08180, "close": 1.08380 },
        { "open": 1.08410, "high": 1.08510, "low": 1.08250, "close": 1.08290 }
      ],

      "pre_hour_m1": [
        { "high": 1.08435, "low": 1.08410 },
        { "high": 1.08450, "low": 1.08418 },
        { "high": 1.08442, "low": 1.08415 },
        { "high": 1.08438, "low": 1.08408 },
        { "high": 1.08445, "low": 1.08412 }
      ],

      "current_h1_m1": {
        "running_high":     1.08521,
        "running_low":      1.08398,
        "current_close":    1.08498,
        "close_3_bars_ago": 1.08455
      }
    },
    "timestamp": "2025-05-19 10:17:36"
  }
}</pre>
            </div>
        </div>

        <div class="mt-6 p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <h3 class="text-base font-semibold mb-4" style="color: var(--text-primary);">Error / EA Not Running</h3>
            <div class="response-preview" style="color: var(--text-primary);">
<pre>HTTP 503
{
  "arrissa_data": {
    "error":   "MT5 Data Server not connected",
    "message": "No Expert Advisor is currently running to process this request.
                Attach the TimeMachine ML API EA in your MT5 terminal."
  }
}</pre>
            </div>
        </div>
    </div>

    <!-- Field Reference -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-6" style="color: var(--text-primary);">Field Reference</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-base font-semibold mb-4" style="color: var(--text-primary);">Top-Level Fields</h3>
                <div class="space-y-3 text-sm">
                    <div class="highlight-box">
                        <strong style="color: var(--text-primary);">symbol</strong>
                        <span style="color: var(--text-secondary);"> — Uppercase ticker as received from EA</span>
                    </div>
                    <div class="highlight-box">
                        <strong style="color: var(--text-primary);">broker_time</strong>
                        <span style="color: var(--text-secondary);"> — MT5 server time at moment of calculation</span>
                    </div>
                    <div class="highlight-box">
                        <strong style="color: var(--text-primary);">h1_open_time</strong>
                        <span style="color: var(--text-secondary);"> — Timestamp of the current H1 bar's open</span>
                    </div>
                    <div class="highlight-box">
                        <strong style="color: var(--text-primary);">h1_open</strong>
                        <span style="color: var(--text-secondary);"> — Open price of the current H1 bar</span>
                    </div>
                    <div class="highlight-box">
                        <strong style="color: var(--text-primary);">atr14</strong>
                        <span style="color: var(--text-secondary);"> — 14-period H1 ATR (confirmed, bar 1)</span>
                    </div>
                </div>
            </div>

            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-base font-semibold mb-4" style="color: var(--text-primary);">Array & Object Fields</h3>
                <div class="space-y-3 text-sm">
                    <div class="highlight-box">
                        <strong style="color: var(--text-primary);">prev_h1[0..2]</strong>
                        <span style="color: var(--text-secondary);"> — Last 3 completed H1 candles (open/high/low/close), newest first</span>
                    </div>
                    <div class="highlight-box">
                        <strong style="color: var(--text-primary);">pre_hour_m1[0..4]</strong>
                        <span style="color: var(--text-secondary);"> — 5 M1 bars immediately before the H1 opened (high/low only)</span>
                    </div>
                    <div class="highlight-box">
                        <strong style="color: var(--text-primary);">running_high / running_low</strong>
                        <span style="color: var(--text-secondary);"> — H1 bar's developing high/low so far</span>
                    </div>
                    <div class="highlight-box">
                        <strong style="color: var(--text-primary);">current_close</strong>
                        <span style="color: var(--text-secondary);"> — Close of M1 bar 1 (last completed M1)</span>
                    </div>
                    <div class="highlight-box">
                        <strong style="color: var(--text-primary);">close_3_bars_ago</strong>
                        <span style="color: var(--text-secondary);"> — Close of M1 bar 4 (momentum reference)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- EA Setup -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-6" style="color: var(--text-primary);">MT5 EA Setup</h2>
        <div class="p-6 rounded-2xl" style="background-color: rgba(255, 152, 0, 0.08); border: 1px solid #FF9800;">
            <div class="flex items-start">
                <div class="flex-shrink-0 mr-4">
                    <i data-feather="settings" style="width: 24px; height: 24px; color: #FF9800;"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-base font-semibold mb-3" style="color: var(--text-primary);">Configuration Steps</h3>
                    <ol class="space-y-2 text-sm" style="color: var(--text-secondary);">
                        <li>1. Download <code>TimeMachineAPI.mq5</code> from the <a href="/download-eas" style="color: #0EA5E9;">Download EAs</a> page</li>
                        <li>2. Open MT5 → File → Open Data Folder → place in <code>MQL5/Experts/</code></li>
                        <li>3. Open in MetaEditor and compile (F7)</li>
                        <li>4. Attach EA to <strong>any</strong> chart — it runs on a timer, not on the chart symbol</li>
                        <li>5. Enable WebRequest: Tools → Options → Expert Advisors</li>
                        <li>6. Add URL: <code><?php echo $baseUrl; ?>/time-machine-ml-api-v1/time-machine-ml-api.php</code></li>
                        <li>7. Set <code>AppBaseURL</code> in EA inputs if your server is not localhost</li>
                        <li>8. EA logs "TimeMachine ML API EA v2.01 initialized" when ready</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Best Practices -->
    <div class="mb-8 p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
        <h2 class="text-xl font-bold mb-4" style="color: var(--text-primary);">ML Integration Tips</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm" style="color: var(--text-secondary);">
            <div class="flex items-start">
                <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success);"></i>
                <span>Normalise prices relative to <code>h1_open</code> before feeding into models to remove price-level bias</span>
            </div>
            <div class="flex items-start">
                <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success);"></i>
                <span>Divide raw prices by <code>atr14</code> to create volatility-normalised features</span>
            </div>
            <div class="flex items-start">
                <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success);"></i>
                <span>Use <code>pretend_date</code> + <code>pretend_time</code> in a loop to build training datasets at scale</span>
            </div>
            <div class="flex items-start">
                <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success);"></i>
                <span><code>pre_hour_m1</code> captures the order-flow context just before the new hour — useful for entry model features</span>
            </div>
            <div class="flex items-start">
                <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success);"></i>
                <span>Derive momentum: <code>current_close − close_3_bars_ago</code> normalised by ATR</span>
            </div>
            <div class="flex items-start">
                <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success);"></i>
                <span>The EA can serve any symbol available on your broker — no need to attach to each chart separately</span>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="p-6 rounded-2xl" style="background: linear-gradient(135deg, rgba(14, 165, 233, 0.1), rgba(99, 102, 241, 0.1)); border: 1px solid var(--border);">
        <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">Ready to Get Started?</h3>
        <div class="flex flex-wrap gap-3">
            <a href="/download-eas" class="inline-flex items-center px-5 py-2.5 rounded-full text-sm font-medium" style="background: linear-gradient(135deg, #0EA5E9, #6366F1); color: white;">
                <i data-feather="download" class="mr-2" style="width: 16px; height: 16px;"></i>
                Download EA
            </a>
            <a href="/settings" class="inline-flex items-center px-5 py-2.5 rounded-full text-sm font-medium" style="background-color: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border);">
                <i data-feather="settings" class="mr-2" style="width: 16px; height: 16px;"></i>
                Configure API Settings
            </a>
            <a href="/quarters-theory-api-guide" class="inline-flex items-center px-5 py-2.5 rounded-full text-sm font-medium" style="background-color: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border);">
                <i data-feather="target" class="mr-2" style="width: 16px; height: 16px;"></i>
                Quarters Theory API
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
