#!/usr/bin/env sh
# Host-side 15-second CPU incident collector. Intended for a systemd user timer.
# It is log-first: only open/close evidence and active-incident samples are sent
# to the application database, so a busy application never gets one DB write per
# HTTP request.
set -eu

MONITOR_LOG_DIR="${MONITOR_LOG_DIR:-/home/forbes/monitoring-logs}"
MONITOR_STATE_DIR="${MONITOR_STATE_DIR:-/home/forbes/.local/state/forbes-monitor}"
MONITOR_CPU_THRESHOLD="${MONITOR_CPU_THRESHOLD:-60}"
MONITOR_RECOVERY_SAMPLES="${MONITOR_RECOVERY_SAMPLES:-2}"
MONITOR_RETENTION_DAYS="${MONITOR_RETENTION_DAYS:-30}"
MYSQL_MONITOR_COMMAND="${MYSQL_MONITOR_COMMAND:-}"
MONITOR_EVIDENCE_REQUEST_LIMIT="${MONITOR_EVIDENCE_REQUEST_LIMIT:-500}"

mkdir -p "$MONITOR_LOG_DIR" "$MONITOR_STATE_DIR"
today="$(date -u +%F)"
log_file="$MONITOR_LOG_DIR/resource-$today.jsonl"
timestamp="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
now_epoch="$(date +%s)"
load="$(cut -d ' ' -f1-3 /proc/loadavg 2>/dev/null || uptime)"
memory="$(free -b 2>/dev/null | awk '/^Mem:/ {print "used=" $3 ",total=" $2}' || true)"
stats="$(docker stats --no-stream --format '{{.Name}}|{{.CPUPerc}}|{{.MemPerc}}|{{.PIDs}}' 2>/dev/null || true)"

json_snapshot() {
    python3 - "$timestamp" "$load" "$memory" "$stats" <<'PY'
import json
import sys

rows = []
for line in sys.argv[4].splitlines():
    parts = line.rstrip('\n').split('|')
    if len(parts) != 4:
        continue
    try:
        rows.append({
            'name': parts[0],
            'cpu_percent': float(parts[1].rstrip('%')),
            'memory_percent': float(parts[2].rstrip('%')),
            'pids': int(parts[3]),
        })
    except ValueError:
        continue
print(json.dumps({
    'ts': sys.argv[1], 'type': 'resource_snapshot',
    'host_load': sys.argv[2], 'host_memory': sys.argv[3], 'containers': rows,
}, ensure_ascii=False))
PY
}

snapshot="$(json_snapshot)"
printf '%s\n' "$snapshot" >> "$log_file"

app_container="$(docker ps --format '{{.ID}} {{.Names}}' 2>/dev/null | awk '$2 ~ /^forbes_app\./ {print $1; exit}')"
emit() {
    payload="$1"
    printf '%s\n' "$payload" >> "$log_file"
    if [ -n "$app_container" ]; then
        printf '%s\n' "$payload" | docker exec -i "$app_container" php /var/www/html/tools/monitoring/ingest_resource_snapshot.php >/dev/null 2>&1 || true
    fi
}

service_for_container() {
    case "$1" in
        mysql-8_mysql*) printf '%s\n' mysql-8_mysql ;;
        forbes_app*) printf '%s\n' forbes_app ;;
        sec-forbes_app*) printf '%s\n' sec-forbes_app ;;
    esac
}

row_for_service() {
    service="$1"
    printf '%s\n' "$stats" | awk -F'|' -v service="$service" '
        service == "mysql-8_mysql" && $1 ~ /^mysql-8_mysql/ { print; exit }
        service == "forbes_app" && $1 ~ /^forbes_app/ { print; exit }
        service == "sec-forbes_app" && $1 ~ /^sec-forbes_app/ { print; exit }
    '
}

capture_evidence() {
    service="$1"
    phase="$2"
    since="$3"
    evidence="$MONITOR_LOG_DIR/incident-${service}-${phase}-$(date -u +%Y%m%dT%H%M%SZ).json"
    safe_processes="$(ps -eo pid,ppid,comm,%cpu,%mem,etime --sort=-%cpu 2>/dev/null | head -n 41 || true)"
    mysql_output=""
    if [ -n "$MYSQL_MONITOR_COMMAND" ]; then
        mysql_output="$(sh -c "$MYSQL_MONITOR_COMMAND" 2>&1 | head -n 300 || true)"
    fi

    # Docker PERF is already structured and redacted by RequestPerformanceHook.
    # Capture both applications for a MySQL incident because either can be the load source.
    perf_file="$(mktemp "${TMPDIR:-/tmp}/forbes-perf.XXXXXX")"
    trap 'rm -f "$perf_file"' EXIT HUP INT TERM
    docker ps --format '{{.Names}}' 2>/dev/null | awk '/^(forbes_app|sec-forbes_app)/' | while read -r container; do
        docker logs --timestamps --since "$since" "$container" 2>&1 | awk '/ PERF \{/{sub(/^.* PERF /, ""); print}' | tail -n "$MONITOR_EVIDENCE_REQUEST_LIMIT" >> "$perf_file" || true
    done

    python3 - "$evidence" "$timestamp" "$service" "$phase" "$load" "$memory" "$safe_processes" "$mysql_output" "$perf_file" <<'PY'
import json
import sys

path, ts, service, phase, load, memory, processes, mysql, perf_path = sys.argv[1:]
records = []
try:
    with open(perf_path, encoding='utf-8', errors='replace') as handle:
        for line in handle:
            try:
                item = json.loads(line)
            except json.JSONDecodeError:
                continue
            if item.get('type') == 'request_performance':
                records.append(item)
except OSError:
    pass
payload = {
    'captured_at': ts,
    'service_name': service,
    'phase': phase,
    'host': {'load': load, 'memory': memory},
    'processes': processes.splitlines()[:40],
    'mysql_diagnostics': mysql[:65536],
    'request_performance': records[-1000:],
}
with open(path, 'w', encoding='utf-8') as handle:
    json.dump(payload, handle, ensure_ascii=False, separators=(',', ':'))
print(json.dumps(payload, ensure_ascii=False, separators=(',', ':')))
PY
    rm -f "$perf_file"
    trap - EXIT HUP INT TERM
}

