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
     * Internal method to sync engagement data from external APIs
     * Extracted from sync_external_process for reusability
     *
     * @param int $id The influencer_dummy record ID
     * @return array Status and data from sync operation
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
            $ratecard = $data['ratecard'];

            $user = $this->session->userdata('user');

            // Get account ID and basic info
            $response = $this->template->get_account_id($type, $url);

            if (!$response['status']) {
                return ['status' => 'error', 'message' => 'Gagal mengambil account ID.'];
            }

            $update1 = [
                'updated_at' => date("Y-m-d H:i:s"),
                'updated_by' => strval($user['id']),
                'account_id' => $response['data']['account_id'],
                'img' => $response['data']['img'],
                'follower' => $response['data']['follower'],
                'media_count' => $response['data']['media_count']
            ];

            // Extract full_name from API response if available
            if (isset($response['data']['full_name'])) {
                $update1['full_name'] = $response['data']['full_name'];
            } elseif (isset($response['data']['nickname'])) {
                $update1['full_name'] = $response['data']['nickname'];
            } elseif (isset($response['data']['display_name'])) {
                $update1['full_name'] = $response['data']['display_name'];
            }

            $this->db->update('influencer_dummy', $update1, ['id' => $id]);

            // Get post list
            if ($type === "Tiktok") {
                preg_match('/@([a-zA-Z0-9._]+)/', $url, $matches);
                $username = $matches[1] ?? '';
                $response = $this->template->get_post_list($type, $update1['account_id']);
            } else {
                $response = $this->template->get_post_list($type, $update1['account_id']);
            }

            if (!$response['status']) {
                return ['status' => 'error', 'message' => 'Gagal mengambil data postingan.'];
            }

            // Calculate engagement metrics
            $like = $comment = $collect = $share = $view = 0;
            $i = 0;

            foreach ($response['data'] as $post) {
                $like += $post['like'];
                $comment += $post['comment'];
                $collect += $post['collect'];
                $share += $post['share'];
                $view += $post['view'];
                $i++;
                if ($i >= 10) break;
            }

            $avg_view = $i ? $view / $i : 0;
            $avg_interaksi = $i ? ($like + $comment + $collect + $share) / $i : 0;
            $er = ($avg_view > 0) ? ($avg_interaksi / $avg_view * 100) : 0;

            $update2 = [
                'sync_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s"),
                'updated_by' => strval($user['id']),
                'frequency_2' => $i,
                'view_2' => $view,
                'like_2' => $like,
                'collect_2' => $collect,
                'share_2' => $share,
                'comment_2' => $comment,
                'avg_view_2' => $avg_view,
                'avg_interaksi_2' => $avg_interaksi,
                'er' => $er,
                'cpm_2' => ($ratecard > 0 && $avg_view > 0) ? ($ratecard / $avg_view * 1000) : 0
            ];

            $this->db->update('influencer_dummy', $update2, ['id' => $id]);

            return [
                'status' => 'success',
                'message' => 'Sync data berhasil!',
                'data' => [
                    'follower' => $update1['follower'],
                    'cpm' => $update2['cpm_2'],
                    'er' => $update2['er'],
                    'avg_view' => $update2['avg_view_2'],
                    'ratecard' => $ratecard
                ]
            ];
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
        $ratecard = $data['ratecard'];

        $user = $this->session->userdata('user');

        $response = $this->template->get_account_id($type, $url);

        if (!$response['status']) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Gagal mengambil account ID.'
            ]);
            exit;
        }

        $update1 = [
            'updated_at' => date("Y-m-d H:i:s"),
            'updated_by' => strval($user['id']),
            'account_id' => $response['data']['account_id'],
            'img' => $response['data']['img'],
            'follower' => $response['data']['follower'],
            'media_count' => $response['data']['media_count']
        ];
        $this->db->update('influencer_dummy', $update1, ['id' => $id]);

        if ($type === "Tiktok") {
            preg_match('/@([a-zA-Z0-9._]+)/', $url, $matches);
            $username = $matches[1] ?? '';
            $response = $this->template->get_post_list($type, $update1['account_id']);
        } else {
            $response = $this->template->get_post_list($type, $update1['account_id']);
        }

        if (!$response['status']) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Gagal mengambil data postingan.'
            ]);
            exit;
        }

        $like = $comment = $collect = $share = $view = 0;
        $i = 0;

        foreach ($response['data'] as $post) {
            $like += $post['like'];
            $comment += $post['comment'];
            $collect += $post['collect'];
            $share += $post['share'];
            $view += $post['view'];
            $i++;
            if ($i >= 10) break;
        }

        $avg_view = $i ? $view / $i : 0;
        $avg_interaksi = $i ? ($like + $comment + $collect + $share) / $i : 0;
        $er = ($avg_view > 0) ? ($avg_interaksi / $avg_view * 100) : 0;

        $update2 = [
            'sync_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s"),
            'updated_by' => strval($user['id']),
            'frequency_2' => $i,
            'view_2' => $view,
            'like_2' => $like,
            'collect_2' => $collect,
            'share_2' => $share,
            'comment_2' => $comment,
            'avg_view_2' => $avg_view,
            'avg_interaksi_2' => $avg_interaksi,
            'er' => $er,
            'cpm_2' => ($ratecard > 0 && $avg_view > 0) ? ($ratecard / $avg_view * 1000) : 0
        ];

        $this->db->update('influencer_dummy', $update2, ['id' => $id]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Refresh data berhasil!',
            'data' => [
                'follower' => $update1['follower'],
                'cpm' => $update2['cpm_2'],
                'er' => $update2['er'],
                'avg_view' => $update2['avg_view_2'],
                'ratecard' => $ratecard
            ]
        ]);
        exit;
    }

    public function refresh_data($list_id)
    {
        try {
            $ids = explode(',', str_replace("'", "", $list_id)); 
            $user = $this->session->userdata('user');
            if (!$user || !isset($user['id'])) return;

            foreach ($ids as $id) {
                $query = $this->mymodel->selectWithQuery("SELECT * FROM influencer_dummy WHERE id = '$id'");
                if (!$query || count($query) === 0) continue;

                $data = $query[0];
                $url = $data['url'];
                $type = $data['type'];
                $ratecard = is_numeric($data['ratecard']) ? $data['ratecard'] : 0;

                $response = $this->template->get_account_id($type, $url);
                if (!$response['status'] || !isset($response['data']['account_id'])) continue;

                $update1 = [
                    'updated_at' => date("Y-m-d H:i:s"),
                    'updated_by' => strval($user['id']),
                    'account_id' => $response['data']['account_id'],
                    'img' => $response['data']['img'],
                    'follower' => $response['data']['follower'],
                    'media_count' => $response['data']['media_count']
                ];
                $this->db->update('influencer_dummy', $update1, ['id' => $id]);

                if ($type === "Tiktok") {
                    preg_match('/@([a-zA-Z0-9_]+)/', $url, $matches);
                    $username = $matches[1] ?? '';
                    if (empty($username)) continue;
                    $response = $this->template->get_post_list($type, $update1['account_id']);
                } else {
                    $response = $this->template->get_post_list($type, $update1['account_id']);
                }

                if (!$response['status']) continue;

                $like = $comment = $collect = $share = $view = 0;
                $i = 0;
                foreach ($response['data'] as $post) {
                    $like += $post['like'];
                    $comment += $post['comment'];
                    $collect += $post['collect'];
                    $share += $post['share'];
                    $view += $post['view'];
                    if (++$i >= 10) break;
                }

                $avg_view = $i ? $view / $i : 0;
                $avg_interaksi = $i ? ($like + $comment + $collect + $share) / $i : 0;
                $er = ($avg_view > 0) ? ($avg_interaksi / $avg_view * 100) : 0;
                $cpm = ($ratecard > 0 && $avg_view > 0) ? ($ratecard / $avg_view * 1000) : 0;

                $update2 = [
                    'sync_at' => date("Y-m-d H:i:s"),
                    'updated_at' => date("Y-m-d H:i:s"),
                    'updated_by' => strval($user['id']),
                    'frequency_2' => $i,
                    'view_2' => $view,
                    'like_2' => $like,
                    'collect_2' => $collect,
                    'share_2' => $share,
                    'comment_2' => $comment,
                    'avg_view_2' => $avg_view,
                    'avg_interaksi_2' => $avg_interaksi,
                    'er' => $er,
                    'cpm_2' => $cpm
                ];
                $this->db->update('influencer_dummy', $update2, ['id' => $id]);

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Refresh data berhasil!',
                    'data' => [
                        'follower' => $update1['follower'],
                        'cpm' => $update2['cpm_2'],
                        'er' => $update2['er'],
                        'avg_view' => $update2['avg_view_2'],
                        'ratecard' => $ratecard
                    ]
                ]);
            }
        } catch (Exception $e) {
            log_message('error', 'Refresh Data Error: ' . $e->getMessage());
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