# DESIGN_SYSTEM — Top Solution (UI/UX)

Status: DRAFT for approval. Design only; no backend. All Arabic copy in these
documents is **placeholder** (clearly marked) — real copy, logo, photography
and prices come from the owner. No real products/prices are implied.

## 1. Direction
Modern minimalist interior-design store: white canvas, quiet light-gray
surfaces, natural wood as the single warm accent, generous whitespace,
large product photography, restrained motion. Trust and clarity first
(COD market: reassurance about delivery, payment on receipt, returns).

## 2. Design tokens (CSS custom properties, defined once in child theme;
plugin templates consume them)
### Colour
| Token | Value (proposal) | Use |
|---|---|---|
| `--ts-bg` | `#FFFFFF` | page background |
| `--ts-surface` | `#F5F5F4` | cards, sections, inputs bg |
| `--ts-surface-2` | `#ECECEA` | dividers, hover, disabled |
| `--ts-ink` | `#1F1F1D` | primary text |
| `--ts-ink-2` | `#5B5B57` | secondary text |
| `--ts-wood` | `#B98B5E` | accent (oak/walnut tone), icons, highlights |
| `--ts-wood-dark` | `#8A5F37` | primary buttons, links, focus (AA with white) |
| `--ts-wood-tint` | `#F3E9DE` | selected state, badges bg |
| `--ts-success` | `#2F7D4F` | in stock, confirmations |
| `--ts-warn` | `#B26B00` | supplier-sourced, low stock |
| `--ts-danger` | `#B3261E` | errors, unavailable |
| `--ts-line` | `#E2E2DF` | borders |
Wood texture: optional subtle grain image used ONLY in hero strips/footers
(≤ 30 KB, CSS background, decorative). Colours must be verified with the
owner's logo/brand (Q12 in PROJECT_SPEC). Contrast: body text ≥ 4.5:1,
large text/UI ≥ 3:1; `--ts-wood-dark` on white passes AA for text/buttons.
Dark mode: not in release 1.

### Typography (Arabic-first)
Family proposal (open-licence, self-hosted, subsetted Arabic + Latin digits):
headings **Noto Kufi Arabic** (or IBM Plex Sans Arabic), body **IBM Plex Sans
Arabic** (alt: Tajawal/Cairo). Fallback `system-ui, "Segoe UI", Tahoma, sans-serif`.
`font-display: swap`; preload 2 weights max (400, 700).
Numerals: Western digits (0-9) vs Arabic-Indic: **decision Q** — default
Western digits for prices (Algerian retail norm, to confirm).
Scale (mobile → desktop, line-height generous for Arabic ≥ 1.7 body):
| Style | Mobile | Desktop | Weight |
|---|---|---|---|
| Display | 30/40 | 48/60 | 700 |
| H1 | 26/36 | 36/48 | 700 |
| H2 | 22/32 | 28/40 | 700 |
| H3 | 18/28 | 20/30 | 600 |
| Body | 16/28 | 17/30 | 400 |
| Small | 14/22 | 14/22 | 400 |
| Price | 22/28 | 28/34 | 700 (tabular nums) |
Never letter-space Arabic; avoid italics; avoid justify.

### Spacing, radius, elevation, motion
Spacing 4-pt scale: 4, 8, 12, 16, 24, 32, 48, 64, 96. Section padding 48
mobile / 96 desktop. Radius: 8 (inputs/cards), 12 (large cards), 999
(chips). Elevation: borders first; shadow `0 2px 12px rgba(0,0,0,.06)` only
for floating (sticky bar, modal, dropdown). Motion 150–250 ms ease-out;
respect `prefers-reduced-motion`.

## 3. RTL rules
`<html lang="ar" dir="rtl">`. Use **logical CSS** (`margin-inline-start`,
`inset-inline-end`, `text-align:start`), flex/grid natural order, no
hard-coded left/right. Icons that imply direction (arrows, chevrons, back,
carousel, progress) are mirrored; media controls, logos, phone numbers
(`dir="ltr"` isolate), product codes and Latin text are not. Numbers/prices
use `<bdi>`/`unicode-bidi: isolate`. Currency written after the number:
`12 000 د.ج` (format via WC settings). Carousels scroll-snap with RTL
start. Form labels above fields, right-aligned. Admin screens: WP admin
already RTL when locale ar; our CSS uses logical properties and tests in
both directions.

## 4. Layout & responsive rules (mobile-first)
Breakpoints: `sm 480` · `md 768` · `lg 1024` · `xl 1280`. Container max
1200 (content), 720 (reading/forms), gutters 16 mobile / 24 tablet / 32
desktop. Grid: 4 cols mobile, 8 tablet, 12 desktop.
Rules:
- Single column < 768; product grids 2 cols (mobile) → 3 (md) → 4 (lg).
- Tap targets ≥ 44×44 px; spacing ≥ 8 px between targets.
- Sticky bottom CTA bar on mobile landing pages (height 64 + safe-area inset).
- Header: mobile = logo + search icon + menu icon; desktop = logo, nav,
  search, phone/WhatsApp (only if owner provides number).
- Images: aspect ratios fixed (product 1:1 or 4:5, hero 4:5 mobile / 16:9
  desktop) to prevent CLS; `srcset`; lazy-load below fold.
