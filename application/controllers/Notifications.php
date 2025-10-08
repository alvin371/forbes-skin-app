<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

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
    }
    
    public function index()
    {
        $data['user'] = $_SESSION['user'];
        $user_id = $data['user']['id'];
        
        // Pass permission data to view
        $data['can_delete'] = $this->permission->check_permission($user_id, 'notifications', 'delete');
        
        $keyword = $_GET['keyword'] ?? "";
        $is_read_filter = $_GET['is_read_filter'] ?? "";
        
        $qry = "user_id = '$user_id'";
        
        if ($keyword) {
            $qry .= " AND (title LIKE '%$keyword%' OR message LIKE '%$keyword%')";
        }
        
        if ($is_read_filter !== "") {
            $qry .= " AND is_read = '$is_read_filter'";
        }

        // Count total notifications for pagination
        $count_query = $this->mymodel->selectWithQuery("SELECT COUNT(id) AS count FROM notifications WHERE $qry");
        $total_notifications = $count_query[0]['count'];
        
        $data['page'] = CEIL($total_notifications / 20);
        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($total_notifications) . ' notifikasi ditemukan!</label></p>';

        // Pagination
        $current_page = intval($_GET['page'] ?? 1);
        if ($current_page <= 1) {
            $current_page = 1;
        }
        
        $offset = ($current_page - 1) * 20;

        // Get paginated notifications
        $notifications = $this->mymodel->selectWithQuery("SELECT * FROM notifications WHERE $qry ORDER BY created_at DESC LIMIT $offset, 20");
        $data['notifications'] = $notifications;
        
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);
        
        $data['title'] = 'Semua Notifikasi - ' . $this->template->title();
        $data['content'] = $this->load->view("notifications/index", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function get_notifications()
    {
        $user_id = $_SESSION['user']['id'];
        $limit = $this->input->get('limit') ? (int)$this->input->get('limit') : 10;
        
        // Get notifications
        $notifications = $this->mymodel->selectWithQuery("
            SELECT * 
            FROM notifications 
            WHERE user_id = '$user_id' 
            ORDER BY created_at DESC 
            LIMIT $limit
        ");
        
        // Count unread notifications
        $unread_count = $this->mymodel->selectWithQuery("
            SELECT COUNT(id) as count 
            FROM notifications 
            WHERE user_id = '$user_id' AND is_read = 0
        ")[0]['count'];
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => $unread_count
            ]));
    }

    // API: Mendapatkan jumlah notifikasi yang belum dibaca
    public function get_unread_count()
    {
        $user_id = $_SESSION['user']['id'];
        
        $count = $this->mymodel->selectWithQuery("
            SELECT COUNT(id) as count 
            FROM notifications 
            WHERE user_id = '$user_id' AND is_read = 0
        ")[0]['count'];
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => true,
                'count' => $count
            ]));
    }

    public function mark_read()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $notification_id = $input['notification_id'] ?? null;
        $user_id = $_SESSION['user']['id'];
        
        if (!$notification_id) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false, 
                    'message' => 'Notification ID required'
                ]));
            return;
        }
        
        $result = $this->db->update('notifications', ['is_read' => 1], [
            'id' => $notification_id,
            'user_id' => $user_id
        ]);
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => $result,
                'message' => $result ? 'Notification marked as read' : 'Failed to mark notification as read'
            ]));
    }

    public function mark_all_read()
    {
        $user_id = $_SESSION['user']['id'];
        
        $result = $this->db->update('notifications', ['is_read' => 1], [
            'user_id' => $user_id,
            'is_read' => 0
        ]);
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => $result,
                'message' => $result ? 'All notifications marked as read' : 'Failed to mark notifications as read'
            ]));
    }

    public function delete($id = null)
    {
        if (!$id) {
            $this->session->set_flashdata('error', 'ID notifikasi tidak valid');
            redirect('notifications');
        }
        
        $user_id = $_SESSION['user']['id'];
        
        $result = $this->db->delete('notifications', [
            'id' => $id,
            'user_id' => $user_id
        ]);
        
        if ($result) {
            $this->session->set_flashdata('success', 'Notifikasi berhasil dihapus');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus notifikasi');
        }
        
        redirect('notifications');
    }

    public function clear_read()
    {
        $user_id = $_SESSION['user']['id'];
        
        $result = $this->db->delete('notifications', [
            'user_id' => $user_id,
            'is_read' => 1
        ]);
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => $result,
                'message' => $result ? 'Read notifications cleared' : 'Failed to clear notifications'
            ]));
    }

    public static function send_notification($db, $user_id, $title, $message, $type = 'info', $related_table = null, $related_id = null)
    {
        $notification_data = [
            'user_id' => $user_id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'related_table' => $related_table,
            'related_id' => $related_id,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $db->insert('notifications', $notification_data);
    }
}