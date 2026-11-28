# DATABASE_SCHEMA

Rule: native WordPress/WooCommerce storage first. A custom table is allowed
only with written justification (§5). All custom keys use prefix `ts_`/`_ts_`.
Schema is illustrative-design only (no SQL executed yet); column lists are
final-candidate and subject to approval.

## 1. WooCommerce native data used
| Data | Where |
|---|---|
| Products, variations, price, sale price + schedule, stock, attributes, images | WC product CRUD |
| Orders, items, shipping line, fees, customer address, status, notes | WC order CRUD (HPOS or legacy) |
| Payment method | `cod` gateway |
| Currency/decimals | WC settings |

## 2. Product meta (`_ts_*`, via WC CRUD `update_meta_data`)
| Key | Type | Meaning |
|---|---|---|
| `_ts_lp_enabled` | `yes/no` | Use landing renderer |
| `_ts_lp_template` | string | template id (`standard`,`promo`,`premium`) |
| `_ts_lp_config` | JSON | published config (see LANDING_PAGE_SYSTEM §5) |
| `_ts_lp_config_draft` | JSON | draft for preview |
| `_ts_lp_version` | int | bump on save, used for cache keys |
| `_ts_form_id` | int | referenced `ts_form` |
| `_ts_offer_ids` | JSON array | referenced `ts_offer` ids, ordered |
| `_ts_availability` | string | `in_stock`,`from_supplier`,`unavailable` (names TBD) |
| `_ts_lead_time_text` | string | only if owner supplies wording |
| `_ts_supplier_id` | int | default supplier (cost cap) |
| `_ts_purchase_cost` | decimal | current cost (cost cap, never exposed) |
| `_ts_is_gift_only` | `yes/no` | hidden gift product |
| `_ts_option_defs` | JSON | non-priced custom options (FORM_BUILDER §7) |
Variations carry `_ts_purchase_cost`, `_ts_availability` where they differ.

## 3. Private custom post types (native storage)
All: `public=false`, `show_ui=false` (custom admin screens), `show_in_rest=false`,
`supports=title`. Config in post meta as validated JSON.
| CPT | Meta keys |
|---|---|
| `ts_offer` | `_ts_type`, `_ts_status`, `_ts_priority`, `_ts_group`, `_ts_exclusive`, `_ts_starts_at`, `_ts_ends_at`, `_ts_product_scope` (JSON: product ids / category ids / all), `_ts_conditions` (JSON), `_ts_actions` (JSON), `_ts_version` |
| `ts_form` | `_ts_fields` (JSON ordered list), `_ts_settings` (JSON), `_ts_version` |
| `ts_supplier` | `_ts_contact` (JSON, admin only), `_ts_notes`, `_ts_status` |
Reason for CPTs: low-volume config entities, revisions/trash/export for free,
no migrations. Lookup "offers for product" via a product-side id list
(`_ts_offer_ids`) plus global-scope offers via a small cached index option
(`ts_offer_index`, not autoloaded).

## 4. Order data (HPOS-safe, `$order->update_meta_data`)
Order-level:
| Key | Content |
|---|---|
| `_ts_source` | `landing_page` |
| `_ts_template` | template id at order time |
| `_ts_form_id`, `_ts_form_version` | form used |
| `_ts_form_answers` | JSON: [{key,label,type,value}] for non-core fields |
| `_ts_pricing_snapshot` | JSON: see §4.1 |
| `_ts_delivery_snapshot` | JSON: method, carrier, zone, geo ids/names, fee, rate id/version |
| `_ts_idempotency_key` | string, unique-checked |
| `_ts_client_meta` | JSON: hashed IP, UA family (privacy-minimised), submit timestamp |
| `_ts_flags` | JSON: duplicate-suspect, spam-score |
| `_ts_cost_total_snapshot` | decimal (cost cap only) |
Line-item meta: `_ts_offer_id`, `_ts_unit_base_price`, `_ts_unit_final_price`,
`_ts_options` (selected options), `_ts_is_gift` (`1`), `_ts_unit_cost_snapshot`
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
Prefix `{$wpdb->prefix}ts_`; created with `dbDelta`; versioned by `Migrator`
(`ts_db_version` option). Engine InnoDB, utf8mb4.
| Table | Why native storage is insufficient |
|---|---|
| `ts_purchase_lots` | Transactional records aggregated for cost/margin/supplier reports; meta tables cannot be indexed/aggregated efficiently |
| `ts_stock_movements` | Append-only audit ledger, high write volume, per-product time queries |
| `ts_delivery_rates` | Matrix lookup (method × zone/geo × optional weight/qty bands) hit on every submit; needs composite index |
| `ts_geo_units` | Administrative hierarchy (wilaya→commune), selectable in forms, hundreds–thousands of rows; parent/child + active flag |
| `ts_submission_log` (optional) | Throttling and idempotency/audit independent of order creation; only if option-based approach proves insufficient (decide in Phase 4) |

### Column designs
`ts_purchase_lots`: id PK, supplier_id, product_id, variation_id, qty,
unit_cost DECIMAL(18,4), currency, purchased_at, reference VARCHAR(100),
note TEXT, created_by, created_at. Idx(product_id, purchased_at), Idx(supplier_id).
`ts_stock_movements`: id PK, product_id, variation_id, delta INT, reason
ENUM-like VARCHAR(30) (`order`,`manual`,`purchase`,`return`,`correction`),
ref_type, ref_id (order id / purchase id), balance_after INT NULL,
user_id, created_at. Idx(product_id, created_at), Idx(ref_type, ref_id).
`ts_delivery_rates` (**as built in Phase 2**): id PK, method (home|office), geo_id
(0 = default for the method), fee DECIMAL(18,4), active (0 = not served), updated_at.
Unique(method, geo_id). Zones, carriers per rate and weight/quantity bands from the
original design are deferred (see docs/PLUGIN_PHASE2.md).
`ts_pickup_points` (added in Phase 2): id, geo_id (commune or wilaya), carrier,
name, address, hours, active. Index (geo_id, active).
Methods, carriers, zones, pickup points: small config → options/CPT
(`ts_delivery_*` JSON options, not autoloaded) unless Phase 3 shows need.
`ts_geo_units`: id PK, parent_id, level (`wilaya`/`commune`), code,
name_ar, active, source_version. Idx(parent_id, level). Contents = import from
a source approved by owner (Q8); not shipped invented.
Idempotency keys (primary mechanism): atomic `add_option()` row
(`ts_idem_<hash>`, autoload=no) — unique `option_name` gives atomic insert;
cleanup via daily cron.

## 6. Options (wp_options)
`ts_settings` (autoload yes, small), `ts_delivery_config` (no autoload),
`ts_offer_index` (no autoload), `ts_db_version`, `ts_delete_data_on_uninstall`.

## 7. Data lifecycle & privacy
Deactivate: nothing removed. Uninstall: removed only if flag set. Personal
data lives in WC orders (WC privacy exporters/erasers apply); `_ts_client_meta`
is hashed/minimised and registered with WP privacy tools. Backups before
migrations; migrations idempotent.
