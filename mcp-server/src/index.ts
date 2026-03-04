import express from "express";
import { randomUUID } from "crypto";
import { StreamableHTTPServerTransport } from "@modelcontextprotocol/sdk/server/streamableHttp.js";
import { isInitializeRequest } from "@modelcontextprotocol/sdk/types.js";
import { loadConfig } from "./config.js";
import { createMcpServer } from "./server.js";

// ── Config ────────────────────────────────────────────────────────────────────
const config = loadConfig();
const PORT   = Number(process.env.PORT ?? 3000);
const HOST   = process.env.HOST ?? "0.0.0.0";

console.log(`Arrissa Data MCP Server`);
console.log(`  Base URL : ${config.baseUrl}`);
console.log(`  API Key  : ${config.apiKey ? config.apiKey.slice(0, 8) + "****" : "(not set)"}`);

// ── HTTP server ───────────────────────────────────────────────────────────────
const app = express();
app.use(express.json());

// ── Header API key auth — all /mcp routes require x-api-key ──────────────────
app.use("/mcp", (req, res, next) => {
  const provided = ((req.headers["x-api-key"] as string | undefined) ?? "").trim();
  if (!provided || provided !== config.apiKey) {
    res.status(401).json({
      error: "Unauthorized",
      message: "Provide your Arrissa API key in the x-api-key request header",
    });
    return;
  }
  next();
});

// In-memory session store for stateful session reuse (optional but MCP-compliant)
const sessions = new Map<string, StreamableHTTPServerTransport>();

// ── POST /mcp  — accept MCP requests (stateless + stateful session support) ──
app.post("/mcp", async (req, res) => {
  try {
    const sessionId = req.headers["mcp-session-id"] as string | undefined;
    let transport: StreamableHTTPServerTransport;

    if (sessionId && sessions.has(sessionId)) {
      // Reuse existing session
      transport = sessions.get(sessionId)!;
    } else if (!sessionId && isInitializeRequest(req.body)) {
      // New session — create server + transport
      const mcpServer = createMcpServer(config);
      const newSessionId = randomUUID();
      transport = new StreamableHTTPServerTransport({
        sessionIdGenerator: () => newSessionId,
        onsessioninitialized: (id) => {
          sessions.set(id, transport);
          // Clean up after 1 hour of inactivity (generous for AI workflows)
          setTimeout(() => sessions.delete(id), 60 * 60 * 1000);
        },
      });
      await mcpServer.connect(transport);
    } else if (!sessionId) {
      // Stateless request (no session management needed)
      const mcpServer = createMcpServer(config);
      transport = new StreamableHTTPServerTransport({ sessionIdGenerator: undefined });
      await mcpServer.connect(transport);
      await transport.handleRequest(req, res, req.body);
      return;
    } else {
      res.status(400).json({ error: "Invalid or expired session ID" });
      return;
    }

    await transport.handleRequest(req, res, req.body);
  } catch (err) {
    console.error("MCP request error:", err);
    if (!res.headersSent) {
      res.status(500).json({ error: "Internal server error" });
    }
  }
});

// ── GET /mcp  — SSE stream for server-initiated messages ─────────────────────
app.get("/mcp", async (req, res) => {
  try {
    const sessionId = req.headers["mcp-session-id"] as string | undefined;
    if (!sessionId || !sessions.has(sessionId)) {
      res.status(400).json({ error: "Valid mcp-session-id header required for SSE" });
      return;
    }
    const transport = sessions.get(sessionId)!;
    await transport.handleRequest(req, res);
  } catch (err) {
    console.error("SSE error:", err);
    if (!res.headersSent) res.status(500).json({ error: "Internal server error" });
  }
});

// ── DELETE /mcp  — close session ─────────────────────────────────────────────
app.delete("/mcp", async (req, res) => {
  const sessionId = req.headers["mcp-session-id"] as string | undefined;
  if (sessionId && sessions.has(sessionId)) {
    const transport = sessions.get(sessionId)!;
    await transport.close();
    sessions.delete(sessionId);
  }
  res.status(200).json({ ok: true });
});

// ── GET /health ───────────────────────────────────────────────────────────────
app.get("/health", (_req, res) => {
  res.json({
    status: "ok",
    server: "arrissa-data-mcp",
    version: "1.0.0",
    baseUrl: config.baseUrl,
    activeSessions: sessions.size,
    timestamp: new Date().toISOString(),
  });
});

// ── GET / ─────────────────────────────────────────────────────────────────────
app.get("/", (_req, res) => {
  res.json({
    name: "Arrissa Data MCP Server",
    version: "1.0.0",
    transport: "HTTP Streamable (MCP 2025-03-26 spec)",
    endpoint: "/mcp",
    health: "/health",
    tools: 63,
    categories: [
      "market-data (8 tools)",
      "chart-images (9 tools)",
      "economic-calendar (15 tools)",
      "orders (20 tools)",
      "market-analysis (3 tools)",
      "web-content (8 tools)",
    ],
  });
});

// ── Start ─────────────────────────────────────────────────────────────────────
app.listen(PORT, HOST, () => {
  console.log(`\nListening on http://${HOST}:${PORT}`);
  console.log(`  MCP endpoint : http://${HOST}:${PORT}/mcp`);
  console.log(`  Health check : http://${HOST}:${PORT}/health`);
  console.log(`  Ready — 63 tools registered across 6 categories.\n`);
});
