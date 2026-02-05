#!/usr/bin/env node
"use strict";

const fs = require("fs");
const path = require("path");
const crypto = require("crypto");

const DEFAULT_TIMEOUT_MS = 60000;
const PAGE_LIMIT = 100;
const PRODUCT_DETAIL_CONCURRENCY = 5;
const PRODUCT_INGEST_BATCH = 20;

function parseArgs(argv) {
  const args = { _: [] };
  for (let i = 2; i < argv.length; i += 1) {
    const token = argv[i];
    if (!token.startsWith("--")) {
      args._.push(token);
      continue;
    }
    const eqIdx = token.indexOf("=");
    if (eqIdx !== -1) {
      const key = token.slice(2, eqIdx);
      const value = token.slice(eqIdx + 1);
      args[key] = value;
    } else {
      const key = token.slice(2);
      const next = argv[i + 1];
      if (next && !next.startsWith("--")) {
        args[key] = next;
        i += 1;
      } else {
        args[key] = "true";
      }
    }
  }
  return args;
}

function loadEnv(rootDir) {
  const env = {};
  const envPath = path.join(rootDir, ".env");
  if (!fs.existsSync(envPath)) {
    return env;
  }
  const content = fs.readFileSync(envPath, "utf8");
  const lines = content.split(/\r?\n/);
  for (const line of lines) {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith("#")) {
      continue;
    }
    const eqIdx = trimmed.indexOf("=");
    if (eqIdx === -1) {
      continue;
    }
    const key = trimmed.slice(0, eqIdx).trim();
    let value = trimmed.slice(eqIdx + 1).trim();
    if ((value.startsWith("\"") && value.endsWith("\"")) || (value.startsWith("'") && value.endsWith("'"))) {
      value = value.slice(1, -1);
    }
    env[key] = value;
  }
  return env;
}

function envValue(env, key) {
  return process.env[key] || env[key] || "";
}

function normalizeBaseUrl(value) {
  if (!value) {
    return "";
  }
  let base = value.trim();
  if (!/^https?:\/\//i.test(base)) {
    base = `http://${base}`;
  }
  return base.replace(/\/+$/, "");
}

function formatDate(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

function firstDayOfMonth(date = new Date()) {
  return formatDate(new Date(date.getFullYear(), date.getMonth(), 1));
}

function todayDate() {
  return formatDate(new Date());
}

function addDays(dateStr, days) {
  const [y, m, d] = dateStr.split("-").map((item) => parseInt(item, 10));
  const date = new Date(y, m - 1, d);
  date.setDate(date.getDate() + days);
  return formatDate(date);
}

function toUnix(dateStr) {
  const [y, m, d] = dateStr.split("-").map((item) => parseInt(item, 10));
  const date = new Date(y, m - 1, d, 0, 0, 0);
  return Math.floor(date.getTime() / 1000);
}

function tiktokSignature({ url, secret, timestamp, query, body }) {
  const params = {};
  for (const [key, value] of Object.entries(query || {})) {
    if (key === "sign" || key === "access_token") {
      continue;
    }
    if (key === "timestamp" && timestamp != null) {
      params[key] = timestamp;
    } else {
      params[key] = value;
    }
  }
  const sortedKeys = Object.keys(params).sort();
  const pathPart = new URL(url).pathname;
  let input = pathPart;
  for (const key of sortedKeys) {
    input += `${key}${params[key]}`;
  }
  const bodyString = typeof body === "string" ? body : body ? JSON.stringify(body) : "";
  if (bodyString) {
    input += bodyString;
  }
  input = `${secret}${input}${secret}`;
  return crypto.createHmac("sha256", secret).update(input).digest("hex");
}

async function fetchWithTimeout(url, options = {}, timeoutMs = DEFAULT_TIMEOUT_MS) {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), timeoutMs);
  try {
    const response = await fetch(url, { ...options, signal: controller.signal });
    return response;
  } finally {
    clearTimeout(timeout);
  }
}

async function fetchJson(url, options = {}) {
  const response = await fetchWithTimeout(url, options);
  const text = await response.text();
  let data = null;
  try {
    data = text ? JSON.parse(text) : null;
  } catch (err) {
    data = null;
  }
  return { ok: response.ok, status: response.status, data, text };
}

async function mapLimit(items, limit, handler) {
  const results = [];
  let index = 0;
  const workers = new Array(Math.min(limit, items.length)).fill(null).map(async () => {
    while (index < items.length) {
      const currentIndex = index;
      index += 1;
      try {
        results[currentIndex] = await handler(items[currentIndex], currentIndex);
      } catch (err) {
        results[currentIndex] = null;
      }
    }
  });
  await Promise.all(workers);
  return results;
}

