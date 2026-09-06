#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)"
PLUGIN_DIR="$ROOT_DIR/plugins/g3ar4ub-wordpress-drafts"
MARKETPLACE_NAME="g3ar4ub-local"
PLUGIN_NAME="g3ar4ub-wordpress-drafts"

if [[ "$(uname -s)" != "Darwin" ]]; then
  echo "File này dành cho macOS. Trên Windows, dùng INSTALL-WINDOWS.ps1." >&2
  exit 1
fi
command -v codex >/dev/null 2>&1 || {
  echo "Chưa tìm thấy Codex CLI. Cài bằng lệnh:" >&2
  echo "curl -fsSL https://chatgpt.com/codex/install.sh | sh" >&2
  exit 1
}
command -v node >/dev/null 2>&1 || {
  echo "Chưa tìm thấy Node.js 18 trở lên. Hãy cài Node.js LTS rồi mở lại Terminal." >&2
  exit 1
}
command -v security >/dev/null 2>&1 || {
  echo "Không tìm thấy macOS Keychain CLI (/usr/bin/security)." >&2
  exit 1
}

node_major="$(node -p 'Number(process.versions.node.split(".")[0])')"
if [[ "$node_major" -lt 18 ]]; then
  echo "Cần Node.js 18 trở lên; phiên bản hiện tại: $(node --version)" >&2
  exit 1
fi

if ! codex login status >/dev/null 2>&1; then
  echo "Codex chưa đăng nhập. Chạy 'codex login', chọn Sign in with ChatGPT, rồi chạy lại installer." >&2
  exit 1
fi

chmod +x "$PLUGIN_DIR"/scripts/*.sh

echo "Đăng ký WordPress MCP..."
bash "$PLUGIN_DIR/scripts/register-mcp.sh"

echo "Thêm marketplace và plugin vào Codex..."
if ! codex plugin marketplace add "$ROOT_DIR"; then
  echo "Marketplace có thể đã được đăng ký; tiếp tục cài/cập nhật plugin."
fi
codex plugin add "$PLUGIN_NAME@$MARKETPLACE_NAME"

echo
echo "Các website hiện có:"
node "$PLUGIN_DIR/scripts/site-manager.mjs" list-ids
echo
read -r -p "Nhập SITE_ID cần cấu hình Keychain (hoặc Enter để bỏ qua): " site_id
if [[ -n "$site_id" ]]; then
  bash "$PLUGIN_DIR/scripts/configure-secrets.sh" "$site_id"
fi

echo
echo "Hoàn tất WordPress Connector v5.0 Dynamic Sites cho macOS."
echo "Credential cũ được giữ nguyên. Mở một phiên Codex mới và gọi wordpress_sites."
echo "Để cấu hình credential: bash \"$PLUGIN_DIR/scripts/configure-secrets.sh\" SITE_ID"
echo
read -r -p "Nhấn Enter để đóng cửa sổ này..." _
