# Production canary plan — endorse-refresh PHP worker

**Status: PLAN ONLY. Nothing here has been executed against production.** No production data,
env, service, migration, queue or deployment has been modified during this work.

This document is written so that someone who did not run the load tests can execute the
rollout, recognise a failure, and roll back without needing to understand the pipeline
internals.

> **Config values marked _(provisional)_ are pending the final 30-minute run and the real-provider
> leg census.** Everything else — ordering, gates, rollback, monitoring — is stable.

---

## 1. What changes, and what does not

| Component | Change | Risk if wrong |
|---|---|---|
| `endorse_refresh_rate_tokens` | ALTER adds 10 NULLable columns + 3 indexes | Limiter fails **closed** if code ships before the ALTER — see §2 |
| `Api_v2::cronjob_endorse_refresh` | Stand-down gate widened to `php_worker` | Cron keeps draining (status quo) if the env is not set |
| `EndorseRefreshQueueService::applyResults` | New optional `$opts` arg | None — cron passes nothing and behaves identically |
| `EndorseRefreshQueueService::claimBatch` | New optional `recovery_min_interval_sec` | Defaults to `0.0` = current behaviour exactly |
| New swarm service `forbes_endorse-refresh-php-worker` | Added at **0 replicas** | None until deliberately scaled |
| Rust `endorse-refresh-worker` | **Untouched, stays 0/0** | — |

The three shared-code changes are all additive with defaults that reproduce current behaviour
byte for byte. That is deliberate: deploying the code must be a no-op until the driver env is
flipped.

## 2. Migration ordering is the one hard dependency

`docker-entrypoint.sh` runs migrations **non-blocking** — if the migration step fails or times
out, the app starts anyway. That is the right call for an index, but it means the app can
serve traffic with the ALTER unapplied.

