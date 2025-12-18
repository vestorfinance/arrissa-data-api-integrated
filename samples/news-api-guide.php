<?php
// Include password protection
require_once 'password_protect.php';

// Include shared configuration
require_once '../app_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo APP_TITLE; ?> - Economic News API Guide</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<!-- Logout Button -->
<a href="logout.php" class="btn btn-outline-danger btn-sm logout-btn">🔓 Logout</a>

<div class="container py-5">
  <p><a href="index.php" class="btn btn-outline-light mb-4">← Back to Home</a></p>
  <h1 class="mb-4">📊 <?php echo APP_TITLE; ?> Economic News API Guide</h1>

  <div class="alert alert-info mb-4">
    <h5>🔗 API Endpoint</h5>
    <strong>Base URL:</strong><br>
    <code><?php echo BASE_URL; ?>/news-api-v1/news-api.php</code>
  </div>

  <div class="alert alert-info mb-4">
    <h5>🔑 Your API Key</h5>
    <strong>Default API Key:</strong><br>
    <code><?php echo DEFAULT_API_KEY; ?></code>
  </div>

  <p>This API provides comprehensive access to historical and scheduled economic event data with advanced filtering capabilities. 
  It supports date/time ranges, currency filtering, event-specific queries, and flexible display formats with API key authentication.</p>

  <h2 class="mt-5">🔐 Authentication</h2>
  <p>All requests require an API key parameter for authentication and quota tracking:</p>
  <div class="code-block">
<pre><?php echo BASE_URL; ?>/news-api-v1/news-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&currency=USD&period=today</pre>
  </div>

  <h2 class="mt-5">🔧 Parameters</h2>
  <table class="table table-dark table-bordered">
    <thead>
      <tr><th>Parameter</th><th>Type</th><th>Required</th><th>Description</th></tr>
    </thead>
    <tbody>
      <tr>
        <td><span class="badge badge-custom">api_key</span></td>
        <td>string</td>
        <td>✅ Always</td>
        <td>Your unique API key for authentication and quota tracking.</td>
      </tr>
      <tr><td>start_date</td><td>string (YYYY-MM-DD)</td><td>✅ if no period</td><td>Start date for filtering events</td></tr>
      <tr><td>start_time</td><td>string (HH:MM:SS)</td><td>✅ if no period</td><td>Start time for filtering events (UTC)</td></tr>
      <tr><td>end_date</td><td>string (YYYY-MM-DD)</td><td>✅ if no period</td><td>End date for filtering events</td></tr>
      <tr><td>end_time</td><td>string (HH:MM:SS)</td><td>✅ if no period</td><td>End time for filtering events (UTC)</td></tr>
      <tr><td>period</td><td>string</td><td>✅ if no date/time</td><td>Predefined time range (see supported periods below)</td></tr>
      <tr><td>currency</td><td>string (e.g., USD)</td><td>❌</td><td>Filter events by specific currency</td></tr>
      <tr><td>currency_exclude</td><td>string (comma-separated)</td><td>❌</td><td>Exclude specified currencies (e.g., "USD,GBP")</td></tr>
      <tr><td>event_id</td><td>string (comma-separated)</td><td>❌</td><td>Filter by consistent event IDs for grouped results</td></tr>
      <tr><td>display</td><td>string ("min")</td><td>❌</td><td>Returns minimal data fields when set to "min"</td></tr>
      <tr><td>pretend_now_date</td><td>string (YYYY-MM-DD)</td><td>❌</td><td>Override "now" date for relative period calculations</td></tr>
      <tr><td>pretend_now_time</td><td>string (HH:MM)</td><td>❌</td><td>Override "now" time for relative period calculations</td></tr>
      <tr><td>future_limit</td><td>string</td><td>❌</td><td>Limit future events scope (see future limits below)</td></tr>
      <tr><td>spit_out</td><td>string ("all")</td><td>❌</td><td>Include all events for period (past & future) when set to "all"</td></tr>
    </tbody>
  </table>

  <h2 class="mt-5">🗓️ Supported Period Values</h2>
  <div class="row">
    <div class="col-md-6">
      <h6>Fixed Periods:</h6>
      <ul>
        <li><code>today</code> - Current day from 00:00 to now</li>
        <li><code>yesterday</code> - Previous day (full 24h)</li>
        <li><code>this-week</code> - Sunday to now</li>
        <li><code>last-week</code> - Previous full week</li>
        <li><code>this-month</code> - 1st of month to now</li>
        <li><code>last-month</code> - Previous full month</li>
        <li><code>this-year</code> - Jan 1st to now</li>
        <li><code>future</code> - All future events</li>
      </ul>
    </div>
    <div class="col-md-6">
      <h6>Rolling Periods:</h6>
      <ul>
        <li><code>last-7-days</code> - Last 7 days from now</li>
        <li><code>last-14-days</code> - Last 14 days from now</li>
        <li><code>last-30-days</code> - Last 30 days from now</li>
        <li><code>last-3-months</code> - Last 3 months from now</li>
        <li><code>last-6-months</code> - Last 6 months from now</li>
        <li><code>last-12-months</code> - Last 12 months from now</li>
        <li><code>last-2-years</code> - Previous 2 full years</li>
      </ul>
    </div>
  </div>

  <h5 class="mt-4">🎯 Custom Period Format</h5>
  <p>You can also use flexible custom periods with the format <code>last-N-unit</code>:</p>
  <div class="code-block">
