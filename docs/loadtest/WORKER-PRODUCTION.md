# Endorse-refresh PHP worker — production operations

State as deployed on 2026-08-19. Written so someone who was not present can operate, diagnose
and roll back this worker without reading the pipeline internals.

---

## 1. What is deployed

| Component | Value |
|---|---|
| Swarm service | `forbes_endorse-refresh-php-worker` |
| Image | same as `forbes_app` (`gilangp/forbes-app:develop…`) — only the command differs |
| Command | `php -d memory_limit=512M index.php EndorseRefreshWorker run` |
| Replicas | 1 |
| Limits | 0.5 CPU, 512 MB |
| Restart | `condition: any` — the worker exits on purpose every hour, Swarm must restart it |
| Healthcheck | age of PID 1 < 3900s (see §4) |
| Networks | **none** (deliberate — see §5) |
| Mounts | `/home/forbes/artifact/forbes-skin/.env` → `/var/www/html/.env:ro` |

The service was created with `docker service create`, **not** through the Ansible/compose
deploy. A future `docker stack deploy` will not know about it. Move this into the deploy
config to make it permanent.

## 2. Configuration

All worker settings live in the mounted `.env`. Swarm `environment:` entries are **ignored**,
because `env()` prefers the `.env` file at FCPATH over `getenv()`.

```
ENDORSE_REFRESH_WORKER_MODE=production
ENDORSE_REFRESH_DRIVER=php_worker          # cron stands down; guard REQUIRES this
ENDORSE_REFRESH_WORKER_REPLICAS=1          # pacing divides the fleet budget by this
ENDORSE_REFRESH_LEG_ORDER=rapidapi,direct_scrape
ENDORSE_REFRESH_MAX_LEGS=1                 # RapidAPI only
ENDORSE_REFRESH_MAX_IN_FLIGHT=5            # see §3 — this is the critical number
ENDORSE_REFRESH_RAPIDAPI_RATE_PER_MIN=240
ENDORSE_REFRESH_DIRECT_RATE_PER_MIN=60     # must be > 0 or the guard refuses to boot
ENDORSE_REFRESH_RAPIDAPI_TIMEOUT=8
ENDORSE_REFRESH_RETRY_PRIORITY_DEMOTION=1
ENDORSE_REFRESH_TOKEN_RETENTION_SEC=3600
ENDORSE_REFRESH_MAX_RUNTIME_SEC=3600
ENDORSE_REFRESH_DRAIN_SEC=30
ENDORSE_REFRESH_RATE_PER_MIN=120           # cron-only cap; kept non-zero so ROLLBACK is safe
```

**Editing `.env`: use `cat > .env`, never `sed -i`.** `sed -i` writes a new file and renames
it, which breaks the Docker bind mount — the host file changes while the container keeps
reading the old inode. This cost real debugging time.

**`ENDORSE_REFRESH_RATE_PER_MIN=0` means NO CAP, not "off".** Leave it at 120 so a rollback to
`DRIVER=cron` still has a brake.

## 3. Measured behaviour, and the number that matters

Little's Law governs this worker: `throughput = concurrency ÷ latency`.

| Concurrency | Provider latency | Throughput | Outcome |
|---|---|---|---|
| 30 | 5–11 s, climbing | 130–250/min | **collapsed after ~25 min**, 0% success for 14 min |
| 5 | ~3.3 s, flat | ~60/min | stable, 93–96% success |

RapidAPI answers in **0.3 s when idle** and **~3.3 s under our load**. The load test that
produced the "440/min" figure ran against a mock that answered in ~1 s and never degraded, so
it could not express provider degradation. `MAX_IN_FLIGHT=30` was derived from that mock and
was roughly 6× too high for the real provider.

**400/min is not reachable through this single RapidAPI plan.** At 3.3 s latency it would need
~23 concurrent requests, and 30 already collapsed. Options, in order of cost:

1. Route `/video/` back to the scrape (35 % of the corpus, free, ~1 s, verified 12/12 alive).
   `LEG_ORDER=direct_scrape,rapidapi` + `MAX_LEGS=2`. Cuts RapidAPI load by a third.
2. Upgrade the RapidAPI plan — the binding limit is concurrency, which is a plan property.
3. Accept a longer window: ~60/min clears 50 k in roughly 14 hours.

## 4. Healthcheck — why the image's own check had to be replaced

