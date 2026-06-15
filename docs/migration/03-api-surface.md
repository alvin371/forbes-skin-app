# API Surface

**Highest-priority doc for the API+SPA target.** This is the existing contract. Endpoint list is derived from `application/config/routes.php` (authoritative) plus controller methods. The HRMS API also has a live OpenAPI spec — **`docs/openapi/hrms.yaml`**, served as Swagger UI at `/docs/hrms` and raw at `/docs/openapi/hrms.yaml` (via the `Docs` controller). Treat that YAML as the precise schema for HRMS; extend it rather than re-spec.

## Auth models at a glance

| Surface | Controller | Auth |
|---|---|---|
| HRMS API | `Api_hrms` | **JWT Bearer** (`ApiAuth`), session fallback; PIN gate on some flows |
| Performance API | `Api_performance` | Session/RBAC (admin) + user scope |
| Marketplace v2 | `Api_v2` | **Worker secret + IP allowlist** (cron/callback); webhook signatures |
| Marketplace v3 | `Api_v3` | Worker secret / cron context |
| Legacy marketplace | `Api` | Worker secret / manual (deprecated) |
| Attendance web API | `AttendanceController` | Session |
| Approvals web AJAX | `approvals/*` | Session + RBAC |

## Response conventions (inconsistent — unify in migration)

- **JSON APIs** (`Api_hrms`, `Api_performance`, most `Api_v2`): `set_content_type('application/json')` with bodies like `{ "success": true, "data": ... }` or `{ "success": false, "message": ... }`. HTTP status set via `set_status_header()`.
- **Web AJAX fragments** (`Endorse`, `Transaction`, `Ajax`, many `BaseController` modules): return **rendered HTML strings**; frontend JS checks for a literal `"success"` substring in the response. **Not a real contract** — these are the biggest API-fication debt.
- **403 from RBAC**: HTML `error_403` page for normal requests; JSON 403 for `require_ajax_permission()` calls.
- File upload/download paths resolved via `attachment_helper.php` (`hrms_attachment_url()`, `project_uploaded_file_url()`).

---

## HRMS API — `Api_hrms` (JWT) — base `/api/hrms`

### Auth & profile
| Method | Route | Controller method |
|---|---|---|
| POST | `/api/hrms/auth/login` | `auth_login` |
| POST | `/api/hrms/auth/refresh` | `auth_refresh` |
| POST | `/api/hrms/pin/setup` | `pin_setup` |
| POST | `/api/hrms/pin/verify` | `pin_verify` |
| POST | `/api/hrms/pin/reset` | `pin_reset` |
| GET | `/api/hrms/profile` | `profile` |
| POST | `/api/hrms/profile/password` | `profile_password` |
| GET | `/api/hrms/config` | `config` |

### Attendance
| Method | Route | Method |
|---|---|---|
| POST | `/api/hrms/attendance/check-in` | `attendance_check_in` |
| POST | `/api/hrms/attendance/check-out` | `attendance_check_out` |
| POST | `/api/hrms/attendance/out-of-town/check-in` | `attendance_out_of_town_check_in` |
| POST | `/api/hrms/attendance/out-of-town/check-out` | `attendance_out_of_town_check_out` |
| POST | `/api/hrms/attendance/office-proof` | `attendance_office_proof` |
| GET | `/api/hrms/attendance/status` | `attendance_status` |
| POST | `/api/hrms/attendance/{id}/reason` | `attendance_reason/$1` |
| GET | `/api/hrms/attendance/history` | `attendance_history` |
| GET | `/api/hrms/attendance/dashboard` | `attendance_dashboard` |
| GET | `/api/hrms/attendance/recap` | `attendance_recap` |
| GET | `/api/hrms/attendance/recap-all` | `attendance_recap_all` |
| GET | `/api/hrms/attendance/report` | `attendance_report` |

### Leave
| Method | Route | Method |
|---|---|---|
| GET/POST | `/api/hrms/leave` | `leave` (list / create) |
| GET | `/api/hrms/leave/{id}` | `leave_detail/$1` |
| POST | `/api/hrms/leave/{id}/submit` | `leave_submit/$1` |
| POST | `/api/hrms/leave/{id}/cancel` | `leave_cancel/$1` |
| GET | `/api/hrms/leave/{id}/progress` | `leave_progress/$1` |
| GET | `/api/hrms/leave/types` | `leave_types` |
| GET | `/api/hrms/leave/quota` | `leave_quota` |
| GET | `/api/hrms/leave/quota/detail` | `leave_quota_detail` |

### Leave approvals
| Method | Route | Method |
|---|---|---|
| GET | `/api/hrms/leave/approvals` | `leave_approvals` |
| GET | `/api/hrms/leave/approvals/{id}` | `leave_approval_detail/$1` |
| POST | `/api/hrms/leave/approvals/{id}/approve` | `leave_approval_approve/$1` |
| POST | `/api/hrms/leave/approvals/{id}/reject` | `leave_approval_reject/$1` |
| GET | `/api/hrms/leave/approvals/history` | `leave_approvals_history` |

