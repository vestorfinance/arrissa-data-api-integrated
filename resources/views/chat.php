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

        .chat-frame {
            width: 100%;
            max-width: 860px;
            display: flex;
            flex-direction: column;
            background: var(--card-bg);
            border-left: 1px solid var(--border);
            border-right: 1px solid var(--border);
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

        /* ── n8n CSS tokens ── */
        :root {
            --chat--color-primary:            var(--ai-primary);
            --chat--color-primary-shade-50:   var(--ai-hover);
            --chat--color-secondary:          var(--ai-primary);
            --chat--color-light:              var(--bg-secondary);
            --chat--color-light-shade-50:     var(--bg-tertiary);
            --chat--color-medium:             var(--border);
            --chat--color-dark:               var(--bg-primary);
            --chat--color-white:              var(--text-primary);
            --chat--border-radius:            16px;
            --chat--header-height:            0px;
            --chat--header--padding:          0px;
            --chat--header--background:       transparent;
            --chat--header--color:            transparent;
            --chat--header--border-top:       none;
            --chat--header--border-bottom:    none;
            --chat--textarea--height:         56px;
            --chat--message--font-size:       14.5px;
            --chat--message--padding:         12px 16px;
            --chat--message--border-radius:   18px;
            --chat--message--bot--background: var(--bg-tertiary);
            --chat--message--bot--color:      var(--text-primary);
            --chat--message--user--background: var(--ai-primary);
            --chat--message--user--color:     #fff;
        }

        /* ── Kill default header ── */
        .n8n-chat .chat-header,
        .n8n-chat-header,
        [class*="chat-header"] {
            display: none !important;
            height: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            overflow: hidden !important;
        }

        /* ── Root container ── */
        .n8n-chat {
            background: var(--bg-primary) !important;
            color: var(--text-primary) !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif !important;
            height: 100% !important;
            display: flex !important;
            flex-direction: column !important;
            overflow: hidden !important;
        }

        /* ── Messages scroll area ── */
        .n8n-chat .chat-messages-container,
        .n8n-chat [class*="messages"] {
            background: var(--bg-primary) !important;
            flex: 1 1 0% !important;
            overflow-y: auto !important;
            padding: 24px 28px 32px !important;
            scrollbar-width: thin;
            scrollbar-color: var(--input-border) transparent;
        }
        .n8n-chat .chat-messages-container::-webkit-scrollbar { width: 5px; }
        .n8n-chat .chat-messages-container::-webkit-scrollbar-track { background: transparent; }
        .n8n-chat .chat-messages-container::-webkit-scrollbar-thumb { background: var(--input-border); border-radius: 4px; }

        /* ── Message bubbles ── */
        .n8n-chat .chat-message { margin-bottom: 4px !important; }
        .n8n-chat .chat-message .chat-message-markdown,
        .n8n-chat .chat-message .chat-message-text {
            font-size: 14.5px !important;
            line-height: 1.65 !important;
            letter-spacing: -0.01em !important;
        }
        /* bot bubble */
        .n8n-chat .chat-message-from-bot .chat-message-text,
        .n8n-chat .chat-messages-message-from-bot .chat-message-text {
            background: var(--bg-tertiary) !important;
            color: var(--text-primary) !important;
            border-radius: 4px 18px 18px 18px !important;
        }
        /* user bubble */
        .n8n-chat .chat-message-from-user .chat-message-text,
        .n8n-chat .chat-messages-message-from-user .chat-message-text {
            background: var(--ai-primary) !important;
            color: #fff !important;
            border-radius: 18px 4px 18px 18px !important;
        }

        /* ── Input section — the "composer" ── */
        .n8n-chat .chat-input-container,
        .n8n-chat [class*="chat-footer"],
        .n8n-chat [class*="input-wrapper"] {
            background: var(--bg-primary) !important;
            border-top: none !important;
            padding: 8px 20px 16px !important;
            padding-bottom: max(16px, env(safe-area-inset-bottom, 16px)) !important;
            flex-shrink: 0 !important;
        }

        /* Inner row that wraps textarea + send button */
        .n8n-chat .chat-input-container > *,
        .n8n-chat [class*="chat-footer"] > * {
            position: relative !important;
        }

        /* The textarea */
        .n8n-chat .chat-input,
        .n8n-chat textarea {
            background: var(--input-bg) !important;
            border: 1.5px solid var(--input-border) !important;
            border-radius: 16px !important;
            color: var(--text-primary) !important;
            font-size: 15px !important;
            font-family: 'Inter', sans-serif !important;
            line-height: 1.55 !important;
            padding: 15px 56px 15px 18px !important;
            resize: none !important;
            width: 100% !important;
            min-height: 56px !important;
            max-height: 220px !important;
            transition: border-color .18s ease, box-shadow .18s ease !important;
            box-shadow: 0 2px 12px rgba(0,0,0,.18), 0 1px 3px rgba(0,0,0,.12) !important;
        }
        .n8n-chat .chat-input:focus,
        .n8n-chat textarea:focus {
            outline: none !important;
            border-color: var(--ai-primary) !important;
            box-shadow: 0 2px 12px rgba(0,0,0,.18), 0 0 0 3px rgba(16,163,127,.18) !important;
        }
        .n8n-chat .chat-input::placeholder,
        .n8n-chat textarea::placeholder {
            color: var(--text-secondary) !important;
            opacity: 0.7 !important;
        }

        /* ── Send button — rounded square, bottom-right anchored ── */
        .n8n-chat .chat-input-send-button,
        .n8n-chat [class*="send-button"],
        .n8n-chat button[type="submit"] {
            background: var(--ai-primary) !important;
            border: none !important;
            border-radius: 11px !important;
            width: 38px !important;
            height: 38px !important;
            min-width: 38px !important;
            min-height: 38px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            color: #fff !important;
            position: absolute !important;
            right: 10px !important;
            bottom: 10px !important;
            top: auto !important;
            transform: none !important;
            cursor: pointer !important;
            transition: background .15s, transform .12s cubic-bezier(.34,1.56,.64,1), box-shadow .15s !important;
            box-shadow: 0 2px 8px rgba(16,163,127,.35) !important;
        }
        .n8n-chat .chat-input-send-button:hover,
        .n8n-chat [class*="send-button"]:hover,
        .n8n-chat button[type="submit"]:hover {
            background: var(--ai-hover) !important;
            transform: scale(1.07) !important;
            box-shadow: 0 4px 14px rgba(16,163,127,.45) !important;
        }
        .n8n-chat .chat-input-send-button:active,
        .n8n-chat [class*="send-button"]:active {
            transform: scale(0.94) !important;
            transition-duration: .06s !important;
        }

        /* Typing / loading dots */
        .n8n-chat [class*="loading"] span,
        .n8n-chat [class*="typing"] span {
            background: var(--ai-primary) !important;
        }

        /* ── Hint line below textarea ── */
        .n8n-chat .chat-input-container::after {
            content: 'Enter to send · Shift + Enter for new line';
            display: block;
            font-size: 11px;
            color: var(--text-secondary);
            opacity: 0.55;
            text-align: center;
            padding-top: 7px;
            letter-spacing: .01em;
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
            border-top-color: var(--ai-primary);
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
            #chat-area { padding: 0; }
            .n8n-chat .chat-messages-container { padding: 16px 16px 0 !important; }
            .n8n-chat .chat-input-container,
            .n8n-chat [class*="chat-footer"] { padding: 8px 12px 12px !important; }
        }
    </style>
</head>
<body>

    <!-- ── Top-bar ── -->
    <header id="chat-topbar">
        <!-- Logo -->
        <div class="topbar-logo">
            <svg viewBox="0 0 7000 7000" xmlns="http://www.w3.org/2000/svg">
                <g><path d="M3534.57 2921.26l509.33 278.51 0 600.85 -543.85 297.38 -543.84 -297.38 0 -600.85 543.84 -297.38 34.51 18.87zm166.69 255.62l-201.2 -110.02 -399.01 218.18 0 430.3 399.01 218.19 399.01 -218.19 0 -430.3 -197.81 -108.16z"/><path d="M3206.76 1423.91l672.75 0 0 1366.1 -745.17 0 0 -1366.1 72.42 0zm527.92 144.83l-455.5 0 0 1076.43 455.5 0 0 -1076.43z"/><polygon points="3436.12,1496.03 3436.12,899.79 3580.96,899.79 3580.96,1496.03"/><polygon points="3432.32,3004.16 3432.32,2675.89 3577.15,2675.89 3577.15,3004.16"/><path d="M3203.34 5576.08l672.75 0 0 -1366.09 -745.17 0 0 1366.09 72.42 0zm527.92 -144.83l-455.5 0 0 -1076.43 455.5 0 0 -1076.43z"/><polygon points="3432.7,5503.96 3432.7,6100.2 3577.53,6100.2 3577.53,5503.96"/><polygon points="3428.89,3986.69 3428.89,4314.95 3573.73,4314.95 3573.73,3986.69"/><path d="M5172.55 4811.44l336.37 -582.62 -1183.07 -683.05 -372.59 645.33 1183.07 683.05 36.21 -62.72zm138.53 -529.61l-227.75 394.48 -932.21 -538.21 227.75 -394.48 932.21 538.21z"/><path d="M5479.29 2714.84l-336.37 -582.62 -1183.07 683.05 372.58 645.33 1183.07 -683.05 -36.21 -62.71zm-389.39 -384.77l227.75 394.47 -932.21 538.21 -227.75 -394.47 932.21 -538.21z"/><path d="M1829.86 4820.48l-336.38 -582.62 1183.07 -683.05 372.59 645.33 -1183.07 683.05 -36.21 -62.72zm-138.53 -529.61l227.75 394.47 932.21 -538.21 -227.75 -394.47 -932.21 538.21z"/><path d="M1520.7 2723.73l336.38 -582.62 1183.07 683.05 -372.58 645.33 -1183.07 -683.05 36.21 -62.72zm389.39 -384.77l-227.75 394.47 932.21 538.22 227.75 -394.48 -932.21 -538.21z"/></g>
            </svg>
        </div>

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
                }
            } catch (e) { console.error(e); }
        };

        window.startNewChat = function() {
            window.currentSession = crypto.randomUUID ? crypto.randomUUID().replace(/-/g,'') : Math.random().toString(36).slice(2).repeat(2).slice(0,32);
            Object.keys(localStorage)
                .filter(k => k.includes('n8n-chat'))
                .forEach(k => localStorage.removeItem(k));
            initChat();
        };

        initChat();
    </script>

    <script>
        feather.replace();

        // Theme
        const THEME_KEY = 'arrissa_theme';
        function applyTheme(t) {
            document.body.classList.toggle('light-theme', t === 'light');
            const ic = document.getElementById('theme-icon');
            if (ic) { ic.setAttribute('data-feather', t === 'light' ? 'sun' : 'moon'); feather.replace(); }
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
