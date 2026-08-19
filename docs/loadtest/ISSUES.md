# Issues found — endorse-refresh 400/min effort

Every problem encountered, the phase it surfaced in, how it was diagnosed, and how it was
fixed. Ordered by discovery.

Severity: **CRITICAL** (would corrupt production or void results) · **HIGH** (blocks the
target) · **MEDIUM** (correctness-adjacent or wasteful) · **LOW** (harness only).

Status: **FIXED** · **OPEN** · **BY DESIGN**

| # | Phase | Severity | Title | Status |
|---|---|---|---|---|
| 1 | 1 | CRITICAL | Load-test network could reach production MySQL | FIXED |
| 2 | 1 | CRITICAL | Isolation guard ran after the database connection | FIXED |
| 3 | 2 | CRITICAL | Ledger migration unapplied would disable the rate limiter | FIXED |
| 4 | 7 | HIGH | Short runs report ~2× the sustainable throughput | FIXED |
| 5 | 7 | MEDIUM | Report failed on correct behaviour | FIXED |
| 6 | 4 | MEDIUM | Reservation denials cost ~3,600 wasted queries | FIXED |
| 7 | 4 | HIGH | Worker over-claimed 4:1 against the rate budget | FIXED |
| 8 | 4 | HIGH | Rolling-window limiter produced a throughput sawtooth | FIXED |
| 9 | 3 | HIGH | RapidAPI fallback leg was silently never exercised | FIXED |
| 10 | 1 | MEDIUM | `internal: true` blocks published ports | FIXED |
| 11 | 7 | LOW | Editing a running bash script corrupted a run | FIXED |
| 12 | 7 | LOW | `--scale` with unrelated service names aborted the run | FIXED |
| 13 | 1 | LOW | Base image has no arm64 manifest | FIXED |
| 14 | 7 | MEDIUM | Misdiagnosed correct rate limiting as starvation | FIXED |
| 15 | 4 | CRITICAL | Pacing treated as backpressure deadlocked the worker | FIXED |
| 16 | 4 | HIGH | Pacing was per-worker, so N replicas emitted N× the smooth rate | FIXED |
| 17 | 4 | HIGH | Ready buffer sized from concurrency, not from the start rate | FIXED |
| 18 | 7 | MEDIUM | Ran the capacity scenarios above the approved local budget | FIXED |

---

## Issue 1 — Load-test network could reach production MySQL {#issue-1}

**Phase 1 · CRITICAL · FIXED**

The very first run of `verify-isolation.sh` failed its own check:

```
FAIL  production MySQL is REACHABLE from the worker network
```

**Cause.** A default Docker bridge network NATs outbound traffic. Isolation was resting
entirely on configuration — the guard would refuse a production hostname — but nothing stopped
a worker from opening a socket to `217.217.253.76:3306` if that config were ever wrong.

**Fix.** Made isolation a property of the network rather than a promise made by config:
`loadtest-net` is now `internal: true`, which removes the NAT gateway so containers have **no
route off the host at all**. Mock scenarios need no egress — the provider mock is on the same
network. Staged real-provider runs add egress back through a **separate compose file that must
be named on the command line** (`docker-compose.loadtest.real.yml`), so nobody enables it by
accident.

Added a second, stronger assertion: mock-mode runs must prove there is **no route out at all**,
not merely that one host is unreachable.

```
PASS  production MySQL is not reachable from the worker network
PASS  the worker network has no internet egress (mock mode)
```

**Lesson.** "We could not find a way out" is not the same as "there is no way out."

---

## Issue 2 — Isolation guard ran after the database connection {#issue-2}

**Phase 1 · CRITICAL · FIXED**

`EndorseRefreshLoadTestGuard` was called at the top of the controller's `run()` method, and its
docblock claimed it ran "before the database is touched". It did not.

**Cause.** `application/config/autoload.php:61` autoloads the `database` library, so
`CI_Controller::__construct()` opens a connection to whatever `DB_HOSTNAME`/`DB_DATABASE`
happen to be configured — before any method body executes. Discovered when a container run
failed with a CI database error at the `parent::__construct()` line rather than a guard message.

