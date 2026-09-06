# Cài WordPress Connector trên Windows

Hướng dẫn dùng Windows native và PowerShell. Credential được mã hóa bằng DPAPI `CurrentUser`, chỉ tài khoản Windows hiện tại trên máy đó giải mã được.

## 1. Chuẩn bị

Mở PowerShell và kiểm tra:

```powershell
node --version
codex --version
codex login status
```

Nếu chưa có Node.js LTS:

```powershell
winget install OpenJS.NodeJS.LTS
```

Nếu chưa có Codex CLI, sau khi cài Node.js và mở PowerShell mới:

```powershell
npm install -g @openai/codex
codex login
```

Chọn **Sign in with ChatGPT**.

## 2. Giải nén

Trong File Explorer, nhấn phải ZIP và chọn **Extract All**. Không chạy installer trực tiếp bên trong cửa sổ ZIP.

Hoặc dùng PowerShell:

```powershell
$Destination = Join-Path $HOME "Downloads\wp-connector-v5-windows"
New-Item -ItemType Directory -Force -Path $Destination | Out-Null

Expand-Archive `
  -LiteralPath (Join-Path $HOME "Downloads\g3ar4ub-codex-marketplace-v5.0-dynamic-sites-windows.zip") `
  -DestinationPath $Destination `
  -Force

Set-Location (Join-Path $Destination "g3ar4ub-codex-marketplace")
```

## 3. Chạy installer

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass `
  -File .\INSTALL-WINDOWS.ps1
```

Installer kiểm tra Node.js và Codex login; đăng ký MCP; cài plugin; sau đó cho phép nhập `SITE_ID`, username và Application Password.

## 4. Kiểm tra

Đóng Codex, mở phiên mới rồi nhập:

```text
Gọi wordpress_sites, sau đó dùng wordpress_health kiểm tra website cần dùng.
Chỉ kiểm tra kết nối, không tạo hoặc sửa bài.
```

## 5. Cấu hình thêm website sau này

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass `
  -File .\plugins\g3ar4ub-wordpress-drafts\scripts\Configure-Secrets-Windows.ps1 `
  -Site SITE_ID
```

Không sao chép file DPAPI sang máy hoặc tài khoản Windows khác.
