<?php
/**
 * Arrissa AI Chat — /chat
 * Standalone page: login-gated (enforced by root router), no sidebar.
 */
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Database.php';

// ── Load config from database ─────────────────────────────────────────────────
$db = Database::getInstance();
function chatSetting($db, $key, $default = '') {
    $r = $db->query("SELECT value FROM settings WHERE key = ?", [$key])->fetch();
    return $r ? $r['value'] : $default;
}

$webhookUrl      = chatSetting($db, 'chat_webhook_url', '');
$chatTitle       = chatSetting($db, 'chat_title',       'Arrissa AI');
$chatSubtitle    = chatSetting($db, 'chat_subtitle',    'Your AI assistant');
$initialMessages = json_decode(chatSetting($db, 'chat_initial_messages',
    json_encode(["Hello! I'm Arrissa AI. How can I help you today?", "Feel free to ask me anything."])), true)
    ?? ["Hello! I'm Arrissa AI. How can I help you today?", "Feel free to ask me anything."];
$enableStreaming  = chatSetting($db, 'chat_enable_streaming', '0') === '1';
$availableModels = json_decode(chatSetting($db, 'chat_available_models',
    json_encode(['analysis-model-1' => 'Analysis Model 1', 'analysis-model-2' => 'Analysis Model 2', 'analysis-model-3' => 'Analysis Model 3'])), true)
    ?? ['analysis-model-1' => 'Analysis Model 1'];

// ── Session ───────────────────────────────────────────────────────────────────
// Handle model change POST → return JSON, create new session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_model'])) {
    if (array_key_exists($_POST['selected_model'], $availableModels)) {
        unset($_SESSION['chat_session_id']);
        $_SESSION['selected_model']  = $_POST['selected_model'];
        $_SESSION['chat_session_id'] = bin2hex(random_bytes(16));
        header('Content-Type: application/json');
        echo json_encode([
            'status'        => 'success',
            'newSessionId'  => $_SESSION['chat_session_id'],
            'selectedModel' => $_POST['selected_model']
        ]);
        exit;
    }
}

