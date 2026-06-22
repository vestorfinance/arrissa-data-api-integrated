<?php
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Database.php';

$db = Database::getInstance();

$stmt   = $db->query("SELECT value FROM settings WHERE key = 'app_base_url'");
$result = $stmt->fetch();
$baseUrl = $result ? $result['value'] : 'http://localhost:8000';

$stmt   = $db->query("SELECT value FROM settings WHERE key = 'api_key'");
$result = $stmt->fetch();
$apiKey = $result ? $result['value'] : '';

$title = 'Google News API Guide';
$page  = 'google-news-api-guide';
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
.method-badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 9999px;
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.05em;
}
.endpoint-tag {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 9999px;
    font-size: 0.7rem;
    font-weight: 700;
    font-family: 'Fira Code', monospace;
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}
</style>

<div class="p-8 max-w-[1600px] mx-auto">

    <!-- Hero Header -->
    <div class="mb-10">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-4xl font-bold mb-3 tracking-tight" style="color: var(--text-primary);">
                    Google News API
                    <span class="section-badge ml-3" style="background: linear-gradient(135deg, #0EA5E9, #6366F1); color: white;">v1.0</span>
                </h1>
                <p class="text-lg" style="color: var(--text-secondary);">Live news headlines from Google News RSS — search, top stories, topics, geo, and breaking news with optional full article extraction</p>
            </div>
        </div>

        <!-- Features Banner -->
        <div class="p-6 rounded-2xl gradient-bg" style="border: 1px solid var(--border);">
            <div class="flex items-start">
                <div class="flex-shrink-0 mr-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background: linear-gradient(135deg, #0EA5E9, #6366F1);">
                        <i data-feather="rss" style="width: 24px; height: 24px; color: white;"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">What is the Google News API?</h3>
                    <p class="text-sm mb-4" style="color: var(--text-secondary);">A pure-PHP wrapper around Google News RSS feeds. Send a request with your query, topic, or location — get back structured JSON with headlines, sources, publish dates, and descriptions. Enable <code>data_depth=true</code> for the API to follow each article link and extract the full article body, author, image, and metadata.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm" style="color: var(--text-secondary);">
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #0EA5E9;"></i>
                            <span><strong>5 Endpoints:</strong> search, top headlines, topic, geo/location, and breaking news — all in one file</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #0EA5E9;"></i>
                            <span><strong>Multi-language &amp; Region:</strong> Pass <code>hl</code>, <code>gl</code>, and <code>ceid</code> to get news in any language and country</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #0EA5E9;"></i>
                            <span><strong>Article Enrichment:</strong> <code>data_depth=true</code> follows links and extracts full article text, author, image, and publish date</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: #0EA5E9;"></i>
                            <span><strong>Breaking News Filter:</strong> Returns only articles published within the last N hours, deduplicated and sorted newest-first</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- API Endpoint -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-6" style="color: var(--text-primary);">API Endpoint</h2>
        <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="mb-4">
                <label class="text-sm font-medium mb-2 block" style="color: var(--text-secondary);">Base URL</label>
                <div class="p-4 rounded-xl font-mono text-sm" style="background-color: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-primary);">
                    <?php echo $baseUrl; ?>/google-news-api-v1/google-news-api.php
                </div>
            </div>
            <div class="mb-4">
                <label class="text-sm font-medium mb-2 block" style="color: var(--text-secondary);">Your API Key</label>
                <div class="p-4 rounded-xl font-mono text-sm break-all" style="background-color: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-primary);">
                    <?php echo $apiKey ?: 'Not configured — visit Settings'; ?>
                </div>
            </div>
            <div>
                <label class="text-sm font-medium mb-2 block" style="color: var(--text-secondary);">HTTP Methods</label>
                <div class="flex gap-2">
                    <span class="method-badge" style="background: rgba(16,185,129,0.2); color: #10B981;">GET</span>
                    <span class="method-badge" style="background: rgba(14,165,233,0.2); color: #0EA5E9;">POST</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Endpoints Overview -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-6" style="color: var(--text-primary);">Endpoints</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php
            $endpoints = [
                ['search',   '#0EA5E9', 'search', 'Search news by keyword, phrase, or Boolean query. Supports advanced filters like when:, site:, intitle:, before:, and after:.'],
                ['top',      '#6366F1', 'trending-up', 'Top headlines from Google News — the current front page for any language and country.'],
                ['topic',    '#10B981', 'tag', 'News filtered by topic category: WORLD, NATION, BUSINESS, TECHNOLOGY, ENTERTAINMENT, SCIENCE, SPORTS, or HEALTH.'],
                ['geo',      '#F59E0B', 'map-pin', 'News specific to a city, country, or region — e.g. ?location=Zimbabwe or ?location=Tokyo.'],
                ['breaking', '#EF4444', 'zap', 'Breaking news from the last N hours (default 2 h). Pulls multiple feeds, deduplicates, and sorts newest-first.'],
            ];
            foreach ($endpoints as $ep):
            ?>
            <div class="p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-center mb-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center mr-3 flex-shrink-0" style="background: linear-gradient(135deg, <?php echo $ep[1]; ?>, <?php echo $ep[1]; ?>99);">
                        <i data-feather="<?php echo $ep[2]; ?>" style="width: 18px; height: 18px; color: white;"></i>
                    </div>
                    <code class="font-bold text-sm" style="color: <?php echo $ep[1]; ?>;">?endpoint=<?php echo $ep[0]; ?></code>
                </div>
                <p class="text-xs" style="color: var(--text-secondary);"><?php echo $ep[3]; ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Parameters -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-6" style="color: var(--text-primary);">Parameters</h2>
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
                    <?php
                    $params = [
                        ['api_key',    'string',  '✅ Yes', '#0EA5E9', 'Your API authentication key. Also accepted as <code>X-Api-Key</code> header.'],
                        ['endpoint',   'string',  '✅ Yes', '#0EA5E9', 'One of: <code>search</code>, <code>top</code>, <code>topic</code>, <code>geo</code>, <code>breaking</code>. Default: <code>top</code>.'],
                        ['q',          'string',  '❌ search', '#F59E0B', 'Search query. Required for <code>endpoint=search</code>. Supports Google News operators: <code>when:1d</code>, <code>site:bbc.com</code>, <code>intitle:bitcoin</code>.'],
                        ['topic',      'string',  '❌ topic', '#F59E0B', 'Topic category for <code>endpoint=topic</code>. One of: <code>WORLD</code> <code>NATION</code> <code>BUSINESS</code> <code>TECHNOLOGY</code> <code>ENTERTAINMENT</code> <code>SCIENCE</code> <code>SPORTS</code> <code>HEALTH</code>.'],
                        ['location',   'string',  '❌ geo', '#F59E0B', 'City, country, or region name for <code>endpoint=geo</code>. E.g. <code>Zimbabwe</code>, <code>Tokyo</code>, <code>United States</code>.'],
                        ['num',        'integer', '❌ No', null, 'Max number of results to return. Range: 1–100. Default: <code>10</code>.'],
                        ['hl',         'string',  '❌ No', null, 'Language code (BCP-47). Default: <code>en-US</code>. E.g. <code>fr</code>, <code>de</code>, <code>es</code>, <code>zh-CN</code>.'],
                        ['gl',         'string',  '❌ No', null, 'Country code (ISO 3166-1 alpha-2). Default: <code>US</code>. E.g. <code>GB</code>, <code>ZA</code>, <code>JP</code>.'],
                        ['ceid',       'string',  '❌ No', null, 'Locale identifier, e.g. <code>US:en</code>. Auto-derived from <code>hl</code> and <code>gl</code> if omitted.'],
                        ['data_depth', 'bool',    '❌ No', null, 'Set to <code>true</code> to follow each article link and extract the full article body, author, image, and metadata. Slower — allow 2–5 s per article.'],
                        ['when',       'string',  '❌ No', null, 'Time filter for search results: <code>1h</code>, <code>4h</code>, <code>1d</code>, <code>7d</code>, etc.'],
                        ['after',      'string',  '❌ No', null, 'Return articles published after this date (YYYY-MM-DD). For <code>endpoint=search</code>.'],
                        ['before',     'string',  '❌ No', null, 'Return articles published before this date (YYYY-MM-DD). For <code>endpoint=search</code>.'],
                        ['site',       'string',  '❌ No', null, 'Restrict search results to a specific domain, e.g. <code>bbc.com</code>.'],
                        ['intitle',    'string',  '❌ No', null, 'Return only articles where the title contains this keyword.'],
                        ['within',     'float',   '❌ No', null, 'For <code>endpoint=breaking</code>: only return articles published within this many hours. Max: <code>24</code>. Default: <code>2</code>.'],
                    ];
                    foreach ($params as $i => $p):
                        $border = $i < count($params) - 1 ? 'border-bottom: 1px solid var(--border);' : '';
                    ?>
                    <tr style="<?php echo $border; ?>">
                        <td class="px-6 py-4"><code class="text-sm" style="color: <?php echo $p[3] ?? '#6366F1'; ?>;"><?php echo $p[0]; ?></code></td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);"><?php echo $p[1]; ?></td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);"><?php echo $p[2]; ?></td>
                        <td class="px-6 py-4 text-sm" style="color: var(--text-secondary);"><?php echo $p[4]; ?></td>
                    </tr>
                    <?php endforeach; ?>
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
                <p class="text-sm" style="color: var(--text-secondary);">Copy and paste these into your terminal</p>
            </div>
        </div>

        <?php
        $apiBase = "{$baseUrl}/google-news-api-v1/google-news-api.php";

        $curlSections = [

            // Search
            [
                'title'  => 'Search News',
                'color'  => '#0EA5E9',
                'badge'  => 'search',
                'desc'   => 'Find articles matching a keyword or phrase. Supports Google News operators: <code>when:</code>, <code>site:</code>, <code>intitle:</code>, <code>before:</code>, <code>after:</code>.',
                'examples' => [
                    ['Bitcoin news — last 24 hours',       "?endpoint=search&q=bitcoin&when=1d&num=5&api_key={$apiKey}"],
                    ['Gold price — site restricted',        "?endpoint=search&q=gold+price&site=reuters.com&num=5&api_key={$apiKey}"],
                    ['Forex news — EUR/USD',               "?endpoint=search&q=EURUSD+forex&num=10&api_key={$apiKey}"],
                    ['Tech news — in title only',          "?endpoint=search&q=AI&intitle=artificial+intelligence&num=5&api_key={$apiKey}"],
                    ['Market news — specific date range',  "?endpoint=search&q=stock+market&after=2025-01-01&before=2025-12-31&num=5&api_key={$apiKey}"],
                ],
            ],

            // Top
            [
                'title'  => 'Top Headlines',
                'color'  => '#6366F1',
                'badge'  => 'top',
                'desc'   => 'The current Google News front page — top stories across all categories for the specified language and region.',
                'examples' => [
                    ['Top headlines — US English',         "?endpoint=top&num=10&api_key={$apiKey}"],
                    ['Top headlines — UK English',         "?endpoint=top&hl=en-GB&gl=GB&ceid=GB:en&num=10&api_key={$apiKey}"],
                    ['Top headlines — South Africa',       "?endpoint=top&hl=en-ZA&gl=ZA&ceid=ZA:en&num=10&api_key={$apiKey}"],
                    ['Top headlines — French',             "?endpoint=top&hl=fr&gl=FR&ceid=FR:fr&num=10&api_key={$apiKey}"],
                    ['Top headlines — Japanese',           "?endpoint=top&hl=ja&gl=JP&ceid=JP:ja&num=10&api_key={$apiKey}"],
                ],
            ],

            // Topic
            [
                'title'  => 'Topic News',
                'color'  => '#10B981',
                'badge'  => 'topic',
                'desc'   => 'News filtered by Google News topic section. Valid topics: WORLD, NATION, BUSINESS, TECHNOLOGY, ENTERTAINMENT, SCIENCE, SPORTS, HEALTH.',
                'examples' => [
                    ['Business news',                     "?endpoint=topic&topic=BUSINESS&num=10&api_key={$apiKey}"],
                    ['Technology news',                   "?endpoint=topic&topic=TECHNOLOGY&num=10&api_key={$apiKey}"],
                    ['World news',                        "?endpoint=topic&topic=WORLD&num=10&api_key={$apiKey}"],
                    ['Science news',                      "?endpoint=topic&topic=SCIENCE&num=10&api_key={$apiKey}"],
                    ['Sports news',                       "?endpoint=topic&topic=SPORTS&num=10&api_key={$apiKey}"],
                    ['Health news',                       "?endpoint=topic&topic=HEALTH&num=10&api_key={$apiKey}"],
                ],
            ],

            // Geo
            [
                'title'  => 'Geo / Location News',
                'color'  => '#F59E0B',
                'badge'  => 'geo',
                'desc'   => 'News specific to a geographic location — country, city, or region. Google News resolves the location name automatically.',
                'examples' => [
                    ['Zimbabwe news',                     "?endpoint=geo&location=Zimbabwe&num=10&api_key={$apiKey}"],
                    ['South Africa news',                 "?endpoint=geo&location=South+Africa&num=10&api_key={$apiKey}"],
                    ['Tokyo news',                        "?endpoint=geo&location=Tokyo&num=10&api_key={$apiKey}"],
                    ['New York news',                     "?endpoint=geo&location=New+York&num=10&api_key={$apiKey}"],
                    ['London news',                       "?endpoint=geo&location=London&num=10&api_key={$apiKey}"],
                    ['Dubai news',                        "?endpoint=geo&location=Dubai&num=10&api_key={$apiKey}"],
                ],
            ],

            // Breaking
            [
                'title'  => 'Breaking News',
                'color'  => '#EF4444',
                'badge'  => 'breaking',
                'desc'   => 'Articles published within the last N hours, deduplicated across multiple feeds and sorted newest-first.',
                'examples' => [
                    ['Breaking — last 2 hours (default)', "?endpoint=breaking&api_key={$apiKey}"],
                    ['Breaking — last 1 hour',            "?endpoint=breaking&within=1&api_key={$apiKey}"],
                    ['Breaking — last 6 hours',           "?endpoint=breaking&within=6&num=20&api_key={$apiKey}"],
                    ['Breaking — crypto (last 4 h)',      "?endpoint=breaking&q=crypto&within=4&num=10&api_key={$apiKey}"],
                    ['Breaking — WORLD topic (last 3 h)', "?endpoint=breaking&topic=WORLD&within=3&num=10&api_key={$apiKey}"],
                    ['Breaking — forex (last 2 h)',       "?endpoint=breaking&q=forex&within=2&num=10&api_key={$apiKey}"],
                ],
            ],

            // data_depth
            [
                'title'  => 'Article Enrichment (data_depth=true)',
                'color'  => '#8B5CF6',
                'badge'  => 'data_depth',
                'desc'   => 'Appends full article content to each item — the API follows each link, fetches the page, and extracts body text, author, image, published date, and more. Expect 2–5 seconds extra per article.',
                'examples' => [
                    ['Top headlines + full article body',  "?endpoint=top&num=3&data_depth=true&api_key={$apiKey}"],
                    ['Bitcoin search + full articles',     "?endpoint=search&q=bitcoin&num=3&data_depth=true&api_key={$apiKey}"],
                    ['Breaking + full articles',           "?endpoint=breaking&within=2&num=3&data_depth=true&api_key={$apiKey}"],
                ],
            ],
        ];

        foreach ($curlSections as $section):
        ?>
        <div class="mb-6 p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <h3 class="text-lg font-semibold mb-2" style="color: var(--text-primary);">
                <span class="endpoint-tag mr-2" style="background: rgba(0,0,0,0.3); color: <?php echo $section['color']; ?>; border: 1px solid <?php echo $section['color']; ?>33;"><?php echo $section['badge']; ?></span>
                <?php echo $section['title']; ?>
            </h3>
            <p class="text-sm mb-4" style="color: var(--text-secondary);"><?php echo $section['desc']; ?></p>
            <div class="space-y-3">
                <?php foreach ($section['examples'] as $ex):
                    $url = $apiBase . $ex[1];
                ?>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-xs font-semibold" style="color: var(--text-secondary);"><?php echo $ex[0]; ?></label>
                        <button onclick="copyToClipboard('curl &quot;<?= htmlspecialchars($url) ?>&quot;')" class="text-xs px-2 py-1 rounded-lg transition-colors flex items-center gap-1" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);">
                            <i data-feather="copy" style="width: 12px; height: 12px;"></i>
                            Copy
                        </button>
                    </div>
                    <div class="p-3 rounded-lg" style="background-color: var(--input-bg); border: 1px solid var(--input-border); font-family: 'Fira Code', monospace; font-size: 0.75rem; overflow-x: auto; white-space: nowrap;">
                        <code style="color: var(--text-primary);">curl "<?= htmlspecialchars($url) ?>"</code>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="divider"></div>

    <!-- Live Test -->
    <div class="mb-12">
        <h2 class="text-2xl font-bold mb-6" style="color: var(--text-primary);">Live Test</h2>
        <div class="grid grid-cols-1 gap-6">
            <?php
            $liveTests = [
                ['Top Headlines',              "#0EA5E9", "?endpoint=top&num=5&api_key={$apiKey}"],
                ['Bitcoin Search',             "#6366F1", "?endpoint=search&q=bitcoin&num=5&api_key={$apiKey}"],
                ['Business Topic',             "#10B981", "?endpoint=topic&topic=BUSINESS&num=5&api_key={$apiKey}"],
                ['Breaking News — last 2h',    "#EF4444", "?endpoint=breaking&within=2&num=5&api_key={$apiKey}"],
                ['Zimbabwe Geo News',          "#F59E0B", "?endpoint=geo&location=Zimbabwe&num=5&api_key={$apiKey}"],
                ['Technology Topic',           "#8B5CF6", "?endpoint=topic&topic=TECHNOLOGY&num=5&api_key={$apiKey}"],
            ];
            foreach ($liveTests as $idx => $lt):
                $url = $apiBase . $lt[2];
            ?>
            <div class="p-6 rounded-2xl example-card cursor-pointer" style="background-color: var(--card-bg); border: 1px solid var(--border);" onclick="testNewsAPI(event.currentTarget, '<?php echo htmlspecialchars($url); ?>')">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3" style="background: linear-gradient(135deg, <?php echo $lt[1]; ?>, <?php echo $lt[1]; ?>99);">
                            <span class="text-sm font-bold text-white"><?php echo $idx + 1; ?></span>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold" style="color: var(--text-primary);"><?php echo $lt[0]; ?></h3>
                            <span class="text-xs font-mono" style="color: var(--text-secondary);"><?php echo htmlspecialchars($lt[2]); ?></span>
                        </div>
                    </div>
                    <button class="px-3 py-1 rounded-lg text-xs font-medium" style="background: linear-gradient(135deg, #0EA5E9, #6366F1); color: white;" onclick="event.stopPropagation(); testNewsAPI(event.currentTarget.closest('.example-card'), '<?php echo htmlspecialchars($url); ?>')">
                        Test Request
                    </button>
                </div>
                <div class="p-4 rounded-xl font-mono text-sm overflow-x-auto" style="background-color: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-primary); white-space: nowrap;">
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

        <!-- Payload Fields Overview -->
        <div class="mb-6 p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <h3 class="text-xl font-semibold mb-6" style="color: var(--text-primary);">Response Payload Overview</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <div class="payload-field-badge mb-3">Core</div>
                    <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Status &amp; Meta</h4>
                    <ul class="text-xs space-y-1" style="color: var(--text-secondary);">
                        <li><code>status</code> — "ok" or "error"</li>
                        <li><code>count</code> — items returned</li>
                        <li><code>feedTitle</code> — RSS feed name</li>
                        <li><code>feedLink</code> — RSS feed URL</li>
                    </ul>
                </div>
                <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <div class="payload-field-badge mb-3">Item</div>
                    <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Each News Item</h4>
                    <ul class="text-xs space-y-1" style="color: var(--text-secondary);">
                        <li><code>title</code></li>
                        <li><code>link</code> — Google News URL</li>
                        <li><code>pubDate</code></li>
                        <li><code>source</code> — publisher name</li>
                        <li><code>sourceUrl</code></li>
                        <li><code>description</code></li>
                    </ul>
                </div>
                <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <div class="payload-field-badge mb-3">Enriched</div>
                    <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">articleContent (data_depth)</h4>
                    <ul class="text-xs space-y-1" style="color: var(--text-secondary);">
                        <li><code>actualUrl</code> — resolved URL</li>
                        <li><code>articleTitle</code></li>
                        <li><code>author</code></li>
                        <li><code>publishedDate</code></li>
                        <li><code>image</code></li>
                        <li><code>body</code> — article text</li>
                        <li><code>wordCount</code></li>
                    </ul>
                </div>
                <div class="p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <div class="payload-field-badge mb-3">Context</div>
                    <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Endpoint Context Fields</h4>
                    <ul class="text-xs space-y-1" style="color: var(--text-secondary);">
                        <li><code>query</code> — (search)</li>
                        <li><code>topic</code> — (topic)</li>
                        <li><code>location</code> — (geo)</li>
                        <li><code>withinHours</code> — (breaking)</li>
                        <li><code>ageMinutes</code> — (breaking items)</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Basic Response -->
        <div class="p-6 rounded-2xl mb-4" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <h3 class="text-base font-semibold mb-4" style="color: var(--text-primary);">Basic Response (endpoint=search)</h3>
            <div class="response-preview" style="color: var(--text-primary);">
