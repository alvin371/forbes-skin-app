# Configuration & Security

Config files, the full env catalog, and the security debt the migration must address.

## Configuration files (`application/config/`)

| File | Key facts |
|---|---|
| `config.php` | `base_url` auto-detected; `index_page=''` (clean URLs via mod_rewrite); session driver `files`, cookie `ci_session`, SameSite=Lax, TTL 7200s, save path `APPPATH.'cache/sessions/'`; `log_threshold=0` (logging off); **`csrf_protection=FALSE`**; **`global_xss_filtering=FALSE`**; composer autoload disabled. |
| `database.php` | mysqli via `env()`: `DB_HOSTNAME/USERNAME/PASSWORD/DATABASE/DRIVER`. `db_debug` on unless `CI_ENV=production`. utf8/utf8_general_ci. **`save_queries=TRUE`** (memory growth on long requests). |
| `autoload.php` | libs `database, email, session, template, telegrambot`; helpers `url, file, attachment`; model `mymodel`. |
| `routes.php` | 385 lines; `translate_uri_dashes=TRUE`; default `home/index`; 404 `page/error`. See `03-api-surface.md`. |
| `constants.php` | file/dir modes, fopen modes, exit codes. |
| `hooks.php` | lifecycle hooks. |
| `email.php`, `telegram.php` | service config (env-driven). |

- **Env loader:** `application/helpers/env_helper.php` — `env($key, $default)`, parses `.env` once (static cache), strips quotes/comments, falls back to `getenv()`. **DB key is `DB_HOSTNAME` (not `DB_HOST`).**
- **Environment:** set in `index.php` (`ENVIRONMENT`, currently `development` → `error_reporting(0)`), timezone `Asia/Jakarta`.

## `.env` variable catalog (from `.env.example`)

### Database
`DB_HOSTNAME`, `DB_USERNAME`, `DB_PASSWORD`, `DB_DATABASE`, `DB_DRIVER`(=mysqli)

### Application / observability
`CI_ENV`(development|testing|production), `BASE_URL`, `SENTRY_DSN`, `SENTRY_ENVIRONMENT`, `SENTRY_RELEASE`, `SENTRY_TRACES_SAMPLE_RATE`

### Google OAuth
`GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_PROJECT_ID`, `GOOGLE_AUTH_URI`, `GOOGLE_TOKEN_URI`, `GOOGLE_AUTH_PROVIDER_CERT_URL`, `GOOGLE_REDIRECT_URI_PROD`, `GOOGLE_REDIRECT_URI_LOCAL`

### SMTP
`SMTP_HOST`, `SMTP_USER`, `SMTP_PASS`, `SMTP_PORT_SSL`(465), `SMTP_PORT_TLS`(587)

### Telegram
`TELEGRAM_BOT_TOKEN`, `TELEGRAM_GROUP_CHAT_ID`, `TELEGRAM_PARSE_MODE`(HTML)

### TikTok Shop + worker
`TIKTOK_APP_KEY`, `TIKTOK_APP_SECRET`, `TIKTOK_WORKER_URL`, `TIKTOK_WORKER_MODE`(node|rust), `NODE_BIN`, `WORKER_SHARED_SECRET`, `WORKER_IP_ALLOWLIST`

### Other marketplaces
`LAZADA_APP_KEY`, `LAZADA_APP_SECRET`, `SHOPEE_PARTNER_ID`, `SHOPEE_PARTNER_KEY`, `META_APP_ID`, `META_APP_SECRET`

### TikTok Business Center
`TIKTOK_BC_APP_ID`, `TIKTOK_BC_APP_SECRET`, `TIKTOK_BC_ACCESS_TOKEN`

### Scraping
`RAPIDAPI_HOST`, `RAPIDAPI_KEY` (legacy); `SCRAPINGBOT_USERNAME`, `SCRAPINGBOT_API_KEY`, `SCRAPINGBOT_BASE_URL`

### reCAPTCHA
`RECAPTCHA_SITE_KEY`, `RECAPTCHA_SECRET_KEY`

### HRMS API auth
`API_JWT_SECRET`, `API_JWT_TTL_MIN`(30), `API_REFRESH_TTL_DAYS`(30), `PIN_MAX_ATTEMPTS`(5), `PIN_LOCKOUT_MINUTES`(15)

## Caching & sessions
- Sessions: file-based (`application/cache/sessions/`) — **stateful, blocks horizontal scaling & SPA statelessness.**
- Dashboard cache: Memcached → file fallback, 60s TTL, MD5 keys.
- `predis/predis` available for Redis (sessions/cache) — preferred target for a scalable API.

---

## Security posture — debt to fix in migration

### 1. Disabled framework protections
- **CSRF off** (`csrf_protection=FALSE`) — acceptable only if moving to token-auth APIs (CSRF N/A for Bearer); web forms today are unprotected.
- **XSS filtering off** (`global_xss_filtering=FALSE`) — output escaping is manual/inconsistent. New frontend (SPA) must escape on render; API must not trust stored data.

### 2. SQL injection hotspots (string-concatenated SQL)
These need parameterization or an ORM/query-builder discipline in the new backend. Confirmed patterns:
- **`Mymodel::selectWithQuery($rawSql)`** — the central raw-SQL sink, called widely from `Dashboard`, `Ads`, `Ajax`, marketplace controllers with interpolated values.
- **`Dashboard`** brand filter: `$firstLetter = substr($_GET['brand'],0,1)` interpolated into `LIKE '{$firstLetter}%'` across `shop_name`/`advertiser_name`/`account_name` (repeated at multiple methods) — **direct GET→SQL**. Also date ranges interpolated into `WHERE DATE(date) BETWEEN '$start' AND '$until'`.
- **`OvertimeRequestModel`** overlap check: `$this->db->where("(start_time < '$endTime' AND end_time > '$startTime')")` — string concat in `where()`.
- HRMS models (`ApprovalRouteVersionModel`, `LeaveLedgerModel`, `ApprovalStepModel`, `OvertimeRequestModel::get_user_monthly_summary`) mostly use `?` bindings — **good reference**.

### 3. Authentication weaknesses
- **Hybrid MD5/`password_hash`** — legacy MD5 hashes auto-upgraded on login; un-migrated accounts still hold MD5. Force-upgrade or reset during migration.
- **JWT secret default** `change-me`/`change-this-secret` — must be a strong secret in prod; HS256 symmetric (consider RS256 for multi-service).
- **JWT falls back to session** (`ApiAuth::authenticate`) — convenient but blurs the auth boundary; a pure API should not silently accept session cookies.
- **`db_debug` true in non-prod** leaks SQL errors; ensure `CI_ENV=production` in prod.
- **`save_queries=TRUE`** retains every query in memory — disable in prod for long-running/worker requests.

### 4. Authorization coupling
- RBAC enforced inside `BaseController.__construct` against `user_module_permissions` and tied to `$_SESSION`. Hardcoded bypass list (`auth, ajax, api, api_v2, api_v3`) means **all `Api*` controllers self-police** — verify each one actually checks the worker secret / JWT (gaps here are auth holes).

### 5. Secrets in env
All third-party secrets live in `.env` (not a vault). `TIKTOK_BC_ACCESS_TOKEN` is a long-lived token in plaintext. Consider a secrets manager in the target deployment.

> Full prioritized remediation + sequencing is in `08-migration-notes.md`.
