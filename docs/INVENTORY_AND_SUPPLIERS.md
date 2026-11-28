# INVENTORY_AND_SUPPLIERS

## 1. Inventory model
- WooCommerce stock is the authority for physical quantities
  (manage stock per product/variation).
- Availability state per product/variation (`_ds_availability`):
  `in_stock`, `from_supplier` (sourced after order), `unavailable`.
  Mapping with WC stock status is synchronised by `AvailabilityService`
  (e.g. `from_supplier` ⇒ WC "on backorder"-like behaviour allowing orders,
  but kept distinct in our logic and UI).
- `ds_stock_movements` ledger: every stock-affecting event records delta +
  reason + reference; writes happen from hooks on WC stock changes
  (`woocommerce_product_set_stock`, `woocommerce_variation_set_stock`,
  order stock reduce/restore) and manual adjustments in our UI (capability
  `ds_manage_inventory`). Ledger is informational/audit; never the source
  for storefront availability.
- Low-stock threshold uses WC setting per product with a dashboard widget.
- Reconciliation tool (Tools tab): compare ledger running balance vs WC stock,
  report drift (no auto-fix).

## 2. Supplier-sourced orders ("needs purchase" queue)
Orders containing `from_supplier` lines get order meta flag
`_ds_needs_purchase`. Inventory → "Pending supplier items" screen lists
lines awaiting purchase (grouped by supplier). Actions: create purchase lot
(qty, unit cost, supplier, reference) → marks lines as purchased/received;
on receipt optionally increases WC stock. Statuses of procurement per line:
`pending`, `ordered`, `received`, `cancelled` (item meta `_ds_proc_status`).
Whether procurement affects WC order statuses (e.g. custom status) is a
business decision (Q16); default: separate procurement flag, order status
untouched.

## 3. Suppliers
CPT `ds_supplier` (name, contact JSON, notes, status). No supplier data is
seeded. Product ↔ default supplier (`_ds_supplier_id`); a product may have
multiple historical suppliers via purchase lots. Supplier screens require
`ds_manage_suppliers`; contact/financial details are admin-only.

## 4. Purchase costs
- `ds_purchase_lots` stores each purchase with unit cost.
- Current cost per product/variation: `_ds_purchase_cost` set manually or
  derived by a setting: `manual` (default) | `last_purchase` | `weighted_average`
  (owner picks, Q15). Derived value recalculated on lot insert.
- At order time, unit cost snapshot saved to order/line meta (cost cap) →
  margin report independent of later cost changes.
- Margin definition = line revenue − unit cost × qty (− delivery cost if owner
  includes it; delivery cost data requires its own input, Q15).

## 5. Cost protection requirements (enforced + tested)
1. Capability `ds_view_costs` (administrator only by default).
2. Stored only in underscore meta / custom tables / CPT with
   `show_in_rest=false`; never in WC REST `meta_data`, never in
   storefront/templates, emails, order notes, invoices, CSV exports without
   the capability, or the cached landing fragments.
3. Admin UI hides cost fields/columns/tabs without capability (also blocked
   server-side on save/query, not just hidden).
4. Hooks `woocommerce_hidden_order_itemmeta` and REST filters strip cost keys.
5. Automated leak tests: public product page HTML, REST product/order
   responses for non-cost users, customer emails, confirmation page.
6. Audit: cost changes logged (user, time, old→new) in ledger-like note.

## 6. Reports (inventory/supplier related)
Stock on hand vs availability, low stock, pending supplier items, purchases
by supplier/period, average cost, margin by product (cost cap).

## 7. Edge cases
Returns of COD refused deliveries restore stock (WC native on cancel) and
ledger entry `return`; variation-level costs; partial receipt of a purchase;
product price below cost warning (admin only); deleted supplier with history
(soft-archive only).
