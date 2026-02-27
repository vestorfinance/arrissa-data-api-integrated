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

// Run git pull and capture output + exit code
$output = [];
$exitCode = 0;
exec("cd " . escapeshellarg($repoPath) . " && git pull origin main 2>&1", $output, $exitCode);

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
