# TikTok Transaction Sync (forbes-skin-app)

This document is the **full reference** for how TikTok order sync works for `/transaction` in **forbes-skin-app**.  
It is aligned to **bhskin-application** as the source of truth.

## Scope
This covers **all related parts** of TikTok order sync:
- UI and routes
- Controller flow
- Internal API flow
- TikTok API request shape + signature
- Mapping into `transaction` table
- Debug/logging and common failure reasons
- Detail refresh behavior

## Entry Points (UI -> Internal API)
Transaction page: `application/controllers/Transaction.php` (`index`)  
Default date range: `start_date` = first day of current month, `until_date` = today

Sync modal: `application/views/transaction/sync.php`  
Uses stores from `marketplace_config` where `status = 'Aktif'`.

Sync action URL:
`transaction/sync-process?marketplace=TIKTOK&shop_id=...&start_date=YYYY-MM-DD&until_date=YYYY-MM-DD`

Internal API call:
```
{endpoint_url}/api/marketplace/order?marketplace=TIKTOK&shop_id=...&start_date=YYYY-MM-DD&until_date=YYYY-MM-DD&debug=1
```
`endpoint_url` is from `Template::endpoint_url()`.

## TikTok Sync: Internal API Flow
**Source file:** `application/controllers/Api_v2.php`  
**Function:** `marketplace_order()`

### 1) Configuration and credentials
`marketplace_config.val` (JSON) contains `app_key`, `access_token`, `shop.cipher`.

`.env` contains `TIKTOK_APP_KEY`, `TIKTOK_APP_SECRET`.

Controller loads env via `require_once application/helpers/env_helper.php`.

### 2) Date range conversion
Input: `start_date` and `until_date` (YYYY-MM-DD).

Converted to Unix timestamps:
- `start_time` = `start_date 00:00:00`
- `until_time` = `(until_date + 1 day) 00:00:00`

This makes the range **inclusive** of `until_date`.

### 3) TikTok orders/search request
Endpoint:
`https://open-api.tiktokglobalshop.com/order/202309/orders/search`

Query params: `app_key`, `shop_cipher`, `sort_field=create_time`, `sort_order=ASC`, `timestamp`, `page_size=100`, `page_token` (optional), `sign` (HMAC-SHA256)

Headers: `content-type: application/json`, `x-tts-access-token: <access_token>`

Body (JSON): `create_time_ge`, `create_time_lt`, `update_time_ge`, `update_time_lt`

Pagination: `next_page_token` from response becomes `page_token`.  
Loop runs up to 100 pages or stops when `orders` is empty.

Example curl:
```bash
curl -X POST \
  'https://open-api.tiktokglobalshop.com/order/202309/orders/search?app_key=APP_KEY&shop_cipher=SHOP_CIPHER&sort_field=create_time&sort_order=ASC&timestamp=1623812664&page_size=20&sign=SIGN' \
  -H 'x-tts-access-token: ACCESS_TOKEN' \
  -H 'content-type: application/json' \
  -d '{
    "create_time_ge": 1623812664,
    "create_time_lt": 1623899064,
    "update_time_ge": 1623812664,
    "update_time_lt": 1623899064
  }'
```

### 4) TikTok signature generation
**Function:** `Api_v2::tiktok_signature_generator()`  
**Rules (aligned with TikTok docs):**
1. Take all query parameters **except** `sign` and `access_token`
2. Sort by key (ascending)
3. Build string: `{path}{key}{value}{key}{value}...`
4. Append request body (JSON) if present
5. Wrap with secret: `secret + input + secret`
6. HMAC-SHA256 with `secret`

The function reads: `path` from URL, `get` params, `post` body string, `secret` = `TIKTOK_APP_SECRET`.

### 5) Response handling
Response is decoded from JSON.
Orders are read from `data.orders` (preferred) or `data.order_list` (fallback).

If no orders, the page loop stops.

### 6) Mapping to `transaction`
For each order, the sync fills these fields in `transaction`:
- `order_id` = `order_id` or `id`
- `marketplace` = `TIKTOK`
- `shop_id`, `shop_name`
- `is_manual` = `0`
- `date` from `create_time`
- `c_type` from `is_sample_order`
- `shipping` from `shipping_provider` or `delivery_option_name`
- `awb_number` from `tracking_number`
- `payment_type` from `is_cod`
- `payment_status` + `pay_at` from `paid_time`
- `rts_at` from `rts_time` (fallback `rts_sla_time`)
- `return_at` from `cancel_time`
- `c_username` from `buyer_nickname` (fallback `buyer_email`)
- `id_buyer` from `buyer_user_id` (fallback `user_id`)
- `customer_text`, `phone`, `address`, `postal_code`, region fields from `recipient_address`
- `customer_price`, `omset_kotor`, `diskon_penjual`, `omset_bersih` from `payment`

#### Line items → `pesanan`
If `line_items` exists it fills `pesanan`, `pesanan_count`, `json`, `hpp`, and sets `brand` using item majority.

If `line_items` is missing, product-related fields remain empty and the UI will show `0` or `-`.

### 7) Status mapping
TikTok status → internal `order_status`:
- `UNPAID` → `UNPAID`
- `ON_HOLD`, `AWAITING_COLLECTION` → `PROCESSED`
- `AWAITING_SHIPMENT` → `READY_TO_SHIP`
- `IN_TRANSIT`, `PARTIALLY_SHIPPING` → `SHIPPED` (+ `is_shipped = 1`)
- `DELIVERED` → `DELIVERED`
- `COMPLETED` → `COMPLETED`
- `CANCELLED` → `CANCELLED`

### 8) Insert vs update
- If transaction exists (`order_id + marketplace`): **update**
- If not exists and status is not cancelled: **insert**
- `created_at` and `updated_at` are set accordingly

## Detail Refresh (Full Order Data)
Syncing **orders/search** does not always return full details.  
For full data (products, finance, return status), use:
`/transaction/refresh` → `Transaction::refresh_process()` → `Api_v2::marketplace_order_detail()`

Detail endpoint:
`https://open-api.tiktokglobalshop.com/order/202309/orders?ids=<order_id>`

This flow also writes product mapping + `json`, computes `hpp`/`omset`/`marketplace_fee`, and fills address and payment detail.

## Debugging
`Transaction::sync_process()` always enables debug for TikTok.
Debug includes: final endpoint URL, HTTP status, cURL error, TikTok request URL, response code/message/request_id, and a preview of the payload.

The debug block is appended to the Sync modal response.

## Common Failure Reasons
- Invalid `sign` (most common)
- Wrong `app_key` / `app_secret`
- Wrong `access_token`
- `timestamp` too far from current time
- Request body mismatch in signature generation

## Related Files
- `application/controllers/Transaction.php`
- `application/views/transaction/sync.php`
- `application/controllers/Api_v2.php`
- `application/controllers/Api.php` (proxy for `orders/search`, not used by sync)
- `application/helpers/env_helper.php`
