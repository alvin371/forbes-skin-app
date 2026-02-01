<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09);">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">
                <i class="bi bi-exclamation-triangle" style="color: #fa541c;"></i> Pengajuan Perlu Rute
                <?php if (!empty($requests)): ?>
                    <span style="background-color: #fa541c; color: #fff; padding: 2px 8px; border-radius: 10px; font-size: 12px; margin-left: 8px;"><?php echo count($requests); ?></span>
                <?php endif; ?>
            </h3>
            <small style="color: rgba(0,0,0,0.45);">Pengajuan cuti yang tidak memiliki rute approval otomatis</small>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="<?php echo site_url('approvals/inbox'); ?>" class="btn btn-outline-secondary" style="border-radius: 2px; height: 32px; padding: 4px 15px; font-size: 14px;">
                <i class="bi bi-inbox"></i> Inbox
            </a>
            <a href="<?php echo site_url('admin/approval-routes'); ?>" class="btn btn-outline-primary" style="border-radius: 2px; height: 32px; padding: 4px 15px; font-size: 14px;">
                <i class="bi bi-gear"></i> Kelola Rute
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

        <!-- Info Box -->
        <div style="margin-bottom: 16px; padding: 12px 16px; background-color: #fff7e6; border: 1px solid #ffd591; border-radius: 4px;">
            <p style="margin: 0; font-size: 13px; color: #d46b08;">
                <i class="bi bi-info-circle"></i>
                Pengajuan di bawah ini tidak cocok dengan rute approval yang tersedia. Anda dapat menetapkan rute secara manual, atau membuat rute baru agar pengajuan serupa di masa depan dapat diproses otomatis.
            </p>
        </div>

        <?php if (empty($requests)): ?>
            <div style="text-align: center; padding: 48px 0;">
                <i class="bi bi-check-circle" style="font-size: 48px; color: #52c41a;"></i>
                <p style="margin-top: 16px; color: rgba(0,0,0,0.65); font-size: 14px;">Semua pengajuan sudah memiliki rute approval.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table" style="border: 1px solid #f0f0f0; border-collapse: separate; border-spacing: 0;">
                    <thead>
                        <tr style="background-color: #fafafa;">
                            <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">No. Pengajuan</th>
                            <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Pemohon</th>
                            <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Tipe Cuti</th>
                            <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Tanggal</th>
                            <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Hari</th>
                            <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Diajukan</th>
                            <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr style="border-bottom: 1px solid #f0f0f0;">
                                <td style="padding: 12px 8px; font-size: 14px;">
                                    <code style="background-color: #f5f5f5; padding: 2px 6px; border-radius: 2px;"><?php echo htmlspecialchars($request['request_no']); ?></code>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.85);">
                                    <strong><?php echo htmlspecialchars($request['requester_name']); ?></strong>
                                    <?php if (!empty($request['requester_role'])): ?>
                                        <br><small style="color: rgba(0,0,0,0.45);"><?php echo htmlspecialchars($request['requester_role']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                    <?php echo htmlspecialchars($request['leave_type_name']); ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                    <?php echo date('d M', strtotime($request['start_date'])); ?> - <?php echo date('d M Y', strtotime($request['end_date'])); ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px;">
                                    <span style="background-color: #e6f7ff; color: #1890ff; padding: 2px 8px; border-radius: 10px; font-size: 12px; font-weight: 500;">
                                        <?php echo $request['days_count']; ?> hari
                                    </span>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                    <?php if (!empty($request['submitted_at'])): ?>
                                        <?php echo date('d M Y H:i', strtotime($request['submitted_at'])); ?>
                                    <?php elseif (!empty($request['created_at'])): ?>
                                        <?php echo date('d M Y H:i', strtotime($request['created_at'])); ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px;">
                                    <button type="button" class="btn btn-primary btn-sm" style="border-radius: 2px; font-size: 12px;"
                                            onclick="openAssignModal(<?php echo $request['leave_request_id']; ?>, '<?php echo htmlspecialchars($request['requester_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($request['request_no'], ENT_QUOTES); ?>')">
                                        <i class="bi bi-link-45deg"></i> Tetapkan Rute
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Assign Route Modal -->
<div class="modal fade" id="assignRouteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 2px;">
            <div class="modal-header" style="border-bottom: 1px solid #f0f0f0; padding: 16px; background-color: #e6f7ff;">
                <h5 class="modal-title" style="font-size: 16px; font-weight: 500; color: #1890ff;">
                    <i class="bi bi-link-45deg"></i> Tetapkan Rute Approval
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding: 16px;">
                <p>Tetapkan rute approval untuk pengajuan:</p>
                <div style="background-color: #fafafa; padding: 12px; border-radius: 4px; margin-bottom: 16px;">
                    <strong id="modalRequestNo"></strong><br>
                    <small style="color: rgba(0,0,0,0.45);" id="modalRequesterName"></small>
                </div>

                <input type="hidden" id="assignLeaveRequestId">

                <div class="mb-3">
                    <label class="form-label" style="font-size: 14px;">Pilih Rute Approval <span style="color: #ff4d4f;">*</span></label>
                    <select id="assignRouteSelect" class="form-select" style="border-radius: 2px; font-size: 14px;">
                        <option value="">-- Pilih Rute --</option>
                        <?php foreach ($available_routes as $route): ?>
                            <option value="<?php echo $route['id']; ?>">
                                <?php echo htmlspecialchars($route['name']); ?>
                                (<?php echo htmlspecialchars($route['route_code']); ?> v<?php echo $route['version']; ?>)
                                - <?php echo $route['step_count']; ?> step
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="routePreview" style="display: none; margin-top: 16px; padding: 12px; background-color: #f6ffed; border: 1px solid #b7eb8f; border-radius: 4px;">
                    <h6 style="margin: 0 0 8px 0; font-size: 13px; color: #52c41a;">Langkah Approval:</h6>
                    <div id="routeSteps"></div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f0f0f0; padding: 12px 16px;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 2px;">Batal</button>
                <button type="button" class="btn btn-primary" onclick="submitAssignRoute()" style="border-radius: 2px; background-color: #1890ff; border-color: #1890ff;">
                    <i class="bi bi-check"></i> Tetapkan
                </button>
            </div>
        </div>
    </div>
</div>

<script>
var assignModal;
var routeStepsData = <?php echo json_encode(array_column($available_routes, null, 'id')); ?>;

document.addEventListener('DOMContentLoaded', function() {
    assignModal = new bootstrap.Modal(document.getElementById('assignRouteModal'));

    document.getElementById('assignRouteSelect').addEventListener('change', function() {
        var routeId = this.value;
        var previewDiv = document.getElementById('routePreview');
        var stepsDiv = document.getElementById('routeSteps');

        if (routeId && routeStepsData[routeId]) {
            var route = routeStepsData[routeId];
            var stepsHtml = '';

            if (route.steps && route.steps.length > 0) {
                route.steps.forEach(function(step, idx) {
                    stepsHtml += '<div style="font-size: 12px; margin-bottom: 4px;">';
                    stepsHtml += '<span style="display: inline-block; width: 20px; text-align: center; background-color: #52c41a; color: #fff; border-radius: 50%; font-size: 10px; line-height: 20px; margin-right: 8px;">' + step.step_no + '</span>';
                    stepsHtml += '<strong>' + (step.step_name || 'Step ' + step.step_no) + '</strong>';
                    stepsHtml += ' <span style="color: rgba(0,0,0,0.45);">(' + step.approver_type + ': ' + step.approver_value + ')</span>';
                    stepsHtml += '</div>';
                });
            } else {
                stepsHtml = '<span style="color: rgba(0,0,0,0.45); font-size: 12px;">Loading...</span>';
            }

            stepsDiv.innerHTML = stepsHtml;
            previewDiv.style.display = 'block';
        } else {
            previewDiv.style.display = 'none';
        }
    });
});

function openAssignModal(leaveRequestId, requesterName, requestNo) {
    document.getElementById('assignLeaveRequestId').value = leaveRequestId;
    document.getElementById('modalRequestNo').textContent = requestNo;
    document.getElementById('modalRequesterName').textContent = 'Pemohon: ' + requesterName;
    document.getElementById('assignRouteSelect').value = '';
    document.getElementById('routePreview').style.display = 'none';
    assignModal.show();
}

function submitAssignRoute() {
    var leaveRequestId = document.getElementById('assignLeaveRequestId').value;
    var routeVersionId = document.getElementById('assignRouteSelect').value;

    if (!routeVersionId) {
        alert('Silakan pilih rute approval');
        return;
    }

    fetch('<?php echo site_url('approvals/inbox/assign-route'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'leave_request_id=' + encodeURIComponent(leaveRequestId) +
              '&route_version_id=' + encodeURIComponent(routeVersionId) +
              '&<?php echo $this->security->get_csrf_token_name(); ?>=' + encodeURIComponent('<?php echo $this->security->get_csrf_hash(); ?>')
    })
    .then(response => response.json())
    .then(data => {
        assignModal.hide();
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        assignModal.hide();
        alert('Terjadi kesalahan. Silakan coba lagi.');
    });
}
</script>
