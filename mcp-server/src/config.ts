import Database from "better-sqlite3";
import path from "path";
import { fileURLToPath } from "url";

export interface Config {
  baseUrl: string;
  apiKey: string;
}

export function loadConfig(): Config {
  // Accept env-var overrides so docker / remote deploys don't need the DB
  const envBase = process.env.ARRISSA_BASE_URL;
  const envKey  = process.env.ARRISSA_API_KEY;
  if (envBase && envKey) {
    return { baseUrl: envBase.replace(/\/$/, ""), apiKey: envKey };
  }

  // Fall back to the SQLite database that lives one level above mcp-server/
  const __dirname = path.dirname(fileURLToPath(import.meta.url));
  const dbPath    = path.resolve(__dirname, "../../database/app.db");

  let db: Database.Database;
  try {
    db = new Database(dbPath, { readonly: true });
  } catch {
    // Try the root app.db path
    const alt = path.resolve(__dirname, "../../app.db");
    db = new Database(alt, { readonly: true });
  }

  const get = (key: string): string => {
    const row = db.prepare("SELECT value FROM settings WHERE key = ?").get(key) as
      | { value: string }
      | undefined;
    return row?.value ?? "";
  };

  const baseUrl = envBase ?? get("app_base_url") ?? "http://localhost";
  const apiKey  = envKey  ?? get("api_key")      ?? "";

  db.close();
  return { baseUrl: baseUrl.replace(/\/$/, ""), apiKey };
}
