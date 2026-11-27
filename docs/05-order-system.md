# 05 — Top Solution Order

## Flow
1. Customer opens landing page, picks options/offer, fills form.
2. Frontend sends **only identifiers and choices**: product_id, variation/
   option ids, offer id, quantity, delivery method/zone ids, field values,
   nonce. **Never prices.**
3. Server (`OrderService`):
   a. Verify nonce, rate-limit, honeypot, (optional) CAPTCHA.
   b. Load product from WC; verify purchasable + availability.
   c. Validate/sanitise every field against the form definition.
   d. `PriceCalculator`: base price from WC → apply offer via `OfferEngine`
      (re-validating offer is active, applicable, quantity valid) → add
      delivery fee from `RateCalculator`. Output a signed-nothing, pure
      computation result.
   e. Create order with `wc_create_order()`, add line items (+gift items),
      set address fields, payment method `cod`, set totals via WC
      (`calculate_totals`), save snapshots (03 §3.3), status `processing`
      (or `on-hold` if you prefer manual confirmation call — Q in 09).
   f. Reduce stock via WC native flow.
   g. Return order key → redirect to confirmation page.
4. Confirmation page: custom URL/endpoint, shows order number (WC order
   number), items, offer applied, delivery, total to pay on delivery,
   next-step message. Access verified by order key (no enumeration by ID).
   Double-submit protected (idempotency token → same order returned).

## Offer types (separate from templates)
- **Quantity discount**: tiers (min_qty → percent or fixed unit price).
- **Bundle**: fixed set of products/variations at a bundle price.
- **Free gift**: optional gift product, condition (min qty / min total / always).
- **Promo price**: scheduled price override (start/end).
Rules: explicit priority and stacking policy (default: **no stacking**,
best single offer or customer-selected; confirm in 09). `OfferEngine` is pure
and unit-tested with a table of cases.

## Dynamic options
Variable products → WC variations (attributes → variation id resolved on
server). Option availability and per-variation price read from WC.

## Customisable fields
Form = ordered field list (text, phone, select, textarea, wilaya/commune
selects, checkbox). Core fields (name, phone, wilaya, address) locked
as required; others toggleable. Phone validation for Algerian numbers —
exact rules confirmed in 09. Algerian locale/wilaya/commune data supplied/
verified by you, not invented.

## Edge cases to test
Price changed in admin mid-session; offer expired mid-session; stock hits 0;
tampered POST (price, offer, product id); duplicate submits; variation not
purchasable; gift out of stock; HPOS on/off; guest only.
