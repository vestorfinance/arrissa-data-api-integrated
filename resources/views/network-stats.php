<?php
/**
 * Network Stats — aggregated view of all connected Arrissa Data instances.
 * Only accessible when this software is running on arrissadata.com.
 * Data is loaded and refreshed via AJAX — no full-page reloads.
 */

// ── Domain gate ───────────────────────────────────────────────────────────────
$host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
if ($host !== 'arrissadata.com' && $host !== 'www.arrissadata.com') {
    http_response_code(403);
    echo '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body style="font-family:sans-serif;background:#0f0f0f;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;"><div style="text-align:center;"><h1 style="color:#ef4444;">403 Forbidden</h1><p style="color:#a0a0a0;">This page is only accessible on arrissadata.com</p></div></body></html>';
    exit;
}

$title = 'Network Stats';

ob_start();
?>
<div class="p-8 max-w-7xl mx-auto">

    <!-- Page header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold mb-1" style="color:var(--text-primary);">
                <i data-feather="globe" style="width:22px;height:22px;display:inline;vertical-align:middle;margin-right:8px;color:var(--accent);"></i>
                Network Stats
            </h1>
            <p style="color:var(--text-secondary);">Live view of all connected Arrissa Data instances</p>
        </div>
        <div class="flex items-center gap-3">
            <span id="last-updated" class="text-xs" style="color:var(--text-secondary);">Loading…</span>
            <span id="refresh-spinner" style="display:none;">
                <svg style="width:16px;height:16px;animation:spin 0.8s linear infinite;color:var(--accent);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
            </span>
        </div>
    </div>

    <!-- Summary cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
        <div class="rounded-2xl p-5" style="background:var(--card-bg);border:1px solid var(--border);">
            <p class="text-xs font-semibold uppercase tracking-wider mb-2" style="color:var(--text-secondary);">Total Instances</p>
            <p id="stat-total" class="text-3xl font-bold" style="color:var(--text-primary);">—</p>
        </div>
        <div class="rounded-2xl p-5" style="background:var(--card-bg);border:1px solid var(--border);">
            <p class="text-xs font-semibold uppercase tracking-wider mb-2" style="color:var(--text-secondary);">Online <span style="font-weight:400;">(last 6 min)</span></p>
            <p id="stat-online" class="text-3xl font-bold" style="color:var(--success);">—</p>
        </div>
        <div class="rounded-2xl p-5" style="background:var(--card-bg);border:1px solid var(--border);">
            <p class="text-xs font-semibold uppercase tracking-wider mb-2" style="color:var(--text-secondary);">Offline / Stale</p>
            <p id="stat-offline" class="text-3xl font-bold" style="color:var(--danger);">—</p>
        </div>
    </div>

    <!-- Instances table -->
    <div id="instances-wrap" class="mb-8"></div>

    <!-- How it works info box -->
    <div class="rounded-2xl p-6" style="background:var(--card-bg);border:1px solid var(--border);">
        <h3 class="text-base font-semibold mb-1 flex items-center gap-2" style="color:var(--text-primary);">
            <i data-feather="info" style="width:16px;height:16px;color:var(--accent);flex-shrink:0;"></i>
            Zero-Config Auto-Connect
        </h3>
        <p class="text-sm" style="color:var(--text-secondary);">
            Every instance running this software automatically reports its stats to this hub every 5 minutes —
            no manual setup or secret keys required. Just make sure <strong style="color:var(--text-primary);">app_base_url</strong>
            is correctly set in each instance's Settings page and it will appear here automatically.
        </p>
    </div>

</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<script>
function humanBytes(b) {
    if (b === null || b === undefined) return '—';
    const u = ['B','KB','MB','GB','TB'];
    let i = 0;
    while (b >= 1024 && i < 4) { b /= 1024; i++; }
    return b.toFixed(1) + ' ' + u[i];
}

function humanUptime(s) {
    if (s === null || s === undefined) return '—';
    const d = Math.floor(s / 86400); s %= 86400;
    const h = Math.floor(s / 3600);  s %= 3600;
    const m = Math.floor(s / 60);
    const parts = [];
    if (d) parts.push(d + 'd');
    if (h) parts.push(h + 'h');
    parts.push(m + 'm');
    return parts.join(' ');
}

