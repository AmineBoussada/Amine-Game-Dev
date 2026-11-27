# Top Solution — Planning Documentation (Phase 0)

Status: **DRAFT — awaiting approval. No implementation code exists yet.**

| # | Document | Purpose |
|---|----------|---------|
| 01 | [Scope & Requirements](01-scope-and-requirements.md) | What is in/out of release 1, per-module requirements |
| 02 | [Architecture](02-architecture.md) | Plugin/theme split, modules, layering, boundaries |
| 03 | [Data Model](03-data-model.md) | WooCommerce data used, custom meta, custom tables, order meta |
| 04 | [Landing Pages](04-landing-pages.md) | Templates, sections, editing, preview |
| 05 | [Order System](05-order-system.md) | Order form, offers, server-side pricing, COD flow |
| 06 | [Core Business](06-core-business.md) | Inventory, suppliers, costs, delivery, reporting |
| 07 | [Security & Quality](07-security-and-quality.md) | Validation, privacy of costs, HPOS, testing, conventions |
| 08 | [Roadmap](08-roadmap.md) | Phases, deliverables, acceptance gates |
| 09 | [Open Questions](09-open-questions.md) | Business facts we must NOT invent; decisions needed from you |

Rule for all documents: no business data (products, prices, suppliers,
delivery companies, wilaya lists, fees) is invented here. Anything unknown is
listed in 09 and will be entered as configuration, not hard-coded.
