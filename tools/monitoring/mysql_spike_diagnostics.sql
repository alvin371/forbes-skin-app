-- Read-only statements for the optional MYSQL_MONITOR_COMMAND in
-- resource_snapshot.sh. Run with the least-privileged forbes_monitor account.
SHOW FULL PROCESSLIST;

SELECT VARIABLE_NAME, VARIABLE_VALUE
FROM performance_schema.global_status
WHERE VARIABLE_NAME IN (
    'Threads_connected', 'Threads_running', 'Questions', 'Slow_queries',
    'Created_tmp_disk_tables', 'Created_tmp_tables', 'Handler_read_rnd_next',
    'Innodb_buffer_pool_reads', 'Innodb_row_lock_time', 'Innodb_row_lock_waits'
);

SELECT
    DIGEST_TEXT,
    COUNT_STAR,
    ROUND(SUM_TIMER_WAIT / 1000000000000, 1) AS total_seconds,
    ROUND(AVG_TIMER_WAIT / 1000000000, 1) AS average_ms,
    SUM_ROWS_EXAMINED,
    SUM_ROWS_SENT,
    SUM_NO_INDEX_USED,
    SUM_NO_GOOD_INDEX_USED
FROM performance_schema.events_statements_summary_by_digest
WHERE DIGEST_TEXT IS NOT NULL
ORDER BY SUM_TIMER_WAIT DESC
LIMIT 20;
