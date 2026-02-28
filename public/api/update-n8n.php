<?php
/**
 * Update n8n API
 * POST /api/update-n8n
 *
 * Detects whether n8n is running as:
 *   (a) a global npm package managed by systemd / pm2  → updates + restarts it
 *   (b) a Docker container                             → returns docker instructions
 *
 * Protected by session auth.
 */

require_once __DIR__ . '/../../app/Auth.php';
Auth::check();

header('Content-Type: application/json');

// ─── Helper: run a shell command, capture output + exit code ───────────────
function run(string $cmd, array &$out = []): int {
    $out = [];
    exec($cmd . ' 2>&1', $out, $code);
    return $code;
}

// ─── Windows: we can't manage n8n natively — return docker instructions ────
if (PHP_OS_FAMILY === 'Windows') {
    echo json_encode([
        'success'     => false,
        'mode'        => 'docker',
        'docker_info' => true,
        'message'     => 'Running on Windows. If n8n is running in Docker Desktop, use the commands below to update it.',
    ]);
    exit;
}

// ─── Linux: detect Docker vs npm ───────────────────────────────────────────

// Check if a container named "n8n" (or image "n8nio/n8n") is running
$dockerLines = [];
$dockerRunning = false;
$dockerContainerName = 'n8n';
if (run('command -v docker') === 0) {
    run('docker ps --format "{{.Names}} {{.Image}}"', $dockerLines);
    foreach ($dockerLines as $line) {
        if (stripos($line, 'n8n') !== false) {
            $dockerRunning = true;
            // Grab the container name (first word)
            $parts = explode(' ', trim($line));
            $dockerContainerName = $parts[0] ?? 'n8n';
            break;
        }
    }
}

if ($dockerRunning) {
    // Docker path — we can update it by pulling the new image and recreating the container.
    // But that requires knowing how it was started (docker run vs compose).
    // Detect docker-compose or docker compose
    $composeFile = '';
    foreach (['/home', '/root', '/opt', '/srv'] as $dir) {
        $found = [];
        run("find $dir -maxdepth 4 -name 'docker-compose.yml' -o -name 'docker-compose.yaml' -o -name 'compose.yml' 2>/dev/null | head -5", $found);
        foreach ($found as $f) {
            if (stripos(file_get_contents($f), 'n8n') !== false) {
                $composeFile = $f;
                break 2;
            }
        }
    }

    if ($composeFile) {
        $composeDir = dirname($composeFile);
        $lines = [];
        run("cd " . escapeshellarg($composeDir) . " && docker compose pull n8n && docker compose up -d n8n", $lines);
        $output = implode("\n", $lines);
        echo json_encode([
            'success' => true,
            'mode'    => 'docker-compose',
            'output'  => "Compose file: $composeFile\n\n" . $output,
            'message' => 'n8n Docker image updated and container recreated via docker compose.',
        ]);
    } else {
        // Standalone docker run — just return clear instructions
        echo json_encode([
            'success'     => false,
            'mode'        => 'docker',
            'docker_info' => true,
            'container'   => $dockerContainerName,
            'message'     => "n8n is running in Docker container \"$dockerContainerName\" but no compose file was found. Use the manual commands below.",
        ]);
    }
    exit;
}

// ─── npm global install path ────────────────────────────────────────────────

// Check n8n is actually installed globally
$n8nCheck = [];
if (run('n8n --version', $n8nCheck) !== 0 && run('npx --no n8n --version', $n8nCheck) !== 0) {
    echo json_encode([
        'success' => false,
        'mode'    => 'not-found',
        'message' => 'n8n does not appear to be installed on this server (not found in PATH). Install it with: npm install -g n8n',
    ]);
    exit;
}

$oldVersion = trim(implode('', $n8nCheck));
$lines      = [];

// Update n8n globally
run('npm update -g n8n', $lines);
$updateOutput = implode("\n", $lines);

// Check new version
$newVersionOut = [];
run('n8n --version', $newVersionOut);
$newVersion = trim(implode('', $newVersionOut));

// ─── Restart n8n ────────────────────────────────────────────────────────────
$restartMethod = 'none';
$restartOutput = '';
$restartLines  = [];

// Try pm2 first
if (run('command -v pm2') === 0) {
    $pm2List = [];
    run('pm2 list', $pm2List);
    $pm2HasN8n = false;
    foreach ($pm2List as $l) {
        if (stripos($l, 'n8n') !== false) { $pm2HasN8n = true; break; }
    }
    if ($pm2HasN8n) {
        run('pm2 restart n8n', $restartLines);
        $restartMethod = 'pm2';
        $restartOutput = implode("\n", $restartLines);
    }
}

// Fall back to systemctl
if ($restartMethod === 'none' && run('command -v systemctl') === 0) {
    $svcCheck = [];
    if (run('systemctl is-active n8n', $svcCheck) === 0 || trim(implode('', $svcCheck)) === 'active') {
        run('systemctl restart n8n', $restartLines);
        $restartMethod = 'systemctl';
        $restartOutput = implode("\n", $restartLines);
    }
}

echo json_encode([
    'success'        => true,
    'mode'           => 'npm',
    'old_version'    => $oldVersion,
    'new_version'    => $newVersion,
    'already_latest' => ($oldVersion === $newVersion),
    'restart_method' => $restartMethod,
    'output'         => $updateOutput . ($restartOutput ? "\n\n--- Restart ($restartMethod) ---\n" . $restartOutput : ''),
    'message'        => $restartMethod !== 'none'
        ? "n8n updated and restarted via $restartMethod."
        : "n8n updated. Could not auto-restart (no pm2 or systemd service found). Restart manually.",
]);
