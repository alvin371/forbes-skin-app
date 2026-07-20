<?php

use PHPUnit\Framework\TestCase;

if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}

/**
 * CI3 has no autoloader, so a library making static calls into a sibling library must
 * declare that dependency itself. EndorseRefreshV2Coordinator previously relied on some
 * earlier caller having run load->library('EndorseRefreshQueueService'), which made
 * Endorse::sync_process fatal with `Class "EndorseRefreshQueueService" not found` — it
 * loads only the coordinator.
 *
 * Each case runs in a fresh PHP subprocess: the rest of the suite already pulls every
 * class in, so an in-process assertion would pass regardless of the fix.
 *
 * @internal
 */
final class EndorseLibraryDependencyTest extends TestCase
{
    public function testCoordinatorAloneResolvesItsStaticDependencies(): void
    {
        $this->assertLoadsStandalone(
            'EndorseRefreshV2Coordinator.php',
            "EndorseRefreshQueueService::normalizeTiktokUrl('https://www.tiktok.com/@a/video/123');
             Endorse_sync::is_terminal_class('permanent');",
        );
    }

    public function testQueueServiceAloneResolvesItsStaticDependencies(): void
    {
        $this->assertLoadsStandalone(
            'EndorseRefreshQueueService.php',
            "Endorse_sync::is_terminal_class('empty');",
        );
    }

    /**
     * Load one library file in a clean process and run the static calls it makes,
     * asserting the process exits cleanly rather than fataling on a missing class.
     */
    private function assertLoadsStandalone(string $library, string $staticCalls): void
    {
        $libDir = realpath(__DIR__ . '/../../application/libraries');
        $this->assertNotFalse($libDir, 'library directory not found');

        $script = "<?php
            define('BASEPATH', __DIR__);
            require_once " . var_export($libDir . '/' . $library, true) . ";
            {$staticCalls}
            echo 'OK';
        ";

        $file = tempnam(sys_get_temp_dir(), 'endorse_dep_') . '.php';
        file_put_contents($file, $script);

        try {
            $output   = [];
            $exitCode = 0;
            exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($file) . ' 2>&1', $output, $exitCode);
            $joined = implode("\n", $output);

            $this->assertSame(0, $exitCode, "{$library} failed to load standalone:\n{$joined}");
            $this->assertStringContainsString('OK', $joined);
        } finally {
            @unlink($file);
        }
    }
}
