# Khắc phục lỗi thường gặp

## `codex: command not found`

Cài hoặc cập nhật Codex, mở Terminal/PowerShell mới rồi kiểm tra `codex --version`.

## `node: command not found`

Cài Node.js LTS. Connector yêu cầu Node.js 18 trở lên.

## Không tìm thấy installer

Bạn đang đứng sai thư mục hoặc chưa giải nén ZIP. Chạy `pwd`/`Get-Location` và kiểm tra file installer trước khi thực thi.

## Linux không đọc được credential

Chạy installer từ Terminal trong phiên desktop đã đăng nhập. Kiểm tra `secret-tool`, `DBUS_SESSION_BUS_ADDRESS` và GNOME Keyring/Secret Service đang hoạt động.

## Windows chặn PowerShell script

Chỉ chạy file từ ZIP phát hành đã kiểm tra checksum:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\INSTALL-WINDOWS.ps1
```

## `401 Unauthorized`

Kiểm tra WordPress username và Application Password. Không dùng mật khẩu đăng nhập WordPress thông thường.

## `403 Forbidden`

Kiểm tra user có role **AI Draft Writer**, Connector đang bật draft intake và firewall/security plugin không chặn REST API.

## Sai domain hoặc profile

Gọi `wordpress_sites`, đối chiếu `SITE_ID`, sau đó gọi `wordpress_health`. Connector khóa exact-domain để tránh ghi nhầm website.

## WebSocket chuyển sang HTTPS

Đây là cảnh báo transport của Codex, không phải lỗi WordPress REST. Nếu tiến trình còn hiển thị `Working`, chờ Codex hoàn tất. Nếu phiên dừng, dùng `codex resume --last` và yêu cầu kiểm tra draft/external_id trước khi tiếp tục.
