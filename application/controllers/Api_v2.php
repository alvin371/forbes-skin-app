<?php

// Load custom env helper BEFORE Composer autoloader to prevent illuminate/support conflicts
require_once __DIR__ . '/../../application/helpers/env_helper.php';

require 'vendor/autoload.php';

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
class Api_v2 extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
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
    
    function update_cronjob(){
        $platform = 'Tiktok';
        // $data = $this->mymodel->selectWithQuery("SELECT link_upload FROM `endorse` WHERE DATE(created_at) BETWEEN '2025-05-01' AND '2025-05-25' AND platform = 'Tiktok' AND link_upload != '' AND id_campaign = 54 LIMIT 10");
        
        // foreach ($data as $row) {
        //     $link_upload = $row['link_upload'];
        //     $response = $this->template->get_social_media($platform, $link_upload);
            
        //     // Cetak response jika perlu
        //     print_r($response);
        // }
        $response = $this->template->get_account_id($platform, 'https://www.tiktok.com/@xxmivaxx4');
        print_r($response);
    }

    function objKeySort($obj)
    {
        $newKey = array_keys($obj);
        sort($newKey);
        $newObj = [];
        foreach ($newKey as $key) {
            $newObj[$key] = $obj[$key];
        }
        return $newObj;
    }

    function getEnvVar($k)
    {
        $v = $_ENV[$k] ?? null;
        if ($v !== null) {
            return $v;
        }
        $v = $_SERVER[$k] ?? null;
        if ($v !== null) {
            return $v;
        }
        return null;
    }
    function interpolateVar($value, $env)
    {
        foreach ($env as $key => $val) {
            $value = str_replace("{{" . $key . "}}", $val, $value);
        }
        return $value;
    }

    function tiktok_signature_generator($dt)
    {
        $secret = $dt['secret'];
        $ts = $dt['timest'];
        $queryParam = $dt['get'];
        $param = [];
        foreach ($queryParam as $key => $value) {
            if ($key == "timestamp") {
                $v = $ts;
            } else {
                $v = $value;
                if ($v == null || $v == "{{" . $key . "}}") {
                    $v = $this->getEnvVar($key);
                }
            }
            $param[$key] = $v;
        }
        unset($param["sign"]);
        unset($param["access_token"]);
        $sortedObj = $this->objKeySort($param);
        $path = parse_url($dt['url'], PHP_URL_PATH);
        $signstring = $secret . $path;
        foreach ($sortedObj as $key => $value) {
            $signstring .= $key . $value;
        }
        // $signstring .=  $secret;
        $signstring .= $dt['post'] . $secret;

        $sign = hash_hmac("sha256", $signstring, $secret);
        return $sign;
    }

    public function marketplace_callback_shopee()
    {
        $marketplace = "SHOPEE";
        $dt = $_GET;

        $host = 'https://partner.shopeemobile.com';
        $partner_id = $this->partner_id_shopee;
        $partner_key = $this->partner_key_shopee;
        $code = $dt['code'];
        $shop_id = $dt['shop_id'];
        $path = "/api/v2/auth/token/get";
        $timest = time();
        $body = array("code" => $code,  "shop_id" => intval($shop_id), "partner_id" => intval($partner_id));
        $baseString = sprintf("%s%s%s", $partner_id, $path, $timest);
        $sign = hash_hmac('sha256', $baseString, $partner_key);
        $url = sprintf("%s%s?partner_id=%s&timestamp=%s&sign=%s", $host, $path, $partner_id, $timest, $sign);

        $c = curl_init($url);
        curl_setopt($c, CURLOPT_POST, 1);
        curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($c);
        $response = json_decode($response, true);

        $access_token = $response['access_token'];
        $refresh_token = $response['refresh_token'];
        $expired_at = time() + $response['expire_in'];
        if (empty($access_token)) {
            echo 'Koneksi shopee tidak berhasil. Silahkan coba lagi nanti! <a href="' . base_url() . 'marketplace-account">Kembali</a>';
            die;
        }

        $path = "/api/v2/shop/get_profile";

        $timest = time();
        $body = array("partner_id" => intval($partner_id), "shop_id" => intval($shop_id), "refresh_token" => $refresh_token);
        $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
        $sign = hash_hmac('sha256', $baseString, $partner_key);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $host . $path . '?access_token=' . $access_token . '&partner_id=' . $partner_id . '&shop_id=' . $shop_id . '&sign=' . $sign . '&timestamp=' . $timest . '',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));
        $response = curl_exec($curl);
        $response = json_decode($response, true);



        $shop_id = $shop_id;
        $shop_name = $response['response']['shop_name'];
        $shop = $response['response'];
        $img_url = $response['response']['shop_logo'];

        if (empty($shop_id)) {
            echo 'Toko shopee tidak ditemukan. Silahkan coba lagi nanti! <a href="' . base_url() . 'marketplace-account">Kembali</a>';
            die;
        }

        $check = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $shop_id));

        $dt = array();
        if ($img_url) {
            $file_name = $shop_id . '.jpg';
            $img_dir = './assets/img/marketplace_account/' . $file_name;
            file_put_contents($img_dir, file_get_contents($img_url));
            $dt['img'] = $file_name;
        }
        $config = array();
        $config['partner_id'] = $partner_id;
        $config['access_token'] = $access_token;
        $config['refresh_token'] = $refresh_token;
        $config['shop'] = $shop;
        $dt['val'] = json_encode($config, true);
        $dt['opt'] = $marketplace;
        $dt['status'] = "Aktif";
        $dt['shop_id'] = $shop_id;
        $dt['shop_name'] = $shop_name;
        if ($check) {
            $dt['updated_at'] = DATE("Y-m-d H:i:s");
            $dt['updated_by'] = $_SESSION['user']['id'];
            $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
            $this->db->update('marketplace_config', $dt, array('id' => $check['id']));
        } else {
            $dt['created_at'] = DATE("Y-m-d H:i:s");
            $dt['created_by'] = $_SESSION['user']['id'];
            $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
            $this->db->insert('marketplace_config', $dt);
        }
        return redirect(base_url() . 'marketplace-account');
    }


    public function marketplace_callback_lazada()
    {
        $marketplace = "LAZADA";
        $dt = $_GET;
        $code = $dt['code'];
        $app_key = $this->app_key_lazada;
        $app_secret = $this->app_secret_lazada;

        $url = 'https://api.lazada.co.id/rest';

        $c = new LazopClient($url, $app_key, $app_secret);
        $request = new LazopRequest('/auth/token/create');
        $request->addApiParam('code', $code);

        $response = $c->execute($request);
        $response = json_decode($response, true);

        $access_token = $response['access_token'];
        $refresh_token = $response['refresh_token'];
        $expired_at = time() + $response['expires_in'];

        // print_r($response);

        if (empty($access_token)) {
            echo 'Koneksi lazada tidak berhasil. Silahkan coba lagi nanti! <a href="' . base_url() . 'marketplace-account">Kembali</a>';
            die;
        }

        $c = new LazopClient($url, $app_key, $app_secret);
        $request = new LazopRequest('/seller/get', 'GET');
        $response = $c->execute($request, $access_token);
        $response = json_decode($response, true);


        $shop_id = $response['data']['seller_id'];
        $shop_name = $response['data']['name'];
        $shop = $response['data'];
        $img_url = $response['data']['logo_url'];


        if (empty($shop_id)) {
            echo 'Toko lazada tidak ditemukan. Silahkan coba lagi nanti! <a href="' . base_url() . 'marketplace-account">Kembali</a>';
            die;
        }

        $check = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $shop_id));

        $dt = array();
        if ($img_url) {
            $file_name = $check['id'] . '.jpg';
            $img_dir = './assets/img/marketplace_account/' . $file_name;
            file_put_contents($img_dir, file_get_contents($img_url));
            $dt['img'] = $file_name;
        }
        $config = array();
        $config['app_key'] = $app_key;
        $config['access_token'] = $access_token;
        $config['refresh_token'] = $refresh_token;
        $config['shop'] = $shop;
        $dt['val'] = json_encode($config, true);
        $dt['opt'] = $marketplace;
        $dt['status'] = "Aktif";
        $dt['shop_id'] = $shop_id;
        $dt['shop_name'] = $shop_name;
        if ($check) {
            $dt['updated_at'] = DATE("Y-m-d H:i:s");
            $dt['updated_by'] = $_SESSION['user']['id'];
            $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
            $this->db->update('marketplace_config', $dt, array('id' => $check['id']));
        } else {
            $dt['created_at'] = DATE("Y-m-d H:i:s");
            $dt['created_by'] = $_SESSION['user']['id'];
            $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
            $this->db->insert('marketplace_config', $dt);
        }
        return redirect(base_url() . 'marketplace-account');
    }

    public function marketplace_callback_tiktok()
    {
        $marketplace = "TIKTOK";
        $dt = $_GET;
        $app_key = $dt['app_key'];
        $code = $dt['code'];
        $app_secret = $this->app_secret_tiktok;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'auth.tiktok-shops.com/api/v2/token/get?app_key=' . $app_key . '&app_secret=' . $app_secret . '&auth_code=' . $code . '&grant_type=authorized_code',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $response = json_decode($response, true);

        $access_token = $response['data']['access_token'];
        $refresh_token = $response['data']['refresh_token'];
        $expired_at = $response['data']['access_token_expire_in'];
        if (empty($access_token)) {
            echo 'Koneksi tiktok tidak berhasil. Silahkan coba lagi nanti! <a href="' . base_url() . 'marketplace-account">Kembali</a>';
            die;
        }

        $url = 'https://open-api.tiktokglobalshop.com/authorization/202309/shops?app_key=' . $app_key . '&sign={{sign}}&timestamp={{timestamp}}';
        $urlParts = parse_url($url);
        $paramGET = [];
        parse_str($urlParts['query'], $paramGET);
        $secret = $this->app_secret_tiktok;
        $timest = strtotime('now');
        $pr = array();
        $pr['secret'] = $secret;
        $pr['timest'] = $timest;
        $pr['get'] = $paramGET;
        $pr['url'] = $url;
        $sign = $this->tiktok_signature_generator($pr);

        $url = str_replace('{{sign}}', $sign, $url);
        $url = str_replace('{{timestamp}}', $timest, $url);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'x-tts-access-token: ' . $access_token
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $response = json_decode($response, true);

        $shop_id = $response['data']['shops'][0]['id'];
        $shop_name = $response['data']['shops'][0]['name'];
        $shop = $response['data']['shops'][0];

        if (empty($shop_id)) {
            echo 'Toko tiktok tidak ditemukan. Silahkan coba lagi nanti! <a href="' . base_url() . 'marketplace-account">Kembali</a>';
            die;
        }

        $check = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $shop_id));

        $dt = array();
        $config = array();
        $config['app_key'] = $app_key;
        $config['access_token'] = $access_token;
        $config['refresh_token'] = $refresh_token;
        $config['shop'] = $shop;
        $dt['val'] = json_encode($config, true);
        $dt['opt'] = $marketplace;
        $dt['status'] = "Aktif";
        $dt['shop_id'] = $shop_id;
        $dt['shop_name'] = $shop_name;

        if ($check) {
            $dt['updated_at'] = DATE("Y-m-d H:i:s");
            $dt['updated_by'] = $_SESSION['user']['id'];
            $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
            $this->db->update('marketplace_config', $dt, array('id' => $check['id']));
        } else {
            $dt['created_at'] = DATE("Y-m-d H:i:s");
            $dt['created_by'] = $_SESSION['user']['id'];
            $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
            $this->db->insert('marketplace_config', $dt);
        }
        return redirect(base_url() . 'marketplace-account');
    }

    function marketplace_token_refresh()
    {
        header('Content-Type: application/json; charset=utf-8');
        $dt = $_GET;
        $marketplace = $dt['marketplace'];
        $marketplace = strtoupper($marketplace);
        $shop_id = $dt['shop_id'];
        $qry = "";
        if ($shop_id) {
            $qry .= " AND shop_id = '$shop_id' ";
        }
        if ($marketplace) {
            $qry .= " AND opt = '$marketplace' ";
        }
        $data = $this->mymodel->selectWithQuery("SELECT *
        FROM marketplace_config
        WHERE status = 'Aktif' $qry");

        $is_error = false;
        $text = '';

        foreach ($data as $k => $v) {
            if ($v['opt'] == "TIKTOK") {
                $marketplace = $v['opt'];
                $config = json_decode($v['val'], true);
                $app_key = $this->app_key_tiktok;
                $refresh_token = $config['refresh_token'];
                $app_secret = $this->app_secret_tiktok;

                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'auth.tiktok-shops.com/api/v2/token/refresh?app_key=' . $app_key . '&app_secret=' . $app_secret . '&refresh_token=' . $refresh_token . '&grant_type=refresh_token',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                ));

                $response = curl_exec($curl);
                curl_close($curl);

                $response = json_decode($response, true);

                if ($response['data']['access_token']) {

                    $access_token = $response['data']['access_token'];
                    $refresh_token = $response['data']['refresh_token'];
                    $expired_at = $response['data']['access_token_expire_in'];

                    $url = 'https://open-api.tiktokglobalshop.com/authorization/202309/shops?app_key=' . $app_key . '&sign={{sign}}&timestamp={{timestamp}}';
                    $urlParts = parse_url($url);
                    $paramGET = [];
                    parse_str($urlParts['query'], $paramGET);
                    $secret = $this->app_secret_tiktok;
                    $timest = strtotime('now');
                    $pr = array();
                    $pr['secret'] = $secret;
                    $pr['timest'] = $timest;
                    $pr['get'] = $paramGET;
                    $pr['url'] = $url;
                    $sign = $this->tiktok_signature_generator($pr);

                    $url = str_replace('{{sign}}', $sign, $url);
                    $url = str_replace('{{timestamp}}', $timest, $url);

                    $curl = curl_init();

                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'GET',
                        CURLOPT_HTTPHEADER => array(
                            'x-tts-access-token: ' . $access_token
                        ),
                    ));

                    $response = curl_exec($curl);

                    curl_close($curl);

                    $response = json_decode($response, true);

                    $shop_id = $response['data']['shops'][0]['id'];
                    $shop_name = $response['data']['shops'][0]['name'];
                    $shop = $response['data']['shops'][0];

                    $dt = array();
                    $config = array();
                    $config['app_key'] = $app_key;
                    $config['access_token'] = $access_token;
                    $config['refresh_token'] = $refresh_token;
                    $config['shop'] = $shop;
                    $dt['val'] = json_encode($config, true);
                    $dt['opt'] = $marketplace;
                    $dt['status'] = "Aktif";
                    $dt['shop_id'] = $shop_id;
                    $dt['shop_name'] = $shop_name;
                    $dt['updated_at'] = DATE("Y-m-d H:i:s");
                    $dt['refresh_token_at'] = DATE("Y-m-d H:i:s");
                    $dt['updated_by'] = $_SESSION['user']['id'];
                    $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
                    if ($shop_id) {
                        $this->db->update('marketplace_config', $dt, array('id' => $v['id']));
                    } else {
                        $is_error = true;
                        $text .= 'Toko ' . $v['shop_name'] . ' tidak ditemukan!<br>';
                    }
                }
            } else if ($v['opt'] == "SHOPEE") {
                $marketplace = $v['opt'];
                $config = json_decode($v['val'], true);
                $partner_id = $this->partner_id_shopee;
                $partner_key = $this->partner_key_shopee;
                $refresh_token = $config['refresh_token'];
                $shop_id = $v['shop_id'];

                $host = 'https://partner.shopeemobile.com';
                $path = "/api/v2/auth/access_token/get";
                $timest = time();
                $body = array("partner_id" => intval($partner_id), "shop_id" => intval($shop_id), "refresh_token" => $refresh_token);
                $baseString = sprintf("%s%s%s", $partner_id, $path, $timest);
                $sign = hash_hmac('sha256', $baseString, $partner_key);
                $url = sprintf("%s%s?partner_id=%s&timestamp=%s&sign=%s", $host, $path, $partner_id, $timest, $sign);

                $c = curl_init($url);
                curl_setopt($c, CURLOPT_POST, 1);
                curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($body));
                curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);

                $response = curl_exec($c);
                $response = json_decode($response, true);

                $access_token = $response['access_token'];
                $refresh_token = $response['refresh_token'];
                $expired_at = time() + $response['expire_in'];

                if ($access_token) {
                    $path = "/api/v2/shop/get_profile";

                    $timest = time();
                    $body = array("partner_id" => intval($partner_id), "shop_id" => intval($shop_id), "refresh_token" => $refresh_token);
                    $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
                    $sign = hash_hmac('sha256', $baseString, $partner_key);

                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $host . $path . '?access_token=' . $access_token . '&partner_id=' . $partner_id . '&shop_id=' . $shop_id . '&sign=' . $sign . '&timestamp=' . $timest . '',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'GET',
                        CURLOPT_HTTPHEADER => array(
                            'Content-Type: application/json'
                        ),
                    ));
                    $response = curl_exec($curl);
                    $response = json_decode($response, true);


                    $shop_id = $shop_id;
                    $shop_name = $response['response']['shop_name'];
                    $shop = $response['response'];
                    $img_url = $response['response']['shop_logo'];
                    if ($shop_id) {
                        $dt = array();
                        if ($img_url) {
                            $file_name = $shop_id . '.jpg';
                            $img_dir = './assets/img/marketplace_account/' . $file_name;
                            file_put_contents($img_dir, file_get_contents($img_url));
                            $dt['img'] = $file_name;
                        }

                        $config = array();
                        $config['partner_id'] = $partner_id;
                        $config['access_token'] = $access_token;
                        $config['refresh_token'] = $refresh_token;
                        $config['shop'] = $shop;
                        $dt['val'] = json_encode($config, true);
                        $dt['opt'] = $marketplace;
                        $dt['status'] = "Aktif";
                        $dt['shop_id'] = $shop_id;
                        $dt['shop_name'] = $shop_name;
                        $dt['updated_at'] = DATE("Y-m-d H:i:s");
                        $dt['refresh_token_at'] = DATE("Y-m-d H:i:s");
                        $dt['updated_by'] = $_SESSION['user']['id'];
                        $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
                        $this->db->update('marketplace_config', $dt, array('id' => $v['id']));
                    } else {
                        $is_error = true;
                        $text .= 'Toko ' . $v['shop_name'] . ' tidak ditemukan!<br>';
                    }
                } else {
                    $is_error = true;
                    $text .= 'Refresh token shopee id : ' . $v['shop_id'] . ' tidak valid!<br>';
                }
            } else if ($v['opt'] == "LAZADA") {
                $marketplace = $v['opt'];
                $config = json_decode($v['val'], true);
                $app_key = $this->app_key_lazada;
                $app_secret = $this->app_secret_lazada;
                $refresh_token = $config['refresh_token'];
                $url = 'https://api.lazada.co.id/rest';

                $c = new LazopClient($url, $app_key, $app_secret);
                $request = new LazopRequest('/auth/token/refresh');
                $request->addApiParam('refresh_token', $refresh_token);
                $response = $c->execute($request);
                $response = json_decode($response, true);

                $access_token = $response['access_token'];
                $refresh_token = $response['refresh_token'];
                $expired_at = time() + $response['expires_in'];


                $c = new LazopClient($url, $app_key, $app_secret);
                $request = new LazopRequest('/seller/get', 'GET');
                $response = $c->execute($request, $access_token);
                $response = json_decode($response, true);

                $shop_id = $response['data']['seller_id'];
                $shop_name = $response['data']['name'];
                $shop = $response['data'];
                $img_url = $response['data']['logo_url'];

                $dt = array();
                if ($img_url) {
                    $file_name = $shop_id . '.jpg';
                    $img_dir = './assets/img/marketplace_account/' . $file_name;
                    file_put_contents($img_dir, file_get_contents($img_url));
                    $dt['img'] = $file_name;
                }

                $config = array();
                $config['app_key'] = $app_key;
                $config['access_token'] = $access_token;
                $config['refresh_token'] = $refresh_token;
                $config['shop'] = $shop;
                $dt['val'] = json_encode($config, true);
                $dt['opt'] = $marketplace;
                $dt['status'] = "Aktif";
                $dt['shop_id'] = $shop_id;
                $dt['shop_name'] = $shop_name;
                $dt['updated_at'] = DATE("Y-m-d H:i:s");
                $dt['refresh_token_at'] = DATE("Y-m-d H:i:s");
                $dt['updated_by'] = $_SESSION['user']['id'];
                $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
                if ($shop_id) {
                    $this->db->update('marketplace_config', $dt, array('id' => $v['id']));
                } else {
                    $is_error = true;
                    $text .= 'Toko ' . $v['shop_name'] . ' tidak ditemukan!<br>';
                }
            } else if ($v['opt'] == "META") {
                $marketplace = $v['opt'];
                $app_id = $this->app_id_meta;
                $app_secret = $this->app_secret_meta;
                $config = json_decode($v['val'], true);
                $refresh_token = $config['access_token'];

                $url = "https://graph.facebook.com/v21.0/oauth/access_token";

                $params = array(
                    'grant_type' => 'fb_exchange_token',
                    'client_id' => $app_id,
                    'client_secret' => $app_secret,
                    'fb_exchange_token' => $refresh_token
                );

                $url_with_params = $url . '?' . http_build_query($params);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url_with_params);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $response = curl_exec($ch);
                curl_close($ch);

                $response_data = json_decode($response, true);

                $config = array();
                $config['app_id'] = $app_id;
                $config['access_token'] = $refresh_token;
                $config['refresh_token'] = $refresh_token;
                $dt['val'] = json_encode($config, true);
                $dt['opt'] = $marketplace;
                $dt['status'] = "Aktif";
                $dt['shop_id'] = $app_id;
                $dt['updated_at'] = DATE("Y-m-d H:i:s");
                $dt['refresh_token_at'] = DATE("Y-m-d H:i:s");
                $dt['updated_by'] = $_SESSION['user']['id'];
                $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
                if ($app_id) {
                    $sql = "UPDATE marketplace_config 
                            SET val = ? 
                            WHERE shop_id = ?";
                    $this->db->query($sql, array($dt['val'], $app_id));
                } else {
                    $is_error = true;
                    $text .= 'Toko ' . $v['shop_name'] . ' tidak ditemukan!<br>';
                }
            }
        }
        if ($is_error) {
            $html['status'] = false;
            $html['data'] = array();
            $html['msg'] = $text;
            echo json_encode($html, true);
            die;
        } else {
            $html['status'] = true;
            $html['data'] = array();
            $html['msg'] = 'Refresh token berhasil!';
            echo json_encode($html, true);
            die;
        }
    }

    function customer_summary()
    {
        $id_customer = $_GET['id'];
        $this->buyer_summary($id_customer);

        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = true;
        $html['data'] = array();
        $html['msg'] = 'Pembaharuan customer order berhasil!';
        echo json_encode($html, true);
        die;
    }

    function buyer_summary($id_customer)
    {

        $dtt = array();
        $query = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count, 
        SUM(omset_kotor) as omset_kotor,
        SUM(komisi_afiliasi) as komisi_afiliasi,
        SUM(diskon_penjual) as diskon_penjual,
        SUM(omset_bersih) as omset_bersih,
        SUM(dana_pencairan) as dana_pencairan,
        SUM(price_total) as price_total,
        SUM(marketplace_fee) as marketplace_fee,
        SUM(customer_price) as customer_price,
        SUM(transaction.return) as returnn FROM transaction WHERE customer = '$id_customer' AND type_sub = 'POS'
        AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID')
        ORDER BY transaction.date ASC
        ");

        $dtt['count_order'] = strval($query[0]['count']);
        $dtt['return_trx'] = strval($query[0]['returnn']);
        $dtt['omset_kotor'] = strval($query[0]['omset_kotor']);
        $dtt['komisi_afiliasi'] = strval($query[0]['komisi_afiliasi']);
        $dtt['diskon_penjual'] = strval($query[0]['diskon_penjual']);
        $dtt['omset_bersih'] = strval($query[0]['omset_bersih']);
        $dtt['dana_pencairan'] = strval($query[0]['dana_pencairan']);
        $dtt['price_total'] = strval($query[0]['price_total']);
        $dtt['marketplace_fee'] = strval($query[0]['marketplace_fee']);
        $dtt['customer_price'] = strval($query[0]['customer_price']);

        $query = $this->mymodel->selectWithQuery("SELECT
        transaction.date,
        order_id,
        order_status,
        pesanan,
        transaction.json,
        is_manual,
        (omset_kotor) as omset_kotor,
        (komisi_afiliasi) as komisi_afiliasi,
        (diskon_penjual) as diskon_penjual,
        (omset_bersih) as omset_bersih,
        (dana_pencairan) as dana_pencairan,
        (price_total) as price_total,
        (marketplace_fee) as marketplace_fee,
        (customer_price) as customer_price,
        (transaction.return) as returnn FROM transaction WHERE customer = '$id_customer' AND type_sub = 'POS'
        AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID')
        ORDER BY transaction.date ASC");

        $dtt['first_order'] = strval($query[0]['date']);

        $last_query = end($query);

        $dtt['last_order'] = "";
        if ($last_query['date'] && $last_query['date'] != $dtt['first_order']) {
            $dtt['last_order'] = strval($last_query['date']);
        }


        $dt = array();
        if ($query) {
            if (count($query) > 1) {
                $dt['cb_cl'] = "CL";
            } else {
                $dt['cb_cl'] = "CB";
            }
            $this->db->update('transaction', $dt, array('customer' => $id_customer));
        }


        $json = array();
        $price_total_hpp = 0;
        foreach ($query as $kk => $vv) {
            $json[$kk]['date'] = $vv['date'];
            $json[$kk]['id'] = $vv['id'];
            $json[$kk]['order_id'] = $vv['order_id'];
            $json[$kk]['order_status'] = $vv['order_status'];
            $json[$kk]['is_manual'] = $vv['is_manual'];
            if ($vv['is_manual'] == 1) {
                $json[$kk]['data'] = json_decode($vv['pesanan'], true);
            } else {
                $vv['pesanan'] = array();
                foreach (json_decode($vv['json'], true) as $kkk => $vvv) {
                    $vv['pesanan'][$kkk]['item_name'] = $vvv['product_text'];
                    $vv['pesanan'][$kkk]['item_sku'] = $vvv['sku'];
                    $vv['pesanan'][$kkk]['item_id'] = $vvv['product'];
                    $vv['pesanan'][$kkk]['qty'] = $vvv['qty'];
                }
                $json[$kk]['data'] = $vv['pesanan'];
            }
        }

        $dtt['pesanan'] = json_encode($json, true);
        if (empty($query)) {
            $this->db->delete('customer', array('id' => $id_customer));
        } else {
            if (count($query) > 1) {
                $dt['cb_cl'] = "CL";
            } else {
                $dt['cb_cl'] = "CB";
            }
            $this->db->update('customer', $dtt, array('id' => $id_customer));
        }

        return $dt;
    }

    function calculate_buyer($dt)
    {
        $user = $_SESSION['user'];
    
        if (is_string($dt['pesanan'])) {
            $dt['pesanan'] = json_decode($dt['pesanan'], true);
        }
    
        $sku_list = [];
        foreach ($dt['pesanan'] as $item) {
            $sku_cleaned = preg_replace('/^\d+-/', '', $item['sku']);
            $sku_list[] = "'" . $sku_cleaned . "'"; 
        }
    
        if (!empty($sku_list)) {
            $sku_values = implode(',', $sku_list); 
            $query = "SELECT DISTINCT p.sku, p.brand FROM product p WHERE p.is_varian = 0 AND p.sku IN ($sku_values)";
            $result = $this->mymodel->selectWithQuery($query);
        } else {
            $result = [];
        }

        $brand_list = array_column($result, 'brand');

        $dt['brand'] = implode(', ', array_unique($brand_list));

        $data = $this->mymodel->selectDataOne('customer', ['id_buyer' => $dt['id_buyer'], 'marketplace' => $dt['marketplace']]);
        

        if (empty($data)) {
            $dtt = [
                'akun_type' => $dt['c_type'],
                'brand' => $dt['brand'],
                'marketplace' => $dt['marketplace'],
                'id_buyer' => strval($dt['id_buyer']),
                'status' => "Aktif",
                'created_at' => date("Y-m-d H:i:s"),
                'created_by' => strval($user['id']),
                'full_name' => $dt['customer_text'],
                'phone' => $dt['phone'],
                'username' => strval($dt['c_username']),
                'count_order' => 1,
                'first_order' => strval($dt['date']),
                'last_order' => strval($dt['date']),
                'address' => $dt['address'],
                'province_text' => $dt['province_text'],
                'city_text' => $dt['city_text'],
                'subdistrict_text' => $dt['subdistrict_text'],
                'shop_id' => $dt['shop_id'],
                'shop_name' => $dt['shop_name']
            ];
            $this->db->insert('customer', $dtt);
            $id_buyer = $this->db->insert_id();
        } else {
            $dtt = [
                'updated_at' => date("Y-m-d H:i:s"),
                'shop_id' => $dt['shop_id'],
                'shop_name' => $dt['shop_name'],
                'brand' => $dt['brand'],
                'akun_type' => $dt['c_type'],
            ];
            $this->db->update('customer', $dtt, ['id' => $data['id']]);
            $id_buyer = $data['id'];
        }

        $this->db->update('transaction', ['customer' => $id_buyer], ['id' => $dt['id']]);

        $this->buyer_summary($id_buyer);
    }


    public function update_stock($id_product)
    {
        $dtp = array();
        $id_product = $id_product;
        $query = $this->mymodel->selectWithQuery("SELECT SUM(qty_in) as qty_in,SUM(qty_in_pos) as qty_in_pos,SUM(qty_out) as qty_out,SUM(qty_out_pos) as qty_out_pos,SUM(qty) as qty FROM stock WHERE product = '$id_product'");

        $dtp['stock_in'] = strval($query[0]['qty_in']);
        $dtp['stock_in_pos'] = strval($query[0]['qty_in_pos']);
        $dtp['stock_out'] = strval(abs($query[0]['qty_out']) * -1);
        $dtp['stock_out_pos'] = strval(abs($query[0]['qty_out_pos']) * -1);
        $dtp['stock'] = strval(doubleval($query[0]['qty']));
        $this->db->update('product', $dtp, array('id' => $id_product));
    }

    function calculate_stock_product()
    {
        $product = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE is_varian = 0
        ORDER BY sku ASC
        ");
        $product_arr = array();
        foreach ($product as $k => $v) {
            $product_arr[$v['id']] = $v;
        }

        foreach ($product_arr as $k => $v) {
            $id_product = $v['id'];
            $this->update_stock($id_product);
        }
    }
    function calculate_stock_product_by_id($id)
    {
        $product = $this->mymodel->selectWithQuery("SELECT * FROM product 
        WHERE id = '$id'
        ORDER BY sku ASC
        ");
        $product_arr = array();
        foreach ($product as $k => $v) {
            $product_arr[$v['id']] = $v;
        }

        foreach ($product_arr as $k => $v) {
            $id_product = $v['id'];
            $this->update_stock($id_product);
        }
    }
    
    function calculate_stock($dt)
    {
        $order_id = $dt['order_id'];
        $marketplace = $dt['marketplace'];
        $shop_id = $dt['shop_id'];
        $user = $_SESSION['user'];
        $id_trx = $dt['id'];
        if ($order_id) {
            $this->db->delete('stock_product_3rd', " id_trx = '$id_trx' AND type_sub = 'POS' AND type = 'Out' ");
            $this->db->delete('stock', " id_trx = '$id_trx' AND type_sub = 'POS' AND type = 'Out' ");
        }
        // if (in_array($dt['order_status'], array('CANCELLED', 'IN_CANCEL'))) {
        //     $this->db->delete('stock_product_3rd', " id_trx = '$id_trx' AND type_sub = 'POS' ");
        //     $this->db->delete('stock', " id_trx = '$id_trx' AND type_sub = 'POS' ");
        // }
        // if (!in_array($dt['order_status'], array('CANCELLED', 'IN_CANCEL', 'RETURN', 'REFUND'))) {
        foreach ($dt['stock_product_3rd'] as $k2 => $v2) {
            $dts = array();
            $dts['shipping'] = strval($dt['shipping']);
            $dts['awb_number'] = strval($dt['awb_number']);
            $dts['order_status'] = strval($dt['order_status']);
            $dts['marketplace'] = strval($dt['marketplace']);
            $dts['shop_id'] = strval($dt['shop_id']);
            $dts['shop_name'] = strval($dt['shop_name']);
            $dts['brand'] = strval($dt['brand']);
            $dts['id_trx'] = strval($dt['id']);
            $dts['qty'] = 0 - abs($v2['qty']);
            // $dts['qty_out'] = abs($v2['qty']);
            $dts['qty_out_pos'] = abs($v2['qty']);
            $dts['qty_in'] = '0';

            $dts['original_price'] = doubleval($v2['original_price']);
            $dts['price'] = doubleval($v2['price']);
            $dts['discount'] = doubleval($v2['discount']);
            $dts['price_total'] = doubleval($v2['price_total']);



            $dts['product_id'] = strval($v2['id_product_parent']);
            $dts['product_sku'] = strval($v2['sku_parent']);
            $dts['product_text'] = strval($v2['name_parent']);
            $dts['varian_id'] = strval($v2['id_product']);
            $dts['varian_sku'] = strval($v2['sku']);
            $dts['varian_text'] = strval($v2['name']);
            $dts['order_id'] = $dt['order_id'];
            $dts['type'] = "Out";
            $dts['type_sub'] = "POS";
            $dts['created_at'] = DATE("Y-m-d H:i:s");
            $dts['date'] = $dt['date'];
            $dts['created_by'] = strval($user['id']);
            $dts['status'] = "Aktif";
            $dts['desc'] = "Penjualan";

            if ($dts['id_trx']) {
                $this->db->insert('stock_product_3rd', $dts);
            }
            
            if (in_array($dt['order_status'], array('CANCELLED', 'IN_CANCEL'))) {
                $dts['date'] = DATE("Y-m-d H:i:s", strtotime($dt['return_at']));
                $dts['type'] = "In";
                $dts['qty'] =  abs($v2['qty']);
                $dts['qty_out'] = '0';
                $dts['qty_out_pos'] = '0';
                $dts['qty_in'] = '0';
                $dts['qty_in_pos'] =  abs($v2['qty']);
                $dts['desc'] = "Return";

                if ($dts['id_trx']) {
                    $this->db->insert('stock_product_3rd', $dts);
                }
            }

            if (in_array($dt['order_status'], array('CANCELLED', 'IN_CANCEL')) && $dt['is_shipped'] == 1) {
                $dts['date'] = DATE("Y-m-d H:i:s", strtotime($dt['return_at']));
                $dts['type'] = "Ongoing";
                $dts['qty'] =  abs($v2['qty']);
                $dts['qty_out'] = '0';
                $dts['qty_out_pos'] = '0';
                $dts['qty_in'] = '0';
                $dts['qty_in_pos'] =  '0';
                $dts['qty_retur'] =  abs($v2['qty']);
                $dts['desc'] = "Return";

                if ($dts['id_trx']) {
                    $this->db->insert('stock_product_3rd', $dts);
                }
            }
        }
        
        // if ($dt['order_status'] == 'CANCELLED' && $dt['is_shipped'] == 0) {
        //     $this->db->delete('stock_product_3rd', " id_trx = '$id_trx' AND type_sub = 'POS' ");
        // }
        
        foreach ($dt['stock'] as $k2 => $v2) {
            $dts = array();
            $dts['shipping'] = strval($dt['shipping']);
            $dts['awb_number'] = strval($dt['awb_number']);
            $dts['order_status'] = strval($dt['order_status']);
            $dts['marketplace'] = strval($dt['marketplace']);
            $dts['shop_id'] = strval($dt['shop_id']);
            $dts['shop_name'] = strval($dt['shop_name']);
            $dts['id_trx'] = strval($dt['id']);
            $dts['qty'] = 0 - abs($v2['qty']);
            // $dts['qty_out'] = abs($v2['qty']);
            $dts['qty_out_pos'] = abs($v2['qty']);
            $dts['qty_in'] = '0';
            $dts['price'] = doubleval($v2['price']);
            $dts['discount'] = doubleval($v2['discount']);
            $dts['hpp'] = doubleval($v2['hpp']);
            $dts['price_total'] = doubleval($v2['price_total']);
            $dts['product'] = $v2['product'];
            $dts['product_text'] = $v2['product_text'];
            $dts['sku'] = $v2['sku'];
            $dts['order_id'] = $dt['order_id'];
            $dts['brand'] = strval($dt['brand']);
            $dts['type'] = "Out";
            $dts['type_sub'] = "POS";
            $dts['created_at'] = DATE("Y-m-d H:i:s");
            $dts['date'] = $dt['date'];
            $dts['created_by'] = strval($user['id']);
            $dts['status'] = "Aktif";

            if ($dts['id_trx']) {
                $this->db->insert('stock', $dts);
            }
            
            if (in_array($dt['order_status'], array('CANCELLED', 'IN_CANCEL'))) {
                if (empty($dt['return_at'])) {
                    $dt['return_at'] = $dt['cancel_at'];
                }
                $dts['date'] = DATE("Y-m-d H:i:s", strtotime($dt['return_at']));
                $dts['type'] = "In";
                $dts['qty'] =  abs($v2['qty']);
                $dts['qty_out'] = '0';
                $dts['qty_out_pos'] = '0';
                $dts['qty_in'] = '0';
                $dts['qty_in_pos'] =  abs($v2['qty']);
                if ($dts['id_trx']) {
                    $this->db->insert('stock', $dts);
                }
            }
            
            if (in_array($dt['order_status'], array('CANCELLED', 'IN_CANCEL')) && $dt['is_shipped'] == 1) {
                if (empty($dt['return_at'])) {
                    $dt['return_at'] = $dt['cancel_at'];
                }
                $dts['date'] = DATE("Y-m-d H:i:s", strtotime($dt['return_at']));
                $dts['type'] = "Ongoing";
                $dts['qty'] =  abs($v2['qty']);
                $dts['qty_out'] = '0';
                $dts['qty_out_pos'] = '0';
                $dts['qty_in'] = '0';
                $dts['qty_in_pos'] =  '0';
                $dts['qty_retur'] =  abs($v2['qty']);
                if ($dts['id_trx']) {
                    $this->db->insert('stock', $dts);
                }
            }
        }
        
        // if ($dt['order_status'] == 'CANCELLED' && $dt['is_shipped'] == 0) {
        //     $this->db->delete('stock', " id_trx = '$id_trx' AND type_sub = 'POS' ");
        // }

        foreach ($dt['stock'] as $k2 => $v2) {
            $this->calculate_stock_product_by_id($v2['product']);
        }
        // }
        $this->update_stock_marketplace($dt);
    }

    
    function update_stock_marketplace($dt) 
    {
        $id_product = array_keys($dt['stock']);
        $id_products = implode("','", $id_product);
    
        $products = $this->mymodel->selectWithQuery("SELECT sku, stock as stock_akhir 
                                FROM product
                                WHERE id IN ('$id_products') GROUP BY sku");
        $stock_map = [];
        $sku_arr = [];
        foreach ($products as $sku) {
            $uppercase_sku = strtoupper($sku['sku']);
            $sku_arr[] = $uppercase_sku;
            $stock_map[$uppercase_sku] = $sku['stock_akhir'];

            if (preg_match('/^(\d+)-(.+)/', $sku['sku'], $matches)) {
                $base_sku = strtoupper($matches[2]);
                if (!isset($stock_map[$base_sku])) {
                    $stock_map[$base_sku] = $sku['stock_akhir'];
                }
            }
        }
    
        $like_clauses = array_map(function($sku) {
            return "json_varian LIKE '%$sku%'";
        }, $sku_arr);
        $like_sql = implode(' OR ', $like_clauses);
    
        $products_3rd = $this->mymodel->selectWithQuery("SELECT id_product, marketplace, shop_id, shop_name, json_varian FROM product_3rd WHERE $like_sql");
    
        foreach ($products_3rd as &$p3) {
            $json_varian = json_decode($p3['json_varian'], true);
            $p3_variants = []; 
            
            if (is_array($json_varian)) {
                foreach ($json_varian as $varian) {
                    $sku = isset($varian['model_sku']) ? $varian['model_sku'] : ($varian['sku'] ?? '');
                    $sku_parent = $varian['sku_parent'] ?? '';
                    $sku_used = '';
                    $stock_total = null;

        
                    $stock_map_upper = array_change_key_case($stock_map, CASE_UPPER);
        
                    $bundle_parts = [];
                    if ($sku !== '') {
                        $bundle_parts = preg_split('/[\s&+\/,]+/', strtoupper(trim($sku)));
                    } elseif ($sku_parent !== '') {
                        $bundle_parts = preg_split('/\s*[&+,\/]\s*/', strtoupper(trim($sku_parent)));
                        $bundle_parts = array_map('trim', $bundle_parts);
                        $bundle_parts = array_filter($bundle_parts);
                    }
        
                    if (preg_match('/^(\d+)-(.+)/', $sku, $matches)) {
                        $quantity = (int)$matches[1];
                        $base_sku = strtoupper($matches[2]);
                        
                        if (isset($stock_map_upper[$base_sku])) {
                            $stock_total = floor($stock_map_upper[$base_sku] / $quantity);
                            $sku_used = $sku;
                        }
                    }
                    elseif (count($bundle_parts) > 1) {
                        $stock_candidates = [];
                        $sku_in = implode("','", array_map('addslashes', $bundle_parts)); 
                        $query = "SELECT sku, stock as stock_akhir 
                                  FROM product 
                                  WHERE UPPER(sku) IN ('$sku_in') 
                                  GROUP BY sku";
                    
                        $result = $this->mymodel->selectWithQuery($query);
                    
                        $stock_lookup = [];
                        foreach ($result as $row) {
                            $stock_lookup[strtoupper($row['sku'])] = $row['stock_akhir'];
                        }
                    
                        foreach ($bundle_parts as $part) {
                            $part_upper = strtoupper($part);
                            if (isset($stock_lookup[$part_upper])) {
                                $stock_candidates[] = $stock_lookup[$part_upper];
                            }
                        }
                    
                        if (!empty($stock_candidates)) {
                            $stock_total = min($stock_candidates);
                            $sku_used = implode(' + ', $bundle_parts);
                        }
                    }
                    else {
                        $sku_candidate = !empty($bundle_parts) ? $bundle_parts[0] : strtoupper(trim($sku));
                        
                        if (isset($stock_map_upper[$sku_candidate])) {
                            $stock_total = $stock_map_upper[$sku_candidate];
                            $sku_used = $sku_candidate;
                        }
                    }
        
                    if ($stock_total !== null) {
                        $variant_data = [
                            'id_product' => $p3['id_product'],
                            'marketplace' => $p3['marketplace'],
                            'shop_id' => $p3['shop_id'],
                            'shop_name' => $p3['shop_name'],
                            'json_varian' => $p3['json_varian'],
                            'sku' => $sku_used,
                            'stock' => $stock_total,
                            'variant_details' => $varian
                        ];
                        
                        $p3_variants[] = $variant_data;
                    }
                }
            }
            
            if (empty($p3_variants)) {
                $p3_variants[] = [
                    'id_product' => $p3['id_product'],
                    'marketplace' => $p3['marketplace'],
                    'shop_id' => $p3['shop_id'],
                    'shop_name' => $p3['shop_name'],
                    'json_varian' => $p3['json_varian'],
                    'sku' => null,
                    'stock' => 0
                ];
            }
            
            $p3 = $p3_variants;
        }
        unset($p3);
        
        $flattened_products = [];
        foreach ($products_3rd as $product_variants) {
            foreach ($product_variants as $variant) {
                $flattened_products[] = $variant;
            }
        }
        
        $arr_product = [
            'SHOPEE' => [],
            'LAZADA' => [],
            'TIKTOK' => [],
        ];
        
        foreach ($flattened_products as $product) {
            $marketplace = strtoupper($product['marketplace']);
            if (isset($arr_product[$marketplace])) {
                $arr_product[$marketplace][] = $product;
            }
        }
        
        foreach ($arr_product as $marketplace => $products) {
            foreach ($products as $product) {
                $stock = $product['stock'];
                
                switch ($marketplace) {
                    case 'SHOPEE':
                        $this->updateShopeeStock($product, $stock);
                        break;
                    case 'LAZADA':
                        $this->updateLazadaStock($product, $stock);
                        break;
                    case 'TIKTOK':
                        $this->updateTiktokStock($product, $stock);
                        break;
                }
            }
        }
    }

    protected function updateShopeeStock($product, $stock) 
    {
        $config = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $product['shop_id']));
        if (!$config) return false;
        
        $config = json_decode($config['val'], true);
        $access_token = $config['access_token'];
        $partner_id = $this->partner_id_shopee;
        $partner_key = $this->partner_key_shopee;
        $host = 'https://partner.shopeemobile.com';
    
        $path = "/api/v2/product/update_stock";
        $timest = time();
        $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $product['shop_id']);
        $sign = hash_hmac('sha256', $baseString, $partner_key);
        
        $variants = json_decode($product['json_varian'], true) ?? [];
        
        $stock_list = [];
        $target_sku = $product['sku']; 
        
        foreach ($variants as $variant) {
            if (isset($variant['sku']) && strtoupper(trim($variant['sku'])) === strtoupper(trim($target_sku))) {
                if (!empty($variant['id_product']) && $variant['id_product'] != '0') {
                    $stock_list[] = [
                        'model_id' => (int)$variant['id_product'],
                        'seller_stock' => [
                            [
                                'stock' => (int)$stock
                            ]
                        ]
                    ];
                    break; 
                }
            }
        }
        
        if (empty($stock_list)) {
            $stock_list[] = [
                'seller_stock' => [
                    [
                        'stock' => (int)$stock
                    ]
                ]
            ];
        }
    
        $item_id = is_numeric($product['id_product']) ? (int)$product['id_product'] : 0;
        if ($item_id <= 0) {
            echo "Invalid product ID: " . $product['id_product'];
            return false;
        }
    
        $post_data = [
            'item_id' => $item_id,
            'stock_list' => $stock_list
        ];
    
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $host . $path . '?access_token=' . $config['access_token'] . '&partner_id=' . $partner_id . '&shop_id=' . $product['shop_id'] . '&sign=' . $sign . '&timestamp=' . $timest,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($post_data, JSON_NUMERIC_CHECK), // Gunakan JSON_NUMERIC_CHECK
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));
        
        $response = curl_exec($curl);
        
        if (curl_errno($curl)) {
            echo 'Curl error: ' . curl_error($curl);
        }
        
        curl_close($curl);
        
        return $response;
    }
    
    protected function updateTiktokStock($product, $stock) 
    {
        
        $config = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $product['shop_id']));
        if (!$config) return false;
        
        $config = json_decode($config['val'], true);
        $marketplace = "TIKTOK";
        $app_key = $config['app_key'];
        $access_token = $config['access_token'];
        $shop_cipher = $config['shop']['cipher'];
        $shop_id = $product['shop_id'];
        $app_secret = $this->app_secret_tiktok;
    
        $skus = [];
        $target_sku = $product['sku'];
        $variants = json_decode($product['json_varian'], true) ?? [];
    
        foreach ($variants as $variant) {
            if (isset($variant['sku']) && strtoupper(trim($variant['sku'])) === strtoupper(trim($target_sku))) {
                if (!empty($variant['id_product']) && $variant['id_product'] != '0') {
                    $skus[] = [
                        'id' => (string)$variant['id_product'],
                        'stock_infos' => [
                            [
                                'available_stock' => (int)$stock
                            ]
                        ]
                    ];
                    break;
                }
            }
        }
    
        if (empty($skus)) {
            $skus[] = [
                'id' => (string)$product['id_product'],
                'stock_infos' => [
                    [
                        'available_stock' => (int)$stock
                    ]
                ]
            ];
        }
    
        $post_data = [
            'product_id' => (string)$product['id_product'],
            'skus' => $skus
        ];
    
        $base_url = 'https://open-api.tiktokglobalshop.com/api/products/stocks';
        $url = $base_url . '?access_token=' . $access_token . 
               '&app_key=' . $app_key . 
               '&shop_cipher=' . $shop_cipher . 
               '&shop_id=' . $shop_id . 
               '&sign={{sign}}&timestamp={{timestamp}}&version=202212';
    
        $urlParts = parse_url($url);
        $paramGET = [];
        parse_str($urlParts['query'], $paramGET);
        
        $timest = time();
        $pr = [
            'secret' => $app_secret,
            'timest' => $timest,
            'get' => $paramGET,
            'url' => $base_url
        ];
        
        $sign = $this->tiktok_signature_generator($pr);
    
        $url = str_replace('{{sign}}', $sign, $url);
        $url = str_replace('{{timestamp}}', $timest, $url);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => json_encode($post_data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-tts-access-token: ' . $access_token
            ],
        ]);
    
        $response = curl_exec($curl);
    
        curl_close($curl);
    
        return $response;
    }

    
    protected function updateLazadaStock($product, $stock) 
    {
        $config = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $product['shop_id']));
        if (!$config) return false;
        
        $config = json_decode($config['val'], true);
        $access_token = $config['access_token'];
        $app_key = $this->app_key_lazada; 
        $app_secret = $this->app_secret_lazada; 
        $url = 'https://api.lazada.co.id/rest'; 
        
        $variants = json_decode($product['json_varian'], true) ?? [];
        
        $sku_payload = '';
        $target_sku = $product['sku']; 
        
        foreach ($variants as $variant) {
            if (isset($variant['sku']) && strtoupper(trim($variant['sku'])) === strtoupper(trim($target_sku))) {
                $sku_id = !empty($variant['id_product']) ? $variant['id_product'] : $product['id_product'];
                
                $sku_payload = '
                <Sku>
                    <ItemId>'.$product['id_product'].'</ItemId>
                    <SkuId>'.$sku_id.'</SkuId>
                    <SellerSku>'.htmlspecialchars($variant['sku']).'</SellerSku>
                    <SellableQuantity>'.(int)$stock.'</SellableQuantity>
                </Sku>';
                break; 
            }
        }
        
        if (empty($sku_payload)) {
            $sku_payload = '
            <Sku>
                <ItemId>'.$product['id_product'].'</ItemId>
                <SkuId>'.$product['id_product'].'</SkuId>
                <SellerSku>'.htmlspecialchars($product['sku']).'</SellerSku>
                <SellableQuantity>'.(int)$stock.'</SellableQuantity>
            </Sku>';
        }
    
        $xml_payload = '<Request>
            <Product>
                <Skus>
                    '.$sku_payload.'
                </Skus>
            </Product>
        </Request>';
        
        echo htmlspecialchars($xml_payload) . "\n";
    
        $c = new LazopClient($url, $app_key, $app_secret);
        $request = new LazopRequest('/product/stock/sellable/update');
        $request->addApiParam('payload', $xml_payload);
        
        $response = $c->execute($request, $access_token);
        $response = json_decode($response, true);
        return $response;
    }

    function marketplace_order_detail()
    {
        header('Content-Type: application/json; charset=utf-8');

        $is_configurated = 1;

        $dt = $_GET;
        $marketplace = $dt['marketplace'];
        $order_id = $dt['order_id'];
        $shop_id = $dt['shop_id'];
        $mode = $dt['mode'];

        $product = $this->mymodel->selectWithQuery("SELECT * FROM product
        ORDER BY sku ASC
        ");
        $arr_product = array();
        foreach ($product as $k => $v) {
            $arr_product[$v['id']] = $v;
        }

        if ($marketplace) {
            $this->db->where('marketplace', $marketplace);
        }

        $this->db->select('id,marketplace,order_id,shop_id,shop_name');
        $trx_existing = $this->mymodel->selectDataOne('transaction', array('order_id' => $order_id));

        $is_error = '';
        $msg = '';

        $trx = array();
        $trx['marketplace'] = $dt['marketplace'];
        $trx['order_id'] = $dt['order_id'];
        $trx['shop_id'] = $dt['shop_id'];

        if (empty($shop_id)) {
            $trx = $trx_existing;
        }

        $config = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $trx['shop_id']));
        $shop_name = $config['shop_name'];
        $shop_id = $config['shop_id'];

        if ($trx['marketplace'] == "TIKTOK") {
            $marketplace = $trx['marketplace'];
            $config = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $trx['shop_id']));
            $config = json_decode($config['val'], true);
            $app_key = $config['app_key'];
            $access_token = $config['access_token'];
            $shop_cipher = $config['shop']['cipher'];
            $app_secret = $this->app_secret_tiktok;

            $url = 'https://open-api.tiktokglobalshop.com/order/202309/orders?access_token=' . $access_token . '&app_key=' . $app_key . '&ids=' . $order_id . '&shop_cipher=' . $shop_cipher . '&shop_id=' . $shop_id . '&sign={{sign}}&timestamp={{timestamp}}&version=202309';

            $urlParts = parse_url($url);
            $paramGET = [];
            parse_str($urlParts['query'], $paramGET);
            $timest = strtotime('now');
            $pr = array();
            $pr['secret'] = $app_secret;
            $pr['timest'] = $timest;
            $pr['get'] = $paramGET;
            $pr['url'] = $url;
            $sign = $this->tiktok_signature_generator($pr);

            $url = str_replace('{{sign}}', $sign, $url);
            $url = str_replace('{{timestamp}}', $timest, $url);

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'x-tts-access-token: ' . $access_token
                ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);

            $response = json_decode($response, true);


            if (empty($response['data'])) {
                $html['status'] = false;
                $html['data'] = array();
                $html['msg'] = $response['message'];
                echo json_encode($html, true);
                die;
            }

            $v2 = $response['data']['orders'][0];

            $price_total_hpp = 0;

            $dt = array();
            $dt['type'] = "Out";
            $dt['type_sub'] = "POS";
            $dt['order_id'] = $order_id;
            $dt['shop_id'] = $shop_id;
            $dt['shop_name'] = $shop_name;
            $dt['marketplace'] = $marketplace;
            $dt['date'] = DATE("Y-m-d H:i:s", $v2['create_time']);
            $dt['shipping'] = strval($v2['shipping_provider']);
            $dt['awb_number'] = strval($v2['tracking_number']);
            
            if($v2['is_sample_order'] == true) {
                $dt['c_type'] = "Affiliate";
            } else {
                $dt['c_type'] = "Pelanggan";
            }
            
            $js = array();
            foreach ($v2['line_items'] as $k4 => $v4) {
                $js[$k4]['id_product'] = $v4['sku_id'];
                $js[$k4]['sku'] = $v4['seller_sku'];
                $name = $v4['sku_name'];
                if ($name == "Default") {
                    $name = "";
                }
                $js[$k4]['name'] = $name;
                $js[$k4]['id_product_parent'] = $v4['product_id'];
                $js[$k4]['sku_parent'] = "";
                $js[$k4]['name_parent'] = $v4['product_name'];
                $js[$k4]['qty'] = '1';
                $js[$k4]['price'] = $v4['sale_price'];
                $js[$k4]['original_price'] = $v4['original_price'];
                $js[$k4]['discount'] = $v4['seller_discount'];
            }
            


            $c_type['akun_type'] = "Pelanggan";

            $brand = array();
            $json = array();
            foreach ($js as $k4 => $v4) {
                $id_product = $v4['id_product'];
                $id_product_parent = $v4['id_product_parent'];
                $this->db->select('json');
                $conf = $this->mymodel->selectDataOne('product_variant_3rd', array('id_product' => $id_product, 'id_product_parent' => $id_product_parent));

                if (empty($conf) && $v4['sku']) {
                    $conf = $this->mymodel->selectDataOne('product_variant_3rd', array('sku' => $v4['sku']));
                }

                $conf = json_decode($conf['json'], true);
                if (empty($conf)) {
                    $js[$k4]['is_empty'] = true;
                    $is_configurated = 0;
                }
                foreach ($conf as $k5 => $v5) {
                    $product = $arr_product[$v5['product']];
                    $brand[$product['brand']] += 1;
                    $price = 0;
                    if ($dt['c_type'] == "Pelanggan") {
                        $price = $product['price_normal'];
                    } else if ($dt['c_type'] == "Distributor") {
                        $price = $product['price_distributor'];
                    } else if ($dt['c_type'] == "Reseller") {
                        $price = $product['price_reseller'];
                    } else {
                        $price = $product['price_normal'];
                    }
                    $json[$product['id']]['sku'] = $product['sku'];
                    $json[$product['id']]['hpp'] = $product['price_buy'];
                    $json[$product['id']]['product'] = $product['id'];
                    $json[$product['id']]['product_text'] = $product['name'];
                    $json[$product['id']]['product_sub'] = $product['sub_name'];
                    $json[$product['id']]['brand'] = $product['brand'];
                    $json[$product['id']]['price'] = $price;
                    $json[$product['id']]['qty'] += (doubleval($v5['qty']) * doubleval($v4['qty']));
                    $json[$product['id']]['price_total'] += (doubleval($json[$product['id']]['qty']) * doubleval($price));
                    $json[$product['id']]['price_total_hpp'] += (doubleval($json[$product['id']]['qty']) * doubleval($json[$product['id']]['hpp']));

                    $price_total_hpp += (doubleval($json[$product['id']]['qty']) * doubleval($json[$product['id']]['hpp']));
                }
            }

            $brand_selected = "MG";
            $arr_brand = array();
            foreach ($json as $k4 => $v4) {
                $arr_brand[$v4['brand']] += 1;
            }

            $max = 0;
            foreach ($arr_brand as $k => $v) {
                if ($v >= $max) {
                    $max = $v;
                    $brand_selected = $k;
                }
            }

            $dt['brand'] = $brand_selected;

            $dt['pesanan'] = json_encode($js, true);
            $dt['pesanan_count'] = count($js);

            $dt['hpp'] = doubleval($price_total_hpp);
            $dt['json'] = json_encode($json, true);

            if ($v2['is_cod'] == true) {
                $dt['payment_type'] = "COD";
            } else {
                $dt['payment_type'] = "TF";
            }
            if ($v2['rts_time']) {
                $dt['rts_at'] = strval(DATE("Y-m-d H:i:s", $v2['rts_time']));
            }
            if ($v2['paid_time']) {
                $dt['payment_status'] = "Paid";
                $dt['pay_at'] = strval(DATE("Y-m-d H:i:s", $v2['paid_time']));
            } else {
                $dt['payment_status'] = "Unpaid";
            }
            
            $order_status = "PENDING";
            $dt['is_shipped'] = 0;
            if (in_array($v2['status'], array('UNPAID'))) {
                $order_status = 'UNPAID';
            } else if (in_array($v2['status'], array('AWAITING_COLLECTION', 'ON_HOLD'))) {
                $order_status = 'PROCESSED';
            } else if (in_array($v2['status'], array('returned'))) {
                $order_status = 'RETURN';
            } else if (in_array($v2['status'], array('CANCELLED'))) {
                $order_status = 'CANCELLED';
            } else if (in_array($v2['status'], array('COMPLETED'))) {
                $order_status = 'COMPLETED';
            } else if (in_array($v2['status'], array('DELIVERED'))) {
                $order_status = 'DELIVERED';
            } else if (in_array($v2['status'], array('IN_TRANSIT', 'PARTIALLY_SHIPPING'))) {
                $order_status = 'SHIPPED';
                $dt['is_shipped'] = 1;
            } else if (in_array($v2['status'], array('AWAITING_SHIPMENT'))) {
                $order_status = 'READY_TO_SHIP';
            }
            
            $dt['order_status'] = $order_status;
            $dt['return_at'] = DATE("Y-m-d H:i:s", $v2['cancel_time']);

            if (in_array($dt['order_status'], array('DELIVERED', 'COMPLETED', 'CANCELLED'))) {

                $url = 'https://open-api.tiktokglobalshop.com/return_refund/202309/returns/search?access_token=' . $access_token . '&app_key=' . $app_key . '&shop_cipher=' . $shop_cipher . '&shop_id=' . $shop_id . '&sign={{sign}}&timestamp={{timestamp}}&version=202309';

                $urlParts = parse_url($url);
                $paramGET = [];
                parse_str($urlParts['query'], $paramGET);
                $timest = strtotime('now');
                $pr = array();
                $pr['secret'] = $app_secret;
                $pr['timest'] = $timest;
                $pr['get'] = $paramGET;
                $pr['post'] = '{"order_ids":["' . $order_id . '"]}';
                $pr['url'] = $url;
                $sign = $this->tiktok_signature_generator($pr);

                $url = str_replace('{{sign}}', $sign, $url);
                $url = str_replace('{{timestamp}}', $timest, $url);
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => $pr['post'],
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json',
                        'x-tts-access-token: ' . $access_token
                    ),
                ));


                $response = curl_exec($curl);
                $response = json_decode($response, true);

                // if ($response['data']['return_orders']) {
                //     $v3 = $response['data']['return_orders'][0];
                //     $dt['return_at'] = DATE("Y-m-d H:i:s", $v3['create_time']);
                //     $dt['order_status'] = "RETURN";
                // }
            }


            $this->db->select('id');
            $customer = $this->mymodel->selectDataOne('customer', array('id_buyer' => $dt['id_buyer'], 'marketplace' => $marketplace));
            if (empty($customer)) {
                $dt['customer_text'] = strval($v2['recipient_address']['name']);
                $dt['phone'] = strval($v2['recipient_address']['phone_number']);
                $dt['address'] = strval($v2['recipient_address']['full_address']);
                $dt['address_2'] = strval($v2['recipient_address']['full_address']);
                $dt['postal_code'] = strval($v2['recipient_address']['postal_code']);
                $dt['province_text'] = strval($v2['recipient_address']['district_info'][1]['address_name']);
                $dt['city_text'] = strval($v2['recipient_address']['district_info'][2]['address_name']);
                $dt['subdistrict_text'] = strval($v2['recipient_address']['district_info'][3]['address_name']);
            }

            $dt['id_buyer'] = $v2['user_id'];
            // $id_buyer = $dt['id_buyer'];
            // $customer = $this->mymodel->selectDataOne("customer", array('id_buyer' => $id_buyer, 'marketplace' => $marketplace));
            // print_r($customer);
            // print_r($dt);
            // die;



            $url = 'https://open-api.tiktokglobalshop.com/api/finance/order/settlements?access_token=' . $access_token . '&app_key=' . $app_key . '&order_id=' . $order_id . '&shop_cipher=' . $shop_cipher . '&shop_id=' . $shop_id . '&sign={{sign}}&timestamp={{timestamp}}&version=202212';

            $urlParts = parse_url($url);
            $paramGET = [];
            parse_str($urlParts['query'], $paramGET);
            $timest = strtotime('now');
            $pr = array();
            $pr['secret'] = $app_secret;
            $pr['timest'] = $timest;
            $pr['get'] = $paramGET;
            $pr['url'] = $url;
            $sign = $this->tiktok_signature_generator($pr);

            $url = str_replace('{{sign}}', $sign, $url);
            $url = str_replace('{{timestamp}}', $timest, $url);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'x-tts-access-token: ' . $access_token
                ),
            ));

            $response = curl_exec($curl);
            
            $response = json_decode($response, true);
            if ($response['message'] != 'Success') {
                $html['status'] = false;
                $html['data'] = array();
                $html['msg'] = $response['message'];
                echo json_encode($html, true);
                die;
            }

            $payment = $response['data']['settlement_list'][0]['settlement_info'];


            $dt['customer_price'] = doubleval($v2['payment']['total_amount']);
            $dt['omset_kotor'] = doubleval($v2['payment']['original_total_product_price']);
            $dt['omset_bersih'] = doubleval($v2['payment']['original_total_product_price'] - $v2['payment']['seller_discount']);
            $dt['diskon_penjual'] = doubleval($v2['payment']['seller_discount']);

            if ($payment['settlement_amount']) {
                $dt['komisi_afiliasi'] = doubleval($payment['affiliate_commission']);
                // $dt['omset_kotor'] = doubleval($v['price_total']);
                // $dt['diskon_penjual'] = abs(doubleval($payment['subtotal_after_seller_discounts']) - doubleval($v['price_total']));
                $dt['omset_bersih'] = doubleval($payment['subtotal_after_seller_discounts']);
                $dt['marketplace_fee'] = doubleval($payment['platform_commission']) + doubleval($payment['sfp_service_fee']);
                $dt['dana_pencairan'] = doubleval($payment['settlement_amount']);
                $dt['pencairan_status'] = '';
                $dt['pencairan_at'] = '';
                if ($payment['settlement_time']) {
                    $dt['pencairan_status'] = 'Settlement';
                    $dt['pencairan_at'] = DATE("Y-m-d H:i:s", ($payment['settlement_time']));
                }
            }

            if ($dt['marketplace_fee'] == 0) {
                $channel = $this->mymodel->selectDataOne('marketplace', array('name' => $marketplace));
                $fee_json = json_decode($channel['configuration'], true);
                $fee = array();
                foreach ($fee_json as $kk => $vv) {
                    if (DATE("Y-m-d", strtotime($dt['date'])) >= $vv['date']) {
                        $fee = $vv;
                    } else {
                        break;
                    }
                }
                $marketplace_fee = 0;
                if ($fee['type'] == "Persentase") {
                    if ($fee['fee'] > 0) {
                        $marketplace_fee = doubleval($dt['omset_bersih']) * $fee['fee'] / 100;
                    }
                } else {
                    $marketplace_fee = $fee['fee'];
                }
                $dt['marketplace_fee'] = $marketplace_fee;
            }

            // print_r($dt);
            // print_r($v2);
            // echo ' --- ';
            // print_r($payment);

            // die;

            $dt['updated_at'] = DATE("Y-m-d H:i:s");
            $dt['is_webhook'] = 1;

            if ($mode == "webhook") {
                $dtt = array();
                $dtt['order_date'] = $dt['date'];
                $this->db->update('webhook', $dtt, array('order_id' => $order_id));
            }
        } else if ($trx['marketplace'] == "SHOPEE") {
            $marketplace = $trx['marketplace'];
            $config = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $trx['shop_id']));
            $config = json_decode($config['val'], true);
            $app_key = $config['app_key'];
            $access_token = $config['access_token'];
            $shop_cipher = $config['shop']['cipher'];
            $partner_id = $this->partner_id_shopee;
            $partner_key = $this->partner_key_shopee;
            $host = 'https://partner.shopeemobile.com';

            $path = "/api/v2/order/get_order_detail";
            $timest = time();
            $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
            $sign = hash_hmac('sha256', $baseString, $partner_key);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $host . $path . '?access_token=' . $config['access_token'] . '&order_sn_list=' . $order_id . '&response_optional_fields=buyer_user_id,buyer_username,estimated_shipping_fee,recipient_address,actual_shipping_fee,goods_to_declare,note,note_update_time,item_list,pay_time,dropshipper,dropshipper_phone,split_up,buyer_cancel_reason,cancel_by,cancel_reason,actual_shipping_fee_confirmed,buyer_cpf_id,fulfillment_flag,pickup_done_time,package_list,shipping_carrier,payment_method,total_amount,buyer_username,invoice_data,no_plastic_packing,order_chargeable_weight_gram,edt,return_due_date&request_order_status_pending=true&partner_id=' . $partner_id . '&shop_id=' . $shop_id . '&sign=' . $sign . '&timestamp=' . $timest . '',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            $response = json_decode($response, true);
            if (empty($response['response']['order_list'][0])) {
                $html['status'] = false;
                $html['data'] = array();
                $html['msg'] = $response['message'];
                echo json_encode($html, true);
                die;
            }

            $v2 = $response['response']['order_list'][0];

            $price_total_hpp = 0;

            $dt = array();
            $dt['type'] = "Out";
            $dt['type_sub'] = "POS";
            $dt['order_id'] = $order_id;
            $dt['shop_id'] = $shop_id;
            $dt['shop_name'] = $shop_name;
            $dt['marketplace'] = $marketplace;
            $dt['date'] = DATE("Y-m-d H:i:s", $v2['create_time']);
            $dt['shipping'] = strval($v2['shipping_carrier']);
            $js = array();
            foreach ($v2['item_list'] as $k4 => $v4) {
                $js[$k4]['id_product'] = $v4['model_id'];
                $js[$k4]['sku'] = $v4['model_sku'];
                $js[$k4]['name'] = $v4['model_name'];
                $js[$k4]['id_product_parent'] = $v4['item_id'];
                $js[$k4]['sku_parent'] = $v4['item_sku'];
                $js[$k4]['name_parent'] = $v4['item_name'];
                $js[$k4]['qty'] = intval($v4['model_quantity_purchased']);
                $js[$k4]['price'] = intval($v4['model_discounted_price']);
                $js[$k4]['original_price'] = intval($v4['model_original_price']);
                $js[$k4]['discount'] = intval($v4['model_original_price'] - $v4['model_discounted_price']);
            }




            $c_type['akun_type'] = "Pelanggan";
            $dt['c_type'] = "Pelanggan";

            $brand = array();
            $json = array();
            foreach ($js as $k4 => $v4) {
                $id_product = $v4['id_product'];
                $id_product_parent = $v4['id_product_parent'];
                $this->db->select('json');
                $conf = $this->mymodel->selectDataOne('product_variant_3rd', array('id_product' => $id_product, 'id_product_parent' => $id_product_parent));

                if (empty($conf) && $v4['sku']) {
                    $conf = $this->mymodel->selectDataOne('product_variant_3rd', array('sku' => $v4['sku']));
                }

                $conf = json_decode($conf['json'], true);
                if (empty($conf)) {
                    $js[$k4]['is_empty'] = true;
                    $is_configurated = 0;
                }
                foreach ($conf as $k5 => $v5) {
                    $product = $arr_product[$v5['product']];
                    $price = 0;
                    if ($dt['c_type'] == "Pelanggan") {
                        $price = $product['price_normal'];
                    } else if ($dt['c_type'] == "Distributor") {
                        $price = $product['price_distributor'];
                    } else if ($dt['c_type'] == "Reseller") {
                        $price = $product['price_reseller'];
                    } else {
                        $price = $product['price_normal'];
                    }
                    $json[$product['id']]['sku'] = $product['sku'];
                    $json[$product['id']]['hpp'] = $product['price_buy'];
                    $json[$product['id']]['product'] = $product['id'];
                    $json[$product['id']]['product_text'] = $product['name'];
                    $json[$product['id']]['product_sub'] = $product['sub_name'];
                    $json[$product['id']]['brand'] = $product['brand'];
                    $json[$product['id']]['price'] = $price;
                    $json[$product['id']]['qty'] += (doubleval($v5['qty']) * doubleval($v4['qty']));
                    $json[$product['id']]['price_total'] += (doubleval($json[$product['id']]['qty']) * doubleval($price));
                    $json[$product['id']]['price_total_hpp'] += (doubleval($json[$product['id']]['qty']) * doubleval($json[$product['id']]['hpp']));

                    $price_total_hpp += (doubleval($json[$product['id']]['qty']) * doubleval($json[$product['id']]['hpp']));
                }
            }


            $brand_selected = "MG";
            $arr_brand = array();
            foreach ($json as $k4 => $v4) {
                $arr_brand[$v4['brand']] += 1;
            }

            $max = 0;
            foreach ($arr_brand as $k => $v) {
                if ($v >= $max) {
                    $max = $v;
                    $brand_selected = $k;
                }
            }

            $dt['brand'] = $brand_selected;

            $dt['pesanan'] = json_encode($js, true);
            $dt['pesanan_count'] = count($js);

            $dt['hpp'] = doubleval($price_total_hpp);
            $dt['json'] = json_encode($json, true);

            if ($v2['cod'] == true) {
                $dt['payment_type'] = "COD";
            } else {
                $dt['payment_type'] = "TF";
            }

            $path = "/api/v2/logistics/get_tracking_info";
            $timest = time();
            $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
            $sign = hash_hmac('sha256', $baseString, $partner_key);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $host . $path . '?partner_id=' . $partner_id . '&order_sn=' . $order_id . '&access_token=' . $access_token . '&timestamp=' . $timest . '&sign=' . $sign . '&shop_id=' . $shop_id,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ));

            $response_shipping = curl_exec($curl);

            $response_shipping = json_decode($response_shipping, true);

            curl_close($curl);

            $dt['rts_at'] = "";
            $dt['is_shipped'] = 0;
            foreach ($response_shipping['response']['tracking_info'] as $k3 => $v3) {
                if ($v3['logistics_status'] == "ORDER_CREATED") {
                    $dt['rts_at'] = DATE("Y-m-d H:i:s", $v3['update_time']);
                } else if ($v3['logistics_status'] == "PICKED_UP") {
                    $dt['is_shipped'] = 1;
                }
            }

            if ($v2['pay_time']) {
                $dt['payment_status'] = "Paid";
                $dt['pay_at'] = strval(DATE("Y-m-d H:i:s", $v2['pay_time']));
            } else {
                $dt['payment_status'] = "Unpaid";
            }

            $dt['order_status'] = $v2['order_status'];
            $dt['id_buyer'] = strval($v2['buyer_user_id']);


            $this->db->select('id');
            $customer = $this->mymodel->selectDataOne('customer', array('id_buyer' => $dt['id_buyer'], 'marketplace' => $marketplace));
            if (empty($customer)) {
                $dt['c_username'] = strval($v2['buyer_username']);
                $dt['customer_text'] = strval($v2['recipient_address']['name']);
                $dt['phone'] = strval($v2['recipient_address']['phone']);
                $dt['address'] = strval($v2['recipient_address']['full_address']);
                $dt['address_2'] = strval($v2['recipient_address']['full_address']);
                $dt['postal_code'] = strval($v2['recipient_address']['zipcode']);
                $dt['province_text'] = strval($v2['recipient_address']['state']);
                $dt['city_text'] = strval($v2['recipient_address']['city']);
                $dt['subdistrict_text'] = strval($v2['recipient_address']['district']);
            }



            $order_status = $v2['order_status'];
            if ($v2['order_status'] == "TO_CONFIRM_RECEIVE") {
                $order_status = "DELIVERED";
            } else if ($v2['order_status'] == "TO_RETURN") {
                $order_status = "RETURN";
            } else if ($v2['order_status'] == "RETRY_SHIP") {
                $order_status = "PROCESSED";
            }

            // if (in_array($v2['order_status'], array('CANCELLED')) && $v2['pickup_done_time']) {
            //     foreach ($response_shipping['response']['tracking_info'] as $k3 => $v3) {
            //         if (in_array($v3['logistics_status'], array('RETURNED', 'RETURN'))) {
            //             $dt['return_at'] = DATE("Y-m-d H:i:s", $v3['update_time']);
            //             break;
            //         }
            //     }
            //     $dt['order_status'] = "RETURN";
            // }


            $curl = curl_init();

            $path = "/api/v2/logistics/get_tracking_number";
            $timest = time();
            $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
            $sign = hash_hmac('sha256', $baseString, $partner_key);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $host . $path . '?access_token=' . $access_token . '&order_sn=' . $order_id . '&partner_id=' . $partner_id . '&shop_id=' . $shop_id . '&sign=' . $sign . '&timestamp=' . $timest . '',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ));

            $response_awb = curl_exec($curl);
            $response_awb = json_decode($response_awb, true);
            curl_close($curl);

            $dt['awb_number'] = strval($response_awb['response']['tracking_number']);


            $path = "/api/v2/payment/get_escrow_detail";
            $timest = time();
            $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
            $sign = hash_hmac('sha256', $baseString, $partner_key);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $host . $path . '?access_token=' . $config['access_token'] . '&order_sn=' . $order_id . '&response_optional_fields=buyer_user_id,buyer_username,estimated_shipping_fee,recipient_address,actual_shipping_fee,goods_to_declare,note,note_update_time,item_list,pay_time,dropshipper,dropshipper_phone,split_up,buyer_cancel_reason,cancel_by,cancel_reason,actual_shipping_fee_confirmed,buyer_cpf_id,fulfillment_flag,pickup_done_time,package_list,shipping_carrier,payment_method,total_amount,buyer_username,invoice_data,no_plastic_packing,order_chargeable_weight_gram,edt,return_due_date&request_order_status_pending=true&partner_id=' . $partner_id . '&shop_id=' . $shop_id . '&sign=' . $sign . '&timestamp=' . $timest . '',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ));

            $response = curl_exec($curl);
            // print_r($response);

            curl_close($curl);
            $response = json_decode($response, true);


            $omset_kotor = 0;
            foreach ($v2['item_list'] as $kk => $vv) {
                $omset_kotor += $vv['model_quantity_purchased'] * $vv['model_original_price'];
            }
            $dt['omset_kotor'] = doubleval($omset_kotor);
            $detail = $response['response']['order_income'];
            if ($detail['escrow_amount']) {
                $dt['komisi_afiliasi'] = doubleval($detail['order_ams_commission_fee']);
                $dt['omset_kotor'] = doubleval($dt['omset_kotor']);
                $dt['diskon_penjual'] = doubleval($detail['seller_discount']);
                $dt['omset_bersih'] = doubleval($dt['omset_kotor']) - doubleval($detail['seller_discount']);
                $dt['marketplace_fee'] = doubleval($detail['commission_fee']) + doubleval($detail['service_fee']);

                $dt['pencairan_status'] = '';
                $dt['pencairan_at'] = '';
                $dt['dana_pencairan'] = '';
                if (in_array($dt['order_status'], array('COMPLETED'))) {
                    $dt['dana_pencairan'] = doubleval($detail['escrow_amount']);
                    if ($dt['dana_pencairan']) {
                        $dt['pencairan_status'] = 'Settlement';
                        // $dt['pencairan_at'] = DATE("Y-m-d H:i:s", ($detail['settlement_time']));
                    }
                }
            }

            if (in_array($dt['order_status'], array("CANCELLED"))) {
                $dt['cancel_at'] = DATE("Y-m-d H:i:s", $v2['update_time']);
            }

            $dt['customer_price'] = $v2['total_amount'];
            $dt['updated_at'] = DATE("Y-m-d H:i:s");
            $dt['is_webhook'] = 1;

            if ($mode == "webhook") {
                $dtt = array();
                $dtt['order_date'] = $dt['date'];
                $this->db->update('webhook', $dtt, array('order_id' => $order_id));
            }
        } else if ($trx['marketplace'] == "LAZADA") {
            $marketplace = $trx['marketplace'];
            $config = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $trx['shop_id']));
            $config = json_decode($config['val'], true);
            $access_token = $config['access_token'];
            $shop_cipher = $config['shop']['cipher'];

            $app_key = $this->app_key_lazada;
            $app_secret = $this->app_secret_lazada;
            $url = 'https://api.lazada.co.id/rest';
            $page_size = 100;
            $cursor = '';


            $c = new LazopClient($url, $app_key, $app_secret);
            $request = new LazopRequest('/order/get', 'GET');
            $request->addApiParam('order_id', $order_id);

            $response = $c->execute($request, $access_token);
            $response = json_decode($response, true);

            if ($response['data']) {
                $v2 = $response['data'];

                $c = new LazopClient($url, $app_key, $app_secret);
                $request = new LazopRequest('/order/items/get', 'GET');
                $request->addApiParam('order_id', $order_id);
                $response_item = $c->execute($request, $access_token);
                $response_item = json_decode($response_item, true);

                $price_total_hpp = 0;

                $dt = array();
                $dt['type'] = "Out";
                $dt['type_sub'] = "POS";
                $dt['order_id'] = $order_id;
                $dt['shop_id'] = $shop_id;
                $dt['shop_name'] = $shop_name;
                $dt['marketplace'] = $marketplace;
                $dt['date'] = DATE("Y-m-d H:i:s", strtotime(strval(substr($v2['created_at'], 0, 19))));
                $dt['id_buyer'] = strval($response_item['data'][0]['buyer_id']);

                $this->db->select('id');
                $customer = $this->mymodel->selectDataOne('customer', array('id_buyer' => $dt['id_buyer'], 'marketplace' => $marketplace));
                if (empty($customer)) {
                    $dt['customer_text'] = strval($v2['customer_first_name']);
                    $dt['phone'] = strval($v2['address_shipping']['phone']);
                    $dt['address'] = strval($v2['address_shipping']['address1']);
                    $dt['address_2'] = strval($v2['address_shipping']['address1']);
                    $dt['postal_code'] = strval($v2['address_shipping']['post_code']);
                    $dt['province_text'] = strval($v2['address_shipping']['address3']);
                    $dt['city_text'] = strval($v2['address_shipping']['city']);
                    $dt['subdistrict_text'] = strval($v2['address_shipping']['address2']);
                }
                if ($v2['payment_method'] == "COD") {
                    $dt['payment_type'] = "COD";
                } else {
                    $dt['payment_type'] = "TF";
                }
                $dt['customer_price'] = $v2['price'] + $v2['shipping_fee'] - $v2['voucher_seller'] - $v2['voucher_platform'];
                $segments = explode(',', $response_item['data'][0]['shipment_provider']);
                $segments = explode(': ', $segments[0]);
                $shipment_provider = $segments[1];
                if (empty($shipment_provider)) {
                    $shipment_provider = $segments[0];
                }
                $dt['shipping'] = strval($shipment_provider);
                $dt['awb_number'] = strval($response_item['data'][0]['tracking_code']);

                $c = new LazopClient($url, $app_key, $app_secret);
                $request = new LazopRequest('/logistic/order/trace');
                $request->addApiParam('order_id', $order_id);
                $response_shipping = $c->execute($request, $access_token);
                $response_shipping = json_decode($response_shipping, true);

                $dt['rts_at'] = "";
                foreach ($response_shipping['result']['module'][0]['package_detail_info_list'][0]['logistic_detail_info_list'] as $k3 => $v3) {
                    if ($v3['detail_type'] == "ready_to") {
                        $dt['rts_at'] = DATE("Y-m-d H:i:s", (substr($v3['event_time'], 0, 10)));
                    }
                }
                if ($response_item['data'][0]['payment_time']) {
                    $dt['payment_status'] = strval("Paid");
                    $dt['pay_at'] = DATE("Y-m-d H:i:s", (substr($response_item['data'][0]['payment_time'], 0, 10)));
                } else {
                    $dt['payment_status'] = strval("Unpaid");
                }
                $js = array();
                // print_r($response_item['data']);
                foreach ($response_item['data'] as $k4 => $v4) {
                    $js[$k4]['id_product'] = $v4['sku_id'];
                    $js[$k4]['sku'] = $v4['sku'];
                    $parts = explode(":", $v4['variation']);
                    $v4['variation'] = $parts[1];
                    if (empty($v4['variation'])) {
                        $v4['variation'] = $parts[0];
                    }
                    if (empty($v4['variation'])) {
                        $v4['variation'] = $v4['name'];
                    }
                    $js[$k4]['name'] = strval($v4['variation']);
                    $js[$k4]['id_product_parent'] = $v4['product_id'];
                    $js[$k4]['sku_parent'] = "";
                    $js[$k4]['name_parent'] = $v4['name'];
                    $js[$k4]['qty'] = '1';
                    $js[$k4]['price'] = intval($v4['item_price']);
                    $js[$k4]['original_price'] = intval($v4['item_price']);
                    $js[$k4]['discount'] = intval($v4['model_original_price'] - $v4['model_discounted_price']);
                    // print_r($v4);
                }


                $c_type['akun_type'] = "Pelanggan";

                $brand = array();
                $json = array();
                foreach ($js as $k4 => $v4) {
                    $id_product = $v4['id_product'];
                    $id_product_parent = $v4['id_product_parent'];
                    $this->db->select('json');
                    $conf = $this->mymodel->selectDataOne('product_variant_3rd', array('id_product' => $id_product, 'id_product_parent' => $id_product_parent));

                    if (empty($conf) && $v4['sku']) {
                        $conf = $this->mymodel->selectDataOne('product_variant_3rd', array('sku' => $v4['sku']));
                    }

                    $conf = json_decode($conf['json'], true);
                    if (empty($conf)) {
                        $js[$k4]['is_empty'] = true;
                        $is_configurated = 0;
                    }
                    foreach ($conf as $k5 => $v5) {
                        $product = $arr_product[$v5['product']];
                        $price = 0;
                        if ($dt['c_type'] == "Pelanggan") {
                            $price = $product['price_normal'];
                        } else if ($dt['c_type'] == "Distributor") {
                            $price = $product['price_distributor'];
                        } else if ($dt['c_type'] == "Reseller") {
                            $price = $product['price_reseller'];
                        } else {
                            $price = $product['price_normal'];
                        }
                        $json[$product['id']]['sku'] = $product['sku'];
                        $json[$product['id']]['hpp'] = $product['price_buy'];
                        $json[$product['id']]['product'] = $product['id'];
                        $json[$product['id']]['product_text'] = $product['name'];
                        $json[$product['id']]['product_sub'] = $product['sub_name'];
                        $json[$product['id']]['brand'] = $product['brand'];
                        $json[$product['id']]['price'] = $price;
                        $json[$product['id']]['qty'] += (doubleval($v5['qty']) * doubleval($v4['qty']));
                        $json[$product['id']]['price_total'] += (doubleval($json[$product['id']]['qty']) * doubleval($price));
                        $json[$product['id']]['price_total_hpp'] += (doubleval($json[$product['id']]['qty']) * doubleval($json[$product['id']]['hpp']));

                        $price_total_hpp += (doubleval($json[$product['id']]['qty']) * doubleval($json[$product['id']]['hpp']));
                    }
                }

                $brand_selected = "MG";
                $arr_brand = array();
                foreach ($json as $k4 => $v4) {
                    $arr_brand[$v4['brand']] += 1;
                }

                $max = 0;
                foreach ($arr_brand as $k => $v) {
                    if ($v >= $max) {
                        $max = $v;
                        $brand_selected = $k;
                    }
                }

                $dt['brand'] = $brand_selected;

                $dt['pesanan'] = json_encode($js, true);
                $dt['pesanan_count'] = count($js);

                $dt['hpp'] = doubleval($price_total_hpp);
                $dt['json'] = json_encode($json, true);
                $order_status = "COMPLETED";
                if (in_array($v2['statuses'][0], array('unpaid'))) {
                    $order_status = 'UNPAID';
                } else if (in_array($v2['statuses'][0], array('topack', 'pending'))) {
                    $order_status = 'PROCESSED';
                } else if (in_array($v2['statuses'][0], array('returned', 'shipped_back_success'))) {
                    $order_status = 'RETURN';
                } else if (in_array($v2['statuses'][0], array('canceled', 'failed', 'lost'))) {
                    $order_status = 'CANCELLED';
                } else if (in_array($v2['statuses'][0], array('confirmed'))) {
                    $order_status = 'COMPLETED';
                    $dt['disbursement_at'] = '';
                    $dt['is_disbursement'] = '1';
                } else if (in_array($v2['statuses'][0], array('delivered'))) {
                    $order_status = 'DELIVERED';
                } else if (in_array($v2['statuses'][0], array('shipped'))) {
                    $order_status = 'SHIPPED';
                    $dt['is_shipped'] = 1;
                } else if (in_array($v2['statuses'][0], array('ready_to_ship', 'toship', 'shipping'))) {
                    $order_status = 'READY_TO_SHIP';
                }

                $dt['order_status'] = $order_status;
                

                // foreach ($response_shipping['result']['module'][0]['package_detail_info_list'][0]['logistic_detail_info_list'] as $k3 => $v3) {
                //     if ($v3['status_code'] == '1420') {
                //         $dt['order_status']  = "RETURN";
                //         $dt['return_at'] = DATE("Y-m-d H:i:s", substr($v2['event_time'], 0, 10));
                //     }
                // }


                // if (in_array($dt['order_status'], array('COMPLETED'))) {
                $start_date = date("Y-m-01", strtotime($dt['date']));
                $until_date = date("Y-m-t", strtotime($start_date . " +1 months"));
                $c = new LazopClient($url, $app_key, $app_secret);
                $request = new LazopRequest('/finance/transaction/details/get', 'GET');
                $request->addApiParam('offset', '0');
                $request->addApiParam('trade_order_id', $order_id);
                $request->addApiParam('limit', '100');
                $request->addApiParam('start_time', $start_date);
                $request->addApiParam('end_time', $until_date);
                $response_vat = $c->execute($request, $config['access_token']);
                // print_r($response_vat);

                $response_vat = json_decode($response_vat, true);
                $price_admin = 0;
                $price_total = 0;
                $diskon_penjual = 0;
                foreach ($response_vat['data'] as $kk => $vv) {
                    if (in_array($vv['fee_type'], array('118', '306'))) {
                        $diskon_penjual += doubleval(str_replace('-', '', str_replace(',', '', $vv['amount'])));
                    }
                    if (in_array($vv['transaction_type'], array('Orders-Sales'))) {
                        $price_total += doubleval(str_replace('-', '', str_replace(',', '', $vv['amount'])));
                    }
                    if (in_array($vv['fee_type'], array('298', '16', '3'))) {
                        $price_admin += doubleval(str_replace('-', '', str_replace(',', '', $vv['amount'])));
                    }
                    if ($vv['transaction_date']) {
                        $dt['pencairan_at'] = DATE("Y-m-d 00:00:01", strtotime($vv['transaction_date']));
                    }
                }
                // print_r($v2);
                if (empty($price_total)) {
                    $price_total = $v2['price'];
                    $dt['customer_price'] = $v2['price'];
                }
                if (doubleval($price_total - $price_admin - $diskon_penjual) > 0) {
                    $dt['komisi_afiliasi'] = doubleval(0);
                    $dt['omset_kotor'] = doubleval($price_total);
                    $dt['diskon_penjual'] = doubleval($diskon_penjual);
                    $dt['omset_bersih'] = doubleval($price_total) - doubleval($diskon_penjual);
                    $dt['marketplace_fee'] = doubleval($price_admin);
                    $dt['dana_pencairan'] = doubleval($price_total - $price_admin - $diskon_penjual);
                    $dt['pencairan_status'] = '';
                    // $dt['pencairan_at'] = '';
                    if ($dt['dana_pencairan']) {
                        $dt['pencairan_status'] = 'Settlement';
                        // $dt['pencairan_at'] = DATE("Y-m-d H:i:s", ($detail['settlement_time']));
                    }
                }
                // }

                if ($dt['marketplace_fee'] == 0) {
                    $channel = $this->mymodel->selectDataOne('marketplace', array('name' => $marketplace));
                    $fee_json = json_decode($channel['configuration'], true);
                    $fee = array();
                    foreach ($fee_json as $kk => $vv) {
                        if (DATE("Y-m-d", strtotime($dt['date'])) >= $vv['date']) {
                            $fee = $vv;
                        } else {
                            break;
                        }
                    }
                    $marketplace_fee = 0;
                    if ($fee['type'] == "Persentase") {
                        if ($fee['fee'] > 0) {
                            $marketplace_fee = doubleval($dt['customer_price']) * $fee['fee'] / 100;
                        }
                    } else {
                        $marketplace_fee = $fee['fee'];
                    }
                    $dt['marketplace_fee'] = $marketplace_fee;
                }

                // print_r($dt);
                // print_r($v2);
                // print_r($response_vat['data']);

                $dt['updated_at'] = DATE("Y-m-d H:i:s");
                $dt['is_webhook'] = 1;
                if ($mode == "webhook") {
                    $dtt = array();
                    $dtt['order_date'] = $dt['date'];
                    $this->db->update('webhook', $dtt, array('order_id' => $order_id));
                }
            } else {
                $is_error = true;
            }
        }
        if ($v2) {
            if ($trx_existing) {
                $dt['is_configurated'] = $is_configurated;
                // if ($dt['order_status'] == 'CANCELLED' && $dt['is_shipped'] == 0) {
                //     $this->db->delete('transaction', array('id' => $trx_existing['id']));
                // }
                $this->db->update('transaction', $dt, array('id' => $trx_existing['id']));
                $dt['id'] = $trx_existing['id'];
                $dt['stock'] = $json;
                $dt['stock_product_3rd'] = $js;
                $this->calculate_stock($dt);
                $this->calculate_buyer($dt);
                $html['status'] = true;
                $html['data'] = array();
                $html['msg'] = 'Data ' . $order_id . ' berhasil diperbarui!';
                echo json_encode($html, true);
                die;
            } else {
                $dt['is_configurated'] = $is_configurated;
                $this->db->insert('transaction', $dt);
                $dt['id'] = $this->db->insert_id();
                $dt['stock'] = $json;
                $dt['stock_product_3rd'] = $js;
                $this->calculate_stock($dt);
                $this->calculate_buyer($dt);
                $html['status'] = true;
                $html['data'] = array();
                $html['msg'] = 'Data ' . $order_id . ' berhasil ditambahkan!';
                echo json_encode($html, true);
                die;
            }
        } else if (empty($trx)) {
            $html['status'] = false;
            $html['data'] = array();
            $html['msg'] = 'Data tidak ditemukan!';
            echo json_encode($html, true);
            die;
        } else {
            $html['status'] = false;
            $html['data'] = array();
            $html['msg'] = 'Koneksi marketplace bermasalah!';
            echo json_encode($html, true);
            die;
        }
    }

    function marketplace_order_tracking()
    {
        header('Content-Type: application/json; charset=utf-8');

        $dt = $_GET;
        $marketplace = $dt['marketplace'];
        $order_id = $dt['order_id'];
        $mode = $dt['mode'];

        $product = $this->mymodel->selectWithQuery("SELECT * FROM product
        ORDER BY sku ASC
        ");
        $arr_product = array();
        foreach ($product as $k => $v) {
            $arr_product[$v['id']] = $v;
        }

        if ($marketplace) {
            $this->db->where('marketplace', $marketplace);
        }
        $trx = $this->mymodel->selectDataOne('transaction', array('order_id' => $order_id, 'is_manual' => '0'));
        $is_error = '';
        $msg = '';
        if ($trx) {
            if ($trx['marketplace'] == "TIKTOK") {
                $marketplace = $trx['marketplace'];
                $config = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $trx['shop_id']));
                $config = json_decode($config['val'], true);
                $app_key = $config['app_key'];
                $access_token = $config['access_token'];
                $shop_cipher = $config['shop']['cipher'];
                $shop_id = $trx['shop_id'];
                $shop_name = $trx['shop_name'];
                $app_secret = $this->app_secret_tiktok;

                $url = 'https://open-api.tiktokglobalshop.com/fulfillment/202309/orders/' . $order_id . '/tracking?access_token=' . $access_token . '&app_key=' . $app_key . '&shop_cipher=' . $shop_cipher . '&shop_id=' . $shop_id . '&sign={{sign}}&timestamp={{timestamp}}&version=202309';

                $urlParts = parse_url($url);
                $paramGET = [];
                parse_str($urlParts['query'], $paramGET);
                $timest = strtotime('now');
                $pr = array();
                $pr['secret'] = $app_secret;
                $pr['timest'] = $timest;
                $pr['get'] = $paramGET;
                $pr['url'] = $url;
                $sign = $this->tiktok_signature_generator($pr);

                $url = str_replace('{{sign}}', $sign, $url);
                $url = str_replace('{{timestamp}}', $timest, $url);

                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => array(
                        'x-tts-access-token: ' . $access_token
                    ),
                ));

                $response = curl_exec($curl);

                curl_close($curl);

                $response = json_decode($response, true);
                if (empty($response['data']['tracking'])) {
                    $html['status'] = false;
                    $html['data'] = array();
                    $html['msg'] = 'Data belum tersedia!';
                    echo json_encode($html, true);
                    die;
                }
                $arr = array();
                foreach ($response['data']['tracking'] as $k2 => $v2) {
                    $arr[$k2]['title'] = $v2['title'];
                    $arr[$k2]['description'] = $v2['description'];
                    $arr[$k2]['datetime'] = DATE("Y-m-d H:i:s", substr($v2['update_time_millis'], 0, 10));
                }
            } else if ($trx['marketplace'] == "SHOPEE") {
                $marketplace = $trx['marketplace'];
                $config = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $trx['shop_id']));
                $config = json_decode($config['val'], true);
                $app_key = $config['app_key'];
                $access_token = $config['access_token'];
                $shop_cipher = $config['shop']['cipher'];
                $shop_id = $trx['shop_id'];
                $shop_name = $trx['shop_name'];
                $partner_id = $this->partner_id_shopee;
                $partner_key = $this->partner_key_shopee;
                $host = 'https://partner.shopeemobile.com';
                $path = "/api/v2/logistics/get_tracking_info";
                $timest = time();
                $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
                $sign = hash_hmac('sha256', $baseString, $partner_key);
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $host . $path . '?partner_id=' . $partner_id . '&order_sn=' . $order_id . '&access_token=' . $access_token . '&timestamp=' . $timest . '&sign=' . $sign . '&shop_id=' . $shop_id,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                ));

                $response = curl_exec($curl);

                curl_close($curl);
                $response = json_decode($response, true);
                if (empty($response['response']['tracking_info'])) {
                    $html['status'] = false;
                    $html['data'] = array();
                    $html['msg'] = 'Data belum tersedia!';
                    echo json_encode($html, true);
                    die;
                }
                $arr = array();
                foreach ($response['response']['tracking_info'] as $k2 => $v2) {
                    $arr[$k2]['title'] = $v2['logistics_status'];
                    $arr[$k2]['description'] = $v2['description'];
                    $arr[$k2]['datetime'] = DATE("Y-m-d H:i:s", $v2['update_time']);
                }
            } else if ($trx['marketplace'] == "LAZADA") {
                $marketplace = $trx['marketplace'];
                $config = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $trx['shop_id']));
                $config = json_decode($config['val'], true);
                $access_token = $config['access_token'];
                $shop_cipher = $config['shop']['cipher'];
                $shop_id = $trx['shop_id'];
                $shop_name = $trx['shop_name'];
                $app_key = $this->app_key_lazada;
                $app_secret = $this->app_secret_lazada;
                $url = 'https://api.lazada.co.id/rest';
                $page_size = 100;
                $cursor = '';


                $c = new LazopClient($url, $app_key, $app_secret);
                $request = new LazopRequest('/logistic/order/trace');
                $request->addApiParam('order_id', $order_id);
                $response = $c->execute($request, $access_token);
                $response = json_decode($response, true);
                if (empty($response['result']['module'][0]['package_detail_info_list'][0]['logistic_detail_info_list'])) {
                    $html['status'] = false;
                    $html['data'] = array();
                    $html['msg'] = 'Data belum tersedia!';
                    echo json_encode($html, true);
                    die;
                }
                $arr = array();
                foreach ($response['result']['module'][0]['package_detail_info_list'][0]['logistic_detail_info_list'] as $k2 => $v2) {
                    $arr[$k2]['title'] = $v2['title'];
                    $arr[$k2]['description'] = $v2['description'];
                    $arr[$k2]['datetime'] = DATE("Y-m-d H:i:s", substr($v2['event_time'], 0, 10));
                }
            }
            if (empty($arr)) {
                $html['status'] = false;
                $html['data'] = array();
                $html['msg'] = 'Data belum tersedia!';
                echo json_encode($html, true);
                die;
            } else {
                $html['status'] = true;
                $html['data'] = $arr;
                $html['msg'] = 'Data ' . $order_id . ' ditemukan!';
                echo json_encode($html, true);
                die;
            }
        } else {
            $html['status'] = false;
            $html['data'] = array();
            $html['msg'] = 'Data tidak ditemukan!';
            echo json_encode($html, true);
            die;
        }
    }

    function marketplace_webhook_reset()
    {
        $this->db->delete('webhook', " order_id = '' ");
        $this->db->delete('webhook', " order_date != '' ");

        // $this->db->delete('webhook', " order_date != '' AND order_id != '' ");

        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = true;
        $html['data'] = array();
        $html['msg'] = 'Reset webhook success!';
        echo json_encode($html, true);
        die;
    }

    function marketplace_webhook_refresh()
    {
        $mode = "";
        $data = array();
        $data = $this->mymodel->selectWithQuery("SELECT id,marketplace,order_id,shop_id
            FROM transaction
            WHERE is_webhook = 0 AND is_manual = 0
            ORDER BY updated_at DESC
            LIMIT 30
            ");
        if (empty($data)) {
            $data = $this->mymodel->selectWithQuery("SELECT MIN(id) as id, marketplace, order_id, shop_id
            FROM webhook
            WHERE order_id != '' AND order_date = ''
            GROUP BY order_id, marketplace, shop_id
            ORDER BY MIN(id) DESC
            LIMIT 30
            ");
            $mode = "webhook";
        }

        foreach ($data as $k => $v) {

            if ($mode == "webhook") {
                $url = base_url() . 'api/marketplace/order/detail?shop_id=' . $v['shop_id'] . '&marketplace=' . $v['marketplace'] . '&order_id=' . $v['order_id'] . '&mode=webhook';
            } else {
                $url = base_url() . 'api/marketplace/order/detail?shop_id=' . $v['shop_id'] . '&marketplace=' . $v['marketplace'] . '&order_id=' . $v['order_id'] . '';
            }
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 1,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
        }

        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = true;
        $html['data'] = $data;
        $html['mode'] = $mode;
        $html['msg'] = 'Refresh order data success!';
        echo json_encode($html, true);
        die;
    }
    
    function marketplace_webhook_update()
    {
        $mode = "";
        $data = array();
        $data = $this->mymodel->selectWithQuery("SELECT id,marketplace,order_id,shop_id FROM transaction WHERE DATE(date) >= '2025-04-01' AND DATE(date) <= '2025-06-10' AND order_status = 'COMPLETED' AND dana_pencairan = 0 AND order_status IN ('SETTLEMENT','COMPLETED') AND customer_price > 0 AND type_sub = 'POS' AND DATE(updated_at) != CURDATE()
            ");
        
        print_r($data);die;

        // foreach ($data as $k => $v) {

        //     if ($mode == "webhook") {
        //         $url = base_url() . 'api/marketplace/order/detail?shop_id=' . $v['shop_id'] . '&marketplace=' . $v['marketplace'] . '&order_id=' . $v['order_id'] . '&mode=webhook';
        //     } else {
        //         $url = base_url() . 'api/marketplace/order/detail?shop_id=' . $v['shop_id'] . '&marketplace=' . $v['marketplace'] . '&order_id=' . $v['order_id'] . '';
        //     }
        //     $curl = curl_init();
        //     curl_setopt_array($curl, array(
        //         CURLOPT_URL => $url,
        //         CURLOPT_RETURNTRANSFER => true,
        //         CURLOPT_ENCODING => '',
        //         CURLOPT_MAXREDIRS => 10,
        //         CURLOPT_TIMEOUT => 1,
        //         CURLOPT_FOLLOWLOCATION => true,
        //         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        //         CURLOPT_CUSTOMREQUEST => 'GET',
        //         CURLOPT_HTTPHEADER => array(
        //             'Content-Type: application/json'
        //         ),
        //     ));
        //     $response = curl_exec($curl);
        //     curl_close($curl);
        // }

        // header('Content-Type: application/json; charset=utf-8');
        // $html = array();
        // $html['status'] = true;
        // $html['data'] = $data;
        // $html['mode'] = $mode;
        // $html['msg'] = 'Refresh order data success!';
        // echo json_encode($html, true);
        // die;
    }

    function marketplace_order()
    {


        header('Content-Type: application/json; charset=utf-8');

        $dt = $_GET;
        $marketplace = $dt['marketplace'];
        $marketplace = strtoupper($marketplace);
        $shop_id = $dt['shop_id'];
        $qry = "";
        if ($shop_id) {
            $qry .= " AND shop_id = '$shop_id' ";
        }
        if ($marketplace) {
            $qry .= " AND opt = '$marketplace' ";
        }

        $data = $this->mymodel->selectWithQuery("SELECT *
        FROM marketplace_config
        WHERE status = 'Aktif' $qry");

        $product = $this->mymodel->selectWithQuery("SELECT * FROM product
        ORDER BY sku ASC
        ");

        $arr_product = array();
        foreach ($product as $k => $v) {
            $arr_product[$v['id']] = $v;
        }



        $start_date = $_GET['start_date'];
        $until_date = $_GET['until_date'];
        if (empty($start_date) || empty($until_date)) {
            $start_date = DATE("Y-m-d 00:00:00");
            $until_date = DATE("Y-m-d 00:00:00");
        } else {
            $start_date .= '00:00:00';
            $until_date .= '00:00:00';
        }

        $start_time = strtotime($start_date);
        $until_time = $until_date . '';
        $until_time = DATE('Y-m-d 00:00:00', strtotime($until_time . " +1 days"));
        $until_time = strtotime($until_time);

        foreach ($data as $k => $v) {
            if ($v['opt'] == "TIKTOK") {
                $marketplace = "TIKTOK";
                $config = json_decode($v['val'], true);
                $app_key = $config['app_key'];
                $access_token = $config['access_token'];
                $shop_cipher = $config['shop']['cipher'];
                $app_secret = $this->app_secret_tiktok;
                $shop_id = $v['shop_id'];
                $shop_name = $v['shop_name'];

                $page_size = 100;
                $cursor = "";

                for ($i = 0; $i <= 100; $i++) {

                    $url = 'https://open-api.tiktokglobalshop.com/api/orders/search?access_token=' . $access_token . '&app_key=' . $app_key . '&shop_id=' . $shop_id . '&sign={{sign}}&timestamp={{timestamp}}&version=202212';
                    $urlParts = parse_url($url);
                    $paramGET = [];
                    parse_str($urlParts['query'], $paramGET);
                    $timest = strtotime('now');
                    $pr = array();
                    $pr['secret'] = $app_secret;
                    $pr['timest'] = $timest;
                    $pr['get'] = $paramGET;
                    $pr['post'] = '{"cursor":"' . $cursor . '","page_size":' . $page_size . ',"sort_by":"CREATE_TIME","create_time_from":' . $start_time . ',"create_time_to":' . $until_time . ',"sort_type":2}';
                    $pr['url'] = $url;
                    $sign = $this->tiktok_signature_generator($pr);

                    $url = str_replace('{{sign}}', $sign, $url);
                    $url = str_replace('{{timestamp}}', $timest, $url);
                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => $pr['post'],
                        CURLOPT_HTTPHEADER => array(
                            'Content-Type: application/json',
                            'x-tts-access-token: ' . $access_token
                        ),
                    ));

                    $response = curl_exec($curl);

                    $response = json_decode($response, true);

                    $cursor = $response['data']['next_cursor'];

                    if (empty($response['data']['order_list'])) {
                        break;
                    }

                    foreach ($response['data']['order_list'] as $k2 => $v2) {
                        $order_id = $v2['order_id'];
                        $this->db->select('id');
                        $trx = $this->mymodel->selectDataOne('transaction', array('order_id' => $order_id, 'marketplace' => $marketplace));

                        $dt = array();
                        $dt['type'] = "Out";
                        $dt['type_sub'] = "POS";
                        $dt['marketplace'] = strval($marketplace);
                        $dt['shop_id'] = strval($shop_id);
                        $dt['shop_name'] = strval($shop_name);
                        $dt['marketplace'] = $marketplace;
                        $dt['order_id'] = $order_id;
                        if (in_array($v2['order_status'], array('100'))) {
                            $order_status = 'UNPAID';
                        } else if (in_array($v2['order_status'], array('112', '105'))) {
                            $order_status = 'PROCESSED';
                        } else if (in_array($v2['order_status'], array('returned'))) {
                            $order_status = 'RETURN';
                        } else if (in_array($v2['order_status'], array('140'))) {
                            $order_status = 'CANCELLED';
                        } else if (in_array($v2['order_status'], array('130',))) {
                            $order_status = 'COMPLETED';
                        } else if (in_array($v2['order_status'], array('122'))) {
                            $order_status = 'DELIVERED';
                        } else if (in_array($v2['order_status'], array('121', '114'))) {
                            $order_status = 'SHIPPED';
                            $dt['is_shipped'] = 1;
                        } else if (in_array($v2['order_status'], array('111'))) {
                            $order_status = 'READY_TO_SHIP';
                        }
                        $dt['order_status'] = strval($order_status);
                        if ($trx) {
                            $dt['updated_at'] = DATE("Y-m-d H:i:s");
                            $this->db->update('transaction', $dt, array('id' => $trx['id']));
                        } else if ($dt['order_status'] !== 'CANCELLED') {
                            $dt['date'] = DATE("Y-m-d 23:00:00", strtotime($start_date));
                            $dt['created_at'] = DATE("Y-m-d H:i:s");
                            $this->db->insert('transaction', $dt);
                        }
                    }
                }
            } else if ($v['opt'] == "SHOPEE") {
                $marketplace = "SHOPEE";
                $config = json_decode($v['val'], true);
                $access_token = $config['access_token'];
                $shop_cipher = $config['shop']['cipher'];
                $host = 'https://partner.shopeemobile.com';
                $partner_id = $this->partner_id_shopee;
                $partner_key = $this->partner_key_shopee;
                $shop_id = $v['shop_id'];
                $shop_name = $v['shop_name'];

                $page_size = 100;
                $cursor = '';

                for ($i = 0; $i <= 100; $i++) {
                    $path = "/api/v2/order/get_order_list";
                    $timest = time();
                    $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
                    $sign = hash_hmac('sha256', $baseString, $partner_key);

                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $host . $path . '?partner_id=' . $partner_id . '&timestamp=' . $timest . '&shop_id=' . $shop_id . '&access_token=' . $access_token . '&sign=' . $sign
                            . '&time_range_field=create_time&time_from=' . $start_time . '&time_to=' . $until_time . '&page_size=' . $page_size . '&cursor=' . $cursor . '&response_optional_fields=order_status',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'GET',
                    ));

                    $response = curl_exec($curl);

                    curl_close($curl);
                    $response = json_decode($response, true);

                    $cursor = $response['response']['next_cursor'];

                    foreach ($response['response']['order_list'] as $k2 => $v2) {
                        $order_id = $v2['order_sn'];
                        $this->db->select('id');
                        $trx = $this->mymodel->selectDataOne('transaction', array('order_id' => $order_id, 'marketplace' => $marketplace));

                        $dt = array();
                        $dt['type'] = "Out";
                        $dt['type_sub'] = "POS";
                        $dt['marketplace'] = strval($marketplace);
                        $dt['shop_id'] = strval($shop_id);
                        $dt['shop_name'] = strval($shop_name);
                        $dt['marketplace'] = $marketplace;
                        $dt['order_id'] = $order_id;
                        $dt['order_status'] = $v2['order_status'];
                        if ($trx) {
                            $dt['updated_at'] = DATE("Y-m-d H:i:s");
                            $this->db->update('transaction', $dt, array('id' => $trx['id']));
                        } else if ($dt['order_status'] !== 'CANCELLED') {
                            $dt['date'] = DATE("Y-m-d 23:00:00", strtotime($start_date));
                            $dt['created_at'] = DATE("Y-m-d H:i:s");
                            $this->db->insert('transaction', $dt);
                        }
                    }
                    if (empty($cursor)) {
                        break;
                    }
                }
            } else if ($v['opt'] == "LAZADA") {
                $marketplace = "LAZADA";
                $config = json_decode($v['val'], true);
                $access_token = $config['access_token'];
                $shop_cipher = $config['shop']['cipher'];
                $app_key = $this->app_key_lazada;
                $app_secret = $this->app_secret_lazada;
                $shop_id = $v['shop_id'];
                $shop_name = $v['shop_name'];
                $url = 'https://api.lazada.co.id/rest';
                $page_size = 100;
                $cursor = '';

                $nomor = 0;
                $offset = 0;
                $limit = 100;

                for ($i = 0; $i <= 100; $i++) {
                    $c = new LazopClient($url, $app_key, $app_secret);
                    $request = new LazopRequest('/orders/get', 'GET');
                    $request->addApiParam('sort_direction', 'ASC');
                    $request->addApiParam('offset', $offset);
                    $request->addApiParam('limit', $limit);
                    $request->addApiParam('sort_by', 'created_at');
                    $start_date = DATE("Y-m-d", strtotime($start_date));
                    $until_date = DATE("Y-m-d", strtotime($until_date));
                    $until_date = DATE('Y-m-d', strtotime($until_date . " +1 days"));

                    $request->addApiParam('created_after', $start_date . 'T00:00:00+07:00');
                    $request->addApiParam('created_before', $until_date . 'T00:00:00+07:00');

                    $response = $c->execute($request, $access_token);
                    $response = json_decode($response, true);

                    $total_data = $response['data']['countTotal'];

                    foreach ($response['data']['orders'] as $k2 => $v2) {
                        $nomor++;
                        $order_id = $v2['order_id'];
                        $this->db->select('id');
                        $trx = $this->mymodel->selectDataOne('transaction', array('order_id' => $order_id, 'marketplace' => $marketplace));

                        $dt = array();
                        $dt['type'] = "Out";
                        $dt['type_sub'] = "POS";
                        $dt['marketplace'] = strval($marketplace);
                        $dt['shop_id'] = strval($shop_id);
                        $dt['shop_name'] = strval($shop_name);
                        $dt['marketplace'] = $marketplace;
                        $dt['order_id'] = $order_id;

                        $order_status = "COMPLETED";
                        $dt['is_shipped'] = 0;
                        if (in_array($v2['statuses'][0], array('unpaid'))) {
                            $order_status = 'UNPAID';
                        } else if (in_array($v2['statuses'][0], array('topack', 'pending'))) {
                            $order_status = 'PROCESSED';
                        } else if (in_array($v2['statuses'][0], array('returned', 'shipped_back_success'))) {
                            $order_status = 'RETURN';
                        } else if (in_array($v2['statuses'][0], array('canceled', 'failed', 'lost'))) {
                            $order_status = 'CANCELLED';
                        } else if (in_array($v2['statuses'][0], array('confirmed'))) {
                            $order_status = 'COMPLETED';
                            $dt['disbursement_at'] = '';
                            $dt['is_disbursement'] = '1';
                        } else if (in_array($v2['statuses'][0], array('delivered'))) {
                            $order_status = 'DELIVERED';
                        } else if (in_array($v2['statuses'][0], array('shipped'))) {
                            $order_status = 'SHIPPED';
                            $dt['is_shipped'] = 1;
                        } else if (in_array($v2['statuses'][0], array('ready_to_ship', 'toship', 'shipping'))) {
                            $order_status = 'READY_TO_SHIP';
                        }

                        $dt['order_status'] = $order_status;

                        $dt['customer_price'] = $v2['price'];
                        
                        print_r($dt);

                        if ($trx) {
                            $dt['updated_at'] = DATE("Y-m-d H:i:s");
                            $this->db->update('transaction', $dt, array('id' => $trx['id']));
                        } else if ($dt['order_status'] !== 'CANCELLED') {
                            $dt['date'] = DATE("Y-m-d 23:00:00", strtotime($start_date));
                            $dt['created_at'] = DATE("Y-m-d H:i:s");
                            $this->db->insert('transaction', $dt);
                        }
                    }
                    $offset += $limit;
                    if ($nomor >= intval($total_data)) {
                        break;
                    }
                }
            }
        }

        $html['status'] = true;
        $html['data'] = array();
        $html['msg'] = 'Sync data order berhasil!';
        echo json_encode($html, true);
        die;
    }

    function marketplace_product()
    {

        $msg = "Sync data produk berhasil!";
        $status = true;
        header('Content-Type: application/json; charset=utf-8');

        $dt = $_GET;
        $marketplace = $dt['marketplace'];
        $marketplace = strtoupper($marketplace);
        $shop_id = $dt['shop_id'];
        $qry = "";
        if ($shop_id) {
            $qry .= " AND shop_id = '$shop_id' ";
        }
        if ($marketplace) {
            $qry .= " AND opt = '$marketplace' ";
        }

        $data = $this->mymodel->selectWithQuery("SELECT *
        FROM marketplace_config
        WHERE status = 'Aktif' $qry");

        foreach ($data as $k => $v) {
            $shop_name = $v['shop_name'];
            $shop_id = $v['shop_id'];
            if ($v['opt'] == "TIKTOK") {
                $marketplace = "TIKTOK";
                $config = json_decode($v['val'], true);
                $app_key = $config['app_key'];
                $access_token = $config['access_token'];
                $shop_cipher = $config['shop']['cipher'];
                $app_secret = $this->app_secret_tiktok;

                $page_size = 100;
                $next_page_token = "";

                for ($i = 0; $i <= 100; $i++) {
                    $url = 'https://open-api.tiktokglobalshop.com/product/202312/products/search?access_token=' . $access_token . '&app_key=' . $app_key . '&page_size=' . $page_size . '&page_token=' . $next_page_token . '&shop_cipher=' . $shop_cipher . '&shop_id=' . $shop_id . '&sign={{sign}}&timestamp={{timestamp}}&version=202312';
                    $urlParts = parse_url($url);
                    $paramGET = [];
                    parse_str($urlParts['query'], $paramGET);
                    $timest = strtotime('now');
                    $pr = array();
                    $pr['secret'] = $app_secret;
                    $pr['timest'] = $timest;
                    $pr['get'] = $paramGET;
                    $pr['post'] = '{"status":"ACTIVATE"}';
                    $pr['url'] = $url;
                    $sign = $this->tiktok_signature_generator($pr);

                    $url = str_replace('{{sign}}', $sign, $url);
                    $url = str_replace('{{timestamp}}', $timest, $url);
                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => $pr['post'],
                        CURLOPT_HTTPHEADER => array(
                            'Content-Type: application/json',
                            'x-tts-access-token: ' . $access_token
                        ),
                    ));

                    $response = curl_exec($curl);

                    $response = json_decode($response, true);

                    if ($response['code']) {
                        $msg = $marketplace . ' ' . $shop_name . ' : ' . $response['message'];
                        $status = false;
                    }

                    $next_page_token = $response['data']['next_page_token'];

                    foreach ($response['data']['products'] as $k2 => $v2) {

                        $id_product = $v2['id'];
                        $this->db->select('id');
                        $product = $this->mymodel->selectDataOne('product_3rd', array('id_product' => $id_product, 'marketplace' => $marketplace));

                        $url = 'https://open-api.tiktokglobalshop.com/product/202309/products/' . $id_product . '?app_key=' . $app_key . '&shop_cipher=' . $shop_cipher . '&shop_id=' . $shop_id . '&access_token=' . $access_token . '&sign={{sign}}&timestamp={{timestamp}}&version=202309';

                        $urlParts = parse_url($url);
                        $paramGET = [];
                        parse_str($urlParts['query'], $paramGET);
                        $timest = strtotime('now');
                        $pr = array();
                        $pr['secret'] = $app_secret;
                        $pr['timest'] = $timest;
                        $pr['get'] = $paramGET;
                        $pr['post'] = '';
                        $pr['url'] = $url;
                        $sign = $this->tiktok_signature_generator($pr);

                        $url = str_replace('{{sign}}', $sign, $url);
                        $url = str_replace('{{timestamp}}', $timest, $url);
                        $curl = curl_init();
                        curl_setopt_array($curl, array(
                            CURLOPT_URL => $url,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => '',
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 0,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => 'GET',
                            CURLOPT_POSTFIELDS => $pr['post'],
                            CURLOPT_HTTPHEADER => array(
                                'Content-Type: application/json',
                                'x-tts-access-token: ' . $access_token
                            ),
                        ));

                        $response_detail = curl_exec($curl);

                        $response_detail = json_decode($response_detail, true);

                        $v2 = $response_detail['data'];
                        $dt = array();
                        $dt['marketplace'] = $marketplace;
                        $dt['id_product'] = $id_product;
                        $dt['name'] = strval($v2['title']);
                        $dt['desc'] = strval($v2['description']);
                        $dt['sku'] = strval($v2['sku']);

                        $dt['shop_name'] = $shop_name;
                        $dt['shop_id'] = $shop_id;
                        // $img_url = $v2['main_images'][0]['urls'][0];
                        $img_url = $v2['main_images'][0]['thumb_urls'][0];
                        if ($img_url) {
                            $file_name = $id_product . '.jpg';
                            // $img_dir = '/public_html/app/assets/img/product_3rd/' . $file_name;
                            $img_dir = './assets/img/product_3rd/' . $file_name;
                            file_put_contents($img_dir, file_get_contents($img_url));
                            $dt['img'] = $file_name;
                        }

                        if ($product) {
                            $dt['updated_at'] = DATE("Y-m-d H:i:s");
                            $this->db->update('product_3rd', $dt, array('id' => $product['id']));
                        } else {
                            $dt['created_by'] = strval($_SESSION['user']['id']);
                            $dt['created_at'] = DATE("Y-m-d H:i:s");
                            $this->db->insert('product_3rd', $dt);
                            $product['id'] = $this->db->insert_id();
                        }

                        $item = $v2['skus'];
                        $item_list = array();
                        if (empty($item)) {
                            $varian['sku'] = '';
                            $varian['name'] = '';
                            $varian['id_product'] = '0';
                            $varian['sku_parent'] = $dt['sku'];
                            $varian['parent_name'] = $dt['name'];
                            $varian['id_product_parent'] = $dt['id_product'];
                            $varian['id_parent'] = $product['id'];
                            $varian['img'] = $dt['img'];
                            $item_list[] = $varian;
                        } else {
                            foreach ($item as $k3 => $v3) {
                                $varian['sku'] = $v3['seller_sku'];
                                $varian['name'] = strval($v3['sales_attributes'][0]['value_name']);
                                $varian['id_product'] = $v3['id'];
                                $varian['sku_parent'] = $dt['sku'];
                                $varian['parent_name'] = $dt['name'];
                                $varian['id_product_parent'] = $dt['id_product'];
                                $varian['id_parent'] = $product['id'];
                                // $img_url = $v3['sales_attributes'][0]['sku_img']['urls'][0];
                                $img_url = $v3['sales_attributes'][0]['sku_img']['thumb_urls'][0];
                                if ($img_url) {
                                    $file_name = $varian['id_product'] . '.jpg';
                                    $img_dir = './assets/img/product_3rd/' . $file_name;
                                    file_put_contents($img_dir, file_get_contents($img_url));
                                    $varian['img'] = $file_name;
                                }
                                $item_list[] = $varian;
                            }
                        }



                        $dt['json_varian'] = json_encode($item_list, true);
                        $dt['count_varian'] = count($item_list);

                        $dt['updated_at'] = DATE("Y-m-d H:i:s");
                        $this->db->update('product_3rd', $dt, array('id' => $product['id']));

                        $ids = '';
                        foreach ($item_list as $k4 => $v4) {
                            $id_product = $v4['id_product'];
                            $id_product_parent = $v4['id_product_parent'];
                            $this->db->select('id');
                            $product = $this->mymodel->selectDataOne('product_variant_3rd', array('id_product' => $id_product, 'id_product_parent' => $id_product_parent, 'marketplace' => $marketplace));
                            $dtt = array();
                            foreach ($v4 as $k5 => $v5) {
                                $dtt[$k5] = strval($v5);
                            }
                            $dtt['marketplace'] = $marketplace;
                            $dtt['shop_name'] = $shop_name;
                            $dtt['shop_id'] = $shop_id;

                            if ($dtt['sku']) {
                                $dat = $this->mymodel->selectDataOne('product_variant_3rd', array('sku' => $dtt['sku']));
                                if ($dat) {
                                    $dtt['json'] = strval($dat['json']);
                                }
                            }

                            if ($product) {
                                $dtt['updated_at'] = DATE("Y-m-d H:i:s");
                                $this->db->update('product_variant_3rd', $dtt, array('id' => $product['id']));
                            } else {

                                $dtt['created_by'] = strval($_SESSION['user']['id']);
                                $dtt['created_at'] = DATE("Y-m-d H:i:s");
                                $this->db->insert('product_variant_3rd', $dtt);
                                $product['id'] = $this->db->insert_id();
                            }
                            $ids .= $id_product . ',';
                        }
                        // print_r($v2);
                        // die;
                        // $id_parent = $dt['id_product'];
                        // $ids = substr($ids, 0, -1);
                        // $qry = "";
                        // if ($ids != "") {
                        //     $qry = " AND id_product NOT IN ($ids) ";
                        // }
                        // $this->db->query("DELETE FROM product_variant_3rd
                        //         WHERE id_product_parent = '$id_parent' $qry");
                    }
                    if (empty($next_page_token)) {
                        break;
                    }
                }
            } else if ($v['opt'] == "SHOPEE") {
                $marketplace = $v['opt'];
                $config = json_decode($v['val'], true);
                $app_key = $this->app_key_lazada;
                $partner_id = $this->partner_id_shopee;
                $partner_key = $this->partner_key_shopee;
                $access_token = $config['access_token'];
                $host = 'https://partner.shopeemobile.com';
                $shop_id = intval($shop_id);

                $offset = 0;
                for ($i = 0; $i <= 3; $i++) {
                    $path = "/api/v2/product/get_item_list";
                    $timest = time();
                    $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
                    $sign = hash_hmac('sha256', $baseString, $partner_key);

                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $host . $path . '?partner_id=' . $config['partner_id'] . '&timestamp=' . $timest . '&shop_id=' . $shop_id . '&access_token=' . $access_token . '&sign=' . $sign
                            . '&offset=' . $offset . '&page_size=50&item_status=NORMAL',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'GET',
                    ));
                    $response = curl_exec($curl);
                    curl_close($curl);
                    $response = json_decode($response, true);

                    if ($response['response']['next_offset']) {
                        $offset = $response['response']['next_offset'];
                    } else {
                        $offset = $response['response']['next_offset'];
                    }

                    $list_id = '';
                    foreach ($response['response']['item'] as $k => $v) {
                        $list_id .= $v['item_id'] . ',';
                    }
                    $list_id = substr($list_id, 0, -1);

                    if ($list_id) {
                        $path = "/api/v2/product/get_item_base_info";
                        $timest = time();
                        $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
                        $sign = hash_hmac('sha256', $baseString, $partner_key);

                        $curl = curl_init();
                        curl_setopt_array($curl, array(
                            CURLOPT_URL => $host . $path . '?access_token=' . $access_token . '&item_id_list=' . $list_id . '&need_complaint_policy=true&need_tax_info=true&partner_id=' . $partner_id . '&shop_id=' . $shop_id . '&sign=' . $sign . '&timestamp=' . $timest . '',
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => '',
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 0,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => 'GET',
                        ));

                        $response = curl_exec($curl);
                        curl_close($curl);
                        $response = json_decode($response, true);
                    }
                    foreach ($response['response']['item_list']  as $k2 => $v2) {
                        $id_product = $v2['item_id'];
                        $this->db->select('id');
                        $product = $this->mymodel->selectDataOne('product_3rd', array('id_product' => $id_product, 'marketplace' => $marketplace));

                        $dt = array();
                        $dt['marketplace'] = $marketplace;
                        $dt['id_product'] = $id_product;
                        $dt['name'] = strval($v2['item_name']);
                        $dt['desc'] = strval($v2['description']);
                        $dt['sku'] = strval($v2['item_sku']);
                        $dt['shop_name'] = $shop_name;
                        $dt['shop_id'] = $shop_id;
                        $img_url = $v2['image']['image_url_list'][0];
                        if ($img_url) {
                            $file_name = $id_product . '.jpg';
                            $img_dir = './assets/img/product_3rd/' . $file_name;
                            file_put_contents($img_dir, file_get_contents($img_url));
                            $dt['img'] = $file_name;
                        }

                        if ($product) {
                            $dt['updated_at'] = DATE("Y-m-d H:i:s");
                            $this->db->update('product_3rd', $dt, array('id' => $product['id']));
                        } else {
                            $dt['created_by'] = strval($_SESSION['user']['id']);
                            $dt['created_at'] = DATE("Y-m-d H:i:s");
                            $this->db->insert('product_3rd', $dt);
                            $product['id'] = $this->db->insert_id();
                        }

                        $item = array();
                        if (intval($v2['has_model']) > 0) {
                            $path = "/api/v2/product/get_model_list";
                            $timest = time();
                            $baseString = sprintf("%s%s%s%s%s", $partner_id, $path, $timest, $access_token, $shop_id);
                            $sign = hash_hmac('sha256', $baseString, $partner_key);

                            // $dt['id_product'] = '3609877442';
                            $curl = curl_init();
                            curl_setopt_array($curl, array(
                                CURLOPT_URL => $host . $path . '?access_token=' . $access_token . '&item_id=' . $dt['id_product'] . '&need_complaint_policy=true&need_tax_info=true&partner_id=' . $partner_id . '&shop_id=' . $shop_id . '&sign=' . $sign . '&timestamp=' . $timest . '',
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_ENCODING => '',
                                CURLOPT_MAXREDIRS => 10,
                                CURLOPT_TIMEOUT => 0,
                                CURLOPT_FOLLOWLOCATION => true,
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                CURLOPT_CUSTOMREQUEST => 'GET',
                            ));

                            $response = curl_exec($curl);
                            curl_close($curl);
                            $response = json_decode($response, true);

                            $item = $response['response']['model'];
                        }


                        // print_r($v2);
                        // print_r($item);
                        // echo ' --- ';

                        $item_list = array();
                        if (empty($item)) {
                            $varian['sku'] = '';
                            $varian['name'] = '';
                            $varian['id_product'] = '0';
                            $varian['sku_parent'] = $dt['sku'];
                            $varian['parent_name'] = $dt['name'];
                            $varian['id_product_parent'] = $dt['id_product'];
                            $varian['id_parent'] = $product['id'];
                            $varian['img'] = $dt['img'];
                            $item_list[] = $varian;
                        } else {
                            foreach ($item as $k3 => $v3) {
                                $varian['sku'] = $v3['model_sku'];
                                $name = $v3['model_name'];
                                if (empty($name)) {
                                    $name = $dt['name'];
                                }
                                $varian['name'] = strval($name);
                                $varian['id_product'] = $v3['model_id'];
                                $varian['sku_parent'] = $dt['sku'];
                                $varian['parent_name'] = $dt['name'];
                                $varian['id_product_parent'] = $dt['id_product'];
                                $varian['id_parent'] = $product['id'];
                                $img_url = $response['response']['tier_variation'][$k3]['option_list'][0]['image']['image_url'];
                                if ($img_url) {
                                    $file_name = $varian['id_product'] . '.jpg';
                                    $img_dir = './assets/img/product_3rd/' . $file_name;
                                    file_put_contents($img_dir, file_get_contents($img_url));
                                    $varian['img'] = $file_name;
                                }
                                $item_list[] = $varian;
                            }
                        }

                        $dt['json_varian'] = json_encode($item_list, true);
                        $dt['count_varian'] = count($item_list);



                        $dt['updated_at'] = DATE("Y-m-d H:i:s");
                        $this->db->update('product_3rd', $dt, array('id' => $product['id']));

                        $ids = '';
                        foreach ($item_list as $k4 => $v4) {
                            $id_product = $v4['id_product'];
                            $id_product_parent = $v4['id_product_parent'];
                            $this->db->select('id');
                            $product = $this->mymodel->selectDataOne('product_variant_3rd', array('id_product' => $id_product, 'id_product_parent' => $id_product_parent, 'marketplace' => $marketplace));
                            $dtt = array();
                            foreach ($v4 as $k5 => $v5) {
                                $dtt[$k5] = strval($v5);
                            }
                            $dtt['marketplace'] = $marketplace;
                            $dtt['shop_name'] = $shop_name;
                            $dtt['shop_id'] = $shop_id;
                            if ($product) {
                                $dtt['updated_at'] = DATE("Y-m-d H:i:s");
                                $this->db->update('product_variant_3rd', $dtt, array('id' => $product['id']));
                            } else {

                                $dtt['created_by'] = strval($_SESSION['user']['id']);
                                $dtt['created_at'] = DATE("Y-m-d H:i:s");
                                $this->db->insert('product_variant_3rd', $dtt);
                                $product['id'] = $this->db->insert_id();
                            }
                            $ids .= $id_product . ',';
                        }
                        // $id_parent = $dt['id_product'];
                        // $ids = substr($ids, 0, -1);
                        // $qry = "";
                        // if ($ids != "") {
                        //     $qry = " AND id_product NOT IN ($ids) ";
                        // }
                        // $this->db->query("DELETE FROM product_variant_3rd
                        //         WHERE id_product_parent = '$id_parent' $qry");
                    }

                    if (empty($offset)) {
                        break;
                    }
                }
            } else if ($v['opt'] == "LAZADA") {
                $marketplace = $v['opt'];
                $config = json_decode($v['val'], true);
                $app_key = $this->app_key_lazada;
                $app_secret = $this->app_secret_lazada;
                $refresh_token = $config['refresh_token'];
                $url = 'https://api.lazada.co.id/rest';

                $nomor = 0;
                $offset = 0;
                $limit = 50;
                for ($i = 0; $i <= 3; $i++) {
                    $nomor++;
                    $c = new LazopClient($url, $app_key, $app_secret);
                    $request = new LazopRequest('/products/get', 'GET');
                    $request->addApiParam('filter', 'all');
                    $request->addApiParam('offset', $offset);
                    $request->addApiParam('limit', $limit);
                    $request->addApiParam('options', '1');
                    $response = $c->execute($request, $config['access_token']);
                    $response = json_decode($response, true);

                    foreach ($response['data']['products'] as $k2 => $v2) {
                        $id_product = $v2['item_id'];
                        $this->db->select('id');
                        $product = $this->mymodel->selectDataOne('product_3rd', array('id_product' => $id_product, 'marketplace' => $marketplace));

                        $dt = array();
                        $dt['marketplace'] = $marketplace;
                        $dt['id_product'] = $id_product;
                        $dt['name'] = strval($v2['attributes']['name']);
                        $dt['desc'] = strval($v2['attributes']['description']);
                        $dt['sku'] = "";

                        $dt['shop_name'] = $shop_name;
                        $dt['shop_id'] = $shop_id;
                        $img_url = $v2['images'][0];
                        if ($img_url) {
                            $file_name = $id_product . '.jpg';
                            $img_dir = './assets/img/product_3rd/' . $file_name;
                            file_put_contents($img_dir, file_get_contents($img_url));
                            $dt['img'] = $file_name;
                        }

                        if ($product) {
                            $dt['updated_at'] = DATE("Y-m-d H:i:s");
                            $this->db->update('product_3rd', $dt, array('id' => $product['id']));
                        } else {
                            $dt['created_by'] = strval($_SESSION['user']['id']);
                            $dt['created_at'] = DATE("Y-m-d H:i:s");
                            $this->db->insert('product_3rd', $dt);
                            $product['id'] = $this->db->insert_id();
                        }


                        $item = $v2['skus'];
                        $item_list = array();
                        if (empty($item)) {
                            $varian['sku'] = '';
                            $varian['name'] = '';
                            $varian['id_product'] = '0';
                            $varian['sku_parent'] = $dt['sku'];
                            $varian['parent_name'] = $dt['name'];
                            $varian['id_product_parent'] = $dt['id_product'];
                            $varian['id_parent'] = $product['id'];
                            $varian['img'] = $dt['img'];
                            $item_list[] = $varian;
                        } else {
                            foreach ($item as $k3 => $v3) {
                                $varian['sku'] = $v3['SellerSku'];

                                $name = "";
                                if ($v3['saleProp']) {
                                    foreach ($v3['saleProp'] as $k4 => $v4) {
                                        $name = $v4;
                                    }
                                }
                                if (empty($name)) {
                                    $name = $v3['fragrance_family'];
                                }

                                $varian['name'] = strval($name);
                                $varian['id_product'] = $v3['SkuId'];
                                $varian['sku_parent'] = $dt['sku'];
                                $varian['parent_name'] = $dt['name'];
                                $varian['id_product_parent'] = $dt['id_product'];
                                $varian['id_parent'] = $product['id'];
                                $img_url = $v3['Images'][0];
                                if ($img_url) {
                                    $file_name = $varian['id_product'] . '.jpg';
                                    $img_dir = './assets/img/product_3rd/' . $file_name;
                                    file_put_contents($img_dir, file_get_contents($img_url));
                                    $varian['img'] = $file_name;
                                }
                                $item_list[] = $varian;
                            }
                        }

                        $dt['json_varian'] = json_encode($item_list, true);
                        $dt['count_varian'] = count($item_list);

                        $dt['updated_at'] = DATE("Y-m-d H:i:s");
                        $this->db->update('product_3rd', $dt, array('id' => $product['id']));

                        $ids = '';
                        foreach ($item_list as $k4 => $v4) {
                            $id_product = $v4['id_product'];
                            $id_product_parent = $v4['id_product_parent'];
                            $this->db->select('id');
                            $product = $this->mymodel->selectDataOne('product_variant_3rd', array('id_product' => $id_product, 'id_product_parent' => $id_product_parent, 'marketplace' => $marketplace));
                            $dtt = array();
                            foreach ($v4 as $k5 => $v5) {
                                $dtt[$k5] = strval($v5);
                            }
                            $dtt['marketplace'] = $marketplace;
                            $dtt['shop_name'] = $shop_name;
                            $dtt['shop_id'] = $shop_id;
                            if ($product) {
                                $dtt['updated_at'] = DATE("Y-m-d H:i:s");
                                $this->db->update('product_variant_3rd', $dtt, array('id' => $product['id']));
                            } else {

                                $dtt['created_by'] = strval($_SESSION['user']['id']);
                                $dtt['created_at'] = DATE("Y-m-d H:i:s");
                                $this->db->insert('product_variant_3rd', $dtt);
                                $product['id'] = $this->db->insert_id();
                            }
                            $ids .= $id_product . ',';
                        }
                        // $id_parent = $dt['id_product'];
                        // $ids = substr($ids, 0, -1);
                        // $qry = "";
                        // if ($ids != "") {
                        //     $qry = " AND id_product NOT IN ($ids) ";
                        // }
                        // $this->db->query("DELETE FROM product_variant_3rd
                        //         WHERE id_product_parent = '$id_parent' $qry");
                    }

                    $offset += $limit;

                    if ($nomor >= intval($response['data']['total_products'])) {
                        break;
                    }
                }
            }
        }

        $html['status'] = $status;
        $html['data'] = array();
        $html['msg'] = $msg;
        echo json_encode($html, true);
        die;
    }

    function cronjob_influencer()
    {


        $user = $_SESSION['user'];

        $mode = strval($_GET['mode']);

        $target = DATE("Y-m-d 11:00:00");
        $now = DATE("Y-m-d H:i:s");
        if ($mode != 'true') {
            if ($now >= $target) {
                // SKIP
            } else {
                header('Content-Type: application/json; charset=utf-8');
                $html = array();
                $html['status'] = false;
                $html['data'] = array();
                $html['msg'] = "Bhskin influencer cronjob will be processed at " . $target . "!";
                echo json_encode($html, true);
                die;
            }
        }
        $today = DATE("Y-m-d");
        $today = DATE('Y-m-d', strtotime($today . " -7 days"));
        $todayy = $today;
        $list = $this->mymodel->selectWithQuery("SELECT * FROM influencer WHERE status = 'Aktif' AND DATE(sync_at) <= '$today' OR DATE(sync_at) IS NULL AND url != '' LIMIT 10");
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
                    $uri = explode("/", parse_url($url, PHP_URL_PATH));
                    $username = $uri[1];
                    $username = str_replace('@', '', $username);
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
                        $dt['avg_view'] = $dt['view'] / 10;
                    }
                    if (($dt['like'] + $dt['comment'] + $dt['collect'] + $dt['share'])  > 0) {
                        $dt['avg_interaksi'] = ($dt['like'] + $dt['comment'] + $dt['collect'] + $dt['share']) / 10;
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

                    // $msg = "Refresh data berhasil!";
                    // echo $this->template->alert_success($msg);
                    // die;
                }
            }
        }
        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = true;
        $html['data'] = array();
        $html['msg'] = count($list) . " data influencer yg di sync <= $todayy berhasil diperbarui";
        echo json_encode($html, true);
        die;
    }

    function cronjob_influencer_dummy()
    {
        $user = isset($_SESSION['user']) ? $_SESSION['user'] : null;

        $mode = isset($_GET['mode']) ? strval($_GET['mode']) : '';

        $target = DATE("Y-m-d 01:00:00");
        $now = DATE("Y-m-d H:i:s");

        if ($mode != 'true') {
            if ($now >= $target) {
                // SKIP - proceed with execution
            } else {
                header('Content-Type: application/json; charset=utf-8');
                $html = array();
                $html['status'] = false;
                $html['data'] = array();
                $html['msg'] = "Influencer dummy cronjob will be processed at " . $target . "!";
                echo json_encode($html, true);
                die;
            }
        }

        $today = DATE("Y-m-d");
        $sync_date = DATE('Y-m-d', strtotime($today . " -7 days"));

        // Get influencer_dummy records that need syncing
        $list = $this->mymodel->selectWithQuery("SELECT * FROM influencer_dummy WHERE status = 'Aktif' AND (DATE(sync_at) <= '$sync_date' OR sync_at IS NULL) AND url != '' LIMIT 10");

        $processed = 0;
        foreach ($list as $kl => $vl) {
            $id = $vl['id'];
            $query = $vl;
            $url = $query['url'];
            $type = $query['type'] ? $query['type'] : 'Tiktok';
            $ratecard = is_numeric($query['ratecard']) ? $query['ratecard'] : 0;

            // Get account ID and basic stats
            $response = $this->template->get_account_id($type, $url);

            if ($response['status'] == false) {
                continue;
            }

            $dt = array();
            $dt['updated_at'] = DATE("Y-m-d H:i:s");
            if ($user) {
                $dt['updated_by'] = strval($user['id']);
            }
            $dt['account_id'] = $response['data']['account_id'];
            $dt['img'] = $response['data']['img'];
            $dt['follower'] = $response['data']['follower'];
            $dt['media_count'] = $response['data']['media_count'];

            $this->db->update('influencer_dummy', $dt, array('id' => $id));

            // Get post list data
            if ($type == "Tiktok") {
                preg_match('/@([a-zA-Z0-9._]+)/', $url, $matches);
                $username = $matches[1] ?? '';
                if (empty($username)) {
                    continue;
                }
                $response = $this->template->get_post_list($type, $dt['account_id']);
            } else {
                $response = $this->template->get_post_list($type, $dt['account_id']);
            }

            if ($response['status'] == false) {
                continue;
            }

            $like = $comment = $collect = $share = $view = 0;
            $i = 0;

            foreach ($response['data'] as $k => $v) {
                $like += $v['like'];
                $comment += $v['comment'];
                $collect += $v['collect'];
                $share += $v['share'];
                $view += $v['view'];
                if ($i >= 10) {
                    break;
                }
                $i++;
            }

            $avg_view = $i ? $view / $i : 0;
            $avg_interaksi = $i ? ($like + $comment + $collect + $share) / $i : 0;
            $er = ($avg_view > 0) ? ($avg_interaksi / $avg_view * 100) : 0;
            $cpm = ($ratecard > 0 && $avg_view > 0) ? ($ratecard / $avg_view * 1000) : 0;

            $dt_2 = array();
            $dt_2['sync_at'] = DATE("Y-m-d H:i:s");
            $dt_2['updated_at'] = DATE("Y-m-d H:i:s");
            if ($user) {
                $dt_2['updated_by'] = strval($user['id']);
            }
            $dt_2['frequency_2'] = $i;
            $dt_2['view_2'] = $view;
            $dt_2['like_2'] = $like;
            $dt_2['collect_2'] = $collect;
            $dt_2['share_2'] = $share;
            $dt_2['comment_2'] = $comment;
            $dt_2['avg_view_2'] = $avg_view;
            $dt_2['avg_interaksi_2'] = $avg_interaksi;
            $dt_2['er'] = $er;
            $dt_2['cpm_2'] = $cpm;

            $this->db->update('influencer_dummy', $dt_2, array('id' => $id));
            $processed++;
        }

        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = true;
        $html['data'] = array();
        $html['msg'] = $processed . " data influencer dummy yg di sync <= $sync_date berhasil diperbarui";
        echo json_encode($html, true);
        die;
    }

    function maintenance()
    {
        $logs = $this->mymodel->selectWithQuery("SELECT *
        FROM endorse_logs
        WHERE views_after = 0
        AND views < 0
        -- AND id_endorse = '5320'
        ");
        foreach ($logs as $k2 => $v2) {
            $dtt = array();
            $id = $v2['id'];
            $id_endorse = $v2['id_endorse'];
            $dt_before = $this->mymodel->selectWithQuery("SELECT *
            FROM endorse_logs WHERE id_endorse = '$id_endorse'
            AND id < '$id'
            ORDER BY id DESC
            LIMIT 1
            ");
            $dt_before = $dt_before[0];
            if ($dt_before) {
                $dtt['views_after'] = strval($dt_before['views_after']);
                $dtt['likes_after'] = strval($dt_before['likes_after']);
                $dtt['comment_after'] = strval($dt_before['comment_after']);
                $dtt['share_save_after'] = strval($dt_before['share_save_after']);
                $dtt['cpm_after'] = strval($dt_before['cpm_after']);

                $dtt['views'] = 0;
                $dtt['likes'] = 0;
                $dtt['comment'] = 0;
                $dtt['share_save'] = 0;
                $dtt['cpm'] = 0;

                $dtt['views_before'] = strval($dt_before['views_after']);
                $dtt['likes_before'] = strval($dt_before['likes_after']);
                $dtt['comment_before'] = strval($dt_before['comment_after']);
                $dtt['share_save_before'] = strval($dt_before['share_save_after']);
                $dtt['cpm_before'] = strval($dt_before['cpm_after']);

                print_r($dtt);
                $this->db->update('endorse_logs', $dtt, array('id' => $v2['id']));
            }
        }
    }

    function maintenance_2()
    {
        $today = DATE("Y-m-d");
        $data = $this->mymodel->selectWithQuery("SELECT *
        FROM endorse
        -- WHERE id = 5101
        WHERE DATE(updated_at) != '$today'
        -- LIMIT 100
        ");
        foreach ($data as $k2 => $v2) {
            $id = $v2['id'];
            $logs = $this->mymodel->selectWithQuery("SELECT *
            FROM endorse_logs
            WHERE id_endorse = '$id'
            AND views_after > 0
            ORDER BY id DESC
            LIMIT 1");
            $logs = $logs[0];
            $dtt = array();
            $dtt['views'] = strval($logs['views_after']);
            $dtt['likes'] = strval($logs['likes_after']);
            $dtt['comment'] = strval($logs['comment_after']);
            $dtt['share_save'] = strval($logs['share_save_after']);
            $dtt['cpm'] = strval($logs['cpm_after']);
            $dtt['updated_at'] = DATE("Y-m-d H:i:s");
            print_r($dtt);
            $this->db->update('endorse', $dtt, array('id' => $v2['id']));
        }
    }

    function maintenance_3()
    {
        $today = DATE("Y-m-04 H:i:s");
        $data = $this->mymodel->selectWithQuery("SELECT *
        FROM endorse
        WHERE link_upload LIKE '%vt.%'
        AND platform = 'Tiktok' AND DATE(updated_at) != '$today'
        AND status = 'Aktif' AND status_campaign = 'Aktif'
        ORDER BY created_at DESC
        LIMIT 1000
       ");

        foreach ($data as $k => $v) {
            echo $v['id'];
            echo '<br>';
            $dt = array();
            $url = $v['link_upload'];
            echo $url;
            echo '<br>';
            $new_url = $this->getFinalUrl($url);
            if (strpos($new_url, "tiktok.com") !== false) {
                echo $new_url;
                echo '<br>';
                $dt['link_upload'] = $new_url;
            }
            echo '----';
            echo '<br>';
            echo '<br>';
            $dt['updated_at'] = $today;
            $dt['platform'] = 'Tiktok';
            $this->db->update('endorse', $dt, array('id' => $v['id']));
        }
    }

    function getFinalUrl($url)
    {
        // Get headers for the URL, including any redirect headers
        $headers = get_headers($url, 1);

        // Check if there is a 'Location' header, which indicates a redirect
        if (isset($headers['Location'])) {
            // If 'Location' is an array (in case of multiple redirects), get the last one
            $finalUrl = is_array($headers['Location']) ? end($headers['Location']) : $headers['Location'];
            $parsedUrl = parse_url($finalUrl);

            // Reconstruct the URL without query parameters
            $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'];
            return $baseUrl;
        }

        // Return the original URL if there is no redirect
        return $url;
    }

    function cronjob_endorse()
    {


        $user = $_SESSION['user'];

        $mode = strval($_GET['mode']);

        $target = DATE("Y-m-d 11:00:00");
        $now = DATE("Y-m-d H:i:s");
        if ($mode != 'true') {
            if ($now >= $target) {
                // SKIP
            } else {
                header('Content-Type: application/json; charset=utf-8');
                $html = array();
                $html['status'] = false;
                $html['data'] = array();
                $html['msg'] = "Bhskin influencer cronjob will be processed at " . $target . "!";
                echo json_encode($html, true);
                die;
            }
        }
        $today = DATE("Y-m-d");
        // $today = DATE('Y-m-d', strtotime($today . " -1 days"));
        $todayy = $today;

        $list = $this->mymodel->selectWithQuery("SELECT * FROM endorse WHERE status = 'Aktif' AND status_campaign = 'Aktif' AND (DATE(sync_at) < '$today' OR DATE(sync_at) IS NULL) AND link_upload != '' LIMIT 10");

        foreach ($list as $kl => $vl) {

            $id_endorse = $vl['id'];
            $v = $vl;
            $today = DATE("Y-m-d");
            $yesterday = DATE('Y-m-d', strtotime($today . " -1 days"));

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

            $dts = array();
            $dts['sync_at'] = DATE("Y-m-d H:i:s");
            if ($response['data']['created_at']) {
                $dts['posting_at'] = $response['data']['created_at'];
            }

            $this->db->update('endorse', $dts, array('id' => $v['id']));

            // if ($response['data']['view'] > 0) {

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
                $id_influencer = $vl['influencer'];
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



            $dtt = $dt;
            unset($dt['is_fyp']);
            unset($dtt['id_endorse']);
            unset($dtt['id_campaign']);
            unset($dtt['date']);
            $dtt['updated_at'] = DATE("Y-m-d H:i:s");

            $this->db->update('endorse', $dtt, array('id' => $id_endorse));


            if ($v['total_cost'] > 0 && $dt['views'] > 0) {
                $dt['cpm'] = doubleval($v['total_cost']) / doubleval($dt['views']) * 1000;
            } else {
                $dt['cpm'] = 0;
            }

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
            // }

            // $dt['is_cron'] = '1';
            // print_r($dt);die;
            $dt['brand'] = strval($vl['brand']);

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

            $dt_tmp = array();
            foreach ($dtt as $kt => $vt) {
                $dt_tmp[$kt] = strval($vt);
            }
            $dtt = $dt_tmp;

            $dtt['updated_at'] = DATE("Y-m-d H:i:s");
            $dtt['updated_by'] = strval($user['id']);
            $this->db->update('endorse', $dtt, array('id' => $v['id']));
        }

        $data = $this->mymodel->selectWithQuery("SELECT id
        FROM endorse_campaign 
        WHERE status = 'Aktif'");
        foreach ($data as $k => $v) {
            $id_parent = $v['id'];
            $this->update_endorse_parent($id_parent, $v);
        }

        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = true;
        $html['data'] = array();
        $html['msg'] = count($list) . " data endorse yg di sync <= $todayy berhasil diperbarui";
        echo json_encode($html, true);
        die;
    }

    function update_endorse_parent($id_parent, $detail)
    {
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
        $dt['status'] = strval($detail['status']);

        $campaign = $this->mymodel->selectDataOne('endorse_campaign', array('id' => $id_parent));
        $dt['brand'] = strval($campaign['brand']);


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

        $dtt['updated_at'] = DATE("Y-m-d H:i:s");
        $dtt['updated_by'] = strval($user['id']);
        $this->db->update('endorse_campaign', $dtt, array('id' => $v['id']));
    }

    function cronjob_endorse_campaign()
    {


        $user = $_SESSION['user'];

        $mode = strval($_GET['mode']);

        $target = DATE("Y-m-d 11:00:00");
        $now = DATE("Y-m-d H:i:s");
        if ($mode != 'true') {
            if ($now >= $target) {
                // SKIP
            } else {
                header('Content-Type: application/json; charset=utf-8');
                $html = array();
                $html['status'] = false;
                $html['data'] = array();
                $html['msg'] = "Bhskin endorse campaign cronjob will be processed at " . $target . "!";
                echo json_encode($html, true);
                die;
            }
        }
        $today = DATE("Y-m-d");
        $today = DATE('Y-m-d', strtotime($today . " -1 days"));
        $todayy = $today;

        $list = $this->mymodel->selectWithQuery("SELECT * FROM endorse_campaign WHERE status = 'Aktif' LIMIT 10");

        foreach ($list as $kl => $vl) {
            $id_campaign = $vl['id'];

            $id_parent = $vl['id'];
            $this->update_endorse_parent($id_parent, $vl);
        }



        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = true;
        $html['data'] = array();
        $html['msg'] = count($list) . " data endorse campaign yg di sync <= $todayy berhasil diperbarui";
        echo json_encode($html, true);
        die;
    }
    public function webhook()
    {
        date_default_timezone_set('Asia/Jakarta');
        header('Content-Type: application/json; charset=utf-8');
        $dt['marketplace'] = strval($_GET['marketplace']);
        $dt['get'] = json_encode($_GET, true);
        $dt['post'] = json_encode($_POST, true);
        $dt['input'] = file_get_contents("php://input");
        $dt['key'] = strval($_SERVER['HTTP_X_API_KEY']);
        $dt['method'] = strval($_SERVER['REQUEST_METHOD']);
        $dt['created_at'] = DATE("Y-m-d H:i:s");
        $dt['is_live'] = 'true';

        $json = json_decode($dt['input'], true);

        $order_id = $json['data']['order_id'];

        if ($order_id == "") {
            $order_id = $json['data']['ordersn'];
        }
        if ($order_id == "") {
            $order_id = $json['data']['content']['content']['source_content'];
        }
        if ($order_id == "") {
            $order_id = $json['data']['trade_order_id'];
        }

        $shop_id = $json['shop_id'];
        if ($shop_id == "") {
            $shop_id = $json['seller_id'];
        }

        $dt['order_id'] = strval($order_id);
        $dt['shop_id'] = strval($shop_id);
        $json = array();
        if ($dt['order_id']) {
            $this->db->insert('webhook', $dt);
            $dtt = array();
            $dtt['order_id'] = strval($order_id);
            $dtt['shop_id'] = strval($shop_id);
            $dtt['marketplace'] = strval($dt['marketplace']);
            $html = array();
            $html['status'] = true;
            $html['data'] = $dtt;
            $html['msg'] = "Bhskin webhook live access has been successful!";
            echo json_encode($html, true);
            die;
        } else {
            $html = array();
            $html['status'] = false;
            $html['data'] = array();
            $html['msg'] = "Bhskin webhook live access has been unsuccessful!";
            echo json_encode($html, true);
            die;
        }
    }

    function marketplace_order_download()
    {


        if ($_GET['start_date']) {
            $start_date = $_GET['start_date'];
        } else {
            $start_date = DATE('Y-m-d');
            $start_date = DATE('Y-m-d', strtotime($start_date . " -31 days"));
        }
        if ($_GET['until_date']) {
            $until_date = $_GET['until_date'];
        } else {
            $until_date = DATE('Y-m-d');
        }
        $brand = $_GET['brand'];
        $marketplace = $_GET['marketplace'];
        $cs = $_GET['cs'];
        $keyword = $_GET['keyword'];
        $id = $_GET['id'];
        $order_status = $_GET['order_status'];
        $keyword_category = $_GET['keyword_category'];

        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $data['brand'] = $brand;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM user WHERE role = '3' 
        ORDER BY full_name ASC
        ");

        $data['cs'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM product WHERE status = 'Aktif'
        ORDER BY sku ASC
        ");

        $data['product'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM shipping ORDER BY name ASC");

        $data['shipping'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM marketplace ORDER BY name ASC");

        $data['marketplace'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM brand ORDER BY code ASC");

        $data['brands'] = $query;

        $qry = "";
        $qry = " DATE(date) >= '$start_date'
        AND DATE(date) <= '$until_date' ";

        if ($id) {
            $qry .= " AND customer = '$id' ";
        }

        $ids = $_GET['ids'];
        $data['ids'] = $ids;
        if ($ids) {
            $qry .= " AND id  IN ($ids) ";
        }



        if ($brand) {
            $qry .= " AND brand = '$brand' ";
        }
        $ekspedisi = $_GET['ekspedisi'];
        if ($ekspedisi) {
            $qry .= " AND shipping = '$ekspedisi' ";
        }
        if ($marketplace) {
            $qry .= " AND marketplace = '$marketplace' ";
        }

        if ($cs) {
            $qry .= " AND cs = '$cs' ";
        }

        if ($order_status) {
            if ($order_status == "WEBHOOK") {
                $qry .= " AND is_webhook = 0 AND is_manual = 0";
            } else if ($order_status == "ACTIVE") {
                $qry .= " AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') ";
            } else if ($order_status == "READY_TO_SHIP") {
                $qry .= " AND order_status IN ('READY_TO_SHIP','PENDING') ";
            } else if ($order_status == "UNPAID") {
                $qry .= " AND payment_status = 'Unpaid' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED') ";
            } else if ($order_status == "SETTLEMENT") {
                $qry .= " AND dana_pencairan > 0 AND is_disbursement > 0 ";
            } else if ($order_status == "CANCELLED") {
                $qry .= " AND order_status IN ('CANCELLED','IN_CANCEL') ";
            } else {
                $qry .= " AND order_status = '$order_status' ";
            }
        }

        if ($keyword) {
            if ($keyword_category == "Order ID") {
                $qry .= " AND order_id LIKE '%$keyword%' ";
            } else if ($keyword_category == "Username") {
                $qry .= " AND c_username LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nama Pelanggan") {
                $qry .= " AND customer_text LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nomor Pelanggan") {
                $qry .= " AND phone LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nomor Resi") {
                $qry .= " AND awb_number LIKE '%$keyword%' ";
            } else if ($keyword_category == "Nama Produk") {
                $qry .= " AND pesanan LIKE '%$keyword%' ";
            }
        }

        $order_type = $_GET['order_type'];
        $data['order_type'] = $order_type;
        if ($order_type == "Manual") {
            $qry .= " AND is_manual = 1 ";
        } else if ($order_type == "Marketplace") {
            $qry .= " AND is_manual = 0 ";
        }


        $filename = 'ORDER.';
        if ($marketplace) {
            $filename .= $marketplace . '.';
        }
        $filename .=  $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date);


        $user = $_SESSION['user'];
        $dt = array();
        $dt['title'] = $filename;
        $dt['created_at'] = DATE("Y-m-d H:i:s");
        $dt['created_by'] = strval($user['id']);
        $dt['param'] = $this->template->get_param();
        // print_r($dtc);die;
        $this->db->insert('download_file', $dt);
        $id = $this->db->insert_id();

        // $title .= $filename . '.' . $id . '';
        $filename .= '.' . $id . '.xlsx';

        // Set the file path where the spreadsheet will be saved
        $file_path = str_replace('public/', '', FCPATH . 'assets/webfile/excel/') . $filename;


        $dt = array();
        $dt['title'] = $filename;
        $dt['file'] = $file_path;

        $this->db->update('download_file', $dt, array('id' => $id));

        $query = $this->mymodel->selectWithQuery("SELECT * FROM transaction
    WHERE $qry 
    -- AND order_status NOT IN ('CANCELLED','IN_CANCEL') 
    AND type_sub = 'POS' 
    ORDER BY date DESC, id DESC
    ");
        $data['data'] = $query;
        $this->spreadsheet = new Spreadsheet();

        $this->spreadsheet->getProperties()
            ->setCreator('KARYA STUDIO TEKNOLOGI DIGITAL')
            ->setLastModifiedBy('KARYA STUDIO TEKNOLOGI DIGITAL')
            ->setTitle('ORDER.' . $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date))
            ->setSubject('ORDER.' . $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date))
            ->setDescription('ORDER.' . $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date))
            ->setKeywords('ORDER.' . $this->template->date_format($start_date) . '.' . $this->template->date_format($until_date));



        $style_col = array(
            'font' => array('bold' => true),
            'alignment' => array(
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ),
            'borders' => array(
                'top' => array('style'  => Border::BORDER_THIN),
                'right' => array('style'  => Border::BORDER_THIN),
                'bottom' => array('style'  => Border::BORDER_THIN),
                'left' => array('style'  => Border::BORDER_THIN)
            ),
            'fill' => array(
                'type' => Fill::FILL_SOLID,
                'color' => array('rgb' => 'aeb5bc')
            ),
        );

        $style_row = array(
            'alignment' => array(
                'vertical' => Alignment::VERTICAL_CENTER
            ),
            'borders' => array(
                'top' => array('style'  => Border::BORDER_THIN),
                'right' => array('style'  => Border::BORDER_THIN),
                'bottom' => array('style'  => Border::BORDER_THIN),
                'left' => array('style'  => Border::BORDER_THIN)
            )
        );


        $query = $this->mymodel->selectWithQuery("SELECT * FROM product
    ORDER BY brand ASC, sub_name ASC
    ");

        $data['product'] = $query;


        $data['header'] = array();

        $header_1 = array(
            "ID",
            "TGL ORDER",
            "TGL RTS",
            "ORDER ID",
            "BRAND",
            "KET",
            "KODE CS",
            "CB/CL",
            "NAMA",
            "NO HP",
            "USERNAME",
            "ALAMAT",
            "KAB",
            "PROV",
            "PESANAN",
        );

        $header_2 = array();

        foreach ($data['product'] as $k => $v) {
            $header_2[] = strtoupper($v['sub_name']);
        }

        $header_3 = array(
            "OMSET KOTOR",
            "DISKON & VOUCHER PENJUAL",
            "BIAYA LAINNYA",
            "OMSET BERSIH",
            "MARKETPLACE FEE",
            "AFFILIATE FEE",
            "TOTAL PENCAIRAN DANA",
            "IS_CAIR",
            "RETURN",
            "JENIS PEMBAYARAN",
            "JUMLAH",
            "TANGGAL TF",
            "TANGGAL CEK",
            "ACC",
            "EKSPEDISI",
            "NO RESI",
            "ALAMAT",
            "PROV",
            "KAB",
            "KEC",
            "CATATAN",
            "STATUS ORDER",
            // "IS_MANUAL",
        );

        // $data['header'] = array_merge($header_1, $header_2, $header_3);


        $body_1 = array(
            "id",
            "date",
            "rts_at",
            "order_id",
            "brand",
            "marketplace",
            "cs",
            "cb_cl",
            "customer_text",
            "phone",
            "c_username",
            "address",
            "city_text",
            "province_text",
            "pesanan",

        );

        $body_2 = array();

        foreach ($data['product'] as $k => $v) {
            $body_2[] = $v['id'];
        }

        $body_3  = array(
            "omset_kotor",
            "diskon_penjual",
            "biaya_lainnya",
            "omset_bersih",
            "marketplace_fee",
            "komisi_afiliasi",
            "dana_pencairan",
            "is_disbursement",
            "return",
            "payment_type",
            "dibayar",
            "pay_at",
            "check_at",
            "acc",
            "shipping",
            "awb_number",
            "address",
            "province_text",
            "city_text",
            "subdistrict_text",
            "desc",
            "order_status",
            // "is_manual",
        );

        $data['body'] = array_merge($body_1, $body_2, $body_3);



        $i = 0;
        foreach ($header_1 as $kk => $v) {
            $code = $this->template->get_name_from_number($i + 1) . '1';
            $this->spreadsheet->setActiveSheetIndex(0)
                ->setCellValue($code, $v);
            $this->spreadsheet->getActiveSheet()->getStyle($code)->getFont()->setBold(true);
            $this->spreadsheet->getActiveSheet()->getStyle($code)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('dcdcdb');
            $this->spreadsheet
                ->getActiveSheet()
                ->getStyle($code)
                ->getBorders()
                ->getOutline()
                ->setBorderStyle(Border::BORDER_THIN);
            $i++;
        }

        foreach ($header_2 as $kk => $v) {
            $code = $this->template->get_name_from_number($i + 1) . '1';
            $this->spreadsheet->setActiveSheetIndex(0)
                ->setCellValue($code, $v);
            $this->spreadsheet->getActiveSheet()->getStyle($code)->getFont()->setBold(true);
            $this->spreadsheet->getActiveSheet()->getStyle($code)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('ffff00');
            $this->spreadsheet
                ->getActiveSheet()
                ->getStyle($code)
                ->getBorders()
                ->getOutline()
                ->setBorderStyle(Border::BORDER_THIN);
            $i++;
        }

        foreach ($header_3 as $k => $v) {
            $code = $this->template->get_name_from_number($i + 1) . '1';
            $this->spreadsheet->setActiveSheetIndex(0)
                ->setCellValue($code, $v);
            $this->spreadsheet->getActiveSheet()->getStyle($code)->getFont()->setBold(true);
            $this->spreadsheet->getActiveSheet()->getStyle($code)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('dcdcdb');
            $this->spreadsheet
                ->getActiveSheet()
                ->getStyle($code)
                ->getBorders()
                ->getOutline()
                ->setBorderStyle(Border::BORDER_THIN);
            $i++;
        }

        $column = 2;
        foreach ($data['data'] as $k => $v) {
            //             $index = 2;

            //             $index_alpha = $this->template->get_name_from_number($index);
            //             $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v['date']);
            //             $this->spreadsheet->getActiveSheet()
            //             ->getStyle($index_alpha . $column)
            //             ->getAlignment()
            //             ->setWrapText(true)
            //             ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            //             $index++;

            //             $index_alpha = $this->template->get_name_from_number($index);
            //             $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v['order_id']);
            //             $this->spreadsheet->getActiveSheet()
            //             ->getStyle($index_alpha . $column)
            //             ->getAlignment()
            //             ->setWrapText(true)
            //             ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            //             $index++;

            //             $index_alpha = $this->template->get_name_from_number($index);
            //             $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v['brand']);
            //             $this->spreadsheet->getActiveSheet()
            //             ->getStyle($index_alpha . $column)
            //             ->getAlignment()
            //             ->setWrapText(true)
            //             ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            //             $index++;

            //             $index_alpha = $this->template->get_name_from_number($index);
            //             $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v['marketplace']);
            //             $this->spreadsheet->getActiveSheet()
            //             ->getStyle($index_alpha . $column)
            //             ->getAlignment()
            //             ->setWrapText(true)
            //             ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            //             $index++;

            //             $index_alpha = $this->template->get_name_from_number($index);
            //             $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v['cs']);
            //             $this->spreadsheet->getActiveSheet()
            //             ->getStyle($index_alpha . $column)
            //             ->getAlignment()
            //             ->setWrapText(true)
            //             ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            //             $index++;

            //             $index_alpha = $this->template->get_name_from_number($index);
            //             $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v['cb_cl']);
            //             $this->spreadsheet->getActiveSheet()
            //             ->getStyle($index_alpha . $column)
            //             ->getAlignment()
            //             ->setWrapText(true)
            //             ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            //             $index++;

            //             $index_alpha = $this->template->get_name_from_number($index);
            //             $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v['customer_text']);
            //             $this->spreadsheet->getActiveSheet()
            //             ->getStyle($index_alpha . $column)
            //             ->getAlignment()
            //             ->setWrapText(true)
            //             ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            //             $index++;

            //             $index_alpha = $this->template->get_name_from_number($index);
            //             $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v['phone']);
            //             $this->spreadsheet->getActiveSheet()
            //             ->getStyle($index_alpha . $column)
            //             ->getAlignment()
            //             ->setWrapText(true)
            //             ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            //             $index++;

            //             $index_alpha = $this->template->get_name_from_number($index);
            //             $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v['c_username']);
            //             $this->spreadsheet->getActiveSheet()
            //             ->getStyle($index_alpha . $column)
            //             ->getAlignment()
            //             ->setWrapText(true)
            //             ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            //             $index++;

            //             $index_alpha = $this->template->get_name_from_number($index);
            //             $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v['address']);
            //             $this->spreadsheet->getActiveSheet()
            //             ->getStyle($index_alpha . $column)
            //             ->getAlignment()
            //             ->setWrapText(true)
            //             ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            //             $index++;

            //             $index_alpha = $this->template->get_name_from_number($index);
            //             $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v['city_text']);
            //             $this->spreadsheet->getActiveSheet()
            //             ->getStyle($index_alpha . $column)
            //             ->getAlignment()
            //             ->setWrapText(true)
            //             ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            //             $index++;

            //             $index_alpha = $this->template->get_name_from_number($index);
            //             $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v['province_text']);
            //             $this->spreadsheet->getActiveSheet()
            //             ->getStyle($index_alpha . $column)
            //             ->getAlignment()
            //             ->setWrapText(true)
            //             ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            //             $index++;

            //             $list = json_decode($v['pesanan'],true) ;
            //             $v['pesanan'] = '';
            //                     foreach($list as $kk=>$vv){
            //                         $v['pesanan'] .= $vv['qty']."x ".$vv['item_name']."
            // ";
            //                     }
            //             $index_alpha = $this->template->get_name_from_number($index);
            //             $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v['pesanan']);
            //             $this->spreadsheet->getActiveSheet()
            //             ->getStyle($index_alpha . $column)
            //             ->getAlignment()
            //             ->setWrapText(true)
            //             ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            //             $index++;
            // break;
            $v['id'] = '';
            $index = 1;
            foreach ($body_1 as $k2 => $v2) {
                if ($v2 == "order_id") {
                    $v[$v2] = " " . $v[$v2];
                }
                if ($v2 == "phone") {
                    $v[$v2] = " " . $v[$v2];
                }
                if ($v2 == "pesanan") {
                    $list = json_decode($v[$v2], true);
                    $v[$v2] = '';
                    foreach ($list as $kk => $vv) {
                        $v[$v2] .= $vv['qty'] . "x " . $vv['item_name'] . "
";
                    }
                }
                $index_alpha = $this->template->get_name_from_number($index);
                $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v[$v2]);
                $this->spreadsheet->getActiveSheet()
                    ->getStyle($index_alpha . $column)
                    ->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
                $index++;
            }

            $json = json_decode($v['json'], true);

            foreach ($body_2 as $k2 => $v2) {
                $val = $json[$v2]['qty'];
                $index_alpha = $this->template->get_name_from_number($index);
                $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $val);
                $this->spreadsheet->getActiveSheet()
                    ->getStyle($index_alpha . $column)
                    ->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
                $index++;
            }
            foreach ($body_3 as $k2 => $v2) {
                $index_alpha = $this->template->get_name_from_number($index);
                $this->spreadsheet->setActiveSheetIndex(0)->setCellValue($index_alpha . $column, $v[$v2]);
                $this->spreadsheet->getActiveSheet()
                    ->getStyle($index_alpha . $column)
                    ->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
                $index++;
            }

            $column++;
        }

        $sheet = $this->spreadsheet->getActiveSheet();
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }

        $writer = new Xlsx($this->spreadsheet);

        $writer->save($file_path);

        if (file_exists($file_path)) {
            echo "File saved successfully.";
            $dt = array();
            $dt['updated_at'] = date("Y-m-d H:i:s");
            $this->db->update('download_file', $dt, array('id' => $id));
        } else {
            echo "Error saving the file.";
        }
    }
}
