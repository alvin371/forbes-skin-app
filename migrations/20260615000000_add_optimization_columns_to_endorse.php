<?php
/**
 * Migration: Add content-optimization columns to endorse.
 *
 * Adds a frozen INITIAL baseline, a frozen FINAL snapshot, growth (final - initial),
 * and the requestor/executor behavioral fields. Metric columns are deliberately
 * SINGULAR (like/comment/share/save/view) to form an isolated namespace distinct from
 * the legacy plural daily columns (likes/views/share_save), so the two never collide.
 * share and save are kept SEPARATE here (legacy merged share_save stays untouched).
 *
 * Variables injected by run.php: $pdo (PDO), $direction (string 'up'|'down')
 *
 * Run:  php migrations/run.php 20260615000000_add_optimization_columns_to_endorse.php
 * Down: php migrations/run.php 20260615000000_add_optimization_columns_to_endorse.php down
 */

if ($direction === 'down') {
    $columns = [
        'is_optimization',
        'optimization_status',
        'request_keyword',
        'device',
        'request_by',
        'request_date',
        'view_growth',
        'save_growth',
        'share_growth',
        'comment_growth',
        'like_growth',
        'final_fetched_at',
        'view_final',
        'save_final',
        'share_final',
        'comment_final',
        'like_final',
        'initial_fetched_at',
        'view_initial',
        'save_initial',
        'share_initial',
        'comment_initial',
        'like_initial',
    ];

    foreach ($columns as $column) {
        $exists = $pdo->query("SHOW COLUMNS FROM `endorse` LIKE " . $pdo->quote($column))->fetchAll();
        if (!empty($exists)) {
            $pdo->exec("ALTER TABLE `endorse` DROP COLUMN `$column`");
            echo "Dropped column endorse.$column.\n";
        } else {
            echo "Column endorse.$column does not exist, skipping.\n";
        }
    }
    return;
}

// Columns are appended at the end of the table (no AFTER clause) so the migration is
// portable regardless of which optional TikTok-media columns exist on this database.
$columns = [
    // Frozen INITIAL baseline (captured once, at first content link entry)
    'like_initial'        => "ALTER TABLE `endorse` ADD COLUMN `like_initial` INT NULL",
    'comment_initial'     => "ALTER TABLE `endorse` ADD COLUMN `comment_initial` INT NULL",
    'share_initial'       => "ALTER TABLE `endorse` ADD COLUMN `share_initial` INT NULL",
    'save_initial'        => "ALTER TABLE `endorse` ADD COLUMN `save_initial` INT NULL",
    'view_initial'        => "ALTER TABLE `endorse` ADD COLUMN `view_initial` INT NULL",
    'initial_fetched_at'  => "ALTER TABLE `endorse` ADD COLUMN `initial_fetched_at` DATETIME NULL",

    // Frozen FINAL snapshot (captured when optimization_status -> Completed)
    'like_final'          => "ALTER TABLE `endorse` ADD COLUMN `like_final` INT NULL",
    'comment_final'       => "ALTER TABLE `endorse` ADD COLUMN `comment_final` INT NULL",
    'share_final'         => "ALTER TABLE `endorse` ADD COLUMN `share_final` INT NULL",
    'save_final'          => "ALTER TABLE `endorse` ADD COLUMN `save_final` INT NULL",
    'view_final'          => "ALTER TABLE `endorse` ADD COLUMN `view_final` INT NULL",
    'final_fetched_at'    => "ALTER TABLE `endorse` ADD COLUMN `final_fetched_at` DATETIME NULL",

    // Growth = final - initial
    'like_growth'         => "ALTER TABLE `endorse` ADD COLUMN `like_growth` INT NULL",
    'comment_growth'      => "ALTER TABLE `endorse` ADD COLUMN `comment_growth` INT NULL",
    'share_growth'        => "ALTER TABLE `endorse` ADD COLUMN `share_growth` INT NULL",
    'save_growth'         => "ALTER TABLE `endorse` ADD COLUMN `save_growth` INT NULL",
    'view_growth'         => "ALTER TABLE `endorse` ADD COLUMN `view_growth` INT NULL",

    // Behavioral fields (requestor/executor inputs)
    'request_date'        => "ALTER TABLE `endorse` ADD COLUMN `request_date` DATE NULL",
    'request_by'          => "ALTER TABLE `endorse` ADD COLUMN `request_by` VARCHAR(100) NULL",
    'device'              => "ALTER TABLE `endorse` ADD COLUMN `device` VARCHAR(100) NULL",
    'request_keyword'     => "ALTER TABLE `endorse` ADD COLUMN `request_keyword` VARCHAR(255) NULL",
    'optimization_status' => "ALTER TABLE `endorse` ADD COLUMN `optimization_status` VARCHAR(20) NULL",
    'is_optimization'     => "ALTER TABLE `endorse` ADD COLUMN `is_optimization` TINYINT(1) NOT NULL DEFAULT 0",
];

foreach ($columns as $column => $sql) {
    $exists = $pdo->query("SHOW COLUMNS FROM `endorse` LIKE " . $pdo->quote($column))->fetchAll();
    if (empty($exists)) {
        $pdo->exec($sql);
        echo "Added column endorse.$column.\n";
    } else {
        echo "Column endorse.$column already exists, skipping.\n";
    }
}

// Index to make the optimization list view + reconcile sweep efficient.
$indexExists = $pdo->query("SHOW INDEX FROM `endorse` WHERE Key_name = 'idx_optimization'")->fetchAll();
if (empty($indexExists)) {
    $pdo->exec("ALTER TABLE `endorse` ADD INDEX `idx_optimization` (`is_optimization`, `optimization_status`)");
    echo "Added index endorse.idx_optimization.\n";
} else {
    echo "Index endorse.idx_optimization already exists, skipping.\n";
}
