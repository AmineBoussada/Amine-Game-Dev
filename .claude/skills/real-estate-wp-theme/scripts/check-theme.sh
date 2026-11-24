#!/usr/bin/env bash
# Static checks for a generated theme. Usage: check-theme.sh wordpress/<slug>
set -u
DIR="${1:?Usage: check-theme.sh wordpress/<slug>}"
DIR="${DIR%/}"
SLUG="$(basename "$DIR")"
FAIL=0
ok()  { echo "  ✓ $*"; }
bad() { echo "  ✗ $*"; FAIL=1; }

echo "Checking $DIR"

# 1. Required files
for f in style.css functions.php index.php header.php footer.php front-page.php archive-property.php single-property.php \
         inc/fields.php inc/settings.php assets/css/tokens.css assets/js/theme.js assets/js/admin.js; do
  [ -f "$DIR/$f" ] || bad "missing $f"
done
[ $FAIL -eq 0 ] && ok "required files present"

# 2. Theme header
NAME="$(grep -m1 '^Theme Name:' "$DIR/style.css" | cut -d: -f2- | xargs)"
[ -n "$NAME" ] && ok "Theme Name: $NAME" || bad "style.css has no Theme Name"

# 3. PHP syntax
if command -v php >/dev/null; then
  ERR=0
  while IFS= read -r f; do php -l "$f" >/dev/null 2>&1 || { bad "PHP syntax: $f"; ERR=1; }; done < <(find "$DIR" -name '*.php')
  [ $ERR -eq 0 ] && ok "PHP syntax ($(find "$DIR" -name '*.php' | wc -l) files)"
else
  echo "  ! php not installed: syntax check skipped"
fi

# 4. Leftover source branding (novalidate is an HTML attribute, not branding)
LEFT="$(grep -rnoiP '(?<![a-z])nova(?!lidate)' "$DIR" --include='*.php' --include='*.css' --include='*.js' --include='*.json' | head -10)"
[ -z "$LEFT" ] && ok "no leftover \"nova\" branding" || { bad "leftover \"nova\" branding:"; echo "$LEFT" | sed 's/^/      /'; }

# 5. Zip
ZIP="$(dirname "$DIR")/$SLUG.zip"
if [ -f "$ZIP" ]; then
  if unzip -tq "$ZIP" >/dev/null 2>&1; then
    TOP="$(unzip -Z1 "$ZIP" | cut -d/ -f1 | sort -u | tr '\n' ' ' | xargs)"
    [ "$TOP" = "$SLUG" ] && ok "zip valid, single root folder \"$SLUG/\"" || bad "zip root should be \"$SLUG/\" (found: $TOP)"
    unzip -Z1 "$ZIP" | grep -q "^$SLUG/style.css$" && ok "zip contains $SLUG/style.css" || bad "zip lacks $SLUG/style.css"
  else
    bad "zip is corrupted: $ZIP"
  fi
else
  echo "  ! no zip found at $ZIP"
fi

[ $FAIL -eq 0 ] && echo "All checks passed." || echo "Some checks failed."
exit $FAIL
