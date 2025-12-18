
require_once 'password_protect.php';

// Include shared configuration
require_once '../app_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo APP_TITLE; ?> - MT5 Data API Guide v1.4 with Volume Parameters</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<!-- Logout Button -->
<a href="logout.php" class="btn btn-outline-danger btn-sm logout-btn">🔓 Logout</a>

<div class="container py-5">
  <p><a href="index.php" class="btn btn-outline-light mb-4">← Back to Home</a></p>
  <h1 class="mb-4">📈 <?php echo APP_TITLE; ?> MT5 Data API Guide <span class="badge badge-new">v1.4</span> <span class="badge badge-volume">Volume Parameters</span></h1>

  <div class="alert alert-success mb-4">
    <h5>🆕 What's New in v1.4</h5>
    <ul class="mb-0">
      <li><strong><span class="highlight-volume">Multiple Volume Parameters:</span></strong> Support for <code>candle-volume</code>, <code>candlevolume</code>, and <code>volume</code> parameters</li>
      <li><strong>Enhanced Volume Validation:</strong> Proper validation and error handling for all volume parameter formats</li>
      <li><strong>Fixed dataField Support:</strong> Corrected <code>dataField</code> parameter handling for single field output</li>
      <li><strong>Improved Parameter Detection:</strong> Automatic detection and forwarding of volume parameters to EA</li>
      <li><strong>Enhanced Debug Logging:</strong> Better logging for volume parameter processing and validation</li>
      <li><strong>Backward Compatible:</strong> All v1.3 calls work unchanged with enhanced volume support</li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-3">
      <div class="quick-nav">
        <h6>📋 Quick Navigation</h6>
        <a href="#authentication" class="nav-link">🔐 Authentication</a>
        <a href="#parameters" class="nav-link">🔧 Parameters</a>
        <a href="#volume-parameters" class="nav-link">📊 Volume Parameters</a>
        <a href="#basic-usage" class="nav-link">📊 Basic Usage</a>
        <a href="#direct-indicators" class="nav-link">🎯 Direct Indicators</a>
        <a href="#ma-enhanced" class="nav-link">📊 Enhanced MAs</a>
        <a href="#individual-examples" class="nav-link">🔢 Individual Examples</a>
        <a href="#combination-examples" class="nav-link">🔗 Combinations</a>
        <a href="#strategies" class="nav-link">📈 Strategies</a>
        <a href="#api-modes" class="nav-link">📋 API Modes</a>
        <a href="#responses" class="nav-link">📤 Responses</a>
      </div>
    </div>
    <div class="col-md-9">

  <div class="alert alert-info mb-4">
    <h5>🔗 API Endpoint</h5>
    <strong>Base URL:</strong><br>
    <code><?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php</code>
  </div>

  <div class="alert alert-info mb-4">
    <h5>🔑 Your API Key</h5>
    <strong>Default API Key:</strong><br>
    <code><?php echo DEFAULT_API_KEY; ?></code>
  </div>

  <p>This API enables on-demand retrieval of MetaTrader 5 ("MT5") chart data with comprehensive technical indicators via HTTP requests. 
  Version 1.4 introduces enhanced volume parameter support, improved dataField handling, and better parameter validation.</p>

  <h2 id="authentication" class="mt-5">🔐 Authentication</h2>
  <p>All requests require an API key parameter:</p>
  <div class="code-block">