**Fix.** Moved the check into the controller constructor **before** `parent::__construct()`,
where only `env()` and the guard itself are required. `check()` became trivial: reaching it at
all proves isolation.

**Verification.** Pointing the container at `.env.example` now refuses with 9 violation codes
and never opens a connection:

```
REFUSING TO START: production isolation is not proven.
  [environment_not_loadtest] CI_ENV must be one of: testing
  [loadtest_marker_missing] ENDORSE_REFRESH_LOADTEST must be explicitly set to 1/true
  [db_name_not_disposable] DB_DATABASE must end with "_loadtest"
  ...
```

**Lesson.** A guard's position in the file is part of its contract. Verify the ordering, don't
assume it.

---

## Issue 3 — Ledger migration unapplied would disable the rate limiter {#issue-3}

**Phase 2 · CRITICAL · FIXED**

Caught by the **existing** integration suite, which is exactly its job:

```
EndorseRefreshDrainE2E ✘ Multi chunk drain completes all successes
   PDOException: Column not found: 1054 Unknown column 'leg' in 'field list'
   .../EndorseRefreshRateLimiter.php:131
```

**Cause.** `reserveToken()` unconditionally INSERTs the new ledger columns. `docker-entrypoint.sh:17`
runs `php migrations/run.php --pending` under `timeout 120` and **deliberately does not fail
the container** when it errors — so the application genuinely can start against a schema that
predates the ALTER. Every `reserve()` would then throw, and because the limiter **fails closed**,
the result is a *total denial of provider fallback requests*: strictly worse than the status quo.

**Fix.** Both stores probe for the column once per process and fall back to the original
five-column INSERT when absent. One extra query per process; the ledger degrades, the limiter
keeps working. Also fixed the drain suite to build the current schema.

**Lesson.** A schema change that a fail-closed component depends on is a deploy-ordering
hazard, not just a migration.

---

## Issue 4 — Short runs report ~2× the sustainable throughput {#issue-4}

**Phase 7 · HIGH · FIXED**

The first smoke run reported **958 completions/min** against a 480/min budget.

**Cause.** The limiter counts starts in a rolling 60s window. A 30-second run legitimately
spends a *full minute's* budget, so dividing by elapsed time doubles the apparent rate. Nothing
was broken — the measurement was.

**Fix.** `report.php` computes throughput from per-completion timestamps as the **worst rolling
60s** and the sustained 300s window, and explicitly labels runs shorter than ~2 minutes:

```
!! RUN TOO SHORT for a trustworthy sustained rate (rolling 60s limiter window not exercised)
```

**Lesson.** Getting a flattering number early is a reason to distrust the instrument.

---

## Issue 5 — Report failed on correct behaviour {#issue-5}

**Phase 7 · MEDIUM · FIXED**

The first report showed `overall: FAIL` on two invariants that were the seeded pathological
fixtures behaving **exactly as designed**: a pre-seeded completed row with no business write,
and an inconsistent row correctly quarantined by `resetStuck` as `needs_reconciliation`.

**Fix.** Split into **VIOLATIONS** (must be zero) and **OBSERVATIONS** (expected outcomes,
reported because their *absence* would be as suspicious as a wrong value — a run where the
poison row was never isolated means the poison path was never exercised). Restricted
`completed_without_business_write` to rows this run actually processed.

**Lesson.** A report that cries wolf on correct behaviour trains you to ignore it.

---

## Issue 6 — Reservation denials cost ~3,600 wasted queries {#issue-6}

**Phase 4 · MEDIUM · FIXED**

First smoke run: **1,209 reservation denials in 30 seconds**. Each denial costs a `GET_LOCK` +
`COUNT` + `RELEASE_LOCK` — ~3,600 queries competing with the very requests they were waiting for.

