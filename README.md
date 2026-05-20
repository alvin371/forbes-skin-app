# HRMS API Integration Plan (acneno-hrms -> forbes-skin-app)

## Endorse Refresh Docs

- [Endorse Refresh Guide](docs/ENDORSE_REFRESH_GUIDE.md)
- [Endorse Refresh Trace](docs/ENDORSE_REFRESH_TRACE.md)
- [Endorse Refresh Queue Runbook](docs/ENDORSE_REFRESH_QUEUE_RUNBOOK.md)

## Goal
Implement backend APIs in `forbes-skin-app` to satisfy the mobile app in `acneno-hrms`.

## Required API Surface (from acneno-hrms)
Auth:
- `POST /auth/login` -> `{ accessToken, refreshToken, user { id, name, email, role? } }`
- `POST /auth/refresh` -> `{ accessToken, refreshToken }`

Profile:
- `GET /profile` -> `{ id, name, email, role? }`
- `PATCH /profile` -> update name/email/phone/etc (fields to confirm)

PIN:
- `POST /pin/setup` -> set/update PIN hash for user
- `POST /pin/verify` -> verify PIN, return ok/locked
- `POST /pin/reset` -> clear PIN (admin/self with password)

Config:
- `GET /config` -> office + attendance rules + wifi/BSSID allowlist

Attendance:
- `POST /attendance/office-proof` -> `{ ok: true }`
- `POST /attendance/check-in` -> `{ lat, lng, gpsAccuracy, distanceMeters, wifiProof }`
- `POST /attendance/check-out` -> `{ lat, lng, gpsAccuracy, distanceMeters, wifiProof }`
- `GET /attendance/history` -> `AttendanceRecord[]`

Leave:
- `GET /leave` -> `LeaveRecord[]`
- `GET /leave/quota` -> `{ total, remaining }`
- `POST /leave` (JSON or multipart) -> created record

Performance:
- `GET /performance` -> `PerformanceRecord[]`
- `POST /performance` -> created record

## Current forbes-skin-app Coverage
- Attendance: `AttendanceController`, `AttendanceEligibilityService`, `attendance_logs` table, `/api/attendance/*` routes.
- Leave: `LeaveController`, leave tables, approvals, attachments.
- Auth: session-based web login.
- Missing: token auth, profile API, config API, leave quota, performance module, HRMS API routes matching the mobile app.
- Missing: PIN backend support (hash storage, lockouts, reset flow).

## Implementation Plan
1. Routing strategy (decide once and keep consistent)
   - Preferred: add `/api/hrms/...` routes and update `acneno-hrms` `API_BASE_URL` to point to `/api/hrms`.
   - Compatibility option: keep root routes (`/auth/login`, `/leave`, `/attendance/*`, `/performance`) and return JSON when `Accept: application/json` or `Content-Type: application/json` is used.

2. Token-based auth
   - Add JWT access tokens (short TTL) + refresh tokens (long TTL).
   - Table: `api_refresh_tokens` with `user_id`, `token_hash`, `expires_at`, `revoked_at`, `created_at`, `user_agent`, `ip`.
   - Env: `API_JWT_SECRET`, `API_JWT_TTL_MIN`, `API_REFRESH_TTL_DAYS`.
   - Implement `POST /auth/login` and `POST /auth/refresh`.
   - Accept login by email or username; return `user` with `id`, `name`, `email`, `role`.

3. API auth middleware
   - Add library `ApiAuth` to validate `Authorization: Bearer <token>`.
   - Attach current user; reject 401 on missing/expired tokens; support refresh flow.

4. Profile APIs
   - `GET /profile`: return `id`, `name`, `email`, `role`.
   - `PATCH /profile`: allow updates to safe fields (confirm with product).

5. PIN APIs
   - Table: `user_pins` with `user_id`, `pin_hash`, `salt`, `failed_attempts`, `locked_until`, `updated_at`.
   - `POST /pin/setup`: hash new PIN (bcrypt/argon2) and store, reset counters.
   - `POST /pin/verify`: compare hash, increment failures, apply lockout (configurable).
   - `POST /pin/reset`: require password or admin role, clear pin_hash.

6. Config API
   - `GET /config`: return office geo rules + wifi/BSSID allowlist; replaces mobile env config.
   - Source: `offices` + new fields if needed (ssid/bssid list, proof rules).

7. Attendance APIs
   - Create `AttendanceApiController` (or extend existing) with:
     - `POST /attendance/office-proof`: validate `wifiProof` (see below) and return `{ ok: true }`.
     - `POST /attendance/check-in` and `/check-out`: reuse `AttendanceEligibilityService`, store in `attendance_logs`.
     - `GET /attendance/history`: add model method to list per-user logs ordered by time.
   - Extend office WiFi proof support:
     - Use existing `offices.allowed_bssids` field; parse BSSID list and compare to `wifiProof`.
     - Optional: add `attendance_logs.wifi_proof` and `attendance_logs.proof_method`.

8. Leave APIs
   - `GET /leave`: map to `LeaveRequestModel->get_by_user`.
   - `POST /leave`: reuse validation from `LeaveController` but return JSON and accept multipart file `attachment`.
   - `GET /leave/quota`: choose a source of truth:
     - Option A: add `leave_quotas` table per user and leave type.
     - Option B: compute remaining from leave types + approved usage (define annual limits).
   - Map statuses to mobile app values:
     - `PENDING_APPROVAL` -> `Pending`
     - `APPROVED` -> `Approved`
     - `REJECTED` / `CANCELLED` -> `Rejected`

9. Performance APIs
   - Add new tables (example):
     - `performance_cycles` (id, name, start_date, end_date, is_active).
     - `performance_entries` (id, user_id, cycle, achievements, challenges, self_score, notes, created_at).
   - `GET /performance`: list by user.
   - `POST /performance`: validate fields and store entry.

10. Error format + validation
   - Standard JSON: `{ message, errors? }`.
   - Use consistent 4xx/5xx status codes to match mobile expectations.

11. Security + rate limits
   - Add throttling for attendance submissions (reuse existing cooldown logic).
   - Log auth token usage; revoke refresh tokens on logout.
   - Add PIN lockout (e.g., 5 attempts -> 15 min lock).

12. Verification checklist
   - Postman collection for each endpoint.
   - Happy-path flows: login -> refresh -> attendance -> leave -> performance.
   - Test multipart upload and invalid GPS/accuracy rules.

## Notes
- Environment variables are loaded via `.env` and the `env()` helper.
- Web UI routes should remain unchanged if the compatibility option is used.
- Biometrics are handled client-side; no backend endpoints required.

## Sentry
- Sentry is initialized from `index.php` when `SENTRY_DSN` is set.
- This app uses PHP front-controller instrumentation in `index.php`; Node-style `instrument.js` startup files do not apply here.
- Configure `SENTRY_DSN`, `SENTRY_ENVIRONMENT`, `SENTRY_RELEASE`, and `SENTRY_TRACES_SAMPLE_RATE` in the root `.env`.
- HTTP requests start a transaction in `index.php` and finish it during shutdown so normal web and API traffic can produce traces.
- Use `/diagnostic/sentry` to trigger one explicit verification message and trace.
- Set `zend.exception_ignore_args = Off` in `php.ini` if stack trace arguments are needed in Sentry.
