<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * NotificationDispatcher
 *
 * The single entry point for emitting notifications. Resolves an event template
 * (NotificationEvents), applies cross-process deduplication, and writes the in-app
 * row (NotificationModel). This is the one place that turns a domain event into a
 * stored notification.
 *
 * Reuse: any service emits a notification with
 *     $this->notificationdispatcher->dispatch($userId, 'leave.approved', $ctx);
 *
 * Channels: in-app today. The push channel hooks in at Phase 4 (see dispatch()),
 * gated by the same dedupe decision so a deduped event is suppressed on every channel.
 */
class NotificationDispatcher
{
    /** Type constants mirror NotificationEvents for callers that build payloads directly. */
    const TYPE_INFO    = 'info';
    const TYPE_SUCCESS = 'success';
    const TYPE_WARNING = 'warning';
    const TYPE_ERROR   = 'error';

    /** Deduplication window in seconds. */
    protected $dedupeWindow = 60;

    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('NotificationModel');
        $this->CI->load->library('NotificationEvents');
    }

    /**
     * Dispatch a registered event to a single user.
     *
     * @param int    $userId Recipient user id
     * @param string $eventKey e.g. 'leave.approved'
     * @param array  $ctx Context for the event template
     * @return bool True if a notification was written; false if skipped (no user,
     *              unknown event, or deduplicated).
     */
    public function dispatch($userId, $eventKey, array $ctx = array())
    {
        if (!$userId) {
            log_message('warning', 'NotificationDispatcher: no user id for event ' . $eventKey);
            return false;
        }

        $event = $this->CI->notificationevents->build($eventKey, $ctx);
        if ($event === null) {
            return false; // unknown key already logged by registry
        }

        // Carry the event key so the push channel can record which event produced the row.
        $event['event_key'] = $eventKey;

        return $this->emit($userId, $event);
    }

    /**
     * Dispatch the same event to many users (e.g. all HR admins).
     *
     * @param array  $userIds
     * @param string $eventKey
     * @param array  $ctx
     * @return int Number of notifications written.
     */
    public function dispatchMany(array $userIds, $eventKey, array $ctx = array())
    {
        $count = 0;
        foreach ($userIds as $userId) {
            if ($this->dispatch($userId, $eventKey, $ctx)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Write a pre-built event payload for a user. Lower-level than dispatch();
     * use it when an event has no registry template yet.
     *
     * @param int   $userId
     * @param array $event ['title','message','type','related_table','related_id','dedupe_key']
     * @return bool
     */
    public function emit($userId, array $event)
    {
        if (!$userId) {
            return false;
        }

        $dedupeKey = $event['dedupe_key'] ?? null;
        if ($dedupeKey && $this->CI->NotificationModel->existsByDedupeKey($dedupeKey, $this->dedupeWindow)) {
            log_message('debug', 'NotificationDispatcher: duplicate skipped: ' . $dedupeKey);
            return false;
        }

        $id = $this->CI->NotificationModel->insert(array(
            'user_id'       => $userId,
            'title'         => $event['title'] ?? null,
            'message'       => $event['message'] ?? '',
            'type'          => $event['type'] ?? self::TYPE_INFO,
            'related_table' => $event['related_table'] ?? null,
            'related_id'    => $event['related_id'] ?? null,
            'dedupe_key'    => $dedupeKey,
        ));

        if (!$id) {
            return false;
        }

        // Push delivery (FCM Phase 4): gated by the same dedupe decision above, so a
        // suppressed event is suppressed on every channel. Queues one outbox row; the cron
        // worker expands it to the user's live device tokens. Users with no tokens -> the
        // worker marks the row SENT, no error. Never let push failure undo the in-app write.
        // no_push events (e.g. the "push is broken" alert) stay in-app only to avoid a loop.
        if (empty($event['no_push'])) {
            try {
                $this->CI->load->library('PushChannel');
                $this->CI->pushchannel->enqueue($userId, $event);
            } catch (Exception $e) {
                log_message('error', 'NotificationDispatcher: push enqueue failed: ' . $e->getMessage());
            }
        }

        return true;
    }
}
