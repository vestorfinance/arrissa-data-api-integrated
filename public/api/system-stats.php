<?php
/**
 * System resource stats endpoint — CPU load, RAM, disk, uptime.
 * Auth-protected. Called by the dashboard widget every 5 s.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../app/Auth.php';
Auth::check();

$isLinux = (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN');

// ── CPU load average ──────────────────────────────────────────────
$load = false;
if ($isLinux) {
    $load = sys_getloadavg();
}
$cpu = $load !== false ? round($load[0], 2) : null;

// ── RAM (Linux: /proc/meminfo; Windows: N/A) ──────────────────────
$ramTotal = null;
$ramUsed  = null;
$ramFree  = null;
if ($isLinux && is_readable('/proc/meminfo')) {
    $meminfo = file_get_contents('/proc/meminfo');
    preg_match('/MemTotal:\s+(\d+)/', $meminfo, $mt);
    preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $ma);
    if ($mt && $ma) {
        $ramTotal = (int)$mt[1] * 1024;                    // bytes
        $ramFree  = (int)$ma[1] * 1024;
        $ramUsed  = $ramTotal - $ramFree;
    }
}

// ── Disk ──────────────────────────────────────────────────────────
$diskPath  = __DIR__ . '/../../';
$diskTotal = @disk_total_space($diskPath);
$diskFree  = @disk_free_space($diskPath);
$diskUsed  = ($diskTotal && $diskFree) ? $diskTotal - $diskFree : null;

// ── Uptime (Linux only) ───────────────────────────────────────────
$uptimeSeconds = null;
if ($isLinux && is_readable('/proc/uptime')) {
    $uptimeSeconds = (int)explode(' ', file_get_contents('/proc/uptime'))[0];
}

function humanBytes($b) {
    if ($b === null) return null;
    $u = ['B','KB','MB','GB','TB'];
    $i = 0;
    while ($b >= 1024 && $i < 4) { $b /= 1024; $i++; }
    return round($b, 1) . ' ' . $u[$i];
}

function humanUptime($s) {
    if ($s === null) return null;
    $d = intdiv($s, 86400); $s %= 86400;
    $h = intdiv($s, 3600);  $s %= 3600;
    $m = intdiv($s, 60);
    $parts = [];
    if ($d) $parts[] = "{$d}d";
    if ($h) $parts[] = "{$h}h";
    $parts[] = "{$m}m";
    return implode(' ', $parts);
}

echo json_encode([
    'cpu_load_1m'   => $cpu,
    'ram_total'     => $ramTotal,
    'ram_used'      => $ramUsed,
    'ram_free'      => $ramFree,
    'ram_total_h'   => humanBytes($ramTotal),
    'ram_used_h'    => humanBytes($ramUsed),
    'ram_pct'       => ($ramTotal && $ramUsed) ? round($ramUsed / $ramTotal * 100, 1) : null,
    'disk_total'    => $diskTotal,
    'disk_used'     => $diskUsed,
    'disk_free'     => $diskFree,
    'disk_total_h'  => humanBytes($diskTotal ?: null),
    'disk_used_h'   => humanBytes($diskUsed),
    'disk_pct'      => ($diskTotal && $diskUsed) ? round($diskUsed / $diskTotal * 100, 1) : null,
    'uptime_s'      => $uptimeSeconds,
    'uptime_h'      => humanUptime($uptimeSeconds),
    'os'            => $isLinux ? 'linux' : 'windows',
    'php_version'   => PHP_VERSION,
    'ts'            => time(),
]);
