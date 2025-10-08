<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Milestone extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('mymodel');
        $this->load->library('template');
    }

    public function index()
    {
        $data['user'] = $_SESSION['user'];
        
        // Check if user has permission (only HR/Admin roles 1 and 2)
        if (!in_array($data['user']['role'], array('1', '2'))) {
            redirect(base_url() . 'dashboard');
        }

        $data['title'] = 'Milestone & Leaderboard - ' . $this->template->title();
        $data['content'] = $this->load->view("milestone/index", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    // Milestone Management Methods
    public function milestone_tab()
    {
        $data['user'] = $_SESSION['user'];
        $data['template'] = $this->template;
        
        $keyword_category = $_GET['keyword_category'] ?? "Judul";
        $keyword = $_GET['keyword'] ?? "";
        
        $data['keyword_category'] = $keyword_category;

        $qry = "1=1";
        
        if ($keyword) {
            if ($keyword_category == "Judul") {
                $qry .= " AND msq.title LIKE '%$keyword%'";
            } else if ($keyword_category == "Deskripsi") {
                $qry .= " AND msq.description LIKE '%$keyword%'";
            } else if ($keyword_category == "Type") {
                $qry .= " AND msq.milestone_type LIKE '%$keyword%'";
            }
        }

        $query = $this->mymodel->selectWithQuery("SELECT COUNT(msq.id) AS count 
            FROM milestone_side_quests msq 
            LEFT JOIN user u ON msq.created_by = u.id 
            WHERE $qry");
        $data['page'] = CEIL($query[0]['count'] / 10);
        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query[0]['count']) . ' data ditemukan!</label></p>';

        $current_page = intval($_GET['page'] ?? 1);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $url = base_url() . '/milestone/milestone_tab/' . $this->template->get_param();
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $this->load->view("milestone/milestone_tab", $data);
    }

    public function milestone_item()
    {
        $data['template'] = $this->template;
        $keyword_category = $_GET['keyword_category'] ?? "Judul";
        $keyword = $_GET['keyword'] ?? "";
        
        $qry = "1=1";
        
        if ($keyword) {
            if ($keyword_category == "Judul") {
                $qry .= " AND msq.title LIKE '%$keyword%'";
            } else if ($keyword_category == "Deskripsi") {
                $qry .= " AND msq.description LIKE '%$keyword%'";
            } else if ($keyword_category == "Type") {
                $qry .= " AND msq.milestone_type LIKE '%$keyword%'";
            }
        }

        $limit = 10;
        $current_page = $_GET['page'] ?? 1;
        
        if ($current_page <= 1) {
            $offset = 0;
        } else {
            $offset = ($current_page - 1) * $limit;
        }

        $query = $this->mymodel->selectWithQuery("SELECT msq.*, u.full_name as creator_name 
            FROM milestone_side_quests msq 
            LEFT JOIN user u ON msq.created_by = u.id 
            WHERE $qry 
            ORDER BY msq.created_at DESC 
            LIMIT $offset, $limit");
        $data['data'] = $query;
        $data['start'] = $offset;
        
        $this->load->view("milestone/milestone_item", $data);
    }

    // Milestone CRUD Methods
    public function milestone_create_page()
    {
        $data['user'] = $_SESSION['user'];
        
        if (!in_array($data['user']['role'], array('1', '2'))) {
            redirect(base_url() . 'dashboard');
        }

        $data['data'] = array();
        $data['title'] = 'Tambah Milestone Quest - ' . $this->template->title();
        $data['content'] = $this->load->view("milestone/milestone_create", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function milestone_store()
    {
        $user = $_SESSION['user'];
        $dt = $_POST['dt'];
        
        // Validate required fields
        if (!isset($dt['target_value']) || $dt['target_value'] < 0) {
            $msg = 'Target value harus diisi dan tidak boleh negatif!';
            echo $this->template->alert_danger($msg);
            return;
        }
        
        if (empty($dt['reward_description'])) {
            $msg = 'Reward description harus diisi!';
            echo $this->template->alert_danger($msg);
            return;
        }
        
        // Handle file upload for animation image
        if (!empty($_FILES['gambar_animasi']['name'])) {
            $upload_path = 'assets/uploads/milestone_animations/';
            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0755, true);
            }
            
            $config['upload_path'] = $upload_path;
            $config['allowed_types'] = 'gif|jpg|png|jpeg|webp';
            $config['max_size'] = 2048; // 2MB
            $config['file_name'] = 'milestone_' . time() . '_' . uniqid();
            
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

        if ($this->db->insert('milestone_side_quests', $dt)) {
            $msg = 'Tambah milestone berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Tambah milestone tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }

    public function milestone_edit_page()
    {
        $data['user'] = $_SESSION['user'];
        
        if (!in_array($data['user']['role'], array('1', '2'))) {
            redirect(base_url() . 'dashboard');
        }

        $id = $_GET['id'];
        $query = $this->mymodel->selectWithQuery("SELECT * FROM milestone_side_quests WHERE id = '$id'");
        
        if (empty($query)) {
            redirect(base_url() . 'milestone');
        }

        $data['data'] = $query[0];
        $data['title'] = 'Edit Milestone Quest - ' . $this->template->title();
        $data['content'] = $this->load->view("milestone/milestone_edit", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function milestone_update()
    {
        $user = $_SESSION['user'];
        $id = $_POST['id'];
        $dt = $_POST['dt'];
        
        // Validate required fields
        if (!isset($dt['target_value']) || $dt['target_value'] < 0) {
            $msg = 'Target value harus diisi dan tidak boleh negatif!';
            echo $this->template->alert_danger($msg);
            return;
        }
        
        if (empty($dt['reward_description'])) {
            $msg = 'Reward description harus diisi!';
            echo $this->template->alert_danger($msg);
            return;
        }
        
        // Get current data for file handling
        $current_data = $this->mymodel->selectWithQuery("SELECT gambar_animasi FROM milestone_side_quests WHERE id = '$id'");
        if (empty($current_data)) {
            $msg = 'Data tidak ditemukan!';
            echo $this->template->alert_danger($msg);
            return;
        }
        
        // Handle file upload
        if (!empty($_FILES['gambar_animasi']['name'])) {
            $upload_path = 'assets/uploads/milestone_animations/';
            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0755, true);
            }
            
            $config['upload_path'] = $upload_path;
            $config['allowed_types'] = 'gif|jpg|png|jpeg|webp';
            $config['max_size'] = 2048; // 2MB
            $config['file_name'] = 'milestone_' . time() . '_' . uniqid();
            
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

        if ($this->db->update('milestone_side_quests', $dt, array('id' => $id))) {
            $msg = 'Update milestone berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Update milestone tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }

    public function milestone_detail()
    {
        $data['user'] = $_SESSION['user'];
        
        if (!in_array($data['user']['role'], array('1', '2'))) {
            redirect(base_url() . 'dashboard');
        }

        $id = $_GET['id'];
        $query = $this->mymodel->selectWithQuery("SELECT msq.*, u.full_name as creator_name 
            FROM milestone_side_quests msq 
            LEFT JOIN user u ON msq.created_by = u.id 
            WHERE msq.id = '$id'");
        
        if (empty($query)) {
            redirect(base_url() . 'milestone');
        }

        $data['data'] = $query[0];
        $data['title'] = 'Detail Milestone Quest - ' . $this->template->title();
        $data['content'] = $this->load->view("milestone/milestone_detail", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function milestone_remove()
    {
        $id = $_GET['id'];
        $data['data']['id'] = $id;
        $data['type'] = 'milestone';
        $this->load->view("milestone/delete", $data);
    }

    public function milestone_delete()
    {
        $user = $_SESSION['user'];
        $id = $_POST['id'];

        // Get current data to delete associated files
        $current_data = $this->mymodel->selectWithQuery("SELECT gambar_animasi FROM milestone_side_quests WHERE id = '$id'");
        
        if ($this->db->delete('milestone_side_quests', array('id' => $id))) {
            // Delete associated image file if exists
            if (!empty($current_data) && !empty($current_data[0]['gambar_animasi'])) {
                $upload_path = 'assets/uploads/milestone_animations/';
                if (file_exists($upload_path . $current_data[0]['gambar_animasi'])) {
                    unlink($upload_path . $current_data[0]['gambar_animasi']);
                }
            }
            
            $msg = 'Hapus milestone berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Hapus milestone tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }

    // Leaderboard Methods
    public function leaderboard_tab()
    {
        $data['user'] = $_SESSION['user'];
        $data['template'] = $this->template;
        
        $this->load->view("milestone/leaderboard_tab", $data);
    }

    public function monthly_leaderboard()
    {
        $current_month = date('Y-m');
        
        try {
            // First check if user_side_quest_stats table exists and has data
            $table_check = $this->mymodel->selectWithQuery("SHOW TABLES LIKE 'user_side_quest_stats'");
            
            if (empty($table_check)) {
                // Table doesn't exist, return empty data
                $data['data'] = array();
                $data['current_month'] = $current_month;
                $data['error_message'] = 'Statistics table not found. Please run the database setup script.';
                $this->load->view("milestone/monthly_leaderboard_item", $data);
                return;
            }
            
            $query = $this->mymodel->selectWithQuery("SELECT 
                usqs.user_profile_id,
                u.full_name,
                up.join_date,
                p.name as position_name,
                ql.name as level_name,
                COALESCE(usqs.monthly_points_earned, 0) as monthly_points_earned,
                COALESCE(usqs.monthly_completed_quests, 0) as monthly_completed_quests,
                COALESCE(usqs.milestone_bonus_points, 0) as milestone_bonus_points,
                COALESCE(usqs.monthly_points_earned, 0) as total_monthly_points
                FROM user_side_quest_stats usqs
                LEFT JOIN user_profile up ON usqs.user_profile_id = up.id
                LEFT JOIN user u ON up.user_id = u.id
                LEFT JOIN positions p ON up.position_id = p.id
                LEFT JOIN quest_levels ql ON p.level_id = ql.id
                WHERE usqs.current_month = '$current_month' AND (usqs.monthly_points_earned > 0 OR usqs.monthly_completed_quests > 0)
                ORDER BY total_monthly_points DESC, usqs.monthly_completed_quests DESC
                LIMIT 20");
            
            $data['data'] = $query ? $query : array();
            $data['current_month'] = $current_month;
            
        } catch (Exception $e) {
            // Handle database errors gracefully
            $data['data'] = array();
            $data['current_month'] = $current_month;
            $data['error_message'] = 'Error loading leaderboard data. Please contact administrator.';
            
            // Log error for debugging (if logging is available)
            if (method_exists($this, 'log_message')) {
                log_message('error', 'Monthly leaderboard error: ' . $e->getMessage());
            }
        }
        
        $this->load->view("milestone/monthly_leaderboard_item", $data);
    }

    public function ongoing_leaderboard()
    {
        try {
            // First check if user_side_quest_stats table exists and has data
            $table_check = $this->mymodel->selectWithQuery("SHOW TABLES LIKE 'user_side_quest_stats'");
            
            if (empty($table_check)) {
                // Table doesn't exist, return empty data
                $data['data'] = array();
                $data['error_message'] = 'Statistics table not found. Please run the database setup script.';
                $this->load->view("milestone/ongoing_leaderboard_item", $data);
                return;
            }
            
            $query = $this->mymodel->selectWithQuery("SELECT 
                usqs.user_profile_id,
                u.full_name,
                up.join_date,
                p.name as position_name,
                ql.name as level_name,
                COALESCE(usqs.total_points_earned, 0) as total_points_earned,
                COALESCE(usqs.total_completed_quests, 0) as total_completed_quests,
                COALESCE(usqs.milestone_bonus_points, 0) as milestone_bonus_points,
                COALESCE(usqs.total_points_earned, 0) as total_all_time_points
                FROM user_side_quest_stats usqs
                LEFT JOIN user_profile up ON usqs.user_profile_id = up.id
                LEFT JOIN user u ON up.user_id = u.id
                LEFT JOIN positions p ON up.position_id = p.id
                LEFT JOIN quest_levels ql ON p.level_id = ql.id
                WHERE (usqs.total_points_earned > 0 OR usqs.total_completed_quests > 0)
                ORDER BY total_all_time_points DESC, usqs.total_completed_quests DESC
                LIMIT 20");
            
            $data['data'] = $query ? $query : array();
            
        } catch (Exception $e) {
            // Handle database errors gracefully
            $data['data'] = array();
            $data['error_message'] = 'Error loading leaderboard data. Please contact administrator.';
            
            // Log error for debugging (if logging is available)
            if (method_exists($this, 'log_message')) {
                log_message('error', 'Ongoing leaderboard error: ' . $e->getMessage());
            }
        }
        
        $this->load->view("milestone/ongoing_leaderboard_item", $data);
    }

    // Stats Management Methods
    public function initialize_stats()
    {
        $user = $_SESSION['user'];
        
        // Check if user has permission (only HR/Admin roles 1 and 2)
        if (!in_array($user['role'], array('1', '2'))) {
            echo $this->template->alert_danger('Access denied! Only HR/Admin can initialize stats.');
            return;
        }

        try {
            // First check if tables exist
            $stats_table_check = $this->mymodel->selectWithQuery("SHOW TABLES LIKE 'user_side_quest_stats'");
            $submissions_table_check = $this->mymodel->selectWithQuery("SHOW TABLES LIKE 'side_quest_submissions'");
            
            if (empty($stats_table_check)) {
                echo $this->template->alert_danger('Statistics table not found. Please run the database setup script first.');
                return;
            }
            
            if (empty($submissions_table_check)) {
                echo $this->template->alert_warning('No side quest submissions table found. Creating empty stats records.');
                // Create empty stats for all user profiles
                $this->db->query("INSERT IGNORE INTO user_side_quest_stats (user_profile_id, total_completed_quests, total_points_earned, monthly_completed_quests, monthly_points_earned, current_month)
                    SELECT 
                        up.id as user_profile_id,
                        0 as total_completed_quests,
                        0 as total_points_earned,
                        0 as monthly_completed_quests,
                        0 as monthly_points_earned,
                        DATE_FORMAT(NOW(), '%Y-%m') as current_month
                    FROM user_profile up
                    WHERE up.id NOT IN (SELECT user_profile_id FROM user_side_quest_stats)");
                echo $this->template->alert_success('Empty stats records created for all user profiles.');
                return;
            }

            // Initialize stats from side quest submissions only
            $this->db->query("INSERT INTO user_side_quest_stats (user_profile_id, total_completed_quests, total_points_earned, monthly_completed_quests, monthly_points_earned, current_month)
                SELECT 
                    sqs.user_profile_id,
                    COUNT(*) as total_completed_quests,
                    COALESCE(SUM(COALESCE(sqs.notes_point, 0) + COALESCE(sqs.presentation_point, 0) + COALESCE(sq.points, 0)), 0) as total_points_earned,
                    COUNT(CASE WHEN DATE_FORMAT(sqs.approved_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m') THEN 1 END) as monthly_completed_quests,
                    COALESCE(SUM(CASE WHEN DATE_FORMAT(sqs.approved_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m') 
                                 THEN COALESCE(sqs.notes_point, 0) + COALESCE(sqs.presentation_point, 0) + COALESCE(sq.points, 0) 
                                 ELSE 0 END), 0) as monthly_points_earned,
                    DATE_FORMAT(NOW(), '%Y-%m') as current_month
                FROM side_quest_submissions sqs
                LEFT JOIN side_quests sq ON sqs.quest_id = sq.id
                WHERE sqs.status = 'approved'
                GROUP BY sqs.user_profile_id
                ON DUPLICATE KEY UPDATE
                    total_completed_quests = VALUES(total_completed_quests),
                    total_points_earned = VALUES(total_points_earned),
                    monthly_completed_quests = VALUES(monthly_completed_quests),
                    monthly_points_earned = VALUES(monthly_points_earned),
                    current_month = VALUES(current_month),
                    last_updated = NOW()");

            // Also create empty records for users without submissions
            $this->db->query("INSERT IGNORE INTO user_side_quest_stats (user_profile_id, total_completed_quests, total_points_earned, monthly_completed_quests, monthly_points_earned, current_month)
                SELECT 
                    up.id as user_profile_id,
                    0 as total_completed_quests,
                    0 as total_points_earned,
                    0 as monthly_completed_quests,
                    0 as monthly_points_earned,
                    DATE_FORMAT(NOW(), '%Y-%m') as current_month
                FROM user_profile up
                WHERE up.id NOT IN (SELECT user_profile_id FROM user_side_quest_stats)");

            // Get count of initialized records
            $stats_count = $this->mymodel->selectWithQuery("SELECT COUNT(*) as count FROM user_side_quest_stats");
            $count = !empty($stats_count) ? $stats_count[0]['count'] : 0;

            echo $this->template->alert_success("Stats initialization completed! Initialized $count user profile records.");
            
        } catch (Exception $e) {
            echo $this->template->alert_danger('Error initializing stats: ' . $e->getMessage());
        }
    }

    // Claimable Milestone Methods
    public function claimable_milestone()
    {
        $data['user'] = $_SESSION['user'];
        $data['template'] = $this->template;
        
        // Check if user has permission (only HR/Admin roles 1 and 2)
        if (!in_array($data['user']['role'], array('1', '2'))) {
            redirect(base_url() . 'dashboard');
        }

        // Get milestone claim history (empty initially, loaded via AJAX)
        $data['milestone_claim_history'] = array();

        // Load view directly like leaderboard_tab (no template wrapper)
        $this->load->view("milestone/claimable_milestone", $data);
    }

    public function claimable_milestone_item()
    {
        $data['user'] = $_SESSION['user'];
        $data['template'] = $this->template;
        
        $keyword_category = isset($_GET['keyword_category']) ? $_GET['keyword_category'] : "User";
        $keyword = isset($_GET['keyword']) ? $_GET['keyword'] : "";
        
        $data['keyword_category'] = $keyword_category;
        $data['keyword'] = $keyword;

        $qry = "1=1"; // Show all milestone achievements regardless of status
        
        if ($keyword) {
            if ($keyword_category == "User") {
                $qry .= " AND u.full_name LIKE '%$keyword%'";
            } else if ($keyword_category == "Milestone") {
                $qry .= " AND msq.title LIKE '%$keyword%'";
            } else if ($keyword_category == "Type") {
                $qry .= " AND msq.milestone_type LIKE '%$keyword%'";
            } else if ($keyword_category == "Status") {
                $qry .= " AND ma.status LIKE '%$keyword%'";
            }
        }

        $query = $this->mymodel->selectWithQuery("SELECT COUNT(ma.id) AS count 
            FROM milestone_achievements ma
            LEFT JOIN milestone_side_quests msq ON ma.milestone_quest_id = msq.id
            LEFT JOIN user_profile up ON ma.user_profile_id = up.id
            LEFT JOIN user u ON up.user_id = u.id 
            WHERE $qry");
        $data['page'] = CEIL($query[0]['count'] / 10);
        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query[0]['count']) . ' data ditemukan!</label></p>';

        $current_page = intval($_GET['page'] ?? 1);
        $offset = ($current_page - 1) * 10;

        $claim_history = $this->mymodel->selectWithQuery("
            SELECT ma.*, 
                   COALESCE(ma.status, 'waiting_approval') as status,
                   msq.title as milestone_title,
                   msq.description as milestone_description,
                   msq.reward_points,
                   msq.milestone_type,
                   u.full_name as claimed_by_name,
                   u.username as claimed_by_username,
                   up.position_id,
                   p.name as position_name,
                   au.full_name as approved_by_name
            FROM milestone_achievements ma
            LEFT JOIN milestone_side_quests msq ON ma.milestone_quest_id = msq.id
            LEFT JOIN user_profile up ON ma.user_profile_id = up.id
            LEFT JOIN user u ON up.user_id = u.id
            LEFT JOIN positions p ON up.position_id = p.id
            LEFT JOIN user au ON ma.approved_by = au.id
            WHERE $qry
            ORDER BY 
                CASE WHEN COALESCE(ma.status, 'waiting_approval') = 'waiting_approval' THEN 1
                     WHEN COALESCE(ma.status, 'waiting_approval') = 'approved' THEN 2
                     WHEN COALESCE(ma.status, 'waiting_approval') = 'delivered' THEN 3 END ASC,
                ma.achieved_at DESC
            LIMIT 10 OFFSET $offset
        ");

        // Format the data for display
        foreach ($claim_history as &$claim) {
            $claim['claimed_at_formatted'] = date('d M Y H:i', strtotime($claim['claimed_at']));
            $claim['milestone_type_display'] = '';
            
            switch ($claim['milestone_type']) {
                case 'quest_count':
                    $claim['milestone_type_display'] = 'Quest Count';
                    break;
                case 'total_points':
                    $claim['milestone_type_display'] = 'Total Points';
                    break;
                case 'monthly_points':
                    $claim['milestone_type_display'] = 'Monthly Points';
                    break;
            }
        }

        $data['milestone_claim_history'] = $claim_history;
        $this->load->view("milestone/claimable_milestone_item", $data);
    }

    private function getMilestoneClaimHistory()
    {
        $claim_history = $this->mymodel->selectWithQuery("
            SELECT ma.*, 
                   msq.title as milestone_title,
                   msq.description as milestone_description,
                   msq.reward_points,
                   msq.milestone_type,
                   u.full_name as claimed_by_name,
                   u.username as claimed_by_username,
                   up.position_id,
                   p.name as position_name
            FROM milestone_achievements ma
            LEFT JOIN milestone_side_quests msq ON ma.milestone_quest_id = msq.id
            LEFT JOIN user_profile up ON ma.user_profile_id = up.id
            LEFT JOIN user u ON up.user_id = u.id
            LEFT JOIN positions p ON up.position_id = p.id
            WHERE ma.claimed = 1
            ORDER BY ma.claimed_at DESC
        ");

        // Format the data for display
        foreach ($claim_history as &$claim) {
            $claim['claimed_at_formatted'] = date('d M Y H:i', strtotime($claim['claimed_at']));
            $claim['milestone_type_display'] = '';
            
            switch ($claim['milestone_type']) {
                case 'quest_count':
                    $claim['milestone_type_display'] = 'Quest Count';
                    break;
                case 'total_points':
                    $claim['milestone_type_display'] = 'Total Points';
                    break;
                case 'monthly_points':
                    $claim['milestone_type_display'] = 'Monthly Points';
                    break;
            }
        }

        return $claim_history;
    }

    // New methods for approval workflow
    public function approve_claim()
    {
        $user = $_SESSION['user'];
        
        // Check if user has permission (only HR/Admin roles 1 and 2)
        if (!in_array($user['role'], array('1', '2'))) {
            echo $this->template->alert_danger('Anda tidak memiliki akses untuk menyetujui claim milestone.');
            return;
        }

        $achievement_id = $_POST['achievement_id'];
        
        // Get achievement details
        $achievement = $this->mymodel->selectWithQuery("
            SELECT ma.*, msq.reward_points, msq.title as milestone_title,
                   up.user_id, up.score
            FROM milestone_achievements ma
            LEFT JOIN milestone_side_quests msq ON ma.milestone_quest_id = msq.id  
            LEFT JOIN user_profile up ON ma.user_profile_id = up.id
            WHERE ma.id = '$achievement_id' AND (ma.status = 'waiting_approval' OR ma.status IS NULL)
        ");

        if (empty($achievement)) {
            echo $this->template->alert_danger('Achievement tidak ditemukan atau sudah diproses. Achievement ID: ' . $achievement_id);
            return;
        }

        $achievement_data = $achievement[0];
        $reward_points = intval($achievement_data['reward_points']);
        $user_profile_id = $achievement_data['user_profile_id'];
        $current_score = intval($achievement_data['score']);

        // Check if user has sufficient points
        if ($current_score < $reward_points) {
            echo $this->template->alert_danger('User tidak memiliki poin yang cukup untuk claim ini. Dibutuhkan: ' . $reward_points . ', tersedia: ' . $current_score);
            return;
        }

        // Start database transaction
        $this->db->trans_begin();

        try {
            // Update achievement status to approved
            $update_data = array(
                'status' => 'approved',
                'approved_at' => date('Y-m-d H:i:s'),
                'approved_by' => $user['id']
            );
            $this->db->where('id', $achievement_id);
            $this->db->update('milestone_achievements', $update_data);

            // Deduct points from user profile
            if ($reward_points > 0) {
                $this->db->query("
                    UPDATE user_profile 
                    SET score = score - " . $reward_points . "
                    WHERE id = '$user_profile_id'
                ");
                
                // Also update milestone bonus points
                $this->db->query("
                    UPDATE user_side_quest_stats 
                    SET milestone_bonus_points = milestone_bonus_points - " . $reward_points . "
                    WHERE user_profile_id = '$user_profile_id'
                ");
            }

            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Database transaction failed');
            }

            $this->db->trans_commit();
            echo $this->template->alert_success('Claim milestone berhasil disetujui! ' . $reward_points . ' poin telah dipotong dari user.');
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            echo $this->template->alert_danger('Gagal menyetujui claim: ' . $e->getMessage());
        }
    }

    public function mark_delivered()
    {
        $user = $_SESSION['user'];
        
        // Check if user has permission (only HR/Admin roles 1 and 2)
        if (!in_array($user['role'], array('1', '2'))) {
            echo $this->template->alert_danger('Anda tidak memiliki akses untuk menandai milestone sebagai terkirim.');
            return;
        }

        $achievement_id = $_POST['achievement_id'];
        
        // Get achievement details
        $achievement = $this->mymodel->selectWithQuery("
            SELECT ma.*, msq.title as milestone_title
            FROM milestone_achievements ma
            LEFT JOIN milestone_side_quests msq ON ma.milestone_quest_id = msq.id  
            WHERE ma.id = '$achievement_id' AND ma.status = 'approved'
        ");

        if (empty($achievement)) {
            echo $this->template->alert_danger('Achievement tidak ditemukan atau belum disetujui.');
            return;
        }

        // Handle file upload for proof image
        $proof_image = null;
        if (!empty($_FILES['proof_image']['name'])) {
            $config['upload_path'] = './assets/uploads/milestone_proofs/';
            $config['allowed_types'] = 'jpg|jpeg|png|pdf';
            $config['max_size'] = 5120; // 5MB
            $config['file_name'] = 'proof_' . $achievement_id . '_' . time();
            
            // Create directory if not exists
            if (!is_dir($config['upload_path'])) {
                mkdir($config['upload_path'], 0755, true);
            }
            
            $this->load->library('upload', $config);
            
            if ($this->upload->do_upload('proof_image')) {
                $upload_data = $this->upload->data();
                $proof_image = $upload_data['file_name'];
            } else {
                echo $this->template->alert_danger('Gagal upload gambar bukti: ' . $this->upload->display_errors());
                return;
            }
        }

        // Update achievement status to delivered
        $update_data = array(
            'status' => 'delivered',
            'delivered_at' => date('Y-m-d H:i:s'),
            'claimed' => 1, // Set for backward compatibility
            'claimed_at' => date('Y-m-d H:i:s')
        );

        if ($proof_image) {
            $update_data['proof_image'] = $proof_image;
        }

        if ($this->mymodel->updateData('milestone_achievements', $update_data, array('id' => $achievement_id))) {
            if ($proof_image) {
                echo $this->template->alert_success('Milestone berhasil ditandai sebagai terkirim dengan bukti pengiriman.');
            } else {
                echo $this->template->alert_success('Milestone berhasil ditandai sebagai terkirim.');
            }
        } else {
            echo $this->template->alert_danger('Gagal menandai milestone sebagai terkirim.');
        }
    }

    public function get_claim_details()
    {
        $achievement_id = $_POST['achievement_id'];
        
        $achievement = $this->mymodel->selectWithQuery("
            SELECT ma.*, msq.title as milestone_title, msq.description, msq.reward_points,
                   msq.milestone_type, u.full_name as user_name, u.username,
                   p.name as position_name
            FROM milestone_achievements ma
            LEFT JOIN milestone_side_quests msq ON ma.milestone_quest_id = msq.id
            LEFT JOIN user_profile up ON ma.user_profile_id = up.id
            LEFT JOIN user u ON up.user_id = u.id
            LEFT JOIN positions p ON up.position_id = p.id
            WHERE ma.id = '$achievement_id'
        ");

        if (!empty($achievement)) {
            echo json_encode($achievement[0]);
        } else {
            echo json_encode(['error' => 'Achievement not found']);
        }
    }
}