<?php

use PHPUnit\Framework\TestCase;

/**
 * `migrations/run.php --pending` requires every migration in ONE PHP process, so all
 * migration files share a single global function namespace.
 *
 * Two hazards follow, and both are silent:
 *   1. two migrations declaring the same unguarded function name -> fatal redeclare;
 *   2. two migrations declaring the same GUARDED name -> whichever loads first wins and
 *      the second migration silently runs the first one's implementation.
 *
 * (2) is the dangerous one: a later migration's `hasColumn()` could be checking a
 * different information_schema view, or returning a different type, and the guard would
 * hide it. Unique per-migration prefixes make it unrepresentable, and this test keeps it
 * that way.
 *
 * @internal
 */
final class MigrationHelperIsolationTest extends TestCase
{
    /**
     * Every function a migration declares, as file => list of names.
     *
     * @return array<string, list<string>>
     */
    private function declaredFunctions(): array
    {
        $declared = [];

        foreach ($this->migrationFiles() as $path) {
            $source = file_get_contents($path);
            $this->assertIsString($source, "unreadable migration {$path}");

            preg_match_all('/^\s*function\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(/m', $source, $matches);
            $declared[basename($path)] = $matches[1];
        }

        return $declared;
    }

    /**
     * @return list<string>
     */
    private function migrationFiles(): array
    {
        $files = glob(__DIR__ . '/../../migrations/*.php') ?: [];
        // run.php is the runner, not a migration; it legitimately owns global helpers.
        $files = array_values(array_filter($files, static fn (string $p) => basename($p) !== 'run.php'));
        $this->assertNotEmpty($files, 'no migration files found');

        return $files;
    }

    public function testNoTwoMigrationsDeclareTheSameGlobalFunction(): void
    {
        $owners = [];

        foreach ($this->declaredFunctions() as $file => $functions) {
            foreach ($functions as $function) {
                $owners[$function][] = $file;
            }
        }

        $collisions = array_filter($owners, static fn (array $files) => count($files) > 1);

        $this->assertSame(
            [],
            $collisions,
            'these function names are declared by more than one migration, so whichever '
            . 'loads first silently wins: ' . json_encode($collisions, JSON_UNESCAPED_SLASHES),
        );
    }

    /**
     * A guard only protects re-inclusion of the SAME file. It cannot tell a re-include
     * apart from a different migration reusing the name, so the name itself has to be
     * unambiguous — a bare `hasColumn` is not.
     */
    public function testMigrationHelperNamesAreUniquelyPrefixed(): void
    {
        $generic = ['hasTable', 'hasColumn', 'hasIndex', 'tableExists', 'columnExists', 'indexExists'];

        foreach ($this->declaredFunctions() as $file => $functions) {
            foreach ($functions as $function) {
                $this->assertNotContains(
                    $function,
                    $generic,
                    "{$file} declares the generic global helper {$function}(); prefix it with "
                    . 'something specific to this migration so a later migration cannot collide.',
                );
            }
        }
    }

    /**
     * A declaration that is not guarded cannot survive the disposable integration schema
     * rebuild, which requires these files a second time in the same process.
     */
    public function testEveryMigrationHelperIsGuardedByItsOwnName(): void
    {
        foreach ($this->migrationFiles() as $path) {
            $source = file_get_contents($path);

            preg_match_all('/^\s*function\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(/m', $source, $matches);

            foreach ($matches[1] as $function) {
                $this->assertMatchesRegularExpression(
                    "/function_exists\\(\\s*'" . preg_quote($function, '/') . "'\\s*\\)/",
                    $source,
                    basename($path) . " declares {$function}() without a matching "
                    . "function_exists('{$function}') guard.",
                );
            }
        }
    }
}
