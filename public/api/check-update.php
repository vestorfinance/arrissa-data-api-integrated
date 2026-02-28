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

// Fetch from remote silently
if (PHP_OS_FAMILY === 'Windows') {
    exec('cd "' . $repoPath . '" && git fetch origin 2>&1', $fetchOut, $fetchCode);
} else {
    exec('cd ' . escapeshellarg($repoPath) . ' && git fetch origin 2>&1', $fetchOut, $fetchCode);
}

// Get local HEAD
exec('cd "' . $repoPath . '" && git rev-parse HEAD 2>&1', $localOut);
$localHead = trim($localOut[0] ?? '');

// Get remote HEAD (tracking branch)
exec('cd "' . $repoPath . '" && git rev-parse @{u} 2>&1', $remoteOut);
$remoteHead = trim($remoteOut[0] ?? '');

// Count how many commits behind
exec('cd "' . $repoPath . '" && git rev-list HEAD..@{u} --count 2>&1', $countOut);
$commitsBehind = intval(trim($countOut[0] ?? '0'));

$updateAvailable = ($localHead !== $remoteHead) && $commitsBehind > 0;

echo json_encode([
    'update_available' => $updateAvailable,
    'commits_behind'   => $commitsBehind,
    'local_head'       => substr($localHead, 0, 7),
    'remote_head'      => substr($remoteHead, 0, 7),
]);
