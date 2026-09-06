# Multi-site article and Schema contract

## HTML contract

- Output exactly one outer wrapper: `<article class="g3ai-article PROFILE_CLASS">...</article>`.
- Replace `PROFILE_CLASS` with the selected site's exact class from `site-profiles.md`.
- Do not nest another `<article>` and do not use any `g3ar4ub-*` class.
- Do not include `<style>`, `<script>`, inline event handlers, or inline CSS. WordPress loads the scoped runtime CSS and JavaScript.
- Use semantic `h2`/`h3` headings with unique IDs and link the table of contents to those IDs.
- Use runtime components where useful: `g3ai-hero`, `g3ai-eyebrow`, `g3ai-lead`, `g3ai-toc`, `g3ai-section`, `g3ai-grid`, `g3ai-grid-2`, `g3ai-grid-4`, `g3ai-card`, `g3ai-callout`, `g3ai-warning`, `g3ai-success`, `g3ai-table-wrap`, `g3ai-steps`, `g3ai-checklist`, `g3ai-accordion`, `g3ai-accordion-panel`, `g3ai-tabs`, `g3ai-filter-bar`, and supported `data-g3ai-*` hooks. The Rayesports profile also supports `g3ai-tag`, `g3ai-odds`, `g3ai-confidence`, and `g3ai-picks` for the supplied match-center structure.
- Use interactive components only when they improve the content. An accordion button must use `data-g3ai-accordion`, `aria-expanded`, and an adjacent `.g3ai-accordion-panel`.

Minimum structure:

```html
<article class="g3ai-article PROFILE_CLASS">
  <header class="g3ai-hero">
    <span class="g3ai-eyebrow">SITE BRAND Guide</span>
    <h1>Article title</h1>
    <p class="g3ai-lead">Concise introduction.</p>
  </header>
  <nav class="g3ai-toc" aria-label="Table of contents">
    <ol>
      <li><a href="#section-id">Section title</a></li>
    </ol>
  </nav>
  <section class="g3ai-section" id="section-id">
    <h2>Section title</h2>
    <p>Short context paragraph.</p>
    <ul class="g3ai-checklist">
      <li>One complete idea.</li>
      <li>One complete idea.</li>
    </ul>
  </section>
</article>
```

## List and readability contract

- Every bullet or numbered point is a separate `<li>` on its own source line and visual row.
- Use `<ul>` for facts, takeaways, requirements, advantages, disadvantages, and checklists.
- Use `<ol>` for sequences, rankings, and step-by-step instructions.
- Never write `• item`, `- item`, `1. item`, or multiple list ideas joined by `<br>` inside a paragraph.
- Prefer paragraphs of one to three sentences. Split a paragraph when its ideas can be scanned independently.
- Use a semantic table inside `.g3ai-table-wrap` for repeated-field comparisons.
- FAQ questions and answers must be visually separate and exactly match FAQ Schema text.

## SEO and Schema contract

Provide `seo_title`, `meta_description`, `focus_keyword`, Facebook fields, and Twitter fields. Keep visible content, metadata, canonical URL, image URL, and Schema consistent. The WordPress connector writes these fields to the SEO provider selected or detected on that site.

Pass Schema separately in `schema`; never insert JSON-LD into `content_html`. Use one JSON-LD object with `"@context": "https://schema.org"` and a linked `@graph` containing:

- `Organization` with a same-domain stable `#organization` `@id`, verified site name, URL, and logo when health supplies one. Add `sameAs` only when verified.
- `WebSite` with `#website`, URL, verified name, publisher linked to `#organization`, and the site's WordPress language.
- `WebPage` with `#webpage`, canonical URL, name, description, `isPartOf`, `about`, breadcrumb link, dates, and language.
- `BreadcrumbList` with `#breadcrumb` and ordered absolute same-domain `ListItem` URLs.
- Exactly one primary node: `Article`, `BlogPosting`, or `NewsArticle`, selected by actual intent. Include `#article`, canonical URL, headline, description, image, ISO 8601 dates, author, publisher, `mainEntityOfPage`, `isPartOf`, and language.
- `FAQPage` only when identical questions and answers are visibly present.
- `HowTo` only for a genuine task whose complete steps are visible. Do not use `SoftwareApplication` merely because an article discusses software.

All core relations must use stable same-domain `@id` values. Do not copy an entity, logo, social URL, cover, canonical URL, or breadcrumb from another profile. Do not invent ratings, reviews, prices, authors, dates, or identities.

## Pre-write checklist

- Destination `site`, domain, root class, brand, watermark, internal links, SEO URLs, and Schema URLs all match.
- Exactly one `.g3ai-article` wrapper; no nested article.
- Runtime components and hooks are valid; no inline CSS/JavaScript.
- Lists use `ul`/`ol` plus one `li` per point; no simulated bullets.
- SEO/social fields are complete and aligned with search intent.
- Schema is valid JSON, uses a linked `@graph`, matches visible content, and includes required nodes.
- Stable site-specific `external_id` is retained for updates.
