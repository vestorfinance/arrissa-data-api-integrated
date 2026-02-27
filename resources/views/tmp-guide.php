<?php
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Database.php';

$db  = Database::getInstance();
$pdo = $db->getConnection();

// Load settings
$stmt = $pdo->prepare("SELECT key, value FROM settings WHERE key IN ('app_base_url','api_key')");
$stmt->execute();
$settings = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $settings[$row['key']] = $row['value'];
}
$baseUrl = rtrim($settings['app_base_url'] ?? 'http://localhost', '/');
$apiKey  = $settings['api_key'] ?? '';

// Load categories with tool counts
$categories = $pdo->query("
    SELECT
        tc.id,
        tc.name,
        tc.description,
        tc.endpoint_base,
        tc.requires_ea,
        tc.ea_name,
        COUNT(t.id)         AS tool_count,
        SUM(t.enabled)      AS enabled_count
    FROM tool_categories tc
    LEFT JOIN tools t ON tc.id = t.category_id
    GROUP BY tc.id
    ORDER BY tc.id
")->fetchAll(PDO::FETCH_ASSOC);

$totalTools   = array_sum(array_column($categories, 'tool_count'));
$totalEnabled = array_sum(array_column($categories, 'enabled_count'));

// Category meta (icon, accent colour, guide link)
$catMeta = [
    'market-data'       => ['icon' => 'trending-up',   'color' => '#4f46e5', 'guide' => '/market-data-api-guide',      'label' => 'Market Data'],
    'chart-images'      => ['icon' => 'image',          'color' => '#7c3aed', 'guide' => '/chart-image-api-guide',      'label' => 'Chart Images'],
    'economic-calendar' => ['icon' => 'calendar',       'color' => '#0891b2', 'guide' => '/news-api-guide',             'label' => 'Economic Calendar'],
    'orders'            => ['icon' => 'shopping-cart',  'color' => '#059669', 'guide' => '/orders-api-guide',           'label' => 'Orders'],
    'market-analysis'   => ['icon' => 'bar-chart-2',    'color' => '#d97706', 'guide' => '/symbol-info-api-guide',      'label' => 'Market Analysis'],
    'web-content'       => ['icon' => 'globe',          'color' => '#db2777', 'guide' => '/url-api-guide',              'label' => 'Web Content'],
];

$title = 'TMP — Tool Matching Protocol';
$page  = 'tmp-guide';
ob_start();
?>

<style>
.section-badge {
    display: inline-flex;
    align-items: center;
    padding: 5px 12px;
    border-radius: 9999px;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.gradient-bg {
    background: linear-gradient(135deg, rgba(79, 70, 229, 0.08) 0%, rgba(16, 185, 129, 0.08) 100%);
}
.divider {
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--border), transparent);
    margin: 2.5rem 0;
}
.api-code {
    font-family: 'Fira Code', 'Consolas', monospace;
    font-size: 0.8rem;
    line-height: 1.7;
    word-break: break-all;
}
.cat-card {
    border-radius: 20px !important;
    transition: transform 0.18s, box-shadow 0.18s;
}
.cat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 32px rgba(0,0,0,0.35);
}
.stat-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 9999px;
    font-size: 0.78rem;
    font-weight: 600;
}
.copy-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 6px;
    transition: background 0.15s;
}
.copy-btn:hover { background: rgba(255,255,255,0.07); }
.copy-btn.copied { color: var(--success) !important; }
.param-row { padding: 10px 0; }
.param-row:not(:last-child) { border-bottom: 1px solid var(--border); }
</style>

