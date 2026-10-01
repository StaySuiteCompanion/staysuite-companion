#!/usr/bin/env bash
#
# Build a lightweight wp.org release zip (production files only).
#
# Usage: bin/export.sh [version]
#   version defaults to the Version header in staysuite-companion.php.
#
# Excludes: src, node_modules, docs, configs, dev scripts, dist itself.
set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SLUG="staysuite-companion"

VERSION="${1:-$(grep -m1 -E '^\s*\* Version:' "$PLUGIN_DIR/staysuite-companion.php" | grep -o -E '[0-9]+\.[0-9]+\.[0-9]+')}"
if [ -z "$VERSION" ]; then
    echo "Could not determine version." >&2
    exit 1
fi

# Production files only.
INCLUDE=(
    staysuite-companion.php
    readme.txt
    includes
    templates
    languages
    vendor
    assets/build
    assets/css
    assets/js
)

STAGE="$(mktemp -d)"
trap 'rm -rf "$STAGE"' EXIT
mkdir -p "$STAGE/$SLUG"
for item in "${INCLUDE[@]}"; do
    if [ -e "$PLUGIN_DIR/$item" ]; then
        cp -R "$PLUGIN_DIR/$item" "$STAGE/$SLUG/"
    fi
done
# Strip leftovers that may hide inside included dirs.
find "$STAGE/$SLUG" \( -name ".DS_Store" -o -name "*.map" \) -delete

OUT="$PLUGIN_DIR/dist/$SLUG-$VERSION.zip"
mkdir -p "$PLUGIN_DIR/dist"
(cd "$STAGE" && zip -qr "$OUT" "$SLUG")
echo "Built: $OUT"
unzip -l "$OUT" | tail -n 3
