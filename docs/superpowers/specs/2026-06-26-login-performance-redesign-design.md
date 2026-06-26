# Login Performance Redesign

**Date:** 2026-06-26
**Branch:** `fix/performance-improvement-login`
**Status:** Design approved — staged delivery (quick wins → redesign)

## Context

The login experience is slow across the whole chain: the auth POST, the
post-login redirect, and the landing page that renders afterward. Investigation
of the code plus the 2026-06-20 server-side load-test digests
(`loadtest/results/2026-06-20-serverside/`) pinned the offenders to four root
causes, none of which is the sidebar sorting the user initially suspected.

Goal: cut login-to-usable time by removing per-request RBAC work, eliminating a
full table scan on login, killing a redundant capability probe, and fixing a
silently-dead dashboard cache.

## Root causes

### Stage 1 — Auth POST (`Auth::login_process`, `application/controllers/Auth.php:576`)
- `user.username` has **no index**. Login runs
  `SELECT * FROM user WHERE username = ?` → full table scan every attempt.
  Migrations added indexes for `influencer`, `user_module_permissions`,
  `endorse_logs` — never `user`.
- `get_user_default_page()` runs **synchronously inside the blocking POST**, so
  all permission-probe latency is paid before the POST returns.

### Stage 2 — Redirect (`Auth::get_user_default_page`, `Auth.php:136`)
- Sequential `has_module_access()` probes (user → influencer → crm → transaction
  → product). Each is **two queries**: a `user_module_permissions` COUNT plus the
  `MAX(rp.can_view)` role-join fallback. The load digest shows both running
  **1615 calls each** (equal counts) — the cache table was empty/unsynced for
  those users so every check fell through to the fallback.
- Per-request capability probe `SHOW TABLES LIKE ?` / `INFORMATION_SCHEMA`:
  **6459 calls / 29.7s** in the digest. The static cache
  (`Permission::$permission_table_capabilities_cache`) only lives one PHP
  request, so it re-probes every request.
- Two round-trips to log in: `login_process` echoes an alert, then a separate
  AJAX call to `Auth::get_redirect_url` (`Auth.php:238`) reads the URL back out
  of session.

### Stage 3 — Landing (`Dashboard::index`, `application/controllers/Dashboard.php:44`)
- Cache is silently dead in prod. The constructor (`Dashboard.php:17-38`) loads
  the memcached cache driver inside a try/catch, but CI's driver load does **not
  throw when the memcached extension is absent** — it "succeeds," then every
  `get()` returns `FALSE`, so the file fallback never triggers and the heavy
  ads/KOL/etc aggregation recomputes on every load (`endorse_logs`/`influencer`
  joins run 400-700ms).
- All aggregation blocks the first byte.
- Sidebar `ORDER BY m.sort_order` is already one cheap batched query — **not** a
  bottleneck. Deprioritized.

## Approved direction

1. **Session permission map = primary runtime source** for permission checks.
2. **`user_module_permissions` = warm fallback / read model** for admin screens,
   debugging, and rebuilding the session map. Not retired now; revisit after the
   session map is stable in prod.
3. **`perm_version`** invalidation: a global version bumped on role changes;
   sessions lazily reload their map when their stored version is stale.
4. **Staged delivery**: low-risk quick wins ship first, redesign second.

---

## Stage A — Quick wins (PR 1, low risk)

Shippable immediately, independent of the redesign.

### A1. Add `user` indexes
New migration `migrations/<ts>_add_user_login_indexes.php` following the guard
pattern in `migrations/20260622020000_add_influencer_username_index.php`:
- index on `user(username)` (login lookup)
- index on `user(email)` (duplicate-check paths, `Auth.php:58,67`)

### A2. Gate the capability probe behind config
In `Permission::get_permission_table_capabilities()`
(`application/libraries/Permission.php:270`): when an env/config flag
(e.g. `PERMISSION_TABLES_READY=true`) is set, skip the `INFORMATION_SCHEMA` /
`SHOW TABLES` probe and assume the five RBAC tables exist. Keeps the probe as the
default for fresh/partial installs; eliminates 29.7s of probing in prod.

