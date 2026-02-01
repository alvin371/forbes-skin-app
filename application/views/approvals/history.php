<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09);">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">
                <i class="bi bi-clock-history"></i> Riwayat Approval
            </h3>
            <small style="color: rgba(0,0,0,0.45);">Pengajuan cuti yang telah Anda tindaklanjuti</small>
        </div>
        <a href="<?php echo site_url('approvals/inbox'); ?>" class="btn btn-outline-primary" style="border-radius: 2px; height: 32px; padding: 4px 15px; font-size: 14px;">
            <i class="bi bi-inbox"></i> Kembali ke Inbox
        </a>
    </div>

    <div class="card-body" style="padding: 16px;">
        <?php if (empty($approvals)): ?>
            <div style="text-align: center; padding: 48px 0;">
                <i class="bi bi-clock-history" style="font-size: 48px; color: #d9d9d9;"></i>
                <p style="margin-top: 16px; color: rgba(0,0,0,0.45); font-size: 14px;">Belum ada riwayat approval.</p>
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
                            <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Tindakan</th>
                            <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($approvals as $approval): ?>
                            <?php
                            $isApproved = $approval['action'] === 'APPROVED';
                            $isRejected = $approval['action'] === 'REJECTED';
                            $actionColor = $isApproved ? '#52c41a' : ($isRejected ? '#ff4d4f' : '#d9d9d9');
                            $actionBg = $isApproved ? '#f6ffed' : ($isRejected ? '#fff2f0' : '#fafafa');
                            ?>
                            <tr style="border-bottom: 1px solid #f0f0f0;">
                                <td style="padding: 12px 8px; font-size: 14px;">
                                    <code style="background-color: #f5f5f5; padding: 2px 6px; border-radius: 2px;"><?php echo htmlspecialchars($approval['request_no']); ?></code>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.85);">
                                    <strong><?php echo htmlspecialchars($approval['requester_name']); ?></strong>
                                    <?php if (!empty($approval['requester_role'])): ?>
                                        <br><small style="color: rgba(0,0,0,0.45);"><?php echo htmlspecialchars($approval['requester_role']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                    <?php echo htmlspecialchars($approval['leave_type_name']); ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                    <?php echo date('d M', strtotime($approval['start_date'])); ?> - <?php echo date('d M Y', strtotime($approval['end_date'])); ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px;">
                                    <span style="background-color: #e6f7ff; color: #1890ff; padding: 2px 8px; border-radius: 10px; font-size: 12px; font-weight: 500;">
                                        <?php echo $approval['days_count']; ?> hari
                                    </span>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px;">
                                    <span style="background-color: <?php echo $actionBg; ?>; color: <?php echo $actionColor; ?>; border: 1px solid <?php echo $actionColor; ?>80; padding: 2px 8px; border-radius: 2px; font-size: 12px;">
                                        <?php echo $approval['action']; ?>
                                    </span>
                                    <?php if (!empty($approval['notes'])): ?>
                                        <br><small style="color: rgba(0,0,0,0.45); font-style: italic;" title="<?php echo htmlspecialchars($approval['notes']); ?>">
                                            "<?php echo htmlspecialchars(mb_substr($approval['notes'], 0, 30)); ?><?php echo strlen($approval['notes']) > 30 ? '...' : ''; ?>"
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                    <?php if (!empty($approval['action_at'])): ?>
                                        <?php echo date('d M Y', strtotime($approval['action_at'])); ?>
                                        <br><small style="color: rgba(0,0,0,0.45);"><?php echo date('H:i', strtotime($approval['action_at'])); ?></small>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
