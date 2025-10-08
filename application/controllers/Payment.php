<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class Payment extends BaseController
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
            'action' => 'edit'
        ]);
    }

    public function index()
    {
        // ====== GET & defaults ======
        $nama_creator   = $this->input->get('nama_creator') ?? [];
        $pic            = $this->input->get('pic') ?? [];
        $endorse_status = $this->input->get('endorse_status'); 
        $all_get        = $this->input->get(null, true) ?: [];
        if (empty($all_get)) $endorse_status = '';

        $start_date = $this->input->get('start_date');
        $until_date = $this->input->get('until_date');
        if (empty($start_date)) $start_date = date('Y-m-01');
        if (empty($until_date)) $until_date = date('Y-m-d');

        $start_dt = $this->db->escape($start_date . ' 00:00:00');
        $end_dt   = $this->db->escape($until_date . ' 23:59:59');

        $data['template'] = $this->template;
        $data['title']    = 'Payment Influencer - ' . $this->template->title();

        // ====== WHERE dasar ======
        $where = " WHERE e.status_endorse NOT IN ('Review', 'Hold', 'Reject', 'Problem') ";

        if (!empty($nama_creator)) {
            $escaped = array_map(fn($s) => $this->db->escape_str($s), $nama_creator);
            $where  .= " AND e.nama_creator IN ('" . implode("','", $escaped) . "')";
        }

        if (!empty($pic)) {
            $pic_conditions = [];
            foreach ($pic as $p) if ($p !== '') $pic_conditions[] = "e.pic LIKE '".$this->db->escape_str($p)."%'";
            if ($pic_conditions) $where .= " AND (".implode(" OR ", $pic_conditions).")";
        }

        // ====== Timestamp pengajuan (elemen terakhir JSON) ======
        $pengajuan_ts_sql = "
            CASE
                WHEN e.pengajuan_payment_logs IS NULL OR JSON_LENGTH(e.pengajuan_payment_logs) = 0 THEN NULL
                ELSE CAST(
                    COALESCE(
                        JSON_UNQUOTE(JSON_EXTRACT(
                            e.pengajuan_payment_logs,
                            CONCAT('$[', JSON_LENGTH(e.pengajuan_payment_logs) - 1, '].updated_at')
                        )),
                        JSON_UNQUOTE(JSON_EXTRACT(
                            e.pengajuan_payment_logs,
                            CONCAT('$[', JSON_LENGTH(e.pengajuan_payment_logs) - 1, '].created_at')
                        ))
                    ) AS DATETIME
                )
            END
        ";

        // ====== Parse filter status ======
        $onlyPengajuanSelected = false;
        $filterDPFPSelected    = false;
        $sumStatusCondSql      = " AND (pl.status_payment LIKE '%DP%' OR pl.status_payment LIKE '%FP%')"; // default SUM

        if ($endorse_status !== null) {
            $tokens       = ($endorse_status === '') ? [''] : array_map('trim', explode(',', $endorse_status));
            $hasPengajuan = in_array('',  $tokens, true);
            $hasDP        = in_array('DP', $tokens, true);
            $hasFP        = in_array('FP', $tokens, true);

            $onlyPengajuanSelected = ($hasPengajuan && !$hasDP && !$hasFP);
            $filterDPFPSelected    = ($hasDP || $hasFP) && !$onlyPengajuanSelected;

            if ($onlyPengajuanSelected) {
                $sumStatusCondSql = " AND 1=0"; // pengajuan saja → SUM nominal = 0
            } elseif ($hasDP || $hasFP) {
                $sp = [];
                if ($hasDP) $sp[] = "pl.status_payment LIKE '%DP%'";
                if ($hasFP) $sp[] = "pl.status_payment LIKE '%FP%'";
                $sumStatusCondSql = " AND (".implode(" OR ", $sp).")";
            }

            // Tambahkan filter pengajuan/logs ke WHERE utama
            $clauses = [];
            if ($hasPengajuan) {
                $clauses[] = "(
                    e.pengajuan_status_payment LIKE '%Pengajuan Payment%'
                    AND ($pengajuan_ts_sql) BETWEEN $start_dt AND $end_dt
                )";
            }
            if ($hasDP || $hasFP) {
                // Pakai flag dari aggregate in-range (pl_e/pl_b) agar "DP" / "FP" benar-benar terjadi di periode filter
                $flag = [];
                if ($hasDP) $flag[] = "(COALESCE(pl_e.has_dp, 0) = 1 OR COALESCE(pl_b.has_dp, 0) = 1)";
                if ($hasFP) $flag[] = "(COALESCE(pl_e.has_fp, 0) = 1 OR COALESCE(pl_b.has_fp, 0) = 1)";
                $clauses[] = "(".implode(" OR ", $flag).")";
            }
            if (!empty($clauses)) $where .= " AND (".implode(" OR ", $clauses).")";
        } else {
            // default: ada logs dalam range tanggal apa pun
            $where .= " AND (COALESCE(pl_e.any_log,0)=1 OR COALESCE(pl_b.any_log,0)=1) ";
        }

        // ====== SQL: Pre-aggregate IN-RANGE (untuk nominal/last date & filter baris) ======
        $pl_endorse_sql = "
            SELECT
                pl.id_endorse,
                MAX(pl.created_at) AS last_payment_created_at,
                SUM(CASE WHEN pl.status_payment LIKE '%DP%' THEN pl.nominal_dibayarkan ELSE 0 END) AS sum_dp,
                SUM(CASE WHEN pl.status_payment LIKE '%FP%' THEN pl.nominal_dibayarkan ELSE 0 END) AS sum_fp,
                MAX(pl.status_payment LIKE '%DP%') AS has_dp,
                MAX(pl.status_payment LIKE '%FP%') AS has_fp,
                1 AS any_log
            FROM payment_logs pl
            WHERE pl.id_endorse IS NOT NULL
            AND pl.id_endorse <> 0
            AND pl.created_at BETWEEN $start_dt AND $end_dt
            GROUP BY pl.id_endorse
        ";

        $pl_bundling_sql = "
            SELECT
                pl.id_campaign,
                pl.nama_influencer,
                MAX(pl.created_at) AS last_payment_created_at,
                SUM(CASE WHEN pl.status_payment LIKE '%DP%' THEN pl.nominal_dibayarkan ELSE 0 END) AS sum_dp,
                SUM(CASE WHEN pl.status_payment LIKE '%FP%' THEN pl.nominal_dibayarkan ELSE 0 END) AS sum_fp,
                MAX(pl.status_payment LIKE '%DP%') AS has_dp,
                MAX(pl.status_payment LIKE '%FP%') AS has_fp,
                1 AS any_log
            FROM payment_logs pl
            WHERE (pl.id_endorse = 0 OR pl.id_endorse IS NULL)
            AND pl.created_at BETWEEN $start_dt AND $end_dt
            GROUP BY pl.id_campaign, pl.nama_influencer
        ";

        // ====== SQL: Pre-aggregate ALL-TIME (untuk kolom STATUS agar "lifetime") ======
        $pl_endorse_all_sql = "
            SELECT
                pl.id_endorse,
                MAX(pl.status_payment LIKE '%DP%') AS has_dp_any,
                MAX(pl.status_payment LIKE '%FP%') AS has_fp_any
            FROM payment_logs pl
            WHERE pl.id_endorse IS NOT NULL
            AND pl.id_endorse <> 0
            GROUP BY pl.id_endorse
        ";

        $pl_bundling_all_sql = "
            SELECT
                pl.id_campaign,
                pl.nama_influencer,
                MAX(pl.status_payment LIKE '%DP%') AS has_dp_any,
                MAX(pl.status_payment LIKE '%FP%') AS has_fp_any
            FROM payment_logs pl
            WHERE (pl.id_endorse = 0 OR pl.id_endorse IS NULL)
            GROUP BY pl.id_campaign, pl.nama_influencer
        ";

        // ====== STATUS (pakai ALL-TIME) ======
        $status_pick_sql = "
            CASE
                WHEN e.is_payment_bundling = 0 THEN
                    TRIM(BOTH ',' FROM CONCAT(
                        CASE WHEN COALESCE(pl_e_all.has_dp_any,0)=1 THEN 'DP,' ELSE '' END,
                        CASE WHEN COALESCE(pl_e_all.has_fp_any,0)=1 THEN 'FP,' ELSE '' END
                    ))
                ELSE
                    TRIM(BOTH ',' FROM CONCAT(
                        CASE WHEN COALESCE(pl_b_all.has_dp_any,0)=1 THEN 'DP,' ELSE '' END,
                        CASE WHEN COALESCE(pl_b_all.has_fp_any,0)=1 THEN 'FP,' ELSE '' END
                    ))
            END
        ";

        // ====== NOMINAL (tetap IN-RANGE & tunduk pada pilihan DP/FP) ======
        $sum_pick_sql = "
            CASE
                WHEN e.is_payment_bundling = 0 THEN
                    (
                        SELECT COALESCE(SUM(pl.nominal_dibayarkan),0)
                        FROM payment_logs pl
                        WHERE pl.id_endorse = e.id
                        AND pl.created_at BETWEEN $start_dt AND $end_dt
                        $sumStatusCondSql
                    )
                ELSE
                    (
                        SELECT COALESCE(SUM(pl.nominal_dibayarkan),0)
                        FROM payment_logs pl
                        WHERE (pl.id_endorse = 0 OR pl.id_endorse IS NULL)
                        AND pl.id_campaign = e.id_campaign
                        AND pl.nama_influencer = e.nama_creator
                        AND pl.created_at BETWEEN $start_dt AND $end_dt
                        $sumStatusCondSql
                    )
            END
        ";

        // ====== LAST payment_created_at (ambil dari IN-RANGE aggregate) ======
        $last_payment_pick_sql = "
            COALESCE(
                CASE WHEN e.is_payment_bundling = 0 THEN pl_e.last_payment_created_at END,
                CASE WHEN e.is_payment_bundling = 1 THEN pl_b.last_payment_created_at END
            )
        ";

        // ====== DISPLAY UPDATED AT ======
        $display_updated_sql = "
            CASE
                WHEN COALESCE(e.pengajuan_status_payment,'') <> '' THEN
                    $pengajuan_ts_sql
                WHEN ($status_pick_sql) <> '' THEN
                    $last_payment_pick_sql
                ELSE e.updated_at
            END
        ";

        // ====== SELECT utama ======
        $sql = "
            SELECT 
                e.id,
                CASE WHEN e.is_payment_bundling = 0 THEN e.id ELSE NULL END AS endorse_id,

                e.link_mou,
                e.pic,
                e.tgl_tf,
                e.nama_creator,
                e.id_campaign,
                c.title,
                CONCAT(i.bank, ' - ', i.no_rekening) AS no_rekening,
                i.pemilik_rekening,

                e.total_cost,
                e.nominal_pengajuan,

                e.status_endorse,
                e.`desc`,

                ($sum_pick_sql)   AS nominal_dibayarkan_calc,
                ($status_pick_sql) AS status_payment_calc,

                e.keterangan_payment,
                e.pengajuan_status_payment,
                e.updated_at,
                e.is_payment_bundling,

                $pengajuan_ts_sql      AS last_pengajuan_updated_at,
                $last_payment_pick_sql AS last_payment_created_at,
                $display_updated_sql   AS display_updated_at

            FROM endorse e
            JOIN endorse_campaign c ON c.id = e.id_campaign
            JOIN influencer i       ON i.id = e.influencer

            /* pre-aggregate IN-RANGE */
            LEFT JOIN ( $pl_endorse_sql ) pl_e
                ON pl_e.id_endorse = e.id
            LEFT JOIN ( $pl_bundling_sql ) pl_b
                ON pl_b.id_campaign = e.id_campaign AND pl_b.nama_influencer = e.nama_creator

            /* pre-aggregate ALL-TIME (untuk status lifetime) */
            LEFT JOIN ( $pl_endorse_all_sql ) pl_e_all
                ON pl_e_all.id_endorse = e.id
            LEFT JOIN ( $pl_bundling_all_sql ) pl_b_all
                ON pl_b_all.id_campaign = e.id_campaign AND pl_b_all.nama_influencer = e.nama_creator

            $where
            ORDER BY display_updated_at DESC, e.updated_at DESC, e.id DESC
        ";

        // ====== Eksekusi ======
        $rows = $this->mymodel->selectWithQuery($sql);

        // ====== Post-processing (tetap sama dengan flowmu) ======
        $grouped   = [];
        $finalRows = [];

        foreach ($rows as $r) {
            $r['total_cost']         = (float) ($r['total_cost'] ?? 0);
            $r['nominal_pengajuan']  = (float) ($r['nominal_pengajuan'] ?? 0);
            $r['display_updated_at'] = $r['display_updated_at'] ?? $r['updated_at'];

            if ($onlyPengajuanSelected) {
                $r['status_payment']     = '';
                $r['nominal_dibayarkan'] = 0.0;
            } else {
                $r['status_payment']     = $r['status_payment_calc'] ?? '';
                $r['nominal_dibayarkan'] = (float)($r['nominal_dibayarkan_calc'] ?? 0);
            }

            if ((int)$r['is_payment_bundling'] === 1) {
                $key = $r['id_campaign'].'|'.$r['nama_creator'];
                if (!isset($grouped[$key])) {
                    $grouped[$key] = $r;
                    $grouped[$key]['endorse_id'] = NULL;
                } else {
                    $grouped[$key]['total_cost']        += $r['total_cost'];
                    $grouped[$key]['nominal_pengajuan']  = $r['nominal_pengajuan'];
                    if (strtotime($r['display_updated_at']) > strtotime($grouped[$key]['display_updated_at'])) {
                        $sum_cost = $grouped[$key]['total_cost'];
                        $sum_nom  = $grouped[$key]['nominal_pengajuan'];
                        $grouped[$key] = $r;
                        $grouped[$key]['total_cost']        = $sum_cost;
                        $grouped[$key]['nominal_pengajuan'] = $sum_nom;
                        $grouped[$key]['endorse_id']        = NULL;
                    } else {
                        if (strtotime((string)$r['last_pengajuan_updated_at']) > strtotime((string)$grouped[$key]['last_pengajuan_updated_at'])) {
                            $grouped[$key]['last_pengajuan_updated_at'] = $r['last_pengajuan_updated_at'];
                        }
                        if (strtotime((string)$r['last_payment_created_at']) > strtotime((string)$grouped[$key]['last_payment_created_at'])) {
                            $grouped[$key]['last_payment_created_at'] = $r['last_payment_created_at'];
                        }
                        if (strtotime($r['display_updated_at']) > strtotime($grouped[$key]['display_updated_at'])) {
                            $grouped[$key]['display_updated_at'] = $r['display_updated_at'];
                        }
                    }
                }
            } else {
                $finalRows[] = $r;
            }
        }

        $finalRows = array_merge($finalRows, array_values($grouped));

        if ($filterDPFPSelected) {
            $finalRows = array_values(array_filter($finalRows, function ($row) {
                return (float)($row['nominal_dibayarkan'] ?? 0) > 0;
            }));
        }

        usort($finalRows, fn($a,$b) => (strtotime($b['display_updated_at'] ?? $b['updated_at'])) <=> (strtotime($a['display_updated_at'] ?? $a['updated_at'])));

        $data['payment_fee']  = $finalRows;
        $data['nama_creator'] = $this->mymodel->selectWithQuery("SELECT DISTINCT nama_creator FROM endorse WHERE status_payment != ''");
        $data['pic']          = $this->mymodel->selectWithQuery("SELECT DISTINCT pic FROM endorse WHERE status_payment != ''");

        $view_path = 'endorse/payment_fee';
        $data['content'] = $this->load->view($view_path, $data, true);
        $this->load->view('TemplateDashboard', $data);
    }





    public function action()
    {
        $data['id_campaign'] = $_GET['id_campaign'] ?? null;

        $id_selected_v2 = $this->input->post('id_selected_v2');
        $id_selected    = $this->input->get('id_selected') ?: $this->input->post('id_selected');
        if ($id_selected) { $id = explode(',', $id_selected); }

        $code = $_GET['code'] ?? '';
        $data['data']['id']   = $id ?? [];
        $data['data']['code'] = $code;

        if ($code == "bulk_payment") {
            $data['question'] = "Apakah kamu yakin ingin bulk payment endorse ini?";
            $data['btn']      = "Payment";
        } else if ($code == "rollback_payment") {
            $data['question'] = "Rollback payment terakhir untuk baris terpilih? Data akan dikembalikan ke status pengajuan.";
            $data['btn']      = "Rollback";
        }

        $this->load->view("endorse/action_payment", $data);
    }


    // public function action_process()
    // {
    //     $code = $this->input->post('code', true);
    //     $user = $_SESSION['user'] ?? null;

    //     if (!$user) {
    //         echo $this->template->alert_danger('Sesi pengguna tidak ditemukan.');
    //         return;
    //     }

    //     $selected = $this->input->post('id_selected', true); // "id_campaign|urlencoded(nama_creator),..."
    //     if (!$selected) {
    //         echo $this->template->alert_danger('Tidak ada baris yang dipilih.');
    //         return;
    //     }

    //     $pairs = array_filter(array_map('trim', explode(',', $selected)));
    //     $now   = date('Y-m-d H:i:s');
    //     $uid   = (int)$user['id'];
    //     $ucode = $user['code'];

    //     // =========================
    //     // ======= BULK FLOW =======
    //     // =========================
    //     if ($code === 'bulk_payment') {
    //         $link_telegram = trim((string)$this->input->post('link_telegram', true));
    //         if ($link_telegram === '') {
    //             echo $this->template->alert_danger('Link Telegram wajib diisi.');
    //             return;
    //         }

    //         $tgl_tf = trim((string)$this->input->post('tgl_tf', true));
    //         if ($tgl_tf === '') {
    //             echo $this->template->alert_danger('Tanggal TF wajib diisi.');
    //             return;
    //         }

    //         $this->db->trans_begin();
    //         try {
    //             $processType = function(string $type, string $id_campaign, string $nama_creator)
    //                 use ($link_telegram, $tgl_tf, $now, $uid)
    //             {
    //                 $type  = strtoupper($type);
    //                 $exact = 'Pengajuan Payment ' . $type;

    //                 // Ambil SEMUA baris bundling untuk pair ini yang lagi pengajuan tipe ini
    //                 $rows = $this->db->query("
    //                     SELECT e.id, e.total_cost, COALESCE(e.nominal_pengajuan,0) AS nominal_pengajuan
    //                     FROM endorse e
    //                     WHERE e.id_campaign = ?
    //                     AND e.nama_creator = ?
    //                     AND (e.is_payment_bundling = 1)
    //                     AND TRIM(e.pengajuan_status_payment) = ?
    //                     AND COALESCE(e.nominal_pengajuan,0) > 0
    //                     AND e.status_endorse NOT IN ('Review','Hold','Reject','Problem')
    //                 ", [$id_campaign, $nama_creator, $exact])->result_array();

    //                 if (empty($rows)) return;

    //                 // UPDATE semua baris → pindahkan nominal_pengajuan ke nominal_dibayarkan, clear pengajuan
    //                 $this->db->set('status_payment', $type);
    //                 $this->db->set('link_telegram', $link_telegram);
    //                 $this->db->set('tgl_tf', $tgl_tf);
    //                 $this->db->set('updated_at', $now);
    //                 $this->db->set('updated_by', $uid);
    //                 $this->db->set('nominal_dibayarkan', 'COALESCE(nominal_dibayarkan,0) + COALESCE(nominal_pengajuan,0)', false);
    //                 $this->db->set('nominal_pengajuan', 0, false);
    //                 $this->db->set('pengajuan_status_payment', null);
    //                 $this->db->where('id_campaign', $id_campaign);
    //                 $this->db->where('nama_creator', $nama_creator);
    //                 $this->db->group_start();
    //                 $this->db->where('is_payment_bundling', 1);
    //                 $this->db->group_end();
    //                 $this->db->where('TRIM(pengajuan_status_payment) =', $exact, false);
    //                 if (!$this->db->update('endorse')) {
    //                     throw new Exception('Gagal update endorse untuk '.$nama_creator.' ('.$type.')');
    //                 }

    //                 // ALWAYS INSERT log per baris (tanpa cek duplikat)
    //                 foreach ($rows as $r) {
    //                     $nominal = (float)$r['nominal_pengajuan'];
    //                     if ($nominal <= 0) continue;

    //                     $total_cost = (float)$r['total_cost'];
    //                     $log = [
    //                         'id_endorse'         => 0, // bundling
    //                         'id_campaign'        => $id_campaign,
    //                         'nama_influencer'    => $nama_creator,
    //                         'created_at'         => $now,
    //                         'updated_at'         => $now,
    //                         'created_by'         => $uid,
    //                         'updated_by'         => $uid,
    //                         'total_cost'         => $total_cost,
    //                         'nominal_dibayarkan' => $nominal,
    //                         'bukti_tf'           => null,
    //                         'link_tele'          => $link_telegram,
    //                         'status_payment'     => $type,
    //                     ];
    //                     if (!$this->db->insert('payment_logs', $log)) {
    //                         throw new Exception('Gagal insert log '.$nama_creator.' ('.$type.')');
    //                     }
    //                 }
    //             };

    //             foreach ($pairs as $pair) {
    //                 $parts = explode('|', $pair, 2);
    //                 if (count($parts) !== 2) continue;
    //                 $id_campaign  = $parts[0];
    //                 $nama_creator = urldecode($parts[1]);

    //                 // urutan bebas
    //                 $processType('DP', $id_campaign, $nama_creator);
    //                 $processType('FP', $id_campaign, $nama_creator);
    //             }

    //             if ($this->db->trans_status() === false) {
    //                 throw new Exception('DB Transaction failed (bulk_payment)');
    //             }

    //             $this->db->trans_commit();
    //             echo $this->template->alert_success('Bulk payment berhasil diproses.');
    //             return;

    //         } catch (Throwable $e) {
    //             $this->db->trans_rollback();
    //             echo $this->template->alert_danger('Bulk payment gagal: ' . htmlspecialchars($e->getMessage()));
    //             return;
    //         }
    //     }

    //     // ============================
    //     // ===== ROLLBACK PAYMENT =====
    //     // ============================
    //     if ($code === 'rollback_payment') {
    //         $this->db->trans_begin();
    //         try {
    //             foreach ($pairs as $pair) {
    //                 $parts = explode('|', $pair, 2);
    //                 if (count($parts) !== 2) continue;
    //                 $id_campaign  = $parts[0];
    //                 $nama_creator = urldecode($parts[1]);

    //                 // Ambil log terakhir DP/FP untuk pair ini (bundling)
    //                 $log = $this->db->query("
    //                     SELECT *
    //                     FROM payment_logs
    //                     WHERE id_campaign = ? AND nama_influencer = ?
    //                     AND status_payment IN ('DP','FP')
    //                     ORDER BY created_at DESC, id DESC
    //                     LIMIT 1
    //                 ", [$id_campaign, $nama_creator])->row_array();

    //                 if (empty($log)) continue;

    //                 $amount = (float)($log['nominal_dibayarkan'] ?? 0);
    //                 if ($amount <= 0) {
    //                     $this->db->delete('payment_logs', ['id' => (int)$log['id']]);
    //                     continue;
    //                 }

    //                 $statusType = strtoupper(trim((string)$log['status_payment']));
    //                 $rollbackPengajuanStatus = in_array($statusType, ['DP','FP'], true)
    //                     ? ('Pengajuan Payment ' . $statusType)
    //                     : 'Pengajuan Payment';

    //                 // Ambil satu baris endorse bundling terbaru untuk append JSON dan kembalikan nominal
    //                 $endorse = $this->db->query("
    //                     SELECT id, pengajuan_payment_logs
    //                     FROM endorse
    //                     WHERE id_campaign = ? AND nama_creator = ? AND is_payment_bundling = 1
    //                     ORDER BY updated_at DESC, id DESC
    //                     LIMIT 1
    //                 ", [$id_campaign, $nama_creator])->row_array();

    //                 if (empty($endorse)) {
    //                     throw new Exception('Data endorse bundling tidak ditemukan untuk rollback.');
    //                 }

    //                 // Reset header pasangan
    //                 $this->db->set('status_payment', '');
    //                 $this->db->set('pengajuan_status_payment', $rollbackPengajuanStatus);
    //                 $this->db->set('tgl_tf', null);
    //                 $this->db->set('bukti_tf', null);
    //                 $this->db->set('link_telegram', null);
    //                 $this->db->set('updated_at', $now);
    //                 $this->db->set('updated_by', $uid);
    //                 $this->db->where('id_campaign', $id_campaign);
    //                 $this->db->where('nama_creator', $nama_creator);
    //                 $this->db->group_start();
    //                 $this->db->where('is_payment_bundling', 1);
    //                 $this->db->group_end();
    //                 if (!$this->db->update('endorse')) {
    //                     throw new Exception('Gagal update header rollback.');
    //                 }

    //                 // Kembalikan nominal ke satu baris
    //                 $logsJson = [];
    //                 if (!empty($endorse['pengajuan_payment_logs'])) {
    //                     $tmp = json_decode($endorse['pengajuan_payment_logs'], true);
    //                     if (is_array($tmp)) $logsJson = $tmp;
    //                 }
    //                 $logsJson[] = [
    //                     'status'            => $rollbackPengajuanStatus,
    //                     'nominal_pengajuan' => $amount,
    //                     'note'              => 'rollback from payment_logs id '.$log['id'].' status '.$statusType,
    //                     'created_at'        => $now,
    //                     'updated_at'        => $now,
    //                     'created_by'        => $ucode,
    //                 ];

    //                 $this->db->set('nominal_dibayarkan', 'GREATEST(COALESCE(nominal_dibayarkan,0) - '.$this->db->escape_str($amount).', 0)', false);
    //                 $this->db->set('nominal_pengajuan',  'COALESCE(nominal_pengajuan,0) + '.$this->db->escape_str($amount), false);
    //                 $this->db->set('pengajuan_payment_logs', json_encode($logsJson));
    //                 $this->db->where('id', (int)$endorse['id']);
    //                 if (!$this->db->update('endorse')) {
    //                     throw new Exception('Gagal mengembalikan nominal pada endorse.');
    //                 }

    //                 // Hapus log terakhir
    //                 $this->db->delete('payment_logs', ['id' => (int)$log['id']]);
    //             }

    //             if ($this->db->trans_status() === false) {
    //                 throw new Exception('DB Transaction failed (rollback_payment)');
    //             }

    //             $this->db->trans_commit();
    //             echo $this->template->alert_success('Rollback payment berhasil.');
    //             return;

    //         } catch (Throwable $e) {
    //             $this->db->trans_rollback();
    //             echo $this->template->alert_danger('Rollback gagal: ' . htmlspecialchars($e->getMessage()));
    //             return;
    //         }
    //     }

    //     echo $this->template->alert_danger('Kode aksi tidak valid.');
    // }

    public function action_process()
    {
        $code = $this->input->post('code', true);
        $user = $_SESSION['user'] ?? null;

        if (!$user) {
            echo $this->template->alert_danger('Sesi pengguna tidak ditemukan.');
            return;
        }

        $selected = $this->input->post('id_selected', true);
        if (!$selected) {
            echo $this->template->alert_danger('Tidak ada baris yang dipilih.');
            return;
        }

        $pairs = array_filter(array_map('trim', explode(',', $selected)));
        $now   = date('Y-m-d H:i:s');
        $uid   = (int)$user['id'];
        $ucode = $user['code'];

        // =========================
        // ======= BULK FLOW =======
        // =========================
        if ($code === 'bulk_payment') {
            $link_telegram = trim((string)$this->input->post('link_telegram', true));
            if ($link_telegram === '') {
                echo $this->template->alert_danger('Link Telegram wajib diisi.');
                return;
            }

            $tgl_tf = trim((string)$this->input->post('tgl_tf', true));
            if ($tgl_tf === '') {
                echo $this->template->alert_danger('Tanggal TF wajib diisi.');
                return;
            }

            $this->db->trans_begin();
            try {
                $processType = function(string $type, string $id_campaign, string $nama_creator)
                    use ($link_telegram, $tgl_tf, $now, $uid)
                {
                    $exact = 'Pengajuan Payment ' . $type;

                    // 1) Ambil agregat nominal & total_cost untuk semua baris yang diajukan
                    $agg = $this->db->query("
                        SELECT 
                            MAX(COALESCE(e.nominal_pengajuan,0)) AS total_nominal,
                            SUM(COALESCE(e.total_cost,0))        AS total_cost_sum,
                            COUNT(*)                               AS rows_cnt
                        FROM endorse e
                        WHERE e.id_campaign = ?
                        AND e.nama_creator = ?
                        AND e.pengajuan_status_payment = ?
                        AND COALESCE(e.nominal_pengajuan,0) > 0
                    ", [$id_campaign, $nama_creator, $exact])->row_array();

                    $total_nominal = (float)($agg['total_nominal'] ?? 0);
                    if ($total_nominal <= 0) {
                        return; 
                    }

                    // 2) Mass update semua baris endorse yg match (konsisten dgn store):
                    $this->db->set('status_payment', $type);
                    $this->db->set('link_telegram', $link_telegram);
                    $this->db->set('tgl_tf', $tgl_tf);
                    $this->db->set('updated_at', $now);
                    $this->db->set('updated_by', $uid);
                    $this->db->set('nominal_dibayarkan', 'COALESCE(nominal_dibayarkan,0) + COALESCE(nominal_pengajuan,0)', false);
                    $this->db->set('nominal_pengajuan', 0);
                    $this->db->set('pengajuan_status_payment', null);
                    $this->db->where('id_campaign', $id_campaign);
                    $this->db->where('nama_creator', $nama_creator);
                    $this->db->where('pengajuan_status_payment', $exact);
                    $this->db->update('endorse');

                    // 3) Tulis SATU baris log bundling (TANPA id_endorse)
                    $log = [
                        'id_campaign'        => $id_campaign,
                        'nama_influencer'    => $nama_creator,
                        'created_at'         => $now,
                        'updated_at'         => $now,
                        'created_by'         => $uid,
                        'updated_by'         => $uid,
                        'total_cost'         => (float)($agg['total_cost_sum'] ?? 0),
                        'nominal_dibayarkan' => $total_nominal,  
                        'bukti_tf'           => null,           
                        'link_tele'          => $link_telegram,
                        'status_payment'     => $type,           
                        'berhasil_dibayarkan'=> 1,              
                    ];

                    $this->db->insert('payment_logs', $log);
                };

                $processTypeSingle = function(string $type, string $id_campaign, string $nama_creator, int $endorse_id)
                    use ($link_telegram, $tgl_tf, $now, $uid)
                {
                    $exact = 'Pengajuan Payment ' . $type;

                    // 1) Ambil nominal_pengajuan & total_cost baris spesifik (non-bundling)
                    $row = $this->db->query("
                        SELECT 
                            COALESCE(e.nominal_pengajuan,0) AS nominal_pengajuan,
                            COALESCE(e.total_cost,0)        AS total_cost
                        FROM endorse e
                        WHERE e.id = ?
                        AND e.id_campaign = ?
                        AND e.nama_creator = ?
                        AND e.pengajuan_status_payment = ?
                    ", [$endorse_id, $id_campaign, $nama_creator, $exact])->row_array();

                    $nominal = (float)($row['nominal_pengajuan'] ?? 0);
                    if ($nominal <= 0) return;

                    // 2) Update hanya baris itu
                    $this->db->set('status_payment', $type);
                    $this->db->set('link_telegram', $link_telegram);
                    $this->db->set('tgl_tf', $tgl_tf);
                    $this->db->set('updated_at', $now);
                    $this->db->set('updated_by', $uid);
                    $this->db->set('nominal_dibayarkan', 'COALESCE(nominal_dibayarkan,0) + COALESCE(nominal_pengajuan,0)', false);
                    $this->db->set('nominal_pengajuan', 0);
                    $this->db->set('pengajuan_status_payment', null);
                    $this->db->where('id', $endorse_id);
                    $this->db->update('endorse');

                    // 3) Tulis LOG dengan id_endorse (non-bundling)
                    $this->db->insert('payment_logs', [
                        'id_endorse'         => $endorse_id,             
                        'id_campaign'        => $id_campaign,
                        'nama_influencer'    => $nama_creator,
                        'created_at'         => $now,
                        'updated_at'         => $now,
                        'created_by'         => $uid,
                        'updated_by'         => $uid,
                        'total_cost'         => (float)($row['total_cost'] ?? 0),
                        'nominal_dibayarkan' => $nominal,
                        'bukti_tf'           => null,
                        'link_tele'          => $link_telegram,
                        'status_payment'     => $type,                
                        'berhasil_dibayarkan'=> 1,
                    ]);
                };

                foreach ($pairs as $pair) {
                    $parts = explode('|', $pair);
                    if (count($parts) < 2) continue;

                    $id_campaign  = $parts[0];
                    $nama_creator = urldecode($parts[1]);
                    $endorse_id = 0;
                    if (isset($parts[2])) {
                        $id3 = trim($parts[2]);
                        if (preg_match('/^\d+$/', $id3)) $endorse_id = (int)$id3;
                    }


                    if ($endorse_id > 0) {
                        // === NON-BUNDLING ===
                        $processTypeSingle('DP', $id_campaign, $nama_creator, $endorse_id);
                        $processTypeSingle('FP', $id_campaign, $nama_creator, $endorse_id);
                    } else {
                        // === BUNDLING (seperti kode Anda sekarang) ===
                        $processType('DP', $id_campaign, $nama_creator);
                        $processType('FP', $id_campaign, $nama_creator);
                    }
                }


                if ($this->db->trans_status() === false) {
                    throw new Exception('DB Transaction failed (bulk_payment)');
                }

                $this->db->trans_commit();
                echo $this->template->alert_success('Bulk payment (bundling) berhasil diproses.');
                return;
            } catch (Throwable $e) {
                $this->db->trans_rollback();
                echo $this->template->alert_danger('Bulk payment gagal: ' . htmlspecialchars($e->getMessage()));
                return;
            }
        }



        // ============================
        // ===== ROLLBACK PAYMENT =====
        // ============================
        if ($code === 'rollback_payment') {
            $this->db->trans_begin();
            try {
                foreach ($pairs as $pair) {
                    $parts = explode('|', $pair);
                    if (count($parts) < 2) continue;

                    $id_campaign  = $parts[0];
                    $nama_creator = urldecode($parts[1]);
                    $endorse_id   = (isset($parts[2]) && ctype_digit($parts[2])) ? (int)$parts[2] : 0;

                    // Ambil log terakhir: jika non-bundling, filter pakai id_endorse
                    if ($endorse_id > 0) {
                        $log = $this->db->query("
                            SELECT *
                            FROM payment_logs
                            WHERE id_campaign = ? AND nama_influencer = ? AND id_endorse = ?
                            ORDER BY created_at DESC, id DESC
                            LIMIT 1
                        ", [$id_campaign, $nama_creator, $endorse_id])->row_array();
                    } else {
                        $log = $this->db->query("
                            SELECT *
                            FROM payment_logs
                            WHERE id_campaign = ? AND nama_influencer = ?
                            ORDER BY created_at DESC, id DESC
                            LIMIT 1
                        ", [$id_campaign, $nama_creator])->row_array();
                    }

                    if (empty($log)) continue;

                    $amount = (float)($log['nominal_dibayarkan'] ?? 0);
                    if ($amount <= 0) continue;

                    $statusType = strtoupper(trim((string)$log['status_payment']));
                    $allowed    = ['DP','FP'];
                    $rollbackPengajuanStatus = in_array($statusType, $allowed, true)
                        ? ('Pengajuan Payment ' . $statusType)
                        : 'Pengajuan Payment';

                    if ($endorse_id > 0) {
                        // Non-bundling: rollback hanya 1 baris
                        $endorse = $this->db->query("
                            SELECT id, pengajuan_payment_logs
                            FROM endorse
                            WHERE id = ? AND id_campaign = ? AND nama_creator = ?
                            LIMIT 1
                        ", [$endorse_id, $id_campaign, $nama_creator])->row_array();
                    } else {
                        // Bundling: ambil salah satu baris terbaru untuk tulis log pengajuan
                        $endorse = $this->db->query("
                            SELECT id, pengajuan_payment_logs
                            FROM endorse
                            WHERE id_campaign = ? AND nama_creator = ?
                            ORDER BY updated_at DESC, id DESC
                            LIMIT 1
                        ", [$id_campaign, $nama_creator])->row_array();
                    }

                    if (empty($endorse)) continue;

                    // Reset status (scope dibedakan)
                    $this->db->set('status_payment', '');
                    $this->db->set('pengajuan_status_payment', $rollbackPengajuanStatus);
                    $this->db->set('tgl_tf', null);
                    $this->db->set('bukti_tf', null);
                    $this->db->set('link_telegram', null);
                    $this->db->set('updated_at', $now);
                    $this->db->set('updated_by', $uid);

                    if ($endorse_id > 0) {
                        $this->db->where('id', $endorse_id);
                    } else {
                        $this->db->where('id_campaign', $id_campaign);
                        $this->db->where('nama_creator', $nama_creator);
                    }
                    $this->db->update('endorse');

                    // Kembalikan nominal (hanya baris terkait di non-bundling; bundling tetap mass)
                    $this->db->set('nominal_dibayarkan', 'GREATEST(COALESCE(nominal_dibayarkan,0) - '.$this->db->escape_str($amount).', 0)', false);
                    $this->db->set('nominal_pengajuan', 'COALESCE(nominal_pengajuan,0) + '.$this->db->escape_str($amount), false);
                    $logs = [];
                    if (!empty($endorse['pengajuan_payment_logs'])) {
                        $tmp = json_decode($endorse['pengajuan_payment_logs'], true);
                        if (is_array($tmp)) { $logs = $tmp; }
                    }
                    $logs[] = [
                        'status'             => $rollbackPengajuanStatus,
                        'nominal_pengajuan'  => $amount,
                        'note'               => 'rollback from payment_logs id '.$log['id'].' status '.$statusType,
                        'created_at'         => $now,
                        'updated_at'         => $now,
                        'created_by'         => $ucode,
                    ];
                    $this->db->set('pengajuan_payment_logs', json_encode($logs));
                    $this->db->where('id', (int)$endorse['id']);
                    $this->db->update('endorse');

                    // Hapus log yang dibatalkan
                    $this->db->delete('payment_logs', ['id' => (int)$log['id']]);
                }


                if ($this->db->trans_status() === false) {
                    throw new Exception('DB Transaction failed (rollback_payment)');
                }

                $this->db->trans_commit();
                echo $this->template->alert_success('Rollback payment berhasil.');
                return;
            } catch (Throwable $e) {
                $this->db->trans_rollback();
                echo $this->template->alert_danger('Rollback gagal: ' . htmlspecialchars($e->getMessage()));
                return;
            }
        }
        echo $this->template->alert_danger('Kode aksi tidak valid.');
    }


    public function edit()
    {
        $nama_creator = $this->input->get('nama_creator');
        $id_campaign  = $this->input->get('id_campaign');
        $id           = (int)$this->input->get('id');

        $start_date   = $this->input->get('start_date');
        $until_date   = $this->input->get('until_date');
        if (empty($start_date)) $start_date = date('Y-m-d', strtotime('-31 days'));
        if (empty($until_date)) $until_date = date('Y-m-d');
        $start_dt = $this->db->escape($start_date . ' 00:00:00');
        $end_dt   = $this->db->escape($until_date . ' 23:59:59');

        $endorse_status = $this->input->get('endorse_status'); // null | '' | 'DP' | 'FP' | 'DP,FP'
        $tokens = ($endorse_status === null) ? [] : (($endorse_status === '') ? [''] : array_map('trim', explode(',', $endorse_status)));
        $hasPengajuan = in_array('', $tokens, true);
        $hasDP        = in_array('DP', $tokens, true);
        $hasFP        = in_array('FP', $tokens, true);
        $onlyPengajuanSelected = ($hasPengajuan && !$hasDP && !$hasFP);

        $pengajuan_ts_sql = "
            CASE
                WHEN e.pengajuan_payment_logs IS NULL OR JSON_LENGTH(e.pengajuan_payment_logs) = 0 THEN NULL
                ELSE CAST(
                    COALESCE(
                        JSON_UNQUOTE(JSON_EXTRACT(
                            e.pengajuan_payment_logs,
                            CONCAT('$[', JSON_LENGTH(e.pengajuan_payment_logs) - 1, '].updated_at')
                        )),
                        JSON_UNQUOTE(JSON_EXTRACT(
                            e.pengajuan_payment_logs,
                            CONCAT('$[', JSON_LENGTH(e.pengajuan_payment_logs) - 1, '].created_at')
                        ))
                    ) AS DATETIME
                )
            END
        ";

        if ($id > 0) {
            $endorse = $this->db
                ->select('e.*, c.title, c.brand')
                ->from('endorse e')
                ->join('endorse_campaign c', 'c.id = e.id_campaign', 'inner')
                ->where('e.id', $id)
                ->where_not_in('e.status_endorse', ['Review','Hold','Reject','Problem'])
                ->get()->row_array();

            if (!$endorse) { show_error('Data tidak ditemukan', 404); return; }

            $data['campaign'] = [
                'id'                       => $endorse['id'],
                'id_campaign'              => $endorse['id_campaign'],
                'title'                    => $endorse['title'],
                'brand'                    => $endorse['brand'] ?? '',
                'nama_creator'             => $endorse['nama_creator'],
                'pic'                      => $endorse['pic'],
                'link_mou'                 => $endorse['link_mou'],
                'link_telegram'            => $endorse['link_telegram'],
                'total_cost'               => (float)$endorse['total_cost'],
                'status_endorse'           => $endorse['status_endorse'],
                'desc'                     => $endorse['desc'],
                'tgl_tf'                   => $endorse['tgl_tf'],
                'bukti_tf'                 => $endorse['bukti_tf'],
                'status_payment'           => $endorse['status_payment'],
                'nominal_dibayarkan'       => (float)$endorse['nominal_dibayarkan'],
                'keterangan_payment'       => $endorse['keterangan_payment'],
                'pengajuan_status_payment' => $endorse['pengajuan_status_payment'],
                'nominal_pengajuan'        => (float)$endorse['nominal_pengajuan'],
            ];

            $sql_logs_by_endorse = "
                SELECT l.*, u.code, 'payment' AS log_type
                FROM payment_logs l
                INNER JOIN user u ON u.id = l.created_by
                WHERE l.id_endorse = ".(int)$id."
                ORDER BY l.created_at DESC, l.id DESC
            ";
            $payment_logs = $this->mymodel->selectWithQuery($sql_logs_by_endorse) ?: [];

            $pengajuan_logs = [];
            if (!empty($endorse['pengajuan_payment_logs'])) {
                $tmp = json_decode($endorse['pengajuan_payment_logs'], true);
                if (is_array($tmp)) {
                    foreach ($tmp as $log) {
                        $pengajuan_logs[] = [
                            'status'             => $log['status'] ?? '',
                            'created_by'         => $log['created_by'] ?? '',
                            'created_at'         => $log['created_at'] ?? $endorse['updated_at'] ?? null,
                            'nominal_dibayarkan' => $log['nominal_pengajuan'] ?? 0,
                            'code'               => $log['created_by'] ?? '',
                            'log_type'           => 'pengajuan',
                            'status_payment'     => $log['status'] ?? '',
                            'note'               => $log['note'] ?? null
                        ];
                    }
                }
            }

            $all_logs = array_merge($payment_logs, $pengajuan_logs);
            usort($all_logs, fn($a,$b)=> strtotime($b['created_at']) <=> strtotime($a['created_at']));
            $data['all_logs'] = $all_logs;

            $this->load->view("endorse/edit_payment", $data);
            return;
        }

        $whereBase = "
            e.nama_creator = ".$this->db->escape($nama_creator)."
            AND e.id_campaign = ".$this->db->escape($id_campaign)."
            AND e.is_payment_bundling = 1
            AND e.status_endorse NOT IN ('Review','Hold','Reject','Problem')
        ";

        $extraDateFilter = "";
        if ($onlyPengajuanSelected) {
            $extraDateFilter = " AND ($pengajuan_ts_sql) BETWEEN $start_dt AND $end_dt ";
        } else {
            $extraDateFilter = "";
        }

        $sqlAgg = "
            SELECT
                SUM(e.total_cost)        AS total_cost_sum,
                e.nominal_pengajuan      AS nominal_pengajuan_new,
                MAX(c.title)             AS title,
                MAX(c.brand)             AS brand,
                MAX(e.pic)               AS pic,
                MAX(e.link_mou)          AS link_mou,
                MAX(e.link_telegram)     AS link_telegram,
                MAX(e.keterangan_payment) AS keterangan_payment,
                MAX(e.pengajuan_status_payment) AS pengajuan_status_payment
            FROM endorse e
            INNER JOIN endorse_campaign c ON c.id = e.id_campaign
            WHERE $whereBase
            $extraDateFilter
        ";
        $agg = $this->db->query($sqlAgg)->row_array();

        $data['campaign'] = [
            'id'                       => null,
            'id_campaign'              => $id_campaign,
            'title'                    => $agg['title'] ?? '',
            'brand'                    => $agg['brand'] ?? '',
            'nama_creator'             => $nama_creator,
            'pic'                      => $agg['pic'] ?? '',
            'link_mou'                 => $agg['link_mou'] ?? '',
            'link_telegram'            => $agg['link_telegram'] ?? '',
            'total_cost'               => (float)($agg['total_cost_sum'] ?? 0),
            'status_endorse'           => 'Acc',
            'desc'                     => '',
            'tgl_tf'                   => null,
            'bukti_tf'                 => null,
            'status_payment'           => '',
            'nominal_dibayarkan'       => 0,
            'keterangan_payment'       => $agg['keterangan_payment'] ?? '',
            'pengajuan_status_payment' => $agg['pengajuan_status_payment'] ?? '',
            'nominal_pengajuan'        => (float)($agg['nominal_pengajuan_new'] ?? 0),
        ];

        $sql_logs = "
            SELECT l.*, u.code, 'payment' AS log_type
            FROM payment_logs l
            INNER JOIN user u ON u.id = l.created_by
            WHERE l.nama_influencer = ".$this->db->escape($nama_creator)."
            AND l.id_campaign = ".$this->db->escape($id_campaign)."
            AND l.id_endorse = 0
            ORDER BY l.created_at DESC, l.id DESC
        ";
        $payment_logs = $this->mymodel->selectWithQuery($sql_logs) ?: [];

        $pengajuan_logs = [];
        $sqlPengajuanBundle = "
            SELECT e.pengajuan_payment_logs
            FROM endorse e
            WHERE $whereBase
            $extraDateFilter
            LIMIT 1
        ";
        $rows = $this->db->query($sqlPengajuanBundle)->result_array();
        foreach ($rows as $row) {
            if (!empty($row['pengajuan_payment_logs'])) {
                $tmp = json_decode($row['pengajuan_payment_logs'], true);
                if (is_array($tmp)) {
                    foreach ($tmp as $log) {
                        $pengajuan_logs[] = [
                            'status'             => $log['status'] ?? '',
                            'created_by'         => $log['created_by'] ?? '',
                            'created_at'         => $log['created_at'] ?? null,
                            'nominal_dibayarkan' => $log['nominal_pengajuan'] ?? 0,
                            'code'               => $log['created_by'] ?? '',
                            'log_type'           => 'pengajuan',
                            'status_payment'     => $log['status'] ?? '',
                            'note'               => $log['note'] ?? null
                        ];
                    }
                }
            }
        }

        $all_logs = array_merge($payment_logs, $pengajuan_logs);
        usort($all_logs, fn($a,$b)=> strtotime($b['created_at']) <=> strtotime($a['created_at']));
        $data['all_logs'] = $all_logs;

        $this->load->view("endorse/edit_payment", $data);
    }




    public function store()
    {
        $user = $_SESSION['user'];

        $dt                           = $_POST['dt'];
        $nama_creator                 = trim($dt['nama_creator'] ?? '');
        $status_payment               = strtoupper(trim($dt['status_payment'] ?? ''));
        $pengajuan_status_payment     = $dt['pengajuan_status_payment'] ?? null;
        $id_campaign                  = (int)($dt['id_campaign'] ?? 0);
        $id_endorse                   = (int)($dt['id'] ?? 0);
        $link_telegram                = trim($dt['link_telegram'] ?? '');
        $nominal_pengajuan            = (float)($dt['nominal_pengajuan'] ?? 0);
        $nominal_dibayarkan_sebelumnya= (float)($dt['nominal_dibayarkan'] ?? 0);
        $nominal_dibayarkan_total     = $nominal_pengajuan + $nominal_dibayarkan_sebelumnya;
        $keterangan_payment           = $dt['keterangan_payment'] ?? '';
        $tgl_tf                       = $dt['tgl_tf'] ?? null;
        $total_cost                   = (float)($dt['total_cost'] ?? 0);
        $berhasil_dibayarkan          = isset($dt['berhasil_dibayarkan']) ? (int)$dt['berhasil_dibayarkan'] : 1;

        $dt['img'] = $dt['img'] ?? null;

        // Upload bukti (opsional)
        if (!empty($_FILES['file']['name'])) {
            $dir  = "./assets/img/transfer/";
            $config = [
                'upload_path'   => $dir,
                'allowed_types' => 'jpg|jpeg|png',
                'overwrite'     => TRUE,
                'file_name'     => date("YmdHis"),
                'max_size'      => 2048,
            ];
            $this->load->library('upload', $config);
            if (!$this->upload->do_upload('file')) {
                echo $this->template->alert_danger($this->upload->display_errors());
                return;
            } else {
                $file      = $this->upload->data();
                $dt['img'] = $file['file_name'];
            }
        }

        $now        = date("Y-m-d H:i:s");
        $updated_by = (int)$user['id'];

        $data_endorse = [
            'status_payment'          => $status_payment,
            'link_telegram'           => $link_telegram,
            'pengajuan_status_payment'=> NULL,
            'bukti_tf'                => $dt['img'],
            'tgl_tf'                  => $tgl_tf,
            'nominal_dibayarkan'      => $nominal_dibayarkan_total,
            'nominal_pengajuan'       => 0,
            'updated_at'              => $now,
            'updated_by'              => $updated_by,
            'keterangan_payment'      => $keterangan_payment,
        ];

        $this->db->trans_begin();

        if (!empty($pengajuan_status_payment)) {
            $this->db->where('nama_creator', $nama_creator);
            $this->db->where('id_campaign', $id_campaign);
            $this->db->like('pengajuan_status_payment', 'Pengajuan Payment', 'both');
        } else {
            $this->db->where('id', $id_endorse);
        }

        if (!$this->db->update('endorse', $data_endorse)) {
            $this->db->trans_rollback();
            echo $this->template->alert_danger('Update data tidak berhasil!')." DB Error: ".$this->db->error()['message'];
            return;
        }

        $whereDup = [
            'id_endorse'         => $id_endorse,
            'id_campaign'        => $id_campaign,
            'nama_influencer'    => $nama_creator,
            'status_payment'     => $status_payment,
            'created_by'         => $updated_by,
            'total_cost'         => $total_cost,
            'nominal_dibayarkan' => $nominal_pengajuan,
            'berhasil_dibayarkan'=> $berhasil_dibayarkan,
        ];

        $exists = $this->db->select('id')
                        ->from('payment_logs')
                        ->where($whereDup)
                        ->limit(1)
                        ->get()
                        ->num_rows() > 0;

        if (!$exists) {
            $log_data = [
                'id_endorse'         => $id_endorse,
                'id_campaign'        => $id_campaign,
                'nama_influencer'    => $nama_creator,
                'created_at'         => $now,
                'updated_at'         => $now,
                'created_by'         => $updated_by,
                'updated_by'         => $updated_by,
                'total_cost'         => $total_cost,
                'nominal_dibayarkan' => $nominal_pengajuan, // hanya nominal aksi saat ini
                'bukti_tf'           => $dt['img'],
                'link_tele'          => $link_telegram,
                'status_payment'     => $status_payment,
                'berhasil_dibayarkan'=> $berhasil_dibayarkan,
            ];

            if (!$this->db->insert('payment_logs', $log_data)) {
                $this->db->trans_rollback();
                echo $this->template->alert_danger('Log pembayaran gagal direkam!')." DB Error: ".$this->db->error()['message'];
                return;
            }
            $msgLog = 'Log pembayaran direkam.';
        } else {
            $msgLog = 'Lewati insert log (sudah pernah direkam).';
        }

        $this->db->trans_commit();
        echo $this->template->alert_success('Update data berhasil. '.$msgLog);
    }


    public function logs()
    {
        $id_campaign  = (int)$this->input->get('id_campaign');
        $nama_creator = $this->input->get('nama_creator'); 
        $endorse_id   = (int)$this->input->get('endorse_id');
        $start_date   = $this->input->get('start_date');
        $until_date   = $this->input->get('until_date');

        if (empty($start_date)) $start_date = date('Y-m-01');
        if (empty($until_date)) $until_date = date('Y-m-d');

        $start_dt = $this->db->escape($start_date . ' 00:00:00');
        $end_dt   = $this->db->escape($until_date . ' 23:59:59');

        $this->db->select('status_payment, created_at, created_by, nominal_dibayarkan');
        $this->db->from('payment_logs');
        $this->db->where_in('status_payment', ['DP','FP']);

        if ($endorse_id > 0) {
            $this->db->where('id_endorse', $endorse_id);
        } else {
            $this->db->group_start()
                        ->where('id_endorse', 0)
                        ->or_where('id_endorse IS NULL', null, false)
                    ->group_end();
            $this->db->where('id_campaign', $id_campaign);
            $this->db->where('nama_influencer', $nama_creator);
        }

        $this->db->order_by('created_at', 'ASC');
        $rows = $this->db->get()->result_array();

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['ok' => true, 'data' => $rows]));
    }

}
