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

$title = 'TMA + CG API Guide';
$page = 'tma-cg-api-guide';
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
.zone-visual {
    display: flex;
    flex-direction: column;
    height: 200px;
    border-radius: 12px;
    overflow: hidden;
    margin: 1.5rem 0;
    border: 1px solid var(--border);
}
.zone-segment {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
    color: white;
    border-bottom: 2px solid var(--bg-primary);
}
.zone-segment:last-child {
    border-bottom: none;
}
.premium-zone { background: linear-gradient(135deg, #F44336, #EF5350); }
.equilibrium-zone { background: linear-gradient(135deg, #9E9E9E, #BDBDBD); }
.discount-zone { background: linear-gradient(135deg, #4CAF50, #66BB6A); }
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
                <p class="text-sm mb-3" style="color: var(--text-secondary);">This API requires the TMA CG Data EA to be running on an MT5 chart. The EA loads the TMA + CG indicator and provides real-time premium/discount zone analysis.</p>
                <a href="/download-eas" class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium transition-colors" style="background: linear-gradient(135deg, #9C27B0, #BA68C8); color: white;">
                    <i data-feather="download" class="mr-2" style="width: 16px; height: 16px;"></i>
                    Download TMA CG Data EA
                </a>
            </div>
        </div>
    </div>

    <!-- Hero Header -->
    <div class="mb-10">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-4xl font-bold mb-3 tracking-tight" style="color: var(--text-primary);">
                    TMA + CG API
                    <span class="section-badge ml-3" style="background: linear-gradient(135deg, #9C27B0, #BA68C8); color: white;">v1.0</span>
                </h1>
                <p class="text-lg" style="color: var(--text-secondary);">Real-time premium/discount zone detection with TMA (Triangular Moving Average) and Center of Gravity</p>
            </div>
        </div>
        
        <!-- Features Banner -->
        <div class="p-6 rounded-2xl gradient-bg" style="border: 1px solid var(--border);">
            <div class="flex items-start">
                <div class="flex-shrink-0 mr-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background: linear-gradient(135deg, #9C27B0, #BA68C8);">
                        <i data-feather="activity" style="width: 24px; height: 24px; color: white;"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">What is TMA + CG?</h3>
                    <p class="text-sm mb-4" style="color: var(--text-secondary);">TMA + CG combines Triangular Moving Average with Center of Gravity to create dynamic bands that identify premium and discount zones. The API provides:</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm" style="color: var(--text-secondary);">
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #9C27B0;"></i>
                            <span><strong>Zone Detection:</strong> Premium (above TMA), Discount (below TMA), Equilibrium (at TMA)</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #9C27B0;"></i>
                            <span><strong>Zone Percentage:</strong> How far price is from TMA middle to outer band (0-100%)</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #9C27B0;"></i>
                            <span><strong>Dynamic Bands:</strong> 7 deviation levels using Fibonacci-based multipliers</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #9C27B0;"></i>
                            <span><strong>Multi-Instrument:</strong> Works on any symbol and timeframe</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Zone Visual Explanation -->
    <div class="mb-8 p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
        <h3 class="text-xl font-semibold mb-4" style="color: var(--text-primary);">Understanding Premium & Discount Zones</h3>
        
        <div class="mb-6">
            <div class="zone-visual">
                <div class="zone-segment premium-zone">PREMIUM ZONE<br>Price above TMA = Potential Sell</div>
                <div class="zone-segment equilibrium-zone">EQUILIBRIUM<br>Price at TMA Middle = Fair Value</div>
                <div class="zone-segment discount-zone">DISCOUNT ZONE<br>Price below TMA = Potential Buy</div>
            </div>
            <ul class="text-sm space-y-2" style="color: var(--text-secondary);">
                <li><strong>Premium Zone:</strong> Price is above the TMA middle line. Market is trading at premium prices (good for selling/shorting)</li>
                <li><strong>Discount Zone:</strong> Price is below the TMA middle line. Market is trading at discount prices (good for buying/longing)</li>
                <li><strong>Percentage:</strong> Shows how far price has moved from TMA middle towards the outer band 7 (0% = at TMA, 100% = at outer band)</li>
            </ul>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Quick Start -->
    <div class="mb-12">
        <h2 class="text-3xl font-bold mb-6" style="color: var(--text-primary);">Quick Start</h2>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="w-10 h-10 rounded-full flex items-center justify-center mb-4" style="background: linear-gradient(135deg, #9C27B0, #BA68C8);">
                    <span class="text-lg font-bold" style="color: white;">1</span>
                </div>
                <h3 class="text-lg font-semibold mb-2" style="color: var(--text-primary);">Install & Run EA</h3>
                <p class="text-sm" style="color: var(--text-secondary);">Download TMA CG Data EA from Download EAs page and attach it to any MT5 chart</p>
            </div>
            
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="w-10 h-10 rounded-full flex items-center justify-center mb-4" style="background: linear-gradient(135deg, #9C27B0, #BA68C8);">
                    <span class="text-lg font-bold" style="color: white;">2</span>
                </div>
                <h3 class="text-lg font-semibold mb-2" style="color: var(--text-primary);">Get Your API Key</h3>
                <p class="text-sm mb-2" style="color: var(--text-secondary);">Copy your API key from settings</p>
                <div class="p-3 rounded-lg" style="background-color: var(--input-bg); border: 1px solid var(--input-border);">
                    <code class="text-xs" style="color: var(--text-primary);"><?= htmlspecialchars($apiKey) ?></code>
                </div>
            </div>
            
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="w-10 h-10 rounded-full flex items-center justify-center mb-4" style="background: linear-gradient(135deg, #9C27B0, #BA68C8);">
                    <span class="text-lg font-bold" style="color: white;">3</span>
                </div>
                <h3 class="text-lg font-semibold mb-2" style="color: var(--text-primary);">Make API Request</h3>
                <p class="text-sm" style="color: var(--text-secondary);">Send GET request with symbol and timeframe parameters</p>
            </div>
        </div>
    </div>

    <!-- API Endpoint -->
    <div class="mb-12">
        <h2 class="text-3xl font-bold mb-6" style="color: var(--text-primary);">API Endpoint</h2>
        
        <div class="p-6 rounded-2xl example-card" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium px-3 py-1 rounded-full" style="background: linear-gradient(135deg, #4CAF50, #66BB6A); color: white;">GET</span>
                <button onclick="copyToClipboard('<?= htmlspecialchars($baseUrl) ?>/tma-cg-api-v1/tma-cg-api.php')" class="text-sm px-3 py-1 rounded-lg transition-colors" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);">
                    <i data-feather="copy" style="width: 14px; height: 14px;"></i>
                </button>
            </div>
            <code class="api-code" style="color: var(--text-primary); display: block; word-break: break-all;"><?= htmlspecialchars($baseUrl) ?>/tma-cg-api-v1/tma-cg-api.php</code>
        </div>
    </div>

    <!-- Parameters -->
    <div class="mb-12">
        <h2 class="text-3xl font-bold mb-6" style="color: var(--text-primary);">Parameters</h2>
        
        <div class="overflow-x-auto rounded-2xl" style="border: 1px solid var(--border);">
            <table class="w-full">
                <thead style="background-color: var(--input-bg);">
                    <tr style="border-bottom: 1px solid var(--border);">
                        <th class="px-6 py-4 text-left text-sm font-semibold" style="color: var(--text-primary);">Parameter</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold" style="color: var(--text-primary);">Type</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold" style="color: var(--text-primary);">Required</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold" style="color: var(--text-primary);">Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-bottom: 1px solid var(--border); background-color: var(--card-bg);">
                        <td class="px-6 py-4 text-sm font-mono" style="color: var(--text-primary);">api_key</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">string</td>
                        <td class="px-6 py-4"><span class="text-xs font-semibold px-2 py-1 rounded" style="background-color: rgba(244, 67, 54, 0.1); color: #F44336;">Yes</span></td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">Your API authentication key</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border); background-color: var(--card-bg);">
                        <td class="px-6 py-4 text-sm font-mono" style="color: var(--text-primary);">symbol</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">string</td>
                        <td class="px-6 py-4"><span class="text-xs font-semibold px-2 py-1 rounded" style="background-color: rgba(244, 67, 54, 0.1); color: #F44336;">Yes</span></td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">Trading symbol (e.g., XAUUSD, EURUSD)</td>
                    </tr>
                    <tr style="background-color: var(--card-bg);">
                        <td class="px-6 py-4 text-sm font-mono" style="color: var(--text-primary);">timeframe</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">string</td>
                        <td class="px-6 py-4"><span class="text-xs font-semibold px-2 py-1 rounded" style="background-color: rgba(76, 175, 80, 0.1); color: #4CAF50;">No</span></td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">Timeframe (M1, M5, M15, H1, H4, D1). Default: M1</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Response Format -->
    <div class="mb-12">
        <h2 class="text-3xl font-bold mb-6" style="color: var(--text-primary);">Response Format</h2>
        
        <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <pre class="response-preview" style="color: var(--text-primary);"><code>{
  "status": "success",
  "symbol": "XAUUSD",
  "timeframe": "M1",
  "zone": "premium",
  "percentage": 45.23,
  "current_price": 2667.45,
  "tma_middle": 2665.00,
  "upper_band_1": 2666.50,
  "lower_band_1": 2663.50,
  "upper_band_7": 2670.00,
  "lower_band_7": 2660.00,
  "timestamp": "2026-01-14 10:30"
}</code></pre>
        </div>
        
        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-4 rounded-xl" style="background-color: var(--input-bg); border: 1px solid var(--input-border);">
                <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">zone</h4>
                <p class="text-xs" style="color: var(--text-secondary);">"premium", "discount", or "equilibrium"</p>
            </div>
            <div class="p-4 rounded-xl" style="background-color: var(--input-bg); border: 1px solid var(--input-border);">
                <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">percentage</h4>
                <p class="text-xs" style="color: var(--text-secondary);">0-100%: distance from TMA middle to outer band 7</p>
            </div>
            <div class="p-4 rounded-xl" style="background-color: var(--input-bg); border: 1px solid var(--input-border);">
                <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">upper_band_1 / lower_band_1</h4>
                <p class="text-xs" style="color: var(--text-secondary);">Innermost bands (1.618x deviation)</p>
            </div>
            <div class="p-4 rounded-xl" style="background-color: var(--input-bg); border: 1px solid var(--input-border);">
                <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">upper_band_7 / lower_band_7</h4>
                <p class="text-xs" style="color: var(--text-secondary);">Outermost bands (3.236x deviation)</p>
            </div>
        </div>
    </div>

    <!-- Live Examples -->
    <div class="mb-12">
        <h2 class="text-3xl font-bold mb-6" style="color: var(--text-primary);">
            <i data-feather="zap" class="inline mr-2" style="width: 32px; height: 32px;"></i>
            Live API Examples
        </h2>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- XAUUSD Example -->
            <div class="p-6 rounded-2xl example-card" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">Gold (XAUUSD) M1</h3>
                <div class="p-3 rounded-lg mb-4" style="background-color: var(--input-bg); border: 1px solid var(--input-border); font-family: 'Fira Code', monospace; font-size: 0.75rem; overflow-x: auto;">
                    <code style="color: var(--text-primary);"><?= htmlspecialchars($baseUrl) ?>/tma-cg-api-v1/tma-cg-api.php?api_key=<?= htmlspecialchars($apiKey) ?>&symbol=XAUUSD&timeframe=M1</code>
                </div>
                <button onclick="testTmaApi('XAUUSD', 'M1')" class="w-full py-3 rounded-xl font-medium transition-all" style="background: linear-gradient(135deg, #9C27B0, #BA68C8); color: white;">
                    <i data-feather="play" class="inline mr-2" style="width: 16px; height: 16px;"></i>
                    Test Now
                </button>
                <div id="result-XAUUSD-M1" class="mt-4 hidden"></div>
            </div>

            <!-- EURUSD Example -->
            <div class="p-6 rounded-2xl example-card" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">Euro (EURUSD) H1</h3>
                <div class="p-3 rounded-lg mb-4" style="background-color: var(--input-bg); border: 1px solid var(--input-border); font-family: 'Fira Code', monospace; font-size: 0.75rem; overflow-x: auto;">
                    <code style="color: var(--text-primary);"><?= htmlspecialchars($baseUrl) ?>/tma-cg-api-v1/tma-cg-api.php?api_key=<?= htmlspecialchars($apiKey) ?>&symbol=EURUSD&timeframe=H1</code>
                </div>
                <button onclick="testTmaApi('EURUSD', 'H1')" class="w-full py-3 rounded-xl font-medium transition-all" style="background: linear-gradient(135deg, #9C27B0, #BA68C8); color: white;">
                    <i data-feather="play" class="inline mr-2" style="width: 16px; height: 16px;"></i>
                    Test Now
                </button>
                <div id="result-EURUSD-H1" class="mt-4 hidden"></div>
            </div>
        </div>
    </div>

    <!-- Error Responses -->
    <div class="mb-12">
        <h2 class="text-3xl font-bold mb-6" style="color: var(--text-primary);">Error Responses</h2>
        
        <div class="space-y-4">
            <div class="p-5 rounded-xl" style="background-color: var(--card-bg); border: 1px solid rgba(244, 67, 54, 0.3);">
                <h4 class="text-sm font-semibold mb-2" style="color: #F44336;">Invalid API Key</h4>
                <pre class="text-xs" style="color: var(--text-secondary);"><code>{"error": "Invalid API key"}</code></pre>
            </div>
            
            <div class="p-5 rounded-xl" style="background-color: var(--card-bg); border: 1px solid rgba(255, 152, 0, 0.3);">
                <h4 class="text-sm font-semibold mb-2" style="color: #FF9800;">EA Not Running</h4>
                <pre class="text-xs" style="color: var(--text-secondary);"><code>{"status": "timeout", "error": "MT5 Data Server not connected", "message": "No Expert Advisor (EA) is currently running..."}</code></pre>
            </div>
        </div>
    </div>
</div>

<script>
// Feather icons
feather.replace();

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Copied to clipboard!');
    });
}

async function testTmaApi(symbol, timeframe) {
    const resultDiv = document.getElementById(`result-${symbol}-${timeframe}`);
    resultDiv.classList.remove('hidden');
    resultDiv.innerHTML = `<div class="p-4 rounded-xl" style="background-color: var(--input-bg); border: 1px solid var(--input-border);"><div style="display: flex; align-items: center;"><div style="width: 20px; height: 20px; border: 2px solid var(--border); border-top-color: #9C27B0; border-radius: 50%; animation: spin 1s linear infinite; margin-right: 12px;"></div><span style="color: var(--text-secondary); font-size: 14px;">Testing API...</span></div></div>`;
    
    try {
        const response = await fetch(`<?= htmlspecialchars($baseUrl) ?>/tma-cg-api-v1/tma-cg-api.php?api_key=<?= htmlspecialchars($apiKey) ?>&symbol=${symbol}&timeframe=${timeframe}`);
        const data = await response.json();
        
        let zoneColor;
        if (data.zone === 'premium') zoneColor = '#F44336';
        else if (data.zone === 'discount') zoneColor = '#4CAF50';
        else zoneColor = '#9E9E9E';
        
        resultDiv.innerHTML = `
            <div class="p-5 rounded-xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-semibold" style="color: ${zoneColor};">● ${data.zone ? data.zone.toUpperCase() : 'N/A'} (${data.percentage || 0}%)</span>
                    <span class="text-xs" style="color: var(--text-secondary);">${data.timestamp || ''}</span>
                </div>
                <div class="grid grid-cols-2 gap-3 text-xs" style="color: var(--text-secondary);">
                    <div><strong>Price:</strong> ${data.current_price || 'N/A'}</div>
                    <div><strong>TMA:</strong> ${data.tma_middle || 'N/A'}</div>
                    <div><strong>Upper Band 1:</strong> ${data.upper_band_1 || 'N/A'}</div>
                    <div><strong>Lower Band 1:</strong> ${data.lower_band_1 || 'N/A'}</div>
                </div>
                <pre class="mt-3 p-3 rounded text-xs overflow-x-auto" style="background-color: var(--input-bg); color: var(--text-primary);">${JSON.stringify(data, null, 2)}</pre>
            </div>
        `;
    } catch (error) {
        resultDiv.innerHTML = `<div class="p-4 rounded-xl" style="background-color: rgba(244, 67, 54, 0.1); border: 1px solid #F44336; color: #F44336;">Error: ${error.message}</div>`;
    }
    
    feather.replace();
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/app.php';
?>
