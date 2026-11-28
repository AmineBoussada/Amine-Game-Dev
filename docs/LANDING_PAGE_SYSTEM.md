# LANDING_PAGE_SYSTEM

## 1. Principles (decision A)
- Dedicated rendering system with predefined PHP templates; not page-builder
  documents per product.
- Each product stores selected template + configuration (`_ts_lp_*`).
- Templates are presentation-only and independent from order/offer logic.
- Elementor **Free** remains for general site pages. Landing pages, the order
  form (including its modal / sticky-bar modes) never use Elementor Pro
  (no Popup Builder, no Pro forms, no Theme Builder).

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

## 10. Template `longform` — long-form COD product page (requested 2026-11-28)
Reference: the common Algerian COD landing pattern (owner-supplied
screenshots). Structure only is reused; no third-party brand, images or claims.
Prototype: `docs/design/prototype/product-longform.html`.

**Colours: brand palette only** — Everglade `#014749` (primary),
Whirlpool `#395C7A` (secondary), Aqua Cove `#53978A` (decoration), Cornfield
`#C88928` (highlight, dark text only), Sisal Beige `#C0976B` (warm neutral,
used as tints), White. No off-palette fills; text ink and form-feedback
colours are the only additions (see `docs/design/DESIGN_SYSTEM.md`). The page
is a single light theme so the palette never shifts. **No countdown /
Black-Friday style urgency bar** (urgency only from real offer data, per §3).

Section order (each section: show/hide + reorder per product, §4–§6):
1. Header: menu · centred logo · search (theme header, compact variant).
2. Reviews summary strip (rating + count) — **only when real reviews exist**.
3. Hero: headline + main creative (image upload; before/after optional).
4. **Order form near the top** (Forms module): name, phone, wilaya,
   commune, delivery method (home / office), quantity offers, product line
   with thumbnail and server-computed price, totals, submit "إتمام الطلب".
5. Pain points (circular images + caption, repeater).
6. Benefits list (check items, repeater).
7. Materials / specifications (icon tiles + spec table from WC attributes).
8. Before / after pairs (image pairs + caption, repeater).
9. Mid-page CTA band (scrolls to the form).
10. How ordering works (4 steps: order, confirmation, delivery, pay).
11. Trust badges (COD, home delivery, office pickup).
12. FAQ accordion (repeater).
13. Footer (theme footer).
Persistent: sticky bottom bar "اطلب الآن — الدفع عند الاستلام" (hidden while
the form is on screen; tapping scrolls to the form and focuses the name
field) and a back-to-top button.

**Image-section type:** any section may instead be a full-width uploaded
creative (sellers often design these in external tools): attachment id,
required alt text, responsive `srcset`, lazy-loaded except the first.
Page column max 560 px on desktop (phone-first layout, as in the reference).