<pre>{
  "status": "ok",
  "count": 5,
  "feedTitle": "\"bitcoin\" - Google News",
  "feedLink": "",
  "query": "bitcoin",
  "items": [
    {
      "title": "Bitcoin hits new all-time high above $100,000",
      "link": "https://news.google.com/articles/CBMi...",
      "pubDate": "Mon, 22 Jun 2026 09:30:00 GMT",
      "source": "Reuters",
      "sourceUrl": "https://www.reuters.com",
      "description": "Bitcoin surged past $100,000 for the first time..."
    },
    { ... }
  ]
}</pre>
            </div>
        </div>

        <!-- Enriched Response -->
        <div class="p-6 rounded-2xl mb-4" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <h3 class="text-base font-semibold mb-4" style="color: var(--text-primary);">Enriched Response (data_depth=true)</h3>
            <div class="response-preview" style="color: var(--text-primary);">
<pre>{
  "status": "ok",
  "count": 3,
  "feedTitle": "Top stories - Google News",
  "feedLink": "",
  "items": [
    {
      "title": "Markets Rally on Fed Rate Cut Signal",
      "link": "https://news.google.com/articles/CBMi...",
      "pubDate": "Mon, 22 Jun 2026 10:00:00 GMT",
      "source": "Bloomberg",
      "sourceUrl": "https://www.bloomberg.com",
      "description": "U.S. stocks climbed after the Federal Reserve...",
      "articleContent": {
        "actualUrl": "https://www.bloomberg.com/news/articles/...",
        "articleTitle": "Markets Rally on Fed Rate Cut Signal",
        "author": "Jane Smith",
        "publishedDate": "2026-06-22T10:00:00Z",
        "modifiedDate": null,
        "section": "Markets",
        "description": "U.S. stocks climbed after the Federal Reserve...",
        "image": "https://assets.bwbx.io/images/...",
        "keywords": "stocks, fed, rate cut",
        "video": null,
        "wordCount": 342,
        "body": "U.S. equities surged Monday after the Federal Reserve signaled...",
        "screenshotUrl": null
      }
    }
  ]
}</pre>
            </div>
        </div>

        <!-- Breaking Response -->
        <div class="p-6 rounded-2xl mb-4" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <h3 class="text-base font-semibold mb-4" style="color: var(--text-primary);">Breaking News Response (endpoint=breaking)</h3>
            <div class="response-preview" style="color: var(--text-primary);">
