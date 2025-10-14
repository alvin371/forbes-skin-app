<?php

// Load custom env helper BEFORE Composer autoloader to prevent illuminate/support conflicts
require_once __DIR__ . '/../../application/helpers/env_helper.php';

require 'vendor/autoload.php';

use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Campaign;
use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use Lazada\LazopClient;
use Lazada\LazopRequest;

defined('BASEPATH') or exit('No direct script access allowed');
class Api_v3 extends CI_Controller
{
    private $current_tax;

    function __construct()
    {
        parent::__construct();

        // Load required libraries and models
        $this->load->model('mymodel');
        $this->load->database();
        $this->load->helper('env');

        // TikTok Shop API credentials
        $this->app_key_tiktok = env('TIKTOK_APP_KEY', '');
        $this->app_secret_tiktok = env('TIKTOK_APP_SECRET', '');

        // Lazada API credentials
        $this->app_key_lazada = env('LAZADA_APP_KEY', '');
        $this->app_secret_lazada = env('LAZADA_APP_SECRET', '');

        // Shopee API credentials
        $this->partner_id_shopee = env('SHOPEE_PARTNER_ID', '');
        $this->partner_key_shopee = env('SHOPEE_PARTNER_KEY', '');

        // Meta/Facebook API credentials
        $this->app_id_meta = env('META_APP_ID', '');
        $this->app_secret_meta = env('META_APP_SECRET', '');

        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");

        // Ambil tax saat class diinisialisasi
        $tax = $this->mymodel->selectWithQuery("SELECT tax FROM config WHERE id = 'TAX'");
        $this->current_tax = $tax[0]['tax'] ?? 0; // Gunakan 0 jika data tidak ditemukan
    }

    public function index()
    {
        $dt = $_GET;
        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = true;
        $html['data'] = $dt;
        $html['msg'] = "Bhskin REST API access has been successful!";
        echo json_encode($html, true);
    }

