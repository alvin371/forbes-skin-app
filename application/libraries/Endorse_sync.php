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
    const ERR_RATE_LIMIT = 'rate_limited';
    const ERR_INTERNAL  = 'internal';

    /**
     * Retry policy, single source of truth. Only genuinely unrecoverable classes
     * terminate a queue row. Everything else — transport/infra/config/transient — is
     * retried until max_attempts. A slow or briefly-unhealthy RapidAPI upstream must
     * never permanently fail the queue (that caused the 3-day stall). The infra and
     * config labels remain useful for diagnostics (diagnoseStall, monitoring), never routing.
     */
    public static function is_terminal_class(string $errorClass): bool
    {
        return $errorClass === self::ERR_PERMANENT
            || $errorClass === self::ERR_EMPTY;
    }

    /**
     * Ordering guard, single source of truth (unit-tested). An observation is stale when the
     * row already carries an observation timestamp that is newer than, or equal to, the
     * incoming one — so a late older/duplicate response cannot overwrite fresher data.
     * A missing/empty existing timestamp (first observation) or a missing incoming timestamp
     * is never treated as stale, preserving forward progress and legacy behaviour.
     *
     * Both values are 'Y-m-d H:i:s[.u]' in UTC; string comparison is correct for that format.
     */
    public static function isStaleObservation(string $existingObservedAt, string $incomingObservedAt): bool
    {
        $existingObservedAt = trim($existingObservedAt);
        $incomingObservedAt = trim($incomingObservedAt);
        if ($existingObservedAt === '' || $incomingObservedAt === '') {
            return false;
        }
        return $incomingObservedAt <= $existingObservedAt;
    }

    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->load->model('mymodel');
    }

    public function normalize_response(array $response, string $platform, string $url, string $source = 'unknown'): array
    {
        $response['status'] = !empty($response['status']);
        $response['msg'] = strval($response['msg'] ?? '');
        $response['error_class'] = strval($response['error_class'] ?? '');
        $response['reason_code'] = strval($response['reason_code'] ?? '');
        $response['stats_source'] = $source;
        $response['observed_at'] = strval($response['observed_at'] ?? gmdate('Y-m-d H:i:s') . '.000000');
        $response['data'] = is_array($response['data'] ?? null) ? $response['data'] : [];

        $response['data']['content_id'] = strval($response['data']['content_id'] ?? '');
        if ($response['data']['content_id'] === '' && strtolower($platform) === 'tiktok') {
            if (!empty($this->CI) && !empty($this->CI->template)) {
                $response['data']['content_id'] = strval($this->CI->template->extract_tiktok_content_id($url));
            } elseif (preg_match('#/(?:video|photo)/(\d+)#', $url, $match)) {
                $response['data']['content_id'] = strval($match[1]);
            }
        }
        $response['data']['media_type'] = strval($response['data']['media_type'] ?? '');
        if ($response['data']['media_type'] === '' && strtolower($platform) === 'tiktok') {
            if (!empty($this->CI) && !empty($this->CI->template)) {
                $response['data']['media_type'] = strval($this->CI->template->detect_tiktok_media_type_from_url($url));
            } else {
                $response['data']['media_type'] = stripos($url, '/photo/') !== false ? 'photo' : 'video';
            }
        }
        $response['data']['created_at'] = strval($response['data']['created_at'] ?? '');
        $response['data']['video_link'] = strval($response['data']['video_link'] ?? '');
        $response['data']['cover'] = strval($response['data']['cover'] ?? '');
        $response['data']['images'] = array_values(is_array($response['data']['images'] ?? null) ? $response['data']['images'] : []);

        $statsFields = [];
        if (is_array($response['stats_fields'] ?? null)) {
            foreach ($response['stats_fields'] as $field) {
                $field = strval($field);
                if (in_array($field, ['like', 'share', 'comment', 'collect', 'view'], true)) {
                    $statsFields[$field] = true;
                }
            }
        }

        foreach (['like', 'share', 'comment', 'collect', 'view'] as $field) {
            if (array_key_exists($field, $response['data'])) {
                $value = $response['data'][$field];
                if ($value === '' || $value === null) {
                    $response['data'][$field] = null;
                } else {
                    $response['data'][$field] = is_float($value + 0) ? (float) $value : intval($value);
                    $statsFields[$field] = true;
                }
            } else {
                $response['data'][$field] = null;
            }
        }

        $response['stats_fields'] = array_values(array_keys($statsFields));
        $response['stats_found'] = !empty($response['stats_fields']) || !empty($response['data']['content_id']);
        $response['stats_complete'] = count(array_intersect($response['stats_fields'], ['like', 'share', 'comment', 'collect', 'view'])) === 5;

        return $response;
    }

    protected function buildMetricSet(array $response, array $endorse, array $prevStats): array
    {
        $fields = array_values(array_map('strval', is_array($response['stats_fields'] ?? null) ? $response['stats_fields'] : []));
        $present = array_fill_keys($fields, true);

        $current = [
            'like' => intval($endorse['likes'] ?? ($prevStats['likes_after'] ?? 0)),
            'comment' => intval($endorse['comment'] ?? ($prevStats['comment_after'] ?? 0)),
            'share_save' => floatval($endorse['share_save'] ?? ($prevStats['share_save_after'] ?? 0)),
            'view' => intval($endorse['views'] ?? ($prevStats['views_after'] ?? 0)),
        ];
        $previous = [
            'like' => intval($prevStats['likes_after'] ?? 0),
            'comment' => intval($prevStats['comment_after'] ?? 0),
            'share_save' => floatval($prevStats['share_save_after'] ?? 0),
            'view' => intval($prevStats['views_after'] ?? 0),
        ];

        $incomingShareSave = null;
        if (isset($present['share']) || isset($present['collect'])) {
            $incomingShareSave = floatval($response['data']['share'] ?? 0) + floatval($response['data']['collect'] ?? 0);
            $current['share_save'] = $incomingShareSave;
        }
        if (isset($present['like'])) {
            $current['like'] = intval($response['data']['like']);
        }
        if (isset($present['comment'])) {
            $current['comment'] = intval($response['data']['comment']);
        }
        if (isset($present['view'])) {
            $current['view'] = max($previous['view'], intval($response['data']['view']));
        }

        return [
            'current' => $current,
            'previous' => $previous,
            'present' => $present,
            'completeness' => !empty($response['stats_complete']) ? 'complete' : 'partial',
        ];
    }

    protected function businessDateFromObservedAt(string $observedAt): string
    {
        $utc = new DateTimeZone('UTC');
        $businessTz = new DateTimeZone(strval(env('ENDORSE_REFRESH_BUSINESS_TIMEZONE', 'Asia/Jakarta')));
        $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $observedAt, $utc);
        if (!$dt) {
            $dt = new DateTimeImmutable('now', $utc);
        }

        return $dt->setTimezone($businessTz)->format('Y-m-d');
    }

    protected function applyObservationMetadata(string $table, array &$row, array $response): void
    {
        if ($this->CI->db->field_exists('stats_completeness', $table)) {
            $row['stats_completeness'] = !empty($response['stats_complete']) ? 'complete' : 'partial';
        }
        if ($this->CI->db->field_exists('stats_fields', $table)) {
            $row['stats_fields'] = json_encode(array_values($response['stats_fields'] ?? []));
        }
        if ($this->CI->db->field_exists('stats_source', $table)) {
            $row['stats_source'] = strval($response['stats_source'] ?? '');
        }
        if ($this->CI->db->field_exists('stats_observed_at', $table)) {
            $row['stats_observed_at'] = strval($response['observed_at'] ?? '');
        }
    }

    /**
     * Classify a Template::get_social_media response for retry decisions.
     */
    public function classify_response(array $response, string $platform, string $url): array
    {
        $response = $this->normalize_response($response, $platform, $url);

        if (!empty($response['status']) && !empty($response['stats_found'])) {
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
        $response = $this->normalize_response($response, $platform, $url, strval($response['stats_source'] ?? 'queue'));

        $classification = $this->classify_response($response, $platform, $url);

        if ($classification['class'] !== self::ERR_OK) {
            return [
                'status'      => false,
                'error_class' => $classification['class'],
                'msg'         => $classification['msg'],
            ];
        }

        $db = $this->CI->db;
        $incomingObservedAt = strval($response['observed_at']);
        $today = $this->businessDateFromObservedAt($incomingObservedAt);
        $id_endorse = intval($endorse['id']);

        if ($prev_stats === null) {
            $prev_stats = $this->load_prev_stats($id_endorse, $today);
        }

        $metrics = $this->buildMetricSet($response, $endorse, $prev_stats);
        $stats = [
            'likes' => $metrics['current']['like'],
            'comment' => $metrics['current']['comment'],
            'share_save' => $metrics['current']['share_save'],
            'views' => $metrics['current']['view'],
        ];
        $prev_likes      = $metrics['previous']['like'];
        $prev_comment    = $metrics['previous']['comment'];
        $prev_share_save = $metrics['previous']['share_save'];
        $prev_views      = $metrics['previous']['view'];

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
        $this->applyObservationMetadata('endorse', $endorseUpdate, $response);
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
        if ($platform === 'Threads' && $db->field_exists('threads_media_id', 'endorse') && !empty($response['data']['content_id'])) {
            $endorseUpdate['threads_media_id'] = strval($response['data']['content_id']);
        }

        // LOGICAL-ORDER guard. Freshness is decided by `stats_observation_seq` — the monotonic
        // queue-row id, STABLE across all attempts/retries of one refresh generation (so a
        // retry cannot outrank a newer job) and strictly greater for a genuinely newer refresh.
        // The check is atomic (lives in the UPDATE's WHERE); affected-row count classifies the
        // outcome. `$applyStats` decides whether the stat columns are (re)written; duplicates
        // still fall through to the log upsert so a crash BEFORE endorse_logs recovers on retry.
        $hasSeqCol   = $db->field_exists('stats_observation_seq', 'endorse');
        $incomingSeq = (array_key_exists('observation_seq', $response) && $response['observation_seq'] !== null && $response['observation_seq'] !== '')
            ? intval($response['observation_seq'])
            : null;
        $outcome = 'applied_newer';

        if ($hasSeqCol && $incomingSeq === null) {
            // No queue-derived order. Two legitimate cases:
            //  - interactive/authoritative sync (manual "Refresh data" button): the user asked
            //    for CURRENT data now → write the stats but DO NOT advance stats_observation_seq,
            //    so a later queue generation (whatever its id) still applies and ordering stays
            //    intact. This is the only sanctioned way to reach apply without an order.
            //  - first-ever observation on a row that has no order yet → apply.
            // Anything else (a queue-style caller that failed to supply an order over ORDERED
            // data) is a contract violation and must not overwrite fresher data.
            $authoritative = !empty($response['observation_authoritative']);
            $curSeq = $this->currentObservationSeq($id_endorse);
            if ($curSeq !== null && !$authoritative) {
                $this->emitContractError($id_endorse, 'missing_observation_seq');
                return ['status' => true, 'error_class' => self::ERR_OK, 'msg' => 'contract_error: missing observation order', 'outcome' => 'contract_error'];
            }
            $db->update('endorse', $endorseUpdate, ['id' => $id_endorse]);
            $outcome = ($authoritative && $curSeq !== null) ? 'applied_authoritative' : 'applied_newer';
        } elseif ($hasSeqCol) {
            $endorseUpdate['stats_observation_seq'] = $incomingSeq;
            $db->where('id', $id_endorse);
            $db->where('(stats_observation_seq IS NULL OR stats_observation_seq < ' . intval($incomingSeq) . ')', null, false);
            $db->update('endorse', $endorseUpdate);
            if (intval($db->affected_rows()) < 1) {
                // 0 rows changed → classify precisely (never one undocumented bucket).
                $curSeq = $this->currentObservationSeq($id_endorse);
                if ($curSeq !== null && $curSeq > $incomingSeq) {
                    return ['status' => true, 'error_class' => self::ERR_OK, 'msg' => 'stale observation skipped', 'outcome' => 'stale'];
                }
                // equal sequence → duplicate delivery. The stat columns are already current, so
                // the endorse UPDATE was a no-op; we STILL fall through to the log upsert
                // (idempotent via the unique (id_endorse,date) key) so a crash before
                // endorse_logs is repaired on retry.
                $outcome = 'duplicate';
            }
        } else {
            // Legacy schema without the sequence column → previous unguarded behaviour.
            $db->update('endorse', $endorseUpdate, ['id' => $id_endorse]);
        }

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
        $this->applyObservationMetadata('endorse_logs', $logRow, $response);

        if ($hasSeqCol && $incomingSeq !== null && $db->field_exists('stats_observation_seq', 'endorse_logs')) {
            $logRow['stats_observation_seq'] = $incomingSeq;
        }

        if ($existing_log_id) {
            // A duplicate delivery (same observation order) must not rewrite an existing log —
            // the endorse row was left unchanged, so the already-written log stays consistent.
            // Only applied_newer refreshes update the log.
            if ($outcome !== 'duplicate') {
                $logRow['updated_at'] = date('Y-m-d H:i:s');
                $logRow['updated_by'] = strval($user_id);
                $db->update('endorse_logs', $logRow, ['id' => $existing_log_id]);
            }
        } else {
            // Missing log (first apply, or a crash before the log write on a prior attempt) →
            // insert. This is what makes the whole apply crash-recoverable.
            $logRow['created_at'] = date('Y-m-d H:i:s');
            $logRow['created_by'] = strval($user_id);
            $db->insert('endorse_logs', $logRow);
        }

        return [
            'status'      => true,
            'error_class' => self::ERR_OK,
            'msg'         => 'OK',
            'outcome'     => $outcome,
        ];
    }

    /** Authoritative current logical observation sequence for a row (null if unset). */
    private function currentObservationSeq(int $id_endorse): ?int
    {
        $rows = $this->CI->mymodel->selectWithQuery(
            "SELECT stats_observation_seq FROM endorse WHERE id = '$id_endorse'"
        );
        if (empty($rows) || !array_key_exists('stats_observation_seq', $rows[0]) || $rows[0]['stats_observation_seq'] === null) {
            return null;
        }
        return intval($rows[0]['stats_observation_seq']);
    }

    /** Emit a structured contract-error metric (no secrets). */
    private function emitContractError(int $id_endorse, string $reason): void
    {
        error_log(json_encode([
            'evt'        => 'endorse_apply_contract_error',
            'reason'     => $reason,
            'id_endorse' => $id_endorse,
        ], JSON_UNESCAPED_SLASHES));
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
        $response = $this->normalize_response($response, $platform, $url, strval($response['stats_source'] ?? 'queue'));

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
        $present = array_fill_keys(array_values($response['stats_fields'] ?? []), true);

        // NOTE: the API exposes "save" under the key `collect`.
        $metrics = [
            'like'    => isset($present['like']) ? intval($response['data']['like']) : ($endorse['like_' . $purpose] ?? null),
            'comment' => isset($present['comment']) ? intval($response['data']['comment']) : ($endorse['comment_' . $purpose] ?? null),
            'share'   => isset($present['share']) ? intval($response['data']['share']) : ($endorse['share_' . $purpose] ?? null),
            'save'    => isset($present['collect']) ? intval($response['data']['collect']) : ($endorse['save_' . $purpose] ?? null),
            'view'    => isset($present['view']) ? intval($response['data']['view']) : ($endorse['view_' . $purpose] ?? null),
        ];

        $update = [
            'updated_at' => $now,
            'updated_by' => strval($user_id),
        ];
        $this->applyObservationMetadata('endorse', $update, $response);

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
        if ($platform === 'Threads' && $db->field_exists('threads_media_id', 'endorse') && !empty($response['data']['content_id'])) {
            $update['threads_media_id'] = strval($response['data']['content_id']);
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
