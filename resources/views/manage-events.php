<?php
require_once __DIR__ . '/../../app/Auth.php';
Auth::check();

$db = new PDO('sqlite:' . __DIR__ . '/../../database/app.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get event counts by range
$ranges = [
    'past-5-years' => '-5 years',
    'past-2-years' => '-2 years',
    'past-1-year'  => '-1 year',
    'past-6-months' => '-6 months',
    'past-3-months' => '-3 months',
    'past-1-month'  => '-1 month',
    'past-week'     => '-7 days',
    'today'         => '0 days',
    'all-future'    => '+5 years'
];

$eventCounts = [];
$totalEvents = 0;

foreach ($ranges as $rangeKey => $modifier) {
    $now = new DateTime('now', new DateTimeZone('UTC'));
    if ($rangeKey === 'today') {
        $fromDate = clone $now;
        $fromDate->setTime(0, 0, 0);
        $toDate = clone $now;
        $toDate->setTime(23, 59, 59);
    } elseif ($rangeKey === 'all-future') {
        $fromDate = clone $now;
        $toDate = clone $now;
        $toDate->modify('+5 years');
    } else {
        $fromDate = clone $now;
        $fromDate->modify($modifier);
        $toDate = clone $now;
    }
    
    $stmt = $db->prepare("
        SELECT COUNT(*) as count FROM economic_events 
        WHERE event_date BETWEEN ? AND ?
    ");
    $stmt->execute([$fromDate->format('Y-m-d'), $toDate->format('Y-m-d')]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $eventCounts[$rangeKey] = $result['count'] ?? 0;
}

$stmt = $db->prepare("SELECT COUNT(*) as count FROM economic_events");
$stmt->execute();
$totalResult = $stmt->fetch(PDO::FETCH_ASSOC);
$totalEvents = $totalResult['count'] ?? 0;

$stmt = $db->prepare("SELECT value FROM settings WHERE key = 'events_last_updated'");
$stmt->execute();
$lastUpdatedRow = $stmt->fetch(PDO::FETCH_ASSOC);
$lastUpdated = $lastUpdatedRow['value'] ?? null;

// Load api_key + base_url for the HTTP cron trigger example
$settingsStmt = $db->query("SELECT key, value FROM settings WHERE key IN ('api_key','app_base_url')");
$appSettings = [];
foreach ($settingsStmt->fetchAll(PDO::FETCH_ASSOC) as $sr) { $appSettings[$sr['key']] = $sr['value']; }
$cronApiKey  = $appSettings['api_key'] ?? 'YOUR_API_KEY';
$cronBaseUrl = rtrim($appSettings['app_base_url'] ?? '', '/');

$title = 'Manage Economic Events';
$page = 'manage-events';
ob_start();
?>

<style>
.section-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 14px;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.025em;
}

.stat-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.stat-card:hover {
    transform: translateY(-4px);
    border-color: var(--input-border);
}

.divider {
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--border), transparent);
    margin: 3rem 0;
}

.api-code {
    font-family: 'Fira Code', 'Consolas', monospace;
    font-size: 0.8125rem;
    line-height: 1.6;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border-radius: 9999px;
    border: 1px solid var(--input-border);
    background-color: var(--input-bg);
    color: var(--text-primary);
    font-size: 1rem;
    transition: all 0.2s;
}

.form-input:hover {
    border-color: var(--accent);
}

.form-input:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.btn-sync {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.875rem 1.75rem;
    border-radius: 9999px;
    border: none;
    background: linear-gradient(135deg, #4f46e5, #6366f1);
    color: white;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(79, 70, 229, 0.2);
}

.btn-sync:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(79, 70, 229, 0.3);
}

.btn-sync:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-group {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.status-message {
    margin-top: 1.5rem;
    padding: 1.25rem;
    border-radius: 1rem;
    display: none;
    animation: slideIn 0.3s ease;
}

.status-message.success {
    background-color: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.3);
    color: var(--success);
}

.status-message.error {
    background-color: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #ef4444;
}

