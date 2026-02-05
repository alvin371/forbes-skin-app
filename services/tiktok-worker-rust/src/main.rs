use axum::{
    extract::State,
    http::StatusCode,
    response::IntoResponse,
    routing::{get, post},
    Json, Router,
};
use chrono::{Datelike, NaiveDate, Utc};
use hmac::{Hmac, Mac};
use reqwest::Client;
use serde::{Deserialize, Serialize};
use serde_json::Value;
use sha2::Sha256;
use std::{collections::BTreeMap, net::SocketAddr, sync::Arc, time::Duration};
use tracing::{error, info};

const PAGE_LIMIT: usize = 100;

#[derive(Clone)]
struct AppState {
    http: Client,
    api_base: String,
    app_secret: String,
    worker_secret: Option<String>,
}

#[derive(Serialize)]
struct ApiResponse<T> {
    status: bool,
    msg: String,
    data: Option<T>,
}

#[derive(Deserialize)]
struct SyncOrdersRequest {
    shop_id: Option<String>,
    start_date: Option<String>,
    until_date: Option<String>,
    debug: Option<bool>,
}

#[derive(Deserialize)]
struct ConfigResponse {
    status: bool,
    data: Option<Vec<MarketplaceConfig>>,
    msg: Option<String>,
}

#[derive(Deserialize, Clone)]
struct MarketplaceConfig {
    shop_id: String,
    shop_name: String,
    marketplace: String,
    app_key: String,
    access_token: String,
    shop_cipher: String,
}

#[derive(Serialize)]
struct SyncSummary {
    shop_id: String,
    shop_name: String,
    pages: usize,
    orders: usize,
    ingested: usize,
}

#[tokio::main]
async fn main() {
    tracing_subscriber::fmt()
        .with_env_filter(tracing_subscriber::EnvFilter::from_default_env())
        .init();

    let api_base = std::env::var("APP_API_BASE")
        .or_else(|_| std::env::var("ENDPOINT_URL"))
        .or_else(|_| std::env::var("BASE_URL"))
        .unwrap_or_else(|_| "http://app".to_string());
    let app_secret = std::env::var("TIKTOK_APP_SECRET").unwrap_or_default();

    if app_secret.is_empty() {
        panic!("TIKTOK_APP_SECRET is required");
    }

    let timeout_secs: u64 = std::env::var("APP_API_TIMEOUT")
        .ok()
        .and_then(|val| val.parse::<u64>().ok())
        .unwrap_or(60);

    let http = Client::builder()
        .timeout(Duration::from_secs(timeout_secs))
        .build()
        .expect("failed to build http client");

    let worker_secret = std::env::var("WORKER_SHARED_SECRET").ok().filter(|v| !v.is_empty());

    let state = AppState {
        http,
        api_base: api_base.trim_end_matches('/').to_string(),
        app_secret,
        worker_secret,
    };

    let app = Router::new()
        .route("/health", get(health))
        .route("/sync/orders", post(sync_orders))
        .with_state(Arc::new(state));

    let port: u16 = std::env::var("WORKER_PORT")
        .ok()
        .and_then(|val| val.parse::<u16>().ok())
        .unwrap_or(8081);
    let addr = SocketAddr::from(([0, 0, 0, 0], port));

    info!("tiktok-worker listening on {}", addr);

    axum::Server::bind(&addr)
        .serve(app.into_make_service())
        .await
        .expect("server failed");
}

async fn health() -> impl IntoResponse {
    (StatusCode::OK, "ok")
}

async fn sync_orders(
    State(state): State<Arc<AppState>>,
    Json(payload): Json<SyncOrdersRequest>,
) -> impl IntoResponse {
    let debug = payload.debug.unwrap_or(false);
    let (start_date, until_date) = build_date_range(payload.start_date, payload.until_date);

    let configs = match fetch_configs(&state, payload.shop_id.clone()).await {
        Ok(items) => items,
        Err(err) => {
            error!("config error: {}", err);
            return Json(ApiResponse::<Value> {
                status: false,
                msg: err,
                data: None,
            });
        }
    };

    if configs.is_empty() {
        return Json(ApiResponse::<Value> {
            status: false,
            msg: "No active TikTok shop found".to_string(),
            data: None,
        });
    }

    let mut summaries = Vec::new();

    for config in configs {
        match sync_orders_for_shop(&state, &config, &start_date, &until_date, debug).await {
            Ok(summary) => summaries.push(summary),
            Err(err) => {
                error!("sync error for shop {}: {}", config.shop_id, err);
                return Json(ApiResponse::<Value> {
                    status: false,
                    msg: err,
                    data: None,
                });
            }
        }
    }

    Json(ApiResponse {
        status: true,
        msg: "Sync data order berhasil!".to_string(),
        data: Some(serde_json::json!({ "shops": summaries })),
    })
}

