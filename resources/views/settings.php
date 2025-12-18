<?php
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Database.php';

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

$db = Database::getInstance();
$username = Auth::getUser();

// Get settings from database
function getSetting($db, $key, $default = '') {
    $stmt = $db->query("SELECT value FROM settings WHERE key = ?", [$key]);
    $result = $stmt->fetch();
    return $result ? $result['value'] : $default;
}

$appName = getSetting($db, 'app_name', 'Arrissa Data API');
$apiKey = getSetting($db, 'api_key', 'arr_' . bin2hex(random_bytes(8)));
$appBaseUrl = getSetting($db, 'app_base_url', 'http://' . $_SERVER['HTTP_HOST']);

$title = 'Settings';
$page = 'settings';
ob_start();
?>

<div class="p-8 max-w-2xl mx-auto space-y-4">
    
    <?php if ($success || $error): ?>
    <div class="text-sm px-4 py-2 rounded-lg inline-flex items-center" style="background-color: <?php echo $error ? 'rgba(239, 68, 68, 0.1)' : 'rgba(16, 185, 129, 0.1)'; ?>; color: <?php echo $error ? 'var(--danger)' : 'var(--success)'; ?>;">
        <i data-feather="<?php echo $error ? 'x' : 'check'; ?>" style="width: 16px; height: 16px; margin-right: 8px;"></i>
        <?php 
            if ($success === 'app_name') echo 'Saved';
            elseif ($success === 'api_key') echo 'Refreshed';
            elseif ($success === 'password') echo 'Updated';
            elseif ($success === 'base_url') echo 'Saved';
            else echo 'Error';
        ?>
    </div>
    <?php endif; ?>

    <div class="p-4 rounded-2xl flex items-center space-x-4" style="background-color: var(--card-bg); border: 1px solid var(--border);">
        <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background-color: var(--text-primary);">
            <span class="font-bold text-lg" style="color: var(--bg-primary);"><?php echo strtoupper(substr($username, 0, 1)); ?></span>
        </div>
        <div>
            <div class="text-base font-medium" style="color: var(--text-primary);"><?php echo htmlspecialchars($username); ?></div>
            <div class="text-sm" style="color: var(--text-secondary);">Administrator</div>
        </div>
    </div>

    <form method="POST" action="/settings/update-app-name">
        <div class="p-4 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <label class="text-sm mb-3 block" style="color: var(--text-secondary);">App Name</label>
            <div class="flex space-x-3">
                <input type="text" name="app_name" value="<?php echo htmlspecialchars($appName); ?>" class="flex-1 rounded-lg px-4 py-3 text-base focus:outline-none" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);" required>
                <button type="submit" class="text-sm px-5 py-3 rounded-lg font-medium" style="background-color: var(--text-primary); color: var(--bg-primary);">Save</button>
            </div>
        </div>
    </form>

    <form method="POST" action="/settings/update-base-url">
        <div class="p-4 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <label class="text-sm mb-3 block" style="color: var(--text-secondary);">App Base URL</label>
            <div class="flex space-x-3">
                <input type="url" name="app_base_url" value="<?php echo htmlspecialchars($appBaseUrl); ?>" class="flex-1 rounded-lg px-4 py-3 text-base focus:outline-none" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);" required>
                <button type="submit" class="text-sm px-5 py-3 rounded-lg font-medium" style="background-color: var(--text-primary); color: var(--bg-primary);">Save</button>
            </div>
        </div>
    </form>

    <div class="p-4 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
        <div class="flex justify-between items-center mb-3">
            <label class="text-sm" style="color: var(--text-secondary);">API Key</label>
            <button type="button" onclick="showRefreshKeyModal()" class="text-sm px-3 py-2 rounded-lg" style="background-color: var(--input-bg); color: var(--text-secondary); border: 1px solid var(--input-border);">
                <i data-feather="refresh-cw" style="width: 14px; height: 14px; display: inline;"></i>
            </button>
        </div>
        <div class="flex space-x-3">
            <input type="text" id="apiKeyInput" value="<?php echo htmlspecialchars($apiKey); ?>" readonly class="flex-1 rounded-lg px-4 py-3 text-sm font-mono focus:outline-none" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);">
            <button onclick="copyApiKey(this)" class="px-4 py-3 rounded-lg" style="background-color: var(--input-bg); color: var(--text-secondary); border: 1px solid var(--input-border);">
                <i data-feather="copy" style="width: 18px; height: 18px;"></i>
            </button>
        </div>
    </div>

    <!-- Hidden form for API key refresh -->
    <form id="refreshKeyForm" method="POST" action="/settings/refresh-api-key" style="display: none;">
        <input type="hidden" name="confirm" value="1">
    </form>

    <form method="POST" action="/settings/change-password">
        <div class="p-4 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <label class="text-sm mb-3 block" style="color: var(--text-secondary);">Change Password</label>
            <div class="space-y-3">
                <input type="password" name="current_password" placeholder="Current" class="w-full rounded-lg px-4 py-3 text-base focus:outline-none" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);" required>
                <input type="password" name="new_password" placeholder="New" class="w-full rounded-lg px-4 py-3 text-base focus:outline-none" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);" required>
                <input type="password" name="confirm_password" placeholder="Confirm" class="w-full rounded-lg px-4 py-3 text-base focus:outline-none" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);" required>
            </div>
            <button type="submit" class="mt-3 text-sm px-5 py-3 rounded-lg font-medium w-full" style="background-color: var(--text-primary); color: var(--bg-primary);">Update</button>
        </div>
    </form>

