# FCM Push — Mobile Integration Handoff

Self-contained spec for the Android/iOS team. The **backend is complete and tested**: it stores
device tokens, queues pushes, and a cron worker delivers them via FCM HTTP v1. Your job is the
client side — get an FCM token, register it, handle incoming pushes. No backend changes needed.

Firebase project: **`hrms-acneno`**. Use the same project for Android + iOS.

---

## 1. What the backend already does
- Stores one row per device in `device_tokens` (token, user_id, platform, revoked_at).
- On every notifiable HR event (leave/overtime approved, rejected, pending, etc.) it writes an
  in-app notification **and** queues a push (one outbox row per recipient user).
- A cron (`api/cronjob/notification-dispatch`, every 1 min) expands each row to the user's live
  tokens and sends via FCM. Dead tokens (FCM `UNREGISTERED`/404) are auto-revoked — if the OS
  rotates a token, just re-register the new one (below) and the old one self-cleans.

You do **not** call FCM send. You only register the token and render what arrives.

---

## 2. Auth (needed before registering a device)
All `/api/hrms/*` endpoints (except login/refresh) require `Authorization: Bearer <accessToken>`.

### Login — `POST /api/hrms/auth/login`
Body: `{ "email": "...", "password": "..." }`
→ `200`:
```json
{ "accessToken": "<JWT>", "refreshToken": "<opaque>", "user": { ... }, "schedule": { ... } }
```
- `accessToken` (JWT) lifetime ~30 min (`API_JWT_TTL_MIN`).
- `refreshToken` lifetime ~30 days (`API_REFRESH_TTL_DAYS`).

### Refresh — `POST /api/hrms/auth/refresh`
Body: `{ "refreshToken": "<opaque>" }` → `200 { accessToken, refreshToken }` (rotates both).
On `401`, send the user back to login.

---

## 3. Register the device token

### `POST /api/hrms/devices`
Headers: `Authorization: Bearer <accessToken>`, `Content-Type: application/json`
Body:
```json
{ "token": "<FCM registration token>", "platform": "android", "app_version": "1.4.0" }
```
- `platform`: `"android"` | `"ios"` (web is handled by the dashboard).
- `token`: ≤255 chars. `app_version`: optional, ≤30 chars.
- Responses: `200 {"ok":true}` · `422` validation · `401` bad/expired JWT · `500`.
- Idempotent upsert: re-registering the same token refreshes it (and moves it to the current
  user on a shared device). Safe to call on every app start and whenever the SDK reports a new
  token.

### `DELETE /api/hrms/devices`  (call on logout)
Headers: `Authorization: Bearer <accessToken>`, body `{ "token": "<same token>" }`
→ `200 {"ok":true}` (idempotent; revokes so the user stops getting pushes on that device).

### When to call
- After login + FCM token obtained → `POST`.
- On `onNewToken` / token-refresh callback → `POST` (re-register).
- On logout → `DELETE`.

---

## 4. The push payload you receive

FCM HTTP v1 message the worker sends (`application/libraries/Fcm.php`):
```json
{
  "message": {
    "token": "<device token>",
    "notification": { "title": "Pengajuan Cuti Disetujui", "body": "Pengajuan Cuti Anda ..." },
    "data": { "type": "success", "related_table": "leave_requests", "related_id": "1234" }
  }
}
```
- **`notification.title` / `body`** — display text (Indonesian), already user-ready. No PII beyond
  what's in the title/body templates.
- **`data`** — routing only (all string values):
  | key | meaning | values |
  |-----|---------|--------|
  | `type` | severity / styling | `info` `success` `warning` `error` |
  | `related_table` | entity to deep-link | `leave_requests`, `overtime_requests`, `leave_quotas` |
  | `related_id` | entity id (may be empty) | numeric string |

Use `related_table` + `related_id` to deep-link (e.g. open the leave request detail screen).

### Foreground vs background
- **Background / killed**: the `notification` block auto-shows in the tray. Tapping it should
  route via `data`.
- **Foreground**: SDK delivers the message to your in-app handler — render your own banner/toast
  using `notification` + `data`.

---

## 5. Client checklist
- [ ] Add Firebase to the app, project `hrms-acneno` (`google-services.json` / `GoogleService-Info.plist`).
- [ ] iOS: enable Push Notifications + Background Modes (remote notifications), upload APNs key to
      Firebase.
- [ ] Request notification permission (Android 13+ `POST_NOTIFICATIONS`, iOS prompt).
- [ ] Get token (`FirebaseMessaging.getToken()`), `POST /api/hrms/devices` after login.
- [ ] Re-register on `onNewToken`; `DELETE` on logout.
- [ ] Handle foreground messages; deep-link on notification tap via `data`.
- [ ] Attach `Authorization: Bearer` + refresh flow on `401`.

## 6. End-to-end test
1. Log in on a real device, allow notifications, confirm a `platform` row appears in
   `device_tokens` (ask backend to check, or via the app's success response).
2. Have someone approve/reject your leave or overtime (or backend seeds an outbox row).
3. Within ~1 min (cron cadence) the push arrives. Tapping it deep-links via `data`.
4. Backend can verify delivery: outbox row → `SENT`; a bad token → auto-revoked.

## 7. Reference (backend, FYI only)
- Endpoints/auth: `application/controllers/Api_hrms.php`, `application/libraries/ApiAuth.php`.
- Send transport + payload shape: `application/libraries/Fcm.php`.
- Event titles/bodies (source of truth): `application/libraries/NotificationEvents.php`.
- Worker: `Api_v2::cronjob_notification_dispatch`. Health: `GET api/fcm/health`.
