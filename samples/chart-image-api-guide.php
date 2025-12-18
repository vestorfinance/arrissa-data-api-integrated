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
 *  Date:    2025-09-20
 * ------------------------------------------------------------------------
 */
// chart-api-guide.php

// Include password protection
require_once './password_protect.php';

// Include shared configuration
require_once '../app_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo APP_TITLE; ?> - Chart API Guide</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #121212;
      color: #e0e0e0;
    }
    a {
      color: #90caf9;
    }
    .table-dark th, .table-dark td {
      color: #e0e0e0;
    }
    .code-block {
      background-color: #1e1e1e;
      padding: 1em;
      border-radius: 8px;
      font-family: monospace;
      color: #c3e88d;
      overflow-x: auto;
    }
    .badge-custom {
      background-color: #2196F3;
      color: white;
    }
    .alert-info {
      background-color: #1a237e;
      border-color: #3f51b5;
      color: #e3f2fd;
    }
    .alert-warning {
      background-color: #f57c00;
      border-color: #ff9800;
      color: #fff3e0;
    }
    .chart-preview {
      border: 2px solid #3f51b5;
      border-radius: 8px;
      margin: 10px 0;
    }
    .logout-btn {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 1000;
    }
  </style>
</head>
<body>
<!-- Logout Button -->
<a href="logout.php" class="btn btn-outline-danger btn-sm logout-btn">🔓 Logout</a>

<div class="container py-5">
  <p><a href="index.php" class="btn btn-outline-light mb-4">← Back to Home</a></p>
  <h1 class="mb-4">📊 <?php echo APP_TITLE; ?> Chart API Guide</h1>

  <div class="alert alert-info mb-4">
    <h5>🔗 API Endpoint</h5>
    <strong>Base URL:</strong><br>
    <code><?php echo BASE_URL; ?>/chart-image-api-v1/chart-image-api.php</code>
  </div>

  <div class="alert alert-info mb-4">
    <h5>🔑 Your API Key</h5>
    <strong>Default API Key:</strong><br>
    <code><?php echo DEFAULT_API_KEY; ?></code>
  </div>

  <p>This API generates professional 16:9 candlestick charts with advanced technical analysis features. 
  It fetches real-time market data and renders high-quality PNG charts with customizable indicators, 
  Fibonacci levels, period separators, and high/low markers.</p>

  <h2 class="mt-5">🔐 Authentication</h2>
  <p>All requests require an API key parameter:</p>
  <div class="code-block">
<pre><?php echo BASE_URL; ?>/chart-image-api-v1/chart-image-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=EURUSD&timeframe=H1</pre>
  </div>

  <!-- Rest of the content remains the same as before, but all instances of YOUR_KEY are now replaced with <?php echo DEFAULT_API_KEY; ?> -->

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
        <td><span class="badge badge-custom">api_key</span></td>
        <td>string</td>
        <td>✅ Always</td>
        <td>Your unique API key for authentication and quota tracking.</td>
      </tr>
      <tr>
        <td><span class="badge badge-custom">symbol</span></td>
        <td>string</td>
        <td>✅ Always</td>
        <td>Trading symbol (e.g., "EURUSD", "GBPJPY", "XAUUSD")</td>
      </tr>
      <tr>
        <td><span class="badge badge-custom">timeframe</span></td>
        <td>string</td>
        <td>✅ Always</td>
        <td>Chart timeframe: M1, M5, M15, H1, H4, D1</td>
      </tr>
      <tr>
        <td>count</td>
        <td>integer</td>
        <td>❌</td>
        <td>Number of candles to display (default: 100)</td>
      </tr>
      <tr>
        <td>pretend_date</td>
        <td>string (YYYY-MM-DD)</td>
        <td>❌</td>
        <td>Historical date for backtesting charts</td>
      </tr>
      <tr>
        <td>pretend_time</td>
        <td>string (HH:MM)</td>
        <td>❌</td>
        <td>Historical time for backtesting charts</td>
      </tr>
      <tr>
        <td>rangeType</td>
        <td>string</td>
        <td>❌</td>
        <td>Period range: "today", "yesterday", "this-week", "last-week", etc.</td>
      </tr>
      <tr>
        <td>ema1_period</td>
        <td>integer</td>
        <td>❌</td>
        <td>First EMA period (orange line)</td>
      </tr>
      <tr>
        <td>ema2_period</td>
        <td>integer</td>
        <td>❌</td>
        <td>Second EMA period (gray line)</td>
      </tr>
      <tr>
        <td>atr</td>
        <td>integer</td>
        <td>❌</td>
        <td>ATR (Average True Range) period (purple line)</td>
      </tr>
      <tr>
        <td>data</td>
        <td>string</td>
        <td>❌</td>
        <td>Data field filter: "open", "high", "low", "close"</td>
      </tr>
      <tr>
        <td>fib</td>
        <td>boolean</td>
        <td>❌</td>
        <td>Show Fibonacci retracement levels (true/false)</td>
      </tr>
      <tr>
        <td>period_separators</td>
        <td>string</td>
        <td>❌</td>
        <td>Time period separators: 5M, 15M, 30M, 1H, 4H, day, week, month, year</td>
      </tr>
      <tr>
        <td>high_low</td>
        <td>boolean</td>
        <td>❌</td>
        <td>Show high/low lines for each period segment (true/false)</td>
      </tr>
    </tbody>
  </table>

  <!-- Continue with all the rest of the content from the previous version, 
       but replace all instances of YOUR_KEY with <?php echo DEFAULT_API_KEY; ?> -->

  <h2 class="mt-5">🔗 Quick Test Links</h2>
  <div class="row">
    <div class="col-md-6">
      <h6>Basic Charts:</h6>
      <ul>
        <li><a href="<?php echo BASE_URL; ?>/chart-image-api-v1/chart-image-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=EURUSD&timeframe=H1" target="_blank">EURUSD H1</a></li>
        <li><a href="<?php echo BASE_URL; ?>/chart-image-api-v1/chart-image-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=GBPJPY&timeframe=M15" target="_blank">GBPJPY M15</a></li>
        <li><a href="<?php echo BASE_URL; ?>/chart-image-api-v1/chart-image-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=XAUUSD&timeframe=H4" target="_blank">Gold H4</a></li>
      </ul>
    </div>
    <div class="col-md-6">
      <h6>Technical Analysis:</h6>
      <ul>
        <li><a href="<?php echo BASE_URL; ?>/chart-image-api-v1/chart-image-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=EURUSD&timeframe=H1&ema1_period=20&ema2_period=50" target="_blank">EURUSD with EMAs</a></li>
        <li><a href="<?php echo BASE_URL; ?>/chart-image-api-v1/chart-image-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=GBPUSD&timeframe=H4&fib=true" target="_blank">GBPUSD with Fibonacci</a></li>
        <li><a href="<?php echo BASE_URL; ?>/chart-image-api-v1/chart-image-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&symbol=USDJPY&timeframe=H1&atr=14" target="_blank">USDJPY with ATR</a></li>
      </ul>
    </div>
  </div>

  <div class="mt-5 p-3" style="background-color: #1a1a1a; border-radius: 8px;">
    <p class="mb-0"><strong>Note:</strong> All examples use your configured API key: <code><?php echo DEFAULT_API_KEY; ?></code> and the <?php echo APP_TITLE; ?> API endpoint at <code><?php echo BASE_URL; ?>/chart-image-api-v1/chart-image-api.php</code></p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>