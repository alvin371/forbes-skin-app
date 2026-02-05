<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09);">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">
                <i class="bi bi-inbox"></i> Inbox Approval Lembur
                <?php if ($pending_count > 0): ?>
                    <span style="background-color: #ff4d4f; color: #fff; padding: 2px 8px; border-radius: 10px; font-size: 12px; margin-left: 8px;"><?php echo $pending_count; ?></span>
                <?php endif; ?>
            </h3>
            <small style="color: rgba(0,0,0,0.45);">Pengajuan lembur yang menunggu persetujuan Anda</small>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="<?php echo site_url('approvals/overtime/history'); ?>" class="btn btn-outline-secondary" style="border-radius: 2px; height: 32px; padding: 4px 15px; font-size: 14px;">
                <i class="bi bi-clock-history"></i> Riwayat
            </a>
        </div>
    </div>

    <div class="card-body" style="padding: 16px;">
        <?php if ($this->session->flashdata('success')): ?>
            <div class="alert alert-success" style="padding: 8px 15px; border-radius: 2px; font-size: 14px; background-color: #f6ffed; border: 1px solid #b7eb8f; color: #52c41a; margin-bottom: 16px;">
                <i class="bi bi-check-circle"></i> <?php echo $this->session->flashdata('success'); ?>
            </div>
        <?php endif; ?>

        <?php if ($this->session->flashdata('error')): ?>
            <div class="alert alert-danger" style="padding: 8px 15px; border-radius: 2px; font-size: 14px; background-color: #fff2f0; border: 1px solid #ffccc7; color: #ff4d4f; margin-bottom: 16px;">
                <i class="bi bi-exclamation-circle"></i> <?php echo $this->session->flashdata('error'); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($pending_approvals)): ?>
            <div style="text-align: center; padding: 48px 0;">
                <i class="bi bi-inbox" style="font-size: 48px; color: #d9d9d9;"></i>
                <p style="margin-top: 16px; color: rgba(0,0,0,0.45); font-size: 14px;">Tidak ada pengajuan lembur yang perlu disetujui saat ini.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table" style="border: 1px solid #f0f0f0; border-collapse: separate; border-spacing: 0;">
                    <thead>
                        <tr style="background-color: #fafafa;">
                            <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">No. Pengajuan</th>
                            <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Pemohon</th>
                            <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Tipe Lembur</th>
                            <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Tanggal</th>
                            <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Waktu</th>
                            <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Durasi</th>
                            <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Step</th>
                            <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_approvals as $approval): ?>
                            <tr style="border-bottom: 1px solid #f0f0f0;">
                                <td style="padding: 12px 8px; font-size: 14px;">
                                    <code style="background-color: #f5f5f5; padding: 2px 6px; border-radius: 2px;"><?php echo htmlspecialchars($approval['request_no']); ?></code>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.85);">
                                    <strong><?php echo htmlspecialchars($approval['requester_name']); ?></strong>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                    <?php echo htmlspecialchars($approval['overtime_type_name']); ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                    <?php echo date('d M Y', strtotime($approval['overtime_date'])); ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                    <?php echo htmlspecialchars($approval['start_time']); ?> - <?php echo htmlspecialchars($approval['end_time']); ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px;">
                                    <span style="background-color: #e6f7ff; color: #1890ff; padding: 2px 8px; border-radius: 10px; font-size: 12px; font-weight: 500;">
                                        <?php echo number_format((float) $approval['duration_hours'], 2); ?> jam
                                    </span>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px;">
                                    <span style="color: rgba(0,0,0,0.65);">
                                        Step <?php echo $approval['current_step']; ?>/<?php echo $approval['total_steps']; ?>
                                    </span>
                                    <?php if (!empty($approval['step_name'])): ?>
                                        <br><small style="color: rgba(0,0,0,0.45);"><?php echo htmlspecialchars($approval['step_name']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px;">
                                    <div style="display: flex; gap: 8px;">
                                        <a href="<?php echo site_url('approvals/overtime/detail/' . $approval['id']); ?>" class="btn btn-outline-primary btn-sm" style="border-radius: 2px; font-size: 12px;">
                                            <i class="bi bi-eye"></i> Review
                                        </a>
                                        <button type="button" class="btn btn-success btn-sm" style="border-radius: 2px; font-size: 12px;" onclick="quickApprove(<?php echo $approval['id']; ?>, '<?php echo htmlspecialchars($approval['requester_name'], ENT_QUOTES); ?>')">
                                            <i class="bi bi-check"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" style="border-radius: 2px; font-size: 12px;" onclick="quickReject(<?php echo $approval['id']; ?>, '<?php echo htmlspecialchars($approval['requester_name'], ENT_QUOTES); ?>')">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Approve Modal -->
<div class="modal fade" id="quickApproveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 2px;">
            <div class="modal-header" style="border-bottom: 1px solid #f0f0f0; padding: 16px; background-color: #f6ffed;">
                <h5 class="modal-title" style="font-size: 16px; font-weight: 500; color: #52c41a;"><i class="bi bi-check-circle"></i> Setujui Pengajuan Lembur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="quickApproveForm">
                <div class="modal-body" style="padding: 16px;">
                    <p>Anda akan menyetujui pengajuan lembur dari <strong id="approveRequesterName"></strong>.</p>
                    <input type="hidden" id="approveStepId">
                    <div class="mb-3">
                        <label class="form-label" style="font-size: 14px;">Catatan (opsional)</label>
                        <textarea id="approveNotes" class="form-control" rows="2" style="border-radius: 2px; font-size: 14px;" placeholder="Tambahkan catatan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #f0f0f0; padding: 12px 16px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 2px;">Batal</button>
                    <button type="submit" class="btn btn-success" style="border-radius: 2px;"><i class="bi bi-check"></i> Setujui</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Quick Reject Modal -->
<div class="modal fade" id="quickRejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 2px;">
            <div class="modal-header" style="border-bottom: 1px solid #f0f0f0; padding: 16px; background-color: #fff2f0;">
                <h5 class="modal-title" style="font-size: 16px; font-weight: 500; color: #ff4d4f;"><i class="bi bi-x-circle"></i> Tolak Pengajuan Lembur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="quickRejectForm">
                <div class="modal-body" style="padding: 16px;">
                    <p>Anda akan menolak pengajuan lembur dari <strong id="rejectRequesterName"></strong>.</p>
                    <input type="hidden" id="rejectStepId">
                    <div class="mb-3">
                        <label class="form-label" style="font-size: 14px;">Alasan Penolakan <span style="color: #ff4d4f;">*</span></label>
                        <textarea id="rejectNotes" class="form-control" rows="3" style="border-radius: 2px; font-size: 14px;" placeholder="Jelaskan alasan penolakan..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #f0f0f0; padding: 12px 16px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 2px;">Batal</button>
                    <button type="submit" class="btn btn-danger" style="border-radius: 2px;"><i class="bi bi-x"></i> Tolak</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var approveModal, rejectModal;

document.addEventListener('DOMContentLoaded', function() {
    approveModal = new bootstrap.Modal(document.getElementById('quickApproveModal'));
    rejectModal = new bootstrap.Modal(document.getElementById('quickRejectModal'));

    document.getElementById('quickApproveForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitApprove();
    });

    document.getElementById('quickRejectForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitReject();
    });
});

