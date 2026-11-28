# Theme test harness (Déco Store Child)

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
4. Symlink `wp-content/themes/deco-store-child` to the repo theme.
5. From the folder that contains `wordpress/`: `php install.php`, `php seed.php`,
   `php opt.php` (turns off WooCommerce "coming soon", sets 0 decimals).
6. `php -S 127.0.0.1:8080 -t wordpress router.php`
7. `npm i playwright axe-core` next to the JS files, then:
   `node e2e.js <out-dir>` (12 page types x 3 viewports: overflow, console
   errors, h1/main structure, axe), `node interact.js <out-dir>` (drawer, focus
   trap, submenus, search, scroll-hide, desktop dropdown, no-JS),
   `node wc.js <out-dir>` (single/variable product, cart, checkout).
8. `php elem.php on|off` toggles an Elementor-built homepage.
9. `php seed2.php` adds fixtures: product gallery, out-of-stock product,
   customer `client` with an order, classic `[woocommerce_cart]` /
   `[woocommerce_checkout]` pages, a page on Elementor Free's "Full Width"
   template.
10. `node validate.js <out-dir> <label>`: the main gate. 22 screens x 2
    viewports (home, shop, categories, category, single product with gallery,
    variable product, about, contact, search, 404, blog, Elementor page,
    login, block + classic cart and checkout, account dashboard, orders,
    addresses, billing address, account details). Fails on: HTTP status,
    horizontal overflow, missing RTL, missing theme header/footer/main, JS
    errors, axe WCAG 2.1 AA + best-practice violations, any "elementor-pro"
    reference in the HTML. Two WooCommerce-core markup findings are ignored by
    rule + element (documented in the script).
11. `php elementor-toggle.php on|off`: run validate.js in both modes to prove
    the store works with Elementor Free active AND with Elementor deactivated.
12. `php kit.php`: runs the Elementor kit brand sync as admin (idempotency check).
13. `node install-zip.js <zip> <out-dir>`: installs `dist/deco-store-child.zip`
    through the real wp-admin upload screen and activates it.
14. `php themecheck.php`: runs the official Theme Check plugin (place
    `theme-check` in plugins) against the installed theme.
15. `php shotmode.php on|off` + `node screenshot.js <png>`: regenerate
    `screenshot.png` with neutral site name/tagline.

Elementor Pro is never installed in this environment; the suite asserts no
Pro reference appears in any rendered page.

PHPCS: `phpcs --standard=wp-content/themes/deco-store-child/phpcs.xml.dist wp-content/themes/deco-store-child`
(needs `wp-coding-standards/wpcs` and `phpcompatibility/phpcompatibility-wp`).
