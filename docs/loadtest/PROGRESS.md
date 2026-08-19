# Endorse-refresh 400/min — phase-by-phase progress log

Working record of the effort to prove the endorse-refresh pipeline can sustain **400 unique
valid-post completions per minute** at **≤1.5 provider requests per completed post**, with
zero correctness violations.

Companion documents:
- [`ISSUES.md`](ISSUES.md) — every problem found, which phase it surfaced in, and its fix
- [`../../tools/loadtest/README.md`](../../tools/loadtest/README.md) — how to run the harness
- `tools/loadtest/reports/*.json` — machine-readable results per run

Status legend: **DONE** · **IN PROGRESS** · **PENDING**

---

## Phase 0 — Discover the real system — **DONE**

Read the actual code rather than trusting the brief. Findings that changed the plan:

**400/min is structurally impossible on the production path.** Three independent caps in
`Api_v2::cronjob_endorse_refresh`:

| Cap | Value | Evidence |
|---|---|---|
| ticks/minute | 1 | `deploy/monitoring/forbes-crontab-stability.snippet:3` (`* * * * *` + `flock -n`) |
| HTTP parallelism | **10** | `Api_v2.php:8232` — `boundedWorkerSetting(env(...), 10, 10)` |
| claim per tick | 50 | `Api_v2.php:8240` |

Ceiling ≈50/min. Production gets ~5.6/min because of a second, larger defect:
`Template::get_social_media_batch` (`Template.php:1619-1748`) runs the direct scrape in
parallel via `curl_multi`, then runs the RapidAPI fallback in a **sequential `foreach`**
(`:1690-1747`), plus a **third leg** (`scrapeTiktokDetailFromPage:2111`) that re-issues a
byte-identical copy of leg 1. Effective concurrency ≈1; ~70% of claims deferred per tick.

Other load-bearing discoveries:
- `ENDORSE_REFRESH_LIMITER_MODE=request_start_reservation` is set in production but **inert** —
  `CiDbReservationStore` is only wired into the dead `ENDORSE_REFRESH_INCREMENTAL_CLAIM` branch,
  and that branch only ever builds the `rapidapi` scope, never `direct_scrape`. There was **no
  cap at all** on outbound request starts.
- The existing `evt=endorse_refresh_request` log fires **once per queue item**, not per HTTP
  request, and **undercounts the leg-3 path by one**. Unusable as the ≤1.5 baseline.
- The Rust worker is 0/0 replicas by deliberate decision, uses a
  `claim → await whole batch → submit` barrier (`main.rs:389`), has **no rate limiter** and
  **no SIGTERM handling at all**.

**Decision:** build a long-running PHP CLI worker that replaces only the fetch loop, reusing
every hardened primitive (`claimBatch`, `applyResults`, `releaseUnstartedChunk`, `resetStuck`,
`stats_observation_seq` fencing) through injected callables.

**Production reconnaissance (read-only, via SSH):** 3 vCPU / 7.8 GB VPS, MySQL 8.0.46,
`forbes_app` 1/1, `forbes_endorse-refresh-worker` **0/0**, cron `* * * * *` with `flock -n`
and `--max-time 55`. Live tuning: `BATCH_SIZE=20`, `PARALLEL_HTTP=5`, `RATE_PER_MIN=60`,
`DEADLINE_SEC=45`, `LEASE_SEC=120`, `FETCH_MODE=bhskin_scrape`.

---

## Phase 1 — Production-isolated environment — **DONE**

Built `docker-compose.loadtest.yml`: disposable MySQL 8.0 (matching production), Go provider
mock, N workers, on an isolated network. Schema is built by the integration suite's **canonical
builder** (`QueueSchema::build`) — real schema dumps plus real migration `up()` paths in order —
because `migrations/run.php --pending` cannot run against a bare database (the migration set
assumes a business schema it does not create), and because a hand-written schema would make the
load test measure query plans production never runs.

