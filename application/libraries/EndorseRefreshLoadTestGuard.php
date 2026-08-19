<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Fail-fast production-isolation guard for the endorse-refresh load-test worker.
 *
 * The load-test worker claims queue rows, writes business rows and starts outbound provider
 * requests. Pointed at production it would corrupt live data and burn the live provider
 * quota. So it refuses to boot unless it can PROVE it is isolated.
 *
 * Design:
 *  - PURE. Takes an env map, returns violation codes. No DB, no I/O, no globals — so the
 *    whole policy is unit-testable without a container (mirrors EndorseRefreshRateScope).
 *  - DENY BY DEFAULT. A missing or unparseable value is a violation, never a pass. Every
 *    check answers "can I prove this is safe?", not "can I prove this is unsafe?".
 *  - NEVER ECHOES VALUES. Violations are stable codes plus a non-sensitive hint. A hostname,
 *    database name, password or API key must never reach stdout, a log, or a report.
 *
 * Adding a check is cheap; removing one is not. Anything that could reach production is a
 * violation here, not a runtime warning.
 */
final class EndorseRefreshLoadTestGuard
{
    /**
     * Environments in which the load-test worker may run.
     *
     * Only 'testing'. Not because 'loadtest' would be unsafe, but because index.php's
     * ENVIRONMENT switch has no case for it and exits 503 before CodeIgniter boots — so
     * accepting it here would trade a clear guard failure for a confusing 503. Load-test-ness
     * is asserted separately by ENDORSE_REFRESH_LOADTEST, below.
     */
    const ALLOWED_ENVIRONMENTS = array('testing');

    /**
     * Explicit opt-in marker. CI_ENV=testing alone is not enough: an ordinary test environment
     * must not be able to start a process that spends provider quota and writes business rows
     * at 400/min. Someone has to have typed this on purpose.
     */
    const LOADTEST_MARKER_KEY = 'ENDORSE_REFRESH_LOADTEST';

    /**
     * Selects the rule set. Only the exact string "production" leaves load-test mode, so a
     * missing, empty or misspelled value keeps the strict rules.
     */
    const MODE_KEY = 'ENDORSE_REFRESH_WORKER_MODE';
    const MODE_LOADTEST = 'loadtest';
    const MODE_PRODUCTION = 'production';

    /**
     * The database name must END with this. A load test cannot be aimed at a database whose
     * name someone did not deliberately mark as disposable — which rules out `forbes`,
     * `forbes_prod`, and every production-shaped name, without needing to enumerate them.
     */
    const REQUIRED_DB_SUFFIX = '_loadtest';

    /**
     * Hosts known to be production. Substring-matched, case-insensitive. This is a
     * belt-and-braces layer: REQUIRED_LOCAL_DB_HOSTS below is the real gate, because an
     * allowlist cannot be defeated by a hostname nobody thought to add here.
     */
    const PRODUCTION_HOST_MARKERS = array(
        '217.217.253.76',
        'acnenosystem.com',
        'mysql-8_mysql',
        'forbes_app',
        'sec-forbes',
    );

    /**
     * The DB host must be one of these, or a *.local / *.internal container alias. An
     * allowlist is the only check that fails closed against a production host nobody
     * predicted.
     */
    const REQUIRED_LOCAL_DB_HOSTS = array(
        '127.0.0.1',
        'localhost',
        '::1',
        'loadtest-mysql',
        'host.docker.internal',
    );

    /** Retention shorter than this lets the ledger's batched upsert resurrect a pruned row. */
    const MIN_TOKEN_RETENTION_SEC = 60;

    /**
     * Collect every violation in $env. Empty array means isolation is proven.
     *
     * Returns a list of ['code' => string, 'hint' => string]; `hint` is safe to print and
     * never contains a value read from $env.
     */
    /**
     * Which rule set applies. Deny-by-default: anything that is not the exact string
     * "production" — including a missing value or a typo — gets the strict load-test rules.
     * Production mode can therefore never be entered by accident or by an empty variable.
     */
    public static function mode(array $env): string
    {
        return strtolower(trim(self::str($env, self::MODE_KEY))) === self::MODE_PRODUCTION
            ? self::MODE_PRODUCTION
            : self::MODE_LOADTEST;
    }

