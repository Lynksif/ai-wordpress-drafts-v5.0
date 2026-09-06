#!/usr/bin/env node

import fs from "node:fs/promises";
import path from "node:path";
import readline from "node:readline";
import { fileURLToPath } from "node:url";
import { COVER_HEIGHT, COVER_WIDTH, MAX_WEBP_BYTES, optimizeCoverToWebp } from "./image-optimizer.mjs";
import { addSiteProfile, envPrefixForSite, getAllSiteProfiles, getDisabledBuiltinIds, getSiteIds, getSiteProfile, publicSiteProfile, registryPath, removeSiteProfile } from "./site-profiles.mjs";
import { validateDraftInput } from "./draft-validator.mjs";

const SERVER_NAME = "ai-wordpress-multisite-drafts";
const SERVER_VERSION = "5.0.0";
const REST_NAMESPACE = "/wp-json/g3ar4ub-ai/v1";
const MAX_IMAGE_BYTES = 8 * 1024 * 1024;
const SUPPORTED_PROTOCOLS = new Set(["2024-11-05", "2025-03-26", "2025-06-18", "2025-11-25"]);
const PLUGIN_ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");

class ConnectorError extends Error {
  constructor(message, details = {}) {
    super(message);
    this.name = "ConnectorError";
    this.details = details;
  }
}

function requireProfile(site) {
  const profile = getSiteProfile(site);
  if (!profile) throw new ConnectorError(`Không tìm thấy site '${site || ""}'. Gọi wordpress_sites để xem registry hiện tại.`);
  return profile;
}

function getConfig(site) {
  const profile = requireProfile(site);
  const prefix = envPrefixForSite(profile.id);
  const username = process.env[`${prefix}_USERNAME`] || "";
  const appPassword = (process.env[`${prefix}_APP_PASSWORD`] || "").replace(/\s+/g, "");
  if (!username || !appPassword) {
    throw new ConnectorError(
      `Chưa cấu hình secret cho ${profile.domain}. Chạy script cấu hình credential tương ứng với macOS, Windows hoặc Linux cho site ${profile.id}.`,
      { site: profile.id }
    );
  }
  return { profile, siteUrl: profile.url, username, appPassword };
}

function isConfigured(site) {
  const prefix = envPrefixForSite(site);
  return Boolean(process.env[`${prefix}_USERNAME`] && process.env[`${prefix}_APP_PASSWORD`]);
}

async function wordpressRequest(site, endpoint, options = {}) {
  const { profile, siteUrl, username, appPassword } = getConfig(site);
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 60000);
  const headers = new Headers(options.headers || {});
  headers.set("Authorization", `Basic ${Buffer.from(`${username}:${appPassword}`).toString("base64")}`);
  headers.set("Accept", "application/json");
  headers.set("User-Agent", `AI-WordPress-Multisite-Connector/${SERVER_VERSION}`);

  try {
    const response = await fetch(`${siteUrl}${endpoint}`, { ...options, headers, signal: controller.signal });
    const raw = await response.text();
    let data = {};
    if (raw) {
      try {
        data = JSON.parse(raw);
      } catch {
        data = { message: raw.slice(0, 500) };
      }
    }
    if (!response.ok) {
      const message = response.status === 401
        ? `Xác thực ${profile.domain} thất bại. Kiểm tra username và Application Password.`
        : data?.message || `${profile.domain} trả về HTTP ${response.status}.`;
      throw new ConnectorError(message, { site: profile.id, status: response.status, code: data?.code || "wordpress_error" });
    }
    return data;
  } catch (error) {
    if (error instanceof ConnectorError) throw error;
    if (error?.name === "AbortError") throw new ConnectorError(`Kết nối ${profile.domain} quá thời gian chờ.`, { site: profile.id });
    throw new ConnectorError(`Không thể kết nối ${profile.domain}: ${error?.message || "unknown error"}`, { site: profile.id });
  } finally {
    clearTimeout(timeout);
  }
}

