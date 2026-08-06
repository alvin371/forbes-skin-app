<?php

defined('BASEPATH') || exit('No direct script access allowed');

/**
 * Owns the async Threads slice of endorse_refresh_queue.
 */
class ThreadsEndorseScraperService
{
    protected $CI;
    protected $db;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
        $this->CI->load->model('mymodel');
        $this->CI->load->library('Threads_scraper_api');
        $this->CI->load->library('Endorse_sync');
        $this->db = $this->CI->db;
    }

    public function run(int $limit = 40): array
    {
        $limit     = max(1, min(200, $limit));
        $polled    = $this->pollSubmitted($limit);
        $submitted = $this->submitPending($limit);

        return [
            'status'    => true,
            'submitted' => $submitted['submitted'],
            'completed' => $polled['completed'],
            'retrying'  => $polled['retrying'] + $submitted['retrying'],
            'failed'    => $polled['failed'] + $submitted['failed'],
            'waiting'   => $polled['waiting'],
        ];
    }

    protected function submitPending(int $limit): array
    {
        $workerId = $this->workerId();
        $now      = date('Y-m-d H:i:s');
        $this->db->query("\n            UPDATE endorse_refresh_queue\n            SET status = 'processing', worker_id = " . $this->db->escape($workerId) . ",\n                started_at = '{$now}', claimed_at = '{$now}'\n            WHERE status = 'pending' AND platform = 'Threads' AND worker_id IS NULL\n              AND (next_attempt_at IS NULL OR next_attempt_at <= UTC_TIMESTAMP(6))\n            ORDER BY priority DESC, attempts ASC, created_at ASC\n            LIMIT {$limit}\n        ");
        $rows    = $this->CI->mymodel->selectWithQuery("\n            SELECT * FROM endorse_refresh_queue\n            WHERE status = 'processing' AND worker_id = " . $this->db->escape($workerId) . "\n            ORDER BY id ASC\n        ");
        $summary = ['submitted' => 0, 'retrying' => 0, 'failed' => 0];

        foreach ($rows as $row) {
            $attemptNo = (int) ($row['attempt_sequence'] ?? 0) + 1;
            $this->db->insert('endorse_refresh_queue_attempts', [
                'queue_id'   => (int) ($row['id']), 'attempt_no' => $attemptNo,
                'worker_id'  => $workerId, 'status' => 'processing',
                'started_at' => $now, 'created_at' => $now,
            ]);
            $attemptId = (int) ($this->db->insert_id());
            $this->db->update('endorse_refresh_queue', [
                'attempt_sequence' => $attemptNo, 'active_attempt_id' => $attemptId,
                'attempts'         => (int) ($row['attempts']) + 1,
            ], ['id' => (int) ($row['id'])]);

            $result = $this->CI->threads_scraper_api->scrapePost((string) ($row['link_upload']));
            $data   = is_array($result['data'] ?? null) ? $result['data'] : [];
            if (! empty($result['status']) && ! empty($data['job_id'])) {
                $this->db->update('endorse_refresh_queue', [
                    'status'                => 'submitted', 'provider_job_id' => (string) ($data['job_id']),
                    'provider_submitted_at' => $now, 'worker_id' => null,
                    'claim_owner'           => null, 'started_at' => null, 'claimed_at' => $now,
                ], ['id' => (int) ($row['id'])]);
                $this->finalizeAttempt((int) ($row['id']), $attemptNo, $workerId, 'submitted', null, null, null);
                $summary['submitted']++;

                continue;
            }
            $outcome = $this->retryOrFail($row, $attemptNo, $workerId, (string) ($result['msg'] ?? 'Gagal membuat job Threads.'), (string) ($result['error_class'] ?? 'transient'));
            $summary[$outcome]++;
        }

        return $summary;
    }

    protected function pollSubmitted(int $limit): array
    {
        $rows    = $this->CI->mymodel->selectWithQuery("\n            SELECT * FROM endorse_refresh_queue\n            WHERE status = 'submitted' AND platform = 'Threads'\n            ORDER BY provider_submitted_at ASC, id ASC\n            LIMIT {$limit}\n        ");
        $summary = ['completed' => 0, 'retrying' => 0, 'failed' => 0, 'waiting' => 0];
        $timeout = max(60, (int) (env('SOCIAL_SCRAPER_JOB_TIMEOUT_SEC', 900)));

        foreach ($rows as $row) {
            $attemptNo   = (int) ($row['attempt_sequence'] ?? 0);
            $workerId    = (string) ($this->attemptWorkerId((int) ($row['id']), $attemptNo));
            $submittedAt = strtotime((string) ($row['provider_submitted_at'] ?? '')) ?: time();
            if ((time() - $submittedAt) >= $timeout) {
                $outcome = $this->retryOrFail($row, $attemptNo, $workerId, 'Job Threads melewati batas waktu.', 'transient');
                $summary[$outcome]++;

                continue;
            }
            $job = $this->CI->threads_scraper_api->job((string) ($row['provider_job_id']));
            if (empty($job['status'])) {
                // A polling transport error does not create a duplicate remote job.
                $this->db->update('endorse_refresh_queue', ['error_message' => (string) ($job['msg'] ?? 'Gagal memeriksa job Threads.')], ['id' => (int) ($row['id'])]);
                $summary['waiting']++;

                continue;
            }
            $remote = $job['data'];
            $status = (string) ($remote['status'] ?? '');
            if (in_array($status, ['pending', 'running'], true)) {
                $summary['waiting']++;

                continue;
            }
            if ($status !== 'completed') {
                $outcome = $this->retryOrFail($row, $attemptNo, $workerId, (string) ($remote['error'] ?? 'Job Threads gagal.'), 'transient');
                $summary[$outcome]++;

                continue;
            }

            $response    = Threads_scraper_api::normalizePostResult(is_array($remote['result'] ?? null) ? $remote['result'] : [], (string) ($row['link_upload']), (string) ($remote['platform'] ?? ''));
            $endorseRows = $this->CI->mymodel->selectWithQuery('SELECT * FROM endorse WHERE id = ' . (int) ($row['id_endorse']) . ' LIMIT 1');
            if (empty($endorseRows)) {
                $outcome = $this->retryOrFail($row, $attemptNo, $workerId, 'Endorse row no longer exists.', 'permanent');
                $summary[$outcome]++;

                continue;
            }
            $endorse = $endorseRows[0];
            // Threads rows never enter the main endorse_refresh_queue (claimBatch excludes
            // platform='Threads'), so the Threads scraper queue id is this row's single,
            // consistent logical observation source — stable across its retries.
            $response['observation_seq'] = (int) ($row['id']);
            $purpose                     = (string) ($row['purpose'] ?? 'daily');
            $applied                     = $purpose === 'daily'
                ? $this->CI->endorse_sync->apply($endorse, $response, (int) ($row['enqueued_by'] ?? 0))
                : $this->CI->endorse_sync->apply_snapshot($endorse, $response, $purpose, (int) ($row['enqueued_by'] ?? 0));
            if (empty($applied['status'])) {
                $outcome = $this->retryOrFail($row, $attemptNo, $workerId, (string) ($applied['msg'] ?? 'Gagal menerapkan statistik Threads.'), (string) ($applied['error_class'] ?? 'transient'));
                $summary[$outcome]++;

                continue;
            }
            $now = date('Y-m-d H:i:s');
            $this->db->update('endorse_refresh_queue', [
                'status'            => 'completed', 'error_message' => null, 'worker_id' => null,
                'active_attempt_id' => null, 'completed_at' => $now,
            ], ['id' => (int) ($row['id'])]);
            $this->finalizeAttempt((int) ($row['id']), $attemptNo, $workerId, 'completed', null, null, $now);
            if ($purpose === 'daily') {
                $this->CI->endorse_sync->update_campaign_parent((int) ($endorse['id_campaign']));
            }
            $summary['completed']++;
        }

        return $summary;
    }

    protected function retryOrFail(array $row, int $attemptNo, string $workerId, string $msg, string $errorClass): string
    {
        $terminal = Endorse_sync::is_terminal_class($errorClass);
        $failed   = $terminal || $attemptNo >= (int) ($row['max_attempts']);
        $now      = date('Y-m-d H:i:s');
        $this->db->update('endorse_refresh_queue', [
            'status'          => $failed ? 'failed' : 'pending', 'error_message' => $msg,
            'worker_id'       => null, 'claim_owner' => null, 'active_attempt_id' => null,
            'provider_job_id' => null, 'provider_submitted_at' => null,
            'started_at'      => null, 'completed_at' => $failed ? $now : null,
            'next_attempt_at' => $failed ? null : date('Y-m-d H:i:s', time() + 60),
        ], ['id' => (int) ($row['id'])]);
        $this->finalizeAttempt((int) ($row['id']), $attemptNo, $workerId, $failed ? 'failed' : 'retrying', $errorClass, $msg, $now);

        return $failed ? 'failed' : 'retrying';
    }

    protected function finalizeAttempt(int $queueId, int $attemptNo, string $workerId, string $status, ?string $class, ?string $msg, ?string $finishedAt): void
    {
        $data = ['status' => $status, 'error_class' => $class, 'error_message' => $msg];
        if ($finishedAt !== null) {
            $data['finished_at'] = $finishedAt;
        }
        $this->db->update('endorse_refresh_queue_attempts', $data, ['queue_id' => $queueId, 'attempt_no' => $attemptNo, 'worker_id' => $workerId]);
    }

    protected function attemptWorkerId(int $queueId, int $attemptNo): string
    {
        $row = $this->CI->mymodel->selectWithQuery("SELECT worker_id FROM endorse_refresh_queue_attempts WHERE queue_id = {$queueId} AND attempt_no = {$attemptNo} LIMIT 1");

        return (string) ($row[0]['worker_id'] ?? '');
    }

    protected function workerId(): string
    {
        return sprintf('%08x-%04x-%04x-%04x-%012x', mt_rand(), mt_rand(0, 0xFFFF), mt_rand(0, 0xFFFF), mt_rand(0, 0xFFFF), mt_rand());
    }
}
