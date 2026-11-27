# 07 — Security & Quality

## Server-side validation
- Every request: nonce + capability (admin) or nonce + rate limit (public).
- Prices, discounts, fees, totals: computed only server-side; submitted
  values for these are ignored/rejected.
- IDs must resolve to real, purchasable, allowed objects.
- Sanitise on input (`sanitize_text_field`, `absint`, `wc_clean`), escape on
  output, prepared statements (`$wpdb->prepare`) for all custom SQL.
- Anti-spam: honeypot, per-IP/phone throttling, optional CAPTCHA, duplicate
  detection window.

## Cost / supplier confidentiality
Capability `ts_view_costs`; no exposure through templates, REST, emails,
order exports, `get_post_meta` dumps; unit/integration tests assert absence
on public endpoints and customer emails.

## HPOS
Declare compatibility; use only WC CRUD for orders; test with HPOS enabled
(sync on and off) and with legacy storage.

## Core integrity
No edits to WP/WC core or third-party plugins/themes. Overrides only via
hooks/filters and the child theme.

## Code standards
WordPress Coding Standards (PHPCS), PHPStan (level to be agreed), PSR-4,
docblocks, i18n on all strings, no inline secrets, no `eval`/unserialize of
user data (JSON only).

## Testing strategy
- Unit: OfferEngine, PriceCalculator, RateCalculator, Validators (PHPUnit).
- Integration (WP test suite + WooCommerce): order creation, stock, HPOS,
  capability checks, cost non-leak.
- E2E (Playwright): landing page render, RTL, order submission, confirmation.
- Manual QA checklist per phase, mobile RTL.
Environment: a local WordPress+WooCommerce instance is required to run
integration/E2E tests; **availability in this cloud environment is unverified**
and will be checked at the start of Phase 1.

## Operational
Logging via `WC_Logger` (no personal data in logs), backups before DB
migrations, versioned `Migrator`, staging before production.
