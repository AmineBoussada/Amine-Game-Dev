# DATABASE_SCHEMA

Rule: native WordPress/WooCommerce storage first. A custom table is allowed
only with written justification (§5). All custom keys use prefix `ds_`/`_ds_`.
Schema is illustrative-design only (no SQL executed yet); column lists are
final-candidate and subject to approval.

## 1. WooCommerce native data used
| Data | Where |
|---|---|
| Products, variations, price, sale price + schedule, stock, attributes, images | WC product CRUD |
| Orders, items, shipping line, fees, customer address, status, notes | WC order CRUD (HPOS or legacy) |
| Payment method | `cod` gateway |
| Currency/decimals | WC settings |

## 2. Product meta (`_ds_*`, via WC CRUD `update_meta_data`)
| Key | Type | Meaning |
|---|---|---|
| `_ds_lp_enabled` | `yes/no` | Use landing renderer |
| `_ds_lp_template` | string | template id (`standard`,`promo`,`premium`) |
| `_ds_lp_config` | JSON | published config (see LANDING_PAGE_SYSTEM §5) |
| `_ds_lp_config_draft` | JSON | draft for preview |
| `_ds_lp_version` | int | bump on save, used for cache keys |
| `_ds_form_id` | int | referenced `ds_form` |
| `_ds_offer_ids` | JSON array | referenced `ds_offer` ids, ordered |
| `_ds_availability` | string | `in_stock`,`from_supplier`,`unavailable` (names TBD) |
| `_ds_lead_time_text` | string | only if owner supplies wording |
| `_ds_supplier_id` | int | default supplier (cost cap) |
| `_ds_purchase_cost` | decimal | current cost (cost cap, never exposed) |
| `_ds_is_gift_only` | `yes/no` | hidden gift product |
| `_ds_option_defs` | JSON | non-priced custom options (FORM_BUILDER §7) |
Variations carry `_ds_purchase_cost`, `_ds_availability` where they differ.

## 3. Private custom post types (native storage)
All: `public=false`, `show_ui=false` (custom admin screens), `show_in_rest=false`,
`supports=title`. Config in post meta as validated JSON.
| CPT | Meta keys |
|---|---|
| `ds_offer` | `_ds_type`, `_ds_status`, `_ds_priority`, `_ds_group`, `_ds_exclusive`, `_ds_starts_at`, `_ds_ends_at`, `_ds_product_scope` (JSON: product ids / category ids / all), `_ds_conditions` (JSON), `_ds_actions` (JSON), `_ds_version` |
| `ds_form` | `_ds_fields` (JSON ordered list), `_ds_settings` (JSON), `_ds_version` |
| `ds_supplier` | `_ds_contact` (JSON, admin only), `_ds_notes`, `_ds_status` |
Reason for CPTs: low-volume config entities, revisions/trash/export for free,
no migrations. Lookup "offers for product" via a product-side id list
(`_ds_offer_ids`) plus global-scope offers via a small cached index option
(`ds_offer_index`, not autoloaded).

## 4. Order data (HPOS-safe, `$order->update_meta_data`)
Order-level:
| Key | Content |
|---|---|
| `_ds_source` | `landing_page` |
| `_ds_template` | template id at order time |
| `_ds_form_id`, `_ds_form_version` | form used |
| `_ds_form_answers` | JSON: [{key,label,type,value}] for non-core fields |
| `_ds_pricing_snapshot` | JSON: see §4.1 |
| `_ds_delivery_snapshot` | JSON: method, carrier, zone, geo ids/names, fee, rate id/version |
| `_ds_idempotency_key` | string, unique-checked |
| `_ds_client_meta` | JSON: hashed IP, UA family (privacy-minimised), submit timestamp |
| `_ds_flags` | JSON: duplicate-suspect, spam-score |
| `_ds_cost_total_snapshot` | decimal (cost cap only) |
Line-item meta: `_ds_offer_id`, `_ds_unit_base_price`, `_ds_unit_final_price`,
`_ds_options` (selected options), `_ds_is_gift` (`1`), `_ds_unit_cost_snapshot`
(cost cap only; hidden via `woocommerce_hidden_order_itemmeta`).
### 4.1 Pricing snapshot (JSON)
```
{ "engine_version":"1", "currency":"DZD", "decimals":N,
  "lines":[{ "product_id","variation_id","qty","base_unit","sale_unit_applied",
             "applied":[{"offer_id","type","label","effect","amount"}],
             "final_unit","line_total" }],
  "gifts":[{"product_id","variation_id","qty","offer_id"}],
  "subtotal","discount_total","delivery_fee","grand_total",
  "rejected":[{"offer_id","reason"}] }
```
Costs are never placed in this snapshot; they live only in the cost-cap meta.

