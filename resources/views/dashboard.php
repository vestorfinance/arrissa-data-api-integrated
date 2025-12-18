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
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/app.php';
?>
