<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Scrapingbot
{
    private $username;
    private $apiKey;
    private $baseUrl;

    public function __construct()
    {
        $this->username = env('SCRAPINGBOT_USERNAME', '');
        $this->apiKey = env('SCRAPINGBOT_API_KEY', '');
        $this->baseUrl = rtrim(env('SCRAPINGBOT_BASE_URL', 'http://api.scraping-bot.io'), '/');
    }

    /**
     * Start a scrape job via POST
     *
     * @param string $scraper  Scraper type (e.g. 'tiktokProfile', 'instagramProfile')
     * @param array  $params   Scraper-specific parameters
     * @return array ['status' => bool, 'responseId' => string|null, 'msg' => string]
     */
    public function startScrape($scraper, $params = [])
    {
        $url = $this->baseUrl . '/scrape/data-scraper';

        $body = array_merge(['scraper' => $scraper], $params);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
            ],
            CURLOPT_USERPWD        => $this->username . ':' . $this->apiKey,
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return ['status' => false, 'responseId' => null, 'msg' => "cURL Error: $err"];
        }

        $data = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300 && !empty($data['responseId'])) {
            return [
                'status'     => true,
                'responseId' => $data['responseId'],
                'msg'        => 'Scrape job submitted'
            ];
        }

        return [
            'status'     => false,
            'responseId' => null,
            'msg'        => 'Failed to start scrape: ' . ($data['message'] ?? $response)
        ];
    }

    /**
     * Poll for scrape result via GET
     *
     * @param string $scraper     Scraper type
     * @param string $responseId  The responseId from startScrape
     * @return array ['status' => string, 'data' => mixed, 'msg' => string]
     *               status: 'success', 'pending', 'error'
     */
    public function pollResult($scraper, $responseId)
    {
        $url = $this->baseUrl . '/scrape/data-scraper-response?scraper=' . urlencode($scraper) . '&responseId=' . urlencode($responseId);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_USERPWD        => $this->username . ':' . $this->apiKey,
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return ['status' => 'error', 'data' => null, 'msg' => "cURL Error: $err"];
        }

        $data = json_decode($response, true);

        if ($httpCode == 200 && is_array($data)) {
            // ScrapingBot returns raw data array on success (e.g. [{"type":"profile",...}])
            if (isset($data[0]) && !isset($data['status'])) {
                // Detect error responses disguised as success: [{"message":"Something went wrong",...}]
                $firstItem = $data[0];
                if (isset($firstItem['message']) && ($firstItem['type'] ?? null) !== 'profile') {
                    return [
                        'status' => 'error',
                        'data'   => null,
                        'msg'    => 'Scrape returned error: ' . $firstItem['message']
                    ];
                }

                return [
                    'status' => 'success',
                    'data'   => $data,
                    'msg'    => 'Scrape completed'
                ];
            }

            if (isset($data['status']) && $data['status'] === 'success') {
                return [
                    'status' => 'success',
                    'data'   => $data['response'] ?? $data,
                    'msg'    => 'Scrape completed'
                ];
            }

            if (isset($data['status']) && $data['status'] === 'pending') {
                return [
                    'status' => 'pending',
                    'data'   => null,
                    'msg'    => 'Scrape still processing'
                ];
            }

            // Some responses use a message string without status when still processing
            if (isset($data['message']) && stripos($data['message'], 'not finished') !== false) {
                return [
                    'status' => 'pending',
                    'data'   => null,
                    'msg'    => 'Scrape still processing'
                ];
            }
        }

        // Non-JSON or unexpected responses that indicate "not finished" should be treated as pending
        if (is_string($response) && stripos($response, 'not finished') !== false) {
            return [
                'status' => 'pending',
                'data'   => null,
                'msg'    => 'Scrape still processing'
            ];
        }

        return [
            'status' => 'error',
            'data'   => null,
            'msg'    => 'Scrape failed: ' . ($data['message'] ?? $response)
        ];
    }

    /**
     * Convenience wrapper: start TikTok profile scrape
     *
     * @param string $url       TikTok profile URL (e.g. https://www.tiktok.com/@username)
     * @param int    $maxVideos Number of recent videos to include (default 10)
     * @return array startScrape result
     */
    public function scrapeTiktokProfile($url)
    {
        return $this->startScrape('tiktokProfile', [
            'url' => $url,
        ]);
    }

    /**
     * Convenience wrapper: start Instagram profile scrape
     *
     * @param string $account     Instagram username (without @)
     * @param int    $postsNumber Number of recent posts to include (default 12)
     * @return array startScrape result
     */
    public function scrapeInstagramProfile($account, $postsNumber = 12)
    {
        return $this->startScrape('instagramProfile', [
            'account'      => $account,
            'posts_number' => $postsNumber,
        ]);
    }

    /**
     * Build the scrape URL for a given entity
     *
     * @param string $type     Platform type ('Tiktok' or 'Instagram')
     * @param string $url      Profile URL
     * @return array ['scraper' => string, 'url' => string] or false on failure
     */
    public function buildScrapeParams($type, $url)
    {
        if ($type === 'Tiktok') {
            // Ensure URL is full TikTok profile URL
            $uri = explode("/", parse_url($url, PHP_URL_PATH));
            $username = $uri[1] ?? '';
            $username = str_replace('@', '', $username);
            if (empty($username)) {
                return false;
            }
            return [
                'scraper' => 'tiktokProfile',
                'params'  => [
                    'url' => 'https://www.tiktok.com/@' . $username,
                ],
            ];
        }

        if ($type === 'Instagram') {
            $uri = explode("/", parse_url($url, PHP_URL_PATH));
            $username = $uri[1] ?? '';
            $username = str_replace('@', '', $username);
            if (empty($username)) {
                return false;
            }
            return [
                'scraper' => 'instagramProfile',
                'params'  => [
                    'account'      => $username,
                    'posts_number' => 12,
                ],
            ];
        }

        return false;
    }
}