- No horizontal scroll at 320 px width; test 320, 360, 390, 768, 1024, 1440.
- Forms never rely on hover; inline validation on blur; error summary on
  submit; numeric keyboards for phone/qty (`inputmode`).
Performance budget per page: ≤ 150 KB CSS+JS gz (landing ≤ 60 KB JS-free
critical path), fonts ≤ 2 files, hero image preloaded.

## 5. Accessibility
WCAG 2.2 AA target: visible focus ring (2 px `--ts-wood-dark` + 2 px white
offset), semantic landmarks, one H1, labels bound to inputs, error text
with `aria-describedby`, `aria-live` for price updates, keyboard operable
selectors/accordions/modals (focus trap), alt text from media library,
reduced-motion support, no colour-only status (icon + text).

## 6. Component library (storefront)
Each component: purpose · anatomy · states · responsive · a11y.
| Component | Anatomy / notes |
|---|---|
| **Button** | Primary (wood-dark fill, white text), Secondary (outline), Ghost, Whatsapp/Call (only if numbers provided). Sizes: M 48h, L 56h (landing CTA). States: default/hover/focus/active/disabled/loading (spinner, label kept). Full width on mobile for primary CTAs. |
| **Input / Select / Textarea** | 48h, `--ts-surface` bg + 1px line, label above, helper text, error state (danger border + icon + message), RTL caret. |
| **Choice card (radio/checkbox)** | Large tappable card, wood-tint when selected, check icon; used for delivery method, offers, options. |
| **Quantity stepper** | − 1 + with bounds, numeric input between, 44px buttons. |
| **Option swatch/chips** | Colour swatches (circle 40px, ring when selected, name on select), size/finish chips; unavailable = diagonal strike + aria-disabled. |
| **Price block** | Final price (large), old price struck-through, savings badge "وفّر …" computed by server data; tabular numerals; `aria-live`. |
| **Offer card** | Radio card: label, tier description, price per unit, total, badge (Best value) only when computed; selected = wood-tint + border. Layouts: cards (grid) / list. |
| **Badge/Tag** | Neutral, wood, success, warn, danger. Text always present. |
| **Product card** | Image (1:1, hover second image desktop), title (2 lines clamp), price, availability tag, quick CTA "اطلب الآن" optional. Entire card = link. |
| **Category tile** | Image + label overlay on gradient; 2-col mobile. |
| **Gallery** | Mobile: swipe carousel with dots + counter, tap → lightbox. Desktop: main + thumbnails (vertical, inline-start side). |
| **Accordion / FAQ** | Button headers, chevron mirrored, one-open or multi-open setting. |
| **Trust bar** | 3–4 icon+text items (payment on delivery, delivery, returns, support) — content from owner. |
| **Sticky CTA bar** | Mobile bottom: price + primary "اطلب الآن", appears after hero scroll; opens form (scroll to inline or modal per config). |
| **Order form (modal / inline / sticky-bar sheet)** | See STOREFRONT §Landing; sections: summary, fields, delivery, offers, total, submit. |
| **Order summary** | Lines (thumb, name, options, qty), gifts, discount, delivery, total "المبلغ عند الاستلام". |
| **Stepper (progress)** | Used for tracking timeline (RTL: progress flows right→left). |
| **Alert / Toast / Empty state** | Icon + text + action; polite live regions. |
| **Breadcrumb** | Chevron mirrored; collapsed on mobile (back link). |
| **Header / Footer / Mobile menu drawer** | Drawer opens from inline-start; footer: links, policies, contact (owner data), payment-on-delivery note. |
| **Pagination / Load more** | "عرض المزيد" button (default) with URL param for SEO. |
| **Filter sheet** | Mobile bottom sheet; desktop sidebar (inline-start). |

## 7. Admin design language (WordPress admin)
Native WP admin look is kept (no re-skinning of core), our screens use
WP components + a light layer: same palette tokens, 8-pt spacing, cards on
`#F0F0F1` WP bg, wood-dark for primary buttons on our screens only. RTL
tested. Patterns: page header (title + primary action), tab bar (WP
`nav-tab` style), filter bar, table lists (WP_List_Table look) with row
actions, two-column editors (main + sidebar summary), drawers for
secondary edits, inline validation, unsaved-changes guard, toasts
("Saved"), skeleton loaders, empty states with a CTA. Destructive actions
need confirm with consequence text. Permission-gated elements are absent
(not just disabled) when capability missing (cost fields).
Admin components: Tabs, Toggle, Section card (drag handle ⠿, toggle,
expand), Drag list, Repeater, Media picker, Rich-text mini editor, Token
chips, Tier table editor, Rule builder (condition rows), Field panel,
Matrix grid (rates), Stat tile, Chart (simple, accessible, table fallback),
Status pill, Bulk action bar, Import wizard (upload → map → dry-run → commit).

## 8. Content/voice
Arabic (Modern Standard, simple, friendly, direct). Imperative CTAs
("اطلب الآن"). Never invent claims (discounts, scarcity, warranties,
delivery times). Urgency elements (countdown, "x left") only when backed by
real offer end-date / stock data from the system.
