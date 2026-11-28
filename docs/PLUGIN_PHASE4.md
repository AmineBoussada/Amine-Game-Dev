# Phase 4 — Offers engine (plugin v0.4.0)

## What it does
- **Pricing engine** (`src/Offers/PricingEngine.php`): a pure function (no WordPress, no database). Same request + same offers + same settings = same result, whatever the order of the offers. All amounts are integer minor units; percentages are basis points, rounded half-up per unit.
- **Offer types**: quantity discount (tiers), bundle (fixed set of products, fixed price or percent), free gift, custom rule (conditions + exactly one action). Closed vocabulary: no code, no expressions.
- **Conditions**: minimum quantity, minimum subtotal (both over the products in the offer's scope), wilayas, delivery methods, weekdays (store time zone), date window (start inclusive, end exclusive), scope (all / products / categories).
- **Resolution** (spec §5): drafts and archived offers are invisible; an *exclusive* offer wins alone; otherwise **one price offer per line** (the customer's chosen offer first, then priority, then biggest saving, then lowest id), gifts up to a limit, the best delivery reduction, the best order-level discount.
- **Customer choice**: offers marked *selectable* apply only when chosen. A chosen id that is not eligible is **refused** (`chosen_offer_invalid`), never swapped for another offer.
- **Guards**: a unit price is never raised or negative; a bundle is atomic (all items, enough quantity) and its price is spread over the covered lines so the sum is exact; an offer that does not lower the price is rejected (`not_a_discount`); optional minimum price per line (% of base); order discount capped; a gift out of stock withdraws the offer; quantity 1–1000 and price ≤ 10^12 minor units; bad input returns an error instead of a guess.
- **Result** carries everything Phase 5 must store as a snapshot: engine version, each applied offer with id/version/label, per-line base and final prices, gifts, delivery base/discount/final, totals, and the list of **rejected offers with the reason**.
- **Admin** (Top Solution > العروض): list with badges (selectable, exclusive, invalid, possible conflict), editor (sections follow the chosen type), activate / pause / duplicate / archive / delete; **simulator** showing totals, applied offers and why others were rejected. Invalid input is never saved and the typed values are kept.
- **Settings > العروض**: max gifts per order (default 1, 0 = no gifts), minimum line price % (default 0 = off).
- `EngineInputs::price_single()` builds the request from WooCommerce (active price incl. native sale price, categories, stock for gifts) and the delivery tables; Phase 5 calls the same function, so an order can never differ from the simulator.

## Decisions (differences from the written spec — please confirm)
1. **Fourth group `order`** for fixed order-level discounts (the spec listed them as a step but not as a group). The largest wins, one per order.
2. **A custom rule has exactly one action.** "10 % off and free delivery" = two offers. This keeps groups and tie-breaks unambiguous.
3. **Native sale price is the base**, not an offer entity: the engine starts from WooCommerce's active price. "Campaign price with dates" is therefore done with a dated custom rule.
4. **Usage limits are not included** (they need order counts from Phase 5); the field will be added then.
5. **Free-delivery threshold** (Delivery settings) is evaluated on the subtotal *before* offers; offers then adjust the fee.
6. Equal priority is allowed but the admin warns, and the engine's tie-break is deterministic (bigger saving, then oldest).
7. Offers are written with **ids** for products/categories/wilayas (no product search box yet); a product picker comes with the landing-page editor (Phase 6).

## Files
`src/Offers/{OfferSchema,PricingEngine,OfferForm,OfferConflicts,OfferText,OfferRepository,EngineInputs,Module}.php`, `src/Admin/OffersPage.php`, `assets/offers-admin.js`, settings tab, `uninstall.php` (offers removed on request).

## Tests
- PHPUnit: 245 tests / 4181 assertions (engine: tier edges, rounding, floor, dates, conditions, scope, priority/tie-breaks, order independence, exclusive, selectable and tampered ids, gifts + stock + limit, delivery and order discounts, bundles incl. awkward allocation and variation items, bad/huge input, a 200-case property test of determinism and the total invariant; schema, form conversion, overlap detection, settings).
- Mutation check: deliberately breaking six rules in the engine (tier boundary, date end, priority order, discount cap, price floor, chosen-offer filter) made tests fail each time.
- Integration (real WP + WooCommerce, fictional data): 27 checks (prices from WooCommerce, delivery tables, versions, drafts, gift stock, tampered choice, native sale price, invalid stored offer forced to draft, private post type).
- Browser: 26 checks (create/refuse/keep draft, conflict warning, badges, edit round-trip, duplicate/delete, simulator values and explanations, settings validation, 375 px, subscriber blocked, POST without nonce refused); axe: no violations on editor, simulator and list.
- phpcs: clean.

## Open / not done
- Offers are not shown to customers yet (cards, strike-through prices: Phase 6) and do not create orders (Phase 5).
- Owner decisions still needed: offer stacking policy confirmation (decisions 1–2 above), rounding policy confirmation (half-up per unit), whether the customer picks cards or the best offer is automatic (both are supported).