## 5. Custom tables (justified)
Prefix `{$wpdb->prefix}ds_`; created with `dbDelta`; versioned by `Migrator`
(`ds_db_version` option). Engine InnoDB, utf8mb4.
| Table | Why native storage is insufficient |
|---|---|
| `ds_purchase_lots` | Transactional records aggregated for cost/margin/supplier reports; meta tables cannot be indexed/aggregated efficiently |
| `ds_stock_movements` | Append-only audit ledger, high write volume, per-product time queries |
| `ds_delivery_rates` | Matrix lookup (method × zone/geo × optional weight/qty bands) hit on every submit; needs composite index |
| `ds_geo_units` | Administrative hierarchy (wilaya→commune), selectable in forms, hundreds–thousands of rows; parent/child + active flag |
| `ds_submission_log` (optional) | Throttling and idempotency/audit independent of order creation; only if option-based approach proves insufficient (decide in Phase 4) |

### Column designs
`ds_purchase_lots`: id PK, supplier_id, product_id, variation_id, qty,
unit_cost DECIMAL(18,4), currency, purchased_at, reference VARCHAR(100),
note TEXT, created_by, created_at. Idx(product_id, purchased_at), Idx(supplier_id).
`ds_stock_movements`: id PK, product_id, variation_id, delta INT, reason
ENUM-like VARCHAR(30) (`order`,`manual`,`purchase`,`return`,`correction`),
ref_type, ref_id (order id / purchase id), balance_after INT NULL,
user_id, created_at. Idx(product_id, created_at), Idx(ref_type, ref_id).
`ds_delivery_rates` (**as built in Phase 2**): id PK, method (home|office), geo_id
(0 = default for the method), fee DECIMAL(18,4), active (0 = not served), updated_at.
Unique(method, geo_id). Zones, carriers per rate and weight/quantity bands from the
original design are deferred (see docs/PLUGIN_PHASE2.md).
`ds_pickup_points` (added in Phase 2): id, geo_id (commune or wilaya), carrier,
name, address, hours, active. Index (geo_id, active).
Methods, carriers, zones, pickup points: small config → options/CPT
(`ds_delivery_*` JSON options, not autoloaded) unless Phase 3 shows need.
`ds_geo_units`: id PK, parent_id, level (`wilaya`/`commune`), code,
name_ar, active, source_version. Idx(parent_id, level). Contents = import from
a source approved by owner (Q8); not shipped invented.
Idempotency keys (primary mechanism): atomic `add_option()` row
(`ds_idem_<hash>`, autoload=no) — unique `option_name` gives atomic insert;
cleanup via daily cron.

## 6. Options (wp_options)
`ds_settings` (autoload yes, small), `ds_delivery_config` (no autoload),
`ds_offer_index` (no autoload), `ds_db_version`, `ds_delete_data_on_uninstall`.

## 7. Data lifecycle & privacy
Deactivate: nothing removed. Uninstall: removed only if flag set. Personal
data lives in WC orders (WC privacy exporters/erasers apply); `_ds_client_meta`
is hashed/minimised and registered with WP privacy tools. Backups before
migrations; migrations idempotent.
