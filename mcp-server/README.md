# Arrissa Data MCP Server

HTTP Streamable MCP server exposing **63 tools** across 6 categories from the Arrissa Data API platform.

## Quick Start

```bash
cd mcp-server
npm install
npm run build
npm start
```

Server starts on **http://localhost:3000** by default.

## Configuration

The server reads `app_base_url` and `api_key` from the SQLite database automatically. Override with environment variables:

```bash
set ARRISSA_BASE_URL=http://localhost
set ARRISSA_API_KEY=your-api-key
set PORT=3000
npm start
```

## MCP Endpoint

```
POST http://localhost:3000/mcp
GET  http://localhost:3000/mcp   (SSE for server-to-client messages)
```

## Development (hot-reload)

```bash
npm run dev
```

## Tools (63 total)

| Category | Tools | Description |
|---|---|---|
| **market-data** | 8 | OHLC candles, indicators, MAs, volume, backtest mode |
| **chart-images** | 9 | PNG chart generation with EMA, Fibonacci, ATR, dark theme, streaming |
| **economic-calendar** | 15 | Events by period/date/currency/impact, future events, similar scenes, latest snapshot |
| **orders** | 20 | Market/limit/stop orders, close/modify positions, trade history, P&L |
| **market-analysis** | 3 | Symbol behavior analysis, TMA+CG zones, Quarters Theory levels |
| **web-content** | 8 | URL fetching with auth variants, Reuters news, Yahoo Finance |

## Claude Desktop / AI Client Config

Add to your MCP client config:

```json
{
  "mcpServers": {
    "arrissa-data": {
      "type": "http",
      "url": "http://localhost:3000/mcp"
    }
  }
}
```

## Health Check

```
GET http://localhost:3000/health
```
