<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * PushChannel — the push delivery channel (FCM Phase 4).
 *
 * Bridges NotificationDispatcher to the outbox queue. enqueue() writes exactly ONE
 * outbox row per recipient; the Phase 5 cron worker expands that to the user's live
 * device tokens at send time (fewer rows, always a fresh token list). Knows nothing
 * about FCM transport — it only queues.
 *
 * Loaded lowercase per CI3 convention: $this->pushchannel.
 */
class PushChannel
{
    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('NotificationOutboxModel');
    }

    /**
     * Queue a push for one user from a built event payload (NotificationEvents shape).
     * Body text is generic; only routing IDs go into data_json (no PII in the push).
     *
     * @param int   $userId
     * @param array $event ['title','message','type','related_table','related_id','event_key']
     * @return int|false Outbox row id, or false.
     */
    public function enqueue($userId, array $event)
    {
        if (!$userId) {
            return false;
        }

        $data = array(
            'type'          => $event['type'] ?? null,
            'related_table' => $event['related_table'] ?? null,
            'related_id'    => $event['related_id'] ?? null,
        );

        return $this->CI->NotificationOutboxModel->enqueue(
            (int) $userId,
            $event['event_key'] ?? null,
            $event['title'] ?? '',
            $event['message'] ?? '',
            $data
        );
    }
}
