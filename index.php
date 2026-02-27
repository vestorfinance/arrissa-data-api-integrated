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

// Handle URL fetch API (requires api_key)
if ($uri === '/api/url-api') {
    include __DIR__ . '/url-api-v1/url-api.php';
    exit;
}

// Handle sync events API (requires auth)
if ($uri === '/api/sync-events') {
    include __DIR__ . '/public/api/sync-events.php';
    exit;
}

// Handle update events API — syncs from last known update + all future (requires auth)
if ($uri === '/api/update-events') {
    include __DIR__ . '/public/api/update-events.php';
    exit;
}

// Handle run-cron API — trigger smart event sync via HTTP (api_key auth, usable from n8n etc.)
if ($uri === '/api/run-cron') {
    include __DIR__ . '/public/api/run-cron.php';
    exit;
}

// Handle truncate events API (requires auth)
if ($uri === '/api/truncate-events') {
    include __DIR__ . '/public/api/truncate-events.php';
    exit;
}

// Handle economic events API (no auth required)
if ($uri === '/api/economic-events') {
    include __DIR__ . '/news-api-v1/economic-events.php';
    exit;
}

// Handle TMP categories API (requires api_key)
if ($uri === '/api/tmp-categories') {
    include __DIR__ . '/public/api/tmp-categories.php';
    exit;
}

// Handle TMP tool capabilities API (requires api_key)
if ($uri === '/api/tmp-tool-capabilities') {
    include __DIR__ . '/public/api/tmp-tool-capabilities.php';
    exit;
}

// Handle TMP get-tool API (requires api_key + search_phrase)
if ($uri === '/api/tmp-get-tool') {
    include __DIR__ . '/public/api/tmp-get-tool.php';
    exit;
}

// Handle TMP admin CRUD API (requires session auth)
if ($uri === '/api/tmp-admin') {
    include __DIR__ . '/public/api/tmp-admin.php';
    exit;
}

// Front page disabled — redirect to login
if ($uri === '/') {
    header('Location: /login');
    exit;
}

// Require authentication for all other pages
require_once __DIR__ . '/app/Auth.php';
Auth::check();

// Route handling
switch ($uri) {
    case '/dashboard':
        $page = 'dashboard';
        break;
    case '/market-data-api-guide':
        $page = 'market-data-api-guide';
        break;
    case '/news-api-guide':
        $page = 'news-api-guide';
        break;
    case '/similar-scene-api-guide':
        $page = 'similar-scene-api-guide';
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
    case '/quarters-theory-api-guide':
        $page = 'quarters-theory-api-guide';
        break;
    case '/tma-cg-api-guide':
        $page = 'tma-cg-api-guide';
        break;
    case '/url-api-guide':
        $page = 'url-api-guide';
        break;
    case '/tmp-guide':
        $page = 'tmp-guide';
        break;
    case '/tmp-manage':
        $page = 'tmp-manage';
        break;
    case '/download-eas':
        $page = 'download-eas';
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
    case '/economic-calendar':
        $page = 'economic-calendar';
        break;
    case '/manage-events':
        $page = 'manage-events';
        break;
    default:
        $page = 'dashboard';
}

// Include the view
include __DIR__ . "/resources/views/{$page}.php";
