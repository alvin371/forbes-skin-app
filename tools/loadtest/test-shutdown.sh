#!/usr/bin/env bash
# Graceful shutdown and crash consistency test.
#
#   tools/loadtest/test-shutdown.sh [--mode graceful|hard|kill]
#
# Acceptance criteria this exists to prove, from the brief:
#   * SIGTERM stops claiming, drains in-flight work, and submits its results;
#   * claims whose provider request never started are RELEASED without consuming an attempt;
#   * genuinely interrupted started work is recovered by lease expiry, not lost;
#   * no duplicate business write, no stale result accepted, no silent loss.
#
# Three modes, because they fail differently:
#   graceful  SIGTERM + full drain window       -> zero rows left processing
#   hard      SIGTERM twice (hardStop)          -> in-flight abandoned to lease recovery
#   kill      SIGKILL, no handler runs at all   -> everything abandoned to lease recovery
#
# The kill mode is the important one: it is the only case that proves the system is correct
# when the worker's own shutdown code never executes.

# shellcheck source=lib.sh
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

MODE="graceful"
while [ $# -gt 0 ]; do
  case "$1" in
    --mode) MODE="${2:?}"; shift 2 ;;
    *) lt_die "unknown argument: $1" ;;
  esac
done

lt_require_env_file

FAILED=0
pass() { printf '  PASS  %s\n' "$1"; }
fail() { printf '  FAIL  %s  (%s)\n' "$1" "$2"; FAILED=1; }

q() { lt_mysql -N -e "$1" 2>/dev/null | tr -d ' \n\r'; }

# A latency profile slow enough that requests are reliably in flight when the signal lands.
lt_say "configuring slow provider so requests are genuinely in flight at signal time"
lt_mock_profile '{"seed":"shutdown","correlation":0.0,
  "scrape":{"success_rate":1.0,"latency":{"p50_ms":4000,"p95_ms":6000,"p99_ms":8000}},
  "rapidapi":{"success_rate":1.0,"latency":{"p50_ms":3000,"p95_ms":5000,"p99_ms":7000}}}'

lt_set_env ENDORSE_REFRESH_MAX_IN_FLIGHT 25
lt_set_env ENDORSE_REFRESH_MAX_RUNTIME_SEC 600
lt_set_env ENDORSE_REFRESH_DRAIN_SEC 30
lt_set_env ENDORSE_REFRESH_TEST_RUN_ID "shutdown_${MODE}"

SEED_COUNT=2000
"${LT_DIR}/seed.sh" --count "$SEED_COUNT" >/dev/null
lt_say "seeded ${SEED_COUNT} rows"

# The seeder places its deliberately-broken fixtures at ids >= count + 1000 (seed.php:158).
# They are pre-existing damage the shutdown path never touched, and two of them look exactly
# like shutdown bugs if not excluded:
#   * fixture 5 (inconsistent parent/attempt) is CORRECTLY quarantined and left 'processing'
#     for human reconciliation — that is the designed outcome, not a failed drain;
#   * fixture 7 (attempts already exhausted) is seeded with attempts=3 and no attempt rows,
#     which matches "an attempt was charged to a released row" without one having been.
# Asserting over them would have reported a false FAIL on a correct graceful shutdown.
PATHOLOGICAL_MIN_ID=$((SEED_COUNT + 1000))
REAL_ROWS="q.id <= ${PATHOLOGICAL_MIN_ID}"

lt_compose up -d --scale endorse-worker=1 endorse-worker >/dev/null 2>&1
WORKER="$(lt_compose ps -q endorse-worker | head -1)"
[ -n "$WORKER" ] || lt_die "worker did not start"

lt_say "letting work get in flight"
sleep 25

IN_FLIGHT_BEFORE="$(q "SELECT COUNT(*) FROM ${LT_DB_NAME}.endorse_refresh_queue WHERE status='processing'")"
COMPLETED_BEFORE="$(q "SELECT COUNT(*) FROM ${LT_DB_NAME}.endorse_refresh_queue WHERE status='completed'")"
lt_say "at signal time: processing=${IN_FLIGHT_BEFORE} completed=${COMPLETED_BEFORE}"

if [ "${IN_FLIGHT_BEFORE:-0}" -lt 1 ]; then
  lt_die "nothing was in flight — the test would prove nothing"