**Cause.** On denial the pipeline backed off *claiming* but retried the *reservation* on the
very next loop iteration, ~100×/second.

**Fix.** Added `denied_backoff_sec` (50 ms): after a denial, stop attempting reservations
briefly. Denials fell **1,209 → 268 (−78%)** with identical throughput (480 completions both
runs).

---

## Issue 7 — Worker over-claimed 4:1 against the rate budget {#issue-7}

**Phase 4 · HIGH · FIXED**

15-minute run, 2 replicas × 30 in-flight, 600/min budget:

```
attempt.cancelled  22122
attempt.completed   5382
```

**4.1 wasted transactions per useful one** — the same over-claiming pathology this project set
out to remove from the cron, reappearing in a new form.

**Cause.** Claim capacity was computed from **slot count**. Slots say how much can be *in
flight*; the rate budget says how much can be *started*. At 60 slots against 600/min those
disagree by ~5×, so the worker leased rows it could not start, released them after 2s, and
re-claimed them.

**Fix.** Claim capacity now tracks the budget adaptively — multiplicative decrease on denial,
additive recovery on success, floored at 10% — mirroring the brownout backoff already in
`Template::get_social_media_batch`. Exposed as `claim_capacity_factor` in the run totals; far
below 1.0 means concurrency is over-provisioned relative to the budget.

Pinned by `tests/Unit/EndorseRefreshPipelineTest.php`, which runs with **no network and no
database** because a `reserve` that always denies never creates a handle.

---

## Issue 8 — Rolling-window limiter produced a throughput sawtooth {#issue-8}

**Phase 4 · HIGH · FIXED**

The 15-minute run averaged 340 completions/min but oscillated between **109 and 389/min**
across rolling 60s windows. Completions would freeze for 40–50s, then jump ~250.

**Cause.** The shared limiter bounds the *rate* but says nothing about its *shape*. The worker
could burst faster than the sustained rate, so it spent the whole minute's budget in the first
~20 seconds and then blocked until the window aged out. Confirmed directly from the instrumented
denial reason:

```
{"evt":"endorse_refresh_reserve_denied","scope":"direct_scrape",
 "limit":400,"used_in_window":400,"reason":"window_full"}
```

A burst is also worse than it looks upstream: the provider sees 400 requests in 20 seconds, and
rate-limit enforcement is usually shaped rather than averaged. The brief's "local smooth rate:
10 request starts per second, burst maximum 20" turned out to be load-bearing, not decorative.

**Fix.** Added per-scope **token-bucket pacing** in the pipeline: refill at `limit/60` per
second, capped at `pace_burst` (default 20). Paced-out starts cost nothing — they never reach
the database. The shared DB limiter remains the distributed hard backstop, so pacing can only
decide *when* to ask, never admit more than the shared budget allows.

**Verification.** In flight at time of writing.

---

## Issue 9 — RapidAPI fallback leg was silently never exercised {#issue-9}

**Phase 3 · HIGH · FIXED**

Scenario B showed `rapidapi n=3, ok=0, error_class=config` — every fallback attempt failed
before a socket was opened, while the run still reported healthy throughput and a flattering
amplification of 1.002.

**Cause.** `.env.loadtest.example` shipped `RAPIDAPI_KEY=` (empty), and
`Template::rapidApiConfigError()` returns a `config`-class error whenever host **or** key is
empty. The entire fallback path — and therefore the whole amplification question the ≤1.5
target rests on — was untested.

**Fix.** The example now ships a placeholder key with a comment explaining why a non-empty
value is required even for mock runs. Scenario C then produced real two-leg data: `pA = 0.768`,
conditional rescue `0.351`, amplification **1.386**.

**Lesson.** A configuration default that makes a code path fail *early and quietly* is more
dangerous than one that makes it fail loudly.

---

## Issue 10 — `internal: true` blocks published ports {#issue-10}

**Phase 1 · MEDIUM · FIXED**

After fixing Issue 1, the host could no longer reach MySQL on `:33071` or the mock control
plane on `:18080` — Docker does not set up port publishing on an internal network.

