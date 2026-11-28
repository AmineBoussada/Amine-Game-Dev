# ADMIN_SCREENS — WordPress administration UX

Design only; maps to the modules in `docs/` (SYSTEM_ARCHITECTURE). Arabic
UI, RTL. Wireframes are desktop (≥1024); responsive notes after each.
WP admin collapses to a single column < 782 px; our layouts follow WP's
breakpoint (782) rather than the storefront's.

## 0. Navigation
Top-level menu **Top Solution**: لوحة التحكم · صفحات الهبوط · العروض ·
النماذج · الطلبات · المخزون · الموردون · التوصيل · التقارير · الإعدادات.
(Each item gated by capability; absent if not permitted.) Orders item opens
the WooCommerce orders list with a Top Solution view (screen 4 below), not a
separate order store. Admin bar shortcut: "معاينة صفحة الهبوط" on product
screens.

## A. Product editor (native WC product screen + "Top Solution" integration)
Keep the native WooCommerce "Product data" box. We add a **Top Solution
panel** below it (full-width card) with a vertical/horizontal tab set. Tabs
requested: General · Landing Page · Offers · Order Form · Product Options ·
Availability. (Supplier & Cost is a 7th tab visible only with `ts_view_costs`.)

Common chrome of the panel:
```
┌ Top Solution ───────────────────────────────  [معاينة] [نشر التعديلات] ┐
│ [عام] [صفحة الهبوط] [العروض] [نموذج الطلب] [خيارات المنتج] [التوفر] [(المورد والتكلفة)] │
│ ─────────────────────────────────────────────────────────────────── │
│  tab content                                    │ Sidebar summary     │
│                                                 │ • Status: Draft/Live│
│                                                 │ • Template          │
│                                                 │ • Form / Offers #   │
│                                                 │ • Availability      │
│                                                 │ • Checklist ✓/!     │
└──────────────────────────────────────────────────────────────────────┘
```
Sticky footer bar when unsaved changes: "لديك تغييرات غير محفوظة" + Save draft /
Publish / Discard. Tab labels show a dot when the tab has errors, a count
badge for offers. Mobile admin: tabs become a scrollable pill row; sidebar
summary moves to top as collapsible.
**Publish checklist** (sidebar): WC price set ✓, main image ✓, template
chosen ✓, order form assigned ✓, delivery available ✓, availability not
"unavailable" ✓ — warnings, not blockers, except form/availability.

### A1. General tab
Purpose: landing page master switch and summary of data coming from WC.
```
[Toggle] استخدام صفحة هبوط لهذا المنتج  (off ⇒ native WC product page)
[Info card] Data used from WooCommerce (read-only, with "edit in WC" links):
   title · price/sale · gallery · short/long description · attributes
[Select] Landing template (thumbnail grid mini) → full control in tab 2
[Text] Landing headline override (opt) · [Text] Short pitch override (opt)
[Group] SEO: title, meta description, share image
[Read-only] Permalink, last published, last editor
```
Rules: disabling keeps all config; inline notice explains consequences.

