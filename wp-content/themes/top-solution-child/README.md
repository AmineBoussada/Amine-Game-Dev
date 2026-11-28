# Top Solution Child (theme)

Child of **Hello Elementor**. Global design system, header, navigation,
mobile drawer, footer, homepage, category layouts, content-page layouts and
WooCommerce visual styling. **Arabic + RTL first.**
It contains **no business logic**: pricing, offers, forms, orders, inventory,
suppliers, delivery and the per-product landing page renderer belong to the
`top-solution-core` plugin (separate phase).

Requirements: WordPress >= 6.4, PHP >= 7.4, parent theme `hello-elementor`.
WooCommerce and Elementor are optional (the theme degrades gracefully without them).

## Install
Copy the folder to `wp-content/themes/top-solution-child/`, make sure
`hello-elementor` is installed, activate. Set Settings > General > Site
Language to **Arabic** (an admin notice warns if the locale is not RTL).
Recommended: Settings > Permalinks > "Post name".

## Rules this theme follows
- Parent theme and WooCommerce/Elementor files are never modified; all
  integration is through hooks, filters and template overrides.
- WordPress Coding Standards (config: `phpcs.xml.dist`, 0 violations).
- Logical CSS properties only (`margin-inline`, `inset-inline-start`, ...);
  the few direction-dependent cases are commented `RTL:`.
- All output is escaped; icons pass through `wp_kses`.
- Assets are enqueued (never inline/hard-coded in templates), versioned by
  file mtime, conditionally loaded.
- Fonts are self-hosted (SIL OFL): IBM Plex Sans Arabic (body), Noto Kufi
  Arabic (headings). No Google Fonts request (also disabled for Elementor).