### A3. One-shot login response
Return the redirect URL directly in the `login_process` JSON payload so the
client no longer needs the second `get_redirect_url` request. Keep
`get_redirect_url` temporarily for backward compatibility, then remove once the
front-end no longer calls it.

---

## Stage B — Redesign (PR 2+)

### B1. Cache backend stabilization (do first — stabilizes prod)
Fix `Dashboard` cache init (`Dashboard.php:17-38`) so a missing memcached
extension is detected and the **file cache is actually used**. Approach: probe
`extension_loaded('memcached')` (and/or a real set/get round-trip) before
selecting the adapter; fall back to `file` otherwise. Verify the
per-calculation MD5-keyed caches (`calculate_ads_spending`, `calculate_kol_spending`,
`calculate_etc_spending`) then populate. This alone restores the 60s-TTL caching
the dashboard was designed around.

### B2. Session permission bootstrap
At login (after password verify, before computing the redirect), load the full
permission matrix once via the existing batch path
(`Permission::get_user_permissions($user_id)`, `Permission.php:156`) and store a
compact map in `$_SESSION['perm']` (module → caps) plus role/level and
`$_SESSION['perm_version']`.

Rewire runtime checks to read the session map first, DB only on miss:
- `Permission::has_module_access` (`Permission.php:99`)
- `Permission::check_permission` (`Permission.php:61`)

Result: normal page loads do **0** permission queries. `user_module_permissions`
and the role-join fallback are only touched on cold rebuild.

### B3. `perm_version` invalidation
- Single global version (e.g. a one-row `permission_meta` table, or a cache key).
- Bump it in `Roles::sync_user_permissions_for_role` (`application/controllers/Roles.php:427`)
  wherever `user_module_permissions` is resynced.
- A lightweight middleware/check compares `$_SESSION['perm_version']` to the
  current version once per request (PK lookup ~0.1ms). On mismatch, rebuild the
  session map from `user_module_permissions` (the warm read model) and refresh
  the stored version.

### B4. Redirect from session map
Rewrite `get_user_default_page` (`Auth.php:136`) to read `$_SESSION['perm']`
instead of calling `has_module_access` repeatedly. Zero DB during redirect.

### B5. Lazy dashboard widgets
After B1 stabilizes caching, make the heavy spend cards lazy: `Dashboard::index`
renders the shell + sidebar immediately; spend numbers load via AJAX endpoints
that reuse the existing `calculate_*_spending` methods. Fixes perceived speed
regardless of cache backend.

---

## Out of scope
- Sidebar sort optimization (already cheap).
- Retiring `user_module_permissions` (kept as fallback/read model; revisit later).
- bcrypt cost tuning (intentional).

## Verification
- **A1**: `EXPLAIN SELECT * FROM user WHERE username = ?` shows index use, not
  `type=ALL`. Re-run login load test; confirm scan rows drop.
- **A2**: With flag on, `SHOW TABLES LIKE` count drops to ~0 in a new pschema
  digest; login still works for admin + non-admin roles.
- **A3**: Login completes in one request; redirect lands correctly per role.
- **B1**: Dashboard second load served from cache (log `cache hit`); aggregation
  queries absent from the digest on warm load.
- **B2/B4**: New pschema digest shows `user_module_permissions` COUNT and
  `MAX(rp.can_view)` fallback calls drop near-zero on normal navigation.
- **B3**: Edit a role → bump version → next page load for an affected user
  rebuilds the map and reflects the new permission.
- **B5**: Dashboard shell renders before spend numbers; widget AJAX returns
  matching totals.
- End-to-end: re-run `loadtest/` against the login → redirect → dashboard flow
  and compare latency knee vs the 2026-06-20 baseline.