**Fix.** `loadtest-mysql` and `provider-mock` additionally join a host-facing
`loadtest-control` network. **The worker deliberately does not** — it is the process that
spends provider quota and claims queue rows, so it stays air-gapped. The isolation check was
tightened to inspect the *worker's* own network attachment rather than the stack's.

---

## Issue 11 — Editing a running bash script corrupted a run {#issue-11}

**Phase 7 · LOW · FIXED**

A 15-minute run ended with `run.sh: line 99: WHERE: command not found`, losing its report step.

**Cause.** Self-inflicted: `run.sh` was edited *while executing*. Bash reads scripts
incrementally by byte offset, so the edit shifted the file and execution resumed mid-SQL.

**Fix.** Regenerated the report from the database afterwards (the measurement data was intact)
and adopted the rule: never edit a script while a run is using it.

---

## Issue 12 — `--scale` with unrelated service names aborted the run {#issue-12}

**Phase 7 · LOW · FIXED**

The first scenario-A attempt exited silently after seeding; no worker ever started.

**Cause.** `docker compose up -d --scale endorse-worker=N --no-recreate loadtest-mysql provider-mock`
errors because the scaled service is not among the named services. With `set -e` in `lib.sh`,
the script exited before a single request was made — and stderr was redirected to `/dev/null`,
so the failure was invisible.

**Fix.** Removed the redundant line; infrastructure is already up from `up.sh`. Named only
`endorse-worker` on the scale command.

---

## Issue 13 — Base image has no arm64 manifest {#issue-13}

**Phase 1 · LOW · FIXED**

```
gilangp/forbes-base:latest: no match for platform in manifest
```

**Cause.** Production is x86_64; the development host is Apple Silicon.

**Fix.** Built the same base natively from the repo's own `Dockerfile.base` (PHP 8.4.24, with
`pcntl` and `curl` confirmed present). Emulating x86 via QEMU would have distorted every CPU
measurement by ~5–10×, making the throughput numbers meaningless. The architecture difference
is recorded as a **known local-vs-production delta** for the canary plan.

---

## Issue 14 — Misdiagnosed correct rate limiting as starvation {#issue-14}

**Phase 7 · MEDIUM · FIXED**

Observed zero requests in 60 seconds and concluded the adaptive claim-capacity change (Issue 7)
had over-corrected into a self-reinforcing starvation deadlock. It had not.

**Cause of the misdiagnosis.** The `reservation_denied` log line recorded only *that* a denial
happened, not *why*. A correctly throttled worker and a starved one looked identical. Three very
different causes — window full (budget working), limit ≤ 0 (misconfiguration that will never
resolve), lock unavailable (replica contention) — demand opposite responses.

**Fix.** The reserve collaborator now logs the reason, the limit and the observed window usage.
That immediately showed `reason: window_full, used_in_window: 400/400` — the limiter working
exactly as designed — and redirected the investigation to the real defect, Issue 8.

**Lesson.** Instrument the *reason*, not just the event. The cost of the missing field was
roughly an hour of investigating a non-existent bug.

---

## Issue 15 — Pacing treated as backpressure deadlocked the worker {#issue-15}

**Phase 4 · CRITICAL · FIXED**

After adding the pacer (Issue 8), completions froze at 377 and stayed there. Two workers sat
**completely idle for 159 seconds** while 12,000 rows were pending and claimable.

**Diagnosis.** The evidence was contradictory at first: the worker logged
`reason: window_full, used_in_window: 400`, yet querying the ledger from the host showed **zero
tokens in the last 60 seconds**. The reconciling fact came from MySQL itself:

```
ID    TIME  COMMAND  STATE
4800  47    Sleep
4801  47    Sleep
```

Both worker connections had been **idle for 47 seconds**. The workers were not querying the
database at all — so the `window_full` lines were stale, left over from the initial burst. The
denials being logged were coming from the *pacer*, which never touches the database.

