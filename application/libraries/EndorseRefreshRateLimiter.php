<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Scoped, cross-worker request-start rate reservation for endorse-refresh.
 *
 * Design goals (see fix/endorse-refresh-provider-parity):
 *  - a provider token is reserved immediately before an outbound request and retained for
 *    EVERY outcome (success/429/5xx/timeout/invalid/app-error); never by an unstarted claim;
 *  - reservations are SCOPED per provider + API-key so Forbes and Sec-Forbes (separate keys)
 *    never block one another, and a direct tiktok.com scrape never spends the RapidAPI budget;
 *  - the MySQL user-level lock name is namespaced (never the global "erq_rate") and never
 *    contains the raw key — only an 8-char fingerprint.
 *
 * The reservation POLICY (scope + lock identity) is pure and unit-testable. The STORE does
 * the atomic DB work and is swappable (PDO for tests, CI-db for production) so the fetch loop
 * never constructs an ad-hoc PDO.
 */
final class EndorseRefreshRateScope
{
    const PROVIDER_DIRECT  = 'direct_scrape';
    const PROVIDER_RAPIDAPI = 'rapidapi';

    /** Stable, non-secret key fingerprint (never the key itself). */
    public static function fingerprint(string $apiKey): string
    {
        $apiKey = trim($apiKey);
        return $apiKey === '' ? 'none' : substr(md5($apiKey), 0, 8);
    }

    /**
     * The provider_scope stored on each reservation row and used in the rolling count.
     * Direct scrape and each RapidAPI key are independent budgets.
     */
    public static function scope(string $provider, string $apiKey = ''): string
    {
        $provider = strtolower(trim($provider)) ?: 'unknown';
        return $provider === self::PROVIDER_DIRECT
            ? self::PROVIDER_DIRECT
            : $provider . ':' . self::fingerprint($apiKey);
    }

    /**
     * Namespaced MySQL user-level lock identity. Global to the server, so it MUST include
     * app + env + provider + key fingerprint to avoid unrelated apps blocking each other.
     * MySQL truncates GET_LOCK names at 64 bytes, so hash the tail if long.
     */
    public static function lockNameFor(string $env, string $app, string $providerScope): string
    {
        $raw = 'endorse-rate:' . strtolower(trim($env) ?: 'dev') . ':' . strtolower(trim($app) ?: 'app') . ':' . $providerScope;
        return strlen($raw) <= 64 ? $raw : substr($raw, 0, 31) . ':' . md5($raw);
    }
}

interface EndorseRefreshRateReservationStore
{
    /** Atomically reserve one token for $scope if the rolling window has room. */
    public function reserve(string $scope, int $limit, int $windowSec, array $ctx = []): bool;

    /**
     * As reserve(), but returns the token row id (or null when denied).
     *
     * The row this INSERTs is simultaneously the rate-limit token AND the opening half of the
     * request ledger entry: it is written immediately before the request starts, so it already
     * records that a request was attempted regardless of how it ends. Handing the id back lets
     * the caller complete that same row with the outcome instead of writing a second row —
     * which is what keeps "requests started" and "requests observed" the same number by
     * construction rather than by reconciliation.
     *
     * Recognised $ctx keys: run_id, queue_id, attempt_no, leg, worker_id, test_run_id.
     */
    public function reserveToken(string $scope, int $limit, int $windowSec, array $ctx = []): ?int;

    /** Count reservations for $scope inside the last $windowSec. */
    public function countInWindow(string $scope, int $windowSec): int;

    /** Delete reservations older than window+grace. Returns rows removed. */
    public function pruneExpired(int $windowSec, int $graceSec = 60): int;
}

/**
 * PDO-backed store (integration tests, and any caller holding a PDO). Uses a namespaced
 * GET_LOCK to serialise the scoped check-and-insert so N concurrent workers cannot exceed
 * the per-scope limit. Fail-closed if the lock cannot be taken.
 */
final class PdoReservationStore implements EndorseRefreshRateReservationStore
{
    private PDO $pdo;
    private string $env;
    private string $app;
    /** null = not yet probed. See hasLedgerColumns(). */
    private ?bool $hasLedger = null;

    public function __construct(PDO $pdo, string $env = 'test', string $app = 'forbes')
    {
        $this->pdo = $pdo;
        $this->env = $env;
        $this->app = $app;
    }

    private function scalar(string $sql)
    {
        $st = $this->pdo->query($sql);
        $v = $st->fetchColumn();
        $st->closeCursor();
        return $v;
    }

