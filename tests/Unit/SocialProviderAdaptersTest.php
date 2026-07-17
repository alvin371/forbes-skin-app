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

require_once __DIR__ . '/../../application/libraries/Instagram_rapid_api.php';
require_once __DIR__ . '/../../application/libraries/Threads_api.php';
require_once __DIR__ . '/../../application/helpers/social_platform_helper.php';

/**
 * @internal
 */
final class SocialProviderAdaptersTest extends TestCase
{
    public function testInstagramProfileAndPartialPostAreNormalized(): void
    {
        $api            = new InstagramRapidApiProbe(['host' => 'test.invalid', 'key' => 'test-key', 'timeout' => 30]);
        $api->responses = [
            ['status' => true, 'http_status' => 200, 'data' => ['data' => [
                'pk'             => '42', 'username' => 'creator', 'full_name' => 'Creator',
                'follower_count' => 1234, 'media_count' => 18, 'profile_pic_url' => 'https://cdn.test/avatar.jpg',
            ]]],
            ['status' => true, 'http_status' => 200, 'data' => ['graphql' => ['shortcode_media' => [
                'id'                           => '99', 'shortcode' => 'ABC123',
                'edge_media_preview_like'      => ['count' => 20],
                'edge_media_to_parent_comment' => ['count' => 3],
                'display_url'                  => 'https://cdn.test/post.jpg',
            ]]]],
        ];

        $profile = $api->profile('creator');
        $post    = $api->post('https://www.instagram.com/p/ABC123/');

        $this->assertSame('42', $profile['data']['account_id']);
        $this->assertSame(1234, $profile['data']['follower']);
        $this->assertSame(20, $post['data']['like']);
        $this->assertNull($post['data']['view']);
        $this->assertSame(['like', 'comment'], $post['stats_fields']);
    }

    public function testThreadsMetricsMapRepliesAndRepostsPlusQuotes(): void
    {
        $api = new ThreadsApiProbe([
            'access_token' => 'test-token',
            'app_id'       => 'test-app',
            'app_secret'   => 'test-secret',
            'timeout'      => 30,
            'budget'       => 25,
        ]);
        $api->responses = [
            ['status' => true, 'http_status' => 200, 'data' => [
                'id'        => 'media-1', 'shortcode' => 'POST1', 'media_type' => 'TEXT',
                'permalink' => 'https://www.threads.net/@creator/post/POST1',
                'timestamp' => '2026-07-17T00:00:00+0000',
            ]],
            ['status' => true, 'http_status' => 200, 'data' => ['data' => [
                ['name' => 'views', 'values' => [['value' => 100]]],
                ['name' => 'likes', 'values' => [['value' => 12]]],
                ['name' => 'replies', 'values' => [['value' => 4]]],
                ['name' => 'reposts', 'values' => [['value' => 3]]],
                ['name' => 'quotes', 'values' => [['value' => 2]]],
            ]]],
        ];

        $result = $api->post('https://www.threads.net/@creator/post/POST1', 'media-1');

        $this->assertTrue($result['status']);
        $this->assertSame(100, $result['data']['view']);
        $this->assertSame(4, $result['data']['comment']);
        $this->assertSame(5, $result['data']['share']);
        $this->assertSame(['like', 'comment', 'share', 'view'], $result['stats_fields']);
    }

    public function testThreadsRecentPostsSkipsFailingInsightsInsteadOfAborting(): void
    {
        $api = new ThreadsApiProbe([
            'access_token' => 'test-token',
            'app_id'       => 'test-app',
            'app_secret'   => 'test-secret',
            'timeout'      => 30,
            'budget'       => 25,
        ]);
        $api->responses = [
            ['status' => true, 'http_status' => 200, 'data' => ['data' => [
                ['id' => 'media-1', 'shortcode' => 'POST1', 'media_type' => 'TEXT'],
                ['id' => 'media-2', 'shortcode' => 'POST2', 'media_type' => 'TEXT'],
            ]]],
            ['status' => false, 'msg' => 'Media deleted', 'data' => [], 'error_class' => 'permanent', 'http_status' => 404],
            ['status' => true, 'http_status' => 200, 'data' => ['data' => [
                ['name' => 'views', 'values' => [['value' => 50]]],
                ['name' => 'likes', 'values' => [['value' => 5]]],
            ]]],
        ];

        $result = $api->recentPosts(10);

        $this->assertTrue($result['status']);
        $this->assertCount(1, $result['data']);
        $this->assertSame('media-2', $result['data'][0]['content_id']);
    }

    public function testThreadsRecentPostsAbortsOnConfigClassInsightFailure(): void
    {
        $api = new ThreadsApiProbe([
            'access_token' => 'test-token',
            'app_id'       => 'test-app',
            'app_secret'   => 'test-secret',
            'timeout'      => 30,
            'budget'       => 25,
        ]);
        $api->responses = [
            ['status' => true, 'http_status' => 200, 'data' => ['data' => [
                ['id' => 'media-1', 'shortcode' => 'POST1', 'media_type' => 'TEXT'],
                ['id' => 'media-2', 'shortcode' => 'POST2', 'media_type' => 'TEXT'],
            ]]],
            ['status' => false, 'msg' => 'Invalid OAuth token', 'data' => [], 'error_class' => 'config', 'http_status' => 401],
        ];

        $result = $api->recentPosts(10);

        $this->assertFalse($result['status']);
        $this->assertSame('config', $result['error_class']);
    }

    public function testInstagramAndThreadsAreAutoFetchPlatformsAndUrlsAreDetected(): void
    {
        $this->assertTrue(is_auto_fetch_platform('Instagram'));
        $this->assertTrue(is_auto_fetch_platform('Threads'));
        $this->assertSame('Threads', detect_platform_from_url('https://www.threads.com/@creator/post/ABC'));
    }
}

final class InstagramRapidApiProbe extends Instagram_rapid_api
{
    public $responses = [];

    protected function request(string $path, array $query = []): array
    {
        return array_shift($this->responses);
    }
}

final class ThreadsApiProbe extends Threads_api
{
    public $responses = [];

    protected function request(string $url, string $method = 'GET', array $query = [], array $form = [], float $deadline = 0.0): array
    {
        return array_shift($this->responses);
    }
}
