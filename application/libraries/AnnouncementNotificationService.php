<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * AnnouncementNotificationService (announcement domain facade)
 *
 * Thin facade over NotificationDispatcher, mirroring NotificationService /
 * OvertimeNotificationService. Broadcasts a published announcement to every active
 * employee: each gets one in-app row + one queued FCM push (PushChannel), carrying
 * related_table='announcements' + related_id so the app deep-links to the detail.
 *
 * dedupe is global within the dispatcher window, so we dispatch per user with a
 * per-recipient dedupe_key (see NotificationEvents 'announcement.published') rather
 * than dispatchMany() with one shared key.
 */
class AnnouncementNotificationService
{
    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->library('NotificationDispatcher');
        $this->CI->load->model('AnnouncementModel');
    }

    /**
     * Broadcast a published announcement to all active employees.
     *
     * @param array $announcement Row from announcements (needs id, title, category).
     * @return int Number of notifications written.
     */
    public function broadcastPublished(array $announcement)
    {
        $announcementId = $announcement['id'] ?? null;
        if (!$announcementId) {
            return 0;
        }

        $userIds = $this->CI->AnnouncementModel->active_employee_ids();
        $count = 0;
        foreach ($userIds as $userId) {
            $written = $this->CI->notificationdispatcher->dispatch($userId, 'announcement.published', array(
                'announcement_id' => (int) $announcementId,
                'title'           => $announcement['title'] ?? null,
                'category'        => $announcement['category'] ?? null,
                'user_id'         => (int) $userId,
            ));
            if ($written) {
                $count++;
            }
        }
        return $count;
    }
}
