# Can sec-forbes run the same endorse-refresh worker?

Assessment for `secondary-forbes-skin-app`, deployed as `sec-forbes_app` on the same host.

**Verdict: the design supports it, but it is NOT safe to enable today.** Four blockers, one of
which would silently double the load on a provider that has already collapsed once.

---

## Measured facts

| | forbes | sec-forbes |
|---|---|---|
| Path | `~/artifact/forbes-skin` | `~/artifact/sec-forbes-skin` |
| Database | `forbes_app` | `forbes_app_sec` |
| Base URL | acnenosystem.com | bkasystem.com |
| Repository | `forbes-skin-app` | **`secondary-forbes-skin-app` — a different repo** |
| `endorse_refresh_queue` | yes | yes |
| `endorse_refresh_rate_tokens` | yes | **absent** |
| RapidAPI host | tiktok-video-no-watermark10 | identical |
| RapidAPI key fingerprint | `fde924d3f895` | **`fde924d3f895` — the same key** |
| `APP_NAME` | empty | empty |

---

## Blocker 1 — the rate limiters cannot see each other, but the provider quota is shared

This is the dangerous one.

`CiDbReservationStore` enforces its budget by counting rows in `endorse_refresh_rate_tokens`
**inside its own database**. forbes counts in `forbes_app`; sec-forbes would count in
`forbes_app_sec`. Neither can see the other's requests.

But both use the **same RapidAPI key**, so the provider sees the sum. Two workers each
configured at 240/min would put **480/min** on one key — and this provider collapsed at roughly
250/min, taking 14 minutes of 0 % success to recover (see `WORKER-PRODUCTION.md` §3).

Each side would report itself as perfectly within budget while together breaking it. Nothing in
the current design detects this.

**Resolved by the provider lease** (`EndorseRefreshWorker::acquireProviderLease`, added
2026-08-20). Rather than trying to make two independent counters add up, it makes concurrency
impossible: MySQL user-level locks are per-**server**, not per-schema, and both schemas live in
one instance, so a single named lock serialises the tenants regardless of schedule drift.

```
lock name : endorse-provider:<md5-8 of RAPIDAPI_KEY>
acquire   : GET_LOCK(name, 0) before claiming — non-blocking
denied    : log provider_busy, return an empty claim, retry in ~1s. NEVER exit.
release   : on an empty claim (queue drained) and on shutdown
```

Keying on a **fingerprint of the key** is the load-bearing detail: tenants sharing a key
exclude each other, tenants given separate keys run in parallel automatically, and a third
tenant needs no configuration. Enabled per tenant with `ENDORSE_REFRESH_PROVIDER_LOCK=1`; off
by default so a single-tenant deployment is unchanged.

It must be enabled on **both** workers or it protects nothing.

The alternatives remain valid if the shared key is ever split:

1. **Separate RapidAPI keys.** The scope is a fingerprint of the key, so distinct keys give
   independent budgets *and* independent quota — and the lease then stops serialising them,
   automatically. Cleanest, and the only one that scales past two tenants running at once.
2. **Split one budget by hand**, e.g. 120/min each. Simple, but every future change has to be
   made in two places and nothing enforces the arithmetic.
3. **Share one rate-limit table.** Correct in principle, but couples the two tenants'
   availability and is the largest change.

## Blocker 2 — `forbes_app_sec` has no `endorse_refresh_rate_tokens` table

The database has only `endorse_refresh_queue` and `endorse_refresh_queue_attempts`.

`reserve()` fails **closed** on error. A worker started against this database would deny every
single provider request — it would not crash, it would simply do nothing, which is a much
harder failure to diagnose. The ledger migration must run there first, and the ten columns must
be verified exactly as in `WORKER-PRODUCTION.md` §9.

## Blocker 3 — ~~the secondary repository does not contain the worker~~ WITHDRAWN

**This blocker was wrong, and it was the expensive one.** It said the worker subsystem had to
be ported into `secondary-forbes-skin-app` and the two diverged `EndorseRefreshQueueService`
copies reconciled — weeks of work against a repo with no `composer.json`, no `phpunit.xml` and
no migration runner. Measurement on 2026-08-20 says no port is needed at all.