async function jsonRequest(site, endpoint, method, payload) {
  return wordpressRequest(site, endpoint, {
    method,
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

async function checkHealth(site) {
  const profile = requireProfile(site);
  const health = await wordpressRequest(profile.id, `${REST_NAMESPACE}/health`, { method: "GET" });
  const reportedUrl = health?.site?.url || profile.url;
  let reportedHost = "";
  try {
    reportedHost = new URL(reportedUrl).hostname.replace(/^www\./, "");
  } catch {
    throw new ConnectorError(`Health response của ${profile.domain} không có site URL hợp lệ.`, { site: profile.id });
  }
  if (reportedHost !== profile.domain) {
    throw new ConnectorError(`Chặn kết nối nhầm site: yêu cầu ${profile.domain} nhưng WordPress báo ${reportedHost}.`, { site: profile.id });
  }
  if (health?.site?.profile && health.site.profile !== profile.id) {
    throw new ConnectorError(`Hồ sơ WordPress là ${health.site.profile}, không khớp ${profile.id}.`, { site: profile.id });
  }
  return { requested_site: profile.id, profile: publicSiteProfile(profile, true), ...health };
}

function selectDraftPayload(input, profile) {
  try {
    validateDraftInput(input, profile);
  } catch (error) {
    throw new ConnectorError(error.message, { site: profile.id, code: "draft_preflight_failed" });
  }

  const allowed = ["post_id", "external_id", "title", "slug", "content_html", "excerpt", "categories", "tags", "featured_media", "schema"];
  const payload = {};
  for (const key of allowed) {
    if (Object.prototype.hasOwnProperty.call(input, key)) payload[key] = input[key];
  }

  const seo = {};
  for (const key of ["seo_title", "meta_description", "focus_keyword"]) seo[key] = input.seo[key];
  const social = {};
  for (const key of ["facebook_title", "facebook_description", "facebook_image", "twitter_title", "twitter_description", "twitter_image"]) {
    if (Object.prototype.hasOwnProperty.call(input.seo.social, key)) social[key] = input.seo.social[key];
  }
  seo.social = social;
  payload.seo = seo;
  return payload;
}

function detectImageMime(buffer) {
  if (buffer.length >= 3 && buffer[0] === 0xff && buffer[1] === 0xd8 && buffer[2] === 0xff) return;
  if (buffer.length >= 8 && buffer.subarray(0, 8).equals(Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]))) return;
  if (buffer.length >= 12 && buffer.subarray(0, 4).toString("ascii") === "RIFF" && buffer.subarray(8, 12).toString("ascii") === "WEBP") return;
  if (buffer.length >= 16 && buffer.subarray(4, 8).toString("ascii") === "ftyp" && ["avif", "avis"].includes(buffer.subarray(8, 12).toString("ascii"))) return;
  throw new ConnectorError("Cover phải là JPEG, PNG, WebP hoặc AVIF hợp lệ.");
}

async function uploadCover(args) {
  const profile = requireProfile(args?.site);
  if (typeof args?.file_path !== "string" || !args.file_path.trim()) throw new ConnectorError("file_path là bắt buộc.", { site: profile.id });
  const filePath = path.resolve(args.file_path);
  const stat = await fs.stat(filePath).catch(() => null);
  if (!stat?.isFile()) throw new ConnectorError("Không tìm thấy file cover.", { site: profile.id });
  if (stat.size <= 0 || stat.size > MAX_IMAGE_BYTES) throw new ConnectorError("Cover phải lớn hơn 0 byte và không quá 8 MB.", { site: profile.id });
  detectImageMime(await fs.readFile(filePath));

  let optimized;
  try {
    optimized = await optimizeCoverToWebp(filePath);
  } catch (error) {
    throw new ConnectorError(`Không thể tối ưu cover: ${error?.message || "unknown error"}`, { site: profile.id });
  }

  const form = new FormData();
  form.append("file", new Blob([optimized.buffer], { type: optimized.mime }), optimized.filename);
  form.append("alt_text", args.alt_text);
  if (typeof args.caption === "string") form.append("caption", args.caption);
  const uploaded = await wordpressRequest(profile.id, `${REST_NAMESPACE}/media`, { method: "POST", body: form });
  return {
    site: profile.id,
    domain: profile.domain,
    ...uploaded,
    optimization: {
      source_bytes: stat.size,
      uploaded_bytes: optimized.bytes,
      format: "webp",
      width: optimized.width,
      height: optimized.height,
      quality: optimized.quality,
      encoder: optimized.encoder,
      max_bytes: MAX_WEBP_BYTES,
    },
  };
}

function credentialCommands(site, action = "configure") {
  const unixScript = path.join(PLUGIN_ROOT, "scripts", action === "clear" ? "clear-secrets.sh" : "configure-secrets.sh");
  const windowsScript = path.join(PLUGIN_ROOT, "scripts", action === "clear" ? "Clear-Secrets-Windows.ps1" : "Configure-Secrets-Windows.ps1");
  const unixQuoted = `'${unixScript.replace(/'/g, `'"'"'`)}'`;
  const windowsQuoted = windowsScript.replace(/"/g, '`"');
  return {
    macos_linux: `bash ${unixQuoted} ${site}`,
    windows: `powershell.exe -NoProfile -ExecutionPolicy Bypass -File "${windowsQuoted}" -Site ${site}`,
  };
}

const siteProperty = { type: "string", pattern: "^[a-z0-9][a-z0-9-]{1,39}$", description: "Required destination website profile ID from wordpress_sites." };
const tools = [
  {
    name: "wordpress_sites",
    title: "List managed WordPress sites",
    description: "List built-in and custom website profiles and whether each has local credentials. Never returns secrets.",
    inputSchema: { type: "object", properties: {}, additionalProperties: false },
    annotations: { readOnlyHint: true, destructiveHint: false, openWorldHint: false },
  },
  {
    name: "wordpress_site_add",
    title: "Add or restore a WordPress website profile",
    description: "Add one custom HTTPS WordPress site, or restore a previously removed built-in site, in the local connector registry. Does not accept credentials and does not change the remote WordPress site. Install and configure WordPress Connector v5 on the domain first.",
    inputSchema: {
      type: "object",
      properties: {
        id: { type: "string", pattern: "^[a-z0-9][a-z0-9-]{1,39}$", description: "Stable local ID, for example example-site." },
        domain: { type: "string", description: "Hostname only, for example example.com. HTTPS is enforced." },
        brand: { type: "string", minLength: 2, maxLength: 80 },
        seo_provider: { type: "string", enum: ["auto", "seopress", "rank_math", "yoast", "none"], default: "auto" },
        watermark: { type: "string", minLength: 2, maxLength: 80, description: "Optional; defaults to the uppercase domain." },
        style: { type: "string", minLength: 3, maxLength: 500, description: "Optional visual direction for generated drafts." },
        editorial: { type: "string", minLength: 3, maxLength: 500, description: "Optional editorial scope." },
      },
      required: ["id", "domain", "brand", "seo_provider"],
      additionalProperties: false,
    },
    annotations: { readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: false },
  },
  {
    name: "wordpress_site_remove",
    title: "Remove a website from the local connector",
    description: "Remove an active profile from the local connector registry after exact domain confirmation. A built-in profile is disabled locally and can be restored later. Never deletes a WordPress site, post, media, user, or remote data.",
    inputSchema: {
      type: "object",
      properties: {
        site: siteProperty,
        confirm_domain: { type: "string", description: "Exact stored domain required as a deletion guard." },
      },
      required: ["site", "confirm_domain"],
      additionalProperties: false,
    },
    annotations: { readOnlyHint: false, destructiveHint: true, idempotentHint: false, openWorldHint: false },
  },
  {
    name: "wordpress_health",
    title: "Check one WordPress connection",
    description: "Verify the selected domain, WordPress site profile, draft-only mode, authenticated user, HTTPS, SEO provider, and safety guarantees.",
    inputSchema: { type: "object", properties: { site: siteProperty }, required: ["site"], additionalProperties: false },
    annotations: { readOnlyHint: true, destructiveHint: false, openWorldHint: true },
  },
  {
    name: "wordpress_upload_cover",
    title: "Optimize and upload one site cover",
    description: `Upload a cover to the explicitly selected site. Input is center-cropped to ${COVER_WIDTH}x${COVER_HEIGHT}, converted to WebP, stripped of metadata, and reduced to at most ${Math.round(MAX_WEBP_BYTES / 1024)} KB. The source must use the selected site's branding and watermark.`,
    inputSchema: {
      type: "object",
      properties: {
        site: siteProperty,
        file_path: { type: "string", description: "Absolute or workspace-relative source image path. The source is not modified." },
        alt_text: { type: "string", description: "SEO-friendly image alt text for the selected site." },
        caption: { type: "string", description: "Optional WordPress media caption." },
      },
      required: ["site", "file_path", "alt_text"],
      additionalProperties: false,
    },
    annotations: { readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: true },
  },
  {
    name: "wordpress_create_or_update_draft",
    title: "Create or update one site draft",
    description: "Create or update a review-ready draft only on the explicitly selected site. Preflight requires the matching g3ai site class, semantic lists, complete SEO/social fields, and a linked Schema @graph for that exact domain. No publish or delete capability exists.",
    inputSchema: {
      type: "object",
      properties: {
        site: siteProperty,
        post_id: { type: "integer", minimum: 1 },
        external_id: { type: "string", maxLength: 100 },
        title: { type: "string" },
        slug: { type: "string" },
        content_html: { type: "string", description: "Exactly one outer article with g3ai-article plus the selected profile class. Use semantic ul/ol/li and supported g3ai-* runtime components; no style/script/inline handlers." },
        excerpt: { type: "string" },
        categories: { type: "array", items: { anyOf: [{ type: "integer" }, { type: "string" }] } },
        tags: { type: "array", items: { anyOf: [{ type: "integer" }, { type: "string" }] } },
        featured_media: { type: "integer", minimum: 0 },
        seo: {
          type: "object",
          properties: {
            seo_title: { type: "string" },
            meta_description: { type: "string" },
            focus_keyword: { type: "string" },
            social: {
              type: "object",
              properties: {
                facebook_title: { type: "string" }, facebook_description: { type: "string" }, facebook_image: { type: "string", format: "uri" },
                twitter_title: { type: "string" }, twitter_description: { type: "string" }, twitter_image: { type: "string", format: "uri" },
              },
              required: ["facebook_title", "facebook_description", "twitter_title", "twitter_description"],
              additionalProperties: false,
            },
          },
          required: ["seo_title", "meta_description", "focus_keyword", "social"],
          additionalProperties: false,
        },
        schema: { description: "Complete linked JSON-LD @graph for the selected domain with Organization, WebSite, WebPage, BreadcrumbList, and one appropriate article node." },
      },
      required: ["site", "external_id", "title", "content_html", "seo", "schema"],
      additionalProperties: false,
    },
    annotations: { readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: true },
  },
  {
    name: "wordpress_get_draft",
    title: "Read one site draft",
    description: "Read a connector-managed draft from the explicitly selected site to verify status, HTML, SEO metadata, Schema, and preview links.",
    inputSchema: { type: "object", properties: { site: siteProperty, post_id: { type: "integer", minimum: 1 } }, required: ["site", "post_id"], additionalProperties: false },
    annotations: { readOnlyHint: true, destructiveHint: false, openWorldHint: true },
  },
];

function successResult(data) {
  return { content: [{ type: "text", text: JSON.stringify(data, null, 2) }], structuredContent: data, isError: false };
}

function errorResult(error) {
  const payload = { error: error?.message || "Unknown connector error", ...(error instanceof ConnectorError ? error.details : {}) };
  return { content: [{ type: "text", text: payload.error }], structuredContent: payload, isError: true };
}

async function callTool(name, args) {
  switch (name) {
    case "wordpress_sites": {
      const profiles = getAllSiteProfiles();
      return successResult({
        registry_file: registryPath(),
        disabled_builtin_sites: getDisabledBuiltinIds(),
        sites: Object.values(profiles).map((profile) => publicSiteProfile(profile, isConfigured(profile.id))),
      });
    }
    case "wordpress_site_add": {
      let profile;
      try {
        profile = addSiteProfile(args);
      } catch (error) {
        throw new ConnectorError(error.message, { code: "site_add_failed" });
      }
      return successResult({
        added: publicSiteProfile(profile, false),
        remote_wordpress_changed: false,
        next_step: "Chạy đúng lệnh local bên dưới để nhập username và Application Password ẩn, sau đó khởi động lại Codex và gọi wordpress_health.",
        credential_commands: credentialCommands(profile.id),
      });
    }
    case "wordpress_site_remove": {
      let profile;
      try {
        profile = removeSiteProfile(args?.site, args?.confirm_domain);
      } catch (error) {
        throw new ConnectorError(error.message, { site: args?.site, code: "site_remove_failed" });
      }
      return successResult({
        removed: { id: profile.id, domain: profile.domain, removal_type: profile.removalType },
        local_profile_removed: true,
        remote_wordpress_changed: false,
        remote_data_deleted: false,
        note: profile.removalType === "builtin_disabled"
          ? "Profile built-in đã bị ẩn khỏi registry local và có thể khôi phục bằng wordpress_site_add với cùng ID/domain. Credential vẫn được giữ cho đến khi người dùng chủ động chạy lệnh xóa bên dưới."
          : "Profile custom đã được gỡ khỏi registry local. Credential vẫn được giữ cho đến khi người dùng chủ động chạy lệnh xóa bên dưới.",
        credential_cleanup_commands: credentialCommands(profile.id, "clear"),
      });
    }
    case "wordpress_health":
      return successResult(await checkHealth(args?.site));
    case "wordpress_upload_cover":
      return successResult(await uploadCover(args));
    case "wordpress_create_or_update_draft": {
      const profile = requireProfile(args?.site);
      const health = await checkHealth(profile.id);
      if (health.mode !== "draft_only" || !health.guarantees?.creates_drafts_only || !health.guarantees?.no_publish_endpoint || !health.guarantees?.no_delete_endpoint) {
        throw new ConnectorError(`Khóa an toàn draft-only của ${profile.domain} không đầy đủ.`, { site: profile.id });
      }
      const result = await jsonRequest(profile.id, `${REST_NAMESPACE}/drafts`, "POST", selectDraftPayload(args, profile));
      return successResult({ site: profile.id, domain: profile.domain, ...result });
    }
    case "wordpress_get_draft": {
      const profile = requireProfile(args?.site);
      const postId = Number(args?.post_id);
      if (!Number.isInteger(postId) || postId < 1) throw new ConnectorError("post_id không hợp lệ.", { site: profile.id });
      const result = await wordpressRequest(profile.id, `${REST_NAMESPACE}/drafts/${postId}`, { method: "GET" });
      return successResult({ site: profile.id, domain: profile.domain, ...result });
    }
    default:
      throw new ConnectorError(`Không có tool: ${name}`);
  }
}

function writeMessage(message) {
  process.stdout.write(`${JSON.stringify(message)}\n`);
}

function writeError(id, code, message, data) {
  writeMessage({ jsonrpc: "2.0", id: id ?? null, error: { code, message, ...(data ? { data } : {}) } });
}

async function handleMessage(message) {
  if (!message || message.jsonrpc !== "2.0" || typeof message.method !== "string") {
    writeError(message?.id, -32600, "Invalid Request");
    return;
  }
  const isNotification = !Object.prototype.hasOwnProperty.call(message, "id");
  try {
    let result;
    switch (message.method) {
      case "initialize": {
        const requested = message.params?.protocolVersion;
        result = {
          protocolVersion: SUPPORTED_PROTOCOLS.has(requested) ? requested : "2025-06-18",
          capabilities: { tools: { listChanged: false } },
          serverInfo: { name: SERVER_NAME, version: SERVER_VERSION },
          instructions: `The connector has ${getSiteIds().length} managed WordPress profiles (built-in plus local custom profiles). Every content operation must name one site and verify health before writing. wordpress_site_add and wordpress_site_remove change only the local routing registry; they never alter a remote WordPress installation. The server only uploads covers, creates or updates connector-managed drafts, and reads drafts; it has no publish or remote delete tools.`,
        };
        break;
      }
      case "notifications/initialized":
      case "notifications/cancelled":
        return;
      case "ping":
        result = {};
        break;
      case "tools/list":
        result = { tools };
        break;
      case "tools/call":
        try {
          result = await callTool(message.params?.name, message.params?.arguments || {});
        } catch (error) {
          result = errorResult(error);
        }
        break;
      default:
        if (!isNotification) writeError(message.id, -32601, "Method not found");
        return;
    }
    if (!isNotification) writeMessage({ jsonrpc: "2.0", id: message.id, result });
  } catch (error) {
    if (!isNotification) writeError(message.id, -32603, "Internal error", { message: error?.message });
  }
}

const input = readline.createInterface({ input: process.stdin, crlfDelay: Infinity, terminal: false });
input.on("line", (line) => {
  if (!line.trim()) return;
  try {
    void handleMessage(JSON.parse(line));
  } catch {
    writeError(null, -32700, "Parse error");
  }
});

process.on("uncaughtException", (error) => {
  process.stderr.write(`AI WordPress Multisite MCP fatal error: ${error?.message || "unknown"}\n`);
  process.exitCode = 1;
});
