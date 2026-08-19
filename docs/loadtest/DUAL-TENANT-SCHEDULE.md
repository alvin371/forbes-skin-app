# Two tenants, one RapidAPI key — risk map and required work

Target schedule:

| Waktu | Tenant | Beban |
|---|---|---|
| 00:05 | forbes | all-refresh-sync, 24.551 post |
| 04:00 | sec-forbes | all sync, 12.530 post |
| 16:00 | sec-forbes | all sync, 12.530 post |

The shared RapidAPI key is deliberate — one company, two market segments — so the mitigation is
**time separation** rather than separate keys. That is workable, but it only holds if the
separation is *enforced* and if each window is actually long enough. Neither is true today.

Measured 2026-08-19 unless stated.

---

## RISK 1 — the 04:00 start leaves no margin; 05:00 fits (MEDIUM)

**Corrected 2026-08-19.** An earlier version of this document put the forbes corpus at 24.551
and concluded the schedule was impossible. That was wrong: it filtered only on
`e.status='Aktif'`, while `enqueueAllActive` also joins `endorse_campaign` and requires
`c.status='Aktif'` **and** `e.status_campaign='Aktif'`.

```
filter salah (e.status saja)        24.551
enqueueAllActive sebenarnya         16.430   (16.283 di antaranya Tiktok)
```

Recomputed against measured throughput — median **62 completion/min**, range 4–94:

```
16.283 / 62  =  263 menit = 4 j 23 m   →  00:05 + 4:23 = 04:28   (tipikal)
16.283 / 50  =  326 menit = 5 j 26 m   →  05:31                  (malam buruk)
16.283 / 85  =  192 menit = 3 j 12 m   →  03:17                  (forbes sendirian)
```

Plus a retry tail of roughly 10–20 minutes (6–9 % of attempts retry on a 60–90 s backoff).

So the honest picture:

| Start sec-forbes | Buffer di throughput tipikal | Aman di malam buruk? |
|---|---|---|
| 04:00 | **−28 menit (bentrok)** | tidak |
| 05:00 | +32 menit | tidak (selesai 05:31) |
| 06:00 | +1 j 32 m | ya |

**05:00 is a reasonable choice and clearly better than 04:00**, but the margin is thin: one slow
night eats it. Note the 62/min figure was measured *while sec-forbes was also draining*; alone,
forbes should be faster, which is why 05:00 is defensible rather than reckless.

The reliable fix is not a bigger buffer — it is RISK 2's run lock, which turns an overrun into
"the second tenant waits" instead of "both tenants collapse the provider". With that in place,
05:00 is safe even on a bad night.

sec-forbes, for reference:

```
12.530 / 62 = 202 menit = 3 j 22 m
05:00 → 08:22     16:00 → 19:22
```

Total provider time per day ≈ 11 h, comfortably inside 24 h once the runs are sequential.

**Ways to widen the margin further**

1. Route `/video/` back to the scrape. 35 % of the corpus leaves RapidAPI entirely (free,
   ~1 s, verified 12/12 alive), so the same provider budget carries more posts per minute.
2. Raise concurrency — but see RISK 6; the provider collapsed at 30 and the safe ceiling
   between 6 and 29 is unmeasured.

## RISK 2 — nothing enforces the separation, and it is already being violated (HIGH)

At the time of writing, **both tenants were draining simultaneously**:

```
22:03–22:11   sec-forbes  20–30 attempt/menit   (cron 3x per menit)
              forbes      46–75 request/menit   (worker)
              latensi forbes 2,8–5,5 s (rata-rata ~3,9 s, naik dari ~3,3 s saat sendirian)
```

Nobody scheduled this; it is simply what the current configuration does. A time-based plan that
depends on humans not making mistakes will be violated the first time a run overruns — and
RISK 1 guarantees the first run overruns.

**Required:** a cross-tenant mutual exclusion that does not depend on the clock.

The clean mechanism already exists in the stack: **MySQL `GET_LOCK` is per-server, not
per-database.** Both tenants share one MySQL instance, so a single named lock held for the
duration of a run gives real mutual exclusion regardless of schedule drift:

```
worker startup : SELECT GET_LOCK('endorse-refresh:provider:<rapidapi-key-fingerprint>', 0)
   got it      → run
   did not     → log "tenant lain sedang jalan", exit 0, let Swarm retry later
worker exit    : RELEASE_LOCK(...)   (also released automatically when the connection drops)
```

Keying the lock on the **API key fingerprint** is the important detail: tenants that share a
key exclude each other, tenants with separate keys run in parallel, and the behaviour stays
correct if a third tenant appears or if keys are split later.

## RISK 3 — the forbes worker runs 24/7, which is incompatible with a scheduled model (HIGH)

`ENDORSE_REFRESH_DRIVER=php_worker` makes the worker the permanent drainer. It is not a job
that starts at 00:05; it runs continuously and picks up whatever is enqueued, whenever.

And work *is* enqueued continuously: `endorse-sync-campaign` runs **every 15 minutes** on
forbes. So forbes will be active at 04:00 and at 16:00 regardless of the all-sync schedule.

**Required:** decide which model applies, and make the configuration match.

- *Scheduled*: scale the service to 1 at 00:05 and to 0 when the queue drains. Deterministic
  windows, but a drained-queue detector is needed or it runs forever at zero throughput.
- *Continuous with a lock* (recommended): leave it running, let RISK 2's lock serialise the
  tenants. Simpler, self-correcting when a run overruns, and no queue-drain detection needed.

## RISK 4 — quota headroom is thinner than it looks (MEDIUM)

```
Kuota bulanan      2.166.666
Sisa               1.796.252   (reset dalam 22,4 hari)
Terpakai           ~370.000 dalam ~7,6 hari  →  ~48.700/hari
```

Under the target schedule:

```
forbes   24.551 × 1 pass  = 24.551 post/hari
sec      12.530 × 2 pass  = 25.060 post/hari
                            49.611 post/hari
× amplifikasi ~1,15 (retry + post mati)  ≈  57.000 request/hari
× 30 hari                                 ≈  1,71 juta/bulan   =  79 % kuota
```

79 % leaves little room. A provider incident that triggers retries, one extra pass, or a rise
in dead posts pushes this over. Two things make it worse than the arithmetic suggests:

- **Dead posts are re-enqueued every pass.** forbes currently carries 4.523 terminal-failed
  rows. `enqueueAllActive` filters only on `endorse.status='Aktif'`, and the partial-unique key
  does not constrain `failed` rows, so those posts are re-queued and re-attempted on every run.
  That is thousands of guaranteed-wasted requests per day, per tenant.
- **sec-forbes has no request ledger**, so half of the consumption is invisible (RISK 5).

**Required:** implement the quarantine writer (the table and both read paths already exist,
only the writer is missing) so a post proven gone is never re-queued.

## RISK 5 — sec-forbes is invisible (MEDIUM)

`forbes_app_sec` has only `endorse_refresh_queue` and `endorse_refresh_queue_attempts`. There is
**no `endorse_refresh_rate_tokens`**, which means:

- no per-request ledger → no way to measure sec-forbes' provider latency, success rate or
  request count, which is exactly the data that diagnosed the 21:03 collapse on forbes;
- `reserve()` fails **closed**, so a worker started there today would deny every request and
  appear to be doing nothing rather than crashing — the hardest kind of failure to spot.

During a shared-key incident we would be blind to half the load.

**Required:** run the endorse-refresh migrations against `forbes_app_sec` and verify the ten
ledger columns before any worker starts there.

## RISK 6 — the safe concurrency ceiling is still unknown (MEDIUM)

Two data points only:

```
concurrency 30  → latensi 5–11 s, 130–250/menit, KOLAPS setelah ~25 menit
concurrency  5  → latensi ~3,3 s, ~60/menit, stabil 93–96 %
```

Everything between 6 and 29 is unmeasured. RISK 1's fix may require raising it, and there is no
evidence yet for where the cliff is. Stepping up must be done one increment at a time with the
watchdog armed, watching **success rate** — the collapse signal — rather than latency, which is
high (~3,3 s) even when perfectly healthy.