fn build_date_range(start_date: Option<String>, until_date: Option<String>) -> (String, String) {
    let today = Utc::now().date_naive();
    let default_start = NaiveDate::from_ymd_opt(today.year(), today.month(), 1).unwrap();

    let start = start_date
        .and_then(|val| NaiveDate::parse_from_str(&val, "%Y-%m-%d").ok())
        .unwrap_or(default_start);
    let until = until_date
        .and_then(|val| NaiveDate::parse_from_str(&val, "%Y-%m-%d").ok())
        .unwrap_or(today);

    (start.format("%Y-%m-%d").to_string(), until.format("%Y-%m-%d").to_string())
}

async fn fetch_configs(state: &AppState, shop_id: Option<String>) -> Result<Vec<MarketplaceConfig>, String> {
    let mut url = format!("{}/api/marketplace/config?marketplace=TIKTOK", state.api_base);
    if let Some(shop) = shop_id {
        if !shop.is_empty() {
            url.push_str("&shop_id=");
            url.push_str(&shop);
        }
    }

    let mut request = state.http.get(&url);
    if let Some(secret) = &state.worker_secret {
        request = request.header("x-worker-secret", secret);
    }
    let response = request
        .send()
        .await
        .map_err(|err| format!("Failed to fetch config: {}", err))?;

    let payload: ConfigResponse = response
        .json()
        .await
        .map_err(|err| format!("Invalid config response: {}", err))?;

    if !payload.status {
        return Err(payload.msg.unwrap_or_else(|| "Config request failed".to_string()));
    }

    Ok(payload.data.unwrap_or_default())
}

async fn sync_orders_for_shop(
    state: &AppState,
    config: &MarketplaceConfig,
    start_date: &str,
    until_date: &str,
    debug: bool,
) -> Result<SyncSummary, String> {
    let start_time = to_unix(start_date);
    let until_time = to_unix(&add_days(until_date, 1));

    let mut page_token = String::new();
    let mut pages = 0;
    let mut orders_total = 0;
    let mut ingested = 0;

    for _ in 0..PAGE_LIMIT {
        let mut query = BTreeMap::new();
        query.insert("app_key".to_string(), config.app_key.clone());
        query.insert("shop_cipher".to_string(), config.shop_cipher.clone());
        query.insert("sort_field".to_string(), "create_time".to_string());
        query.insert("sort_order".to_string(), "ASC".to_string());
        query.insert("page_size".to_string(), "100".to_string());
        if !page_token.is_empty() {
            query.insert("page_token".to_string(), page_token.clone());
        }

        let body = serde_json::json!({
            "create_time_ge": start_time,
            "create_time_lt": until_time,
            "update_time_ge": start_time,
            "update_time_lt": until_time,
        });

        let response = tiktok_request(
            state,
            "POST",
            "https://open-api.tiktokglobalshop.com/order/202309/orders/search",
            &query,
            Some(body),
            &config.access_token,
        )
        .await?;

        let orders = response
            .get("data")
            .and_then(|data| data.get("orders"))
            .and_then(|val| val.as_array())
            .or_else(|| {
                response
                    .get("data")
                    .and_then(|data| data.get("order_list"))
                    .and_then(|val| val.as_array())
            })
            .cloned()
            .unwrap_or_default();

        if orders.is_empty() {
            break;
        }

        pages += 1;
        orders_total += orders.len();

        ingest_orders(state, config, start_date, until_date, debug, orders).await?;
        ingested += orders.len();

        let next_page = response
            .get("data")
            .and_then(|data| data.get("next_page_token"))
            .and_then(|val| val.as_str())
            .unwrap_or("");

        if next_page.is_empty() {
            break;
        }
        page_token = next_page.to_string();
    }

    Ok(SyncSummary {
        shop_id: config.shop_id.clone(),
        shop_name: config.shop_name.clone(),
        pages,
        orders: orders_total,
        ingested,
    })
}

