#!/usr/bin/env bash
#
# Cut a release: bump the version everywhere it is written, add the changelog
# entry, build the production zip, then commit, tag, push and publish.
#
# This is the only command in the repository that writes a version number.
#
# Usage:
#   bin/release.sh [--minor|--major|--set X.Y.Z] [options]
#
#   --minor            1.0.4 -> 1.1.0   (new features)
#   --major            1.0.4 -> 2.0.0   (breaking changes, migrations)
#   --set X.Y.Z        Set an exact version instead of bumping.
#                      With no flag the version is a patch: 1.0.4 -> 1.0.5.
#   --min-free X.Y.Z   Raise the minimum free version the Pro add-on requires,
#                      rewriting its gate constant, admin notice, readmes and
#                      docs/pro.md. Only needed when Pro needs new free hooks.
#   --skip-npm           Reuse the existing bundles instead of rebuilding them.
#   --notes "..."        Changelog bullets for this release.
#   --notes-file FILE    Read the bullets from FILE.
#   --dry-run            Show every step, change nothing, push nothing.
#   --no-push            Stop after the local commit and tag.
#   --no-gh              Skip the GitHub release (zip is still built).
#   --sync-only          Only write the version into all version-carrying files.
#   --allow-dirty        Release with uncommitted changes in the tree.
#   --branch NAME        Release from NAME instead of the current branch.
#
# Changelog bullets default to CHANGELOG.d/<version>.md so release notes can be
# drafted and reviewed before the release runs.
set -euo pipefail

# shellcheck source=bin/lib.sh
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

PART="patch"
EXACT=""
ACTION=0
MIN_FREE=""
NOTES=""
NOTES_FILE=""
PUSH=1
GH_RELEASE=1
SYNC_ONLY=0
ALLOW_DIRTY=0
SKIP_NPM=0
BRANCH=""

while [ $# -gt 0 ]; do
    case "$1" in
        --minor) PART=minor; ACTION=1; shift ;;
        --major) PART=major; ACTION=1; shift ;;
        --set) EXACT="${2:-}"; [ -n "$EXACT" ] || die "--set needs a version"; ACTION=1; shift 2 ;;
        --min-free) MIN_FREE="${2:-}"; [ -n "$MIN_FREE" ] || die "--min-free needs a version"; shift 2 ;;
        --notes) NOTES="${2:-}"; shift 2 ;;
        --notes-file) NOTES_FILE="${2:-}"; shift 2 ;;
        --dry-run) DRY_RUN=1; shift ;;
        --no-push) PUSH=0; shift ;;
        --no-gh) GH_RELEASE=0; shift ;;
        --sync-only) SYNC_ONLY=1; shift ;;
        --allow-dirty) ALLOW_DIRTY=1; shift ;;
        --skip-npm) SKIP_NPM=1; shift ;;
        --branch) BRANCH="${2:-}"; shift 2 ;;
        -h|--help)
            awk 'NR>2 && /^#/ {sub(/^# ?/, ""); print; next} NR>2 {exit}' "$0"
            exit 0
            ;;
        -*) die "Unknown option: $1" ;;
        *)
            # Bare patch|minor|major|X.Y.Z keeps working for muscle memory.
            case "$1" in
                patch|minor|major) PART="$1" ;;
                *) EXACT="$1" ;;
            esac
            ACTION=1
            shift
            ;;
    esac
done

command -v git >/dev/null 2>&1 || die "git not found"
git -C "$PLUGIN_DIR" rev-parse --git-dir >/dev/null 2>&1 || die "Not a git repository: $PLUGIN_DIR"

CURRENT="$(version_get)"
[ -n "$CURRENT" ] || die "Could not read the current version from $MAIN_FILE"

if [ -n "$EXACT" ]; then
    printf '%s' "$EXACT" | grep -qE '^[0-9]+\.[0-9]+\.[0-9]+$' || die "Version must look like X.Y.Z, got '$EXACT'"
    VERSION="$EXACT"
elif [ "$SYNC_ONLY" -eq 1 ] && [ "$ACTION" -eq 0 ]; then
    # --sync-only with no version: just reconcile the version files.
    VERSION="$CURRENT"
else
    VERSION="$(version_bump "$CURRENT" "$PART")"
fi

if [ "$SYNC_ONLY" -eq 0 ]; then
    [ "$VERSION" != "$CURRENT" ] || die "Version $VERSION is already released. Bump higher."
fi

if [ -n "$EXACT" ]; then
    log "$SLUG $CURRENT -> $VERSION (explicit)"
else
    log "$SLUG $CURRENT -> $VERSION ($PART)"
fi
if [ "$DRY_RUN" -eq 1 ]; then
    log "dry-run: nothing will be written, committed or pushed"
fi

# --- Preflight ---------------------------------------------------------------

STATUS="$(git -C "$PLUGIN_DIR" status --porcelain)"
if [ -n "$STATUS" ] && [ "$ALLOW_DIRTY" -eq 0 ] && [ "$SYNC_ONLY" -eq 0 ]; then
    printf '%s\n' "$STATUS" >&2
    die "Working tree is dirty. Commit your work first, or pass --allow-dirty."
