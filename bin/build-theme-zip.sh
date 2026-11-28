#!/usr/bin/env bash
# Build an installable WordPress theme archive: dist/top-solution-child.zip
# The archive root is the folder "top-solution-child/" (required by WordPress).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
THEMES="$ROOT/wp-content/themes"
SLUG="top-solution-child"
OUT="$ROOT/dist/$SLUG.zip"

[ -f "$THEMES/$SLUG/style.css" ] || { echo "style.css not found in $THEMES/$SLUG" >&2; exit 1; }
grep -q "^Template: hello-elementor" "$THEMES/$SLUG/style.css" || { echo "style.css must declare Template: hello-elementor" >&2; exit 1; }

mkdir -p "$ROOT/dist"
rm -f "$OUT"

cd "$THEMES"
zip -r -X -q "$OUT" "$SLUG" \
	-x "$SLUG/phpcs.xml.dist" \
	-x "$SLUG/.*" -x "$SLUG/**/.*" \
	-x "$SLUG/node_modules/*" -x "$SLUG/vendor/*" \
	-x "*.DS_Store" -x "*Thumbs.db"

echo "Built $OUT"
unzip -l "$OUT" | tail -1
