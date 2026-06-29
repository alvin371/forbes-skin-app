<?php
/**
 * Migration: Composite/date indexes for the dashboard + overview hot paths.
 *
 * Load tests showed a latency knee at ~2-5 concurrent users. The dashboard
 * (Dashboard.php) and overview/summary endpoints (Ajax.php, Overview.php) filter
 * large fact tables by a date range. Those WHEREs were wrapped in DATE(col),
 * which is non-sargable and forced full table scans; that wrapping has now been
 * rewritten to half-open ranges (col >= start AND col < until+1day), so these
 * indexes can finally drive row selection.
 *
 * Index choices:
 *  - transaction (type_sub, date): the POS analytics queries all filter
 *    type_sub='POS' (equality) + a date range. Equality-then-range is optimal.
 *    Plus a plain (date) for the few date-only scans.
 *  - payment_logs (status_payment, created_at): KOL spend filters
 *    status_payment IN ('FP','DP') (equality set) + created_at range.
 *  - stock (date): HPP queries filter s.date range + type/type_sub.
 *  - *_ads_data / advertiser_spend (date): ad-spend aggregation by date range.
 *  - endorse (posting_at): summary GROUP BY influencer counts over a posting_at
 *    range with no id_campaign filter (existing index leads with id_campaign).
 *
 * Guard pattern matches 20260622020000_add_influencer_username_index.php, with an
 * extra column-existence check so a table whose schema differs is skipped, not
 * errored.
 *
 * INTERIM ONLY: supports the live CI3/MySQL app until the Postgres migration.
 *
 * Run:  php migrations/run.php 20260626140000_add_loadtest_sargable_date_indexes.php
 * Down: php migrations/run.php 20260626140000_add_loadtest_sargable_date_indexes.php down
 */

$indexes = [
    ['transaction',       'idx_trx_typesub_date',    '(`type_sub`, `date`)'],
    ['transaction',       'idx_trx_date',            '(`date`)'],
    ['payment_logs',      'idx_pl_status_created',    '(`status_payment`, `created_at`)'],
    ['stock',             'idx_stock_date',          '(`date`)'],
    ['shopee_ads_data',   'idx_shopee_ads_date',     '(`date`)'],
    ['meta_ads_data',     'idx_meta_ads_date',       '(`date`)'],
    ['tiktok_ads_data',   'idx_tiktok_ads_date',     '(`date`)'],
    ['advertiser_spend',  'idx_advertiser_spend_date','(`date`)'],
    ['endorse',           'idx_endorse_posting_at',  '(`posting_at`)'],
];

$columns_of = function ($pdo, $cols) {
    // cols like "(`type_sub`, `date`)" -> ['type_sub','date']
    preg_match_all('/`([^`]+)`/', $cols, $m);
    return $m[1];
};

$column_exists = function ($pdo, $table, $column) {
    $rows = $pdo->query("SHOW COLUMNS FROM `$table` LIKE " . $pdo->quote($column))->fetchAll();
    return !empty($rows);
};

if ($direction === 'down') {
    foreach ($indexes as [$table, $name, $cols]) {
        $tableExists = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table))->fetchAll();
        if (empty($tableExists)) { echo "Table $table does not exist, skipping.\n"; continue; }
        $exists = $pdo->query("SHOW INDEX FROM `$table` WHERE Key_name = " . $pdo->quote($name))->fetchAll();
        if (!empty($exists)) {
            $pdo->exec("ALTER TABLE `$table` DROP INDEX `$name`");
            echo "Dropped index $table.$name.\n";
        } else {
            echo "Index $table.$name does not exist, skipping.\n";
        }
    }
    return;
}

foreach ($indexes as [$table, $name, $cols]) {
    $tableExists = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table))->fetchAll();
    if (empty($tableExists)) { echo "Table $table does not exist, skipping.\n"; continue; }

    $missingCol = false;
    foreach ($columns_of($pdo, $cols) as $col) {
        if (!$column_exists($pdo, $table, $col)) {
            echo "Column $table.$col missing, skipping index $name.\n";
            $missingCol = true;
            break;
        }
    }
    if ($missingCol) { continue; }

    $exists = $pdo->query("SHOW INDEX FROM `$table` WHERE Key_name = " . $pdo->quote($name))->fetchAll();
    if (empty($exists)) {
        $pdo->exec("ALTER TABLE `$table` ADD INDEX `$name` $cols");
        echo "Added index $table.$name.\n";
    } else {
        echo "Index $table.$name already exists, skipping.\n";
    }
}