function renderInstances(instances) {
    const wrap = document.getElementById('instances-wrap');

    if (!instances.length) {
        wrap.innerHTML = `
            <div class="rounded-2xl p-12 text-center" style="background:var(--card-bg);border:1px solid var(--border);">
                <svg style="width:48px;height:48px;color:var(--text-secondary);margin:0 auto 16px;display:block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="1" y1="1" x2="23" y2="23"/><path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55" /><path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39" /><path d="M10.71 5.05A16 16 0 0 1 22.56 9" /><path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88" /><path d="M8.53 16.11a6 6 0 0 1 6.95 0" /><line x1="12" y1="20" x2="12.01" y2="20"/></svg>
                <h3 class="text-lg font-semibold mb-2" style="color:var(--text-primary);">No instances connected yet</h3>
                <p style="color:var(--text-secondary);">Set <strong>app_base_url</strong> in Settings on each instance — it will appear here within 5 minutes.</p>
            </div>`;
        return;
    }

    const rows = instances.map(inst => {
        const onlineBadge = inst.online
            ? `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" style="background:rgba(16,185,129,0.15);color:var(--success);"><span style="width:6px;height:6px;border-radius:50%;background:var(--success);flex-shrink:0;"></span>Online</span>`
            : `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" style="background:rgba(239,68,68,0.15);color:var(--danger);"><span style="width:6px;height:6px;border-radius:50%;background:var(--danger);flex-shrink:0;"></span>Offline</span>`;

        const ramCell = inst.ram_pct != null
            ? `<div class="font-medium" style="color:var(--text-primary);">${inst.ram_pct}%</div><div class="text-xs mt-0.5" style="color:var(--text-secondary);">${humanBytes(inst.ram_used)} / ${humanBytes(inst.ram_total)}</div>`
            : `<span style="color:var(--text-secondary);">—</span>`;

        const diskCell = inst.disk_pct != null
            ? `<div class="font-medium" style="color:var(--text-primary);">${inst.disk_pct}%</div><div class="text-xs mt-0.5" style="color:var(--text-secondary);">${humanBytes(inst.disk_used)} / ${humanBytes(inst.disk_total)}</div>`
            : `<span style="color:var(--text-secondary);">—</span>`;

        const version = inst.app_version ? ` <span style="color:var(--text-secondary);font-size:0.75rem;">(${inst.app_version})</span>` : '';

        return `<tr style="border-bottom:1px solid var(--border);" onmouseover="this.style.background='var(--bg-secondary)'" onmouseout="this.style.background=''">
            <td class="px-5 py-4">
                <div class="font-semibold mb-0.5" style="color:var(--text-primary);">${inst.instance_name || inst.instance_url}${version}</div>
                <a href="${inst.instance_url}" target="_blank" rel="noopener noreferrer" class="text-xs hover:underline" style="color:var(--accent);">${inst.instance_url}</a>
            </td>
            <td class="px-4 py-4">${onlineBadge}</td>
            <td class="px-4 py-4" style="color:var(--text-primary);">${inst.cpu_load ?? '—'}</td>
            <td class="px-4 py-4">${ramCell}</td>
            <td class="px-4 py-4">${diskCell}</td>
            <td class="px-4 py-4 whitespace-nowrap" style="color:var(--text-primary);">${humanUptime(inst.uptime_s)}</td>
            <td class="px-4 py-4 whitespace-nowrap" style="color:var(--text-secondary);">${inst.php_version || '—'}</td>
            <td class="px-4 py-4 whitespace-nowrap" style="color:var(--text-secondary);">${inst.os_platform || '—'}</td>
            <td class="px-4 py-4 whitespace-nowrap text-xs" style="color:var(--text-secondary);">${inst.ago}</td>
        </tr>`;
    }).join('');

    wrap.innerHTML = `
        <div class="rounded-2xl overflow-hidden" style="border:1px solid var(--border);">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr style="background:var(--bg-secondary);border-bottom:1px solid var(--border);">
                            <th class="text-left px-5 py-3 font-semibold whitespace-nowrap" style="color:var(--text-secondary);">Instance</th>
                            <th class="text-left px-4 py-3 font-semibold whitespace-nowrap" style="color:var(--text-secondary);">Status</th>
                            <th class="text-left px-4 py-3 font-semibold whitespace-nowrap" style="color:var(--text-secondary);">CPU (1m)</th>
                            <th class="text-left px-4 py-3 font-semibold whitespace-nowrap" style="color:var(--text-secondary);">RAM</th>
                            <th class="text-left px-4 py-3 font-semibold whitespace-nowrap" style="color:var(--text-secondary);">Disk</th>
                            <th class="text-left px-4 py-3 font-semibold whitespace-nowrap" style="color:var(--text-secondary);">Uptime</th>
                            <th class="text-left px-4 py-3 font-semibold whitespace-nowrap" style="color:var(--text-secondary);">PHP</th>
                            <th class="text-left px-4 py-3 font-semibold whitespace-nowrap" style="color:var(--text-secondary);">OS</th>
                            <th class="text-left px-4 py-3 font-semibold whitespace-nowrap" style="color:var(--text-secondary);">Last Ping</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        </div>`;
}

