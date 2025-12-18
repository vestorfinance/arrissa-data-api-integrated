<?php
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Database.php';

$db = Database::getInstance();

// Get base URL from database
$stmt = $db->query("SELECT value FROM settings WHERE key = 'app_base_url'");
$result = $stmt->fetch();
$baseUrl = $result ? $result['value'] : 'http://localhost:8000';

// Get API key from database
$stmt = $db->query("SELECT value FROM settings WHERE key = 'api_key'");
$result = $stmt->fetch();
$apiKey = $result ? $result['value'] : '';

// Fetch all unique consistent_event_ids with a representative event_name and affected currencies
$pdo = $db->getConnection();
$sql = "
    SELECT
      e.consistent_event_id,
      MIN(e.event_name) AS event_name,
      GROUP_CONCAT(DISTINCT e.currency) AS currencies
    FROM economic_events AS e
    WHERE e.consistent_event_id IS NOT NULL AND e.consistent_event_id != ''
    GROUP BY e.consistent_event_id
    ORDER BY event_name ASC
";
$stmt   = $pdo->query($sql);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

$title = 'Event ID Reference';
$page = 'event-id-reference';
ob_start();
?>

<style>
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
.copy-icon {
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    margin-left: 8px;
    transition: all 0.2s;
    opacity: 0.6;
}
.copy-icon:hover {
    opacity: 1;
    transform: scale(1.1);
}
.copy-icon.copied {
    color: var(--success);
}
.event-row {
    transition: background-color 0.2s;
}
.event-row:hover {
    background-color: var(--bg-secondary);
}
</style>

