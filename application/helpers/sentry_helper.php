<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('sentry_sdk_available')) {
    function sentry_sdk_available()
    {
        return class_exists('\\Sentry\\State\\HubInterface') && function_exists('\\Sentry\\captureException');
    }
}

if (!function_exists('sentry_capture_exception')) {
    function sentry_capture_exception(Throwable $exception, array $context = array())
    {
        if (!sentry_sdk_available()) {
            return null;
        }

        return \Sentry\withScope(function (\Sentry\State\Scope $scope) use ($exception, $context) {
            foreach ($context as $key => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                if (is_scalar($value)) {
                    $scope->setTag((string) $key, (string) $value);
                    continue;
                }

                $scope->setExtra((string) $key, $value);
            }

            return \Sentry\captureException($exception);
        });
    }
}

if (!function_exists('sentry_capture_message')) {
    function sentry_capture_message($message, array $context = array())
    {
        if (!sentry_sdk_available() || !function_exists('\\Sentry\\captureMessage')) {
            return null;
        }

        return \Sentry\withScope(function (\Sentry\State\Scope $scope) use ($message, $context) {
            foreach ($context as $key => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                if (is_scalar($value)) {
                    $scope->setTag((string) $key, (string) $value);
                    continue;
                }

                $scope->setExtra((string) $key, $value);
            }

            return \Sentry\captureMessage((string) $message);
        });
    }
}

if (!function_exists('sentry_set_user')) {
    function sentry_set_user(array $user = array())
    {
        if (!sentry_sdk_available()) {
            return;
        }

        $payload = array_filter(array(
            'id' => isset($user['id']) ? (string) $user['id'] : null,
            'username' => $user['username'] ?? null,
            'email' => $user['email'] ?? null,
            'name' => $user['full_name'] ?? ($user['name'] ?? null),
        ));

        \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($payload) {
            $scope->setUser($payload);
        });
    }
}
