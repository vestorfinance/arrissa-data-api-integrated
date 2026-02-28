<?php
require_once __DIR__ . '/../../app/Database.php';

$db = Database::getInstance();
$stmt = $db->query("SELECT value FROM settings WHERE key = 'app_name'");
$result = $stmt->fetch();
$appName = $result ? $result['value'] : 'Arrissa Data API';

$title = 'Dashboard - ' . $appName;
ob_start();
?>

<div class="p-8 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="text-center mb-8">
        <h1 class="text-3xl font-semibold mb-2 tracking-tight" style="color: var(--text-primary);">API Documentation</h1>
        <p class="text-base" style="color: var(--text-secondary);">Explore available endpoints and integration guides</p>
    </div>

    <!-- Centered Search Bar -->
    <div class="flex justify-center mb-8">
        <div class="relative w-full max-w-2xl">
            <input 
                type="text" 
                id="searchInput"
                placeholder="Search APIs, settings, documentation, database..." 
                class="w-full rounded-full pl-12 pr-5 py-4 text-base focus:outline-none"
                style="background-color: var(--input-bg); color: var(--text-secondary); border: 1px solid var(--input-border);"
                oninput="performSearch()"
                autocomplete="off"
            >
            <i data-feather="search" class="absolute left-4 top-4" style="width: 20px; height: 20px; color: var(--text-secondary);"></i>
            
            <!-- Search Results Dropdown -->
            <div id="searchResults" class="absolute w-full mt-2 rounded-2xl shadow-lg hidden" style="background-color: var(--card-bg); border: 1px solid var(--border); max-height: 400px; overflow-y: auto; z-index: 1000;">
            </div>
        </div>
    </div>

    <!-- No Results Message -->
    <div id="noResults" class="text-center py-12 hidden">
        <i data-feather="search" style="width: 48px; height: 48px; color: var(--text-secondary); margin: 0 auto 16px;"></i>
        <h3 class="text-lg font-semibold mb-2" style="color: var(--text-primary);">No results found</h3>
        <p class="text-sm" style="color: var(--text-secondary);">Try searching with different keywords</p>
    </div>

    <!-- API Cards Grid -->
    <div id="cardsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

    <!-- System Stats Widget -->
    <div id="sysStatsCard" class="col-span-full rounded-2xl p-5" style="background-color:var(--card-bg);border:1px solid var(--border);">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0" style="background-color:var(--bg-secondary);border:1px solid var(--border);">
                    <i data-feather="activity" style="width:16px;height:16px;color:var(--text-secondary);"></i>
                </div>
                <span class="font-semibold text-sm" style="color:var(--text-primary);">System Resources</span>
            </div>
            <span id="stats-ts" class="text-xs" style="color:var(--text-secondary);">loading…</span>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <!-- CPU -->
            <div class="p-3 rounded-xl" style="background-color:var(--bg-secondary);border:1px solid var(--border);">
                <p class="text-xs font-semibold mb-1" style="color:var(--text-secondary);">CPU Load (1m)</p>
                <p id="stats-cpu" class="text-xl font-bold" style="color:var(--text-primary);">—</p>
            </div>
            <!-- RAM -->
            <div class="p-3 rounded-xl" style="background-color:var(--bg-secondary);border:1px solid var(--border);">
                <div class="flex justify-between mb-1">
                    <p class="text-xs font-semibold" style="color:var(--text-secondary);">RAM</p>
                    <p id="stats-ram-pct" class="text-xs font-semibold" style="color:var(--text-primary);">—</p>
                </div>
                <div class="rounded-full overflow-hidden mb-1" style="height:5px;background:var(--border);">
                    <div id="stats-ram-bar" class="h-full rounded-full transition-all" style="width:0%;background:var(--accent);"></div>
                </div>
                <p id="stats-ram-detail" class="text-xs" style="color:var(--text-secondary);">—</p>
            </div>
            <!-- Disk -->
            <div class="p-3 rounded-xl" style="background-color:var(--bg-secondary);border:1px solid var(--border);">
                <div class="flex justify-between mb-1">
                    <p class="text-xs font-semibold" style="color:var(--text-secondary);">Disk</p>
                    <p id="stats-disk-pct" class="text-xs font-semibold" style="color:var(--text-primary);">—</p>
                </div>
                <div class="rounded-full overflow-hidden mb-1" style="height:5px;background:var(--border);">
                    <div id="stats-disk-bar" class="h-full rounded-full transition-all" style="width:0%;background:#10b981;"></div>
                </div>
                <p id="stats-disk-detail" class="text-xs" style="color:var(--text-secondary);">—</p>
            </div>
            <!-- Uptime & PHP -->
            <div class="p-3 rounded-xl" style="background-color:var(--bg-secondary);border:1px solid var(--border);">
                <p class="text-xs font-semibold mb-1" style="color:var(--text-secondary);">Uptime</p>
                <p id="stats-uptime" class="text-sm font-bold mb-1" style="color:var(--text-primary);">—</p>
                <p id="stats-php" class="text-xs" style="color:var(--text-secondary);">—</p>
            </div>
        </div>
    </div>
        <!-- Market Data API Guide -->
        <a href="/market-data-api-guide" class="api-card block rounded-2xl overflow-hidden transition-all duration-200 group" data-keywords="market data api ohlc candle tick volume timeframe m1 m5 m15 m30 h1 h4 d1 w1 mn1 mt5 metatrader expert advisor ea range query today last-hour last-7days chart technical indicators open high low close" style="background-color: var(--card-bg); border: 1px solid var(--border);" onmouseover="this.style.borderColor='var(--input-border)'; this.style.backgroundColor='var(--bg-secondary)';" onmouseout="this.style.borderColor='var(--border)'; this.style.backgroundColor='var(--card-bg)';">
            <div class="p-6">
                <div class="w-12 h-12 rounded-full flex items-center justify-center mb-5" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <i data-feather="trending-up" class="group-hover-icon" style="width: 24px; height: 24px; color: var(--text-secondary);"></i>
                </div>
                <h3 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">Market Data API Guide</h3>
                <p class="text-sm mb-6 leading-relaxed" style="color: var(--text-secondary);">Comprehensive documentation for MT5 Market Data API.</p>
                <button class="inline-flex items-center text-sm font-medium px-4 py-2 rounded-lg transition-all" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);" onmouseover="this.style.backgroundColor='var(--bg-secondary)';" onmouseout="this.style.backgroundColor='var(--input-bg)';">
                    View Documentation
                    <i data-feather="arrow-right" class="ml-2" style="width: 16px; height: 16px;"></i>
                </button>
            </div>
        </a>

        <!-- Event ID Reference -->
        <a href="/event-id-reference" class="api-card block rounded-2xl overflow-hidden transition-all duration-200 group" data-keywords="event id reference consistent economic calendar forex news usd eur gbp jpy aud cad chf nzd currencies nfp gdp cpi inflation interest rate fomc fed ecb boe pmi manufacturing services retail sales employment unemployment" style="background-color: var(--card-bg); border: 1px solid var(--border);" onmouseover="this.style.borderColor='var(--input-border)'; this.style.backgroundColor='var(--bg-secondary)';" onmouseout="this.style.borderColor='var(--border)'; this.style.backgroundColor='var(--card-bg)';">
            <div class="p-6">
                <div class="w-12 h-12 rounded-full flex items-center justify-center mb-5" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <i data-feather="list" class="group-hover-icon" style="width: 24px; height: 24px; color: var(--text-secondary);"></i>
                </div>
                <h3 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">Event ID Reference</h3>
                <p class="text-sm mb-6 leading-relaxed" style="color: var(--text-secondary);">Lookup all consistent event IDs and their currencies.</p>
                <button class="inline-flex items-center text-sm font-medium px-4 py-2 rounded-lg transition-all" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);" onmouseover="this.style.backgroundColor='var(--bg-secondary)';" onmouseout="this.style.backgroundColor='var(--input-bg)';">
                    View Reference
                    <i data-feather="arrow-right" class="ml-2" style="width: 16px; height: 16px;"></i>
                </button>
            </div>
        </a>

        <!-- News API Guide -->
        <a href="/news-api-guide" class="api-card block rounded-2xl overflow-hidden transition-all duration-200 group" data-keywords="news api economic events calendar forex announcements impact high medium low sentiment date range currency filter database sqlite scraping" style="background-color: var(--card-bg); border: 1px solid var(--border);" onmouseover="this.style.borderColor='var(--input-border)'; this.style.backgroundColor='var(--bg-secondary)';" onmouseout="this.style.borderColor='var(--border)'; this.style.backgroundColor='var(--card-bg)';">
            <div class="p-6">
                <div class="w-12 h-12 rounded-full flex items-center justify-center mb-5" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <i data-feather="file-text" class="group-hover-icon" style="width: 24px; height: 24px; color: var(--text-secondary);"></i>
                </div>
                <h3 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">News API Guide</h3>
                <p class="text-sm mb-6 leading-relaxed" style="color: var(--text-secondary);">Economic events and news data API documentation.</p>
                <button class="inline-flex items-center text-sm font-medium px-4 py-2 rounded-lg transition-all" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);" onmouseover="this.style.backgroundColor='var(--bg-secondary)';" onmouseout="this.style.backgroundColor='var(--input-bg)';">
                    View Guide
                    <i data-feather="arrow-right" class="ml-2" style="width: 16px; height: 16px;"></i>
                </button>
            </div>
        </a>

        <!-- Similar Scene API Guide -->
        <a href="/similar-scene-api-guide" class="api-card block rounded-2xl overflow-hidden transition-all duration-200 group" data-keywords="similar scene api historical event occurrences pattern analysis machine learning training backtesting correlation market reaction timeframe m1 m30 h1 h4 d1 symbol xauusd eurusd event matching synchronized ohlc candles" style="background-color: var(--card-bg); border: 1px solid var(--border);" onmouseover="this.style.borderColor='var(--input-border)'; this.style.backgroundColor='var(--bg-secondary)';" onmouseout="this.style.borderColor='var(--border)'; this.style.backgroundColor='var(--card-bg)';">
            <div class="p-6">
                <div class="w-12 h-12 rounded-full flex items-center justify-center mb-5" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <i data-feather="layers" class="group-hover-icon" style="width: 24px; height: 24px; color: var(--text-secondary);"></i>
                </div>
                <h3 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">Similar Scene API Guide</h3>
                <p class="text-sm mb-6 leading-relaxed" style="color: var(--text-secondary);">Historical event patterns with synchronized multi-timeframe market data.</p>
                <button class="inline-flex items-center text-sm font-medium px-4 py-2 rounded-lg transition-all" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);" onmouseover="this.style.backgroundColor='var(--bg-secondary)';" onmouseout="this.style.backgroundColor='var(--input-bg)';">
                    View Guide
                    <i data-feather="arrow-right" class="ml-2" style="width: 16px; height: 16px;"></i>
                </button>
            </div>
        </a>

        <!-- Chart Image API Guide -->
        <a href="/chart-image-api-guide" class="api-card block rounded-2xl overflow-hidden transition-all duration-200 group" data-keywords="chart image api screenshot capture png jpg jpeg svg canvas visualization graph" style="background-color: var(--card-bg); border: 1px solid var(--border);" onmouseover="this.style.borderColor='var(--input-border)'; this.style.backgroundColor='var(--bg-secondary)';" onmouseout="this.style.borderColor='var(--border)'; this.style.backgroundColor='var(--card-bg)';">
            <div class="p-6">
                <div class="w-12 h-12 rounded-full flex items-center justify-center mb-5" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <i data-feather="image" class="group-hover-icon" style="width: 24px; height: 24px; color: var(--text-secondary);"></i>
                </div>
                <h3 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">Chart Image API Guide</h3>
                <p class="text-sm mb-6 leading-relaxed" style="color: var(--text-secondary);">Endpoint details & usage examples.</p>
                <button class="inline-flex items-center text-sm font-medium px-4 py-2 rounded-lg transition-all" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);" onmouseover="this.style.backgroundColor='var(--bg-secondary)';" onmouseout="this.style.backgroundColor='var(--input-bg)';">
                    View Guide
                    <i data-feather="arrow-right" class="ml-2" style="width: 16px; height: 16px;"></i>
                </button>
            </div>
        </a>

        <!-- Orders API -->
        <a href="/orders-api-guide" class="api-card block rounded-2xl overflow-hidden transition-all duration-200 group" data-keywords="orders api trading mt5 buy sell close position pending limit stop market sl tp break even trailing stop loss take profit history profit loss calculation volume lots magic number" style="background-color: var(--card-bg); border: 1px solid var(--border);" onmouseover="this.style.borderColor='var(--input-border)'; this.style.backgroundColor='var(--bg-secondary)';" onmouseout="this.style.borderColor='var(--border)'; this.style.backgroundColor='var(--card-bg)';">
            <div class="p-6">
                <div class="w-12 h-12 rounded-full flex items-center justify-center mb-5" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <i data-feather="shopping-cart" class="group-hover-icon" style="width: 24px; height: 24px; color: var(--text-secondary);"></i>
                </div>
                <h3 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">Orders API Guide</h3>
                <p class="text-sm mb-6 leading-relaxed" style="color: var(--text-secondary);">Complete MT5 trading operations documentation.</p>
                <button class="inline-flex items-center text-sm font-medium px-4 py-2 rounded-lg transition-all" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);" onmouseover="this.style.backgroundColor='var(--bg-secondary)';" onmouseout="this.style.backgroundColor='var(--input-bg)';">
                    View Guide
                    <i data-feather="arrow-right" class="ml-2" style="width: 16px; height: 16px;"></i>
                </button>
            </div>
        </a>

        <!-- Symbol Info API -->
        <a href="/symbol-info-api-guide" class="api-card block rounded-2xl overflow-hidden transition-all duration-200 group" data-keywords="symbol info api analysis behavior pattern statistics average high low body wick bullish bearish candle timeframe backtesting historical pretend date time m5 m15 m30 h1 h4 h8 h12 d1 w1 monthly" style="background-color: var(--card-bg); border: 1px solid var(--border);" onmouseover="this.style.borderColor='var(--input-border)'; this.style.backgroundColor='var(--bg-secondary)';" onmouseout="this.style.borderColor='var(--border)'; this.style.backgroundColor='var(--card-bg)';">
            <div class="p-6">
                <div class="w-12 h-12 rounded-full flex items-center justify-center mb-5" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <i data-feather="bar-chart-2" class="group-hover-icon" style="width: 24px; height: 24px; color: var(--text-secondary);"></i>
                </div>
                <h3 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">Symbol Info API</h3>
                <p class="text-sm mb-6 leading-relaxed" style="color: var(--text-secondary);">Advanced symbol behavior analysis & average calculations.</p>
                <button class="inline-flex items-center text-sm font-medium px-4 py-2 rounded-lg transition-all" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);" onmouseover="this.style.backgroundColor='var(--bg-secondary)';" onmouseout="this.style.backgroundColor='var(--input-bg)';">
                    View Guide
                    <i data-feather="arrow-right" class="ml-2" style="width: 16px; height: 16px;"></i>
                </button>
            </div>
        </a>

        <!-- Quarters Theory API -->
        <a href="/quarters-theory-api-guide" class="api-card block rounded-2xl overflow-hidden transition-all duration-200 group" data-keywords="quarters theory api richchild price quarters time quarters quota values low percentage high percentage m15 m30 h1 h4 h6 h12 d1 w1 mn1 multi-timeframe confluence entry exit timing support resistance reversal continuation" style="background-color: var(--card-bg); border: 1px solid var(--border);" onmouseover="this.style.borderColor='var(--input-border)'; this.style.backgroundColor='var(--bg-secondary)';" onmouseout="this.style.borderColor='var(--border)'; this.style.backgroundColor='var(--card-bg)';">
            <div class="p-6">
                <div class="w-12 h-12 rounded-full flex items-center justify-center mb-5" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <i data-feather="target" class="group-hover-icon" style="width: 24px; height: 24px; color: var(--text-secondary);"></i>
                </div>
                <h3 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">Quarters Theory API</h3>
                <p class="text-sm mb-6 leading-relaxed" style="color: var(--text-secondary);">Multi-timeframe quarter analysis for precision entry/exit timing.</p>
                <span class="inline-flex items-center text-sm font-medium px-4 py-2 rounded-lg transition-all" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);">
                    View Guide
                    <i data-feather="arrow-right" class="ml-2" style="width: 16px; height: 16px;"></i>
                </span>
            </div>
        </a>

        <!-- URL API -->
        <a href="/url-api-guide" class="api-card block rounded-2xl overflow-hidden transition-all duration-200 group" data-keywords="url api fetch scrape web page content html text extract title body http https bearer token basic auth session cookie custom headers proxy request external" style="background-color: var(--card-bg); border: 1px solid var(--border);" onmouseover="this.style.borderColor='var(--input-border)'; this.style.backgroundColor='var(--bg-secondary)';" onmouseout="this.style.borderColor='var(--border)'; this.style.backgroundColor='var(--card-bg)';">
            <div class="p-6">
                <div class="w-12 h-12 rounded-full flex items-center justify-center mb-5" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <i data-feather="globe" class="group-hover-icon" style="width: 24px; height: 24px; color: var(--text-secondary);"></i>
                </div>
                <h3 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">URL API Guide</h3>
                <p class="text-sm mb-6 leading-relaxed" style="color: var(--text-secondary);">Fetch and extract text content from any URL with multi-method authentication support.</p>
                <button class="inline-flex items-center text-sm font-medium px-4 py-2 rounded-lg transition-all" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);" onmouseover="this.style.backgroundColor='var(--bg-secondary)';" onmouseout="this.style.backgroundColor='var(--input-bg)';">
                    View Guide
                    <i data-feather="arrow-right" class="ml-2" style="width: 16px; height: 16px;"></i>
                </button>
            </div>
        </a>

        <!-- TMA + CG API -->
        <a href="/tma-cg-api-guide" class="api-card block rounded-2xl overflow-hidden transition-all duration-200 group" data-keywords="tma cg api triangular moving average center of gravity premium discount zone bands deviation fibonacci upper lower equilibrium buy sell" style="background-color: var(--card-bg); border: 1px solid var(--border);" onmouseover="this.style.borderColor='var(--input-border)'; this.style.backgroundColor='var(--bg-secondary)';" onmouseout="this.style.borderColor='var(--border)'; this.style.backgroundColor='var(--card-bg)';">
            <div class="p-6">
                <div class="w-12 h-12 rounded-full flex items-center justify-center mb-5" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <i data-feather="activity" class="group-hover-icon" style="width: 24px; height: 24px; color: var(--text-secondary);"></i>
                </div>
                <h3 class="text-lg font-semibold mb-3" style="color: var(--text-primary);">TMA + CG API</h3>
                <p class="text-sm mb-6 leading-relaxed" style="color: var(--text-secondary);">Premium/discount zone detection with dynamic TMA bands.</p>
                <span class="inline-flex items-center text-sm font-medium px-4 py-2 rounded-lg transition-all" style="background-color: var(--input-bg); color: var(--text-primary); border: 1px solid var(--input-border);">
                    View Guide
                    <i data-feather="arrow-right" class="ml-2" style="width: 16px; height: 16px;"></i>
                </span>
            </div>
        </a>
    </div>
