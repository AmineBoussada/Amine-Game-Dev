# PROJECT_SPEC — Top Solution

Status: DRAFT for approval. No application code exists. Supersedes the earlier
Phase 0 drafts (removed). Companion documents: SYSTEM_ARCHITECTURE,
DATABASE_SCHEMA, LANDING_PAGE_SYSTEM, OFFER_ENGINE_SPEC, FORM_BUILDER_SPEC,
ORDER_WORKFLOW, INVENTORY_AND_SUPPLIERS, DELIVERY_SYSTEM, DEVELOPMENT_ROADMAP.

## 1. Business
Single-vendor Algerian store: interior decoration and home improvement
(homes, kitchens/bathrooms, offices, commercial shops). Direct online sales,
Cash on Delivery (COD) only; home delivery or delivery-office pickup; stock
may be physical or sourced from suppliers after the order.
No business data (products, prices, suppliers, carriers, fees, wilaya lists)
is invented anywhere in these documents; all of it is admin-entered config.

## 2. Stack & language
WordPress, WooCommerce, Hello Elementor child theme, **Elementor Free (Elementor Pro is never required)**, PHP, MySQL,
HTML5/CSS3/JS. Arabic only in release 1 (storefront + our admin screens),
full RTL. All strings use i18n (`top-solution` text domain) so languages can
be added later; Arabic is the only shipped translation.

## 3. Product principles (the 14 rules, operationalised)
| Rule | How it is enforced |
|---|---|
| WooCommerce is source of truth | Products/customers/orders only in WC objects; our data = config + snapshots |
| No parallel order system | Orders created only via `wc_create_order()`; "Top Solution Orders" admin menu is a filtered view of WC orders |
| Separation templates / offers / forms | Three independent entities referenced by ID (see SYSTEM_ARCHITECTURE §4) |
| Never modify core | Hooks/filters only; child theme for design |
| Logic in plugin, design in child theme | Plugin `top-solution-core`; theme has zero business logic |
| HPOS | Declared compatible; only WC CRUD for orders; tested with HPOS on and off |
| Server validates prices/forms | Client sends IDs/choices only; see ORDER_WORKFLOW |
| Protect costs/suppliers | Capability `ts_view_costs`, underscore meta, no REST/email/export exposure, leak tests |
| Modular & documented | Module boundaries, PHPDoc, PHPCS, tests |

### Elementor Free only (binding requirement, 2026-11-28)
No Elementor Pro widgets, Theme Builder, WooCommerce Builder, Popup Builder or
Pro forms at any stage, in the theme or the plugin. Header, footer and every
store template (archives, single product, cart, checkout, My Account) are
native theme/WooCommerce templates. Elementor Free is used only for editable
page content; the store must keep working with Elementor deactivated.

## 4. WordPress pages (all)
**Created/required (WooCommerce standard):** Shop (archive), Cart, Checkout,
My Account — kept installed for WooCommerce compatibility; the primary flow
bypasses cart/checkout. Fallback use (e.g. products without landing page) is
a decision below (Q4).
**Top Solution pages (created on activation, editable, Elementor allowed):**
| Page | Purpose | Built with |
|---|---|---|
| Home | Merchandising | Elementor |
| Categories / Shop | Product browsing | WC archive + child theme |
| Order Confirmation | Thank-you for landing orders (`[ts_order_confirmation]`) | Plugin shortcode/endpoint, styled by theme |
| Delivery Information | Policy (content from you) | Elementor/editor |
| Returns Policy | Content from you | editor |
| About / Contact | Content from you | Elementor |
| Terms & Conditions, Privacy | Content from you | editor |
| 404 / Search | Child theme templates | theme |
**Virtual (no page object):** per-product landing page = product permalink
rendered by plugin; preview URL `?ts_preview=<token>`.
Not planned: customer account area, order tracking, blog (confirm Q9).

