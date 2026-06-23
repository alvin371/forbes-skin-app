#!/usr/bin/env bash
# perfwatch v2 — high-resolution 1-hour profiler for the shared forbes box.
# Two concurrent collectors so the slow docker-stats call never throttles the 1s loop:
#   metrics.csv     per-second: host load + MySQL Threads_running/connected + Com_select (cheap)
#   cpu.csv         ~per-3s:    per-container CPU% (docker stats is slow; its own cadence)
#   processlist.csv per-second: every in-flight (non-sleep) query — id, db, elapsed s, state, SQL
#   slow.log        EVERY query + exact duration (long_query_time=0 for the window; reverted on exit)
#   access-*.log    every HTTP hit on each app for the window
# Usage: nohup bash perfwatch.sh [duration_seconds] >/dev/null 2>&1 &
set -u
DUR=${1:-3600}
BASE="$HOME/perfwatch"
OUT="$BASE/run-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$OUT"; ln -sfn "$OUT" "$BASE/latest"

CID=$(docker ps --format '{{.Names}}' | grep -i mysql | head -1)
FAPP=$(docker ps --format '{{.Names}}' | grep -E 'forbes_app' | grep -v sec | head -1)
SAPP=$(docker ps --format '{{.Names}}' | grep sec-forbes | head -1)
myq() { docker exec -i "$CID" bash -c 'mysql -N -uroot -p"$(cat "$MYSQL_ROOT_PASSWORD_FILE")" -e "$1"' _ "$1" 2>/dev/null; }

cleanup() {
  myq "SET GLOBAL long_query_time=2;"
  kill "$CPU_PID" 2>/dev/null
  echo "stop=$(date '+%F %T')  reverted long_query_time=2" >> "$OUT/meta.txt"
}
trap cleanup EXIT INT TERM

echo "start=$(date '+%F %T %Z')  dur=${DUR}s  mysql=$CID  forbes=$FAPP  sec=$SAPP" > "$OUT/meta.txt"
myq "SET GLOBAL long_query_time=0;"
echo "long_query_time=0 set $(date)" >> "$OUT/meta.txt"

# --- background CPU collector (its own cadence; docker stats --no-stream ~2-3s) ---
echo "ts,forbes_cpu,sec_cpu,mysql_cpu,forbes_mem,sec_mem" > "$OUT/cpu.csv"
(
  while :; do
    TS=$(date '+%F %T')
    S=$(docker stats --no-stream --format '{{.Name}}|{{.CPUPerc}}|{{.MemUsage}}' 2>/dev/null)
    FOR=$(echo "$S" | grep -E 'forbes_app' | grep -v sec | head -1)
    SEC=$(echo "$S" | grep sec-forbes | head -1)
    MY=$(echo  "$S" | grep mysql | head -1)
    echo "$TS,$(echo "$FOR"|cut -d'|' -f2|tr -d '%'),$(echo "$SEC"|cut -d'|' -f2|tr -d '%'),$(echo "$MY"|cut -d'|' -f2|tr -d '%'),$(echo "$FOR"|cut -d'|' -f3|cut -d/ -f1|tr -d ' '),$(echo "$SEC"|cut -d'|' -f3|cut -d/ -f1|tr -d ' ')" >> "$OUT/cpu.csv"
  done
) & CPU_PID=$!

# --- per-second metrics + processlist ---
echo "ts,load1,threads_running,threads_connected,com_select" > "$OUT/metrics.csv"
echo "ts|id|db|time_s|state|sql" > "$OUT/processlist.csv"
END=$(( $(date +%s) + DUR ))
while [ "$(date +%s)" -lt "$END" ]; do
  TS=$(date '+%F %T')
  LOAD=$(cut -d' ' -f1 /proc/loadavg)
  ST=$(myq "SELECT CONCAT_WS(',',
        (SELECT VARIABLE_VALUE FROM performance_schema.global_status WHERE VARIABLE_NAME='Threads_running'),
        (SELECT VARIABLE_VALUE FROM performance_schema.global_status WHERE VARIABLE_NAME='Threads_connected'),
        (SELECT VARIABLE_VALUE FROM performance_schema.global_status WHERE VARIABLE_NAME='Com_select'));")
  echo "$TS,$LOAD,${ST:-,,}" >> "$OUT/metrics.csv"
  myq "SELECT CONCAT_WS('|','$TS',id,IFNULL(db,''),time,IFNULL(state,''),REPLACE(REPLACE(LEFT(info,300),'\n',' '),'|',' ')) FROM information_schema.processlist WHERE command='Query' AND info IS NOT NULL AND info NOT LIKE '%processlist%' AND info NOT LIKE '%global_status%';" >> "$OUT/processlist.csv"
  sleep 1
done

docker logs --since "${DUR}s" "$FAPP" > "$OUT/access-forbes.log" 2>&1
docker logs --since "${DUR}s" "$SAPP" > "$OUT/access-sec.log"    2>&1
docker exec -i "$CID" bash -c 'tail -c 80000000 /var/lib/mysql/slow.log' > "$OUT/slow.log" 2>/dev/null
