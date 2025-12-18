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

$title = 'Symbol Info API Guide';
$page = 'symbol-info-api';
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
                <p class="text-sm mb-3" style="color: var(--text-secondary);">This API requires the Symbol Info EA to be running on an MT5 chart. The EA processes analysis requests and returns comprehensive symbol behavior data.</p>
                <a href="/download-eas" class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium transition-colors" style="background-color: var(--accent); color: white;">
                    <i data-feather="download" class="mr-2" style="width: 16px; height: 16px;"></i>
                    Download Symbol Info EA
                </a>
            </div>
        </div>
    </div>

    <!-- Hero Header -->
    <div class="mb-10">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-4xl font-bold mb-3 tracking-tight" style="color: var(--text-primary);">
                    Symbol Info API
                    <span class="section-badge ml-3" style="background-color: var(--success); color: var(--bg-primary);">v1.0</span>
                </h1>
                <p class="text-lg" style="color: var(--text-secondary);">Advanced symbol behavior analysis and statistical calculations across multiple timeframes</p>
            </div>
        </div>
        
        <!-- Features Banner -->
        <div class="p-6 rounded-2xl gradient-bg" style="border: 1px solid var(--border);">
            <div class="flex items-start">
                <div class="flex-shrink-0 mr-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background-color: var(--success);">
                        <i data-feather="zap" style="width: 24px; height: 24px; color: white;"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">Key Features</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm" style="color: var(--text-secondary);">
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span><strong style="color: var(--text-primary);">Multi-Timeframe Support:</strong> M5, M15, M30, H1, H4, H8, H12, D1, W1, Monthly</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span><strong style="color: var(--text-primary);">Comprehensive Analysis:</strong> Average high, low, body, wick calculations</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span><strong style="color: var(--text-primary);">Historical Backtesting:</strong> Analyze behavior at specific dates</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span><strong style="color: var(--text-primary);">Flexible Lookback:</strong> Customizable period analysis</span>
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
                <?php echo htmlspecialchars($baseUrl); ?>/symbol-info-api-v1/symbol-info-api.php
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
            <pre class="p-4 rounded-xl overflow-x-auto api-code" style="background-color: var(--bg-primary); border: 1px solid var(--border); color: var(--text-secondary);"><?php echo htmlspecialchars($baseUrl); ?>/symbol-info-api-v1/symbol-info-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&symbol=EURUSD&timeframe=D1</pre>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Overview Section -->
    <div class="mb-12">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, var(--accent), var(--success));">
                <i data-feather="info" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Overview</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Understanding symbol behavior analysis</p>
            </div>
        </div>
        <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <p class="mb-4" style="color: var(--text-secondary);">
                The Symbol Info API provides comprehensive statistical analysis of symbol behavior across different timeframes. 
                Calculate average ranges, body sizes, wick lengths, and other metrics essential for trading strategy development.
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <div class="flex items-center mb-2">
                        <i data-feather="trending-up" style="width: 18px; height: 18px; color: var(--accent); margin-right: 8px;"></i>
                        <span class="font-semibold" style="color: var(--text-primary);">Statistical Analysis</span>
                    </div>
                    <p class="text-sm" style="color: var(--text-secondary);">Average high, low, body, upper wick, lower wick in pips</p>
                </div>
                <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <div class="flex items-center mb-2">
                        <i data-feather="bar-chart-2" style="width: 18px; height: 18px; color: var(--accent); margin-right: 8px;"></i>
                        <span class="font-semibold" style="color: var(--text-primary);">Market Sentiment</span>
                    </div>
                    <p class="text-sm" style="color: var(--text-secondary);">Bullish vs bearish candle count analysis</p>
                </div>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Basic Examples -->
    <div class="mb-12">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, var(--accent), var(--success));">
                <i data-feather="code" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Basic Examples</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Common use cases and requests</p>
            </div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Example 1 -->
            <div class="example-card p-6 rounded-2xl cursor-pointer" style="background-color: var(--card-bg); border: 1px solid var(--border);" onclick="testAPI(event.currentTarget, '<?php echo htmlspecialchars($baseUrl); ?>/symbol-info-api-v1/symbol-info-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&symbol=EURUSD&timeframe=D1')">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <h3 class="font-semibold text-lg mb-2" style="color: var(--text-primary);">Daily Average - EURUSD</h3>
                        <p class="text-sm mb-3" style="color: var(--text-secondary);">Get 30-day average behavior analysis</p>
                    </div>
                    <button class="px-3 py-1 rounded-lg text-xs font-medium" style="background-color: var(--accent); color: white;" onclick="event.stopPropagation(); testAPI(event.currentTarget.closest('.example-card'), '<?php echo htmlspecialchars($baseUrl); ?>/symbol-info-api-v1/symbol-info-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&symbol=EURUSD&timeframe=D1')">>
                        <i data-feather="play" style="width: 12px; height: 12px; display: inline; margin-right: 4px;"></i>Test
                    </button>
                </div>
                <div class="space-y-2">
                    <div class="flex items-center text-sm">
                        <span class="font-medium mr-2" style="color: var(--text-secondary);">Symbol:</span>
                        <code class="api-code" style="color: var(--accent);">EURUSD</code>
                    </div>
                    <div class="flex items-center text-sm">
                        <span class="font-medium mr-2" style="color: var(--text-secondary);">Timeframe:</span>
                        <code class="api-code" style="color: var(--accent);">D1</code>
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between">
                    <code class="api-code text-xs" style="color: var(--text-secondary);">symbol=EURUSD&timeframe=D1</code>
                    <button onclick="event.stopPropagation(); copyURL('<?php echo htmlspecialchars($baseUrl); ?>/symbol-info-api-v1/symbol-info-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&symbol=EURUSD&timeframe=D1')" class="px-3 py-1 rounded-lg text-xs" style="background-color: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border);">
                        <i data-feather="copy" style="width: 12px; height: 12px; display: inline;"></i>
                    </button>
                </div>
                <div class="test-response mt-4" style="display: none;"></div>
            </div>

            <!-- Example 2 -->
            <div class="example-card p-6 rounded-2xl cursor-pointer" style="background-color: var(--card-bg); border: 1px solid var(--border);" onclick="testAPI(event.currentTarget, '<?php echo htmlspecialchars($baseUrl); ?>/symbol-info-api-v1/symbol-info-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&symbol=GBPUSD&timeframe=H4')">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <h3 class="font-semibold text-lg mb-2" style="color: var(--text-primary);">4-Hour Analysis - GBPUSD</h3>
                        <p class="text-sm mb-3" style="color: var(--text-secondary);">Get 30-period H4 behavior</p>
                    </div>
                    <button class="px-3 py-1 rounded-lg text-xs font-medium" style="background-color: var(--accent); color: white;" onclick="event.stopPropagation(); testAPI(event.currentTarget.closest('.example-card'), '<?php echo htmlspecialchars($baseUrl); ?>/symbol-info-api-v1/symbol-info-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&symbol=GBPUSD&timeframe=H4')">
                        <i data-feather="play" style="width: 12px; height: 12px; display: inline; margin-right: 4px;"></i>Test
                    </button>
                </div>
                <div class="space-y-2">
                    <div class="flex items-center text-sm">
                        <span class="font-medium mr-2" style="color: var(--text-secondary);">Symbol:</span>
                        <code class="api-code" style="color: var(--accent);">GBPUSD</code>
                    </div>
                    <div class="flex items-center text-sm">
                        <span class="font-medium mr-2" style="color: var(--text-secondary);">Timeframe:</span>
                        <code class="api-code" style="color: var(--accent);">H4</code>
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between">
                    <code class="api-code text-xs" style="color: var(--text-secondary);">symbol=GBPUSD&timeframe=H4</code>
                    <button onclick="event.stopPropagation(); copyURL('<?php echo htmlspecialchars($baseUrl); ?>/symbol-info-api-v1/symbol-info-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&symbol=GBPUSD&timeframe=H4')" class="px-3 py-1 rounded-lg text-xs" style="background-color: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border);">
                        <i data-feather="copy" style="width: 12px; height: 12px; display: inline;"></i>
                    </button>
                </div>
                <div class="test-response mt-4" style="display: none;"></div>
            </div>

            <!-- Example 3 -->
            <div class="example-card p-6 rounded-2xl cursor-pointer" style="background-color: var(--card-bg); border: 1px solid var(--border);" onclick="testAPI(event.currentTarget, '<?php echo htmlspecialchars($baseUrl); ?>/symbol-info-api-v1/symbol-info-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&symbol=XAUUSD&timeframe=H1&lookback=50')">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <h3 class="font-semibold text-lg mb-2" style="color: var(--text-primary);">Gold Hourly - Custom Lookback</h3>
                        <p class="text-sm mb-3" style="color: var(--text-secondary);">Analyze last 50 hours of XAUUSD</p>
                    </div>
                    <button class="px-3 py-1 rounded-lg text-xs font-medium" style="background-color: var(--accent); color: white;" onclick="event.stopPropagation(); testAPI(event.currentTarget.closest('.example-card'), '<?php echo htmlspecialchars($baseUrl); ?>/symbol-info-api-v1/symbol-info-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&symbol=XAUUSD&timeframe=H1&lookback=50')">
                        <i data-feather="play" style="width: 12px; height: 12px; display: inline; margin-right: 4px;"></i>Test
                    </button>
                </div>
                <div class="space-y-2">
                    <div class="flex items-center text-sm">
                        <span class="font-medium mr-2" style="color: var(--text-secondary);">Symbol:</span>
                        <code class="api-code" style="color: var(--accent);">XAUUSD</code>
                    </div>
                    <div class="flex items-center text-sm">
                        <span class="font-medium mr-2" style="color: var(--text-secondary);">Timeframe:</span>
                        <code class="api-code" style="color: var(--accent);">H1</code>
                    </div>
                    <div class="flex items-center text-sm">
                        <span class="font-medium mr-2" style="color: var(--text-secondary);">Lookback:</span>
                        <code class="api-code" style="color: var(--accent);">50</code>
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between">
                    <code class="api-code text-xs" style="color: var(--text-secondary);">symbol=XAUUSD&timeframe=H1&lookback=50</code>
                    <button onclick="event.stopPropagation(); copyURL('<?php echo htmlspecialchars($baseUrl); ?>/symbol-info-api-v1/symbol-info-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&symbol=XAUUSD&timeframe=H1&lookback=50')" class="px-3 py-1 rounded-lg text-xs" style="background-color: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border);">
                        <i data-feather="copy" style="width: 12px; height: 12px; display: inline;"></i>
                    </button>
                </div>
                <div class="test-response mt-4" style="display: none;"></div>
            </div>

            <!-- Example 4 -->
            <div class="example-card p-6 rounded-2xl cursor-pointer" style="background-color: var(--card-bg); border: 1px solid var(--border);" onclick="testAPI(event.currentTarget, '<?php echo htmlspecialchars($baseUrl); ?>/symbol-info-api-v1/symbol-info-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&symbol=USDJPY&timeframe=M15&ignore_sunday=false')">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <h3 class="font-semibold text-lg mb-2" style="color: var(--text-primary);">USDJPY M15 - Include Sundays</h3>
                        <p class="text-sm mb-3" style="color: var(--text-secondary);">Analysis with Sunday data included</p>
                    </div>
                    <button class="px-3 py-1 rounded-lg text-xs font-medium" style="background-color: var(--accent); color: white;" onclick="event.stopPropagation(); testAPI(event.currentTarget.closest('.example-card'), '<?php echo htmlspecialchars($baseUrl); ?>/symbol-info-api-v1/symbol-info-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&symbol=USDJPY&timeframe=M15&ignore_sunday=false')">
                        <i data-feather="play" style="width: 12px; height: 12px; display: inline; margin-right: 4px;"></i>Test
                    </button>
                </div>
                <div class="space-y-2">
                    <div class="flex items-center text-sm">
                        <span class="font-medium mr-2" style="color: var(--text-secondary);">Symbol:</span>
                        <code class="api-code" style="color: var(--accent);">USDJPY</code>
                    </div>
                    <div class="flex items-center text-sm">
                        <span class="font-medium mr-2" style="color: var(--text-secondary);">Timeframe:</span>
                        <code class="api-code" style="color: var(--accent);">M15</code>
                    </div>
                    <div class="flex items-center text-sm">
                        <span class="font-medium mr-2" style="color: var(--text-secondary);">Ignore Sunday:</span>
                        <code class="api-code" style="color: var(--accent);">false</code>
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between">
                    <code class="api-code text-xs" style="color: var(--text-secondary);">symbol=USDJPY&timeframe=M15&ignore_sunday=false</code>
                    <button onclick="event.stopPropagation(); copyURL('<?php echo htmlspecialchars($baseUrl); ?>/symbol-info-api-v1/symbol-info-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&symbol=USDJPY&timeframe=M15&ignore_sunday=false')" class="px-3 py-1 rounded-lg text-xs" style="background-color: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border);">
                        <i data-feather="copy" style="width: 12px; height: 12px; display: inline;"></i>
                    </button>
                </div>
                <div class="test-response mt-4" style="display: none;"></div>
            </div>

        </div>
    </div>

    <div class="divider"></div>

    <!-- Advanced Examples -->
    <div class="mb-12">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, var(--accent), var(--success));">
                <i data-feather="zap" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Advanced Examples</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Complex analysis scenarios</p>
            </div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Weekly Analysis -->
            <div class="example-card p-6 rounded-2xl cursor-pointer" style="background-color: var(--card-bg); border: 1px solid var(--border);" onclick="testAPI(event.currentTarget, '<?php echo htmlspecialchars($baseUrl); ?>/symbol-info-api-v1/symbol-info-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&symbol=BTCUSD&timeframe=W1&lookback=20')">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <h3 class="font-semibold text-lg mb-2" style="color: var(--text-primary);">Bitcoin Weekly Analysis</h3>
                        <p class="text-sm mb-3" style="color: var(--text-secondary);">Last 20 weeks of BTCUSD behavior</p>
                    </div>
                    <button class="px-3 py-1 rounded-lg text-xs font-medium" style="background-color: var(--accent); color: white;" onclick="event.stopPropagation(); testAPI(event.currentTarget.closest('.example-card'), '<?php echo htmlspecialchars($baseUrl); ?>/symbol-info-api-v1/symbol-info-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&symbol=BTCUSD&timeframe=W1&lookback=20')">
                        <i data-feather="play" style="width: 12px; height: 12px; display: inline; margin-right: 4px;"></i>Test
                    </button>
                </div>
                <div class="space-y-2">
                    <div class="flex items-center text-sm">
                        <span class="font-medium mr-2" style="color: var(--text-secondary);">Symbol:</span>
                        <code class="api-code" style="color: var(--accent);">BTCUSD</code>
                    </div>
                    <div class="flex items-center text-sm">
                        <span class="font-medium mr-2" style="color: var(--text-secondary);">Timeframe:</span>
                        <code class="api-code" style="color: var(--accent);">W1</code>
                    </div>
                    <div class="flex items-center text-sm">
                        <span class="font-medium mr-2" style="color: var(--text-secondary);">Lookback:</span>
                        <code class="api-code" style="color: var(--accent);">20 weeks</code>
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between">
                    <code class="api-code text-xs" style="color: var(--text-secondary);">symbol=BTCUSD&timeframe=W1&lookback=20</code>
                    <button onclick="event.stopPropagation(); copyURL('<?php echo htmlspecialchars($baseUrl); ?>/symbol-info-api-v1/symbol-info-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&symbol=BTCUSD&timeframe=W1&lookback=20')" class="px-3 py-1 rounded-lg text-xs" style="background-color: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border);">
                        <i data-feather="copy" style="width: 12px; height: 12px; display: inline;"></i>
                    </button>
                </div>
                <div class="test-response mt-4" style="display: none;"></div>
            </div>

            <!-- Backtesting with Pretend Date -->
            <div class="example-card p-6 rounded-2xl cursor-pointer" style="background-color: var(--card-bg); border: 1px solid var(--border);" onclick="testAPI(event.currentTarget, '<?php echo htmlspecialchars($baseUrl); ?>/symbol-info-api-v1/symbol-info-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&symbol=EURUSD&timeframe=D1&pretend_date=2024.01.15&pretend_time=12:00')">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <h3 class="font-semibold text-lg mb-2" style="color: var(--text-primary);">Historical Backtesting</h3>
                        <p class="text-sm mb-3" style="color: var(--text-secondary);">Analyze behavior as of specific date</p>
                    </div>
                    <button class="px-3 py-1 rounded-lg text-xs font-medium" style="background-color: var(--accent); color: white;" onclick="event.stopPropagation(); testAPI(event.currentTarget.closest('.example-card'), '<?php echo htmlspecialchars($baseUrl); ?>/symbol-info-api-v1/symbol-info-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&symbol=EURUSD&timeframe=D1&pretend_date=2024.01.15&pretend_time=12:00')">
                        <i data-feather="play" style="width: 12px; height: 12px; display: inline; margin-right: 4px;"></i>Test
                    </button>
                </div>
                <div class="space-y-2">
                    <div class="flex items-center text-sm">
                        <span class="font-medium mr-2" style="color: var(--text-secondary);">Symbol:</span>
                        <code class="api-code" style="color: var(--accent);">EURUSD</code>
                    </div>
                    <div class="flex items-center text-sm">
                        <span class="font-medium mr-2" style="color: var(--text-secondary);">Pretend Date:</span>
                        <code class="api-code" style="color: var(--accent);">2024.01.15</code>
                    </div>
                    <div class="flex items-center text-sm">
                        <span class="font-medium mr-2" style="color: var(--text-secondary);">Pretend Time:</span>
                        <code class="api-code" style="color: var(--accent);">12:00</code>
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between">
                    <code class="api-code text-xs" style="color: var(--text-secondary);">...pretend_date=2024.01.15&pretend_time=12:00</code>
                    <button onclick="event.stopPropagation(); copyURL('<?php echo htmlspecialchars($baseUrl); ?>/symbol-info-api-v1/symbol-info-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&symbol=EURUSD&timeframe=D1&pretend_date=2024.01.15&pretend_time=12:00')" class="px-3 py-1 rounded-lg text-xs" style="background-color: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border);">
                        <i data-feather="copy" style="width: 12px; height: 12px; display: inline;"></i>
                    </button>
                </div>
                <div class="test-response mt-4" style="display: none;"></div>
            </div>

        </div>
    </div>

    <div class="divider"></div>

    <!-- Parameters Reference -->
    <div class="mb-12">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, var(--accent), var(--success));">
                <i data-feather="list" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Parameters Reference</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Complete parameter documentation</p>
            </div>
        </div>
        <div class="rounded-2xl overflow-hidden" style="border: 1px solid var(--border);">
            <table class="w-full">
                <thead>
                    <tr style="background-color: var(--bg-secondary);">
                        <th class="text-left p-4 font-semibold" style="color: var(--text-primary);">Parameter</th>
                        <th class="text-left p-4 font-semibold" style="color: var(--text-primary);">Type</th>
                        <th class="text-left p-4 font-semibold" style="color: var(--text-primary);">Required</th>
                        <th class="text-left p-4 font-semibold" style="color: var(--text-primary);">Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-top: 1px solid var(--border);">
                        <td class="p-4"><code class="api-code" style="color: var(--accent);">api_key</code></td>
                        <td class="p-4" style="color: var(--text-secondary);">string</td>
                        <td class="p-4"><span class="px-2 py-1 rounded text-xs" style="background-color: var(--danger-bg); color: var(--danger);">Yes</span></td>
                        <td class="p-4" style="color: var(--text-secondary);">Your authentication API key</td>
                    </tr>
                    <tr style="border-top: 1px solid var(--border);">
                        <td class="p-4"><code class="api-code" style="color: var(--accent);">symbol</code></td>
                        <td class="p-4" style="color: var(--text-secondary);">string</td>
                        <td class="p-4"><span class="px-2 py-1 rounded text-xs" style="background-color: var(--danger-bg); color: var(--danger);">Yes</span></td>
                        <td class="p-4" style="color: var(--text-secondary);">MT5 symbol name (e.g., EURUSD, XAUUSD)</td>
                    </tr>
                    <tr style="border-top: 1px solid var(--border);">
                        <td class="p-4"><code class="api-code" style="color: var(--accent);">timeframe</code></td>
                        <td class="p-4" style="color: var(--text-secondary);">string</td>
                        <td class="p-4"><span class="px-2 py-1 rounded text-xs" style="background-color: var(--warning-bg); color: var(--warning);">Optional</span></td>
                        <td class="p-4" style="color: var(--text-secondary);">M5, M15, M30, H1, H4, H8, H12, D1, W1, M (default: D1)</td>
                    </tr>
                    <tr style="border-top: 1px solid var(--border);">
                        <td class="p-4"><code class="api-code" style="color: var(--accent);">lookback</code></td>
                        <td class="p-4" style="color: var(--text-secondary);">integer</td>
                        <td class="p-4"><span class="px-2 py-1 rounded text-xs" style="background-color: var(--warning-bg); color: var(--warning);">Optional</span></td>
                        <td class="p-4" style="color: var(--text-secondary);">Number of periods to analyze (default varies by timeframe)</td>
                    </tr>
                    <tr style="border-top: 1px solid var(--border);">
                        <td class="p-4"><code class="api-code" style="color: var(--accent);">ignore_sunday</code></td>
                        <td class="p-4" style="color: var(--text-secondary);">boolean</td>
                        <td class="p-4"><span class="px-2 py-1 rounded text-xs" style="background-color: var(--warning-bg); color: var(--warning);">Optional</span></td>
                        <td class="p-4" style="color: var(--text-secondary);">Exclude Sunday data from calculations (default: true)</td>
                    </tr>
                    <tr style="border-top: 1px solid var(--border);">
                        <td class="p-4"><code class="api-code" style="color: var(--accent);">pretend_date</code></td>
                        <td class="p-4" style="color: var(--text-secondary);">string</td>
                        <td class="p-4"><span class="px-2 py-1 rounded text-xs" style="background-color: var(--warning-bg); color: var(--warning);">Optional</span></td>
                        <td class="p-4" style="color: var(--text-secondary);">Historical date for backtesting (format: YYYY.MM.DD)</td>
                    </tr>
                    <tr style="border-top: 1px solid var(--border);">
                        <td class="p-4"><code class="api-code" style="color: var(--accent);">pretend_time</code></td>
                        <td class="p-4" style="color: var(--text-secondary);">string</td>
                        <td class="p-4"><span class="px-2 py-1 rounded text-xs" style="background-color: var(--warning-bg); color: var(--warning);">Optional</span></td>
                        <td class="p-4" style="color: var(--text-secondary);">Historical time for backtesting (format: HH:MM)</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Response Structure -->
    <div class="mb-12">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, var(--accent), var(--success));">
                <i data-feather="package" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Response Structure</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Understanding API responses</p>
            </div>
        </div>
        <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <p class="mb-4" style="color: var(--text-secondary);">All responses include comprehensive symbol behavior analysis with detailed statistics:</p>
            <pre class="p-4 rounded-xl overflow-x-auto" style="background-color: var(--bg-primary); border: 1px solid var(--border); max-height: 500px;"><code class="api-code" style="color: var(--text-secondary); font-size: 0.7rem;">{
  "arrissa_data": {
    "request_id": "analysis_69440124d422d8.14818790",
    "symbol": "EURUSD",
    "timeframe": "D1",
    "lookback": "30",
    "ignore_sunday": "true",
    "symbol_behaviour_data": "=== CURRENT MARKET POSITION ===
Current Price: 1.17320
Daily High: 1.17494
Daily Low: 1.17119
Daily Range: 0.00375
Current Position in Daily Range: 53.6%
Distance from Daily High: 46.4%
Distance from Daily Low: 53.6%

=== BASIC STATISTICS ===
Symbol: EURUSD (D1 timeframe analysis)
Point value: 0.00001
Lookback period: 30 D1 periods
Trading periods analyzed: 25 daily days
Total movement: 13026.0 points
Average daily movement: 521.0 points
Movement range: 301.0 - 810.0 points

=== BULLISH VS BEARISH CANDLE BODY ANALYSIS (D1) ===
Total Bullish Candles: 12
Total Bearish Candles: 18
Total Bullish Body Value: 2984.0 points
Total Bearish Body Value: 1863.0 points
Average Bullish Body Size: 248.7 points
Average Bearish Body Size: 103.5 points

=== WICK ANALYSIS ===
Total Top Wicks Value: 4445.0 points
Total Bottom Wicks Value: 4295.0 points
Average Top Wick Size: 148.2 points
Average Bottom Wick Size: 143.2 points
Top vs Bottom Wick Ratio: 1.03

=== LAST 5 CANDLES DETAILED ANALYSIS ===
Candle 1 (2025.12.14 00:00):
  Type=BEARISH
  OHLC=1.17388/1.17404/1.17335/1.17367
  Body=21.0pts (30.4% of range)
  TopWick=16.0pts (23.2% of range)
  BottomWick=32.0pts (46.4% of range)
  TotalRange=69.0pts
  TickVolume=1247

--- BASIC PERIOD TYPE ANALYSIS ---
Bullish days: 8 (32.0%) - Avg: 586.8 pts
Bearish days: 10 (40.0%) - Avg: 483.0 pts
Doji days: 7 (28.0%) - Avg: 500.3 pts

--- CONSECUTIVE PERIODS (STREAKS) ANALYSIS ---
Max bullish streak: 3 days
Max bearish streak: 6 days
Avg bullish streak length: 2.3 days
Avg bearish streak length: 4.0 days

--- PERIOD PATTERN ANALYSIS ---
Continuation days: 10 (41.7%) - Avg: 557.1 pts
Reversal days: 3 (12.5%) - Avg: 568.3 pts
Doji pattern days: 7 (29.2%) - Avg: 500.3 pts"
  }
}</code></pre>
            <div class="mt-4 p-4 rounded-lg" style="background-color: var(--bg-secondary); border-left: 4px solid var(--accent);">
                <p class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Response Fields</p>
                <ul class="text-sm space-y-2" style="color: var(--text-secondary);">
                    <li><code class="api-code" style="color: var(--accent);">request_id</code> - Unique identifier for the analysis request</li>
                    <li><code class="api-code" style="color: var(--accent);">symbol</code> - Trading symbol analyzed</li>
                    <li><code class="api-code" style="color: var(--accent);">timeframe</code> - Chart timeframe used for analysis</li>
                    <li><code class="api-code" style="color: var(--accent);">lookback</code> - Number of periods analyzed</li>
                    <li><code class="api-code" style="color: var(--accent);">ignore_sunday</code> - Whether Sunday data was excluded</li>
                    <li><code class="api-code" style="color: var(--accent);">symbol_behaviour_data</code> - Comprehensive text report including:
                        <ul class="ml-4 mt-1 space-y-1">
                            <li>• Current market position and daily range</li>
                            <li>• Basic statistics (average movement, point value)</li>
                            <li>• Bullish vs bearish candle analysis</li>
                            <li>• Wick analysis (top/bottom wicks, ratios)</li>
                            <li>• Last 5 candles detailed breakdown</li>
                            <li>• Period type analysis (bullish/bearish/doji percentages)</li>
                            <li>• Streak analysis (consecutive periods)</li>
                            <li>• Pattern analysis (continuation/reversal days)</li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </div>