### Generic approvals inbox (cross-type)
| Method | Route | Method |
|---|---|---|
| GET | `/api/hrms/approvals/inbox` | `approvals_inbox` |
| GET | `/api/hrms/approvals/inbox/count` | `approvals_inbox_count` |
| POST | `/api/hrms/approvals/{id}/approve` | `approval_approve/$1` |
| POST | `/api/hrms/approvals/{id}/reject` | `approval_reject/$1` |
| GET | `/api/hrms/approvals/history` | `approvals_history` |

### Overtime
| Method | Route | Method |
|---|---|---|
| GET | `/api/hrms/overtime/types` | `overtime_types` |
| GET/POST | `/api/hrms/overtime` | `overtime` (list / create) |
| GET | `/api/hrms/overtime/summary` | `overtime_summary` |
| GET | `/api/hrms/overtime/{id}` | `overtime_detail/$1` |
| POST | `/api/hrms/overtime/{id}/cancel` | `overtime_cancel/$1` |
| GET | `/api/hrms/overtime/approvals` | `overtime_approvals` |
| GET | `/api/hrms/overtime/approvals/{id}` | `overtime_approval_detail/$1` |
| GET | `/api/hrms/overtime/approvals/inbox` | `overtime_approvals_inbox` |
| GET | `/api/hrms/overtime/approvals/inbox/count` | `overtime_approvals_inbox_count` |
| POST | `/api/hrms/overtime/approvals/{id}/approve` | `overtime_approval_approve/$1` |
| POST | `/api/hrms/overtime/approvals/{id}/reject` | `overtime_approval_reject/$1` |
| GET | `/api/hrms/overtime/approvals/history` | `overtime_approvals_history` |

### Performance (employee-facing subset)
| Method | Route | Method |
|---|---|---|
| GET | `/api/hrms/performance/templates/active` | `performance_templates_active` |
| GET | `/api/hrms/performance/submissions` | `performance_submissions` |
| GET | `/api/hrms/performance/submissions/{id}` | `performance_submission_detail/$1` |
| POST | `/api/hrms/performance/submissions/{id}/cancel` | `performance_submission_cancel/$1` |

### Files & misc
| Method | Route | Method |
|---|---|---|
| GET | `/api/hrms/holidays` | `holidays` |
| POST | `/api/hrms/upload` | `upload` |
| GET | `/api/hrms/files/{scope}/{filename}` | `uploaded_file/$1` |
| GET | `/writable/uploads/{scope}/{filename}` | `uploaded_file/$1` (alias) |

---

## Performance API — `Api_performance` (verb-scoped routes)

Admin (`/admin/performance/*`) and user (`/performance/*`) split. Routes are explicitly HTTP-verb-scoped in `routes.php`.

| Method | Route | Method |
|---|---|---|
| GET | `/admin/performance/roles` | `roles` |
| GET | `/admin/performance/templates` | `templates` |
| POST | `/admin/performance/templates` | `template_create` |
| GET | `/admin/performance/templates/{id}` | `template/$1` |
| PUT | `/admin/performance/templates/{id}` | `template_update/$1` |
| DELETE | `/admin/performance/templates/{id}` | `template_delete/$1` |
| POST | `/admin/performance/templates/{id}/items` | `item_create/$1` |
| PUT | `/admin/performance/items/{id}` | `item_update/$1` |
| DELETE | `/admin/performance/items/{id}` | `item_delete/$1` |
| POST | `/admin/performance/templates/{id}/items/reorder` | `items_reorder/$1` |
| GET | `/admin/performance/submissions` | `submissions` |
| GET | `/performance/templates/active` | `templates_active` |
| POST | `/performance/submissions` | `submission_create` |
| GET | `/performance/submissions/me` | `submissions_me` |
| GET | `/performance/submissions/{id}` | `submission/$1` |

> This is the **most RESTful** part of the codebase (proper verbs). Good reference shape for the migrated API.

---

## Marketplace API v2 — `Api_v2` (worker secret + IP allowlist)

### OAuth callbacks
| Route | Method |
|---|---|
| `/api/marketplace/callback/shopee` | `marketplace_callback_shopee` |
| `/api/marketplace/callback/lazada` | `marketplace_callback_lazada` |
| `/api/marketplace/callback/tiktok` | `marketplace_callback_tiktok` |

### Config / token / webhook management
| Route | Method |
|---|---|
| `/api/marketplace/config` | `marketplace_config` |
| `/api/marketplace/token/refresh` | `marketplace_token_refresh` |
| `/api/marketplace/webhook/refresh` | `marketplace_webhook_refresh` |
| `/api/marketplace/webhook/reset` | `marketplace_webhook_reset` |

