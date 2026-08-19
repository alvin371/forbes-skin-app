#!/usr/bin/env bash
# Sample container and database resource usage during a run, one JSONL record per tick.
#
#   tools/loadtest/sample-resources.sh <output.jsonl> [interval_sec]
#
# Runs in the background for the duration of a scenario; run.sh starts and stops it. Exists
# because the acceptance criteria include CPU < ~80%, memory < ~85% and DB connections < ~75%
# of pool, and a number sampled by hand once mid-run is not evidence that the limit held for
# the whole run.

# shellcheck source=lib.sh
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

OUT="${1:?usage: sample-resources.sh <output.jsonl> [interval_sec]}"
INTERVAL="${2:-10}"

mkdir -p "$(dirname "$OUT")"
: > "$OUT"

while true; do
  TS="$(date -u +%Y-%m-%dT%H:%M:%SZ)"

  # Per-container CPU / memory. --no-stream so each sample is a point-in-time reading.
  CONTAINERS="$(docker stats --no-stream --format '{{.Name}}|{{.CPUPerc}}|{{.MemPerc}}|{{.MemUsage}}|{{.PIDs}}' 2>/dev/null \
    | grep 'forbes-loadtest' \
    | awk -F'|' '{gsub(/%/,"",$2); gsub(/%/,"",$3);
        printf "%s{\"name\":\"%s\",\"cpu_pct\":%s,\"mem_pct\":%s,\"mem\":\"%s\",\"pids\":%s}", sep, $1, $2, $3, $4, $5; sep=","}')"

  # Connection pool utilisation and lock pressure — the two database limits that matter here.
  DB="$(lt_mysql -N -B -e "
    SELECT CONCAT(
      '{\"threads_connected\":', (SELECT VARIABLE_VALUE FROM performance_schema.global_status WHERE VARIABLE_NAME='THREADS_CONNECTED'),
      ',\"threads_running\":',   (SELECT VARIABLE_VALUE FROM performance_schema.global_status WHERE VARIABLE_NAME='THREADS_RUNNING'),
      ',\"max_connections\":',   @@max_connections,
      ',\"row_lock_waits\":',    (SELECT VARIABLE_VALUE FROM performance_schema.global_status WHERE VARIABLE_NAME='INNODB_ROW_LOCK_WAITS'),
      ',\"row_lock_time_avg_ms\":', (SELECT VARIABLE_VALUE FROM performance_schema.global_status WHERE VARIABLE_NAME='INNODB_ROW_LOCK_TIME_AVG'),
      '}')" 2>/dev/null)"

  printf '{"ts":"%s","containers":[%s],"db":%s}\n' "$TS" "${CONTAINERS:-}" "${DB:-null}" >> "$OUT"
  sleep "$INTERVAL"
done
