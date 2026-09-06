# AI WordPress Multi-Site Drafts v5

Plugin kết nối Codex trên Linux, macOS hoặc Windows với WordPress Connector v5. Bảy site hệ thống được bảo vệ và có thể thêm/gỡ các profile custom trực tiếp từ Codex.

## Công cụ

- `wordpress_sites`: liệt kê profile built-in/custom và trạng thái credential.
- `wordpress_site_add`: thêm profile custom hoặc khôi phục profile built-in đã tắt, không nhận secret.
- `wordpress_site_remove`: gỡ profile active sau khi xác nhận đúng domain; không xóa dữ liệu remote.
- `wordpress_health`: kiểm tra domain, profile, HTTPS, SEO và khóa draft-only.
- `wordpress_upload_cover`: crop 800×400, đổi WebP, xóa metadata và nén tối đa 100 KB.
- `wordpress_create_or_update_draft`: tạo/cập nhật draft cùng HTML profile, SEO, social và Schema.
- `wordpress_get_draft`: đọc lại draft để kiểm tra.

Không có tool Publish hoặc remote Delete.

## Registry và credential

Registry custom nằm trong thư mục cấu hình của user đang đăng nhập:

- Linux/macOS: `${XDG_CONFIG_HOME:-~/.config}/g3ar4ub-wordpress-drafts/sites.json`
- Windows: `%LOCALAPPDATA%\G3AR4UB\WordPressDrafts\sites.json`

Registry không chứa credential. Username và Application Password được bảo vệ riêng:

- Linux: Secret Service/GNOME Keyring.
- macOS: Keychain.
- Windows: DPAPI `CurrentUser`.

Sau khi thêm profile bằng Codex, chạy lệnh credential do tool trả về rồi khởi động lại Codex.

## Bảo vệ định tuyến

- Mọi thao tác nội dung bắt buộc nhận `site` và URL chỉ được lấy từ registry.
- Health khóa exact domain/profile trước khi ghi.
- Built-in profile khi gỡ chỉ bị tắt trong registry local và có thể khôi phục.
- Xóa profile custom chỉ thay đổi registry local.
- Chỉ tạo/cập nhật bài connector quản lý ở trạng thái draft/pending.
- Published, scheduled, private và trashed posts được bảo vệ.