Isolation is enforced in three layers and **verified against the running stack**, not asserted:

```
PASS  worker .env is the load-test file (mount took effect)
PASS  database name carries the _loadtest suffix
PASS  EndorseRefreshLoadTestGuard reports isolation proven
PASS  production MySQL is not reachable from the worker network
PASS  the worker network has no internet egress (mock mode)
PASS  worker is attached only to the isolated load-test network
PASS  no mail/webhook/push/Sentry credentials in the worker environment
```

See [ISSUES.md#issue-1](ISSUES.md#issue-1) and [#issue-2](ISSUES.md#issue-2) — the first
version of this phase **failed** two of its own checks.

Tests: `tests/Unit/EndorseRefreshLoadTestGuardTest.php`, 17 cases.

---

## Phase 2 — Request-level observability — **DONE**

Extended `endorse_refresh_rate_tokens` into a true per-request ledger
(`migrations/20260820120000_extend_endorse_refresh_rate_tokens_ledger.php`) rather than
building a parallel table: it is already at the right grain (one row per *started* request,
written immediately before the request goes out).

- `CiDbReservationStore::reserveToken(): ?int` returns the row id; `reserve()` is now a wrapper.
  Interface, `PdoReservationStore` and every existing test unchanged.
- `EndorseRefreshLedger` buffers outcomes in memory and flushes the whole buffer in **one
  multi-row `UPDATE … CASE`** on the loop's slow path — never a synchronous write per request.
- Deliberately **not** `INSERT … ON DUPLICATE KEY UPDATE`: that form requires an explicit `id`,
  so if retention deleted a row between reserve and flush it would silently **INSERT it back**,
  resurrecting a pruned row as a live rate-limit token. An `UPDATE` affects zero rows instead.
  The safety property is structural, not a matter of configuring retention correctly.

Tests: `tests/Unit/EndorseRefreshLedgerTest.php`, 10 cases.

---

## Phase 3 — Dataset and mock provider — **DONE**

**Mock** (`services/provider-mock/`, Go): serves both legs, with per-post deterministic
outcomes seeded by `crc32(contentID + seed)` so runs are comparable per `queue_id`. Includes a
`correlation` knob because dead/private/removed posts fail **both** legs — without it the
fallback's measured value is optimistic and the requests-per-completion prediction is wrong in
the direction that matters.

Verified against the **real** PHP parsers, not a copy of them
(`tests/integration/ProviderMockContractTest.php`, 7 cases): the rehydration `<script>` tag
matches `extractTiktokItemStructFromHtml`, the JSON satisfies
`isValidRapidApiTiktokDetailResponse`, and the URL is built by the real `buildTiktokDetailUrl`.

**Dataset:** 19,867 unique real TikTok URLs (19,855 unique content ids), exported read-only
from production — `link_upload` only, no names, no user data, no credentials. All query strings
stripped: they carry TikTok share/device attribution (`web_id`, `u_code`, `_d`,
`sender_device`) that the pipeline never reads. Git-ignored.

**Seeder** (`tools/loadtest/seed.php`): bulk corpus with **unique content ids per post** (reusing
one post would trigger `stats_observation_seq` idempotency, give unrealistic provider caching
and bypass real business-key behaviour), plus 8 pathological fixtures — invalid URL, empty URL,
private post, poison ghost attempt, inconsistent parent/attempt, expired lease, attempts
exhausted, completed-history-alongside-active.

---

## Phase 4 — The continuous worker — **IN PROGRESS**

| File | Role |
|---|---|
| `application/controllers/EndorseRefreshWorker.php` | CLI entry, wiring, signal handlers |
| `application/libraries/EndorseRefreshPipeline.php` | Continuous `curl_multi` state machine |
| `application/libraries/EndorseRefreshFetchKit.php` | `extends Template`; handle builders + re-exported parsers |
| `application/libraries/EndorseRefreshLedger.php` | Buffered ledger writer |

**`Template.php` is not modified.** It is a plain non-`final` class whose parsers are
`protected`, so a subclass reaches all of them — which is why a bug in the load-test worker
cannot affect the production cron path. All 15 inherited methods verified present.

Loop: claim only up to free capacity → fill slots → `curl_multi_exec` → harvest each completion
**immediately** (apply it, or start leg 2 as a new slot) → watchdog/ledger/rollup maintenance →
block on `curl_multi_select` only when nothing progressed. No chunk barrier, no blocking
`curl_exec`. `rescue_lane` becomes a longer per-slot timeout instead of collapsing concurrency
to 1.

Reservation happens **after** the leg is chosen and **before** the handle exists, so "one token
per started request" holds by construction for every outcome including 429, timeout and
malformed body. Both scopes participate — previously only RapidAPI did.

Tests: `tests/Unit/EndorseRefreshPipelineTest.php`, 7 cases, **zero network or DB** (a `reserve`
that always denies exercises the whole claim/deny/release path).

Request starts are additionally **paced locally** by a per-scope token bucket (refill
`limit/60/replicas` per second, burst 20) so the shared limiter receives a smooth stream rather
than a burst followed by a stall. Getting this right took three iterations —
[Issue 8](ISSUES.md#issue-8), [Issue 15](ISSUES.md#issue-15), [Issue 16](ISSUES.md#issue-16) —
the middle of which deadlocked the worker entirely.

Outstanding: confirm the final pacing shape holds across a full run.

---

## Phase 5 — Shared-code changes — **DONE**

Two changes to `EndorseRefreshQueueService`, each minimal and each defaulting to existing
behaviour so the cron path is byte-for-byte unchanged.

**5a. `applyResults(array $items, array $responses, array $opts = [])`** — guards the campaign
rollup loop behind `defer_campaign_rollup` and returns `touched_campaigns`.
`update_campaign_parent()` runs ~11 statements including four unbounded aggregates; per-item at
400 completions/min that is ~4,400 aggregate statements/min, which would displace the provider
as the bottleneck. The worker unions the campaign ids and flushes at most once per campaign per
10s, plus unconditionally at shutdown. Safe because the rollup is a pure recompute and was
*already* outside the per-item transaction — this changes timing, not atomicity.

**5b. `$opts['recovery_min_interval_sec']`** (default `0.0` = unchanged) plus a non-blocking
`GET_LOCK`, so only one replica runs `resetStuck()` at a time. At 400/min across replicas the
unguarded version runs ~20 recovery scans/second, each taking X-locks across rows legitimately
in flight elsewhere.

Proven by `tests/integration/EndorseRefreshApplyRollupParityTest.php` (4 cases): per-item apply
with deferred rollup produces a **byte-identical** snapshot of queue, attempts, endorse,
endorse_logs and campaign aggregates versus batch apply.

---

## Phase 6 — Graceful shutdown and crash tests — **DONE**

Implemented: `pcntl_async_signals`, a handler that flips **two scalars only**, stop-claiming,
release of all `$ready` via `releaseUnstartedChunk` (attempts *not* consumed), drain of
in-flight work with normal per-item apply, and at the deadline a synthesized `infra_stall`
applied to each remaining slot — those *did* start a request, so the attempt must be consumed;
releasing them would be a lie.

`tools/loadtest/test-shutdown.sh` covers three modes — `graceful`, `hard` (double SIGTERM),
`kill` (SIGKILL, no shutdown code runs at all) — asserting no silent loss, no attempts charged
to released rows, no duplicate business write, no false completion, no leaked ownership, and
that abandoned rows carry a lease and are actually reclaimed by a recovery cycle.

**All three modes executed and passing** (2026-08-19), each with ~20 requests genuinely in
flight at signal time:

| Mode | In flight at signal | After | Result |
|---|---|---|---|
| `graceful` | 19 | **0 processing**, completions 95→110, 3 unstarted claims released | 8/8 PASS |
| `hard` (double SIGTERM) | 21 | 12 abandoned, all leased, all reclaimed by recovery | 8/8 PASS |
| `kill` (SIGKILL) | 19 | 18 abandoned, all leased, all reclaimed by recovery | 8/8 PASS |

The SIGKILL case is the one that matters: none of the worker's shutdown code runs, and
correctness rests entirely on lease fencing. Nothing was lost, nothing was double-written, and
one recovery cycle reclaimed every abandoned row.

### The test initially reported two false failures

The first `graceful` run reported `rows still processing after a full drain (1)` and
`attempts charged to released rows (1)`. Both were **seeded pathological fixtures**, not
shutdown defects:

- fixture 5 (inconsistent parent/attempt) is *correctly* quarantined as `needs_reconciliation`
  and deliberately left in `processing` for human attention;
- fixture 7 is seeded with `attempts=3` and no attempt rows, which is indistinguishable from
  "an attempt was charged to a released row" unless excluded.

The assertions now scope to real rows by id (the seeder places fixtures at `count + 1000 + n`).
While fixing this I also tightened the post-recovery check from `<= 1` to `= 0`: with the
fixtures excluded there is no legitimate leftover, and the loose threshold would have hidden a
genuine single-row leak.

---

## Phase 7 — Measurement and iteration — **IN PROGRESS**

### Results so far (mock provider)

| Run | Config | Sustained (rolling 300s) | Amplification | Rate peak | Violations |
|---|---|---|---|---|---|
| A — happy path | 1 worker, 20 in-flight, 480+240 | **471.6–480.2/min** | 1.000 | 480+? | 10/10 PASS |
| B — p50 3s / p95 8s / p99 25s | 1 worker, 40 in-flight, 480+240 | **461.6–479.6/min** | 1.002 | — | 10/10 PASS |
| C — 20% transient, 5 min | 1 worker, 30 in-flight, 480+240 | 411.6–429.2/min | 1.386 | 655/min (over 600 budget) | 10/10 PASS |
| C — 15 min, **before** pacer | 2 workers, 30 in-flight, 400+200 | 248.4–374.6/min | 1.493 | 600/min PASS | 10/10 PASS |
| C — 10 min, **before** replica divisor | 2 workers, 30 in-flight, 400+200 | 284.8–375.0/min | 1.483 | 600/min PASS | 10/10 PASS |
| C — 10 min, **after** replica divisor | 2 workers, 30 in-flight, 480+240 | **367.8–443.6/min** | 1.449 | 692/min **over 600 budget** | 10/10 PASS |

The last row is the pacing fix working — the sawtooth is gone and the curve is straight — but it
is **not a valid capacity claim**, for two reasons found in that run:

1. It was configured at 720/min combined, above the approved 600/min local load-test limit
   ([ISSUES.md#issue-18](ISSUES.md#issue-18)).
2. It produced **11,668 cancelled attempts against 4,286 completions** and drove MySQL to
   **237.6% CPU** — a ready buffer sized from concurrency rather than from the start rate
   ([ISSUES.md#issue-17](ISSUES.md#issue-17)). Correct, but ~2.7 wasted claim/release cycles per
   completed post. Locally MySQL had cores to spare; production's 3-vCPU box would not.

| C — 10 min, buffer fix, in budget | 2 workers, 30 in-flight, 400+200 | 292.8–371.6/min | 1.470 | 580/min PASS | 10/10 PASS |
| C — 10 min, **matched split** | 2 workers, 30 in-flight, **480+120** | 361.6–**445.4**/min | **1.416** | **600/min PASS** | 10/10 PASS |

After the buffer fix, cancelled attempts on the same scenario dropped from **11,668 to 16** —
the churn is gone, and MySQL load with it.

### The budget split is a tuning variable, not a constant

At a 600/min combined budget the split between the two legs decides the ceiling, because the
first leg's cap bounds everything downstream. With measured `pA ≈ 0.741` and `pB ≈ 0.481`:

```
direct 400 / rapid 200  ->  400*0.741 + 104*0.481  = 346/min   (rapid uses 104 of its 200)
direct 480 / rapid 120  ->  480*0.741 + 117*0.481  = 412/min   (both legs saturated)
```

Same total budget, ~19% difference in output. The rule is that the fallback budget should be
`direct_rate x (1 - pA)` — anything more is unusable, anything less throttles the rescue path.
This makes the [Phase 2 leg census](#phase-2--request-level-observability--done) load-bearing for
configuration, not just for the amplification target.

Measured side by side, the matched split is strictly better on every axis at once — the same
output for fewer requests, inside budget, at a fifth of the database load:

| | 720/min budget | 600/min matched split |
|---|---|---|
| Completions | 4,286 | 4,278 |
| Requests started | 6,210 | 6,059 |
| Combined peak | 692/min (**over budget**) | 600/min PASS |
| Amplification | 1.449 | **1.416** |
| Cancelled attempts | 11,668 | **12** |
| MySQL peak CPU | 237.6% | **43.5%** |
| Worker CPU | 25–27% | **3.5–3.7%** |

### Why the 10-minute runs cannot settle the target

The acceptance criterion is ≥400/min sustained in a rolling 5-minute window for ≥15 consecutive
minutes. Over a 636-second completion span, *every* 5-minute window overlaps either ramp-up or
drain, which is why worst-rolling-300s reads 361.6/min while best reads 445.4/min. The
30-minute run is the only one of these that can express the criterion at all.

**Scenario B is the head-of-line proof:** with p99 ≈12.7s items in flight, throughput held at
the rate cap. Under the old chunk-barrier design a 12.7s item stalls its entire chunk of 5.

**Resources are not the constraint.** Mid-run: workers at **1.2–2.0% CPU** and **51 MB**
(6.6% of a 768 MB limit); MySQL 7.25% CPU; **3 database connections of 300 (1% of pool)**; zero
deadlocks. The binding constraint throughout has been the configured rate budget.

### Measurement integrity

- Throughput is reported as the **worst rolling 60s** and sustained 300s window, never the run
  total — see [ISSUES.md#issue-4](ISSUES.md#issue-4).
- Every run reconciles ledger rows against the mock's own request count. Scenario B onward:
  **delta +0**.
- Every run computes the true peak request-start rate per scope over any rolling 60s window.

### Outstanding

- ~~Confirm the pacer removes the sawtooth~~ — done; the curve is straight
  ([ISSUES.md#issue-16](ISSUES.md#issue-16)).
- ~~Production canary plan~~ — written: [CANARY.md](CANARY.md).
- Re-establish the capacity number within the 600/min local budget at the ratio-matched split
  (run in flight). The 443/min figure came from an over-budget run and does not count
  ([ISSUES.md#issue-18](ISSUES.md#issue-18)).
- Scenarios D, E, F, I.
- All three shutdown modes (`graceful`, `hard`, `kill`).
- 30-minute run with 12,000+ items.
- Phase 2 leg census against real providers (Stages 1–3) — gates both the amplification claim
  and the budget split.

---

## Deviations from the approved plan, and why

1. **The guard runs before `parent::__construct()`.** `config/autoload.php` autoloads
   `database`, so a guard in the method body would have validated an environment it had already
   connected to.
2. **The load-test network is `internal: true`.** The first isolation check *failed*: a default
   Docker bridge NATs outbound and the worker genuinely could reach production MySQL.
3. **`reserveToken` probes for the ledger columns once per process.** `docker-entrypoint.sh` runs
   migrations non-blocking, so the app can start with the ALTER unapplied — and the limiter
   fails *closed*, which would have denied all provider fallback requests.
4. **Local request pacing added.** Not in the plan; the brief's "10 starts/second smooth, burst
   20" turned out to be load-bearing rather than decorative (Issue 8).
