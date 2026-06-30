# Endorse Refresh — 06:00 / 23:00 Window Reliability

**Date:** 2026-06-30
**Status:** Approved design (measure-first)

## Problem

acneno (~10k active TikTok endorses) and BKA (~5k) must fully refresh social metrics twice a day,
at **06:00** and **23:00** (off-hours; staff work 08:00–17:00). The refresh stalled, prompting a
proposal to rewrite the fetch worker in **Rust** as a new service.

## Research findings (last month of `develop` + live prod)

- **The pipeline is the codebase's main effort.** 168 commits/30 days; the arc:
  ScrapingBot async queue → RapidAPI parallel `curl_multi` fan-out (`#174–176`) → daily cap (`#165`)
  → per-minute rate cap + tunable concurrency (`63ae3358`) → wall-clock budget (`349760b0`) →
  bigger batches (`55c3db44`) → purpose-scoped snapshots.
- **Budget is fine.** ~15k calls/round (10k + 5k) × 2/day ≈ 30k/day (≈900k/mo) < the 1M RapidAPI
  plan. The earlier apparent overage was **churn** — deferred attempts were real API calls — removed
  by PR #180.
- **PHP is capable.** Same cron code: **BKA completed 68,780 refreshes**; acneno only 884, because
  acneno runs the broken build. Not a language problem.
- **Rust is the wrong lever.** The hard ceiling is the shared **500 req/min** RapidAPI limit (~8/s).
  PHP/Node/Rust all saturate that trivially; raw speed buys nothing.
- **Real wall = execution model.** The per-minute HTTP cron cold-starts PHP, claims a batch, runs
  ≤45s under nginx's 60s ceiling, then dies — fragmented, can't reliably *sustain* ~250/min of
  successful fetches; plus crude cross-app coordination of the shared 500/min.

## Decision

**Measure the fixed cron before rewriting.** At a steady 500/min, 15k/round drains in ~30 min — well
inside the windows — so the fixed PHP cron very likely suffices. Prove it with data before building
a new service.

## Plan

### 1. Deploy parity (ops)
Put acneno on the fixed `develop` build (PR #180: stale-recovery before caps; delete deferred
attempt rows; failure diagnostics). Biggest single lever; prerequisite to measuring.

### 2. Throughput tuning (env only, no deploy)
Size each run to complete up to the 250/min per-app cap within 45s:
`ENDORSE_REFRESH_BATCH_SIZE=250`, `ENDORSE_REFRESH_PARALLEL_HTTP=20`,
`ENDORSE_REFRESH_DEADLINE_SEC=45`, `ENDORSE_REFRESH_RATE_PER_MIN=250` (250+250 = the shared 500/min).
All already read by `Api_v2::cronjob_endorse_refresh`; leftovers defer cleanly (no attempt charged
after PR #180).

### 3. Window visibility (reuse existing monitoring)
- `tools/monitoring/backlog_snapshot.php` — added gauges: `forbes_endorse_refresh_queue_total{status}`,
  `forbes_endorse_refresh_oldest_pending_age_seconds`, `forbes_endorse_refresh_completed_today_total`.
- Per-run counts already land in `monitor-YYYY-MM-DD.log` `job_finish` for `cronjob_endorse_refresh`
  (`completed_count/failed_count/retrying_count/deferred_count` after PR #180).
- Failure reasons now visible per attempt in the queue Riwayat (`http=/apicode=/apimsg=/dataid=`).
- *Optional follow-up (YAGNI until needed):* a per-window drain summary (minutes-to-drain, peak
  rate) on the queue page, derived from the above.

### 4. Measurement protocol + decision gate
Across several real 06:00 / 23:00 cycles for both apps, record: minutes to drain to ~0 pending;
sustained completions/min (target ≈250/app, ≈500 combined); failure breakdown (genuine deleted
content vs `http=429`/timeout).

- **Success** = both apps drain within their windows for several consecutive days, sustaining
  ≈250/min/app, failures explained as real deleted content → **keep PHP cron; no Rust.**
- **Fail** (cron can't sustain the rate) → escalate to a **persistent shared worker** owning the key
  + one 500/min token bucket, draining both queues (PHP-CLI reusing `Endorse_sync`/`Template`, or
  Node per `docs/tiktok-worker/README.md`). Rust only if profiling shows fetch+parse CPU is itself
  the bottleneck — which at 500/min it is not.

## Out of scope
Rust service, persistent worker, RapidAPI plan upgrade, separate per-app keys — all deferred unless
the measurement gate fails.
