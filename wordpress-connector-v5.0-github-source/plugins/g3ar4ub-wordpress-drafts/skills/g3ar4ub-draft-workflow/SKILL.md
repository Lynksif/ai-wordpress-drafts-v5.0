---
name: g3ar4ub-draft-workflow
description: Use when adding or removing a locally managed WordPress site, or when creating, updating, uploading a cover for, or verifying a connector-managed WordPress draft. Supports protected built-in profiles and custom domains. Never publish or delete remote content.
---

# Managed WordPress draft workflow

## Manage site profiles

1. Call `wordpress_sites` before profile management. It lists active profiles and any locally disabled built-in IDs.
2. Add a custom website only when the user explicitly supplies a stable profile ID, exact domain, brand, and SEO provider. Watermark, style, and editorial scope may be derived from those explicit values. The root class is always `g3ai-site-ID`.
3. Call `wordpress_site_add` without any username, password, token, or Application Password. This tool changes only the local connector registry.
4. Give the user the returned operating-system-specific credential command. The user must run it locally and enter the WordPress Application Password in the hidden Terminal/PowerShell prompt. Never ask them to paste it into chat.
5. After credentials are configured, tell the user to restart Codex, then call `wordpress_health`. Stop if the WordPress Connector v5 custom profile, domain, HTTPS, SEO provider, or draft-only guarantees do not match.
6. Remove a custom profile only after the user explicitly confirms the exact stored domain. Call `wordpress_site_remove` with both `site` and `confirm_domain`.
7. Explain that removal affects only the local routing registry. It never deletes the WordPress installation, posts, media, users, settings, or other remote data. A removed built-in profile is disabled locally and can be restored with `wordpress_site_add` using its original ID/domain; a custom profile is removed from the registry.
8. Offer the returned credential-cleanup command separately. Credential cleanup is not automatic; complete disconnection also requires the user to revoke the matching Application Password in WordPress.

## Create or update a draft

1. Resolve exactly one destination from the user's request. Call `wordpress_sites` and use [the managed site profiles](references/site-profiles.md). Never select a destination from the keyword alone. If the destination is not explicit, ask before any write or upload.
2. Call `wordpress_health` with the selected `site` before the first operation in a session. Stop if credentials are missing, the returned domain/profile differs, HTTPS is false, or draft-only and published-post protection are incomplete.
3. Read [the required article and Schema contract](references/article-schema-contract.md). For `rayesports`, also read [the RAYbet source layout contract](references/rayesports-source-layout.md). For `99ggcloud`, also read [the 9GG CLOUD source layout contract](references/99ggcloud-source-layout.md). Use the live profile's root class, brand, visual treatment, editorial scope, same-domain links, and watermark.
4. Prepare the complete article, semantic component HTML, SEO/social fields, and linked JSON-LD graph before writing.
5. Make the article easy to scan:
   - Put every list item in its own `<li>` inside `<ul>` or `<ol>`; never simulate a list with hyphens, bullets, `<br>`, or one long paragraph.
   - Use `<ol class="g3ai-steps">` for ordered instructions and `<ul class="g3ai-checklist">` for checks or takeaways.
   - Keep headings, cards, table rows, FAQ questions, and answers in separate semantic elements.
   - For `rayesports`, use its match-center runtime components and a visible responsible-risk notice.
   - For `99ggcloud`, distinguish independent measurements, provider claims, and unverified data.
6. Validate before writing: exactly one outer `<article class="g3ai-article PROFILE_CLASS">`, no nested `<article>`, no `g3ar4ub-*` classes, no inline CSS/scripts/handlers, complete SEO/social fields, and a linked Schema `@graph` for the exact selected domain.
7. For a cover, use the selected profile's branding and domain watermark. Call `wordpress_upload_cover` with the same `site`. Confirm WebP, 800×400, and at most 102400 bytes.
8. Call `wordpress_create_or_update_draft` with the same explicit `site` and a stable site-specific `external_id`.
9. Call `wordpress_get_draft` with the same `site` and returned post ID. Verify status, profile, title, HTML, SEO/social data, Schema, cover, and URLs.
10. Report the destination domain, action, edit URL, preview URL, and cover byte size. Never claim the draft is published; a human editor reviews and publishes it.

CSS and JavaScript are supplied by WordPress Connector v5. Generate supported `g3ai-*` components and `data-g3ai-*` hooks only; never place CSS or JavaScript source in `content_html`.
