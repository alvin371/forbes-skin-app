//! Long-lived endorse-refresh consumer.
//!
//! Drains `endorse_refresh_queue` continuously instead of the per-minute PHP cron:
//!   1. POST /api/endorse-refresh/claim  -> N fetch-ready items (PHP owns claim + rate cap)
//!   2. fetch each item's TikTok stats over an ISOLATED HTTP/1.1 connection (bounded
//!      concurrency via a semaphore), parsing __UNIVERSAL_DATA_FOR_REHYDRATION__
//!   3. POST /api/endorse-refresh/result -> PHP applies outcomes + retry policy
//!
//! PHP keeps the DB, retry policy, rate cap and rollup. This worker only does concurrency
//! + isolated HTTP. No HTTP/2, no multiplexing: one request = one connection = one timeout,
//! so a single stalled request can never take down the batch (the old curl_multi failure).

use axum::{extract::State, routing::get, Json, Router};
use chrono::{FixedOffset, TimeZone};
use regex::Regex;
use reqwest::header::{COOKIE, USER_AGENT};
use reqwest::Client;
use serde::{Deserialize, Serialize};
use serde_json::Value;
use std::{
    net::SocketAddr,
    sync::{
        atomic::{AtomicI64, Ordering},
        Arc,
    },
    time::Duration,
};
use tokio::sync::Semaphore;
use tracing::{error, info, warn};

// Asia/Jakarta (+07:00) — match the PHP server tz so createTime -> date lands on the same day.
const JAKARTA_OFFSET_SECS: i32 = 7 * 3600;

#[derive(Clone)]
struct Config {
    api_base: String,
    worker_secret: Option<String>,
    concurrency: usize,
    claim_limit: u32,
    idle_sleep: Duration,
    default_timeout: u64,
    scrape_ua: String,
    scrape_cookie: String,
}

fn env_string(key: &str, default: &str) -> String {
    std::env::var(key).ok().filter(|v| !v.is_empty()).unwrap_or_else(|| default.to_string())
}
fn env_parse<T: std::str::FromStr>(key: &str, default: T) -> T {
    std::env::var(key).ok().and_then(|v| v.parse::<T>().ok()).unwrap_or(default)
}

fn load_config() -> Config {
    let concurrency = env_parse::<usize>("ENDORSE_REFRESH_CONCURRENCY", 10).clamp(1, 50);
    Config {
        api_base: env_string("APP_API_BASE", "http://app").trim_end_matches('/').to_string(),
        worker_secret: std::env::var("WORKER_SHARED_SECRET").ok().filter(|v| !v.is_empty()),
        concurrency,
        claim_limit: env_parse::<u32>("ENDORSE_REFRESH_BATCH_SIZE", 40).clamp(1, 500),
        idle_sleep: Duration::from_millis(env_parse::<u64>("ENDORSE_REFRESH_IDLE_SLEEP_MS", 2000).clamp(250, 60_000)),
        default_timeout: env_parse::<u64>("ENDORSE_REFRESH_HTTP_TIMEOUT", 30).clamp(1, 120),
        // Mirror Template::scrapeTiktokDetailFromPage exactly — same UA + cookie that works in prod.
        scrape_ua: env_string(
            "ENDORSE_REFRESH_SCRAPE_UA",
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:100.0) Gecko/20100101 Firefox/100.0",
        ),
        scrape_cookie: env_string(
            "ENDORSE_REFRESH_SCRAPE_COOKIE",
            "tt_chain_token=+O8Mw9RH4nKrX/ACdOBhXw==; tt_csrf_token=27TtpaB8-Wftkj0rFR_w6LdtcAp4tdDCFfBY; ttwid=1%7CdJI7LAdiTNKwSISqHad9wDTJ6G_70WU_PGro2isx-ac%7C1705385087%7C518efef116162148489d7f25fa3c7b06633a23c824590f04ea0226d5c2b6f092",
        ),
    }
}

#[derive(Deserialize, Clone)]
struct ClaimItem {
    queue_id: i64,
    #[serde(default)]
    platform: String,
    #[serde(default)]
    url: String,
    #[serde(default)]
    timeout_sec: u64,
}

