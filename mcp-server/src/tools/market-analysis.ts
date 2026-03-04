import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { buildUrl, fetchJson } from "../api.js";
import type { Config } from "../config.js";

export function registerMarketAnalysisTools(server: McpServer, config: Config): void {

  // 53. get_symbol_behavior_analysis
  server.tool(
    "get_symbol_behavior_analysis",
    "Get comprehensive symbol behavior and volatility analysis — daily averages, typical ranges, directional bias, and statistical data for a trading instrument.",
    {
      symbol:        z.string().describe("Trading instrument e.g. XAUUSD, EURUSD"),
      timeframe:     z.enum(["M5","M15","M30","H1","H4","H8","H12","D1","W1","M"]).describe("Analysis timeframe"),
      lookback:      z.number().int().optional().describe("Number of historical periods to analyze (omit for default). Limits: M5=2000 M15=1000 M30=500 H1=500 H4=200 H8=100 H12=60 D1=1000 W1=200 M=120"),
      ignore_sunday: z.boolean().optional().describe("Exclude Sunday candles (default: true)"),
      pretend_date:  z.string().optional().describe("Backtesting date YYYY-MM-DD (requires pretend_time)"),
      pretend_time:  z.string().optional().describe("Backtesting time HH:MM (requires pretend_date)"),
    },
    async ({ symbol, timeframe, lookback, ignore_sunday, pretend_date, pretend_time }) => ({
      content: await fetchJson(buildUrl(config, "/symbol-info-api-v1/symbol-info-api.php", {
        symbol, timeframe, lookback,
        ignore_sunday: ignore_sunday === false ? "false" : undefined,
        pretend_date, pretend_time,
      })),
    })
  );

  // 54. get_tma_cg_zone
  server.tool(
    "get_tma_cg_zone",
    "Get TMA+CG zone classification (premium / discount / equilibrium) and the percentage position of the current price within the TMA bands.",
    {
      symbol:       z.string().describe("Trading instrument e.g. XAUUSD"),
      timeframe:    z.enum(["M1","M5","M15","M30","H1","H4","D1"]).optional().describe("Timeframe (default M1)"),
      pretend_date: z.string().optional().describe("Backtesting date YYYY-MM-DD"),
      pretend_time: z.string().optional().describe("Backtesting time HH:MM"),
    },
    async ({ symbol, timeframe, pretend_date, pretend_time }) => ({
      content: await fetchJson(buildUrl(config, "/tma-cg-api-v1/tma-cg-api.php", {
        symbol, timeframe, pretend_date, pretend_time,
      })),
    })
  );

  // 55. get_quarters_theory_data
  server.tool(
    "get_quarters_theory_data",
    "Get Quarters Theory key price levels (quarter boundaries at every 0.25 unit) and the current zone position for a trading instrument.",
    {
      symbol:       z.string().describe("Trading instrument e.g. XAUUSD, EURUSD"),
      pretend_date: z.string().optional().describe("Backtesting date YYYY-MM-DD"),
      pretend_time: z.string().optional().describe("Backtesting time HH:MM"),
    },
    async ({ symbol, pretend_date, pretend_time }) => ({
      content: await fetchJson(buildUrl(config, "/quarters-theory-api-v1/quarters-theory-api.php", {
        symbol, pretend_date, pretend_time,
      })),
    })
  );
}
