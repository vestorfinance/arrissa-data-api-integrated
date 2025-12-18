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
                placeholder="Search APIs, settings, documentation..." 
                class="w-full rounded-full pl-12 pr-5 py-4 text-base focus:outline-none"
                style="background-color: var(--input-bg); color: var(--text-secondary); border: 1px solid var(--input-border);"
                oninput="searchContent()"
            >
            <i data-feather="search" class="absolute left-4 top-4" style="width: 20px; height: 20px; color: var(--text-secondary);"></i>
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
        <a href="/market-data-api-guide" class="api-card block rounded-2xl overflow-hidden transition-all duration-200 group" data-title="Market Data API Guide" data-description="Comprehensive documentation for MT5 Market Data API" style="background-color: var(--card-bg); border: 1px solid var(--border);" onmouseover="this.style.borderColor='var(--input-border)'; this.style.backgroundColor='var(--bg-secondary)';" onmouseout="this.style.borderColor='var(--border)'; this.style.backgroundColor='var(--card-bg)';">
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
        <a href="/event-id-reference" class="api-card block rounded-2xl overflow-hidden transition-all duration-200 group" data-title="Event ID Reference" data-description="Lookup all consistent event IDs and their currencies" style="background-color: var(--card-bg); border: 1px solid var(--border);" onmouseover="this.style.borderColor='var(--input-border)'; this.style.backgroundColor='var(--bg-secondary)';" onmouseout="this.style.borderColor='var(--border)'; this.style.backgroundColor='var(--card-bg)';">
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
        <a href="/news-api-guide" class="api-card block rounded-2xl overflow-hidden transition-all duration-200 group" data-title="News API Guide" data-description="Economic events and news data API documentation" style="background-color: var(--card-bg); border: 1px solid var(--border);" onmouseover="this.style.borderColor='var(--input-border)'; this.style.backgroundColor='var(--bg-secondary)';" onmouseout="this.style.borderColor='var(--border)'; this.style.backgroundColor='var(--card-bg)';">
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
        <a href="/chart-image-api-guide" class="api-card block rounded-2xl overflow-hidden transition-all duration-200 group" data-title="Chart Image API Guide" data-description="Endpoint details & usage examples" style="background-color: var(--card-bg); border: 1px solid var(--border);" onmouseover="this.style.borderColor='var(--input-border)'; this.style.backgroundColor='var(--bg-secondary)';" onmouseout="this.style.borderColor='var(--border)'; this.style.backgroundColor='var(--card-bg)';">
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
        <a href="/orders-api-guide" class="api-card block rounded-2xl overflow-hidden transition-all duration-200 group" data-title="Orders API Guide" data-description="Complete MT5 trading operations documentation" style="background-color: var(--card-bg); border: 1px solid var(--border);" onmouseover="this.style.borderColor='var(--input-border)'; this.style.backgroundColor='var(--bg-secondary)';" onmouseout="this.style.borderColor='var(--border)'; this.style.backgroundColor='var(--card-bg)';">
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
        <a href="/symbol-info-api-guide" class="api-card block rounded-2xl overflow-hidden transition-all duration-200 group" style="background-color: var(--card-bg); border: 1px solid var(--border);" onmouseover="this.style.borderColor='var(--input-border)'; this.style.backgroundColor='var(--bg-secondary)';" onmouseout="this.style.borderColor='var(--border)'; this.style.backgroundColor='var(--card-bg)';">
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
    function searchCards() {
        const searchInput = document.getElementById('searchInput');
        const searchTerm = searchInput.value.toLowerCase().trim();
        const cards = document.querySelectorAll('.api-card');
        const noResults = document.getElementById('noResults');
        const cardsGrid = document.getElementById('cardsGrid');
        
        let visibleCount = 0;
        
        cards.forEach(card => {
            const title = card.getAttribute('data-title').toLowerCase();
            const description = card.getAttribute('data-description').toLowerCase();
            
            if (searchTerm === '' || title.includes(searchTerm) || description.includes(searchTerm)) {
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
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/app.php';
?>
