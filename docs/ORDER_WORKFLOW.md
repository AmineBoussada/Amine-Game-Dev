# ORDER_WORKFLOW

Decision D: real WooCommerce orders, HPOS support, price + offer snapshots,
duplicate-submission prevention.

## 1. Actors & entry
Customer on landing page → form submit → `CheckoutController` (public AJAX or
REST route, `POST`, JSON). Response: success {redirect_url} or field errors.

## 2. Preconditions on page load
Frontend fetches (no-store endpoint) a **form token**: HMAC-signed
{product_id, form_id, form_version, issued_at, nonce_seed}, TTL (setting,
e.g. hours) and an **idempotency key** (random 128-bit) bound to the token.

## 3. Submission payload (allowed contents only)
token, idempotency_key, product_id, variation selection (attributes),
quantity, chosen_offer_id, delivery method id + geo ids + pickup point id,
form field values, honeypot, optional CAPTCHA response. Any price/total
field present → ignored and flagged.

## 4. Server pipeline (ordered; fail fast)
1. Method/size/Content-Type; token signature + TTL + product match.
2. Rate limit (IP, phone, product); honeypot; CAPTCHA (if enabled); blocklist.
3. **Idempotency gate** (§6). If key already completed → return the
   original order's confirmation URL.
4. Load product (WC); must be published, purchasable, landing-enabled or
   allowed; resolve variation; check availability policy (§7) and quantity
   bounds.
5. Validate/sanitise form (FORM_BUILDER_SPEC §4).
6. Delivery: validate method/geo/pickup; `RateCalculator` → fee.
7. `PricingService` → OfferEngine → `PricingResult`.
8. If client `expected_total` (display-only) ≠ server total → return
   `price_changed` with new totals; **no order created**; UI asks confirm
   (resubmits with same idempotency key unlocked).
9. Final stock check inside creation transaction scope (WC row-level stock handling).
10. **Create order** (§5).
11. Mark idempotency key complete with order id; return confirmation URL.
12. Post-actions (async-safe): admin notification email, optional webhooks later.

## 5. Order creation (WC CRUD only)
- `wc_create_order(['status'=>'pending'])` → add product line items with
  `set_subtotal/set_total` from engine result (line prices come only from
  PricingResult); gift lines at 0 with `_ds_is_gift`; shipping line via the
  DS WC shipping method (rate = server fee); set billing address (country DZ,
  state, city, address, phone, name); payment method `cod`
  (title from gateway); `calculate_totals(false)` then **assert**
  `get_total()` equals engine grand_total (else abort + log + delete draft).
- Save meta: pricing/delivery/form snapshots, source, template, flags,
  costs snapshot (cost cap meta) (DATABASE_SCHEMA §4).
- Status transition: to the owner-chosen initial status (`processing` or
  `on-hold`, Q1) using WC status API so stock reduction, emails, and hooks
  run natively.
- Add private order note: "Created from landing page <template>" (no costs).
- All through CRUD objects; no direct SQL on orders → HPOS/legacy both work.

## 6. Duplicate-submission prevention (layers)
1. Client: disable button, single in-flight request.
2. **Atomic idempotency**: `add_option('ds_idem_'.hash(key), state, '', 'no')`
   — unique option name ⇒ only first request proceeds; states: `processing` →
   `done:<order_id>`; concurrent duplicates receive `409 in_progress` or the
   completed result. Stale `processing` (> N seconds) recoverable by cron
   after verifying no order carries that key (`_ds_idempotency_key` lookup via
   `wc_get_orders` meta query).
3. Order meta `_ds_idempotency_key` for audit/recovery.
4. Heuristic duplicate warning: same normalised phone + same product within
   window (setting) → order created but flagged `_ds_flags.duplicate_suspect`
   for admin review (blocking policy is a business decision, Q13).
5. Cleanup cron removes expired idempotency rows.

## 7. Availability & stock rules
| Availability | Behaviour |
|---|---|
| in_stock | WC stock enforced; qty ≤ stock; order reduces stock natively |
| from_supplier | Accepted without stock (backorders-equivalent); flagged "needs purchase"; lead-time text if owner supplied |
| unavailable | Form hidden; submit rejected |
Gift items follow stock rules; if gift unavailable → gift dropped and
recorded in snapshot (`rejected`), customer informed in preview.

## 8. Confirmation page
Custom endpoint/page `Order Confirmation` reached with `?ds_order=<id>&key=<wc_order_key>&t=<token>`;
validated against WC order key (`$order->key_is_valid`). Shows: WC order number
(unique, from WC — optionally sequential-number plugin later), items with
offer labels, options, delivery method/fee/pickup point, total payable on
delivery, next-step message (owner text), contact. Reload-safe (read-only),
`noindex`, no-store cache headers. No costs or internal flags displayed.

## 9. Failure handling
Validation errors → 422 with field messages. Server failure after partial
creation → compensating delete/cancel + idempotency key reset to allow
retry; every failure logged (without personal data) with correlation id.
Customer-facing generic message in Arabic; support reference id.

## 10. Admin experience
WC Orders list: column/filter "Source: Landing page", template, offer,
flags (duplicate/spam), delivery type. Order edit screen: read-only meta box
"Déco Store" (snapshots, formatted); cost cap users also see costs/margin.
Status workflow: owner-defined COD confirmation/dispatch (custom statuses
optional — decide Q16/Q1; otherwise native statuses).

## 11. Test matrix
Tampered prices/ids; replayed token; expired token; concurrent identical
submits (parallel requests); network retry; offer expired between display
and submit; stock exhaustion race; HPOS on/off; guest; Arabic input edge
cases; each template; email contents contain no costs.
