<?php
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Database.php';

$db = Database::getInstance();

$stmt = $db->query("SELECT value FROM settings WHERE key = 'app_base_url'");
$result = $stmt->fetch();
$baseUrl = $result ? rtrim($result['value'], '/') : 'http://localhost:8000';

$stmt = $db->query("SELECT value FROM settings WHERE key = 'api_key'");
$result = $stmt->fetch();
$apiKey = $result ? $result['value'] : '';

$title = 'MCP Server Guide';
$page  = 'mcp-guide';
ob_start();
?>

<style>
/* ── MCP Guide custom styles ── */
.mcp-config-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
@media (max-width: 640px) { .mcp-config-grid { grid-template-columns: 1fr; } }
.mcp-field-label { display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.05em; }
.mcp-text-field  { width: 100%; background-color: var(--bg-secondary); border: 1px solid var(--border); border-radius: 9999px; padding: 10px 16px; font-size: 0.875rem; color: var(--text-primary); font-family: inherit; outline: none; transition: border-color 0.15s; }
.mcp-text-field:focus { border-color: var(--accent); }
.mcp-env-tabs { display: flex; gap: 8px; flex-wrap: wrap; }
.mcp-env-tab  { padding: 9px 18px; border-radius: 9999px; border: 1px solid var(--border); background: transparent; color: var(--text-secondary); font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s; font-family: inherit; display: inline-flex; align-items: center; gap: 7px; }
.mcp-env-tab:hover  { border-color: var(--accent); color: var(--text-primary); }
.mcp-env-tab.active { background-color: var(--accent); border-color: var(--accent); color: #fff; }
.mcp-section { margin-bottom: 56px; }
.mcp-section-header { display: flex; align-items: center; gap: 14px; margin-bottom: 24px; }
.mcp-section-icon { width: 46px; height: 46px; border-radius: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.mcp-section-header h2 { font-size: 1.4rem; font-weight: 700; margin: 0 0 3px; color: var(--text-primary); }
.mcp-section-header p  { font-size: 0.85rem; color: var(--text-secondary); margin: 0; }
.mcp-steps { display: flex; flex-direction: column; gap: 16px; }
.mcp-step { background-color: var(--bg-secondary); border: 1px solid var(--border); border-radius: 18px; padding: 22px 24px; }
.mcp-step-num { display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 50%; background-color: var(--accent); color: #fff; font-size: 0.72rem; font-weight: 700; margin-right: 10px; flex-shrink: 0; }
.mcp-step-title { font-size: 0.95rem; font-weight: 700; color: var(--text-primary); display: flex; align-items: center; margin-bottom: 14px; }
.mcp-step-body { font-size: 0.875rem; color: var(--text-secondary); }
.mcp-step-body p { margin: 0 0 12px; }
.mcp-step-body p:last-child { margin-bottom: 0; }
.mcp-code-block { position: relative; background-color: #0a0a0a; border: 1px solid var(--border); border-radius: 12px; margin: 12px 0; overflow: hidden; }
.mcp-code-block pre { margin: 32px 0 0 0; padding: 14px 48px 14px 16px; font-family: 'JetBrains Mono','Fira Code','Courier New',monospace; font-size: 0.78rem; color: #e2e8f0; overflow-x: auto; white-space: pre; line-height: 1.7; }
.mcp-code-lang { position: absolute; top: 10px; left: 14px; font-size: 0.62rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.08em; }
.mcp-copy-btn { position: absolute; top: 7px; right: 9px; background-color: var(--bg-tertiary); border: 1px solid var(--border); color: var(--text-secondary); border-radius: 7px; padding: 4px 10px; font-size: 0.68rem; cursor: pointer; font-family: inherit; font-weight: 600; transition: all 0.15s; display: flex; align-items: center; gap: 4px; }
.mcp-copy-btn:hover  { background-color: var(--accent); color: #fff; border-color: var(--accent); }
.mcp-copy-btn.copied { background-color: var(--success); color: #fff; border-color: var(--success); }
.mcp-callout { border-radius: 12px; padding: 13px 15px; margin: 12px 0; display: flex; align-items: flex-start; gap: 11px; font-size: 0.83rem; }
.mcp-callout-info    { background-color: rgba(79,70,229,0.08); border: 1px solid rgba(79,70,229,0.25); color: var(--text-secondary); }
.mcp-callout-warn    { background-color: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.28); color: var(--text-secondary); }
.mcp-callout-success { background-color: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.25); color: var(--text-secondary); }
.mcp-callout-icon { flex-shrink: 0; margin-top: 1px; }
.mcp-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 9999px; font-size: 0.68rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.mcp-badge-accent  { background-color: rgba(79,70,229,0.12); color: var(--accent); border: 1px solid rgba(79,70,229,0.3); }
.mcp-badge-warn    { background-color: rgba(245,158,11,0.1); color: var(--warning); border: 1px solid rgba(245,158,11,0.28); }
.mcp-badge-success { background-color: rgba(16,185,129,0.1); color: var(--success); border: 1px solid rgba(16,185,129,0.28); }
.mcp-badge-mcp     { background: linear-gradient(90deg,rgba(79,70,229,0.12),rgba(16,185,129,0.12)); color: #10b981; border: 1px solid rgba(16,185,129,0.3); }
.mcp-divider { height: 1px; background: linear-gradient(90deg,transparent,var(--border),transparent); margin: 44px 0; }
.mcp-page-layout { display: flex; gap: 36px; align-items: flex-start; }
.mcp-toc { width: 200px; flex-shrink: 0; position: sticky; top: 72px; background-color: var(--bg-secondary); border: 1px solid var(--border); border-radius: 14px; padding: 18px; font-size: 0.78rem; }
.mcp-toc-title { font-weight: 700; color: var(--text-primary); margin-bottom: 10px; font-size: 0.8rem; }
.mcp-toc a { display: block; color: var(--text-secondary); padding: 4px 0; transition: color 0.15s; }
.mcp-toc a:hover { color: var(--text-primary); text-decoration: none; }
.mcp-toc a.sub { padding-left: 12px; font-size: 0.73rem; }
.mcp-main { flex: 1; min-width: 0; }
@media (max-width: 768px) { .mcp-toc { display: none; } .mcp-page-layout { display: block; } }
.mcp-tool-list { margin: 0 0 0 18px; padding: 0; list-style: none; line-height: 2.4; }
.mcp-tool-list li { display: flex; align-items: flex-start; gap: 8px; }
.mcp-tool-list li svg { flex-shrink: 0; margin-top: 4px; }
.mcp-tools-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 12px; margin-bottom: 40px; }
@media (max-width: 600px) { .mcp-tools-grid { grid-template-columns: 1fr 1fr; } }
.mcp-tool-pill { background-color: var(--bg-secondary); border: 1px solid var(--border); border-radius: 12px; padding: 12px 14px; display: flex; align-items: center; gap: 10px; }
.mcp-tool-pill-name  { font-size: 0.82rem; font-weight: 700; color: var(--text-primary); }
.mcp-tool-pill-count { font-size: 0.75rem; color: var(--text-secondary); margin-top: 1px; }
.mcp-apikey-badge { display: inline-flex; align-items: center; gap: 8px; background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); border-radius: 9999px; padding: 6px 14px; font-size: 0.8rem; color: var(--success); font-family: 'JetBrains Mono','Fira Code',monospace; font-weight: 600; word-break: break-all; }
.mcp-guide-card { background-color: var(--bg-secondary); border: 1px solid var(--border); border-radius: 18px; padding: 20px 22px; display: flex; align-items: center; gap: 18px; text-decoration: none; transition: border-color 0.2s, transform 0.15s; }
.mcp-guide-card:hover { border-color: var(--accent); transform: translateY(-2px); text-decoration: none; }
.mcp-guide-card-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.mcp-guide-card h3 { font-size: 0.95rem; font-weight: 700; color: var(--text-primary); margin: 0 0 3px; }
.mcp-guide-card p  { font-size: 0.8rem; color: var(--text-secondary); margin: 0; }
.mcp-hl-domain { color: #a5f3fc; }
.mcp-hl-key    { color: #86efac; }
code.mcp-inline { background-color: var(--bg-tertiary); padding: 2px 7px; border-radius: 5px; font-size: 0.82em; font-family: 'JetBrains Mono','Fira Code',monospace; color: #c4b5fd; }
.mcp-env-panel         { display: none; }
.mcp-env-panel.active  { display: block; }
</style>

<div class="p-6 md:p-8 max-w-[1200px] mx-auto">

    <!-- Hero -->
    <div class="mb-10">
        <div class="flex items-start justify-between mb-2 flex-wrap gap-3">
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#4f46e5,#10b981);display:flex;align-items:center;justify-content:center;font-size:0.65rem;font-weight:900;color:#fff;letter-spacing:-0.03em;flex-shrink:0;">MCP</div>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight" style="color:var(--text-primary);">MCP Server Guide</h1>
                        <p style="color:var(--text-secondary);font-size:0.875rem;margin:2px 0 0;">Connect 63 tools — market data, calendar, charts, orders — to Claude, Cursor, or any AI client.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- API key info box -->
        <div class="mcp-callout mcp-callout-success" style="margin-top:8px;">
            <i data-feather="check-circle" class="mcp-callout-icon" style="width:16px;height:16px;color:var(--success);"></i>
            <div>
                <p style="margin:0 0 6px;font-weight:600;color:var(--text-primary);">Your API key is automatically loaded from the database</p>
                <p style="margin:0;font-size:0.82rem;">The MCP server reads the same API key stored in your Arrissa Data installation — no configuration or copy-pasting required. Every code snippet on this page already contains your key.</p>
            </div>
        </div>

        <?php if ($apiKey): ?>
        <div style="margin-top:12px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <span style="font-size:0.78rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.05em;">Your API Key:</span>
            <span class="mcp-apikey-badge">
                <i data-feather="key" style="width:13px;height:13px;"></i>
                <?= htmlspecialchars($apiKey) ?>
            </span>
        </div>
        <?php endif; ?>
    </div>

    <!-- What you get -->
    <div class="mcp-tools-grid">
        <div class="mcp-tool-pill">
            <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#10b981,#059669);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i data-feather="trending-up" style="width:16px;height:16px;color:#fff;"></i>
            </div>
            <div>
                <div class="mcp-tool-pill-name">Market Data</div>
                <div class="mcp-tool-pill-count">8 tools</div>
            </div>
        </div>
        <div class="mcp-tool-pill">
            <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#4f46e5,#818cf8);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i data-feather="bar-chart-2" style="width:16px;height:16px;color:#fff;"></i>
            </div>
            <div>
                <div class="mcp-tool-pill-name">Chart Images</div>
                <div class="mcp-tool-pill-count">9 tools</div>
            </div>
        </div>
        <div class="mcp-tool-pill">
            <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#d97706,#f59e0b);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i data-feather="calendar" style="width:16px;height:16px;color:#fff;"></i>
            </div>
            <div>
                <div class="mcp-tool-pill-name">Economic Calendar</div>
                <div class="mcp-tool-pill-count">15 tools</div>
            </div>
        </div>
        <div class="mcp-tool-pill">
            <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#dc2626,#ef4444);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i data-feather="shopping-cart" style="width:16px;height:16px;color:#fff;"></i>
            </div>
            <div>
                <div class="mcp-tool-pill-name">Orders</div>
                <div class="mcp-tool-pill-count">20 tools</div>
            </div>
        </div>
        <div class="mcp-tool-pill">
            <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#7c3aed,#8b5cf6);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i data-feather="activity" style="width:16px;height:16px;color:#fff;"></i>
            </div>
            <div>
                <div class="mcp-tool-pill-name">Market Analysis</div>
                <div class="mcp-tool-pill-count">3 tools</div>
            </div>
        </div>
        <div class="mcp-tool-pill">
            <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#0891b2,#06b6d4);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i data-feather="globe" style="width:16px;height:16px;color:#fff;"></i>
            </div>
            <div>
                <div class="mcp-tool-pill-name">Web Content</div>
                <div class="mcp-tool-pill-count">8 tools</div>
            </div>
        </div>
    </div>

    <!-- Configurator (domain + port only — API key is from DB) -->
    <div style="background-color:var(--bg-secondary);border:1px solid var(--border);border-radius:20px;padding:26px 28px;margin-bottom:44px;">
        <p style="font-size:0.9rem;font-weight:700;margin:0 0 18px;color:var(--text-primary);">
            <i data-feather="settings" style="width:15px;height:15px;display:inline;vertical-align:-2px;margin-right:6px;color:var(--accent);"></i>
            Quick Configurator — personalise the commands below
        </p>
        <div class="mcp-config-grid">
            <div>
                <label class="mcp-field-label" for="mcpDomainInput">Your Domain / Hostname</label>
                <input class="mcp-text-field" type="text" id="mcpDomainInput" placeholder="e.g. arrissadata.com" oninput="mcpUpdateAll()">
            </div>
            <div>
                <label class="mcp-field-label" for="mcpPortInput">MCP Server Port</label>
                <input class="mcp-text-field" type="text" id="mcpPortInput" placeholder="3000" oninput="mcpUpdateAll()">
            </div>
        </div>
        <label class="mcp-field-label">Choose Your Environment</label>
        <div class="mcp-env-tabs">
            <button class="mcp-env-tab active" onclick="mcpSwitchEnv('ubuntu')">
                <i data-feather="server" style="width:13px;height:13px;"></i>Ubuntu + Caddy
            </button>
            <button class="mcp-env-tab" onclick="mcpSwitchEnv('xampp')">
                <i data-feather="monitor" style="width:13px;height:13px;"></i>Windows + XAMPP
            </button>
            <button class="mcp-env-tab" onclick="mcpSwitchEnv('wamp')">
                <i data-feather="home" style="width:13px;height:13px;"></i>Windows + WAMP
            </button>
        </div>
    </div>

    <!-- Page layout -->
    <div class="mcp-page-layout">
        <aside class="mcp-toc">
            <div class="mcp-toc-title">On This Page</div>
            <a href="#mcp-install">Installation</a>
            <a href="#mcp-service" class="sub">Run as Service</a>
            <a href="#mcp-caddy" class="sub">Caddy (Ubuntu)</a>
            <a href="#mcp-auth">Authentication</a>
            <a href="#mcp-claude">Claude Desktop</a>
            <a href="#mcp-cursor">Cursor</a>
            <a href="#mcp-test">Test the Server</a>
            <a href="#mcp-tools">All 63 Tools</a>
        </aside>

        <div class="mcp-main">

<!-- ══════════════════════════════════════ UBUNTU + CADDY -->
<div class="mcp-env-panel active" id="panel-ubuntu">

    <div class="mcp-section" id="mcp-install">
        <div class="mcp-section-header">
            <div class="mcp-section-icon" style="background:linear-gradient(135deg,#4f46e5,#6366f1);">
                <i data-feather="download" style="width:20px;height:20px;color:#fff;"></i>
            </div>
            <div>
                <h2>Install &amp; Build <span class="mcp-badge mcp-badge-accent">Ubuntu + Caddy</span></h2>
                <p>Node.js 18+ required. The MCP server lives inside the Arrissa repo — no separate clone needed.</p>
            </div>
        </div>
        <div class="mcp-steps">
            <div class="mcp-step">
                <div class="mcp-step-title"><span class="mcp-step-num">1</span>Install Node.js 20 LTS</div>
                <div class="mcp-step-body">
                    <div class="mcp-code-block">
                        <span class="mcp-code-lang">bash</span>
                        <button class="mcp-copy-btn" onclick="mcpCopyCode(this)"><i data-feather="copy" style="width:10px;height:10px;"></i> Copy</button>
                        <pre>curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node -v  # should print v20.x.x</pre>
                    </div>
                </div>
            </div>
            <div class="mcp-step">
                <div class="mcp-step-title"><span class="mcp-step-num">2</span>Install dependencies &amp; build</div>
                <div class="mcp-step-body">
                    <p>The <code class="mcp-inline">mcp-server/</code> folder is part of the main Arrissa repo.</p>
                    <div class="mcp-code-block">
                        <span class="mcp-code-lang">bash</span>
                        <button class="mcp-copy-btn" onclick="mcpCopyCode(this)"><i data-feather="copy" style="width:10px;height:10px;"></i> Copy</button>
                        <pre>cd /var/www/arrissa/mcp-server
npm install
npm run build</pre>
                    </div>
                    <div class="mcp-callout mcp-callout-success">
                        <i data-feather="check-circle" class="mcp-callout-icon" style="width:15px;height:15px;color:var(--success);"></i>
                        <span>On success <code class="mcp-inline">npm run build</code> exits cleanly and creates the <code class="mcp-inline">dist/</code> folder. The server reads the API key and base URL directly from the SQLite database — no <code class="mcp-inline">.env</code> file needed.</span>
                    </div>
                </div>
            </div>
            <div class="mcp-step" id="mcp-service">
                <div class="mcp-step-title"><span class="mcp-step-num">3</span>Run as a systemd service</div>
                <div class="mcp-step-body">
                    <p>Create a systemd unit so the MCP server starts on boot and restarts on crash:</p>
                    <div class="mcp-code-block">
                        <span class="mcp-code-lang">bash</span>
                        <button class="mcp-copy-btn" onclick="mcpCopyCode(this)"><i data-feather="copy" style="width:10px;height:10px;"></i> Copy</button>
                        <pre id="u-service-unit">sudo tee /etc/systemd/system/arrissa-mcp.service &lt;&lt;'EOF'
[Unit]
Description=Arrissa Data MCP Server
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/arrissa/mcp-server
ExecStart=/usr/bin/node dist/index.js
Restart=always
RestartSec=5
Environment=PORT=3000
Environment=NODE_ENV=production

[Install]
WantedBy=multi-user.target
EOF

sudo systemctl daemon-reload
sudo systemctl enable arrissa-mcp
sudo systemctl start arrissa-mcp
sudo systemctl status arrissa-mcp</pre>
                    </div>
                    <div class="mcp-callout mcp-callout-info">
                        <i data-feather="info" class="mcp-callout-icon" style="width:15px;height:15px;color:var(--accent);"></i>
                        <div>
                            <p style="margin:0 0 6px;"><code class="mcp-inline">systemctl status</code> opens in a pager — press <kbd style="background:var(--bg-tertiary);border:1px solid var(--border);border-radius:5px;padding:1px 7px;font-size:0.8em;font-family:monospace;color:var(--text-primary);">q</kbd> to exit back to the prompt.</p>
                            <p style="margin:0;">Follow logs live: <code class="mcp-inline">sudo journalctl -u arrissa-mcp -f</code> &nbsp;|&nbsp; Restart after a rebuild: <code class="mcp-inline">sudo systemctl restart arrissa-mcp</code></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mcp-step" id="mcp-caddy">
                <div class="mcp-step-title"><span class="mcp-step-num">4</span>Expose via Caddy</div>
                <div class="mcp-step-body">
                    <p>Add a route to your Caddyfile so the MCP endpoint is available at <code class="mcp-hl-domain mcp-inline" id="u-mcp-path">https://mcp.yourdomain.com/mcp</code>:</p>
                    <div class="mcp-code-block">
                        <span class="mcp-code-lang">caddyfile</span>
                        <button class="mcp-copy-btn" onclick="mcpCopyCode(this)"><i data-feather="copy" style="width:10px;height:10px;"></i> Copy</button>
                        <pre id="u-caddy-block">mcp.yourdomain.com {
    reverse_proxy localhost:3000
    encode gzip
}

# Alternatively, add inside your main domain block:
# handle /mcp* { reverse_proxy localhost:3000 }</pre>
                    </div>
                    <div class="mcp-code-block" style="margin-top:10px;">
                        <span class="mcp-code-lang">bash</span>
                        <button class="mcp-copy-btn" onclick="mcpCopyCode(this)"><i data-feather="copy" style="width:10px;height:10px;"></i> Copy</button>
                        <pre>sudo systemctl reload caddy</pre>
                    </div>
                    <p style="margin-top:12px;">Verify the server is up:</p>
                    <div class="mcp-code-block">
                        <span class="mcp-code-lang">bash</span>
                        <button class="mcp-copy-btn" onclick="mcpCopyCode(this)"><i data-feather="copy" style="width:10px;height:10px;"></i> Copy</button>
                        <pre id="u-health-check">curl https://mcp.yourdomain.com/health</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div><!-- /panel-ubuntu -->

<!-- ══════════════════════════════════════ WINDOWS XAMPP -->
<div class="mcp-env-panel" id="panel-xampp">

    <div class="mcp-section" id="mcp-install">
        <div class="mcp-section-header">
            <div class="mcp-section-icon" style="background:linear-gradient(135deg,#f59e0b,#ef4444);">
                <i data-feather="download" style="width:20px;height:20px;color:#fff;"></i>
            </div>
            <div>
                <h2>Install &amp; Build <span class="mcp-badge mcp-badge-warn">Windows + XAMPP</span></h2>
                <p>Node.js 18+ required. Run PowerShell as Administrator.</p>
            </div>
        </div>
        <div class="mcp-steps">
            <div class="mcp-step">
                <div class="mcp-step-title"><span class="mcp-step-num">1</span>Install Node.js 20 LTS</div>
                <div class="mcp-step-body">
                    <p>Download from <a href="https://nodejs.org" target="_blank" style="color:var(--accent);">nodejs.org</a> and install. Verify:</p>
                    <div class="mcp-code-block">
                        <span class="mcp-code-lang">powershell</span>
                        <button class="mcp-copy-btn" onclick="mcpCopyCode(this)"><i data-feather="copy" style="width:10px;height:10px;"></i> Copy</button>
                        <pre>node -v   # v20.x.x
npm -v</pre>
                    </div>
                </div>
            </div>
            <div class="mcp-step">
                <div class="mcp-step-title"><span class="mcp-step-num">2</span>Install dependencies &amp; build</div>
                <div class="mcp-step-body">
                    <div class="mcp-code-block">
                        <span class="mcp-code-lang">powershell</span>
                        <button class="mcp-copy-btn" onclick="mcpCopyCode(this)"><i data-feather="copy" style="width:10px;height:10px;"></i> Copy</button>
                        <pre>cd C:\xampp\htdocs\arrissa\mcp-server
npm install
npm run build</pre>
                    </div>
                    <div class="mcp-callout mcp-callout-success">
                        <i data-feather="check-circle" class="mcp-callout-icon" style="width:15px;height:15px;color:var(--success);"></i>
                        <span>The server reads the API key directly from the SQLite database — no <code class="mcp-inline">.env</code> file and no manual key entry required.</span>
                    </div>
                </div>
            </div>
            <div class="mcp-step" id="mcp-service">
                <div class="mcp-step-title"><span class="mcp-step-num">3</span>Run as a Windows Scheduled Task</div>
                <div class="mcp-step-body">
                    <p>Register a task that starts the MCP server at boot (run PowerShell <strong>as Administrator</strong>):</p>
                    <div class="mcp-code-block">
                        <span class="mcp-code-lang">powershell</span>
                        <button class="mcp-copy-btn" onclick="mcpCopyCode(this)"><i data-feather="copy" style="width:10px;height:10px;"></i> Copy</button>
                        <pre>$nodeExe = (Get-Command node).Source
$script  = "C:\xampp\htdocs\arrissa\mcp-server\dist\index.js"
$workDir = "C:\xampp\htdocs\arrissa\mcp-server"
$action  = New-ScheduledTaskAction -Execute $nodeExe -Argument $script -WorkingDirectory $workDir
$trigger = New-ScheduledTaskTrigger -AtStartup
$settings = New-ScheduledTaskSettingsSet -ExecutionTimeLimit 0 -RestartOnIdle $false
Register-ScheduledTask -TaskName "ArrissaMCPServer" -Action $action -Trigger $trigger -Settings $settings -RunLevel Highest -Force

# Start it now without rebooting
Start-ScheduledTask -TaskName "ArrissaMCPServer"</pre>
                    </div>
                    <div class="mcp-callout mcp-callout-info">
                        <i data-feather="info" class="mcp-callout-icon" style="width:15px;height:15px;color:var(--accent);"></i>
                        <span>To stop: <code class="mcp-inline">Stop-ScheduledTask -TaskName "ArrissaMCPServer"</code> &nbsp;|&nbsp; To remove: <code class="mcp-inline">Unregister-ScheduledTask -TaskName "ArrissaMCPServer" -Confirm:$false</code></span>
                    </div>
                </div>
            </div>
            <div class="mcp-step">
                <div class="mcp-step-title"><span class="mcp-step-num">4</span>Verify the server is running</div>
                <div class="mcp-step-body">
                    <div class="mcp-code-block">
                        <span class="mcp-code-lang">powershell</span>
                        <button class="mcp-copy-btn" onclick="mcpCopyCode(this)"><i data-feather="copy" style="width:10px;height:10px;"></i> Copy</button>
                        <pre>Invoke-RestMethod http://localhost:3000/health | ConvertTo-Json</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div><!-- /panel-xampp -->

<!-- ══════════════════════════════════════ WINDOWS WAMP -->
<div class="mcp-env-panel" id="panel-wamp">

    <div class="mcp-section" id="mcp-install">
        <div class="mcp-section-header">
            <div class="mcp-section-icon" style="background:linear-gradient(135deg,#10b981,#059669);">
                <i data-feather="download" style="width:20px;height:20px;color:#fff;"></i>
            </div>
            <div>
                <h2>Install &amp; Build <span class="mcp-badge mcp-badge-success">Windows + WAMP</span></h2>
                <p>Node.js 18+ required. Run in PowerShell.</p>
            </div>
        </div>
        <div class="mcp-steps">
            <div class="mcp-step">
                <div class="mcp-step-title"><span class="mcp-step-num">1</span>Install Node.js 20 LTS</div>
                <div class="mcp-step-body">
                    <p>Download from <a href="https://nodejs.org" target="_blank" style="color:var(--accent);">nodejs.org</a> and install. Verify:</p>
                    <div class="mcp-code-block">
                        <span class="mcp-code-lang">powershell</span>
                        <button class="mcp-copy-btn" onclick="mcpCopyCode(this)"><i data-feather="copy" style="width:10px;height:10px;"></i> Copy</button>
                        <pre>node -v   # v20.x.x
npm -v</pre>
                    </div>
                </div>
            </div>
            <div class="mcp-step">
                <div class="mcp-step-title"><span class="mcp-step-num">2</span>Install dependencies &amp; build</div>
                <div class="mcp-step-body">
                    <div class="mcp-code-block">
                        <span class="mcp-code-lang">powershell</span>
                        <button class="mcp-copy-btn" onclick="mcpCopyCode(this)"><i data-feather="copy" style="width:10px;height:10px;"></i> Copy</button>
                        <pre>cd C:\wamp64\www\mcp-server
npm install
npm run build</pre>
                    </div>
                    <div class="mcp-callout mcp-callout-success">
                        <i data-feather="check-circle" class="mcp-callout-icon" style="width:15px;height:15px;color:var(--success);"></i>
                        <span>The server reads the API key directly from the SQLite database — no <code class="mcp-inline">.env</code> file needed. For local dev you can also run <code class="mcp-inline">node dist/index.js</code> directly.</span>
                    </div>
                </div>
            </div>
            <div class="mcp-step" id="mcp-service">
                <div class="mcp-step-title"><span class="mcp-step-num">3</span>Run as a Windows Scheduled Task</div>
                <div class="mcp-step-body">
                    <p>Run PowerShell <strong>as Administrator</strong>:</p>
                    <div class="mcp-code-block">
                        <span class="mcp-code-lang">powershell</span>
                        <button class="mcp-copy-btn" onclick="mcpCopyCode(this)"><i data-feather="copy" style="width:10px;height:10px;"></i> Copy</button>
                        <pre>$nodeExe = (Get-Command node).Source
$script  = "C:\wamp64\www\mcp-server\dist\index.js"
$workDir = "C:\wamp64\www\mcp-server"
$action  = New-ScheduledTaskAction -Execute $nodeExe -Argument $script -WorkingDirectory $workDir
$trigger = New-ScheduledTaskTrigger -AtStartup
$settings = New-ScheduledTaskSettingsSet -ExecutionTimeLimit 0 -RestartOnIdle $false
Register-ScheduledTask -TaskName "ArrissaMCPServer" -Action $action -Trigger $trigger -Settings $settings -RunLevel Highest -Force

Start-ScheduledTask -TaskName "ArrissaMCPServer"</pre>
                    </div>
                </div>
            </div>
            <div class="mcp-step">
                <div class="mcp-step-title"><span class="mcp-step-num">4</span>Verify the server is running</div>
                <div class="mcp-step-body">
                    <div class="mcp-code-block">
                        <span class="mcp-code-lang">powershell</span>
                        <button class="mcp-copy-btn" onclick="mcpCopyCode(this)"><i data-feather="copy" style="width:10px;height:10px;"></i> Copy</button>
                        <pre>Invoke-RestMethod http://localhost:3000/health | ConvertTo-Json</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div><!-- /panel-wamp -->

<!-- ══════════════════════════════════════ AUTH -->
<div class="mcp-divider"></div>

<div class="mcp-section" id="mcp-auth">
    <div class="mcp-section-header">
        <div class="mcp-section-icon" style="background:linear-gradient(135deg,#ef4444,#f59e0b);">
            <i data-feather="key" style="width:20px;height:20px;color:#fff;"></i>
        </div>
        <div>
            <h2>Authentication</h2>
            <p>Every request to <code class="mcp-inline">/mcp</code> must include your API key as a header</p>
        </div>
    </div>
    <div class="mcp-steps">
        <div class="mcp-step">
            <div class="mcp-step-title"><span class="mcp-step-num" style="background:var(--danger);">!</span>Required header</div>
            <div class="mcp-step-body">
                <p>The MCP server reads your API key from the Arrissa database at startup. Every request to the <code class="mcp-inline">/mcp</code> endpoint must carry:</p>
                <div class="mcp-code-block">
                    <span class="mcp-code-lang">http</span>
                    <button class="mcp-copy-btn" onclick="mcpCopyCode(this)"><i data-feather="copy" style="width:10px;height:10px;"></i> Copy</button>
                    <pre>x-api-key: <span class="mcp-hl-key"><?= htmlspecialchars($apiKey ?: 'YOUR_API_KEY') ?></span></pre>
                </div>
                <p>Requests with a missing or wrong key receive <code class="mcp-inline">401 Unauthorized</code>. You do not need to set this key anywhere — the MCP server loads it automatically from the same database your Arrissa Data API uses.</p>
                <div class="mcp-callout mcp-callout-warn">
                    <i data-feather="alert-triangle" class="mcp-callout-icon" style="width:15px;height:15px;color:var(--warning);"></i>
                    <span>Keep your API key secret. Never commit it to version control or expose it client-side. Rotate it from the Settings page if it is ever leaked.</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mcp-divider"></div>

<!-- ══════════════════════════════════════ CLAUDE DESKTOP -->
<div class="mcp-section" id="mcp-claude">
    <div class="mcp-section-header">
        <div class="mcp-section-icon" style="background:linear-gradient(135deg,#d97706,#f59e0b);">
            <i data-feather="cpu" style="width:20px;height:20px;color:#fff;"></i>
        </div>
        <div>
            <h2>Connect Claude Desktop</h2>
            <p>Add the Arrissa MCP server to your Claude Desktop config</p>
        </div>
    </div>
    <div class="mcp-steps">
        <div class="mcp-step">
            <div class="mcp-step-title"><span class="mcp-step-num">1</span>Open your Claude Desktop config file</div>
            <div class="mcp-step-body">
                <p><strong style="color:var(--text-primary);">macOS / Linux:</strong> <code class="mcp-inline">~/.config/claude/claude_desktop_config.json</code></p>
                <p><strong style="color:var(--text-primary);">Windows:</strong> <code class="mcp-inline">%APPDATA%\Claude\claude_desktop_config.json</code></p>
                <p>Create the file if it does not exist. Then add or merge the <code class="mcp-inline">mcpServers</code> block below.</p>
            </div>
        </div>
        <div class="mcp-step">
            <div class="mcp-step-title"><span class="mcp-step-num">2</span>Remote server (Ubuntu + Caddy)</div>
            <div class="mcp-step-body">
                <div class="mcp-code-block">
                    <span class="mcp-code-lang">json</span>
                    <button class="mcp-copy-btn" onclick="mcpCopyCode(this)"><i data-feather="copy" style="width:10px;height:10px;"></i> Copy</button>
                    <pre id="claude-remote-config">{
  "mcpServers": {
    "arrissa-data": {
      "type": "http",
      "url": "https://mcp.yourdomain.com/mcp",
      "headers": {
        "x-api-key": "<?= htmlspecialchars($apiKey ?: 'YOUR_API_KEY') ?>"
      }
    }
  }
}</pre>
                </div>
                <div class="mcp-callout mcp-callout-info">
                    <i data-feather="info" class="mcp-callout-icon" style="width:15px;height:15px;color:var(--accent);"></i>
                    <span>If you used a path route instead of a subdomain, the URL becomes <code class="mcp-inline" id="claude-path-note">https://yourdomain.com/mcp</code></span>
                </div>
            </div>
        </div>
        <div class="mcp-step">
            <div class="mcp-step-title"><span class="mcp-step-num">3</span>Local server (Windows localhost)</div>
            <div class="mcp-step-body">
                <div class="mcp-code-block">
                    <span class="mcp-code-lang">json</span>
                    <button class="mcp-copy-btn" onclick="mcpCopyCode(this)"><i data-feather="copy" style="width:10px;height:10px;"></i> Copy</button>
                    <pre id="claude-local-config">{
  "mcpServers": {
    "arrissa-data": {
      "type": "http",
      "url": "http://localhost:3000/mcp",
      "headers": {
        "x-api-key": "<?= htmlspecialchars($apiKey ?: 'YOUR_API_KEY') ?>"
      }
    }
  }
}</pre>
                </div>
            </div>
        </div>
        <div class="mcp-step">
            <div class="mcp-step-title"><span class="mcp-step-num">4</span>Restart Claude Desktop</div>
            <div class="mcp-step-body">
                <p>Quit and relaunch Claude Desktop. Open a new conversation — you should see <strong style="color:var(--text-primary);">arrissa-data</strong> in the tools panel (hammer icon).</p>
                <div class="mcp-callout mcp-callout-success">
                    <i data-feather="check-circle" class="mcp-callout-icon" style="width:15px;height:15px;color:var(--success);"></i>
                    <span>Try asking: <em>"Get me the latest USD economic events from the Arrissa calendar"</em> — Claude will invoke <code class="mcp-inline">get_latest_economic_events</code> automatically.</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mcp-divider"></div>

<!-- ══════════════════════════════════════ CURSOR -->
<div class="mcp-section" id="mcp-cursor">
    <div class="mcp-section-header">
        <div class="mcp-section-icon" style="background:linear-gradient(135deg,#1d4ed8,#3b82f6);">
            <i data-feather="code" style="width:20px;height:20px;color:#fff;"></i>
        </div>
        <div>
            <h2>Connect Cursor</h2>
            <p>Use Arrissa tools directly in the Cursor AI editor</p>
        </div>
    </div>
    <div class="mcp-steps">
        <div class="mcp-step">
            <div class="mcp-step-title"><span class="mcp-step-num">1</span>Open Cursor MCP settings</div>
            <div class="mcp-step-body">
                <p>In Cursor go to <strong style="color:var(--text-primary);">Settings → MCP</strong> or edit <code class="mcp-inline">~/.cursor/mcp.json</code> directly.</p>
            </div>
        </div>
        <div class="mcp-step">
            <div class="mcp-step-title"><span class="mcp-step-num">2</span>Add the Arrissa server</div>
            <div class="mcp-step-body">
                <div class="mcp-code-block">
                    <span class="mcp-code-lang">json</span>
                    <button class="mcp-copy-btn" onclick="mcpCopyCode(this)"><i data-feather="copy" style="width:10px;height:10px;"></i> Copy</button>
                    <pre id="cursor-config">{
  "mcpServers": {
    "arrissa-data": {
      "url": "https://mcp.yourdomain.com/mcp",
      "headers": {
        "x-api-key": "<?= htmlspecialchars($apiKey ?: 'YOUR_API_KEY') ?>"
      }
    }
  }
}</pre>
                </div>
                <p style="margin-top:10px;">For local use replace the URL with <code class="mcp-inline">http://localhost:3000/mcp</code>.</p>
            </div>
        </div>
        <div class="mcp-step">
            <div class="mcp-step-title"><span class="mcp-step-num">3</span>Reload &amp; verify</div>
            <div class="mcp-step-body">
                <p>Reload the Cursor window. In the chat panel (<code class="mcp-inline">Ctrl+L</code>) you should see the Arrissa tools available. Test with: <em>"What economic events are scheduled for today?"</em></p>
            </div>
        </div>
    </div>
</div>

<div class="mcp-divider"></div>

<!-- ══════════════════════════════════════ TEST -->
<div class="mcp-section" id="mcp-test">
    <div class="mcp-section-header">
        <div class="mcp-section-icon" style="background:linear-gradient(135deg,#10b981,#059669);">
            <i data-feather="activity" style="width:20px;height:20px;color:#fff;"></i>
        </div>
        <div>
            <h2>Test the Server</h2>
            <p>Verify auth and tool discovery work correctly</p>
        </div>
    </div>
    <div class="mcp-steps">
        <div class="mcp-step">
            <div class="mcp-step-title"><span class="mcp-step-num">1</span>Health check (no auth required)</div>
            <div class="mcp-step-body">
                <div class="mcp-code-block">
                    <span class="mcp-code-lang">bash</span>
                    <button class="mcp-copy-btn" onclick="mcpCopyCode(this)"><i data-feather="copy" style="width:10px;height:10px;"></i> Copy</button>
                    <pre id="test-health">curl https://mcp.yourdomain.com/health</pre>
                </div>
                <p>Expected response includes <code class="mcp-inline">"status": "ok"</code> and <code class="mcp-inline">"tools": 63</code>.</p>
            </div>
        </div>
        <div class="mcp-step">
            <div class="mcp-step-title"><span class="mcp-step-num">2</span>MCP initialize — with auth header</div>
            <div class="mcp-step-body">
                <div class="mcp-code-block">
                    <span class="mcp-code-lang">bash</span>
                    <button class="mcp-copy-btn" onclick="mcpCopyCode(this)"><i data-feather="copy" style="width:10px;height:10px;"></i> Copy</button>
                    <pre id="test-init">curl -s -X POST https://mcp.yourdomain.com/mcp \
  -H "Content-Type: application/json" \
  -H "Accept: application/json, text/event-stream" \
  -H "x-api-key: <?= htmlspecialchars($apiKey ?: 'YOUR_API_KEY') ?>" \
  -d '{
    "jsonrpc": "2.0",
    "id": 1,
    "method": "initialize",
    "params": {
      "protocolVersion": "2025-03-26",
      "capabilities": {},
      "clientInfo": { "name": "test", "version": "1.0" }
    }
  }' | jq .</pre>
                </div>
            </div>
        </div>
        <div class="mcp-step">
            <div class="mcp-step-title"><span class="mcp-step-num">3</span>Confirm 401 without the header</div>
            <div class="mcp-step-body">
                <div class="mcp-code-block">
                    <span class="mcp-code-lang">bash</span>
                    <button class="mcp-copy-btn" onclick="mcpCopyCode(this)"><i data-feather="copy" style="width:10px;height:10px;"></i> Copy</button>
                    <pre id="test-unauth">curl -s -X POST https://mcp.yourdomain.com/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-03-26","capabilities":{},"clientInfo":{"name":"test","version":"1.0"}}}'
# Should return: {"error":"Unauthorized","message":"Provide your Arrissa API key in the x-api-key request header"}</pre>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mcp-divider"></div>

<!-- ══════════════════════════════════════ TOOLS LIST -->
<div class="mcp-section" id="mcp-tools">
    <div class="mcp-section-header">
        <div class="mcp-section-icon" style="background:linear-gradient(135deg,#7c3aed,#a78bfa);">
            <i data-feather="tool" style="width:20px;height:20px;color:#fff;"></i>
        </div>
        <div>
            <h2>All 63 Tools</h2>
            <p>Grouped by category — every tool is available to any connected AI client</p>
        </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:16px;">

        <div class="mcp-step">
            <div class="mcp-step-title" style="margin-bottom:14px;">
                <span class="mcp-step-num" style="background:linear-gradient(135deg,#10b981,#059669);">
                    <i data-feather="trending-up" style="width:12px;height:12px;"></i>
                </span>
                Market Data — 8 tools
            </div>
            <div class="mcp-step-body">
                <ul class="mcp-tool-list">
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_market_data</code> — OHLCV candles for a symbol &amp; timeframe</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_latest_candle</code> — Most recent candle only</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_multiple_symbols</code> — Batch candle fetch for multiple symbols</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_symbol_info</code> — Broker symbol specification (digits, lots, spread)</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_multiple_symbol_info</code> — Batch symbol info</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_tma_cg_data</code> — TMA + CG indicator values</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_quarters_theory_data</code> — Quarters Theory levels</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_available_symbols</code> — List symbols with data in the DB</span></li>
                </ul>
            </div>
        </div>

        <div class="mcp-step">
            <div class="mcp-step-title" style="margin-bottom:14px;">
                <span class="mcp-step-num" style="background:linear-gradient(135deg,#4f46e5,#818cf8);">
                    <i data-feather="bar-chart-2" style="width:12px;height:12px;"></i>
                </span>
                Chart Images — 9 tools
            </div>
            <div class="mcp-step-body">
                <ul class="mcp-tool-list">
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_chart_image</code> — Standard candlestick chart PNG</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_chart_image_with_indicators</code> — Chart with overlay indicators</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_multi_timeframe_chart</code> — Side-by-side multi-TF chart</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_chart_stream_url</code> — Live streaming chart URL</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">list_chart_streams</code> — All active chart stream URLs</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_chart_stream_image</code> — Snapshot of a stream</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">create_chart_stream</code> — Create a new stream URL</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">delete_chart_stream</code> — Remove a stream</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_chart_comparison</code> — Compare two symbols on one chart</span></li>
                </ul>
            </div>
        </div>

        <div class="mcp-step">
            <div class="mcp-step-title" style="margin-bottom:14px;">
                <span class="mcp-step-num" style="background:linear-gradient(135deg,#d97706,#f59e0b);">
                    <i data-feather="calendar" style="width:12px;height:12px;"></i>
                </span>
                Economic Calendar — 15 tools
            </div>
            <div class="mcp-step-body">
                <ul class="mcp-tool-list">
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_economic_events</code> — Events by date range / period</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_today_events</code> — Today's events</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_upcoming_events</code> — Next N upcoming events</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_latest_economic_events</code> — Most recent occurrence of every event type</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_high_impact_events</code> — High-impact only</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_events_by_currency</code> — Events for one or more currencies</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_this_week_events</code> — Current week events</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_next_week_events</code> — Next week's scheduled events</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_event_by_id</code> — Single event detail by ID</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">search_events</code> — Free-text search over event names</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_nfp_history</code> — Full Non-Farm Payrolls history</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_cpi_history</code> — CPI release history</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_fomc_history</code> — FOMC rate decision history</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_similar_scenes</code> — Find past events matching current conditions</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_event_statistics</code> — Aggregated stats for an event type</span></li>
                </ul>
            </div>
        </div>

        <div class="mcp-step">
            <div class="mcp-step-title" style="margin-bottom:14px;">
                <span class="mcp-step-num" style="background:linear-gradient(135deg,#dc2626,#ef4444);">
                    <i data-feather="shopping-cart" style="width:12px;height:12px;"></i>
                </span>
                Orders — 20 tools
            </div>
            <div class="mcp-step-body">
                <ul class="mcp-tool-list">
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_open_orders</code> / <code class="mcp-inline">get_pending_orders</code> / <code class="mcp-inline">get_closed_orders</code></span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_order_by_ticket</code> / <code class="mcp-inline">get_orders_by_symbol</code></span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_account_summary</code> / <code class="mcp-inline">get_account_balance</code> / <code class="mcp-inline">get_equity</code></span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_daily_pnl</code> / <code class="mcp-inline">get_weekly_pnl</code> / <code class="mcp-inline">get_monthly_pnl</code></span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_win_rate</code> / <code class="mcp-inline">get_drawdown</code> / <code class="mcp-inline">get_profit_factor</code></span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_trade_history</code> / <code class="mcp-inline">get_best_trades</code> / <code class="mcp-inline">get_worst_trades</code></span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_symbol_performance</code> / <code class="mcp-inline">get_trading_hours_analysis</code></span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_risk_metrics</code> / <code class="mcp-inline">get_position_sizing</code></span></li>
                </ul>
            </div>
        </div>

        <div class="mcp-step">
            <div class="mcp-step-title" style="margin-bottom:14px;">
                <span class="mcp-step-num" style="background:linear-gradient(135deg,#7c3aed,#8b5cf6);">
                    <i data-feather="activity" style="width:12px;height:12px;"></i>
                </span>
                Market Analysis — 3 tools
            </div>
            <div class="mcp-step-body">
                <ul class="mcp-tool-list">
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">analyze_market_conditions</code> — Trend, volatility, regime summary</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_correlation_matrix</code> — Cross-asset correlation</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_volatility_analysis</code> — ATR, historical volatility, range stats</span></li>
                </ul>
            </div>
        </div>

        <div class="mcp-step">
            <div class="mcp-step-title" style="margin-bottom:14px;">
                <span class="mcp-step-num" style="background:linear-gradient(135deg,#0891b2,#06b6d4);">
                    <i data-feather="globe" style="width:12px;height:12px;"></i>
                </span>
                Web Content — 8 tools
            </div>
            <div class="mcp-step-body">
                <ul class="mcp-tool-list">
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">scrape_url</code> — Fetch and clean text from any URL</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">scrape_multiple_urls</code> — Batch scrape</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_page_title</code> / <code class="mcp-inline">get_page_links</code></span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">extract_article_content</code> — Article text extraction</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">search_web_content</code> — Keyword search within a scraped page</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_source_name</code> — Derive publisher name from URL</span></li>
                    <li><i data-feather="chevron-right" style="width:13px;height:13px;color:var(--accent);flex-shrink:0;margin-top:4px;"></i><span><code class="mcp-inline">get_financial_news</code> — News from Reuters / Yahoo Finance</span></li>
                </ul>
            </div>
        </div>

    </div>
</div>

        </div><!-- /mcp-main -->
    </div><!-- /mcp-page-layout -->

</div><!-- /container -->

<script>
(function() {

function mcpGetDomain() { return (document.getElementById('mcpDomainInput').value.trim() || 'yourdomain.com'); }
function mcpGetPort()   { return (document.getElementById('mcpPortInput').value.trim()   || '3000'); }

function mcpUpdateAll() {
    const domain = mcpGetDomain();
    const port   = mcpGetPort();
    const apiKey = <?= json_encode($apiKey ?: 'YOUR_API_KEY') ?>;

    // Caddy block
    mcpSetCode('u-caddy-block', el => el.replace(/mcp\.yourdomain\.com/g, `mcp.${domain}`).replace(/yourdomain\.com/g, domain).replace(/localhost:3000/g, `localhost:${port}`));
    // MCP path hint
    var pathEl = document.getElementById('u-mcp-path');
    if (pathEl) pathEl.textContent = `https://mcp.${domain}/mcp`;

    // Health / test
    mcpSetCode('u-health-check', el => el.replace(/mcp\.yourdomain\.com/g, `mcp.${domain}`));
    mcpSetCode('test-health',    el => el.replace(/mcp\.yourdomain\.com/g, `mcp.${domain}`));
    mcpSetCode('test-init',      el => el.replace(/mcp\.yourdomain\.com/g, `mcp.${domain}`));
    mcpSetCode('test-unauth',    el => el.replace(/mcp\.yourdomain\.com/g, `mcp.${domain}`));

    // Claude
    mcpSetCode('claude-remote-config', el => el.replace(/mcp\.yourdomain\.com/g, `mcp.${domain}`));
    mcpSetCode('claude-local-config',  el => el.replace(/localhost:3000/g, `localhost:${port}`));
    var pathNote = document.getElementById('claude-path-note');
    if (pathNote) pathNote.textContent = `https://${domain}/mcp`;

    // Cursor
    mcpSetCode('cursor-config', el => el.replace(/mcp\.yourdomain\.com/g, `mcp.${domain}`));

    // systemd port
    mcpSetCode('u-service-unit', el => el.replace(/PORT=3000/g, `PORT=${port}`));
}

function mcpSetCode(id, fn) {
    var el = document.getElementById(id);
    if (!el) return;
    if (!el.dataset.orig) el.dataset.orig = el.textContent;
    el.textContent = fn(el.dataset.orig);
}

function mcpSwitchEnv(env) {
    document.querySelectorAll('.mcp-env-tab').forEach(function(t) { t.classList.remove('active'); });
    document.querySelectorAll('.mcp-env-panel').forEach(function(p) { p.classList.remove('active'); });
    var tab = document.querySelector('.mcp-env-tab[onclick="mcpSwitchEnv(\'' + env + '\')"]');
    if (tab) tab.classList.add('active');
    var panel = document.getElementById('panel-' + env);
    if (panel) panel.classList.add('active');
}

function mcpCopyCode(btn) {
    var pre = btn.closest('.mcp-code-block').querySelector('pre');
    navigator.clipboard.writeText(pre.textContent.trim()).then(function() {
        btn.classList.add('copied');
        btn.innerHTML = '<i data-feather="check" style="width:10px;height:10px;"></i> Copied!';
        if (window.feather) feather.replace();
        setTimeout(function() {
            btn.classList.remove('copied');
            btn.innerHTML = '<i data-feather="copy" style="width:10px;height:10px;"></i> Copy';
            if (window.feather) feather.replace();
        }, 2000);
    });
}

// Expose globally
window.mcpUpdateAll  = mcpUpdateAll;
window.mcpSwitchEnv  = mcpSwitchEnv;
window.mcpCopyCode   = mcpCopyCode;

// Smooth scroll for TOC
document.querySelectorAll('.mcp-toc a[href^="#"]').forEach(function(a) {
    a.addEventListener('click', function(e) {
        var target = document.querySelector(a.getAttribute('href'));
        if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    });
});

})();
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layouts/app.php';
?>
