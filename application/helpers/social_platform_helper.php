<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Social platform registry for the content-optimization feature.
 *
 * Single source of truth for:
 *  - which platforms support real auto-fetch of per-post metrics, and
 *  - detecting the platform from a content link so the requestor form can
 *    auto-select it.
 *
 * Canonical platform strings match the values used in the endorse forms:
 *   Tiktok, Instagram, Threads, Youtube, Twitter, Facebook
 */

if (!function_exists('social_auto_fetch_platforms')) {
    /**
     * Map of platform => supports real auto-fetch.
     * Flip a value to true once its per-post scraper is implemented.
     */
    function social_auto_fetch_platforms()
    {
        return [
            'Tiktok'    => true,
            'Instagram' => true,
            'Threads'   => true,
            'Youtube'   => false,
            'Twitter'   => false,
            'Facebook'  => false,
        ];
    }
}

if (!function_exists('is_auto_fetch_platform')) {
    /**
     * Whether the given platform supports automatic metric fetching.
     * Placeholder platforms return false (metrics entered manually).
     */
    function is_auto_fetch_platform($platform)
    {
        $map = social_auto_fetch_platforms();
        $key = trim((string) $platform);
        return isset($map[$key]) ? (bool) $map[$key] : false;
    }
}

if (!function_exists('detect_platform_from_url')) {
    /**
     * Detect the canonical platform string from a content URL.
     * Returns '' when the platform can't be determined.
     */
    function detect_platform_from_url($url)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }

        $patterns = [
            'Tiktok'    => '#(^|\.)tiktok\.com#i',
            'Instagram' => '#(^|\.)instagram\.com#i',
            'Threads'   => '#(^|\.)threads\.(com|net)#i',
            'Youtube'   => '#(^|\.)(youtube\.com|youtu\.be)#i',
            'Twitter'   => '#(^|\.)(twitter\.com|x\.com)#i',
            'Facebook'  => '#(^|\.)(facebook\.com|fb\.watch|fb\.com)#i',
        ];

        $host = parse_url($url, PHP_URL_HOST);
        $haystack = $host !== null ? $host : $url;

        foreach ($patterns as $platform => $pattern) {
            if (preg_match($pattern, $haystack)) {
                return $platform;
            }
        }

        return '';
    }
}

if (!function_exists('platform_requires_username_from_url')) {
    /**
     * Whether the creator username can be read straight out of a content URL.
     *
     * TikTok (/@user/video/...) and Threads (/@user/post/...) carry the username
     * in the path, so the influencer is resolved by reverse-lookup. Instagram post
     * URLs (/p/SHORTCODE, /reel/SHORTCODE) do not, so the influencer must come from
     * the form selection instead.
     */
    function platform_requires_username_from_url($platform)
    {
        $key = trim((string) $platform);
        return strcasecmp($key, 'Tiktok') === 0 || strcasecmp($key, 'Threads') === 0;
    }
}

if (!function_exists('extract_username_from_content_url')) {
    /**
     * Extract the creator username from a content URL for the given platform.
     * Returns '' when the platform has no username in the URL or the URL doesn't match.
     */
    function extract_username_from_content_url($url, $platform)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }

        $patterns = [
            'Tiktok'  => '#tiktok\.com/@([^/]+)/#i',
            'Threads' => '#threads\.(?:com|net)/@([^/]+)/post/#i',
        ];

        foreach ($patterns as $key => $pattern) {
            if (strcasecmp($key, (string) $platform) !== 0) {
                continue;
            }
            if (preg_match($pattern, $url, $match)) {
                return $match[1];
            }
        }

        return '';
    }
}

if (!function_exists('normalize_content_url_for_duplicate_check')) {
    /**
     * Collapse a content URL to a comparable key so trivially different links
     * (scheme, www, query string, fragment, trailing slash, casing of the host)
     * still register as the same content.
     */
    function normalize_content_url_for_duplicate_check($url)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }

        $parts = parse_url($url);
        $host = isset($parts['host']) ? strtolower($parts['host']) : '';
        $path = isset($parts['path']) ? $parts['path'] : '';

        if ($host === '') {
            // Scheme-less link: parse_url puts everything in path.
            $path = preg_replace('#^//#', '', $path);
            $segments = explode('/', $path, 2);
            $host = strtolower($segments[0]);
            $path = isset($segments[1]) ? '/' . $segments[1] : '';
        }

        $host = preg_replace('#^www\.#i', '', $host);
        $path = rtrim($path, '/');

        return $host . $path;
    }
}
