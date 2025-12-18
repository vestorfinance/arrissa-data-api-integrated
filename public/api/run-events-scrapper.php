<?php
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Database.php';

// Check authentication
Auth::check();

// Close the session to prevent blocking other requests
session_write_close();

// Set headers for server-sent events
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

// Disable output buffering
if (ob_get_level()) ob_end_clean();
ini_set('output_buffering', 'off');
ini_set('zlib.output_compression', false);

// Function to send SSE message
function sendMessage($data) {
    echo "data: " . json_encode($data) . "\n\n";
    if (ob_get_level()) ob_flush();
    flush();
}

// Get the script directory
$scriptDir = dirname(__DIR__, 2);
$eventsDir = $scriptDir . DIRECTORY_SEPARATOR . 'events-scrapper-nodejs';
$batFile = __DIR__ . DIRECTORY_SEPARATOR . 'run-events-scrapper.bat';

// Check if Node.js is installed
exec('node --version 2>&1', $output, $returnCode);
if ($returnCode !== 0) {
    sendMessage([
        'type' => 'error',
        'message' => 'Node.js is not installed or not in PATH. Please install Node.js from https://nodejs.org/'
    ]);
    exit;
}

sendMessage([
    'type' => 'info',
    'message' => 'Checking dependencies...'
]);

// Check if package.json exists
if (!file_exists($eventsDir . DIRECTORY_SEPARATOR . 'package.json')) {
    sendMessage([
        'type' => 'error',
        'message' => 'package.json not found in events-scrapper-nodejs directory'
    ]);
    exit;
}

// Check if node_modules exists, if not install dependencies
if (!is_dir($eventsDir . DIRECTORY_SEPARATOR . 'node_modules')) {
    sendMessage([
        'type' => 'info',
        'message' => 'Installing dependencies... This may take a few minutes.'
    ]);
    
    // Change to events directory and run npm install
    $oldDir = getcwd();
    chdir($eventsDir);
    
    $descriptorspec = [
        0 => ['pipe', 'r'],  // stdin
        1 => ['pipe', 'w'],  // stdout
        2 => ['pipe', 'w']   // stderr
    ];
    
    $process = proc_open('npm install', $descriptorspec, $pipes);
    
    if (is_resource($process)) {
        fclose($pipes[0]);
        
        // Read output
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        
        while (true) {
            $status = proc_get_status($process);
            
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            
            if ($stdout) {
                sendMessage([
                    'type' => 'output',
                    'message' => $stdout
                ]);
            }
            
            if ($stderr) {
                sendMessage([
                    'type' => 'output',
                    'message' => $stderr
                ]);
            }
            
            if (!$status['running']) {
                break;
            }
            
            usleep(100000); // 100ms
        }
        
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
    }
    
    chdir($oldDir);
    
    sendMessage([
        'type' => 'success',
        'message' => 'Dependencies installed successfully!'
    ]);
}

sendMessage([
    'type' => 'info',
    'message' => 'Opening command prompt...'
]);

// Absolute path to the VBS launcher
$vbsFilePath = 'C:\\wamp64\\www\\events-scrapper-nodejs\\launch-scrapper.vbs';

if (!file_exists($vbsFilePath)) {
    sendMessage([
        'type' => 'error',
        'message' => 'launch-scrapper.vbs file not found at: ' . $vbsFilePath
    ]);
    sendMessage([
        'type' => 'done',
        'message' => 'Process finished.'
    ]);
    exit;
}

sendMessage([
    'type' => 'info',
    'message' => 'Found VBS launcher at: ' . $vbsFilePath
]);

// Execute the VBS file using WScript
$command = 'wscript.exe "' . $vbsFilePath . '"';
pclose(popen($command, 'r'));

sendMessage([
    'type' => 'success',
    'message' => 'Command prompt opened successfully!'
]);

sendMessage([
    'type' => 'success',
    'message' => 'The scrapper is now running in a separate window.'
]);

sendMessage([
    'type' => 'info',
    'message' => 'You can close this browser tab - the scrapper will continue running.'
]);

sendMessage([
    'type' => 'done',
    'message' => 'Process finished.'
]);
