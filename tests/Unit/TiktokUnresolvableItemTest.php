<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../vendor/autoload.php';

if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}

if (! function_exists('env')) {
    function env($key, $default = null)
    {
        $value = getenv($key);

        return $value !== false ? $value : $default;
    }
}

require_once __DIR__ . '/../../application/libraries/Template.php';
require_once __DIR__ . '/../../application/libraries/Endorse_sync.php';

/**
 * A post that RapidAPI cannot resolve must fail ONCE, not three times.
 *
 * Verified against production: live posts answer code:0 for every URL form — vt.tiktok.com
 * short-links, canonical /photo/ slideshow posts, and /video/ — so a code:-1 "Url parsing is
 * failed" is a statement about the CONTENT (deleted / private / author-only), not about the
 * connection. Classified 'transient' it burned max_attempts x ~2 requests on every dead post
 * on every enqueue cycle.
 *
 * The blast radius of getting this wrong in the other direction is much worse: a live post
 * marked permanent is silently dropped. Hence the deliberately narrow match list, pinned here.
 *
 * @internal
 */
final class TiktokUnresolvableItemTest extends TestCase
{
    private function probe(): UnresolvableProbe
    {
        return new UnresolvableProbe();
    }

    public function testDeadPostIsClassifiedPermanentSoItFailsOnTheFirstAttempt(): void
    {
        $result = $this->probe()->classify('7591841763959147797', [
            'code' => -1,
            'msg'  => 'Url parsing is failed! Please check url.',
            'data' => [],
        ]);

        $this->assertSame('permanent', $result['error_class']);
        $this->assertTrue(
            Endorse_sync::is_terminal_class($result['error_class']),
            'applyResults checks is_terminal_class BEFORE the attempts counter, so this is what '
            . 'makes a dead post consume exactly one attempt',
        );
    }

    public function testContentLevelFailuresAreTerminal(): void
    {
        $messages = [
            'Url parsing is failed! Please check url.',
            'video not found',
            'Item not found',
            'content not found',
        ];

        foreach ($messages as $msg) {
            $result = $this->probe()->classify('123', ['code' => -1, 'msg' => $msg, 'data' => []]);
            $this->assertSame('permanent', $result['error_class'], $msg);
        }
    }

    /**
     * The important negative cases. These are provider-side or account-side problems that WILL
     * succeed later, so marking them permanent would throw away live posts en masse.
     */
    public function testProviderSideFailuresStayRetryable(): void
    {
        $messages = [
            'You have exceeded the MONTHLY quota for Requests',
            'Too many requests',
            'Invalid API key',
            'Internal server error',
            '',
        ];

        foreach ($messages as $msg) {
            $result = $this->probe()->classify('123', ['code' => -1, 'msg' => $msg, 'data' => []]);

            $this->assertSame('transient', $result['error_class'], $msg);
            $this->assertFalse(Endorse_sync::is_terminal_class($result['error_class']), $msg);
        }
    }

    public function testSuccessfulPayloadIsNeverTreatedAsUnresolvable(): void
    {
        // A live photo-slideshow post: code 0 with images[] and counts.
        $this->assertFalse($this->probe()->isUnresolvable([
            'code' => 0,
            'msg'  => 'success',
            'data' => ['id' => '7674972394762603796', 'play_count' => 1218057, 'images' => ['a', 'b']],
        ]));
    }

    public function testNonArrayResponseIsNotUnresolvable(): void
    {
        $this->assertFalse($this->probe()->isUnresolvable(null));
        $this->assertFalse($this->probe()->isUnresolvable('boom'));
    }
}

/**
 * @internal
 */
final class UnresolvableProbe extends Template
{
    public function __construct()
    {
    }

    public function classify(string $contentId, $response): array
    {
        return $this->buildTiktokRapidApiFailureResponse($contentId, $response);
    }

    public function isUnresolvable($response): bool
    {
        return $this->rapidApiItemIsUnresolvable($response);
    }
}
