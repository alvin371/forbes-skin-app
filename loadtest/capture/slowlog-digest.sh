#!/usr/bin/env bash
# Snapshot + digest MySQL slow log AND performance_schema around a load test
# (run ON vmi2846295). slow_query_log is already ON -> /var/lib/mysql/slow.log.
#
#   bash slowlog-digest.sh --start   # mark slow.log size, set long_query_time=0 (log ALL),
#                                     # reset perf_schema digest stats for a clean test window
#   bash slowlog-digest.sh --stop    # revert long_query_time=2, copy test slice, digest both
#
# Outputs (cwd): slowlog-<ts>.log, slowlog-digest-<ts>.txt, pschema-digest-<ts>.txt
#
# NOTE: --start lowers long_query_time to 0 = logs EVERY query (full picture, heavier disk).
#       --stop ALWAYS reverts to 2. Don't leave a test in the --start state.

set -u
CID="$(docker ps --format '{{.Names}}' | grep -i mysql | head -1)"
MARK="/tmp/slowlog.startsize"
SLOW="/var/lib/mysql/slow.log"

myroot() { docker exec -i "$CID" bash -c 'mysql -uroot -p"$(cat "$MYSQL_ROOT_PASSWORD_FILE")" "$@"' _ "$@"; }
size_in_container() { docker exec -i "$CID" bash -c "stat -c %s '$SLOW' 2>/dev/null || echo 0"; }

case "${1:-}" in
  --start)
    size_in_container > "$MARK"
    echo "marked slow.log start size = $(cat "$MARK") bytes"
    # log ALL queries during the test + reset perf_schema digest stats for a clean window
    myroot -e "SET GLOBAL long_query_time=0;
               TRUNCATE performance_schema.events_statements_summary_by_digest;" 2>/dev/null \
      && echo "long_query_time=0 (logging ALL) + perf_schema digest reset" \
      || echo "WARN: could not set long_query_time / reset perf_schema (check root)"
    echo ">>> remember to run --stop after the test to revert long_query_time=2"
    ;;
  --stop)
    TS="$(date +%Y%m%d-%H%M%S)"
    START="$(cat "$MARK" 2>/dev/null || echo 0)"
    # ALWAYS revert threshold first
    myroot -e "SET GLOBAL long_query_time=2;" 2>/dev/null \
      && echo "reverted long_query_time=2" || echo "WARN: could not revert long_query_time"

    # slow-log slice written during the test
    OUT="./slowlog-${TS}.log"
    docker exec -i "$CID" bash -c "tail -c +$((START+1)) '$SLOW'" > "$OUT"
    echo "slow-log slice -> $OUT ($(wc -l < "$OUT") lines)"
    if command -v pt-query-digest >/dev/null 2>&1; then
      pt-query-digest "$OUT" > "./slowlog-digest-${TS}.txt"
    else
      docker exec -i "$CID" bash -c "mysqldumpslow -s t -t 30 '$SLOW'" > "./slowlog-digest-${TS}.txt" 2>/dev/null
    fi
    echo "slow-log digest -> ./slowlog-digest-${TS}.txt"

    # performance_schema: top queries by total time during the window (normalized)
    myroot -t -e "
      SELECT LEFT(DIGEST_TEXT,90) AS query, COUNT_STAR AS calls,
             ROUND(SUM_TIMER_WAIT/1e12,2) AS total_s,
             ROUND(AVG_TIMER_WAIT/1e9,1)  AS avg_ms,
             ROUND(MAX_TIMER_WAIT/1e9,1)  AS max_ms,
             ROUND(SUM_ROWS_EXAMINED/GREATEST(COUNT_STAR,1)) AS avg_rows_examined,
             ROUND(SUM_ROWS_SENT/GREATEST(COUNT_STAR,1))     AS avg_rows_sent
      FROM performance_schema.events_statements_summary_by_digest
      ORDER BY SUM_TIMER_WAIT DESC LIMIT 30;" > "./pschema-digest-${TS}.txt" 2>/dev/null
    echo "perf_schema digest -> ./pschema-digest-${TS}.txt"
    ;;
  *)
    echo "usage: $0 --start | --stop"; exit 1;;
esac