    public function reserve(string $scope, int $limit, int $windowSec, array $ctx = []): bool
    {
        return $this->reserveToken($scope, $limit, $windowSec, $ctx) !== null;
    }

    public function reserveToken(string $scope, int $limit, int $windowSec, array $ctx = []): ?int
    {
        if ($limit <= 0) {
            return null;
        }
        $lock = EndorseRefreshRateScope::lockNameFor($this->env, $this->app, $scope);
        if (intval($this->scalar("SELECT GET_LOCK(" . $this->pdo->quote($lock) . ", 5)")) !== 1) {
            return null;
        }
        try {
            if ($this->countInWindow($scope, $windowSec) >= $limit) {
                return null;
            }
            if ($this->hasLedgerColumns()) {
                $st = $this->pdo->prepare(
                    "INSERT INTO endorse_refresh_rate_tokens (provider_scope, created_at, run_id, queue_id, attempt_no, leg, worker_id, test_run_id)
                     VALUES (?, NOW(6), ?, ?, ?, ?, ?, ?)"
                );
                $st->execute([
                    $scope,
                    $ctx['run_id'] ?? null,
                    isset($ctx['queue_id']) ? intval($ctx['queue_id']) : null,
                    isset($ctx['attempt_no']) ? intval($ctx['attempt_no']) : null,
                    $ctx['leg'] ?? null,
                    $ctx['worker_id'] ?? null,
                    $ctx['test_run_id'] ?? null,
                ]);
            } else {
                $st = $this->pdo->prepare(
                    "INSERT INTO endorse_refresh_rate_tokens (provider_scope, created_at, run_id, queue_id, attempt_no)
                     VALUES (?, NOW(6), ?, ?, ?)"
                );
                $st->execute([
                    $scope,
                    $ctx['run_id'] ?? null,
                    isset($ctx['queue_id']) ? intval($ctx['queue_id']) : null,
                    isset($ctx['attempt_no']) ? intval($ctx['attempt_no']) : null,
                ]);
            }
            return (int) $this->pdo->lastInsertId();
        } finally {
            $this->scalar("SELECT RELEASE_LOCK(" . $this->pdo->quote($lock) . ")");
        }
    }

    /**
     * Whether migration 20260820120000 has landed, probed once per process.
     *
     * This is not defensive clutter — it is a deploy-ordering requirement. docker-entrypoint.sh
     * runs `migrations/run.php --pending` under `timeout 120` and deliberately does NOT fail
     * the container when it errors, so the application genuinely can start against a schema
     * that predates the ALTER. Without this probe every reserve() would throw, and because
     * the limiter fails CLOSED, the result would be a total denial of provider fallback
     * requests — a far worse outcome than not recording ledger columns for a few minutes.
     */
    private function hasLedgerColumns(): bool
    {
        if ($this->hasLedger === null) {
            try {
                $this->hasLedger = $this->pdo
                    ->query("SHOW COLUMNS FROM endorse_refresh_rate_tokens LIKE 'leg'")
                    ->fetchColumn() !== false;
            } catch (Throwable $e) {
                $this->hasLedger = false;
            }
        }

        return $this->hasLedger;
    }

    public function countInWindow(string $scope, int $windowSec): int
    {
        $st = $this->pdo->prepare(
            "SELECT COUNT(*) FROM endorse_refresh_rate_tokens
             WHERE provider_scope = ? AND created_at > (NOW(6) - INTERVAL ? SECOND)"
        );
        $st->execute([$scope, $windowSec]);
        $n = (int) $st->fetchColumn();
        $st->closeCursor();
        return $n;
    }

    public function pruneExpired(int $windowSec, int $graceSec = 60): int
    {
        $cutoff = $windowSec + max(0, $graceSec);
        $st = $this->pdo->prepare(
            "DELETE FROM endorse_refresh_rate_tokens WHERE created_at < (NOW(6) - INTERVAL ? SECOND)"
        );
        $st->execute([$cutoff]);
        return $st->rowCount();
    }
}

/**
 * Production store backed by the existing CodeIgniter database connection (mysqli). Reuses
 * the framework connection — no ad-hoc PDO in the fetch loop — and runs the same scoped,
 * GET_LOCK-guarded reservation as the PDO store. `$db` is a CI_DB_query_builder.
 */
final class CiDbReservationStore implements EndorseRefreshRateReservationStore
{
    private $db;
    private string $env;
    private string $app;
    /** null = not yet probed. See PdoReservationStore::hasLedgerColumns() for why. */
    private ?bool $hasLedger = null;

