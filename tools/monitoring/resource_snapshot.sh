#!/usr/bin/env sh
# Host-side, log-only resource sampler. Intended for a systemd user timer.
set -eu

MONITOR_LOG_DIR="${MONITOR_LOG_DIR:-/home/forbes/monitoring-logs}"
MONITOR_STATE_DIR="${MONITOR_STATE_DIR:-/home/forbes/.local/state/forbes-monitor}"
MONITOR_CPU_THRESHOLD="${MONITOR_CPU_THRESHOLD:-80}"
MONITOR_MEMORY_THRESHOLD="${MONITOR_MEMORY_THRESHOLD:-85}"
MONITOR_CONSECUTIVE_SAMPLES="${MONITOR_CONSECUTIVE_SAMPLES:-3}"
MYSQL_MONITOR_COMMAND="${MYSQL_MONITOR_COMMAND:-}"

mkdir -p "$MONITOR_LOG_DIR" "$MONITOR_STATE_DIR"
today="$(date -u +%F)"
log_file="$MONITOR_LOG_DIR/resource-$today.jsonl"
state_file="$MONITOR_STATE_DIR/high-samples"

json_value() {
  printf '%s' "$1" | python3 -c 'import json, sys; print(json.dumps(sys.stdin.read(), ensure_ascii=False))'
}

container_json() {
  printf '%s\n' "$1" | python3 -c '
import json
import sys

rows = []
for line in sys.stdin:
    parts = line.rstrip("\n").split("|")
    if len(parts) != 4:
        continue
    rows.append({
        "name": parts[0],
        "cpu_percent": float(parts[1].rstrip("%")),
        "memory_percent": float(parts[2].rstrip("%")),
        "pids": int(parts[3]),
    })
print(json.dumps(rows, ensure_ascii=False))
'
}

stats="$(docker stats --no-stream --format '{{.Name}}|{{.CPUPerc}}|{{.MemPerc}}|{{.PIDs}}' 2>/dev/null || true)"
load="$(cut -d ' ' -f1-3 /proc/loadavg 2>/dev/null || uptime)"
memory="$(free -b 2>/dev/null | awk '/^Mem:/ {print "used=" $3 ",total=" $2}' || true)"
timestamp="$(date -u +%Y-%m-%dT%H:%M:%SZ)"

high="$(printf '%s\n' "$stats" | awk -F'|' -v cpu="$MONITOR_CPU_THRESHOLD" -v mem="$MONITOR_MEMORY_THRESHOLD" '
  $1 ~ /^(forbes_app|mysql-8_mysql|forbes_endorse-refresh-worker)/ {
    gsub(/%/, "", $2); gsub(/%/, "", $3);
    if (($2 + 0) >= cpu || ($3 + 0) >= mem) found=1
  }
  END { print found ? "true" : "false" }
')"

previous=0
[ -r "$state_file" ] && previous="$(cat "$state_file" 2>/dev/null || printf 0)"
case "$previous" in ''|*[!0-9]*) previous=0 ;; esac
if [ "$high" = true ]; then
  count=$((previous + 1))
else
  count=0
fi
printf '%s\n' "$count" > "$state_file"

printf '{"ts":"%s","type":"resource_snapshot","high":%s,"consecutive_high":%s,"host_load":%s,"host_memory":%s,"containers":%s}\n' \
  "$timestamp" "$high" "$count" "$(json_value "$load")" "$(json_value "$memory")" "$(container_json "$stats")" >> "$log_file"

if [ "$count" -eq "$MONITOR_CONSECUTIVE_SAMPLES" ]; then
  evidence="$MONITOR_LOG_DIR/spike-$(date -u +%Y%m%dT%H%M%SZ).txt"
  {
    echo "timestamp=$timestamp"
    echo "host_load=$load"
    echo "container_stats=$stats"
    echo "top_processes"
    ps -eo pid,ppid,comm,%cpu,%mem,etime,args --sort=-%cpu | head -n 40
    echo "container_processes"
    docker ps --format '{{.Names}}' | grep -E '^(forbes_app|mysql-8_mysql)' | while read -r container; do
      echo "[$container]"
      docker top "$container" -eo pid,ppid,pcpu,pmem,etime,args 2>&1 | head -n 30
    done
    if [ -n "$MYSQL_MONITOR_COMMAND" ]; then
      echo "mysql_diagnostics"
      sh -c "$MYSQL_MONITOR_COMMAND" 2>&1
    fi
  } > "$evidence"
  printf '{"ts":"%s","type":"resource_spike","evidence_file":%s,"consecutive_high":%s}\n' \
    "$timestamp" "$(json_value "$evidence")" "$count" >> "$log_file"
fi

# Retain 14 days, compressing completed daily JSONL files after one day.
find "$MONITOR_LOG_DIR" -maxdepth 1 -type f -name 'resource-*.jsonl' -mtime +1 -exec gzip -f {} \;
find "$MONITOR_LOG_DIR" -maxdepth 1 -type f \( -name 'resource-*.jsonl.gz' -o -name 'spike-*.txt' \) -mtime +14 -delete
