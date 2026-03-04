import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { buildUrl, fetchImage, fetchJson } from "../api.js";
import type { Config } from "../config.js";

const TIMEFRAMES = ["M1", "M5", "M15", "M30", "H1", "H4", "D1", "W1", "MN1"] as const;
const PATH = "/chart-image-api-v1/chart-image-api.php";

export function registerChartImageTools(server: McpServer, config: Config): void {

  // 9. generate_chart_image
  server.tool(
    "generate_chart_image",
    "Generate a 16:9 PNG candlestick chart for a symbol and timeframe. Returns a PNG image.",
    {
      symbol:    z.string().describe("Trading instrument"),
      timeframe: z.enum(TIMEFRAMES).describe("Candle timeframe"),
      count:     z.number().int().min(1).optional().describe("Number of candles to display (default 100)"),
      rangeType: z.string().optional().describe("Named time range instead of count"),
    },
    async ({ symbol, timeframe, count, rangeType }) => ({
      content: await fetchImage(buildUrl(config, PATH, { symbol, timeframe, count, rangeType })),
    })
  );

  // 10. generate_chart_with_emas
  server.tool(
    "generate_chart_with_emas",
    "Generate a candlestick chart image with one or two EMA overlay lines drawn on it.",
    {
      symbol:      z.string().describe("Trading instrument"),
      timeframe:   z.enum(TIMEFRAMES).describe("Candle timeframe"),
      count:       z.number().int().min(1).optional().describe("Number of candles (default 100)"),
      rangeType:   z.string().optional().describe("Named time range instead of count"),
      ema1_period: z.number().int().positive().describe("Period for EMA 1 e.g. 20"),
      ema2_period: z.number().int().positive().optional().describe("Period for EMA 2 e.g. 50 (optional)"),
    },
    async ({ symbol, timeframe, count, rangeType, ema1_period, ema2_period }) => ({
      content: await fetchImage(buildUrl(config, PATH, { symbol, timeframe, count, rangeType, ema1_period, ema2_period })),
    })
  );

  // 11. generate_chart_with_fibonacci
  server.tool(
    "generate_chart_with_fibonacci",
    "Generate a candlestick chart image with Fibonacci retracement levels overlaid.",
    {
      symbol:    z.string().describe("Trading instrument"),
      timeframe: z.enum(TIMEFRAMES).describe("Candle timeframe"),
      count:     z.number().int().min(1).optional().describe("Number of candles (default 100)"),
      rangeType: z.string().optional().describe("Named time range instead of count"),
    },
    async ({ symbol, timeframe, count, rangeType }) => ({
      content: await fetchImage(buildUrl(config, PATH, { symbol, timeframe, count, rangeType, fib: "true" })),
    })
  );

  // 12. generate_chart_with_atr
  server.tool(
    "generate_chart_with_atr",
    "Generate a candlestick chart image with the Average True Range (ATR) indicator displayed.",
    {
      symbol:     z.string().describe("Trading instrument"),
      timeframe:  z.enum(TIMEFRAMES).describe("Candle timeframe"),
      count:      z.number().int().min(1).optional().describe("Number of candles (default 100)"),
      rangeType:  z.string().optional().describe("Named time range instead of count"),
      atr_period: z.number().int().positive().describe("ATR period e.g. 14"),
    },
    async ({ symbol, timeframe, count, rangeType, atr_period }) => ({
      content: await fetchImage(buildUrl(config, PATH, { symbol, timeframe, count, rangeType, atr: atr_period })),
    })
  );

  // 13. generate_chart_with_period_separators
  server.tool(
    "generate_chart_with_period_separators",
    "Generate a chart image with vertical period-separator lines and optional high/low markers per segment. Periods: 5M, 15M, 30M, 1H, 4H, day, week, month, year.",
    {
      symbol:             z.string().describe("Trading instrument"),
      timeframe:          z.enum(TIMEFRAMES).describe("Candle timeframe"),
      count:              z.number().int().min(1).optional().describe("Number of candles"),
      rangeType:          z.string().optional().describe("Named time range instead of count"),
      period_separators:  z.string().describe("Comma-separated periods to draw e.g. 1H,day"),
      high_low:           z.boolean().optional().describe("Draw high/low markers per period segment"),
    },
    async ({ symbol, timeframe, count, rangeType, period_separators, high_low }) => ({
      content: await fetchImage(buildUrl(config, PATH, { symbol, timeframe, count, rangeType, period_separators, high_low })),
    })
  );

  // 14. generate_chart_dark_theme
  server.tool(
    "generate_chart_dark_theme",
    "Generate a candlestick chart image with a dark background theme.",
    {
      symbol:    z.string().describe("Trading instrument"),
      timeframe: z.enum(TIMEFRAMES).describe("Candle timeframe"),
      count:     z.number().int().min(1).optional().describe("Number of candles (default 100)"),
      rangeType: z.string().optional().describe("Named time range instead of count"),
    },
    async ({ symbol, timeframe, count, rangeType }) => ({
      content: await fetchImage(buildUrl(config, PATH, { symbol, timeframe, count, rangeType, theme: "dark" })),
    })
  );

  // 15. generate_chart_rangeType
  server.tool(
    "generate_chart_rangeType",
    "Generate a chart image covering a named time range (today, last-hour, this-week, etc.) instead of a fixed candle count.",
    {
      symbol:    z.string().describe("Trading instrument"),
      timeframe: z.enum(TIMEFRAMES).describe("Candle timeframe"),
      rangeType: z.string().describe("Named range e.g. today, last-week, last-3-hours, last-21-days"),
    },
    async ({ symbol, timeframe, rangeType }) => ({
      content: await fetchImage(buildUrl(config, PATH, { symbol, timeframe, rangeType })),
    })
  );

  // 16. get_streaming_chart_url
  server.tool(
    "get_streaming_chart_url",
    "Get a short shareable URL for a live auto-updating streaming chart. Returns JSON with the URL.",
    {
      symbol:    z.string().describe("Trading instrument"),
      timeframe: z.enum(TIMEFRAMES).describe("Candle timeframe"),
      count:     z.number().int().min(1).optional().describe("Number of candles"),
    },
    async ({ symbol, timeframe, count }) => ({
      content: await fetchJson(buildUrl(config, PATH, { symbol, timeframe, count, streaming: "url" })),
    })
  );

  // 17. redirect_to_streaming_chart
  server.tool(
    "redirect_to_streaming_chart",
    "Get a direct link to the live streaming chart page (auto-refreshing). Returns the redirect URL as text.",
    {
      symbol:    z.string().describe("Trading instrument"),
      timeframe: z.enum(TIMEFRAMES).describe("Candle timeframe"),
      count:     z.number().int().min(1).optional().describe("Number of candles"),
    },
    async ({ symbol, timeframe, count }) => {
      const url = buildUrl(config, PATH, { symbol, timeframe, count, streaming: "redirect" });
      return { content: [{ type: "text" as const, text: `Streaming chart URL:\n${url}` }] };
    }
  );
}
