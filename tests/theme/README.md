# Theme test harness (Top Solution Child)

Scripts used to test the child theme in a throw-away WordPress. Nothing here
is shipped with the theme and nothing touches a real site.

**Content in `seed.php` is TEST ONLY** (placeholder Arabic names, prices,
phone numbers). It is never a source of real business data.

## Setup (local, outside the repo)
1. Download WordPress (>= the version WooCommerce requires), the
   `hello-elementor` theme, and the `woocommerce`, `elementor` and
   `sqlite-database-integration` plugins from wordpress.org into one folder
   laid out as `wordpress/wp-content/{themes,plugins}`.
2. Copy `plugins/sqlite-database-integration/db.copy` to
   `wp-content/db.php` (fill in its two placeholders) and add a `wp-config.php`
   (WP_DEBUG + WP_DEBUG_LOG on, `WP_HOME`/`WP_SITEURL` = `http://127.0.0.1:8080`).
3. Download the Arabic language packs (core + WooCommerce) into
   `wp-content/languages/` so `is_rtl()` is true.
4. Symlink `wp-content/themes/top-solution-child` to the repo theme.
5. From the folder that contains `wordpress/`: `php install.php`, `php seed.php`,
   `php opt.php` (turns off WooCommerce "coming soon", sets 0 decimals).
6. `php -S 127.0.0.1:8080 -t wordpress router.php`
7. `npm i playwright axe-core` next to the JS files, then:
   `node e2e.js <out-dir>` (12 page types x 3 viewports: overflow, console
   errors, h1/main structure, axe), `node interact.js <out-dir>` (drawer, focus
   trap, submenus, search, scroll-hide, desktop dropdown, no-JS),
   `node wc.js <out-dir>` (single/variable product, cart, checkout).
8. `php elem.php on|off` toggles an Elementor-built homepage.

PHPCS: `phpcs --standard=wp-content/themes/top-solution-child/phpcs.xml.dist wp-content/themes/top-solution-child`
(needs `wp-coding-standards/wpcs` and `phpcompatibility/phpcompatibility-wp`).
