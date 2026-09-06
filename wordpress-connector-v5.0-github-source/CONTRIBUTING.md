# Contributing

## Báo lỗi

Trước khi mở Issue:

1. Cập nhật Codex CLI.
2. Kiểm tra Node.js 18 trở lên.
3. Gọi `wordpress_health` cho đúng `SITE_ID`.
4. Xóa toàn bộ password, token, cookie và thông tin nhạy cảm khỏi log.

## Pull request

- Giữ nguyên nguyên tắc draft-only.
- Không thêm Publish hoặc remote Delete tool/route.
- Không hard-code domain credential hoặc Application Password.
- Kiểm tra manifest, Bash/Node syntax và build release trước khi gửi PR.
- Cập nhật CHANGELOG khi thay đổi hành vi người dùng.

Build kiểm tra:

```bash
bash scripts/build-release.sh
cd dist
sha256sum -c SHA256SUMS.txt
```
