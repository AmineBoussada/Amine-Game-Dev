=== Top Solution Child ===
Contributors: topsolution
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Arabic / RTL child theme of Hello Elementor for the Top Solution WooCommerce store. Works with Elementor Free; Elementor Pro is not required or used.

== Description ==

Global design system (Everglade, Whirlpool, Aqua Cove, Cornfield, Sisal Beige and white), header, navigation,
mobile drawer, footer, homepage, category and content layouts, and visual
styling for WooCommerce: product cards, archives, single product and gallery,
cart, checkout (classic and block) and My Account.

* Parent theme: Hello Elementor (required).
* WooCommerce: supported (optional for the theme itself).
* Elementor: Free version only, optional. No Elementor Pro widgets, Theme
  Builder, WooCommerce Builder, Popup Builder or forms are used. Header,
  footer and all store templates are native theme / WooCommerce templates, so
  the store keeps working if Elementor is deactivated.
* Business logic (pricing, offers, order forms, delivery, inventory) is not in
  the theme; it belongs to the separate Top Solution Core plugin.

== Installation ==

1. Make sure the parent theme is installed: Appearance > Themes > Add New,
   search "Hello Elementor", Install (no need to activate it).
2. Appearance > Themes > Add New > Upload Theme, choose top-solution-child.zip,
   Install, Activate.
3. Settings > General > Site Language: Arabic.
4. Appearance > Menus: assign "القائمة الرئيسية" and "قائمة التذييل".
5. Appearance > Customize > Top Solution: contact data and homepage texts.
6. Contact page: Page Attributes > Template "Top Solution — اتصل بنا".
7. Categories page: Template "Top Solution — الأقسام".

== Copyright ==

Top Solution Child, (C) 2026 Top Solution. GPLv2 or later.

Bundled fonts, licensed under the SIL Open Font License 1.1:
* IBM Plex Sans Arabic, (C) 2019 IBM Corp. (assets/fonts/LICENSE-IBM-Plex-Sans-Arabic.txt)
* Noto Kufi Arabic, (C) 2019-2022 Google LLC. (assets/fonts/LICENSE-Noto-Kufi-Arabic.txt)

== Changelog ==

= 0.3.0 =
* New brand palette: Everglade #014749, Whirlpool #395C7A, Aqua Cove #53978A,
  Cornfield #C88928, Sisal Beige #C0976B, white. Replaces Moss/Mustard/Eggshell.
* Elementor kit palette upgraded (only where still at defaults or the previous theme palette).

= 0.2.0 =
* Elementor Free only: removed all Elementor Pro Theme Builder calls; own index.php.
* Elementor Free page templates (Full Width, Canvas) get a <main> landmark.
* Elementor kit receives the brand colours and fonts once, only if still at factory defaults.
* Product gallery, out-of-stock badge, cash-on-delivery note, responsive shop tables,
  My Account layouts, classic checkout two-column layout.
* Brand palette (superseded in 0.3.0): Moss, Mustard, Eggshell, white.
* Contact page template replaces the [ts_contact_cards] / [ts_trust_bar] shortcodes
  (shortcodes are plugin territory). Block editor styles added.

= 0.1.0 =
* First version.
