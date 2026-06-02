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

$title = 'Markets Brain API Guide';
$page  = 'markets-brain-api-guide';
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
                <p class="text-sm mb-3" style="color: var(--text-secondary);">This API requires the <strong style="color: var(--text-primary);">Markets Brain API EA</strong> to be running on an MT5 chart. The EA polls every second and runs all 22 neural modules on any requested symbol, returning raw brain state scores, module insights, and a dominant synthesis thought — no trade signals.</p>
                <a href="/download-eas" class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium transition-colors" style="background: linear-gradient(135deg, #10B981, #0EA5E9); color: white;">
                    <i data-feather="download" class="mr-2" style="width: 16px; height: 16px;"></i>
                    Download Markets Brain EA
                </a>
            </div>
        </div>
    </div>

    <!-- Hero Header -->
    <div class="mb-10">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-4xl font-bold mb-3 tracking-tight" style="color: var(--text-primary);">
                    Markets Brain API
                    <span class="section-badge ml-3" style="background: linear-gradient(135deg, #10B981, #0EA5E9); color: white;">v1.0</span>
                </h1>
                <p class="text-lg" style="color: var(--text-secondary);">22-module neural brain — raw market state scores and thoughts for any symbol, on demand</p>
            </div>
        </div>

        <!-- Features Banner -->
        <div class="p-6 rounded-2xl gradient-bg" style="border: 1px solid var(--border);">
            <div class="flex items-start">
                <div class="flex-shrink-0 mr-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background: linear-gradient(135deg, #10B981, #0EA5E9);">
                        <i data-feather="cpu" style="width: 24px; height: 24px; color: white;"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">What is the Markets Brain API?</h3>
                    <p class="text-sm mb-4" style="color: var(--text-secondary);">Send a symbol — the EA responds with the full output of 22 independent neural modules running simultaneously on live MT5 data. Each module scores the market from &minus;1.0 (strongly bearish) to +1.0 (strongly bullish) with a weighted confidence, an interpretation type (BULL / BEAR / NEUTRAL / WARNING), and a natural-language thought. A synthesis module aggregates all 22 scores into a single brain state. <strong style="color: var(--text-primary);">No BUY/SELL signals are produced</strong> — only raw state observations for you to interpret.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm" style="color: var(--text-secondary);">
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #10B981;"></i>
                            <span><strong>22 Independent Modules:</strong> Each module analyzes a different dimension — momentum, volume, session, traps, liquidity, breakout, supply/demand, and more</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #10B981;"></i>
                            <span><strong>Brain State Score:</strong> Confidence-weighted synthesis of all modules into one overall market reading with conflict measurement</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #10B981;"></i>
                            <span><strong>Regime Detection:</strong> Classifies market as RANGING, TREND_UP, TREND_DOWN, or VOLATILE based on multi-module convergence</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #10B981;"></i>
                            <span><strong>Natural-Language Thoughts:</strong> Every module emits a short human-readable observation — the dominant thought surfaces as the brain's primary insight</span>
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
                <div class="payload-field-badge mb-3">Price</div>
                <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Live Price Info</h4>
                <ul class="text-xs space-y-1" style="color: var(--text-secondary);">
                    <li><code>price.bid</code></li>
                    <li><code>price.ask</code></li>
                    <li><code>price.spread</code></li>
                    <li><code>server_time</code></li>
                </ul>
            </div>
            <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                <div class="payload-field-badge mb-3">Brain</div>
                <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Brain State</h4>
                <ul class="text-xs space-y-1" style="color: var(--text-secondary);">
                    <li><code>brain.score</code></li>
                    <li><code>brain.confidence</code></li>
                    <li><code>brain.conflict</code></li>
                    <li><code>brain.regime</code></li>
                    <li><code>brain.trap_active</code></li>
                    <li><code>brain.dominant_thought</code></li>
                </ul>
            </div>
            <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                <div class="payload-field-badge mb-3">Modules</div>
                <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">22-Module Array</h4>
                <ul class="text-xs space-y-1" style="color: var(--text-secondary);">
                    <li><code>modules[n].id</code></li>
                    <li><code>modules[n].name</code></li>
                    <li><code>modules[n].score</code></li>
                    <li><code>modules[n].weight</code></li>
                    <li><code>modules[n].etype</code></li>
                    <li><code>modules[n].thought</code></li>
                </ul>
            </div>
            <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                <div class="payload-field-badge mb-3">Scoring</div>
                <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Score Types</h4>
                <ul class="text-xs space-y-1" style="color: var(--text-secondary);">
                    <li class="opacity-70">&minus;1.0 &rarr; strongly bearish</li>
                    <li class="opacity-70">0.0 &rarr; neutral</li>
                    <li class="opacity-70">+1.0 &rarr; strongly bullish</li>
                    <li><code>etype</code>: BULL / BEAR</li>
                    <li><code>etype</code>: NEUTRAL / WARNING</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- 22 Neural Modules -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-6" style="color: var(--text-primary);">The 22 Neural Modules</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">

            <?php
            $modules = [
                [0,  'TICK_SENSE',    'zap',           '#10B981', '#0EA5E9', 'Tick-by-tick microstructure and momentum direction from the bid/ask flow'],
                [1,  'MEMORY',        'archive',        '#0EA5E9', '#6366F1', 'Recent bar memory — how price has behaved in the last N candles'],
                [2,  'ANTICIPATION',  'eye',            '#6366F1', '#8B5CF6', 'Pre-movement pressure buildup — detects coiling before expansion'],
                [3,  'UNCERTAINTY',   'help-circle',    '#8B5CF6', '#EC4899', 'Volatility and uncertainty gauge — high ATR spread signals indecision'],
                [4,  'MOMENTUM',      'trending-up',    '#10B981', '#0EA5E9', 'Price momentum via ROC and RSI — measures rate and direction of change'],
                [5,  'VOLUME',        'bar-chart-2',    '#0EA5E9', '#10B981', 'Volume analysis — detects surge, exhaustion, and divergence patterns'],
                [6,  'SESSION',       'clock',          '#F59E0B', '#F97316', 'Session context — London, NY, Tokyo, Sydney overlap scoring'],
                [7,  'SR_LEVELS',     'layers',         '#10B981', '#0EA5E9', 'Support and resistance proximity — price position relative to key levels'],
                [8,  'PATTERN',       'grid',           '#8B5CF6', '#6366F1', 'Candlestick pattern recognition — engulfing, pin bars, doji, and more'],
                [9,  'ORDER_FLOW',    'shuffle',        '#0EA5E9', '#6366F1', 'Order flow pressure analysis — imbalance between buying and selling force'],
                [10, 'TRAP_SENSE',    'alert-triangle', '#EF4444', '#F97316', 'Bull and bear trap detection — breakouts that fail and reverse'],
                [11, 'TREND',         'activity',       '#10B981', '#0EA5E9', 'Multi-bar trend direction and strength over short and medium horizons'],
                [12, 'MULTI_TF',      'maximize',       '#6366F1', '#8B5CF6', 'Multi-timeframe alignment — agreement between M5, M15, H1, H4 trend'],
                [13, 'LIQUIDITY',     'droplet',        '#0EA5E9', '#10B981', 'Liquidity pool proximity — areas above swing highs and below swing lows'],
                [14, 'ACCUMULATION',  'download',       '#10B981', '#34D399', 'Accumulation phase detection — quiet range-building before markup'],
                [15, 'DISTRIBUTION',  'upload',         '#EF4444', '#F87171', 'Distribution phase detection — range-building before markdown'],
                [16, 'BREAKOUT',      'external-link',  '#F59E0B', '#10B981', 'Breakout strength — velocity and volume behind a level violation'],
                [17, 'DEVILS_EYE',    'eye-off',        '#EF4444', '#8B5CF6', 'Devil\'s advocate — challenges prevailing bias, flags overextension'],
                [18, 'REVERSALS',     'refresh-cw',     '#8B5CF6', '#EC4899', 'Reversal signal detection — exhaustion wicks, climax bars, divergence'],
                [19, 'PATTERN_FAIL',  'x-circle',       '#EF4444', '#F97316', 'Pattern failure detection — setups that form then break in the wrong direction'],
                [20, 'SUPPLY_DEMAND', 'package',        '#10B981', '#0EA5E9', 'Supply and demand zone analysis — imbalance origins with fresh vs. tested zones'],
                [21, 'SYNTHESIS',     'cpu',            '#10B981', '#0EA5E9', 'Weighted synthesis of all 22 modules — the brain\'s final state and dominant thought'],
            ];
            foreach ($modules as $mod):
            ?>
            <div class="p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-center mb-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center mr-3 flex-shrink-0" style="background: linear-gradient(135deg, <?= $mod[3] ?>, <?= $mod[4] ?>);">
                        <i data-feather="<?= $mod[2] ?>" style="width: 16px; height: 16px; color: white;"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="method-badge" style="background: rgba(16,185,129,0.12); color: #10B981;"><?= $mod[0] ?></span>
                            <h3 class="text-sm font-bold" style="color: var(--text-primary);"><?= $mod[1] ?></h3>
                        </div>
                    </div>
                </div>
                <p class="text-xs" style="color: var(--text-secondary);"><?= $mod[5] ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Module output fields explanation -->
        <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <h3 class="text-base font-semibold mb-2" style="color: var(--text-primary);">Module Output Fields</h3>
            <p class="text-sm mb-5" style="color: var(--text-secondary);">Every module in the <code>modules</code> array returns the same six fields. The synthesis module (id 21) aggregates all weighted scores into the final brain reading.</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border-left: 3px solid #10B981;">
                    <div class="flex items-center mb-2">
                        <span class="method-badge mr-2" style="background: rgba(16,185,129,0.15); color: #10B981;">score</span>
                        <span class="text-xs" style="color: var(--text-secondary);">float &minus;1.0 to +1.0</span>
                    </div>
                    <p class="text-xs" style="color: var(--text-secondary);">Raw directional reading from this module. Negative = bearish pressure, positive = bullish pressure, zero = neutral.</p>
                </div>
                <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border-left: 3px solid #0EA5E9;">
                    <div class="flex items-center mb-2">
                        <span class="method-badge mr-2" style="background: rgba(14,165,233,0.15); color: #0EA5E9;">weight</span>
                        <span class="text-xs" style="color: var(--text-secondary);">float 0.0 to 1.0</span>
                    </div>
                    <p class="text-xs" style="color: var(--text-secondary);">How much this module contributes to the brain's overall synthesis. Higher-weight modules have greater influence on the final score.</p>
                </div>
                <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border-left: 3px solid #8B5CF6;">
                    <div class="flex items-center mb-2">
                        <span class="method-badge mr-2" style="background: rgba(139,92,246,0.15); color: #8B5CF6;">etype</span>
                        <span class="text-xs" style="color: var(--text-secondary);">string enum</span>
                    </div>
                    <p class="text-xs" style="color: var(--text-secondary);"><code>BULL</code> — bullish lean. <code>BEAR</code> — bearish lean. <code>NEUTRAL</code> — no clear edge. <code>WARNING</code> — conflicting or trap-like signal.</p>
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
                    <?php echo $baseUrl; ?>/markets-brain-api-v1/markets-brain-api.php
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
                    <tr>
                        <td class="px-6 py-4"><code class="text-sm" style="color: #10B981;">api_key</code></td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">string</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--success);">&#10003; Yes</td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);">Your API authentication key (also accepted as <code>X-Api-Key</code> header).</td>
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
            <!-- Forex Majors -->
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">
                    <span class="method-badge mr-2" style="background: rgba(16,185,129,0.15); color: #10B981;">Forex</span>
                    Major Pairs
                </h3>
                <div class="space-y-4">
                    <?php
                    $forexExamples = [
                        ['EUR/USD — Brain Analysis', 'EURUSD'],
                        ['GBP/USD — Brain Analysis', 'GBPUSD'],
                        ['USD/JPY — Brain Analysis', 'USDJPY'],
                    ];
                    foreach ($forexExamples as $ex):
                        $url = "{$baseUrl}/markets-brain-api-v1/markets-brain-api.php?symbol={$ex[1]}&api_key={$apiKey}";
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

            <!-- Gold & Cross Pairs -->
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">
                    <span class="method-badge mr-2" style="background: rgba(14,165,233,0.15); color: #0EA5E9;">Commodities</span>
                    Gold &amp; Cross Pairs
                </h3>
                <div class="space-y-4">
                    <?php
                    $commodityExamples = [
                        ['XAU/USD — Brain Analysis (Gold)', 'XAUUSD'],
                        ['GBP/JPY — Brain Analysis',        'GBPJPY'],
                        ['AUD/USD — Brain Analysis',        'AUDUSD'],
                    ];
                    foreach ($commodityExamples as $ex):
                        $url = "{$baseUrl}/markets-brain-api-v1/markets-brain-api.php?symbol={$ex[1]}&api_key={$apiKey}";
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

    <!-- Interactive Live Test -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-6" style="color: var(--text-primary);">Live Test</h2>
        <div class="grid grid-cols-1 gap-6">
            <?php
            $testExamples = [
                ['GBP/USD — Neural Brain Scan', 'GBPUSD'],
                ['EUR/USD — Neural Brain Scan',  'EURUSD'],
                ['XAU/USD — Neural Brain Scan',  'XAUUSD'],
                ['USD/JPY — Neural Brain Scan',  'USDJPY'],
            ];
            foreach ($testExamples as $idx => $ex):
                $url = "{$baseUrl}/markets-brain-api-v1/markets-brain-api.php?symbol={$ex[1]}&api_key={$apiKey}";
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
    "request_id": "mb_6834f2abc1234.5678",
    "symbol": "GBPUSD",
    "payload": {
      "symbol":      "GBPUSD",
      "server_time": "2026-05-20 14:32:11",
      "price": {
        "bid":    1.27340,
        "ask":    1.27345,
        "spread": 0.5
      },
      "brain": {
        "score":          0.312,     // -1 (bearish) to +1 (bullish) weighted synthesis
        "confidence":     0.68,      // |score| x (1 - conflict x 0.4)
        "conflict":       0.24,      // 0 = all modules agree, 1 = maximum disagreement
        "regime":         "TREND_UP", // RANGING | TREND_UP | TREND_DOWN | VOLATILE
        "trap_active":    false,
        "trap_score":    -0.05,
        "atr":            0.00890,
        "dominant_thought": "Momentum aligns with structure -- upward pressure building"
      },
      "modules": [
        { "id": 0,  "name": "TICK_SENSE",    "score":  0.45, "weight": 0.80, "etype": "BULL",    "thought": "Tick flow skewing bid-side" },
        { "id": 1,  "name": "MEMORY",        "score":  0.30, "weight": 0.70, "etype": "BULL",    "thought": "Recent bars show higher lows forming" },
        { "id": 2,  "name": "ANTICIPATION",  "score":  0.20, "weight": 0.65, "etype": "BULL",    "thought": "Coiling detected -- range tightening" },
        { "id": 3,  "name": "UNCERTAINTY",   "score": -0.10, "weight": 0.50, "etype": "NEUTRAL", "thought": "ATR near median -- standard volatility" },
        { "id": 4,  "name": "MOMENTUM",      "score":  0.55, "weight": 0.90, "etype": "BULL",    "thought": "RSI ascending, ROC positive" },
        { "id": 5,  "name": "VOLUME",        "score":  0.35, "weight": 0.75, "etype": "BULL",    "thought": "Volume expanding on up moves" },
        { "id": 6,  "name": "SESSION",       "score":  0.40, "weight": 0.60, "etype": "BULL",    "thought": "London/NY overlap -- high participation window" },
        { "id": 7,  "name": "SR_LEVELS",     "score":  0.25, "weight": 0.70, "etype": "BULL",    "thought": "Price above key H4 support cluster" },
        { "id": 8,  "name": "PATTERN",       "score":  0.50, "weight": 0.65, "etype": "BULL",    "thought": "Bullish engulfing on M15" },
        { "id": 9,  "name": "ORDER_FLOW",    "score":  0.30, "weight": 0.80, "etype": "BULL",    "thought": "Buy-side imbalance dominant" },
        { "id": 10, "name": "TRAP_SENSE",    "score": -0.05, "weight": 0.85, "etype": "NEUTRAL", "thought": "No active trap signature detected" },
        { "id": 11, "name": "TREND",         "score":  0.60, "weight": 0.90, "etype": "BULL",    "thought": "Structure of higher highs and higher lows intact" },
        { "id": 12, "name": "MULTI_TF",      "score":  0.45, "weight": 0.85, "etype": "BULL",    "thought": "M15, H1 and H4 aligned bullish" },
        { "id": 13, "name": "LIQUIDITY",     "score":  0.20, "weight": 0.65, "etype": "BULL",    "thought": "Liquidity pool above -- price attracted upward" },
        { "id": 14, "name": "ACCUMULATION",  "score":  0.35, "weight": 0.70, "etype": "BULL",    "thought": "Range compression suggests accumulation phase" },
        { "id": 15, "name": "DISTRIBUTION",  "score": -0.10, "weight": 0.70, "etype": "NEUTRAL", "thought": "No distribution signature at current level" },
        { "id": 16, "name": "BREAKOUT",      "score":  0.40, "weight": 0.75, "etype": "BULL",    "thought": "Resistance cleared with volume" },
        { "id": 17, "name": "DEVILS_EYE",    "score": -0.15, "weight": 0.80, "etype": "WARNING", "thought": "Slight overextension on M5 -- monitor for retracement" },
        { "id": 18, "name": "REVERSALS",     "score": -0.05, "weight": 0.65, "etype": "NEUTRAL", "thought": "No reversal pattern present" },
        { "id": 19, "name": "PATTERN_FAIL",  "score":  0.00, "weight": 0.60, "etype": "NEUTRAL", "thought": "No failed pattern in recent bars" },
        { "id": 20, "name": "SUPPLY_DEMAND", "score":  0.30, "weight": 0.75, "etype": "BULL",    "thought": "Fresh demand zone holding below current price" },
        { "id": 21, "name": "SYNTHESIS",     "score":  0.312,"weight": 1.00, "etype": "BULL",    "thought": "Lean is BULLISH at 68% confidence across 22 modules" }
      ]
    },
    "timestamp": "2026-05-20 14:32:11"
  }
}</pre>
            </div>
        </div>

        <div class="mt-4 p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <h3 class="text-base font-semibold mb-4" style="color: var(--text-primary);">Brain State Fields</h3>
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
                            <td class="px-5 py-3"><code style="color: #10B981;">brain.score</code></td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">float</td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">Weighted synthesis of all 22 module scores. Range &minus;1.0 (max bearish) to +1.0 (max bullish). 0 = no edge.</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="px-5 py-3"><code style="color: #10B981;">brain.confidence</code></td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">float</td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">Confidence in the brain score: <code>|score| &times; (1 &minus; conflict &times; 0.4)</code>. Penalized when modules disagree.</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="px-5 py-3"><code style="color: #10B981;">brain.conflict</code></td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">float</td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">Module disagreement level (0 = all agree, 1 = maximum conflict). High conflict = choppy or uncertain market.</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="px-5 py-3"><code style="color: #10B981;">brain.regime</code></td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">string</td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);"><code>RANGING</code> — price bounded. <code>TREND_UP</code> — directional bullish. <code>TREND_DOWN</code> — directional bearish. <code>VOLATILE</code> — high noise, no clear bias.</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="px-5 py-3"><code style="color: #10B981;">brain.trap_active</code></td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">bool</td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);"><code>true</code> when the TRAP_SENSE module detects an active bull or bear trap signature in current price action.</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="px-5 py-3"><code style="color: #10B981;">brain.trap_score</code></td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">float</td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">TRAP_SENSE raw score. Negative = bear trap (false breakdown), positive = bull trap (false breakout).</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="px-5 py-3"><code style="color: #10B981;">brain.atr</code></td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">float</td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">ATR(14) on the primary timeframe — the volatility baseline used across multiple modules.</td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3"><code style="color: #10B981;">brain.dominant_thought</code></td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">string</td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);">The highest-impact module thought — the single most relevant observation from all 22 modules at this moment.</td>
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
                    <p class="text-xs" style="color: var(--text-secondary);">EA is not running or did not respond within 15 seconds. Attach <code>MarketsBrainAPI.mq5</code> to any MT5 chart.</p>
                </div>
                <div class="p-4 rounded-xl" style="background-color: rgba(244,67,54,0.08); border: 1px solid rgba(244,67,54,0.3);">
                    <div class="font-mono text-xs mb-2" style="color: #F44336;">HTTP 400 — Missing symbol</div>
                    <p class="text-xs" style="color: var(--text-secondary);"><code>symbol</code> parameter is required. Pass any valid MT5 symbol (e.g. <code>GBPUSD</code>, <code>XAUUSD</code>).</p>
                </div>
                <div class="p-4 rounded-xl" style="background-color: rgba(244,67,54,0.08); border: 1px solid rgba(244,67,54,0.3);">
                    <div class="font-mono text-xs mb-2" style="color: #F44336;">HTTP 400 — EA returned error</div>
                    <p class="text-xs" style="color: var(--text-secondary);">Symbol not available in the broker's feed, or EA failed to create indicator handles. Verify the symbol name matches exactly what is in MT5.</p>
                </div>
                <div class="p-4 rounded-xl" style="background-color: rgba(244,67,54,0.08); border: 1px solid rgba(244,67,54,0.3);">
                    <div class="font-mono text-xs mb-2" style="color: #F44336;">HTTP 404 — Not found</div>
                    <p class="text-xs" style="color: var(--text-secondary);">Missing or invalid <code>api_key</code>. Check your key in Settings.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Score Interpretation -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-6" style="color: var(--text-primary);">Score Interpretation</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-base font-semibold mb-4" style="color: var(--text-primary);">brain.score ranges</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 rounded-xl" style="background-color: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3);">
                        <span class="text-sm font-semibold" style="color: #10B981;">+0.60 to +1.00</span>
                        <span class="text-xs" style="color: var(--text-secondary);">Strong bullish conviction — most modules aligned upward</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl" style="background-color: rgba(16,185,129,0.06); border: 1px solid rgba(16,185,129,0.2);">
                        <span class="text-sm font-semibold" style="color: #34D399;">+0.20 to +0.59</span>
                        <span class="text-xs" style="color: var(--text-secondary);">Mild bullish lean — majority favors upward but not unanimously</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl" style="background-color: rgba(156,163,175,0.1); border: 1px solid rgba(156,163,175,0.2);">
                        <span class="text-sm font-semibold" style="color: var(--text-secondary);">&minus;0.19 to +0.19</span>
                        <span class="text-xs" style="color: var(--text-secondary);">Neutral — no clear edge; market likely ranging or undecided</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl" style="background-color: rgba(239,68,68,0.06); border: 1px solid rgba(239,68,68,0.2);">
                        <span class="text-sm font-semibold" style="color: #F87171;">&minus;0.20 to &minus;0.59</span>
                        <span class="text-xs" style="color: var(--text-secondary);">Mild bearish lean — majority favors downward</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl" style="background-color: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3);">
                        <span class="text-sm font-semibold" style="color: #EF4444;">&minus;0.60 to &minus;1.00</span>
                        <span class="text-xs" style="color: var(--text-secondary);">Strong bearish conviction — most modules aligned downward</span>
                    </div>
                </div>
            </div>
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-base font-semibold mb-4" style="color: var(--text-primary);">Regime meanings</h3>
                <div class="space-y-3">
                    <div class="p-3 rounded-xl" style="background-color: var(--bg-secondary); border-left: 3px solid #10B981;">
                        <div class="flex items-center mb-1">
                            <span class="method-badge mr-2" style="background: rgba(16,185,129,0.15); color: #10B981;">TREND_UP</span>
                        </div>
                        <p class="text-xs" style="color: var(--text-secondary);">Directional bullish structure. Multi-timeframe modules confirm upward momentum. Pullbacks are buying opportunities in trend-following frameworks.</p>
                    </div>
                    <div class="p-3 rounded-xl" style="background-color: var(--bg-secondary); border-left: 3px solid #EF4444;">
                        <div class="flex items-center mb-1">
                            <span class="method-badge mr-2" style="background: rgba(239,68,68,0.15); color: #EF4444;">TREND_DOWN</span>
                        </div>
                        <p class="text-xs" style="color: var(--text-secondary);">Directional bearish structure. Multi-timeframe modules confirm downward momentum. Rallies are selling opportunities.</p>
                    </div>
                    <div class="p-3 rounded-xl" style="background-color: var(--bg-secondary); border-left: 3px solid #F59E0B;">
                        <div class="flex items-center mb-1">
                            <span class="method-badge mr-2" style="background: rgba(245,158,11,0.15); color: #F59E0B;">RANGING</span>
                        </div>
                        <p class="text-xs" style="color: var(--text-secondary);">Price is bounded. Module scores are mixed and conflict is elevated. Mean-reversion strategies may apply; breakout setups are premature.</p>
                    </div>
                    <div class="p-3 rounded-xl" style="background-color: var(--bg-secondary); border-left: 3px solid #8B5CF6;">
                        <div class="flex items-center mb-1">
                            <span class="method-badge mr-2" style="background: rgba(139,92,246,0.15); color: #8B5CF6;">VOLATILE</span>
                        </div>
                        <p class="text-xs" style="color: var(--text-secondary);">High ATR with conflicting signals. News spike or erratic price action. High risk — most setups are unreliable until volatility normalizes.</p>
                    </div>
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
                        <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">conflict tells you how reliable the score is</h4>
                        <p class="text-xs" style="color: var(--text-secondary);">A high <code>brain.score</code> with low <code>conflict</code> (&lt; 0.25) means most modules agree — a rare and meaningful signal. A high score with high conflict (&gt; 0.50) means a few loud modules are outvoting a divided room. Use <code>confidence</code> not <code>score</code> alone to filter entries.</p>
                    </div>
                </div>
            </div>
            <div class="p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-start">
                    <i data-feather="info" class="mr-3 flex-shrink-0" style="width: 18px; height: 18px; color: #10B981; margin-top: 2px;"></i>
                    <div>
                        <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Watch DEVILS_EYE when score is extreme</h4>
                        <p class="text-xs" style="color: var(--text-secondary);">DEVILS_EYE (id 17) challenges the dominant bias. When <code>brain.score</code> is above +0.6 or below &minus;0.6, check if DEVILS_EYE is issuing a WARNING <code>etype</code>. A WARNING from this module is the brain questioning its own conviction — don't ignore it.</p>
                    </div>
                </div>
            </div>
            <div class="p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-start">
                    <i data-feather="info" class="mr-3 flex-shrink-0" style="width: 18px; height: 18px; color: #0EA5E9; margin-top: 2px;"></i>
                    <div>
                        <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">EA can scan any symbol from one chart</h4>
                        <p class="text-xs" style="color: var(--text-secondary);">The EA only needs to be attached to one MT5 chart. It creates per-request handles for any symbol you send — GBPUSD, XAUUSD, USDJPY, or any instrument in your broker's feed — without needing a separate chart open for each.</p>
                    </div>
                </div>
            </div>
            <div class="p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-start">
                    <i data-feather="info" class="mr-3 flex-shrink-0" style="width: 16px; height: 16px; color: #6366F1; margin-top: 2px;"></i>
                    <div>
                        <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Combine with Risk Management API</h4>
                        <p class="text-xs" style="color: var(--text-secondary);">Use the Markets Brain API to read the neural state of a symbol, then pass the regime and score into your strategy logic. Once you have a directional thesis, call the Risk Management API to get structurally-placed SL/TP levels before placing the order.</p>
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
            <a href="/risk-management-api-guide" class="inline-flex items-center px-5 py-2.5 rounded-full text-sm font-medium" style="background-color: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border);">
                <i data-feather="shield" class="mr-2" style="width: 16px; height: 16px;"></i>
                Risk Management API
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
    responseDiv.innerHTML = '<div style="padding: 12px; background-color: var(--bg-secondary); border-radius: 8px; color: var(--text-secondary);"><i data-feather="loader" style="width: 14px; height: 14px; display: inline; animation: spin 1s linear infinite;"></i> Loading… (up to 15 s while EA runs 22 modules)</div>';
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