<pre>{
  "status": "ok",
  "count": 8,
  "feedTitle": "Breaking News",
  "feedLink": "",
  "withinHours": 2,
  "topic": null,
  "query": null,
  "items": [
    {
      "title": "Breaking: Central bank raises rates by 25 bps",
      "link": "https://news.google.com/articles/...",
      "pubDate": "Mon, 22 Jun 2026 11:45:00 GMT",
      "source": "Financial Times",
      "sourceUrl": "https://www.ft.com",
      "description": "The central bank surprised markets with a rate hike...",
      "ageMinutes": 12
    }
  ]
}</pre>
            </div>
        </div>

        <!-- Response Fields Table -->
        <div class="p-6 rounded-2xl mb-4" style="background-color: var(--card-bg); border: 1px solid var(--border);">
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
                        <?php
                        $fields = [
                            ['status',         'string',  'ok or error'],
                            ['count',          'integer', 'Number of items returned'],
                            ['feedTitle',      'string',  'Google News RSS feed title'],
                            ['feedLink',       'string',  'URL of the RSS feed source'],
                            ['items[].title',  'string',  'Headline text of the news article'],
                            ['items[].link',   'string',  'Google News redirect URL (resolves to the actual article)'],
                            ['items[].pubDate','string',  'Publication date in RFC 2822 format (e.g. Mon, 22 Jun 2026 09:30:00 GMT)'],
                            ['items[].source', 'string',  'Publisher name (e.g. BBC News, Reuters)'],
                            ['items[].sourceUrl','string','Publisher website URL'],
                            ['items[].description','string','Short article excerpt (HTML-stripped)'],
                            ['items[].ageMinutes','integer','Minutes since publication — only present in breaking endpoint'],
                            ['items[].articleContent','object','Full article data — only present when data_depth=true'],
                            ['articleContent.actualUrl','string','Final URL after following Google News redirect'],
                            ['articleContent.articleTitle','string','Full headline from the article page'],
                            ['articleContent.author','string|null','Author byline extracted from structured data or meta tags'],
                            ['articleContent.publishedDate','string|null','ISO 8601 publish date from JSON-LD or article:published_time'],
                            ['articleContent.image','string|null','Featured image URL from og:image or JSON-LD'],
                            ['articleContent.keywords','string|null','Keywords from JSON-LD or meta tags'],
                            ['articleContent.body','string|null','Full article body text (up to 8 000 characters)'],
                            ['articleContent.wordCount','integer','Number of words in the extracted body text'],
                        ];
                        foreach ($fields as $i => $f):
                            $border = $i < count($fields) - 1 ? 'border-bottom: 1px solid var(--border);' : '';
                        ?>
                        <tr style="<?php echo $border; ?>">
                            <td class="px-5 py-3"><code style="color: #0EA5E9;"><?php echo $f[0]; ?></code></td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);"><?php echo $f[1]; ?></td>
                            <td class="px-5 py-3" style="color: var(--text-secondary);"><?php echo $f[2]; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Error Responses -->
        <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <h3 class="text-base font-semibold mb-4" style="color: var(--text-primary);">Error Responses</h3>
            <div class="space-y-3">
                <div class="p-4 rounded-xl" style="background-color: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.3);">
                    <div class="font-mono text-xs mb-2" style="color: #EF4444;">HTTP 401 — Invalid or missing API key</div>
                    <p class="text-xs" style="color: var(--text-secondary);">No <code>api_key</code> parameter or <code>X-Api-Key</code> header was provided, or the value does not match. Check Settings for your key.</p>
                </div>
                <div class="p-4 rounded-xl" style="background-color: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.3);">
                    <div class="font-mono text-xs mb-2" style="color: #EF4444;">HTTP 400 — Parameter 'q' is required for the search endpoint</div>
                    <p class="text-xs" style="color: var(--text-secondary);">You called <code>endpoint=search</code> without providing the <code>q</code> parameter.</p>
                </div>
                <div class="p-4 rounded-xl" style="background-color: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.3);">
                    <div class="font-mono text-xs mb-2" style="color: #EF4444;">HTTP 400 — Invalid topic. Valid values: WORLD, NATION, ...</div>
                    <p class="text-xs" style="color: var(--text-secondary);">The <code>topic</code> value is not one of the eight valid categories. Topics are case-insensitive in the parameter but stored uppercase internally.</p>
                </div>
                <div class="p-4 rounded-xl" style="background-color: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.3);">
                    <div class="font-mono text-xs mb-2" style="color: #EF4444;">HTTP 400 — Parameter 'location' is required for the geo endpoint</div>
                    <p class="text-xs" style="color: var(--text-secondary);">You called <code>endpoint=geo</code> without providing the <code>location</code> parameter.</p>
                </div>
                <div class="p-4 rounded-xl" style="background-color: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.3);">
                    <div class="font-mono text-xs mb-2" style="color: #EF4444;">HTTP 400 — Unknown endpoint</div>
                    <p class="text-xs" style="color: var(--text-secondary);">The <code>endpoint</code> value is not one of: search, top, topic, geo, breaking.</p>
                </div>
                <div class="p-4 rounded-xl" style="background-color: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.3);">
                    <div class="font-mono text-xs mb-2" style="color: #EF4444;">articleContent.error — Could not fetch article content</div>
                    <p class="text-xs" style="color: var(--text-secondary);">When <code>data_depth=true</code>, the article page returned too little content (likely paywalled, rate-limited, or a bot-challenge page). Other articles in the batch are unaffected.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Usage Tips -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-6" style="color: var(--text-primary);">Usage Tips</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-start">
                    <i data-feather="search" class="mr-3 flex-shrink-0" style="width: 18px; height: 18px; color: #0EA5E9; margin-top: 2px;"></i>
                    <div>
                        <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Advanced search operators</h4>
                        <p class="text-xs" style="color: var(--text-secondary);">Google News supports the same operators as Google Search: <code>when:1d</code> for last 24 h, <code>after:2025-01-01</code>, <code>site:bbc.com</code> to restrict to one source, <code>intitle:bitcoin</code> to match headline only. Combine them: <code>bitcoin+site:reuters.com+when:1d</code>.</p>
                    </div>
                </div>
            </div>
            <div class="p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-start">
                    <i data-feather="globe" class="mr-3 flex-shrink-0" style="width: 18px; height: 18px; color: #0EA5E9; margin-top: 2px;"></i>
                    <div>
                        <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Language &amp; region targeting</h4>
                        <p class="text-xs" style="color: var(--text-secondary);">Use <code>hl</code> (language), <code>gl</code> (country), and <code>ceid</code> together: <code>hl=fr&amp;gl=FR&amp;ceid=FR:fr</code> for French news, <code>hl=en-ZA&amp;gl=ZA&amp;ceid=ZA:en</code> for South Africa. <code>ceid</code> auto-derives from <code>hl</code>/<code>gl</code> if omitted.</p>
                    </div>
                </div>
            </div>
            <div class="p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-start">
                    <i data-feather="zap" class="mr-3 flex-shrink-0" style="width: 18px; height: 18px; color: #0EA5E9; margin-top: 2px;"></i>
                    <div>
                        <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Breaking news for market events</h4>
                        <p class="text-xs" style="color: var(--text-secondary);">Use <code>endpoint=breaking&amp;q=NFP</code> or <code>&amp;q=interest+rate+decision</code> to catch central bank announcements and high-impact economic events in near real time. The <code>ageMinutes</code> field lets you filter down to articles newer than a specific threshold.</p>
                    </div>
                </div>
            </div>
            <div class="p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-start">
                    <i data-feather="book-open" class="mr-3 flex-shrink-0" style="width: 18px; height: 18px; color: #0EA5E9; margin-top: 2px;"></i>
                    <div>
                        <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">data_depth for sentiment / NLP</h4>
                        <p class="text-xs" style="color: var(--text-secondary);">Set <code>data_depth=true</code> and pipe the <code>body</code> text into a language model or sentiment analyser. Use <code>num=3–5</code> to keep response times reasonable — each article fetch adds 1–3 s. The body is capped at 8 000 characters per article.</p>
                    </div>
                </div>
            </div>
            <div class="p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-start">
                    <i data-feather="map-pin" class="mr-3 flex-shrink-0" style="width: 18px; height: 18px; color: #0EA5E9; margin-top: 2px;"></i>
                    <div>
                        <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Geo for country-specific fundamentals</h4>
                        <p class="text-xs" style="color: var(--text-secondary);">Use <code>endpoint=geo&amp;location=Zimbabwe</code> to pull local economic headlines that don't surface in global top stories. Useful for emerging-market currency research or region-specific event monitoring.</p>
                    </div>
                </div>
            </div>
            <div class="p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-start">
                    <i data-feather="cpu" class="mr-3 flex-shrink-0" style="width: 18px; height: 18px; color: #0EA5E9; margin-top: 2px;"></i>
                    <div>
                        <h4 class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Wire into n8n / Markets Brain</h4>
                        <p class="text-xs" style="color: var(--text-secondary);">Drop this API into an n8n HTTP Request node or a Markets Brain context injection. Use breaking news as a real-time news feed input to your AI assistant — the structured JSON makes it easy to extract headlines, sources, and timestamps.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="p-6 rounded-2xl" style="background: linear-gradient(135deg, rgba(14, 165, 233, 0.1), rgba(99, 102, 241, 0.1)); border: 1px solid var(--border);">
        <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">Related APIs</h3>
        <div class="flex flex-wrap gap-3">
            <a href="/news-api-guide" class="inline-flex items-center px-5 py-2.5 rounded-full text-sm font-medium" style="background: linear-gradient(135deg, #0EA5E9, #6366F1); color: white;">
                <i data-feather="rss" class="mr-2" style="width: 16px; height: 16px;"></i>
                Economic News API
            </a>
            <a href="/markets-brain-api-guide" class="inline-flex items-center px-5 py-2.5 rounded-full text-sm font-medium" style="background-color: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border);">
                <i data-feather="cpu" class="mr-2" style="width: 16px; height: 16px;"></i>
                Markets Brain API
            </a>
            <a href="/market-data-api-guide" class="inline-flex items-center px-5 py-2.5 rounded-full text-sm font-medium" style="background-color: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border);">
                <i data-feather="bar-chart-2" class="mr-2" style="width: 16px; height: 16px;"></i>
                Market Data API
            </a>
            <a href="/settings" class="inline-flex items-center px-5 py-2.5 rounded-full text-sm font-medium" style="background-color: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border);">
                <i data-feather="settings" class="mr-2" style="width: 16px; height: 16px;"></i>
                Configure API Key
            </a>
        </div>
    </div>

