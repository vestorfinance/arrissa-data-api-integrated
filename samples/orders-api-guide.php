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
// mt5-trading-api-guide.php

// Include password protection
require_once 'password_protect.php';

// Include shared configuration
require_once '../app_config.php';

// Construct base API URL
$api_base = BASE_URL . '/orders-api-v1/orders-api.php?api_key=' . DEFAULT_API_KEY;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo APP_TITLE; ?> - MT5 Trading API Guide</title>
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
    .response-block {
      background-color: #0d1117;
      padding: 1em;
      border-radius: 8px;
      font-family: monospace;
      color: #58a6ff;
      overflow-x: auto;
      max-height: 400px;
      overflow-y: auto;
      border: 1px solid #30363d;
    }
    .badge-custom {
      background-color: #2196F3;
      color: white;
    }
    .badge-get {
      background-color: #4CAF50;
      color: white;
    }
    .badge-post {
      background-color: #FF9800;
      color: white;
    }
    .alert-info {
      background-color: #1a237e;
      border-color: #3f51b5;
      color: #e3f2fd;
    }
    .alert-success {
      background-color: #1b5e20;
      border-color: #4caf50;
      color: #e8f5e8;
    }
    .alert-warning {
      background-color: #e65100;
      border-color: #ff9800;
      color: #fff3e0;
    }
    .logout-btn {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 1000;
    }
    .nav-pills .nav-link.active {
      background-color: #2196F3;
    }
    .nav-pills .nav-link {
      color: #90caf9;
    }
    .example-section {
      background-color: #1a1a1a;
      padding: 1.5rem;
      border-radius: 8px;
      margin-bottom: 1rem;
    }
    .btn-test {
      margin: 0.25rem;
    }
    .loading {
      opacity: 0.6;
      pointer-events: none;
    }
  </style>
</head>
<body>
<!-- Logout Button -->
<a href="logout.php" class="btn btn-outline-danger btn-sm logout-btn">🔓 Logout</a>

<div class="container py-5">
  <p><a href="index.php" class="btn btn-outline-light mb-4">← Back to Home</a></p>
  <h1 class="mb-4">🚀 <?php echo APP_TITLE; ?> MT5 Trading API Guide</h1>

  <div class="alert alert-info mb-4">
    <h5>🔗 API Endpoint</h5>
    <strong>Base URL:</strong><br>
    <code><?php echo BASE_URL; ?>/orders-api-v1/orders-api.php</code>
  </div>

  <div class="alert alert-success mb-4">
    <h5>🔑 Your API Key</h5>
    <strong>Default API Key:</strong><br>
    <code><?php echo DEFAULT_API_KEY; ?></code>
  </div>

  <!-- Navigation Tabs -->
  <ul class="nav nav-pills mb-4" id="apiTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="overview-tab" data-bs-toggle="pill" data-bs-target="#overview" type="button">📋 Overview</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="trading-tab" data-bs-toggle="pill" data-bs-target="#trading" type="button">💹 Trading</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="orders-tab" data-bs-toggle="pill" data-bs-target="#orders" type="button">📊 Orders</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="history-tab" data-bs-toggle="pill" data-bs-target="#history" type="button">📈 History</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="profit-tab" data-bs-toggle="pill" data-bs-target="#profit" type="button">💰 Profit</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="examples-tab" data-bs-toggle="pill" data-bs-target="#examples" type="button">🧪 Live Tests</button>
    </li>
  </ul>

  <div class="tab-content" id="apiTabsContent">
    
    <!-- Overview Tab -->
    <div class="tab-pane fade show active" id="overview" role="tabpanel">
      <h2>📋 API Overview</h2>
      <p>This comprehensive MT5 Trading API enables complete control over MetaTrader 5 accounts via HTTP requests. Execute trades, manage positions, retrieve order history, and analyze profit data - all through simple REST API calls.</p>

      <h3 class="mt-4">🔐 Authentication</h3>
      <p>All requests require an API key parameter for authentication and quota tracking:</p>
      <div class="code-block">
