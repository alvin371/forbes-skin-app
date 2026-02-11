# TikTok Sync Workers (Node + Rust)

This document describes the TikTok sync worker enhancement for `forbes-skin-app`, with steps to replicate the same pattern in another project.

## Goal
- Move TikTok API calls out of PHP for better performance and non-blocking UI.
- Keep PHP responsible only for **data persistence** (transaction + product_3rd tables).
- Support **Node worker** as the default runtime, with an easy switch to **Rust worker** for order sync.

## Architecture
- UI calls existing sync endpoints.
- PHP decides which worker to use based on `TIKTOK_WORKER_MODE`.
- Worker calls TikTok APIs, then posts raw data back to PHP ingest endpoints.

Flow (Orders):
1. UI -> `transaction/sync-process`
2. PHP -> Node worker (default) or Rust worker
3. Worker -> TikTok API (orders/search)
4. Worker -> PHP `POST /api/marketplace/order/ingest`

Flow (Products, Node only):
1. UI -> `product-3rd/sync-process`
2. PHP -> Node worker
3. Worker -> TikTok API (products/search + product detail)
4. Worker -> PHP `POST /api/marketplace/product/ingest`

## Worker Modes
- **Node (default)**: `services/tiktok-worker/worker.js`
- **Rust (orders only)**: `services/tiktok-worker-rust` container

Switch using:
```
TIKTOK_WORKER_MODE=node
# or
TIKTOK_WORKER_MODE=rust
```

## Required Routes (PHP)
These are internal APIs used by the workers:
- `GET /api/marketplace/config`
- `POST /api/marketplace/order/ingest`
- `POST /api/marketplace/product/ingest`

## Security (Recommended)
Protect worker-only endpoints via **shared secret** and/or **IP allowlist**.

PHP expects:
- `X-Worker-Secret` header when `WORKER_SHARED_SECRET` is set
- Request IP to be included in `WORKER_IP_ALLOWLIST` if set

## Environment Variables
Add these in `.env` (and keep defaults in `.env.example`):

Required:
- `TIKTOK_APP_SECRET` (worker signature generation)

Optional:
- `TIKTOK_WORKER_MODE=node|rust` (default: node)
- `TIKTOK_WORKER_URL` (Rust worker base URL; default: `http://tiktok-worker:8081`)
- `WORKER_SHARED_SECRET` (shared secret for worker API calls)
- `WORKER_IP_ALLOWLIST` (comma-separated IPs)

## Node Worker (Default)
Location: `services/tiktok-worker/worker.js`

Requirements:
- Node.js 18+ (uses built-in `fetch`)

Manual usage:
```bash
node services/tiktok-worker/worker.js orders \
  --shop_id=SHOP_ID \
  --start_date=YYYY-MM-DD \
  --until_date=YYYY-MM-DD \
  --api-base=https://your-domain.com

node services/tiktok-worker/worker.js products \
  --shop_id=SHOP_ID \
  --api-base=https://your-domain.com
```

If `WORKER_SHARED_SECRET` is set, the worker sends `X-Worker-Secret` automatically.

## Rust Worker (Orders Only)
Location: `services/tiktok-worker-rust`

Dockerized service (see `docker-compose.yml`).
Key env vars:
- `APP_API_BASE` (PHP base URL inside Docker; e.g. `http://app`)
- `WORKER_PORT` (default: `8081`)
- `TIKTOK_APP_SECRET`
- `WORKER_SHARED_SECRET` (optional)

Health check:
```
GET /health
```

Order sync endpoint:
```
POST /sync/orders
```
Payload:
```json
{
  "shop_id": "123",
  "start_date": "2026-02-01",
  "until_date": "2026-02-05",
  "debug": true
}
```

## PHP Integration Points
- `application/controllers/Transaction.php`
  - Uses `TIKTOK_WORKER_MODE` to choose Node or Rust for TikTok orders.
- `application/controllers/Product_3rd.php`
  - Uses Node worker for TikTok product sync (when mode is `node`).
- `application/controllers/Api_v2.php`
  - `marketplace_config` + ingest endpoints for orders/products.

## Docker Compose (Rust Worker)
Add a service block like:
```yaml
  tiktok-worker:
    build:
      context: ./services/tiktok-worker-rust
      dockerfile: Dockerfile
    env_file:
      - .env
    environment:
      - APP_API_BASE=http://app
      - WORKER_PORT=8081
    networks:
      - hrms-network
```

## Replication Checklist (for another project)
1. Copy `services/tiktok-worker/worker.js` into the new repo.
2. Add worker-only PHP endpoints:
   - `GET /api/marketplace/config`
   - `POST /api/marketplace/order/ingest`
   - `POST /api/marketplace/product/ingest`
3. Add routes for those endpoints.
4. Update sync controllers to call the worker (Node by default).
5. Add env vars to `.env.example`.
6. (Optional) Add Rust worker container and switch with `TIKTOK_WORKER_MODE=rust`.
7. Set `WORKER_SHARED_SECRET` and `WORKER_IP_ALLOWLIST` if needed.

## Troubleshooting
- `TIKTOK_APP_SECRET is required`: missing in `.env`.
- `Unauthorized`: shared secret/IP allowlist mismatch.
- `response tidak valid dari worker`: worker failed or returned non-JSON.
- Node worker fails: ensure Node 18+.
