<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Influencer_dummy extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper('url');
        $this->load->library('session');
        $this->load->library('user_agent');
    }

    public function index() 
    {
        $page = !empty($_GET['p']) ? $_GET['p'] : "Tiktok";
        $start_date = !empty($_GET['start_date']) ? $_GET['start_date'] : date("Y-m-01");
        $until_date = !empty($_GET['until_date']) ? $_GET['until_date'] : date("Y-m-d");

        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;

        $keyword_category = !empty($_GET['keyword_category']) ? $_GET['keyword_category'] : "Username";
        $data['keyword_category'] = $keyword_category;

        $keyword = isset($_GET['keyword']) ? $_GET['keyword'] : "";

        $filter = " WHERE 1=1";

        if ($keyword) {
            switch ($keyword_category) {
                case "Nama Creator":
                    $filter .= " AND `url` LIKE '%$keyword%' ";
                    break;
                case "Username":
                    $filter .= " AND username LIKE '%$keyword%' ";
                    break;
                case "URL":
                    $filter .= " AND url LIKE '%$keyword%' ";
                    break;
                case "Deskripsi":
                    $filter .= " AND `desc` LIKE '%$keyword%' ";
                    break;
                case "Platform":
                    $filter .= " AND type LIKE '%$keyword%' ";
                    break;
                case "Niche":
                    $filter .= " AND niche LIKE '%$keyword%' ";
                    break;
                case "PIC":
                    $filter .= " AND pic LIKE '%$keyword%' ";
                    break;
            }
        }

        if ($start_date && $until_date) {
            $filter .= " AND DATE(created_at) BETWEEN '$start_date' AND '$until_date'";
        }

        $qry = "SELECT * FROM influencer_dummy $filter AND type = '$page' ORDER BY id DESC";
        $data['influencers'] = $this->db->query($qry)->result();

        $count_qry = "SELECT COUNT(*) as total FROM influencer_dummy $filter AND type = '$page'";
        $count_result = $this->db->query($count_qry)->row();
        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($count_result->total) . ' data ditemukan!</label></p>';
        $data['page'] = $page;

        $data['brands'] = $this->db->select('code')->get('brand')->result();
        $data['pics'] = $this->db
            ->select('full_name')
            ->where_in('role', [1, 2, 11])
            ->where('id !=', 1)
            ->get('user')
            ->result();
        
        $data['niches'] = $this->mymodel->selectWithQuery("SELECT DISTINCT niche FROM niche");
        $data['filter_pic'] = $this->mymodel->selectWithQuery("SELECT DISTINCT pic FROM influencer_dummy");
        $data['filter_niche'] = $this->mymodel->selectWithQuery("SELECT DISTINCT niche FROM influencer_dummy");

        // Check queue statuses for displayed influencer IDs
        $data['queue_statuses'] = [];
        if (!empty($data['influencers'])) {
            $displayed_ids = array_map(function($inf) { return $inf->id; }, $data['influencers']);
            $placeholders = implode(',', $displayed_ids);
            $queue_rows = $this->db->query("
                SELECT sq1.entity_id, sq1.status
                FROM scraping_queue sq1
                INNER JOIN (
                    SELECT entity_id, MAX(id) as max_id
                    FROM scraping_queue
                    WHERE entity_type = 'influencer_dummy'
                    AND entity_id IN ($placeholders)
                    GROUP BY entity_id
                ) sq2 ON sq1.id = sq2.max_id
                WHERE sq1.status IN ('pending', 'submitted')
            ")->result_array();

            foreach ($queue_rows as $row) {
                $data['queue_statuses'][$row['entity_id']] = $row['status'];
            }
        }

        $data['template'] = $this->template;
        $data['title'] = 'Influencer Dummy - ' . $this->template->title();
        $data['content'] = $this->load->view('influencer_dummy/index', $data, true);

        $this->load->view('TemplateDashboard', $data);
    }


    public function save() {
        $data = $this->input->post();
        $type = $this->input->post('type');
        $auto_fetch = $this->input->post('auto_fetch'); // Get auto_fetch preference

        if (empty($data)) {
            $data = [
                'niche' => '',
                'username' => '',
                'brand' => '',
                'pic_text' => '',
                'type' => $type,
                'url' => '',
                'ratecard' => '',
                'status_reach' => 'Belum Reachout',
            ];
        }

        // Remove auto_fetch field - it's only for frontend logic, not for database
        unset($data['auto_fetch']);

        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['created_by'] = $this->session->userdata('user')['id'] ?? 0;
        $data['updated_by'] = $this->session->userdata('user')['id'] ?? 0;

        if (empty($data['id'])) {
            $this->db->insert('influencer_dummy', $data);
            $id = $this->db->insert_id();
        } else {
            $id = $data['id'];
            unset($data['id']);
            $this->db->where('id', $id)->update('influencer_dummy', $data);
        }

        if ($this->db->affected_rows() > 0 || isset($id)) {
            $saved_data = $this->db->where('id', $id)->get('influencer_dummy')->row_array();

            // Extract username from URL (same pattern as update_field method)
            if (!empty($saved_data['url'])) {
                if (preg_match('/@([a-zA-Z0-9_.]+)/', $saved_data['url'], $matches)) {
                    $extracted_username = $matches[1];
                    $this->db->where('id', $id)->update('influencer_dummy', [
                        'username' => $extracted_username,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'updated_by' => $this->session->userdata('user')['id'] ?? 0
                    ]);

                    // Update saved_data with new username
                    $saved_data['username'] = $extracted_username;
                }
            }

            // Auto-sync engagement data if URL is provided and auto_fetch is enabled
            $should_auto_sync = ($auto_fetch === 'true' || $auto_fetch === true);
            if ($should_auto_sync && !empty($saved_data['url'])) {
                $engagement_data = $this->_sync_engagement_data($id);
                if ($engagement_data && $engagement_data['status'] === 'success') {
                    // Refresh saved_data to get updated engagement metrics
                    $saved_data = $this->db->where('id', $id)->get('influencer_dummy')->row_array();
                }
            }

            echo json_encode([
                'status' => 'success',
                'data' => $saved_data
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Gagal menyimpan data'
            ]);
        }
    }

    /**
     * Internal method to sync engagement data via async queue (ScrapingBot)
     * Enqueues the record for background processing
     *
     * @param int $id The influencer_dummy record ID
     * @return array Status and data from enqueue operation
     */
    private function _sync_engagement_data($id)
    {
        try {
            $query = $this->mymodel->selectWithQuery("SELECT * FROM influencer_dummy WHERE id = '$id'");

            if (!$query || count($query) === 0) {
                return ['status' => 'error', 'message' => 'Data tidak ditemukan!'];
            }

            $data = $query[0];
            $url = $data['url'];
            $type = $data['type'] ? $data['type'] : 'Tiktok';

            if (empty($url)) {
                return ['status' => 'error', 'message' => 'URL belum diisi.'];
            }

            // Enqueue for async ScrapingBot processing with high priority
            $result = $this->template->enqueue_scrape('influencer_dummy', $id, $type, $url, 10);

            if ($result['status']) {
                return [
                    'status' => 'success',
                    'message' => 'Data sedang diproses. ' . $result['msg'],
                    'data' => []
                ];
            }

            return ['status' => 'error', 'message' => $result['msg']];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Exception: ' . $e->getMessage()];
        }
    }
    

    public function update_field() {
        $id = $this->input->post('id');
        $field = $this->input->post('field');
        $value = trim($this->input->post('value'));
    
        if (in_array($field, ['username', 'url'])) {
            $duplicate = $this->db
                ->where($field, $value)
                ->where('id !=', $id)
                ->get('influencer_dummy')
                ->num_rows();
    
            if ($duplicate > 0) {
                echo json_encode([
                    'status' => 'error',
                    'message' => ucfirst($field) . ' sudah digunakan oleh data lain.'
                ]);
                die;
            }
        }
    
        $this->db->where('id', $id)
                 ->set($field, $value)
                 ->set('updated_at', date('Y-m-d H:i:s'))
                 ->set('updated_by', $this->session->userdata('user')['id'] ?? 0);
    
        if ($field === 'contact' && stripos($value, 'wa.me/') === 0) {
            $this->db->set('tipe_kontak', 'WA');
        }

        if ($field === 'ratecard') {
            $this->db->set('status_reach', 'Sudah Reachout');
        }

        if ($field === 'url') {
            if (preg_match('/@([a-zA-Z0-9_.]+)/', $value, $matches)) {
                $extracted_username = $matches[1];
                $this->db->set('username', $extracted_username);
            }
        }

    
        $update = $this->db->update('influencer_dummy');
        $error = $this->db->error();
    
        if ($error['code'] !== 0) {
            echo json_encode([
                'status' => 'error',
                'message' => $error['message']
            ]);
        } elseif ($update) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Query tidak berhasil dijalankan.'
            ]);
        }
    }
    
    

    public function delete($id) {
        $this->db->where('id', $id)->delete('influencer_dummy');
        echo json_encode([
            'status' => $this->db->affected_rows() > 0 ? 'success' : 'error'
        ]);
    }

    public function generate($id)
    {
        $row = $this->db->get_where('influencer_dummy', ['id' => $id])->row_array();
    
        if (!$row) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Data tidak ditemukan di influencer_dummy.'
            ]);
            return;
        }

        $exists = $this->db->get_where('influencer', ['username' => $row['username'], 'type' => $row['type']])->num_rows();

        if ($exists > 0) {
            echo json_encode([
                'status' => 'duplicate',
                'message' => 'Data dengan username dan platform tersebut sudah ada di tabel influencer.'
            ]);
            return;
        }
    
        $this->db->where('id', $id)->update('influencer_dummy', ['is_generated' => 1]);
    
        unset($row['id']);
        $row['created_at'] = date('Y-m-d H:i:s');
    
        $this->db->insert('influencer', $row);
    
        echo json_encode([
            'status' => 'success',
            'message' => 'Data berhasil disalin ke tabel influencer.'
        ]);
    }

    public function generate_all($list_id)
    {
        $list_id = str_replace("'", "", $list_id); // Hilangkan tanda kutip satu
        $ids = array_map('trim', explode(',', $list_id));

        $results = [];

        foreach ($ids as $singleId) {
            $row = $this->db->get_where('influencer_dummy', ['id' => $singleId])->row_array();

            if (!$row) {
                $results[] = [
                    'id' => $singleId,
                    'status' => 'error',
                    'message' => 'Data tidak ditemukan di influencer_dummy.'
                ];
                continue;
            }

            $exists = $this->db->get_where('influencer', ['username' => $row['username']])->num_rows();

            if ($exists > 0) {
                $results[] = [
                    'id' => $singleId,
                    'username' => $row['username'],
                    'status' => 'duplicate',
                    'message' => 'Data dengan username ' . $row['username'] . ' tersebut sudah ada di tabel influencer.'
                ];
                continue;
            }

            $this->db->where('id', $singleId)->update('influencer_dummy', ['is_generated' => 1]);

            unset($row['id']);
            $row['created_at'] = date('Y-m-d H:i:s');

            $this->db->insert('influencer', $row);

            $results[] = [
                'id' => $singleId,
                'status' => 'success',
                'message' => 'Data berhasil disalin ke tabel influencer.'
            ];
        }

        return $results;
    }


    


    public function sync_external_process()
    {
        header('Content-Type: application/json');

        $id = $this->input->post('id');
        if (!$id) {
            echo json_encode([
                'status' => 'error',
                'message' => 'ID tidak ditemukan!'
            ]);
            exit;
        }

        $query = $this->mymodel->selectWithQuery("SELECT * FROM influencer_dummy WHERE id = '$id'");

        if (!$query || count($query) === 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Data tidak ditemukan!'
            ]);
            exit;
        }

        $data = $query[0];
        $url = $data['url'];
        $type = $data['type'] ? $data['type'] : 'Tiktok';

        if (empty($url)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'URL belum diisi.'
            ]);
            exit;
        }

        if ($type == 'Tiktok') {
            // Synchronous via RapidAPI
            $result = $this->template->syncTiktokProfile('influencer_dummy', $id, $type, $url);
            if ($result['status']) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Data berhasil disinkronkan.'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => $result['msg']
                ]);
            }
        } else {
            // Instagram: async via ScrapingBot
            $result = $this->template->enqueue_scrape('influencer_dummy', $id, $type, $url, 10);
            if ($result['status']) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Refresh data sedang diproses. Data akan diperbarui dalam beberapa menit.'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => $result['msg']
                ]);
            }
        }
        exit;
    }

    public function refresh_data($list_id)
    {
        try {
            $ids = explode(',', str_replace("'", "", $list_id));

            $enqueued = 0;
            foreach ($ids as $id) {
                $id = trim($id);
                if (empty($id)) continue;

                $query = $this->mymodel->selectWithQuery("SELECT id, type, url FROM influencer_dummy WHERE id = '$id'");
                if (!$query || count($query) === 0) continue;

                $data = $query[0];
                $type = $data['type'] ? $data['type'] : 'Tiktok';

                if (empty($data['url'])) continue;

                // TikTok: sync via RapidAPI, Instagram: async via ScrapingBot
                if ($type == 'Tiktok') {
                    $result = $this->template->syncTiktokProfile('influencer_dummy', $data['id'], $type, $data['url']);
                } else {
                    $result = $this->template->enqueue_scrape('influencer_dummy', $data['id'], $type, $data['url'], 10);
                }
                if ($result['status']) $enqueued++;
            }

            echo json_encode([
                'status' => 'success',
                'message' => "$enqueued data berhasil diproses."
            ]);
        } catch (Exception $e) {
            log_message('error', 'Refresh Data Error: ' . $e->getMessage());
            echo json_encode([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
    }


    public function action()
    {
        $id_selected_v2 = $_POST['id_selected_v2'];
        $id_selected = $_POST['id_selected'];
        if ($id_selected) {
            $id = explode(',', $id_selected);
        }
        $code = $_GET['code'];
        $data['data']['id'] = $id;
        $data['data']['code'] = $code;
        if ($code == "hapus_data") {
            $data['question'] = "Apakah kamu yakin ingin menghapus data influencer ini?";
            $data['btn'] = "Hapus Data";
        } else if ($code == "refresh_data") {
            $data['question'] = "Apakah kamu yakin ingin merefresh data influencer ini?";
            $data['btn'] = "Refresh Data";
        } else if ($code == "generate_data") {
            $data['question'] = "Apakah kamu yakin ingin generate data influencer ini?";
            $data['btn'] = "Generate Data";
        } else if ($code == "nonaktifkan_data") {
            $data['question'] = "Apakah kamu yakin ingin menonaktifkan data influencer ini?";
            $data['btn'] = "Nonaktifkan Data";
        }
        $this->load->view("influencer_dummy/action", $data);
    }

    public function action_process()
    {
        $list_id = "";
        $code = $_POST['code'];
        $user = $_SESSION['user'];

        $id_selected = $_POST['id_selected'];
        if ($id_selected) {
            $id = explode(',', $id_selected);
        }
        if ($code == "hapus_data") {
            foreach ($id as $k => $v) {
                $list_id .= "'" . $v . "',";
            }

            $list_id = substr($list_id, 0, -1);

            if ($list_id) {
                $dt = array();
                $this->db->delete('influencer_dummy', "id IN ($list_id)");
                $msg = 'Hapus data berhasil!';
                echo $this->template->alert_success($msg);
            } else {
                $msg = 'Pastikan kamu sudah memilih minimal 1 data!';
                echo $this->template->alert_danger($msg);
            }
        } else if ($code == "refresh_data") {
            foreach ($id as $k => $v) {
                $list_id .= "'" . $v . "',";
            }
            $list_id = substr($list_id, 0, -1);

            if ($list_id) {
                $inf = $this->mymodel->selectWithQuery("SELECT id FROM influencer_dummy WHERE id IN ($list_id)");
                
                $postData = array(
                    'ids' => $id 
                );
                
                $url = base_url() . '/influencer-dummy/action-process';
                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 1,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => http_build_query($postData),
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/x-www-form-urlencoded'
                    ),
                ));

                $response = curl_exec($curl);
                curl_close($curl);

                $msg = 'Refresh data berhasil! Silahkan tunggu beberapa saat hingga data diperbarui!';
                echo $this->template->alert_success($msg);
            } else {
                $msg = 'Pastikan kamu sudah memilih minimal 1 data!';
                echo $this->template->alert_danger($msg);
            }
        } else if ($code == "generate_data") {
            foreach ($id as $k => $v) {
                $list_id .= "'" . $v . "',";
            }

            $list_id = substr($list_id, 0, -1);

            if ($list_id) {
                $result = $this->generate_all($list_id);
            
                $errorMessages = [];
            
                foreach ($result as $r) {
                    if ($r['status'] !== 'success') {
                        $errorMessages[] = "{$r['message']}";
                    }
                }
            
                if (!empty($errorMessages)) {
                    $msg = implode('<br>', $errorMessages);
                    echo $this->template->alert_danger($msg);
                } else {
                    $msg = 'Generate data berhasil!';
                    echo $this->template->alert_success($msg);
                }
            } else {
                $msg = 'Pastikan kamu sudah memilih minimal 1 data!';
                echo $this->template->alert_danger($msg);
            }
            
        } else if ($code == "nonaktifkan_data") {
            foreach ($id as $k => $v) {
                $list_id .= "'" . $v . "',";
            }
            $list_id = substr($list_id, 0, -1);
            $this->db->update('influencer_dummy', ['status' => 'Nonaktif'], "id IN ($list_id)");
            $msg = 'Nonaktifkan data berhasil!';
            echo $this->template->alert_success($msg);
        }
    }

    /**
     * Check queue status for given influencer_dummy IDs
     * Returns status map and updated data for completed items
     */
    public function check_queue_status()
    {
        header('Content-Type: application/json');

        $ids_param = $this->input->post('ids');
        if (empty($ids_param)) {
            echo json_encode(['status' => 'error', 'message' => 'No IDs provided']);
            return;
        }

        // Accept comma-separated string or array
        if (is_array($ids_param)) {
            $ids = array_map('intval', $ids_param);
        } else {
            $ids = array_map('intval', explode(',', $ids_param));
        }
        $ids = array_filter($ids);

        if (empty($ids)) {
            echo json_encode(['status' => 'error', 'message' => 'No valid IDs']);
            return;
        }

        // Get latest queue entry for each entity_id
        $placeholders = implode(',', $ids);
        $queue_results = $this->db->query("
            SELECT sq1.entity_id, sq1.status, sq1.error_message
            FROM scraping_queue sq1
            INNER JOIN (
                SELECT entity_id, MAX(id) as max_id
                FROM scraping_queue
                WHERE entity_type = 'influencer_dummy'
                AND entity_id IN ($placeholders)
                GROUP BY entity_id
            ) sq2 ON sq1.id = sq2.max_id
        ")->result_array();

        $result = [];
        $completed_ids = [];

        foreach ($queue_results as $row) {
            $eid = $row['entity_id'];
            $result[$eid] = [
                'queue_status' => $row['status'],
                'error_message' => $row['error_message'] ?? null,
            ];
            if ($row['status'] === 'completed') {
                $completed_ids[] = $eid;
            }
        }

        // For completed items, fetch updated engagement data
        if (!empty($completed_ids)) {
            $this->db->where_in('id', $completed_ids);
            $influencers = $this->db->get('influencer_dummy')->result_array();

            foreach ($influencers as $inf) {
                $id = $inf['id'];
                $result[$id]['data'] = [
                    'follower' => (int)($inf['follower'] ?? 0),
                    'cpm' => floatval($inf['cpm_2'] ?? 0),
                    'avg_view' => floatval($inf['avg_view_2'] ?? 0),
                    'er' => floatval($inf['er'] ?? 0),
                    'ratecard' => floatval($inf['ratecard'] ?? 0),
                ];
            }
        }

        echo json_encode(['status' => 'success', 'results' => $result]);
    }

    public function add_form()
    {
        $page = !empty($_GET['p']) ? $_GET['p'] : "Tiktok";
        $platform = ucfirst($page);

        $data['platform'] = $platform;
        $data['brands'] = $this->db->select('code')->get('brand')->result();
        $data['pics'] = $this->db
            ->select('full_name')
            ->where_in('role', [1, 2, 11])
            ->where('id !=', 1)
            ->get('user')
            ->result();
        $data['niches'] = $this->mymodel->selectWithQuery("SELECT DISTINCT niche FROM niche");

        $this->load->view('influencer_dummy/add_form', $data);
    }

    public function edit_niche()
    {
        $data['niches'] = $this->db->order_by('niche', 'ASC')->get('niche')->result();
        $this->load->view('influencer_dummy/edit_niche_form', $data);
    }
    
    public function save_niche()
    {
        $this->load->library('form_validation');
        
        $this->form_validation->set_rules('niche', 'Niche', 'required|is_unique[niche.niche]|max_length[255]');
        
        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('errors', validation_errors());
        } else {
            $niche = $this->input->post('niche');
            $this->db->insert('niche', array('niche' => $niche));
            $this->session->set_flashdata('message', 'Niche added successfully');
        }
        
        redirect($this->agent->referrer());
    }
    
    // Menghapus niche
    public function delete_niche($niche)
    {
        $decodedNiche = urldecode($niche);
        $this->db->where('niche', $decodedNiche)->delete('niche');
        
        // Return simple success response
        echo 'success';
    }


}