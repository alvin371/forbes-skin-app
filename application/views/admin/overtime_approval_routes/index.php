<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09);">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">Rute Approval Lembur</h3>
            <small style="color: rgba(0,0,0,0.45);">Kelola rute persetujuan lembur berdasarkan user dan role</small>
        </div>
        <div style="display: flex; gap: 8px;">
            <?php if ($can_create): ?>
                <a href="<?php echo site_url('admin/overtime-approval-routes/create'); ?>" class="btn btn-primary" style="background-color: #1890ff; border-color: #1890ff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px;">
                    <i class="bi bi-plus"></i> Buat Rute Baru
                </a>
            <?php endif; ?>
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

        <div style="margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <label style="margin-right: 8px; font-size: 14px;">
                    <input type="checkbox" id="showInactive" <?php echo $show_inactive ? 'checked' : ''; ?> onchange="toggleInactive()"> Tampilkan non-aktif
                </label>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table" style="border: 1px solid #f0f0f0; border-collapse: separate; border-spacing: 0;">
                <thead>
                    <tr style="background-color: #fafafa;">
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Kode</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Nama</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Kondisi</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Steps</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Dibuat</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Status</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($routes)): ?>
                        <tr>
                            <td colspan="7" style="padding: 24px; text-align: center; font-size: 14px; color: rgba(0,0,0,0.45);">
                                Belum ada rute approval lembur. Klik "Buat Rute Baru" untuk menambahkan.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($routes as $route): ?>
                            <tr style="border-bottom: 1px solid #f0f0f0;">
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                    <code style="background-color: #f5f5f5; padding: 2px 6px; border-radius: 2px;"><?php echo htmlspecialchars($route['route_code']); ?></code>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.85); font-weight: 500;">
                                    <?php echo htmlspecialchars($route['name']); ?>
                                    <?php if ($route['description']): ?>
                                        <br><small style="color: rgba(0,0,0,0.45);"><?php echo htmlspecialchars(substr($route['description'], 0, 50)); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                    <?php echo $route['scope_count']; ?> kondisi
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                    <?php echo $route['step_count']; ?> step
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                    <?php echo !empty($route['created_at']) ? date('d M Y', strtotime($route['created_at'])) : '-'; ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px;">
                                    <?php if ((int) $route['is_active'] === 1): ?>
                                        <span style="background-color: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f; padding: 0 8px; height: 22px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px;">
                                            Aktif
                                        </span>
                                    <?php else: ?>
                                        <span style="background-color: #fff2f0; color: #ff4d4f; border: 1px solid #ffccc7; padding: 0 8px; height: 22px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px;">
                                            Non-Aktif
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px;">
                                    <div style="display: flex; gap: 8px;">
                                        <?php if ($can_edit): ?>
                                            <a href="<?php echo site_url('admin/overtime-approval-routes/' . $route['id'] . '/edit'); ?>" style="color: #1890ff;" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($can_delete && (int) $route['is_active'] === 1): ?>
                                            <a href="<?php echo site_url('admin/overtime-approval-routes/' . $route['id'] . '/deactivate'); ?>" style="color: #ff4d4f;" title="Nonaktifkan" onclick="return confirm('Nonaktifkan rute ini?');">
                                                <i class="bi bi-x-circle"></i>
                                            </a>
                                        <?php elseif ($can_edit && (int) $route['is_active'] === 0): ?>
                                            <a href="<?php echo site_url('admin/overtime-approval-routes/' . $route['id'] . '/activate'); ?>" style="color: #52c41a;" title="Aktifkan">
                                                <i class="bi bi-check-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($can_delete): ?>
                                            <a href="<?php echo site_url('admin/overtime-approval-routes/' . $route['id'] . '/delete'); ?>" style="color: #8c8c8c;" title="Hapus" onclick="return confirm('Hapus rute ini?');">
                                                <i class="bi bi-trash"></i>
                                            </a>
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

<script>
function toggleInactive() {
    var showInactive = document.getElementById('showInactive').checked;
    if (showInactive) {
        window.location.href = '<?php echo site_url('admin/overtime-approval-routes'); ?>?show_inactive=1';
    } else {
        window.location.href = '<?php echo site_url('admin/overtime-approval-routes'); ?>';
    }
}
</script>