### A2. Landing Page tab (the landing page editor)
Sub-sections, left rail (inline-start) = vertical steps, right = canvas.
```
┌ Steps ──────┬ Canvas ──────────────────────────────────────────────┐
│ 1 القالب    │ [Template cards: Standard | Promo | Premium]          │
│ 2 الأقسام   │   each: thumbnail, description, "الأنسب لـ..." tag      │
│ 3 المحتوى   │   current ✓; switching shows impact dialog            │
│ 4 زر الطلب  │                                                       │
│ 5 النموذج   │                                                       │
│ 6 معاينة    │                                                       │
└─────────────┴───────────────────────────────────────────────────────┘
```
**Step 1 Template selection**: 3 large radio cards with preview image
(from the template library), name, intent, section count. Changing
template: modal listing sections that will be hidden/kept; "Keep my content"
(default) preserves data.
**Step 2 Sections (visibility + ordering)**:
```
⠿ [👁 toggle] Hero            [تعديل ▾]   (locked: cannot hide if no order_form)
⠿ [👁] المزايا
⠿ [👁] العروض                 (uses attached offers)
⠿ [👁] الوصف (من WooCommerce)
⠿ [ ] المواصفات
⠿ [👁] نموذج الطلب           (position set in step 5)
⠿ [👁] الأسئلة الشائعة
```
Drag handle + keyboard reorder (Alt+↑/↓ with announcement) + "نقل لأعلى/أسفل"
buttons for touch; toggle eye = visibility; expand chevron opens inline
editor. Section badges: "من WooCommerce" / "مخصص". Disabled/unsupported
sections for current template are greyed with reason. "Reset to template
default order".
**Step 3 Product-specific content**: accordion per section, fields by
type: headline, text (mini rich-text: bold, list, link), image picker
(media library, alt required), repeater (benefits: icon picker from
set + title + text; FAQ: Q/A), video URL (allow-listed), spec rows.
Each field shows "افتراضي من WooCommerce" with override toggle. Character
guidance counters, live mini-preview thumbnail of the section.
**Step 4 CTA configuration**: primary CTA label (default "اطلب الآن"),
secondary CTA (optional: اتصل / واتساب only if numbers configured in
settings), CTA action (scroll to form / open modal / open sheet), sticky bar
on/off + show-after-scroll, show price in sticky bar toggle, reassurance
text ("الدفع عند الاستلام") toggle, repeat CTA at end toggle.
**Step 5 Order form placement**: choose Form (select, + "إنشاء نموذج"
link), Position (radio with schematic icons: بعد الهيرو / بعد العروض /
نهاية الصفحة), Display mode (inline / modal / sticky-bar sheet) with
tiny animated schematic, mobile vs desktop override (mobile may differ),
Offers layout (cards/list), preselected offer.
**Step 6 Preview mode**: toggle between Draft/Published, device switcher
(📱 390 / 📟 768 / 🖥 1280) in an iframe (draft token URL), RTL-correct;
"فتح في تبويب جديد", "نسخ رابط المعاينة" (expires), orange banner "نسخة
تجريبية — غير منشورة" on the frontend; form submission in preview is
**disabled** (simulated, no order) with "اختبار الطلب" simulator showing
computed totals. Publish button here too; diff summary ("ما الذي تغيّر").
Responsive: rail becomes top stepper; preview iframe full-width.

### A3. Offers tab
```
[Attached offers list — drag to order, toggle active, edit/open]
 ⠿ ◉ كمية: 2 → خصم ... [قاعدة] [أولوية 10] [من..إلى] [تعديل] [فصل]
[+ إرفاق عرض موجود ▾]  [+ عرض جديد (drawer)]
[Conflict panel]: overlaps / equal priority warnings
[Mini simulator]: qty [ ] wilaya [ ] → winner offer, total
[Native price card] WC price/sale info with link
```
Drawer "New offer" reuses Offers screen editor (screen 2) fields in a
compact form. Customer choice setting: auto-best / customer selects.

### A4. Order Form tab
```
[Select form ▾] [معاينة النموذج] [تحرير في منشئ النماذج ↗]
[Overrides for this product (do not alter the shared form):]
   CTA/submit label · hide optional fields · default quantity/max quantity
   delivery methods allowed · pickup allowed toggle
[Summary: field list as read-only chips with required markers]
```
Hard rule banner: "الأسعار تُحسب في الخادم" educational note.

### A5. Product Options tab
```
[Variation options (WooCommerce attributes) — read-only list: attribute, values, variations count, link "تعديل في تبويب Attributes/Variations"]
[Display style per attribute: swatch colour / image / chips / dropdown]
   swatch mapping table (value → colour hex or image) per attribute
[Custom options (non-priced): repeater]
   label · type (text/select/radio) · required · values · help
[Notice] "الخيارات التي تغيّر السعر تُدار عبر المتغيّرات أو العروض"
```
Warnings: variation without price/stock; swatch missing mapping.

