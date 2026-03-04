import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { buildUrl, fetchJson } from "../api.js";
import type { Config } from "../config.js";

const PATH = "/orders-api-v1/orders-api.php";

export function registerOrdersTools(server: McpServer, config: Config): void {

  // 33. open_buy_market_order
  server.tool(
    "open_buy_market_order",
    "Open a buy market order at the current ask price.",
    {
      symbol: z.string().describe("Trading instrument e.g. XAUUSD"),
      volume: z.number().positive().describe("Lot size e.g. 0.01 | 0.1 | 1.0"),
      sl:     z.number().describe("Stop loss price (0 = no stop loss)"),
      tp:     z.number().describe("Take profit price (0 = no take profit)"),
    },
    async ({ symbol, volume, sl, tp }) => ({
      content: await fetchJson(buildUrl(config, PATH, { action: "BUY", symbol, volume, sl, tp })),
    })
  );

  // 34. open_sell_market_order
  server.tool(
    "open_sell_market_order",
    "Open a sell market order at the current bid price.",
    {
      symbol: z.string().describe("Trading instrument e.g. XAUUSD"),
      volume: z.number().positive().describe("Lot size e.g. 0.01 | 0.1 | 1.0"),
      sl:     z.number().describe("Stop loss price (0 = no stop loss)"),
      tp:     z.number().describe("Take profit price (0 = no take profit)"),
    },
    async ({ symbol, volume, sl, tp }) => ({
      content: await fetchJson(buildUrl(config, PATH, { action: "SELL", symbol, volume, sl, tp })),
    })
  );

  // 35. place_buy_limit_order
  server.tool(
    "place_buy_limit_order",
    "Place a buy limit pending order. Entry price must be below the current market.",
    {
      symbol: z.string().describe("Trading instrument"),
      volume: z.number().positive().describe("Lot size"),
      price:  z.number().positive().describe("Limit entry price (below current market)"),
      sl:     z.number().describe("Stop loss price (0 = none)"),
      tp:     z.number().describe("Take profit price (0 = none)"),
    },
    async ({ symbol, volume, price, sl, tp }) => ({
      content: await fetchJson(buildUrl(config, PATH, { action: "BUY_LIMIT", symbol, volume, price, sl, tp })),
    })
  );

  // 36. place_sell_limit_order
  server.tool(
    "place_sell_limit_order",
    "Place a sell limit pending order. Entry price must be above the current market.",
    {
      symbol: z.string().describe("Trading instrument"),
      volume: z.number().positive().describe("Lot size"),
      price:  z.number().positive().describe("Limit entry price (above current market)"),
      sl:     z.number().describe("Stop loss price (0 = none)"),
      tp:     z.number().describe("Take profit price (0 = none)"),
    },
    async ({ symbol, volume, price, sl, tp }) => ({
      content: await fetchJson(buildUrl(config, PATH, { action: "SELL_LIMIT", symbol, volume, price, sl, tp })),
    })
  );

  // 37. place_buy_stop_order
  server.tool(
    "place_buy_stop_order",
    "Place a buy stop pending order. Entry price must be above the current market.",
    {
      symbol: z.string().describe("Trading instrument"),
      volume: z.number().positive().describe("Lot size"),
      price:  z.number().positive().describe("Stop entry price (above current market)"),
      sl:     z.number().describe("Stop loss price (0 = none)"),
      tp:     z.number().describe("Take profit price (0 = none)"),
    },
    async ({ symbol, volume, price, sl, tp }) => ({
      content: await fetchJson(buildUrl(config, PATH, { action: "BUY_STOP", symbol, volume, price, sl, tp })),
    })
  );

  // 38. place_sell_stop_order
  server.tool(
    "place_sell_stop_order",
    "Place a sell stop pending order. Entry price must be below the current market.",
    {
      symbol: z.string().describe("Trading instrument"),
      volume: z.number().positive().describe("Lot size"),
      price:  z.number().positive().describe("Stop entry price (below current market)"),
      sl:     z.number().describe("Stop loss price (0 = none)"),
      tp:     z.number().describe("Take profit price (0 = none)"),
    },
    async ({ symbol, volume, price, sl, tp }) => ({
      content: await fetchJson(buildUrl(config, PATH, { action: "SELL_STOP", symbol, volume, price, sl, tp })),
    })
  );

  // 39. close_position_by_ticket
  server.tool(
    "close_position_by_ticket",
    "Close a specific open position by its MT5 ticket number.",
    {
      ticket: z.number().int().positive().describe("MT5 position ticket number"),
    },
    async ({ ticket }) => ({
      content: await fetchJson(buildUrl(config, PATH, { action: "CLOSE", ticket })),
    })
  );

  // 40. close_all_positions
  server.tool(
    "close_all_positions",
    "Close all currently open positions, or all positions for a specific symbol. Pass symbol='ALL' for everything.",
    {
      symbol: z.string().describe("ALL = close all symbols, or specify a symbol e.g. XAUUSD"),
    },
    async ({ symbol }) => ({
      content: await fetchJson(buildUrl(config, PATH, { action: "CLOSE_ALL", symbol })),
    })
  );

  // 41. close_losing_positions
  server.tool(
    "close_losing_positions",
    "Close only the losing (negative P&L) positions.",
    {
      symbol: z.string().describe("ALL or specific symbol"),
    },
    async ({ symbol }) => ({
      content: await fetchJson(buildUrl(config, PATH, { action: "CLOSE_LOSS", symbol })),
    })
  );

  // 42. close_profitable_positions
  server.tool(
    "close_profitable_positions",
    "Close only the profitable (positive P&L) positions.",
    {
      symbol: z.string().describe("ALL or specific symbol"),
    },
    async ({ symbol }) => ({
      content: await fetchJson(buildUrl(config, PATH, { action: "CLOSE_PROFIT", symbol })),
    })
  );

  // 43. move_position_to_break_even
  server.tool(
    "move_position_to_break_even",
    "Move the stop loss of a specific position to its entry price (break even).",
    {
      ticket: z.number().int().positive().describe("MT5 position ticket number"),
    },
    async ({ ticket }) => ({
      content: await fetchJson(buildUrl(config, PATH, { action: "BREAK_EVEN", ticket })),
    })
  );

  // 44. move_all_positions_to_break_even
  server.tool(
    "move_all_positions_to_break_even",
    "Move all open positions' stop losses to their respective entry prices.",
    {
      symbol: z.string().describe("ALL or specific symbol"),
    },
    async ({ symbol }) => ({
      content: await fetchJson(buildUrl(config, PATH, { action: "BREAK_EVEN_ALL", symbol })),
    })
  );

  // 45. modify_take_profit
  server.tool(
    "modify_take_profit",
    "Modify the take profit price of a specific open position.",
    {
      ticket:    z.number().int().positive().describe("MT5 position ticket number"),
      new_value: z.number().positive().describe("New take profit price level"),
    },
    async ({ ticket, new_value }) => ({
      content: await fetchJson(buildUrl(config, PATH, { action: "MODIFY_TP", ticket, new_value })),
    })
  );

  // 46. modify_stop_loss
  server.tool(
    "modify_stop_loss",
    "Modify the stop loss price of a specific open position.",
    {
      ticket:    z.number().int().positive().describe("MT5 position ticket number"),
      new_value: z.number().positive().describe("New stop loss price level"),
    },
    async ({ ticket, new_value }) => ({
      content: await fetchJson(buildUrl(config, PATH, { action: "MODIFY_SL", ticket, new_value })),
    })
  );

  // 47. delete_pending_order
  server.tool(
    "delete_pending_order",
    "Delete a specific pending (limit or stop) order by its MT5 ticket number.",
    {
      ticket: z.number().int().positive().describe("MT5 pending order ticket number"),
    },
    async ({ ticket }) => ({
      content: await fetchJson(buildUrl(config, PATH, { action: "DELETE_ORDER", ticket })),
    })
  );

  // 48. delete_all_pending_orders
  server.tool(
    "delete_all_pending_orders",
    "Delete all pending (limit and stop) orders on the account.",
    {},
    async () => ({
      content: await fetchJson(buildUrl(config, PATH, { action: "DELETE_ALL_ORDERS" })),
    })
  );

  // 49. trail_stop_loss
  server.tool(
    "trail_stop_loss",
    "Apply a trailing stop loss to a position by a specified distance in points.",
    {
      ticket:    z.number().int().positive().describe("MT5 position ticket number"),
      new_value: z.number().int().positive().describe("Trailing distance in points e.g. 50"),
    },
    async ({ ticket, new_value }) => ({
      content: await fetchJson(buildUrl(config, PATH, { action: "TRAIL_SL", ticket, new_value })),
    })
  );

  // 50. get_open_orders
  server.tool(
    "get_open_orders",
    "Get currently open/running positions with optional filters. Combine filters as needed.",
    {
      filter_running: z.boolean().optional().describe("Include open/running positions"),
      filter_pending: z.boolean().optional().describe("Include pending orders"),
      filter_profit:  z.boolean().optional().describe("Only profitable positions"),
      filter_loss:    z.boolean().optional().describe("Only losing positions"),
      filter_symbol:  z.string().optional().describe("Filter by specific symbol"),
      filter_comment: z.string().optional().describe("Filter by trade comment text"),
    },
    async ({ filter_running, filter_pending, filter_profit, filter_loss, filter_symbol, filter_comment }) => ({
      content: await fetchJson(buildUrl(config, PATH, {
        filter_running: filter_running ? "true" : undefined,
        filter_pending: filter_pending ? "true" : undefined,
        filter_profit:  filter_profit  ? "true" : undefined,
        filter_loss:    filter_loss    ? "true" : undefined,
        filter_symbol,
        filter_comment,
      })),
    })
  );

  // 51. get_trade_history
  server.tool(
    "get_trade_history",
    "Get closed trade history for a time period. Options: today | last-hour | last-10 | last-20 | last-7days | last-30days.",
    {
      history: z.string().describe("Period: today | last-hour | last-10 | last-20 | last-7days | last-30days"),
    },
    async ({ history }) => ({
      content: await fetchJson(buildUrl(config, PATH, { history })),
    })
  );

  // 52. get_profit_summary
  server.tool(
    "get_profit_summary",
    "Get a profit and loss summary for a period. Options: today | last-hour | this-week | this-month | last-7days | last-30days.",
    {
      profit: z.string().describe("Period: today | last-hour | this-week | this-month | last-7days | last-30days"),
    },
    async ({ profit }) => ({
      content: await fetchJson(buildUrl(config, PATH, { profit })),
    })
  );
}
