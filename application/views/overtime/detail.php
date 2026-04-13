<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09);">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">Detail Pengajuan Lembur</h3>
            <small style="color: rgba(0,0,0,0.45);"><?php echo htmlspecialchars($request['request_no']); ?></small>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="<?php echo site_url('overtime'); ?>" class="btn btn-secondary" style="border-radius: 2px; height: 32px; padding: 4px 15px; font-size: 14px;">
                Kembali
            </a>
            <?php if (!empty($can_delete) && in_array($request['status'], array('SUBMITTED', 'IN_REVIEW'), true)): ?>
                <form method="post" action="<?php echo site_url('overtime/' . $request['id'] . '/cancel'); ?>" style="margin: 0;">
                    <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
                    <button type="submit" class="btn btn-danger" style="border-radius: 2px; height: 32px; padding: 4px 15px; font-size: 14px;" onclick="return confirm('Batalkan pengajuan lembur ini?');">
                        Batalkan
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card-body" style="padding: 24px;">
        <?php
        $statusStyles = array(
            'SUBMITTED' => array('bg' => '#fffbe6', 'color' => '#faad14', 'border' => '#ffe58f', 'label' => 'Submitted'),
            'IN_REVIEW' => array('bg' => '#e6f7ff', 'color' => '#1890ff', 'border' => '#91d5ff', 'label' => 'In Review'),
            'APPROVED' => array('bg' => '#f6ffed', 'color' => '#52c41a', 'border' => '#b7eb8f', 'label' => 'Approved'),
            'REJECTED' => array('bg' => '#fff2f0', 'color' => '#ff4d4f', 'border' => '#ffccc7', 'label' => 'Rejected'),
            'CANCELLED' => array('bg' => '#f5f5f5', 'color' => '#8c8c8c', 'border' => '#d9d9d9', 'label' => 'Cancelled'),
        );
        $badge = $statusStyles[$request['status']] ?? array('bg' => '#f5f5f5', 'color' => '#8c8c8c', 'border' => '#d9d9d9', 'label' => $request['status']);
        ?>

        <div style="margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid #f0f0f0;">
            <h5 style="margin-bottom: 16px; font-size: 14px; font-weight: 500; color: rgba(0,0,0,0.85);">
                <i class="bi bi-info-circle"></i> Informasi Pengajuan
            </h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label style="font-size: 13px; color: rgba(0,0,0,0.45);">Tipe Lembur</label>
                        <div style="font-size: 14px; color: rgba(0,0,0,0.85);"><?php echo htmlspecialchars($request['overtime_type_name'] ?? '-'); ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label style="font-size: 13px; color: rgba(0,0,0,0.45);">Status</label>
                        <div>
                            <span style="background-color: <?php echo $badge['bg']; ?>; color: <?php echo $badge['color']; ?>; border: 1px solid <?php echo $badge['border']; ?>; padding: 0 8px; height: 22px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px;">
                                <?php echo $badge['label']; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label style="font-size: 13px; color: rgba(0,0,0,0.45);">Tanggal</label>
                        <div style="font-size: 14px; color: rgba(0,0,0,0.85);"><?php echo date('d M Y', strtotime($request['overtime_date'])); ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label style="font-size: 13px; color: rgba(0,0,0,0.45);">Waktu</label>
                        <div style="font-size: 14px; color: rgba(0,0,0,0.85);"><?php echo htmlspecialchars($request['start_time']); ?> - <?php echo htmlspecialchars($request['end_time']); ?></div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label style="font-size: 13px; color: rgba(0,0,0,0.45);">Durasi</label>
                        <div style="font-size: 14px; color: rgba(0,0,0,0.85);"><?php echo number_format((float) $request['duration_hours'], 2); ?> jam</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label style="font-size: 13px; color: rgba(0,0,0,0.45);">Lampiran</label>
                        <div style="font-size: 14px; color: rgba(0,0,0,0.85);">
                            <?php if (!empty($request['attachment_path'])): ?>
                                <a href="<?php echo hrms_attachment_url($request['attachment_path']); ?>" target="_blank" style="color: #1890ff;">Lihat Lampiran</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label style="font-size: 13px; color: rgba(0,0,0,0.45);">Alasan</label>
                <div style="font-size: 14px; color: rgba(0,0,0,0.85); white-space: pre-wrap;"><?php echo htmlspecialchars($request['reason']); ?></div>
            </div>
        </div>

        <div>
            <h5 style="margin-bottom: 16px; font-size: 14px; font-weight: 500; color: rgba(0,0,0,0.85);">
                <i class="bi bi-diagram-3"></i> Progress Approval
            </h5>
            <?php if (empty($progress) || empty($progress['steps'])): ?>
                <div style="font-size: 14px; color: rgba(0,0,0,0.45);">Belum ada progress approval.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm" style="border: 1px solid #f0f0f0; border-collapse: separate; border-spacing: 0;">
                        <thead>
                            <tr style="background-color: #fafafa;">
                                <th style="padding: 10px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; font-size: 13px;">Step</th>
                                <th style="padding: 10px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; font-size: 13px;">Approver</th>
                                <th style="padding: 10px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; font-size: 13px;">Status</th>
                                <th style="padding: 10px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; font-size: 13px;">Waktu</th>
                                <th style="padding: 10px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; font-size: 13px;">Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($progress['steps'] as $step): ?>
                                <tr>
                                    <td style="padding: 10px 8px; font-size: 13px;"><?php echo (int) $step['step_no']; ?> - <?php echo htmlspecialchars($step['step_name']); ?></td>
                                    <td style="padding: 10px 8px; font-size: 13px;"><?php echo htmlspecialchars($step['approver_name'] ?? '-'); ?></td>
                                    <td style="padding: 10px 8px; font-size: 13px;"><?php echo htmlspecialchars($step['action']); ?></td>
                                    <td style="padding: 10px 8px; font-size: 13px;"><?php echo $step['action_at'] ? date('d M Y H:i', strtotime($step['action_at'])) : '-'; ?></td>
                                    <td style="padding: 10px 8px; font-size: 13px;"><?php echo htmlspecialchars($step['notes'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