function chunkArray(items, size) {
  const chunks = [];
  for (let i = 0; i < items.length; i += size) {
    chunks.push(items.slice(i, i + size));
  }
  return chunks;
}

async function fetchMarketplaceConfig(apiBase, shopId, workerSecret) {
  const url = new URL("/api/marketplace/config", apiBase);
  url.searchParams.set("marketplace", "TIKTOK");
  if (shopId) {
    url.searchParams.set("shop_id", shopId);
  }
  const headers = {};
  if (workerSecret) {
    headers["x-worker-secret"] = workerSecret;
  }
  const response = await fetchJson(url.toString(), { method: "GET", headers });
  if (!response.ok || !response.data || response.data.status !== true) {
    throw new Error(response.data?.msg || response.text || "Failed to load marketplace config");
  }
  return response.data.data || [];
}

async function ingestOrders(apiBase, payload, workerSecret) {
  const url = new URL("/api/marketplace/order/ingest", apiBase);
  const headers = { "content-type": "application/json" };
  if (workerSecret) {
    headers["x-worker-secret"] = workerSecret;
  }
  const response = await fetchJson(url.toString(), {
    method: "POST",
    headers,
    body: JSON.stringify(payload),
  });
  if (!response.ok || !response.data || response.data.status !== true) {
    throw new Error(response.data?.msg || response.text || "Order ingest failed");
  }
  return response.data;
}

async function ingestProducts(apiBase, payload, workerSecret) {
  const url = new URL("/api/marketplace/product/ingest", apiBase);
  const headers = { "content-type": "application/json" };
  if (workerSecret) {
    headers["x-worker-secret"] = workerSecret;
  }
  const response = await fetchJson(url.toString(), {
    method: "POST",
    headers,
    body: JSON.stringify(payload),
  });
  if (!response.ok || !response.data || response.data.status !== true) {
    throw new Error(response.data?.msg || response.text || "Product ingest failed");
  }
  return response.data;
}

async function tiktokRequest({ method, endpoint, query, body, accessToken, secret }) {
  const timestamp = Math.floor(Date.now() / 1000);
  const queryParams = { ...query, timestamp };
  const bodyJson = body ? JSON.stringify(body) : "";
  const sign = tiktokSignature({ url: endpoint, secret, timestamp, query: queryParams, body: bodyJson });
  queryParams.sign = sign;
  const url = `${endpoint}?${new URLSearchParams(queryParams).toString()}`;
  const headers = {
    "content-type": "application/json",
  };
  if (accessToken) {
    headers["x-tts-access-token"] = accessToken;
  }
  const response = await fetchJson(url, {
    method,
    headers,
    body: method === "GET" ? undefined : bodyJson,
  });
  return response;
}

async function syncOrdersForShop({ config, apiBase, appSecret, startDate, untilDate, debug, workerSecret }) {
  const stats = { pages: 0, orders: 0, ingested: 0 };
  const start = startDate || firstDayOfMonth();
  const until = untilDate || todayDate();
  const startTime = toUnix(start);
  const untilTime = toUnix(addDays(until, 1));

  let pageToken = "";
  for (let page = 0; page < PAGE_LIMIT; page += 1) {
    const query = {
      app_key: config.app_key,
      shop_cipher: config.shop_cipher,
      sort_field: "create_time",
      sort_order: "ASC",
      page_size: 100,
    };
    if (pageToken) {
      query.page_token = pageToken;
    }
    const body = {
      create_time_ge: startTime,
      create_time_lt: untilTime,
      update_time_ge: startTime,
      update_time_lt: untilTime,
    };
    const response = await tiktokRequest({
      method: "POST",
      endpoint: "https://open-api.tiktokglobalshop.com/order/202309/orders/search",
      query,
      body,
      accessToken: config.access_token,
      secret: appSecret,
    });
    if (!response.ok || !response.data || response.data.code) {
      throw new Error(response.data?.message || response.text || "TikTok orders request failed");
    }
    const orders = Array.isArray(response.data?.data?.orders)
      ? response.data.data.orders
      : Array.isArray(response.data?.data?.order_list)
      ? response.data.data.order_list
      : [];

    if (!orders.length) {
      break;
    }

    stats.pages += 1;
    stats.orders += orders.length;

    await ingestOrders(apiBase, {
      marketplace: "TIKTOK",
      shop_id: config.shop_id,
      shop_name: config.shop_name,
      start_date: start,
      until_date: until,
      debug: !!debug,
      orders,
    }, workerSecret);

    stats.ingested += orders.length;

    const nextPage = response.data?.data?.next_page_token || "";
    if (!nextPage) {
      break;
    }
    pageToken = nextPage;
  }

  return stats;
}

