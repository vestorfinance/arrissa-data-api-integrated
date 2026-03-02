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
            margin-bottom: 8px !important;
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
        :root { --banner-h: 0px; }

        /* Floating Help Button */
        #help-fab {
            position: fixed;
            bottom: 72px;
            right: 28px;
            z-index: 9990;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), #6366f1);
            color: #fff;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 20px rgba(79,70,229,0.45);
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 700;
        }
        #help-fab:hover {
            transform: translateY(-3px) scale(1.08);
            box-shadow: 0 8px 28px rgba(79,70,229,0.6);
            text-decoration: none;
        }
        #help-fab title { display: none; }

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

        /* ── Terms Acceptance Overlay ── */
        #terms-overlay {
            position: fixed;
            inset: 0;
            z-index: 10001;
            background: rgba(0, 0, 0, 0.92);
            backdrop-filter: blur(6px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            animation: fadeIn 0.25s ease-out;
        }
        #terms-overlay.hidden {
            display: none;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }
        #terms-modal {
            background: var(--card-bg, #1f1f1f);
            border: 1px solid var(--border, #2a2a2a);
            border-radius: 20px;
            width: 100%;
            max-width: 760px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 32px 80px rgba(0,0,0,0.7);
            overflow: hidden;
        }
        #terms-header {
            padding: 24px 28px 16px;
            border-bottom: 1px solid var(--border, #2a2a2a);
            flex-shrink: 0;
        }
        #terms-header h2 {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-primary, #fff);
            margin: 0 0 4px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        #terms-header p {
            font-size: 0.8rem;
            color: var(--text-secondary, #999);
            margin: 0;
        }
        #terms-body {
            flex: 1;
            overflow-y: auto;
            padding: 20px 28px;
            font-size: 0.78rem;
            line-height: 1.65;
            color: var(--text-secondary, #aaa);
        }
        #terms-body h3 {
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--text-primary, #fff);
            margin: 18px 0 6px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        #terms-body p {
            margin: 0 0 10px;
        }
        #terms-body ul {
            margin: 6px 0 10px 18px;
            padding: 0;
        }
        #terms-body li {
            margin-bottom: 5px;
        }
        #terms-body strong {
            color: var(--text-primary, #fff);
        }
        #terms-body .terms-warning {
            background: rgba(239,68,68,0.1);
            border-left: 3px solid #ef4444;
            border-radius: 4px;
            padding: 10px 14px;
            margin: 12px 0;
            color: #fca5a5;
            font-size: 0.78rem;
        }
        #terms-body .terms-section {
            border-bottom: 1px solid var(--border, #2a2a2a);
            padding-bottom: 14px;
            margin-bottom: 14px;
        }
        #terms-footer {
            padding: 16px 28px 20px;
            border-top: 1px solid var(--border, #2a2a2a);
            flex-shrink: 0;
            background: var(--card-bg, #1f1f1f);
        }
        #terms-scroll-note {
            font-size: 0.73rem;
            color: var(--text-secondary, #999);
            text-align: center;
            margin-bottom: 12px;
            transition: opacity 0.3s;
        }
        #terms-check-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 14px;
        }
        #terms-check-row input[type="checkbox"] {
            width: 17px !important;
            height: 17px !important;
            min-width: 17px;
            margin-top: 2px;
            accent-color: var(--accent, #4f46e5);
            cursor: pointer;
            border-radius: 4px !important;
        }
        #terms-check-row label {
            font-size: 0.8rem;
            color: var(--text-secondary, #aaa);
            cursor: pointer;
            line-height: 1.5;
        }
        #terms-check-row label strong {
            color: var(--text-primary, #fff);
        }
        #terms-accept-btn {
            width: 100%;
            padding: 12px;
            background: var(--accent, #4f46e5);
            color: #fff;
            border: none;
            border-radius: 9999px !important;
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.15s;
            letter-spacing: 0.02em;
        }
        #terms-accept-btn:disabled {
            opacity: 0.35;
            cursor: not-allowed;
        }
        #terms-accept-btn:not(:disabled):hover {
            opacity: 0.88;
            transform: translateY(-1px);
        }
        #terms-accept-btn:not(:disabled):active {
            transform: translateY(0);
        }
        #terms-decline-row {
            text-align: center;
            margin-top: 10px;
        }
        #terms-decline-row a {
            font-size: 0.73rem;
            color: var(--text-secondary, #777);
            text-decoration: underline;
            cursor: pointer;
        }
        #terms-decline-row a:hover {
            color: #ef4444;
        }
    </style>
