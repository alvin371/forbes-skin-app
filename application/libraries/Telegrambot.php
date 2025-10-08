<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class TelegramBot {
    protected $token;
    protected $parse_mode;

    public function __construct()
    {
        $CI =& get_instance();
        $CI->load->config('telegram');
        $this->token = $CI->config->item('telegram_bot_token');
        $this->parse_mode = $CI->config->item('telegram_parse_mode') ?: 'HTML';
    }

    protected function request($method, $params = [])
    {
        $url = "https://api.telegram.org/bot{$this->token}/{$method}";
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $params,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 20,
        ]);
        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) {
            log_message('error', 'Telegram API error: '.$err);
            return false;
        }
        $decoded = json_decode($resp, true);
        if (!($decoded['ok'] ?? false)) {
            log_message('error', 'Telegram API not ok: '.$resp);
            return false;
        }
        return $decoded['result'];
    }

    public function sendMessage($chat_id, $text, $parse_mode = null, $disable_preview = true)
    {
        return $this->request('sendMessage', [
            'chat_id'                  => $chat_id,
            'text'                     => $text,
            'parse_mode'               => $parse_mode ?: $this->parse_mode,
            'disable_web_page_preview' => $disable_preview ? 'true' : 'false',
        ]);
    }
}
