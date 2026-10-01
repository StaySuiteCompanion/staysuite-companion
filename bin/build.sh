#!/usr/bin/env bash
#
# Build the production release zip: fresh bundles, production-only vendor,
# and a minimal dist/ archive that installs on a stock WordPress site.
#
# Usage:
#   bin/build.sh [version] [options]
#
#   version           Defaults to the Version header in the plugin file.
#   --lint            Run PHPCS before building (composer lint).
#   --skip-npm        Reuse the existing assets/build output.
#   --skip-composer   Alias for --vendor=copy (deprecated; use --vendor=copy).
#   --vendor=MODE     fresh (default) runs composer install --no-dev in the
#                     staging tree; copy reuses the working vendor.
#
# Environment:
#   SKIP_NPM=1, LINT=1
set -euo pipefail

# shellcheck source=bin/lib.sh
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

VERSION=""
LINT="${LINT:-0}"
SKIP_NPM="${SKIP_NPM:-0}"
VENDOR_MODE="fresh"

for arg in "$@"; do
    case "$arg" in
        --lint) LINT=1 ;;
        --skip-npm) SKIP_NPM=1 ;;
        --skip-composer) VENDOR_MODE="copy" ;;
        --vendor=*) VENDOR_MODE="${arg#--vendor=}" ;;
        -h|--help)
            sed -n '3,20p' "$0" | sed -E 's/^# \{0,1\}//'
            exit 0
            ;;
        -*) die "Unknown option: $arg" ;;
        *) VERSION="$arg" ;;
    esac
done

if [ -z "$VERSION" ]; then
    VERSION="$(version_get)"
fi
[ -n "$VERSION" ] || die "Could not determine version. Pass one explicitly: bin/build.sh 0.2.1"

log "Building $SLUG $VERSION"

# A zip whose header, constant and Stable tag disagree confuses updates in the
# field, so check before spending time on the build.
version_verify
[ "$VERSION" = "$(version_get)" ] || warn "Building $VERSION while headers say $(version_get)"

if [ "$LINT" -eq 1 ]; then
    if command -v composer >/dev/null 2>&1; then
        log "Linting PHP"
        composer lint
    else
        warn "composer not found — skipping lint"
    fi
fi

if [ "$SKIP_NPM" -eq 1 ]; then
    step "Skipping asset build (--skip-npm)"
elif [ ! -d "$PLUGIN_DIR/src" ]; then
    step "No src/ — nothing to bundle"
else
    log "Building assets (npm run build)"
    # A shell that exports NODE_ENV=production would make npm skip
    # devDependencies, which is where @wordpress/scripts lives.
    (cd "$PLUGIN_DIR" && NODE_ENV=development npm ci --silent 2>/dev/null || NODE_ENV=development npm install --silent)
    # ...and the reverse trap: NODE_ENV=development here would ship unminified
    # bundles plus source maps. Pin each mode to the step it belongs to.
    # `npm run build` compiles the Sass in src/scss first, then the JS.
    (cd "$PLUGIN_DIR" && NODE_ENV=production npm run build)
fi

# Guards the zip against a stylesheet that is missing or older than its source,
# which is how a --skip-npm build ships stale styles.
if [ -d "$PLUGIN_DIR/src/scss" ]; then
    css_verify
fi

stage_and_zip "$VERSION" "$VENDOR_MODE"
