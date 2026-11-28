# Top Solution Child (theme)

Child of **Hello Elementor**. Global design system, header, navigation,
mobile drawer, footer, homepage, category layouts, content-page layouts and
WooCommerce visual styling. **Arabic + RTL first.**
It contains **no business logic**: pricing, offers, forms, orders, inventory,
suppliers, delivery and the per-product landing page renderer belong to the
`top-solution-core` plugin (separate phase).

Requirements: WordPress >= 6.4, PHP >= 7.4, parent theme `hello-elementor`.
WooCommerce and Elementor **Free** are optional (the theme degrades gracefully without them).

## Elementor: Free only (critical requirement)
- **Elementor Pro is not required and not used**: no Pro widgets, no Theme
  Builder locations (`elementor_theme_do_location()` is never called), no
  WooCommerce Builder, Popup Builder or Pro forms.
- Header, footer, product archives, single product, cart, checkout and My
  Account are rendered by **native theme / WooCommerce templates and hooks**.
  The child ships its own `index.php` so it never goes through the parent's
  Pro location checks.
- Elementor Free is used only for editable page content (homepage, About,
  Contact, landing sections) and Elementor Free's page templates
  ("Elementor Full Width", "Elementor Canvas").
- With Elementor deactivated every page still renders through the theme's
  templates (verified by the test suite in both modes).

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
| `inc/template-tags.php` | Helpers: logo, menu fallback, breadcrumbs, tel/WhatsApp URLs, Elementor-built check, cart link/count, contact cards markup. |
| `inc/woocommerce.php` | Presentation hooks only: shop wrappers, out-of-stock card badge, cash-on-delivery note under add-to-cart, drops WC float layout CSS (keeps "general"), 4 columns / 12 per page, breadcrumb markup, pagination labels, Arabic sale badge, header cart-count fragment, top-category helpers, categories-page URL helper. Loop "add to cart" hidden by default (filter `ts_child_show_loop_add_to_cart`). |
| `inc/elementor.php` | Elementor Free only: loads tokens/fonts in the editor+preview, disables Elementor's Google Fonts, wraps Elementor Free page templates in `<main>`, and once (admin, `admin_init`) writes the brand colours/fonts/1200px width into the active kit **only where the kit is still at Elementor's factory defaults**. |
| `header.php` / `footer.php` | Replace the parent's. Always render the theme header/footer (Hello's free "disable header/footer" setting is respected). |
| `index.php` | Replaces the parent's fallback; routes pages, posts, archives, search, 404 to the theme's template parts. |
| `screenshot.png`, `readme.txt` | Theme screenshot (1200x900) and WordPress readme (licences, changelog). |
| `searchform.php` | Search form (products only when WooCommerce is active). |
| `front-page.php` | If the front page is built with Elementor -> its content; otherwise the built-in homepage. |
| `template-parts/site-header.php` | Announcement, header bar, search row, mobile drawer. |
| `template-parts/site-footer.php` | Footer: brand, footer menu, contact (only entered data), copyright, "payment on delivery" note. |
| `template-parts/trust-bar.php` | Trust items. |
| `template-parts/home/{home,hero,categories,featured,steps}.php` | Built-in homepage sections; each hides itself when empty. |
| `template-parts/category-tile.php` | One category tile. |
| `template-parts/{single,archive,search,404,post-card}.php` | Override the parent's template parts used by `index.php`: pages/posts, archives, search, 404. |
| `templates/page-categories.php` | Page template "Top Solution - الأقسام": all top-level categories + subcategory chips. |
| `templates/page-contact.php` | Page template "Top Solution - اتصل بنا": page content (editor or Elementor Free) + contact cards (tap-to-call, WhatsApp, email, address, hours) from the Customizer. Replaces the former shortcode: shortcodes are plugin territory. |
| `templates/page-full-width.php` | Page template "Top Solution - عرض كامل": no title, edge-to-edge (for Elementor pages). |
| `assets/css/main.css` | Tokens, @font-face, base, header, nav, drawer, footer, buttons, tiles, prose, pagination, contact cards, 404. |
| `assets/css/editor.css` | Block-editor canvas styles (fonts, colours, headings, links) via `add_editor_style()`. |
| `assets/css/home.css` | Hero (loaded only on the built-in homepage). |
| `assets/css/woocommerce.css` | Product cards and loops (CSS grid), single product + gallery, cart, checkout (classic two-column + block), My Account, responsive shop tables (replaces the dequeued WooCommerce smallscreen CSS), notices, forms, block grids. |
| `assets/js/theme.js` | Drawer (focus trap, Escape, overlay, scroll lock), search toggle, submenu buttons, mobile hide-on-scroll header. Vanilla JS. |
| `assets/fonts/*` | Self-hosted woff2 + licences. |
| `phpcs.xml.dist` | Coding-standards ruleset. |
| `languages/` | Reserved. Arabic is the source language of theme strings (text domain `top-solution-child`). |

