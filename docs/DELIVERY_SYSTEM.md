# DELIVERY_SYSTEM

No carriers, zones, prices or geography are invented. Everything below is
structure; contents are admin-entered or imported from owner-approved data.

## 1. Concepts
- **Delivery method**: `home` (home delivery), `pickup` (delivery-office
  pickup). Extensible registry.
- **Carrier** (delivery company): name, status, notes; optional per-method
  availability. Carrier API integration is out of release 1.
- **Geography**: hierarchy wilaya → commune (`ds_geo_units`), active flags,
  imported from a source approved by owner (Q8). Maps to WooCommerce
  country DZ + state code for orders.
- **Zone**: named group of geo units (e.g. for pricing) — optional layer.
- **Rate**: price for (method [+carrier], zone or geo unit, quantity/weight
  band) in `ds_delivery_rates`; resolution order: commune-specific → wilaya
  → zone → method default → "unavailable".
- **Pickup point**: office name, geo unit, address, hours (owner data),
  active flag, carrier link. Only offered where defined.
- **Free delivery rules**: via offers (`free_delivery` action) and/or
  threshold in delivery config (min subtotal) — both optional.

## 2. Admin: Delivery menu tabs
Methods · Carriers · Geography (import/enable/disable) · Zones & Rates
(matrix editor: bulk set per wilaya, copy rates, CSV import/export) ·
Pickup Points · Rules (free delivery threshold, COD notes) · Preview tool
(choose geo/method/subtotal → shows resolved fee).
Validation: every active geo unit offered by a form must resolve to a rate or
be explicitly "unavailable"; coverage report lists gaps.

## 3. Calculation (`RateCalculator`, pure + repository)
Input: method id, geo ids, pickup point id, qty/subtotal/weight (if used),
offers' delivery effects. Steps: resolve rate → apply threshold/free rules
→ apply offer delivery action → clamp ≥ 0 → return fee + rate id/version +
explanation (for snapshot). Unknown/inactive combination ⇒ error, never a
default price.
Weight/volume bands: only if owner needs (large decor items may), requires
product weight/dimensions in WC — decision Q7.

## 4. WooCommerce integration
`DecoStore\Delivery\ShippingMethod extends WC_Shipping_Method` registered
and enabled via a shipping zone for country DZ (created by setup tool). Our
order flow builds the shipping line item from the server-resolved rate;
WC reports include it. Cart/checkout fallback pages call the same
RateCalculator through `calculate_shipping()`.

## 5. Form integration
Delivery method, wilaya, commune, pickup point fields (FORM_BUILDER_SPEC)
are bound to this config; dependent options loaded from a cacheable
read-only endpoint (versioned URL, immutable cache) to keep landing pages
fast; fee preview displayed from the same endpoint, but order recomputes.

## 6. Snapshot
`_ds_delivery_snapshot`: method, carrier, geo ids + names (Arabic),
pickup point, fee, rate id/version, rule applied. Later rate edits don't
alter past orders.

## 7. Reporting
Orders by wilaya/method/carrier, delivery revenue, failed/refused delivery
counts (needs status workflow, Q16).

## 8. Security/performance
Admin caps `ds_manage_delivery`; rates cached (object cache) with
version-bumped invalidation; composite index lookup; CSV import validated,
size-limited, dry-run preview before commit.

## 9. Open decisions
Carriers list; zones/rates; pickup points; who bears COD/return costs;
weight bands; delivery cost (to company) tracking for margin; service
coverage per commune; working hours/lead times text.