#[derive(Deserialize)]
struct ClaimResponse {
    #[serde(default)]
    items: Vec<ClaimItem>,
    #[serde(default)]
    skipped: Option<Value>,
}

#[derive(Serialize)]
struct ResultItem {
    queue_id: i64,
    response: SocialResponse,
}

/// Mirrors the Template::get_social_media response shape so PHP's apply()/classify_response
/// consume it unchanged. On failure we always set an explicit, RETRYABLE error_class
/// (transient / infra_stall / infra_connect) — never the terminal permanent/empty classes,
/// which stay PHP-only. classify_response honours error_class directly when set.
#[derive(Serialize)]
struct SocialResponse {
    status: bool,
    msg: String,
    #[serde(skip_serializing_if = "str::is_empty")]
    error_class: String,
    data: SocialData,
}

#[derive(Serialize, Default)]
struct SocialData {
    like: i64,
    share: i64,
    comment: i64,
    collect: i64,
    view: i64,
    created_at: String,
    content_id: String,
    media_type: String,
    video_link: String,
    cover: String,
    images: Vec<String>,
}

impl SocialResponse {
    fn failure(error_class: &str, msg: impl Into<String>) -> Self {
        SocialResponse { status: false, msg: msg.into(), error_class: error_class.to_string(), data: SocialData::default() }
    }
}

#[derive(Clone)]
struct AppState {
    heartbeat: Arc<AtomicI64>,
}

#[tokio::main]
async fn main() {
    tracing_subscriber::fmt()
        .with_env_filter(tracing_subscriber::EnvFilter::from_default_env())
        .init();

    let cfg = load_config();

    // http1_only(true): forbid HTTP/2 outright. Keep-alive connection reuse is fine (it is
    // NOT multiplexing) — each in-flight request still owns a distinct connection.
    let client = Client::builder()
        .http1_only()
        .pool_max_idle_per_host(cfg.concurrency)
        .connect_timeout(Duration::from_secs(5))
        .build()
        .expect("failed to build http client");

    let heartbeat = Arc::new(AtomicI64::new(chrono::Utc::now().timestamp()));

    // Health server for Swarm healthcheck + stale-worker alerting.
    let health_state = AppState { heartbeat: heartbeat.clone() };
    let port: u16 = env_parse::<u16>("WORKER_PORT", 8082);
    tokio::spawn(async move {
        let app = Router::new().route("/health", get(health)).with_state(health_state);
        let addr = SocketAddr::from(([0, 0, 0, 0], port));
        info!("health server on {}", addr);
        match tokio::net::TcpListener::bind(addr).await {
            Ok(listener) => {
                if let Err(e) = axum::serve(listener, app).await {
                    error!("health server failed: {}", e);
                }
            }
            Err(e) => error!("health bind failed: {}", e),
        }
    });

    info!(
        "endorse-refresh-worker draining {} (concurrency={}, claim_limit={})",
        cfg.api_base, cfg.concurrency, cfg.claim_limit
    );

    run_loop(client, cfg, heartbeat).await;
}

async fn health(State(state): State<AppState>) -> Json<Value> {
    let last = state.heartbeat.load(Ordering::Relaxed);
    let age = chrono::Utc::now().timestamp() - last;
    Json(serde_json::json!({ "status": "ok", "last_claim_unix": last, "age_secs": age }))
}

async fn run_loop(client: Client, cfg: Config, heartbeat: Arc<AtomicI64>) {
    let sem = Arc::new(Semaphore::new(cfg.concurrency));

    loop {
        let items = match claim(&client, &cfg).await {
            Ok(items) => items,
            Err(e) => {
                warn!("claim failed: {} — backing off", e);
                tokio::time::sleep(cfg.idle_sleep).await;
                continue;
            }
        };

        heartbeat.store(chrono::Utc::now().timestamp(), Ordering::Relaxed);

        if items.is_empty() {
            tokio::time::sleep(cfg.idle_sleep).await;
            continue;
        }

        // Fetch the whole claimed batch concurrently, bounded by the semaphore. One item's
        // stall only holds its own permit; the rest keep flowing.
        let mut handles = Vec::with_capacity(items.len());
        for item in items {
            let (client, cfg, sem) = (client.clone(), cfg.clone(), sem.clone());
            handles.push(tokio::spawn(async move {
                let _permit = sem.acquire_owned().await.expect("semaphore closed");
                let response = fetch_one(&client, &cfg, &item).await;
                ResultItem { queue_id: item.queue_id, response }
            }));
        }

        let mut results = Vec::with_capacity(handles.len());
        for h in handles {
            match h.await {
                Ok(r) => results.push(r),
                Err(e) => error!("fetch task panicked: {}", e),
            }
        }

        if let Err(e) = post_results(&client, &cfg, &results).await {
            // PHP couldn't apply — the rows stay 'processing' and resetStuck will recover
            // them, so nothing is lost. Back off and continue.
            error!("post_results failed: {} — {} outcomes not applied this round", e, results.len());
            tokio::time::sleep(cfg.idle_sleep).await;
        }
    }
}

