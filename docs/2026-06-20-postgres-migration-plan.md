# Postgres Migration Plan — Endorse Cluster (target design)

Date: 2026-06-20
Status: **Design doc only** — no code yet. Captures the target schema + ETL + cutover so
the new Go/Rust app is built against a clean, normalized schema.

Principle: **normalize at the migration boundary, not in the live CI3 schema.** Rewriting
CI3's hand-written concat SQL in place is throwaway work (the app is being replaced) and
high-risk (no tests). The clean model below is built once, and the data is transformed
into it during the one-time ETL.

---

## 1. Why the current schema is the way it is (carry-over context)

`endorse_logs` (~1.3M rows) is a daily time-series snapshot, one row per endorse per day.
It is over-wide for two reasons:

1. **Derivable columns (3NF violation).** Each metric is stored 3×:
   `*_after` (measured), `*_before` (= yesterday's `*_after`, copied), and the bare delta
   (`= after - before`). Only `*_after` is a true measurement; before/delta are computable.
2. **Copied parent attributes.** `link_upload` (TEXT!), `brand`, `platform`, `status`,
   `status_campaign` are copied onto every daily row though they belong to `endorse`.
   These are write-only in the logs (reads JOIN to `endorse`).

Net: a clean fact row is ~8 narrow columns vs ~27 wide → 40–60% narrower, which is the
permanent buffer-pool win in Postgres.

Also: the campaign rollup (`Endorse.php:1631`, `Endorse_campaign.php:368`,
`Endorse_sync.php:445`) `SUM`s **all** log history per campaign with no date filter — an
expensive scan that also blocks archiving. Target replaces it with a materialized view.

---

## 2. Target schema (Postgres)

```sql
CREATE TABLE influencer (             -- deduped during ETL
    id          BIGINT PRIMARY KEY,
    ...
);

CREATE TABLE campaign (
    id          BIGINT PRIMARY KEY,
    ...
);

CREATE TABLE endorse (
    id            BIGINT PRIMARY KEY,
    campaign_id   BIGINT NOT NULL REFERENCES campaign(id),
    influencer_id BIGINT NOT NULL REFERENCES influencer(id),
    link_upload   TEXT,
    platform      TEXT,
    brand         TEXT,
    status        TEXT,
    -- current snapshot metrics live here (one row/endorse), not on every daily log
    ...
);

-- Narrow fact table: ONLY the measured ("after") values.
CREATE TABLE endorse_metric_daily (
    endorse_id  BIGINT NOT NULL REFERENCES endorse(id),
    date        DATE   NOT NULL,
    views       BIGINT,
    likes       BIGINT,
    comment     BIGINT,
    share_save  BIGINT,
    cpm         NUMERIC,
    cost        NUMERIC,
    PRIMARY KEY (endorse_id, date)
) PARTITION BY RANGE (date);          -- monthly partitions; cheap archive/drop
```

### Derived data (never stored)
- `*_before` and deltas → computed with window functions:
  ```sql
  CREATE VIEW endorse_metric_daily_delta AS
  SELECT *,
    views - LAG(views) OVER (PARTITION BY endorse_id ORDER BY date) AS views_delta,
    likes - LAG(likes) OVER (PARTITION BY endorse_id ORDER BY date) AS likes_delta
    -- ... comment, share_save, cpm
  FROM endorse_metric_daily;
  ```
- Campaign totals → **materialized view** refreshed on schedule (replaces the all-history
  per-campaign `SUM` rollup):
  ```sql
  CREATE MATERIALIZED VIEW campaign_totals AS
  SELECT e.campaign_id,
         SUM(m.views) AS views, SUM(m.likes) AS likes, SUM(m.cost) AS cost
  FROM endorse_metric_daily m JOIN endorse e ON e.id = m.endorse_id
  GROUP BY e.campaign_id;
  ```

### Integrity (CI3 enforces none today)
- Real FK constraints on `endorse`, `endorse_metric_daily`.
- `payment_logs` currently links influencer **by name** (`nama_influencer`) → switch to
  `influencer_id` FK during ETL.

### Scale option
`endorse_metric_daily` is a textbook **TimescaleDB hypertable** if metric volume grows —
partition + compression + continuous aggregates out of the box.

---

## 3. ETL + cleanup

1. **Consume the `migrate_class` tag** (built per `2026-06-20-endorse-logs-profile.md`):
   - `active` (+ human-approved `idle`) → hot `endorse_metric_daily`.
   - `dead` / `orphan` / `junk` → cold archive table/partition (or skip, per decision).
2. **Transform**: keep only `*_after` values as the fact metrics; drop before/delta and
   copied parent attrs (recompute via views).
3. **Cleanup**: dedupe `influencer`; repair `payment_logs` name → `influencer_id`;
   enforce FK constraints (fail loudly on orphans — they should already be tagged).

---

## 4. Cutover + safety

- **Freeze or delta-resync.** Crons keep writing MySQL between snapshot and cutover.
  Either freeze writes during a short window, OR re-sync rows changed after the snapshot
  (`updated_at` / `created_at` watermark) before flipping the app.
- **Parity check** after load:
  - row counts: MySQL (active+idle) == Postgres `endorse_metric_daily`.
  - `campaign_totals` (Postgres) == legacy campaign rollup numbers (old app), spot-checked.
  - a few business metrics (top campaigns' total views/cost) match old vs new.
- **Rollback.** Keep MySQL live and read-only until Postgres is verified green — that is
  the undo. Never delete the source until parity is signed off.

---

## 5. Scope note

`endorse_logs` is the **pilot**. Repeat the same profile → tag → verify → ETL method for
the other big/messy tables (`transaction` across all marketplaces, `stock`) before their
migration. Each gets its own `migrate_class` pass.
