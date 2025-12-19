<?php
/**
 * Chart Image Streaming View
 * Displays a continuously updating chart image that refreshes every 500ms
 */

// Get all query parameters to pass to the chart API
$params = $_GET;
unset($params['stream']); // Remove stream parameter as we don't need it for the actual image generation
unset($params['streaming']); // Remove streaming parameter as well

// Build query string for the chart image
$queryString = http_build_query($params);
$chartUrl = '/chart-image-api-v1/chart-image-api.php?' . $queryString;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Chart Stream - <?php echo htmlspecialchars($params['symbol'] ?? 'Chart'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: rgba(20, 20, 20, 0.95);
            display: flex;
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #ffffff;
            overflow-x: hidden;
        }
        
        .stream-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 0;
            gap: 0;
            transition: margin-left 0.3s ease;
        }
        
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: rgba(20, 20, 20, 0.95);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            padding: 20px;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s ease;
        }
        
        .sidebar.mobile-hidden {
            transform: translateX(-100%);
        }
        
        .hamburger {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            cursor: pointer;
            z-index: 1001;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        
        .hamburger span {
            width: 24px;
            height: 2px;
            background: white;
            transition: all 0.3s ease;
        }
        
        .hamburger.active span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }
        
        .hamburger.active span:nth-child(2) {
            opacity: 0;
        }
        
        .hamburger.active span:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -6px);
        }
        
        .sidebar-header {
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .control-section {
            margin-bottom: 24px;
        }
        
        .control-section-title {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 12px;
            letter-spacing: 0.5px;
        }
        
        .stream-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }
        
        .stream-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 18px;
            font-weight: 600;
        }
        
        .live-indicator {
            width: 12px;
            height: 12px;
            background: #ef4444;
            border-radius: 50%;
            animation: pulse 1.5s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(0.95); }
        }
        
        .stream-info {
            display: flex;
            gap: 20px;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .chart-frame {
            width: 100%;
            height: 100vh;
            background: rgba(20, 20, 20, 0.95);
            border-radius: 0;
            overflow: hidden;
            box-shadow: none;
            flex: 1;
        }
        
        #chartImage {
            width: 100%;
            height: auto;
            display: block;
        }
        
        .btn {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: #ffffff;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
        }
        
        .btn.active {
            background: #3b82f6;
            border-color: #3b82f6;
        }
        
        #updateCounter {
            color: #3b82f6;
            font-weight: 600;
        }
        
        .error-message {
            padding: 15px 20px;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 12px;
            color: #ef4444;
            display: none;
        }
        
        select {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: #ffffff;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            outline: none;
        }
        
        select:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
        }
        
        select option {
            background: #1a1a1a;
            color: #ffffff;
        }
        
        .stat-card {
            padding: 12px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .stat-label {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.5);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        
        .stat-value {
            font-size: 18px;
            font-weight: 600;
            color: #3b82f6;
        }
        
        /* Desktop styles */
        @media (min-width: 769px) {
            .stream-header {
                display: none;
            }
            
            .error-message {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 999;
                max-width: 400px;
            }
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .stream-container {
                margin-left: 0 !important;
                padding: 20px;
                gap: 20px;
            }
            
            .chart-frame {
                border-radius: 12px;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
                height: auto;
            }
            
            .hamburger {
                display: flex;
            }
            
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.mobile-visible {
                transform: translateX(0);
            }
            
            .stream-info {
                display: none;
            }
        }
        
        @media (min-width: 769px) {
            .stream-container {
                margin-left: 250px;
            }
        }
    </style>
</head>
<body>
    <!-- Hamburger Menu -->
    <button class="hamburger" id="hamburger">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <!-- Main Content -->
    <div class="stream-container">
        <div class="stream-header">
            <div class="stream-title">
                <div class="live-indicator"></div>
                <span>LIVE CHART STREAM</span>
            </div>
            <div class="stream-info">
                <div class="info-item">
                    <span>Symbol:</span>
                    <strong id="currentSymbol"><?php echo htmlspecialchars($params['symbol'] ?? 'N/A'); ?></strong>
                </div>
                <div class="info-item">
                    <span>Timeframe:</span>
                    <strong id="currentTimeframe"><?php echo htmlspecialchars($params['timeframe'] ?? 'N/A'); ?></strong>
                </div>
                <div class="info-item">
                    <span>Updates:</span>
                    <strong id="updateCounter">0</strong>
                </div>
            </div>
        </div>
        
        <div class="error-message" id="errorMessage"></div>
        
        <div class="chart-frame">
            <img id="chartImage" src="<?php echo htmlspecialchars($chartUrl); ?>&_t=<?php echo time(); ?>" alt="Live Chart">
        </div>
    </div>

    <!-- Left Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-title">Chart Controls</div>
            <div style="font-size: 12px; color: rgba(255, 255, 255, 0.5);">Live Stream Settings</div>
        </div>
        
        <!-- Stats -->
        <div class="control-section">
            <div class="control-section-title">Statistics</div>
            <div class="stat-card">
                <div class="stat-label">Total Updates</div>
                <div class="stat-value" id="sidebarUpdateCounter">0</div>
            </div>
        </div>
        
        <!-- Chart Settings -->
        <div class="control-section">
            <div class="control-section-title">Chart Settings</div>
            <div style="margin-bottom: 12px;">
                <label style="font-size: 12px; color: rgba(255, 255, 255, 0.7); display: block; margin-bottom: 6px;">Symbol</label>
                <select id="symbolSelect">
                    <option value="EURUSD" <?php echo ($params['symbol'] ?? '') === 'EURUSD' ? 'selected' : ''; ?>>EURUSD</option>
                    <option value="GBPUSD" <?php echo ($params['symbol'] ?? '') === 'GBPUSD' ? 'selected' : ''; ?>>GBPUSD</option>
                    <option value="USDJPY" <?php echo ($params['symbol'] ?? '') === 'USDJPY' ? 'selected' : ''; ?>>USDJPY</option>
                    <option value="AUDUSD" <?php echo ($params['symbol'] ?? '') === 'AUDUSD' ? 'selected' : ''; ?>>AUDUSD</option>
                    <option value="USDCAD" <?php echo ($params['symbol'] ?? '') === 'USDCAD' ? 'selected' : ''; ?>>USDCAD</option>
                    <option value="NZDUSD" <?php echo ($params['symbol'] ?? '') === 'NZDUSD' ? 'selected' : ''; ?>>NZDUSD</option>
                    <option value="USDCHF" <?php echo ($params['symbol'] ?? '') === 'USDCHF' ? 'selected' : ''; ?>>USDCHF</option>
                    <option value="EURJPY" <?php echo ($params['symbol'] ?? '') === 'EURJPY' ? 'selected' : ''; ?>>EURJPY</option>
                    <option value="GBPJPY" <?php echo ($params['symbol'] ?? '') === 'GBPJPY' ? 'selected' : ''; ?>>GBPJPY</option>
                    <option value="EURGBP" <?php echo ($params['symbol'] ?? '') === 'EURGBP' ? 'selected' : ''; ?>>EURGBP</option>
                    <option value="XAUUSD" <?php echo ($params['symbol'] ?? '') === 'XAUUSD' ? 'selected' : ''; ?>>XAUUSD (Gold)</option>
                    <option value="BTCUSD" <?php echo ($params['symbol'] ?? '') === 'BTCUSD' ? 'selected' : ''; ?>>BTCUSD</option>
                </select>
            </div>
            
            <div>
                <label style="font-size: 12px; color: rgba(255, 255, 255, 0.7); display: block; margin-bottom: 6px;">Timeframe</label>
                <select id="timeframeSelect">
                    <option value="M1" <?php echo ($params['timeframe'] ?? '') === 'M1' ? 'selected' : ''; ?>>M1 (1 min)</option>
                    <option value="M5" <?php echo ($params['timeframe'] ?? '') === 'M5' ? 'selected' : ''; ?>>M5 (5 min)</option>
                    <option value="M15" <?php echo ($params['timeframe'] ?? '') === 'M15' ? 'selected' : ''; ?>>M15 (15 min)</option>
                    <option value="M30" <?php echo ($params['timeframe'] ?? '') === 'M30' ? 'selected' : ''; ?>>M30 (30 min)</option>
                    <option value="H1" <?php echo ($params['timeframe'] ?? '') === 'H1' ? 'selected' : ''; ?>>H1 (1 hour)</option>
                    <option value="H4" <?php echo ($params['timeframe'] ?? '') === 'H4' ? 'selected' : ''; ?>>H4 (4 hours)</option>
                    <option value="D1" <?php echo ($params['timeframe'] ?? '') === 'D1' ? 'selected' : ''; ?>>D1 (1 day)</option>
                </select>
            </div>
        </div>
        
        <!-- Stream Controls -->
        <div class="control-section">
            <div class="control-section-title">Stream Controls</div>
            <button class="btn active" id="toggleStream" style="margin-bottom: 10px;">
                <i data-feather="pause" style="width: 16px; height: 16px;"></i>
                <span id="streamStatus">Pause</span>
            </button>
            <button class="btn" id="refreshNow">
                <i data-feather="refresh-cw" style="width: 16px; height: 16px;"></i>
                Refresh Now
            </button>
        </div>
    </div>

    <script>
        let updateCount = 0;
        let isStreaming = true;
        let streamInterval = null;
        let currentSymbol = '<?php echo addslashes($params['symbol'] ?? 'EURUSD'); ?>';
        let currentTimeframe = '<?php echo addslashes($params['timeframe'] ?? 'H1'); ?>';
        const chartImage = document.getElementById('chartImage');
        const updateCounter = document.getElementById('updateCounter');
        const sidebarUpdateCounter = document.getElementById('sidebarUpdateCounter');
        const toggleBtn = document.getElementById('toggleStream');
        const streamStatus = document.getElementById('streamStatus');
        const refreshBtn = document.getElementById('refreshNow');
        const symbolSelect = document.getElementById('symbolSelect');
        const timeframeSelect = document.getElementById('timeframeSelect');
        const currentSymbolDisplay = document.getElementById('currentSymbol');
        const currentTimeframeDisplay = document.getElementById('currentTimeframe');
        const errorMessage = document.getElementById('errorMessage');
        const hamburger = document.getElementById('hamburger');
        const sidebar = document.getElementById('sidebar');
        const baseUrl = '<?php echo addslashes($chartUrl); ?>';
        
        // Hamburger menu toggle
        hamburger.addEventListener('click', function() {
            this.classList.toggle('active');
            sidebar.classList.toggle('mobile-hidden');
            sidebar.classList.toggle('mobile-visible');
        });
        
        // Build chart URL with current symbol and timeframe
        function buildChartUrl() {
            const urlParams = new URLSearchParams(baseUrl.split('?')[1]);
            urlParams.set('symbol', currentSymbol);
            urlParams.set('timeframe', currentTimeframe);
            return baseUrl.split('?')[0] + '?' + urlParams.toString();
        }
        
        function updateChart() {
            const timestamp = new Date().getTime();
            const chartBaseUrl = buildChartUrl();
            const newSrc = chartBaseUrl + '&_t=' + timestamp;
            
            // Create a new image to preload
            let tempImg = new Image();
            tempImg.onload = function() {
                chartImage.src = newSrc;
                updateCount++;
                updateCounter.textContent = updateCount;
                sidebarUpdateCounter.textContent = updateCount;
                hideError();
                // Clean up to prevent memory leaks
                tempImg.onload = null;
                tempImg.onerror = null;
                tempImg = null;
            };
            tempImg.onerror = function() {
                showError('Failed to load chart image');
                // Clean up even on error
                tempImg.onload = null;
                tempImg.onerror = null;
                tempImg = null;
            };
            tempImg.src = newSrc;
        }
        
        function startStream() {
            if (streamInterval) clearInterval(streamInterval);
            streamInterval = setInterval(updateChart, 500);
            isStreaming = true;
            toggleBtn.classList.add('active');
            toggleBtn.querySelector('i').setAttribute('data-feather', 'pause');
            streamStatus.textContent = 'Pause';
            feather.replace();
        }
        
        function stopStream() {
            if (streamInterval) {
                clearInterval(streamInterval);
                streamInterval = null;
            }
            isStreaming = false;
            toggleBtn.classList.remove('active');
            toggleBtn.querySelector('i').setAttribute('data-feather', 'play');
            streamStatus.textContent = 'Resume';
            feather.replace();
        }
        
        function showError(message) {
            errorMessage.textContent = message;
            errorMessage.style.display = 'block';
        }
        
        function hideError() {
            errorMessage.style.display = 'none';
        }
        
        // Symbol change handler
        symbolSelect.addEventListener('change', function() {
            currentSymbol = this.value;
            currentSymbolDisplay.textContent = currentSymbol;
            updateCount = 0;
            updateCounter.textContent = updateCount;
            sidebarUpdateCounter.textContent = updateCount;
            updateChart();
        });
        
        // Timeframe change handler
        timeframeSelect.addEventListener('change', function() {
            currentTimeframe = this.value;
            currentTimeframeDisplay.textContent = currentTimeframe;
            updateCount = 0;
            updateCounter.textContent = updateCount;
            sidebarUpdateCounter.textContent = updateCount;
            updateChart();
        });
        
        // Toggle stream
        toggleBtn.addEventListener('click', function() {
            if (isStreaming) {
                stopStream();
            } else {
                startStream();
            }
        });
        
        // Refresh now
        refreshBtn.addEventListener('click', function() {
            updateChart();
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === ' ') { // Space bar
                e.preventDefault();
                toggleBtn.click();
            } else if (e.key === 'r' || e.key === 'R') {
                e.preventDefault();
                refreshBtn.click();
            }
        });
        
        // Start streaming on load
        startStream();
        
        // Initialize Feather icons
        feather.replace();
        
        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            stopStream();
        });
    </script>
</body>
</html>