<pre><?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=EURUSD&timeframe=M5&count=10&rsi=14&ema_1=e,20&candle-volume=true</pre>
  </div>

  <h2 id="parameters" class="mt-5">🔧 Core Parameters</h2>
  <table class="table table-dark table-bordered">
    <thead>
      <tr>
        <th>Parameter</th>
        <th>Type</th>
        <th>Required</th>
        <th>Description</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><span class="badge badge-custom">api_key</span></td>
        <td>string</td>
        <td>✅ Always</td>
        <td>Your unique API key for authentication and quota tracking.</td>
      </tr>
      <tr>
        <td>symbol</td>
        <td>string (e.g. "EURUSD")</td>
        <td>✅ Always</td>
        <td>Trading symbol to fetch candles for. Max 20 characters, alphanumeric with ._- allowed.</td>
      </tr>
      <tr>
        <td>timeframe</td>
        <td>string (M1, M5, M15, M30, H1, H4, D1, W1, MN1)</td>
        <td>✅ in legacy/<br>✅ in expanded</td>
        <td>Candle timeframe. In "legacy" mode, used with <code>count</code>. In "expanded" mode, optional if <code>rangeType</code> is present.</td>
      </tr>
      <tr>
        <td>count</td>
        <td>integer (1-5000)</td>
        <td>✅ in legacy/<br>✅ in expanded (if <code>rangeType=future</code>)</td>
        <td>Number of candles to return. Range: 1-5000.</td>
      </tr>
      <tr>
        <td>rangeType</td>
        <td>string</td>
        <td>❌ unless using expanded</td>
        <td><strong>Fixed ranges:</strong> "last-five-minutes", "last-hour", "last-6-hours", "today", "yesterday", "this-week", etc.<br>
            <strong><span class="highlight-new">Dynamic ranges:</span></strong> "last-X-minutes" where X is 1-1440<br>
            <strong>Special:</strong> "future" (requires count parameter)</td>
      </tr>
      <tr>
        <td>pretend_date</td>
        <td>string (YYYY-MM-DD)</td>
        <td>❌</td>
        <td>When present with <code>pretend_time</code>, treat that as "now" instead of actual server time.<br>
            Format must be <code>YYYY-MM-DD</code>. E.g. <code>2025-05-01</code>.</td>
      </tr>
      <tr>
        <td>pretend_time</td>
        <td>string (HH:MM)</td>
        <td>❌</td>
        <td>When present with <code>pretend_date</code>, treat that as "now" instead of actual server time.<br>
            Format must be <code>HH:MM</code> (24h). E.g. <code>10:30</code>.</td>
      </tr>
      <tr>
        <td>dataField</td>
        <td>string ("open", "high", "low", "close", "volume")</td>
        <td>❌</td>
        <td>Return only that field instead of full OHLC. If omitted, full OHLC is returned.<br>
            <strong>Note:</strong> When dataField=volume, returns tick volume values.</td>
      </tr>
    </tbody>
  </table>

  <h2 id="volume-parameters" class="mt-5">📊 Volume Parameters <span class="badge badge-volume">NEW v1.4</span></h2>
  <p>Version 1.4 introduces comprehensive volume parameter support with multiple parameter names and enhanced validation.</p>

  <div class="alert alert-warning mb-4">
    <h6><span class="highlight-volume">📊 Volume Parameter Options:</span></h6>
    <p><strong>Primary:</strong> <code>&candle-volume=true</code> (with hyphen)</p>
    <p><strong>Alternative:</strong> <code>&candlevolume=true</code> (without hyphen)</p>
    <p><strong>Short Form:</strong> <code>&volume=true</code> (concise)</p>
    <p><strong>Values:</strong> <code>true</code>, <code>false</code>, <code>1</code>, <code>0</code></p>
  </div>

  <div class="volume-example">
    <h6>📊 Volume Parameter Usage:</h6>
    <ul class="mb-0">
      <li><strong>Include Volume in Candles:</strong> <code>&candle-volume=true</code> - Adds volume field to each candle object</li>
      <li><strong>Volume Data Only:</strong> <code>&dataField=volume</code> - Returns only volume values as array</li>
      <li><strong>No Volume (Default):</strong> Omit parameter or use <code>&volume=false</code></li>
      <li><strong>Backward Compatibility:</strong> All three parameter names work identically</li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-6">
      <div class="indicator-card">
        <h6>📈 Volume Parameter Names</h6>
        <ul class="mb-0">
          <li><code>candle-volume</code> - Primary (with hyphen)</li>
          <li><code>candlevolume</code> - Alternative (no hyphen)</li>
          <li><code>volume</code> - Short form</li>
        </ul>
      </div>
    </div>
    <div class="col-md-6">
      <div class="indicator-card">
        <h6>🎯 Volume Parameter Values</h6>
        <ul class="mb-0">
          <li><code>true</code> - Enable tick volume</li>
          <li><code>false</code> - Disable tick volume</li>
          <li><code>1</code> - Enable (alternative)</li>
          <li><code>0</code> - Disable (alternative)</li>
        </ul>
      </div>
    </div>
  </div>

  <h2 id="basic-usage" class="mt-5">📊 Basic Usage (No Indicators)</h2>
  
  <div class="example-section">
    <h5>🔄 Legacy Mode - Basic Candle Data</h5>
    <div class="example-grid">
      <div class="example-item">
        <h6>📈 EURUSD M5 - Last 50 Candles</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=EURUSD&timeframe=M5&count=50" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📈 EURUSD M5 - With Volume</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=EURUSD&timeframe=M5&count=50&candle-volume=true" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📈 GBPUSD H1 - Volume Only</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=GBPUSD&timeframe=H1&count=100&dataField=volume" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📈 USDJPY D1 - Close Prices Only</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=USDJPY&timeframe=D1&count=30&dataField=close" target="_blank">View Example</a>
      </div>
    </div>
  </div>

  <div class="example-section">
    <h5>📊 Volume Parameter Examples</h5>
    <div class="example-grid">
      <div class="example-item">
        <h6>📊 Primary Volume Parameter - EURUSD</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=EURUSD&timeframe=M5&count=50&candle-volume=true" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Alternative Volume Parameter - GBPUSD</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=GBPUSD&timeframe=M5&count=50&candlevolume=true" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Short Volume Parameter - USDJPY</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=USDJPY&timeframe=M5&count=50&volume=true" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Volume Data Field - AUDUSD</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=AUDUSD&timeframe=M15&count=100&dataField=volume" target="_blank">View Example</a>
      </div>
    </div>
  </div>

  <div class="example-section">
    <h5>📅 Expanded Mode - Time-Based Ranges</h5>
    <div class="example-grid">
      <div class="example-item">
        <h6>🕐 Last Hour - EURUSD M5</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=EURUSD&rangeType=last-hour&timeframe=M5" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>🕐 Last 30 Minutes - GBPUSD M1 with Volume</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=GBPUSD&rangeType=last-30-minutes&timeframe=M1&volume=true" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📅 Today - USDJPY H1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=USDJPY&rangeType=today&timeframe=H1" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📅 This Week - AUDUSD D1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=AUDUSD&rangeType=this-week&timeframe=D1" target="_blank">View Example</a>
      </div>
    </div>
  </div>

  <div class="example-section">
    <h5>🔮 Future Mode & Backtesting</h5>
    <div class="example-grid">
      <div class="example-item">
        <h6>🔮 Next 24 Hours - EURUSD H1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=EURUSD&rangeType=future&timeframe=H1&count=24" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>⏰ Backtest - GBPUSD Historical</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=GBPUSD&timeframe=H1&count=50&pretend_date=2024-12-01&pretend_time=10:30" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Close Prices Only - USDJPY</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=USDJPY&timeframe=H1&count=100&dataField=close" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Volume Data Only - BTCUSD</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=BTCUSD&timeframe=M15&count=50&dataField=volume" target="_blank">View Example</a>
      </div>
    </div>
  </div>

  <h2 id="direct-indicators" class="mt-5">🎯 Direct Indicator Parameters <span class="badge badge-enhanced">v1.3+</span></h2>
  <p>Direct URL parameter support for indicators. Simply add indicators as URL parameters without any wrapper.</p>

  <div class="alert alert-warning mb-4">
    <h6><span class="highlight-enhanced">🎯 Direct Parameter Format:</span></h6>
    <p><strong>Simple:</strong> <code>&rsi=14&atr=14&bb=20,0,2.0</code></p>
    <p><strong>Multiple of Same Type:</strong> <code>&ema_1=20&ema_2=50&rsi1=14&rsi2=9</code></p>
    <p><strong>Enhanced MAs:</strong> <code>&ma_1=e,20&ma_2=s,50&ema_1=20</code></p>
    <p><strong>With Volume:</strong> <code>&rsi=14&ema_1=e,20&bb=20,0,2.0&candle-volume=true</code></p>
  </div>

  <h2 id="ma-enhanced" class="mt-5">📊 Enhanced Moving Average Support <span class="badge badge-enhanced">v1.3+</span></h2>
  
  <div class="ma-example">
    <h6>🔧 MA Parameter Formats:</h6>
    <ul class="mb-0">
      <li><strong>Type + Period:</strong> <code>&ma_1=e,20</code> (EMA with period 20)</li>
      <li><strong>Period Only:</strong> <code>&ma_2=50</code> (SMA with period 50 - default)</li>
      <li><strong>Specific Types:</strong> <code>&ema_1=20&sma_1=50&lwma_1=l,100</code></li>
      <li><strong>Multiple MAs:</strong> <code>&ma_1=e,20&ma_2=s,50&ma_3=l,100</code></li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-6">
      <div class="indicator-card">
        <h6>📈 MA Types Available</h6>
        <ul class="mb-0">
          <li><code>e</code> = EMA (Exponential)</li>
          <li><code>s</code> = SMA (Simple) - default</li>
          <li><code>sm</code> = SMMA (Smoothed)</li>
          <li><code>l</code> = LWMA (Linear Weighted)</li>
        </ul>
      </div>
    </div>
    <div class="col-md-6">
      <div class="indicator-card">
        <h6>🎯 MA Examples</h6>
        <ul class="mb-0">
          <li><code>&ma_1=e,20</code> - EMA 20</li>
          <li><code>&ma_2=50</code> - SMA 50</li>
          <li><code>&ema_1=20</code> - EMA 20</li>
          <li><code>&sma_1=s,50</code> - SMA 50</li>
        </ul>
      </div>
    </div>
  </div>

  <h2 id="individual-examples" class="mt-5">🔢 Individual Indicator Examples</h2>

  <div class="example-section">
    <h5>📈 Moving Averages - All Types</h5>
    <div class="example-grid">
      <div class="example-item">
        <h6>📊 SMA 20 - EURUSD H1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=EURUSD&timeframe=H1&count=100&sma_1=20" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 EMA 20 with Volume - GBPUSD H1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=GBPUSD&timeframe=H1&count=100&ema_1=20&candlevolume=true" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 SMMA 20 - USDJPY H1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=USDJPY&timeframe=H1&count=100&smma_1=20" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 LWMA 20 - AUDUSD H1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=AUDUSD&timeframe=H1&count=100&lwma_1=20" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Enhanced MA Format - USDCAD</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=USDCAD&timeframe=H1&count=100&ma_1=e,20" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Multiple MAs with Volume - NZDUSD</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=NZDUSD&timeframe=H1&count=100&ma_1=e,20&ma_2=s,50&volume=true" target="_blank">View Example</a>
      </div>
    </div>
  </div>

  <div class="example-section">
    <h5>🌊 Oscillators - Individual Examples</h5>
    <div class="example-grid">
      <div class="example-item">
        <h6>📊 RSI 14 - EURUSD H1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=EURUSD&timeframe=H1&count=100&rsi=14" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 RSI 9 (Fast) with Volume - GBPUSD M15</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=GBPUSD&timeframe=M15&count=100&rsi=9&candle-volume=true" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Stochastic 14,3,3 - USDJPY H1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=USDJPY&timeframe=H1&count=100&stoch=14,3,3" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Stochastic 5,3,3 (Fast) - AUDUSD M5</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=AUDUSD&timeframe=M5&count=200&stoch=5,3,3" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 CCI 14 - USDCAD H1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=USDCAD&timeframe=H1&count=100&cci=14" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Williams %R 14 - NZDUSD H1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=NZDUSD&timeframe=H1&count=100&wpr=14" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 MFI 14 - EURGBP H1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=EURGBP&timeframe=H1&count=100&mfi=14" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Momentum 14 - EURJPY H1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=EURJPY&timeframe=H1&count=100&momentum=14" target="_blank">View Example</a>
      </div>
    </div>
  </div>

  <div class="example-section">
    <h5>📏 Volatility Indicators - Individual Examples</h5>
    <div class="example-grid">
      <div class="example-item">
        <h6>📊 Bollinger Bands 20,2.0 - EURUSD H1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=EURUSD&timeframe=H1&count=100&bb=20,0,2.0" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Bollinger Bands 20,1.5 (Tight) - GBPUSD M15</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=GBPUSD&timeframe=M15&count=100&bb=20,0,1.5" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 ATR 14 - USDJPY H1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=USDJPY&timeframe=H1&count=100&atr=14" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 ATR 7 (Fast) - AUDUSD M5</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=AUDUSD&timeframe=M5&count=200&atr=7" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Standard Deviation 20 - USDCAD H1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=USDCAD&timeframe=H1&count=100&stddev=20" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Envelopes 14,0.1 - NZDUSD H1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=NZDUSD&timeframe=H1&count=100&envelopes=14,0.1" target="_blank">View Example</a>
      </div>
    </div>
  </div>

  <div class="example-section">
    <h5>📊 Trend Indicators - Individual Examples</h5>
    <div class="example-grid">
      <div class="example-item">
        <h6>📊 Parabolic SAR 0.02,0.2 - EURUSD H1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=EURUSD&timeframe=H1&count=100&sar=0.02,0.2" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Parabolic SAR 0.01,0.1 (Sensitive) - GBPUSD M15</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=GBPUSD&timeframe=M15&count=100&sar=0.01,0.1" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Ichimoku 9,26,52 - USDJPY H4</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=USDJPY&timeframe=H4&count=200&ichimoku=9,26,52" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Alligator 13,8,5 - AUDUSD H1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=AUDUSD&timeframe=H1&count=100&alligator=13,8,5" target="_blank">View Example</a>
      </div>
    </div>
  </div>

  <div class="example-section">
    <h5>📈 Volume & Bill Williams - Individual Examples</h5>
    <div class="example-grid">
      <div class="example-item">
        <h6>📊 On Balance Volume - BTCUSD M15</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=BTCUSD&timeframe=M15&count=100&obv=0" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 OBV with Volume Data - ETHUSD H1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=ETHUSD&timeframe=H1&count=100&obv=0&candle-volume=true" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Awesome Oscillator - EURUSD H1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=EURUSD&timeframe=H1&count=100&ao" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Accelerator Oscillator - GBPUSD H1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=GBPUSD&timeframe=H1&count=100&ac" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Fractals - USDJPY H4</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=USDJPY&timeframe=H4&count=100&fractals" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 DeMarker 14 - AUDUSD H1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=AUDUSD&timeframe=H1&count=100&demarker=14" target="_blank">View Example</a>
      </div>
    </div>
  </div>

  <h2 id="combination-examples" class="mt-5">🔗 Indicator Combination Examples</h2>

  <div class="example-section">
    <h5>📈 Moving Average Combinations with Volume</h5>
    <div class="example-grid">
      <div class="example-item">
        <h6>📊 Triple EMA (20,50,200) with Volume - EURUSD H1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=EURUSD&timeframe=H1&count=200&ema_1=20&ema_2=50&ema_3=200&candle-volume=true" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Mixed MA Types (EMA20, SMA50, LWMA100) - GBPUSD</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=GBPUSD&timeframe=H1&count=200&ma_1=e,20&ma_2=s,50&ma_3=l,100" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Enhanced MA Format Mix with Volume - USDJPY</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=USDJPY&timeframe=H1&count=150&ma_1=e,20&ema_2=50&sma_1=200&volume=true" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Scalping MAs (8,21,50) - AUDUSD M5</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=AUDUSD&timeframe=M5&count=300&ema_1=8&ema_2=21&ema_3=50" target="_blank">View Example</a>
      </div>
    </div>
  </div>

  <div class="example-section">
    <h5>🌊 Multiple Oscillator Combinations</h5>
    <div class="example-grid">
      <div class="example-item">
        <h6>📊 RSI + Stochastic - EURUSD H1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=EURUSD&timeframe=H1&count=100&rsi=14&stoch=14,3,3" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Multiple RSI Periods (9,14,21) - GBPUSD</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=GBPUSD&timeframe=H1&count=100&rsi=9&rsi1=14&rsi2=21" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Oscillator Suite (RSI,CCI,WPR,MFI) - USDJPY</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=USDJPY&timeframe=H1&count=100&rsi=14&cci=14&wpr=14&mfi=14" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Fast Oscillators with Volume - AUDUSD M15</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=AUDUSD&timeframe=M15&count=200&rsi=9&stoch=5,3,3&momentum=10&candlevolume=true" target="_blank">View Example</a>
      </div>
    </div>
  </div>

  <div class="example-section">
    <h5>📏 Volatility Combinations</h5>
    <div class="example-grid">
      <div class="example-item">
        <h6>📊 Multiple Bollinger Bands (2.0, 1.5) - EURUSD</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=EURUSD&timeframe=H1&count=100&bb=20,0,2.0&bb1=20,0,1.5" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Multiple ATR Periods (7,14,21) - GBPUSD</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=GBPUSD&timeframe=H1&count=100&atr=7&atr1=14&atr2=21" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Volatility Suite (BB,ATR,StdDev) - USDJPY</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=USDJPY&timeframe=H1&count=100&bb=20,0,2.0&atr=14&stddev=20" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 BB + Envelopes with Volume - AUDUSD</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=AUDUSD&timeframe=H1&count=100&bb=20,0,2.0&envelopes=14,0.1&volume=true" target="_blank">View Example</a>
      </div>
    </div>
  </div>

  <div class="example-section">
    <h5>📊 Trend Analysis Combinations</h5>
    <div class="example-grid">
      <div class="example-item">
        <h6>📊 SAR + Ichimoku - EURUSD H4</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=EURUSD&timeframe=H4&count=200&sar=0.02,0.2&ichimoku=9,26,52" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Multiple SAR Settings - GBPUSD</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=GBPUSD&timeframe=H1&count=100&sar=0.02,0.2&sar1=0.01,0.1" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Alligator + Fractals - USDJPY H1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=USDJPY&timeframe=H1&count=100&alligator=13,8,5&fractals" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Bill Williams Suite (AO,AC,Alligator) - AUDUSD</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=AUDUSD&timeframe=H1&count=100&ao&ac&alligator=13,8,5" target="_blank">View Example</a>
      </div>
    </div>
  </div>

  <div class="example-section">
    <h5>📈 Volume Analysis Combinations</h5>
    <div class="example-grid">
      <div class="example-item">
        <h6>📊 OBV + Volume Data - BTCUSD M15</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=BTCUSD&timeframe=M15&count=100&obv=0&candle-volume=true" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Volume + MFI + RSI - ETHUSD H1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=ETHUSD&timeframe=H1&count=100&mfi=14&rsi=14&candlevolume=true" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Complete Volume Analysis - SOLUSD H1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=SOLUSD&timeframe=H1&count=100&obv=0&mfi=14&ao&volume=true" target="_blank">View Example</a>
      </div>
      <div class="example-item">
        <h6>📊 Crypto Volume + Volatility - ADAUSD M30</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=ADAUSD&timeframe=M30&count=200&atr=14&bb=20,0,2.0&candle-volume=true" target="_blank">View Example</a>
      </div>
    </div>
  </div>

  <h2 id="strategies" class="mt-5">📈 Complete Trading Strategy Examples</h2>

  <div class="example-section">
    <h5>🎯 Trend Following Strategies with Volume</h5>
    <div class="example-grid">
      <div class="example-item">
        <h6>🔥 Golden Cross Strategy - EURUSD H1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=EURUSD&timeframe=H1&count=200&ema_1=20&ema_2=50&ema_3=200&atr=14&candle-volume=true" target="_blank">View Strategy</a>
        <small class="text-muted d-block">EMA 20>50>200 alignment + ATR for stops + Volume confirmation</small>
      </div>
      <div class="example-item">
        <h6>🎯 Triple MA Trend - GBPUSD H4</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=GBPUSD&timeframe=H4&count=200&sma_1=20&sma_2=50&sma_3=200&atr=14&rsi=14" target="_blank">View Strategy</a>
        <small class="text-muted d-block">SMA trend alignment + RSI filter + ATR position sizing</small>
      </div>
      <div class="example-item">
        <h6>📊 Ichimoku Complete - USDJPY H4</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=USDJPY&timeframe=H4&count=200&ichimoku=9,26,52&atr=14&rsi=14" target="_blank">View Strategy</a>
        <small class="text-muted d-block">Full Ichimoku analysis + RSI momentum + ATR volatility</small>
      </div>
      <div class="example-item">
        <h6>🎯 SAR Trend Following with Volume - AUDUSD H1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=AUDUSD&timeframe=H1&count=150&sar=0.02,0.2&ema_1=21&atr=14&rsi=14&volume=true" target="_blank">View Strategy</a>
        <small class="text-muted d-block">SAR trend signals + EMA filter + RSI confirmation + Volume</small>
      </div>
    </div>
  </div>

  <div class="example-section">
    <h5>🌊 Mean Reversion Strategies</h5>
    <div class="example-grid">
      <div class="example-item">
        <h6>⚡ RSI Divergence Setup with Volume - EURUSD M15</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=EURUSD&timeframe=M15&count=200&rsi=14&rsi1=9&bb=20,0,2.0&momentum=14&atr=14&candlevolume=true" target="_blank">View Strategy</a>
        <small class="text-muted d-block">Dual RSI divergence + BB extremes + Momentum + Volume confirmation</small>
      </div>
      <div class="example-item">
        <h6>📊 BB Mean Reversion - GBPUSD M30</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=GBPUSD&timeframe=M30&count=150&bb=20,0,2.0&bb1=20,0,1.5&rsi=14&stoch=14,3,3" target="_blank">View Strategy</a>
        <small class="text-muted d-block">Dual BB levels + RSI oversold/bought + Stochastic timing</small>
      </div>
      <div class="example-item">
        <h6>🎯 Multi-Oscillator Reversal - USDJPY H1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=USDJPY&timeframe=H1&count=100&rsi=14&cci=14&wpr=14&bb=20,0,2.0&atr=14" target="_blank">View Strategy</a>
        <small class="text-muted d-block">Triple oscillator confluence + BB boundaries + ATR stops</small>
      </div>
      <div class="example-item">
        <h6>📈 Stochastic Mean Reversion - AUDUSD M15</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=AUDUSD&timeframe=M15&count=200&stoch=14,3,3&stoch1=5,3,3&bb=20,0,2.0&sma_1=50" target="_blank">View Strategy</a>
        <small class="text-muted d-block">Dual Stochastic + BB + SMA 50 trend filter</small>
      </div>
    </div>
  </div>

  <div class="example-section">
    <h5>💥 Breakout Strategies with Volume</h5>
    <div class="example-grid">
      <div class="example-item">
        <h6>🚀 Volatility Breakout with Volume - EURUSD M5</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=EURUSD&timeframe=M5&count=300&bb=20,0,2.0&bb1=20,0,1.5&atr=14&atr1=7&momentum=10&candle-volume=true" target="_blank">View Strategy</a>
        <small class="text-muted d-block">Dual BB breakout + ATR expansion + Volume spike + Momentum</small>
      </div>
      <div class="example-item">
        <h6>⚡ Range Breakout with Volume - GBPUSD M15</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=GBPUSD&timeframe=M15&count=200&bb=20,0,2.0&atr=14&rsi=14&ema_1=21&volume=true" target="_blank">View Strategy</a>
        <small class="text-muted d-block">BB breakout + ATR confirmation + RSI non-extreme + Volume</small>
      </div>
      <div class="example-item">
        <h6>🎯 Momentum Breakout - USDJPY M5</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=USDJPY&timeframe=M5&count=300&momentum=10&momentum1=14&atr=7&bb=20,0,1.5&rsi=9" target="_blank">View Strategy</a>
        <small class="text-muted d-block">Dual momentum + Fast ATR + Tight BB + Fast RSI</small>
      </div>
      <div class="example-item">
        <h6>📊 News Breakout Setup with Volume - AUDUSD M1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=AUDUSD&timeframe=M1&count=500&atr=5&atr1=14&bb=20,0,1.0&momentum=5&candlevolume=true" target="_blank">View Strategy</a>
        <small class="text-muted d-block">Ultra-fast indicators for news events + Volume confirmation</small>
      </div>
    </div>
  </div>

  <div class="example-section">
    <h5>⚡ Scalping Strategies with Volume</h5>
    <div class="example-grid">
      <div class="example-item">
        <h6>🔥 EMA Scalping with Volume - EURUSD M1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=EURUSD&timeframe=M1&count=500&ema_1=8&ema_2=21&rsi=9&bb=20,0,1.5&atr=7&candle-volume=true" target="_blank">View Strategy</a>
        <small class="text-muted d-block">Fast EMA cross + RSI 9 + Tight BB + Fast ATR + Volume</small>
      </div>
      <div class="example-item">
        <h6>⚡ Stochastic Scalping - GBPUSD M5</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=GBPUSD&timeframe=M5&count=300&stoch=5,3,3&ema_1=13&bb=20,0,1.5&atr=7&momentum=10" target="_blank">View Strategy</a>
        <small class="text-muted d-block">Fast Stochastic + EMA 13 + Tight BB + Quick momentum</small>
      </div>
      <div class="example-item">
        <h6>🎯 Ultra-Fast Scalping - USDJPY M1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=USDJPY&timeframe=M1&count=500&ema_1=5&rsi=7&momentum=5&atr=5&bb=10,0,1.0" target="_blank">View Strategy</a>
        <small class="text-muted d-block">Ultra-fast settings for high-frequency scalping</small>
      </div>
      <div class="example-item">
        <h6>📊 Volume Scalping - AUDUSD M5</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=AUDUSD&timeframe=M5&count=300&ema_1=8&ema_2=21&atr=7&rsi=9&momentum=10&volume=true" target="_blank">View Strategy</a>
        <small class="text-muted d-block">EMA scalping + Volume confirmation + Fast indicators</small>
      </div>
    </div>
  </div>

  <div class="example-section">
    <h5>📈 Crypto Trading Strategies with Volume</h5>
    <div class="example-grid">
      <div class="example-item">
        <h6>₿ Bitcoin Trend Analysis with Volume - BTCUSD H1</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=BTCUSD&timeframe=H1&count=200&ema_1=20&ema_2=50&rsi=14&bb=20,0,2.0&atr=14&candle-volume=true" target="_blank">View Strategy</a>
        <small class="text-muted d-block">EMA trend + RSI momentum + BB volatility + Volume analysis</small>
      </div>
      <div class="example-item">
        <h6>⚡ Ethereum Scalping with Volume - ETHUSD M15</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=ETHUSD&timeframe=M15&count=200&ema_1=8&ema_2=21&rsi=9&stoch=5,3,3&atr=7&candlevolume=true" target="_blank">View Strategy</a>
        <small class="text-muted d-block">Fast crypto scalping with volume confirmation</small>
      </div>
      <div class="example-item">
        <h6>🚀 Altcoin Momentum with Volume - SOLUSD M30</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=SOLUSD&timeframe=M30&count=150&rsi=14&momentum=14&bb=20,0,2.0&atr=14&obv=0&volume=true" target="_blank">View Strategy</a>
        <small class="text-muted d-block">Altcoin momentum with dual volume indicators</small>
      </div>
      <div class="example-item">
        <h6>💎 Multi-Crypto Analysis with Volume - ADAUSD H4</h6>
        <a href="<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=ADAUSD&timeframe=H4&count=200&sma_1=50&sma_2=200&rsi=14&atr=14&mfi=14&candle-volume=true" target="_blank">View Strategy</a>
        <small class="text-muted d-block">Long-term crypto trend with volume and money flow</small>
      </div>
    </div>
  </div>

  <h2 id="api-modes" class="mt-5">📋 API Operation Modes</h2>
  
  <h5>🔄 1. Legacy Mode</h5>
  <div class="code-block">
