# Features & Business Rules

Domain-by-domain functional spec. Focus is on **rules that aren't obvious from the schema** — the logic that must survive migration. Cross-ref: `02-data-model.md` (tables), `01-architecture.md` (the service layer that implements these).

---

## HRMS — Attendance

**Check-in/out eligibility** (`AttendanceEligibilityService`, `attendance_helper.php`):
- Geofence: `haversine_meters()` distance between device lat/lng and the `offices` row must be ≤ `offices.radius_m` (default 150m); GPS `accuracy` must be ≤ `offices.min_accuracy_m` (default 50m).
- IP gating: `ip_in_cidrs()` checks request IP against `offices.allowed_ip_cidrs` (CIDR list) — and WiFi BSSID/SSID matching (columns added later).
- Dedup: `Attendance_log_model::has_recent_log()` (time-window) and `has_type_today()` prevent duplicate IN/OUT per day.
- **Out-of-town** flow: separate endpoints bypass office geofence with proof (`attendance_office_proof`), stored with `attendance_category`/`attendance_reason`/`notes(JSON)`.
- Late/early: `attendance_settings` defines `check_in_start/end`, `check_out_start/end`, `grace_period_minutes`; the `attendance_reason` endpoint captures justification when outside window.
- `special_schedule` flag supports non-standard shift days.

## HRMS — Leave

**Lifecycle** (`LeaveRequestModel`, `LeaveQuotaService`, `ApprovalWorkflowEngine`):
1. Create (`SUBMITTED`/`PENDING_APPROVAL`) → generates `request_no` (`RequestNoGenerator`, unique with retry).
2. Submit → `ApprovalRouteResolver` resolves the applicable versioned route, snapshots it into `approval_instances.route_snapshot`, and seeds `approval_steps`.
3. Each approver acts → `ApprovalWorkflowEngine` advances `current_step`; on final approve → `APPROVED`.
4. On approval, quota is deducted and written to `leave_ledger`; `leave_quota_logs` records the delta (`DEDUCT`). Cancel/reject restores quota (`RESTORE`).
- **Working-day counting** (`LeaveCalculatorService`) excludes `holidays` (active) and weekends.
- **Overlap** detection (`LeaveOverlapService`) blocks conflicting date ranges.
- Quota defaults auto-seeded per user from `leave_types.default_quota_days` (`LeaveQuotaModel::apply_defaults_for_user`).
- `requires_attachment` / `max_days_per_request` enforced per leave type.
- States: `SUBMITTED → PENDING_APPROVAL → APPROVED | REJECTED | CANCELLED`. (Note: a `submitted→pending` data migration exists — `MigrateLeaveStatusController`.)

## HRMS — Overtime

Mirrors leave (`OvertimeRequestModel`, `OvertimeWorkflowEngine`, `OvertimeCalculatorService`):
- `has_overlap()` blocks overlapping OT on the same day.
- `duration_hours` computed from start/end (`OvertimeCalculatorService`).
- States: `SUBMITTED → IN_REVIEW → APPROVED | REJECTED | CANCELLED`. Approved OT → `overtime_ledger` (with `year`/`month` for reporting).
- Uses the same dynamic approval routing (`request_type='overtime'`).

## HRMS — Dynamic Approval Routing (the crown jewel — migrate carefully)

The most sophisticated subsystem. Generic across leave + overtime.