    function marketplace_ads()
    {
        $dt = $_GET;
        $marketplace = isset($dt['marketplace']) ? strtoupper($dt['marketplace']) : null;
        $shop_id = isset($dt['shop_id']) ? $dt['shop_id'] : null;
        $platform = isset($dt['platform']) ? $dt['platform'] : null;
        $qry = "";


        if ($shop_id) {
            $shop_id_escaped = $this->db->escape_str($shop_id);
            $qry .= " AND shop_id = '$shop_id_escaped' ";
        }
        if ($marketplace) {
            $marketplace_escaped = $this->db->escape_str($marketplace);
            $qry .= " AND opt = '$marketplace_escaped' ";
        }

        $data = $this->mymodel->selectWithQuery("SELECT * FROM marketplace_config WHERE status = 'Aktif' $qry");

        $tax = $this->mymodel->selectWithQuery("SELECT tax FROM config WHERE id = 'TAX'");
        $current_tax = $tax[0]['tax'];

        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : "";
        $end_date = isset($_GET['until_date']) ? $_GET['until_date'] : "";

        foreach ($data as $k => $v) {
            if ($v['opt'] == "LAZADA") {
                $marketplace = 'LAZADA';
                $config = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $v['shop_id']));
                $config = json_decode($config['val'], true);

                $access_token = $config['access_token'];
                $shop_cipher = $config['shop']['cipher'];
                $shop_id = $v['shop_id'];
                $app_key = $this->app_key_lazada;
                $app_secret = $this->app_secret_lazada;
                $url = 'https://api.lazada.co.id/rest';

                $c = new LazopClient($url, $app_key, $app_secret);
                $request = new LazopRequest('/sponsor/solutions/report/getDiscoveryReportCampaign', 'GET');

                $start_date = date('Y-m-d');
                $end_date = date('Y-m-d');
                $request->addApiParam('startDate', $start_date);
                $request->addApiParam('endDate', $end_date);
                $request->addApiParam('pageNo', '1');
                $request->addApiParam('pageSize', '100');

                $response = $c->execute($request, $access_token);
                $responseData = json_decode($response, true);


                if (isset($responseData['result']['result']) && is_array($responseData['result']['result']) && count($responseData['result']['result']) > 0) {
                    foreach ($responseData['result']['result'] as $data) {
                        $dt = array('ctr' => $data['ctr'], 'campaignType' => isset($data['campaignType']) ? $data['campaignType'] : 0, 'campaignId' => $data['campaignId'], 'storeRevenue' => isset($data['storeRevenue']) ? $data['storeRevenue'] : 0, 'storeCvr' => isset($data['storeCvr']) ? $data['storeCvr'] : 0, 'storeA2c' => isset($data['storeA2c']) ? $data['storeA2c'] : 0, 'storeOrders' => isset($data['storeOrders']) ? $data['storeOrders'] : 0, 'productUnitSold' => isset($data['productUnitSold']) ? $data['productUnitSold'] : 0, 'impressions' => $data['impressions'], 'productCvr' => isset($data['productCvr']) ? $data['productCvr'] : 0, 'productOrders' => $data['productOrders'], 'storeRoi' => $data['storeRoi'], 'cpc' => isset($data['cpc']) ? $data['cpc'] : 0, 'spend' => $data['spend'], 'clicks' => $data['clicks'], 'productRevenue' => $data['productRevenue'], 'storeUnitSold' => isset($data['storeUnitSold']) ? $data['storeUnitSold'] : 0, 'campaignName' => $data['campaignName'], 'productType' => isset($data['productType']) ? $data['productType'] : 'ALL', 'dayBudget' => isset($data['dayBudget']) ? $data['dayBudget'] : 0, 'productA2c' => isset($data['productA2c']) ? $data['productA2c'] : 0, 'created_at' => date('Y-m-d H:i:s'));
                        $sql_check = "SELECT COUNT(*) as count FROM lazada_ads_data WHERE created_at = ? AND campaignId = ?";
                        $dataCount = $this->db->query($sql_check, [$dt['created_at'], $dt['campaignId']])->row()->count;
                        if ($dataCount == 0) {
                            $sql_insert = "INSERT INTO lazada_ads_data (date, ctr, campaignType, campaignId, storeRevenue, storeCvr, storeA2c, storeOrders, productUnitSold, impressions, productCvr, productOrders, storeRoi, cpc, spend, clicks, productRevenue, storeUnitSold, campaignName, productType, dayBudget, productA2c, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                            $this->db->query($sql_insert, [$dt['ctr'], $dt['campaignType'], $dt['campaignId'], $dt['storeRevenue'], $dt['storeCvr'], $dt['storeA2c'], $dt['storeOrders'], $dt['productUnitSold'], $dt['impressions'], $dt['productCvr'], $dt['productOrders'], $dt['storeRoi'], $dt['cpc'], $dt['spend'] + $dt['spend'] * $current_tax, $dt['clicks'], $dt['productRevenue'], $dt['storeUnitSold'], $dt['campaignName'], $dt['productType'], $dt['dayBudget'], $dt['productA2c'], $dt['created_at']]);
                        } else {
                            $sql_update = "UPDATE lazada_ads_data SET date = ? ,  ctr = ?, campaignType = ?, storeRevenue = ?, storeCvr = ?, storeA2c = ?, storeOrders = ?, productUnitSold = ?, impressions = ?, productCvr = ?, productOrders = ?, storeRoi = ?, cpc = ?, spend = ?, clicks = ?, productRevenue = ?, storeUnitSold = ?, campaignName = ?, productType = ?, dayBudget = ?, productA2c = ?, created_at = ? WHERE created_at = ? AND campaignId = ?";
                            $this->db->query($sql_update, [$dt['ctr'], $dt['campaignType'], $dt['storeRevenue'], $dt['storeCvr'], $dt['storeA2c'], $dt['storeOrders'], $dt['productUnitSold'], $dt['impressions'], $dt['productCvr'], $dt['productOrders'], $dt['storeRoi'], $dt['cpc'], $dt['spend'] + $dt['spend'] * $current_tax, $dt['clicks'], $dt['productRevenue'], $dt['storeUnitSold'], $dt['campaignName'], $dt['productType'], $dt['dayBudget'], $dt['productA2c'], $dt['created_at'], $dt['created_at'], $dt['campaignId']]);
                        }
                    }
                } else {
                    $dt = array(
                        'date' => date('Y-m-d'),
                        'ctr' => 0,
                        'shop_id' => '400607847038',
                        'campaignType' => 0,
                        'campaignId' => 0,
                        'storeRevenue' => 0,
                        'storeCvr' => 0,
                        'storeA2c' => 0,
                        'storeOrders' => 0,
                        'productUnitSold' => 0,
                        'impressions' => 0,
                        'productCvr' => 0,
                        'productOrders' => 0,
                        'storeRoi' => 0,
                        'cpc' => 0,
                        'spend' => 0,
                        'clicks' => 0,
                        'productRevenue' => 0,
                        'storeUnitSold' => 0,
                        'campaignName' => 'Tidak Ada Kampanye Aktif',
                        'productType' => 'ALL',
                        'dayBudget' => 0,
                        'productA2c' => 0,
                        'created_at' => date('Y-m-d H:i:s')
                    );

                    $sql_check = "SELECT * FROM lazada_ads_data WHERE shop_id = ? AND date = ?";
                    $dataCount = $this->db->query($sql_check, ['400607847038', date('Y-m-d')])->row()->count;

                    if ($dataCount == 0) {
                        $sql_insert = "INSERT INTO lazada_ads_data (date, shop_id, ctr, campaignType, campaignId, storeRevenue, storeCvr, storeA2c, storeOrders, productUnitSold, impressions, productCvr, productOrders, storeRoi, cpc, spend, clicks, productRevenue, storeUnitSold, campaignName, productType, dayBudget, productA2c, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $this->db->query($sql_insert, [
                            $dt['date'],
                            $dt['shop_id'],
                            $dt['ctr'],
                            $dt['campaignType'],
                            $dt['campaignId'],
                            $dt['storeRevenue'],
                            $dt['storeCvr'],
                            $dt['storeA2c'],
                            $dt['storeOrders'],
                            $dt['productUnitSold'],
                            $dt['impressions'],
                            $dt['productCvr'],
                            $dt['productOrders'],
                            $dt['storeRoi'],
                            $dt['cpc'],
                            $dt['spend'] + $dt['spend'] * $tax[0]['tax'],
                            $dt['clicks'],
                            $dt['productRevenue'],
                            $dt['storeUnitSold'],
                            $dt['campaignName'],
                            $dt['productType'],
                            $dt['dayBudget'],
                            $dt['productA2c'],
                            $dt['created_at']
                        ]);
                    } else {
                        $sql_update = "UPDATE lazada_ads_data SET date = ? ,  ctr = ?, campaignType = ?, storeRevenue = ?, storeCvr = ?, storeA2c = ?, storeOrders = ?, productUnitSold = ?, impressions = ?, productCvr = ?, productOrders = ?, storeRoi = ?, cpc = ?, spend = ?, clicks = ?, productRevenue = ?, storeUnitSold = ?, campaignName = ?, productType = ?, dayBudget = ?, productA2c = ?, created_at = ? WHERE shop_id = ? AND date = ?";
                        $this->db->query($sql_update, [$dt['date'], $dt['ctr'], $dt['campaignType'], $dt['storeRevenue'], $dt['storeCvr'], $dt['storeA2c'], $dt['storeOrders'], $dt['productUnitSold'], $dt['impressions'], $dt['productCvr'], $dt['productOrders'], $dt['storeRoi'], $dt['cpc'], $dt['spend'], $dt['clicks'], $dt['productRevenue'], $dt['storeUnitSold'], $dt['campaignName'], $dt['productType'], $dt['dayBudget'], $dt['productA2c'], $dt['created_at'], $dt['created_at'], $dt['campaignId']]);
                    }
                }
                $success = true;
            } else if ($v['opt'] == "SHOPEE") {
                $marketplace = $v['opt'];
                $config = json_decode($v['val'], true);
                $access_token = $config['access_token'];
                $host = 'https://partner.shopeemobile.com';
                $partner_id = $this->partner_id_shopee;
                $partner_key = $this->partner_key_shopee;
                $shop_id = $v['shop_id'];
                $shop_name = $v['shop_name'];

                // Step 1: Get campaign list
                $path = "/api/v2/ads/get_product_level_campaign_id_list";
                $timest = time();
                $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
                $sign = hash_hmac('sha256', $baseString, $partner_key);

                $start_date = date('d-m-Y');
                $end_date = date('d-m-Y');

                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $host . $path . '?partner_id=' . $partner_id . '&timestamp=' . $timest . '&shop_id=' . $shop_id . '&access_token=' . $access_token . '&end_date=' . $end_date . '&sign=' . $sign . '&start_date=' . $start_date,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                ));

                $response = curl_exec($curl);
                curl_close($curl);

                $responseData = json_decode($response, true);

                if (isset($responseData['response']['campaign_list'])) {
                    $campaignList = $responseData['response']['campaign_list'];
                    $campaignIds = array_column($campaignList, 'campaign_id');
                    
                    // Split campaign IDs into chunks of 100
                    $campaignIdChunks = array_chunk($campaignIds, 100);
                    
                    foreach ($campaignIdChunks as $chunk) {
                        $campaign_id_list = implode(',', $chunk);
                        $detailPath = "/api/v2/ads/get_product_campaign_daily_performance";
                        $timestamp = time();
                        $baseStringDetail = sprintf("%s%s%s%s%s", $partner_id, $detailPath, $timestamp, $access_token, $shop_id);
                        $signDetail = hash_hmac('sha256', $baseStringDetail, $partner_key);

                        $start_date = date('d-m-Y');
                        $end_date = date('d-m-Y');

                        $url = $host . $detailPath . '?partner_id=' . $partner_id .
                            '&timestamp=' . $timestamp .
                            '&shop_id=' . $shop_id .
                            '&access_token=' . $access_token .
                            '&sign=' . $signDetail .
                            '&campaign_id_list=' . $campaign_id_list .
                            '&start_date=' . $start_date .
                            '&end_date=' . $end_date;

                        $curl = curl_init();
                        curl_setopt_array($curl, array(
                            CURLOPT_URL => $url,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => 'GET',
                        ));

                        $detailResponse = curl_exec($curl);
                        curl_close($curl);

                        $detailData = json_decode($detailResponse, true);

                        // Optional: Add delay between requests to avoid rate limiting
                        sleep(1);
                    }
                }

                $success = true;
            } else if ($v['opt'] == "META") {
                $app_id = $this->app_id_meta;
                $app_secret = $this->app_secret_meta;
                $marketplace = $v['opt'];
                $config = json_decode($v['val'], true);
                $access_token = $config['refresh_token'];
                $fields = 'impressions,clicks,spend,ctr,purchase_roas,actions,action_values';

                $account_ids =  $this->mymodel->selectWithQuery("SELECT account_id FROM ads_meta_account WHERE status = 1");

                $since = date('Y-m-d');
                $until = date('Y-m-d');

                foreach ($account_ids as $account_id) {
                    $id = $account_id['account_id'];
                    $url = "https://graph.facebook.com/v21.0/act_$id/campaigns?time_range={'since':'$since','until':'$until'}&access_token=$access_token";
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $response = curl_exec($ch);
                    print_r($response);die;
                    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    $responseData = json_decode($response, true);

                    if (isset($responseData['data'])) {
                        foreach ($responseData['data'] as $campaign) {
                            $campaign_id = $campaign['id'];
                            $insights_url = "https://graph.facebook.com/v21.0/$campaign_id/insights?fields=ctr,spend,impressions,clicks,campaign_id,campaign_name,account_id,catalog_segment_value&time_range={'since':'$since','until':'$until'}&access_token=$access_token";

                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $insights_url);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            $insights_response = curl_exec($ch);
                            $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                            curl_close($ch);
                            $insightsData = json_decode($insights_response, true);

                            if (isset($insightsData['data'])) {
                                foreach ($insightsData['data'] as $dataAds) {
                                    $campaign_name = $dataAds['campaign_name'];
                                    $ctr = $dataAds['ctr'] ?? 0;
                                    $impressions = $dataAds['impressions'] ?? 0;
                                    $spend = $dataAds['spend'] ?? 0;
                                    $spend_after_tax = $dataAds['spend'] + ($dataAds['spend'] * $current_tax) ?? 0;
                                    $clicks = $dataAds['clicks'] ?? 0;
                                    $purchase = 0;
                                    $add_to_cart = 0;

                                    if (!empty($dataAds['catalog_segment_value'])) {
                                        foreach ($dataAds['catalog_segment_value'] as $segment) {
                                            if ($segment['action_type'] == 'omni_purchase') {
                                                $purchase = $segment['value'];
                                            }
                                            if ($segment['action_type'] == 'omni_add_to_cart') {
                                                $add_to_cart = $segment['value'];
                                            }
                                        }
                                    }

                                    $date_start = $dataAds['date_start'];
                                    $date_stop = $dataAds['date_stop'];

                                    $dt = array(
                                        'date' => $date_start,
                                        'account_id' => str_replace('act_', '', $id),
                                        'campaign_id' => $campaign_id,
                                        'campaign_name' => $campaign_name,
                                        'ctr' => $ctr,
                                        'impressions' => $impressions,
                                        'tax' => $current_tax,
                                        'spend' => $spend,
                                        'spend_after_tax' => $spend_after_tax,
                                        'clicks' => $clicks,
                                        'purchases' => $purchase,
                                        'add_to_cart' => $add_to_cart,
                                        'date_start' => $date_start,
                                        'date_stop' => $date_stop
                                    );

                                    $this->db->where('date', $date_start);
                                    $this->db->where('campaign_id', $campaign_id);
                                    $query = $this->db->get('meta_ads_data');

                                    $this->db->query("SET session wait_timeout=28800", FALSE);
                                    $this->db->query("SET session interactive_timeout=28800", FALSE);

                                    if ($query->num_rows() == 0) {
                                        $this->db->insert('meta_ads_data', $dt);
                                    } else {
                                        $this->db->where('date_start', $date_start);
                                        $this->db->where('date_stop', $date_stop);
                                        $this->db->where('campaign_id', $campaign_id);
                                        $this->db->update('meta_ads_data', $dt);
                                    }
                                }
                            }
                        }
                    }
                }
            } else if ($v['opt'] == "TIKTOKBC") {
                $tiktok_bc_app_id = env('TIKTOK_BC_APP_ID', '');
                $tiktok_bc_secret = env('TIKTOK_BC_APP_SECRET', '');
                $tiktok_bc_token = env('TIKTOK_BC_ACCESS_TOKEN', '');

                $advertiser_url = "https://business-api.tiktok.com/open_api/v1.3/oauth2/advertiser/get/?app_id={$tiktok_bc_app_id}&secret={$tiktok_bc_secret}";

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $advertiser_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Access-Token: {$tiktok_bc_token}",
                ]);

                $advertiser_response = curl_exec($ch);
                if (curl_errno($ch)) {
                    echo "Error: " . curl_error($ch);
                    curl_close($ch);
                    exit;
                }
                curl_close($ch);

                $advertiser_data = json_decode($advertiser_response, true);

                if (!isset($advertiser_data['data']['list']) || empty($advertiser_data['data']['list'])) {
                    echo "No advertiser data available.\n";
                    exit;
                }

                foreach ($advertiser_data['data']['list'] as $advertiser) {
                    $advertiser_id = $advertiser['advertiser_id'];
                    $advertiser_name = $advertiser['advertiser_name'];

                    $report_url = "https://business-api.tiktok.com/open_api/v1.3/report/integrated/get/";

                    $report_data = [
                        "advertiser_id" => $advertiser_id,
                        "report_type" => "BASIC",
                        "data_level" => "AUCTION_ADVERTISER",
                        "dimensions" => json_encode(["advertiser_id"]),
                        "metrics" => json_encode([
                            "impressions",
                            "clicks",
                            "spend",
                            "currency",
                            "ctr",
                            "cpc",
                            "cpm",
                            "onsite_shopping_roas",
                            "onsite_shopping",
                            "cost_per_onsite_shopping",
                            "onsite_shopping_rate",
                            "value_per_onsite_shopping",
                            "total_onsite_shopping_value",
                            "onsite_initiate_checkout_count",
                            "cost_per_onsite_initiate_checkout_count",
                            "onsite_initiate_checkout_count_rate",
                            "value_per_onsite_initiate_checkout_count",
                            "total_onsite_initiate_checkout_count_value",
                            "onsite_on_web_detail",
                            "cost_per_onsite_on_web_detail",
                            "onsite_on_web_detail_rate",
                            "value_per_onsite_on_web_detail",
                            "total_onsite_on_web_detail_value",
                            "onsite_on_web_cart",
                            "cost_per_onsite_on_web_cart",
                            "onsite_on_web_cart_rate",
                            "value_per_onsite_on_web_cart",
                            "total_onsite_on_web_cart_value",
                            "total_onsite_initiate_checkout_count_value",
                            "total_onsite_shopping_value",
                            "value_per_onsite_initiate_checkout_count",
                            "value_per_onsite_shopping",
                            "value_per_onsite_on_web_cart",
                            "value_per_onsite_on_web_detail",
                            "frequency",
                            "reach"
                        ]),
                        "start_date" => date('Y-m-d'),
                        "end_date" => date('Y-m-d')
                    ];

                    $query_string = http_build_query($report_data);

                    $ch = curl_init($report_url . '?' . $query_string);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        "Content-Type: application/json",
                        "Access-Token: {$tiktok_bc_token}"
                    ]);

                    $report_response = curl_exec($ch);
                    

                    if (curl_errno($ch)) {
                        echo "Error: " . curl_error($ch);
                        curl_close($ch);
                        continue;
                    }
                    curl_close($ch);

                    $report_data = json_decode($report_response, true);

                    foreach ($report_data['data']['list'] as $metrics_data) {
                        $metrics = $metrics_data['metrics'] ?? [];
                        $dimensions = $metrics_data['dimensions'] ?? [];

                        $getMetric = function ($key, $default = 0) use ($metrics) {
                            return $metrics[$key] ?? $default;
                        };

                        $url = "https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/usd.json";

                        $ch = curl_init($url);

                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt(
                            $ch,
                            CURLOPT_HTTPHEADER,
                            [
                                'Accept: application/json',
                            ]
                        );

                        $response = curl_exec($ch);

                        if (curl_errno($ch)) {
                            echo "cURL Error: " . curl_error($ch);
                        } else {
                            $responseData = json_decode($response, true);

                            if (isset($responseData['usd']['idr'])) {
                                $exchange_rate = $responseData['usd']['idr'];

                                $dt = [
                                    'advertiser_id' => $dimensions['advertiser_id'] ?? null,
                                    'advertiser_name' => $advertiser_name,
                                    'impressions' => $getMetric('impressions'),
                                    'clicks' => $getMetric('clicks'),
                                    'spend' => $getMetric('spend'),
                                    'spend_idr' => $getMetric('currency') == 'USD' ? ($exchange_rate * $getMetric('spend')) : $getMetric('spend'),
                                    'spend_idr_after_tax' => $getMetric('currency') == 'USD' ? ($exchange_rate * $getMetric('spend')) + ($exchange_rate * $getMetric('spend') * $current_tax)  : $getMetric('spend') + ($getMetric('spend') * $current_tax),
                                    'tax' => $current_tax,
                                    'currency' => $getMetric('currency'),
                                    'ctr' => $getMetric('ctr'),
                                    'cpc' => $getMetric('cpc'),
                                    'cpm' => $getMetric('cpm'),
                                    'onsite_shopping_roas' => $getMetric('onsite_shopping_roas'),
                                    'onsite_shopping' => $getMetric('onsite_shopping'),
                                    'cost_per_onsite_shopping' => $getMetric('cost_per_onsite_shopping'),
                                    'onsite_shopping_rate' => $getMetric('onsite_shopping_rate'),
                                    'value_per_onsite_shopping' => $getMetric('value_per_onsite_shopping'),
                                    'total_onsite_shopping_value' => $getMetric('total_onsite_shopping_value'),
                                    'total_onsite_shopping_value_idr' => $getMetric('currency') == 'USD' ? $exchange_rate * $getMetric('total_onsite_shopping_value') : $getMetric('total_onsite_shopping_value'),
                                    'onsite_initiate_checkout_count' => $getMetric('onsite_initiate_checkout_count'),
                                    'cost_per_onsite_initiate_checkout_count' => $getMetric('cost_per_onsite_initiate_checkout_count'),
                                    'onsite_initiate_checkout_count_rate' => $getMetric('onsite_initiate_checkout_count_rate'),
                                    'value_per_onsite_initiate_checkout_count' => $getMetric('value_per_onsite_initiate_checkout_count'),
                                    'total_onsite_initiate_checkout_count_value' => $getMetric('total_onsite_initiate_checkout_count_value'),
                                    'onsite_on_web_detail' => $getMetric('onsite_on_web_detail'),
                                    'cost_per_onsite_on_web_detail' => $getMetric('cost_per_onsite_on_web_detail'),
                                    'onsite_on_web_detail_rate' => $getMetric('onsite_on_web_detail_rate'),
                                    'value_per_onsite_on_web_detail' => $getMetric('value_per_onsite_on_web_detail'),
                                    'total_onsite_on_web_detail_value' => $getMetric('total_onsite_on_web_detail_value'),
                                    'onsite_on_web_cart' => $getMetric('onsite_on_web_cart'),
                                    'cost_per_onsite_on_web_cart' => $getMetric('cost_per_onsite_on_web_cart'),
                                    'onsite_on_web_cart_rate' => $getMetric('onsite_on_web_cart_rate'),
                                    'value_per_onsite_on_web_cart' => $getMetric('value_per_onsite_on_web_cart'),
                                    'total_onsite_on_web_cart_value' => $getMetric('total_onsite_on_web_cart_value'),
                                    'total_onsite_on_web_cart_value_idr' => $getMetric('currency') == 'USD' ? $exchange_rate * $getMetric('total_onsite_on_web_cart_value') : $getMetric('total_onsite_on_web_cart_value'),
                                    'frequency' => $getMetric('frequency'),
                                    'reach' => $getMetric('reach'),
                                    'date' => date('Y-m-d')
                                ];


                                $query = $this->db->where('advertiser_id', $dt['advertiser_id'])
                                    ->where('date', date('Y-m-d'))
                                    ->get('tiktok_ads_data');

                                if ($query->num_rows() == 0) {
                                    $this->db->insert('tiktok_ads_data', $dt);
                                } else {
                                    $this->db->where('advertiser_id', $dt['advertiser_id'])
                                        ->where('date', date('Y-m-d'))
                                        ->update('tiktok_ads_data', $dt);
                                }
                            }
                        }
                    }
                }
                $success = true;
            }
        }

        // Return JSON response
        header('Content-Type: application/json; charset=utf-8');
        if (isset($success) && $success) {
            echo json_encode([
                'status' => true,
                'message' => 'Data berhasil diproses',
                'marketplace' => $marketplace ?? null
            ]);
        } else {
            echo json_encode([
                'status' => false,
                'message' => 'Tidak ada data yang diproses atau marketplace tidak ditemukan'
            ]);
        }
    }

    function get_tiktok_gmv()
    {
        $tiktok_bc_app_id = env('TIKTOK_BC_APP_ID', '');
        $tiktok_bc_secret = env('TIKTOK_BC_APP_SECRET', '');
        $tiktok_bc_token = env('TIKTOK_BC_ACCESS_TOKEN', '');

        $advertiser_url = "https://business-api.tiktok.com/open_api/v1.3/oauth2/advertiser/get/?app_id={$tiktok_bc_app_id}&secret={$tiktok_bc_secret}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $advertiser_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Access-Token: {$tiktok_bc_token}",
        ]);

        $advertiser_response = curl_exec($ch);
        if (curl_errno($ch)) {
            echo "Error: " . curl_error($ch);
            curl_close($ch);
            exit;
        }
        curl_close($ch);

        $advertiser_data = json_decode($advertiser_response, true);

        if (!isset($advertiser_data['data']['list']) || empty($advertiser_data['data']['list'])) {
            echo "No advertiser data available.\n";
            exit;
        }

        foreach ($advertiser_data['data']['list'] as $advertiser) {
            $advertiser_id = $advertiser['advertiser_id'];
            $advertiser_name = $advertiser['advertiser_name'];

            $accessToken = $tiktok_bc_token;
            
            // Get advertiser info to retrieve currency
            $info_url = "https://business-api.tiktok.com/open_api/v1.3/advertiser/info/?advertiser_ids=" . urlencode('["' . $advertiser_id . '"]');
        
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $info_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Access-Token: ' . $accessToken,
                'Content-Type: application/json'
            ]);
        
            $info_response = curl_exec($ch);
            curl_close($ch);
    
            $info_data = json_decode($info_response, true);
            $currency = $info_data['data']['list'][0]['currency'] ?? 'Unknown';

            // Get data GMV
            $apiUrl = 'https://business-api.tiktok.com/open_api/v1.3/report/integrated/get/';
            

            $params = [
                'advertiser_id' => $advertiser_id,
                'service_type' => 'AUCTION',
                'report_type' => 'TT_SHOP',
                'data_level' => 'AUCTION_ADVERTISER',
                'dimensions' => '["advertiser_id"]',
                'metrics' => '["spend"]',
                'start_date' => date('Y-m-d'),
                'end_date' => date('Y-m-d'),
                'page' => 1,
                'page_size' => 100
            ];

            $queryString = http_build_query($params);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl . '?' . $queryString);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Access-Token: ' . $accessToken
            ]);

            $response = curl_exec($ch);

            if ($response === false) {
                $error = curl_error($ch);
                echo "cURL Error: " . $error;
            } else {
                $responseData = json_decode($response, true);
                if (!isset($responseData['data']['list']) || empty($responseData['data']['list'])) {
                    echo "No data found for the given request.\n";
                } else {
                    foreach ($responseData['data']['list'] as $data) {
                        $advertiser_id = $data['dimensions']['advertiser_id'];

                        if (isset($data['metrics']['spend'])) {
                            $spend = $data['metrics']['spend'];

                            $url = "https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/usd.json";

                            $ch = curl_init($url);

                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt(
                                $ch,
                                CURLOPT_HTTPHEADER,
                                [
                                    'Accept: application/json',
                                ]
                            );

                            $response = curl_exec($ch);

                            if (curl_errno($ch)) {
                                echo "cURL Error: " . curl_error($ch);
                            } else {
                                $responseData = json_decode($response, true);

                                if (isset($responseData['usd']['idr'])) {
                                    $exchangeRate = $responseData['usd']['idr'];
                                    if ($currency != 'IDR') {
                                        $idr = $spend * $exchangeRate;
                                    } else {
                                        $idr = $spend;
                                    }

                                    $dt = array(
                                        'date' => date('Y-m-d'),
                                        'advertiser_id' => $advertiser_id,
                                        'advertiser_name' => $advertiser_name,
                                        'spend' => $spend,
                                        'spend_idr' => $idr,
                                        'spend_idr_after_tax' => $idr + ($idr * $current_tax),
                                        'tax' => $this->current_tax
                                    );

                                    $this->db->where('advertiser_id', $advertiser_id);
                                    $this->db->where('date', date('Y-m-d'));
                                    $query = $this->db->get('advertiser_spend');

                                    if ($query->num_rows() == 0) {
                                        $this->db->insert('advertiser_spend', $dt);
                                    } else {
                                        $this->db->where('date', date('Y-m-d'));
                                        $this->db->where('advertiser_id', $advertiser_id);
                                        $this->db->update('advertiser_spend', $dt);
                                    }
                                } else {
                                    echo "Error: Unable to retrieve exchange rate.";
                                }
                                curl_close($ch);
                            }
                        } else {
                            echo "No spend data available for advertiser: " . $advertiser_id . "\n";
                        }
                    }
                }
            }

            curl_close($ch);
        }
    }

    function get_tiktok_campaign()
    {
        try {
            $tiktok_bc_token = env('TIKTOK_BC_ACCESS_TOKEN', '');

            $today = date('Y-m-d');
            $advertiser_id_result = $this->mymodel->selectWithQuery("SELECT DISTINCT advertiser_id FROM tiktok_ads_data WHERE date = '$today'");
            $advertiser_ids = array_column($advertiser_id_result, 'advertiser_id');

            // Validate advertiser IDs
            if (empty($advertiser_ids)) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status' => false,
                    'message' => 'No advertiser IDs found for today. Please run the ads sync first.',
                    'date' => $today
                ]);
                return;
            }

            $access_token = $tiktok_bc_token;
            $report_url = "https://business-api.tiktok.com/open_api/v1.3/report/integrated/get/";

            foreach ($advertiser_ids as $advertiser_id) {
            $report_data = [
                "advertiser_id" => $advertiser_id,
                "report_type" => "BASIC",
                "data_level" => "AUCTION_CAMPAIGN",
                "dimensions" => json_encode(["campaign_id"]),
                "metrics" => json_encode([
                    "campaign_name",
                    "impressions",
                    "clicks",
                    "spend",
                    "ctr",
                    "cpc",
                    "cpm",
                    "onsite_shopping_roas",
                    "onsite_shopping",
                    "cost_per_onsite_shopping",
                    "onsite_shopping_rate",
                    "value_per_onsite_shopping",
                    "total_onsite_shopping_value",
                    "onsite_on_web_cart",
                    "total_onsite_on_web_cart_value",
                    "frequency",
                    "reach",
                    "currency"
                ]),
                "start_date" => date('Y-m-d'),
                "end_date" => date('Y-m-d')
            ];

            $query_string = http_build_query($report_data);
            $ch = curl_init($report_url . '?' . $query_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Access-Token: $access_token"]);
            $report_response = curl_exec($ch);
            if ($report_response === false) {
                echo "Error fetching campaign report: " . curl_error($ch);
                continue;
            }
            curl_close($ch);

            $report_data_decoded = json_decode($report_response, true);
            if (!empty($report_data_decoded['data']['list'])) {
                foreach ($report_data_decoded['data']['list'] as $metrics_data) {
                    $metrics = $metrics_data['metrics'] ?? [];
                    $dimensions = $metrics_data['dimensions'] ?? [];

                    if ((!empty($metrics['spend']) && $metrics['spend'] != 0) || (!empty($metrics['onsite_shopping']) && $metrics['onsite_shopping'] != 0)) {
                        $getMetric = function ($key, $default = 0) use ($metrics) {
                            return $metrics[$key] ?? $default;
                        };

                        $currency = $getMetric('currency', 'USD');
                        $spend = $getMetric('spend');
                        $onsite_shopping = $getMetric('onsite_shopping');
                        $spend_idr = $spend;
                        $onsite_shopping_idr = $onsite_shopping;

                        if ($currency === 'USD') {
                            $url = "https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/usd.json";
                            $ch = curl_init($url);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json"]);
                            $response = curl_exec($ch);
                            $responseData = json_decode($response, true);
                            curl_close($ch);

                            if (isset($responseData['usd']['idr'])) {
                                $exchangeRate = $responseData['usd']['idr'];
                                $spend_idr = $spend * $exchangeRate;
                                $onsite_shopping_idr = $onsite_shopping * $exchangeRate;
                            }
                        }

                        $dt = [
                            'advertiser_id' => $advertiser_id,
                            'campaign_id' => $dimensions['campaign_id'] ?? '',
                            'campaign_name' => $getMetric('campaign_name'),
                            'impressions' => $getMetric('impressions'),
                            'clicks' => $getMetric('clicks'),
                            'spend' => $spend,
                            'spend_idr' => $spend_idr,
                            'spend_idr_after_tax' => $spend_idr + ($spend_idr * $this->current_tax),
                            'tax' => $this->current_tax,
                            'ctr' => $getMetric('ctr'),
                            'cpc' => $getMetric('cpc'),
                            'cpm' => $getMetric('cpm'),
                            'onsite_shopping_roas' => $getMetric('onsite_shopping_roas'),
                            'onsite_shopping' => $onsite_shopping_idr,
                            'cost_per_onsite_shopping' => $getMetric('cost_per_onsite_shopping'),
                            'onsite_shopping_rate' => $getMetric('onsite_shopping_rate'),
                            'value_per_onsite_shopping' => $getMetric('value_per_onsite_shopping'),
                            'total_onsite_shopping_value' => $getMetric('total_onsite_shopping_value'),
                            'onsite_on_web_cart' => $getMetric('onsite_on_web_cart'),
                            'total_onsite_on_web_cart_value' => $getMetric('total_onsite_on_web_cart_value'),
                            'frequency' => $getMetric('frequency'),
                            'reach' => $getMetric('reach'),
                            'currency' => $currency,
                            'date' => date('Y-m-d')
                        ];

                        $query = $this->db->where('campaign_id', $dt['campaign_id'])
                            ->where('date', date('Y-m-d'))
                            ->get('tiktok_campaign_data');

                        if ($query->num_rows() == 0) {
                            $this->db->insert('tiktok_campaign_data', $dt);
                        } else {
                            $this->db->where('campaign_id', $dt['campaign_id'])
                                ->where('date', date('Y-m-d'))
                                ->update('tiktok_campaign_data', $dt);
                        }
                    }
                }
            }
        }

            // Return JSON response
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => true,
                'message' => 'TikTok campaign data synced successfully',
                'advertiser_count' => count($advertiser_ids),
                'date' => date('Y-m-d')
            ]);
        } catch (Exception $e) {
            // Handle errors
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => 'Error syncing TikTok campaign data: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'date' => date('Y-m-d')
            ]);
        }
    }
    
    public function generate_recurring_expense()
    {
        $expenses = $this->mymodel->selectWithQuery("SELECT * FROM expense WHERE is_recurring = 1");
        foreach ($expenses as $expense) {
            $last_generated_at = $expense['last_generated_at'];
            $recurring_type = $expense['recurring_type'];

            $next_date = $this->get_next_date($last_generated_at, $recurring_type, $expense);
            print_r($expense);
            print_r($next_date);

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

            print_r($new_expense);
        }
        echo "Recurring expenses generated successfully.";
        $success = true;
    }

    public function get_next_date($last_date, $recurring_type, $expense)
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
                $date_of_month = min($date_of_month, $last_day_of_month); // Batasi tanggal sesuai jumlah hari di bulan
                return date('Y-m-d', strtotime("$next_year-$next_month-$date_of_month"));
            case 'Tahunan':
                return date('Y-m-d', strtotime($last_date . ' +1 year'));
            default:
                return $last_date;
        }
    }

    public function convertDayToEnglish($day)
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

// if ($success) {
//     $html['status'] = true;
//     $html['data'] = array();
//     $html['msg'] = 'Berhasil';
//     echo json_encode($html, true);
//     die;
// } else {
//     $html['status'] = false;
//     $html['data'] = array();
//     $html['msg'] = 'Tidak ada data yang diproses.';
//     echo json_encode($html, true);
//     die;
// }
header('Content-Type: application/json; charset=utf-8');