### Orders / products
| Route | Method |
|---|---|
| `/api/marketplace/order` | `marketplace_order` |
| `/api/marketplace/order/ingest` | `marketplace_order_ingest` |
| `/api/marketplace/order/detail` | `marketplace_order_detail` |
| `/api/marketplace/order/tracking` | `marketplace_order_tracking` |
| `/api/marketplace/order/download` | `marketplace_order_download` |
| `/api/marketplace/product` | `marketplace_product` |
| `/api/marketplace/product/ingest` | `marketplace_product_ingest` |

### Webhook & other
| Route | Method |
|---|---|
| `/api/webhook` | `webhook` (marketplace event receiver — signature-verified) |
| `/api/customer/summary` | `customer_summary` |

### Cronjobs (HTTP-triggered, worker-gated)
| Route | Method | Purpose |
|---|---|---|
| `/api/cronjob/endorse-campaign` | `cronjob_endorse_campaign` | sync campaigns |
| `/api/cronjob/endorse` | `cronjob_endorse` | process endorsements |
| `/api/cronjob/endorse-sync-campaign` | `cronjob_endorse_by_campaign` | sync by campaign |
| `/api/cronjob/endorse-refresh-enqueue-all` | `cronjob_endorse_refresh_enqueue_all` | enqueue all refreshes |
| `/api/cronjob/endorse-refresh` | `cronjob_endorse_refresh` | process refresh queue |
| `/api/cronjob/influencer` | `cronjob_influencer` | sync influencer profiles |
| `/api/cronjob/influencer-dummy` | `cronjob_influencer_dummy` | dummy data |
| `/api/cronjob/scraping-submit` | `cronjob_scraping_submit` | submit scrape jobs |
| `/api/cronjob/scraping-poll` | `cronjob_scraping_poll` | poll scrape results |
| `/api/cronjob/scraping-enqueue` | `cronjob_scraping_enqueue` | enqueue items |
| `/api/cronjob/tiktok-sync` | `cronjob_tiktok_sync` | TikTok data sync |

---

## Marketplace API v3 — `Api_v3`

| Route | Method | Purpose |
|---|---|---|
| `/api/marketplace/ads` | `marketplace_ads` | ads data |
| `/api/tiktok/campaign` | `get_tiktok_campaign` | TikTok campaign data |
| `/api/tiktok/gmv` | `get_tiktok_gmv` | TikTok GMV |
| `/cronjob/expense` | `generate_recurring_expense` | recurring expense gen (**wins the duplicate route**) |
| `/cronjob/sync-product` | `sync_all_product` | product sync |

## TikTok Business OAuth — `TiktokAuth`
| Route | Method |
|---|---|
| `/auth/redirect` | `redirect_to_auth` |
| `/auth/callback` | `callback` |

---

## Legacy marketplace API — `Api` (DEPRECATED, `// OLD` block)

Kept for reference; **retire in migration.** Includes per-platform auth (`/api/auth/{shopee,tiktok,lazada}`), per-platform get-product/order/finance (`/api/{shopee,lazada,tiktok}/get-*`), refresh-token endpoints, legacy cronjobs (`/api/cronjob-order`, `-finance`, `-endorse`, `-influencer`), legacy webhooks (`/api/webhook-api`, `/api/webhook-test`), and `/cronjob/update-customer`. The active marketplace surface is `Api_v2`/`Api_v3`.

---

## Web AJAX endpoints (session auth) — need API-fication

These return HTML fragments or ad-hoc JSON, consumed by jQuery in views. Representative set:

- **Attendance:** `/api/attendance/confirm` (POST), `/api/attendance/status`, `/api/attendance/logs` (`AttendanceController`); `/attendance/report/data` (`AttendanceReport/data_json`), `/attendance/report/pdf`, `/attendance/report/set-schedule` (POST).
- **Approvals (web):** `approvals/inbox/*` (detail/approve/reject/history/needs-route/assign-route/quick-approve/quick-reject/pending-count), `approvals/leaves/*`, `approvals/overtime/*` — full set in `routes.php` lines 123–148.
- **Endorse:** `/endorse/queue`, `/endorse/queue-data`, `/endorse/queue-history`, `/endorse/queue-count`, `/endorse/clear-queue`, `/endorse/force-retry`, `/endorse/bulk-refresh`, `/ajax/refresh-campaign-endorses`.
- **Transaction:** `/transaction/cetak-resi`, `/transaction/cetak-resi/preview`, `/transaction/scan-ready-to-ship`, `/transaction/scan-ready-to-ship/submit`.
- **Admin CRUD** (leave-types, leave-quotas, holidays, offices, approval-routes, performance-appraisal): conventional MVC routes returning full pages or redirects — see `routes.php` lines 63–164.

> For the SPA migration these must become real JSON endpoints. The HRMS half (`Api_hrms`) already covers most employee/approver flows in JSON — the **admin CRUD and the entire marketplace/marketing web layer are the gap.**
