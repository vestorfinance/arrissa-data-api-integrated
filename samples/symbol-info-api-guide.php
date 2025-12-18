<?php
/**
 * ------------------------------------------------------------------------
 *  Author : Ngonidzashe Jiji
 *  Handles: Instagram: @davidrichchild
 *           Telegram: t.me/david_richchild
 *           TikTok: davidrichchild
 *  URLs    : https://arrissadata.com
 *            https://arrissatechnologies.com
 *            https://arrissa.trade
 *
 *  Course  : https://www.udemy.com/course/6804721
 *
 *  Permission:
 *    You are granted permission to use, copy, modify, and distribute this
 *    software and its source code for personal or commercial projects,
 *    provided that the author details above remain intact and visible in
 *    the distributed software (including any compiled or minified form).
 *
 *  Requirements:
 *    - Keep the author name, handles, URLs, and course link in this header
 *      (or an equivalent attribution location in distributed builds).
 *    - You may NOT remove or obscure the attribution.
 *
 *  Disclaimer:
 *    This software is provided "AS IS", without warranty of any kind,
 *    express or implied. The author is not liable for any claim, damages,
 *    or other liability arising from the use of this software.
 *
 *  Version: 1.0
 *  Date:    2025-01-27
 * ------------------------------------------------------------------------
 */
// daily-average-api-guide.php

// Include password protection
require_once 'password_protect.php';

// Include shared configuration
require_once '../app_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo APP_TITLE; ?> - Timeframe-Specific Movement API Guide</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

</head>
<body>
<!-- Logout Button -->
<a href="logout.php" class="btn btn-outline-danger btn-sm logout-btn">🔓 Logout</a>