`CiDbReservationStore::reserve()` fails **closed** on error. Had the worker shipped without a
column probe, an unapplied ALTER would have denied *every* provider request rather than
degrading. This is [ISSUES.md#issue-3](ISSUES.md#issue-3); it is already mitigated by a
one-time column probe in both stores, but the ordering requirement stands:

```
1. Deploy code (migration runs on app start; worker service still 0 replicas)
2. VERIFY the ALTER applied before scaling the worker above 0:
```

```sql
SELECT COUNT(*) AS ok FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = DATABASE()
   AND TABLE_NAME   = 'endorse_refresh_rate_tokens'
   AND COLUMN_NAME IN ('leg','ok','http_code','curl_errno','total_time_ms',
                       'error_class','retry_after_sec','worker_id','test_run_id','finished_at');
-- expect 10
```

If this returns anything but 10, **stop** and apply the migration manually. Do not scale the
worker.

## 3. Cutover sequence

Production is Docker Swarm, deployed by `deploy/develop.yml` (Ansible), stack `forbes`.

**Step 0 — precondition.** Confirm `ENDORSE_REFRESH_DRIVER=cron`, Rust worker 0/0, and the
column check in §2 returns 10.

**Step 1 — record the baseline.** Run the §5 queries and keep the output. Without a
before-number, no rollback decision can be made objectively.

**Step 2 — one worker, cron still running.** Scale the PHP worker to 1 replica while leaving
`ENDORSE_REFRESH_DRIVER=cron`. Both drain concurrently. This is *safe* — `FOR UPDATE SKIP
LOCKED` plus the fenced `activateClaimRow` prevents any double-claim — but it is deliberately
temporary, because the cron path re-enables the serial RapidAPI fallback and spends provider
budget outside the worker's reservation accounting.

Set conservative rates for this step _(provisional)_:

```
ENDORSE_REFRESH_WORKER_REPLICAS=1
ENDORSE_REFRESH_MAX_IN_FLIGHT=20
ENDORSE_REFRESH_DIRECT_RATE_PER_MIN=120
ENDORSE_REFRESH_RAPIDAPI_RATE_PER_MIN=60
ENDORSE_REFRESH_LIMITER_MODE=request_start_reservation
```

Soak 30 minutes. Watch §5. Proceed only if all invariants are zero.

**Step 3 — cron stands down.** Set `ENDORSE_REFRESH_DRIVER=php_worker`. The cron now returns
`driver_php_worker_standby` immediately; the manual "Proses Sekarang" button (`force=1`) still
works inline, which is the intended manual escape hatch.

**Step 4 — raise the budget in rate steps, one variable at a time.** Rate and concurrency are
separate controls and must never be changed in the same step ([ISSUES.md#issue-8](ISSUES.md#issue-8)
is what happens when the shape of the rate is wrong even though the cap is right).

**Split the budget in the measured leg ratio, not 2:1.** The first leg's cap bounds everything
downstream, so fallback budget beyond `direct_rate × (1 − pA)` is unusable while the direct leg
starves. Measured on mock Scenario C (`pA ≈ 0.73`), same 600/min total:

```
direct 400 / rapid 200  ->  sustained 292.8-371.6/min   (rapid used only ~95 of 200)
direct 480 / rapid 120  ->  both legs saturated
```

Recompute the ratio from production's own `pA` at each step (§5 per-leg query) rather than
inheriting the mock's. The table below assumes `pA ≈ 0.73`; **re-derive it before step 4b**.

| Step | direct/min | rapid/min | combined | Soak |
|---|---|---|---|---|
| 4a | 145 | 35 | 180 | 30 min |
| 4b | 290 | 70 | 360 | 30 min |
| 4c | 435 | 105 | 540 | 60 min |
| 4d | 580 | 140 | 720 | 60 min |

720/min is the approved combined operational safety ceiling. **Do not exceed it.** The
provider hard limit is 800/min; the 600/min figure is the *local load-test* limit and does not
apply in production.

**Expected capacity.** At the mock Scenario C conversion of ~0.68 completions per request,
720/min yields ≈490/min — comfortable headroom over the 400 target. Note this headroom exists
only at the ceiling: at the 600/min local limit the same profile yields ≈408/min, roughly a 2%
margin. Production capacity therefore depends on running at step 4d, and on `pA` being no worse
than the mock's 0.73.

**Step 5 — replicas, if needed.** Only after 4d is stable. When scaling to N replicas,
`ENDORSE_REFRESH_WORKER_REPLICAS` **must** be updated to N in the same change — it is a
rate-relevant setting, not just a capacity one
([ISSUES.md#issue-16](ISSUES.md#issue-16)). Understating it reintroduces bursting; overstating
it merely paces slower than necessary, so when in doubt, overstate.

## 4. Rollback

Rollback is a single env change and is always available:

```
ENDORSE_REFRESH_DRIVER=cron          # cron resumes on the next minute tick
docker service scale forbes_endorse-refresh-php-worker=0
```

Scale to 0 sends SIGTERM. The worker stops claiming, releases every unstarted claim without
consuming a retry attempt, lets in-flight requests finish and apply, then exits. Rows that were
genuinely mid-request are recovered by lease expiry — verified in all three shutdown modes
including SIGKILL, where none of the worker's own shutdown code runs at all.

**Roll back automatically if any of these hold:**

- any correctness invariant in §5 is non-zero
- provider 429 rate exceeds 1% of requests
- any 401/403 from the provider (credential or policy problem — stop, do not retry)
- combined request starts exceed 720/min over any rolling 60s window
- app or DB CPU >80%, memory >85%, or DB connections >75% of pool
- completions/min falls *below* the pre-cutover baseline

## 5. Monitoring queries

Run these at each soak step. Every "must be zero" query returning non-zero is a rollback
trigger, not something to investigate while the worker keeps running.

**Throughput (worst rolling 60s, not the flattering average — see [ISSUES.md#issue-4](ISSUES.md#issue-4)):**

```sql
SELECT MIN(c) AS worst_per_min FROM (
  SELECT COUNT(*) AS c
    FROM endorse_refresh_queue
   WHERE status='completed' AND completed_at >= NOW() - INTERVAL 30 MINUTE
   GROUP BY UNIX_TIMESTAMP(completed_at) DIV 60
) w;
```

**Request-start rate per scope (the budget check):**

```sql
SELECT scope, COUNT(*) AS starts_last_60s
  FROM endorse_refresh_rate_tokens
 WHERE created_at >= NOW(6) - INTERVAL 60 SECOND
 GROUP BY scope;
```

**Per-leg success — this is what sets the budget split (§3 step 4):**

```sql
SELECT leg,
       COUNT(*)                                   AS requests,
       SUM(ok = 1)                                AS successes,
       ROUND(SUM(ok = 1) / COUNT(*), 4)           AS success_rate,
       ROUND(AVG(total_time_ms))                  AS avg_ms
  FROM endorse_refresh_rate_tokens
 WHERE created_at >= NOW() - INTERVAL 30 MINUTE
   AND finished_at IS NOT NULL
 GROUP BY leg;
```

`success_rate` for `direct_scrape` is `pA`. Set `rapid_rate ≈ direct_rate × (1 − pA)`. If the
RapidAPI leg's request count sits well below its configured cap, the split is wasting budget
that the direct leg could be using.

**Amplification (must stay ≤1.5):**

```sql
SELECT
  (SELECT COUNT(*) FROM endorse_refresh_rate_tokens
    WHERE created_at >= NOW() - INTERVAL 30 MINUTE) /
  (SELECT COUNT(*) FROM endorse_refresh_queue
    WHERE status='completed' AND completed_at >= NOW() - INTERVAL 30 MINUTE)
  AS requests_per_completion;
```

**Requests that started and never returned (silent loss detector):**

```sql
SELECT COUNT(*) FROM endorse_refresh_rate_tokens
 WHERE finished_at IS NULL
   AND created_at < NOW(6) - INTERVAL 60 SECOND
   AND created_at >= NOW(6) - INTERVAL 30 MINUTE;
```

**Correctness invariants — all must be zero:**

```sql
-- two processing attempts for one queue row (duplicate ownership)
SELECT COUNT(*) FROM (
  SELECT queue_id FROM endorse_refresh_queue_attempts
   WHERE status='processing' GROUP BY queue_id HAVING COUNT(*)>1) d;

-- terminal row still holding a worker or an active attempt pointer
SELECT COUNT(*) FROM endorse_refresh_queue
 WHERE status IN ('completed','failed')
   AND (worker_id IS NOT NULL OR active_attempt_id IS NOT NULL);

-- duplicate business write
SELECT COUNT(*) FROM (
  SELECT id_endorse, date FROM endorse_logs
   GROUP BY id_endorse, date HAVING COUNT(*)>1) d;

-- attempts past the configured maximum (infinite poison retry)
SELECT COUNT(*) FROM endorse_refresh_queue
 WHERE attempts > CAST(@max_attempts AS UNSIGNED);
```

**Ledger retention** — confirm pruning is keeping up, since the ledger is the evidence base:

```sql
SELECT COUNT(*) AS rows_total,
       TIMESTAMPDIFF(MINUTE, MIN(created_at), NOW()) AS oldest_minutes
  FROM endorse_refresh_rate_tokens;
```

`ENDORSE_REFRESH_TOKEN_RETENTION_SEC` defaults to 3600 and is validated `>= 60`. A retention
shorter than the flush interval could let a buffered terminal update resurrect a pruned row as
a live rate-limit token — which is why the ledger writer uses a multi-row `UPDATE … CASE` and
never an upsert ([ISSUES.md](ISSUES.md)).

## 6. Local↔production differences that matter

The measurements were taken on Apple Silicon under OrbStack. Production is a **3-vCPU, 7.8 GB
x86 VPS on which MySQL already holds ~3.3 GB**. The differences that could change the outcome:

| | Local | Production | Consequence |
|---|---|---|---|
| CPU | Apple Silicon, many cores | 3 vCPU shared with app + MySQL | Worker CPU measured at 1.2–2.0%; headroom looks ample but is not free |
| DB | Dedicated disposable MySQL 8.0 | Shared with the whole app, 3.3 GB resident | Connection budget matters more; measured 3 of 300 |
| Provider | Local mock, no network egress | Real TikTok + RapidAPI over WAN | **Latency will be higher and more variable** — the dominant unknown |
| Arch | arm64 native | x86_64 | Base image has no arm64 manifest ([ISSUES.md#issue-13](ISSUES.md#issue-13)); built natively locally rather than emulated, which would have distorted CPU 5–10× |

**The honest limitation:** required concurrency scales with latency
(`concurrency ≈ 6.67 × avg end-to-end seconds`). Every local latency figure comes from a mock
on the same host. If real-provider p95 latency is materially worse than the Scenario B profile
(p50 3s / p95 8s / p99 25s), `ENDORSE_REFRESH_MAX_IN_FLIGHT` must rise proportionally — that is
the single most likely reason production behaves differently from these runs.

## 7. What is proven, and what is not

See [PROGRESS.md](PROGRESS.md) for the measurement record. In short:

- **Proven against a mock provider:** sustained throughput above target with zero correctness
  violations, amplification within budget, request-start rate within the approved ceiling, and
  correct behaviour under transient failure, high latency, and all three shutdown modes.
- **Not proven:** real-provider success rates. The leg census that determines the true `pA`
  (direct-scrape success) and conditional `pB` (fallback rescue rate) governs amplification by
  arithmetic, not tuning: scrape-first requires `pA ≥ ~0.565` to stay under 1.5. Until that is
  measured against real providers, the amplification target is an assumption, not a result.

Do not treat this plan as authorising a production deployment. It is the plan that a
deployment decision would be made *from*.
