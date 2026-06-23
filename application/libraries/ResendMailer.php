<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Thin wrapper over the Resend HTTPS API (https://resend.com) for transactional
 * email (currently: password-reset links). Chosen over the legacy SMTP path for
 * deliverability and speed — a single non-blocking HTTPS POST instead of an SMTP
 * handshake.
 *
 * Config via .env:
 *   RESEND_API_KEY   API key (required to actually send)
 *   MAIL_FROM        From header, e.g. "Forbes Skin <noreply@acnenosystem.com>"
 *
 * Failures return false and are reported to Sentry; callers decide UX (the reset
 * flow keeps responses generic and tells the user to try again later).
 */
class ResendMailer
{
    protected $CI;
    protected $apiKey;
    protected $from;
    protected $endpoint = 'https://api.resend.com/emails';

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->helper('sentry');
        $this->apiKey = (string) env('RESEND_API_KEY', '');
        $this->from = (string) env('MAIL_FROM', 'Forbes Skin <noreply@acnenosystem.com>');
    }

    /**
     * Send an HTML email.
     *
     * @param string $toEmail recipient address
     * @param string $toName  recipient display name (optional)
     * @param string $subject subject line
     * @param string $htmlBody HTML body
     * @return bool true on a 2xx Resend response
     */
    public function send($toEmail, $toName, $subject, $htmlBody)
    {
        $toEmail = trim((string) $toEmail);
        if ($toEmail === '') {
            return false;
        }

        if ($this->apiKey === '') {
            sentry_capture_message('ResendMailer: RESEND_API_KEY is not configured', array(
                'to' => $toEmail,
                'subject' => $subject,
            ));
            return false;
        }

        $to = $toName !== '' ? sprintf('%s <%s>', $toName, $toEmail) : $toEmail;
        $payload = json_encode(array(
            'from' => $this->from,
            'to' => array($to),
            'subject' => (string) $subject,
            'html' => (string) $htmlBody,
        ));

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($err !== '') {
            sentry_capture_message('ResendMailer: cURL error', array(
                'to' => $toEmail,
                'error' => $err,
            ));
            return false;
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            sentry_capture_message('ResendMailer: non-2xx response', array(
                'to' => $toEmail,
                'http_code' => $httpCode,
                'response' => is_string($response) ? substr($response, 0, 500) : null,
            ));
            return false;
        }

        return true;
    }
}
