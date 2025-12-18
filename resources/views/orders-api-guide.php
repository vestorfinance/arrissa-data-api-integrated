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

$title = 'Orders API Guide';
$page = 'orders-api';
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
    <div class="mb-6 p-5 rounded-2xl" style="background-color: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger);">
        <div class="flex items-start">
            <div class="flex-shrink-0 mr-4">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, var(--danger), var(--warning));">
                    <i data-feather="alert-triangle" style="width: 20px; height: 20px; color: white;"></i>
                </div>
            </div>
            <div class="flex-1">
                <h3 class="text-base font-semibold mb-2" style="color: var(--text-primary);">MT5 Expert Advisor Required</h3>
                <p class="text-sm mb-3" style="color: var(--text-secondary);">This API requires the Orders API EA to be running on an MT5 chart. The EA executes trading operations on your MT5 account. Make sure you understand the risks before executing real trades.</p>
                <a href="/download-eas" class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium transition-colors" style="background-color: var(--danger); color: white;">
                    <i data-feather="download" class="mr-2" style="width: 16px; height: 16px;"></i>
                    Download Orders API EA
                </a>
            </div>
        </div>
    </div>

    <!-- Hero Header -->
    <div class="mb-10">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-4xl font-bold mb-3 tracking-tight" style="color: var(--text-primary);">
                    MT5 Orders API
                    <span class="section-badge ml-3" style="background-color: var(--success); color: var(--bg-primary);">v1.0</span>
                </h1>
                <p class="text-lg" style="color: var(--text-secondary);">Complete MT5 trading operations including orders, history, and profit tracking</p>
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
                    <h3 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">Complete Trading API</h3>
                    <ul class="space-y-2 text-sm" style="color: var(--text-secondary);">
                        <li class="flex items-start">
                            <i data-feather="check" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success); margin-top: 2px;"></i>
                            <span>Full trade execution (BUY, SELL, LIMIT, STOP orders)</span>
                        </li>
                        <li class="flex items-start">
                            <i data-feather="check" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success); margin-top: 2px;"></i>
                            <span>Position management (CLOSE, BREAK_EVEN, TRAIL_SL)</span>
                        </li>
                        <li class="flex items-start">
                            <i data-feather="check" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success); margin-top: 2px;"></i>
                            <span>Trade history queries (today, last-hour, last-7days, last-30days)</span>
                        </li>
                        <li class="flex items-start">
                            <i data-feather="check" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success); margin-top: 2px;"></i>
                            <span>Profit/loss tracking with flexible time ranges</span>
                        </li>
                    </ul>
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
                <?php echo htmlspecialchars($baseUrl); ?>/orders-api-v1/orders-api.php
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
                <pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word;"><?php echo htmlspecialchars($baseUrl); ?>/orders-api-v1/orders-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&filter_running=true</pre>
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
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Live API Examples</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Click any example to test the API in real-time</p>
            </div>
        </div>

        <!-- Get Orders Examples -->
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-5" style="color: var(--text-primary);">Get Orders & Positions</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php
                $ordersExamples = [
                    ['title' => 'All Orders & Positions', 'params' => '?api_key=' . $apiKey, 'desc' => 'Get all open positions and pending orders', 'id' => 'all-orders'],
                    ['title' => 'Running Positions Only', 'params' => '?api_key=' . $apiKey . '&filter_running=true', 'desc' => 'Only active market positions', 'id' => 'running-only'],
                    ['title' => 'Pending Orders Only', 'params' => '?api_key=' . $apiKey . '&filter_pending=true', 'desc' => 'Only pending limit/stop orders', 'id' => 'pending-only'],
                    ['title' => 'Profitable Positions', 'params' => '?api_key=' . $apiKey . '&filter_profit=true', 'desc' => 'Filter positions in profit', 'id' => 'profit-filter'],
                    ['title' => 'Loss Positions', 'params' => '?api_key=' . $apiKey . '&filter_loss=true', 'desc' => 'Filter positions in loss', 'id' => 'loss-filter'],
                    ['title' => 'Filter by Symbol - EURUSD', 'params' => '?api_key=' . $apiKey . '&filter_symbol=EURUSD', 'desc' => 'Get orders for specific symbol', 'id' => 'symbol-filter'],
                ];
                foreach ($ordersExamples as $example):
                ?>
                <div class="example-card p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                    <h4 class="text-sm font-semibold mb-3" style="color: var(--text-primary);"><?php echo $example['title']; ?></h4>
                    <p class="text-xs mb-3" style="color: var(--text-secondary);"><?php echo $example['desc']; ?></p>
                    
                    <div class="relative mb-3">
                        <div class="p-3 rounded-lg api-code text-xs pr-12" style="background-color: var(--input-bg); border: 1px solid var(--input-border); overflow-x: auto;">
                            <code style="color: var(--text-primary);"><?php echo htmlspecialchars($example['params']); ?></code>
                        </div>
                        <button onclick="copyURL('<?php echo $example['id']; ?>', '<?php echo htmlspecialchars($example['params'], ENT_QUOTES); ?>')" class="absolute top-2 right-2 px-2 py-1 rounded-lg text-xs transition-all" style="background-color: var(--bg-secondary); color: var(--text-secondary);" onmouseover="this.style.backgroundColor='var(--bg-tertiary)';" onmouseout="this.style.backgroundColor='var(--bg-secondary)';" title="Copy full URL">
                            <i data-feather="copy" style="width: 12px; height: 12px;"></i>
                        </button>
                    </div>

                    <div class="flex gap-2 items-center">
                        <button onclick="testAPI('<?php echo $example['id']; ?>', '<?php echo htmlspecialchars($example['params'], ENT_QUOTES); ?>')" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all" style="background-color: var(--accent); color: white;" onmouseover="this.style.opacity='0.9';" onmouseout="this.style.opacity='1';">
                            <i data-feather="play" class="inline-block mr-1" style="width: 12px; height: 12px;"></i>
                            Test Request
                        </button>
                        <span id="copy-status-<?php echo $example['id']; ?>" class="text-xs" style="color: var(--success); display: none;">Copied!</span>
                    </div>

                    <div id="response-<?php echo $example['id']; ?>" class="hidden mt-3">
                        <h5 class="text-xs font-semibold mb-2" style="color: var(--text-primary);">Response:</h5>
                        <pre class="p-3 rounded-lg overflow-x-auto text-xs api-code" style="background-color: var(--bg-primary); border: 1px solid var(--border); max-height: 300px;"><code style="color: var(--text-secondary);"></code></pre>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Market Orders Examples -->
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-5" style="color: var(--text-primary);">Market Orders (BUY/SELL)</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php
                $marketExamples = [
                    ['title' => 'BUY EURUSD - 0.01 Lot', 'params' => '?api_key=' . $apiKey . '&action=BUY&symbol=EURUSD&volume=0.01', 'desc' => 'Open buy position at market', 'id' => 'buy-eurusd'],
                    ['title' => 'BUY with SL & TP', 'params' => '?api_key=' . $apiKey . '&action=BUY&symbol=EURUSD&volume=0.01&sl=1.0800&tp=1.1000', 'desc' => 'Buy with stop loss and take profit', 'id' => 'buy-sl-tp'],
                    ['title' => 'SELL GBPUSD - 0.02 Lot', 'params' => '?api_key=' . $apiKey . '&action=SELL&symbol=GBPUSD&volume=0.02', 'desc' => 'Open sell position at market', 'id' => 'sell-gbpusd'],
                    ['title' => 'SELL with Protection', 'params' => '?api_key=' . $apiKey . '&action=SELL&symbol=GBPUSD&volume=0.01&sl=1.2800&tp=1.2600', 'desc' => 'Sell with SL & TP protection', 'id' => 'sell-protected'],
                    ['title' => 'BUY USDJPY - 0.05 Lot', 'params' => '?api_key=' . $apiKey . '&action=BUY&symbol=USDJPY&volume=0.05', 'desc' => 'Larger position size', 'id' => 'buy-usdjpy'],
                    ['title' => 'BUY BTCUSD - Crypto', 'params' => '?api_key=' . $apiKey . '&action=BUY&symbol=BTCUSD&volume=0.01', 'desc' => 'Bitcoin market buy', 'id' => 'buy-btc'],
                ];
                foreach ($marketExamples as $example):
                ?>
                <div class="example-card p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                    <h4 class="text-sm font-semibold mb-3" style="color: var(--text-primary);"><?php echo $example['title']; ?></h4>
                    <p class="text-xs mb-3" style="color: var(--text-secondary);"><?php echo $example['desc']; ?></p>
                    
                    <div class="relative mb-3">
                        <div class="p-3 rounded-lg api-code text-xs pr-12" style="background-color: var(--input-bg); border: 1px solid var(--input-border); overflow-x: auto;">
                            <code style="color: var(--text-primary);"><?php echo htmlspecialchars($example['params']); ?></code>
                        </div>
                        <button onclick="copyURL('<?php echo $example['id']; ?>', '<?php echo htmlspecialchars($example['params'], ENT_QUOTES); ?>')" class="absolute top-2 right-2 px-2 py-1 rounded-lg text-xs transition-all" style="background-color: var(--bg-secondary); color: var(--text-secondary);" onmouseover="this.style.backgroundColor='var(--bg-tertiary)';" onmouseout="this.style.backgroundColor='var(--bg-secondary)';" title="Copy full URL">
                            <i data-feather="copy" style="width: 12px; height: 12px;"></i>
                        </button>
                    </div>

                    <div class="flex gap-2 items-center">
                        <button onclick="testAPI('<?php echo $example['id']; ?>', '<?php echo htmlspecialchars($example['params'], ENT_QUOTES); ?>')" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all" style="background-color: var(--accent); color: white;" onmouseover="this.style.opacity='0.9';" onmouseout="this.style.opacity='1';">
                            <i data-feather="play" class="inline-block mr-1" style="width: 12px; height: 12px;"></i>
                            Test Request
                        </button>
                        <span id="copy-status-<?php echo $example['id']; ?>" class="text-xs" style="color: var(--success); display: none;">Copied!</span>
                    </div>

                    <div id="response-<?php echo $example['id']; ?>" class="hidden mt-3">
                        <h5 class="text-xs font-semibold mb-2" style="color: var(--text-primary);">Response:</h5>
                        <pre class="p-3 rounded-lg overflow-x-auto text-xs api-code" style="background-color: var(--bg-primary); border: 1px solid var(--border); max-height: 300px;"><code style="color: var(--text-secondary);"></code></pre>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Pending Orders Examples -->
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-5" style="color: var(--text-primary);">Pending Orders (LIMIT/STOP)</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php
                $pendingExamples = [
                    ['title' => 'BUY LIMIT - Below Market', 'params' => '?api_key=' . $apiKey . '&action=BUY_LIMIT&symbol=EURUSD&volume=0.01&price=1.0850', 'desc' => 'Buy limit at specified price', 'id' => 'buy-limit'],
                    ['title' => 'BUY LIMIT with Protection', 'params' => '?api_key=' . $apiKey . '&action=BUY_LIMIT&symbol=EURUSD&volume=0.01&price=1.0850&sl=1.0800&tp=1.0950', 'desc' => 'Buy limit with SL & TP', 'id' => 'buy-limit-protected'],
                    ['title' => 'SELL LIMIT - Above Market', 'params' => '?api_key=' . $apiKey . '&action=SELL_LIMIT&symbol=EURUSD&volume=0.01&price=1.1050', 'desc' => 'Sell limit at specified price', 'id' => 'sell-limit'],
                    ['title' => 'BUY STOP - Above Market', 'params' => '?api_key=' . $apiKey . '&action=BUY_STOP&symbol=GBPUSD&volume=0.01&price=1.2750', 'desc' => 'Breakout buy order', 'id' => 'buy-stop'],
                    ['title' => 'SELL STOP - Below Market', 'params' => '?api_key=' . $apiKey . '&action=SELL_STOP&symbol=GBPUSD&volume=0.01&price=1.2650', 'desc' => 'Breakdown sell order', 'id' => 'sell-stop'],
                    ['title' => 'Multi-Level BUY LIMITs', 'params' => '?api_key=' . $apiKey . '&action=BUY_LIMIT&symbol=USDJPY&volume=0.01&price=148.50', 'desc' => 'Grid trading setup', 'id' => 'buy-limit-grid'],
                ];
                foreach ($pendingExamples as $example):
                ?>
                <div class="example-card p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                    <h4 class="text-sm font-semibold mb-3" style="color: var(--text-primary);"><?php echo $example['title']; ?></h4>
                    <p class="text-xs mb-3" style="color: var(--text-secondary);"><?php echo $example['desc']; ?></p>
                    
                    <div class="relative mb-3">
                        <div class="p-3 rounded-lg api-code text-xs pr-12" style="background-color: var(--input-bg); border: 1px solid var(--input-border); overflow-x: auto;">
                            <code style="color: var(--text-primary);"><?php echo htmlspecialchars($example['params']); ?></code>
                        </div>
                        <button onclick="copyURL('<?php echo $example['id']; ?>', '<?php echo htmlspecialchars($example['params'], ENT_QUOTES); ?>')" class="absolute top-2 right-2 px-2 py-1 rounded-lg text-xs transition-all" style="background-color: var(--bg-secondary); color: var(--text-secondary);" onmouseover="this.style.backgroundColor='var(--bg-tertiary)';" onmouseout="this.style.backgroundColor='var(--bg-secondary)';" title="Copy full URL">
                            <i data-feather="copy" style="width: 12px; height: 12px;"></i>
                        </button>
                    </div>

                    <div class="flex gap-2 items-center">
                        <button onclick="testAPI('<?php echo $example['id']; ?>', '<?php echo htmlspecialchars($example['params'], ENT_QUOTES); ?>')" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all" style="background-color: var(--accent); color: white;" onmouseover="this.style.opacity='0.9';" onmouseout="this.style.opacity='1';">
                            <i data-feather="play" class="inline-block mr-1" style="width: 12px; height: 12px;"></i>
                            Test Request
                        </button>
                        <span id="copy-status-<?php echo $example['id']; ?>" class="text-xs" style="color: var(--success); display: none;">Copied!</span>
                    </div>

                    <div id="response-<?php echo $example['id']; ?>" class="hidden mt-3">
                        <h5 class="text-xs font-semibold mb-2" style="color: var(--text-primary);">Response:</h5>
                        <pre class="p-3 rounded-lg overflow-x-auto text-xs api-code" style="background-color: var(--bg-primary); border: 1px solid var(--border); max-height: 300px;"><code style="color: var(--text-secondary);"></code></pre>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Position Management Examples -->
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-5" style="color: var(--text-primary);">Position Management</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php
                $mgmtExamples = [
                    ['title' => 'Close by Ticket', 'params' => '?api_key=' . $apiKey . '&action=CLOSE&ticket=123456', 'desc' => 'Close specific position', 'id' => 'close-ticket'],
                    ['title' => 'Close All Positions', 'params' => '?api_key=' . $apiKey . '&action=CLOSE_ALL&symbol=ALL', 'desc' => 'Close all open positions', 'id' => 'close-all'],
                    ['title' => 'Close All EURUSD', 'params' => '?api_key=' . $apiKey . '&action=CLOSE&symbol=EURUSD', 'desc' => 'Close all positions for symbol', 'id' => 'close-eurusd'],
                    ['title' => 'Close Only Losses', 'params' => '?api_key=' . $apiKey . '&action=CLOSE_LOSS&symbol=ALL', 'desc' => 'Close all losing positions', 'id' => 'close-losses'],
                    ['title' => 'Close Only Profits', 'params' => '?api_key=' . $apiKey . '&action=CLOSE_PROFIT&symbol=ALL', 'desc' => 'Close all profitable positions', 'id' => 'close-profits'],
                    ['title' => 'Close Loss - GBPUSD Only', 'params' => '?api_key=' . $apiKey . '&action=CLOSE_LOSS&symbol=GBPUSD', 'desc' => 'Close GBPUSD losses only', 'id' => 'close-gbpusd-loss'],
                ];
                foreach ($mgmtExamples as $example):
                ?>
                <div class="example-card p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                    <h4 class="text-sm font-semibold mb-3" style="color: var(--text-primary);"><?php echo $example['title']; ?></h4>
                    <p class="text-xs mb-3" style="color: var(--text-secondary);"><?php echo $example['desc']; ?></p>
                    
                    <div class="relative mb-3">
                        <div class="p-3 rounded-lg api-code text-xs pr-12" style="background-color: var(--input-bg); border: 1px solid var(--input-border); overflow-x: auto;">
                            <code style="color: var(--text-primary);"><?php echo htmlspecialchars($example['params']); ?></code>
                        </div>
                        <button onclick="copyURL('<?php echo $example['id']; ?>', '<?php echo htmlspecialchars($example['params'], ENT_QUOTES); ?>')" class="absolute top-2 right-2 px-2 py-1 rounded-lg text-xs transition-all" style="background-color: var(--bg-secondary); color: var(--text-secondary);" onmouseover="this.style.backgroundColor='var(--bg-tertiary)';" onmouseout="this.style.backgroundColor='var(--bg-secondary)';" title="Copy full URL">
                            <i data-feather="copy" style="width: 12px; height: 12px;"></i>
                        </button>
                    </div>

                    <div class="flex gap-2 items-center">
                        <button onclick="testAPI('<?php echo $example['id']; ?>', '<?php echo htmlspecialchars($example['params'], ENT_QUOTES); ?>')" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all" style="background-color: var(--accent); color: white;" onmouseover="this.style.opacity='0.9';" onmouseout="this.style.opacity='1';">
                            <i data-feather="play" class="inline-block mr-1" style="width: 12px; height: 12px;"></i>
                            Test Request
                        </button>
                        <span id="copy-status-<?php echo $example['id']; ?>" class="text-xs" style="color: var(--success); display: none;">Copied!</span>
                    </div>

                    <div id="response-<?php echo $example['id']; ?>" class="hidden mt-3">
                        <h5 class="text-xs font-semibold mb-2" style="color: var(--text-primary);">Response:</h5>
                        <pre class="p-3 rounded-lg overflow-x-auto text-xs api-code" style="background-color: var(--bg-primary); border: 1px solid var(--border); max-height: 300px;"><code style="color: var(--text-secondary);"></code></pre>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Position Modification Examples -->
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-5" style="color: var(--text-primary);">Position Modification (SL/TP)</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php
                $modifyExamples = [
                    ['title' => 'Modify Take Profit', 'params' => '?api_key=' . $apiKey . '&action=MODIFY_TP&ticket=123456&new_value=1.1000', 'desc' => 'Update TP to new level', 'id' => 'modify-tp'],
                    ['title' => 'Modify Stop Loss', 'params' => '?api_key=' . $apiKey . '&action=MODIFY_SL&ticket=123456&new_value=1.0850', 'desc' => 'Update SL to new level', 'id' => 'modify-sl'],
                    ['title' => 'Move to Break Even', 'params' => '?api_key=' . $apiKey . '&action=BREAK_EVEN&ticket=123456', 'desc' => 'Set SL to entry price', 'id' => 'break-even'],
                    ['title' => 'Break Even All', 'params' => '?api_key=' . $apiKey . '&action=BREAK_EVEN_ALL&symbol=ALL', 'desc' => 'Move all positions to BE', 'id' => 'break-even-all'],
                    ['title' => 'Trail Stop Loss - 50 pts', 'params' => '?api_key=' . $apiKey . '&action=TRAIL_SL&ticket=123456&new_value=50', 'desc' => 'Trail SL by 50 points', 'id' => 'trail-50'],
                    ['title' => 'Trail Stop Loss - 100 pts', 'params' => '?api_key=' . $apiKey . '&action=TRAIL_SL&ticket=123456&new_value=100', 'desc' => 'Wider trailing stop', 'id' => 'trail-100'],
                ];
                foreach ($modifyExamples as $example):
                ?>
                <div class="example-card p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                    <h4 class="text-sm font-semibold mb-3" style="color: var(--text-primary);"><?php echo $example['title']; ?></h4>
                    <p class="text-xs mb-3" style="color: var(--text-secondary);"><?php echo $example['desc']; ?></p>
                    
                    <div class="relative mb-3">
                        <div class="p-3 rounded-lg api-code text-xs pr-12" style="background-color: var(--input-bg); border: 1px solid var(--input-border); overflow-x: auto;">
                            <code style="color: var(--text-primary);"><?php echo htmlspecialchars($example['params']); ?></code>
                        </div>
                        <button onclick="copyURL('<?php echo $example['id']; ?>', '<?php echo htmlspecialchars($example['params'], ENT_QUOTES); ?>')" class="absolute top-2 right-2 px-2 py-1 rounded-lg text-xs transition-all" style="background-color: var(--bg-secondary); color: var(--text-secondary);" onmouseover="this.style.backgroundColor='var(--bg-tertiary)';" onmouseout="this.style.backgroundColor='var(--bg-secondary)';" title="Copy full URL">
                            <i data-feather="copy" style="width: 12px; height: 12px;"></i>
                        </button>
                    </div>

                    <div class="flex gap-2 items-center">
                        <button onclick="testAPI('<?php echo $example['id']; ?>', '<?php echo htmlspecialchars($example['params'], ENT_QUOTES); ?>')" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all" style="background-color: var(--accent); color: white;" onmouseover="this.style.opacity='0.9';" onmouseout="this.style.opacity='1';">
                            <i data-feather="play" class="inline-block mr-1" style="width: 12px; height: 12px;"></i>
                            Test Request
                        </button>
                        <span id="copy-status-<?php echo $example['id']; ?>" class="text-xs" style="color: var(--success); display: none;">Copied!</span>
                    </div>

                    <div id="response-<?php echo $example['id']; ?>" class="hidden mt-3">
                        <h5 class="text-xs font-semibold mb-2" style="color: var(--text-primary);">Response:</h5>
                        <pre class="p-3 rounded-lg overflow-x-auto text-xs api-code" style="background-color: var(--bg-primary); border: 1px solid var(--border); max-height: 300px;"><code style="color: var(--text-secondary);"></code></pre>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Pending Order Management -->
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-5" style="color: var(--text-primary);">Pending Order Management</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php
                $deletExamples = [
                    ['title' => 'Delete Order by Ticket', 'params' => '?api_key=' . $apiKey . '&action=DELETE_ORDER&ticket=123456', 'desc' => 'Delete specific pending order', 'id' => 'delete-ticket'],
                    ['title' => 'Delete All Orders', 'params' => '?api_key=' . $apiKey . '&action=DELETE_ALL_ORDERS&symbol=ALL', 'desc' => 'Delete all pending orders', 'id' => 'delete-all'],
                    ['title' => 'Delete EURUSD Orders', 'params' => '?api_key=' . $apiKey . '&action=DELETE_ALL_ORDERS&symbol=EURUSD', 'desc' => 'Delete symbol-specific orders', 'id' => 'delete-eurusd'],
                    ['title' => 'Modify Order Price', 'params' => '?api_key=' . $apiKey . '&action=MODIFY_ORDER&ticket=123456&new_value=1.0900', 'desc' => 'Change pending order price', 'id' => 'modify-order-price'],
                    ['title' => 'Modify Order SL', 'params' => '?api_key=' . $apiKey . '&action=MODIFY_SL&ticket=123456&new_value=1.0850', 'desc' => 'Update pending order SL', 'id' => 'modify-order-sl'],
                    ['title' => 'Modify Order TP', 'params' => '?api_key=' . $apiKey . '&action=MODIFY_TP&ticket=123456&new_value=1.1000', 'desc' => 'Update pending order TP', 'id' => 'modify-order-tp'],
                ];
                foreach ($deletExamples as $example):
                ?>
                <div class="example-card p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                    <h4 class="text-sm font-semibold mb-3" style="color: var(--text-primary);"><?php echo $example['title']; ?></h4>
                    <p class="text-xs mb-3" style="color: var(--text-secondary);"><?php echo $example['desc']; ?></p>
                    
                    <div class="relative mb-3">
                        <div class="p-3 rounded-lg api-code text-xs pr-12" style="background-color: var(--input-bg); border: 1px solid var(--input-border); overflow-x: auto;">
                            <code style="color: var(--text-primary);"><?php echo htmlspecialchars($example['params']); ?></code>
                        </div>
                        <button onclick="copyURL('<?php echo $example['id']; ?>', '<?php echo htmlspecialchars($example['params'], ENT_QUOTES); ?>')" class="absolute top-2 right-2 px-2 py-1 rounded-lg text-xs transition-all" style="background-color: var(--bg-secondary); color: var(--text-secondary);" onmouseover="this.style.backgroundColor='var(--bg-tertiary)';" onmouseout="this.style.backgroundColor='var(--bg-secondary)';" title="Copy full URL">
                            <i data-feather="copy" style="width: 12px; height: 12px;"></i>
                        </button>
                    </div>

                    <div class="flex gap-2 items-center">
                        <button onclick="testAPI('<?php echo $example['id']; ?>', '<?php echo htmlspecialchars($example['params'], ENT_QUOTES); ?>')" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all" style="background-color: var(--accent); color: white;" onmouseover="this.style.opacity='0.9';" onmouseout="this.style.opacity='1';">
                            <i data-feather="play" class="inline-block mr-1" style="width: 12px; height: 12px;"></i>
                            Test Request
                        </button>
                        <span id="copy-status-<?php echo $example['id']; ?>" class="text-xs" style="color: var(--success); display: none;">Copied!</span>
                    </div>

                    <div id="response-<?php echo $example['id']; ?>" class="hidden mt-3">
                        <h5 class="text-xs font-semibold mb-2" style="color: var(--text-primary);">Response:</h5>
                        <pre class="p-3 rounded-lg overflow-x-auto text-xs api-code" style="background-color: var(--bg-primary); border: 1px solid var(--border); max-height: 300px;"><code style="color: var(--text-secondary);"></code></pre>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Trade History Examples -->
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-5" style="color: var(--text-primary);">Trade History Queries</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php
                $historyExamples = [
                    ['title' => 'Today\'s History', 'params' => '?api_key=' . $apiKey . '&history=today', 'desc' => 'All trades closed today', 'id' => 'history-today'],
                    ['title' => 'Last Hour Trades', 'params' => '?api_key=' . $apiKey . '&history=last-hour', 'desc' => 'Trades in last 60 minutes', 'id' => 'history-hour'],
                    ['title' => 'Last 10 Trades', 'params' => '?api_key=' . $apiKey . '&history=last-10', 'desc' => 'Most recent 10 trades', 'id' => 'history-10'],
                    ['title' => 'Last 20 Trades', 'params' => '?api_key=' . $apiKey . '&history=last-20', 'desc' => 'Most recent 20 trades', 'id' => 'history-20'],
                    ['title' => 'Last 7 Days', 'params' => '?api_key=' . $apiKey . '&history=last-7days', 'desc' => 'Weekly trade history', 'id' => 'history-7days'],
                    ['title' => 'Last 30 Days', 'params' => '?api_key=' . $apiKey . '&history=last-30days', 'desc' => 'Monthly trade history', 'id' => 'history-30days'],
                ];
                foreach ($historyExamples as $example):
                ?>
                <div class="example-card p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                    <h4 class="text-sm font-semibold mb-3" style="color: var(--text-primary);"><?php echo $example['title']; ?></h4>
                    <p class="text-xs mb-3" style="color: var(--text-secondary);"><?php echo $example['desc']; ?></p>
                    
                    <div class="relative mb-3">
                        <div class="p-3 rounded-lg api-code text-xs pr-12" style="background-color: var(--input-bg); border: 1px solid var(--input-border); overflow-x: auto;">
                            <code style="color: var(--text-primary);"><?php echo htmlspecialchars($example['params']); ?></code>
                        </div>
                        <button onclick="copyURL('<?php echo $example['id']; ?>', '<?php echo htmlspecialchars($example['params'], ENT_QUOTES); ?>')" class="absolute top-2 right-2 px-2 py-1 rounded-lg text-xs transition-all" style="background-color: var(--bg-secondary); color: var(--text-secondary);" onmouseover="this.style.backgroundColor='var(--bg-tertiary)';" onmouseout="this.style.backgroundColor='var(--bg-secondary)';" title="Copy full URL">
                            <i data-feather="copy" style="width: 12px; height: 12px;"></i>
                        </button>
                    </div>

                    <div class="flex gap-2 items-center">
                        <button onclick="testAPI('<?php echo $example['id']; ?>', '<?php echo htmlspecialchars($example['params'], ENT_QUOTES); ?>')" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all" style="background-color: var(--accent); color: white;" onmouseover="this.style.opacity='0.9';" onmouseout="this.style.opacity='1';">
                            <i data-feather="play" class="inline-block mr-1" style="width: 12px; height: 12px;"></i>
                            Test Request
                        </button>
                        <span id="copy-status-<?php echo $example['id']; ?>" class="text-xs" style="color: var(--success); display: none;">Copied!</span>
                    </div>

                    <div id="response-<?php echo $example['id']; ?>" class="hidden mt-3">
                        <h5 class="text-xs font-semibold mb-2" style="color: var(--text-primary);">Response:</h5>
                        <pre class="p-3 rounded-lg overflow-x-auto text-xs api-code" style="background-color: var(--bg-primary); border: 1px solid var(--border); max-height: 300px;"><code style="color: var(--text-secondary);"></code></pre>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Profit/Loss Tracking -->
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-5" style="color: var(--text-primary);">Profit/Loss Tracking</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php
                $profitExamples = [
                    ['title' => 'Today\'s Profit', 'params' => '?api_key=' . $apiKey . '&profit=today', 'desc' => 'Total P/L for today', 'id' => 'profit-today'],
                    ['title' => 'Last Hour Profit', 'params' => '?api_key=' . $apiKey . '&profit=last-hour', 'desc' => 'P/L in last 60 minutes', 'id' => 'profit-hour'],
                    ['title' => 'This Week Profit', 'params' => '?api_key=' . $apiKey . '&profit=this-week', 'desc' => 'Weekly profit/loss', 'id' => 'profit-week'],
                    ['title' => 'This Month Profit', 'params' => '?api_key=' . $apiKey . '&profit=this-month', 'desc' => 'Monthly profit/loss', 'id' => 'profit-month'],
                    ['title' => 'Last 7 Days Profit', 'params' => '?api_key=' . $apiKey . '&profit=last-7days', 'desc' => 'Rolling 7-day P/L', 'id' => 'profit-7days'],
                    ['title' => 'Last 30 Days Profit', 'params' => '?api_key=' . $apiKey . '&profit=last-30days', 'desc' => 'Rolling 30-day P/L', 'id' => 'profit-30days'],
                ];
                foreach ($profitExamples as $example):
                ?>
                <div class="example-card p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                    <h4 class="text-sm font-semibold mb-3" style="color: var(--text-primary);"><?php echo $example['title']; ?></h4>
                    <p class="text-xs mb-3" style="color: var(--text-secondary);"><?php echo $example['desc']; ?></p>
                    
                    <div class="relative mb-3">
                        <div class="p-3 rounded-lg api-code text-xs pr-12" style="background-color: var(--input-bg); border: 1px solid var(--input-border); overflow-x: auto;">
                            <code style="color: var(--text-primary);"><?php echo htmlspecialchars($example['params']); ?></code>
                        </div>
                        <button onclick="copyURL('<?php echo $example['id']; ?>', '<?php echo htmlspecialchars($example['params'], ENT_QUOTES); ?>')" class="absolute top-2 right-2 px-2 py-1 rounded-lg text-xs transition-all" style="background-color: var(--bg-secondary); color: var(--text-secondary);" onmouseover="this.style.backgroundColor='var(--bg-tertiary)';" onmouseout="this.style.backgroundColor='var(--bg-secondary)';" title="Copy full URL">
                            <i data-feather="copy" style="width: 12px; height: 12px;"></i>
                        </button>
                    </div>

                    <div class="flex gap-2 items-center">
                        <button onclick="testAPI('<?php echo $example['id']; ?>', '<?php echo htmlspecialchars($example['params'], ENT_QUOTES); ?>')" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all" style="background-color: var(--accent); color: white;" onmouseover="this.style.opacity='0.9';" onmouseout="this.style.opacity='1';">
                            <i data-feather="play" class="inline-block mr-1" style="width: 12px; height: 12px;"></i>
                            Test Request
                        </button>
                        <span id="copy-status-<?php echo $example['id']; ?>" class="text-xs" style="color: var(--success); display: none;">Copied!</span>
                    </div>

                    <div id="response-<?php echo $example['id']; ?>" class="hidden mt-3">
                        <h5 class="text-xs font-semibold mb-2" style="color: var(--text-primary);">Response:</h5>
                        <pre class="p-3 rounded-lg overflow-x-auto text-xs api-code" style="background-color: var(--bg-primary); border: 1px solid var(--border); max-height: 300px;"><code style="color: var(--text-secondary);"></code></pre>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
    <div class="p-8 rounded-2xl" style="background-color: var(--card-bg); border: 2px solid var(--danger); max-width: 600px; width: 100%; max-height: 90vh; overflow-y: auto;">
        <div class="flex items-start mb-6">
            <div class="w-12 h-12 rounded-full flex items-center justify-center mr-4 flex-shrink-0" style="background-color: var(--danger-bg);">
                <i data-feather="alert-triangle" style="width: 24px; height: 24px; color: var(--danger);"></i>
            </div>
            <div class="flex-1">
                <h3 class="text-xl font-bold mb-2" style="color: var(--danger);">Confirm Trading Action</h3>
                <p id="confirmMessage" class="text-sm" style="color: var(--text-secondary);"></p>
            </div>
        </div>
        <div class="p-4 rounded-lg mb-6" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
            <p class="text-xs font-semibold mb-3" style="color: var(--text-primary);">This will execute a REAL trade on your MT5 account:</p>
            <div class="p-3 rounded-lg" style="background-color: var(--bg-primary); border: 1px solid var(--border); max-height: 200px; overflow-y: auto;">
                <code id="confirmAction" class="api-code text-xs" style="color: var(--accent); word-break: break-all; white-space: pre-wrap; display: block;"></code>
            </div>
        </div>
        <div class="flex gap-3">
            <button onclick="executeConfirmedAPI()" class="flex-1 px-4 py-3 rounded-lg text-sm font-semibold transition-all" style="background-color: var(--danger); color: white;" onmouseover="this.style.opacity='0.9';" onmouseout="this.style.opacity='1';">
                <i data-feather="zap" class="inline-block mr-2" style="width: 14px; height: 14px;"></i>
                Yes, Execute Trade
            </button>
            <button onclick="closeConfirmModal()" class="flex-1 px-4 py-3 rounded-lg text-sm font-semibold transition-all" style="background-color: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border);" onmouseover="this.style.backgroundColor='var(--bg-tertiary)';" onmouseout="this.style.backgroundColor='var(--bg-secondary)';">
                Cancel
            </button>
        </div>
    </div>
