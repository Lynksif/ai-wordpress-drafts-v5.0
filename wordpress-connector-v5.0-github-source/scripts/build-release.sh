#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)"
DIST_DIR="$ROOT_DIR/dist"
TEMP_DIR="$(mktemp -d)"
VERSION="5.0"
MARKETPLACE_ROOT="g3ar4ub-codex-marketplace"

cleanup() {
  rm -rf -- "$TEMP_DIR"
}
trap cleanup EXIT

command -v zip >/dev/null 2>&1 || {
  echo "Thiếu lệnh zip." >&2
  exit 1
}

mkdir -p "$DIST_DIR"
rm -f -- \
  "$DIST_DIR/ai-multisite-draft-connector-v${VERSION}-dynamic-sites.zip" \
  "$DIST_DIR/g3ar4ub-codex-marketplace-v${VERSION}-dynamic-sites-macos.zip" \
  "$DIST_DIR/g3ar4ub-codex-marketplace-v${VERSION}-dynamic-sites-linux.zip" \
  "$DIST_DIR/g3ar4ub-codex-marketplace-v${VERSION}-dynamic-sites-windows.zip" \
  "$DIST_DIR/wordpress-connector-v${VERSION}-github-source.zip" \
  "$DIST_DIR/SHA256SUMS.txt"

(
  cd "$ROOT_DIR/wordpress-plugin"
  zip -qr "$DIST_DIR/ai-multisite-draft-connector-v${VERSION}-dynamic-sites.zip" \
    g3ar4ub-ai-draft-connector
)

make_platform_package() {
  local platform="$1"
  local installer="$2"
  local guide="$3"
  local archive="$4"
  local stage="$TEMP_DIR/$platform/$MARKETPLACE_ROOT"

  mkdir -p "$stage"
  cp -a "$ROOT_DIR/.agents" "$stage/.agents"
  cp -a "$ROOT_DIR/plugins" "$stage/plugins"
  cp "$ROOT_DIR/$installer" "$stage/$installer"
  cp "$ROOT_DIR/$guide" "$stage/README-$platform.md"
  cp "$ROOT_DIR/SECURITY.md" "$ROOT_DIR/LICENSE" "$stage/"

  if [[ "$installer" == *.sh || "$installer" == *.command ]]; then
    chmod +x "$stage/$installer"
  fi
  find "$stage/plugins/g3ar4ub-wordpress-drafts/scripts" \
    -maxdepth 1 -type f -name '*.sh' -exec chmod +x {} +

  (
    cd "$TEMP_DIR/$platform"
    zip -qr "$DIST_DIR/$archive" "$MARKETPLACE_ROOT"
  )
}

make_platform_package \
  macos \
  INSTALL-MACOS.command \
  docs/INSTALL-MACOS.md \
  "g3ar4ub-codex-marketplace-v${VERSION}-dynamic-sites-macos.zip"

make_platform_package \
  linux \
  INSTALL-LINUX.sh \
  docs/INSTALL-LINUX.md \
  "g3ar4ub-codex-marketplace-v${VERSION}-dynamic-sites-linux.zip"

make_platform_package \
  windows \
  INSTALL-WINDOWS.ps1 \
  docs/INSTALL-WINDOWS.md \
  "g3ar4ub-codex-marketplace-v${VERSION}-dynamic-sites-windows.zip"

(
  cd "$ROOT_DIR"
  zip -qr "$DIST_DIR/wordpress-connector-v${VERSION}-github-source.zip" . \
    -x 'dist/*' '.git/*'
)

(
  cd "$DIST_DIR"
  sha256sum \
    "ai-multisite-draft-connector-v${VERSION}-dynamic-sites.zip" \
    "g3ar4ub-codex-marketplace-v${VERSION}-dynamic-sites-macos.zip" \
    "g3ar4ub-codex-marketplace-v${VERSION}-dynamic-sites-linux.zip" \
    "g3ar4ub-codex-marketplace-v${VERSION}-dynamic-sites-windows.zip" \
    "wordpress-connector-v${VERSION}-github-source.zip" \
    > SHA256SUMS.txt
)

echo "Đã tạo bộ phát hành tại: $DIST_DIR"
