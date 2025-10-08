<?php

defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/BaseController.php';

class Report extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('mymodel');
        $this->load->library('template');

        // Set public methods (no permission required)
        $this->set_public_methods([]);

        // Report module is read-only - only view permission needed
        $this->set_method_permissions([]);
    }

    public function index()
    {

        $data['title'] = 'Report - ' . $this->template->title();

        $user = $_SESSION['user'];


        $query = $this->mymodel->selectWithQuery("SELECT * FROM brand WHERE status = 'ENABLE' ORDER BY name ASC");
        $data['brands'] = $query;

        $data['channel_2'] = $this->mymodel->selectWithQuery("SELECT *
        FROM marketplace
        ORDER BY name ASC");

        $data['channel'] = $this->mymodel->selectWithQuery("SELECT *
        FROM marketplace
        WHERE name IN ('SHOPEE','LAZADA','TIKTOK')
        ORDER BY name ASC");

        $url = base_url() . 'dashboard/' . $this->template->get_param();
        $data['url'] = $this->template->get_param_without_keyword_category($url);
        $data['param'] = $this->template->get_param();

        $is_operational = ($_GET['p'] ?? '') === "operasional" ? 1 : 0;

        $view_file = $is_operational ? "report/operasional" : "report/overview";
        $data['content'] = $this->load->view($view_file, $data, true);
        $this->load->view('TemplateDashboard', $data);
    }
}
