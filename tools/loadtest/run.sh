#!/usr/bin/env bash
# Run one load-test scenario end to end: configure, seed, run N workers, report.
#
#   tools/loadtest/run.sh --scenario A --duration 300 --replicas 1 --in-flight 20
#
# Scenarios (mock provider profiles) are defined in scenarios/. Everything the worker reads
# comes from .env.loadtest, which this script rewrites — the worker prefers that file over the
# process environment, so a docker `environment:` entry would be silently ignored.
#
# Runs shorter than ~3 minutes cannot produce a trustworthy sustained rate: the limiter's
# rolling 60s window lets a short run spend a full minute's budget. report.php says so
# explicitly rather than printing a flattering number.

# shellcheck source=lib.sh
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

SCENARIO="A"
DURATION=300
REPLICAS=1
IN_FLIGHT=""
SEED_COUNT=""
LEG_ORDER=""
MAX_LEGS=""
DIRECT_RATE=""
RAPID_RATE=""
LABEL=""
SKIP_SEED=0

while [ $# -gt 0 ]; do
  case "$1" in
    --scenario)    SCENARIO="${2:?}"; shift 2 ;;
    --duration)    DURATION="${2:?}"; shift 2 ;;
    --replicas)    REPLICAS="${2:?}"; shift 2 ;;
    --in-flight)   IN_FLIGHT="${2:?}"; shift 2 ;;
    --seed-count)  SEED_COUNT="${2:?}"; shift 2 ;;
    --leg-order)   LEG_ORDER="${2:?}"; shift 2 ;;
    --max-legs)    MAX_LEGS="${2:?}"; shift 2 ;;
    --direct-rate) DIRECT_RATE="${2:?}"; shift 2 ;;
    --rapid-rate)  RAPID_RATE="${2:?}"; shift 2 ;;
    --label)       LABEL="${2:?}"; shift 2 ;;
    --skip-seed)   SKIP_SEED=1; shift ;;
    *) lt_die "unknown argument: $1" ;;
  esac
done

lt_require_env_file

PROFILE_FILE="${LT_DIR}/scenarios/${SCENARIO}.json"
[ -f "$PROFILE_FILE" ] || lt_die "no scenario profile: ${PROFILE_FILE}"

TEST_RUN_ID="$(date +%Y%m%d%H%M%S)_${SCENARIO}"
LABEL="${LABEL:-scenario ${SCENARIO}}"
mkdir -p "$LT_REPORTS"

# --- worker configuration ----------------------------------------------------
lt_set_env ENDORSE_REFRESH_TEST_RUN_ID "$TEST_RUN_ID"
# +30s so the worker is never the thing that ends the run; the script stops it.
lt_set_env ENDORSE_REFRESH_MAX_RUNTIME_SEC "$((DURATION + 30))"
lt_set_env ENDORSE_REFRESH_WORKER_REPLICAS "$REPLICAS"
[ -n "$IN_FLIGHT" ]   && lt_set_env ENDORSE_REFRESH_MAX_IN_FLIGHT "$IN_FLIGHT"
[ -n "$LEG_ORDER" ]   && lt_set_env ENDORSE_REFRESH_LEG_ORDER "$LEG_ORDER"
[ -n "$MAX_LEGS" ]    && lt_set_env ENDORSE_REFRESH_MAX_LEGS "$MAX_LEGS"
[ -n "$DIRECT_RATE" ] && lt_set_env ENDORSE_REFRESH_DIRECT_RATE_PER_MIN "$DIRECT_RATE"
[ -n "$RAPID_RATE" ]  && lt_set_env ENDORSE_REFRESH_RAPIDAPI_RATE_PER_MIN "$RAPID_RATE"

# --- provider profile --------------------------------------------------------
lt_say "applying mock profile: ${SCENARIO}"
lt_mock_profile "$(cat "$PROFILE_FILE")"

# --- dataset -----------------------------------------------------------------
if [ "$SKIP_SEED" -eq 0 ]; then
  # Size the corpus so the queue cannot run dry mid-run and understate throughput.
  # 800/min is above any rate this stack can sustain, so this is a safe upper bound.
  COUNT="${SEED_COUNT:-$(( (DURATION / 60 + 1) * 800 ))}"
  "${LT_DIR}/seed.sh" --count "$COUNT" >/dev/null
  lt_say "seeded ${COUNT} rows"