The app image's HEALTHCHECK curls an HTTP endpoint. The worker serves no HTTP, so that check
can only ever report unhealthy, and Swarm killed a perfectly healthy worker with `exit 137`.

The replacement measures something the worker actually guarantees: a healthy worker **always
exits by itself** after `MAX_RUNTIME_SEC`. So if PID 1 has been alive past 3900 s it is hung.

```
--health-cmd 'test "$(ps -o etimes= -p 1 | tr -d " ")" -lt 3900'
--health-interval 60s --health-timeout 5s --health-retries 3 --health-start-period 90s
```

Catches: a hung process. Does **not** catch: a live worker making no progress. For that, watch
completions per minute (§6).

## 5. Three deployment traps already hit

1. **`host.docker.internal` did not resolve** → worker could not reach MySQL. `forbes_app`
   declares `extra_hosts: host.docker.internal:host-gateway`; the new service did not.
   Fixed with `--host-add host.docker.internal:host-gateway`.
2. **The `doc-net-app` overlay broke that resolution intermittently.** The worker needs no
   overlay — MySQL is reached through the host gateway and RapidAPI through the internet — so
   the network was removed entirely.
3. **The image healthcheck killed it** (§4).

## 6. Monitoring

Per-leg health, from the request ledger (this table is the reason the ledger migration exists):

```sql
SELECT DATE_FORMAT(created_at,'%H:%i') AS minute, COUNT(*) AS req, SUM(ok=1) AS ok,
       ROUND(SUM(ok=1)/COUNT(*)*100) AS pct, ROUND(AVG(total_time_ms)) AS avg_ms
  FROM endorse_refresh_rate_tokens
 WHERE created_at >= NOW() - INTERVAL 15 MINUTE
 GROUP BY minute ORDER BY minute;
```

Interpretation, calibrated against the 2026-08-19 incident:

| Success | Latency | Meaning |
|---|---|---|
| 84–100 % | ~3.3 s | healthy — this provider is simply slow under load |
| 43–99 % | 5–11 s | degrading, will collapse if pushed |
| 0 % | 12 s (= timeout) | collapsed; stop the worker so it can recover |

Failure classes worth knowing: `infra_stall` is a timeout (`cURL#28`, `http=0`); `permanent`
means the post is genuinely gone and is failed on the first attempt without retrying.

## 7. Temporary circuit breaker

`worker_watchdog.sh` on the server polls every 60 s and scales the service to 0 when, over a
2-minute window of ≥30 requests, success drops below 60 % **or** average latency exceeds
7000 ms (the timeout is 8000 ms).

The first version used a 3000 ms latency threshold taken from the *idle* measurement and
stopped a healthy worker at 91 % success. The lesson is in the thresholds above: for this
provider the collapse signal is **success rate**, not latency.

This is a stopgap. The worker has no circuit breaker of its own, and during the incident it
kept firing ~130 requests/min at a dead provider for 14 minutes.

## 8. Rollback

```bash
# .env — edit with cat >, not sed -i
ENDORSE_REFRESH_DRIVER=cron

docker service scale forbes_endorse-refresh-php-worker=0
```

The cron resumes on the next minute tick. Scaling to 0 sends SIGTERM: the worker stops
claiming, releases unstarted claims **without** consuming a retry attempt, lets in-flight
requests finish, then exits. Rows genuinely mid-request are recovered by lease expiry —
verified in graceful, double-SIGTERM and SIGKILL modes.

## 9. Start-up order (must not be reordered)

```bash
# 1. migration FIRST — the limiter fails CLOSED without the ledger columns
docker exec <app> php /var/www/html/migrations/run.php --pending

# 2. verify 10 columns; if not 10, STOP
SELECT COUNT(*) FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='endorse_refresh_rate_tokens'
   AND COLUMN_NAME IN ('leg','ok','http_code','curl_errno','total_time_ms',
                       'error_class','retry_after_sec','worker_id','test_run_id','finished_at');

# 3. prove isolation without processing anything
docker exec <app> php index.php EndorseRefreshWorker check     # expect: "isolation proven"

# 4. only then
docker service scale forbes_endorse-refresh-php-worker=1
```

In practice step 1 happens on its own: `docker-entrypoint.sh` runs pending migrations on every
container start. Verify anyway — it is non-blocking and logs a warning on failure rather than
refusing to start.
