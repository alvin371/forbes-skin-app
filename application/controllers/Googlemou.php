<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require 'vendor/autoload.php';
require_once APPPATH.'core/BaseController.php';

use Google\Client as Google_Client;
use Google\Service\Drive as Google_Service_Drive;
use Google\Service\Docs as Google_Service_Docs;
use Google\Service\Oauth2 as Google_Service_Oauth2;

class Googlemou extends BaseController
{
    private $TEMPLATE_FILE_ID = '1rt0VY8YUdf2bfdsywdzeO3L-NZ_rOCuj-5HwOOof7e8';

    /** @var Google_Client */
    private $client;

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
        $this->load->library('template');

        // Load environment helper
        $this->load->helper('env');

        $this->client = new Google_Client();

        // Use environment variables for Google OAuth config
        $this->client->setClientId(env('GOOGLE_CLIENT_ID'));
        $this->client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));

        $this->set_public_methods([
            'oauth2callback',
            'action_generate_mou_pdf',
            'action_send_mou_email',
            'logout_google',
        ]);

        $this->client->setScopes([
            Google_Service_Drive::DRIVE,
            Google_Service_Docs::DOCUMENTS,
            Google_Service_Oauth2::USERINFO_EMAIL,
            Google_Service_Calendar::CALENDAR
        ]);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent select_account');
        $this->client->setRedirectUri(base_url('googlemou/oauth2callback'));
    }

    public function index() {
        $token = $this->_get_valid_token_or_redirect();
        $this->load->view('redirect/success');
    }

    /** Callback OAuth dari Google */
    public function oauth2callback() {
        $code = $this->input->get('code');
        if (!$code) { echo "Gagal login (code tidak ada)."; return; }

        $token = $this->client->fetchAccessTokenWithAuthCode($code);
        if (isset($token['error'])) {
            $desc = $token['error_description'] ?? $token['error'];
            echo "OAuth error: " . htmlspecialchars($desc);
            return;
        }

        // Simpan token (ARRAY) → session sebagai JSON
        $this->client->setAccessToken($token);
        $this->session->set_userdata('access_token', json_encode($this->client->getAccessToken()));

        redirect('googlemou');
    }

    /** Endpoint yang dipanggil JS-mu untuk generate MOU via GDocs */
    // public function action_generate_mou_pdf()
    // {
    //     // --- Ambil & validasi token dari session ---
    //     $raw   = $this->session->userdata('access_token');
    //     if (!$raw) {
    //         return $this->_json(['success'=>false,'status'=>'redirect','redirect'=>base_url('googlemou')]);
    //     }

    //     $token = is_string($raw) ? json_decode($raw, true) : $raw;
    //     if (!$token || empty($token['access_token'])) {
    //         return $this->_json(['success'=>false,'status'=>'redirect','redirect'=>base_url('googlemou')]);
    //     }

    //     // set token (ARRAY) ke client
    //     $this->client->setAccessToken($token);

    //     // refresh jika perlu
    //     if ($this->client->isAccessTokenExpired()) {
    //         $refresh = $this->client->getRefreshToken();
    //         if ($refresh) {
    //             $new = $this->client->fetchAccessTokenWithRefreshToken($refresh);
    //             if (!isset($new['refresh_token'])) $new['refresh_token'] = $refresh;
    //             $this->client->setAccessToken($new);
    //             $this->session->set_userdata('access_token', json_encode($this->client->getAccessToken()));
    //             $token = $this->client->getAccessToken(); // sinkronkan variabel
    //         } else {
    //             return $this->_json(['success'=>false,'status'=>'redirect','redirect'=>base_url('googlemou')]);
    //         }
    //     }


    //     // 1) Ambil input
    //     $id_campaign   = (int)$this->input->post('id_campaign');
    //     $nama_creator  = $this->input->post('nama_creator', true);
    //     $ids_raw       = $this->input->post('mou_item_ids', true);
    //     $override_raw  = (int)($this->input->post('total_cost_override_raw') ?? 0);
    //     $sow_text_in   = trim((string)$this->input->post('sow'));
    //     $alur_text_in  = trim((string)$this->input->post('alur_kerjasama'));
    //     $pay_awal_in   = trim((string)$this->input->post('pembayaran_awal') ?: 'DP');
    //     $persen_dp_in  = (int)($this->input->post('persentase_pembayaran_awal') ?? 50);

    //     if (!$nama_creator || !$id_campaign || !$ids_raw) {
    //         return $this->_json(['success'=>false,'message'=>'Param tidak lengkap']);
    //     }

    //     // 2) Query DB
    //     $ids = array_values(array_filter(array_map('intval', explode(',', $ids_raw))));
    //     if (!$ids) return $this->_json(['success'=>false,'message'=>'Item kosong']);

    //     $rows = $this->db->where_in('id', $ids)->get('endorse')->result_array();
    //     if (!$rows) return $this->_json(['success'=>false,'message'=>'Data item tidak ditemukan']);

    //     $auto_total = array_sum(array_map(fn($r)=>(int)($r['total_cost']??0), $rows));
    //     $total      = $override_raw ?: $auto_total;

    //     $inf      = $this->db->get_where('influencer', ['username'=>$nama_creator])->row_array();
    //     $campaign = $this->db->get_where('endorse_campaign', ['id'=>$id_campaign])->row_array();
    //     if (!$inf) return $this->_json(['success'=>false,'message'=>'Data influencer tidak ditemukan']);

    //     // 3) Build data pengganti template
    //     $picName = 'System';
    //     foreach ($rows as $r) {
    //         if (!empty($r['pic']))      { $picName = $r['pic']; break; }
    //         if (!empty($r['pic_name'])) { $picName = $r['pic_name']; break; }
    //     }

    //     if ($sow_text_in === '') {
    //         $tmp=[]; foreach($rows as $i=>$r){
    //             $tmp[]='- '.($r['sow_description'] ?? $r['notes'] ?? $r['content_type'] ?? ('Item '.($i+1)));
    //         } $sow_text_in = implode("\n", $tmp);
    //     }
    //     if ($alur_text_in === '') {
    //         $tmp=[]; foreach($rows as $i=>$r){
    //             $tmp[]=($i+1).') '.($r['workflow_description'] ?? $r['deadline'] ?? ('Tahap '.($i+1)));
    //         } $alur_text_in = implode("\n", $tmp);
    //     }

    //     $pemb_awal  = in_array(strtoupper($pay_awal_in),['DP','FP']) ? strtoupper($pay_awal_in) : 'DP';
    //     $persen_dp  = ($pemb_awal==='DP') ? ($persen_dp_in ?: ($campaign['dp_percentage'] ?? 50)) : 100;
    //     $nominal_dp = (int)(($total * $persen_dp) / 100);
    //     $tglIndo    = $this->_format_tanggal_id(date('Y-m-d'));

    //     $repl = [
    //         'brand'                       => $campaign['brand_name'] ?? 'BHSKIN',
    //         'pic'                         => $picName,
    //         'full_name'                   => $inf['full_name'] ?? $inf['name'] ?? $nama_creator,
    //         'alamat'                      => $inf['address'] ?? '-',
    //         'phone'                       => $inf['phone'] ?? '-',
    //         'username'                    => '@'.$nama_creator,
    //         'sow'                         => $sow_text_in,
    //         'alur_kerjasama'              => $alur_text_in,
    //         'total_cost'                  => number_format($total,0,',','.'),
    //         'total_cost_bilangan'         => $this->_terbilang($total).' Rupiah',
    //         'pembayaran_awal'             => $pemb_awal,
    //         'persentase_pembayaran_awal'  => $persen_dp,
    //         'nominal_dp'                  => number_format($nominal_dp,0,',','.'),
    //         'bilangan_pembayaran_awal'    => $this->_terbilang($nominal_dp).' Rupiah',
    //         'bank'                        => $inf['bank_name'] ?? '-',
    //         'no_rekening'                 => $inf['bank_account'] ?? '-',
    //         'pemilik_rekening'            => $inf['bank_account_name'] ?? ($inf['full_name'] ?? '-'),
    //         'tanggal'                     => $tglIndo,
    //     ];

    //     try {
    //         // 4) Service client (TANPA mengubah/set token dari session lagi!)
    //         $drive = new Google_Service_Drive($this->client);
    //         $docs  = new Google_Service_Docs($this->client);

    //         // (opsional) log akun Google yang dipakai
    //         $oauth2 = new Google_Service_Oauth2($this->client);
    //         $me = $oauth2->userinfo->get();
    //         log_message('error', '[GDrive] OAuth user email='.$me->email);

    //         // 5) Preflight: cek akses ke template
    //         try {
    //             // cek akses
    //             $probe = $drive->files->get(
    //                 $this->TEMPLATE_FILE_ID,
    //                 ['fields'=>'id,name,owners,driveId,shortcutDetails', 'supportsAllDrives'=>true]
    //             );
    //             log_message('error', '[GDrive] Probe OK: '.json_encode($probe));

    //             // copy
    //             $newTitle = 'MOU - '.$nama_creator.' - '.$tglIndo;
    //             $copyReq  = new Google_Service_Drive\DriveFile(['name'=>$newTitle]);
    //             $copied   = $drive->files->copy(
    //                 $this->TEMPLATE_FILE_ID,
    //                 $copyReq,
    //                 ['fields'=>'id,name', 'supportsAllDrives'=>true]
    //             );
    //             $docId    = $copied->id;

    //         } catch (Exception $ex) {
    //             log_message('error', '[GDrive] files.get error: '.$ex->getMessage());
    //             return $this->_json(['success'=>false,'message'=>'Template tidak bisa diakses: '.$ex->getMessage()]);
    //         }


    //         // 7) Replace placeholder {{key}}
    //         $reqs = [];
    //         foreach($repl as $k=>$v){
    //             $reqs[] = new Google_Service_Docs\Request([
    //                 'replaceAllText'=>[
    //                     'containsText'=>['text'=>'{{'.$k.'}}','matchCase'=>true],
    //                     'replaceText'=>(string)$v
    //                 ]
    //             ]);
    //         }
    //         $docs->documents->batchUpdate($docId, new Google_Service_Docs\BatchUpdateDocumentRequest(['requests'=>$reqs]));

    //         // 8) Export PDF
    //         $resp    = $drive->files->export($docId, 'application/pdf', ['alt'=>'media']);
    //         $pdfData = $resp->getBody()->getContents();

    //         // 9) Simpan ke server lokal
    //         $rawFileName = 'MOU '.$nama_creator.' - '.$repl['pic'].' - '.$tglIndo.'.pdf';
    //         $fileName    = $this->_sanitize_filename($rawFileName);
    //         $dir         = FCPATH.'uploads/mou/';
    //         if(!is_dir($dir)) mkdir($dir,0777,true);
    //         $finalPath   = $dir.$fileName;
    //         file_put_contents($finalPath, $pdfData);
    //         $pdfUrl      = base_url('uploads/mou/'.$fileName);

    //         // 10) Logging DB
    //         $this->db->trans_start();
    //         $now   = date('Y-m-d H:i:s');
    //         $user  = $_SESSION['user'] ?? null;
    //         $uid   = $user['id']   ?? null;
    //         $ucode = $user['code'] ?? null;

    //         foreach($ids as $endorseId){
    //             $this->db->insert('mou_logs',[
    //                 'id_endorse'  => $endorseId,
    //                 'id_campaign' => $id_campaign,
    //                 'nama_creator'=> $nama_creator,
    //                 'pic'         => $repl['pic'],
    //                 'filename'    => $fileName,
    //                 'pdf_url'     => $pdfUrl,
    //                 'created_at'  => $now,
    //                 'generated_by'=> $ucode,
    //                 'extra_json'  => json_encode(['gdoc_id'=>$docId], JSON_UNESCAPED_UNICODE)
    //             ]);

    //             $rowEndorse = $this->db->get_where('endorse',['id'=>$endorseId])->row_array();
    //             $logs = [];
    //             if (!empty($rowEndorse['logs'])) {
    //                 $dec = json_decode($rowEndorse['logs'], true);
    //                 if (is_array($dec)) $logs = $dec;
    //             }
    //             $logs[] = [
    //                 'status'       => 'MOU Generated (GDocs)',
    //                 'created_by'   => (string)$uid,
    //                 'created_text' => $ucode,
    //                 'created_at'   => $now,
    //             ];
    //             $this->db->update('endorse',[
    //                 'is_generated_mou'   => 1,
    //                 'link_generated_mou' => $pdfUrl,
    //                 'logs'               => json_encode($logs, JSON_UNESCAPED_UNICODE),
    //                 'updated_at'         => $now,
    //                 'updated_by'         => $uid,
    //             ], ['id'=>$endorseId]);
    //         }
    //         $this->db->trans_complete();
    //         if ($this->db->trans_status() === FALSE) {
    //             return $this->_json(['success'=>false,'message'=>'Gagal menyimpan log ke database']);
    //         }

    //         return $this->_json(['success'=>true,'pdf_url'=>$pdfUrl,'filename'=>$fileName]);

    //     } catch (\Throwable $e) {
    //         return $this->_json(['success'=>false,'message'=>$e->getMessage()]);
    //     }
    // }

    public function action_generate_mou_pdf()
    {
        // --- Ambil & validasi token dari session ---
        $raw = $this->session->userdata('access_token');
        if (!$raw) {
            return $this->_json(['success'=>false,'status'=>'redirect','redirect'=>base_url('googlemou')]);
        }

        $token = is_string($raw) ? json_decode($raw, true) : $raw;
        if (!$token || empty($token['access_token'])) {
            return $this->_json(['success'=>false,'status'=>'redirect','redirect'=>base_url('googlemou')]);
        }

        $this->client->setAccessToken($token);
        if ($this->client->isAccessTokenExpired()) {
            $refresh = $this->client->getRefreshToken();
            if ($refresh) {
                $new = $this->client->fetchAccessTokenWithRefreshToken($refresh);
                if (!isset($new['refresh_token'])) $new['refresh_token'] = $refresh;
                $this->client->setAccessToken($new);
                $this->session->set_userdata('access_token', json_encode($this->client->getAccessToken()));
            } else {
                return $this->_json(['success'=>false,'status'=>'redirect','redirect'=>base_url('googlemou')]);
            }
        }

        // 1) Ambil input
        $id_campaign   = (int)$this->input->post('id_campaign');
        $nama_creator  = $this->input->post('nama_creator', true);
        $ids_raw       = $this->input->post('mou_item_ids', true);
        $override_raw  = (int)($this->input->post('total_cost_override_raw') ?? 0);
        $sow_text_in   = trim((string)$this->input->post('sow'));
        $produk_kerjasama = trim((string)$this->input->post('produk_kerjasama'));
        $deadline_postingan = trim((string)$this->input->post('deadline_postingan'));
        $pay_awal_in   = trim((string)$this->input->post('pembayaran_awal') ?: 'DP');
        $persen_dp_in  = (int)($this->input->post('persentase_pembayaran_awal') ?? 50);

        if (!$nama_creator || !$id_campaign || !$ids_raw) {
            return $this->_json(['success'=>false,'message'=>'Param tidak lengkap']);
        }

        // 2) Query DB
        $ids = array_values(array_filter(array_map('intval', explode(',', $ids_raw))));
        if (!$ids) return $this->_json(['success'=>false,'message'=>'Item kosong']);

        $rows = $this->db->where_in('id', $ids)->get('endorse')->result_array();
        if (!$rows) return $this->_json(['success'=>false,'message'=>'Data item tidak ditemukan']);

        $auto_total = array_sum(array_map(fn($r)=>(int)($r['total_cost']??0), $rows));
        $total      = $override_raw ?: $auto_total;

        $inf      = $this->db->get_where('influencer', ['username'=>$nama_creator])->row_array();
        $campaign = $this->db->get_where('endorse_campaign', ['id'=>$id_campaign])->row_array();
        if (!$inf) return $this->_json(['success'=>false,'message'=>'Data influencer tidak ditemukan']);

        // 3) Build data pengganti template
        $picName = 'System';
        foreach ($rows as $r) {
            if (!empty($r['pic']))      { $picName = $r['pic']; break; }
            if (!empty($r['pic_name'])) { $picName = $r['pic_name']; break; }
        }

        $brand_raw = $campaign['brand'];
        $brand = '';
        if ($brand_raw == 'MG') {
            $brand = 'Miscella-G';
        } else if ($brand_raw == 'POME') {
            $brand = 'POME';
        } else {
            $brand = 'BHSKIN';
        }

        // === BUILD SOW ===
        $sow_items = [];

        // Ambil data dari SOW builder (JSON)
        $sow_json = $this->input->post('sow_json');
        $is_ads   = $this->input->post('is_ads') ? true : false;

        if ($sow_json) {
            $sow_data = json_decode($sow_json, true);
            if (is_array($sow_data)) {
                foreach ($sow_data as $i => $row) {
                    $qty    = (int)($row['total'] ?? 0);
                    if ($qty <= 0) continue;

                    $produk = $row['produk'] ?? '-';
                    $kerkun = ($row['jenis'] === 'kerkun')
                        ? 'dengan Keranjang Kuning'
                        : 'tanpa Keranjang Kuning';

                    // huruf a, b, c ...
                    $huruf = chr(97 + count($sow_items));

                    $sow_items[] = $huruf.".   {$qty} (".$this->_terbilang($qty).") Content Review Video Tiktok produk {$produk} {$kerkun}";
                }
            }
        }

        // Tambahkan jika Ads dicentang
        if ($is_ads) {
            $huruf = chr(97 + count($sow_items));
            $sow_items[] = $huruf.".   Scanbarcode untuk ads";
        }

        // Jika user tidak isi manual, baru fallback ke data endorse
        if (empty($sow_items) && $sow_text_in === '') {
            foreach ($rows as $i=>$r){
                $huruf = chr(97 + count($sow_items));
                $sow_items[] = $huruf.". ".($r['sow_description'] ?? $r['notes'] ?? $r['content_type'] ?? ('Item '.($i+1)));
            }
        }

        $sow_text_in = implode("\n", $sow_items);

        $pemb_awal  = in_array(strtoupper($pay_awal_in),['DP','FP']) ? strtoupper($pay_awal_in) : 'DP';
        $persen_dp  = ($pemb_awal==='DP') ? ($persen_dp_in ?: ($campaign['dp_percentage'] ?? 50)) : 100;
        $nominal_dp = (int)(($total * $persen_dp) / 100);
        $tglIndo    = $this->_format_tanggal_id(date('Y-m-d'));

        $repl = [
            'brand'                       => $brand,
            'pic'                         => $picName,
            'full_name'                   => $inf['full_name'],
            'alamat'                      => $inf['alamat'] ?? '-',
            'phone'                       => $inf['phone'] ?? '-',
            'username'                    => $nama_creator,
            'sow'                         => $sow_text_in,
            'produk_kerjasama'            => $produk_kerjasama ?: '-',
            'deadline_postingan'          => $deadline_postingan ?: '-',
            'total_cost'                  => number_format($total,0,',','.'),
            'total_cost_bilangan'         => $this->_terbilang($total).' Rupiah',
            'pembayaran_awal'             => $pemb_awal,
            'persentase_pembayaran_awal'  => $persen_dp,
            'nominal_dp'                  => number_format($nominal_dp,0,',','.'),
            'bilangan_pembayaran_awal'    => $this->_terbilang($nominal_dp).' Rupiah',
            'bank'                        => $inf['bank'] ?? '-',
            'no_rekening'                 => $inf['no_rekening'] ?? '-',
            'pemilik_rekening'            => $inf['pemilik_rekening'] ?? '-',
            'tanggal'                     => $tglIndo,
        ];

        try {
            $drive = new Google_Service_Drive($this->client);
            $docs  = new Google_Service_Docs($this->client);

            // --- Preflight: cek template & handle shortcut/docx ---
            $probe = $drive->files->get(
                $this->TEMPLATE_FILE_ID,
                ['fields'=>'id,name,mimeType,shortcutDetails', 'supportsAllDrives'=>true]
            );

            // Resolve shortcut jika perlu
            if ($probe->getShortcutDetails() && $probe->getShortcutDetails()->getTargetId()) {
                $this->TEMPLATE_FILE_ID = $probe->getShortcutDetails()->getTargetId();
                $probe = $drive->files->get(
                    $this->TEMPLATE_FILE_ID,
                    ['fields'=>'id,name,mimeType', 'supportsAllDrives'=>true]
                );
            }

            $tplMime = $probe->getMimeType();

            $newTitle = 'MOU '.$nama_creator.' - '.$picName.' - '.$tglIndo;
            // Jika template adalah DOCX → copy sambil convert ke GDocs
            $copyReqArr = ['name'=>$newTitle];
            if (in_array($tplMime, [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/msword'
            ])) {
                $copyReqArr['mimeType'] = 'application/vnd.google-apps.document';
            }
            $copyReq  = new Google_Service_Drive\DriveFile($copyReqArr);

            $copied   = $drive->files->copy(
                $this->TEMPLATE_FILE_ID,
                $copyReq,
                ['fields'=>'id,name,mimeType', 'supportsAllDrives'=>true]
            );
            $docId = $copied->id;

            // Pastikan yang kita pegang GDocs
            if ($copied->getMimeType() !== 'application/vnd.google-apps.document') {
                return $this->_json([
                    'success'=>false,
                    'message'=>'Template bukan Google Docs dan gagal dikonversi.'
                ]);
            }

            // --- Replace placeholder {{key}} ---
            $reqs = [];
            foreach($repl as $k=>$v){
                $reqs[] = new Google_Service_Docs\Request([
                    'replaceAllText'=>[
                        'containsText'=>['text'=>'{{'.$k.'}}','matchCase'=>true],
                        'replaceText'=>(string)$v
                    ]
                ]);
            }
            $docs->documents->batchUpdate(
                $docId,
                new Google_Service_Docs\BatchUpdateDocumentRequest(['requests'=>$reqs])
            );

            // --- Ambil webViewLink untuk view, dan siapkan URL export untuk unduh ---
            $meta   = $drive->files->get($docId, ['fields'=>'id,name,webViewLink', 'supportsAllDrives'=>true]);
            $docUrl = $meta->getWebViewLink();

            // endpoint export milik kita sendiri yang mengalirkan PDF (no save)
            $downloadUrl = base_url('googlemou/export_pdf/'.$docId);

            // --- Logging DB (tanpa PDF) ---
            $this->db->trans_start();
            $now   = date('Y-m-d H:i:s');
            $user  = $_SESSION['user'] ?? null;
            $uid   = $user['id']   ?? null;
            $ucode = $user['code'] ?? null;

            foreach($ids as $endorseId){
                $insertData = [
                    'id_endorse'  => $endorseId,
                    'id_campaign' => $id_campaign,
                    'nama_creator'=> $nama_creator,
                    'pic'         => $repl['pic'],
                    'filename'    => $meta->getName().'.gdoc',
                    // pakai kolom yang sama untuk simpan link dokumen (bukan PDF)
                    'pdf_url'     => $docUrl,
                    'created_at'  => $now,
                    'generated_by'=> $ucode,
                ];
                if ($this->db->field_exists('extra_json', 'mou_logs')) {
                    $insertData['extra_json'] = json_encode([
                        'gdoc_id' => $docId,
                        'produk_kerjasama' => $produk_kerjasama,
                        'deadline_postingan' => $deadline_postingan
                    ], JSON_UNESCAPED_UNICODE);
                }
                $this->db->insert('mou_logs', $insertData);

                $rowEndorse = $this->db->get_where('endorse',['id'=>$endorseId])->row_array();
                $logs = [];
                if (!empty($rowEndorse['logs'])) {
                    $dec = json_decode($rowEndorse['logs'], true);
                    if (is_array($dec)) $logs = $dec;
                }
                $logs[] = [
                    'status_mou'       => 'MOU Generated',
                    'created_by'   => (string)$uid,
                    'created_text' => $ucode,
                    'created_at'   => $now,
                ];
                $this->db->update('endorse',[
                    'is_generated_mou'   => 1,
                    'link_generated_mou' => $docUrl, // simpan link dokumen
                    'logs'               => json_encode($logs, JSON_UNESCAPED_UNICODE),
                    'updated_at'         => $now,
                    'updated_by'         => $uid,
                ], ['id'=>$endorseId]);
            }
            $this->db->trans_complete();
            if ($this->db->trans_status() === FALSE) {
                return $this->_json(['success'=>false,'message'=>'Gagal menyimpan log ke database']);
            }

            // --- Response: kembalikan info dokumen ---
            return $this->_json([
                'success'      => true,
                'doc_id'       => $docId,
                'doc_url'      => $docUrl,
                'download_url' => $downloadUrl,
                // backward-compat:
                'pdf_url'      => $downloadUrl,
                'filename'     => $meta->getName().'.gdoc',
                'message'      => 'MOU berhasil dibuat.'
            ]);

        } catch (\Throwable $e) {
            return $this->_json(['success'=>false,'message'=>$e->getMessage()]);
        }
    }

    // Stream PDF hasil export langsung ke browser (tanpa menyimpan file)
    public function export_pdf($docId)
    {
        // cek OAuth
        $raw = $this->session->userdata('access_token');
        if (!$raw) { show_error('Unauthorized', 401); return; }
        $token = is_string($raw) ? json_decode($raw,true) : $raw;
        if (!$token || empty($token['access_token'])) { show_error('Unauthorized', 401); return; }
        $this->client->setAccessToken($token);
        if ($this->client->isAccessTokenExpired()) { show_error('Token expired', 401); return; }

        $drive = new Google_Service_Drive($this->client);

        try {
            // ambil nama file untuk nama unduhan
            $meta    = $drive->files->get($docId, ['fields'=>'id,name', 'supportsAllDrives'=>true]);
            $name    = $meta->getName();
            if (strtolower(substr($name, -4)) !== '.pdf') {
                // pastikan nama rapi untuk unduhan
                $name .= '.pdf';
            }

            // export bytes
            $resp    = $drive->files->export($docId, 'application/pdf', ['alt'=>'media']);
            $pdfData = $resp->getBody()->getContents();

            // kirim ke browser
            $this->output
                ->set_content_type('application/pdf')
                ->set_header('Content-Disposition: attachment; filename="'.$name.'"')
                ->set_header('Cache-Control: no-store, no-cache, must-revalidate')
                ->set_output($pdfData);
        } catch (\Throwable $e) {
            log_message('error', '[GDrive] export_pdf error: '.$e->getMessage());
            show_error('Gagal mengekspor PDF', 500);
        }
    }

    public function action_send_mou_email()
    {
        $nama_creator = $this->input->post('nama_creator', true);
        $doc_id       = $this->input->post('doc_id', true);   // baru
        $doc_url      = $this->input->post('doc_url', true);  // baru (link GDocs untuk body)
        $pdf_url      = $this->input->post('pdf_url', true);  // legacy (boleh ada, kita coba parse doc_id)

        if (!$nama_creator) {
            return $this->_json(['success'=>false, 'message'=>'Nama creator kosong']);
        }

        // ambil email influencer
        $inf   = $this->db->get_where('influencer', ['username'=>$nama_creator])->row_array();
        $email = $inf['email'] ?? '';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->_json(['success'=>false, 'message'=>'Email influencer tidak valid']);
        }

        // jika doc_id belum ada, coba ambil dari pdf_url kita (pattern: .../googlemou/export_pdf/{docId})
        if (!$doc_id && $pdf_url && preg_match('~/export_pdf/([a-zA-Z0-9_-]+)~', $pdf_url, $m)) {
            $doc_id = $m[1];
        }
        if (!$doc_id) {
            return $this->_json(['success'=>false, 'message'=>'doc_id tidak ditemukan']);
        }

        // ---- OAuth token untuk akses Drive
        $raw = $this->session->userdata('access_token');
        $token = is_string($raw) ? json_decode($raw, true) : $raw;
        if (!$token || empty($token['access_token'])) {
            return $this->_json(['success'=>false, 'message'=>'Unauthorized (token kosong)']);
        }
        $this->client->setAccessToken($token);
        if ($this->client->isAccessTokenExpired()) {
            $refresh = $this->client->getRefreshToken();
            if ($refresh) {
                $new = $this->client->fetchAccessTokenWithRefreshToken($refresh);
                if (!isset($new['refresh_token'])) $new['refresh_token'] = $refresh;
                $this->client->setAccessToken($new);
                $this->session->set_userdata('access_token', json_encode($this->client->getAccessToken()));
            } else {
                return $this->_json(['success'=>false, 'message'=>'Token expired & tidak bisa refresh']);
            }
        }

        try {
            $drive   = new Google_Service_Drive($this->client);
            $meta    = $drive->files->get($doc_id, ['fields'=>'id,name', 'supportsAllDrives'=>true]);
            $pdfName = preg_replace('/\.gdoc$/i', '', $meta->getName()) . '.pdf';

            $resp    = $drive->files->export($doc_id, 'application/pdf', ['alt'=>'media']);
            $pdfData = $resp->getBody()->getContents();
        } catch (\Throwable $e) {
            return $this->_json(['success'=>false, 'message'=>'Gagal mengambil PDF dari Drive: '.$e->getMessage()]);
        }

        $this->load->library('email');

        $configs = [
            [
                'label'        => '465/ssl',
                'protocol'     => 'smtp',
                'smtp_host'    => env('SMTP_HOST', 'smtp.hostinger.com'),
                'smtp_user'    => env('SMTP_USER', 'mou@bhskin.co.id'),
                'smtp_pass'    => env('SMTP_PASS', ''),
                'smtp_port'    => env('SMTP_PORT_SSL', 465),
                'smtp_crypto'  => 'ssl',
                'smtp_timeout' => 30,
                'mailtype'     => 'text',
                'charset'      => 'utf-8',
                'newline'      => "\r\n",
                'crlf'         => "\r\n",
                'wordwrap'     => true,
                'validate'     => true,
            ],
            [
                'label'        => '587/tls',
                'protocol'     => 'smtp',
                'smtp_host'    => env('SMTP_HOST', 'smtp.hostinger.com'),
                'smtp_user'    => env('SMTP_USER', 'mou@bhskin.co.id'),
                'smtp_pass'    => env('SMTP_PASS', ''),
                'smtp_port'    => env('SMTP_PORT_TLS', 587),
                'smtp_crypto'  => 'tls',
                'smtp_timeout' => 30,
                'mailtype'     => 'text',
                'charset'      => 'utf-8',
                'newline'      => "\r\n",
                'crlf'         => "\r\n",
                'wordwrap'     => true,
                'validate'     => true,
            ],
        ];

        $last_error = null;
        foreach ($configs as $cfg) {
            $this->email->initialize($cfg);
            $this->email->set_newline("\r\n");
            $this->email->set_crlf("\r\n");

            $this->email->clear(true);
            $this->email->from(env('SMTP_USER', 'mou@bhskin.co.id'), 'BH Skin - MoU System'); 
            $this->email->to($email);
            $this->email->subject('MoU Kerja Sama - '.$inf['full_name']);

            $linkView = $doc_url ?: $pdf_url;
            $body = "Halo kak {$inf['full_name']},\n\n"
                . "Terlampir dokumen MoU kerja sama dengan BHSKIN untuk dapat ditinjau.\n"
                . "Silakan dibaca kembali, dan hubungi kami jika ada hal yang ingin ditanyakan.\n\n"
                . "Terima kasih atas kerja samanya.\n\n"
                . "Salam,\n"
                . "Tim BHSKIN";
            $this->email->message($body);


            $this->email->attach($pdfData, 'attachment', $pdfName, 'application/pdf');

            if ($this->email->send()) {
                return $this->_json(['success' => true, 'used' => $cfg['label']]);
            }

            $last_error = "Channel {$cfg['label']} gagal:\n" . $this->email->print_debugger(['headers','subject','body']);
        }

        return $this->_json([
            'success' => false,
            'message' => $last_error ?: 'Tidak diketahui (gagal tanpa log)',
        ]);

    }



    // ---------------- Helper ----------------

    private function _get_valid_token_or_redirect() {
        $raw = $this->session->userdata('access_token');
        if (!$raw) {
            redirect($this->client->createAuthUrl());
        }
        $token = is_string($raw) ? json_decode($raw, true) : $raw;
        if (!$token || empty($token['access_token'])) {
            $this->session->unset_userdata('access_token');
            redirect($this->client->createAuthUrl());
        }
        $this->client->setAccessToken($token);

        if ($this->client->isAccessTokenExpired()) {
            $refresh = $this->client->getRefreshToken();
            if ($refresh) {
                $new = $this->client->fetchAccessTokenWithRefreshToken($refresh);
                if (!isset($new['refresh_token'])) $new['refresh_token'] = $refresh;
                $this->client->setAccessToken($new);
                $this->session->set_userdata('access_token', json_encode($this->client->getAccessToken()));
            } else {
                $this->session->unset_userdata('access_token');
                redirect($this->client->createAuthUrl());
            }
        }
        return $this->client->getAccessToken();
    }

    public function test_drive() {
        $raw = $this->session->userdata('access_token');
        $token = is_string($raw) ? json_decode($raw, true) : $raw;
        if (!$token) { echo 'no token'; return; }
        $this->client->setAccessToken($token);
        if ($this->client->isAccessTokenExpired()) { echo 'expired'; return; }

        $drive = new Google_Service_Drive($this->client);
        try {
            $about = $drive->about->get(['fields' => 'user(displayName,emailAddress)']);
            echo '<pre>'; print_r($about); echo '</pre>';
        } catch (Exception $e) {
            echo $e->getMessage(); 
        }
    }


    private function _get_valid_token_or_redirect_json() {
        $raw = $this->session->userdata('access_token');
        if (!$raw) {
            $this->_json(['success'=>false,'status'=>'redirect','redirect'=>base_url('googlemou')]);
            return null;
        }
        $token = is_string($raw) ? json_decode($raw, true) : $raw;
        if (!$token || empty($token['access_token'])) {
            $this->_json(['success'=>false,'status'=>'redirect','redirect'=>base_url('googlemou')]);
            return null;
        }
        $this->client->setAccessToken($token);

        if ($this->client->isAccessTokenExpired()) {
            $refresh = $this->client->getRefreshToken();
            if ($refresh) {
                $new = $this->client->fetchAccessTokenWithRefreshToken($refresh);
                if (!isset($new['refresh_token'])) $new['refresh_token'] = $refresh;
                $this->client->setAccessToken($new);
                $this->session->set_userdata('access_token', json_encode($this->client->getAccessToken()));
            } else {
                $this->_json(['success'=>false,'status'=>'redirect','redirect'=>base_url('googlemou')]);
                return null;
            }
        }
        return $this->client->getAccessToken();
    }

    private function _format_tanggal_id($ymd)
    {
        $bulan = [
            1=>'Januari','Februari','Maret','April','Mei','Juni',
            'Juli','Agustus','September','Oktober','November','Desember'
        ];
        $ts = strtotime($ymd);
        if (!$ts) $ts = time();
        $d = (int)date('j', $ts);
        $m = (int)date('n', $ts);
        $y = (int)date('Y', $ts);
        return $d.' '.$bulan[$m].' '.$y;
    }

    private function _terbilang($angka)
    {
        $angka = abs($angka);
        $baca = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];

        if ($angka < 12) return trim($baca[$angka]);
        if ($angka < 20) return trim($this->_terbilang($angka - 10) . " Belas");
        if ($angka < 100) return trim($this->_terbilang(intval($angka / 10)) . " Puluh " . $this->_terbilang($angka % 10));
        if ($angka < 200) return trim("Seratus " . $this->_terbilang($angka - 100));
        if ($angka < 1000) return trim($this->_terbilang(intval($angka / 100)) . " Ratus " . $this->_terbilang($angka % 100));
        if ($angka < 2000) return trim("Seribu " . $this->_terbilang($angka - 1000));
        if ($angka < 1000000) return trim($this->_terbilang(intval($angka / 1000)) . " Ribu " . $this->_terbilang($angka % 1000));
        if ($angka < 1000000000) return trim($this->_terbilang(intval($angka / 1000000)) . " Juta " . $this->_terbilang($angka % 1000000));
        if ($angka < 1000000000000) return trim($this->_terbilang(intval($angka / 1000000000)) . " Miliar " . $this->_terbilang($angka % 1000000000));
        return (string)$angka;
    }

    private function _sanitize_filename($name)
    {
        $name = preg_replace('/[\/\\\\:*?"<>|]+/', '_', $name);
        $name = preg_replace('/\s+/', ' ', trim($name));
        if (strlen($name) > 200) {
            $ext = '.pdf';
            $base = substr($name, 0, 200 - strlen($ext));
            $name = $base.$ext;
        }
        return $name;
    }

    private function _json($arr){
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($arr, JSON_UNESCAPED_UNICODE));
    }

    public function logout_google() {
        $this->session->unset_userdata('access_token');
        redirect('googlemou'); 
    }
        
}
