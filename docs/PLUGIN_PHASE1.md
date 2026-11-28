# Déco Store Core — Phase 1 (foundation)

Version 0.1.0 · `wp-content/plugins/deco-store-core/` · installable ZIP: `dist/deco-store-core.zip`
(build: `bin/build-plugin-zip.sh`). Requires WordPress 6.4+, PHP 7.4+, WooCommerce 8.0+.
**Elementor is not used at all; Elementor Pro is never required.**

## What it does
- **Requirements check** (PHP / WordPress / WooCommerce): if not met, an Arabic admin notice explains why and nothing else loads.
- **HPOS**: declares compatibility (`custom_order_tables`) before WooCommerce initialises. Orders will only ever be handled through WooCommerce order objects.
- **Capabilities** (9): `ds_manage_landing`, `ds_manage_offers`, `ds_manage_forms`, `ds_manage_delivery`, `ds_manage_inventory`, `ds_manage_suppliers`, `ds_view_costs`, `ds_view_reports`, `ds_manage_settings`.
  Administrator has all. Shop manager has landing, offers, forms, delivery, inventory and reports. **Costs, suppliers and settings are administrator-only** (open question Q10; change with the `ds_core_role_caps` filter).
- **Settings** (Déco Store > Settings), validated on the server: *Orders* tab: initial order status (`processing` default, or `on-hold`); *General* tab: diagnostics logging, delete data on uninstall.
- **Status dashboard** (Déco Store > Dashboard): 8 read-only checks (versions, HPOS, cash-on-delivery gateway, currency, theme, RTL/Arabic, permalinks, Elementor).
- **Migrations**: versioned, idempotent (`ds_core_db_version`). Later phases add steps (tables, post types).
- **Logger**: WooCommerce logs, source `deco-store`. Personal data and costs are redacted by key name (whole-word match).
- **Uninstall**: removes plugin settings and capabilities only if "delete data" was ticked. Orders are never touched.

## Files
| File | Purpose |
|---|---|
| `deco-store-core.php` | Header (`Requires Plugins: woocommerce`), constants, autoloader, HPOS declaration, boot hook |
| `src/Core/Autoloader.php` | PSR-4 autoloader (no Composer on the server) |
| `src/Core/Plugin.php` | Boot, requirement notice, text domain, `ds_core_loaded` action for later modules |
| `src/Core/Requirements.php` | Pure version checks + Arabic messages |
| `src/Core/Capabilities.php` | Capability list, default role map, grant / revoke |
| `src/Core/Settings.php` | Schema, defaults, idempotent server-side sanitising, Settings API registration |
| `src/Core/Migrator.php` / `Activator.php` | Upgrade steps / activation |
| `src/Core/Logger.php` | Privacy-safe logging |
| `src/Admin/Menu.php` | Menu by capability; `ds_core_admin_menu` action for later screens |
| `src/Admin/DashboardPage.php` / `StatusChecks.php` | Status page |
| `src/Admin/SettingsPage.php` | Tabbed settings form |
| `assets/admin.css` | Admin styles, loaded on this plugin's screens only (RTL-safe, brand palette) |
| `uninstall.php`, `readme.txt`, `phpcs.xml.dist` | Cleanup, WordPress readme, coding standard |

## Decisions
- Own autoloader instead of requiring `vendor/`: the ZIP installs anywhere.
- `sanitize()` is **idempotent** and validates every known key by type: WordPress runs the sanitize callback twice on a save, and an earlier version lost the new value on the second pass (caught by the browser tests, now covered by a unit test and an integration check).
- Defaults follow the owner's decisions: `processing` for new orders; nothing deleted unless opted in.
- Admin screens are Arabic (source strings), logical CSS, text + symbol on every status badge (not colour alone).

## Tests (`tests/plugin/`)
- `unit/` (PHPUnit, no WordPress): autoloader, requirements, settings (incl. forged values, idempotency), capabilities (costs/suppliers/settings private), logger redaction.
- `integration.php`: against real WordPress + WooCommerce: capabilities per role, idempotent activation, migrations, real save path, HPOS declaration, logs contain no phone/cost, no Elementor Pro reference.
- `ui.js`: admin as administrator / shop manager / subscriber; save, persistence across tabs, forged value, forged request without nonce, phone width.
- `uninstall-check.php`, `install-plugin-zip.js` (real uploader), missing-WooCommerce behaviour.