## Design tokens (`:root` in `main.css`)
`--ts-bg #fff`, `--ts-surface-2`, `--ts-ink`,
`--ts-ink-2`, and the palette: `--ts-everglade #014749` (primary: buttons,
links, footer), `--ts-whirlpool #395c7a` (secondary), `--ts-aqua #53978a`
(decoration only), `--ts-cornfield #c88928` (highlights, always dark text),
`--ts-sisal #c0976b` (warm neutral; its tints are `--ts-surface`,
`--ts-surface-2`, `--ts-line`). Components use the role tokens `--ts-brand`,
`--ts-accent`, `--ts-tint`. Colours were supplied by the owner (palette
image); see `docs/design/DESIGN_SYSTEM.md` for the contrast table.

## Customizer (Appearance > Customize > Top Solution)
Contact data (phone, WhatsApp, email, address, hours), header (announcement,
search, cart icon - off by default), homepage (hero title/text/CTA/image,
trust items, sections on/off, featured title/count, three steps), footer
(about, copyright). **Nothing is invented**: empty contact fields are not
rendered; hero title/text fall back to site title/tagline; the only default
copy is neutral UI text and the three given facts (cash on delivery, home
delivery, office pickup).

## Elementor Free usage
- Build the homepage and general pages with Elementor Free; for edge-to-edge
  pages use "Top Solution - عرض كامل" or Elementor's "Elementor Full Width".
- Site Settings (kit) are pre-filled once with the brand: Primary = Everglade,
  Secondary = Whirlpool, Text = #42585A, Accent (buttons) = Everglade; custom
  colours Cornfield, Aqua Cove, Sisal Beige, White (a kit still holding
  Elementor's defaults or this theme's earlier palette is updated; anything the
  owner changed is left alone); fonts Noto Kufi Arabic / IBM Plex Sans Arabic
  (self-hosted by the theme); content width 1200px. Anything already changed
  by the owner is left untouched.
- Never put white text on Cornfield, Aqua Cove or Sisal Beige, and never use Cornfield or Aqua Cove as a text colour on white.

## WooCommerce recommendations
- Settings > Products > "Shop page display" / "Category display": "Show
  subcategories" or "Show both" renders category tiles in loops.
- Currency DZD, decimals, position: set by the store owner (not forced by the theme).
- Cart/checkout (block or classic) are styled; the primary order flow is the
  landing page (plugin), not these pages.

## Theme Check (wordpress.org rules)
Run with the official Theme Check plugin: no REQUIRED issues. Remaining
WARNING/INFO items come from the parent Hello Elementor's own files. Remaining
RECOMMENDED items are deliberate: supports such as post-thumbnails,
align-wide and responsive-embeds are declared by the parent; Theme/Author URI
are left empty until the store has a domain; no widget areas, custom
header/background or block patterns are needed by the design.

## Extending
Filters: `ts_child_show_loop_add_to_cart` (bool). Templates can be overridden
by copying into the child theme as usual. New icons: add to
`ts_child_icon_paths()`.

## Known limits / not in this theme
Product landing pages, offers, order forms, order tracking, delivery logic,
product filters (price/attribute sheet) - plugin or later phases.
Translations: Arabic only; English strings can be added with a POT file later.

## Packaging
`bin/build-theme-zip.sh` creates `dist/top-solution-child.zip` (folder
`top-solution-child/` at the root of the archive; dev files such as
`phpcs.xml.dist` are excluded). Upload it in Appearance > Themes > Add New.

## Testing
See `tests/theme/README.md` and `docs/design/`.
