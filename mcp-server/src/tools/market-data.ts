import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { buildUrl, fetchJson } from "../api.js";
import type { Config } from "../config.js";

const TIMEFRAMES = ["M1", "M5", "M15", "M30", "H1", "H4", "D1", "W1", "MN1"] as const;
const RANGE_TYPES = [
  "last-five-minutes","last-hour","last-6-hours","last-12-hours","last-48-hours",
  "last-3-days","last-4-days","last-5-days","last-7-days","last-14-days","last-30-days",
  "today","yesterday","this-week","last-week","this-month","last-month",
  "last-3-months","last-6-months","this-year","last-12-months","future",
] as const;
const PATH = "/market-data-api-v1/market-data-api.php";

export function registerMarketDataTools(server: McpServer, config: Config): void {

  // 1. get_candles_by_count
  server.tool(
    "get_candles_by_count",
    "Get the most recent N OHLC candles for a symbol and timeframe.",
    {
      symbol:    z.string().describe("Trading instrument e.g. XAUUSD, EURUSD"),
      timeframe: z.enum(TIMEFRAMES).describe("Candle timeframe"),
      count:     z.number().int().min(1).max(5000).describe("Number of candles (1-5000)"),
    },
    async ({ symbol, timeframe, count }) => ({
      content: await fetchJson(buildUrl(config, PATH, { symbol, timeframe, count })),
    })
  );

  // 2. get_candles_by_range
  server.tool(
    "get_candles_by_range",
    "Get candles for a named time range (today, last-week, this-month, last-7-days, etc.).",
    {
      symbol:    z.string().describe("Trading instrument"),
      timeframe: z.enum(TIMEFRAMES).describe("Candle timeframe"),
      rangeType: z.enum(RANGE_TYPES).describe("Named time range. Dynamic ranges like last-15-minutes, last-3-hours, last-21-days are also accepted — pass as a string.").or(z.string()),
    },
    async ({ symbol, timeframe, rangeType }) => ({
      content: await fetchJson(buildUrl(config, PATH, { symbol, timeframe, rangeType })),
    })
  );

  // 3. get_candles_last_x_minutes
  server.tool(
    "get_candles_last_x_minutes",
    "Get candles covering the last X minutes (1-1440). Useful for 'last 15 minutes', 'last 90 minutes', etc.",
    {
      symbol:    z.string().describe("Trading instrument"),
      timeframe: z.enum(TIMEFRAMES).describe("Candle timeframe"),
      minutes:   z.number().int().min(1).max(1440).describe("Number of minutes (1-1440)"),
    },
    async ({ symbol, timeframe, minutes }) => ({
      content: await fetchJson(buildUrl(config, PATH, { symbol, timeframe, rangeType: `last-${minutes}-minutes` })),
    })
  );

  // 4. get_candles_with_indicators
  server.tool(
    "get_candles_with_indicators",
    "Get candles with one or more technical indicators. Oscillators: rsi, stoch, cci, wpr, mfi, momentum, demarker. Trend: macd, sar, ichimoku. Volatility: bb, atr, envelopes, stddev. Volume: obv. Bill Williams: ac, ao, alligator, fractals. Max 3 indicators per request.",
    {
      symbol:       z.string().describe("Trading instrument"),
      timeframe:    z.enum(TIMEFRAMES).describe("Candle timeframe"),
      count:        z.number().int().min(1).max(5000).optional().describe("Number of candles"),
      rangeType:    z.string().optional().describe("Named range instead of count"),
      rsi:          z.number().int().optional().describe("RSI period e.g. 14"),
      stoch:        z.string().optional().describe("Stochastic k,d,slowing e.g. 5,3,3"),
      cci:          z.number().int().optional().describe("CCI period"),
      wpr:          z.number().int().optional().describe("Williams %R period"),
      mfi:          z.number().int().optional().describe("Money Flow Index period"),
      momentum:     z.number().int().optional().describe("Momentum period"),
      demarker:     z.number().int().optional().describe("DeMarker period"),
      macd:         z.string().optional().describe("MACD fast,slow,signal e.g. 12,26,9"),
      sar:          z.string().optional().describe("SAR step,maximum e.g. 0.02,0.2"),
      ichimoku:     z.string().optional().describe("Ichimoku tenkan,kijun,senkou e.g. 9,26,52"),
      bb:           z.string().optional().describe("Bollinger Bands period,shift,deviation e.g. 20,0,2"),
      atr:          z.number().int().optional().describe("ATR period"),
      envelopes:    z.string().optional().describe("Envelopes period,deviation"),
      stddev:       z.number().int().optional().describe("StdDev period"),
      obv:          z.number().int().optional().describe("OBV volume type 0=tick 1=real"),
      ac:           z.boolean().optional().describe("Accelerator Oscillator"),
      ao:           z.boolean().optional().describe("Awesome Oscillator"),
      alligator:    z.string().optional().describe("Alligator jaw,teeth,lips e.g. 13,8,5"),
      fractals:     z.boolean().optional().describe("Bill Williams Fractals"),
    },
    async (args) => {
      const { symbol, timeframe, count, rangeType, ...indicators } = args;
      const params: Record<string, string | number | boolean | undefined> = { symbol, timeframe };
      if (count)     params.count     = count;
      if (rangeType) params.rangeType = rangeType;
      for (const [k, v] of Object.entries(indicators)) {
        if (v !== undefined && v !== false) params[k] = v === true ? "true" : v;
      }
      return { content: await fetchJson(buildUrl(config, PATH, params)) };
    }
  );

  // 5. get_candles_with_moving_averages
  server.tool(
    "get_candles_with_moving_averages",
    "Get candles with up to 10 moving averages. Type prefix: e=EMA, s=SMA (default), sm=SMMA, l=LWMA. Examples: ma_1=e,20 ma_2=50 or ema_1=20.",
    {
      symbol:    z.string().describe("Trading instrument"),
      timeframe: z.enum(TIMEFRAMES).describe("Candle timeframe"),
      count:     z.number().int().min(1).max(5000).optional().describe("Number of candles"),
      rangeType: z.string().optional().describe("Named range instead of count"),
      ma_1:      z.string().optional().describe("MA 1: type,period e.g. e,20 or just 50 for SMA"),
      ma_2:      z.string().optional().describe("MA 2"),
      ma_3:      z.string().optional().describe("MA 3"),
      ma_4:      z.string().optional().describe("MA 4"),
      ma_5:      z.string().optional().describe("MA 5"),
      ema_1:     z.string().optional().describe("EMA shorthand e.g. ema_1=20"),
      ema_2:     z.string().optional().describe("EMA 2"),
      sma_1:     z.string().optional().describe("SMA shorthand"),
      sma_2:     z.string().optional().describe("SMA 2"),
      lwma_1:    z.string().optional().describe("LWMA shorthand"),
    },
    async ({ symbol, timeframe, count, rangeType, ...mas }) => {
      const params: Record<string, string | number | undefined> = { symbol, timeframe };
      if (count)     params.count     = count;
      if (rangeType) params.rangeType = rangeType;
      for (const [k, v] of Object.entries(mas)) {
        if (v !== undefined) params[k] = v;
      }
      return { content: await fetchJson(buildUrl(config, PATH, params)) };
    }
  );

  // 6. get_candles_with_volume
  server.tool(
    "get_candles_with_volume",
    "Get candles with tick volume included in each candle object.",
    {
      symbol:    z.string().describe("Trading instrument"),
      timeframe: z.enum(TIMEFRAMES).describe("Candle timeframe"),
      count:     z.number().int().min(1).max(5000).optional().describe("Number of candles"),
      rangeType: z.string().optional().describe("Named range instead of count"),
    },
    async ({ symbol, timeframe, count, rangeType }) => ({
      content: await fetchJson(buildUrl(config, PATH, { symbol, timeframe, count, rangeType, volume: "true" })),
    })
  );

  // 7. get_single_price_field_array
  server.tool(
    "get_single_price_field_array",
    "Get a single OHLCV field as a plain array of values (e.g., only close prices, only volumes).",
    {
      symbol:    z.string().describe("Trading instrument"),
      timeframe: z.enum(TIMEFRAMES).describe("Candle timeframe"),
      count:     z.number().int().min(1).max(5000).optional().describe("Number of candles"),
      rangeType: z.string().optional().describe("Named range instead of count"),
      dataField: z.enum(["open", "high", "low", "close", "volume"]).describe("Which OHLCV field to return as array"),
    },
    async ({ symbol, timeframe, count, rangeType, dataField }) => ({
      content: await fetchJson(buildUrl(config, PATH, { symbol, timeframe, count, rangeType, dataField })),
    })
  );

  // 8. get_candles_backtest_mode
  server.tool(
    "get_candles_backtest_mode",
    "Get candles as if the current moment were a specific historical date/time — backtesting / replay mode.",
    {
      symbol:       z.string().describe("Trading instrument"),
      timeframe:    z.enum(TIMEFRAMES).describe("Candle timeframe"),
      count:        z.number().int().min(1).max(5000).optional().describe("Number of candles"),
      rangeType:    z.string().optional().describe("Named range instead of count"),
      pretend_date: z.string().describe("Simulate this as 'today'. Format: YYYY-MM-DD"),
      pretend_time: z.string().describe("Simulate this as 'now'. Format: HH:MM"),
    },
    async ({ symbol, timeframe, count, rangeType, pretend_date, pretend_time }) => ({
      content: await fetchJson(buildUrl(config, PATH, { symbol, timeframe, count, rangeType, pretend_date, pretend_time })),
    })
  );
}
