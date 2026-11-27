# STOREFRONT_SCREENS — 12 screens

Legend: wireframes are mobile (≈390 px) in RTL; blocks listed top→bottom.
`[ ]` = block, `(P)` = placeholder content. Desktop adaptations after each.
Data comes from WooCommerce/system; nothing is invented.

## Scope flags needing your decision (conflicts with the approved plan)
1. **Cart & Checkout (screens 7–8)**: the architecture bypasses cart/checkout
   for landing-page products (single-step COD order). Designed here as a
   **secondary path** (products without a landing page / multi-item) using
   WooCommerce pages. If you want a cart-based flow as primary, the order
   workflow spec changes.
2. **Order tracking (screen 10)**: not in the backend plan (tracking was
   out of release 1). Designed as a **lookup by order number + phone**
   showing order status from WooCommerce statuses; carrier tracking needs
   owner-supplied carrier data/APIs. Needs approval as added scope.

## Global chrome (all pages)
**Header (mobile)**: [menu icon] [logo center] [search icon] · optional
[cart icon + count] only if cart path enabled. Announcement bar above
(optional, owner text, dismissible). **Desktop**: logo, primary nav
(الرئيسية، الأقسام، من نحن، اتصل بنا — placeholders), search field,
contact number if provided.
**Footer**: 4 blocks (brand blurb · shop links · info/policies · contact),
trust note "الدفع عند الاستلام" (placeholder), copyright.
**Sticky elements**: header on scroll-up only (mobile); CTA bar (landing).

---
## 1. Homepage (Elementor-built, theme components)
```
[Announcement bar (opt)]
[Header]
[Hero: large lifestyle image (wood/interior), H1 (P), sub (P), CTA "تسوّق الآن" + secondary]
[Trust bar: ٣–٤ icons]
[Category tiles 2-col grid (WC categories)]
[Featured products carousel/grid (WC featured/ manual)]
[Promo banner (optional): offer product link, wood-tint bg]
[Why Top Solution: 3 benefit cards (owner copy)]
[Customer proof: reviews/photos — ONLY if real content exists]
[Delivery & payment explainer: 3 steps (Order → Confirm → Pay on receipt)]
[FAQ short (3–5)]
[Footer]
```
Desktop: hero split 50/50 (image inline-end), categories 4–6 tiles in a
row, featured 4-col grid. Elementor widgets limited to approved
global-styled blocks (hero, tiles, grid) to preserve identity.
States: empty featured → hidden section.

## 2. Product categories (all categories index)
```
[Breadcrumb/back] [H1 "الأقسام"] [intro (opt)]
[Category tiles 2-col; hierarchy: parent tile → subcategory chips]
```
Desktop 3–4 col. Tile shows product count only if > 0.

## 3. Product listing (category/shop archive)
```
[Breadcrumb] [H1 + count] [Sort ▾] [Filter ▾ (sheet)]
[Active filter chips]
[Product grid 2-col]  [Load more]
```
Product card: image, title, price (+ old price), availability tag
(متوفر / حسب الطلب (supplier) / غير متوفر), optional "عرض خاص" badge when an
active offer exists. Filters: category, price range, attributes (colour,
material), availability. Desktop: sidebar filters (inline-start), grid 3–4.
Empty/no results: illustration + reset filters. Landing-enabled product
cards link to the landing page (same permalink).

## 4. Standard product landing page (template `standard`)
```
[Header minimal: logo + phone/WA icon]
[Hero: gallery (swipe) | title, price block, availability, short pitch]
[CTA primary "اطلب الآن" (anchor/modal) + trust micro-line]
[Benefits: 3–4 icon cards]
[Offers (if any): offer cards radio]
[Description (WC) + Specs table (WC attributes)]
[Gallery / lifestyle images]
[Order form (inline default)]
[FAQ] [Delivery info] [Trust bar]
[Footer minimal]  + [Sticky CTA bar]
```
Order form block (inline), fields in order: product options (swatches/
variant), quantity, offers, name, phone, wilaya, commune, delivery method
(home/pickup cards), pickup point (if pickup), address (home), note
(opt) → **Order summary** (items, offer, delivery fee, total payable on
receipt) → submit "تأكيد الطلب" + "الدفع عند الاستلام" reassurance. Price
and total update via server preview (aria-live); loading shimmer.
Form display modes: **inline** (section), **modal** (bottom sheet mobile,
centered dialog desktop), **sticky bar** (collapsed price + CTA that expands
to sheet). Position options: hero / after offers / bottom.
Desktop: two columns — gallery inline-start sticky, info + form inline-end
(form inline = right column card). 

