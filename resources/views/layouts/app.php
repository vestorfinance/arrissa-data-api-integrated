<!DOCTYPE html>
<html lang="en">
<head>
<?php
require_once __DIR__ . '/../../../app/_Qvr9mBx3.php';
require_once __DIR__ . '/../../../app/_Tz8wKpN4.php';
_Qvr9mBx3::_v();
_Tz8wKpN4::_v();
?>
    <meta charset="UTF-8">
    <!-- v2026.02.28 -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Arrissa Data API'; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#ffffff',
                        dark: {
                            100: '#0a0a0a',
                            200: '#050505',
                            300: '#000000',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        :root {
            --bg-primary: #0f0f0f;
            --bg-secondary: #1a1a1a;
            --bg-tertiary: #2d2d2d;
            --text-primary: #ffffff;
            --text-secondary: #a0a0a0;
            --accent: #4f46e5;
            --accent-hover: #6366f1;
            --border: #3a3a3a;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --card-bg: #1f1f1f;
            --input-bg: #262626;
            --input-border: #404040;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--bg-primary);
            transition: background-color 0.3s, color 0.3s;
            overflow: hidden;
            position: fixed;
            width: 100%;
            height: 100%;
            font-size: 16px;
        }
        body.light-theme {
            --bg-primary: #ffffff;
            --bg-secondary: #f9fafb;
            --bg-tertiary: #f3f4f6;
            --text-primary: #111827;
            --text-secondary: #6b7280;
            --border: #e5e7eb;
            --card-bg: #ffffff;
            --input-bg: #f9fafb;
            --input-border: #d1d5db;
        }
        .sidebar-link {
            transition: all 0.2s;
        }
        .sidebar-link:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        .sidebar-link.active {
            background: rgba(255, 255, 255, 0.08);
        }
        .theme-toggle {
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: background-color 0.2s;
        }
        .theme-toggle:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        /* Global pill styles */
        button, .btn, input[type="submit"] {
            border-radius: 9999px !important;
        }
        input:not([type="checkbox"]):not([type="radio"]) {
            border-radius: 9999px !important;
        }
        .card, .api-card {
            border-radius: 24px !important;
        }

        /* Custom Thin Scrollbars - Sitewide */
        * {
            scrollbar-width: thin;
            scrollbar-color: var(--input-border) var(--bg-secondary);
        }
        
        *::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        *::-webkit-scrollbar-track {
            background: var(--bg-secondary);
        }
        
        *::-webkit-scrollbar-thumb {
            background-color: var(--input-border);
            border-radius: 4px;
            border: 2px solid var(--bg-secondary);
        }
        
        *::-webkit-scrollbar-thumb:hover {
            background-color: var(--border);
        }
        
        /* Scrollbar for code blocks */
        pre::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        pre::-webkit-scrollbar-thumb {
            background-color: var(--border);
            border-radius: 3px;
        }

        /* Mobile Header Bar */
        .mobile-header {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 64px;
            background-color: var(--bg-primary);
            border-bottom: 1px solid var(--border);
            z-index: 997;
            align-items: center;
            padding: 0 20px;
        }
        
        .hamburger-btn {
            background: none;
            border: none;
            padding: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: background-color 0.2s;
        }
        .hamburger-btn:hover {
            background-color: var(--bg-secondary);
        }
        
        .mobile-header-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-left: 12px;
        }
        
        .mobile-header-logo-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .mobile-header-title {
            font-weight: 600;
            font-size: 16px;
            color: var(--text-primary);
        }
        
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 998;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .sidebar-overlay.active {
            display: block;
            opacity: 1;
        }

        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .mobile-header {
                display: flex;
            }
            
            aside {
                position: fixed;
                left: -320px;
                top: 0;
                height: 100%;
                z-index: 999;
                transition: left 0.3s ease-in-out;
            }
            
            aside.mobile-open {
                left: 0;
            }
            
            main {
                margin-left: 0 !important;
                padding-top: 64px;
            }
            
            /* Adjust main content padding for mobile */
            .p-8 {
                padding: 1.5rem !important;
            }
            
            /* Make cards single column on mobile */
            .grid-cols-1.md\:grid-cols-2.lg\:grid-cols-3 {
                grid-template-columns: 1fr !important;
            }
            
            /* Adjust search bar on mobile */
            .max-w-2xl {
                max-width: 100% !important;
            }
        }

        /* Tablet adjustments */
        @media (max-width: 1024px) and (min-width: 769px) {
            .grid-cols-1.md\:grid-cols-2.lg\:grid-cols-3 {
                grid-template-columns: repeat(2, 1fr) !important;
            }
        }

        /* Update notification banner */
        #update-banner {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 9998;
            background: linear-gradient(90deg, rgba(79,70,229,0.97), rgba(99,102,241,0.97));
            backdrop-filter: blur(8px);
            border-bottom: 1px solid rgba(255,255,255,0.12);
            padding: 10px 20px;
            align-items: center;
            justify-content: center;
            gap: 16px;
            font-size: 0.875rem;
            color: #fff;
            animation: slideDown 0.3s ease-out;
        }
        #update-banner.visible {
            display: flex;
        }
        @keyframes slideDown {
            from { transform: translateY(-100%); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }
        #update-pull-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 16px;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 700;
            background: #fff;
            color: var(--accent);
            border: none;
            cursor: pointer;
            transition: opacity 0.2s;
            text-decoration: none;
        }
        #update-pull-btn:hover { opacity: 0.88; }
        #update-pull-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        #update-dismiss-btn {
            background: none;
            border: none;
            color: rgba(255,255,255,0.6);
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            border-radius: 50%;
            transition: color 0.15s;
        }
        #update-dismiss-btn:hover { color: #fff; }
        /* Push page down when banner is visible */
        body.has-update-banner aside,
        body.has-update-banner .mobile-header {
            top: 42px !important;
        }
        body.has-update-banner main {
            padding-top: 42px;
        }
        #page-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: var(--bg-primary);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.3s ease-out;
        }
        #page-loader.hidden {
            opacity: 0;
            pointer-events: none;
        }
        .loader-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid var(--border);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body style="background-color: var(--bg-primary);">
    <!-- Global Page Loader -->
    <div id="page-loader">
        <div class="loader-spinner"></div>
        <p style="color: var(--text-secondary); margin-top: 20px; font-size: 14px;">Loading...</p>
    </div>

    <!-- Update Notification Banner -->
    <div id="update-banner">
        <i data-feather="download-cloud" style="width:16px;height:16px;flex-shrink:0;"></i>
        <span id="update-banner-text">A new update is available.</span>
        <button id="update-pull-btn" onclick="doPullUpdate()">
            <i data-feather="download" style="width:13px;height:13px;"></i>
            Pull Update
        </button>
        <button id="update-dismiss-btn" onclick="dismissUpdateBanner()" title="Dismiss">
            <i data-feather="x" style="width:16px;height:16px;"></i>
        </button>
    </div>

    <!-- Mobile Header Bar -->
    <header class="mobile-header">
        <button class="hamburger-btn" onclick="toggleMobileSidebar()" aria-label="Toggle menu">
            <i data-feather="menu" style="width: 24px; height: 24px; color: var(--text-primary);"></i>
        </button>
        <div class="mobile-header-logo">
            <div class="mobile-header-logo-circle" style="background-color: var(--accent); display: flex; align-items: center; justify-content: center;">