fi

case "$MODE" in
  graceful)
    lt_say "sending SIGTERM, allowing the full drain"
    docker kill --signal=SIGTERM "$WORKER" >/dev/null
    ;;
  hard)
    lt_say "sending SIGTERM twice (second = hard stop)"
    docker kill --signal=SIGTERM "$WORKER" >/dev/null
    sleep 1
    docker kill --signal=SIGTERM "$WORKER" >/dev/null
    ;;
  kill)
    lt_say "sending SIGKILL — no shutdown code runs at all"
    docker kill --signal=SIGKILL "$WORKER" >/dev/null
    ;;
  *) lt_die "unknown mode: ${MODE}" ;;
esac

# Wait for the process to actually be gone before asserting anything.
for _ in $(seq 1 60); do
  docker inspect -f '{{.State.Running}}' "$WORKER" 2>/dev/null | grep -q false && break
  sleep 1
done
lt_compose rm -f endorse-worker >/dev/null 2>&1

echo
lt_say "post-shutdown state (mode: ${MODE})"

# Scoped to real rows: a quarantined fixture is SUPPOSED to sit in 'processing' awaiting
# human reconciliation, so counting it here would make a correct drain look like a leak.
PROCESSING="$(q "SELECT COUNT(*) FROM ${LT_DB_NAME}.endorse_refresh_queue q WHERE ${REAL_ROWS} AND q.status='processing'")"
QUARANTINED="$(q "SELECT COUNT(*) FROM ${LT_DB_NAME}.endorse_refresh_queue q WHERE NOT (${REAL_ROWS}) AND q.status='processing'")"
COMPLETED="$(q "SELECT COUNT(*) FROM ${LT_DB_NAME}.endorse_refresh_queue WHERE status='completed'")"
CANCELLED="$(q "SELECT COUNT(*) FROM ${LT_DB_NAME}.endorse_refresh_queue_attempts WHERE status='cancelled'")"
printf '  processing=%s completed=%s cancelled_attempts=%s  (seeded fixtures held: %s)\n\n' \
  "$PROCESSING" "$COMPLETED" "$CANCELLED" "$QUARANTINED"

# --- universal invariants (all modes) ----------------------------------------

# 1. No silent loss: every seeded row is still accounted for in some state.
TOTAL="$(q "SELECT COUNT(*) FROM ${LT_DB_NAME}.endorse_refresh_queue")"
ACCOUNTED="$(q "SELECT COUNT(*) FROM ${LT_DB_NAME}.endorse_refresh_queue WHERE status IN ('pending','processing','submitted','completed','failed')")"
[ "$TOTAL" = "$ACCOUNTED" ] && pass "no silent loss (every row is in a known state)" \
                            || fail "rows vanished" "total=${TOTAL} accounted=${ACCOUNTED}"

# 2. A released (never-started) claim must NOT consume a retry attempt. This is what makes
#    shutdown free: cancelled allocations cost the row nothing.
BAD_ATTEMPTS="$(q "
  SELECT COUNT(*) FROM ${LT_DB_NAME}.endorse_refresh_queue q
   WHERE ${REAL_ROWS} AND q.status='pending' AND q.attempts > 0
     AND NOT EXISTS (SELECT 1 FROM ${LT_DB_NAME}.endorse_refresh_queue_attempts a
                      WHERE a.queue_id=q.id AND a.status IN ('retrying','failed','timed_out'))")"
[ "${BAD_ATTEMPTS:-0}" = "0" ] && pass "released claims consumed no retry attempt" \
                               || fail "attempts charged to released rows" "$BAD_ATTEMPTS"

# 3. No duplicate business write.
DUP_LOGS="$(q "SELECT COUNT(*) FROM (SELECT id_endorse, date FROM ${LT_DB_NAME}.endorse_logs GROUP BY id_endorse, date HAVING COUNT(*)>1) d")"
[ "${DUP_LOGS:-0}" = "0" ] && pass "no duplicate endorse_logs business key" \
                           || fail "duplicate business writes" "$DUP_LOGS"

