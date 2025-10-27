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
class Api extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->helper('env');
        $this->app_secret_tiktok = env('TIKTOK_APP_SECRET', '');
    }

    function sync()
    {
        $data = $this->mymodel->selectWithQuery("SELECT * FROM product_3rd_live");
        foreach ($data as $k => $v) {

            echo '---------PRODUCT---------';
            echo '<br>';
            $id_product = $v['id_product'];
            $detail = $this->mymodel->selectDataOne('product_3rd', array('id_product' => $id_product));
            if (empty($detail)) {
                $dt = array();
                unset($v['id']);
                $dt = $v;
                $this->db->insert('product_3rd', $dt);
                $product['id'] = $this->db->insert_id();
                echo 'Add product';
                echo '<br>';
                echo $v['name'];
                echo '<br>';
            } else {
                $product['id'] = $detail['id'];
            }
            echo '-------VARIAN-----------';
            echo '<br>';
            $list = json_decode($v['json_varian'], true);
            foreach ($list as $k2 => $v2) {
                $id_product = $v2['id_product'];
                $detail = $this->mymodel->selectDataOne('product_variant_3rd', array('id_product' => $id_product));
                if (empty($detail)) {
                    $dt = array();
                    unset($v2['id']);
                    $name = $v2['name'];
                    if (empty($name)) {
                        $name = $v['name'];
                    }
                    $v2['name'] = strval($name);
                    $v2['marketplace'] = $v['marketplace'];
                    $v2['shop_id'] = $v['shop_id'];
                    $v2['shop_name'] = $v['shop_name'];
                    $v2['id_parent'] = $product['id'];
                    $dt = $v2;
                    $this->db->insert('product_variant_3rd', $dt);
                    $product['id'] = $this->db->insert_id();
                    echo 'Add product variant';
                    echo '<br>';
                    echo $v2['name'];
                    echo '<br>';
                }
            }
        }
    }
    public function index()
    {
        $dt = $_GET;
        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = true;
        $html['data'] = $dt;
        $html['msg'] = "Acneno System REST API access has been successful!";
        echo json_encode($html, true);
    }

    function download_process()
    {


        if ($_GET['start_date']) {
            $start_date = $_GET['start_date'];
        } else {
            $start_date = DATE('Y-m-d');
            $start_date = date('Y-m-01');
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
            "TANGGAL",
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
    function generate_stock($js, $json, $v2, $dt, $query)
    {
        $order_id = $dt['order_id'];
        $marketplace = $dt['marketplace'];
        $brand = $dt['brand'];
        $webhook = $this->mymodel->selectWithQuery("SELECT *
        FROM webhook
        WHERE order_id = '$order_id' AND marketplace = '$marketplace'
        ORDER BY id DESC
        LIMIT 1
        ");
        $webhook = $webhook[0];
        $data_input = json_decode($webhook['input'], true);

        if ($dt['marketplace'] == "TIKTOK") {
            $cancel_at = DATE("Y-m-d H:i:s", $data_input['data']['update_time']);
        } else if ($dt['marketplace'] == "SHOPEE") {
            $cancel_at = DATE("Y-m-d H:i:s", $data_input['data']['update_time']);
        } else if ($dt['marketplace'] == "LAZADA") {
            $cancel_at = DATE("Y-m-d H:i:s", $data_input['data']['status_update_time']);
        }
        // print_r($json);die;
        // die;
        if ($query['id']) {

            $this->db->delete('stock_product_3rd', array('id_trx' => $query['id']));
            $this->db->delete('stock', array('id_trx' => $query['id']));
        }

        if (!in_array($dt['order_status'], array('UNPAID', 'CANCELLED', 'IN_CANCEL'))) {


            foreach ($js as $k2 => $v2) {
                $dts = array();
                $dts['order_status'] = strval($dt['order_status']);
                $dts['marketplace'] = strval($dt['marketplace']);
                $dts['brand'] = strval($dt['brand']);
                $dts['id_trx'] = strval($dt['id']);
                $dts['qty'] = 0 - abs($v2['qty']);
                // $dts['qty_out'] = abs($v2['qty']);
                $dts['qty_out_pos'] = abs($v2['qty']);
                $dts['qty_in'] = '0';
                $dts['marketplace'] = $v2['marketplace'];
                $dts['product_id'] = $v2['item_id'];
                $dts['product_sku'] = $v2['item_sku'];
                $dts['product_text'] = $v2['item_name'];
                $dts['varian_id'] = $v2['model_id'];
                $dts['varian_sku'] = $v2['model_sku'];
                $dts['varian_text'] = strval($v2['model_name']);
                $dts['code'] = $dt['order_id'];
                $dts['type'] = "Out";
                $dts['type_sub'] = "POS";
                $dts['created_at'] = DATE("Y-m-d H:i:s");
                $dts['date'] = $dt['date'];
                $dts['created_by'] = strval($user['id']);
                $dts['status'] = "Aktif";
                $dts['desc'] = "Penjualan";
                // print_r($dts);die;
                if ($dts['id_trx']) {
                    $this->db->insert('stock_product_3rd', $dts);
                }
                if (in_array($dt['order_status'], array('RETURN'))) {
                    $dts['date'] = strval($cancel_at);
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
            }
            foreach ($json as $k2 => $v2) {
                $dts = array();
                $dts['order_status'] = strval($dt['order_status']);
                $dts['marketplace'] = strval($dt['marketplace']);
                $dts['brand'] = strval($dt['brand']);
                $dts['id_trx'] = strval($dt['id']);
                $dts['qty'] = 0 - abs($v2['qty']);
                // $dts['qty_out'] = abs($v2['qty']);
                $dts['qty_out_pos'] = abs($v2['qty']);
                $dts['qty_in'] = '0';
                $dts['price'] = $v2['price'];
                $dts['hpp'] = $v2['hpp'];
                $dts['price_total'] = $v2['price_total'];
                $dts['product'] = $v2['product'];
                $dts['product_text'] = $v2['product_text'];
                $dts['sku'] = $v2['sku'];
                $dts['code'] = $dt['order_id'];
                $dts['type'] = "Out";
                $dts['type_sub'] = "POS";
                $dts['created_at'] = DATE("Y-m-d H:i:s");
                $dts['date'] = $dt['date'];
                $dts['created_by'] = strval($user['id']);
                $dts['status'] = "Aktif";
                if ($dts['id_trx']) {
                    $this->db->insert('stock', $dts);
                }
                if (in_array($dt['order_status'], array('CANCELLED', 'IN_CANCEL', 'RETURN', 'REFUND'))) {
                    $dts['date'] = strval($cancel_at);
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
            }
        }
    }
    function update_order()
    {

        $date = $_GET['date'];
        $data = $this->mymodel->selectWithQuery("SELECT id,marketplace,input FROM webhook
        -- WHERE DATE(created_at) = '$date' 
        -- AND 
        WHERE order_id = ''
        ");

        $arr = array();

        foreach ($data as $k => $v) {
            $dt = array();
            $json = json_decode($v['input'], true);
            $order_id = '';
            if ($v['marketplace'] == "TIKTOK") {
                $order_id = $json['data']['order_id'];
            } else if ($v['marketplace'] == "SHOPEE") {
                $order_id = $json['data']['ordersn'];
                if (empty($order_id)) {
                    $order_id = $json['data']['content']['content']['source_content'];
                }
            } else if ($v['marketplace'] == "LAZADA") {
                $order_id = $json['data']['trade_order_id'];
            }

            $dt['order_id'] = strval($order_id);
            $dt['marketplace'] = strval($v['marketplace']);

            if ($dt['order_id']) {
                $this->db->update('webhook', $dt, array('id' => $v['id']));
            } else {
                $this->db->delete('webhook', array('id' => $v['id']));
            }

            $arr[$k] = $dt;
        }

        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = true;
        $html['data'] = $arr;
        $html['msg'] = 'Get data success!';
        echo json_encode($html, true);
        die;
    }

    function update_customer_order()
    {
        $id_customer = $_GET['id_customer'];

        $this->customer_summary_v2($id_customer);

        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = true;
        $html['data'] = array();
        $html['msg'] = 'Pembaharuan customer order berhasil!';
        echo json_encode($html, true);
        die;
    }

    function get_order_detail()
    {

        $order_id = $_GET['order_id'];
        $marketplace = $_GET['marketplace'];
        $brand = $_GET['brand'];


        $is_manual = $_GET['is_manual'];
        if ($is_manual == "1") {
            $dt['order_id'] = $_GET['order_id'];
            $dt['marketplace'] = $_GET['marketplace'];
            $dt['brand'] = $_GET['brand'];
        } else {
            $webhook = $this->mymodel->selectWithQuery("SELECT id,marketplace,brand,order_id,shop_id,input
            FROM webhook
            WHERE order_id = '$order_id'
            -- AND marketplace = '$marketplace' AND brand = '$brand'
            ORDER BY id DESC
            LIMIT 1;
            ");
            $webhook = $webhook[0];
            $shop_id = $webhook['shop_id'];
            $json = json_decode($webhook['input'], true);
            $shop_id = $json['shop_id'];
            if ($shop_id == "") {
                $shop_id = $json['seller_id'];
            }

            if (empty($webhook)) {
                header('Content-Type: application/json; charset=utf-8');
                $html = array();
                $html['status'] = false;
                $html['data'] = array();
                $html['msg'] = 'Webhook not found!';
                echo json_encode($html, true);
                die;
            }

            $dt = $webhook;

            $id_webhook = $webhook['id'];
            $dtt = array();
            $dtt['hit_at'] = DATE("Y-m-d H:i:s");
            $this->db->update('webhook', $dtt, array('id' => $id_webhook));
            $dtt = array();

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
        }

        if ($dt['marketplace'] == "TIKTOK") {
            $marketplace = "TIKTOK";
            $data = json_decode($dt['input'], true);
            $id = $order_id;
            if ($id) {
                $query = $this->mymodel->selectWithQuery("SELECT * FROM transaction WHERE order_id = '$id'
                -- AND marketplace = '$marketplace'
                ");
                $query = $query[0];
                $trx = $query;
                //            if(empty($trx)){
                if (1 == 1) {

                    $product = $this->mymodel->selectWithQuery("SELECT * FROM product
                ORDER BY sku ASC
                ");
                    $product_arr = array();
                    foreach ($product as $k => $v) {
                        $product_arr[$v['id']] = $v;
                    }

                    if ($is_manual == "1") {
                        $config = $this->mymodel->selectWithQuery("SELECT val FROM marketplace_config WHERE opt = 'tiktok' AND brand = '$brand' ");
                    } else {
                        $config = $this->mymodel->selectWithQuery("SELECT val FROM marketplace_config WHERE opt = 'tiktok' AND shop_id = '$shop_id' ");
                    }
                    $config = $config[0];
                    $config = json_decode($config['val'], true);

                    $url = $config['partner_host'];

                    $appkey = $config['app_key'];
                    $appSecret = $config['app_secret'];
                    $config['access_token'];

                    $access_token = $config['access_token'];
                    $app_key = $config['app_key'];
                    $shop_id = $config['shop_id'];
                    $shop_cipher = $config['cipher'];

                    $list_id = '"' . $id . '"';

                    $url = 'https://open-api.tiktokglobalshop.com/api/orders/detail/query?access_token=' . $access_token . '&app_key=' . $app_key . '&shop_id=' . $shop_id . '&sign={{sign}}&timestamp={{timestamp}}&version=202305';
                    $urlParts = parse_url($url);
                    $paramGET = [];
                    parse_str($urlParts['query'], $paramGET);
                    $secret = $config['secret'];
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
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => '{"order_id_list":[' . $list_id . ']}',
                        CURLOPT_HTTPHEADER => array(
                            'Content-Type: application/json',
                            'x-tts-access-token: ' . $access_token
                        ),
                    ));
                    $response = curl_exec($curl);
                    header('Content-Type: application/json; charset=utf-8');
                    // echo $response;die;
                    curl_close($curl);
                    $response = json_decode($response, true);
                    $omset_kotor = $response['data']['order_list'][0]['payment_info']['original_total_product_price'];
                    // echo $omset_kotor;die;

                    if (empty($response['data']['order_list'][0]['order_id'])) {
                        $dt = array();
                        header('Content-Type: application/json; charset=utf-8');
                        $html = array();
                        $html['status'] = false;
                        $html['data'] = $dt;
                        $html['msg'] = "Data tidak ditemukan!";
                        echo json_encode($html, true);
                        die;
                    }
                    $nomor = 0;
                    foreach ($response['data']['order_list'] as $k3 => $v3) {
                        $nomor++;
                        // print_r($v3);die;

                        $marketplace = "TIKTOK";
                        $id_marketplace = $v3['order_id'];
                        $query = $this->mymodel->selectWithQuery("SELECT id,phone, order_status 
                        FROM transaction WHERE order_id = '$id_marketplace' AND marketplace = '$marketplace'
                        LIMIT 1
                        ");

                        $query = $query[0];
                        $dt = array();
                        $dt['date'] = strval(DATE("Y-m-d H:i:s", substr($v3['create_time'], 0, -3)));
                        $order_status = "COMPLETED";


                        $order_id = $id_marketplace;

                        $access_token = $config['access_token'];
                        $app_key = $config['app_key'];
                        $shop_id = $config['shop_id'];
                        $shop_cipher = $config['cipher'];

                        $url = 'https://open-api.tiktokglobalshop.com/api/finance/order/settlements?access_token=' . $access_token . '&app_key=' . $app_key . '&order_id=' . $order_id . '&shop_cipher=&shop_id=&sign={{sign}}&timestamp={{timestamp}}&version=202212';
                        $urlParts = parse_url($url);
                        $paramGET = [];
                        parse_str($urlParts['query'], $paramGET);
                        $secret = $config['secret'];
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
                            CURLOPT_POSTFIELDS => '%7B%7D=',
                            CURLOPT_HTTPHEADER => array(
                                'x-tts-access-token: ' . $access_token,
                                'Content-Type: application/x-www-form-urlencoded'
                            ),
                        ));
                        $response = curl_exec($curl);

                        curl_close($curl);
                        $response = json_decode($response, true);
                        $dt['omset_kotor'] = doubleval($omset_kotor);
                        $detail = $response['data']['settlement_list'][0]['settlement_info'];
                        if ($detail['settlement_amount']) {
                            // echo doubleval($v['price_total']);die;
                            // $dt = array();
                            $dt['komisi_afiliasi'] = doubleval($detail['affiliate_commission']);
                            $dt['omset_kotor'] = doubleval($dt['omset_kotor']);
                            $dt['diskon_penjual'] = abs(doubleval($detail['subtotal_after_seller_discounts']) - doubleval($dt['omset_kotor']));
                            $dt['omset_bersih'] = doubleval($detail['subtotal_after_seller_discounts']);
                            $dt['marketplace_fee'] = doubleval($detail['platform_commission']) + doubleval($detail['sfp_service_fee']);
                            $dt['dana_pencairan'] = doubleval($detail['settlement_amount']);
                            $dt['pencairan_status'] = '';
                            $dt['pencairan_at'] = DATE("Y-m-d H:i:s", ($detail['settlement_time']));
                        }

                        // print_r($dt);
                        // Use this field to obtain orders in a specific status
                        // - UNPAID = 100;
                        // - ON_HOLD = 105;
                        // - AWAITING_SHIPMENT = 111;
                        // - AWAITING_COLLECTION = 112;
                        // - PARTIALLY_SHIPPING = 114;
                        // - IN_TRANSIT = 121;
                        // - DELIVERED = 122;
                        // - COMPLETED = 130;
                        // - CANCELLED = 140;

                        if (in_array($v3['order_status'], array('100'))) {
                            $order_status = 'UNPAID';
                        } else if (in_array($v3['order_status'], array('112', '105'))) {
                            $order_status = 'PROCESSED';
                        } else if (in_array($v3['order_status'], array('returned'))) {
                            $order_status = 'RETURN';
                        } else if (in_array($v3['order_status'], array('140'))) {
                            $order_status = 'CANCELLED';
                        } else if (in_array($v3['order_status'], array('130',))) {
                            $order_status = 'COMPLETED';
                            $dt['disbursement_at'] = '';
                            $dt['is_disbursement'] = '1';
                        } else if (in_array($v3['order_status'], array('122'))) {
                            $order_status = 'COMPLETED';
                        } else if (in_array($v3['order_status'], array('121', '114'))) {
                            $order_status = 'SHIPPED';
                        } else if (in_array($v3['order_status'], array('111'))) {
                            $order_status = 'READY_TO_SHIP';
                        }
                        $dt['order_status'] = strval($order_status);
                        if ($v3['is_cod'] == true) {
                            $dt['payment_type'] = "COD";
                        } else {
                            $dt['payment_type'] = "TF";
                        }
                        $dt['type'] = "Out";
                        $dt['type_sub'] = "POS";
                        $dt['brand'] = strval($brand);
                        $dt['awb_number'] = strval($v3['tracking_number']);
                        $dt['order_id'] = strval($v3['order_id']);
                        $dt['shop_id'] = strval($config['shop_id']);
                        $dt['shop_name'] = strval($config['shop_name']);
                        $dt['shop_img'] = strval($config['shop_logo']);
                        $dt['cancel_by'] = strval($v3['cancel_by']);
                        $dt['is_webhook'] = "1";
                        $dt['dari_gudang'] = "1";
                        $dt['cancel_reason'] = strval($v3['cancel_reason']);
                        $dt['po_number'] = strval($v3['order_id']);
                        $dt['c_type'] = strval("Pelanggan");
                        $id_buyer = $v3['buyer_uid'];
                        //$query['phone'] = '';
                        if ($query['phone'] == "") {
                            $dt['customer_text'] = strval($v3['recipient_address']['name']);
                            $dt['phone'] = strval($v3['recipient_address']['phone']);
                            $dt['address'] = strval($v3['recipient_address']['address_detail']);
                            $dt['address_2'] = strval($v3['recipient_address']['address_detail']);
                            $dt['postal_code'] = strval($v3['recipient_address']['zipcode']);
                            $dt['province_text'] = strval($v3['district_info_list'][1]['address_name']);
                            $dt['city_text'] = strval($v3['district_info_list'][2]['address_name']);
                            $dt['subdistrict_text'] = strval($v3['district_info_list'][3]['address_name']);
                            $dt['c_username'] = strval($v3['customer_first_name']);

                            $customer = $this->mymodel->selectWithQuery("SELECT id FROM customer WHERE id_buyer = '$id_buyer' AND marketplace = '$marketplace'");
                            if (empty($customer)) {
                                $dtc = $customer;
                                $dtc['akun_type'] = "Pelanggan";
                                $dtc['brand'] = $dt['brand'];
                                $dtc['marketplace'] = $marketplace;
                                $dtc['id_buyer'] = strval($id_buyer);
                                $dtc['status'] = "Aktif";
                                $dtc['created_at'] = DATE("Y-m-d H:i:s");
                                $dtc['updated_at'] = DATE("Y-m-d H:i:s");
                                $dtc['created_by'] = strval($user['id']);
                                $dtc['full_name'] = $dt['customer_text'];
                                $dtc['phone'] = $dt['phone'];
                                $dtc['username'] = $dt['c_username'];
                                $dtc['count_order'] = 1;
                                $dtc['first_order'] = strval($dt['date']);
                                $dtc['last_order'] = strval($dt['date']);
                                $dtc['address'] = $dt['address'];
                                $dtc['province_text'] = $dt['province_text'];
                                $dtc['city_text'] = $dt['city_text'];
                                $dtc['subdistrict_text'] = $dt['subdistrict_text'];
                                // print_r($dtc);die;
                                $this->db->insert('customer', $dtc);
                                $dt['customer'] = $this->db->insert_id();
                            } else {
                                $dt['customer'] = $customer[0]['id'];
                            }
                        }
                        $dt['shipping'] = strval($v3['shipping_provider']);
                        $dt['shipping_price'] = strval($v3['payment_info']['shipping_fee']);

                        $price_total = $v3['payment_info']['original_total_product_price'];
                        $dt['omset_kotor'] = $v3['payment_info']['original_total_product_price'];
                        $dt['price'] = strval($price_total);
                        $dt['price_total'] = strval($price_total);
                        $dt['price_total_2'] = strval($price_total);
                        // print_r($dt);die;

                        $dt['customer_price'] = strval($v3['payment_info']['sub_total'] + $v3['payment_info']['shipping_fee']);
                        $dt['dibayar'] = $dt['customer_price'];
                        $dt['diskon_penjual'] = strval($v3['payment_info']['seller_discount']);

                        $dt['other_price'] = strval(0);
                        $dt['marketplace'] = strval($marketplace);
                        $dt['id_buyer'] = strval($v3['buyer_uid']);
                        $dt['bank'] = strval($v3['bank_name']);
                        $dt['cs_phone'] = strval($v3['print_sender_info']['phone']);

                        if ($v3['paid_time']) {
                            $dt['payment_status'] = strval("Paid");
                            $dt['pay_at'] = strval(DATE("Y-m-d H:i:s", substr($v3['paid_time'], 0, -3)));
                        } else {
                            $dt['payment_status'] = strval("Unpaid");
                        }
                        if ($v3['cancel_user']) {
                            $dt['cancel_by'] = strval($v3['cancel_user']);
                            $dt['cancel_reason'] = strval($v3['cancel_reason']);
                            // $dt['order_status'] = strval('RETURN'); 
                            // $dt['return_status'] = 'PENDING';
                        }



                        $dt['id_trx'] = strval($v3['order_id']);
                        $dt['id_marketplace'] = strval($v3['order_id']);
                        if ($v3['pickup_done_time']) {
                            $dt['pickup_at'] = strval(DATE("Y-m-d H:i:s", $v3['pickup_done_time']));
                        }
                        if ($v3['paid_time']) {
                            $dt['pay_at'] = strval(DATE("Y-m-d H:i:s", substr($v3['paid_time'], 0, 10)));
                        }
                        if ($v3['ship_by_date']) {
                            $dt['ship_by_at'] = strval(DATE("Y-m-d H:i:s", substr($v3['ship_by_date'], 0, 10)));
                        }
                        $dt['marketplace'] = $marketplace;

                        $js = array();
                        foreach ($v3['order_line_list'] as $k4 => $v4) {
                            // echo $k4;
                            $js[$k4]['item_id'] = $v4['product_id'];
                            $js[$k4]['item_sku'] = $v4['seller_sku'];
                            $js[$k4]['model_id'] = $v4['sku_id'];
                            $js[$k4]['model_sku'] = $v4['seller_sku'];
                            $js[$k4]['model_name'] = $v4['sku_name'];
                            $js[$k4]['item_name'] = $v4['product_name'];
                            $js[$k4]['qty'] = '1';
                            $js[$k4]['marketplace'] = $marketplace;
                        }
                        $js_new = array();
                        foreach ($js as $k4 => $v4) {
                            $js_new[$v4['model_id']]['qty'] += $v4['qty'];
                            $js_new[$v4['model_id']]['item_id'] = $v4['item_id'];
                            $js_new[$v4['model_id']]['item_sku'] = $v4['item_sku'];
                            $js_new[$v4['model_id']]['item_name'] = $v4['item_name'];
                            $js_new[$v4['model_id']]['model_id'] = $v4['model_id'];
                            $js_new[$v4['model_id']]['model_sku'] = $v4['model_sku'];
                            $js_new[$v4['model_id']]['model_name'] = $v4['model_name'];
                            $js_new[$v4['model_id']]['marketplace'] = $v4['marketplace'];
                        }
                        $j = 0;
                        $js = array();
                        foreach ($js_new as $k4 => $v4) {
                            $js[$j] = $v4;
                            $j++;
                        }

                        $dt['pesanan'] = json_encode($js, true);
                        $dt['pesanan_count'] = count($js);

                        $dt['id'] = $query['id'];

                        $json = array();
                        $price_total_hpp = 0;
                        foreach ($js as $k4 => $v4) {
                            $id_variant = $v4['model_id'];
                            $conf = $this->mymodel->selectWithQuery("SELECT json FROM product_variant_3rd
                                WHERE id_product = '$id_variant' LIMIT 1");
                            $conf = json_decode($conf[0]['json'], true);
                            if (empty($conf)) {
                                $id_variant = $v4['item_id'];
                                $conf = $this->mymodel->selectWithQuery("SELECT json FROM product_variant_3rd
                                    WHERE id_product = '$id_variant' LIMIT 1");
                                $conf = json_decode($conf[0]['json'], true);
                            }
                            // print_r($conf);die;
                            foreach ($conf as $k5 => $v5) {
                                $product = $product_arr[$v5['product']];
                                $price = 0;
                                if ($dt['akun_type'] == "Pelanggan") {
                                    $price = $product['price_normal'];
                                } else if ($dt['akun_type'] == "Distributor") {
                                    $price = $product['price_distributor'];
                                } else if ($dt['akun_type'] == "Reseller") {
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

                        $dt['hpp'] = doubleval($price_total_hpp);
                        $dt['json'] = json_encode($json, true);

                        // print_r($dt);die;
                        $run = 1;

                        // if($dt['payment_type']=="TF"){
                        //     if(in_array($dt['order_status'],array('UNPAID','CANCELLED','IN_CANCEL','PENDING'))){
                        //         $run = 0;
                        //     }
                        // }else{
                        //     if(in_array($dt['order_status'],array('CANCELLED','IN_CANCEL'))){
                        //         $run = 1;
                        //     }
                        // }

                        if ($run == 1) {
                            if ($query) {
                                if (in_array($query['order_status'], array('RETURN'))) {
                                    unset($dt['order_status']);
                                }
                                $dt['updated_at'] = DATE("Y-m-d H:i:s");
                                $dt['updated_by'] = strval($user['id']);
                                $this->db->update('transaction', $dt, array('id' => $query['id']));
                            } else {
                                $dt['created_at'] = DATE("Y-m-d H:i:s");
                                $dt['created_by'] = strval($user['id']);
                                $this->db->insert('transaction', $dt);
                                $dt['id'] = $this->db->insert_id();
                            }
                        }


                        $this->generate_stock($js, $json, $v2, $dt, $query);

                        //$this->customer_summary($dt['customer']);
                        // print_r($dt);
                        // echo '<br><br>';
                    }
                }
            }
            // if($data['type'] == "1"){
            //     $dtt = array();
            //     $dtt['updated_at'] = DATE("Y-m-d H:i:s");

            //     // The newest order status (support UNPAID, ON_HOLD, AWAITING_SHIPMENT, AWAITING_COLLECTION, CANCEL, IN_TRANSIT, DELIVERED, COMPLETED currently)
            //     // ON_HOLD status is currently only available in orders from US and UK markets.

            //     $order_status = "COMPLETED";
            //     if(in_array($data['data']['order_status'],array('UNPAID'))){
            //         $order_status = 'UNPAID';
            //     }else if(in_array($data['data']['order_status'],array('AWAITING_COLLECTION','ON_HOLD'))){
            //         $order_status = 'PROCESSED';
            //     }else if(in_array($data['data']['order_status'],array('CANCEL'))){
            //         $order_status = 'CANCELLED';
            //     }else if(in_array($data['data']['order_status'],array('COMPLETED',))){
            //         $order_status = 'COMPLETED';
            //         $dtt['disbursement_at'] = DATE("Y-m-d H:i:s",$data['data']['update_time']);
            //         $dtt['is_disbursement'] = '1';
            //     }else if(in_array($data['data']['order_status'],array('DELIVERED'))){
            //         $order_status = 'DELIVERED';
            //     }else if(in_array($data['data']['order_status'],array('IN_TRANSIT'))){
            //         $order_status = 'SHIPPED';
            //     }else if(in_array($data['data']['order_status'],array('AWAITING_SHIPMENT'))){
            //         $order_status = 'READY_TO_SHIP';
            //     }
            //     if($order_status){
            //         $dtt['order_status'] = $order_status;
            //     }

            //     if($trx['shipping']==""){

            //         $config = $this->mymodel->selectWithQuery("SELECT val FROM marketplace_config WHERE opt = 'tiktok' AND brand = '$brand' ");
            //         $config = $config[0];
            //         $config = json_decode($config['val'],true);

            //         $url = $config['partner_host'];

            //         $appkey = $config['app_key'];
            //         $appSecret = $config['app_secret'];
            //         $config['access_token'];

            //         $access_token = $config['access_token'];
            //         $app_key = $config['app_key'];
            //         $shop_id = $config['shop_id'];
            //         $shop_cipher = $config['cipher'];

            //         $list_id = '"'.$id.'"';

            //         $url = 'https://open-api.tiktokglobalshop.com/api/orders/detail/query?access_token='.$access_token.'&app_key='.$app_key.'&shop_id='.$shop_id.'&sign={{sign}}&timestamp={{timestamp}}&version=202305';
            //         $urlParts = parse_url($url);
            //         $paramGET = [];
            //         parse_str($urlParts['query'], $paramGET);
            //         $secret = $config['secret'];
            //         $timest = strtotime('now');
            //         $pr = array();
            //         $pr['secret'] = $secret;
            //         $pr['timest'] = $timest;
            //         $pr['get'] = $paramGET;
            //         $pr['url'] = $url;
            //         $sign = $this->tiktok_signature_generator($pr);

            //         $url = str_replace('{{sign}}',$sign,$url);
            //         $url = str_replace('{{timestamp}}',$timest,$url);

            //         $curl = curl_init();

            //         curl_setopt_array($curl, array(
            //         CURLOPT_URL => $url,
            //         CURLOPT_RETURNTRANSFER => true,
            //         CURLOPT_ENCODING => '',
            //         CURLOPT_MAXREDIRS => 10,
            //         CURLOPT_TIMEOUT => 0,
            //         CURLOPT_FOLLOWLOCATION => true,
            //         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            //         CURLOPT_CUSTOMREQUEST => 'POST',
            //         CURLOPT_POSTFIELDS =>'{"order_id_list":['.$list_id.']}',
            //         CURLOPT_HTTPHEADER => array(
            //             'Content-Type: application/json',
            //             'x-tts-access-token: '.$access_Token
            //         ),
            //         ));

            //         $response = curl_exec($curl);

            //         curl_close($curl);

            //         $response = json_decode($response,true);

            //         foreach($response['data']['order_list'] as $k3=>$v3){
            //             $dtt['awb_number'] = strval($v3['tracking_number']);
            //             $dtt['shipping'] = strval($v3['shipping_provider']);
            //         }
            //     }

            //     $dtt['updated_at'] = DATE("Y-m-d H:i:s");
            //     $model = $this->db->table('transaction');
            //     $model->where(array('id_marketplace'=>$id,'MARKETPLACE'=>$marketplace));
            //     $model->update($dtt);
            // }else if($data['type'] == "2"){
            //     //REVERSE
            //     $dtt = array();
            //     $dtt['updated_at'] = DATE("Y-m-d H:i:s");

            //     $reverse_type = "CANCEL";
            //     if($data['data']['reverse_type'] == "1"){
            //         $reverse_type = "CANCEL";
            //         $order_status = "CANCELLED";
            //     }else if($data['data']['reverse_type'] == "2"){
            //         $reverse_type = "REFUND";
            //         $order_status = "REFUND";
            //     }else if($data['data']['reverse_type'] == "3"){
            //         $reverse_type = "RETURN_AND_REFUND";
            //         $order_status = "RETURN";
            //     }else if($data['data']['reverse_type'] == "4"){
            //         $reverse_type = "REQUEST_CANCEL";
            //         // $order_status = "RETURN";
            //     }
            //     if($order_status){
            //         $dtt['order_status'] = $order_status;
            //     }

            //     $dtt['reverse_type'] = $reverse_type;

            //     $reverse_status = "AFTERSALE_APPLYING";
            //     if($data['data']['reverse_order_status'] == "1"){
            //         $reverse_status = "AFTERSALE_APPLYING";
            //     }else if($data['data']['reverse_order_status'] == "2"){
            //         $reverse_status = "AFTERSALE_REJECT_APPLICATION";
            //     }else if($data['data']['reverse_order_status'] == "3"){
            //         $reverse_status = "AFTERSALE_RETURNING";
            //     }else if($data['data']['reverse_order_status'] == "4"){
            //         $reverse_status = "AFTERSALE_BUYER_SHIPPED";
            //     }else if($data['data']['reverse_order_status'] == "5"){
            //         $reverse_status = "AFTERSALE_SELLER_REJECT_RECEIVE";
            //     }else if($data['data']['reverse_order_status'] == "50"){
            //         $reverse_status = "AFTERSALE_SUCCESS";
            //     }else if($data['data']['reverse_order_status'] == "51"){
            //         $reverse_status = "CANCEL_SUCCESS";
            //     }else if($data['data']['reverse_order_status'] == "99"){
            //         $reverse_status = "CLOSED";
            //     }else if($data['data']['reverse_order_status'] == "100"){
            //         $reverse_status = "COMPLETE";
            //     }

            //     $dtt['reverse_status'] = $reverse_status;

            //     $reverse_by = "BUYER";
            //     if($data['data']['reverse_user'] == "1"){
            //         $reverse_by = "BUYER";
            //     }else if($data['data']['reverse_user'] == "2"){
            //         $reverse_by = "SELLER";
            //     }else if($data['data']['reverse_user'] == "3"){
            //         $reverse_by = "OPERATOR";
            //     }else if($data['data']['reverse_user'] == "4"){
            //         $reverse_by = "SYSTEM";
            //     }
            //     $dtt['reverse_by'] = $reverse_by;
            //     $dtt['reverse_id'] = $data['data']['reverse_order_id'];
            //     $dtt['reverse_at'] = DATE("Y-m-d H:i:s",$data['data']['update_time']);
            //     $dtt['updated_at'] = DATE("Y-m-d H:i:s");
            //     $model = $this->db->table('transaction');
            //     $model->where(array('id_marketplace'=>$id,'MARKETPLACE'=>$marketplace));
            //     $model->update($dtt);
            // }else if($data['type'] == "11"){
            //     //CANCELLED
            //     $dtt = array();
            //     $dtt['updated_at'] = DATE("Y-m-d H:i:s");
            //     $order_status = "CANCELLED";
            //     $dtt['order_status'] = $order_status;
            //     $cancel_status = "PENDING";
            //     if($data['data']['cancel_status'] == "CANCELLATION_REQUEST_PENDING"){
            //         $cancel_status = "PENDING";
            //     }else if($data['data']['cancel_status'] == "CANCELLATION_REQUEST_SUCCESS"){
            //         $cancel_status = "SUCCESS";
            //     }else if($data['data']['cancel_status'] == "CANCELLATION_REQUEST_CANCELLED"){
            //         $cancel_status = "CANCELLED";
            //     }else if($data['data']['cancel_status'] == "CANCELLATION_REQUEST_COMPLETE"){
            //         $cancel_status = "COMPLETE";
            //     }
            //     $dtt['reverse_status'] = $cancel_status;
            //     $dtt['reverse_by'] = $data['data']['cancellations_role'];
            //     $dtt['reverse_id'] = $data['data']['cancel_id'];
            //     $dtt['reverse_at'] = DATE("Y-m-d H:i:s",$data['data']['update_time']);
            //     $dtt['updated_at'] = DATE("Y-m-d H:i:s");

            //     $model = $this->db->table('transaction');
            //     $model->where(array('id_marketplace'=>$id,'MARKETPLACE'=>$marketplace));
            //     $model->update($dtt);
            // }else if($data['type'] == "12"){
            //     //RETURN
            //     $dtt = array();
            //     $dtt['updated_at'] = DATE("Y-m-d H:i:s");
            //     $order_status = "RETURN";
            //     $dtt['order_status'] = $order_status;
            //     $dtt['reverse_status'] = $data['data']['return_status'];
            //     $dtt['reverse_by'] = $data['data']['return_role'];
            //     $dtt['return_sn'] = $data['data']['return_id'];
            //     $dtt['reverse_at'] = DATE("Y-m-d H:i:s",$data['data']['update_time']);
            //     $dtt['updated_at'] = DATE("Y-m-d H:i:s");
            //     $model = $this->db->table('transaction');
            //     $model->where(array('id_marketplace'=>$id,'MARKETPLACE'=>$marketplace));
            //     $model->update($dtt);
            // }
        } else if ($dt['marketplace'] == "SHOPEE") {
            $marketplace = "SHOPEE";
            $data = json_decode($dt['input'], true);
            $id = $order_id;
            if ($id) {
                $query = $this->mymodel->selectWithQuery("SELECT * FROM transaction WHERE order_id = '$id'
                --  AND marketplace = '$marketplace'
                 ");
                $query = $query[0];
                $trx = $query;
                //            if(empty($trx)){
                if (1 == 1) {

                    $product = $this->mymodel->selectWithQuery("SELECT * FROM product
        ORDER BY sku ASC
        ");
                    $product_arr = array();
                    foreach ($product as $k => $v) {
                        $product_arr[$v['id']] = $v;
                    }


                    if ($is_manual == "1") {
                        $config = $this->mymodel->selectWithQuery("SELECT val FROM marketplace_config WHERE opt = 'shopee' AND brand = '$brand' ");
                    } else {
                        $config = $this->mymodel->selectWithQuery("SELECT val FROM marketplace_config WHERE opt = 'shopee' AND shop_id = '$shop_id' ");
                    }
                    $config = $config[0];
                    $config = json_decode($config['val'], true);

                    $host = $config['partner_host'];
                    $partnerId = $config['partner_id'];
                    $partnerKey = $config['partner_key'];
                    $shopId = intval($config['shop_id']);
                    $refreshToken = $config['refresh_token'];


                    $path = "/api/v2/order/get_order_detail";
                    $timest = time();
                    $baseString = sprintf("%s%s%s%s%s", $partnerId, $path, $timest, $config['access_token'], $config['shop_id']);
                    $sign = hash_hmac('sha256', $baseString, $partnerKey);
                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $host . $path . '?access_token=' . $config['access_token'] . '&order_sn_list=' . $id . '&response_optional_fields=buyer_user_id,buyer_username,estimated_shipping_fee,recipient_address,actual_shipping_fee,goods_to_declare,note,note_update_time,item_list,pay_time,dropshipper,dropshipper_phone,split_up,buyer_cancel_reason,cancel_by,cancel_reason,actual_shipping_fee_confirmed,buyer_cpf_id,fulfillment_flag,pickup_done_time,package_list,shipping_carrier,payment_method,total_amount,buyer_username,invoice_data,no_plastic_packing,order_chargeable_weight_gram,edt,return_due_date&request_order_status_pending=true&partner_id=' . $config['partner_id'] . '&shop_id=' . $config['shop_id'] . '&sign=' . $sign . '&timestamp=' . $timest . '',
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

                    $omset_kotor = 0;
                    foreach ($response['response']['order_list'][0]['item_list'] as $kk => $vv) {
                        $omset_kotor += $vv['model_quantity_purchased'] * $vv['model_original_price'];
                    }

                    $curl = curl_init();

                    $path = "/api/v2/logistics/get_tracking_number";
                    $timest = time();
                    $baseString = sprintf("%s%s%s%s%s", $partnerId, $path, $timest, $config['access_token'], $config['shop_id']);
                    $sign = hash_hmac('sha256', $baseString, $partnerKey);
                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $host . $path . '?access_token=' . $config['access_token'] . '&order_sn=' . $id . '&partner_id=' . $config['partner_id'] . '&shop_id=' . $config['shop_id'] . '&sign=' . $sign . '&timestamp=' . $timest . '',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'GET',
                    ));

                    $response_awb = curl_exec($curl);
                    // echo $response_awb;die;
                    $response_awb = json_decode($response_awb, true);

                    curl_close($curl);



                    // echo json_encode($response['response']['order_list'][0],true);die;
                    // print_r($response['response']['order_list'][0]);die;

                    // if($response['message']){
                    //     $html['status'] = false;
                    //     $html['data'] = array();
                    //     $html['msg'] = $response['message'];
                    //     echo json_encode($html, true);
                    //     die;
                    // }



                    if (empty($response['response']['order_list'][0]['order_sn'])) {
                        $dt = array();
                        header('Content-Type: application/json; charset=utf-8');
                        $html = array();
                        $html['status'] = false;
                        $html['data'] = $dt;
                        $html['msg'] = "Data tidak ditemukan!";
                        echo json_encode($html, true);
                        die;
                    }

                    foreach ($response['response']['order_list'] as $k3 => $v3) {
                        // print_r($v3);die;
                        $marketplace = "SHOPEE";
                        $id_marketplace = $v3['order_sn'];
                        $query = $this->mymodel->selectWithQuery("SELECT id,phone, order_status 
                    FROM transaction WHERE order_id = '$id_marketplace' AND marketplace = '$marketplace'
                    LIMIT 1
                    ");

                        $query = $query[0];

                        $dt = array();


                        $list_id = $id_marketplace;

                        $host = $config['partner_host'];
                        $partnerId = $config['partner_id'];
                        $partnerKey = $config['partner_key'];
                        $shopId = intval($config['shop_id']);
                        $refreshToken = $config['refresh_token'];

                        $path = "/api/v2/payment/get_escrow_detail";
                        $timest = time();
                        $baseString = sprintf("%s%s%s%s%s", $partnerId, $path, $timest, $config['access_token'], $config['shop_id']);
                        $sign = hash_hmac('sha256', $baseString, $partnerKey);
                        $curl = curl_init();
                        curl_setopt_array($curl, array(
                            CURLOPT_URL => $host . $path . '?access_token=' . $config['access_token'] . '&order_sn=' . $list_id . '&response_optional_fields=buyer_user_id,buyer_username,estimated_shipping_fee,recipient_address,actual_shipping_fee,goods_to_declare,note,note_update_time,item_list,pay_time,dropshipper,dropshipper_phone,split_up,buyer_cancel_reason,cancel_by,cancel_reason,actual_shipping_fee_confirmed,buyer_cpf_id,fulfillment_flag,pickup_done_time,package_list,shipping_carrier,payment_method,total_amount,buyer_username,invoice_data,no_plastic_packing,order_chargeable_weight_gram,edt,return_due_date&request_order_status_pending=true&partner_id=' . $config['partner_id'] . '&shop_id=' . $config['shop_id'] . '&sign=' . $sign . '&timestamp=' . $timest . '',
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => '',
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 0,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => 'GET',
                        ));

                        $response = curl_exec($curl);

                        // echo $response;die;
                        curl_close($curl);
                        $response = json_decode($response, true);


                        $dt['omset_kotor'] = doubleval($omset_kotor);
                        $detail = $response['response']['order_income'];
                        if ($detail['escrow_amount']) {
                            // $dt = array();
                            // $dt['komisi_afiliasi'] = doubleval($detail['order_ams_commission_fee']);
                            // $dt['omset_kotor'] = doubleval($detail['original_price']);
                            // $dt['diskon_penjual'] = doubleval($detail['voucher_from_seller']);
                            // $dt['omset_bersih'] = doubleval($detail['original_price']) - doubleval($detail['voucher_from_seller']);
                            // $dt['marketplace_fee'] = doubleval($detail['commission_fee']) + doubleval($detail['service_fee']);
                            // $dt['dana_pencairan'] = doubleval($detail['escrow_amount']);
                            // $dt['pencairan_status'] = '';
                            // $dt['pencairan_at'] = DATE("Y-m-d H:i:s",($detail['settlement_time']));
                            $dt['komisi_afiliasi'] = doubleval($detail['order_ams_commission_fee']);
                            $dt['omset_kotor'] = doubleval($dt['omset_kotor']);
                            $dt['diskon_penjual'] = doubleval($detail['voucher_from_seller']);
                            $dt['omset_bersih'] = doubleval($dt['omset_kotor']) - doubleval($detail['voucher_from_seller']);
                            $dt['marketplace_fee'] = doubleval($detail['commission_fee']) + doubleval($detail['service_fee']);
                            $dt['dana_pencairan'] = doubleval($detail['escrow_amount']);
                            $dt['pencairan_status'] = '';
                            $dt['pencairan_at'] = DATE("Y-m-d H:i:s", ($detail['settlement_time']));
                            // print_r($dt);die;
                        }



                        $dt['date'] = strval(DATE("Y-m-d H:i:s", $v3['create_time']));

                        $order_status = $v3['order_status'];
                        if ($v3['order_status'] == "TO_CONFIRM_RECEIVE") {
                            $order_status = "DELIVERED";
                        } else if ($v3['order_status'] == "TO_RETURN") {
                            $order_status = "RETURN";
                        } else if ($v3['order_status'] == "RETRY_SHIP") {
                            $order_status = "PROCESSED";
                        }

                        if (!in_array($query['order_status'], array('CANCELLED', 'REFUND', 'RETURN'))) {
                            if ($order_status) {
                                $dt['order_status'] = strval($order_status);
                            }
                        }

                        if ($v3['cod']) {
                            $dt['payment_type'] = "COD";
                        } else {
                            $dt['payment_type'] = "TF";
                        }


                        // $path = "/api/v2/order/get_buyer_invoice_info";
                        // $timest = time();
                        // $baseString = sprintf("%s%s%s%s%s", $partnerId, $path, $timest, $config['access_token'], $config['shop_id']);
                        // $sign = hash_hmac('sha256', $baseString, $partnerKey);
                        // $curl = curl_init();
                        // curl_setopt_array($curl, array(
                        //     CURLOPT_URL => $host.$path.'?access_token='.$config['access_token'].'&partner_id='.$config['partner_id'].'
                        //     &shop_id='.$config['shop_id'].'&sign='.$sign.'&timestamp='.$timest,
                        //     CURLOPT_RETURNTRANSFER => true,
                        //     CURLOPT_ENCODING => '',
                        //     CURLOPT_MAXREDIRS => 10,
                        //     CURLOPT_TIMEOUT => 0,
                        //     CURLOPT_FOLLOWLOCATION => true,
                        //     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        //     CURLOPT_CUSTOMREQUEST => 'POST',
                        //     CURLOPT_POSTFIELDS =>'{
                        //         "queries": [
                        //            {
                        //               "order_sn": "220314U0G6UNMN"
                        //            }
                        //         ]
                        //      }',
                        //     CURLOPT_HTTPHEADER => array(
                        //     'Content-Type: application/json'
                        //     ),
                        // ));
                        // $response = curl_exec($curl);
                        // curl_close($curl);


                        // $response = json_decode($response,true);

                        $dt['type'] = "Out";
                        $dt['type_sub'] = "POS";
                        $dt['brand'] = strval($brand);
                        $dt['awb_number'] = strval($response_awb['response']['tracking_number']);
                        $dt['order_id'] = strval($v3['order_sn']);
                        $dt['shop_id'] = strval($config['shop_id']);
                        $dt['shop_name'] = strval($config['shop_name']);
                        $dt['shop_img'] = strval($config['shop_logo']);
                        $dt['cancel_by'] = strval($v3['cancel_by']);
                        $dt['is_webhook'] = "1";
                        $dt['dari_gudang'] = "1";
                        $dt['cancel_reason'] = strval($v3['cancel_reason']);
                        $dt['po_number'] = strval($v3['order_sn']);
                        $dt['c_type'] = strval("Pelanggan");
                        $id_buyer = $v3['buyer_user_id'];
                        //$query['phone'] = '';
                        if ($query['phone'] == "") {
                            $dt['customer_text'] = strval($v3['recipient_address']['name']);
                            $dt['phone'] = strval($v3['recipient_address']['phone']);
                            $dt['address'] = strval($v3['recipient_address']['full_address']);
                            $dt['address_2'] = strval($v3['recipient_address']['full_address']);
                            $dt['postal_code'] = strval($v3['recipient_address']['zipcode']);
                            $dt['province_text'] = strval($v3['recipient_address']['state']);
                            $dt['city_text'] = strval($v3['recipient_address']['city']);
                            $dt['subdistrict_text'] = strval($v3['recipient_address']['district']);
                            $dt['c_username'] = strval($v3['buyer_username']);
                            $customer = $this->mymodel->selectWithQuery("SELECT id FROM customer WHERE id_buyer = '$id_buyer' AND marketplace = '$marketplace'");
                            if (empty($customer)) {
                                $dtc = $customer;
                                $dtc['akun_type'] = "Pelanggan";
                                $dtc['brand'] = $dt['brand'];
                                $dtc['marketplace'] = $marketplace;
                                $dtc['id_buyer'] = strval($id_buyer);
                                $dtc['status'] = "Aktif";
                                $dtc['created_at'] = DATE("Y-m-d H:i:s");
                                $dtc['updated_at'] = DATE("Y-m-d H:i:s");
                                $dtc['created_by'] = strval($user['id']);
                                $dtc['full_name'] = $dt['customer_text'];
                                $dtc['phone'] = $dt['phone'];
                                $dtc['username'] = $dt['c_username'];
                                $dtc['count_order'] = 1;
                                $dtc['first_order'] = strval($dt['date']);
                                $dtc['last_order'] = strval($dt['date']);
                                $dtc['address'] = $dt['address'];
                                $dtc['province_text'] = $dt['province_text'];
                                $dtc['city_text'] = $dt['city_text'];
                                $dtc['subdistrict_text'] = $dt['subdistrict_text'];


                                $this->db->insert('customer', $dtc);
                                $dt['customer'] = $this->db->insert_id();
                            } else {
                                $dt['customer'] = $customer[0]['id'];
                            }
                        }
                        $dt['shipping'] = strval($v3['shipping_carrier']);
                        $dt['shipping_price'] = strval($v3['actual_shipping_fee']);

                        $price_total = 0;
                        foreach ($v3['item_list'] as $kk => $vv) {
                            $price_total += $vv['model_original_price'];
                        }
                        $dt['price'] = strval($price_total);
                        $dt['price_total'] = strval($price_total);
                        $dt['price_total_2'] = strval($price_total);

                        $dt['customer_price'] = strval($v3['total_amount']);
                        $dt['dibayar'] = $dt['customer_price'];

                        $dt['other_price'] = strval(0);
                        $dt['marketplace'] = strval($marketplace);
                        $dt['id_buyer'] = strval($v3['buyer_user_id']);
                        $dt['bank'] = strval($v3['bank_name']);
                        $dt['cs_phone'] = strval($v3['print_sender_info']['phone']);
                        if ($v3['pay_time']) {
                            $dt['payment_status'] = strval("Paid");
                        } else {
                            $dt['payment_status'] = strval("Unpaid");
                        }

                        $dt['payment_at'] = strval($v3['payment_date']);
                        $dt['id_trx'] = strval($v3['order_sn']);
                        $dt['id_marketplace'] = strval($v3['order_sn']);
                        if ($v3['pickup_done_time']) {
                            $dt['pickup_at'] = strval(DATE("Y-m-d H:i:s", $v3['pickup_done_time']));
                        }
                        if ($v3['pay_time']) {
                            $dt['pay_at'] = strval(DATE("Y-m-d H:i:s", $v3['pay_time']));
                        }
                        if ($v3['ship_by_date']) {
                            $dt['ship_by_at'] = strval(DATE("Y-m-d H:i:s", $v3['ship_by_date']));
                        }
                        $dt['marketplace'] = $marketplace;
                        $json = $v3['item_list'];
                        $js = array();
                        foreach ($json as $k4 => $v4) {
                            $js[$k4]['item_id'] = $v4['item_id'];
                            $js[$k4]['item_sku'] = $v4['item_sku'];
                            $js[$k4]['item_name'] = $v4['item_name'];
                            if (empty($v4['model_id'])) {
                                $v4['model_id'] = $v4['item_id'];
                                $v4['model_sku'] = $v4['item_sku'];
                                $v4['model_name'] = $v4['item_name'];
                            }
                            $js[$k4]['model_id'] = $v4['model_id'];
                            $js[$k4]['model_sku'] = $v4['model_sku'];
                            $js[$k4]['model_name'] = $v4['model_name'];
                            $js[$k4]['qty'] = $v4['model_quantity_purchased'];
                            $js[$k4]['marketplace'] = $marketplace;
                        }

                        $js_new = array();
                        foreach ($js as $k4 => $v4) {
                            $js_new[$v4['model_id']]['qty'] += $v4['qty'];
                            $js_new[$v4['model_id']]['item_id'] = $v4['item_id'];
                            $js_new[$v4['model_id']]['item_sku'] = $v4['item_sku'];
                            $js_new[$v4['model_id']]['item_name'] = $v4['item_name'];
                            $js_new[$v4['model_id']]['model_id'] = $v4['model_id'];
                            $js_new[$v4['model_id']]['model_sku'] = $v4['model_sku'];
                            $js_new[$v4['model_id']]['model_name'] = $v4['model_name'];
                            $js_new[$v4['model_id']]['marketplace'] = $v4['marketplace'];
                        }

                        $j = 0;
                        $js = array();
                        foreach ($js_new as $k4 => $v4) {
                            $js[$j] = $v4;
                            $j++;
                        }


                        $dt['pesanan'] = json_encode($js, true);
                        $dt['pesanan_count'] = count($js);
                        $dt['id'] = $query['id'];

                        $json = array();
                        $price_total_hpp = 0;
                        foreach ($js as $k4 => $v4) {
                            $id_variant = $v4['model_id'];
                            $conf = $this->mymodel->selectWithQuery("SELECT json FROM product_variant_3rd
                        WHERE id_product = '$id_variant' LIMIT 1");
                            $conf = json_decode($conf[0]['json'], true);
                            if (empty($conf)) {
                                $id_variant = $v4['item_id'];
                                $conf = $this->mymodel->selectWithQuery("SELECT json FROM product_variant_3rd
                            WHERE id_product = '$id_variant' LIMIT 1");
                                $conf = json_decode($conf[0]['json'], true);
                            }
                            foreach ($conf as $k5 => $v5) {
                                $product = $product_arr[$v5['product']];
                                $price = 0;
                                if ($dt['akun_type'] == "Pelanggan") {
                                    $price = $product['price_normal'];
                                } else if ($dt['akun_type'] == "Distributor") {
                                    $price = $product['price_distributor'];
                                } else if ($dt['akun_type'] == "Reseller") {
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

                        $dt['hpp'] = doubleval($price_total_hpp);
                        $dt['json'] = json_encode($json, true);
                        // print_r($dt);die;
                        $run = 1;

                        // if($dt['payment_type']=="TF"){
                        //     if(in_array($dt['order_status'],array('UNPAID','CANCELLED','IN_CANCEL','PENDING'))){
                        //         $run = 0;
                        //     }
                        // }else{
                        //     if(in_array($dt['order_status'],array('CANCELLED','IN_CANCEL'))){
                        //         $run = 1;
                        //     }
                        // }

                        if ($run == 1) {
                            if ($query) {
                                if (in_array($query['order_status'], array('RETURN'))) {
                                    unset($dt['order_status']);
                                }
                                $dt['updated_at'] = DATE("Y-m-d H:i:s");
                                $dt['updated_by'] = strval($user['id']);

                                $this->db->update('transaction', $dt, array('id' => $query['id']));
                            } else {
                                $dt['created_at'] = DATE("Y-m-d H:i:s");
                                $dt['created_by'] = strval($user['id']);

                                $this->db->insert('transaction', $dt);
                                $dt['id'] = $this->db->insert_id();
                            }
                        }
                        $this->generate_stock($js, $json, $v2, $dt, $query);


                        //$this->customer_summary($dt['customer']);
                    }
                }
            }
            // if($data['code']=="3"){
            //     $order_status = $data['data']['status'];
            //     if($order_status == "COMPLETED"){
            //         $dtt['disbursement_at'] = DATE("Y-m-d H:i:s",$data['data']['update_time']);
            //         $dtt['is_disbursement'] = '1';
            //     }
            //     if($order_status == "TO_CONFIRM_RECEIVE"){
            //         $order_status = 'DELIVERED';
            //     }
            //     $dtt['order_status'] = $order_status;
            //     $dtt['updated_at'] = DATE("Y-m-d H:i:s");
            //     $model = $this->db->table('transaction');
            //     $model->where(array('id_marketplace'=>$id,'MARKETPLACE'=>$marketplace));
            //     $model->update($dtt);
            // }else if($data['code']=="4"){
            //     $dtt['forder_id'] = $data['data']['forder_id'];
            //     $dtt['package_number'] = $data['data']['package_number'];
            //     $dtt['tracking_no'] = $data['data']['tracking_no'];
            //     $dtt['awb_number'] = $data['data']['tracking_no'];
            //     $dtt['updated_at'] = DATE("Y-m-d H:i:s");
            //     $model = $this->db->table('transaction');
            //     $model->where(array('id_marketplace'=>$id,'MARKETPLACE'=>$marketplace));
            //     $model->update($dtt);
            // }
        } else if ($dt['marketplace'] == "LAZADA") {
            $marketplace = "LAZADA";
            $data = json_decode($dt['input'], true);
            $id = $order_id;
            if ($id) {
                $query = $this->mymodel->selectWithQuery("SELECT * FROM transaction WHERE order_id = '$id'
                --  AND marketplace = '$marketplace'
                 ");
                $query = $query[0];
                $trx = $query;
                //            if(empty($trx)){
                if (1 == 1) {

                    $product = $this->mymodel->selectWithQuery("SELECT * FROM product
                        ORDER BY sku ASC
                        ");
                    $product_arr = array();
                    foreach ($product as $k => $v) {
                        $product_arr[$v['id']] = $v;
                    }


                    if ($is_manual == "1") {
                        $config = $this->mymodel->selectWithQuery("SELECT val FROM marketplace_config WHERE opt = 'lazada' AND brand = '$brand' ");
                    } else {
                        $config = $this->mymodel->selectWithQuery("SELECT val FROM marketplace_config WHERE opt = 'lazada' AND shop_id = '$shop_id' ");
                    }
                    $config = $config[0];
                    $config = json_decode($config['val'], true);

                    $url = $config['partner_host'];

                    $appkey = $config['app_key'];
                    $appSecret = $config['app_secret'];
                    $config['access_token'];

                    $c = new LazopClient($url, $appkey, $appSecret);
                    $request = new LazopRequest('/order/get', 'GET');
                    $request->addApiParam('order_id', $id);

                    $response = $c->execute($request, $config['access_token']);

                    $responses = json_decode($response, true);

                    $omset_kotor = $responses['data']['price'];

                    $total_data = 1;
                    $response = array();
                    $response['data']['orders'][0] = $responses['data'];
                    // print_r($response['data']['orders']);die;
                    if (empty($response['data']['orders'][0]['order_number'])) {
                        $dt = array();
                        header('Content-Type: application/json; charset=utf-8');
                        $html = array();
                        $html['status'] = false;
                        $html['data'] = $dt;
                        $html['msg'] = "Data tidak ditemukan!";
                        echo json_encode($html, true);
                        die;
                    }

                    foreach ($response['data']['orders'] as $k3 => $v3) {
                        $nomor++;

                        // $v3['order_number'] = '1407814792257102';
                        $c = new LazopClient($url, $appkey, $appSecret);
                        $request = new LazopRequest('/logistic/order/trace');
                        $request->addApiParam('order_id', $v3['order_number']);
                        $response_shipping = $c->execute($request, $config['access_token']);
                        $response_shipping = json_decode($response_shipping, true);

                        $c = new LazopClient($url, $appkey, $appSecret);
                        $request = new LazopRequest('/order/items/get', 'GET');
                        $request->addApiParam('order_id', $v3['order_number']);
                        $response_item = $c->execute($request, $config['access_token']);
                        // echo $response_item;die;
                        $response_item = json_decode($response_item, true);

                        $segments = explode(',', $response_item['data'][0]['shipment_provider']);
                        $segments = explode(': ', $segments[0]);
                        $shipment_provider = $segments[1];
                        if (empty($shipment_provider)) {
                            $shipment_provider = $segments[0];
                        }

                        $marketplace = "LAZADA";
                        $id_marketplace = $v3['order_number'];
                        $query = $this->mymodel->selectWithQuery("SELECT id,phone, order_status 
                        FROM transaction WHERE order_id = '$id_marketplace' AND marketplace = '$marketplace'
                        LIMIT 1
                        ");

                        $query = $query[0];

                        $dt = array();

                        $dt['date'] = strval(substr($v3['created_at'], 0, 19));

                        $order_id = $id_marketplace;

                        $url = $config['partner_host'];

                        $appkey = $config['app_key'];
                        $appSecret = $config['app_secret'];
                        $config['access_token'];

                        $start_date = date("Y-m-01", strtotime($dt['date']));
                        $until_date = date("Y-m-t", strtotime($start_date . " +1 months"));
                        $c = new LazopClient($url, $appkey, $appSecret);
                        $request = new LazopRequest('/finance/transaction/details/get', 'GET');
                        $request->addApiParam('offset', '0');
                        $request->addApiParam('trade_order_id', $order_id);
                        $request->addApiParam('limit', '100');
                        $request->addApiParam('start_time', $start_date);
                        $request->addApiParam('end_time', $until_date);
                        $response_vat = $c->execute($request, $config['access_token']);

                        $response_vat = json_decode($response_vat, true);
                        // die;
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
                        }

                        $dt['omset_kotor'] = doubleval($omset_kotor);
                        if (doubleval($price_total - $price_admin - $diskon_penjual) > 0) {
                            // $dt = array();
                            $dt['komisi_afiliasi'] = doubleval(0);
                            $dt['omset_kotor'] = doubleval($dt['omset_kotor']);
                            $dt['diskon_penjual'] = doubleval($diskon_penjual);
                            $dt['omset_bersih'] = doubleval($dt['omset_kotor']) - doubleval($diskon_penjual);
                            $dt['marketplace_fee'] = doubleval($price_admin);
                            $dt['dana_pencairan'] = doubleval($dt['omset_kotor'] - $price_admin - $diskon_penjual);
                            $dt['pencairan_status'] = '';
                            $dt['pencairan_at'] = DATE("Y-m-d H:i:s", ($detail['settlement_time']));
                        }

                        // print_r($dt);die;
                        // $dt['date'] = strval(DATE("Y-m-d H:i:s",$v3['created_at'])); 


                        $order_status = "COMPLETED";
                        if (in_array($v3['statuses'][0], array('unpaid'))) {
                            $order_status = 'UNPAID';
                        } else if (in_array($v3['statuses'][0], array('topack', 'pending'))) {
                            $order_status = 'PROCESSED';
                        } else if (in_array($v3['statuses'][0], array('returned'))) {
                            $order_status = 'RETURN';
                        } else if (in_array($v3['statuses'][0], array('canceled', 'failed', 'lost'))) {
                            $order_status = 'CANCELLED';
                        } else if (in_array($v3['statuses'][0], array('confirmed'))) {
                            $order_status = 'COMPLETED';
                            $dt['disbursement_at'] = '';
                            $dt['is_disbursement'] = '1';
                        } else if (in_array($v3['statuses'][0], array('delivered'))) {
                            $order_status = 'DELIVERED';
                        } else if (in_array($v3['statuses'][0], array('shipped'))) {
                            $order_status = 'SHIPPED';
                        } else if (in_array($v3['statuses'][0], array('ready_to_ship', 'toship', 'shipping'))) {
                            $order_status = 'READY_TO_SHIP';
                        }

                        $dt['order_status'] = strval($order_status);
                        if ($v3['payment_method'] == "COD") {
                            $dt['payment_type'] = "COD";
                        } else {
                            $dt['payment_type'] = "TF";
                        }
                        $dt['type'] = "Out";
                        $dt['type_sub'] = "POS";
                        $dt['brand'] = strval($brand);
                        $dt['awb_number'] = strval($response_shipping['result']['module'][0]['package_detail_info_list'][0]['tracking_number']);
                        $dt['order_id'] = strval($v3['order_number']);
                        $dt['shop_id'] = strval($config['shop_id']);
                        $dt['shop_name'] = strval($config['shop_name']);
                        $dt['shop_img'] = strval($config['shop_logo']);
                        $dt['cancel_by'] = strval($v3['cancel_by']);
                        $dt['is_webhook'] = "1";
                        $dt['dari_gudang'] = "1";
                        $dt['cancel_reason'] = strval($v3['cancel_reason']);
                        $dt['po_number'] = strval($v3['order_number']);
                        $dt['c_type'] = strval("Pelanggan");
                        $id_buyer = $response_item['data'][0]['buyer_id'];
                        //$query['phone'] = '';
                        if ($query['phone'] == "") {
                            $dt['customer_text'] = strval($v3['customer_first_name'] . ' ' . $v3['customer_last_name']);
                            $dt['phone'] = strval($v3['address_shipping']['phone']);
                            $dt['address'] = strval($v3['address_shipping']['address1']);
                            $dt['address_2'] = strval($v3['address_shipping']['address1']);
                            $dt['postal_code'] = strval($v3['address_shipping']['post_code']);
                            $dt['province_text'] = strval($v3['address_shipping']['address3']);
                            $dt['city_text'] = strval($v3['address_shipping']['city']);
                            $dt['subdistrict_text'] = strval($v3['address_shipping']['address2']);
                            $dt['c_username'] = strval($v3['customer_first_name']);

                            $customer = $this->mymodel->selectWithQuery("SELECT id FROM customer WHERE id_buyer = '$id_buyer' AND marketplace = '$marketplace'");
                            if (empty($customer)) {
                                $dtc = $customer;
                                $dtc['akun_type'] = "Pelanggan";
                                $dtc['brand'] = $dt['brand'];
                                $dtc['marketplace'] = $marketplace;
                                $dtc['id_buyer'] = strval($id_buyer);
                                $dtc['status'] = "Aktif";
                                $dtc['created_at'] = DATE("Y-m-d H:i:s");
                                $dtc['updated_at'] = DATE("Y-m-d H:i:s");
                                $dtc['created_by'] = strval($user['id']);
                                $dtc['full_name'] = $dt['customer_text'];
                                $dtc['phone'] = $dt['phone'];
                                $dtc['username'] = $dt['c_username'];
                                $dtc['count_order'] = 1;
                                $dtc['first_order'] = strval($dt['date']);
                                $dtc['last_order'] = strval($dt['date']);
                                $dtc['address'] = $dt['address'];
                                $dtc['province_text'] = $dt['province_text'];
                                $dtc['city_text'] = $dt['city_text'];
                                $dtc['subdistrict_text'] = $dt['subdistrict_text'];

                                $this->db->insert('customer', $dtc);
                                $dt['customer'] = $this->db->insert_id();
                            } else {
                                $dt['customer'] = $customer[0]['id'];
                            }
                        }
                        $dt['shipping'] = strval($shipment_provider);
                        $dt['shipping_price'] = strval($v3['shipping_fee_original']);

                        $price_total = $v3['price'];

                        $dt['price'] = strval($price_total);
                        $dt['price_total'] = strval($price_total);
                        $dt['price_total_2'] = strval($price_total);


                        $dt['customer_price'] = $v3['price'] + $v3['shipping_fee'] - $v3['voucher_seller'] - $v3['voucher_platform'];
                        $dt['dibayar'] = $dt['customer_price'];

                        $dt['discount'] = strval($v3['discount_amount']);
                        $dt['other_price'] = strval(0);
                        $dt['marketplace'] = strval($marketplace);
                        $dt['id_buyer'] = strval($v3['buyer_user_id']);
                        $dt['bank'] = strval($v3['bank_name']);
                        $dt['cs_phone'] = strval($v3['print_sender_info']['phone']);
                        // print_r($response_item);die;
                        if ($response_item['data'][0]['payment_time']) {
                            $dt['payment_status'] = strval("Paid");
                            $dt['pay_at'] = DATE("Y-m-d H:i:s", (substr($response_item['data'][0]['payment_time'], 0, 10)));
                        } else {
                            $dt['payment_status'] = strval("Unpaid");
                        }
                        // print_r($dt);die;

                        $dt['payment_at'] = strval($v3['payment_date']);
                        $dt['id_trx'] = strval($v3['order_number']);
                        $dt['id_marketplace'] = strval($v3['order_number']);
                        if ($v3['pickup_done_time']) {
                            $dt['pickup_at'] = strval(DATE("Y-m-d H:i:s", $v3['pickup_done_time']));
                        }
                        // if($v3['pay_time']){
                        //     $dt['pay_at'] = strval(DATE("Y-m-d H:i:s",$v3['pay_time']));
                        // }
                        if ($v3['ship_by_date']) {
                            $dt['ship_by_at'] = strval(DATE("Y-m-d H:i:s", $v3['ship_by_date']));
                        }
                        $dt['marketplace'] = $marketplace;

                        $js = array();
                        foreach ($response_item['data'] as $k4 => $v4) {
                            // echo $k4;
                            // print_r($v4);die;
                            $js[$k4]['item_id'] = $v4['product_id'];
                            $js[$k4]['item_sku'] = $v4['sku'];
                            $js[$k4]['item_name'] = $v4['name'];
                            $js[$k4]['model_id'] = $v4['sku_id'];
                            $js[$k4]['model_sku'] = $v4['sku'];
                            $parts = explode(":", $v4['variation']);
                            $v4['variation'] = $parts[1];
                            if (empty($v4['variation'])) {
                                $v4['variation'] = $parts[0];
                            }
                            if (empty($v4['variation'])) {
                                $v4['variation'] = $v4['name'];
                            }
                            $js[$k4]['model_name'] = $v4['variation'];
                            $js[$k4]['qty'] = '1';
                            $js[$k4]['marketplace'] = $marketplace;
                        }

                        // print_r($js);die;

                        $js_new = array();
                        foreach ($js as $k4 => $v4) {
                            $js_new[$v4['model_id']]['qty'] += $v4['qty'];
                            $js_new[$v4['model_id']]['item_id'] = $v4['item_id'];
                            $js_new[$v4['model_id']]['item_sku'] = $v4['item_sku'];
                            $js_new[$v4['model_id']]['item_name'] = $v4['item_name'];
                            $js_new[$v4['model_id']]['model_id'] = $v4['model_id'];
                            $js_new[$v4['model_id']]['model_sku'] = $v4['model_sku'];
                            $js_new[$v4['model_id']]['model_name'] = $v4['model_name'];
                            $js_new[$v4['model_id']]['marketplace'] = $v4['marketplace'];
                        }
                        // print_r($js);die;
                        $j = 0;
                        $js = array();
                        foreach ($js_new as $k4 => $v4) {
                            $js[$j] = $v4;
                            $j++;
                        }

                        $dt['pesanan'] = json_encode($js, true);
                        $dt['pesanan_count'] = count($js);
                        $dt['id'] = $query['id'];

                        $json = array();
                        $price_total_hpp = 0;
                        foreach ($js as $k4 => $v4) {
                            $id_variant = $v4['model_id'];
                            $conf = $this->mymodel->selectWithQuery("SELECT json FROM product_variant_3rd
                                WHERE id_product = '$id_variant' LIMIT 1");
                            $conf = json_decode($conf[0]['json'], true);
                            if (empty($conf)) {
                                $id_variant = $v4['item_id'];
                                $conf = $this->mymodel->selectWithQuery("SELECT json FROM product_variant_3rd
                                    WHERE id_product = '$id_variant' LIMIT 1");
                                $conf = json_decode($conf[0]['json'], true);
                            }
                            foreach ($conf as $k5 => $v5) {
                                $product = $product_arr[$v5['product']];
                                $price = 0;
                                if ($dt['akun_type'] == "Pelanggan") {
                                    $price = $product['price_normal'];
                                } else if ($dt['akun_type'] == "Distributor") {
                                    $price = $product['price_distributor'];
                                } else if ($dt['akun_type'] == "Reseller") {
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

                        $dt['hpp'] = doubleval($price_total_hpp);
                        $dt['json'] = json_encode($json, true);

                        // print_r($dt);die;
                        $run = 1;

                        // if($dt['payment_type']=="TF"){
                        //     if(in_array($dt['order_status'],array('UNPAID','CANCELLED','IN_CANCEL','PENDING'))){
                        //         $run = 0;
                        //     }
                        // }else{
                        //     if(in_array($dt['order_status'],array('CANCELLED','IN_CANCEL'))){
                        //         $run = 1;
                        //     }
                        // }
                        if ($run == 1) {
                            if ($query) {
                                if (in_array($query['order_status'], array('RETURN'))) {
                                    unset($dt['order_status']);
                                }
                                $dt['updated_at'] = DATE("Y-m-d H:i:s");
                                $dt['updated_by'] = strval($user['id']);

                                $this->db->update('transaction', $dt, array('id' => $query['id']));
                            } else {
                                $dt['created_at'] = DATE("Y-m-d H:i:s");
                                $dt['created_by'] = strval($user['id']);

                                $this->db->insert('transaction', $dt);
                            }
                        }


                        $this->generate_stock($js, $json, $v2, $dt, $query);


                        // print_r($dt);
                        //$this->customer_summary($dt['customer']);
                    }
                }
            }
            // if($data['message_type']=="0"){
            //     if(in_array($data['data']['order_status'],array('unpaid'))){
            //         $order_status = 'UNPAID';
            //     }else if(in_array($data['data']['order_status'],array('topack','pending'))){
            //         $order_status = 'PROCESSED';
            //     }else if(in_array($data['data']['order_status'],array('returned'))){
            //         $order_status = 'RETURN';
            //     }else if(in_array($data['data']['order_status'],array('canceled','failed','lost'))){
            //         $order_status = 'CANCELLED';
            //     }else if(in_array($data['data']['order_status'],array('confirmed'))){
            //         $order_status = 'COMPLETED';
            //         $dtt['disbursement_at'] = DATE("Y-m-d H:i:s",$data['data']['status_update_time']);
            //         $dtt['is_disbursement'] = '1';
            //     }else if(in_array($data['data']['order_status'],array('delivered'))){
            //         $order_status = 'DELIVERED';
            //     }else if(in_array($data['data']['order_status'],array('shipped'))){
            //         $order_status = 'SHIPPED';
            //     }else if(in_array($data['data']['order_status'],array('ready_to_ship','toship','shipping'))){
            //         $order_status = 'READY_TO_SHIP';
            //     }

            //     if($order_status){
            //         $dtt['order_status'] = $order_status;
            //     }
            //     if($trx['shipping'] == ""){

            //         $config = $this->mymodel->selectWithQuery("SELECT val FROM marketplace_config WHERE opt = 'lazada' AND brand = '$brand' ");
            //         $config = $config[0];
            //         $config = json_decode($config['val'],true);

            //         $url = $config['partner_host'];

            //         $appkey = $config['app_key'];
            //         $appSecret = $config['app_secret'];
            //         $config['access_token'];

            //         $c = new LazopClient($url,$appkey,$appSecret);
            //         $request = new LazopRequest('/order/get','GET');
            //         $request->addApiParam('order_id',$id);

            //         $response = $c->execute($request, $config['access_token']);
            //         $responses = json_decode($response,true);
            //         $total_data = 1;
            //         $response = array();
            //         $response['data']['orders'][0] = $responses['data'];
            //         foreach($response['data']['orders'] as $k3=>$v3){
            //             $nomor++;

            //             // $v3['order_number'] = '1407814792257102';
            //             $c = new LazopClient($url,$appkey,$appSecret);
            //             $request = new LazopRequest('/logistic/order/trace');
            //             $request->addApiParam('order_id',$v3['order_number']);
            //             $response_shipping = $c->execute($request, $config['access_token']);
            //             $response_shipping = json_decode($response_shipping,true);

            //             $c = new LazopClient($url,$appkey,$appSecret);
            //             $request = new LazopRequest('/order/items/get','GET');
            //             $request->addApiParam('order_id',$v3['order_number']);
            //             $response_item = $c->execute($request, $config['access_token']);
            //             $response_item = json_decode($response_item,true);
            //             $segments = explode(',', $response_item['data'][0]
            //             ['shipment_provider']);
            //             $segments = explode(': ', $segments[0]);
            //             $shipment_provider = $segments[1];
            //             if(empty($shipment_provider)){
            //                 $shipment_provider = $segments[0];
            //             }
            //             $marketplace = "LAZADA";
            //             $id_marketplace = $v3['order_number'];
            //             $query = $this->mymodel->selectWithQuery("SELECT id,phone, order_status 
            //             FROM transaction WHERE order_id = '$id_marketplace' AND marketplace = '$marketplace'
            //             LIMIT 1
            //             ");
            //             
            //             $query = $query[0];

            //             // $dt = array();
            //             $dtt['shipping'] = strval($shipment_provider);
            //             $dtt['awb_number'] = strval($response_shipping['result']['module'][0]['package_detail_info_list'][0]['tracking_number']);
            //         }
            //     }

            //     $dtt['updated_at'] = DATE("Y-m-d H:i:s");
            //     $model = $this->db->table('transaction');
            //     $model->where(array('id_marketplace'=>$id,'MARKETPLACE'=>$marketplace));
            //     $model->update($dtt);

            // }else if($data['message_type']=="10"){
            //     $order_status = 'RETURN';
            //     if (strpos($data['data']['reverse_status'], 'RTM') !== false || strpos($data['data']['reverse_status'], 'RTW') !== false) {
            //         $order_status = "RETURN";
            //     }else if (strpos($data['data']['reverse_status'], 'REFUND') !== false) {
            //         $order_status = "REFUND";
            //     }else{
            //         $order_status = "CANCELLED";
            //     }
            //     $dtt['order_status'] = $order_status;
            //     $dtt['reverse_status'] = $data['data']['reverse_status'];
            //     $dtt['reverse_id'] = $data['data']['reverse_order_id'];
            //     $dtt['reverse_at'] = DATE("Y-m-d H:i:s",$data['data']['status_update_time']);
            //     $dtt['updated_at'] = DATE("Y-m-d H:i:s");
            //     $model = $this->db->table('transaction');
            //     $model->where(array('id_marketplace'=>$id,'MARKETPLACE'=>$marketplace));
            //     $model->update($dtt);
            // }
        }

        if ($order_id && $dt['date']) {
            $dtt = array();
            $dtt['order_date'] = strval($dt['date']);
            $dtt['updated_at'] = DATE("Y-m-d H:i:s");

            $this->db->update('webhook', $dtt, array('order_id' => $order_id));
        }
        if ($dt['order_id']) {
            // $this->remove_duplicate_order($dt['marketplace'],$dt['order_id'],$dt['order_status']);
            $this->update_product_stock();
        }

        $this->remove_duplicate_order_v2();

        $is_manual = $_GET['is_manual'];

        if ($trx) {
            $order_id = $trx['order_id'];
            $id_buyer = $trx['id_buyer'];
            $id_customer = $trx['customer'];
            // $is_manual = $trx['is_manual'];
            $url = base_url() . '/api/update-customer-order?marketplace=' . $marketplace . '&id_buyer=' . $id_buyer . '&id_customer=' . $id_customer . '&is_manual=' . $is_manual;
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
            // echo $url;
        }

        if ($is_manual == "1") {

            header('Content-Type: application/json; charset=utf-8');
            $html = array();
            $html['status'] = true;
            unset($webhook['input']);
            $html['data'] = $webhook;
            $html['msg'] = 'Pembaharuan order status berhasil!';
            echo json_encode($html, true);
            die;
        }
    }

    function reset_webhook()
    {
        $dt['updated_at'] = DATE("Y-m-d H:i:s");

        $this->db->delete('webhook', " order_id = '' ");

        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = true;
        $html['data'] = array();
        $html['msg'] = 'Reset webhook success!';
        echo json_encode($html, true);
        die;
    }

    function refresh_customer()
    {
        $mode = $_GET['mode'];
        $date = $_GET['date'];
        $marketplace = $_GET['marketplace'];

        $user = $_SESSION['user'];

        if ($marketplace) {
            $qry .= " AND marketplace = '$marketplace' ";
        }

        $data = $this->mymodel->selectWithQuery("SELECT id, brand, customer_text, phone, c_username, marketplace,cs,subdistrict_text,city_text,province_text,address
        FROM transaction
        WHERE customer = 0 AND is_manual = 1
        LIMIT 30
        ");
        $arr_customer = array();
        foreach ($data as $k => $v) {
            // print_r($v);die;
            $marketplace = $v['marketplace'];
            $brand = $v['brand'];
            $phone = $v['phone'];
            $c_username = $v['c_username'];
            $customer_text = $v['customer_text'];
            $cs = $v['cs'];
            $customer = $this->mymodel->selectWithQuery("SELECT id
           FROM customer
           WHERE full_name = '$customer_text'
           AND (phone = '$phone' OR username = '$c_username')
           ");
            $customer = $customer[0];

            if (empty($customer)) {
                $dt['subdistrict_text'] = strval($v['subdistrict_text']);
                $dt['city_text'] = strval($v['city_text']);
                $dt['address'] = strval($v['address']);
                $dt['province_text'] = strval($v['province_text']);
                $dt['full_name'] = strval($customer_text);
                $dt['username'] = strval($c_username);
                $dt['phone'] = strval($phone);
                $dt['brand'] = strval($brand);
                $dt['marketplace'] = strval($marketplace);
                $dt['cs'] = strval($cs);
                $dt['akun_type'] = "Pelanggan";
                $dt['is_manual'] = "1";
                $dt['status'] = "Aktif";
                $dt['created_at'] = DATE("Y-m-d H:i:s");
                $this->db->insert('customer', $dt);
                $id_customer = $this->db->insert_id();
            } else {
                $id_customer = $customer['id'];
            }
            if (!in_array($id_customer, $arr_customer)) {
                $arr_customer[] = $id_customer;
            }
            //    echo $id_customer;
            //    echo '<br>';
            $dt = array();
            $dt['updated_at'] = DATE("Y-m-d H:i:s");
            $dt['customer'] = strval($id_customer);

            $this->db->update('transaction', $dt, array('id' => $v['id']));
        }
        foreach ($arr_customer as $k => $v) {
            $id_customer = $v;
            $this->customer_summary_v2($id_customer);
        }
        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = true;
        $html['data'] = $data;
        $html['msg'] = 'Find ' . count($data) . ' data order!';
        echo json_encode($html, true);
        die;
    }


    function refresh_order()
    {
        $mode = $_GET['mode'];
        $date = $_GET['date'];
        $marketplace = $_GET['marketplace'];
        $qry = "";
        if ($marketplace) {
            $qry .= " AND marketplace = '$marketplace' ";
        }
        if ($mode == "webhook") {
            $data = $this->mymodel->selectWithQuery("SELECT id,marketplace,brand,order_id
            FROM webhook
            WHERE order_id != '' AND order_date = '' $qry
            GROUP BY order_id
            ORDER BY id DESC
            LIMIT 30
        ");
        } else {
            $data = $this->mymodel->selectWithQuery("SELECT id,marketplace,brand,order_id
            FROM transaction
            WHERE is_webhook = 0 $qry AND is_manual = 0
            ORDER BY updated_at DESC
            LIMIT 30
            ");
            // if(empty($data)){
            //     $today = DATE("Y-m-d H:i:s");
            //     $yesterday = DATE('Y-m-d', strtotime($today . " -1 days"));
            //     $data = $this->mymodel->selectWithQuery("SELECT id,marketplace,brand,order_id,date
            //     FROM transaction
            //     WHERE order_status = 'READY_TO_SHIP' $qry AND DATE(date) <= '$yesterday'
            //     ORDER BY updated_at DESC
            //     LIMIT 30
            //     ");
            // }
        }


        foreach ($data as $k => $v) {
            $data_2[] = $v;
            $order_id = $v['order_id'];
            $dt['updated_at'] = DATE("Y-m-d H:i:s");

            $this->db->update('transaction', $dt, array('id' => $v['id']));

            if ($mode == "webhook") {
                // use shop id
                $url = base_url() . '/api/get-order-detail?order_id=' . $order_id . '&marketplace=' . $v['marketplace'] . '&brand=' . $v['brand'] . '';
            } else {
                // use brand
                $url = base_url() . '/api/get-order-detail?order_id=' . $order_id . '&marketplace=' . $v['marketplace'] . '&brand=' . $v['brand'] . '&is_manual=1';
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
        $html['data'] = $data_2;
        $html['msg'] = 'Refresh order data success!';
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
                $html['data'] = $filter;
                $html['msg'] = "Acneno System influencer cronjob will be processed at " . $target . "!";
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
                    $response = $this->template->get_post_list($query['type'], $username);
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
        $html['data'] = $filter;
        $html['msg'] = count($list) . " data influencer yg di sync <= $todayy berhasil diperbarui";
        echo json_encode($html, true);
        die;
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
                $html['data'] = $filter;
                $html['msg'] = "Acneno System influencer cronjob will be processed at " . $target . "!";
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
            WHERE id_endorse = '$id_endorse' AND date < '$today' AND views > 0 ORDER BY date DESC LIMIT 1 ");
            $query_yesterday = $query_yesterday[0];


            $dt = array();
            $dt['id_endorse'] = strval($v['id']);
            $dt['id_campaign'] = strval($v['id_campaign']);
            $dt['date'] = $today;



            $response = $this->template->get_social_media($v['platform'], $v['link_upload']);


            $dts = array();
            $dts['sync_at'] = DATE("Y-m-d H:i:s");
            if ($response['data']['created_at']) {
                $dts['posting_at'] = $response['data']['created_at'];
            }

            $this->db->update('endorse', $dts, array('id' => $v['id']));

            if ($response['data']['view'] > 0) {
                $dt['likes'] = $response['data']['like'];
                $dt['comment'] = $response['data']['comment'];
                $dt['share_save'] = doubleval($response['data']['share']) + doubleval($response['data']['collect']);
                $dt['views'] = $response['data']['view'];

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
            }

            // $dt['is_cron'] = '1';
            // print_r($dt);die;

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
            $this->update_endorse_parent($id_parent);
        }

        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = true;
        $html['data'] = $filter;
        $html['msg'] = count($list) . " data endorse yg di sync <= $todayy berhasil diperbarui";
        echo json_encode($html, true);
        die;
    }

    function update_endorse_parent($id_parent)
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
                $html['data'] = $filter;
                $html['msg'] = "Acneno System endorse campaign cronjob will be processed at " . $target . "!";
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
            $this->update_endorse_parent($id_parent);
        }



        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = true;
        $html['data'] = $filter;
        $html['msg'] = count($list) . " data endorse campaign yg di sync <= $todayy berhasil diperbarui";
        echo json_encode($html, true);
        die;
    }

    public function cronjob_order()
    {
        $start_date = DATE("Y-m-01");
        $until_date = DATE("Y-m-d");
        $start_date = $start_date . ' 00:00:00';
        $until_date = $until_date . ' 00:00:00';
        $curl = curl_init();

        $filter = $_GET;

        if ($filter['channel'] == "SHOPEE" || $filter['channel'] == "") {
            curl_setopt_array($curl, array(
                CURLOPT_URL => base_url() . '/api/shopee/get-order?start_date=' . urlencode($start_date) . '&until_date=' . urlencode($until_date) . '',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Cookie: ci_session=ji2dpajlqasgk8200eroqtjrri48iugl'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            header('Content-Type: application/json; charset=utf-8');
            $html = json_decode($response, true);
            echo json_encode($html, true);
            die;
        }
        if ($filter['channel'] == "LAZADA" || $filter['channel'] == "") {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => base_url() . '/api/lazada/get-order?start_date=' . urlencode($start_date) . '&until_date=' . urlencode($until_date) . '',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Cookie: ci_session=ji2dpajlqasgk8200eroqtjrri48iugl'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            header('Content-Type: application/json; charset=utf-8');
            $html = json_decode($response, true);
            echo json_encode($html, true);
            die;
        }

        if ($filter['channel'] == "TIKTOK" || $filter['channel'] == "") {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => base_url() . '/api/tiktok/get-order?start_date=' . urlencode($start_date) . '&until_date=' . urlencode($until_date) . '',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Cookie: ci_session=ji2dpajlqasgk8200eroqtjrri48iugl'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            header('Content-Type: application/json; charset=utf-8');
            $html = json_decode($response, true);
            echo json_encode($html, true);
            die;
        }


        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = false;
        $html['data'] = array();
        $html['msg'] = "Channel tidak ditemukan!";
        echo json_encode($html, true);
        die;
    }

    public function cronjob_finance()
    {
        $start_date = DATE("Y-m-01");
        $until_date = DATE("Y-m-d");
        $start_date = $start_date . ' 00:00:00';
        $until_date = $until_date . ' 00:00:00';
        $curl = curl_init();

        $filter = $_GET;

        if ($filter['channel'] == "SHOPEE" || $filter['channel'] == "") {
            curl_setopt_array($curl, array(
                CURLOPT_URL => base_url() . '/api/shopee/get-finance',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Cookie: ci_session=ji2dpajlqasgk8200eroqtjrri48iugl'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            header('Content-Type: application/json; charset=utf-8');
            $html = json_decode($response, true);
            echo json_encode($html, true);
            die;
        }
        if ($filter['channel'] == "LAZADA" || $filter['channel'] == "") {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => base_url() . '/api/lazada/get-finance',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Cookie: ci_session=ji2dpajlqasgk8200eroqtjrri48iugl'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            header('Content-Type: application/json; charset=utf-8');
            $html = json_decode($response, true);
            echo json_encode($html, true);
            die;
        }

        if ($filter['channel'] == "TIKTOK" || $filter['channel'] == "") {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => base_url() . '/api/tiktok/get-finance',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Cookie: ci_session=ji2dpajlqasgk8200eroqtjrri48iugl'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            header('Content-Type: application/json; charset=utf-8');
            $html = json_decode($response, true);
            echo json_encode($html, true);
            die;
        }

        // if($filter['channel'] == "ALL" || $filter['channel'] == ""){
        //     $data = $this->mymodel->selectWithQuery("SELECT id, date, order_id, marketplace FROM transaction WHERE omset_kotor != price_total AND omset_kotor = 0 ORDER BY date ASC LIMIT 10");
        //     // print_r($data);die;
        //     foreach($data as $k=>$v){
        //         if($v['marketplace'] == "TIKTOK"){
        //             $curl = curl_init();
        //             curl_setopt_array($curl, array(
        //             CURLOPT_URL => base_url().'/api/webhook?marketplace=TIKTOK',
        //             CURLOPT_RETURNTRANSFER => true,
        //             CURLOPT_ENCODING => '',
        //             CURLOPT_MAXREDIRS => 10,
        //             CURLOPT_TIMEOUT => 0,
        //             CURLOPT_FOLLOWLOCATION => true,
        //             CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        //             CURLOPT_CUSTOMREQUEST => 'GET',
        //             CURLOPT_POSTFIELDS =>'{
        //                 "type": 1,
        //                 "tts_notification_id": "7353692523523016456",
        //                 "shop_id": "7493992274333500784",
        //                 "timestamp": 1712164964,
        //                 "data": {
        //                     "is_on_hold_order": false,
        //                     "order_id": "'.$v['order_id'].'",
        //                     "order_status": "COMPLETED",
        //                     "update_time": 1712164963
        //                 }
        //             }',
        //             CURLOPT_HTTPHEADER => array(
        //                 'Content-Type: application/json'
        //             ),
        //             ));
        //             $response = curl_exec($curl);
        //             curl_close($curl);
        //         }
        //     }

        //     header('Content-Type: application/json; charset=utf-8');
        //     $html = json_decode($response,true);
        //     echo json_encode($html, true);
        //     die;

        // }


        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = false;
        $html['data'] = array();
        $html['msg'] = "Channel tidak ditemukan!";
        echo json_encode($html, true);
        die;
    }

    public function auth_lazada()
    {

        $brand = $_SESSION['brand'];
        $dt = $_GET;
        $html = array();
        $config = $this->mymodel->selectWithQuery("SELECT val FROM marketplace_config WHERE opt = 'lazada' AND brand = '$brand' ");
        $config = $config[0];
        $config = json_decode($config['val'], true);

        $config['code'] = $_GET['code'];

        if ($config['code']) {
            $user = $_SESSION['user'];
            $dtt = array();
            $dtt['val'] = json_encode($config, true);
            $dtt['updated_at'] = DATE("Y-m-d H:i:s");
            $dtt['updated_by'] = $user['id'];
            $this->db->update('marketplace_config', $dtt, array('opt' => 'lazada', 'brand' => $brand));
        }

        $url = $config['partner_host'];
        $url = 'https://api.lazada.com/rest';

        $appkey = $config['app_key'];
        $appSecret = $config['app_secret'];

        $c = new LazopClient($url, $appkey, $appSecret);
        $request = new LazopRequest('/auth/token/create');
        $request->addApiParam('code', $_GET['code']);

        $response = $c->execute($request);
        $response = json_decode($response, true);
        $config['access_token'] = $response['access_token'];
        $config['refresh_token'] = $response['refresh_token'];
        $config['user_id'] = $response['country_user_info']['user_id'];
        $config['shop_id'] = $response['country_user_info']['seller_id'];


        if ($config['access_token']) {
            $user = $_SESSION['user'];
            $dtt = array();
            $dtt['val'] = json_encode($config, true);
            $dtt['updated_at'] = DATE("Y-m-d H:i:s");
            $dtt['updated_by'] = $user['id'];

            $this->db->update('marketplace_config', $dtt, array('opt' => 'lazada', 'brand' => $brand));
            return redirect(base_url() . 'transaction');
        } else {
            echo 'Koneksi lazada tidak berhasil. Silahkan coba lagi nanti! <a href="' . base_url() . '/transaction">Kembali</a>';
        }
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
        $signstring .= '{}' . $secret;
        // echo $signstring;
        // die;
        $sign = hash_hmac("sha256", $signstring, $secret);
        return $sign;
    }

    public function marketplace_callback_tiktok()
    {

        $dt = $_GET;
        $type = $dt['type'];
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
            echo 'Koneksi tiktok tidak berhasil. Silahkan coba lagi nanti! <a href="' . base_url() . '/transaction">Kembali</a>';
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

        if (empty($access_token)) {
            echo 'Toko tiktok tidak ditemukan. Silahkan coba lagi nanti! <a href="' . base_url() . '/transaction">Kembali</a>';
            die;
        }

        $check = $this->mymodel->selectDataOne('marketplace_config', array('shop_id' => $shop_id));

        if ($check) {
            $dt = array();
            $config = array();
            $config['app_key'] = $app_key;
            $config['access_token'] = $access_token;
            $config['refresh_token'] = $refresh_token;
            $config['shop'] = $response['data']['shops'][0];
            $dt['val'] = json_encode($config, true);
            $dt['opt'] = "tiktok";
            $dt['status'] = "Aktif";
            $dt['shop_id'] = $response['data']['shops'][0]['id'];
            $dt['shop_name'] = $response['data']['shops'][0]['name'];
            $dt['updated_at'] = DATE("Y-m-d H:i:s");
            $dt['updated_by'] = $_SESSION['user']['id'];
            $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
            $this->db->update('marketplace_config', $dt, array('shop_id' => $shop_id));
        } else {
            $dt = array();
            $config = array();
            $config['app_key'] = $app_key;
            $config['access_token'] = $access_token;
            $config['refresh_token'] = $refresh_token;
            $config['shop'] = $response['data']['shops'][0];
            $dt['val'] = json_encode($config, true);
            $dt['opt'] = "tiktok";
            $dt['status'] = "Aktif";
            $dt['shop_id'] = $response['data']['shops'][0]['id'];
            $dt['shop_name'] = $response['data']['shops'][0]['name'];
            $dt['created_at'] = DATE("Y-m-d H:i:s");
            $dt['created_by'] = $_SESSION['user']['id'];
            $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
            $this->db->insert('marketplace_config', $dt);
        }
        return redirect(base_url() . 'transaction');
    }

    function marketplace_token_refresh()
    {
        header('Content-Type: application/json; charset=utf-8');
        $dt = $_GET;
        $type = $dt['type'];
        $shop_id = $dt['shop_id'];
        if ($shop_id) {
            $qry = " AND shop_id = '$shop_id' ";
        }
        $data = $this->mymodel->selectWithQuery("SELECT *
        FROM marketplace_config
        WHERE status = 'Aktif' $qry");
        foreach ($data as $k => $v) {
            if ($v['opt'] == "tiktok") {

                $config = json_decode($v['val'], true);
                $app_key = $config['app_key'];
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

                    $dt = array();
                    $config = array();
                    $config['app_key'] = $app_key;
                    $config['access_token'] = $access_token;
                    $config['refresh_token'] = $refresh_token;
                    $config['shop'] = $response['data']['shops'][0];
                    $dt['val'] = json_encode($config, true);
                    $dt['opt'] = "tiktok";
                    $dt['status'] = "Aktif";
                    $dt['shop_id'] = $response['data']['shops'][0]['id'];
                    $dt['shop_name'] = $response['data']['shops'][0]['name'];
                    $dt['updated_at'] = DATE("Y-m-d H:i:s");
                    $dt['updated_by'] = $_SESSION['user']['id'];
                    $dt['expired_at'] = DATE("Y-m-d H:i:s", $expired_at);
                    $this->db->update('marketplace_config', $dt, array('id' => $v['id']));
                }
            }
        }
        $html['status'] = true;
        $html['data'] = array();
        $html['msg'] = 'Refresh token berhasil!';
        echo json_encode($html, true);
        die;
    }

    function marketplace_order()
    {
        header('Content-Type: application/json; charset=utf-8');

        $dt = $_GET;
        $type = $dt['type'];
        $shop_id = $dt['shop_id'];

        $qry = "";
        if ($type) {
            $qry = " AND opt = '$type' ";
        }
        if ($shop_id) {
            $qry = " AND shop_id = '$shop_id' ";
        }
        $data = $this->mymodel->selectWithQuery("SELECT *
        FROM marketplace_config
        WHERE status = 'Aktif' $qry");

        foreach ($data as $k => $v) {
            if ($type == "tiktok") {

                $config = json_decode($v['val'], true);
                $app_key = $config['app_key'];
                $access_token = $config['access_token'];
                $shop_cipher = $config['shop']['cipher'];
                $app_secret = $this->app_secret_tiktok;

                //
                $url = 'https://open-api.tiktokglobalshop.com/order/202309/orders/search?access_token=' . $access_token . '&app_key=' . $app_key . '&page_size=100&shop_cipher=' . $shop_cipher . '&shop_id=&sign={{sign}}&sort_order=DESC&timestamp={{timestamp}}&version=202309';

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
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => '{}',
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json',
                        'x-tts-access-token: ' . $access_token
                    ),
                ));

                $response = curl_exec($curl);

                curl_close($curl);
                echo $response;
            }
        }

        // $html['status'] = true;
        // $html['data'] = array();
        // $html['msg'] = 'Sync data order berhasil!';
        // echo json_encode($html, true);
        // die;
    }


    public function auth_tiktok()
    {
        $brand = $_SESSION['brand'];
        $dt = $_GET;
        $html = array();

        $config = $this->mymodel->selectWithQuery("SELECT val FROM marketplace_config WHERE opt = 'tiktok' AND brand = '$brand' ");
        $config = $config[0];
        $config = json_decode($config['val'], true);
        $config['app_key'] = $_GET['app_key'];
        $config['code'] = $_GET['code'];

        $user = $_SESSION['user'];
        $dtt = array();
        $dtt['val'] = json_encode($config, true);
        $dtt['updated_at'] = DATE("Y-m-d H:i:s");
        $dtt['updated_by'] = $user['id'];

        $this->db->update('marketplace_config', $dtt, array('opt' => 'tiktok', 'brand' => $brand));

        $access_token = $config['code'];
        $app_key = $config['app_key'];
        $shop_id = $config['shop_id'];
        $secret = $config['secret'];
        // print_r($config);die;
        $code = $_GET['code'];

        $url = 'https://auth.tiktok-shops.com/api/v2/token/get?app_key=' . $app_key . '&app_secret=' . $secret . '&grant_type=authorized_code&auth_code=' . $code;

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
        ));

        $response = curl_exec($curl);
        // echo $response;die;
        $response = json_decode($response, true);

        if ($response['data']['access_token']) {
            $config['access_token'] = $response['data']['access_token'];
            $config['refresh_token'] = $response['data']['refresh_token'];
            $config['shop_name'] = $response['data']['seller_name'];
            $user = $_SESSION['user'];
            $dtt = array();
            $dtt['val'] = json_encode($config, true);
            $dtt['updated_at'] = DATE("Y-m-d H:i:s");
            $dtt['updated_by'] = $user['id'];

            $this->db->update('marketplace_config', $dtt, array('opt' => 'tiktok', 'brand' => $brand));
            // print_r($response);die;
            return redirect(base_url() . 'transaction');
        } else {
            $html['status'] = false;
            $html['data'] = array();
            $html['msg'] = $response['message'];
            // $html['msg'] = 'Refresh token tiktok tidak berhasil!';
            echo json_encode($html, true);
            die;
        }
        echo 'Koneksi tiktok tidak berhasil. Silahkan coba lagi nanti! <a href="' . base_url() . '/transaction">Kembali</a>';
    }

    public function auth_shopee()
    {

        $brand = $_SESSION['brand'];
        $dt = $_GET;
        $html = array();
        $config = $this->mymodel->selectWithQuery("SELECT val FROM marketplace_config WHERE opt = 'shopee' AND brand = '$brand' ");
        $config = $config[0];
        $config = json_decode($config['val'], true);
        $config['shop_id'] = $_GET['shop_id'];
        $config['code'] = $_GET['code'];

        $user = $_SESSION['user'];
        $dtt = array();
        $dtt['val'] = json_encode($config, true);
        $dtt['updated_at'] = DATE("Y-m-d H:i:s");
        $dtt['updated_by'] = $user['id'];

        $this->db->update('marketplace_config', $dtt, array('opt' => 'shopee', 'brand' => $brand));

        $host = $config['partner_host'];
        $partnerId = $config['partner_id'];
        $partnerKey = $config['partner_key'];
        $code = $config['code'];
        $shopId = intval($config['shop_id']);
        $path = "/api/v2/auth/token/get";
        $timest = time();
        $body = array("code" => $code,  "shop_id" => $shopId, "partner_id" => intval($partnerId));
        $baseString = sprintf("%s%s%s", $partnerId, $path, $timest);
        $sign = hash_hmac('sha256', $baseString, $partnerKey);
        $url = sprintf("%s%s?partner_id=%s&timestamp=%s&sign=%s", $host, $path, $partnerId, $timest, $sign);

        $c = curl_init($url);
        curl_setopt($c, CURLOPT_POST, 1);
        curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($c);
        $response = json_decode($response, true);

        $config['access_token'] = $response['access_token'];
        $config['refresh_token'] = $response['refresh_token'];
        if ($config['access_token']) {
            $user = $_SESSION['user'];
            $dtt = array();
            $dtt['val'] = json_encode($config, true);
            $dtt['updated_at'] = DATE("Y-m-d H:i:s");
            $dtt['updated_by'] = $user['id'];

            $this->db->update('marketplace_config', $dtt, array('opt' => 'shopee', 'brand' => $brand));
            return redirect(base_url() . 'transaction');
        } else {
            echo 'Koneksi shopee tidak berhasil. Silahkan coba lagi nanti! <a href="' . base_url() . '/transaction">Kembali</a>';
        }
    }

    function update_product_stock()
    {
        $product = $this->mymodel->selectWithQuery("SELECT * FROM product
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

    function remove_duplicate_order($marketplace, $order_id, $order_status)
    {
        $data = $this->mymodel->selectWithQuery("SELECT id FROM transaction WHERE order_id = '$order_id' AND marketplace = '$marketplace' ORDER BY id ASC");
        foreach ($data as $k => $v) {
            if ($k > 0) {
                $models = $this->db->table('stock_product_3rd');
                $models->delete(['id_trx' => $v['id']]);

                $models = $this->db->table('stock');
                $models->delete(['id_trx' => $v['id']]);

                $models = $this->db->table('transaction');
                $models->delete(['id' => $v['id']]);
            }
            // if(in_array($order_status,array('CANCELLED','IN_CANCEL'))){
            //     $models = $this->db->table('stock_product_3rd');
            //     $models->delete(['id_trx' => $v['id']]);

            //     $models = $this->db->table('stock');
            //     $models->delete(['id_trx' => $v['id']]);

            //     $models = $this->db->table('transaction');
            //     $models->delete(['id' => $v['id']]);
            // }
        }
    }
    public function webhook()
    {
        $brand = $_GET['brand'];
        $is_manual = $_GET['is_manual'];
        date_default_timezone_set('Asia/Jakarta');
        header('Content-Type: application/json; charset=utf-8');
        // $dt['type'] = "Order Status";
        $dt['brand'] = strval($brand);
        $dt['marketplace'] = strval($_GET['marketplace']);
        $dt['get'] = json_encode($_GET, true);
        $dt['post'] = json_encode($_POST, true);
        //$dt['input'] = json_encode(file_get_contents("php://input"),true);
        $dt['input'] = file_get_contents("php://input");
        // $dt['header'] = json_encode($_SERVER,true);
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
        // print_r($dt);die;
        if ($is_manual != "1") {
            $this->db->insert('webhook', $dt);
            $id_webhook = $this->db->insert_id();
        }


        // $url = base_url().'/api/get-order-detail?order_id='.$order_id.'&marketplace='.$dt['marketplace'].'&brand='.$dt['brand'].'&is_manual=1';

        // $curl = curl_init();

        // curl_setopt_array($curl, array(
        //   CURLOPT_URL => $url,
        //   CURLOPT_RETURNTRANSFER => true,
        //   CURLOPT_ENCODING => '',
        //   CURLOPT_MAXREDIRS => 10,
        //   CURLOPT_TIMEOUT => 1,
        //   CURLOPT_FOLLOWLOCATION => true,
        //   CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        //   CURLOPT_CUSTOMREQUEST => 'GET',
        //   CURLOPT_HTTPHEADER => array(
        //     'Content-Type: application/json'
        //   ),
        // ));

        // $response = curl_exec($curl);

        // curl_close($curl);

        // echo $url;die;

        $dtt = array();
        $dtt['order_id'] = strval($order_id);
        $dtt['shop_id'] = strval($shop_id);
        $dtt['marketplace'] = strval($dt['marketplace']);
        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = true;
        $html['data'] = $dtt;
        $html['msg'] = "Acneno System webhook live access has been successful!";
        echo json_encode($html, true);
        die;
    }
    function remove_duplicate_order_v2()
    {
        $datas = $this->mymodel->selectWithQuery("SELECT date,order_id, marketplace, count(id) count
        FROM transaction
        WHERE type_sub != 'Expense'
        GROUP BY order_id
        HAVING count > 1
        ORDER BY count DESC");
        foreach ($datas as $k2 => $v2) {
            $order_id = $v2['order_id'];
            $marketplace = $v2['marketplace'];
            $data = $this->mymodel->selectWithQuery("SELECT id FROM transaction WHERE order_id = '$order_id' AND marketplace = '$marketplace' ORDER BY id ASC");
            foreach ($data as $k => $v) {
                if ($k > 0) {
                    $models = $this->db->table('stock_product_3rd');
                    $models->delete(['id_trx' => $v['id']]);

                    $models = $this->db->table('stock');
                    $models->delete(['id_trx' => $v['id']]);

                    $models = $this->db->table('transaction');
                    $models->delete(['id' => $v['id']]);
                }
            }
        }
    }
    public function webhook_test()
    {
        date_default_timezone_set('Asia/Jakarta');
        header('Content-Type: application/json; charset=utf-8');
        $dt['type'] = "Order Status";
        $dt['get'] = json_encode($_GET, true);
        $dt['post'] = json_encode($_POST, true);
        //$dt['input'] = json_encode(file_get_contents("php://input"),true);
        $dt['input'] = file_get_contents("php://input");
        $dt['header'] = json_encode($_SERVER, true);
        $dt['key'] = strval($_SERVER['HTTP_X_API_KEY']);
        $dt['method'] = strval($_SERVER['REQUEST_METHOD']);
        $dt['created_at'] = DATE("Y-m-d H:i:s");
        $dt['is_live'] = 'false';


        $this->db->insert('webhook', $dt);

        $dt = array();
        header('Content-Type: application/json; charset=utf-8');
        $html = array();
        $html['status'] = true;
        $html['data'] = $dt;
        $html['msg'] = "Acneno System webhook test access has been successful!";
        echo json_encode($html, true);
    }
    public function auth_marketplace_lazada()
    {

        $brand = $_GET['brand'];

        $_SESSION['brand'] = $brand;

        $config = $this->mymodel->selectWithQuery("SELECT val FROM marketplace_config WHERE opt = 'lazada' AND brand = '$brand' ");
        $config = $config[0];
        $config = json_decode($config['val'], true);
        $cliend_id = $config['client_id'];
        $url = 'https://app.acnenosystem.com/api/auth/lazada';

        // if($brand=="MG"){
        //     // $url = 'https://auth.lazada.com/oauth/authorize?response_type=code&force_auth=true&redirect_uri='.$url.'&client_id='.$config['app_key'];
        //     // return redirect($url);
        // }else 
        if ($brand == "POME") {
            $url = 'https://auth.lazada.com/oauth/authorize?response_type=code&force_auth=true&redirect_uri=' . $url . '&client_id=' . $config['app_key'];
            return redirect($url);
        } else {
            echo 'Brand ' . $brand . ' tidak tersedia!. Silahkan coba lagi nanti! <a href="' . base_url() . '/transaction">Kembali</a>';
        }
    }
    public function auth_marketplace_tiktok()
    {
        $brand = $_GET['brand'];

        $_SESSION['brand'] = $brand;


        if ($brand == "MG") {
            $url = 'https://services.tiktokshop.com/open/authorize?service_id=7364024630703113990';
            return redirect($url);
        } else if ($brand == "POME") {
            $url = 'https://services.tiktokshop.com/open/authorize?service_id=7346761498458113797';
            return redirect($url);
        } else {
            echo 'Brand ' . $brand . ' tidak tersedia!. Silahkan coba lagi nanti! <a href="' . base_url() . '/transaction">Kembali</a>';
        }
    }
    public function auth_marketplace_shopee()
    {

        $brand = $_GET['brand'];

        $_SESSION['brand'] = $brand;

        $config = $this->mymodel->selectWithQuery("SELECT val FROM marketplace_config WHERE opt = 'shopee' AND brand = '$brand' ");
        $config = $config[0];
        $config = json_decode($config['val'], true);

        $host = $config['partner_host'];
        $partnerId = $config['partner_id'];
        $partnerKey = $config['partner_key'];

        $path = "/api/v2/shop/auth_partner";
        $redirectUrl = base_url() . "/api/auth/shopee";
        $timest = time();
        $baseString = sprintf("%s%s%s", $partnerId, $path, $timest);
        $sign = hash_hmac('sha256', $baseString, $partnerKey);
        $url = sprintf("%s%s?partner_id=%s&timestamp=%s&sign=%s&redirect=%s", $host, $path, $partnerId, $timest, $sign, $redirectUrl);

        return redirect($url);
    }

    public function shopee_refresh_token()
    {

        $brand = $_GET['brand'];

        header('Content-Type: application/json; charset=utf-8');
        $html = array();

        $config = $this->mymodel->selectWithQuery("SELECT val FROM marketplace_config WHERE opt = 'shopee' AND brand = '$brand' ");
        $config = $config[0];
        $config = json_decode($config['val'], true);

        $host = $config['partner_host'];
        $partnerId = $config['partner_id'];
        $partnerKey = $config['partner_key'];
        $shopId = intval($config['shop_id']);
        $refreshToken = $config['refresh_token'];

        $path = "/api/v2/auth/access_token/get";

        $timest = time();
        $body = array("partner_id" => $partnerId, "shop_id" => $shopId, "refresh_token" => $refreshToken);
        $baseString = sprintf("%s%s%s", $partnerId, $path, $timest);
        $sign = hash_hmac('sha256', $baseString, $partnerKey);
        $url = sprintf("%s%s?partner_id=%s&timestamp=%s&sign=%s", $host, $path, $partnerId, $timest, $sign);

        $c = curl_init($url);
        curl_setopt($c, CURLOPT_POST, 1);
        curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($c);
        $response = json_decode($response, true);

        $config['access_token'] = $response['access_token'];
        $config['refresh_token'] = $response['refresh_token'];
        $config['expire_in'] = $response['expire_in'];


        if ($response['message']) {
            $html['status'] = false;
            $html['data'] = array();
            $html['msg'] = $response['message'];
            echo json_encode($html, true);
            die;
        }

        if ($config['access_token']) {

            $host = $config['partner_host'];
            $partnerId = $config['partner_id'];
            $partnerKey = $config['partner_key'];
            $shopId = intval($config['shop_id']);
            $refreshToken = $config['refresh_token'];

            $path = "/api/v2/shop/get_profile";

            $timest = time();
            $body = array("partner_id" => $partnerId, "shop_id" => $shopId, "refresh_token" => $refreshToken);
            $baseString = sprintf("%s%s%s%s%s", $partnerId, $path, $timest, $config['access_token'], $config['shop_id']);
            $sign = hash_hmac('sha256', $baseString, $partnerKey);

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $host . $path . '?access_token=' . $config['access_token'] . '&partner_id=' . $config['partner_id'] . '&shop_id=' . $config['shop_id'] . '&sign=' . $sign . '&timestamp=' . $timest . '',
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

            $user = $_SESSION['user'];
            $dtt = array();
            if ($response['response']) {
                $config['shop_logo'] = $response['response']['shop_logo'];
                $config['shop_name'] = $response['response']['shop_name'];
            }

            $dtt['val'] = json_encode($config, true);
            $dtt['shop_id'] = strval($config['shop_id']);
            $dtt['updated_at'] = DATE("Y-m-d H:i:s");
            $dtt['updated_by'] = $user['id'];

            $this->db->update('marketplace_config', $dtt, array('opt' => 'shopee', 'brand' => $brand));

            $html['status'] = true;
            $html['data'] = $response['response'];
            $html['msg'] = 'Refresh token shopee berhasil!';
            echo json_encode($html, true);
            die;
        } else {
            $html['status'] = false;
            $html['data'] = array();
            $html['msg'] = 'Refresh token shopee tidak berhasil!';
            echo json_encode($html, true);
            die;
        }
    }

    public function lazada_refresh_token()
    {

        $brand = $_GET['brand'];

        header('Content-Type: application/json; charset=utf-8');
        $html = array();

        $config = $this->mymodel->selectWithQuery("SELECT val FROM marketplace_config WHERE opt = 'lazada' AND brand = '$brand' ");
        $config = $config[0];
        $config = json_decode($config['val'], true);

        $url = $config['partner_host'];

        $appkey = $config['app_key'];
        $appSecret = $config['app_secret'];


        $c = new LazopClient($url, $appkey, $appSecret);
        $request = new LazopRequest('/auth/token/refresh');
        $request->addApiParam('refresh_token', $config['refresh_token']);
        $response = $c->execute($request);
        $response = json_decode($response, true);

        // print_r($response);

        // echo '<br><br><br>';

        if ($response['access_token'] == "") {
            $html['status'] = false;
            $html['data'] = array();
            $html['msg'] = $response['message'];
            echo json_encode($html, true);
            die;
        }

        $config['access_token'] = $response['access_token'];
        $config['refresh_token'] = $response['refresh_token'];
        $config['expire_in'] = $response['expires_in'];

        // echo $config['access_token'];die;

        $c = new LazopClient($url, $appkey, $appSecret);
        $request = new LazopRequest('/seller/get', 'GET');
        $response = $c->execute($request, $config['access_token']);
        $response = json_decode($response, true);

        $config['shop_logo'] = $response['data']['shop_logo'];
        $config['shop_name'] = $response['data']['name'];
        $config['shop_id'] = $response['data']['seller_id'];

        $dtt['val'] = json_encode($config, true);
        $dtt['shop_id'] = strval($config['shop_id']);
        $dtt['updated_at'] = DATE("Y-m-d H:i:s");
        $dtt['updated_by'] = $user['id'];

        $this->db->update('marketplace_config', $dtt, array('opt' => 'lazada', 'brand' => $brand));

        if ($response['data']) {
            $html['status'] = true;
            $html['data'] = $response['data'];
            $html['msg'] = 'Refresh token lazada berhasil!';
            echo json_encode($html, true);
            die;
        } else {
            $html['status'] = false;
            $html['data'] = array();
            $html['msg'] = 'Refresh token lazada tidak berhasil!';
            echo json_encode($html, true);
            die;
        }
    }

    public function tiktok_refresh_token()
    {

        $brand = $_GET['brand'];

        header('Content-Type: application/json; charset=utf-8');
        $html = array();

        $config = $this->mymodel->selectWithQuery("SELECT val FROM marketplace_config WHERE opt = 'tiktok' AND brand = '$brand'");
        $config = $config[0];
        $config = json_decode($config['val'], true);

        $access_token = $config['access_token'];
        $app_key = $config['app_key'];
        $shop_id = $config['shop_id'];
        $secret = $config['secret'];


        $refresh_token = $config['refresh_token'];

        $url = 'https://auth.tiktok-shops.com/api/v2/token/refresh?app_key=' . $app_key . '&app_secret=' . $secret . '&grant_type=refresh_token&refresh_token=' . $refresh_token;

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
        ));

        $response = curl_exec($curl);


        $response = json_decode($response, true);

        if ($response['data']['access_token']) {
            $config['access_token'] = $response['data']['access_token'];
            $config['refresh_token'] = $response['data']['refresh_token'];
            $config['shop_name'] = $response['data']['seller_name'];
            $user = $_SESSION['user'];
            $dtt = array();
            $dtt['val'] = json_encode($config, true);
            $dtt['updated_at'] = DATE("Y-m-d H:i:s");
            $dtt['updated_by'] = $user['id'];
            $this->db->update('marketplace_config', $dtt, array('opt' => 'tiktok', 'brand' => $brand));
        } else {
            $html['status'] = false;
            $html['data'] = array();
            $html['msg'] = $response['message'];
            // $html['msg'] = 'Refresh token tiktok tidak berhasil!';
            echo json_encode($html, true);
            die;
        }


        $url = 'https://open-api.tiktokglobalshop.com/seller/202309/shops?app_key=' . $app_key . '&sign={{sign}}&timestamp={{timestamp}}';
        $urlParts = parse_url($url);
        $paramGET = [];
        parse_str($urlParts['query'], $paramGET);
        $secret = $config['secret'];
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
            CURLOPT_POSTFIELDS => '%7B%7D=',
            CURLOPT_HTTPHEADER => array(
                'x-tts-access-token: ' . $access_token,
                'Content-Type: application/x-www-form-urlencoded'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $response = json_decode($response, true);

        if ($response['data']['shops'][0]['id']) {
            $config['shop_id'] = $response['data']['shops'][0]['id'];
            $shop_id = $config['shop_id'];
        }

        $url = 'https://open-api.tiktokglobalshop.com/api/shop/get_authorized_shop?app_key=' . $app_key . '&shop_id=' . $shop_id . '&access_token=' . $access_token . '&sign={{sign}}&timestamp={{timestamp}}&version=202212';
        $urlParts = parse_url($url);
        $paramGET = [];
        parse_str($urlParts['query'], $paramGET);
        $secret = $config['secret'];
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
            CURLOPT_POSTFIELDS => '%7B%7D=',
            CURLOPT_HTTPHEADER => array(
                'x-tts-access-token: ' . $access_token,
                'Content-Type: application/x-www-form-urlencoded'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $response = json_decode($response, true);

        if ($response['data']['shop_list'][0]['shop_cipher']) {
            $config['shop_cipher'] = $response['data']['shop_list'][0]['shop_cipher'];
            $config['shop_name'] = $response['data']['shop_list'][0]['shop_name'];
            $user = $_SESSION['user'];
            $dtt = array();
            $dtt['val'] = json_encode($config, true);
            $dtt['shop_id'] = strval($config['shop_id']);
            $dtt['updated_at'] = DATE("Y-m-d H:i:s");
            $dtt['updated_by'] = $user['id'];
            $this->db->update('marketplace_config', $dtt, array('opt' => 'tiktok', 'brand' => $brand));
            $html['status'] = true;
            $html['data'] = $response['data'];
            $html['msg'] = 'Refresh token tiktok berhasil!';
            echo json_encode($html, true);
            die;
        } else {
            $html['status'] = false;
            $html['data'] = array();
            $html['msg'] = $response['message'];
            // $html['msg'] = 'Refresh token tiktok tidak berhasil!';
            echo json_encode($html, true);
            die;
        }
    }

    public function lazada_get_product()
    {

        $brand = $_GET['brand'];

        header('Content-Type: application/json; charset=utf-8');
        $html = array();

        $config = $this->mymodel->selectWithQuery("SELECT val FROM marketplace_config WHERE opt = 'lazada' AND brand = '$brand' ");
        $config = $config[0];
        $config = json_decode($config['val'], true);

        $url = $config['partner_host'];

        $appkey = $config['app_key'];
        $appSecret = $config['app_secret'];
        $config['access_token'];

        $nomor = 0;
        $offset = 0;
        $limit = 50;
        for ($i = 1; $i < 2; $i++) {
            $c = new LazopClient($url, $appkey, $appSecret);
            $request = new LazopRequest('/products/get', 'GET');
            $request->addApiParam('filter', 'all');
            $request->addApiParam('offset', $offset);
            $request->addApiParam('limit', $limit);
            $request->addApiParam('options', '1');
            $response = $c->execute($request, $config['access_token']);
            $response = json_decode($response, true);
            // print_r($response);
            if ($response['message']) {
                $html['status'] = false;
                $html['data'] = array();
                $html['msg'] = $response['message'];
                echo json_encode($html, true);
                die;
            }
            // print_r($response);
            foreach ($response['data']['products'] as $k3 => $v3) {
                $nomor++;
                $marketplace = "LAZADA";
                $id_product = $v3['item_id'];
                $query = $this->mymodel->selectWithQuery("SELECT id 
                    FROM product_3rd WHERE id_product = '$id_product' AND marketplace = '$marketplace'
                    LIMIT 1
                    ");

                $query = $query[0];
                $dt = array();

                $dt['shop_id'] = strval($config['shop_id']);
                $dt['shop_name'] = strval($config['shop_name']);
                $dt['shop_img'] = strval($config['shop_logo']);
                $dt['id_product'] = strval($v3['item_id']);
                $dt['sku'] = strval($v3['item_id']);
                $dt['name'] = strval($v3['attributes']['name']);
                $dt['img'] = strval($v3['images'][0]);
                $dt['marketplace'] = $marketplace;


                if ($response['message']) {
                    $html['status'] = false;
                    $html['data'] = array();
                    $html['msg'] = $response['message'];
                    echo json_encode($html, true);
                    die;
                }

                $response['response']['model'] = $v3['skus'];

                if (empty($response['response']['model'])) {
                    $varian = array();
                    $varian['model_sku'] = $dt['sku'];
                    $varian['model_name'] = $dt['name'];
                    $varian['id_product'] = $dt['id_product'];
                    $varian['model_id'] = $dt['id_product'];
                    $varian['img'] = $dt['img'];
                    $response['response']['model'][0] = $varian;
                } else {
                    foreach ($response['response']['model'] as $k4 => $v4) {
                        $varian = array();
                        $varian['model_sku'] = $v4['SellerSku'];
                        if ($v4['saleProp']) {
                            foreach ($v4['saleProp'] as $k5 => $v5) {
                                $varian['model_name'] = $v5;
                            }
                        }
                        if (empty($varian['model_name'])) {
                            $varian['model_name'] = $v4['fragrance_family'];
                        }
                        if (empty($varian['model_name'])) {
                            $varian['model_name'] = $dt['name'];
                        }
                        $varian['id_product'] = $dt['id_product'];
                        $varian['model_id'] = $v4['SkuId'];
                        $varian['img'] = $v4['Images'][0];
                        $response['response']['model'][$k4] = $varian;
                    }
                }

                // if($dt['sku']=="6230210886"){
                //     print_r($response['response']['model']);
                //     die;
                // }

                $dt['json_varian'] = json_encode($response['response']['model'], true);
                $dt['count_varian'] = count($response['response']['model']);
                if ($query) {
                    $dt['updated_at'] = DATE("Y-m-d H:i:s");
                    $dt['updated_by'] = strval($user['id']);
                    $this->db->update('product_3rd', $dt, array('id' => $query['id']));
                    $id_parent = $query['id'];
                } else {
                    $dt['created_at'] = DATE("Y-m-d H:i:s");
                    $dt['created_by'] = strval($user['id']);
                    $this->db->insert('product_3rd', $dt);
                    $id_parent = $this->db->insert_id();
                }




                foreach ($response['response']['model'] as $k3 => $v3) {
                    $marketplace = "LAZADA";
                    $id_product = $v3['model_id'];
                    $query = $this->mymodel->selectWithQuery("SELECT id 
                        FROM product_variant_3rd WHERE id_product = '$id_product' AND marketplace = '$marketplace'
                        LIMIT 1
                        ");

                    $query = $query[0];

                    $dtt = array();
                    $dtt['shop_id'] = strval($config['shop_id']);
                    $dtt['shop_name'] = strval($config['shop_name']);
                    $dtt['shop_img'] = strval($config['shop_logo']);
                    $dtt['id_product'] = strval($v3['model_id']);
                    $dtt['id_product_parent'] = strval($dt['id_product']);
                    $dtt['id_parent'] = strval($id_parent);
                    $dtt['sku_parent'] = strval($dt['sku']);
                    $dtt['parent_name'] = strval($dt['name']);
                    $dtt['img_parent'] = strval($dt['img']);
                    $dtt['sku'] = strval($v3['model_sku']);
                    $dtt['name'] = strval($v3['model_name']);
                    $dtt['img'] = strval($v3['img']);
                    $dtt['marketplace'] = $marketplace;

                    $sku_selected = $dtt['sku'];
                    $exist = $this->mymodel->selectWithQuery("SELECT json
                        FROM product_variant_3rd
                        WHERE sku = '$sku_selected' AND json != ''");
                    if ($exist[0]['json']) {
                        $dtt['json'] = $exist[0]['json'];
                    }

                    if ($query) {
                        $dtt['updated_at'] = DATE("Y-m-d H:i:s");
                        $dtt['updated_by'] = strval($user['id']);
                        $this->db->update('product_variant_3rd', $dtt, array('id' => $query['id']));
                    } else {
                        $dtt['created_at'] = DATE("Y-m-d H:i:s");
                        $dtt['created_by'] = strval($user['id']);
                        $this->db->insert('product_variant_3rd', $dtt);
                    }
                }
            }

            $offset += $limit;
            if ($nomor >= intval($response['data']['total_products'])) {
                break;
            }
        }
        $html['status'] = true;
        $html['data'] = array();
        $html['msg'] = 'Sync data produk lazada berhasil!';
        echo json_encode($html, true);
        die;
    }

    public function lazada_get_order()
    {

        $brand = $_GET['brand'];

        header('Content-Type: application/json; charset=utf-8');
        $html = array();

        $user = $_SESSION['user'];

        $product = $this->mymodel->selectWithQuery("SELECT * FROM product
        ORDER BY sku ASC
        ");
        $product_arr = array();
        foreach ($product as $k => $v) {
            $product_arr[$v['id']] = $v;
        }


        $start_date = $_GET['start_date'];
        $until_date = $_GET['until_date'];

        $timestamp1 = strtotime($start_date);
        $timestamp2 = strtotime($until_date);

        $intervalInSeconds = abs($timestamp2 - $timestamp1);

        $intervalInDays = $intervalInSeconds / (24 * 60 * 60);

        // if($intervalInDays >= 1){
        //     $html['status'] = false;
        //     $html['data'] = array();
        //     $html['msg'] = 'Sync data maksimal 1 hari!';
        //     echo json_encode($html, true);
        //     die;
        // }

        $until_date = DATE('Y-m-d 00:00:00', strtotime($until_date . " +1 days"));
        $start_date = DATE("Y-m-d", strtotime($start_date));
        $until_date = DATE("Y-m-d", strtotime($until_date));

        $config = $this->mymodel->selectWithQuery("SELECT val FROM marketplace_config WHERE opt = 'lazada' AND brand = '$brand' ");
        $config = $config[0];
        $config = json_decode($config['val'], true);

        $url = $config['partner_host'];

        $appkey = $config['app_key'];
        $appSecret = $config['app_secret'];
        $config['access_token'];

        $nomor = 0;
        $offset = 0;
        $limit = 100;
        $total_data = 0;
        for ($i = 1; $i <= 1000; $i++) {

            $c = new LazopClient($url, $appkey, $appSecret);
            $request = new LazopRequest('/orders/get', 'GET');
            $request->addApiParam('sort_direction', 'ASC');
            $request->addApiParam('offset', $offset);
            $request->addApiParam('limit', $limit);
            $request->addApiParam('sort_by', 'created_at');
            $request->addApiParam('created_after', $start_date . 'T00:00:00+07:00');
            $request->addApiParam('created_before', $until_date . 'T00:00:00+07:00');

            $response = $c->execute($request, $config['access_token']);
            // echo $response;die;
            $response = json_decode($response, true);
            $total_data = $response['data']['countTotal'];
            if ($response['message']) {
                $html['status'] = false;
                $html['data'] = array();
                $html['msg'] = $response['message'];
                echo json_encode($html, true);
                die;
            }
            foreach ($response['data']['orders'] as $k3 => $v3) {
                $nomor++;

                $marketplace = "LAZADA";
                $id_marketplace = $v3['order_id'];
                $query = $this->mymodel->selectWithQuery("SELECT id,date 
                FROM transaction WHERE order_id = '$id_marketplace' AND marketplace = '$marketplace'
                LIMIT 1
                ");

                $query = $query[0];
                $dt = array();

                $dt['date'] = strval(substr($v3['created_at'], 0, 19));

                $order_status = "COMPLETED";
                if (in_array($v3['statuses'][0], array('unpaid'))) {
                    $order_status = 'UNPAID';
                } else if (in_array($v3['statuses'][0], array('topack', 'pending'))) {
                    $order_status = 'PROCESSED';
                } else if (in_array($v3['statuses'][0], array('returned'))) {
                    $order_status = 'RETURN';
                } else if (in_array($v3['statuses'][0], array('canceled', 'failed', 'lost'))) {
                    $order_status = 'CANCELLED';
                } else if (in_array($v3['statuses'][0], array('confirmed'))) {
                    $order_status = 'COMPLETED';
                    $dt['disbursement_at'] = '';
                    $dt['is_disbursement'] = '1';
                } else if (in_array($v3['statuses'][0], array('delivered'))) {
                    $order_status = 'DELIVERED';
                } else if (in_array($v3['statuses'][0], array('shipped'))) {
                    $order_status = 'SHIPPED';
                } else if (in_array($v3['statuses'][0], array('ready_to_ship', 'toship', 'shipping'))) {
                    $order_status = 'READY_TO_SHIP';
                }

                $dt['order_status'] = strval($order_status);

                $dt['type'] = "Out";
                $dt['type_sub'] = "POS";
                $dt['marketplace'] = strval($marketplace);
                $dt['brand'] = strval($brand);
                $dt['order_id'] = strval($id_marketplace);
                $dt['shop_id'] = strval($config['shop_id']);
                $dt['shop_name'] = strval($config['shop_name']);
                $dt['shop_img'] = strval($config['shop_logo']);
                $dt['c_type'] = strval("Pelanggan");
                if ($query) {
                    $dt['updated_at'] = DATE("Y-m-d H:i:s");
                    $dt['updated_by'] = strval($user['id']);
                    $this->db->update('transaction', $dt, array('id' => $query['id']));
                } else {
                    $dt['created_at'] = DATE("Y-m-d H:i:s");
                    $dt['created_by'] = strval($user['id']);
                    $this->db->insert('transaction', $dt);
                    $dt['id'] = $this->db->insert_id();
                }
            }
            $offset += $limit;
            if ($nomor >= intval($total_data)) {
                break;
            }
        }
        if ($nomor > 0) {
            foreach ($product_arr as $k => $v) {
                $id_product = $v['id'];
                $this->update_stock($id_product);
            }
        }
        $html['status'] = true;
        $html['data'] = array();
        $html['msg'] = "Sync " . intval($nomor) . " data order lazada berhasil";
        echo json_encode($html, true);
        die;
    }

    public function shopee_get_product()
    {

        $brand = $_GET['brand'];

        header('Content-Type: application/json; charset=utf-8');
        $html = array();

        $user = $_SESSION['user'];

        $config = $this->mymodel->selectWithQuery("SELECT val FROM marketplace_config WHERE opt = 'shopee' AND brand = '$brand' ");
        $config = $config[0];
        $config = json_decode($config['val'], true);

        $host = $config['partner_host'];
        $partnerId = $config['partner_id'];
        $partnerKey = $config['partner_key'];
        $shopId = intval($config['shop_id']);
        $refreshToken = $config['refresh_token'];


        $offset = 0;
        for ($i = 1; $i < 2; $i++) {

            $path = "/api/v2/product/get_item_list";
            $timest = time();
            $baseString = sprintf("%s%s%s%s%s", $partnerId, $path, $timest, $config['access_token'], $config['shop_id']);
            $sign = hash_hmac('sha256', $baseString, $partnerKey);

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $host . $path . '?partner_id=' . $config['partner_id'] . '&timestamp=' . $timest . '&shop_id=' . $config['shop_id'] . '&access_token=' . $config['access_token'] . '&sign=' . $sign
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

            if ($response['message']) {
                $html['status'] = false;
                $html['data'] = array();
                $html['msg'] = $response['message'];
                echo json_encode($html, true);
                die;
            }

            // print_r($response);
            // echo '<br>br>';
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
                $baseString = sprintf("%s%s%s%s%s", $partnerId, $path, $timest, $config['access_token'], $config['shop_id']);
                $sign = hash_hmac('sha256', $baseString, $partnerKey);

                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $host . $path . '?access_token=' . $config['access_token'] . '&item_id_list=' . $list_id . '&need_complaint_policy=true&need_tax_info=true&partner_id=' . $config['partner_id'] . '&shop_id=' . $config['shop_id'] . '&sign=' . $sign . '&timestamp=' . $timest . '',
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

                if ($response['message']) {
                    $html['status'] = false;
                    $html['data'] = array();
                    $html['msg'] = $response['message'];
                    echo json_encode($html, true);
                    die;
                }

                foreach ($response['response']['item_list'] as $k3 => $v3) {

                    $marketplace = "SHOPEE";
                    $id_product = $v3['item_id'];
                    $query = $this->mymodel->selectWithQuery("SELECT id 
                    FROM product_3rd WHERE id_product = '$id_product' AND marketplace = '$marketplace'
                    LIMIT 1
                    ");

                    $query = $query[0];
                    $dt = array();

                    $dt['shop_id'] = strval($config['shop_id']);
                    $dt['shop_name'] = strval($config['shop_name']);
                    $dt['shop_img'] = strval($config['shop_logo']);
                    $dt['id_product'] = strval($v3['item_id']);
                    $dt['sku'] = strval($v3['item_sku']);
                    $dt['name'] = strval($v3['item_name']);
                    $dt['img'] = strval($v3['image']['image_url_list'][0]);
                    $dt['marketplace'] = $marketplace;

                    $path = "/api/v2/product/get_model_list";
                    $timest = time();
                    $baseString = sprintf("%s%s%s%s%s", $partnerId, $path, $timest, $config['access_token'], $config['shop_id']);
                    $sign = hash_hmac('sha256', $baseString, $partnerKey);

                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $host . $path . '?access_token=' . $config['access_token'] . '&item_id=' . $dt['id_product'] . '&need_complaint_policy=true&need_tax_info=true&partner_id=' . $config['partner_id'] . '&shop_id=' . $config['shop_id'] . '&sign=' . $sign . '&timestamp=' . $timest . '',
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

                    if ($response['message']) {
                        $html['status'] = false;
                        $html['data'] = array();
                        $html['msg'] = $response['message'];
                        echo json_encode($html, true);
                        die;
                    }

                    if (empty($response['response']['model'])) {
                        $varian = array();
                        $varian['model_sku'] = $dt['sku'];
                        $varian['model_name'] = $dt['name'];
                        $varian['id_product'] = $dt['id_product'];
                        $varian['model_id'] = $dt['id_product'];
                        $response['response']['model'][0] = $varian;
                    }

                    $dt['json_varian'] = json_encode($response['response']['model'], true);
                    $dt['count_varian'] = count($response['response']['model']);

                    if ($query) {
                        $dt['updated_at'] = DATE("Y-m-d H:i:s");
                        $dt['updated_by'] = strval($user['id']);
                        $this->db->update('product_3rd', $dt, array('id' => $query['id']));
                        $id_parent = $query['id'];
                    } else {
                        $dt['created_at'] = DATE("Y-m-d H:i:s");
                        $dt['created_by'] = strval($user['id']);
                        $this->db->update('product_3rd', $dt);
                        $id_parent = $this->db->insert_id();
                    }




                    foreach ($response['response']['model'] as $k3 => $v3) {
                        $marketplace = "SHOPEE";
                        $id_product = $v3['model_id'];
                        $query = $this->mymodel->selectWithQuery("SELECT id 
                        FROM product_variant_3rd WHERE id_product = '$id_product' AND marketplace = '$marketplace'
                        LIMIT 1
                        ");

                        $query = $query[0];

                        $dtt = array();
                        $dtt['shop_id'] = strval($config['shop_id']);
                        $dtt['shop_name'] = strval($config['shop_name']);
                        $dtt['shop_img'] = strval($config['shop_logo']);
                        $dtt['id_product'] = strval($v3['model_id']);
                        $dtt['id_product_parent'] = strval($dt['id_product']);
                        $dtt['id_parent'] = strval($id_parent);
                        $dtt['sku_parent'] = strval($dt['sku']);
                        $dtt['parent_name'] = strval($dt['name']);
                        $dtt['img_parent'] = strval($dt['img']);
                        $dtt['sku'] = strval($v3['model_sku']);
                        $dtt['name'] = strval($v3['model_name']);
                        $dtt['img'] = strval($response['response']['tier_variation'][$k3]['option_list'][0]['image']['image_url']);
                        $dtt['marketplace'] = $marketplace;

                        $sku_selected = $dtt['sku'];
                        $exist = $this->mymodel->selectWithQuery("SELECT json
                        FROM product_variant_3rd
                        WHERE sku = '$sku_selected' AND json != ''");
                        if ($exist[0]['json']) {
                            $dtt['json'] = $exist[0]['json'];
                        }

                        if ($query) {
                            $dtt['updated_at'] = DATE("Y-m-d H:i:s");
                            $dtt['updated_by'] = strval($user['id']);
                            $this->db->update('product_variant_3rd', $dtt, array('id' => $query['id']));
                        } else {
                            $dtt['created_at'] = DATE("Y-m-d H:i:s");
                            $dtt['created_by'] = strval($user['id']);
                            $this->db->insert('product_variant_3rd', $dtt);
                        }
                    }
                }
            }
            if (empty($offset)) {
                break;
            }
        }
        $html['status'] = true;
        $html['data'] = array();
        $html['msg'] = "Sync data produk shopee berhasil";
        echo json_encode($html, true);
        die;
    }

    public function shopee_get_order()
    {
        $nomor = 0;
        $brand = $_GET['brand'];
        header('Content-Type: application/json; charset=utf-8');
        $html = array();

        $product = $this->mymodel->selectWithQuery("SELECT * FROM product
        ORDER BY sku ASC
        ");
        $product_arr = array();
        foreach ($product as $k => $v) {
            $product_arr[$v['id']] = $v;
        }

        $user = $_SESSION['user'];

        $config = $this->mymodel->selectWithQuery("SELECT val FROM marketplace_config WHERE opt = 'shopee' AND brand = '$brand' ");
        $config = $config[0];
        $config = json_decode($config['val'], true);

        $host = $config['partner_host'];
        $partnerId = $config['partner_id'];
        $partnerKey = $config['partner_key'];
        $shopId = intval($config['shop_id']);
        $refreshToken = $config['refresh_token'];

        $start_date = $_GET['start_date'];
        $until_date = $_GET['until_date'];

        $timestamp1 = strtotime($start_date);
        $timestamp2 = strtotime($until_date);

        $intervalInSeconds = abs($timestamp2 - $timestamp1);

        $intervalInDays = $intervalInSeconds / (24 * 60 * 60);

        // if($intervalInDays >= 1){
        //     $html['status'] = false;
        //     $html['data'] = array();
        //     $html['msg'] = 'Sync data maksimal 1 hari!';
        //     echo json_encode($html, true);
        //     die;
        // }

        $start_time = strtotime($start_date);
        $until_time = $until_date . '';
        $until_time = DATE('Y-m-d 00:00:00', strtotime($until_time . " +1 days"));
        $until_time = strtotime($until_time);

        // ORDER
        $cursor = 0;
        for ($i = 1; $i <= 1000; $i++) {
            // break;
            $path = "/api/v2/order/get_order_list";
            $timest = time();
            $baseString = sprintf("%s%s%s%s%s", $partnerId, $path, $timest, $config['access_token'], $config['shop_id']);
            $sign = hash_hmac('sha256', $baseString, $partnerKey);

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $host . $path . '?partner_id=' . $config['partner_id'] . '&timestamp=' . $timest . '&shop_id=' . $config['shop_id'] . '&access_token=' . $config['access_token'] . '&sign=' . $sign
                    . '&time_range_field=create_time&time_from=' . $start_time . '&time_to=' . $until_time . '&page_size=100&cursor=' . $cursor . '&response_optional_fields=order_status',
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
            if ($response['message']) {
                $html['status'] = false;
                $html['data'] = array();
                $html['msg'] = $response['message'];
                echo json_encode($html, true);
                die;
            }

            if ($response['response']['next_cursor']) {
                $cursor = $response['response']['next_cursor'];
            } else {
                $cursor = $response['response']['next_cursor'];
            }
            foreach ($response['response']['order_list'] as $k3 => $v3) {
                $nomor++;
                $marketplace = "SHOPEE";
                $id_marketplace = $v3['order_sn'];
                $query = $this->mymodel->selectWithQuery("SELECT id,date 
                    FROM transaction WHERE order_id = '$id_marketplace' 
                    -- AND marketplace = '$marketplace'
                    LIMIT 1
                    ");

                $query = $query[0];
                $dt = array();

                if (empty($query['date'])) {
                    $dt['date'] = strval($start_date . ' 00:00:01');
                }


                $order_status = $v3['order_status'];
                if ($v3['order_status'] == "TO_CONFIRM_RECEIVE") {
                    $order_status = "DELIVERED";
                }

                if (!in_array($query['order_status'], array('CANCELLED', 'REFUND', 'RETURN'))) {
                    if ($order_status) {
                        $dt['order_status'] = strval($order_status);
                    }
                }

                $dt['type'] = "Out";
                $dt['type_sub'] = "POS";
                $dt['marketplace'] = strval($marketplace);
                $dt['brand'] = strval($brand);
                $dt['order_id'] = strval($id_marketplace);
                $dt['shop_id'] = strval($config['shop_id']);
                $dt['shop_name'] = strval($config['shop_name']);
                $dt['shop_img'] = strval($config['shop_logo']);

                $dt['c_type'] = strval("Pelanggan");
                if ($query) {
                    $dt['updated_at'] = DATE("Y-m-d H:i:s");
                    $dt['updated_by'] = strval($user['id']);
                    $this->db->update('transaction', $dt, array('id' => $query['id']));
                } else {
                    $dt['created_at'] = DATE("Y-m-d H:i:s");
                    $dt['created_by'] = strval($user['id']);
                    $this->db->insert('transaction', $dt);
                    $dt['id'] = $this->db->insert_id();
                }
            }
            // }
            if (empty($cursor)) {
                break;
            }
        }

        //RETURN
        $cursor = 0;
        for ($i = 1; $i <= 1000; $i++) {
            $path = "/api/v2/returns/get_return_list";
            $timest = time();
            $baseString = sprintf("%s%s%s%s%s", $partnerId, $path, $timest, $config['access_token'], $config['shop_id']);
            $sign = hash_hmac('sha256', $baseString, $partnerKey);

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $host . $path . '?partner_id=' . $config['partner_id'] . '&timestamp=' . $timest . '&shop_id=' . $config['shop_id'] . '&access_token=' . $config['access_token'] . '&sign=' . $sign
                    . '&create_time_from=' . $start_time . '&create_time_to=' . $until_time . '&page_size=100&page_no=' . $cursor . '',
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
            if ($response['message']) {
                $html['status'] = false;
                $html['data'] = array();
                $html['msg'] = $response['message'];
                echo json_encode($html, true);
                die;
            }

            if ($response['response']['next_cursor']) {
                $cursor = $response['response']['next_cursor'];
            } else {
                $cursor = $response['response']['next_cursor'];
            }
            foreach ($response['response']['return'] as $k3 => $v3) {
                $nomor++;
                $marketplace = "SHOPEE";
                $id_marketplace = $v3['order_sn'];
                $query = $this->mymodel->selectWithQuery("SELECT id,date 
                    FROM transaction WHERE order_id = '$id_marketplace'
                    -- AND marketplace = '$marketplace'
                    LIMIT 1
                    ");

                $query = $query[0];
                $dt = array();
                $dt['date'] = strval(DATE("Y-m-d H:i:s", $v3['create_time']));

                $dt['order_status'] = 'RETURN';


                $dt['type'] = "Out";
                $dt['type_sub'] = "POS";
                $dt['marketplace'] = strval($marketplace);
                $dt['brand'] = strval($brand);
                $dt['order_id'] = strval($id_marketplace);
                $dt['shop_id'] = strval($config['shop_id']);
                $dt['shop_name'] = strval($config['shop_name']);
                $dt['shop_img'] = strval($config['shop_logo']);

                $dt['c_type'] = strval("Pelanggan");
                if ($query) {
                    $dt['updated_at'] = DATE("Y-m-d H:i:s");
                    $dt['updated_by'] = strval($user['id']);
                    $this->db->update('transaction', $dt, array('id' => $query['id']));
                } else {
                    $dt['created_at'] = DATE("Y-m-d H:i:s");
                    $dt['created_by'] = strval($user['id']);
                    $this->db->insert('transaction', $dt);
                    $dt['id'] = $this->db->insert_id();
                }
            }
            // }
            if (empty($cursor)) {
                break;
            }
        }


        $html['status'] = true;
        $html['data'] = array();
        $html['msg'] = "Sync " . intval($nomor) . " data order shopee berhasil";
        echo json_encode($html, true);
        die;
    }

    public function tiktok_get_product()
    {

        $brand = $_GET['brand'];

        header('Content-Type: application/json; charset=utf-8');
        $html = array();

        $config = $this->mymodel->selectWithQuery("SELECT val FROM marketplace_config WHERE opt = 'tiktok' AND brand = '$brand' ");
        $config = $config[0];
        $config = json_decode($config['val'], true);

        $nomor = 0;
        $offset = 0;
        $limit = 50;
        $total_data = 0;
        for ($i = 1; $i < 2; $i++) {
            $access_token = $config['access_token'];
            $app_key = $config['app_key'];
            $shop_id = $config['shop_id'];
            $shop_cipher = $config['cipher'];

            $url = 'https://open-api.tiktokglobalshop.com/api/products/search?access_token=' . $access_token . '&app_key=' . $app_key . '&shop_id=' . $shop_id . '&sign={{sign}}&timestamp={{timestamp}}&version=202212';
            $urlParts = parse_url($url);
            $paramGET = [];
            parse_str($urlParts['query'], $paramGET);
            $secret = $config['secret'];
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
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{"page_number":' . $i . ',"page_size":100}',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'x-tts-access-token: ' . $access_token
                ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);

            $response = json_decode($response, true);

            $total_data = $response['data']['total'];

            if ($response['message'] != "Success") {
                $html['status'] = false;
                $html['data'] = array();
                $html['msg'] = $response['message'];
                echo json_encode($html, true);
                die;
            }
            $nomor = 0;
            foreach ($response['data']['products'] as $k3 => $v3) {
                $marketplace = "TIKTOK";
                $id_product = $v3['id'];
                $query = $this->mymodel->selectWithQuery("SELECT id 
                    FROM product_3rd WHERE id_product = '$id_product' AND marketplace = '$marketplace'
                    LIMIT 1
                    ");

                $query = $query[0];
                $dt = array();

                $dt['shop_id'] = strval($config['shop_id']);
                $dt['shop_name'] = strval($config['shop_name']);
                $dt['shop_img'] = strval($config['shop_logo']);
                $dt['id_product'] = strval($v3['id']);
                $dt['sku'] = strval($v3['item_id']);
                $dt['name'] = strval($v3['name']);
                $dt['img'] = strval($v3['images'][0]);
                $dt['marketplace'] = $marketplace;


                $url = 'https://open-api.tiktokglobalshop.com/api/products/details?access_token=' . $access_token . '&app_key=' . $app_key . '&product_id=' . $id_product . '&shop_id=' . $shop_id . '&sign={{sign}}&timestamp={{timestamp}}&version=202306';
                $urlParts = parse_url($url);
                $paramGET = [];
                parse_str($urlParts['query'], $paramGET);
                $secret = $config['secret'];
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
                    CURLOPT_POSTFIELDS => '%7B%7D=',
                    CURLOPT_HTTPHEADER => array(
                        'x-tts-access-token: ' . $access_token,
                        'Content-Type: application/x-www-form-urlencoded'
                    ),
                ));

                $response = curl_exec($curl);

                curl_close($curl);

                $response = json_decode($response, true);

                $dt['img'] = strval($response['data']['images'][0]['url_list'][0]);

                if ($response['message'] != 'Success') {
                    $html['status'] = false;
                    $html['data'] = array();
                    $html['msg'] = $response['message'];
                    echo json_encode($html, true);
                    die;
                }

                $response['response']['model'] = $response['data']['skus'];

                if (empty($response['response']['model'])) {
                    $varian = array();
                    $varian['model_sku'] = $dt['sku'];
                    $varian['model_name'] = $dt['name'];
                    $varian['id_product'] = $dt['id_product'];
                    $varian['model_id'] = $dt['id_product'];
                    $varian['img'] = $dt['img'];
                    $response['response']['model'][0] = $varian;
                } else if (count($response['response']['model']) <= 1) {
                    $varian = array();
                    $v4 = $response['response']['model'][0];
                    $varian['model_sku'] = $v4['seller_sku'];
                    $varian['model_name'] = $dt['name'];
                    $varian['id_product'] = $dt['id_product'];
                    $varian['model_id'] = $dt['id_product'];
                    $varian['img'] = $dt['img'];
                    $response['response']['model'][0] = $varian;
                } else {
                    foreach ($response['response']['model'] as $k4 => $v4) {
                        $detail = $v4['sales_attributes'][0];
                        $varian = array();
                        $varian['model_sku'] = $v4['seller_sku'];
                        $varian['model_name'] = $detail['value_name'];
                        if (empty($varian['model_name'])) {
                            $varian['model_name'] = $dt['name'];
                        }
                        $varian['id_product'] = $dt['id_product'];
                        $varian['model_id'] = $v4['id'];
                        $varian['img'] = strval($detail['sku_img']['url_list'][0]);
                        $response['response']['model'][$k4] = $varian;
                    }
                }


                $dt['json_varian'] = json_encode($response['response']['model'], true);
                $dt['count_varian'] = count($response['response']['model']);
                if ($query) {
                    $dt['updated_at'] = DATE("Y-m-d H:i:s");
                    $dt['updated_by'] = strval($user['id']);
                    $this->db->update('product_3rd', $dt, array('id' => $query['id']));
                    $id_parent = $query['id'];
                } else {
                    $dt['created_at'] = DATE("Y-m-d H:i:s");
                    $dt['created_by'] = strval($user['id']);
                    $this->db->insert('product_3rd', $dt);
                    $id_parent = $this->db->insert_id();
                }




                foreach ($response['response']['model'] as $k3 => $v3) {
                    $marketplace = "TIKTOK";
                    $id_product = $v3['model_id'];
                    $query = $this->mymodel->selectWithQuery("SELECT id 
                        FROM product_variant_3rd WHERE id_product = '$id_product' AND marketplace = '$marketplace'
                        LIMIT 1
                        ");

                    $query = $query[0];

                    $dtt = array();
                    $dtt['shop_id'] = strval($config['shop_id']);
                    $dtt['shop_name'] = strval($config['shop_name']);
                    $dtt['shop_img'] = strval($config['shop_logo']);
                    $dtt['id_product'] = strval($v3['model_id']);
                    $dtt['id_product_parent'] = strval($dt['id_product']);
                    $dtt['id_parent'] = strval($id_parent);
                    $dtt['sku_parent'] = strval($dt['sku']);
                    $dtt['parent_name'] = strval($dt['name']);
                    $dtt['img_parent'] = strval($dt['img']);
                    $dtt['sku'] = strval($v3['model_sku']);
                    $dtt['name'] = strval($v3['model_name']);
                    $dtt['img'] = strval($v3['img']);
                    $dtt['marketplace'] = $marketplace;

                    $sku_selected = $dtt['sku'];
                    $exist = $this->mymodel->selectWithQuery("SELECT json
                        FROM product_variant_3rd
                        WHERE sku = '$sku_selected' AND json != ''");
                    if ($exist[0]['json']) {
                        $dtt['json'] = $exist[0]['json'];
                    }

                    if ($query) {
                        $dtt['updated_at'] = DATE("Y-m-d H:i:s");
                        $dtt['updated_by'] = strval($user['id']);
                        $this->db->update('product_variant_3rd', $dtt, array('id' => $query['id']));
                    } else {
                        $dtt['created_at'] = DATE("Y-m-d H:i:s");
                        $dtt['created_by'] = strval($user['id']);
                        $this->db->insert('product_variant_3rd', $dtt);
                    }
                }
                $nomor++;
            }
            if ($nomor >= intval($total_data)) {
                break;
            }
        }
        $html['status'] = true;
        $html['data'] = array();
        $html['msg'] = 'Sync data produk tiktok berhasil!';
        echo json_encode($html, true);
        die;
    }
    public function tiktok_get_finance()
    {

        $brand = $_GET['brand'];

        header('Content-Type: application/json; charset=utf-8');
        $html = array();

        $user = $_SESSION['user'];

        $config = $this->mymodel->selectWithQuery("SELECT val FROM marketplace_config WHERE opt = 'tiktok' AND brand = '$brand' ");
        $config = $config[0];
        $config = json_decode($config['val'], true);

        $url = $config['partner_host'];

        $appkey = $config['app_key'];
        $appSecret = $config['app_secret'];
        $config['access_token'];
        $marketplace = 'TIKTOK';
        $data = $this->mymodel->selectWithQuery("SELECT id,order_id,price_total FROM transaction WHERE order_status IN ('COMPLETED') AND dana_pencairan = 0 AND marketplace = '$marketplace' AND brand = '$brand'
        -- AND order_id = '576673928922761193'
        ORDER BY updated_at ASC
        LIMIT 20
        ");

        foreach ($data as $k => $v) {
            $order_id = $v['order_id'];

            $access_token = $config['access_token'];
            $app_key = $config['app_key'];
            $shop_id = $config['shop_id'];
            $shop_cipher = $config['cipher'];

            $url = 'https://open-api.tiktokglobalshop.com/api/finance/order/settlements?access_token=' . $access_token . '&app_key=' . $app_key . '&order_id=' . $order_id . '&shop_cipher=&shop_id=&sign={{sign}}&timestamp={{timestamp}}&version=202212';
            $urlParts = parse_url($url);
            $paramGET = [];
            parse_str($urlParts['query'], $paramGET);
            $secret = $config['secret'];
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
                CURLOPT_POSTFIELDS => '%7B%7D=',
                CURLOPT_HTTPHEADER => array(
                    'x-tts-access-token: ' . $access_token,
                    'Content-Type: application/x-www-form-urlencoded'
                ),
            ));
            $response = curl_exec($curl);


            curl_close($curl);
            $response = json_decode($response, true);
            $detail = $response['data']['settlement_list'][0]['settlement_info'];
            if ($detail['settlement_amount']) {
                // echo doubleval($v['price_total']);die;
                $dt = array();
                $dt['komisi_afiliasi'] = doubleval($detail['affiliate_commission']);
                $dt['omset_kotor'] = doubleval($v['price_total']);
                $dt['diskon_penjual'] = abs(doubleval($detail['subtotal_after_seller_discounts']) - doubleval($v['price_total']));
                $dt['omset_bersih'] = doubleval($detail['subtotal_after_seller_discounts']);
                $dt['marketplace_fee'] = doubleval($detail['platform_commission']) + doubleval($detail['sfp_service_fee']);
                $dt['dana_pencairan'] = doubleval($detail['settlement_amount']);
                $dt['pencairan_status'] = '';
                $dt['pencairan_at'] = DATE("Y-m-d H:i:s", ($detail['settlement_time']));
            }
            $dt['updated_at'] = DATE("Y-m-d H:i:s");
            $dt['updated_by'] = strval($user['id']);

            $this->db->update('transaction', $dt, array('id' => $v['id']));
        }
        $html['status'] = true;
        $html['data'] = array();
        $html['msg'] = 'Sync data finance tiktok berhasil!';
        echo json_encode($html, true);
        die;
    }

    public function lazada_get_finance()
    {

        $brand = $_GET['brand'];

        header('Content-Type: application/json; charset=utf-8');
        $html = array();

        $user = $_SESSION['user'];

        $config = $this->mymodel->selectWithQuery("SELECT val FROM marketplace_config WHERE opt = 'lazada' AND brand = '$brand' ");
        $config = $config[0];
        $config = json_decode($config['val'], true);

        $url = $config['partner_host'];

        $appkey = $config['app_key'];
        $appSecret = $config['app_secret'];
        $config['access_token'];
        $marketplace = 'LAZADA';
        $data = $this->mymodel->selectWithQuery("SELECT id,order_id,price_total,date FROM transaction WHERE order_status IN ('COMPLETED') AND dana_pencairan = 0 AND marketplace = '$marketplace'
        -- AND order_id = '1410405946954551'
        ORDER BY updated_at ASC
        LIMIT 20
        ");

        foreach ($data as $k => $v) {
            $order_id = $v['order_id'];

            $url = $config['partner_host'];

            $appkey = $config['app_key'];
            $appSecret = $config['app_secret'];
            $config['access_token'];
            $c = new LazopClient($url, $appkey, $appSecret);
            $request = new LazopRequest('/order/items/get', 'GET');
            $request->addApiParam('order_id', $order_id);
            $response_item = $c->execute($request, $config['access_token']);
            $response_item;
            $response_item = json_decode($response_item, true);
            $start_date = date("Y-m-1", strtotime($v['date']));
            $until_date = date("Y-m-t", strtotime($start_date . " +1 months"));
            $c = new LazopClient($url, $appkey, $appSecret);
            $request = new LazopRequest('/finance/transaction/details/get', 'GET');
            $request->addApiParam('offset', '0');
            $request->addApiParam('trade_order_id', $order_id);
            $request->addApiParam('limit', '100');
            $request->addApiParam('start_time', $start_date);
            $request->addApiParam('end_time', $until_date);
            $response_vat = $c->execute($request, $config['access_token']);
            // echo $response_vat;die;
            $response_vat = json_decode($response_vat, true);
            // die;
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
            }

            if (doubleval($price_total - $price_admin - $diskon_penjual) > 0) {
                $dt = array();
                $dt['komisi_afiliasi'] = doubleval(0);
                $dt['omset_kotor'] = doubleval($price_total);
                $dt['diskon_penjual'] = doubleval($diskon_penjual);
                $dt['omset_bersih'] = doubleval($price_total) - doubleval($diskon_penjual);
                $dt['marketplace_fee'] = doubleval($price_admin);
                $dt['dana_pencairan'] = doubleval($price_total - $price_admin - $diskon_penjual);
                $dt['pencairan_status'] = '';
                $dt['pencairan_at'] = DATE("Y-m-d H:i:s", ($detail['settlement_time']));
            }
            // print_r($response_vat);die;
            // print_r($dt);die;          
            $dt['updated_at'] = DATE("Y-m-d H:i:s");
            $dt['updated_by'] = strval($user['id']);

            $this->db->update('transaction', $dt, array('id' => $v['id']));
        }
        $html['status'] = true;
        $html['data'] = array();
        $html['msg'] = 'Sync data finance lazada berhasil!';
        echo json_encode($html, true);
        die;
    }

    public function shopee_get_finance()
    {

        $brand = $_GET['brand'];

        header('Content-Type: application/json; charset=utf-8');
        $html = array();

        $user = $_SESSION['user'];

        $config = $this->mymodel->selectWithQuery("SELECT val FROM marketplace_config WHERE opt = 'shopee' AND brand = '$brand' ");
        $config = $config[0];
        $config = json_decode($config['val'], true);

        $url = $config['partner_host'];

        $appkey = $config['app_key'];
        $appSecret = $config['app_secret'];
        $config['access_token'];
        $marketplace = 'SHOPEE';
        $data = $this->mymodel->selectWithQuery("SELECT id,order_id,price_total FROM transaction WHERE order_status IN ('COMPLETED') AND dana_pencairan = 0 AND marketplace = '$marketplace'
        -- AND order_id = '240318DJPAKQ3J'
        ORDER BY updated_at ASC
        LIMIT 20
        ");

        foreach ($data as $k => $v) {
            $order_id = $v['order_id'];
            $list_id = $order_id;

            $host = $config['partner_host'];
            $partnerId = $config['partner_id'];
            $partnerKey = $config['partner_key'];
            $shopId = intval($config['shop_id']);
            $refreshToken = $config['refresh_token'];

            $path = "/api/v2/payment/get_escrow_detail";
            $timest = time();
            $baseString = sprintf("%s%s%s%s%s", $partnerId, $path, $timest, $config['access_token'], $config['shop_id']);
            $sign = hash_hmac('sha256', $baseString, $partnerKey);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $host . $path . '?access_token=' . $config['access_token'] . '&order_sn=' . $list_id . '&response_optional_fields=buyer_user_id,buyer_username,estimated_shipping_fee,recipient_address,actual_shipping_fee,goods_to_declare,note,note_update_time,item_list,pay_time,dropshipper,dropshipper_phone,split_up,buyer_cancel_reason,cancel_by,cancel_reason,actual_shipping_fee_confirmed,buyer_cpf_id,fulfillment_flag,pickup_done_time,package_list,shipping_carrier,payment_method,total_amount,buyer_username,invoice_data,no_plastic_packing,order_chargeable_weight_gram,edt,return_due_date&request_order_status_pending=true&partner_id=' . $config['partner_id'] . '&shop_id=' . $config['shop_id'] . '&sign=' . $sign . '&timestamp=' . $timest . '',
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

            $detail = $response['response']['order_income'];
            if ($detail['escrow_amount']) {
                $dt = array();
                $dt['komisi_afiliasi'] = doubleval($detail['order_ams_commission_fee']);
                $dt['omset_kotor'] = doubleval($detail['original_price']);
                $dt['diskon_penjual'] = doubleval($detail['voucher_from_seller']);
                $dt['omset_bersih'] = doubleval($detail['original_price']) - doubleval($detail['voucher_from_seller']);
                $dt['marketplace_fee'] = doubleval($detail['commission_fee']) + doubleval($detail['service_fee']);
                $dt['dana_pencairan'] = doubleval($detail['escrow_amount']);
                $dt['pencairan_status'] = '';
                $dt['pencairan_at'] = DATE("Y-m-d H:i:s", ($detail['settlement_time']));
                // print_r($dt);die;
            }
            $dt['updated_at'] = DATE("Y-m-d H:i:s");
            $dt['updated_by'] = strval($user['id']);

            $this->db->update('transaction', $dt, array('id' => $v['id']));
        }
        $html['status'] = true;
        $html['data'] = array();
        $html['msg'] = 'Sync data finance shopee berhasil!';
        echo json_encode($html, true);
        die;
    }

    public function tiktok_get_order()
    {

        header('Content-Type: application/json; charset=utf-8');
        $html = array();

        $user = $_SESSION['user'];

        $product = $this->mymodel->selectWithQuery("SELECT * FROM product
        ORDER BY sku ASC
        ");
        $product_arr = array();
        foreach ($product as $k => $v) {
            $product_arr[$v['id']] = $v;
        }

        $brand = $_GET['brand'];
        $start_date = $_GET['start_date'];
        $until_date = $_GET['until_date'];

        $timestamp1 = strtotime($start_date);
        $timestamp2 = strtotime($until_date);

        $intervalInSeconds = abs($timestamp2 - $timestamp1);

        $intervalInDays = $intervalInSeconds / (24 * 60 * 60);

        // if($intervalInDays >= 1){
        //     $html['status'] = false;
        //     $html['data'] = array();
        //     $html['msg'] = 'Sync data maksimal 1 hari!';
        //     echo json_encode($html, true);
        //     die;
        // }

        $until_date = DATE('Y-m-d 00:00:00', strtotime($until_date . " +1 days"));
        $start_date = DATE("Y-m-d", strtotime($start_date));
        $until_date = DATE("Y-m-d", strtotime($until_date));

        $start_time = strtotime($start_date);
        $until_time = $until_date . '';
        $until_time = DATE('Y-m-d 00:00:00', strtotime($until_time . " +1 days"));
        $until_time = strtotime($until_time);

        $config = $this->mymodel->selectWithQuery("SELECT val FROM marketplace_config WHERE opt = 'tiktok' AND brand = '$brand' ");
        $config = $config[0];
        $config = json_decode($config['val'], true);

        $url = $config['partner_host'];

        $appkey = $config['app_key'];
        $appSecret = $config['app_secret'];
        $config['access_token'];

        $nomor = 0;
        $offset = 0;
        $limit = 100;
        $total_data = 0;
        $page_token = "";
        $cursor = "";
        for ($i = 1; $i <= 1000; $i++) {
            $access_token = $config['access_token'];
            $app_key = $config['app_key'];
            $shop_id = $config['shop_id'];
            $shop_cipher = $config['cipher'];
            $url = 'https://open-api.tiktokglobalshop.com/api/orders/search?access_token=' . $access_token . '&app_key=' . $app_key . '&shop_id=' . $shop_id . '&sign={{sign}}&timestamp={{timestamp}}&version=202212';
            $urlParts = parse_url($url);
            $paramGET = [];
            parse_str($urlParts['query'], $paramGET);
            $secret = $config['secret'];
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
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{"cursor":"' . $cursor . '","page_size":100,"sort_by":"CREATE_TIME","create_time_from":' . $start_time . ',"create_time_to":' . $until_time . ',"sort_type":2}',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'x-tts-access-token: ' . $access_token
                ),
            ));
            $response = curl_exec($curl);
            $response = json_decode($response, true);

            $cursor = $response['data']['next_cursor'];

            $is_break = false;

            if (empty($response['data']['order_list'])) {
                $is_break = true;
            }

            if ($response['message'] != 'Success') {
                $html['status'] = false;
                $html['data'] = array();
                $html['msg'] = $response['message'];
                echo json_encode($html, true);
                die;
            }

            foreach ($response['data']['order_list'] as $k3 => $v3) {
                $nomor++;

                $marketplace = "TIKTOK";
                $id_marketplace = $v3['order_id'];
                $query = $this->mymodel->selectWithQuery("SELECT id,date 
                    FROM transaction WHERE order_id = '$id_marketplace'
                    --  AND marketplace = '$marketplace'
                    LIMIT 1
                    ");

                $query = $query[0];
                $dt = array();

                if (empty($query['date'])) {
                    $dt['date'] = strval($start_date . ' 00:00:01');
                    // $dt['date'] = strval(DATE("Y-m-d H:i:s",$v3['update_time'])); 
                }


                if (in_array($v3['order_status'], array('100'))) {
                    $order_status = 'UNPAID';
                } else if (in_array($v3['order_status'], array('112', '105'))) {
                    $order_status = 'PROCESSED';
                } else if (in_array($v3['order_status'], array('returned'))) {
                    $order_status = 'RETURN';
                } else if (in_array($v3['order_status'], array('140'))) {
                    $order_status = 'CANCELLED';
                } else if (in_array($v3['order_status'], array('130',))) {
                    $order_status = 'COMPLETED';
                    $dt['disbursement_at'] = '';
                    $dt['is_disbursement'] = '1';
                } else if (in_array($v3['order_status'], array('122'))) {
                    $order_status = 'COMPLETED';
                } else if (in_array($v3['order_status'], array('121', '114'))) {
                    $order_status = 'SHIPPED';
                } else if (in_array($v3['order_status'], array('111'))) {
                    $order_status = 'READY_TO_SHIP';
                }

                $dt['order_status'] = strval($order_status);

                $dt['type'] = "Out";
                $dt['type_sub'] = "POS";
                $dt['marketplace'] = strval($marketplace);
                $dt['brand'] = strval($brand);
                $dt['order_id'] = strval($id_marketplace);
                $dt['shop_id'] = strval($config['shop_id']);
                $dt['shop_name'] = strval($config['shop_name']);
                $dt['shop_img'] = strval($config['shop_logo']);

                $dt['c_type'] = strval("Pelanggan");
                if ($query) {
                    $dt['updated_at'] = DATE("Y-m-d H:i:s");
                    $dt['updated_by'] = strval($user['id']);
                    $this->db->update('transaction', $dt, array('id' => $query['id']));
                } else {
                    $dt['created_at'] = DATE("Y-m-d H:i:s");
                    $dt['created_by'] = strval($user['id']);
                    $this->db->insert('transaction', $dt);
                    $dt['id'] = $this->db->insert_id();
                }
            }
            if ($is_break) {
                break;
            }
        }

        if ($nomor > 0) {
            foreach ($product_arr as $k => $v) {
                $id_product = $v['id'];
                $this->update_stock($id_product);
            }
        }

        $html['status'] = true;
        $html['data'] = array();
        $html['msg'] = "Sync " . intval($nomor) . " data order tiktok berhasil";
        echo json_encode($html, true);
        die;
    }


    public function update_stock($id_product)
    {

        $dtp = array();
        $id_product = $id_product;
        $query = $this->mymodel->selectWithQuery("SELECT SUM(qty_in) as qty_in,SUM(qty_out) as qty_out,SUM(qty_out_pos) as qty_out_pos,SUM(qty) as qty FROM stock WHERE product = '$id_product'");

        $dtp['stock_in'] = strval($query[0]['qty_in']);
        $dtp['stock_out'] = strval(abs($query[0]['qty_out']) * -1);
        $dtp['stock_out_pos'] = strval(abs($query[0]['qty_out_pos']) * -1);
        $dtp['stock'] = strval(doubleval($query[0]['qty']));
        $this->db->update('product', $dtp, array('id' => $id_product));
    }

    function customer_summary($id_customer)
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
        SUM(transaction.return) as returnn FROM transaction WHERE customer = '$id_customer' AND type_sub = 'POS'");

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

        $query = $this->mymodel->selectWithQuery("SELECT id,date FROM transaction WHERE customer = '$id_customer' AND type_sub = 'POS' AND order_status NOT IN ('CANCELLED','IN_CANCELLED','RETURN','REFUND') ORDER BY date ASC LIMIT 1");

        $dtt['first_order'] = strval($query[0]['date']);

        $dt = array();
        $dt['cb_cl'] = 'CL';
        $id = $query[0]['id'];

        $this->db->update('transaction', $dt, array('customer' => $id_customer));
        $dt = array();
        $dt['cb_cl'] = 'CB';
        $id = $query[0]['id'];

        $this->db->update('transaction', $dt, array('id' => $id));


        $query = $this->mymodel->selectWithQuery("SELECT date FROM transaction WHERE customer = '$id_customer' AND type_sub = 'POS' AND order_status NOT IN ('CANCELLED','IN_CANCELLED','RETURN','REFUND') ORDER BY date DESC LIMIT 1");

        $dtt['last_order'] = strval($query[0]['date']);


        $json = array();
        $price_total_hpp = 0;
        $query = $this->mymodel->selectWithQuery("SELECT id,date,json,pesanan,is_manual,order_id FROM transaction WHERE customer = '$id_customer' AND type_sub = 'POS' AND order_status NOT IN ('CANCELLED','IN_CANCELLED','RETURN','REFUND') ORDER BY date ASC");

        foreach ($query as $kk => $vv) {
            $json[$kk]['date'] = $vv['date'];
            $json[$kk]['id'] = $vv['id'];
            $json[$kk]['order_id'] = $vv['order_id'];
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

        $mg = '"MG"';
        $check = $this->mymodel->selectWithQuery("SELECT id FROM transaction WHERE customer = '$id_customer' AND json LIKE '%$mg%' AND order_status NOT IN ('UNPAID','CANCELLED','IN_CANCELLED','RETURN','REFUND') LIMIT 1");
        if ($check) {
            $dtt['brand'] = "MG";
        } else {
            $dtt['brand'] = "POME";
        }


        $this->db->update('customer', $dtt, array('id' => $id_customer));
        return $dt;
    }


    function customer_summary_v2($id_customer)
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
        SUM(transaction.return) as returnn FROM transaction WHERE customer = '$id_customer' AND type_sub = 'POS'");

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

        $query = $this->mymodel->selectWithQuery("SELECT id,date FROM transaction WHERE customer = '$id_customer' AND type_sub = 'POS' AND order_status NOT IN ('CANCELLED','IN_CANCELLED','RETURN','REFUND') ORDER BY date ASC LIMIT 1");

        $dtt['first_order'] = strval($query[0]['date']);

        $dt = array();
        $dt['cb_cl'] = 'CL';
        $id = $query[0]['id'];

        $this->db->update('transaction', $dt, array('customer' => $id_customer));
        $dt = array();
        $dt['cb_cl'] = 'CB';
        $id = $query[0]['id'];

        $this->db->update('transaction', $dt, array('id' => $id));


        $query = $this->mymodel->selectWithQuery("SELECT date FROM transaction WHERE customer = '$id_customer' AND type_sub = 'POS' AND order_status NOT IN ('CANCELLED','IN_CANCELLED','RETURN','REFUND') ORDER BY date DESC LIMIT 1");

        $dtt['last_order'] = strval($query[0]['date']);


        $json = array();
        $price_total_hpp = 0;
        $query = $this->mymodel->selectWithQuery("SELECT id,date,json,pesanan,is_manual,order_id FROM transaction WHERE customer = '$id_customer' AND type_sub = 'POS' AND order_status NOT IN ('CANCELLED','IN_CANCELLED','RETURN','REFUND') ORDER BY date ASC");

        foreach ($query as $kk => $vv) {
            $json[$kk]['date'] = $vv['date'];
            $json[$kk]['id'] = $vv['id'];
            $json[$kk]['order_id'] = $vv['order_id'];
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


        $this->db->update('customer', $dtt, array('id' => $id_customer));
        return $dt;
    }

    function cronjob_update_customer()
    {
        $success_count = 0;
        $failed_count = 0;

        // Get customers that need updating (those with transactions but outdated summary data)
        // Limit to 50 customers per run to avoid timeout
        $customers = $this->mymodel->selectWithQuery("
            SELECT DISTINCT c.id, c.full_name, c.updated_at
            FROM customer c
            INNER JOIN transaction t ON t.customer = c.id
            WHERE c.updated_at < DATE_SUB(NOW(), INTERVAL 1 DAY)
            OR c.pesanan IS NULL
            OR c.pesanan = ''
            ORDER BY c.updated_at ASC
            LIMIT 50
        ");

        foreach ($customers as $customer) {
            try {
                $id_customer = $customer['id'];

                // Call the customer_summary_v2 function to update customer data
                $this->customer_summary_v2($id_customer);

                $success_count++;
            } catch (Exception $e) {
                $failed_count++;
                // Log error if needed
                log_message('error', 'Failed to update customer ' . $customer['id'] . ': ' . $e->getMessage());
            }
        }

        // Return the formatted response
        $response = "[CRON] Customer Scrape Update - Success: $success_count | Failed: $failed_count";

        header('Content-Type: text/plain; charset=utf-8');
        echo $response;
        die;
    }
}
