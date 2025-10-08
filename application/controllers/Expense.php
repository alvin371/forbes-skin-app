<?php

defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/BaseController.php';

class Expense extends BaseController
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

        $this->load->helper('url');
        $this->load->library('session');
        $this->load->library('user_agent');
    }

    public function index()
    {

        if (isset($_GET['keyword_category']) && $_GET['keyword_category']) {
            $keyword_category = $_GET['keyword_category'];
        } else {
            $keyword_category = "Judul";
        }
        $data['keyword_category'] = $keyword_category;
        $keyword = isset($_GET['keyword']) ? $_GET['keyword'] : null;

        $data['title'] = 'Pengeluaran - ' . $this->template->title();
        if (isset($_GET['start_date']) && $_GET['start_date']) {
            $start_date = $_GET['start_date'];
        } else {
            $start_date = date('Y-m-01');
        }
        if (isset($_GET['until_date']) && $_GET['until_date']) {
            $until_date = $_GET['until_date'];
        } else {
            $until_date = date('Y-m-d');
        }

        $query = $this->mymodel->selectWithQuery("SELECT * FROM brand WHERE status = 'ENABLE' ORDER BY name ASC");

        $data['brands'] = $query;


        $keyword = isset($_GET['keyword']) ? $_GET['keyword'] : null;
        $brand = isset($_GET['brand']) ? $_GET['brand'] : null;
        $marketplace = isset($_GET['marketplace']) ? $_GET['marketplace'] : null;
        $cs = isset($_GET['cs']) ? $_GET['cs'] : null;
        $product = isset($_GET['product']) ? $_GET['product'] : null;
        $type = isset($_GET['type']) ? $_GET['type'] : null;
        $type_sub = isset($_GET['type_sub']) ? $_GET['type_sub'] : null;

        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $data['brand'] = $brand;
        $brand = isset($_GET['brand']) ? $_GET['brand'] : null;
        $category = isset($_GET['category']) ? $_GET['category'] : null;
        $is_recurring = isset($_GET['is_recurring']) ? $_GET['is_recurring'] : null;



        $qry = "";

        $qry = "";
        $qry = " DATE(date) >= '" . $this->db->escape_str($start_date) . "'
        AND DATE(date) <= '" . $this->db->escape_str($until_date) . "'";

        $keyword = isset($_GET['keyword']) ? $_GET['keyword'] : null;
        $keyword_category = isset($_GET['keyword_category']) ? $_GET['keyword_category'] : 'Judul';

        if ($keyword) {
            $escaped_keyword = $this->db->escape_str($keyword);
            if ($keyword_category == "Judul") {
                $qry .= " AND title LIKE '%" . $escaped_keyword . "%' ";
            } else if ($keyword_category == "Keterangan") {
                $qry .= " AND desc LIKE '%" . $escaped_keyword . "%' ";
            }
        }

        if ($brand) {
            $qry .= " AND brand = '" . $this->db->escape_str($brand) . "' ";
        }

        if ($is_recurring) {
            $qry .= " AND is_recurring = '" . $this->db->escape_str($is_recurring) . "' ";
        }

        if ($category) {
            $qry .= " AND category = '" . $this->db->escape_str($category) . "' ";
        }

        $limit = 30;

        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

        $allowedSort = [
            'date' => 'date',
            'brand' => 'brand',
            'title' => 'title',
            'desc' => 'desc',
            'price' => 'price',
            'qty' => 'qty',
            'price_total' => 'price_total',
            'percentage' => 'percentage',
            'category' => 'category',
            'id' => 'id',
        ];

        $sort_by  = $_GET['sort_by']  ?? 'date';
        $sort_dir = strtoupper($_GET['sort_dir'] ?? 'DESC');

        if (!isset($allowedSort[$sort_by])) {
            $sort_by = 'date';
        }
        if (!in_array($sort_dir, ['ASC', 'DESC'], true)) {
            $sort_dir = 'DESC';
        }

        $data['sort_by']  = $sort_by;
        $data['sort_dir'] = $sort_dir;

        $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count FROM expense
        WHERE $qry ");

        $data['page'] = CEIL($query[0]['count'] / 30);

        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query[0]['count']) . ' data ditemukan!</label></p>';


        $item = '';

        $current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $total_expense = $this->mymodel->selectWithQuery("SELECT ABS(SUM(price_total)) as total_expense FROM expense
        WHERE $qry ");

        $data['total_expense'] = $this->template->separator_only($total_expense[0]['total_expense'] ?? 0);

        $url = base_url() . '/expense/' . $this->template->get_param();
        $data['url'] = $this->template->get_param_without_keyword_category($url);
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $data['brand'] = $brand;
        $data['content'] = $this->load->view('expense/all', $data, true);

        $this->load->view('TemplateDashboard', $data);
    }

    public function item()
    {
        $data['template'] = $this->template;

        // ========== Params dasar ==========
        $start_date = $_GET['start_date'] ?? date('Y-m-01');
        $until_date = $_GET['until_date'] ?? date('Y-m-d');
        $data['start_date'] = $start_date;
        $data['until_date']  = $until_date;

        // Ambil data brand untuk filter
        $query = $this->mymodel->selectWithQuery("SELECT * FROM brand WHERE status = 'ENABLE' ORDER BY name ASC");
        $data['brands'] = $query;

        $keyword          = $_GET['keyword'] ?? null;
        $keyword_category = $_GET['keyword_category'] ?? null; // "Judul" / "Keterangan"
        $brand            = $_GET['brand'] ?? null;
        $is_recurring     = $_GET['is_recurring'] ?? null;
        $category         = $_GET['category'] ?? null;

        // ========== Server-side filter AG Grid ==========
        $ff = $this->input->get('filter_field');
        $fv = $this->input->get('filter_value');
        $fo = $this->input->get('filter_operator');

        $filters = [];
        if (is_array($ff) && is_array($fv)) {
            $n = min(count($ff), count($fv));
            for ($i = 0; $i < $n; $i++) {
                $filters[] = [
                    'field' => (string)$ff[$i],
                    'value' => (string)$fv[$i],
                    'op'    => isset($fo[$i]) ? (string)$fo[$i] : 'equals',
                ];
            }
        } elseif (!empty($ff) && !empty($fv)) {
            $filters[] = [
                'field' => (string)$ff,
                'value' => (string)$fv,
                'op'    => !empty($fo) ? (string)$fo : 'equals',
            ];
        }
        $data['filters'] = $filters;

        // ========== Build WHERE aman ==========
        $esc = fn($s) => $this->db->escape_str($s);

        $qry = " DATE(date) >= '" . $esc($start_date) . "' AND DATE(date) <= '" . $esc($until_date) . "' ";

        if ($keyword) {
            $kw = $esc($keyword);
            if ($keyword_category === "Judul") {
                $qry .= " AND title LIKE '%{$kw}%'";
            } elseif ($keyword_category === "Keterangan") {
                $qry .= " AND `desc` LIKE '%{$kw}%'";
            }
        }
        if ($brand !== null && $brand !== '') {
            $qry .= " AND brand = '" . $esc($brand) . "'";
        }
        if ($is_recurring !== null && $is_recurring !== '') {
            $qry .= " AND is_recurring = '" . $esc($is_recurring) . "'";
        }
        if ($category !== null && $category !== '') {
            $qry .= " AND category = '" . $esc($category) . "'";
        }

        // Tambahan filter dari AG Grid (whitelist)
        $allowed_filter_fields = [
            'id',
            'date',
            'brand',
            'category',
            'title',
            'desc',
            'price',
            'qty',
            'customer_text',
            'customer',
            'type',
            'type_sub',
            'price_total',
            'price_total_2',
            'net_price',
            'created_by',
            'updated_by',
            'created_at',
            'updated_at',
            'status',
            'is_recurring',
            'recurring_type',
            'recurring_day',
            'recurring_date',
            'last_generated_at'
        ];
        if (!empty($filters)) {
            $conds = [];
            foreach ($filters as $f) {
                $field = $f['field'];
                $value = $esc($f['value']);
                $op    = $esc($f['op']);
                if (!in_array($field, $allowed_filter_fields, true)) continue;

                if ($value === '-') {
                    $conds[] = "($field IS NULL OR $field = '' OR $field = '-')";
                } elseif ($op === 'equals') {
                    $conds[] = "$field = '{$value}'";
                } elseif ($op === 'contains') {
                    $conds[] = "$field LIKE '%{$value}%'";
                } elseif ($op === 'startsWith') {
                    $conds[] = "$field LIKE '{$value}%'";
                } elseif ($op === 'endsWith') {
                    $conds[] = "$field LIKE '%{$value}'";
                }
            }
            if (!empty($conds)) {
                $qry .= " AND (" . implode(" AND ", $conds) . ")";
            }
        }

        // ========== Query data expense ==========
        $expenses = $this->mymodel->selectWithQuery("
            SELECT *
            FROM expense
            WHERE $qry
            ORDER BY date DESC, id DESC
        ");

        // ========== Hitung net sales dan persentase untuk setiap expense ==========
        foreach ($expenses as &$expense) {
            $month_start = date('Y-m-01', strtotime($expense['date']));
            $month_end = date('Y-m-t', strtotime($expense['date']));

            // Query untuk mendapatkan net sales bulanan per brand
            $net_sales_query = $this->mymodel->selectWithQuery("
                SELECT SUM(omset_kotor - diskon_penjual) AS result 
                FROM transaction 
                WHERE 
                    DATE(date) >= '$month_start' 
                    AND DATE(date) <= '$month_end'
                    AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') 
                    AND type_sub = 'POS'
                    " . ($expense['brand'] ? " AND brand = '{$expense['brand']}'" : "") . "
            ");

            $net_sales = $net_sales_query[0]['result'] ?? 0;

            // Hitung persentase
            $price_total = isset($expense['price_total']) ? (float)abs($expense['price_total']) : 0.0;
            $percent = ($net_sales > 0) ? ($price_total / $net_sales * 100.0) : 0.0;

            // Tambahkan ke data expense
            $expense['net_sales'] = $net_sales;
            $expense['percent'] = $percent;
        }
        unset($expense);

        $data['data'] = $expenses;

        // ========== JSON mode untuk AG-Grid ==========
        $accept = $this->input->get_request_header('Accept', TRUE);
        if (stripos($accept, 'application/json') !== FALSE) {
            $fmt  = function ($n) {
                return is_numeric($n) ? number_format((float)$n, 0, '', '.') : ($n ?? '');
            };
            $fmt2 = function ($n) {
                return is_numeric($n) ? number_format((float)$n, 2, ',', '.') : ($n ?? '');
            };

            $rows = [];
            foreach ($expenses as $v) {
                $percent   = (float)($v['percent'] ?? 0);
                $net_sales = (float)($v['net_sales'] ?? 0);

                $rows[] = [
                    'id'                => (int)($v['id'] ?? 0),
                    'date'              => DATE('Y-m-d', strtotime($v['date'])) ?? '',
                    'brand'             => $v['brand'] ?? '',
                    'category'          => $v['category'] ?? '',
                    'title'             => $v['title'] ?? '',
                    'desc'              => $v['desc'] ?? '',
                    'price'             => (float)($v['price'] ?? 0),
                    'price_fmt'         => $fmt($v['price'] ?? 0),
                    'qty'               => (float)($v['qty'] ?? 0),
                    'customer_text'     => $v['customer_text'] ?? '',
                    'customer'          => (int)($v['customer'] ?? 0),
                    'type'              => $v['type'] ?? '',
                    'type_sub'          => $v['type_sub'] ?? '',
                    'price_total'       => (float)(abs($v['price_total']) ?? 0),
                    'price_total_fmt'   => $fmt(abs($v['price_total']) ?? 0),
                    'price_total_2'     => (float)($v['price_total_2'] ?? 0),
                    'price_total_2_fmt' => $fmt($v['price_total_2'] ?? 0),
                    'net_price'         => (float)($v['net_price'] ?? 0),
                    'net_price_fmt'     => $fmt($v['net_price'] ?? 0),
                    'net_sales'         => $net_sales,
                    'net_sales_fmt'     => $fmt($net_sales),
                    'percent'           => (float) ($v['percent'] ?? 0),
                    'percent_fmt'       => $fmt2($percent),
                    'created_by'        => $v['created_by'] ?? '',
                    'updated_by'        => $v['updated_by'] ?? '',
                    'created_at'        => $v['created_at'] ?? '',
                    'updated_at'        => $v['updated_at'] ?? '',
                    'status'            => $v['status'] ?? '',
                    'is_recurring'      => (int)($v['is_recurring'] ?? 0),
                    'recurring_type'    => $v['recurring_type'] ?? '',
                    'recurring_day'     => $v['recurring_day'] ?? '',
                    'recurring_date'    => $v['recurring_date'] ?? '',
                    'last_generated_at' => $v['last_generated_at'] ?? '',
                ];
            }

            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'ok'   => true,
                    'rows' => $rows,
                ]));
        }

        // ========== Mode HTML biasa ==========
        $this->load->view("expense/item", $data);
    }


    public function edit_category()
    {
        $data['categories'] = $this->db->order_by('category', 'ASC')->get('category')->result();
        $this->load->view('expense/edit_category_form', $data);
    }

    public function save_category()
    {
        $this->load->library('form_validation');

        $this->form_validation->set_rules('category', 'category', 'required|is_unique[category.category]|max_length[255]');

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('errors', validation_errors());
        } else {
            $category = $this->input->post('category');
            $this->db->insert('category', array('category' => $category));
            $this->session->set_flashdata('message', 'Category added successfully');
        }

        redirect($this->agent->referrer());
    }

    public function delete_category($category)
    {
        $decodedCategory = urldecode($category);
        $this->db->where('category', $decodedCategory)->delete('category');

        echo 'success';
    }

    public function edit()
    {
        $id = isset($_GET['id']) ? $_GET['id'] : null;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM expense WHERE id = '" . $this->db->escape_str($id) . "' AND type = 'Out' AND type_sub = 'Expense' ");

        $data['data'] = $query[0];

        $query = $this->mymodel->selectWithQuery("SELECT * FROM brand WHERE status = 'ENABLE' ORDER BY name ASC");

        $data['brands'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM category");

        $data['categories'] = $query;


        $this->load->view("expense/edit", $data);
    }

    public function update()
    {

        $user = $_SESSION['user'];

        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $dt['date'] = DATE("Y-m-d H:i:s", strtotime($dt['date']));
        $dt['updated_at'] = DATE("Y-m-d H:i:s");
        $dt['updated_by'] = $user['id'];

        if ($dt['category'] != 'Gift') {
            $dt['customer_text'] = '';
            $dt['customer'] = '';
        }
        $dt['is_recurring'] = $dt['is_recurring'] ?? 0;
        if ($dt['is_recurring'] == 0) {
            $dt['recurring_type'] = '';
        }
        $dt['status'] = $dt['status'] ?? 'Aktif';
        $dt['price'] = 0 - doubleval($dt['price']);
        $dt['price_total'] = doubleval($dt['price']);
        $dt['price_total_2'] = $dt['price_total'];
        $dt['net_price'] = $dt['price_total'];
        if ($_FILES['file']['name']) {
            $config['upload_path'] = FCPATH . 'assets/img/transaction/';
            $config['allowed_types'] = 'jpg|jpeg|png';
            $config['max_size'] = 1024;
            $config['file_name'] = $id;

            $this->load->library('upload', $config);

            if (!$this->upload->do_upload('file')) {
                $msg = 'Pastikan tipe file .jpg, .jpeg atau .png!';
                echo $this->template->alert_danger($msg);
                die;
            } else {
                $upload_data = $this->upload->data();
                $dt['img'] = $upload_data['file_name'];
            }
        }

        if ($this->db->update('expense', $dt, array('id' => $id))) {

            if ($dt['category'] == "Gift") {
                $id_customer = $_POST['id_customer'];
                $this->refresh_gift($id_customer);
            }

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

        $query = $this->mymodel->selectWithQuery("SELECT * FROM brand WHERE status = 'ENABLE' ORDER BY name ASC");

        $data['brands'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM category");

        $data['categories'] = $query;

        $this->load->view("expense/create", $data);
    }


    public function store()
    {

        $user = $_SESSION['user'];

        $id = $_POST['id'];
        $dt = $_POST['dt'];
        $dt['date'] = DATE("Y-m-d H:i:s", strtotime($dt['date']));
        $dt['created_at'] = DATE("Y-m-d H:i:s");
        $dt['created_by'] = $user['id'];

        if ($dt['category'] != 'Gift') {
            $dt['customer_text'] = '';
            $dt['customer'] = '';
        }
        $dt['is_recurring'] = $dt['is_recurring'] ?? 0;
        if ($dt['is_recurring'] == 0) {
            $dt['recurring_type'] = '';
        }

        $dt['status'] = $dt['status'] ?? 'Aktif';
        $dt['type'] = 'Out';
        $dt['type_sub'] = 'Expense';
        $dt['price'] = 0 - doubleval($dt['price']);
        $dt['price_total'] = doubleval($dt['price']);
        $dt['price_total_2'] = $dt['price_total'];
        $dt['net_price'] = $dt['price_total'];
        $dt['last_generated_at'] = DATE("Y-m-d");
        if ($_FILES['file']['name']) {
            $config['upload_path'] = FCPATH . 'assets/img/transaction/';
            $config['allowed_types'] = 'jpg|jpeg|png';
            $config['max_size'] = 1024;
            $config['file_name'] = DATE('Ymdhis');

            $this->load->library('upload', $config);

            if (!$this->upload->do_upload('file')) {
                $msg = 'Pastikan tipe file .jpg, .jpeg atau .png!';
                echo $this->template->alert_danger($msg);
                die;
            } else {
                $upload_data = $this->upload->data();
                $dt['img'] = $upload_data['file_name'];
            }
        }





        if ($this->db->insert('expense', $dt)) {
            $msg = 'Tambah data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Tambah data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }

    public function sync()
    {
        $id = isset($_GET['id']) ? $_GET['id'] : null;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM expense WHERE id = '" . $this->db->escape_str($id) . "'");

        $data['data'] = $query[0];
        $this->load->view("expense/sync", $data);
    }

    public function sync_process()
    {

        $transaction = $this->template->get_session('expense');
        $id = $_POST['id'];


        $query = $this->mymodel->selectWithQuery("SELECT * FROM expense WHERE id = '" . $this->db->escape_str($id) . "'");

        $v = $query[0];
        $response = $this->template->get_social_media($v['type'], $v['url']);
        $dt = array();
        $dt['updated_at'] = DATE("Y-m-d H:i:s");
        $dt['updated_by'] = $transaction['id'];
        if ($response['data']['like'] > 0) {
            $dt['like'] = $response['data']['like'];
            $dt['comment'] = $response['data']['comment'];
            $dt['collect'] = $response['data']['collect'];
            $dt['share'] = $response['data']['share'];
            $dt['view'] = $response['data']['view'];
            if ($v['cost'] > 0 && $dt['view'] > 0) {
                $dt['cpm'] = $v['cost'] / $dt['view'] * 1000;
            }
        }

        $this->db->update('expense', $dt, array('id' => $v['id']));

        if ($response['status'] == true) {
            $msg = 'Sync data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            if ($response) {
                $msg = $response['msg'];
            } else {
                $msg = 'Data transaction belum tersedia!';
            }
            echo $this->template->alert_danger($msg);
        }
    }

    public function remove()
    {
        $data['data']['id'] = isset($_GET['id']) ? $_GET['id'] : null;
        $this->load->view("expense/delete", $data);
    }

    public function action()
    {
        $id_selected_v2 = $_POST['id_selected_v2'];
        $id_selected = $_POST['id_selected'];
        if ($id_selected) {
            $id = explode(',', $id_selected);
        }
        $brand = $_POST['brand'];
        $code = isset($_GET['code']) ? $_GET['code'] : null;
        $data['data']['id'] = $id;
        $data['data']['code'] = $code;
        if ($code == "hapus_data") {
            $data['question'] = "Apakah kamu yakin ingin menghapus data expense ini?";
            $data['btn'] = "Hapus Data";
        }
        $this->load->view("expense/action", $data);
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
                $this->db->delete('expense', "id IN ($list_id)");
                $msg = 'Hapus data berhasil!';
                echo $this->template->alert_success($msg);
            } else {
                $msg = 'Pastikan kamu sudah memilih minimal 1 data!';
                echo $this->template->alert_danger($msg);
            }
        }
    }

    public function delete()
    {

        $id = $_POST['id'];

        $data = $this->mymodel->selectWithQuery("SELECT customer
        FROM expense WHERE id = '" . $this->db->escape_str($id) . "' ");
        $data = $data[0];


        if ($this->db->delete('expense', array('id' => $id))) {
            $id_customer = $data['customer'];
            $this->refresh_gift($id_customer);

            $msg = 'Hapus data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Hapus data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }
    function refresh_gift($id_customer)
    {
        $data = $this->mymodel->selectWithQuery("SELECT id, date, title,expense.desc,qty,price,price_total FROM expense
        WHERE customer = '" . $this->db->escape_str($id_customer) . "' AND category = 'Gift'
        ORDER BY date ASC");
        $dt = array();
        $dt['gift'] = json_encode($data, true);
        $this->db->update('customer', $dt, array('id' => $id_customer));
    }

    public function generate_recurring_expense()
    {
        $expenses = $this->mymodel->selectWithQuery("SELECT * FROM expense WHERE is_recurring = 1");
        foreach ($expenses as $expense) {
            $last_generated_at = $expense['last_generated_at'];
            $recurring_type = $expense['recurring_type'];

            $next_date = $this->get_next_date($last_generated_at, $recurring_type, $expense);

            if ($next_date == date('Y-m-d')) {
                $new_expense = [
                    'date'            => $next_date,
                    'brand'           => $expense['brand'],
                    'category'        => $expense['category'],
                    'title'           => $expense['title'],
                    'desc'            => $expense['desc'],
                    'price'           => $expense['price'],
                    'qty'             => $expense['qty'],
                    'customer_text'   => $expense['customer_text'],
                    'customer'        => $expense['customer'],
                    'type'            => $expense['type'],
                    'type_sub'        => $expense['type_sub'],
                    'price_total'     => $expense['price_total'],
                    'price_total_2'   => $expense['price_total_2'],
                    'net_price'       => $expense['net_price'],
                    'created_by'      => $expense['created_by'],
                    'updated_by'      => $expense['updated_by'],
                    'created_at'      => date('Y-m-d H:i:s'),
                    'updated_at'      => date('Y-m-d H:i:s'),
                    'status'          => $expense['status'],
                    'is_recurring'    => 0,
                    'recurring_type'  => $expense['recurring_type'],
                    'last_generated_at' => $next_date
                ];

                $this->db->insert('expense', $new_expense);

                $this->db->where('id', $expense['id'])->update('expense', ['last_generated_at' => $next_date]);
            }
        }
        echo "Recurring expenses generated successfully.";
    }

    private function get_next_date($last_date, $recurring_type, $expense)
    {
        if (!$last_date || !strtotime($last_date)) {
            $last_date = date('Y-m-d');
        }

        switch ($recurring_type) {
            case 'Harian':
                return date('Y-m-d', strtotime($last_date . ' +1 day'));
            case 'Mingguan':
                $day_of_week = $this->convertDayToEnglish($expense['recurring_day'] ?? 'Senin');
                return date('Y-m-d', strtotime("next $day_of_week", strtotime($last_date)));
            case 'Bulanan':
                $date_of_month = $expense['recurring_date'] ?? 1;
                $next_date = date('Y-m-d', strtotime($last_date . " +1 month"));
                $next_month = date('m', strtotime($next_date));
                $next_year = date('Y', strtotime($next_date));
                $last_day_of_month = date('t', strtotime("$next_year-$next_month-01"));
                $date_of_month = min($date_of_month, $last_day_of_month);
                return date('Y-m-d', strtotime("$next_year-$next_month-$date_of_month"));
            case 'Tahunan':
                return date('Y-m-d', strtotime($last_date . ' +1 year'));
            default:
                return $last_date;
        }
    }

    private function convertDayToEnglish($day)
    {
        $days = [
            'Senin' => 'Monday',
            'Selasa' => 'Tuesday',
            'Rabu' => 'Wednesday',
            'Kamis' => 'Thursday',
            'Jumat' => 'Friday',
            'Sabtu' => 'Saturday',
            'Minggu' => 'Sunday',
        ];
        return $days[$day] ?? 'Monday';
    }
}
