<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * NotificationEvents
 *
 * Central registry of notification event templates. Pure presentation: given an
 * event key + context array, it returns a normalized event payload
 * (title, message, type, related_table, related_id, dedupe_key) that
 * NotificationDispatcher turns into an in-app row (and, later, a push message).
 *
 * Adding a notification for a new service = add a case here + call the dispatcher.
 * No transport / DB logic lives in this class.
 *
 * Event keys use a `domain.event` convention, e.g. `leave.approved`,
 * `overtime.rejected`.
 */
class NotificationEvents
{
    const TYPE_INFO    = 'info';
    const TYPE_SUCCESS = 'success';
    const TYPE_WARNING = 'warning';
    const TYPE_ERROR   = 'error';

    /**
     * Build a normalized event payload.
     *
     * @param string $key Event key (e.g. 'leave.approved')
     * @param array  $ctx Context values referenced by the template
     * @return array|null ['title','message','type','related_table','related_id','dedupe_key']
     *                    or null if the key is unknown.
     */
    public function build($key, array $ctx)
    {
        switch ($key) {

            // ----- Leave -----
            case 'leave.approver_assigned':
                return $this->payload(
                    'Pengajuan Cuti Baru Perlu Disetujui',
                    sprintf(
                        '%s mengajukan %s (%s hari) dari %s sampai %s. Silakan review dan berikan persetujuan.',
                        $ctx['requester_name'] ?? 'Karyawan',
                        $ctx['leave_type_name'] ?? 'Cuti',
                        $ctx['days_count'] ?? '',
                        $this->d($ctx['start_date'] ?? null),
                        $this->d($ctx['end_date'] ?? null)
                    ),
                    self::TYPE_INFO,
                    'leave_requests',
                    $ctx['leave_request_id'] ?? null,
                    "approver_assigned_{$ctx['leave_request_id']}_{$ctx['approver_id']}"
                );

            case 'leave.approved':
                return $this->payload(
                    'Pengajuan Cuti Disetujui',
                    sprintf(
                        'Pengajuan %s Anda dari %s sampai %s (%s hari) telah disetujui.',
                        $ctx['leave_type_name'] ?? 'Cuti',
                        $this->d($ctx['start_date'] ?? null),
                        $this->d($ctx['end_date'] ?? null),
                        $ctx['days_count'] ?? ''
                    ),
                    self::TYPE_SUCCESS,
                    'leave_requests',
                    $ctx['leave_request_id'] ?? null,
                    "request_approved_{$ctx['leave_request_id']}"
                );

            case 'leave.rejected':
                $msg = sprintf(
                    'Pengajuan %s Anda dari %s sampai %s telah ditolak oleh %s.',
                    $ctx['leave_type_name'] ?? 'Cuti',
                    $this->d($ctx['start_date'] ?? null),
                    $this->d($ctx['end_date'] ?? null),
                    $ctx['step_name'] ?? ''
                );
                if (!empty($ctx['reason'])) {
                    $msg .= ' Alasan: ' . $ctx['reason'];
                }
                return $this->payload(
                    'Pengajuan Cuti Ditolak',
                    $msg,
                    self::TYPE_ERROR,
                    'leave_requests',
                    $ctx['leave_request_id'] ?? null,
                    "request_rejected_{$ctx['leave_request_id']}"
                );

            case 'leave.needs_route':
                return $this->payload(
                    'Pengajuan Cuti Memerlukan Penentuan Rute',
                    sprintf(
                        'Pengajuan cuti dari %s tidak memiliki rute approval yang sesuai. Silakan tentukan rute approval secara manual.',
                        $ctx['requester_name'] ?? 'Karyawan'
                    ),
                    self::TYPE_WARNING,
                    'leave_requests',
                    $ctx['leave_request_id'] ?? null,
                    "needs_route_{$ctx['leave_request_id']}_{$ctx['admin_id']}"
                );

            case 'leave.pending_step':
                return $this->payload(
                    'Pengajuan Cuti Sedang Diproses',
                    sprintf(
                        'Pengajuan %s Anda sedang menunggu persetujuan dari %s (%s).',
                        $ctx['leave_type_name'] ?? 'Cuti',
                        $ctx['approver_name'] ?? '',
                        $ctx['step_name'] ?? ''
                    ),
                    self::TYPE_INFO,
                    'leave_requests',
                    $ctx['leave_request_id'] ?? null,
                    "pending_step_{$ctx['leave_request_id']}_{$ctx['step_name']}"
                );

            case 'leave.step_approved':
                return $this->payload(
                    'Tahap Persetujuan Cuti Berhasil',
                    sprintf(
                        'Pengajuan %s Anda telah disetujui oleh %s (Tahap %d dari %d).',
                        $ctx['leave_type_name'] ?? 'Cuti',
                        $ctx['step_name'] ?? '',
                        $ctx['step_no'] ?? 0,
                        $ctx['total_steps'] ?? 0
                    ),
                    self::TYPE_INFO,
                    'leave_requests',
                    $ctx['leave_request_id'] ?? null,
                    "step_approved_{$ctx['leave_request_id']}_{$ctx['step_no']}"
                );

            case 'leave.quota_change':
                $change = $ctx['change_amount'] ?? 0;
                $changeText = $change > 0 ? '+' . $change : $change;
                return $this->payload(
                    'Perubahan Kuota Cuti',
                    sprintf(
                        'Kuota %s Anda berubah %s hari. Sisa kuota: %s hari. %s',
                        $ctx['leave_type_name'] ?? '',
                        $changeText,
                        $ctx['new_remaining'] ?? '',
                        $ctx['reason'] ?? ''
                    ),
                    $change > 0 ? self::TYPE_SUCCESS : self::TYPE_INFO,
                    'leave_quotas',
                    null,
                    null // quota changes are intentionally not deduplicated
                );

            // ----- Overtime -----
            case 'overtime.approver_assigned':
                return $this->payload(
                    'Pengajuan Lembur Baru Perlu Disetujui',
                    sprintf(
                        '%s mengajukan lembur %s pada tanggal %s (%s - %s, %s jam). Silakan review dan berikan persetujuan.',
                        $ctx['requester_name'] ?? 'Karyawan',
                        $ctx['overtime_type_name'] ?? 'Lembur',
                        $this->d($ctx['overtime_date'] ?? null),
                        $ctx['start_time'] ?? '',
                        $ctx['end_time'] ?? '',
                        $ctx['duration_hours'] ?? ''
                    ),
                    self::TYPE_INFO,
                    'overtime_requests',
                    $ctx['overtime_request_id'] ?? null,
                    "overtime_approver_assigned_{$ctx['overtime_request_id']}_{$ctx['approver_id']}"
                );

            case 'overtime.approved':
                return $this->payload(
                    'Pengajuan Lembur Disetujui',
                    sprintf(
                        'Pengajuan lembur %s Anda pada tanggal %s (%s - %s, %s jam) telah disetujui.',
                        $ctx['overtime_type_name'] ?? 'Lembur',
                        $this->d($ctx['overtime_date'] ?? null),
                        $ctx['start_time'] ?? '',
                        $ctx['end_time'] ?? '',
                        $ctx['duration_hours'] ?? ''
                    ),
                    self::TYPE_SUCCESS,
                    'overtime_requests',
                    $ctx['overtime_request_id'] ?? null,
                    "overtime_request_approved_{$ctx['overtime_request_id']}"
                );

            case 'overtime.rejected':
                $msg = sprintf(
                    'Pengajuan lembur %s Anda pada tanggal %s telah ditolak oleh %s.',
                    $ctx['overtime_type_name'] ?? 'Lembur',
                    $this->d($ctx['overtime_date'] ?? null),
                    $ctx['step_name'] ?? ''
                );
                if (!empty($ctx['reason'])) {
                    $msg .= ' Alasan: ' . $ctx['reason'];
                }
                return $this->payload(
                    'Pengajuan Lembur Ditolak',
                    $msg,
                    self::TYPE_ERROR,
                    'overtime_requests',
                    $ctx['overtime_request_id'] ?? null,
                    "overtime_request_rejected_{$ctx['overtime_request_id']}"
                );

            case 'overtime.pending_step':
                return $this->payload(
                    'Pengajuan Lembur Sedang Diproses',
                    sprintf(
                        'Pengajuan lembur %s Anda sedang menunggu persetujuan dari %s (%s).',
                        $ctx['overtime_type_name'] ?? 'Lembur',
                        $ctx['approver_name'] ?? '',
                        $ctx['step_name'] ?? ''
                    ),
                    self::TYPE_INFO,
                    'overtime_requests',
                    $ctx['overtime_request_id'] ?? null,
                    "overtime_pending_step_{$ctx['overtime_request_id']}_{$ctx['step_name']}"
                );

            case 'overtime.step_approved':
                return $this->payload(
                    'Tahap Persetujuan Lembur Berhasil',
                    sprintf(
                        'Pengajuan lembur %s Anda telah disetujui oleh %s (Tahap %d dari %d).',
                        $ctx['overtime_type_name'] ?? 'Lembur',
                        $ctx['step_name'] ?? '',
                        $ctx['step_no'] ?? 0,
                        $ctx['total_steps'] ?? 0
                    ),
                    self::TYPE_INFO,
                    'overtime_requests',
                    $ctx['overtime_request_id'] ?? null,
                    "overtime_step_approved_{$ctx['overtime_request_id']}_{$ctx['step_no']}"
                );

            // ----- System -----
            case 'system.fcm_unavailable':
                return $this->payload(
                    'Push Notifikasi Bermasalah',
                    'Layanan push (FCM) gagal mengirim notifikasi. Silakan cek konfigurasi service account FCM.',
                    self::TYPE_ERROR,
                    null,
                    null,
                    'fcm_unavailable',
                    true // in-app only: push is the thing that's broken, never enqueue it
                );

            default:
                log_message('error', 'NotificationEvents: unknown event key "' . $key . '"');
                return null;
        }
    }

    /**
     * Assemble a normalized payload array.
     *
     * @param bool $noPush When true, the dispatcher writes the in-app row but skips the push
     *                     channel — used for alerts about push itself failing (avoids a loop).
     */
    private function payload($title, $message, $type, $relatedTable, $relatedId, $dedupeKey, $noPush = false)
    {
        return array(
            'title'         => $title,
            'message'       => $message,
            'type'          => $type,
            'related_table' => $relatedTable,
            'related_id'    => $relatedId,
            'dedupe_key'    => $dedupeKey,
            'no_push'       => $noPush,
        );
    }

    /**
     * Format a date as 'd M Y', tolerating null/empty input.
     */
    private function d($date)
    {
        if (empty($date)) {
            return '';
        }
        return date('d M Y', strtotime($date));
    }
}