</div>

<script>
    let searchTimeout;
    
    function performSearch() {
        clearTimeout(searchTimeout);
        const searchInput = document.getElementById('searchInput');
        const searchResults = document.getElementById('searchResults');
        const query = searchInput.value.trim();
        
        if (query.length < 2) {
            searchResults.classList.add('hidden');
            filterCards('');
            return;
        }
        
        // Filter cards immediately
        filterCards(query);
        
        // Debounce API search
        searchTimeout = setTimeout(async () => {
            try {
                const response = await fetch(`/api/search?q=${encodeURIComponent(query)}`);
                const text = await response.text();
                
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON response:', text);
                    searchResults.classList.add('hidden');
                    return;
                }
                
                if (data.results && data.results.length > 0) {
                    displaySearchResults(data.results);
                } else {
                    searchResults.classList.add('hidden');
                }
            } catch (error) {
                console.error('Search error:', error);
                searchResults.classList.add('hidden');
            }
        }, 300);
    }
    
    function displaySearchResults(results) {
        const searchResults = document.getElementById('searchResults');
        let html = '<div class="p-2">';
        
        const grouped = {};
        results.forEach(result => {
            if (!grouped[result.type]) grouped[result.type] = [];
            grouped[result.type].push(result);
        });
        
        for (const [type, items] of Object.entries(grouped)) {
            html += `<div class="px-3 py-2 text-xs font-semibold" style="color: var(--text-secondary);">${type.toUpperCase()}</div>`;
            items.forEach(result => {
                html += `
                    <a href="${result.url}" class="block px-4 py-3 rounded-xl hover:bg-opacity-50 transition-colors" style="color: var(--text-primary);" onmouseover="this.style.backgroundColor='var(--bg-secondary)'" onmouseout="this.style.backgroundColor='transparent'">
                        <div class="flex items-start">
                            <i data-feather="${result.icon}" class="mr-3 flex-shrink-0" style="width: 18px; height: 18px; color: var(--accent);"></i>
                            <div class="flex-1">
                                <div class="font-medium text-sm">${result.title}</div>
                                <div class="text-xs mt-1" style="color: var(--text-secondary);">${result.description}</div>
                            </div>
                        </div>
                    </a>
                `;
            });
        }
        
        html += '</div>';
        searchResults.innerHTML = html;
        searchResults.classList.remove('hidden');
        feather.replace();
    }
    
    function filterCards(query) {
        const cards = document.querySelectorAll('.api-card');
        const noResults = document.getElementById('noResults');
        const cardsGrid = document.getElementById('cardsGrid');
        
        if (query.length < 2) {
            cards.forEach(card => card.style.display = 'block');
            cardsGrid.style.display = 'grid';
            noResults.classList.add('hidden');
            return;
        }
        
        const searchTerm = query.toLowerCase();
        let visibleCount = 0;
        
        cards.forEach(card => {
            const keywords = card.getAttribute('data-keywords').toLowerCase();
            const title = card.querySelector('h3').textContent.toLowerCase();
            const description = card.querySelector('p').textContent.toLowerCase();
            
            if (keywords.includes(searchTerm) || title.includes(searchTerm) || description.includes(searchTerm)) {
                card.style.display = 'block';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        if (visibleCount === 0) {
            cardsGrid.style.display = 'none';
            noResults.classList.remove('hidden');
            feather.replace();
        } else {
            cardsGrid.style.display = 'grid';
            noResults.classList.add('hidden');
        }
    }
    
    // Close search results when clicking outside
    document.addEventListener('click', function(e) {
        const searchInput = document.getElementById('searchInput');
        const searchResults = document.getElementById('searchResults');
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.classList.add('hidden');
        }
    });

    // ── System stats widget ──────────────────────────────────────────
    function barColor(pct) {
        if (pct >= 90) return '#ef4444';
        if (pct >= 70) return '#f59e0b';
        return null; // use default
    }

    async function fetchStats() {
        try {
            const r = await fetch('/api/system-stats');
            if (!r.ok) return;
            const d = await r.json();

            // CPU
            const cpuEl = document.getElementById('stats-cpu');
            cpuEl.textContent = d.cpu_load_1m !== null ? d.cpu_load_1m : 'N/A';

            // RAM
            if (d.ram_pct !== null) {
                document.getElementById('stats-ram-pct').textContent = d.ram_pct + '%';
                const ramBar = document.getElementById('stats-ram-bar');
                ramBar.style.width = d.ram_pct + '%';
                const rc = barColor(d.ram_pct);
                if (rc) ramBar.style.background = rc;
                document.getElementById('stats-ram-detail').textContent =
                    (d.ram_used_h || '?') + ' / ' + (d.ram_total_h || '?');
            } else {
                document.getElementById('stats-ram-pct').textContent = 'N/A';
                document.getElementById('stats-ram-detail').textContent = 'Not available on Windows';
            }

            // Disk
            if (d.disk_pct !== null) {
                document.getElementById('stats-disk-pct').textContent = d.disk_pct + '%';
                const diskBar = document.getElementById('stats-disk-bar');
                diskBar.style.width = d.disk_pct + '%';
                const dc = barColor(d.disk_pct);
                if (dc) diskBar.style.background = dc;
                document.getElementById('stats-disk-detail').textContent =
                    (d.disk_used_h || '?') + ' / ' + (d.disk_total_h || '?');
            }

            // Uptime & PHP
            document.getElementById('stats-uptime').textContent = d.uptime_h || 'N/A';
            document.getElementById('stats-php').textContent = 'PHP ' + (d.php_version || '?');

            // Timestamp
            const now = new Date();
            document.getElementById('stats-ts').textContent =
                'Updated ' + now.getHours().toString().padStart(2,'0') + ':' +
                now.getMinutes().toString().padStart(2,'0') + ':' +
                now.getSeconds().toString().padStart(2,'0');

            feather.replace();
        } catch (e) { /* silent */ }
    }

    fetchStats();
    setInterval(fetchStats, 5000);
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/app.php';
?>
