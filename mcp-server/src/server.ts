import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import type { Config } from "./config.js";
import { registerMarketDataTools }       from "./tools/market-data.js";
import { registerChartImageTools }       from "./tools/chart-images.js";
import { registerEconomicCalendarTools } from "./tools/economic-calendar.js";
import { registerOrdersTools }           from "./tools/orders.js";
import { registerMarketAnalysisTools }   from "./tools/market-analysis.js";
import { registerWebContentTools }       from "./tools/web-content.js";

/** Create a fresh McpServer with all 63 tools registered. */
export function createMcpServer(config: Config): McpServer {
  const server = new McpServer(
    { name: "arrissa-data", version: "1.0.0" },
    {
      capabilities: {
        tools: {},
      },
    }
  );

  registerMarketDataTools(server, config);
  registerChartImageTools(server, config);
  registerEconomicCalendarTools(server, config);
  registerOrdersTools(server, config);
  registerMarketAnalysisTools(server, config);
  registerWebContentTools(server, config);

  return server;
}
