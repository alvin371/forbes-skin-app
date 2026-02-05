# Transaction Sync (TikTok Marketplace)

This document explains how the **Transaction** module syncs order data from a **specific TikTok Shop (marketplace)**.

## Scope
The sync described here is for **TikTok Shop orders** and is triggered from the Transaction UI. It fetches orders from TikTok, then inserts or updates records in the local `transaction` table.

## Entry Points
- UI modal: `application/views/transaction/sync.php`
- Controller: `application/controllers/Transaction.php` (`sync()` and `sync_process()`)
- Internal API: `application/controllers/Api_v2.php` (`marketplace_order()`)

## High-Level Flow
1. **Open the Transaction page** with a date range (`start_date`, `until_date`).
2. The **Sync modal** loads **active marketplace stores** from `marketplace_config`.
3. Click **Sync Data** on a specific store (TikTok) to send a request:
   `transaction/sync-process?marketplace=TIKTOK&shop_id=...&start_date=YYYY-MM-DD&until_date=YYYY-MM-DD`
4. `Transaction::sync_process()` calls the internal API endpoint:
   `api/marketplace/order`.
5. `Api_v2::marketplace_order()` detects `marketplace = TIKTOK`, calls TikTok Shop API, and updates/inserts orders into `transaction`.

## Detailed TikTok Sync Steps
### 1) Store selection (specific TikTok shop)
- The modal lists stores from `marketplace_config` with `status = 'Aktif'`.
- Each store carries:
  - `shop_id`
  - `shop_name`
  - `opt` (marketplace name, e.g. `TIKTOK`)
  - `val` JSON config (TikTok credentials)

### 2) Sync request parameters
`Transaction::sync_process()` uses:
- `marketplace` (must be `TIKTOK`)
- `shop_id` (the store you want to sync)
- `start_date` (YYYY-MM-DD)
- `until_date` (YYYY-MM-DD)

### 3) Internal API call
`Transaction::sync_process()` calls:
```
{endpoint_url}/api/marketplace/order?marketplace=TIKTOK&shop_id=...&start_date=YYYY-MM-DD&until_date=YYYY-MM-DD
```
`endpoint_url` comes from `Template::endpoint_url()`.

### 4) TikTok API request
Inside `Api_v2::marketplace_order()`:
- Reads TikTok credentials:
  - `TIKTOK_APP_KEY`, `TIKTOK_APP_SECRET` (env)
  - `access_token`, `shop.cipher` from `marketplace_config.val`
- Builds a signed request to:
  `https://open-api.tiktokglobalshop.com/api/orders/search`
- Sends a `POST` body containing:
  - `cursor`, `page_size`
  - `create_time_from`, `create_time_to`
  - `sort_by`, `sort_type`

**Date range handling**:
- `start_date` => `YYYY-MM-DD 00:00:00`
- `until_date` => `YYYY-MM-DD 00:00:00`, then **+1 day** (inclusive range)

**Example TikTok API call (curl)**  
Note: `sign` is generated in code (`tiktok_signature_generator`) and depends on query + body + secret.
```bash
curl -X POST \
  'https://open-api.tiktokglobalshop.com/api/orders/search?access_token=YOUR_ACCESS_TOKEN&app_key=YOUR_APP_KEY&shop_id=YOUR_SHOP_ID&sign=YOUR_SIGN&timestamp=YOUR_TIMESTAMP&version=202212' \
  -H 'Content-Type: application/json' \
  -H 'x-tts-access-token: YOUR_ACCESS_TOKEN' \
  -d '{
    "cursor": "",
    "page_size": 100,
    "sort_by": "CREATE_TIME",
    "create_time_from": 1706745600,
    "create_time_to": 1706832000,
    "sort_type": 2
  }'
```

### 5) Order mapping and persistence
For each order in `order_list`:
- Find existing transaction by `order_id + marketplace`.
- Map TikTok status to internal `order_status`:
  - `100` -> `UNPAID`
  - `112`, `105` -> `PROCESSED`
  - `140` -> `CANCELLED`
  - `130` -> `COMPLETED`
  - `122` -> `DELIVERED`
  - `121`, `114` -> `SHIPPED` (also sets `is_shipped = 1`)
  - `111` -> `READY_TO_SHIP`
- If existing: **update** `transaction`.
- If new and not cancelled: **insert** into `transaction`.

## Notes And Quirks
- In `Transaction::sync_process`, `start_date` is currently set from `until_date`.
- That means the API receives the same start and end date, so the sync is effectively a single-day sync.

## Token Refresh
If the TikTok access token is expired, use the **Refresh Token** button. It hits:
`marketplace-account/refresh-token-process?marketplace=TIKTOK&shop_id=...`

## Requirements / Configuration
- `.env`:
  - `TIKTOK_APP_KEY`
  - `TIKTOK_APP_SECRET`
- `marketplace_config` table:
  - `opt = 'TIKTOK'`
  - `status = 'Aktif'`
  - `val` JSON contains `app_key`, `access_token`, and `shop.cipher`

## Related Files
- `application/controllers/Transaction.php`
- `application/views/transaction/sync.php`
- `application/controllers/Api_v2.php`
