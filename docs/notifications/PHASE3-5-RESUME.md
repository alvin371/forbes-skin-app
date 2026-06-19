# Notification FCM — Phases 3-5 Resume

Branch `feat/notification-module-refactor`. Phases 3-5 (outbox queue, push channel,
dispatcher wiring, cron worker) are implemented. Push now fires end-to-end for both leave
and overtime, gated by the existing dedupe so a suppressed event is suppressed on every
channel.

## What shipped

| Layer | File | Role |
|-------|------|------|
| Schema | `migrations/20260618150000_create_notification_outbox.php` | `notification_outbox` queue table (applied locally) |
| Model | `application/models/NotificationOutboxModel.php` | `enqueue` / `recoverStale` / `claimBatch` / `markSent` / `markRetry` (worker_id lease, exp. backoff, DEAD after max_attempts) |
| Channel | `application/libraries/PushChannel.php` | `enqueue($userId, $event)` → one outbox row per recipient |
| Wiring | `application/libraries/NotificationDispatcher.php` | injects `event_key`; calls `pushchannel->enqueue()` at the old Phase-4 point, after the in-app insert, in a try/catch so push never undoes the in-app write |
| Worker | `application/controllers/Api_v2.php` → `cronjob_notification_dispatch()` | recoverStale → claimBatch → per token `Fcm->send()` → revoke dead → markSent/markRetry |
| Route | `application/config/routes.php` | `api/cronjob/notification-dispatch` |

### Flow
```
Leave/Overtime service → NotificationDispatcher::dispatch()
   → in-app row (NotificationModel)            [always]
   → PushChannel->enqueue() → notification_outbox (1 row/user, PENDING)
GET api/cronjob/notification-dispatch (cron @1min)
   → claim PENDING → expand to device_tokens.activeForUser() → Fcm->send() per token
   → dead token revoked; row → SENT, or markRetry (backoff) → DEAD after 5 attempts
```
- No device tokens for a user → in-app only, row marked SENT (no error).
- Only **retryable** failures (5xx / 429 / network / token-mint) keep a row alive; a single
  token's hard 4xx is logged but doesn't block siblings.

### Design notes / deviations
- Added `worker_id` + `claimed_at` to the outbox table beyond the original sketch, for safe
  concurrent claims and stale-lease recovery (mirrors `endorse_refresh_queue`).
- Batch size tunable via `.env` `NOTIFICATION_DISPATCH_BATCH` (default 20, clamp 1-200).

## YOUR ACTION LIST

1. **Firebase service account** — create/locate the Firebase project, generate a service
   account JSON, base64-encode it, and set in `.env`:
   - `FCM_SERVICE_ACCOUNT_B64=<base64 of the JSON>`
   - `FCM_PROJECT_ID=<project-id>` (optional; falls back to `project_id` in the JSON)
   Keys are already documented in `.env.example`; `.env` is gitignored.

2. **Run the migration on each target server**:
   ```
   php migrations/run.php 20260618150000_create_notification_outbox.php
   ```
   (Already applied on this local DB `forbes_app`.)

3. **Register the cron** (every 1 minute):
   ```
   * * * * * curl -s https://<host>/api/cronjob/notification-dispatch >/dev/null
   ```

4. **Obtain a real device FCM token** (mobile/web client) to run the verification below.

5. **Review & commit.** Pre-existing `M` on `application/controllers/Endorse.php` and
   `application/libraries/EndorseOptimizationSheet.php` are unrelated and untouched — keep
   them out of this commit. (`Endorse.php` still writes notifications directly; migrating it
   to the dispatcher is a separate future PR.)

## Verification (needs steps 1 + 4 done)
1. `POST /api/hrms/devices` with a real token → row in `device_tokens`. ✅ no-creds-needed.
2. Submit a leave request → in-app row **and** a `notification_outbox` PENDING row.
3. `GET api/cronjob/notification-dispatch` → device receives push; outbox → `SENT`.
4. Register a bogus token → send → token auto-revoked (`revoked_at` set), outbox not stuck
   (retries with backoff, → `DEAD` after 5 attempts).
5. Approve + reject leave **and** overtime end-to-end → requester/approver get push + in-app.

## Status verification done in this session
- `php -l` clean on all new/edited files.
- Outbox migration applied (`Created table notification_outbox.`).
- Not yet runtime-tested against live FCM (requires the service account + device token above).

---

## Phase 6 — Ops hardening (added after review)