<div class="container py-5">
  <p><a href="index.php" class="btn btn-outline-light mb-4">← Back to Home</a></p>
  <h1 class="mb-4">📊 <?php echo APP_TITLE; ?> Timeframe-Specific Movement API Guide</h1>

  <div class="alert alert-info mb-4">
    <h5>🔗 API Endpoint</h5>
    <strong>Base URL:</strong><br>
    <code><?php echo BASE_URL; ?>/daily-average-api/daily-average-api.php</code>
  </div>

  <div class="alert alert-info mb-4">
    <h5>🔑 Your API Key (Optional)</h5>
    <strong>Default API Key:</strong><br>
    <code><?php echo DEFAULT_API_KEY; ?></code><br>
    <small><em>Note: Authentication is currently disabled for standalone version</em></small>
  </div>

  <p>This advanced API provides comprehensive multi-timeframe movement analysis with <strong>timeframe-specific lookback periods</strong>. The lookback parameter now represents the exact number of periods for the selected timeframe (e.g., lookback=30 for M30 means 30 thirty-minute periods, not 30 days).</p>

  <h2 class="mt-5">⏰ Supported Timeframes & Default Lookbacks</h2>
  <div class="timeframe-grid">
    <div class="timeframe-card">
      <h6><span class="badge badge-timeframe">M5</span> 5-Minute</h6>
      <small>Default: 288 periods (1 day)<br>Max: 2,000 periods (~7 days)</small>
    </div>
    <div class="timeframe-card">
      <h6><span class="badge badge-timeframe">M15</span> 15-Minute</h6>
      <small>Default: 96 periods (1 day)<br>Max: 1,000 periods (~10 days)</small>
    </div>
    <div class="timeframe-card">
      <h6><span class="badge badge-timeframe">M30</span> 30-Minute</h6>
      <small>Default: 48 periods (1 day)<br>Max: 500 periods (~10 days)</small>
    </div>
    <div class="timeframe-card">
      <h6><span class="badge badge-timeframe">H1</span> 1-Hour</h6>
      <small>Default: 24 periods (1 day)<br>Max: 500 periods (~20 days)</small>
    </div>
    <div class="timeframe-card">
      <h6><span class="badge badge-timeframe">H4</span> 4-Hour</h6>
      <small>Default: 30 periods (~5 days)<br>Max: 200 periods (~33 days)</small>
    </div>
    <div class="timeframe-card">
      <h6><span class="badge badge-timeframe">H8</span> 8-Hour</h6>
      <small>Default: 21 periods (~7 days)<br>Max: 100 periods (~33 days)</small>
    </div>
    <div class="timeframe-card">
      <h6><span class="badge badge-timeframe">H12</span> 12-Hour</h6>
      <small>Default: 14 periods (~7 days)<br>Max: 60 periods (~30 days)</small>
    </div>
    <div class="timeframe-card">
      <h6><span class="badge badge-timeframe">D1</span> Daily</h6>
      <small>Default: 30 periods (30 days)<br>Max: 1,000 periods (~3 years)</small>
    </div>
    <div class="timeframe-card">
      <h6><span class="badge badge-timeframe">W1</span> Weekly</h6>
      <small>Default: 12 periods (~3 months)<br>Max: 200 periods (~4 years)</small>
    </div>
    <div class="timeframe-card">
      <h6><span class="badge badge-timeframe">M</span> Monthly</h6>
      <small>Default: 12 periods (1 year)<br>Max: 120 periods (~10 years)</small>
    </div>
  </div>

  <h2 class="mt-5">🔧 Parameters</h2>
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
        <td><span class="badge badge-custom">symbol</span></td>
        <td>string (e.g. "GBPUSD")</td>
        <td>✅ Always</td>
        <td>Trading symbol to analyze movement patterns for.</td>
      </tr>
      <tr>
        <td><span class="badge badge-timeframe">timeframe</span></td>
        <td>string</td>
        <td>❌ Optional</td>
        <td>Analysis timeframe. Valid options: M5, M15, M30, H1, H4, H8, H12, D1, W1, M<br>
            Default: <code>D1</code> (daily) if not specified.</td>
      </tr>
      <tr>
        <td><span class="badge badge-lookback">lookback</span></td>
        <td>integer</td>
        <td>❌ Optional</td>
        <td><strong>Number of timeframe periods</strong> to analyze (NOT days).<br>
            E.g., lookback=30 for M30 = 30 thirty-minute periods<br>
            Default: Timeframe-specific (see table above). Max: Varies by timeframe.</td>
      </tr>
      <tr>
        <td><span class="badge badge-sunday">ignore_sunday</span></td>
        <td>boolean</td>
        <td>❌ Optional</td>
        <td>Whether to exclude Sundays from daily (D1) analysis. Only applies to D1 timeframe.<br>
            Valid options: <code>true</code>, <code>false</code>, <code>1</code>, <code>0</code><br>
            Default: <code>true</code> (exclude Sundays) if not specified.</td>
      </tr>
      <tr>
        <td>pretend_date</td>
        <td>string (YYYY-MM-DD)</td>
        <td>❌ Optional</td>
        <td>Analyze market as if it were this date. Must be used with pretend_time.<br>
            Format: <code>YYYY-MM-DD</code>. E.g. <code>2025-10-10</code>.</td>
      </tr>
      <tr>
        <td>pretend_time</td>
        <td>string (HH:MM)</td>
        <td>❌ Optional</td>
        <td>Analyze market as if it were this time. Must be used with pretend_date.<br>
            Format: <code>HH:MM</code> (24h). E.g. <code>8:00</code>.</td>
      </tr>
    </tbody>
  </table>

  <h2 class="mt-5">🎯 Timeframe-Specific Lookback Explained</h2>
  
  <div class="parameter-highlight">
    <h6><span class="badge badge-lookback">lookback</span> Parameter - Key Concept</h6>
    <p><strong>IMPORTANT:</strong> The lookback parameter is now timeframe-specific periods, NOT days!</p>
    <div class="row">
      <div class="col-md-6">
        <h6>✅ Correct Understanding:</h6>
        <ul>
          <li><code>timeframe=M30&lookback=48</code> = 48 thirty-minute periods</li>
          <li><code>timeframe=H4&lookback=30</code> = 30 four-hour periods</li>
          <li><code>timeframe=D1&lookback=30</code> = 30 daily periods</li>
          <li><code>timeframe=W1&lookback=12</code> = 12 weekly periods</li>
        </ul>
      </div>
      <div class="col-md-6">
        <h6>❌ Old (Incorrect) Understanding:</h6>
        <ul>
          <li><s>lookback=30 always meant 30 days</s></li>
          <li><s>All timeframes used day-based calculation</s></li>
          <li><s>M30 with lookback=30 meant 30 days of M30 data</s></li>
        </ul>
      </div>
    </div>
  </div>

  <h2 class="mt-5">📊 Lookback Examples by Timeframe</h2>
  <table class="table table-dark table-bordered lookback-table">
    <thead>
      <tr>
        <th>Timeframe</th>
        <th>Example Lookback</th>
        <th>Actual Periods</th>
        <th>Approximate Time Coverage</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><span class="badge badge-timeframe">M5</span></td>
        <td>lookback=288</td>
        <td>288 five-minute periods</td>
        <td>1 day (24 hours)</td>
      </tr>
      <tr>
        <td><span class="badge badge-timeframe">M15</span></td>
        <td>lookback=96</td>
        <td>96 fifteen-minute periods</td>
        <td>1 day (24 hours)</td>
      </tr>
      <tr>
        <td><span class="badge badge-timeframe">M30</span></td>
        <td>lookback=48</td>
        <td>48 thirty-minute periods</td>
        <td>1 day (24 hours)</td>
      </tr>
      <tr>
        <td><span class="badge badge-timeframe">H1</span></td>
        <td>lookback=24</td>
        <td>24 one-hour periods</td>
        <td>1 day (24 hours)</td>
      </tr>
      <tr>
        <td><span class="badge badge-timeframe">H4</span></td>
        <td>lookback=30</td>
        <td>30 four-hour periods</td>
        <td>5 days (120 hours)</td>
      </tr>
      <tr>
        <td><span class="badge badge-timeframe">D1</span></td>
        <td>lookback=30</td>
        <td>30 daily periods</td>
        <td>30 days (1 month)</td>
      </tr>
      <tr>
        <td><span class="badge badge-timeframe">W1</span></td>
        <td>lookback=12</td>
        <td>12 weekly periods</td>
        <td>12 weeks (~3 months)</td>
      </tr>
      <tr>
        <td><span class="badge badge-timeframe">M</span></td>
        <td>lookback=12</td>
        <td>12 monthly periods</td>
        <td>12 months (1 year)</td>
      </tr>
    </tbody>
  </table>

  <h2 class="mt-5">📋 API Operation Examples</h2>
  
  <h5>🔄 1. Default Analysis (Uses Timeframe Defaults)</h5>
  <div class="code-block">