emit_event() {
    event_type="$1"
    service="$2"
    incident_key="$3"
    started_at="$4"
    cpu="$5"
    mem="$6"
    pids="$7"
    peak_cpu="$8"
    peak_mem="$9"
    evidence_path="${10:-}"
    evidence=""
    if [ -n "$evidence_path" ] && [ -r "$evidence_path" ]; then
        evidence="$(cat "$evidence_path")"
    fi
    python3 - "$event_type" "$timestamp" "$service" "$incident_key" "$started_at" "$cpu" "$mem" "$pids" "$peak_cpu" "$peak_mem" "$load" "$memory" "$evidence" <<'PY'
import json
import sys

event_type, ts, service, key, started_at, cpu, mem, pids, peak_cpu, peak_mem, load, memory, evidence = sys.argv[1:]
payload = {
    'type': event_type, 'ts': ts, 'source': 'host', 'service_name': service,
    'incident_key': key, 'started_at': started_at,
    'cpu_percent': float(cpu), 'memory_percent': float(mem), 'pids': int(float(pids or 0)),
    'peak_cpu_percent': float(peak_cpu), 'peak_memory_percent': float(peak_mem),
    'host_load': load, 'host_memory': memory,
}
if evidence:
    try:
        payload['evidence'] = json.loads(evidence)
    except json.JSONDecodeError:
        payload['evidence'] = {'capture_error': 'invalid evidence JSON'}
print(json.dumps(payload, ensure_ascii=False, separators=(',', ':')))
PY
}

for service in mysql-8_mysql forbes_app sec-forbes_app; do
    row="$(row_for_service "$service")"
    [ -n "$row" ] || continue
    name="$(printf '%s' "$row" | cut -d'|' -f1)"
    cpu="$(printf '%s' "$row" | cut -d'|' -f2 | tr -d '%')"
    mem="$(printf '%s' "$row" | cut -d'|' -f3 | tr -d '%')"
    pids="$(printf '%s' "$row" | cut -d'|' -f4)"
    case "$cpu:$mem:$pids" in *[!0-9.:]*) continue ;; esac
    state_file="$MONITOR_STATE_DIR/incident-${service}.state"
    incident_key=""; started_at=""; peak_cpu=0; peak_mem=0; recovery=0
    if [ -r "$state_file" ]; then
        IFS='|' read -r incident_key started_at peak_cpu peak_mem recovery < "$state_file" || true
    fi
    high="$(awk -v value="$cpu" -v threshold="$MONITOR_CPU_THRESHOLD" 'BEGIN { print value >= threshold ? 1 : 0 }')"
    peak_cpu="$(awk -v old="$peak_cpu" -v value="$cpu" 'BEGIN { print old > value ? old : value }')"
    peak_mem="$(awk -v old="$peak_mem" -v value="$mem" 'BEGIN { print old > value ? old : value }')"

    if [ -z "$incident_key" ] && [ "$high" = 1 ]; then
        incident_key="${service}-${now_epoch}-$$"
        started_at="$timestamp"
        recovery=0
        capture_evidence "$service" open "5 minutes ago" >/dev/null
        evidence="$(find "$MONITOR_LOG_DIR" -maxdepth 1 -type f -name "incident-${service}-open-*.json" -print | sort | tail -n 1)"
        printf '%s|%s|%s|%s|%s\n' "$incident_key" "$started_at" "$peak_cpu" "$peak_mem" "$recovery" > "$state_file"
        emit "$(emit_event performance_spike_open "$service" "$incident_key" "$started_at" "$cpu" "$mem" "$pids" "$peak_cpu" "$peak_mem" "$evidence")"
        continue
    fi

    [ -n "$incident_key" ] || continue
    if [ "$high" = 1 ]; then recovery=0; else recovery=$((recovery + 1)); fi
    printf '%s|%s|%s|%s|%s\n' "$incident_key" "$started_at" "$peak_cpu" "$peak_mem" "$recovery" > "$state_file"
    emit "$(emit_event performance_spike_sample "$service" "$incident_key" "$started_at" "$cpu" "$mem" "$pids" "$peak_cpu" "$peak_mem")"
    if [ "$recovery" -ge "$MONITOR_RECOVERY_SAMPLES" ]; then
        capture_evidence "$service" close "$started_at" >/dev/null
        evidence="$(find "$MONITOR_LOG_DIR" -maxdepth 1 -type f -name "incident-${service}-close-*.json" -print | sort | tail -n 1)"
        emit "$(emit_event performance_spike_close "$service" "$incident_key" "$started_at" "$cpu" "$mem" "$pids" "$peak_cpu" "$peak_mem" "$evidence")"
        rm -f "$state_file"
    fi
done

find "$MONITOR_LOG_DIR" -maxdepth 1 -type f -name 'resource-*.jsonl' -mtime +1 -exec gzip -f {} \;
find "$MONITOR_LOG_DIR" -maxdepth 1 -type f \( -name 'resource-*.jsonl.gz' -o -name 'incident-*.json' \) -mtime +"$MONITOR_RETENTION_DAYS" -delete
