# Production performance monitoring

The deploy playbook installs a user-level systemd timer. It samples Docker every
15 seconds and opens one incident per target service as soon as CPU is at or
above 60%. An incident closes only after two consecutive samples below 60%.
The collector retains host JSONL/evidence for 30 days and persists only incident
samples/evidence to the authenticated Performance Spike page; it never writes
one database row for every application request.

Create `~/.config/forbes-monitor.env` on the server before enabling it:

```sh
MONITOR_CPU_THRESHOLD=60
MONITOR_RECOVERY_SAMPLES=2
MONITOR_RETENTION_DAYS=30
MONITOR_EVIDENCE_REQUEST_LIMIT=500
# Optional: least-privileged MySQL monitoring user. The command must output
# normalized process-list and Performance Schema digest data; never place its
# password in this repository or in JSONL output.
MYSQL_MONITOR_COMMAND=
```

Use `tools/monitoring/mysql_spike_diagnostics.sql` as the command input after
creating a monitoring-only MySQL account with `PROCESS` plus `SELECT` on
`performance_schema`. Keep its credential in a mode-0600 server defaults file,
not in this repository. The SQL deliberately records digest text (with literals
replaced by `?`) rather than raw process-list SQL.
`tools/monitoring/mysql_monitor_setup.sql` is the administrator-only setup
template; replace its password placeholder only on the server.

Set `MONITOR_SERVICE_NAME=forbes_app` in the primary application environment and
`MONITOR_SERVICE_NAME=sec-forbes_app` in the secondary application environment.
Both need `MONITOR_REQUEST_LOGGING=true` and `MONITOR_REQUEST_LOG_STDERR=true`.
The hook emits only route, method, status, duration, opaque user ID/role, and
redacted SQL fingerprints; it does not emit request parameters, usernames, or
raw IP addresses.

Dozzle displays the same `PERF` JSON records. The Performance Spike page shows
the 30-day incident timeline, affected service, peak/duration, active-user and
endpoint summaries, and sanitized MySQL evidence. Raw host evidence remains in
`~/monitoring-logs/` and can be correlated through the incident key.