<pre>?api_key=<?php echo DEFAULT_API_KEY; ?></pre>
      </div>

      <h3 class="mt-4">🎯 Core Features</h3>
      <div class="row">
        <div class="col-md-6">
          <ul>
            <li><strong>Market Orders:</strong> BUY, SELL at current prices</li>
            <li><strong>Pending Orders:</strong> BUY_LIMIT, SELL_LIMIT, BUY_STOP, SELL_STOP</li>
            <li><strong>Position Management:</strong> Close, modify, break-even</li>
            <li><strong>Order Management:</strong> Delete pending orders</li>
          </ul>
        </div>
        <div class="col-md-6">
          <ul>
            <li><strong>Advanced Features:</strong> Trail stop loss, close by profit/loss</li>
            <li><strong>Order Filtering:</strong> By symbol, status, profit/loss</li>
            <li><strong>Historical Data:</strong> Trade history with flexible time ranges</li>
            <li><strong>Profit Analysis:</strong> Comprehensive profit tracking</li>
          </ul>
        </div>
      </div>

      <h3 class="mt-4">⚙️ Technical Architecture</h3>
      <p>The API uses a queue-based system for reliable MT5 communication:</p>
      <ol>
        <li><strong>Request Queuing:</strong> HTTP requests are queued as JSON files</li>
        <li><strong>EA Processing:</strong> MT5 Expert Advisor polls and processes requests</li>
        <li><strong>Trade Execution:</strong> EA executes trades and posts results back</li>
        <li><strong>Response Delivery:</strong> API returns JSON response to client</li>
        <li><strong>Auto Cleanup:</strong> Temporary files are garbage collected</li>
      </ol>

      <div class="alert alert-warning">
        <strong>⚠️ Important Notes:</strong>
        <ul class="mb-0">
          <li>Ensure MT5 terminal is running with the EA attached</li>
          <li>WebRequest must be enabled in MT5 settings</li>
          <li>API endpoint must be added to allowed URLs</li>
          <li>All prices are in symbol's base currency</li>
        </ul>
      </div>
    </div>

    <!-- Trading Tab -->
    <div class="tab-pane fade" id="trading" role="tabpanel">
      <h2>💹 Trading Operations</h2>
      
      <div class="example-section">
        <h3>🔄 Market Orders</h3>
        <p>Execute immediate buy/sell orders at current market prices:</p>
        
        <div class="row">
          <div class="col-md-6">
            <h5>BUY Orders</h5>
            <a href="<?php echo $api_base; ?>&action=BUY&symbol=EURUSD&volume=0.01" target="_blank" class="btn btn-success btn-test">
              📈 BUY EURUSD 0.01
            </a>
            <a href="<?php echo $api_base; ?>&action=BUY&symbol=EURUSD&volume=0.01&sl=1.1000&tp=1.1200" target="_blank" class="btn btn-success btn-test">
              📈 BUY EURUSD with SL/TP
            </a>
            <a href="<?php echo $api_base; ?>&action=BUY&symbol=GBPUSD&volume=0.02" target="_blank" class="btn btn-success btn-test">
              📈 BUY GBPUSD 0.02
            </a>
            <a href="<?php echo $api_base; ?>&action=BUY&symbol=USDJPY&volume=0.01" target="_blank" class="btn btn-success btn-test">
              📈 BUY USDJPY 0.01
            </a>
          </div>
          <div class="col-md-6">
            <h5>SELL Orders</h5>
            <a href="<?php echo $api_base; ?>&action=SELL&symbol=EURUSD&volume=0.01" target="_blank" class="btn btn-danger btn-test">
              📉 SELL EURUSD 0.01
            </a>
            <a href="<?php echo $api_base; ?>&action=SELL&symbol=GBPUSD&volume=0.01&sl=1.2500&tp=1.2300" target="_blank" class="btn btn-danger btn-test">
              📉 SELL GBPUSD with SL/TP
            </a>
            <a href="<?php echo $api_base; ?>&action=SELL&symbol=USDJPY&volume=0.01" target="_blank" class="btn btn-danger btn-test">
              📉 SELL USDJPY 0.01
            </a>
            <a href="<?php echo $api_base; ?>&action=SELL&symbol=AUDUSD&volume=0.01" target="_blank" class="btn btn-danger btn-test">
              📉 SELL AUDUSD 0.01
            </a>
          </div>
        </div>
      </div>

      <div class="example-section">
        <h3>📋 Pending Orders</h3>
        <p>Place orders that execute when price reaches specified levels:</p>
        
        <div class="row">
          <div class="col-md-6">
            <h5>Limit Orders</h5>
            <a href="<?php echo $api_base; ?>&action=BUY_LIMIT&symbol=EURUSD&volume=0.01&price=1.1000" target="_blank" class="btn btn-info btn-test">
              📊 BUY LIMIT EURUSD
            </a>
            <a href="<?php echo $api_base; ?>&action=SELL_LIMIT&symbol=GBPUSD&volume=0.01&price=1.2600" target="_blank" class="btn btn-info btn-test">
              📊 SELL LIMIT GBPUSD
            </a>
            <a href="<?php echo $api_base; ?>&action=BUY_LIMIT&symbol=USDJPY&volume=0.01&price=110.00&sl=109.50&tp=110.50" target="_blank" class="btn btn-info btn-test">
              📊 BUY LIMIT with SL/TP
            </a>
            <a href="<?php echo $api_base; ?>&action=SELL_LIMIT&symbol=AUDUSD&volume=0.01&price=0.7500" target="_blank" class="btn btn-info btn-test">
              📊 SELL LIMIT AUDUSD
            </a>
          </div>
          <div class="col-md-6">
            <h5>Stop Orders</h5>
            <a href="<?php echo $api_base; ?>&action=BUY_STOP&symbol=EURUSD&volume=0.01&price=1.1200" target="_blank" class="btn btn-warning btn-test">
              🚦 BUY STOP EURUSD
            </a>
            <a href="<?php echo $api_base; ?>&action=SELL_STOP&symbol=GBPUSD&volume=0.01&price=1.2400" target="_blank" class="btn btn-warning btn-test">
              🚦 SELL STOP GBPUSD
            </a>
            <a href="<?php echo $api_base; ?>&action=SELL_STOP&symbol=USDJPY&volume=0.01&price=109.00&sl=109.50&tp=108.50" target="_blank" class="btn btn-warning btn-test">
              🚦 SELL STOP with SL/TP
            </a>
            <a href="<?php echo $api_base; ?>&action=BUY_STOP&symbol=AUDUSD&volume=0.01&price=0.7200" target="_blank" class="btn btn-warning btn-test">
              🚦 BUY STOP AUDUSD
            </a>
          </div>
        </div>
      </div>

      <div class="example-section">
        <h3>❌ Position Management</h3>
        <p>Close and manage existing positions:</p>
        
        <div class="row">
          <div class="col-md-6">
            <h5>Close Operations</h5>
            <a href="<?php echo $api_base; ?>&action=CLOSE&symbol=EURUSD" target="_blank" class="btn btn-secondary btn-test">
              ❌ Close All EURUSD
            </a>
            <a href="<?php echo $api_base; ?>&action=CLOSE&symbol=GBPUSD" target="_blank" class="btn btn-secondary btn-test">
              ❌ Close All GBPUSD
            </a>
            <a href="<?php echo $api_base; ?>&action=CLOSE_ALL" target="_blank" class="btn btn-secondary btn-test">
              ❌ Close All Positions
            </a>
            <a href="<?php echo $api_base; ?>&action=CLOSE_LOSS&symbol=ALL" target="_blank" class="btn btn-danger btn-test">
              💔 Close All Losses
            </a>
            <a href="<?php echo $api_base; ?>&action=CLOSE_PROFIT&symbol=ALL" target="_blank" class="btn btn-success btn-test">
              💚 Close All Profits
            </a>
          </div>
          <div class="col-md-6">
            <h5>Advanced Management</h5>
            <a href="<?php echo $api_base; ?>&action=BREAK_EVEN_ALL&symbol=ALL" target="_blank" class="btn btn-primary btn-test">
              ⚖️ Break Even All
            </a>
            <a href="<?php echo $api_base; ?>&action=BREAK_EVEN_ALL&symbol=EURUSD" target="_blank" class="btn btn-primary btn-test">
              ⚖️ Break Even EURUSD
            </a>
            <a href="<?php echo $api_base; ?>&action=BREAK_EVEN_ALL&symbol=GBPUSD" target="_blank" class="btn btn-primary btn-test">
              ⚖️ Break Even GBPUSD
            </a>
            <a href="<?php echo $api_base; ?>&action=DELETE_ALL_ORDERS&symbol=ALL" target="_blank" class="btn btn-info btn-test">
              🗑️ Delete All Orders
            </a>
            <a href="<?php echo $api_base; ?>&action=DELETE_ALL_ORDERS&symbol=EURUSD" target="_blank" class="btn btn-info btn-test">
              🗑️ Delete EURUSD Orders
            </a>
          </div>
        </div>
      </div>

      <div class="example-section">
        <h3>🔧 Position Modification</h3>
        <p>Modify existing positions (requires actual ticket numbers):</p>
        
        <div class="row">
          <div class="col-md-6">
            <h5>Stop Loss & Take Profit</h5>
            <a href="<?php echo $api_base; ?>&action=MODIFY_SL&ticket=123456789&new_value=1.1000" target="_blank" class="btn btn-warning btn-test">
              🛑 Modify Stop Loss
            </a>
            <a href="<?php echo $api_base; ?>&action=MODIFY_TP&ticket=123456789&new_value=1.1200" target="_blank" class="btn btn-success btn-test">
              🎯 Modify Take Profit
            </a>
            <a href="<?php echo $api_base; ?>&action=BREAK_EVEN&ticket=123456789" target="_blank" class="btn btn-primary btn-test">
              ⚖️ Break Even Position
            </a>
          </div>
          <div class="col-md-6">
            <h5>Advanced Features</h5>
            <a href="<?php echo $api_base; ?>&action=TRAIL_SL&ticket=123456789&new_value=50" target="_blank" class="btn btn-info btn-test">
              📈 Trail SL (50 points)
            </a>
            <a href="<?php echo $api_base; ?>&action=CLOSE&ticket=123456789" target="_blank" class="btn btn-secondary btn-test">
              ❌ Close Specific Position
            </a>
            <a href="<?php echo $api_base; ?>&action=DELETE_ORDER&ticket=123456789" target="_blank" class="btn btn-danger btn-test">
              🗑️ Delete Specific Order
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Orders Tab -->
    <div class="tab-pane fade" id="orders" role="tabpanel">
      <h2>📊 Order Information</h2>
      
      <div class="example-section">
        <h3>📋 View All Orders</h3>
        <p>Get comprehensive order information:</p>
        
        <a href="<?php echo $api_base; ?>" target="_blank" class="btn btn-primary btn-test">
          📊 Get All Orders
        </a>
        <a href="<?php echo $api_base; ?>&filter_running=true" target="_blank" class="btn btn-success btn-test">
          🟢 Running Positions Only
        </a>
        <a href="<?php echo $api_base; ?>&filter_pending=true" target="_blank" class="btn btn-warning btn-test">
          🟡 Pending Orders Only
        </a>
        <a href="<?php echo $api_base; ?>&filter_closed=true" target="_blank" class="btn btn-secondary btn-test">
          🔴 Closed Positions Only
        </a>
      </div>

      <div class="example-section">
        <h3>🔍 Filter by Profitability</h3>
        <p>Filter orders by profit/loss status:</p>
        
        <a href="<?php echo $api_base; ?>&filter_profit=true" target="_blank" class="btn btn-success btn-test">
          💚 Profitable Only
        </a>
        <a href="<?php echo $api_base; ?>&filter_loss=true" target="_blank" class="btn btn-danger btn-test">
          💔 Losing Only
        </a>
        <a href="<?php echo $api_base; ?>&filter_profit=true&filter_symbol=EURUSD" target="_blank" class="btn btn-success btn-test">
          💚 EURUSD Profits
        </a>
        <a href="<?php echo $api_base; ?>&filter_loss=true&filter_symbol=GBPUSD" target="_blank" class="btn btn-danger btn-test">
          💔 GBPUSD Losses
        </a>
        <a href="<?php echo $api_base; ?>&filter_profit=true&filter_running=true" target="_blank" class="btn btn-success btn-test">
          💚 Running Profits
        </a>
        <a href="<?php echo $api_base; ?>&filter_loss=true&filter_running=true" target="_blank" class="btn btn-danger btn-test">
          💔 Running Losses
        </a>
      </div>

      <div class="example-section">
        <h3>🎯 Filter by Symbol</h3>
        <p>View orders for specific trading pairs:</p>
        
        <a href="<?php echo $api_base; ?>&filter_symbol=EURUSD" target="_blank" class="btn btn-info btn-test">
          🇪🇺 EURUSD Orders
        </a>
        <a href="<?php echo $api_base; ?>&filter_symbol=GBPUSD" target="_blank" class="btn btn-info btn-test">
          🇬🇧 GBPUSD Orders
        </a>
        <a href="<?php echo $api_base; ?>&filter_symbol=USDJPY" target="_blank" class="btn btn-info btn-test">
          🇯🇵 USDJPY Orders
        </a>
        <a href="<?php echo $api_base; ?>&filter_symbol=AUDUSD" target="_blank" class="btn btn-info btn-test">
          🇦🇺 AUDUSD Orders
        </a>
        <a href="<?php echo $api_base; ?>&filter_symbol=USDCAD" target="_blank" class="btn btn-info btn-test">
          🇨🇦 USDCAD Orders
        </a>
        <a href="<?php echo $api_base; ?>&filter_symbol=NZDUSD" target="_blank" class="btn btn-info btn-test">
          🇳🇿 NZDUSD Orders
        </a>
      </div>

      <div class="example-section">
        <h3>🔄 Combined Filters</h3>
        <p>Use multiple filters together:</p>
        
        <a href="<?php echo $api_base; ?>&filter_running=true&filter_symbol=EURUSD" target="_blank" class="btn btn-primary btn-test">
          🟢 Running EURUSD
        </a>
        <a href="<?php echo $api_base; ?>&filter_pending=true&filter_symbol=GBPUSD" target="_blank" class="btn btn-primary btn-test">
          🟡 Pending GBPUSD
        </a>
        <a href="<?php echo $api_base; ?>&filter_closed=true&filter_symbol=USDJPY" target="_blank" class="btn btn-primary btn-test">
          🔴 Closed USDJPY
        </a>
        <a href="<?php echo $api_base; ?>&filter_running=true&filter_profit=true&filter_symbol=EURUSD" target="_blank" class="btn btn-success btn-test">
          💚 EURUSD Running Profits
        </a>
        <a href="<?php echo $api_base; ?>&filter_running=true&filter_loss=true&filter_symbol=GBPUSD" target="_blank" class="btn btn-danger btn-test">
          💔 GBPUSD Running Losses
        </a>
      </div>
    </div>

    <!-- History Tab -->
    <div class="tab-pane fade" id="history" role="tabpanel">
      <h2>📈 Order History</h2>
      
      <div class="example-section">
        <h3>📅 Time-Based History</h3>
        <p>Retrieve trading history for different time periods:</p>
        
        <div class="row">
          <div class="col-md-6">
            <h5>Recent History</h5>
            <a href="<?php echo $api_base; ?>&history=today" target="_blank" class="btn btn-primary btn-test">
              📅 Today's Trades
            </a>
            <a href="<?php echo $api_base; ?>&history=last-hour" target="_blank" class="btn btn-primary btn-test">
              ⏰ Last Hour
            </a>
            <a href="<?php echo $api_base; ?>&history=last-10" target="_blank" class="btn btn-primary btn-test">
              🔟 Last 10 Orders
            </a>
            <a href="<?php echo $api_base; ?>&history=last-20" target="_blank" class="btn btn-primary btn-test">
              2️⃣0️⃣ Last 20 Orders
            </a>
          </div>
          <div class="col-md-6">
            <h5>Extended History</h5>
            <a href="<?php echo $api_base; ?>&history=last-7days" target="_blank" class="btn btn-info btn-test">
              📊 Last 7 Days
            </a>
            <a href="<?php echo $api_base; ?>&history=last-30days" target="_blank" class="btn btn-info btn-test">
              📈 Last 30 Days
            </a>
            <a href="<?php echo $api_base; ?>&history=this-week" target="_blank" class="btn btn-secondary btn-test">
              📅 This Week
            </a>
            <a href="<?php echo $api_base; ?>&history=this-month" target="_blank" class="btn btn-secondary btn-test">
              📅 This Month
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Profit Tab -->
    <div class="tab-pane fade" id="profit" role="tabpanel">
      <h2>💰 Profit Analysis</h2>
      
      <div class="example-section">
        <h3>📊 Performance Analysis</h3>
        <p>Comprehensive profit tracking and performance metrics:</p>
        
        <div class="row">
          <div class="col-md-6">
            <h5>Recent Performance</h5>
            <a href="<?php echo $api_base; ?>&profit=today" target="_blank" class="btn btn-success btn-test">
              💰 Today's Profit
            </a>
            <a href="<?php echo $api_base; ?>&profit=last-hour" target="_blank" class="btn btn-success btn-test">
              ⏰ Last Hour Profit
            </a>
            <a href="<?php echo $api_base; ?>&profit=this-week" target="_blank" class="btn btn-info btn-test">
              📅 This Week
            </a>
            <a href="<?php echo $api_base; ?>&profit=this-month" target="_blank" class="btn btn-info btn-test">
              📅 This Month
            </a>
          </div>
          <div class="col-md-6">
            <h5>Extended Analysis</h5>
            <a href="<?php echo $api_base; ?>&profit=last-7days" target="_blank" class="btn btn-primary btn-test">
              📊 Last 7 Days
            </a>
            <a href="<?php echo $api_base; ?>&profit=last-30days" target="_blank" class="btn btn-primary btn-test">
              📈 Last 30 Days
            </a>
            <a href="<?php echo $api_base; ?>&profit=last-3months" target="_blank" class="btn btn-secondary btn-test">
              📊 Last 3 Months
            </a>
            <a href="<?php echo $api_base; ?>&profit=last-6months" target="_blank" class="btn btn-secondary btn-test">
              📈 Last 6 Months
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Live Tests Tab -->
    <div class="tab-pane fade" id="examples" role="tabpanel">
      <h2>🧪 Live API Testing</h2>
      
      <div class="example-section">
        <h3>🚀 Quick Test Suite</h3>
        <p>Test all major API functions with real-time responses:</p>
        
        <div class="row">
          <div class="col-md-4">
            <h5>📊 Information</h5>
            <a href="<?php echo $api_base; ?>" target="_blank" class="btn btn-outline-primary btn-test w-100 mb-2">
              📋 All Orders
            </a>
            <a href="<?php echo $api_base; ?>&filter_running=true" target="_blank" class="btn btn-outline-success btn-test w-100 mb-2">
              🟢 Running Positions
            </a>
            <a href="<?php echo $api_base; ?>&profit=today" target="_blank" class="btn btn-outline-info btn-test w-100 mb-2">
              💰 Today's Profit
            </a>
            <a href="<?php echo $api_base; ?>&history=last-10" target="_blank" class="btn btn-outline-secondary btn-test w-100 mb-2">
              📈 Last 10 Trades
            </a>
          </div>
          <div class="col-md-4">
            <h5>💹 Trading</h5>
            <a href="<?php echo $api_base; ?>&action=BUY&symbol=EURUSD&volume=0.01" target="_blank" class="btn btn-outline-success btn-test w-100 mb-2">
              📈 Test BUY
            </a>
            <a href="<?php echo $api_base; ?>&action=SELL&symbol=EURUSD&volume=0.01" target="_blank" class="btn btn-outline-danger btn-test w-100 mb-2">
              📉 Test SELL
            </a>
            <a href="<?php echo $api_base; ?>&action=BUY_LIMIT&symbol=EURUSD&volume=0.01&price=1.0500" target="_blank" class="btn btn-outline-info btn-test w-100 mb-2">
              📊 Test BUY LIMIT
            </a>
            <a href="<?php echo $api_base; ?>&action=SELL_STOP&symbol=EURUSD&volume=0.01&price=1.2000" target="_blank" class="btn btn-outline-warning btn-test w-100 mb-2">
              🚦 Test SELL STOP
            </a>
          </div>
          <div class="col-md-4">
            <h5>⚙️ Management</h5>
            <a href="<?php echo $api_base; ?>&action=CLOSE_LOSS&symbol=ALL" target="_blank" class="btn btn-outline-secondary btn-test w-100 mb-2">
              💔 Close Losses
            </a>
            <a href="<?php echo $api_base; ?>&action=CLOSE_PROFIT&symbol=ALL" target="_blank" class="btn btn-outline-success btn-test w-100 mb-2">
              💚 Close Profits
            </a>
            <a href="<?php echo $api_base; ?>&action=BREAK_EVEN_ALL&symbol=ALL" target="_blank" class="btn btn-outline-primary btn-test w-100 mb-2">
              ⚖️ Break Even All
            </a>
            <a href="<?php echo $api_base; ?>&action=DELETE_ALL_ORDERS&symbol=ALL" target="_blank" class="btn btn-outline-info btn-test w-100 mb-2">
              🗑️ Delete Orders
            </a>
          </div>
        </div>
      </div>

      <div class="example-section">
        <h3>🎯 Symbol-Specific Tests</h3>
        <p>Test operations on specific currency pairs:</p>
        
        <div class="row">
          <div class="col-md-3">
            <h6>🇪🇺 EURUSD</h6>
            <a href="<?php echo $api_base; ?>&action=BUY&symbol=EURUSD&volume=0.01" target="_blank" class="btn btn-success btn-sm w-100 mb-1">BUY</a>
            <a href="<?php echo $api_base; ?>&action=SELL&symbol=EURUSD&volume=0.01" target="_blank" class="btn btn-danger btn-sm w-100 mb-1">SELL</a>
            <a href="<?php echo $api_base; ?>&filter_symbol=EURUSD" target="_blank" class="btn btn-info btn-sm w-100 mb-1">Orders</a>
            <a href="<?php echo $api_base; ?>&action=CLOSE&symbol=EURUSD" target="_blank" class="btn btn-secondary btn-sm w-100 mb-1">Close All</a>
          </div>
          <div class="col-md-3">
            <h6>🇬🇧 GBPUSD</h6>
            <a href="<?php echo $api_base; ?>&action=BUY&symbol=GBPUSD&volume=0.01" target="_blank" class="btn btn-success btn-sm w-100 mb-1">BUY</a>
            <a href="<?php echo $api_base; ?>&action=SELL&symbol=GBPUSD&volume=0.01" target="_blank" class="btn btn-danger btn-sm w-100 mb-1">SELL</a>
            <a href="<?php echo $api_base; ?>&filter_symbol=GBPUSD" target="_blank" class="btn btn-info btn-sm w-100 mb-1">Orders</a>
            <a href="<?php echo $api_base; ?>&action=CLOSE&symbol=GBPUSD" target="_blank" class="btn btn-secondary btn-sm w-100 mb-1">Close All</a>
          </div>
          <div class="col-md-3">
            <h6>🇯🇵 USDJPY</h6>
            <a href="<?php echo $api_base; ?>&action=BUY&symbol=USDJPY&volume=0.01" target="_blank" class="btn btn-success btn-sm w-100 mb-1">BUY</a>
            <a href="<?php echo $api_base; ?>&action=SELL&symbol=USDJPY&volume=0.01" target="_blank" class="btn btn-danger btn-sm w-100 mb-1">SELL</a>
            <a href="<?php echo $api_base; ?>&filter_symbol=USDJPY" target="_blank" class="btn btn-info btn-sm w-100 mb-1">Orders</a>
            <a href="<?php echo $api_base; ?>&action=CLOSE&symbol=USDJPY" target="_blank" class="btn btn-secondary btn-sm w-100 mb-1">Close All</a>
          </div>
          <div class="col-md-3">
            <h6>🇦🇺 AUDUSD</h6>
            <a href="<?php echo $api_base; ?>&action=BUY&symbol=AUDUSD&volume=0.01" target="_blank" class="btn btn-success btn-sm w-100 mb-1">BUY</a>
            <a href="<?php echo $api_base; ?>&action=SELL&symbol=AUDUSD&volume=0.01" target="_blank" class="btn btn-danger btn-sm w-100 mb-1">SELL</a>
            <a href="<?php echo $api_base; ?>&filter_symbol=AUDUSD" target="_blank" class="btn btn-info btn-sm w-100 mb-1">Orders</a>
            <a href="<?php echo $api_base; ?>&action=CLOSE&symbol=AUDUSD" target="_blank" class="btn btn-secondary btn-sm w-100 mb-1">Close All</a>
          </div>
        </div>
      </div>

      <div class="example-section">
        <h3>📊 URL Examples</h3>
        <p>Copy these URLs for your own applications:</p>
        
        <div class="code-block">