## 5. Promotional product landing page (`promo`)
Same skeleton as Standard with changes:
```
[Promo strip: offer title + end date countdown ONLY if offer has end date]
[Hero: image + big savings badge (computed), price block with old price]
[Offer cards as the focal block directly under hero (above benefits)]
[Quick CTA + form directly after offers]
[Benefits compact] [Description/specs collapsed accordions]
[Social proof (real)] [FAQ] [Final CTA band wood-tint]
```
Urgency rules: countdown, savings, stock-left appear only from real data.
Offer cards are larger, selected state strong, "الأكثر توفيراً" badge
auto-assigned from computed best saving. Sticky bar shows selected offer
total. Desktop: offers displayed as 3-column row under hero.

## 6. Premium product landing page (`premium`)
```
[Full-bleed hero image 4:5 (mobile) / 16:9 (desktop), title overlay, price subtle]
[Story section: large type paragraph + image pairs (alternating)]
[Material / craftsmanship highlights: icon-less, large numerals/labels]
[Gallery: large stacked images / horizontal scroller]
[Specifications: elegant two-column list]
[Offers (subtle list, not cards)]
[Order form (modal default or bottom)]
[Care/delivery notes] [FAQ minimal] [Final CTA]
```
Tone: more whitespace (section padding 64/128), lighter UI chrome,
serif-like Arabic display (e.g., Noto Naskh display) optional for H1 only,
wood grain accent line dividers. Desktop: hero overlay left/start aligned,
sections with 2-col asymmetric layouts.

## 7. Cart (secondary path, WooCommerce page)
```
[H1 "السلة"] 
[Line items: thumb, title, options, qty stepper, line price, remove]
[Offer/discount info lines (read-only, from engine)]
[Summary card: subtotal, delivery "يُحدد عند الإتمام", total]
[CTA "متابعة الطلب"] [Continue shopping]
```
Empty state: message + category links. Desktop: 2 col (items | summary
sticky). Coupons: **not shown** (offers engine instead; confirm Q).

## 8. Checkout (secondary path)
Single page, no account required, mobile single column:
```
[Progress: السلة ▸ البيانات ▸ التأكيد]
[Contact + address form (same field components & validations as order form)]
[Delivery method + wilaya/commune + pickup point]
[Payment: "الدفع عند الاستلام" (only option, preselected, info text)]
[Order summary (collapsible on mobile, expanded desktop)]
[Terms checkbox (if required)] [Submit "تأكيد الطلب"]
```
Same server rules as landing orders (WooCommerce checkout reuses form
config where possible; technical approach to confirm in Phase 5).

## 9. Order success
```
[Success icon (check, wood/success)] [H1 "تم استلام طلبك" (P)]
[Order number (large, copy button)] [Next steps: 1) نتصل بك للتأكيد (if applicable, Q1) 2) التجهيز والشحن 3) الدفع عند الاستلام]
[Order summary: items+options, offer applied, delivery method/fee/pickup point, TOTAL TO PAY ON DELIVERY]
[Customer data recap (name, phone, address)]
[Buttons: تتبّع الطلب (if tracking approved) · متابعة التسوق · contact]
```
Reload-safe, noindex, no internal flags/costs. Desktop: 2-col (steps | summary).

## 10. Order tracking (approval needed — see flags)
```
[H1 "تتبّع طلبك"]
[Form: رقم الطلب + رقم الهاتف → "بحث"]  (rate limited; no enumeration)
[Result: status timeline (RTL stepper): تم الاستلام ▸ قيد التجهيز ▸ تم الشحن ▸ تم التسليم/ملغى]
[Order summary + delivery info; carrier name/tracking number ONLY if owner enters it]
[Help/contact]
```
Status labels map to WooCommerce statuses (owner defines custom labels,
Q16). Not-found state: generic message (doesn't reveal existence).

## 11. About (Elementor)
```
[H1 + short intro] [Story: image + text pairs] [Values: 3 cards]
[Showroom/team photos (if real)] [Trust/delivery coverage summary]
[CTA: تواصل معنا / تصفح الأقسام]
```
All copy/images from owner.

## 12. Contact (Elementor/native)
```
[H1] [Contact cards: phone, WhatsApp, email, address, hours — only provided data, tap-to-call/tap-to-chat]
[Contact form: name, phone, message (+ honeypot, anti-spam)] [Map embed only if owner supplies location (lazy)]
[FAQ link]
```
Form submissions → email to admin (+ stored as WP entries? decide Q14).

---
## Cross-screen UX rules
- One primary CTA per viewport; colour = wood-dark.
- Price shown with currency after number; savings from server.
- Always show "الدفع عند الاستلام" near every order CTA.
- Errors: inline + summary; never clear typed data.
- Loading: skeletons, disabled submit with spinner; double-tap safe.
- 404/empty/offline states designed with the same components.
- SEO: single H1, descriptive titles, structured data kept from WC.
