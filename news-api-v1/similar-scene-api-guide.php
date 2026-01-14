<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Similar Scene API Market Guide</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    pre { background-color: #212529; color: #f8f9fa; padding: 1rem; border-radius: 8px; overflow-x: auto; }
    .code-title { font-weight: bold; color: #0d6efd; margin-top: 1rem; }
    .badge-auth { background-color: #dc3545; }
    .badge-optional { background-color: #6c757d; }
    code { background-color: #2d3238; padding: 2px 6px; border-radius: 4px; }
  </style>
</head>
<body class="bg-dark text-light">
<div class="container py-5">

  <h1 class="mb-4 text-info">📊 Similar Scene API Market Guide</h1>
  <p class="lead">Find historical event occurrences with synchronized market data from multiple timeframes.</p>
  <p><strong>Base URL:</strong> <code>http://localhost/news-api-v1/similar-scene-api.php</code></p>

  <div class="alert alert-warning" role="alert">
    <strong>🔐 Authentication Required:</strong> All requests must include a valid <code>api_key</code> parameter.
  </div>

  <hr>

  <h2>🔧 HTTP Method</h2>
  <p><code>GET</code></p>

  <hr>

  <h2>🧩 Query Parameters</h2>
  <table class="table table-dark table-striped table-hover">
    <thead>
      <tr>
        <th>Parameter</th>
        <th>Required</th>
        <th>Description</th>
        <th>Example</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><code>api_key</code></td>
        <td><span class="badge badge-auth">✅ Required</span></td>
        <td>Your API authentication key</td>
        <td>your_secret_key_here</td>
      </tr>
      <tr>
        <td><code>event_id</code></td>
        <td><span class="badge badge-auth">✅ Required</span></td>
        <td>Comma-separated list of consistent event IDs to match simultaneously</td>
        <td>CPI_US,CORECPI_US</td>
      </tr>
      <tr>
        <td><code>symbol</code></td>
        <td><span class="badge badge-optional">❌ Optional</span></td>
        <td>Trading symbol for market data (default: <code>XAUUSD</code>)</td>
        <td>EURUSD, XAUUSD, US30</td>
      </tr>
      <tr>
        <td><code>period</code></td>
        <td><span class="badge badge-optional">❌ Optional</span></td>
        <td>Predefined time range filter</td>
        <td>last-month, today</td>
      </tr>
      <tr>
        <td><code>start_date</code></td>
        <td><span class="badge badge-optional">❌ Optional</span></td>
        <td>Custom range start date (requires start_time, end_date, end_time)</td>
        <td>2025-04-01</td>
      </tr>
      <tr>
        <td><code>start_time</code></td>
        <td><span class="badge badge-optional">❌ Optional</span></td>
        <td>Custom range start time</td>
        <td>00:00:00</td>
      </tr>
      <tr>
        <td><code>end_date</code></td>
        <td><span class="badge badge-optional">❌ Optional</span></td>
        <td>Custom range end date</td>
        <td>2025-04-30</td>
      </tr>
      <tr>
        <td><code>end_time</code></td>
        <td><span class="badge badge-optional">❌ Optional</span></td>
        <td>Custom range end time</td>
        <td>23:59:59</td>
      </tr>
      <tr>
        <td><code>display</code></td>
        <td><span class="badge badge-optional">❌ Optional</span></td>
        <td>Set to <code>min</code> for minimal output fields</td>
        <td>min</td>
      </tr>
      <tr>
        <td><code>output</code></td>
        <td><span class="badge badge-optional">❌ Optional</span></td>
        <td>Set to <code>all</code> to include all events at occurrence time</td>
        <td>all</td>
      </tr>
      <tr>
        <td><code>currency</code></td>
        <td><span class="badge badge-optional">❌ Optional</span></td>
        <td>Filter economic events by currency code</td>
        <td>USD, EUR, GBP</td>
      </tr>
    </tbody>
  </table>

  <hr>

  <h2>⏱ Supported Period Values</h2>
  <div class="row">
    <div class="col-md-6">
      <ul>
        <li><code>today</code></li>
        <li><code>yesterday</code></li>
        <li><code>this-week</code></li>
        <li><code>last-week</code></li>
        <li><code>this-month</code></li>
        <li><code>last-month</code></li>
        <li><code>last-3-months</code></li>
      </ul>
    </div>
    <div class="col-md-6">
      <ul>
        <li><code>last-6-months</code></li>
        <li><code>last-7-days</code></li>
        <li><code>last-14-days</code></li>
        <li><code>last-30-days</code></li>
        <li><code>this-year</code></li>
        <li><code>last-12-months</code></li>
        <li><code>last-2-years</code></li>
        <li><code>future</code></li>
      </ul>
    </div>
  </div>

  <hr>

  <h2>📤 Example Requests</h2>
  <div class="accordion" id="exampleAccordion">
    
    <div class="accordion-item bg-dark text-light">
      <h2 class="accordion-header">
        <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#ex1">
          🔹 Basic Query (Find CPI & Core CPI occurrences)
        </button>
      </h2>
      <div id="ex1" class="accordion-collapse collapse" data-bs-parent="#exampleAccordion">
        <div class="accordion-body">
          <p>Find all historical occurrences where both CPI and Core CPI were released simultaneously:</p>
          <pre><code>http://localhost/news-api-v1/similar-scene-api.php?api_key=YOUR_API_KEY&event_id=CPI_US,CORECPI_US</code></pre>
          <p class="text-muted small">This returns XAUUSD (Gold) market data by default.</p>
        </div>
      </div>
    </div>

    <div class="accordion-item bg-dark text-light">
      <h2 class="accordion-header">
        <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#ex2">
          🔹 With Custom Symbol (EURUSD)
        </button>
      </h2>
      <div id="ex2" class="accordion-collapse collapse" data-bs-parent="#exampleAccordion">
        <div class="accordion-body">
          <p>Get market data for EURUSD instead of default XAUUSD:</p>
          <pre><code>http://localhost/news-api-v1/similar-scene-api.php?api_key=YOUR_API_KEY&event_id=NFP_US&symbol=EURUSD</code></pre>
        </div>
      </div>
    </div>

    <div class="accordion-item bg-dark text-light">
      <h2 class="accordion-header">
        <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#ex3">
          🔹 Minimal Display Mode
        </button>
      </h2>
      <div id="ex3" class="accordion-collapse collapse" data-bs-parent="#exampleAccordion">
        <div class="accordion-body">
          <p>Get only essential event and candle data (no extra metadata):</p>
          <pre><code>http://localhost/news-api-v1/similar-scene-api.php?api_key=YOUR_API_KEY&event_id=CPI_US,CORECPI_US&display=min</code></pre>
        </div>
      </div>
    </div>

    <div class="accordion-item bg-dark text-light">
      <h2 class="accordion-header">
        <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#ex4">
          🔹 All Events at Occurrence Time
        </button>
      </h2>
      <div id="ex4" class="accordion-collapse collapse" data-bs-parent="#exampleAccordion">
        <div class="accordion-body">
          <p>Include all economic events that occurred at the same time, not just the specified IDs:</p>
          <pre><code>http://localhost/news-api-v1/similar-scene-api.php?api_key=YOUR_API_KEY&event_id=CPI_US,CORECPI_US&output=all</code></pre>
        </div>
      </div>
    </div>

    <div class="accordion-item bg-dark text-light">
      <h2 class="accordion-header">
        <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#ex5">
          🔹 Filter by Period (Last Month)
        </button>
      </h2>
      <div id="ex5" class="accordion-collapse collapse" data-bs-parent="#exampleAccordion">
        <div class="accordion-body">
          <p>Only return occurrences from last month:</p>
          <pre><code>http://localhost/news-api-v1/similar-scene-api.php?api_key=YOUR_API_KEY&event_id=NFP_US&period=last-month</code></pre>
        </div>
      </div>
    </div>

    <div class="accordion-item bg-dark text-light">
      <h2 class="accordion-header">
        <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#ex6">
          🔹 Custom Date Range
        </button>
      </h2>
      <div id="ex6" class="accordion-collapse collapse" data-bs-parent="#exampleAccordion">
        <div class="accordion-body">
          <p>Query specific date range:</p>
          <pre><code>http://localhost/news-api-v1/similar-scene-api.php?api_key=YOUR_API_KEY&event_id=CPI_US,CORECPI_US&start_date=2025-04-01&start_time=00:00:00&end_date=2025-04-30&end_time=23:59:59</code></pre>
        </div>
      </div>
    </div>

    <div class="accordion-item bg-dark text-light">
      <h2 class="accordion-header">
        <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#ex7">
          🔹 Filter by Currency
        </button>
      </h2>
      <div id="ex7" class="accordion-collapse collapse" data-bs-parent="#exampleAccordion">
        <div class="accordion-body">
          <p>Only include USD economic events:</p>
          <pre><code>http://localhost/news-api-v1/similar-scene-api.php?api_key=YOUR_API_KEY&event_id=CPI_US,CORECPI_US&currency=USD</code></pre>
        </div>
      </div>
    </div>

    <div class="accordion-item bg-dark text-light">
      <h2 class="accordion-header">
        <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#ex8">
          🔹 Combined: US30 + Last 7 Days + Minimal
        </button>
      </h2>
      <div id="ex8" class="accordion-collapse collapse" data-bs-parent="#exampleAccordion">
        <div class="accordion-body">
          <p>Get US30 (Dow Jones) data for last week with minimal output:</p>
          <pre><code>http://localhost/news-api-v1/similar-scene-api.php?api_key=YOUR_API_KEY&event_id=VPRWG&symbol=US30&period=last-7-days&display=min</code></pre>
        </div>
      </div>
    </div>

  </div>

  <hr>

  <h2>📊 Response Structure</h2>
  <p>The API returns matching occurrences with enriched market data across multiple timeframes:</p>
  
  <h3 class="mt-4">🔸 Market Data Windows</h3>
  <table class="table table-dark table-bordered">
    <thead>
      <tr>
        <th>Data Key</th>
        <th>Timeframe</th>
        <th>Description</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><code>{symbol}_data</code></td>
        <td>M1 (1-minute)</td>
        <td>Main window: T-1 minute to T+5 minutes around event time</td>
      </tr>
      <tr>
        <td><code>candle_30min</code></td>
        <td>M30 (30-minute)</td>
        <td>30-minute candle after event</td>
      </tr>
      <tr>
        <td><code>candle_1h</code></td>
        <td>H1 (1-hour)</td>
        <td>1-hour candle after event</td>
      </tr>
      <tr>
        <td><code>candle_4h</code></td>
        <td>H4 (4-hour)</td>
        <td>4-hour candle after event</td>
      </tr>
      <tr>
        <td><code>candle_close_day</code></td>
        <td>D1 (Daily)</td>
        <td>Close of day candle for event date</td>
      </tr>
    </tbody>
  </table>

  <h3 class="mt-4">📝 Sample Response</h3>
  <p class="code-title">Full Response (default mode):</p>
  <pre><code>{
  "vestor_data": {
    "occurrence_1": {
      "occurrence_date": "2025-04-10",
      "occurrence_time": "12:30:00",
      "events": [
        {
          "id": "12345",
          "event_name": "Core CPI (MoM) (Mar)",
          "consistent_event_id": "CORECPI_US",
          "currency": "USD",
          "forecast_value": "0.3",
          "actual_value": "0.1",
          "previous_value": "0.2",
          "impact": "high"
        },
        {
          "id": "12346",
          "event_name": "CPI (MoM) (Mar)",
          "consistent_event_id": "CPI_US",
          "currency": "USD",
          "forecast_value": "0.1",
          "actual_value": "-0.1",
          "previous_value": "0.2",
          "impact": "high"
        }
      ],
      "xauusd_data": [
        {
          "date": "2025-04-10",
          "time": "12:29:00",
          "open": "2034.50",
          "high": "2035.80",
          "low": "2033.20",
          "close": "2034.90"
        },
        {
          "date": "2025-04-10",
          "time": "12:30:00",
          "open": "2034.90",
          "high": "2038.50",
          "low": "2034.50",
          "close": "2037.20"
        }
      ],
      "candle_30min": {
        "date": "2025-04-10",
        "time": "13:00:00",
        "open": "2037.20",
        "high": "2040.10",
        "low": "2036.50",
        "close": "2039.80"
      },
      "candle_1h": {
        "date": "2025-04-10",
        "time": "13:30:00",
        "open": "2039.80",
        "high": "2042.30",
        "low": "2038.90",
        "close": "2041.50"
      },
      "candle_4h": {
        "date": "2025-04-10",
        "time": "16:30:00",
        "open": "2041.50",
        "high": "2045.70",
        "low": "2040.20",
        "close": "2044.30"
      },
      "candle_close_day": {
        "date": "2025-04-10",
        "time": "23:59:00",
        "open": "2030.10",
        "high": "2045.70",
        "low": "2028.50",
        "close": "2043.20"
      }
    },
    "occurrence_2": {
      "occurrence_date": "2025-03-12",
      "occurrence_time": "12:30:00",
      "events": [...],
      "xauusd_data": [...],
      ...
    }
  }
}</code></pre>

  <p class="code-title mt-4">Minimal Response (<code>display=min</code>):</p>
  <pre><code>{
  "vestor_data": {
    "occurrence_1": {
      "occurrence_date": "2025-04-10",
      "occurrence_time": "12:30:00",
      "events": [
        {
          "event_name": "Core CPI (MoM) (Mar)",
          "forecast_value": "0.3",
          "actual_value": "0.1",
          "previous_value": "0.2"
        }
      ],
      "xauusd_data": [
        {
          "time": "12:29:00",
          "open": "2034.50",
          "high": "2035.80",
          "low": "2033.20",
          "close": "2034.90"
        }
      ],
      "candle_30min": {
        "time": "13:00:00",
        "open": "2037.20",
        "high": "2040.10",
        "low": "2036.50",
        "close": "2039.80"
      },
      ...
    }
  }
}</code></pre>

  <hr>

  <h2>⚠️ Error Responses</h2>
  <table class="table table-dark table-hover">
    <thead>
      <tr>
        <th>Error</th>
        <th>Response</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>Missing API Key</td>
        <td><code>{"vestor_data":{"error":"Missing API key."}}</code></td>
      </tr>
      <tr>
        <td>Invalid API Key</td>
        <td><code>{"vestor_data":{"error":"Invalid API key."}}</code></td>
      </tr>
      <tr>
        <td>Missing event_id</td>
        <td><code>{"vestor_data":{"error":"Missing required parameter: event_id"}}</code></td>
      </tr>
      <tr>
        <td>Invalid period</td>
        <td><code>{"vestor_data":{"error":"Invalid period parameter."}}</code></td>
      </tr>
      <tr>
        <td>Market Data API Error</td>
        <td><code>{"vestor_data":{"error":"Failed to fetch market data","http_code":500}}</code></td>
      </tr>
      <tr>
        <td>Database Error</td>
        <td><code>{"vestor_data":{"error":"&lt;PDO exception message&gt;"}}</code></td>
      </tr>
    </tbody>
  </table>

  <hr>

  <h2>💡 Use Cases</h2>
  <div class="row">
    <div class="col-md-6">
      <h4>📈 Pattern Analysis</h4>
      <p>Analyze how markets react to specific economic events by comparing historical occurrences with synchronized OHLC data.</p>
    </div>
    <div class="col-md-6">
      <h4>🤖 Machine Learning Training</h4>
      <p>Build training datasets with labeled event-market data for predictive models.</p>
    </div>
    <div class="col-md-6">
      <h4>📊 Backtesting Strategies</h4>
      <p>Test trading strategies against historical event scenarios with accurate market data.</p>
    </div>
    <div class="col-md-6">
      <h4>🔍 Event Correlation</h4>
      <p>Find occurrences where multiple events were released simultaneously and study their combined market impact.</p>
    </div>
  </div>

  <hr>

  <h2>🚀 Integration Tips</h2>
  <ul>
    <li><strong>Authentication:</strong> Store your API key securely and include it in every request.</li>
    <li><strong>Rate Limiting:</strong> Consider implementing caching for frequently accessed historical data.</li>
    <li><strong>Timeframe Strategy:</strong> Use the main window (M1) for immediate reactions and higher timeframes for trend analysis.</li>
    <li><strong>Symbol Selection:</strong> Match symbols to currency pairs affected by events (e.g., USD events → EURUSD, XAUUSD).</li>
    <li><strong>Data Processing:</strong> Market data is fetched from the Market Data API queue system backed by MT5.</li>
  </ul>

  <hr>

  <h2>📚 Related APIs</h2>
  <ul>
    <li><strong>Market Data API:</strong> <code>/market-data-api-v1/market-data-api.php</code> - Direct market data access</li>
    <li><strong>News API:</strong> <code>/news-api-v1/news-api.php</code> - Economic calendar events</li>
    <li><strong>Event ID Reference:</strong> <code>/news/event-id-reference.php</code> - List of all consistent event IDs</li>
  </ul>

  <hr>

  <footer class="text-center text-muted mt-5">
    <p>&copy; 2026 Vestor Finance - Arrissa Data API Integration</p>
  </footer>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
