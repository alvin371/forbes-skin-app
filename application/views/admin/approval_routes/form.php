<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09);">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">
            <?php echo $is_edit ? 'Edit Rute Approval' : 'Buat Rute Approval Baru'; ?>
        </h3>
        <?php if ($is_edit && $route): ?>
            <small style="color: rgba(0,0,0,0.45);">Mengedit akan membuat versi baru (v<?php echo $route['version'] + 1; ?>)</small>
        <?php endif; ?>
    </div>

    <div class="card-body" style="padding: 24px;">
        <?php if ($this->session->flashdata('error')): ?>
            <div class="alert alert-danger" style="padding: 8px 15px; border-radius: 2px; font-size: 14px; background-color: #fff2f0; border: 1px solid #ffccc7; color: #ff4d4f; margin-bottom: 16px;">
                <i class="bi bi-exclamation-circle"></i> <?php echo $this->session->flashdata('error'); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo $is_edit ? site_url('admin/approval-routes/' . $route['id'] . '/update') : site_url('admin/approval-routes/store'); ?>" id="routeForm">
            <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">

            <!-- Basic Info Section -->
            <div style="margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid #f0f0f0;">
                <h5 style="margin-bottom: 16px; font-size: 14px; font-weight: 500; color: rgba(0,0,0,0.85);">
                    <i class="bi bi-info-circle"></i> Informasi Dasar
                </h5>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label" style="font-size: 14px; color: rgba(0,0,0,0.85);">Kode Rute <span style="color: #ff4d4f;">*</span></label>
                            <input type="text" name="route_code" class="form-control" style="border-radius: 2px; height: 32px; font-size: 14px;"
                                   value="<?php echo htmlspecialchars($route['route_code'] ?? ''); ?>"
                                   placeholder="contoh: MARKETING_SHORT"
                                   <?php echo $is_edit ? 'readonly' : 'required'; ?>>
                            <small style="color: rgba(0,0,0,0.45);">Kode unik untuk identifikasi rute (uppercase, tanpa spasi)</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label" style="font-size: 14px; color: rgba(0,0,0,0.85);">Nama Rute <span style="color: #ff4d4f;">*</span></label>
                            <input type="text" name="name" class="form-control" style="border-radius: 2px; height: 32px; font-size: 14px;"
                                   value="<?php echo htmlspecialchars($route['name'] ?? ''); ?>"
                                   placeholder="contoh: Rute Marketing Cuti Singkat" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label" style="font-size: 14px; color: rgba(0,0,0,0.85);">Berlaku Dari <span style="color: #ff4d4f;">*</span></label>
                            <input type="date" name="effective_from" class="form-control" style="border-radius: 2px; height: 32px; font-size: 14px;"
                                   value="<?php echo htmlspecialchars($route['effective_from'] ?? date('Y-m-d')); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label" style="font-size: 14px; color: rgba(0,0,0,0.85);">Deskripsi</label>
                            <input type="text" name="description" class="form-control" style="border-radius: 2px; height: 32px; font-size: 14px;"
                                   value="<?php echo htmlspecialchars($route['description'] ?? ''); ?>"
                                   placeholder="Deskripsi singkat tentang rute ini">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scopes Section -->
            <div style="margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid #f0f0f0;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h5 style="margin: 0; font-size: 14px; font-weight: 500; color: rgba(0,0,0,0.85);">
                        <i class="bi bi-filter"></i> Kondisi Pencocokan (Scopes)
                    </h5>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="addScope()" style="border-radius: 2px; font-size: 12px;">
                        <i class="bi bi-plus"></i> Tambah Kondisi
                    </button>
                </div>
                <small style="display: block; margin-bottom: 12px; color: rgba(0,0,0,0.45);">
                    Tentukan kondisi untuk mencocokkan rute ini dengan pengajuan cuti. Jika tidak ada kondisi, rute ini menjadi fallback (cocok untuk semua).
                </small>

                <div id="scopesContainer">
                    <?php if (!empty($route['scopes'])): ?>
                        <?php foreach ($route['scopes'] as $idx => $scope): ?>
                            <div class="scope-row" style="display: flex; gap: 12px; margin-bottom: 12px; padding: 12px; background-color: #fafafa; border-radius: 4px;">
                                <select name="scope_type[]" class="form-select" style="flex: 1; border-radius: 2px; height: 32px; font-size: 14px;" onchange="updateScopeValue(this)">
                                    <option value="">-- Pilih Tipe --</option>
                                    <?php foreach ($scope_types as $key => $label): ?>
                                        <option value="<?php echo $key; ?>" <?php echo $scope['scope_type'] == $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="scope_operator[]" class="form-select" style="width: 150px; border-radius: 2px; height: 32px; font-size: 14px;">
                                    <?php foreach ($operators as $key => $label): ?>
                                        <option value="<?php echo $key; ?>" <?php echo $scope['operator'] == $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" name="scope_value[]" class="form-control" style="flex: 1; border-radius: 2px; height: 32px; font-size: 14px;"
                                       value="<?php echo htmlspecialchars($scope['scope_value'] ?? ''); ?>" placeholder="Nilai">
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeScope(this)" style="border-radius: 2px;">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Steps Section -->
            <div style="margin-bottom: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h5 style="margin: 0; font-size: 14px; font-weight: 500; color: rgba(0,0,0,0.85);">
                        <i class="bi bi-list-ol"></i> Langkah Approval
                    </h5>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="addStep()" style="border-radius: 2px; font-size: 12px;">
                        <i class="bi bi-plus"></i> Tambah Step
                    </button>
                </div>
                <small style="display: block; margin-bottom: 12px; color: rgba(0,0,0,0.45);">
                    Tentukan urutan approver. Minimal harus ada 1 step.
                </small>

                <div id="stepsContainer">
                    <?php if (!empty($route['steps'])): ?>
                        <?php foreach ($route['steps'] as $idx => $step): ?>
                            <div class="step-row" style="display: flex; gap: 12px; margin-bottom: 12px; padding: 12px; background-color: #f0f5ff; border-radius: 4px; border-left: 3px solid #1890ff;">
                                <div style="display: flex; align-items: center; width: 60px;">
                                    <span style="font-weight: 500; color: #1890ff;">Step</span>
                                    <input type="number" name="step_no[]" class="form-control" style="width: 50px; border-radius: 2px; height: 32px; font-size: 14px; margin-left: 8px;"
                                           value="<?php echo $step['step_no']; ?>" min="1" readonly>
                                </div>
                                <input type="text" name="step_name[]" class="form-control" style="width: 150px; border-radius: 2px; height: 32px; font-size: 14px;"
                                       value="<?php echo htmlspecialchars($step['step_name'] ?? ''); ?>" placeholder="Nama Step">
                                <select name="approver_type[]" class="form-select approver-type-select" style="width: 150px; border-radius: 2px; height: 32px; font-size: 14px;" onchange="updateApproverValue(this)">
                                    <option value="">-- Tipe --</option>
                                    <?php foreach ($approver_types as $key => $label): ?>
                                        <option value="<?php echo $key; ?>" <?php echo $step['approver_type'] == $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="approver_value[]" class="form-select approver-value-select" style="flex: 1; border-radius: 2px; height: 32px; font-size: 14px;">
                                    <option value="<?php echo htmlspecialchars($step['approver_value']); ?>"><?php echo htmlspecialchars($step['approver_value']); ?></option>
                                </select>
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeStep(this)" style="border-radius: 2px;">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="step-row" style="display: flex; gap: 12px; margin-bottom: 12px; padding: 12px; background-color: #f0f5ff; border-radius: 4px; border-left: 3px solid #1890ff;">
                            <div style="display: flex; align-items: center; width: 60px;">
                                <span style="font-weight: 500; color: #1890ff;">Step</span>
                                <input type="number" name="step_no[]" class="form-control" style="width: 50px; border-radius: 2px; height: 32px; font-size: 14px; margin-left: 8px;"
                                       value="1" min="1" readonly>
                            </div>
                            <input type="text" name="step_name[]" class="form-control" style="width: 150px; border-radius: 2px; height: 32px; font-size: 14px;"
                                   placeholder="Nama Step" value="Atasan Langsung">
                            <select name="approver_type[]" class="form-select approver-type-select" style="width: 150px; border-radius: 2px; height: 32px; font-size: 14px;" onchange="updateApproverValue(this)">
                                <option value="">-- Tipe --</option>
                                <?php foreach ($approver_types as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" <?php echo $key == 'dynamic' ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="approver_value[]" class="form-select approver-value-select" style="flex: 1; border-radius: 2px; height: 32px; font-size: 14px;">
                                <option value="direct_manager" selected>Atasan Langsung</option>
                            </select>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeStep(this)" style="border-radius: 2px;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Form Actions -->
            <div style="display: flex; gap: 12px; justify-content: flex-end; padding-top: 16px; border-top: 1px solid #f0f0f0;">
                <a href="<?php echo site_url('admin/approval-routes'); ?>" class="btn btn-secondary" style="border-radius: 2px; height: 32px; padding: 4px 15px; font-size: 14px;">
                    Batal
                </a>
                <button type="submit" class="btn btn-primary" style="background-color: #1890ff; border-color: #1890ff; border-radius: 2px; height: 32px; padding: 4px 15px; font-size: 14px;">
                    <i class="bi bi-check"></i> <?php echo $is_edit ? 'Simpan Perubahan' : 'Buat Rute'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Data for dropdowns
var roles = <?php echo json_encode($roles); ?>;
var users = <?php echo json_encode($users); ?>;
var leaveTypes = <?php echo json_encode($leave_types); ?>;
var dynamicApprovers = <?php echo json_encode($dynamic_approvers); ?>;
var scopeTypes = <?php echo json_encode($scope_types); ?>;
var operators = <?php echo json_encode($operators); ?>;
var approverTypes = <?php echo json_encode($approver_types); ?>;

function addScope() {
    var container = document.getElementById('scopesContainer');
    var html = `
        <div class="scope-row" style="display: flex; gap: 12px; margin-bottom: 12px; padding: 12px; background-color: #fafafa; border-radius: 4px;">
            <select name="scope_type[]" class="form-select" style="flex: 1; border-radius: 2px; height: 32px; font-size: 14px;" onchange="updateScopeValue(this)">
                <option value="">-- Pilih Tipe --</option>
                ${Object.keys(scopeTypes).map(k => `<option value="${k}">${scopeTypes[k]}</option>`).join('')}
            </select>
            <select name="scope_operator[]" class="form-select" style="width: 150px; border-radius: 2px; height: 32px; font-size: 14px;">
                ${Object.keys(operators).map(k => `<option value="${k}">${operators[k]}</option>`).join('')}
            </select>
            <input type="text" name="scope_value[]" class="form-control" style="flex: 1; border-radius: 2px; height: 32px; font-size: 14px;" placeholder="Nilai">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeScope(this)" style="border-radius: 2px;">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
}

function removeScope(btn) {
    btn.closest('.scope-row').remove();
}

function updateScopeValue(select) {
    var row = select.closest('.scope-row');
    var valueInput = row.querySelector('input[name="scope_value[]"]');
    var scopeType = select.value;

    // For leave_type, show dropdown
    if (scopeType === 'leave_type') {
        var html = `<select name="scope_value[]" class="form-select" style="flex: 1; border-radius: 2px; height: 32px; font-size: 14px;">
            <option value="">-- Pilih --</option>
            ${leaveTypes.map(lt => `<option value="${lt.id}">${lt.name}</option>`).join('')}
        </select>`;
        valueInput.outerHTML = html;
    } else if (scopeType === 'user') {
        var html = `<select name="scope_value[]" class="form-select" style="flex: 1; border-radius: 2px; height: 32px; font-size: 14px;">
            <option value="">-- Pilih --</option>
            ${users.map(u => `<option value="${u.id}">${u.full_name} (${u.role_text || '-'})</option>`).join('')}
        </select>`;
        valueInput.outerHTML = html;
    } else if (scopeType === 'role') {
        var html = `<select name="scope_value[]" class="form-select" style="flex: 1; border-radius: 2px; height: 32px; font-size: 14px;">
            <option value="">-- Pilih --</option>
            ${roles.map(r => `<option value="${r.display_name}">${r.display_name}</option>`).join('')}
        </select>`;
        valueInput.outerHTML = html;
    }
}

var stepCounter = <?php echo !empty($route['steps']) ? count($route['steps']) : 1; ?>;

function addStep() {
    stepCounter++;
    var container = document.getElementById('stepsContainer');
    var html = `
        <div class="step-row" style="display: flex; gap: 12px; margin-bottom: 12px; padding: 12px; background-color: #f0f5ff; border-radius: 4px; border-left: 3px solid #1890ff;">
            <div style="display: flex; align-items: center; width: 60px;">
                <span style="font-weight: 500; color: #1890ff;">Step</span>
                <input type="number" name="step_no[]" class="form-control" style="width: 50px; border-radius: 2px; height: 32px; font-size: 14px; margin-left: 8px;"
                       value="${stepCounter}" min="1" readonly>
            </div>
            <input type="text" name="step_name[]" class="form-control" style="width: 150px; border-radius: 2px; height: 32px; font-size: 14px;" placeholder="Nama Step">
            <select name="approver_type[]" class="form-select approver-type-select" style="width: 150px; border-radius: 2px; height: 32px; font-size: 14px;" onchange="updateApproverValue(this)">
                <option value="">-- Tipe --</option>
                ${Object.keys(approverTypes).map(k => `<option value="${k}">${approverTypes[k]}</option>`).join('')}
            </select>
            <select name="approver_value[]" class="form-select approver-value-select" style="flex: 1; border-radius: 2px; height: 32px; font-size: 14px;">
                <option value="">-- Pilih Tipe Dulu --</option>
            </select>
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeStep(this)" style="border-radius: 2px;">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    renumberSteps();
}

function removeStep(btn) {
    var rows = document.querySelectorAll('.step-row');
    if (rows.length <= 1) {
        alert('Minimal harus ada 1 step approval.');
        return;
    }
    btn.closest('.step-row').remove();
    renumberSteps();
}

function renumberSteps() {
    var rows = document.querySelectorAll('.step-row');
    rows.forEach(function(row, idx) {
        row.querySelector('input[name="step_no[]"]').value = idx + 1;
    });
    stepCounter = rows.length;
}

function updateApproverValue(select) {
    var row = select.closest('.step-row');
    var valueSelect = row.querySelector('.approver-value-select');
    var approverType = select.value;

    var html = '';
    if (approverType === 'user') {
        html = `<option value="">-- Pilih User --</option>` +
            users.map(u => `<option value="${u.id}">${u.full_name} (${u.role_text || '-'})</option>`).join('');
    } else if (approverType === 'role') {
        html = `<option value="">-- Pilih Role --</option>` +
            roles.map(r => `<option value="${r.display_name}">${r.display_name}</option>`).join('');
    } else if (approverType === 'dynamic') {
        html = Object.keys(dynamicApprovers).map(k => `<option value="${k}">${dynamicApprovers[k]}</option>`).join('');
    } else if (approverType === 'position') {
        html = `<option value="">-- Ketik Nama Posisi --</option>`;
        valueSelect.outerHTML = `<input type="text" name="approver_value[]" class="form-control approver-value-select" style="flex: 1; border-radius: 2px; height: 32px; font-size: 14px;" placeholder="Nama Posisi">`;
        return;
    } else {
        html = `<option value="">-- Pilih Tipe Dulu --</option>`;
    }

    // Ensure it's a select element
    if (valueSelect.tagName === 'INPUT') {
        valueSelect.outerHTML = `<select name="approver_value[]" class="form-select approver-value-select" style="flex: 1; border-radius: 2px; height: 32px; font-size: 14px;">${html}</select>`;
    } else {
        valueSelect.innerHTML = html;
    }
}

// Initialize existing approver value selects
document.querySelectorAll('.approver-type-select').forEach(function(select) {
    if (select.value) {
        updateApproverValue(select);
        // Re-select the saved value
        var row = select.closest('.step-row');
        var savedValue = row.querySelector('.approver-value-select').getAttribute('data-saved-value');
        if (savedValue) {
            row.querySelector('.approver-value-select').value = savedValue;
        }
    }
});
</script>
