#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)"
PLUGIN_DIR="$ROOT_DIR/plugins/g3ar4ub-wordpress-drafts"

if [[ "$(uname -s)" != "Linux" ]]; then
  echo "File này dành cho Linux. Trên macOS hoặc Windows, dùng installer tương ứng." >&2
  exit 1
fi

command -v codex >/dev/null 2>&1 || {
  echo "Chưa tìm thấy Codex CLI. Hãy cài Codex và đăng nhập bằng ChatGPT trước." >&2
  exit 1
}
command -v node >/dev/null 2>&1 || {
  echo "Chưa tìm thấy Node.js 18 trở lên." >&2
  exit 1
}
command -v secret-tool >/dev/null 2>&1 || {
  echo "Thiếu secret-tool. Trên Ubuntu/Pop!_OS: sudo apt install libsecret-tools" >&2
  exit 1
}

node_major="$(node -p 'Number(process.versions.node.split(".")[0])')"
if [[ "$node_major" -lt 18 ]]; then
  echo "Node.js hiện tại quá cũ; cần Node.js 18 trở lên." >&2
  exit 1
fi

if ! codex login status >/dev/null 2>&1; then
  echo "Codex chưa đăng nhập. Chạy: codex login" >&2
  exit 1
fi

if ! command -v magick >/dev/null 2>&1 && ! command -v convert >/dev/null 2>&1 && ! command -v ffmpeg >/dev/null 2>&1; then
  echo "Lưu ý: chưa có ImageMagick/FFmpeg; tạo draft vẫn dùng được nhưng chưa thể tối ưu cover WebP."
fi

chmod +x "$PLUGIN_DIR"/scripts/*.sh

echo "Đăng ký WordPress MCP..."
bash "$PLUGIN_DIR/scripts/register-mcp.sh"

echo "Đăng ký hoặc dùng lại marketplace g3ar4ub-local..."
codex plugin marketplace add "$ROOT_DIR" >/dev/null 2>&1 || true

echo "Cài bản plugin hiện tại vào Codex..."
codex plugin add g3ar4ub-wordpress-drafts@g3ar4ub-local

echo
echo "Các website hiện có:"
node "$PLUGIN_DIR/scripts/site-manager.mjs" list-ids
echo
read -r -p "Nhập SITE_ID cần cấu hình Secret Service (hoặc Enter để bỏ qua): " site_id
if [[ -n "$site_id" ]]; then
  bash "$PLUGIN_DIR/scripts/configure-secrets.sh" "$site_id"
fi

echo
echo "Hoàn tất WordPress Connector v5.0 Dynamic Sites cho Linux."
echo "Credential cũ được giữ nguyên. Mở một phiên Codex mới và gọi wordpress_sites."
echo "Để cấu hình credential: bash \"$PLUGIN_DIR/scripts/configure-secrets.sh\" SITE_ID"