All 400 direct tokens were written in a 36-second burst and then nothing for 159 seconds.

**Two compounding causes:**

1. **Pace denials halved the claim-capacity factor.** `takePaceToken()` returning false
   produced the same `DENIED` code as a shared-budget refusal, and `fillSlots()` halves the
   factor on `DENIED`. But pacing says "not yet" many times per second *by design* — that is
   what a smooth-rate shaper does. The factor collapsed to its 0.1 floor within seconds.

2. **The capacity floor fell below `claim_min_batch`.** With `max_in_flight=30` and
   `ready_low_water=5`, nominal is 35, so at factor 0.1 the claim target was
   `ceil(35 × 0.1) = 4`. `ENDORSE_REFRESH_CLAIM_MIN_BATCH=5`. The guard
   `if ($capacity < $minBatch) return;` therefore matched on **every** iteration, forever. The
   `ready` queue drained, slots drained, and nothing ever refilled them.

A self-reinforcing deadlock: recovery of the factor required a successful start, and starts
required claiming, and claiming was blocked by the factor.

**Fix.**
- Added a distinct `PACED` return code. It backs off filling by one smooth-rate tick (20 ms)
  and **does not touch** `claimCapacityFactor`. Only a genuine shared-budget `DENIED` reduces it.
- The claim target is now `max(claim_min_batch, ceil(nominal × factor))`, so the threshold is
  always reachable.

**Regression tests** (`tests/Unit/EndorseRefreshPipelineTest.php`):
- `testWorkerKeepsClaimingWhenCapacityCollapsesBelowMinBatch` — starts the factor at the floor
  and asserts claiming continues at ≥ `claim_min_batch`.
- `testPacingDoesNotReduceClaimCapacity` — pacing throttles starts while `denied` stays 0 and
  the factor stays 1.0.

**Lesson.** Two conditions that both mean "don't start right now" can require opposite
responses. Collapsing them into one code lost the distinction between *wait a moment* and
*claim less work*, and the interaction with an unrelated minimum produced total deadlock.

---

## Issue 16 — Pacing was per-worker, so N replicas emitted N× the smooth rate {#issue-16}

**Phase 4 · HIGH · FIXED**

With the deadlock (Issue 15) fixed, throughput recovered but the opening burst remained:

```
t=11s  workers=2  completed=141      -> 846/min
t=21s  workers=2  completed=274
t=31s  workers=2  completed=379
t=41s  workers=2  completed=380      <- flat: budget spent, waiting for the window
```

**Cause.** The token bucket is per **process**, but the rate budget is per **fleet**. Each of
the two replicas paced independently at `400/60 = 6.67` starts/second, for a combined 13.3/s —
exactly twice the intended smooth rate. The shared limiter still enforced the 400/min cap
correctly, but it absorbed the excess as a burst, which is the precise shape pacing exists to
remove.

**Fix.** The pace rate and burst are divided by `ENDORSE_REFRESH_WORKER_REPLICAS` (default 1),
which `run.sh` sets from its own `--replicas` argument. Over-stating the replica count is safe
— it merely paces slower than necessary; under-stating it degrades to the pre-pacing burst,
with the shared limiter still enforcing the hard cap. The failure mode is therefore always
"slower", never "over budget".

**Note for the canary plan.** This makes replica count a *rate-relevant* setting, not just a
capacity one. Scaling replicas without updating `ENDORSE_REFRESH_WORKER_REPLICAS` reintroduces
bursting against the provider.

**Measured impact.** A full 10-minute Scenario C run on the pre-fix build
(`reports/20260819122556_C.json`, 2×30 in-flight, 400+200) quantifies the cost. Note that the
*rate cap itself was never breached* — the shared limiter did its job exactly:

```
REQUEST-START RATE   direct 400/min, rapidapi 200/min, combined peak 600/600   PASS
THROUGHPUT           rolling 60s   worst 169   best 380     <- 2.2x sawtooth
                     rolling 300s  worst 284.8 best 375
AMPLIFICATION        1.483 (target <= 1.5)
```

