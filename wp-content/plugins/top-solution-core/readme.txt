=== Top Solution Core ===
Contributors: topsolution
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
Requires Plugins: woocommerce
Stable tag: 0.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Business logic for the Top Solution store: landing pages, cash-on-delivery orders, offers, delivery, inventory and reports. Works with Elementor Free; Elementor Pro is never required.

== Description ==

Phase 1 (this version) is the foundation:

* Requirements check (PHP, WordPress, WooCommerce) with a clear admin notice.
* HPOS (High-Performance Order Storage) compatibility declared; orders are only ever handled through WooCommerce order objects.
* Custom capabilities (ts_manage_landing, ts_manage_offers, ts_manage_forms, ts_manage_delivery, ts_manage_inventory, ts_manage_suppliers, ts_view_costs, ts_view_reports, ts_manage_settings). Purchase costs and suppliers are administrator-only by default.
* Settings (Top Solution > Settings): initial order status (default: processing), diagnostics logging, delete-data-on-uninstall.
* Status dashboard (Top Solution > Dashboard).
* Versioned migrations, privacy-safe logging through WooCommerce logs.

Landing pages, the order form, offers, inventory and reports arrive in later versions.

Elementor: the plugin does not use Elementor at all. It never requires Elementor Pro (no Theme Builder, WooCommerce Builder, Popup Builder or Pro forms).

== Installation ==

1. Install and activate WooCommerce.
2. Upload top-solution-core.zip in Plugins > Add New > Upload Plugin, then activate.
3. Open Top Solution > Dashboard and review the status list.

== Uninstall ==

Deactivating or deleting the plugin keeps your data unless "delete data on uninstall" is ticked in Top Solution > Settings > General. Orders are WooCommerce data and are never deleted by this plugin.

== Changelog ==

= 0.3.0 =
* Form builder (Phase 3): forms as private records, builder with arrow reordering, 14 field types, conditional visibility rules, a default form (name, phone, wilaya, commune, delivery method, office, address, note), duplicate / set default / delete.
* Server-side validation of every submission (hidden fields are discarded), dependent wilaya > commune > office lists through public read-only REST routes, phone rule setting (generic or Algerian mobile preset).

= 0.2.0 =
* Delivery (Phase 2): wilayas and communes (CSV import from the owner's approved source, nothing pre-loaded), delivery methods (home, delivery office), rates per wilaya with commune overrides and a default, explicit "not served" areas, free-delivery threshold, pickup points, price preview, WooCommerce shipping method "Top Solution — Delivery" and one-click shipping zone setup.
* Every import shows a dry run first; the confirmed import applies exactly the previewed rows.

= 0.1.0 =
* Foundation: requirements, HPOS declaration, capabilities, settings, migrations, logger, status dashboard.
