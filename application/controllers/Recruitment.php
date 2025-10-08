<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';


class Recruitment extends BaseController
{
    // Configure BaseController for automatic permission checking
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

        // BaseController automatically checks 'view' permission for index()
        // Pass permission data to view
        $data['can_create'] = $this->permission->check_permission($user_id, 'recruitment', 'create');
        $data['can_edit'] = $this->permission->check_permission($user_id, 'recruitment', 'edit');
        $data['can_delete'] = $this->permission->check_permission($user_id, 'recruitment', 'delete');

        $keyword_category = $_GET['keyword_category'] ?? "Nama";
        $keyword = $_GET['keyword'] ?? "";
        $status_filter = $_GET['status_filter'] ?? "";
        $status_testcase = $_GET['status_testcase'] ?? "";
        
        $data['keyword_category'] = $keyword_category;
        $data['status_filter'] = $status_filter;
        $data['status_testcase'] = $status_testcase;
        $data['title'] = 'Recruitment Management - ' . $this->template->title();

        $qry = "1=1";
        
        if ($keyword) {
            if ($keyword_category == "Nama") {
                $qry .= " AND nama_lengkap LIKE '%$keyword%'";
            } else if ($keyword_category == "Posisi") {
                $qry .= " AND posisi_dilamar LIKE '%$keyword%'";
            }
        }
        
        if ($status_filter) {
            $qry .= " AND status_recruitment = '$status_filter'";
        }

        if ($status_testcase == "shared_testcase_1" || $status_testcase == "done_testcase_1") {
            $qry .= " AND status_testcase = '$status_testcase'";
        } else if ($status_testcase == "pending") {
            $qry .= " AND (status_testcase IS NULL OR status_testcase = '')";
        }

