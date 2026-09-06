<div align="center">

WordPress Connector v5.0

Dynamic Sites · Codex → WordPress Draft Workflow

Kết nối Codex trên macOS, Linux hoặc Windows với nhiều website WordPress, quản lý site trực tiếp trong Codex và tạo bài nháp an toàn.








Tải bản mới nhất · Cài đặt · Tài liệu · Bảo mật

</div>

✨ Tổng quan

WordPress Connector v5.0 Dynamic Sites gồm hai thành phần:

WordPress Connector cài trên từng website để tiếp nhận nội dung qua REST API được bảo vệ.

Codex Marketplace Plugin chạy cục bộ trên máy, quản lý website và gọi WordPress Connector bằng credential lưu trong hệ điều hành.

flowchart LR
    A[Codex] --> B[Local MCP]
    B --> C[WordPress REST]
    C --> D[Draft Editor]
    D --> E[Kiểm duyệt thủ công]

Connector không dùng OpenAI API key, không có máy chủ relay và không gửi credential lên repository.

🚀 Tính năng

Tính năng

Mô tả

Dynamic Sites

Thêm, liệt kê, kiểm tra và gỡ website trực tiếp trong Codex

Draft workflow

Tạo hoặc cập nhật đúng bài nháp bằng external_id ổn định

SEO adapters

Hỗ trợ SEOPress, Rank Math và Yoast

Cover WebP

Crop 800 × 400, xóa metadata và tối ưu tối đa 100 KB

Exact-domain lock

Kiểm tra đúng domain/profile trước mọi thao tác ghi

Native secret storage

macOS Keychain, Linux Secret Service, Windows DPAPI CurrentUser

Protected content

Bảo vệ bài published, scheduled, private và trashed

Draft-only

Không có Publish hoặc remote Delete tool/route

📦 File phát hành

Tải file trong trang Releases:

File

Dùng cho

ai-multisite-draft-connector-v5.0-dynamic-sites.zip

Upload trong WordPress Admin

g3ar4ub-codex-marketplace-v5.0-dynamic-sites-macos.zip

Mac Intel và Apple Silicon

g3ar4ub-codex-marketplace-v5.0-dynamic-sites-linux.zip

Ubuntu, Pop!_OS và Linux Desktop

g3ar4ub-codex-marketplace-v5.0-dynamic-sites-windows.zip

Windows native/PowerShell

wordpress-connector-v5.0-github-source.zip

Source repository hoàn chỉnh

SHA256SUMS.txt

Kiểm tra tính toàn vẹn file

🧩 Cài đặt

Bước 1 — Cài plugin WordPress

Vào WordPress Admin → Plugins → Add New Plugin → Upload Plugin, upload:

ai-multisite-draft-connector-v5.0-dynamic-sites.zip

Kích hoạt plugin, sau đó mở Settings → AI Draft Connector và bật Enable draft intake.

Bước 2 — Cài bộ Codex trên máy

Chọn đúng hệ điều hành:

Hướng dẫn macOS

Hướng dẫn Linux

Hướng dẫn Windows

Bước 3 — Kiểm tra kết nối

Đóng Codex, mở phiên mới và nhập:

Gọi wordpress_sites, sau đó dùng wordpress_health kiểm tra website cần dùng.
Chỉ kiểm tra kết nối, không tạo hoặc sửa bài.

➕ Thêm website trong Codex

Sau khi cài WordPress Connector trên domain mới, nhập:

Thêm website vào WordPress Connector:
ID: example-site
Domain: example.com
Brand: Example Brand
SEO: seopress

Codex gọi wordpress_site_add và trả về lệnh cấu hình credential phù hợp với hệ điều hành. Nhập Application Password trong Terminal hoặc PowerShell, không dán password vào cuộc trò chuyện.

➖ Gỡ website trong Codex

Gỡ profile example-site khỏi WordPress Connector.
Tôi xác nhận domain chính xác là example.com.
Chỉ gỡ cấu hình local, không xóa dữ liệu WordPress.

Thao tác này chỉ thay đổi registry trên máy. Nó không xóa website, bài viết, media, user hoặc cài đặt WordPress.

🛡️ An toàn mặc định

Chỉ tạo hoặc cập nhật bài draft/pending do Connector quản lý.

Không có công cụ Publish hoặc remote Delete.

Mọi thao tác ghi đều khóa theo exact domain/profile.

Mỗi máy nên dùng một Application Password riêng.

Credential không được ghi vào source, ZIP, prompt, CLI argument hoặc sites.json.

Có thể thu hồi quyền từng máy trong hồ sơ WordPress.

🧰 MCP tools

Tool

Chức năng

wordpress_sites

Liệt kê website và trạng thái credential

wordpress_site_add

Thêm/khôi phục profile local; không nhận secret

wordpress_site_remove

Gỡ profile local sau khi xác nhận domain

wordpress_health

Kiểm tra WordPress, SEO và khóa draft-only

wordpress_upload_cover

Tối ưu và tải ảnh cover WebP

wordpress_create_or_update_draft

Tạo/cập nhật bài nháp

wordpress_get_draft

Đọc lại bài nháp để kiểm tra

📚 Tài liệu

Cài WordPress Connector

Cài trên macOS

Cài trên Linux

Cài trên Windows

Đưa source và Release lên GitHub

Nội dung điền trên GitHub

Khắc phục lỗi thường gặp

Chính sách bảo mật

Lịch sử phiên bản

🔐 Kiểm tra checksum

Linux/macOS:

sha256sum -c SHA256SUMS.txt

Windows PowerShell:

Get-FileHash .\*.zip -Algorithm SHA256

🏗️ Build release

bash scripts/build-release.sh

Các file sẽ được tạo trong dist/. Workflow GitHub Actions tự build và tải asset lên Release khi push tag v5.0.0.

📄 License

Phát hành theo MIT License.

<div align="center">

Built for safe, review-first WordPress publishing.

</div>