# 4. No false completion.
FALSE_COMPLETE="$(q "
  SELECT COUNT(*) FROM ${LT_DB_NAME}.endorse_refresh_queue q
    JOIN ${LT_DB_NAME}.endorse e ON e.id=q.id_endorse
   WHERE q.status='completed' AND e.stats_observation_seq IS NULL
     AND EXISTS (SELECT 1 FROM ${LT_DB_NAME}.endorse_refresh_queue_attempts a
                  WHERE a.queue_id=q.id AND a.status='completed')")"
[ "${FALSE_COMPLETE:-0}" = "0" ] && pass "no completion without a business write" \
                                 || fail "false completions" "$FALSE_COMPLETE"

# 5. No completed/failed row still holding a worker or an active attempt pointer.
LEAKED="$(q "SELECT COUNT(*) FROM ${LT_DB_NAME}.endorse_refresh_queue WHERE status IN ('completed','failed') AND (worker_id IS NOT NULL OR active_attempt_id IS NOT NULL)")"
[ "${LEAKED:-0}" = "0" ] && pass "terminal rows hold no worker or attempt pointer" \
                         || fail "leaked ownership on terminal rows" "$LEAKED"

# 6. No duplicate ownership.
DUP_OWN="$(q "SELECT COUNT(*) FROM (SELECT queue_id FROM ${LT_DB_NAME}.endorse_refresh_queue_attempts WHERE status='processing' GROUP BY queue_id HAVING COUNT(*)>1) d")"
[ "${DUP_OWN:-0}" = "0" ] && pass "no queue row has two processing attempts" \
                          || fail "duplicate ownership" "$DUP_OWN"

# --- mode-specific -----------------------------------------------------------
case "$MODE" in
  graceful)
    # The whole point of the drain: nothing is left for lease recovery to clean up.
    [ "${PROCESSING:-0}" = "0" ] && pass "graceful drain left ZERO rows processing" \
                                 || fail "rows still processing after a full drain" "$PROCESSING"
    [ "${CANCELLED:-0}" -gt 0 ] && pass "unstarted claims were released (${CANCELLED} cancelled attempts)" \
                                || fail "no claims were released" "expected > 0"
    ;;
  hard|kill)
    # Abandoned rows are EXPECTED here. What matters is that they are recoverable — the lease
    # is set and expiring, not null, so resetStuck() will fence and release them.
    RECOVERABLE="$(q "SELECT COUNT(*) FROM ${LT_DB_NAME}.endorse_refresh_queue q WHERE ${REAL_ROWS} AND q.status='processing' AND q.lease_expires_at IS NOT NULL")"
    if [ "${PROCESSING:-0}" = "0" ]; then
      pass "nothing abandoned (drain won the race)"
    elif [ "$RECOVERABLE" = "$PROCESSING" ]; then
      pass "all ${PROCESSING} abandoned rows carry a lease and are recoverable"
    else
      fail "abandoned rows without a lease cannot be recovered" "processing=${PROCESSING} with_lease=${RECOVERABLE}"
    fi

    # Prove recovery actually happens rather than asserting it would: expire the leases and
    # run one claim cycle, which calls resetStuck() first.
    if [ "${PROCESSING:-0}" != "0" ]; then
      lt_say "expiring leases and running one recovery cycle"
      lt_mysql -e "UPDATE ${LT_DB_NAME}.endorse_refresh_queue
                      SET lease_expires_at = NOW(6) - INTERVAL 600 SECOND,
                          started_at = NOW(6) - INTERVAL 900 SECOND
                    WHERE status='processing'" >/dev/null 2>&1
      lt_set_env ENDORSE_REFRESH_MAX_RUNTIME_SEC 15
      lt_compose run --rm endorse-worker >/dev/null 2>&1 || true

      # Exact, not "at most one": the seeded fixtures are excluded by id, so there is no
      # legitimate leftover to tolerate. A <=1 threshold here would hide a real single-row leak.
      STILL="$(q "SELECT COUNT(*) FROM ${LT_DB_NAME}.endorse_refresh_queue q WHERE ${REAL_ROWS} AND q.status='processing'")"
      [ "${STILL:-0}" = "0" ] && pass "lease recovery reclaimed every abandoned row" \
                             || fail "rows still stuck after recovery" "$STILL"
    fi
    ;;
esac

echo
if [ "$FAILED" -ne 0 ]; then
  lt_die "shutdown test FAILED (mode: ${MODE})"
fi
lt_say "shutdown test PASSED (mode: ${MODE})"