<pre># Daily analysis with default 30 daily periods
<?php echo BASE_URL; ?>/daily-average-api/daily-average-api.php?symbol=GBPUSD

# 4-hour analysis with default 30 H4 periods (~5 days)
<?php echo BASE_URL; ?>/daily-average-api/daily-average-api.php?symbol=GBPUSD&timeframe=H4

# 30-minute analysis with default 48 M30 periods (1 day)
<?php echo BASE_URL; ?>/daily-average-api/daily-average-api.php?symbol=EURUSD&timeframe=M30</pre>
  </div>

  <h5 class="mt-4">⚙️ 2. Custom Period Analysis</h5>
  <div class="code-block">
<pre># Analyze last 100 M30 periods (about 2 days of M30 data)
<?php echo BASE_URL; ?>/daily-average-api/daily-average-api.php?symbol=GBPUSD&timeframe=M30&lookback=100

# Analyze last 60 H4 periods (about 10 days of H4 data)
<?php echo BASE_URL; ?>/daily-average-api/daily-average-api.php?symbol=EURUSD&timeframe=H4&lookback=60

# Analyze last 90 daily periods (3 months of daily data)
<?php echo BASE_URL; ?>/daily-average-api/daily-average-api.php?symbol=USDJPY&timeframe=D1&lookback=90</pre>
  </div>

  <h5 class="mt-4">📊 3. Scalping Analysis (Short-term Periods)</h5>
  <div class="code-block">