<pre># Basic legacy mode
<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=EURUSD&timeframe=M15&count=50

# Legacy with indicators and volume
<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=EURUSD&timeframe=M15&count=50&sma_1=20&sma_2=50&ema_1=e,20&rsi=14&candle-volume=true

# Legacy with backtesting and volume
<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=GBPUSD&timeframe=H1&count=100&pretend_date=2025-05-01&pretend_time=10:30&bb=20,0,2.0&atr=14&volume=true</pre>
  </div>

  <h5 class="mt-4">📅 2. Expanded Mode</h5>
  <div class="code-block">
<pre># Time-based ranges
<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=EURUSD&rangeType=today&timeframe=M30

# Dynamic minute ranges with volume
<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=GBPUSD&rangeType=last-120-minutes&timeframe=M5&candlevolume=true

# Expanded with indicators and volume
<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=USDJPY&rangeType=last-4-hours&timeframe=M15&ema_1=e,20&ema_2=e,50&bb=20,0,2.0&rsi=14&stoch=14,3,3&atr=14&candle-volume=true</pre>
  </div>

  <h5 class="mt-4">🔮 3. Future Mode</h5>
  <div class="code-block">
<pre># Future bars
<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=EURUSD&rangeType=future&timeframe=H1&count=24

# Future with indicators and volume
<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=GBPUSD&rangeType=future&timeframe=H1&count=20&sma_1=50&sma_2=200&atr=14&rsi=14&volume=true</pre>
  </div>

  <h5 class="mt-4">📊 4. Data Field Mode</h5>
  <div class="code-block">
