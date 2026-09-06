#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SITE_MANAGER="$ROOT_DIR/scripts/site-manager.mjs"
SERVICE="ai-wordpress-multisite-drafts"
LEGACY_SERVICE="g3ar4ub-wordpress-drafts"
OS_NAME="$(uname -s)"

if ! command -v node >/dev/null 2>&1; then
  echo "AI WordPress MCP: cần Node.js 18 trở lên." >&2
  exit 1
fi

node_major="$(node -p 'Number(process.versions.node.split(".")[0])')"
if [[ "$node_major" -lt 18 ]]; then
  echo "AI WordPress MCP: phiên bản Node.js hiện tại quá cũ; cần Node.js 18 trở lên." >&2
  exit 1
fi

lookup_secret() {
  if [[ "$OS_NAME" == "Darwin" ]]; then
    security find-generic-password -s "$SERVICE" -a "$1:$2" -w 2>/dev/null || true
  else
    secret-tool lookup service "$SERVICE" site "$1" key "$2" 2>/dev/null || true
  fi
}

case "$OS_NAME" in
  Darwin)
    command -v security >/dev/null 2>&1 || { echo "AI WordPress MCP: không tìm thấy macOS Keychain CLI." >&2; exit 1; }
    ;;
  Linux)
    command -v secret-tool >/dev/null 2>&1 || { echo "AI WordPress MCP: thiếu secret-tool." >&2; exit 1; }
    ;;
  *)
    echo "AI WordPress MCP: dùng Start-Mcp-Windows.ps1 trên Windows." >&2
    exit 1
    ;;
esac

while IFS= read -r site; do
  [[ -z "$site" ]] && continue
  username="$(lookup_secret "$site" wordpress-username)"
  app_password="$(lookup_secret "$site" wordpress-app-password)"

  if [[ "$OS_NAME" == "Linux" && "$site" == "g3ar4ub" && ( -z "$username" || -z "$app_password" ) ]]; then
    username="${username:-$(secret-tool lookup service "$LEGACY_SERVICE" key wordpress-username 2>/dev/null || true)}"
    app_password="${app_password:-$(secret-tool lookup service "$LEGACY_SERVICE" key wordpress-app-password 2>/dev/null || true)}"
  fi

  prefix="AIWP_$(printf '%s' "$site" | tr '[:lower:]-' '[:upper:]_')"
  export "${prefix}_USERNAME=$username"
  export "${prefix}_APP_PASSWORD=$app_password"
  unset app_password
done < <(node "$SITE_MANAGER" list-ids)

exec node "$ROOT_DIR/server/index.mjs"

