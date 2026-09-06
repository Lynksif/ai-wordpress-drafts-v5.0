# WordPress Connector v5.0 Dynamic Sites

Phiên bản v5.0 mở rộng WordPress Connector thành workflow đa website chạy trực tiếp trong Codex trên macOS, Linux và Windows.

## Highlights

- Add, list, health-check, and remove WordPress sites directly from Codex.
- Create or update connector-managed drafts with stable `external_id` routing.
- Support SEOPress, Rank Math, and Yoast SEO metadata.
- Generate and upload 800 × 400 WebP covers optimized to 100 KB or less.
- Store credentials securely in macOS Keychain, Linux Secret Service, or Windows DPAPI CurrentUser.
- Validate the exact domain and website profile before every write operation.

## Safety

This release is draft-only by design:

- No Publish MCP tool.
- No remote Delete MCP tool.
- No Publish/Delete WordPress REST route.
- Published, scheduled, private, and trashed posts are protected.
- Credentials are not stored in the repository, release ZIPs, prompts, CLI arguments, or site registry.

## Downloads

### WordPress

Install this file through WordPress Admin:

```text
ai-multisite-draft-connector-v5.0-dynamic-sites.zip
```

### Codex local installer

Choose one package for your operating system:

- macOS: `g3ar4ub-codex-marketplace-v5.0-dynamic-sites-macos.zip`
- Linux: `g3ar4ub-codex-marketplace-v5.0-dynamic-sites-linux.zip`
- Windows: `g3ar4ub-codex-marketplace-v5.0-dynamic-sites-windows.zip`

The full repository source is available as:

```text
wordpress-connector-v5.0-github-source.zip
```

Verify downloaded files with `SHA256SUMS.txt` before installation.

## Requirements

- WordPress 6.5 or newer.
- Node.js 18 or newer.
- Codex signed in with ChatGPT.
- HTTPS WordPress website.
- Dedicated WordPress user with the AI Draft Writer role.
- A separate WordPress Application Password for each computer.

## After installation

Start a new Codex session and run:

```text
Call wordpress_sites, then use wordpress_health to check the target site.
Only test the connection; do not create or modify a post.
```

See the repository README and the operating-system guides for complete instructions.
