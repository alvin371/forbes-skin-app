<?php

/**
 * Integration bootstrap.
 *
 * PHPUnit's launcher loads composer autoload (hence illuminate/support's env()) before this
 * bootstrap, so env() cannot be pre-empted. illuminate's env() resolves PhpOption\Option
 * lazily at call time; that package is a transitive dep present in production but absent in
 * this dev vendor tree. We provide a minimal, faithful Option stub (only the subset
 * illuminate uses) so the REAL production code under test can call env() unchanged — no
 * dependency or composer.lock change.
 */

namespace PhpOption {
    if (! class_exists(Option::class)) {
        abstract class Option
        {
            public static function fromValue($value, $noneValue = null)
            {
                return $value === $noneValue ? new None() : new Some($value);
            }

            abstract public function map(callable $f);

            abstract public function getOrCall(callable $f);

            abstract public function getOrElse($default);
        }

        final class Some extends Option
        {
            private $value;

            public function __construct($value)
            {
                $this->value = $value;
            }

            public function map(callable $f)
            {
                return new self($f($this->value));
            }

            public function getOrCall(callable $f)
            {
                return $this->value;
            }

            public function getOrElse($default)
            {
                return $this->value;
            }
        }

        final class None extends Option
        {
            public function map(callable $f)
            {
                return $this;
            }

            public function getOrCall(callable $f)
            {
                return $f();
            }

            public function getOrElse($default)
            {
                return $default;
            }
        }
    }
}

namespace {
    use Illuminate\Support\Env;

    require_once __DIR__ . '/../../vendor/autoload.php';

    // EndorseRefreshQueueService's constructor references APPPATH; point it at the real
    // application dir so its optional-library is_file() check behaves as in production.
    if (! defined('APPPATH')) {
        define('APPPATH', realpath(__DIR__ . '/../../application') . DIRECTORY_SEPARATOR);
    }

    // illuminate's env() lazily builds a Dotenv RepositoryBuilder (also absent in this dev
    // vendor tree). Inject a minimal getenv-backed repository so env() resolves via process
    // env — matching how the production app's own env() reads configuration.
    if (class_exists(Env::class)) {
        $repo = new class () {
            public function get($key)
            {
                $v = getenv($key);

                return $v === false ? null : $v;
            }

            public function set($key, $value = null)
            {
                putenv($key . '=' . $value);

                return $this;
            }

            public function clear($key)
            {
                putenv($key);

                return $this;
            }
        };
        // (setAccessible is a no-op / deprecated on PHP 8.1+; setValue works directly.)
        (new ReflectionProperty(Env::class, 'repository'))->setValue(null, $repo);
    }
}
