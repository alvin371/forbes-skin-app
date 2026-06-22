<?php
/**
 * Migration runner with applied-state tracking.
 *
 * Usage:
 *   php migrations/run.php <file.php> [up|down]   Run a single migration (records state)
 *   php migrations/run.php --pending              Run every not-yet-applied migration in order
 *   php migrations/run.php --status               Show applied / pending for all migrations
 *   php migrations/run.php --baseline             Mark all current files as applied WITHOUT running
 *                                                 (use once when adopting tracking on an existing DB)
 *
 * Why: migrations were committed but never run on prod (no tracking, no deploy step),
 * so index/schema changes silently never took effect. `--pending` is safe to run on
 * every deploy: it applies only what is missing, in filename order, and records each in
 * the `schema_migrations` table.
 *
 * Each migration file receives $pdo (PDO) and $direction ('up'|'down') in its scope.
 */

if (PHP_SAPI !== 'cli') {
    exit("Run from command line only.\n");
}

// ---- Load .env manually (avoids BASEPATH/FCPATH dependency) ----
$envFile = __DIR__ . '/../.env';
$env = [];
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$name, $value] = explode('=', $line, 2);
        $env[trim($name)] = trim(trim($value), '"\'');
    }
} else {
    fwrite(STDERR, "Warning: .env file not found at $envFile\n");
}

function migration_env(array $env, string $key, string $default = ''): string
{
    return $env[$key] ?? (getenv($key) ?: $default);
}

// ---- Connect ----
$host   = migration_env($env, 'DB_HOSTNAME', '127.0.0.1');
$user   = migration_env($env, 'DB_USERNAME', 'root');
$pass   = migration_env($env, 'DB_PASSWORD', '');
$dbname = migration_env($env, 'DB_DATABASE', '');
$port   = (int) migration_env($env, 'DB_PORT', '3306');

if ($dbname === '') {
    fwrite(STDERR, "Error: DB_DATABASE is not set in .env\n");
    exit(1);
}

$dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "Connection failed: " . $e->getMessage() . "\n");
    exit(1);
}

// ---- Tracking helpers ----
function ensure_tracking_table(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS schema_migrations (
            filename VARCHAR(255) NOT NULL PRIMARY KEY,
            applied_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}

function migration_files(): array
{
    $files = glob(__DIR__ . '/*.php') ?: [];
    $files = array_filter($files, static fn($f) => basename($f) !== 'run.php');
    sort($files);
    return array_map('basename', $files);
}

function applied_set(PDO $pdo): array
{
    $rows = $pdo->query("SELECT filename FROM schema_migrations")->fetchAll(PDO::FETCH_COLUMN);
    return array_fill_keys($rows, true);
}

function mark_applied(PDO $pdo, string $name): void
{
    $stmt = $pdo->prepare("INSERT INTO schema_migrations (filename, applied_at) VALUES (?, NOW())
                           ON DUPLICATE KEY UPDATE applied_at = NOW()");
    $stmt->execute([$name]);
}

function mark_removed(PDO $pdo, string $name): void
{
    $pdo->prepare("DELETE FROM schema_migrations WHERE filename = ?")->execute([$name]);
}

/** Include a migration file with $pdo + $direction in scope. */
function run_migration_file(PDO $pdo, string $direction, string $file): void
{
    include $file;
}

ensure_tracking_table($pdo);

$cmd = $argv[1] ?? '';

// ---- Commands ----
if ($cmd === '--status') {
    $applied = applied_set($pdo);
    $pending = 0;
    foreach (migration_files() as $name) {
        $is = isset($applied[$name]);
        $pending += $is ? 0 : 1;
        printf("  [%s] %s\n", $is ? 'x' : ' ', $name);
    }
    echo "\n" . count(migration_files()) . " total, $pending pending.\n";
    exit(0);
}

if ($cmd === '--baseline') {
    foreach (migration_files() as $name) {
        mark_applied($pdo, $name);
    }
    echo "Baselined " . count(migration_files()) . " migrations as applied (no SQL run).\n";
    exit(0);
}

if ($cmd === '--pending') {
    // Advisory lock so concurrent container/replica starts don't race the same ALTER.
    $locked = $pdo->query("SELECT GET_LOCK('schema_migrations', 30) AS l")->fetch();
    if (empty($locked['l'])) {
        echo "Another migration run holds the lock; skipping --pending.\n";
        exit(0);
    }
    try {
        $applied = applied_set($pdo);
        $ran = 0;
        foreach (migration_files() as $name) {
            if (isset($applied[$name])) {
                continue;
            }
            echo "Running migration: $name [up]\n";
            try {
                run_migration_file($pdo, 'up', __DIR__ . '/' . $name);
                mark_applied($pdo, $name);
                $ran++;
            } catch (Throwable $e) {
                fwrite(STDERR, "Migration failed ($name): " . $e->getMessage() . "\n");
                exit(1);
            }
        }
        echo "Done. Applied $ran pending migration(s).\n";
    } finally {
        $pdo->query("SELECT RELEASE_LOCK('schema_migrations')");
    }
    exit(0);
}

// ---- Single-file mode (backward compatible) ----
if ($cmd === '' || $cmd[0] === '-') {
    fwrite(STDERR, "Usage: php migrations/run.php <file.php> [up|down] | --pending | --status | --baseline\n");
    exit(1);
}

$direction = $argv[2] ?? 'up';
$name = basename($cmd);
$file = __DIR__ . '/' . $name;

if (!file_exists($file)) {
    fwrite(STDERR, "Migration file not found: $file\n");
    exit(1);
}

echo "Running migration: $name [$direction]\n";
try {
    run_migration_file($pdo, $direction, $file);
} catch (Throwable $e) {
    fwrite(STDERR, "Migration failed: " . $e->getMessage() . "\n");
    exit(1);
}

if ($direction === 'down') {
    mark_removed($pdo, $name);
} else {
    mark_applied($pdo, $name);
}

echo "Done.\n";
