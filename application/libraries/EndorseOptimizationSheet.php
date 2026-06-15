<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * EndorseOptimizationSheet — builds the optimization dataset in the team's sheet layout and
 * pushes it to Google Sheets. Shared by the on-demand button (Endorse), the xlsx export
 * (Endorse), and the cron (Api_v2), so the column layout/filters live in exactly one place.
 *
 * Layout mirrors the team's "Database" tab plus an added System Status column; the app-owned
 * target tab (GOOGLE_SHEETS_TAB) is full-replaced on each sync.
 */
class EndorseOptimizationSheet
{
    protected $CI;

    public function __construct()
    {
        $this->CI = get_instance();
        $this->CI->load->database();
        $this->CI->load->model('mymodel');
        $this->CI->load->helper('env');
    }

    /**
     * Build header + rows from the endorse table, honoring the given filters.
     *
     * @param array $filters Keys: id_campaign, is_optimization, optimization_status, platform,
     *                       request_by, device, media_type, start_date, until_date.
     * @return array ['header' => string[], 'rows' => array<int,array<int,string>>]
     */
    public function buildRows(array $filters): array
    {
        $db = $this->CI->db;
        $where = " WHERE 1=1 ";

        if (!empty($filters['id_campaign'])) {
            $where .= " AND id_campaign = '" . $db->escape_str($filters['id_campaign']) . "' ";
        }

        // Default to optimization rows unless explicitly overridden with ''.
        if (isset($filters['is_optimization']) && $filters['is_optimization'] !== '') {
            $flag = $filters['is_optimization'] == '1' ? '1' : '0';
            $where .= " AND is_optimization = '$flag' ";
        } else {
            $where .= " AND is_optimization = '1' ";
        }

        if (!empty($filters['optimization_status'])) {
            $where .= " AND optimization_status = '" . $db->escape_str($filters['optimization_status']) . "' ";
        }
        if (!empty($filters['platform'])) {
            $where .= " AND platform = '" . $db->escape_str($filters['platform']) . "' ";
        }
        if (!empty($filters['request_by'])) {
            $where .= " AND request_by LIKE '%" . $db->escape_str($filters['request_by']) . "%' ";
        }
        if (!empty($filters['device'])) {
            $where .= " AND device LIKE '%" . $db->escape_str($filters['device']) . "%' ";
        }
        if (!empty($filters['media_type'])) {
            $where .= " AND tiktok_media_type = '" . $db->escape_str($filters['media_type']) . "' ";
        }
        if (!empty($filters['start_date']) && !empty($filters['until_date'])) {
            $sd = $db->escape_str($filters['start_date']);
            $ud = $db->escape_str($filters['until_date']);
            $where .= " AND ( (request_date IS NOT NULL AND request_date BETWEEN '$sd' AND '$ud')
                          OR (request_date IS NULL AND DATE(created_at) BETWEEN '$sd' AND '$ud') ) ";
        }

        $rows = $this->CI->mymodel->selectWithQuery("
            SELECT request_date, created_at, pic, link_upload, platform, request_by, device,
                   manual_status, optimization_status, request_keyword,
                   comment_initial, comment_final, comment_growth,
                   view_initial, view_final, view_growth,
                   like_initial, like_final, like_growth,
                   save_initial, save_final, save_growth,
                   share_initial, share_final, share_growth
            FROM endorse
            $where
            ORDER BY id DESC
        ");

        // Header order matches the team's "Database" tab, with an added System Status column
        // and a blank "Jumlah Komentar Optimasi" (the app has no equivalent field yet).
        $header = [
            'Tanggal', 'NO', 'PIC', 'Link Konten', 'Platform', 'REQUEST BY', 'Tools',
            'Manual Status', 'System Status', 'Request Keyword', 'Jumlah Komentar Optimasi',
            'Comment Sebelum Optimasi', 'Comment Sesudah Optimasi', 'Growth Comment',
            'Views Sebelum Optimasi', 'Views Sesudah Optimasi', 'Growth Views',
            'Like Sebelum Optimasi', 'Like Sesudah Optimasi', 'LikeViews',
            'Save Sebelum Optimasi', 'Save Sesudah Optimasi', 'Save Views',
            'Share Sebelum Optimasi', 'Share Sesudah Optimasi', 'Share Views',
        ];

        $out = [];
        $no = 1;
        foreach ($rows as $row) {
            $req_date = !empty($row['request_date'])
                ? $row['request_date']
                : (!empty($row['created_at']) ? date('Y-m-d', strtotime($row['created_at'])) : '');

            $out[] = array_map(function ($v) {
                return (string) ($v ?? '');
            }, [
                $req_date,
                $no,
                $row['pic'],
                $row['link_upload'],
                $row['platform'],
                $row['request_by'],
                $row['device'],
                $row['manual_status'],
                $row['optimization_status'],
                $row['request_keyword'],
                '', // Jumlah Komentar Optimasi (no app field yet)
                $row['comment_initial'], $row['comment_final'], $row['comment_growth'],
                $row['view_initial'], $row['view_final'], $row['view_growth'],
                $row['like_initial'], $row['like_final'], $row['like_growth'],
                $row['save_initial'], $row['save_final'], $row['save_growth'],
                $row['share_initial'], $row['share_final'], $row['share_growth'],
            ]);
            $no++;
        }

        return ['header' => $header, 'rows' => $out];
    }

    /**
     * Build + push the dataset to the configured spreadsheet/tab. Returns a JSON-ready array.
     *
     * @return array ['status' => bool, 'msg' => string, 'written' => int]
     */
    public function sync(array $filters): array
    {
        $spreadsheetId = env('GOOGLE_SHEETS_SPREADSHEET_ID');
        $tab = env('GOOGLE_SHEETS_TAB', 'AUTO_Optimasi');

        if (!$spreadsheetId) {
            return ['status' => false, 'msg' => 'GOOGLE_SHEETS_SPREADSHEET_ID belum diset di .env.', 'written' => 0];
        }

        try {
            $built = $this->buildRows($filters);
            $this->CI->load->library('GoogleSheets');
            $written = $this->CI->googlesheets->replaceTab($spreadsheetId, $tab, $built['header'], $built['rows']);
            return ['status' => true, 'msg' => "$written baris disinkronkan ke tab '$tab'.", 'written' => $written];
        } catch (\Throwable $e) {
            return ['status' => false, 'msg' => 'Gagal sync ke Google Sheet: ' . $e->getMessage(), 'written' => 0];
        }
    }
}
