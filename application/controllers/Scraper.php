<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Scraper extends CI_Controller
{
    public function index()
    {
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

        $data['title'] = 'Scraper Customer - ' . $this->template->title();

        $data['content'] = $this->load->view("scraper/all", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function get_data()
    {
        header('Content-Type: application/json');
        
        $start_date = $this->input->get('start_date') ?: DATE("Y-m-01");
        $until_date = $this->input->get('until_date') ?: DATE("Y-m-d");
        
        $start = $this->input->get('start') ?: 0;
        $length = $this->input->get('length') ?: 10;
        $search = $this->input->get('search')['value'] ?: '';
        $order_column = $this->input->get('order')[0]['column'] ?: 0;
        $order_dir = $this->input->get('order')[0]['dir'] ?: 'desc';
        
        $columns = array(
            0 => 'id',
            1 => 'order_id',
            2 => 'name',
            3 => 'address',
            4 => 'phone',
            5 => 'created_at',
            6 => 'username'
        );
        
        $order_by = $columns[$order_column] . ' ' . $order_dir;
        
        $qry = "WHERE 1=1";
        
        if ($start_date && $until_date) {
            $qry .= " AND DATE(created_at) >= '$start_date' AND DATE(created_at) <= '$until_date'";
        }
        
        if ($search) {
            $search = $this->db->escape_like_str($search);
            $qry .= " AND (order_id LIKE '%$search%' OR name LIKE '%$search%' OR phone LIKE '%$search%' OR username LIKE '%$search%')";
        }
        
        $total_query = $this->db->query("SELECT COUNT(id) as total FROM customer_scrap $qry");
        $total_data = $total_query->row()->total;
        
        $data_query = $this->db->query("SELECT * FROM customer_scrap $qry ORDER BY $order_by LIMIT $start, $length");
        $data = $data_query->result_array();
        
        $output = array(
            "draw" => intval($this->input->get('draw')),
            "recordsTotal" => $total_data,
            "recordsFiltered" => $total_data,
            "data" => array()
        );
        
        foreach ($data as $index => $row) {
            $status_badge = $row['is_generated'] == 1
                ? '<span class="badge text-bg-success fs-12">Tersimpan</span>'
                : '<span class="badge text-bg-danger fs-12">Belum Disimpan</span>';

            $output['data'][] = array(
                $start + $index + 1,
                $row['order_id'],
                $row['name'],
                $row['address'],
                $row['phone'],
                date('d M Y', strtotime($row['created_at'])),
                $row['username'],
                $status_badge 
            );
        }

        
        echo json_encode($output);
    }

    public function import()
    {
        $data = $_SESSION['filter'] ?? [];
        $this->load->view("scraper/import_customer", $data);
    }

    public function import_process()
    {
        $user = $_SESSION['user'];
        $start_date = $this->input->post('start_date');
        $until_date = $this->input->post('until_date');

        $this->db->where('DATE(created_at) >=', $start_date);
        $this->db->where('DATE(created_at) <=', $until_date);
        $this->db->where('is_generated =', 0);
        $customers = $this->db->get('customer_scrap')->result_array();

        if (empty($customers)) {
            $msg = 'Tidak ada data customer yang ditemukan untuk rentang tanggal tersebut!';
            echo $this->template->alert_danger($msg);
            die;
        }

        $success_count = 0;
        $failed_count = 0;

        foreach ($customers as $customer) {
            $order_id = $customer['order_id'];
            $marketplace = $customer['marketplace'];

            $query = $this->mymodel->selectWithQuery("
                SELECT id, customer 
                FROM transaction 
                WHERE order_id = '$order_id' AND marketplace = '$marketplace'
                LIMIT 1
            ");

            if (!empty($query)) {
                $query = $query[0];
                $transaction_id = $query['id'];
                $customer_id = $query['customer'];

                $dt = [
                    'updated_at' => date('Y-m-d H:i:s'),
                    'order_id' => $order_id,
                    'marketplace' => $marketplace,
                    'customer_text' => strval($customer['name']),
                    'phone' => strval($customer['phone']),
                    'address' => strval($customer['address']),
                    'city_text' => strval($customer['city_text'] ?? ''),
                    'province_text' => strval($customer['province_text'] ?? ''),
                    'c_username' => strval($customer['username']),
                ];
                $this->db->update('transaction', $dt, ['id' => $transaction_id]);

                $dtt = [
                    'updated_at' => date('Y-m-d H:i:s'),
                    'full_name' => strval($customer['name']),
                    'phone' => strval($customer['phone']),
                    'username' => strval($customer['username']),
                    'address' => strval($customer['address']),
                    'city_text' => strval($customer['city_text'] ?? ''),
                    'province_text' => strval($customer['province_text'] ?? ''),
                ];
                $this->db->update('customer', $dtt, ['id' => $customer_id]);

                $this->db->update('customer_scrap', ['is_generated' => 1], [
                    'order_id' => $order_id,
                    'marketplace' => $marketplace
                ]);

                $success_count++;
            } else {
                $failed_count++;
            }
        }

        if ($failed_count > 0) {
            $msg = "Import selesai! Berhasil memperbarui $success_count data, gagal $failed_count data.";
            echo $this->template->alert_success($msg);
        } else {
            $msg = "Import berhasil! $success_count data customer & transaksi telah diperbarui.";
            echo $this->template->alert_success($msg);
        }
    }



}