<pre># Last 500 M5 periods (about 1.7 days of 5-minute data)
<?php echo BASE_URL; ?>/daily-average-api/daily-average-api.php?symbol=EURUSD&timeframe=M5&lookback=500

# Last 200 M15 periods (about 2 days of 15-minute data)  
<?php echo BASE_URL; ?>/daily-average-api/daily-average-api.php?symbol=GBPUSD&timeframe=M15&lookback=200

# Last 100 M30 periods (about 2 days of 30-minute data)
<?php echo BASE_URL; ?>/daily-average-api/daily-average-api.php?symbol=USDJPY&timeframe=M30&lookback=100</pre>
  </div>

  <h5 class="mt-4">📈 4. Swing Trading Analysis (Medium-term Periods)</h5>
  <div class="code-block">
<pre># Last 50 H4 periods (about 8 days of 4-hour data)
<?php echo BASE_URL; ?>/daily-average-api/daily-average-api.php?symbol=GBPUSD&timeframe=H4&lookback=50

# Last 30 H8 periods (about 10 days of 8-hour data)
<?php echo BASE_URL; ?>/daily-average-api/daily-average-api.php?symbol=EURUSD&timeframe=H8&lookback=30

# Last 60 daily periods (2 months of daily data)
<?php echo BASE_URL; ?>/daily-average-api/daily-average-api.php?symbol=XAUUSD&timeframe=D1&lookback=60</pre>
  </div>

  <h5 class="mt-4">📊 5. Position Trading Analysis (Long-term Periods)</h5>
  <div class="code-block">
<pre># Last 180 daily periods (6 months of daily data)
<?php echo BASE_URL; ?>/daily-average-api/daily-average-api.php?symbol=GBPUSD&timeframe=D1&lookback=180

# Last 52 weekly periods (1 year of weekly data)
<?php echo BASE_URL; ?>/daily-average-api/daily-average-api.php?symbol=EURUSD&timeframe=W1&lookback=52

# Last 24 monthly periods (2 years of monthly data)
<?php echo BASE_URL; ?>/daily-average-api/daily-average-api.php?symbol=XAUUSD&timeframe=M&lookback=24</pre>
  </div>

  <h2 class="mt-5">📤 Enhanced Response Format</h2>
  
  <h5>✅ Success Response</h5>
  <div class="code-block">
<pre>{
  "arrissa_data": {
    "symbol_behaviour_data": "Symbol: GBPUSD (M30 timeframe analysis). Point value: 0.00001. Lookback period: 100 M30 periods. Trading periods analyzed: 100 30-minute periods. Total movement: 4,500 points. Average 30-minute movement: 45 points..."
  }
}</pre>
  </div>

  <h5 class="mt-4">❌ Timeframe-Specific Error Responses</h5>
  <div class="code-block">
<pre># Invalid lookback for specific timeframe
{
  "arrissa_data": {
    "error": "Invalid lookback for M5 timeframe. Must be between 1 and 2000 periods"
  }
}

