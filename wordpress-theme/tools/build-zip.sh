#!/usr/bin/env bash
# Builds dist/tassili-immobilier.zip, the file to upload at Appearance > Themes > Add New > Upload Theme.
# Rebuilds the translation files first and refuses to package if a string has no translation.
set -euo pipefail
cd "$(dirname "$0")/.."

php tools/build-translations.php
for f in $(find tassili-immobilier -name '*.php'); do php -l "$f" >/dev/null; done

VERSION="$(sed -n 's/^Version: *//p' tassili-immobilier/style.css | head -1 | tr -d '\r')"
OUT="dist/tassili-immobilier-$VERSION.zip"
mkdir -p dist
rm -f dist/tassili-immobilier*.zip
zip -rq "$OUT" tassili-immobilier -x '*.DS_Store' -x '*/node_modules/*'
echo "Built $OUT ($(du -h "$OUT" | cut -f1))"
unzip -l "$OUT" | tail -1
