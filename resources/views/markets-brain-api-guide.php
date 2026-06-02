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
.section-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 14px;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.025em;
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
.module-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 3fr;
    gap: 0.75rem;
    padding: 0.6rem 1rem;
    border-bottom: 1px solid var(--border);
    align-items: start;
    font-size: 0.8rem;
}
.module-row:last-child { border-bottom: none; }
.module-row.header {
    font-weight: 700;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-secondary);
    background: var(--bg-secondary);
    border-radius: 8px 8px 0 0;
}
</style>

<div class="p-8 max-w-5xl mx-auto">

    <!-- Header -->
    <div class="mb-10">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                <i data-feather="cpu" style="width: 24px; height: 24px; color: var(--accent);"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold" style="color: var(--text-primary);">Markets Brain API</h1>
                <p class="text-sm" style="color: var(--text-secondary);">22-module neural brain — raw market state scores and thoughts for any symbol</p>
            </div>
        </div>
        <div class="p-4 rounded-xl" style="background: rgba(79,70,229,0.08); border: 1px solid rgba(79,70,229,0.3);">
            <p class="text-sm font-medium" style="color: var(--text-primary);">Output: raw neural state only — <strong>no BUY / SELL signals</strong>. The brain reports what it observes; your strategy decides what to do with that information.</p>
        </div>
    </div>

    <!-- Endpoint -->
    <div class="mb-8">
        <h2 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">Endpoint</h2>
        <div class="rounded-xl overflow-hidden" style="background-color: var(--input-bg); border: 1px solid var(--input-border);">
            <div class="px-4 py-3 flex items-center gap-3" style="border-bottom: 1px solid var(--border);">
                <span class="section-badge" style="background: rgba(16,185,129,0.15); color: #10b981;">GET</span>
                <code class="api-code" style="color: var(--text-primary);"><?php echo htmlspecialchars($baseUrl); ?>/markets-brain-api-v1/markets-brain-api.php?symbol=GBPUSD&amp;api_key=<?php echo htmlspecialchars($apiKey); ?></code>
            </div>
            <div class="px-4 py-3">
                <table class="w-full text-sm">
                    <thead>
                        <tr style="color: var(--text-secondary);">
                            <th class="text-left pb-2 font-semibold" style="width:140px;">Parameter</th>
                            <th class="text-left pb-2 font-semibold" style="width:80px;">Required</th>
                            <th class="text-left pb-2 font-semibold">Description</th>
                        </tr>
                    </thead>
                    <tbody style="color: var(--text-primary);">
                        <tr>
                            <td class="py-1 pr-4"><code class="api-code">symbol</code></td>
                            <td class="py-1 pr-4" style="color: #10b981;">Yes</td>
                            <td class="py-1">MT5 symbol name (e.g. <code class="api-code">GBPUSD</code>, <code class="api-code">XAUUSD</code>)</td>
                        </tr>
                        <tr>
                            <td class="py-1 pr-4"><code class="api-code">api_key</code></td>
                            <td class="py-1 pr-4" style="color: #10b981;">Yes</td>
                            <td class="py-1">Your API key (or send as <code class="api-code">X-Api-Key</code> header)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- How it works -->
    <div class="mb-8">
        <h2 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">How it works</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <?php foreach ([
                ['1', 'Client sends GET with ?symbol=', 'trending-up'],
                ['2', 'Server writes .req.json to queue dir', 'file'],
                ['3', 'EA polls, reads request, runs all 22 modules on that symbol', 'cpu'],
                ['4', 'EA POSTs raw JSON result; client receives it', 'check-circle'],
            ] as $step): ?>
            <div class="p-4 rounded-xl flex flex-col gap-2" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="w-7 h-7 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0" style="background-color: var(--accent); color: #fff;"><?php echo $step[0]; ?></div>
                <div class="flex items-center gap-2">
                    <i data-feather="<?php echo $step[2]; ?>" style="width:16px;height:16px;color:var(--text-secondary);flex-shrink:0;"></i>
                    <p class="text-sm" style="color: var(--text-secondary);"><?php echo $step[1]; ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <p class="text-sm mt-4" style="color: var(--text-secondary);">Timeout: 15 seconds. If no EA is running, the API returns an error with instructions to attach the EA in MT5.</p>
    </div>

    <div class="divider"></div>

    <!-- EA Setup -->
    <div class="mb-8">
        <h2 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">EA Setup</h2>
        <ol class="list-decimal list-inside space-y-2 text-sm mb-4" style="color: var(--text-secondary);">
            <li>Compile <strong style="color: var(--text-primary);">MarketsBrainAPI.mq5</strong> in MetaEditor and attach it to <em>any chart</em> in MT5.</li>
            <li>In <strong>Tools &rarr; Options &rarr; Expert Advisors</strong>, add <code class="api-code" style="color: var(--text-primary);"><?php echo htmlspecialchars($baseUrl); ?></code> to the allowed URLs list.</li>
            <li>Set <strong>AppBaseURL</strong> input to match your server URL (default: <code class="api-code" style="color: var(--text-primary);">http://127.0.0.1</code>).</li>
            <li>The EA polls every <strong>InpApiPollingSeconds</strong> (default: 1 s). When a request arrives it runs all 22 brain modules on the requested symbol and returns raw scores.</li>
        </ol>
        <div class="rounded-xl overflow-hidden" style="background-color: var(--input-bg); border: 1px solid var(--input-border);">
            <div class="px-4 py-2 text-xs font-semibold" style="color: var(--text-secondary); border-bottom: 1px solid var(--border);">EA Inputs</div>
            <table class="w-full text-sm px-4">
                <tbody style="color: var(--text-primary);">
                    <?php foreach ([
                        ['AppBaseURL',           'http://127.0.0.1', 'Base URL of your Arrissa Data server'],
                        ['InpEnableApi',         'true',             'Toggle API polling on/off'],
                        ['InpApiPollingSeconds', '1',                'How often the EA checks for pending requests'],
                        ['InpDebugMode',         'false',            'Enable Print() debug output in Experts log'],
                        ['InpPrimaryTF',         'H1',               'Primary timeframe for all modules'],
                        ['InpHTF',               'H4',               'Higher timeframe (multi-TF module)'],
                        ['InpMTF',               'M15',              'Mid timeframe (multi-TF module)'],
                    ] as $row): ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td class="px-4 py-2 font-mono text-xs" style="width:200px; color: var(--accent);"><?php echo $row[0]; ?></td>
                        <td class="py-2 pr-4 font-mono text-xs" style="color: var(--text-secondary); width:100px;"><?php echo $row[1]; ?></td>
                        <td class="py-2 text-xs" style="color: var(--text-secondary);"><?php echo $row[2]; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Response -->
    <div class="mb-8">
        <h2 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">Response Structure</h2>
        <pre class="rounded-xl p-5 overflow-x-auto api-code text-xs" style="background-color: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-primary);">{
  "arrissa_data": {
    "request_id": "mb_68...",
    "symbol": "GBPUSD",
    "payload": {
      "symbol":      "GBPUSD",
      "server_time": "2026-06-02 14:23:05",
      "price": {
        "bid":    1.27341,
        "ask":    1.27344,
        "spread": 0.00003
      },
      "brain": {
        "score":            0.3241,      // weighted average of all 22 modules (-1 to +1)
        "confidence":       0.2518,      // |score| × (1 - conflict×0.4)
        "conflict":         0.3870,      // std-dev of module scores (higher = more disagreement)
        "regime":           "TREND_UP",  // RANGING | TREND_UP | TREND_DOWN | VOLATILE
        "trap_active":      false,       // true if trap sensor fired
        "trap_score":       0.1200,      // 0–1 trap probability
        "atr":              0.00042,
        "dominant_thought": "The trend is up. I should not be looking for reasons to sell."
      },
      "modules": [
        {
          "id":      0,
          "name":    "TICK_SENSE",
          "score":   0.7000,      // -1.0 (bearish) to +1.0 (bullish)
          "weight":  0.70,
          "etype":   "BULL",      // BULL | BEAR | NEUTRAL | WARNING
          "thought": "One large print at the offer. Initiative buying."
        },
        // ... 21 more modules
      ]
    },
    "timestamp": "2026-06-02 14:23:05"
  }
}</pre>
    </div>

    <div class="divider"></div>

    <!-- 22 Modules -->
    <div class="mb-8">
        <h2 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">The 22 Neural Modules</h2>
        <div class="rounded-xl overflow-hidden" style="border: 1px solid var(--border);">
            <div class="module-row header">
                <span>Module</span>
                <span>Weight</span>
                <span>Score range</span>
                <span>What it reads</span>
            </div>
            <?php foreach ([
                ['0',  'TICK_SENSE',    '0.70', '−1 to +1',  'Bullish/bearish character of last 5 bars; volume weight'],
                ['1',  'MEMORY',        '0.80', '−0.4 to +0.4', 'Price proximity to prior volume spikes and swing extremes'],
                ['2',  'ANTICIPATION',  '0.70', '0 to +0.4', 'Range compression ratio; coiling energy before a move'],
                ['3',  'UNCERTAINTY',   '0.60', '0 (neutral)', 'Internal conflict — triggers when modules disagree'],
                ['4',  'MOMENTUM',      '1.00', '−1 to +1',  'RSI, MACD, divergence, momentum direction'],
                ['5',  'VOLUME',        '0.90', '−0.9 to +0.9', 'Volume ratio vs 20-bar average; climax detection'],
                ['6',  'SESSION',       '0.50', '−0.2 to +0.2', 'Trading session quality by server hour and day'],
                ['7',  'SR_LEVELS',     '1.00', '−0.7 to +0.7', 'Distance to 40-bar swing high/low and round numbers'],
                ['8',  'PATTERN',       '0.80', '−0.8 to +0.8', 'Pin bars, engulfing, inside bars, HH/HL, LL/LH structure'],
                ['9',  'ORDER_FLOW',    '0.90', '−1 to +1',  'Close location value; cumulative delta divergence'],
                ['10', 'TRAP_SENSE',    '0.90', 'counter-bias', 'Stop runs, failed breakouts, obvious-setup traps'],
                ['11', 'TREND',         '1.00', '−1 to +1',  'EMA 8/21/50 stack; slope; ATR trend fatigue'],
                ['12', 'MULTI_TF',      '1.00', '−1 to +1',  'HTF and MTF EMA alignment with current direction'],
                ['13', 'LIQUIDITY',     '0.70', '−0.3 to +0.3', 'Equal highs/lows clusters; spread anomalies'],
                ['14', 'ACCUMULATION',  '0.80', '0 to +0.7', 'Rising lows, flat highs, high-vol-up / low-vol-down'],
                ['15', 'DISTRIBUTION',  '0.80', '−0.7 to 0', 'Falling highs, flat lows, high-vol-down / low-vol-up'],
                ['16', 'BREAKOUT',      '0.90', '−0.8 to +0.8', 'Close vs 18-bar range; volume confirmation; retests'],
                ['17', 'DEVILS_EYE',    '0.70', 'counter-bias', 'Challenges the dominant read — devil\'s advocate'],
                ['18', 'REVERSALS',     '0.90', '−0.5 to +0.5', 'Climax bars, shrinking swings, character change'],
                ['19', 'PATTERN_FAIL',  '0.80', '−0.6 to +0.6', 'Fires when expected pattern collapses (trapped crowd)'],
                ['20', 'SUPPLY_DEMAND', '0.90', '−0.9 to +0.9', 'Cumulative demand/supply volume ratio; shock detection'],
                ['21', 'SYNTHESIS',     '0.00', '0 (meta)',    'Summarises the brain state — no directional weight'],
            ] as $m): ?>
            <div class="module-row" style="color: var(--text-primary);">
                <span><strong><?php echo $m[1]; ?></strong> <span style="color: var(--text-secondary); font-size: 0.7rem;">(id <?php echo $m[0]; ?>)</span></span>
                <span style="color: var(--text-secondary);"><?php echo $m[2]; ?></span>
                <span style="color: var(--text-secondary); font-size: 0.72rem;"><?php echo $m[3]; ?></span>
                <span style="color: var(--text-secondary);"><?php echo $m[4]; ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Score interpretation -->
    <div class="mb-8">
        <h2 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">Reading the Scores</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-5 rounded-xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="font-semibold mb-3 text-sm" style="color: var(--text-primary);">brain.score</h3>
                <ul class="text-sm space-y-1" style="color: var(--text-secondary);">
                    <li><code class="api-code">+0.7 to +1.0</code> — Strong bullish lean</li>
                    <li><code class="api-code">+0.3 to +0.7</code> — Moderate bullish lean</li>
                    <li><code class="api-code">−0.3 to +0.3</code> — Neutral / unresolved</li>
                    <li><code class="api-code">−0.7 to −0.3</code> — Moderate bearish lean</li>
                    <li><code class="api-code">−1.0 to −0.7</code> — Strong bearish lean</li>
                </ul>
            </div>
            <div class="p-5 rounded-xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="font-semibold mb-3 text-sm" style="color: var(--text-primary);">brain.conflict</h3>
                <ul class="text-sm space-y-1" style="color: var(--text-secondary);">
                    <li><code class="api-code">&lt; 0.3</code> — Modules mostly agree</li>
                    <li><code class="api-code">0.3 – 0.5</code> — Moderate disagreement</li>
                    <li><code class="api-code">&gt; 0.5</code> — High internal conflict — treat with caution</li>
                </ul>
                <p class="text-xs mt-3" style="color: var(--text-secondary);">High conflict reduces <code class="api-code">confidence</code> via <code class="api-code">conf × (1 − conflict × 0.4)</code>.</p>
            </div>
            <div class="p-5 rounded-xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="font-semibold mb-3 text-sm" style="color: var(--text-primary);">brain.regime</h3>
                <ul class="text-sm space-y-1" style="color: var(--text-secondary);">
                    <li><code class="api-code">TREND_UP</code> — EMA 8 &gt; 21 &gt; 50</li>
                    <li><code class="api-code">TREND_DOWN</code> — EMA 8 &lt; 21 &lt; 50</li>
                    <li><code class="api-code">RANGING</code> — EMAs mixed or flat</li>
                    <li><code class="api-code">VOLATILE</code> — (reserved for future expansion)</li>
                </ul>
            </div>
            <div class="p-5 rounded-xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="font-semibold mb-3 text-sm" style="color: var(--text-primary);">module.etype</h3>
                <ul class="text-sm space-y-1" style="color: var(--text-secondary);">
                    <li><code class="api-code">BULL</code> — Bullish reading</li>
                    <li><code class="api-code">BEAR</code> — Bearish reading</li>
                    <li><code class="api-code">NEUTRAL</code> — No clear edge</li>
                    <li><code class="api-code">WARNING</code> — Caution flag (trap, climax, divergence…)</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Error responses -->
    <div class="mb-8">
        <h2 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">Error Responses</h2>
        <div class="space-y-3">
            <div class="p-4 rounded-xl" style="background-color: var(--input-bg); border: 1px solid var(--input-border);">
                <div class="flex items-center gap-2 mb-2">
                    <span class="section-badge" style="background: rgba(239,68,68,0.15); color: #ef4444;">503</span>
                    <span class="text-sm font-medium" style="color: var(--text-primary);">MT5 Data Server not connected</span>
                </div>
                <p class="text-xs" style="color: var(--text-secondary);">No EA is attached in MT5, or the EA has not allowed the URL. Attach <strong>MarketsBrainAPI.mq5</strong> to any chart and add the URL in Tools &rarr; Options &rarr; Expert Advisors.</p>
            </div>
            <div class="p-4 rounded-xl" style="background-color: var(--input-bg); border: 1px solid var(--input-border);">
                <div class="flex items-center gap-2 mb-2">
                    <span class="section-badge" style="background: rgba(239,68,68,0.15); color: #ef4444;">404</span>
                    <span class="text-sm font-medium" style="color: var(--text-primary);">Not found / invalid API key</span>
                </div>
                <p class="text-xs" style="color: var(--text-secondary);">Missing or incorrect <code class="api-code">api_key</code> parameter.</p>
            </div>
        </div>
    </div>

</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layouts/app.php';
?>
