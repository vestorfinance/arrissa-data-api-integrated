<?php
require_once __DIR__ . '/../../app/Auth.php';

$title = 'Brokers';
$page = 'brokers';
ob_start();
?>

<style>
.broker-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 24px;
    overflow: hidden;
}
.broker-card:hover {
    transform: translateY(-4px);
    border-color: var(--accent) !important;
    box-shadow: 0 8px 32px rgba(79, 70, 229, 0.15);
}
.broker-badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 9999px;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.broker-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 10px 14px;
    border-radius: 12px;
    min-width: 0;
    flex: 1;
}
.create-account-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 11px 24px;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
    border: 1px solid var(--accent);
    color: var(--accent);
    background: transparent;
    width: 100%;
}
.create-account-btn:hover {
    background-color: var(--accent);
    color: #fff;
    box-shadow: 0 4px 16px rgba(79, 70, 229, 0.35);
}
.broker-icon-wrap {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.4rem;
    font-weight: 800;
    letter-spacing: -1px;
}
.divider {
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--border), transparent);
    margin: 2.5rem 0;
}
</style>

<div class="p-8 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-10">
        <div class="flex items-start justify-between mb-3">
            <div>
                <h1 class="text-3xl font-bold mb-2 tracking-tight" style="color: var(--text-primary);">
                    Recommended Brokers
                </h1>
                <p class="text-base" style="color: var(--text-secondary);">Partner brokers trusted for trading with Arrissa Data APIs. Open a live account to get started.</p>
            </div>
            <div class="flex-shrink-0 ml-6">
                <span class="broker-badge" style="background-color: rgba(79, 70, 229, 0.12); color: var(--accent); border: 1px solid rgba(79,70,229,0.25);">
                    <i data-feather="star" style="width: 10px; height: 10px; margin-right: 5px; fill: currentColor;"></i>
                    6 Partners
                </span>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Broker Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

        <!-- 1. Exness Technologies -->
        <div class="broker-card p-6" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="flex items-center gap-4 mb-5">
                <div class="broker-icon-wrap" style="background-color: rgba(79,70,229,0.12); color: var(--accent);">
                    EX
                </div>
                <div>
                    <h3 class="text-lg font-bold leading-tight" style="color: var(--text-primary);">Exness Technologies</h3>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="broker-badge" style="background-color: rgba(16,185,129,0.12); color: var(--success); border: 1px solid rgba(16,185,129,0.25);">Regulated</span>
                        <span class="broker-badge" style="background-color: rgba(245,158,11,0.1); color: var(--warning); border: 1px solid rgba(245,158,11,0.2);">FCA · CySEC</span>
                    </div>
                </div>
            </div>
            <p class="text-sm leading-relaxed mb-5" style="color: var(--text-secondary);">
                One of the world's largest forex brokers by trading volume. Founded in 2008, Exness offers raw spreads, instant withdrawals 24/7, and multiple account types suited for all trader levels.
            </p>
            <!-- Stats -->
            <div class="flex gap-3 mb-6">
                <div class="broker-stat" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <span class="text-xs font-semibold" style="color: var(--text-secondary);">Min Deposit</span>
                    <span class="text-base font-bold mt-1" style="color: var(--text-primary);">$10</span>
                </div>
                <div class="broker-stat" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <span class="text-xs font-semibold" style="color: var(--text-secondary);">Spreads From</span>
                    <span class="text-base font-bold mt-1" style="color: var(--text-primary);">0.0 pips</span>
                </div>
                <div class="broker-stat" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <span class="text-xs font-semibold" style="color: var(--text-secondary);">Leverage</span>
                    <span class="text-base font-bold mt-1" style="color: var(--text-primary);">1:2000</span>
                </div>
            </div>
            <a href="https://one.exnessonelink.com/a/l5kqp6wwav" target="_blank" rel="noopener noreferrer" class="create-account-btn">
                <i data-feather="user-plus" style="width: 15px; height: 15px;"></i>
                Create Account
            </a>
        </div>

        <!-- 2. Just Markets -->
        <div class="broker-card p-6" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="flex items-center gap-4 mb-5">
                <div class="broker-icon-wrap" style="background-color: rgba(16,185,129,0.12); color: var(--success);">
                    JM
                </div>
                <div>
                    <h3 class="text-lg font-bold leading-tight" style="color: var(--text-primary);">Just Markets</h3>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="broker-badge" style="background-color: rgba(16,185,129,0.12); color: var(--success); border: 1px solid rgba(16,185,129,0.25);">Regulated</span>
                        <span class="broker-badge" style="background-color: rgba(245,158,11,0.1); color: var(--warning); border: 1px solid rgba(245,158,11,0.2);">FSC</span>
                    </div>
                </div>
            </div>
            <p class="text-sm leading-relaxed mb-5" style="color: var(--text-secondary);">
                Multi-asset broker offering forex, metals, indices, and crypto. Competitive tight spreads, a built-in copy trading platform, and fast MT5 execution for both new and experienced traders.
            </p>
            <div class="flex gap-3 mb-6">
                <div class="broker-stat" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <span class="text-xs font-semibold" style="color: var(--text-secondary);">Min Deposit</span>
                    <span class="text-base font-bold mt-1" style="color: var(--text-primary);">$1</span>
                </div>
                <div class="broker-stat" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <span class="text-xs font-semibold" style="color: var(--text-secondary);">Spreads From</span>
                    <span class="text-base font-bold mt-1" style="color: var(--text-primary);">0.0 pips</span>
                </div>
                <div class="broker-stat" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <span class="text-xs font-semibold" style="color: var(--text-secondary);">Leverage</span>
                    <span class="text-base font-bold mt-1" style="color: var(--text-primary);">1:3000</span>
                </div>
            </div>
            <a href="https://one.justmarkets.link/a/u1t760v8ma" target="_blank" rel="noopener noreferrer" class="create-account-btn">
                <i data-feather="user-plus" style="width: 15px; height: 15px;"></i>
                Create Account
            </a>
        </div>

        <!-- 3. AxiCorp -->
        <div class="broker-card p-6" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="flex items-center gap-4 mb-5">
                <div class="broker-icon-wrap" style="background-color: rgba(239,68,68,0.12); color: var(--danger);">
                    AX
                </div>
                <div>
                    <h3 class="text-lg font-bold leading-tight" style="color: var(--text-primary);">AxiCorp</h3>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="broker-badge" style="background-color: rgba(16,185,129,0.12); color: var(--success); border: 1px solid rgba(16,185,129,0.25);">Regulated</span>
                        <span class="broker-badge" style="background-color: rgba(245,158,11,0.1); color: var(--warning); border: 1px solid rgba(245,158,11,0.2);">ASIC · FCA</span>
                    </div>
                </div>
            </div>
            <p class="text-sm leading-relaxed mb-5" style="color: var(--text-secondary);">
                Award-winning ECN broker regulated by ASIC and FCA. Raw spreads from 0.0 pips, no minimum deposit on standard accounts, and access to MT4 &amp; MT5 with industry-leading execution speeds.
            </p>
            <div class="flex gap-3 mb-6">
                <div class="broker-stat" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <span class="text-xs font-semibold" style="color: var(--text-secondary);">Min Deposit</span>
                    <span class="text-base font-bold mt-1" style="color: var(--text-primary);">$0</span>
                </div>
                <div class="broker-stat" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <span class="text-xs font-semibold" style="color: var(--text-secondary);">Spreads From</span>
                    <span class="text-base font-bold mt-1" style="color: var(--text-primary);">0.0 pips</span>
                </div>
                <div class="broker-stat" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <span class="text-xs font-semibold" style="color: var(--text-secondary);">Leverage</span>
                    <span class="text-base font-bold mt-1" style="color: var(--text-primary);">1:500</span>
                </div>
            </div>
            <a href="https://www.axi.com/int/live-account?promocode=4737724" target="_blank" rel="noopener noreferrer" class="create-account-btn">
                <i data-feather="user-plus" style="width: 15px; height: 15px;"></i>
                Create Account
            </a>
        </div>

        <!-- 4. HeroFX -->
        <div class="broker-card p-6" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="flex items-center gap-4 mb-5">
                <div class="broker-icon-wrap" style="background-color: rgba(245,158,11,0.12); color: var(--warning);">
                    HX
                </div>
                <div>
                    <h3 class="text-lg font-bold leading-tight" style="color: var(--text-primary);">HeroFX</h3>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="broker-badge" style="background-color: rgba(16,185,129,0.12); color: var(--success); border: 1px solid rgba(16,185,129,0.25);">Regulated</span>
                        <span class="broker-badge" style="background-color: rgba(245,158,11,0.1); color: var(--warning); border: 1px solid rgba(245,158,11,0.2);">MT5</span>
                    </div>
                </div>
            </div>
            <p class="text-sm leading-relaxed mb-5" style="color: var(--text-secondary);">
                Commission-free trading with ultra-high leverage up to 1:2000. Instant deposit and withdrawal processing, 24/7 multilingual support, and a range of account types for every trading style.
            </p>
            <div class="flex gap-3 mb-6">
                <div class="broker-stat" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <span class="text-xs font-semibold" style="color: var(--text-secondary);">Min Deposit</span>
                    <span class="text-base font-bold mt-1" style="color: var(--text-primary);">$5</span>
                </div>
                <div class="broker-stat" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <span class="text-xs font-semibold" style="color: var(--text-secondary);">Commission</span>
                    <span class="text-base font-bold mt-1" style="color: var(--text-primary);">$0</span>
                </div>
                <div class="broker-stat" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <span class="text-xs font-semibold" style="color: var(--text-secondary);">Leverage</span>
                    <span class="text-base font-bold mt-1" style="color: var(--text-primary);">1:2000</span>
                </div>
            </div>
            <a href="https://herofx.co/?partner_code=8138744" target="_blank" rel="noopener noreferrer" class="create-account-btn">
                <i data-feather="user-plus" style="width: 15px; height: 15px;"></i>
                Create Account
            </a>
        </div>

        <!-- 5. GatesFX -->
        <div class="broker-card p-6" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="flex items-center gap-4 mb-5">
                <div class="broker-icon-wrap" style="background-color: rgba(99,102,241,0.12); color: #818cf8;">
                    GF
                </div>
                <div>
                    <h3 class="text-lg font-bold leading-tight" style="color: var(--text-primary);">GatesFX</h3>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="broker-badge" style="background-color: rgba(16,185,129,0.12); color: var(--success); border: 1px solid rgba(16,185,129,0.25);">Regulated</span>
                        <span class="broker-badge" style="background-color: rgba(245,158,11,0.1); color: var(--warning); border: 1px solid rgba(245,158,11,0.2);">STP · ECN</span>
                    </div>
                </div>
            </div>
            <p class="text-sm leading-relaxed mb-5" style="color: var(--text-secondary);">
                STP/ECN broker with multiple account types designed for all levels. Tight spreads from 0.0 pips, MT4 &amp; MT5 compatible, low latency order execution, and a low barrier entry with minimal deposit.
            </p>
            <div class="flex gap-3 mb-6">
                <div class="broker-stat" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <span class="text-xs font-semibold" style="color: var(--text-secondary);">Min Deposit</span>
                    <span class="text-base font-bold mt-1" style="color: var(--text-primary);">$10</span>
                </div>
                <div class="broker-stat" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <span class="text-xs font-semibold" style="color: var(--text-secondary);">Spreads From</span>
                    <span class="text-base font-bold mt-1" style="color: var(--text-primary);">0.0 pips</span>
                </div>
                <div class="broker-stat" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <span class="text-xs font-semibold" style="color: var(--text-secondary);">Platforms</span>
                    <span class="text-base font-bold mt-1" style="color: var(--text-primary);">MT4 · MT5</span>
                </div>
            </div>
            <a href="https://secure.gatesfx.com/links/go/2489" target="_blank" rel="noopener noreferrer" class="create-account-btn">
                <i data-feather="user-plus" style="width: 15px; height: 15px;"></i>
                Create Account
            </a>
        </div>

        <!-- 6. IC Markets -->
        <div class="broker-card p-6" style="background-color: var(--card-bg); border: 1px solid var(--border);">
            <div class="flex items-center gap-4 mb-5">
                <div class="broker-icon-wrap" style="background-color: rgba(59,130,246,0.12); color: #60a5fa;">
                    IC
                </div>
                <div>
                    <h3 class="text-lg font-bold leading-tight" style="color: var(--text-primary);">IC Markets</h3>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="broker-badge" style="background-color: rgba(16,185,129,0.12); color: var(--success); border: 1px solid rgba(16,185,129,0.25);">Regulated</span>
                        <span class="broker-badge" style="background-color: rgba(245,158,11,0.1); color: var(--warning); border: 1px solid rgba(245,158,11,0.2);">ASIC · CySEC</span>
                    </div>
                </div>
            </div>
            <p class="text-sm leading-relaxed mb-5" style="color: var(--text-secondary);">
                True ECN broker renowned for ultra-fast execution under 1ms and raw spreads from 0.0 pips. Regulated by ASIC, CySEC and FSA. Ideal for algorithmic and high-frequency traders.
            </p>
            <div class="flex gap-3 mb-6">
                <div class="broker-stat" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <span class="text-xs font-semibold" style="color: var(--text-secondary);">Min Deposit</span>
                    <span class="text-base font-bold mt-1" style="color: var(--text-primary);">$200</span>
                </div>
                <div class="broker-stat" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <span class="text-xs font-semibold" style="color: var(--text-secondary);">Spreads From</span>
                    <span class="text-base font-bold mt-1" style="color: var(--text-primary);">0.0 pips</span>
                </div>
                <div class="broker-stat" style="background-color: var(--bg-secondary); border: 1px solid var(--border);">
                    <span class="text-xs font-semibold" style="color: var(--text-secondary);">Execution</span>
                    <span class="text-base font-bold mt-1" style="color: var(--text-primary);">&lt;1ms</span>
                </div>
            </div>
            <a href="https://icmarkets.com/?camp=72520" target="_blank" rel="noopener noreferrer" class="create-account-btn">
                <i data-feather="user-plus" style="width: 15px; height: 15px;"></i>
                Create Account
            </a>
        </div>

    </div>

    <!-- Footer note -->
    <div class="mt-10 text-center">
        <p class="text-xs" style="color: var(--text-secondary);">
            <i data-feather="info" style="width: 12px; height: 12px; display: inline; vertical-align: middle; margin-right: 4px;"></i>
            Partner links. Trading involves significant risk. Please review each broker's terms and conditions before opening an account.
        </p>
    </div>
</div>

<script>
    feather.replace();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layouts/app.php';
?>
