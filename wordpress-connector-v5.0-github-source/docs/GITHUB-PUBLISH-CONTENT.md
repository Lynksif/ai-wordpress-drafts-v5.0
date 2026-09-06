# Nội dung đầy đủ để đăng lên GitHub

Tài liệu này chứa nội dung có thể sao chép trực tiếp vào từng trường trên GitHub.

## 1. Thông tin repository

**Repository name**

```text
ai-wordpress-drafts-v5.0
```

**Owner đề xuất**

```text
LynkSif
```

**Description**

```text
Draft-only WordPress Connector for Codex with dynamic multi-site management, SEO adapters, WebP covers, and native credential storage on macOS, Linux, and Windows.
```

**Website**

```text
https://g3ar4ub.com/
```

**Topics**

```text
wordpress
codex
wordpress-plugin
mcp-server
seo
seopress
rank-math
yoast-seo
content-automation
draft-workflow
multi-site
webp
macos
linux
windows
```

Nên bật:

- Releases
- Issues
- Discussions nếu muốn nhận câu hỏi cài đặt

Không cần bật Projects hoặc Wiki ở phiên bản đầu tiên.

## 2. Tên và mô tả Release

**Choose a tag**

```text
v5.0.0
```

**Release title**

```text
WordPress Connector v5.0 Dynamic Sites
```

**Target**

```text
main
```

**Release description**

Sao chép toàn bộ nội dung từ file `RELEASE-NOTES-v5.0.0.md`.

## 3. Thứ tự upload Release assets

Upload theo thứ tự này để người dùng dễ chọn:

1. `ai-multisite-draft-connector-v5.0-dynamic-sites.zip`
2. `g3ar4ub-codex-marketplace-v5.0-dynamic-sites-macos.zip`
3. `g3ar4ub-codex-marketplace-v5.0-dynamic-sites-linux.zip`
4. `g3ar4ub-codex-marketplace-v5.0-dynamic-sites-windows.zip`
5. `wordpress-connector-v5.0-github-source.zip`
6. `SHA256SUMS.txt`

Không upload Application Password, file `sites.json`, credential export, `.env` hoặc ảnh chụp có secret.

## 4. Nội dung About ở cột phải

```text
Safe Codex → WordPress draft workflow with dynamic sites, SEO metadata and WebP covers.
```

Website:

```text
https://g3ar4ub.com/
```

Thêm toàn bộ Topics ở mục 1, sau đó bật dấu kiểm **Releases**.

## 5. Nội dung commit đầu tiên

```text
Release WordPress Connector v5.0 Dynamic Sites
```

## 6. Nội dung thông báo ngắn để chia sẻ Release

```text
WordPress Connector v5.0 Dynamic Sites is now available.

Connect Codex to multiple WordPress websites on macOS, Linux, or Windows; manage sites directly in Codex; generate SEO-ready drafts; and optimize WebP covers with native credential protection.

Draft-only by design. No Publish or remote Delete tools.
```

## 7. Mẫu GitHub Issue cài đặt

**Title**

```text
[Install] OS – short error description
```

**Body**

```text
Operating system:
Codex version:
Node.js version:
WordPress version:
Connector version:
SEO provider:

Expected result:

Actual result:

Sanitized error log:

I confirm that the log contains no password, token, cookie, or private credential.
```

## 8. Giao diện repository đề xuất

Để repository nhìn sạch và chuyên nghiệp:

1. Ghim Release `v5.0.0` mới nhất.
2. Đặt ảnh Social preview kích thước 1280 × 640 px.
3. Giữ mô tả repository trong một dòng.
4. Dùng Topics thay vì nhồi từ khóa vào tên repository.
5. Không commit thư mục `dist/`; để GitHub Actions tạo Release assets từ tag.
6. Giữ README, SECURITY, CHANGELOG và LICENSE tại root.
