# Cài WordPress Connector v5.0

Thực hiện riêng trên từng website muốn kết nối với Codex.

## 1. Sao lưu

Sao lưu database và thư mục plugin trước khi thay thế phiên bản đang chạy.

## 2. Upload plugin

1. Mở **WordPress Admin → Plugins → Add New Plugin → Upload Plugin**.
2. Chọn `ai-multisite-draft-connector-v5.0-dynamic-sites.zip`.
3. Chọn **Install Now**.
4. Nếu WordPress phát hiện phiên bản cũ, chọn **Replace current with uploaded**.
5. Kích hoạt **G3AR4UB AI Draft Connector**.

Không giải nén ZIP trước khi upload vào WordPress.

## 3. Cấu hình website

Mở **Settings → AI Draft Connector**:

- Chọn profile có sẵn hoặc **Custom profile**.
- Nhập Profile ID dạng chữ thường, số và dấu gạch ngang, ví dụ `chillspec`.
- Nhập Brand chính xác.
- Chọn SEO provider: SEOPress, Rank Math hoặc Yoast.
- Chọn **Connector outputs Schema** nếu muốn Connector ghi Schema.
- Bật **Enable draft intake**.
- Lưu cấu hình.

Ví dụ ChillSpec:

```text
Profile ID: chillspec
Domain: chillspec.com
Brand: ChillSpec
SEO: SEOPress
Mode: draft_only
```

## 4. Tạo tài khoản dành cho Codex

1. Tạo WordPress user riêng.
2. Gán role **AI Draft Writer**.
3. Mở hồ sơ user và tạo Application Password, ví dụ `Codex MacBook – ChillSpec`.
4. Sao chép password một lần và nhập vào installer trên máy tương ứng.

Không gửi Application Password qua chat và không đưa vào GitHub.

## 5. Kiểm tra

Sau khi cài gói Codex trên máy, mở phiên mới và nhập:

```text
Gọi wordpress_health kiểm tra site chillspec.
Chỉ kiểm tra kết nối, không tạo hoặc sửa bài.
```

Kết quả đúng phải xác nhận domain, SEO provider, `draft_only`, không có Publish/Delete và bảo vệ bài đã xuất bản.