function refreshData() {
    const spinner = document.getElementById('refresh-spinner');
    spinner.style.display = 'inline';

    fetch('/api/network-stats-data')
        .then(r => r.json())
        .then(data => {
            spinner.style.display = 'none';
            document.getElementById('stat-total').textContent   = data.total;
            document.getElementById('stat-online').textContent  = data.online;
            document.getElementById('stat-offline').textContent = data.offline;
            renderInstances(data.instances || []);
            const d = new Date();
            document.getElementById('last-updated').textContent =
                'Updated ' + d.getHours().toString().padStart(2,'0') + ':' +
                d.getMinutes().toString().padStart(2,'0') + ':' +
                d.getSeconds().toString().padStart(2,'0');
        })
        .catch(() => { spinner.style.display = 'none'; });
}

// On page load: fire a force-ping for this instance (hub registers itself),
// then immediately load the data. Other instances self-report automatically
// via the background heartbeat already running in the layout.
(function init() {
    const fd = new FormData();
    fd.append('force', '1');
    fetch('/api/instance-heartbeat', { method: 'POST', body: fd })
        .finally(() => refreshData());
})();

// Silently refresh data every 30 seconds (no page reload)
setInterval(refreshData, 30000);
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/app.php';
?>

if ($host !== 'arrissadata.com' && $host !== 'www.arrissadata.com') {
    http_response_code(403);
    echo '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body style="font-family:sans-serif;background:#0f0f0f;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;"><div style="text-align:center;"><h1 style="color:#ef4444;">403 Forbidden</h1><p style="color:#a0a0a0;">This page is only accessible on arrissadata.com</p></div></body></html>';
    exit;
}

require_once __DIR__ . '/../../app/Database.php';

$db = Database::getInstance();

