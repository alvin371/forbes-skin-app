<div class="row">
    <div class="col-md-8">
        <!-- Request Details Card -->
        <div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09); margin-bottom: 16px;">
            <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px;">
                <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">
                    <i class="bi bi-file-earmark-text"></i> Detail Pengajuan Lembur
                </h3>
            </div>
            <div class="card-body" style="padding: 16px;">
                <?php if ($this->session->flashdata('error')): ?>
                    <div class="alert alert-danger" style="padding: 8px 15px; border-radius: 2px; font-size: 14px; background-color: #fff2f0; border: 1px solid #ffccc7; color: #ff4d4f; margin-bottom: 16px;">
                        <i class="bi bi-exclamation-circle"></i> <?php echo $this->session->flashdata('error'); ?>
                    </div>
                <?php endif; ?>

                <div class="row" style="margin-bottom: 16px;">
                    <div class="col-6">
                        <label style="font-size: 12px; color: rgba(0,0,0,0.45);">No. Pengajuan</label>
                        <p style="font-size: 14px; color: rgba(0,0,0,0.85); margin: 0;">
                            <code style="background-color: #f5f5f5; padding: 2px 6px; border-radius: 2px;"><?php echo htmlspecialchars($overtime_request['request_no']); ?></code>
                        </p>
                    </div>
                    <div class="col-6">
                        <label style="font-size: 12px; color: rgba(0,0,0,0.45);">Status</label>
                        <p style="margin: 0;">
                            <?php
                            $statusColors = array(
                                'SUBMITTED' => '#faad14',
                                'IN_REVIEW' => '#1890ff',
                                'APPROVED' => '#52c41a',
                                'REJECTED' => '#ff4d4f',
                                'CANCELLED' => '#8c8c8c',
                                'NEEDS_ROUTE' => '#fa541c',
                            );
                            $color = isset($statusColors[$overtime_request['status']]) ? $statusColors[$overtime_request['status']] : '#d9d9d9';
                            ?>
                            <span style="background-color: <?php echo $color; ?>20; color: <?php echo $color; ?>; border: 1px solid <?php echo $color; ?>80; padding: 2px 8px; border-radius: 2px; font-size: 12px;">
                                <?php echo $overtime_request['status']; ?>
                            </span>
                        </p>
                    </div>
                </div>

                <div class="row" style="margin-bottom: 16px;">
                    <div class="col-12">
                        <label style="font-size: 12px; color: rgba(0,0,0,0.45);">Pemohon</label>
                        <p style="font-size: 14px; color: rgba(0,0,0,0.85); margin: 0;">
                            <strong><?php echo htmlspecialchars($requester['full_name'] ?? '-'); ?></strong>
                            <?php if (!empty($requester['role_text'])): ?>
                                <br><small style="color: rgba(0,0,0,0.45);"><?php echo htmlspecialchars($requester['role_text']); ?></small>
                            <?php endif; ?>
                            <?php if (!empty($requester['position_name'])): ?>
                                <small style="color: rgba(0,0,0,0.45);"> - <?php echo htmlspecialchars($requester['position_name']); ?></small>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <div class="row" style="margin-bottom: 16px;">
                    <div class="col-4">
                        <label style="font-size: 12px; color: rgba(0,0,0,0.45);">Tipe Lembur</label>
                        <p style="font-size: 14px; color: rgba(0,0,0,0.85); margin: 0;"><?php echo htmlspecialchars($overtime_request['overtime_type_name']); ?></p>
                    </div>
                    <div class="col-4">
                        <label style="font-size: 12px; color: rgba(0,0,0,0.45);">Tanggal</label>
                        <p style="font-size: 14px; color: rgba(0,0,0,0.85); margin: 0;">
                            <?php echo date('d M Y', strtotime($overtime_request['overtime_date'])); ?>
                        </p>
                    </div>
                    <div class="col-4">
                        <label style="font-size: 12px; color: rgba(0,0,0,0.45);">Durasi</label>
                        <p style="font-size: 14px; margin: 0;">
                            <span style="background-color: #e6f7ff; color: #1890ff; padding: 2px 8px; border-radius: 10px; font-size: 14px; font-weight: 500;">
                                <?php echo number_format((float) $overtime_request['duration_hours'], 2); ?> jam
                            </span>
                        </p>
                    </div>
                </div>

                <div class="row" style="margin-bottom: 16px;">
                    <div class="col-4">
                        <label style="font-size: 12px; color: rgba(0,0,0,0.45);">Waktu</label>
                        <p style="font-size: 14px; color: rgba(0,0,0,0.85); margin: 0;">
                            <?php echo htmlspecialchars($overtime_request['start_time']); ?> - <?php echo htmlspecialchars($overtime_request['end_time']); ?>
                        </p>
                    </div>
                </div>

                <?php if (!empty($overtime_request['reason'])): ?>
                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 12px; color: rgba(0,0,0,0.45);">Alasan</label>
                        <p style="font-size: 14px; color: rgba(0,0,0,0.65); margin: 0; padding: 8px; background-color: #fafafa; border-radius: 4px;">
                            <?php echo nl2br(htmlspecialchars($overtime_request['reason'])); ?>
                        </p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($overtime_request['attachment_path'])): ?>
                    <?php
                    $attachmentUrl = hrms_attachment_url($overtime_request['attachment_path']);
                    $fileExt = strtolower(pathinfo(parse_url($attachmentUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                    $isImage = in_array($fileExt, array('jpg', 'jpeg', 'png', 'gif', 'webp'));
                    $isPdf = $fileExt === 'pdf';
                    ?>
                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 12px; color: rgba(0,0,0,0.45); margin-bottom: 8px; display: block;">Lampiran</label>
                        <div style="border: 1px solid #d9d9d9; border-radius: 4px; overflow: hidden; background-color: #fafafa;">
                            <?php if ($isImage): ?>
                                <a href="<?php echo $attachmentUrl; ?>" target="_blank" title="Klik untuk memperbesar">
                                    <img src="<?php echo $attachmentUrl; ?>" alt="Lampiran" style="max-width: 100%; max-height: 400px; display: block; margin: 0 auto; cursor: zoom-in;">
                                </a>
                            <?php elseif ($isPdf): ?>
                                <iframe src="<?php echo $attachmentUrl; ?>" style="width: 100%; height: 500px; border: none;"></iframe>
                                <div style="padding: 8px; background-color: #fff; border-top: 1px solid #d9d9d9; text-align: center;">
                                    <a href="<?php echo $attachmentUrl; ?>" target="_blank" style="color: #1890ff; font-size: 12px;">
                                        <i class="bi bi-box-arrow-up-right"></i> Buka di tab baru
                                    </a>
                                </div>
                            <?php else: ?>
                                <div style="padding: 16px; text-align: center;">
                                    <i class="bi bi-file-earmark" style="font-size: 48px; color: #d9d9d9;"></i>
                                    <p style="margin: 8px 0 0; color: rgba(0,0,0,0.45);"><?php echo strtoupper($fileExt); ?> File</p>
                                    <a href="<?php echo $attachmentUrl; ?>" target="_blank" class="btn btn-outline-primary btn-sm" style="margin-top: 8px; border-radius: 2px;">
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Approval Form Card -->
        <?php if ($step['action'] === 'PENDING'): ?>
            <div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09);">
                <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px;">
                    <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">
                        <i class="bi bi-check2-square"></i> Tindakan Approval
                    </h3>
                </div>
                <div class="card-body" style="padding: 16px;">
                    <div class="mb-3">
                        <label class="form-label" style="font-size: 14px; color: rgba(0,0,0,0.85);">Catatan</label>
                        <textarea id="approvalNotes" class="form-control" rows="3" style="border-radius: 2px; font-size: 14px;" placeholder="Tambahkan catatan (wajib untuk penolakan)..."></textarea>
                    </div>

                    <div style="display: flex; gap: 12px;">
                        <form method="post" action="<?php echo site_url('approvals/overtime/approve/' . $step['id']); ?>" style="flex: 1;" id="approveForm">
                            <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                            <input type="hidden" name="notes" id="approveNotesInput">
                            <button type="submit" class="btn btn-success w-100" style="border-radius: 2px; height: 40px;" onclick="document.getElementById('approveNotesInput').value = document.getElementById('approvalNotes').value;">
                                <i class="bi bi-check-lg"></i> Setujui
                            </button>
                        </form>
                        <form method="post" action="<?php echo site_url('approvals/overtime/reject/' . $step['id']); ?>" style="flex: 1;" id="rejectForm" onsubmit="return validateReject();">
                            <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                            <input type="hidden" name="notes" id="rejectNotesInput">
                            <button type="submit" class="btn btn-danger w-100" style="border-radius: 2px; height: 40px;">
                                <i class="bi bi-x-lg"></i> Tolak
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-md-4">
        <!-- Approval Progress Card -->
        <div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09); margin-bottom: 16px;">
            <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px;">
                <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">
                    <i class="bi bi-diagram-3"></i> Progress Approval
                </h3>
            </div>
            <div class="card-body" style="padding: 16px;">
                <?php if (!empty($all_steps)): ?>
                    <div class="approval-timeline">
                        <?php foreach ($all_steps as $idx => $s): ?>
                            <?php
                            $isCurrentStep = isset($progress['current_step']) && $s['step_no'] == $progress['current_step'] && $s['action'] === 'PENDING';
                            $isCompleted = $s['action'] === 'APPROVED';
                            $isRejected = $s['action'] === 'REJECTED';

                            if ($isCompleted) {
                                $stepColor = '#52c41a';
                                $stepBg = '#f6ffed';
                                $icon = 'bi-check-circle-fill';
                            } elseif ($isRejected) {
                                $stepColor = '#ff4d4f';
                                $stepBg = '#fff2f0';
                                $icon = 'bi-x-circle-fill';
                            } elseif ($isCurrentStep) {
                                $stepColor = '#1890ff';
                                $stepBg = '#e6f7ff';
                                $icon = 'bi-arrow-right-circle-fill';
                            } else {
                                $stepColor = '#d9d9d9';
                                $stepBg = '#fafafa';
                                $icon = 'bi-circle';
                            }
                            ?>
                            <div style="display: flex; margin-bottom: 16px; <?php echo $idx < count($all_steps) - 1 ? 'border-left: 2px solid ' . $stepColor . '; margin-left: 11px; padding-left: 20px;' : 'padding-left: 0;'; ?>">
                                <div style="<?php echo $idx < count($all_steps) - 1 ? 'margin-left: -31px;' : ''; ?> margin-right: 12px;">
                                    <i class="bi <?php echo $icon; ?>" style="font-size: 20px; color: <?php echo $stepColor; ?>;"></i>
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-size: 14px; font-weight: 500; color: rgba(0,0,0,0.85);">
                                        Step <?php echo $s['step_no']; ?>: <?php echo htmlspecialchars($s['step_name'] ?? 'Approval'); ?>
                                    </div>
                                    <div style="font-size: 12px; color: rgba(0,0,0,0.45); margin-top: 4px;">
                                        <?php if (!empty($s['approver_name'])): ?>
                                            <?php echo htmlspecialchars($s['approver_name']); ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($s['action'] !== 'PENDING'): ?>
                                        <div style="font-size: 12px; color: <?php echo $stepColor; ?>; margin-top: 4px;">
                                            <?php echo $s['action']; ?>
                                            <?php if (!empty($s['action_at'])): ?>
                                                - <?php echo date('d M Y H:i', strtotime($s['action_at'])); ?>
                                            <?php endif; ?>
                                            <?php if (!empty($s['actual_approver_name'])): ?>
                                                <br>oleh <?php echo htmlspecialchars($s['actual_approver_name']); ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($s['notes'])): ?>
                                            <div style="font-size: 12px; color: rgba(0,0,0,0.65); margin-top: 4px; padding: 4px 8px; background-color: <?php echo $stepBg; ?>; border-radius: 4px;">
                                                "<?php echo htmlspecialchars($s['notes']); ?>"
                                            </div>
                                        <?php endif; ?>
                                    <?php elseif ($isCurrentStep): ?>
                                        <div style="font-size: 12px; color: #1890ff; margin-top: 4px;">
                                            <i class="bi bi-hourglass-split"></i> Menunggu approval...
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: rgba(0,0,0,0.45); font-size: 14px; text-align: center;">Belum ada data approval.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Back Button -->
        <a href="<?php echo site_url('approvals/overtime'); ?>" class="btn btn-secondary w-100" style="border-radius: 2px;">
            <i class="bi bi-arrow-left"></i> Kembali ke Inbox
        </a>
    </div>
</div>

<script>
function validateReject() {
    var notes = document.getElementById('approvalNotes').value.trim();
    if (!notes) {
        alert('Alasan penolakan wajib diisi');
        return false;
    }
    document.getElementById('rejectNotesInput').value = notes;
    return true;
}
</script>
