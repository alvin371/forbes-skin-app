<?php
/**
 * Collapse duplicate (queue_id, attempt_no) rows in endorse_refresh_queue_attempts.
 *
 * Usage:
 *   php tools/maintenance/dedup_queue_attempts.php --dry-run   Report only. Writes nothing.
 *   php tools/maintenance/dedup_queue_attempts.php --archive   Copy the surplus rows aside.
 *   php tools/maintenance/dedup_queue_attempts.php --delete    Remove the surplus rows.
 *
 * WHY THIS IS A TOOL AND NOT A MIGRATION
 *
 * `migrations/run.php --pending` runs on every container start (docker-entrypoint.sh:13-19).
 * A destructive data repair must never be reachable that way: it would fire on whichever
 * tenant's .env happened to be mounted, on a schedule nobody chose. So this is an explicit,
 * operator-invoked tool, and the schema migration that needs it (contract_v2's unique index on
 * (queue_id, attempt_no)) simply fails loudly until it has been run.
 *
 * WHAT THE DUPLICATES ARE
 *
 * Measured on forbes_app_sec 2026-08-20: 20,616 duplicate groups / 88,640 surplus rows, of
 * which 87% carry "Worker stalled; item returned to pending queue". resetStuck() returns a
 * stalled queue row to `pending` without incrementing the queue row's `attempts` counter, so
 * the next claim writes another attempt row bearing the SAME attempt_no. Genuinely-dead posts
 * ("Url parsing is failed") appear only in single digits.
 *
 * WHY MAX(id) IS THE ROW WE KEEP
 *
 * Endorse_analytics_read_model:197-205 reads the newest attempt per queue via MAX(id). Keeping
 * the highest id in each group therefore leaves every user-visible analytic untouched. Verified
 * safe besides: this table carries no views/likes/comments (update_campaign_parent sums from
 * `endorse`, not from here) and has zero foreign keys pointing at it.
 *
 * SAFETY
 *
 * --delete refuses to run unless an archive covering exactly the rows it is about to remove
 * already exists, and it recounts immediately before deleting so a concurrent writer that
 * changed the picture aborts the run rather than silently deleting a different set.
 */

if (PHP_SAPI !== 'cli') {
    exit("Run from command line only.\n");
}

$mode = $argv[1] ?? '';
if (!in_array($mode, ['--dry-run', '--archive', '--delete'], true)) {
    fwrite(STDERR, "Usage: php tools/maintenance/dedup_queue_attempts.php --dry-run|--archive|--delete\n");
    exit(2);
}

// ---- Load .env manually (same approach as migrations/run.php: no BASEPATH dependency) ----
$envFile = __DIR__ . '/../../.env';
$env     = [];
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $env[trim($name)] = trim(trim($value), '"\'');
    }
} else {
    fwrite(STDERR, "Error: .env not found at {$envFile}\n");
    exit(1);
}

$dbname = $env['DB_DATABASE'] ?? '';
if ($dbname === '') {
    fwrite(STDERR, "Error: DB_DATABASE is not set in .env\n");
    exit(1);
}

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
    $env['DB_HOSTNAME'] ?? '127.0.0.1',
    (int) ($env['DB_PORT'] ?? 3306),
    $dbname
);