if (!isset($_SESSION['chat_session_id'])) {
    $_SESSION['chat_session_id'] = bin2hex(random_bytes(16));
}
$sessionId     = $_SESSION['chat_session_id'];
$selectedModel = $_SESSION['selected_model'] ?? array_key_first($availableModels);
$username      = Auth::getUser();
$firstModel    = $availableModels[$selectedModel] ?? reset($availableModels);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?= htmlspecialchars($chatTitle) ?></title>
    <link rel="icon" type="image/png" href="/arrisssa-favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@n8n/chat/dist/style.css" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        /* ── Design tokens (identical to app) ── */
        :root {
            --bg-primary:   #0f0f0f;
            --bg-secondary: #1a1a1a;
            --bg-tertiary:  #2d2d2d;
            --text-primary: #ffffff;
            --text-secondary: #a0a0a0;
            --accent:        #4f46e5;
            --accent-hover:  #6366f1;
            --border:        #3a3a3a;
            --success:       #10b981;
            --danger:        #ef4444;
            --card-bg:       #1f1f1f;
            --input-bg:      #262626;
            --input-border:  #404040;

            /* n8n AI green */
            --ai-primary:   #10a37f;
            --ai-hover:     #0d8f6b;
        }
        body.light-theme {
            --bg-primary:   #ffffff;
            --bg-secondary: #f9fafb;
            --bg-tertiary:  #f3f4f6;
            --text-primary: #111827;
            --text-secondary: #6b7280;
            --border:       #e5e7eb;
            --card-bg:      #ffffff;
            --input-bg:     #f9fafb;
            --input-border: #d1d5db;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        /* ── Page loader ── */
        #page-loader {
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: var(--bg-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity .35s ease, visibility .35s ease;
        }
        #page-loader.hidden {
            opacity: 0;
            visibility: hidden;
        }
        .page-loader-ring {
            width: 28px;
            height: 28px;
            border: 2.5px solid rgba(255,255,255,.1);
            border-top-color: var(--ai-primary);
            border-radius: 50%;
            animation: pl-spin .7s linear infinite;
        }
        body.light-theme .page-loader-ring {
            border-color: rgba(0,0,0,.08);
            border-top-color: var(--ai-primary);
        }
        @keyframes pl-spin { to { transform: rotate(360deg); } }

        html, body {
            height: 100%; width: 100%;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            overflow: hidden;
            position: fixed;
            -webkit-font-smoothing: antialiased;
            overscroll-behavior: none;
        }

        /* ── Top-bar ── */
        #chat-topbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 60px;
            background: var(--bg-primary);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 20px;
            gap: 14px;
            z-index: 200;
        }

        .topbar-logo {
            width: 34px; height: 34px;
            border-radius: 50%;
            background: var(--accent);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .topbar-logo svg { width: 20px; height: 20px; color: #fff; fill: currentColor; }

        .topbar-title {
            font-weight: 700;
            font-size: 15px;
            color: var(--text-primary);
            white-space: nowrap;
            flex-shrink: 0;
        }
        .topbar-subtitle {
            font-size: 12px;
            color: var(--text-secondary);
            white-space: nowrap;
            flex-shrink: 0;
            display: none;
        }
        @media (min-width: 560px) { .topbar-subtitle { display: block; } }

        .topbar-spacer { flex: 1; }

        /* Model selector */
        .model-select-wrap {
            position: relative;
            flex-shrink: 0;
        }
        #topModelSelect {
            appearance: none;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 9999px;
            color: var(--text-primary);
            font-family: inherit;
            font-size: 13px;
            font-weight: 500;
            padding: 7px 34px 7px 14px;
            cursor: pointer;
            transition: border-color .15s;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 10px center;
            background-repeat: no-repeat;
            background-size: 14px;
        }
        #topModelSelect:focus { outline: none; border-color: var(--accent); }
        #topModelSelect option { background: var(--bg-secondary); color: var(--text-primary); }

        /* New chat button */
        .topbar-btn {
            width: 34px; height: 34px;
            border-radius: 9999px;
            border: 1px solid var(--border);
            background: var(--input-bg);
            color: var(--text-secondary);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
            transition: background .15s, color .15s;
        }
        .topbar-btn:hover { background: var(--bg-tertiary); color: var(--text-primary); }
        .topbar-btn svg { width: 16px; height: 16px; }

        /* Dashboard link */
        .topbar-home {
            display: flex; align-items: center; gap: 6px;
            padding: 7px 14px;
            border-radius: 9999px;
            border: 1px solid var(--border);
            background: var(--input-bg);
            color: var(--text-secondary);
            font-size: 13px; font-weight: 500;
            text-decoration: none;
            flex-shrink: 0;
            transition: background .15s, color .15s;
        }
        .topbar-home:hover { background: var(--bg-tertiary); color: var(--text-primary); text-decoration: none; }
        .topbar-home svg { width: 14px; height: 14px; }
        .topbar-home-label { display: none; }
        @media (min-width: 480px) { .topbar-home-label { display: inline; } }

        /* User avatar */
        .topbar-avatar {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: var(--text-primary);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 13px;
            color: var(--bg-primary);
            flex-shrink: 0;
            cursor: default;
        }

        /* ── Hamburger (mobile only) ── */
        .topbar-hamburger {
            display: none;
            width: 34px; height: 34px;
            border-radius: 9999px;
            border: 1px solid var(--border);
            background: var(--input-bg);
            color: var(--text-secondary);
            align-items: center; justify-content: center;
            cursor: pointer; flex-shrink: 0;
            transition: background .15s, color .15s;
        }
        .topbar-hamburger:hover { background: var(--bg-tertiary); color: var(--text-primary); }
        .topbar-hamburger svg { width: 17px; height: 17px; }
        @media (max-width: 767px) { .topbar-hamburger { display: flex; } }

        /* ── Mobile sidebar drawer ── */
        #mobile-sidebar-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,.55);
            z-index: 300;
            backdrop-filter: blur(2px);
        }
        #mobile-sidebar-overlay.open { display: block; }

        #mobile-sidebar {
            position: fixed;
            top: 0; left: -280px;
            width: 260px; height: 100%;
            background: var(--bg-secondary);
            border-right: 1px solid var(--border);
            z-index: 310;
            display: flex; flex-direction: column;
            padding: 0;
            transition: left .25s cubic-bezier(.4,0,.2,1);
            overflow-y: auto;
        }
        #mobile-sidebar.open { left: 0; }

        .msb-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 16px 18px;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }
        .msb-brand {
            display: flex; align-items: center; gap: 10px;
        }
        .msb-brand-icon {
            width: 30px; height: 30px; border-radius: 50%;
            background: var(--accent);
            display: flex; align-items: center; justify-content: center;
        }
        .msb-brand-icon svg { width: 18px; height: 18px; fill: #fff; }
        .msb-brand-name { font-size: 14px; font-weight: 700; color: var(--text-primary); }
        .msb-close {
            width: 28px; height: 28px; border-radius: 50%;
            border: none; background: transparent;
            color: var(--text-secondary); cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: background .15s, color .15s;
        }
        .msb-close:hover { background: var(--bg-tertiary); color: var(--text-primary); }
        .msb-close svg { width: 16px; height: 16px; }

        .msb-nav { flex: 1; padding: 12px 10px; display: flex; flex-direction: column; gap: 2px; }
        .msb-link {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 12px;
            border-radius: 10px;
            color: var(--text-secondary);
            font-size: 14px; font-weight: 500;
            text-decoration: none;
            transition: background .15s, color .15s;
        }
        .msb-link:hover, .msb-link.active {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }
        .msb-link svg { width: 16px; height: 16px; flex-shrink: 0; }
        .msb-divider { height: 1px; background: var(--border); margin: 8px 0; }

        .msb-footer {
            padding: 14px 18px;
            border-top: 1px solid var(--border);
            flex-shrink: 0;
        }
        .msb-user {
            display: flex; align-items: center; gap: 10px;
        }
        .msb-user-avatar {
            width: 32px; height: 32px; border-radius: 50%;
            background: var(--text-primary);
            color: var(--bg-primary);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 13px; flex-shrink: 0;
        }
        .msb-user-name { font-size: 13px; font-weight: 600; color: var(--text-primary); }
        .msb-user-role { font-size: 11px; color: var(--text-secondary); }

        /* Sidebar section label */
        .msb-section-label {
            font-size: 10px; font-weight: 600;
            text-transform: uppercase; letter-spacing: .08em;
            color: var(--text-secondary);
            padding: 14px 14px 6px;
        }
        /* Model select inside sidebar */
        .msb-model-select {
            appearance: none;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 9999px;
            color: var(--text-primary);
            font-family: inherit;
            font-size: 13px;
            font-weight: 500;
            padding: 9px 36px 9px 16px;
            cursor: pointer;
            width: 100%;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 14px;
        }
        .msb-model-select:focus { outline: none; border-color: #10a37f; }
        .msb-model-select option { background: var(--bg-secondary); color: var(--text-primary); }
        .msb-model-wrap { padding: 0 12px 4px; }

        /* Action buttons in sidebar */
        .msb-action {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 14px;
            border-radius: 10px;
            color: var(--text-secondary);
            font-size: 14px; font-weight: 500;
            background: none; border: none; width: 100%;
            cursor: pointer; text-align: left;
            font-family: inherit;
            transition: background .15s, color .15s;
            text-decoration: none;
        }
        .msb-action:hover { background: var(--bg-tertiary); color: var(--text-primary); }
        .msb-action svg { width: 16px; height: 16px; flex-shrink: 0; }

        /* Hide topbar items that move to sidebar on mobile */
        @media (max-width: 767px) {
            .model-select-wrap { display: none !important; }
            .topbar-btn { display: none !important; }
            .theme-toggle-btn { display: none !important; }
            .topbar-home { display: none !important; }
        }

        /* ── Desktop: Powered by footer ── */
        #powered-by-footer {
            display: none;
            position: fixed;
            bottom: 0; left: 0; right: 0;
            height: 52px;
            background: var(--bg-primary);
            border-top: 1px solid var(--border);
            align-items: center; justify-content: center;
            gap: 6px;
            font-size: 12px;
            color: var(--text-secondary);
            z-index: 100;
            letter-spacing: .01em;
        }
        @media (min-width: 768px) { #powered-by-footer { display: flex; } }
        #powered-by-footer .pb-dot {
            width: 5px; height: 5px; border-radius: 50%;
            background: var(--ai-primary);
            opacity: .7;
        }
        #powered-by-footer a {
            color: var(--ai-primary);
            text-decoration: none;
            font-weight: 600;
        }
        #powered-by-footer a:hover { text-decoration: underline; }

        /* ── Chat area ── */
        #chat-area {
            position: fixed;
            top: 60px; left: 0; right: 0; bottom: 0;
            display: flex;
            align-items: stretch;
            justify-content: center;
            padding: 0;
            background: var(--bg-primary);
            overflow: hidden;
        }
        @media (min-width: 768px) {
            #chat-area { bottom: 52px; }
        }

        .chat-frame {
            width: 100%;
            max-width: 860px;
            display: flex;
            flex-direction: column;
            background: var(--card-bg);
            border: none !important;
            overflow: hidden;
        }

        #n8n-chat {
            flex: 1;
            width: 100%;
            height: 100%;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* No-webhook banner */
        #no-webhook-banner {
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            gap: 16px;
            padding: 32px;
            text-align: center;
        }
        #no-webhook-banner.visible { display: flex; }
        .no-webhook-icon {
            width: 56px; height: 56px;
            border-radius: 50%;
            background: rgba(79,70,229,.12);
            display: flex; align-items: center; justify-content: center;
        }
        .no-webhook-icon svg { width: 28px; height: 28px; color: var(--accent); }

        /* ═══════════════════════════════════════════════════════════════
           n8n CHAT — CHATGPT-STYLE REDESIGN
           Using official CSS variable names from @n8n/chat docs
        ════════════════════════════════════════════════════════════════ */

        /* 1. Set n8n's own design tokens to match our dark theme */
        #n8n-chat,
        .n8n-chat {
            /* Colors */
            --chat--color--primary:             #10a37f;
            --chat--color--primary-shade-50:     #0d8f6b;
            --chat--color--primary--shade-100:   #0b7a5a;
            --chat--color--secondary:            #10a37f;
            --chat--color-secondary-shade-50:    #0d8f6b;
            --chat--color-white:                 var(--text-primary);
            --chat--color-light:                 var(--bg-secondary);
            --chat--color-light-shade-50:        var(--bg-tertiary);
            --chat--color-light-shade-100:       var(--border);
            --chat--color-medium:                var(--border);
            --chat--color-dark:                  var(--bg-primary);
            --chat--color-disabled:              #555;
            --chat--color-typing:                var(--text-secondary);

            /* Layout */
            --chat--spacing:                     1rem;
            --chat--border-radius:               16px;
            --chat--transition-duration:         0.15s;
            --chat--font-family:                 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;

            /* Header — hidden */
            --chat--header-height:               0px;
            --chat--header--padding:             0px;
            --chat--header--background:          transparent;
            --chat--header--color:               transparent;
            --chat--header--border-top:          none;
            --chat--header--border-bottom:       none;
            --chat--header--border-left:         none;
            --chat--header--border-right:        none;

            /* Messages */
            --chat--message--font-size:          15px;
            --chat--message--padding:            11px 16px;
            --chat--message--border-radius:      20px;
            --chat--message-line-height:         1.65;
            --chat--message--margin-bottom:      6px;
            --chat--message--bot--background:    var(--bg-tertiary);
            --chat--message--bot--color:         var(--text-primary);
            --chat--message--bot--border:        none;
            --chat--message--user--background:   #10a37f;
            --chat--message--user--color:        #ffffff;
            --chat--message--user--border:       none;
            --chat--message--pre--background:    rgba(0,0,0,0.25);
            --chat--messages-list--padding:      24px 0;

            /* Input area */
            --chat--textarea--height:            52px;
            --chat--textarea--max-height:        200px;
            --chat--input--font-size:            15px;
            --chat--input--border:               1.5px solid var(--border);
            --chat--input--border-active:        1.5px solid #10a37f;
            --chat--input--border-radius:        25px;
            --chat--input--padding:              14px 56px 14px 20px;
            --chat--input--background:           var(--input-bg);
            --chat--input--text-color:           var(--text-primary);
            --chat--input--line-height:          1.55;

            /* Send button */
            --chat--input--send--button--background:       #10a37f;
            --chat--input--send--button--color:            #ffffff;
            --chat--input--send--button--background-hover: #0d8f6b;
            --chat--input--send--button--color-hover:      #ffffff;

            /* Body + footer backgrounds */
            --chat--body--background:            var(--bg-primary);
            --chat--footer--background:          var(--bg-primary);
            --chat--footer--color:               var(--text-primary);

            /* Buttons */
            --chat--button--border-radius:       9999px;
            --chat--button--font-size:           14px;
            --chat--button--background--primary:       #10a37f;
            --chat--button--color--primary:            #ffffff;
            --chat--button--background--primary--hover: #0d8f6b;
        }

        /* 2. Hide the n8n default header entirely */
        .n8n-chat .chat-header,
        .n8n-chat [class*="chat-header"],
        .n8n-chat [class*="chatHeader"] {
            display: none !important;
            height: 0 !important;
            min-height: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            border: none !important;
            overflow: hidden !important;
        }

        /* 3. Root widget container — fully transparent, no borders */
        .n8n-chat {
            padding: 20px !important;
            background: var(--chat--body--background) !important;
            border: none !important;
            box-shadow: none !important;
            font-family: 'Inter', -apple-system, sans-serif !important;
            height: 100% !important;
            display: flex !important;
            flex-direction: column !important;
            overflow: hidden !important;
        }
        /* Kill any border/shadow on every direct child panel */
        .n8n-chat > * {
            border: none !important;
            box-shadow: none !important;
        }

        /* 4. ── MESSAGES AREA — bottom-anchored (ChatGPT pattern) ── */
        .n8n-chat .chat-messages-container {
            background: transparent !important;
            flex: 1 1 0% !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            display: flex !important;
            flex-direction: column !important;
            scrollbar-width: thin;
            scrollbar-color: var(--input-border) transparent;
        }
        .n8n-chat .chat-messages-container::-webkit-scrollbar { width: 4px; }
        .n8n-chat .chat-messages-container::-webkit-scrollbar-track { background: transparent; }
        .n8n-chat .chat-messages-container::-webkit-scrollbar-thumb { background: var(--input-border); border-radius: 4px; }

        /* The messages list gets margin-top:auto → pushes it to the bottom when few messages */
        .n8n-chat .chat-messages-list,
        .n8n-chat [class*="messages-list"],
        .n8n-chat ul[class*="message"] {
            margin-top: auto !important;
            padding: 20px 0 16px !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 2px !important;
            width: 100% !important;
            max-width: 720px !important;
            margin-left: auto !important;
            margin-right: auto !important;
            padding-left: 24px !important;
            padding-right: 24px !important;
        }

        /* 5. ── MESSAGE BUBBLES ── */
        .n8n-chat .chat-message {
            display: flex !important;
            flex-direction: column !important;
            max-width: 78% !important;
            margin-bottom: 2px !important;
        }

        /* Bot — left aligned, subtle bubble */
        .n8n-chat .chat-message-from-bot {
            align-self: flex-start !important;
        }
        .n8n-chat .chat-message-from-bot .chat-message-text {
            background: var(--bg-tertiary) !important;
            color: var(--text-primary) !important;
            border-radius: 4px 20px 20px 20px !important;
            padding: 11px 16px !important;
            font-size: 15px !important;
            line-height: 1.65 !important;
        }

        /* User — right aligned, teal pill bubble */
        .n8n-chat .chat-message-from-user {
            align-self: flex-end !important;
        }
        .n8n-chat .chat-message-from-user .chat-message-text {
            background: #10a37f !important;
            color: #fff !important;
            border-radius: 20px 4px 20px 20px !important;
            padding: 11px 16px !important;
            font-size: 15px !important;
            line-height: 1.65 !important;
        }

        /* code blocks inside bubbles */
        .n8n-chat .chat-message-text pre,
        .n8n-chat .chat-message-text code {
            background: rgba(0,0,0,.28) !important;
            border-radius: 8px !important;
            font-size: 13px !important;
        }

        /* Typing indicator dots */
        .n8n-chat [class*="typing"] span,
        .n8n-chat [class*="loading-dots"] span {
            background: #10a37f !important;
            border-radius: 50% !important;
        }

        /* 6. ── COMPOSER / FOOTER ── */
        /* Outer wrapper — THIS is the visible rounded pill box */
        [class*="chat-inputs"] {
            background: var(--input-bg) !important;
            border: none !important;
            border-radius: 25px !important;
            overflow: hidden !important;
            box-shadow: 0 1px 4px rgba(0,0,0,.2) !important;
            padding: 20px !important;
            margin: 0 !important;
            margin-bottom: 20px !important;
            transition: box-shadow .18s !important;
            display: flex !important;
            align-items: flex-end !important;
        }
        [class*="chat-inputs"]:focus-within {
            box-shadow: 0 1px 4px rgba(0,0,0,.2), 0 0 0 3px rgba(16,163,127,.2) !important;
        }
        .n8n-chat .chat-input-container,
        .n8n-chat [class*="chatFooter"],
        .n8n-chat [class*="input-container"] {
            background: transparent !important;
            border: none !important;
            border-top: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            padding-bottom: 0 !important;
            flex-shrink: 0 !important;
        }
        .n8n-chat [class*="chat-footer"] {
            background: var(--bg-primary) !important;
            border: none !important;
            border-top: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            padding-bottom: 0 !important;
            flex-shrink: 0 !important;
        }

        /* The actual input row — centred, max-width aligned with messages */
        .n8n-chat .chat-input-container > div,
        .n8n-chat [class*="chat-footer"] > div,
        .n8n-chat [class*="input-row"],
        .n8n-chat [class*="inputRow"] {
            position: relative !important;
            max-width: 720px !important;
            margin: 0 auto !important;
            padding: 0 !important;
        }

        /* Inner textarea — flat, transparent, fills the wrapper; wrapper clips the corners */
        .n8n-chat .chat-input,
        .n8n-chat textarea[class*="input"],
        [class*="chat-inputs"] textarea {
            background: transparent !important;
            border: none !important;
            border-radius: 0 !important;
            color: var(--text-primary) !important;
            font-size: 15px !important;
            font-family: 'Inter', sans-serif !important;
            line-height: 1.5 !important;
            padding: 14px 58px 14px 22px !important;
            resize: none !important;
            width: 100% !important;
            min-height: 52px !important;
            max-height: 160px !important;
            box-shadow: none !important;
            outline: none !important;
            display: block !important;
            transition: none !important;
        }
        .n8n-chat .chat-input:focus,
        .n8n-chat textarea[class*="input"]:focus,
        [class*="chat-inputs"] textarea:focus {
            outline: none !important;
            box-shadow: none !important;
            border: none !important;
        }
        .n8n-chat .chat-input::placeholder,
        .n8n-chat textarea[class*="input"]::placeholder,
        [class*="chat-inputs"] textarea::placeholder {
            color: var(--text-secondary) !important;
            opacity: .65 !important;
        }

        /* 7. ── SEND BUTTON — pill, teal ── */
        .n8n-chat .chat-input-send-button,
        .n8n-chat [class*="send-button"],
        .n8n-chat button[aria-label*="end"],
        .n8n-chat button[class*="send"] {
            background: #10a37f !important;
            border: none !important;
            border-radius: 9999px !important;
            width: 48px !important;
            height: 48px !important;
            min-width: 48px !important;
            min-height: 48px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            color: #fff !important;
            position: absolute !important;
            right: 10px !important;
            top: 38% !important;
            bottom: 0px !important;
            transform: translateY(-50%) !important;
            cursor: pointer !important;
            box-shadow: 0 2px 10px rgba(16,163,127,.4) !important;
            transition: background .15s, transform .12s cubic-bezier(.34,1.56,.64,1), box-shadow .15s !important;
        }
        .n8n-chat .chat-input-send-button:hover,
        .n8n-chat [class*="send-button"]:hover {
            background: #0d8f6b !important;
            transform: translateY(-50%) scale(1.1) !important;
            box-shadow: 0 4px 16px rgba(16,163,127,.5) !important;
        }
        .n8n-chat .chat-input-send-button:active,
        .n8n-chat [class*="send-button"]:active {
            transform: translateY(-50%) scale(0.92) !important;
            transition-duration: .06s !important;
        }

        /* Hint line below composer */
        .n8n-chat .chat-input-container::after,
        .n8n-chat [class*="chat-footer"]::after {
            content: 'Enter to send  ·  Shift + Enter for new line';
            display: block;
            font-size: 11px;
            color: var(--text-secondary);
            opacity: .45;
            text-align: center;
            padding-top: 8px;
            letter-spacing: .015em;
        }

        /* ── Loading spinner ── */
        .chat-loading {
            display: flex; align-items: center; justify-content: center;
            flex-direction: column; gap: 14px;
            height: 100%;
            color: var(--text-secondary); font-size: 14px;
        }
        .chat-spinner {
            width: 32px; height: 32px;
            border: 2.5px solid var(--bg-tertiary);
            border-top-color: #10a37f;
            border-radius: 50%;
            animation: spin .75s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Theme toggle ── */
        .theme-toggle-btn {
            width: 34px; height: 34px;
            border-radius: 9999px;
            border: 1px solid var(--border);
            background: var(--input-bg);
            color: var(--text-secondary);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; flex-shrink: 0;
            transition: background .15s, color .15s;
        }
        .theme-toggle-btn:hover { background: var(--bg-tertiary); color: var(--text-primary); }
        .theme-toggle-btn svg { width: 16px; height: 16px; }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .chat-frame { max-width: 100%; border-left: none; border-right: none; }
            .n8n-chat { padding: 0 !important; }
            [class*="chat-inputs"] { padding: 10px !important; }
            .n8n-chat .chat-messages-list,
            .n8n-chat [class*="messages-list"] { padding-left: 16px !important; padding-right: 16px !important; }
            .n8n-chat .chat-input-container > div,
            .n8n-chat [class*="chat-footer"] > div { padding: 0 14px !important; }
            .n8n-chat .chat-input-send-button,
            .n8n-chat [class*="send-button"] { right: 17px !important; }
        }
    </style>