</div>

<script>
function testAPI(element, url) {
    // Find the card element
    let card = element;
    
    // If element has classList and contains 'example-card', it's already the card
    if (element && element.classList && element.classList.contains('example-card')) {
        card = element;
    } 
    // Otherwise, try to find the parent card
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
            // Check if there's an error in the response
            if (data.arrissa_data && data.arrissa_data.error) {
                responseDiv.innerHTML = '<div style="padding: 12px; background-color: var(--warning-bg); border-radius: 8px; color: var(--warning);"><div style="font-weight: 600; margin-bottom: 8px;"><i data-feather="alert-triangle" style="width: 14px; height: 14px; display: inline;"></i> ' + data.arrissa_data.error + '</div>' + (data.arrissa_data.message ? '<div style="font-size: 0.75rem; opacity: 0.9;">' + data.arrissa_data.message + '</div>' : '') + '</div>';
                feather.replace();
            } else {
                responseDiv.innerHTML = '<div style="padding: 12px; background-color: var(--bg-primary); border-radius: 8px; border: 1px solid var(--border);"><pre style="margin: 0; max-height: 300px; overflow-y: auto; color: var(--text-secondary); font-size: 0.75rem;">' + JSON.stringify(data, null, 2) + '</pre></div>';
            }
        })
        .catch(error => {
            responseDiv.innerHTML = '<div style="padding: 12px; background-color: var(--danger-bg); border-radius: 8px; color: var(--danger);"><i data-feather="alert-circle" style="width: 14px; height: 14px; display: inline;"></i> Error: ' + error.message + '</div>';
            feather.replace();
        });
}

function copyURL(url) {
    navigator.clipboard.writeText(url).then(() => {
        // Show success feedback
        const notification = document.createElement('div');
        notification.textContent = 'URL copied to clipboard!';
        notification.style.cssText = 'position: fixed; top: 20px; right: 20px; background-color: var(--success); color: white; padding: 12px 24px; border-radius: 8px; z-index: 9999; font-size: 14px; font-weight: 500;';
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 2000);
    });
}

feather.replace();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layouts/app.php';
?>
