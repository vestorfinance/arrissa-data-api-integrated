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

$title = 'Latest Events API Guide';
$page  = 'latest-events-api-guide';
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
.gradient-bg {
    background: linear-gradient(135deg, rgba(79,70,229,0.1) 0%, rgba(16,185,129,0.1) 100%);
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
.param-row:not(:last-child) {
    border-bottom: 1px solid var(--border);
}
</style>

<div class="p-8 max-w-[1600px] mx-auto">

    <!-- Hero Header -->
    <div class="mb-10">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-4xl font-bold mb-3 tracking-tight" style="color: var(--text-primary);">
                    Latest Events API
                    <span class="section-badge ml-3" style="background-color: var(--success); color: var(--bg-primary);">v1.0</span>
                </h1>
                <p class="text-lg" style="color: var(--text-secondary);">One row per economic event type — the most recent occurrence up to now or any pretend date</p>
            </div>
        </div>

        <!-- Concept Banner -->
        <div class="p-6 rounded-2xl gradient-bg" style="border: 1px solid var(--border);">
            <div class="flex items-start">
                <div class="flex-shrink-0 mr-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background-color: var(--success);">
                        <i data-feather="clock" style="width:24px;height:24px;color:white;"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">How It Works</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm" style="color: var(--text-secondary);">
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width:16px;height:16px;color:var(--success);"></i>
                            <span><strong style="color:var(--text-primary);">One row per event type:</strong> Groups by <code style="color:var(--accent)">consistent_event_id</code> — you get NFP once, CPI once, etc.</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width:16px;height:16px;color:var(--success);"></i>
                            <span><strong style="color:var(--text-primary);">Latest occurrence only:</strong> Returns the most recent release of each event up to the cutoff time</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width:16px;height:16px;color:var(--success);"></i>
                            <span><strong style="color:var(--text-primary);">Pretend date/time:</strong> Travel back in time — ask "what was the last NFP as of Jan 15 2026?"</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width:16px;height:16px;color:var(--success);"></i>
                            <span><strong style="color:var(--text-primary);">Filter freely:</strong> Narrow by currency, specific event IDs, or impact level</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Endpoint + Key -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10">
        <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="flex items-center mb-3">
                <span class="section-badge mr-3" style="background-color: rgba(16,163,127,0.15); color: var(--success);">GET</span>
                <h3 class="font-semibold" style="color: var(--text-primary);">Endpoint</h3>
            </div>
            <pre class="p-4 rounded-xl overflow-x-auto api-code" style="background-color:var(--bg-primary);border:1px solid var(--border);color:var(--text-secondary);white-space:pre-wrap;word-break:break-all;"><?php echo htmlspecialchars($baseUrl); ?>/api/latest-events?api_key=<?php echo htmlspecialchars($apiKey); ?></pre>
        </div>
        <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="flex items-center mb-3">
                <i data-feather="key" class="mr-3" style="width:18px;height:18px;color:var(--accent);"></i>
                <h3 class="font-semibold" style="color: var(--text-primary);">Authentication</h3>
            </div>
            <p class="text-sm mb-3" style="color: var(--text-secondary);">Pass your API key as a query parameter. Wrong or missing key returns <code style="color:var(--accent)">404</code>.</p>
            <pre class="p-3 rounded-xl overflow-x-auto api-code" style="background-color:var(--bg-primary);border:1px solid var(--border);color:var(--text-secondary);">api_key=<?php echo htmlspecialchars($apiKey); ?></pre>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Parameters -->
    <div class="mb-10">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, var(--accent), var(--success));">
                <i data-feather="sliders" style="width:24px;height:24px;color:white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Parameters</h2>
                <p class="text-sm" style="color: var(--text-secondary);">All parameters are optional except <code style="color:var(--accent)">api_key</code></p>
            </div>
        </div>

        <div class="rounded-2xl overflow-hidden" style="border: 1px solid var(--border);">
            <!-- header row -->
            <div class="grid grid-cols-12 px-6 py-3 text-xs font-semibold uppercase tracking-wider" style="background-color:var(--bg-secondary);color:var(--text-secondary);">
                <div class="col-span-3">Parameter</div>
                <div class="col-span-2">Type</div>
                <div class="col-span-7">Description</div>
            </div>
            <?php
            $params = [
                ['api_key',       'string',  'required', 'Your API key'],
                ['currency',      'string',  'optional', 'Comma-separated currency codes to filter by e.g. <code style="color:var(--accent)">USD,EUR,GBP</code>'],
                ['event_id',      'string',  'optional', 'Comma-separated <code style="color:var(--accent)">consistent_event_id</code> values e.g. <code style="color:var(--accent)">USD_NFP,EUR_CPI</code>'],
                ['impact',        'string',  'optional', 'Comma-separated impact levels: <code style="color:var(--accent)">High</code>, <code style="color:var(--accent)">Medium</code>, <code style="color:var(--accent)">Low</code>'],
                ['must_have',     'string',  'optional', 'Set to <code style="color:var(--accent)">actual</code> to only return events that have a real (non-TBD) actual value'],
                ['pretend_date',  'date',    'optional', 'Treat this date as "today" for the cutoff — format <code style="color:var(--accent)">YYYY-MM-DD</code>'],
                ['pretend_time',  'time',    'optional', 'Used with <code style="color:var(--accent)">pretend_date</code> — format <code style="color:var(--accent)">HH:MM:SS</code> UTC (defaults to 23:59:59)'],
            ];
            foreach ($params as $i => $p) {
                $bg = $i % 2 === 0 ? 'var(--card-bg)' : 'var(--bg-secondary)';
            ?>
            <div class="param-row grid grid-cols-12 px-6 py-4 text-sm" style="background-color:<?php echo $bg; ?>">
                <div class="col-span-3">
                    <code style="color:var(--accent)"><?php echo $p[0]; ?></code>
                    <?php if ($p[2] === 'required'): ?>
                    <span class="ml-2 text-xs px-2 py-0.5 rounded-full" style="background:rgba(239,68,68,.15);color:#ef4444;">required</span>
                    <?php else: ?>
                    <span class="ml-2 text-xs px-2 py-0.5 rounded-full" style="background:rgba(99,102,241,.1);color:var(--text-secondary);">optional</span>
                    <?php endif; ?>
                </div>
                <div class="col-span-2 text-xs pt-1" style="color:var(--text-secondary);"><?php echo $p[1]; ?></div>
                <div class="col-span-7" style="color:var(--text-secondary);"><?php echo $p[3]; ?></div>
            </div>
            <?php } ?>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Examples -->
    <div class="mb-10">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, var(--accent), var(--success));">
                <i data-feather="code" style="width:24px;height:24px;color:white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Example Requests</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Copy and use directly</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- All event types -->
            <div class="p-6 rounded-2xl" style="background-color:var(--card-bg);border:1px solid var(--border);">
                <div class="flex items-center mb-4">
                    <i data-feather="globe" class="mr-3" style="width:18px;height:18px;color:var(--accent);"></i>
                    <h4 class="font-semibold" style="color:var(--text-primary);">All event types — latest occurrence of each</h4>
                </div>
                <pre class="p-4 rounded-xl overflow-x-auto api-code" style="background-color:var(--bg-primary);border:1px solid var(--border);color:var(--text-secondary);white-space:pre-wrap;word-break:break-all;"><?php echo htmlspecialchars($baseUrl); ?>/api/latest-events
  ?api_key=<?php echo htmlspecialchars($apiKey); ?></pre>
            </div>

            <!-- Filter by currency -->
            <div class="p-6 rounded-2xl" style="background-color:var(--card-bg);border:1px solid var(--border);">
                <div class="flex items-center mb-4">
                    <i data-feather="filter" class="mr-3" style="width:18px;height:18px;color:var(--accent);"></i>
                    <h4 class="font-semibold" style="color:var(--text-primary);">USD &amp; EUR events only</h4>
                </div>
                <pre class="p-4 rounded-xl overflow-x-auto api-code" style="background-color:var(--bg-primary);border:1px solid var(--border);color:var(--text-secondary);white-space:pre-wrap;word-break:break-all;"><?php echo htmlspecialchars($baseUrl); ?>/api/latest-events
  ?api_key=<?php echo htmlspecialchars($apiKey); ?>

  &currency=USD,EUR</pre>
            </div>

            <!-- High impact only -->
            <div class="p-6 rounded-2xl" style="background-color:var(--card-bg);border:1px solid var(--border);">
                <div class="flex items-center mb-4">
                    <i data-feather="zap" class="mr-3" style="width:18px;height:18px;color:var(--warning);"></i>
                    <h4 class="font-semibold" style="color:var(--text-primary);">High impact events with actual value</h4>
                </div>
                <pre class="p-4 rounded-xl overflow-x-auto api-code" style="background-color:var(--bg-primary);border:1px solid var(--border);color:var(--text-secondary);white-space:pre-wrap;word-break:break-all;"><?php echo htmlspecialchars($baseUrl); ?>/api/latest-events
  ?api_key=<?php echo htmlspecialchars($apiKey); ?>

  &impact=High
  &must_have=actual</pre>
            </div>

            <!-- Specific event types -->
            <div class="p-6 rounded-2xl" style="background-color:var(--card-bg);border:1px solid var(--border);">
                <div class="flex items-center mb-4">
                    <i data-feather="tag" class="mr-3" style="width:18px;height:18px;color:var(--accent);"></i>
                    <h4 class="font-semibold" style="color:var(--text-primary);">Specific events by event_id</h4>
                </div>
                <pre class="p-4 rounded-xl overflow-x-auto api-code" style="background-color:var(--bg-primary);border:1px solid var(--border);color:var(--text-secondary);white-space:pre-wrap;word-break:break-all;"><?php echo htmlspecialchars($baseUrl); ?>/api/latest-events
  ?api_key=<?php echo htmlspecialchars($apiKey); ?>

  &event_id=USD_NFP,USD_CPI,USD_FOMC_INTEREST_RATE</pre>
            </div>

            <!-- Pretend date — backtesting -->
            <div class="p-6 rounded-2xl lg:col-span-2" style="background-color:var(--card-bg);border:1px solid var(--border);">
                <div class="flex items-center mb-4">
                    <i data-feather="rewind" class="mr-3" style="width:18px;height:18px;color:#818cf8;"></i>
                    <h4 class="font-semibold" style="color:var(--text-primary);">Backtesting — "what was the last USD data as of Jan 15 2026 08:30 UTC?"</h4>
                </div>
                <pre class="p-4 rounded-xl overflow-x-auto api-code" style="background-color:var(--bg-primary);border:1px solid var(--border);color:var(--text-secondary);white-space:pre-wrap;word-break:break-all;"><?php echo htmlspecialchars($baseUrl); ?>/api/latest-events
  ?api_key=<?php echo htmlspecialchars($apiKey); ?>

  &currency=USD
  &impact=High
  &must_have=actual
  &pretend_date=2026-01-15
  &pretend_time=08:30:00</pre>
                <div class="mt-4 highlight-box">
                    <p class="text-xs" style="color:var(--text-secondary);">Use <code style="color:var(--accent)">pretend_date</code> + <code style="color:var(--accent)">pretend_time</code> to replay history. Returns only events whose date/time ≤ the pretend cutoff — giving you the same snapshot the AI would have seen at that exact moment.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Response format -->
    <div class="mb-10">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, var(--accent), var(--success));">
                <i data-feather="package" style="width:24px;height:24px;color:white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Response Format</h2>
                <p class="text-sm" style="color: var(--text-secondary);">JSON — always <code style="color:var(--accent)">Content-Type: application/json</code></p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Success -->
            <div class="p-6 rounded-2xl" style="background-color:var(--card-bg);border:1px solid var(--border);">
                <div class="flex items-center mb-4">
                    <span class="section-badge mr-3" style="background-color:var(--success);color:var(--bg-primary);">200 Success</span>
                </div>
                <pre class="p-4 rounded-xl overflow-x-auto api-code" style="background-color:var(--bg-primary);border:1px solid var(--border);color:var(--text-secondary);">{
  "success": true,
  "cutoff_datetime": "2026-03-04 14:22:00 UTC",
  "total_event_types": 42,
  "filters": {
    "currency": "USD",
    "event_id": null,
    "impact": null,
    "must_have": null
  },
  "events": [
    {
      "consistent_event_id": "USD_NFP",
      "event_name": "Non-Farm Payrolls",
      "currency": "USD",
      "impact_level": "High",
      "event_date": "2026-02-07",
      "event_time": "13:30:00",
      "actual_value": "143K",
      "forecast_value": "175K",
      "previous_value": "307K"
    },
    {
      "consistent_event_id": "USD_CPI",
      "event_name": "CPI m/m",
      "currency": "USD",
      "impact_level": "High",
      "event_date": "2026-02-12",
      "event_time": "13:30:00",
      "actual_value": "0.5%",
      "forecast_value": "0.3%",
      "previous_value": "0.4%"
    }
  ]
}</pre>
            </div>

            <!-- Field reference -->
            <div class="p-6 rounded-2xl" style="background-color:var(--card-bg);border:1px solid var(--border);">
                <h4 class="font-semibold mb-4" style="color:var(--text-primary);">Response Fields</h4>
                <div class="space-y-3 text-sm">
                    <?php
                    $fields = [
                        ['success',              'Always <code style="color:var(--success)">true</code> on success'],
                        ['cutoff_datetime',      'The UTC cutoff used — either now or your pretend_date/pretend_time'],
                        ['total_event_types',    'Number of distinct event types returned'],
                        ['filters',              'Echo of the filters you applied'],
                        ['events[]',             'Array — one object per event type (latest occurrence)'],
                        ['consistent_event_id',  'Unique stable identifier for this event type e.g. <code style="color:var(--accent)">USD_NFP</code>'],
                        ['event_name',           'Human-readable event name'],
                        ['currency',             'Currency this event belongs to'],
                        ['impact_level',         'High / Medium / Low'],
                        ['event_date',           'Date of this occurrence (YYYY-MM-DD, UTC)'],
                        ['event_time',           'Time of this occurrence (HH:MM:SS, UTC)'],
                        ['actual_value',         'The released actual figure'],
                        ['forecast_value',       'Analyst forecast before the release'],
                        ['previous_value',       'Prior period\'s actual value'],
                    ];
                    foreach ($fields as $f): ?>
                    <div class="flex items-start py-2" style="border-bottom: 1px solid var(--border);">
                        <code style="color:var(--accent); min-width: 180px; flex-shrink:0; font-size:0.75rem;"><?php echo $f[0]; ?></code>
                        <span style="color:var(--text-secondary);"><?php echo $f[1]; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Use cases -->
    <div class="mb-10">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, var(--accent), var(--success));">
                <i data-feather="lightbulb" style="width:24px;height:24px;color:white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Common Use Cases</h2>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="p-5 rounded-2xl" style="background-color:var(--card-bg);border:1px solid var(--border);">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background:rgba(16,163,127,.15);">
                    <i data-feather="cpu" style="width:18px;height:18px;color:var(--success);"></i>
                </div>
                <h4 class="font-semibold mb-2" style="color:var(--text-primary);">AI Context Snapshot</h4>
                <p class="text-sm" style="color:var(--text-secondary);">Feed the AI a current economic picture — "here is the latest NFP, CPI, interest rate..." without querying individual events.</p>
            </div>
            <div class="p-5 rounded-2xl" style="background-color:var(--card-bg);border:1px solid var(--border);">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background:rgba(99,102,241,.15);">
                    <i data-feather="rewind" style="width:18px;height:18px;color:#818cf8;"></i>
                </div>
                <h4 class="font-semibold mb-2" style="color:var(--text-primary);">Backtesting</h4>
                <p class="text-sm" style="color:var(--text-secondary);">Use <code style="color:var(--accent)">pretend_date</code> to simulate what economic data was available at any point in the past for strategy replay.</p>
            </div>
            <div class="p-5 rounded-2xl" style="background-color:var(--card-bg);border:1px solid var(--border);">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background:rgba(245,158,11,.15);">
                    <i data-feather="bar-chart-2" style="width:18px;height:18px;color:var(--warning);"></i>
                </div>
                <h4 class="font-semibold mb-2" style="color:var(--text-primary);">Dashboard Summary</h4>
                <p class="text-sm" style="color:var(--text-secondary);">Populate a live macro dashboard showing the last known value for every tracked indicator across all currencies.</p>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Live Tester -->
    <div class="mb-10">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, var(--accent), var(--success));">
                <i data-feather="play-circle" style="width:24px;height:24px;color:white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Live Tester</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Try the API directly from this page</p>
            </div>
        </div>

        <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="text-xs font-semibold mb-2 block" style="color:var(--text-primary);">Currency <span style="color:var(--text-secondary);font-weight:400;">(optional)</span></label>
                    <input type="text" id="leFilterCurrency" placeholder="USD,EUR,GBP"
                        class="w-full px-3 py-2 rounded-xl text-sm outline-none"
                        style="background-color:var(--input-bg);border:1px solid var(--input-border);color:var(--text-primary);">
                </div>
                <div>
                    <label class="text-xs font-semibold mb-2 block" style="color:var(--text-primary);">Event ID <span style="color:var(--text-secondary);font-weight:400;">(optional)</span></label>
                    <input type="text" id="leFilterEventId" placeholder="USD_NFP,EUR_CPI"
                        class="w-full px-3 py-2 rounded-xl text-sm outline-none"
                        style="background-color:var(--input-bg);border:1px solid var(--input-border);color:var(--text-primary);">
                </div>
                <div>
                    <label class="text-xs font-semibold mb-2 block" style="color:var(--text-primary);">Impact <span style="color:var(--text-secondary);font-weight:400;">(optional)</span></label>
                    <select id="leFilterImpact"
                        class="w-full px-3 py-2 rounded-xl text-sm outline-none"
                        style="background-color:var(--input-bg);border:1px solid var(--input-border);color:var(--text-primary);">
                        <option value="">All</option>
                        <option value="High">High</option>
                        <option value="Medium">Medium</option>
                        <option value="Low">Low</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold mb-2 block" style="color:var(--text-primary);">Pretend Date <span style="color:var(--text-secondary);font-weight:400;">(optional)</span></label>
                    <input type="date" id="leFilterPretendDate"
                        class="w-full px-3 py-2 rounded-xl text-sm outline-none"
                        style="background-color:var(--input-bg);border:1px solid var(--input-border);color:var(--text-primary);">
                </div>
                <div>
                    <label class="text-xs font-semibold mb-2 block" style="color:var(--text-primary);">Pretend Time UTC <span style="color:var(--text-secondary);font-weight:400;">(optional)</span></label>
                    <input type="time" id="leFilterPretendTime"
                        class="w-full px-3 py-2 rounded-xl text-sm outline-none"
                        style="background-color:var(--input-bg);border:1px solid var(--input-border);color:var(--text-primary);">
                </div>
                <div class="flex items-end pb-2">
                    <label class="flex items-center gap-2 text-sm cursor-pointer" style="color:var(--text-secondary);">
                        <input type="checkbox" id="leFilterMustHave" class="w-4 h-4 rounded" style="accent-color:var(--accent);">
                        Require actual value
                    </label>
                </div>
            </div>

            <button id="leTestBtn" type="button" onclick="runLeTest()"
                class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all"
                style="background: linear-gradient(135deg, var(--accent), #6366f1); color: white; border: none; cursor: pointer;">
                <i data-feather="send" style="width:16px;height:16px;display:inline;"></i> Send Request
            </button>

            <div id="leTestResult" class="hidden mt-4">
                <pre id="leTestOutput"
                    class="p-4 rounded-xl overflow-x-auto api-code"
                    style="background-color:var(--bg-primary);border:1px solid var(--border);color:var(--text-secondary);max-height:400px;overflow-y:auto;white-space:pre-wrap;word-break:break-all;"></pre>
            </div>
        </div>
    </div>

