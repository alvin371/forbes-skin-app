# Data Model

Tables grouped by domain. **Critical split:** the **HRMS half has real migrations** (`application/migrations/`, 19 files, source of truth below). The **marketplace/marketing/finance half does NOT** — those tables predate the migration system and live only in the production MySQL schema (no migration files, no DDL in repo). For that half, table/column facts below come from query usage in controllers/models and are marked *(legacy schema, no migration)*.

## Migration system

- Runner: **`migrations/run.php`** — a standalone PHP CLI script (not CI's native migrations). Usage: `php migrations/run.php <migration_file.php> [up|down]`.
- It reads `.env` manually (parses `KEY=VALUE`, strips quotes/comments), connects via **PDO** using `DB_HOSTNAME/DB_USERNAME/DB_PASSWORD/DB_DATABASE`, and `include`s the migration file passing `$pdo` and `$direction`.
- Each migration file is a plain PHP script that runs DDL against `$pdo` based on `$direction`.
- There is **no migrations tracking table** — runs are manual, one file at a time. (A `MigrationRunner` controller + `/migrate/latest` route also exist for web-triggered runs.)
- Seeds: `sql/seed_approval_modules.sql` (modules + role permissions for approval system), `sql/seed_approval_routes.sql` (routes/scopes/steps). Also web seed routes via `SeedRunner` (`/seed/*`).

### Migration files (chronological)
```
20251229203700_create_offices_and_attendance_logs
20251229204400_add_office_active_and_bssid
20251229211000_create_leave_management_tables
20260108100000_create_holidays_table
20260108100100_create_attendance_settings_table
20260108120000_create_performance_appraisal_tables
20260115100000_convert_performance_to_role_based
20260120100000_add_performance_submission_cancellation
20260201100000_create_approval_routing_tables
20260205100000_create_overtime_tables
20260205100001_seed_overtime_types
20260205120000_create_overtime_approval_routes
20260205123000_add_attendance_log_notes_and_special_schedule
20260224103000_fix_endorse_logs_views_and_uniqueness   ← only marketing-side migration
20260408110000_add_overview_chart_indexes
20260410120000_unify_approval_route_sources
20260416000100_add_attendance_reason_fields
20260416000200_add_attendance_category_to_attendance_logs
20260430130000_add_overview_kol_generated_date_indexes
```

---

## HRMS — Attendance

**`offices`** — geofenced locations. `id`, `name`, `lat DECIMAL(10,7)`, `lng DECIMAL(10,7)`, `radius_m INT(150)`, `min_accuracy_m INT(50)`, `allowed_ip_cidrs TEXT`, `is_active TINYINT`, `created_at`, `updated_at`. (+ BSSID/SSID columns added by `20251229204400`.) Model: `Office_model`.

**`attendance_logs`** — check-in/out events. `id`, `user_id`, `office_id`, `type ENUM('IN','OUT')`, `lat`, `lng`, `accuracy FLOAT`, `distance_m FLOAT`, `method VARCHAR(50)`, `ip_address VARCHAR(45)`, `user_agent TEXT`, `created_at`. Later columns: `notes JSON` + `special_schedule TINYINT` (`...123000`), `attendance_reason VARCHAR(255)` (`...000100`), `attendance_category VARCHAR(100)` (`...000200`). Model: `Attendance_log_model` (dedup via `has_recent_log()`, `has_type_today()`).

**`attendance_settings`** — `id`, `check_in_start/end TIME`, `check_out_start/end TIME`, `grace_period_minutes INT`, `is_active`, timestamps. Model: `AttendanceSettingsModel`.

## HRMS — Leave

**`leave_types`** — `id`, `code VARCHAR(50) UNIQUE`, `name`, `is_paid TINYINT`, `requires_attachment TINYINT`, `max_days_per_request INT?`, `is_active TINYINT(1)`, `default_quota_days INT?` (auto-seed source), timestamps. Model: `LeaveTypeModel`.

**`leave_requests`** — `id`, `request_no VARCHAR(30) UNIQUE`, `user_id`, `leave_type_id`, `start_date`, `end_date`, `days_count INT`, `reason TEXT?`, `attachment_path VARCHAR(255)?`, `status ENUM('SUBMITTED','PENDING_APPROVAL','APPROVED','REJECTED','CANCELLED')`, `current_step INT(1)`, timestamps. Model: `LeaveRequestModel`.

**`leave_quotas`** — `id`, `user_id`, `leave_type_id`, `total_days INT`, `remaining_days INT`, `year INT?`, `updated_at`. Model: `LeaveQuotaModel` (`apply_defaults_for_user`, `deduct_quota`, `restore_quota`).

**`leave_ledger`** — approved-leave audit. `id`, `user_id`, `leave_type_id`, `leave_request_id`, `start_date`, `end_date`, `days_used`, `is_paid`, `year`, `approved_by?`, `approved_at?`, `created_at`. Model: `LeaveLedgerModel` (usage summaries, company stats, monthly trend, users-on-leave).

**`leave_quota_logs`** — quota change audit. `id`, `user_id`, `leave_type_id`, `leave_request_id?`, `action_type ENUM('DEDUCT','RESTORE','ADJUST','RESET','INITIALIZE')`, `old/new_total_days`, `old/new_remaining_days`, `change_amount` (all DECIMAL(5,2)), `reason TEXT?`, `performed_by`, `created_at`. Model: `LeaveQuotaLogModel`.

## HRMS — Approval routing (dynamic, versioned)

**`approval_route_versions`** — `id`, `route_code VARCHAR(50)`, `name`, `description?`, `version INT(1)`, `request_type VARCHAR(50)?` (added `...410120000`; defaults to `leave` for legacy rows), `effective_from DATE`, `effective_to DATE?`, `is_active`, `created_by?`, timestamps. Model: `ApprovalRouteVersionModel`.

**`approval_route_scopes`** — matching conditions. `id`, `route_version_id`, `scope_type ENUM('company','office','department','role','user','leave_type','leave_duration','overtime_type','overtime_duration')`, `scope_value VARCHAR(255)?`, `operator ENUM('eq','neq','in','not_in','lte','gte','lt','gt')`.

**`approval_route_steps`** — step definitions. `id`, `route_version_id`, `step_no INT(1)`, `approver_type ENUM('user','role','position','dynamic')`, `approver_value`, `step_name?`, `is_optional TINYINT`.

**`approval_instances`** — per-leave-request snapshot. `id`, `leave_request_id UNIQUE`, `route_version_id`, `route_snapshot JSON`, `total_steps`, `current_step`, `status ENUM('IN_PROGRESS','COMPLETED','REJECTED','CANCELLED','NEEDS_ROUTE')`, timestamps. Model: `ApprovalInstanceModel`.

**`approval_steps`** — per-step records w/ optimistic locking. `id`, `approval_instance_id`, `leave_request_id`, `step_no`, `step_name?`, `assigned_approver_id?`, `actual_approver_id?`, `action ENUM('PENDING','APPROVED','REJECTED','SKIPPED')`, `action_at?`, `notes?`, `version INT(1)` (optimistic lock), timestamps. Model: `ApprovalStepModel` (`get_pending_for_approver()` is a 7-join inbox query).

> **Legacy duplication:** an older **`approval_routes`** table (simple) exists alongside `approval_route_versions` (the versioned system). Models do field-existence checks (`has_request_type_field()`) for backward compatibility. Migration `...410120000` *unified approval route sources* by adding `request_type`. **Retire the old table during migration.**

## HRMS — Overtime (mirrors approval structure)

**`overtime_types`** — `id`, `code VARCHAR(20) UNIQUE`, `name`, `description?`, `requires_attachment`, `is_active`, timestamps. Seeded by `...100001`. Model: `OvertimeTypeModel`.

**`overtime_requests`** — `id`, `request_no VARCHAR(30) UNIQUE`, `user_id`, `overtime_type_id`, `office_id?`, `overtime_date DATE`, `start_time TIME`, `end_time TIME`, `duration_hours DECIMAL(4,2)`, `reason TEXT`, `attachment_path?`, `status ENUM('SUBMITTED','IN_REVIEW','APPROVED','REJECTED','CANCELLED')`, `submitted_at?`, `approval_instance_id?`, `current_step INT(0)`, `final_approved_by?`, `final_approved_at?`, timestamps. Model: `OvertimeRequestModel` (`has_overlap()`, `generate_request_no()`).

**`overtime_approval_instances`** / **`overtime_approval_steps`** — same shape as leave equivalents. Models: `OvertimeApprovalInstanceModel`, `OvertimeApprovalStepModel`.

**`overtime_ledger`** — `id`, `overtime_request_id`, `user_id`, `overtime_type_id`, `overtime_date`, `start_time`, `end_time`, `hours_worked DECIMAL(4,2)`, `year`, `month`, `approved_by?`, `approved_at?`, `created_at`. Model: `OvertimeLedgerModel`.

> Overtime reuses approval routing via `request_type='overtime'` in `approval_route_versions` (migration `...205120000` created `overtime_approval_routes`, then `...410120000` unified sources). Two parallel approval engines (`ApprovalWorkflowEngine` for leave, `OvertimeWorkflowEngine` for OT) — **consolidation candidate.**

## HRMS — Performance appraisal

**`performance_templates`** — `id`, `name`, `period_year INT`, `role_id INT?` (role-based after `...115100000`), `is_active`, timestamps.

**`performance_template_items`** — `id`, `template_id FK→templates CASCADE`, `order_no`, `objective TEXT`, `kpi TEXT`, `target_value DECIMAL(10,2)`, `unit`, `weight DECIMAL(5,2)`, timestamps.

**`performance_submissions`** — `id`, `template_id FK RESTRICT`, `employee_id`, `employee_name/nik/department/position?`, `period_year`, `total_score DECIMAL(10,4)`, `status ENUM('DRAFT','SUBMITTED')` (cancellation added `...120100000`), timestamps. **UNIQUE(employee_id, template_id, period_year)**.

**`performance_submission_items`** — `id`, `submission_id FK CASCADE`, `template_item_id FK RESTRICT`, `actual_value DECIMAL(10,2)`, `score_ratio DECIMAL(10,4)`, `final_score DECIMAL(10,4)`, timestamps. Model: `Performance_model`. Deep-dive in `docs/performance-appraisal/`.

## HRMS — Misc

**`holidays`** — `id`, `date DATE UNIQUE`, `name`, `is_active`, timestamps. Model: `HolidayModel`. Used by leave/overtime working-day calc.

**`api_refresh_tokens`** — JWT refresh storage. `id`, `user_id`, `token_hash` (sha256), `expires_at`, `revoked_at?`, `created_at`, `user_agent`, `ip`. Written/read by `ApiAuth`. *(No migration file found — created via `SchemaBootstrap`/manual.)*

---

## RBAC / core *(legacy schema, no migration)*

- **`user`** — accounts. Fields referenced: `id`, `full_name`, `email`, `role`/`role_text`, password hash, plus PIN fields for HRMS. (Hybrid MD5/`password_hash`.)
- **`roles`** — `id`, name/display, `is_active`, hierarchy level.
- **`user_roles`** — `user_id` ↔ `role_id` (RBAC assignment).
- **`modules`** — feature modules for permissions.
- **`role_permissions`** — module access by role.
- **`user_module_permissions`** — *primary* per-user permission table used by `BaseController` (`can_view/create/edit/delete/approve` by `user_id`+`module_name`).

## Marketplace / Finance *(legacy schema, no migration)*

- **`transaction`** — orders from all marketplaces (status enum incl. `CANCELLED, RETURN, REFUND, IN_CANCELLED, UNPAID`).
- **`product`**, **`product_3rd`** — catalog + marketplace product mapping.
- **`stock`** — inventory movements (in/out, GOOD/BAD returns).
- **`brand`** — brand dimension (drives dashboard filtering).
- **`marketplace_config`** — **per-shop API credentials**: shop IDs, access/refresh tokens, expiry. Central to all marketplace integrations + token refresh.
- **`expense`** + expense category tables — finance.
- Ads aggregates: **`shopee_ads_data`**, **`meta_ads_data`**, **`tiktok_ads_data`**, **`gmv_spend_data`** — read by `Dashboard` UNION queries. Indexes added by `...408110000` (overview chart) and `...430130000` (KOL generated-date).

## Marketing / Endorse *(legacy schema, no migration except endorse-logs fix)*

- **`endorse_campaign`** — campaign planning/tracking.
- **`endorse`** — individual endorsements (linked to campaign + influencer).
- **`payment_logs`** — endorsement payment tracking.
- **`influencer`** — influencer profiles (TikTok/IG metrics, synced via scraping).
- **`scraping_queue`** — async scraping jobs (priority tiers 10/7/5/3; submit→poll→enqueue cron). See `docs/SCRAPINGBOT_INTEGRATION_GUIDE.md`.
- **`group_wa`** — WhatsApp group management.
- Endorse logs/views — touched by migration `...224103000` (uniqueness + views fix).

> **Migration risk for the marketplace/marketing half:** no schema in the repo. Before migrating, **dump the live schema** (`SHOW CREATE TABLE`) for every table above — column types, indexes, and FKs must be reverse-engineered from the production DB, not assumed from this doc.
