# Tassili Immobilier — frontend

Static frontend (HTML5, CSS3, vanilla JavaScript) for a bilingual French / Arabic real estate agency template, built from the approved design system. Tassili Immobilier is a fictional agency and every listing, price, name and phone number is sample data.

No backend, no build step, no framework. WordPress/PHP comes later.

## Run it

Open `index.html` in a browser, or serve the folder:

```bash
npm start            # http://localhost:5173
```

## Pages

| File | Page |
|---|---|
| `index.html` | Home: hero search, wilayas, featured listings, types, why us, new developments, testimonials, seller CTA |
| `biens.html` | Properties archive: filters (sheet on mobile), active chips, sort, grid/list, pagination, WhatsApp alert. `?favoris=1` shows saved properties |
| `bien.html?ref=TS-1024` | Property page: gallery + lightbox, facts, papers, amenities, location, agent panel, visit request, similar listings, mobile action bar |
| `a-propos.html` | About: story, figures, method, team, legal and privacy |
| `contact.html` | Contact: WhatsApp / phone / e-mail tiles, validated form, office, FAQ. `?sujet=vendre` preselects the subject |

## Structure

```
assets/css/tokens.css      colours (light + dark), fonts, spacing — the approved palette
assets/css/styles.css      all components and page layouts (logical properties, so RTL mirrors)
assets/js/boot.js          sets language, direction and theme before first paint
assets/js/utils.js         helpers: formatting (DA, milliards), favourites, toast, WhatsApp links
assets/js/i18n.js          every French and Arabic string
assets/js/config.js        agency details, agents, wilayas/communes, types, papers, amenities
assets/js/data.js          16 sample listings (= future WordPress posts + custom fields)
assets/js/media.js         generated placeholder photos; set p.photos to use real images
assets/js/components.js    icons, header + mobile drawer, footer, property card, global events
assets/js/search.js        search state <-> URL, matching, sorting, home search panel
assets/js/forms.js         validation (Algerian phone numbers, visit dates, consent)
assets/js/pages/*.js       one controller per page
```

## Language and direction

The FR / العربية switch in the top bar sets `lang` and `dir` on `<html>`, saves the choice, and re-renders the page in place. `?lang=ar` or `?lang=fr` in any URL also works. Prices, references and phone numbers are isolated as left-to-right runs so they read correctly inside Arabic text.

## Before going live

Replace the placeholder phone numbers, WhatsApp numbers, e-mail and address in `assets/js/config.js`. Forms currently simulate sending: hook `TI.forms.bind(..., onValid)` up to the real endpoint.

## Tests

```bash
npm install          # installs Playwright (first time only)
npx playwright install chromium
npm test
```

`tests/run.cjs` starts a local server and checks 103 interactions: search, filters, URL state, pagination, sort, list view, favourites, gallery, lightbox (keyboard, swipe, RTL), visit and contact form validation, language and theme switching, mobile menu and filter sheet, and that no page scrolls sideways at 360, 768 and 1280 px in both languages.
