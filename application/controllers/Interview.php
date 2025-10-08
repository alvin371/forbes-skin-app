<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class Interview extends BaseController
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

        // Pass permission data to view
        $data['can_view'] = $this->permission->check_permission($user_id, 'interview', 'view');
        $data['can_approve'] = $this->permission->check_permission($user_id, 'interview', 'approve');

        $keyword_category = $_GET['keyword_category'] ?? "Nama";
        $keyword = $_GET['keyword'] ?? "";
        $status_filter = $_GET['status_filter'] ?? "";
        
        $data['keyword_category'] = $keyword_category;
        $data['status_filter'] = $status_filter;
        $data['title'] = 'Interview Management - ' . $this->template->title();

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

        $query = $this->mymodel->selectWithQuery("SELECT COUNT(i.id) AS count 
            FROM interview i
            INNER JOIN job_applications j ON i.id_job_applications = j.id
            WHERE $qry");
        $data['page'] = CEIL($query[0]['count'] / 10);
        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query[0]['count']) . ' data ditemukan!</label></p>';

        $current_page = intval($_GET['page'] ?? 1);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $url = base_url() . '/interview/' . $this->template->get_param();
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $data['content'] = $this->load->view("interview/all", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function item()
    {
        $data['template'] = $this->template;
        $keyword_category = $_GET['keyword_category'] ?? "Nama";
        $keyword = $_GET['keyword'] ?? "";
        $status_filter = $_GET['status_filter'] ?? "";
        
        $qry = "1=1";
        
        if ($keyword) {
            if ($keyword_category == "Nama") {
                $qry .= " AND j.nama_lengkap LIKE '%$keyword%'";
            } else if ($keyword_category == "Posisi") {
                $qry .= " AND j.posisi_dilamar LIKE '%$keyword%'";
            }
        }
        
        if ($status_filter) {
            $qry .= " AND j.status_recruitment = '$status_filter'";
        }

        $limit = 10;
        $current_page = $_GET['page'] ?? 1;
        
        if ($current_page <= 1) {
            $offset = 0;
        } else {
            $offset = ($current_page - 1) * $limit;
        }

        $query = $this->mymodel->selectWithQuery("SELECT i.*, j.nama_lengkap, j.posisi_dilamar
            FROM interview i
            INNER JOIN job_applications j ON i.id_job_applications = j.id 
            WHERE $qry
            ORDER BY created_at DESC LIMIT $offset, $limit");;
        $data['data'] = $query;
        $data['start'] = $offset;
        
        $this->load->view("interview/item", $data);
    }

    public function update_status()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $id = $this->input->post('id');
        $applicantId = $this->input->post('applicant_id');
        $field = $this->input->post('field');
        $value = $this->input->post('value');

        if (empty($id) || empty($field)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
            return;
        }

        $allowed_fields = ['interview_approval'];
        if (!in_array($field, $allowed_fields)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid field']);
            return;
        }

        try {
            $update_data = [$field => $value];

            // Update job_applications table
            $this->db->where('id', $id);
            $this->db->update('interview', $update_data);

            $this->db->where('id', $applicantId);
            $this->db->update('job_applications', ['status_approval' => $value]);
            if ($this->db->affected_rows() <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'No changes made or record not found']);
                return;
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

        $id = $this->input->post('id');
        $notes = $this->input->post('notes');

        try {
            $this->db->where('id', $id);
            $result = $this->db->update('interview', ['notes' => $notes]);

            if ($this->db->affected_rows() >= 0) {
                echo json_encode(['status' => 'success', 'message' => 'Notes updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No changes made or record not found']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function update_datetime()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $id = $this->input->post('id');
        $datetime = $this->input->post('datetime');

        try {
            $this->db->where('id', $id);
            $result = $this->db->update('interview', ['interview_datetime' => $datetime]);

            if ($this->db->affected_rows() >= 0) {
                echo json_encode(['status' => 'success', 'message' => 'Interview datetime updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No changes made or record not found']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function update_interviewer()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $id = $this->input->post('id');
        $interviewer = $this->input->post('interviewer');

        try {
            $this->db->where('id', $id);
            $result = $this->db->update('interview', ['interviewer' => $interviewer]);

            if ($this->db->affected_rows() >= 0) {
                echo json_encode(['status' => 'success', 'message' => 'Interviewer updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No changes made or record not found']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function questions()
    {
        $data['user'] = $_SESSION['user'];
        $user_id = $data['user']['id'];

        // Pass permission data to view
        $data['can_create'] = $this->permission->check_permission($user_id, 'interview_questions', 'create');
        $data['can_edit'] = $this->permission->check_permission($user_id, 'interview_questions', 'edit');
        $data['can_delete'] = $this->permission->check_permission($user_id, 'interview_questions', 'delete');

        $position_filter = $_GET['position_filter'] ?? "";
        $category_filter = $_GET['category_filter'] ?? "";
        $keyword = $_GET['keyword'] ?? "";
        
        $data['position_filter'] = $position_filter;
        $data['category_filter'] = $category_filter;
        $data['keyword'] = $keyword;
        $data['title'] = 'Interview Questions Management - ' . $this->template->title();

        $qry = "1=1";
        
        if ($position_filter) {
            $qry .= " AND iq.position = '$position_filter'";
        }
        
        if ($category_filter) {
            $qry .= " AND iq.category_id = '$category_filter'";
        }
        
        if ($keyword) {
            $qry .= " AND iq.question_text LIKE '%$keyword%'";
        }

        $query = $this->mymodel->selectWithQuery("SELECT COUNT(iq.id) AS count 
            FROM interview_questions iq
            INNER JOIN interview_categories ic ON iq.category_id = ic.id
            WHERE $qry");
        $data['page'] = CEIL($query[0]['count'] / 10);
        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query[0]['count']) . ' data ditemukan!</label></p>';

        // Get positions for filter
        $data['positions'] = $this->mymodel->selectWithQuery("SELECT DISTINCT position FROM interview_questions ORDER BY position");
        
        // Get categories for filter
        $data['categories'] = $this->mymodel->selectWithQuery("SELECT * FROM interview_categories WHERE is_active = 1 ORDER BY category_name");

        $current_page = intval($_GET['page'] ?? 1);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $url = base_url() . '/interview/questions/' . $this->template->get_param();
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $data['content'] = $this->load->view("interview/questions", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function questions_item()
    {
        $data['template'] = $this->template;
        $position_filter = $_GET['position_filter'] ?? "";
        $category_filter = $_GET['category_filter'] ?? "";
        $keyword = $_GET['keyword'] ?? "";
        
        $qry = "1=1";
        
        if ($position_filter) {
            $qry .= " AND iq.position = '$position_filter'";
        }
        
        if ($category_filter) {
            $qry .= " AND iq.category_id = '$category_filter'";
        }
        
        if ($keyword) {
            $qry .= " AND iq.question_text LIKE '%$keyword%'";
        }

        $limit = 10;
        $current_page = $_GET['page'] ?? 1;
        
        if ($current_page <= 1) {
            $offset = 0;
        } else {
            $offset = ($current_page - 1) * $limit;
        }

        $query = $this->mymodel->selectWithQuery("SELECT iq.*, ic.category_name
            FROM interview_questions iq
            INNER JOIN interview_categories ic ON iq.category_id = ic.id 
            WHERE $qry
            ORDER BY iq.position, iq.sort_order ASC LIMIT $offset, $limit");
        $data['data'] = $query;
        $data['start'] = $offset;
        
        $this->load->view("interview/questions_item", $data);
    }

    public function add_question()
    {
        $data['user'] = $_SESSION['user'];
        $data['title'] = 'Add Interview Question - ' . $this->template->title();
        $data['categories'] = $this->mymodel->selectWithQuery("SELECT * FROM interview_categories WHERE is_active = 1 ORDER BY category_name");
        $data['positions'] = $this->mymodel->selectWithQuery("
            SELECT positions.id, positions.name as name, level.name as level_name 
            FROM positions 
            INNER JOIN quest_levels level ON positions.level_id = level.id 
        ");

        if ($this->input->post()) {
            $dt = $this->input->post();
            $id_posisi = $dt['position_id'] ?? null;

            if ($id_posisi) {
                $query = $this->db->select('positions.name, quest_levels.name as level_name')
                                ->from('positions')
                                ->join('quest_levels', 'positions.level_id = quest_levels.id')
                                ->where('positions.id', $id_posisi)
                                ->get();

                $posisi = $query->row_array();

                if ($posisi) {
                    $position_text = $posisi['name'] . ' - ' . $posisi['level_name'];
                } else {
                    $position_text = 'Unknown Position';
                }
            }
            $insert_data = array(
                'position_id' => $this->input->post('position_id'),
                'position' => $position_text ?? '',
                'category_id' => $this->input->post('category_id'),
                'question_text' => $this->input->post('question_text'),
                'question_type' => $this->input->post('question_type'),
                'score_range' => $this->input->post('score_range'),
                'options' => $this->input->post('options'),
                'sort_order' => $this->input->post('sort_order') ?: 0,
                'is_active' => 1
            );

            $this->db->insert('interview_questions', $insert_data);
            redirect(base_url() . 'interview/questions?success=1');
        }

        $data['content'] = $this->load->view("interview/add_question", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function edit_question($id)
    {
        $data['user'] = $_SESSION['user'];
        $data['title'] = 'Edit Interview Question - ' . $this->template->title();
        $data['categories'] = $this->mymodel->selectWithQuery("SELECT * FROM interview_categories WHERE is_active = 1 ORDER BY category_name");
        $data['positions'] = $this->mymodel->selectWithQuery("
            SELECT positions.id, positions.name as name, level.name as level_name 
            FROM positions 
            INNER JOIN quest_levels level ON positions.level_id = level.id 
        ");
        $data['question'] = $this->mymodel->selectWithQuery("SELECT * FROM interview_questions WHERE id = $id")[0];

        if ($this->input->post()) {
            $dt = $this->input->post();
            $id_posisi = $dt['position_id'] ?? null;

            if ($id_posisi) {
                $query = $this->db->select('positions.name, quest_levels.name as level_name')
                                ->from('positions')
                                ->join('quest_levels', 'positions.level_id = quest_levels.id')
                                ->where('positions.id', $id_posisi)
                                ->get();

                $posisi = $query->row_array();

                if ($posisi) {
                    $position_text = $posisi['name'] . ' - ' . $posisi['level_name'];
                } else {
                    $position_text = 'Unknown Position';
                }
            }

            $update_data = array(
                'position_id' => $this->input->post('position_id'),
                'position' => $position_text ?? '',
                'category_id' => $this->input->post('category_id'),
                'question_text' => $this->input->post('question_text'),
                'question_type' => $this->input->post('question_type'),
                'score_range' => $this->input->post('score_range'),
                'options' => $this->input->post('options'),
                'sort_order' => $this->input->post('sort_order') ?: 0
            );

            $this->db->where('id', $id);
            $this->db->update('interview_questions', $update_data);
            redirect(base_url() . 'interview/questions?success=2');
        }

        $data['content'] = $this->load->view("interview/edit_question", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function delete_question()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $id = $this->input->post('id');

        try {
            $this->db->where('id', $id);
            $this->db->delete('interview_questions');

            if ($this->db->affected_rows() > 0) {
                echo json_encode(['status' => 'success', 'message' => 'Question deleted successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Question not found']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function summary()
    {
        $data['user'] = $_SESSION['user'];
        
        // Perbaikan: Validasi interview_id
        $interview_id = $this->uri->segment(3);
        
        // Debug: cek apakah interview_id ada
        if (empty($interview_id) || !is_numeric($interview_id)) {
            log_message('error', 'Invalid interview_id: ' . $interview_id);
            show_404();
            return;
        }
        
        $interview_type = $this->input->get('type');

        $qry = "1=1";
        if ($interview_type == 'interview_user') {
            $qry = "i.interview_status = 'interview_user'";
        } else if ($interview_type == 'interview_hr') {
            $qry = "i.interview_status = 'interview_hr'";
        } 

        $data['title'] = 'Interview Summary - ' . $this->template->title();
        
        // Perbaikan: Escape interview_id untuk keamanan
        $interview_id = (int) $interview_id; // Cast ke integer untuk keamanan
        
        // Get interview data
        $interview = $this->mymodel->selectWithQuery("SELECT i.*, j.nama_lengkap, j.posisi_dilamar, j.domisili, j.cv_portofolio, j.link_testcase
            FROM interview i
            INNER JOIN job_applications j ON i.id_job_applications = j.id 
            WHERE i.id = $interview_id AND $qry");

        if (empty($interview)) {
            if ($this->input->is_ajax_request()) {
                echo json_encode(['status' => 'error', 'message' => 'Interview not found']);
                return;
            }
            show_404();
        }
        
        $data['interview'] = $interview[0];
        
        // Get questions for this position
        $position = $this->db->escape_str($data['interview']['posisi_dilamar']);
        $data['questions'] = $this->mymodel->selectWithQuery("SELECT iq.*, ic.category_name
            FROM interview_questions iq
            INNER JOIN interview_categories ic ON iq.category_id = ic.id
            WHERE iq.position = '$position' AND iq.is_active = 1
            ORDER BY ic.id, iq.sort_order");

        // Get existing answers
        $existing_answers = $this->mymodel->selectWithQuery("SELECT * FROM interview_answers WHERE interview_id = $interview_id");
        $data['answers'] = array();
        foreach ($existing_answers as $answer) {
            $data['answers'][$answer['question_id']] = $answer;
        }

        // Handle AJAX request
        if ($this->input->is_ajax_request()) {
            $this->save_interview_summary($interview_id);
            return;
        }

        $data['content'] = $this->load->view("interview/summary", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    private function save_interview_summary($interview_id)
    {
        try {
            $post_data = $this->input->post();
            $rows_affected = 0;

            $question_ids = array_unique(array_merge(
                array_keys($post_data['scores'] ?? []),
                array_keys($post_data['notes'] ?? [])
            ));

            foreach ($question_ids as $question_id) {
                $answer_data = [
                    'interview_id' => $interview_id,
                    'question_id' => $question_id,
                    'answer_text' => '', // default jika tidak ada teks jawaban
                    'score' => $post_data['scores'][$question_id] ?? null,
                    'notes' => $post_data['notes'][$question_id] ?? null
                ];

                // Check if answer exists
                $existing = $this->mymodel->selectWithQuery(
                    "SELECT id FROM interview_answers WHERE interview_id = '$interview_id' AND question_id = '$question_id'"
                );

                if ($existing) {
                    $this->db->where('interview_id', $interview_id);
                    $this->db->where('question_id', $question_id);
                    $this->db->update('interview_answers', $answer_data);
                } else {
                    $this->db->insert('interview_answers', $answer_data);
                }

                $rows_affected += $this->db->affected_rows();
            }

            if ($this->input->is_ajax_request()) {
                if ($rows_affected > 0) {
                    echo json_encode(['status' => 'success', 'message' => 'Data berhasil disimpan']);
                } else {
                    echo json_encode(['status' => 'info', 'message' => 'Tidak ada perubahan data']);
                }
            } else {
                if ($rows_affected > 0) {
                    redirect(base_url() . 'interview/summary/' . $interview_id . '?success=1');
                } else {
                    redirect(base_url() . 'interview/summary/' . $interview_id . '?info=1');
                }
            }

        } catch (Exception $e) {
            log_message('error', 'Error saving interview summary: ' . $e->getMessage());

            if ($this->input->is_ajax_request()) {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data: ' . $e->getMessage()]);
            } else {
                redirect(base_url() . 'interview/summary/' . $interview_id . '?error=1');
            }
        }
    }

    public function get_summary()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $id = $this->input->post('id');
        $interview = $this->mymodel->selectWithQuery("SELECT summary_link FROM interview WHERE id = $id");
        
        if ($interview) {
            echo json_encode(['status' => 'success', 'summary' => $interview[0]['summary_link']]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Interview not found']);
        }
    }

    public function save_summary()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $id = $this->input->post('id');
        $summary = $this->input->post('summary');

        try {
            $this->db->where('id', $id);
            $result = $this->db->update('interview', ['summary_link' => $summary]);

            if ($this->db->affected_rows() >= 0) {
                echo json_encode(['status' => 'success', 'message' => 'Summary updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No changes made or record not found']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
}