    public static function violations(array $env): array
    {
        if (self::mode($env) === self::MODE_PRODUCTION) {
            return self::productionViolations($env);
        }

        $out = array();

        $ci = strtolower(trim(self::str($env, 'CI_ENV')));
        if (!in_array($ci, self::ALLOWED_ENVIRONMENTS, true)) {
            $out[] = self::v('environment_not_loadtest', 'CI_ENV must be one of: ' . implode(', ', self::ALLOWED_ENVIRONMENTS));
        }
        if (!self::isExplicitlyTrue($env, self::LOADTEST_MARKER_KEY)) {
            $out[] = self::v('loadtest_marker_missing', self::LOADTEST_MARKER_KEY . ' must be explicitly set to 1/true');
        }

        // --- Database identity -------------------------------------------------------
        $dbName = strtolower(trim(self::str($env, 'DB_DATABASE')));
        if ($dbName === '') {
            $out[] = self::v('db_name_missing', 'DB_DATABASE is required');
        } elseif (substr($dbName, -strlen(self::REQUIRED_DB_SUFFIX)) !== self::REQUIRED_DB_SUFFIX) {
            $out[] = self::v('db_name_not_disposable', 'DB_DATABASE must end with "' . self::REQUIRED_DB_SUFFIX . '"');
        }

        $dbHost = strtolower(trim(self::str($env, 'DB_HOSTNAME')));
        if ($dbHost === '') {
            $out[] = self::v('db_host_missing', 'DB_HOSTNAME is required');
        } else {
            if (self::matchesProductionMarker($dbHost)) {
                $out[] = self::v('db_host_is_production', 'DB_HOSTNAME matches a known production host');
            }
            if (!self::isLocalHost($dbHost)) {
                $out[] = self::v('db_host_not_local', 'DB_HOSTNAME must be a loopback address or a *.local/*.internal container alias');
            }
        }

        // --- Anything that can reach the outside world -------------------------------
        // A load test replays real queue rows. If mail or webhooks are live it will notify
        // real people about synthetic events.
        if (self::truthy($env, 'SMTP_HOST') && !self::isExplicitlyTrue($env, 'LOADTEST_MAIL_DISABLED')) {
            $out[] = self::v('mail_enabled', 'SMTP must be unset, or LOADTEST_MAIL_DISABLED=true');
        }
        if (self::truthy($env, 'TELEGRAM_BOT_TOKEN') && !self::isExplicitlyTrue($env, 'LOADTEST_WEBHOOKS_DISABLED')) {
            $out[] = self::v('webhooks_enabled', 'Telegram/webhooks must be unset, or LOADTEST_WEBHOOKS_DISABLED=true');
        }
        if (self::truthy($env, 'FCM_SERVICE_ACCOUNT_FILE') || self::truthy($env, 'FCM_SERVICE_ACCOUNT_B64')) {
            $out[] = self::v('push_enabled', 'FCM credentials must not be present in a load-test env');
        }
        if (self::truthy($env, 'SENTRY_DSN')) {
            $out[] = self::v('error_reporting_enabled', 'SENTRY_DSN must be unset so synthetic failures never page anyone');
        }

        $baseUrl = strtolower(trim(self::str($env, 'BASE_URL')));
        if ($baseUrl !== '' && self::matchesProductionMarker($baseUrl)) {
            $out[] = self::v('base_url_is_production', 'BASE_URL points at production');
        }

        return array_merge($out, self::rateBudgetViolations($env));
    }

    /**
     * Production rules.
     *
     * The load-test rules exist to prove the worker is NOT pointed at production. In
     * production that question is settled, so those checks are dropped — but the ones that
     * bound outbound load are not, because they are the only global brake once the worker
     * disables the claim-time attempt cap.
     *
     * Two production-specific invariants are added instead:
     *
     *  - the cron must have stood down (ENDORSE_REFRESH_DRIVER=php_worker). Running both
     *    drains at once is safe for correctness (SKIP LOCKED) but doubles provider load and
     *    makes the rate budget meaningless, since the cron spends requests outside the
     *    worker's reservation accounting;
     *  - the replica count must be declared, because pacing divides the fleet budget by it.
     *    Understating it reintroduces bursting against the provider.
     */
    private static function productionViolations(array $env): array
    {
        $out = self::rateBudgetViolations($env);

        $driver = strtolower(trim(self::str($env, 'ENDORSE_REFRESH_DRIVER')));
        if ($driver !== 'php_worker') {
            $out[] = self::v('cron_not_stood_down', 'ENDORSE_REFRESH_DRIVER must be "php_worker" so the per-minute cron stops draining the same queue');
        }

        $replicas = self::intOrNull($env, 'ENDORSE_REFRESH_WORKER_REPLICAS');
        if ($replicas === null || $replicas < 1) {
            $out[] = self::v('replicas_not_declared', 'ENDORSE_REFRESH_WORKER_REPLICAS must be a positive integer matching the deployed replica count');
        }

        return $out;
    }

