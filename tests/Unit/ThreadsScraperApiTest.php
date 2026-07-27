<?php

use PHPUnit\Framework\TestCase;

if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}
if (! function_exists('env')) {
    function env($key, $default = null)
    {
        return $default;
    }
}

require_once __DIR__ . '/../../application/libraries/Threads_scraper_api.php';

/**
 * @internal
 */
final class ThreadsScraperApiTest extends TestCase
{
    public function testCompletedPostResponseMapsToExistingEndorseContract(): void
    {
        $result = Threads_scraper_api::normalizePostResult([
            'platform'   => 'threads',
            'post_id'    => 'media-1',
            'permalink'  => 'https://www.threads.net/@creator/post/POST1',
            'likes'      => 12,
            'comments'   => 4,
            'shares'     => 5,
            'views'      => 100,
            'media_type' => 'TEXT',
        ], 'https://threads.net/@creator/post/POST1?utm_source=test');

        $this->assertTrue($result['status']);
        $this->assertSame('media-1', $result['data']['content_id']);
        $this->assertSame(100, $result['data']['view']);
        $this->assertSame(['like', 'comment', 'share', 'view'], $result['stats_fields']);
    }

    public function testMismatchedPermalinkCannotUpdateAnotherEndorsement(): void
    {
        $result = Threads_scraper_api::normalizePostResult([
            'platform'  => 'threads',
            'post_id'   => 'media-1',
            'permalink' => 'https://www.threads.net/@creator/post/OTHER',
        ], 'https://www.threads.net/@creator/post/POST1');

        $this->assertFalse($result['status']);
        $this->assertSame('permanent', $result['error_class']);
    }

    public function testWrongPlatformIsRejected(): void
    {
        $result = Threads_scraper_api::normalizePostResult([
            'platform'  => 'instagram',
            'post_id'   => 'post-1',
            'permalink' => 'https://www.instagram.com/p/POST1/',
        ]);

        $this->assertFalse($result['status']);
        $this->assertSame('permanent', $result['error_class']);
    }
}
