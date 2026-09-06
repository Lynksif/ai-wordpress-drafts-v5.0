# Security Policy

## Nguyên tắc

- Connector chỉ tạo hoặc cập nhật draft/pending do chính Connector quản lý.
- Không có Publish hoặc remote Delete tool/route.
- Mọi URL ghi dữ liệu được lấy từ registry đã xác thực exact-domain.
- Credential không nằm trong source, ZIP, registry website hoặc prompt.
- macOS dùng Keychain, Linux dùng Secret Service, Windows dùng DPAPI CurrentUser.

## Báo cáo lỗ hổng

Không đăng credential hoặc thông tin website riêng tư trong GitHub Issue công khai. Khi báo lỗi, chỉ cung cấp phiên bản, hệ điều hành, bước tái hiện và log đã xóa secret.

## Thu hồi quyền

Khi mất máy hoặc ngừng sử dụng Connector, thu hồi Application Password trong hồ sơ WordPress của user tương ứng.
