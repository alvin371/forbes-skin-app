<?php
$displayName = trim((string) ($request['requester_name'] ?? ''));
$username = trim((string) ($request['requester_username'] ?? ''));
$email = trim((string) ($request['requester_email'] ?? ''));
$roleText = trim((string) ($request['requester_role_text'] ?? ''));
$position = trim((string) ($request['requester_position'] ?? ''));
$primaryLabel = $displayName !== '' ? $displayName : ($username !== '' ? $username : ('User #' . (int) $request['user_id']));
?>

<div class="row">
    <div class="col-md-8">
        <div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09); margin-bottom: 16px;">
            <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px;">
                <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">
                    <i class="bi bi-file-earmark-text"></i> Detail Pengajuan Cuti
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
                            <code style="background-color: #f5f5f5; padding: 2px 6px; border-radius: 2px;"><?php echo htmlspecialchars($request['request_no']); ?></code>
                        </p>
                    </div>
                    <div class="col-6">
                        <label style="font-size: 12px; color: rgba(0,0,0,0.45);">Status</label>
                        <p style="margin: 0;">
                            <?php
                            $statusColors = array(
                                'SUBMITTED' => '#1890ff',
                                'PENDING_APPROVAL' => '#faad14',
                                'IN_REVIEW' => '#722ed1',
                                'APPROVED' => '#52c41a',
                                'REJECTED' => '#ff4d4f',
                                'CANCELLED' => '#8c8c8c',
                                'NEEDS_ROUTE' => '#fa541c',
                            );
                            $color = isset($statusColors[$request['status']]) ? $statusColors[$request['status']] : '#d9d9d9';
                            ?>
                            <span style="background-color: <?php echo $color; ?>20; color: <?php echo $color; ?>; border: 1px solid <?php echo $color; ?>80; padding: 2px 8px; border-radius: 2px; font-size: 12px;">
                                <?php echo htmlspecialchars(ucwords(strtolower(str_replace('_', ' ', $request['status'])))); ?>
                            </span>
                        </p>
                    </div>
                </div>

                <div class="row" style="margin-bottom: 16px;">
                    <div class="col-12">
                        <label style="font-size: 12px; color: rgba(0,0,0,0.45);">Pemohon</label>
                        <p style="font-size: 14px; color: rgba(0,0,0,0.85); margin: 0;">
                            <strong><?php echo htmlspecialchars($primaryLabel); ?></strong>
                            <?php if ($roleText !== ''): ?>
                                <br><small style="color: rgba(0,0,0,0.45);"><?php echo htmlspecialchars($roleText); ?></small>
                            <?php endif; ?>
                            <?php if ($position !== ''): ?>
                                <small style="color: rgba(0,0,0,0.45);"> - <?php echo htmlspecialchars($position); ?></small>
                            <?php endif; ?>
                            <?php if ($username !== ''): ?>
                                <br><small style="color: rgba(0,0,0,0.45);">@<?php echo htmlspecialchars($username); ?></small>
                            <?php endif; ?>
                            <?php if ($email !== ''): ?>
                                <br><small style="color: rgba(0,0,0,0.45);"><?php echo htmlspecialchars($email); ?></small>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <div class="row" style="margin-bottom: 16px;">
                    <div class="col-4">
                        <label style="font-size: 12px; color: rgba(0,0,0,0.45);">Tipe Cuti</label>
                        <p style="font-size: 14px; color: rgba(0,0,0,0.85); margin: 0;"><?php echo htmlspecialchars($request['leave_type_name']); ?></p>
                    </div>
                    <div class="col-4">
                        <label style="font-size: 12px; color: rgba(0,0,0,0.45);">Tanggal</label>
                        <p style="font-size: 14px; color: rgba(0,0,0,0.85); margin: 0;">
                            <?php echo date('d M Y', strtotime($request['start_date'])); ?> - <?php echo date('d M Y', strtotime($request['end_date'])); ?>
                        </p>
                    </div>
                    <div class="col-4">
                        <label style="font-size: 12px; color: rgba(0,0,0,0.45);">Jumlah Hari</label>
                        <p style="font-size: 14px; margin: 0;">
                            <span style="background-color: #e6f7ff; color: #1890ff; padding: 2px 8px; border-radius: 10px; font-size: 14px; font-weight: 500;">
                                <?php echo (int) $request['days_count']; ?> hari
                            </span>
                        </p>
                    </div>
                </div>

                <?php if (!empty($request['reason'])): ?>
                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 12px; color: rgba(0,0,0,0.45);">Alasan</label>
                        <p style="font-size: 14px; color: rgba(0,0,0,0.65); margin: 0; padding: 8px; background-color: #fafafa; border-radius: 4px;">
                            <?php echo nl2br(htmlspecialchars($request['reason'])); ?>
                        </p>
                    </div>
                <?php endif; ?>

                <div style="margin-bottom: 16px;">
                    <label style="font-size: 12px; color: rgba(0,0,0,0.45); margin-bottom: 8px; display: block;">Lampiran</label>
                    <div style="font-size: 14px; color: rgba(0,0,0,0.65);">
                        <?php if (!empty($request['attachment_path'])): ?>
                            <?php
                            $attachmentPath = $request['attachment_path'];
                            $isAbsolute = preg_match('/^https?:\\/\\//i', $attachmentPath) === 1;
                            $attachmentUrl = $isAbsolute ? $attachmentPath : base_url($attachmentPath);
                            $extension = strtolower(pathinfo(parse_url($attachmentUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                            ?>
                            <div style="border: 1px solid #d9d9d9; border-radius: 4px; overflow: hidden; background-color: #fafafa;">
                                <?php if (in_array($extension, array('jpg', 'jpeg', 'png', 'gif', 'webp'), true)): ?>
                                    <a href="<?php echo htmlspecialchars($attachmentUrl); ?>" target="_blank" title="Klik untuk memperbesar">
                                        <img src="<?php echo htmlspecialchars($attachmentUrl); ?>" alt="Lampiran" style="max-width: 100%; max-height: 400px; display: block; margin: 0 auto; cursor: zoom-in;">
                                    </a>
                                <?php elseif ($extension === 'pdf'): ?>
                                    <iframe src="<?php echo htmlspecialchars($attachmentUrl); ?>" title="Lampiran" style="width: 100%; height: 500px; border: none;"></iframe>
                                    <div style="padding: 8px; background-color: #fff; border-top: 1px solid #d9d9d9; text-align: center;">
                                        <a href="<?php echo htmlspecialchars($attachmentUrl); ?>" target="_blank" style="color: #1890ff; font-size: 12px;">
                                            <i class="bi bi-box-arrow-up-right"></i> Buka di tab baru
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div style="padding: 16px; text-align: center;">
                                        <i class="bi bi-file-earmark" style="font-size: 48px; color: #d9d9d9;"></i>
                                        <p style="margin: 8px 0 0; color: rgba(0,0,0,0.45);"><?php echo htmlspecialchars(strtoupper($extension)); ?> File</p>
                                        <a href="<?php echo htmlspecialchars($attachmentUrl); ?>" target="_blank" class="btn btn-outline-primary btn-sm" style="margin-top: 8px; border-radius: 2px;">
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <span style="color: rgba(0,0,0,0.45);">No attachment</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($request['status'] === 'PENDING_APPROVAL'): ?>
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
                        <form method="post" action="<?php echo site_url('approvals/leaves/' . $request['id'] . '/approve'); ?>" style="flex: 1;" id="approveForm">
                            <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                                <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
                            <?php endif; ?>
                            <input type="hidden" name="notes" id="approveNotesInput">
                            <button type="submit" class="btn btn-success w-100" style="border-radius: 2px; height: 40px;" onclick="document.getElementById('approveNotesInput').value = document.getElementById('approvalNotes').value;">
                                <i class="bi bi-check-lg"></i> Setujui
                            </button>
                        </form>
                        <form method="post" action="<?php echo site_url('approvals/leaves/' . $request['id'] . '/reject'); ?>" style="flex: 1;" id="rejectForm" onsubmit="return validateReject();">
                            <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                                <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
                            <?php endif; ?>
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
                                        Step <?php echo (int) $s['step_no']; ?>
                                    </div>
                                    <div style="font-size: 12px; color: rgba(0,0,0,0.45); margin-top: 4px;">
                                        <?php if (!empty($s['assigned_approver_name'])): ?>
                                            <?php echo htmlspecialchars($s['assigned_approver_name']); ?>
                                            <?php if (!empty($s['assigned_approver_role'])): ?>
                                                <br><small>(<?php echo htmlspecialchars($s['assigned_approver_role']); ?>)</small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span>Approver belum ter-resolve</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($s['action'] !== 'PENDING'): ?>
                                        <div style="font-size: 12px; color: <?php echo $stepColor; ?>; margin-top: 4px;">
                                            <?php echo htmlspecialchars($s['action']); ?>
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
                    <p style="color: rgba(0,0,0,0.45); font-size: 14px; text-align: center;">Belum ada route approval.</p>
                <?php endif; ?>
            </div>
        </div>

        <a href="<?php echo site_url('approvals/leaves'); ?>" class="btn btn-secondary w-100" style="border-radius: 2px;">
            <i class="bi bi-arrow-left"></i> Kembali ke Approval Cuti
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
