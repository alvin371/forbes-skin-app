<?php
/**
 * Migration: Index user.username and user.email for the login path.
 *
 * Auth::login_process() looks the user up with
 *   SELECT * FROM user WHERE username = ?
 * and the duplicate-account checks in Auth use
 *   SELECT ... FROM user WHERE email = ? AND id != ?
 * Neither `username` nor `email` had an index, so every login (and every
 * signup/profile-edit duplicate check) did a full table scan of `user`.
 * As the user table grows this adds latency to the blocking login POST.
 *
 * Plain (non-unique) indexes: the app does not enforce uniqueness at the DB
 * level today and historical data may contain duplicates, so a UNIQUE index
 * could fail to build. A normal index still makes the equality lookup an
 * index seek. The table is small enough that these build online instantly.
 *
 * Same guard pattern as 20260622020000_add_influencer_username_index.php.
 *
 * INTERIM ONLY: supports the live CI3/MySQL app until the Postgres migration.
 *
 * Run:  php migrations/run.php 20260626120000_add_user_login_indexes.php
 * Down: php migrations/run.php 20260626120000_add_user_login_indexes.php down
 */

$indexes = [
    ['user', 'idx_user_username', '(`username`)'],
    ['user', 'idx_user_email', '(`email`)'],
];

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
    $exists = $pdo->query("SHOW INDEX FROM `$table` WHERE Key_name = " . $pdo->quote($name))->fetchAll();
    if (empty($exists)) {
        $pdo->exec("ALTER TABLE `$table` ADD INDEX `$name` $cols");
        echo "Added index $table.$name.\n";
    } else {
        echo "Index $table.$name already exists, skipping.\n";
    }
}
