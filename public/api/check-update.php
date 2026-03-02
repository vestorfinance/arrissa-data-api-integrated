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

// ── Git operations ────────────────────────────────────────────────────────────
if (PHP_OS_FAMILY === 'Windows') {
    $cd = 'cd /d "' . $repoPath . '"';
    exec($cd . ' && ' . $git . ' fetch origin 2>&1', $fetchOut, $fetchCode);
    exec($cd . ' && ' . $git . ' rev-parse HEAD 2>&1', $localOut);
    exec($cd . ' && ' . $git . ' rev-parse @{u} 2>&1', $remoteOut);
    exec($cd . ' && ' . $git . ' rev-list HEAD..@{u} --count 2>&1', $countOut);
} else {
    $cd = 'cd ' . escapeshellarg($repoPath);
    exec($cd . ' && git fetch origin 2>&1', $fetchOut, $fetchCode);
    exec($cd . ' && git rev-parse HEAD 2>&1', $localOut);
    exec($cd . ' && git rev-parse @{u} 2>&1', $remoteOut);
    exec($cd . ' && git rev-list HEAD..@{u} --count 2>&1', $countOut);
}

$localHead     = trim($localOut[0] ?? '');
$remoteHead    = trim($remoteOut[0] ?? '');
$commitsBehind = intval(trim($countOut[0] ?? '0'));

$updateAvailable = ($localHead !== $remoteHead) && $commitsBehind > 0;

echo json_encode([
    'update_available' => $updateAvailable,
    'commits_behind'   => $commitsBehind,
    'local_head'       => substr($localHead, 0, 7),
    'remote_head'      => substr($remoteHead, 0, 7),
]);