<div class="p-8 max-w-[1600px] mx-auto">
    <!-- Hero Header -->
    <div class="mb-10">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-4xl font-bold mb-3 tracking-tight" style="color: var(--text-primary);">
                    Event ID Reference
                </h1>
                <p class="text-lg" style="color: var(--text-secondary);">Complete lookup of all consistent event IDs and their affected currencies</p>
            </div>
        </div>
        
        <!-- Info Banner -->
        <div class="p-6 rounded-2xl" style="background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(16, 185, 129, 0.1) 100%); border: 1px solid var(--border);">
            <div class="flex items-start">
                <div class="flex-shrink-0 mr-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background-color: var(--accent);">
                        <i data-feather="info" style="width: 24px; height: 24px; color: white;"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">Using Event IDs</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm" style="color: var(--text-secondary);">
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--accent);"></i>
                            <span>Use <strong style="color: var(--text-primary);">consistent_event_id</strong> to filter and group related events</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--accent);"></i>
                            <span>Pass multiple IDs separated by commas for combined queries</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--accent);"></i>
                            <span>Click any ID to copy it to your clipboard instantly</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--accent);"></i>
                            <span>Use the search box to quickly find specific events</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Actions -->
    <div class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="relative">
                <input 
                    type="text" 
                    id="searchInput"
                    placeholder="Search events or IDs..." 
                    class="w-full rounded-full pl-12 pr-5 py-4 text-base focus:outline-none"
                    style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);"
                >
                <i data-feather="search" class="absolute left-4 top-4" style="width: 20px; height: 20px; color: var(--text-secondary);"></i>
            </div>
            <div class="flex items-center gap-4">
                <button 
                    id="copyAllBtn"
                    class="inline-flex items-center text-sm font-medium px-6 py-3 rounded-full transition-all"
                    style="background-color: var(--accent); color: white;"
                    onmouseover="this.style.backgroundColor='var(--accent-hover)';"
                    onmouseout="this.style.backgroundColor='var(--accent)';"
                >
                    <i data-feather="copy" class="mr-2" style="width: 16px; height: 16px;"></i>
                    Copy All Visible IDs
                </button>
                <span id="resultCount" class="text-sm" style="color: var(--text-secondary);">
                    <?php echo count($events); ?> events
                </span>
            </div>
        </div>
    </div>

    <!-- Events Table -->
    <div class="rounded-2xl overflow-hidden" style="background-color: var(--card-bg); border: 1px solid var(--border);">
        <div class="overflow-x-auto">
            <table class="w-full text-sm" id="eventsTable">
                <thead>
                    <tr style="background-color: var(--bg-secondary); border-bottom: 2px solid var(--border);">
                        <th class="text-left py-4 px-6 font-semibold" style="color: var(--text-primary);">Event Name</th>
                        <th class="text-left py-4 px-6 font-semibold" style="color: var(--text-primary);">Consistent Event ID</th>
                        <th class="text-left py-4 px-6 font-semibold" style="color: var(--text-primary);">Currencies</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($events)): ?>
                    <tr>
                        <td colspan="3" class="py-8 px-6 text-center" style="color: var(--text-secondary);">
                            <i data-feather="inbox" class="mx-auto mb-3" style="width: 48px; height: 48px;"></i>
                            <p>No events found in the database.</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($events as $event): ?>
                        <tr class="event-row" style="border-bottom: 1px solid var(--border);" data-search="<?php echo htmlspecialchars(strtolower($event['event_name'] . ' ' . $event['consistent_event_id'] . ' ' . $event['currencies'])); ?>">
                            <td class="py-4 px-6" style="color: var(--text-primary);">
                                <?php echo htmlspecialchars($event['event_name']); ?>
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex items-center">
                                    <code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--accent); color: white;">
                                        <?php echo htmlspecialchars($event['consistent_event_id']); ?>
                                    </code>
                                    <i data-feather="copy" 
                                       class="copy-icon ml-2" 
                                       data-id="<?php echo htmlspecialchars($event['consistent_event_id']); ?>"
                                       title="Copy ID"
                                       style="width: 16px; height: 16px; color: var(--text-secondary);">
                                    </i>
                                </div>
                            </td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">
                                <?php 
                                $currencies = explode(',', $event['currencies']);
                                foreach ($currencies as $currency): 
                                    $currency = trim($currency);
                                    if ($currency):
                                ?>
                                    <span class="inline-block px-2 py-1 rounded-full text-xs mr-1 mb-1" style="background-color: var(--input-bg); color: var(--text-primary);">
                                        <?php echo htmlspecialchars($currency); ?>
                                    </span>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Usage Example -->
    <div class="mb-10">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, var(--accent), var(--success));">
                <i data-feather="code" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Usage Example</h2>
                <p class="text-sm" style="color: var(--text-secondary);">How to use event IDs in API requests</p>
            </div>
        </div>

        <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <p class="text-sm mb-4 font-medium" style="color: var(--text-primary);">Query Multiple Events:</p>
            <div class="p-5 rounded-xl api-code text-xs overflow-x-auto" style="background-color: var(--bg-primary); color: var(--text-primary); border: 1px solid var(--input-border);">
                <pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word;"><?php echo htmlspecialchars($baseUrl); ?>/news-api-v1/news-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&event_id=EVENT_ID_1,EVENT_ID_2&period=last-6-months</pre>
            </div>
            <p class="text-xs mt-3" style="color: var(--text-secondary);">
                Replace EVENT_ID_1,EVENT_ID_2 with actual IDs from the table above. Click any ID to copy it.
            </p>
        </div>
    </div>
</div>

<script>
    // Initialize Feather icons
    feather.replace();
    
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const eventRows = document.querySelectorAll('.event-row');
    const resultCount = document.getElementById('resultCount');
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        let visibleCount = 0;
        
        eventRows.forEach(row => {
            const searchData = row.getAttribute('data-search');
            if (searchData.includes(searchTerm)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        resultCount.textContent = visibleCount + ' event' + (visibleCount !== 1 ? 's' : '');
    });
    
    // Copy individual ID
    document.querySelectorAll('.copy-icon').forEach(icon => {
        icon.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            navigator.clipboard.writeText(id).then(() => {
                this.classList.add('copied');
                setTimeout(() => this.classList.remove('copied'), 500);
            });
        });
    });
    
    // Copy all visible IDs
    document.getElementById('copyAllBtn').addEventListener('click', function() {
        const visibleRows = Array.from(eventRows).filter(row => row.style.display !== 'none');
        const ids = visibleRows.map(row => row.querySelector('.copy-icon').getAttribute('data-id'));
        
        if (ids.length > 0) {
            const allIds = ids.join(',');
            navigator.clipboard.writeText(allIds).then(() => {
                const originalText = this.innerHTML;
                this.innerHTML = '<i data-feather="check" style="width: 16px; height: 16px;" class="mr-2"></i>Copied!';
                feather.replace();
                setTimeout(() => {
                    this.innerHTML = originalText;
                    feather.replace();
                }, 1000);
            });
        }
    });
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layouts/app.php';
?>
