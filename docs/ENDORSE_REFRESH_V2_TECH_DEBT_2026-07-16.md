# Endorse Refresh v2 Technical Debt

Date: 2026-07-16
Status: implementation complete for P0 foundation; not production-ready

## P1 release gates still required

- Rehearse paused activation and rollback end to end.
- Verify required indexes and critical queries with real plans and timings.
- Run multi-replica ownership-switch testing.
- Run fallback deduplication lease tests.
- Run provider circuit and worker-health circuit tests, including half-open probe behavior.
- Run load testing and deadlock testing on claim/result paths.
- Measure contention on runtime-control and provider-health hot rows at p50/p95/p99.
- Validate partial-observation downstream behavior:
  - rollups
  - CPM/FYP logic
  - dashboards
  - mixed-time snapshot presentation
- Add and verify metrics and alerts for:
  - active circuits
  - stale claims
  - claim conflicts
  - fallback failures by reason
  - owner-wide worker health
- Rehearse worker secret rotation with overlap support.
- Rehearse drain and force-recovery procedures before any production Rust ownership switch.

## P2 backlog

- Random claim token in addition to active attempt identity.
- Separate share/save storage for daily metrics instead of merged `share_save`.
- Per-field freshness metadata.
- Direct-scrape-only degraded mode while provider circuit is open.
- Hot-row scaling improvements if contention becomes material in P1 testing.

## Known implementation limitations

- The current bridge cron path still exists for legacy mode and production safety.
- Activation tooling for duplicate reconciliation is not yet implemented as a full operator workflow.
- Circuit metrics/alerts and release-gate automation are not yet in this code pass.
- The current migration creates archive/report tables but does not perform paused activation reconciliation by itself.

## Operational rule

Do not switch production ownership to Rust until every P1 gate above has passed and sign-off is complete.
