#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)"
SITE_MANAGER="$ROOT_DIR/scripts/site-manager.mjs"
SERVICE="ai-wordpress-multisite-drafts"
selection="${1:-}"
OS_NAME="$(uname -s)"
SITES=()
while IFS= read -r site; do
  [[ -n "$site" ]] && SITES+=("$site")
done < <(node "$SITE_MANAGER" list-ids)

case "$OS_NAME" in
  Darwin) command -v security >/dev/null 2>&1 || { echo "Thiếu macOS Keychain CLI." >&2; exit 1; } ;;
  Linux) command -v secret-tool >/dev/null 2>&1 || { echo "Thiếu secret-tool." >&2; exit 1; } ;;
  *) echo "Trên Windows, dùng Clear-Secrets-Windows.ps1." >&2; exit 1 ;;
esac

valid_site_id() {
  [[ "$1" =~ ^[a-z0-9][a-z0-9-]{1,39}$ ]]
}

clear_site() {
  local site="$1"
  if [[ "$OS_NAME" == "Darwin" ]]; then
    security delete-generic-password -s "$SERVICE" -a "$site:wordpress-username" >/dev/null 2>&1 || true
    security delete-generic-password -s "$SERVICE" -a "$site:wordpress-app-password" >/dev/null 2>&1 || true
  else
    secret-tool clear service "$SERVICE" site "$site" key wordpress-username || true
    secret-tool clear service "$SERVICE" site "$site" key wordpress-app-password || true
  fi
  echo "Đã xóa secret local: $site"
}

if [[ "$selection" == "all" ]]; then
  for site in "${SITES[@]}"; do clear_site "$site"; done
  if command -v codex >/dev/null 2>&1 && codex mcp get g3ar4ub_wordpress >/dev/null 2>&1; then
    codex mcp remove g3ar4ub_wordpress >/dev/null
  fi
  echo "Đã gỡ MCP cục bộ. Thu hồi Application Password trên WordPress nếu muốn ngắt hoàn toàn."
elif valid_site_id "$selection"; then
  clear_site "$selection"
  echo "Credential local đã được gỡ. Thu hồi Application Password tương ứng trong WordPress nếu không còn dùng."
else
  echo "Cách dùng: bash scripts/clear-secrets.sh <SITE_ID|all>" >&2
  exit 1
fi

