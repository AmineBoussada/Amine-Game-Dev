---
name: real-estate-wp-theme
description: Generate a ready-to-install WordPress theme for a new real-estate agency client (Algerian market, French UI) by rebranding the tested NOVA IMMOBILIER theme in wordpress/nova-immobilier, with a choice of two designs (NOVA classic, or Riviera with light/dark mode and a French/Arabic right-to-left site) — new name, logo text, colors, fonts, city wording, contact details, code prefix and reference prefix — then check it and deliver a .zip. Use whenever the user wants a website/theme for another agency ("crée le site pour l'agence X", "nouveau client immobilier", "génère un thème pour…", "انشئ موقع لوكالة…"), or asks to reuse the NOVA theme for someone else.
---

# Real-estate WordPress theme generator

Produces `wordpress/<slug>/` and `wordpress/<slug>.zip` from the source theme `wordpress/nova-immobilier/`.
The result keeps every tested feature: the properties back office (price in DA, rooms, surfaces, 58 wilayas,
photos, status, featured), agency settings, client requests, filters and gallery, SEO, and the demo import command.

The source theme is the single template. Improve features there, and every future client theme inherits them.
Never copy a client theme back over it.

## 1. Collect the inputs

Start from `config.example.json` (in this skill folder). Ask the user, in one AskUserQuestion round, only for what
they have not given yet:

| Field | Notes |
|---|---|
| `name` | Full agency name, e.g. "Atlas Immobilier". Replaces "NOVA IMMOBILIER" everywhere. |
| `shortName`, `logoBottom` | The two logo lines (e.g. "ATLAS" / "IMMOBILIER"). The first letter of `shortName` becomes the monogram. |
| `slug` | Theme folder and zip name: lowercase and hyphens (`atlas-immobilier`). |
| `prefix` | Code prefix, 2–10 lowercase letters (`atlas`). Used for PHP functions, meta keys (`_atlas_price`), settings page (`atlas-settings`), WP-CLI (`wp atlas seed`). |
| `refPrefix` | Listing references, uppercase (`ATL` gives ATL-1024). |
| `colors` | Six hex colors: `background`, `surface`, `text`, `muted`, `accent`, `soft`. Shades of the accent are derived. Keep text-on-background contrast high and give the accent enough contrast for white button text. |
| `fonts` | Google Fonts `display` (headings) and `body`, with weights that exist for that family. Safe pairs: Playfair Display + Manrope, Cormorant Garamond + Inter, Fraunces + DM Sans, Lora + Work Sans. |
| `locality` | `wilaya` (admin default), French phrases `a` ("à Oran"), `de` ("d’Oran"), `adjective` ("oranaise"), optional `communesExample`. |
| `contact` | phone, mobile, WhatsApp (international digits), email, address, city line, opening hours. |
| `socials`, `texts` | Optional. Leave a social URL empty to hide it. `texts` sets the homepage hero, the about block, the footer description and three stats. |
| `demoContent` | `true` imports the 12 demo properties (located in Algiers) with `wp <prefix> seed`. Use `false` for a real client who will enter their own listings. |
| `demoNotice` | `false` hides the "site de démonstration" footer line. Use `false` for a real client. |
| `layout` | `"nova"` (default) or `"riviera"`. See *Layouts* below. Ask the user which one when they have not said. |

### Layouts

The back office (properties, settings, client requests, demo import) is the same for every layout. Only the visitor side changes.

- **`nova`** — the original NOVA design (beige, bronze, serif titles). All config fields apply.
- **`riviera`** — navy and white, marinière stripes, magazine layout (the "Bahia Immobilier" design). Adds:
  - a **light/dark** button, remembered on the visitor's device;
  - a **French/Arabic** button: the whole site switches to Arabic, right to left, and the choice is remembered on every page;
  - optional **Arabic fields on each property** (title, summary, description; the French text is shown when they are empty)
    and an **Arabic texts** section in *Réglages agence*;
  - a visit planner on the property page (next opening days, time slots) that fills the contact form.

  Extra config fields: `nameAr` (required), `locality.city` and `locality.cityAr` (required), `logoBottomAr`, `colors.gold`,
  and `textsAr` (`heroTitle`, `heroText`, `aboutTitle`, `aboutText`, `description`, `address`, `city`, `stats` = 3 labels).
  `fonts` is ignored (Jost, and IBM Plex Sans Arabic in Arabic). The dark palette is derived from `colors.accent`.
  Full example: `config.riviera.example.json`. Demo content: 10 properties in Oran with Arabic texts.
  Interface translations live in `layouts/riviera/inc/lang-ar.php` (French text => Arabic): add a line there for any new string,
  and ask the user to have the Arabic reviewed by a native speaker before a real client goes live.