</div>

<script>
function escHtml(s) {
    return String(s == null ? '' : s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function testNewsAPI(card, url) {
    if (!card) return;
    const responseDiv = card.querySelector('.test-response');
    if (!responseDiv) return;

    responseDiv.style.display = 'block';
    responseDiv.innerHTML = '<div style="padding:12px;background-color:var(--bg-secondary);border-radius:8px;color:var(--text-secondary);"><i data-feather="loader" style="width:14px;height:14px;display:inline;animation:spin 1s linear infinite;"></i> Fetching news...</div>';
    feather.replace();

    fetch(url)
        .then(r => r.json())
        .then(data => {
            if (data.status === 'error') {
                responseDiv.innerHTML = '<div style="padding:12px;background-color:rgba(239,68,68,0.1);border-radius:8px;border:1px solid #EF4444;color:#EF4444;"><strong><i data-feather="alert-triangle" style="width:14px;height:14px;display:inline;"></i> ' + escHtml(data.error) + '</strong></div>';
                feather.replace();
                return;
            }

            const count = data.count || 0;
            let html = '<div style="padding:12px;background-color:var(--bg-primary);border-radius:8px;border:1px solid var(--border);">';
            html += '<div style="font-size:0.8rem;font-weight:600;margin-bottom:10px;color:var(--text-secondary);">' + count + ' results</div>';

            if (data.items && data.items.length > 0) {
                data.items.forEach(function(item, i) {
                    html += '<div style="margin-bottom:8px;padding:8px;border-radius:6px;background:var(--bg-secondary);border:1px solid var(--border);">';
                    html += '<div style="font-size:0.8rem;font-weight:600;color:var(--text-primary);margin-bottom:4px;">' + (i+1) + '. ' + escHtml(item.title || '—') + '</div>';
                    html += '<div style="font-size:0.7rem;color:var(--text-secondary);">';
                    if (item.source) html += '<span style="margin-right:10px;"><strong>' + escHtml(item.source) + '</strong></span>';
                    if (item.pubDate) html += '<span>' + escHtml(item.pubDate) + '</span>';
                    if (item.ageMinutes !== undefined) html += ' <span style="color:#EF4444;">• ' + item.ageMinutes + ' min ago</span>';
                    html += '</div>';
                    if (item.description) html += '<div style="font-size:0.7rem;color:var(--text-secondary);margin-top:4px;">' + escHtml(item.description.substring(0, 160)) + '...</div>';
                    if (item.articleContent && item.articleContent.body) {
                        html += '<div style="font-size:0.7rem;color:#0EA5E9;margin-top:4px;"><i data-feather="file-text" style="width:11px;height:11px;display:inline;"></i> ' + item.articleContent.wordCount + ' words extracted</div>';
                    }
                    html += '</div>';
                });
            }

            html += '<details style="margin-top:8px;"><summary style="font-size:0.75rem;color:var(--text-secondary);cursor:pointer;">View raw JSON</summary>';
            html += '<pre style="margin-top:8px;max-height:300px;overflow-y:auto;color:var(--text-secondary);font-size:0.7rem;">' + escHtml(JSON.stringify(data, null, 2)) + '</pre></details>';
            html += '</div>';
            responseDiv.innerHTML = html;
            feather.replace();
        })
        .catch(function(err) {
            responseDiv.innerHTML = '<div style="padding:12px;background-color:rgba(239,68,68,0.1);border-radius:8px;border:1px solid #EF4444;color:#EF4444;"><i data-feather="alert-circle" style="width:14px;height:14px;display:inline;"></i> ' + escHtml(err.message) + '</div>';
            feather.replace();
        });
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() { alert('Copied to clipboard!'); });
}

feather.replace();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/app.php';
?>
