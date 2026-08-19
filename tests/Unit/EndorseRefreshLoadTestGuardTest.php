<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../vendor/autoload.php';

if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}

require_once __DIR__ . '/../../application/libraries/EndorseRefreshLoadTestGuard.php';

/**
 * The load-test worker writes business rows and spends provider quota. This guard is the only
 * thing standing between a mistyped env var and production, so every check is pinned here —
 * including that it fails CLOSED on absent values rather than assuming safety.
 *
 * @internal
 */
final class EndorseRefreshLoadTestGuardTest extends TestCase
{
    /**
     * A fully isolated environment. Individual tests mutate one key to prove one check.
     */
    private function safeEnv(array $overrides = []): array
    {
        return array_merge([
            'CI_ENV'                                => 'testing',
            'ENDORSE_REFRESH_LOADTEST'              => '1',
            'DB_DATABASE'                           => 'forbes_loadtest',
            'DB_HOSTNAME'                           => '127.0.0.1',
            'BASE_URL'                              => 'http://localhost:8090/',
            'SMTP_HOST'                             => '',
            'TELEGRAM_BOT_TOKEN'                    => '',
            'FCM_SERVICE_ACCOUNT_FILE'              => '',
            'FCM_SERVICE_ACCOUNT_B64'               => '',
            'SENTRY_DSN'                            => '',
            'LOADTEST_MAIL_DISABLED'                => 'true',
            'LOADTEST_WEBHOOKS_DISABLED'            => 'true',
            'ENDORSE_REFRESH_DIRECT_RATE_PER_MIN'   => '480',
            'ENDORSE_REFRESH_RAPIDAPI_RATE_PER_MIN' => '240',
            'ENDORSE_REFRESH_TOKEN_RETENTION_SEC'   => '3600',
        ], $overrides);
    }

    /**
     * @return list<string>
     */
    private function codes(array $env): array
    {
        return array_column(EndorseRefreshLoadTestGuard::violations($env), 'code');
    }

    public function testAFullyIsolatedEnvironmentPasses(): void
    {
        $this->assertSame([], EndorseRefreshLoadTestGuard::violations($this->safeEnv()));
    }

    public function testProductionEnvironmentIsRejected(): void
    {
        $this->assertContains('environment_not_loadtest', $this->codes($this->safeEnv(['CI_ENV' => 'production'])));
        $this->assertContains('environment_not_loadtest', $this->codes($this->safeEnv(['CI_ENV' => 'development'])));
        $this->assertNotContains('environment_not_loadtest', $this->codes($this->safeEnv(['CI_ENV' => 'testing'])));
    }

    /**
     * CI_ENV=testing is shared with the ordinary unit/integration suites. Without a second,
     * deliberate marker an existing test environment could start a process that spends
     * provider quota — so the marker must be opt-in, not opt-out.
     */
    public function testTestingEnvironmentAloneIsNotEnough(): void
    {
        $codes = $this->codes($this->safeEnv(['ENDORSE_REFRESH_LOADTEST' => '']));
        $this->assertContains('loadtest_marker_missing', $codes);
        $this->assertNotContains('environment_not_loadtest', $codes);

        foreach (['0', 'false', 'no', 'off', 'maybe'] as $notTrue) {
            $this->assertContains(
                'loadtest_marker_missing',
                $this->codes($this->safeEnv(['ENDORSE_REFRESH_LOADTEST' => $notTrue])),
                $notTrue . ' must not satisfy the load-test marker',
            );
        }
    }

    public function testDatabaseMustCarryTheDisposableSuffix(): void
    {
        // The production database name is exactly the kind of value this must catch.
        $this->assertContains('db_name_not_disposable', $this->codes($this->safeEnv(['DB_DATABASE' => 'forbes'])));
        $this->assertContains('db_name_not_disposable', $this->codes($this->safeEnv(['DB_DATABASE' => 'forbes_prod'])));
        // A name that merely CONTAINS the suffix is not enough — it must end with it.
        $this->assertContains('db_name_not_disposable', $this->codes($this->safeEnv(['DB_DATABASE' => 'forbes_loadtest_restored'])));
        $this->assertNotContains('db_name_not_disposable', $this->codes($this->safeEnv(['DB_DATABASE' => 'forbes_loadtest'])));
    }

    public function testKnownProductionHostsAreRejected(): void
    {
        $codes = $this->codes($this->safeEnv(['DB_HOSTNAME' => '217.217.253.76']));
        $this->assertContains('db_host_is_production', $codes);
        $this->assertContains('db_host_not_local', $codes);

        $this->assertContains('db_host_is_production', $this->codes($this->safeEnv(['DB_HOSTNAME' => 'mysql-8_mysql'])));
    }

    public function testUnknownNonLocalHostsFailClosed(): void
    {
        // The allowlist is what protects against a production host nobody thought to denylist.
        $this->assertContains('db_host_not_local', $this->codes($this->safeEnv(['DB_HOSTNAME' => 'db.example.com'])));
        $this->assertContains('db_host_not_local', $this->codes($this->safeEnv(['DB_HOSTNAME' => '10.0.0.5'])));
    }

