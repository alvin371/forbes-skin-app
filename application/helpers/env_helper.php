<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Load environment variables from .env file
 *
 * @param string $key Environment variable key
 * @param mixed $default Default value if key not found
 * @return mixed
 */
if (!function_exists('env')) {
    function env($key, $default = null)
    {
        static $env_loaded = false;
        static $env_vars = [];

        // Load .env file only once
        if (!$env_loaded) {
            $env_file = FCPATH . '.env';

            if (file_exists($env_file)) {
                $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

                foreach ($lines as $line) {
                    // Skip comments
                    if (strpos(trim($line), '#') === 0) {
                        continue;
                    }

                    // Parse key=value pairs
                    if (strpos($line, '=') !== false) {
                        list($name, $value) = explode('=', $line, 2);
                        $name = trim($name);
                        $value = trim($value);

                        // Remove quotes if present
                        $value = trim($value, '"\'');

                        $env_vars[$name] = $value;

                        // Also set as PHP environment variable
                        putenv("$name=$value");
                    }
                }
            }

            $env_loaded = true;
        }

        // Return from our loaded vars
        if (isset($env_vars[$key])) {
            return $env_vars[$key];
        }

        // Fallback to getenv
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }

        return $default;
    }
}
