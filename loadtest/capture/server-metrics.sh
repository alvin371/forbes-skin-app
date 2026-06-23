#!/usr/bin/env bash
# Server-side metrics sampler for the load test (run ON vmi2846295 during k6).
# Samples every INTERVAL sec into a CSV until Ctrl-C.
#   Container CPU/mem/net (docker stats), host CPU/disk/net (sysstat), MySQL status deltas.
#
# Usage (on server):
#   bash server-metrics.sh                 # -> ./loadtest-metrics-<ts>.csv, 5s interval
#   INTERVAL=5 OUT=/tmp/lt.csv bash server-metrics.sh
#
# Pair with k6 on the laptop; keep clocks aligned (both Asia/Jakarta). Start ~1m before k6.

set -u
INTERVAL="${INTERVAL:-5}"
OUT="${OUT:-./loadtest-metrics-$(date +%Y%m%d-%H%M%S).csv}"
CID="$(docker ps --format '{{.Names}}' | grep -i mysql | head -1)"

mysql_status() {  # prints "var value" lines for selected counters
  docker exec -i "$CID" bash -c \
    'mysql -uroot -p"$(cat "$MYSQL_ROOT_PASSWORD_FILE")" -N -e "
      SHOW GLOBAL STATUS WHERE Variable_name IN (
       '\''Threads_running'\'','\''Threads_connected'\'','\''Max_used_connections'\'',
       '\''Innodb_buffer_pool_reads'\'','\''Innodb_buffer_pool_read_requests'\'',
       '\''Handler_read_rnd_next'\'','\''Created_tmp_disk_tables'\'','\''Slow_queries'\'',
       '\''Com_select'\'','\''Com_insert'\'','\''Com_update'\'');"' 2>/dev/null
}

echo "ts,mysql_cpu,mysql_mem,forbes_cpu,sec_cpu,threads_running,threads_connected,bp_reads,bp_read_req,handler_rnd_next,tmp_disk,slow_queries,com_select,com_insert,com_update" > "$OUT"
echo "sampling every ${INTERVAL}s -> $OUT  (Ctrl-C to stop)"

while true; do
  TS="$(date '+%Y-%m-%d %H:%M:%S')"
  STATS="$(docker stats --no-stream --format '{{.Name}};{{.CPUPerc}};{{.MemUsage}}')"
  MYSQL_CPU="$(echo "$STATS" | grep -i mysql | cut -d';' -f2 | tr -d '%')"
  MYSQL_MEM="$(echo "$STATS" | grep -i mysql | cut -d';' -f3 | awk '{print $1}')"
  FORBES_CPU="$(echo "$STATS" | grep -iE '^forbes_app' | cut -d';' -f2 | tr -d '%')"
  SEC_CPU="$(echo "$STATS" | grep -i 'sec-forbes' | cut -d';' -f2 | tr -d '%')"

  declare -A M=()
  while read -r k v; do [ -n "${k:-}" ] && M[$k]="$v"; done < <(mysql_status)

  echo "$TS,$MYSQL_CPU,$MYSQL_MEM,$FORBES_CPU,$SEC_CPU,${M[Threads_running]:-},${M[Threads_connected]:-},${M[Innodb_buffer_pool_reads]:-},${M[Innodb_buffer_pool_read_requests]:-},${M[Handler_read_rnd_next]:-},${M[Created_tmp_disk_tables]:-},${M[Slow_queries]:-},${M[Com_select]:-},${M[Com_insert]:-},${M[Com_update]:-}" >> "$OUT"
  sleep "$INTERVAL"
done

# Optional, run in separate terminals for host disk/net (sysstat is installed):
#   iostat -x 5     > iostat-<ts>.log
#   sar -n DEV 5    > netdev-<ts>.log
#   mpstat -P ALL 5 > cpu-<ts>.log