fn auth<'a>(req: reqwest::RequestBuilder, cfg: &Config) -> reqwest::RequestBuilder {
    match &cfg.worker_secret {
        Some(secret) => req.header("x-worker-secret", secret),
        None => req,
    }
}

async fn claim(client: &Client, cfg: &Config) -> Result<Vec<ClaimItem>, String> {
    let url = format!("{}/api/endorse-refresh/claim", cfg.api_base);
    let req = auth(client.post(&url).json(&serde_json::json!({ "limit": cfg.claim_limit })), cfg)
        .timeout(Duration::from_secs(30));
    let resp = req.send().await.map_err(|e| format!("claim request: {}", e))?;
    if !resp.status().is_success() {
        return Err(format!("claim HTTP {}", resp.status()));
    }
    let parsed: ClaimResponse = resp.json().await.map_err(|e| format!("claim decode: {}", e))?;
    if let Some(skip) = parsed.skipped {
        info!("claim skipped by cap: {}", skip);
        return Ok(Vec::new());
    }
    Ok(parsed.items)
}

async fn post_results(client: &Client, cfg: &Config, results: &[ResultItem]) -> Result<(), String> {
    if results.is_empty() {
        return Ok(());
    }
    let url = format!("{}/api/endorse-refresh/result", cfg.api_base);
    let req = auth(client.post(&url).json(&serde_json::json!({ "results": results })), cfg)
        .timeout(Duration::from_secs(30));
    let resp = req.send().await.map_err(|e| format!("result request: {}", e))?;
    if !resp.status().is_success() {
        return Err(format!("result HTTP {}", resp.status()));
    }
    Ok(())
}

/// Fetch + parse ONE TikTok URL, scrape-primary (the proven robust prod path).
// ponytail: scrape-only; RapidAPI fallback deferred. Scrape is the ~70x-faster path that
// actually works in prod; on scrape failure we return `transient` so the row retries, and
// the PHP cron fallback (which has its own RapidAPI path) is one ENDORSE_REFRESH_DRIVER flip
// away. Add a RapidAPI fallback here if scrape ever degrades systemically.
async fn fetch_one(client: &Client, cfg: &Config, item: &ClaimItem) -> SocialResponse {
    if item.platform != "Tiktok" {
        // Instagram/other individual-post scraping is unsupported (matches get_social_media);
        // keep it retryable rather than terminal — PHP already skips these on enqueue.
        return SocialResponse::failure("transient", "Platform belum tersedia di worker");
    }
    if item.url.is_empty() {
        return SocialResponse::failure("transient", "URL kosong");
    }

    let timeout = if item.timeout_sec > 0 { item.timeout_sec } else { cfg.default_timeout };
    let req = client
        .get(&item.url)
        .header(USER_AGENT, &cfg.scrape_ua)
        .header(COOKIE, &cfg.scrape_cookie)
        .timeout(Duration::from_secs(timeout));

    let resp = match req.send().await {
        Ok(r) => r,
        Err(e) => {
            // The infamous stall = timeout with no bytes. Tag it infra_stall so PHP grants
            // the rescue lane (wider timeout) on the next attempt.
            if e.is_timeout() {
                return SocialResponse::failure("infra_stall", format!("timeout after {}s: {}", timeout, e));
            }
            if e.is_connect() {
                return SocialResponse::failure("infra_connect", format!("connect: {}", e));
            }
            return SocialResponse::failure("transient", format!("request: {}", e));
        }
    };

    if !resp.status().is_success() {
        return SocialResponse::failure("transient", format!("HTTP {}", resp.status()));
    }

    let html = match resp.text().await {
        Ok(t) => t,
        Err(e) => return SocialResponse::failure("transient", format!("read body: {}", e)),
    };

    match parse_item_struct(&html) {
        Some(item_struct) => map_item(&item_struct, &item.url),
        None => SocialResponse::failure("transient", "Stats belum tersedia dari scrape"),
    }
}

