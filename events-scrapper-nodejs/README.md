# Events Scrapper - Node.js

This is an economic events scraper that collects forex news events and stores them in the SQLite database.

## Features

- Scrapes economic events from major forex websites
- Stores events in SQLite database
- Filters by currency and impact level
- Automatic timezone conversion (NY to UTC)
- Prevents duplicate events
- High-frequency mode for real-time updates

## Requirements

- Node.js (v14 or higher)
- npm (Node Package Manager)

## Installation

The dependencies will be installed automatically when you run the scrapper from the web interface.

Manual installation:
```bash
cd events-scrapper-nodejs
npm install
```

## Dependencies

- `puppeteer` - Web scraping and browser automation
- `sqlite3` - SQLite database driver
- `moment-timezone` - Timezone handling
- `node-cron` - Scheduled tasks

## Usage

### Via Web Interface (Recommended)

1. Login to the Arrissa Data API dashboard
2. Click on "Run Events Scrapper" in the sidebar
3. Click the "Run Scrapper" button
4. Watch the real-time output in the terminal window

### Via Command Line

```bash
cd events-scrapper-nodejs
node events.js
```

You will be prompted to choose:
1. Run current week + next week scrapers (sequential)
2. Run events history scraper only
3. Run scheduled scraper (monitors and runs automatically)

## Database

Events are stored in `database/app.db` in the `economic_events` table with the following fields:

- `event_id` - Unique identifier (SHA1 hash)
- `event_name` - Name of the economic event
- `event_date` - Date of the event (YYYY-MM-DD)
- `event_time` - Time of the event (HH:MM:SS) in UTC
- `currency` - Currency code (USD, EUR, GBP, etc.)
- `forecast_value` - Forecasted value
- `actual_value` - Actual value (after release)
- `previous_value` - Previous value
- `impact_level` - High, Moderate, or Low
- `consistent_event_id` - Consistent ID for recurring events

## Author

**Ngonidzashe Jiji**
- Instagram: [@davidrichchild](https://instagram.com/davidrichchild)
- Telegram: [t.me/david_richchild](https://t.me/david_richchild)
- TikTok: davidrichchild

## URLs

- [https://arrissadata.com](https://arrissadata.com)
- [https://arrissatechnologies.com](https://arrissatechnologies.com)
- [https://arrissa.trade](https://arrissa.trade)

## Course

Learn more: [Udemy Course #6804721](https://www.udemy.com/course/6804721)

## License

This software is provided "AS IS", without any warranty. You are granted permission to use, copy, modify, and distribute this code for personal or commercial projects, provided that the author details remain intact.