## File map
| File | Purpose |
|---|---|
| `style.css` | Theme header only (name, parent, version). Styles live in `assets/css`. |
| `functions.php` | Defines constants and loads `inc/*`. |
| `inc/setup.php` | `after_setup_theme`: text domain, custom logo, HTML5, menus (Arabic labels for parent's `menu-1` header and `menu-2` footer), `ts-category-tile` image size, body classes, emoji script removal, excerpt tweaks, non-RTL admin notice. |
| `inc/enqueue.php` | Enqueues `main.css` (after parent `hello-elementor-theme-style`), `home.css` (only when the built-in homepage renders), `woocommerce.css` (when WooCommerce is active), `theme.js` (footer, `defer`); dequeues parent `hello-elementor-header-footer`; preloads the two body font files. |
| `inc/customizer.php` | All editable values (Customizer > Top Solution): contact data, announcement, header toggles, homepage copy/toggles, trust items, steps, footer text. Defaults + sanitisers. `ts_child_get( $key )` reads them. |
| `inc/icons.php` | Inline SVG icon set; `ts_child_the_icon( 'name' )`. Direction icons flip in RTL. |
| `inc/template-tags.php` | Helpers: logo, menu fallback, breadcrumbs, tel/WhatsApp URLs, Elementor-built check, cart link/count. |
| `inc/shortcodes.php` | `[ts_contact_cards]` (tap-to-call/WhatsApp/email/address/hours cards from the Customizer) and `[ts_trust_bar]`. |
| `inc/woocommerce.php` | Presentation hooks only: shop wrappers, drops WC float layout CSS (keeps "general"), 4 columns / 12 per page, breadcrumb markup, pagination labels, Arabic sale badge, header cart-count fragment, top-category helpers, categories-page URL helper. Loop "add to cart" hidden by default (filter `ts_child_show_loop_add_to_cart`). |
| `inc/elementor.php` | Loads tokens/fonts in the Elementor editor+preview; disables Elementor's Google Fonts. |
| `header.php` / `footer.php` | Replace the parent's. Elementor Pro header/footer locations still win via `elementor_theme_do_location()`. |
| `searchform.php` | Search form (products only when WooCommerce is active). |
| `front-page.php` | If the front page is built with Elementor -> its content; otherwise the built-in homepage. |
| `template-parts/site-header.php` | Announcement, header bar, search row, mobile drawer. |
| `template-parts/site-footer.php` | Footer: brand, footer menu, contact (only entered data), copyright, "payment on delivery" note. |
| `template-parts/trust-bar.php` | Trust items. |
| `template-parts/home/{home,hero,categories,featured,steps}.php` | Built-in homepage sections; each hides itself when empty. |
| `template-parts/category-tile.php` | One category tile. |
| `template-parts/{single,archive,search,404,post-card}.php` | Override the parent's template parts used by `index.php`: pages/posts, archives, search, 404. |
| `templates/page-categories.php` | Page template "Top Solution - الأقسام": all top-level categories + subcategory chips. |
| `templates/page-full-width.php` | Page template "Top Solution - عرض كامل": no title, edge-to-edge (for Elementor pages). |
| `assets/css/main.css` | Tokens, @font-face, base, header, nav, drawer, footer, buttons, tiles, prose, pagination, contact cards, 404. |
| `assets/css/home.css` | Hero (loaded only on the built-in homepage). |
| `assets/css/woocommerce.css` | Loops (CSS grid), single product fallback, cart, checkout, account, notices, blocks (cart/checkout/product collection), forms. |
| `assets/js/theme.js` | Drawer (focus trap, Escape, overlay, scroll lock), search toggle, submenu buttons, mobile hide-on-scroll header. Vanilla JS. |
| `assets/fonts/*` | Self-hosted woff2 + licences. |
| `phpcs.xml.dist` | Coding-standards ruleset. |
| `languages/` | Reserved. Arabic is the source language of theme strings (text domain `top-solution-child`). |

## Design tokens (`:root` in `main.css`)
`--ts-bg #fff`, `--ts-surface-2`, `--ts-ink`,
`--ts-ink-2`, `--ts-brand #576238` Moss (buttons/links/icons),
`--ts-brand-d` (hover), `--ts-accent #ffd95d` Mustard (highlights, always with
dark text), `--ts-surface #f0eadc` Eggshell, `--ts-tint`, status colours,
`--ts-line`, fonts, spacing, radii, header height. Brand colours supplied by
the owner; see `docs/design/DESIGN_SYSTEM.md` for the contrast table.

## Customizer (Appearance > Customize > Top Solution)
Contact data (phone, WhatsApp, email, address, hours), header (announcement,
search, cart icon - off by default), homepage (hero title/text/CTA/image,
trust items, sections on/off, featured title/count, three steps), footer
(about, copyright). **Nothing is invented**: empty contact fields are not
rendered; hero title/text fall back to site title/tagline; the only default
copy is neutral UI text and the three given facts (cash on delivery, home
delivery, office pickup).

## Elementor
- Build the homepage and general pages in Elementor; use page template
  "Top Solution - عرض كامل" for edge-to-edge canvases.
- Site Settings > Global: set colours to the tokens above and fonts to
  "IBM Plex Sans Arabic" (body) / "Noto Kufi Arabic" (headings) by typing the
  family name; they are already self-hosted by the theme.
- Elementor Site Settings > Layout > Content width: 1200px to match the theme.
- Elementor Pro theme-builder headers/footers/archives override the theme's.

## WooCommerce recommendations
- Settings > Products > "Shop page display" / "Category display": "Show
  subcategories" or "Show both" renders category tiles in loops.
- Currency DZD, decimals, position: set by the store owner (not forced by the theme).
- Cart/checkout (block or classic) are styled; the primary order flow is the
  landing page (plugin), not these pages.

## Extending
Filters: `ts_child_show_loop_add_to_cart` (bool). Templates can be overridden
by copying into the child theme as usual. New icons: add to
`ts_child_icon_paths()`.

## Known limits / not in this theme
Product landing pages, offers, order forms, order tracking, delivery logic,
product filters (price/attribute sheet) - plugin or later phases.
Translations: Arabic only; English strings can be added with a POT file later.

## Testing
See `tests/theme/README.md` and `docs/design/`.
