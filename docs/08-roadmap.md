# 08 — Roadmap (phases; each ends with report + approval gate)

| Phase | Name | Main deliverables | Gate |
|---|---|---|---|
| 0 | Planning (this) | Docs 00–09 | Your approval + answers to 09 |
| 1 | Foundation | Plugin skeleton, autoload, HPOS declaration, dependency check, child theme (RTL, tokens), migrator, capabilities, CI/lint/test setup | tests + manual activation check |
| 2 | Landing engine | Registries, product meta box, 3 templates (basic), sections, preview | render + admin QA |
| 3 | Delivery & availability | Delivery config, RateCalculator, availability states, stock log | unit tests |
| 4 | Order system | Forms, OfferEngine, PriceCalculator, OrderService, confirmation page | tamper/HPOS tests |
| 5 | Offers admin & polish | Offer UI, bundles, gifts, template polish | UX review |
| 6 | Suppliers & costs | Suppliers, purchases, cost guard, snapshots | leak tests |
| 7 | Reporting | Reports + CSV | data verification |
| 8 | Hardening & launch | Security review, performance, staging UAT, docs | go-live checklist |

(Order of 3/4 can swap if you prefer to see orders earlier.)

## Per-phase report template
Completed work · files created/modified · technical decisions · unresolved
issues · tests run + results · "Awaiting approval".
