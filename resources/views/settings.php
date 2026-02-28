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

    <div class="p-4 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
        <label class="text-sm mb-3 block" style="color: var(--text-secondary);">App Name</label>
        <div class="px-4 py-3 rounded-lg" style="background-color: var(--input-bg); border: 1px solid var(--input-border);">
            <span class="text-base" style="color: var(--text-secondary); opacity: 0.5;"><?php echo htmlspecialchars($appName); ?></span>
        </div>
    </div>

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

    <!-- Pull Updates -->
    <div class="p-4 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
        <div class="flex justify-between items-center">
            <div>
                <div class="text-sm font-medium" style="color: var(--text-primary);">App Updates</div>
                <div class="text-xs mt-0.5" style="color: var(--text-secondary);">Pull latest code from the Git repository</div>
            </div>
            <button id="updateBtn" onclick="pullUpdates()" class="text-sm px-5 py-2.5 rounded-lg font-medium flex items-center gap-2" style="background-color: var(--text-primary); color: var(--bg-primary);">
                <i data-feather="download-cloud" style="width: 15px; height: 15px;"></i>
                Pull Updates
            </button>
        </div>
        <div id="updateOutput" style="display:none;" class="mt-3 p-3 rounded-lg text-xs font-mono whitespace-pre-wrap break-all" style="background-color: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-secondary); max-height: 160px; overflow-y: auto;"></div>
    </div>

    <!-- n8n Update -->
    <div class="p-4 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
        <div class="flex justify-between items-center">
            <div>
                <div class="text-sm font-medium" style="color: var(--text-primary);">n8n Update</div>
                <div class="text-xs mt-0.5" style="color: var(--text-secondary);">Update n8n to the latest version and restart it</div>
            </div>
            <button id="n8nUpdateBtn" onclick="updateN8n()" class="text-sm px-5 py-2.5 rounded-lg font-medium flex items-center gap-2" style="background-color: var(--accent); color: #fff;">
                <i data-feather="zap" style="width: 15px; height: 15px;"></i>
                Update n8n
            </button>
        </div>
        <div id="n8nUpdateOutput" style="display:none;" class="mt-3 p-3 rounded-lg text-xs font-mono whitespace-pre-wrap break-all" style="background-color: var(--input-bg); border: 1px solid var(--input-border); max-height: 200px; overflow-y: auto;"></div>
        <!-- Docker instructions panel (hidden until needed) -->
        <div id="n8nDockerPanel" style="display:none;" class="mt-3">
            <div class="text-xs mb-2" style="color: var(--text-secondary);">n8n is running in Docker. Run these commands on your server to update:</div>
            <div class="p-3 rounded-lg text-xs font-mono whitespace-pre" style="background-color: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-primary); overflow-x: auto;" id="n8nDockerCmds"></div>
        </div>
    </div>

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

async function pullUpdates() {
    const btn = document.getElementById('updateBtn');
    const out = document.getElementById('updateOutput');

    btn.disabled = true;
    btn.innerHTML = '<i data-feather="loader" style="width: 15px; height: 15px;"></i> Pulling…';
    feather.replace();
    out.style.display = 'none';
    out.textContent = '';

    try {
        const res = await fetch('/api/update-app', { method: 'POST' });
        const data = await res.json();

        out.style.display = 'block';
        out.style.backgroundColor = 'var(--input-bg)';
        out.style.border = '1px solid var(--input-border)';
        out.style.color = data.success ? 'var(--success)' : 'var(--danger)';

        if (data.success && data.already_up_to_date) {
            out.textContent = '✓ Already up to date.';
        } else if (data.success) {
            out.textContent = '✓ Updated successfully.\n\n' + data.output;
        } else {
            out.textContent = '✗ ' + data.error + '\n\n' + data.output;
        }

        btn.innerHTML = '<i data-feather="download-cloud" style="width: 15px; height: 15px;"></i> Pull Updates';
        btn.disabled = false;
        feather.replace();
    } catch (e) {
        out.style.display = 'block';
        out.style.color = 'var(--danger)';
        out.textContent = '✗ Request failed: ' + e.message;
        btn.innerHTML = '<i data-feather="download-cloud" style="width: 15px; height: 15px;"></i> Pull Updates';
        btn.disabled = false;
        feather.replace();
    }
}

async function updateN8n() {
    const btn    = document.getElementById('n8nUpdateBtn');
    const out    = document.getElementById('n8nUpdateOutput');
    const docker = document.getElementById('n8nDockerPanel');
    const cmds   = document.getElementById('n8nDockerCmds');

    btn.disabled = true;
    btn.innerHTML = '<i data-feather="loader" style="width:15px;height:15px;"></i> Updating…';
    feather.replace();
    out.style.display = 'none';
    docker.style.display = 'none';
    out.textContent = '';

    try {
        const res  = await fetch('/api/update-n8n', { method: 'POST' });
        const data = await res.json();

        // Docker / local case — show manual commands
        if (data.docker_info) {
            const container = data.container || 'n8n';
            docker.style.display = 'block';
            cmds.textContent =
`# If using Docker Desktop (local) or standalone docker run:
docker pull n8nio/n8n
docker stop ${container}
docker rm ${container}
# Then re-run your original docker run command with the same volumes/env.

# If using Docker Compose (find your compose file directory first):
docker compose pull n8n
docker compose up -d n8n`;
            out.style.display = 'block';
            out.style.color = 'var(--warning)';
            out.textContent = '⚠ ' + data.message;
        } else if (data.success) {
            out.style.display = 'block';
            const same = data.already_latest;
            out.style.color = 'var(--success)';
            let txt = same
                ? `✓ Already on latest version (${data.new_version}).`
                : `✓ Updated: ${data.old_version} → ${data.new_version}.`;
            if (data.restart_method && data.restart_method !== 'none') {
                txt += `\nRestarted via ${data.restart_method}.`;
            } else if (!same) {
                txt += '\n⚠ Could not auto-restart. Restart n8n manually.';
            }
            if (data.output) txt += '\n\n' + data.output;
            out.textContent = txt;
        } else {
            out.style.display = 'block';
            out.style.color = data.mode === 'not-found' ? 'var(--warning)' : 'var(--danger)';
            out.textContent = '✗ ' + data.message;
        }
    } catch (e) {
        out.style.display = 'block';
        out.style.color = 'var(--danger)';
        out.textContent = '✗ Request failed: ' + e.message;
    }

    btn.innerHTML = '<i data-feather="zap" style="width:15px;height:15px;"></i> Update n8n';
    btn.disabled = false;
    feather.replace();
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/app.php';
?>
