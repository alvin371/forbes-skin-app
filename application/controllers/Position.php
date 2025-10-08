<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class Position extends BaseController
{
    // Configure BaseController for automatic permission checking
    protected $require_permissions = false; // Temporarily disable permission checking for career tree debugging
    protected $show_403_on_deny = true;
    protected $public_methods = ['test_db', 'get_career_tree_data', 'get_user_career_tree_data', 'test_career_endpoint', 'career_progression_tab', 'create_missing_tables', 'get_dendrogram_data', 'get_org_chart_data', 'org_chart', 'calculate_career_paths', 'get_clustering_metrics', 'save_custom_arrangement', 'get_custom_arrangement', 'delete_custom_arrangement', 'apply_arrangement', 'get_dual_view_data', 'get_career_path_details', 'dual_view_org_chart', 'get_position_details', 'career_paths_overview']; // Allow these methods without permissions for testing
    
    public function __construct()
    {
        parent::__construct();
        
        // Load required libraries and models for BaseController integration
        $this->load->database();
        $this->load->model('mymodel');
        $this->load->library('permission');
        $this->load->library('template');
    }

    public function index()
    {
        $data['user'] = $_SESSION['user'];
        $data['title'] = 'Position Management - ' . $this->template->title();
        $data['content'] = $this->load->view("position/index", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    /**
     * Organization Chart - Interactive hierarchical visualization
     */
    public function org_chart()
    {
        $data['user'] = $_SESSION['user'];
        $data['title'] = 'Organization Chart - ' . $this->template->title();
        $data['content'] = $this->load->view("position/org_chart", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    /**
     * Position management tab content
     */
    public function position_management_tab()
    {
        $data['user'] = $_SESSION['user'];
        $user_id = $data['user']['id'];
        
        // BaseController automatically checks 'view' permission for index()
        // Pass permission data to view
        $data['can_create'] = $this->permission->check_permission($user_id, 'position', 'create');
        $data['can_edit'] = $this->permission->check_permission($user_id, 'position', 'edit');
        $data['can_delete'] = $this->permission->check_permission($user_id, 'position', 'delete');

        $keyword_category = $_GET['keyword_category'] ?? "Nama";
        $keyword = $_GET['keyword'] ?? "";
        $level_filter = $_GET['level_filter'] ?? "";
        
        $data['keyword_category'] = $keyword_category;
        $data['level_filter'] = $level_filter;

        // Get quest levels for dropdown (optional labeling)
        $data['quest_levels'] = $this->mymodel->selectWithQuery("SELECT * FROM quest_levels ORDER BY level_order ASC");

        $qry = "1=1";
        
        if ($keyword) {
            if ($keyword_category == "Nama") {
                $qry .= " AND p.name LIKE '%$keyword%'";
            } else if ($keyword_category == "Level") {
                $qry .= " AND ql.name LIKE '%$keyword%'";
            }
        }
        
        if ($level_filter) {
            $qry .= " AND p.level_id = '$level_filter'";
        }

        $query = $this->mymodel->selectWithQuery("SELECT COUNT(p.id) AS count 
            FROM positions p 
            LEFT JOIN quest_levels ql ON p.level_id = ql.id
            WHERE $qry");
        $data['page'] = CEIL($query[0]['count'] / 10);
        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query[0]['count']) . ' data ditemukan!</label></p>';

        $current_page = intval($_GET['page'] ?? 1);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $url = base_url() . '/position/' . $this->template->get_param();
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $this->load->view("position/position_management_tab", $data);
    }

    public function item()
    {
        $data['template'] = $this->template;
        $user_id = $_SESSION['user']['id'];
        
        // Pass permission data to view
        $data['can_create'] = $this->permission->check_permission($user_id, 'position', 'create');
        $data['can_edit'] = $this->permission->check_permission($user_id, 'position', 'edit');
        $data['can_delete'] = $this->permission->check_permission($user_id, 'position', 'delete');
        
        $keyword_category = $_GET['keyword_category'] ?? "Nama";
        $keyword = $_GET['keyword'] ?? "";
        $level_filter = $_GET['level_filter'] ?? "";
        
        $qry = "1=1";
        
        if ($keyword) {
            if ($keyword_category == "Nama") {
                $qry .= " AND p.name LIKE '%$keyword%'";
            } else if ($keyword_category == "Level") {
                $qry .= " AND ql.name LIKE '%$keyword%'";
            }
        }
        
        if ($level_filter) {
            $qry .= " AND p.level_id = '$level_filter'";
        }

        $limit = 10;
        $current_page = $_GET['page'] ?? 1;
        
        if ($current_page <= 1) {
            $offset = 0;
        } else {
            $offset = ($current_page - 1) * $limit;
        }

        $query = $this->mymodel->selectWithQuery("SELECT p.*, COALESCE(ql.name, 'No Level') as level_name 
            FROM positions p 
            LEFT JOIN quest_levels ql ON p.level_id = ql.id
            WHERE $qry 
            ORDER BY p.position_order ASC, p.name ASC 
            LIMIT $offset, $limit");
        $data['data'] = $query;
        $data['start'] = $offset;
        
        $this->load->view("position/item", $data);
    }

    public function create_page()
    {
        $data['user'] = $_SESSION['user'];

        if (!in_array($data['user']['role'], array('1', '2'))) {
            redirect(base_url() . 'dashboard');
        }

        $data['data'] = array();
        $data['quest_levels'] = $this->mymodel->selectWithQuery("SELECT * FROM quest_levels ORDER BY id ASC");

        // Get all positions for parent dropdown
        $data['all_positions'] = $this->mymodel->selectWithQuery("
            SELECT id, name, department
            FROM positions
            ORDER BY name ASC
        ");

        // Get all positions for career path targets
        $data['available_positions'] = $this->mymodel->selectWithQuery("
            SELECT id, name, department, level_id
            FROM positions
            ORDER BY department, name
        ");

        $data['title'] = 'Tambah Position - ' . $this->template->title();
        $data['content'] = $this->load->view("position/create_page", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function store()
    {
        $user = $_SESSION['user'];
        $dt = $_POST['dt'];

        // Debug: Log original input
        error_log("Original input: " . print_r($dt, true));

        // Add user tracking and timestamps
        $dt['created_by'] = $user['id'];
        $dt['created_at'] = date('Y-m-d H:i:s');

        // Validate required fields
        if (empty($dt['name'])) {
            $msg = 'Nama position harus diisi!';
            echo $this->template->alert_danger($msg);
            return;
        }

        // Ensure data is properly preserved
        $dt['name'] = trim($dt['name']); // Only trim whitespace at start/end

        // Set default values (quest level is optional for labeling only)
        $dt['position_order'] = $dt['position_order'] ?? 0;
        $dt['level_id'] = !empty($dt['level_id']) ? $dt['level_id'] : null; // Allow NULL for optional quest level

        // Handle parent position as optional (empty string should be NULL for root positions)
        if (isset($dt['parent_position_id'])) {
            $dt['parent_position_id'] = !empty($dt['parent_position_id']) ? $dt['parent_position_id'] : null;
        }

        // Remove fields that are no longer used (position properties, progression requirements)
        unset($dt['has_lead_role']);
        unset($dt['is_leadership']);
        unset($dt['progression_requirements']);

        // Build career_paths JSON from form data (simplified: only position and type)
        if (isset($_POST['career_paths']) && !empty($_POST['career_paths'])) {
            $paths = $_POST['career_paths'];
            $career_paths_data = [
                'next_roles' => []
            ];

            foreach ($paths as $path) {
                // Skip if position_id is empty (incomplete path)
                if (empty($path['position_id'])) {
                    continue;
                }

                $career_paths_data['next_roles'][] = [
                    'position_id' => (int)$path['position_id'],
                    'position_name' => $path['position_name'] ?? '',
                    'path_type' => $path['path_type'] ?? 'vertical_technical'
                ];
            }

            // Only set career_paths if there are valid paths
            if (!empty($career_paths_data['next_roles'])) {
                $dt['career_paths'] = json_encode($career_paths_data);
            } else {
                $dt['career_paths'] = null;
            }
        } else {
            $dt['career_paths'] = null; // Clear if no paths provided
        }

        // Debug: Log final data before insert
        error_log("Final data: " . print_r($dt, true));

        if ($this->db->insert('positions', $dt)) {
            $msg = 'Position berhasil ditambahkan!';
            echo $this->template->alert_success($msg);
        } else {
            // Get database error for debugging
            $error = $this->db->error();
            error_log("DB Error: " . print_r($error, true));
            $msg = 'Gagal menambahkan position! Error: ' . $error['message'];
            echo $this->template->alert_danger($msg);
        }
    }

    public function edit_page()
    {
        $data['user'] = $_SESSION['user'];

        if (!in_array($data['user']['role'], array('1', '2'))) {
            redirect(base_url() . 'dashboard');
        }

        $id = $_GET['id'];
        $query = $this->mymodel->selectWithQuery("SELECT * FROM positions WHERE id = '$id'");

        if (empty($query)) {
            redirect(base_url() . 'position');
        }

        $data['data'] = $query[0];
        $data['quest_levels'] = $this->mymodel->selectWithQuery("SELECT * FROM quest_levels ORDER BY id ASC");

        // Get all positions for parent dropdown, excluding current position to prevent self-parenting
        $data['all_positions'] = $this->mymodel->selectWithQuery("
            SELECT id, name, department
            FROM positions
            WHERE id != '$id'
            ORDER BY name ASC
        ");

        // Get all OTHER positions for career path targets (excluding current position)
        $data['available_positions'] = $this->mymodel->selectWithQuery("
            SELECT id, name, department, level_id
            FROM positions
            WHERE id != '$id'
            ORDER BY department, name
        ");

        // Parse existing career_paths JSON for editing
        $data['existing_career_paths'] = [];
        if (!empty($data['data']['career_paths'])) {
            $paths_json = json_decode($data['data']['career_paths'], true);
            if (isset($paths_json['next_roles']) && is_array($paths_json['next_roles'])) {
                $data['existing_career_paths'] = $paths_json['next_roles'];
            }
        }

        $data['title'] = 'Edit Position - ' . $this->template->title();
        $data['content'] = $this->load->view("position/edit_page", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function update()
    {
        $user = $_SESSION['user'];
        $id = $_POST['id'];
        $dt = $_POST['dt'];

        // Debug: Log original input
        error_log("Update original input: " . print_r($dt, true));

        // Add user tracking
        $dt['updated_by'] = $user['id'];
        $dt['updated_at'] = date('Y-m-d H:i:s');

        // Validate required fields
        if (empty($dt['name'])) {
            $msg = 'Nama position harus diisi!';
            echo $this->template->alert_danger($msg);
            return;
        }

        // Ensure data is properly preserved
        $dt['name'] = trim($dt['name']); // Only trim whitespace at start/end

        // Handle quest level as optional labeling field
        $dt['position_order'] = $dt['position_order'] ?? 0;
        $dt['level_id'] = !empty($dt['level_id']) ? $dt['level_id'] : null; // Allow NULL for optional quest level

        // Handle parent position as optional (empty string should be NULL for root positions)
        if (isset($dt['parent_position_id'])) {
            $dt['parent_position_id'] = !empty($dt['parent_position_id']) ? $dt['parent_position_id'] : null;
        }

        // Remove fields that are no longer used (position properties, progression requirements)
        unset($dt['has_lead_role']);
        unset($dt['is_leadership']);
        unset($dt['progression_requirements']);

        // Build career_paths JSON from form data (simplified: only position and type)
        if (isset($_POST['career_paths']) && !empty($_POST['career_paths'])) {
            $paths = $_POST['career_paths'];
            $career_paths_data = [
                'next_roles' => []
            ];

            foreach ($paths as $path) {
                // Skip if position_id is empty (incomplete path)
                if (empty($path['position_id'])) {
                    continue;
                }

                $career_paths_data['next_roles'][] = [
                    'position_id' => (int)$path['position_id'],
                    'position_name' => $path['position_name'] ?? '',
                    'path_type' => $path['path_type'] ?? 'vertical_technical'
                ];
            }

            // Only set career_paths if there are valid paths
            if (!empty($career_paths_data['next_roles'])) {
                $dt['career_paths'] = json_encode($career_paths_data);
            } else {
                $dt['career_paths'] = null;
            }
        } else {
            $dt['career_paths'] = null; // Clear if no paths provided
        }

        // Debug: Log final data before update
        error_log("Update final data: " . print_r($dt, true));

        if ($this->db->update('positions', $dt, array('id' => $id))) {
            $msg = 'Position berhasil diupdate!';
            echo $this->template->alert_success($msg);
        } else {
            // Get database error for debugging
            $error = $this->db->error();
            error_log("Update DB Error: " . print_r($error, true));
            $msg = 'Gagal mengupdate position! Error: ' . $error['message'];
            echo $this->template->alert_danger($msg);
        }
    }

    public function detail()
    {
        $data['user'] = $_SESSION['user'];
        
        if (!in_array($data['user']['role'], array('1', '2'))) {
            redirect(base_url() . 'dashboard');
        }

        $id = $_GET['id'];
        $query = $this->mymodel->selectWithQuery("SELECT p.*, COALESCE(ql.name, 'No Level') as level_name 
            FROM positions p 
            LEFT JOIN quest_levels ql ON p.level_id = ql.id
            WHERE p.id = '$id'");
        
        if (empty($query)) {
            redirect(base_url() . 'position');
        }

        $data['data'] = $query[0];
        
        // Get users with this position
        $employees = $this->mymodel->selectWithQuery("SELECT u.full_name, u.email, up.* 
            FROM user_profile up 
            LEFT JOIN user u ON up.user_id = u.id 
            WHERE up.position_id = '$id' 
            ORDER BY u.full_name ASC");
        $data['employees'] = $employees;
        
        $data['title'] = 'Detail Position - ' . $this->template->title();
        $data['content'] = $this->load->view("position/detail_page", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function remove()
    {
        $id = $_GET['id'];
        $data['data']['id'] = $id;
        $this->load->view("position/delete", $data);
    }

    /**
     * Debug method to check position usage - temporary for troubleshooting
     */
    public function debug_position_usage()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            echo "No ID provided";
            return;
        }

        echo "<h3>Debug Position Usage for ID: $id</h3>";
        
        // Check user_profile table
        $profiles = $this->mymodel->selectWithQuery("SELECT up.*, u.full_name FROM user_profile up LEFT JOIN user u ON up.user_id = u.id WHERE up.position_id = '$id'");
        echo "<h4>User Profiles using this position:</h4>";
        if (empty($profiles)) {
            echo "<p>No user profiles found with this position_id</p>";
        } else {
            echo "<ul>";
            foreach ($profiles as $profile) {
                echo "<li>User: " . ($profile['full_name'] ?? 'N/A') . " (ID: " . $profile['user_id'] . ", Position ID: " . $profile['position_id'] . ")</li>";
            }
            echo "</ul>";
        }

        // Check career progressions
        $progressions = $this->mymodel->selectWithQuery("SELECT * FROM career_progressions WHERE source_position_id = '$id' OR target_position_id = '$id'");
        echo "<h4>Career Progressions involving this position:</h4>";
        if (empty($progressions)) {
            echo "<p>No career progressions found</p>";
        } else {
            echo "<ul>";
            foreach ($progressions as $prog) {
                echo "<li>From: " . $prog['source_position_id'] . " To: " . $prog['target_position_id'] . " (Active: " . $prog['is_active'] . ")</li>";
            }
            echo "</ul>";
        }

        // Check if position exists
        $position = $this->mymodel->selectWithQuery("SELECT * FROM positions WHERE id = '$id'");
        echo "<h4>Position Details:</h4>";
        if (empty($position)) {
            echo "<p>Position not found!</p>";
        } else {
            echo "<p>Name: " . $position[0]['name'] . ", Level: " . $position[0]['level_id'] . "</p>";
        }
    }

    /**
     * Test method to verify input handling - temporary for troubleshooting
     */
    public function test_input()
    {
        echo "<h3>Test Input Processing</h3>";
        echo "<form method='POST' action='" . base_url() . "position/test_input_process'>";
        echo "<label>Test Name:</label><br>";
        echo "<input type='text' name='test_name' placeholder='Try: Test \"quoted\" name with spaces' style='width: 300px; padding: 5px;'><br><br>";
        echo "<input type='submit' value='Test Submit' style='padding: 5px 10px;'>";
        echo "</form>";
    }

    /**
     * Process test input to see what's actually received
     */
    public function test_input_process()
    {
        if ($_POST) {
            echo "<h3>Received Input:</h3>";
            echo "<pre>";
            echo "Raw POST data:\n";
            print_r($_POST);
            echo "\nEscaped data:\n";
            if (isset($_POST['test_name'])) {
                echo "Original: " . $_POST['test_name'] . "\n";
                echo "Escaped: " . $this->db->escape_str($_POST['test_name']) . "\n";
                echo "HTML: " . htmlspecialchars($_POST['test_name'], ENT_QUOTES, 'UTF-8') . "\n";
            }
            echo "</pre>";
        } else {
            echo "No POST data received";
        }
    }

    public function delete()
    {
        $user = $_SESSION['user'];
        $id = $_POST['id'];

        // Validate and sanitize the ID
        if (empty($id) || !is_numeric($id)) {
            $msg = 'ID posisi tidak valid!';
            echo $this->template->alert_danger($msg);
            die;
        }

        // Check if this position is being used by user profiles (check for actual valid position_id, not NULL)
        $profiles = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count FROM user_profile WHERE position_id = '$id' AND position_id IS NOT NULL");
        if ($profiles[0]['count'] > 0) {
            // Get specific user details for debugging
            $users = $this->mymodel->selectWithQuery("SELECT u.full_name, up.position_id FROM user_profile up LEFT JOIN user u ON up.user_id = u.id WHERE up.position_id = '$id' LIMIT 3");
            $user_names = array_column($users, 'full_name');
            $msg = 'Posisi tidak dapat dihapus karena sedang digunakan oleh karyawan: ' . implode(', ', $user_names);
            echo $this->template->alert_danger($msg);
            die;
        }

        // Check if this position has career progressions
        $progressions = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count FROM career_progressions WHERE (source_position_id = '$id' OR target_position_id = '$id') AND is_active = 1");
        if ($progressions[0]['count'] > 0) {
            $msg = 'Posisi tidak dapat dihapus karena memiliki jalur karir terkait!';
            echo $this->template->alert_danger($msg);
            die;
        }

        // Additional check: ensure the position exists before trying to delete
        $position_exists = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count FROM positions WHERE id = '$id'");
        if ($position_exists[0]['count'] == 0) {
            $msg = 'Posisi tidak ditemukan!';
            echo $this->template->alert_danger($msg);
            die;
        }

        if ($this->db->delete('positions', array('id' => $id))) {
            $msg = 'Hapus data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Hapus data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }

    /**
     * Bulk delete positions
     */
    public function bulk_delete()
    {
        // Set content type to JSON
        header('Content-Type: application/json');

        $user = $_SESSION['user'];
        $user_id = $user['id'];

        // Check if user has permission to delete positions
        try {
            $this->permission->enforce_permission($user_id, 'position', 'delete');
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki izin untuk menghapus position.']);
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
                // Check if position exists
                $position_check = $this->mymodel->selectWithQuery("SELECT id, name FROM positions WHERE id = '$id'");
                if (empty($position_check)) {
                    $failed_count++;
                    $failed_items[] = "Position ID $id not found";
                    continue;
                }

                $position_name = $position_check[0]['name'];

                // Check if this position is being used by user profiles
                $profiles = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count FROM user_profile WHERE position_id = '$id' AND position_id IS NOT NULL");
                if ($profiles[0]['count'] > 0) {
                    $failed_count++;
                    $failed_items[] = "Position '$position_name' is in use by employees";
                    continue;
                }

                // Check if this position has career progressions
                $progressions = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count FROM career_progressions WHERE (source_position_id = '$id' OR target_position_id = '$id') AND is_active = 1");
                if ($progressions[0]['count'] > 0) {
                    $failed_count++;
                    $failed_items[] = "Position '$position_name' has related career progressions";
                    continue;
                }

                // Delete position
                if ($this->db->delete('positions', array('id' => $id))) {
                    $deleted_count++;
                } else {
                    $failed_count++;
                    $failed_items[] = "Failed to delete position '$position_name'";
                }

            } catch (Exception $e) {
                $failed_count++;
                $failed_items[] = "Error deleting position ID $id: " . $e->getMessage();
            }
        }

        // Complete transaction
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            echo json_encode([
                'success' => false,
                'message' => 'Transaction failed during bulk delete operation.'
            ]);
            return;
        }

        // Return success response
        echo json_encode([
            'success' => true,
            'deleted_count' => $deleted_count,
            'failed_count' => $failed_count,
            'failed_items' => $failed_items,
            'message' => $deleted_count > 0 ? 'Bulk delete completed successfully.' : 'No positions were deleted.'
        ]);
    }

    /**
     * AJAX endpoint to get position details
     * Used for career path builder autocomplete and validation
     */
    public function get_position_details()
    {
        header('Content-Type: application/json');

        $position_id = $_GET['id'] ?? null;

        if (!$position_id) {
            echo json_encode(['success' => false, 'message' => 'Position ID is required']);
            return;
        }

        $position = $this->mymodel->selectWithQuery("
            SELECT
                p.id,
                p.name,
                p.department,
                p.level_id,
                ql.name as level_name
            FROM positions p
            LEFT JOIN quest_levels ql ON p.level_id = ql.id
            WHERE p.id = '$position_id'
            LIMIT 1
        ");

        if (!empty($position)) {
            echo json_encode([
                'success' => true,
                'data' => $position[0]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Position not found'
            ]);
        }
    }

    /**
     * Career Paths Overview Tab Content
     * Displays all positions with their career paths in a visual format
     */
    public function career_paths_overview()
    {
        $data['user'] = $_SESSION['user'];

        // Get all positions with career paths
        $data['positions'] = $this->mymodel->selectWithQuery("
            SELECT
                p.id,
                p.name,
                p.department,
                p.career_paths,
                ql.name as level_name,
                ql.level_order,
                COUNT(up.id) as employee_count
            FROM positions p
            LEFT JOIN quest_levels ql ON p.level_id = ql.id
            LEFT JOIN user_profile up ON p.id = up.position_id
            GROUP BY p.id
            ORDER BY p.department, p.name
        ");

        // Parse career paths JSON for each position
        foreach ($data['positions'] as &$position) {
            $position['paths'] = [];
            if (!empty($position['career_paths'])) {
                $paths_json = json_decode($position['career_paths'], true);
                if (isset($paths_json['next_roles']) && is_array($paths_json['next_roles'])) {
                    $position['paths'] = $paths_json['next_roles'];
                }
            }
            $position['has_paths'] = !empty($position['paths']);
        }

        // Get statistics
        $total_positions = count($data['positions']);
        $positions_with_paths = count(array_filter($data['positions'], function($p) {
            return $p['has_paths'];
        }));

        $data['stats'] = [
            'total_positions' => $total_positions,
            'positions_with_paths' => $positions_with_paths,
            'positions_without_paths' => $total_positions - $positions_with_paths,
            'coverage_percentage' => $total_positions > 0 ? round(($positions_with_paths / $total_positions) * 100, 1) : 0
        ];

        // Load view directly (for tab content)
        $this->load->view("position/career_paths_overview", $data);
    }

    // =====================================================
    // CAREER PROGRESSION MANAGEMENT METHODS
    // =====================================================

    /**
     * Career progression management interface
     */
    public function career_progression()
    {
        $data['user'] = $_SESSION['user'];
        $user_id = $data['user']['id'];
        
        // Check permissions
        $data['can_create'] = $this->permission->check_permission($user_id, 'position', 'create');
        $data['can_edit'] = $this->permission->check_permission($user_id, 'position', 'edit');
        $data['can_delete'] = $this->permission->check_permission($user_id, 'position', 'delete');
        
        $data['title'] = 'Career Progression Management - ' . $this->template->title();
        
        // Get all positions with career progression data
        $data['positions'] = $this->get_positions_with_career_data();
        
        // Get career progression statistics
        $data['progression_stats'] = $this->get_career_progression_stats();
        
        $data['content'] = $this->load->view("position/career_progression", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    /**
     * Career progression tab content (for tab interface)
     */
    public function career_progression_tab()
    {
        $data['user'] = $_SESSION['user'];
        $user_id = $data['user']['id'];
        
        // Check permissions
        $data['can_create'] = $this->permission->check_permission($user_id, 'position', 'create');
        $data['can_edit'] = $this->permission->check_permission($user_id, 'position', 'edit');
        $data['can_delete'] = $this->permission->check_permission($user_id, 'position', 'delete');
        
        // Get all positions with career progression data
        $data['positions'] = $this->get_positions_with_career_data();
        
        // Get career progression statistics
        $data['progression_stats'] = $this->get_career_progression_stats();
        
        // Load view directly without TemplateDashboard
        $this->load->view("position/career_progression", $data);
    }

    /**
     * Get career progression tree for a specific position
     */
    public function career_tree()
    {
        $position_id = $_GET['position_id'] ?? null;
        
        if (!$position_id) {
            echo json_encode(['error' => 'Position ID is required']);
            return;
        }
        
        $career_tree = $this->build_career_tree($position_id);
        echo json_encode($career_tree);
    }

    /**
     * Get all possible career paths from a position (suffix tree traversal)
     */
    public function career_paths()
    {
        $position_id = $_GET['position_id'] ?? null;
        $direction = $_GET['direction'] ?? 'up'; // 'up' for promotion paths, 'down' for reverse
        
        if (!$position_id) {
            echo json_encode(['error' => 'Position ID is required']);
            return;
        }
        
        if ($direction === 'up') {
            $paths = $this->get_promotion_paths($position_id);
        } else {
            $paths = $this->get_reverse_paths($position_id);
        }
        
        echo json_encode(['paths' => $paths]);
    }

    /**
     * Create or update career progression
     */
    public function save_career_progression()
    {
        $user = $_SESSION['user'];
        $data = $_POST;
        
        // Validate required fields
        if (!isset($data['source_position_id'])) {
            echo json_encode(['success' => false, 'message' => 'Source position is required']);
            return;
        }
        
        // Get target position IDs (now an array)
        $target_position_ids = isset($data['target_position_ids']) ? $data['target_position_ids'] : [];
        
        if (empty($target_position_ids)) {
            echo json_encode(['success' => false, 'message' => 'At least one target position must be selected']);
            return;
        }
        
        // Validate numeric fields
        $source_id = intval($data['source_position_id']);
        if (!is_numeric($data['source_position_id']) || $source_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid source position ID']);
            return;
        }
        
        // Validate all target position IDs are numeric
        foreach ($target_position_ids as $target_id) {
            if (!is_numeric($target_id) || intval($target_id) <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid target position ID']);
                return;
            }
        }
        
        // Get source position department for validation
        $source_position = $this->mymodel->selectWithQuery("SELECT department FROM positions WHERE id = '$source_id'");
        if (empty($source_position)) {
            echo json_encode(['success' => false, 'message' => 'Source position not found']);
            return;
        }
        $source_department = $source_position[0]['department'];
        
        // Validate target positions are in the same department OR are executive positions (CEO, etc.)
        foreach ($target_position_ids as $target_id) {
            $target_position = $this->mymodel->selectWithQuery("SELECT department, name FROM positions WHERE id = '$target_id'");
            if (empty($target_position)) {
                echo json_encode(['success' => false, 'message' => 'Target position not found']);
                return;
            }
            
            $target_dept = $target_position[0]['department'];
            $target_name = $target_position[0]['name'];
            
            // Check if target is in same department OR is executive position (no department/null/empty)
            $is_same_department = $target_dept === $source_department;
            $is_executive_position = empty($target_dept) || $target_dept === null || $target_dept === 'No Department';
            
            if (!$is_same_department && !$is_executive_position) {
                echo json_encode(['success' => false, 'message' => 'Cross-department progression not allowed. Position "' . $target_name . '" is not in the same department or an executive position.']);
                return;
            }
            
            // Check for circular dependencies
            if ($this->has_circular_dependency($source_id, $target_id, null)) {
                echo json_encode(['success' => false, 'message' => 'Circular dependency detected in career path to "' . $target_position[0]['name'] . '"']);
                return;
            }
        }
        
        // Start database transaction for multiple inserts
        $this->db->trans_start();
        
        try {
            // Delete existing progressions for this source position (clean slate approach)
            $this->db->delete('career_progressions', ['source_position_id' => $source_id]);
            
            // Create progression record for each target position
            $created_count = 0;
            foreach ($target_position_ids as $target_id) {
                $progression_data = [
                    'source_position_id' => $source_id,
                    'target_position_id' => intval($target_id),
                    'progression_type' => 'direct', // Default since we removed the field
                    'requirements' => !empty($data['requirements']) ? $data['requirements'] : null,
                    'estimated_years' => !empty($data['estimated_months']) ? floatval($data['estimated_months']) / 12 : null, // Convert months to years for database storage
                    'min_performance_rating' => !empty($data['min_performance_rating']) ? floatval($data['min_performance_rating']) : null,
                    'required_quests' => !empty($data['required_quests']) ? json_encode($data['required_quests']) : null,
                    'approval_required' => isset($data['approval_required']) ? 1 : 0,
                    'is_active' => isset($data['is_active']) ? 1 : 0,
                    'created_by' => $user['id'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_by' => $user['id'],
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                if ($this->db->insert('career_progressions', $progression_data)) {
                    $created_count++;
                } else {
                    throw new Exception('Failed to insert career progression');
                }
            }
            
            // Complete transaction
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Transaction failed');
            }
            
            $message = "Successfully created $created_count career progression path(s) within the same department";
            echo json_encode(['success' => true, 'message' => $message, 'created_count' => $created_count]);
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            error_log("Career progression save error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to save career progressions: ' . $e->getMessage()]);
        }
    }

    /**
     * Get career progression data for editing
     */
    public function get_career_progression()
    {
        $progression_id = $_GET['progression_id'] ?? null;
        
        if (!$progression_id) {
            echo json_encode(['success' => false, 'message' => 'Progression ID is required']);
            return;
        }
        
        $progression = $this->mymodel->selectWithQuery("
            SELECT 
                cp.*,
                sp.name as source_position_name,
                sp.department as source_department,
                tp.name as target_position_name,
                tp.department as target_department
            FROM career_progressions cp
            LEFT JOIN positions sp ON cp.source_position_id = sp.id
            LEFT JOIN positions tp ON cp.target_position_id = tp.id
            WHERE cp.id = '$progression_id'
        ");
        
        if (empty($progression)) {
            echo json_encode(['success' => false, 'message' => 'Career progression not found']);
            return;
        }
        
        // Convert estimated_years back to months for display
        $progression[0]['estimated_months'] = $progression[0]['estimated_years'] ? round($progression[0]['estimated_years'] * 12) : null;
        
        echo json_encode(['success' => true, 'data' => $progression[0]]);
    }

    /**
     * Update career progression
     */
    public function update_career_progression()
    {
        $user = $_SESSION['user'];
        $data = $_POST;
        
        // Validate required fields
        if (!isset($data['progression_id']) || !isset($data['source_position_id']) || !isset($data['target_position_id'])) {
            echo json_encode(['success' => false, 'message' => 'Progression ID, source position, and target position are required']);
            return;
        }
        
        $progression_id = intval($data['progression_id']);
        $source_id = intval($data['source_position_id']);
        $target_id = intval($data['target_position_id']);
        
        // Validate numeric fields
        if ($progression_id <= 0 || $source_id <= 0 || $target_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID values']);
            return;
        }
        
        // Check if progression exists
        $existing = $this->mymodel->selectWithQuery("SELECT id FROM career_progressions WHERE id = '$progression_id'");
        if (empty($existing)) {
            echo json_encode(['success' => false, 'message' => 'Career progression not found']);
            return;
        }
        
        // Get source and target position info for validation
        $source_position = $this->mymodel->selectWithQuery("SELECT department, name FROM positions WHERE id = '$source_id'");
        $target_position = $this->mymodel->selectWithQuery("SELECT department, name FROM positions WHERE id = '$target_id'");
        
        if (empty($source_position) || empty($target_position)) {
            echo json_encode(['success' => false, 'message' => 'Source or target position not found']);
            return;
        }
        
        // Validate same department rule
        if ($source_position[0]['department'] !== $target_position[0]['department']) {
            echo json_encode(['success' => false, 'message' => 'Cross-department progression not allowed. Positions must be in the same department.']);
            return;
        }
        
        // Check for circular dependencies (exclude current progression from check)
        if ($this->has_circular_dependency($source_id, $target_id, $progression_id)) {
            echo json_encode(['success' => false, 'message' => 'Circular dependency detected in career path']);
            return;
        }
        
        try {
            $update_data = [
                'source_position_id' => $source_id,
                'target_position_id' => $target_id,
                'requirements' => !empty($data['requirements']) ? $data['requirements'] : null,
                'estimated_years' => !empty($data['estimated_months']) ? floatval($data['estimated_months']) / 12 : null, // Convert months to years
                'min_performance_rating' => !empty($data['min_performance_rating']) ? floatval($data['min_performance_rating']) : null,
                'approval_required' => isset($data['approval_required']) ? 1 : 0,
                'is_active' => isset($data['is_active']) ? 1 : 0,
                'updated_by' => $user['id'],
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $result = $this->db->update('career_progressions', $update_data, ['id' => $progression_id]);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Career progression updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update career progression']);
            }
            
        } catch (Exception $e) {
            error_log("Career progression update error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to update career progression: ' . $e->getMessage()]);
        }
    }

    /**
     * Get all career progressions for management table
     */
    public function get_all_career_progressions()
    {
        try {
            $progressions = $this->mymodel->selectWithQuery("
                SELECT 
                    cp.*,
                    sp.name as source_position_name,
                    sp.department as source_department,
                    tp.name as target_position_name,
                    tp.department as target_department,
                    ROUND(cp.estimated_years * 12) as estimated_months
                FROM career_progressions cp
                LEFT JOIN positions sp ON cp.source_position_id = sp.id
                LEFT JOIN positions tp ON cp.target_position_id = tp.id
                ORDER BY sp.department, sp.name, tp.name
            ");
            
            echo json_encode(['success' => true, 'data' => $progressions]);
        } catch (Exception $e) {
            error_log("Get career progressions error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load career progressions']);
        }
    }

    /**
     * Delete career progression
     */
    public function delete_career_progression()
    {
        $progression_id = $_POST['progression_id'] ?? null;

        if (!$progression_id) {
            echo json_encode(['success' => false, 'message' => 'Progression ID is required']);
            return;
        }

        // Check if progression is used in history
        $history_count = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count FROM career_progression_history WHERE progression_id = '$progression_id'");
        if ($history_count[0]['count'] > 0) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete progression with history records']);
            return;
        }

        $result = $this->db->delete('career_progressions', ['id' => $progression_id]);
        echo json_encode(['success' => $result, 'message' => 'Career progression deleted successfully']);
    }

    /**
     * Bulk delete career progressions
     */
    public function bulk_delete_career_progressions()
    {
        // Require AJAX request
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $ids = $this->input->post('ids');

        if (empty($ids) || !is_array($ids)) {
            echo json_encode([
                'success' => false,
                'message' => 'No career progressions selected for deletion'
            ]);
            return;
        }

        // Sanitize IDs
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, function($id) { return $id > 0; });

        if (empty($ids)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid career progression IDs'
            ]);
            return;
        }

        $this->db->trans_start();

        $deleted_count = 0;
        $failed_count = 0;
        $failed_reasons = [];

        foreach ($ids as $progression_id) {
            try {
                // Check if progression exists
                $progression = $this->mymodel->selectWithQuery("SELECT id, source_position_id, target_position_id FROM career_progressions WHERE id = '$progression_id'");
                if (empty($progression)) {
                    $failed_count++;
                    $failed_reasons[] = "Career progression ID $progression_id not found";
                    continue;
                }

                // Check if progression is used in history
                $history_count = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count FROM career_progression_history WHERE progression_id = '$progression_id'");
                if ($history_count[0]['count'] > 0) {
                    $failed_count++;
                    $failed_reasons[] = "Career progression ID $progression_id has history records";
                    continue;
                }

                // Delete the career progression
                $result = $this->db->delete('career_progressions', ['id' => $progression_id]);

                if ($result) {
                    $deleted_count++;
                } else {
                    $failed_count++;
                    $failed_reasons[] = "Failed to delete career progression ID $progression_id";
                }

            } catch (Exception $e) {
                $failed_count++;
                $failed_reasons[] = "Error deleting career progression ID $progression_id: " . $e->getMessage();
            }
        }

        $this->db->trans_complete();

        // Prepare response
        $total_requested = count($ids);
        $success = $deleted_count > 0;

        $message = '';
        if ($deleted_count > 0) {
            $message = "$deleted_count career progression(s) berhasil dihapus";
        }
        if ($failed_count > 0) {
            if (!empty($message)) $message .= '. ';
            $message .= "$failed_count career progression(s) gagal dihapus";
        }

        echo json_encode([
            'success' => $success,
            'message' => $message,
            'deleted_count' => $deleted_count,
            'failed_count' => $failed_count,
            'total_requested' => $total_requested,
            'failed_reasons' => $failed_reasons
        ]);
    }

    /**
     * Get career progression history for reporting
     */
    public function career_history()
    {
        $user_id = $_GET['user_id'] ?? null;
        $position_id = $_GET['position_id'] ?? null;
        $limit = $_GET['limit'] ?? 50;
        
        $where_conditions = ['1=1'];
        
        if ($user_id) {
            $where_conditions[] = "cph.user_id = '$user_id'";
        }
        
        if ($position_id) {
            $where_conditions[] = "(cph.from_position_id = '$position_id' OR cph.to_position_id = '$position_id')";
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $query = "
            SELECT 
                cph.*,
                u.full_name as employee_name,
                fp.name as from_position_name,
                tp.name as to_position_name,
                approver.full_name as approver_name,
                cp.progression_type
            FROM career_progression_history cph
            LEFT JOIN user u ON cph.user_id = u.id
            LEFT JOIN positions fp ON cph.from_position_id = fp.id
            LEFT JOIN positions tp ON cph.to_position_id = tp.id
            LEFT JOIN user approver ON cph.approved_by = approver.id
            LEFT JOIN career_progressions cp ON cph.progression_id = cp.id
            WHERE $where_clause
            ORDER BY cph.promotion_date DESC
            LIMIT $limit
        ";
        
        $history = $this->mymodel->selectWithQuery($query);
        echo json_encode(['history' => $history]);
    }

    // =====================================================
    // PRIVATE HELPER METHODS FOR CAREER PROGRESSION
    // =====================================================

    /**
     * Build complete career tree from a position (suffix tree structure)
     */
    private function build_career_tree($position_id, $visited = [])
    {
        // Prevent infinite loops
        if (in_array($position_id, $visited)) {
            return null;
        }
        
        $visited[] = $position_id;
        
        // Get current position details
        $position = $this->mymodel->selectWithQuery("
            SELECT p.*, ql.name as level_name, ql.level_order
            FROM positions p 
            LEFT JOIN quest_levels ql ON p.level_id = ql.id 
            WHERE p.id = '$position_id'
        ");
        
        if (empty($position)) {
            return null;
        }
        
        $position = $position[0];
        
        // Get all possible next positions (suffix tree branches)
        $next_positions = $this->mymodel->selectWithQuery("
            SELECT 
                cp.*,
                tp.name as target_position_name,
                tp.department as target_department,
                tql.name as target_level_name
            FROM career_progressions cp
            JOIN positions tp ON cp.target_position_id = tp.id
            LEFT JOIN quest_levels tql ON tp.level_id = tql.id
            WHERE cp.source_position_id = '$position_id' 
            AND cp.is_active = 1
            ORDER BY cp.progression_type, tp.name
        ");
        
        // Build tree structure recursively
        $branches = [];
        foreach ($next_positions as $next_pos) {
            $branch = $this->build_career_tree($next_pos['target_position_id'], $visited);
            if ($branch) {
                $branches[] = [
                    'progression' => $next_pos,
                    'subtree' => $branch
                ];
            }
        }
        
        return [
            'position' => $position,
            'branches' => $branches,
            'depth' => count($visited) - 1
        ];
    }

    /**
     * Get all promotion paths from a position using suffix tree traversal
     */
    private function get_promotion_paths($position_id, $current_path = [], $all_paths = [])
    {
        // Get direct next positions
        $next_positions = $this->mymodel->selectWithQuery("
            SELECT 
                cp.*,
                tp.name as target_position_name,
                tp.department as target_department
            FROM career_progressions cp
            JOIN positions tp ON cp.target_position_id = tp.id
            WHERE cp.source_position_id = '$position_id' 
            AND cp.is_active = 1
        ");
        
        // If no next positions, this is a terminal node
        if (empty($next_positions)) {
            if (!empty($current_path)) {
                $all_paths[] = $current_path;
            }
            return $all_paths;
        }
        
        // Recursively explore each branch
        foreach ($next_positions as $next_pos) {
            $new_path = $current_path;
            $new_path[] = [
                'position_id' => $next_pos['target_position_id'],
                'position_name' => $next_pos['target_position_name'],
                'department' => $next_pos['target_department'],
                'progression_type' => $next_pos['progression_type'],
                'estimated_months' => $next_pos['estimated_years'] ? round($next_pos['estimated_years'] * 12) : null
            ];
            
            $all_paths = $this->get_promotion_paths($next_pos['target_position_id'], $new_path, $all_paths);
        }
        
        return $all_paths;
    }

    /**
     * Get reverse paths (positions that can lead to this position)
     */
    private function get_reverse_paths($position_id)
    {
        return $this->mymodel->selectWithQuery("
            SELECT 
                cp.*,
                sp.name as source_position_name,
                sp.department as source_department,
                sql.name as source_level_name
            FROM career_progressions cp
            JOIN positions sp ON cp.source_position_id = sp.id
            LEFT JOIN quest_levels sql ON sp.level_id = sql.id
            WHERE cp.target_position_id = '$position_id' 
            AND cp.is_active = 1
            ORDER BY cp.progression_type, sp.name
        ");
    }

    /**
     * Check for circular dependencies in career progressions
     */
    private function has_circular_dependency($source_id, $target_id, $exclude_progression_id = null, $visited = [])
    {
        if ($source_id == $target_id) {
            return true;
        }
        
        if (in_array($target_id, $visited)) {
            return true;
        }
        
        $visited[] = $target_id;
        
        // Check if target position leads back to source
        $exclude_clause = $exclude_progression_id ? " AND id != '$exclude_progression_id'" : "";
        $next_positions = $this->mymodel->selectWithQuery("
            SELECT target_position_id 
            FROM career_progressions 
            WHERE source_position_id = '$target_id' AND is_active = 1 $exclude_clause
        ");
        
        foreach ($next_positions as $next_pos) {
            if ($this->has_circular_dependency($source_id, $next_pos['target_position_id'], $exclude_progression_id, $visited)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get positions with career progression data
     */
    private function get_positions_with_career_data()
    {
        return $this->mymodel->selectWithQuery("
            SELECT 
                p.*,
                COALESCE(ql.name, 'No Level') as level_name,
                COALESCE(ql.level_order, 999) as level_order,
                COUNT(DISTINCT cp_out.id) as outgoing_paths,
                COUNT(DISTINCT cp_in.id) as incoming_paths,
                COUNT(DISTINCT up.id) as employee_count
            FROM positions p
            LEFT JOIN quest_levels ql ON p.level_id = ql.id
            LEFT JOIN career_progressions cp_out ON p.id = cp_out.source_position_id AND cp_out.is_active = 1
            LEFT JOIN career_progressions cp_in ON p.id = cp_in.target_position_id AND cp_in.is_active = 1
            LEFT JOIN user_profile up ON p.id = up.position_id
            GROUP BY p.id, p.name, ql.name, ql.level_order
            ORDER BY p.department, ql.level_order, p.position_order, p.name
        ");
    }

    /**
     * Get career progression statistics
     */
    private function get_career_progression_stats()
    {
        $stats = [];
        
        // Total progressions
        $total = $this->mymodel->selectWithQuery("SELECT COUNT(*) as count FROM career_progressions WHERE is_active = 1");
        $stats['total_progressions'] = $total[0]['count'];
        
        // Progressions by type
        $by_type = $this->mymodel->selectWithQuery("
            SELECT progression_type, COUNT(*) as count 
            FROM career_progressions 
            WHERE is_active = 1 
            GROUP BY progression_type
        ");
        $stats['by_type'] = $by_type;
        
        // Recent promotions
        $recent = $this->mymodel->selectWithQuery("
            SELECT COUNT(*) as count 
            FROM career_progression_history 
            WHERE promotion_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stats['recent_promotions'] = $recent[0]['count'];
        
        return $stats;
    }

    /**
     * Get existing departments for autocomplete
     */
    public function get_departments()
    {
        $departments = $this->mymodel->selectWithQuery("
            SELECT DISTINCT department 
            FROM positions 
            WHERE department IS NOT NULL AND department != ''
            ORDER BY department ASC
        ");
        
        $department_list = [];
        foreach ($departments as $dept) {
            $department_list[] = $dept['department'];
        }
        
        echo json_encode($department_list);
    }

    /**
     * Get position statistics for dashboard
     */
    public function position_stats()
    {
        $stats = [];
        
        // Total positions
        $total = $this->mymodel->selectWithQuery("SELECT COUNT(*) as count FROM positions");
        $stats['total_positions'] = $total[0]['count'];
        
        // Positions by department
        $by_dept = $this->mymodel->selectWithQuery("
            SELECT department, COUNT(*) as count 
            FROM positions 
            WHERE department IS NOT NULL AND department != ''
            GROUP BY department 
            ORDER BY count DESC
        ");
        $stats['by_department'] = $by_dept;
        
        // Leadership positions
        $leadership = $this->mymodel->selectWithQuery("
            SELECT COUNT(*) as count 
            FROM positions 
            WHERE is_leadership = 1
        ");
        $stats['leadership_positions'] = $leadership[0]['count'];
        
        // Positions with career paths
        $with_paths = $this->mymodel->selectWithQuery("
            SELECT COUNT(DISTINCT p.id) as count
            FROM positions p
            JOIN career_progressions cp ON p.id = cp.source_position_id
            WHERE cp.is_active = 1
        ");
        $stats['positions_with_paths'] = $with_paths[0]['count'];
        
        echo json_encode($stats);
    }

    /**
     * Bulk create positions from template
     */
    public function bulk_create_positions()
    {
        $user = $_SESSION['user'];
        $template_data = $_POST['template_data'] ?? null;
        
        if (!$template_data) {
            echo json_encode(['success' => false, 'message' => 'Template data required']);
            return;
        }
        
        // Decode template data
        $positions = json_decode($template_data, true);
        if (!$positions) {
            echo json_encode(['success' => false, 'message' => 'Invalid template data']);
            return;
        }
        
        $this->db->trans_start();
        $created_count = 0;
        $errors = [];
        
        try {
            foreach ($positions as $position_data) {
                // Add common fields
                $position_data['created_by'] = $user['id'];
                $position_data['created_at'] = date('Y-m-d H:i:s');
                
                // Set defaults
                $position_data['has_lead_role'] = $position_data['has_lead_role'] ?? 0;
                $position_data['is_leadership'] = $position_data['is_leadership'] ?? 0;
                $position_data['position_order'] = $position_data['position_order'] ?? 0;
                
                if ($this->db->insert('positions', $position_data)) {
                    $created_count++;
                } else {
                    $errors[] = "Failed to create position: " . ($position_data['name'] ?? 'Unknown');
                }
            }
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                echo json_encode(['success' => false, 'message' => 'Transaction failed']);
                return;
            }
            
            $message = "Successfully created {$created_count} positions";
            if (!empty($errors)) {
                $message .= ". Errors: " . implode(', ', $errors);
            }
            
            echo json_encode(['success' => true, 'message' => $message, 'created_count' => $created_count]);
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Get career tree data for D3.js visualization
     * Returns hierarchical data structure compatible with D3.js tree layouts
     */
    /**
     * Enhanced test method to check database connectivity and table structure
     */
    public function test_db()
    {
        header('Content-Type: application/json');
        $results = [];

        try {
            // Test 1: Check if positions table exists
            try {
                $positions = $this->mymodel->selectWithQuery("SELECT COUNT(*) as count FROM positions");
                $results['positions_table'] = [
                    'exists' => true,
                    'count' => $positions[0]['count']
                ];
            } catch (Exception $e) {
                $results['positions_table'] = [
                    'exists' => false,
                    'error' => $e->getMessage()
                ];
            }

            // Test 2: Check if quest_levels table exists
            try {
                $levels = $this->mymodel->selectWithQuery("SELECT COUNT(*) as count FROM quest_levels");
                $results['quest_levels_table'] = [
                    'exists' => true,
                    'count' => $levels[0]['count']
                ];
            } catch (Exception $e) {
                $results['quest_levels_table'] = [
                    'exists' => false,
                    'error' => $e->getMessage()
                ];
            }

            // Test 3: Check if career_progressions table exists
            try {
                $progressions = $this->mymodel->selectWithQuery("SELECT COUNT(*) as count FROM career_progressions");
                $results['career_progressions_table'] = [
                    'exists' => true,
                    'count' => $progressions[0]['count']
                ];
            } catch (Exception $e) {
                $results['career_progressions_table'] = [
                    'exists' => false,
                    'error' => $e->getMessage()
                ];
            }

            // Test 4: Test basic database connection
            $results['database_connection'] = [
                'connected' => true,
                'database_name' => $this->db->database
            ];

            echo json_encode(['success' => true, 'results' => $results]);

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'results' => $results
            ]);
        }
    }

    /**
     * Create missing database tables for career tree functionality
     */
    public function create_missing_tables()
    {
        header('Content-Type: application/json');
        $created = [];
        $errors = [];

        try {
            $this->db->trans_start();

            // Create positions table if it doesn't exist
            try {
                $this->mymodel->selectWithQuery("SELECT 1 FROM positions LIMIT 1");
            } catch (Exception $e) {
                $sql = "CREATE TABLE positions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    department VARCHAR(255) DEFAULT NULL,
                    position_order INT DEFAULT 0,
                    level_id INT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_position_order (position_order),
                    INDEX idx_level_id (level_id)
                )";

                if ($this->db->query($sql)) {
                    $created[] = 'positions';
                } else {
                    $errors[] = 'Failed to create positions table';
                }
            }

            // Create quest_levels table if it doesn't exist
            try {
                $this->mymodel->selectWithQuery("SELECT 1 FROM quest_levels LIMIT 1");
            } catch (Exception $e) {
                $sql = "CREATE TABLE quest_levels (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    level_order INT DEFAULT 1,
                    description TEXT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_level_order (level_order)
                )";

                if ($this->db->query($sql)) {
                    $created[] = 'quest_levels';

                    // Insert default quest levels
                    $default_levels = [
                        ['name' => 'Junior', 'level_order' => 1, 'description' => 'Entry level position'],
                        ['name' => 'Intermediate', 'level_order' => 2, 'description' => 'Mid-level position'],
                        ['name' => 'Senior', 'level_order' => 3, 'description' => 'Senior level position']
                    ];

                    foreach ($default_levels as $level) {
                        $this->db->insert('quest_levels', $level);
                    }
                } else {
                    $errors[] = 'Failed to create quest_levels table';
                }
            }

            // Create career_progressions table if it doesn't exist
            try {
                $this->mymodel->selectWithQuery("SELECT 1 FROM career_progressions LIMIT 1");
            } catch (Exception $e) {
                $sql = "CREATE TABLE career_progressions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    source_position_id INT NOT NULL,
                    target_position_id INT NOT NULL,
                    progression_type ENUM('promotion', 'lateral', 'development') DEFAULT 'promotion',
                    estimated_years DECIMAL(3,1) DEFAULT NULL,
                    requirements TEXT DEFAULT NULL,
                    is_active TINYINT(1) DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_source_position (source_position_id),
                    INDEX idx_target_position (target_position_id),
                    UNIQUE KEY unique_progression (source_position_id, target_position_id)
                )";

                if ($this->db->query($sql)) {
                    $created[] = 'career_progressions';
                } else {
                    $errors[] = 'Failed to create career_progressions table';
                }
            }

            // Add some sample data if tables were created
            if (in_array('positions', $created) && in_array('quest_levels', $created)) {
                $sample_positions = [
                    ['name' => 'Junior Developer', 'department' => 'Engineering', 'position_order' => 100, 'level_id' => 1],
                    ['name' => 'Senior Developer', 'department' => 'Engineering', 'position_order' => 300, 'level_id' => 3],
                    ['name' => 'Team Lead', 'department' => 'Engineering', 'position_order' => 500, 'level_id' => 3],
                    ['name' => 'Marketing Specialist', 'department' => 'Marketing', 'position_order' => 200, 'level_id' => 2],
                    ['name' => 'Marketing Manager', 'department' => 'Marketing', 'position_order' => 400, 'level_id' => 3]
                ];

                foreach ($sample_positions as $position) {
                    $this->db->insert('positions', $position);
                }
            }

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Transaction failed during table creation');
            }

            echo json_encode([
                'success' => true,
                'created_tables' => $created,
                'errors' => $errors,
                'message' => count($created) > 0 ?
                    'Successfully created ' . count($created) . ' table(s): ' . implode(', ', $created) :
                    'All required tables already exist'
            ]);

        } catch (Exception $e) {
            $this->db->trans_rollback();
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'created_tables' => $created,
                'errors' => $errors
            ]);
        }
    }

    public function get_career_tree_data()
    {
        try {
            // Simplified query to avoid potential issues
            $positions = $this->mymodel->selectWithQuery("
                SELECT
                    p.id,
                    p.name,
                    COALESCE(p.department, 'No Department') as department,
                    0 as is_leadership,
                    0 as has_lead_role,
                    COALESCE(p.position_order, 0) as position_order,
                    COALESCE(p.level_id, 0) as level_id,
                    COALESCE(ql.name, 'No Level') as level_name,
                    COALESCE(ql.level_order, 999) as level_order,
                    0 as employee_count
                FROM positions p
                LEFT JOIN quest_levels ql ON p.level_id = ql.id
                ORDER BY p.position_order DESC, p.name
            ");

            if (empty($positions)) {
                // Return empty but valid response instead of throwing exception
                $response = [
                    'success' => true,
                    'data' => [],
                    'metadata' => [
                        'total_nodes' => 0,
                        'total_positions' => 0,
                        'total_progressions' => 0,
                        'departments' => [],
                        'levels' => [],
                        'generated_at' => date('Y-m-d H:i:s')
                    ]
                ];

                header('Content-Type: application/json');
                echo json_encode($response);
                return;
            }

            // Get progressions with simpler query
            $progressions = [];
            try {
                $progressions = $this->mymodel->selectWithQuery("
                    SELECT
                        source_position_id,
                        target_position_id
                    FROM career_progressions
                    WHERE is_active = 1
                ");
            } catch (Exception $prog_error) {
                // Career progressions table might not exist, that's okay
                $progressions = [];
            }

            // Calculate progression counts manually (simpler approach)
            foreach ($positions as &$position) {
                $position_id = $position['id'];
                $outgoing = 0;
                $incoming = 0;

                foreach ($progressions as $prog) {
                    if ($prog['source_position_id'] == $position_id) {
                        $outgoing++;
                    }
                    if ($prog['target_position_id'] == $position_id) {
                        $incoming++;
                    }
                }

                $position['outgoing_paths'] = $outgoing;
                $position['incoming_paths'] = $incoming;
            }

            // Extract metadata
            $departments = array_values(array_unique(array_column($positions, 'department')));
            $levels = array_values(array_unique(array_filter(array_column($positions, 'level_name'))));

            $metadata = [
                'total_nodes' => count($positions),
                'total_positions' => count($positions),
                'total_progressions' => count($progressions),
                'departments' => $departments,
                'levels' => $levels,
                'generated_at' => date('Y-m-d H:i:s')
            ];

            // Build hierarchical structure for D3.js visualization
            $hierarchical_data = $this->build_d3_hierarchy($positions, $progressions);

            // Return both flat and hierarchical data for compatibility
            $response = [
                'success' => true,
                'data' => $hierarchical_data,
                'flat_data' => $positions, // Keep flat data for fallback
                'metadata' => $metadata
            ];

            // Set JSON headers
            header('Content-Type: application/json');
            echo json_encode($response);

        } catch (Exception $e) {
            // Enhanced error logging for debugging
            $error_details = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => date('Y-m-d H:i:s'),
                'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ];

            error_log("Career tree data error: " . json_encode($error_details));

            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Failed to load career tree data: ' . $e->getMessage(),
                'error_code' => 'CAREER_TREE_ERROR',
                'debug_info' => [
                    'error_message' => $e->getMessage(),
                    'error_file' => basename($e->getFile()),
                    'error_line' => $e->getLine(),
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        }
    }

    /**
     * Build D3.js hierarchical structure from flat position data
     */
    private function build_d3_hierarchy($positions, $progressions)
    {
        if (empty($positions)) {
            return [
                'name' => 'Organization',
                'children' => []
            ];
        }

        // Create a map of positions for easy lookup
        $position_map = [];
        foreach ($positions as $position) {
            $position_map[$position['id']] = [
                'id' => $position['id'],
                'name' => $position['name'],
                'department' => $position['department'],
                'level_name' => $position['level_name'],
                'position_order' => $position['position_order'],
                'level_order' => $position['level_order'],
                'outgoing_paths' => $position['outgoing_paths'],
                'incoming_paths' => $position['incoming_paths'],
                'children' => []
            ];
        }

        // Build hierarchy based on progressions
        $root_positions = [];
        $child_position_ids = [];

        // First, identify parent-child relationships from progressions
        foreach ($progressions as $progression) {
            $source_id = $progression['source_position_id'];
            $target_id = $progression['target_position_id'];

            if (isset($position_map[$source_id]) && isset($position_map[$target_id])) {
                // Add target as child of source
                $position_map[$source_id]['children'][] = $position_map[$target_id];
                $child_position_ids[] = $target_id;
            }
        }

        // Find root positions (positions with no incoming progressions or high position_order)
        foreach ($position_map as $id => $position) {
            if (!in_array($id, $child_position_ids) || $position['position_order'] >= 500) {
                $root_positions[] = $position;
            }
        }

        // If no clear hierarchy, create one based on department and level
        if (empty($root_positions) || count($root_positions) == count($positions)) {
            return $this->build_department_hierarchy($positions);
        }

        // Sort root positions by position_order (descending)
        usort($root_positions, function($a, $b) {
            return $b['position_order'] - $a['position_order'];
        });

        return [
            'name' => 'Organization',
            'children' => $root_positions
        ];
    }

    /**
     * Build hierarchy grouped by department as fallback
     */
    private function build_department_hierarchy($positions)
    {
        $departments = [];

        foreach ($positions as $position) {
            $dept_name = $position['department'];
            if (!isset($departments[$dept_name])) {
                $departments[$dept_name] = [
                    'name' => $dept_name,
                    'children' => []
                ];
            }

            $departments[$dept_name]['children'][] = [
                'id' => $position['id'],
                'name' => $position['name'],
                'department' => $position['department'],
                'level_name' => $position['level_name'],
                'position_order' => $position['position_order'],
                'level_order' => $position['level_order'],
                'outgoing_paths' => $position['outgoing_paths'],
                'incoming_paths' => $position['incoming_paths'],
                'children' => []
            ];
        }

        // Sort positions within each department by level_order and position_order
        foreach ($departments as &$dept) {
            usort($dept['children'], function($a, $b) {
                if ($a['level_order'] == $b['level_order']) {
                    return $b['position_order'] - $a['position_order'];
                }
                return $a['level_order'] - $b['level_order'];
            });
        }

        return [
            'name' => 'Organization',
            'children' => array_values($departments)
        ];
    }

    /**
     * Create D3.js compatible hierarchy but also work with fallback
     */
    private function create_d3js_hierarchy($positions, $progressions)
    {
        // For compatibility with both D3.js and fallback, return positions array
        // D3.js can handle flat data and build hierarchy, fallback uses it directly

        // If no positions, return empty structure
        if (empty($positions)) {
            return [
                'name' => 'Organization',
                'children' => []
            ];
        }

        // Return flat array that both D3.js and fallback can use
        // D3.js CareerTreeVisualization will convert this to hierarchical structure
        // Fallback will render it as a simple list
        return $positions;
    }

    /**
     * Calculate outgoing and incoming progression counts for each position
     */
    private function calculate_progression_counts($positions, $progressions)
    {
        $progression_counts = [];
        
        // Initialize counts
        foreach ($positions as $position) {
            $progression_counts[$position['id']] = [
                'outgoing_paths' => 0,
                'incoming_paths' => 0
            ];
        }
        
        // Count progressions
        foreach ($progressions as $progression) {
            $source_id = $progression['source_position_id'];
            $target_id = $progression['target_position_id'];
            
            if (isset($progression_counts[$source_id])) {
                $progression_counts[$source_id]['outgoing_paths']++;
            }
            if (isset($progression_counts[$target_id])) {
                $progression_counts[$target_id]['incoming_paths']++;
            }
        }
        
        // Add counts to positions array
        foreach ($positions as &$position) {
            $id = $position['id'];
            $position['outgoing_paths'] = $progression_counts[$id]['outgoing_paths'];
            $position['incoming_paths'] = $progression_counts[$id]['incoming_paths'];
        }
        
        return $positions;
    }

    /**
     * Filter positions to only show those with career paths
     * Keeps positions that have at least one career path (incoming or outgoing)
     * Also preserves high-level executive positions for organizational structure
     */
    private function filter_positions_with_career_paths($positions)
    {
        if (empty($positions)) {
            return $positions;
        }

        // Find the maximum position_order to identify executive levels
        $max_position_order = max(array_column($positions, 'position_order'));
        $executive_threshold = $max_position_order >= 2 ? $max_position_order - 1 : 0;

        // Filter positions that have career paths OR are high-level executives
        $filtered_positions = array_filter($positions, function($position) use ($executive_threshold) {
            $has_career_paths = $position['outgoing_paths'] > 0 || $position['incoming_paths'] > 0;
            $is_executive = $position['position_order'] >= $executive_threshold;
            
            return $has_career_paths || $is_executive;
        });

        // If filtering results in too few positions (less than 25% of original), 
        // fall back to showing all positions to maintain visualization integrity
        $original_count = count($positions);
        $filtered_count = count($filtered_positions);
        
        if ($filtered_count < ($original_count * 0.25) && $original_count > 4) {
            error_log("Career tree filter: Filtered count ($filtered_count) too low, showing all positions ($original_count)");
            return $positions; // Return all positions
        }

        // Re-index the array to avoid gaps in array keys
        return array_values($filtered_positions);
    }

    /**
     * Create career progression-based hierarchy using actual progression relationships
     * This is the CORRECT way to build the tree based on career paths
     */
    private function create_career_progression_hierarchy($positions, $progressions)
    {
        if (empty($positions)) {
            return ['name' => 'No Positions', 'type' => 'empty', 'children' => []];
        }

        // Create position lookup map
        $position_map = [];
        foreach ($positions as $position) {
            $position_map[$position['id']] = [
                'id' => $position['id'],
                'name' => $position['name'],
                'type' => 'position',
                'department' => $position['department'],
                'department_label' => $position['department'],
                'position_order' => (int)$position['position_order'],
                'level_name' => $position['level_name'],
                'is_leadership' => (bool)$position['is_leadership'],
                'has_lead_role' => (bool)$position['has_lead_role'],
                'employee_count' => (int)$position['employee_count'],
                'outgoing_paths' => (int)$position['outgoing_paths'],
                'incoming_paths' => (int)$position['incoming_paths'],
                'children' => []
            ];
        }

        // Build parent-child relationships based on career progressions
        $children_map = []; // track which positions are children
        foreach ($progressions as $progression) {
            $source_id = $progression['source_position_id'];
            $target_id = $progression['target_position_id'];
            
            if (isset($position_map[$source_id]) && isset($position_map[$target_id])) {
                // In career progression, source is the LOWER position, target is HIGHER
                // So target should be parent of source in the tree
                $position_map[$target_id]['children'][] = &$position_map[$source_id];
                $children_map[$source_id] = true; // mark source as a child
                
                // Add progression metadata
                $position_map[$source_id]['progression_to'] = [
                    'target_id' => $target_id,
                    'target_name' => $progression['target_name'],
                    'progression_type' => $progression['progression_type'] ?? 'promotion'
                ];
            }
        }

        // Find root positions (positions that are not children of any other position)
        $root_positions = [];
        foreach ($position_map as $id => $position) {
            if (!isset($children_map[$id])) {
                // This position has no incoming progressions, it's a root
                $root_positions[] = $position;
            }
        }

        // If no clear roots found, use highest position_order as root
        if (empty($root_positions)) {
            $max_order = max(array_column($positions, 'position_order'));
            foreach ($position_map as $position) {
                if ($position['position_order'] == $max_order) {
                    $root_positions[] = $position;
                }
            }
        }

        // Create final tree structure
        if (count($root_positions) == 1) {
            // Single root
            return $root_positions[0];
        } else if (count($root_positions) > 1) {
            // Multiple roots - create virtual root
            return [
                'id' => 'org_root',
                'name' => 'Organization',
                'type' => 'org_root',
                'department' => 'Organization',
                'department_label' => 'Executive Level',
                'position_order' => max(array_column($root_positions, 'position_order')),
                'level_name' => 'Executive',
                'is_leadership' => true,
                'has_lead_role' => true,
                'employee_count' => 0,
                'outgoing_paths' => 0,
                'incoming_paths' => 0,
                'children' => $root_positions
            ];
        } else {
            // No roots found - return the highest order position
            $highest_position = null;
            $max_order = -1;
            foreach ($position_map as $position) {
                if ($position['position_order'] > $max_order) {
                    $max_order = $position['position_order'];
                    $highest_position = $position;
                }
            }
            return $highest_position ?: ['name' => 'No Valid Hierarchy', 'type' => 'error', 'children' => []];
        }
    }

    /**
     * Create position_order-based hierarchy with department labels
     * Root is the position(s) with highest position_order
     */
    private function create_position_order_hierarchy($positions)
    {
        if (empty($positions)) {
            return ['name' => 'No Positions', 'type' => 'empty', 'children' => []];
        }

        // Group positions by position_order
        $order_groups = [];
        foreach ($positions as $position) {
            $order = (int)$position['position_order'];
            if (!isset($order_groups[$order])) {
                $order_groups[$order] = [];
            }
            $order_groups[$order][] = $position;
        }

        // Sort by position_order descending (highest first)
        krsort($order_groups);
        
        // Get the highest position_order (root level)
        $highest_order = array_key_first($order_groups);
        $root_positions = $order_groups[$highest_order];
        
        // Create the tree structure
        if (count($root_positions) == 1) {
            // Single root position
            $root_data = $root_positions[0];
            $tree_data = [
                'id' => $root_data['id'],
                'name' => $root_data['name'],
                'type' => 'position',
                'department' => $root_data['department'],
                'department_label' => $root_data['department'], // Add department label
                'position_order' => (int)$root_data['position_order'],
                'level_name' => $root_data['level_name'],
                'is_leadership' => (bool)$root_data['is_leadership'],
                'has_lead_role' => (bool)$root_data['has_lead_role'],
                'employee_count' => (int)$root_data['employee_count'],
                'children' => $this->build_hierarchy_children($order_groups, $highest_order)
            ];
        } else {
            // Multiple root positions - create virtual root
            $tree_data = [
                'id' => 'root_level_' . $highest_order,
                'name' => 'Organization (Level ' . $highest_order . ')',
                'type' => 'root_group',
                'department' => 'Organization',
                'department_label' => 'Multiple Departments',
                'position_order' => $highest_order,
                'level_name' => 'Root Level',
                'is_leadership' => true,
                'has_lead_role' => true,
                'employee_count' => 0,
                'children' => $this->create_same_level_branches($root_positions, $order_groups, $highest_order)
            ];
        }

        return $tree_data;
    }

    /**
     * Build hierarchical children based on position_order levels
     */
    private function build_hierarchy_children($order_groups, $current_order)
    {
        $children = [];
        
        // Get the next lower position_order level
        foreach ($order_groups as $order => $positions) {
            if ($order < $current_order) {
                // Create children for this level
                foreach ($positions as $position) {
                    $child_node = [
                        'id' => $position['id'],
                        'name' => $position['name'],
                        'type' => 'position',
                        'department' => $position['department'],
                        'department_label' => $position['department'],
                        'position_order' => (int)$position['position_order'],
                        'level_name' => $position['level_name'],
                        'is_leadership' => (bool)$position['is_leadership'],
                        'has_lead_role' => (bool)$position['has_lead_role'],
                        'employee_count' => (int)$position['employee_count'],
                        'children' => $this->build_hierarchy_children($order_groups, $order)
                    ];
                    $children[] = $child_node;
                }
                break; // Only get the immediate next level
            }
        }

        return $children;
    }

    /**
     * Create separate branches for positions with same name and position_order
     */
    private function create_same_level_branches($positions, $order_groups, $current_order)
    {
        $branches = [];
        
        // Group positions by name and position_order for branch detection
        $position_groups = [];
        foreach ($positions as $position) {
            $key = $position['name'] . '_' . $position['position_order'];
            if (!isset($position_groups[$key])) {
                $position_groups[$key] = [];
            }
            $position_groups[$key][] = $position;
        }

        // Create branches
        foreach ($position_groups as $group_key => $same_positions) {
            if (count($same_positions) == 1) {
                // Single position
                $pos = $same_positions[0];
                $branch = [
                    'id' => $pos['id'],
                    'name' => $pos['name'],
                    'type' => 'position',
                    'department' => $pos['department'],
                    'department_label' => $pos['department'],
                    'position_order' => (int)$pos['position_order'],
                    'level_name' => $pos['level_name'],
                    'is_leadership' => (bool)$pos['is_leadership'],
                    'has_lead_role' => (bool)$pos['has_lead_role'],
                    'employee_count' => (int)$pos['employee_count'],
                    'children' => $this->build_hierarchy_children($order_groups, $current_order)
                ];
                $branches[] = $branch;
            } else {
                // Multiple positions with same name + order = separate branches at same level
                foreach ($same_positions as $index => $pos) {
                    $branch = [
                        'id' => $pos['id'],
                        'name' => $pos['name'] . ' (' . ($index + 1) . ')', // Add numbering
                        'type' => 'position',
                        'department' => $pos['department'],
                        'department_label' => $pos['department'],
                        'position_order' => (int)$pos['position_order'],
                        'level_name' => $pos['level_name'],
                        'is_leadership' => (bool)$pos['is_leadership'],
                        'has_lead_role' => (bool)$pos['has_lead_role'],
                        'employee_count' => (int)$pos['employee_count'],
                        'children' => $this->build_hierarchy_children($order_groups, $current_order)
                    ];
                    $branches[] = $branch;
                }
            }
        }

        return $branches;
    }

    /**
     * Create organizational structure with departments and grouped positions
     */
    private function create_organizational_structure($positions)
    {
        $departments = [];
        
        // Group positions by department
        foreach ($positions as $position) {
            $dept = $position['department'] ?: 'Other';
            if (!isset($departments[$dept])) {
                $departments[$dept] = [];
            }
            $departments[$dept][] = $position;
        }
        
        $departmental_structure = [];
        
        foreach ($departments as $dept_name => $dept_positions) {
            // Group positions by name within department
            $grouped_positions = [];
            foreach ($dept_positions as $position) {
                $pos_name = $position['name'];
                if (!isset($grouped_positions[$pos_name])) {
                    $grouped_positions[$pos_name] = [];
                }
                $grouped_positions[$pos_name][] = $position;
            }
            
            // Create department children structure
            $department_children = [];
            
            foreach ($grouped_positions as $pos_name => $same_positions) {
                if (count($same_positions) == 1) {
                    // Single position - create normal node
                    $pos = $same_positions[0];
                    $department_children[] = [
                        'id' => $pos['id'],
                        'name' => $pos['name'],
                        'type' => 'position',
                        'department' => $dept_name,
                        'position_order' => (int)$pos['position_order'],
                        'level_name' => $pos['level_name'],
                        'is_leadership' => (bool)$pos['is_leadership'],
                        'has_lead_role' => (bool)$pos['has_lead_role'],
                        'employee_count' => (int)$pos['employee_count'],
                        'children' => []
                    ];
                } else {
                    // Multiple positions with same name - create group node
                    $group_children = [];
                    foreach ($same_positions as $pos) {
                        $group_children[] = [
                            'id' => $pos['id'],
                            'name' => $pos['name'],
                            'type' => 'position',
                            'department' => $dept_name,
                            'position_order' => (int)$pos['position_order'],
                            'level_name' => $pos['level_name'],
                            'is_leadership' => (bool)$pos['is_leadership'],
                            'has_lead_role' => (bool)$pos['has_lead_role'],
                            'employee_count' => (int)$pos['employee_count'],
                            'children' => []
                        ];
                    }
                    
                    // Create a position group
                    $department_children[] = [
                        'id' => 'group_' . $dept_name . '_' . $pos_name,
                        'name' => $pos_name,
                        'type' => 'position_group',
                        'department' => $dept_name,
                        'position_order' => (int)$same_positions[0]['position_order'],
                        'level_name' => $same_positions[0]['level_name'],
                        'is_leadership' => (bool)$same_positions[0]['is_leadership'],
                        'has_lead_role' => (bool)$same_positions[0]['has_lead_role'],
                        'employee_count' => array_sum(array_column($same_positions, 'employee_count')),
                        'children' => $group_children
                    ];
                }
            }
            
            // Sort department children by position_order (descending - senior to junior)
            usort($department_children, function($a, $b) {
                if ($a['position_order'] === $b['position_order']) {
                    return strcmp($a['name'], $b['name']);
                }
                return $b['position_order'] - $a['position_order'];
            });
            
            // Add department with its positions directly (no "Career Progression Path" node)
            foreach ($department_children as $position) {
                $position['department_label'] = $dept_name; // Add department label for frontend
                $departmental_structure[] = $position;
            }
        }
        
        return $departmental_structure;
    }

    /**
     * Create simple departmental tree for positions (legacy - keeping for compatibility)
     */
    private function create_simple_departmental_tree($positions)
    {
        $departments = [];
        
        foreach ($positions as $position) {
            $dept = $position['department'] ?: 'Other';
            if (!isset($departments[$dept])) {
                $departments[$dept] = [];
            }
            
            $departments[$dept][] = [
                'id' => $position['id'],
                'name' => $position['name'],
                'type' => 'position',
                'position_order' => (int)$position['position_order'],
                'level_name' => $position['level_name'],
                'level_order' => (int)$position['level_order'],
                'is_leadership' => (bool)$position['is_leadership'],
                'has_lead_role' => (bool)$position['has_lead_role'],
                'employee_count' => (int)$position['employee_count'],
                'department' => $dept
            ];
        }
        
        // Create hierarchical structure for each department
        $departmental_trees = [];
        
        foreach ($departments as $dept_name => $positions) {
            // Sort positions by position_order (descending - senior to entry level)
            usort($positions, function($a, $b) {
                if ($a['position_order'] === $b['position_order']) {
                    return strcmp($a['name'], $b['name']);
                }
                return $b['position_order'] - $a['position_order']; // Reversed order
            });
            
            // Create vertical career progression hierarchy
            $department_root = [
                'name' => $dept_name,
                'type' => 'department',
                'department' => $dept_name,
                'children' => $this->create_career_hierarchy($positions)
            ];
            
            $departmental_trees[] = $department_root;
        }
        
        // Sort departments by name for consistent display
        usort($departmental_trees, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        return $departmental_trees;
    }
    
    /**
     * Create vertical career progression hierarchy from sorted positions
     */
    private function create_career_hierarchy($sorted_positions)
    {
        if (empty($sorted_positions)) {
            return [];
        }
        
        // Create a linear chain where each position leads to the next lower level
        // CMO (4) -> Head Marketing (3) -> Specialist (1) -> Junior (0), etc.
        $hierarchy = [];
        
        for ($i = 0; $i < count($sorted_positions); $i++) {
            $position = $sorted_positions[$i];
            
            // Each position becomes a node
            $node = [
                'id' => $position['id'],
                'name' => $position['name'],
                'type' => 'position',
                'position_order' => $position['position_order'],
                'level_name' => $position['level_name'],
                'is_leadership' => $position['is_leadership'],
                'has_lead_role' => $position['has_lead_role'],
                'employee_count' => $position['employee_count'],
                'department' => $position['department'],
                'children' => []
            ];
            
            // If there's a next position, add it as a child (career progression down)
            if ($i + 1 < count($sorted_positions)) {
                $node['children'] = [$this->create_single_position_node($sorted_positions, $i + 1)];
            }
            
            // Return the first position as root (highest level - CEO/CMO/etc.)
            if ($i === 0) {
                return [$node];
            }
        }
        
        return [];
    }
    
    /**
     * Create a single position node with its career progression chain
     */
    private function create_single_position_node($positions, $index)
    {
        if ($index >= count($positions)) {
            return null;
        }
        
        $position = $positions[$index];
        
        $node = [
            'id' => $position['id'],
            'name' => $position['name'],
            'type' => 'position',
            'position_order' => $position['position_order'],
            'level_name' => $position['level_name'],
            'is_leadership' => $position['is_leadership'],
            'has_lead_role' => $position['has_lead_role'],
            'employee_count' => $position['employee_count'],
            'department' => $position['department'],
            'children' => []
        ];
        
        // If there's a next position, add it as a child
        if ($index + 1 < count($positions)) {
            $node['children'] = [$this->create_single_position_node($positions, $index + 1)];
        }
        
        return $node;
    }

    /**
     * Get career tree data for a specific position (user context)
     * Returns user-specific career progression paths and opportunities
     */
    public function get_user_career_tree_data()
    {
        try {
            $user_id = $_SESSION['user']['id'] ?? null;
            $position_id = $_GET['position_id'] ?? null;
            
            if (!$user_id) {
                throw new Exception('User not authenticated');
            }
            
            // Get user's current position if not provided
            if (!$position_id) {
                $user_profile = $this->mymodel->selectWithQuery("
                    SELECT position_id FROM user_profile WHERE user_id = '$user_id'
                ");
                $position_id = $user_profile[0]['position_id'] ?? null;
            }
            
            if (!$position_id) {
                throw new Exception('No position found for user');
            }
            
            // Get positions relevant to user's career path
            $positions = $this->get_user_relevant_positions($position_id);
            
            // Get career progressions relevant to user
            $progressions = $this->get_user_relevant_progressions($position_id);
            
            // Get user's quest progress
            $quest_progress = $this->get_user_quest_progress($user_id);
            
            // Build user-specific tree
            $tree_data = $this->build_user_career_tree($positions, $progressions, $quest_progress, $position_id);
            
            $response = [
                'success' => true,
                'data' => $tree_data,
                'user_context' => [
                    'current_position_id' => $position_id,
                    'available_paths' => count($progressions),
                    'quest_progress' => $quest_progress,
                    'recommendations' => $this->get_career_recommendations($user_id, $position_id)
                ]
            ];
            
            header('Content-Type: application/json');
            echo json_encode($response);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'USER_CAREER_TREE_ERROR'
            ]);
        }
    }

    public function test_career_endpoint()
    {
        try {
            header('Content-Type: application/json');

            // Test basic functionality step by step
            $user_id = $_SESSION['user']['id'] ?? null;

            if (!$user_id) {
                echo json_encode([
                    'success' => false,
                    'message' => 'No user session',
                    'debug' => 'Session data: ' . print_r($_SESSION, true)
                ]);
                return;
            }

            // Test database connection
            $test_query = $this->mymodel->selectWithQuery("SELECT 1 as test");
            if (empty($test_query)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Database connection failed'
                ]);
                return;
            }

            // Test user profile query
            $user_profile = $this->mymodel->selectWithQuery("
                SELECT position_id FROM user_profile WHERE user_id = '$user_id'
            ");

            if (empty($user_profile)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'No user profile found',
                    'user_id' => $user_id
                ]);
                return;
            }

            $position_id = $user_profile[0]['position_id'];

            // Test simple career progressions query
            $simple_progressions = $this->mymodel->selectWithQuery("
                SELECT cp.*, tp.name as target_position
                FROM career_progressions cp
                JOIN positions tp ON cp.target_position_id = tp.id
                WHERE cp.source_position_id = '$position_id'
                AND cp.is_active = 1
                LIMIT 5
            ");

            echo json_encode([
                'success' => true,
                'message' => 'Test successful',
                'data' => [
                    'user_id' => $user_id,
                    'position_id' => $position_id,
                    'progressions_found' => count($simple_progressions),
                    'sample_progressions' => $simple_progressions
                ]
            ]);

        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Build hierarchical career tree structure for D3.js
     */
    private function build_hierarchical_career_tree($positions, $progressions)
    {
        // Create position lookup map
        $position_map = [];
        foreach ($positions as $position) {
            $position_map[$position['id']] = [
                'id' => $position['id'],
                'name' => $position['name'],
                'department' => $position['department'],
                'level_name' => $position['level_name'],
                'level_id' => $position['level_id'],
                'level_order' => $position['level_order'],
                'is_leadership' => (bool)$position['is_leadership'],
                'has_lead_role' => (bool)$position['has_lead_role'],
                'employee_count' => (int)$position['employee_count'],
                'outgoing_paths' => (int)$position['outgoing_paths'],
                'incoming_paths' => (int)$position['incoming_paths'],
                'children' => []
            ];
        }
        
        // Build parent-child relationships based on career progressions
        foreach ($progressions as $progression) {
            $source_id = $progression['source_position_id'];
            $target_id = $progression['target_position_id'];
            
            if (isset($position_map[$source_id]) && isset($position_map[$target_id])) {
                // Add progression metadata to the relationship
                $position_map[$target_id]['progression'] = [
                    'type' => $progression['progression_type'],
                    'estimated_months' => $progression['estimated_months'],
                    'min_performance_rating' => $progression['min_performance_rating']
                ];
                
                $position_map[$source_id]['children'][] = &$position_map[$target_id];
            }
        }
        
        // Find root nodes (positions with no incoming progressions or entry-level positions)
        $roots = [];
        foreach ($position_map as $position) {
            if ($position['incoming_paths'] == 0 || $position['level_order'] == 1) {
                $roots[] = $position;
            }
        }
        
        // If no clear roots, group by department and level
        if (empty($roots)) {
            $roots = $this->create_departmental_tree($position_map);
        }
        
        // Return tree structure
        return [
            'name' => 'Career Progression Tree',
            'type' => 'root',
            'children' => $roots,
            'metadata' => [
                'total_nodes' => count($position_map),
                'root_nodes' => count($roots)
            ]
        ];
    }

    /**
     * Build user-specific career tree
     */
    private function build_user_career_tree($positions, $progressions, $quest_progress, $current_position_id)
    {
        // Build basic tree structure
        $tree_data = $this->build_hierarchical_career_tree($positions, $progressions);
        
        // Add user context to nodes
        $this->add_user_context_to_tree($tree_data, $quest_progress, $current_position_id);
        
        return $tree_data;
    }

    /**
     * Add user-specific context to tree nodes
     */
    private function add_user_context_to_tree(&$node, $quest_progress, $current_position_id)
    {
        if (isset($node['id'])) {
            // Mark current position
            $node['is_current'] = ($node['id'] == $current_position_id);
            
            // Add quest requirements and readiness
            $node['quest_requirements'] = $this->get_position_quest_requirements($node['id']);
            $node['career_readiness'] = $this->calculate_career_readiness($node['id'], $quest_progress);
            
            // Add accessibility status
            $node['is_accessible'] = $this->is_position_accessible($node['id'], $current_position_id, $quest_progress);
        }
        
        // Recursively process children
        if (isset($node['children'])) {
            foreach ($node['children'] as &$child) {
                $this->add_user_context_to_tree($child, $quest_progress, $current_position_id);
            }
        }
    }

    /**
     * Get positions relevant to user's career path
     */
    private function get_user_relevant_positions($position_id)
    {
        return $this->mymodel->selectWithQuery("
            SELECT DISTINCT
                p.id,
                p.name,
                p.department,
                p.is_leadership,
                p.has_lead_role,
                ql.name as level_name,
                ql.level_order,
                COUNT(DISTINCT up.id) as employee_count,
                COUNT(DISTINCT cp_out.id) as outgoing_paths,
                COUNT(DISTINCT cp_in.id) as incoming_paths
            FROM positions p
            LEFT JOIN quest_levels ql ON p.level_id = ql.id
            LEFT JOIN career_progressions cp_out ON p.id = cp_out.source_position_id AND cp_out.is_active = 1
            LEFT JOIN career_progressions cp_in ON p.id = cp_in.target_position_id AND cp_in.is_active = 1
            LEFT JOIN user_profile up ON p.id = up.position_id
            WHERE p.department = (
                SELECT p2.department FROM positions p2 WHERE p2.id = '$position_id'
            )
            OR p.id IN (
                SELECT cp.target_position_id FROM career_progressions cp 
                WHERE cp.source_position_id = '$position_id' AND cp.is_active = 1
            )
            OR p.id IN (
                SELECT cp.source_position_id FROM career_progressions cp 
                WHERE cp.target_position_id = '$position_id' AND cp.is_active = 1
            )
            OR p.id = '$position_id'
            GROUP BY p.id
            ORDER BY p.department, ql.level_order, p.name
        ");
    }

    /**
     * Get career progressions relevant to user
     */
    private function get_user_relevant_progressions($position_id)
    {
        return $this->mymodel->selectWithQuery("
            SELECT 
                cp.source_position_id,
                cp.target_position_id,
                cp.progression_type,
                cp.estimated_months,
                cp.min_performance_rating
            FROM career_progressions cp
            WHERE cp.is_active = 1
            AND (
                cp.source_position_id = '$position_id'
                OR cp.target_position_id = '$position_id'
                OR cp.source_position_id IN (
                    SELECT p.id FROM positions p 
                    WHERE p.department = (
                        SELECT p2.department FROM positions p2 WHERE p2.id = '$position_id'
                    )
                )
            )
            ORDER BY cp.source_position_id, cp.target_position_id
        ");
    }

    /**
     * Get user's quest progress for career readiness calculation
     */
    private function get_user_quest_progress($user_id)
    {
        $main_quests = $this->mymodel->selectWithQuery("
            SELECT mqs.*, mq.title, mq.default_benefit
            FROM main_quest_submissions mqs
            JOIN main_quests mq ON mqs.main_quest_id = mq.id
            WHERE mqs.user_id = '$user_id'
            ORDER BY mqs.created_at DESC
        ");
        
        $side_quests = $this->mymodel->selectWithQuery("
            SELECT sqs.*, sq.title, sq.points
            FROM side_quest_submissions sqs
            JOIN side_quests sq ON sqs.side_quest_id = sq.id
            WHERE sqs.user_id = '$user_id'
            AND sqs.status = 'approved'
            ORDER BY sqs.created_at DESC
        ");
        
        return [
            'main_quests' => $main_quests,
            'side_quests' => $side_quests,
            'total_main_approved' => count(array_filter($main_quests, fn($q) => $q['status'] === 'approved')),
            'total_side_approved' => count($side_quests),
            'total_points' => array_sum(array_column($side_quests, 'points'))
        ];
    }

    /**
     * Get career recommendations for user
     */
    private function get_career_recommendations($user_id, $position_id)
    {
        // Get available career progressions from current position
        $available_paths = $this->mymodel->selectWithQuery("
            SELECT 
                cp.*,
                p.name as target_position_name,
                p.department as target_department,
                ql.name as target_level_name
            FROM career_progressions cp
            JOIN positions p ON cp.target_position_id = p.id
            LEFT JOIN quest_levels ql ON p.level_id = ql.id
            WHERE cp.source_position_id = '$position_id' 
            AND cp.is_active = 1
            ORDER BY cp.estimated_months ASC, ql.level_order ASC
        ");
        
        $recommendations = [];
        foreach ($available_paths as $path) {
            $readiness = $this->calculate_career_readiness($path['target_position_id'], $this->get_user_quest_progress($user_id));
            
            $recommendations[] = [
                'target_position' => $path['target_position_name'],
                'department' => $path['target_department'],
                'level' => $path['target_level_name'],
                'estimated_months' => $path['estimated_months'],
                'readiness_percentage' => $readiness['percentage'],
                'readiness_status' => $readiness['status'],
                'next_steps' => $readiness['next_steps']
            ];
        }
        
        return $recommendations;
    }

    /**
     * Calculate career readiness percentage
     */
    private function calculate_career_readiness($target_position_id, $quest_progress)
    {
        // Get requirements for target position
        $requirements = $this->get_position_quest_requirements($target_position_id);
        
        if (empty($requirements)) {
            return [
                'percentage' => 85, // Default readiness if no specific requirements
                'status' => 'ready',
                'next_steps' => ['Apply for position when available']
            ];
        }
        
        $total_requirements = count($requirements);
        $met_requirements = 0;
        $next_steps = [];
        
        foreach ($requirements as $requirement) {
            switch ($requirement['type']) {
                case 'main_quest':
                    if ($quest_progress['total_main_approved'] >= $requirement['count']) {
                        $met_requirements++;
                    } else {
                        $next_steps[] = "Complete {$requirement['count']} main quests";
                    }
                    break;
                case 'side_quest':
                    if ($quest_progress['total_side_approved'] >= $requirement['count']) {
                        $met_requirements++;
                    } else {
                        $next_steps[] = "Complete {$requirement['count']} side quests";
                    }
                    break;
                case 'points':
                    if ($quest_progress['total_points'] >= $requirement['count']) {
                        $met_requirements++;
                    } else {
                        $needed = $requirement['count'] - $quest_progress['total_points'];
                        $next_steps[] = "Earn {$needed} more points";
                    }
                    break;
            }
        }
        
        $percentage = $total_requirements > 0 ? round(($met_requirements / $total_requirements) * 100) : 85;
        
        $status = 'not_ready';
        if ($percentage >= 90) $status = 'ready';
        else if ($percentage >= 70) $status = 'mostly_ready';
        else if ($percentage >= 50) $status = 'partially_ready';
        
        return [
            'percentage' => $percentage,
            'status' => $status,
            'next_steps' => empty($next_steps) ? ['Ready to apply!'] : $next_steps
        ];
    }

    /**
     * Get quest requirements for a position
     */
    private function get_position_quest_requirements($position_id)
    {
        // For now, return default requirements based on position level
        // This can be enhanced to store specific requirements per position
        $position = $this->mymodel->selectWithQuery("
            SELECT p.*, ql.level_order 
            FROM positions p 
            LEFT JOIN quest_levels ql ON p.level_id = ql.id 
            WHERE p.id = '$position_id'
        ");
        
        if (empty($position)) return [];
        
        $level_order = $position[0]['level_order'] ?? 1;
        $is_leadership = $position[0]['is_leadership'] ?? 0;
        
        $requirements = [];
        
        // Level-based requirements
        if ($level_order >= 2) { // Intermediate+
            $requirements[] = ['type' => 'main_quest', 'count' => 1];
            $requirements[] = ['type' => 'side_quest', 'count' => 3];
        }
        if ($level_order >= 3) { // Senior+
            $requirements[] = ['type' => 'main_quest', 'count' => 2];
            $requirements[] = ['type' => 'points', 'count' => 100];
        }
        
        // Leadership requirements
        if ($is_leadership) {
            $requirements[] = ['type' => 'main_quest', 'count' => 3];
            $requirements[] = ['type' => 'points', 'count' => 200];
        }
        
        return $requirements;
    }

    /**
     * Check if position is accessible to user
     */
    private function is_position_accessible($target_position_id, $current_position_id, $quest_progress)
    {
        // Check if there's a career progression path
        $path_exists = $this->mymodel->selectWithQuery("
            SELECT id FROM career_progressions 
            WHERE source_position_id = '$current_position_id' 
            AND target_position_id = '$target_position_id' 
            AND is_active = 1
        ");
        
        if (empty($path_exists)) {
            return false;
        }
        
        // Check quest requirements
        $readiness = $this->calculate_career_readiness($target_position_id, $quest_progress);
        return $readiness['percentage'] >= 50; // Minimum 50% readiness
    }

    /**
     * Get unique departments from positions
     */
    private function get_unique_departments($positions)
    {
        $departments = array_unique(array_column($positions, 'department'));
        return array_values(array_filter($departments));
    }

    /**
     * Get unique levels from positions
     */
    private function get_unique_levels($positions)
    {
        $levels = [];
        foreach ($positions as $position) {
            if ($position['level_name'] && !in_array($position['level_name'], $levels)) {
                $levels[] = [
                    'id' => $position['level_id'],
                    'name' => $position['level_name'],
                    'order' => $position['level_order']
                ];
            }
        }
        
        // Sort by level order
        usort($levels, fn($a, $b) => $a['order'] <=> $b['order']);
        return $levels;
    }

    /**
     * Create departmental tree structure when no clear hierarchy exists
     */
    private function create_departmental_tree($position_map)
    {
        $departments = [];

        foreach ($position_map as $position) {
            $dept = $position['department'] ?: 'Other';
            if (!isset($departments[$dept])) {
                $departments[$dept] = [
                    'name' => $dept,
                    'type' => 'department',
                    'children' => []
                ];
            }
            $departments[$dept]['children'][] = $position;
        }

        return array_values($departments);
    }

    /**
     * Get dendrogram data optimized for hierarchical clustering visualization
     */
    public function get_dendrogram_data()
    {
        try {
            header('Content-Type: application/json');

            // Get all positions with enhanced data for clustering
            $positions = $this->mymodel->selectWithQuery("
                SELECT
                    p.id,
                    p.name,
                    COALESCE(p.department, 'No Department') as department,
                    COALESCE(p.position_order, 0) as position_order,
                    COALESCE(p.level_id, 0) as level_id,
                    COALESCE(ql.name, 'No Level') as level_name,
                    COALESCE(ql.level_order, 999) as level_order,
                    COALESCE(p.is_leadership, 0) as is_leadership,
                    0 as employee_count,
                    0 as outgoing_paths,
                    0 as incoming_paths
                FROM positions p
                LEFT JOIN quest_levels ql ON p.level_id = ql.id
                ORDER BY p.position_order DESC, p.name
            ");

            if (empty($positions)) {
                echo json_encode([
                    'success' => true,
                    'data' => [],
                    'clustering_metadata' => [
                        'total_positions' => 0,
                        'clusters_generated' => 0,
                        'max_distance' => 0,
                        'departments' => [],
                        'levels' => []
                    ]
                ]);
                return;
            }

            // Get career progressions
            $progressions = [];
            try {
                $progressions = $this->mymodel->selectWithQuery("
                    SELECT
                        source_position_id,
                        target_position_id,
                        progression_type,
                        estimated_years
                    FROM career_progressions
                    WHERE is_active = 1
                ");
            } catch (Exception $e) {
                // Career progressions table might not exist
                $progressions = [];
            }

            // Calculate progression counts
            foreach ($positions as &$position) {
                $position_id = $position['id'];
                $outgoing = 0;
                $incoming = 0;

                foreach ($progressions as $prog) {
                    if ($prog['source_position_id'] == $position_id) {
                        $outgoing++;
                    }
                    if ($prog['target_position_id'] == $position_id) {
                        $incoming++;
                    }
                }

                $position['outgoing_paths'] = $outgoing;
                $position['incoming_paths'] = $incoming;
            }

            // Create hierarchical clustering data
            $hierarchical_data = $this->create_clustering_hierarchy($positions);

            // Generate clustering metadata
            $metadata = [
                'total_positions' => count($positions),
                'clusters_generated' => $this->count_clusters($hierarchical_data),
                'max_distance' => $this->calculate_max_distance($positions),
                'departments' => array_values(array_unique(array_column($positions, 'department'))),
                'levels' => array_values(array_unique(array_filter(array_column($positions, 'level_name')))),
                'position_order_range' => [
                    'min' => min(array_column($positions, 'position_order')),
                    'max' => max(array_column($positions, 'position_order'))
                ],
                'generated_at' => date('Y-m-d H:i:s')
            ];

            echo json_encode([
                'success' => true,
                'data' => $hierarchical_data,
                'flat_data' => $positions,
                'progressions' => $progressions,
                'clustering_metadata' => $metadata
            ]);

        } catch (Exception $e) {
            error_log("Dendrogram data error: " . $e->getMessage());

            echo json_encode([
                'success' => false,
                'message' => 'Failed to generate dendrogram data: ' . $e->getMessage(),
                'error_code' => 'DENDROGRAM_ERROR'
            ]);
        }
    }

    /**
     * Get organization chart data in hierarchical format for D3.js visualization
     * Returns flare.json-compatible structure with parent-child relationships
     */
    public function get_org_chart_data()
    {
        try {
            header('Content-Type: application/json');

            // Get all positions with full details
            $positions = $this->mymodel->selectWithQuery("
                SELECT
                    p.id,
                    p.name,
                    p.parent_position_id,
                    COALESCE(p.department, 'No Department') as department,
                    COALESCE(p.position_order, 0) as position_order,
                    COALESCE(p.level_id, 0) as level_id,
                    COALESCE(ql.name, 'No Level') as level_name,
                    COALESCE(ql.level_order, 999) as level_order,
                    COALESCE(p.is_leadership, 0) as is_leadership,
                    COALESCE(p.is_hiring, 0) as is_hiring,
                    COALESCE(p.has_lead_role, 0) as has_lead_role,
                    p.progression_requirements,
                    p.career_paths
                FROM positions p
                LEFT JOIN quest_levels ql ON p.level_id = ql.id
                ORDER BY p.position_order DESC, p.name
            ");

            if (empty($positions)) {
                echo json_encode([
                    'success' => true,
                    'data' => ['name' => 'Organization', 'children' => []],
                    'metadata' => [
                        'total_positions' => 0,
                        'departments' => [],
                        'levels' => [],
                        'max_depth' => 0
                    ]
                ]);
                return;
            }

            // Count subordinates for each position (for size calculation)
            $subordinate_counts = [];
            foreach ($positions as $pos) {
                $subordinate_counts[$pos['id']] = 0;
            }

            foreach ($positions as $pos) {
                if ($pos['parent_position_id']) {
                    if (isset($subordinate_counts[$pos['parent_position_id']])) {
                        $subordinate_counts[$pos['parent_position_id']]++;
                    }
                }
            }

            // Build hierarchical tree structure
            $org_chart = $this->build_org_chart_tree($positions, $subordinate_counts);

            // Generate metadata
            $departments = array_values(array_unique(array_column($positions, 'department')));
            $levels = array_values(array_unique(array_filter(array_column($positions, 'level_name'))));

            $metadata = [
                'total_positions' => count($positions),
                'departments' => $departments,
                'department_count' => count($departments),
                'levels' => $levels,
                'level_count' => count($levels),
                'max_depth' => $this->calculate_tree_depth($org_chart),
                'hiring_positions' => count(array_filter($positions, fn($p) => $p['is_hiring'] == 1)),
                'leadership_positions' => count(array_filter($positions, fn($p) => $p['is_leadership'] == 1)),
                'generated_at' => date('Y-m-d H:i:s')
            ];

            echo json_encode([
                'success' => true,
                'data' => $org_chart,
                'flat_data' => $positions,
                'metadata' => $metadata
            ]);

        } catch (Exception $e) {
            error_log("Org chart data error: " . $e->getMessage());

            echo json_encode([
                'success' => false,
                'message' => 'Failed to generate organization chart data: ' . $e->getMessage(),
                'error_code' => 'ORG_CHART_ERROR'
            ]);
        }
    }

    /**
     * Build hierarchical tree structure from flat position data
     */
    private function build_org_chart_tree($positions, $subordinate_counts, $parent_id = null)
    {
        $children = [];

        foreach ($positions as $pos) {
            // Match root nodes (parent_position_id is NULL or 0) or children of current parent
            if ($parent_id === null) {
                if (!empty($pos['parent_position_id'])) {
                    continue;
                }
            } else {
                if ($pos['parent_position_id'] != $parent_id) {
                    continue;
                }
            }

            // Calculate node size (based on subordinates + base size)
            $size = 100 + ($subordinate_counts[$pos['id']] * 50);

            $node = [
                'name' => $pos['name'],
                'id' => $pos['id'],
                'department' => $pos['department'],
                'level_id' => $pos['level_id'],
                'level_name' => $pos['level_name'],
                'level_order' => $pos['level_order'],
                'position_order' => $pos['position_order'],
                'is_leadership' => (int)$pos['is_leadership'],
                'is_hiring' => (int)$pos['is_hiring'],
                'has_lead_role' => (int)$pos['has_lead_role'],
                'size' => $size,
                'subordinate_count' => $subordinate_counts[$pos['id']],
                'progression_requirements' => $pos['progression_requirements'],
                'career_paths' => $pos['career_paths']
            ];

            // Recursively build children
            $node_children = $this->build_org_chart_tree($positions, $subordinate_counts, $pos['id']);
            if (!empty($node_children)) {
                $node['children'] = $node_children;
            }

            $children[] = $node;
        }

        // If this is the root call and we have children, wrap in root node
        if ($parent_id === null && !empty($children)) {
            // If only one root, return it directly
            if (count($children) === 1) {
                return $children[0];
            }

            // Multiple roots - wrap in organization node
            return [
                'name' => 'Organization',
                'id' => 'root',
                'type' => 'root',
                'size' => 500,
                'children' => $children
            ];
        }

        return $children;
    }

    /**
     * Calculate maximum depth of tree structure
     */
    private function calculate_tree_depth($node, $current_depth = 0)
    {
        if (!isset($node['children']) || empty($node['children'])) {
            return $current_depth;
        }

        $max_child_depth = $current_depth;
        foreach ($node['children'] as $child) {
            $child_depth = $this->calculate_tree_depth($child, $current_depth + 1);
            $max_child_depth = max($max_child_depth, $child_depth);
        }

        return $max_child_depth;
    }

    /**
     * Calculate career paths using hierarchical clustering
     */
    public function calculate_career_paths()
    {
        try {
            header('Content-Type: application/json');

            $source_position_id = $_GET['source_position_id'] ?? null;
            $max_paths = intval($_GET['max_paths'] ?? 10);

            if (!$source_position_id) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Source position ID is required'
                ]);
                return;
            }

            // Get all positions for clustering analysis
            $positions = $this->mymodel->selectWithQuery("
                SELECT
                    p.id,
                    p.name,
                    p.department,
                    p.position_order,
                    p.level_id,
                    ql.name as level_name,
                    ql.level_order,
                    p.is_leadership
                FROM positions p
                LEFT JOIN quest_levels ql ON p.level_id = ql.id
            ");

            // Calculate optimal career paths using clustering algorithm
            $career_paths = $this->generate_clustered_career_paths($source_position_id, $positions, $max_paths);

            echo json_encode([
                'success' => true,
                'source_position_id' => $source_position_id,
                'paths' => $career_paths,
                'metadata' => [
                    'total_paths_found' => count($career_paths),
                    'calculation_method' => 'hierarchical_clustering',
                    'max_paths_requested' => $max_paths
                ]
            ]);

        } catch (Exception $e) {
            error_log("Career paths calculation error: " . $e->getMessage());

            echo json_encode([
                'success' => false,
                'message' => 'Failed to calculate career paths: ' . $e->getMessage(),
                'error_code' => 'CAREER_PATHS_ERROR'
            ]);
        }
    }

    /**
     * Get clustering metrics and statistics
     */
    public function get_clustering_metrics()
    {
        try {
            header('Content-Type: application/json');

            // Get positions data
            $positions = $this->mymodel->selectWithQuery("
                SELECT
                    p.id,
                    p.name,
                    p.department,
                    p.position_order,
                    p.level_id,
                    ql.level_order,
                    p.is_leadership
                FROM positions p
                LEFT JOIN quest_levels ql ON p.level_id = ql.id
            ");

            if (empty($positions)) {
                echo json_encode([
                    'success' => true,
                    'metrics' => [
                        'total_positions' => 0,
                        'distance_matrix' => [],
                        'cluster_cohesion' => 0,
                        'silhouette_score' => 0
                    ]
                ]);
                return;
            }

            // Calculate clustering metrics
            $metrics = $this->calculate_clustering_metrics($positions);

            echo json_encode([
                'success' => true,
                'metrics' => $metrics
            ]);

        } catch (Exception $e) {
            error_log("Clustering metrics error: " . $e->getMessage());

            echo json_encode([
                'success' => false,
                'message' => 'Failed to calculate clustering metrics: ' . $e->getMessage(),
                'error_code' => 'CLUSTERING_METRICS_ERROR'
            ]);
        }
    }

    /**
     * Create hierarchical clustering structure for dendrogram
     */
    private function create_clustering_hierarchy($positions)
    {
        if (empty($positions)) {
            return [
                'name' => 'Organization',
                'type' => 'root',
                'children' => []
            ];
        }

        // Sort positions by position_order (highest first for CEO at top)
        usort($positions, function($a, $b) {
            return $b['position_order'] - $a['position_order'];
        });

        // Create hierarchical clustering based on organizational structure from image
        $hierarchy = [
            'name' => 'Organization',
            'type' => 'root',
            'children' => []
        ];

        // Group positions directly by department (no level grouping)
        $dept_groups = [];
        foreach ($positions as $pos) {
            $dept = $pos['department'];
            if (!isset($dept_groups[$dept])) {
                $dept_groups[$dept] = [];
            }
            $dept_groups[$dept][] = [
                'name' => $pos['name'],
                'type' => $this->getPositionTypeByOrder($pos['position_order']),
                'isPosition' => true,
                'data' => $pos,
                'id' => $pos['id'],
                'department' => $dept,
                'level_name' => $pos['level_name'],
                'position_order' => $pos['position_order'],
                'outgoing_paths' => $pos['outgoing_paths'],
                'incoming_paths' => $pos['incoming_paths'],
                'distance_metrics' => $this->calculate_position_distances($pos, $positions)
            ];
        }

        // Create department nodes and sort positions within each department by position_order (highest first)
        foreach ($dept_groups as $dept_name => $dept_positions) {
            // Sort positions within department by position_order (descending - highest first)
            usort($dept_positions, function($a, $b) {
                return ($b['position_order'] ?? 0) - ($a['position_order'] ?? 0);
            });

            $avg_order = count($dept_positions) > 0 ?
                array_sum(array_column($dept_positions, 'position_order')) / count($dept_positions) : 0;

            $hierarchy['children'][] = [
                'name' => $dept_name,
                'type' => 'department',
                'isDepartment' => true,
                'children' => $dept_positions,
                'count' => count($dept_positions),
                'cluster_distance' => $this->calculate_cluster_distance($dept_positions),
                'avg_order' => $avg_order
            ];
        }

        // Sort departments by average position_order (highest first)
        usort($hierarchy['children'], function($a, $b) {
            return ($b['avg_order'] ?? 0) - ($a['avg_order'] ?? 0);
        });

        return $hierarchy;
    }

    /**
     * Get position type based on position order
     */
    private function getPositionTypeByOrder($position_order)
    {
        if ($position_order >= 1000) return 'ceo';
        if ($position_order >= 500) return 'executive';
        if ($position_order >= 100) return 'manager';
        return 'specialist';
    }

    /**
     * Calculate distance metrics for a position relative to others
     */
    private function calculate_position_distances($position, $all_positions)
    {
        $distances = [];
        $pos_order = $position['position_order'];
        $pos_dept = $position['department'];
        $pos_level = $position['level_order'] ?? 0;

        foreach ($all_positions as $other) {
            if ($other['id'] == $position['id']) continue;

            // Calculate multi-dimensional distance
            $order_diff = abs($pos_order - $other['position_order']);
            $dept_similarity = ($pos_dept === $other['department']) ? 0 : 1;
            $level_diff = abs($pos_level - ($other['level_order'] ?? 0));

            // Weighted euclidean distance
            $distance = sqrt(
                pow($order_diff / 1000, 2) * 0.6 +  // Position order weight
                pow($dept_similarity, 2) * 0.2 +      // Department weight
                pow($level_diff, 2) * 0.2             // Level weight
            );

            $distances[] = [
                'target_id' => $other['id'],
                'target_name' => $other['name'],
                'distance' => round($distance, 3),
                'order_diff' => $order_diff,
                'dept_similarity' => $dept_similarity,
                'level_diff' => $level_diff
            ];
        }

        // Sort by distance and return top 5 closest
        usort($distances, function($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });

        return array_slice($distances, 0, 5);
    }

    /**
     * Calculate cluster distance for department groupings
     */
    private function calculate_cluster_distance($positions)
    {
        if (count($positions) <= 1) return 0;

        $total_distance = 0;
        $count = 0;

        for ($i = 0; $i < count($positions); $i++) {
            for ($j = $i + 1; $j < count($positions); $j++) {
                $pos1 = $positions[$i]['data'];
                $pos2 = $positions[$j]['data'];

                $order_diff = abs($pos1['position_order'] - $pos2['position_order']);
                $level_diff = abs(($pos1['level_order'] ?? 0) - ($pos2['level_order'] ?? 0));

                $distance = sqrt(pow($order_diff / 1000, 2) + pow($level_diff, 2));
                $total_distance += $distance;
                $count++;
            }
        }

        return $count > 0 ? round($total_distance / $count, 3) : 0;
    }

    /**
     * Generate career paths using clustering analysis
     */
    private function generate_clustered_career_paths($source_position_id, $positions, $max_paths)
    {
        $source_pos = null;
        foreach ($positions as $pos) {
            if ($pos['id'] == $source_position_id) {
                $source_pos = $pos;
                break;
            }
        }

        if (!$source_pos) {
            return [];
        }

        $paths = [];
        $source_order = $source_pos['position_order'];
        $source_dept = $source_pos['department'];
        $source_level = $source_pos['level_order'] ?? 0;

        foreach ($positions as $target_pos) {
            if ($target_pos['id'] == $source_position_id) continue;

            // Calculate progression viability
            $viability = $this->calculate_progression_viability($source_pos, $target_pos);

            if ($viability['score'] > 0.3) { // Minimum viability threshold
                $paths[] = [
                    'target_position_id' => $target_pos['id'],
                    'target_position_name' => $target_pos['name'],
                    'target_department' => $target_pos['department'],
                    'target_level' => $target_pos['level_name'],
                    'progression_type' => $viability['type'],
                    'viability_score' => $viability['score'],
                    'estimated_timeframe' => $viability['timeframe'],
                    'difficulty_level' => $viability['difficulty'],
                    'cluster_distance' => $viability['distance'],
                    'requirements' => $viability['requirements']
                ];
            }
        }

        // Sort by viability score (highest first)
        usort($paths, function($a, $b) {
            return $b['viability_score'] <=> $a['viability_score'];
        });

        return array_slice($paths, 0, $max_paths);
    }

    /**
     * Calculate progression viability between two positions
     */
    private function calculate_progression_viability($source_pos, $target_pos)
    {
        $source_order = $source_pos['position_order'];
        $target_order = $target_pos['position_order'];
        $source_level = $source_pos['level_order'] ?? 0;
        $target_level = $target_pos['level_order'] ?? 0;

        // Calculate basic metrics
        $order_ratio = $target_order / max($source_order, 1);
        $level_progression = $target_level - $source_level;
        $dept_change = ($source_pos['department'] !== $target_pos['department']);

        // Determine progression type
        $type = 'promotion';
        if ($level_progression < 0) $type = 'regression';
        else if ($level_progression == 0) $type = 'lateral';
        else if ($level_progression > 1) $type = 'skip_level';
        if ($dept_change) $type .= '_dept_change';

        // Calculate viability score (0-1)
        $score = 0.5; // Base score

        // Positive factors
        if ($level_progression > 0 && $level_progression <= 2) $score += 0.3;
        if ($order_ratio > 1 && $order_ratio <= 3) $score += 0.2;
        if (!$dept_change) $score += 0.2;

        // Negative factors
        if ($level_progression < 0) $score -= 0.4;
        if ($order_ratio > 5) $score -= 0.3;
        if ($dept_change) $score -= 0.1;

        // Calculate timeframe (months)
        $base_time = 12;
        if ($level_progression > 0) $base_time += $level_progression * 6;
        if ($dept_change) $base_time += 6;
        if ($order_ratio > 2) $base_time += ($order_ratio - 2) * 3;

        // Calculate difficulty (1-10)
        $difficulty = 5;
        if ($level_progression > 1) $difficulty += 2;
        if ($dept_change) $difficulty += 1;
        if ($order_ratio > 3) $difficulty += 2;
        $difficulty = min(max($difficulty, 1), 10);

        // Distance calculation
        $distance = sqrt(
            pow(abs($source_order - $target_order) / 1000, 2) * 0.6 +
            pow($dept_change ? 1 : 0, 2) * 0.2 +
            pow(abs($level_progression), 2) * 0.2
        );

        // Generate requirements
        $requirements = [];
        if ($level_progression > 0) {
            $requirements[] = "Complete performance review with rating 4.0+";
            $requirements[] = "Demonstrate leadership capabilities";
        }
        if ($dept_change) {
            $requirements[] = "Cross-departmental training completion";
            $requirements[] = "Department head approval";
        }
        if ($order_ratio > 2) {
            $requirements[] = "Executive approval required";
            $requirements[] = "Strategic leadership experience";
        }

        return [
            'score' => max(min($score, 1), 0),
            'type' => $type,
            'timeframe' => max($base_time, 6),
            'difficulty' => $difficulty,
            'distance' => round($distance, 3),
            'requirements' => $requirements
        ];
    }

    /**
     * Calculate comprehensive clustering metrics
     */
    private function calculate_clustering_metrics($positions)
    {
        $total_positions = count($positions);

        if ($total_positions < 2) {
            return [
                'total_positions' => $total_positions,
                'distance_matrix_size' => 0,
                'average_distance' => 0,
                'cluster_cohesion' => 0,
                'silhouette_score' => 0
            ];
        }

        // Calculate distance matrix
        $distance_matrix = [];
        $total_distance = 0;
        $distance_count = 0;

        for ($i = 0; $i < $total_positions; $i++) {
            $distance_matrix[$i] = [];
            for ($j = 0; $j < $total_positions; $j++) {
                if ($i == $j) {
                    $distance_matrix[$i][$j] = 0;
                } else {
                    $distance = $this->calculate_position_distance($positions[$i], $positions[$j]);
                    $distance_matrix[$i][$j] = $distance;
                    if ($i < $j) { // Count each pair only once
                        $total_distance += $distance;
                        $distance_count++;
                    }
                }
            }
        }

        $average_distance = $distance_count > 0 ? $total_distance / $distance_count : 0;

        // Calculate cluster cohesion by department
        $departments = [];
        foreach ($positions as $i => $pos) {
            $dept = $pos['department'];
            if (!isset($departments[$dept])) {
                $departments[$dept] = [];
            }
            $departments[$dept][] = $i;
        }

        $cluster_cohesion = 0;
        $cohesion_count = 0;

        foreach ($departments as $dept_indices) {
            if (count($dept_indices) > 1) {
                $dept_total_distance = 0;
                $dept_pair_count = 0;

                for ($i = 0; $i < count($dept_indices); $i++) {
                    for ($j = $i + 1; $j < count($dept_indices); $j++) {
                        $idx1 = $dept_indices[$i];
                        $idx2 = $dept_indices[$j];
                        $dept_total_distance += $distance_matrix[$idx1][$idx2];
                        $dept_pair_count++;
                    }
                }

                if ($dept_pair_count > 0) {
                    $cluster_cohesion += $dept_total_distance / $dept_pair_count;
                    $cohesion_count++;
                }
            }
        }

        $cluster_cohesion = $cohesion_count > 0 ? $cluster_cohesion / $cohesion_count : 0;

        // Simplified silhouette score calculation
        $silhouette_score = max(0, 1 - ($cluster_cohesion / max($average_distance, 0.001)));

        return [
            'total_positions' => $total_positions,
            'distance_matrix_size' => $total_positions * $total_positions,
            'average_distance' => round($average_distance, 3),
            'cluster_cohesion' => round($cluster_cohesion, 3),
            'silhouette_score' => round($silhouette_score, 3),
            'department_clusters' => count($departments),
            'largest_cluster_size' => max(array_map('count', $departments))
        ];
    }

    /**
     * Calculate distance between two positions
     */
    private function calculate_position_distance($pos1, $pos2)
    {
        $order_diff = abs($pos1['position_order'] - $pos2['position_order']);
        $dept_similarity = ($pos1['department'] === $pos2['department']) ? 0 : 1;
        $level_diff = abs(($pos1['level_order'] ?? 0) - ($pos2['level_order'] ?? 0));

        // Weighted euclidean distance
        return sqrt(
            pow($order_diff / 1000, 2) * 0.6 +
            pow($dept_similarity, 2) * 0.2 +
            pow($level_diff, 2) * 0.2
        );
    }

    /**
     * Count total clusters in hierarchical structure
     */
    private function count_clusters($hierarchy)
    {
        $count = 1; // Count current node

        if (isset($hierarchy['children']) && is_array($hierarchy['children'])) {
            foreach ($hierarchy['children'] as $child) {
                $count += $this->count_clusters($child);
            }
        }

        return $count;
    }

    /**
     * Calculate maximum distance between any two positions
     */
    private function calculate_max_distance($positions)
    {
        $max_distance = 0;

        for ($i = 0; $i < count($positions); $i++) {
            for ($j = $i + 1; $j < count($positions); $j++) {
                $distance = $this->calculate_position_distance($positions[$i], $positions[$j]);
                if ($distance > $max_distance) {
                    $max_distance = $distance;
                }
            }
        }

        return round($max_distance, 3);
    }

    /**
     * Save custom arrangement configuration
     */
    public function save_custom_arrangement()
    {
        try {
            header('Content-Type: application/json');

            // Validate request method
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Only POST requests allowed']);
                return;
            }

            // Get JSON input
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input) {
                echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
                return;
            }

            // Validate required fields
            $required_fields = ['name', 'arrangement_data', 'arrangement_type'];
            foreach ($required_fields as $field) {
                if (!isset($input[$field]) || empty($input[$field])) {
                    echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
                    return;
                }
            }

            $user_id = $_SESSION['user']['id'] ?? null;
            if (!$user_id) {
                echo json_encode(['success' => false, 'message' => 'User not authenticated']);
                return;
            }

            // Validate arrangement type
            $valid_types = ['manual', 'template', 'algorithm'];
            if (!in_array($input['arrangement_type'], $valid_types)) {
                echo json_encode(['success' => false, 'message' => 'Invalid arrangement type']);
                return;
            }

            // Validate arrangement data structure
            if (!$this->validate_arrangement_data($input['arrangement_data'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid arrangement data structure']);
                return;
            }

            // Check if arrangement name already exists for this user
            $existing = $this->mymodel->selectWithQuery("
                SELECT id FROM custom_arrangements
                WHERE user_id = '$user_id' AND name = '" . $this->db->escape_str($input['name']) . "'
            ");

            if (!empty($existing) && (!isset($input['id']) || $input['id'] != $existing[0]['id'])) {
                echo json_encode(['success' => false, 'message' => 'Arrangement name already exists']);
                return;
            }

            // Prepare data for storage
            $arrangement_json = json_encode($input['arrangement_data']);
            $metadata = [
                'node_count' => $this->count_arrangement_nodes($input['arrangement_data']),
                'creation_method' => $input['arrangement_type'],
                'last_modified' => date('Y-m-d H:i:s'),
                'version' => '1.0'
            ];

            $data = [
                'user_id' => $user_id,
                'name' => trim($input['name']),
                'description' => isset($input['description']) ? trim($input['description']) : null,
                'arrangement_type' => $input['arrangement_type'],
                'arrangement_data' => $arrangement_json,
                'metadata' => json_encode($metadata),
                'is_default' => isset($input['is_default']) ? ($input['is_default'] ? 1 : 0) : 0,
                'is_public' => isset($input['is_public']) ? ($input['is_public'] ? 1 : 0) : 0,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if (isset($input['id']) && !empty($input['id'])) {
                // Update existing arrangement
                $arrangement_id = intval($input['id']);

                // Verify ownership
                $ownership_check = $this->mymodel->selectWithQuery("
                    SELECT user_id FROM custom_arrangements WHERE id = '$arrangement_id'
                ");

                if (empty($ownership_check) || $ownership_check[0]['user_id'] != $user_id) {
                    echo json_encode(['success' => false, 'message' => 'Arrangement not found or access denied']);
                    return;
                }

                $result = $this->mymodel->updateData('custom_arrangements', $data, ['id' => $arrangement_id]);
                $message = 'Arrangement updated successfully';

            } else {
                // Create new arrangement
                $data['created_at'] = date('Y-m-d H:i:s');
                $arrangement_id = $this->mymodel->insertData('custom_arrangements', $data);
                $result = !empty($arrangement_id);
                $message = 'Arrangement saved successfully';
            }

            if ($result) {
                // If this is set as default, remove default flag from other arrangements
                if ($data['is_default']) {
                    $this->mymodel->updateData(
                        'custom_arrangements',
                        ['is_default' => 0],
                        ['user_id' => $user_id, 'id !=' => $arrangement_id ?? $input['id']]
                    );
                }

                echo json_encode([
                    'success' => true,
                    'message' => $message,
                    'arrangement_id' => $arrangement_id ?? $input['id'],
                    'metadata' => $metadata
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to save arrangement']);
            }

        } catch (Exception $e) {
            error_log("Save arrangement error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    }

    /**
     * Get custom arrangement by ID or list all arrangements
     */
    public function get_custom_arrangement()
    {
        try {
            header('Content-Type: application/json');

            $user_id = $_SESSION['user']['id'] ?? null;
            if (!$user_id) {
                echo json_encode(['success' => false, 'message' => 'User not authenticated']);
                return;
            }

            $arrangement_id = $_GET['id'] ?? null;

            if ($arrangement_id) {
                // Get specific arrangement
                $arrangement_id = intval($arrangement_id);
                $arrangement = $this->mymodel->selectWithQuery("
                    SELECT ca.*, u.name as creator_name
                    FROM custom_arrangements ca
                    LEFT JOIN users u ON ca.user_id = u.id
                    WHERE ca.id = '$arrangement_id'
                    AND (ca.user_id = '$user_id' OR ca.is_public = 1)
                ");

                if (empty($arrangement)) {
                    echo json_encode(['success' => false, 'message' => 'Arrangement not found']);
                    return;
                }

                $result = $arrangement[0];
                $result['arrangement_data'] = json_decode($result['arrangement_data'], true);
                $result['metadata'] = json_decode($result['metadata'], true);

                echo json_encode(['success' => true, 'arrangement' => $result]);

            } else {
                // Get all arrangements for user (own + public)
                $arrangements = $this->mymodel->selectWithQuery("
                    SELECT ca.*, u.name as creator_name,
                           CASE WHEN ca.user_id = '$user_id' THEN 1 ELSE 0 END as is_owned
                    FROM custom_arrangements ca
                    LEFT JOIN users u ON ca.user_id = u.id
                    WHERE ca.user_id = '$user_id' OR ca.is_public = 1
                    ORDER BY ca.is_default DESC, ca.updated_at DESC
                ");

                foreach ($arrangements as &$arrangement) {
                    $arrangement['metadata'] = json_decode($arrangement['metadata'], true);
                    // Don't include full arrangement_data in list view for performance
                    unset($arrangement['arrangement_data']);
                }

                echo json_encode(['success' => true, 'arrangements' => $arrangements]);
            }

        } catch (Exception $e) {
            error_log("Get arrangement error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load arrangement: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete custom arrangement
     */
    public function delete_custom_arrangement()
    {
        try {
            header('Content-Type: application/json');

            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Invalid request method']);
                return;
            }

            $arrangement_id = $_GET['id'] ?? $_POST['id'] ?? null;
            if (!$arrangement_id) {
                echo json_encode(['success' => false, 'message' => 'Arrangement ID is required']);
                return;
            }

            $user_id = $_SESSION['user']['id'] ?? null;
            if (!$user_id) {
                echo json_encode(['success' => false, 'message' => 'User not authenticated']);
                return;
            }

            $arrangement_id = intval($arrangement_id);

            // Verify ownership
            $arrangement = $this->mymodel->selectWithQuery("
                SELECT user_id, name FROM custom_arrangements WHERE id = '$arrangement_id'
            ");

            if (empty($arrangement)) {
                echo json_encode(['success' => false, 'message' => 'Arrangement not found']);
                return;
            }

            if ($arrangement[0]['user_id'] != $user_id) {
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }

            // Delete arrangement
            $result = $this->mymodel->deleteData('custom_arrangements', ['id' => $arrangement_id]);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Arrangement "' . $arrangement[0]['name'] . '" deleted successfully'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete arrangement']);
            }

        } catch (Exception $e) {
            error_log("Delete arrangement error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    }

    /**
     * Apply arrangement to dendrogram
     */
    public function apply_arrangement()
    {
        try {
            header('Content-Type: application/json');

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || !isset($input['arrangement_id'])) {
                echo json_encode(['success' => false, 'message' => 'Arrangement ID is required']);
                return;
            }

            $user_id = $_SESSION['user']['id'] ?? null;
            if (!$user_id) {
                echo json_encode(['success' => false, 'message' => 'User not authenticated']);
                return;
            }

            $arrangement_id = intval($input['arrangement_id']);

            // Get arrangement data
            $arrangement = $this->mymodel->selectWithQuery("
                SELECT arrangement_data, arrangement_type, name
                FROM custom_arrangements
                WHERE id = '$arrangement_id'
                AND (user_id = '$user_id' OR is_public = 1)
            ");

            if (empty($arrangement)) {
                echo json_encode(['success' => false, 'message' => 'Arrangement not found']);
                return;
            }

            $arrangement_data = json_decode($arrangement[0]['arrangement_data'], true);

            if (!$arrangement_data) {
                echo json_encode(['success' => false, 'message' => 'Invalid arrangement data']);
                return;
            }

            // Apply arrangement to current dendrogram data
            $applied_data = $this->apply_arrangement_to_dendrogram($arrangement_data);

            echo json_encode([
                'success' => true,
                'message' => 'Arrangement "' . $arrangement[0]['name'] . '" applied successfully',
                'arrangement_type' => $arrangement[0]['arrangement_type'],
                'dendrogram_data' => $applied_data
            ]);

        } catch (Exception $e) {
            error_log("Apply arrangement error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to apply arrangement: ' . $e->getMessage()]);
        }
    }

    /**
     * Validate arrangement data structure
     */
    private function validate_arrangement_data($data)
    {
        if (!is_array($data)) {
            return false;
        }

        // Check for required fields based on arrangement type
        $required_fields = ['nodes', 'layout'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }

        // Validate nodes structure
        if (!is_array($data['nodes'])) {
            return false;
        }

        foreach ($data['nodes'] as $node) {
            if (!isset($node['id']) || !isset($node['x']) || !isset($node['y'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Count nodes in arrangement data
     */
    private function count_arrangement_nodes($data)
    {
        if (!isset($data['nodes']) || !is_array($data['nodes'])) {
            return 0;
        }

        return count($data['nodes']);
    }

    /**
     * Apply saved arrangement to current dendrogram data
     */
    private function apply_arrangement_to_dendrogram($arrangement_data)
    {
        // Get current positions data
        $positions = $this->mymodel->selectWithQuery("
            SELECT
                p.id,
                p.name,
                COALESCE(p.department, 'No Department') as department,
                COALESCE(p.position_order, 0) as position_order,
                COALESCE(ql.name, 'No Level') as level_name,
                COALESCE(ql.level_order, 999) as level_order,
                COALESCE(p.is_leadership, 0) as is_leadership
            FROM positions p
            LEFT JOIN quest_levels ql ON p.level_id = ql.id
            ORDER BY p.position_order DESC, p.name
        ");

        // Create base dendrogram structure
        $dendrogram_data = $this->create_clustering_hierarchy($positions);

        // Apply custom positions from arrangement data
        if (isset($arrangement_data['nodes']) && is_array($arrangement_data['nodes'])) {
            $this->apply_node_positions($dendrogram_data, $arrangement_data['nodes']);
        }

        // Apply layout settings
        if (isset($arrangement_data['layout'])) {
            $dendrogram_data['layout_settings'] = $arrangement_data['layout'];
        }

        return $dendrogram_data;
    }

    /**
     * Apply custom node positions to dendrogram data
     */
    private function apply_node_positions(&$node, $position_overrides)
    {
        // Create lookup map for position overrides
        $position_map = [];
        foreach ($position_overrides as $override) {
            $position_map[$override['id']] = $override;
        }

        // Apply positions recursively
        $this->apply_positions_recursive($node, $position_map);
    }

    /**
     * Recursively apply position overrides
     */
    private function apply_positions_recursive(&$node, $position_map)
    {
        // Apply position if override exists
        if (isset($node['id']) && isset($position_map[$node['id']])) {
            $override = $position_map[$node['id']];
            $node['x'] = $override['x'];
            $node['y'] = $override['y'];
            if (isset($override['fixed'])) {
                $node['fixed'] = $override['fixed'];
            }
        }

        // Recursively apply to children
        if (isset($node['children']) && is_array($node['children'])) {
            foreach ($node['children'] as &$child) {
                $this->apply_positions_recursive($child, $position_map);
            }
        }
    }

    /**
     * ================================================================
     * CAREER PATH VISUALIZATION SYSTEM
     * ================================================================
     * Dual-view system for Reporting Structure vs Career Paths
     * ================================================================
     */

    /**
     * Dual-view org chart page
     */
    public function dual_view_org_chart()
    {
        $data['user'] = $_SESSION['user'];
        $data['title'] = 'Career Path Visualization - ' . $this->template->title();
        $data['content'] = $this->load->view("position/dual_view_org_chart", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    /**
     * Get data for dual-view visualization
     * Supports both reporting structure and career paths modes
     */
    public function get_dual_view_data()
    {
        try {
            header('Content-Type: application/json');

            $mode = $_GET['mode'] ?? 'reporting'; // 'reporting' or 'career_paths'

            // Get all positions with relationships
            $positions = $this->mymodel->selectWithQuery("
                SELECT
                    p.id,
                    p.name,
                    p.parent_position_id,
                    p.department,
                    p.level_id,
                    p.position_order,
                    p.career_paths,
                    COALESCE(ql.name, 'No Level') as level_name,
                    COALESCE(ql.level_order, 999) as level_order,
                    COUNT(DISTINCT up.user_id) as employee_count
                FROM positions p
                LEFT JOIN quest_levels ql ON p.level_id = ql.id
                LEFT JOIN user_profile up ON up.position_id = p.id
                GROUP BY p.id
                ORDER BY p.position_order DESC, p.name
            ");

            // Calculate subordinate counts
            $subordinate_counts = [];
            foreach ($positions as $pos) {
                $subordinate_counts[$pos['id']] = $this->count_all_subordinates($pos['id'], $positions);
            }

            if ($mode === 'career_paths') {
                // Build data structure optimized for career path visualization
                $data = $this->build_career_path_visualization_data($positions, $subordinate_counts);
            } else {
                // Build traditional reporting tree
                $data = $this->build_org_chart_tree($positions, $subordinate_counts, null);
            }

            // Count positions with career paths
            $positions_with_paths = 0;
            foreach ($positions as $pos) {
                if (!empty($pos['career_paths'])) {
                    $positions_with_paths++;
                }
            }

            echo json_encode([
                'success' => true,
                'mode' => $mode,
                'data' => $data,
                'metadata' => [
                    'total_positions' => count($positions),
                    'positions_with_paths' => $positions_with_paths,
                    'department_count' => count(array_unique(array_column($positions, 'department')))
                ]
            ]);

        } catch (Exception $e) {
            error_log("Dual view data error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Failed to load dual view data: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Build visualization data for career path mode
     * Returns tree structure with career path connections
     */
    private function build_career_path_visualization_data($positions, $subordinate_counts)
    {
        // Start with all positions (no hierarchy filter in career path mode)
        $nodes = [];
        $links = [];

        foreach ($positions as $pos) {
            // Add node
            $node = [
                'id' => $pos['id'],
                'name' => $pos['name'],
                'department' => $pos['department'] ?? 'No Department',
                'level_name' => $pos['level_name'],
                'level_order' => $pos['level_order'],
                'employee_count' => $pos['employee_count'],
                'subordinate_count' => $subordinate_counts[$pos['id']],
                'size' => 100 + ($subordinate_counts[$pos['id']] * 50),
                'has_career_paths' => !empty($pos['career_paths'])
            ];

            // Parse career paths if they exist
            if (!empty($pos['career_paths'])) {
                $paths_data = json_decode($pos['career_paths'], true);
                $career_paths = [];

                if (isset($paths_data['next_roles']) && is_array($paths_data['next_roles'])) {
                    foreach ($paths_data['next_roles'] as $path) {
                        $career_paths[] = [
                            'target_id' => $path['position_id'],
                            'target_name' => $path['position_name'],
                            'path_type' => $path['path_type'],
                            'timeline' => $path['estimated_timeline'] ?? 'N/A'
                        ];

                        // Add link for visualization
                        $links[] = [
                            'source' => $pos['id'],
                            'target' => $path['position_id'],
                            'path_type' => $path['path_type'],
                            'timeline' => $path['estimated_timeline'] ?? 'N/A'
                        ];
                    }
                }

                $node['career_paths'] = $career_paths;
            }

            $nodes[] = $node;
        }

        return [
            'nodes' => $nodes,
            'links' => $links
        ];
    }

    /**
     * Get detailed career path information for a specific position
     */
    public function get_career_path_details()
    {
        try {
            header('Content-Type: application/json');

            $position_id = $_GET['position_id'] ?? null;

            if (!$position_id) {
                echo json_encode(['success' => false, 'message' => 'Position ID required']);
                return;
            }

            // Get position with career paths
            $position = $this->mymodel->selectWithQuery("
                SELECT
                    p.id,
                    p.name,
                    p.department,
                    p.career_paths,
                    ql.name as level_name,
                    ql.level_order
                FROM positions p
                LEFT JOIN quest_levels ql ON p.level_id = ql.id
                WHERE p.id = '$position_id'
            ");

            if (empty($position)) {
                echo json_encode(['success' => false, 'message' => 'Position not found']);
                return;
            }

            $career_paths = [];
            if (!empty($position[0]['career_paths'])) {
                $paths_data = json_decode($position[0]['career_paths'], true);
                if (isset($paths_data['next_roles']) && is_array($paths_data['next_roles'])) {
                    $career_paths = $paths_data['next_roles'];
                }
            }

            echo json_encode([
                'success' => true,
                'position' => $position[0],
                'paths' => $career_paths
            ]);

        } catch (Exception $e) {
            error_log("Career path details error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Failed to get career path details: ' . $e->getMessage()
            ]);
        }
    }
}