#!/usr/bin/env bash
# Build an installable WordPress plugin archive: dist/deco-store-core.zip
# The archive root is the folder "deco-store-core/" (required by WordPress).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PLUGINS="$ROOT/wp-content/plugins"
SLUG="deco-store-core"
OUT="$ROOT/dist/$SLUG.zip"

[ -f "$PLUGINS/$SLUG/$SLUG.php" ] || { echo "$SLUG.php not found in $PLUGINS/$SLUG" >&2; exit 1; }
grep -q "^ \* Plugin Name:" "$PLUGINS/$SLUG/$SLUG.php" || { echo "plugin header missing" >&2; exit 1; }

mkdir -p "$ROOT/dist"
rm -f "$OUT"

cd "$PLUGINS"
zip -r -X -q "$OUT" "$SLUG" \
	-x "$SLUG/phpcs.xml.dist" \
	-x "$SLUG/.*" -x "$SLUG/**/.*" \
	-x "$SLUG/node_modules/*" -x "$SLUG/vendor/*" \
	-x "*.DS_Store" -x "*Thumbs.db"

echo "Built $OUT"
unzip -l "$OUT" | tail -1
