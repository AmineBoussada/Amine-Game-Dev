# 01 — Scope & Requirements

## Business
Single-vendor Algerian store: interior decoration and home improvement
(homes, kitchens/bathrooms, offices, commercial shops). Direct online sales,
**Cash on Delivery only**, home delivery or delivery-office pickup. Stock may
be physical or sourced from suppliers after the order.

## Release 1 language
Arabic only, full RTL, storefront **and** wp-admin screens of our plugin.
All strings still go through i18n functions (text domain `top-solution`) so
other languages can be added later without refactoring.

## Modules
| Module | Plugin | Summary |
|---|---|---|
| Landing Pages | `top-solution-core` | Per-product marketing page, 3 templates, sections, preview |
| Top Solution Order | `top-solution-core` | Product order form, offers, COD order creation, confirmation page |
| Business Core | `top-solution-core` | Inventory/availability, suppliers, purchase costs, delivery, reports |
| Global design | child theme | Typography, RTL, header/footer, tokens only — no business logic |

## Functional requirements (summary)
**Landing pages**: per-product enable + template choice (Standard Product,
Promotional Offer, Premium Product); edit product-specific content; show/hide/
reorder supported sections; order-form position and display mode; preview
before publish; uses WooCommerce product data (title, price, images, stock,
description) wherever possible.

**Order**: per-product form; dynamic product options (variations/attributes);
configurable fields; promotional offers, quantity discounts, bundles, optional
free gifts; creates a normal WooCommerce order (COD); custom confirmation page;
WooCommerce order number; product + offer snapshot stored on the order.

**Business**: inventory, suppliers, purchase costs, availability states,
delivery configuration + pricing, reporting.

## Non-goals (release 1)
Online payment gateways, multi-vendor, multi-language UI, delivery-company API
integrations, marketplace features. (Can be revisited; none are requested.)

## Definition of "commercially usable"
An order placed on a landing page is a correct WooCommerce order with
correct server-computed totals, stock effect, delivery cost, and traceable
offer data; the admin can see and fulfil it using standard WooCommerce screens.
