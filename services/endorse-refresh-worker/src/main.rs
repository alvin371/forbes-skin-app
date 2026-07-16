//! Long-lived endorse-refresh consumer.
//!
//! Drains `endorse_refresh_queue` continuously instead of the per-minute PHP cron:
//!   1. POST /api/endorse-refresh/claim  -> N fetch-ready items (PHP owns claim + rate cap)
//!   2. fetch each item's TikTok stats over an ISOLATED HTTP/1.1 connection (bounded
//!      concurrency via a semaphore), parsing __UNIVERSAL_DATA_FOR_REHYDRATION__ and
//!      falling back to PHP's authenticated single-item RapidAPI path on scrape misses
//!   3. POST /api/endorse-refresh/result -> PHP applies outcomes + retry policy
//!
//! PHP keeps the DB, retry policy, rate cap and rollup. This worker only does concurrency
//! + isolated HTTP. No HTTP/2, no multiplexing: one request = one connection = one timeout,
//!   so a single stalled request can never take down the batch (the old curl_multi failure).

use axum::{extract::State, routing::get, Json, Router};
use chrono::{FixedOffset, TimeZone};
use regex::Regex;
use reqwest::header::{COOKIE, USER_AGENT};
use reqwest::Client;
use serde::{Deserialize, Serialize};
use serde_json::Value;
use std::{
    fs::File,
    io::Read,
    net::SocketAddr,
    sync::{
        atomic::{AtomicI64, AtomicU64, Ordering},
        Arc,
    },
    time::{Duration, Instant},
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
    worker_id: String,
    task_identity: String,
}

fn env_string(key: &str, default: &str) -> String {
    std::env::var(key)
        .ok()
        .filter(|v| !v.is_empty())
        .unwrap_or_else(|| default.to_string())
}
fn env_parse<T: std::str::FromStr>(key: &str, default: T) -> T {
    std::env::var(key)
        .ok()
        .and_then(|v| v.parse::<T>().ok())
        .unwrap_or(default)
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
        worker_id: generate_worker_uuid(),
        task_identity: env_string("ENDORSE_REFRESH_TASK_IDENTITY", "endorse-refresh-worker"),
    }
}

fn generate_worker_uuid() -> String {
    let mut bytes = [0u8; 16];
    let random_ok = File::open("/dev/urandom")
        .and_then(|mut file| file.read_exact(&mut bytes))
        .is_ok();
    if !random_ok {
        static FALLBACK_COUNTER: AtomicU64 = AtomicU64::new(0);
        let counter = FALLBACK_COUNTER.fetch_add(1, Ordering::Relaxed) as u128;
        let nanos = std::time::SystemTime::now()
            .duration_since(std::time::UNIX_EPOCH)
            .map(|d| d.as_nanos())
            .unwrap_or(0);
        let pid = std::process::id() as u128;
        let seed = nanos ^ (pid << 64) ^ counter;
        bytes.copy_from_slice(&seed.to_be_bytes());
    }
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    let hex = bytes.map(|b| format!("{:02x}", b));

    format!(
        "{}{}{}{}-{}{}-{}{}-{}{}-{}{}{}{}{}{}",
        hex[0], hex[1], hex[2], hex[3],
        hex[4], hex[5],
        hex[6], hex[7],
        hex[8], hex[9],
        hex[10], hex[11], hex[12], hex[13], hex[14], hex[15]
    )
}

#[derive(Deserialize, Clone)]
struct ClaimItem {
    queue_id: i64,
    attempt_no: i64,
    active_attempt_id: i64,
    worker_id: String,
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
    contract_version: i64,
    #[serde(default)]
    worker_id: String,
    #[serde(default)]
    items: Vec<ClaimItem>,
}

#[derive(Serialize)]
struct ResultItem {
    queue_id: i64,
    attempt_no: i64,
    active_attempt_id: i64,
    worker_id: String,
    response: SocialResponse,
}

/// Mirrors the Template::get_social_media response shape so PHP's apply()/classify_response
/// consume it unchanged. Direct transport failures use retryable machine classes; fallback
/// responses preserve PHP's class (or lack of one) so PHP remains the retry-policy owner.
#[derive(Serialize, Clone)]
struct SocialResponse {
    status: bool,
    msg: String,
    #[serde(skip_serializing_if = "str::is_empty")]
    error_class: String,
    #[serde(skip_serializing_if = "str::is_empty")]
    reason_code: String,
    stats_found: bool,
    stats_complete: bool,
    stats_fields: Vec<String>,
    observed_at: String,
    data: SocialData,
}