### A6. Availability tab
```
[Radio cards] متوفر (مخزون) · حسب الطلب من المورد · غير متوفر
[Stock panel (from WC)]: manage stock toggle, qty, low-stock threshold (link)
[Supplier-sourced]: lead-time text (owner wording), "يتطلب شراءً" flag note
[Per-variation table]: variation | availability | stock | lead text
[Behavior preview]: what customers see (tag + form state)
[(Cost-cap only) Supplier & cost tab separate]
```

## B. Additional screens

### B1. Landing template library (صفحات الهبوط ▸ القوالب)
```
[Header: القوالب | + (future custom)]  [Tabs: القوالب · المنتجات]
[Template cards grid ×3]: large preview image (desktop+mobile mock),
   name, description, section list chips, "عدد المنتجات المستخدمة", [معاينة نموذجية] [تعيين لمنتجات…]
[Products tab: table] المنتج | القالب | الحالة (منشور/مسودة/معطل) | آخر تعديل | إجراءات
   bulk: assign template, enable/disable, duplicate config, open preview
```
Template detail drawer: sections supported, default order, slots,
design token overrides (accent from approved palette). "Sample preview" uses
clearly labelled demo content, never real unpublished data.

### B2. Offers management
List: tabs All/Active/Scheduled/Expired/Draft; columns: name, type, scope,
priority, group, dates, used-in (# products), status, orders (30d).
Filters, search, bulk activate/archive/duplicate.
Editor (two-column):
```
Main: [Name & customer-facing label]
      [Type select: سعر مخفّض / خصم كمية / حزمة / هدية / قاعدة مخصصة]
      Type-specific block:
        qty: tier table (min qty | discount % / amount / unit price) rows ±
        bundle: item picker rows (product/variation, qty) + bundle price
        gift: gift product picker, qty, condition
        custom: rule builder (IF conditions rows / THEN actions rows)
      [Scope: products/categories/all] [Conditions: dates, geo, delivery method]
Sidebar: Status, Priority (number + hint), Group, Exclusive toggle,
         Selectable by customer toggle, Schedule, Limits,
         [Validation messages], [Simulator panel] (product, qty, wilaya → result + rejected reasons)
```
Conflict center: table of overlapping active offers with resolution
shown ("يفوز: X لأن الأولوية أعلى"). Dangerous settings flagged (price floor).

### B3. Form builder
```
┌ Field library ┬ Canvas (preview, ordered) ┬ Field settings ┐
│ text, phone…  │ ⠿ الاسم الكامل *           │ Label, placeholder│
│ wilaya, …     │ ⠿ الهاتف *                 │ Required, Locked   │
│ (drag/click)  │ ⠿ الولاية *                │ Validation preset  │
│               │ ⠿ طريقة التوصيل *          │ Width full/half    │
│               │ [+ إضافة حقل]              │ Visibility rules   │
│               │ Live RTL preview toggle    │ Maps to (WC field) │
└───────────────┴────────────────────────────┴───────────────────┘
```
Header: form name, status, usage count, [معاينة] [حفظ]. Locked core fields
show lock icon + tooltip. Visibility rule builder: "أظهر هذا الحقل إذا
[field] [يساوي] [value]". Validation errors list before save. Version
history drawer. Mobile admin: three panes become stacked tabs
(Library/Canvas/Settings).

### B4. Custom WooCommerce orders view
Not a new order store: a **view layer** on the WC orders list (HPOS).
```
[Tabs/segments]: كل الطلبات · صفحات الهبوط · بانتظار التأكيد · تحتاج شراء من المورد · مشتبه بها · COD للتسليم
[Filters]: date, status, wilaya, delivery method, template, offer, source, flags
[Table]: # | العميل + هاتف (tap-to-call) | المنتجات (thumb ×n, options) | العرض | التوصيل (wilaya/نوع) | الإجمالي | الحالة | العلامات | تاريخ
[Row actions]: عرض · تأكيد · إلغاء · طباعة بطاقة · اتصال · ملاحظة
[Bulk]: change status, print labels/lists, export (no costs unless cap)
```
Order detail: native WC screen + **Top Solution meta box** (read-only
formatted snapshots): Landing template, pricing breakdown (base → offer →
final), gifts, delivery snapshot, form answers, flags (duplicate/spam with
reason), procurement status per line, margin block (cost cap only).
Quick-actions bar: call, WhatsApp (if enabled), change status,
mark procurement. Mobile admin: card list per order with key facts.

### B5. Inventory management
```
Tabs: التوفر · سجل الحركة · مخزون منخفض · بانتظار الشراء
Availability: table product/variation | availability | stock | threshold | supplier* | lead text | quick edit
Ledger: filters (product, reason, dates, user) + table + CSV
Low stock: list + "إنشاء طلب شراء" shortcut
Pending: grouped by supplier → lines from orders → [إنشاء عملية شراء]
Tools: reconcile ledger vs WC stock (report only)
```
Inline quick-edit with undo toast. Stat tiles on top: منخفض, بانتظار
الشراء, غير متوفر. (*supplier column cost-cap/supplier cap).

### B6. Suppliers
```
Suppliers list (search, status) → detail:
 [Info card: contact (admin-only), notes]
 [Tabs] المنتجات المرتبطة · المشتريات · ملخّص التكلفة (cost cap)
Purchases: table + [+ عملية شراء]: supplier, reference, date, lines
  (product/variation, qty, unit cost), receive-to-stock checkbox
Cost settings: method (manual/last/weighted average), margin warnings
```
Permission states: without `ts_view_costs`, cost columns/tabs not rendered.
Soft-archive suppliers with history.

### B7. Delivery settings
```
Tabs: الطرق · شركات التوصيل · المناطق والأسعار · نقاط الاستلام · القواعد · المعاينة
Methods: home / pickup toggles, labels, notes
Carriers: list + status (no API in v1)
Zones & Rates: matrix editor
   rows = wilaya (expandable to communes), columns = method (+carrier), cells = fee/free/غير متاح
   bulk set, copy from, CSV import wizard (upload → map → dry-run diff → commit), coverage gaps banner
Pickup points: table + map-less address form, hours, active
Rules: free-delivery threshold, notes text
Preview: choose wilaya/commune/method/subtotal → resolved fee + explanation
```
Empty state emphasises "لا توجد بيانات توصيل — أضف أو استورد" (no seeded data).

### B8. Reports
```
Header: date range picker + compare toggle + export
Tabs: المبيعات · العروض · التوصيل/الولايات · المخزون · الهامش* (*cost cap)
Overview tiles: orders, revenue, avg order value, share by template, cancel rate
Charts: line (orders/revenue), bar (by wilaya/template/offer) with data-table toggle
Tables: sortable, drill-down to filtered orders list
```
Charts are accessible (patterns + labels + table fallback); colours from
tokens (Moss + Mustard + neutrals; status colours separate).

### B9. Dashboard (landing screen of the menu)
Tiles (today's orders, awaiting confirmation, needs purchase, low stock),
latest orders list, setup checklist (delivery configured, form assigned,
first landing page published), quick links.

### B10. Settings
Tabs: عام · الطلبات (initial status, duplicates policy) · مكافحة الإزعاج ·
الصفحات الافتراضية · الصلاحيات (role→capability matrix) · أدوات (diagnostics,
uninstall data flag, export/import settings).

## C. Admin interaction & state rules
Loading skeletons; optimistic toggles with rollback; autosave drafts for
landing config every N s (explicit publish); unsaved-guard; errors scroll
to first invalid field; success toast 4 s; destructive confirm modals; all
interactive controls keyboard accessible; empty states always include the
next action; permission-gated items hidden server-side.
