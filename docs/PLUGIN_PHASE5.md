# Phase 5 — Orders (plugin v0.5.0)

## What it does
- **Order form for a product**: shortcode `[ds_order_form product="ID" form="ID"]` (form optional = default form; on a product page the product is taken from the page). It shows the variation choice (variable products), the quantity (unless the form has its own quantity field), the offers the customer may pick, the form, a live price summary and the button. Phase 6 places the same widget in the landing-page templates.
- **Live summary**: every change asks the server for a quote (`POST ds-core/v1/quote`); the browser never computes or sends a price.
- **Submission pipeline** (`POST ds-core/v1/orders`, ORDER_WORKFLOW §4), failing fast: honeypot → signed token (product, form, age) → minimum fill time (2 s; the page retries by itself) → rate limit per network address → idempotency gate → product/variation → form validation (server rules, hidden fields discarded) → delivery availability for the area → pricing engine → displayed total vs server total → stock → duplicate check → order creation.
- **Real WooCommerce orders** through CRUD only (HPOS and legacy storage both tested): product line (subtotal = base price, total = after offers), gift lines at 0, order discount as a negative fee line named after the offer, delivery line from the delivery tables (`ds_delivery`), guest billing/shipping (name, phone, DZ, wilaya state, commune, address or pickup office), cash on delivery, customer note. After WooCommerce computes its totals, **the order total must equal the engine total to the last unit, or the order is deleted**.
- Then the native status change to the owner's initial status (`processing`): stock reduction, e-mails and hooks run as usual.
- **Snapshots on the order** (`_ds_` meta): pricing (engine version, lines, offers with versions, rejected offers), delivery (method, area, office, carrier, rate id, fees), form id/version, extra answers, flags, idempotency key, hashed network address + time. Editing an offer, form or rate later never changes past orders.
- **Duplicates**: (1) button disabled while sending; (2) one random key per order attempt, claimed atomically in the database — a double click, a retry after a lost response or parallel requests give **one** order and the same confirmation; (3) the key stored on the order; (4) same phone + same product within a window (setting, default 24 h) → order created but flagged "possible duplicate" (never refused); (5) daily cleanup of old keys.
- **Price changed between display and submit** (offer ended, rate changed): no order; the new total is shown and the customer confirms again. A chosen offer that is no longer valid is never swapped silently.
- **Confirmation page** `/?ds_order=ID&key=<WooCommerce order key>`: order number, items, gifts, savings, delivery (office), total payable on delivery, owner message (setting). Not cached, `noindex`, safe to reload, 404 with a wrong key. No internal data.
- **Admin**: "Déco Store" box on the order screen (source, offers, delivery, extra answers, flags, versions) and a column in the orders list with flags; internal line meta hidden from the items table. Works on both order screens (HPOS and legacy).
- **Settings > الطلبات**: confirmation message, duplicate window, attempts per network address (default 20 per 10 min), page validity (default 12 h).

## Security notes
- Public endpoints on purpose (guest checkout). Protection: HMAC token signed with a key derived from the site salts (never stored or sent), honeypot, minimum fill time, rate limit, 32 KB JSON-only bodies, idempotency, everything recomputed on the server; price fields sent by a browser are ignored.
- Network address: only a salted hash is stored; forwarded headers are not trusted (they can be forged) — behind a proxy/CDN this needs configuration (Phase 9).
- **Finding fixed**: WooCommerce's REST API returns every order meta entry, underscore keys included, to staff and API keys. All `_ds_` meta is now removed from WooCommerce REST responses (orders, line items, products, variations). The data stays on the order.
- Logs: failures are logged with a reference number shown to the customer, never with personal data.

## Decisions to confirm
1. Duplicate policy: **flag, never block** (Q13). Window 24 h by default.
2. The whole customer name goes into "first name" (no guessing how to split Arabic names).
3. Stock: WooCommerce's own rules (backorders per product). If two customers take the last unit at the same moment, the second order is kept but flagged "stock ran out" with a private note. The "from supplier" availability comes with Phase 7.
4. The page needs JavaScript to order (a message says so without it).

## Files
`src/Orders/{Token,Idempotency,RateLimiter,Checkout,OrderFactory,Confirmation,OrderWidget,RestPrivacy,Module}.php`, `src/Rest/OrderController.php`, `src/Admin/OrderAdmin.php`, `assets/order.js`, `assets/forms.js` (server errors, live error list), `assets/forms.css` (widget + confirmation), settings, `uninstall.php`, `EngineInputs` (returns the delivery quote).

## Tests
- PHPUnit 253 tests (token: round trip, expiry edge, tampering, malformed, future-dated; settings incl. text type).
- Integration `orders-integration.php`, 63 checks, run with **HPOS on and with legacy storage** (`hpos-toggle.php`): happy path, totals, snapshots, stock, e-mails without internal data, confirmation access, idempotency, tampering, token/honeypot/speed, field errors, delivery not served, stock, variations, offers combined (WooCommerce total = engine total), offer changed, duplicate flag, rate limit, REST layer, no `_ds_` meta in WooCommerce REST.
- Browser `order-ui.js` (guest, phone): 22 checks incl. live summary, client errors and the error list clearing, total tampered → confirm, lost response + retry + double click → one order, fast customer auto-retry, three parallel identical requests → one order, confirmation (noindex, reload, wrong key 404), axe clean on form and confirmation, no JS errors. `order-admin-ui.js` on both storages.
- `uninstall-data-check.php`: with "delete data" ticked, uninstall removes forms, offers, idempotency rows, the daily schedule and settings, and keeps WooCommerce orders (this also closes the Phase 4 open point).
- All previous suites re-run (phases 1–4, theme 44 checks). `offers-ui.js` failed twice once inside a long chained run and passed on the three following runs; the failure details were not captured.

## Not done / open
- Landing-page templates (Phase 6) will host the widget; offer cards are plain radio buttons until then.
- CAPTCHA: not added (owner decision); the hooks are in place in the pipeline.
- Sequential order numbers: WooCommerce ids are used.