</head>
<body style="background-color: var(--bg-primary);">
    <!-- Global Page Loader -->
    <div id="page-loader">
        <div class="loader-spinner"></div>
        <p style="color: var(--text-secondary); margin-top: 20px; font-size: 14px;">Loading...</p>
    </div>

    <!-- Terms & Conditions Acceptance Overlay -->
    <div id="terms-overlay" class="hidden">
        <div id="terms-modal">
            <div id="terms-header">
                <h2>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    Software License &amp; Disclaimer — Arrissa Data API
                </h2>
                <p>You must read and accept these terms before accessing this software. Scroll to the bottom to continue.</p>
            </div>
            <div id="terms-body">

                <div class="terms-warning">
                    <strong>IMPORTANT:</strong> This software is provided for technical use only and does not constitute financial advice. Trading financial instruments involves substantial risk of loss. You may lose all of your invested capital.
                </div>

                <div class="terms-section">
                    <h3>1. Proprietary Software — All Rights Reserved</h3>
                    <p>This software, including all source code, compiled binaries, documentation, expert advisors, APIs, and associated components (the <strong>"Software"</strong>), is the exclusive intellectual property of <strong>Arrissa Pty Ltd</strong>, authored by <strong>Ngonidzashe Jiji (David Richchild)</strong>. Copyright © 2024–2026 Arrissa Pty Ltd. All rights reserved.</p>
                    <p>No title, ownership, or intellectual property rights in the Software are transferred to you. All rights not expressly granted in this Agreement are expressly reserved by Arrissa Pty Ltd.</p>
                </div>

                <div class="terms-section">
                    <h3>2. Prohibited — No Redistribution or Resale</h3>
                    <p>You are <strong>strictly prohibited</strong> from:</p>
                    <ul>
                        <li>Copying, reproducing, distributing, publishing, or transmitting the Software to any third party in any form or by any means;</li>
                        <li>Selling, reselling, sublicensing, renting, leasing, or otherwise commercialising the Software without <strong>explicit prior written permission</strong> from Arrissa Pty Ltd;</li>
                        <li>Bundling or repackaging the Software into any other product or service;</li>
                        <li>Reverse engineering, decompiling, disassembling, or attempting to derive source code from any portion of the Software;</li>
                        <li>Removing, obscuring, or altering any copyright notices, trademarks, or proprietary labels.</li>
                    </ul>
                    <p>Any authorised use must include visible attribution: <em>"Powered by Arrissa Data API — © Arrissa Pty Ltd. Used with written permission."</em></p>
                </div>

                <div class="terms-section">
                    <h3>3. Disclaimer of Warranties — Provided "As Is"</h3>
                    <p>THE SOFTWARE IS PROVIDED <strong>"AS IS" AND "AS AVAILABLE"</strong>, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED. To the fullest extent permitted by law, Arrissa Pty Ltd and Ngonidzashe Jiji (David Richchild) expressly disclaim all warranties, including but not limited to:</p>
                    <ul>
                        <li>Any warranty of <strong>merchantability, quality, or fitness for a particular purpose</strong>;</li>
                        <li>Any warranty that the Software will be uninterrupted, error-free, secure, or free of harmful components;</li>
                        <li>Any warranty regarding the accuracy, completeness, reliability, or timeliness of any data, signals, outputs, or results produced by the Software;</li>
                        <li>Any warranty that the Software will meet your requirements or achieve any specific outcome.</li>
                    </ul>
                    <p>The Software has not been designed, tested, or certified for any specific trading strategy, market condition, broker, financial instrument, or jurisdiction. <strong>You are solely responsible for determining whether the Software is appropriate for your use case.</strong></p>
                </div>

                <div class="terms-section">
                    <h3>4. Financial Risk Disclaimer — Risk of Loss</h3>
                    <div class="terms-warning">
                        Trading foreign exchange, CFDs, derivatives, cryptocurrencies, and other financial instruments involves <strong>substantial risk of loss and is not suitable for all investors</strong>. You may lose your entire invested capital or more.
                    </div>
                    <p>The Software is a <strong>technical data and connectivity tool only</strong>. It does not constitute financial advice, investment advice, trading recommendations, or any other form of regulated advice. Nothing produced by the Software should be interpreted as a recommendation to buy, sell, or hold any financial instrument.</p>
                    <p>All trading and investment decisions made using or in connection with the Software are made <strong>entirely at your own risk</strong>. Past performance, backtested data, or simulated results are not indicative of future results.</p>
                </div>

                <div class="terms-section">
                    <h3>5. Limitation of Liability — No Liability for Losses</h3>
                    <p>TO THE FULLEST EXTENT PERMITTED BY LAW, Arrissa Pty Ltd, Ngonidzashe Jiji (David Richchild), and their respective directors, officers, employees, agents, affiliates, and associates (the <strong>"Released Parties"</strong>) shall <strong>not be liable</strong> under any legal theory for:</p>
                    <ul>
                        <li>Any <strong>financial loss, trading loss, loss of profits, or loss of capital</strong> of any nature whatsoever;</li>
                        <li>Any direct, indirect, incidental, consequential, special, or punitive damages;</li>
                        <li>Any loss arising from <strong>reliance on outputs, signals, or data</strong> generated by the Software;</li>
                        <li>Any loss arising from errors, inaccuracies, interruptions, delays, or failures in the Software;</li>
                        <li>Any adverse market outcomes related to the use of this Software.</li>
                    </ul>
                    <p>This exclusion applies even if the Released Parties have been advised of the possibility of such damages. To the extent liability cannot be excluded, the total aggregate liability shall not exceed <strong>AUD $100</strong>.</p>
                </div>

                <div class="terms-section">
                    <h3>6. Updates, Modifications &amp; Continuity of Service</h3>
                    <p>Licensor may, at its <strong>sole discretion</strong>, release updates, patches, or new versions of the Software. Licensor is under <strong>no obligation</strong> to release any update, maintain the Software, or continue development. Updates may introduce breaking changes to functionality, APIs, or data formats without liability.</p>
                    <p>Licensor reserves the right to <strong>discontinue the Software, withdraw any licence, or shut down associated services</strong> at any time, with or without notice, and without liability to you. Your continued use of the Software following any update constitutes acceptance of the updated terms.</p>
                </div>

                <div class="terms-section">
                    <h3>7. Governing Law</h3>
                    <p>This Agreement is governed by the laws of the <strong>Republic of South Africa</strong>. Any dispute shall be subject to the exclusive jurisdiction of the courts of South Africa.</p>
                </div>

                <div class="terms-section">
                    <h3>8. Indemnification</h3>
                    <p>You agree to indemnify and hold harmless the Released Parties from all claims, losses, damages, costs, and expenses (including legal fees) arising from your use or misuse of the Software, your breach of this Agreement, or any trading or investment activities conducted using the Software.</p>
                </div>

                <p style="margin-top:16px; font-size:0.75rem; color: var(--text-secondary);">
                    By accepting below, you confirm you have read, understood, and unconditionally agree to be legally bound by this Agreement in its entirety, including the full terms in the <strong>LICENSE.md</strong> file included with this Software. <strong>© 2024–2026 Arrissa Pty Ltd. All Rights Reserved.</strong>
                </p>

            </div>
            <div id="terms-footer">
                <div id="terms-scroll-note">↓ Scroll to the bottom to enable acceptance</div>
                <div id="terms-check-row">
                    <input type="checkbox" id="terms-checkbox" disabled onchange="onTermsCheckChange()">
                    <label for="terms-checkbox">
                        I confirm I have read and fully understand the above terms. I <strong>unconditionally agree</strong> to be legally bound by this Software License Agreement, including the disclaimer of warranties, the financial risk disclaimer, the prohibition on redistribution, and the limitation of liability.
                    </label>
                </div>
                <button id="terms-accept-btn" disabled onclick="acceptTerms()">
                    I Accept — Continue to Software
                </button>
                <div id="terms-decline-row">
                    <a onclick="declineTerms()">I do not accept — exit</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Reveal terms overlay synchronously before first paint if not yet accepted -->
    <script>
        (function(){
            var TERMS_VERSION = 'v2';
            var STORAGE_KEY   = 'arrissa_terms_accepted';
            if (localStorage.getItem(STORAGE_KEY) !== TERMS_VERSION) {
                var el = document.getElementById('terms-overlay');
                if (el) el.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
        })();
    </script>

    <!-- Floating Help Button -->
    <a id="help-fab" href="https://arrissadata.com/get" target="_blank" rel="noopener" title="Help">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    </a>

    <!-- Update Notification Banner -->
    <div id="update-banner">
        <span id="update-banner-text">A new update is available.</span>
        <button id="update-pull-btn" onclick="doPullUpdate()">Update Now</button>
        <button id="update-dismiss-btn" onclick="dismissUpdateBanner()" title="Dismiss" style="font-size:1.1rem;line-height:1;">&#x2715;</button>
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
                <a href="/" class="sidebar-link <?php echo ($page ?? '') == 'dashboard' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-2 rounded-full mb-1" style="color: <?php echo ($page ?? '') == 'dashboard' ? 'var(--text-primary)' : 'var(--text-secondary)'; ?>;">
                    <i data-feather="grid" style="width: 20px; height: 20px;"></i>
                    <span class="text-base font-medium">Dashboard</span>
                </a>
                <a href="/brokers" class="sidebar-link flex items-center space-x-3 px-4 py-2 rounded-full mb-1" style="color: var(--accent); border: 1px solid var(--accent); background: transparent; <?php echo ($page ?? '') == 'brokers' ? 'background: rgba(79,70,229,0.08) !important;' : ''; ?>">
                    <i data-feather="briefcase" style="width: 20px; height: 20px;"></i>
                    <span class="text-base font-medium">Brokers</span>
                </a>
                <?php _Tz8wKpN4::_r(); ?>
                <a href="/market-data-api-guide" class="sidebar-link <?php echo ($page ?? '') == 'market-data-api' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-2 rounded-full mb-1" style="color: <?php echo ($page ?? '') == 'market-data-api' ? 'var(--text-primary)' : 'var(--text-secondary)'; ?>;">
                    <i data-feather="trending-up" style="width: 20px; height: 20px;"></i>
                    <span class="text-base font-medium">Market Data API Guide</span>
                </a>
                <?php
                    $newsSubPages = ['news-api-guide', 'manage-events', 'similar-scene-api-guide', 'event-id-reference'];
                    $newsGroupOpen = in_array($page ?? '', $newsSubPages);
                ?>
                <!-- News API group -->
                <div class="mb-1">
                    <button onclick="toggleNavGroup('news-group')" class="sidebar-link w-full flex items-center justify-between px-4 py-2 rounded-full <?php echo $newsGroupOpen ? 'active' : ''; ?>" style="color: <?php echo $newsGroupOpen ? 'var(--text-primary)' : 'var(--text-secondary)'; ?>; background: none; border: none; cursor: pointer;">
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
                <a href="/chart-image-api-guide" class="sidebar-link <?php echo ($page ?? '') == 'chart-image-api' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-2 rounded-full mb-1" style="color: <?php echo ($page ?? '') == 'chart-image-api' ? 'var(--text-primary)' : 'var(--text-secondary)'; ?>;">
                    <i data-feather="image" style="width: 20px; height: 20px;"></i>
                    <span class="text-base font-medium">Chart Image API Guide</span>
                </a>
                <a href="/orders-api-guide" class="sidebar-link <?php echo ($page ?? '') == 'orders-api' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-2 rounded-full mb-1" style="color: <?php echo ($page ?? '') == 'orders-api' ? 'var(--text-primary)' : 'var(--text-secondary)'; ?>;">
                    <i data-feather="shopping-cart" style="width: 20px; height: 20px;"></i>
                    <span class="text-base font-medium">Orders API Guide</span>
                </a>
                <a href="/symbol-info-api-guide" class="sidebar-link <?php echo ($page ?? '') == 'symbol-info-api' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-2 rounded-full mb-1" style="color: <?php echo ($page ?? '') == 'symbol-info-api' ? 'var(--text-primary)' : 'var(--text-secondary)'; ?>;">
                    <i data-feather="bar-chart-2" style="width: 20px; height: 20px;"></i>
                    <span class="text-base font-medium">Symbol Info API Guide</span>
                </a>
                <a href="/quarters-theory-api-guide" class="sidebar-link <?php echo ($page ?? '') == 'quarters-theory-api-guide' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-2 rounded-full mb-1" style="color: <?php echo ($page ?? '') == 'quarters-theory-api-guide' ? 'var(--text-primary)' : 'var(--text-secondary)'; ?>;">
                    <i data-feather="target" style="width: 20px; height: 20px;"></i>
                    <span class="text-base font-medium">Quarters Theory API Guide</span>
                </a>
                <a href="/url-api-guide" class="sidebar-link <?php echo ($page ?? '') == 'url-api-guide' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-2 rounded-full mb-1" style="color: <?php echo ($page ?? '') == 'url-api-guide' ? 'var(--text-primary)' : 'var(--text-secondary)'; ?>;">
                    <i data-feather="globe" style="width: 20px; height: 20px;"></i>
                    <span class="text-base font-medium">URL API Guide</span>
                </a>
                <?php
                    $tmpSubPages = ['tmp-guide', 'tmp-manage'];
                    $tmpGroupOpen = in_array($page ?? '', $tmpSubPages);
                ?>
                <div class="mb-1">
                    <button onclick="toggleNavGroup('tmp-group')" class="sidebar-link w-full flex items-center justify-between px-4 py-2 rounded-full <?php echo $tmpGroupOpen ? 'active' : ''; ?>" style="color: <?php echo $tmpGroupOpen ? 'var(--text-primary)' : 'var(--text-secondary)'; ?>; background: none; border: none; cursor: pointer;">
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
                <a href="/download-eas" class="sidebar-link <?php echo ($page ?? '') == 'download-eas' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-2 rounded-full mb-1" style="color: <?php echo ($page ?? '') == 'download-eas' ? 'var(--text-primary)' : 'var(--text-secondary)'; ?>;">
                    <i data-feather="download" style="width: 20px; height: 20px;"></i>
                    <span class="text-base font-medium">Download EAs</span>
                </a>
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

        // -- Update check --
        const UPDATE_DISMISS_KEY = 'update_dismissed_head';

        function applyBannerLayout() {
            const banner = document.getElementById('update-banner');
            const wrapper = document.querySelector('.flex.h-screen');
            const mobileHeader = document.querySelector('.mobile-header');
            const sidebar = document.getElementById('sidebar');
            const h = banner.getBoundingClientRect().height;
            if (wrapper) { wrapper.style.marginTop = h + 'px'; wrapper.style.height = 'calc(100vh - ' + h + 'px)'; }
            if (mobileHeader) mobileHeader.style.top = h + 'px';
            if (sidebar) sidebar.style.top = h + 'px';
        }

        function clearBannerLayout() {
            const wrapper = document.querySelector('.flex.h-screen');
            const mobileHeader = document.querySelector('.mobile-header');
            const sidebar = document.getElementById('sidebar');
            if (wrapper) { wrapper.style.marginTop = ''; wrapper.style.height = ''; }
            if (mobileHeader) mobileHeader.style.top = '';
            if (sidebar) sidebar.style.top = '';
        }

        async function checkForUpdate() {
            try {
                const res = await fetch('/api/check-update');
                if (!res.ok) return;
                const data = await res.json();
                if (!data.update_available) return;
                const dismissed = localStorage.getItem(UPDATE_DISMISS_KEY);
                if (dismissed === data.remote_head) return;
                const label = data.commits_behind === 1
                    ? '1 new update available'
                    : data.commits_behind + ' new updates available';
                document.getElementById('update-banner-text').textContent = label;
                document.getElementById('update-banner').classList.add('visible');
                requestAnimationFrame(applyBannerLayout);
            } catch (e) { /* silent */ }
        }

        function dismissUpdateBanner() {
            document.getElementById('update-banner').classList.remove('visible');
            clearBannerLayout();
            fetch('/api/check-update').then(r => r.json()).then(d => {
                if (d.remote_head) localStorage.setItem(UPDATE_DISMISS_KEY, d.remote_head);
            }).catch(() => {});
        }

        async function doPullUpdate() {
            const btn = document.getElementById('update-pull-btn');
            btn.disabled = true;
            btn.textContent = 'Updating...';
            try {
                const res = await fetch('/api/update-app', { method: 'POST' });
                const data = await res.json();
                if (data.success && !data.already_up_to_date) {
                    document.getElementById('update-banner-text').textContent = 'Update successful! Reloading...';
                    btn.textContent = 'Done';
                    setTimeout(() => location.reload(), 1200);
                } else if (data.success && data.already_up_to_date) {
                    document.getElementById('update-banner-text').textContent = 'Already up to date.';
                    setTimeout(dismissUpdateBanner, 2000);
                } else {
                    document.getElementById('update-banner-text').textContent = 'Update failed. Check server logs.';
                    btn.disabled = false;
                    btn.textContent = 'Retry';
                }
            } catch (e) {
                document.getElementById('update-banner-text').textContent = 'Request failed.';
                btn.disabled = false;
                btn.textContent = 'Retry';
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

        // ── Terms Overlay Logic ──
        (function() {
            const TERMS_VERSION = 'v2'; // bump this string to force re-acceptance after a terms update
            const STORAGE_KEY   = 'arrissa_terms_accepted';

            const overlay  = document.getElementById('terms-overlay');
            const body     = document.getElementById('terms-body');
            const checkbox = document.getElementById('terms-checkbox');
            const acceptBtn = document.getElementById('terms-accept-btn');
            const scrollNote = document.getElementById('terms-scroll-note');

            function termsAccepted() {
                return localStorage.getItem(STORAGE_KEY) === TERMS_VERSION;
            }

            if (!termsAccepted()) {
                // overlay already revealed by inline sync script above — just wire up scroll
                document.body.style.overflow = 'hidden';

                // Enable checkbox once user has scrolled to (or near) the bottom
                body.addEventListener('scroll', function() {
                    const nearBottom = body.scrollTop + body.clientHeight >= body.scrollHeight - 60;
                    if (nearBottom) {
                        checkbox.disabled = false;
                        scrollNote.style.opacity = '0';
                    }
                });
            } else {
                overlay.classList.add('hidden');
            }

            window.onTermsCheckChange = function() {
                acceptBtn.disabled = !checkbox.checked;
            };

            window.acceptTerms = function() {
                if (!checkbox.checked) return;
                localStorage.setItem(STORAGE_KEY, TERMS_VERSION);
                overlay.style.transition = 'opacity 0.3s ease-out';
                overlay.style.opacity = '0';
                setTimeout(() => {
                    overlay.classList.add('hidden');
                    document.body.style.overflow = '';
                }, 300);
            };

            window.declineTerms = function() {
                if (confirm('You must accept the terms to use this software. Click OK to be redirected, or Cancel to review the terms.')) {
                    window.location.href = 'https://arrissa.com';
                }
            };
        })();
    </script>
</body>
</html>
