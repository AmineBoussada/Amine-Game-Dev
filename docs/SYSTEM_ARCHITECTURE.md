# SYSTEM_ARCHITECTURE

## 1. Deployable units
```
wp-content/plugins/top-solution-core/     all business logic, admin UI, rendering
wp-content/themes/hello-elementor-child/  global design, RTL tokens, fonts, header/footer; NO business logic
```
Elementor: used for general pages (Home, About, Contact, policies). The
single-product view of landing-enabled products is rendered by the plugin;
Elementor is not involved there (decision A).
Required plugins: WooCommerce (hard dependency, checked on activation).
Elementor (soft dependency for general pages only).

## 2. Plugin modules (PSR-4, namespace `TopSolution\`)
```
Core        Bootstrap, Container, Hooks loader, Migrator, Capabilities,
            Settings, Assets, Logger, HPOS declaration, Requirements check
Landing     TemplateRegistry, SectionRegistry, ConfigRepository, Renderer,
            Preview, ProductDataTab (admin)
Offers      OfferRepository (CPT), OfferEngine (pure), Eligibility,
            Resolver, Simulator (admin)
Forms       FormRepository (CPT), FieldTypeRegistry, FormRenderer,
            SubmissionValidator, Builder (admin)
Orders      CheckoutController (public endpoint), OrderFactory,
            PricingService, Idempotency, Confirmation, OrderAdminMeta
Delivery    MethodRepository, ZoneRepository, RateRepository,
            RateCalculator, ShippingMethod (WC_Shipping_Method subclass)
Inventory   AvailabilityService, StockLedger, LowStockReport
Suppliers   SupplierRepository (CPT), PurchaseRepository, CostGuard
Reports     Query builders, Admin report screens, CSV export
Admin       Menu, Screens, Tabs, Notices, Dashboard
Security    Tokens, RateLimiter, AntiSpam, Sanitizer, Guard (capability checks)
```
Layers: Domain services (pure PHP, no WP calls; unit-testable) → Repositories
(only DB/meta access) → Controllers (nonce/capability/sanitise, call
services) → Views (escape, output only). Dependency direction is one-way.

## 3. Integration with WooCommerce
- Products: WC product objects; our data in `_ts_*` product meta.
- Orders: `wc_create_order()`, WC CRUD only (HPOS safe). Never `get_post_meta`
  / `WP_Query` for orders; use `wc_get_orders()` / data stores.
- Stock: WC native stock management (`wc_update_product_stock`, order stock
  reduction). Our StockLedger only records movements.
- Shipping: a native WC shipping method class backed by our rate tables, so the
  order carries a real WC shipping line item.
- Payment: native COD gateway (`cod`) enabled; no custom gateway.
- Emails: native WC emails; our data never added to customer emails except
  explicit non-sensitive order lines.
- Customers: guest orders; billing fields populated from the form.

## 4. Independence rules (templates ⟂ offers ⟂ forms)
- A landing page stores *references*: `template_id`, `form_id`, `offer_ids[]`.
- Templates never compute prices or build forms; they call
  `Slot::render('order_form')` and `Slot::render('offers')`, which delegate
  to Forms / Offers modules and receive view-models.
- Changing a template changes presentation only. Offers/forms are reusable
  across products and templates.
- OfferEngine and PricingService do not import any Landing class.

## 5. Native-first storage summary (details: DATABASE_SCHEMA)
Native: products, orders, customers, stock, shipping lines, options,
product/order meta, private CPTs for offers/forms/suppliers.
Custom tables (justified only): `ts_purchase_lots`, `ts_stock_movements`,
`ts_delivery_rates`, `ts_geo_units`, `ts_submission_log` (optional).
Note: this revises the earlier draft which put offers in a custom table.