<div class="p-8 max-w-[1600px] mx-auto">

    <!-- ── Hero ────────────────────────────────────────────────────── -->
    <div class="mb-10">
        <div class="flex flex-col md:flex-row md:items-start md:justify-between mb-6 gap-4">
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <h1 class="text-4xl font-bold tracking-tight" style="color: var(--text-primary);">
                        Tool Matching Protocol
                    </h1>
                    <span class="section-badge" style="background-color: var(--accent); color: #fff;">TMP</span>
                </div>
                <p class="text-lg" style="color: var(--text-secondary);">
                    A structured catalog of all available API tools — with category groupings, input schemas, and search phrases for AI-assisted tool discovery.
                </p>
            </div>
            <!-- Stats row -->
            <div class="flex gap-3 flex-wrap shrink-0">
                <div class="stat-pill" style="background-color: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border);">
                    <i data-feather="layers" style="width:14px;height:14px;color:var(--accent);"></i>
                    <?= count($categories) ?> Categories
                </div>
                <div class="stat-pill" style="background-color: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border);">
                    <i data-feather="tool" style="width:14px;height:14px;color:var(--success);"></i>
                    <?= $totalTools ?> Total Tools
                </div>
                <div class="stat-pill" style="background-color: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border);">
                    <i data-feather="check-circle" style="width:14px;height:14px;color:var(--success);"></i>
                    <?= $totalEnabled ?> Enabled
                </div>
            </div>
        </div>

        <!-- Info Banner -->
        <div class="p-5 rounded-2xl gradient-bg" style="border: 1px solid var(--border);">
            <div class="flex items-start gap-4">
                <div class="w-11 h-11 rounded-full flex items-center justify-center flex-shrink-0" style="background-color: var(--accent);">
                    <i data-feather="cpu" style="width:20px;height:20px;color:#fff;"></i>
                </div>
                <div>
                    <h3 class="font-semibold mb-1" style="color: var(--text-primary);">What is TMP?</h3>
                    <p class="text-sm leading-relaxed" style="color: var(--text-secondary);">
                        TMP provides a machine-readable and human-readable index of every API capability in this system.
                        Each tool has a <strong style="color:var(--text-primary);">search_phrase</strong> for semantic lookup,
                        a <strong style="color:var(--text-primary);">tool_format</strong> URL template with all required parameters,
                        and <strong style="color:var(--text-primary);">inputs_explanation</strong> describing every accepted value.
                        AI agents can query the Categories API to discover available tools and construct correct requests automatically.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Categories API ────────────────────────────────────────── -->
    <div class="mb-10">
        <h2 class="text-2xl font-bold mb-1" style="color: var(--text-primary);">Categories API</h2>
        <p class="text-sm mb-5" style="color: var(--text-secondary);">Returns all tool categories with tool counts. Requires API key.</p>

        <!-- Endpoint -->
        <div class="rounded-2xl p-5 mb-4" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-widest" style="color: var(--text-secondary);">Endpoint</span>
                <span class="section-badge" style="background-color: rgba(16,185,129,0.15); color: var(--success);">GET</span>
            </div>
            <div class="flex items-center gap-3 rounded-xl px-4 py-3" style="background-color: var(--bg-primary); border: 1px solid var(--border);">
                <code class="api-code flex-1" style="color: var(--text-primary);"><?= htmlspecialchars($baseUrl) ?>/api/tmp-categories?api_key=<span style="color:var(--accent);"><?= htmlspecialchars($apiKey) ?></span></code>
                <button class="copy-btn" onclick="copyText('<?= htmlspecialchars($baseUrl) ?>/api/tmp-categories?api_key=<?= htmlspecialchars($apiKey) ?>', this)" title="Copy URL">
                    <i data-feather="copy" style="width:15px;height:15px;color:var(--text-secondary);"></i>
                </button>
            </div>
        </div>

        <!-- Parameters table -->
        <div class="rounded-2xl overflow-hidden" style="border: 1px solid var(--border);">
            <div class="px-5 py-3" style="background-color: var(--bg-secondary); border-bottom: 1px solid var(--border);">
                <span class="text-sm font-semibold" style="color: var(--text-primary);">Parameters</span>
            </div>
            <div class="px-5" style="background-color: var(--bg-primary);">
                <div class="param-row flex items-start gap-4">
                    <code class="text-sm font-mono w-28 flex-shrink-0 mt-1" style="color: var(--accent);">api_key</code>
                    <div>
                        <span class="section-badge mr-2" style="background-color: rgba(239,68,68,0.12); color: var(--danger); font-size:0.68rem;">required</span>
                        <span class="text-sm" style="color: var(--text-secondary);">Your API key from Settings</span>
                    </div>
                </div>
                <div class="param-row flex items-start gap-4">
                    <code class="text-sm font-mono w-28 flex-shrink-0 mt-1" style="color: var(--accent);">exclude</code>
                    <div>
                        <span class="section-badge mr-2" style="background-color: rgba(100,116,139,0.12); color: var(--text-secondary); font-size:0.68rem;">optional</span>
                        <span class="text-sm" style="color: var(--text-secondary);">Comma-separated list of category <strong style="color:var(--text-primary);">names</strong> to omit from the response. Useful when an AI agent only needs a subset of categories. <br><span class="text-xs" style="color:var(--text-secondary);">e.g. <code style="color:var(--accent);">exclude=chart-images,orders</code></span></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Live response preview button -->
        <div class="mt-4 flex gap-3 flex-wrap">
            <a href="<?= htmlspecialchars($baseUrl) ?>/api/tmp-categories?api_key=<?= htmlspecialchars($apiKey) ?>" target="_blank"
               class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold"
               style="background-color: var(--accent); color: #fff; border-radius: 9999px; text-decoration: none;">
                <i data-feather="external-link" style="width:15px;height:15px;"></i>
                Test Live Response
            </a>
            <a href="<?= htmlspecialchars($baseUrl) ?>/api/tmp-categories?api_key=<?= htmlspecialchars($apiKey) ?>&exclude=chart-images,orders" target="_blank"
               class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold"
               style="background-color: var(--bg-secondary); color: var(--text-primary); border-radius: 9999px; text-decoration: none; border: 1px solid var(--border);">
                <i data-feather="filter" style="width:15px;height:15px;"></i>
                Test with exclude=chart-images,orders
            </a>
        </div>

        <!-- Sample Response -->
        <div class="mt-5 rounded-2xl overflow-hidden" style="border: 1px solid var(--border);">
            <div class="flex items-center justify-between px-5 py-3" style="background-color: var(--bg-secondary); border-bottom: 1px solid var(--border);">
                <span class="text-sm font-semibold" style="color: var(--text-primary);">Sample Response</span>
                <button class="copy-btn" id="sample-copy-btn" onclick="copySample()" title="Copy">
                    <i data-feather="copy" style="width:14px;height:14px;color:var(--text-secondary);"></i>
                </button>
            </div>
            <pre id="sample-response" class="api-code p-5 overflow-x-auto" style="background-color: var(--bg-primary); color: var(--text-secondary); margin:0;">{
  "status": "success",
  "count": <?= count($categories) ?>,
  "categories": [
<?php foreach ($categories as $i => $cat): $m = $catMeta[$cat['name']] ?? []; ?>
    {
      "id": <?= $cat['id'] ?>,
      "name": "<?= htmlspecialchars($cat['name']) ?>",
      "description": "<?= htmlspecialchars($cat['description']) ?>",
      "endpoint_base": "<?= htmlspecialchars($cat['endpoint_base'] ?? '') ?>",
      "requires_ea": <?= $cat['requires_ea'] ? 'true' : 'false' ?>,
      "ea_name": <?= $cat['ea_name'] ? '"'.htmlspecialchars($cat['ea_name']).'"' : 'null' ?>,
      "tool_count": <?= (int)$cat['tool_count'] ?>,
      "enabled_count": <?= (int)$cat['enabled_count'] ?>
    }<?= $i < count($categories)-1 ? ',' : '' ?>

<?php endforeach; ?>
  ]
}</pre>
        </div>
    </div>

    <div class="divider"></div>

    <!-- ── Tool Capabilities API ─────────────────────────────────── -->
    <?php
    // Pre-load all search phrases grouped by category for the sample response
    $allPhrases = $pdo->query("
        SELECT tc.name AS category, t.search_phrase
        FROM tools t
        JOIN tool_categories tc ON tc.id = t.category_id
        WHERE t.enabled = 1
        ORDER BY tc.id, t.id
    ")->fetchAll(PDO::FETCH_ASSOC);
    $groupedPhrases = [];
    foreach ($allPhrases as $r) { $groupedPhrases[$r['category']][] = $r['search_phrase']; }
    $firstCat  = array_key_first($groupedPhrases);
    $firstPhrases = $groupedPhrases[$firstCat] ?? [];
    ?>
    <div class="mb-10">
        <h2 class="text-2xl font-bold mb-1" style="color: var(--text-primary);">Tool Capabilities API</h2>
        <p class="text-sm mb-5" style="color: var(--text-secondary);">
            Returns <code style="color:var(--accent);">search_phrase</code> values for a category — or all categories if none is specified. Used by AI agents to discover what each category can do.
        </p>

        <!-- Endpoint box -->
        <div class="rounded-2xl p-5 mb-4" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-widest" style="color: var(--text-secondary);">Endpoint</span>
                <span class="section-badge" style="background-color: rgba(16,185,129,0.15); color: var(--success);">GET</span>
            </div>
            <!-- With category -->
            <p class="text-xs font-semibold mb-1" style="color: var(--text-secondary);">With category filter:</p>
            <div class="flex items-center gap-3 rounded-xl px-4 py-3 mb-3" style="background-color: var(--bg-primary); border: 1px solid var(--border);">
                <code class="api-code flex-1" style="color: var(--text-primary);"><?= htmlspecialchars($baseUrl) ?>/api/tmp-tool-capabilities?api_key=<span style="color:var(--accent);"><?= htmlspecialchars($apiKey) ?></span>&amp;category_name=<span style="color:var(--success);"><?= htmlspecialchars($firstCat ?? 'market-data') ?></span></code>
                <button class="copy-btn" onclick="copyText('<?= htmlspecialchars($baseUrl) ?>/api/tmp-tool-capabilities?api_key=<?= htmlspecialchars($apiKey) ?>&category_name=<?= htmlspecialchars($firstCat ?? 'market-data') ?>', this)" title="Copy">
                    <i data-feather="copy" style="width:15px;height:15px;color:var(--text-secondary);"></i>
                </button>
            </div>
            <!-- All -->
            <p class="text-xs font-semibold mb-1" style="color: var(--text-secondary);">All categories (no filter):</p>
            <div class="flex items-center gap-3 rounded-xl px-4 py-3" style="background-color: var(--bg-primary); border: 1px solid var(--border);">
                <code class="api-code flex-1" style="color: var(--text-primary);"><?= htmlspecialchars($baseUrl) ?>/api/tmp-tool-capabilities?api_key=<span style="color:var(--accent);"><?= htmlspecialchars($apiKey) ?></span></code>
                <button class="copy-btn" onclick="copyText('<?= htmlspecialchars($baseUrl) ?>/api/tmp-tool-capabilities?api_key=<?= htmlspecialchars($apiKey) ?>', this)" title="Copy">
                    <i data-feather="copy" style="width:15px;height:15px;color:var(--text-secondary);"></i>
                </button>
            </div>
        </div>

        <!-- Parameters table -->
        <div class="rounded-2xl overflow-hidden mb-4" style="border: 1px solid var(--border);">
            <div class="px-5 py-3" style="background-color: var(--bg-secondary); border-bottom: 1px solid var(--border);">
                <span class="text-sm font-semibold" style="color: var(--text-primary);">Parameters</span>
            </div>
            <div class="px-5" style="background-color: var(--bg-primary);">
                <div class="param-row flex items-start gap-4">
                    <code class="text-sm font-mono w-36 flex-shrink-0 mt-1" style="color: var(--accent);">api_key</code>
                    <div>
                        <span class="section-badge mr-2" style="background-color: rgba(239,68,68,0.12); color: var(--danger); font-size:0.68rem;">required</span>
                        <span class="text-sm" style="color: var(--text-secondary);">Your API key from Settings</span>
                    </div>
                </div>
                <div class="param-row flex items-start gap-4">
                    <code class="text-sm font-mono w-36 flex-shrink-0 mt-1" style="color: var(--success);">category_name</code>
                    <div>
                        <span class="section-badge mr-2" style="background-color: rgba(16,185,129,0.1); color: var(--success); font-size:0.68rem;">optional</span>
                        <span class="text-sm" style="color: var(--text-secondary);">Filter to a single category. One of: <code style="color:var(--text-primary);"><?= implode(' | ', array_keys($groupedPhrases)) ?></code>. Omit to return all.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Live test buttons -->
        <div class="flex gap-3 flex-wrap mb-5">
            <a href="<?= htmlspecialchars($baseUrl) ?>/api/tmp-tool-capabilities?api_key=<?= htmlspecialchars($apiKey) ?>&category_name=<?= htmlspecialchars($firstCat ?? 'market-data') ?>" target="_blank"
               class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold"
               style="background-color: var(--accent); color: #fff; border-radius: 9999px; text-decoration: none;">
                <i data-feather="external-link" style="width:15px;height:15px;"></i>
                Test with Category Filter
            </a>
            <a href="<?= htmlspecialchars($baseUrl) ?>/api/tmp-tool-capabilities?api_key=<?= htmlspecialchars($apiKey) ?>" target="_blank"
               class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold"
               style="background-color: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border); border-radius: 9999px; text-decoration: none;">
                <i data-feather="list" style="width:15px;height:15px;"></i>
                Test All Categories
            </a>
        </div>

        <!-- Two sample response tabs -->
        <div class="rounded-2xl overflow-hidden" style="border: 1px solid var(--border);">
            <!-- Tab bar -->
            <div class="flex" style="background-color: var(--bg-secondary); border-bottom: 1px solid var(--border);">
                <button id="tab-cat" onclick="switchCapTab('cat')" class="px-5 py-3 text-sm font-semibold border-b-2" style="border-color: var(--accent); color: var(--text-primary); background:none; cursor:pointer;">With category_name</button>
                <button id="tab-all" onclick="switchCapTab('all')" class="px-5 py-3 text-sm font-semibold border-b-2" style="border-color: transparent; color: var(--text-secondary); background:none; cursor:pointer;">All categories</button>
                <div class="flex-1"></div>
                <button class="copy-btn mr-2" onclick="copyCapSample()" title="Copy">
                    <i data-feather="copy" id="cap-copy-icon" style="width:14px;height:14px;color:var(--text-secondary);"></i>
                </button>
            </div>
            <!-- Cat response -->
            <pre id="cap-sample-cat" class="api-code p-5 overflow-x-auto" style="background-color: var(--bg-primary); color: var(--text-secondary); margin:0;">{
  "status": "success",
  "category": "<?= htmlspecialchars($firstCat ?? 'market-data') ?>",
  "count": <?= count($firstPhrases) ?>,
  "search_phrases": [
<?php foreach ($firstPhrases as $i => $phrase): ?>
    "<?= htmlspecialchars($phrase) ?>"<?= $i < count($firstPhrases)-1 ? ',' : '' ?>
<?php endforeach; ?>
  ]
}</pre>
            <!-- All response -->
            <pre id="cap-sample-all" class="api-code p-5 overflow-x-auto" style="background-color: var(--bg-primary); color: var(--text-secondary); margin:0; display:none;">{
  "status": "success",
  "total": <?= count($allPhrases) ?>,
  "categories": [
<?php $catKeys = array_keys($groupedPhrases); foreach ($catKeys as $ci => $cname): $cphrases = $groupedPhrases[$cname]; ?>
    {
      "category": "<?= htmlspecialchars($cname) ?>",
      "count": <?= count($cphrases) ?>,
      "search_phrases": [<?= implode(', ', array_map(fn($p) => '"'.htmlspecialchars($p).'"', $cphrases)) ?>]
    }<?= $ci < count($catKeys)-1 ? ',' : '' ?>
<?php endforeach; ?>
  ]
}</pre>
        </div>
    </div>

    <div class="divider"></div>

    <!-- ── Get Tool API ──────────────────────────────────────────── -->
    <?php
    $sampleTool = $pdo->query("
        SELECT t.tool_name, t.tool_format, t.inputs_explanation, t.description, t.search_phrase,
               tc.name AS category
        FROM tools t
        JOIN tool_categories tc ON tc.id = t.category_id
        WHERE t.enabled = 1
        ORDER BY t.id
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);
    $sampleToolFormat  = $sampleTool ? str_replace(['{base_url}', '{api_key}'], [$baseUrl, $apiKey], $sampleTool['tool_format']) : '';
    $sampleToolJson    = $sampleTool ? json_encode([
        'search_phrase'      => $sampleTool['search_phrase'],
        'tool_url'           => $sampleToolFormat,
        'inputs_explanation' => $sampleTool['inputs_explanation'],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '{}';
    ?>
    <div class="mb-10">
        <h2 class="text-2xl font-bold mb-1" style="color: var(--text-primary);">Get Tool API</h2>
        <p class="text-sm mb-5" style="color: var(--text-secondary);">
            Pass a <code style="color:var(--accent);">search_phrase</code> to receive two fields: <code style="color:var(--accent);">tool_url</code> — the fully-resolved URL with <code style="color:var(--text-primary);">base_url</code> and <code style="color:var(--text-primary);">api_key</code> already substituted — and <code style="color:var(--accent);">inputs_explanation</code> describing each remaining placeholder. The URL is ready to call immediately.
        </p>

        <!-- Endpoint box -->
        <div class="rounded-2xl p-5 mb-4" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-widest" style="color: var(--text-secondary);">Endpoint</span>
                <span class="section-badge" style="background-color: rgba(16,185,129,0.15); color: var(--success);">GET</span>
            </div>
            <div class="flex items-center gap-3 rounded-xl px-4 py-3" style="background-color: var(--bg-primary); border: 1px solid var(--border);">
                <code class="api-code flex-1" style="color: var(--text-primary);"><?= htmlspecialchars($baseUrl) ?>/api/tmp-get-tool?api_key=<span style="color:var(--accent);"><?= htmlspecialchars($apiKey) ?></span>&amp;search_phrase=<span style="color:var(--success);">{search_phrase}</span></code>
                <button class="copy-btn" onclick="copyText('<?= htmlspecialchars($baseUrl) ?>/api/tmp-get-tool?api_key=<?= htmlspecialchars($apiKey) ?>&search_phrase=', this)" title="Copy">
                    <i data-feather="copy" style="width:15px;height:15px;color:var(--text-secondary);"></i>
                </button>
            </div>
        </div>

        <!-- Parameters -->
        <div class="rounded-2xl overflow-hidden mb-4" style="border: 1px solid var(--border);">
            <div class="px-5 py-3" style="background-color: var(--bg-secondary); border-bottom: 1px solid var(--border);">
                <span class="text-sm font-semibold" style="color: var(--text-primary);">Parameters</span>
            </div>
            <div class="px-5" style="background-color: var(--bg-primary);">
                <div class="param-row flex items-start gap-4">
                    <code class="text-sm font-mono w-36 flex-shrink-0 mt-1" style="color: var(--accent);">api_key</code>
                    <div>
                        <span class="section-badge mr-2" style="background-color: rgba(239,68,68,0.12); color: var(--danger); font-size:0.68rem;">required</span>
                        <span class="text-sm" style="color: var(--text-secondary);">Your API key from Settings</span>
                    </div>
                </div>
                <div class="param-row flex items-start gap-4">
                    <code class="text-sm font-mono w-36 flex-shrink-0 mt-1" style="color: var(--accent);">search_phrase</code>
                    <div>
                        <span class="section-badge mr-2" style="background-color: rgba(239,68,68,0.12); color: var(--danger); font-size:0.68rem;">required</span>
                        <span class="text-sm" style="color: var(--text-secondary);">
                            Exact phrase for the tool (case-insensitive). Use
                            <code style="color:var(--text-primary);">/api/tmp-tool-capabilities</code> to list all valid phrases.
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Response fields note -->
        <div class="rounded-2xl p-4 mb-5 flex gap-3" style="background-color: rgba(79,70,229,0.07); border: 1px solid rgba(79,70,229,0.2);">
            <i data-feather="info" style="width:16px;height:16px;color:var(--accent);flex-shrink:0;margin-top:2px;"></i>
            <p class="text-sm leading-relaxed" style="color: var(--text-secondary);">
                The returned <code style="color:var(--accent);">tool_url</code> has <code style="color:var(--text-primary);">base_url</code> and <code style="color:var(--text-primary);">api_key</code> already filled in from your app settings. Only user-specific placeholders like <code style="color:var(--text-primary);">{symbol}</code>, <code style="color:var(--text-primary);">{timeframe}</code>, <code style="color:var(--text-primary);">{ticket}</code> etc. remain for you to substitute.
            </p>
        </div>

        <!-- Live test -->
        <?php if ($sampleTool): ?>
        <div class="flex gap-3 flex-wrap mb-5">
            <a href="<?= htmlspecialchars($baseUrl) ?>/api/tmp-get-tool?api_key=<?= htmlspecialchars($apiKey) ?>&search_phrase=<?= urlencode($sampleTool['search_phrase']) ?>" target="_blank"
               class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold"
               style="background-color: var(--accent); color: #fff; border-radius: 9999px; text-decoration: none;">
                <i data-feather="external-link" style="width:15px;height:15px;"></i>
                Test Live Response
            </a>
        </div>
        <?php endif; ?>

        <!-- Sample response -->
        <div class="rounded-2xl overflow-hidden" style="border: 1px solid var(--border);">
            <div class="flex items-center justify-between px-5 py-3" style="background-color: var(--bg-secondary); border-bottom: 1px solid var(--border);">
                <span class="text-sm font-semibold" style="color: var(--text-primary);">Sample Response</span>
                <button class="copy-btn" onclick="copyText(document.getElementById('get-tool-sample').innerText, this)" title="Copy">
                    <i data-feather="copy" style="width:14px;height:14px;color:var(--text-secondary);"></i>
                </button>
            </div>
            <pre id="get-tool-sample" class="api-code p-5 overflow-x-auto" style="background-color: var(--bg-primary); color: var(--text-secondary); margin:0;"><?= htmlspecialchars($sampleToolJson) ?></pre>
        </div>
    </div>

    <div class="divider"></div>
    <div class="mb-6">
        <h2 class="text-2xl font-bold mb-1" style="color: var(--text-primary);">Tool Categories</h2>
        <p class="text-sm" style="color: var(--text-secondary);">Click a category card to open its API guide.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mb-10">
        <?php foreach ($categories as $cat):
            $m     = $catMeta[$cat['name']] ?? ['icon' => 'box', 'color' => '#4f46e5', 'guide' => '#', 'label' => ucfirst($cat['name'])];
            $pct   = $cat['tool_count'] > 0 ? round(($cat['enabled_count'] / $cat['tool_count']) * 100) : 0;
        ?>
        <a href="<?= $m['guide'] ?>" class="cat-card block p-6" style="background-color: var(--card-bg); border: 1px solid var(--border); text-decoration: none;">
            <!-- Header -->
            <div class="flex items-start justify-between mb-4">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0" style="background-color: <?= $m['color'] ?>22; border: 1px solid <?= $m['color'] ?>44;">
                    <i data-feather="<?= $m['icon'] ?>" style="width:22px;height:22px;color:<?= $m['color'] ?>;"></i>
                </div>
                <span class="section-badge" style="background-color: <?= $m['color'] ?>22; color: <?= $m['color'] ?>;">
                    <?= $cat['tool_count'] ?> tools
                </span>
            </div>

            <!-- Name + description -->
            <h3 class="text-base font-bold mb-1 tracking-tight" style="color: var(--text-primary);"><?= htmlspecialchars($m['label']) ?></h3>
            <p class="text-sm mb-4 leading-relaxed" style="color: var(--text-secondary);"><?= htmlspecialchars($cat['description']) ?></p>

            <!-- Endpoint -->
            <?php if ($cat['endpoint_base']): ?>
            <div class="rounded-xl px-3 py-2 mb-4 overflow-hidden" style="background-color: var(--bg-secondary);">
                <code class="text-xs" style="color: var(--text-secondary); word-break: break-all;"><?= htmlspecialchars($cat['endpoint_base']) ?></code>
            </div>
            <?php endif; ?>

            <!-- EA badge -->
            <div class="flex items-center gap-2 flex-wrap">
                <?php if ($cat['requires_ea']): ?>
                <span class="stat-pill" style="background-color: rgba(245,158,11,0.1); color: var(--warning); border: 1px solid rgba(245,158,11,0.2);">
                    <i data-feather="cpu" style="width:12px;height:12px;"></i>
                    Requires MT5 EA
                </span>
                <?php else: ?>
                <span class="stat-pill" style="background-color: rgba(16,185,129,0.1); color: var(--success); border: 1px solid rgba(16,185,129,0.2);">
                    <i data-feather="wifi" style="width:12px;height:12px;"></i>
                    No EA needed
                </span>
                <?php endif; ?>

                <!-- Enabled bar -->
                <div class="flex items-center gap-2 ml-auto">
                    <div class="h-1.5 rounded-full overflow-hidden" style="width: 60px; background-color: var(--border);">
                        <div class="h-full rounded-full" style="width: <?= $pct ?>%; background-color: <?= $m['color'] ?>;"></div>
                    </div>
                    <span class="text-xs" style="color: var(--text-secondary);"><?= $cat['enabled_count'] ?>/<?= $cat['tool_count'] ?></span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="divider"></div>

    <!-- ── EA Requirements Summary ─────────────────────────────────── -->
    <div class="mb-10">
        <h2 class="text-2xl font-bold mb-5" style="color: var(--text-primary);">EA Requirements</h2>
        <div class="rounded-2xl overflow-hidden" style="border: 1px solid var(--border);">
            <div class="px-5 py-3" style="background-color: var(--bg-secondary); border-bottom: 1px solid var(--border);">
                <span class="text-sm font-semibold" style="color: var(--text-primary);">Which MT5 Expert Advisors are needed per category</span>
            </div>
            <div style="background-color: var(--bg-primary);">
                <?php foreach ($categories as $i => $cat):
                    $m = $catMeta[$cat['name']] ?? ['icon' => 'box', 'color' => '#4f46e5', 'label' => ucfirst($cat['name'])];
                ?>
                <div class="flex items-center gap-4 px-5 py-4 <?= $i < count($categories)-1 ? '' : '' ?>" style="<?= $i < count($categories)-1 ? 'border-bottom: 1px solid var(--border);' : '' ?>">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0" style="background-color: <?= $m['color'] ?>22;">
                        <i data-feather="<?= $m['icon'] ?>" style="width:15px;height:15px;color:<?= $m['color'] ?>;"></i>
                    </div>
                    <div class="flex-1">
                        <span class="text-sm font-semibold" style="color: var(--text-primary);"><?= htmlspecialchars($m['label']) ?></span>
                    </div>
                    <div class="text-sm text-right" style="color: var(--text-secondary);">
                        <?php if ($cat['requires_ea'] && $cat['ea_name']): ?>
                            <code class="text-xs px-3 py-1 rounded-full" style="background-color: var(--bg-secondary); color: var(--warning);"><?= htmlspecialchars($cat['ea_name']) ?></code>
                        <?php else: ?>
                            <span class="text-xs px-3 py-1 rounded-full" style="background-color: rgba(16,185,129,0.1); color: var(--success);">No EA required</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- ── Quick-start TMP Usage ──────────────────────────────────── -->
    <div class="mb-10">
        <h2 class="text-2xl font-bold mb-2" style="color: var(--text-primary);">Using TMP with an AI Agent</h2>
        <p class="text-sm mb-5" style="color: var(--text-secondary);">
            Give your AI agent the system context below so it can discover and call tools automatically.
        </p>

        <div class="rounded-2xl overflow-hidden" style="border: 1px solid var(--border);">
            <div class="flex items-center justify-between px-5 py-3" style="background-color: var(--bg-secondary); border-bottom: 1px solid var(--border);">
                <span class="text-sm font-semibold" style="color: var(--text-primary);">System Prompt Snippet</span>
                <button class="copy-btn" onclick="copyText(document.getElementById('system-prompt').innerText, this)" title="Copy">
                    <i data-feather="copy" style="width:14px;height:14px;color:var(--text-secondary);"></i>
                </button>
            </div>
            <pre id="system-prompt" class="api-code p-5 overflow-x-auto" style="background-color: var(--bg-primary); color: var(--text-secondary); margin:0;">You have access to the Arrissa Data API.
Your base URL is: <?= htmlspecialchars($baseUrl) ?>

Your API key is: <?= htmlspecialchars($apiKey) ?>


To discover available tools, call:
  GET <?= htmlspecialchars($baseUrl) ?>/api/tmp-categories?api_key=<?= htmlspecialchars($apiKey) ?>

To get search_phrase list for a category, call:
  GET <?= htmlspecialchars($baseUrl) ?>/api/tmp-tool-capabilities?api_key=<?= htmlspecialchars($apiKey) ?>&category_name={category_name}

To get the full tool URL + inputs for a specific tool, call:
  GET <?= htmlspecialchars($baseUrl) ?>/api/tmp-get-tool?api_key=<?= htmlspecialchars($apiKey) ?>&search_phrase={search_phrase}

Each tool has:
  - tool_name          : unique identifier
  - tool_format        : URL template with {placeholders}
  - inputs_explanation : what each placeholder accepts
  - description        : what the tool does
  - search_phrase      : semantic label for tool discovery

Replace {base_url} with <?= htmlspecialchars($baseUrl) ?> and {api_key} with <?= htmlspecialchars($apiKey) ?> in all tool_format values.</pre>
        </div>
    </div>

</div>

<script>
function copyText(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        btn.classList.add('copied');
        setTimeout(() => btn.classList.remove('copied'), 1800);
    });
}
function copySample() {
    const text = document.getElementById('sample-response').innerText;
    copyText(text, document.getElementById('sample-copy-btn'));
}
function switchCapTab(tab) {
    const catPre  = document.getElementById('cap-sample-cat');
    const allPre  = document.getElementById('cap-sample-all');
    const tabCat  = document.getElementById('tab-cat');
    const tabAll  = document.getElementById('tab-all');
    if (tab === 'cat') {
        catPre.style.display = '';
        allPre.style.display = 'none';
        tabCat.style.borderColor = 'var(--accent)';
        tabCat.style.color = 'var(--text-primary)';
        tabAll.style.borderColor = 'transparent';
        tabAll.style.color = 'var(--text-secondary)';
    } else {
        catPre.style.display = 'none';
        allPre.style.display = '';
        tabAll.style.borderColor = 'var(--accent)';
        tabAll.style.color = 'var(--text-primary)';
        tabCat.style.borderColor = 'transparent';
        tabCat.style.color = 'var(--text-secondary)';
    }
}
function copyCapSample() {
    const visible = document.getElementById('cap-sample-cat').style.display !== 'none'
        ? document.getElementById('cap-sample-cat')
        : document.getElementById('cap-sample-all');
    copyText(visible.innerText, document.getElementById('cap-copy-icon').closest('button'));
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/app.php';
