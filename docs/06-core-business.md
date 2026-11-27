# 06 — Core Business Management

## Inventory & availability
- WooCommerce stock is authoritative for physical stock.
- Availability states per product/variation (names to confirm):
  in stock · available from supplier (lead time) · unavailable.
  "From supplier" products accept orders without stock; lead-time text shown
  only if you provide it.
- `ts_stock_movements` audit log for manual adjustments, order reductions,
  purchase receipts.
- Low-stock view in admin.

## Suppliers & purchase costs
- Supplier CRUD (admin-only screen). Product ↔ supplier link; purchase
  records with unit cost and date.
- Current purchase cost per product/variation; snapshot onto orders.
- **Protection**: custom capability `ts_view_costs` (default: administrator
  only; **not** shop_manager unless you want). Data lives in underscore
  meta/custom tables, never in REST responses, storefront output, emails,
  exports, or order notes visible to customers. Covered by tests (07).

## Delivery
- Configuration screens: delivery methods (home / pickup), zones, rates,
  pickup points. **Empty on install** — you provide real companies, zones
  and prices. Rate rules: by zone (and optionally weight/qty if you need).
- Server-side `RateCalculator`; result stored as order snapshot and as a WC
  shipping line (so WC reports include it).
- Possible free-delivery threshold per offer (only if you want it).

## Reporting (admin, capability-gated)
Phase-gated, built on WC order data + snapshots:
sales by period/product/template/offer · offer performance · orders by
wilaya/delivery type · stock/availability · margin (price − snapshot cost −
delivery cost if relevant) · supplier purchase totals.
Queries via WC data stores / HPOS tables (`wc_get_orders`, `OrdersTableQuery`),
never raw postmeta. Export CSV for admins only.
