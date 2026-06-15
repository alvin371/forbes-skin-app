# Architecture

How the monolith is wired. Read with `05-features.md` (what it does) and `03-api-surface.md` (the contract).

## 1. Two controller styles

The codebase mixes two controller base classes — a strong signal of the two-product history.

### `CI_Controller` (legacy CodeIgniter 3 style)
Used for **APIs and utilities**. No automatic auth/permission gate. Dependencies loaded manually in the constructor.
- `Api`, `Api_v2`, `Api_v3`, `Api_hrms`, `Api_performance`, `Ajax`, `Auth`, `AttendanceController`, `Docs`, `PublicController`, the `approvals/*` controllers, `TiktokAuth`, migration/seed utilities.

### `BaseController` (CI4-ish pattern) — `application/core/BaseController.php`
Used for **feature/web modules**. The constructor (`BaseController::__construct`) does heavy lifting on **every request**:
1. Loads `sentry` helper, `permission` + `template` libraries.
2. `init_user_data()` — reads `$_SESSION['user']`; **redirects to `auth/login` if absent**. Sets `$this->user_id`, `$this->user_data`, and tags Sentry.
3. `get_module_name()` — maps controller class → module via `$controller_module_map` (e.g. `endorse`→`endorse_campaign`, `dashboard`→`dashboard`). Special cases: `ads` resolves by `?m=` platform param; `crm` resolves by `?brand=` param.
4. `check_method_permission()` — maps method → action via `$method_action_map` (`index/item/detail`→view, `store`→create, `update`→edit, `delete`→delete, `approve`→approve), then calls `permission->check_permission($user_id, $module, $action)`. On failure: HTTP 403 + renders `errors/html/error_403` and `exit`.
   - **Bypass list** (hardcoded): controllers `auth, ajax, api, api_v2, api_v3` skip the permission check entirely.

Helper methods child controllers use: `set_public_methods()`, `set_method_permissions()`, `require_permission()`, `has_permission()`, `get_permission_data()` (returns `can_view/create/edit/delete/approve` for views), `require_roles()`, `require_ajax_permission()` (returns JSON 403 for AJAX).

> **Migration note:** permission enforcement is **coupled to the controller base class** and to `$_SESSION`. An API-first rebuild must lift this into middleware/policies that work statelessly (JWT-derived identity), not session-derived.

## 2. Request lifecycle

```
index.php (sets ENVIRONMENT, Asia/Jakarta TZ, error_reporting)
  → CI bootstrap → autoload → routes.php → Router → Controller
```

- **`index.php`** — defines `ENVIRONMENT` (currently `development`, with `error_reporting(0)`), timezone `Asia/Jakarta`.
- **Autoload** (`application/config/autoload.php`):
  - Libraries: `database`, `email`, `session`, `template`, `telegrambot` (all loaded on every request).
  - Helpers: `url`, `file`, `attachment`.
  - Models: `mymodel` (the shared CRUD model).
- **Routing** (`application/config/routes.php`, 385 lines): `translate_uri_dashes = TRUE` (URL dashes → method underscores), default controller `home/index`, 404 override `page/error`. Routes are grouped by era with `// NEW` and `// OLD` comment markers — the `OLD` block is the legacy `Api` marketplace surface. Some routes are HTTP-verb-scoped (`['get']`, `['post']`, `['put']`, `['delete']`) for `Api_performance` and a few others.
- **Hooks** (`application/config/hooks.php`): lifecycle hooks (e.g. Sentry/bootstrap). Composer autoload is **disabled** in `config.php`; composer classes are pulled in where needed.

> **Duplicate-route gotcha:** `cronjob/expense` is declared twice (`Expense/generate_recurring_expense` then `Api_v3/generate_recurring_expense`) — the **last declaration wins → `Api_v3`**. Watch for these when reasoning about behavior.

## 3. Authentication & authorization — three coexisting schemes

| Scheme | Where | Mechanism |
|---|---|---|
| **Web session** | All `BaseController` web modules + `Auth` | `$_SESSION['is_login']`, `$_SESSION['user']`. Login in `Auth` controller. Hybrid password verify: MD5 (legacy) auto-upgraded to `password_hash()` on login. |
| **JWT (HS256)** | `Api_hrms` only | `ApiAuth` library (`application/libraries/ApiAuth.php`). `authenticate()` reads `Authorization: Bearer <jwt>`; **falls back to session** if no bearer header. Access token TTL `API_JWT_TTL_MIN` (30m). Refresh tokens hashed (sha256) in **`api_refresh_tokens`** table, TTL `API_REFRESH_TTL_DAYS` (30d). PIN/2FA gate (`pin_setup/verify/reset`, lockout via `PIN_MAX_ATTEMPTS`/`PIN_LOCKOUT_MINUTES`). JWT payload carries `sub, name, email, role, role_id`. |
| **Worker secret + IP allowlist** | `Api_v2` (cronjobs, marketplace callbacks), `Seed` | Shared secret + `WORKER_IP_ALLOWLIST` env. Gate logic lives in `Api_v2.php` and `Seed.php`. |