## 6. Admin navigation (top-level menu "Top Solution", Arabic labels at build)
| Menu | Tabs / contents | Capability |
|---|---|---|
| Dashboard | Today's orders, low stock, pending supplier items, quick links | ts_view_reports |
| Landing Pages | List of products with LP status/template; bulk template assign | ts_manage_landing |
| Offers | List · Add/Edit · Simulator | ts_manage_offers |
| Forms | List · Builder · Field library | ts_manage_forms |
| Orders | Link to WC Orders pre-filtered by `_ts_source` + COD status filters | manage_woocommerce |
| Inventory | Availability · Stock ledger · Low stock | ts_manage_inventory |
| Suppliers | Suppliers · Purchases · Costs (cost tabs need ts_view_costs) | ts_manage_suppliers |
| Delivery | Methods · Zones & Rates · Pickup Points · Carriers · Geography | ts_manage_delivery |
| Reports | Sales · Offers · Delivery · Stock · Margin(cost cap) · Export | ts_view_reports |
| Settings | General · Orders · Anti-spam · Landing defaults · Permissions · Tools(diagnostics, data uninstall) | ts_manage_settings |
**Product edit screen** (native WC "Product data" gets a "Top Solution" tab with
sub-tabs): Landing Page · Sections · Order Form · Offers · Availability ·
Supplier & Cost (cost cap only) · Preview.

## 7. Capabilities (custom, mapped to roles on activation)
`ts_manage_landing, ts_manage_offers, ts_manage_forms, ts_manage_delivery,
ts_manage_inventory, ts_manage_suppliers, ts_view_costs, ts_view_reports,
ts_manage_settings`. Administrator gets all; `ts_view_costs` is granted to
no other role by default (Q10).

## 8. Request flows
- **Render**: product request → `Renderer` loads `_ts_lp_config` → TemplateRegistry
  → SectionRegistry → output (fragment cache keyed by product modified time +
  config version + offer versions).
- **Submit**: see ORDER_WORKFLOW.
- **Admin save**: capability + nonce → validate against schema → save → bump
  version → invalidate caches.

## 9. Security requirements
1. Server computes every price, discount, fee, total; posted monetary values rejected.
2. Public endpoint: server-issued form token (HMAC, TTL), honeypot, rate limit
   (IP + phone + product), body size limit, optional CAPTCHA, JSON/field allow-list.
3. Admin: capability check + nonce on every action; no `nopriv` for admin ops.
4. Sanitise on input, escape on output, `$wpdb->prepare` always.
5. Costs/suppliers: `_ts_` underscore meta (not exposed in WC REST `meta_data`),
   CPT `show_in_rest=false`, `public=false`, cap gated, excluded from exports
   and emails; automated leak tests on REST, front-end HTML and emails.
6. Confirmation page access by order key + unguessable token, not order ID.
7. No unserialize of user data; JSON only; schema validated.
8. Logging via `WC_Logger` without personal data or costs.
9. Security headers recommendations documented (CSP compatible assets, no inline script where feasible).
10. Updates: DB migrations versioned, reversible where feasible, backup prompt.

## 10. Performance requirements
- Frontend assets enqueued only on landing/confirmation pages; one CSS + one
  small vanilla-JS bundle (no jQuery, no Elementor payload there); RTL CSS
  generated at build; font subsetting (Arabic) via child theme.
- Images: use WC attachments with responsive `srcset`, lazy-load below fold,
  LCP image preloaded.
- Fragment/object cache for rendered landing HTML; prices read live via WC.
  Cache invalidated on product, offer, form, config save.
- No N+1 queries: batch-load offers/forms per request; indexed custom tables.
- Compatible with full-page cache (form token fetched post-load, no-store).
- Admin: paginated lists, no autoloaded large options.
- Targets (proposals): server TTFB cached < 200 ms; landing JS < 30 KB gz;
  submit-to-confirmation < 2 s typical.

## 11. Conventions & quality
WordPress Coding Standards (PHPCS), PHPStan, PHPUnit (unit + WP integration
incl. HPOS matrix), Playwright E2E (RTL, mobile viewport). Composer
autoload; `languages/` POT; semantic versioning; changelog; no commits of
secrets; DB version constant + Migrator.
