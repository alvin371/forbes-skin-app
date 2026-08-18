<?php

/**
 * The single canonical endorse-refresh schema for every MySQL integration test.
 *
 * Previously each test class hand-wrote its own CREATE TABLE statements. They drifted from
 * one another and from production: one declared unique keys production lacked, another
 * omitted the ones production has, and a third used a narrower `attempt_no`. Tests then
 * proved invariants against a schema nobody runs.
 *
 * This builds the disposable database by executing the REAL production migration `up()`
 * path in filename order, on top of the real `endorse`/`endorse_campaign` schema dumps.
 * A schema defect therefore surfaces in tests instead of in production.
 */
final class QueueSchema
{
    /**
     * Production migrations that define the endorse-refresh queue contract, in the order
     * migrations/run.php would apply them.
     */
    public const MIGRATIONS = [
        '20260428000000_create_endorse_refresh_queue.php',
        '20260428001000_add_worker_id_to_endorse_refresh_queue.php',
        '20260430000000_add_endorse_refresh_attempts.php',
        '20260615000100_add_purpose_to_endorse_refresh_queue.php',
        '20260716093000_add_endorse_refresh_contract_v2.php',
        '20260727090000_add_threads_scraper_queue_state.php',
        '20260803120000_create_endorse_refresh_rate_tokens.php',
        '20260803130000_add_stats_observation_seq.php',
        '20260818150000_add_endorse_refresh_diagnostics.php',
        '20260818170000_harden_endorse_refresh_claims.php',
    ];

    /**
     * Tables every test resets between cases, in FK-safe order.
     */
    public const DATA_TABLES = [
        'endorse_refresh_queue_attempts',
        'endorse_refresh_queue',
        'endorse_refresh_rate_tokens',
        'endorse_logs',
        'endorse',
        'endorse_campaign_logs',
        'endorse_campaign',
    ];

    /**
     * Build the canonical schema, reusing it when it is already present and intact.
     *
     * Some suites still create their own simulator tables and drop the real ones, so
     * "already built" is decided by inspecting the database rather than by a flag: if the
     * canonical shape is gone, it is rebuilt. Migration helper functions are guarded with
     * function_exists so the files can be executed more than once in one process.
     */
    public static function build(PDO $pdo): void
    {
        // The real dumps carry legacy NOT NULL columns without defaults; every integration
        // connection relaxes strict mode the same way so seeding stays terse and uniform.
        $pdo->exec("SET SESSION sql_mode=''");

        if (self::isIntact($pdo)) {
            self::reset($pdo);

            return;
        }

        self::dropAll($pdo);
        self::loadSqlFile($pdo, __DIR__ . '/../schema/endorse_real_schema.sql');
        self::loadSqlFile($pdo, __DIR__ . '/../schema/campaign_real_schema.sql');

        $direction = 'up';

        foreach (self::MIGRATIONS as $migration) {
            // Migrations echo progress; keep the test output readable.
            ob_start();

            try {
                require __DIR__ . '/../../../migrations/' . $migration;
            } finally {
                ob_end_clean();
            }
        }
    }

    /**
     * The canonical schema is intact when the last migration's own artefacts are present.
     * Cheap enough to run per test class and immune to another suite dropping tables.
     */
    private static function isIntact(PDO $pdo): bool
    {
        foreach (self::DATA_TABLES as $table) {
            if (empty($pdo->query('SHOW TABLES LIKE ' . $pdo->quote($table))->fetchAll())) {
                return false;
            }
        }

        // Columns AND indexes: a leftover minimal table from another suite can carry the
        // hardening indexes while missing most of the production column set.
        foreach (['id_campaign', 'enqueued_by', 'purpose', 'claim_owner', 'lease_expires_at', 'next_attempt_at', 'provider_job_id'] as $column) {
            if (empty($pdo->query('SHOW COLUMNS FROM `endorse_refresh_queue` LIKE ' . $pdo->quote($column))->fetchAll())) {
                return false;
            }
        }

        return ! empty($pdo->query("SHOW INDEX FROM `endorse_refresh_queue` WHERE Key_name = 'uq_active_endorse_purpose'")->fetchAll())
            && ! empty($pdo->query("SHOW INDEX FROM `endorse_refresh_queue_attempts` WHERE Key_name = 'uq_active_queue_attempt'")->fetchAll());
    }

    /**
     * A dedicated sibling database for suites that legitimately need a NON-canonical
     * schema (a pre-migration baseline, or a behaviour simulator). Isolating them keeps
     * the canonical schema intact for every other suite.
     */
    public static function isolatedDatabase(array $cfg, string $suffix): string
    {
        $name = $cfg['db'] . '_' . $suffix;
        $root = new PDO(
            "mysql:host={$cfg['host']};port={$cfg['port']}",
            $cfg['user'],
            $cfg['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
        $root->exec("DROP DATABASE IF EXISTS `{$name}`");
        $root->exec("CREATE DATABASE `{$name}`");

        return $name;
    }

    /**
     * Truncate data without touching the schema, so every test starts from a known state.
     */
    public static function reset(PDO $pdo): void
    {
        $pdo->exec("SET SESSION sql_mode=''");
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

        foreach (self::DATA_TABLES as $table) {
            $pdo->exec("TRUNCATE TABLE `{$table}`");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * The DDL MySQL actually materialised, for schema-contract assertions.
     */
    public static function showCreate(PDO $pdo, string $table): string
    {
        $row = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);

        return (string) ($row[1] ?? '');
    }

    private static function dropAll(PDO $pdo): void
    {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    private static function loadSqlFile(PDO $pdo, string $path): void
    {
        $sql = file_get_contents($path);
        if ($sql === false) {
            throw new RuntimeException("Cannot read schema file {$path}");
        }

        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            if ($statement !== '') {
                $pdo->exec($statement);
            }
        }
    }
}
