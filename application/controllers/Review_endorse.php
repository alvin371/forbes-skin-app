<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Review_endorse extends CI_Controller
{
    public function index()
    {
        $keyword_category = $this->input->get('keyword_category') ?? "Username";
        $keyword = $this->input->get('keyword') ?? "";
        $campaign_filter = $this->input->get('campaign_filter') ?? "";
        $nama_creator = $this->input->get('nama_creator') ?? [];
        $pic = $this->input->get('pic') ?? [];
        
        $data['keyword_category'] = $keyword_category;
        $data['campaign_filter'] = $campaign_filter;
        $data['nama_creator'] = $nama_creator;
        $data['pic'] = $pic;

        $start_date = $this->input->get('start_date');
        $until_date = $this->input->get('until_date');
        
        if (empty($start_date)) {
            $start_date = date('Y-m-d', strtotime('-31 days'));
        }
        
        if (empty($until_date)) {
            $until_date = date('Y-m-d');
        }

        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;

        $data['template'] = $this->template;
        $data['title'] = 'Review Endorse - ' . $this->template->title();

        // Base query condition
        $qry = "e.status_endorse = 'Review' AND c.is_internal = 0 AND e.created_at BETWEEN '$start_date' AND '$until_date 23:59:59'";
        
        // Apply filters
        if (!empty($keyword)) {
            if ($keyword_category == "Username") {
                $qry .= " AND i.username LIKE '%$keyword%'";
            } else if ($keyword_category == "PIC") {
                $qry .= " AND e.pic LIKE '%$keyword%'";
            } else if ($keyword_category == "SPV") {
                $qry .= " AND c.spv LIKE '%$keyword%'";
            }
        }
        
        if (!empty($campaign_filter)) {
            $qry .= " AND e.id_campaign = '$campaign_filter'";
        }
        
        if (!empty($nama_creator)) {
            $nama_creator_str = "'" . implode("','", $nama_creator) . "'";
            $qry .= " AND e.nama_creator IN ($nama_creator_str)";
        }

        if (!empty($pic)) {
            $pic_conditions = [];
            foreach ($pic as $p) {
                if (!empty($p)) {
                    $pic_conditions[] = "e.pic LIKE '{$p}%'";
                }
            }

            if (!empty($pic_conditions)) {
                $qry .= " AND (" . implode(" OR ", $pic_conditions) . ")";
            }
        }

        // Get total count
        $total_query = $this->mymodel->selectWithQuery("
            SELECT COUNT(e.id) as total 
            FROM endorse e
            INNER JOIN endorse_campaign c ON c.id = e.id_campaign
            INNER JOIN influencer i ON i.id = e.influencer
            WHERE $qry
        ");
        $total_records = $total_query[0]['total'] ?? 0;

        // Handle pagination
        $per_page_options = [10, 20, 30, 50, 100, 500];
        $limit = in_array($this->input->get('limit'), $per_page_options) ? $this->input->get('limit') : 10;
        $current_page = max(1, intval($this->input->get('page') ?? 1));
        $offset = ($current_page - 1) * $limit;
        
        $data['page'] = ceil($total_records / $limit);
        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($total_records) . ' data ditemukan!</label></p>';

        // Setup pagination
        $url = base_url() . 'review_endorse/' . $this->template->get_param();
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $data['campaigns'] = $this->mymodel->selectWithQuery("SELECT id, title as name FROM endorse_campaign ORDER BY title ASC");

        $data['content'] = $this->load->view("review_endorse/all", $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function item()
    {
        $data['template'] = $this->template;
        
        // Get filter parameters
        $keyword_category = $this->input->get('keyword_category') ?? 'Username';
        $keyword = $this->input->get('keyword') ?? '';
        $campaign_filter = $this->input->get('campaign_filter') ?? '';
        $nama_creator = $this->input->get('nama_creator') ?? [];
        $pic = $this->input->get('pic') ?? [];
        $spv = $this->input->get('spv') ?? '';
        $start_date = $this->input->get('start_date');
        $until_date = $this->input->get('until_date');
        $pic_array = [];
        
        // Get sorting parameters
        $sort_column = $this->input->get('sort') ?? 'created_at';
        $sort_direction = $this->input->get('dir') ?? 'DESC';
        
        // Validate sort direction
        $sort_direction = strtoupper($sort_direction) === 'ASC' ? 'ASC' : 'DESC';
        
        // Validate sort column
        $allowed_columns = ['created_at', 'total_cost', 'nama_creator', 'rencana_at'];
        $sort_column = in_array($sort_column, $allowed_columns) ? $sort_column : 'created_at';

        // Set default dates if empty
        if (empty($start_date)) {
            $start_date = date('Y-m-d', strtotime('-31 days'));
        }
        
        if (empty($until_date)) {
            $until_date = date('Y-m-d');
        }

        // Base query condition
        $qry = "e.status_endorse = 'Review' AND c.is_internal = 0 AND e.created_at BETWEEN '$start_date' AND '$until_date 23:59:59'";
        
        // Apply filters
        if (!empty($campaign_filter)) {
            $qry .= " AND e.id_campaign = '$campaign_filter'";
        }
        
        if (!empty($keyword)) {
            if ($keyword_category == "Username") {
                $qry .= " AND i.username LIKE '%$keyword%'";
            } else if ($keyword_category == "PIC") {
                $qry .= " AND e.pic LIKE '%$keyword%'";
            } else if ($keyword_category == "SPV") {
                $qry .= " AND c.spv LIKE '%$keyword%'";
            }
        }

        if (!empty($pic)) {
            $pic = explode(',', $pic);
            $picList = implode("','", $pic);
            $qry .= " AND e.pic IN ('$picList')";
        }

        if (!empty($spv)) {
            $spv = explode(',', $spv);
            $spvList = implode("','", $spv);
            $qry .= " AND c.spv IN ('$spvList')";
        }

        // Pagination settings
        $per_page_options = [10, 20, 30, 50, 100, 500];
        $limit = in_array($this->input->get('limit'), $per_page_options) ? $this->input->get('limit') : 10;
        $current_page = max(1, intval($this->input->get('page') ?? 1));
        $offset = ($current_page - 1) * $limit;

        // Get total count for this filter first
        $total_query = $this->mymodel->selectWithQuery("
            SELECT COUNT(e.id) as total 
            FROM endorse e
            INNER JOIN endorse_campaign c ON c.id = e.id_campaign
            INNER JOIN influencer i ON i.id = e.influencer
            WHERE $qry
        ");
        $total_data = $total_query[0]['total'] ?? 0;

        // Get paginated data with sorting
        $query = $this->mymodel->selectWithQuery("
            SELECT 
                e.id AS endorse_id,
                e.*,
                c.title as campaign_name,
                c.spv as spv,
                i.username
            FROM 
                endorse e
            INNER JOIN 
                endorse_campaign c ON c.id = e.id_campaign
            INNER JOIN 
                influencer i ON i.id = e.influencer
            WHERE 
                $qry
            ORDER BY $sort_column $sort_direction
            LIMIT $offset, $limit
        ");

        // Prepare data for view
        $data['data'] = $query;
        $data['total_data'] = $total_data;
        $data['page'] = ceil($total_data / $limit);
        $data['current_page'] = $current_page;
        $data['limit'] = $limit;
        $data['per_page_options'] = $per_page_options;
        $data['start'] = $offset + 1;
        $data['end'] = min($offset + $limit, $total_data);
        
        // Pass filter and sorting values back to view
        $data['keyword_category'] = $keyword_category;
        $data['keyword'] = $keyword;
        $data['campaign_filter'] = $campaign_filter;
        $data['nama_creator'] = $nama_creator;
        $data['pic'] = $pic;
        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $data['sort_column'] = $sort_column;
        $data['sort_direction'] = $sort_direction;

        // Get table HTML
        $table_html = $this->load->view("review_endorse/item", $data, true);
        
        // Create notification text
        $notif_text = $this->template->separator_only($total_data) . ' data ditemukan!';

        // Return JSON response
        $response = array(
            'table_html' => $table_html,
            'notif_text' => $notif_text,
            'total_data' => $total_data
        );

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    public function get_pic_options()
    {
        $filter_pic = $this->mymodel->selectWithQuery("
            SELECT DISTINCT endorse.pic
            FROM endorse
            INNER JOIN endorse_campaign ON endorse_campaign.id = endorse.id_campaign
            WHERE status_endorse = 'Review'
            AND endorse.pic IS NOT NULL
            AND endorse.pic != ''
            AND endorse_campaign.is_internal = 0
            ORDER BY endorse.pic ASC
        ");

        $result = array_map(function($item) {
            if (is_array($item)) {
                return ['pic' => $item['pic']];
            }
            return ['pic' => $item->pic];
        }, $filter_pic);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }

    public function get_spv_options()
    {
        $filter_spv = $this->mymodel->selectWithQuery("
            SELECT DISTINCT spv
            FROM endorse_campaign
            WHERE spv IS NOT NULL
            AND spv != ''
            ORDER BY spv ASC
        ");

        $result = array_map(function($item) {
            if (is_array($item)) {
                return ['spv' => $item['spv']];
            }
            return ['spv' => $item->spv];
        }, $filter_spv);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }


    public function action_process()
    {
        $code = $_POST['code'];
        $user = $_SESSION['user'];
        $id_selected = $_POST['id_selected'] ?? '';

        if (empty($id_selected)) {
            echo $this->template->alert_danger('Pastikan kamu sudah memilih minimal 1 data!');
            return;
        }

        $ids = explode(',', $id_selected);
        if (empty($ids)) {
            echo $this->template->alert_danger('Format data yang dipilih tidak valid!');
            return;
        }

        $this->db->trans_begin();

        try {
            if ($code == "hapus_data") {
                $this->db->where_in('id', $ids)->delete('endorse');
                $msg = 'Hapus data berhasil!';
            } 
            else if ($code == "ubah_status") {
                $status = $this->input->post('status');

                foreach ($ids as $id) {
                    $dat = $this->mymodel->selectDataOne('endorse', ['id' => $id]);
                    $json = json_decode($dat['logs'], true);
                    if (!is_array($json)) {
                        $json = [];
                    }

                    $last_log = end($json);
                    $need_log = true;

                    if (isset($last_log['status_endorse']) && $last_log['status_endorse'] == $status) {
                        $need_log = false;
                    }

                    if ($need_log) {
                        $arr = [
                            'status'       => $status,
                            'created_by'   => $user['id'],
                            'created_text' => $user['code'],
                            'created_at'   => date("Y-m-d H:i:s"),
                            'is_review'    => 1
                        ];
                        $json[] = $arr;
                    }

                    $update_data = ['status_endorse' => $status];
                    if ($need_log) {
                        $update_data['logs'] = json_encode($json, true);
                    }

                    $this->db->where('id', $id);
                    $this->db->update('endorse', $update_data);
                }

                $msg = 'Ubah data berhasil!';
            }

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                echo $this->template->alert_danger('Proses gagal!');
            } else {
                $this->db->trans_commit();
                echo $this->template->alert_success($msg);
            }
        } catch (Exception $e) {
            $this->db->trans_rollback();
            echo $this->template->alert_danger('Terjadi kesalahan: ' . $e->getMessage());
        }
    }


    public function get_detail_data()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $id = $this->input->get('id');
        if (empty($id)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
            return;
        }

        $endorse = $this->mymodel->selectDataOne('endorse', ['id' => $id]);
        if (!$endorse) {
            echo json_encode(['status' => 'error', 'message' => 'Data not found']);
            return;
        }

        $campaign = $this->mymodel->selectDataOne('endorse_campaign', ['id' => $endorse['id_campaign']]);
        
        $influencer = $this->mymodel->selectDataOne('influencer', ['id' => $endorse['influencer']]);

        $data = $endorse;
        
        // Platform icons
        if ($data['platform'] == "Tiktok") {
            $data['img'] = base_url() . '/assets/img/icon/icon-tiktok.png';
        } else if ($data['platform'] == "Instagram") {
            $data['img'] = base_url() . '/assets/img/icon/icon-ig.png';
        } else if ($data['platform'] == "Youtube") {
            $data['img'] = base_url() . '/assets/img/icon/icon-youtube.png';
        } else if ($data['platform'] == "Facebook") {
            $data['img'] = base_url() . '/assets/img/icon/icon-fb.png';
        } else if ($data['platform'] == "Twitter") {
            $data['img'] = base_url() . '/assets/img/icon/icon-twitter.png';
        } else {
            $data['img'] = base_url() . '/assets/img/icon/icon-no.png';
        }

        // Format empty values
        $data['nama_creator'] = $data['nama_creator'] ?: "-";
        
        // Format dates
        $date_fields = ['posting_at', 'rencana_at', 'barang_dikirim_at', 'sync_at', 'created_at'];
        foreach ($date_fields as $field) {
            $data[$field] = $data[$field] ? date("d/m/Y", strtotime($data[$field])) : '-';
        }

        // Format links
        $data['link_brief'] = $data['link_brief'] ? '<a href="' . $data['link_brief'] . '" target="_blank">' . $data['link_brief'] . '</a>' : '-';
        $data['link_mou'] = $data['link_mou'] ? '<a href="' . $data['link_mou'] . '" target="_blank">' . $data['link_mou'] . '</a>' : '-';
        
        // Platform image with link
        if ($data['link_upload']) {
            $data['img'] = '<a href="' . $data['link_upload'] . '" target="_blank"><img style="width:40px;border-radius:10px;" class="mt-0" src="' . $data['img'] . '"></a>';
        } else {
            $data['img'] = '<img style="width:40px;border-radius:10px; filter: grayscale(100%)!important;" class="mt-0" src="' . $data['img'] . '">';
        }

        // Status colors
        $data['bg'] = '#e6e6e6';
        $data['clr'] = '#000';
        if (in_array($data['status_endorse'], array('Review'))) {
            $data['bg'] = '#e6e6e6';
        } else if (in_array($data['status_endorse'], array('Hold'))) {
            $data['bg'] = '#ffe599';
        } else if (in_array($data['status_endorse'], array('Acc', 'Pengajuan Payment'))) {
            $data['bg'] = '#f6b26b';
        } else if (in_array($data['status_endorse'], array('Barang Dikirim'))) {
            $data['bg'] = '#ffd0d0';
        } else if (in_array($data['status_endorse'], array('Draft Content'))) {
            $data['bg'] = '#d4edbc';
        } else if (in_array($data['status_endorse'], array('Posted Content'))) {
            $data['bg'] = '#7bd3ea';
        } else if (in_array($data['status_endorse'], array('Reject'))) {
            $data['bg'] = '#ea7b7b';
        }

        // Payment status colors
        $data['bg_payment'] = '#fff';
        $data['clr_payment'] = '#fff';
        if (in_array($data['status_payment'], array('DP'))) {
            $data['bg_payment'] = '#60bb55';
            $data['clr_payment'] = '#000';
        } else if (in_array($data['status_payment'], array('FP'))) {
            $data['bg_payment'] = '#8CCDEB';
            $data['clr_payment'] = '#000';
        } else if (in_array($data['status_payment'], array('Pengajuan Payment'))) {
            $data['bg_payment'] = '#e6e6e6';
            $data['clr_payment'] = '#000';
        } else if (in_array($data['pengajuan_status_payment'], array('Pengajuan Payment DP'))) {
            $data['bg_payment'] = '#C2FFC7';
            $data['clr_payment'] = '#000';
        } else if (in_array($data['pengajuan_status_payment'], array('Pengajuan Payment FP'))) {
            $data['bg_payment'] = '#A5BFCC';
            $data['clr_payment'] = '#000';
        }

        // Description
        $data['desc'] = $data['desc'] ?: '-';

        // SPV
        $data['spv'] = $data['spv'] ?: '-';

        // Creator data
        $data['type'] = $influencer['type'];
        $data['tipe_kontak'] = $influencer['tipe_kontak'];
        $data['url'] = $influencer['url'];
        $data['contact'] = $influencer['contact'];
        
        // Creator platform icon
        if ($data['type'] == "Tiktok") {
            $data['img_creator_1'] = base_url() . '/assets/img/icon/icon-tiktok.png';
        } else if ($data['type'] == "Instagram") {
            $data['img_creator_1'] = base_url() . '/assets/img/icon/icon-ig.png';
        } else if ($data['type'] == "Youtube") {
            $data['img_creator_1'] = base_url() . '/assets/img/icon/icon-youtube.png';
        } else if ($data['type'] == "Facebook") {
            $data['img_creator_1'] = base_url() . '/assets/img/icon/icon-fb.png';
        } else if ($data['type'] == "Twitter") {
            $data['img_creator_1'] = base_url() . '/assets/img/icon/icon-twitter.png';
        } else {
            $data['img_creator_1'] = base_url() . '/assets/img/icon/icon-no.png';
        }

        if ($data['url']) {
            $data['img_creator_1'] = '<a href="' . $data['url'] . '" target="_blank"><img style="width:40px;border-radius:10px;" class="mt-0" src="' . $data['img_creator_1'] . '"></a>';
        } else {
            $data['img_creator_1'] = '<img style="width:40px;border-radius:10px; filter: grayscale(100%)!important;" class="mt-0" src="' . $data['img_creator_1'] . '">';
        }

        // Contact icon
        if ($data['tipe_kontak'] == "WA") {
            $data['img_creator_2'] = base_url() . '/assets/img/icon/icon-wa.png';
        } else if ($data['tipe_kontak'] == "IG") {
            $data['img_creator_2'] = base_url() . '/assets/img/icon/icon-ig.png';
        } else if ($data['tipe_kontak'] == "Email") {
            $data['img_creator_2'] = base_url() . '/assets/img/icon/icon-email.png';
        } else if ($data['tipe_kontak'] == "HP") {
            $data['img_creator_2'] = base_url() . '/assets/img/icon/icon-phone.png';
        } else {
            $data['img_creator_2'] = base_url() . '/assets/img/icon/icon-no.png';
        }

        if ($data['contact']) {
            $data['img_creator_2'] = '<a href="' . $data['contact'] . '" target="_blank"><img style="width:40px;border-radius:10px;" class="mt-0" src="' . $data['img_creator_2'] . '"></a>';
        } else {
            $data['img_creator_2'] = '<img style="width:40px;border-radius:10px; filter: grayscale(100%)!important;" class="mt-0" src="' . $data['img_creator_2'] . '">';
        }

        // Add influencer metrics
        $data['cpm_2'] = $this->template->separator_only($influencer['cpm_2']);
        $data['avg_interaksi_2'] = $this->template->separator_only($influencer['avg_interaksi_2']);
        $data['avg_view_2'] = $this->template->separator_only($influencer['avg_view_2']);

        // Format numeric values
        $numeric_fields = ['total_cost', 'views', 'cpm', 'likes', 'comment', 'share_save'];
        foreach ($numeric_fields as $field) {
            $data[$field] = $this->template->separator_only($data[$field]);
        }

        echo json_encode(['status' => 'success', 'data' => $data]);
    }

    public function action()
    {
        $data['template'] = $this->template;
        $data['data'] = $this->input->get();
        
        // Set default values
        $data['data']['code'] = $data['data']['code'] ?? '';
        $data['data']['id'] = $data['data']['id'] ?? '';
        
        if ($data['data']['code'] == 'ubah_status') {
            $data['question'] = 'Ubah status data ini?';
            $data['btn'] = 'Ubah Status';
        } else {
            show_404();
        }

        $this->load->view('review_endorse/action', $data);
    }

    public function update_status()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $user = $_SESSION['user'];
        if (!in_array($user['role'], array('1', '2'))) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
            return;
        }

        $id = $this->input->post('id');
        $field = $this->input->post('field');
        $value = $this->input->post('value');

        if (empty($id) || empty($field)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
            return;
        }

        $allowed_fields = ['status_endorse'];
        if (!in_array($field, $allowed_fields)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid field']);
            return;
        }

        try {
            $update_data = [$field => $value];
            
            // Get current data
            $dat = $this->mymodel->selectDataOne('endorse', array('id' => $id));
            $json = json_decode($dat['logs'], true);
            $json_end = array();
            if ($json) {
                $json_end = end($json);
            }
            
            // Check if status changed
            if ($json_end['status'] != $value) {
                $arr = array();
                $arr['status'] = $value;
                $arr['created_by'] = $_SESSION['user']['id'];
                $arr['created_text'] = $_SESSION['user']['code'];
                $arr['created_at'] = DATE("Y-m-d H:i:s");
                $arr['is_review'] = 1;
                $json[] = $arr;
                $update_data['logs'] = json_encode($json, true);
            }

            $this->db->where('id', $id);
            $this->db->update('endorse', $update_data);

            echo json_encode(['status' => 'success', 'message' => 'Status updated successfully']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function get_history()
    {
        $data = $this->mymodel->selectWithQuery("
            SELECT logs, endorse_campaign.title AS campaign_name, endorse.nama_creator 
            FROM endorse 
            INNER JOIN endorse_campaign ON endorse.id_campaign = endorse_campaign.id
            WHERE logs LIKE '%is_review%' 
            ORDER BY endorse.id DESC
            LIMIT 15
        ");

        echo json_encode(['status' => 'success', 'data' => $data]);
    }
}
