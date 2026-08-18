<?php

defined('BASEPATH') || exit('No direct script access allowed');

/**
 * Lightweight, failure-tolerant diagnostics. It must never stop a refresh job.
 */
final class EndorseRefreshDiagnostics
{
    private $CI;
    private $db;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
        $this->db = $this->CI->db;
    }

    public function startRun(string $source, int $userId = 0, int $campaignId = 0, array $config = []): string
    {
        $id = $this->uuid();

        try {
            $this->db->insert('endorse_refresh_runs', [
                'id'          => $id, 'source' => substr($source, 0, 40),
                'request_id'  => $this->requestId(), 'initiator_user_id' => $userId > 0 ? $userId : null,
                'id_campaign' => $campaignId > 0 ? $campaignId : null, 'status' => 'running',
                'config_json' => json_encode($config), 'started_at' => $this->now(),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'refresh diagnostics start failed: ' . $e::class);
        }

        return $id;
    }

    public function finishRun(string $id, array $fields, string $status = 'completed'): void
    {
        if ($id === '') {
            return;
        }

        try {
            $allowed = ['candidate_count', 'enqueued_count', 'skipped_duplicate_count', 'excluded_count', 'claimed_count', 'completed_count', 'retrying_count', 'failed_count', 'deferred_count', 'note'];
            $update  = ['status' => $status, 'finished_at' => $this->now()];

            foreach ($allowed as $key) {
                if (array_key_exists($key, $fields)) {
                    $update[$key] = $fields[$key];
                }
            }
            $this->db->where('id', $id)->update('endorse_refresh_runs', $update);
        } catch (Throwable $e) {
            log_message('error', 'refresh diagnostics finish failed: ' . $e::class);
        }
    }

    public function archiveAndClear(int $userId, string $reason): array
    {
        $reason   = substr(trim($reason) ?: 'manual_clear', 0, 255);
        $now      = $this->now();
        $queue    = $this->scalar('SELECT COUNT(*) FROM endorse_refresh_queue');
        $attempts = $this->scalar('SELECT COUNT(*) FROM endorse_refresh_queue_attempts');
        $this->db->trans_start();
        $this->db->query('INSERT INTO endorse_refresh_queue_archive (id,id_endorse,id_campaign,platform,purpose,link_upload,status,priority,attempts,attempt_sequence,active_attempt_id,provider_job_id,provider_submitted_at,max_attempts,error_message,worker_id,claim_owner,claimed_at,next_attempt_at,enqueued_by,retry_source_id,enqueue_run_id,enqueue_source,created_at,started_at,completed_at,archived_at,archived_by,archive_reason) SELECT id,id_endorse,id_campaign,platform,purpose,link_upload,status,priority,attempts,attempt_sequence,active_attempt_id,provider_job_id,provider_submitted_at,max_attempts,error_message,worker_id,claim_owner,claimed_at,next_attempt_at,enqueued_by,retry_source_id,enqueue_run_id,enqueue_source,created_at,started_at,completed_at,?,?,? FROM endorse_refresh_queue', [$now, $userId > 0 ? $userId : null, $reason]);
        $this->db->query('INSERT INTO endorse_refresh_queue_attempt_archive (id,queue_id,attempt_no,worker_id,status,error_class,error_message,started_at,finished_at,created_at,archived_at) SELECT id,queue_id,attempt_no,worker_id,status,error_class,error_message,started_at,finished_at,created_at,? FROM endorse_refresh_queue_attempts', [$now]);
        $this->db->query('DELETE FROM endorse_refresh_queue_attempts');
        $this->db->query('DELETE FROM endorse_refresh_queue');
        $this->db->trans_complete();
        if ($this->db->trans_status() === false) {
            throw new RuntimeException('Gagal mengarsipkan queue sebelum clear.');
        }
        $run = $this->startRun('manual_clear', $userId, 0, ['reason' => $reason]);
        $this->finishRun($run, ['candidate_count' => $queue, 'note' => 'archived_attempts=' . $attempts], 'cleared');

        return ['queue' => $queue, 'attempts' => $attempts, 'reason' => $reason];
    }

    public function cleanup(int $days = 30): void
    {
        $days = max(1, min(365, $days));

        foreach (['endorse_refresh_queue_attempt_archive' => 'archived_at', 'endorse_refresh_queue_archive' => 'archived_at', 'endorse_refresh_resource_snapshots' => 'captured_at', 'endorse_refresh_spikes' => 'captured_at', 'endorse_refresh_runs' => 'started_at'] as $table => $column) {
            $this->db->query("DELETE FROM `{$table}` WHERE `{$column}` < (NOW(6) - INTERVAL {$days} DAY)");
        }
    }

    private function scalar(string $sql): int
    {
        $row = $this->db->query($sql)->row_array();

        return (int) (array_values($row ?: [0])[0]);
    }

    private function now(): string
    {
        return gmdate('Y-m-d H:i:s') . '.' . sprintf('%06d', (int) ((microtime(true) - floor(microtime(true))) * 1000000));
    }

    private function requestId(): ?string
    {
        return function_exists('monitoring_request_id') ? monitoring_request_id() : null;
    }

    private function uuid(): string
    {
        try {
            $b    = random_bytes(16);
            $b[6] = chr((ord($b[6]) & 0x0F) | 0x40);
            $b[8] = chr((ord($b[8]) & 0x3F) | 0x80);

            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
        } catch (Throwable $e) {
            return uniqid('refresh-', true);
        }
    }
}
