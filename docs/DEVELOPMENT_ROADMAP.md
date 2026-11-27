# DEVELOPMENT_ROADMAP

Method: planning first, then phases; each phase ends with a report
(completed work, files created/modified, technical decisions, unresolved
issues, tests run) and **waits for explicit approval**. Nothing is
implemented until this documentation set is approved.

| Phase | Name | Deliverables | Exit tests |
|---|---|---|---|
| 0 | Planning | These 10 documents + answers to open questions | Approval |
| 1 | Foundation | Plugin skeleton, Composer/PSR-4, requirements check, HPOS declaration, Migrator, capabilities, settings framework, Logger, child theme (RTL, Arabic font, tokens), PHPCS/PHPStan/PHPUnit/CI setup, local WP+WC test env | Activation/deactivation, lint, HPOS on/off boot |
| 2 | Geography & Delivery | `ts_geo_units`, import tool, methods/carriers/zones/rates/pickup, RateCalculator, WC ShippingMethod, Delivery admin | Unit tests rates; coverage report |
| 3 | Forms | `ts_form`, field registry, builder UI, validators, renderer, dependent selects | Validation matrix; RTL render |
| 4 | Offers | `ts_offer`, OfferEngine, resolver, simulator, DisplayProvider | Table-driven + property tests |
| 5 | Orders | CheckoutController, tokens, idempotency, PricingService, OrderFactory, confirmation page, order admin meta box, anti-spam | Tamper/race/HPOS tests; manual COD walk-through |
| 6 | Landing pages | Registries, 3 templates, sections, product data tab, draft/preview, cache | Template contract + visual/RTL/mobile tests |
| 7 | Inventory & Suppliers | Availability, ledger, pending-supplier queue, suppliers, purchases, costs, CostGuard | Cost-leak tests; stock tests |
| 8 | Reports & Dashboard | Report queries/screens, CSV, dashboard | Data verification vs raw orders |
| 9 | Hardening & Launch | Security review, performance pass, plugin-compat checklist, staging UAT, docs, backup/rollback plan | UAT sign-off, go-live checklist |
Order note: Phases 5 and 6 may swap if you want visual progress earlier;
Phase 5 requires 2–4 complete (it consumes delivery, forms, offers).

## Cross-phase rules
Branch per phase, small commits, changelog, no core edits, i18n strings
from day one, PHPDoc, tests written with each module, migrations idempotent.

## Dependencies / prerequisites from owner
Answers to PROJECT_SPEC §9; hosting/staging access; brand assets & Arabic
copy; delivery data; approved geography source; real sample products for
testing (or approval to use clearly labelled placeholder data in dev only).

## Risks to schedule
Delivery data availability (blocks Phase 2 verification); geography source;
offer-policy decisions (blocks Phase 4 defaults); CAPTCHA choice; hosting
caching layer behaviour (validated in Phase 9).

## Definition of done (per feature)
Spec'd behaviour implemented; server-side validation; capability + nonce
checks; escaped output; i18n; tests green (incl. HPOS where order-related);
no cost leakage; docs updated; phase report delivered.