</div>

<script>
async function runLeTest() {
    const btn    = document.getElementById('leTestBtn');
    const result = document.getElementById('leTestResult');
    const output = document.getElementById('leTestOutput');

    btn.disabled  = true;
    btn.innerHTML = '<i data-feather="loader" style="width:16px;height:16px;display:inline;"></i> Loading…';
    feather.replace();

    const params = new URLSearchParams({ api_key: '<?= htmlspecialchars($apiKey) ?>' });

    const currency    = document.getElementById('leFilterCurrency').value.trim();
    const eventId     = document.getElementById('leFilterEventId').value.trim();
    const impact      = document.getElementById('leFilterImpact').value;
    const pretendDate = document.getElementById('leFilterPretendDate').value;
    const pretendTime = document.getElementById('leFilterPretendTime').value;
    const mustHave    = document.getElementById('leFilterMustHave').checked;

    if (currency)    params.set('currency',     currency);
    if (eventId)     params.set('event_id',     eventId);
    if (impact)      params.set('impact',       impact);
    if (pretendDate) params.set('pretend_date', pretendDate);
    if (pretendTime) params.set('pretend_time', pretendTime + ':00');
    if (mustHave)    params.set('must_have',    'actual');

    try {
        const res  = await fetch('<?= htmlspecialchars($baseUrl) ?>/api/latest-events?' + params.toString());
        const data = await res.json();
        output.textContent = JSON.stringify(data, null, 2);
    } catch (e) {
        output.textContent = 'Error: ' + e.message;
    }

    result.classList.remove('hidden');
    btn.disabled  = false;
    btn.innerHTML = '<i data-feather="send" style="width:16px;height:16px;display:inline;"></i> Send Request';
    feather.replace();
}
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layouts/app.php';
?>
