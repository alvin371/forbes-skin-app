# Production performance monitoring

The deploy playbook installs a user-level systemd timer. It writes one JSONL
resource snapshot per minute to `~/monitoring-logs`, captures process evidence
on the third consecutive high sample, compresses completed files, and deletes
its own files after 14 days.

Create `~/.config/forbes-monitor.env` on the server before enabling it:

```sh
MONITOR_CPU_THRESHOLD=80
MONITOR_MEMORY_THRESHOLD=85
MONITOR_CONSECUTIVE_SAMPLES=3
# Optional: least-privileged MySQL monitoring user. The command must output
# normalized process-list and Performance Schema digest data; never place its
# password in this repository or in JSONL output.
MYSQL_MONITOR_COMMAND=
```

Use `tools/monitoring/mysql_spike_diagnostics.sql` as the command input after
creating a monitoring-only MySQL account with `PROCESS` plus `SELECT` on
`performance_schema`. Keep its credential in a root/user-owned defaults file,
not in this repository.

Dozzle displays Docker stdout/stderr, including the application's `PERF` JSON
records. It does not display host timer files; inspect those with `journalctl
--user -u forbes-resource-monitor` and `~/monitoring-logs/`.
