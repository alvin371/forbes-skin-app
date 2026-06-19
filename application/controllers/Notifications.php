<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

/**
 * Notifications (web inbox)
 *
 * Renders the in-app notification list and serves the small JSON endpoints the
 * dashboard polls. All data access goes through NotificationModel — no raw SQL /
 * string interpolation, which previously allowed SQL injection via $_GET['keyword'].
 * Writes are owned by NotificationDispatcher; this controller only reads + updates
 * read-state / deletes for the logged-in user.
 */
class Notifications extends BaseController
{
    protected $require_permissions = true;
    protected $show_403_on_deny = true;
    protected $public_methods = []; // All methods require permissions

    public function __construct()
    {
        parent::__construct();
        $this->load->library('permission');
        $this->load->library('template');
        $this->load->model('NotificationModel');
    }

    public function index()
    {
        $data['user'] = $_SESSION['user'];
        $user_id = (int) $data['user']['id'];

        $data['can_delete'] = $this->permission->check_permission($user_id, 'notifications', 'delete');

        $filters = array(
            'keyword' => $this->input->get('keyword') ?? '',
            'is_read' => $this->input->get('is_read_filter') ?? '',
        );

        $per_page = 20;
        $total_notifications = $this->NotificationModel->countAll($user_id, $filters);

        $data['page'] = (int) ceil($total_notifications / $per_page);
        $data['notif'] = '<p class="mb-1"><label class="text-notif">'
            . $this->template->separator_only($total_notifications)
            . ' notifikasi ditemukan!</label></p>';

        $current_page = max(1, (int) ($this->input->get('page') ?? 1));
        $offset = ($current_page - 1) * $per_page;

        $data['notifications'] = $this->NotificationModel->listForUser($user_id, $filters, $per_page, $offset);

        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $data['title'] = 'Semua Notifikasi - ' . $this->template->title();
        $data['content'] = $this->load->view("notifications/index", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function get_notifications()
    {
        $user_id = (int) $_SESSION['user']['id'];
        $limit = $this->input->get('limit') ? (int) $this->input->get('limit') : 10;

        $this->json(array(
            'success'       => true,
            'notifications' => $this->NotificationModel->getRecent($user_id, $limit),
            'unread_count'  => $this->NotificationModel->unreadCount($user_id),
        ));
    }

    // API: unread notification count
    public function get_unread_count()
    {
        $user_id = (int) $_SESSION['user']['id'];

        $this->json(array(
            'success' => true,
            'count'   => $this->NotificationModel->unreadCount($user_id),
        ));
    }

    public function mark_read()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $notification_id = $input['notification_id'] ?? null;
        $user_id = (int) $_SESSION['user']['id'];

        if (!$notification_id) {
            $this->json(array('success' => false, 'message' => 'Notification ID required'));
            return;
        }

        $affected = $this->NotificationModel->markRead($notification_id, $user_id);
        $this->json(array(
            'success' => $affected > 0,
            'updated' => $affected,
            'message' => $affected > 0
                ? 'Notification marked as read'
                : 'Notification not found or already read',
        ));
    }

    public function mark_all_read()
    {
        $user_id = (int) $_SESSION['user']['id'];
        $affected = $this->NotificationModel->markAllRead($user_id);

        $this->json(array(
            'success' => true,
            'updated' => $affected,
            'message' => $affected . ' notification(s) marked as read',
        ));
    }

    public function delete($id = null)
    {
        if (!$id) {
            $this->session->set_flashdata('error', 'ID notifikasi tidak valid');
            redirect('notifications');
        }

        $user_id = (int) $_SESSION['user']['id'];
        $affected = $this->NotificationModel->delete($id, $user_id);

        if ($affected > 0) {
            $this->session->set_flashdata('success', 'Notifikasi berhasil dihapus');
        } else {
            $this->session->set_flashdata('error', 'Notifikasi tidak ditemukan');
        }

        redirect('notifications');
    }

    public function clear_read()
    {
        $user_id = (int) $_SESSION['user']['id'];
        $affected = $this->NotificationModel->clearRead($user_id);

        $this->json(array(
            'success' => true,
            'deleted' => $affected,
            'message' => $affected . ' read notification(s) cleared',
        ));
    }

    /**
     * Emit a JSON response.
     */
    private function json(array $payload)
    {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }
}