// Ensure table exists
$db->query("
    CREATE TABLE IF NOT EXISTS instance_heartbeats (
        id            INTEGER PRIMARY KEY AUTOINCREMENT,
        instance_key  TEXT    NOT NULL UNIQUE,
        instance_url  TEXT    NOT NULL,
        instance_name TEXT,
        php_version   TEXT,
        os_platform   TEXT,
        cpu_load      REAL,
        ram_total     INTEGER,
        ram_used      INTEGER,
        ram_pct       REAL,
        disk_total    INTEGER,
        disk_used     INTEGER,
        disk_pct      REAL,
        uptime_s      INTEGER,
        app_version   TEXT,
        first_seen    DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_seen     DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

$instances = $db->fetchAll("SELECT * FROM instance_heartbeats ORDER BY last_seen DESC");

$title = 'Network Stats';

function humanBytes($b) {
    if ($b === null) return '—';
    $u = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($b >= 1024 && $i < 4) { $b /= 1024; $i++; }
    return round($b, 1) . ' ' . $u[$i];
}

function humanUptime($s) {
    if ($s === null) return '—';
    $d = intdiv($s, 86400); $s %= 86400;
    $h = intdiv($s, 3600);  $s %= 3600;
    $m = intdiv($s, 60);
    $parts = [];
    if ($d) $parts[] = "{$d}d";
    if ($h) $parts[] = "{$h}h";
    $parts[] = "{$m}m";
    return implode(' ', $parts);
}

$now    = time();
$online = array_filter($instances, fn($i) => (strtotime($i['last_seen']) >= $now - 360));

ob_start();
?>
<div class="p-8 max-w-7xl mx-auto">

    <!-- Page header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold mb-1" style="color:var(--text-primary);">
                <i data-feather="globe" style="width:22px;height:22px;display:inline;vertical-align:middle;margin-right:8px;color:var(--accent);"></i>
                Network Stats
            </h1>
            <p style="color:var(--text-secondary);">Live view of all connected Arrissa Data instances</p>
        </div>
        <div class="flex items-center gap-2">
            <span id="ping-status" class="text-xs px-3 py-1 rounded-full" style="background:var(--bg-secondary);color:var(--text-secondary);border:1px solid var(--border);">Pinging…</span>
            <button id="ping-btn" onclick="forcePing()" class="flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium" style="background:var(--accent);color:#fff;border:none;cursor:pointer;">
                <i data-feather="zap" style="width:14px;height:14px;"></i>
                Ping Now
            </button>
            <button onclick="location.reload()" class="flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium" style="background:var(--bg-secondary);color:var(--text-secondary);border:1px solid var(--border);">
                <i data-feather="refresh-cw" style="width:14px;height:14px;"></i>
                Refresh
            </button>
        </div>
    </div>

    <!-- Summary cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
        <div class="rounded-2xl p-5" style="background:var(--card-bg);border:1px solid var(--border);">
            <p class="text-xs font-semibold uppercase tracking-wider mb-2" style="color:var(--text-secondary);">Total Instances</p>
            <p class="text-3xl font-bold" style="color:var(--text-primary);"><?php echo count($instances); ?></p>
        </div>
        <div class="rounded-2xl p-5" style="background:var(--card-bg);border:1px solid var(--border);">
            <p class="text-xs font-semibold uppercase tracking-wider mb-2" style="color:var(--text-secondary);">Online <span style="color:var(--text-secondary);font-weight:400;">(last 6 min)</span></p>
            <p class="text-3xl font-bold" style="color:var(--success);"><?php echo count($online); ?></p>
        </div>
        <div class="rounded-2xl p-5" style="background:var(--card-bg);border:1px solid var(--border);">
            <p class="text-xs font-semibold uppercase tracking-wider mb-2" style="color:var(--text-secondary);">Offline / Stale</p>
            <p class="text-3xl font-bold" style="color:var(--danger);"><?php echo count($instances) - count($online); ?></p>
        </div>
    </div>

    <!-- Instances table -->
    <?php if (empty($instances)): ?>
    <div class="rounded-2xl p-12 text-center" style="background:var(--card-bg);border:1px solid var(--border);">
        <i data-feather="wifi-off" style="width:48px;height:48px;color:var(--text-secondary);margin:0 auto 16px;display:block;"></i>
        <h3 class="text-lg font-semibold mb-2" style="color:var(--text-primary);">No instances connected yet</h3>
        <p style="color:var(--text-secondary);">Configure your other instances with the reporting secret shown below.</p>
    </div>
    <?php else: ?>
    <div class="rounded-2xl overflow-hidden mb-8" style="border:1px solid var(--border);">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr style="background:var(--bg-secondary);border-bottom:1px solid var(--border);">
                        <th class="text-left px-5 py-3 font-semibold whitespace-nowrap" style="color:var(--text-secondary);">Instance</th>
                        <th class="text-left px-4 py-3 font-semibold whitespace-nowrap" style="color:var(--text-secondary);">Status</th>
                        <th class="text-left px-4 py-3 font-semibold whitespace-nowrap" style="color:var(--text-secondary);">CPU (1m)</th>
                        <th class="text-left px-4 py-3 font-semibold whitespace-nowrap" style="color:var(--text-secondary);">RAM</th>
                        <th class="text-left px-4 py-3 font-semibold whitespace-nowrap" style="color:var(--text-secondary);">Disk</th>
                        <th class="text-left px-4 py-3 font-semibold whitespace-nowrap" style="color:var(--text-secondary);">Uptime</th>
                        <th class="text-left px-4 py-3 font-semibold whitespace-nowrap" style="color:var(--text-secondary);">PHP</th>
                        <th class="text-left px-4 py-3 font-semibold whitespace-nowrap" style="color:var(--text-secondary);">OS</th>
                        <th class="text-left px-4 py-3 font-semibold whitespace-nowrap" style="color:var(--text-secondary);">Last Ping</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($instances as $inst):
                        $lastTs   = strtotime($inst['last_seen']);
                        $isOnline = $lastTs >= $now - 360;
                        $diffSec  = $now - $lastTs;
                        if ($diffSec < 60)         $ago = 'Just now';
                        elseif ($diffSec < 3600)   $ago = round($diffSec / 60) . 'm ago';
                        elseif ($diffSec < 86400)  $ago = round($diffSec / 3600) . 'h ago';
                        else                       $ago = round($diffSec / 86400) . 'd ago';
                    ?>
                    <tr style="border-bottom:1px solid var(--border);"
                        onmouseover="this.style.background='var(--bg-secondary)'"
                        onmouseout="this.style.background=''">
                        <td class="px-5 py-4">
                            <div class="font-semibold mb-0.5" style="color:var(--text-primary);">
                                <?php echo htmlspecialchars($inst['instance_name'] ?? $inst['instance_url']); ?>
                            </div>
                            <a href="<?php echo htmlspecialchars($inst['instance_url']); ?>" target="_blank" rel="noopener noreferrer"
                               class="text-xs hover:underline" style="color:var(--accent);">
                                <?php echo htmlspecialchars($inst['instance_url']); ?>
                            </a>
                            <?php if (!empty($inst['app_version'])): ?>
                            <span class="ml-1 text-xs" style="color:var(--text-secondary);">(<?php echo htmlspecialchars($inst['app_version']); ?>)</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-4">
                            <?php if ($isOnline): ?>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
                                  style="background:rgba(16,185,129,0.15);color:var(--success);">
                                <span style="width:6px;height:6px;border-radius:50%;background:var(--success);flex-shrink:0;"></span>
                                Online
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
                                  style="background:rgba(239,68,68,0.15);color:var(--danger);">
                                <span style="width:6px;height:6px;border-radius:50%;background:var(--danger);flex-shrink:0;"></span>
                                Offline
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-4" style="color:var(--text-primary);">
                            <?php echo $inst['cpu_load'] !== null ? $inst['cpu_load'] : '—'; ?>
                        </td>
                        <td class="px-4 py-4">
                            <?php if ($inst['ram_pct'] !== null): ?>
                            <div class="font-medium" style="color:var(--text-primary);"><?php echo $inst['ram_pct']; ?>%</div>
                            <div class="text-xs mt-0.5" style="color:var(--text-secondary);">
                                <?php echo humanBytes($inst['ram_used']); ?> / <?php echo humanBytes($inst['ram_total']); ?>
                            </div>
                            <?php else: ?>
                            <span style="color:var(--text-secondary);">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-4">
                            <?php if ($inst['disk_pct'] !== null): ?>
                            <div class="font-medium" style="color:var(--text-primary);"><?php echo $inst['disk_pct']; ?>%</div>
                            <div class="text-xs mt-0.5" style="color:var(--text-secondary);">
                                <?php echo humanBytes($inst['disk_used']); ?> / <?php echo humanBytes($inst['disk_total']); ?>
                            </div>
                            <?php else: ?>
                            <span style="color:var(--text-secondary);">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap" style="color:var(--text-primary);">
                            <?php echo humanUptime($inst['uptime_s']); ?>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap" style="color:var(--text-secondary);">
                            <?php echo htmlspecialchars($inst['php_version'] ?? '—'); ?>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap" style="color:var(--text-secondary);">
                            <?php echo htmlspecialchars($inst['os_platform'] ?? '—'); ?>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-xs" style="color:var(--text-secondary);">
                            <?php echo $ago; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- How it works info box -->
    <div class="rounded-2xl p-6" style="background:var(--card-bg);border:1px solid var(--border);">
        <h3 class="text-base font-semibold mb-1 flex items-center gap-2" style="color:var(--text-primary);">
            <i data-feather="info" style="width:16px;height:16px;color:var(--accent);flex-shrink:0;"></i>
            Zero-Config Auto-Connect
        </h3>
        <p class="text-sm" style="color:var(--text-secondary);">
            Every instance running this software automatically reports its stats to this hub every 5 minutes —
            no manual setup or secret keys required. Just make sure <strong style="color:var(--text-primary);">app_base_url</strong>
            is correctly set in each instance's Settings page and the instance will appear here automatically.
        </p>
    </div>

</div>

<script>
function setStatus(msg, color) {
    const el = document.getElementById('ping-status');
    el.textContent = msg;
    el.style.color = color || 'var(--text-secondary)';
}

function forcePing() {
    const btn = document.getElementById('ping-btn');
    btn.disabled = true;
    setStatus('Pinging…', 'var(--text-secondary)');

    const fd = new FormData();
    fd.append('force', '1');

    fetch('/api/instance-heartbeat', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            if (data.success) {
                setStatus('Pinged ✓ — refreshing…', 'var(--success)');
                // Reset localStorage so the background heartbeat also fires fresh
                localStorage.removeItem('arrissa_hb_ts');
                setTimeout(() => location.reload(), 1200);
            } else if (data.skipped) {
                setStatus('Skipped: ' + (data.reason || 'rate limit'), 'var(--warning)');
            } else {
                setStatus('Error: ' + (data.error || data.curl_error || JSON.stringify(data)), 'var(--danger)');
            }
        })
        .catch(e => {
            btn.disabled = false;
            setStatus('Request failed', 'var(--danger)');
        });
}

// Force ping immediately on page load (bypass localStorage rate limit)
localStorage.removeItem('arrissa_hb_ts');
forcePing();

// Auto-refresh every 60 seconds
setTimeout(() => location.reload(), 60000);
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/app.php';
?>
