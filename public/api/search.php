<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    require_once __DIR__ . '/../../app/Database.php';

    $query = $_GET['q'] ?? '';

    if (empty($query)) {
        echo json_encode(['results' => []]);
        exit;
    }

    $db = Database::getInstance()->getConnection();
    $results = [];
    $searchTerm = '%' . $query . '%';

    // Search Settings
    try {
        $stmt = $db->prepare("SELECT key, value FROM settings WHERE key LIKE ? OR value LIKE ?");
        $stmt->execute([$searchTerm, $searchTerm]);
        $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($settings as $setting) {
            $results[] = [
                'type' => 'setting',
                'title' => ucwords(str_replace('_', ' ', $setting['key'])),
                'description' => $setting['value'],
                'url' => '/settings',
                'icon' => 'settings'
            ];
        }
    } catch (Exception $e) {}

    // Search Economic Events
    try {
        $stmt = $db->prepare("SELECT DISTINCT event_name, consistent_event_id, currencies FROM economic_events WHERE event_name LIKE ? OR currencies LIKE ? OR consistent_event_id LIKE ? LIMIT 20");
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($events as $event) {
            $results[] = [
                'type' => 'event',
                'title' => $event['event_name'],
                'description' => 'Event ID: ' . $event['consistent_event_id'] . ' | Currencies: ' . $event['currencies'],
                'url' => '/event-id-reference',
                'icon' => 'calendar'
            ];
        }
    } catch (Exception $e) {}

    // Search Pages/Documentation
    $pages = [
        [
            'title' => 'Market Data API',
            'keywords' => 'market data api ohlc candle tick volume timeframe m1 m5 m15 m30 h1 h4 d1 w1 mn1 mt5 metatrader expert advisor ea range query today last-hour last-7days chart technical indicators open high low close',
            'description' => 'Comprehensive MT5 Market Data API documentation',
            'url' => '/market-data-api-guide',
            'icon' => 'trending-up'
        ],
        [
            'title' => 'Orders API',
            'keywords' => 'orders api trading mt5 buy sell close position pending limit stop market sl tp break even trailing stop loss take profit history profit loss calculation volume lots magic number',
            'description' => 'Complete MT5 trading operations documentation',
            'url' => '/orders-api-guide',
            'icon' => 'shopping-cart'
        ],
        [
            'title' => 'Symbol Info API',
            'keywords' => 'symbol info api analysis behavior pattern statistics average high low body wick bullish bearish candle timeframe backtesting historical pretend date time m5 m15 m30 h1 h4 h8 h12 d1 w1 monthly',
            'description' => 'Advanced symbol behavior analysis & statistics',
            'url' => '/symbol-info-api-guide',
            'icon' => 'bar-chart-2'
        ],
        [
            'title' => 'News API',
            'keywords' => 'news api economic events calendar forex announcements impact high medium low sentiment date range currency filter database sqlite scraping',
            'description' => 'Economic events and news data API',
            'url' => '/news-api-guide',
            'icon' => 'file-text'
        ],
        [
            'title' => 'Event ID Reference',
            'keywords' => 'event id reference consistent economic calendar forex news usd eur gbp jpy aud cad chf nzd currencies nfp gdp cpi inflation interest rate fomc fed ecb boe pmi manufacturing services retail sales employment unemployment',
            'description' => 'Complete event ID lookup table',
            'url' => '/event-id-reference',
            'icon' => 'list'
        ],
        [
            'title' => 'Chart Image API',
            'keywords' => 'chart image api screenshot capture png jpg jpeg svg canvas visualization graph',
            'description' => 'Chart image generation endpoint',
            'url' => '/chart-image-api-guide',
            'icon' => 'image'
        ],
        [
            'title' => 'Download Expert Advisors',
            'keywords' => 'download expert advisor ea mq5 ex5 mt5 metatrader install setup configuration json library jason.mqh compile source code market data orders symbol info localhost 127.0.0.1 autotrading dll imports allowed urls',
            'description' => 'MT5 Expert Advisors installation and downloads',
            'url' => '/download-eas',
            'icon' => 'download'
        ],
        [
            'title' => 'Settings',
            'keywords' => 'settings configuration api key refresh base url app name password change update account preferences database sqlite wamp localhost',
            'description' => 'API configuration and account settings',
            'url' => '/settings',
            'icon' => 'settings'
        ]
    ];

    foreach ($pages as $page) {
        if (stripos($page['title'], $query) !== false || stripos($page['description'], $query) !== false || stripos($page['keywords'], $query) !== false) {
            $results[] = [
                'type' => 'page',
                'title' => $page['title'],
                'description' => $page['description'],
                'url' => $page['url'],
                'icon' => $page['icon']
            ];
        }
    }

    // Search common terms
    $commonTerms = [
        'localhost' => ['title' => 'Download EAs - Localhost Configuration', 'description' => 'Configure EAs to use http://127.0.0.1', 'url' => '/download-eas', 'icon' => 'download'],
        '127.0.0.1' => ['title' => 'Download EAs - Localhost Configuration', 'description' => 'Configure EAs to use http://127.0.0.1', 'url' => '/download-eas', 'icon' => 'download'],
        'ea' => ['title' => 'Download Expert Advisors', 'description' => 'MT5 Expert Advisors for all APIs', 'url' => '/download-eas', 'icon' => 'download'],
        'expert advisor' => ['title' => 'Download Expert Advisors', 'description' => 'MT5 Expert Advisors for all APIs', 'url' => '/download-eas', 'icon' => 'download'],
        'api key' => ['title' => 'Settings - API Key', 'description' => 'Refresh and manage your API key', 'url' => '/settings', 'icon' => 'settings'],
        'password' => ['title' => 'Settings - Change Password', 'description' => 'Update your account password', 'url' => '/settings', 'icon' => 'settings'],
        'database' => ['title' => 'Settings - Database Configuration', 'description' => 'SQLite database settings', 'url' => '/settings', 'icon' => 'settings']
    ];

    foreach ($commonTerms as $term => $data) {
        if (stripos($term, $query) !== false || stripos($query, $term) !== false) {
            $alreadyExists = false;
            foreach ($results as $result) {
                if ($result['url'] === $data['url'] && $result['title'] === $data['title']) {
                    $alreadyExists = true;
                    break;
                }
            }
            if (!$alreadyExists) {
                $results[] = array_merge(['type' => 'suggestion'], $data);
            }
        }
    }

    echo json_encode(['results' => $results]);

} catch (Exception $e) {
    echo json_encode(['results' => [], 'error' => $e->getMessage()]);
}
