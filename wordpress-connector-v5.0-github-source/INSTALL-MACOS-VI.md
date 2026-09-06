# WordPress Connector v5.0 Dynamic Sites — macOS

Gói này chạy trực tiếp trong Codex CLI trên macOS, đăng nhập bằng ChatGPT và không cần OpenAI API key. Credential WordPress được lưu trong Keychain của tài khoản macOS hiện tại.

## Yêu cầu

- macOS trên Apple Silicon hoặc Intel.
- Node.js 18 trở lên.
- Codex CLI đã đăng nhập bằng ChatGPT.
- WordPress Connector v5 trên website cần kết nối.
- Tài khoản WordPress có role **AI Draft Writer** và Application Password riêng.

## Cài đặt

Giải nén ZIP, mở Terminal và chạy:

```bash
cd "$HOME/Downloads/g3ar4ub-codex-marketplace"
chmod +x INSTALL-MACOS.command
./INSTALL-MACOS.command
```

Installer sẽ:

1. Kiểm tra Codex, Node.js và macOS Keychain.
2. Đăng ký MCP `g3ar4ub_wordpress` bằng đường dẫn tuyệt đối.
3. Cài hoặc cập nhật plugin `g3ar4ub-wordpress-drafts`.
4. Cho phép nhập một `SITE_ID` để lưu username và Application Password vào Keychain.

Sau khi hoàn tất, đóng Codex và mở phiên mới. Nhập:

```text
Gọi wordpress_sites, sau đó dùng wordpress_health kiểm tra website cần dùng.
Chỉ kiểm tra kết nối, không tạo hoặc sửa bài.
```

## Thêm website trong Codex

```text
Thêm website vào WordPress Connector:
ID: example-site
Domain: example.com
Brand: Example Brand
SEO: seopress
```

Sau khi Codex gọi `wordpress_site_add`, cấu hình credential bằng lệnh mà công cụ trả về. Không dán Application Password vào cuộc trò chuyện.

## Gỡ website trong Codex

```text
Gỡ profile example-site khỏi WordPress Connector.
Tôi xác nhận domain chính xác là example.com.
Chỉ gỡ cấu hình local, không xóa dữ liệu WordPress.
```

Việc gỡ profile local không xóa website, bài viết, media hoặc user WordPress. Plugin không có công cụ Publish hoặc Delete từ xa.