async function fetchProductDetail({ config, appSecret, productId }) {
  const endpoint = `https://open-api.tiktokglobalshop.com/product/202309/products/${productId}`;
  const query = {
    app_key: config.app_key,
    shop_cipher: config.shop_cipher,
    shop_id: config.shop_id,
    access_token: config.access_token,
    version: "202309",
  };
  const response = await tiktokRequest({
    method: "GET",
    endpoint,
    query,
    body: null,
    accessToken: config.access_token,
    secret: appSecret,
  });
  if (!response.ok || !response.data || response.data.code) {
    throw new Error(response.data?.message || response.text || "TikTok product detail failed");
  }
  return response.data?.data || null;
}

async function syncProductsForShop({ config, apiBase, appSecret, workerSecret }) {
  const stats = { pages: 0, products: 0, ingested: 0 };
  let pageToken = "";

  for (let page = 0; page < PAGE_LIMIT; page += 1) {
    const endpoint = "https://open-api.tiktokglobalshop.com/product/202312/products/search";
    const query = {
      access_token: config.access_token,
      app_key: config.app_key,
      page_size: 100,
      page_token: pageToken,
      shop_cipher: config.shop_cipher,
      shop_id: config.shop_id,
      version: "202312",
    };

    const response = await tiktokRequest({
      method: "POST",
      endpoint,
      query,
      body: { status: "ACTIVATE" },
      accessToken: config.access_token,
      secret: appSecret,
    });

    if (!response.ok || !response.data || response.data.code) {
      throw new Error(response.data?.message || response.text || "TikTok product search failed");
    }

    const products = Array.isArray(response.data?.data?.products) ? response.data.data.products : [];
    const nextPage = response.data?.data?.next_page_token || "";

    if (!products.length) {
      break;
    }

    stats.pages += 1;
    stats.products += products.length;

    const details = await mapLimit(products, PRODUCT_DETAIL_CONCURRENCY, async (item) => {
      const id = item?.id;
      if (!id) {
        return null;
      }
      return fetchProductDetail({ config, appSecret, productId: id });
    });

    const filtered = details.filter((item) => item);
    for (const batch of chunkArray(filtered, PRODUCT_INGEST_BATCH)) {
      await ingestProducts(apiBase, {
        marketplace: "TIKTOK",
        shop_id: config.shop_id,
        shop_name: config.shop_name,
        products: batch,
      }, workerSecret);
      stats.ingested += batch.length;
    }

    if (!nextPage) {
      break;
    }
    pageToken = nextPage;
  }

  return stats;
}

async function main() {
  const args = parseArgs(process.argv);
  const mode = args.mode || args._[0];
  const shopId = args.shop_id || args.shopId || "";
  const startDate = args.start_date || "";
  const untilDate = args.until_date || "";
  const debug = args.debug === "1" || args.debug === "true";

  if (!mode || (mode !== "orders" && mode !== "products")) {
    throw new Error("Mode is required: orders or products");
  }

  const rootDir = path.resolve(__dirname, "..", "..");
  const env = loadEnv(rootDir);
  const apiBase = normalizeBaseUrl(args["api-base"] || envValue(env, "ENDPOINT_URL") || envValue(env, "BASE_URL") || "http://127.0.0.1");
  const appSecret = envValue(env, "TIKTOK_APP_SECRET");

  if (!appSecret) {
    throw new Error("TIKTOK_APP_SECRET is missing in environment");
  }

  const workerSecret = envValue(env, "WORKER_SHARED_SECRET");
  const configs = await fetchMarketplaceConfig(apiBase, shopId, workerSecret);
  if (!configs.length) {
    throw new Error("No active TikTok shop found");
  }

  const summary = { mode, shops: [], status: true };

  for (const config of configs) {
    if (mode === "orders") {
      const stats = await syncOrdersForShop({ config, apiBase, appSecret, startDate, untilDate, debug, workerSecret });
      summary.shops.push({ shop_id: config.shop_id, shop_name: config.shop_name, stats });
    } else {
      const stats = await syncProductsForShop({ config, apiBase, appSecret, workerSecret });
      summary.shops.push({ shop_id: config.shop_id, shop_name: config.shop_name, stats });
    }
  }

  const msg = mode === "orders" ? "Sync data order berhasil!" : "Sync data produk berhasil!";
  return { status: true, msg, data: summary };
}

main()
  .then((result) => {
    process.stdout.write(`${JSON.stringify(result)}\n`);
  })
  .catch((err) => {
    const payload = { status: false, msg: err.message || String(err) };
    process.stdout.write(`${JSON.stringify(payload)}\n`);
    process.exitCode = 1;
  });
