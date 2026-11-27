# OFFER_ENGINE_SPEC

Decision B: a server-side pricing engine is the sole authority for prices.
The frontend displays previews computed by the same rules via a server
endpoint or pre-rendered tables, but never submits prices.

## 1. Inputs / outputs
Input (`PricingRequest`): list of lines {product_id, variation_id, qty,
chosen_offer_id?}, delivery selection (method, geo), timestamp, currency
settings. Output (`PricingResult`): lines with base/final unit prices,
applied offers, gifts, discount total, delivery fee, grand total, rejected
offers + reasons (see DATABASE_SCHEMA §4.1). Pure function: same input +
same offer versions ⇒ same output. No HTML, no DB inside the engine (data
passed in by `PricingService`).

## 2. Base price
`base_unit` = WooCommerce **active price** (`get_price()`), i.e. native sale
price/schedule is respected. Quantity ≤ stock rules are checked by
OrderFactory, not here.

## 3. Offer types
| Type | Definition | Effect |
|---|---|---|
| `sale_price` | Native WC sale price (+ schedule); optional TS "campaign price" override per offer with dates | sets unit price |
| `qty_discount` | Tiers [{min_qty, percent | fixed_off | unit_price}] on a product/variation scope | unit price by matching tier (highest min_qty ≤ qty) |
| `bundle` | Fixed set of items [{product/variation, qty}] with bundle price or percent | replaces line prices of those items; all items must be present |
| `free_gift` | Gift product(+variation), qty, condition | adds zero-priced gift line; stock checked |
| `custom_rule` | Conditions + actions from a closed vocabulary (below) | percent/fixed discount, set price, free delivery, gift |
Closed vocabulary (no code/expressions):
Conditions: min_qty, min_subtotal, product_in, category_in, geo_in
(wilaya/commune), delivery_method, date_window, weekday/time window (optional).
Actions: percent_off, fixed_off_line, fixed_off_order, set_unit_price,
free_delivery, add_gift, delivery_discount (percent/fixed).

## 4. Offer entity (CPT `ts_offer`)
type, status (draft/active/archived), scope, priority (int, higher wins),
group, exclusive flag, selectable flag (customer chooses vs auto), starts_at,
ends_at, usage_limit (optional global cap — decision Q), label (Arabic text
shown), conditions, actions, version.
Groups (stacking domains): `price` (affects unit price), `gift`,
`delivery`. Rule: **at most one `price`-group offer per line**; `gift` and
`delivery` offers can stack with a price offer unless any matched offer is
`exclusive`.

## 5. Evaluation algorithm
1. Collect candidate offers: scope matches line product/category/global;
   status active; within dates.
2. Eligibility: evaluate conditions against request; collect rejections with
   reasons.
3. Customer selection: if the landing page offers cards (`selectable`),
   `chosen_offer_id` must be one of the eligible selectable offers; else
   rejected (not silently swapped) and the UI is refreshed. Without a
   choice and auto offers present → step 4.
4. Resolve per group per line:
   a. If any eligible `exclusive` offer exists: highest `priority` exclusive
      wins; all other offers (including other groups) are dropped.
   b. Else `price` group: pick highest priority; tie → lowest final price for
      customer; tie → lowest offer id (deterministic).
   c. `gift` group: apply all eligible, up to `max_gifts_per_order` setting.
   d. `delivery` group: best discount wins (single).
5. Apply in fixed order: unit price offers → order-level fixed discounts →
   delivery adjustments → gifts.
6. Guards: final unit price never < 0 (and ≥ optional minimum price/floor
   percent of base — setting, protects against misconfiguration); discount
   total ≤ subtotal; bundle atomicity; gift stock check; rounding.
7. Produce result + rejected list.

## 6. Rounding & currency
Compute in integer minor units using WC decimals setting; percent discounts
rounded **half-up per unit** (policy to confirm, Q3); totals are sums of
rounded lines (no post-hoc rounding drift). DZD only; no multi-currency.

## 7. Conflict handling summary (defaults, owner may change — Q5)
- No stacking inside `price` group; explicit priority; deterministic tie-break.
- Native sale price is an offer of type `sale_price` with default priority 0:
  a qty discount (priority > 0) replaces it by default; setting
  "discounts compound on sale price" off by default.
- Admin warnings when two active offers overlap same scope with equal priority.
- **Simulator** screen: pick product/qty/geo → shows candidates, winner,
  rejected reasons, final totals.

## 8. Display vs authority
`Offers\DisplayProvider` prepares card data (labels, strike-through price,
savings) using the engine; the order endpoint always recomputes. Result
mismatch between displayed and recomputed total → response returns the
recomputed result and the UI asks customer to confirm (no silent change of
amount at submit).

## 9. Versioning & snapshot
Engine has `engine_version`; each offer has `version`. Snapshot stores
both. Editing an offer never alters past orders.

## 10. Testing (mandatory before release)
Table-driven unit tests: each type, boundaries (qty at tier edges), dates,
overlapping offers, exclusivity, gifts out of stock, rounding, floors,
tampered offer ids, variation prices, bundle partial presence, huge qty.
Property tests: total = Σ lines + delivery − order discounts; deterministic.
