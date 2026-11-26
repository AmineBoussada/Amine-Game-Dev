#!/usr/bin/env bash
# Builds dist/tassili-immobilier.zip, the file to upload at Appearance > Themes > Add New > Upload Theme.
# Rebuilds the translation files first and refuses to package if a string has no translation.
set -euo pipefail
cd "$(dirname "$0")/.."

php tools/build-translations.php
for f in $(find tassili-immobilier -name '*.php'); do php -l "$f" >/dev/null; done

mkdir -p dist
rm -f dist/tassili-immobilier.zip
zip -rq dist/tassili-immobilier.zip tassili-immobilier -x '*.DS_Store' -x '*/node_modules/*'
echo "Built dist/tassili-immobilier.zip ($(du -h dist/tassili-immobilier.zip | cut -f1))"
unzip -l dist/tassili-immobilier.zip | tail -1
