# Announcement Module — Technical Debt & Follow-ups

Date: 2026-06-20
Branch: `feat/notification-module-refactor`
Status: module built, DB activated (table + module row + grants + permission cache).

This is a resume of known debt and risks introduced by, or adjacent to, the
Announcement module. Each item lists impact and a suggested fix. None block the
module from working; they are follow-ups to schedule.

---

## High

### 1. Publish broadcast runs synchronously inside the HTTP request
`Announcement::store()` / `update()` call `AnnouncementNotificationService::broadcastPublished()`,
which loops **every active employee** and dispatches an in-app notification one
row at a time inside the publish request.
- **Impact**: publish latency grows linearly with headcount; risk of request
  timeout / partial broadcast for large companies. FCM push is already queued via
  `notification_outbox`, but the in-app row inserts and per-user dispatch are inline.
- **Fix**: enqueue the broadcast (write an outbox/job row) and fan out from an
  existing cron (mirror the notification push cron). Keep publish request O(1).

### 2. `sanitize_html()` is regex-based, not a real HTML sanitizer
`Announcement::sanitize_html()` strips `<script>/<iframe>/<style>`, `on*` handlers
and `javascript:` URLs with regex. Project XSS filtering is globally off and
`detail.php` renders stored content raw.
- **Impact**: obfuscated / nested payloads (e.g. `<svg onload>`, encoded URLs,
  `<math>`, data URIs) can bypass regex → stored XSS for any reader.
- **Fix**: run TinyMCE HTML through a real sanitizer (HTMLPurifier or an
  allowlist DOM pass) on save; treat any pre-existing/direct-insert rows as untrusted.

---

## Medium

### 3. Category/subcategory stored as denormalized label strings
`announcements.category` / `subcategory` hold the literal label. The canonical map
lives only in `announcement_categories_helper.php`.
- **Impact**: renaming/removing a category in the helper orphans existing rows
  (validation will reject edits, list filters silently miss them). No FK / lookup table.
- **Fix**: either freeze the labels as a contract, or normalize into a
  `announcement_categories` table keyed by id.

### 4. `created_by` / `updated_by` have no FK constraint
Plain `INT UNSIGNED` columns, no FK to `user`.
- **Impact**: orphaned author references if a user is deleted; "created by" UI
  (when added) must null-guard.
- **Fix**: add FK with `ON DELETE SET NULL`, or document the intentional soft link.

### 5. No automated tests
No unit/integration coverage for the model filters, validation, publish-transition
broadcast guard, or the API window/404 logic.
- **Impact**: regressions in filter SQL or the `old!=PUBLISHED && new==PUBLISHED`
  guard go undetected.
- **Fix**: add tests for `AnnouncementModel::search/count`, `validate()`,
  `announcement_subcategory_valid()`, and `Api_hrms::announcement_detail` states.

### 6. Permission cache is manually maintained for new modules
`user_module_permissions` is a **cache table** (not a view). New module rows are
only rebuilt when an admin saves roles (`Roles.php`) or via
`MigrateUserModulePermissions`. This module's cache was populated by hand.
- **Impact**: if grants change later outside the Roles UI, the cache drifts;
  correctness only holds because `check_permission` has a live `role_permissions`
  fallback.
- **Fix**: add a small "activate module" routine that registers the module row +
  rebuilds its cache in one step, callable from seed/CLI.

---

## Low

### 7. List "publish date to" filter bounds the start column
`AnnouncementModel::apply_filters()` applies both `publish_start_at` (from) and
`publish_end_at` (to) bounds against the **`publish_start_at`** column — the
`publish_end_at` column is never filtered.
- **Impact**: an announcement whose window *ends* in range but *starts* before it
  won't match the date filter. May be intended (range on start date) but is
  ambiguous vs the field label.
- **Fix**: confirm intent; if "active during range" is meant, filter both columns.

### 8. Re-publish dedupe window vs transition guard
Broadcast is gated by the status transition (`old != PUBLISHED`). The per-recipient
dedupe key (`announcement_published_{id}_{user}`) only suppresses within the 60s
notification window.
- **Impact**: archive→publish cycles >60s apart legitimately re-broadcast; fine
  today, but the two mechanisms (status guard + dedupe) overlap unclearly.
- **Fix**: document the intended re-notify policy; if "never re-notify", persist a
  per-announcement broadcast flag instead of relying on dedupe TTL.

### 9. Stale `user_roles` rows (`user_id = 0`)
The permission cache populate produced one `user_id = 0` row (pre-existing bad
`user_roles` data, same as other modules).
- **Impact**: harmless noise; inflates cache counts.
- **Fix**: data cleanup of orphan `user_roles` (cross-module, not announcement-specific).

---

## Related modules

### 10. Notification dedupe is global within the window
`NotificationModel::existsByDedupeKey()` is global per key within ~60s, not scoped
per recipient. Every fan-out/broadcast event must hand-craft a per-recipient key
(as this module does) or silently drop all-but-first recipient.
- **Fix**: scope dedupe by `(user_id, dedupe_key)` at the model layer so broadcast
  events can't regress.

### 11. Migration runner has no applied-migrations ledger
`migrations/run.php` is standalone; each file self-guards for idempotency but there
is no `schema_migrations` table tracking what ran.
- **Impact**: applied state is implicit; hard to audit across environments.
- **Fix**: add a migrations ledger table + skip-if-applied in the runner.
