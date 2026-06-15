# Migration Context — Overview & Index

> **Audience:** This doc set is reference context for planning a migration toward an **API-first backend + separate SPA/mobile frontend**. It is *not* a migration plan — it captures the current system in enough detail that a planning agent (Claude Fable) can design the bigger migration without re-reading the whole codebase.

## What this application is

The repo (`forbes-skin-app`, product name **Acneno**) is **two products fused into one CodeIgniter 3 monolith**, sharing one database, one auth/session layer, one template shell, and one routing table:

1. **Marketplace / Marketing / Finance suite** — the *original* product. Shopee / Lazada / TikTok Shop / Meta integration, order & product ingestion, inventory, endorse/influencer campaigns, social-media scraping, and finance dashboards (HPP, net profit, ad-spend aggregation).
2. **HRMS** — the *newer, more structured* product. Attendance (geofenced), leave, overtime, a versioned dynamic approval-routing engine, performance appraisal, and RBAC. This half has real DB migrations, service/engine libraries, model classes, a JWT API, and an OpenAPI spec — it is the cleaner half to migrate.

Both halves are at very different maturity levels. The HRMS half already has a JSON API (`Api_hrms`) consumed by a mobile/SPA client; the marketplace/marketing half is largely **server-rendered + AJAX HTML fragments** with worker-style JSON endpoints for cronjobs.

## Tech stack snapshot

| Layer | Current |
|---|---|
| Framework | CodeIgniter 3.x (with some CI4-style patterns) |
| Language | PHP 8.x (composer `platform.php` pinned to 8.4.0) |
| DB | MySQL via `mysqli` driver, CI Query Builder + heavy raw SQL |
| Server | Apache + mod_rewrite (XAMPP heritage) |
| Frontend | Server-rendered PHP views, Bootstrap 5.2.3, jQuery, ag-grid, Chart.js, D3, Select2 |
| Auth (web) | PHP session (`$_SESSION['is_login']`, `$_SESSION['user']`) |
| Auth (API) | HS256 JWT (`ApiAuth`), session fallback; worker IP+secret for cron/marketplace |
| Cache | Memcached → file fallback (Dashboard); Redis available (`predis`) |
| Error tracking | Sentry (`sentry/sentry ^4.18`) |
| Key composer deps | `appolous/lazada-php-sdk`, `phpoffice/phpspreadsheet`, `illuminate/support`, `predis/predis`, `sentry/sentry`, `kint-php/kint` |

Scale: ~70 controllers, 21 models, 24 libraries, ~55 view module directories, 19 DB migrations (HRMS only), 6556 graph nodes / 726 files.

## Target architecture (the direction this migration serves)

**API + separate SPA/mobile frontend.** Implications that shape every doc below:
- The **API surface** (`03-api-surface.md`) is the highest-value artifact — it defines the contract a new frontend consumes.
- Server-rendered screens (`04-web-frontend-and-views.md`) must be inventoried for **API-fication** — which already have JSON backing vs which only return HTML fragments.
- **Auth must be unified** — today there are three schemes (session, JWT, worker-secret). See `08-migration-notes.md`.
- Business rules currently living *inside controllers and the `Template` library* must be surfaced (`05-features.md`) so they survive the move to a thin API + thick service layer.

## Suggested reading order for the planning agent

1. **00-overview.md** (this file) — orientation.
2. **01-architecture.md** — how the monolith is wired (controllers, auth, services).
3. **05-features.md** — what the system *does* (domain business rules).
4. **02-data-model.md** — the data it does it to.
5. **03-api-surface.md** — the existing contract (most important for API+SPA).
6. **04-web-frontend-and-views.md** — what still needs API-fication.
7. **06-integrations.md** — external dependencies and their failure modes.
8. **07-config-and-security.md** — env, config, security debt.
9. **08-migration-notes.md** — synthesized risks, sequencing, and decisions for Fable.

## Module map (by domain)

| Domain | Controllers (representative) | Status |
|---|---|---|
| HRMS — Attendance | `Api_hrms`, `AttendanceController`, `AttendancePageController`, `AttendanceReport`, admin/`AttendanceSettingsController` | API + web |
| HRMS — Leave | `Api_hrms`, `LeaveController`, admin/`LeaveTypesController`/`LeaveQuotasController`/`LeaveRequestsController` | API + web |
| HRMS — Overtime | `Api_hrms`, `Overtime`, approvals/`OvertimeApprovalController` | API + web |
| HRMS — Approvals | `Api_hrms`, approvals/`ApprovalInboxController`/`LeaveApprovalController`, admin/`ApprovalRoutesController` | API + web |
| HRMS — Performance | `Api_performance`, `Api_hrms`, admin/`PerformanceAppraisal` | API + web |
| HRMS — Admin/Org | admin/`Offices`, admin/`HolidaysController`, `Roles`, `Modules`, `User`, `Position` | web |
| Marketplace | `Api`, `Api_v2`, `Api_v3`, `Transaction`, `Product`, `Stock`, `Shipping`, `Marketplace*` | mixed |
| Marketing/Endorse | `Endorse`, `Endorse_campaign`, `Influencer`, `Review_endorse`, `Scraper`, `Ajax` | web + cron |
| Finance | `Dashboard`, `Expense`, `Payment`, `Ads`, `Overview`, `Report` | web + cron |
| Integrations | `Googlemeet`, `Googlemou`, `TiktokAuth`, `Meta_account`, `Marketplace_account` | web/OAuth |
| Engagement/CRM | `Quest`, `Quest_level`, `Profile`, `Crm`, `Customer`, `Group_wa`, `Milestone`, `Interview`, `Recruitment` | web |

## Existing documentation — reuse, don't duplicate

This migration doc set deliberately does **not** repeat what already exists. Point Fable at these:

| Existing artifact | Covers |
|---|---|
| `DESIGN.md` (repo root) | Visual design system: colors, typography, design principles for the Acneno UI rebuild |
| `PRODUCT.md` (repo root) | Product purpose, user personas (HR admins, owners, employees), brand direction |
| `docs/openapi/hrms.yaml` | OpenAPI 3 spec for the HRMS API; served live at `/docs/hrms` (Swagger UI) via `Docs` controller |
| `docs/SCRAPINGBOT_INTEGRATION_GUIDE.md` | ScrapingBot social-media scraping architecture (queue, cron, parsers) |
| `docs/INFLUENCER_RAPIDAPI_INTEGRATION.md` | Legacy RapidAPI TikTok scraping integration |
| `docs/ENDORSE_REFRESH_GUIDE.md` + `ENDORSE_REFRESH_*` | Endorse refresh queue design, cron cloning, runbook, trace |
| `docs/performance-appraisal/` | Performance appraisal module deep-dive |
| `docs/tiktok-worker/` | Standalone TikTok order-sync worker (node/rust) |
| `CLAUDE.md` (repo root) | High-level dev guidance + `code-review-graph` MCP usage |

## Files in this doc set

- `01-architecture.md` — controller styles, request lifecycle, auth layers, service/engine layer.
- `02-data-model.md` — tables by domain, migration system, legacy schema gaps.
- `03-api-surface.md` — full endpoint catalog, auth per endpoint, response conventions.
- `04-web-frontend-and-views.md` — views, templates, frontend stack, SPA-readiness.
- `05-features.md` — domain-by-domain functional spec & business rules.
- `06-integrations.md` — external services, tokens, refresh flows.
- `07-config-and-security.md` — env catalog, config files, security posture.
- `08-migration-notes.md` — API+SPA guidance, risks, sequencing for Fable.
