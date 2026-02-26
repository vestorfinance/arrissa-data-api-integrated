# Arrissa Data API

A self-hosted market data dashboard and API suite powered by PHP + SQLite, designed to work alongside MetaTrader 5 Expert Advisors.

---

## Requirements

- **WAMP64 / XAMPP** (PHP 8.2+, Apache)
- **MetaTrader 5** with the relevant EA attached per API (see below)
- **SQLite** (bundled with PHP)

See [XAMPP_INSTALLATION.md](XAMPP_INSTALLATION.md) for setup instructions.

---

## Getting Started

1. Clone or copy this repo into your web server root (e.g. `c:\wamp64\www\`)
2. Copy `.env.example` to `.env` and fill in your values
3. Run the database initialiser:
   ```
   php database/init.php
   ```
4. Visit `http://localhost/` in your browser

---

## Project Structure

```
app/                        # Core PHP classes
  Auth.php                  # Session authentication
  Database.php              # SQLite connection helper
  FooterGuard.php           # Tamper-proof footer integrity guard
  SidebarGuard.php          # Tamper-proof sidebar link integrity guard
  WebScraper.php            # Event scraping utilities

config/
  database.php              # DB path config

database/
  init.php                  # DB schema initialiser
  import_events.php         # Import economic calendar events
  setup-cron.ps1            # Windows Scheduled Task setup (PowerShell)

public/api/                 # Internal JSON API endpoints
  tmp-admin.php             # TMP tool CRUD (list, add, edit, delete)
  tmp-categories.php        # TMP category list
  tmp-get-tool.php          # Retrieve a single TMP tool
  tmp-tool-capabilities.php # AI-facing capabilities endpoint
  sync-events.php           # Sync economic events from source
  run-cron.php              # HTTP-triggerable cron endpoint (api_key auth)
  search.php                # Global search

resources/views/            # PHP view templates
  layouts/app.php           # Master layout (sidebar, footer, theme)
  dashboard.php             # Dashboard home
  settings.php              # App settings
  manage-events.php         # Economic calendar event management
  tmp-guide.php             # TMP Protocol guide page
  tmp-manage.php            # TMP tool manager UI
  download-eas.php          # EA download page
  *-api-guide.php           # Per-API documentation pages

market-data-api-v1/         # Market data API (OHLCV, candles, backtest mode)
news-api-v1/                # Economic calendar API (events, similar-scene)
orders-api-v1/              # MT5 orders API (open, close, modify, trail)
chart-image-api-v1/         # Candlestick chart image generation API
symbol-info-api-v1/         # Symbol behaviour & volatility analysis API
tma-cg-api-v1/              # TMA + CG zone API (premium/discount/equilibrium)
quarters-theory-api-v1/     # Quarters Theory data API
url-api-v1/                 # URL shortener / streaming chart links

expert-advisors/            # MT5 EA source files (.mq5) and compiled (.ex5)
```

---

## API Overview

| API | Endpoint | Requires EA |
|-----|----------|-------------|
| Market Data | `/market-data-api-v1/market-data-api.php` | Arrissa Data MT5 Market Data API.ex5 |
| Chart Images | `/chart-image-api-v1/chart-image-api.php` | Arrissa Data MT5 Market Data API.ex5 |
| Economic Calendar | `/news-api-v1/news-api.php` | — |
| Similar Scene | `/news-api-v1/similar-scene-api.php` | — |
| Orders | `/orders-api-v1/orders-api.php` | Arrissa Data MT5 Orders API.ex5 |
| Symbol Info | `/symbol-info-api-v1/symbol-info-api.php` | Arrissa Data Symbol Info API.ex5 |
| TMA + CG | `/tma-cg-api-v1/tma-cg-api.php` | TMA CG Data EA.ex5 |
| Quarters Theory | `/quarters-theory-api-v1/quarters-theory-api.php` | Richchild Quarters Theory Data EA.ex5 |

All endpoints authenticate via `?api_key={api_key}`.

---

## TMP Protocol

The **Tool Matching Protocol (TMP)** is an AI-facing system that maps natural language phrases to the correct API tool and URL format.

- Browse & manage tools at `/tmp-manage`
- Read the guide at `/tmp-guide`
- AI endpoint: `GET /api/tmp-tool-capabilities`
- Tools collection reference: `TMP-tools-collection.txt`

---

## Cron / Event Sync

Economic calendar events sync automatically via a scheduled task.

**Set up Windows Scheduled Task:**
```powershell
.\database\setup-cron.ps1
```

**Trigger manually via HTTP:**
```
GET /api/run-cron?api_key={api_key}
GET /api/run-cron?api_key={api_key}&force=true
```

---

## Integrity Guards

`FooterGuard` and `SidebarGuard` use SHA-256 HMAC integrity checks to prevent tampering with protected UI elements. Any unauthorised change halts the app with a `503` response.

To reseal after an authorised change:
```bash
php -r "require 'app/FooterGuard.php'; echo FooterGuard::seal();"
php -r "require 'app/SidebarGuard.php'; echo SidebarGuard::seal();"
```

---

## License

(c) 2026 Arrissa Pty Ltd - Ngonidzashe Jiji (David Richchild). All rights reserved. Redistribution or resale without explicit written permission is prohibited.
