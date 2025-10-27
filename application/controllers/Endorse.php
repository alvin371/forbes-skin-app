<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';
class Endorse extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('mymodel');
        $this->load->library('template');
        $this->load->helper(['url','form']);
        $this->load->library(['email']);

        // Set public methods (no permission required)
        $this->set_public_methods([]);

        // Override method-to-action mapping if needed
        $this->set_method_permissions([
            'remove' => 'delete',
            'action' => 'edit'
        ]);
        
    }



    public function stats()
    {

        $id_campaign = $_GET['id_campaign'];
        $id_influencer = $_GET['id_influencer'];

        $data['template'] = $this->template;

        $data['title'] = 'Campaign Detail - ' . $this->template->title();

        $data['checkbox'] = $_SESSION['checkbox'];
        $id_campaign = $_GET['id_campaign'];
        $data['detail'] = $this->mymodel->selectWithQuery("SELECT *
        FROM endorse_campaign WHERE id = '$id_campaign'");
        $data['detail'] = $data['detail'][0];

        $id_campaign = $_GET['id_campaign'];

        $add_param = "";
        if ($id_influencer) {

            $qry = "";
            if ($id_campaign) {
                $qry .= " AND id_campaign = '$id_campaign' ";
            }

            $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) AS count
            FROM endorse
            WHERE influencer = '$id_influencer' $qry 
            ");
        } else if ($id_campaign) {

            $qry = "";

            $type = $_GET['type'];
            $start_date = $_GET['start_date'];
            $until_date = $_GET['until_date'];
            $start_year = $_GET['start_year'];
            $until_year = $_GET['until_year'];
            $start_month = $_GET['start_month'];
            $until_month = $_GET['until_month'];
            $start_week = $_GET['start_week'];
            $until_week = $_GET['until_week'];
            $site = $_GET['site'];
            $customer = $_GET['customer'];
            $mpu = $_GET['mpu'];

            if ($type == "Yearly") {
                $qry_opt = " YEAR(date) ";
                $start_date = $start_year . '-01-01';
                $until_date = $until_year . '-12-31';
                $group = "  GROUP BY YEAR(date) ";
            } else if ($type == "Monthly") {
                $qry_opt = " MONTH(date) ";
                $start_month = str_pad($start_month, 2, "0", STR_PAD_LEFT);
                $until_month = str_pad($until_month, 2, "0", STR_PAD_LEFT);
                $start_date = $start_year . '-' . $start_month . '-01';
                $until_date = $start_year . '-' . $until_month . '-31';
                $group = "  GROUP BY MONTH(date) ";
            } else if ($type == "Weekly") {
                $qry_opt = " WEEK(date) ";
                $start_week = str_pad($start_week, 2, "0", STR_PAD_LEFT);
                $until_week = str_pad($until_week, 2, "0", STR_PAD_LEFT);

                $year = $start_year;
                $week = $start_week;
                $start_date = date("Y-m-d", strtotime($year . "W" . $week . "1"));

                $year = $start_year;
                $week = $until_week;
                $until_date = date("Y-m-d", strtotime($year . "W" . $week . "7"));
                $group = "  GROUP BY WEEK(date) ";
            } else {
                $qry_opt = " DATE(date) ";
                $group = "  GROUP BY DATE(date) ";
            }



            if ($_GET['keyword_category']) {
                $keyword_category = $_GET['keyword_category'];
            } else {
                $keyword_category = "Nama Creator";
            }
            $data['keyword_category'] = $keyword_category;
            $keyword = $_GET['keyword'];

            if ($_GET['start_date']) {
                $start_date = $_GET['start_date'];
            } else {
                $start_date = DATE("Y-m-01");
                
            }
            if ($_GET['until_date']) {
                $until_date = $_GET['until_date'];
            } else {
                $until_date = DATE('Y-m-d');
            }
            $data['start_date'] = $start_date;
            $data['until_date'] = $until_date;
            $qry = "";

            $ids = $_GET['ids'];
            $data['ids'] = $ids;
            if ($ids) {
                $qry .= " AND id  IN ($ids) ";
            }

            if ($brand) {
                $qry .= " AND brand = '$brand' ";
            }

            $cat = $_GET['cat'];
            if ($cat == "Tanggal Dibuat") {
                $qry .= " AND DATE(created_at) >= '$start_date' AND DATE(created_at) <= '$until_date' ";
            } else if ($cat == "Rencana Upload") {
                $qry .= " AND DATE(rencana_at) >= '$start_date' AND DATE(rencana_at) <= '$until_date' ";
            } else if ($cat == "Tanggal Posting") {
                $qry .= " AND DATE(posting_at) >= '$start_date' AND DATE(posting_at) <= '$until_date' ";
            } else {
                // $qry .= " AND DATE(created_at) >= '$start_date' AND DATE(created_at) <= '$until_date' ";
            }

            $status = $_GET['status'];
            if ($status) {
                if ($status == 'Ada MOU') {
                    $qry .= " AND link_mou != '' ";
                } else if ($status == 'Tidak Ada MOU') {
                    $qry .= " AND link_mou = '' ";
                } else if ($status == 'FYP') {
                    $qry .= " AND is_fyp = 1 ";
                }
            }

            $status = $_GET['endorse_status'];
            $statusArray = $status ? explode(',', $status) : [];
            $text = '';
            foreach ($statusArray as $k => $v) {
                $text .= "'" . $v . "',";
            }
            $text = substr($text, 0, -1);

            if ($text) {
                $qry .= " AND status_endorse IN ($text) ";
            }


            $platform = $_GET['platform'];
            if ($platform) {
                $qry .= " AND platform = '$platform' ";
            }


            if ($keyword) {
                if ($keyword_category == "Nama Creator") {
                    $qry .= " AND nama_creator LIKE '%$keyword%' ";
                } else if ($keyword_category == "Link Upload") {
                    $qry .= " AND link_upload LIKE '%$keyword%' ";
                } else if ($keyword_category == "PIC") {
                    $qry .= " AND pic LIKE '%$keyword%' ";
                } else if ($keyword_category == "Platform") {
                    $qry .= " AND platform LIKE '%$keyword%' ";
                } else if ($keyword_category == "Task") {
                    $qry .= " AND task LIKE '%$keyword%' ";
                } else if ($keyword_category == "Keterangan") {
                    $qry .= " AND endorse.desc LIKE '%$keyword%' ";
                }
            }

            $query = $this->mymodel->selectWithQuery("SELECT influencer as id
                FROM endorse
                WHERE id_campaign = '$id_campaign' $qry 
                GROUP BY influencer
            ");

            $ids = [];
            foreach ($query as $row) {
                if (!empty($row['id'])) {
                    $ids[] = (int)$row['id']; 
                }
            }

            $text = count($ids) > 0 ? implode(',', $ids) : '0';


            $qry = "";

            $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) AS count
            FROM influencer
            WHERE id IN ($text)");

            $add_param = "&id_influencer=$text";
        }
        $data['page'] = CEIL($query[0]['count'] / 30);

        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query[0]['count']) . ' data ditemukan!</label></p>';

        $item = '';

        $current_page = intval($_GET['page']);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $data['title_2'] = $this->template->date_format_indo($start_date) . ' - ' . $this->template->date_format_indo($until_date);

        $url = base_url() . '/endorse/' . $this->template->get_param();
        $data['url'] = $this->template->get_param_without('endorse_status');
        $data['url_2'] = $this->template->get_param_without('status');
        $data['url_item'] = $this->template->get_param() . $add_param;
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        if ($id_influencer) {
            $data['content'] = $this->load->view("endorse/stats_endorse", $data, true);
            $this->load->view("TemplateDashboard", $data);
        } else if ($id_campaign) {
            $data['content'] = $this->load->view("endorse/stats_influencer", $data, true);
            $this->load->view("TemplateDashboard", $data);
        }
    }

    public function payment_fee()
    {

        $data['template'] = $this->template;

        $data['title'] = 'Campaign Detail - ' . $this->template->title();

        // $_SESSION['checkbox'][0] = 'true';
        // $_SESSION['checkbox'][1] = 'true';
        // $_SESSION['checkbox'][7] = 'true';
        $data['checkbox'] = $_SESSION['checkbox'];
        $id_campaign = $_GET['id_campaign'];
        $data['detail'] = $this->mymodel->selectWithQuery("SELECT *
        FROM endorse_campaign WHERE id = '$id_campaign'");
        $data['detail'] = $data['detail'][0];
        if (empty($data['detail'])) {
            redirect(base_url() . 'endorse-campaign');
        }

        $id_campaign = $_GET['id_campaign'];

        $type = $_GET['type'];
        $start_date = $_GET['start_date'];
        $until_date = $_GET['until_date'];
        $start_year = $_GET['start_year'];
        $until_year = $_GET['until_year'];
        $start_month = $_GET['start_month'];
        $until_month = $_GET['until_month'];
        $start_week = $_GET['start_week'];
        $until_week = $_GET['until_week'];
        $site = $_GET['site'];
        $customer = $_GET['customer'];
        $mpu = $_GET['mpu'];

        if ($type == "Yearly") {
            $qry_opt = " YEAR(date) ";
            $start_date = $start_year . '-01-01';
            $until_date = $until_year . '-12-31';
            $group = "  GROUP BY YEAR(date) ";
        } else if ($type == "Monthly") {
            $qry_opt = " MONTH(date) ";
            $start_month = str_pad($start_month, 2, "0", STR_PAD_LEFT);
            $until_month = str_pad($until_month, 2, "0", STR_PAD_LEFT);
            $start_date = $start_year . '-' . $start_month . '-01';
            $until_date = $start_year . '-' . $until_month . '-31';
            $group = "  GROUP BY MONTH(date) ";
        } else if ($type == "Weekly") {
            $qry_opt = " WEEK(date) ";
            $start_week = str_pad($start_week, 2, "0", STR_PAD_LEFT);
            $until_week = str_pad($until_week, 2, "0", STR_PAD_LEFT);

            $year = $start_year;
            $week = $start_week;
            $start_date = date("Y-m-d", strtotime($year . "W" . $week . "1"));

            $year = $start_year;
            $week = $until_week;
            $until_date = date("Y-m-d", strtotime($year . "W" . $week . "7"));
            $group = "  GROUP BY WEEK(date) ";
        } else {
            $qry_opt = " DATE(date) ";
            $group = "  GROUP BY DATE(date) ";
        }



        if ($_GET['keyword_category']) {
            $keyword_category = $_GET['keyword_category'];
        } else {
            $keyword_category = "Nama Creator";
        }
        $data['keyword_category'] = $keyword_category;
        $keyword = $_GET['keyword'];

        if ($_GET['start_date']) {
            $start_date = $_GET['start_date'];
        } else {
            $start_date = DATE("Y-m-01");
            
        }
        if ($_GET['until_date']) {
            $until_date = $_GET['until_date'];
        } else {
            $until_date = DATE('Y-m-d');
        }
        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $qry = "";

        $ids = $_GET['ids'];
        $data['ids'] = $ids;
        if ($ids) {
            $qry .= " AND id  IN ($ids) ";
        }

        if ($brand) {
            $qry .= " AND brand = '$brand' ";
        }

        $cat = $_GET['cat'];
        if ($cat == "Tanggal Dibuat") {
            $qry .= " AND DATE(created_at) >= '$start_date' AND DATE(created_at) <= '$until_date' ";
        } else if ($cat == "Rencana Upload") {
            $qry .= " AND DATE(rencana_at) >= '$start_date' AND DATE(rencana_at) <= '$until_date' ";
        } else if ($cat == "Tanggal Posting") {
            $qry .= " AND DATE(posting_at) >= '$start_date' AND DATE(posting_at) <= '$until_date' ";
        } else {
            // $qry .= " AND DATE(created_at) >= '$start_date' AND DATE(created_at) <= '$until_date' ";
        }

        $status = $_GET['status'];
        if ($status) {
            if ($status == 'Ada MOU') {
                $qry .= " AND link_mou != '' ";
            } else if ($status == 'Tidak Ada MOU') {
                $qry .= " AND link_mou = '' ";
            } else if ($status == 'FYP') {
                $qry .= " AND is_fyp = 1 ";
            }
        }

        $status = $_GET['endorse_status'];
        $statusArray = $status ? explode(',', $status) : [];
        $text = '';
        foreach ($statusArray as $k => $v) {
            $text .= "'" . $v . "',";
        }
        $text = substr($text, 0, -1);

        if ($text) {
            $qry .= " AND status_endorse IN ($text) ";
        }

        $platform = $_GET['platform'];
        if ($platform) {
            $qry .= " AND platform = '$platform' ";
        }

        $status_data = $_GET['status_data'];
        if ($status_data) {
            $qry .= " AND status = '$status_data' ";
        }

        if ($keyword) {
            if ($keyword_category == "Nama Creator") {
                $qry .= " AND nama_creator LIKE '%$keyword%' ";
            } else if ($keyword_category == "Link Upload") {
                $qry .= " AND link_upload LIKE '%$keyword%' ";
            } else if ($keyword_category == "PIC") {
                $qry .= " AND pic LIKE '%$keyword%' ";
            } else if ($keyword_category == "Platform") {
                $qry .= " AND platform LIKE '%$keyword%' ";
            } else if ($keyword_category == "Task") {
                $qry .= " AND task LIKE '%$keyword%' ";
            } else if ($keyword_category == "Keterangan") {
                $qry .= " AND endorse.desc LIKE '%$keyword%' ";
            }
        }

        $ads = $_GET['ads'];
        if ($ads == "Iya") {
            $qry .= " AND kode_ads != '' ";
        } else  if ($ads == "Tidak") {
            $qry .= " AND kode_ads = '' ";
        }

        $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) AS count
        FROM endorse
        WHERE id_campaign = '$id_campaign' $qry 
        ");

        $data['page'] = CEIL($query[0]['count'] / 30);

        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query[0]['count']) . ' data ditemukan!</label></p>';

        $item = '';

        $current_page = intval($_GET['page']);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $data['title_2'] = $this->template->date_format_indo($start_date) . ' - ' . $this->template->date_format_indo($until_date);

        $url = base_url() . '/endorse/' . $this->template->get_param();
        $data['url'] = $this->template->get_param_without('endorse_status');
        $data['url_2'] = $this->template->get_param_without('status');
        $data['url_item'] = $this->template->get_param();
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $data['content'] = $this->load->view("endorse/payment-fee", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function item_influencer()
    {
        $data['template'] = $this->template;

        // Ambil parameter dari GET
        $keyword_category = (isset($_GET['keyword_category']) && $_GET['keyword_category']) ? $_GET['keyword_category'] : "Username";
        $data['keyword_category'] = $keyword_category;

        $keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';

        $start_date = (isset($_GET['start_date']) && $_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
        $until_date = (isset($_GET['until_date']) && $_GET['until_date']) ? $_GET['until_date'] : date('Y-m-d');

        $qry = "1=1";

        if (isset($_GET['id_influencer']) && $_GET['id_influencer']) {
            $id_influencer = $_GET['id_influencer'];
            $qry .= " AND influencer.id IN ($id_influencer)";
        }

        if (isset($_GET['status']) && $_GET['status']) {
            $status = $_GET['status'];
            $statusArray = explode(',', $status);
            $text = "";
            foreach ($statusArray as $v) {
                $text .= "'" . $this->db->escape_str(trim($v)) . "',";
            }
            $text = rtrim($text, ',');
            $qry .= " AND status_reach IN ($text)";
        }

        if ($keyword) {
            if ($keyword_category == "Nama Creator") {
                $qry .= " AND full_name LIKE '%" . $this->db->escape_like_str($keyword) . "%'";
            } else if ($keyword_category == "Username") {
                $qry .= " AND (influencer.username LIKE '%" . $this->db->escape_like_str($keyword) . "%' OR endorse.nama_creator LIKE '%" . $this->db->escape_like_str($keyword) . "%')";
            } else if ($keyword_category == "URL") {
                $qry .= " AND (influencer.url LIKE '%" . $this->db->escape_like_str($keyword) . "%' OR endorse.url LIKE '%" . $this->db->escape_like_str($keyword) . "%')";
            } else if ($keyword_category == "Keterangan") {
                $qry .= " AND (influencer.desc LIKE '%" . $this->db->escape_like_str($keyword) . "%' OR endorse.desc LIKE '%" . $this->db->escape_like_str($keyword) . "%')";
            } else if ($keyword_category == "Platform") {
                $qry .= " AND (influencer.type LIKE '%" . $this->db->escape_like_str($keyword) . "%' OR endorse.platform LIKE '%" . $this->db->escape_like_str($keyword) . "%')";
            } else if ($keyword_category == "Niche") {
                $qry .= " AND (influencer.niche LIKE '%" . $this->db->escape_like_str($keyword) . "%' OR endorse.niche LIKE '%" . $this->db->escape_like_str($keyword) . "%')";
            } else if ($keyword_category == "PIC") {
                $qry .= " AND (influencer.pic LIKE '%" . $this->db->escape_like_str($keyword) . "%' OR endorse.pic LIKE '%" . $this->db->escape_like_str($keyword) . "%')";
            }
        }

        $order_by = " ORDER BY influencer.ratecard DESC";
        // $sort = isset($_GET['sort']) ? $_GET['sort'] : '';
        // $sort_sub = isset($_GET['sort_sub']) ? $_GET['sort_sub'] : '';

        // if ($sort == "Frequency") {
        //     $order_by .= " influencer.frequency ";
        // } else if ($sort == "RC") {
        //     $order_by .= " influencer.ratecard ";
        // } else if ($sort == "CPM") {
        //     $order_by .= " influencer.cpm ";
        // } else if ($sort == "ER") {
        //     $order_by .= " influencer.er ";
        // } else {
        //     $order_by .= " influencer.id ";
        // }

        // $order_by .= ($sort_sub == "Asc") ? " ASC " : " DESC ";

        $limit = 30;
        $current_page = (isset($_GET['page']) && $_GET['page']) ? $_GET['page'] : 1;
        $offset = ($current_page > 1) ? (($current_page - 1) * $limit) : 0;

        $sql = "
        SELECT *
        FROM influencer
        WHERE id IN (
            SELECT DISTINCT influencer.id
            FROM influencer
            INNER JOIN endorse ON influencer.username = endorse.nama_creator
            WHERE $qry
        )
        $order_by
        LIMIT $offset, $limit
    ";

        $query = $this->mymodel->selectWithQuery($sql);

        $data['campaign']['id'] = isset($_GET['id_campaign']) ? $_GET['id_campaign'] : '';
        $data['data'] = $query;
        $data['start'] = $offset;
        $this->load->view("influencer/item", $data);
    }


    public function item_endorse()
    {

        $id_campaign = $_GET['id_campaign'];

        $type = $_GET['type'];
        $start_date = $_GET['start_date'];
        $until_date = $_GET['until_date'];
        $start_year = $_GET['start_year'];
        $until_year = $_GET['until_year'];
        $start_month = $_GET['start_month'];
        $until_month = $_GET['until_month'];
        $start_week = $_GET['start_week'];
        $until_week = $_GET['until_week'];
        $site = $_GET['site'];
        $customer = $_GET['customer'];
        $mpu = $_GET['mpu'];

        if ($type == "Yearly") {
            $qry_opt = " YEAR(date) ";
            $start_date = $start_year . '-01-01';
            $until_date = $until_year . '-12-31';
            $group = "  GROUP BY YEAR(date) ";
        } else if ($type == "Monthly") {
            $qry_opt = " MONTH(date) ";
            $start_month = str_pad($start_month, 2, "0", STR_PAD_LEFT);
            $until_month = str_pad($until_month, 2, "0", STR_PAD_LEFT);
            $start_date = $start_year . '-' . $start_month . '-01';
            $until_date = $start_year . '-' . $until_month . '-31';
            $group = "  GROUP BY MONTH(date) ";
        } else if ($type == "Weekly") {
            $qry_opt = " WEEK(date) ";
            $start_week = str_pad($start_week, 2, "0", STR_PAD_LEFT);
            $until_week = str_pad($until_week, 2, "0", STR_PAD_LEFT);

            $year = $start_year;
            $week = $start_week;
            $start_date = date("Y-m-d", strtotime($year . "W" . $week . "1"));

            $year = $start_year;
            $week = $until_week;
            $until_date = date("Y-m-d", strtotime($year . "W" . $week . "7"));
            $group = "  GROUP BY WEEK(date) ";
        } else {
            $qry_opt = " DATE(date) ";
            $group = "  GROUP BY DATE(date) ";
        }



        if ($_GET['keyword_category']) {
            $keyword_category = $_GET['keyword_category'];
        } else {
            $keyword_category = "Nama Creator";
        }
        $data['keyword_category'] = $keyword_category;
        $keyword = $_GET['keyword'];

        if ($_GET['start_date']) {
            $start_date = $_GET['start_date'];
        } else {
            $start_date = DATE("Y-m-01");
            
        }
        if ($_GET['until_date']) {
            $until_date = $_GET['until_date'];
        } else {
            $until_date = DATE('Y-m-d');
        }
        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $qry = "";

        $ids = $_GET['ids'];
        $data['ids'] = $ids;
        if ($ids) {
            $qry .= " AND id  IN ($ids) ";
        }

        if ($brand) {
            $qry .= " AND brand = '$brand' ";
        }

        $cat = $_GET['cat'];
        if ($cat == "Tanggal Dibuat") {
            $qry .= " AND DATE(created_at) >= '$start_date' AND DATE(created_at) <= '$until_date' ";
        } else if ($cat == "Rencana Upload") {
            $qry .= " AND DATE(rencana_at) >= '$start_date' AND DATE(rencana_at) <= '$until_date' ";
        } else if ($cat == "Tanggal Posting") {
            $qry .= " AND DATE(posting_at) >= '$start_date' AND DATE(posting_at) <= '$until_date' ";
        } else {
            // $qry .= " AND DATE(created_at) >= '$start_date' AND DATE(created_at) <= '$until_date' ";
        }

        $status = $_GET['status'];
        if ($status) {
            if ($status == 'Ada MOU') {
                $qry .= " AND link_mou != '' ";
            } else if ($status == 'Tidak Ada MOU') {
                $qry .= " AND link_mou = '' ";
            } else if ($status == 'FYP') {
                $qry .= " AND is_fyp = 1 ";
            }
        }

        $status = $_GET['endorse_status'];
        $statusArray = $status ? explode(',', $status) : [];
        $text = '';
        foreach ($statusArray as $k => $v) {
            $text .= "'" . $v . "',";
        }
        $text = substr($text, 0, -1);

        if ($text) {
            $qry .= " AND status_endorse IN ($text) ";
        }


        $platform = $_GET['platform'];
        if ($platform) {
            $qry .= " AND platform = '$platform' ";
        }


        if ($keyword) {
            if ($keyword_category == "Nama Creator") {
                $qry .= " AND nama_creator LIKE '%$keyword%' ";
            } else if ($keyword_category == "Link Upload") {
                $qry .= " AND link_upload LIKE '%$keyword%' ";
            } else if ($keyword_category == "PIC") {
                $qry .= " AND pic LIKE '%$keyword%' ";
            } else if ($keyword_category == "Platform") {
                $qry .= " AND platform LIKE '%$keyword%' ";
            } else if ($keyword_category == "Task") {
                $qry .= " AND task LIKE '%$keyword%' ";
            } else if ($keyword_category == "Keterangan") {
                $qry .= " AND endorse.desc LIKE '%$keyword%' ";
            }
        }

        $limit = 30;

        $current_page = $_GET['page'];

        if ($current_page <= 1) {
            $offset = 0;
        } else {
            $offset = ($current_page - 1) * $limit;
        }
        $id_influencer = $_GET['id_influencer'];
        $id_campaign = $_GET['id_campaign'];
        $qry = "";
        if ($id_campaign) {
            $qry .= " AND id_campaign = '$id_campaign' ";
        }
        $query = $this->mymodel->selectWithQuery("SELECT * FROM endorse
        WHERE influencer = '$id_influencer' $qry 
        ORDER BY id DESC
        LIMIT $offset, $limit
        ");


        $data['data'] = $query;

        $data['template'] = $this->template;

        $url = base_url() . '/endorse/' . $this->template->get_param();
        $data['url'] = $this->template->get_param_without_status();
        $data['param'] = $this->template->get_param_without('endorse_status');

        $data['start'] = $offset;
        $this->load->view("endorse/item", $data);
    }

    public function index()
    {

        $data['template'] = $this->template;

        $data['title'] = 'Campaign Detail - ' . $this->template->title();

        // $_SESSION['checkbox'][0] = 'true';
        // $_SESSION['checkbox'][1] = 'true';
        // $_SESSION['checkbox'][7] = 'true';
        $data['checkbox'] = $_SESSION['checkbox'];
        $id_campaign = $_GET['id_campaign'];
        $data['detail'] = $this->mymodel->selectWithQuery("SELECT *
        FROM endorse_campaign WHERE id = '$id_campaign'");
        $data['detail'] = $data['detail'][0];
        if (empty($data['detail'])) {
            redirect(base_url() . 'endorse-campaign');
        }

        $id_campaign = $_GET['id_campaign'];

        $type = $_GET['type'];
        $start_date = $_GET['start_date'];
        $until_date = $_GET['until_date'];
        $start_year = $_GET['start_year'];
        $until_year = $_GET['until_year'];
        $start_month = $_GET['start_month'];
        $until_month = $_GET['until_month'];
        $start_week = $_GET['start_week'];
        $until_week = $_GET['until_week'];
        $site = $_GET['site'];
        $customer = $_GET['customer'];
        $mpu = $_GET['mpu'];

        if ($type == "Yearly") {
            $qry_opt = " YEAR(date) ";
            $start_date = $start_year . '-01-01';
            $until_date = $until_year . '-12-31';
            $group = "  GROUP BY YEAR(date) ";
        } else if ($type == "Monthly") {
            $qry_opt = " MONTH(date) ";
            $start_month = str_pad($start_month, 2, "0", STR_PAD_LEFT);
            $until_month = str_pad($until_month, 2, "0", STR_PAD_LEFT);
            $start_date = $start_year . '-' . $start_month . '-01';
            $until_date = $start_year . '-' . $until_month . '-31';
            $group = "  GROUP BY MONTH(date) ";
        } else if ($type == "Weekly") {
            $qry_opt = " WEEK(date) ";
            $start_week = str_pad($start_week, 2, "0", STR_PAD_LEFT);
            $until_week = str_pad($until_week, 2, "0", STR_PAD_LEFT);

            $year = $start_year;
            $week = $start_week;
            $start_date = date("Y-m-d", strtotime($year . "W" . $week . "1"));

            $year = $start_year;
            $week = $until_week;
            $until_date = date("Y-m-d", strtotime($year . "W" . $week . "7"));
            $group = "  GROUP BY WEEK(date) ";
        } else {
            $qry_opt = " DATE(date) ";
            $group = "  GROUP BY DATE(date) ";
        }



        if ($_GET['keyword_category']) {
            $keyword_category = $_GET['keyword_category'];
        } else {
            $keyword_category = "Nama Creator";
        }
        $data['keyword_category'] = $keyword_category;
        $keyword = $_GET['keyword'];

        if ($_GET['start_date']) {
            $start_date = $_GET['start_date'];
        } else {
            $start_date = DATE("Y-m-01");
            
        }
        if ($_GET['until_date']) {
            $until_date = $_GET['until_date'];
        } else {
            $until_date = DATE('Y-m-d');
        }
        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $qry = "";

        $ids = $_GET['ids'];
        $data['ids'] = $ids;
        if ($ids) {
            $qry .= " AND id  IN ($ids) ";
        }

        if ($brand) {
            $qry .= " AND brand = '$brand' ";
        }

        $cat = $_GET['cat'];
        if ($cat == "Tanggal Dibuat") {
            $qry .= " AND DATE(created_at) >= '$start_date' AND DATE(created_at) <= '$until_date' ";
        } else if ($cat == "Rencana Upload") {
            $qry .= " AND DATE(rencana_at) >= '$start_date' AND DATE(rencana_at) <= '$until_date' ";
        } else if ($cat == "Tanggal Posting") {
            $qry .= " AND DATE(posting_at) >= '$start_date' AND DATE(posting_at) <= '$until_date' ";
        } else {
            // $qry .= " AND DATE(created_at) >= '$start_date' AND DATE(created_at) <= '$until_date' ";
        }

        $status = $_GET['status'];
        if ($status) {
            if ($status == 'Ada MOU') {
                $qry .= " AND link_mou != '' ";
            } else if ($status == 'Tidak Ada MOU') {
                $qry .= " AND link_mou = '' ";
            } else if ($status == 'FYP') {
                $qry .= " AND is_fyp = 1 ";
            }
        }

        $status = $_GET['endorse_status'];
        $statusArray = $status ? explode(',', $status) : [];
        $text = '';
        foreach ($statusArray as $k => $v) {
            $text .= "'" . $v . "',";
        }
        $text = substr($text, 0, -1);

        if ($text) {
            $qry .= " AND status_endorse IN ($text) ";
        }

        $status = $_GET['status_payment'];
        $statusArray = $status ? explode(',', $status) : [];
        
        $likeConditions = [];
        $inValues = [];
        
        foreach ($statusArray as $v) {
            $v = trim($v);
            if (strtolower($v) === 'pengajuan payment') {
                $likeConditions[] = "pengajuan_status_payment LIKE '%Pengajuan Payment%'";
            } else {
                $inValues[] = "'" . addslashes($v) . "'";
            }
        }
        
        $conditions = [];
        
        if (!empty($inValues)) {
            $conditions[] = "status_payment IN (" . implode(',', $inValues) . ")";
        }
        
        if (!empty($likeConditions)) {
            $conditions = array_merge($conditions, $likeConditions);
        }
        
        if (!empty($conditions)) {
            $qry .= " AND (" . implode(' OR ', $conditions) . ") ";
        }



        $platform = $_GET['platform'];
        if ($platform) {
            $qry .= " AND platform = '$platform' ";
        }

        $status_data = $_GET['status_data'];
        if ($status_data) {
            $qry .= " AND status = '$status_data' ";
        }

        if ($keyword) {
            if ($keyword_category == "Nama Creator") {
                $qry .= " AND nama_creator LIKE '%$keyword%' ";
            } else if ($keyword_category == "Link Upload") {
                $qry .= " AND link_upload LIKE '%$keyword%' ";
            } else if ($keyword_category == "PIC") {
                $qry .= " AND pic LIKE '%$keyword%' ";
            } else if ($keyword_category == "Platform") {
                $qry .= " AND platform LIKE '%$keyword%' ";
            } else if ($keyword_category == "Task") {
                $qry .= " AND task LIKE '%$keyword%' ";
            } else if ($keyword_category == "Keterangan") {
                $qry .= " AND endorse.desc LIKE '%$keyword%' ";
            }
        }

        $ads = $_GET['ads'];
        if ($ads == "Iya") {
            $qry .= " AND kode_ads != '' ";
        } else  if ($ads == "Tidak") {
            $qry .= " AND kode_ads = '' ";
        }

        $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) AS count
        FROM endorse
        WHERE id_campaign = '$id_campaign' $qry 
        ");

        $data['page'] = CEIL($query[0]['count'] / 10);

        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only($query[0]['count']) . ' data ditemukan!</label></p>';

        $item = '';

        $current_page = intval($_GET['page']);
        if ($current_page <= 1) {
            $current_page = 1;
        }

        $campaign = $this->mymodel->selectWithQuery("SELECT start_at, until_at FROM endorse_campaign     WHERE id = '$id_campaign'");
        $data['start_at'] = $campaign[0]['start_at'];
        $data['until_at'] = $campaign[0]['until_at'];

        $data['title_2'] = $this->template->date_format_indo($start_date) . ' - ' . $this->template->date_format_indo($until_date);

        $url = base_url() . '/endorse/' . $this->template->get_param();
        $data['url'] = $this->template->get_param_without('endorse_status');
        $data['url_2'] = $this->template->get_param_without('status');
        $data['url_item'] = $this->template->get_param();
        $data['param'] = $this->template->get_param();
        $data['param_pagination'] = $this->template->get_param_without('page');
        $data['pagination'] = $this->template->pagination($data['page'], $current_page, $data['param_pagination']);

        $data['content'] = $this->load->view("endorse/all", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }


    public function detail()
    {

        $data['template'] = $this->template;
        $data['title'] = 'Endorse Detail - ' . $this->template->title();
        if ($_GET['start_date']) {
            $start_date = $_GET['start_date'];
        } else {
            $start_date = DATE("Y-m-01");
            
        }
        if ($_GET['until_date']) {
            $until_date = $_GET['until_date'];
        } else {
            $until_date = DATE('Y-m-d');
        }
        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $_SESSION['checkbox'][0] = 'true';
        $data['checkbox'] = $_SESSION['checkbox'];
        $id = $_GET['id'];
        $data['detail'] = $this->mymodel->selectWithQuery("SELECT *
        FROM endorse WHERE id = '$id'");
        $data['detail'] = $data['detail'][0];

        $data['title_2'] = $this->template->date_format_indo($start_date) . ' - ' . $this->template->date_format_indo($until_date);


        $data['content'] = $this->load->view("endorse/detail", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function item()
    {
        $id_campaign = $_GET['id_campaign'];

        $type = $_GET['type'];
        $start_date = $_GET['start_date'];
        $until_date = $_GET['until_date'];
        $start_year = $_GET['start_year'];
        $until_year = $_GET['until_year'];
        $start_month = $_GET['start_month'];
        $until_month = $_GET['until_month'];
        $start_week = $_GET['start_week'];
        $until_week = $_GET['until_week'];
        $site = $_GET['site'];
        $customer = $_GET['customer'];
        $mpu = $_GET['mpu'];

        if ($type == "Yearly") {
            $qry_opt = " YEAR(date) ";
            $start_date = $start_year . '-01-01';
            $until_date = $until_year . '-12-31';
            $group = "  GROUP BY YEAR(date) ";
        } else if ($type == "Monthly") {
            $qry_opt = " MONTH(date) ";
            $start_month = str_pad($start_month, 2, "0", STR_PAD_LEFT);
            $until_month = str_pad($until_month, 2, "0", STR_PAD_LEFT);
            $start_date = $start_year . '-' . $start_month . '-01';
            $until_date = $start_year . '-' . $until_month . '-31';
            $group = "  GROUP BY MONTH(date) ";
        } else if ($type == "Weekly") {
            $qry_opt = " WEEK(date) ";
            $start_week = str_pad($start_week, 2, "0", STR_PAD_LEFT);
            $until_week = str_pad($until_week, 2, "0", STR_PAD_LEFT);

            $year = $start_year;
            $week = $start_week;
            $start_date = date("Y-m-d", strtotime($year . "W" . $week . "1"));

            $year = $start_year;
            $week = $until_week;
            $until_date = date("Y-m-d", strtotime($year . "W" . $week . "7"));
            $group = "  GROUP BY WEEK(date) ";
        } else {
            $qry_opt = " DATE(date) ";
            $group = "  GROUP BY DATE(date) ";
        }

        if ($_GET['keyword_category']) {
            $keyword_category = $_GET['keyword_category'];
        } else {
            $keyword_category = "Nama Creator";
        }
        $data['keyword_category'] = $keyword_category;
        $keyword = $_GET['keyword'];

        if ($_GET['start_date']) {
            $start_date = $_GET['start_date'];
        } else {
            $start_date = DATE("Y-m-01");
        }
        if ($_GET['until_date']) {
            $until_date = $_GET['until_date'];
        } else {
            $until_date = DATE('Y-m-d');
        }
        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $qry = "";

        $ids = $_GET['ids'];
        $data['ids'] = $ids;
        if ($ids) {
            $qry .= " AND id  IN ($ids) ";
        }

        $cat = $_GET['cat'];
        if ($cat == "Tanggal Dibuat") {
            $qry .= " AND DATE(endorse.created_at) >= '$start_date' AND DATE(endorse.created_at) <= '$until_date' ";
        } else if ($cat == "Rencana Upload") {
            $qry .= " AND DATE(rencana_at) >= '$start_date' AND DATE(rencana_at) <= '$until_date' ";
        } else if ($cat == "Tanggal Posting") {
            $qry .= " AND DATE(posting_at) >= '$start_date' AND DATE(posting_at) <= '$until_date' ";
        }

        $status = $_GET['status'];
        if ($status) {
            if ($status == 'Ada MOU') {
                $qry .= " AND link_mou != '' ";
            } else if ($status == 'Tidak Ada MOU') {
                $qry .= " AND link_mou = '' ";
            } else if ($status == 'FYP') {
                $qry .= " AND is_fyp = 1 ";
            }
        }

        $status = $_GET['endorse_status'];
        $statusArray = $status ? explode(',', $status) : [];
        $text = '';
        foreach ($statusArray as $k => $v) {
            $text .= "'" . $v . "',";
        }
        $text = substr($text, 0, -1);

        if ($text) {
            $qry .= " AND status_endorse IN ($text) ";
        }

        $status = $_GET['status_payment'];
        $statusArray = $status ? explode(',', $status) : [];
        
        $likeConditions = [];
        $inValues = [];
        
        foreach ($statusArray as $v) {
            $v = trim($v);
            if (strtolower($v) === 'pengajuan payment') {
                $likeConditions[] = "pengajuan_status_payment LIKE '%Pengajuan Payment%'";
            } else {
                $inValues[] = "'" . addslashes($v) . "'";
            }
        }
        
        $conditions = [];
        
        if (!empty($inValues)) {
            $conditions[] = "status_payment IN (" . implode(',', $inValues) . ")";
        }
        
        if (!empty($likeConditions)) {
            $conditions = array_merge($conditions, $likeConditions);
        }
        
        if (!empty($conditions)) {
            $qry .= " AND (" . implode(' OR ', $conditions) . ") ";
        }

        $platform = $_GET['platform'];
        if ($platform) {
            $qry .= " AND platform = '$platform' ";
        }

        $status_data = $_GET['status_data'];
        if ($status_data) {
            $qry .= " AND status = '$status_data' ";
        }

        if ($keyword) {
            if ($keyword_category == "Nama Creator") {
                $qry .= " AND nama_creator LIKE '%$keyword%' ";
            } else if ($keyword_category == "Link Upload") {
                $qry .= " AND link_upload LIKE '%$keyword%' ";
            } else if ($keyword_category == "PIC") {
                $qry .= " AND endorse.pic LIKE '%$keyword%' ";
            } else if ($keyword_category == "Platform") {
                $qry .= " AND platform LIKE '%$keyword%' ";
            } else if ($keyword_category == "Task") {
                $qry .= " AND task LIKE '%$keyword%' ";
            } else if ($keyword_category == "Keterangan") {
                $qry .= " AND endorse.desc LIKE '%$keyword%' ";
            }
        }

        $ads = $_GET['ads'];
        if ($ads == "Iya") {
            $qry .= " AND kode_ads != '' ";
        } else if ($ads == "Tidak") {
            $qry .= " AND kode_ads = '' ";
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
        
        $sort_column = $_GET['sort_column'] ?? 'id'; 
        $sort_order = $_GET['sort_order'] ?? 'DESC'; 

        $allowed_columns = ['id', 'nama_creator', 'pic', 'total_cost', 'status_endorse', 
                        'views', 'cpm', 'engagement'];
        if (!in_array($sort_column, $allowed_columns)) {
            $sort_column = 'id';
        }

        $sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';

        $count_query = $this->mymodel->selectWithQuery("SELECT COUNT(*) as total FROM endorse 
            INNER JOIN influencer ON endorse.nama_creator = influencer.username
            WHERE id_campaign = '$id_campaign' $qry");
        $total_data = $count_query[0]['total'];
        $data['total_data'] = $total_data;
        $data['page'] = ceil($total_data / $limit);
        $data['current_page'] = $current_page;

        $query = $this->mymodel->selectWithQuery("
            SELECT
                e.*,
                (e.likes + e.comment + e.share_save) AS engagement,
                i.contact,
                i.tipe_kontak
            FROM
                (SELECT DISTINCT * FROM endorse WHERE id_campaign = '$id_campaign' $qry) AS e
            LEFT JOIN influencer AS i ON e.nama_creator = i.username
            ORDER BY $sort_column $sort_order
            LIMIT $offset, $limit
        ");

        $data['data'] = $query;
        $data['template'] = $this->template;

        $url = base_url() . '/endorse/' . $this->template->get_param();
        $data['url'] = $this->template->get_param_without_status();
        $data['param'] = $this->template->get_param_without('endorse_status');

        $data['start'] = $offset + 1;
        $data['end'] = min($offset + $limit, $total_data);
        
        $this->load->view("endorse/item", $data);
    }

    public function alert_payment()
    {
        $id = $_GET['id'];
        $data['data']['id'] = $id;
        $this->load->view("endorse/update_payment", $data);
    }


    public function sync_all()
    {
        $id = $_GET['id'];
        $data['data']['id'] = $id;
        $this->load->view("endorse/sync_all", $data);
    }
    public function sync_all_process()
    {
        $id = $_POST['id'];
        $filter = $_GET;

        // $target = DATE("Y-m-d 12:00:00");
        $target = DATE("Y-m-d 23:30:00");
        $now = DATE("Y-m-d H:i:s");

        $user = $_SESSION['user'];
        $today = DATE("Y-m-d");
        $yesterday = DATE('Y-m-d', strtotime($today . " -1 days"));

        $mode = $_GET['mode'];
        $ids = $_GET['ids'];
        if ($mode == "refresh_data") {
            $qry = " AND a.id IN ($ids) ";
            $id = $_GET['id_campaign'];
        }


        $data = $this->mymodel->selectWithQuery("SELECT a.*
        FROM endorse a 
        WHERE a.id_campaign = '$id'
        AND a.link_upload != '' 
        AND a.status = 'Aktif' AND a.status_campaign = 'Aktif'
        $qry
        ");



        foreach ($data as $k => $v) {
            $id_endorse = $v['id'];
            $query = $this->mymodel->selectWithQuery("SELECT id
            FROM endorse_logs
            WHERE id_endorse = '$id_endorse' AND date = '$today' ");
            $query = $query[0];

            $query_yesterday = $this->mymodel->selectWithQuery("SELECT * 
            FROM endorse_logs
            WHERE id_endorse = '$id_endorse' AND date < '$today' AND views_after > 0 ORDER BY date DESC LIMIT 1 ");
            $query_yesterday = $query_yesterday[0];

            $dt = array();

            $dt['status'] = strval($v['status']);
            $dt['status_campaign'] = strval($v['status_campaign']);

            $dt['id_endorse'] = strval($v['id']);
            $dt['id_campaign'] = strval($v['id_campaign']);
            $dt['influencer'] = strval($v['influencer']);
            $dt['date'] = $today;

            $response = $this->template->get_social_media($v['platform'], $v['link_upload']);

            $dt['likes'] = intval($query_yesterday['likes_after']);
            $dt['comment'] = intval($query_yesterday['comment_after']);
            $dt['share_save'] = intval($query_yesterday['share_save_after']);
            $dt['views'] = intval($query_yesterday['views_after']);

            if ($response['data']['view'] > 0) {
                $dt['likes'] = $response['data']['like'];
                $dt['comment'] = $response['data']['comment'];
                $dt['share_save'] = doubleval($response['data']['share']) + doubleval($response['data']['collect']);
                $dt['views'] = $response['data']['view'];
            }


            if ($dt['views'] >= 50000) {
                $id_influencer = $v['influencer'];
                $creator = $this->mymodel->selectWithQuery("SELECT follower
                    FROM influencer WHERE id = '$id_influencer'");
                $creator = $creator[0];
                $percentage = 0;
                $follower = intval($creator['follower']);
                if ($follower > 0) {
                    $batas = intval($follower * 30 / 100);
                    if ($dt['views'] >= $batas) {
                        $dt['is_fyp'] = "1";
                    }
                } else {
                    $dt['is_fyp'] = "1";
                }
            }
            if ($v['total_cost'] > 0 && $dt['views'] > 0) {
                $dt['cpm'] = doubleval($v['total_cost']) / doubleval($dt['views']) * 1000;
            } else {
                $dt['cpm'] = 0;
            }
            $dtt = $dt;
            unset($dtt['id_endorse']);
            unset($dtt['id_campaign']);
            unset($dtt['date']);
            $dtt['updated_at'] = DATE("Y-m-d H:i:s");

            $this->db->update('endorse', $dtt, array('id' => $id_endorse));

            $dtt = array();
            $dtt['sync_at'] = DATE("Y-m-d H:i:s");
            $dtt['posting_at'] = $response['data']['created_at'];
            $dtt['likes'] = doubleval($dt['likes']);
            $dtt['comment'] = doubleval($dt['comment']);
            $dtt['share_save'] = doubleval($dt['share_save']);
            $dtt['views'] = doubleval($dt['views']);
            $dtt['cpm'] = doubleval($dt['cpm']);

            $dt['total_cost'] = doubleval($v['total_cost']);

            $dt['link_upload'] = strval($v['link_upload']);
            $dt['platform'] = strval($v['platform']);

            $dt['likes_after'] = intval($dt['likes']);
            $dt['comment_after'] = intval($dt['comment']);
            $dt['share_save_after'] = intval($dt['share_save']);
            $dt['views_after'] = intval($dt['views']);

            if ($v['total_cost'] > 0 && $dt['views_after'] > 0) {
                $dt['cpm_after'] = doubleval($v['total_cost']) / doubleval($dt['views_after']) * 1000;
            } else {
                $dt['cpm_after'] = 0;
            }

            $dt['likes'] -= intval($query_yesterday['likes_after']);
            $dt['comment'] -= intval($query_yesterday['comment_after']);
            $dt['share_save'] -= intval($query_yesterday['share_save_after']);
            $dt['views'] -= intval($query_yesterday['views_after']);

            if ($v['total_cost'] > 0 && $dt['views'] > 0) {
                $dt['cpm'] = doubleval($v['total_cost']) / doubleval($dt['views']) * 1000;
            } else {
                $dt['cpm'] = 0;
            }

            $dt['likes_before'] = intval($query_yesterday['likes_after']);
            $dt['comment_before'] = intval($query_yesterday['comment_after']);
            $dt['share_save_before'] = intval($query_yesterday['share_save_after']);
            $dt['views_before'] = intval($query_yesterday['views_after']);

            if ($v['total_cost'] > 0 && $dt['views_before'] > 0) {
                $dt['cpm_before'] = doubleval($v['total_cost']) / doubleval($dt['views_before']) * 1000;
            } else {
                $dt['cpm_before'] = 0;
            }
            // }

            // $dt['is_cron'] = '1';
            // print_r($dt);die;

            $dt_tmp = array();
            foreach ($dt as $kt => $vt) {
                $dt_tmp[$kt] = strval($vt);
            }
            $dt = $dt_tmp;


            unset($dt['is_fyp']);
            unset($dt['posting_at']);
            unset($dt['sync_at']);

            if ($query) {
                $dt['updated_at'] = DATE("Y-m-d H:i:s");
                $dt['updated_by'] = strval($user['id']);
                $this->db->update('endorse_logs', $dt, array('id' => $query['id']));
                $id_parent = $query['id'];
            } else {
                $dt['created_at'] = DATE("Y-m-d H:i:s");
                $dt['created_by'] = strval($user['id']);
                $this->db->insert('endorse_logs', $dt);
                $id_parent = $this->db->insert_id();
            }

            $dt_tmp = array();
            foreach ($dtt as $kt => $vt) {
                $dt_tmp[$kt] = strval($vt);
            }
            $dtt = $dt_tmp;

            $dtt['updated_at'] = DATE("Y-m-d H:i:s");
            $dtt['updated_by'] = strval($user['id']);
            // print_r($dtt);die;
            $this->db->update('endorse', $dtt, array('id' => $v['id']));
        }
        $data = $this->mymodel->selectWithQuery("SELECT id
        FROM endorse_campaign 
        WHERE status = 'Aktif' 
        AND id = '$id'");
        foreach ($data as $k => $v) {
            $id_parent = $v['id'];
            $this->update_endorse_parent($id_parent);
        }
        $msg = 'Refresh data berhasil!';
        echo $this->template->alert_success($msg);
    }
    function update_endorse_parent($id_parent)
    {
        $user = $_SESSION['user'];

        $v['id'] = $id_parent;
        $today = DATE("Y-m-d");
        $yesterday = DATE('Y-m-d', strtotime($today . " -1 days"));
        $query = $this->mymodel->selectWithQuery("SELECT id
        FROM endorse_campaign_logs
        WHERE id_campaign = '$id_parent' AND date = '$today' ");
        $query = $query[0];

        $query_yesterday = $this->mymodel->selectWithQuery("SELECT *
        FROM endorse_campaign_logs
        WHERE id_campaign = '$id_parent' AND date < '$today' ORDER BY date DESC LIMIT 1 ");
        $query_yesterday = $query_yesterday[0];

        $item_detail = $this->mymodel->selectWithQuery("SELECT SUM(total_cost) as total_cost, COUNT(id) as count_endorse,
        SUM(likes) as likes, SUM(comment) as comment, SUM(share_save) as share_save, SUM(views) as views, AVG(cpm) as cpm
        FROM endorse
        WHERE id_campaign = '$id_parent'  AND link_upload != ''
        AND status = 'Aktif' ");
        $item_detail = $item_detail[0];

        $item = $this->mymodel->selectWithQuery("SELECT SUM(likes) as likes, SUM(comment) as comment, SUM(share_save) as share_save, SUM(views) as views, AVG(cpm) as cpm,
        SUM(likes_after) as likes_after, SUM(comment_after) as comment_after, SUM(share_save_after) as share_save_after, SUM(views_after) as views_after, AVG(cpm_after) as cpm_after,
        SUM(likes_before) as likes_before, SUM(comment_before) as comment_before, SUM(share_save_before) as share_save_before, SUM(views_before) as views_before, AVG(cpm_before) as cpm_before
        FROM endorse_logs
        WHERE id_campaign = '$id_parent';");
        $dt = array();
        $dt['id_campaign'] = $v['id'];
        $dt['total_cost'] = doubleval($item_detail['total_cost']);
        $dt['date'] = $today;
        foreach ($item[0] as $k2 => $v2) {
            $dt[$k2] = doubleval($v2);
        }


        $dtt = array();
        foreach ($item_detail as $k3 => $v3) {
            $dtt[$k3] = doubleval($v3);
        }

        $id_parent = $v['id'];

        $summary = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count
        FROM endorse WHERE id_campaign = '$id_parent'  ");
        $summary = $summary[0];
        $dtt['count_endorse'] = intval($summary['count']);
        $dt['ce_now'] = $dtt['count_endorse'];

        $summary = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count
        FROM endorse WHERE id_campaign = '$id_parent' AND status = 'Aktif'  ");
        $summary = $summary[0];
        $dtt['count_endorse_active'] = intval($summary['count']);
        $dt['ce_active_now'] = $dtt['count_endorse_active'];

        $summary = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count
        FROM endorse WHERE id_campaign = '$id_parent' AND status = 'Aktif' 
        AND link_upload != '' ");
        $summary = $summary[0];
        $dtt['count_endorse_processed'] = intval($summary['count']);
        $dt['ce_processed_now'] = $dtt['count_endorse_processed'];


        $summary = $this->mymodel->selectWithQuery("SELECT COUNT(DISTINCT influencer) as count
        FROM endorse WHERE id_campaign = '$id_parent'  ");
        $summary = $summary[0];
        $dtt['count_influencer'] = intval($summary['count']);
        $dt['ci_now'] = $dtt['count_influencer'];

        $summary = $this->mymodel->selectWithQuery("SELECT COUNT(DISTINCT influencer) as count
        FROM endorse WHERE id_campaign = '$id_parent' AND status = 'Aktif'  ");
        $summary = $summary[0];
        $dtt['count_influencer_active'] = intval($summary['count']);
        $dt['ci_active_now'] = $dtt['count_influencer_active'];

        $summary = $this->mymodel->selectWithQuery("SELECT COUNT(DISTINCT influencer) as count
        FROM endorse WHERE id_campaign = '$id_parent' AND status = 'Aktif'  
        AND link_upload != '' ");
        $summary = $summary[0];
        $dtt['count_influencer_processed'] = intval($summary['count']);
        $dt['ci_processed_now'] = $dtt['count_influencer_processed'];

        // print_r($query_yesterday);die;
        $dt['ci_before'] =  $query_yesterday['ci_now'];
        $dt['ci_active_before'] = $query_yesterday['ci_active_now'];
        $dt['ci_processed_before'] = $query_yesterday['ci_processed_now'];

        $dt['ce_before'] =  $query_yesterday['ce_now'];
        $dt['ce_active_before'] = $query_yesterday['ce_active_now'];
        $dt['ce_processed_before'] = $query_yesterday['ce_processed_now'];

        $dt['ci_before'] =  $query_yesterday['ci_now'];
        $dt['ci_active_before'] = $query_yesterday['ci_active_now'];
        $dt['ci_processed_before'] = $query_yesterday['ci_processed_now'];
        $dt['ce_before'] =  $query_yesterday['ce_now'];
        $dt['ce_active_before'] = $query_yesterday['ce_active_now'];
        $dt['ce_processed_before'] = $query_yesterday['ce_processed_now'];

        $dt['ci_after'] =  $dt['ci_now'];
        $dt['ci_active_after'] = $dt['ci_active_now'];
        $dt['ci_processed_after'] = $dt['ci_processed_now'];
        $dt['ce_after'] =  $dt['ce_now'];
        $dt['ce_active_after'] = $dt['ce_active_now'];
        $dt['ce_processed_after'] = $dt['ce_processed_now'];

        $dt['ci_now'] =  $dt['ci_after'] - $dt['now_before'];
        $dt['ci_active_now'] = $dt['ci_active_after'] - $dt['ci_active_before'];
        $dt['ci_processed_now'] = $dt['ci_processed_after'] - $dt['ci_processed_before'];
        $dt['ce_now'] =  $dt['ce_after'] - $dt['ce_before'];
        $dt['ce_active_now'] = $dt['ce_active_after'] - $dt['ce_active_before'];
        $dt['ce_processed_now'] = $dt['ce_processed_after'] - $dt['ce_processed_before'];

        // $dt['is_cron'] = '1';
        $dt_tmp = array();
        foreach ($dt as $kt => $vt) {
            $dt_tmp[$kt] = strval($vt);
        }
        $dt = $dt_tmp;

        if ($query) {
            $dt['updated_at'] = DATE("Y-m-d H:i:s");
            $dt['updated_by'] = strval($user['id']);
            $this->db->update('endorse_campaign_logs', $dt, array('id' => $query['id']));
            // $id_parent = $query['id'];
        } else {
            $dt['created_at'] = DATE("Y-m-d H:i:s");
            $dt['created_by'] = strval($user['id']);
            $this->db->insert('endorse_campaign_logs', $dt);
            // $id_parent = $this->db->insert_id();
        }

        $dt_tmp = array();
        foreach ($dtt as $kt => $vt) {
            $dt_tmp[$kt] = strval($vt);
        }
        $dtt = $dt_tmp;

        $campaign = $this->mymodel->selectDataOne('endorse_campaign', array('id' => $id_parent));
        $dt['brand'] = strval($campaign['brand']);

        $dtt['updated_at'] = DATE("Y-m-d H:i:s");
        $dtt['updated_by'] = strval($user['id']);
        $this->db->update('endorse_campaign', $dtt, array('id' => $v['id']));
    }

    public function clone()
    {
        $id = $_GET['id'];
        $data['data']['id'] = $id;
        $this->load->view("endorse/clone", $data);
    }

    public function clone_process()
    {
        $user = $_SESSION['user'];
        $id = $_POST['id'];

        $query = $this->mymodel->selectDataOne("endorse", array('id' => $id));
        if ($query) {

            $dt = array();
            $dt = $query;
            $dt['created_at'] = DATE("Y-m-d H:i:s");
            $dt['sync_at'] = "";
            unset($dt['id']);
            unset($dt['cpm']);
            unset($dt['views']);
            unset($dt['comment']);
            unset($dt['likes']);
            unset($dt['share_save']);
            $this->db->insert('endorse', $dt);

            $id_parent = $query['id_campaign'];
            $this->update_endorse_parent($id_parent);
            $msg = 'Kloning konten berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = "Kloning konten tidak berhasil!";
            echo $this->template->alert_danger($msg);
        }
    }


    public function sync()
    {
        $id = $_GET['id'];
        $data['data']['id'] = $id;
        $this->load->view("endorse/sync", $data);
    }

    public function sync_process()
    {
        $user = $_SESSION['user'];
        $id = $_POST['id'];

        $query = $this->mymodel->selectWithQuery("SELECT * FROM endorse WHERE id = '$id'");

        if ($query[0]['status'] != "Aktif") {
            echo $this->template->alert_danger("Pastikan status endorse aktif.");
            die;
        } else if ($query[0]['status_campaign'] != "Aktif") {
            echo $this->template->alert_danger("Pastikan status campaign aktif.");
            die;
        }

        $v = $query[0];
        $detail = $query[0];
        $response = $this->template->get_social_media($v['platform'], $v['link_upload']);

        $id_endorse = $v['id'];
        $today = DATE("Y-m-d");
        $query_yesterday = $this->mymodel->selectWithQuery("SELECT * 
        FROM endorse_logs
        WHERE id_endorse = '$id_endorse' AND date < '$today' AND views_after > 0 ORDER BY date DESC LIMIT 1");
        $query_yesterday = $query_yesterday[0];


        $dt = array();

        $dt['status'] = strval($v['status']);
        $dt['status_campaign'] = strval($v['status_campaign']);
        $dt['sync_at'] = DATE("Y-m-d H:i:s");

        if ($response['data']['created_at']) {
            $dt['posting_at'] = $response['data']['created_at'];
        }

        $dt['likes'] = intval($query_yesterday['likes_after']);
        $dt['comment'] = intval($query_yesterday['comment_after']);
        $dt['share_save'] = intval($query_yesterday['share_save_after']);
        $dt['views'] = intval($query_yesterday['views_after']);

        if ($response['data']['view'] > 0) {
            $dt['likes'] = $response['data']['like'];
            $dt['comment'] = $response['data']['comment'];
            $dt['share_save'] = doubleval($response['data']['share']) + doubleval($response['data']['collect']);
            $dt['views'] = $response['data']['view'];
        }

        $dt['likes'] = $response['data']['like'];
        $dt['comment'] = $response['data']['comment'];
        $dt['share_save'] = doubleval($response['data']['share']) + doubleval($response['data']['collect']);
        $dt['views'] = $response['data']['view'];


        if ($dt['views'] >= 50000) {
            $id_influencer = $detail['influencer'];
            $creator = $this->mymodel->selectWithQuery("SELECT follower
                    FROM influencer WHERE id = '$id_influencer'");
            $creator = $creator[0];
            $percentage = 0;
            $follower = intval($creator['follower']);
            if ($follower > 0) {
                $batas = intval($follower * 30 / 100);
                if ($dt['views'] >= $batas) {
                    $dt['is_fyp'] = "1";
                }
            } else {
                $dt['is_fyp'] = "1";
            }
        }

        if ($v['total_cost'] > 0 && $dt['views'] > 0) {
            $dt['cpm'] = doubleval($v['total_cost']) / doubleval($dt['views']) * 1000;
        } else {
            $dt['cpm'] = 0;
        }
        $dt['updated_at'] = DATE("Y-m-d H:i:s");

        if ($this->db->update('endorse', $dt, array('id' => $id))) {

            unset($dt['is_fyp']);
            unset($dt['posting_at']);
            unset($dt['sync_at']);

            $today = DATE("Y-m-d");
            // $today = "2024-06-18";
            $yesterday = DATE('Y-m-d', strtotime($today . " -1 days"));

            $id_endorse = $v['id'];
            $query = $this->mymodel->selectWithQuery("SELECT id
                FROM endorse_logs
                WHERE id_endorse = '$id_endorse' AND date = '$today' ");
            $query = $query[0];


            $dt['id_endorse'] = strval($v['id']);
            $dt['id_campaign'] = strval($v['id_campaign']);
            $dt['influencer'] = strval($v['influencer']);
            $dt['date'] = $today;


            $dtt = array();
            $dtt['likes'] = doubleval($dt['likes']);
            $dtt['comment'] = doubleval($dt['comment']);
            $dtt['share_save'] = doubleval($dt['share_save']);
            $dtt['views'] = doubleval($dt['views']);
            $dtt['cpm'] = doubleval($dt['cpm']);

            $dt['total_cost'] = doubleval($v['total_cost']);

            $dt['link_upload'] = strval($v['link_upload']);
            $dt['platform'] = strval($v['platform']);

            $dt['likes_after'] = intval($dt['likes']);
            $dt['comment_after'] = intval($dt['comment']);
            $dt['share_save_after'] = intval($dt['share_save']);
            $dt['views_after'] = intval($dt['views']);

            if ($v['total_cost'] > 0 && $dt['views_after'] > 0) {
                $dt['cpm_after'] = doubleval($v['total_cost']) / doubleval($dt['views_after']) * 1000;
            } else {
                $dt['cpm_after'] = 0;
            }

            $dt['likes'] -= intval($query_yesterday['likes_after']);
            $dt['comment'] -= intval($query_yesterday['comment_after']);
            $dt['share_save'] -= intval($query_yesterday['share_save_after']);
            $dt['views'] -= intval($query_yesterday['views_after']);

            if ($v['total_cost'] > 0 && $dt['views'] > 0) {
                $dt['cpm'] = doubleval($v['total_cost']) / doubleval($dt['views']) * 1000;
            } else {
                $dt['cpm'] = 0;
            }

            $dt['likes_before'] = intval($query_yesterday['likes_after']);
            $dt['comment_before'] = intval($query_yesterday['comment_after']);
            $dt['share_save_before'] = intval($query_yesterday['share_save_after']);
            $dt['views_before'] = intval($query_yesterday['views_after']);

            if ($v['total_cost'] > 0 && $dt['views_before'] > 0) {
                $dt['cpm_before'] = doubleval($v['total_cost']) / doubleval($dt['views_before']) * 1000;
            } else {
                $dt['cpm_before'] = 0;
            }

            $dt['brand'] = strval($detail['brand']);

            $dt_tmp = array();
            foreach ($dt as $kt => $vt) {
                $dt_tmp[$kt] = strval($vt);
            }
            $dt = $dt_tmp;

            if ($query) {
                $dt['updated_at'] = DATE("Y-m-d H:i:s");
                $dt['updated_by'] = strval($user['id']);
                $this->db->update('endorse_logs', $dt, array('id' => $query['id']));
                $id_parent = $query['id'];
            } else {
                $dt['created_at'] = DATE("Y-m-d H:i:s");
                $dt['created_by'] = strval($user['id']);
                $this->db->insert('endorse_logs', $dt);
                $id_parent = $this->db->insert_id();
            }

            $id_parent = $detail['id_campaign'];
            $this->update_endorse_parent($id_parent);
        }

        if ($response['status'] == true) {
            $msg = 'Refresh data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            echo $this->template->alert_danger($response['msg']);
        }
    }


    public function edit()
    {
        $id = $_GET['id'];

        $query = $this->mymodel->selectWithQuery("SELECT * FROM endorse WHERE id = '$id'");

        $data['data'] = $query[0];

        $data['pic'] = $this->mymodel->selectWithQuery("SELECT * FROM user WHERE role IN ('1', '2', '11') ORDER BY full_name ASC");
        $data['brand'] = $this->mymodel->selectWithQuery("SELECT * FROM brand ORDER BY code ASC");
        $data['influencer'] = $this->mymodel->selectWithQuery("SELECT * FROM influencer ORDER BY full_name ASC");
        $data_row = $this->mymodel->selectWithQuery("SELECT product, product_text FROM endorse_campaign WHERE id = '$id_campaign'");

        $row = $data_row[0] ?? ['product' => '', 'product_text' => ''];

        $product_ids = explode(',', $row['product']);
        $product_names = explode(',', $row['product_text']);

        $data['produk'] = [];
        foreach ($product_ids as $i => $id) {
            $data['produk'][] = [
                'id' => trim($id),
                'name' => trim($product_names[$i] ?? '')
            ];
        }

        $data['product_all'] = $this->mymodel->selectWithQuery("
            SELECT id, name
            FROM product
            WHERE
                is_operational = 0
                AND status = 'Aktif'
                AND (
                    is_varian = 1
                    OR (is_varian = 0 AND (parent_id IS NULL OR parent_id = ''))
                )
            ORDER BY name ASC
        ");

        $data['niche'] = $this->mymodel->selectWithQuery("SELECT DISTINCT niche FROM niche ORDER BY niche ASC");

        $query = $this->mymodel->selectWithQuery("SELECT * FROM brand ORDER BY name ASC");

        $data['brand'] = $query;
        $this->load->view("endorse/edit", $data);
    }

    public function update()
    {
        $user = $_SESSION['user'];

        $id = $_POST['id'];
        $id_campaign = $_POST['id_campaign'];
        $dt = $_POST['dt'];
        $dt['updated_at'] = DATE("Y-m-d H:i:s");
        $dt['updated_by'] = $user['id'];

        // Ambil data lama untuk perbandingan
        $old_data = $this->mymodel->selectDataOne('endorse', array('id' => $id));

        if ($dt['status_endorse'] == "Barang Dikirim" && $dt['status_endorse'] != $_POST['status_endorse_existing']) {
            $dt['barang_dikirim_at'] = DATE("Y-m-d H:i:s");
        }

        if ($dt['link_upload']) {
            $dt['status_endorse'] = 'Posted Content';

            if (preg_match('#tiktok\.com/@([^/]+)/#', $dt['link_upload'], $match)) {
                $usernameFromUrl = $match[1];

                $influencerData = $this->mymodel->selectDataOne('influencer', ['username' => $usernameFromUrl]);
                if ($influencerData) {
                    $dt['influencer'] = $influencerData['id'];
                    $dt['nama_creator'] = $influencerData['username'];
                } else {
                    $msg = "Username '$usernameFromUrl' tidak ditemukan di database influencer.";
                    echo $this->template->alert_danger($msg);
                    die;
                }
            } else {
                $msg = "Link upload tidak valid atau tidak dapat mengambil username.";
                echo $this->template->alert_danger($msg);
                die;
            }
        } else {
            $id_data = $dt['influencer'];
            $detail = $this->mymodel->selectWithQuery("SELECT * FROM influencer WHERE id = '$id_data'");
            $detail = $detail[0];
            $dt['nama_creator'] = strval($detail['username']);
        }

        if ($_FILES['file']['name']) {
            $input = $this->validate([
                'file' => [
                    'uploaded[file]',
                    'mime_in[file,image/jpg,image/jpeg,image/png]',
                    'max_size[file,1024]',
                ]
            ]);

            if (!$input) {
                $msg = 'Pastikan tipe file .jpg, .jpeg atau .png!';
                echo $this->template->alert_danger($msg);
                die;
            } else {
                $img = $this->request->getFile('file');
                $dir = str_replace('public/', '', FCPATH . 'assets/img/endorse/');
                $img->move($dir);
                $data = [
                    'name' =>  $img->getName(),
                    'type'  => $img->getClientMimeType()
                ];
                $currentFileName = $dir . $data['name'];
                $newfile = $id . '.' . substr(strrchr($data['name'], "."), 1);
                $newFileName = $dir . $newfile;
                rename($currentFileName, $newFileName);
                $dt['img'] = $newfile;
            }
        }

        // Handle media_attachment upload (image/video)
        if (isset($_FILES['media_attachment']) && $_FILES['media_attachment']['name']) {
            $upload_path = FCPATH . 'assets/img/endorse/';

            // Ensure directory exists
            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0755, true);
            }

            $config['upload_path'] = $upload_path;
            $config['allowed_types'] = 'jpg|jpeg|png|mp4|mov|avi';
            $config['max_size'] = 10240; // 10MB
            $config['file_name'] = DATE('Ymdhis') . '_media_' . $id;
            $config['overwrite'] = FALSE;

            $this->load->library('upload', $config);

            if ($this->upload->do_upload('media_attachment')) {
                $upload_data = $this->upload->data();
                $dt['media_attachment'] = $upload_data['file_name'];
            } else {
                $msg = 'Upload media gagal: ' . $this->upload->display_errors('', '');
                echo $this->template->alert_danger($msg);
                die;
            }
        }

        $dt['updated_at'] = DATE("Y-m-d H:i:s");

        $dat = $this->mymodel->selectDataOne('endorse', array('id' => $id));
        $json = json_decode($dat['logs'], true);
        $json_end = array();
        if ($json) {
            $json_end = end($json);
        }
        if ($json_end['status'] != $dt['status_endorse']) {
            $arr = array();
            $arr['status'] = $dt['status_endorse'];
            $arr['created_by'] = $_SESSION['user']['id'];
            $arr['created_text'] = $_SESSION['user']['code'];
            $arr['created_at'] = DATE("Y-m-d H:i:s");
            $json[] = $arr;
            $dt['logs'] = json_encode($json, true);
        }

        if ($this->db->update('endorse', $dt, array('id' => $id))) {

            $id_parent = $id_campaign;
            $this->update_endorse_parent($id_parent);

            $nama_creator = $dt['nama_creator'];

            $count = $this->mymodel->selectWithQuery("SELECT COUNT(endorse.id) as total, title 
                    FROM endorse 
                    INNER JOIN endorse_campaign ON endorse.id_campaign = endorse_campaign.id
                    WHERE nama_creator = '$nama_creator' AND status_endorse IN ('Posted Content', 'Draft Content') 
                    GROUP BY title; ");

            if (count($count) == 1) {
                $dti['status_reach'] = 'Pernah Kerjasama';
            } else if (count($count) > 1) {
                $dti['status_reach'] = 'Repeat Kerjasama';
            } else if (count($count) < 1) {
                $dti['status_reach'] = 'Belum Reachout';
            }

            $this->db->update('influencer', $dti, array('id' => $id_data));


            // KIRIM NOTIFIKASI JIKA STATUS = 'Review'
            if ($dt['status_endorse'] == 'Review') {
                $spv = $this->mymodel->selectWithQuery("SELECT spv FROM endorse_campaign WHERE id = '$id_campaign'");
                $spv = $spv[0]['spv'] ?? null;

                if (!empty($spv)) {
                    $spv_user_id = $user = $this->mymodel->selectWithQuery("SELECT id FROM user WHERE full_name = '$spv'");
                    $spv_user_id = $spv_user_id[0]['id'] ?? null;
                    
                    if ($spv_user_id) {
                        $campaign_title = $campaign['title'] ?? 'Campaign';
                        $title = 'Endorse Baru Perlu Review';
                        $message = "Ada endorse baru dari creator {$nama_creator} untuk campaign '{$campaign_title}' yang memerlukan review Anda.";

                        $this->send_notification(
                            $spv_user_id,
                            $title,
                            $message,
                            'warning',
                            'endorse',
                            $endorse_id
                        );
                    }
                }
            }


            $msg = 'Update data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Update data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }

    public function set_pengiriman_barang()
    {
        $shipping_ids = $this->input->post('shipping_ids');
        $link_mou = $this->input->post('link_mou');
        
        if (empty($shipping_ids)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Tidak ada konten yang dipilih'
            ]);
            return;
        }
        
        if (empty($link_mou)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Link MOU harus diisi'
            ]);
            return;
        }
        
        // Update link_mou untuk setiap endorse yang dipilih
        $ids_array = explode(',', $shipping_ids);
        $updated_count = 0;
        
        foreach ($ids_array as $id) {
            // Ambil data endorse yang lama
            $endorse_data = $this->mymodel->selectDataOne('endorse', ['id' => $id]);
            
            $update_data = [
                'link_mou' => $link_mou,
                'updated_at' => date('Y-m-d H:i:s'),
                'barang_dikirim_at' => date('Y-m-d H:i:s')
            ];
            
            // Tambahkan logs barang dikirim
            $json = json_decode($endorse_data['logs'], true);
            $json_end = array();
            if ($json) {
                $json_end = end($json);
            }
            
            // Cek apakah status terakhir bukan "Barang Dikirim"
            if ($json_end['status'] != 'Barang Dikirim') {
                $arr = array();
                $arr['status_pengiriman'] = 'Barang Dikirim';
                $arr['created_by'] = $_SESSION['user']['id'];
                $arr['created_text'] = $_SESSION['user']['code'];
                $arr['created_at'] = date("Y-m-d H:i:s");
                $json[] = $arr;
                $update_data['logs'] = json_encode($json, true);
            }
            
            $result = $this->mymodel->updateData('endorse', $update_data, ['id' => $id]);
            if ($result) {
                $updated_count++;
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => "Link MOU berhasil diupdate untuk {$updated_count} konten"
        ]);
    }

    // Tambahkan di controller endorse
    public function send_telegram()
    {
        $id_campaign = $this->input->post('id_campaign');
        $nama_creator = $this->input->post('nama_creator');
        $produkData = json_decode($this->input->post('produk_data'), true);
        $jenis_pengiriman = $this->input->post('jenis_pengiriman');
        $detail_pic = $this->input->post('detail_pic');

        header('Content-Type: application/json');
        try {
            if (is_array($produkData) && !empty($produkData)) {
                $hasQty = false;
                foreach ($produkData as $row) {
                    if (!empty($row['qty']) && (int)$row['qty'] > 0) {
                        $hasQty = true;
                        break;
                    }
                }

                if ($hasQty) {
                    $endorse = $this->mymodel->selectDataOne('endorse', ['nama_creator' => $nama_creator, 'id_campaign' => $id_campaign]);

                    $pic_raw = $endorse['pic'] ?? '-';
                    $pic = strtoupper(str_replace(' ', '', $pic_raw));
                    $dt = [
                        'nama_creator' => $endorse['nama_creator'],
                        'pic' => $pic,
                        'jenis_pengiriman' => $jenis_pengiriman,
                        'detail_pic' => $detail_pic
                    ];
                    
                    $result = $this->send_bot_mou($nama_creator, $id_campaign, $produkData, $dt);
                    
                    if ($result) {
                        echo json_encode([
                            'success' => true,
                            'message' => 'Notifikasi pengiriman berhasil dikirim!'
                        ]);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Gagal mengirim notifikasi!'
                        ]);
                    }
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Tidak ada produk dengan quantity yang valid!'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Data produk tidak valid!'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
    }

    private function send_bot_mou($nama_creator, $id_campaign, $produkList, $dt)
    {
        $influencer = $this->mymodel->selectDataOne('influencer', ['username' => $nama_creator]);

        $username   = $influencer['username'] ?? '-';
        $full_name  = $influencer['full_name'] ?? '-';
        $alamat     = $influencer['alamat'] ?? '-';
        $no_hp      = $influencer['phone'] ?? '-';
        $pic        = $dt['pic'] ?? '-';
        $detail_pic = $dt['detail_pic'] ?? '-';
        $jenis_pengiriman = $dt['jenis_pengiriman'] ?? 'Endorse';

        $lines = [];
        foreach ($produkList as $row) {
            $pname = trim((string)($row['nama'] ?? ''));
            $pqty  = (int)($row['qty'] ?? 0);
            if ($pname !== '' && $pqty > 0) {
                $lines[] = "{$pname} {$pqty}";
            }
        }
        $produkText = implode("\n", $lines);

        $message = "<b>{$jenis_pengiriman}</b>\n\n"
                . "Nama: {$full_name}\n"
                . "Alamat: {$alamat}\n"
                . "No hp: {$no_hp}\n\n"
                . "<b>Produk:</b>\n{$produkText}\n\n"
                . "#{$pic} ({$detail_pic})";

        $this->load->config('telegram');
        $chatId = $this->config->item('telegram_group_chat_id');
        $result = $this->telegrambot->sendMessage($chatId, $message, 'HTML');
        
        return $result;
    }


    public function create()
    {
        $data['data'] = array();

        $data['detail']['id'] = $_GET['id'];
        $data['user'] = $_SESSION['user'];
        $id_campaign = $_GET['id'];
        // print_r($_GET);

        $data['pic'] = $this->mymodel->selectWithQuery("SELECT * FROM user WHERE role IN ('1', '2', '11') ORDER BY full_name ASC");
        $data['brand'] = $this->mymodel->selectWithQuery("SELECT * FROM brand ORDER BY code ASC");
        $data['influencer'] = $this->mymodel->selectWithQuery("SELECT * FROM influencer ORDER BY full_name ASC");
        $data_row = $this->mymodel->selectWithQuery("SELECT product, product_text FROM endorse_campaign WHERE id = '$id_campaign'");

        $row = $data_row[0] ?? ['product' => '', 'product_text' => ''];

        $product_ids = explode(',', $row['product']);
        $product_names = explode(',', $row['product_text']);

        $data['produk'] = [];
        foreach ($product_ids as $i => $id) {
            $data['produk'][] = [
                'id' => trim($id),
                'name' => trim($product_names[$i] ?? '')
            ];
        }

        $data['product_all'] = $this->mymodel->selectWithQuery("
            SELECT id, name
            FROM product
            WHERE
                is_operational = 0
                AND status = 'Aktif'
                AND (
                    is_varian = 1
                    OR (is_varian = 0 AND (parent_id IS NULL OR parent_id = ''))
                )
            ORDER BY name ASC
        ");

        $data['niche'] = $this->mymodel->selectWithQuery("SELECT DISTINCT niche FROM niche ORDER BY niche ASC");

        $this->load->view("endorse/create", $data);
    }

    public function ajukan_payment()
    {
        $data['data'] = array();

        $id = $this->input->get('id', TRUE);

        $data['detail']['id'] = $id;

        $data['data'] = $this->mymodel->selectWithQuery(
            "SELECT endorse.*, influencer.bank, influencer.no_rekening, influencer.pemilik_rekening
             FROM endorse 
             INNER JOIN influencer ON endorse.nama_creator = influencer.username
             WHERE endorse.id = '$id'"
        );

        $this->load->view("endorse/ajukan_payment", $data);
    }

    public function batal_ajukan_payment()
    {
        $data['data'] = array();

        $id = $this->input->get('id', TRUE);

        $data['detail']['id'] = $id;

        $data['data'] = $this->mymodel->selectWithQuery(
            "SELECT *
             FROM endorse WHERE id = '$id'"
        );

        $this->load->view("endorse/batal_ajukan_payment", $data);
    }


    public function store()
    {
        $user = $_SESSION['user'];

        $id = $_POST['id'];
        $id_campaign = $_POST['id_campaign'];
        $dt = $_POST['dt'];
        $dt['created_at'] = DATE("Y-m-d H:i:s");
        $dt['created_by'] = $user['id'];

        $this->db->select('status');
        $campaign = $this->mymodel->selectDataOne('endorse_campaign', array('id' => $id_campaign));
        $dt['status_campaign'] = $campaign['status'];
        
        if ($dt['status_endorse'] == "Barang Dikirim" && $dt['status_endorse'] != $_POST['status_endorse_existing']) {
            $dt['barang_dikirim_at'] = DATE("Y-m-d H:i:s");
        }

        if ($dt['link_upload']) {
            $dt['status_endorse'] = 'Posted Content';

            if (preg_match('#tiktok\.com/@([^/]+)/#', $dt['link_upload'], $match)) {
                $usernameFromUrl = $match[1];

                $influencerData = $this->mymodel->selectDataOne('influencer', ['username' => $usernameFromUrl]);
                if ($influencerData) {
                    $dt['influencer'] = $influencerData['id'];
                    $dt['nama_creator'] = $influencerData['username'];
                } else {
                    $msg = "Username '$usernameFromUrl' tidak ditemukan di database influencer.";
                    echo $this->template->alert_danger($msg);
                    die;
                }
            } else {
                $msg = "Link upload tidak valid atau tidak dapat mengambil username.";
                echo $this->template->alert_danger($msg);
                die;
            }
        } else {
            $id_data = $dt['influencer'];
            $detail = $this->mymodel->selectWithQuery("SELECT * FROM influencer WHERE id = '$id_data'");
            $detail = $detail[0];
            $dt['nama_creator'] = strval($detail['username']);
        }

        if ($_FILES['file']['name']) {
            $input = $this->validate([
                'file' => [
                    'uploaded[file]',
                    'mime_in[file,image/jpg,image/jpeg,image/png]',
                    'max_size[file,1024]',
                ]
            ]);

            if (!$input) {
                $msg = 'Pastikan tipe file .jpg, .jpeg atau .png!';
                echo $this->template->alert_danger($msg);
                die;
            } else {
                $img = $this->request->getFile('file');
                $dir = str_replace('public/', '', FCPATH . 'assets/img/endorse/');
                $img->move($dir);
                $data = [
                    'name' =>  $img->getName(),
                    'type'  => $img->getClientMimeType()
                ];
                $currentFileName = $dir . $data['name'];
                $newfile = DATE('Ymdhis') . '.' . substr(strrchr($data['name'], "."), 1);
                $newFileName = $dir . $newfile;
                rename($currentFileName, $newFileName);
                $dt['img'] = $newfile;
            }
        }

        // Handle media_attachment upload (image/video)
        if (isset($_FILES['media_attachment']) && $_FILES['media_attachment']['name']) {
            $upload_path = FCPATH . 'assets/img/endorse/';

            // Ensure directory exists
            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0755, true);
            }

            $config['upload_path'] = $upload_path;
            $config['allowed_types'] = 'jpg|jpeg|png|mp4|mov|avi';
            $config['max_size'] = 10240; // 10MB
            $config['file_name'] = DATE('Ymdhis') . '_media';
            $config['overwrite'] = FALSE;

            $this->load->library('upload', $config);

            if ($this->upload->do_upload('media_attachment')) {
                $upload_data = $this->upload->data();
                $dt['media_attachment'] = $upload_data['file_name'];
            } else {
                $msg = 'Upload media gagal: ' . $this->upload->display_errors('', '');
                echo $this->template->alert_danger($msg);
                die;
            }
        }

        $arr = array();
        $arr['status'] = $dt['status_endorse'];
        $arr['created_by'] = $_SESSION['user']['id'];
        $arr['created_text'] = $_SESSION['user']['code'];
        $arr['created_at'] = DATE("Y-m-d H:i:s");
        $json[] = $arr;
        $dt['logs'] = json_encode($json, true);

        if ($this->db->insert('endorse', $dt)) {
            $endorse_id = $this->db->insert_id(); 
            
            $id_parent = $id_campaign;
            $this->update_endorse_parent($id_parent);
            $nama_creator = $dt['nama_creator'];

            $count = $this->mymodel->selectWithQuery("SELECT COUNT(endorse.id) as total, title 
                    FROM endorse 
                    INNER JOIN endorse_campaign ON endorse.id_campaign = endorse_campaign.id
                    WHERE nama_creator = '$nama_creator' AND status_endorse IN ('Posted Content', 'Draft Content') 
                    GROUP BY title; ");

            $dti = array();
            
            if (count($count) == 1) {
                $dti['status_reach'] = 'Pernah Kerjasama';
                $this->db->update('influencer', $dti, array('id' => $id_data));
            } else if (count($count) > 1) {
                $dti['status_reach'] = 'Repeat Kerjasama';
                $this->db->update('influencer', $dti, array('id' => $id_data));
            }

            // KIRIM NOTIFIKASI JIKA STATUS = 'Review'
            if ($dt['status_endorse'] == 'Review') {
                $spv = $this->mymodel->selectWithQuery("SELECT spv FROM endorse_campaign WHERE id = '$id_campaign'");
                $spv = $spv[0]['spv'] ?? null;

                if (!empty($spv)) {
                    $spv_user_id = $user = $this->mymodel->selectWithQuery("SELECT id FROM user WHERE full_name = '$spv'");
                    $spv_user_id = $spv_user_id[0]['id'] ?? null;
                    
                    if ($spv_user_id) {
                        $campaign_title = $campaign['title'] ?? 'Campaign';
                        $title = 'Endorse Baru Perlu Review';
                        $message = "Ada endorse baru dari creator {$nama_creator} untuk campaign '{$campaign_title}' yang memerlukan review Anda.";

                        $this->send_notification(
                            $spv_user_id,
                            $title,
                            $message,
                            'warning',
                            'endorse',
                            $endorse_id
                        );
                    }
                }
            }


            $msg = 'Tambah data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Tambah data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }
    public function remove()
    {
        $id = $_GET['id'];

        $query = $this->db->get_where('endorse', ['id' => $id]);
        $data['data'] = $query->row_array();

        $this->load->view("endorse/delete", $data);
    }


    public function delete()
    {
        $id = $_POST['id'];
        $id_campaign = $_POST['id_campaign'];
        $nama_creator = $_POST['nama_creator'];

        if (empty($id) || empty($id_campaign) || empty($nama_creator)) {
            echo $this->template->alert_danger('Parameter tidak lengkap!');
            return;
        }

        $this->db->delete('endorse_logs', array('id_endorse' => $id));
        $this->db->delete('payment_logs', array('id_campaign' => $id_campaign, 'nama_influencer' => $nama_creator));

        if ($this->db->delete('endorse', array('id' => $id))) {
            $msg = 'Hapus data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Hapus data tidak berhasil!';
            echo $this->template->alert_danger($msg);
            echo "DB Error: " . $this->db->error()['message'];
        }
    }


    public function action()
    {
        $data['id_campaign'] = $_GET['id_campaign'];

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
            $data['question'] = "Apakah kamu yakin ingin menghapus data endorse ini?";
            $data['btn'] = "Hapus Data";
        } else if ($code == "refresh_data") {
            $data['question'] = "Apakah kamu yakin ingin merefresh data endorse ini?";
            $data['btn'] = "Refresh Data";
        } else if ($code == "ubah_status") {
            $data['question'] = "Apakah kamu yakin ingin mengubah status data endorse ini?";
            $data['btn'] = "Ubah Status Konten";
        } else if ($code == "ubah_status_data") {
            $data['question'] = "Apakah kamu yakin ingin mengubah status data ini?";
            $data['btn'] = "Ubah Status";
        }
        $this->load->view("endorse/action", $data);
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
                $this->db->delete('endorse', "id IN ($list_id)");
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

            if ($list_apid) {
                $data =  $this->mymodel->selectWithQuery("SELECT *
                FROM endorse
                WHERE id IN ($list_id) AND status = 'Aktif' 
                AND status_campaign = 'Aktif' AND link_upload != ''
                 ");
                $list_id = "";
                foreach ($data as $k => $v) {
                    $list_id .= "" . $v['id'] . ",";
                }
                $list_id = substr($list_id, 0, -1);
            }
            if ($list_id) {
                $id_campaign = $_POST['id_campaign'];
                $url = base_url() . '/endorse/sync-all-process?id_campaign=' . $id_campaign . '&mode=refresh_data&ids=' . $list_id;
                $curl = curl_init();

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
                // $response = json_decode($response,true);
                curl_close($curl);
            } else {
                $msg = 'Pastikan kamu sudah memilih minimal 1 data!';
                echo $this->template->alert_danger($msg);
            }
        } else if ($code == "ubah_status") {
            $status = $_POST['status'];
            $ids = array();
            foreach ($id as $k => $v) {
                if ($v > 0) {
                    $list_id .= "" . $v . ",";
                    $ids[]  = $v;
                }
            }
            $list_id = substr($list_id, 0, -1);
            if ($list_id) {

                $list_id = explode(',', $list_id);

                foreach ($ids as $k => $v) {

                    $dat = $this->mymodel->selectDataOne('endorse', array('id' => $v));
                    $json = json_decode($dat['logs'], true);
                    $json_end = array();
                    if ($json) {
                        $json_end = end($json);
                    }
                    if ($json_end['status'] != $status) {
                        $arr = array();
                        $arr['status'] = $status;
                        $arr['created_by'] = $_SESSION['user']['id'];
                        $arr['created_text'] = $_SESSION['user']['code'];
                        $arr['created_at'] = DATE("Y-m-d H:i:s");
                        $json[] = $arr;

                        $dtt = array();
                        $dtt['logs'] = json_encode($json, true);
                        $dtt['status_endorse'] = $status;
                        $dtt['updated_at'] = DATE("Y-m-d H:i:s");
                        $this->db->update('endorse', $dtt, array('id' => $v));
                    }
                }
                // die;

                // $dtt = array();
                // $dtt['status_endorse'] = $status;
                // $dtt['updated_at'] = DATE("Y-m-d H:i:s");
                // $this->db->update('endorse', $dtt, "id IN ($list_id)");

                $msg = 'Ubah status endorse berhasil!';
                echo $this->template->alert_success($msg);
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
                $dtt['status'] = $status;
                $dtt['updated_at'] = DATE("Y-m-d H:i:s");
                $this->db->update('endorse', $dtt, "id IN ($list_id)");

                $msg = 'Ubah status berhasil!';
                echo $this->template->alert_success($msg);
            } else {
                $msg = 'Pastikan kamu sudah memilih minimal 1 data!';
                echo $this->template->alert_danger($msg);
            }
        } else if ($code == "ubah_status_payment") {
            $status_pengajuan = 'FP'; 
            $ids = array();
            $list_id = '';
            
            foreach ($id as $k => $v) {
                if ($v > 0) {
                    $list_id .= $v . ",";
                    $ids[] = $v;
                }
            }
            
            if (!empty($ids)) {
                $this->db->where_in('id', $ids);
                $this->db->where('status_endorse', 'Acc');
                $endorse_items = $this->db->get('endorse')->result_array();
                
                $success_count = 0;
                
                foreach ($endorse_items as $item) {
                    $current_logs = isset($item['pengajuan_payment_logs']) ? 
                        json_decode($item['pengajuan_payment_logs'], true) : array();
                    
                    $log_entry = array(
                        'status' => 'Pengajuan Payment ' . $status_pengajuan,
                        'created_by' => $_SESSION['user']['code'],
                        'created_at' => date('Y-m-d H:i:s'),
                        'nominal_pengajuan' => $item['total_cost'],
                        'keterangan' => 'Pengajuan Full Payment'
                    );
                    
                    $current_logs[] = $log_entry;
                    
                    $data_update = [
                        'pengajuan_status_payment' => 'Pengajuan Payment ' . $status_pengajuan,
                        'nominal_pengajuan' => $item['total_cost'],
                        'keterangan_payment' => 'Pengajuan Full Payment',
                        'pengajuan_payment_logs' => json_encode($current_logs),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $this->db->where('id', $item['id']);
                    $this->db->where('status_endorse', 'Acc');
                    $update_result = $this->db->update('endorse', $data_update);
                    
                    if ($update_result) {
                        $success_count++;
                    }
                }
                
                if ($success_count > 0) {
                    $msg = 'Berhasil mengajukan Full Payment untuk ' . $success_count . ' item!';
                    echo $this->template->alert_success($msg);
                } else {
                    $msg = 'Tidak ada data yang berhasil diupdate. Pastikan status endorse sudah "Acc".';
                    echo $this->template->alert_danger($msg);
                }
            } else {
                $msg = 'Pastikan kamu sudah memilih minimal 1 data yang valid untuk diubah!';
                echo $this->template->alert_danger($msg);
            }
        }
    }

    public function logs()
    {

        $data['template'] = $this->template;

        $data['title'] = 'Campaign Logs - ' . $this->template->title();

        $date = $_GET['date'];

        $qry = " DATE(endorse_logs.date) = '$date' ";
        $qry_endorse = "";

        $id_campaign = $_GET['id_campaign'];

        if ($id_campaign) {
            $qry .= " AND endorse_logs.id_campaign = '$id_campaign' ";
            $qry_endorse .= " AND endorse.id_campaign = '$id_campaign' ";
        }

        $ids_campaign = $_GET['ids_campaign'];
        $text = '';
        foreach ($ids_campaign as $k => $v) {
            $text .= "'" . $v . "',";
        }
        $text = substr($text, 0, -1);

        if ($text) {
            $qry_endorse .= " AND endorse.id_campaign IN ($text) ";
        }


        $endorse_status = $_GET['endorse_status'];

        $statusArray = $endorse_status ? explode(',', $endorse_status) : [];
        $text = '';
        foreach ($statusArray as $k => $v) {
            $text .= "'" . $v . "',";
        }
        $text = substr($text, 0, -1);

        if ($text) {
            $qry_endorse .= " AND status_endorse IN ($text) ";
        }

        if ($_GET['keyword_category']) {
            $keyword_category = $_GET['keyword_category'];
        } else {
            $keyword_category = "Nama Creator";
        }
        $data['keyword_category'] = $keyword_category;
        $keyword = $_GET['keyword'];

        if ($keyword) {
            if ($keyword_category == "Nama Creator") {
                $qry .= " AND endorse.nama_creator LIKE '%$keyword%' ";
            } else if ($keyword_category == "Link Upload") {
                $qry .= " AND endorse.link_upload LIKE '%$keyword%' ";
            } else if ($keyword_category == "PIC") {
                $qry .= " AND endorse.pic LIKE '%$keyword%' ";
            } else if ($keyword_category == "Platform") {
                $qry .= " AND endorse.platform LIKE '%$keyword%' ";
            } else if ($keyword_category == "Task") {
                $qry .= " AND endorse.task LIKE '%$keyword%' ";
            } else if ($keyword_category == "Keterangan") {
                $qry .= " AND endorse.desc LIKE '%$keyword%' ";
            }
        }

        $query = $this->mymodel->selectWithQuery("SELECT id,total_cost
			FROM endorse
			WHERE 1=1 $qry_endorse
			");

        $list_ids = '';
        foreach ($query as $k => $v) {
            $text .= "'" . $v['id'] . "',";
        }
        $text = substr($text, 0, -1);

        if ($text) {
            $qry .= " AND id_endorse IN ($text) ";
        }

        $data['data'] = $this->mymodel->selectWithQuery("SELECT *
        FROM endorse_logs
        INNER JOIN endorse ON endorse.id = endorse_logs.id_endorse
        WHERE $qry
        ORDER BY endorse_logs.id DESC");

        $data['notif'] = '<p class="mb-1"><label class="text-notif">' . $this->template->separator_only(count($data['data'])) . ' data ditemukan!</label></p>';
        $data['url'] = base_url() . '/endorse/logs?date=' . $date . '&id_campaign=' . $id_campaign . '&keyword_category=' . $keyword_category . '&keyword=' . $keyword;
        $data['content'] = $this->load->view("endorse/logs", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function payment_logs()
    {

        $data['template'] = $this->template;

        $data['title'] = 'Payment Logs - ' . $this->template->title();

        $date = $_GET['date'];

        $id_campaign = $_GET['id_campaign'];

        $nama_creator = $_GET['nama_creator'];

        $sql_logs = "
                SELECT 
                    l.*,
                    u.code
                FROM payment_logs l
                INNER JOIN user u ON u.id = l.created_by             
                WHERE l.nama_influencer = '$nama_creator'
                AND l.id_campaign = '$id_campaign'";
        $data['data'] = $this->mymodel->selectWithQuery($sql_logs);

        $data['content'] = $this->load->view("endorse/payment_logs", $data, true);

        // var_dump($data['data']);

        $this->load->view("TemplateDashboard", $data);
    }
    public function remove_logs()
    {
        $id = $_GET['id'];
        $data['data']['id'] = $id;
        $this->load->view("endorse/delete_logs", $data);
    }

    public function delete_logs()
    {
        $id = $_POST['id'];
        if ($this->db->delete('endorse_logs', array('id' => $id))) {
            $msg = 'Hapus data berhasil!';
            echo $this->template->alert_success($msg);
        } else {
            $msg = 'Hapus data tidak berhasil!';
            echo $this->template->alert_danger($msg);
        }
    }

    function cek()
    {
        $data =  $this->mymodel->selectWithQuery("SELECT id,id_endorse
       FROM endorse_logs
       WHERE influencer = ''
       ");
        foreach ($data as $k => $v) {
            $dt = array();
            $endorse = $this->mymodel->selectDataOne('endorse', array('id' => $v['id_endorse']));
            $dt['influencer'] = strval($endorse['influencer']);
            $this->db->update('endorse_logs', $dt, array('id' => $v['id']));
        }
        echo 'success';
    }

    public function set_pengajuan_payment()
    {
        $id = $this->input->post('id');
        $id_campaign = $this->input->post('id_campaign');
        $nama_creator = $this->input->post('nama_creator');
        $is_payment_bundling = $this->input->post('is_payment_bundling');
        $status_pengajuan_payment = $this->input->post('status_pengajuan_payment');
        $status_payment = $this->input->post('status_payment');
        $nominal_pengajuan_post = (int)($this->input->post('nominal_pengajuan') ?? 0);
        $keterangan_payment = $this->input->post('keterangan_payment');
        $bank = $this->input->post('bank');
        $no_rekening = $this->input->post('no_rekening');
        $pemilik_rekening = $this->input->post('pemilik_rekening');
        $bundling_ids_raw = $this->input->post('bundling_ids', true);

        $bundling_ids = [];
        if (!empty($bundling_ids_raw)) {
            foreach (explode(',', $bundling_ids_raw) as $x) {
                $x = (int)trim($x);
                if ($x > 0) $bundling_ids[$x] = $x;
            }
            $bundling_ids = array_values($bundling_ids);
        }

        if (empty($id)) {
            echo $this->template->alert_danger('ID tidak boleh kosong!');
            return;
        }

        if ($no_rekening != '' || $bank != '' || $pemilik_rekening != '') {
            $this->db->update('influencer', ['bank' => $bank, 'no_rekening' => $no_rekening, 'pemilik_rekening' => $pemilik_rekening], ['username' => $nama_creator]);
        }

        $nama_user = $_SESSION['user'] ?? ['code' => 'system', 'id' => 0];
        $now = date('Y-m-d H:i:s');
        $status_pengajuan_label = ($status_pengajuan_payment !== '') ? ('Pengajuan Payment ' . $status_pengajuan_payment) : '';

        $this->db->trans_begin();
        try {
            $append_log = function(array $row, int $nominal_for_log) use ($status_pengajuan_label, $nama_user, $now, $keterangan_payment, $is_payment_bundling) {
                $logs = [];
                if (!empty($row['pengajuan_payment_logs'])) {
                    $decoded = json_decode($row['pengajuan_payment_logs'], true);
                    if (is_array($decoded)) $logs = $decoded;
                }
                $logs[] = [
                    'status' => $status_pengajuan_label,
                    'created_by' => $nama_user['code'] ?? 'system',
                    'created_at' => $now,
                    'nominal_pengajuan' => $nominal_for_log,
                    'bundling' => ($is_payment_bundling == '1' ? 'Bundling' : 'Non Bundling'),
                    'keterangan' => $keterangan_payment
                ];
                return json_encode($logs);
            };

            $data_update_common = [
                'pengajuan_status_payment' => $status_pengajuan_label,
                'status_payment' => ($status_payment == 'FP' ? 'DP' : ''),
                'keterangan_payment' => $keterangan_payment,
                'is_payment_bundling' => ($is_payment_bundling == '1' ? 1 : 0),
                'updated_at' => $now
            ];

            if ($is_payment_bundling === '1') {
                if (empty($bundling_ids)) {
                    $this->db->trans_rollback();
                    echo $this->template->alert_danger('Pilih minimal satu item untuk bundling.');
                    return;
                }
                $rows = $this->db->where_in('id', $bundling_ids)->get('endorse')->result_array();
                foreach ($rows as $row) {
                    $log_json = $append_log($row, $nominal_pengajuan_post);
                    $payload = $data_update_common;
                    $payload['nominal_pengajuan'] = $nominal_pengajuan_post;
                    $payload['pengajuan_payment_logs'] = $log_json;
                    if (strtoupper(trim((string)$status_pengajuan_payment)) === 'DP' && (int)($row['nominal_dibayarkan'] ?? 0) > 0) {
                        continue;
                    }
                    $this->db->update('endorse', $payload, ['id' => (int)$row['id']]);
                }
                // Tidak menulis payment_logs pada saat pengajuan (bundling)
            } else {
                $row = $this->db->get_where('endorse', ['id' => (int)$id])->row_array();
                $log_json = $append_log($row, $nominal_pengajuan_post);
                $payload = $data_update_common;
                $payload['nominal_pengajuan'] = $nominal_pengajuan_post;
                $payload['pengajuan_payment_logs'] = $log_json;
                $this->db->update('endorse', $payload, ['id' => (int)$id]);

                // Tidak menulis payment_logs pada saat pengajuan (non-bundling)
            }

            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Transaksi DB gagal.');
            }

            $this->db->trans_commit();

            $campaign = $this->db->get_where('endorse_campaign', ['id' => $id_campaign])->row_array();
            $campaign_title = $campaign['title'] ?? 'Campaign';
            $target_user_id = 2;
            $title = 'Pengajuan Payment Baru';
            $message = "Pengajuan payment {$status_pengajuan_payment} dari creator {$nama_creator} untuk campaign '{$campaign_title}' dengan nominal Rp " . number_format($nominal_pengajuan_post, 0, ',', '.');
            $this->send_notification($target_user_id, $title, $message, 'info', 'endorse', $id);

            echo $this->template->alert_success('Data payment berhasil disimpan!');
            redirect('endorse?id_campaign=' . $id_campaign);
            return;

        } catch (\Throwable $e) {
            $this->db->trans_rollback();
            echo 'Gagal menyimpan data payment. Error: ' . $e->getMessage();
            return;
        }
    }




    public function api_bundling_candidates()
    {
        $id_campaign  = $this->input->get('id_campaign', true);
        $nama_creator = $this->input->get('nama_creator', true);

        if (!$id_campaign || !$nama_creator) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['data' => [], 'error' => 'Missing params']));
        }

        $where = "WHERE e.id_campaign = ".$this->db->escape($id_campaign)."
                AND e.nama_creator = ".$this->db->escape($nama_creator)."
                AND (e.status_payment IS NULL OR e.status_payment = '' OR e.status_payment IN ('', 'DP'))";

        if (!empty($exclude_id)) {
            $where .= " AND e.id <> ".$this->db->escape($exclude_id);
        }

        $sql = "
            SELECT 
                e.*,
                c.title
            FROM endorse e
            JOIN endorse_campaign c ON c.id = e.id_campaign
            $where
            ORDER BY e.updated_at DESC, e.id DESC
            LIMIT 500
        ";

        $rows = $this->mymodel->selectWithQuery($sql);

        $data = array_map(function($r){
            $r['total_cost']        = (int) ($r['total_cost'] ?? 0);
            $r['nominal_pengajuan'] = (int) ($r['nominal_pengajuan'] ?? 0);
            return $r;
        }, $rows ?: []);

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['data' => $data]));
    }



    public function set_batal_pengajuan_payment()
    {
        $id           = (int)$this->input->post('id');
        $id_campaign  = $this->input->post('id_campaign');
        $nama_creator = $this->input->post('nama_creator');

        if (empty($id)) {
            echo $this->template->alert_danger('ID tidak boleh kosong!');
            return;
        }

        $row = $this->db->select('id, id_campaign, nama_creator, is_payment_bundling, status_endorse')
                        ->from('endorse')
                        ->where('id', $id)
                        ->get()->row_array();

        if (!$row) {
            echo $this->template->alert_danger('Data tidak ditemukan.');
            return;
        }

        $bundlingFlag = null;
        if (array_key_exists('is_payment_bundling', $row)) {
            $bundlingFlag = (int)$row['is_payment_bundling'];
        } else {
            $bundlingFlag = 0; 
        }

        $data_update = [
            'pengajuan_status_payment' => '',
            'nominal_pengajuan'        => 0,
            'keterangan_payment'       => '',
            'updated_at'               => date('Y-m-d H:i:s'),
        ];

        $this->db->trans_begin();
        try {
            if ($bundlingFlag === 1) {
                $this->db->group_start()
                            ->where('status_endorse', 'Acc')
                            ->or_where('status_endorse', 'Draft Content')
                            ->or_where('status_endorse', 'Posted Content')
                        ->group_end();

                $this->db->where('id_campaign', $row['id_campaign']);
                $this->db->where('nama_creator', $row['nama_creator']);

                if (array_key_exists('is_payment_bundling', $row)) {
                    $this->db->where('is_payment_bundling', 1);
                } else {
                    $this->db->where('is_payment_bundling', 1);
                }
            } else {
                $this->db->group_start()
                            ->where('status_endorse', 'Acc')
                            ->or_where('status_endorse', 'Draft Content')
                            ->or_where('status_endorse', 'Posted Content')
                        ->group_end();

                $this->db->where('id', $row['id']);
            }

            $update = $this->db->update('endorse', $data_update);

            if (!$update || $this->db->affected_rows() < 1) {
                throw new Exception('Gagal menyimpan data (tidak ada baris yang berubah).');
            }

            // Notifikasi
            $campaign = $this->db->get_where('endorse_campaign', ['id' => $row['id_campaign']])->row_array();
            $campaign_title = $campaign['title'] ?? 'Campaign';

            $target_user_id = 2; // sesuaikan
            $title   = 'Pengajuan Payment Dibatalkan';
            $message = "Pengajuan payment dari creator {$row['nama_creator']} untuk campaign '{$campaign_title}' telah dibatalkan";

            $this->send_notification(
                $target_user_id,
                $title,
                $message,
                'warning',
                'endorse',
                $row['id'] // referensi id yang dipakai di detail
            );

            $this->db->trans_commit();
            // Hindari echo sebelum redirect agar header tidak terkirim duluan.
            $this->session->set_flashdata('success', $this->template->alert_success('Berhasil membatalkan pengajuan payment'));
            redirect('endorse?id_campaign=' . urlencode((string)$row['id_campaign']));
            return;

        } catch (Throwable $e) {
            $this->db->trans_rollback();
            echo $this->template->alert_danger('Gagal menyimpan data payment. Silakan coba lagi. Detail: ' . htmlspecialchars($e->getMessage()));
            return;
        }
    }



    public function get_influencer_data()
    {
        $nama_creator = $this->input->post('nama_creator');
        $endorse_count = $this->mymodel->selectWithQuery("SELECT COUNT(id) AS total FROM endorse WHERE nama_creator = '$nama_creator' AND status_endorse = 'Posted Content'");
        $endorse_count = $endorse_count[0]['total'];
        $influencer = $this->mymodel->selectDataOne('influencer', array('username' => $nama_creator));
        $data = array(
            'endorse_count' => $endorse_count,
            'avg_views' => $this->template->separator_only($influencer['avg_view']),
            'cpm' => $this->template->separator_only($influencer['cpm']),
        );
        $this->output->set_content_type('application/json')->set_output(json_encode($data));
    }

    public function get_influencer_data_all()
    {
        $nama_creator = $this->input->post('nama_creator');
        $influencer = $this->mymodel->selectDataOne('influencer', array('username' => $nama_creator));
        $endorse_count = $this->mymodel->selectWithQuery("SELECT COUNT(id) AS total FROM endorse WHERE nama_creator = '$nama_creator' AND status_endorse = 'Posted Content'");
        $endorse_count = $endorse_count[0]['total'];
        $data = array(
            'endorse_count' => $endorse_count,
            'avg_views' => $this->template->separator_only($influencer['avg_view']),
            'cpm' => $this->template->separator_only($influencer['cpm']),
            'er' => $this->template->separator_only(
                        ($influencer['avg_view'] > 0) 
                            ? ($influencer['avg_interaksi'] / $influencer['avg_view'] * 100) 
                            : 0
                    ) . '%',
            'avg_views_2' => $this->template->separator_only($influencer['avg_view_2']),
            'cpm_2' => $this->template->separator_only($influencer['cpm_2']),
            'er_2' => $this->template->separator_1($influencer['avg_interaksi_2'] / $influencer['avg_view_2'] * 100) . '%',
        );
        $this->output->set_content_type('application/json')->set_output(json_encode($data));
    }

    private function send_notification($user_id, $title, $message, $type = 'info', $related_table = null, $related_id = null) {
        $notification_data = [
            'user_id' => $user_id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->insert('notifications', $notification_data);
    }

    public function get_notifications($user_id, $limit = 10) {
        $this->db->select('*');
        $this->db->where('user_id', $user_id);
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit);
        
        return $this->db->get('notifications')->result_array();
    }

    public function mark_notification_read($notification_id, $user_id) {
        $this->db->where('id', $notification_id);
        $this->db->where('user_id', $user_id);
        return $this->db->update('notifications', ['is_read' => 1]);
    }

    public function count_unread_notifications($user_id) {
        $this->db->where('user_id', $user_id);
        $this->db->where('is_read', 0);
        return $this->db->count_all_results('notifications');
    }

    public function action_save_influencer()
    {
        // Pastikan header JSON dari awal
        $this->output->set_content_type('application/json');

        $nama_creator = $this->input->post('nama_creator', true);
        if (empty($nama_creator)) {
            return $this->_json(['success'=>false, 'message'=>'Nama creator kosong']);
        }

        // Ambil payload
        $payload = [
            'full_name'        => trim((string)$this->input->post('full_name', true)),
            'nik'              => trim((string)$this->input->post('nik', true)),
            'alamat'           => trim((string)$this->input->post('alamat', true)),
            'phone'            => trim((string)$this->input->post('phone', true)),
            'email'            => trim((string)$this->input->post('email', true)),
            'pemilik_rekening' => trim((string)$this->input->post('pemilik_rekening', true)),
            'bank'             => trim((string)$this->input->post('bank', true)),
            'no_rekening'      => trim((string)$this->input->post('no_rekening', true)),
            'max_revisi'       => (int)$this->input->post('max_revisi', true),
            'pembayaran_aman'  => trim((string)$this->input->post('pembayaran_aman', true)),
            'updated_at'       => date('Y-m-d H:i:s'),
            'updated_by'       => $_SESSION['user']['id'] ?? 0,
        ];

        // Matikan tampilan error DB agar tidak “nyembur” HTML ke response
        $prev_db_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;

        $this->db->trans_begin();
        try {
            $row = $this->db->get_where('influencer', ['username'=>$nama_creator])->row_array();

            if ($row) {
                $this->db->update('influencer', $payload, ['username'=>$nama_creator]);
            } else {
                $payload['username']   = $nama_creator;
                $payload['created_at'] = date('Y-m-d H:i:s');
                $payload['created_by'] = $_SESSION['user']['id'] ?? 0;
                $payload['status']     = 'active';
                $this->db->insert('influencer', $payload);
            }

            // Cek error DB manual
            if ($this->db->trans_status() === FALSE) {
                $err = $this->db->error();
                throw new Exception($err['message'] ?? 'DB operation failed');
            }

            $this->db->trans_commit();
            return $this->_json(['success'=>true]);

        } catch (Throwable $e) {
            $this->db->trans_rollback();
            log_message('error', 'action_save_influencer error: '.$e->getMessage());
            return $this->_json(['success'=>false, 'message'=>$e->getMessage()]);
        } finally {
            $this->db->db_debug = $prev_db_debug;
        }
    }


    // // ==============
    // // 2) GENERATE PDF
    // // ==============
    // public function action_generate_mou_pdf()
    // {
    //     $id_campaign  = (int)$this->input->post('id_campaign');
    //     $nama_creator = $this->input->post('nama_creator', true);
    //     $ids_raw      = $this->input->post('mou_item_ids', true);
    //     $override_raw = (int)($this->input->post('total_cost_override_raw') ?? 0);

    //     if (!$nama_creator || !$id_campaign || !$ids_raw) {
    //         return $this->_json(['success'=>false, 'message'=>'Param tidak lengkap']);
    //     }

    //     // Parse ids
    //     $ids = array_values(array_filter(array_map('intval', explode(',', $ids_raw))));
    //     if (!$ids) {
    //         return $this->_json(['success'=>false, 'message'=>'Item kosong']);
    //     }

    //     // Ambil data endorse rows
    //     $rows = $this->db->where_in('id', $ids)->get('endorse')->result_array();
    //     if (!$rows) {
    //         return $this->_json(['success'=>false, 'message'=>'Data item tidak ditemukan']);
    //     }

    //     // Total
    //     $auto_total = array_sum(array_map(function($r){ return (int)($r['total_cost'] ?? 0); }, $rows));
    //     $total = $override_raw ?: $auto_total;

    //     // Influencer & campaign
    //     $inf      = $this->db->get_where('influencer', ['username'=>$nama_creator])->row_array();
    //     $campaign = $this->db->get_where('endorse_campaign', ['id'=>$id_campaign])->row_array();

    //     if (!$inf) {
    //         return $this->_json(['success'=>false, 'message'=>'Data influencer tidak ditemukan']);
    //     }

    //     // === PIC dari TABEL ENDORSE ===
    //     $picName = 'System';
    //     foreach ($rows as $r) {
    //         if (!empty($r['pic'])) { $picName = $r['pic']; break; }
    //         if (!empty($r['pic_name'])) { $picName = $r['pic_name']; break; }
    //     }

    //     // === BUILD SOW (Scope of Work) ===
    //     $sow_items = [];
    //     foreach ($rows as $idx => $r) {
    //         $num = $idx + 1;
    //         $desc = $r['sow_description'] ?? $r['notes'] ?? $r['content_type'] ?? 'Item ' . $num;
    //         $sow_items[] = chr(96 + $num) . ". " . $desc;
    //     }
    //     $sow_text = implode("\n\n", $sow_items);

    //     // === BUILD ALUR KERJASAMA ===
    //     $alur_items = [];
    //     foreach ($rows as $idx => $r) {
    //         $num = $idx + 1;
    //         $workflow = $r['workflow_description'] ?? $r['deadline'] ?? 'Tahap ' . $num;
    //         $alur_items[] = chr(96 + $num) . ". " . $workflow;
    //     }
    //     $alur_text = implode("\n\n", $alur_items);

    //     // === PERHITUNGAN DP (default 50%) ===
    //     $persentase_dp = $campaign['dp_percentage'] ?? 50;
    //     $nominal_dp = ($total * $persentase_dp) / 100;

    //     // Tanggal Indonesia
    //     $tglIndo = $this->_format_tanggal_id(date('Y-m-d'));

    //     // === MAPPING DATA UNTUK REPLACE (sesuai format ${variable}) ===
    //     $replacements = [
    //         'brand'                         => $campaign['brand_name'] ?? 'ACNENO SYSTEM',
    //         'pic'                           => $picName,
    //         'full_name'                     => $inf['full_name'] ?? $inf['name'] ?? $nama_creator,
    //         'alamat'                        => $inf['address'] ?? '-',
    //         'phone'                         => $inf['phone'] ?? '-',
    //         'username'                      => '@' . $nama_creator,
    //         'sow'                           => $sow_text,
    //         'total_cost'                    => number_format($total, 0, ',', '.'),
    //         'total_cost_bilangan'           => $this->_terbilang($total) . ' Rupiah',
    //         'pembayaran_awal'               => 'DP',
    //         'persentase_pembayaran_awal'    => $persentase_dp,
    //         'nominal_dp'                    => number_format($nominal_dp, 0, ',', '.'),
    //         'bilangan_pembayaran_awal'      => $this->_terbilang($nominal_dp) . ' Rupiah',
    //         'bank'                          => $inf['bank_name'] ?? '-',
    //         'no_rekening'                   => $inf['bank_account'] ?? '-',
    //         'pemilik_rekening'              => $inf['bank_account_name'] ?? $inf['full_name'] ?? '-',
    //         'alur_kerjasama'                => $alur_text,
    //         'tanggal'                       => $tglIndo,
    //     ];

    //     // === LOAD TEMPLATE DOCX & REPLACE ===
    //     try {
    //         require_once FCPATH.'vendor/autoload.php';
            
    //         $templatePath = FCPATH.'uploads/templates/Format_MOU_Template.docx';
    //         if (!file_exists($templatePath)) {
    //             return $this->_json(['success'=>false, 'message'=>'Template DOCX tidak ditemukan di: '.$templatePath]);
    //         }

    //         // Load template dengan PhpWord TemplateProcessor
    //         $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);

    //         // Replace semua variabel (format ${variable})
    //         foreach ($replacements as $search => $replace) {
    //             $templateProcessor->setValue($search, $replace);
    //         }

    //         // Simpan hasil ke temporary DOCX
    //         $tempDocx = FCPATH.'uploads/temp/temp_mou_' . time() . '_' . rand(1000,9999) . '.docx';
    //         $tempDir = dirname($tempDocx);
    //         if (!is_dir($tempDir)) {
    //             mkdir($tempDir, 0777, true);
    //         }
    //         $templateProcessor->saveAs($tempDocx);

    //         // === CONVERT DOCX TO PDF ===
    //         // Option 1: Menggunakan LibreOffice (recommended untuk hasil terbaik)
    //         if ($this->_is_libreoffice_available()) {
    //             $pdfPath = $this->_convert_docx_to_pdf_libreoffice($tempDocx);
    //         } 
    //         // Option 2: Menggunakan Dompdf via HTML (fallback)
    //         else {
    //             $pdfPath = $this->_convert_docx_to_pdf_dompdf($tempDocx);
    //         }

    //         if (!$pdfPath || !file_exists($pdfPath)) {
    //             return $this->_json(['success'=>false, 'message'=>'Gagal mengkonversi ke PDF']);
    //         }

    //         // Nama file PDF final
    //         $rawFileName = 'MOU ' . $nama_creator . ' - ' . $picName . ' - ' . $tglIndo . '.pdf';
    //         $fileName    = $this->_sanitize_filename($rawFileName);

    //         // Pindahkan ke folder final
    //         $dir = FCPATH.'uploads/mou/';
    //         if (!is_dir($dir)) {
    //             mkdir($dir, 0777, true);
    //         }
    //         $finalPath = $dir . $fileName;
            
    //         if (!@copy($pdfPath, $finalPath)) {
    //             return $this->_json(['success'=>false, 'message'=>'Gagal menyalin PDF ke folder final']);
    //         }

    //         // Hapus file temporary
    //         @unlink($tempDocx);
    //         @unlink($pdfPath);

    //         $pdfUrl = base_url('uploads/mou/'.$fileName);

    //         // ==== DB LOGGING & UPDATE ====
    //         $this->db->trans_start();

    //         $now   = date('Y-m-d H:i:s');
    //         $user  = isset($_SESSION['user']) ? $_SESSION['user'] : null;
    //         $uid   = $user['id']   ?? null;
    //         $ucode = $user['code'] ?? null;

    //         foreach ($ids as $endorseId) {
    //             // 1. Insert ke mou_logs
    //             $this->db->insert('mou_logs', [
    //                 'id_endorse'   => $endorseId,
    //                 'id_campaign'  => $id_campaign,
    //                 'nama_creator' => $nama_creator,
    //                 'pic'          => $picName,
    //                 'filename'     => $fileName,
    //                 'pdf_url'      => $pdfUrl,
    //                 'created_at'   => $now,
    //                 'generated_by' => $ucode,
    //             ]);

    //             // 2. Update endorse dengan logs
    //             $rowEndorse = $this->db->get_where('endorse', ['id' => $endorseId])->row_array();
    //             $logs = [];
    //             if (!empty($rowEndorse['logs'])) {
    //                 $decoded = json_decode($rowEndorse['logs'], true);
    //                 if (is_array($decoded)) $logs = $decoded;
    //             }
    //             $logs[] = [
    //                 'status'       => 'MOU Generated',
    //                 'created_by'   => (string)$uid,
    //                 'created_text' => $ucode,
    //                 'created_at'   => $now,
    //             ];

    //             $this->db->update('endorse', [
    //                 'is_generated_mou'   => 1,
    //                 'link_generated_mou' => $pdfUrl,
    //                 'logs'               => json_encode($logs, JSON_UNESCAPED_UNICODE),
    //                 'updated_at'         => $now,
    //                 'updated_by'         => $uid,
    //             ], ['id' => $endorseId]);
    //         }

    //         $this->db->trans_complete();
    //         if ($this->db->trans_status() === FALSE) {
    //             return $this->_json(['success'=>false, 'message'=>'Gagal menyimpan log ke database']);
    //         }

    //         // Response sukses
    //         return $this->_json([
    //             'success'   => true,
    //             'pdf_url'   => $pdfUrl,
    //             'filename'  => $fileName,
    //             'message'   => 'MOU berhasil digenerate'
    //         ]);

    //     } catch (\Throwable $e) {
    //         return $this->_json([
    //             'success' => false,
    //             'message' => 'Error: '.$e->getMessage(),
    //             'trace'   => $e->getTraceAsString()
    //         ]);
    //     }
    // }

    // /**
    //  * Check apakah LibreOffice tersedia di server
    //  */
    // private function _is_libreoffice_available()
    // {
    //     $output = shell_exec('which libreoffice 2>&1');
    //     return !empty($output);
    // }

    // /**
    //  * Convert DOCX ke PDF menggunakan LibreOffice (hasil terbaik)
    //  */
    // private function _convert_docx_to_pdf_libreoffice($docxPath)
    // {
    //     $outputDir = dirname($docxPath);
    //     $command = "libreoffice --headless --convert-to pdf --outdir " . escapeshellarg($outputDir) . " " . escapeshellarg($docxPath) . " 2>&1";
        
    //     exec($command, $output, $returnVar);
        
    //     if ($returnVar !== 0) {
    //         return false;
    //     }
        
    //     // PDF akan dibuat dengan nama yang sama tapi ekstensi .pdf
    //     $pdfPath = preg_replace('/\.docx$/i', '.pdf', $docxPath);
        
    //     return file_exists($pdfPath) ? $pdfPath : false;
    // }

    // /**
    //  * Convert DOCX ke PDF menggunakan Dompdf (fallback)
    //  */
    // private function _convert_docx_to_pdf_dompdf($docxPath)
    // {
    //     try {
    //         // Load DOCX dengan PhpWord
    //         $phpWord = \PhpOffice\PhpWord\IOFactory::load($docxPath);
            
    //         // Convert ke HTML
    //         $htmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');
    //         $tempHtml = dirname($docxPath) . '/temp_' . time() . '.html';
    //         $htmlWriter->save($tempHtml);
            
    //         $html = file_get_contents($tempHtml);
            
    //         // Generate PDF dengan Dompdf
    //         $dompdf = new \Dompdf\Dompdf([
    //             'isHtml5ParserEnabled' => true,
    //             'isRemoteEnabled'      => true,
    //             'defaultFont'          => 'DejaVu Sans'
    //         ]);
            
    //         $dompdf->loadHtml($html);
    //         $dompdf->setPaper('A4', 'portrait');
    //         $dompdf->render();
            
    //         // Simpan PDF
    //         $pdfPath = preg_replace('/\.docx$/i', '.pdf', $docxPath);
    //         file_put_contents($pdfPath, $dompdf->output());
            
    //         // Hapus temporary HTML
    //         @unlink($tempHtml);
            
    //         return file_exists($pdfPath) ? $pdfPath : false;
            
    //     } catch (\Exception $e) {
    //         return false;
    //     }
    // }

    // /**
    //  * Konversi angka ke terbilang Indonesia
    //  */
    // private function _terbilang($angka)
    // {
    //     $angka = abs($angka);
    //     $baca = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
        
    //     if ($angka < 12) {
    //         return trim($baca[$angka]);
    //     } elseif ($angka < 20) {
    //         return trim($this->_terbilang($angka - 10) . " Belas");
    //     } elseif ($angka < 100) {
    //         return trim($this->_terbilang($angka / 10) . " Puluh " . $this->_terbilang($angka % 10));
    //     } elseif ($angka < 200) {
    //         return trim("Seratus " . $this->_terbilang($angka - 100));
    //     } elseif ($angka < 1000) {
    //         return trim($this->_terbilang($angka / 100) . " Ratus " . $this->_terbilang($angka % 100));
    //     } elseif ($angka < 2000) {
    //         return trim("Seribu " . $this->_terbilang($angka - 1000));
    //     } elseif ($angka < 1000000) {
    //         return trim($this->_terbilang($angka / 1000) . " Ribu " . $this->_terbilang($angka % 1000));
    //     } elseif ($angka < 1000000000) {
    //         return trim($this->_terbilang($angka / 1000000) . " Juta " . $this->_terbilang($angka % 1000000));
    //     } elseif ($angka < 1000000000000) {
    //         return trim($this->_terbilang($angka / 1000000000) . " Miliar " . $this->_terbilang($angka % 1000000000));
    //     }
        
    //     return (string)$angka;
    // }

    // /**
    //  * Format tanggal ke Indonesia
    //  */
    // private function _format_tanggal_id($ymd)
    // {
    //     $bulan = [
    //         1=>'Januari','Februari','Maret','April','Mei','Juni',
    //         'Juli','Agustus','September','Oktober','November','Desember'
    //     ];
    //     $ts = strtotime($ymd);
    //     if (!$ts) $ts = time();
    //     $d = (int)date('j', $ts);
    //     $m = (int)date('n', $ts);
    //     $y = (int)date('Y', $ts);
    //     return $d.' '.$bulan[$m].' '.$y;
    // }

    // /**
    //  * Sanitasi nama file
    //  */
    // private function _sanitize_filename($name)
    // {
    //     // Ganti karakter tidak valid
    //     $name = preg_replace('/[\/\\\\:*?"<>|]+/', '_', $name);
    //     $name = preg_replace('/\s+/', ' ', trim($name));
        
    //     // Batasi panjang
    //     if (strlen($name) > 200) {
    //         $ext = '.pdf';
    //         $base = substr($name, 0, 200 - strlen($ext));
    //         $name = $base.$ext;
    //     }
        
    //     return $name;
    // }

    /**
     * Return JSON response
     */
    private function _json($arr) {
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($arr, JSON_UNESCAPED_UNICODE));
    }




    // ==============
    // 3) KIRIM EMAIL
    // ==============
    // public function action_send_mou_email()
    // {
    //     $nama_creator = $this->input->post('nama_creator', true);
    //     $pdf_url      = $this->input->post('pdf_url', true);

    //     if (!$nama_creator || !$pdf_url) {
    //         return $this->_json(['success'=>false, 'message'=>'Param tidak lengkap']);
    //     }

    //     $inf = $this->db->get_where('influencer', ['username'=>$nama_creator])->row_array();
    //     $email = $inf['email'] ?? '';
    //     if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    //         return $this->_json(['success'=>false, 'message'=>'Email influencer tidak valid']);
    //     }

    //     $this->email->clear(TRUE);
    //     $this->email->from('mou@acnenosystem.com', 'Acneno System - MoU System');
    //     $this->email->to($email);
    //     $this->email->subject('MoU Kerja Sama - '.$inf['full_name']);
    //     $this->email->message("Halo {$inf['full_name']},\n\nBerikut terlampir MoU kerja sama.\n\nTerima kasih.");

    //     // Attach local path
    //     $localPath = FCPATH . 'uploads/mou/' . basename($pdf_url);
    //     if (is_file($localPath)) {
    //         $this->email->attach($localPath);
    //     }

    //     if ($this->email->send()) {
    //         return $this->_json(['success'=>true]);
    //     } else {
    //         return $this->_json(['success'=>false, 'message'=>$this->email->print_debugger(['headers'])]);
    //     }
    // }

    public function mou_content()
    {
        $id_campaign  = $this->input->get('id_campaign', true);
        $nama_creator = $this->input->get('nama_creator', true);

        if (!$id_campaign || !$nama_creator) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['data' => [], 'error' => 'Missing params']));
        }

        $where = "WHERE e.id_campaign = ".$this->db->escape($id_campaign)."
                AND e.nama_creator = ".$this->db->escape($nama_creator)."
                AND e.link_mou = ''";

        if (!empty($exclude_id)) {
            $where .= " AND e.id <> ".$this->db->escape($exclude_id);
        }

        $sql = "
            SELECT 
                e.id,
                e.id_campaign,
                e.nama_creator,
                e.status_endorse,
                e.`desc`,
                e.total_cost,
                e.nominal_pengajuan,
                e.product_text,
                c.title
            FROM endorse e
            JOIN endorse_campaign c ON c.id = e.id_campaign
            $where
            ORDER BY e.updated_at DESC, e.id DESC
            LIMIT 500
        ";

        $rows = $this->mymodel->selectWithQuery($sql);

        $data = array_map(function($r){
            $r['total_cost']        = (int) ($r['total_cost'] ?? 0);
            return $r;
        }, $rows ?: []);

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['data' => $data]));
    }

    // ==========================
    // VIEW RENDERER (ENTRY PAGE)
    // ==========================
    public function generate_mou()
    {
        $id = $this->input->get('id', TRUE);

        $sql = "
            SELECT endorse.*, influencer.* 
            FROM endorse 
            INNER JOIN influencer ON endorse.nama_creator = influencer.username
            WHERE endorse.id = '$id'
        ";
        $res = $this->mymodel->selectWithQuery($sql);

        $data['detail']['id'] = $id;
        $data['data'] = $res ? $res[0] : []; // ambil baris pertama kalau ada

        $this->load->view("endorse/generate_mou_form", $data);
    }

    // =================
    // HELPER MINI UTILS
    // =================

    private function _html_to_pdf_basic($html, $filePath)
    {
        // Dummy converter (fallback). Produksi: Dompdf/TCPDF/wkhtmltopdf.
        @file_put_contents($filePath, $html);
    }

    private function _mock_data() {
        return [[
            'id'=>0,
            'id_campaign'=>$this->input->get('id_campaign'),
            'nama_creator'=>$this->input->get('nama_creator'),
            'status_endorse'=>'',
            'status_payment'=>'',
        ]];
    }
}