<pre># Market Orders
<?php echo $api_base; ?>&action=BUY&symbol=EURUSD&volume=0.01
<?php echo $api_base; ?>&action=SELL&symbol=GBPUSD&volume=0.01&sl=1.2500&tp=1.2300

# Pending Orders  
<?php echo $api_base; ?>&action=BUY_LIMIT&symbol=EURUSD&volume=0.01&price=1.1000
<?php echo $api_base; ?>&action=SELL_STOP&symbol=GBPUSD&volume=0.01&price=1.2400

# Position Management
<?php echo $api_base; ?>&action=CLOSE_LOSS&symbol=ALL
<?php echo $api_base; ?>&action=BREAK_EVEN_ALL&symbol=ALL

# Information Queries
<?php echo $api_base; ?>&filter_running=true
<?php echo $api_base; ?>&history=today
<?php echo $api_base; ?>&profit=today</pre>
        </div>
      </div>
    </div>
  </div>

  <div class="mt-5 p-4" style="background-color: #1a1a1a; border-radius: 8px;">
    <h4>🔗 Quick Reference</h4>
    <div class="row">
      <div class="col-md-6">
        <p><strong>API Endpoint:</strong><br>
        <code><?php echo BASE_URL; ?>/orders-api-v1/orders-api.php</code></p>
        <p><strong>Your API Key:</strong><br>
        <code><?php echo DEFAULT_API_KEY; ?></code></p>
      </div>
      <div class="col-md-6">
        <p><strong>Support:</strong> All MT5 trading operations</p>
        <p><strong>Response Format:</strong> JSON</p>
        <p><strong>Authentication:</strong> API Key required</p>
        <p><strong>Rate Limiting:</strong> Based on quota allocation</p>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>