<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class Quest extends BaseController
{
    // Configure BaseController for automatic permission checking
    protected $require_permissions = true;
    protected $show_403_on_deny = true;
    protected $public_methods = []; // No public methods - all require authentication

    public function __construct()
    {
        parent::__construct();

        // Load required libraries and models for BaseController integration
        $this->load->database();
        $this->load->model('mymodel');
        $this->load->library('permission');
        $this->load->library('template');

        // Configure method-to-permission mappings for quest approval methods
        $this->set_method_permissions([
            'approve_main_quest_submission' => 'approve',
            'deny_main_quest_submission' => 'approve',
            'approve_side_quest_submission' => 'approve',
            'deny_side_quest_submission' => 'approve',
            'approve_main_quest_submission_with_promotion' => 'approve'
        ]);
    }

    public function index()
    {
        $data['user'] = $_SESSION['user'];
        $user_id = $data['user']['id'];
        
        // Check if user has permission to view quest module
        $this->permission->enforce_permission($user_id, 'quest', 'view');

        $data['title'] = 'Quest Management - ' . $this->template->title();
        $data['content'] = $this->load->view("quest/index", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    // Main Quest Methods
    public function main_quest_tab()
    {
        $data['user'] = $_SESSION['user'];
        $data['template'] = $this->template;
        $user_id = $data['user']['id'];
        
        // Check permission and pass permission data to view
        $this->permission->enforce_permission($user_id, 'quest', 'view');
        $data['can_create'] = $this->permission->check_permission($user_id, 'quest', 'create');
        $data['can_edit'] = $this->permission->check_permission($user_id, 'quest', 'edit');
        $data['can_delete'] = $this->permission->check_permission($user_id, 'quest', 'delete');
        
        $keyword_category = $_GET['keyword_category'] ?? "Judul";
        $keyword = $_GET['keyword'] ?? "";
        $level_filter = $_GET['level_filter'] ?? "";
        
        $data['keyword_category'] = $keyword_category;
        $data['level_filter'] = $level_filter;
        
        // Get positions for filter dropdown with level information
        $data['positions'] = $this->mymodel->selectWithQuery("SELECT p.*, ql.name as level_name FROM positions p LEFT JOIN quest_levels ql ON p.level_id = ql.id ORDER BY p.level_id ASC, p.name ASC");

        $qry = "1=1";
        
        if ($keyword) {
            if ($keyword_category == "Judul") {
                $qry .= " AND mq.title LIKE '%$keyword%'";
            } else if ($keyword_category == "Deskripsi") {
                $qry .= " AND mq.description LIKE '%$keyword%'";
            } else if ($keyword_category == "Position") {
                $qry .= " AND p.name LIKE '%$keyword%'";
            }
        }
        
        if ($level_filter) {
            $qry .= " AND mq.required_position_id = '$level_filter'";
        }

        $query = $this->mymodel->selectWithQuery("SELECT COUNT(mq.id) AS count 
            FROM main_quests mq 
            LEFT JOIN positions p ON mq.required_position_id = p.id 
            LEFT JOIN quest_levels ql ON p.level_id = ql.id 
            WHERE $qry");
        $data['page'] = CEIL($query[0]['count'] / 10);
        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query[0]['count']) . ' data ditemukan!</label></p>';

        $current_page = intval($_GET['page'] ?? 1);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $url = base_url() . '/quest/main_quest_tab/' . $this->template->get_param();
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $this->load->view("quest/main_quest_tab", $data);
    }

    public function main_quest_item()
    {
        $data['template'] = $this->template;
        $user_id = $_SESSION['user']['id'];
        
        // Pass permission data to view
        $data['can_create'] = $this->permission->check_permission($user_id, 'quest', 'create');
        $data['can_edit'] = $this->permission->check_permission($user_id, 'quest', 'edit');
        $data['can_delete'] = $this->permission->check_permission($user_id, 'quest', 'delete');
        $keyword_category = $_GET['keyword_category'] ?? "Judul";
        $keyword = $_GET['keyword'] ?? "";
        $level_filter = $_GET['level_filter'] ?? "";
        
        $qry = "1=1";
        
        if ($keyword) {
            if ($keyword_category == "Judul") {
                $qry .= " AND mq.title LIKE '%$keyword%'";
            } else if ($keyword_category == "Deskripsi") {
                $qry .= " AND mq.description LIKE '%$keyword%'";
            } else if ($keyword_category == "Position") {
                $qry .= " AND p.name LIKE '%$keyword%'";
            }
        }
        
        if ($level_filter) {
            $qry .= " AND mq.required_position_id = '$level_filter'";
        }

        $limit = 10;
        $current_page = $_GET['page'] ?? 1;
        
        if ($current_page <= 1) {
            $offset = 0;
        } else {
            $offset = ($current_page - 1) * $limit;
        }

        $query = $this->mymodel->selectWithQuery("SELECT mq.*, p.name as position_name, ql.name as level_name, u.full_name as creator_name
            FROM main_quests mq 
            LEFT JOIN positions p ON mq.required_position_id = p.id 
            LEFT JOIN quest_levels ql ON p.level_id = ql.id 
            LEFT JOIN user u ON mq.created_by = u.id 
            WHERE $qry 
            ORDER BY mq.created_at DESC 
            LIMIT $offset, $limit");
        $data['data'] = $query;
        $data['start'] = $offset;
        
        $this->load->view("quest/main_quest_item", $data);
    }

    // Side Quest Methods
    public function side_quest_tab()
    {
        $data['user'] = $_SESSION['user'];
        $data['template'] = $this->template;
        
        $keyword_category = $_GET['keyword_category'] ?? "Judul";
        $keyword = $_GET['keyword'] ?? "";
        
        $data['keyword_category'] = $keyword_category;

        $qry = "1=1";
        
        if ($keyword) {
            if ($keyword_category == "Judul") {
                $qry .= " AND sq.title LIKE '%$keyword%'";
            } else if ($keyword_category == "Deskripsi") {
                $qry .= " AND sq.description LIKE '%$keyword%'";
            } else if ($keyword_category == "Creator") {
                $qry .= " AND u.full_name LIKE '%$keyword%'";
            }
        }

        $query = $this->mymodel->selectWithQuery("SELECT COUNT(sq.id) AS count 
            FROM side_quests sq 
            LEFT JOIN user u ON sq.created_by = u.id 
            WHERE $qry");
        $data['page'] = CEIL($query[0]['count'] / 10);
        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query[0]['count']) . ' data ditemukan!</label></p>';

        $current_page = intval($_GET['page'] ?? 1);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $url = base_url() . '/quest/side_quest_tab/' . $this->template->get_param();
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $this->load->view("quest/side_quest_tab", $data);
    }

    public function side_quest_item()
    {
        $data['template'] = $this->template;
        $keyword_category = $_GET['keyword_category'] ?? "Judul";
        $keyword = $_GET['keyword'] ?? "";
        
        $qry = "1=1";
        
        if ($keyword) {
            if ($keyword_category == "Judul") {
                $qry .= " AND sq.title LIKE '%$keyword%'";
            } else if ($keyword_category == "Deskripsi") {
                $qry .= " AND sq.description LIKE '%$keyword%'";
            } else if ($keyword_category == "Creator") {
                $qry .= " AND u.full_name LIKE '%$keyword%'";
            }
        }

        $limit = 10;
        $current_page = $_GET['page'] ?? 1;
        
        if ($current_page <= 1) {
            $offset = 0;
        } else {
            $offset = ($current_page - 1) * $limit;
        }

        $query = $this->mymodel->selectWithQuery("SELECT sq.*, u.full_name as creator_name 
            FROM side_quests sq 
            LEFT JOIN user u ON sq.created_by = u.id 
            WHERE $qry 
            ORDER BY sq.created_at DESC 
            LIMIT $offset, $limit");
        $data['data'] = $query;
        $data['start'] = $offset;
        
        $this->load->view("quest/side_quest_item", $data);
    }

    // Main Quest CRUD Methods
    public function main_quest_create_page()
    {
        $data['user'] = $_SESSION['user'];
        $user_id = $data['user']['id'];
        
        // Check if user has permission to create quests
        $this->permission->enforce_permission($user_id, 'quest', 'create');

        $data['data'] = array();
        $data['positions'] = $this->mymodel->selectWithQuery("SELECT p.*, ql.name as level_name FROM positions p LEFT JOIN quest_levels ql ON p.level_id = ql.id ORDER BY p.level_id ASC, p.name ASC");
        $data['title'] = 'Tambah Main Quest - ' . $this->template->title();
        $data['content'] = $this->load->view("quest/main_quest_create", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function main_quest_store()
    {
        $user = $_SESSION['user'];
        $user_id = $user['id'];

        // Check if user has permission to create quests
        $this->permission->enforce_permission($user_id, 'quest', 'create');

        $dt = $_POST['dt'];

        // Handle both single quest (legacy) and multiple quests (new)
        if (!is_array($dt) || empty($dt)) {
            $msg = 'Data quest tidak valid!';
            echo $this->template->alert_danger($msg);
            return;
        }

        // Check if it's a single quest (old format) or multiple quests (new format)
        $is_single_quest = isset($dt['title']) && !is_array($dt['title']);

        if ($is_single_quest) {
            // Handle single quest (legacy format)
            $quests = array($dt);
        } else {
            // Handle multiple quests (new format)
            $quests = $dt;
        }

        // Validate all quests before insertion
        $validation_errors = array();
        $quest_count = count($quests);

        foreach ($quests as $index => $quest) {
            $quest_number = $index + 1;

            if (empty($quest['title'])) {
                $validation_errors[] = "Quest #$quest_number: Judul quest harus diisi";
            }

            if (empty($quest['required_position_id'])) {
                $validation_errors[] = "Quest #$quest_number: Position requirement harus dipilih";
            }

            if (empty($quest['default_benefit'])) {
                $validation_errors[] = "Quest #$quest_number: Default benefit harus diisi";
            }

            if (empty($quest['description'])) {
                $validation_errors[] = "Quest #$quest_number: Deskripsi quest harus diisi";
            }
        }

        if (!empty($validation_errors)) {
            $error_list = '<ul class="mb-0">' . implode('', array_map(function($error) {
                return "<li>$error</li>";
            }, $validation_errors)) . '</ul>';

            $msg = '<strong>Validasi gagal:</strong><br>' . $error_list;
            echo $this->template->alert_danger($msg);
            return;
        }

        // Start database transaction for bulk insert
        $this->db->trans_start();

        $success_count = 0;
        $failed_quests = array();

        foreach ($quests as $index => $quest) {
            $quest_number = $index + 1;

            // Add audit fields
            $quest['created_by'] = $user['id'];
            $quest['created_at'] = date('Y-m-d H:i:s');

            // Insert quest
            if ($this->db->insert('main_quests', $quest)) {
                $success_count++;
            } else {
                $failed_quests[] = "Quest #$quest_number: " . $quest['title'];
            }
        }

        // Complete transaction
        $this->db->trans_complete();

        // Check transaction status and provide feedback
        if ($this->db->trans_status() === FALSE || !empty($failed_quests)) {
            $this->db->trans_rollback();

            if (!empty($failed_quests)) {
                $failed_list = '<ul class="mb-0">' . implode('', array_map(function($quest) {
                    return "<li>$quest</li>";
                }, $failed_quests)) . '</ul>';

                $msg = '<strong>Beberapa quest gagal disimpan:</strong><br>' . $failed_list;
                echo $this->template->alert_danger($msg);
            } else {
                $msg = 'Terjadi kesalahan saat menyimpan quest. Silakan coba lagi.';
                echo $this->template->alert_danger($msg);
            }
        } else {
            // Success message based on quest count
            if ($quest_count == 1) {
                $msg = 'Quest berhasil disimpan!';
            } else {
                $msg = "Berhasil menyimpan $success_count quest dari total $quest_count quest!";
            }
            echo $this->template->alert_success($msg);
        }
    }

    public function main_quest_edit_page()
    {
        $data['user'] = $_SESSION['user'];
        $user_id = $data['user']['id'];
        
        // Check if user has permission to edit quests
        $this->permission->enforce_permission($user_id, 'quest', 'edit');

        $id = $_GET['id'];
        $query = $this->mymodel->selectWithQuery("SELECT * FROM main_quests WHERE id = '$id'");
        
        if (empty($query)) {
            redirect(base_url() . 'quest');
        }

        $data['data'] = $query[0];
        $data['positions'] = $this->mymodel->selectWithQuery("SELECT p.*, ql.name as level_name FROM positions p LEFT JOIN quest_levels ql ON p.level_id = ql.id ORDER BY p.level_id ASC, p.name ASC");
        $data['title'] = 'Edit Main Quest - ' . $this->template->title();
        $data['content'] = $this->load->view("quest/main_quest_edit", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function main_quest_update()
    {
        $user = $_SESSION['user'];
        $user_id = $user['id'];

        // Check if user has permission to edit quests
        $this->permission->enforce_permission($user_id, 'quest', 'edit');

        $id = $_POST['id'];
        $dt = $_POST['dt'];

        // Validate required fields
        if (empty($dt['required_position_id'])) {
            $msg = 'Position requirement harus dipilih!';
            echo $this->template->alert_danger($msg);
            return;
        }

        if (empty($dt['default_benefit'])) {
            $msg = 'Default benefit harus diisi!';
            echo $this->template->alert_danger($msg);
            return;
        }

        if ($this->db->update('main_quests', $dt, array('id' => $id))) {
            $msg = 'Update data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Update data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }

    public function main_quest_detail()
    {
        $data['user'] = $_SESSION['user'];
        
        if (!in_array($data['user']['role'], array('1', '2'))) {
            redirect(base_url() . 'dashboard');
        }

        $id = $_GET['id'];
        $query = $this->mymodel->selectWithQuery("SELECT mq.*, p.name as position_name, ql.name as level_name, u.full_name as creator_name
            FROM main_quests mq 
            LEFT JOIN positions p ON mq.required_position_id = p.id
            LEFT JOIN quest_levels ql ON p.level_id = ql.id 
            LEFT JOIN user u ON mq.created_by = u.id 
            WHERE mq.id = '$id'");
        
        if (empty($query)) {
            redirect(base_url() . 'quest');
        }

        $data['data'] = $query[0];
        $data['title'] = 'Detail Main Quest - ' . $this->template->title();
        $data['content'] = $this->load->view("quest/main_quest_detail", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function main_quest_remove()
    {
        $id = $_GET['id'];
        $data['data']['id'] = $id;
        $data['type'] = 'main';
        $this->load->view("quest/delete", $data);
    }

    public function main_quest_delete()
    {
        $user = $_SESSION['user'];
        $user_id = $user['id'];
        
        // Check if user has permission to delete quests
        $this->permission->enforce_permission($user_id, 'quest', 'delete');
        
        $id = $_POST['id'];

        // Check if main quest exists
        $quest_check = $this->mymodel->selectWithQuery("SELECT id FROM main_quests WHERE id = '$id'");
        if (empty($quest_check)) {
            $msg = 'Main quest tidak ditemukan!';
            echo $this->template->alert_danger($msg);
            return;
        }

        // Check for existing submissions
        $submissions = $this->mymodel->selectWithQuery("SELECT COUNT(*) as count FROM main_quest_submissions WHERE quest_id = '$id'");
        $submission_count = $submissions[0]['count'];

        // Start transaction for data integrity
        $this->db->trans_start();

        // Delete all related submissions first (if any)
        if ($submission_count > 0) {
            $this->db->delete('main_quest_submissions', array('quest_id' => $id));
        }

        // Then delete the main quest
        $this->db->delete('main_quests', array('id' => $id));

        // Complete transaction
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $msg = 'Hapus data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        } else {
            if ($submission_count > 0) {
                $msg = "Hapus main quest berhasil! ($submission_count submission terkait juga telah dihapus)";
            } else {
                $msg = 'Hapus main quest berhasil!';
            }
            echo $this->template->alert_success($msg);
        }
    }

    public function main_quest_bulk_delete()
    {
        // Set content type to JSON
        header('Content-Type: application/json');

        $user = $_SESSION['user'];
        $user_id = $user['id'];

        // Check if user has permission to delete quests
        try {
            $this->permission->enforce_permission($user_id, 'quest', 'delete');
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki izin untuk menghapus quest.']);
            return;
        }

        // Validate input
        if (empty($_POST['ids']) || !is_array($_POST['ids'])) {
            echo json_encode(['success' => false, 'message' => 'Tidak ada item yang dipilih untuk dihapus.']);
            return;
        }

        $ids = $_POST['ids'];
        $deleted_count = 0;
        $failed_count = 0;
        $failed_items = [];

        // Start transaction for data integrity
        $this->db->trans_start();

        foreach ($ids as $id) {
            // Sanitize ID
            $id = intval($id);
            if ($id <= 0) {
                $failed_count++;
                continue;
            }

            try {
                // Check if main quest exists
                $quest_check = $this->mymodel->selectWithQuery("SELECT id, title FROM main_quests WHERE id = '$id'");
                if (empty($quest_check)) {
                    $failed_count++;
                    $failed_items[] = "Quest ID $id not found";
                    continue;
                }

                $quest_title = $quest_check[0]['title'];

                // Check for existing submissions
                $submissions = $this->mymodel->selectWithQuery("SELECT COUNT(*) as count FROM main_quest_submissions WHERE quest_id = '$id'");
                $submission_count = $submissions[0]['count'];

                // Delete all related submissions first (if any)
                if ($submission_count > 0) {
                    $submission_delete = $this->db->delete('main_quest_submissions', array('quest_id' => $id));
                    if (!$submission_delete) {
                        $failed_count++;
                        $failed_items[] = "Failed to delete submissions for: $quest_title";
                        continue;
                    }
                }

                // Then delete the main quest
                $quest_delete = $this->db->delete('main_quests', array('id' => $id));
                if ($quest_delete && $this->db->affected_rows() > 0) {
                    $deleted_count++;
                } else {
                    $failed_count++;
                    $failed_items[] = "Failed to delete quest: $quest_title";
                }

            } catch (Exception $e) {
                $failed_count++;
                $failed_items[] = "Error deleting quest ID $id: " . $e->getMessage();
            }
        }

        // Complete transaction
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            echo json_encode([
                'success' => false,
                'message' => 'Database transaction failed. All operations have been rolled back.'
            ]);
            return;
        }

        // Return success response
        $response = [
            'success' => true,
            'deleted_count' => $deleted_count,
            'failed_count' => $failed_count,
            'message' => "Bulk delete completed: $deleted_count deleted, $failed_count failed."
        ];

        if (!empty($failed_items)) {
            $response['failed_items'] = $failed_items;
        }

        echo json_encode($response);
    }

    // Main Quest Submission Methods
    public function main_quest_submissions()
    {
        $data['user'] = $_SESSION['user'];
        
        if (!in_array($data['user']['role'], array('1', '2'))) {
            redirect(base_url() . 'dashboard');
        }

        $status_filter = $_GET['status_filter'] ?? "";
        $keyword = $_GET['keyword'] ?? "";
        
        $data['status_filter'] = $status_filter;
        $data['title'] = 'Main Quest Submissions - ' . $this->template->title();

        $qry = "1=1";
        
        if ($keyword) {
            $qry .= " AND (mq.title LIKE '%$keyword%' OR u.full_name LIKE '%$keyword%')";
        }
        
        if ($status_filter) {
            $qry .= " AND mqs.status = '$status_filter'";
        }

        $query = $this->mymodel->selectWithQuery("SELECT COUNT(mqs.id) AS count 
            FROM main_quest_submissions mqs 
            LEFT JOIN main_quests mq ON mqs.quest_id = mq.id 
            LEFT JOIN user_profile up ON mqs.user_profile_id = up.id 
            LEFT JOIN user u ON up.user_id = u.id 
            WHERE $qry");
        $data['page'] = CEIL($query[0]['count'] / 10);
        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query[0]['count']) . ' submission ditemukan!</label></p>';

        $current_page = intval($_GET['page'] ?? 1);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $url = base_url() . '/quest/main_quest_submissions/' . $this->template->get_param();
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $data['content'] = $this->load->view("quest/main_quest_submissions", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function main_quest_submissions_item()
    {
        $data['template'] = $this->template;
        $status_filter = $_GET['status_filter'] ?? "";
        $keyword = $_GET['keyword'] ?? "";
        
        $qry = "1=1";
        
        if ($keyword) {
            $qry .= " AND (mq.title LIKE '%$keyword%' OR u.full_name LIKE '%$keyword%')";
        }
        
        if ($status_filter) {
            $qry .= " AND mqs.status = '$status_filter'";
        }

        $limit = 10;
        $current_page = $_GET['page'] ?? 1;
        
        if ($current_page <= 1) {
            $offset = 0;
        } else {
            $offset = ($current_page - 1) * $limit;
        }

        $query = $this->mymodel->selectWithQuery("SELECT mqs.*, mq.title as quest_title, mq.description as quest_description, u.full_name, u.email,
            p.name as position_name, ql.name as user_level
            FROM main_quest_submissions mqs 
            LEFT JOIN main_quests mq ON mqs.quest_id = mq.id 
            LEFT JOIN user_profile up ON mqs.user_profile_id = up.id 
            LEFT JOIN user u ON up.user_id = u.id 
            LEFT JOIN positions p ON up.position_id = p.id 
            LEFT JOIN quest_levels ql ON p.level_id = ql.id 
            WHERE $qry 
            ORDER BY mqs.submitted_at DESC 
            LIMIT $offset, $limit");
        $data['data'] = $query;
        $data['start'] = $offset;
        
        $this->load->view("quest/main_quest_submissions_item", $data);
    }

    public function side_quest_submissions()
    {
        $data['user'] = $_SESSION['user'];
        
        if (!in_array($data['user']['role'], array('1', '2'))) {
            redirect(base_url() . 'dashboard');
        }

        $status_filter = $_GET['status_filter'] ?? "";
        $keyword = $_GET['keyword'] ?? "";
        
        $data['status_filter'] = $status_filter;
        $data['title'] = 'Side Quest Submissions - ' . $this->template->title();

        $qry = "1=1";
        
        if ($keyword) {
            $qry .= " AND (sq.title LIKE '%$keyword%' OR u.full_name LIKE '%$keyword%')";
        }
        
        if ($status_filter) {
            $qry .= " AND sqs.status = '$status_filter'";
        }

        $query = $this->mymodel->selectWithQuery("SELECT COUNT(sqs.id) AS count 
            FROM side_quest_submissions sqs 
            LEFT JOIN side_quests sq ON sqs.quest_id = sq.id 
            LEFT JOIN user_profile up ON sqs.user_profile_id = up.id 
            LEFT JOIN user u ON up.user_id = u.id 
            WHERE $qry");
        $data['page'] = CEIL($query[0]['count'] / 10);
        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query[0]['count']) . ' submission ditemukan!</label></p>';

        $current_page = intval($_GET['page'] ?? 1);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $url = base_url() . '/quest/side_quest_submissions/' . $this->template->get_param();
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $data['content'] = $this->load->view("quest/side_quest_submissions", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function side_quest_submissions_item()
    {
        $data['template'] = $this->template;
        $status_filter = $_GET['status_filter'] ?? "";
        $keyword = $_GET['keyword'] ?? "";
        
        $qry = "1=1";
        
        if ($keyword) {
            $qry .= " AND (sq.title LIKE '%$keyword%' OR u.full_name LIKE '%$keyword%')";
        }
        
        if ($status_filter) {
            $qry .= " AND sqs.status = '$status_filter'";
        }

        $limit = 10;
        $current_page = $_GET['page'] ?? 1;
        
        if ($current_page <= 1) {
            $offset = 0;
        } else {
            $offset = ($current_page - 1) * $limit;
        }

        $query = $this->mymodel->selectWithQuery("SELECT sqs.*, sq.title as quest_title, sq.description as quest_description, sq.points as quest_points, 
            u.full_name, u.email, p.name as position_name, ql.name as user_level,
            COALESCE(sqs.hr_score, 0) as display_score
            FROM side_quest_submissions sqs 
            LEFT JOIN side_quests sq ON sqs.quest_id = sq.id 
            LEFT JOIN user_profile up ON sqs.user_profile_id = up.id 
            LEFT JOIN user u ON up.user_id = u.id 
            LEFT JOIN positions p ON up.position_id = p.id 
            LEFT JOIN quest_levels ql ON p.level_id = ql.id 
            WHERE $qry 
            ORDER BY sqs.submitted_at DESC 
            LIMIT $offset, $limit");
        $data['data'] = $query;
        $data['start'] = $offset;
        
        $this->load->view("quest/side_quest_submissions_item", $data);
    }

    // Side Quest CRUD Methods
    public function side_quest_create_page()
    {
        $data['user'] = $_SESSION['user'];
        
        if (!in_array($data['user']['role'], array('1', '2'))) {
            redirect(base_url() . 'dashboard');
        }

        $data['data'] = array();
        $data['title'] = 'Tambah Side Quest - ' . $this->template->title();
        $data['content'] = $this->load->view("quest/side_quest_create", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function side_quest_store()
    {
        $user = $_SESSION['user'];
        $dt = $_POST['dt'];
        
        // Validate required fields
        if (empty($dt['points']) || $dt['points'] <= 0) {
            $msg = 'Points harus diisi dan lebih besar dari 0!';
            echo $this->template->alert_danger($msg);
            return;
        }
        
        if (empty($dt['reward'])) {
            $msg = 'Reward description harus diisi!';
            echo $this->template->alert_danger($msg);
            return;
        }
        
        // Handle file upload
        if (!empty($_FILES['gambar_animasi']['name'])) {
            $upload_path = 'assets/uploads/side_quest_animations/';
            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0755, true);
            }
            
            $config['upload_path'] = $upload_path;
            $config['allowed_types'] = 'gif|jpg|png|jpeg|webp';
            $config['max_size'] = 2048; // 2MB
            $config['file_name'] = 'side_quest_' . time() . '_' . uniqid();
            
            $this->load->library('upload', $config);
            
            if ($this->upload->do_upload('gambar_animasi')) {
                $upload_data = $this->upload->data();
                $dt['gambar_animasi'] = $upload_data['file_name'];
            } else {
                $msg = 'Upload gambar gagal: ' . $this->upload->display_errors();
                echo $this->template->alert_danger($msg);
                return;
            }
        }
        
        $dt['created_by'] = $user['id'];
        $dt['created_at'] = date('Y-m-d H:i:s');

        if ($this->db->insert('side_quests', $dt)) {
            $msg = 'Tambah data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Tambah data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }

    public function side_quest_edit_page()
    {
        $data['user'] = $_SESSION['user'];
        
        if (!in_array($data['user']['role'], array('1', '2'))) {
            redirect(base_url() . 'dashboard');
        }

        $id = $_GET['id'];
        $query = $this->mymodel->selectWithQuery("SELECT * FROM side_quests WHERE id = '$id'");
        
        if (empty($query)) {
            redirect(base_url() . 'quest');
        }

        $data['data'] = $query[0];
        $data['title'] = 'Edit Side Quest - ' . $this->template->title();
        $data['content'] = $this->load->view("quest/side_quest_edit", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function side_quest_update()
    {
        $user = $_SESSION['user'];
        $id = $_POST['id'];
        $dt = $_POST['dt'];
        
        // Validate required fields
        if (empty($dt['points']) || $dt['points'] <= 0) {
            $msg = 'Points harus diisi dan lebih besar dari 0!';
            echo $this->template->alert_danger($msg);
            return;
        }
        
        if (empty($dt['reward'])) {
            $msg = 'Reward description harus diisi!';
            echo $this->template->alert_danger($msg);
            return;
        }
        
        // Get current data for file handling
        $current_data = $this->mymodel->selectWithQuery("SELECT gambar_animasi FROM side_quests WHERE id = '$id'");
        if (empty($current_data)) {
            $msg = 'Data tidak ditemukan!';
            echo $this->template->alert_danger($msg);
            return;
        }
        
        // Handle file upload
        if (!empty($_FILES['gambar_animasi']['name'])) {
            $upload_path = 'assets/uploads/side_quest_animations/';
            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0755, true);
            }
            
            $config['upload_path'] = $upload_path;
            $config['allowed_types'] = 'gif|jpg|png|jpeg|webp';
            $config['max_size'] = 2048; // 2MB
            $config['file_name'] = 'side_quest_' . time() . '_' . uniqid();
            
            $this->load->library('upload', $config);
            
            if ($this->upload->do_upload('gambar_animasi')) {
                $upload_data = $this->upload->data();
                $dt['gambar_animasi'] = $upload_data['file_name'];
                
                // Delete old image if exists
                if (!empty($current_data[0]['gambar_animasi']) && file_exists($upload_path . $current_data[0]['gambar_animasi'])) {
                    unlink($upload_path . $current_data[0]['gambar_animasi']);
                }
            } else {
                $msg = 'Upload gambar gagal: ' . $this->upload->display_errors();
                echo $this->template->alert_danger($msg);
                return;
            }
        }

        if ($this->db->update('side_quests', $dt, array('id' => $id))) {
            $msg = 'Update data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Update data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }

    public function side_quest_detail()
    {
        $data['user'] = $_SESSION['user'];
        
        if (!in_array($data['user']['role'], array('1', '2'))) {
            redirect(base_url() . 'dashboard');
        }

        $id = $_GET['id'];
        $query = $this->mymodel->selectWithQuery("SELECT sq.*, u.full_name as creator_name 
            FROM side_quests sq 
            LEFT JOIN user u ON sq.created_by = u.id 
            WHERE sq.id = '$id'");
        
        if (empty($query)) {
            redirect(base_url() . 'quest');
        }

        $data['data'] = $query[0];
        $data['title'] = 'Detail Side Quest - ' . $this->template->title();
        $data['content'] = $this->load->view("quest/side_quest_detail", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function side_quest_remove()
    {
        $id = $_GET['id'];
        $data['data']['id'] = $id;
        $data['type'] = 'side';
        $this->load->view("quest/delete", $data);
    }

    public function side_quest_delete()
    {
        $user = $_SESSION['user'];
        $id = $_POST['id'];

        // Check if side quest exists
        $quest_check = $this->mymodel->selectWithQuery("SELECT id FROM side_quests WHERE id = '$id'");
        if (empty($quest_check)) {
            $msg = 'Side quest tidak ditemukan!';
            echo $this->template->alert_danger($msg);
            return;
        }

        // Get current data to delete associated files
        $current_data = $this->mymodel->selectWithQuery("SELECT gambar_animasi FROM side_quests WHERE id = '$id'");
        
        // Check for existing submissions
        $submissions = $this->mymodel->selectWithQuery("SELECT COUNT(*) as count FROM side_quest_submissions WHERE quest_id = '$id'");
        $submission_count = $submissions[0]['count'];

        // Start transaction for data integrity
        $this->db->trans_start();

        // Delete all related submissions first (if any)
        if ($submission_count > 0) {
            $this->db->delete('side_quest_submissions', array('quest_id' => $id));
        }

        // Then delete the side quest
        $this->db->delete('side_quests', array('id' => $id));

        // Complete transaction
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $msg = 'Hapus data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        } else {
            // Delete associated image file if exists
            if (!empty($current_data) && !empty($current_data[0]['gambar_animasi'])) {
                $upload_path = 'assets/uploads/side_quest_animations/';
                if (file_exists($upload_path . $current_data[0]['gambar_animasi'])) {
                    unlink($upload_path . $current_data[0]['gambar_animasi']);
                }
            }
            
            if ($submission_count > 0) {
                $msg = "Hapus side quest berhasil! ($submission_count submission terkait juga dihapus)";
            } else {
                $msg = 'Hapus side quest berhasil!';
            }
            echo $this->template->alert_success($msg);
        }
    }

    // Main Quest Submission Approval Methods
    public function approve_main_quest_submission()
    {
        $user = $_SESSION['user'];
        
        if (!in_array($user['role'], array('1', '2'))) {
            echo $this->template->alert_danger('Unauthorized access!');
            return;
        }

        $id = $_POST['id'];
        $hr_notes = $_POST['hr_notes'] ?? '';
        
        // Get submission details to get the quest's default benefit
        $submission = $this->mymodel->selectWithQuery("SELECT mqs.*, mq.default_benefit 
            FROM main_quest_submissions mqs 
            LEFT JOIN main_quests mq ON mqs.quest_id = mq.id 
            WHERE mqs.id = '$id'");
        
        if (empty($submission)) {
            echo $this->template->alert_danger('Submission tidak ditemukan!');
            return;
        }
        
        $dt = array(
            'status' => 'approved',
            'approved_by' => $user['id'],
            'approved_at' => date('Y-m-d H:i:s'),
            'hr_notes' => $hr_notes,
            'benefit_type' => $submission[0]['default_benefit'] // Automatically assign the quest's default benefit
        );

        if ($this->db->update('main_quest_submissions', $dt, array('id' => $id))) {
            $msg = 'Main quest submission berhasil disetujui dengan benefit otomatis!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Gagal menyetujui submission!';
            echo $this->template->alert_danger($msg);
        }
    }

    public function deny_main_quest_submission()
    {
        $user = $_SESSION['user'];
        
        if (!in_array($user['role'], array('1', '2'))) {
            echo $this->template->alert_danger('Unauthorized access!');
            return;
        }

        $id = $_POST['id'];
        $hr_notes = $_POST['hr_notes'] ?? '';
        
        $dt = array(
            'status' => 'denied',
            'hr_notes' => $hr_notes,
            'approved_by' => $user['id'],
            'approved_at' => date('Y-m-d H:i:s')
        );

        if ($this->db->update('main_quest_submissions', $dt, array('id' => $id))) {
            $msg = 'Main quest submission berhasil ditolak!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Gagal menolak submission!';
            echo $this->template->alert_danger($msg);
        }
    }

    /**
     * Helper method for detailed debug logging
     */
    private function log_quest_debug($file, $message) {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] {$message}\n";
        file_put_contents($file, $log_entry, FILE_APPEND | LOCK_EX);
    }

    // Side Quest Submission Approval Methods
    public function approve_side_quest_submission()
    {
        // Enhanced error logging and detailed error handling for side quest approval
        $log_file = APPPATH . 'logs/side_quest_approval_debug_' . date('Y-m-d') . '.log';

        try {
            // Log start of approval process
            $this->log_quest_debug($log_file, "=== approve_side_quest_submission START ===");
            $this->log_quest_debug($log_file, "Memory usage: " . memory_get_usage(true) . " bytes");
            $this->log_quest_debug($log_file, "POST data: " . json_encode($_POST));

            // Check session validity
            if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
                $error_msg = 'Sesi Anda telah berakhir. Silakan login kembali.';
                $this->log_quest_debug($log_file, "ERROR: Session expired or invalid");
                echo $this->template->alert_danger($error_msg);
                return;
            }

            $user = $_SESSION['user'];
            $this->log_quest_debug($log_file, "User ID: " . $user['id'] . " (" . $user['username'] . ")");

            // Validate required POST data
            if (empty($_POST['id'])) {
                $error_msg = 'ID submission tidak valid. Silakan refresh halaman dan coba lagi.';
                $this->log_quest_debug($log_file, "ERROR: Missing submission ID in request");
                echo $this->template->alert_danger($error_msg);
                return;
            }

            $id = $_POST['id'];
            $hr_notes = $_POST['hr_notes'] ?? '';
            $video_review = isset($_POST['video_review']) && $_POST['video_review'] == '1';

            // Automatic scoring: 1 point for standard approval, 100 for video review
            $hr_score = $video_review ? 100 : 1;
            $review_type = $video_review ? "review video" : "approval standar";

            $this->log_quest_debug($log_file, "CHECKPOINT 1: Basic validation passed - ID: $id, Score: $hr_score, Type: $review_type");

            // Check database connection
            if (!$this->db->conn_id) {
                $error_msg = 'Koneksi database bermasalah. Silakan coba lagi dalam beberapa saat.';
                $this->log_quest_debug($log_file, "ERROR: Database connection failed");
                echo $this->template->alert_danger($error_msg);
                return;
            }

            $this->log_quest_debug($log_file, "CHECKPOINT 2: Database connection verified");

            // Get submission details with comprehensive error handling
            try {
                $submission = $this->mymodel->selectWithQuery("SELECT sqs.*
                    FROM side_quest_submissions sqs
                    WHERE sqs.id = '$id'");
                $this->log_quest_debug($log_file, "Submission query executed, result count: " . count($submission));
            } catch (Exception $e) {
                $error_msg = 'Database error during submission query: ' . $e->getMessage();
                $this->log_quest_debug($log_file, "ERROR: " . $error_msg);
                echo $this->template->alert_danger($error_msg);
                return;
            }

            if (empty($submission)) {
                $error_msg = 'Submission tidak ditemukan. Data mungkin sudah dihapus atau diubah.';
                $this->log_quest_debug($log_file, "ERROR: Submission not found with ID: " . $id);
                echo $this->template->alert_danger($error_msg);
                return;
            }

            $submission_data = $submission[0];
            $user_profile_id = $submission_data['user_profile_id'];
            $quest_id = $submission_data['quest_id'];
            $current_status = $submission_data['status'];

            $this->log_quest_debug($log_file, "CHECKPOINT 3: Submission found - User Profile ID: $user_profile_id, Quest ID: $quest_id, Current Status: $current_status");

            // Check if submission is already processed
            if ($current_status !== 'pending') {
                $status_text = ($current_status === 'approved') ? 'sudah disetujui' : 'sudah ditolak';
                $error_msg = "Submission ini $status_text sebelumnya dan tidak dapat diproses ulang. Silakan refresh halaman untuk melihat status terbaru.";
                $this->log_quest_debug($log_file, "ERROR: Submission already processed with status: $current_status");
                echo $this->template->alert_danger($error_msg);
                return;
            }

            // Prepare update data
            $dt = array(
                'status' => 'approved',
                'approved_by' => $user['id'],
                'approved_at' => date('Y-m-d H:i:s'),
                'hr_notes' => $hr_notes,
                'hr_score' => $hr_score
            );

            $this->log_quest_debug($log_file, "CHECKPOINT 4: Update data prepared - " . json_encode($dt));
            $this->log_quest_debug($log_file, "Memory usage before update: " . memory_get_usage(true) . " bytes");

            // Attempt database update with comprehensive error handling
            try {
                $this->log_quest_debug($log_file, "CHECKPOINT 5: Starting database update");

                // Check database connection before update
                if (!$this->db->conn_id) {
                    throw new Exception("Database connection lost before update");
                }

                $update_result = $this->db->update('side_quest_submissions', $dt, array('id' => $id));

                if ($update_result) {
                    $affected_rows = $this->db->affected_rows();
                    $this->log_quest_debug($log_file, "CHECKPOINT 6: Database update successful - Affected rows: " . $affected_rows);

                    if ($affected_rows === 0) {
                        $error_msg = "Submission telah diubah oleh pengguna lain. Silakan refresh halaman dan coba lagi.";
                        $this->log_quest_debug($log_file, "ERROR: No rows updated - concurrent modification detected");
                        echo $this->template->alert_danger($error_msg);
                        return;
                    }

                    // Update user side quest statistics for leaderboard
                    $this->log_quest_debug($log_file, "CHECKPOINT 7: Updating user statistics for profile ID: " . $user_profile_id);

                    $stats_update_success = false;
                    try {
                        $stats_update_success = $this->updateUserSideQuestStats($user_profile_id);
                        if ($stats_update_success) {
                            $this->log_quest_debug($log_file, "CHECKPOINT 8: User statistics updated successfully");
                        } else {
                            $this->log_quest_debug($log_file, "WARNING: Statistics update returned false - check detailed logs");
                        }
                    } catch (Exception $e) {
                        // Log statistics update error but don't fail the approval
                        $this->log_quest_debug($log_file, "WARNING: Statistics update exception - " . $e->getMessage());
                        $stats_update_success = false;
                    }

                    // Prepare success message with optional warning about statistics
                    $msg = "Side quest submission berhasil disetujui dengan $review_type ($hr_score poin)!";
                    if (!$stats_update_success) {
                        $msg .= " (Catatan: Statistik leaderboard akan diperbarui secara otomatis dalam beberapa saat)";
                    }

                    $this->log_quest_debug($log_file, "=== approve_side_quest_submission SUCCESS ===");
                    echo $this->template->alert_success($msg);

                } else {
                    // Get detailed database error
                    $db_error = $this->db->error();
                    $last_query = $this->db->last_query();

                    $error_msg = "Database update failed. MySQL Error " . $db_error['code'] . ": " . $db_error['message'];
                    $this->log_quest_debug($log_file, "ERROR: " . $error_msg);
                    $this->log_quest_debug($log_file, "Last Query: " . $last_query);

                    echo $this->template->alert_danger($error_msg);
                }

            } catch (Exception $e) {
                $error_msg = "Exception during database update: " . $e->getMessage();
                $this->log_quest_debug($log_file, "ERROR: " . $error_msg);
                echo $this->template->alert_danger($error_msg);
            }

        } catch (Exception $e) {
            // Catch any unexpected exceptions
            $error_msg = "Unexpected error in approve_side_quest_submission: " . $e->getMessage();
            $this->log_quest_debug($log_file, "FATAL ERROR: " . $error_msg);
            $this->log_quest_debug($log_file, "Stack trace: " . $e->getTraceAsString());
            echo $this->template->alert_danger($error_msg);
        } finally {
            $this->log_quest_debug($log_file, "Memory usage at end: " . memory_get_usage(true) . " bytes");
            $this->log_quest_debug($log_file, "=== approve_side_quest_submission END ===\n");
        }
    }

    public function deny_side_quest_submission()
    {
        // Enhanced error logging and detailed error handling for side quest denial
        $log_file = APPPATH . 'logs/side_quest_approval_debug_' . date('Y-m-d') . '.log';

        try {
            // Log start of denial process
            $this->log_quest_debug($log_file, "=== deny_side_quest_submission START ===");
            $this->log_quest_debug($log_file, "Memory usage: " . memory_get_usage(true) . " bytes");
            $this->log_quest_debug($log_file, "POST data: " . json_encode($_POST));

            // Check session validity
            if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
                $error_msg = 'Sesi Anda telah berakhir. Silakan login kembali.';
                $this->log_quest_debug($log_file, "ERROR: Session expired or invalid");
                echo $this->template->alert_danger($error_msg);
                return;
            }

            $user = $_SESSION['user'];
            $this->log_quest_debug($log_file, "User ID: " . $user['id'] . " (" . $user['username'] . ")");

            // Validate required POST data
            if (empty($_POST['id'])) {
                $error_msg = 'ID submission tidak valid. Silakan refresh halaman dan coba lagi.';
                $this->log_quest_debug($log_file, "ERROR: Missing submission ID in request");
                echo $this->template->alert_danger($error_msg);
                return;
            }

            $id = $_POST['id'];
            $hr_notes = $_POST['hr_notes'] ?? '';

            $this->log_quest_debug($log_file, "CHECKPOINT 1: Basic validation passed - ID: $id, HR Notes: " . strlen($hr_notes) . " characters");

            // Check database connection
            if (!$this->db->conn_id) {
                $error_msg = 'Database connection failed before denial process.';
                $this->log_quest_debug($log_file, "ERROR: " . $error_msg);
                echo $this->template->alert_danger($error_msg);
                return;
            }

            $this->log_quest_debug($log_file, "CHECKPOINT 2: Database connection verified");

            // Get submission details to verify it exists and check current status
            try {
                $submission = $this->mymodel->selectWithQuery("SELECT sqs.*
                    FROM side_quest_submissions sqs
                    WHERE sqs.id = '$id'");
                $this->log_quest_debug($log_file, "Submission query executed, result count: " . count($submission));
            } catch (Exception $e) {
                $error_msg = 'Database error during submission query: ' . $e->getMessage();
                $this->log_quest_debug($log_file, "ERROR: " . $error_msg);
                echo $this->template->alert_danger($error_msg);
                return;
            }

            if (empty($submission)) {
                $error_msg = 'Submission tidak ditemukan. Data mungkin sudah dihapus atau diubah.';
                $this->log_quest_debug($log_file, "ERROR: Submission not found with ID: " . $id);
                echo $this->template->alert_danger($error_msg);
                return;
            }

            $submission_data = $submission[0];
            $user_profile_id = $submission_data['user_profile_id'];
            $quest_id = $submission_data['quest_id'];
            $current_status = $submission_data['status'];

            $this->log_quest_debug($log_file, "CHECKPOINT 3: Submission found - User Profile ID: $user_profile_id, Quest ID: $quest_id, Current Status: $current_status");

            // Check if submission is already processed
            if ($current_status !== 'pending') {
                $status_text = ($current_status === 'approved') ? 'sudah disetujui' : 'sudah ditolak';
                $error_msg = "Submission ini $status_text sebelumnya dan tidak dapat diproses ulang. Silakan refresh halaman untuk melihat status terbaru.";
                $this->log_quest_debug($log_file, "ERROR: Submission already processed with status: $current_status");
                echo $this->template->alert_danger($error_msg);
                return;
            }

            // Prepare update data
            $dt = array(
                'status' => 'denied',
                'hr_notes' => $hr_notes,
                'approved_by' => $user['id'],
                'approved_at' => date('Y-m-d H:i:s')
            );

            $this->log_quest_debug($log_file, "CHECKPOINT 4: Update data prepared - " . json_encode($dt));
            $this->log_quest_debug($log_file, "Memory usage before update: " . memory_get_usage(true) . " bytes");

            // Attempt database update with comprehensive error handling
            try {
                $this->log_quest_debug($log_file, "CHECKPOINT 5: Starting database update");

                // Check database connection before update
                if (!$this->db->conn_id) {
                    throw new Exception("Database connection lost before update");
                }

                $update_result = $this->db->update('side_quest_submissions', $dt, array('id' => $id));

                if ($update_result) {
                    $affected_rows = $this->db->affected_rows();
                    $this->log_quest_debug($log_file, "CHECKPOINT 6: Database update successful - Affected rows: " . $affected_rows);

                    if ($affected_rows === 0) {
                        $error_msg = "Submission telah diubah oleh pengguna lain. Silakan refresh halaman dan coba lagi.";
                        $this->log_quest_debug($log_file, "ERROR: No rows updated - concurrent modification detected");
                        echo $this->template->alert_danger($error_msg);
                        return;
                    }

                    $msg = 'Side quest submission berhasil ditolak!';
                    $this->log_quest_debug($log_file, "=== deny_side_quest_submission SUCCESS ===");
                    echo $this->template->alert_success($msg);

                } else {
                    // Get detailed database error
                    $db_error = $this->db->error();
                    $last_query = $this->db->last_query();

                    $error_msg = "Database update failed. MySQL Error " . $db_error['code'] . ": " . $db_error['message'];
                    $this->log_quest_debug($log_file, "ERROR: " . $error_msg);
                    $this->log_quest_debug($log_file, "Last Query: " . $last_query);

                    echo $this->template->alert_danger($error_msg);
                }

            } catch (Exception $e) {
                $error_msg = "Exception during database update: " . $e->getMessage();
                $this->log_quest_debug($log_file, "ERROR: " . $error_msg);
                echo $this->template->alert_danger($error_msg);
            }

        } catch (Exception $e) {
            // Catch any unexpected exceptions
            $error_msg = "Unexpected error in deny_side_quest_submission: " . $e->getMessage();
            $this->log_quest_debug($log_file, "FATAL ERROR: " . $error_msg);
            $this->log_quest_debug($log_file, "Stack trace: " . $e->getTraceAsString());
            echo $this->template->alert_danger($error_msg);
        } finally {
            $this->log_quest_debug($log_file, "Memory usage at end: " . memory_get_usage(true) . " bytes");
            $this->log_quest_debug($log_file, "=== deny_side_quest_submission END ===\n");
        }
    }
    
    // Helper method to update user side quest statistics for leaderboard
    private function updateUserSideQuestStats($user_profile_id)
    {
        $log_file = APPPATH . 'logs/side_quest_approval_debug_' . date('Y-m-d') . '.log';

        try {
            $this->log_quest_debug($log_file, "=== updateUserSideQuestStats START for profile_id: $user_profile_id ===");

            // Check if user_side_quest_stats table exists
            $table_check = $this->mymodel->selectWithQuery("SHOW TABLES LIKE 'user_side_quest_stats'");
            if (empty($table_check)) {
                $this->log_quest_debug($log_file, "ERROR: user_side_quest_stats table does not exist");

                // Try to create the table using basic structure
                $create_table_sql = "
                    CREATE TABLE IF NOT EXISTS user_side_quest_stats (
                        id int(11) NOT NULL AUTO_INCREMENT,
                        user_profile_id int(11) NOT NULL,
                        total_completed_quests int(11) DEFAULT 0,
                        total_points_earned int(11) DEFAULT 0,
                        monthly_completed_quests int(11) DEFAULT 0,
                        monthly_points_earned int(11) DEFAULT 0,
                        milestone_bonus_points int(11) DEFAULT 0,
                        current_month varchar(7) NOT NULL,
                        last_updated timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (id),
                        UNIQUE KEY user_profile_id (user_profile_id),
                        KEY idx_current_month (current_month)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                ";

                $create_result = $this->db->query($create_table_sql);
                if ($create_result) {
                    $this->log_quest_debug($log_file, "SUCCESS: user_side_quest_stats table created");
                } else {
                    $db_error = $this->db->error();
                    $this->log_quest_debug($log_file, "ERROR: Failed to create user_side_quest_stats table - " . $db_error['message']);
                    throw new Exception("Failed to create user_side_quest_stats table: " . $db_error['message']);
                }
            } else {
                $this->log_quest_debug($log_file, "CHECKPOINT: user_side_quest_stats table exists");
            }

            // Get current month
            $current_month = date('Y-m');
            $this->log_quest_debug($log_file, "Current month: $current_month");

            // Calculate total completed side quests and points using new hr_score system
            try {
                $stats = $this->mymodel->selectWithQuery("
                    SELECT
                        COUNT(*) as total_completed,
                        SUM(COALESCE(hr_score, 0)) as total_points,
                        SUM(CASE WHEN DATE_FORMAT(approved_at, '%Y-%m') = '$current_month' THEN 1 ELSE 0 END) as monthly_completed,
                        SUM(CASE WHEN DATE_FORMAT(approved_at, '%Y-%m') = '$current_month' THEN COALESCE(hr_score, 0) ELSE 0 END) as monthly_points
                    FROM side_quest_submissions
                    WHERE user_profile_id = '$user_profile_id' AND status = 'approved'
                ");
                $this->log_quest_debug($log_file, "Statistics query executed successfully");
            } catch (Exception $e) {
                $this->log_quest_debug($log_file, "ERROR: Statistics query failed - " . $e->getMessage());
                throw new Exception("Statistics calculation failed: " . $e->getMessage());
            }

            if (empty($stats)) {
                $this->log_quest_debug($log_file, "ERROR: No statistics data returned");
                throw new Exception("No statistics data returned from query");
            }

            $stat_data = $stats[0];
            $this->log_quest_debug($log_file, "Statistics calculated - " . json_encode($stat_data));

            // Check for existing user stats record
            try {
                $existing_stats = $this->mymodel->selectWithQuery("SELECT id FROM user_side_quest_stats WHERE user_profile_id = '$user_profile_id'");
                $this->log_quest_debug($log_file, "Existing stats check - found " . count($existing_stats) . " records");
            } catch (Exception $e) {
                $this->log_quest_debug($log_file, "ERROR: Failed to check existing stats - " . $e->getMessage());
                throw new Exception("Failed to check existing stats: " . $e->getMessage());
            }

            $update_data = array(
                'total_completed_quests' => intval($stat_data['total_completed']),
                'total_points_earned' => intval($stat_data['total_points']),
                'monthly_completed_quests' => intval($stat_data['monthly_completed']),
                'monthly_points_earned' => intval($stat_data['monthly_points']),
                'current_month' => $current_month,
                'last_updated' => date('Y-m-d H:i:s')
            );

            $this->log_quest_debug($log_file, "Update data prepared - " . json_encode($update_data));

            // Perform database update/insert with error handling
            try {
                if (!empty($existing_stats)) {
                    // Update existing record
                    $update_result = $this->db->update('user_side_quest_stats', $update_data, array('user_profile_id' => $user_profile_id));
                    if (!$update_result) {
                        $db_error = $this->db->error();
                        throw new Exception("Stats update failed - MySQL Error " . $db_error['code'] . ": " . $db_error['message']);
                    }
                    $affected_rows = $this->db->affected_rows();
                    $this->log_quest_debug($log_file, "Stats updated successfully - affected rows: $affected_rows");
                } else {
                    // Insert new record
                    $update_data['user_profile_id'] = $user_profile_id;
                    $insert_result = $this->db->insert('user_side_quest_stats', $update_data);
                    if (!$insert_result) {
                        $db_error = $this->db->error();
                        throw new Exception("Stats insert failed - MySQL Error " . $db_error['code'] . ": " . $db_error['message']);
                    }
                    $insert_id = $this->db->insert_id();
                    $this->log_quest_debug($log_file, "Stats inserted successfully - insert ID: $insert_id");
                }
            } catch (Exception $e) {
                $this->log_quest_debug($log_file, "ERROR: Database operation failed - " . $e->getMessage());
                throw $e;
            }

            // Update user profile total score with error handling
            try {
                $total_score = intval($stat_data['total_points']);
                $this->log_quest_debug($log_file, "Attempting to update profile score to: $total_score for profile_id: $user_profile_id");

                $profile_update_result = $this->db->query("
                    UPDATE user_profile
                    SET score = $total_score
                    WHERE id = '$user_profile_id'
                ");

                if ($profile_update_result) {
                    $affected_rows = $this->db->affected_rows();
                    $this->log_quest_debug($log_file, "Profile score updated successfully - total_score: $total_score, affected_rows: $affected_rows");
                } else {
                    $db_error = $this->db->error();
                    $this->log_quest_debug($log_file, "WARNING: Profile score update failed - MySQL Error " . $db_error['code'] . ": " . $db_error['message']);
                    // Don't throw exception for profile update failure - stats update is more important
                }
            } catch (Exception $e) {
                $this->log_quest_debug($log_file, "WARNING: Profile score update exception - " . $e->getMessage());
                // Don't throw exception for profile update failure
            }

            $this->log_quest_debug($log_file, "=== updateUserSideQuestStats SUCCESS ===");
            return true;

        } catch (Exception $e) {
            // Log detailed error information for debugging
            $error_msg = "Failed to update user side quest stats for user_profile_id: $user_profile_id - Error: " . $e->getMessage() . " - Line: " . $e->getLine();
            $this->log_quest_debug($log_file, "FATAL ERROR: " . $error_msg);
            $this->log_quest_debug($log_file, "Stack trace: " . $e->getTraceAsString());
            log_message('error', $error_msg);
            return false;
        }
    }
    
    // =====================================================
    // CAREER PROGRESSION INTEGRATION METHODS
    // =====================================================

    /**
     * Check if main quest approval should trigger career progression
     */
    public function check_career_progression($submission_id)
    {
        $submission = $this->mymodel->selectWithQuery("
            SELECT 
                mqs.*,
                mq.is_promotion_quest,
                mq.target_position_id,
                up.user_id,
                up.position_id as current_position_id,
                p.name as current_position_name
            FROM main_quest_submissions mqs
            JOIN main_quests mq ON mqs.quest_id = mq.id
            JOIN user_profile up ON mqs.user_profile_id = up.id
            LEFT JOIN positions p ON up.position_id = p.id
            WHERE mqs.id = '$submission_id'
        ");
        
        if (empty($submission) || $submission[0]['status'] !== 'approved') {
            return false;
        }
        
        $sub = $submission[0];
        
        // Check if this is a promotion quest
        if ($sub['is_promotion_quest'] != 1 || !$sub['target_position_id']) {
            return false;
        }
        
        // Check if career progression path exists
        $progression = $this->mymodel->selectWithQuery("
            SELECT * FROM career_progressions 
            WHERE source_position_id = '{$sub['current_position_id']}' 
            AND target_position_id = '{$sub['target_position_id']}'
            AND is_active = 1
        ");
        
        if (empty($progression)) {
            return false;
        }
        
        return [
            'submission' => $sub,
            'progression' => $progression[0]
        ];
    }

    /**
     * Process automatic career progression after quest approval
     */
    public function process_career_progression($submission_id, $approved_by)
    {
        $progression_data = $this->check_career_progression($submission_id);
        
        if (!$progression_data) {
            return false;
        }
        
        $sub = $progression_data['submission'];
        $progression = $progression_data['progression'];
        
        // Begin transaction for data integrity
        $this->db->trans_start();
        
        try {
            // Update user's position
            $this->db->update('user_profile', 
                ['position_id' => $progression['target_position_id']], 
                ['id' => $sub['user_profile_id']]
            );
            
            // Record career progression history
            $history_data = [
                'user_id' => $sub['user_id'],
                'from_position_id' => $sub['current_position_id'],
                'to_position_id' => $progression['target_position_id'],
                'progression_id' => $progression['id'],
                'promotion_date' => date('Y-m-d'),
                'approved_by' => $approved_by,
                'quest_submission_id' => $submission_id,
                'notes' => 'Automatic promotion via quest completion'
            ];
            
            $this->db->insert('career_progression_history', $history_data);
            
            // Complete transaction
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                log_message('error', 'Failed to process career progression for submission ' . $submission_id);
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'Career progression error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get available promotion quests for a user's position
     */
    public function get_promotion_quests($position_id)
    {
        return $this->mymodel->selectWithQuery("
            SELECT 
                mq.*,
                tp.name as target_position_name,
                tp.department as target_department,
                cp.progression_type,
                cp.estimated_years,
                cp.requirements
            FROM main_quests mq
            JOIN career_progressions cp ON mq.target_position_id = cp.target_position_id
            JOIN positions tp ON cp.target_position_id = tp.id
            WHERE cp.source_position_id = '$position_id'
            AND mq.is_promotion_quest = 1
            AND cp.is_active = 1
            ORDER BY cp.progression_type, tp.name
        ");
    }

    /**
     * Enhanced main quest approval with career progression
     */
    public function approve_main_quest_submission_with_promotion()
    {
        $user = $_SESSION['user'];
        $submission_id = $_POST['submission_id'] ?? null;
        $notes = $_POST['notes'] ?? '';
        
        if (!$submission_id) {
            echo json_encode(['success' => false, 'message' => 'Submission ID required']);
            return;
        }
        
        // Check permissions
        $this->permission->enforce_permission($user['id'], 'quest', 'approve');
        
        // Begin transaction
        $this->db->trans_start();
        
        try {
            // Approve the quest submission
            $update_data = [
                'status' => 'approved',
                'approved_by' => $user['id'],
                'approved_at' => date('Y-m-d H:i:s'),
                'hr_notes' => $notes
            ];
            
            $this->db->update('main_quest_submissions', $update_data, ['id' => $submission_id]);
            
            // Process career progression if applicable
            $promotion_processed = $this->process_career_progression($submission_id, $user['id']);
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                echo json_encode(['success' => false, 'message' => 'Failed to approve submission']);
                return;
            }
            
            $message = 'Quest submission approved successfully';
            if ($promotion_processed) {
                $message .= ' and career progression processed';
            }
            
            echo json_encode([
                'success' => true, 
                'message' => $message,
                'promotion_processed' => $promotion_processed
            ]);
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            echo json_encode(['success' => false, 'message' => 'Error processing approval: ' . $e->getMessage()]);
        }
    }

    /**
     * Get career progression analytics for reporting
     */
    public function career_progression_analytics()
    {
        $user_id = $_SESSION['user']['id'];
        $this->permission->enforce_permission($user_id, 'quest', 'view');
        
        $analytics = [];
        
        // Recent promotions
        $analytics['recent_promotions'] = $this->mymodel->selectWithQuery("
            SELECT 
                cph.*,
                u.full_name as employee_name,
                fp.name as from_position,
                tp.name as to_position,
                mq.title as quest_title
            FROM career_progression_history cph
            JOIN user u ON cph.user_id = u.id
            JOIN positions fp ON cph.from_position_id = fp.id
            JOIN positions tp ON cph.to_position_id = tp.id
            LEFT JOIN main_quest_submissions mqs ON cph.quest_submission_id = mqs.id
            LEFT JOIN main_quests mq ON mqs.quest_id = mq.id
            WHERE cph.promotion_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            ORDER BY cph.promotion_date DESC
            LIMIT 20
        ");
        
        // Promotion quests completion rate
        $analytics['promotion_quest_stats'] = $this->mymodel->selectWithQuery("
            SELECT 
                mq.title,
                tp.name as target_position,
                COUNT(mqs.id) as total_submissions,
                SUM(CASE WHEN mqs.status = 'approved' THEN 1 ELSE 0 END) as approved_submissions,
                SUM(CASE WHEN cph.id IS NOT NULL THEN 1 ELSE 0 END) as actual_promotions
            FROM main_quests mq
            LEFT JOIN main_quest_submissions mqs ON mq.id = mqs.quest_id
            LEFT JOIN positions tp ON mq.target_position_id = tp.id
            LEFT JOIN career_progression_history cph ON mqs.id = cph.quest_submission_id
            WHERE mq.is_promotion_quest = 1
            GROUP BY mq.id, mq.title, tp.name
            ORDER BY total_submissions DESC
        ");
        
        echo json_encode($analytics);
    }

    /**
     * Generate career progression recommendations
     */
    public function career_progression_recommendations($user_profile_id = null)
    {
        if (!$user_profile_id && isset($_GET['user_profile_id'])) {
            $user_profile_id = $_GET['user_profile_id'];
        }
        
        if (!$user_profile_id) {
            echo json_encode(['error' => 'User profile ID required']);
            return;
        }
        
        // Get user's current position and profile
        $user_profile = $this->mymodel->selectWithQuery("
            SELECT 
                up.*,
                u.full_name,
                p.name as current_position,
                p.department,
                ql.name as current_level
            FROM user_profile up
            JOIN user u ON up.user_id = u.id
            JOIN positions p ON up.position_id = p.id
            LEFT JOIN quest_levels ql ON p.level_id = ql.id
            WHERE up.id = '$user_profile_id'
        ");
        
        if (empty($user_profile)) {
            echo json_encode(['error' => 'User profile not found']);
            return;
        }
        
        $profile = $user_profile[0];
        
        // Get available career progressions
        $available_paths = $this->mymodel->selectWithQuery("
            SELECT 
                cp.*,
                tp.name as target_position,
                tp.department as target_department,
                tql.name as target_level,
                mq.title as promotion_quest_title,
                mq.id as promotion_quest_id
            FROM career_progressions cp
            JOIN positions tp ON cp.target_position_id = tp.id
            LEFT JOIN quest_levels tql ON tp.level_id = tql.id
            LEFT JOIN main_quests mq ON mq.target_position_id = tp.id AND mq.is_promotion_quest = 1
            WHERE cp.source_position_id = '{$profile['position_id']}'
            AND cp.is_active = 1
            ORDER BY cp.progression_type, tp.name
        ");
        
        // Get user's quest completion history
        $quest_history = $this->mymodel->selectWithQuery("
            SELECT 
                COUNT(*) as total_completed,
                AVG(CASE WHEN (COALESCE(notes_point, 0) + COALESCE(presentation_point, 0)) > 0 THEN (COALESCE(notes_point, 0) + COALESCE(presentation_point, 0)) ELSE 0 END) as avg_performance
            FROM main_quest_submissions mqs
            WHERE mqs.user_profile_id = '$user_profile_id' 
            AND mqs.status = 'approved'
        ");
        
        $recommendations = [
            'user_profile' => $profile,
            'available_paths' => $available_paths,
            'performance_stats' => $quest_history[0] ?? ['total_completed' => 0, 'avg_performance' => 0],
            'recommendations' => []
        ];
        
        // Generate smart recommendations
        foreach ($available_paths as $path) {
            $recommendation = [
                'path' => $path,
                'priority' => 'medium',
                'reasoning' => [],
                'readiness_score' => 0
            ];
            
            // Analyze readiness based on various factors
            $readiness_factors = [];
            
            // Experience factor
            if ($path['estimated_years']) {
                // This would need actual hire date calculation
                $readiness_factors['experience'] = 50; // Placeholder
                $recommendation['reasoning'][] = "Estimated {$path['estimated_years']} years experience required";
            }
            
            // Performance factor
            if ($quest_history[0]['avg_performance'] >= 80) {
                $readiness_factors['performance'] = 90;
                $recommendation['reasoning'][] = "Strong quest completion performance";
            } elseif ($quest_history[0]['avg_performance'] >= 60) {
                $readiness_factors['performance'] = 70;
                $recommendation['reasoning'][] = "Good quest completion performance";
            } else {
                $readiness_factors['performance'] = 40;
                $recommendation['reasoning'][] = "Need to improve quest completion performance";
            }
            
            // Quest availability factor
            if ($path['promotion_quest_id']) {
                $readiness_factors['quest_available'] = 100;
                $recommendation['reasoning'][] = "Promotion quest available: {$path['promotion_quest_title']}";
            } else {
                $readiness_factors['quest_available'] = 30;
                $recommendation['reasoning'][] = "No specific promotion quest available";
            }
            
            // Calculate overall readiness score
            $recommendation['readiness_score'] = !empty($readiness_factors) ? 
                array_sum($readiness_factors) / count($readiness_factors) : 0;
            
            // Set priority based on readiness score
            if ($recommendation['readiness_score'] >= 80) {
                $recommendation['priority'] = 'high';
            } elseif ($recommendation['readiness_score'] >= 60) {
                $recommendation['priority'] = 'medium';
            } else {
                $recommendation['priority'] = 'low';
            }
            
            $recommendations['recommendations'][] = $recommendation;
        }
        
        // Sort recommendations by readiness score
        usort($recommendations['recommendations'], function($a, $b) {
            return $b['readiness_score'] - $a['readiness_score'];
        });
        
        echo json_encode($recommendations);
    }
}