fi

# --- run ---------------------------------------------------------------------
lt_say "starting ${REPLICAS} worker(s) for ${DURATION}s  [run ${TEST_RUN_ID}]"
# Infrastructure is already up (tools/loadtest/up.sh). Naming only endorse-worker here keeps
# the scale flag unambiguous — combining --scale with unrelated service names errors out, and
# with `set -e` that ends the run silently before a single request is made.
lt_compose up -d --scale "endorse-worker=${REPLICAS}" endorse-worker

# Resource sampling runs for the whole scenario: a single hand-taken reading proves nothing
# about whether CPU/memory/connection limits held throughout.
RESOURCE_LOG="${LT_REPORTS}/${TEST_RUN_ID}.resources.jsonl"
"${LT_DIR}/sample-resources.sh" "$RESOURCE_LOG" 10 &
SAMPLER_PID=$!
trap 'kill "$SAMPLER_PID" 2>/dev/null || true' EXIT

STARTED_AT=$(date +%s)
while [ $(( $(date +%s) - STARTED_AT )) -lt "$DURATION" ]; do
  sleep 10
  RUNNING="$(lt_compose ps -q endorse-worker | wc -l | tr -d ' ')"
  ELAPSED=$(( $(date +%s) - STARTED_AT ))
  DONE="$(lt_mysql -N -e "SELECT COUNT(*) FROM ${LT_DB_NAME}.endorse_refresh_queue WHERE status='completed'" 2>/dev/null | tr -d ' \n')"
  printf '  t=%-5s workers=%-3s completed=%s\n' "${ELAPSED}s" "$RUNNING" "${DONE:-?}"

  # Automatic stop condition: every worker died. Continuing would measure an empty stack.
  if [ "$RUNNING" = "0" ]; then
    lt_warn "all workers exited early — stopping"
    break
  fi
done

# --- graceful stop -----------------------------------------------------------
# SIGTERM, then wait out the drain. stop_grace_period exceeds ENDORSE_REFRESH_DRAIN_SEC so the
# drain is never cut short — that is what makes shutdown correctness measurable here.
lt_say "sending SIGTERM and draining"
lt_compose stop endorse-worker >/dev/null 2>&1
lt_compose rm -f endorse-worker >/dev/null 2>&1

kill "$SAMPLER_PID" 2>/dev/null || true
trap - EXIT

# --- resource summary ---------------------------------------------------------
if [ -s "$RESOURCE_LOG" ]; then
  lt_say "peak resource usage over the run"
  python3 - "$RESOURCE_LOG" <<'PYEOF'
import json, sys
peak, db = {}, {"threads_connected": 0, "max_connections": 0, "row_lock_waits": 0}
for line in open(sys.argv[1]):
    line = line.strip()
    if not line:
        continue
    try:
        rec = json.loads(line)
    except json.JSONDecodeError:
        continue
    for c in rec.get("containers") or []:
        p = peak.setdefault(c["name"], {"cpu": 0.0, "mem": 0.0})
        p["cpu"] = max(p["cpu"], float(c.get("cpu_pct") or 0))
        p["mem"] = max(p["mem"], float(c.get("mem_pct") or 0))
    d = rec.get("db") or {}
    for k in db:
        db[k] = max(db[k], int(d.get(k) or 0))
for name, p in sorted(peak.items()):
    print(f"  {name:42s} cpu {p['cpu']:6.1f}%  mem {p['mem']:5.1f}%")
if db["max_connections"]:
    pct = 100.0 * db["threads_connected"] / db["max_connections"]
    print(f"  {'mysql connections':42s} {db['threads_connected']}/{db['max_connections']} ({pct:.1f}% of pool)")
    print(f"  {'innodb row lock waits':42s} {db['row_lock_waits']}")
PYEOF
fi

# --- report ------------------------------------------------------------------
JSON="tools/loadtest/reports/${TEST_RUN_ID}.json"
lt_worker_once php tools/loadtest/report.php \
  --label "$LABEL" --json "$JSON" --window 300 --mock-url "http://provider-mock.local:8080" || true

lt_say "mock ground truth:"
lt_mock /_control/stats | sed 's/^/  /'
echo

lt_say "report written to ${JSON}"