Layout files are written with the `nova_` prefix and placeholders (`__SHORT__`, `__CITY__`, `__CITY_AR__`, `__NAME_AR__`,
`__LOGO_BOTTOM__`, `__LOGO_BOTTOM_AR__`); the generator copies them over the base theme and rebrands them like the rest.
To add a layout, create `layouts/<name>/` with its templates, `inc/layout.php` (loaded by `functions.php`), and one stylesheet.

When the user gives only a name and a city, propose sensible colors and fonts and say which ones you chose.
Never invent real contact details: use the user's data or clearly fictional demo values.

Save the config as `wordpress/<slug>.config.json`. It is git-ignored because it holds client contact data.

## 2. Generate

From the repository root:

```bash
npm run theme:build          # refresh the NOVA source assets first (shared CSS, JS, demo data)
node .claude/skills/real-estate-wp-theme/scripts/generate-theme.mjs wordpress/<slug>.config.json
```

- Add `--force` to regenerate over an existing `wordpress/<slug>/`, after confirming with the user if they edited that folder by hand.
- The script validates the config, copies the theme, applies the rebrand rules, sets the design tokens, fonts and settings
  defaults, empties the demo data if asked, builds the zip, and lists any leftover "nova" occurrence (exit code 1).
- A leftover means the source theme gained a new branded string. Add a rule to `generate-theme.mjs` rather than
  patching the output by hand.

## 3. Check

```bash
.claude/skills/real-estate-wp-theme/scripts/check-theme.sh wordpress/<slug>
```

This checks required files, the theme header, PHP syntax, leftover branding, and the zip structure (one root folder named
`<slug>`, which WordPress requires). All checks must pass before delivery.

## 4. Live test (recommended when Docker is available)

```bash
cd wordpress
# start dockerd first if `docker info` fails: (dockerd > /tmp/dockerd.log 2>&1 &)
export THEME=<slug> WP_PORT=8081
docker compose -p <prefix> up -d
WP="docker compose -p <prefix> run --rm -T cli wp"
$WP core install --url=http://localhost:8081 --title="<name>" --admin_user=admin --admin_password=<pw> --admin_email=admin@example.com --skip-email
$WP theme activate <slug> && $WP rewrite structure '/%postname%/' --hard && $WP <prefix> seed
cd .. && node .claude/skills/real-estate-wp-theme/scripts/smoke-test.cjs --base http://localhost:8081 --prefix <prefix> --pass <pw> --out <scratchpad>/<slug>
```

The smoke test covers the visitor pages, search, filters, sorting, favorites and form validation. It also covers the owner
workflow: add a property with 2 uploaded photos, edit price and rooms, mark it "Vendu", change the phone in the settings,
receive a client request, and trash the property. Look at `home.png` and `property.png` to confirm the colors, fonts and logo.
Clean up with `docker compose -p <prefix> down -v`.

Docker notes: export `THEME` and `WP_PORT` in every shell that runs `docker compose` for this project, otherwise compose
recreates the container with the default theme. Regenerating with `--force` replaces the theme folder: restart the
container afterwards (`docker compose -p <prefix> restart wordpress`).

For the `riviera` layout, also check the Arabic and dark versions: open the site with `?lang=ar`, switch the theme with the
moon button, and look at the home page and a property page at 390 px wide (no horizontal scroll, buttons at least 44 px).

If wordpress.org is unreachable, the French language pack cannot install. That only affects the WordPress admin chrome:
the theme's own screens are already in French.

## 5. Deliver

Send `wordpress/<slug>.zip` to the user and give these installation steps in French:

1. Hébergement WordPress (PHP 8.0 ou plus), WordPress installé en français.
2. **Apparence › Thèmes**, bouton **Téléverser un thème** en haut (le formulaire n’apparaît qu’après ce clic) ›
   **Choisir un fichier** › **Installer maintenant** › **Activer**.
   Méthode de secours : décompresser le zip et envoyer le dossier `<slug>/` dans `wp-content/themes/` par FTP.
3. **Réglages › Permaliens** : « Titre de la publication ».
4. Créer les pages `accueil`, `a-propos`, `contact`, `favoris`, `politique-de-confidentialite`, et définir « Accueil »
   comme page d’accueil (Réglages › Lecture). Avec WP-CLI, `wp <prefix> seed` fait tout cela.
5. Compléter **Réglages agence**, puis ajouter les biens dans **Biens immobiliers**.

The owner's guide in `wordpress/README.md` (section 1) applies unchanged to every generated theme.

## Rules

- Do not commit client themes or configs unless the user asks. `.gitignore` excludes every `wordpress/*/` folder except
  `nova-immobilier/` and the fictional demo `bahia-immobilier/`, and excludes `wordpress/*.config.json` and `wordpress/*.zip`.
- Do not use the NOVA name, logo or demo contact details for a real client.
- Keep the generated theme self-contained. It needs no plugin.
