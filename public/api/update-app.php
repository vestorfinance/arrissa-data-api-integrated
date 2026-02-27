<?php
/**
 * Update App API
 * POST /api/update-app
 *
 * Runs git pull on the server to pull the latest code from the repo.
 * Protected by session auth.
 */

require_once __DIR__ . '/../../app/Auth.php';
Auth::check();

header('Content-Type: application/json');

$repoPath = realpath(__DIR__ . '/../../');

$output   = [];
$exitCode = 0;

if (PHP_OS_FAMILY === 'Windows') {
    // Windows (WAMP/XAMPP): run update.bat directly via cmd — no sudo or chown needed
    $updateScript = $repoPath . '\\update.bat';
    $cmd = 'cmd /c "' . $updateScript . '" 2>&1';
    exec($cmd, $output, $exitCode);
} else {
    // Linux/macOS: use sudo update.sh which fixes .git ownership then pulls
    // Requires sudoers entry: www-data ALL=(ALL) NOPASSWD: /path/to/update.sh
    $updateScript = $repoPath . '/update.sh';
    exec("sudo " . escapeshellarg($updateScript) . " 2>&1", $output, $exitCode);
}

$outputStr = implode("\n", $output);

if ($exitCode !== 0) {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'error'   => 'git pull failed',
        'output'  => $outputStr,
    ]);
    exit;
}

// Detect if anything actually changed
$alreadyUpToDate = stripos($outputStr, 'Already up to date') !== false;

echo json_encode([
    'success'          => true,
    'already_up_to_date' => $alreadyUpToDate,
    'output'           => $outputStr,
]);
