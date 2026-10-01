#!/usr/bin/env bash
#
# Collect changelog candidates since the last release tag.
#
# Usage:
#   scripts/changelog-collect.sh            # since the most recent tag
#   scripts/changelog-collect.sh v0.2.0     # since a specific tag (exclusive)
#   scripts/changelog-collect.sh --all      # every commit in the branch
#
# Groups Conventional Commit subjects by type and separates commits that a site
# owner will never care about (chore, ci, test, docs) so they can be dropped
# from the changelog. Output is a starting point, not the final wording.
set -euo pipefail

case "${1:-}" in
    --all) RANGE="HEAD" ;;
    "") RANGE="" ;;
    *..*) RANGE="${1}" ;;   # an explicit range was given
    *) RANGE="${1}..HEAD" ;;
esac

if [ -z "$RANGE" ]; then
    last_tag="$(git describe --tags --abbrev=0 2>/dev/null || true)"
    if [ -n "$last_tag" ]; then
        RANGE="$last_tag..HEAD"
        echo "# since $last_tag"
    else
        RANGE="HEAD"
        echo "# no tags found - listing all commits"
    fi
fi

commits="$(git log --no-merges --pretty=format:'%s' $RANGE 2>/dev/null || true)"
if [ -z "$commits" ]; then
    echo "# no commits in $RANGE"
    exit 0
fi

group() {
    local type="$1" pattern="$2"
    local matches
    matches="$(printf '%s\n' "$commits" \
        | grep -E "^${pattern}(\(.+\))?!?: " || true)"
    [ -n "$matches" ] || return 0
    echo
    echo "## $type"
    printf '%s\n' "$matches" | sed -E "s/^${pattern}(\(.+\))?!?: /- /"
}

group "feat"    "feat"
group "fix"     "fix"
group "perf"    "perf"
group "refactor" "refactor"

# Everything that is not a recognised type: read it, it may still be user-facing.
echo
echo "## unclassified (read before dropping)"
printf '%s\n' "$commits" \
    | grep -vE '^(feat|fix|perf|refactor|build|chore|ci|test|docs|style)(\(.+\))?!?: ' \
    | sed -E 's/^/- /' || true

echo
echo "## normally omitted (chore, ci, test, docs, build, style)"
printf '%s\n' "$commits" \
    | grep -E '^(chore|ci|test|docs|build|style)(\(.+\))?!?: ' \
    | sed -E 's/^[a-z]+(\(.+\))?!?: /- /' || true