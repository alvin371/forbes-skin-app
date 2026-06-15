# Migration Notes (for Claude Fable)

Synthesis layer. The other docs describe *what is*; this one flags *what to decide* for the **API + separate SPA/mobile frontend** target. Not a plan — inputs to a plan.

## The central fact

This is **two products at two maturity levels** sharing one DB and one auth/session layer. They should probably migrate on **different tracks**:

- **HRMS** — already has a JWT API (`Api_hrms`), an OpenAPI spec, real migrations, a clean service/engine layer, and purpose-built models with parameterized queries. **Lowest-risk, highest-readiness.** A SPA/mobile client already consumes it. Migrate first / extract first.
- **Marketplace + Marketing + Finance** — server-rendered, raw-SQL-heavy, no schema in repo, business logic buried in controllers and the `Template` god-lib, HTML-fragment "APIs". **Highest-risk.** Needs schema reverse-engineering and a from-scratch API layer.

## API-fication inventory

| State | Surface | Action |
|---|---|---|
| ✅ Real JSON API | Employee/approver HRMS flows (`Api_hrms`), performance (`Api_performance`) | Stabilize contract, version it, point SPA at it. |
| 🟡 Ad-hoc JSON / partial | attendance report `data_json`, `approvals/*` web AJAX, `endorse/queue*` | Normalize to one envelope + status codes. |
| 🔴 HTML-fragment only | all admin CRUD, marketplace (`Transaction/Product/Stock/Shipping`), marketing (`Endorse/Influencer/Overview`), finance (`Dashboard/Expense/Report`) | Build new JSON endpoints; extract logic from `Template` lib + controllers. |

## Decision points to resolve

1. **Auth unification.** Today: session (web) + JWT (HRMS, with session fallback) + worker-secret/IP (cron/marketplace). Target a single model:
   - Bearer JWT for all user-facing API calls (drop the session fallback in `ApiAuth`).
   - Service/worker auth (mTLS, signed tokens, or scoped API keys) for cron/marketplace/worker — replace the shared-secret+IP allowlist.
   - Decide JWT signing: stay HS256 vs move to RS256 if multiple services verify tokens.
2. **RBAC relocation.** Lift `BaseController`'s `user_module_permissions` check out of the controller constructor into stateless API middleware/policies. Preserve the `module_name + action(view/create/edit/delete/approve)` model — it's coherent. Keep `sidebar_registry_helper.php` as the module catalog source.
3. **Approval engine consolidation.** Merge `ApprovalWorkflowEngine` + `OvertimeWorkflowEngine` into one generic engine keyed by `request_type` (the schema already supports it via `approval_route_versions.request_type`). Preserve: route versioning, JSON snapshot on submit, optimistic-locking (`approval_steps.version`), `NEEDS_ROUTE` fallback.
4. **The `Template` god-library (1,653 lines).** It mixes presentation, marketplace API calls, scraping, parsing, formatting. Decompose into: API-client services, scraping/parsing services (port parsers verbatim — shape-sensitive), and frontend-side formatting (moves to SPA).
5. **Schema source of truth.** HRMS has migrations; the marketplace/marketing half does **not**. Before touching it, `SHOW CREATE TABLE` the live DB for: `transaction, product, product_3rd, stock, brand, marketplace_config, expense(+categories), shopee_ads_data, meta_ads_data, tiktok_ads_data, gmv_spend_data, endorse, endorse_campaign, payment_logs, influencer, scraping_queue, group_wa, user, roles, user_roles, modules, role_permissions, user_module_permissions, api_refresh_tokens`. Adopt a single migration framework for the new backend.
6. **Background jobs.** Cron currently = HTTP endpoints behind worker secret. Target a real queue/worker (Redis is already available via `predis`). Keep the async submit→poll→enqueue model for scraping and the endorse refresh queue — synchronous calls will blow the ScrapingBot budget.
7. **Caching/state for horizontal scale.** File sessions + Memcached-first dashboard cache block stateless scaling. Move sessions to JWT (stateless) and cache to Redis.

## Legacy to retire (don't port)

- `Api` controller (entire `// OLD` route block): legacy per-platform marketplace auth/get/refresh, legacy cronjobs, `webhook-api`/`webhook-test`. Superseded by `Api_v2`/`Api_v3`.
- Old simple **`approval_routes`** table — superseded by versioned `approval_route_versions` (+ scopes/steps). Migration `...410120000` already unified sources.
- `Influencer_dummy` / `cronjob_influencer_dummy` — test scaffolding.
- One-off migration/diagnostic controllers (`FixLeaveStatusController`, `MigrateLeaveStatusController`, `MigrateUserModulePermissions`, `DiagnosticController`, `SchemaBootstrap`, `SeedRunner`, `MigrationRunner`) — operational utilities, not product surface.
- RapidAPI scraping path — replaced by ScrapingBot.

## Security checklist (carry into new backend)

- [ ] Parameterize every query; eliminate `Mymodel::selectWithQuery()` raw-SQL sink. Priority: `Dashboard` `$_GET['brand']`→`LIKE` injection, date-range interpolation, `OvertimeRequestModel` `where()` concat.
- [ ] Force-migrate/reset all legacy MD5 passwords; drop the MD5 path.
- [ ] Strong `API_JWT_SECRET`; decide HS256 vs RS256; remove session fallback in API auth.
- [ ] Re-enable output escaping (SPA renders; API never emits HTML).
- [ ] Move secrets out of `.env` into a secrets manager; rotate `TIKTOK_BC_ACCESS_TOKEN` and any committed-by-accident creds.
- [ ] `CI_ENV=production`, `save_queries=FALSE`, `db_debug=FALSE` in prod.
- [ ] Audit each `Api*` controller actually enforces auth (the `BaseController` bypass list exempts them).

## Suggested sequencing (input, not gospel)

1. **Foundation:** stand up the new API backend skeleton + unified auth (JWT) + RBAC middleware + Redis sessions/queue. Reverse-engineer & codify the full DB schema into migrations.
2. **HRMS first (proven):** port the service/engine layer (approvals, leave/overtime quota, attendance eligibility, performance) behind a clean, versioned API matching/extending `docs/openapi/hrms.yaml`. Repoint the existing mobile/SPA client.
3. **SPA shell:** build the new frontend per `DESIGN.md`, consuming the HRMS API; retire `TemplateDashboard.php` for HRMS screens.
4. **Marketplace/marketing API-fication:** build JSON endpoints for `Transaction/Product/Stock/Shipping`, then `Endorse/Influencer/Overview`, then `Dashboard/Expense/Report`. Extract logic from `Template` lib + controllers; keep async queues; migrate `marketplace_config` tokens intact (coordinate OAuth redirect-URI cutover, §integrations).
5. **Decommission:** remove the legacy `Api` surface, old approval table, dummy/utility controllers, RapidAPI path.

## Quick reference

- Architecture/wiring → `01-architecture.md`
- Tables & migration system → `02-data-model.md`
- Endpoint catalog & auth → `03-api-surface.md` (+ `docs/openapi/hrms.yaml`)
- Frontend inventory & SPA gaps → `04-web-frontend-and-views.md`
- Business rules → `05-features.md`
- Integrations & tokens → `06-integrations.md`
- Env, config, security debt → `07-config-and-security.md`
- Design system / personas → `DESIGN.md`, `PRODUCT.md`