function quickApprove(stepId, requesterName) {
    document.getElementById('approveStepId').value = stepId;
    document.getElementById('approveRequesterName').textContent = requesterName;
    document.getElementById('approveNotes').value = '';
    approveModal.show();
}

function quickReject(stepId, requesterName) {
    document.getElementById('rejectStepId').value = stepId;
    document.getElementById('rejectRequesterName').textContent = requesterName;
    document.getElementById('rejectNotes').value = '';
    rejectModal.show();
}

function submitApprove() {
    var stepId = document.getElementById('approveStepId').value;
    var notes = document.getElementById('approveNotes').value;

    fetch('<?php echo site_url('approvals/overtime/quick-approve'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'step_id=' + encodeURIComponent(stepId) + '&notes=' + encodeURIComponent(notes) + '&<?php echo $this->security->get_csrf_token_name(); ?>=' + encodeURIComponent('<?php echo $this->security->get_csrf_hash(); ?>')
    })
    .then(response => response.json())
    .then(data => {
        approveModal.hide();
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        approveModal.hide();
        alert('Terjadi kesalahan. Silakan coba lagi.');
    });
}

function submitReject() {
    var stepId = document.getElementById('rejectStepId').value;
    var notes = document.getElementById('rejectNotes').value;

    if (!notes.trim()) {
        alert('Alasan penolakan wajib diisi');
        return;
    }

    fetch('<?php echo site_url('approvals/overtime/quick-reject'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'step_id=' + encodeURIComponent(stepId) + '&notes=' + encodeURIComponent(notes) + '&<?php echo $this->security->get_csrf_token_name(); ?>=' + encodeURIComponent('<?php echo $this->security->get_csrf_hash(); ?>')
    })
    .then(response => response.json())
    .then(data => {
        rejectModal.hide();
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        rejectModal.hide();
        alert('Terjadi kesalahan. Silakan coba lagi.');
    });
}
</script>
