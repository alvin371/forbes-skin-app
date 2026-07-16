<?php
defined('BASEPATH') or exit('No direct script access allowed');

class EndorseRefreshV2Coordinator
{
    const CONTRACT_VERSION = 2;
    const PROVIDER_KEY_RAPIDAPI = 'rapidapi';
    const OWNER_CRON = 'cron';
    const OWNER_RUST = 'rust';
    const MAX_RESULT_BATCH = 100;
    const MAX_FALLBACK_CACHE_BYTES = 16384;

    /** @var CI_Controller */
    protected $CI;
    protected $db;
    protected $tableExistsCache = [];

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->load->model('mymodel');
        $this->CI->load->library('template');
        $this->CI->load->library('endorse_sync');
        $this->db = $this->CI->db;
        $this->seedHealthRows();
    }

    public static function generateWorkerUuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }

    public static function isUuidV4(string $value): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            trim($value)
        );
    }

    public static function deterministicRetryDelaySeconds(int $queueId, int $attemptNo, int $baseSeconds = 60): int
    {
        $baseSeconds = max(1, min(3600, $baseSeconds));
        $attemptNo = max(1, $attemptNo);
        $base = $baseSeconds * (2 ** min(10, max(0, $attemptNo - 1)));
        $fraction = ((crc32($queueId . ':' . $attemptNo) & 0xffff) / 65535);
        $multiplier = 0.8 + ($fraction * 0.4);

        return (int) max(1, round($base * $multiplier));
    }

    public function runtimeState(): array
    {
        if (!$this->tableExists('endorse_refresh_runtime_control')) {
            return [
                'contract_state' => 'legacy',
                'owner_state' => 'cron',
                'generation' => 0,
            ];
        }

        $row = $this->singleRow("
            SELECT `contract_state`, `owner_state`, `generation`
            FROM `endorse_refresh_runtime_control`
            WHERE `id` = 1
            LIMIT 1
        ");

        if (empty($row)) {
            return [
                'contract_state' => 'legacy',
                'owner_state' => 'cron',
                'generation' => 0,
            ];
        }

        return [
            'contract_state' => strval($row['contract_state'] ?? 'legacy'),
            'owner_state' => strval($row['owner_state'] ?? 'cron'),
            'generation' => intval($row['generation'] ?? 0),
        ];
    }

    public function isLegacyMode(): bool
    {
        return $this->runtimeState()['contract_state'] !== 'v2';
    }

    public function validateV2Request(array $payload, bool $requireWorkerId = true): ?array
    {
        if (intval($payload['contract_version'] ?? 0) !== self::CONTRACT_VERSION) {
            return $this->errorEnvelope(426, 'contract_version_unsupported', 'Contract version 2 required.');
        }

        if ($requireWorkerId) {
            $workerId = trim((string) ($payload['worker_id'] ?? ''));
            if ($workerId === '' || !self::isUuidV4($workerId)) {
                return $this->errorEnvelope(422, 'invalid_worker_id', 'worker_id must be a UUID v4.');
            }
        }

        return null;
    }

    public function validateContractCanClaim(string $owner, bool $lock = false): ?array
    {
        if (!$this->tableExists('endorse_refresh_runtime_control')) {
            return $this->errorEnvelope(503, 'contract_not_active', 'Contract v2 is not available.');
        }

        if (!$this->validateFallbackConfigInvariant()) {
            return $this->errorEnvelope(503, 'fallback_config_invalid', 'Fallback lease invariant is invalid.');
        }

        $runtimeSql = "
            SELECT `contract_state`, `owner_state`, `generation`
            FROM `endorse_refresh_runtime_control`
            WHERE `id` = 1
        ";
        if ($lock) {
            $runtimeSql .= " FOR UPDATE";
        }
        $runtime = $this->singleRow($runtimeSql);
        if (empty($runtime) || strval($runtime['contract_state'] ?? '') !== 'v2') {
            return $this->errorEnvelope(503, 'contract_not_active', 'Contract v2 is not active.');
        }

        $ownerState = strval($runtime['owner_state'] ?? '');
        $allowed = ($owner === self::OWNER_RUST && $ownerState === 'rust')
            || ($owner === self::OWNER_CRON && $ownerState === 'cron');
        if (!$allowed) {
            return $this->errorEnvelope(503, 'driver_not_owner', 'This worker does not own new claims.');
        }

        foreach ($this->activeCircuitReasons($lock) as $circuit) {
            if ($circuit['owner'] === $owner || $circuit['owner'] === self::PROVIDER_KEY_RAPIDAPI) {
                return [
                    'http_status' => 503,
                    'body' => [
                        'status' => false,
                        'reason' => 'circuit_open',
                        'msg' => 'A required circuit is open.',
                        'active_circuits' => $this->activeCircuitReasons(false),
                    ],
                ];
            }
        }

        return null;
    }

    public function claimBatchV2(string $owner, string $workerId, int $limit, string $taskIdentity = ''): array
    {
        $limit = max(1, min(self::MAX_RESULT_BATCH, $limit));
        $this->db->trans_begin();

        try {
            $contractError = $this->validateContractCanClaim($owner, true);
            if ($contractError !== null) {
                $this->db->trans_rollback();
                return $contractError;
            }

            $rows = $this->queryRows("
                SELECT *
                FROM `endorse_refresh_queue`
                WHERE `status` = 'pending'
                  AND `worker_id` IS NULL
                  AND (`next_attempt_at` IS NULL OR `next_attempt_at` <= UTC_TIMESTAMP(6))
                ORDER BY `priority` DESC, `attempts` ASC, `created_at` ASC, `id` ASC
                LIMIT {$limit}
                FOR UPDATE
            ");

            $claimed = [];
            $finalized = 0;
            $now = $this->nowUtc();

            foreach ($rows as $row) {
                $queueId = intval($row['id']);
                if ($this->isQuarantinedContent(intval($row['id_endorse']), strval($row['platform']), strval($row['link_upload']))) {
                    $this->db->update('endorse_refresh_queue', [
                        'status' => 'failed',
                        'error_message' => 'item_quarantined',
                        'completed_at' => $now,
                        'worker_id' => null,
                        'claim_owner' => null,
                        'active_attempt_id' => null,
                        'started_at' => null,
                    ], ['id' => $queueId]);
                    $finalized++;
                    continue;
                }

                $attemptNo = intval($row['attempt_sequence']) + 1;
                $this->db->update('endorse_refresh_queue', [
                    'status' => 'processing',
                    'worker_id' => $workerId,
                    'claim_owner' => $owner,
                    'attempt_sequence' => $attemptNo,
                    'claimed_at' => $now,
                    'started_at' => $now,
                    'next_attempt_at' => null,
                ], ['id' => $queueId]);

                $attempt = [
                    'queue_id' => $queueId,
                    'attempt_no' => $attemptNo,
                    'worker_id' => $workerId,
                    'status' => 'processing',
                    'started_at' => $now,
                    'created_at' => $now,
                ];
                $this->db->insert('endorse_refresh_queue_attempts', $attempt);
                $attemptId = intval($this->db->insert_id());
                $this->db->update('endorse_refresh_queue', [
                    'active_attempt_id' => $attemptId,
                ], ['id' => $queueId]);

                $claimed[] = [
                    'queue_id' => $queueId,
                    'id_endorse' => intval($row['id_endorse']),
                    'platform' => strval($row['platform']),
                    'url' => EndorseRefreshQueueService::normalizeTiktokUrl(strval($row['link_upload'])),
                    'purpose' => strval($row['purpose'] ?? 'daily'),
                    'enqueued_by' => intval($row['enqueued_by'] ?? 0),
                    'attempt_no' => $attemptNo,
                    'active_attempt_id' => $attemptId,
                    'max_attempts' => intval($row['max_attempts']),
                    'worker_id' => $workerId,
                    'timeout_sec' => max(1, intval(env('ENDORSE_REFRESH_HTTP_TIMEOUT', 30))),
                    'task_identity' => $taskIdentity,
                ];
            }

            $this->db->trans_commit();

            return [
                'http_status' => 200,
                'body' => [
                    'status' => true,
                    'contract_version' => self::CONTRACT_VERSION,
                    'worker_id' => $workerId,
                    'claimed' => count($claimed),
                    'finalized_without_claim' => $finalized,
                    'items' => $claimed,
                ],
            ];
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            log_message('error', 'endorse_refresh_v2_claim_failed: ' . $e->getMessage());

            return $this->errorEnvelope(500, 'claim_failed', 'Claim failed.');
        }
    }

    public function fetchFallbackV2(array $payload): array
    {
        $requestError = $this->validateV2Request($payload, true);
        if ($requestError !== null) {
            return $requestError;
        }

        $identity = $this->validateActiveClaimIdentity(
            intval($payload['queue_id'] ?? 0),
            intval($payload['attempt_no'] ?? 0),
            intval($payload['active_attempt_id'] ?? 0),
            trim((string) ($payload['worker_id'] ?? '')),
            false
        );
        if (!$identity['ok']) {
            return $this->errorEnvelope($identity['http_status'], $identity['reason'], $identity['msg']);
        }

        $providerError = $this->providerCircuitOpenEnvelope();
        if ($providerError !== null) {
            return $providerError;
        }

        $lease = $this->acquireFallbackLease($identity['queue'], $identity['attempt']);
        if (!$lease['ok']) {
            return $lease['response'];
        }

        $response = $this->CI->template->get_social_media(
            strval($identity['queue']['platform'] ?? ''),
            EndorseRefreshQueueService::normalizeTiktokUrl(strval($identity['queue']['link_upload'] ?? '')),
            true,
            null,
            true
        );
        $normalized = $this->CI->endorse_sync->normalize_response(
            $response,
            strval($identity['queue']['platform'] ?? ''),
            strval($identity['queue']['link_upload'] ?? ''),
            'rapidapi_fallback'
        );

        $providerTransition = $this->providerTransitionForResponse($normalized);
        $this->completeFallbackLease($lease, $normalized, $providerTransition);

        if ($providerTransition['systemic']) {
            return [
                'http_status' => 503,
                'body' => [
                    'status' => false,
                    'reason' => $providerTransition['reason_code'],
                    'msg' => strval($normalized['msg'] ?? 'Provider unavailable'),
                    'active_circuits' => $this->activeCircuitReasons(false),
                ],
            ];
        }

        return [
            'http_status' => 200,
            'body' => $normalized,
        ];
    }

    public function releaseClaimsV2(array $payload, string $owner): array
    {
        $requestError = $this->validateV2Request($payload, true);
        if ($requestError !== null) {
            return $requestError;
        }

        $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];
        if (empty($items)) {
            return [
                'http_status' => 200,
                'body' => ['status' => true, 'released' => 0],
            ];
        }

        $byQueue = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                return $this->errorEnvelope(422, 'invalid_release_payload', 'Release items must be objects.');
            }
            $queueId = intval($item['queue_id'] ?? 0);
            if ($queueId <= 0 || isset($byQueue[$queueId])) {
                return $this->errorEnvelope(422, 'duplicate_queue_id', 'Release payload contains duplicate queue IDs.');
            }
            $byQueue[$queueId] = $item;
        }

        $this->db->trans_begin();
        try {
            $errors = [];
            $queueRows = $this->lockedQueues(array_keys($byQueue));
            foreach ($byQueue as $queueId => $item) {
                $validation = $this->validateActiveClaimIdentity(
                    $queueId,
                    intval($item['attempt_no'] ?? 0),
                    intval($item['active_attempt_id'] ?? 0),
                    trim((string) ($payload['worker_id'] ?? '')),
                    true,
                    $queueRows
                );
                if (!$validation['ok']) {
                    $errors[] = $this->queueConflict($queueId, $validation['http_status'], $validation['reason'], $validation['msg']);
                }
            }

            if (!empty($errors)) {
                $this->db->trans_rollback();
                return [
                    'http_status' => 409,
                    'body' => ['status' => false, 'reason' => 'claim_conflict', 'conflicts' => $errors],
                ];
            }

            $now = $this->nowUtc();
            foreach ($queueRows as $queue) {
                $queueId = intval($queue['id']);
                $item = $byQueue[$queueId];
                $reasonCode = trim((string) ($item['reason_code'] ?? 'released'));
                $msg = trim((string) ($item['msg'] ?? 'Released back to queue.'));
                $this->db->update('endorse_refresh_queue', [
                    'status' => 'pending',
                    'worker_id' => null,
                    'claim_owner' => null,
                    'active_attempt_id' => null,
                    'started_at' => null,
                    'claimed_at' => $now,
                    'next_attempt_at' => $now,
                    'error_message' => $msg,
                ], ['id' => $queueId]);
                $this->db->update('endorse_refresh_queue_attempts', [
                    'status' => 'cancelled',
                    'error_class' => $reasonCode,
                    'error_message' => $msg,
                    'finished_at' => $now,
                ], ['id' => intval($queue['active_attempt_id'])]);
            }

            $this->db->trans_commit();

            return [
                'http_status' => 200,
                'body' => ['status' => true, 'released' => count($queueRows), 'owner' => $owner],
            ];
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            log_message('error', 'endorse_refresh_v2_release_failed: ' . $e->getMessage());

            return $this->errorEnvelope(500, 'release_failed', 'Release failed.');
        }
    }

    public function applyResultsV2(array $payload): array
    {
        $requestError = $this->validateV2Request($payload, true);
        if ($requestError !== null) {
            return $requestError;
        }

        $results = is_array($payload['results'] ?? null) ? $payload['results'] : [];
        if (empty($results)) {
            return [
                'http_status' => 200,
                'body' => ['status' => true, 'processed' => 0, 'completed' => 0, 'failed' => 0, 'retrying' => 0],
            ];
        }
        if (count($results) > self::MAX_RESULT_BATCH) {
            return $this->errorEnvelope(422, 'result_batch_too_large', 'Result batch exceeds the configured maximum.');
        }

        $byQueue = [];
        foreach ($results as $item) {
            if (!is_array($item)) {
                return $this->errorEnvelope(422, 'invalid_result_payload', 'Result items must be objects.');
            }
            $queueId = intval($item['queue_id'] ?? 0);
            if ($queueId <= 0 || isset($byQueue[$queueId])) {
                return $this->errorEnvelope(422, 'duplicate_queue_id', 'Result payload contains duplicate queue IDs.');
            }
            $byQueue[$queueId] = $item;
        }

        $queueIds = array_keys($byQueue);
        sort($queueIds, SORT_NUMERIC);

        $this->db->trans_begin();
        try {
            $queueRows = $this->lockedQueues($queueIds);
            $errors = [];
            foreach ($queueIds as $queueId) {
                $item = $byQueue[$queueId];
                $validation = $this->validateActiveClaimIdentity(
                    $queueId,
                    intval($item['attempt_no'] ?? 0),
                    intval($item['active_attempt_id'] ?? 0),
                    trim((string) ($payload['worker_id'] ?? '')),
                    true,
                    $queueRows
                );
                if (!$validation['ok']) {
                    $errors[] = $this->queueConflict($queueId, $validation['http_status'], $validation['reason'], $validation['msg']);
                }
            }

            if (!empty($errors)) {
                $this->db->trans_rollback();
                return [
                    'http_status' => 409,
                    'body' => ['status' => false, 'reason' => 'claim_conflict', 'conflicts' => $errors],
                ];
            }

            $endorseRows = $this->endorseMapFromQueues($queueRows);
            $endorseIds = array_keys($endorseRows);
            $prevStatsMap = $this->CI->endorse_sync->load_prev_stats_batch(
                $endorseIds,
                $this->businessDateFromUtc($this->nowUtc())
            );

            $completed = 0;
            $failed = 0;
            $retrying = 0;
            $touchedCampaigns = [];
            $now = $this->nowUtc();
            $retryBase = intval(env('ENDORSE_REFRESH_RETRY_BASE_SEC', 60));

            foreach ($queueRows as $queue) {
                $queueId = intval($queue['id']);
                $item = $byQueue[$queueId];
                $response = is_array($item['response'] ?? null) ? $item['response'] : ['status' => false, 'msg' => 'No response', 'data' => []];
                $endorse = $endorseRows[intval($queue['id_endorse'])] ?? null;

                if (!$endorse) {
                    $this->finalizeQueueAsFailed($queue, intval($item['attempt_no']), 'Endorse row no longer exists', Endorse_sync::ERR_PERMANENT, $now);
                    $failed++;
                    continue;
                }

                $purpose = strval($queue['purpose'] ?? 'daily');
                if ($purpose === 'daily') {
                    $result = $this->CI->endorse_sync->apply(
                        $endorse,
                        $response,
                        intval($queue['enqueued_by'] ?? 0),
                        $prevStatsMap[intval($endorse['id'])] ?? null
                    );
                } else {
                    $result = $this->CI->endorse_sync->apply_snapshot(
                        $endorse,
                        $response,
                        $purpose,
                        intval($queue['enqueued_by'] ?? 0)
                    );
                }

                $attemptNo = intval($item['attempt_no']);
                $attemptId = intval($item['active_attempt_id']);
                $errorClass = strval($result['error_class'] ?? Endorse_sync::ERR_TRANSIENT);
                $msg = strval($result['msg'] ?? 'Gagal');

                if (!empty($result['status'])) {
                    $this->db->update('endorse_refresh_queue', [
                        'status' => 'completed',
                        'attempts' => max(intval($queue['attempts']), $attemptNo),
                        'worker_id' => null,
                        'claim_owner' => null,
                        'active_attempt_id' => null,
                        'error_message' => null,
                        'completed_at' => $now,
                        'started_at' => null,
                    ], ['id' => $queueId]);
                    $this->db->update('endorse_refresh_queue_attempts', [
                        'status' => 'completed',
                        'finished_at' => $now,
                        'error_class' => null,
                        'error_message' => null,
                    ], ['id' => $attemptId]);
                    if ($purpose === 'daily') {
                        $touchedCampaigns[intval($endorse['id_campaign'])] = true;
                    }
                    $completed++;
                    continue;
                }

                if (Endorse_sync::is_terminal_class($errorClass) || $attemptNo >= intval($queue['max_attempts'])) {
                    $this->finalizeQueueAsFailed($queue, $attemptNo, $msg, $errorClass, $now);
                    $failed++;
                    continue;
                }

                $delay = self::deterministicRetryDelaySeconds($queueId, $attemptNo, $retryBase);
                $nextAttemptAt = gmdate('Y-m-d H:i:s', time() + $delay) . '.000000';
                $this->db->update('endorse_refresh_queue', [
                    'status' => 'pending',
                    'attempts' => max(intval($queue['attempts']), $attemptNo),
                    'worker_id' => null,
                    'claim_owner' => null,
                    'active_attempt_id' => null,
                    'started_at' => null,
                    'claimed_at' => $now,
                    'next_attempt_at' => $nextAttemptAt,
                    'error_message' => $msg,
                ], ['id' => $queueId]);
                $this->db->update('endorse_refresh_queue_attempts', [
                    'status' => 'retrying',
                    'finished_at' => $now,
                    'error_class' => $errorClass,
                    'error_message' => $msg,
                ], ['id' => $attemptId]);
                $retrying++;
            }

            foreach (array_keys($touchedCampaigns) as $campaignId) {
                $this->CI->endorse_sync->update_campaign_parent($campaignId, 0);
            }

            $this->db->trans_commit();

            return [
                'http_status' => 200,
                'body' => [
                    'status' => true,
                    'processed' => count($queueRows),
                    'completed' => $completed,
                    'failed' => $failed,
                    'retrying' => $retrying,
                ],
            ];
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            log_message('error', 'endorse_refresh_v2_result_failed: ' . $e->getMessage());

            return $this->errorEnvelope(500, 'result_failed', 'Result finalization failed.');
        }
    }

    public function extractContentKey(string $platform, string $url): string
    {
        $platform = strtolower(trim($platform));
        $normalizedUrl = EndorseRefreshQueueService::normalizeTiktokUrl($url);
        if ($platform === 'tiktok') {
            $contentId = trim((string) $this->CI->template->extract_tiktok_content_id($normalizedUrl));
            if ($contentId !== '') {
                return 'tiktok:' . $contentId;
            }
        }

        return $platform . ':' . hash('sha256', $normalizedUrl);
    }

    public function isQuarantinedContent(int $endorseId, string $platform, string $url): bool
    {
        if ($endorseId <= 0 || !$this->tableExists('endorse_refresh_quarantine')) {
            return false;
        }

        $contentKey = $this->extractContentKey($platform, $url);
        $row = $this->singleRow("
            SELECT `id`
            FROM `endorse_refresh_quarantine`
            WHERE `id_endorse` = " . intval($endorseId) . "
              AND `content_key` = " . $this->db->escape($contentKey) . "
              AND `cleared_at` IS NULL
            LIMIT 1
        ");

        return !empty($row);
    }

    protected function seedHealthRows(): void
    {
        if ($this->tableExists('endorse_refresh_provider_health')) {
            $this->db->query("
                INSERT IGNORE INTO `endorse_refresh_provider_health`
                    (`provider_key`, `state`, `generation`, `updated_at`)
                VALUES
                    ('rapidapi', 'closed', 1, UTC_TIMESTAMP(6))
            ");
        }
        if ($this->tableExists('endorse_refresh_worker_health')) {
            $this->db->query("
                INSERT IGNORE INTO `endorse_refresh_worker_health`
                    (`owner_key`, `state`, `generation`, `updated_at`)
                VALUES
                    ('cron', 'closed', 1, UTC_TIMESTAMP(6)),
                    ('rust', 'closed', 1, UTC_TIMESTAMP(6))
            ");
        }
    }

    protected function providerCircuitOpenEnvelope(): ?array
    {
        foreach ($this->activeCircuitReasons(false) as $circuit) {
            if ($circuit['owner'] === self::PROVIDER_KEY_RAPIDAPI) {
                return [
                    'http_status' => 503,
                    'body' => [
                        'status' => false,
                        'reason' => 'circuit_open',
                        'msg' => 'RapidAPI provider circuit is open.',
                        'active_circuits' => $this->activeCircuitReasons(false),
                    ],
                ];
            }
        }

        return null;
    }

    protected function providerTransitionForResponse(array $response): array
    {
        $reason = trim((string) ($response['reason_code'] ?? ''));
        $errorClass = trim((string) ($response['error_class'] ?? ''));
        $msg = strtolower(trim((string) ($response['msg'] ?? '')));

        if (in_array($reason, ['fallback_auth_failed', 'worker_auth_invalid'], true)
            || strpos($msg, 'unauthorized') !== false
            || strpos($msg, 'forbidden') !== false) {
            return ['systemic' => true, 'reason_code' => 'provider_auth_failed', 'state' => 'open', 'open_seconds' => null];
        }
        if (strpos($msg, 'rate') !== false || strpos($msg, '429') !== false) {
            return ['systemic' => true, 'reason_code' => 'provider_rate_limited', 'state' => 'open', 'open_seconds' => 60];
        }
        if (in_array($errorClass, [Endorse_sync::ERR_INFRA_STALL, Endorse_sync::ERR_INFRA_CONNECT, Endorse_sync::ERR_INFRA_DNS, Endorse_sync::ERR_INFRA_TLS], true)) {
            return ['systemic' => true, 'reason_code' => 'provider_timeout', 'state' => 'open', 'open_seconds' => 60];
        }
        if (!empty($response['status'])) {
            return ['systemic' => false, 'reason_code' => '', 'state' => 'closed', 'open_seconds' => 0];
        }

        return ['systemic' => false, 'reason_code' => $reason ?: 'fallback_api_failed', 'state' => 'closed', 'open_seconds' => 0];
    }

    protected function completeFallbackLease(array $lease, array $response, array $providerTransition): void
    {
        if (!$this->tableExists('endorse_refresh_fallback_calls')) {
            return;
        }

        $json = $this->sanitizeFallbackCache($response);
        $status = !empty($response['status']) ? 'completed' : 'failed';
        $reasonCode = strval($response['reason_code'] ?? ($providerTransition['reason_code'] ?? ''));

        $this->db->trans_begin();
        try {
            $runtime = $this->singleRow("
                SELECT `id`
                FROM `endorse_refresh_runtime_control`
                WHERE `id` = 1
                FOR UPDATE
            ");
            if (!empty($runtime) && $this->tableExists('endorse_refresh_provider_health')) {
                $provider = $this->singleRow("
                    SELECT `provider_key`, `generation`
                    FROM `endorse_refresh_provider_health`
                    WHERE `provider_key` = 'rapidapi'
                    FOR UPDATE
                ");
                if (!empty($provider)) {
                    $update = [
                        'state' => $providerTransition['state'],
                        'reason_code' => $providerTransition['reason_code'],
                        'updated_at' => $this->nowUtc(),
                    ];
                    if ($providerTransition['state'] === 'open' && $providerTransition['open_seconds'] !== null) {
                        $update['open_until'] = gmdate('Y-m-d H:i:s', time() + intval($providerTransition['open_seconds'])) . '.000000';
                    }
                    if ($providerTransition['state'] === 'closed') {
                        $update['open_until'] = null;
                    }
                    $this->db->update('endorse_refresh_provider_health', $update, ['provider_key' => 'rapidapi']);
                }
            }

            $this->db->update('endorse_refresh_fallback_calls', [
                'status' => $status,
                'http_status' => intval($response['http_status'] ?? 200),
                'reason_code' => $reasonCode,
                'response_json' => $json,
                'updated_at' => $this->nowUtc(),
                'completed_at' => $this->nowUtc(),
            ], ['id' => intval($lease['id'])]);

            $this->db->trans_commit();
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            log_message('error', 'endorse_refresh_v2_fallback_complete_failed: ' . $e->getMessage());
        }
    }

    protected function acquireFallbackLease(array $queue, array $attempt): array
    {
        if (!$this->tableExists('endorse_refresh_fallback_calls')) {
            return ['ok' => true, 'id' => 0];
        }

        $leaseSeconds = max(1, intval(env('ENDORSE_REFRESH_FALLBACK_LEASE_SECONDS', 90)));
        $identityWhere = [
            'queue_id' => intval($queue['id']),
            'attempt_no' => intval($attempt['attempt_no']),
            'worker_id' => strval($attempt['worker_id']),
        ];
        $this->db->trans_begin();
        try {
            $existing = $this->singleRow("
                SELECT *
                FROM `endorse_refresh_fallback_calls`
                WHERE `queue_id` = " . intval($queue['id']) . "
                  AND `attempt_no` = " . intval($attempt['attempt_no']) . "
                  AND `worker_id` = " . $this->db->escape(strval($attempt['worker_id'])) . "
                FOR UPDATE
            ");
            if (!empty($existing)) {
                $cached = json_decode(strval($existing['response_json'] ?? ''), true);
                if (in_array(strval($existing['status']), ['completed', 'failed'], true) && is_array($cached)) {
                    $this->db->trans_commit();

                    return [
                        'ok' => false,
                        'response' => ['http_status' => 200, 'body' => $cached],
                    ];
                }
                if (strval($existing['status']) === 'in_progress' && !empty($existing['lease_expires_at']) && strtotime($existing['lease_expires_at']) > time()) {
                    $this->db->trans_commit();

                    return [
                        'ok' => false,
                        'response' => $this->errorEnvelope(409, 'fallback_in_progress', 'Fallback already in progress for this claim.'),
                    ];
                }

                $leaseToken = self::generateWorkerUuid();
                $this->db->update('endorse_refresh_fallback_calls', [
                    'status' => 'in_progress',
                    'lease_token' => $leaseToken,
                    'lease_expires_at' => gmdate('Y-m-d H:i:s', time() + $leaseSeconds) . '.000000',
                    'updated_at' => $this->nowUtc(),
                ], ['id' => intval($existing['id'])]);
                $this->db->trans_commit();

                return ['ok' => true, 'id' => intval($existing['id']), 'lease_token' => $leaseToken];
            }

            $leaseToken = self::generateWorkerUuid();
            $this->db->insert('endorse_refresh_fallback_calls', array_merge($identityWhere, [
                'status' => 'in_progress',
                'lease_token' => $leaseToken,
                'lease_expires_at' => gmdate('Y-m-d H:i:s', time() + $leaseSeconds) . '.000000',
                'created_at' => $this->nowUtc(),
                'updated_at' => $this->nowUtc(),
            ]));
            $id = intval($this->db->insert_id());
            $this->db->trans_commit();

            return ['ok' => true, 'id' => $id, 'lease_token' => $leaseToken];
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            log_message('error', 'endorse_refresh_v2_fallback_lease_failed: ' . $e->getMessage());

            return ['ok' => false, 'response' => $this->errorEnvelope(500, 'fallback_lease_failed', 'Fallback lease acquisition failed.')];
        }
    }

    protected function lockedQueues(array $queueIds): array
    {
        $queueIds = array_values(array_unique(array_map('intval', $queueIds)));
        sort($queueIds, SORT_NUMERIC);
        if (empty($queueIds)) {
            return [];
        }

        return $this->queryRows("
            SELECT *
            FROM `endorse_refresh_queue`
            WHERE `id` IN (" . implode(',', $queueIds) . ")
            ORDER BY `id` ASC
            FOR UPDATE
        ");
    }

    protected function endorseMapFromQueues(array $queueRows): array
    {
        $endorseIds = [];
        foreach ($queueRows as $queue) {
            $endorseIds[] = intval($queue['id_endorse']);
        }
        $endorseIds = array_values(array_unique(array_filter($endorseIds)));
        if (empty($endorseIds)) {
            return [];
        }

        $rows = $this->queryRows("
            SELECT *
            FROM `endorse`
            WHERE `id` IN (" . implode(',', $endorseIds) . ")
            ORDER BY `id` ASC
            FOR UPDATE
        ");
        $map = [];
        foreach ($rows as $row) {
            $map[intval($row['id'])] = $row;
        }

        return $map;
    }

    protected function validateActiveClaimIdentity(
        int $queueId,
        int $attemptNo,
        int $activeAttemptId,
        string $workerId,
        bool $lock = false,
        ?array $lockedQueues = null
    ): array {
        if ($queueId <= 0) {
            return ['ok' => false, 'http_status' => 422, 'reason' => 'invalid_queue_id', 'msg' => 'queue_id is required.'];
        }
        if ($attemptNo <= 0) {
            return ['ok' => false, 'http_status' => 422, 'reason' => 'invalid_attempt_no', 'msg' => 'attempt_no is required.'];
        }
        if ($activeAttemptId <= 0) {
            return ['ok' => false, 'http_status' => 422, 'reason' => 'invalid_active_attempt_id', 'msg' => 'active_attempt_id is required.'];
        }

        $queue = null;
        if ($lockedQueues !== null) {
            foreach ($lockedQueues as $candidate) {
                if (intval($candidate['id']) === $queueId) {
                    $queue = $candidate;
                    break;
                }
            }
        }
        if ($queue === null) {
            $sql = "
                SELECT *
                FROM `endorse_refresh_queue`
                WHERE `id` = {$queueId}
                LIMIT 1
            ";
            if ($lock) {
                $sql .= " FOR UPDATE";
            }
            $queue = $this->singleRow($sql);
        }
        if (empty($queue)) {
            return ['ok' => false, 'http_status' => 404, 'reason' => 'queue_not_found', 'msg' => 'Queue not found.'];
        }
        if (strval($queue['status']) === 'completed') {
            return ['ok' => false, 'http_status' => 409, 'reason' => 'queue_completed', 'msg' => 'Queue already completed.'];
        }
        if (strval($queue['status']) !== 'processing') {
            return ['ok' => false, 'http_status' => 409, 'reason' => 'queue_not_processing', 'msg' => 'Queue is not processing.'];
        }
        if (strval($queue['worker_id']) !== $workerId) {
            return ['ok' => false, 'http_status' => 409, 'reason' => 'worker_mismatch', 'msg' => 'worker_id does not match the active claim.'];
        }
        if (intval($queue['attempt_sequence']) !== $attemptNo) {
            return ['ok' => false, 'http_status' => 409, 'reason' => 'attempt_mismatch', 'msg' => 'attempt_no does not match the active claim.'];
        }
        if (intval($queue['active_attempt_id']) !== $activeAttemptId) {
            return ['ok' => false, 'http_status' => 409, 'reason' => 'attempt_not_active', 'msg' => 'active_attempt_id does not match the active claim.'];
        }

        $attemptSql = "
            SELECT *
            FROM `endorse_refresh_queue_attempts`
            WHERE `id` = {$activeAttemptId}
            LIMIT 1
        ";
        if ($lock) {
            $attemptSql .= " FOR UPDATE";
        }
        $attempt = $this->singleRow($attemptSql);
        if (empty($attempt)) {
            return ['ok' => false, 'http_status' => 409, 'reason' => 'attempt_not_active', 'msg' => 'Active attempt row not found.'];
        }
        if (intval($attempt['queue_id']) !== $queueId || intval($attempt['attempt_no']) !== $attemptNo) {
            return ['ok' => false, 'http_status' => 409, 'reason' => 'attempt_mismatch', 'msg' => 'Attempt row does not match the queue identity.'];
        }
        if (strval($attempt['worker_id']) !== $workerId) {
            return ['ok' => false, 'http_status' => 409, 'reason' => 'worker_mismatch', 'msg' => 'Attempt worker does not match the active claim.'];
        }
        if (strval($attempt['status']) !== 'processing') {
            return ['ok' => false, 'http_status' => 409, 'reason' => 'attempt_not_active', 'msg' => 'Attempt is no longer active.'];
        }

        return ['ok' => true, 'queue' => $queue, 'attempt' => $attempt];
    }

    protected function activeCircuitReasons(bool $lock): array
    {
        $circuits = [];
        if ($this->tableExists('endorse_refresh_provider_health')) {
            $sql = "SELECT `provider_key`, `state`, `reason_code`, `open_until` FROM `endorse_refresh_provider_health` WHERE `state` <> 'closed'";
            if ($lock) {
                $sql .= " FOR UPDATE";
            }
            foreach ($this->queryRows($sql) as $row) {
                if ($this->isCircuitActive($row)) {
                    $circuits[] = [
                        'owner' => strval($row['provider_key']),
                        'reason' => strval($row['reason_code']),
                        'open_until' => $row['open_until'] ?? null,
                    ];
                }
            }
        }
        if ($this->tableExists('endorse_refresh_worker_health')) {
            $sql = "SELECT `owner_key`, `state`, `reason_code`, `open_until` FROM `endorse_refresh_worker_health` WHERE `state` <> 'closed'";
            if ($lock) {
                $sql .= " FOR UPDATE";
            }
            foreach ($this->queryRows($sql) as $row) {
                if ($this->isCircuitActive($row)) {
                    $circuits[] = [
                        'owner' => strval($row['owner_key']),
                        'reason' => strval($row['reason_code']),
                        'open_until' => $row['open_until'] ?? null,
                    ];
                }
            }
        }

        return $circuits;
    }

    protected function isCircuitActive(array $row): bool
    {
        if (strval($row['state'] ?? '') === 'half_open') {
            return true;
        }
        if (strval($row['state'] ?? '') !== 'open') {
            return false;
        }
        if (empty($row['open_until'])) {
            return true;
        }

        return strtotime(strval($row['open_until'])) > time();
    }

    protected function validateFallbackConfigInvariant(): bool
    {
        $lease = intval(env('ENDORSE_REFRESH_FALLBACK_LEASE_SECONDS', 90));
        $timeout = intval(env('ENDORSE_REFRESH_PROVIDER_TIMEOUT_SECONDS', 60));
        $margin = intval(env('ENDORSE_REFRESH_COMPLETION_MARGIN_SECONDS', 15));

        return $lease >= ($timeout + $margin);
    }

    protected function sanitizeFallbackCache(array $response): ?string
    {
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $safe = [
            'status' => !empty($response['status']),
            'msg' => mb_substr(strval($response['msg'] ?? ''), 0, 512),
            'error_class' => strval($response['error_class'] ?? ''),
            'reason_code' => strval($response['reason_code'] ?? ''),
            'stats_found' => !empty($response['stats_found']),
            'stats_complete' => !empty($response['stats_complete']),
            'stats_fields' => array_values(array_map('strval', is_array($response['stats_fields'] ?? null) ? $response['stats_fields'] : [])),
            'observed_at' => strval($response['observed_at'] ?? ''),
            'data' => [
                'like' => array_key_exists('like', $data) ? $data['like'] : null,
                'share' => array_key_exists('share', $data) ? $data['share'] : null,
                'comment' => array_key_exists('comment', $data) ? $data['comment'] : null,
                'collect' => array_key_exists('collect', $data) ? $data['collect'] : null,
                'view' => array_key_exists('view', $data) ? $data['view'] : null,
                'content_id' => mb_substr(strval($data['content_id'] ?? ''), 0, 64),
                'media_type' => mb_substr(strval($data['media_type'] ?? ''), 0, 16),
                'created_at' => mb_substr(strval($data['created_at'] ?? ''), 0, 32),
                'cover' => mb_substr(strval($data['cover'] ?? ''), 0, 512),
                'video_link' => mb_substr(strval($data['video_link'] ?? ''), 0, 2048),
            ],
        ];

        $json = json_encode($safe, JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return null;
        }
        if (strlen($json) > self::MAX_FALLBACK_CACHE_BYTES) {
            return json_encode([
                'status' => false,
                'reason_code' => 'fallback_cache_payload_too_large',
                'msg' => 'Normalized fallback payload exceeded cache limit.',
            ]);
        }

        return $json;
    }

    protected function finalizeQueueAsFailed(array $queue, int $attemptNo, string $msg, string $errorClass, string $now): void
    {
        $this->db->update('endorse_refresh_queue', [
            'status' => 'failed',
            'attempts' => max(intval($queue['attempts']), $attemptNo),
            'worker_id' => null,
            'claim_owner' => null,
            'active_attempt_id' => null,
            'error_message' => $msg,
            'completed_at' => $now,
            'started_at' => null,
        ], ['id' => intval($queue['id'])]);
        $this->db->update('endorse_refresh_queue_attempts', [
            'status' => 'failed',
            'error_class' => $errorClass,
            'error_message' => $msg,
            'finished_at' => $now,
        ], ['id' => intval($queue['active_attempt_id'])]);
    }

    protected function queueConflict(int $queueId, int $httpStatus, string $reason, string $msg): array
    {
        return [
            'queue_id' => $queueId,
            'http_status' => $httpStatus,
            'reason' => $reason,
            'msg' => $msg,
        ];
    }

    protected function errorEnvelope(int $httpStatus, string $reason, string $msg): array
    {
        return [
            'http_status' => $httpStatus,
            'body' => [
                'status' => false,
                'reason' => $reason,
                'msg' => $msg,
            ],
        ];
    }

    protected function tableExists(string $table): bool
    {
        if (!array_key_exists($table, $this->tableExistsCache)) {
            $this->tableExistsCache[$table] = $this->db->table_exists($table);
        }

        return $this->tableExistsCache[$table];
    }

    protected function queryRows(string $sql): array
    {
        $query = $this->db->query($sql);

        return $query ? $query->result_array() : [];
    }

    protected function singleRow(string $sql): array
    {
        $rows = $this->queryRows($sql);

        return !empty($rows) ? $rows[0] : [];
    }

    protected function nowUtc(): string
    {
        return gmdate('Y-m-d H:i:s') . '.000000';
    }

    protected function businessDateFromUtc(string $utcDateTime): string
    {
        $tz = new DateTimeZone(strval(env('ENDORSE_REFRESH_BUSINESS_TIMEZONE', 'Asia/Jakarta')));
        $utc = new DateTimeZone('UTC');
        $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $utcDateTime, $utc);
        if (!$dt) {
            $dt = new DateTimeImmutable('now', $utc);
        }

        return $dt->setTimezone($tz)->format('Y-m-d');
    }
}