# Invalid timeframe
{
  "arrissa_data": {
    "error": "Invalid timeframe. Valid options: M5, M15, M30, H1, H4, H8, H12, D1, W1, M"
  }
}</pre>
  </div>

  <h2 class="mt-5">📋 Enhanced Analysis Output</h2>
  <div class="behavioral-sample">
    <h6>Example M30 Analysis (100 periods lookback):</h6>
    <p><em>"Symbol: GBPUSD (M30 timeframe analysis). Point value: 0.00001. Lookback period: 100 M30 periods. Trading periods analyzed: 100 30-minute periods. Total movement: 4,500 points. Average 30-minute movement: 45 points. Movement range: 5 - 180 points. The market shows high volatility with 30-minute periods capable of moving 36 times more than quiet periods. Bullish periods: 48 (48%) - Avg: 47 pts. Bearish periods: 46 (46%) - Avg: 44 pts. Doji periods: 6 (6%) - Avg: 15 pts. The market demonstrates balanced directional bias over the 100 M30 periods analyzed. Max bullish streak: 5 periods. Max bearish streak: 7 periods. Continuation periods: 42 (42%) indicating moderate trend persistence within 30-minute timeframes. This timeframe is suitable for scalping strategies with quick entry/exit decisions. Analysis reference time using 30-minute timeframe with 100 M30 periods lookback. This is current 30-minute market analysis."</em></p>
  </div>

  <h2 class="mt-5">🎯 Strategic Lookback Recommendations</h2>
  
  <div class="row">
    <div class="col-md-6">
      <h6>Scalping Strategy (M5-M30):</h6>
      <ul>
        <li><strong>M5:</strong> 288-576 periods (1-2 days)</li>
        <li><strong>M15:</strong> 96-192 periods (1-2 days)</li>
        <li><strong>M30:</strong> 48-96 periods (1-2 days)</li>
        <li><strong>Focus:</strong> Recent micro-patterns</li>
      </ul>
    </div>
    <div class="col-md-6">
      <h6>Day Trading Strategy (H1-H4):</h6>
      <ul>
        <li><strong>H1:</strong> 24-168 periods (1-7 days)</li>
        <li><strong>H4:</strong> 6-42 periods (1-7 days)</li>
        <li><strong>Focus:</strong> Intraday momentum patterns</li>
      </ul>
    </div>
  </div>
  
  <div class="row mt-3">
    <div class="col-md-6">
      <h6>Swing Trading Strategy (H4-D1):</h6>
      <ul>
        <li><strong>H4:</strong> 30-120 periods (5-20 days)</li>
        <li><strong>H8:</strong> 21-84 periods (7-28 days)</li>
        <li><strong>D1:</strong> 30-90 periods (1-3 months)</li>
        <li><strong>Focus:</strong> Medium-term trend patterns</li>
      </ul>
    </div>
    <div class="col-md-6">
      <h6>Position Trading Strategy (D1-M):</h6>
      <ul>
        <li><strong>D1:</strong> 90-365 periods (3-12 months)</li>
        <li><strong>W1:</strong> 12-104 periods (3-24 months)</li>
        <li><strong>M:</strong> 12-60 periods (1-5 years)</li>
        <li><strong>Focus:</strong> Long-term behavioral patterns</li>
      </ul>
    </div>
  </div>

  <h2 class="mt-5">📥 Quick Test Links by Period Count</h2>
  
  <div class="row">
    <div class="col-md-3">
      <h6>Short Period Analysis:</h6>
      <ul>
        <li><a href="<?php echo BASE_URL; ?>/daily-average-api/daily-average-api.php?symbol=EURUSD&timeframe=M30&lookback=50" target="_blank">EUR/USD M30 (50 periods)</a></li>
        <li><a href="<?php echo BASE_URL; ?>/daily-average-api/daily-average-api.php?symbol=GBPUSD&timeframe=H1&lookback=25" target="_blank">GBP/USD H1 (25 periods)</a></li>
        <li><a href="<?php echo BASE_URL; ?>/daily-average-api/daily-average-api.php?symbol=USDJPY&timeframe=H4&lookback=20" target="_blank">USD/JPY H4 (20 periods)</a></li>
      </ul>
    </div>
    <div class="col-md-3">
      <h6>Medium Period Analysis:</h6>
      <ul>
        <li><a href="<?php echo BASE_URL; ?>/daily-average-api/daily-average-api.php?symbol=EURUSD&timeframe=M30&lookback=100" target="_blank">EUR/USD M30 (100 periods)</a></li>
        <li><a href="<?php echo BASE_URL; ?>/daily-average-api/daily-average-api.php?symbol=GBPUSD&timeframe=H4&lookback=50" target="_blank">GBP/USD H4 (50 periods)</a></li>
        <li><a href="<?php echo BASE_URL; ?>/daily-average-api/daily-average-api.php?symbol=XAUUSD&timeframe=D1&lookback=60" target="_blank">Gold D1 (60 periods)</a></li>
      </ul>
    </div>
    <div class="col-md-3">
      <h6>Long Period Analysis:</h6>
      <ul>
        <li><a href="<?php echo BASE_URL; ?>/daily-average-api/daily-average-api.php?symbol=EURUSD&timeframe=H4&lookback=100" target="_blank">EUR/USD H4 (100 periods)</a></li>
        <li><a href="<?php echo BASE_URL; ?>/daily-average-api/daily-average-api.php?symbol=GBPUSD&timeframe=D1&lookback=180" target="_blank">GBP/USD D1 (180 periods)</a></li>
        <li><a href="<?php echo BASE_URL; ?>/daily-average-api/daily-average-api.php?symbol=XAUUSD&timeframe=W1&lookback=52" target="_blank">Gold W1 (52 periods)</a></li>
      </ul>
    </div>
    <div class="col-md-3">
      <h6>Extended Period Analysis:</h6>
      <ul>
        <li><a href="<?php echo BASE_URL; ?>/daily-average-api/daily-average-api.php?symbol=EURUSD&timeframe=D1&lookback=365" target="_blank">EUR/USD D1 (365 periods)</a></li>
        <li><a href="<?php echo BASE_URL; ?>/daily-average-api/daily-average-api.php?symbol=GBPUSD&timeframe=W1&lookback=104" target="_blank">GBP/USD W1 (104 periods)</a></li>
        <li><a href="<?php echo BASE_URL; ?>/daily-average-api/daily-average-api.php?symbol=XAUUSD&timeframe=M&lookback=60" target="_blank">Gold Monthly (60 periods)</a></li>
      </ul>
    </div>
  </div>

  <h2 class="mt-5">✅ Best Practices & Tips</h2>
  <ul>
    <li><strong>Period Understanding:</strong> Remember lookback is always timeframe-specific periods, not days</li>
    <li><strong>Reasonable Limits:</strong> Each timeframe has maximum period limits for performance</li>
    <li><strong>Default Values:</strong> Each timeframe has optimized default lookback values</li>
    <li><strong>Strategy Alignment:</strong> Match lookback periods to your trading strategy timeframe</li>
    <li><strong>Data Sufficiency:</strong> Ensure your broker provides enough historical data</li>
    <li><strong>Performance Consideration:</strong> Larger lookback periods take longer to process</li>
    <li><strong>Market Context:</strong> Consider market conditions when choosing lookback periods</li>
  </ul>

  <h2 class="mt-5">🔍 Period Calculation Examples</h2>
  <div class="alert alert-info">
    <h6>💡 Understanding Period Calculations:</h6>
    <div class="row">
      <div class="col-md-6">
        <p><strong>Example 1: M30 Timeframe</strong></p>
        <ul class="mb-0">
          <li>1 day = 48 M30 periods (24 hours ÷ 0.5 hours)</li>
          <li>lookback=100 = ~2.08 days of M30 data</li>
          <li>lookback=200 = ~4.17 days of M30 data</li>
        </ul>
      </div>
      <div class="col-md-6">
        <p><strong>Example 2: H4 Timeframe</strong></p>
        <ul class="mb-0">
          <li>1 day = 6 H4 periods (24 hours ÷ 4 hours)</li>
          <li>lookback=30 = 5 days of H4 data</li>
          <li>lookback=60 = 10 days of H4 data</li>
        </ul>
      </div>
    </div>
  </div>

  <div class="mt-5 p-3" style="background-color: #1a1a1a; border-radius: 8px;">
    <p class="mb-0"><strong>Timeframe-Specific Lookback:</strong> This enhanced API now uses timeframe-specific period lookbacks instead of day-based calculations. This provides more precise control over the exact number of periods analyzed for each timeframe, enabling better strategy-specific analysis. All analysis adapts to the exact number of periods requested for the selected timeframe. Analysis is generated in real-time by the MT5 Expert Advisor and delivered through the <?php echo APP_TITLE; ?> API endpoint at <code><?php echo BASE_URL; ?>/daily-average-api/daily-average-api.php</code></p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>