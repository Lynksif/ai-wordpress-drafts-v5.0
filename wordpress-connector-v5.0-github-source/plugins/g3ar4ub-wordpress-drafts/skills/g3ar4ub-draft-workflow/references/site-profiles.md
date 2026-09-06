# Managed website profiles

`wordpress_sites` is the source of truth for the active registry. It returns built-in profiles, custom profiles, and IDs of built-in profiles disabled only on the current computer.

## Protected built-in profiles

| Site parameter | Domain | Required root class | Cover watermark | Preferred SEO |
| --- | --- | --- | --- | --- |
| `g3ar4ub` | `g3ar4ub.com` | `g3ai-site-g3ar4ub` | `G3AR4UB.COM` | Auto |
| `bootstup` | `bootstup.org` | `g3ai-site-bootstup` | `BOOTSTUP.ORG` | Auto |
| `beportsquad` | `beportsquad.com` | `g3ai-site-beportsquad` | `BEPORTSQUAD.COM` | Auto |
| `sporttokvn` | `sporttokvn.com` | `g3ai-site-sporttokvn` | `SPORTTOKVN.COM` | Auto |
| `rayesports` | `rayesports.com` | `g3ai-site-rayesports` | `RAYESPORTS.COM` | Rank Math |
| `99ggcloud` | `99ggcloud.com` | `g3ai-site-99ggcloud` | `99GGCLOUD.COM` | Rank Math |
| `chillspec` | `chillspec.com` | `g3ai-site-chillspec` | `CHILLSPEC.COM` | SEOPress |

Built-in routing remains fixed to the listed HTTPS domain. Removing one only disables it in the local active registry; adding it again with the same ID/domain restores the canonical profile.

## Custom profiles

A custom profile requires:

- A unique 2–40 character lowercase ID using letters, numbers, and hyphens.
- A hostname only, without protocol, port, path, query, or fragment.
- Brand and SEO provider: `auto`, `seopress`, `rank_math`, `yoast`, or `none`.
- A matching WordPress Connector v5 Custom profile on the real WordPress domain.
- A root class fixed to `g3ai-site-ID` on both Codex and WordPress.

The Codex registry is local to the current operating-system user. Adding a profile does not install WordPress Connector or create credentials. Removing it does not contact WordPress.

## Routing safeguards

- Pass the same `site` value to health, cover upload, draft write, and draft read.
- Never accept a destination URL at write time. Every operation resolves the URL from the managed registry.
- Never reuse a cover URL, canonical URL, internal link, Organization `@id`, WebSite `@id`, or Breadcrumb URL from another profile.
- Keep `external_id` site-specific.
- Same-domain internal links are the default.
- The health response is the source of truth for the WordPress site name, language, logo, current profile, domain, and detected SEO provider.
- Stop on any domain or profile mismatch.
