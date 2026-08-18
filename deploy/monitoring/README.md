# Production performance monitoring

The deploy playbook installs a user-level systemd timer. It writes one JSONL
resource snapshot per minute to `~/monitoring-logs`, persists a compact copy for
the authenticated Queue Diagnostics page, captures process evidence on sustained
high samples, compresses completed files, and deletes its own files after 30 days.

Create `~/.config/forbes-monitor.env` on the server before enabling it:

```sh
MONITOR_CPU_THRESHOLD=80
MONITOR_MEMORY_THRESHOLD=85
MONITOR_CONSECUTIVE_SAMPLES=3
MONITOR_RETENTION_DAYS=30
MONITOR_SPIKE_COOLDOWN_SECONDS=600
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

Dozzle displays Docker stdout/stderr, including the application's `PERF` JSON
records. The Queue Diagnostics page provides the 30-day summary and lineage;
host evidence files remain available through `journalctl --user -u
forbes-resource-monitor` and `~/monitoring-logs/`.
