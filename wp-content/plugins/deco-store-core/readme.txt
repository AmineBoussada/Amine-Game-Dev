=== Déco Store Core ===
Contributors: decostore
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
Requires Plugins: woocommerce
Stable tag: 0.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Business logic for the Déco Store shop: landing pages, cash-on-delivery orders, offers, delivery, inventory and reports. Works with Elementor Free; Elementor Pro is never required.

== Description ==

Phase 1 (this version) is the foundation:

* Requirements check (PHP, WordPress, WooCommerce) with a clear admin notice.
* HPOS (High-Performance Order Storage) compatibility declared; orders are only ever handled through WooCommerce order objects.
* Custom capabilities (ds_manage_landing, ds_manage_offers, ds_manage_forms, ds_manage_delivery, ds_manage_inventory, ds_manage_suppliers, ds_view_costs, ds_view_reports, ds_manage_settings). Purchase costs and suppliers are administrator-only by default.
* Settings (Déco Store > Settings): initial order status (default: processing), diagnostics logging, delete-data-on-uninstall.
* Status dashboard (Déco Store > Dashboard).
* Versioned migrations, privacy-safe logging through WooCommerce logs.

Landing pages, the order form, offers, inventory and reports arrive in later versions.

Elementor: the plugin does not use Elementor at all. It never requires Elementor Pro (no Theme Builder, WooCommerce Builder, Popup Builder or Pro forms).

== Installation ==

1. Install and activate WooCommerce.
2. Upload deco-store-core.zip in Plugins > Add New > Upload Plugin, then activate.
3. Open Déco Store > Dashboard and review the status list.

== Uninstall ==

Deactivating or deleting the plugin keeps your data unless "delete data on uninstall" is ticked in Déco Store > Settings > General. Orders are WooCommerce data and are never deleted by this plugin.

== Changelog ==

= 0.5.0 =
* Orders (Phase 5): order form widget ([ds_order_form]) with live server-side price summary, submission pipeline (signed token, honeypot, rate limit, idempotency, server validation and pricing), real WooCommerce cash-on-delivery orders with price/delivery/form snapshots, confirmation page, order screen box and list column, duplicate flag. Works with HPOS and legacy order storage.
* WooCommerce REST responses no longer include Déco Store internal meta.

= 0.4.0 =
* Offers (Phase 4): server-side pricing engine (quantity tiers, bundles, free gifts, custom rules with a closed vocabulary), offers editor, "who wins and why" price simulator, overlap warnings, settings for gifts per order and a minimum-price guard.
* Every price is computed on the server from WooCommerce prices; the browser never supplies a price.

= 0.3.0 =
* Form builder (Phase 3): forms as private records, builder with arrow reordering, 14 field types, conditional visibility rules, a default form (name, phone, wilaya, commune, delivery method, office, address, note), duplicate / set default / delete.
* Server-side validation of every submission (hidden fields are discarded), dependent wilaya > commune > office lists through public read-only REST routes, phone rule setting (generic or Algerian mobile preset).

= 0.2.0 =
* Delivery (Phase 2): wilayas and communes (CSV import from the owner's approved source, nothing pre-loaded), delivery methods (home, delivery office), rates per wilaya with commune overrides and a default, explicit "not served" areas, free-delivery threshold, pickup points, price preview, WooCommerce shipping method "Déco Store — Delivery" and one-click shipping zone setup.
* Every import shows a dry run first; the confirmed import applies exactly the previewed rows.

= 0.1.0 =
* Foundation: requirements, HPOS declaration, capabilities, settings, migrations, logger, status dashboard.
