# Cài WordPress Connector trên macOS

Hỗ trợ Mac Intel và Apple Silicon. Credential được lưu trong macOS Keychain của tài khoản hiện tại.

## 1. Chuẩn bị

Kiểm tra Terminal:

```bash
node --version
codex --version
codex login status
```

Cần Node.js 18 trở lên. Nếu chưa có Codex CLI:

```bash
curl -fsSL https://chatgpt.com/codex/install.sh | sh
exec zsh -l
codex login
```

Khi đăng nhập, chọn **Sign in with ChatGPT**.

## 2. Giải nén

```bash
mkdir -p "$HOME/Downloads/wp-connector-v5-macos"

unzip -o \
  "$HOME/Downloads/g3ar4ub-codex-marketplace-v5.0-dynamic-sites-macos.zip" \
  -d "$HOME/Downloads/wp-connector-v5-macos"

cd "$HOME/Downloads/wp-connector-v5-macos/g3ar4ub-codex-marketplace"
```

## 3. Chạy installer

```bash
chmod +x INSTALL-MACOS.command
./INSTALL-MACOS.command
```

Installer kiểm tra Node.js, Codex login và Keychain; đăng ký MCP; cài plugin; sau đó cho phép nhập `SITE_ID` để cấu hình credential.

Ví dụ:

```text
SITE_ID: chillspec
```

Nhập WordPress username và Application Password tại prompt ẩn.

## 4. Kiểm tra

Đóng Codex, mở phiên mới rồi nhập:

```text
Gọi wordpress_sites, sau đó dùng wordpress_health kiểm tra site chillspec.
Chỉ kiểm tra kết nối, không tạo hoặc sửa bài.
```

## 5. Cấu hình thêm website sau này

```bash
bash plugins/g3ar4ub-wordpress-drafts/scripts/configure-secrets.sh SITE_ID
```

Credential cũ trong Keychain được giữ nguyên khi chạy lại installer.
