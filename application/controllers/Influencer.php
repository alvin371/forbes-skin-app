<?php

defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/BaseController.php';

class Influencer extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('mymodel');
        $this->load->library('template');

        // Set public methods (no permission required)
        $this->set_public_methods([]);

        // Override method-to-action mapping if needed
        $this->set_method_permissions([
            'remove' => 'delete',
            'sync' => 'create',
            'action' => 'edit',
            'action_process' => 'edit'
        ]);
    }

    public function index()
    {

        if ($_GET['keyword_category']) {
            $keyword_category = $_GET['keyword_category'];
        } else {
            $keyword_category = "Username";
        }
        $data['keyword_category'] = $keyword_category;
        $keyword = $_GET['keyword'];

        if ($_GET['start_date'] == "") {
            $start_date = DATE("Y-m-01");
        } else {
            $start_date = $_GET['start_date'];
        }
        if ($_GET['until_date'] == "") {
            $until_date = DATE("Y-m-d");
        } else {
            $until_date = $_GET['until_date'];
        }

        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $data['brand'] = $brand;

        $data['title'] = 'Influencer - ' . $this->template->title();

        $qry = "";
        $qry = " 1=1 ";

        $ids = $_GET['ids'];
        $data['ids'] = $ids;
        if ($ids) {
            $qry .= " AND id  IN ($ids) ";
        }


        if ($brand) {
            $qry .= " AND brand = '$brand' ";
        }

        $status = $_GET['status'];
        $statusArray = $status ? explode(',', $status) : [];
        $text = '';
        foreach ($statusArray as $k => $v) {
            $text .= "'" . $v . "',";
        }
        $text = substr($text, 0, -1);

        if ($text) {
            $qry .= " AND status_reach IN ($text) ";
        }

        if ($keyword) {
            if ($keyword_category == "Nama Creator") {
                $qry .= " AND full_name LIKE '%$keyword%' ";
            } else if ($keyword_category == "Username") {
                $qry .= " AND influencer.username LIKE '%$keyword%' ";
            } else if ($keyword_category == "URL") {
                $qry .= " AND influencer.url LIKE '%$keyword%' ";
            } else if ($keyword_category == "Keterangan") {
                $qry .= " AND influencer.desc LIKE '%$keyword%' ";
            } else if ($keyword_category == "Platform") {
                $qry .= " AND influencer.type LIKE '%$keyword%' ";
            } else if ($keyword_category == "Niche") {
                $qry .= " AND influencer.niche LIKE '%$keyword%' ";
            } else if ($keyword_category == "PIC") {
                $qry .= " AND influencer.pic LIKE '%$keyword%' ";
            }
        }

        $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) AS count
        FROM influencer
        WHERE $qry
        ");

        $data['page'] = CEIL($query[0]['count'] / 30);

        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query[0]['count']) . ' data ditemukan!</label></p>';

        $item = '';

        $current_page = intval($_GET['page']);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $url = base_url() . '/influencer/' . $this->template->get_param();
        $data['url_1'] = $this->template->get_param_without_status($url);
        $data['url_2'] = $this->template->get_param_without_keyword_category($url);
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $data['content'] = $this->load->view("influencer/all", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function item()
    {
        $data['template'] = $this->template;

        $keyword_category = $_GET['keyword_category'] ?? "Username";
        $data['keyword_category'] = $keyword_category;
        $keyword = $_GET['keyword'] ?? '';

        $start_date = $_GET['start_date'] ?? date('Y-m-d');
        $until_date = $_GET['until_date'] ?? date('Y-m-d');
        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;

        $qry = "";
        $qry = " 1=1 ";

        $ids = $_GET['ids'];
        $data['ids'] = $ids;
        if ($ids) {
            $qry .= " AND id  IN ($ids) ";
        }


        if ($brand) {
            $qry .= " AND brand = '$brand' ";
        }

        $status = $_GET['status'];
        $statusArray = $status ? explode(',', $status) : [];
        $text = '';
        foreach ($statusArray as $k => $v) {
            $text .= "'" . $v . "',";
        }
        $text = substr($text, 0, -1);

        if ($text) {
            $qry .= " AND status_reach IN ($text) ";
        }

        if ($keyword) {
            if ($keyword_category == "Nama Creator") {
                $qry .= " AND full_name LIKE '%$keyword%' ";
            } else if ($keyword_category == "Username") {
                $qry .= " AND influencer.username LIKE '%$keyword%' ";
            } else if ($keyword_category == "URL") {
                $qry .= " AND influencer.url LIKE '%$keyword%' ";
            } else if ($keyword_category == "Keterangan") {
                $qry .= " AND influencer.desc LIKE '%$keyword%' ";
            } else if ($keyword_category == "Platform") {
                $qry .= " AND influencer.type LIKE '%$keyword%' ";
            } else if ($keyword_category == "Niche") {
                $qry .= " AND influencer.niche LIKE '%$keyword%' ";
            } else if ($keyword_category == "PIC") {
                $qry .= " AND influencer.pic LIKE '%$keyword%' ";
            }
        }

        $per_page_options = [10, 20, 30, 50, 100, 500];
        $limit = isset($_GET['limit']) && in_array($_GET['limit'], $per_page_options) 
                ? (int)$_GET['limit'] 
                : 10;
        $data['limit'] = $limit;
        $data['per_page_options'] = $per_page_options;

        $current_page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($current_page - 1) * $limit;

        $allowed_columns = ['id', 'username', 'follower', 'avg_view', 'cpm', 'avg_view_2', 'cpm_2', 'ratecard', 'pic'];
        $sort_column = in_array($_GET['sort_column'] ?? '', $allowed_columns) 
                    ? $_GET['sort_column'] 
                    : 'id';
        $sort_order = strtoupper($_GET['sort_order'] ?? '') === 'ASC' 
                    ? 'ASC' 
                    : 'DESC';

        $count_query = $this->mymodel->selectWithQuery("SELECT COUNT(*) as total FROM influencer WHERE $qry");
        $total_data = $count_query[0]['total'] ?? 0;
        $data['total_data'] = $total_data;
        $data['page'] = ceil($total_data / $limit);
        $data['current_page'] = $current_page;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM influencer
            WHERE $qry 
            ORDER BY $sort_column $sort_order
            LIMIT $offset, $limit
        ");

        $data['data'] = $query;
        $data['start'] = $offset + 1;
        $data['end'] = min($offset + $limit, $total_data);

        $this->load->view("influencer/item", $data);
    }

    public function edit()
    {
        $id = $_GET['id'];

        $sqlInf = "
            SELECT i.*, u.full_name AS created_by_name
            FROM influencer i
            LEFT JOIN `user` u ON u.id = i.created_by
            WHERE i.id = '$id'
            LIMIT 1
        ";
        $rowsInf = $this->mymodel->selectWithQuery($sqlInf);
        if (empty($rowsInf)) {
            show_error('Data influencer tidak ditemukan', 404);
            return;
        }
        $inf = $rowsInf[0];
        $data['data'] = $inf;

        $username = $this->db->escape_str($inf['username'] ?? '');
        $sqlDummy = "
            SELECT d.created_at AS d_created_at, u2.full_name AS d_created_by_name
            FROM influencer_dummy d
            LEFT JOIN `user` u2 ON u2.id = d.created_by   -- join berdasarkan id
            WHERE d.username = '$username' AND d.is_generated = 1
            ORDER BY d.created_at DESC
            LIMIT 1
        ";
        $rowsDummy = $this->mymodel->selectWithQuery($sqlDummy);

        if (!empty($rowsDummy)) {
            $d   = $rowsDummy[0];

            if (empty($d['d_created_by_name']) || $d['d_created_by_name'] == 1) {
                $who = 'System';
            } else {
                $who = $d['d_created_by_name'];
            }

            $when = date('d M Y H:i', strtotime($d['d_created_at']));
            $data['log_text'] = "Data di <b>generate</b> dari listing oleh <b>{$who}</b> pada <b>{$when}</b>.";
        } else {
            if (empty($inf['created_by_name']) || $inf['created_by'] == 1) {
                $who = 'System';
            } else {
                $who = $inf['created_by_name'];
            }

            $when = date('d M Y H:i', strtotime($inf['created_at']));
            $data['log_text'] = "Data <b>ditambahkan</b> oleh <b>{$who}</b> pada <b>{$when}</b>.";
        }


        $data['brand'] = $this->mymodel->selectWithQuery("SELECT * FROM brand ORDER BY name ASC");

        $data['pic']   = $this->mymodel->selectWithQuery("SELECT * FROM `user` ORDER BY full_name ASC");

        $this->load->view("influencer/edit", $data);
    }



    public function update()
    {
        $user = $_SESSION['user'];

        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $dt['updated_at'] = DATE("Y-m-d H:i:s");
        $dt['updated_by'] = $user['id'];

        if (is_array($dt['pic'])) {
            $dt['pic'] = implode(', ', $dt['pic']);
        }

        if (is_array($dt['brand'])) {
            $dt['brand'] = implode(', ', $dt['brand']);
        }

        if ($dt['status_reach'] == 'Belum Reachout' && !empty($dt['ratecard'])) {
            $dt['status_reach'] = 'Sudah Reachout';
        }

        $nama_creator = $dt['username'];

        $count = $this->mymodel->selectWithQuery("SELECT COUNT(endorse.id) as total, title 
                FROM endorse 
                INNER JOIN endorse_campaign ON endorse.id_campaign = endorse_campaign.id
                WHERE nama_creator = '$nama_creator' AND status_endorse IN ('Posted Content', 'Draft Content') 
                GROUP BY title; ");

        if (count($count) == 1 && $dt['status_reach'] != 'Affiliate') {
            $dt['status_reach'] = 'Pernah Kerjasama';
        } else if (count($count) > 1 && $dt['status_reach'] != 'Affiliate') {
            $dt['status_reach'] = 'Repeat Kerjasama';
        }

        if ($this->db->update('influencer', $dt, array('id' => $id))) {
            $msg = 'Update data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Update data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }


    public function create()
    {
        $data['data'] = array();

        $query = $this->mymodel->selectWithQuery("SELECT * FROM user ORDER BY full_name ASC");

        $data['pic'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM brand ORDER BY name ASC");

        $data['brand'] = $query;
        $data['user'] = $_SESSION['user'];

        $this->load->view("influencer/create", $data);
    }


    public function store()
    {
        $user = $_SESSION['user'];

        $id = $_POST['id'];
        $dt = $_POST['dt'];

        $dt['username']   = trim(strval($dt['username'] ?? ''));
        $dt['created_at'] = date("Y-m-d H:i:s");
        $dt['created_by'] = $user['id'];

        if (is_array($dt['pic']))   { $dt['pic']   = implode(', ', $dt['pic']); }
        if (is_array($dt['brand'])) { $dt['brand'] = implode(', ', $dt['brand']); }

        $username     = $dt['username']; 
        $bank         = $_POST['dt']['bank'] ?? '';
        $no_rekening  = $_POST['dt']['no_rekening'] ?? '';
        $desc         = $_POST['dt']['desc'] ?? '';
        $status_reach = $_POST['dt']['status_reach'] ?? '';

        $exists = $this->db->where('username', $username)
                        ->limit(1)
                        ->get('influencer')
                        ->num_rows() > 0;

        if ($exists) {
            $msg = 'Data dengan username tersebut sudah ada';
            echo $this->template->alert_danger($msg);
            return; 
        }

        $dt2 = [
            'nama_creator' => $username,
            'bank'         => $bank,
            'no_rekening'  => $no_rekening,
            'description'  => $desc,
        ];

        if ($status_reach === 'Belum Reachout' && !empty($dt['ratecard'])) {
            $dt['status_reach'] = 'Sudah Reachout';
        }

        $this->db->trans_begin();

        try {
            $this->db->insert('influencer', $dt);

            $this->db->where('nama_creator', $username);
            $query = $this->db->get('influencer_cost');

            if ($query->num_rows() == 0) {
                $this->db->insert('influencer_cost', $dt2);
            } else {
                $this->db->where('nama_creator', $username);
                $this->db->update('influencer_cost', $dt2);
            }

            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Transaksi gagal!');
            }

            $this->db->trans_commit();
            $msg = 'Tambah data berhasil!';
            echo $this->template->alert_success($msg);
        } catch (Exception $e) {
            $this->db->trans_rollback();
            $msg = 'Tambah data tidak berhasil: ' . $e->getMessage();
            echo $this->template->alert_danger($msg);
        }
    }


    public function sync_all()
    {
        $id = $_GET['id'];
        $data['param'] = json_encode($_GET, true);
        $this->load->view("influencer/sync_all", $data);
    }

    public function sync_all_process()
    {
        $user = $_SESSION['user'];
        $dt = json_decode($_POST['param'], true);

        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $data['brand'] = $dt['brand'];

        if ($dt['keyword_category']) {
            $keyword_category = $dt['keyword_category'];
        } else {
            $keyword_category = "Username";
        }
        $data['keyword_category'] = $keyword_category;
        $keyword = $dt['keyword'];

        if ($_GET['start_date']) {
            $start_date = $dt['start_date'];
        } else {
            $start_date = DATE('Y-m-d');
        }
        if ($_GET['until_date']) {
            $until_date = $dt['until_date'];
        } else {
            $until_date = DATE('Y-m-d');
        }
        $qry = "";
        $qry = " 1=1 ";

        $ids = $_GET['ids'];
        $data['ids'] = $ids;
        if ($ids) {
            $qry .= " AND id  IN ($ids) ";
        }

        if ($brand) {
            $qry .= " AND brand = '$brand' ";
        }


        $status = $_GET['status'];
        $statusArray = $status ? explode(',', $status) : [];
        $text = '';
        foreach ($statusArray as $k => $v) {
            $text .= "'" . $v . "',";
        }
        $text = substr($text, 0, -1);

        if ($text) {
            $qry .= " AND status_reach IN ($text) ";
        }

        if ($keyword) {
            if ($keyword_category == "Nama Creator") {
                $qry .= " AND full_name LIKE '%$keyword%' ";
            } else if ($keyword_category == "Username") {
                $qry .= " AND influencer.username LIKE '%$keyword%' ";
            } else if ($keyword_category == "URL") {
                $qry .= " AND influencer.url LIKE '%$keyword%' ";
            } else if ($keyword_category == "Keterangan") {
                $qry .= " AND influencer.desc LIKE '%$keyword%' ";
            } else if ($keyword_category == "Platform") {
                $qry .= " AND influencer.type LIKE '%$keyword%' ";
            } else if ($keyword_category == "Niche") {
                $qry .= " AND influencer.niche LIKE '%$keyword%' ";
            } else if ($keyword_category == "PIC") {
                $qry .= " AND influencer.pic LIKE '%$keyword%' ";
            }
        }

        $mode = $_GET['mode'];
        $ids = $_GET['ids'];
        if ($mode == "refresh_data") {
            $qry = " id IN ($ids) ";
        }

        $list = $this->mymodel->selectWithQuery("SELECT * FROM influencer WHERE $qry AND status = 'Aktif' ");

        $dt = array();

        foreach ($list as $kl => $vl) {
            $id = $vl['id'];
            $query = $vl;

            $endorse = $this->mymodel->selectWithQuery("SELECT COUNT(id) as frequency, SUM(total_cost) as total_cost, SUM(views) as views, 
            AVG(views) as avg_views, 
            AVG(likes+comment+share_save) as avg_interaksi, 
            SUM(likes) as likes,
            SUM(share_save) as share,
            SUM(comment) as comment
            FROM endorse WHERE influencer = '$id'
            AND link_upload != ''
            ");
            $endorse = $endorse[0];

            $dt = array();
            $dt['sync_at'] = DATE("Y-m-d H:i:s");
            $dt['frequency'] = $endorse['frequency'];
            $dt['total_cost'] = $endorse['total_cost'];
            $dt['view'] = $endorse['views'];
            $dt['like'] = $endorse['likes'];
            $dt['comment'] = $endorse['comment'];
            $dt['collect'] = $endorse['collect'];
            $dt['share'] = $endorse['share'];
            $dt['avg_view'] = $endorse['avg_views'];
            $dt['avg_interaksi'] = $endorse['avg_interaksi'];
            if ($endorse['total_cost'] > 0 && $endorse['views'] > 0) {
                $dt['cpm'] = $endorse['total_cost'] / $endorse['views'] * 1000;
            } else {
                $dt['cpm'] = 0;
            }

            $this->db->update('influencer', $dt, array('id' => $id));

            $url = $query['url'];

            $response = $this->template->get_account_id($query['type'], $query['url']);
            // print_r($response);die;
            if ($response['status'] == false) {
                // $msg = $response['msg'];
                // echo $this->template->alert_danger($msg);
                // die;
            } else {
                $dt['updated_at'] = DATE("Y-m-d H:i:s");
                $dt['updated_by'] = strval($user['id']);
                $dt['account_id'] = $response['data']['account_id'];
                // print_r($response);die;
                $dt['img'] = $response['data']['img'];
                $dt['follower'] = $response['data']['follower'];
                $dt['media_count'] = $response['data']['media_count'];
                // print_r($dt);die;
                $this->db->update('influencer', $dt, array('id' => $id));

                if ($query['type'] == "Tiktok") {
                    $url = $query['url'];
                    preg_match('/@([a-zA-Z0-9_]+)/', $url, $matches);
                    $response = $this->template->get_post_list($query['type'], $response['data']['account_id']);
                } else {
                    $response = $this->template->get_post_list($query['type'], $response['data']['account_id']);
                }

                if ($response['status'] == false) {
                    // $msg = $response['msg'];
                    // echo $this->template->alert_danger($msg);
                    // die;
                } else {
                    $dt = array();
                    $dt['updated_at'] = DATE("Y-m-d H:i:s");
                    $dt['updated_by'] = strval($user['id']);
                    $dt['like'] = 0;
                    $dt['comment'] = 0;
                    $dt['collect'] = 0;
                    $dt['share'] = 0;
                    $dt['view'] = 0;
                    // print_r($response['data']);
                    $i = 0;
                    foreach ($response['data'] as $k => $v) {
                        $dt['like'] += $v['like'];
                        $dt['comment'] += $v['comment'];
                        $dt['collect'] += $v['collect'];
                        $dt['share'] += $v['share'];
                        $dt['view'] += $v['view'];
                        if ($i >= 10) {
                            break;
                        }
                        $i++;
                    }

                    if ($dt['view'] > 0) {
                        $dt['avg_view'] = $dt['view'] / $i;
                    }
                    if (($dt['like'] + $dt['comment'] + $dt['collect'] + $dt['share'])  > 0) {
                        $dt['avg_interaksi'] = ($dt['like'] + $dt['comment'] + $dt['collect'] + $dt['share']) / $i;
                    }
                    if ($dt['view'] > 0 && $dt['avg_interaksi'] > 0) {
                        $dt['er'] = $dt['avg_interaksi'] / $dt['avg_view'] * 100;
                    }
                    $dt['sync_at'] = DATE("Y-m-d H:i:s");
                    // $this->db->update('influencer', $dt, array('id' => $id));

                    $today = DATE("Y-m-d");
                    $logs = $this->mymodel->selectWithQuery("SELECT id FROM influencer_logs WHERE id_influencer = '$id' AND DATE(date) = '$today' ");
                    $logs = $logs[0];
                    if ($logs) {
                        $dt['updated_at'] = DATE("Y-m-d H:i:s");
                        $this->db->update('influencer_logs', $dt, array('id' => $logs['id']));
                    } else {
                        $dt['id_influencer'] = $id;
                        $dt['date'] = $today;
                        $dt['status'] = "Aktif";
                        $dt['created_at'] = DATE("Y-m-d H:i:s");
                        $this->db->update('influencer_logs', $dt);
                    }

                    $dt_2 = array();
                    $dt_2['sync_at'] = $dt['sync_at'];
                    $dt_2['frequency_2'] = $i;
                    $dt_2['er'] = $dt['er'];
                    $dt_2['updated_at'] = DATE("Y-m-d H:i:s");
                    $dt_2['updated_by'] = strval($user['id']);
                    $dt_2['view_2'] = $dt['view'];
                    $dt_2['like_2'] = $dt['like'];
                    $dt_2['collect_2'] = $dt['collect'];
                    $dt_2['share_2'] = $dt['share'];
                    $dt_2['comment_2'] = $dt['comment'];
                    $dt_2['avg_view_2'] = $dt['view'] / $i;
                    $dt_2['avg_interaksi_2'] = ($dt['like'] + $dt['comment'] + $dt['collect'] + $dt['share']) / $i;

                    if ($query['ratecard'] > 0 && $dt['view'] > 0) {
                        $dt_2['cpm_2'] = $query['ratecard'] / $dt_2['avg_view_2'] * 1000;
                    } else {
                        $dt_2['cpm_2'] = 0;
                    }

                    $this->db->update('influencer', $dt_2, array('id' => $id));

                    // $msg = "Refresh data berhasil!";
                    // echo $this->template->alert_success($msg);
                    // die;
                }
            }
        }
        if ($list) {
            $msg = "Refresh data berhasil!";
            echo $this->template->alert_success($msg);
            die;
        } else {
            $msg = "Data tidak ditemukan!";
            echo $this->template->alert_danger($msg);
            die;
        }
    }

    public function sync()
    {
        $id = $_GET['id'];

        $query = $this->mymodel->selectWithQuery("SELECT * FROM influencer WHERE id = '$id'");

        $data['data'] = $query[0];
        $this->load->view("influencer/sync", $data);
    }

    public function sync_process()
    {


        $user = $_SESSION['user'];
        $id = $_POST['id'];

        $query = $this->mymodel->selectWithQuery("SELECT * FROM influencer WHERE id = '$id'");
        $query = $query[0];

        if ($query['status'] == "Tidak Aktif") {
            $msg = "Pastikan status influencer Aktif!";
            echo $this->template->alert_danger($msg);
            die;
        }

        $endorse = $this->mymodel->selectWithQuery("SELECT COUNT(id) as frequency, SUM(total_cost) as total_cost, SUM(views) as views, 
        AVG(views) as avg_views, 
        AVG(likes+comment+share_save) as avg_interaksi, 
        SUM(likes) as likes,
        SUM(share_save) as share,
        SUM(comment) as comment
        FROM endorse WHERE influencer = '$id'
        AND link_upload != ''
        ");
        $endorse = $endorse[0];

        $dt = array();
        $dt['sync_at'] = DATE("Y-m-d H:i:s");
        $dt['frequency'] = $endorse['frequency'];
        $dt['total_cost'] = $endorse['total_cost'];
        $dt['view'] = $endorse['views'];
        $dt['like'] = $endorse['likes'];
        $dt['comment'] = $endorse['comment'];
        $dt['collect'] = $endorse['collect'];
        $dt['share'] = $endorse['share'];
        $dt['avg_view'] = $endorse['avg_views'];
        $dt['avg_interaksi'] = $endorse['avg_interaksi'];
        if ($endorse['total_cost'] > 0 && $endorse['views'] > 0) {
            $dt['cpm'] = $endorse['total_cost'] / $endorse['views'] * 1000;
        } else {
            $dt['cpm'] = 0;
        }

        $this->db->update('influencer', $dt, array('id' => $id));
        // die;
        $url = $query['url'];

        $response = $this->template->get_account_id($query['type'], $query['url']);
        if ($response['status'] == false) {
            $msg = $response['msg'];
            echo $this->template->alert_danger($msg);
            die;
        } else {
            $dt['updated_at'] = DATE("Y-m-d H:i:s");
            $dt['updated_by'] = strval($user['id']);
            $dt['account_id'] = $response['data']['account_id'];
            // print_r($response);die;
            $dt['img'] = $response['data']['img'];
            $dt['follower'] = $response['data']['follower'];
            $dt['media_count'] = $response['data']['media_count'];
            // print_r($dt);die;
            $this->db->update('influencer', $dt, array('id' => $id));

            if ($query['type'] == "Tiktok") {
                $url = $query['url'];
                $uri = explode("/", parse_url($url, PHP_URL_PATH));
                $username = $uri[1];
                $username = str_replace('@', '', $username);
                $response = $this->template->get_post_list($query['type'], $response['data']['account_id']);
            } else {
                $response = $this->template->get_post_list($query['type'], $response['data']['account_id']);
            }

            if ($response['status'] == false) {
                $msg = $response['msg'];
                echo $this->template->alert_danger($msg);
                die;
            } else {
                $dt = array();
                $dt['updated_at'] = DATE("Y-m-d H:i:s");
                $dt['updated_by'] = strval($user['id']);
                $dt['like'] = 0;
                $dt['comment'] = 0;
                $dt['collect'] = 0;
                $dt['share'] = 0;
                $dt['view'] = 0;
                // print_r($response['data']);
                $i = 0;
                foreach ($response['data'] as $k => $v) {
                    $dt['like'] += $v['like'];
                    $dt['comment'] += $v['comment'];
                    $dt['collect'] += $v['collect'];
                    $dt['share'] += $v['share'];
                    $dt['view'] += $v['view'];
                    if ($i >= 10) {
                        break;
                    }
                    $i++;
                }


                if ($dt['view'] > 0) {
                    $dt['avg_view'] = $dt['view'] / $i;
                }
                if (($dt['like'] + $dt['comment'] + $dt['collect'] + $dt['share'])  > 0) {
                    $dt['avg_interaksi'] = ($dt['like'] + $dt['comment'] + $dt['collect'] + $dt['share']) / $i;
                }
                if ($dt['view'] > 0 && $dt['avg_interaksi'] > 0) {
                    $dt['er'] = $dt['avg_interaksi'] / $dt['avg_view'] * 100;
                }
                $dt['sync_at'] = DATE("Y-m-d H:i:s");
                // $this->db->update('influencer', $dt, array('id' => $id));

                $today = DATE("Y-m-d");
                $logs = $this->mymodel->selectWithQuery("SELECT id FROM influencer_logs WHERE id_influencer = '$id' AND DATE(date) = '$today' ");
                $logs = $logs[0];
                if ($logs) {
                    $dt['updated_at'] = DATE("Y-m-d H:i:s");
                    $this->db->update('influencer_logs', $dt, array('id' => $logs['id']));
                } else {
                    $dt['id_influencer'] = $id;
                    $dt['date'] = $today;
                    $dt['status'] = "Aktif";
                    $dt['created_at'] = DATE("Y-m-d H:i:s");
                    $this->db->insert('influencer_logs', $dt);
                }

                $dt_2 = array();
                $dt_2['sync_at'] = $dt['sync_at'];
                $dt_2['frequency_2'] = $i;
                $dt_2['er'] = $dt['er'];
                $dt_2['updated_at'] = DATE("Y-m-d H:i:s");
                $dt_2['updated_by'] = strval($user['id']);
                $dt_2['view_2'] = $dt['view'];
                $dt_2['like_2'] = $dt['like'];
                $dt_2['collect_2'] = $dt['collect'];
                $dt_2['share_2'] = $dt['share'];
                $dt_2['comment_2'] = $dt['comment'];
                $dt_2['avg_view_2'] = $dt['view'] / $i;
                $dt_2['avg_interaksi_2'] = ($dt['like'] + $dt['comment'] + $dt['collect'] + $dt['share']) / $i;

                if ($query['ratecard'] > 0 && $dt['view'] > 0) {
                    $dt_2['cpm_2'] = $query['ratecard'] / $dt_2['avg_view_2'] * 1000;
                } else {
                    $dt_2['cpm_2'] = 0;
                }

                $this->db->update('influencer', $dt_2, array('id' => $id));

                $msg = "Refresh data berhasil!";
                echo $this->template->alert_success($msg);
                die;
            }
        }
    }

    public function remove()
    {
        $id = $_GET['id'];
        $data['data']['id'] = $id;
        $this->load->view("influencer/delete", $data);
    }

    public function delete()
    {

        $user = $_SESSION['user'];

        $id = $_POST['id'];



        if ($this->db->delete('influencer', array('id' => $id))) {
            $msg = 'Hapus data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Hapus data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }

    public function action()
    {

        $id_selected_v2 = $_POST['id_selected_v2'];

        $id_selected = $_POST['id_selected'];
        if ($id_selected) {
            $id = explode(',', $id_selected);
        }

        $is_manual = $_POST['is_manual'];
        $marketplace = $_POST['marketplace'];
        $brand = $_POST['brand'];
        $order_id = $_POST['order_id'];
        $code = $_GET['code'];
        $data['data']['id'] = $id;
        $data['data']['code'] = $code;
        if ($code == "hapus_data") {
            $data['question'] = "Apakah kamu yakin ingin menghapus data influencer ini?";
            $data['btn'] = "Hapus Data";
        } else if ($code == "refresh_data") {
            $data['question'] = "Apakah kamu yakin ingin merefresh data influencer ini?";
            $data['btn'] = "Refresh Data";
        } else if ($code == "ubah_status") {
            $data['question'] = "Apakah kamu yakin ingin mengubah status data influencer ini?";
            $data['btn'] = "Ubah Status Influencer";
        } else if ($code == "ubah_status_data") {
            $data['question'] = "Apakah kamu yakin ingin mengubah status data ini?";
            $data['btn'] = "Ubah Status";
        }
        $this->load->view("influencer/action", $data);
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
        $is_manual = $_POST['is_manual'];
        $marketplace = $_POST['marketplace'];
        $brand = $_POST['brand'];
        $order_id = $_POST['order_id'];
        if ($code == "hapus_data") {
            foreach ($id as $k => $v) {
                $list_id .= "'" . $v . "',";
            }

            $list_id = substr($list_id, 0, -1);
            if ($list_id) {
                $dt = array();
                $this->db->delete('influencer', "id IN ($list_id)");
                $msg = 'Hapus data berhasil!';
                echo $this->template->alert_success($msg);
            } else {
                $msg = 'Pastikan kamu sudah memilih minimal 1 data!';
                echo $this->template->alert_danger($msg);
            }
        } else if ($code == "refresh_data") {
            foreach ($id as $k => $v) {
                if ($v > 0) {
                    $list_id .= "" . $v . ",";
                }
            }
            $list_id = substr($list_id, 0, -1);
            if ($list_id) {

                $url = base_url() . '/influencer/sync-all-process?mode=refresh_data&ids=' . $list_id;
                $curl = curl_init();

                // echo $url;die;
                // echo '<br>';

                curl_setopt_array($curl, array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json'
                    ),
                ));

                $response = curl_exec($curl);
                echo $response;
                die;
                curl_close($curl);
            } else {
                $msg = 'Pastikan kamu sudah memilih minimal 1 data!';
                echo $this->template->alert_danger($msg);
            }
        } else if ($code == "ubah_status") {
            $status = $_POST['status'];
            foreach ($id as $k => $v) {
                if ($v > 0) {
                    $list_id .= "" . $v . ",";
                }
            }
            $list_id = substr($list_id, 0, -1);
            if ($list_id) {
                $dtt = array();
                $dtt['status_reach'] = $status;
                $dtt['updated_at'] = DATE("Y-m-d H:i:s");
                $this->db->update('influencer', $dtt, "id IN ($list_id)");

                $msg = 'Ubah status influencer berhasil!';
                echo $this->template->alert_success($msg);
            } else {
                $msg = 'Pastikan kamu sudah memilih minimal 1 data!';
                echo $this->template->alert_danger($msg);
            }
        } else if ($code == "ubah_status_data") {
            $status = $_POST['status'];
            foreach ($id as $k => $v) {
                if ($v > 0) {
                    $list_id .= "" . $v . ",";
                }
            }
            $list_id = substr($list_id, 0, -1);
            if ($list_id) {
                $dtt = array();
                $dtt['status'] = $status;
                $dtt['updated_at'] = DATE("Y-m-d H:i:s");
                $this->db->update('influencer', $dtt, "id IN ($list_id)");

                $msg = 'Ubah status berhasil!';
                echo $this->template->alert_success($msg);
            } else {
                $msg = 'Pastikan kamu sudah memilih minimal 1 data!';
                echo $this->template->alert_danger($msg);
            }
        }
    }
    
    public function sync_external_process()
    {
        header('Content-Type: application/json');
        $list = $this->mymodel->selectWithQuery("SELECT * FROM influencer WHERE avg_interaksi_2 = 0 AND status = 'Aktif' ");

        $dt = array();

        foreach ($list as $kl => $vl) {
            $id = $vl['id'];
            $query = $vl;

            $endorse = $this->mymodel->selectWithQuery("SELECT COUNT(id) as frequency, SUM(total_cost) as total_cost, SUM(views) as views, 
            AVG(views) as avg_views, 
            AVG(likes+comment+share_save) as avg_interaksi, 
            SUM(likes) as likes,
            SUM(share_save) as share,
            SUM(comment) as comment
            FROM endorse WHERE influencer = '$id'
            AND link_upload != ''
            ");
            $endorse = $endorse[0];

            $dt = array();
            $dt['sync_at'] = DATE("Y-m-d H:i:s");
            $dt['frequency'] = $endorse['frequency'];
            $dt['total_cost'] = $endorse['total_cost'];
            $dt['view'] = $endorse['views'];
            $dt['like'] = $endorse['likes'];
            $dt['comment'] = $endorse['comment'];
            $dt['collect'] = $endorse['collect'];
            $dt['share'] = $endorse['share'];
            $dt['avg_view'] = $endorse['avg_views'];
            $dt['avg_interaksi'] = $endorse['avg_interaksi'];
            if ($endorse['total_cost'] > 0 && $endorse['views'] > 0) {
                $dt['cpm'] = $endorse['total_cost'] / $endorse['views'] * 1000;
            } else {
                $dt['cpm'] = 0;
            }

            $this->db->update('influencer', $dt, array('id' => $id));

            $url = $query['url'];

            $response = $this->template->get_account_id($query['type'], $query['url']);
            // print_r($response);die;
            if ($response['status'] == false) {
                // $msg = $response['msg'];
                // echo $this->template->alert_danger($msg);
                // die;
            } else {
                $dt['updated_at'] = DATE("Y-m-d H:i:s");
                $dt['updated_by'] = strval($user['id']);
                $dt['account_id'] = $response['data']['account_id'];
                // print_r($response);die;
                $dt['img'] = $response['data']['img'];
                $dt['follower'] = $response['data']['follower'];
                $dt['media_count'] = $response['data']['media_count'];
                // print_r($dt);die;
                $this->db->update('influencer', $dt, array('id' => $id));

                if ($query['type'] == "Tiktok") {
                    $url = $query['url'];
                    preg_match('/@([a-zA-Z0-9_]+)/', $url, $matches);
                    $response = $this->template->get_post_list($query['type'], $response['data']['account_id']);
                } else {
                    $response = $this->template->get_post_list($query['type'], $response['data']['account_id']);
                }

                if ($response['status'] == false) {
                    // $msg = $response['msg'];
                    // echo $this->template->alert_danger($msg);
                    // die;
                } else {
                    $dt = array();
                    $dt['updated_at'] = DATE("Y-m-d H:i:s");
                    $dt['updated_by'] = strval($user['id']);
                    $dt['like'] = 0;
                    $dt['comment'] = 0;
                    $dt['collect'] = 0;
                    $dt['share'] = 0;
                    $dt['view'] = 0;
                    // print_r($response['data']);
                    $i = 0;
                    foreach ($response['data'] as $k => $v) {
                        $dt['like'] += $v['like'];
                        $dt['comment'] += $v['comment'];
                        $dt['collect'] += $v['collect'];
                        $dt['share'] += $v['share'];
                        $dt['view'] += $v['view'];
                        if ($i >= 10) {
                            break;
                        }
                        $i++;
                    }

                    if ($dt['view'] > 0) {
                        $dt['avg_view'] = $dt['view'] / $i;
                    }
                    if (($dt['like'] + $dt['comment'] + $dt['collect'] + $dt['share'])  > 0) {
                        $dt['avg_interaksi'] = ($dt['like'] + $dt['comment'] + $dt['collect'] + $dt['share']) / $i;
                    }
                    if ($dt['view'] > 0 && $dt['avg_interaksi'] > 0) {
                        $dt['er'] = $dt['avg_interaksi'] / $dt['avg_view'] * 100;
                    }
                    $dt['sync_at'] = DATE("Y-m-d H:i:s");
                    // $this->db->update('influencer', $dt, array('id' => $id));

                    $today = DATE("Y-m-d");
                    $logs = $this->mymodel->selectWithQuery("SELECT id FROM influencer_logs WHERE id_influencer = '$id' AND DATE(date) = '$today' ");
                    $logs = $logs[0];
                    if ($logs) {
                        $dt['updated_at'] = DATE("Y-m-d H:i:s");
                        $this->db->update('influencer_logs', $dt, array('id' => $logs['id']));
                    } else {
                        $dt['id_influencer'] = $id;
                        $dt['date'] = $today;
                        $dt['status'] = "Aktif";
                        $dt['created_at'] = DATE("Y-m-d H:i:s");
                        $this->db->update('influencer_logs', $dt);
                    }

                    $dt_2 = array();
                    $dt_2['sync_at'] = $dt['sync_at'];
                    $dt_2['frequency_2'] = $i;
                    $dt_2['er'] = $dt['er'];
                    $dt_2['updated_at'] = DATE("Y-m-d H:i:s");
                    $dt_2['updated_by'] = strval($user['id']);
                    $dt_2['view_2'] = $dt['view'];
                    $dt_2['like_2'] = $dt['like'];
                    $dt_2['collect_2'] = $dt['collect'];
                    $dt_2['share_2'] = $dt['share'];
                    $dt_2['comment_2'] = $dt['comment'];
                    $dt_2['avg_view_2'] = $dt['view'] / $i;
                    $dt_2['avg_interaksi_2'] = ($dt['like'] + $dt['comment'] + $dt['collect'] + $dt['share']) / $i;

                    if ($query['ratecard'] > 0 && $dt['view'] > 0) {
                        $dt_2['cpm_2'] = $query['ratecard'] / $dt_2['avg_view_2'] * 1000;
                    } else {
                        $dt_2['cpm_2'] = 0;
                    }

                    $this->db->update('influencer', $dt_2, array('id' => $id));

                    // $msg = "Refresh data berhasil!";
                    // echo $this->template->alert_success($msg);
                    // die;
                }
            }
        }
        if ($list) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Refresh data berhasil!'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Data tidak ditemukan!'
            ]);
        }
        exit;

        
    }
    
}
