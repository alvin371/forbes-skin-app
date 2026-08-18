<?php

use PHPUnit\Framework\TestCase;

if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}
if (! class_exists('CI_Controller')) {
    class CI_Controller
    {
    }
}

require_once __DIR__ . '/../../application/controllers/Api_v2.php';

/**
 * @internal
 */
final class ThreadsOAuthSecurityTest extends TestCase
{
    public function testSignedRequestRequiresAValidHmac(): void
    {
        $secret     = 'test-secret';
        $payload    = $this->encode(json_encode(['algorithm' => 'HMAC-SHA256', 'user_id' => 'threads-user']));
        $signature  = $this->encode(hash_hmac('sha256', $payload, $secret, true));
        $controller = (new ReflectionClass(Api_v2::class))->newInstanceWithoutConstructor();
        $property   = new ReflectionProperty(Api_v2::class, 'app_secret_threads');
        $property->setValue($controller, $secret);
        $method = new ReflectionMethod(Api_v2::class, 'parse_threads_signed_request');

        $valid   = $method->invoke($controller, $signature . '.' . $payload);
        $invalid = $method->invoke($controller, $this->encode('tampered') . '.' . $payload);

        $this->assertSame('threads-user', $valid['user_id']);
        $this->assertNull($invalid);
    }

    private function encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