<pre># Single field output
<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=EURUSD&timeframe=H1&count=100&dataField=close

# Volume data only
<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=BTCUSD&timeframe=M15&count=50&dataField=volume

# High prices with indicators
<?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=GBPUSD&timeframe=H1&count=100&dataField=high&rsi=14&ema_1=20</pre>
  </div>

  <h2 id="responses" class="mt-5">📤 Response Formats</h2>
  
  <h5>✅ Success Response with Indicators and Volume</h5>
  <div class="code-block">
<pre>{
  "vestor_data": {
    "request_id": "req_683f5ac7a61a89.30914087",
    "symbol": "EURUSD",
    "rangeType": "last-30-minutes",
    "timeframe": "M5",
    "candles": [
      {
        "time": "Mon 2025.01.27 10:35",
        "open": 1.13203,
        "high": 1.13279,
        "low": 1.13199,
        "close": 1.13255,
        "volume": 1250,
        "rsi": 65.42,
        "ema_1": 1.13240,
        "ema_2": 1.13220,
        "sma_1": 1.13226,
        "bb": {
          "upper": 1.13340,
          "middle": 1.13225,
          "lower": 1.13110
        },
        "atr": 0.00045,
        "stoch": {
          "k": 75.3,
          "d": 72.1
        }
      }
    ],
    "currentPrice": 1.13260,
    "request_metadata": {
      "symbol": "EURUSD",
      "rangeType": "last-30-minutes",
      "timeframe": "M5",
      "indicators_requested": ["rsi", "ema_1", "ema_2", "sma_1", "bb", "atr", "stoch"],
      "indicator_count": 7,
      "volume_parameter": "candle-volume",
      "volume_enabled": true,
      "requested_at": "2025-01-27 14:30:15",
      "processing_time_ms": 245
    }
  }
}</pre>
  </div>

  <h5 class="mt-4">📊 Data Field Response (Volume Only)</h5>
  <div class="code-block">
