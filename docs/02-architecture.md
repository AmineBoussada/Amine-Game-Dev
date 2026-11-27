# 02 — Architecture

## Components
```
wp-content/
  plugins/top-solution-core/     ALL business logic (never in the theme)
  themes/hello-elementor-child/  Global design only
```
Elementor is used for global/site pages; per-product landing pages are
rendered by the plugin from structured data (see decision D-1).

## Plugin layout (proposed)
```
top-solution-core/
  top-solution-core.php        bootstrap, HPOS declaration, dependency check
  uninstall.php
  src/
    Core/        Plugin, Loader, Activator, Migrator, Capabilities, Assets
    Landing/     TemplateRegistry, SectionRegistry, LandingRepository,
                 Renderer, Preview, ProductMetaBox
    Offers/      OfferRepository, OfferTypes (qty-discount, bundle, gift),
                 OfferEngine (pure pricing logic), AdminUI
    Forms/       FormRepository, FieldRegistry, FormRenderer, Validator
    Orders/      OrderService (creates WC order), PriceCalculator,
                 ConfirmationPage, OrderMeta
    Inventory/   AvailabilityService, StockSync
    Suppliers/   SupplierRepository, PurchaseRepository, CostGuard
    Delivery/    ZoneRepository, RateCalculator, PickupPoints
    Reports/     QueryBuilders, AdminReports
    Admin/       Menus, Screens, Notices
    Rest/        (only if needed) internal endpoints, nonce/cap protected
  templates/     PHP view files (landing templates, form, confirmation)
  assets/        css/js (RTL-first)
  languages/
  tests/
```
PSR-4 autoloading via Composer (`TopSolution\`), PHP namespaces, no globals.

## Layering rules (enforce separation)
1. **Domain/services** (OfferEngine, PriceCalculator, RateCalculator,
   AvailabilityService): pure PHP, no HTML, unit-testable.
2. **Repositories**: only place that touches DB / meta.
3. **Controllers** (admin screens, AJAX/REST handlers): validate, call services.
4. **Views/templates**: output only; receive prepared view-models.

Separation required by the brief:
- Landing templates ⟂ offers ⟂ forms: three independent registries/entities.
  A landing page *references* an offer set and a form by ID; templates never
  compute prices or build the form, they call `render_slot('order_form')`.
- Changing template never alters offers or form config.

## Integration with WooCommerce
- Products: native `product` CPT / WC product objects (simple + variable).
- Orders: `wc_create_order()` + WC CRUD (`WC_Order`), never `wp_posts`/postmeta
  queries for orders → HPOS safe.
- Customers: WC customer data on the order (guest checkout; no accounts forced).
- Stock: native WC stock management; our availability states layer on top.
- Emails/status flow: native WC statuses (`processing`/`on-hold`), COD gateway
  (`cod`) as payment method.
- Declares HPOS (`custom_order_tables`) compatibility on `before_woocommerce_init`.

## Key decisions (need your approval)
- **D-1 Landing page rendering**: plugin-rendered PHP templates from structured
  section data (reliable, fast, server-validated, per-product editable fields)
  instead of per-product Elementor documents. Elementor remains for global
  pages, and a section may optionally allow an Elementor template as
  "custom content block" later.
- **D-2 Storage**: landing config, offers, forms → custom post types or
  dedicated tables? Proposal: **offers and suppliers/purchases/delivery in
  custom tables** (relational, queryable for reports); **landing config as
  product meta** (JSON, one key); **forms as a private CPT** (`ts_form`,
  reusable across products). Details in 03.
- **D-3 Frontend submit**: AJAX endpoint (`admin-ajax` or REST) with nonce;
  server recomputes everything. Works without trusting any posted price.
- **D-4 Min versions**: to be confirmed in 09 (PHP, WP, WC).
