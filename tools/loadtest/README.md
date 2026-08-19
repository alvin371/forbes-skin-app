# Endorse-refresh load-test harness

Proves — with evidence rather than estimates — whether the endorse-refresh pipeline can
sustain **400 unique valid-post completions per minute** at **≤1.5 provider requests per
completed post**, with zero correctness violations, before any production rollout.

Nothing here touches production. See [Safety](#safety) for how that is enforced rather than
promised.

---

## Why this exists

The production path cannot reach 400/min, and not by a small margin. `Api_v2::cronjob_endorse_refresh`
is capped three independent ways:

| Cap | Value | Where |
|---|---|---|
| ticks per minute | 1 | `deploy/monitoring/forbes-crontab-stability.snippet:3` (`* * * * *` + `flock -n`) |
| HTTP parallelism | **10** | `Api_v2.php:8232` — `boundedWorkerSetting(env(...), 10, 10)` |
| claim per tick | 50 | `Api_v2.php:8240` |

Structural ceiling ≈ 50/min. Production achieves ~5.6/min because of a second, larger defect:
`Template::get_social_media_batch` runs the direct scrape in parallel via `curl_multi`, then
runs the RapidAPI fallback in a **sequential `foreach`** (`Template.php:1690-1747`), plus a
third leg that re-issues a byte-identical copy of the first request. Effective concurrency
collapses toward 1 and ~70% of claimed rows are deferred every tick.

The worker here replaces that fetch loop only. Claiming, fencing, retry policy, terminal
classification, lease recovery and the per-item transaction remain in
`EndorseRefreshQueueService` — reached through injected callables, not reimplemented.

---

## Quick start

```bash
cp .env.loadtest.example .env.loadtest     # git-ignored; holds a real key only for real runs

tools/loadtest/up.sh                       # TLS material, containers, schema, isolation proof
tools/loadtest/seed.sh --count 13500       # corpus + pathological fixtures
tools/loadtest/run.sh --scenario C --duration 900 --replicas 2 --in-flight 30

tools/loadtest/test-shutdown.sh --mode graceful
tools/loadtest/test-shutdown.sh --mode kill

tools/loadtest/down.sh                     # destroys containers, networks, volumes
```

Reports land in `tools/loadtest/reports/<timestamp>_<scenario>.json` plus a human-readable
summary on stdout.

---

## Files

| Path | Purpose |
|---|---|
| `docker-compose.loadtest.yml` | Disposable stack: MySQL 8.0, provider mock, N workers |
| `docker-compose.loadtest.real.yml` | Overlay granting egress, for staged real-provider runs only |
| `.env.loadtest.example` | Template. The compose file mounts `.env.loadtest` at `/var/www/html/.env` |
| `tools/loadtest/up.sh` | Certs → containers → schema → isolation proof |
| `tools/loadtest/seed.sh` / `seed.php` | Corpus with unique content ids + 8 pathological fixtures |
| `tools/loadtest/run.sh` | One scenario end to end: configure, seed, run, stop, report |
| `tools/loadtest/report.php` | Funnel, amplification, latency, rate proof, invariants |
| `tools/loadtest/test-shutdown.sh` | Graceful / hard / SIGKILL crash consistency |
| `tools/loadtest/verify-isolation.sh` | Seven checks against the running stack |
| `tools/loadtest/schema.php` | Builds schema via the integration suite's canonical builder |
| `tools/loadtest/scenarios/*.json` | Provider profiles A–F, I |
| `services/provider-mock/` | Go mock serving both provider legs |

### Application code under test

| Path | Role |
|---|---|
| `application/controllers/EndorseRefreshWorker.php` | CLI entry, wiring, signal handlers |
| `application/libraries/EndorseRefreshPipeline.php` | The continuous `curl_multi` state machine |
| `application/libraries/EndorseRefreshFetchKit.php` | `extends Template` — handle builders + re-exported parsers |
| `application/libraries/EndorseRefreshLedger.php` | Buffered per-request ledger writer |
| `application/libraries/EndorseRefreshLoadTestGuard.php` | Production-isolation guard |

`Template.php` is **not modified**. The fetch kit subclasses it, so a bug in the load-test
worker cannot affect the production cron path.

---

## Safety

Isolation is a property of the system, not a promise in a comment. Seven checks, all run by
`up.sh` before a single row is written:

```
PASS  worker .env is the load-test file (mount took effect)
PASS  database name carries the _loadtest suffix
PASS  EndorseRefreshLoadTestGuard reports isolation proven
PASS  production MySQL is not reachable from the worker network
PASS  the worker network has no internet egress (mock mode)
PASS  worker is attached only to the isolated load-test network
PASS  no mail/webhook/push/Sentry credentials in the worker environment
```

Three layers, each of which alone would be insufficient:

1. **Network.** `loadtest-net` is `internal: true` — no NAT gateway, so containers have no
   route off the host. A worker pointed at a production hostname simply cannot reach it.
   The first version of this harness *failed* this check: a default Docker bridge NATs
   outbound, and the worker genuinely could reach production MySQL.
   `loadtest-mysql` and `provider-mock` additionally join a host-facing `loadtest-control`
   network so their published ports work; **the worker never does**.

2. **Configuration.** `EndorseRefreshLoadTestGuard` refuses to boot unless `CI_ENV=testing`,
   `ENDORSE_REFRESH_LOADTEST=1`, `DB_DATABASE` ends in `_loadtest`, `DB_HOSTNAME` is loopback
   or a container alias, no mail/webhook/push/Sentry credentials are present, and both rate
   limits are positive and plausible. It runs in the controller constructor **before**
   `parent::__construct()`, because `config/autoload.php` autoloads `database` — a guard
   running later would be validating an environment it had already connected to.

3. **Data.** Mock redirection lives in the seeded URLs and in worker-only code, never in
   `Template.php`.

Violations print stable codes and hints, never a hostname, database name or credential.

Egress for staged real-provider runs is a **separate compose file that must be named on the
command line**, so nobody enables it by accident:

```bash
docker compose -p forbes-loadtest \
  -f docker-compose.loadtest.yml -f docker-compose.loadtest.real.yml up -d --scale endorse-worker=N
tools/loadtest/verify-isolation.sh --expect-egress
```

---

## Reading a report

Four things decide whether a run counts.

**Throughput** — the headline is the *worst rolling 60s* and the sustained 300s window, not
the run total. The limiter uses a rolling 60s window, so a short run can legitimately spend a
full minute's budget and report double the sustainable rate. The first smoke run did exactly
that: 480 requests in 30s against a 480/min cap, reported as "958/min". Runs under ~3 minutes
are labelled untrustworthy rather than given a flattering number.

**Amplification** — `requests started / completed posts`, target ≤1.5. Governed by arithmetic,
not tuning:

```
avg_requests_per_completion = (2 - pA) / (pA + (1 - pA) * pB)
```

where `pA` is leg-1 success and `pB` is the **conditional** leg-2 rescue rate given leg-1
failed. The conditional matters: dead, private and removed posts fail *both* legs, so the
marginal fallback success rate overstates its value. The report computes the conditional by
joining legs per `queue_id`. Scrape-first needs `pA ≥ ~0.565` to stay under 1.5.

**Request-start rate** — the true maximum over every rolling 60s window, per scope. This is
the only global brake: the worker passes `rate_per_min=0` / `daily_cap=0` to `claimBatch`
because budget belongs at request grain, not claim grain. `PASS` ≤600/min, `WARN` to 720,
`FAIL` above.

**Ledger reconciliation** — the mock's own request count versus ledger rows. A ledger row is
created at `reserve()`, *before* the handle exists, so ledger ≥ provider always. A negative
delta means requests reached the provider without a token, i.e. the limiter is not actually
bounding outbound traffic, and every number in the report is void.

Then: ten correctness **violations** that must all be zero, and **observations** that report
the seeded pathological fixtures behaving as designed. The split is deliberate — an earlier
version flagged two "failures" that were correct behaviour, and a report that cries wolf
trains you to ignore it.

---

## Scenarios

| | Profile | Proves |
|---|---|---|
| A | 100% success, p50 1s | Worker/DB ceiling with no provider noise |
| B | p50 3s, p95 8s, p99 25s | Continuous refill; no head-of-line blocking |
| C | 20% transient on leg 1 | Amplification and eventual completion |
| D | 429 + `Retry-After` | Distributed limiter, retry scheduling, no storm |
| E | Timeouts past the client deadline | Lease recovery, no silent loss |
| F | `correlation: 1.0` | No unnecessary fallback for permanently-invalid content |
| I | Every failure mode at once | Poison terminal, recovery not starved, healthy rows drain |

Outcomes are seeded per post (`crc32(contentID + seed)`), never by wall clock, so the same
post gets the same outcome on every run and two runs are comparable per `queue_id`.

---

## Tuning

Required concurrency ≈ `6.67 × average end-to-end latency in seconds`. At 3s that is ~20
in-flight; at 8s, ~53.

**Change concurrency and request rate in separate experiments.** Ramp in-flight
`10 → 20 → 40 → 50 → 60 → 70 → 80`, and if completion rate falls when concurrency rises, the
bottleneck moved — find it before continuing.

Every knob comes from `.env.loadtest`. A docker `environment:` entry will **not** work:
`env_helper.php` reads `FCPATH.'.env'` and prefers it over `getenv()`. `run.sh` rewrites the
file for this reason.
