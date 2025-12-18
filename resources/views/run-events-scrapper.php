<?php
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Database.php';

$db = Database::getInstance();
$title = 'Run Events Scrapper';
$page = 'run-events-scrapper';
ob_start();
?>

<style>
.terminal-window {
    background: #1e1e1e;
    border-radius: 12px;
    padding: 20px;
    font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
    color: #d4d4d4;
    min-height: 400px;
    max-height: 600px;
    overflow-y: auto;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
}

.terminal-line {
    margin: 4px 0;
    line-height: 1.5;
}

.terminal-line.info {
    color: #4fc3f7;
}

.terminal-line.success {
    color: #81c784;
}

.terminal-line.error {
    color: #e57373;
}

.terminal-line.output {
    color: #d4d4d4;
}

.run-button {
    background: linear-gradient(135deg, #4f46e5, #6366f1);
    color: white;
    padding: 12px 32px;
    border-radius: 9999px;
    font-weight: 600;
    transition: all 0.3s;
    border: none;
    cursor: pointer;
    font-size: 16px;
}

.run-button:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(79, 70, 229, 0.3);
}

.run-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.status-badge {
    display: inline-block;
    padding: 6px 16px;
    border-radius: 9999px;
    font-size: 14px;
    font-weight: 600;
    margin-left: 12px;
}

.status-badge.idle {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-secondary);
}

.status-badge.running {
    background: rgba(79, 70, 229, 0.2);
    color: #6366f1;
    animation: pulse 2s infinite;
}

.status-badge.success {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
}

