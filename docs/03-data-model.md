# 03 — Data Model

Principle: use WooCommerce tables for products/customers/orders; add only
what WooCommerce lacks. All custom meta keys are prefixed `_ts_` (underscore
prefix hides them from the default REST/custom-fields UI).

## 3.1 Product meta (WC product, via CRUD `update_meta_data`)
| Key | Type | Notes |
|---|---|---|
| `_ts_lp_enabled` | bool | landing page replaces default product layout |
| `_ts_lp_template` | string | `standard` \| `promo` \| `premium` |
| `_ts_lp_config` | JSON | section order, visibility, content blocks, form position/display mode |
| `_ts_lp_form_id` | int | referenced form (`ts_form`) |
| `_ts_lp_offer_set` | JSON/ids | referenced offers (see 3.2) |
| `_ts_availability` | string | `in_stock` \| `backorder_supplier` \| `unavailable` (final names TBD) |
| `_ts_supplier_id` | int | default supplier (admin-only) |
| `_ts_purchase_cost` | decimal | **admin-only, never rendered/exposed** |

## 3.2 Custom tables (dbDelta, versioned by `Migrator`)
Prefix `{$wpdb->prefix}ts_`. Proposed:
- `ts_offers` — id, product_id, type (`qty_discount`|`bundle`|`gift`|`promo_price`),
  label, config JSON (tiers, bundle items, gift product), starts_at, ends_at,
  status, sort_order.
- `ts_suppliers` — id, name, contact fields, notes, status. (Fields beyond
  name are optional; real set confirmed with you.)
- `ts_purchases` — id, supplier_id, product_id/variation_id, qty, unit_cost,
  purchased_at, reference, notes.
- `ts_stock_movements` — id, product_id, variation_id, delta, reason, order_id,
  created_by, created_at (audit trail; WC stock stays authoritative).
- `ts_delivery_zones` / `ts_delivery_rates` / `ts_pickup_points` — structure
  only; **contents are configuration entered by admin**, not seeded by us.

Private CPT: `ts_form` (form definition: ordered fields, labels, required,
type, options; no pricing).

## 3.3 Order data (WC order, HPOS-safe, via `$order->update_meta_data`)
| Key | Purpose |
|---|---|
| `_ts_source` | `landing_page` |
| `_ts_template` | template used at order time |
| `_ts_offer_snapshot` | JSON: offer id/type/label/params applied + computed discount |
| `_ts_options_snapshot` | selected options (variation/attributes) |
| `_ts_delivery_snapshot` | method (home/pickup), zone, pickup point, fee applied |
| `_ts_unit_cost_snapshot` | purchase cost at order time (admin-only; enables margin reports) |
| `_ts_form_snapshot` | extra custom field answers (label+value) |

Snapshots mean later edits to offers/costs never rewrite history.
Billing/shipping name, phone, wilaya/commune/address → native WC address fields
(customer-facing data stays in WC core fields).
Order items: WC line items; free gifts added as zero-price line items flagged
with item meta `_ts_gift`; discounts applied as line subtotal/total difference
(or negative fee, to be decided in Phase 4 after testing WC reporting).

## 3.4 Uninstall policy
Deactivation removes nothing. Uninstall removes data only if an explicit
"delete data on uninstall" setting is on.
