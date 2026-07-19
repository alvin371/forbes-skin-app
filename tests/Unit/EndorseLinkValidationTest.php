<?php

use PHPUnit\Framework\TestCase;

if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}

require_once __DIR__ . '/../../application/helpers/social_platform_helper.php';

/**
 * Guards the platform-aware link_upload validation used by Endorse::store()/update().
 *
 * @internal
 */
final class EndorseLinkValidationTest extends TestCase
{
    public function testTiktokAndThreadsResolveUsernameFromUrl(): void
    {
        $this->assertTrue(platform_requires_username_from_url('Tiktok'));
        $this->assertTrue(platform_requires_username_from_url('Threads'));
        $this->assertTrue(platform_requires_username_from_url('threads'));

        $this->assertSame(
            'creator',
            extract_username_from_content_url('https://www.tiktok.com/@creator/video/123', 'Tiktok')
        );
        $this->assertSame(
            'creator',
            extract_username_from_content_url('https://www.tiktok.com/@creator/photo/123', 'Tiktok')
        );
        $this->assertSame(
            'creator',
            extract_username_from_content_url('https://www.threads.com/@creator/post/ABC123', 'Threads')
        );
        $this->assertSame(
            'creator',
            extract_username_from_content_url('https://www.threads.net/@creator/post/ABC123', 'Threads')
        );
    }

    public function testInstagramHasNoUsernameInContentUrl(): void
    {
        // IG post URLs carry a shortcode, never the creator handle: the influencer
        // must come from the form selection instead.
        $this->assertFalse(platform_requires_username_from_url('Instagram'));
        $this->assertSame(
            '',
            extract_username_from_content_url('https://www.instagram.com/p/ABC123/', 'Instagram')
        );
        $this->assertSame(
            '',
            extract_username_from_content_url('https://www.instagram.com/reel/ABC123/', 'Instagram')
        );
    }

    public function testUsernameExtractionIsPlatformScopedAndRejectsMismatches(): void
    {
        // A TikTok URL submitted under Threads must not resolve, and vice versa.
        $this->assertSame(
            '',
            extract_username_from_content_url('https://www.tiktok.com/@creator/video/123', 'Threads')
        );
        $this->assertSame(
            '',
            extract_username_from_content_url('https://www.threads.com/@creator/post/ABC', 'Tiktok')
        );

        // Profile links and garbage yield no username.
        $this->assertSame('', extract_username_from_content_url('https://www.tiktok.com/@creator', 'Tiktok'));
        $this->assertSame('', extract_username_from_content_url('https://www.threads.com/@creator', 'Threads'));
        $this->assertSame('', extract_username_from_content_url('not a url', 'Tiktok'));
        $this->assertSame('', extract_username_from_content_url('', 'Tiktok'));
    }

    public function testUnsupportedPlatformsNeverRequireUrlUsername(): void
    {
        foreach (['Youtube', 'Twitter', 'Facebook', '', 'Unknown'] as $platform) {
            $this->assertFalse(platform_requires_username_from_url($platform));
        }
    }

    public function testDuplicateNormalizationCollapsesEquivalentLinks(): void
    {
        $canonical = normalize_content_url_for_duplicate_check('https://www.instagram.com/p/ABC123/');

        $this->assertSame('instagram.com/p/ABC123', $canonical);

        foreach ([
            'https://www.instagram.com/p/ABC123',
            'https://instagram.com/p/ABC123/',
            'http://www.instagram.com/p/ABC123/',
            'https://www.instagram.com/p/ABC123/?igsh=xyz',
            'https://www.instagram.com/p/ABC123/#comments',
            '  https://www.instagram.com/p/ABC123/  ',
            'www.instagram.com/p/ABC123/',
            'https://WWW.INSTAGRAM.COM/p/ABC123/',
        ] as $variant) {
            $this->assertSame($canonical, normalize_content_url_for_duplicate_check($variant), $variant);
        }
    }

    public function testDuplicateNormalizationKeepsDistinctContentApart(): void
    {
        $this->assertNotSame(
            normalize_content_url_for_duplicate_check('https://www.instagram.com/p/ABC123/'),
            normalize_content_url_for_duplicate_check('https://www.instagram.com/p/XYZ789/')
        );
        // Shortcodes are case-sensitive; only the host is lowercased.
        $this->assertNotSame(
            normalize_content_url_for_duplicate_check('https://www.instagram.com/p/ABC123/'),
            normalize_content_url_for_duplicate_check('https://www.instagram.com/p/abc123/')
        );
        $this->assertSame('', normalize_content_url_for_duplicate_check('   '));
    }

    public function testThreadsLinksNormalizeAcrossDomains(): void
    {
        // threads.com and threads.net are different hosts, so they stay distinct here;
        // duplicate detection is additionally scoped by platform in the controller.
        $this->assertSame(
            'threads.com/@creator/post/ABC',
            normalize_content_url_for_duplicate_check('https://www.threads.com/@creator/post/ABC/')
        );
        $this->assertSame(
            'threads.net/@creator/post/ABC',
            normalize_content_url_for_duplicate_check('https://www.threads.net/@creator/post/ABC?x=1')
        );
    }
}