try {
    $pdo = new PDO($dsn, $env['DB_USERNAME'] ?? 'root', $env['DB_PASSWORD'] ?? '', [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, 'Error: cannot connect: ' . $e->getMessage() . "\n");
    exit(1);
}

const SOURCE_TABLE  = 'endorse_refresh_queue_attempts';
const ARCHIVE_TABLE = 'endorse_refresh_queue_attempts_archive';

/**
 * Surplus rows are every row in a duplicate group EXCEPT the highest id in that group.
 *
 * Expressed as a join against the per-group maximum rather than a correlated subquery: MySQL
 * materialises the derived table once, where the correlated form re-runs per candidate row and
 * turned a seconds-long scan into minutes on 745k rows.
 */
function surplus_row_sql(string $selectList): string
{
    $src = SOURCE_TABLE;

    // Alias is `src`, deliberately NOT `a`: the DELETE below aliases its own target table and a
    // shared alias would make the derived table ambiguous against it.
    return "
        SELECT {$selectList}
          FROM {$src} src
          JOIN (
                SELECT queue_id, attempt_no, MAX(id) AS keep_id, COUNT(*) AS n
                  FROM {$src}
                 GROUP BY queue_id, attempt_no
                HAVING n > 1
          ) d ON d.queue_id = src.queue_id AND d.attempt_no = src.attempt_no
         WHERE src.id <> d.keep_id
    ";
}

function count_surplus(PDO $pdo): array
{
    $row = $pdo->query('SELECT COUNT(*) AS surplus FROM (' . surplus_row_sql('src.id') . ') s')->fetch();
    $grp = $pdo->query('
        SELECT COUNT(*) AS groups_n FROM (
            SELECT queue_id FROM ' . SOURCE_TABLE . '
             GROUP BY queue_id, attempt_no HAVING COUNT(*) > 1
        ) g
    ')->fetch();

    return ['surplus' => (int) $row['surplus'], 'groups' => (int) $grp['groups_n']];
}

function archive_exists(PDO $pdo): bool
{
    return $pdo->query("SHOW TABLES LIKE '" . ARCHIVE_TABLE . "'")->fetch() !== false;
}

printf("database   : %s\n", $dbname);
printf("mode       : %s\n", $mode);
printf("started at : %s\n\n", date('Y-m-d H:i:s'));

$before = count_surplus($pdo);
printf("duplicate groups : %d\n", $before['groups']);
printf("surplus rows     : %d\n", $before['surplus']);

$total = (int) $pdo->query('SELECT COUNT(*) c FROM ' . SOURCE_TABLE)->fetch()['c'];
printf("table total rows : %d  (surplus is %.1f%%)\n\n", $total, $total > 0 ? $before['surplus'] / $total * 100 : 0);

if ($before['surplus'] === 0) {
    echo "Nothing to do.\n";
    exit(0);
}

// ---------------------------------------------------------------- dry run
if ($mode === '--dry-run') {
    echo "--- what the surplus is made of (top 10 messages) ---\n";
    $stmt = $pdo->query('
        SELECT COALESCE(NULLIF(LEFT(s.error_message, 60), ""), "(null)") AS msg,
               s.status, s.error_class, COUNT(*) AS n
          FROM (' . surplus_row_sql('src.id, src.status, src.error_class, src.error_message') . ') s
         GROUP BY msg, s.status, s.error_class
         ORDER BY n DESC
         LIMIT 10
    ');
    foreach ($stmt as $r) {
        printf("  %7d  %-10s %-14s %s\n", $r['n'], $r['status'], (string) $r['error_class'], $r['msg']);
    }

    echo "\n--- rows that would be KEPT are the newest of each group; spot check ---\n";
    $stmt = $pdo->query('
        SELECT queue_id, attempt_no, COUNT(*) AS n, MAX(id) AS keep_id, MIN(id) AS drop_from
          FROM ' . SOURCE_TABLE . '
         GROUP BY queue_id, attempt_no HAVING n > 1
         ORDER BY n DESC LIMIT 5
    ');
    foreach ($stmt as $r) {
        printf(
            "  queue_id=%-9s attempt_no=%-4s rows=%-5s keep id=%-10s (drop %d others)\n",
            $r['queue_id'],
            $r['attempt_no'],
            $r['n'],
            $r['keep_id'],
            (int) $r['n'] - 1
        );
    }

    printf("\nArchive table present: %s\n", archive_exists($pdo) ? 'yes' : 'no');
    echo "\nNo rows were written.\n";
    exit(0);
}

// ---------------------------------------------------------------- archive
if ($mode === '--archive') {
    if (!archive_exists($pdo)) {
        // LIKE copies the column types and the PRIMARY KEY, so a re-run cannot double-insert.
        $pdo->exec('CREATE TABLE ' . ARCHIVE_TABLE . ' LIKE ' . SOURCE_TABLE);
        echo "created " . ARCHIVE_TABLE . "\n";
    }

    // INSERT IGNORE so an interrupted archive can simply be re-run: the PK from LIKE makes
    // each surplus row idempotent on its own id.
    $inserted = $pdo->exec('INSERT IGNORE INTO ' . ARCHIVE_TABLE . ' ' . surplus_row_sql('src.*'));
    $archived = (int) $pdo->query('SELECT COUNT(*) c FROM ' . ARCHIVE_TABLE)->fetch()['c'];

    printf("rows inserted this run : %d\n", (int) $inserted);
    printf("archive now holds      : %d\n", $archived);

    if ($archived < $before['surplus']) {
        fwrite(STDERR, sprintf(
            "\nFAIL: archive holds %d rows but %d are surplus. Do NOT run --delete.\n",
            $archived,
            $before['surplus']
        ));
        exit(1);
    }

    echo "\nArchive covers every surplus row. --delete may now be run.\n";
    exit(0);
}

// ---------------------------------------------------------------- delete
if (!archive_exists($pdo)) {
    fwrite(STDERR, "REFUSING: no archive table. Run --archive first.\n");
    exit(1);
}

$archived = (int) $pdo->query('SELECT COUNT(*) c FROM ' . ARCHIVE_TABLE)->fetch()['c'];
if ($archived < $before['surplus']) {
    fwrite(STDERR, sprintf(
        "REFUSING: archive holds %d rows but %d are surplus. Re-run --archive.\n",
        $archived,
        $before['surplus']
    ));
    exit(1);
}

// Every surplus row must be individually present in the archive. Counts alone would pass even
// if the archive were a stale snapshot of a DIFFERENT set of rows.
$unarchived = (int) $pdo->query('
    SELECT COUNT(*) c FROM (' . surplus_row_sql('src.id') . ') s
     WHERE NOT EXISTS (SELECT 1 FROM ' . ARCHIVE_TABLE . ' z WHERE z.id = s.id)
')->fetch()['c'];

if ($unarchived > 0) {
    fwrite(STDERR, sprintf("REFUSING: %d surplus rows are not in the archive. Re-run --archive.\n", $unarchived));
    exit(1);
}

echo "archive verified row-for-row; deleting in batches of 5,000\n";

$deleted = 0;
while (true) {
    // Batched so the transaction stays short: a single 88k-row DELETE holds gap locks across
    // the table long enough to stall the very claim path we are trying to unblock.
    $n = $pdo->exec('
        DELETE a FROM ' . SOURCE_TABLE . ' a
          JOIN (' . surplus_row_sql('src.id') . ' LIMIT 5000) s ON s.id = a.id
    ');

    if ($n === false || $n === 0) {
        break;
    }

    $deleted += $n;
    printf("  deleted %d (running total %d)\n", $n, $deleted);
    usleep(200000);   // let the queue breathe between batches
}

$after = count_surplus($pdo);
printf("\nrows deleted     : %d\n", $deleted);
printf("surplus remaining: %d\n", $after['surplus']);
printf("groups remaining : %d\n", $after['groups']);

if ($after['surplus'] !== 0) {
    fwrite(STDERR, "\nWARNING: surplus rows remain. Re-run --archive then --delete.\n");
    exit(1);
}

echo "\nDone. (queue_id, attempt_no) is now unique; contract_v2 can add its index.\n";
