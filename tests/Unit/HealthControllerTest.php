<?php

use PHPUnit\Framework\TestCase;

if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}

if (! class_exists('CI_Controller')) {
    class CI_Controller
    {
        public $output;
    }
}

require_once __DIR__ . '/../../application/controllers/HealthController.php';

/**
 * @internal
 */
final class HealthControllerTest extends TestCase
{
    public function testIndexReturnsHealthyJsonResponse(): void
    {
        $controller         = new HealthController();
        $controller->output = new HealthControllerOutputStub();

        $response = $controller->index();

        $this->assertSame($controller->output, $response);
        $this->assertSame(200, $controller->output->statusCode);
        $this->assertSame('application/json', $controller->output->contentType);
        $this->assertSame('{"ok":true}', $controller->output->body);
    }
}

final class HealthControllerOutputStub
{
    public $statusCode;
    public $contentType;
    public $body;

    public function set_status_header($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    public function set_content_type($contentType)
    {
        $this->contentType = $contentType;

        return $this;
    }

    public function set_output($body)
    {
        $this->body = $body;

        return $this;
    }
}
