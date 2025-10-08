<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;


defined('BASEPATH') or exit('No direct script access allowed');
class Codeboost extends CI_Controller
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

        $data['title'] = 'Codeboost - ' . $this->template->title();

        $query = $this->mymodel->selectWithQuery("SELECT * FROM marketplace ORDER BY name ASC");
        $data['marketplace'] = $query;

        $query = $this->mymodel->selectWithQuery("SELECT * FROM brand WHERE status = 'Aktif' ORDER BY name ASC");
        $data['brands'] = $query;

        $data['content'] = $this->load->view("codeboost/all", $data, true);
        $this->load->view("TemplateDashboard", $data);
    }

    public function get_data()
    {
        header('Content-Type: application/json');
        
        $start_date = $this->input->get('start_date') ?: DATE("Y-m-01");
        $until_date = $this->input->get('until_date') ?: DATE("Y-m-d");
        $product = $this->input->get('product_name') ?: '';
        
        $start = $this->input->get('start') ?: 0;
        $length = $this->input->get('length') ?: 10;
        $search = $this->input->get('search')['value'] ?: '';
        $order_column = $this->input->get('order')[0]['column'] ?: 0;
        $order_dir = $this->input->get('order')[0]['dir'] ?: 'desc';
        
        $columns = array(
            0 => 'id',
            1 => 'nama_creator',
            2 => 'product_text',
            3 => 'posting_at',
            4 => 'views',
            5 => 'link_vt',
        );
        
        $order_by = $columns[$order_column] . ' ' . $order_dir;
        
        $qry = "WHERE kode_ads != ''";
        
        if ($start_date && $until_date) {
            $qry .= " AND DATE(posting_at) >= '$start_date' AND DATE(posting_at) <= '$until_date'";
        }
        
        if ($product) {
            $productArray = array_map('trim', explode(',', $product));
            $likeConditions = array();
            
            foreach ($productArray as $name) {
                $name = $this->db->escape_like_str($name);
                $likeConditions[] = "product_text LIKE '%$name%'";
            }
            
            $qry .= " AND (" . implode(' OR ', $likeConditions) . ")";
        }
        
        if ($search) {
            $search = $this->db->escape_like_str($search);
            $qry .= " AND (nama_creator LIKE '%$search%' OR product_text LIKE '%$search%' OR link_upload LIKE '%$search%' OR kode_ads LIKE '%$search%')";
        }
        
        $total_query = $this->db->query("SELECT COUNT(id) as total FROM endorse $qry");
        $total_data = $total_query->row()->total;
        
        $data_query = $this->db->query("SELECT * FROM endorse $qry ORDER BY $order_by LIMIT $start, $length");
        $data = $data_query->result_array();
        
        $output = array(
            "draw" => intval($this->input->get('draw')),
            "recordsTotal" => $total_data,
            "recordsFiltered" => $total_data,
            "data" => array()
        );
        
        foreach ($data as $row) {
            $platform = strtolower(trim($row['platform']));
            $platform_icon = base_url().'/assets/img/icon/icon-no.png';

            switch ($platform) {
                case 'tiktok':
                    $platform_icon = base_url().'/assets/img/icon/icon-tiktok.png';
                    break;
                case 'instagram':
                    $platform_icon = base_url().'/assets/img/icon/icon-ig.png';
                    break;
                case 'youtube':
                    $platform_icon = base_url().'/assets/img/icon/icon-youtube.png';
                    break;
                case 'facebook':
                    $platform_icon = base_url().'/assets/img/icon/icon-fb.png';
                    break;
                case 'twitter':
                    $platform_icon = base_url().'/assets/img/icon/icon-twitter.png';
                    break;
            }

            $platform_button = '<button type="button" class="copy-btn me-3" data-clipboard-text="'.$row['kode_ads'].'" style="all: unset; cursor: pointer;">'.
                        '<i class="bi bi-copy"></i>'.
                    '</button>'.'<a href="'.$row['link_upload'].'" target="_blank">'.
                            '<img src="'.$platform_icon.'" alt="'.ucfirst($platform).'" style="height:30px; margin-left:10px; margin-right:5px; border-radius:50px;">'.'</a>';
            
            $kode_ads_short = substr($row['kode_ads'], 0, 10) . '...';
            
            $output['data'][] = array(
                '',
                $row['nama_creator'],
                !empty($row['product_text']) ? $row['product_text'] : '-',
                date('d M Y', strtotime($row['posting_at'])),
                number_format($row['views'], 0, ',', '.'),
                $platform_button, 


            );
        }
        
        echo json_encode($output);
    }

    public function export()
    {
        $start_date = $this->input->get('start_date') ?: DATE("Y-m-01");
        $until_date = $this->input->get('until_date') ?: DATE("Y-m-d");
        $product    = $this->input->get('product_name') ?: '';
        $search     = $this->input->get('search') ?: '';

        $qry = "WHERE kode_ads != ''";

        if ($start_date && $until_date) {
            $qry .= " AND DATE(posting_at) >= " . $this->db->escape($start_date) .
                    " AND DATE(posting_at) <= " . $this->db->escape($until_date);
        }

        if ($product) {
            $productArray = array_map('trim', explode(',', $product));
            $likeConditions = array();
            foreach ($productArray as $name) {
                $name = $this->db->escape_like_str($name);
                $likeConditions[] = "product_text LIKE '%{$name}%'";
            }
            if (!empty($likeConditions)) {
                $qry .= " AND (" . implode(' OR ', $likeConditions) . ")";
            }
        }

        if ($search) {
            $s = $this->db->escape_like_str($search);
            $qry .= " AND (nama_creator LIKE '%{$s}%' OR product_text LIKE '%{$s}%' OR link_upload LIKE '%{$s}%' OR kode_ads LIKE '%{$s}%')";
        }

        $sql = "SELECT id, nama_creator, product_text, posting_at, views, link_upload, platform, kode_ads
                FROM endorse
                {$qry}
                ORDER BY posting_at DESC, id DESC";
        $rows = $this->db->query($sql)->result_array();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('CodeBoost');

        $addr = function(int $col, int $row): string {
            return Coordinate::stringFromColumnIndex($col) . $row;
        };

        $headers = ['#', 'NAMA KOL', 'PRODUK', 'TANGGAL POSTING', 'VIEWS', 'LINK VT', 'PLATFORM', 'KODE ADS'];
        $col = 1;
        foreach ($headers as $h) {
            $sheet->setCellValue($addr($col, 1), $h);
            $col++;
        }

        $rowNum = 2;
        $no = 1;
        foreach ($rows as $r) {
            $sheet->setCellValue($addr(1, $rowNum), (string)$no);
            $sheet->setCellValue($addr(2, $rowNum), (string)$r['nama_creator']);
            $sheet->setCellValue($addr(3, $rowNum), $r['product_text'] !== '' ? (string)$r['product_text'] : '-');
            $sheet->setCellValue($addr(4, $rowNum), (string)$r['posting_at']);   // apa adanya
            $sheet->setCellValue($addr(5, $rowNum), (string)$r['views']);        // apa adanya
            $sheet->setCellValue($addr(6, $rowNum), (string)$r['link_upload']);  // apa adanya
            $sheet->setCellValue($addr(7, $rowNum), ucfirst(strtolower((string)$r['platform'])));
            $sheet->setCellValue($addr(8, $rowNum), (string)$r['kode_ads']);     // kolom baru KODE ADS

            $rowNum++;
            $no++;
        }

        $lastRow = max(1, $sheet->getHighestRow());
        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        $tableRange = "A1:{$lastCol}{$lastRow}";

        $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastCol}1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A1:{$lastCol}1")->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E6F0FF'); 

        $sheet->getStyle($tableRange)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->freezePane('A2');

        $sheet->setAutoFilter($tableRange);

        foreach (range('A', $lastCol) as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $filename = 'codeboost_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Cache-Control: max-age=0');

        if (ob_get_length()) { ob_end_clean(); }

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }



}