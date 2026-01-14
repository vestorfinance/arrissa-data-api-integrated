<?php
require_once __DIR__ . '/../../app/Auth.php';

$title = 'Download Expert Advisors';
$page = 'download-eas';
ob_start();
?>

<style>
.ea-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.ea-card:hover {
    transform: translateY(-4px);
    border-color: var(--accent);
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
.gradient-icon {
    background: linear-gradient(135deg, var(--accent), var(--success));
}
</style>

<div class="p-8 max-w-[1400px] mx-auto">
    <!-- Hero Header -->
    <div class="mb-10">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-4xl font-bold mb-3 tracking-tight" style="color: var(--text-primary);">
                    Download Expert Advisors
                    <span class="section-badge ml-3" style="background-color: var(--success); color: var(--bg-primary);">MT5</span>
                </h1>
                <p class="text-lg" style="color: var(--text-secondary);">MT5 Expert Advisors for all Arrissa Data APIs</p>
            </div>
        </div>
        
        <!-- Installation Info Banner -->
        <div class="p-6 rounded-2xl gradient-bg" style="border: 1px solid var(--border);">
            <div class="flex items-start">
                <div class="flex-shrink-0 mr-4">
                    <div class="w-12 h-12 rounded-2xl gradient-icon flex items-center justify-center">
                        <i data-feather="info" style="width: 24px; height: 24px; color: white;"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">Quick Installation Guide</h3>
                    <ol class="space-y-2 text-sm" style="color: var(--text-secondary);">
                        <li class="flex items-start">
                            <span class="font-bold mr-2" style="color: var(--accent);">1.</span>
                            <span>Download the .ex5 (compiled) file for the API you want to use - .mq5 (source) is only needed if you want to edit</span>
                        </li>
                        <li class="flex items-start">
                            <span class="font-bold mr-2" style="color: var(--accent);">2.</span>
                            <span>Open MT5 and go to File → Open Data Folder</span>
                        </li>
                        <li class="flex items-start">
                            <span class="font-bold mr-2" style="color: var(--accent);">3.</span>
                            <span>Navigate to MQL5/Experts/ folder</span>
                        </li>
                        <li class="flex items-start">
                            <span class="font-bold mr-2" style="color: var(--accent);">4.</span>
                            <span>Copy the .ex5 file into the Experts folder</span>
                        </li>
                        <li class="flex items-start">
                            <span class="font-bold mr-2" style="color: var(--accent);">5.</span>
                            <span>Restart MT5 or refresh the Navigator panel (right-click → Refresh)</span>
                        </li>
                        <li class="flex items-start">
                            <span class="font-bold mr-2" style="color: var(--accent);">6.</span>
                            <span>Drag the EA from Navigator onto any chart to activate it</span>
                        </li>
                        <li class="flex items-start">
                            <span class="font-bold mr-2" style="color: var(--accent);">7.</span>
                            <span>In the EA settings, set the API URL to <strong style="color: var(--accent);">http://127.0.0.1</strong> (localhost) - do NOT use the URL from Settings page</span>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- EA Configuration Notice -->
    <div class="mb-10 p-6 rounded-2xl" style="background-color: rgba(245, 158, 11, 0.1); border: 1px solid var(--warning);">
        <div class="flex items-start">
            <div class="flex-shrink-0 mr-4">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background-color: var(--warning);">
                    <i data-feather="settings" style="width: 20px; height: 20px; color: white;"></i>
                </div>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">EA Configuration</h3>
                <p class="text-sm mb-2" style="color: var(--text-secondary);">When you attach an EA to a chart, you'll see the EA settings dialog. Configure the following:</p>
                <ul class="space-y-2 text-sm mt-3" style="color: var(--text-secondary);">
                    <li class="flex items-start">
                        <i data-feather="arrow-right" class="mr-2 flex-shrink-0 mt-0.5" style="width: 16px; height: 16px; color: var(--warning);"></i>
                        <span><strong style="color: var(--text-primary);">API URL:</strong> Always use <code style="background-color: var(--bg-secondary); padding: 2px 8px; border-radius: 6px; color: var(--accent);">http://127.0.0.1</code> (localhost)</span>
                    </li>
                    <li class="flex items-start">
                        <i data-feather="arrow-right" class="mr-2 flex-shrink-0 mt-0.5" style="width: 16px; height: 16px; color: var(--warning);"></i>
                        <span><strong style="color: var(--text-primary);">Important:</strong> Do NOT use the Base URL from the Settings page - EAs communicate directly with WAMP via localhost</span>
                    </li>
                    <li class="flex items-start">
                        <i data-feather="arrow-right" class="mr-2 flex-shrink-0 mt-0.5" style="width: 16px; height: 16px; color: var(--warning);"></i>
                        <span><strong style="color: var(--text-primary);">Allowed URLs:</strong> Go to Tools → Options → Expert Advisors and add <code style="background-color: var(--bg-secondary); padding: 2px 8px; border-radius: 6px; color: var(--accent);">http://127.0.0.1</code> to allowed URLs list</span>
                    </li>
                    <li class="flex items-start">
                        <i data-feather="arrow-right" class="mr-2 flex-shrink-0 mt-0.5" style="width: 16px; height: 16px; color: var(--warning);"></i>
                        <span><strong style="color: var(--text-primary);">AutoTrading:</strong> Enable the AutoTrading button in MT5 toolbar</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Market Data API EA -->
    <div class="mb-12">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl gradient-icon flex items-center justify-center mr-4">
                <i data-feather="bar-chart-2" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Market Data API Expert Advisor</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Provides OHLC candle data with optional tick volume</p>
            </div>
        </div>

        <div class="ea-card p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">What This EA Does</h3>
                    <ul class="space-y-2 text-sm" style="color: var(--text-secondary);">
                        <li class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0 mt-0.5" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span>Serves real-time and historical OHLC candle data from MT5</span>
                        </li>
                        <li class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0 mt-0.5" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span>Supports all timeframes (M1, M5, M15, M30, H1, H4, D1, W1, MN1)</span>
                        </li>
                        <li class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0 mt-0.5" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span>Optional tick volume data inclusion</span>
                        </li>
                        <li class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0 mt-0.5" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span>Time range queries (today, last-hour, last-7days, etc.)</span>
                        </li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">Download Files</h3>
                    <div class="space-y-3">
                        <a href="/expert-advisors/Arrissa Data MT5 Market Data API.mq5" download class="block p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i data-feather="file-text" class="mr-3" style="width: 20px; height: 20px; color: var(--accent);"></i>
                                    <div>
                                        <div class="font-semibold text-sm" style="color: var(--text-primary);">Source Code (.mq5) - Optional</div>
                                        <div class="text-xs" style="color: var(--text-secondary);">Only needed if you want to edit and recompile</div>
                                    </div>
                                </div>
                                <i data-feather="download" style="width: 18px; height: 18px; color: var(--text-secondary);"></i>
                            </div>
                        </a>
                        <a href="/expert-advisors/Arrissa Data MT5 Market Data API.ex5" download class="block p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i data-feather="cpu" class="mr-3" style="width: 20px; height: 20px; color: var(--success);"></i>
                                    <div>
                                        <div class="font-semibold text-sm" style="color: var(--text-primary);">Compiled (.ex5) - Required</div>
                                        <div class="text-xs" style="color: var(--text-secondary);">Ready to use, no editing needed</div>
                                    </div>
                                </div>
                                <i data-feather="download" style="width: 18px; height: 18px; color: var(--text-secondary);"></i>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Orders API EA -->
    <div class="mb-12">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl gradient-icon flex items-center justify-center mr-4">
                <i data-feather="trending-up" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Orders API Expert Advisor</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Complete trading operations including orders, history, and profit tracking</p>
            </div>
        </div>

        <div class="ea-card p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">What This EA Does</h3>
                    <ul class="space-y-2 text-sm" style="color: var(--text-secondary);">
                        <li class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0 mt-0.5" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span>Execute market orders (BUY, SELL) with SL/TP</span>
                        </li>
                        <li class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0 mt-0.5" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span>Place pending orders (BUY_LIMIT, SELL_LIMIT, BUY_STOP, SELL_STOP)</span>
                        </li>
                        <li class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0 mt-0.5" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span>Position management (CLOSE, BREAK_EVEN, TRAIL_SL)</span>
                        </li>
                        <li class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0 mt-0.5" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span>Trade history queries and profit/loss calculations</span>
                        </li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">Download Files</h3>
                    <div class="space-y-3">
                        <a href="/expert-advisors/Arrissa Data MT5 Orders API.mq5" download class="block p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i data-feather="file-text" class="mr-3" style="width: 20px; height: 20px; color: var(--accent);"></i>
                                    <div>
                                        <div class="font-semibold text-sm" style="color: var(--text-primary);">Source Code (.mq5) - Optional</div>
                                        <div class="text-xs" style="color: var(--text-secondary);">Only needed if you want to edit and recompile</div>
                                    </div>
                                </div>
                                <i data-feather="download" style="width: 18px; height: 18px; color: var(--text-secondary);"></i>
                            </div>
                        </a>
                        <a href="/expert-advisors/Arrissa Data MT5 Orders API.ex5" download class="block p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i data-feather="cpu" class="mr-3" style="width: 20px; height: 20px; color: var(--success);"></i>
                                    <div>
                                        <div class="font-semibold text-sm" style="color: var(--text-primary);">Compiled (.ex5) - Required</div>
                                        <div class="text-xs" style="color: var(--text-secondary);">Ready to use, no editing needed</div>
                                    </div>
                                </div>
                                <i data-feather="download" style="width: 18px; height: 18px; color: var(--text-secondary);"></i>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Symbol Info API EA -->
    <div class="mb-12">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl gradient-icon flex items-center justify-center mr-4">
                <i data-feather="activity" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Symbol Info API Expert Advisor</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Statistical analysis of symbol behavior patterns</p>
            </div>
        </div>

        <div class="ea-card p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">What This EA Does</h3>
                    <ul class="space-y-2 text-sm" style="color: var(--text-secondary);">
                        <li class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0 mt-0.5" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span>Analyzes symbol behavior across multiple timeframes</span>
                        </li>
                        <li class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0 mt-0.5" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span>Provides bullish vs bearish candle analysis</span>
                        </li>
                        <li class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0 mt-0.5" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span>Detailed wick analysis and pattern detection</span>
                        </li>
                        <li class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0 mt-0.5" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span>Historical backtesting with pretend date/time</span>
                        </li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">Download Files</h3>
                    <div class="space-y-3">
                        <a href="/expert-advisors/Arrissa Data Symbol Info API.mq5" download class="block p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i data-feather="file-text" class="mr-3" style="width: 20px; height: 20px; color: var(--accent);"></i>
                                    <div>
                                        <div class="font-semibold text-sm" style="color: var(--text-primary);">Source Code (.mq5) - Optional</div>
                                        <div class="text-xs" style="color: var(--text-secondary);">Only needed if you want to edit and recompile</div>
                                    </div>
                                </div>
                                <i data-feather="download" style="width: 18px; height: 18px; color: var(--text-secondary);"></i>
                            </div>
                        </a>
                        <a href="/expert-advisors/Arrissa Data Symbol Info API.ex5" download class="block p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i data-feather="cpu" class="mr-3" style="width: 20px; height: 20px; color: var(--success);"></i>
                                    <div>
                                        <div class="font-semibold text-sm" style="color: var(--text-primary);">Compiled (.ex5) - Required</div>
                                        <div class="text-xs" style="color: var(--text-secondary);">Ready to use, no editing needed</div>
                                    </div>
                                </div>
                                <i data-feather="download" style="width: 18px; height: 18px; color: var(--text-secondary);"></i>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Quarters Theory API EA -->
    <div class="mb-12">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center mr-4" style="background: linear-gradient(135deg, #9C27B0, #BA68C8);">
                <i data-feather="target" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold" style="color: var(--text-primary);">Richchild Quarters Theory API Expert Advisor</h2>
                <p class="text-sm" style="color: var(--text-secondary);">Real-time multi-timeframe quarter analysis for precision trading</p>
            </div>
        </div>

        <div class="ea-card p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">What This EA Does</h3>
                    <ul class="space-y-2 text-sm" style="color: var(--text-secondary);">
                        <li class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0 mt-0.5" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span>Analyzes price position in quarters (0-25%, 25-50%, 50-75%, 75-100%)</span>
                        </li>
                        <li class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0 mt-0.5" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span>Tracks time progression through period quarters (1st, 2nd, 3rd, 4th)</span>
                        </li>
                        <li class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0 mt-0.5" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span>Provides quota values (historical average range ÷ 4) for each timeframe</span>
                        </li>
                        <li class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0 mt-0.5" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span>Covers 9 timeframes: M15, M30, H1, H4, H6, H12, D1, W1, MN1</span>
                        </li>
                        <li class="flex items-start">
                            <i data-feather="check-circle" class="mr-2 flex-shrink-0 mt-0.5" style="width: 16px; height: 16px; color: var(--success);"></i>
                            <span>Countdown timers for each timeframe period close</span>
                        </li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">Download Files</h3>
                    <div class="space-y-3">
                        <a href="/expert-advisors/Richchild Quarters Theory Data EA.mq5" download class="block p-4 rounded-xl" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i data-feather="file-text" class="mr-3" style="width: 20px; height: 20px; color: var(--accent);"></i>
                                    <div>
                                        <div class="font-semibold text-sm" style="color: var(--text-primary);">Source Code (.mq5) - Optional</div>
                                        <div class="text-xs" style="color: var(--text-secondary);">Only needed if you want to edit and recompile</div>
                                    </div>
                                </div>
                                <i data-feather="download" style="width: 18px; height: 18px; color: var(--text-secondary);"></i>
                            </div>
                        </a>
                        <div class="p-4 rounded-xl" style="background-color: rgba(245, 158, 11, 0.1); border: 1px solid var(--warning);">
                            <div class="flex items-start">
                                <i data-feather="alert-circle" class="mr-3 flex-shrink-0 mt-0.5" style="width: 20px; height: 20px; color: var(--warning);"></i>
                                <div>
                                    <div class="font-semibold text-sm mb-1" style="color: var(--text-primary);">No .ex5 file needed</div>
                                    <div class="text-xs" style="color: var(--text-secondary);">This EA only needs to be compiled from source. Download the .mq5 file, place in MQL5/Experts/, and compile in MetaEditor (F7).</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Important Notes -->
    <div class="p-6 rounded-2xl" style="background-color: var(--card-bg); border: 1px solid var(--accent);">
        <div class="flex items-start">
            <div class="w-10 h-10 rounded-xl gradient-icon flex items-center justify-center mr-4 flex-shrink-0">
                <i data-feather="alert-circle" style="width: 20px; height: 20px; color: white;"></i>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">Important Notes</h3>
                <ul class="space-y-2 text-sm" style="color: var(--text-secondary);">
                    <li class="flex items-start">
                        <span class="mr-2">•</span>
                        <span>Each EA must be running on an MT5 chart for the respective API to work</span>
                    </li>
                    <li class="flex items-start">
                        <span class="mr-2">•</span>
                        <span>Enable AutoTrading in MT5 (click the "AutoTrading" button in the toolbar)</span>
                    </li>
                    <li class="flex items-start">
                        <span class="mr-2">•</span>
                        <span>Add <strong style="color: var(--accent);">http://127.0.0.1</strong> to allowed URLs in Tools → Options → Expert Advisors</span>
                    </li>
                    <li class="flex items-start">
                        <span class="mr-2">•</span>
                        <span>In EA settings, use <strong style="color: var(--accent);">http://127.0.0.1</strong> for API URL (not the URL from Settings page)</span>
                    </li>
                    <li class="flex items-start">
                        <span class="mr-2">•</span>
                        <span>The EAs work with both demo and live accounts</span>
                    </li>
                    <li class="flex items-start">
                        <span class="mr-2">•</span>
                        <span>Source code (.mq5) files allow you to customize and recompile if needed</span>
                    </li>
                    <li class="flex items-start">
                        <span class="mr-2">•</span>
                        <span>If editing source code: Copy <a href="/expert-advisors/includes/jason.mqh" download style="color: var(--accent); text-decoration: underline;">jason.mqh</a> to MQL5/Include/ folder before compiling</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

</div>

<script>
    feather.replace();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layouts/app.php';
?>