<pre># Examples of custom periods:
last-3-hours     # Last 3 hours from now
last-21-days     # Last 21 days from now  
last-8-weeks     # Last 8 weeks from now
last-18-months   # Last 18 months from now
last-5-years     # Last 5 years from now</pre>
  </div>

  <h2 class="mt-5">🔮 Future Limit Options</h2>
  <p>When using <code>period=future</code>, you can limit the scope with <code>future_limit</code>:</p>
  <ul>
    <li><code>today</code> - Rest of today only</li>
    <li><code>tomorrow</code> - Tomorrow's events only</li>
    <li><code>next-2-days</code> - Next 2 days from tomorrow</li>
    <li><code>this-week</code> - Rest of current week</li>
    <li><code>next-week</code> - Next full week</li>
  </ul>

  <h2 class="mt-5">📊 Query Modes</h2>
  
  <h5>🔄 1. Period-Based Queries</h5>
  <p>Use predefined or custom periods for quick data retrieval:</p>
  <div class="code-block">
<pre># Get today's USD events
<?php echo BASE_URL; ?>/news-api-v1/news-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&currency=USD&period=today

# Get last 3 months of EUR events  
<?php echo BASE_URL; ?>/news-api-v1/news-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&currency=EUR&period=last-3-months

# Custom period: last 15 days
<?php echo BASE_URL; ?>/news-api-v1/news-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&period=last-15-days</pre>
  </div>

  <h5 class="mt-4">📅 2. Explicit Date/Time Queries</h5>
  <p>Specify exact start and end timestamps:</p>
  <div class="code-block">
<pre># Specific date range
<?php echo BASE_URL; ?>/news-api-v1/news-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&start_date=2025-04-01&start_time=00:00:00&end_date=2025-04-10&end_time=23:59:59</pre>
  </div>

  <h5 class="mt-4">🎯 3. Event-Specific Queries</h5>
  <p>Filter by specific event IDs for grouped results:</p>
  <div class="code-block">
<pre># Get CPI and GDP events
<?php echo BASE_URL; ?>/news-api-v1/news-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&event_id=CPI_US,GDP_US&period=last-month</pre>
  </div>

  <h5 class="mt-4">🔮 4. Future Event Queries</h5>
  <p>Get upcoming events with optional limits:</p>
  <div class="code-block">
<pre># All future USD events
<?php echo BASE_URL; ?>/news-api-v1/news-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&currency=USD&period=future

# Only tomorrow's events
<?php echo BASE_URL; ?>/news-api-v1/news-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&period=future&future_limit=tomorrow</pre>
  </div>

  <h2 class="mt-5">📥 Example Requests</h2>
  <div class="row">
    <div class="col-md-6">
      <h6>Basic Queries:</h6>
      <ul>
        <li><a href="<?php echo BASE_URL; ?>/news-api-v1/news-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&currency=USD&period=today" target="_blank">Today's USD Events</a></li>
        <li><a href="<?php echo BASE_URL; ?>/news-api-v1/news-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&currency=EUR&period=this-week" target="_blank">This Week's EUR Events</a></li>
        <li><a href="<?php echo BASE_URL; ?>/news-api-v1/news-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&period=last-7-days&display=min" target="_blank">Last 7 Days (Minimal)</a></li>
      </ul>
    </div>
    <div class="col-md-6">
      <h6>Advanced Queries:</h6>
      <ul>
        <li><a href="<?php echo BASE_URL; ?>/news-api-v1/news-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&currency_exclude=USD,GBP&period=last-month" target="_blank">Exclude USD & GBP</a></li>
        <li><a href="<?php echo BASE_URL; ?>/news-api-v1/news-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&event_id=CPI_US,CORECPI_US&period=last-6-months" target="_blank">Specific Event IDs</a></li>
        <li><a href="<?php echo BASE_URL; ?>/news-api-v1/news-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&period=future&future_limit=next-week" target="_blank">Next Week's Future Events</a></li>
      </ul>
    </div>
  </div>

  <h2 class="mt-5">📤 Response Formats</h2>
  
  <div class="alert alert-warning">
    <strong>⚠️ Future Events:</strong> For any event scheduled in the future, <code>actual_value</code> will automatically be set to <strong>"TBD"</strong>.
  </div>

  <h5>✅ Standard Format (Flat List)</h5>
  <div class="code-block">