The damage is entirely in *throughput*, not compliance: both workers spent the rolling window
in the first seconds of each minute, then starved. Sustained 284.8/min against a 400/min goal.
This is the clearest evidence in the whole exercise that a correct hard limiter does not make
a pacer optional — the limiter bounds the peak, the pacer is what converts the budget into
*sustained* throughput.

---

## Issue 17 — Ready buffer sized from concurrency, not from the start rate {#issue-17}

**Phase 4 · HIGH · FIXED**

With pacing correct (Issues 8, 15, 16), the first clean Scenario C run met its rate budget and
every correctness invariant — and still burned most of the database's capacity on nothing:

```
queue.completed      4286
attempt.cancelled   11668      <- 2.7 claim/release cycles per completion
mysql                237.6% CPU peak, 191 innodb row lock waits
```

**Cause.** `claimIfCapacity()` sized the claimed-but-unstarted buffer as
`max_in_flight + ready_low_water` (35 at 2×30), but items leave that buffer only as fast as the
pacer permits starts — ~4/second per worker at 480/min across 2 replicas. So the buffer filled
to ~27, each item waited ~7 seconds to start, and `ready_max_age_sec` is **2.0**. Every surplus
row was therefore claimed, aged out, and released, forever.

Concurrency and rate are different quantities, and this is what conflating them costs. Nothing
was incorrect — released claims consume no attempt, so the rows were fine — but the churn was
~2.7× the useful claim traffic.

**Why it mattered more than it looks.** Locally MySQL had cores to spare. Production is a
3-vCPU box where MySQL already holds ~3.3 GB, so at 237% CPU the database, not the rate budget,
would have become the binding constraint — and the load would have been almost entirely waste.

**Fix.** `readyDepthTarget()` sizes the buffer as `first_leg_rate / 60 / replicas × ready_lead_sec`
(lead 1.5s, comfortably under the 2.0s expiry), floored at `claim_min_batch` so the Issue 15
deadlock cannot return. Only the *first* leg is read: a fallback leg starts from a completed
leg-1 handle, never from the buffer, so it does not drain it. Claiming is now bounded by both
the buffer target and overall nominal capacity, whichever is tighter.

**Lesson.** A buffer in front of a rate limiter must be sized in *seconds of that rate*, never
in units of downstream concurrency. The symptom is invisible in every correctness check and
every throughput number — it shows up only as database load with no output attached.

---

## Issue 18 — Ran the capacity scenarios above the approved local budget {#issue-18}

**Phase 7 · MEDIUM · FIXED**

Scenarios A, B and C were run at `480 + 240 = 720` combined request starts/min. The approved
budget sets **720/min as the combined operational safety ceiling** but **600/min as the local
load-test rate limit** — two different numbers, and 720 respects only the looser one.

The run report caught it (`combined peak 692/min ... WARN (over the 600/min local budget)`),
which is the check working as intended, but it should not have been configured that way to
begin with.

**Consequence for the results.** These were mock-provider runs, so no provider quota was spent
and no policy was breached. The effect is on *interpretation*: throughput measured at a 720/min
budget does not demonstrate the target is met at 600/min. At the measured Scenario C
amplification of 1.449, a 600/min budget caps completions at ~414/min — above the 400 target,
but with only 3.5% headroom, so the capacity claim has to be re-established rather than scaled
down from the 720 numbers.

**Fix.** Re-run the capacity scenarios within 600/min. Treat the 600/min figure as the binding
local constraint and 720/min as a ceiling that must never be approached, not a target.

**Lesson.** When a brief gives several rate numbers, they are usually not redundant. Write each
one into the report as a *separate* named check rather than collapsing them into one worst-case
bound.

---

## Issue 19 — Failing high-priority cohorts starve fresh work (priority inversion) {#issue-19}

**Phase 7 · HIGH · FIXED**

Only the 30-minute run could expose this. Throughput held a flat 434–459/min for seventeen
minutes, then:

```
min 17  438      min 20  119
min 18    1      min 21  220
min 19  365      min 22  435   <- back to normal
```

A full minute completing **one** post. Worst rolling 60s = **0**.

**Diagnosis.** Request starts continued at 565–598/min throughout, with normal latency and zero
unfinished requests — so neither the provider nor the apply path had stalled. Per-minute success
showed both legs at `ok=0`. Grouping the ledger by `attempt_no` identified it exactly:

| minute | attempt_no | requests | successes |
|---|---|---|---|
| 18 | 2 | 585 | **0** |
| 20 | 1 / 3 | 148 / 450 | 110 / **0** |
| 21 | 1 / 3 | 293 / 285 | 230 / **0** |

Entire minutes spent on retry cohorts that completed nothing — while **11,366 eligible fresh
rows** sat pending.

**Cause.** `ORDER BY q.priority DESC` is absolute and has no starvation guard. Within one
priority band `attempts ASC` correctly serves fresh rows before retries, but ACROSS bands a
failing high-priority cohort outranks every fresh row beneath it. The corpus spans priorities
10–12 (`10 + id % 3`), so band 12 drained, then band 11 — and when a band's fresh rows were
exhausted, that band's retries outranked all 8,271 fresh rows in band 10.

Those retries could never succeed: the mock keys its RNG on `content_id`, so a post fails
identically on every attempt. **This is not a mock artifact** — a deleted, private or 404 video
fails deterministically in production too.

**Fix, in two parts.**

1. `retry_priority_demotion` (new `claimBatch` option): each consumed attempt drops a row one
   priority band, bounding how long a failing cohort can hold the head of the queue to
   `max_attempts` bands. **Opt-in, default 0**, and at 0 the builder emits the *original SQL
   string* rather than an arithmetically-equivalent one, so the cron path is provably
   unchanged and the existing claim-shape contract test still pins it.
2. Retry jitter is now proportional to the exponential backoff. It was a fixed
   `baseSeconds / 2` window regardless of attempt: at attempt 3 the delay is 240s, so a cohort
   spread over 30s — 12% of its interval — and re-arrived as a single wave.

**Measured effect** (same 30-minute scenario, same budget):

| | before | after |
|---|---|---|
| Worst rolling 60s | **0** | **422** |
| Worst rolling 300s | 225.8 | **438.4** |
| Completions | 12,484 | **13,560** |
| Amplification | 1.405 | **1.288** |
| Terminal failures | 568 | **0** |
| Direct-leg success rate | 0.738 | 0.801 |

The leg success rate rose without the mock changing: the pipeline simply stopped spending
budget re-fetching posts that could not succeed.

**Lesson.** Absolute priority ordering is a liveness hazard whenever failure is correlated with
the thing being prioritised. The bug is invisible in aggregate throughput and in every
correctness invariant — the run before this fix passed all 10 — and only shows up as a
*worst-window* metric. Reporting the worst rolling window rather than the mean is what caught
it.

---

## Open items

None blocking. Remaining work is execution rather than defect resolution:

- Scenarios D, E, F, I; 30-minute run at 12,000+ items.
- Execute all three shutdown modes.
- Leg census against real providers (Stages 1–3) to obtain the true `pA` and conditional `pB`.

### One open question, not a defect

**How much of the 400/min target survives a pessimistic failure profile?** Under mock Scenario
C (20% transient scrape failure, `pA ≈ 0.73`) the 600/min local budget yields roughly 410–450/min
— it clears the target, but the *budget* is the binding constraint, not the pipeline. The
pipeline itself is nowhere near saturated: workers sit at low single-digit CPU and MySQL uses 4
of 300 connections.

That means capacity is governed by `pA` and the budget split, both of which are properties of
the real providers rather than of this code. Until the Phase 2 leg census runs against real
providers, the capacity figure is conditional on the mock's failure profile being no worse than
reality. Stated plainly so it is not mistaken for a proven production number.