    public function testLoopbackAndContainerAliasesAreAccepted(): void
    {
        foreach (['127.0.0.1', '127.0.0.2', 'localhost', '::1', 'loadtest-mysql', 'provider-mock.local', 'db.internal'] as $host) {
            $this->assertNotContains(
                'db_host_not_local',
                $this->codes($this->safeEnv(['DB_HOSTNAME' => $host])),
                $host . ' should be accepted as a local host',
            );
        }
    }

    public function testMissingValuesAreViolationsNotPasses(): void
    {
        $codes = $this->codes([]);
        $this->assertContains('environment_not_loadtest', $codes);
        $this->assertContains('db_name_missing', $codes);
        $this->assertContains('db_host_missing', $codes);
        $this->assertContains('rate_limit_invalid', $codes);
    }

    public function testOutboundNotificationChannelsMustBeOff(): void
    {
        $this->assertContains('mail_enabled', $this->codes($this->safeEnv([
            'SMTP_HOST' => 'smtp.example.com', 'LOADTEST_MAIL_DISABLED' => 'false',
        ])));
        $this->assertContains('webhooks_enabled', $this->codes($this->safeEnv([
            'TELEGRAM_BOT_TOKEN' => 'x', 'LOADTEST_WEBHOOKS_DISABLED' => '',
        ])));
        $this->assertContains('push_enabled', $this->codes($this->safeEnv(['FCM_SERVICE_ACCOUNT_B64' => 'x'])));
        $this->assertContains('error_reporting_enabled', $this->codes($this->safeEnv(['SENTRY_DSN' => 'https://x@y/1'])));
    }

    public function testAnExplicitDisableFlagPermitsAConfiguredButInertChannel(): void
    {
        $codes = $this->codes($this->safeEnv([
            'SMTP_HOST' => 'smtp.example.com', 'LOADTEST_MAIL_DISABLED' => 'true',
        ]));
        $this->assertNotContains('mail_enabled', $codes);
    }

    public function testProductionBaseUrlIsRejected(): void
    {
        $this->assertContains('base_url_is_production', $this->codes($this->safeEnv([
            'BASE_URL' => 'https://acnenosystem.com/',
        ])));
    }

    /**
     * The worker disables the claim-time attempt cap, so these limits are the only global
     * brake. reserve() fail-CLOSES on <= 0 but fail-OPENS on a huge value, which is why an
     * upper bound is checked too.
     */
    public function testRateLimitsMustBePositiveAndPlausible(): void
    {
        $this->assertContains('rate_limit_invalid', $this->codes($this->safeEnv(['ENDORSE_REFRESH_DIRECT_RATE_PER_MIN' => '0'])));
        $this->assertContains('rate_limit_invalid', $this->codes($this->safeEnv(['ENDORSE_REFRESH_DIRECT_RATE_PER_MIN' => ''])));
        $this->assertContains('rate_limit_invalid', $this->codes($this->safeEnv(['ENDORSE_REFRESH_RAPIDAPI_RATE_PER_MIN' => 'unlimited'])));
        $this->assertContains('rate_limit_implausible', $this->codes($this->safeEnv([
            'ENDORSE_REFRESH_DIRECT_RATE_PER_MIN' => (string) PHP_INT_MAX,
        ])));
    }

    public function testCombinedRateCannotExceedTheApprovedCeiling(): void
    {
        $this->assertNotContains('rate_limit_combined_too_high', $this->codes($this->safeEnv([
            'ENDORSE_REFRESH_DIRECT_RATE_PER_MIN' => '480', 'ENDORSE_REFRESH_RAPIDAPI_RATE_PER_MIN' => '240',
        ])));
        $this->assertContains('rate_limit_combined_too_high', $this->codes($this->safeEnv([
            'ENDORSE_REFRESH_DIRECT_RATE_PER_MIN' => '600', 'ENDORSE_REFRESH_RAPIDAPI_RATE_PER_MIN' => '600',
        ])));
    }

    public function testShortTokenRetentionIsRejected(): void
    {
        // Retention below the ledger flush horizon lets the batched upsert resurrect a pruned
        // row as a phantom limiter token, silently inflating the rolling window count.
        $this->assertContains('token_retention_too_short', $this->codes($this->safeEnv([
            'ENDORSE_REFRESH_TOKEN_RETENTION_SEC' => '30',
        ])));
    }

    public function testAssertIsolatedThrowsWithCodesAndNeverEchoesValues(): void
    {
        $secretish = 'super-secret-prod-host.example.com';

        try {
            EndorseRefreshLoadTestGuard::assertIsolated($this->safeEnv([
                'CI_ENV' => 'production', 'DB_HOSTNAME' => $secretish,
            ]));
            $this->fail('assertIsolated() must throw when isolation is not proven');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('environment_not_loadtest', $e->getMessage());
            $this->assertStringContainsString('db_host_not_local', $e->getMessage());
            $this->assertStringNotContainsString($secretish, $e->getMessage());
        }
    }

