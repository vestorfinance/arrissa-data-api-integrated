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

$title = 'Richchild Quarters Theory API Guide';
$page = 'quarters-theory-api-guide';
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
    background: linear-gradient(135deg, rgba(156, 39, 176, 0.1) 0%, rgba(255, 152, 0, 0.1) 100%);
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
    border-left: 3px solid #9C27B0;
    padding-left: 1rem;
}
.quarter-visual {
    display: flex;
    height: 60px;
    border-radius: 12px;
    overflow: hidden;
    margin: 1.5rem 0;
}
.quarter-segment {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
    color: white;
    border-right: 2px solid var(--bg-primary);
}
.quarter-segment:last-child {
    border-right: none;
}
.q1 { background: linear-gradient(135deg, #4CAF50, #66BB6A); }
.q2 { background: linear-gradient(135deg, #2196F3, #42A5F5); }
.q3 { background: linear-gradient(135deg, #FF9800, #FFA726); }
.q4 { background: linear-gradient(135deg, #F44336, #EF5350); }
.timeframe-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 12px;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    background: linear-gradient(135deg, #9C27B0, #BA68C8);
    color: white;
}
.response-preview {
    background-color: var(--input-bg);
    border: 1px solid var(--input-border);
    border-radius: 16px;
    padding: 1.5rem;
    font-family: 'Fira Code', monospace;
    font-size: 0.8125rem;
    overflow-x: auto;
    max-height: 400px;
    overflow-y: auto;
}
.cursor-pointer {
    cursor: pointer;
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>

<div class="p-8 max-w-[1600px] mx-auto">
    <!-- EA Requirement Notice -->
    <div class="mb-6 p-5 rounded-2xl" style="background-color: rgba(156, 39, 176, 0.1); border: 1px solid #9C27B0;">
        <div class="flex items-start">
            <div class="flex-shrink-0 mr-4">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, #9C27B0, #BA68C8);">
                    <i data-feather="alert-circle" style="width: 20px; height: 20px; color: white;"></i>
                </div>
            </div>
            <div class="flex-1">
                <h3 class="text-base font-semibold mb-2" style="color: var(--text-primary);">MT5 Expert Advisor Required</h3>
                <p class="text-sm mb-3" style="color: var(--text-secondary);">This API requires the Richchild Quarters Theory EA to be running on an MT5 chart. The EA calculates quarter positions and provides real-time multi-timeframe analysis.</p>
                <a href="/download-eas" class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium transition-colors" style="background: linear-gradient(135deg, #9C27B0, #BA68C8); color: white;">
                    <i data-feather="download" class="mr-2" style="width: 16px; height: 16px;"></i>
                    Download Quarters Theory EA
                </a>
            </div>
        </div>
    </div>

    <!-- Hero Header -->
    <div class="mb-10">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-4xl font-bold mb-3 tracking-tight" style="color: var(--text-primary);">
                    Richchild Quarters Theory API
                    <span class="section-badge ml-3" style="background: linear-gradient(135deg, #9C27B0, #BA68C8); color: white;">v1.0</span>
                </h1>
                <p class="text-lg" style="color: var(--text-secondary);">Real-time multi-timeframe quarter analysis for precision trading entries and exits</p>
            </div>
        </div>
        
        <!-- Features Banner -->
        <div class="p-6 rounded-2xl gradient-bg" style="border: 1px solid var(--border);">
            <div class="flex items-start">
                <div class="flex-shrink-0 mr-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background: linear-gradient(135deg, #9C27B0, #BA68C8);">
                        <i data-feather="target" style="width: 24px; height: 24px; color: white;"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">What is Quarters Theory?</h3>
                    <p class="text-sm mb-4" style="color: var(--text-secondary);">Quarters Theory divides each period into four equal segments (quarters) and tracks price position relative to the period's high/low. This provides precise entry/exit timing by analyzing:</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm" style="color: var(--text-secondary);">
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #9C27B0;"></i>
                            <span><strong>Price Quarters:</strong> Where price sits within the period range (0-25%, 25-50%, 50-75%, 75-100%)</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #9C27B0;"></i>
                            <span><strong>Time Quarters:</strong> Which quarter of time the period is in (1st, 2nd, 3rd, 4th)</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #9C27B0;"></i>
                            <span><strong>Quota Values:</strong> Historical average range divided by 4 for profit targets</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #9C27B0;"></i>
                            <span><strong>9 Timeframes:</strong> From M15 to MN1 for multi-timeframe confluence</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quarters Visual Explanation -->
    <div class="mb-8 p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
        <h3 class="text-xl font-semibold mb-4" style="color: var(--text-primary);">Understanding Quarter Divisions</h3>
        
        <div class="mb-6">
            <h4 class="text-base font-semibold mb-3" style="color: var(--text-primary);">Price Quarters (Vertical Movement)</h4>
            <div class="quarter-visual">
                <div class="quarter-segment q1">1st Quarter<br>0-25%</div>
                <div class="quarter-segment q2">2nd Quarter<br>25-50%</div>
                <div class="quarter-segment q3">3rd Quarter<br>50-75%</div>
                <div class="quarter-segment q4">4th Quarter<br>75-100%</div>
            </div>
            <ul class="text-sm space-y-2" style="color: var(--text-secondary);">
                <li><strong>Low %:</strong> Shows how many quotas UP from period low (positive values)</li>
                <li><strong>High %:</strong> Shows how many quotas DOWN from period high (negative values)</li>
                <li><strong>Example:</strong> Low%=75 means price is in 3rd quarter (75% up from low)</li>
            </ul>
        </div>

        <div>
            <h4 class="text-base font-semibold mb-3" style="color: var(--text-primary);">Time Quarters (Horizontal Progression)</h4>
            <div class="quarter-visual">
                <div class="quarter-segment q1">1st Quarter<br>0-25% time</div>
                <div class="quarter-segment q2">2nd Quarter<br>25-50% time</div>
                <div class="quarter-segment q3">3rd Quarter<br>50-75% time</div>
                <div class="quarter-segment q4">4th Quarter<br>75-100% time</div>
            </div>
            <ul class="text-sm space-y-2" style="color: var(--text-secondary);">
                <li><strong>Time Quarter:</strong> Shows which time segment of the period we're in</li>
                <li><strong>Countdown:</strong> Time remaining until period closes</li>
                <li><strong>Strategy:</strong> Price in 4th time quarter + extreme price quarter = potential reversal</li>
            </ul>
        </div>
    </div>

    <div class="divider"></div>

    <!-- API Endpoint Section -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-6" style="color: var(--text-primary);">API Endpoint</h2>
        
        <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="mb-4">
                <label class="text-sm font-medium mb-2 block" style="color: var(--text-secondary);">Base URL</label>
                <div class="p-4 rounded-xl font-mono text-sm" style="background-color: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-primary);">
                    <?php echo $baseUrl; ?>/quarters-theory-api-v1/quarters-theory-api.php
                </div>
            </div>
            
            <div>
                <label class="text-sm font-medium mb-2 block" style="color: var(--text-secondary);">Your API Key</label>
                <div class="p-4 rounded-xl font-mono text-sm break-all" style="background-color: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-primary);">
                    <?php echo $apiKey ?: 'Not configured - Visit Settings'; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Supported Timeframes -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-6" style="color: var(--text-primary);">Supported Timeframes</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php 
            $timeframes = [
                ['M15', '15-Minute', 'Scalping & quick entries'],
                ['M30', '30-Minute', 'Scalping & intraday'],
                ['H1', '1-Hour', 'Intraday trading'],
                ['H4', '4-Hour', 'Swing trading'],
                ['H6', '6-Hour', 'Swing trading'],
                ['H12', '12-Hour', 'Position trading'],
                ['D1', 'Daily', 'Position trading'],
                ['W1', 'Weekly', 'Long-term analysis'],
                ['MN1', 'Monthly', 'Long-term analysis'],
            ];
            
            foreach ($timeframes as $tf): ?>
                <div class="p-5 rounded-2xl example-card" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                    <div class="timeframe-badge mb-3"><?php echo $tf[0]; ?></div>
                    <h4 class="text-base font-semibold mb-2" style="color: var(--text-primary);"><?php echo $tf[1]; ?></h4>
                    <p class="text-sm" style="color: var(--text-secondary);"><?php echo $tf[2]; ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="divider"></div>

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
                        <td class="px-6 py-4"><code class="text-sm" style="color: #9C27B0;">symbol</code></td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">string</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--success);">✅ Yes</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">Trading symbol (e.g., "EURUSD", "GBPUSD")</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td class="px-6 py-4"><code class="text-sm" style="color: #9C27B0;">api_key</code></td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">string</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--success);">✅ Yes</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">Your API authentication key</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td class="px-6 py-4"><code class="text-sm" style="color: #FF9800;">pretend_date</code></td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">string</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">❌ No</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">Historical date (YYYY-MM-DD format, e.g., "2026-01-02") for backtesting</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4"><code class="text-sm" style="color: #FF9800;">pretend_time</code></td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">string</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">❌ No</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">Historical time (H:MM or HH:MM format, e.g., "8:00" or "14:30") for backtesting</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- cURL Examples Section -->
    <div class="mb-12">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, #9C27B0, #BA68C8);">
                <i data-feather="terminal" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">cURL Examples</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Copy and paste these commands into your terminal</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <!-- Basic Quarters Analysis -->
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">Basic Quarters Analysis</h3>
                <div class="space-y-4">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold" style="color: var(--text-secondary);">Get Quarters Data for EURUSD</label>
                            <button onclick="copyToClipboard('curl &quot;<?= htmlspecialchars($baseUrl) ?>/quarters-theory-api-v1/quarters-theory-api.php?symbol=EURUSD&api_key=<?= htmlspecialchars($apiKey) ?>&quot;')" class="text-xs px-2 py-1 rounded-lg transition-colors flex items-center gap-1" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);">
                                <i data-feather="copy" style="width: 12px; height: 12px;"></i>
                                Copy
                            </button>
                        </div>
                        <div class="p-3 rounded-lg" style="background-color: var(--input-bg); border: 1px solid var(--input-border); font-family: 'Fira Code', monospace; font-size: 0.75rem; overflow-x: auto;">
                            <code style="color: var(--text-primary);">curl "<?= htmlspecialchars($baseUrl) ?>/quarters-theory-api-v1/quarters-theory-api.php?symbol=EURUSD&api_key=<?= htmlspecialchars($apiKey) ?>"</code>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold" style="color: var(--text-secondary);">Get Quarters Data for XAUUSD (Gold)</label>
                            <button onclick="copyToClipboard('curl &quot;<?= htmlspecialchars($baseUrl) ?>/quarters-theory-api-v1/quarters-theory-api.php?symbol=XAUUSD&api_key=<?= htmlspecialchars($apiKey) ?>&quot;')" class="text-xs px-2 py-1 rounded-lg transition-colors flex items-center gap-1" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);">
                                <i data-feather="copy" style="width: 12px; height: 12px;"></i>
                                Copy
                            </button>
                        </div>
                        <div class="p-3 rounded-lg" style="background-color: var(--input-bg); border: 1px solid var(--input-border); font-family: 'Fira Code', monospace; font-size: 0.75rem; overflow-x: auto;">
                            <code style="color: var(--text-primary);">curl "<?= htmlspecialchars($baseUrl) ?>/quarters-theory-api-v1/quarters-theory-api.php?symbol=XAUUSD&api_key=<?= htmlspecialchars($apiKey) ?>"</code>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold" style="color: var(--text-secondary);">Get Quarters Data for GBPUSD</label>
                            <button onclick="copyToClipboard('curl &quot;<?= htmlspecialchars($baseUrl) ?>/quarters-theory-api-v1/quarters-theory-api.php?symbol=GBPUSD&api_key=<?= htmlspecialchars($apiKey) ?>&quot;')" class="text-xs px-2 py-1 rounded-lg transition-colors flex items-center gap-1" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);">
                                <i data-feather="copy" style="width: 12px; height: 12px;"></i>
                                Copy
                            </button>
                        </div>
                        <div class="p-3 rounded-lg" style="background-color: var(--input-bg); border: 1px solid var(--input-border); font-family: 'Fira Code', monospace; font-size: 0.75rem; overflow-x: auto;">
                            <code style="color: var(--text-primary);">curl "<?= htmlspecialchars($baseUrl) ?>/quarters-theory-api-v1/quarters-theory-api.php?symbol=GBPUSD&api_key=<?= htmlspecialchars($apiKey) ?>"</code>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold" style="color: var(--text-secondary);">Get Quarters Data for USDJPY</label>
                            <button onclick="copyToClipboard('curl &quot;<?= htmlspecialchars($baseUrl) ?>/quarters-theory-api-v1/quarters-theory-api.php?symbol=USDJPY&api_key=<?= htmlspecialchars($apiKey) ?>&quot;')" class="text-xs px-2 py-1 rounded-lg transition-colors flex items-center gap-1" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);">
                                <i data-feather="copy" style="width: 12px; height: 12px;"></i>
                                Copy
                            </button>
                        </div>
                        <div class="p-3 rounded-lg" style="background-color: var(--input-bg); border: 1px solid var(--input-border); font-family: 'Fira Code', monospace; font-size: 0.75rem; overflow-x: auto;">
                            <code style="color: var(--text-primary);">curl "<?= htmlspecialchars($baseUrl) ?>/quarters-theory-api-v1/quarters-theory-api.php?symbol=USDJPY&api_key=<?= htmlspecialchars($apiKey) ?>"</code>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Historical Analysis Example -->
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">
                    <span style="background: linear-gradient(135deg, #FF9800, #FFA726); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                        🕐 Historical Analysis (Backtesting)
                    </span>
                </h3>
                <div class="mb-4 p-4 rounded-lg" style="background: linear-gradient(135deg, rgba(255, 152, 0, 0.1), rgba(255, 167, 38, 0.1)); border-left: 3px solid #FF9800;">
                    <p class="text-sm mb-2" style="color: var(--text-primary);"><strong>New Feature!</strong> Analyze historical quarters data for backtesting your strategies.</p>
                    <p class="text-xs" style="color: var(--text-secondary);">Use <code>pretend_date</code> and <code>pretend_time</code> parameters to get quarters data as if it was that specific historical moment.</p>
                </div>
                <div class="space-y-4">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold" style="color: var(--text-secondary);">Historical Analysis - January 2nd, 2026 at 8:00 AM</label>
                            <button onclick="copyToClipboard('curl &quot;<?= htmlspecialchars($baseUrl) ?>/quarters-theory-api-v1/quarters-theory-api.php?symbol=EURUSD&api_key=<?= htmlspecialchars($apiKey) ?>&pretend_date=2026-01-02&pretend_time=8:00&quot;')" class="text-xs px-2 py-1 rounded-lg transition-colors flex items-center gap-1" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);">
                                <i data-feather="copy" style="width: 12px; height: 12px;"></i>
                                Copy
                            </button>
                        </div>
                        <div class="p-3 rounded-lg" style="background-color: var(--input-bg); border: 1px solid var(--input-border); font-family: 'Fira Code', monospace; font-size: 0.75rem; overflow-x: auto;">
                            <code style="color: var(--text-primary);">curl "<?= htmlspecialchars($baseUrl) ?>/quarters-theory-api-v1/quarters-theory-api.php?symbol=EURUSD&api_key=<?= htmlspecialchars($apiKey) ?>&pretend_date=2026-01-02&pretend_time=8:00"</code>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold" style="color: var(--text-secondary);">Historical Analysis - December 25th, 2025 at 14:30</label>
                            <button onclick="copyToClipboard('curl &quot;<?= htmlspecialchars($baseUrl) ?>/quarters-theory-api-v1/quarters-theory-api.php?symbol=XAUUSD&api_key=<?= htmlspecialchars($apiKey) ?>&pretend_date=2025-12-25&pretend_time=14:30&quot;')" class="text-xs px-2 py-1 rounded-lg transition-colors flex items-center gap-1" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);">
                                <i data-feather="copy" style="width: 12px; height: 12px;"></i>
                                Copy
                            </button>
                        </div>
                        <div class="p-3 rounded-lg" style="background-color: var(--input-bg); border: 1px solid var(--input-border); font-family: 'Fira Code', monospace; font-size: 0.75rem; overflow-x: auto;">
                            <code style="color: var(--text-primary);">curl "<?= htmlspecialchars($baseUrl) ?>/quarters-theory-api-v1/quarters-theory-api.php?symbol=XAUUSD&api_key=<?= htmlspecialchars($apiKey) ?>&pretend_date=2025-12-25&pretend_time=14:30"</code>
                        </div>
                    </div>
                    <div class="mt-3 text-xs" style="color: var(--text-secondary);">
                        <strong>💡 Use Cases:</strong>
                        <ul class="list-disc list-inside mt-2 space-y-1">
                            <li>Backtest your trading strategy on historical quarters data</li>
                            <li>Analyze how quarters theory worked at specific past events</li>
                            <li>Validate strategy performance on historical price action</li>
                            <li>Study quarter patterns during NFP, FOMC, or other major events</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Usage Examples -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-6" style="color: var(--text-primary);">Usage Examples</h2>
        
        <div class="grid grid-cols-1 gap-6">
            <!-- Example 1 -->
            <div class="p-6 rounded-2xl example-card cursor-pointer" style="background-color: var(--card-bg); border: 1px solid var(--border);" onclick="testAPI(event.currentTarget, '<?php echo htmlspecialchars($baseUrl); ?>/quarters-theory-api-v1/quarters-theory-api.php?symbol=EURUSD&api_key=<?php echo htmlspecialchars($apiKey); ?>')">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3" style="background: linear-gradient(135deg, #9C27B0, #BA68C8);">
                            <span class="text-sm font-bold text-white">1</span>
                        </div>
                        <h3 class="text-lg font-semibold" style="color: var(--text-primary);">Get Quarters Data for EUR/USD</h3>
                    </div>
                    <button class="px-3 py-1 rounded-lg text-xs font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500" style="background: linear-gradient(135deg, #9C27B0, #BA68C8); color: white;" onclick="event.stopPropagation(); testAPI(event.currentTarget.closest('.example-card'), '<?php echo htmlspecialchars($baseUrl); ?>/quarters-theory-api-v1/quarters-theory-api.php?symbol=EURUSD&api_key=<?php echo htmlspecialchars($apiKey); ?>')" tabindex="0">
                        Test Request
                    </button>
                </div>
                <div class="p-4 rounded-xl font-mono text-sm overflow-x-auto" style="background-color: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-primary);">
                    <?php echo htmlspecialchars($baseUrl); ?>/quarters-theory-api-v1/quarters-theory-api.php?symbol=EURUSD&api_key=<?php echo htmlspecialchars($apiKey); ?>
                </div>
                <div class="test-response mt-4" style="display: none;"></div>
            </div>

            <!-- Example 2 -->
            <div class="p-6 rounded-2xl example-card cursor-pointer" style="background-color: var(--card-bg); border: 1px solid var(--border);" onclick="testAPI(event.currentTarget, '<?php echo htmlspecialchars($baseUrl); ?>/quarters-theory-api-v1/quarters-theory-api.php?symbol=XAUUSD&api_key=<?php echo htmlspecialchars($apiKey); ?>')">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3" style="background: linear-gradient(135deg, #9C27B0, #BA68C8);">
                            <span class="text-sm font-bold text-white">2</span>
                        </div>
                        <h3 class="text-lg font-semibold" style="color: var(--text-primary);">Get Quarters Data for Gold</h3>
                    </div>
                    <button class="px-3 py-1 rounded-lg text-xs font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500" style="background: linear-gradient(135deg, #9C27B0, #BA68C8); color: white;" onclick="event.stopPropagation(); testAPI(event.currentTarget.closest('.example-card'), '<?php echo htmlspecialchars($baseUrl); ?>/quarters-theory-api-v1/quarters-theory-api.php?symbol=XAUUSD&api_key=<?php echo htmlspecialchars($apiKey); ?>')" tabindex="0">
                        Test Request
                    </button>
                </div>
                <div class="p-4 rounded-xl font-mono text-sm overflow-x-auto" style="background-color: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-primary);">
                    <?php echo htmlspecialchars($baseUrl); ?>/quarters-theory-api-v1/quarters-theory-api.php?symbol=XAUUSD&api_key=<?php echo htmlspecialchars($apiKey); ?>
                </div>
                <div class="test-response mt-4" style="display: none;"></div>
            </div>

            <!-- Example 3 -->
            <div class="p-6 rounded-2xl example-card cursor-pointer" style="background-color: var(--card-bg); border: 1px solid var(--border);" onclick="testAPI(event.currentTarget, '<?php echo htmlspecialchars($baseUrl); ?>/quarters-theory-api-v1/quarters-theory-api.php?symbol=GBPUSD&api_key=<?php echo htmlspecialchars($apiKey); ?>')">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3" style="background: linear-gradient(135deg, #9C27B0, #BA68C8);">
                            <span class="text-sm font-bold text-white">3</span>
                        </div>
                        <h3 class="text-lg font-semibold" style="color: var(--text-primary);">Get Quarters Data for GBP/USD</h3>
                    </div>
                    <button class="px-3 py-1 rounded-lg text-xs font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500" style="background: linear-gradient(135deg, #9C27B0, #BA68C8); color: white;" onclick="event.stopPropagation(); testAPI(event.currentTarget.closest('.example-card'), '<?php echo htmlspecialchars($baseUrl); ?>/quarters-theory-api-v1/quarters-theory-api.php?symbol=GBPUSD&api_key=<?php echo htmlspecialchars($apiKey); ?>')" tabindex="0">
                        Test Request
                    </button>
                </div>
                <div class="p-4 rounded-xl font-mono text-sm overflow-x-auto" style="background-color: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-primary);">
                    <?php echo htmlspecialchars($baseUrl); ?>/quarters-theory-api-v1/quarters-theory-api.php?symbol=GBPUSD&api_key=<?php echo htmlspecialchars($apiKey); ?>
                </div>
                <div class="test-response mt-4" style="display: none;"></div>
            </div>

            <!-- Example 4 -->
            <div class="p-6 rounded-2xl example-card cursor-pointer" style="background-color: var(--card-bg); border: 1px solid var(--border);" onclick="testAPI(event.currentTarget, '<?php echo htmlspecialchars($baseUrl); ?>/quarters-theory-api-v1/quarters-theory-api.php?symbol=USDJPY&api_key=<?php echo htmlspecialchars($apiKey); ?>')">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3" style="background: linear-gradient(135deg, #9C27B0, #BA68C8);">
                            <span class="text-sm font-bold text-white">4</span>
                        </div>
                        <h3 class="text-lg font-semibold" style="color: var(--text-primary);">Get Quarters Data for USD/JPY</h3>
                    </div>
                    <button class="px-3 py-1 rounded-lg text-xs font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500" style="background: linear-gradient(135deg, #9C27B0, #BA68C8); color: white;" onclick="event.stopPropagation(); testAPI(event.currentTarget.closest('.example-card'), '<?php echo htmlspecialchars($baseUrl); ?>/quarters-theory-api-v1/quarters-theory-api.php?symbol=USDJPY&api_key=<?php echo htmlspecialchars($apiKey); ?>')" tabindex="0">
                        Test Request
                    </button>
                </div>
                <div class="p-4 rounded-xl font-mono text-sm overflow-x-auto" style="background-color: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-primary);">
                    <?php echo htmlspecialchars($baseUrl); ?>/quarters-theory-api-v1/quarters-theory-api.php?symbol=USDJPY&api_key=<?php echo htmlspecialchars($apiKey); ?>
                </div>
                <div class="test-response mt-4" style="display: none;"></div>
            </div>
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
    "request_id": "quarters_679abc123def456",
    "symbol": "EURUSD",
    "quarters_data": {
      "timestamp": "2026-01-13 14:30",
      "analysis_timeframe": "PERIOD_H4",
      "average_range": 450.5,
      "quota_value": 112.6,
      "timeframes": [
        {
          "timeframe": "M15",
          "high": 1.10245,
          "low": 1.10123,
          "low_percentage": 67,
          "high_percentage": -33,
          "time_quarter": "3rd",
          "countdown": "08:45",
          "quota_value": 3.2
        },
        {
          "timeframe": "H1",
          "high": 1.10356,
          "low": 1.10067,
          "low_percentage": 48,
          "high_percentage": -52,
          "time_quarter": "2nd",
          "countdown": "38:25",
          "quota_value": 11.5
        }
        // ... more timeframes
      ]
    }
  }
}</pre>
            </div>
        </div>
    </div>

    <!-- Data Field Explanations -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-6" style="color: var(--text-primary);">Understanding the Response Fields</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-base font-semibold mb-4" style="color: var(--text-primary);">Main Analysis Fields</h3>
                <div class="space-y-3 text-sm">
                    <div class="highlight-box">
                        <strong style="color: var(--text-primary);">timestamp:</strong>
                        <span style="color: var(--text-secondary);"> Current market time</span>
                    </div>
                    <div class="highlight-box">
                        <strong style="color: var(--text-primary);">analysis_timeframe:</strong>
                        <span style="color: var(--text-secondary);"> Primary timeframe for quota calculations</span>
                    </div>
                    <div class="highlight-box">
                        <strong style="color: var(--text-primary);">average_range:</strong>
                        <span style="color: var(--text-secondary);"> Average points movement</span>
                    </div>
                    <div class="highlight-box">
                        <strong style="color: var(--text-primary);">quota_value:</strong>
                        <span style="color: var(--text-secondary);"> Average range ÷ 4</span>
                    </div>
                </div>
            </div>

            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-base font-semibold mb-4" style="color: var(--text-primary);">Per-Timeframe Fields</h3>
                <div class="space-y-3 text-sm">
                    <div class="highlight-box">
                        <strong style="color: var(--text-primary);">low_percentage:</strong>
                        <span style="color: var(--text-secondary);"> Quotas UP from low (0-100%+)</span>
                    </div>
                    <div class="highlight-box">
                        <strong style="color: var(--text-primary);">high_percentage:</strong>
                        <span style="color: var(--text-secondary);"> Quotas DOWN from high (negative)</span>
                    </div>
                    <div class="highlight-box">
                        <strong style="color: var(--text-primary);">time_quarter:</strong>
                        <span style="color: var(--text-secondary);"> Which time quarter (1st, 2nd, 3rd, 4th)</span>
                    </div>
                    <div class="highlight-box">
                        <strong style="color: var(--text-primary);">countdown:</strong>
                        <span style="color: var(--text-secondary);"> Time until period closes</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Trading Strategies -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-6" style="color: var(--text-primary);">Trading Interpretation Examples</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Bullish Scenario -->
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3" style="background: linear-gradient(135deg, #4CAF50, #66BB6A);">
                        <i data-feather="trending-up" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <h3 class="text-lg font-semibold" style="color: var(--text-primary);">Bullish Scenario</h3>
                </div>
                <ul class="space-y-2 text-sm" style="color: var(--text-secondary);">
                    <li>• <strong>Low %:</strong> 85% (near 4th quarter)</li>
                    <li>• <strong>High %:</strong> -15% (close to high)</li>
                    <li>• <strong>Time Quarter:</strong> 2nd (mid-period)</li>
                    <li>• <strong>Interpretation:</strong> Price in upper quarter with time remaining = potential for further upside or reversal zone</li>
                </ul>
            </div>

            <!-- Bearish Scenario -->
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3" style="background: linear-gradient(135deg, #F44336, #EF5350);">
                        <i data-feather="trending-down" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <h3 class="text-lg font-semibold" style="color: var(--text-primary);">Bearish Scenario</h3>
                </div>
                <ul class="space-y-2 text-sm" style="color: var(--text-secondary);">
                    <li>• <strong>Low %:</strong> 15% (near 1st quarter)</li>
                    <li>• <strong>High %:</strong> -85% (far from high)</li>
                    <li>• <strong>Time Quarter:</strong> 3rd (late-mid period)</li>
                    <li>• <strong>Interpretation:</strong> Price in lower quarter late in period = potential support or continuation down</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- EA Setup -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-6" style="color: var(--text-primary);">MT5 EA Setup Requirements</h2>
        
        <div class="p-6 rounded-2xl" style="background-color: rgba(255, 152, 0, 0.1); border: 1px solid #FF9800;">
            <div class="flex items-start">
                <div class="flex-shrink-0 mr-4">
                    <i data-feather="settings" style="width: 24px; height: 24px; color: #FF9800;"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-base font-semibold mb-3" style="color: var(--text-primary);">Configuration Steps</h3>
                    <ol class="space-y-2 text-sm" style="color: var(--text-secondary);">
                        <li>1. Download <code>Richchild Quarters Theory Data EA.mq5</code> from the Download EAs page</li>
                        <li>2. Place in <code>MQL5/Experts/</code> folder in your MT5 data directory</li>
                        <li>3. Open in MetaEditor and compile (F7)</li>
                        <li>4. Attach EA to any symbol chart in MT5</li>
                        <li>5. Enable WebRequest: Tools → Options → Expert Advisors</li>
                        <li>6. Add URL: <code><?php echo $baseUrl; ?>/quarters-theory-api-v1/quarters-theory-api.php</code></li>
                        <li>7. Set <code>InpEnableApi = true</code> in EA inputs</li>
                        <li>8. Verify EA is running and printing quarters data</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Best Practices -->
    <div class="mb-8 p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
        <h2 class="text-xl font-bold mb-4" style="color: var(--text-primary);">Best Practices</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm" style="color: var(--text-secondary);">
            <div class="flex items-start">
                <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success);"></i>
                <span>Use multi-timeframe confluence for stronger signals</span>
            </div>
            <div class="flex items-start">
                <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success);"></i>
                <span>Watch for price at 25%, 50%, 75%, 100% levels</span>
            </div>
            <div class="flex items-start">
                <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success);"></i>
                <span>Pay attention to time quarters for entry timing</span>
            </div>
            <div class="flex items-start">
                <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success);"></i>
                <span>Use quota values to set realistic profit targets</span>
            </div>
            <div class="flex items-start">
                <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success);"></i>
                <span>Monitor countdown for period close setups</span>
            </div>
            <div class="flex items-start">
                <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success);"></i>
                <span>Combine with higher timeframe trend analysis</span>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="p-6 rounded-2xl" style="background: linear-gradient(135deg, rgba(156, 39, 176, 0.1), rgba(255, 152, 0, 0.1)); border: 1px solid var(--border);">
        <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">Ready to Get Started?</h3>
        <div class="flex flex-wrap gap-3">
            <a href="/download-eas" class="inline-flex items-center px-5 py-2.5 rounded-full text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500" style="background: linear-gradient(135deg, #9C27B0, #BA68C8); color: white;" tabindex="0">
                <i data-feather="download" class="mr-2" style="width: 16px; height: 16px;"></i>
                Download EA
            </a>
            <a href="/settings" class="inline-flex items-center px-5 py-2.5 rounded-full text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500" style="background-color: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border);" tabindex="0">
                <i data-feather="settings" class="mr-2" style="width: 16px; height: 16px;"></i>
                Configure API Settings
            </a>
        </div>
    </div>
</div>

<script>
function testAPI(element, url) {
    let card = element;
    
    if (element && element.classList && element.classList.contains('example-card')) {
        card = element;
    } 
    else if (element && element.closest) {
        card = element.closest('.example-card');
    }
    
    if (!card) {
        console.error('Card element not found', element);
        return;
    }
    
    const responseDiv = card.querySelector('.test-response');
    if (!responseDiv) {
        console.error('Response div not found in card', card);
        return;
    }
    
    responseDiv.style.display = 'block';
    responseDiv.innerHTML = '<div style="padding: 12px; background-color: var(--bg-secondary); border-radius: 8px; color: var(--text-secondary);"><i data-feather="loader" style="width: 14px; height: 14px; display: inline; animation: spin 1s linear infinite;"></i> Loading...</div>';
    feather.replace();
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.arrissa_data && data.arrissa_data.error) {
                responseDiv.innerHTML = '<div style="padding: 12px; background-color: rgba(244, 67, 54, 0.1); border-radius: 8px; border: 1px solid #F44336; color: #F44336;"><div style="font-weight: 600; margin-bottom: 8px;"><i data-feather="alert-triangle" style="width: 14px; height: 14px; display: inline;"></i> ' + data.arrissa_data.error + '</div>' + (data.arrissa_data.message ? '<div style="font-size: 0.75rem; opacity: 0.9;">' + data.arrissa_data.message + '</div>' : '') + '</div>';
                feather.replace();
            } else {
                responseDiv.innerHTML = '<div style="padding: 12px; background-color: var(--bg-primary); border-radius: 8px; border: 1px solid var(--border);"><pre style="margin: 0; max-height: 300px; overflow-y: auto; color: var(--text-secondary); font-size: 0.75rem;">' + JSON.stringify(data, null, 2) + '</pre></div>';
            }
        })
        .catch(error => {
            responseDiv.innerHTML = '<div style="padding: 12px; background-color: rgba(244, 67, 54, 0.1); border-radius: 8px; border: 1px solid #F44336; color: #F44336;"><i data-feather="alert-circle" style="width: 14px; height: 14px; display: inline;"></i> Error: ' + error.message + '</div>';
            feather.replace();
        });
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Copied to clipboard!');
    });
}

feather.replace();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/app.php';
?>
