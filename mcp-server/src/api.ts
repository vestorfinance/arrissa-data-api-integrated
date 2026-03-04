import type { Config } from "./config.js";

export type TextResult  = { type: "text"; text: string };
export type ImageResult = { type: "image"; data: string; mimeType: string };
export type ToolContent = TextResult | ImageResult;

/** Fetch a JSON API endpoint and return it as a pretty-printed text result. */
export async function fetchJson(url: string): Promise<ToolContent[]> {
  const res = await fetch(url);
  const text = await res.text();
  let pretty: string;
  try {
    pretty = JSON.stringify(JSON.parse(text), null, 2);
  } catch {
    pretty = text;
  }
  return [{ type: "text", text: pretty }];
}

/** Fetch an image endpoint and return it as a base64 image result. */
export async function fetchImage(url: string): Promise<ToolContent[]> {
  const res = await fetch(url);
  const contentType = res.headers.get("content-type") ?? "image/png";

  if (contentType.startsWith("image/")) {
    const buf    = await res.arrayBuffer();
    const base64 = Buffer.from(buf).toString("base64");
    return [{ type: "image", data: base64, mimeType: contentType }];
  }

  // Gracefully fall back to JSON text if not actually an image
  const text = await res.text();
  return [{ type: "text", text }];
}

/** Build a URL string from a base URL, path, and query parameters.
 *  Skips undefined/null/"" values automatically. */
export function buildUrl(
  config: Config,
  apiPath: string,
  params: Record<string, string | number | boolean | undefined | null>
): string {
  const base = `${config.baseUrl}${apiPath}`;
  const qs   = new URLSearchParams();
  qs.set("api_key", config.apiKey);
  for (const [k, v] of Object.entries(params)) {
    if (v !== undefined && v !== null && v !== "") {
      qs.set(k, String(v));
    }
  }
  return `${base}?${qs.toString()}`;
}
