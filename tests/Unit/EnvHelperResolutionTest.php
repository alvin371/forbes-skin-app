<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * No class on the worker's fetch path may call the global env() helper.
 *
 * The reason is concrete. illuminate/support sits in composer "require" and ships an env()
 * helper, but it does NOT require phpoption/phpoption or vlucas/phpdotenv — a Laravel app gets
 * those from laravel/framework, and this project installs neither. So Illuminate\Support\Env
 * is unusable here: any call fatals with `Class "PhpOption\Option" not found`.
 *
 * That helper always wins the function_exists race under PHPUnit, because vendor/bin/phpunit
 * requires vendor/autoload.php — and with it composer's "files" autoload — before it reads
 * phpunit.xml or any bootstrap. Declaring our own env() later cannot help, so the only durable
 * guarantee is that these classes never call it.
 *
 * This broke CI twice on commits that were green locally, in two different call paths:
 * EndorseRefreshPipeline::startLeg, then Template::getRapidApiConfig reached through
 * EndorseRefreshFetchKit. Both now take their configuration from the caller instead.
 *
 * @internal
 */
final class EnvHelperResolutionTest extends TestCase
{
    /**
     * Counts real calls to a global env(), ignoring mentions in comments and strings and
     * ignoring method calls such as $x->env() or Foo::env().
     */
    private function globalEnvCalls(string $file): int
    {
        $tokens = token_get_all((string) file_get_contents($file));
        $count  = 0;

        foreach ($tokens as $i => $token) {
            if (! is_array($token) || $token[0] !== T_STRING || $token[1] !== 'env') {
                continue;
            }

            $prev = $tokens[$i - 1] ?? null;
            if (is_array($prev) && in_array($prev[0], [T_OBJECT_OPERATOR, T_DOUBLE_COLON], true)) {
                continue;
            }

            for ($j = $i + 1, $n = count($tokens); $j < $n; $j++) {
                if (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
                    continue;
                }
                if ($tokens[$j] === '(') {
                    $count++;
                }

                break;
            }
        }

        return $count;
    }

    public function testWorkerFetchPathClassesNeverCallGlobalEnv(): void
    {
        $base = __DIR__ . '/../../application/libraries/';

        foreach (['EndorseRefreshPipeline.php', 'EndorseRefreshFetchKit.php', 'EndorseRefreshLedger.php'] as $file) {
            $this->assertSame(
                0,
                $this->globalEnvCalls($base . $file),
                $file . ' must receive its configuration from the caller, not from a global env()',
            );
        }
    }

    /**
     * The controller is allowed to read the environment — that is its job, and it runs under
     * CodeIgniter where application/helpers/env_helper.php has already won the race. This
     * documents the boundary rather than leaving it implicit.
     */
    public function testTheControllerIsTheOnlyPlaceThatReadsTheEnvironment(): void
    {
        $calls = $this->globalEnvCalls(__DIR__ . '/../../application/controllers/EndorseRefreshWorker.php');
        $this->assertGreaterThan(0, $calls, 'EndorseRefreshWorker is expected to be the env boundary');
    }
}
