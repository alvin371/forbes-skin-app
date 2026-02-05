<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09);">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">Pengajuan Lembur</h3>
            <small style="color: rgba(0,0,0,0.45);">Ajukan lembur dan pantau status persetujuan</small>
        </div>
        <?php if (!empty($can_create)): ?>
            <a href="<?php echo site_url('overtime/create'); ?>" class="btn btn-primary" style="background-color: #1890ff; border-color: #1890ff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px;">
                <i class="bi bi-plus"></i> Ajukan Lembur
            </a>
        <?php endif; ?>
    </div>

    <div class="card-body" style="padding: 16px;">
        <?php if ($this->session->flashdata('success')): ?>
            <div class="alert alert-success" style="padding: 8px 15px; border-radius: 2px; font-size: 14px; background-color: #f6ffed; border: 1px solid #b7eb8f; color: #52c41a; margin-bottom: 16px;">
                <i class="bi bi-check-circle"></i> <?php echo $this->session->flashdata('success'); ?>
            </div>
        <?php endif; ?>

        <?php if ($this->session->flashdata('warning')): ?>
            <div class="alert alert-warning" style="padding: 8px 15px; border-radius: 2px; font-size: 14px; background-color: #fffbe6; border: 1px solid #ffe58f; color: #faad14; margin-bottom: 16px;">
                <i class="bi bi-exclamation-circle"></i> <?php echo $this->session->flashdata('warning'); ?>
            </div>
        <?php endif; ?>

        <?php if ($this->session->flashdata('error')): ?>
            <div class="alert alert-danger" style="padding: 8px 15px; border-radius: 2px; font-size: 14px; background-color: #fff2f0; border: 1px solid #ffccc7; color: #ff4d4f; margin-bottom: 16px;">
                <i class="bi bi-exclamation-circle"></i> <?php echo $this->session->flashdata('error'); ?>
            </div>
        <?php endif; ?>

        <form method="get" action="<?php echo site_url('overtime'); ?>" style="display: flex; gap: 12px; align-items: flex-end; margin-bottom: 16px; flex-wrap: wrap;">
            <div>
                <label class="form-label" style="font-size: 13px; color: rgba(0,0,0,0.65);">Status</label>
                <select name="status" class="form-select" style="border-radius: 2px; height: 32px; font-size: 14px; min-width: 160px;">
                    <option value="">Semua</option>
                    <?php
                    $statusOptions = array(
                        'SUBMITTED' => 'Submitted',
                        'IN_REVIEW' => 'In Review',
                        'APPROVED' => 'Approved',
                        'REJECTED' => 'Rejected',
                        'CANCELLED' => 'Cancelled',
                    );
                    foreach ($statusOptions as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php echo (!empty($filters['status']) && $filters['status'] === $key) ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label" style="font-size: 13px; color: rgba(0,0,0,0.65);">Bulan</label>
                <input type="month" name="month" class="form-control" style="border-radius: 2px; height: 32px; font-size: 14px; min-width: 160px;"
                       value="<?php echo htmlspecialchars($filters['month'] ?? ''); ?>">
            </div>
            <div>
                <button type="submit" class="btn btn-outline-primary" style="border-radius: 2px; height: 32px; padding: 4px 15px; font-size: 14px;">
                    <i class="bi bi-funnel"></i> Filter
                </button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table" style="border: 1px solid #f0f0f0; border-collapse: separate; border-spacing: 0;">
                <thead>
                    <tr style="background-color: #fafafa;">
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">No. Request</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Tipe</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Tanggal</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Waktu</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Durasi</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Status</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="7" style="padding: 24px; text-align: center; font-size: 14px; color: rgba(0,0,0,0.45);">
                                Belum ada pengajuan lembur.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($requests as $request): ?>
                            <?php
                            $status = $request['status'];
                            $statusStyles = array(
                                'SUBMITTED' => array('bg' => '#fffbe6', 'color' => '#faad14', 'border' => '#ffe58f', 'label' => 'Submitted'),
                                'IN_REVIEW' => array('bg' => '#e6f7ff', 'color' => '#1890ff', 'border' => '#91d5ff', 'label' => 'In Review'),
                                'APPROVED' => array('bg' => '#f6ffed', 'color' => '#52c41a', 'border' => '#b7eb8f', 'label' => 'Approved'),
                                'REJECTED' => array('bg' => '#fff2f0', 'color' => '#ff4d4f', 'border' => '#ffccc7', 'label' => 'Rejected'),
                                'CANCELLED' => array('bg' => '#f5f5f5', 'color' => '#8c8c8c', 'border' => '#d9d9d9', 'label' => 'Cancelled'),
                            );
                            $badge = $statusStyles[$status] ?? array('bg' => '#f5f5f5', 'color' => '#8c8c8c', 'border' => '#d9d9d9', 'label' => $status);
                            ?>
                            <tr style="border-bottom: 1px solid #f0f0f0;">
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                    <code style="background-color: #f5f5f5; padding: 2px 6px; border-radius: 2px;"><?php echo htmlspecialchars($request['request_no']); ?></code>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.85);">
                                    <?php echo htmlspecialchars($request['overtime_type_name'] ?? '-'); ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                    <?php echo date('d M Y', strtotime($request['overtime_date'])); ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                    <?php echo htmlspecialchars($request['start_time']); ?> - <?php echo htmlspecialchars($request['end_time']); ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                    <?php echo number_format((float) $request['duration_hours'], 2); ?> jam
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px;">
                                    <span style="background-color: <?php echo $badge['bg']; ?>; color: <?php echo $badge['color']; ?>; border: 1px solid <?php echo $badge['border']; ?>; padding: 0 8px; height: 22px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px;">
                                        <?php echo $badge['label']; ?>
                                    </span>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px;">
                                    <div style="display: flex; gap: 8px; align-items: center;">
                                        <a href="<?php echo site_url('overtime/' . $request['id']); ?>" style="color: #1890ff;" title="Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (!empty($can_delete) && in_array($status, array('SUBMITTED', 'IN_REVIEW'), true)): ?>
                                            <form method="post" action="<?php echo site_url('overtime/' . $request['id'] . '/cancel'); ?>" style="margin: 0;">
                                                <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
                                                <button type="submit" class="btn btn-link p-0" style="color: #ff4d4f;" onclick="return confirm('Batalkan pengajuan lembur ini?');" title="Batalkan">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