</div>

<script>
function copyURL(id, params) {
    const baseUrl = <?php echo json_encode($baseUrl); ?>;
    const fullURL = `${baseUrl}/orders-api-v1/orders-api.php${params}`;
    
    navigator.clipboard.writeText(fullURL).then(() => {
        const statusSpan = document.getElementById('copy-status-' + id);
        if (statusSpan) {
            statusSpan.style.display = 'inline';
            setTimeout(() => {
                statusSpan.style.display = 'none';
            }, 2000);
        }
    }).catch(err => {
        console.error('Failed to copy:', err);
    });
}

function testAPI(id, params) {
    // Check if this is a trading action that needs confirmation
    const tradingActions = ['BUY', 'SELL', 'CLOSE', 'BREAK_EVEN', 'TRAIL_SL', 'BUY_LIMIT', 'SELL_LIMIT', 'BUY_STOP', 'SELL_STOP'];
    const actionMatch = params.match(/[?&]action=([^&]+)/);
    
    if (actionMatch && tradingActions.includes(actionMatch[1])) {
        // Show confirmation modal for trading actions
        showConfirmModal(id, params, actionMatch[1]);
    } else {
        // Execute non-trading actions directly (GET_HISTORY, CALCULATE_PROFIT, etc.)
        executeAPI(id, params);
    }
}

