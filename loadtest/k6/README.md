# Forbes Load Test — k6

Full plan + safety + parameters: `docs/2026-06-20-load-test-plan.md`.
**Prod off-hours only (01:00–05:00 Asia/Jakarta). Read-only journeys. Guardrails auto-abort.**

## Install (laptop)
```bash
brew install k6          # macOS
# or: https://grafana.com/docs/k6/latest/set-up/install-k6/
```

## Configure — already done
`loadtest/.env` is **pre-populated** (10 admin accounts loadtest1..10 + CAMPAIGN_IDS).
Just load it:
```bash
set -a; source loadtest/.env; set +a
```
(100 VUs share these 10 accounts, round-robin; each VU keeps its own session cookie.
No need for more accounts.) ⚠️ Delete the accounts + `.env` after testing — see plan doc.

## Run order
Wrap every run in `caffeinate -i` (macOS) so the laptop never sleeps mid-test; use a
stable/wired connection. A sleep or wifi drop kills the run.

```bash
# 1) ALWAYS smoke first — validates auth + routes (fix any 4xx before scaling)
SCENARIO=smoke    caffeinate -i k6 run forbes-loadtest.js

# 2) baseline (real-world 100 VU)
SCENARIO=baseline caffeinate -i k6 run --out json=../results/baseline.json forbes-loadtest.js

# 3) soak (leak check + slow-log volume) — the key data-gathering run (default 45m)
SCENARIO=soak     caffeinate -i k6 run --out json=../results/soak.json forbes-loadtest.js
#   override length: SOAK_DURATION=30m SCENARIO=soak caffeinate -i k6 run ...

# 4) optional, only if baseline clean: find the knee
SCENARIO=stress   caffeinate -i k6 run --out json=../results/stress.json forbes-loadtest.js
SCENARIO=spike    caffeinate -i k6 run --out json=../results/spike.json forbes-loadtest.js
```

## On the server, during EACH run (separate SSH terminals)
Capture scripts are already deployed to `~/loadtest-capture/` on the server.
```bash
ssh forbes@217.217.253.76
bash ~/loadtest-capture/slowlog-digest.sh --start # sets long_query_time=0 (log ALL queries)
                                                  # + resets perf_schema digest for clean window
bash ~/loadtest-capture/server-metrics.sh         # CSV every 5s
# (optional) iostat -x 5 ; sar -n DEV 5 ; mpstat -P ALL 5
# ... run k6 ... then:
# Ctrl-C server-metrics.sh
bash ~/loadtest-capture/slowlog-digest.sh --stop  # reverts long_query_time=2 (ALWAYS run this!)
                                                  # outputs slowlog-*.log + slowlog-digest + pschema-digest
```
⚠️ `--start` makes MySQL log **every** query (full visibility, heavier disk). `--stop`
reverts to 2 — always run `--stop`, even if the test aborts, so prod doesn't keep logging all.

## Read the results
k6 end-of-run summary gives P90/P95/P99 (`http_req_duration`), throughput (`http_reqs`),
error rate (`http_req_failed`), max VUs. Per-journey via the `{journey:...}` tagged metrics.
Correlate timestamps with `server-metrics.csv` (CPU/mem/threads/scan counters) and the
slow-log digest. Write up in `docs/2026-06-DD-load-test-results.md`.

## Routes — VERIFIED 2026-06-20
Login + all 4 journeys returned 200 OK with a loadtest admin session
(login by **username**, not email). Still run smoke first as a sanity check, but no
route fixes should be needed. Never escalate past smoke if it's red.
