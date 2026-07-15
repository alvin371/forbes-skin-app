<?php
defined('BASEPATH') or exit('No direct script access allowed');

class EndorseRefreshQueueService
{
    const DEFAULT_PRIORITY = 10;
    const DEFAULT_MAX_ATTEMPTS = 3;
    const INSERT_CHUNK_SIZE = 250;

    protected $CI;
    protected $db;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->load->model('mymodel');
        $this->db = $this->CI->db;
    }

    public function enqueueCampaign(int $id_campaign, int $user_id, array $ids = []): array
    {
        if ($id_campaign <= 0) {
            return [
                'status' => false,
                'msg' => 'Campaign tidak valid.',
                'enqueued' => 0,
                'skipped_duplicates' => 0,
                'id_campaign' => $id_campaign,
            ];
        }

        $extra = '';
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!empty($ids)) {
            $extra = ' AND id IN (' . implode(',', $ids) . ')';
        }

        $rows = $this->CI->mymodel->selectWithQuery("
            SELECT id, id_campaign, platform, link_upload
            FROM endorse
            WHERE id_campaign = '" . intval($id_campaign) . "'
              AND status = 'Aktif' AND status_campaign = 'Aktif'
              AND link_upload != ''
              $extra
        ");

        if (empty($rows)) {
            return [
                'status' => false,
                'msg' => 'Tidak ada konten aktif yang bisa direfresh.',
                'enqueued' => 0,
                'skipped_duplicates' => 0,
                'id_campaign' => $id_campaign,
            ];
        }

        $stats = $this->enqueueRows($rows, $user_id);
        $msg = $this->buildEnqueueMessage($stats['enqueued'], $stats['skipped_duplicates'], $stats['excluded_known_url']);

        return [
            'status' => true,
            'msg' => $msg,
            'enqueued' => $stats['enqueued'],
            'skipped_duplicates' => $stats['skipped_duplicates'],
            'excluded_known_url' => $stats['excluded_known_url'],
            'count' => count($rows),
            'id_campaign' => $id_campaign,
        ];
    }

    public function enqueueAllActive(int $user_id): array
    {
        // Refresh Semua rule: sync only ACTIVE campaigns and, within them, only ACTIVE
        // posts. Never enqueue anything under a "Tidak Aktif" campaign.
        //   c.status = 'Aktif'          — the campaign's own status (source of truth)
        //   e.status = 'Aktif'          — the post's status
        //   e.status_campaign = 'Aktif' — denormalized campaign flag on the row (guards
        //                                 against any drift vs c.status)
        $rows = $this->CI->mymodel->selectWithQuery("
            SELECT e.id, e.id_campaign, e.platform, e.link_upload
            FROM endorse e
            INNER JOIN endorse_campaign c ON c.id = e.id_campaign
            WHERE c.status = 'Aktif'
              AND e.status = 'Aktif'
              AND e.status_campaign = 'Aktif'
              AND e.link_upload != ''
            ORDER BY e.id_campaign ASC, e.id ASC
        ");

        if (empty($rows)) {
            return [
                'status' => true,
                'msg' => 'Tidak ada konten aktif yang bisa direfresh.',
                'campaign_count' => 0,
                'candidate_count' => 0,
                'enqueued' => 0,
                'skipped_duplicates' => 0,
                'excluded_known_url' => 0,
            ];
        }

        $stats = $this->enqueueRows($rows, $user_id);

        return [
            'status' => true,
            'msg' => $this->buildEnqueueMessage($stats['enqueued'], $stats['skipped_duplicates'], $stats['excluded_known_url']),
            'campaign_count' => $stats['campaign_count'],
            'candidate_count' => count($rows),
            'enqueued' => $stats['enqueued'],
            'skipped_duplicates' => $stats['skipped_duplicates'],
            'excluded_known_url' => $stats['excluded_known_url'],
        ];
    }

    /**
     * Enqueue a single frozen-snapshot job (initial baseline or final) for one endorse.
     * High priority (default 50) so it jumps the daily backlog. Purpose-scoped dedup.
     * No-ops for placeholder platforms (metrics entered manually) and for an already
     * captured initial baseline.
     */
    public function enqueueSnapshot(int $id_endorse, string $purpose, int $user_id, int $priority = 50): array
    {
        $purpose = ($purpose === 'final') ? 'final' : 'initial';

        if ($id_endorse <= 0) {
            return ['status' => false, 'msg' => 'Endorse tidak valid.', 'enqueued' => 0];
        }

        $row = $this->CI->mymodel->selectDataOne('endorse', ['id' => $id_endorse]);
        if (empty($row)) {
            return ['status' => false, 'msg' => 'Endorse tidak ditemukan.', 'enqueued' => 0];
        }

        $platform = strval($row['platform'] ?? '');
        $link = trim((string) ($row['link_upload'] ?? ''));

        if ($link === '') {
            return ['status' => false, 'msg' => 'Link konten kosong, snapshot dilewati.', 'enqueued' => 0];
        }

        $this->CI->load->helper('social_platform');
        if (!is_auto_fetch_platform($platform)) {
            // Placeholder platform: metrics are entered manually, nothing to enqueue.
            return ['status' => false, 'msg' => 'Platform belum mendukung auto-fetch.', 'enqueued' => 0, 'placeholder' => true];
        }

        // Initial baseline must stay frozen — skip if already captured.
        if ($purpose === 'initial' && !empty($row['initial_fetched_at'])) {
            return ['status' => false, 'msg' => 'Baseline awal sudah diambil.', 'enqueued' => 0];
        }

        // Purpose-scoped dedup.
        $active = $this->loadActiveEndorseIds([$id_endorse], $purpose);
        if (isset($active[$id_endorse])) {
            return ['status' => false, 'msg' => 'Snapshot sudah ada di antrian.', 'enqueued' => 0];
        }

        $priority = $priority > 0 ? $priority : 50;
        $this->db->insert('endorse_refresh_queue', [
            'id_endorse'   => $id_endorse,
            'id_campaign'  => intval($row['id_campaign'] ?? 0),
            'platform'     => $platform,
            'purpose'      => $purpose,
            'link_upload'  => $link,
            'status'       => 'pending',
            'priority'     => $priority,
            'attempts'     => 0,
            'max_attempts' => self::DEFAULT_MAX_ATTEMPTS,
            'enqueued_by'  => $user_id,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        return ['status' => true, 'msg' => 'Snapshot ditambahkan ke antrian.', 'enqueued' => 1, 'purpose' => $purpose];
    }

    /**
     * Reconcile sweep: enqueue a 'final' snapshot for any auto-fetch endorse that is
     * Completed but has no final snapshot yet (covers enqueues lost after the row
     * update committed). Safe to run repeatedly — dedup + frozen guards prevent dupes.
     */
    public function enqueuePendingFinals(int $user_id, int $limit = 200): array
    {
        $limit = $limit > 0 ? $limit : 200;
        $this->CI->load->helper('social_platform');

        $rows = $this->CI->mymodel->selectWithQuery("
            SELECT id, platform
            FROM endorse
            WHERE optimization_status = 'Completed'
              AND final_fetched_at IS NULL
              AND link_upload != ''
            ORDER BY id ASC
            LIMIT $limit
        ");

        $enqueued = 0;
        $skipped = 0;
        foreach ($rows as $row) {
            if (!is_auto_fetch_platform($row['platform'])) {
                $skipped++;
                continue;
            }
            $res = $this->enqueueSnapshot(intval($row['id']), 'final', $user_id);
            if (!empty($res['enqueued'])) {
                $enqueued++;
            } else {
                $skipped++;
            }
        }

        return [
            'status'   => true,
            'msg'      => "$enqueued final snapshot dijadwalkan, $skipped dilewati.",
            'enqueued' => $enqueued,
            'skipped'  => $skipped,
        ];
    }

    public function cloneFailedRows(array $queueIds, int $user_id): array
    {
        $queueIds = array_values(array_unique(array_filter(array_map('intval', $queueIds))));
        if (empty($queueIds)) {
            return ['status' => false, 'msg' => 'Tidak ada baris dipilih.', 'updated' => 0];
        }

        $idList = implode(',', $queueIds);
        $rows = $this->CI->mymodel->selectWithQuery("
            SELECT id, id_endorse, id_campaign, platform, link_upload, priority, max_attempts
            FROM endorse_refresh_queue
            WHERE id IN ($idList) AND status = 'failed'
        ");

        if (empty($rows)) {
            return ['status' => false, 'msg' => 'Tidak ada baris gagal yang bisa dijadwalkan ulang.', 'updated' => 0];
        }

        $active = $this->loadActiveEndorseIds(array_map(function ($row) {
            return intval($row['id_endorse']);
        }, $rows));

        $now = date('Y-m-d H:i:s');
        $batch = [];
        $skipped = 0;

        foreach ($rows as $row) {
            $id_endorse = intval($row['id_endorse']);
            if (isset($active[$id_endorse])) {
                $skipped++;
                continue;
            }

            $batch[] = [
                'id_endorse' => $id_endorse,
                'id_campaign' => intval($row['id_campaign']),
                'platform' => strval($row['platform']),
                'link_upload' => strval($row['link_upload']),
                'status' => 'pending',
                'priority' => intval($row['priority']) > 0 ? intval($row['priority']) : self::DEFAULT_PRIORITY,
                'attempts' => 0,
                'max_attempts' => intval($row['max_attempts']) > 0 ? intval($row['max_attempts']) : self::DEFAULT_MAX_ATTEMPTS,
                'enqueued_by' => $user_id,
                'retry_source_id' => intval($row['id']),
                'created_at' => $now,
            ];
        }

        if (!empty($batch)) {
            $this->db->insert_batch('endorse_refresh_queue', $batch);
        }

        return [
            'status' => true,
            'msg' => count($batch) . ' baris dijadwalkan ulang.' . ($skipped > 0 ? " $skipped dilewati karena masih aktif di antrian." : ''),
            'updated' => count($batch),
            'skipped_duplicates' => $skipped,
        ];
    }

    public function clearAll(): array
    {
        $attemptRows = $this->CI->mymodel->selectWithQuery("SELECT COUNT(*) AS c FROM endorse_refresh_queue_attempts");
        $queueRows = $this->CI->mymodel->selectWithQuery("SELECT COUNT(*) AS c FROM endorse_refresh_queue");
        $attemptCount = !empty($attemptRows) ? intval($attemptRows[0]['c']) : 0;
        $queueCount = !empty($queueRows) ? intval($queueRows[0]['c']) : 0;

        $this->db->trans_start();
        $this->db->query("DELETE FROM endorse_refresh_queue_attempts");
        $this->db->query("DELETE FROM endorse_refresh_queue");
        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            return [
                'status' => false,
                'msg' => 'Gagal menghapus data antrian.',
                'deleted_queue' => 0,
                'deleted_attempts' => 0,
            ];
        }

        return [
            'status' => true,
            'msg' => $queueCount . ' data antrian dan ' . $attemptCount . ' riwayat percobaan dihapus.',
            'deleted_queue' => $queueCount,
            'deleted_attempts' => $attemptCount,
        ];
    }

    /**
     * Release rows whose worker claimed them but never finished: reset stale
     * `processing` rows back to `pending` (and mark their open attempt rows as
     * `retrying`) so the cron picks them up again. Shared by the worker (run on
     * every invocation, before the rate caps) and the manual "Reset Macet" button.
     */
    public function resetStuck(int $staleMinutes = 5): array
    {
        $staleMinutes = max(1, intval($staleMinutes));
        $now = date('Y-m-d H:i:s');

        $this->db->query("
            UPDATE endorse_refresh_queue_attempts a
            INNER JOIN endorse_refresh_queue q ON q.id = a.queue_id
            SET a.status = 'retrying',
                a.error_class = 'transient',
                a.error_message = 'Worker stalled; item returned to pending queue',
                a.finished_at = '$now'
            WHERE a.status = 'processing'
              AND q.status = 'processing'
              AND q.started_at < (NOW() - INTERVAL $staleMinutes MINUTE)
        ");

        $this->db->query("
            UPDATE endorse_refresh_queue
            SET status = 'pending', worker_id = NULL, started_at = NULL, claimed_at = NULL
            WHERE status = 'processing'
              AND started_at < (NOW() - INTERVAL $staleMinutes MINUTE)
        ");
        $reset = $this->db->affected_rows();

        return [
            'status'      => true,
            'reset_count' => $reset,
            'msg'         => $reset > 0
                ? "$reset item macet dikembalikan ke antrian (menunggu)."
                : 'Tidak ada item macet untuk direset.',
        ];
    }

    public function computeHealth(int $id_campaign = 0, int $staleMinutes = 10): array
    {
        $where = '';
        if ($id_campaign > 0) {
            $where = " AND id_campaign = '" . intval($id_campaign) . "'";
        }

        $summaryRows = $this->CI->mymodel->selectWithQuery("
            SELECT status, COUNT(*) AS c
            FROM endorse_refresh_queue
            WHERE status IN ('pending','processing')
            $where
            GROUP BY status
        ");

        $pending = 0;
        $processing = 0;
        foreach ($summaryRows as $row) {
            if ($row['status'] === 'pending') {
                $pending = intval($row['c']);
            }
            if ($row['status'] === 'processing') {
                $processing = intval($row['c']);
            }
        }

        $metaRows = $this->CI->mymodel->selectWithQuery("
            SELECT
                MIN(CASE WHEN status = 'pending' THEN created_at END) AS oldest_pending_at,
                MAX(CASE WHEN status IN ('completed','failed') THEN completed_at END) AS last_completed_at,
                MAX(CASE WHEN status = 'processing' THEN started_at END) AS last_started_at
            FROM endorse_refresh_queue
            WHERE 1 = 1
            $where
        ");
        $meta = !empty($metaRows) ? $metaRows[0] : [];

        $oldestPendingAt = $meta['oldest_pending_at'] ?? null;
        $lastCompletedAt = $meta['last_completed_at'] ?? null;
        $lastStartedAt = $meta['last_started_at'] ?? null;
        $lastActivityAt = $lastStartedAt ?: $lastCompletedAt;
        $isStalled = false;

        if ($pending > 0 && $processing === 0) {
            if (empty($lastActivityAt) || strtotime($lastActivityAt) < strtotime('-' . intval($staleMinutes) . ' minutes')) {
                $isStalled = true;
            }
        }

        $stall = $isStalled ? $this->diagnoseStall() : null;

        return [
            'active_total' => $pending + $processing,
            'pending_total' => $pending,
            'processing_total' => $processing,
            'oldest_pending_at' => $oldestPendingAt,
            'last_completed_at' => $lastCompletedAt,
            'last_started_at' => $lastStartedAt,
            'is_stalled' => $isStalled,
            'stall_reason' => $stall['reason'] ?? null,
            'stall_label' => $stall['label'] ?? null,
        ];
    }

    /**
     * Explain WHY the queue is stalled, using the same DB signals the worker
     * (Api_v2::cronjob_endorse_refresh) checks — so the banner is accurate
     * without reading server logs. Mirrors the worker's daily/per-minute cap
     * queries and falls back to the most recent attempt error.
     */
    protected function diagnoseStall(): array
    {
        $dailyCap = intval(env('ENDORSE_REFRESH_DAILY_CAP', 0));
        $ratePerMin = intval(env('ENDORSE_REFRESH_RATE_PER_MIN', 0));

        if ($dailyCap > 0) {
            $startOfDay = date('Y-m-d') . ' 00:00:00';
            $row = $this->CI->mymodel->selectWithQuery("
                SELECT COUNT(*) AS c FROM endorse_refresh_queue_attempts
                WHERE started_at >= '$startOfDay'
            ");
            $usedToday = intval($row[0]['c'] ?? 0);
            if ($usedToday >= $dailyCap) {
                return ['reason' => 'daily_cap', 'label' => "batas harian tercapai ($usedToday/$dailyCap)"];
            }
        }

        if ($ratePerMin > 0) {
            $row = $this->CI->mymodel->selectWithQuery("
                SELECT COUNT(*) AS c FROM endorse_refresh_queue_attempts
                WHERE started_at >= (NOW() - INTERVAL 60 SECOND)
            ");
            $usedMinute = intval($row[0]['c'] ?? 0);
            if ($usedMinute >= $ratePerMin) {
                return ['reason' => 'rate_cap', 'label' => "batas per-menit tercapai ($usedMinute/$ratePerMin)"];
            }
        }

        $row = $this->CI->mymodel->selectWithQuery("
            SELECT error_class, COUNT(*) AS c
            FROM endorse_refresh_queue_attempts
            WHERE status IN ('failed','retrying')
              AND started_at >= (NOW() - INTERVAL 15 MINUTE)
            GROUP BY error_class
            ORDER BY c DESC
            LIMIT 1
        ");
        if (!empty($row)) {
            $cls = $row[0]['error_class'] ?: 'unknown';
            return ['reason' => 'upstream_error', 'label' => "error upstream: $cls"];
        }

        return ['reason' => 'idle_worker', 'label' => 'worker tidak berjalan (cek cron)'];
    }

    protected function loadActiveEndorseIds(array $endorseIds, string $purpose = 'daily'): array
    {
        $endorseIds = array_values(array_unique(array_filter(array_map('intval', $endorseIds))));
        if (empty($endorseIds)) {
            return [];
        }

        // Dedup is purpose-scoped: a daily, an initial and a final job for the same
        // endorse are distinct and must NOT swallow each other.
        $purpose = $this->db->escape($purpose);
        $idList = implode(',', $endorseIds);
        $existing = $this->CI->mymodel->selectWithQuery("
            SELECT id_endorse
            FROM endorse_refresh_queue
            WHERE id_endorse IN ($idList)
              AND purpose = $purpose
              AND status IN ('pending','processing')
        ");

        $active = [];
        foreach ($existing as $row) {
            $active[intval($row['id_endorse'])] = true;
        }

        return $active;
    }

    protected function enqueueRows(array $rows, int $user_id): array
    {
        $candidateIds = array_map(function ($row) {
            return intval($row['id']);
        }, $rows);

        $already = $this->loadActiveEndorseIds($candidateIds);
        $knownUrlIssues = $this->loadKnownUrlIssueEndorseIds($candidateIds);
        $campaigns = [];
        $now = date('Y-m-d H:i:s');
        $batch = [];
        $enqueued = 0;
        $skipped = 0;
        $excludedKnownUrl = 0;

        foreach ($rows as $row) {
            $campaigns[intval($row['id_campaign'])] = true;
            $id_endorse = intval($row['id']);

            if (isset($already[$id_endorse])) {
                $skipped++;
                continue;
            }

            if (isset($knownUrlIssues[$id_endorse])) {
                $excludedKnownUrl++;
                continue;
            }

            $batch[] = [
                'id_endorse' => $id_endorse,
                'id_campaign' => intval($row['id_campaign']),
                'platform' => strval($row['platform']),
                'link_upload' => strval($row['link_upload']),
                'status' => 'pending',
                'priority' => self::DEFAULT_PRIORITY,
                'attempts' => 0,
                'max_attempts' => self::DEFAULT_MAX_ATTEMPTS,
                'enqueued_by' => $user_id,
                'created_at' => $now,
            ];

            if (count($batch) >= self::INSERT_CHUNK_SIZE) {
                $this->db->insert_batch('endorse_refresh_queue', $batch);
                $enqueued += count($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            $this->db->insert_batch('endorse_refresh_queue', $batch);
            $enqueued += count($batch);
        }

        return [
            'campaign_count' => count($campaigns),
            'enqueued' => $enqueued,
            'skipped_duplicates' => $skipped,
            'excluded_known_url' => $excludedKnownUrl,
        ];
    }

    protected function buildEnqueueMessage(int $enqueued, int $skipped, int $excludedKnownUrl): string
    {
        $msg = $enqueued . ' konten ditambahkan ke antrian.';
        if ($skipped > 0) {
            $msg .= " $skipped sudah ada di antrian.";
        }
        if ($excludedKnownUrl > 0) {
            $msg .= " $excludedKnownUrl dilewati karena URL TikTok bermasalah.";
        }

        return $msg;
    }

    protected function loadKnownUrlIssueEndorseIds(array $endorseIds): array
    {
        $endorseIds = array_values(array_unique(array_filter(array_map('intval', $endorseIds))));
        if (empty($endorseIds)) {
            return [];
        }

        $idList = implode(',', $endorseIds);
        $rows = $this->CI->mymodel->selectWithQuery("
            SELECT latest.id_endorse
            FROM endorse_refresh_queue latest
            INNER JOIN (
                SELECT id_endorse, MAX(id) AS max_id
                FROM endorse_refresh_queue
                WHERE id_endorse IN ($idList)
                GROUP BY id_endorse
            ) picked ON picked.max_id = latest.id
            WHERE latest.status = 'failed'
              AND (
                latest.error_message LIKE '%Stats data tidak ditemukan%'
                OR latest.error_message LIKE '%url tidak ditemukan%'
              )
        ");

        $blocked = [];
        foreach ($rows as $row) {
            $blocked[intval($row['id_endorse'])] = true;
        }

        return $blocked;
    }

    /**
     * Atomically claim a batch of pending rows and return them ready to fetch.
     *
     * Extracted from Api_v2::cronjob_endorse_refresh so the per-minute cron AND the
     * long-lived Rust consumer (POST /api/endorse-refresh/claim) share ONE hardened
     * claim path: stale recovery, daily + per-minute rate caps, atomic single-UPDATE
     * claim, and one 'processing' attempt row per claimed item (= one upstream request,
     * which is what the rate caps count).
     *
     * Returns:
     *   ['status'=>true, 'claimed'=>N, 'worker_id'=>.., 'items'=>[ {fetch-ready item}.. ]]
     *   or, when a cap blocks the run:
     *   ['status'=>true, 'claimed'=>0, 'items'=>[], 'skipped'=>['reason'=>.., 'msg'=>..]]
     *
     * Each item carries everything both the fetch step and applyResults() need, plus the
     * per-item fetch hints (rescue_lane widens the timeout and forces hd for photo posts).
     *
     * @param array $opts limit, force, daily_cap, rate_per_min, stale_minutes
     */
    public function claimBatch(array $opts = []): array
    {
        $this->CI->load->library('template');
        $this->CI->load->library('endorse_sync');

        $limit = intval($opts['limit'] ?? env('ENDORSE_REFRESH_BATCH_SIZE', 40));
        if ($limit <= 0) {
            $limit = 40;
        } elseif ($limit > 500) {
            $limit = 500;
        }
        $force = !empty($opts['force']);
        $staleMinutes = intval($opts['stale_minutes'] ?? 5);
        if ($staleMinutes < 1) {
            $staleMinutes = 5;
        }

        // Recover stale rows from crashed/killed workers FIRST — before the caps below
        // can early-return. Otherwise orphaned 'processing' rows keep the per-minute
        // counter pinned, every run skips, and recovery never runs: the stall sustains
        // itself. Recovery is two cheap UPDATEs, safe to always run.
        $this->resetStuck($staleMinutes);

        $worker_id = uniqid('w_', true);

        // Daily request cap — protect the shared upstream budget. Counts this brand's own
        // attempt rows (each attempt = one request); each brand has its own DB. 0 = off.
        $dailyCap = intval($opts['daily_cap'] ?? env('ENDORSE_REFRESH_DAILY_CAP', 0));
        if (!$force && $dailyCap > 0) {
            $startOfDay = date('Y-m-d') . ' 00:00:00';
            $usedRow = $this->CI->mymodel->selectWithQuery("
                SELECT COUNT(*) AS c FROM endorse_refresh_queue_attempts
                WHERE started_at >= '$startOfDay'
            ");
            $usedToday = intval($usedRow[0]['c'] ?? 0);
            $remaining = $dailyCap - $usedToday;
            if ($remaining <= 0) {
                return [
                    'status' => true, 'claimed' => 0, 'items' => [], 'worker_id' => $worker_id,
                    'skipped' => [
                        'reason' => 'daily_cap', 'used' => $usedToday, 'cap' => $dailyCap,
                        'msg' => "Daily cap reached ($usedToday/$dailyCap) — skipping run",
                    ],
                ];
            }
            if ($remaining < $limit) {
                $limit = $remaining;
            }
        }

        // Per-minute rate cap — protect the shared upstream pool (e.g. 250 of a 500/min
        // limit). Counting attempts started in the last 60s bounds the combined rate of
        // all overlapping worker runs (cron ticks OR Rust claim calls). 0 = off.
        $ratePerMin = intval($opts['rate_per_min'] ?? env('ENDORSE_REFRESH_RATE_PER_MIN', 0));
        if (!$force && $ratePerMin > 0) {
            $usedRow = $this->CI->mymodel->selectWithQuery("
                SELECT COUNT(*) AS c FROM endorse_refresh_queue_attempts
                WHERE started_at >= (NOW() - INTERVAL 60 SECOND)
            ");
            $usedMinute = intval($usedRow[0]['c'] ?? 0);
            $remainingMinute = $ratePerMin - $usedMinute;
            if ($remainingMinute <= 0) {
                return [
                    'status' => true, 'claimed' => 0, 'items' => [], 'worker_id' => $worker_id,
                    'skipped' => [
                        'reason' => 'rate_per_min', 'used' => $usedMinute, 'cap' => $ratePerMin,
                        'msg' => "Per-minute rate cap reached ($usedMinute/$ratePerMin) — skipping run",
                    ],
                ];
            }
            if ($remainingMinute < $limit) {
                $limit = $remainingMinute;
            }
        }

        if ($limit <= 0) {
            return ['status' => true, 'claimed' => 0, 'items' => [], 'worker_id' => $worker_id];
        }

        $now = date('Y-m-d H:i:s');

        // Atomic claim (single UPDATE serialized by MySQL). `attempts ASC` after priority
        // drains never-tried rows before re-queued transient retries.
        $this->db->query("
            UPDATE endorse_refresh_queue
            SET status = 'processing', worker_id = '$worker_id', claimed_at = '$now', started_at = '$now'
            WHERE status = 'pending' AND worker_id IS NULL
            ORDER BY priority DESC, attempts ASC, created_at ASC
            LIMIT $limit
        ");
        $claimed = $this->db->affected_rows();
        if ($claimed <= 0) {
            return ['status' => true, 'claimed' => 0, 'items' => [], 'worker_id' => $worker_id];
        }

        $rows = $this->CI->mymodel->selectWithQuery("
            SELECT * FROM endorse_refresh_queue
            WHERE worker_id = '$worker_id' AND status = 'processing'
        ");

        // Prior attempt classes → rescue-lane detection (infra-stall rows get more headroom).
        $priorAttemptMap = [];
        $queueIds = array_map(function ($r) { return intval($r['id']); }, $rows);
        if (!empty($queueIds)) {
            $queueIdList = implode(',', $queueIds);
            $priorAttempts = $this->CI->mymodel->selectWithQuery("
                SELECT queue_id, error_class
                FROM endorse_refresh_queue_attempts
                WHERE queue_id IN ($queueIdList)
                  AND status IN ('retrying', 'failed')
                ORDER BY id DESC
            ");
            foreach ($priorAttempts as $pa) {
                $qid = intval($pa['queue_id'] ?? 0);
                if ($qid > 0 && !array_key_exists($qid, $priorAttemptMap)) {
                    $priorAttemptMap[$qid] = strval($pa['error_class'] ?? '');
                }
            }
        }

        // One 'processing' attempt row per claimed item, inserted up front so the rate
        // caps above see it immediately. finalize on outcome (applyResults); resetStuck
        // is the safety-net if the worker dies before finalizing.
        $attemptRows = [];
        foreach ($rows as $r) {
            $attemptRows[] = [
                'queue_id' => intval($r['id']),
                'attempt_no' => intval($r['attempts']) + 1,
                'worker_id' => $worker_id,
                'status' => 'processing',
                'started_at' => $now,
                'created_at' => $now,
            ];
        }
        if (!empty($attemptRows)) {
            $this->db->insert_batch('endorse_refresh_queue_attempts', $attemptRows);
        }

        $httpTimeout = intval(env('ENDORSE_REFRESH_HTTP_TIMEOUT', 30));
        if ($httpTimeout < 1) {
            $httpTimeout = 30;
        }

        $items = [];
        foreach ($rows as $r) {
            $qid = intval($r['id']);
            $priorClass = strval($priorAttemptMap[$qid] ?? '');
            $isRescue = ($priorClass === Endorse_sync::ERR_INFRA_STALL);
            $url = strval($r['link_upload']);
            $hd = ($isRescue && $this->CI->template->detect_tiktok_media_type_from_url($url) === 'photo') ? 1 : 0;
            $items[] = [
                'queue_id'     => $qid,
                'id_endorse'   => intval($r['id_endorse']),
                'platform'     => $r['platform'],
                'url'          => $url,
                'purpose'      => strval($r['purpose'] ?? 'daily'),
                'enqueued_by'  => intval($r['enqueued_by'] ?: 0),
                'attempts'     => intval($r['attempts']),
                'attempt_no'   => intval($r['attempts']) + 1,
                'max_attempts' => intval($r['max_attempts']),
                'worker_id'    => $worker_id,
                'rescue_lane'  => $isRescue,
                'timeout_sec'  => $isRescue ? max(45, $httpTimeout + 15) : $httpTimeout,
                'hd'           => $hd,
            ];
        }

        return ['status' => true, 'claimed' => $claimed, 'items' => $items, 'worker_id' => $worker_id];
    }

    /**
     * Apply fetch outcomes to claimed items: mark completed/retrying/failed/deferred,
     * finalize the attempt row with the item's REAL error, and roll up touched campaigns.
     *
     * Extracted from Api_v2::cronjob_endorse_refresh (step 5+6). Shared by the cron and by
     * POST /api/endorse-refresh/result. `$responses` is indexed identically to `$items`;
     * each element is a Template::get_social_media-shaped array (or {deferred:true}).
     *
     * Committing per item here (not at end of a batch) is what fixes the "retry never
     * logged" bug: the true error is recorded as it happens, never masked by resetStuck's
     * generic stall label after a 60s guillotine.
     */
    public function applyResults(array $items, array $responses): array
    {
        if (empty($items)) {
            return ['completed' => 0, 'failed' => 0, 'retrying' => 0, 'deferred' => 0, 'processed' => 0];
        }

        $this->CI->load->library('endorse_sync');

        $today = date('Y-m-d');
        $endorseIds = array_map(function ($it) { return intval($it['id_endorse']); }, $items);
        $endorseIdList = implode(',', array_map('intval', $endorseIds));
        $endorseRows = $this->CI->mymodel->selectWithQuery("
            SELECT * FROM endorse WHERE id IN ($endorseIdList)
        ");
        $endorseMap = [];
        foreach ($endorseRows as $er) {
            $endorseMap[intval($er['id'])] = $er;
        }
        $prevStatsMap = $this->CI->endorse_sync->load_prev_stats_batch($endorseIds, $today);

        $completed = 0; $failed = 0; $retrying = 0; $deferred = 0;
        $touched = [];

        foreach ($items as $i => $item) {
            $queue_id    = intval($item['queue_id']);
            $id_endorse  = intval($item['id_endorse']);
            $endorse     = $endorseMap[$id_endorse] ?? null;
            $attempts    = intval($item['attempts']) + 1;
            $maxAttempts = intval($item['max_attempts']);
            $worker_id   = strval($item['worker_id'] ?? '');
            $response    = $responses[$i] ?? ['status' => false, 'msg' => 'No response', 'data' => []];

            // Wall-clock deferral (cron only): return untouched, DELETE the up-front attempt
            // (a deferral is not a real request, so it must not burn the caps or spam history).
            if (!empty($response['deferred'])) {
                $this->db->update('endorse_refresh_queue', [
                    'status' => 'pending', 'worker_id' => null, 'started_at' => null, 'claimed_at' => null,
                ], ['id' => $queue_id]);
                $this->db->delete('endorse_refresh_queue_attempts', [
                    'queue_id' => $queue_id, 'attempt_no' => $attempts, 'worker_id' => $worker_id,
                ]);
                $deferred++;
                continue;
            }

            if (!$endorse) {
                $this->markQueueFailed($queue_id, $attempts, 'Endorse row no longer exists', Endorse_sync::ERR_PERMANENT, $worker_id);
                $failed++;
                continue;
            }

            $purpose = strval($item['purpose'] ?? 'daily');
            if ($purpose === 'daily') {
                $result = $this->CI->endorse_sync->apply(
                    $endorse, $response, intval($item['enqueued_by'] ?: 0), $prevStatsMap[$id_endorse] ?? null
                );
            } else {
                $result = $this->CI->endorse_sync->apply_snapshot(
                    $endorse, $response, $purpose, intval($item['enqueued_by'] ?: 0)
                );
            }

            if ($result['status']) {
                $completedAt = date('Y-m-d H:i:s');
                $this->db->update('endorse_refresh_queue', [
                    'status' => 'completed', 'attempts' => $attempts, 'error_message' => null,
                    'worker_id' => null, 'completed_at' => $completedAt,
                ], ['id' => $queue_id]);
                $this->finalizeQueueAttempt($queue_id, $attempts, $worker_id, 'completed', null, null, $completedAt);
                if ($purpose === 'daily') {
                    $touched[intval($endorse['id_campaign'])] = true;
                }
                $completed++;
                continue;
            }

            $errorClass = $result['error_class'] ?? Endorse_sync::ERR_TRANSIENT;
            $msg = $result['msg'] ?: 'Gagal';

            // Only genuinely unrecoverable classes fail immediately; transport/infra classes
            // retry up to max_attempts (one upstream outage must not drain the queue to failed).
            if (Endorse_sync::is_terminal_class($errorClass)) {
                $this->markQueueFailed($queue_id, $attempts, $msg, $errorClass, $worker_id);
                $failed++;
                continue;
            }

            if ($attempts >= $maxAttempts) {
                $this->markQueueFailed($queue_id, $attempts, "$msg (after $attempts attempts)", $errorClass, $worker_id);
                $failed++;
            } else {
                $finishedAt = date('Y-m-d H:i:s');
                $this->db->update('endorse_refresh_queue', [
                    'status' => 'pending', 'attempts' => $attempts, 'error_message' => $msg,
                    'worker_id' => null, 'started_at' => null, 'claimed_at' => null,
                ], ['id' => $queue_id]);
                $this->finalizeQueueAttempt($queue_id, $attempts, $worker_id, 'retrying', $errorClass, $msg, $finishedAt);
                $retrying++;
            }
        }

        foreach (array_keys($touched) as $cid) {
            $this->CI->endorse_sync->update_campaign_parent($cid, 0);
        }

        return [
            'completed' => $completed, 'failed' => $failed, 'retrying' => $retrying,
            'deferred' => $deferred, 'processed' => count($items),
        ];
    }

    protected function markQueueFailed(int $queue_id, int $attempts, string $msg, ?string $errorClass = null, ?string $worker_id = null): void
    {
        $completedAt = date('Y-m-d H:i:s');
        $this->db->update('endorse_refresh_queue', [
            'status'        => 'failed',
            'attempts'      => $attempts,
            'error_message' => $msg,
            'worker_id'     => null,
            'completed_at'  => $completedAt,
        ], ['id' => $queue_id]);
        $this->finalizeQueueAttempt($queue_id, $attempts, $worker_id, 'failed', $errorClass, $msg, $completedAt);
    }

    protected function finalizeQueueAttempt(int $queue_id, int $attemptNo, ?string $worker_id, string $status, ?string $errorClass, ?string $msg, string $finishedAt): void
    {
        $where = [
            'queue_id' => $queue_id,
            'attempt_no' => $attemptNo,
        ];
        if (!empty($worker_id)) {
            $where['worker_id'] = $worker_id;
        }
        $this->db->update('endorse_refresh_queue_attempts', [
            'status' => $status,
            'error_class' => $errorClass,
            'error_message' => $msg,
            'finished_at' => $finishedAt,
        ], $where);
    }
}