/// Mirror Template::extractTiktokItemStructFromHtml — pull the rehydration JSON blob and
/// walk to __DEFAULT_SCOPE__ -> "webapp.video-detail" -> itemInfo -> itemStruct.
fn parse_item_struct(html: &str) -> Option<Value> {
    // Compiled once. `(?s)` = dotall so the JSON (which spans newlines) is captured whole.
    static PATTERN: &str =
        r#"(?s)<script id="__UNIVERSAL_DATA_FOR_REHYDRATION__" type="application/json">(.*?)</script>"#;
    let re = Regex::new(PATTERN).ok()?;
    let caps = re.captures(html)?;
    let blob = caps.get(1)?.as_str();
    let json: Value = serde_json::from_str(blob).ok()?;
    let item = json
        .get("__DEFAULT_SCOPE__")?
        .get("webapp.video-detail")?
        .get("itemInfo")?
        .get("itemStruct")?;
    Some(item.clone())
}

fn i64_at(v: &Value, key: &str) -> i64 {
    match v.get(key) {
        Some(Value::Number(n)) => n.as_i64().unwrap_or(0),
        Some(Value::String(s)) => s.parse::<i64>().unwrap_or(0),
        _ => 0,
    }
}
fn str_at(v: &Value, key: &str) -> String {
    match v.get(key) {
        Some(Value::String(s)) => s.clone(),
        Some(Value::Number(n)) => n.to_string(),
        _ => String::new(),
    }
}

/// Mirror isValidTiktokScrapeItem + mapDirectTiktokItemToResponse.
fn map_item(item: &Value, url: &str) -> SocialResponse {
    let stats = item.get("stats").cloned().unwrap_or(Value::Null);
    let like = i64_at(&stats, "diggCount");
    let share = i64_at(&stats, "shareCount");
    let comment = i64_at(&stats, "commentCount");
    let collect = i64_at(&stats, "collectCount");
    let view = i64_at(&stats, "playCount");

    // isValidTiktokScrapeItem: at least one metric > 0, else treat as scrape-miss (retry).
    if like <= 0 && share <= 0 && comment <= 0 && collect <= 0 && view <= 0 {
        return SocialResponse::failure("transient", "Stats data tidak valid dari scrape");
    }

    let content_id = {
        let id = str_at(item, "id");
        if id.is_empty() { extract_content_id(url) } else { id }
    };

    let created_at = {
        let ct = i64_at(item, "createTime");
        if ct > 0 {
            FixedOffset::east_opt(JAKARTA_OFFSET_SECS)
                .and_then(|off| off.timestamp_opt(ct, 0).single())
                .map(|dt| dt.format("%Y-%m-%d").to_string())
                .unwrap_or_default()
        } else {
            String::new()
        }
    };

    let image_post = item.get("imagePost");
    let is_photo = image_post
        .map(|ip| ip.get("images").map(|v| !v.is_null()).unwrap_or(false) || ip.get("cover").map(|v| !v.is_null()).unwrap_or(false))
        .unwrap_or(false);

    let cover = extract_cover(item);

    let mut images: Vec<String> = Vec::new();
    let mut video_link = String::new();
    if is_photo {
        if let Some(arr) = image_post.and_then(|ip| ip.get("images")).and_then(|v| v.as_array()) {
            for img in arr {
                let u = img
                    .get("imageURL")
                    .and_then(|iu| iu.get("urlList"))
                    .and_then(|l| l.as_array())
                    .and_then(|l| l.first())
                    .and_then(|s| s.as_str())
                    .unwrap_or("");
                if !u.is_empty() {
                    images.push(u.to_string());
                }
            }
        }
        if !images.is_empty() {
            video_link = serde_json::to_string(&images).unwrap_or_default();
        } else if !cover.is_empty() {
            video_link = serde_json::to_string(&vec![cover.clone()]).unwrap_or_default();
        }
    }

    SocialResponse {
        status: true,
        msg: String::new(),
        error_class: String::new(),
        data: SocialData {
            like,
            share,
            comment,
            collect,
            view,
            created_at,
            content_id,
            media_type: if is_photo { "photo".into() } else { "video".into() },
            video_link,
            cover,
            images,
        },
    }
}

