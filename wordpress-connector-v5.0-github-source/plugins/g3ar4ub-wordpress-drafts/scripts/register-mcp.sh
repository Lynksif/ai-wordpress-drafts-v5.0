#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)"
MCP_NAME="g3ar4ub_wordpress"
OS_NAME="$(uname -s)"

if ! command -v codex >/dev/null 2>&1; then
  echo "Chưa tìm thấy Codex CLI. Hãy cài Codex trước khi đăng ký MCP." >&2
  exit 1
fi
if ! command -v node >/dev/null 2>&1; then
  echo "AI WordPress MCP cần Node.js 18 trở lên." >&2
  exit 1
fi

node_major="$(node -p 'Number(process.versions.node.split(".")[0])')"
if [[ "$node_major" -lt 18 ]]; then
  echo "AI WordPress MCP cần Node.js 18 trở lên." >&2
  exit 1
fi
mcp_env=()
if [[ "$OS_NAME" == "Linux" ]]; then
  if [[ -z "${DBUS_SESSION_BUS_ADDRESS:-}" ]]; then
    echo "Không tìm thấy DBUS_SESSION_BUS_ADDRESS. Hãy chạy script từ terminal trong phiên desktop đã đăng nhập." >&2
    exit 1
  fi
  mcp_env+=(--env "DBUS_SESSION_BUS_ADDRESS=$DBUS_SESSION_BUS_ADDRESS")
  if [[ -n "${XDG_RUNTIME_DIR:-}" ]]; then
    mcp_env+=(--env "XDG_RUNTIME_DIR=$XDG_RUNTIME_DIR")
  fi
elif [[ "$OS_NAME" != "Darwin" ]]; then
  echo "Trên Windows, dùng Register-Mcp-Windows.ps1." >&2
  exit 1
fi

if codex mcp get "$MCP_NAME" >/dev/null 2>&1; then
  codex mcp remove "$MCP_NAME" >/dev/null
fi

codex mcp add "$MCP_NAME" "${mcp_env[@]}" -- bash "$ROOT_DIR/scripts/start-mcp.sh"
echo "Đã đăng ký MCP $MCP_NAME bằng đường dẫn tuyệt đối: $ROOT_DIR"
if [[ "$OS_NAME" == "Darwin" ]]; then
  echo "MCP sẽ đọc credential từ macOS Keychain của tài khoản hiện tại."
else
  echo "Đã chuyển môi trường GNOME Keyring cần thiết vào tiến trình MCP."
fi
echo "MCP đã đăng ký 7 profile hệ thống và hỗ trợ thêm/xóa profile custom trực tiếp từ Codex."
echo "Hãy mở một phiên Codex mới để nạp MCP và skill."
