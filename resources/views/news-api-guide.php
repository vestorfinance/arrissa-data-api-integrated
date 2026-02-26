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

$title = 'News API Guide';
$page = 'news-api';
ob_start();
?>

<style>
.example-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.example-card:hover {
    transform: translateY(-4px);
}
.section-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 14px;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.025em;
}
.gradient-bg {
    background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(16, 185, 129, 0.1) 100%);
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
.highlight-box {
    border-left: 3px solid var(--accent);
    padding-left: 1rem;
}
</style>

<div class="p-8 max-w-[1600px] mx-auto">
    <!-- Hero Header -->
    <div class="mb-10">
        <div class="flex items-center justify-between mb-4">
            <div class="flex-1">
                <h1 class="text-4xl font-bold mb-3 tracking-tight" style="color: var(--text-primary);">
                    Economic News API
                    <span class="section-badge ml-3" style="background-color: var(--success); color: var(--bg-primary);">v1.0</span>
                </h1>
                <p class="text-lg" style="color: var(--text-secondary);">Comprehensive access to economic event data with advanced filtering and period-based queries</p>
            </div>
            <div>
                <a href="/run-events-scrapper" class="inline-flex items-center px-6 py-3 rounded-full font-semibold transition-all" style="background: linear-gradient(135deg, #4f46e5, #6366f1); color: white; text-decoration: none; box-shadow: 0 4px 6px rgba(79, 70, 229, 0.2);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 16px rgba(79, 70, 229, 0.3)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px rgba(79, 70, 229, 0.2)';">
                    <i data-feather="refresh-cw" style="width: 18px; height: 18px; margin-right: 8px;"></i>
                    Run Events Scrapper
                </a>
            </div>
        </div>
        
        <!-- What's New Banner -->
        <div class="p-6 rounded-2xl gradient-bg" style="border: 1px solid var(--border);">
            <div class="flex items-start">
                <div class="flex-shrink-0 mr-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background-color: var(--success);">
                        <i data-feather="zap" style="width: 24px; height: 24px; color: white;"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">Key Features</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm" style="color: var(--text-secondary);">
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span><strong style="color: var(--text-primary);">Flexible Periods:</strong> Custom and predefined time ranges</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span><strong style="color: var(--text-primary);">Currency Filtering:</strong> Include or exclude specific currencies</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span><strong style="color: var(--text-primary);">Event Grouping:</strong> Filter by consistent event IDs</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span><strong style="color: var(--text-primary);">Future Events:</strong> Automatic TBD handling for scheduled events</span>
                        </div>
                        <div class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span><strong style="color: var(--text-primary);">Timezone Conversion:</strong> Convert UTC data to any timezone</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Reference Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10">
        <!-- API Endpoint Card -->
        <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="flex items-center mb-4">
                <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3" style="background-color: var(--accent); opacity: 0.2;">
                    <i data-feather="link" style="width: 20px; height: 20px; color: var(--accent);"></i>
                </div>
                <h3 class="text-lg font-semibold" style="color: var(--text-primary);">API Endpoint</h3>
            </div>
            <p class="text-xs uppercase tracking-wider mb-2" style="color: var(--text-secondary);">Base URL</p>
            <div class="p-4 rounded-xl api-code break-all" style="background-color: var(--input-bg); color: var(--accent); border: 1px solid var(--input-border);">
                <?php echo htmlspecialchars($baseUrl); ?>/news-api-v1/news-api.php
            </div>
        </div>

        <!-- API Key Card -->
        <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="flex items-center mb-4">
                <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3" style="background-color: var(--success); opacity: 0.2;">
                    <i data-feather="key" style="width: 20px; height: 20px; color: var(--success);"></i>
                </div>
                <h3 class="text-lg font-semibold" style="color: var(--text-primary);">Your API Key</h3>
            </div>
            <p class="text-xs uppercase tracking-wider mb-2" style="color: var(--text-secondary);">Current Key</p>
            <div class="p-4 rounded-xl api-code break-all" style="background-color: var(--input-bg); color: var(--success); border: 1px solid var(--input-border);">
                <?php echo htmlspecialchars($apiKey); ?>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Authentication Section -->
    <div class="mb-10">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, var(--accent), var(--success));">
                <i data-feather="lock" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Authentication</h2>
                <p class="text-sm" style="color: var(--text-secondary);">All requests require an API key parameter</p>
            </div>
        </div>
        
        <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <p class="text-sm mb-4 font-medium" style="color: var(--text-primary);">Example Request:</p>
            <div class="p-5 rounded-xl api-code text-xs overflow-x-auto" style="background-color: var(--bg-primary); color: var(--text-primary); border: 1px solid var(--input-border);">
                <pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word;"><?php echo htmlspecialchars($baseUrl); ?>/news-api-v1/news-api.php?api_key=<?php echo htmlspecialchars($apiKey); ?>&currency=USD&period=today</pre>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Core Parameters Section -->
    <div class="mb-10">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, var(--accent), var(--success));">
                <i data-feather="sliders" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Core Parameters</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Essential parameters for API requests</p>
            </div>
        </div>

        <div class="rounded-2xl overflow-hidden" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr style="background-color: var(--bg-secondary); border-bottom: 2px solid var(--border);">
                            <th class="text-left py-4 px-6 font-semibold" style="color: var(--text-primary);">Parameter</th>
                            <th class="text-left py-4 px-6 font-semibold" style="color: var(--text-primary);">Type</th>
                            <th class="text-left py-4 px-6 font-semibold" style="color: var(--text-primary);">Required</th>
                            <th class="text-left py-4 px-6 font-semibold" style="color: var(--text-primary);">Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--accent); color: white;">api_key</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">string</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--success); color: white;">Always</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Your unique API key for authentication and quota tracking</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">start_date</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">YYYY-MM-DD</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--warning); color: white;">Conditional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Start date for filtering events (required if no period)</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">start_time</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">HH:MM:SS</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Start time for filtering events (UTC, defaults to 00:00:00)</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">end_date</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">YYYY-MM-DD</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--warning); color: white;">Conditional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">End date for filtering events (required if no period)</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">end_time</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">HH:MM:SS</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">End time for filtering events (UTC, defaults to 23:59:59)</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">period</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">string</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--warning); color: white;">Conditional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Predefined time range (required if no date/time params)</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">currency</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">string</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Filter events by specific currency (e.g., USD, EUR)</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">currency_exclude</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">string</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Exclude currencies (comma-separated, e.g., USD,GBP)</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">event_id</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">string</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Filter by event IDs (comma-separated for grouped results)</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">display</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">string</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Set to "min" for minimal data fields</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">future_limit</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">string</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Limit future events scope. Named: <code>today</code>, <code>tomorrow</code>, <code>next-2-days</code>, <code>this-week</code>, <code>next-week</code>, <code>next-2-weeks</code>, <code>next-month</code>. Dynamic: <code>next-7-days</code>, <code>next-3-weeks</code>, <code>next-2-months</code>, <code>next-6-hours</code></td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">spit_out</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">string</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Set to "all" to include all events (past & future)</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">pretend_now_date</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">YYYY-MM-DD</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Override "now" date for relative period calculations</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">pretend_now_time</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">HH:MM</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Override "now" time for relative period calculations</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">time_zone</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">string</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Convert event dates/times from UTC to specified timezone (e.g., NY, LA, LON, TYO, SYD, or full names like America/New_York)</td>
                        </tr>
                        <tr>
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">must_have</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">string</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Return only events with specified non-null values (comma-separated: forecast_value, actual_value, previous_value)</td>
                        </tr>
                        <tr>
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">avoid_duplicates</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">string</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Set to "true" to remove duplicate events with the same consistent_event_id</td>
                        </tr>
                        <tr>
                            <td class="py-4 px-6"><code class="px-3 py-1.5 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-primary);">ignore_weekends</code></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">string</td>
                            <td class="py-4 px-6"><span class="px-3 py-1 rounded-full text-xs font-semibold" style="background-color: var(--input-bg); color: var(--text-secondary);">Optional</span></td>
                            <td class="py-4 px-6" style="color: var(--text-secondary);">Set to "true" to skip weekends for trading days. If today is Friday, "tomorrow" becomes Monday; if today is Monday, "yesterday" becomes Friday</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Period Values Section -->
    <div class="mb-10">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, var(--success), var(--accent));">
                <i data-feather="calendar" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Supported Periods</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Flexible time range options for data retrieval</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-base font-semibold mb-4 flex items-center" style="color: var(--text-primary);">
                    <i data-feather="clock" class="mr-2" style="width: 18px; height: 18px; color: var(--accent);"></i>
                    Fixed Periods
                </h3>
                <div class="space-y-2 text-sm" style="color: var(--text-secondary);">
                    <div class="flex items-center p-3 rounded-xl" style="background-color: var(--input-bg);">
                        <code class="text-sm font-semibold" style="color: var(--accent);">today</code>
                        <span class="ml-auto text-xs">Current day from 00:00 to now</span>
                    </div>
                    <div class="flex items-center p-3 rounded-xl" style="background-color: var(--input-bg);">
                        <code class="text-sm font-semibold" style="color: var(--accent);">yesterday</code>
                        <span class="ml-auto text-xs">Previous day (full 24h)</span>
                    </div>
                    <div class="flex items-center p-3 rounded-xl" style="background-color: var(--input-bg);">
                        <code class="text-sm font-semibold" style="color: var(--accent);">this-week</code>
                        <span class="ml-auto text-xs">Sunday to now</span>
                    </div>
                    <div class="flex items-center p-3 rounded-xl" style="background-color: var(--input-bg);">
                        <code class="text-sm font-semibold" style="color: var(--accent);">last-week</code>
                        <span class="ml-auto text-xs">Previous full week</span>
                    </div>
                    <div class="flex items-center p-3 rounded-xl" style="background-color: var(--input-bg);">
                        <code class="text-sm font-semibold" style="color: var(--accent);">this-month</code>
                        <span class="ml-auto text-xs">1st of month to now</span>
                    </div>
                    <div class="flex items-center p-3 rounded-xl" style="background-color: var(--input-bg);">
                        <code class="text-sm font-semibold" style="color: var(--accent);">last-month</code>
                        <span class="ml-auto text-xs">Previous full month</span>
                    </div>
                    <div class="flex items-center p-3 rounded-xl" style="background-color: var(--input-bg);">
                        <code class="text-sm font-semibold" style="color: var(--accent);">this-year</code>
                        <span class="ml-auto text-xs">Jan 1st to now</span>
                    </div>
                    <div class="flex items-center p-3 rounded-xl" style="background-color: var(--input-bg);">
                        <code class="text-sm font-semibold" style="color: var(--accent);">future</code>
                        <span class="ml-auto text-xs">All future events</span>
                    </div>
                </div>
            </div>

            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-base font-semibold mb-4 flex items-center" style="color: var(--text-primary);">
                    <i data-feather="trending-up" class="mr-2" style="width: 18px; height: 18px; color: var(--success);"></i>
                    Rolling Periods
                </h3>
                <div class="space-y-2 text-sm" style="color: var(--text-secondary);">
                    <div class="flex items-center p-3 rounded-xl" style="background-color: var(--input-bg);">
                        <code class="text-sm font-semibold" style="color: var(--success);">last-7-days</code>
                        <span class="ml-auto text-xs">Last 7 days from now</span>
                    </div>
                    <div class="flex items-center p-3 rounded-xl" style="background-color: var(--input-bg);">
                        <code class="text-sm font-semibold" style="color: var(--success);">last-14-days</code>
                        <span class="ml-auto text-xs">Last 14 days from now</span>
                    </div>
                    <div class="flex items-center p-3 rounded-xl" style="background-color: var(--input-bg);">
                        <code class="text-sm font-semibold" style="color: var(--success);">last-30-days</code>
                        <span class="ml-auto text-xs">Last 30 days from now</span>
                    </div>
                    <div class="flex items-center p-3 rounded-xl" style="background-color: var(--input-bg);">
                        <code class="text-sm font-semibold" style="color: var(--success);">last-3-months</code>
                        <span class="ml-auto text-xs">Last 3 months from now</span>
                    </div>
                    <div class="flex items-center p-3 rounded-xl" style="background-color: var(--input-bg);">
                        <code class="text-sm font-semibold" style="color: var(--success);">last-6-months</code>
                        <span class="ml-auto text-xs">Last 6 months from now</span>
                    </div>
                    <div class="flex items-center p-3 rounded-xl" style="background-color: var(--input-bg);">
                        <code class="text-sm font-semibold" style="color: var(--success);">last-12-months</code>
                        <span class="ml-auto text-xs">Last 12 months from now</span>
                    </div>
                    <div class="flex items-center p-3 rounded-xl" style="background-color: var(--input-bg);">
                        <code class="text-sm font-semibold" style="color: var(--success);">last-2-years</code>
                        <span class="ml-auto text-xs">Previous 2 full years</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6 rounded-2xl highlight-box" style="background-color: rgba(79, 70, 229, 0.05); border: 1px solid var(--accent);">
            <h4 class="text-sm font-semibold mb-3 flex items-center" style="color: var(--accent);">
                <i data-feather="info" class="mr-2" style="width: 16px; height: 16px;"></i>
                Custom Period Format
            </h4>
            <p class="text-sm mb-3" style="color: var(--text-secondary);">
                Use flexible custom periods with format <code style="background-color: var(--input-bg); padding: 2px 6px; border-radius: 4px;">last-N-unit</code>:
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm" style="color: var(--text-secondary);">
                <div class="flex items-start">
                    <i data-feather="chevron-right" class="mr-2 flex-shrink-0 mt-0.5" style="width: 14px; height: 14px; color: var(--accent);"></i>
                    <span><code style="background-color: var(--input-bg); padding: 2px 6px; border-radius: 4px;">last-3-hours</code> - Last 3 hours from now</span>
                </div>
                <div class="flex items-start">
                    <i data-feather="chevron-right" class="mr-2 flex-shrink-0 mt-0.5" style="width: 14px; height: 14px; color: var(--accent);"></i>
                    <span><code style="background-color: var(--input-bg); padding: 2px 6px; border-radius: 4px;">last-21-days</code> - Last 21 days from now</span>
                </div>
                <div class="flex items-start">
                    <i data-feather="chevron-right" class="mr-2 flex-shrink-0 mt-0.5" style="width: 14px; height: 14px; color: var(--accent);"></i>
                    <span><code style="background-color: var(--input-bg); padding: 2px 6px; border-radius: 4px;">last-8-weeks</code> - Last 8 weeks from now</span>
                </div>
                <div class="flex items-start">
                    <i data-feather="chevron-right" class="mr-2 flex-shrink-0 mt-0.5" style="width: 14px; height: 14px; color: var(--accent);"></i>
                    <span><code style="background-color: var(--input-bg); padding: 2px 6px; border-radius: 4px;">last-18-months</code> - Last 18 months from now</span>
                </div>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Live Examples Section -->
    <div class="mb-10">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, var(--accent), var(--success));">
                <i data-feather="code" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Live API Examples</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Click any example to test the API in real-time</p>
            </div>
        </div>

        <!-- Basic Queries -->
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-5" style="color: var(--text-primary);">Period-Based Queries</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php
                $basicExamples = [
                    ['title' => "Today's USD Events", 'url' => "{$baseUrl}/news-api-v1/news-api.php?api_key={$apiKey}&currency=USD&period=today", 'desc' => 'Current day USD events'],
                    ['title' => "This Week EUR Events", 'url' => "{$baseUrl}/news-api-v1/news-api.php?api_key={$apiKey}&currency=EUR&period=this-week", 'desc' => 'Week-to-date EUR events'],
                    ['title' => 'Last 7 Days (Minimal)', 'url' => "{$baseUrl}/news-api-v1/news-api.php?api_key={$apiKey}&period=last-7-days&display=min", 'desc' => 'Minimal data format'],
                    ['title' => 'Last 30 Days', 'url' => "{$baseUrl}/news-api-v1/news-api.php?api_key={$apiKey}&period=last-30-days", 'desc' => 'Rolling 30-day period'],
                    ['title' => 'This Month GBP', 'url' => "{$baseUrl}/news-api-v1/news-api.php?api_key={$apiKey}&currency=GBP&period=this-month", 'desc' => 'Month-to-date GBP events'],
                    ['title' => 'Last 3 Months', 'url' => "{$baseUrl}/news-api-v1/news-api.php?api_key={$apiKey}&period=last-3-months", 'desc' => 'Quarterly data'],
                ];
                foreach ($basicExamples as $example):
                ?>
                <a href="<?php echo htmlspecialchars($example['url']); ?>" target="_blank" class="example-card block p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                    <div class="flex items-start justify-between mb-3">
                        <h4 class="text-sm font-semibold flex-1" style="color: var(--text-primary);"><?php echo $example['title']; ?></h4>
                        <i data-feather="external-link" style="width: 16px; height: 16px; color: var(--text-secondary);"></i>
                    </div>
                    <p class="text-xs mb-3" style="color: var(--text-secondary);"><?php echo $example['desc']; ?></p>
                    <div class="flex items-center text-xs font-medium" style="color: var(--accent);">
                        <span>Try Example</span>
                        <i data-feather="arrow-right" class="ml-2" style="width: 14px; height: 14px;"></i>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Advanced Queries -->
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-5" style="color: var(--text-primary);">Advanced Filtering</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php
                $advancedExamples = [
                    ['title' => 'Exclude USD & GBP', 'url' => "{$baseUrl}/news-api-v1/news-api.php?api_key={$apiKey}&currency_exclude=USD,GBP&period=last-month", 'desc' => 'Currency exclusion filter'],
                    ['title' => 'Custom Period (15 days)', 'url' => "{$baseUrl}/news-api-v1/news-api.php?api_key={$apiKey}&period=last-15-days", 'desc' => 'Custom rolling period'],
                    ['title' => 'Date Range (No Time)', 'url' => "{$baseUrl}/news-api-v1/news-api.php?api_key={$apiKey}&start_date=2025-01-01&end_date=2025-12-31", 'desc' => 'Full year with dates only'],
                    ['title' => 'Future Events', 'url' => "{$baseUrl}/news-api-v1/news-api.php?api_key={$apiKey}&period=future&future_limit=tomorrow", 'desc' => "Tomorrow's scheduled events"],
                    ['title' => 'Next Week Events', 'url' => "{$baseUrl}/news-api-v1/news-api.php?api_key={$apiKey}&period=future&future_limit=next-week", 'desc' => 'Next week forecast'],
                    ['title' => 'All Today Events', 'url' => "{$baseUrl}/news-api-v1/news-api.php?api_key={$apiKey}&period=today&spit_out=all", 'desc' => 'Past & future for today'],
                    ['title' => 'NY Timezone', 'url' => "{$baseUrl}/news-api-v1/news-api.php?api_key={$apiKey}&period=today&time_zone=NY", 'desc' => 'Today in New York time'],
                    ['title' => 'London Timezone', 'url' => "{$baseUrl}/news-api-v1/news-api.php?api_key={$apiKey}&period=today&time_zone=LON", 'desc' => 'Today in London time'],
                ];
                foreach ($advancedExamples as $example):
                ?>
                <a href="<?php echo htmlspecialchars($example['url']); ?>" target="_blank" class="example-card block p-5 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                    <div class="flex items-start justify-between mb-3">
                        <h4 class="text-sm font-semibold flex-1" style="color: var(--text-primary);"><?php echo $example['title']; ?></h4>
                        <i data-feather="external-link" style="width: 16px; height: 16px; color: var(--text-secondary);"></i>
                    </div>
                    <p class="text-xs mb-3" style="color: var(--text-secondary);"><?php echo $example['desc']; ?></p>
                    <div class="flex items-center text-xs font-medium" style="color: var(--accent);">
                        <span>Try Example</span>
                        <i data-feather="arrow-right" class="ml-2" style="width: 14px; height: 14px;"></i>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- cURL Examples Section -->
    <div class="mb-10">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, var(--accent), var(--success));">
                <i data-feather="terminal" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">cURL Examples</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Copy and paste these commands into your terminal</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <!-- Period-Based -->
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">Period-Based Queries</h3>
                <div class="space-y-4">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold" style="color: var(--text-secondary);">Today's USD Events</label>
                            <button onclick="copyToClipboard('curl &quot;<?= htmlspecialchars($baseUrl) ?>/news-api-v1/news-api.php?api_key=<?= htmlspecialchars($apiKey) ?>&currency=USD&period=today&quot;')" class="text-xs px-2 py-1 rounded-lg transition-colors flex items-center gap-1" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);">
                                <i data-feather="copy" style="width: 12px; height: 12px;"></i>
                                Copy
                            </button>
                        </div>
                        <div class="p-3 rounded-lg" style="background-color: var(--input-bg); border: 1px solid var(--input-border); font-family: 'Fira Code', monospace; font-size: 0.75rem; overflow-x: auto;">
                            <code style="color: var(--text-primary);">curl "<?= htmlspecialchars($baseUrl) ?>/news-api-v1/news-api.php?api_key=<?= htmlspecialchars($apiKey) ?>&currency=USD&period=today"</code>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold" style="color: var(--text-secondary);">This Week EUR Events</label>
                            <button onclick="copyToClipboard('curl &quot;<?= htmlspecialchars($baseUrl) ?>/news-api-v1/news-api.php?api_key=<?= htmlspecialchars($apiKey) ?>&currency=EUR&period=this-week&quot;')" class="text-xs px-2 py-1 rounded-lg transition-colors flex items-center gap-1" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);">
                                <i data-feather="copy" style="width: 12px; height: 12px;"></i>
                                Copy
                            </button>
                        </div>
                        <div class="p-3 rounded-lg" style="background-color: var(--input-bg); border: 1px solid var(--input-border); font-family: 'Fira Code', monospace; font-size: 0.75rem; overflow-x: auto;">
                            <code style="color: var(--text-primary);">curl "<?= htmlspecialchars($baseUrl) ?>/news-api-v1/news-api.php?api_key=<?= htmlspecialchars($apiKey) ?>&currency=EUR&period=this-week"</code>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold" style="color: var(--text-secondary);">Last 7 Days (Minimal Format)</label>
                            <button onclick="copyToClipboard('curl &quot;<?= htmlspecialchars($baseUrl) ?>/news-api-v1/news-api.php?api_key=<?= htmlspecialchars($apiKey) ?>&period=last-7-days&display=min&quot;')" class="text-xs px-2 py-1 rounded-lg transition-colors flex items-center gap-1" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);">
                                <i data-feather="copy" style="width: 12px; height: 12px;"></i>
                                Copy
                            </button>
                        </div>
                        <div class="p-3 rounded-lg" style="background-color: var(--input-bg); border: 1px solid var(--input-border); font-family: 'Fira Code', monospace; font-size: 0.75rem; overflow-x: auto;">
                            <code style="color: var(--text-primary);">curl "<?= htmlspecialchars($baseUrl) ?>/news-api-v1/news-api.php?api_key=<?= htmlspecialchars($apiKey) ?>&period=last-7-days&display=min"</code>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Advanced Filtering -->
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">Advanced Filtering</h3>
                <div class="space-y-4">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold" style="color: var(--text-secondary);">Exclude USD & GBP</label>
                            <button onclick="copyToClipboard('curl &quot;<?= htmlspecialchars($baseUrl) ?>/news-api-v1/news-api.php?api_key=<?= htmlspecialchars($apiKey) ?>&currency_exclude=USD,GBP&period=last-month&quot;')" class="text-xs px-2 py-1 rounded-lg transition-colors flex items-center gap-1" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);">
                                <i data-feather="copy" style="width: 12px; height: 12px;"></i>
                                Copy
                            </button>
                        </div>
                        <div class="p-3 rounded-lg" style="background-color: var(--input-bg); border: 1px solid var(--input-border); font-family: 'Fira Code', monospace; font-size: 0.75rem; overflow-x: auto;">
                            <code style="color: var(--text-primary);">curl "<?= htmlspecialchars($baseUrl) ?>/news-api-v1/news-api.php?api_key=<?= htmlspecialchars($apiKey) ?>&currency_exclude=USD,GBP&period=last-month"</code>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold" style="color: var(--text-secondary);">Future Events - Tomorrow</label>
                            <button onclick="copyToClipboard('curl &quot;<?= htmlspecialchars($baseUrl) ?>/news-api-v1/news-api.php?api_key=<?= htmlspecialchars($apiKey) ?>&period=future&future_limit=tomorrow&quot;')" class="text-xs px-2 py-1 rounded-lg transition-colors flex items-center gap-1" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);">
                                <i data-feather="copy" style="width: 12px; height: 12px;"></i>
                                Copy
                            </button>
                        </div>
                        <div class="p-3 rounded-lg" style="background-color: var(--input-bg); border: 1px solid var(--input-border); font-family: 'Fira Code', monospace; font-size: 0.75rem; overflow-x: auto;">
                            <code style="color: var(--text-primary);">curl "<?= htmlspecialchars($baseUrl) ?>/news-api-v1/news-api.php?api_key=<?= htmlspecialchars($apiKey) ?>&period=future&future_limit=tomorrow"</code>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold" style="color: var(--text-secondary);">NY Timezone - Today</label>
                            <button onclick="copyToClipboard('curl &quot;<?= htmlspecialchars($baseUrl) ?>/news-api-v1/news-api.php?api_key=<?= htmlspecialchars($apiKey) ?>&period=today&time_zone=NY&quot;')" class="text-xs px-2 py-1 rounded-lg transition-colors flex items-center gap-1" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);">
                                <i data-feather="copy" style="width: 12px; height: 12px;"></i>
                                Copy
                            </button>
                        </div>
                        <div class="p-3 rounded-lg" style="background-color: var(--input-bg); border: 1px solid var(--input-border); font-family: 'Fira Code', monospace; font-size: 0.75rem; overflow-x: auto;">
                            <code style="color: var(--text-primary);">curl "<?= htmlspecialchars($baseUrl) ?>/news-api-v1/news-api.php?api_key=<?= htmlspecialchars($apiKey) ?>&period=today&time_zone=NY"</code>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Date Range -->
            <div class="p-6 rounded-2xl" style="background: linear-gradient(135deg, rgba(79, 70, 229, 0.05) 0%, rgba(16, 185, 129, 0.05) 100%); border: 1px solid var(--border);">
                <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">Custom Date Range</h3>
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-semibold" style="color: var(--text-secondary);">Full Year 2025</label>
                        <button onclick="copyToClipboard('curl &quot;<?= htmlspecialchars($baseUrl) ?>/news-api-v1/news-api.php?api_key=<?= htmlspecialchars($apiKey) ?>&start_date=2025-01-01&end_date=2025-12-31&quot;')" class="text-xs px-2 py-1 rounded-lg transition-colors flex items-center gap-1" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);">
                            <i data-feather="copy" style="width: 12px; height: 12px;"></i>
                            Copy
                        </button>
                    </div>
                    <div class="p-3 rounded-lg" style="background-color: var(--input-bg); border: 1px solid var(--input-border); font-family: 'Fira Code', monospace; font-size: 0.75rem; overflow-x: auto;">
                        <code style="color: var(--text-primary);">curl "<?= htmlspecialchars($baseUrl) ?>/news-api-v1/news-api.php?api_key=<?= htmlspecialchars($apiKey) ?>&start_date=2025-01-01&end_date=2025-12-31"</code>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Best Practices Section -->
    <div class="mb-10">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, var(--warning), var(--danger));">
                <i data-feather="check-circle" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Best Practices</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Tips for optimal API usage</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-base font-semibold mb-4 flex items-center" style="color: var(--text-primary);">
                    <i data-feather="zap" class="mr-2" style="width: 18px; height: 18px; color: var(--warning);"></i>
                    Performance Tips
                </h3>
                <ul class="space-y-3 text-sm" style="color: var(--text-secondary);">
                    <li class="flex items-start">
                        <i data-feather="chevron-right" class="mr-2 flex-shrink-0 mt-0.5" style="width: 14px; height: 14px; color: var(--warning);"></i>
                        <span>Use <code style="background-color: var(--input-bg); padding: 2px 6px; border-radius: 4px;">display=min</code> for faster responses with essential data only</span>
                    </li>
                    <li class="flex items-start">
                        <i data-feather="chevron-right" class="mr-2 flex-shrink-0 mt-0.5" style="width: 14px; height: 14px; color: var(--warning);"></i>
                        <span>Combine currency filters with periods for targeted data retrieval</span>
                    </li>
                    <li class="flex items-start">
                        <i data-feather="chevron-right" class="mr-2 flex-shrink-0 mt-0.5" style="width: 14px; height: 14px; color: var(--warning);"></i>
                        <span>Use currency_exclude to remove unwanted currencies from broad queries</span>
                    </li>
                    <li class="flex items-start">
                        <i data-feather="chevron-right" class="mr-2 flex-shrink-0 mt-0.5" style="width: 14px; height: 14px; color: var(--warning);"></i>
                        <span>Use <code style="background-color: var(--input-bg); padding: 2px 6px; border-radius: 4px;">avoid_duplicates=true</code> to get only one event per consistent_event_id</span>
                    </li>
                    <li class="flex items-start">
                        <i data-feather="chevron-right" class="mr-2 flex-shrink-0 mt-0.5" style="width: 14px; height: 14px; color: var(--warning);"></i>
                        <span>Use <code style="background-color: var(--input-bg); padding: 2px 6px; border-radius: 4px;">ignore_weekends=true</code> with "yesterday" or "tomorrow" to skip non-trading days</span>
                    </li>
                </ul>
            </div>

            <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
                <h3 class="text-base font-semibold mb-4 flex items-center" style="color: var(--text-primary);">
                    <i data-feather="alert-circle" class="mr-2" style="width: 18px; height: 18px; color: var(--danger);"></i>
                    Important Notes
                </h3>
                <ul class="space-y-3 text-sm" style="color: var(--text-secondary);">
                    <li class="flex items-start">
                        <i data-feather="chevron-right" class="mr-2 flex-shrink-0 mt-0.5" style="width: 14px; height: 14px; color: var(--danger);"></i>
                        <span>Future events always have <strong>actual_value</strong> set to "TBD"</span>
                    </li>
                    <li class="flex items-start">
                        <i data-feather="chevron-right" class="mr-2 flex-shrink-0 mt-0.5" style="width: 14px; height: 14px; color: var(--danger);"></i>
                        <span>All timestamps are stored in UTC timezone (use time_zone parameter to convert)</span>
                    </li>
                    <li class="flex items-start">
                        <i data-feather="chevron-right" class="mr-2 flex-shrink-0 mt-0.5" style="width: 14px; height: 14px; color: var(--danger);"></i>
                        <span>Monitor your quota usage to avoid service interruption</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Footer Note -->
    <div class="p-8 rounded-2xl text-center" style="background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(16, 185, 129, 0.1) 100%); border: 1px solid var(--border);">
        <div class="mb-4">
            <span class="section-badge" style="background-color: var(--accent); color: white;">API v1.0</span>
        </div>
        <h3 class="text-lg font-semibold mb-2" style="color: var(--text-primary);">Start Analyzing Economic Events</h3>
        <p class="text-sm mb-4" style="color: var(--text-secondary);">All examples use your configured API key and endpoint. Click any example above to test in real-time.</p>
        <div class="text-xs" style="color: var(--text-secondary);">
            <p><strong style="color: var(--text-primary);">Features:</strong> Flexible periods, currency filtering, event grouping, future event handling, custom time ranges, timezone conversion</p>
        </div>
    </div>
</div>

<script>
    feather.replace();

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            alert('Copied to clipboard!');
        });
    }
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layouts/app.php';
?>
