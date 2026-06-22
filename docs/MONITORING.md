# Monitoring

This repository now includes repo-side instrumentation for the hot production paths that are currently driving load:

- request-level JSON logging from `index.php`
- structured cron/job logging in `application/controllers/Api_v2.php`
- a backlog exporter script for Prometheus textfile metrics
- a cron wrapper template for structured host-side cron logs

## App Request Log

HTTP requests append JSON lines to:

- `application/logs/monitor-YYYY-MM-DD.log`

Each line includes:

- `request_id`
- `method`
- `uri`
- `status_code`
- `duration_ms`
- `peak_memory_mb`
- `remote_addr`

`index.php` also emits `X-Request-Id` on HTTP responses.

## Cron/Worker Log

The hot `Api_v2` jobs write `job_start` and `job_finish` records into the same daily monitor log:

- `cronjob_endorse`
- `cronjob_endorse_campaign`
- `cronjob_influencer`
- `cronjob_influencer_dummy`
- `cronjob_scraping_poll`
- `cronjob_endorse_refresh`
- `cronjob_notification_dispatch`

The finish record includes duration and workload counters such as `processed_count`, `queue_count`, and job-specific counts.

## Backlog Exporter

Run locally or on the server:

```bash
php tools/monitoring/backlog_snapshot.php
```

Write to a node-exporter textfile directory:

```bash
php tools/monitoring/backlog_snapshot.php /var/lib/node_exporter/textfile_collector/forbes_backlog.prom
```

Current gauges:

- `forbes_endorse_due_total`
- `forbes_endorse_campaign_active_total`
- `forbes_influencer_due_total`
- `forbes_influencer_dummy_due_total`
- `forbes_scraping_queue_ready_total`
- `forbes_notification_pending_total`
- `forbes_monitor_snapshot_timestamp_seconds`

The exporter reads DB settings from this project’s root `.env` using the same simple key/value format used by the app.

## Cron Wrapper

`tools/monitoring/cron_probe.sh` is a template wrapper for host cron entries. It:

- sends `X-Request-Id`
- measures duration
- records HTTP status
- stores a short response preview
- appends JSONL to `/var/log/cron/structured/<app>.jsonl`

Example:

```bash
tools/monitoring/cron_probe.sh forbes-app endorse https://acnenosystem.com/api/cronjob/endorse
```
