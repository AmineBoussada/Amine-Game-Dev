# LANDING_PAGE_SYSTEM

## 1. Principles (decision A)
- Dedicated rendering system with predefined PHP templates; not page-builder
  documents per product.
- Each product stores selected template + configuration (`_ts_lp_*`).
- Templates are presentation-only and independent from order/offer logic.
- Elementor remains for general site pages.

## 2. Activation
Product with `_ts_lp_enabled=yes` and a valid template → on `template_include`
for `is_singular('product')`, the plugin renders its own template. Else the
native WC single-product page is used (child theme styled). Landing mode is a
per-product switch, so rollout can be gradual. Fallback if config invalid:
native page + admin notice (never a blank page).

## 3. Template architecture
`TemplateRegistry::register( Template $t )` — each template declares:
`id`, Arabic `label`, `supports` (section ids), `default_sections` (ordered),
`slots` (named areas: `hero`, `offers`, `order_form`, `content`, `footer_cta`),
`view` (PHP file), `styles`/`scripts` handles, `defaults` (design tokens
overridable per product: accent colour from approved palette only).
Template contract: receives an immutable `LandingViewModel`; may not query
DB, compute prices or read request data. Slots are filled by modules:
- `offers` slot → `Offers\DisplayProvider` (shows offers, price preview — display
  only, final price recomputed on server)
- `order_form` slot → `Forms\FormRenderer`
- other slots → `SectionRegistry` renderers.
Initial templates (design details with owner, Q12):
| id | Name | Intent |
|---|---|---|
| `standard` | Standard Product | Clean product-led page |
| `promo` | Promotional Offer | Emphasis on offer cards and price; urgency elements only when real dates exist |
| `premium` | Premium Product | Large imagery/storytelling, fewer offers prominence |
Adding a template = new class + view + CSS; no change elsewhere.

## 4. Sections
Each section: `id`, `label`, `fields` (typed schema), `renderer`,
`allowed_templates`, `default_visible`, `data_source` (WC or own).
| Section | Source |
|---|---|
| `hero` | WC title, price (live), gallery, short desc; custom headline override |
| `gallery` | WC gallery |
| `benefits` | repeater (icon, title, text) |
| `description` | WC long description or override |
| `specifications` | WC attributes/dimensions or repeater |
| `offers` | Offers module |
| `order_form` | Forms module |
| `faq` | repeater |
| `delivery_info` | settings-driven text (owner content) |
| `trust` | repeater |
| `video` | URL (allow-listed providers) |
| `sticky_cta` | auto from order_form |
Show/hide + reorder per product within `allowed` rules (`hero` and
`order_form` cannot both be hidden: product must remain orderable unless
availability = unavailable).

## 5. Per-product configuration (`_ts_lp_config`, JSON, schema-validated)
```
{ "schema":1, "template":"standard",
  "sections":[{"id":"hero","visible":true,"data":{...}}, ...],
  "form":{"position":"after_offers|hero|bottom|sticky","display":"inline|modal|sticky_bar",
          "cta_label":"..."},
  "offers":{"layout":"cards|list","preselect":"offer_id|none"},
  "design":{"accent":"token"},
  "seo":{"title":"","description":"","og_image":id} }
```
Form id and offer ids are separate meta (references), not embedded.
Validation: unknown section/keys dropped; rich text via `wp_kses` allow-list;
images by attachment id; URLs allow-listed.

## 6. Admin editing
Product data → "Top Solution" tab: Landing Page (enable/template), Sections
(drag reorder, toggle, edit data), Order Form (form picker, position, display
mode), Offers (attach/order), Preview. Template change keeps shared section
data, drops unsupported sections with a warning. Copy-config-from-product
action for speed.
Draft/publish: edits stay in `_ts_lp_config_draft` until "Publish"; preview
URL token (signed, TTL, capability-bound) renders draft with an admin banner,
`noindex`. Revisions: last published config kept once (rollback).

## 7. SEO & structure
Product structured data kept from WooCommerce (JSON-LD) — landing must not
remove it; page `<h1>` single; canonical = product permalink; compatible
with SEO plugins; RTL `dir`/`lang="ar"`.

## 8. Performance & caching
Fragment cache of rendered sections (key: product id, `_ts_lp_version`,
modified time, offer/form versions, locale). Dynamic parts (price display,
availability) read live from WC or cached with product-invalidation hooks.
Assets only on landing pages; critical CSS inline for hero (optional).

## 9. Testing
Template contract tests (no price/DB access), snapshot HTML tests per
template, RTL visual tests, accessibility checks, config schema fuzzing.