<pre>{
  "response_for": "USD today events",
  "vestor_data": {
    "count": 2,
    "events": [
      {
        "event_name": "CPI (MoM) (Mar)",
        "event_date": "2025-03-12",
        "event_time": "12:30:00",
        "currency": "USD",
        "forecast_value": "0.3%",
        "actual_value": "0.4%",
        "previous_value": "0.2%",
        "consistent_event_id": "CPI_US",
        "impact": "High"
      },
      {
        "event_name": "Retail Sales (MoM)",
        "event_date": "2025-09-01", 
        "event_time": "08:30:00",
        "currency": "USD",
        "forecast_value": "1.1%",
        "actual_value": "TBD",
        "previous_value": "0.9%",
        "consistent_event_id": "RETAIL_SALES_US",
        "impact": "Medium"
      }
    ]
  }
}</pre>
  </div>

  <h5 class="mt-4">📊 Grouped Format (When event_id Used)</h5>
  <div class="code-block">
<pre>{
  "response_for": "USD last-month events",
  "vestor_data": {
    "cpi": {
      "count": 2,
      "events": [
        {
          "event_name": "CPI (MoM) (Mar)",
          "event_date": "2025-03-12",
          "event_time": "12:30:00",
          "currency": "USD",
          "forecast_value": "0.3%",
          "actual_value": "0.4%",
          "previous_value": "0.2%"
        },
        {
          "event_name": "CPI (YoY) (Mar)", 
          "event_date": "2025-03-12",
          "event_time": "12:30:00",
          "currency": "USD",
          "forecast_value": "1.8%",
          "actual_value": "TBD",
          "previous_value": "1.7%"
        }
      ]
    }
  }
}</pre>
  </div>

  <h5 class="mt-4">🎯 Minimal Format (display=min)</h5>
  <div class="code-block">
<pre>{
  "response_for": "USD today events",
  "vestor_data": {
    "count": 1,
    "events": [
      {
        "event_name": "CPI (MoM) (Mar)",
        "event_date": "2025-03-12", 
        "event_time": "12:30:00",
        "currency": "USD",
        "forecast_value": "0.3%",
        "actual_value": "0.4%",
        "previous_value": "0.2%"
      }
    ]
  }
}</pre>
  </div>

  <h5 class="mt-4">❌ Error Response</h5>
  <div class="code-block">
<pre>{
  "vestor_data": {
    "error": "Missing required parameters. Use start_date, start_time, end_date, and end_time, or provide a period parameter."
  }
}</pre>
  </div>

  <h2 class="mt-5">🔒 Authentication & Quota Management</h2>
  <p>The API implements comprehensive authentication and usage tracking:</p>
  <ul>
    <li><strong>API Key Validation:</strong> Each request validates the provided API key</li>
    <li><strong>Account Status:</strong> Checks if account is active and within validity period</li>
    <li><strong>Quota Tracking:</strong> Monitors usage against allocated quota limits</li>
    <li><strong>Automatic Consumption:</strong> Each successful request consumes 1 quota unit</li>
  </ul>

  <div class="alert alert-info">
    <strong>Authentication Errors:</strong>
    <ul class="mb-0">
      <li><code>Missing API key</code> - No api_key parameter provided</li>
      <li><code>Invalid API key</code> - API key not found in system</li>
      <li><code>API key is not active</code> - Account is disabled</li>
      <li><code>API key not yet active</code> - Before activation date</li>
      <li><code>API key has expired</code> - Past expiry date</li>
      <li><code>Quota exceeded</code> - Used all allocated requests</li>
    </ul>
  </div>

  <h2 class="mt-5">⏰ Pretend Time Feature</h2>
  <p>Test relative periods from any historical perspective:</p>
  <div class="code-block">
