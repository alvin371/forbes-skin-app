# Notification Module — Phase 2 (FCM Push) Notes

> **Status update (2026-06-18):** Phases 1-5 are now all implemented. Push fires end-to-end
> (dispatcher → outbox → cron worker → FCM). The live resume + remaining manual/ops actions
> live in `docs/notifications/PHASE3-5-RESUME.md`. This file is kept as the original design record.

Handoff notes for the next session. Phase 0 (refactor of the legacy in-app notification
module) is **done and committed**. This document describes what remains: adding FCM push
on top of the clean base.

Full design: `/Users/alvin/.claude/plans/i-need-to-setup-frolicking-canyon.md`

---

## What Phase 0 delivered (already committed, branch `feat/notification-module-refactor`)

Reusable, low-coupling in-app notification core:

```
Leave/OvertimeNotificationService (facades, unchanged public API)
        │
        ▼
NotificationDispatcher   ← single writer, cross-process dedupe, PUSH HOOK lives here
        │
        ├─ NotificationEvents   (message templates, keyed by `domain.event`)
        └─ NotificationModel    (owns `notifications` table, parameterized SQL)
```

Files:
- `migrations/20260618120000_harden_notifications.php` (applied)
- `application/models/NotificationModel.php`
- `application/libraries/NotificationEvents.php`
- `application/libraries/NotificationDispatcher.php`
- `application/libraries/NotificationService.php` (facade)
- `application/libraries/OvertimeNotificationService.php` (facade)
- `application/controllers/Notifications.php` (hardened)

`notifications` table now has: `type enum(...,'error')`, `is_read NOT NULL DEFAULT 0`,
`dedupe_key VARCHAR(150)`, `updated_at`, and indexes
`(user_id,is_read)`, `(user_id,created_at)`, `(dedupe_key,created_at)`.

**The single integration point for push** is `NotificationDispatcher::emit()` — there is a
marked `// --- Phase 4 extension point ---` comment where `PushChannel->enqueue()` plugs in.
Because both leave and overtime already route through the dispatcher, wiring push there makes
push work for both flows with zero changes to the workflow engines.

---

## Phase 1-5: what to build (each is a step; 2nd PR, separate from Phase 0)

### Phase 1 — Device token storage + registration API
- Migration `<ts>_create_device_tokens.php`:
  `device_tokens(id, user_id INT, token VARCHAR(255) UNIQUE, platform
  ENUM('android','ios','web'), app_version VARCHAR(30) NULL, last_seen_at TIMESTAMP,
  revoked_at TIMESTAMP NULL, created_at, updated_at)`, index `(user_id, revoked_at)`.
- `application/models/DeviceTokenModel.php`:
  - `upsert($userId,$token,$platform,$appVersion)` — `INSERT ... ON DUPLICATE KEY UPDATE`;
    reassign `user_id` if the same token re-registers under a different user (shared device).
  - `activeForUser($userId)` — tokens where `revoked_at IS NULL`.
  - `revokeByToken($token)` — set `revoked_at` (used when FCM reports the token dead).
- `application/controllers/Api_hrms.php`: add
  - `POST /api/hrms/devices` — JWT-auth, body `{token, platform, app_version}`, upsert.
  - `DELETE /api/hrms/devices` — unregister on logout.
  - Add routes in `application/config/routes.php`.
  - **Reuse** the existing JWT auth: `libraries/ApiAuth.php` (`authenticate()` reads the
    Bearer token). Follow the patterns already in `Api_hrms.php`.

### Phase 2 — FCM transport library
- Add `firebase/php-jwt: ^6.10` to `composer.json` as a **direct** dependency
  (PHP 7.4-compatible; do NOT rely on it transitively via `google/apiclient`). Run `composer update firebase/php-jwt`.
- `.env`: `FCM_SERVICE_ACCOUNT_B64` (base64 of the service-account JSON),
  optional `FCM_PROJECT_ID`. Document in `.env.example`. Ensure `.env` is gitignored.
  **Reuse the base64-credential decode pattern** from
  `application/libraries/EndorseOptimizationSheet.php` (tolerates stray chars).