**The worker never executes the secondary app's PHP. It only touches tables.** It reads
`endorse_refresh_queue` and writes `endorse` / `endorse_logs` through `Endorse_sync::apply`.
Which app's code enqueued a row is irrelevant to draining it.

Three facts make one binary serve both tenants:

1. `application/config/database.php` has a single, entirely env-driven group, and `env()`
   (`application/helpers/env_helper.php:51-53`) prefers the `.env` at FCPATH over `getenv()`.
   **A different mounted `.env` is already a tenant selector** — no `DB_GROUP`, no code.
2. `Endorse_sync::apply` guards every divergent column with `$db->field_exists()`
   (`Endorse_sync.php:327`, `:340`, `:350`, `applyObservationMetadata:183-197`), so it degrades
   cleanly against a differently-shaped schema — and it *populates* sec's `tiktok_content_id` /
   `tiktok_cover` / `tiktok_media_type`, columns forbes' own schema does not even have.
3. The gap is **schema**, not code, and schema is what migrations are for.

Measured column diff:

```
forbes_app_sec.endorse_refresh_queue  MISSING: next_attempt_at, lease_expires_at, claim_owner,
        active_attempt_id, attempt_sequence, enqueue_run_id, enqueue_source,
        active_business_slot, provider_job_id, provider_submitted_at
forbes_app_sec.endorse / endorse_logs MISSING: stats_observation_seq, stats_source,
        stats_observed_at, stats_fields, stats_completeness
forbes_app_sec                        MISSING TABLES: endorse_refresh_rate_tokens,
                                                      endorse_refresh_quarantine
```

So the work is **seven migrations, one `.env.worker`, one Swarm service** — and no file in
`secondary-forbes-skin-app` is touched.

```
20260716093000_add_endorse_refresh_contract_v2.php          (order is load-bearing:
20260727090000_add_threads_scraper_queue_state.php           20260803130000 adds its column
20260803120000_create_endorse_refresh_rate_tokens.php        AFTER stats_observed_at, which
20260803130000_add_stats_observation_seq.php                 20260716093000 creates)
20260818150000_add_endorse_refresh_diagnostics.php
20260818170000_harden_endorse_refresh_claims.php
20260820120000_extend_endorse_refresh_rate_tokens_ledger.php
```

**Then `--baseline`, and the order matters.** `docker-entrypoint.sh:13-19` runs
`migrations/run.php --pending` on *every* container start, so a worker container holding sec's
`.env` would otherwise apply all 34 forbes migrations to `forbes_app_sec` — attendance,
notifications, announcements, device_tokens, permission_meta — silently reshaping sec's
database. Run the seven explicitly with the entrypoint overridden, then `--baseline` the
remaining 27, then confirm `--status` reports zero pending.

What remains true from the original blocker: sec's `EndorseRefreshQueueService` **has** diverged
(908 lines vs 2,376; no `applyResults($opts)`, no `recoveryIsDue()`, no `driverOwnsDraining()`,
no quarantine awareness). That matters for what sec's own cron can do — see "Two write paths"
below — but it is not a prerequisite for the worker.

### Two write paths against one set of tables