/// Mirror extract_tiktok_cover_from_item.
fn extract_cover(item: &Value) -> String {
    if let Some(c) = item.get("video").and_then(|v| v.get("cover")).and_then(|v| v.as_str()) {
        if !c.is_empty() {
            return c.to_string();
        }
    }
    if let Some(c) = item
        .get("imagePost")
        .and_then(|ip| ip.get("cover"))
        .and_then(|c| c.get("imageURL"))
        .and_then(|iu| iu.get("urlList"))
        .and_then(|l| l.as_array())
        .and_then(|l| l.first())
        .and_then(|s| s.as_str())
    {
        if !c.is_empty() {
            return c.to_string();
        }
    }
    item.get("video").and_then(|v| v.get("originCover")).and_then(|v| v.as_str()).unwrap_or("").to_string()
}

/// Mirror extract_tiktok_content_id (video/photo/long-digit fallbacks).
fn extract_content_id(url: &str) -> String {
    for pat in [r"/video/(\d+)", r"/photo/(\d+)", r"(\d{10,25})"] {
        if let Ok(re) = Regex::new(pat) {
            if let Some(c) = re.captures(url).and_then(|c| c.get(1)) {
                return c.as_str().to_string();
            }
        }
    }
    String::new()
}

#[cfg(test)]
mod tests {
    use super::*;

    // Minimal rehydration page mirroring TikTok's structure; the fixture asserts the
    // regex + JSON-path extraction and the stat mapping stay wired to real field names.
    const FIXTURE: &str = r#"<html><head></head><body>
<script id="__UNIVERSAL_DATA_FOR_REHYDRATION__" type="application/json">{"__DEFAULT_SCOPE__":{"webapp.video-detail":{"itemInfo":{"itemStruct":{"id":"7660415942815403285","createTime":1705400000,"stats":{"diggCount":27,"shareCount":3,"commentCount":21,"collectCount":5,"playCount":517},"video":{"cover":"https://example.com/cover.jpg"}}}}}}</script>
</body></html>"#;

    #[test]
    fn extracts_and_maps_stats() {
        let item = parse_item_struct(FIXTURE).expect("itemStruct present");
        let resp = map_item(&item, "https://www.tiktok.com/@acneno.ofc/video/7660415942815403285");
        assert!(resp.status);
        assert_eq!(resp.data.like, 27);
        assert_eq!(resp.data.comment, 21);
        assert_eq!(resp.data.view, 517);
        assert_eq!(resp.data.share, 3);
        assert_eq!(resp.data.collect, 5);
        assert_eq!(resp.data.content_id, "7660415942815403285");
        assert_eq!(resp.data.media_type, "video");
        assert_eq!(resp.data.cover, "https://example.com/cover.jpg");
    }

    #[test]
    fn missing_blob_is_none() {
        assert!(parse_item_struct("<html>no blob here</html>").is_none());
    }

    #[test]
    fn zero_stats_is_retryable_failure() {
        let item: Value = serde_json::json!({"id":"1","stats":{"diggCount":0,"playCount":0,"shareCount":0,"commentCount":0,"collectCount":0}});
        let resp = map_item(&item, "https://www.tiktok.com/@x/video/1");
        assert!(!resp.status);
        assert_eq!(resp.error_class, "transient");
    }

    #[test]
    fn content_id_from_url_fallback() {
        assert_eq!(extract_content_id("https://www.tiktok.com/@x/video/7660415942815403285"), "7660415942815403285");
        assert_eq!(extract_content_id("https://www.tiktok.com/@x/photo/123456789012"), "123456789012");
    }
}
