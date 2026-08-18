<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../vendor/autoload.php';

if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}

require_once __DIR__ . '/../../application/helpers/monitoring_helper.php';

final class MonitoringHelperTest extends TestCase
{
    public function testSqlFingerprintRedactsQuotedAndNumericLiterals(): void
    {
        $fingerprint = monitoring_sql_fingerprint(
            "SELECT * FROM user WHERE email = 'person@example.com' AND id = 42 AND token = \"secret\""
        );

        $this->assertSame('SELECT * FROM user WHERE email = ? AND id = ? AND token = ?', $fingerprint);
    }
}