Once the worker drains sec, two codebases write the same tables: the worker (forbes' code) and
sec's app (its own older code, for enqueue and the UI). Consequences worth knowing:

- **sec's enqueue path knows nothing about quarantine**, so it re-queues posts already proven
  gone. This is why the quarantine exclusion belongs in the **claim** SELECT
  (`EndorseRefreshClaimRepository::buildSelectForUpdateSql`) and not only in `enqueueRows`: the
  claim is the one gate every row passes through, whoever queued it.
- **sec's `Endorse_sync` is ahead of forbes' in one place** — it has `ERR_DATA_QUALITY` and
  `PROVIDER_UNRESOLVABLE_PATTERNS`, which forbes lacks. Copying either file over the other
  would regress behaviour that has tests asserting it. Leave both in place; they do not meet.
- **sec's cron driver gate only recognises `'rust'`** (`Api_v2.php:277`), so setting
  `ENDORSE_REFRESH_DRIVER=php_worker` there would *not* stand its cron down. Comment the cron
  lines out instead — which is the agreed approach anyway.

## Blocker 4 — `APP_NAME` is empty on both, so the locks collide

The limiter's advisory lock is `endorse-rate:<env>:<app>:<scope>` and recovery uses
`endorse-recovery:<env>:<app>`. Both default to `prod` / `forbes` when unset — and `APP_NAME`
is empty in **both** `.env` files.

So the two tenants would take the *same* lock name while counting in *different* tables: they
would serialise against each other for no benefit, and still not coordinate their rates. The
worst of both behaviours.

Set a distinct `APP_NAME` per tenant before running anything concurrently.

---

## What is genuinely safe about sharing the worker

The parts that matter for correctness are already tenant-scoped, and none of the blockers above
is a correctness risk — they are capacity and packaging risks.

- **Claiming** is `FOR UPDATE SKIP LOCKED` against each tenant's own queue table, with a fenced
  `activateClaimRow` asserting `affected_rows === 1`. Two tenants cannot claim each other's
  rows; they are different tables in different schemas.
- **Applying** is per-item transactional with `stats_observation_seq` fencing, so a slow tenant
  cannot write a stale result over a newer one.
- **The boot guard** refuses to start unless the rate budget is positive and bounded, the
  driver is `php_worker`, and the replica count is declared — per tenant.
- **Shutdown** releases unstarted claims without consuming attempts, and abandoned rows are
  recovered by lease expiry. Verified under graceful, double-SIGTERM and SIGKILL.

## Order of work

1. Enable `ENDORSE_REFRESH_PROVIDER_LOCK=1` on the **forbes** worker (blocker 1). Do this
   first: it is the thing that makes a second tenant safe, and it is a no-op while forbes runs
   alone.
2. Run the seven migrations against `forbes_app_sec`, then `--baseline`; verify the ten ledger
   columns and that `--status` reports zero pending (blocker 2 + blocker 3).
3. Write `/home/forbes/artifact/sec-forbes-skin/.env.worker` — a copy of sec's `.env` plus the
   worker keys, with `APP_NAME=sec-forbes` (blocker 4). A worker-only file, *not* sec's app
   `.env`, so sec's running app is untouched.
4. Create the service at **0 replicas**, run `EndorseRefreshWorker check`, and only then scale.
5. Start at `MAX_IN_FLIGHT=5` and watch the shared provider's **latency**, not just sec's own
   success rate — degradation caused by one tenant shows up first in the other.

Note that sec-forbes runs its endorse-refresh cron **three times per minute** (offsets
18 s / 38 s / 58 s). Those entries must stand down or cron and worker will both drain. Setting
`ENDORSE_REFRESH_DRIVER=php_worker` will **not** do it — sec's gate only recognises `'rust'`
(`Api_v2.php:277`). Comment the crontab lines out.

## Before the worker: sec's cron is throttled, not broken

Measured 2026-08-20, and worth recording because it is the cheapest fix in this document.

```
sec .env carried exactly ONE endorse-refresh key : ENDORSE_REFRESH_BATCH_SIZE=10
everything else on code defaults
measured                                          10-30 attempts/min at ~95% success
```

At ~22/min a 12,548-post pass takes about **21 hours**. Nothing is failing — the success rate
is fine and the provider path works. sec is simply claiming ten rows at a time.

sec's own code already permits far more (`Api_v2.php:287-292` caps batch at 50 and concurrency
at 20), and `ENDORSE_REFRESH_DAILY_CAP` defaults to 0 (off) with `RATE_PER_MIN` defaulting to
250. So the ceiling was never the code.

Raised to `BATCH_SIZE=30`, `PARALLEL_HTTP=12`, with an explicit `RATE_PER_MIN=150` brake —
deliberately short of the code's 50/20, because forbes collapsed this provider at 30 concurrent
and is stable at 5, and everything between 6 and 29 is unmeasured. Edited with `cat >` so the
bind-mount inode survives; `env()` re-reads the file per request, so no restart is needed.