## RISK 7 — the watchdog stops the worker and never restarts it (MEDIUM)

`worker_watchdog.sh` scales the service to 0 on collapse and exits. That is right during an
incident, but under the target schedule it means a provider hiccup at 01:00 silently ends the
night's run: nothing restarts it, and 04:00 arrives with forbes' work unfinished and
sec-forbes starting on top of a backlog.

**Required:** the breaker should back off and retry, not terminate. Ideally it belongs inside
the worker, where it can pause and resume rather than kill the process.

## RISK 8 — sec-forbes' cron must stand down (MEDIUM)

sec-forbes currently drains via cron **three times per minute** (offsets 18 s / 38 s / 58 s),
plus an enqueue-all at 11:30. If a worker is added without setting
`ENDORSE_REFRESH_DRIVER=php_worker` there, cron and worker both drain and both spend the shared
provider budget — and the cron path spends it *outside* any reservation accounting, so the rate
limiter cannot even see it.

Note this also means the 11:30 enqueue-all does not match the proposed 04:00 / 16:00 windows and
needs revisiting.

## RISK 9 — the secondary repository does not contain the worker (HIGH, but purely mechanical)

`secondary-forbes-skin-app` is a **separate repository**. Missing: `EndorseRefreshRateLimiter`,
`EndorseRefreshClaimRepository`, `EndorseRefreshWorker`, `EndorseRefreshPipeline`,
`EndorseRefreshFetchKit`, `EndorseRefreshLedger`, `EndorseRefreshLoadTestGuard`, and **every
endorse-refresh migration**.

Its `EndorseRefreshQueueService` also predates this work — no `applyResults($opts)`, no
`recoveryIsDue()`, no `driverOwnsDraining()`, no retry demotion. The two copies have diverged,
so this is a reconciliation, not a file copy.

## RISK 10 — shared MySQL is already the busiest tenant (LOW, watch)

```
mysql-8_mysql   142 % CPU, 3,37 GB, tanpa limit
node            3 vCPU, 7,9 GB (tersisa ~3 GB)
```

Both tenants' queues, attempts and ledgers live in this one instance. Two workers plus two cron
paths add claim, apply and ledger writes on top of a baseline that already uses ~47 % of the
node's CPU. Not blocking today — the worker itself measured 3,5 % CPU — but it is the shared
resource most likely to bite as concurrency rises.

## RISK 11 — timezone ambiguity in scheduling (LOW, but easy to get wrong)

The host runs **UTC**; MySQL returns **WIB (UTC+7)**. Every timestamp in this investigation had
to be reconciled between the two, and one earlier analysis was wrong for exactly this reason.
"00:05" and "04:00" must be written into crontabs with the zone stated explicitly, and any
monitoring query comparing `NOW()` to a wall-clock window must be checked against the same zone.

---

## Required work, in dependency order

| # | Item | Blocks | Effort |
|---|---|---|---|
| 1 | Cross-tenant run lock keyed on API-key fingerprint (RISK 2) | everything | small |
| 2 | Migrations + ledger verification on `forbes_app_sec` (RISK 5) | sec worker | small |
| 3 | Port the worker subsystem into the secondary repo, reconcile the queue service (RISK 9) | sec worker | medium |
| 4 | Quarantine writer so dead posts stop being re-queued (RISK 4) | quota headroom | small |
| 5 | Circuit breaker that resumes instead of terminating (RISK 7) | unattended runs | medium |
| 6 | Decide scheduled-vs-continuous and align the crons (RISK 3, 8) | schedule correctness | small |
| 7 | Route `/video/` to the scrape (RISK 1, 4) | fitting the window | small |
| 8 | Step concurrency up to find the safe ceiling (RISK 6) | fitting the window | measurement |

Items 1, 2, 4, 6 and 7 are each small and independently useful. **Item 1 is the one that makes
the shared key safe**, and it should land before sec-forbes runs a worker at all — with it in
place, a schedule overrun degrades into "the second tenant waits" instead of "both tenants
collapse the provider".
