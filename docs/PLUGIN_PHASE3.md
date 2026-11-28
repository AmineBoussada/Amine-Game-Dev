# Phase 3 — Form builder (plugin v0.3.0)

## What it does
- Forms are stored as private records (`ts_form`, not public, not in REST). One form is the **default**; it is created on install/upgrade and cannot be deleted.
- Admin: **Top Solution > النماذج**: list (edit, duplicate, set default, delete) and a builder: add fields from a library, reorder with arrow buttons (keyboard/touch friendly, no drag-and-drop), edit label/placeholder/help/width/required/min/max/options/function, and **visibility rules** (all rules must match). Core fields (name, phone, wilaya, delivery method) are always present, required and always visible.
- 14 field types: text, textarea, phone, number, select, radio, checkbox, terms, quantity, wilaya, commune, delivery method, pickup point, info text.
- Each field has a *function* (maps to): customer name, phone, wilaya, commune, address, note, delivery method, pickup point, quantity, or "extra info saved with the order". Phase 5 uses these to fill the WooCommerce order.
- Phone rule (Settings > Forms): **generic** (8–15 digits, optional +; default, assumes no market) or **Algerian mobile preset** (05/06/07 + 8 digits, accepts +213/00213). Chosen by the owner; nothing is assumed.

## Security / correctness
- Server is authoritative: `FormSchema::normalize` validates every saved form (max 40 fields, unique keys, valid types/functions, required core fields, rule targets exist, no rule cycles, options non-duplicated). An invalid form is **never saved**; the editor keeps the draft and lists the reasons in Arabic.
- `SubmissionValidator` (used by Phase 5) validates a submission against the saved form: hidden fields are discarded, wilaya/commune/office/delivery method are checked against real delivery data through `ValidationContext`. No prices are accepted from the browser.
- Every admin write: POST + nonce + `ts_manage_forms`, redirect after POST. Public REST routes are read-only (`ts-core/v1/communes`, `/pickups`) and return only active, public geography.
- The shortcode `[ts_form id]` only renders for users with `ts_manage_forms` (preview) until Phase 5/6 connect it to the order flow; the front-end JS mirrors rules for usability only.

## Files
`src/Forms/*` (PhoneRule, FieldTypes, Visibility, FormSchema, DefaultForm, ValidationContext, WpValidationContext, SubmissionValidator, FormRepository, FormRenderer, Module), `src/Rest/GeoController.php`, `src/Admin/FormsPage.php`, `src/Core/Text.php`, `assets/forms.css|js`, `assets/builder.js`, settings tab "Forms", Migrator v3, `uninstall.php` (removes forms on request).

## Tests
- PHPUnit: 175 tests / 445 assertions (PhoneRule, Visibility, FormSchema, SubmissionValidator, Text, Settings…).
- Integration (real WP 7.1 + WooCommerce 11.3, fictional geography): migration to v3, default form, create/duplicate/default/delete, CPT private, REST endpoints, shortcode visibility.
- Browser (`tests/plugin/forms-ui.js`, `forms-preview-ui.js`): create form, add/move/edit field, rule, save, server refusal keeps draft, 375px no overflow, subscriber blocked, POST without nonce refused; preview: conditional fields, dependent selects over REST, validation summary; axe: preview clean, builder clean after fixing one unnamed select.
- phpcs (WordPress-Extra/Docs): clean.

## Not yet done / open
- The form is not yet connected to order creation (Phase 5) or to landing pages (Phase 6).
- Real wilaya/commune data, offices, carriers and the phone rule are the owner's decisions.
- Phone placeholder/format hint text is neutral until the phone rule is chosen.
