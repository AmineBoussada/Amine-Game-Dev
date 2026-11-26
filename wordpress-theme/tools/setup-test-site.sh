#!/usr/bin/env bash
# Creates a throw-away WordPress (SQLite, no MySQL needed) for testing the theme.
#
#   tools/setup-test-site.sh <site-dir> [port] [--link-theme]
#
# Needs: php (with pdo_sqlite), curl, unzip. Downloads WordPress and the SQLite Database
# Integration plugin from wordpress.org. With --link-theme the theme folder is symlinked
# into the site; without it the site starts with no theme installed, ready for the
# "upload a zip" test (tests/install-test.cjs).
#
# Afterwards, start the server:
#   PHP_CLI_SERVER_WORKERS=6 php -S 127.0.0.1:<port> -t <site-dir> <site-dir>/router.php
set -euo pipefail

SITE="${1:?usage: setup-test-site.sh <site-dir> [port] [--link-theme]}"
PORT="${2:-8080}"
LINK="${3:-}"
WP_VERSION="${WP_VERSION:-6.7.2}"
HERE="$(cd "$(dirname "$0")/.." && pwd)"
CACHE="${TMPDIR:-/tmp}/tassili-wp-cache"

mkdir -p "$SITE" "$CACHE"
[ -f "$CACHE/wordpress-$WP_VERSION.zip" ] || curl -fsSL -o "$CACHE/wordpress-$WP_VERSION.zip" "https://downloads.wordpress.org/release/wordpress-$WP_VERSION.zip"
[ -f "$CACHE/sqlite.zip" ] || curl -fsSL -o "$CACHE/sqlite.zip" "https://downloads.wordpress.org/plugin/sqlite-database-integration.zip"

SITE="$(cd "$SITE" && pwd)"
TMP="$(mktemp -d)"
unzip -q "$CACHE/wordpress-$WP_VERSION.zip" -d "$TMP"
cp -R "$TMP/wordpress/." "$SITE/"
rm -rf "$TMP"
unzip -q -o "$CACHE/sqlite.zip" -d "$SITE/wp-content/plugins/"

P="wp-content/plugins/sqlite-database-integration"
sed -e "s#{SQLITE_IMPLEMENTATION_FOLDER_PATH}#$SITE/$P#g" -e "s#{SQLITE_PLUGIN}#sqlite-database-integration/load.php#g" "$SITE/$P/db.copy" > "$SITE/wp-content/db.php"
mkdir -p "$SITE/wp-content/database" "$SITE/wp-content/mu-plugins"

cat > "$SITE/wp-config.php" <<EOF
<?php
define( 'DB_NAME', 'wp' ); define( 'DB_USER', '' ); define( 'DB_PASSWORD', '' ); define( 'DB_HOST', '' );
define( 'DB_CHARSET', 'utf8' ); define( 'DB_COLLATE', '' );
define( 'AUTH_KEY', 'a' ); define( 'SECURE_AUTH_KEY', 'b' ); define( 'LOGGED_IN_KEY', 'c' ); define( 'NONCE_KEY', 'd' );
define( 'AUTH_SALT', 'e' ); define( 'SECURE_AUTH_SALT', 'f' ); define( 'LOGGED_IN_SALT', 'g' ); define( 'NONCE_SALT', 'h' );
\$table_prefix = 'wp_';
define( 'WP_HOME', 'http://127.0.0.1:$PORT' ); define( 'WP_SITEURL', 'http://127.0.0.1:$PORT' );
define( 'WP_DEBUG', true ); define( 'WP_DEBUG_LOG', true ); define( 'WP_DEBUG_DISPLAY', false );
define( 'FS_METHOD', 'direct' ); define( 'DISABLE_WP_CRON', true ); define( 'WP_AUTO_UPDATE_CORE', false );
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ . '/' ); }
require_once ABSPATH . 'wp-settings.php';
EOF

# Pretty permalinks on PHP's built-in server need a router.
cat > "$SITE/router.php" <<'EOF'
<?php
$path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
$file = __DIR__ . $path;
if ( '/' !== $path && is_file( $file ) ) { return false; }
if ( is_dir( $file ) && is_file( rtrim( $file, '/' ) . '/index.php' ) ) {
	$_SERVER['SCRIPT_NAME'] = rtrim( $path, '/' ) . '/index.php';
	chdir( $file );
	require rtrim( $file, '/' ) . '/index.php';
	return true;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';
EOF

# Records outgoing mail in wp-content/mail.log instead of sending it (tests read it).
cat > "$SITE/wp-content/mu-plugins/mail-catcher.php" <<'EOF'
<?php
add_filter( 'pre_wp_mail', function ( $null, $atts ) {
	file_put_contents( WP_CONTENT_DIR . '/mail.log', wp_json_encode( $atts ) . "\n", FILE_APPEND );
	return true;
}, 10, 2 );
EOF

cat > "$SITE/install.php" <<EOF
<?php
define( 'WP_INSTALLING', true );
\$_SERVER['HTTP_HOST'] = '127.0.0.1:$PORT';
require __DIR__ . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
if ( is_blog_installed() ) { echo "already installed\n"; exit; }
wp_install( 'Tassili Test', 'admin', 'admin@example.test', true, '', 'adminpass123' );
echo "installed (user admin / adminpass123)\n";
EOF
( cd "$SITE" && php install.php 2>/dev/null | tail -1 )

if [ "$LINK" = "--link-theme" ]; then
	ln -sfn "$HERE/tassili-immobilier" "$SITE/wp-content/themes/tassili-immobilier"
	echo "theme linked"
fi
echo "Ready: $SITE"
echo "Start it: PHP_CLI_SERVER_WORKERS=6 php -S 127.0.0.1:$PORT -t $SITE $SITE/router.php"