    /**
     * The brake that applies in EVERY mode.
     *
     * CiDbReservationStore::reserve() fail-CLOSES on limit <= 0 but fail-OPENS on a huge
     * limit. The worker disables the claim-time attempt cap, so these two numbers are the
     * only global brake on outbound requests. A missing or absurd value is a violation.
     */
    private static function rateBudgetViolations(array $env): array
    {
        $out = array();

        foreach (array('ENDORSE_REFRESH_DIRECT_RATE_PER_MIN', 'ENDORSE_REFRESH_RAPIDAPI_RATE_PER_MIN') as $key) {
            $limit = self::intOrNull($env, $key);
            if ($limit === null || $limit <= 0) {
                $out[] = self::v('rate_limit_invalid', $key . ' must be a positive integer');
            } elseif ($limit > self::MAX_SANE_RATE_PER_MIN) {
                $out[] = self::v('rate_limit_implausible', $key . ' exceeds the ' . self::MAX_SANE_RATE_PER_MIN . '/min safety ceiling');
            }
        }

        $combined = (int) max(0, self::intOrNull($env, 'ENDORSE_REFRESH_DIRECT_RATE_PER_MIN'))
                  + (int) max(0, self::intOrNull($env, 'ENDORSE_REFRESH_RAPIDAPI_RATE_PER_MIN'));
        if ($combined > self::MAX_COMBINED_RATE_PER_MIN) {
            $out[] = self::v('rate_limit_combined_too_high', 'direct + rapidapi request starts must stay at or below ' . self::MAX_COMBINED_RATE_PER_MIN . '/min');
        }

        $retention = self::intOrNull($env, 'ENDORSE_REFRESH_TOKEN_RETENTION_SEC');
        if ($retention !== null && $retention < self::MIN_TOKEN_RETENTION_SEC) {
            $out[] = self::v('token_retention_too_short', 'ENDORSE_REFRESH_TOKEN_RETENTION_SEC must be >= ' . self::MIN_TOKEN_RETENTION_SEC);
        }

        return $out;
    }

    /** Combined provider safety ceiling from the approved budget (720/min). */
    const MAX_COMBINED_RATE_PER_MIN = 720;

    /** No single scope may be configured above the provider hard limit (800/min). */
    const MAX_SANE_RATE_PER_MIN = 800;

    /**
     * Throw unless isolation is proven. The message carries codes and hints only — never a
     * hostname, database name or credential.
     */
    public static function assertIsolated(array $env): void
    {
        $violations = self::violations($env);
        if ($violations === array()) {
            return;
        }

        $lines = array();
        foreach ($violations as $v) {
            $lines[] = '  - [' . $v['code'] . '] ' . $v['hint'];
        }

        throw new RuntimeException(
            "REFUSING TO START: production isolation is not proven.\n"
            . implode("\n", $lines)
            . "\nFix the load-test environment; never relax this guard to make a run start."
        );
    }

    /**
     * The subset of the process environment the guard reads. Kept explicit so a caller can
     * hand the same map to assertIsolated() and to a report without leaking unrelated keys.
     */
    public static function readEnv(callable $reader): array
    {
        $keys = array(
            'CI_ENV', self::LOADTEST_MARKER_KEY, 'DB_DATABASE', 'DB_HOSTNAME', 'BASE_URL',
            'SMTP_HOST', 'TELEGRAM_BOT_TOKEN', 'FCM_SERVICE_ACCOUNT_FILE', 'FCM_SERVICE_ACCOUNT_B64',
            'SENTRY_DSN', 'LOADTEST_MAIL_DISABLED', 'LOADTEST_WEBHOOKS_DISABLED',
            'ENDORSE_REFRESH_DIRECT_RATE_PER_MIN', 'ENDORSE_REFRESH_RAPIDAPI_RATE_PER_MIN',
            'ENDORSE_REFRESH_TOKEN_RETENTION_SEC',
            self::MODE_KEY, 'ENDORSE_REFRESH_DRIVER', 'ENDORSE_REFRESH_WORKER_REPLICAS',
        );

        $out = array();
        foreach ($keys as $k) {
            $out[$k] = $reader($k, '');
        }

        return $out;
    }

    // -- helpers ---------------------------------------------------------------------

    private static function v(string $code, string $hint): array
    {
        return array('code' => $code, 'hint' => $hint);
    }

    private static function str(array $env, string $key): string
    {
        return isset($env[$key]) && is_scalar($env[$key]) ? (string) $env[$key] : '';
    }

    /** True when the key holds a non-empty, non-false-ish value. */
    private static function truthy(array $env, string $key): bool
    {
        $raw = strtolower(trim(self::str($env, $key)));
        return $raw !== '' && !in_array($raw, array('0', 'false', 'no', 'off', 'null', 'none'), true);
    }

    /** True only when the flag is explicitly set to a true-ish value. Absent means NOT disabled. */
    private static function isExplicitlyTrue(array $env, string $key): bool
    {
        $raw = strtolower(trim(self::str($env, $key)));
        return in_array($raw, array('1', 'true', 'yes', 'on'), true);
    }

    private static function intOrNull(array $env, string $key): ?int
    {
        $raw = trim(self::str($env, $key));
        return $raw !== '' && preg_match('/^-?\d+$/', $raw) === 1 ? (int) $raw : null;
    }

    private static function matchesProductionMarker(string $haystack): bool
    {
        foreach (self::PRODUCTION_HOST_MARKERS as $marker) {
            if (strpos($haystack, $marker) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Loopback, an explicitly allowlisted alias, or a container-private suffix. Anything else
     * — including a bare hostname that happens to resolve locally — fails closed.
     */
    private static function isLocalHost(string $host): bool
    {
        if (in_array($host, self::REQUIRED_LOCAL_DB_HOSTS, true)) {
            return true;
        }
        if (substr($host, -6) === '.local' || substr($host, -9) === '.internal') {
            return true;
        }

        // 127.0.0.0/8 in any spelling.
        return preg_match('/^127\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $host) === 1;
    }
}