async fn ingest_orders(
    state: &AppState,
    config: &MarketplaceConfig,
    start_date: &str,
    until_date: &str,
    debug: bool,
    orders: Vec<Value>,
) -> Result<(), String> {
    let url = format!("{}/api/marketplace/order/ingest", state.api_base);
    let payload = serde_json::json!({
        "marketplace": "TIKTOK",
        "shop_id": config.shop_id,
        "shop_name": config.shop_name,
        "start_date": start_date,
        "until_date": until_date,
        "debug": debug,
        "orders": orders,
    });

    let mut request = state.http.post(&url).json(&payload);
    if let Some(secret) = &state.worker_secret {
        request = request.header("x-worker-secret", secret);
    }
    let response = request
        .send()
        .await
        .map_err(|err| format!("Order ingest failed: {}", err))?;

    let result: ApiResponse<Value> = response
        .json()
        .await
        .map_err(|err| format!("Order ingest invalid response: {}", err))?;

    if !result.status {
        return Err(result.msg);
    }

    Ok(())
}

async fn tiktok_request(
    state: &AppState,
    method: &str,
    endpoint: &str,
    query: &BTreeMap<String, String>,
    body: Option<Value>,
    access_token: &str,
) -> Result<Value, String> {
    let timestamp = Utc::now().timestamp().to_string();
    let mut query_params = query.clone();
    query_params.insert("timestamp".to_string(), timestamp.clone());

    let body_string = body
        .as_ref()
        .map(|val| serde_json::to_string(val).unwrap_or_default())
        .unwrap_or_default();

    let sign = tiktok_signature(endpoint, &state.app_secret, &timestamp, &query_params, &body_string);
    query_params.insert("sign".to_string(), sign);

    let url = format!("{}?{}", endpoint, encode_query(&query_params));

    let builder = match method {
        "POST" => state.http.post(&url).body(body_string),
        _ => state.http.get(&url),
    };

    let response = builder
        .header("content-type", "application/json")
        .header("x-tts-access-token", access_token)
        .send()
        .await
        .map_err(|err| format!("TikTok request failed: {}", err))?;

    let status = response.status();
    let payload: Value = response
        .json()
        .await
        .map_err(|err| format!("TikTok response invalid: {}", err))?;

    if !status.is_success() {
        return Err(format!("TikTok HTTP {}", status));
    }

    if let Some(code_val) = payload.get("code") {
        let code = code_val.as_i64().unwrap_or(0);
        if code != 0 {
            let msg = payload
                .get("message")
                .and_then(|val| val.as_str())
                .unwrap_or("TikTok request error");
            return Err(msg.to_string());
        }
    }

    Ok(payload)
}

fn tiktok_signature(
    url: &str,
    secret: &str,
    timestamp: &str,
    query: &BTreeMap<String, String>,
    body: &str,
) -> String {
    let path = match url::Url::parse(url) {
        Ok(parsed) => parsed.path().to_string(),
        Err(_) => url.to_string(),
    };

    let mut input = path;
    for (key, value) in query.iter() {
        if key == "sign" || key == "access_token" {
            continue;
        }
        if key == "timestamp" {
            input.push_str(key);
            input.push_str(timestamp);
        } else {
            input.push_str(key);
            input.push_str(value);
        }
    }

    if !body.is_empty() {
        input.push_str(body);
    }

    let wrapped = format!("{}{}{}", secret, input, secret);
    let mut mac = Hmac::<Sha256>::new_from_slice(secret.as_bytes()).expect("hmac key");
    mac.update(wrapped.as_bytes());
    hex::encode(mac.finalize().into_bytes())
}

fn encode_query(query: &BTreeMap<String, String>) -> String {
    let mut params = Vec::new();
    for (key, value) in query.iter() {
        params.push(format!("{}={}", urlencoding::encode(key), urlencoding::encode(value)));
    }
    params.join("&")
}

fn to_unix(date: &str) -> i64 {
    NaiveDate::parse_from_str(date, "%Y-%m-%d")
        .map(|d| d.and_hms_opt(0, 0, 0).unwrap())
        .map(|dt| dt.timestamp())
        .unwrap_or_else(|_| Utc::now().timestamp())
}

fn add_days(date: &str, days: i64) -> String {
    let parsed = NaiveDate::parse_from_str(date, "%Y-%m-%d").unwrap_or_else(|_| Utc::now().date_naive());
    let next = parsed + chrono::Duration::days(days);
    next.format("%Y-%m-%d").to_string()
}
