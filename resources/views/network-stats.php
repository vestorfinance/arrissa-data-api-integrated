<?php
/**
 * Network Stats — aggregated view of all connected Arrissa Data instances.
 * Only accessible when this software is running on arrissadata.com.
 */

// ── Domain gate ───────────────────────────────────────────────────────────────
$host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
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
$secretRow = $db->fetchOne("SELECT value FROM settings WHERE key = 'stats_reporting_secret'");
$secret    = $secretRow ? $secretRow['value'] : '(run the migration script to generate)';

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
        <button onclick="location.reload()" class="flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium" style="background:var(--bg-secondary);color:var(--text-secondary);border:1px solid var(--border);">
            <i data-feather="refresh-cw" style="width:14px;height:14px;"></i>
            Refresh
        </button>
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

    <!-- Reporting Secret / setup guide -->
    <div class="rounded-2xl p-6" style="background:var(--card-bg);border:1px solid var(--border);">
        <h3 class="text-base font-semibold mb-1 flex items-center gap-2" style="color:var(--text-primary);">
            <i data-feather="key" style="width:16px;height:16px;color:var(--accent);flex-shrink:0;"></i>
            Reporting Secret
        </h3>
        <p class="text-sm mb-4" style="color:var(--text-secondary);">
            Add the line below to the <code style="background:var(--bg-secondary);padding:1px 6px;border-radius:4px;">.env</code>
            file on each instance you want to appear in this dashboard. Instances automatically ping this hub
            every 5 minutes while the dashboard is open.
        </p>
        <div class="flex items-center gap-3">
            <code id="secret-code" class="flex-1 rounded-xl px-4 py-3 text-sm font-mono select-all break-all"
                  style="background:var(--bg-secondary);border:1px solid var(--border);color:var(--text-primary);">
                STATS_REPORTING_SECRET=<?php echo htmlspecialchars($secret); ?>
            </code>
            <button onclick="copySecret()" class="flex items-center gap-2 px-4 py-3 rounded-xl text-sm font-medium flex-shrink-0"
                    style="background:var(--accent);color:#fff;border:none;cursor:pointer;"
                    title="Copy to clipboard">
                <i data-feather="copy" style="width:14px;height:14px;"></i>
                Copy
            </button>
        </div>
        <p class="text-xs mt-3" style="color:var(--text-secondary);">
            After adding the secret, restart your web server (or just load the dashboard on the other instance) — it will appear here within 5 minutes.
        </p>
    </div>

</div>

<script>
function copySecret() {
    const text = document.getElementById('secret-code').textContent.trim();
    navigator.clipboard.writeText(text).then(() => {
        const btn = event.currentTarget;
        btn.innerHTML = '<i data-feather="check" style="width:14px;height:14px;"></i> Copied';
        feather.replace();
        setTimeout(() => {
            btn.innerHTML = '<i data-feather="copy" style="width:14px;height:14px;"></i> Copy';
            feather.replace();
        }, 2000);
    });
}
// Auto-refresh every 30 seconds
setTimeout(() => location.reload(), 30000);
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/app.php';
?>