    public function testAssertIsolatedIsSilentWhenIsolationIsProven(): void
    {
        EndorseRefreshLoadTestGuard::assertIsolated($this->safeEnv());
        $this->addToAssertionCount(1);
    }

    public function testReadEnvCollectsOnlyTheKeysTheGuardInspects(): void
    {
        $seen = [];
        $env  = EndorseRefreshLoadTestGuard::readEnv(static function ($key, $default) use (&$seen) {
            $seen[] = $key;

            return $key === 'CI_ENV' ? 'loadtest' : $default;
        });

        $this->assertContains('CI_ENV', $seen);
        $this->assertContains('DB_HOSTNAME', $seen);
        $this->assertNotContains('DB_PASSWORD', $seen, 'the guard must never read credentials');
        $this->assertNotContains('RAPIDAPI_KEY', $seen, 'the guard must never read provider keys');
        $this->assertSame('loadtest', $env['CI_ENV']);
    }

    // --- production mode ---------------------------------------------------------------

    /**
     * A production worker points at the production database on purpose, so the isolation
     * checks are dropped — but the rate budget is not, because once the worker disables the
     * claim-time attempt cap those two numbers are the only global brake on outbound load.
     */
    private function productionEnv(array $overrides = []): array
    {
        return array_merge([
            'ENDORSE_REFRESH_WORKER_MODE'           => 'production',
            'CI_ENV'                                => 'production',
            'DB_DATABASE'                           => 'forbes_app',
            'DB_HOSTNAME'                           => 'mysql-8_mysql',
            'BASE_URL'                              => 'https://acnenosystem.com/',
            'SMTP_HOST'                             => 'smtp.example.com',
            'SENTRY_DSN'                            => 'https://sentry.example/1',
            'ENDORSE_REFRESH_DRIVER'                => 'php_worker',
            'ENDORSE_REFRESH_WORKER_REPLICAS'       => '2',
            'ENDORSE_REFRESH_DIRECT_RATE_PER_MIN'   => '150',
            'ENDORSE_REFRESH_RAPIDAPI_RATE_PER_MIN' => '450',
            'ENDORSE_REFRESH_TOKEN_RETENTION_SEC'   => '3600',
        ], $overrides);
    }

    public function testProductionModeAcceptsTheProductionDatabase(): void
    {
        $this->assertSame([], EndorseRefreshLoadTestGuard::violations($this->productionEnv()));
    }

    /**
     * Running cron and worker together is safe for correctness (SKIP LOCKED) but doubles
     * provider load and makes the rate budget meaningless, because the cron spends requests
     * outside the worker's reservation accounting.
     */
    public function testProductionModeRequiresTheCronToHaveStoodDown(): void
    {
        $codes = $this->codes($this->productionEnv(['ENDORSE_REFRESH_DRIVER' => 'cron']));
        $this->assertContains('cron_not_stood_down', $codes);
    }

    public function testProductionModeRequiresTheReplicaCountToBeDeclared(): void
    {
        // Pacing divides the fleet budget by this number; undeclared means bursting.
        $this->assertContains('replicas_not_declared', $this->codes($this->productionEnv([
            'ENDORSE_REFRESH_WORKER_REPLICAS' => '',
        ])));
    }

    public function testProductionModeStillEnforcesTheRateBudget(): void
    {
        $this->assertContains('rate_limit_invalid', $this->codes($this->productionEnv([
            'ENDORSE_REFRESH_DIRECT_RATE_PER_MIN' => '0',
        ])));

        $this->assertContains('rate_limit_combined_too_high', $this->codes($this->productionEnv([
            'ENDORSE_REFRESH_DIRECT_RATE_PER_MIN'   => '500',
            'ENDORSE_REFRESH_RAPIDAPI_RATE_PER_MIN' => '400',
        ])));
    }

    /**
     * Deny-by-default. Anything that is not the exact string "production" must keep the strict
     * load-test rules, so a typo or an empty variable can never silently unlock production.
     */
    public function testOnlyTheExactProductionStringLeavesLoadTestMode(): void
    {
        // Semantically different values — including a near-miss abbreviation — stay strict.
        foreach (['', 'prod', 'live', 'loadtest', 'production_worker'] as $value) {
            $codes = $this->codes($this->productionEnv([EndorseRefreshLoadTestGuard::MODE_KEY => $value]));
            $this->assertContains(
                'db_name_not_disposable',
                $codes,
                "mode '{$value}' must fall back to the strict load-test rules",
            );
        }

        // Case and surrounding whitespace are normalised, so an operator cannot be defeated by
        // a stray space or capital. That is still an explicit opt-in, not an accidental one.
        foreach (['production', 'Production', 'PRODUCTION', '  production  '] as $value) {
            $this->assertSame(
                [],
                EndorseRefreshLoadTestGuard::violations(
                    $this->productionEnv([EndorseRefreshLoadTestGuard::MODE_KEY => $value]),
                ),
                "mode '{$value}' should be accepted as production",
            );
        }
    }
}