.status-badge.error {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-right: 8px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>

<div class="p-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold mb-3" style="color: var(--text-primary);">Events Scrapper</h1>
        <p style="color: var(--text-secondary);" class="text-lg">
            Run the economic events scrapper to update the database with the latest forex news events.
        </p>
    </div>

    <!-- Controls -->
    <div class="mb-6 flex flex-wrap items-center gap-4">
        <button onclick="copyPath()" class="run-button inline-flex items-center" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
            <i data-feather="copy" style="width: 18px; height: 18px; margin-right: 8px;"></i>
            <span>Copy Folder Path</span>
        </button>
    </div>

    <!-- Instructions -->
    <div class="mb-6 p-6 rounded-2xl" style="background: var(--card-bg); border: 1px solid var(--border);">
        <h3 class="text-lg font-semibold mb-3 flex items-center" style="color: var(--text-primary);">
            <i data-feather="info" style="width: 20px; height: 20px; margin-right: 8px;"></i>
            <span>How to Run the Scrapper</span>
        </h3>
        
        <div class="mb-4 p-4 rounded-xl" style="background: var(--input-bg); border: 1px solid var(--input-border);">
            <div class="mb-3">
                <div class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Windows Users:</div>
                <div class="flex items-center gap-2">
                    <code style="background: var(--bg-primary); padding: 8px 12px; border-radius: 8px; flex: 1; color: var(--text-primary); font-size: 0.875rem;">C:\wamp64\www\events-scrapper-nodejs\start-scrapper.cmd</code>
                    <button onclick="copyWindowsPath()" class="px-3 py-2 rounded-full text-xs font-semibold" style="background: var(--accent); color: white;">
                        Copy
                    </button>
                </div>
            </div>
            
            <div>
                <div class="text-sm font-semibold mb-2" style="color: var(--text-primary);">Linux/Mac Users:</div>
                <div class="flex items-center gap-2">
                    <code style="background: var(--bg-primary); padding: 8px 12px; border-radius: 8px; flex: 1; color: var(--text-primary); font-size: 0.875rem;">/var/www/html/events-scrapper-nodejs/start-scrapper.sh</code>
                    <button onclick="copyLinuxPath()" class="px-3 py-2 rounded-full text-xs font-semibold" style="background: var(--accent); color: white;">
                        Copy
                    </button>
                </div>
            </div>
        </div>
        
        <ol class="space-y-3" style="color: var(--text-secondary); list-style: none; margin-left: 0;">
            <li class="flex items-start">
                <i data-feather="chevron-right" style="width: 16px; height: 16px; margin-right: 8px; margin-top: 2px; flex-shrink: 0; color: var(--accent);"></i>
                <span><strong>Step 1:</strong> Click "Copy Folder Path" or copy the script path for your OS above</span>
            </li>
            <li class="flex items-start">
                <i data-feather="chevron-right" style="width: 16px; height: 16px; margin-right: 8px; margin-top: 2px; flex-shrink: 0; color: var(--accent);"></i>
                <span><strong>Step 2:</strong> Open File Explorer (Windows) or Finder (Mac) and paste the path</span>
            </li>
            <li class="flex items-start">
                <i data-feather="chevron-right" style="width: 16px; height: 16px; margin-right: 8px; margin-top: 2px; flex-shrink: 0; color: var(--accent);"></i>
                <span><strong>Step 3:</strong> Double-click <code style="background: var(--input-bg); padding: 2px 8px; border-radius: 4px;">start-scrapper.cmd</code> (Windows) or <code style="background: var(--input-bg); padding: 2px 8px; border-radius: 4px;">start-scrapper.sh</code> (Linux/Mac)</span>
            </li>
            <li class="flex items-start">
                <i data-feather="chevron-right" style="width: 16px; height: 16px; margin-right: 8px; margin-top: 2px; flex-shrink: 0; color: var(--accent);"></i>
                <span><strong>Linux/Mac only:</strong> First time? Make executable: <code style="background: var(--input-bg); padding: 2px 8px; border-radius: 4px;">chmod +x start-scrapper.sh</code></span>
            </li>
        </ol>
        <p class="mt-3 flex items-start" style="color: var(--text-secondary); font-size: 0.875rem;">
            <i data-feather="alert-circle" style="width: 16px; height: 16px; margin-right: 4px; margin-top: 2px; flex-shrink: 0; color: var(--warning);"></i>
            <span>The scrapper must run from its original folder where dependencies are installed. Do not move or copy the script files.</span>
        </p>
    </div>

    <!-- Terminal Window -->
    <div class="terminal-window" id="terminal">
        <div class="terminal-line info">Navigate to the folder above and run the script file for your operating system.</div>
        <div class="terminal-line info">The scrapper will open in a command prompt window with an interactive menu.</div>
        <div class="terminal-line success">All dependencies are already installed in the original folder.</div>
    </div>
</div>

<script>
function copyPath() {
    const path = 'C:\\wamp64\\www\\events-scrapper-nodejs\\';
    copyTextToClipboard(path, event.target.closest('button'));
}

function copyWindowsPath() {
    const path = 'C:\\wamp64\\www\\events-scrapper-nodejs\\start-scrapper.cmd';
    copyTextToClipboard(path, event.target);
}

function copyLinuxPath() {
    const path = '/var/www/html/events-scrapper-nodejs/start-scrapper.sh';
    copyTextToClipboard(path, event.target);
}

function copyTextToClipboard(text, button) {
    // Try modern clipboard API first
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(() => {
            showCopySuccess(button);
        }).catch(err => {
            fallbackCopyText(text, button);
        });
    } else {
        fallbackCopyText(text, button);
    }
}

function fallbackCopyText(text, button) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    
    try {
        const successful = document.execCommand('copy');
        if (successful) {
            showCopySuccess(button);
        } else {
            showCopyError(text);
        }
    } catch (err) {
        showCopyError(text);
    }
    
    document.body.removeChild(textarea);
}

function showCopySuccess(button) {
    const originalHTML = button.innerHTML;
    button.innerHTML = button.classList.contains('run-button') 
        ? '<i data-feather="check" style="width: 18px; height: 18px; margin-right: 8px;"></i><span>Copied!</span>'
        : 'Copied!';
    feather.replace();
    
    setTimeout(() => {
        button.innerHTML = originalHTML;
        feather.replace();
    }, 2000);
}

function showCopyError(path) {
    alert('Path copied to show:\n\n' + path + '\n\nPlease copy it manually from this dialog.');
}

// Feather icons
feather.replace();
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layouts/app.php';
