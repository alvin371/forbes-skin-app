-- ENDORSE REFRESH INCIDENT REPAIR PROPOSAL — REVIEW ONLY, DO NOT RUN DIRECTLY.
--
-- Required gates before any write:
--   1. pause cron, manual force callers, monitors, and every queue consumer;
--   2. wait past the maximum valid request path and prove no request is active;
--   3. take a consistent dump of the selected queue/attempt rows and checksum it;
--   4. record the exact counts emitted by the read-only section below;
--   5. replace the incident placeholders in a reviewed copy of this file.

-- ---------------------------------------------------------------------------
-- READ-ONLY PREVIEW. Safe to run in a read-only transaction after callers pause.
-- ---------------------------------------------------------------------------
SET TRANSACTION READ ONLY;
START TRANSACTION WITH CONSISTENT SNAPSHOT;

SELECT NOW(6) AS preview_at,
       @@hostname AS db_host,
       @@transaction_isolation AS isolation_level;

SELECT q.status, COUNT(*) AS queue_count, MIN(q.created_at) AS oldest_created_at
FROM endorse_refresh_queue q
GROUP BY q.status
ORDER BY q.status;

SELECT 'attempts_lt_max' AS invariant_name, COUNT(*) AS affected_rows
FROM endorse_refresh_queue q
JOIN (
    SELECT queue_id, MAX(attempt_no) AS max_attempt_no
    FROM endorse_refresh_queue_attempts
    GROUP BY queue_id
) a ON a.queue_id = q.id
WHERE q.attempts < a.max_attempt_no
UNION ALL
SELECT 'attempt_sequence_lt_max', COUNT(*)
FROM endorse_refresh_queue q
JOIN (
    SELECT queue_id, MAX(attempt_no) AS max_attempt_no
    FROM endorse_refresh_queue_attempts
    GROUP BY queue_id
) a ON a.queue_id = q.id
WHERE q.attempt_sequence < a.max_attempt_no
UNION ALL
SELECT 'processing_without_active_attempt', COUNT(*)
FROM endorse_refresh_queue q
LEFT JOIN endorse_refresh_queue_attempts a
  ON a.id = q.active_attempt_id
 AND a.queue_id = q.id
 AND a.status = 'processing'
WHERE q.status = 'processing' AND a.id IS NULL
UNION ALL
SELECT 'completed_without_completed_attempt', COUNT(*)
FROM endorse_refresh_queue q
WHERE q.status = 'completed'
  AND NOT EXISTS (
      SELECT 1 FROM endorse_refresh_queue_attempts a
      WHERE a.queue_id = q.id AND a.status = 'completed'
  )
UNION ALL
SELECT 'multiple_processing_attempts', COUNT(*)
FROM (
    SELECT queue_id
    FROM endorse_refresh_queue_attempts
    WHERE status = 'processing'
    GROUP BY queue_id
    HAVING COUNT(*) > 1
) duplicate_attempts
UNION ALL
SELECT 'duplicate_active_business_scope', COUNT(*)
FROM (
    SELECT id_endorse, purpose
    FROM endorse_refresh_queue
    WHERE status IN ('pending','processing','submitted')
    GROUP BY id_endorse, purpose
    HAVING COUNT(*) > 1
) duplicate_scopes;

-- Candidate IDs are deliberately explicit so the dump and repair operate on
-- the identical set. Export these IDs and do not recompute them mid-repair.
SELECT q.id AS candidate_queue_id,
       q.status,
       q.attempts,
       q.attempt_sequence,
       COALESCE(MAX(a.attempt_no), 0) AS max_attempt_no,
       SUM(CASE WHEN a.status <> 'cancelled' THEN 1 ELSE 0 END) AS consumed_attempts,
       q.active_attempt_id,
       q.worker_id,
       q.lease_expires_at
FROM endorse_refresh_queue q
LEFT JOIN endorse_refresh_queue_attempts a ON a.queue_id = q.id
GROUP BY q.id
HAVING q.attempt_sequence <> max_attempt_no
    OR q.attempts <> consumed_attempts
    OR (q.status = 'processing' AND q.active_attempt_id IS NULL)
ORDER BY q.id;

ROLLBACK;

