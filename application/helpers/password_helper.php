<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Password helpers shared by web (Auth) and mobile (Api_hrms).
 *
 * - validate_password_strength(): single source of strength rules.
 * - hash_password(): hash new passwords with the strongest algo the host supports
 *   (Argon2id when compiled in, else bcrypt via PASSWORD_DEFAULT).
 * - verify_and_upgrade_password(): the "2 code" verify path that accepts both the
 *   modern hash and legacy unsalted MD5, opportunistically rehashing to the current
 *   algo on a successful match. This is how MD5 disappears row-by-row without any
 *   forced migration or lockout.
 */

if (!function_exists('password_algo')) {
    /**
     * Resolve the hashing algorithm constant to use for new hashes.
     * Optional .env override PASSWORD_ALGO=argon2id|bcrypt; otherwise auto-detect.
     *
     * @return string PASSWORD_* algorithm identifier
     */
    function password_algo()
    {
        $override = strtolower((string) (function_exists('env') ? env('PASSWORD_ALGO', '') : ''));

        if ($override === 'bcrypt') {
            return PASSWORD_BCRYPT;
        }
        if ($override === 'argon2id' && defined('PASSWORD_ARGON2ID')) {
            return PASSWORD_ARGON2ID;
        }

        // Auto: prefer Argon2id (memory-hard) when the host compiled it in.
        if (defined('PASSWORD_ARGON2ID')) {
            return PASSWORD_ARGON2ID;
        }

        return PASSWORD_DEFAULT;
    }
}

if (!function_exists('hash_password')) {
    /**
     * Hash a plaintext password with the resolved algorithm.
     *
     * @param string $plain
     * @return string
     */
    function hash_password($plain)
    {
        return password_hash((string) $plain, password_algo());
    }
}

if (!function_exists('validate_password_strength')) {
    /**
     * Enforce password strength: 8+ chars, upper, lower, digit, special.
     *
     * @param string $password
     * @return true|string  true when valid, otherwise an error message
     */
    function validate_password_strength($password)
    {
        $password = (string) $password;

        if (strlen($password) < 8) {
            return 'Password must be at least 8 characters long.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return 'Password must contain at least one uppercase letter.';
        }
        if (!preg_match('/[a-z]/', $password)) {
            return 'Password must contain at least one lowercase letter.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            return 'Password must contain at least one number.';
        }
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            return 'Password must contain at least one special character (!@#$%^&*(),.?":{}|<>).';
        }

        return true;
    }
}

if (!function_exists('verify_and_upgrade_password')) {
    /**
     * Verify a plaintext password against a user row, accepting both the modern
     * hash and legacy MD5, and transparently upgrading the stored hash to the
     * current algorithm on a successful match.
     *
     * @param object $db    CodeIgniter database instance (CI_DB_query_builder)
     * @param array  $user  user row (must include 'id' and 'password')
     * @param string $plain plaintext candidate
     * @return bool         true when the password matches
     */
    function verify_and_upgrade_password($db, array $user, $plain)
    {
        $plain = (string) $plain;
        if ($plain === '') {
            return false;
        }

        $currentHash = (string) ($user['password'] ?? '');
        if ($currentHash === '') {
            return false;
        }

        // Modern hash (bcrypt/argon2): verify, then rehash if the algo/cost changed.
        if (password_verify($plain, $currentHash)) {
            if (password_needs_rehash($currentHash, password_algo())) {
                $db->update('user', array('password' => hash_password($plain)), array('id' => (int) $user['id']));
            }
            return true;
        }

        // Legacy unsalted MD5: constant-time compare, then upgrade to the modern algo.
        if (hash_equals($currentHash, md5($plain))) {
            $db->update('user', array('password' => hash_password($plain)), array('id' => (int) $user['id']));
            return true;
        }

        return false;
    }
}