        $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) AS count 
            FROM job_applications 
            WHERE $qry");
        $data['page'] = CEIL($query[0]['count'] / 10);
        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query[0]['count']) . ' data ditemukan!</label></p>';

        $current_page = intval($_GET['page'] ?? 1);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $url = base_url() . '/recruitment/' . $this->template->get_param();
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $data['content'] = $this->load->view("recruitment/all", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function item()
    {
        $data['template'] = $this->template;
        $keyword_category = $_GET['keyword_category'] ?? "Nama";
        $keyword = $_GET['keyword'] ?? "";
        $status_filter = $_GET['status_filter'] ?? "";
        $status_testcase = $_GET['status_testcase'] ?? "";

        $qry = "1=1";

        if ($keyword) {
            if ($keyword_category == "Nama") {
                $qry .= " AND nama_lengkap LIKE '%$keyword%'";
            } else if ($keyword_category == "Posisi") {
                $qry .= " AND posisi_dilamar LIKE '%$keyword%'";
            }
        }

        if ($status_filter) {
            $qry .= " AND status_recruitment = '$status_filter'";
        }

        if ($status_testcase == "shared_testcase_1" || $status_testcase == "done_testcase_1") {
            $qry .= " AND status_testcase = '$status_testcase'";
        } else if ($status_testcase == "pending") {
            $qry .= " AND (status_testcase IS NULL OR status_testcase = '')";
        }

        $per_page_options = [10, 20, 30, 50, 100, 500];
        $limit = $_GET['limit'] ?? 10;
        if (!in_array($limit, $per_page_options)) {
            $limit = 10;
        }
        $data['limit'] = $limit;
        $data['per_page_options'] = $per_page_options;

        $current_page = $_GET['page'] ?? 1;
        $offset = ($current_page > 1) ? ($current_page - 1) * $limit : 0;

        $data['page'] = ceil($total_data / $limit);
        $data['current_page'] = $current_page;

        $query = $this->mymodel->selectWithQuery("
            SELECT created_at, nama_lengkap, posisi_dilamar, status_recruitment, 
                status_approval, status_testcase, level, id, notes_hr, tag
            FROM job_applications 
            WHERE $qry
            ORDER BY created_at DESC 
            LIMIT $offset, $limit
        ");
        $data['data'] = $query;

        $data['start'] = $offset;
        $data['end'] = min($offset + $limit, $total_data);

        $this->load->view("recruitment/item", $data);
    }


    public function detail()
    {
        $id = $_GET['id'];
        $interview_type = $_GET['type'] ?? '';

        $qry = "1=1";
        if (!empty($interview_type)) {
            $qry = "AND i.interview_status = '$interview_type'";
        } 
        $query = $this->mymodel->selectWithQuery("SELECT j.*, i.id as id_interview
        FROM job_applications j
        LEFT JOIN interview i ON j.id = i.id_job_applications
        WHERE j.id = '$id' AND $qry");
        
        if (empty($query)) {
            redirect(base_url() . 'recruitment');
        }
        
        $data['data'] = $query[0];
        
        // Check if tag is "Already Apply" and get history
        $data['history'] = array();
        if (isset($data['data']['tag']) && $data['data']['tag'] == 'Already Apply') {
            // Get the reference column from status_logs to match with
            $nama_lengkap = $data['data']['nama_lengkap'];
            
            // Query to get history based on nama_lengkap
            $history_query = $this->mymodel->selectWithQuery("SELECT j.*, 
                DATE_FORMAT(j.created_at, '%d %M %Y %H:%i') as formatted_created_at,
                DATE_FORMAT(j.updated_at, '%d %M %Y %H:%i') as formatted_updated_at
            FROM job_applications j 
            WHERE j.nama_lengkap = '$nama_lengkap' 
            AND j.id != '$id' 
            ORDER BY j.created_at DESC");
            
            if (!empty($history_query)) {
                $data['history'] = $history_query;
            }
        }
        
        $data['title'] = 'Detail Recruitment - ' . $this->template->title();
        $data['content'] = $this->load->view("recruitment/detail_page", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function get_detail_data()
    {
        header('Content-Type: application/json');
        
        $data['user'] = $_SESSION['user'];
        
        $id = $_GET['id'] ?? '';
        
        if (empty($id)) {
            echo json_encode(['status' => 'error', 'message' => 'ID is required']);
            return;
        }
        
        try {
            $query = $this->mymodel->selectWithQuery("SELECT j.*, i.id as id_interview
                FROM job_applications j
                LEFT JOIN interview i ON j.id = i.id_job_applications
                WHERE j.id = '$id'");
            
            if (empty($query)) {
                echo json_encode(['status' => 'error', 'message' => 'Data not found']);
                return;
            }
            
            $main_data = $query[0];
            
            $history = array();
            if (isset($main_data['tag']) && $main_data['tag'] == 'Already Apply') {
                $nama_lengkap = $main_data['nama_lengkap'];
                
                $history_query = $this->mymodel->selectWithQuery("SELECT j.*,
                    DATE_FORMAT(j.created_at, '%d %M %Y %H:%i') as formatted_created_at,
                    DATE_FORMAT(j.updated_at, '%d %M %Y %H:%i') as formatted_updated_at
                    FROM job_applications j
                    WHERE j.nama_lengkap = '$nama_lengkap'
                    AND j.id != '$id'
                    ORDER BY j.created_at DESC");
                
                if (!empty($history_query)) {
                    $history = $history_query;
                }
            }
            
            echo json_encode([
                'status' => 'success',
                'data' => $main_data,
                'history' => $history
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function update_status()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $user = $_SESSION['user'];

        $id = $this->input->post('id');
        $field = $this->input->post('field');
        $value = $this->input->post('value');
        $link_testcase = $this->input->post('link_testcase');

        if (empty($id) || empty($field)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
            return;
        }

        $allowed_fields = ['status_recruitment', 'status_approval', 'status_testcase'];
        if (!in_array($field, $allowed_fields)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid field']);
            return;
        }

        try {
            // Prepare update data
            $update_data = [$field => $value];

            if ($field === 'status_testcase' && $value === 'done_testcase_1' && !empty($link_testcase)) {
                $update_data['link_testcase'] = $link_testcase;
            }

            if ($field === 'status_recruitment') {
                $update_data['status_approval'] = 'pending';
            }

            $this->db->where('id', $id);
            $this->db->update('job_applications', $update_data);

            $log_data = [
                'user_id' => $user['id'],
                'activity' => 'Update recruitment status',
                'details' => json_encode([
                    'applicant_id' => $id,
                    'field' => $field,
                    'new_value' => $value
                ]),
                'created_at' => date('Y-m-d H:i:s')
            ];
            $this->db->insert('activity_logs', $log_data);

            if ($field === 'status_recruitment' && in_array(strtolower($value), ['interview_user', 'interview_hr'])) {
                $this->db->where('id_job_applications', $id);
                $this->db->where('interview_status', $value);
                $exists = $this->db->get('interview')->row();

                if (!$exists) {
                    $interview_data = [
                        'id_job_applications' => $id,
                        'interview_datetime' => null,
                        'interview_status' => $value,
                        'notes' => null,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => null,
                    ];
                    $this->db->insert('interview', $interview_data);
                }
            }


            echo json_encode(['status' => 'success', 'message' => 'Status updated successfully']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }


    public function update_notes()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $user = $_SESSION['user'];

        $id = $this->input->post('id');
        $notes = $this->input->post('notes');

        try {
            $this->db->where('id', $id);
            $result = $this->db->update('job_applications', ['notes_hr' => $notes]);

            if ($this->db->affected_rows() >= 0) {
                echo json_encode(['status' => 'success', 'message' => 'Notes updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No changes made or record not found']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
}