- `application/libraries/Fcm.php`:
  - `getAccessToken()` — build a signed RS256 JWT (claims: `iss`=client_email,
    `scope`=`https://www.googleapis.com/auth/firebase.messaging`,
    `aud`=`https://oauth2.googleapis.com/token`, `iat`/`exp`), POST it to
    `https://oauth2.googleapis.com/token` (grant_type
    `urn:ietf:params:oauth:grant-type:jwt-bearer`). **Cache the returned token ~55 min**
    in a file cache (`APPPATH.'cache/fcm_token.json'`).
  - `send($token, $title, $body, array $data = [])` — POST to
    `https://fcm.googleapis.com/v1/projects/{projectId}/messages:send` with
    `Authorization: Bearer <token>` (cURL — follow `libraries/Scrapingbot.php` style).
    Generic body text only; put IDs in the `data` payload (no PII in the push).
    Return a status + classify errors: `UNREGISTERED`/`NOT_FOUND` → caller revokes token;
    5xx/network → retryable.

### Phase 3 — Outbox queue
- Migration `<ts>_create_notification_outbox.php`:
  `notification_outbox(id, user_id INT, event_key VARCHAR(80), title VARCHAR(255),
  body TEXT, data_json JSON NULL, status ENUM('PENDING','SENDING','SENT','FAILED','DEAD')
  DEFAULT 'PENDING', attempts INT DEFAULT 0, max_attempts INT DEFAULT 5,
  next_attempt_at TIMESTAMP, last_error TEXT NULL, created_at, sent_at TIMESTAMP NULL)`,
  index `(status, next_attempt_at)`.
- `application/models/NotificationOutboxModel.php`:
  - `enqueue($userId, $eventKey, $title, $body, $data)`.
  - `claimBatch($limit)` — atomic lease: `UPDATE ... SET status='SENDING' WHERE
    status='PENDING' AND next_attempt_at<=NOW() LIMIT n`, then select claimed rows.
    **Reuse the claim pattern** from the existing `endorse_refresh_queue` / `scraping_queue`
    workers (see `Api_v2` cronjob methods).
  - `markSent($id)`, `markRetry($id,$err)` (attempts++, exponential `next_attempt_at`,
    → `DEAD` once attempts >= max_attempts).

### Phase 4 — PushChannel wiring
- `application/libraries/PushChannel.php`: `enqueue($userId, $event)` writes **one outbox
  row per user** (the worker expands to that user's live tokens at send time — fewer rows,
  always a fresh token list).
- In `NotificationDispatcher::emit()`, at the marked extension point, after the in-app
  insert succeeds, call `PushChannel->enqueue($userId, $event)`. The dedupe check above it
  already gates both channels.
- Users with no device tokens → in-app only, no error.

### Phase 5 — Cron worker + registry finalize
- `Api_v2::cronjob_notification_dispatch()` + route `api/cronjob/notification-dispatch`:
  `claimBatch` → for each row load `DeviceTokenModel->activeForUser()` → `Fcm->send()` per
  token → revoke dead tokens → `markSent` / `markRetry`. Recommend cron every 1 minute.
- Treat `NotificationEvents` as the single source of titles/bodies across in-app + push.

---

## Decisions already locked (don't re-litigate)
- FCM **HTTP v1** (OAuth2 service-account); legacy server key rejected.
- **Dispatcher + channels + event registry** architecture.
- **Queued** delivery via outbox + cron (not inline).
- **Dedicated** device endpoints (not piggyback on login).
- Type enum standardized on **`'error'`**.
- Dedupe via **`dedupe_key` column** (done in Phase 0).
- `firebase/php-jwt ^6.10` direct dep; mint OAuth2 token manually.

## Gotchas / environment
- CI3 property casing: **models keep case** (`$this->CI->NotificationModel`),
  **libraries are lowercased** (`$this->CI->notificationdispatcher`,
  `$this->CI->notificationevents`). Easy to get wrong.
- Migrations: PHP files `migrations/YYYYMMDDHHMMSS_desc.php` taking `$pdo`+`$direction`;
  run `php migrations/run.php <file.php> [up|down]`. Make them idempotent (SHOW
  COLUMNS/INDEX guards) like `20260618120000_harden_notifications.php`.
- DB env key is `DB_HOSTNAME` (not `DB_HOST`). DB name `forbes_app`.
- `Endorse.php` has its own private `send_notification()` writing the `notifications`
  table directly — a separate domain with unrelated in-flight edits; left untouched. If you
  ever want one writer everywhere, migrate it to the dispatcher in its own PR.

## Verification checklist for the FCM PR
1. `POST /api/hrms/devices` with a real FCM token → row in `device_tokens`.
2. Submit a leave request → in-app row **and** a `notification_outbox` PENDING row.
3. Hit `api/cronjob/notification-dispatch` → device receives push; outbox → `SENT`.
4. Register a bogus token → send → token auto-revoked, outbox not stuck (→ retry/DEAD).
5. Approve + reject leave and overtime end-to-end → requester/approver get push + in-app.