<pre>{
  "vestor_data": {
    "request_id": "req_683f5ac7a61a89.30914087",
    "symbol": "BTCUSD",
    "timeframe": "M15",
    "count": 50,
    "dataField": "volume",
    "data": [1250, 1340, 890, 2100, 1560, 980, 1780, ...],
    "request_metadata": {
      "symbol": "BTCUSD",
      "timeframe": "M15",
      "count": 50,
      "dataField": "volume",
      "mode": "legacy",
      "requested_at": "2025-01-27 14:30:15",
      "processing_time_ms": 180
    }
  }
}</pre>
  </div>

  <h2 class="mt-5">🔧 Supported Indicators Reference</h2>
  <div class="row">
    <div class="col-md-6">
      <div class="indicator-card">
        <h6>📈 Moving Averages</h6>
        <ul class="mb-0">
          <li><code>ma_X</code> - Moving Average (type,period or period)</li>
          <li><code>ema_X</code> - Exponential MA (period)</li>
          <li><code>sma_X</code> - Simple MA (period)</li>
          <li><code>smma_X</code> - Smoothed MA (period)</li>
          <li><code>lwma_X</code> - Linear Weighted MA (period)</li>
        </ul>
      </div>
    </div>
    <div class="col-md-6">
      <div class="indicator-card">
        <h6>🌊 Oscillators</h6>
        <ul class="mb-0">
          <li><code>rsi</code> - RSI (period)</li>
          <li><code>stoch</code> - Stochastic (k,d,slowing)</li>
          <li><code>cci</code> - CCI (period)</li>
          <li><code>wpr</code> - Williams %R (period)</li>
          <li><code>mfi</code> - Money Flow Index (period)</li>
          <li><code>momentum</code> - Momentum (period)</li>
        </ul>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-6">
      <div class="indicator-card">
        <h6>📊 Trend Indicators</h6>
        <ul class="mb-0">
          <li><code>sar</code> - Parabolic SAR (step,maximum)</li>
          <li><code>ichimoku</code> - Ichimoku (tenkan,kijun,senkou)</li>
          <li><code>alligator</code> - Alligator (jaw,teeth,lips)</li>
        </ul>
      </div>
    </div>
    <div class="col-md-6">
      <div class="indicator-card">
        <h6>📏 Volatility</h6>
        <ul class="mb-0">
          <li><code>bb</code> - Bollinger Bands (period,shift,deviation)</li>
          <li><code>atr</code> - ATR (period)</li>
          <li><code>envelopes</code> - Envelopes (period,deviation)</li>
          <li><code>stddev</code> - Standard Deviation (period)</li>
        </ul>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-6">
      <div class="indicator-card">
        <h6>📈 Volume & Bill Williams</h6>
        <ul class="mb-0">
          <li><code>obv</code> - On Balance Volume</li>
          <li><code>ac</code> - Accelerator Oscillator</li>
          <li><code>ao</code> - Awesome Oscillator</li>
          <li><code>fractals</code> - Fractals</li>
        </ul>
      </div>
    </div>
    <div class="col-md-6">
      <div class="indicator-card">
        <h6>🔧 Other</h6>
        <ul class="mb-0">
          <li><code>demarker</code> - DeMarker (period)</li>
        </ul>
      </div>
    </div>
  </div>

  <h2 class="mt-5">📊 Volume Parameter Reference <span class="badge badge-volume">v1.4</span></h2>
  <div class="row">
    <div class="col-md-6">
      <div class="indicator-card">
        <h6>📈 Volume Parameter Names</h6>
        <ul class="mb-0">
          <li><code>candle-volume</code> - Primary parameter (with hyphen)</li>
          <li><code>candlevolume</code> - Alternative (without hyphen)</li>
          <li><code>volume</code> - Short form parameter</li>
        </ul>
      </div>
    </div>
    <div class="col-md-6">
      <div class="indicator-card">
        <h6>🎯 Volume Parameter Usage</h6>
        <ul class="mb-0">
          <li><code>=true</code> or <code>=1</code> - Enable tick volume in candles</li>
          <li><code>=false</code> or <code>=0</code> - Disable tick volume</li>
          <li><code>dataField=volume</code> - Return volume data only</li>
        </ul>
      </div>
    </div>
  </div>

  <h2 class="mt-5">✅ Best Practices</h2>
  <div class="alert alert-info">
    <ul class="mb-0">
      <li><strong>Volume Parameters:</strong> Use any of the three volume parameter names - they work identically</li>
      <li><strong>Data Field vs Volume Parameter:</strong> Use <code>dataField=volume</code> for volume-only arrays, <code>candle-volume=true</code> to add volume to candle objects</li>
      <li><strong>Direct Parameters:</strong> Use direct URL parameters for better performance: <code>&rsi=14&ema_1=e,20</code></li>
      <li><strong>Multiple Indicators:</strong> Use numbered suffixes for multiple of same type: <code>&rsi=14&rsi1=9&rsi2=21</code></li>
      <li><strong>Enhanced MAs:</strong> Use type,period format for precise control: <code>&ma_1=e,20&ma_2=s,50</code></li>
      <li><strong>Parameter Validation:</strong> API validates all parameters - check error messages for corrections</li>
      <li><strong>Performance Limit:</strong> Maximum 30 indicators per request for optimal performance</li>
      <li><strong>Backtesting Accuracy:</strong> Use pretend_date/time for exact historical calculations</li>
      <li><strong>Strategy Development:</strong> Combine complementary indicators (trend + momentum + volatility + volume)</li>
      <li><strong>Timeframe Matching:</strong> Adjust indicator periods to match your trading timeframe</li>
    </ul>
  </div>

  <h2 class="mt-5">🚀 Migration Guide to v1.4</h2>
  <div class="alert alert-success">
    <h6>Upgrading to v1.4 with Enhanced Volume Support:</h6>
    <ul class="mb-0">
      <li><strong>✅ Fully Backward Compatible:</strong> All v1.3, v1.2 and v1.1 API calls work unchanged</li>
      <li><strong>📊 Multiple Volume Parameters:</strong> Use <code>candle-volume</code>, <code>candlevolume</code>, or <code>volume</code> - all work identically</li>
      <li><strong>🎯 Enhanced Validation:</strong> Better error messages for volume parameter validation</li>
      <li><strong>📈 Fixed dataField:</strong> Corrected <code>dataField</code> parameter handling for single field output</li>
      <li><strong>⚡ Better Performance:</strong> Improved parameter detection and forwarding to EA</li>
      <li><strong>🛡️ Enhanced Logging:</strong> Better debug logging for volume parameter processing</li>
      <li><strong>📊 Volume Strategy Ready:</strong> Pre-built combinations work with all volume parameter formats</li>
    </ul>
  </div>

    </div>
  </div>

  <div class="mt-5 p-3" style="background-color: #1a1a1a; border-radius: 8px;">
    <p class="mb-0"><strong>Note:</strong> All examples use your configured API key: <code><?php echo DEFAULT_API_KEY; ?></code> and the <?php echo APP_TITLE; ?> API endpoint at <code><?php echo BASE_URL; ?>/market-data-api-v1/market-data-api.php</code></p>
    <p class="mb-0 mt-2"><strong>Version:</strong> API v1.4 with enhanced volume parameter support, improved dataField handling, and comprehensive validation</p>
    <p class="mb-0 mt-2"><strong>New Features:</strong> Multiple volume parameter names, enhanced volume validation, fixed dataField support, and improved parameter detection</p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>