### Clarification: OAuth2 here is NOT user login
The FCM OAuth2 flow authenticates **the server to Google** (service-to-service). HRMS users
never sign in to Google — the app keeps its own user DB. FCM HTTP v1 + service account is the
**only** transport (Google shut the legacy server-key API down June 2024). All three token types
already refresh with zero ongoing config:
- **Server access token** — `Fcm::getAccessToken()` auto-mints + caches ~55 min. Seamless.
- **Device tokens** — client SDK rotates → app re-POSTs `/api/hrms/devices`; dead ones the worker
  auto-revokes. Seamless.
- **Service account JSON** — never expires unless you revoke/rotate it. Set once.

### What Phase 6 added (failures were silent; SA setup was fiddly)
| Capability | Where |
|------------|-------|
| **File-path service account** (drop raw JSON, set `FCM_SERVICE_ACCOUNT_FILE`) as an alternative to base64 | `Fcm::loadServiceAccount()` (B64 still wins if set) |
| **Health check** `GET api/fcm/health` → `{ok, project_id, source, token_cached}` (one curl, no login) | `Api_v2::fcm_health()` + `Fcm::describe()` |
| **Auth-failure flag + logging** — distinguishes "FCM auth broken" from "this device token dead" | `Fcm::result()` `auth` bit; logs on token-mint fail + 401/403 |
| **In-app alert to admins** when push auth breaks — in-app only (`no_push`, no loop), throttled hourly | `system.fcm_unavailable` event; `NotificationDispatcher::emit()` no_push skip; `Api_v2::alert_fcm_unavailable()` |

New `.env` (all optional): `FCM_SERVICE_ACCOUNT_FILE`, `FCM_ALERT_USER_IDS` (comma user ids; empty
= log-only).

### Phase 6 actions for you
- (Optional) Prefer the file path? Drop the SA JSON somewhere gitignored, set
  `FCM_SERVICE_ACCOUNT_FILE`, leave `FCM_SERVICE_ACCOUNT_B64` blank.
- (Optional) Set `FCM_ALERT_USER_IDS` to the admin user id(s) who should see push-outage alerts.
- After setting creds, sanity-check with `curl https://<host>/api/fcm/health`.

`php -l` clean on all Phase 6 edits.

---

## Phase 7 — Web push client (browser registration)

Backend stored tokens but nothing produced a *web* token. Added the Firebase JS SDK to the
dashboard so a logged-in browser self-registers as a device.

| Piece | File |
|-------|------|
| Service worker (background pushes), config from `.env`, `Service-Worker-Allowed: /` | `application/controllers/Pushsetup.php` → route `firebase-sw` |
| SDK init + permission + `getToken` + POST `/api/hrms/devices` + foreground toast | `application/views/TemplateDashboard.php` (script gated on `FCM_WEB_API_KEY` set) |
| Public web config | `.env` `FCM_WEB_*` keys |

**Web auth is the session cookie** — `ApiAuth::authenticate()` falls back to the session, so the
fetch uses `credentials:'include'`, no JWT needed in the browser. Mobile still uses Bearer JWT.

### Testing web push — your steps
1. **Firebase Console → Project Settings → General → Your apps → Web app** (add one if none).
   Copy `apiKey`, `messagingSenderId`, `appId` (authDomain/projectId already = `hrms-acneno`).
2. **Cloud Messaging → Web Push certificates → Generate key pair** → copy the VAPID public key.
3. Fill `.env`: `FCM_WEB_API_KEY`, `FCM_WEB_MESSAGING_SENDER_ID`, `FCM_WEB_APP_ID`,
   `FCM_WEB_VAPID_KEY` (+ confirm `FCM_WEB_AUTH_DOMAIN`/`FCM_WEB_PROJECT_ID`).
4. **HTTPS is mandatory** for web push (http://*.test won't work). With Valet:
   `valet secure forbes-skin-app` → use `https://forbes-skin-app.test`.
5. Log into the dashboard → browser prompts for notification permission → **Allow**. The token
   auto-POSTs; confirm a `platform='web'` row in `device_tokens`.
6. Submit a leave (or seed an outbox row) → hit `api/cronjob/notification-dispatch` → browser
   shows the notification (background tab) or a toast (focused tab).

### Testing mobile push — your steps
Backend is ready; this is app-side work:
1. Add Firebase to the mobile app using the **same project** (`google-services.json` /
   `GoogleService-Info.plist`).
2. App logs in → gets a JWT (existing `issue_tokens` flow) and an FCM token
   (`FirebaseMessaging.getToken()`).
3. App calls `POST /api/hrms/devices` with `Authorization: Bearer <jwt>`, body
   `{token, platform:'android'|'ios', app_version}`.
4. Same trigger as web step 6 → device receives the push.

### Known tuning item (not blocking)
On web, a payload carrying a `notification` block can display twice in the background (FCM
auto-display + our `onBackgroundMessage` handler). Clean fix if it bothers you: send **data-only**
messages for web and let the SW render them. Left as a follow-up.