</head>
<body>

    <!-- ── Page load overlay ── -->
    <div id="page-loader">
        <div class="page-loader-ring"></div>
    </div>

    <!-- ── Mobile sidebar overlay ── -->
    <div id="mobile-sidebar-overlay" onclick="closeSidebar()"></div>

    <!-- ── Mobile sidebar ── -->
    <nav id="mobile-sidebar">
        <div class="msb-header">
            <div class="msb-brand">
                <span class="msb-brand-name"><?= htmlspecialchars($chatTitle) ?></span>
            </div>
            <button class="msb-close" onclick="closeSidebar()">
                <i data-feather="x"></i>
            </button>
        </div>
        <div class="msb-nav">
            <!-- Model selector -->
            <?php if (count($availableModels) > 0): ?>
            <div class="msb-section-label">Model</div>
            <div class="msb-model-wrap">
                <select class="msb-model-select" onchange="changeModel(this.value); closeSidebar();">
                    <?php foreach ($availableModels as $k => $v): ?>
                    <option value="<?= htmlspecialchars($k) ?>" <?= $k === $selectedModel ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="msb-divider"></div>
            <?php endif; ?>

            <!-- Chat actions -->
            <div class="msb-section-label">Chat</div>
            <button class="msb-action" onclick="startNewChat(); closeSidebar();">
                <i data-feather="edit-2"></i> New Conversation
            </button>
            <button class="msb-action" onclick="toggleTheme(); closeSidebar();" id="msb-theme-btn">
                <i data-feather="moon" id="msb-theme-icon"></i> Toggle Theme
            </button>
            <div class="msb-divider"></div>

            <!-- Navigation -->
            <div class="msb-section-label">Navigate</div>
            <a href="/dashboard" class="msb-action"><i data-feather="grid"></i> Dashboard</a>
            <a href="/settings" class="msb-action"><i data-feather="settings"></i> Settings</a>
            <a href="/logout" class="msb-action"><i data-feather="log-out"></i> Logout</a>
        </div>
        <div class="msb-footer">
            <div class="msb-user">
                <div class="msb-user-avatar"><?= strtoupper(substr($username ?? 'U', 0, 1)) ?></div>
                <div>
                    <div class="msb-user-name"><?= htmlspecialchars($username ?? 'User') ?></div>
                    <div class="msb-user-role">Arrissa AI</div>
                </div>
            </div>
        </div>
    </nav>

    <!-- ── Top-bar ── -->
    <header id="chat-topbar">
        <!-- Hamburger (mobile) -->
        <button class="topbar-hamburger" onclick="openSidebar()" title="Menu">
            <i data-feather="menu"></i>
        </button>

        <span class="topbar-title"><?= htmlspecialchars($chatTitle) ?></span>
        <span class="topbar-subtitle"><?= htmlspecialchars($chatSubtitle) ?></span>

        <div class="topbar-spacer"></div>

        <!-- Model selector -->
        <?php if (count($availableModels) > 0): ?>
        <div class="model-select-wrap">
            <select id="topModelSelect" onchange="changeModel(this.value)">
                <?php foreach ($availableModels as $k => $v): ?>
                <option value="<?= htmlspecialchars($k) ?>" <?= $k === $selectedModel ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <!-- New chat -->
        <button class="topbar-btn" onclick="startNewChat()" title="New chat">
            <i data-feather="edit-2"></i>
        </button>

        <!-- Theme toggle -->
        <button class="theme-toggle-btn" onclick="toggleTheme()" title="Toggle theme" id="themeBtn">
            <i data-feather="moon" id="theme-icon"></i>
        </button>

        <!-- Dashboard -->
        <a href="/dashboard" class="topbar-home">
            <i data-feather="grid"></i>
            <span class="topbar-home-label">Dashboard</span>
        </a>

        <!-- Avatar -->
        <div class="topbar-avatar" title="<?= htmlspecialchars($username ?? 'User') ?>">
            <?= strtoupper(substr($username ?? 'U', 0, 1)) ?>
        </div>
    </header>

    <!-- ── Chat area ── -->
    <div id="chat-area">
        <div class="chat-frame">

            <!-- No-webhook notice -->
            <div id="no-webhook-banner" <?= $webhookUrl ? '' : 'class="visible"' ?>>
                <div class="no-webhook-icon">
                    <i data-feather="alert-circle"></i>
                </div>
                <div style="font-size:15px;font-weight:600;color:var(--text-primary);">Webhook not configured</div>
                <div style="font-size:13px;color:var(--text-secondary);max-width:320px;line-height:1.5;">
                    Go to <a href="/settings#chat-settings" style="color:var(--accent);text-decoration:none;">Settings → Arrissa AI Chat</a> and add your n8n webhook URL to enable the chat.
                </div>
            </div>

            <!-- n8n widget target -->
            <div id="n8n-chat" <?= !$webhookUrl ? 'style="display:none;"' : '' ?>>
                <div class="chat-loading">
                    <div class="chat-spinner"></div>
                    <span>Starting <?= htmlspecialchars($chatTitle) ?>…</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Powered by (desktop) ── -->
    <footer id="powered-by-footer">
        <span class="pb-dot"></span>
        Powered by <a href="/chat">Arrissa AI</a>
        <span class="pb-dot"></span>
    </footer>

    <script type="module">
        import { createChat } from 'https://cdn.jsdelivr.net/npm/@n8n/chat/dist/chat.bundle.es.js';

        window.chatInstance   = null;
        window.currentSession = "<?= $sessionId ?>";
        window.currentModel   = "<?= addslashes($selectedModel) ?>";

        const WEBHOOK_URL       = <?= json_encode($webhookUrl) ?>;
        const ENABLE_STREAMING  = <?= $enableStreaming ? 'true' : 'false' ?>;
        const INITIAL_MESSAGES  = <?= json_encode($initialMessages) ?>;
        const CHAT_TITLE        = <?= json_encode($chatTitle) ?>;

        function initChat() {
            if (!WEBHOOK_URL) return;
            const el = document.getElementById('n8n-chat');
            el.style.display = '';
            el.innerHTML = '<div class="chat-loading"><div class="chat-spinner"></div><span>Starting ' + CHAT_TITLE + '…</span></div>';
            try {
                window.chatInstance = createChat({
                    webhookUrl: WEBHOOK_URL,
                    target: '#n8n-chat',
                    mode: 'fullscreen',
                    loadPreviousSession: true,
                    chatSessionKey: 'sessionId',
                    chatInputKey: 'chatInput',
                    showWelcomeScreen: false,
                    enableStreaming: ENABLE_STREAMING,
                    initialMessages: INITIAL_MESSAGES,
                    metadata: {
                        sessionId: window.currentSession,
                        model: window.currentModel,
                        timestamp: new Date().toISOString()
                    },
                    webhookConfig: {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Session-ID': window.currentSession,
                            'X-Model': window.currentModel
                        }
                    },
                    i18n: {
                        en: {
                            title: '',
                            subtitle: '',
                            inputPlaceholder: 'Message ' + CHAT_TITLE + '…',
                            footer: '',
                            getStarted: 'Start chatting'
                        }
                    }
                });
                patchFetch();
            } catch (e) { console.error('Chat init error:', e); }
        }

        function patchFetch() {
            const orig = window.fetch;
            window.fetch = function(...args) {
                const [url, opts] = args;
                if (url && WEBHOOK_URL && url.includes(new URL(WEBHOOK_URL).pathname)) {
                    try {
                        const d = JSON.parse(opts.body);
                        opts.body = JSON.stringify({
                            sessionId: window.currentSession,
                            action: d.action || 'sendMessage',
                            chatInput: d.chatInput || d.message || '',
                            model: window.currentModel
                        });
                    } catch (_) {}
                }
                return orig.apply(this, args);
            };
        }

        // Expose for model selector
        window.changeModel = async function(newModel) {
            try {
                const res  = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'selected_model=' + encodeURIComponent(newModel)
                });
                const json = await res.json();
                if (json.status === 'success') {
                    window.currentSession = json.newSessionId;
                    window.currentModel   = json.selectedModel;
                    // wipe local n8n storage
                    Object.keys(localStorage)
                        .filter(k => k.includes('n8n-chat'))
                        .forEach(k => localStorage.removeItem(k));
                    initChat();
                    injectLateStyles();
                }
            } catch (e) { console.error(e); }
        };

        window.startNewChat = function() {
            window.currentSession = crypto.randomUUID ? crypto.randomUUID().replace(/-/g,'') : Math.random().toString(36).slice(2).repeat(2).slice(0,32);
            Object.keys(localStorage)
                .filter(k => k.includes('n8n-chat'))
                .forEach(k => localStorage.removeItem(k));
            initChat();
            injectLateStyles();
        };

        initChat();

        // Inject override styles AFTER n8n widget — guarantees our rules beat widget-injected styles
        function injectLateStyles() {
            const s = document.createElement('style');
            s.id = 'arrissa-late-overrides';
            s.textContent = `
                .n8n-chat .chat-input-send-button,
                .n8n-chat [class*="send-button"],
                .n8n-chat button[aria-label*="end"],
                .n8n-chat button[class*="send"] {
                    position: absolute !important;
                    right: 10px !important;
                    top: 38% !important;
                    bottom: 0 !important;
                    transform: translateY(-50%) !important;
                    width: 48px !important;
                    height: 48px !important;
                    border-radius: 9999px !important;
                    background: #10a37f !important;
                    border: none !important;
                    color: #fff !important;
                    cursor: pointer !important;
                }
                @media (max-width: 768px) {
                    .n8n-chat .chat-input-send-button,
                    .n8n-chat [class*="send-button"],
                    .n8n-chat button[aria-label*="end"],
                    .n8n-chat button[class*="send"] {
                        right: 17px !important;
                    }
                }
                .n8n-chat .chat-input,
                .n8n-chat textarea[class*="input"],
                [class*="chat-inputs"] textarea {
                    background: transparent !important;
                    border: none !important;
                    border-radius: 0 !important;
                    box-shadow: none !important;
                    outline: none !important;
                }
            `;
            const old = document.getElementById('arrissa-late-overrides');
            if (old) old.remove();
            document.head.appendChild(s);
        }
        injectLateStyles();
    </script>

    <script>
        feather.replace();

        // Dismiss page loader once everything (fonts, scripts, widget) is ready
        window.addEventListener('load', function() {
            const loader = document.getElementById('page-loader');
            if (loader) loader.classList.add('hidden');
        });

        // Mobile sidebar
        function openSidebar() {
            document.getElementById('mobile-sidebar').classList.add('open');
            document.getElementById('mobile-sidebar-overlay').classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function closeSidebar() {
            document.getElementById('mobile-sidebar').classList.remove('open');
            document.getElementById('mobile-sidebar-overlay').classList.remove('open');
            document.body.style.overflow = '';
        }
        window.openSidebar = openSidebar;
        window.closeSidebar = closeSidebar;

        // Theme
        const THEME_KEY = 'arrissa_theme';
        function applyTheme(t) {
            document.body.classList.toggle('light-theme', t === 'light');
            const ic = document.getElementById('theme-icon');
            const icMsb = document.getElementById('msb-theme-icon');
            const iconName = t === 'light' ? 'sun' : 'moon';
            if (ic) { ic.setAttribute('data-feather', iconName); }
            if (icMsb) { icMsb.setAttribute('data-feather', iconName); }
            feather.replace();
        }
        function toggleTheme() {
            const next = document.body.classList.contains('light-theme') ? 'dark' : 'light';
            localStorage.setItem(THEME_KEY, next);
            applyTheme(next);
        }
        applyTheme(localStorage.getItem(THEME_KEY) || 'dark');
    </script>
</body>
</html>
