# Hướng dẫn upload GitHub đẹp và chuyên nghiệp

## Kết quả sau khi hoàn tất

Repository đề xuất:

```text
https://github.com/LynkSif/ai-wordpress-drafts-v5.0
```

Trang chính hiển thị README, badge, tính năng và hướng dẫn. Trang Releases chứa đúng plugin WordPress, ba bộ cài hệ điều hành, source ZIP và checksum.

## Phần 1 — Tạo repository

1. Đăng nhập GitHub.
2. Nhấn dấu **+** góc trên bên phải → **New repository**.
3. Owner: chọn `LynkSif`.
4. Repository name: `ai-wordpress-drafts-v5.0`.
5. Description: sao chép từ [GITHUB-PUBLISH-CONTENT.md](GITHUB-PUBLISH-CONTENT.md).
6. Chọn **Public** hoặc **Private** theo phạm vi phát hành.
7. Không chọn tạo README, `.gitignore` hoặc License vì source đã có sẵn.
8. Nhấn **Create repository**.

## Phần 2 — Upload source bằng Git

Giải nén `wordpress-connector-v5.0-github-source.zip`. Mở Terminal trong thư mục vừa giải nén:

```bash
git init
git add .
git commit -m "Release WordPress Connector v5.0 Dynamic Sites"
git branch -M main
git remote add origin https://github.com/LynkSif/ai-wordpress-drafts-v5.0.git
git push -u origin main
```

Nếu repository thuộc tài khoản khác, thay `LynkSif` trong URL.

Ưu tiên upload bằng Git thay vì kéo thả trên trình duyệt để giữ đúng thư mục ẩn `.agents`, `.github` và quyền thực thi của file `.command`/`.sh`.

## Phần 3 — Làm đẹp trang repository

### About

Tại cột phải của repository, nhấn biểu tượng bánh răng cạnh **About**:

- Điền Description.
- Điền Website `https://g3ar4ub.com/`.
- Thêm Topics được chuẩn bị trong `GITHUB-PUBLISH-CONTENT.md`.
- Bật **Releases**.

### Social preview

Mở **Settings → General → Social preview → Edit** và upload ảnh 1280 × 640 px. Ảnh nên có:

- Tên `WordPress Connector v5.0`.
- Dòng phụ `Codex → WordPress Draft Workflow`.
- Ba nhãn `macOS`, `Linux`, `Windows`.
- Nhãn an toàn `Draft-only · No Publish/Delete`.
- Màu nền navy, điểm nhấn cyan/violet để đồng bộ phong cách G3AR4UB.

### File root nên hiển thị

```text
.agents/
.github/
docs/
plugins/
scripts/
wordpress-plugin/
CHANGELOG.md
CONTRIBUTING.md
LICENSE
README.md
RELEASE-NOTES-v5.0.0.md
SECURITY.md
```

Không commit thư mục `dist/`; GitHub Actions sẽ build các file phát hành từ source.

## Phần 4 — Tạo Release v5.0.0 tự động

Source đã có workflow `.github/workflows/release.yml`. Chạy:

```bash
git tag -a v5.0.0 -m "WordPress Connector v5.0 Dynamic Sites"
git push origin v5.0.0
```

Sau khi push tag:

1. Mở tab **Actions**.
2. Chọn **Build release assets**.
3. Chờ dấu kiểm xanh.
4. Mở **Releases** để kiểm tra Release `v5.0.0`.

Workflow sẽ tạo và upload:

1. Plugin WordPress.
2. Bộ cài macOS.
3. Bộ cài Linux.
4. Bộ cài Windows.
5. Source ZIP.
6. SHA-256 checksums.

## Phần 5 — Tạo Release thủ công

Nếu không dùng GitHub Actions:

1. Chạy `bash scripts/build-release.sh`.
2. Mở **Releases → Draft a new release**.
3. Chọn **Choose a tag → v5.0.0**.
4. Target: `main`.
5. Release title: `WordPress Connector v5.0 Dynamic Sites`.
6. Dán nội dung `RELEASE-NOTES-v5.0.0.md` vào phần mô tả.
7. Kéo sáu file từ `dist/` vào mục Attach binaries.
8. Không chọn **Set as a pre-release**.
9. Chọn **Set as the latest release**.
10. Nhấn **Publish release**.

## Phần 6 — Kiểm tra sau khi publish

- README hiển thị đủ badge và sơ đồ.
- Link **Tải bản mới nhất** mở đúng trang Release.
- Release có đủ năm ZIP và `SHA256SUMS.txt`.
- ZIP plugin WordPress không bị đổi tên hoặc giải nén.
- Ba ZIP hệ điều hành có tên rõ ràng.
- Không có `.env`, `sites.json`, log, password hoặc credential trong source.
- Tag hiển thị đúng `v5.0.0`.
- Release được đánh dấu **Latest**.

## Import marketplace từ GitHub

Nếu workspace hỗ trợ GitHub marketplace, nhập URL repository và để **Path** trống vì `.agents/plugins/marketplace.json` nằm tại root. Import marketplace không thay thế việc cấu hình credential cục bộ trên từng máy.
