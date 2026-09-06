import fs from "node:fs";
import os from "node:os";
import path from "node:path";

const SEO_PROVIDERS = new Set(["auto", "seopress", "rank_math", "yoast", "none"]);

export const BUILTIN_SITE_PROFILES = Object.freeze({
  g3ar4ub: Object.freeze({
    id: "g3ar4ub", domain: "g3ar4ub.com", url: "https://g3ar4ub.com", brand: "G3AR4UB",
    watermark: "G3AR4UB.COM", rootClass: "g3ai-site-g3ar4ub",
    style: "Cyberpunk esports glassmorphism with neon cyan, purple, pink, and green.",
    editorial: "Gaming gear, AI, streaming, operating systems, technology, and esports.", source: "builtin",
  }),
  bootstup: Object.freeze({
    id: "bootstup", domain: "bootstup.org", url: "https://bootstup.org", brand: "BootStup",
    watermark: "BOOTSTUP.ORG", rootClass: "g3ai-site-bootstup",
    style: "Technical SOC terminal glass UI with dark slate, electric blue, and security green.",
    editorial: "Cybersecurity, SOC, OSINT, networking, malware analysis, operating systems, and technical tutorials.", source: "builtin",
  }),
  beportsquad: Object.freeze({
    id: "beportsquad", domain: "beportsquad.com", url: "https://beportsquad.com", brand: "BeportSquad",
    watermark: "BEPORTSQUAD.COM", rootClass: "g3ai-site-beportsquad",
    style: "Dark cinematic mystery glass UI with crimson, violet, smoke, and restrained horror accents.",
    editorial: "Mysteries, folklore, supernatural stories, entertainment, esports, and culture.", source: "builtin",
  }),
  sporttokvn: Object.freeze({
    id: "sporttokvn", domain: "sporttokvn.com", url: "https://sporttokvn.com", brand: "SportTokVN",
    watermark: "SPORTTOKVN.COM", rootClass: "g3ai-site-sporttokvn",
    style: "Fast sports newsroom glass UI with navy, electric blue, red, and gold highlights.",
    editorial: "Sports and esports news, schedules, match coverage, analysis, and community stories.", source: "builtin",
  }),
  rayesports: Object.freeze({
    id: "rayesports", domain: "rayesports.com", url: "https://rayesports.com", brand: "RAYbet",
    watermark: "RAYESPORTS.COM", rootClass: "g3ai-site-rayesports",
    style: "Esports match-center newsroom with carbon navy, RAYbet yellow, live-data cyan, and restrained red alerts.",
    editorial: "Vietnamese esports news, schedules, match coverage, tournaments, teams, analysis, and responsible odds context.",
    seoProvider: "rank_math", source: "builtin",
  }),
  "99ggcloud": Object.freeze({
    id: "99ggcloud", domain: "99ggcloud.com", url: "https://99ggcloud.com", brand: "9GG CLOUD",
    watermark: "99GGCLOUD.COM", rootClass: "g3ai-site-99ggcloud",
    style: "Esports cloud-lab dashboard with midnight navy, data cyan, GPU violet, healthy-status lime, and restrained latency orange.",
    editorial: "Independent cloud gaming tests, game server hosting guides, latency tools, GPU cloud benchmarks, provider comparisons, setup tutorials, and real-player buying guidance.",
    seoProvider: "rank_math", source: "builtin",
  }),
  chillspec: Object.freeze({
    id: "chillspec", domain: "chillspec.com", url: "https://chillspec.com", brand: "ChillSpec",
    watermark: "CHILLSPEC.COM", rootClass: "g3ai-site-chillspec",
    style: "Clean technical review glass UI with graphite, ice blue, violet, and mint status accents.",
    editorial: "Buyer guides, desk setups, practical knowledge, and technology and gear reviews.",
    seoProvider: "seopress", source: "builtin",
  }),
});

