# 04 — Landing Pages

## Concept
A product with `_ts_lp_enabled = true` renders through our plugin template
instead of the default WooCommerce single-product layout (hooked on
`template_include` / `wc_get_template_part` for `single-product` only).
Disabled → untouched native WooCommerce product page.

## Templates (registry-based)
Each template = id, label (Arabic), supported sections, default order,
default config, PHP view, CSS. Registered via `TemplateRegistry` so adding a
4th template needs no core change.

| Template | Intent | Design emphasis (to be confirmed with you) |
|---|---|---|
| `standard` | Standard Product | Clear hero, benefits, gallery, details, order form |
| `promo` | Promotional Offer | Offer/price emphasis, urgency block (only if you provide real end dates), offers inline |
| `premium` | Premium Product | Large imagery, storytelling, spec/finish detail |

## Section catalogue (candidate; final list needs approval)
hero (title, price, gallery from WC) · benefits · description (WC long desc) ·
gallery · specifications (WC attributes) · offers (from Offers module) ·
order form · FAQ · delivery info · return/trust notes · sticky order button.

Each section: id, label, supported-by-templates, `default_visible`,
`fields` schema (typed: text, richtext, image, repeater), renderer.

## Admin editing UX (product edit screen)
Meta box "Top Solution — Landing Page":
1. Enable toggle + template select
2. Sections list: drag-reorder, show/hide switch, "edit content" per section
3. Order form: pick form preset; position (hero / after offers / bottom /
   sticky) and display mode (inline / modal / sticky-bar)
4. Offers: attach/arrange offers (managed in Offers screen)
5. **Preview** button → renders draft config at a tokenised URL
   (`?ts_preview=<nonce-token>`) visible only to users with edit capability;
   publishing saves draft → live (`_ts_lp_config` vs `_ts_lp_config_draft`).

Content comes from WC where possible (title, gallery, price, attributes,
description); landing-specific fields only override/supplement.

## Rules
- Templates output HTML only; **no price math, no form building** — they call
  `Offers::get_display_data($product)` and `Forms::render($form_id, $context)`.
- RTL-first CSS (logical properties), mobile-first (most COD traffic is mobile —
  to be validated with your analytics if you have any).
- Escape all output (`esc_html`, `wp_kses_post` for rich text).
- Performance: no heavy page-builder payload on landing pages; lazy-load images.
