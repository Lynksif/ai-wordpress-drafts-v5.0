=== AI Multi-Site Draft Connector ===
Contributors: g3ar4ub
Tags: editorial, rest api, drafts, ai, seopress, rank math, yoast
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 5.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A secure, draft-only WordPress REST connector for protected built-in and custom editorial websites.

== Description ==

AI Multi-Site Draft Connector is installed independently on each WordPress site. Version 5 keeps the seven built-in profiles and adds a Custom profile bound to the site's real hostname. It creates and updates managed drafts, uploads optimized WebP covers, assigns existing terms, synchronizes SEO/social fields, and stores a complete JSON-LD graph.

Safety guarantees:

* Every write is forced to draft.
* No publish or delete endpoint exists.
* Published, scheduled, private, and trashed posts cannot be updated.
* A least-privilege AI Draft Writer role is installed.
* Article HTML is sanitized; inline scripts and styles are rejected.
* Media endpoint accepts only WebP covers at or below 100 KB.
* Site profile, content class, SEO data, and Schema remain domain-specific.

Supported SEO metadata adapters: SEOPress, Rank Math, and Yoast SEO.

== Installation ==

1. Upload and activate the plugin on each WordPress site.
2. Open Settings > AI Draft Connector and select the exact website profile.
3. Create a dedicated AI Draft Writer user and Application Password.
4. Store each site's credential separately in the Codex connector secret store.
5. Verify the authenticated health endpoint.

See SETUP-VI.md for detailed instructions.

== REST endpoints ==

* GET /wp-json/g3ar4ub-ai/v1/health
* POST /wp-json/g3ar4ub-ai/v1/media
* POST /wp-json/g3ar4ub-ai/v1/drafts
* GET /wp-json/g3ar4ub-ai/v1/drafts/{id}

There are no publish or delete routes.

== Changelog ==

= 5.0.0 =

* Added a safe Custom profile for any HTTPS WordPress domain.
* Added matching dynamic-site management in the Codex connector.
* Preserved all seven built-in profiles and draft-only protections.

= 4.2.0 =

* Added the chillspec profile for chillspec.com with ChillSpec branding.
* Added the g3ai-site-chillspec root class and CHILLSPEC.COM watermark.
* Set SEOPress as the preferred SEO provider for chillspec.com.
* Preserved the six existing profiles, credential keys, draft-only protections, and REST namespace.

= 4.0.0 =

* Added the 99ggcloud profile for 99ggcloud.com with 9GG CLOUD branding.
* Added a scoped cloud-gaming lab layout for latency tests, game-server guides, GPU cloud benchmarks, and provider comparisons.
* Added Rank Math as the preferred SEO provider for 99ggcloud.com.
* Preserved the five existing site profiles, credentials, draft-only protections, and REST namespace.

= 3.0.0 =

* Added the Rayesports profile for rayesports.com with RAYbet branding.
* Added a scoped esports match-center layout based on the supplied Rayesports article source.
* Added Rank Math as the preferred SEO provider for Rayesports.
* Preserved the four existing site profiles, credentials, draft-only protections, and REST namespace.

= 2.0.0 =

* Added fixed profiles for G3AR4UB, BootStup, BeportSquad, and SportTokVN.
* Added live site identity and profile verification to health responses.
* Added isolated site styling and watermarks.
* Added SEOPress, Rank Math, and Yoast metadata adapters.
* Added complete Schema graph/domain validation and richer draft verification.
* Restricted media intake to optimized WebP covers no larger than 100 KB.

= 1.1.0 =

* Added SEOPress metadata synchronization and connector Schema mode.

= 1.0.0 =

* Initial draft-only connector.
