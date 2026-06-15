# External Integrations

Every third-party dependency, its credentials, token model, and where it lives. These are the highest-risk items in a migration (rate limits, OAuth re-consent, token storage). Env var details in `07-config-and-security.md`.

## Token storage model

- **Marketplace tokens** (Shopee/Lazada/TikTok Shop) → DB table **`marketplace_config`** (per-shop access/refresh token + expiry). Refreshed by `Api_v2/marketplace_token_refresh`.
- **Google OAuth tokens** → stored per integration (Meet/MOU) after `oauth2callback`.
- **JWT refresh tokens** (HRMS API) → `api_refresh_tokens` (sha256-hashed).
- **App-level API keys** (ScrapingBot, RapidAPI, Meta, TikTok BC, Telegram, reCAPTCHA) → `.env` only.

---

## Marketplaces

### Shopee
- Env: `SHOPEE_PARTNER_ID`, `SHOPEE_PARTNER_KEY`.
- OAuth callback `/api/marketplace/callback/shopee` (`Api_v2`). Signing: HMAC-SHA256 over path+params+timestamp.
- Flows: order/product pull + ingest, webhook receiver, ads data (`shopee_ads_data`).
- Library: requests built in `Template` lib helpers (`api_key_ss`, `api_orders_ss`, `api_products_ss`) + `Api_v2`.

### Lazada
- Env: `LAZADA_APP_KEY`, `LAZADA_APP_SECRET`.
- SDK: **`appolous/lazada-php-sdk` v1.6** (`application/libraries/lazada/`).
- OAuth callback `/api/marketplace/callback/lazada`. Order/product/finance flows.

### TikTok Shop
- Env: `TIKTOK_APP_KEY`, `TIKTOK_APP_SECRET`.
- OAuth callback `/api/marketplace/callback/tiktok`; business OAuth via `TiktokAuth` (`/auth/redirect`, `/auth/callback`).
- **Order sync worker**: optional standalone service, `TIKTOK_WORKER_URL`, `TIKTOK_WORKER_MODE` (`node`|`rust`), `NODE_BIN`. Worker→app calls gated by `WORKER_SHARED_SECRET` + `WORKER_IP_ALLOWLIST`. Docs: `docs/tiktok-worker/`.
- Ads/GMV: `Api_v3/get_tiktok_campaign`, `get_tiktok_gmv` (`tiktok_ads_data`, `gmv_spend_data`).

### Meta / Facebook (Ads)
- Env: `META_APP_ID`, `META_APP_SECRET`.
- Ads data into `meta_ads_data`; account mgmt `Meta_account`. Read by Dashboard spend aggregation.

### TikTok Business Center
- Env: `TIKTOK_BC_APP_ID`, `TIKTOK_BC_APP_SECRET`, `TIKTOK_BC_ACCESS_TOKEN` (long-lived token in env).

## Social media scraping

### ScrapingBot (primary, current)
- Env: `SCRAPINGBOT_USERNAME`, `SCRAPINGBOT_API_KEY`, `SCRAPINGBOT_BASE_URL` (`http://api.scraping-bot.io`).
- Library: **`Scrapingbot.php`** — `startScrape()` (POST job), `pollResult()` (GET poll), `scrapeTiktokProfile($url)`, `scrapeInstagramProfile($account, $postsNumber)`. Basic auth `username:apiKey`. Async: pending/success/error.
- Architecture: queue-based via `scraping_queue` + 3 crons (`scraping-submit` 5m, `scraping-poll` 1m, `scraping-enqueue` 30m). Budget ~100K calls/month. **Deep-dive: `docs/SCRAPINGBOT_INTEGRATION_GUIDE.md`.**
- Response quirk: success returns raw array `[{...}]` (check `isset($data[0])` before `$data['status']`). Instagram returns flat post array w/ embedded profile. (Captured in repo memory + the guide.)

### RapidAPI (legacy TikTok)
- Env: `RAPIDAPI_HOST` (`tiktok-video-no-watermark10.p.rapidapi.com`), `RAPIDAPI_KEY`.
- Being phased out in favor of ScrapingBot. Doc: `docs/INFLUENCER_RAPIDAPI_INTEGRATION.md`. Helpers `getRapidApiHeaders()` in `Template` lib.

## Google

### Google Meet (`Googlemeet`)
- OAuth2 (Drive, Docs, Calendar, oauth2 email scopes). `/googlemeet`, `/googlemeet/oauth2callback`, `generate_link`.
- Creates Calendar event with Meet conference data; stores link on `interview`. Used by Recruitment/Interview.

### Google Docs MOU (`Googlemou`)
- OAuth2 (same scopes). Routes `/googlemou`, `/googlemou/oauth2callback`, `/endorse/action_generate_mou_pdf_gdocs`.
- Clones a Google Docs template, replaces placeholders with campaign/creator data, exports PDF, emails it. Used by Endorse.

### Shared Google env
`GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_PROJECT_ID`, `GOOGLE_AUTH_URI`, `GOOGLE_TOKEN_URI`, `GOOGLE_AUTH_PROVIDER_CERT_URL`, `GOOGLE_REDIRECT_URI_PROD`, `GOOGLE_REDIRECT_URI_LOCAL`.

## Notifications & infra services

| Service | Env | Where |
|---|---|---|
| **Telegram** | `TELEGRAM_BOT_TOKEN`, `TELEGRAM_GROUP_CHAT_ID`, `TELEGRAM_PARSE_MODE` | `Telegrambot` lib (autoloaded); payment/queue/workflow alerts |
| **SMTP email** | `SMTP_HOST`, `SMTP_USER`, `SMTP_PASS`, `SMTP_PORT_SSL`, `SMTP_PORT_TLS` | CI `email` lib (autoloaded); MOU + notifications |
| **Sentry** | `SENTRY_DSN`, `SENTRY_ENVIRONMENT`, `SENTRY_RELEASE`, `SENTRY_TRACES_SAMPLE_RATE` | `sentry/sentry ^4.18`, `sentry_helper.php`; `BaseController` tags user |
| **reCAPTCHA v3** | `RECAPTCHA_SITE_KEY`, `RECAPTCHA_SECRET_KEY` | signup/login bot protection |
| **Redis** | (predis config) | `predis/predis` available; caching/sessions |

## Migration considerations for integrations
- **Re-auth risk:** changing the backend host/redirect URIs forces OAuth re-consent (Google, marketplaces). Plan redirect URI cutover.
- **Token continuity:** `marketplace_config` and Google tokens must migrate intact, or shops/integrations silently break.
- **Worker coupling:** the TikTok worker is a separate deployable — keep its `WORKER_SHARED_SECRET`/IP contract or replace with the new API's auth.
- **Rate limits:** ScrapingBot (~100K/mo budget) and marketplace APIs are the throughput constraints — the new architecture should keep the async-queue model, not synchronous calls.
- **Scraping fragility:** parser logic is response-shape-sensitive (see ScrapingBot quirks) — port parsers as-is, with the documented edge cases.
