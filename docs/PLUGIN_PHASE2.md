# Top Solution Core — Phase 2 (delivery and geography)

Version 0.2.0 · database version 2 · ZIP: `dist/top-solution-core.zip`.
**No real data is shipped.** Wilayas, communes, prices and pickup offices are
entered or imported by the owner; the plugin contains none. Tests use
fictional data only ("ولاية تجريبية").

## What it does (Top Solution > Delivery, capability `ts_manage_delivery`)
| Tab | Function |
|---|---|
| Settings | Turn home delivery / delivery-office pickup on or off, customer-facing labels, optional free-delivery threshold. One-click WooCommerce shipping zone "Algeria" using the method. |
| Wilayas and communes | CSV import (codes + Arabic names, French optional, source label), list with commune counts and WooCommerce state code, switch a wilaya off/on. |
| Prices | Grid: one row per wilaya plus a default row, one column per method. Empty = no price at this level; "not served" = no delivery even if a default exists; 0 = free. Commune prices via CSV. Report of active wilayas with no price. |
| Delivery offices | CSV import (wilaya, commune, carrier, name, address, hours), switch off, delete. |
| Price preview | Pick wilaya, commune, subtotal → price per method with the reason, and the offices for that area. Read-only. |

## Price rules (`RateCalculator`, unit-tested)
1. Method must be switched on; the wilaya (and commune, if chosen) must be active.
2. Look up in order: **commune price → wilaya price → default price**. The first one found decides; a "not served" entry blocks the broader levels.
3. Nothing found → **not available** (a price is never guessed).
4. Free-delivery threshold: subtotal ≥ threshold → 0, only for areas that are served.
5. A commune sent with a wilaya it does not belong to is refused (`invalid_area`), so a tampered request cannot get another area's price.
Amounts are integers in minor units (store decimals), never floats; Arabic-Indic digits and spaces are accepted on input.

## Imports
CSV, UTF-8, `,` or `;`, max 2 MB / 20 000 rows. Every import shows a **dry run first** (new / updated / rejected lines with reasons); the confirmed import applies exactly the previewed rows, kept on the server (the browser cannot change them between preview and confirm). Re-importing the same file updates, never duplicates. A commune code cannot be moved to another wilaya by import.

## WooCommerce
Shipping method `ts_delivery` ("Top Solution — التوصيل"), one rate per available method, cost from `DeliveryService`. Wilaya ↔ WooCommerce state: `DZ-01` … `DZ-58`. A wilaya numbered above 58 has no WooCommerce state code; the admin shows a warning. This matters only for the standard WooCommerce cart, not for landing pages (Phase 5 uses `DeliveryService` directly). The city typed in a WooCommerce address is matched to a commune by name; no match → wilaya price.

## Data (custom tables, justified in DATABASE_SCHEMA)
`ts_geo_units` (wilaya/commune, code, Arabic/French name, active, source), `ts_delivery_rates` (method, geo id or 0 for default, fee, active), `ts_pickup_points`. Created by migration 2 (`dbDelta`), dropped only by uninstall with "delete data" ticked.

## Simplifications versus the original spec (deliberate, can be added later)
- No separate "zone" or "carrier" entities: prices are per method and area; the carrier is a text field on offices. Add carriers per price only if you work with several companies at different prices.
- No weight / quantity bands (open question Q7).
- No carrier API integration (out of release 1).

## Tests
- Unit (PHPUnit, 103 total in the project): `MoneyTest`, `GeoCodeTest`, `RateCalculatorTest`, `CsvImporterTest`, `DeliverySettingsTest`.
- `tests/plugin/delivery-integration.php` (29 checks): migration, dry run vs real import, normalised codes, price resolution levels, not served, tampered commune, inactive wilaya, disabled method, free threshold, offices per area, WooCommerce zone (idempotent), WooCommerce rates for real packages.
- `tests/plugin/delivery-ui.js` (42 checks, re-runnable): every tab, settings validation, import preview/confirm, wilaya on/off, price grid (invalid amounts, Arabic-Indic digits, "not served" + ignored-price warning), preview, offices, roles (shop manager allowed, subscriber 403, forged POST 403), phone width.
- Upgrade test: 0.1.0 → 0.2.0 through the WordPress uploader keeps settings and creates the tables.

## Needed from the owner
- The official wilaya / commune list to import (source you approve).
- Delivery companies, prices per wilaya (and communes where different), delivery offices.
- Free-delivery rule, if any.