### RBAC
- Library: `application/libraries/Permission.php` (with permission caching + legacy fallback).
- **Primary permission table: `user_module_permissions`** (`can_view/can_create/can_edit/can_delete/can_approve` per `user_id` + `module_name`) — confirmed in `BaseController::check_method_permission()` debug query.
- Supporting tables: `roles`, `user_roles` (user→role_id, `roles.is_active`), `modules`, `role_permissions`. `ApiAuth` derives a user's primary `role_id` by joining `user_roles → roles` (active only).
- **Sidebar/module registry:** `application/helpers/sidebar_registry_helper.php` — `sidebar_registry()` returns the canonical list of modules (display names, controller mapping, category, sort order, required action, active flag, parent/child). This is the **source of truth for navigation + which modules exist**; categories: System Mgmt, Marketing, Order & Customer, Operasional, HR Mgmt, Admin, User Mgmt.

### Filters (CI4-style, in libraries/)
`AuthFilter`, `AdminAuthFilter`, `ApproverAuthFilter`, `HrAdminFilter` — used by HRMS controllers for role/route gating beyond the `BaseController` module check.

## 4. Service / engine layer — the structured core (preserve in migration)

The HRMS half extracted real business logic into service classes. **This is the part worth carrying over largely intact** — controllers are thin wrappers around these.

| Library | Responsibility |
|---|---|
| `ApprovalWorkflowEngine.php` | Drives multi-step approval chains: builds instances, advances steps, handles approve/reject/skip, completion. |
| `ApprovalRouteResolver.php` | Given a request (leave/overtime) + its attributes, resolves the applicable **versioned route** by matching `approval_route_scopes`. Produces the route snapshot. |
| `OvertimeWorkflowEngine.php` | Overtime equivalent of the approval engine. |
| `LeaveQuotaService.php` | Leave balance math: deduct/restore/adjust, ledger writes, quota logs. |
| `LeaveCalculatorService.php` / `LeaveOverlapService.php` | Working-day counting (holidays-aware) + overlap detection. |
| `OvertimeCalculatorService.php` | Overtime hour calculation. |
| `AttendanceEligibilityService.php` | Geofence eligibility: haversine distance, accuracy, office radius, CIDR/IP checks. |
| `NotificationService.php` / `OvertimeNotificationService.php` | Email / in-app / Telegram notifications for workflow events. |
| `RequestNoGenerator.php` | Unique `request_no` generation (with retry). |
| `UploadService.php` | File upload validation + storage (paired with `attachment_helper.php`). |
| `EndorseRefreshQueueService.php` / `Endorse_sync.php` | Marketing side: endorse refresh queue + social profile sync. |
| `Scrapingbot.php` / `Telegrambot.php` | External API wrappers. |
| `Template.php` (**1,653 lines**) | God-object utility lib: date/number formatting, pagination, cURL helpers, **marketplace API helpers**, **social-media scraping + parsing** (`parseTiktokProfileResponse`, `parseInstagramProfileResponse`, `enqueue_scrape`, `process_scrape_result`), session/alert helpers. **Major refactor target** — business logic hides here. |

## 5. Data access

- Shared model `Mymodel` (autoloaded) provides generic CRUD: `selectData`, `selectWhere`, `selectDataone`, `selectDatarows`, `insertData`, `updateData`, `deleteData`, and `selectWithQuery($rawSql)` — the **centralized raw-SQL entry point** (and the central SQL-injection risk; see `07-config-and-security.md`).
- HRMS models (`LeaveRequestModel`, `ApprovalStepModel`, etc.) are purpose-built with parameterized queries and joins.
- Marketplace/finance controllers (`Dashboard`, `Ads`, `Ajax`) lean on `selectWithQuery()` with string-concatenated SQL.

## 6. Caching

- Dashboard uses **Memcached with file fallback**, 60s TTL, MD5-hashed cache keys on parameters (`calculate_ads_spending`, `calculate_kol_spending`, `calculate_etc_spending`), plus 60s caches for brands/channels lists.
- `predis/predis` is available for Redis but Dashboard caching is Memcached-first.

## 7. Background work model

No queue daemon — **cronjobs hit HTTP endpoints** (gated by worker secret/IP). See `03-api-surface.md` §cronjobs and `05-features.md` for the scraping queue (submit→poll→enqueue) and endorse refresh queue. The TikTok order sync can offload to a **standalone worker** (`node` or `rust` mode, `TIKTOK_WORKER_MODE`) documented under `docs/tiktok-worker/`.
