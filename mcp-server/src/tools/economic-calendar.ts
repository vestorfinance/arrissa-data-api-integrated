import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { buildUrl, fetchJson } from "../api.js";
import type { Config } from "../config.js";

const NEWS_PATH    = "/news-api-v1/news-api.php";
const SIMILAR_PATH = "/news-api-v1/similar-scene-api.php";
const LATEST_PATH  = "/news-api-v1/latest-events-api.php";

const PERIOD_DESC =
  "Named period: today | yesterday | this-week | last-week | this-month | last-month | last-3-months | last-6-months | last-7-days | last-14-days | last-30-days | this-year | last-12-months | last-2-years | future. Dynamic: last-{N}-hours | last-{N}-days | last-{N}-weeks | last-{N}-months e.g. last-3-hours, last-21-days";

export function registerEconomicCalendarTools(server: McpServer, config: Config): void {

  // 18. get_economic_events_by_period
  server.tool(
    "get_economic_events_by_period",
    "Get all economic events for a named time period (today, this-week, last-30-days, last-3-months, future, etc.). Dynamic ranges like last-3-hours, last-21-days are also supported.",
    {
      period: z.string().describe(PERIOD_DESC),
    },
    async ({ period }) => ({
      content: await fetchJson(buildUrl(config, NEWS_PATH, { period })),
    })
  );

  // 19. get_economic_events_by_date_range
  server.tool(
    "get_economic_events_by_date_range",
    "Get economic events between two explicit date/time boundaries.",
    {
      start_date: z.string().describe("Start date YYYY-MM-DD"),
      end_date:   z.string().describe("End date YYYY-MM-DD"),
      start_time: z.string().optional().describe("Start time HH:MM:SS (default 00:00:00)"),
      end_time:   z.string().optional().describe("End time HH:MM:SS (default 23:59:59)"),
    },
    async ({ start_date, end_date, start_time, end_time }) => ({
      content: await fetchJson(buildUrl(config, NEWS_PATH, { start_date, end_date, start_time, end_time })),
    })
  );

  // 20. get_economic_events_by_currency
  server.tool(
    "get_economic_events_by_currency",
    "Get economic events filtered to one or more currencies (USD, EUR, GBP, JPY, CAD, AUD, NZD, CHF).",
    {
      period:   z.string().describe(PERIOD_DESC),
      currency: z.string().describe("Currency code(s) comma-separated e.g. USD or USD,EUR"),
    },
    async ({ period, currency }) => ({
      content: await fetchJson(buildUrl(config, NEWS_PATH, { period, currency })),
    })
  );

  // 21. get_economic_events_by_impact
  server.tool(
    "get_economic_events_by_impact",
    "Get economic events filtered by impact level. Impact: High | Medium | Low (comma-separated for multiple).",
    {
      period: z.string().describe(PERIOD_DESC),
      impact: z.string().describe("High | Medium | Low — comma-separated e.g. High,Medium"),
    },
    async ({ period, impact }) => ({
      content: await fetchJson(buildUrl(config, NEWS_PATH, { period, impact })),
    })
  );

  // 22. get_future_economic_events
  server.tool(
    "get_future_economic_events",
    "Get upcoming future economic events. Cap the window with future_limit: today | tomorrow | next-2-days | this-week | next-week | next-2-weeks | next-month. Dynamic: next-{N}-hours | next-{N}-days.",
    {
      future_limit: z.string().optional().describe("Limit window e.g. tomorrow, next-week, next-3-days"),
    },
    async ({ future_limit }) => ({
      content: await fetchJson(buildUrl(config, NEWS_PATH, { period: "future", future_limit })),
    })
  );

  // 23. get_economic_events_by_event_id
  server.tool(
    "get_economic_events_by_event_id",
    "Get all historical occurrences of a specific economic event by its consistent_event_id (e.g. USD_NFP, EUR_CPI).",
    {
      event_id: z.string().describe("consistent_event_id(s) comma-separated e.g. USD_NFP or USD_NFP,USD_CPI"),
      period:   z.string().optional().describe("Optional time period to filter occurrences"),
    },
    async ({ event_id, period }) => ({
      content: await fetchJson(buildUrl(config, NEWS_PATH, { event_id, period })),
    })
  );

  // 24. get_economic_events_with_timezone
  server.tool(
    "get_economic_events_with_timezone",
    "Get economic events with all date/times converted to a specified timezone. Shorthand: NY | LA | LON | TYO | SYD. Or any PHP timezone like America/New_York.",
    {
      period:    z.string().describe(PERIOD_DESC),
      time_zone: z.string().describe("Timezone shorthand (NY, LON, TYO, SYD, LA) or PHP timezone string"),
    },
    async ({ period, time_zone }) => ({
      content: await fetchJson(buildUrl(config, NEWS_PATH, { period, time_zone })),
    })
  );

  // 25. get_economic_events_minimal
  server.tool(
    "get_economic_events_minimal",
    "Get economic events in minimal format — only event_name, event_date, event_time, currency, plus forecast/actual/previous when available. Saves tokens.",
    {
      period: z.string().describe(PERIOD_DESC),
    },
    async ({ period }) => ({
      content: await fetchJson(buildUrl(config, NEWS_PATH, { period, display: "min" })),
    })
  );

  // 26. get_economic_events_without_duplicates
  server.tool(
    "get_economic_events_without_duplicates",
    "Get economic events with duplicate event types removed — one record per unique consistent_event_id.",
    {
      period: z.string().describe(PERIOD_DESC),
    },
    async ({ period }) => ({
      content: await fetchJson(buildUrl(config, NEWS_PATH, { period, avoid_duplicates: "true" })),
    })
  );

  // 27. get_economic_events_with_actuals
  server.tool(
    "get_economic_events_with_actuals",
    "Get only economic events that have specific field values populated. must_have: actual_value | forecast_value | previous_value (comma-separated).",
    {
      period:    z.string().describe(PERIOD_DESC),
      must_have: z.string().describe("actual_value | forecast_value | previous_value — comma-separated"),
    },
    async ({ period, must_have }) => ({
      content: await fetchJson(buildUrl(config, NEWS_PATH, { period, must_have })),
    })
  );

  // 28. get_economic_events_exclude_currencies
  server.tool(
    "get_economic_events_exclude_currencies",
    "Get all economic events excluding events for specified currencies.",
    {
      period:           z.string().describe(PERIOD_DESC),
      currency_exclude: z.string().describe("Comma-separated currencies to exclude e.g. EUR,GBP"),
    },
    async ({ period, currency_exclude }) => ({
      content: await fetchJson(buildUrl(config, NEWS_PATH, { period, currency_exclude })),
    })
  );

  // 29. get_economic_events_future_with_tbd
  server.tool(
    "get_economic_events_future_with_tbd",
    "Get economic events with actual values masked as 'TBD' — useful for scheduled/future event display.",
    {
      period: z.string().describe(PERIOD_DESC),
    },
    async ({ period }) => ({
      content: await fetchJson(buildUrl(config, NEWS_PATH, { period, tbd: "true" })),
    })
  );

  // 30. get_economic_events_all_including_future
  server.tool(
    "get_economic_events_all_including_future",
    "Get ALL economic events for a period including scheduled future events within that period (not capped at current time). Works best with period: today, this-week, this-month, this-year.",
    {
      period: z.string().describe("today | this-week | this-month | this-year"),
    },
    async ({ period }) => ({
      content: await fetchJson(buildUrl(config, NEWS_PATH, { period, spit_out: "all" })),
    })
  );

  // 31. get_similar_market_scenes
  server.tool(
    "get_similar_market_scenes",
    "Get historical market conditions (candles) at every past occurrence of a specific economic event. Finds repeating market patterns around news events.",
    {
      event_id: z.string().describe("consistent_event_id(s) comma-separated e.g. USD_NFP"),
      symbol:   z.string().optional().describe("Trading instrument for market data (default: XAUUSD)"),
      period:   z.string().optional().describe("Time window e.g. last-3-months, last-12-months"),
      display:  z.enum(["min"]).optional().describe("min = minimal event fields"),
      currency: z.string().optional().describe("Filter events by currency e.g. USD"),
      impact:   z.string().optional().describe("Filter by impact: High | Medium | Low"),
      output:   z.enum(["all"]).optional().describe("all = return all events at each occurrence timestamp"),
      tbd:      z.boolean().optional().describe("Mask actual_value as TBD"),
    },
    async ({ event_id, symbol, period, display, currency, impact, output, tbd }) => ({
      content: await fetchJson(buildUrl(config, SIMILAR_PATH, {
        event_id, symbol, period, display, currency, impact, output, tbd: tbd ? "true" : undefined,
      })),
    })
  );

  // 32. get_latest_economic_events
  server.tool(
    "get_latest_economic_events",
    "Get the latest (most recent) occurrence of every distinct economic event type up to now or a pretend date. Returns one row per event type showing its last known actual, forecast, and previous values. Ideal for giving an AI a current economic snapshot.",
    {
      currency:     z.string().optional().describe("Comma-separated currency filter e.g. USD,EUR"),
      event_id:     z.string().optional().describe("Comma-separated consistent_event_ids e.g. USD_NFP,EUR_CPI"),
      impact:       z.string().optional().describe("High | Medium | Low — comma-separated"),
      must_have:    z.enum(["actual"]).optional().describe("actual = only events that already have a real actual value"),
      pretend_date: z.string().optional().describe("Treat this as today for the cutoff. Format: YYYY-MM-DD"),
      pretend_time: z.string().optional().describe("Used with pretend_date. Format: HH:MM:SS UTC"),
    },
    async ({ currency, event_id, impact, must_have, pretend_date, pretend_time }) => ({
      content: await fetchJson(buildUrl(config, LATEST_PATH, {
        currency, event_id, impact, must_have, pretend_date, pretend_time,
      })),
    })
  );
}