export function registryPath() {
  if (process.env.AIWP_SITES_FILE) return path.resolve(process.env.AIWP_SITES_FILE);
  if (process.platform === "win32") {
    const base = process.env.LOCALAPPDATA || path.join(os.homedir(), "AppData", "Local");
    return path.join(base, "G3AR4UB", "WordPressDrafts", "sites.json");
  }
  const base = process.env.XDG_CONFIG_HOME || path.join(os.homedir(), ".config");
  return path.join(base, "g3ar4ub-wordpress-drafts", "sites.json");
}

function normalizeId(value) {
  const id = typeof value === "string" ? value.trim().toLowerCase() : "";
  if (!/^[a-z0-9][a-z0-9-]{1,39}$/.test(id)) {
    throw new Error("id phải dài 2-40 ký tự, chỉ gồm chữ thường, số và dấu gạch ngang.");
  }
  return id;
}

function normalizeDomain(value) {
  let raw = typeof value === "string" ? value.trim().toLowerCase() : "";
  raw = raw.replace(/^https?:\/\//, "").replace(/^www\./, "").replace(/\/$/, "");
  if (raw.includes("/") || raw.includes(":") || !raw.includes(".") || raw.length > 253) {
    throw new Error("domain phải là hostname thuần, ví dụ example.com; không gồm giao thức, cổng hoặc đường dẫn.");
  }
  const labels = raw.split(".");
  if (labels.some((label) => !/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/.test(label))) {
    throw new Error("domain không hợp lệ.");
  }
  return raw;
}

function normalizeText(value, name, minimum, maximum) {
  const text = typeof value === "string" ? value.trim() : "";
  if (text.length < minimum || text.length > maximum) throw new Error(`${name} phải dài ${minimum}-${maximum} ký tự.`);
  return text;
}

function normalizeProfile(input) {
  const id = normalizeId(input?.id);
  const domain = normalizeDomain(input?.domain);
  const brand = normalizeText(input?.brand, "brand", 2, 80);
  const seoInput = input?.seo_provider ?? input?.seoProvider;
  const seoProvider = typeof seoInput === "string" ? seoInput.trim().toLowerCase() : "auto";
  if (!SEO_PROVIDERS.has(seoProvider)) throw new Error("seo_provider phải là auto, seopress, rank_math, yoast hoặc none.");
  const rootClass = `g3ai-site-${id}`;
  return {
    id,
    domain,
    url: `https://${domain}`,
    brand,
    watermark: input?.watermark ? normalizeText(input.watermark, "watermark", 2, 80) : domain.toUpperCase(),
    rootClass,
    style: input?.style ? normalizeText(input.style, "style", 3, 500) : `Clean editorial design aligned with ${brand}.`,
    editorial: input?.editorial ? normalizeText(input.editorial, "editorial", 3, 500) : `Editorial content for ${brand}.`,
    seoProvider,
    source: "custom",
  };
}

function readRegistry() {
  const file = registryPath();
  if (!fs.existsSync(file)) return { custom: {}, disabledBuiltins: new Set() };
  let parsed;
  try {
    parsed = JSON.parse(fs.readFileSync(file, "utf8"));
  } catch (error) {
    throw new Error(`Không đọc được registry website ${file}: ${error.message}`);
  }
  if (!parsed || parsed.version !== 1 || !Array.isArray(parsed.sites)) throw new Error(`Registry website không đúng định dạng: ${file}`);
  const profiles = {};
  for (const item of parsed.sites) {
    const profile = normalizeProfile(item);
    if (BUILTIN_SITE_PROFILES[profile.id]) throw new Error(`Registry custom trùng profile hệ thống: ${profile.id}`);
    if (Object.values(BUILTIN_SITE_PROFILES).some((builtIn) => builtIn.domain === profile.domain)) {
      throw new Error(`Registry custom trùng domain hệ thống: ${profile.domain}`);
    }
    profiles[profile.id] = profile;
  }
  const disabled = Array.isArray(parsed.disabled_builtins) ? parsed.disabled_builtins : [];
  const disabledBuiltins = new Set();
  for (const id of disabled) {
    if (typeof id !== "string" || !BUILTIN_SITE_PROFILES[id]) throw new Error(`Registry chứa built-in ID không hợp lệ: ${id}`);
    disabledBuiltins.add(id);
  }
  return { custom: profiles, disabledBuiltins };
}

function writeRegistry(custom, disabledBuiltins) {
  const file = registryPath();
  fs.mkdirSync(path.dirname(file), { recursive: true, mode: 0o700 });
  const payload = {
    version: 1,
    sites: Object.values(custom).sort((a, b) => a.id.localeCompare(b.id)),
    disabled_builtins: [...disabledBuiltins].sort(),
  };
  const temporary = `${file}.${process.pid}.tmp`;
  fs.writeFileSync(temporary, `${JSON.stringify(payload, null, 2)}\n`, { mode: 0o600 });
  fs.renameSync(temporary, file);
  try { fs.chmodSync(file, 0o600); } catch { /* Windows ACLs are used instead. */ }
}

export function getAllSiteProfiles() {
  const registry = readRegistry();
  const builtins = Object.fromEntries(
    Object.entries(BUILTIN_SITE_PROFILES).filter(([id]) => !registry.disabledBuiltins.has(id))
  );
  return { ...builtins, ...registry.custom };
}

export function getDisabledBuiltinIds() {
  return [...readRegistry().disabledBuiltins].sort();
}

export function getSiteIds() {
  return Object.keys(getAllSiteProfiles());
}

export function getSiteProfile(site) {
  const id = typeof site === "string" ? site.trim().toLowerCase() : "";
  return getAllSiteProfiles()[id] || null;
}

export function addSiteProfile(input) {
  const profile = normalizeProfile(input);
  const registry = readRegistry();
  const builtIn = BUILTIN_SITE_PROFILES[profile.id];
  if (builtIn) {
    if (!registry.disabledBuiltins.has(profile.id)) throw new Error(`Website ID đã tồn tại: ${profile.id}.`);
    if (profile.domain !== builtIn.domain) throw new Error(`Domain của profile hệ thống ${profile.id} phải là ${builtIn.domain}.`);
    registry.disabledBuiltins.delete(profile.id);
    writeRegistry(registry.custom, registry.disabledBuiltins);
    return builtIn;
  }
  const all = getAllSiteProfiles();
  if (all[profile.id]) throw new Error(`Website ID đã tồn tại: ${profile.id}.`);
  if (Object.values(BUILTIN_SITE_PROFILES).some((item) => item.domain === profile.domain) || Object.values(all).some((item) => item.domain === profile.domain)) {
    throw new Error(`Domain đã tồn tại hoặc thuộc catalog hệ thống: ${profile.domain}.`);
  }
  registry.custom[profile.id] = profile;
  writeRegistry(registry.custom, registry.disabledBuiltins);
  return profile;
}

export function removeSiteProfile(site, confirmDomain) {
  const id = normalizeId(site);
  const registry = readRegistry();
  const profile = BUILTIN_SITE_PROFILES[id] || registry.custom[id];
  if (!profile || registry.disabledBuiltins.has(id)) throw new Error(`Không tìm thấy profile đang hoạt động: ${id}.`);
  if (normalizeDomain(confirmDomain) !== profile.domain) throw new Error("confirm_domain không khớp domain đã lưu; không thay đổi registry.");
  let removalType = "custom_removed";
  if (BUILTIN_SITE_PROFILES[id]) {
    registry.disabledBuiltins.add(id);
    removalType = "builtin_disabled";
  } else {
    delete registry.custom[id];
  }
  writeRegistry(registry.custom, registry.disabledBuiltins);
  return { ...profile, removalType };
}

export function envPrefixForSite(site) {
  return `AIWP_${String(site).toUpperCase().replace(/[^A-Z0-9]/g, "_")}`;
}

export function publicSiteProfile(profile, configured) {
  return {
    id: profile.id,
    domain: profile.domain,
    url: profile.url,
    brand: profile.brand,
    watermark: profile.watermark,
    root_class: profile.rootClass,
    style: profile.style,
    editorial: profile.editorial,
    preferred_seo: profile.seoProvider || "auto",
    source: profile.source || "custom",
    configured,
  };
}
