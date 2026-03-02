<?php
/**
 * Check Update API
 * GET /api/check-update
 *
 * Runs git fetch and compares local HEAD with remote HEAD.
 * Returns whether an update is available.
 * Protected by session auth.
 */

require_once __DIR__ . '/../../app/Auth.php';
Auth::check();

header('Content-Type: application/json');

$repoPath = realpath(__DIR__ . '/../../');

// ── Resolve git binary ────────────────────────────────────────────────────────
// Apache on Windows runs with a minimal PATH that often excludes git.
// Try common installation locations before falling back to bare "git".
function resolveGit(): string {
    if (PHP_OS_FAMILY !== 'Windows') return 'git';
    $candidates = [
        'C:\\Program Files\\Git\\bin\\git.exe',
        'C:\\Program Files\\Git\\cmd\\git.exe',
        'C:\\Program Files (x86)\\Git\\bin\\git.exe',
        'C:\\Program Files (x86)\\Git\\cmd\\git.exe',
    ];
    foreach ($candidates as $p) {
        if (file_exists($p)) return '"' . $p . '"';
    }
    // Last resort: ask where.exe (available in cmd)
    exec('where git 2>NUL', $out);
    if (!empty($out[0]) && file_exists(trim($out[0]))) {
        return '"' . trim($out[0]) . '"';
    }
    return 'git'; // fallback, may still fail
}

$git = resolveGit();
$debug = [];

// ── Check exec() is available ─────────────────────────────────────────────────
$execDisabled = in_array('exec', array_map('trim', explode(',', ini_get('disable_functions'))));
$debug['exec_available'] = !$execDisabled;
$debug['php_os']         = PHP_OS_FAMILY;
$debug['repo_path']      = $repoPath;
$debug['git_binary']     = $git;

if ($execDisabled) {
    echo json_encode([
        'update_available' => false,
        'commits_behind'   => 0,
        'local_head'       => '',
        'remote_head'      => '',
        'error'            => 'exec() is disabled in PHP (disable_functions)',
        'debug'            => $debug,
    ]);
    exit;
}

// ── Git operations ────────────────────────────────────────────────────────────
// On Linux, www-data has no HOME — set it so git can read its config.
$env = PHP_OS_FAMILY !== 'Windows' ? 'HOME=/tmp ' : '';

if (PHP_OS_FAMILY === 'Windows') {
    $cd = 'cd /d "' . $repoPath . '"';
    $sep = ' && ';
} else {
    $cd = 'cd ' . escapeshellarg($repoPath);
    $sep = ' && ';
}

// fetch
exec($env . $cd . $sep . $git . ' fetch origin 2>&1', $fetchOut, $fetchCode);
$debug['fetch_exit']   = $fetchCode;
$debug['fetch_output'] = implode("\n", $fetchOut);

// local HEAD
exec($env . $cd . $sep . $git . ' rev-parse HEAD 2>&1', $localOut, $localCode);
$localHead = trim($localOut[0] ?? '');
$debug['local_head_raw'] = $localHead;
$debug['local_exit']     = $localCode;

// remote ref: try @{u} first, then origin/main, then origin/HEAD, then origin/master
$remoteHead = '';
$remoteAttempts = ["@{u}", 'origin/main', 'origin/HEAD', 'origin/master'];
foreach ($remoteAttempts as $ref) {
    $out = [];
    exec($env . $cd . $sep . $git . ' rev-parse ' . escapeshellarg($ref) . ' 2>&1', $out, $rc);
    $val = trim($out[0] ?? '');
    $debug['remote_try_' . $ref] = ['val' => $val, 'exit' => $rc];
    // A valid SHA is exactly 40 hex chars
    if ($rc === 0 && preg_match('/^[0-9a-f]{40}$/i', $val)) {
        $remoteHead = $val;
        $debug['remote_ref_used'] = $ref;
        break;
    }
}

// commits behind
$commitsBehind = 0;
if ($remoteHead && $localHead) {
    $countOut = [];
    exec($env . $cd . $sep . $git . ' rev-list ' . escapeshellarg($localHead) . '..' . escapeshellarg($remoteHead) . ' --count 2>&1', $countOut, $countCode);
    $commitsBehind = intval(trim($countOut[0] ?? '0'));
    $debug['count_exit']   = $countCode;
    $debug['count_output'] = trim($countOut[0] ?? '');
}

$updateAvailable = $remoteHead && $localHead && ($localHead !== $remoteHead) && $commitsBehind > 0;

echo json_encode([
    'update_available' => $updateAvailable,
    'commits_behind'   => $commitsBehind,
    'local_head'       => substr($localHead, 0, 7),
    'remote_head'      => substr($remoteHead, 0, 7),
    'debug'            => $debug,
]);