<pre># Get "today's" events as if it were January 15, 2025 at 14:30 UTC
<?php echo BASE_URL; ?>/news-api-v1/news-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&currency=EUR&period=today&pretend_now_date=2025-01-15&pretend_now_time=14:30

# Get "last week" from a specific historical point
<?php echo BASE_URL; ?>/news-api-v1/news-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&period=last-week&pretend_now_date=2025-02-01</pre>
  </div>

  <h2 class="mt-5">✅ Best Practices & Tips</h2>
  <ul>
    <li><strong>Performance:</strong> Use <code>display=min</code> for faster responses with essential data only</li>
    <li><strong>Currency Filtering:</strong> Combine <code>currency</code> with periods for targeted data</li>
    <li><strong>Exclusions:</strong> Use <code>currency_exclude</code> to remove unwanted currencies from broad queries</li>
    <li><strong>Future Events:</strong> Remember that <code>actual_value</code> is always "TBD" for future events</li>
    <li><strong>Time Zones:</strong> All timestamps are in UTC timezone</li>
    <li><strong>URL Encoding:</strong> Always URL-encode event_id values containing special characters</li>
    <li><strong>Quota Management:</strong> Monitor your usage to avoid service interruption</li>
    <li><strong>Testing:</strong> Use <code>pretend_now_date/time</code> for historical testing scenarios</li>
  </ul>

  <h2 class="mt-5">🔗 Quick Test Links</h2>
  <div class="row">
    <div class="col-md-6">
      <h6>Period-Based Tests:</h6>
      <ul>
        <li><a href="<?php echo BASE_URL; ?>/news-api-v1/news-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&currency=USD&period=today" target="_blank">USD Today</a></li>
        <li><a href="<?php echo BASE_URL; ?>/news-api-v1/news-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&currency=EUR&period=this-week" target="_blank">EUR This Week</a></li>
        <li><a href="<?php echo BASE_URL; ?>/news-api-v1/news-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&period=last-30-days&display=min" target="_blank">Last 30 Days (Min)</a></li>
      </ul>
    </div>
    <div class="col-md-6">
      <h6>Advanced Tests:</h6>
      <ul>
        <li><a href="<?php echo BASE_URL; ?>/news-api-v1/news-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&currency_exclude=USD,GBP&period=last-week" target="_blank">Exclude USD & GBP</a></li>
        <li><a href="<?php echo BASE_URL; ?>/news-api-v1/news-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&period=future&future_limit=tomorrow" target="_blank">Tomorrow's Events</a></li>
        <li><a href="<?php echo BASE_URL; ?>/news-api-v1/news-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&period=today&spit_out=all" target="_blank">All Today (Past & Future)</a></li>
      </ul>
    </div>
  </div>

  <div class="row mt-3">
    <div class="col-md-6">
      <h6>Custom Period Tests:</h6>
      <ul>
        <li><a href="<?php echo BASE_URL; ?>/news-api-v1/news-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&period=last-15-days" target="_blank">Last 15 Days</a></li>
        <li><a href="<?php echo BASE_URL; ?>/news-api-v1/news-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&currency=JPY&period=last-8-weeks" target="_blank">JPY Last 8 Weeks</a></li>
      </ul>
    </div>
    <div class="col-md-6">
      <h6>Pretend Time Tests:</h6>
      <ul>
        <li><a href="<?php echo BASE_URL; ?>/news-api-v1/news-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&period=today&pretend_now_date=2025-01-15&pretend_now_time=14:30" target="_blank">Pretend Jan 15, 2025</a></li>
        <li><a href="<?php echo BASE_URL; ?>/news-api-v1/news-api.php?api_key=<?php echo DEFAULT_API_KEY; ?>&currency=GBP&period=last-week&pretend_now_date=2025-02-01" target="_blank">GBP Last Week from Feb 1</a></li>
      </ul>
    </div>
  </div>

  <div class="mt-5 p-3" style="background-color: #1a1a1a; border-radius: 8px;">
    <p class="mb-0"><strong>Note:</strong> All examples use your configured API key: <code><?php echo DEFAULT_API_KEY; ?></code> and the <?php echo APP_TITLE; ?> API endpoint at <code><?php echo BASE_URL; ?>/news-api-v1/news-api.php</code></p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>