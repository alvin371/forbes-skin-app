<?php
/**
 * Simple migration runner.
 * Usage: php migrations/run.php <migration_file.php> [up|down]
 *
 * The migration file receives a $pdo (PDO) instance and a $direction string.
 */

if (PHP_SAPI !== 'cli') {
    exit("Run from command line only.\n");
}

if (empty($argv[1])) {
    fwrite(STDERR, "Usage: php migrations/run.php <migration_file.php> [up|down]\n");
    exit(1);
}

$direction = $argv[2] ?? 'up';
$file = __DIR__ . '/' . basename($argv[1]);

if (!file_exists($file)) {
    fwrite(STDERR, "Migration file not found: $file\n");
    exit(1);
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

echo "Running migration: " . basename($file) . " [$direction]\n";

// ---- Run migration ----
// Each migration file gets $pdo and $direction injected via include scope.
try {
    include $file;
} catch (Throwable $e) {
    fwrite(STDERR, "Migration failed: " . $e->getMessage() . "\n");
    exit(1);
}

echo "Done.\n";
