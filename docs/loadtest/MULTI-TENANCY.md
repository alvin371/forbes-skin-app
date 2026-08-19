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

**Options, best first**

1. **Separate RapidAPI keys.** The scope key is a fingerprint of the API key, so distinct keys
   give genuinely independent budgets and independent quota. Cleanest, and the only one that
   scales.
2. **Split one budget by hand.** e.g. 120/min each. Simple, but every future change has to be
   made in two places, and nothing enforces the arithmetic.
3. **Share one rate-limit table.** Point both apps' reservation store at a single database.
   Correct in principle, but couples the two tenants' availability and is the largest change.

Do **not** enable sec-forbes without picking one.

## Blocker 2 — `forbes_app_sec` has no `endorse_refresh_rate_tokens` table

The database has only `endorse_refresh_queue` and `endorse_refresh_queue_attempts`.

`reserve()` fails **closed** on error. A worker started against this database would deny every
single provider request — it would not crash, it would simply do nothing, which is a much
harder failure to diagnose. The ledger migration must run there first, and the ten columns must
be verified exactly as in `WORKER-PRODUCTION.md` §9.

## Blocker 3 — the secondary repository does not contain the worker

`secondary-forbes-skin-app` is a **separate repository**, not a branch. It has
`EndorseRefreshQueueService.php` and `Endorse_sync.php`, but is missing the entire dependency
chain the worker needs:

```
EndorseRefreshRateLimiter.php     (reservation store + scope — absent)
EndorseRefreshClaimRepository.php (claim SQL — absent)
EndorseRefreshWorker.php          (entry point — absent)
EndorseRefreshPipeline.php        (state machine — absent)
EndorseRefreshFetchKit.php        (provider handles — absent)
EndorseRefreshLedger.php          (per-request ledger — absent)
EndorseRefreshLoadTestGuard.php   (boot guard — absent)
```

It also has **no endorse-refresh migrations at all**, which explains blocker 2.

Its `EndorseRefreshQueueService.php` predates this work, so it lacks `applyResults($opts)`,
`recoveryIsDue()`, `driverOwnsDraining()` and the retry-demotion option. Porting is not a
file copy; the two copies of the queue service have diverged and need reconciling.

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

## Order of work, if sec-forbes adoption goes ahead

1. Decide the RapidAPI budget question (blocker 1). Separate keys is the recommendation.
2. Port the endorse-refresh subsystem into `secondary-forbes-skin-app` and reconcile the
   diverged `EndorseRefreshQueueService`.
3. Run the endorse-refresh migrations against `forbes_app_sec`; verify the ten ledger columns.
4. Set distinct `APP_NAME` in both `.env` files.
5. Deploy the worker at **0 replicas**, run `EndorseRefreshWorker check`, and only then scale.
6. Start sec-forbes at a low `MAX_IN_FLIGHT` (5) and watch the shared provider's latency, not
   just its own success rate — degradation caused by one tenant shows up in the other.

Note that sec-forbes currently runs its endorse-refresh cron **three times per minute**
(offsets 18 s / 38 s / 58 s). Those entries must stand down — `ENDORSE_REFRESH_DRIVER=php_worker`
— or cron and worker will both drain and both spend the shared provider budget.