    public function __construct($db, string $env, string $app)
    {
        $this->db = $db;
        $this->env = $env;
        $this->app = $app;
    }

    public function reserve(string $scope, int $limit, int $windowSec, array $ctx = []): bool
    {
        return $this->reserveToken($scope, $limit, $windowSec, $ctx) !== null;
    }

    public function reserveToken(string $scope, int $limit, int $windowSec, array $ctx = []): ?int
    {
        if ($limit <= 0) {
            return null;
        }
        $lock = EndorseRefreshRateScope::lockNameFor($this->env, $this->app, $scope);
        $lockQ = $this->db->escape($lock);
        $got = $this->db->query("SELECT GET_LOCK($lockQ, 5) AS g")->row()->g ?? 0;
        if (intval($got) !== 1) {
            return null;
        }
        try {
            if ($this->countInWindow($scope, $windowSec) >= $limit) {
                return null;
            }
            if ($this->hasLedgerColumns()) {
                $this->db->query(
                    "INSERT INTO endorse_refresh_rate_tokens (provider_scope, created_at, run_id, queue_id, attempt_no, leg, worker_id, test_run_id) VALUES (?, NOW(6), ?, ?, ?, ?, ?, ?)",
                    [$scope, $ctx['run_id'] ?? null, isset($ctx['queue_id']) ? intval($ctx['queue_id']) : null, isset($ctx['attempt_no']) ? intval($ctx['attempt_no']) : null, $ctx['leg'] ?? null, $ctx['worker_id'] ?? null, $ctx['test_run_id'] ?? null]
                );
            } else {
                $this->db->query(
                    "INSERT INTO endorse_refresh_rate_tokens (provider_scope, created_at, run_id, queue_id, attempt_no) VALUES (?, NOW(6), ?, ?, ?)",
                    [$scope, $ctx['run_id'] ?? null, isset($ctx['queue_id']) ? intval($ctx['queue_id']) : null, isset($ctx['attempt_no']) ? intval($ctx['attempt_no']) : null]
                );
            }
            return (int) $this->db->insert_id();
        } finally {
            $this->db->query("SELECT RELEASE_LOCK($lockQ)");
        }
    }

    /** See PdoReservationStore::hasLedgerColumns() — same deploy-ordering requirement. */
    private function hasLedgerColumns(): bool
    {
        if ($this->hasLedger === null) {
            try {
                $row = $this->db->query("SHOW COLUMNS FROM endorse_refresh_rate_tokens LIKE 'leg'")->row();
                $this->hasLedger = !empty($row);
            } catch (Throwable $e) {
                $this->hasLedger = false;
            }
        }

        return $this->hasLedger;
    }

    public function countInWindow(string $scope, int $windowSec): int
    {
        $windowSec = max(1, $windowSec);
        $row = $this->db->query(
            "SELECT COUNT(*) AS c FROM endorse_refresh_rate_tokens WHERE provider_scope = ? AND created_at > (NOW(6) - INTERVAL $windowSec SECOND)",
            [$scope]
        )->row();
        return intval($row->c ?? 0);
    }

    public function pruneExpired(int $windowSec, int $graceSec = 60): int
    {
        $cutoff = max(1, $windowSec + max(0, $graceSec));
        $this->db->query("DELETE FROM endorse_refresh_rate_tokens WHERE created_at < (NOW(6) - INTERVAL $cutoff SECOND)");
        return intval($this->db->affected_rows());
    }
}

/**
 * Minimal structured run/request logger. Emits one compact JSON line per event via
 * error_log (or an injected sink). Never logs URLs, keys, cookies or bodies.
 */
final class EndorseRefreshRunLogger
{
    /** @var callable */
    private $sink;
    private bool $itemEvents;

    public function __construct(?callable $sink = null, bool $itemEvents = true)
    {
        $this->sink = $sink ?? function (string $line) { error_log($line); };
        $this->itemEvents = $itemEvents;
    }

    public function __invoke(string $type, array $data): void
    {
        if ($type === 'request' && !$this->itemEvents) {
            return;
        }
        $safe = [];
        foreach ($data as $k => $v) {
            if (in_array($k, ['url', 'link_upload', 'cookie', 'key', 'authorization', 'body'], true)) {
                continue;
            }
            $safe[$k] = $v;
        }
        $safe['evt'] = 'endorse_refresh_' . $type;
        ($this->sink)(json_encode($safe, JSON_UNESCAPED_SLASHES));
    }
}