#[derive(Serialize, Deserialize, Default, Clone)]
#[serde(default)]
struct SocialData {
    like: Option<i64>,
    share: Option<i64>,
    comment: Option<i64>,
    collect: Option<i64>,
    view: Option<i64>,
    created_at: String,
    content_id: String,
    media_type: String,
    video_link: String,
    cover: String,
    images: Vec<String>,
}

#[derive(Deserialize)]
struct FallbackResponse {
    #[serde(default)]
    status: bool,
    #[serde(default)]
    msg: String,
    #[serde(default)]
    error_class: String,
    #[serde(default)]
    reason: String,
    #[serde(default)]
    stats_found: bool,
    #[serde(default)]
    stats_complete: bool,
    #[serde(default)]
    stats_fields: Vec<String>,
    #[serde(default)]
    observed_at: String,
    #[serde(default)]
    data: Value,
}

impl FallbackResponse {
    fn into_social_response(self) -> SocialResponse {
        let data = if self.data.is_object() {
            serde_json::from_value(self.data).unwrap_or_default()
        } else {
            SocialData::default()
        };
        SocialResponse {
            status: self.status,
            msg: self.msg,
            error_class: self.error_class,
            reason_code: self.reason,
            stats_found: self.stats_found,
            stats_complete: self.stats_complete,
            stats_fields: self.stats_fields,
            observed_at: self.observed_at,
            data,
        }
    }
}

#[derive(Clone, Copy, Debug, Eq, PartialEq)]
enum FetchSource {
    Scrape,
    Fallback,
    Failed,
}

struct FetchOutcome {
    response: SocialResponse,
    source: FetchSource,
    normalized_url: bool,
    release: Option<ReleaseItem>,
}

#[derive(Serialize, Clone)]
struct ReleaseItem {
    queue_id: i64,
    attempt_no: i64,
    active_attempt_id: i64,
    reason_code: String,
    msg: String,
}

