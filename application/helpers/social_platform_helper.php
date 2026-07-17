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
