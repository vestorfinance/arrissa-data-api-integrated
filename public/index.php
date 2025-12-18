<?php

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($value));
    }
}

// Simple router for the dashboard
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_method = $_SERVER['REQUEST_METHOD'];

// Route handling
switch ($request_uri) {
    case '/':
    case '/dashboard':
        $page = 'dashboard';
        break;
    case '/settings':
        if ($request_method === 'POST') {
            require_once __DIR__ . '/../app/Controllers/SettingsController.php';
            $controller = new SettingsController();
            
            // Check if it's an API key refresh request
            if (isset($_POST['action']) && $_POST['action'] === 'refresh_api_key') {
                $controller->refreshApiKey();
                header('Location: /settings?refreshed=1');
                exit;
            }
            
            // Otherwise, update settings
            $controller->updateMultiple($_POST);
            header('Location: /settings?success=1');
            exit;
        }
        $page = 'settings';
        break;
    default:
        $page = 'dashboard';
}

// Load the view
$viewFile = __DIR__ . '/../resources/views/' . $page . '.php';
if (file_exists($viewFile)) {
    include $viewFile;
} else {
    echo "View not found: $page";
}
