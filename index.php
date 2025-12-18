<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simple router
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Handle login and auth routes
if ($uri === '/login') {
    include __DIR__ . '/public/login.php';
    exit;
}

if ($uri === '/auth/login') {
    include __DIR__ . '/public/auth/login.php';
    exit;
}

if ($uri === '/auth/logout') {
    include __DIR__ . '/public/auth/logout.php';
    exit;
}

// Handle settings actions
if ($uri === '/settings/update-app-name') {
    include __DIR__ . '/public/settings/update-app-name.php';
    exit;
}

if ($uri === '/settings/refresh-api-key') {
    include __DIR__ . '/public/settings/refresh-api-key.php';
    exit;
}

if ($uri === '/settings/change-password') {
    include __DIR__ . '/public/settings/change-password.php';
    exit;
}

if ($uri === '/settings/update-base-url') {
    include __DIR__ . '/public/settings/update-base-url.php';
    exit;
}

// Handle search API (no auth required)
if ($uri === '/api/search') {
    include __DIR__ . '/public/api/search.php';
    exit;
}

// Handle events scrapper API (requires auth)
if ($uri === '/api/run-events-scrapper') {
    include __DIR__ . '/public/api/run-events-scrapper.php';
    exit;
}

// Require authentication for all other pages
require_once __DIR__ . '/app/Auth.php';
Auth::check();

// Route handling
switch ($uri) {
    case '/':
    case '/dashboard':
        $page = 'dashboard';
        break;
    case '/market-data-api-guide':
        $page = 'market-data-api-guide';
        break;
    case '/news-api-guide':
        $page = 'news-api-guide';
        break;
    case '/event-id-reference':
        $page = 'event-id-reference';
        break;
    case '/chart-image-api-guide':
        $page = 'chart-image-api-guide';
        break;
    case '/orders-api-guide':
        $page = 'orders-api-guide';
        break;
    case '/symbol-info-api-guide':
        $page = 'symbol-info-api-guide';
        break;
    case '/download-eas':
        $page = 'download-eas';
        break;
    case '/run-events-scrapper':
        $page = 'run-events-scrapper';
        break;
    case '/markets':
        $page = 'markets';
        break;
    case '/portfolio':
        $page = 'portfolio';
        break;
    case '/transactions':
        $page = 'transactions';
        break;
    case '/news':
        $page = 'news';
        break;
    case '/calculator':
        $page = 'calculator';
        break;
    case '/settings':
        $page = 'settings';
        break;
    default:
        $page = 'dashboard';
}

// Include the view
include __DIR__ . "/resources/views/{$page}.php";
