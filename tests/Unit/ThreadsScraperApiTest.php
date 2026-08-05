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

    /**
     * Regression for "Hasil scraper bukan post Threads." — the real completed-job result is
     * the raw scrape dict (id/url/reposts/image_url/datetime, NO platform); the platform lives
     * on the job envelope. This is the exact shape captured from production postgres. It MUST be
     * accepted as a valid Threads post, not merely stop producing the old message.
     */
    public function testRawJobResultShapeIsAcceptedAsValidThreadsPost(): void
    {
        $result = Threads_scraper_api::normalizePostResult([
            'id'         => '3945276211145040330',
            'url'        => 'https://www.threads.net/@hrsglwp/post/DbAb8-iGWHK',
            'likes'      => 9,
            'comments'   => 7,
            'reposts'    => 3,
            'views'      => 952,
            'datetime'   => '2026-07-20T07:46:35+00:00',
            'media_type' => 'Sidecar',
            'image_url'  => 'https://scontent.example/img.webp',
        ], 'https://www.threads.com/@hrsglwp/post/DbAb8-iGWHK', 'threads');

        $this->assertTrue($result['status'], $result['msg'] ?? '');
        $this->assertSame('3945276211145040330', $result['data']['content_id']);
        $this->assertSame('https://www.threads.net/@hrsglwp/post/DbAb8-iGWHK', $result['data']['url']);
        $this->assertSame(9, $result['data']['like']);
        $this->assertSame(7, $result['data']['comment']);
        $this->assertSame(3, $result['data']['share']);
        $this->assertSame(952, $result['data']['view']);
        $this->assertSame('Sidecar', $result['data']['media_type']);
        $this->assertSame('https://scontent.example/img.webp', $result['data']['cover']);
        $this->assertSame('2026-07-20T07:46:35+00:00', $result['data']['created_at']);
    }

    public function testDotComInputMatchesDotNetPermalink(): void
    {
        $result = Threads_scraper_api::normalizePostResult([
            'id'  => 'media-1',
            'url' => 'https://www.threads.net/@creator/post/POST1',
        ], 'https://www.threads.com/@creator/post/POST1', 'threads');

        $this->assertTrue($result['status'], $result['msg'] ?? '');
        $this->assertSame('media-1', $result['data']['content_id']);
    }

    public function testEnvelopePlatformIsHonouredWhenResultHasNoPlatform(): void
    {
        $rejected = Threads_scraper_api::normalizePostResult([
            'id'  => 'media-1',
            'url' => 'https://www.threads.net/@creator/post/POST1',
        ], '', 'instagram');
        $this->assertFalse($rejected['status']);
        $this->assertSame('permanent', $rejected['error_class']);
    }

    public function testMissingIdOrUrlIsTransientContractFailure(): void
    {
        $result = Threads_scraper_api::normalizePostResult([
            'likes'    => 9,
            'comments' => 7,
        ], '', 'threads');

        $this->assertFalse($result['status']);
        $this->assertSame('transient', $result['error_class']);
    }
}
