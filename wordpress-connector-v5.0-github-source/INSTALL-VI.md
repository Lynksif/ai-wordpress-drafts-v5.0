# Cài WordPress Connector v5.0 Dynamic Sites

Bản v5 chạy trực tiếp với Codex CLI/Desktop trên Linux, macOS và Windows. Không dùng OpenAI API key hoặc Webapp public. Bảy profile hiện tại được giữ nguyên; website custom có thể thêm hoặc gỡ khỏi registry local bằng Codex.

## Yêu cầu

- Codex đã đăng nhập bằng ChatGPT.
- Node.js 18 trở lên.
- WordPress Connector v5 đã cài trên domain cần kết nối.
- User WordPress có role **AI Draft Writer** và Application Password riêng.
- ImageMagick hoặc FFmpeg trong `PATH` nếu cần upload cover.

## Linux

Trên Ubuntu/Pop!_OS:

```bash
sudo apt update
sudo apt install libsecret-tools imagemagick unzip
```

Giải nén gói vào thư mục cố định rồi chạy:

```bash
cd "$HOME/g3ar4ub-codex-marketplace"
chmod +x INSTALL-LINUX.sh
./INSTALL-LINUX.sh
```

Installer đăng ký MCP và cập nhật plugin Codex. Credential hiện có được giữ nguyên. Nếu ảnh cover không cần xử lý, ImageMagick là tùy chọn.

## macOS

```bash
cd "$HOME/g3ar4ub-codex-marketplace"
chmod +x INSTALL-MACOS.command
./INSTALL-MACOS.command
```

Credential được lưu trong macOS Keychain.

## Windows

Mở PowerShell tại thư mục đã giải nén:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\INSTALL-WINDOWS.ps1
```

Credential được mã hóa bằng DPAPI `CurrentUser`.

## Thêm website trực tiếp trong Codex

Trước tiên cài WordPress Connector v5 trên website mới. Trong **Settings → AI Draft Connector**:

1. Chọn **Custom profile**.
2. Nhập Profile ID và Brand.
3. Chọn SEO provider.
4. Bật **Enable draft intake** và lưu.
5. Tạo user role **AI Draft Writer** cùng Application Password riêng.

Sau đó yêu cầu Codex:

```text
Thêm website vào WordPress Connector:
ID: example-site
Domain: example.com
Brand: Example Brand
SEO: seopress
```

Codex gọi `wordpress_site_add` và trả về lệnh cấu hình credential phù hợp hệ điều hành. Chạy lệnh đó trong Terminal/PowerShell; nhập Application Password tại prompt ẩn, không dán vào chat. Khởi động lại Codex rồi kiểm tra:

```text
Gọi wordpress_health kiểm tra site example-site.
Chỉ kiểm tra kết nối, không tạo hoặc sửa bài.
```

## Gỡ website trực tiếp trong Codex

Yêu cầu phải nêu đúng ID và xác nhận domain:

```text
Gỡ profile example-site khỏi WordPress Connector.
Tôi xác nhận domain chính xác là example.com.
Chỉ gỡ cấu hình local, không xóa dữ liệu WordPress.
```

`wordpress_site_remove` chỉ thay đổi registry local. Profile custom được gỡ; profile built-in được tắt cục bộ và có thể khôi phục bằng `wordpress_site_add` với cùng ID/domain. Không website, bài viết, media, user hoặc cài đặt WordPress nào bị xóa. Credential được giữ lại cho đến khi bạn chủ động chạy lệnh cleanup mà tool trả về; để ngắt hoàn toàn, Revoke Application Password trong WordPress.

## Cấu hình credential thủ công

Linux/macOS:

```bash
bash plugins/g3ar4ub-wordpress-drafts/scripts/configure-secrets.sh SITE_ID
```

Windows:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\plugins\g3ar4ub-wordpress-drafts\scripts\Configure-Secrets-Windows.ps1 -Site SITE_ID
```

Không sao chép Application Password hoặc kho credential giữa các máy.