</div>

<!-- API Key Refresh Confirmation Modal -->
<div id="refreshKeyModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
    <div class="p-8 rounded-2xl" style="background-color: var(--card-bg); border: 2px solid var(--warning); max-width: 600px; width: 100%;">
        <div class="flex items-start mb-6">
            <div class="w-12 h-12 rounded-full flex items-center justify-center mr-4 flex-shrink-0" style="background-color: var(--warning-bg);">
                <i data-feather="alert-triangle" style="width: 24px; height: 24px; color: var(--warning);"></i>
            </div>
            <div class="flex-1">
                <h3 class="text-xl font-bold mb-2" style="color: var(--warning);">Refresh API Key</h3>
                <p class="text-sm" style="color: var(--text-secondary);">This will change your API key for all your requests.</p>
            </div>
        </div>
        <div class="p-4 rounded-lg mb-6" style="background-color: var(--warning-bg); border: 1px solid var(--warning);">
            <p class="text-xs font-semibold mb-2" style="color: var(--text-primary);">Important Warning:</p>
            <ul class="text-xs space-y-1" style="color: var(--text-secondary);">
                <li>• Your current API key will be invalidated immediately</li>
                <li>• All existing integrations using the old key will stop working</li>
                <li>• You will need to update the API key in all your applications</li>
            </ul>
        </div>
        <div class="flex gap-3">
            <button onclick="confirmRefreshKey()" class="flex-1 px-4 py-3 rounded-lg text-sm font-semibold transition-all" style="background-color: var(--warning); color: white;" onmouseover="this.style.opacity='0.9';" onmouseout="this.style.opacity='1';">
                <i data-feather="refresh-cw" class="inline-block mr-2" style="width: 14px; height: 14px;"></i>
                Yes, Generate New Key
            </button>
            <button onclick="closeRefreshKeyModal()" class="flex-1 px-4 py-3 rounded-lg text-sm font-semibold transition-all" style="background-color: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border);" onmouseover="this.style.backgroundColor='var(--bg-tertiary)';" onmouseout="this.style.backgroundColor='var(--bg-secondary)';">
                Cancel
            </button>
        </div>
    </div>
</div>

<script>
function showRefreshKeyModal() {
    document.getElementById('refreshKeyModal').style.display = 'flex';
    feather.replace();
}

function closeRefreshKeyModal() {
    document.getElementById('refreshKeyModal').style.display = 'none';
}

function confirmRefreshKey() {
    document.getElementById('refreshKeyForm').submit();
}

function copyApiKey(btn) {
    const input = document.getElementById('apiKeyInput');
    navigator.clipboard.writeText(input.value).then(() => {
        btn.innerHTML = '<i data-feather="check" style="width: 14px; height: 14px;"></i>';
        feather.replace();
        setTimeout(() => {
            btn.innerHTML = '<i data-feather="copy" style="width: 14px; height: 14px;"></i>';
            feather.replace();
        }, 1500);
    });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/app.php';
?>