- **Route definition** (`approval_route_versions` + `approval_route_scopes` + `approval_route_steps`): a named, **versioned** route with an `effective_from`/`effective_to` window and `request_type`.
- **Scope matching** (`ApprovalRouteResolver`): a route applies if its `approval_route_scopes` match the request — `scope_type` ∈ {company, office, department, role, user, leave_type, leave_duration, overtime_type, overtime_duration} compared with `operator` ∈ {eq, neq, in, not_in, lte, gte, lt, gt}. This lets routing depend on requester org unit, leave type, **and request size** (e.g. >3 days needs an extra approver).
- **Step chain** (`approval_route_steps`): ordered steps with `approver_type` ∈ {user, role, position, dynamic}. `dynamic` resolves at runtime (e.g. requester's direct manager).
- **Instance snapshot**: when a request is submitted, the resolved route is **frozen** into `approval_instances.route_snapshot` (JSON) — so later route edits don't mutate in-flight requests.
- **Step execution** (`approval_steps`): optimistic locking via `version` column prevents double-approval races. `action` ∈ {PENDING, APPROVED, REJECTED, SKIPPED}.
- **NEEDS_ROUTE**: if no route matches, the instance enters `NEEDS_ROUTE` and surfaces in the admin inbox (`assign-route` endpoint) for manual route assignment.
- Admin manages routes via `admin/ApprovalRoutesController` (create/edit/versions/clone/bulk/preview). Seed data in `sql/seed_approval_routes.sql`.

> **Migration note:** the snapshot + versioning + optimistic-locking design is correct and worth preserving verbatim in the new backend. Two engines (`ApprovalWorkflowEngine`, `OvertimeWorkflowEngine`) duplicate logic — consolidate into one generic engine keyed by `request_type`.

## HRMS — Performance Appraisal

(`Performance_model`, `Api_performance`; deep-dive `docs/performance-appraisal/`.)
- Admin builds **role-based templates** (`performance_templates.role_id`, `period_year`) with weighted KPI items (`objective`, `kpi`, `target_value`, `unit`, `weight`).
- Employee submits actuals → `performance_submission_items` compute `score_ratio` and `final_score`; submission `total_score` aggregates (weighted).
- One submission per (employee, template, period_year) — UNIQUE constraint. Submissions can be cancelled.

## HRMS — Admin / Org
Offices (with geofence config + duplicate/activate), holidays, leave types, leave quotas (bulk-set, copy-from, set-all), attendance settings, roles, modules, users, positions. All conventional CRUD via `admin/*` controllers.

---

## Marketplace (Shopee / Lazada / TikTok Shop)

(`Api_v2`, `Api_v3`, `Transaction`, `Product`, `Stock`, `Shipping`; legacy `Api`.)
- **OAuth + token lifecycle:** per-platform OAuth callback stores credentials in `marketplace_config` (shop id, access/refresh token, expiry). `marketplace_token_refresh` refreshes before expiry. Each platform has its own signing (Shopee HMAC-SHA256, Lazada signature, TikTok signature).
- **Order ingestion:** pull orders (`marketplace_order`) → normalize → `marketplace_order_ingest` into `transaction`. Order statuses: `CANCELLED, RETURN, REFUND, IN_CANCELLED, UNPAID`, etc. Tracking via `marketplace_order_tracking`.
- **Product sync:** `marketplace_product` → `marketplace_product_ingest` into `product`/`product_3rd`; `/cronjob/sync-product` (`Api_v3/sync_all_product`) bulk-syncs.
- **Webhooks:** `/api/webhook` (`Api_v2/webhook`) receives real-time order/product events (signature-verified) and updates `transaction`.
- **Stock:** in/out movements, return handling (GOOD vs BAD), SKU + id tracking (`Stock`).
- **Shipping:** bulk resi (waybill) printing + scan-ready-to-ship (QR via html5-qrcode) — `Transaction/cetak_resi*`, `scan_ready_to_ship*`.
- **TikTok order sync** can offload to a standalone worker (`docs/tiktok-worker/`, `TIKTOK_WORKER_MODE=node|rust`).

## Marketing — Endorse / Influencer

(`Endorse`, `Endorse_campaign`, `Influencer`, `Review_endorse`, `Endorse_sync`, `EndorseRefreshQueueService`, `Scrapingbot`; docs: `ENDORSE_REFRESH_*`, `SCRAPINGBOT_INTEGRATION_GUIDE.md`.)
- **Campaigns** (`endorse_campaign`) group individual **endorsements** (`endorse`) tied to influencers; payments tracked in `payment_logs`.
- **Influencer metric sync:** profiles scraped from TikTok/Instagram via **ScrapingBot** (async queue) — submit→poll→enqueue cron, priority tiers (10 hot/manual, 7 active campaign, 5 regular 7d, 3 cold 14d). Parsers `parseTiktokProfileResponse()` / `parseInstagramProfileResponse()` in `Template` lib. (Legacy RapidAPI path documented in `INFLUENCER_RAPIDAPI_INTEGRATION.md`.)
- **Endorse refresh queue:** `scraping_queue`/refresh-queue with global cron + campaign cloning (see `ENDORSE_REFRESH_GUIDE.md`); UI at `/endorse/queue*`, retry/clear controls.
- **MOU generation:** Google Docs template cloned + placeholder-filled → PDF → emailed (`Googlemou`).
- **Telegram alerts** on payment/queue events (`Telegrambot`, `NotificationService`).

## Finance / Dashboard

(`Dashboard`, `Expense`, `Payment`, `Ads`, `Overview`, `Report`.)
- **Dashboard** aggregates spend across Ads (Shopee/Meta/TikTok ads tables), KOL (endorse), and Expenses via **UNION queries with `COALESCE(SUM())`**, filtered by brand + date range, cached 60s (Memcached→file, MD5 keys).
- Methods: `index` (GMV/spend/revenue), `expense`/`expense_data`, `net_sales_data`, `marketplace_fee`, `laba_bersih` (net profit), `hpp` (COGS), `hpp_bundling` (bundle COGS).
- **Expense:** category-based, brand-scoped, **recurring expense generation** via `/cronjob/expense` (`Api_v3/generate_recurring_expense`).
- Ads tax config via `Ads/update_pajak`.

## Engagement / CRM / Recruitment
- **Quest** (gamification): main/side quests, levels, history (`Quest`, `Quest_level`, `Profile`). Note `PRODUCT.md` cautions against gamified HR visualizations — relevant to redesign scope.
- **CRM / Customer:** interaction tracking, follow-ups, transaction history, WhatsApp groups (`group_wa`). Brand-scoped (`crm_<brand>` module resolution).
- **Recruitment / Interview:** scheduling integrated with **Google Meet** (`Googlemeet` — OAuth, calendar event with Meet link, stored on `interview`).
