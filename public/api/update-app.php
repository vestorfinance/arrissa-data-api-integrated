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

    if (!file_exists($updateScript)) {
        http_response_code(200);
        echo json_encode([
            'success' => false,
            'error'   => 'update.sh not found',
            'output'  => "Expected update script at: $updateScript",
        ]);
        exit;
    }

    if (!is_executable($updateScript)) {
        $output[] = "Warning: update.sh is not executable; falling back to bash invocation.";
        $cmd = 'sudo bash ' . escapeshellarg($updateScript) . ' 2>&1';
    } else {
        $cmd = 'sudo ' . escapeshellarg($updateScript) . ' 2>&1';
    }

    exec($cmd, $output, $exitCode);
}

$outputStr = implode("\n", $output);

if ($exitCode !== 0) {
    // Detect missing sudoers entry — give an actionable error instead of raw sudo output
    $isSudoError = stripos($outputStr, 'a password is required') !== false
                || stripos($outputStr, 'sudo: no tty present') !== false
                || stripos($outputStr, 'askpass') !== false;

    if ($isSudoError) {
        $installDir = realpath($repoPath);
        $scriptPath = $installDir . '/update.sh';
        http_response_code(200);
        echo json_encode([
            'success' => false,
            'error'   => 'sudo not configured',
            'output'  => implode("\n", [
                "ERROR: www-data does not have passwordless sudo access.",
                "",
                "Run this ONCE on your server to fix it:",
                "  sudo bash $scriptPath",
                "",
                "Then add this line to /etc/sudoers (sudo visudo):",
                "  www-data ALL=(ALL) NOPASSWD: /bin/bash $scriptPath",
                "",
                "After saving sudoers, the Update button will work permanently.",
            ]),
        ]);
        exit;
    }

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

// Detect whether Apache restart/reload was triggered
$apacheRestarted = stripos($outputStr, 'graceful reload triggered') !== false
                || stripos($outputStr, 'reloaded via systemctl') !== false
                || stripos($outputStr, 'Scheduling Apache restart') !== false;

echo json_encode([
    'success'            => true,
    'already_up_to_date' => $alreadyUpToDate,
    'apache_restarted'   => $apacheRestarted,
    'output'             => $outputStr,
]);
