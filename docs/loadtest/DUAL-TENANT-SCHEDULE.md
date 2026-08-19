# Two tenants, one RapidAPI key — risk map and required work

Target schedule (**all times WIB** — see RISK 11; the host timezone is being set to
`Asia/Jakarta` so crontab times and these are the same number):

| Waktu | Tenant | Beban |
|---|---|---|
| 00:05 | forbes | all-refresh-sync, **16.283** post Tiktok (16.430 total) |
| 05:00 | sec-forbes | all sync, **12.548** post (semuanya Tiktok) |
| 16:00 | sec-forbes | all sync, 12.548 post |

Corpus figures corrected 2026-08-20: forbes was quoted as 24.551 (wrong filter, see RISK 1) and
sec as 12.530. Both are measured against the actual `enqueueAllActive` predicate.

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

**Implemented 2026-08-20** as `EndorseRefreshWorker::acquireProviderLease`. MySQL `GET_LOCK` is
per-**server**, not per-database; both tenants share one MySQL instance, so one named lock gives
real mutual exclusion regardless of schedule drift.

```
lock name : endorse-provider:<md5-8 of RAPIDAPI_KEY>
acquire   : GET_LOCK(name, 0) before each claim, only when there is work
denied    : log provider_busy, return an empty claim, retry in ~1s
release   : on an empty claim (this tenant is drained) and on shutdown
enable    : ENDORSE_REFRESH_PROVIDER_LOCK=1  — required on BOTH workers
```

Two details that differ from the original sketch here, and both matter:

- **It does not exit on contention.** The sketch said "exit 0, let Swarm retry later". That is
  wrong for a continuous service: Swarm restarts are not a retry mechanism, and an exited
  worker would never come back once the other tenant finished. It stays up and polls.
- **It releases on an empty queue, not at process exit.** Holding until exit would mean
  whichever tenant started first owned the provider all day. Handing the lease back when there
  is nothing left is what makes the two take turns with no clock involved.

Keying on the **API key fingerprint** is the load-bearing detail: tenants that share a key
exclude each other, tenants with separate keys run in parallel automatically, and the behaviour
stays correct if a third tenant appears or the keys are split later.

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

## RISK 8 — sec-forbes' cron must stand down, and the usual switch will not work (MEDIUM)

sec-forbes drains via cron **three times per minute** (offsets 18 s / 38 s / 58 s), plus an
enqueue-all at 11:30. If a worker is added while those keep running, cron and worker both drain
and both spend the shared provider budget — and the cron path spends it *outside* any
reservation accounting, so the rate limiter cannot even see it.

**Correction 2026-08-20: setting `ENDORSE_REFRESH_DRIVER=php_worker` there does NOT stand it
down.** sec's gate is `if ($driver === 'rust' && !$force)` (`Api_v2.php:277`) — the widening to
`php_worker` landed in forbes' repo only, and sec is a separate repository. A `php_worker` value
falls straight through the gate and the cron drains as before. **Comment the three crontab lines
out instead**, which is the agreed approach anyway.

Note this also means the 11:30 enqueue-all does not match the proposed 05:00 / 16:00 windows and
needs revisiting.

### Interim: sec's cron was throttled, not broken

Until the worker lands, sec's throughput is a `.env` question, not an architecture one. Its
`.env` carried exactly one endorse-refresh key, `ENDORSE_REFRESH_BATCH_SIZE=10`, giving 10–30
attempts/min at ~95 % success — about **21 hours** for one 12.548-post pass. sec's own code
already allows batch 50 / concurrency 20 (`Api_v2.php:287-292`), `DAILY_CAP` defaults to off and
`RATE_PER_MIN` to 250, so nothing in the code was the ceiling.

Raised to `BATCH_SIZE=30`, `PARALLEL_HTTP=12`, `RATE_PER_MIN=150` — short of the code's maximum
on purpose, because forbes collapsed this provider at 30 concurrent and everything between 6 and
29 is unmeasured (RISK 6).

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

## RISK 11 — timezone: the host is not UTC, and cron cannot be told a zone (MEDIUM)

**Corrected 2026-08-20. The earlier version of this section said the host runs UTC. It does
not.**

```
Local time  : Wed 2026-08-19 20:41 CEST
Time zone   : Europe/Berlin (CEST, +0200)
MySQL       : WIB (UTC+7)
cron        : Ubuntu vixie-cron 3.0pl1 — CRON_TZ absent from /usr/sbin/cron (verified)
```

Three consequences:

1. Cron fires in **Berlin local time**, so "05:00 WIB" would have to be written `0 0 * * *`.
2. **`CRON_TZ` is not supported by this build**, so the zone cannot be declared per-crontab —
   the trick that usually solves this is unavailable here.
3. Berlin observes DST. At the changeover in late October every absolute-time entry silently
   moves by an hour relative to WIB, and again in March.

**Decision: set the host timezone to `Asia/Jakarta`.** Crontab times then equal WIB directly,
Jakarta has no DST so the schedule is stable forever, and cron finally agrees with MySQL —
which removes the reconciliation that has already produced one wrong analysis in this
investigation. Blast radius is small: only six entries use an absolute hour (crontab lines 97,
98, 99, 104, 105, 106); everything else is `* * * * *` or `*/N`.

Monitoring queries are unaffected — they compare `NOW()` inside MySQL, which was already WIB.

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