impl SocialResponse {
    fn failure(error_class: &str, msg: impl Into<String>) -> Self {
        SocialResponse {
            status: false,
            msg: msg.into(),
            error_class: error_class.to_string(),
            reason_code: String::new(),
            stats_found: false,
            stats_complete: false,
            stats_fields: vec![],
            observed_at: chrono::Utc::now().format("%Y-%m-%d %H:%M:%S%.6f").to_string(),
            data: SocialData::default(),
        }
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
    let health_state = AppState {
        heartbeat: heartbeat.clone(),
    };
    let port: u16 = env_parse::<u16>("WORKER_PORT", 8082);
    tokio::spawn(async move {
        let app = Router::new()
            .route("/health", get(health))
            .with_state(health_state);
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
        let claimed = match claim(&client, &cfg).await {
            Ok(claimed) => claimed,
            Err(e) => {
                warn!("claim failed: {} — backing off", e);
                tokio::time::sleep(cfg.idle_sleep).await;
                continue;
            }
        };

        heartbeat.store(chrono::Utc::now().timestamp(), Ordering::Relaxed);

        let items = claimed.items;
        if items.is_empty() {
            tokio::time::sleep(cfg.idle_sleep).await;
            continue;
        }

        let batch_size = items.len();
        let batch_started = Instant::now();

        // Fetch the whole claimed batch concurrently, bounded by the semaphore. One item's
        // stall only holds its own permit; the rest keep flowing.
        let mut handles = Vec::with_capacity(items.len());
        for item in items {
            let (client, cfg, sem) = (client.clone(), cfg.clone(), sem.clone());
            handles.push(tokio::spawn(async move {
                let _permit = sem.acquire_owned().await.expect("semaphore closed");
                let outcome = fetch_one(&client, &cfg, &item).await;
                (item, outcome)
            }));
        }

        let mut results = Vec::with_capacity(handles.len());
        let mut scrape_ok = 0usize;
        let mut fallback_ok = 0usize;
        let mut fallback_failed = 0usize;
        let mut normalized_urls = 0usize;
        let mut releases: Vec<ReleaseItem> = Vec::new();
        for h in handles {
            match h.await {
                Ok((item, outcome)) => {
                    match outcome.source {
                        FetchSource::Scrape => scrape_ok += 1,
                        FetchSource::Fallback => fallback_ok += 1,
                        FetchSource::Failed => fallback_failed += 1,
                    }
                    if outcome.normalized_url {
                        normalized_urls += 1;
                    }
                    if let Some(release) = outcome.release {
                        releases.push(release);
                    } else {
                        results.push(ResultItem {
                            queue_id: item.queue_id,
                            attempt_no: item.attempt_no,
                            active_attempt_id: item.active_attempt_id,
                            worker_id: item.worker_id.clone(),
                            response: outcome.response,
                        });
                    }
                }
                Err(e) => error!("fetch task panicked: {}", e),
            }
        }

        info!(
            "batch fetched: claimed={}, scrape_ok={}, fallback_ok={}, fallback_failed={}, normalized_urls={}, elapsed_ms={}",
            batch_size,
            scrape_ok,
            fallback_ok,
            fallback_failed,
            normalized_urls,
            batch_started.elapsed().as_millis()
        );

        if !releases.is_empty() {
            if let Err(e) = release_results(&client, &cfg, &releases).await {
                error!("release_results failed: {} — {} claims not released", e, releases.len());
            }
        }

        if let Err(e) = post_results(&client, &cfg, &results).await {
            // PHP couldn't apply — the rows stay 'processing' and resetStuck will recover
            // them, so nothing is lost. Back off and continue.
            error!(
                "post_results failed: {} — {} outcomes not applied this round",
                e,
                results.len()
            );
            tokio::time::sleep(cfg.idle_sleep).await;
        }
    }
}

fn auth(req: reqwest::RequestBuilder, cfg: &Config) -> reqwest::RequestBuilder {
    match &cfg.worker_secret {
        Some(secret) => req.header("x-worker-secret", secret),
        None => req,
    }
}

async fn claim(client: &Client, cfg: &Config) -> Result<ClaimResponse, String> {
    let url = format!("{}/api/endorse-refresh/claim", cfg.api_base);
    let req = auth(
        client
            .post(&url)
            .json(&serde_json::json!({
                "contract_version": 2,
                "worker_id": cfg.worker_id,
                "task_identity": cfg.task_identity,
                "limit": cfg.claim_limit
            })),
        cfg,
    )
    .timeout(Duration::from_secs(30));
    let resp = req
        .send()
        .await
        .map_err(|e| format!("claim request: {}", e))?;
    if !resp.status().is_success() {
        let status = resp.status();
        let body = resp.text().await.unwrap_or_default();
        return Err(format!("claim HTTP {} {}", status, body));
    }
    let parsed: ClaimResponse = resp
        .json()
        .await
        .map_err(|e| format!("claim decode: {}", e))?;
    if parsed.contract_version != 2 {
        return Err(format!(
            "claim contract_version mismatch: expected 2 got {}",
            parsed.contract_version
        ));
    }
    if parsed.worker_id != cfg.worker_id {
        return Err(format!(
            "claim worker_id mismatch: expected {} got {}",
            cfg.worker_id, parsed.worker_id
        ));
    }
    Ok(parsed)
}

async fn post_results(client: &Client, cfg: &Config, results: &[ResultItem]) -> Result<(), String> {
    if results.is_empty() {
        return Ok(());
    }
    let url = format!("{}/api/endorse-refresh/result", cfg.api_base);
    let req = auth(
        client
            .post(&url)
            .json(&serde_json::json!({
                "contract_version": 2,
                "worker_id": cfg.worker_id,
                "results": results
            })),
        cfg,
    )
    .timeout(Duration::from_secs(30));
    let resp = req
        .send()
        .await
        .map_err(|e| format!("result request: {}", e))?;
    if !resp.status().is_success() {
        return Err(format!("result HTTP {}", resp.status()));
    }
    Ok(())
}

async fn release_results(client: &Client, cfg: &Config, items: &[ReleaseItem]) -> Result<(), String> {
    let url = format!("{}/api/endorse-refresh/release", cfg.api_base);
    let req = auth(
        client
            .post(&url)
            .json(&serde_json::json!({
                "contract_version": 2,
                "worker_id": cfg.worker_id,
                "items": items
            })),
        cfg,
    )
    .timeout(Duration::from_secs(30));
    let resp = req
        .send()
        .await
        .map_err(|e| format!("release request: {}", e))?;
    if !resp.status().is_success() {
        let status = resp.status();
        let body = resp.text().await.unwrap_or_default();
        return Err(format!("release HTTP {} {}", status, body));
    }
    Ok(())
}

/// Fetch one TikTok item. Direct page extraction stays the cheap primary path; every
/// scrape failure falls back to PHP's authenticated, authoritative single-item HTTP/1.1
/// RapidAPI path so a generic TikTok HTTP-200 app shell cannot consume queue attempts.
async fn fetch_one(client: &Client, cfg: &Config, item: &ClaimItem) -> FetchOutcome {
    if item.platform != "Tiktok" {
        return FetchOutcome {
            response: SocialResponse::failure("transient", "Platform belum tersedia di worker"),
            source: FetchSource::Failed,
            normalized_url: false,
            release: None,
        };
    }
    if item.url.is_empty() {
        let fallback = fetch_fallback(client, cfg, item).await;
        let source = if fallback.status {
            FetchSource::Fallback
        } else {
            FetchSource::Failed
        };
        return FetchOutcome {
            response: fallback,
            source,
            normalized_url: false,
            release: None,
        };
    }

    let (normalized_url, normalized) = match normalize_tiktok_url(&item.url) {
        Ok(value) => value,
        Err(e) => {
            let fallback = fetch_fallback(client, cfg, item).await;
            return FetchOutcome {
                source: if fallback.status {
                    FetchSource::Fallback
                } else {
                    FetchSource::Failed
                },
                response: if fallback.status {
                    fallback
                } else if fallback.msg.is_empty() {
                    SocialResponse::failure("transient", format!("{}; fallback unavailable", e))
                } else {
                    fallback
                },
                normalized_url: false,
                release: None,
            };
        }
    };

    let scrape = fetch_scrape(client, cfg, item, &normalized_url).await;
    if scrape.status {
        return FetchOutcome {
            response: scrape,
            source: FetchSource::Scrape,
            normalized_url: normalized,
            release: None,
        };
    }

    let fallback = fetch_fallback(client, cfg, item).await;
    if fallback.reason_code == "circuit_open" {
        return FetchOutcome {
            response: fallback.clone(),
            source: FetchSource::Failed,
            normalized_url: normalized,
            release: Some(ReleaseItem {
                queue_id: item.queue_id,
                attempt_no: item.attempt_no,
                active_attempt_id: item.active_attempt_id,
                reason_code: "provider_circuit_open".to_string(),
                msg: fallback.msg.clone(),
            }),
        };
    }
    let source = if fallback.status {
        FetchSource::Fallback
    } else {
        FetchSource::Failed
    };
    FetchOutcome {
        response: fallback,
        source,
        normalized_url: normalized,
        release: None,
    }
}

async fn fetch_scrape(
    client: &Client,
    cfg: &Config,
    item: &ClaimItem,
    url: &str,
) -> SocialResponse {
    let timeout = if item.timeout_sec > 0 {
        item.timeout_sec
    } else {
        cfg.default_timeout
    };
    let req = client
        .get(url)
        .header(USER_AGENT, &cfg.scrape_ua)
        .header(COOKIE, &cfg.scrape_cookie)
        .timeout(Duration::from_secs(timeout));

    let resp = match req.send().await {
        Ok(r) => r,
        Err(e) => {
            // The infamous stall = timeout with no bytes. Tag it infra_stall so PHP grants
            // the rescue lane (wider timeout) on the next attempt.
            if e.is_timeout() {
                return SocialResponse::failure(
                    "infra_stall",
                    format!("timeout after {}s: {}", timeout, e),
                );
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
        Some(item_struct) => map_item(&item_struct, url),
        None => SocialResponse::failure("transient", "Stats belum tersedia dari scrape"),
    }
}

async fn fetch_fallback(client: &Client, cfg: &Config, item: &ClaimItem) -> SocialResponse {
    let url = format!("{}/api/endorse-refresh/fetch-fallback", cfg.api_base);
    let timeout = if item.timeout_sec > 0 {
        item.timeout_sec
    } else {
        cfg.default_timeout
    };
    let req = auth(
        client
            .post(&url)
            .json(&serde_json::json!({
                "contract_version": 2,
                "queue_id": item.queue_id,
                "attempt_no": item.attempt_no,
                "active_attempt_id": item.active_attempt_id,
                "worker_id": item.worker_id,
            })),
        cfg,
    )
    // PHP may make two isolated 12-second RapidAPI attempts. Leave enough headroom
    // for both plus the internal request/response without exceeding stale recovery.
    .timeout(Duration::from_secs((timeout + 5).max(35)));

    let resp = match req.send().await {
        Ok(resp) => resp,
        Err(e) => {
            if e.is_timeout() {
                return SocialResponse::failure("infra_stall", format!("fallback timeout: {}", e));
            }
            if e.is_connect() {
                return SocialResponse::failure(
                    "infra_connect",
                    format!("fallback connect: {}", e),
                );
            }
            return SocialResponse::failure("transient", format!("fallback request: {}", e));
        }
    };

    if resp.status().as_u16() == 503 {
        let body = resp.json::<Value>().await.unwrap_or(Value::Null);
        return SocialResponse {
            status: false,
            msg: body
                .get("msg")
                .and_then(Value::as_str)
                .unwrap_or("Provider circuit open")
                .to_string(),
            error_class: "transient".to_string(),
            reason_code: body
                .get("reason")
                .and_then(Value::as_str)
                .unwrap_or("circuit_open")
                .to_string(),
            stats_found: false,
            stats_complete: false,
            stats_fields: vec![],
            observed_at: chrono::Utc::now().format("%Y-%m-%d %H:%M:%S%.6f").to_string(),
            data: SocialData::default(),
        };
    }
    if !resp.status().is_success() {
        return SocialResponse::failure("transient", format!("fallback HTTP {}", resp.status()));
    }

    match resp.json::<FallbackResponse>().await {
        Ok(response) => response.into_social_response(),
        Err(e) => SocialResponse::failure("transient", format!("fallback decode: {}", e)),
    }
}

fn normalize_tiktok_url(url: &str) -> Result<(String, bool), String> {
    let trimmed = url.trim();
    if trimmed.is_empty() {
        return Err("URL kosong".to_string());
    }
    if trimmed.starts_with("http://") || trimmed.starts_with("https://") {
        return Ok((trimmed.to_string(), false));
    }
    if trimmed.starts_with("//") {
        return Ok((format!("https:{}", trimmed), true));
    }

    let host = Regex::new(r"(?i)^(?:[a-z0-9-]+\.)?tiktok\.com/")
        .map_err(|e| format!("URL matcher: {}", e))?;
    if host.is_match(trimmed) {
        return Ok((format!("https://{}", trimmed), true));
    }

    Err(format!("URL TikTok tidak valid: {}", trimmed))
}

/// Mirror Template::extractTiktokItemStructFromHtml — pull the rehydration JSON blob and
/// walk to __DEFAULT_SCOPE__ -> "webapp.video-detail" -> itemInfo -> itemStruct.
fn parse_item_struct(html: &str) -> Option<Value> {
    // Compiled once. `(?s)` = dotall so the JSON (which spans newlines) is captured whole.
    static PATTERN: &str = r#"(?s)<script id="__UNIVERSAL_DATA_FOR_REHYDRATION__" type="application/json">(.*?)</script>"#;
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
    let like_present = stats.get("diggCount").is_some();
    let share_present = stats.get("shareCount").is_some();
    let comment_present = stats.get("commentCount").is_some();
    let collect_present = stats.get("collectCount").is_some();
    let view_present = stats.get("playCount").is_some();
    if !(like_present || share_present || comment_present || collect_present || view_present) {
        return SocialResponse::failure("transient", "Stats data tidak valid dari scrape");
    }
    let like = if like_present { Some(i64_at(&stats, "diggCount")) } else { None };
    let share = if share_present { Some(i64_at(&stats, "shareCount")) } else { None };
    let comment = if comment_present { Some(i64_at(&stats, "commentCount")) } else { None };
    let collect = if collect_present { Some(i64_at(&stats, "collectCount")) } else { None };
    let view = if view_present { Some(i64_at(&stats, "playCount")) } else { None };

    let content_id = {
        let id = str_at(item, "id");
        if id.is_empty() {
            extract_content_id(url)
        } else {
            id
        }
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
        .map(|ip| {
            ip.get("images").map(|v| !v.is_null()).unwrap_or(false)
                || ip.get("cover").map(|v| !v.is_null()).unwrap_or(false)
        })
        .unwrap_or(false);

    let cover = extract_cover(item);

    let mut images: Vec<String> = Vec::new();
    let mut video_link = String::new();
    if is_photo {
        if let Some(arr) = image_post
            .and_then(|ip| ip.get("images"))
            .and_then(|v| v.as_array())
        {
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
        reason_code: String::new(),
        stats_found: true,
        stats_complete: like_present && share_present && comment_present && collect_present && view_present,
        stats_fields: [
            ("like", like_present),
            ("share", share_present),
            ("comment", comment_present),
            ("collect", collect_present),
            ("view", view_present),
        ]
        .into_iter()
        .filter_map(|(name, present)| if present { Some(name.to_string()) } else { None })
        .collect(),
        observed_at: chrono::Utc::now().format("%Y-%m-%d %H:%M:%S%.6f").to_string(),
        data: SocialData {
            like,
            share,
            comment,
            collect,
            view,
            created_at,
            content_id,
            media_type: if is_photo {
                "photo".into()
            } else {
                "video".into()
            },
            video_link,
            cover,
            images,
        },
    }
}

/// Mirror extract_tiktok_cover_from_item.
fn extract_cover(item: &Value) -> String {
    if let Some(c) = item
        .get("video")
        .and_then(|v| v.get("cover"))
        .and_then(|v| v.as_str())
    {
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
    item.get("video")
        .and_then(|v| v.get("originCover"))
        .and_then(|v| v.as_str())
        .unwrap_or("")
        .to_string()
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
        let resp = map_item(
            &item,
            "https://www.tiktok.com/@acneno.ofc/video/7660415942815403285",
        );
        assert!(resp.status);
        assert_eq!(resp.data.like, Some(27));
        assert_eq!(resp.data.comment, Some(21));
        assert_eq!(resp.data.view, Some(517));
        assert_eq!(resp.data.share, Some(3));
        assert_eq!(resp.data.collect, Some(5));
        assert_eq!(resp.data.content_id, "7660415942815403285");
        assert_eq!(resp.data.media_type, "video");
        assert_eq!(resp.data.cover, "https://example.com/cover.jpg");
    }

    #[test]
    fn missing_blob_is_none() {
        assert!(parse_item_struct("<html>no blob here</html>").is_none());
    }

    #[test]
    fn zero_stats_is_valid_when_fields_are_present() {
        let item: Value = serde_json::json!({"id":"1","stats":{"diggCount":0,"playCount":0,"shareCount":0,"commentCount":0,"collectCount":0}});
        let resp = map_item(&item, "https://www.tiktok.com/@x/video/1");
        assert!(resp.status);
        assert_eq!(resp.data.view, Some(0));
    }

    #[test]
    fn content_id_from_url_fallback() {
        assert_eq!(
            extract_content_id("https://www.tiktok.com/@x/video/7660415942815403285"),
            "7660415942815403285"
        );
        assert_eq!(
            extract_content_id("https://www.tiktok.com/@x/photo/123456789012"),
            "123456789012"
        );
    }

    #[test]
    fn normalizes_scheme_less_tiktok_urls() {
        assert_eq!(
            normalize_tiktok_url("tiktok.com/@x/photo/123456789012").unwrap(),
            ("https://tiktok.com/@x/photo/123456789012".to_string(), true)
        );
        assert_eq!(
            normalize_tiktok_url("//www.tiktok.com/@x/video/123456789012").unwrap(),
            (
                "https://www.tiktok.com/@x/video/123456789012".to_string(),
                true
            )
        );
    }

    #[test]
    fn fallback_success_maps_nullable_stats() {
        let response = FallbackResponse {
            status: true,
            msg: "Data ditemukan".to_string(),
            error_class: String::new(),
            reason: String::new(),
            stats_found: true,
            stats_complete: true,
            stats_fields: vec![
                "like".to_string(),
                "share".to_string(),
                "comment".to_string(),
                "collect".to_string(),
                "view".to_string(),
            ],
            observed_at: "2026-07-16 10:00:00.000000".to_string(),
            data: serde_json::json!({
                "like": 27,
                "share": 3,
                "comment": 21,
                "collect": 5,
                "view": 517,
                "created_at": "2026-07-15",
                "content_id": "7660070976289525013",
                "media_type": "photo",
                "video_link": "",
                "cover": "",
                "images": []
            }),
        };

        let mapped = response.into_social_response();
        assert!(mapped.status);
        assert_eq!(mapped.data.view, Some(517));
        assert_eq!(mapped.data.content_id, "7660070976289525013");
        assert!(mapped.stats_found);
        assert!(mapped.stats_complete);
    }

    #[test]
    fn fallback_error_class_is_preserved() {
        let response = FallbackResponse {
            status: false,
            msg: "RapidAPI unavailable".to_string(),
            error_class: "infra_connect".to_string(),
            reason: "fallback_timeout".to_string(),
            stats_found: false,
            stats_complete: false,
            stats_fields: vec![],
            observed_at: "2026-07-16 10:00:00.000000".to_string(),
            data: Value::Null,
        };

        let mapped = response.into_social_response();
        assert!(!mapped.status);
        assert_eq!(mapped.error_class, "infra_connect");
        assert_eq!(mapped.reason_code, "fallback_timeout");
        assert_eq!(mapped.msg, "RapidAPI unavailable");
    }
}