function showConfirmModal(id, params, action) {
    const modal = document.getElementById('confirmModal');
    const message = document.getElementById('confirmMessage');
    const actionText = document.getElementById('confirmAction');
    
    // Store the pending request
    window.pendingAPIRequest = { id, params };
    
    // Set message based on action
    let warningMsg = '';
    if (action.includes('BUY')) {
        warningMsg = 'You are about to open a BUY position on your live MT5 account.';
    } else if (action.includes('SELL')) {
        warningMsg = 'You are about to open a SELL position on your live MT5 account.';
    } else if (action === 'CLOSE') {
        warningMsg = 'You are about to CLOSE an existing position on your MT5 account.';
    } else if (action === 'BREAK_EVEN') {
        warningMsg = 'You are about to move stop loss to BREAK EVEN on an existing position.';
    } else if (action === 'TRAIL_SL') {
        warningMsg = 'You are about to apply a TRAILING STOP to an existing position.';
    } else {
        warningMsg = 'You are about to execute a trading action on your MT5 account.';
    }
    
    message.textContent = warningMsg;
    actionText.textContent = params;
    modal.style.display = 'flex';
    feather.replace();
}

function closeConfirmModal() {
    document.getElementById('confirmModal').style.display = 'none';
    window.pendingAPIRequest = null;
}

function executeConfirmedAPI() {
    if (window.pendingAPIRequest) {
        const { id, params } = window.pendingAPIRequest;
        executeAPI(id, params);
        closeConfirmModal();
    }
}

function executeAPI(id, params) {
    const baseUrl = <?php echo json_encode($baseUrl); ?>;
    const responseDiv = document.getElementById('response-' + id);
    const codeBlock = responseDiv.querySelector('code');
    
    const url = `${baseUrl}/orders-api-v1/orders-api.php${params}`;
    
    responseDiv.classList.remove('hidden');
    codeBlock.textContent = 'Loading...';
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            codeBlock.textContent = JSON.stringify(data, null, 2);
            feather.replace();
        })
        .catch(error => {
            codeBlock.textContent = 'Error: ' + error.message;
        });
}
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layouts/app.php';
?>