-- ---------------------------------------------------------------------------
-- SNAPSHOT COMMAND SHAPE — execute outside MySQL only after freezing IDs.
-- ---------------------------------------------------------------------------
-- mysqldump --single-transaction --skip-lock-tables DATABASE endorse_refresh_queue
--   --where='id IN (<FROZEN_QUEUE_IDS>)' > incident_queue.sql
-- mysqldump --single-transaction --skip-lock-tables DATABASE endorse_refresh_queue_attempts
--   --where='queue_id IN (<FROZEN_QUEUE_IDS>)' > incident_attempts.sql
-- sha256sum incident_queue.sql incident_attempts.sql

-- ---------------------------------------------------------------------------
-- MUTATING TEMPLATE — INTENTIONALLY COMMENTED OUT.
-- Copy one ascending chunk of at most 500 frozen IDs into a separately reviewed
-- file. Assert every ROW_COUNT() before COMMIT; any mismatch means ROLLBACK.
--
-- Every scheduling column below is written with NOW(6), matching
-- EndorseRefreshQueueService::schedulingNowSql(). Do not substitute
-- UTC_TIMESTAMP(6): the application reads these columns back against NOW(6), so
-- on a non-UTC server a UTC-written next_attempt_at is already in the past and a
-- repaired row becomes instantly eligible.
-- ---------------------------------------------------------------------------
/*
START TRANSACTION;

SELECT id
FROM endorse_refresh_queue
WHERE id IN (<CHUNK_OF_FROZEN_QUEUE_IDS>)
ORDER BY id
FOR UPDATE;

-- Last allocated number is MAX(attempt_no), never COUNT(*).
UPDATE endorse_refresh_queue q
LEFT JOIN (
    SELECT queue_id,
           MAX(attempt_no) AS max_attempt_no,
           SUM(CASE WHEN status <> 'cancelled' THEN 1 ELSE 0 END) AS consumed_attempts
    FROM endorse_refresh_queue_attempts
    WHERE queue_id IN (<CHUNK_OF_FROZEN_QUEUE_IDS>)
    GROUP BY queue_id
) a ON a.queue_id = q.id
SET q.attempt_sequence = COALESCE(a.max_attempt_no, 0),
    q.attempts = COALESCE(a.consumed_attempts, 0)
WHERE q.id IN (<CHUNK_OF_FROZEN_QUEUE_IDS>);

-- Expired processing claims require per-row review. Close only an attempt whose
-- id equals parent.active_attempt_id and whose lease is older than <STALE_BEFORE>.
UPDATE endorse_refresh_queue_attempts a
JOIN endorse_refresh_queue q ON q.active_attempt_id = a.id AND q.id = a.queue_id
SET a.status = 'timed_out',
    a.error_class = 'infra_stall',
    a.error_message = 'Incident repair: expired claim lease',
    a.finished_at = NOW(6)
WHERE q.id IN (<EXACT_EXPIRED_PROCESSING_IDS>)
  AND q.status = 'processing'
  AND a.status = 'processing'
  AND q.lease_expires_at < '<STALE_BEFORE>';

-- Parent release must be the same reviewed ID set and must preserve max-attempt
-- exhaustion. Split retryable and exhausted IDs; never reset every processing row.
UPDATE endorse_refresh_queue
SET status = 'pending', worker_id = NULL, claim_owner = NULL,
    active_attempt_id = NULL, started_at = NULL, lease_expires_at = NULL,
    claimed_at = NOW(6), next_attempt_at = DATE_ADD(NOW(6), INTERVAL 60 SECOND),
    error_message = 'Incident repair: expired claim scheduled for retry'
WHERE id IN (<EXACT_RETRYABLE_EXPIRED_IDS>)
  AND status = 'processing';

UPDATE endorse_refresh_queue
SET status = 'failed', worker_id = NULL, claim_owner = NULL,
    active_attempt_id = NULL, started_at = NULL, lease_expires_at = NULL,
    completed_at = NOW(6), next_attempt_at = NULL,
    error_message = 'Incident repair: maximum attempts exhausted'
WHERE id IN (<EXACT_EXHAUSTED_IDS>)
  AND status = 'processing';

-- Completed parents lacking history require a separate reviewed INSERT containing
-- one explicit synthetic completed attempt per frozen ID. Use error_class
-- 'internal_reconciled' and parent timestamps; never rewrite an older retrying row.

-- Repeat all preview invariants for this chunk here. They must be zero.
COMMIT;
*/

-- Rollback/recovery: keep all invokers paused, restore the two dumps in a clean
-- transaction, remove only synthetic rows identified by the incident marker, rerun
-- every invariant query, then resume with one low-volume canary consumer.