<svg style="width: 22px; height: 22px; color: #fff; fill: currentColor;" viewBox="0 0 7000 7000" xmlns="http://www.w3.org/2000/svg">
                    <g>
                        <path d="M3534.57 2921.26l509.33 278.51 0 600.85 -543.85 297.38 -543.84 -297.38 0 -600.85 543.84 -297.38 34.51 18.87zm166.69 255.62l-201.2 -110.02 -399.01 218.18 0 430.3 399.01 218.19 399.01 -218.19 0 -430.3 -197.81 -108.16z"></path>
                        <path d="M3206.76 1423.91l672.75 0 0 1366.1 -745.17 0 0 -1366.1 72.42 0zm527.92 144.83l-455.5 0 0 1076.43 455.5 0 0 -1076.43z"></path>
                        <polygon points="3436.12,1496.03 3436.12,899.79 3580.96,899.79 3580.96,1496.03"></polygon>
                        <polygon points="3432.32,3004.16 3432.32,2675.89 3577.15,2675.89 3577.15,3004.16"></polygon>
                        <path d="M3203.34 5576.08l672.75 0 0 -1366.09 -745.17 0 0 1366.09 72.42 0zm527.92 -144.83l-455.5 0 0 -1076.43 455.5 0 0 -1076.43z"></path>
                        <polygon points="3432.7,5503.96 3432.7,6100.2 3577.53,6100.2 3577.53,5503.96"></polygon>
                        <polygon points="3428.89,3986.69 3428.89,4314.95 3573.73,4314.95 3573.73,3986.69"></polygon>
                        <path d="M5172.55 4811.44l336.37 -582.62 -1183.07 -683.05 -372.59 645.33 1183.07 683.05 36.21 -62.72zm138.53 -529.61l-227.75 394.48 -932.21 -538.21 227.75 -394.48 932.21 538.21z"></path>
                        <polygon points="5224.77,4576.75 5741.13,4874.87 5813.54,4749.44 5297.19,4451.32"></polygon>
                        <polygon points="3908.87,3821.41 4193.16,3985.54 4265.57,3860.11 3981.29,3695.98"></polygon>
                        <path d="M5479.29 2714.84l-336.37 -582.62 -1183.07 683.05 372.58 645.33 1183.07 -683.05 -36.21 -62.71zm-389.39 -384.77l227.75 394.47 -932.21 538.21 -227.75 -394.47 932.21 -538.21z"></path>
                        <polygon points="5302.15,2552.27 5818.51,2254.15 5746.09,2128.72 5229.73,2426.84"></polygon>
                        <polygon points="3990.05,3314.2 4274.34,3150.07 4201.92,3024.64 3917.63,3188.77"></polygon>
                        <path d="M1829.86 4820.48l-336.38 -582.62 1183.07 -683.05 372.59 645.33 -1183.07 683.05 -36.21 -62.72zm-138.53 -529.61l227.75 394.47 932.21 -538.21 -227.75 -394.47 -932.21 538.21z"></path>
                        <polygon points="1777.64,4585.79 1261.28,4883.91 1188.87,4758.48 1705.22,4460.36"></polygon>
                        <polygon points="3093.54,3830.44 2809.25,3994.57 2736.83,3869.15 3021.12,3705.01"></polygon>
                        <path d="M1520.7 2723.73l336.38 -582.62 1183.07 683.05 -372.58 645.33 -1183.07 -683.05 36.21 -62.72zm389.39 -384.77l-227.75 394.47 932.21 538.22 227.75 -394.48 -932.21 -538.21z"></path>
                        <polygon points="1697.84,2561.16 1181.48,2263.04 1253.9,2137.61 1770.26,2435.73"></polygon>
                        <polygon points="3009.94,3323.09 2725.65,3158.96 2798.07,3033.53 3082.35,3197.66"></polygon>
                    </g>
                </svg>
            </div>
            <span class="mobile-header-title">Arrissa Data API</span>
        </div>
    </header>

    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" onclick="closeMobileSidebar()"></div>

    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside id="sidebar" class="w-80 flex flex-col" style="background-color: var(--bg-primary); border-right: 1px solid var(--border);">
            <!-- Logo -->
            <div class="p-7" style="border-bottom: 1px solid var(--border);">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center" style="background-color: var(--accent);">
                        <svg class="w-30 h-30 text-white" fill="currentColor" viewBox="0 0 7000 7000" xmlns="http://www.w3.org/2000/svg">
                            <g>
                                <path d="M3534.57 2921.26l509.33 278.51 0 600.85 -543.85 297.38 -543.84 -297.38 0 -600.85 543.84 -297.38 34.51 18.87zm166.69 255.62l-201.2 -110.02 -399.01 218.18 0 430.3 399.01 218.19 399.01 -218.19 0 -430.3 -197.81 -108.16z"></path>
                                <path d="M3206.76 1423.91l672.75 0 0 1366.1 -745.17 0 0 -1366.1 72.42 0zm527.92 144.83l-455.5 0 0 1076.43 455.5 0 0 -1076.43z"></path>
                                <polygon points="3436.12,1496.03 3436.12,899.79 3580.96,899.79 3580.96,1496.03"></polygon>
                                <polygon points="3432.32,3004.16 3432.32,2675.89 3577.15,2675.89 3577.15,3004.16"></polygon>
                                <path d="M3203.34 5576.08l672.75 0 0 -1366.09 -745.17 0 0 1366.09 72.42 0zm527.92 -144.83l-455.5 0 0 -1076.43 455.5 0 0 -1076.43z"></path>
                                <polygon points="3432.7,5503.96 3432.7,6100.2 3577.53,6100.2 3577.53,5503.96"></polygon>
                                <polygon points="3428.89,3986.69 3428.89,4314.95 3573.73,4314.95 3573.73,3986.69"></polygon>
                                <path d="M5172.55 4811.44l336.37 -582.62 -1183.07 -683.05 -372.59 645.33 1183.07 683.05 36.21 -62.72zm138.53 -529.61l-227.75 394.48 -932.21 -538.21 227.75 -394.48 932.21 538.21z"></path>
                                <polygon points="5224.77,4576.75 5741.13,4874.87 5813.54,4749.44 5297.19,4451.32"></polygon>
                                <polygon points="3908.87,3821.41 4193.16,3985.54 4265.57,3860.11 3981.29,3695.98"></polygon>
                                <path d="M5479.29 2714.84l-336.37 -582.62 -1183.07 683.05 372.58 645.33 1183.07 -683.05 -36.21 -62.71zm-389.39 -384.77l227.75 394.47 -932.21 538.21 -227.75 -394.47 932.21 -538.21z"></path>
                                <polygon points="5302.15,2552.27 5818.51,2254.15 5746.09,2128.72 5229.73,2426.84"></polygon>
                                <polygon points="3990.05,3314.2 4274.34,3150.07 4201.92,3024.64 3917.63,3188.77"></polygon>
                                <path d="M1829.86 4820.48l-336.38 -582.62 1183.07 -683.05 372.59 645.33 -1183.07 683.05 -36.21 -62.72zm-138.53 -529.61l227.75 394.47 932.21 -538.21 -227.75 -394.47 -932.21 538.21z"></path>
                                <polygon points="1777.64,4585.79 1261.28,4883.91 1188.87,4758.48 1705.22,4460.36"></polygon>
                                <polygon points="3093.54,3830.44 2809.25,3994.57 2736.83,3869.15 3021.12,3705.01"></polygon>
                                <path d="M1520.7 2723.73l336.38 -582.62 1183.07 683.05 -372.58 645.33 -1183.07 -683.05 36.21 -62.72zm389.39 -384.77l-227.75 394.47 932.21 538.22 227.75 -394.48 -932.21 -538.21z"></path>
                                <polygon points="1697.84,2561.16 1181.48,2263.04 1253.9,2137.61 1770.26,2435.73"></polygon>
                                <polygon points="3009.94,3323.09 2725.65,3158.96 2798.07,3033.53 3082.35,3197.66"></polygon>
                            </g>
                        </svg>
                    </div>
                    <span class="font-semibold text-lg tracking-tight" style="color: var(--text-primary);">Arrissa Data API</span>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-4 py-3" style="overflow-y: auto; overflow-x: hidden;">
                <a href="/" class="sidebar-link <?php echo ($page ?? '') == 'dashboard' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-full mb-2" style="color: <?php echo ($page ?? '') == 'dashboard' ? 'var(--text-primary)' : 'var(--text-secondary)'; ?>;">
                    <i data-feather="grid" style="width: 20px; height: 20px;"></i>
                    <span class="text-base font-medium">Dashboard</span>
                </a>
                <a href="/market-data-api-guide" class="sidebar-link <?php echo ($page ?? '') == 'market-data-api' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-full mb-2" style="color: <?php echo ($page ?? '') == 'market-data-api' ? 'var(--text-primary)' : 'var(--text-secondary)'; ?>;">
                    <i data-feather="trending-up" style="width: 20px; height: 20px;"></i>
                    <span class="text-base font-medium">Market Data API Guide</span>
                </a>
                <?php
                    $newsSubPages = ['news-api-guide', 'manage-events', 'similar-scene-api-guide', 'event-id-reference'];
                    $newsGroupOpen = in_array($page ?? '', $newsSubPages);
                ?>
                <!-- News API group -->
                <div class="mb-1">
                    <button onclick="toggleNavGroup('news-group')" class="sidebar-link w-full flex items-center justify-between px-4 py-3 rounded-full <?php echo $newsGroupOpen ? 'active' : ''; ?>" style="color: <?php echo $newsGroupOpen ? 'var(--text-primary)' : 'var(--text-secondary)'; ?>; background: none; border: none; cursor: pointer;">
                        <span class="flex items-center space-x-3">
                            <i data-feather="file-text" style="width: 20px; height: 20px;"></i>
                            <span class="text-base font-medium">News API Guide</span>
                        </span>
                        <i data-feather="chevron-down" id="news-group-chevron" style="width: 16px; height: 16px; transition: transform 0.2s; <?php echo $newsGroupOpen ? 'transform: rotate(180deg);' : ''; ?>"></i>
                    </button>
                    <div id="news-group" style="<?php echo $newsGroupOpen ? '' : 'display:none;'; ?> padding-left: 1rem; margin-top: 2px; border-left: 2px solid var(--border); margin-left: 1.5rem;">
                        <a href="/news-api-guide" class="sidebar-link <?php echo ($page ?? '') == 'news-api-guide' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-2 rounded-full mb-1" style="color: <?php echo ($page ?? '') == 'news-api-guide' ? 'var(--text-primary)' : 'var(--text-secondary)'; ?>; font-size: 0.9rem;">
                            <i data-feather="book-open" style="width: 16px; height: 16px;"></i>
                            <span class="font-medium">API Guide</span>
                        </a>
                        <a href="/manage-events" class="sidebar-link <?php echo ($page ?? '') == 'manage-events' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-2 rounded-full mb-1" style="color: <?php echo ($page ?? '') == 'manage-events' ? 'var(--text-primary)' : 'var(--text-secondary)'; ?>; font-size: 0.9rem;">
                            <i data-feather="calendar" style="width: 16px; height: 16px;"></i>
                            <span class="font-medium">Manage Events</span>
                        </a>
                        <a href="/similar-scene-api-guide" class="sidebar-link <?php echo ($page ?? '') == 'similar-scene-api-guide' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-2 rounded-full mb-1" style="color: <?php echo ($page ?? '') == 'similar-scene-api-guide' ? 'var(--text-primary)' : 'var(--text-secondary)'; ?>; font-size: 0.9rem;">
                            <i data-feather="layers" style="width: 16px; height: 16px;"></i>
                            <span class="font-medium">Similar Scene API</span>
                        </a>
                        <a href="/event-id-reference" class="sidebar-link <?php echo ($page ?? '') == 'event-id-reference' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-2 rounded-full mb-1" style="color: <?php echo ($page ?? '') == 'event-id-reference' ? 'var(--text-primary)' : 'var(--text-secondary)'; ?>; font-size: 0.9rem;">
                            <i data-feather="hash" style="width: 16px; height: 16px;"></i>
                            <span class="font-medium">Event ID Reference</span>
                        </a>
                    </div>
                </div>
                <a href="/chart-image-api-guide" class="sidebar-link <?php echo ($page ?? '') == 'chart-image-api' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-full mb-2" style="color: <?php echo ($page ?? '') == 'chart-image-api' ? 'var(--text-primary)' : 'var(--text-secondary)'; ?>;">
                    <i data-feather="image" style="width: 20px; height: 20px;"></i>
                    <span class="text-base font-medium">Chart Image API Guide</span>
                </a>
                <a href="/orders-api-guide" class="sidebar-link <?php echo ($page ?? '') == 'orders-api' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-full mb-2" style="color: <?php echo ($page ?? '') == 'orders-api' ? 'var(--text-primary)' : 'var(--text-secondary)'; ?>;">
                    <i data-feather="shopping-cart" style="width: 20px; height: 20px;"></i>
                    <span class="text-base font-medium">Orders API Guide</span>
                </a>
                <a href="/symbol-info-api-guide" class="sidebar-link <?php echo ($page ?? '') == 'symbol-info-api' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-full mb-2" style="color: <?php echo ($page ?? '') == 'symbol-info-api' ? 'var(--text-primary)' : 'var(--text-secondary)'; ?>;">
                    <i data-feather="bar-chart-2" style="width: 20px; height: 20px;"></i>
                    <span class="text-base font-medium">Symbol Info API Guide</span>
                </a>
                <a href="/quarters-theory-api-guide" class="sidebar-link <?php echo ($page ?? '') == 'quarters-theory-api-guide' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-full mb-2" style="color: <?php echo ($page ?? '') == 'quarters-theory-api-guide' ? 'var(--text-primary)' : 'var(--text-secondary)'; ?>;">
                    <i data-feather="target" style="width: 20px; height: 20px;"></i>
                    <span class="text-base font-medium">Quarters Theory API Guide</span>
                </a>
                <a href="/url-api-guide" class="sidebar-link <?php echo ($page ?? '') == 'url-api-guide' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-full mb-2" style="color: <?php echo ($page ?? '') == 'url-api-guide' ? 'var(--text-primary)' : 'var(--text-secondary)'; ?>;">
                    <i data-feather="globe" style="width: 20px; height: 20px;"></i>
                    <span class="text-base font-medium">URL API Guide</span>
                </a>
                <?php
                    $tmpSubPages = ['tmp-guide', 'tmp-manage'];
                    $tmpGroupOpen = in_array($page ?? '', $tmpSubPages);
                ?>
                <div class="mb-1">
                    <button onclick="toggleNavGroup('tmp-group')" class="sidebar-link w-full flex items-center justify-between px-4 py-3 rounded-full <?php echo $tmpGroupOpen ? 'active' : ''; ?>" style="color: <?php echo $tmpGroupOpen ? 'var(--text-primary)' : 'var(--text-secondary)'; ?>; background: none; border: none; cursor: pointer;">
                        <span class="flex items-center space-x-3">
                            <i data-feather="cpu" style="width: 20px; height: 20px;"></i>
                            <span class="text-base font-medium">TMP Protocol</span>
                        </span>
                        <i data-feather="chevron-down" id="tmp-group-chevron" style="width: 16px; height: 16px; transition: transform 0.2s; <?php echo $tmpGroupOpen ? 'transform: rotate(180deg);' : ''; ?>"></i>
                    </button>
                    <div id="tmp-group" style="<?php echo $tmpGroupOpen ? '' : 'display:none;'; ?> padding-left: 1rem; margin-top: 2px; border-left: 2px solid var(--border); margin-left: 1.5rem;">
                        <a href="/tmp-guide" class="sidebar-link <?php echo ($page ?? '') == 'tmp-guide' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-2 rounded-full mb-1" style="color: <?php echo ($page ?? '') == 'tmp-guide' ? 'var(--text-primary)' : 'var(--text-secondary)'; ?>; font-size: 0.9rem;">
                            <i data-feather="book-open" style="width: 16px; height: 16px;"></i>
                            <span class="font-medium">TMP Guide</span>
                        </a>
                        <a href="/tmp-manage" class="sidebar-link <?php echo ($page ?? '') == 'tmp-manage' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-2 rounded-full mb-1" style="color: <?php echo ($page ?? '') == 'tmp-manage' ? 'var(--text-primary)' : 'var(--text-secondary)'; ?>; font-size: 0.9rem;">
                            <i data-feather="sliders" style="width: 16px; height: 16px;"></i>
                            <span class="font-medium">TMP Manage</span>
                        </a>
                    </div>
                </div>
                <a href="/download-eas" class="sidebar-link <?php echo ($page ?? '') == 'download-eas' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-full mb-2" style="color: <?php echo ($page ?? '') == 'download-eas' ? 'var(--text-primary)' : 'var(--text-secondary)'; ?>;">
                    <i data-feather="download" style="width: 20px; height: 20px;"></i>
                    <span class="text-base font-medium">Download EAs</span>
                </a>
                <a href="/brokers" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-full mb-2" style="color: var(--accent); border: 1px solid var(--accent); background: transparent; <?php echo ($page ?? '') == 'brokers' ? 'background: rgba(79,70,229,0.08) !important;' : ''; ?>">
                    <i data-feather="briefcase" style="width: 20px; height: 20px;"></i>
                    <span class="text-base font-medium">Brokers</span>
                </a>
                <?php _Tz8wKpN4::_r(); ?>
            </nav>

            <!-- Settings -->
            <div class="p-4" style="border-top: 1px solid var(--border);">
                <div class="flex items-center justify-between mb-3">
                    <a href="/settings" class="sidebar-link <?php echo ($page ?? '') == 'settings' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-full flex-1" style="color: <?php echo ($page ?? '') == 'settings' ? 'var(--text-primary)' : 'var(--text-secondary)'; ?>;">
                        <i data-feather="settings" style="width: 20px; height: 20px;"></i>
                        <span class="text-base font-medium">Settings</span>
                    </a>
                    <div class="theme-toggle" onclick="toggleTheme()" title="Toggle theme">
                        <i data-feather="moon" id="theme-icon" style="width: 20px; height: 20px; color: var(--text-secondary);"></i>
                    </div>
                </div>
                <a href="/auth/logout" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-full" style="color: var(--text-secondary);">
                    <i data-feather="log-out" style="width: 20px; height: 20px;"></i>
                    <span class="text-base font-medium">Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto flex flex-col" style="background-color: var(--bg-primary);">
            <div class="flex-1">
                <?php echo $content ?? ''; ?>
            </div>
            <?php _Qvr9mBx3::_r(); ?>
        </main>
    </div>
    <script>
        feather.replace();

        // ── Update check ──────────────────────────────────────────────
        const UPDATE_DISMISS_KEY = 'update_dismissed_head';

        async function checkForUpdate() {
            try {
                const res = await fetch('/api/check-update');
                if (!res.ok) return;
                const data = await res.json();
                if (!data.update_available) return;

                // Don't re-show if user already dismissed this exact remote commit
                const dismissed = localStorage.getItem(UPDATE_DISMISS_KEY);
                if (dismissed === data.remote_head) return;

                const label = data.commits_behind === 1
                    ? '1 new commit available.'
                    : data.commits_behind + ' new commits available.';
                document.getElementById('update-banner-text').textContent = label;
                document.getElementById('update-banner').classList.add('visible');
                document.body.classList.add('has-update-banner');
                feather.replace();
            } catch (e) { /* network error — silent */ }
        }

        function dismissUpdateBanner() {
            const banner = document.getElementById('update-banner');
            banner.classList.remove('visible');
            document.body.classList.remove('has-update-banner');
            // Store the remote head so we don't nag again for this version
            fetch('/api/check-update').then(r => r.json()).then(d => {
                if (d.remote_head) localStorage.setItem(UPDATE_DISMISS_KEY, d.remote_head);
            }).catch(() => {});
        }

        async function doPullUpdate() {
            const btn = document.getElementById('update-pull-btn');
            btn.disabled = true;
            btn.innerHTML = '<i data-feather="loader" style="width:13px;height:13px;animation:spin 0.8s linear infinite;"></i> Pulling…';
            feather.replace();
            try {
                const res = await fetch('/api/update-app', { method: 'POST' });
                const data = await res.json();
                if (data.success && !data.already_up_to_date) {
                    document.getElementById('update-banner-text').textContent = '✓ Updated! Reloading…';
                    setTimeout(() => location.reload(), 1200);
                } else if (data.success && data.already_up_to_date) {
                    document.getElementById('update-banner-text').textContent = '✓ Already up to date.';
                    setTimeout(dismissUpdateBanner, 2000);
                } else {
                    document.getElementById('update-banner-text').textContent = '✗ Pull failed. Check server logs.';
                    btn.disabled = false;
                    btn.innerHTML = '<i data-feather="refresh-cw" style="width:13px;height:13px;"></i> Retry';
                    feather.replace();
                }
            } catch (e) {
                document.getElementById('update-banner-text').textContent = '✗ Request failed.';
                btn.disabled = false;
                btn.innerHTML = '<i data-feather="refresh-cw" style="width:13px;height:13px;"></i> Retry';
                feather.replace();
            }
        }

        // Check on load, then every 5 minutes
        checkForUpdate();
        setInterval(checkForUpdate, 5 * 60 * 1000);

        // Nav group expand/collapse
        function toggleNavGroup(id) {
            const panel   = document.getElementById(id);
            const chevron = document.getElementById(id + '-chevron');
            const open    = panel.style.display !== 'none';
            panel.style.display  = open ? 'none' : '';
            chevron.style.transform = open ? '' : 'rotate(180deg)';
        }

        // Mobile Sidebar Toggle Functions
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active');
        }
        
        function closeMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
        }
        
        // Close sidebar when clicking on a link (mobile)
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarLinks = document.querySelectorAll('.sidebar-link');
            
            sidebarLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        closeMobileSidebar();
                    }
                });
            });
            
            // Load saved theme
            const savedTheme = localStorage.getItem('theme');
            const themeIcon = document.getElementById('theme-icon');
            
            if (savedTheme === 'light') {
                document.body.classList.add('light-theme');
                themeIcon.setAttribute('data-feather', 'sun');
                feather.replace();
            }
        });
        
        function toggleTheme() {
            const body = document.body;
            const themeIcon = document.getElementById('theme-icon');
            
            if (body.classList.contains('light-theme')) {
                body.classList.remove('light-theme');
                localStorage.setItem('theme', 'dark');
                // Update icon
                themeIcon.setAttribute('data-feather', 'moon');
                feather.replace();
            } else {
                body.classList.add('light-theme');
                localStorage.setItem('theme', 'light');
                // Update icon
                themeIcon.setAttribute('data-feather', 'sun');
                feather.replace();
            }
        }

        // Hide page loader when page is fully loaded
        window.addEventListener('load', function() {
            const loader = document.getElementById('page-loader');
            loader.classList.add('hidden');
            setTimeout(() => {
                loader.style.display = 'none';
            }, 300); // Wait for fade animation to complete
        });
    </script>
</body>
</html>