.status-message.loading {
    background-color: rgba(79, 70, 229, 0.1);
    border: 1px solid rgba(79, 70, 229, 0.3);
    color: var(--accent);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.loader {
    display: inline-block;
    width: 18px;
    height: 18px;
    border: 3px solid rgba(79, 70, 229, 0.3);
    border-top-color: var(--accent);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.info-banner {
    padding: 1.25rem;
    border-radius: 1rem;
    border: 1px solid var(--border);
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 2rem;
}

.info-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.highlight-box {
    border-left: 3px solid var(--accent);
    padding-left: 1rem;
}

.stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stats-text {
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin-top: 0.5rem;
}
</style>

<div class="p-8 max-w-[1600px] mx-auto">
    <!-- Hero Header -->
    <div class="mb-10">
        <div class="flex items-center justify-between mb-4">
            <div class="flex-1">
                <h1 class="text-4xl font-bold mb-3 tracking-tight" style="color: var(--text-primary);">
                    Manage Economic Events
                    <span class="section-badge ml-3" style="background-color: var(--accent); color: white;">Sync</span>
                </h1>
                <p class="text-lg" style="color: var(--text-secondary);">Fetch and synchronize economic calendar events from TradingView to your database</p>
            </div>
        </div>
    </div>

    <!-- Info Banner -->
    <div class="info-banner" style="background-color: rgba(79, 70, 229, 0.05);">
        <div class="info-icon" style="background-color: rgba(79, 70, 229, 0.15);">
            <i data-feather="info" style="width: 18px; height: 18px; color: var(--accent);"></i>
        </div>
        <div class="flex-1">
            <h3 class="font-semibold" style="color: var(--text-primary);">Database Status</h3>
            <p class="text-sm mt-1" style="color: var(--text-secondary);">
                You have <strong style="color: var(--text-primary);"><?php echo $totalEvents; ?> events</strong> currently stored in the database. 
                Use this tool to sync events for specific time ranges.
            </p>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stat-grid">
        <div class="stat-card p-4 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-2xl font-bold" style="color: var(--accent);"><?php echo $eventCounts['past-week']; ?></p>
                    <p class="text-xs uppercase tracking-wider" style="color: var(--text-secondary);">Past Week</p>
                </div>
                <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: rgba(79, 70, 229, 0.1);">
                    <i data-feather="calendar" style="width: 20px; height: 20px; color: var(--accent);"></i>
                </div>
            </div>
        </div>

        <div class="stat-card p-4 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-2xl font-bold" style="color: var(--success);"><?php echo $eventCounts['past-1-month']; ?></p>
                    <p class="text-xs uppercase tracking-wider" style="color: var(--text-secondary);">Past Month</p>
                </div>
                <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: rgba(16, 185, 129, 0.1);">
                    <i data-feather="trending-up" style="width: 20px; height: 20px; color: var(--success);"></i>
                </div>
            </div>
        </div>

        <div class="stat-card p-4 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-2xl font-bold" style="color: #f59e0b;"><?php echo $eventCounts['past-1-year']; ?></p>
                    <p class="text-xs uppercase tracking-wider" style="color: var(--text-secondary);">Past Year</p>
                </div>
                <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: rgba(245, 158, 11, 0.1);">
                    <i data-feather="bar-chart-2" style="width: 20px; height: 20px; color: #f59e0b;"></i>
                </div>
            </div>
        </div>

        <div class="stat-card p-4 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-2xl font-bold" style="color: #8b5cf6;"><?php echo $totalEvents; ?></p>
                    <p class="text-xs uppercase tracking-wider" style="color: var(--text-secondary);">Total Events</p>
                </div>
                <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: rgba(139, 92, 246, 0.1);">
                    <i data-feather="database" style="width: 20px; height: 20px; color: #8b5cf6;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Update Events Card -->
    <div class="p-8 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, #10b981, #059669);">
                <i data-feather="zap" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Update Events</h2>
                <p class="text-sm mt-1" style="color: var(--text-secondary);">Fetches all events from last known sync date through all future — the smart one-click refresh</p>
            </div>
        </div>

        <!-- Last known update info -->
        <div class="mb-6 p-4 rounded-xl" style="background-color: var(--input-bg); border: 1px solid var(--input-border);">
            <div class="flex items-center gap-2">
                <i data-feather="clock" style="width: 16px; height: 16px; color: var(--text-secondary);"></i>
                <span class="text-sm" style="color: var(--text-secondary);">Last known update:</span>
                <span class="text-sm font-semibold" style="color: var(--text-primary);" id="lastUpdatedDisplay">
                    <?php echo $lastUpdated ? htmlspecialchars($lastUpdated) . ' UTC' : '<em style="color:var(--text-secondary)">Never synced — will fetch last 7 days + all future</em>'; ?>
                </span>
            </div>
            <?php if ($lastUpdated): ?>
            <p class="text-xs mt-2" style="color: var(--text-secondary);">Will sync from this date forward, plus all upcoming events up to 5 years ahead.</p>
            <?php endif; ?>
        </div>

        <div class="btn-group">
            <button type="button" class="btn-sync" id="updateBtn" style="background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 4px 6px rgba(16,185,129,0.2);">
                <i data-feather="zap" style="width: 18px; height: 18px;"></i>
                <span>Update Events</span>
            </button>
        </div>

        <div id="updateStatus" class="status-message"></div>
    </div>

    <div class="divider"></div>

    <!-- Sync Form Card -->
    <div class="p-8 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, var(--accent), var(--success));">
                <i data-feather="refresh-cw" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Sync Events</h2>
                <p class="text-sm mt-1" style="color: var(--text-secondary);">Select a time range and fetch events from TradingView</p>
            </div>
        </div>

        <form id="syncForm">
            <!-- Time Range Selection -->
            <div class="form-group">
                <label class="text-sm font-semibold mb-2 block" style="color: var(--text-primary);">Time Range</label>
                <select id="range" name="range" class="form-input" required>
                    <option value="">-- Select Time Range --</option>
                    <optgroup label="Historical Data">
                        <option value="past-5-years">Past 5 Years (<?php echo $eventCounts['past-5-years']; ?> events)</option>
                        <option value="past-2-years">Past 2 Years (<?php echo $eventCounts['past-2-years']; ?> events)</option>
                        <option value="past-1-year">Past 1 Year (<?php echo $eventCounts['past-1-year']; ?> events)</option>
                        <option value="past-6-months">Past 6 Months (<?php echo $eventCounts['past-6-months']; ?> events)</option>
                        <option value="past-3-months">Past 3 Months (<?php echo $eventCounts['past-3-months']; ?> events)</option>
                        <option value="past-1-month">Past 1 Month (<?php echo $eventCounts['past-1-month']; ?> events)</option>
                        <option value="past-week">Past Week (<?php echo $eventCounts['past-week']; ?> events)</option>
                    </optgroup>
                    <optgroup label="Current & Future">
                        <option value="today">Today (<?php echo $eventCounts['today']; ?> events)</option>
                        <option value="all-future">All Future (<?php echo $eventCounts['all-future']; ?> events)</option>
                    </optgroup>
                </select>
                <p class="text-xs mt-2" style="color: var(--text-secondary);" id="rangeDesc"></p>
            </div>

            <!-- Currency Filter -->
            <div class="form-group">
                <label class="text-sm font-semibold mb-2 block" style="color: var(--text-primary);">Currency Filter (Optional)</label>
                <input type="text" id="currencies" name="currencies" class="form-input" placeholder="e.g., USD,EUR,GBP (leave empty for all)">
                <p class="text-xs mt-2" style="color: var(--text-secondary);">Supported: USD, EUR, GBP, JPY, AUD, NZD, CAD, CHF, CNY, MXN, INR, ZAR, KRW</p>
            </div>

            <!-- Submit Button -->
            <div class="btn-group">
                <button type="submit" class="btn-sync" id="syncBtn">
                    <i data-feather="cloud-download" style="width: 18px; height: 18px;"></i>
                    <span>Sync Events</span>
                </button>
            </div>
        </form>

        <!-- Status Message -->
        <div id="status" class="status-message"></div>
    </div>

    <div class="divider"></div>

    <!-- Cron Job Info -->
    <?php
        $cronScriptPath = realpath(__DIR__ . '/../../database/cron-sync-events.php');
        $isWindows      = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        // Normalise to forward-slashes for display; Windows Task Scheduler accepts both
        $cronScriptPathDisplay = str_replace('\\', '/', $cronScriptPath);
    ?>
    <div class="p-8 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
        <div class="flex items-center mb-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, #8b5cf6, #d946ef);">
                <i data-feather="clock" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <h3 class="text-xl font-bold" style="color: var(--text-primary);">Automatic Sync with Cron</h3>
        </div>
        <p class="mb-5" style="color: var(--text-secondary);">Set up a scheduled task to run every minute so it reacts quickly when economic events fire:</p>

        <!-- OS tabs -->
        <div class="flex gap-2 mb-4" id="cronTabs">
            <button onclick="showCronTab('linux')" id="tab-linux"
                class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-all"
                style="background: <?php echo $isWindows ? 'var(--input-bg)' : 'var(--accent)'; ?>; color: <?php echo $isWindows ? 'var(--text-secondary)' : 'white'; ?>; border: 1px solid var(--border);">
                <i data-feather="terminal" style="width:15px;height:15px;"></i> Linux / Ubuntu
            </button>
            <button onclick="showCronTab('windows')" id="tab-windows"
                class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-all"
                style="background: <?php echo $isWindows ? 'var(--accent)' : 'var(--input-bg)'; ?>; color: <?php echo $isWindows ? 'white' : 'var(--text-secondary)'; ?>; border: 1px solid var(--border);">
                <i data-feather="monitor" style="width:15px;height:15px;"></i> Windows
            </button>
        </div>

        <!-- Linux instructions -->
        <div id="cron-linux" <?php echo $isWindows ? 'style="display:none"' : ''; ?>>
            <p class="text-sm mb-4" style="color: var(--text-secondary);">
                Paste each command into your terminal — no GUI or editor needed. Works in headless Ubuntu containers.
            </p>

            <!-- Step 1 -->
            <div class="flex items-center gap-2 mb-1">
                <span class="w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0" style="background:var(--accent);color:white;">1</span>
                <p class="text-xs font-semibold" style="color: var(--text-primary);">Install &amp; enable the cron daemon (if not already running):</p>
            </div>
            <div class="p-3 rounded-xl mb-4 api-code flex items-center justify-between gap-4"
                 style="background-color: var(--input-bg); border: 1px solid var(--input-border); color: var(--accent); font-size:0.8rem;">
                <span id="cron-linux-install">apt-get install -y cron &amp;&amp; service cron start</span>
                <button onclick="copyText('cron-linux-install', this)" class="flex-shrink-0 px-3 py-1 rounded-lg text-xs font-semibold"
                    style="background:var(--accent);color:white;border:none;cursor:pointer;">Copy</button>
            </div>

            <!-- Step 2 -->
            <div class="flex items-center gap-2 mb-1">
                <span class="w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0" style="background:var(--accent);color:white;">2</span>
                <p class="text-xs font-semibold" style="color: var(--text-primary);">Register the job — this single command adds it without opening any editor:</p>
            </div>
            <div class="p-3 rounded-xl mb-2 api-code flex items-center justify-between gap-4"
                 style="background-color: var(--input-bg); border: 1px solid var(--input-border); color: var(--accent); font-size:0.8rem;">
                <span id="cron-linux-cmd">(crontab -l 2>/dev/null; echo "* * * * * php <?php echo htmlspecialchars($cronScriptPathDisplay); ?>") | crontab -</span>
                <button onclick="copyText('cron-linux-cmd', this)" class="flex-shrink-0 px-3 py-1 rounded-lg text-xs font-semibold"
                    style="background:var(--accent);color:white;border:none;cursor:pointer;">Copy</button>
            </div>
            <p class="text-xs mb-4" style="color: var(--text-secondary);">
                This preserves any existing crontab entries and appends the new line. Run it once — re-running it is safe (it won't duplicate the entry if you use the verify step below).
            </p>

            <!-- Step 3: verify -->
            <div class="flex items-center gap-2 mb-1">
                <span class="w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0" style="background:var(--accent);color:white;">3</span>
                <p class="text-xs font-semibold" style="color: var(--text-primary);">Verify the job is registered:</p>
            </div>
            <div class="p-3 rounded-xl mb-4 api-code flex items-center justify-between gap-4"
                 style="background-color: var(--input-bg); border: 1px solid var(--input-border); color: var(--accent); font-size:0.8rem;">
                <span id="cron-linux-verify">crontab -l</span>
                <button onclick="copyText('cron-linux-verify', this)" class="flex-shrink-0 px-3 py-1 rounded-lg text-xs font-semibold"
                    style="background:var(--accent);color:white;border:none;cursor:pointer;">Copy</button>
            </div>

            <!-- Container reboot note -->
            <div class="p-3 rounded-xl" style="background-color: rgba(99,102,241,0.08); border: 1px solid rgba(99,102,241,0.2);">
                <p class="text-xs font-semibold mb-1" style="color: var(--text-primary);">
                    <i data-feather="info" style="width:13px;height:13px;display:inline;vertical-align:middle;margin-right:4px;"></i>
                    Headless container restart note
                </p>
                <p class="text-xs" style="color: var(--text-secondary);">
                    Docker / LXC containers don't run <code style="color:var(--accent)">systemd</code> by default, so the cron daemon may not auto-start after a container restart. Add <code style="color:var(--accent)">service cron start</code> to your container's entrypoint script (e.g. <code style="color:var(--accent)">docker-entrypoint.sh</code> or <code style="color:var(--accent)">CMD</code> in your Dockerfile) to ensure it starts every time the container comes up:
                </p>
                <div class="p-2 rounded-lg mt-2 api-code flex items-center justify-between gap-4"
                     style="background-color: var(--input-bg); border: 1px solid var(--input-border); color: var(--accent); font-size:0.75rem;">
                    <span id="cron-linux-entrypoint">service cron start &amp;&amp; exec "$@"</span>
                    <button onclick="copyText('cron-linux-entrypoint', this)" class="flex-shrink-0 px-3 py-1 rounded-lg text-xs font-semibold"
                        style="background:var(--accent);color:white;border:none;cursor:pointer;">Copy</button>
                </div>
            </div>
        </div>

        <!-- Windows instructions -->
        <div id="cron-windows" <?php echo $isWindows ? '' : 'style="display:none"'; ?>>
            <p class="text-sm mb-3" style="color: var(--text-secondary);">
                Run this once in PowerShell <strong style="color:var(--text-primary);">as Administrator</strong>. The task is registered with Windows Task Scheduler, set to start at system boot and repeat every minute — it will survive restarts automatically.
            </p>

            <?php
                $phpExePath  = PHP_BINARY; // actual php.exe path on this machine
                $scriptWin   = str_replace('/', '\\', $cronScriptPath);
            ?>
            <p class="text-xs font-semibold mb-1" style="color: var(--text-primary);">PowerShell (run as Administrator):</p>
            <div class="p-3 rounded-xl mb-3 api-code flex items-start justify-between gap-4"
                 style="background-color: var(--input-bg); border: 1px solid var(--input-border); color: var(--accent); white-space: pre-wrap; word-break: break-all;">
                <span id="cron-win-cmd">$phpExe  = "<?php echo htmlspecialchars($phpExePath); ?>"
$script  = "<?php echo htmlspecialchars($scriptWin); ?>"
$action  = New-ScheduledTaskAction -Execute $phpExe -Argument $script
$atBoot  = New-ScheduledTaskTrigger -AtStartup
$repeat  = (New-TimeSpan -Minutes 1)
$atBoot.RepetitionInterval = $repeat
$atBoot.RepetitionDuration = [System.TimeSpan]::MaxValue
$settings = New-ScheduledTaskSettingsSet -ExecutionTimeLimit 0 -RestartOnIdle $false
Register-ScheduledTask -TaskName "ArrissaEventsCron" -Action $action -Trigger $atBoot -Settings $settings -RunLevel Highest -Force</span>
                <button onclick="copyText('cron-win-cmd', this)" class="flex-shrink-0 px-3 py-1 rounded-lg text-xs font-semibold"
                    style="background:var(--accent);color:white;border:none;cursor:pointer;height:fit-content;">Copy</button>
            </div>
            <p class="text-xs mb-2" style="color: var(--text-secondary);">
                The task uses <code style="color:var(--accent)">-AtStartup</code> as the base trigger with a 1-minute repetition interval and <code style="color:var(--accent)">MaxValue</code> duration — it will start automatically after every reboot and keep running every minute indefinitely.
            </p>
            <p class="text-xs" style="color: var(--text-secondary);">
                To verify: open <strong style="color:var(--text-primary);">Task Scheduler</strong> and look for <code style="color:var(--accent)">ArrissaEventsCron</code>. To remove it later: <code style="color:var(--accent)">Unregister-ScheduledTask -TaskName "ArrissaEventsCron" -Confirm:$false</code>
            </p>
        </div>

        <div class="highlight-box mt-5">
            <p class="text-sm" style="color: var(--text-secondary);">
                <strong style="color: var(--text-primary);">Smart trigger — only syncs when needed:</strong>
            </p>
            <ul class="text-sm mt-2 space-y-2" style="color: var(--text-secondary);">
                <li class="flex items-start">
                    <i data-feather="check-circle" class="mr-2 mt-0.5 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success);"></i>
                    <span>Runs an update 3 minutes after any event fires (actual data is then available)</span>
                </li>
                <li class="flex items-start">
                    <i data-feather="check-circle" class="mr-2 mt-0.5 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success);"></i>
                    <span>Forces a refresh if the last sync was more than 12 hours ago</span>
                </li>
                <li class="flex items-start">
                    <i data-feather="check-circle" class="mr-2 mt-0.5 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success);"></i>
                    <span>Loads future events up to the next 6 months</span>
                </li>
                <li class="flex items-start">
                    <i data-feather="check-circle" class="mr-2 mt-0.5 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success);"></i>
                    <span>Auto-updates existing events with released actual data</span>
                </li>
            </ul>
        </div>

        <!-- HTTP API trigger (n8n / Make / Zapier / curl) -->
        <div class="mt-5 p-5 rounded-xl" style="background:rgba(99,102,241,.06);border:1px solid rgba(99,102,241,.2);">
            <p class="text-sm font-semibold mb-1 flex items-center gap-2" style="color:var(--text-primary);">
                <i data-feather="zap" style="width:15px;height:15px;color:#818cf8;"></i>
                Trigger via HTTP — n8n, Make, Zapier, curl, anything
            </p>
            <p class="text-xs mb-3" style="color:var(--text-secondary);">Instead of (or in addition to) a local cron job, you can call the sync endpoint from any HTTP scheduler. It uses the same smart timing logic — it skips if not needed, or add <code style="color:var(--accent)">&amp;force=true</code> to always run.</p>

            <!-- Smart trigger URL -->
            <p class="text-xs font-semibold mb-1" style="color:var(--text-primary);">Smart trigger (skips if nothing to do):</p>
            <div class="p-3 rounded-xl mb-3 api-code flex items-center justify-between gap-4"
                 style="background:var(--input-bg);border:1px solid var(--input-border);color:var(--accent);font-size:0.78rem;word-break:break-all;">
                <span id="cron-http-smart"><?php echo htmlspecialchars($cronBaseUrl . '/api/run-cron?api_key=' . $cronApiKey); ?></span>
                <button onclick="copyText('cron-http-smart', this)" class="flex-shrink-0 px-3 py-1 rounded-lg text-xs font-semibold"
                    style="background:var(--accent);color:white;border:none;cursor:pointer;">Copy</button>
            </div>

            <!-- Force trigger URL -->
            <p class="text-xs font-semibold mb-1" style="color:var(--text-primary);">Force trigger (always runs regardless of timing):</p>
            <div class="p-3 rounded-xl mb-3 api-code flex items-center justify-between gap-4"
                 style="background:var(--input-bg);border:1px solid var(--input-border);color:var(--accent);font-size:0.78rem;word-break:break-all;">
                <span id="cron-http-force"><?php echo htmlspecialchars($cronBaseUrl . '/api/run-cron?api_key=' . $cronApiKey . '&force=true'); ?></span>
                <button onclick="copyText('cron-http-force', this)" class="flex-shrink-0 px-3 py-1 rounded-lg text-xs font-semibold"
                    style="background:var(--accent);color:white;border:none;cursor:pointer;">Copy</button>
            </div>

            <!-- Response example -->
            <p class="text-xs font-semibold mb-1" style="color:var(--text-primary);">Example JSON response:</p>
            <div class="p-3 rounded-xl api-code"
                 style="background:var(--input-bg);border:1px solid var(--input-border);color:var(--accent);font-size:0.75rem;white-space:pre;overflow-x:auto;">{
  "status": "ran",
  "reason": "event(s) occurred ~4 min ago — actuals should be available",
  "saved": 3,
  "updated": 1,
  "sync_from": "2026-02-27 08:00:00 UTC",
  "sync_to": "2026-08-27 09:05:00 UTC",
  "duration_ms": 1240
}</div>
            <p class="text-xs mt-2" style="color:var(--text-secondary);">When nothing needs syncing: <code style="color:var(--accent)">{"status":"skipped","reason":"no update needed..."}</code></p>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Danger Zone: Truncate Database -->
    <div class="p-8 rounded-2xl" style="background-color: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.3);">
        <div class="flex items-center mb-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: rgba(239, 68, 68, 0.2);">
                <i data-feather="trash-2" style="width: 24px; height: 24px; color: #ef4444;"></i>
            </div>
            <h3 class="text-xl font-bold" style="color: #ef4444;">Danger Zone</h3>
        </div>
        
        <div class="mb-6 p-4 rounded-lg" style="background-color: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2);">
            <p class="text-sm" style="color: var(--text-secondary);">
                <strong style="color: #ef4444;">⚠️ Warning:</strong> This action will permanently delete ALL events from the database. This cannot be undone. Proceed with caution.
            </p>
        </div>

        <form id="truncateForm">
            <div class="form-group">
                <label class="text-sm font-semibold mb-2 block" style="color: var(--text-primary);">Type "delete" to confirm truncation:</label>
                <input type="text" id="truncateConfirm" name="truncateConfirm" class="form-input" placeholder="Type 'delete' to proceed" autocomplete="off">
                <p class="text-xs mt-2" style="color: var(--text-secondary);">This will remove <?php echo $totalEvents; ?> events from the database permanently.</p>
            </div>

            <div class="btn-group">
                <button type="submit" class="btn-sync" id="truncateBtn" style="background: linear-gradient(135deg, #ef4444, #dc2626); box-shadow: 0 4px 6px rgba(239, 68, 68, 0.2);" disabled>
                    <i data-feather="trash-2" style="width: 18px; height: 18px;"></i>
                    <span>Truncate All Events</span>
                </button>
            </div>
        </form>

        <div id="truncateStatus" class="status-message"></div>
    </div>
</div>

<script>
    feather.replace();

    // Cron tab switcher
    function showCronTab(os) {
        document.getElementById('cron-linux').style.display   = os === 'linux'   ? '' : 'none';
        document.getElementById('cron-windows').style.display = os === 'windows' ? '' : 'none';
        document.getElementById('tab-linux').style.background   = os === 'linux'   ? 'var(--accent)' : 'var(--input-bg)';
        document.getElementById('tab-windows').style.background = os === 'windows' ? 'var(--accent)' : 'var(--input-bg)';
        document.getElementById('tab-linux').style.color   = os === 'linux'   ? 'white' : 'var(--text-secondary)';
        document.getElementById('tab-windows').style.color = os === 'windows' ? 'white' : 'var(--text-secondary)';
    }

    // Copy-to-clipboard helper
    function copyText(elementId, btn) {
        const text = document.getElementById(elementId).innerText;
        navigator.clipboard.writeText(text).then(() => {
            const orig = btn.textContent;
            btn.textContent = '✓ Copied';
            setTimeout(() => { btn.textContent = orig; }, 2000);
        });
    }

    const form = document.getElementById('syncForm');
    const syncBtn = document.getElementById('syncBtn');
    const statusDiv = document.getElementById('status');
    const rangeSelect = document.getElementById('range');
    const rangeDesc = document.getElementById('rangeDesc');

    const truncateForm = document.getElementById('truncateForm');
    const truncateBtn = document.getElementById('truncateBtn');
    const truncateConfirm = document.getElementById('truncateConfirm');
    const truncateStatus = document.getElementById('truncateStatus');

    const rangeDescriptions = {
        'past-5-years': 'Fetch and save economic events from the last 5 years',
        'past-2-years': 'Fetch and save economic events from the last 2 years',
        'past-1-year': 'Fetch and save economic events from the last 1 year',
        'past-6-months': 'Fetch and save economic events from the last 6 months',
        'past-3-months': 'Fetch and save economic events from the last 3 months',
        'past-1-month': 'Fetch and save economic events from the last 1 month',
        'past-week': 'Fetch and save economic events from the last 7 days',
        'today': 'Fetch and save economic events for today only',
        'all-future': 'Fetch and save all upcoming economic events'
    };

    rangeSelect.addEventListener('change', () => {
        rangeDesc.textContent = rangeDescriptions[rangeSelect.value] || '';
    });

    // Sync form submission
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const range = document.getElementById('range').value;
        const currencies = document.getElementById('currencies').value;

        if (!range) {
            showStatus('error', '<strong>Error:</strong> Please select a time range');
            return;
        }

        syncBtn.disabled = true;
        showStatus('loading', '<div class="loader"></div> <span>Syncing events from TradingView — auto-paginating until all events are retrieved, please wait...</span>');

        try {
            const params = new URLSearchParams();
            params.append('range', range);
            if (currencies) params.append('currencies', currencies);

            const response = await fetch(`/api/sync-events?${params}`, {
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            });

            const data = await response.json();

            if (data.success) {
                let message = `<strong>✓ Events Synced Successfully!</strong><br><br>`;
                message += `<strong>📅 Date Range:</strong> ${data.from} → ${data.to}<br>`;
                message += `<strong>� API Requests Made:</strong> ${data.pages}<br>`;
                message += `<strong>📊 Total Fetched:</strong> ${data.total_fetched}<br>`;
                message += `<strong>✨ New Saved:</strong> ${data.saved}<br>`;
                message += `<strong>🔄 Updated:</strong> ${data.updated}`;

                if (data.page_details && data.page_details.length > 0) {
                    message += `<br><br><details style="margin-top:0.5rem;"><summary style="cursor:pointer;font-weight:600;">📋 Per-request breakdown (${data.page_details.length} request${data.page_details.length !== 1 ? 's' : ''})</summary>`;
                    message += `<table style="width:100%;border-collapse:collapse;margin-top:0.5rem;font-size:0.8rem;">`;
                    message += `<tr style="opacity:0.7"><th style="text-align:left;padding:2px 6px">#</th><th style="text-align:left;padding:2px 6px">From</th><th style="padding:2px 6px">Fetched</th><th style="padding:2px 6px">Saved</th><th style="padding:2px 6px">Updated</th></tr>`;
                    data.page_details.forEach(p => {
                        const errMark = p.error ? ' ⚠️' : '';
                        message += `<tr><td style="padding:2px 6px">${p.page}${errMark}</td><td style="padding:2px 6px">${p.from}</td><td style="text-align:center;padding:2px 6px">${p.fetched}</td><td style="text-align:center;padding:2px 6px">${p.saved ?? '-'}</td><td style="text-align:center;padding:2px 6px">${p.updated ?? '-'}</td></tr>`;
                    });
                    message += `</table></details>`;
                }

                if (data.errors && data.errors.length > 0) {
                    message += '<br><strong>⚠️ Errors:</strong><ul style="margin-left:1rem;margin-top:0.5rem;">';
                    data.errors.forEach(err => {
                        message += `<li style="font-size:0.875rem;">${err}</li>`;
                    });
                    message += '</ul>';
                }

                showStatus('success', message);
                setTimeout(() => { location.reload(); }, 3000);
            } else {
                showStatus('error', `<strong>Sync Failed:</strong> ${data.error || 'Unknown error'}`);
            }
        } catch (err) {
            showStatus('error', `<strong>Error:</strong> ${err.message}`);
        } finally {
            syncBtn.disabled = false;
        }
    });

    // Update Events button
    const updateBtn = document.getElementById('updateBtn');
    const updateStatus = document.getElementById('updateStatus');

    function showUpdateStatus(type, message) {
        updateStatus.className = `status-message ${type}`;
        updateStatus.innerHTML = message;
    }

    updateBtn.addEventListener('click', async () => {
        updateBtn.disabled = true;
        showUpdateStatus('loading', '<div class="loader"></div> <span>Updating events from last known sync date through all future — please wait...</span>');

        try {
            const response = await fetch('/api/update-events', {
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            });

            const data = await response.json();

            if (data.success) {
                let message = `<strong>✓ Events Updated Successfully!</strong><br><br>`;
                message += `<strong>📅 Synced From:</strong> ${data.synced_from}<br>`;
                message += `<strong>📅 Synced To:</strong> ${data.synced_to}<br>`;
                message += `<strong>📡 API Requests Made:</strong> ${data.pages}<br>`;
                message += `<strong>📊 Total Fetched:</strong> ${data.total_fetched}<br>`;
                message += `<strong>✨ New Saved:</strong> ${data.saved}<br>`;
                message += `<strong>🔄 Updated:</strong> ${data.updated}`;

                if (data.last_updated_at) {
                    message += `<br><strong>🕒 Last Updated At:</strong> ${data.last_updated_at} UTC`;
                }

                if (data.page_details && data.page_details.length > 0) {
                    message += `<br><br><details style="margin-top:0.5rem;"><summary style="cursor:pointer;font-weight:600;">📋 Per-request breakdown (${data.page_details.length} request${data.page_details.length !== 1 ? 's' : ''})</summary>`;
                    message += `<table style="width:100%;border-collapse:collapse;margin-top:0.5rem;font-size:0.8rem;">`;
                    message += `<tr style="opacity:0.7"><th style="text-align:left;padding:2px 6px">#</th><th style="text-align:left;padding:2px 6px">From</th><th style="padding:2px 6px">Fetched</th><th style="padding:2px 6px">Saved</th><th style="padding:2px 6px">Updated</th></tr>`;
                    data.page_details.forEach(p => {
                        const errMark = p.error ? ' ⚠️' : '';
                        message += `<tr><td style="padding:2px 6px">${p.page}${errMark}</td><td style="padding:2px 6px">${p.from}</td><td style="text-align:center;padding:2px 6px">${p.fetched}</td><td style="text-align:center;padding:2px 6px">${p.saved ?? '-'}</td><td style="text-align:center;padding:2px 6px">${p.updated ?? '-'}</td></tr>`;
                    });
                    message += `</table></details>`;
                }

                if (data.errors && data.errors.length > 0) {
                    message += '<br><strong>⚠️ Errors:</strong><ul style="margin-left:1rem;margin-top:0.5rem;">';
                    data.errors.forEach(err => {
                        message += `<li style="font-size:0.875rem;">${err}</li>`;
                    });
                    message += '</ul>';
                }

                // Refresh the last-updated display inline
                if (data.last_updated_at) {
                    const el = document.getElementById('lastUpdatedDisplay');
                    if (el) el.innerHTML = `${data.last_updated_at} UTC`;
                }

                showUpdateStatus('success', message);
                setTimeout(() => { location.reload(); }, 3000);
            } else {
                showUpdateStatus('error', `<strong>Update Failed:</strong> ${data.error || 'Unknown error'}`);
            }
        } catch (err) {
            showUpdateStatus('error', `<strong>Error:</strong> ${err.message}`);
        } finally {
            updateBtn.disabled = false;
        }
    });

    // Truncate confirmation - enable button only when "delete" is typed
    truncateConfirm.addEventListener('input', () => {
        truncateBtn.disabled = truncateConfirm.value !== 'delete';
    });

    // Truncate form submission
    truncateForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (truncateConfirm.value !== 'delete') {
            showTruncateStatus('error', '<strong>Error:</strong> Please type "delete" to confirm');
            return;
        }

        truncateBtn.disabled = true;
        showTruncateStatus('loading', '<div class="loader"></div> <span>Truncating events database...</span>');

        try {
            const response = await fetch('/api/truncate-events', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' }
            });

            const data = await response.json();

            if (data.success) {
                let message = `<strong>✓ Database Truncated Successfully!</strong><br><br>`;
                message += `<strong>🗑️ Deleted Events:</strong> ${data.deleted}<br>`;
                message += `<strong>📊 Remaining Events:</strong> ${data.remaining}`;

                showTruncateStatus('success', message);
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                showTruncateStatus('error', `<strong>Truncate Failed:</strong> ${data.error || 'Unknown error'}`);
            }
        } catch (err) {
            showTruncateStatus('error', `<strong>Error:</strong> ${err.message}`);
        } finally {
            truncateBtn.disabled = true;
            truncateConfirm.value = '';
        }
    });

    function showStatus(type, message) {
        statusDiv.className = `status-message ${type}`;
        statusDiv.innerHTML = message;
    }

    function showTruncateStatus(type, message) {
        truncateStatus.className = `status-message ${type}`;
        truncateStatus.innerHTML = message;
    }
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layouts/app.php';
?>
