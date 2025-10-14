<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Load env helper if not already loaded
if (!function_exists('env')) {
    require_once APPPATH . 'helpers/env_helper.php';
}

$config['telegram_bot_token'] = env('TELEGRAM_BOT_TOKEN', '');
$config['telegram_group_chat_id'] = env('TELEGRAM_GROUP_CHAT_ID', '');
$config['telegram_parse_mode'] = env('TELEGRAM_PARSE_MODE', 'HTML');
