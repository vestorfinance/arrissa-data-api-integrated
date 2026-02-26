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

$title = 'URL API Guide';
$page  = 'url-api-guide';
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
                    URL API
                    <span class="section-badge ml-3" style="background-color: var(--success); color: var(--bg-primary);">v1.4</span>
                </h1>
                <p class="text-lg" style="color: var(--text-secondary);">Fetch and extract readable text content from any public or authenticated URL</p>
            </div>
        </div>

        <!-- Features Banner -->
        <div class="p-6 rounded-2xl gradient-bg" style="border: 1px solid var(--border);">
            <div class="flex items-start">
                <div class="flex-shrink-0 mr-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background-color: var(--success);">
                        <i data-feather="globe" style="width: 24px; height: 24px; color: white;"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">Key Features</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm" style="color: var(--text-secondary);">
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span><strong style="color: var(--text-primary);">Smart Content Extraction:</strong> Strips scripts/styles, returns clean readable text and title</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span><strong style="color: var(--text-primary);">Multi-Method Auth:</strong> Basic, Bearer, API key header, session cookie, custom headers</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span><strong style="color: var(--text-primary);">Auto Retry:</strong> Up to 3 attempts with intelligent error recovery</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span><strong style="color: var(--text-primary);">Redirect Following:</strong> Handles up to 10 HTTP redirects automatically</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Endpoint + API Key cards -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10">
        <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="flex items-center mb-4">
                <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3" style="background: rgba(79,70,229,0.15);">
                    <i data-feather="link" style="width: 20px; height: 20px; color: var(--accent);"></i>
                </div>
                <h3 class="text-lg font-semibold" style="color: var(--text-primary);">API Endpoint</h3>
            </div>
            <p class="text-xs uppercase tracking-wider mb-2" style="color: var(--text-secondary);">Route</p>
            <div class="p-4 rounded-xl api-code break-all" style="background-color: var(--input-bg); color: var(--accent); border: 1px solid var(--input-border);">
                <?php echo htmlspecialchars($baseUrl); ?>/api/url-api
            </div>
            <p class="text-xs mt-3" style="color: var(--text-secondary);">Method: <strong style="color:var(--text-primary);">GET</strong></p>
        </div>

        <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="flex items-center mb-4">
                <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3" style="background: rgba(16,185,129,0.15);">
                    <i data-feather="key" style="width: 20px; height: 20px; color: var(--success);"></i>
                </div>
                <h3 class="text-lg font-semibold" style="color: var(--text-primary);">Your API Key</h3>
            </div>
            <p class="text-xs uppercase tracking-wider mb-2" style="color: var(--text-secondary);">Include as <code style="color:var(--accent)">?api_key=</code></p>
            <div class="p-4 rounded-xl api-code break-all" style="background-color: var(--input-bg); color: var(--success); border: 1px solid var(--input-border);">
                <?php echo htmlspecialchars($apiKey ?: '(not set — configure in Settings)'); ?>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Authentication -->
    <div class="mb-10">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, var(--accent), var(--success));">
                <i data-feather="lock" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Authentication</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Every request must include the system API key</p>
            </div>
        </div>
        <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <p class="text-sm mb-4" style="color: var(--text-secondary);">Pass <code style="color:var(--accent);">api_key</code> as a query parameter <em>or</em> as the <code style="color:var(--accent);">X-API-Key</code> header.</p>
            <pre class="p-4 rounded-xl overflow-x-auto api-code" style="background-color: var(--bg-primary); border: 1px solid var(--border); color: var(--text-secondary);"><?php echo htmlspecialchars($baseUrl); ?>/api/url-api?api_key=<?php echo htmlspecialchars($apiKey); ?>&url=https://example.com</pre>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Parameters -->
    <div class="mb-10">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, var(--accent), var(--success));">
                <i data-feather="sliders" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Parameters</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Query string parameters accepted by the API</p>
            </div>
        </div>

        <!-- Required -->
        <h3 class="text-base font-semibold mb-3" style="color: var(--text-primary);">Required</h3>
        <div class="rounded-2xl overflow-hidden mb-6" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="param-row grid grid-cols-12 gap-4 p-4 text-xs font-semibold uppercase tracking-wider" style="color: var(--text-secondary); border-bottom: 1px solid var(--border);">
                <div class="col-span-3">Parameter</div>
                <div class="col-span-2">Type</div>
                <div class="col-span-7">Description</div>
            </div>
            <div class="param-row grid grid-cols-12 gap-4 p-4 items-start">
                <div class="col-span-3"><code class="api-code" style="color:var(--accent);">api_key</code></div>
                <div class="col-span-2 text-xs" style="color:var(--text-secondary);">string</div>
                <div class="col-span-7 text-sm" style="color:var(--text-secondary);">Your system API key (authenticates with this server)</div>
            </div>
            <div class="param-row grid grid-cols-12 gap-4 p-4 items-start">
                <div class="col-span-3"><code class="api-code" style="color:var(--accent);">url</code></div>
                <div class="col-span-2 text-xs" style="color:var(--text-secondary);">string</div>
                <div class="col-span-7 text-sm" style="color:var(--text-secondary);">The full URL to fetch (http or https only)</div>
            </div>
        </div>

        <!-- Optional — target auth -->
        <h3 class="text-base font-semibold mb-3" style="color: var(--text-primary);">Optional — Target URL Authentication</h3>
        <p class="text-sm mb-3" style="color: var(--text-secondary);">Use these if the <em>target</em> URL itself requires authentication. They are forwarded to the remote server, not to this API.</p>
        <div class="rounded-2xl overflow-hidden mb-6" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="param-row grid grid-cols-12 gap-4 p-4 text-xs font-semibold uppercase tracking-wider" style="color: var(--text-secondary); border-bottom: 1px solid var(--border);">
                <div class="col-span-3">Parameter</div>
                <div class="col-span-2">Type</div>
                <div class="col-span-7">Description</div>
            </div>
            <?php
            $optParams = [
                ['auth_user',       'string', 'Username for Basic HTTP authentication to the target URL'],
                ['auth_pass',       'string', 'Password for Basic HTTP authentication to the target URL'],
                ['bearer_token',    'string', 'Bearer token — sends Authorization: Bearer {token} header to the target URL'],
                ['target_key',      'string', 'API key to send to the target URL (header name set by api_key_name)'],
                ['api_key_name',    'string', 'Header name for target_key (default: X-API-Key)'],
                ['session_cookie',  'string', 'Raw cookie string to send to the target URL, e.g. session_id=abc123'],
                ['custom_headers',  'JSON',   'JSON object of arbitrary headers to forward to the target URL, e.g. {"Authorization":"Bearer tok"}'],
                ['debug',           '0|1',   'Set to 1 to include extended debug info in the response'],
            ];
            foreach ($optParams as $p): ?>
            <div class="param-row grid grid-cols-12 gap-4 p-4 items-start">
                <div class="col-span-3"><code class="api-code" style="color:var(--accent);"><?php echo $p[0]; ?></code></div>
                <div class="col-span-2 text-xs" style="color:var(--text-secondary);"><?php echo $p[1]; ?></div>
                <div class="col-span-7 text-sm" style="color:var(--text-secondary);"><?php echo $p[2]; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Examples -->
    <div class="mb-10">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, var(--accent), var(--success));">
                <i data-feather="code" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Example Requests</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Copy and use these directly</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Basic -->
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-center mb-4">
                    <i data-feather="globe" class="mr-3" style="width:18px;height:18px;color:var(--accent);"></i>
                    <h4 class="font-semibold" style="color:var(--text-primary);">Basic — Public URL</h4>
                </div>
                <pre class="p-4 rounded-xl overflow-x-auto api-code" style="background-color: var(--bg-primary); border: 1px solid var(--border); color: var(--text-secondary); white-space: pre-wrap; word-break: break-all;"><?php echo htmlspecialchars($baseUrl); ?>/api/url-api
  ?api_key=<?php echo htmlspecialchars($apiKey); ?>

  &url=https://example.com</pre>
            </div>

            <!-- Bearer Token -->
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-center mb-4">
                    <i data-feather="shield" class="mr-3" style="width:18px;height:18px;color:var(--accent);"></i>
                    <h4 class="font-semibold" style="color:var(--text-primary);">Bearer Token Auth (target site)</h4>
                </div>
                <pre class="p-4 rounded-xl overflow-x-auto api-code" style="background-color: var(--bg-primary); border: 1px solid var(--border); color: var(--text-secondary); white-space: pre-wrap; word-break: break-all;"><?php echo htmlspecialchars($baseUrl); ?>/api/url-api
  ?api_key=<?php echo htmlspecialchars($apiKey); ?>

  &url=https://api.example.com/data
  &bearer_token=your_target_token</pre>
            </div>

            <!-- Basic HTTP Auth -->
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-center mb-4">
                    <i data-feather="user-check" class="mr-3" style="width:18px;height:18px;color:var(--accent);"></i>
                    <h4 class="font-semibold" style="color:var(--text-primary);">Basic HTTP Auth (target site)</h4>
                </div>
                <pre class="p-4 rounded-xl overflow-x-auto api-code" style="background-color: var(--bg-primary); border: 1px solid var(--border); color: var(--text-secondary); white-space: pre-wrap; word-break: break-all;"><?php echo htmlspecialchars($baseUrl); ?>/api/url-api
  ?api_key=<?php echo htmlspecialchars($apiKey); ?>

  &url=https://protected.example.com/page
  &auth_user=myuser
  &auth_pass=mypassword</pre>
            </div>

            <!-- Custom Headers -->
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-center mb-4">
                    <i data-feather="settings" class="mr-3" style="width:18px;height:18px;color:var(--accent);"></i>
                    <h4 class="font-semibold" style="color:var(--text-primary);">Custom Headers (target site)</h4>
                </div>
                <pre class="p-4 rounded-xl overflow-x-auto api-code" style="background-color: var(--bg-primary); border: 1px solid var(--border); color: var(--text-secondary); white-space: pre-wrap; word-break: break-all;"><?php echo htmlspecialchars($baseUrl); ?>/api/url-api
  ?api_key=<?php echo htmlspecialchars($apiKey); ?>

  &url=https://api.example.com/feed
  &custom_headers={"X-Token":"abc","X-App":"myapp"}</pre>
            </div>

        </div>
    </div>

    <div class="divider"></div>

    <!-- Response -->
    <div class="mb-10">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, var(--accent), var(--success));">
                <i data-feather="package" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Response Format</h2>
                <p class="text-sm" style="color: var(--text-secondary);">JSON — always Content-Type: application/json</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Success -->
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-center mb-4">
                    <span class="section-badge mr-3" style="background-color: var(--success); color: var(--bg-primary);">200 Success</span>
                </div>
                <pre class="p-4 rounded-xl overflow-x-auto api-code" style="background-color: var(--bg-primary); border: 1px solid var(--border); color: var(--text-secondary);">{
  "success": true,
  "title": "Page Title Here",
  "content": "Extracted readable text...",
  "http_status": 200,
  "url": "https://example.com",
  "effective_url": "https://example.com/",
  "content_length": 1842,
  "attempts": 1
}</pre>
            </div>

            <!-- Error -->
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <div class="flex items-center mb-4">
                    <span class="section-badge mr-3" style="background-color: rgba(239,68,68,0.2); color: #ef4444;">4xx / 5xx Error</span>
                </div>
                <pre class="p-4 rounded-xl overflow-x-auto api-code" style="background-color: var(--bg-primary); border: 1px solid var(--border); color: var(--text-secondary);">{
  "error": "HTTP 401 Unauthorized",
  "debug": {
    "attempts": 3,
    "suggestions": [
      "Authentication required.",
      "• Basic Auth: add &auth_user=... &auth_pass=...",
      "• Bearer Token: add &bearer_token=..."
    ]
  }
}</pre>
                <div class="mt-4 highlight-box">
                    <p class="text-xs" style="color:var(--text-secondary);"><strong style="color:var(--text-primary);">401 from this server</strong> means your <code style="color:var(--accent)">api_key</code> is wrong or missing.<br>
                    <strong style="color:var(--text-primary);">401 in the error body</strong> means the <em>target</em> URL requires authentication.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Live Tester -->
    <div class="mb-10">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, var(--accent), var(--success));">
                <i data-feather="play-circle" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Live Tester</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Try the API directly from this page</p>
            </div>
        </div>

        <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                <div class="md:col-span-3">
                    <label class="text-xs font-semibold mb-2 block" style="color:var(--text-primary);">Target URL</label>
                    <input type="url" id="testUrl" placeholder="https://example.com" value="https://example.com"
                        class="w-full px-4 py-3 rounded-xl text-sm focus:outline-none"
                        style="background-color: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-primary);">
                </div>
                <div>
                    <label class="text-xs font-semibold mb-2 block" style="color:var(--text-primary);">Bearer Token (optional)</label>
                    <input type="text" id="testBearer" placeholder="Leave blank if none"
                        class="w-full px-4 py-3 rounded-xl text-sm focus:outline-none"
                        style="background-color: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-primary);">
                </div>
            </div>
            <div class="mb-4">
                <button id="testBtn" type="button" onclick="runTest()"
                    class="inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-semibold transition-all duration-200"
                    style="background: linear-gradient(135deg, var(--accent), #6366f1); color: white; border: none; cursor: pointer; box-shadow: 0 4px 14px rgba(79,70,229,0.35);"
                    onmouseover="this.style.boxShadow='0 6px 20px rgba(79,70,229,0.5)'; this.style.transform='translateY(-1px)';"
                    onmouseout="this.style.boxShadow='0 4px 14px rgba(79,70,229,0.35)'; this.style.transform='translateY(0)';">
                    <i data-feather="send" style="width:16px;height:16px;"></i>
                    Send Request
                </button>
            </div>
            <div id="testResult" class="hidden">
                <p class="text-xs font-semibold mb-2" style="color:var(--text-secondary);">Response:</p>
                <pre id="testOutput" class="p-4 rounded-xl overflow-x-auto api-code" style="background-color: var(--bg-primary); border: 1px solid var(--border); color: var(--text-secondary); max-height: 400px; overflow-y: auto; white-space: pre-wrap; word-break: break-all;"></pre>
            </div>
        </div>
    </div>

</div>

<script>
async function runTest() {
    const btn    = document.getElementById('testBtn');
    const result = document.getElementById('testResult');
    const output = document.getElementById('testOutput');
    const url    = document.getElementById('testUrl').value.trim();
    const bearer = document.getElementById('testBearer').value.trim();

    if (!url) { alert('Please enter a URL'); return; }

    btn.disabled = true;
    btn.style.opacity = '0.65';
    btn.style.cursor  = 'not-allowed';
    result.classList.remove('hidden');
    output.textContent = 'Fetching…';

    try {
        let params = new URLSearchParams({
            api_key: '<?php echo htmlspecialchars($apiKey, ENT_QUOTES); ?>',
            url: url
        });
        if (bearer) params.append('bearer_token', bearer);

        const resp = await fetch('/api/url-api?' + params.toString());
        const data = await resp.json();
        output.textContent = JSON.stringify(data, null, 2);
    } catch (e) {
        output.textContent = 'Error: ' + e.message;
    } finally {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor  = 'pointer';
        feather.replace();
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/app.php';
?>