fi

BRANCH_NAME="${BRANCH:-$(git -C "$PLUGIN_DIR" rev-parse --abbrev-ref HEAD)}"
case "$BRANCH_NAME" in
    main|master) ;;
    *) [ -n "$BRANCH" ] || warn "Releasing from '$BRANCH_NAME' rather than main" ;;
esac

if [ "$SYNC_ONLY" -eq 0 ]; then
    if git -C "$PLUGIN_DIR" rev-parse "v$VERSION" >/dev/null 2>&1; then
        die "Tag v$VERSION already exists."
    fi
    if git -C "$PLUGIN_DIR" ls-remote --exit-code --tags origin "v$VERSION" >/dev/null 2>&1; then
        die "Tag v$VERSION already exists on origin."
    fi
fi

# --- Changelog notes ---------------------------------------------------------

if [ -z "$NOTES_FILE" ] && [ "$SYNC_ONLY" -eq 0 ]; then
    if [ -f "$PLUGIN_DIR/CHANGELOG.d/$VERSION.md" ]; then
        NOTES_FILE="$PLUGIN_DIR/CHANGELOG.d/$VERSION.md"
    elif [ -n "$NOTES" ]; then
        NOTES_FILE="$(mktemp)"
        printf '%s\n' "$NOTES" > "$NOTES_FILE"
    else
        die "No changelog notes. Create CHANGELOG.d/$VERSION.md or pass --notes \"...\". Bullets only, no heading."
    fi
fi

# --- Version + changelog -----------------------------------------------------

step "Syncing version into headers and readme"
version_sync "$VERSION"

# The Pro floor is a deliberate decision, not a side effect of a patch release:
# raising it forces every Pro site to update the free plugin too.
if [ -n "$MIN_FREE" ]; then
    printf '%s' "$MIN_FREE" | grep -qE '^[0-9]+\.[0-9]+\.[0-9]+$' || die "--min-free must look like X.Y.Z"
    step "Raising the Pro minimum free version to $MIN_FREE"
    version_sync_min_free "$MIN_FREE"
fi

if [ "$SYNC_ONLY" -eq 0 ]; then
    step "Adding readme.txt changelog entry"
    changelog_insert "$VERSION" "$NOTES_FILE"
fi

if [ "$SYNC_ONLY" -eq 1 ]; then
    version_verify
    log "Version files synced to $VERSION"
    exit 0
fi

# --- Build -------------------------------------------------------------------

log "Building the production zip"
BUILD_ARGS=("$VERSION")
if [ "$SKIP_NPM" -eq 1 ]; then
    BUILD_ARGS+=("--skip-npm")
fi
run bash "$PLUGIN_DIR/bin/build.sh" "${BUILD_ARGS[@]}"
ZIP="$PLUGIN_DIR/dist/$SLUG-$VERSION.zip"
[ "$DRY_RUN" -eq 1 ] || [ -f "$ZIP" ] || die "Build did not produce $ZIP"

# --- Commit, tag, publish ----------------------------------------------------

step "Committing release"
run git -C "$PLUGIN_DIR" add -A
if [ -z "$(git -C "$PLUGIN_DIR" status --porcelain)" ]; then
    warn "Nothing to commit — version was already at $VERSION"
else
    run git -C "$PLUGIN_DIR" commit -m "Release $SLUG $VERSION"
fi

step "Tagging v$VERSION"
run git -C "$PLUGIN_DIR" tag -a "v$VERSION" -m "StaySuite Companion $VERSION"

if [ "$PUSH" -eq 1 ]; then
    step "Pushing $BRANCH_NAME and v$VERSION"
    run git -C "$PLUGIN_DIR" push origin "$BRANCH_NAME"
    run git -C "$PLUGIN_DIR" push origin "v$VERSION"
else
    warn "Skipping push (--no-push)"
fi

if [ "$GH_RELEASE" -eq 1 ] && [ "$PUSH" -eq 1 ]; then
    if command -v gh >/dev/null 2>&1; then
        step "Creating the GitHub release with the zip attached"
        if [ -n "${GH_TOKEN:-}" ] || gh auth status >/dev/null 2>&1; then
            run gh release create "v$VERSION" "$ZIP" \
                --repo "$(git -C "$PLUGIN_DIR" remote get-url origin)" \
                --title "$SLUG $VERSION" \
                --notes-file "$NOTES_FILE"
        else
            warn "gh is not authenticated — create the release manually"
        fi
    else
        warn "gh not found — create the release manually"
    fi
fi

log "Released $SLUG $VERSION"
step "zip:        $ZIP"
step "tag:        v$VERSION"
step "wp.org:     commit dist/$SLUG-$VERSION.zip to trunk, then svn cp to tags/$VERSION"

# Pro lives in its own repository, so these edits cannot ride along with this
# commit. Surface them rather than letting them look forgotten.
pro_changes_note