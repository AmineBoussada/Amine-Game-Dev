# Tassili Immobilier: WordPress theme

A bilingual French / Arabic real estate theme for Algerian agencies, built from the approved design and static frontend in `../tassili-immobilier/`. Tassili Immobilier is a fictional agency, and all demo data is made up.

**Install:** upload `dist/tassili-immobilier.zip` at **Appearance > Themes > Add New > Upload Theme**, activate it, then open **Appearance > Demo content**.

Requires WordPress 6.4+ and PHP 7.4+. No plugin is needed.

## What you get

| Area | Details |
|---|---|
| Templates | `front-page`, `archive-property`, `single-property`, `taxonomy-{wilaya,transaction,property_type}`, `page`, `single`, `archive`, `search`, `404`, `index`, `header`, `footer`, `searchform`, `comments`; page templates About, Contact and Favourites |
| Listings | `property` post type at `/biens/`; taxonomies `transaction`, `wilaya` (58 wilayas with communes as children), `property_type`, `feature`; fields for price, surface, typology, rooms, floor, year, new-build and delivery date, documents, agent, gallery |
| Search | Filters in the URL (`tx`, `neuf`, `wilaya`, `commune`, `type`, `typo`, `pmin`, `pmax`, `smin`, `papers`, `amen`, `sort`, `view`). Plain GET form that works without JavaScript; with JavaScript the results swap in place and the count updates live |
| Languages | French (LTR) and Arabic (RTL). `?lang=ar` / `?lang=fr`, remembered in a cookie. Arabic fields for listings, agents, testimonials, pages, posts, terms and menu items. Full `.po` / `.mo` files for both |
| Contact | Contact form and "book a visit" dialog. Nonce, honeypot, rate limit, server validation. Saved as private **Leads** and e-mailed |
| WhatsApp | Button on every listing and a floating button, with the reference pre-filled in the message |
| Back office | Native meta boxes, Customizer panel, demo importer, structured data (schema.org) on listings |
| Design | Light and dark mode, responsive from 360 px, bundled fonts (no external requests) |

## Managing properties (back office)

Everything is under one **Properties** menu in the WordPress admin:

| Screen | What it is for |
|---|---|
| **Overview** | Counts (published, available, reserved, sold or rented, drafts, requests), shortcuts, a step-by-step guide, latest requests |
| **All properties** | The list: photo, reference, price, transaction, wilaya, type, availability and a home-page star. Filters by transaction, wilaya, type and availability. Row links: Edit, Quick Edit, Trash, Mark as reserved, Mark as sold or rented, Make available again, **Duplicate** |
| **Add new** | The full form with every field visible (classic editor): details, documents, Arabic version, availability, agent, photo gallery |
| **Bulk actions** | Select several properties: mark available / reserved / sold or rented, show or remove from the home page, move to the trash |
| **Transactions, Wilayas, Property types, Features** | Manage the lists, with an Arabic name for each |
| **Agents, Testimonials** | People shown on the site |
| **Leads** | Contact and visit requests (administrators only) |
| **Site settings** | Opens the Customizer panel: phone, WhatsApp, address, hours, hero text, figures, language, and whether sold properties stay listed |

A property can be **Available**, **Reserved** or **Sold or rented**. Reserved shows a badge. Sold or rented shows "Vendu" or "Loué", and the property leaves the searches and the home page (its page still opens). Turn on *Show sold and rented properties in the listings* in Site settings to keep them listed.

## Folder layout

```
tassili-immobilier/          the theme (this is what gets zipped)
  style.css                  theme header + WordPress-specific styles
  functions.php              loads inc/*
  inc/                       language, icons, helpers, setup, enqueue, post-types, taxonomies,
                             meta-boxes, customizer, query-filters, template-tags, nav, rest,
                             forms, demo-import (+ demo/*.json)
  template-parts/            card-property, badges, archive-properties, content-*
  templates/                 page-about, page-contact, page-favorites
  assets/css|js|img|fonts    tokens.css, styles.css, fonts.css; app, search, property, forms JS
  languages/                 tassili-immobilier.pot, fr_FR.po/.mo, ar.po/.mo
tools/                       extract-strings, translations, build-translations, build-zip, setup-test-site
tests/                       run.cjs (155+ browser checks), install-test.cjs (zip upload test)
dist/tassili-immobilier.zip  the installable package
```

## How the two languages work

The theme chooses the language itself, so there is no plugin to install. The visitor's choice comes from `?lang=`, then the `tassili_lang` cookie, then the default set in **Customize > Language and display**. WordPress is told to use `fr_FR` or `ar` for the public site, so translations, `<html lang>` and `dir="rtl"` all follow. The admin keeps the signed-in user's own language.

Static text is English in the code with complete French and Arabic translations. After changing any string, run `php tools/build-translations.php`: it regenerates the `.pot`, `.po` and `.mo` files and **fails if a string has no translation**.

If you use a page-caching plugin, make it vary the cache by the `lang` query argument and the `tassili_lang` cookie.

## Things to know

- **Listings live in the theme.** Post types and taxonomies are registered by the theme, so listings are hidden while another theme is active (they stay in the database). This is also why the theme cannot be listed in the WordPress.org directory, whose Theme Check flags `register_post_type()`; it is a deliberate choice for a one-file install.
- **Leads hold personal data.** Only administrators can see them (Properties > Leads). Add suggested privacy text from **Settings > Privacy**.
- **Replace the demo details** in Customize: phone, WhatsApp number, e-mail, address, registration numbers.
- **Permalinks:** use "Post name". The demo importer sets it when none is set.

## Development

Needs PHP 8+, Node 18+, Composer (for PHPCS) and Playwright.

```bash
tools/setup-test-site.sh /tmp/wp 8080 --link-theme     # throw-away WordPress with SQLite
PHP_CLI_SERVER_WORKERS=6 php -S 127.0.0.1:8080 -t /tmp/wp /tmp/wp/router.php &
# in another shell: activate the theme and import demo content from the admin, then
WP_SITE_DIR=/tmp/wp node tests/run.cjs                 # browser tests
tools/build-zip.sh                                     # translations check + dist zip
```

`tests/install-test.cjs` starts from a site that has no theme, uploads the zip through **Appearance > Themes**, activates it, imports the demo and checks the result.

Coding standard: `phpcs --standard=.phpcs.xml.dist` (WordPress, WordPress-Docs, WordPress-Extra, PHPCompatibilityWP for 7.4+). It reports no errors or warnings.

## Licence

GPL v2 or later. Bundled fonts are under the SIL Open Font License 1.1 (see `readme.txt`).
