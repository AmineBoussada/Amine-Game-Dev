=== Tassili Immobilier ===
Contributors: tassiliimmobilier
Requires at least: 6.4
Tested up to: 6.7
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Tags: custom-colors, custom-logo, custom-menu, featured-images, rtl-language-support, translation-ready, two-columns, threaded-comments

A bilingual French / Arabic real estate theme for Algerian agencies: property listings, advanced search, galleries, WhatsApp contact, visit requests and lead capture.

== Description ==

Tassili Immobilier turns WordPress into a property website for an Algerian agency.

* Listings (custom post type) with price in DA and in "milliards", surface, typology (F3, F4…), documents (acte notarié, livret foncier…), features, photos, agent and a French and Arabic version of the title and description.
* Search by transaction, wilaya and commune (all 58 wilayas included), type, typology, price, surface, documents and features. Results update in place, and every search has a shareable link. It also works without JavaScript.
* French and Arabic with full right-to-left layout. Visitors switch language from the header; the choice is remembered.
* Light and dark mode, responsive from 360 px up.
* WhatsApp button on every listing with the reference pre-filled, plus a floating button.
* Contact form and visit-request form. Requests are e-mailed and saved in the back office (Properties > Leads). Anti-spam: nonce, honeypot and rate limit.
* Favourites, kept in the visitor's browser.
* Customizer settings for phone, WhatsApp, address, hours, social links, hero image and figures.
* Optional demo content: 16 sample listings, 4 agents, 3 testimonials and the standard pages.

== Installation ==

1. In WordPress, go to Appearance > Themes > Add New > Upload Theme and choose the zip file.
2. Activate the theme.
3. Go to Appearance > Demo content and click "Import demo content" to see it with sample listings, or add your own under Properties.
4. Go to Appearance > Customize > Tassili Immobilier and enter your phone, WhatsApp number, e-mail and address.
5. Go to Settings > Permalinks and choose "Post name" if it is not already selected.

== Frequently Asked Questions ==

= How does the language switch work? =

The theme serves French and Arabic itself. A visitor's choice (?lang=ar or ?lang=fr) is stored in a cookie. Listings, agents, testimonials, wilayas and other terms have an Arabic field next to the main one. If you use a page-caching plugin, make it vary the cache by the "lang" query argument and the "tassili_lang" cookie.

= Where are the requests sent? =

To the address set in Customize > Agency details > "Send requests to", or the site administrator's address if it is empty. Every request is also stored under Properties > Leads, which only administrators can see.

= What happens to the listings if I switch themes? =

The post types are registered by the theme, so listings are hidden while another theme is active. They stay in the database and come back when this theme is activated again.

= Can I use real photos? =

Yes. Set the featured image of a listing and add more under "Photo gallery". Listings without photos show built-in illustrations.

= Why is the theme not in the WordPress.org directory? =

It registers custom post types and taxonomies, which the directory only allows in plugins. This is a deliberate choice so that a single upload installs everything.

== Changelog ==

= 1.0.0 =
* First release.

== Resources ==

* IBM Plex Sans and IBM Plex Sans Arabic, copyright IBM Corp., SIL Open Font License 1.1, https://github.com/IBM/plex
* Marcellus, copyright Astigmatic, SIL Open Font License 1.1, https://fonts.google.com/specimen/Marcellus
* Reem Kufi, copyright The Reem Kufi Project Authors, SIL Open Font License 1.1, https://github.com/aliftype/reem-kufi
* The placeholder illustrations in assets/img/placeholders and the icons are original work released under the GPL v2 or later.

Copyright (c) 2026 Tassili Immobilier. The theme is distributed under the terms of the GNU General Public License v2 or later.
