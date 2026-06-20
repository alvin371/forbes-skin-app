# endorse_logs — Profiling & migrate_class Classification

Date: 2026-06-20
Purpose: Before the Postgres migration, classify the ~1.3M `endorse_logs` rows into
live / idle / dead / orphan / junk so the ETL can migrate hot data and cold-archive the
rest. **Non-destructive: we tag, never delete.** Classify by *observed behavior*
(last log date), NOT by the user-maintained `status` flag (relations are not maintained,
so `status='Aktif'` lies).

Procedure: **backup → profile (read-only) → add column → classify → verify.**

---

## 0. Backup first (undo for everything below)

```bash
mysqldump -h <host> -u <user> -p <db> \
  endorse endorse_logs endorse_campaign influencer payment_logs \
  > backup_endorse_cluster_2026-06-20.sql
```

---

## 1. Profile (READ-ONLY — run and record numbers BEFORE adding the column)

```sql
-- total rows
SELECT COUNT(*) AS total_rows FROM endorse_logs;

-- orphan logs: parent endorse no longer exists
SELECT COUNT(*) AS orphan_rows
FROM endorse_logs l
LEFT JOIN endorse e ON e.id = l.id_endorse
WHERE e.id IS NULL;

-- junk: endorses whose logs NEVER produced views (peak views_after = 0)
SELECT COUNT(*) AS junk_rows
FROM endorse_logs
WHERE id_endorse IN (
    SELECT id_endorse FROM endorse_logs GROUP BY id_endorse HAVING MAX(views_after) = 0
);

-- per-endorse activity distribution (drives dead/idle/active buckets)
-- last_log = behavioral truth; status = declared (untrusted)
SELECT
    bucket,
    COUNT(*) AS endorses
FROM (
    SELECT
        l.id_endorse,
        MAX(l.date) AS last_log,
        CASE
            WHEN MAX(l.date) >= (CURDATE() - INTERVAL 90 DAY)  THEN 'active'
            WHEN MAX(l.date) <  (CURDATE() - INTERVAL 180 DAY) THEN 'dead'
            ELSE 'idle'   -- between 90 and 180 days
        END AS bucket
    FROM endorse_logs l
    GROUP BY l.id_endorse
) t
GROUP BY bucket;
```

> **Decision gate:** if `dead + orphan + junk` is a large share of 1.3M, proceed to
> classify. If negligible, skip — the table is mostly live and tagging adds no value.

### Recorded numbers (fill in after running)
| metric | count | % of total |
|--------|-------|-----------|
| total_rows | _TBD_ | 100% |
| orphan_rows | _TBD_ | |
| junk_rows | _TBD_ | |
| dead (endorses / rows) | _TBD_ | |
| idle (endorses / rows) | _TBD_ | |
| active (endorses / rows) | _TBD_ | |

---

## 2. Add the tag column

```bash
php migrations/run.php 20260620000100_add_migrate_class_to_endorse_logs.php
```

---

## 3. Classify (set tag only — priority order so each row gets ONE class)

Run in this order; later passes only touch still-NULL rows. Idempotent and re-runnable.

```sql
-- 3a. orphan: no parent endorse
UPDATE endorse_logs l
LEFT JOIN endorse e ON e.id = l.id_endorse
SET l.migrate_class = 'orphan'
WHERE e.id IS NULL AND l.migrate_class IS NULL;

-- 3b. junk: endorse never produced views
UPDATE endorse_logs
SET migrate_class = 'junk'
WHERE migrate_class IS NULL
  AND id_endorse IN (
      SELECT id_endorse FROM (
          SELECT id_endorse FROM endorse_logs GROUP BY id_endorse HAVING MAX(views_after) = 0
      ) x
  );

-- 3c. dead: last log > 180 days ago (per endorse)
UPDATE endorse_logs l
JOIN (
    SELECT id_endorse, MAX(date) AS last_log FROM endorse_logs GROUP BY id_endorse
) m ON m.id_endorse = l.id_endorse
SET l.migrate_class = 'dead'
WHERE l.migrate_class IS NULL
  AND m.last_log < (CURDATE() - INTERVAL 180 DAY);

-- 3d. idle: last log 90-180 days, parent declared Aktif (needs human review)
UPDATE endorse_logs l
JOIN (
    SELECT id_endorse, MAX(date) AS last_log FROM endorse_logs GROUP BY id_endorse
) m ON m.id_endorse = l.id_endorse
JOIN endorse e ON e.id = l.id_endorse
SET l.migrate_class = 'idle'
WHERE l.migrate_class IS NULL
  AND m.last_log < (CURDATE() - INTERVAL 90 DAY)
  AND e.status = 'Aktif';

-- 3e. active: everything else
UPDATE endorse_logs SET migrate_class = 'active' WHERE migrate_class IS NULL;
```

> Note 3c/3d batch by endorse via a join subquery. On 1.3M rows these are large
> UPDATEs — run off-peak (weekend), and if needed chunk by `id` ranges.

---

## 4. Verify the tags

```sql
-- reconcile: per-class counts must sum to total, ZERO untagged
SELECT migrate_class, COUNT(*) FROM endorse_logs GROUP BY migrate_class WITH ROLLUP;
SELECT COUNT(*) AS untagged FROM endorse_logs WHERE migrate_class IS NULL;  -- must be 0

-- spot-check: 10 random rows per class (eyeball — especially 'idle')
SELECT * FROM endorse_logs WHERE migrate_class = 'idle'   ORDER BY RAND() LIMIT 10;
SELECT * FROM endorse_logs WHERE migrate_class = 'dead'   ORDER BY RAND() LIMIT 10;
SELECT * FROM endorse_logs WHERE migrate_class = 'orphan' ORDER BY RAND() LIMIT 10;
SELECT * FROM endorse_logs WHERE migrate_class = 'junk'   ORDER BY RAND() LIMIT 10;
```

Confirm `idle` rows are genuinely abandoned, not a paused seasonal campaign. Record
final counts in the table above.

> The migration ETL consumes this tag: `active` (+ approved `idle`) → hot tables;
> `dead` / `orphan` / `junk` → cold archive. **No DELETE here.**
