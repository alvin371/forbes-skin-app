<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09);">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">
                Riwayat Versi: <?php echo htmlspecialchars($route_name); ?>
            </h3>
            <small style="color: rgba(0,0,0,0.45);">Kode: <code><?php echo htmlspecialchars($route_code); ?></code></small>
        </div>
        <a href="<?php echo site_url('admin/approval-routes'); ?>" class="btn btn-secondary" style="border-radius: 2px; height: 32px; padding: 4px 15px; font-size: 14px;">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card-body" style="padding: 16px;">
        <div class="table-responsive">
            <table class="table" style="border: 1px solid #f0f0f0; border-collapse: separate; border-spacing: 0;">
                <thead>
                    <tr style="background-color: #fafafa;">
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Versi</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Nama</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Berlaku</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Dibuat Oleh</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Status</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($versions)): ?>
                        <tr>
                            <td colspan="6" style="padding: 24px; text-align: center; font-size: 14px; color: rgba(0,0,0,0.45);">
                                Tidak ada data versi.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($versions as $idx => $version): ?>
                            <tr style="border-bottom: 1px solid #f0f0f0; <?php echo $idx === 0 ? 'background-color: #f6ffed;' : ''; ?>">
                                <td style="padding: 12px 8px; font-size: 14px;">
                                    <span style="background-color: <?php echo $idx === 0 ? '#52c41a' : '#d9d9d9'; ?>; color: #fff; padding: 2px 10px; border-radius: 10px; font-size: 12px; font-weight: 500;">
                                        v<?php echo $version['version']; ?>
                                    </span>
                                    <?php if ($idx === 0): ?>
                                        <span style="color: #52c41a; font-size: 12px; margin-left: 8px;">(Terbaru)</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.85);">
                                    <?php echo htmlspecialchars($version['name']); ?>
                                    <?php if (!empty($version['has_legacy_department_scope'])): ?>
                                        <span style="margin-left: 8px; background-color: #fffbe6; color: #ad6800; border: 1px solid #ffe58f; padding: 0 8px; height: 22px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px;">
                                            Legacy scope
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($version['description']): ?>
                                        <br><small style="color: rgba(0,0,0,0.45);"><?php echo htmlspecialchars($version['description']); ?></small>
                                    <?php endif; ?>
                                    <?php if (!empty($version['legacy_scope_warning'])): ?>
                                        <br><small style="color: #ad6800;"><?php echo htmlspecialchars($version['legacy_scope_warning']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                    <?php echo date('d M Y', strtotime($version['effective_from'])); ?>
                                    <?php if ($version['effective_to']): ?>
                                        <br><small style="color: rgba(0,0,0,0.45);">s/d <?php echo date('d M Y', strtotime($version['effective_to'])); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                    <?php echo htmlspecialchars($version['created_by_name'] ?? '-'); ?>
                                    <br><small style="color: rgba(0,0,0,0.45);"><?php echo date('d M Y H:i', strtotime($version['created_at'])); ?></small>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px;">
                                    <?php if ((int) $version['is_active'] === 1 && !$version['effective_to']): ?>
                                        <span style="background-color: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f; padding: 0 8px; height: 22px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px;">
                                            Aktif
                                        </span>
                                    <?php elseif ((int) $version['is_active'] === 1): ?>
                                        <span style="background-color: #fff7e6; color: #fa8c16; border: 1px solid #ffd591; padding: 0 8px; height: 22px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px;">
                                            Superseded
                                        </span>
                                    <?php else: ?>
                                        <span style="background-color: #fff2f0; color: #ff4d4f; border: 1px solid #ffccc7; padding: 0 8px; height: 22px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px;">
                                            Non-Aktif
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px;">
                                    <a href="javascript:void(0)" onclick="viewVersionDetail(<?php echo $version['id']; ?>)" style="color: #1890ff;" title="Lihat Detail">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Version Detail Modal -->
<div class="modal fade" id="versionDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 2px;">
            <div class="modal-header" style="border-bottom: 1px solid #f0f0f0; padding: 16px;">
                <h5 class="modal-title" style="font-size: 16px; font-weight: 500;">Detail Versi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding: 16px;">
                <div id="versionDetailContent">
                    <p style="text-align: center; color: rgba(0,0,0,0.45);">Loading...</p>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f0f0f0; padding: 12px 16px;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 2px;">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewVersionDetail(versionId) {
    var modal = new bootstrap.Modal(document.getElementById('versionDetailModal'));
    document.getElementById('versionDetailContent').innerHTML = '<p style="text-align: center; color: rgba(0,0,0,0.45);">Loading...</p>';
    modal.show();

    fetch('<?php echo site_url('admin/approval-routes/detail/'); ?>' + versionId)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            var route = data.route;
            var html = `
                <div style="margin-bottom: 16px;">
                    <h6 style="color: rgba(0,0,0,0.85); margin-bottom: 8px;">Informasi Umum</h6>
                    <table class="table table-sm" style="font-size: 14px;">
                        <tr><td width="150">Kode</td><td><code>${route.route_code}</code></td></tr>
                        <tr><td>Nama</td><td>${route.name}</td></tr>
                        <tr><td>Versi</td><td>v${route.version}</td></tr>
                        <tr><td>Deskripsi</td><td>${route.description || '-'}</td></tr>
                    </table>
                </div>

                <div style="margin-bottom: 16px;">
                    <h6 style="color: rgba(0,0,0,0.85); margin-bottom: 8px;">Kondisi Pencocokan</h6>
                    ${route.has_legacy_department_scope ? `
                        <div style="margin-bottom: 12px; padding: 8px 12px; border-radius: 4px; background-color: #fffbe6; border: 1px solid #ffe58f; color: #ad6800; font-size: 13px;">
                            ${route.legacy_scope_warning}
                        </div>
                    ` : ''}
                    ${route.scopes.length > 0 ? `
                        <table class="table table-sm" style="font-size: 14px;">
                            <thead><tr><th>Tipe</th><th>Operator</th><th>Nilai</th></tr></thead>
                            <tbody>
                                ${route.scopes.map(s => `<tr><td>${s.scope_type}</td><td>${s.operator}</td><td>${s.scope_value || '-'}</td></tr>`).join('')}
                            </tbody>
                        </table>
                    ` : '<p style="color: rgba(0,0,0,0.45);">Tidak ada kondisi (fallback route)</p>'}
                </div>

                <div>
                    <h6 style="color: rgba(0,0,0,0.85); margin-bottom: 8px;">Langkah Approval</h6>
                    <table class="table table-sm" style="font-size: 14px;">
                        <thead><tr><th>Step</th><th>Nama</th><th>Tipe</th><th>Approver</th></tr></thead>
                        <tbody>
                            ${route.steps.map(s => `<tr>
                                <td>${s.step_no}</td>
                                <td>${s.step_name || '-'}</td>
                                <td>${s.approver_type}</td>
                                <td>${s.approver_value}</td>
                            </tr>`).join('')}
                        </tbody>
                    </table>
                </div>
            `;
            document.getElementById('versionDetailContent').innerHTML = html;
        } else {
            document.getElementById('versionDetailContent').innerHTML = '<p style="color: #ff4d4f;">' + data.message + '</p>';
        }
    })
    .catch(error => {
        document.getElementById('versionDetailContent').innerHTML = '<p style="color: #ff4d4f;">Gagal memuat detail.</p>';
    });
}
</script>
