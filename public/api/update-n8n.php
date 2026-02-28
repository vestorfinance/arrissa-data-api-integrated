<?php
/**
 * Update n8n API
 * POST /api/update-n8n
 *
 * n8n is always installed via Docker Compose (per the installation guide).
 * Known compose locations:
 *   Ubuntu VPS : /opt/n8n/docker-compose.yml  (guide default)
 *   Windows    : %USERPROFILE%\n8n\            (Docker Desktop — instructions only)
 *
 * PHP runs as www-data which may not be in the docker group, so we:
 *   1. Use absolute Docker binary paths
 *   2. Fall back to sudo docker (needs NOPASSWD sudoers)
 *
 * Protected by session auth.
 */

require_once __DIR__ . '/../../app/Auth.php';
Auth::check();

header('Content-Type: application/json');

// ─── Helper: run a command, return exit code, fill $out with lines ─────────
function run(string $cmd, array &$out = []): int {
    $out = [];
    exec($cmd . ' 2>&1', $out, $code);
    return $code;
}

// ─── Find the Docker binary (www-data may not have it in $PATH) ─────────────
function findDocker(): string {
    $candidates = [
        '/usr/bin/docker',
        '/usr/local/bin/docker',
        '/snap/bin/docker',
    ];
    foreach ($candidates as $p) {
        if (is_executable($p)) return $p;
    }
    $out = [];
    if (run('which docker', $out) === 0 && !empty($out[0])) return trim($out[0]);
    return '';
}

// ─── Find the compose file in known / guide-default locations ───────────────
function findComposeFile(): string {
    $candidates = [
        '/opt/n8n/docker-compose.yml',
        '/opt/n8n/docker-compose.yaml',
        '/opt/n8n/compose.yml',
        '/opt/n8n/compose.yaml',
        '/home/ubuntu/n8n/docker-compose.yml',
        '/home/ubuntu/n8n/docker-compose.yaml',
        '/root/n8n/docker-compose.yml',
        '/root/n8n/docker-compose.yaml',
        '/var/www/n8n/docker-compose.yml',
        '/srv/n8n/docker-compose.yml',
    ];
    // Any home directory
    $homeDirs = glob('/home/*/n8n/docker-compose.yml') ?: [];
    foreach (array_merge($candidates, $homeDirs) as $f) {
        if (file_exists($f)) return $f;
    }
    return '';
}

// ─── Windows: return Docker Desktop instructions ─────────────────────────────
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

// ─── Linux: find compose file first (guide always uses /opt/n8n/) ───────────
$composeFile = findComposeFile();

if (!$composeFile) {
    // Deeper scan as fallback
    $found = [];
    run("find /opt /home /root /srv -maxdepth 5 \\( -name 'docker-compose.yml' -o -name 'docker-compose.yaml' -o -name 'compose.yml' \\) 2>/dev/null", $found);
    foreach ($found as $f) {
        $f = trim($f);
        if ($f && file_exists($f) && stripos(@file_get_contents($f), 'n8n') !== false) {
            $composeFile = $f;
            break;
        }
    }
}

if (!$composeFile) {
    // Last resort: check if a Docker container named n8n is running (standalone docker run)
    $docker   = findDocker();
    $psList   = [];
    $dockerCmd = '';
    if ($docker) {
        if (run("$docker ps --format '{{.Names}} {{.Image}}' 2>/dev/null", $psList) === 0) {
            $dockerCmd = $docker;
        } elseif (run("sudo $docker ps --format '{{.Names}} {{.Image}}' 2>/dev/null", $psList) === 0) {
            $dockerCmd = "sudo $docker";
        }
    }
    $containerName = 'n8n';
    $containerFound = false;
    foreach ($psList as $line) {
        if (stripos($line, 'n8n') !== false) {
            $containerFound = true;
            $p = explode(' ', trim($line));
            $containerName = $p[0] ?? 'n8n';
            break;
        }
    }

    if ($containerFound) {
        echo json_encode([
            'success'     => false,
            'mode'        => 'docker',
            'docker_info' => true,
            'container'   => $containerName,
            'message'     => "n8n container \"$containerName\" is running but no docker-compose.yml was found. Use the manual commands below.",
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'mode'    => 'not-found',
            'message' => 'Could not locate an n8n docker-compose.yml. Expected location: /opt/n8n/docker-compose.yml — make sure n8n is installed following the installation guide.',
        ]);
    }
    exit;
}

$composeDir = dirname($composeFile);

// ─── Resolve docker binary and test access ──────────────────────────────────
$docker = findDocker();
if (!$docker) {
    echo json_encode([
        'success' => false,
        'mode'    => 'docker',
        'message' => "Found compose file at $composeFile but Docker binary was not found. Ensure Docker is installed.",
    ]);
    exit;
}

// Use sudo docker if www-data is not in the docker group
$testOut  = [];
$dockerCmd = (run("$docker info 2>/dev/null", $testOut) === 0) ? $docker : "sudo $docker";

// ─── Pull latest image and recreate the container ───────────────────────────
$pullOut  = [];
$upOut    = [];
$pullCode = run("cd " . escapeshellarg($composeDir) . " && $dockerCmd compose pull n8n", $pullOut);
$upCode   = run("cd " . escapeshellarg($composeDir) . " && $dockerCmd compose up -d --force-recreate n8n", $upOut);

$combined = implode("\n", $pullOut) . "\n\n" . implode("\n", $upOut);

// Get the new version from the running container
$verOut = [];
run("$dockerCmd exec n8n n8n --version 2>/dev/null", $verOut);
$newVersion = trim(implode('', $verOut));

$success = ($pullCode === 0 && $upCode === 0);

echo json_encode([
    'success'      => $success,
    'mode'         => 'docker-compose',
    'compose_file' => $composeFile,
    'new_version'  => $newVersion ?: '(check with: docker exec n8n n8n --version)',
    'output'       => $combined,
    'message'      => $success
        ? "n8n updated and restarted via docker compose. Compose file: $composeFile"
        : "docker compose command failed. Check the output below for details.",
]);

