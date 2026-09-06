# Cài AI Multi-Site Draft Connector trên WordPress

Cài plugin v5 này trên từng website cần nối với Codex. Bảy profile hệ thống có sẵn:

| Website | Profile phải chọn | Root class |
| --- | --- | --- |
| `g3ar4ub.com` | `g3ar4ub` | `g3ai-site-g3ar4ub` |
| `bootstup.org` | `bootstup` | `g3ai-site-bootstup` |
| `beportsquad.com` | `beportsquad` | `g3ai-site-beportsquad` |
| `sporttokvn.com` | `sporttokvn` | `g3ai-site-sporttokvn` |
| `rayesports.com` | `rayesports` | `g3ai-site-rayesports` |
| `99ggcloud.com` | `99ggcloud` | `g3ai-site-99ggcloud` |
| `chillspec.com` | `chillspec` | `g3ai-site-chillspec` |

## 1. Cài plugin

Trên từng website:

1. Vào **Plugins → Add New Plugin → Upload Plugin**.
2. Chọn `ai-multisite-draft-connector.zip`.
3. Cài đặt và kích hoạt.
4. Vào **Settings → AI Draft Connector**.
5. Chọn đúng **Website profile** khớp domain hiện tại.
6. Giữ **Connector outputs Schema** nếu muốn connector xuất graph hoàn chỉnh.
7. Chọn **SEO provider: Auto detect** hoặc chỉ định SEOPress, Rank Math, Yoast SEO.
8. Bật **Enable draft intake** và lưu.

Health sẽ trả về URL và profile. Codex từ chối ghi nếu hai giá trị không khớp site được yêu cầu.

### Website custom

Trên một domain mới, chọn **Website profile: Custom profile**, rồi nhập:

- **Custom Profile ID**: 2–40 ký tự thường, số hoặc dấu gạch ngang; phải trùng chính xác ID thêm trong Codex.
- **Custom Brand**: tên thương hiệu hiển thị.
- **Custom Watermark**: tùy chọn; mặc định là domain viết hoa.
- **SEO provider**: phải trùng cấu hình thêm trong Codex.

Custom profile luôn tự khóa vào hostname thật của WordPress. Root class được tạo thành `g3ai-site-PROFILE_ID`; admin không thể đổi URL sang domain khác.

## 2. Tạo tài khoản giới hạn

Không dùng Administrator.

1. Vào **Users → Add New**.
2. Tạo username riêng, ví dụ `ai_draft_writer`.
3. Chọn role **AI Draft Writer**. Website nâng cấp từ v1 có thể vẫn hiển thị tên cũ **G3AR4UB AI Writer** nhưng quyền hạn không đổi.
4. Tạo user.

Role chỉ có quyền tạo/sửa bài của chính nó và tải ảnh; không có Publish, Delete, sửa bài đã xuất bản, quản lý category hay plugin.

## 3. Tạo Application Password riêng

1. Mở hồ sơ user vừa tạo.
2. Tạo Application Password, ví dụ `ChatGPT Work – BootStup` hoặc `ChatGPT Work – RAYbet`.
3. Lưu giá trị bằng script Codex plugin vào macOS Keychain, Windows DPAPI hoặc Linux Secret Service trên chính máy sẽ chạy Codex.

Không gửi mật khẩu qua chat, email hoặc ảnh chụp màn hình.

## 4. Endpoint

Namespace được giữ tương thích với bản cũ:

```text
GET  /wp-json/g3ar4ub-ai/v1/health
POST /wp-json/g3ar4ub-ai/v1/media
POST /wp-json/g3ar4ub-ai/v1/drafts
GET  /wp-json/g3ar4ub-ai/v1/drafts/{id}
```

Không có Publish hoặc Delete endpoint.

## 5. SEO và Schema

Plugin tự phát hiện và đồng bộ title, description, focus keyword, Facebook và X/Twitter metadata với:

- SEOPress
- Rank Math
- Yoast SEO

Khi connector quản lý Schema, graph của SEO plugin bị chặn riêng trên bài connector để tránh trùng entity. Payload bắt buộc có `Organization`, `WebSite`, `WebPage`, `BreadcrumbList` và một trong `Article`, `BlogPosting`, `NewsArticle` với URL cùng domain.

## 6. HTML và giao diện

Mỗi bài phải có đúng một wrapper:

```html
<article class="g3ai-article PROFILE_CLASS">
  ...
</article>
```

Runtime tự áp dụng giao diện theo profile:

- G3AR4UB: cyberpunk esports neon.
- BootStup: SOC terminal và security green.
- BeportSquad: cinematic mystery, crimson và violet.
- SportTokVN: sports newsroom, navy, blue, red và gold.
- RAYbet/Rayesports: esports match center, carbon–navy, vàng RAYbet, cyan dữ liệu trực tiếp và đỏ cảnh báo; hỗ trợ hero, bảng trận, tag game, odds, độ tin cậy, FAQ và cảnh báo rủi ro theo source Rayesports.
- 9GG CLOUD/99ggcloud: esports cloud lab, midnight navy, cyan dữ liệu, violet GPU, lime trạng thái tốt và cam cảnh báo latency; hỗ trợ benchmark, so sánh dịch vụ, vùng máy chủ, thông số test và quick verdict.
- ChillSpec/chillspec: technical review glass UI, graphite, ice blue, violet và mint; dành cho Buyer’s Guide, Desk Setups, Knowledge và Tech & Gear Reviews.

Trên `rayesports.com` và `99ggcloud.com`, chọn **SEO provider: Rank Math**. Trên `chillspec.com`, chọn **SEO provider: SEOPress**. Các website khác có thể tiếp tục dùng cấu hình SEO hiện tại.

Plugin loại bỏ `<style>`, `<script>` và inline handler. Accordion, filters và tabs dùng các hook `data-g3ai-*` đã kiểm tra.

## 7. Cover

Endpoint WordPress chỉ nhận:

- WebP.
- Tối đa 100 KB.

Codex plugin chịu trách nhiệm crop 800×400, xóa metadata và nén trước khi upload.

## 8. Trước khi Publish

- Domain, profile, branding và watermark đúng website.
- Internal links không trộn domain ngoài ý muốn.
- HTML dễ đọc, mỗi gạch đầu dòng là một `<li>` riêng.
- Cover WebP 800×400 và tối đa 100 KB.
- SEO/social metadata đầy đủ.
- Schema đúng domain và không có placeholder.
- Chỉ người biên tập mới Publish.
