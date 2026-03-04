import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import { buildUrl, fetchJson } from "../api.js";
import type { Config } from "../config.js";

const PATH = "/url-api-v1/url-api.php";

export function registerWebContentTools(server: McpServer, config: Config): void {

  // 56. fetch_url_content
  server.tool(
    "fetch_url_content",
    "Fetch and extract meaningful text content (page title + main body text) from any public web page URL. Response includes source_name (auto-derived website name), title, content, http_status, content_length, attempts.",
    {
      url: z.string().url().describe("Complete target URL starting with http:// or https://"),
    },
    async ({ url }) => ({
      content: await fetchJson(buildUrl(config, PATH, { url })),
    })
  );

  // 57. fetch_url_with_basic_auth
  server.tool(
    "fetch_url_with_basic_auth",
    "Fetch content from a URL protected by HTTP Basic Authentication.",
    {
      url:       z.string().url().describe("Target URL"),
      auth_user: z.string().describe("HTTP basic auth username"),
      auth_pass: z.string().describe("HTTP basic auth password"),
    },
    async ({ url, auth_user, auth_pass }) => ({
      content: await fetchJson(buildUrl(config, PATH, { url, auth_user, auth_pass })),
    })
  );

  // 58. fetch_url_with_bearer_token
  server.tool(
    "fetch_url_with_bearer_token",
    "Fetch content from a URL using Bearer token authentication (OAuth / JWT). Sends Authorization: Bearer {token}.",
    {
      url:          z.string().url().describe("Target URL"),
      bearer_token: z.string().describe("OAuth or JWT bearer token"),
    },
    async ({ url, bearer_token }) => ({
      content: await fetchJson(buildUrl(config, PATH, { url, bearer_token })),
    })
  );

  // 59. fetch_url_with_target_api_key
  server.tool(
    "fetch_url_with_target_api_key",
    "Fetch content from a URL that requires an API key passed via a custom HTTP header.",
    {
      url:          z.string().url().describe("Target URL"),
      target_key:   z.string().describe("The API key to send to the target URL"),
      api_key_name: z.string().optional().describe("HTTP header name (default: X-API-Key)"),
    },
    async ({ url, target_key, api_key_name }) => ({
      content: await fetchJson(buildUrl(config, PATH, { url, target_key, api_key_name })),
    })
  );

  // 60. fetch_url_with_session_cookie
  server.tool(
    "fetch_url_with_session_cookie",
    "Fetch content from a URL using a session cookie for authentication.",
    {
      url:            z.string().url().describe("Target URL"),
      session_cookie: z.string().describe("Session cookie string e.g. session_id=abc123def456"),
    },
    async ({ url, session_cookie }) => ({
      content: await fetchJson(buildUrl(config, PATH, { url, session_cookie })),
    })
  );

  // 61. fetch_url_with_custom_headers
  server.tool(
    "fetch_url_with_custom_headers",
    "Fetch content from a URL by injecting custom HTTP headers. Accepts any authentication method or special header requirements.",
    {
      url:            z.string().url().describe("Target URL"),
      custom_headers: z.string().describe('JSON-encoded headers object e.g. {"Authorization":"Bearer token123","Accept":"application/json"}'),
    },
    async ({ url, custom_headers }) => ({
      content: await fetchJson(buildUrl(config, PATH, { url, custom_headers })),
    })
  );

  // 62. fetch_reuters_economic_news
  server.tool(
    "fetch_reuters_economic_news",
    "Fetch the latest global economic news headlines and summaries from Reuters World Economy (reuters.com/markets/econ-world/).",
    {},
    async () => ({
      content: await fetchJson(buildUrl(config, PATH, { url: "https://www.reuters.com/markets/econ-world/" })),
    })
  );

  // 63. fetch_yahoo_finance_economy
  server.tool(
    "fetch_yahoo_finance_economy",
    "Fetch the latest economy topic articles and summaries from Yahoo Finance (sg.finance.yahoo.com/topic/economy/).",
    {},
    async () => ({
      content: await fetchJson(buildUrl(config, PATH, { url: "https://sg.finance.yahoo.com/topic/economy/" })),
    })
  );
}
