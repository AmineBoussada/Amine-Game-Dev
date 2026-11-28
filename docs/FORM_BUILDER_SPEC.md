# FORM_BUILDER_SPEC

Decision C: configurable form builder. Forms are reusable entities (`ds_form`
CPT) referenced by products; independent from templates and from pricing.

## 1. Form entity
`name`, `fields[]` (ordered), `settings` {submit_label, success behaviour,
anti-spam level, show_summary, terms checkbox}, `version`. A default form
(core fields only) is created on activation; its fields are config the owner
can edit. A product may use a form, else the global default form.

## 2. Field model (JSON)
```
{ "key":"full_name", "type":"text", "label":"...", "placeholder":"", "help":"",
  "required":true, "locked":true, "order":1, "width":"full|half",
  "validation":{ "min":2,"max":80,"pattern":"preset|regex-id" },
  "visibility":{ "mode":"always|conditional",
                 "rules":[{"field":"delivery_method","op":"eq","value":"home"}] },
  "options":[{"value":"","label":""}],   // select/radio/checkbox
  "maps_to":"billing_first_name|billing_phone|billing_state|billing_city|billing_address_1|order_note|meta" }
```
`locked` core fields: cannot be deleted/unrequired (customer name, phone,
wilaya, delivery method; commune/address required per delivery rule).

## 3. Field types
text, textarea, phone (normalising), number, select, radio, checkbox,
checkbox group, quantity (bounded), wilaya select (from `ds_geo_units`),
commune select (dependent on wilaya), delivery method (home/pickup, from
Delivery config), pickup point select (dependent), hidden (server-set only,
never trusted), heading/divider/info text, product option (see §7),
terms acceptance. Date/file fields: not in release 1.
Custom types register via `FieldTypeRegistry` (render, sanitise, validate,
export) — one class per type.

## 4. Validation (server authoritative; client mirrors for UX)
Per type sanitiser + validator, then field rules, then cross-field rules.
- Required, length, numeric ranges, allowed-option membership (select values
  must exist in options; geo ids must exist and be active; pickup point must
  belong to chosen geo/method).
- Phone: normalise to canonical form, validate against admin-configured
  patterns (formats to be supplied by owner, Q11).
- Conditional fields: hidden fields are *not required and discarded
  server-side*; visibility rules evaluated on server with submitted values.
- Unknown submitted keys rejected; max field count/length; UTF-8 normalise.
- Output: field-level Arabic error messages keyed by field key.

## 5. Conditional visibility
Ops: eq, neq, in, empty, not_empty. Combine with AND (OR groups optional,
later). Cycles detected at save. Same rule engine shared JS/PHP spec
(PHP is authoritative).

## 6. Builder UX (Forms menu)
List forms · edit: drag-and-drop ordering, add from field library, per-field
panel (label, required, validation, visibility, mapping), live preview (RTL),
duplicate form, usage view (which products use it), version history of last
N saves. Save validates schema (unique keys, valid mappings, no cycles).

## 7. Product option architecture
Two kinds, both resolved on the server:
1. **Variation options (native)**: WC attributes → variation. Rendered as
   swatches/selects from product data; submitted as attribute values; server
   resolves variation id, price, stock. Price differences come only from WC.
2. **Custom non-priced options** (`_ds_option_defs` on product): e.g.
   text note, choice list that does not change price. Stored as line item
   meta `_ds_options`. **Priced add-ons are out of release 1** (would be
   modelled as offer/bundle items; avoids a parallel pricing path).
A form includes a `product_option` field per option group; the product's
available groups are provided by the Product module, so one form works for
many products.

## 8. Mapping to WooCommerce order
`maps_to` fills billing/shipping fields; `order_note` → customer note;
`meta` → `_ds_form_answers` (label+value snapshot). Phone stored in
`billing_phone`; country fixed `DZ`; state from wilaya mapping (WC state
code mapping table in Geography config; Q8).

## 9. Security
Nonce-like form token, honeypot field injected by renderer, rate limits,
max payload, no HTML in values (stored escaped as text), autocompletion
attributes, labels linked for accessibility, no PII in client logs.

## 10. Testing
Per-type validation matrix, conditional visibility truth tables, dependent
selects integrity (tampered commune), snapshot rendering RTL, form version
change vs in-flight submission (old version accepted until TTL expiry).
