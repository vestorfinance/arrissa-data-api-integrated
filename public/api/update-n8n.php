<?php
/**
 * Update n8n API
 * POST /api/update-n8n
 *
 * Linux/Ubuntu: runs /opt/n8n/update-n8n.sh via sudo (same pattern as update.sh).
 * Requires sudoers entry (set up in the n8n installation guide):
 *   www-data ALL=(ALL) NOPASSWD: /opt/n8n/update-n8n.sh
 *
 * Windows: returns Docker Desktop instructions — cannot manage Docker from PHP on Windows.
 *
 * Protected by session auth.
 */

require_once __DIR__ . '/../../app/Auth.php';
Auth::check();

header('Content-Type: application/json');

// ─── Windows: Docker Desktop — return instructions, can't run shell scripts ──
if (PHP_OS_FAMILY === 'Windows') {
    echo json_encode([
        'success'      => false,
        'mode'         => 'docker',
        'docker_info'  => true,
        'container'    => 'n8n',
        'compose_path' => '%USERPROFILE%\\n8n\\docker-compose.yml',
        'message'      => 'Running on Windows with Docker Desktop. Use the commands below in PowerShell to update n8n.',
    ]);
    exit;
}

// ─── Linux: call update-n8n.sh via sudo (mirrors update-app.php pattern) ────
$repoPath     = realpath(__DIR__ . '/../../');
$updateScript = $repoPath . '/update-n8n.sh';

if (!file_exists($updateScript)) {
    echo json_encode([
        'success' => false,
        'mode'    => 'not-found',
        'message' => "update-n8n.sh not found at $updateScript. Pull the latest app update first.",
    ]);
    exit;
}

$output   = [];
$exitCode = 0;
exec("sudo " . escapeshellarg($updateScript) . " 2>&1", $output, $exitCode);
$outputStr = implode("\n", $output);

if ($exitCode !== 0) {
    echo json_encode([
        'success' => false,
        'mode'    => 'script',
        'error'   => 'update-n8n.sh failed',
        'output'  => $outputStr,
        'message' => 'n8n update failed. Check the output below. Ensure the sudoers entry is set up (see the n8n installation guide).',
    ]);
    exit;
}

// Extract version from script output line "Version: x.x.x"
$newVersion = '';
foreach ($output as $line) {
    if (preg_match('/Version:\s*(.+)/', $line, $m)) {
        $newVersion = trim($m[1]);
        break;
    }
}

echo json_encode([
    'success'     => true,
    'mode'        => 'script',
    'new_version' => $newVersion ?: '(run: docker exec n8n n8n --version)',
    'output'      => $outputStr,
    'message'     => 'n8n updated and restarted successfully.',
]);