## 5. WooCommerce product types
| Type | Support | Notes |
|---|---|---|
| Simple | Yes | Core case |
| Variable | Yes | Options = variation attributes; per-variation price/stock |
| Grouped | No | Replaced by TS Bundle offers |
| External/affiliate | No | Not a COD flow |
| Virtual / downloadable | No | Physical goods + delivery only |
| Third-party bundle/composite plugin types | No | Bundles are TS offers, avoids plugin dependency |
Free-gift products: simple or variable WC products, flagged as gift-only
(hidden from catalogue, price ignored in order).

## 6. Scope
In: landing pages (3 templates), offer engine, form builder, COD orders,
confirmation page, inventory/availability, suppliers/purchases/costs,
delivery config, reports, admin UI, security hardening.
Out of release 1: payment gateways, multilingual UI, carrier API
integrations, customer accounts, SMS/WhatsApp, marketing pixels (unless
requested), multi-vendor.

## 7. Non-functional requirements (summary)
Security and performance requirements are detailed in SYSTEM_ARCHITECTURE
§9–10. Headline: all pricing server-side; public endpoints nonce/token +
throttled; landing page assets only on landing pages; no jQuery dependency
on the frontend; LCP target ≤ 2.5 s on mid-range mobile (proposal, to be
validated); WCAG-conscious RTL markup.

## 8. Technical risks
| # | Risk | Mitigation |
|---|---|---|
| R1 | Page caching breaks nonces/idempotency tokens | Token fetched by a no-cache endpoint after load |
| R2 | Order double-creation (retries, double-click, flaky mobile networks) | Atomic idempotency (ORDER_WORKFLOW §6) |
| R3 | Offer rules conflict/unpredictable discounts | Deterministic resolution + admin simulator + exhaustive unit tests |
| R4 | Stock oversell with supplier-sourced items and concurrent orders | WC stock reduce under its own locking; "supplier" availability bypass explicit per product |
| R5 | Cost data leakage (REST, exports, emails, plugins that dump meta) | Underscore meta, cap checks, tests, no cost in order notes/emails |
| R6 | HPOS regressions | Only CRUD APIs; CI matrix with HPOS on/off |
| R7 | Elementor + custom rendering interplay | Landing system owns single-product view; Elementor excluded there |
| R8 | Rounding errors on DZD (decimals setting) | Integer minor-unit math, single rounding policy |
| R9 | Spam/fake COD orders (high in COD markets) | Honeypot, throttle, phone/IP rules, optional CAPTCHA, blocklist, duplicate warning |
| R10 | Wilaya/commune data accuracy (administrative changes) | Admin-managed dataset, versioned import, source approved by owner |
| R11 | Plugin conflicts (caching, SEO, security plugins) | Compatibility checklist at launch |
| R12 | Scope creep from "custom rules" | Constrained rule schema, no arbitrary code |

## 9. Missing business decisions (must be answered; none assumed)
1. Initial COD order status: `processing` or `on-hold` (phone confirmation)?
2. Hosting/environment, PHP/WP/WC versions, staging availability.
3. Currency decimals setting for DZD (whole dinars or 2 decimals)?
4. Do products without a landing page use the normal WC cart/checkout, or must every product have a landing page?
5. Offer stacking policy defaults (see OFFER_ENGINE_SPEC §7).
6. Is the customer chooses between offers (cards) or is the best offer automatic?
7. Delivery: carriers, zones, rates, pickup points, free-delivery rules, who
   pays COD fees/returns.
8. Wilaya/commune source list to adopt; which communes are served.
9. Pages wanted beyond §4 (blog, tracking, FAQ).
10. Who may see costs/suppliers (admin only, or shop manager too).
11. Phone validation formats accepted.
12. Branding: logo, colours, fonts, Arabic copy, reference sites.
13. Anti-spam: CAPTCHA provider; blocklist policy; duplicate-order policy.
14. Notifications (email to admin? WhatsApp/SMS later?).
15. Reporting priorities and margin definition (include delivery cost?).
16. Return/refund handling workflow for COD.
17. Tracking pixels/analytics requirements.
18. Product data entry: who creates products and how many initially?
