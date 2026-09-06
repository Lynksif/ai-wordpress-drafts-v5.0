# Cài WordPress Connector trên Linux

Hướng dẫn ưu tiên Ubuntu và Pop!_OS. Credential được lưu trong Secret Service/GNOME Keyring của phiên desktop hiện tại.

## 1. Cài phụ thuộc

Ubuntu/Pop!_OS/Debian:

```bash
sudo apt update
sudo apt install libsecret-tools imagemagick unzip bubblewrap
```

Kiểm tra:

```bash
node --version
secret-tool --version
codex --version
codex login status
```

Cần Node.js 18 trở lên. Nếu chưa có Codex CLI:

```bash
curl -fsSL https://chatgpt.com/codex/install.sh | sh
exec zsh -l
codex login
```

Nếu đang dùng Bash, thay `exec zsh -l` bằng:

```bash
exec bash -l
```

## 2. Giải nén

```bash
mkdir -p "$HOME/Downloads/wp-connector-v5-linux"

unzip -o \
  "$HOME/Downloads/g3ar4ub-codex-marketplace-v5.0-dynamic-sites-linux.zip" \
  -d "$HOME/Downloads/wp-connector-v5-linux"

cd "$HOME/Downloads/wp-connector-v5-linux/g3ar4ub-codex-marketplace"
```

## 3. Chạy installer

Phải chạy từ Terminal trong phiên desktop đã đăng nhập để MCP nhận `DBUS_SESSION_BUS_ADDRESS` và Keyring.

```bash
chmod +x INSTALL-LINUX.sh
./INSTALL-LINUX.sh
```

Nhập `SITE_ID`, WordPress username và Application Password khi được hỏi. Password được nhập ẩn.

## 4. Kiểm tra

Đóng Codex, mở phiên mới rồi nhập:

```text
Gọi wordpress_sites, sau đó dùng wordpress_health kiểm tra website cần dùng.
Chỉ kiểm tra kết nối, không tạo hoặc sửa bài.
```

## 5. Cấu hình thêm website sau này

```bash
bash plugins/g3ar4ub-wordpress-drafts/scripts/configure-secrets.sh SITE_ID
```

Nếu báo không tìm thấy Secret Service, đăng nhập lại phiên desktop và mở Terminal mới; không chạy installer từ TTY/SSH chưa có Keyring.
