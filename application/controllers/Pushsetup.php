<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pushsetup — serves the FCM web service worker (FCM Phase 7, web client).
 *
 * The service worker must be same-origin and is what receives push while the tab is in
 * the background. It's emitted from a controller (not a static file) so the Firebase web
 * config lives in .env alongside the rest of the FCM settings — no secrets hardcoded, and
 * the public web config (apiKey etc.) stays out of the repo. `Service-Worker-Allowed: /`
 * widens the SW scope to the whole site even though the script is served from this path.
 */
class Pushsetup extends CI_Controller
{
    /**
     * GET firebase-sw  ->  the FCM background service worker (application/javascript).
     */
    public function service_worker()
    {
        header('Content-Type: application/javascript; charset=utf-8');
        header('Service-Worker-Allowed: /');

        $config = json_encode($this->web_config(), JSON_UNESCAPED_SLASHES);

        echo <<<JS
importScripts('https://www.gstatic.com/firebasejs/10.12.2/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.12.2/firebase-messaging-compat.js');

try {
  firebase.initializeApp($config);
  const messaging = firebase.messaging();

  // Background pushes (tab not focused) -> system notification.
  messaging.onBackgroundMessage(function (payload) {
    var n = payload.notification || {};
    self.registration.showNotification(n.title || 'Notifikasi', {
      body: n.body || '',
      tag: (payload.data && payload.data.related_id) ? String(payload.data.related_id) : undefined,
      data: payload.data || {}
    });
  });
} catch (e) {
  // FCM web config not set yet — SW is a no-op until .env is filled.
}
JS;
    }

    /**
     * Public Firebase web app config, sourced from .env. These values are not secret
     * (they ship to the browser) but are kept in .env to avoid hardcoding per-project ids.
     */
    private function web_config()
    {
        return array(
            'apiKey'            => (string) env('FCM_WEB_API_KEY', ''),
            'authDomain'        => (string) env('FCM_WEB_AUTH_DOMAIN', ''),
            'projectId'         => (string) env('FCM_WEB_PROJECT_ID', ''),
            'messagingSenderId' => (string) env('FCM_WEB_MESSAGING_SENDER_ID', ''),
            'appId'             => (string) env('FCM_WEB_APP_ID', ''),
        );
    }
}
