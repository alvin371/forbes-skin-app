<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Endorse_sync — shared per-row sync application logic.
 *
 * Used by Endorse::sync_process (per-row UI button) and Api_v2::cronjob_endorse_refresh
 * (queue worker). Encapsulates: yesterday-stat lookup, endorse + endorse_logs writes,
 * FYP/CPM derivation, response classification for retry policy, and parent rollup.
 */
class Endorse_sync
{
    const ERR_OK        = 'ok';
    const ERR_PERMANENT = 'permanent';
    const ERR_TRANSIENT = 'transient';
    const ERR_EMPTY     = 'empty';
    const ERR_INFRA     = 'infra';
    const ERR_INFRA_DNS = 'infra_dns';
    const ERR_INFRA_CONNECT = 'infra_connect';
    const ERR_INFRA_TLS = 'infra_tls';
    const ERR_INFRA_STALL = 'infra_stall';
    const ERR_CONFIG    = 'config';

    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->load->model('mymodel');
    }

    /**
     * Classify a Template::get_social_media response for retry decisions.
     */
    public function classify_response(array $response, string $platform, string $url): array
    {
        if (!empty($response['status']) && !empty($response['data'])) {
            $hasMetrics = intval($response['data']['view'] ?? 0) > 0
                || intval($response['data']['like'] ?? 0) > 0
                || intval($response['data']['share'] ?? 0) > 0
                || intval($response['data']['comment'] ?? 0) > 0
                || intval($response['data']['collect'] ?? 0) > 0;
            if ($hasMetrics || !empty($response['data']['content_id'])) {
                return ['class' => self::ERR_OK, 'msg' => ''];
            }
        }

        if (!empty($response['status']) && !empty($response['data']['content_id'])) {
            return ['class' => self::ERR_OK, 'msg' => ''];
        }

        $msg = strval($response['msg'] ?? '');
        $machineClass = strval($response['error_class'] ?? '');

        if (in_array($machineClass, [
            self::ERR_INFRA,
            self::ERR_INFRA_DNS,
            self::ERR_INFRA_CONNECT,
            self::ERR_INFRA_TLS,
            self::ERR_INFRA_STALL,
            self::ERR_CONFIG,
            self::ERR_PERMANENT,
            self::ERR_EMPTY,
            self::ERR_TRANSIENT,
        ], true)) {
            return ['class' => $machineClass, 'msg' => $msg ?: 'Gagal mengambil data sosial media'];
        }

        if ($platform === 'Instagram') {
            return ['class' => self::ERR_PERMANENT, 'msg' => $msg ?: 'Individual Instagram post scraping belum tersedia'];
        }

        if (stripos($msg, 'video id tidak ditemukan') !== false
            || stripos($msg, 'url tidak ditemukan') !== false
            || stripos($msg, 'platform belum tersedia') !== false) {
            return ['class' => self::ERR_PERMANENT, 'msg' => $msg];
        }

        if (stripos($msg, 'stats data tidak ditemukan') !== false) {
            return ['class' => self::ERR_EMPTY, 'msg' => $msg];
        }

        // Network/timeout/5xx/rate-limit fall through here — let the queue retry.
        return ['class' => self::ERR_TRANSIENT, 'msg' => $msg ?: 'Gagal mengambil data sosial media'];
    }

    /**
     * Apply a sync response to one endorse row. Updates `endorse` and `endorse_logs`.
     * Does NOT roll up to endorse_campaign — caller decides when (per-row vs batched).
     *
     * @param array      $endorse     Endorse row (must contain id, id_campaign, influencer, brand, platform,
     *                                link_upload, total_cost, status, status_campaign).
     * @param array      $response    Output of Template::get_social_media.
     * @param int        $user_id     Acting user id.
     * @param array|null $prev_stats  Optional pre-loaded yesterday log row (likes_after, comment_after,
     *                                share_save_after, views_after). If null, fetched here.
     * @return array ['status' => bool, 'error_class' => string, 'msg' => string]
     */
    public function apply(array $endorse, array $response, int $user_id, ?array $prev_stats = null): array
    {
        $platform = strval($endorse['platform']);
        $url = strval($endorse['link_upload']);

        $classification = $this->classify_response($response, $platform, $url);

        if ($classification['class'] !== self::ERR_OK) {
            return [
                'status'      => false,
                'error_class' => $classification['class'],
                'msg'         => $classification['msg'],
            ];
        }

        $db = $this->CI->db;
        $today = date('Y-m-d');
        $id_endorse = intval($endorse['id']);

        if ($prev_stats === null) {
            $prev_stats = $this->load_prev_stats($id_endorse, $today);
        }

        $prev_likes      = intval($prev_stats['likes_after'] ?? 0);
        $prev_comment    = intval($prev_stats['comment_after'] ?? 0);
        $prev_share_save = intval($prev_stats['share_save_after'] ?? 0);
        $prev_views      = intval($prev_stats['views_after'] ?? 0);

        $stats = [
            'likes'      => intval($response['data']['like'] ?? 0),
            'comment'    => intval($response['data']['comment'] ?? 0),
            'share_save' => doubleval($response['data']['share'] ?? 0) + doubleval($response['data']['collect'] ?? 0),
            'views'      => intval($response['data']['view'] ?? 0),
        ];

        // Keep views non-decreasing per content (TikTok occasionally returns transient lower values).
        if ($stats['views'] < $prev_views) {
            $stats['views'] = $prev_views;
        }

        $is_fyp = null;
        if ($stats['views'] >= 50000) {
            $follower = $this->lookup_follower(intval($endorse['influencer']));
            if ($follower > 0) {
                $batas = intval($follower * 30 / 100);
                if ($stats['views'] >= $batas) {
                    $is_fyp = '1';
                }
            } else {
                $is_fyp = '1';
            }
        }

        $total_cost = doubleval($endorse['total_cost']);
        $cpm = ($total_cost > 0 && $stats['views'] > 0)
            ? ($total_cost / $stats['views'] * 1000)
            : 0;

        $endorseUpdate = [
            'status'           => strval($endorse['status']),
            'status_campaign'  => strval($endorse['status_campaign']),
            'sync_at'          => date('Y-m-d H:i:s'),
            'likes'            => $stats['likes'],
            'comment'          => $stats['comment'],
            'share_save'       => $stats['share_save'],
            'views'            => $stats['views'],
            'cpm'              => $cpm,
            'updated_at'       => date('Y-m-d H:i:s'),
            'updated_by'       => strval($user_id),
        ];
        if (!empty($response['data']['created_at'])) {
            $endorseUpdate['posting_at'] = $response['data']['created_at'];
        }
        if ($is_fyp !== null) {
            $endorseUpdate['is_fyp'] = $is_fyp;
        }
        if ($platform === 'Tiktok' && $db->field_exists('tiktok_content_id', 'endorse')) {
            $existingCover = strval($endorse['tiktok_cover'] ?? '');
            $normalizedCover = strval($response['data']['cover'] ?? '');
            if ($is_fyp !== null && $existingCover !== '' && !$this->looks_like_url($existingCover)) {
                $normalizedCover = $existingCover;
            }

            $endorseUpdate['tiktok_content_id'] = strval($response['data']['content_id'] ?? '');
            $endorseUpdate['tiktok_media_type'] = strval($response['data']['media_type'] ?? '');
            $endorseUpdate['tiktok_cover'] = $normalizedCover;
            $endorseUpdate['tiktok_content_link'] = strval($response['data']['video_link'] ?? '');
            $endorseUpdate['tiktok_fetched_at'] = date('Y-m-d H:i:s');
        }

        $db->update('endorse', $endorseUpdate, ['id' => $id_endorse]);

        // endorse_logs upsert for today.
        $existing = $this->CI->mymodel->selectWithQuery("
            SELECT id FROM endorse_logs
            WHERE id_endorse = '$id_endorse' AND date = '$today'
            ORDER BY id DESC LIMIT 1
        ");
        $existing_log_id = !empty($existing) ? intval($existing[0]['id']) : 0;

        $views_diff      = max(0, $stats['views']      - $prev_views);
        $likes_diff      = max(0, $stats['likes']      - $prev_likes);
        $comment_diff    = max(0, $stats['comment']    - $prev_comment);
        $share_save_diff = max(0, $stats['share_save'] - $prev_share_save);

        $cpm_diff   = ($total_cost > 0 && $views_diff > 0)         ? ($total_cost / $views_diff * 1000)         : 0;
        $cpm_after  = ($total_cost > 0 && $stats['views'] > 0)     ? ($total_cost / $stats['views'] * 1000)     : 0;
        $cpm_before = ($total_cost > 0 && $prev_views > 0)         ? ($total_cost / $prev_views * 1000)         : 0;

        $logRow = [
            'id_endorse'        => strval($id_endorse),
            'id_campaign'       => strval($endorse['id_campaign']),
            'influencer'        => strval($endorse['influencer']),
            'date'              => $today,
            'status'            => strval($endorse['status']),
            'status_campaign'   => strval($endorse['status_campaign']),
            'total_cost'        => strval($total_cost),
            'link_upload'       => strval($endorse['link_upload']),
            'platform'          => strval($endorse['platform']),
            'brand'             => strval($endorse['brand']),

            // Diff (today-vs-yesterday)
            'likes'             => strval($likes_diff),
            'comment'           => strval($comment_diff),
            'share_save'        => strval($share_save_diff),
            'views'             => strval($views_diff),
            'cpm'               => strval($cpm_diff),

            // Cumulative current
            'likes_after'       => strval($stats['likes']),
            'comment_after'     => strval($stats['comment']),
            'share_save_after'  => strval($stats['share_save']),
            'views_after'       => strval($stats['views']),
            'cpm_after'         => strval($cpm_after),

            // Cumulative prior
            'likes_before'      => strval($prev_likes),
            'comment_before'    => strval($prev_comment),
            'share_save_before' => strval($prev_share_save),
            'views_before'      => strval($prev_views),
            'cpm_before'        => strval($cpm_before),
        ];

        if ($existing_log_id) {
            $logRow['updated_at'] = date('Y-m-d H:i:s');
            $logRow['updated_by'] = strval($user_id);
            $db->update('endorse_logs', $logRow, ['id' => $existing_log_id]);
        } else {
            $logRow['created_at'] = date('Y-m-d H:i:s');
            $logRow['created_by'] = strval($user_id);
            $db->insert('endorse_logs', $logRow);
        }

        return [
            'status'      => true,
            'error_class' => self::ERR_OK,
            'msg'         => 'OK',
        ];
    }

    /**
     * Apply a frozen snapshot (initial baseline or final) to one endorse row.
     *
     * Deliberately separate from apply(): a snapshot must NOT touch the daily columns,
     * endorse_logs, FYP/CPM derivation, or the merged share_save. It only writes the
     * dedicated optimization columns and (for 'final') the growth = final - initial.
     * share and save are stored SEPARATELY here (save = API 'collect' key).
     *
     * Reuses classify_response() and returns the same shape as apply() so the queue
     * worker's retry/finalize machinery is unchanged.
     *
     * @param array  $endorse  Endorse row (must contain id, platform, link_upload, and
     *                         for 'final' the *_initial columns + initial_fetched_at).
     * @param array  $response Output of Template::get_social_media.
     * @param string $purpose  'initial' | 'final'.
     * @param int    $user_id  Acting user id.
     * @return array ['status' => bool, 'error_class' => string, 'msg' => string]
     */
    public function apply_snapshot(array $endorse, array $response, string $purpose, int $user_id): array
    {
        $platform = strval($endorse['platform']);
        $url = strval($endorse['link_upload']);
        $purpose = ($purpose === 'final') ? 'final' : 'initial';

        $classification = $this->classify_response($response, $platform, $url);
        if ($classification['class'] !== self::ERR_OK) {
            return [
                'status'      => false,
                'error_class' => $classification['class'],
                'msg'         => $classification['msg'],
            ];
        }

        $db = $this->CI->db;
        $id_endorse = intval($endorse['id']);
        $now = date('Y-m-d H:i:s');

        // NOTE: the API exposes "save" under the key `collect`.
        $metrics = [
            'like'    => intval($response['data']['like'] ?? 0),
            'comment' => intval($response['data']['comment'] ?? 0),
            'share'   => intval($response['data']['share'] ?? 0),
            'save'    => intval($response['data']['collect'] ?? 0),
            'view'    => intval($response['data']['view'] ?? 0),
        ];

        $update = [
            'updated_at' => $now,
            'updated_by' => strval($user_id),
        ];

        if ($purpose === 'initial') {
            // Baseline must stay frozen — never overwrite once captured.
            if (!empty($endorse['initial_fetched_at'])) {
                return [
                    'status'      => true,
                    'error_class' => self::ERR_OK,
                    'msg'         => 'Initial baseline already captured',
                ];
            }
            $update['like_initial']       = $metrics['like'];
            $update['comment_initial']    = $metrics['comment'];
            $update['share_initial']      = $metrics['share'];
            $update['save_initial']       = $metrics['save'];
            $update['view_initial']       = $metrics['view'];
            $update['initial_fetched_at'] = $now;
        } else {
            $update['like_final']      = $metrics['like'];
            $update['comment_final']   = $metrics['comment'];
            $update['share_final']     = $metrics['share'];
            $update['save_final']      = $metrics['save'];
            $update['view_final']      = $metrics['view'];
            $update['final_fetched_at'] = $now;

            // Growth = final - initial (initial defaults to 0 if never captured).
            $growthRow = [];
            foreach (['like', 'comment', 'share', 'save', 'view'] as $m) {
                $growthRow[$m . '_initial'] = $endorse[$m . '_initial'] ?? 0;
                $growthRow[$m . '_final']   = $metrics[$m];
            }
            foreach ($this->compute_growth($growthRow) as $k => $v) {
                $update[$k] = $v;
            }
        }

        // Capture TikTok thumbnail/content id for parity with the daily sync (optional).
        if ($platform === 'Tiktok' && $db->field_exists('tiktok_content_id', 'endorse')) {
            if (!empty($response['data']['content_id'])) {
                $update['tiktok_content_id'] = strval($response['data']['content_id']);
            }
            if (!empty($response['data']['media_type'])) {
                $update['tiktok_media_type'] = strval($response['data']['media_type']);
            }
            if (!empty($response['data']['cover'])) {
                $update['tiktok_cover'] = strval($response['data']['cover']);
            }
        }

        $db->update('endorse', $update, ['id' => $id_endorse]);

        return [
            'status'      => true,
            'error_class' => self::ERR_OK,
            'msg'         => 'OK',
        ];
    }

    /**
     * Compute growth = final - initial for the five optimization metrics.
     * Shared by apply_snapshot('final') and the manual-entry save path for
     * placeholder platforms. Returns null growth when either side is missing.
     *
     * @param array $row Row carrying *_initial and *_final keys.
     * @return array ['like_growth' => int|null, 'comment_growth' => ..., ...]
     */
    public function compute_growth(array $row): array
    {
        $out = [];
        foreach (['like', 'comment', 'share', 'save', 'view'] as $m) {
            $initial = $row[$m . '_initial'] ?? null;
            $final   = $row[$m . '_final'] ?? null;
            if ($initial === null || $initial === '' || $final === null || $final === '') {
                $out[$m . '_growth'] = null;
            } else {
                $out[$m . '_growth'] = intval($final) - intval($initial);
            }
        }
        return $out;
    }

    protected function looks_like_url(string $value): bool
    {
        return stripos($value, 'http://') === 0 || stripos($value, 'https://') === 0;
    }

    /**
     * Pre-load yesterday's stats for many endorse rows in one query.
     * Returns map: id_endorse => row (likes_after, comment_after, share_save_after, views_after).
     */
    public function load_prev_stats_batch(array $endorse_ids, string $today): array
    {
        $endorse_ids = array_filter(array_map('intval', $endorse_ids));
        if (empty($endorse_ids)) return [];

        $idList = implode(',', $endorse_ids);
        $rows = $this->CI->mymodel->selectWithQuery("
            SELECT t.id_endorse, t.likes_after, t.comment_after, t.share_save_after, t.views_after
            FROM endorse_logs t
            INNER JOIN (
                SELECT id_endorse, MAX(date) AS max_date
                FROM endorse_logs
                WHERE id_endorse IN ($idList)
                  AND date < '$today'
                  AND views_after > 0
                GROUP BY id_endorse
            ) m ON m.id_endorse = t.id_endorse AND m.max_date = t.date
        ");

        $map = [];
        foreach ($rows as $row) {
            $map[intval($row['id_endorse'])] = $row;
        }
        return $map;
    }

    /**
     * Recalculate aggregate counters for an endorse_campaign.
     * Mirrors the original Endorse::update_endorse_parent logic but takes user_id explicitly.
     */
    public function update_campaign_parent(int $id_campaign, int $user_id): void
    {
        $db = $this->CI->db;
        $today = date('Y-m-d');

        $existing = $this->CI->mymodel->selectWithQuery("
            SELECT id FROM endorse_campaign_logs
            WHERE id_campaign = '$id_campaign' AND date = '$today'
        ");
        $existing_id = !empty($existing) ? intval($existing[0]['id']) : 0;

        $yesterday = $this->CI->mymodel->selectWithQuery("
            SELECT * FROM endorse_campaign_logs
            WHERE id_campaign = '$id_campaign' AND date < '$today'
            ORDER BY date DESC LIMIT 1
        ");
        $yesterday = !empty($yesterday) ? $yesterday[0] : [];

        $totals = $this->CI->mymodel->selectWithQuery("
            SELECT SUM(total_cost) as total_cost, COUNT(id) as count_endorse,
                   SUM(likes) as likes, SUM(comment) as comment,
                   SUM(share_save) as share_save, SUM(views) as views, AVG(cpm) as cpm
            FROM endorse
            WHERE id_campaign = '$id_campaign' AND link_upload != '' AND status = 'Aktif'
        ");
        $totals = $totals[0];

        $logTotals = $this->CI->mymodel->selectWithQuery("
            SELECT SUM(likes) as likes, SUM(comment) as comment, SUM(share_save) as share_save,
                   SUM(views) as views, AVG(cpm) as cpm,
                   SUM(likes_after) as likes_after, SUM(comment_after) as comment_after,
                   SUM(share_save_after) as share_save_after, SUM(views_after) as views_after,
                   AVG(cpm_after) as cpm_after,
                   SUM(likes_before) as likes_before, SUM(comment_before) as comment_before,
                   SUM(share_save_before) as share_save_before, SUM(views_before) as views_before,
                   AVG(cpm_before) as cpm_before
            FROM endorse_logs
            WHERE id_campaign = '$id_campaign'
        ");
        $logTotals = $logTotals[0];

        $count_total      = $this->count_endorse($id_campaign, '');
        $count_active     = $this->count_endorse($id_campaign, "AND status = 'Aktif'");
        $count_processed  = $this->count_endorse($id_campaign, "AND status = 'Aktif' AND link_upload != ''");
        $count_inf_total  = $this->count_distinct_influencer($id_campaign, '');
        $count_inf_active = $this->count_distinct_influencer($id_campaign, "AND status = 'Aktif'");
        $count_inf_proc   = $this->count_distinct_influencer($id_campaign, "AND status = 'Aktif' AND link_upload != ''");

        $logRow = [
            'id_campaign'   => strval($id_campaign),
            'total_cost'    => strval(doubleval($totals['total_cost'])),
            'date'          => $today,
        ];
        foreach ($logTotals as $k => $v) {
            $logRow[$k] = strval(doubleval($v));
        }

        $logRow['ce_now']             = strval($count_total);
        $logRow['ce_active_now']      = strval($count_active);
        $logRow['ce_processed_now']   = strval($count_processed);
        $logRow['ci_now']             = strval($count_inf_total);
        $logRow['ci_active_now']      = strval($count_inf_active);
        $logRow['ci_processed_now']   = strval($count_inf_proc);

        $logRow['ce_before']           = strval(intval($yesterday['ce_now'] ?? 0));
        $logRow['ce_active_before']    = strval(intval($yesterday['ce_active_now'] ?? 0));
        $logRow['ce_processed_before'] = strval(intval($yesterday['ce_processed_now'] ?? 0));
        $logRow['ci_before']           = strval(intval($yesterday['ci_now'] ?? 0));
        $logRow['ci_active_before']    = strval(intval($yesterday['ci_active_now'] ?? 0));
        $logRow['ci_processed_before'] = strval(intval($yesterday['ci_processed_now'] ?? 0));

        $logRow['ce_after']           = strval($count_total);
        $logRow['ce_active_after']    = strval($count_active);
        $logRow['ce_processed_after'] = strval($count_processed);
        $logRow['ci_after']           = strval($count_inf_total);
        $logRow['ci_active_after']    = strval($count_inf_active);
        $logRow['ci_processed_after'] = strval($count_inf_proc);

        if ($existing_id) {
            $logRow['updated_at'] = date('Y-m-d H:i:s');
            $logRow['updated_by'] = strval($user_id);
            $db->update('endorse_campaign_logs', $logRow, ['id' => $existing_id]);
        } else {
            $logRow['created_at'] = date('Y-m-d H:i:s');
            $logRow['created_by'] = strval($user_id);
            $db->insert('endorse_campaign_logs', $logRow);
        }

        $campaignUpdate = [
            'total_cost'                 => strval(doubleval($totals['total_cost'])),
            'count_endorse'              => strval($count_total),
            'count_endorse_active'       => strval($count_active),
            'count_endorse_processed'    => strval($count_processed),
            'count_influencer'           => strval($count_inf_total),
            'count_influencer_active'    => strval($count_inf_active),
            'count_influencer_processed' => strval($count_inf_proc),
            'likes'                      => strval(doubleval($totals['likes'])),
            'comment'                    => strval(doubleval($totals['comment'])),
            'share_save'                 => strval(doubleval($totals['share_save'])),
            'views'                      => strval(doubleval($totals['views'])),
            'cpm'                        => strval(doubleval($totals['cpm'])),
            'updated_at'                 => date('Y-m-d H:i:s'),
            'updated_by'                 => strval($user_id),
        ];
        $db->update('endorse_campaign', $campaignUpdate, ['id' => $id_campaign]);
    }

    private function load_prev_stats(int $id_endorse, string $today): array
    {
        $rows = $this->CI->mymodel->selectWithQuery("
            SELECT likes_after, comment_after, share_save_after, views_after
            FROM endorse_logs
            WHERE id_endorse = '$id_endorse' AND date < '$today' AND views_after > 0
            ORDER BY date DESC LIMIT 1
        ");
        return !empty($rows) ? $rows[0] : [];
    }

    private function lookup_follower(int $id_influencer): int
    {
        if ($id_influencer <= 0) return 0;
        $rows = $this->CI->mymodel->selectWithQuery("SELECT follower FROM influencer WHERE id = '$id_influencer'");
        return !empty($rows) ? intval($rows[0]['follower']) : 0;
    }

    private function count_endorse(int $id_campaign, string $extraWhere): int
    {
        $rows = $this->CI->mymodel->selectWithQuery("
            SELECT COUNT(id) as c FROM endorse WHERE id_campaign = '$id_campaign' $extraWhere
        ");
        return !empty($rows) ? intval($rows[0]['c']) : 0;
    }

    private function count_distinct_influencer(int $id_campaign, string $extraWhere): int
    {
        $rows = $this->CI->mymodel->selectWithQuery("
            SELECT COUNT(DISTINCT influencer) as c FROM endorse WHERE id_campaign = '$id_campaign' $extraWhere
        ");
        return !empty($rows) ? intval($rows[0]['c']) : 0;
    }
}
