#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)"
SITE_MANAGER="$ROOT_DIR/scripts/site-manager.mjs"
SERVICE="ai-wordpress-multisite-drafts"
OS_NAME="$(uname -s)"
SITES=()
while IFS= read -r site; do
  [[ -n "$site" ]] && SITES+=("$site")
done < <(node "$SITE_MANAGER" list-ids)

domain_for() {
  node "$SITE_MANAGER" domain "$1"
}

valid_site() {
  domain_for "$1" >/dev/null 2>&1
}

configure_site() {
  local site="$1"
  local domain username app_password
  domain="$(domain_for "$site")"

  echo
  echo "Cấu hình https://$domain"
  read -r -p "WordPress username của tài khoản AI Draft Writer: " username
  if [[ -z "$username" ]]; then
    echo "Username không được để trống." >&2
    return 1
  fi

  read -r -s -p "WordPress Application Password: " app_password
  echo
  if [[ -z "$app_password" ]]; then
    echo "Application Password không được để trống." >&2
    return 1
  fi

  if [[ "$OS_NAME" == "Darwin" ]]; then
    security add-generic-password -U -s "$SERVICE" -a "$site:wordpress-username" -w "$username" >/dev/null
    security add-generic-password -U -s "$SERVICE" -a "$site:wordpress-app-password" -w "$app_password" >/dev/null
  else
    printf '%s' "$username" | secret-tool store --label="AI WordPress $domain username" service "$SERVICE" site "$site" key wordpress-username
    printf '%s' "$app_password" | secret-tool store --label="AI WordPress $domain Application Password" service "$SERVICE" site "$site" key wordpress-app-password
  fi
  unset app_password
  if [[ "$OS_NAME" == "Darwin" ]]; then
    echo "Đã lưu secret cho $domain trong macOS Keychain."
  else
    echo "Đã lưu secret cho $domain trong Linux Secret Service."
  fi
}

case "$OS_NAME" in
  Darwin)
    command -v security >/dev/null 2>&1 || { echo "Không tìm thấy macOS Keychain CLI (/usr/bin/security)." >&2; exit 1; }
    ;;
  Linux)
    command -v secret-tool >/dev/null 2>&1 || { echo "Thiếu secret-tool. Trên Ubuntu/Pop!_OS: sudo apt install libsecret-tools" >&2; exit 1; }
    ;;
  *)
    echo "Script này hỗ trợ macOS và Linux. Trên Windows, dùng Configure-Secrets-Windows.ps1." >&2
    exit 1
    ;;
esac

selection="${1:-}"
if [[ -z "$selection" ]]; then
  echo "Các website trong registry:"
  for site in "${SITES[@]}"; do
    echo "  $site - $(domain_for "$site")"
  done
  read -r -p "Nhập SITE_ID cần cấu hình hoặc all: " selection
fi

if [[ "$selection" == "all" ]]; then
  for site in "${SITES[@]}"; do configure_site "$site"; done
elif valid_site "$selection"; then
  configure_site "$selection"
else
  echo "Website không có trong registry. Dùng wordpress_sites để kiểm tra hoặc wordpress_site_add để thêm." >&2
  exit 1
fi

echo
echo "Hoàn tất. Không có mật khẩu nào được ghi vào plugin, registry hoặc mã nguồn."

