const REQUIRED_GRAPH_TYPES = ["Organization", "WebSite", "WebPage", "BreadcrumbList"];
const ARTICLE_TYPES = new Set(["Article", "BlogPosting", "NewsArticle"]);

function nodeTypes(node) {
  const value = node?.["@type"];
  return Array.isArray(value) ? value : typeof value === "string" ? [value] : [];
}

function parseSchema(schema) {
  if (typeof schema === "string") {
    try {
      return JSON.parse(schema);
    } catch {
      throw new Error("schema phải là JSON hợp lệ.");
    }
  }
  return schema;
}

function validateHtml(html, profile) {
  const articleTags = html.match(/<article\b/gi) || [];
  if (articleTags.length !== 1) {
    throw new Error("content_html phải có đúng một thẻ article và không được lồng article.");
  }
  const opening = html.match(/<article\b[^>]*>/i)?.[0] || "";
  if (!/class\s*=\s*["'][^"']*\bg3ai-article\b/i.test(opening)) {
    throw new Error("Wrapper ngoài cùng phải có class g3ai-article.");
  }
  const escapedRoot = profile.rootClass.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
  if (!new RegExp(`class\\s*=\\s*["'][^"']*\\b${escapedRoot}\\b`, "i").test(opening)) {
    throw new Error(`Wrapper phải có class hồ sơ ${profile.rootClass}.`);
  }
  if (/<\/?(?:style|script)\b/i.test(html) || /\son[a-z]+\s*=/i.test(html) || /\sstyle\s*=/i.test(html)) {
    throw new Error("content_html không được chứa CSS, JavaScript, event handler hoặc inline style.");
  }
  if (/class\s*=\s*["'][^"']*\bg3ar4ub-/i.test(html)) {
    throw new Error("Không dùng class g3ar4ub-*; chỉ dùng runtime g3ai-*.");
  }
}

function validateSeo(seo) {
  if (!seo || typeof seo !== "object" || Array.isArray(seo)) {
    throw new Error("seo đầy đủ là bắt buộc.");
  }
  for (const field of ["seo_title", "meta_description", "focus_keyword"]) {
    if (typeof seo[field] !== "string" || !seo[field].trim()) {
      throw new Error(`seo.${field} là bắt buộc.`);
    }
  }
  if (!seo.social || typeof seo.social !== "object" || Array.isArray(seo.social)) {
    throw new Error("seo.social đầy đủ là bắt buộc.");
  }
  for (const field of ["facebook_title", "facebook_description", "twitter_title", "twitter_description"]) {
    if (typeof seo.social[field] !== "string" || !seo.social[field].trim()) {
      throw new Error(`seo.social.${field} là bắt buộc.`);
    }
  }
}

function validateSchema(schema, profile) {
  const document = parseSchema(schema);
  if (!document || typeof document !== "object" || Array.isArray(document)) {
    throw new Error("schema phải là một JSON-LD object.");
  }
  if (document["@context"] !== "https://schema.org" || !Array.isArray(document["@graph"])) {
    throw new Error("schema phải dùng @context https://schema.org và một @graph.");
  }
  const allTypes = document["@graph"].flatMap(nodeTypes);
  for (const required of REQUIRED_GRAPH_TYPES) {
    if (!allTypes.includes(required)) throw new Error(`Schema @graph thiếu ${required}.`);
  }
  if (!allTypes.some((type) => ARTICLE_TYPES.has(type))) {
    throw new Error("Schema @graph thiếu Article, BlogPosting hoặc NewsArticle phù hợp.");
  }
  const serialized = JSON.stringify(document);
  if (!serialized.includes(profile.url)) {
    throw new Error(`Schema không chứa URL chuẩn của ${profile.domain}.`);
  }
}

export function validateDraftInput(input, profile) {
  if (!input || typeof input !== "object" || Array.isArray(input)) {
    throw new Error("Dữ liệu draft phải là một object.");
  }
  for (const field of ["external_id", "title", "content_html"]) {
    if (typeof input[field] !== "string" || !input[field].trim()) {
      throw new Error(`${field} là bắt buộc.`);
    }
  }
  validateHtml(input.content_html, profile);
  validateSeo(input.seo);
  validateSchema(input.schema, profile);
}
