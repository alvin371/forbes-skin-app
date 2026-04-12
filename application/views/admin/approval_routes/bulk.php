<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09);">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">
            Buat Rute Approval Bulk
        </h3>
        <small style="color: rgba(0,0,0,0.45);">Buat beberapa rute sekaligus menggunakan format JSON</small>
    </div>

    <div class="card-body" style="padding: 24px;">
        <?php if ($this->session->flashdata('error')): ?>
            <div class="alert alert-danger" style="padding: 8px 15px; border-radius: 2px; font-size: 14px; background-color: #fff2f0; border: 1px solid #ffccc7; color: #ff4d4f; margin-bottom: 16px;">
                <i class="bi bi-exclamation-circle"></i> <?php echo $this->session->flashdata('error'); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo site_url('admin/approval-routes/bulk-store'); ?>" id="bulkForm">
            <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
            <input type="hidden" name="routes_json" id="routesJsonInput">

            <!-- Template Section -->
            <div style="margin-bottom: 24px; padding: 16px; background-color: #f6ffed; border: 1px solid #b7eb8f; border-radius: 4px;">
                <h6 style="margin-bottom: 12px; color: #52c41a;"><i class="bi bi-lightbulb"></i> Template JSON</h6>
                <p style="font-size: 13px; color: rgba(0,0,0,0.65); margin-bottom: 12px;">
                    Gunakan template berikut sebagai referensi format JSON:
                </p>
                <pre style="background-color: #fff; padding: 12px; border-radius: 4px; font-size: 12px; overflow-x: auto; max-height: 300px;"><code id="templateJson">[
  {
    "route_code": "MARKETING",
    "name": "Rute Marketing",
    "description": "Rute approval untuk role Marketing",
    "effective_from": "<?php echo date('Y-m-d'); ?>",
    "scopes": [
      {"scope_type": "role", "scope_value": "Marketing", "operator": "eq"}
    ],
    "steps": [
      {"step_no": 1, "step_name": "Atasan Langsung", "approver_type": "dynamic", "approver_value": "direct_manager"},
      {"step_no": 2, "step_name": "Head of HR", "approver_type": "role", "approver_value": "Head of HR"}
    ]
  },
  {
    "route_code": "FINANCE_SHORT",
    "name": "Rute Finance Cuti Singkat",
    "description": "Rute untuk Finance dengan cuti <= 3 hari",
    "effective_from": "<?php echo date('Y-m-d'); ?>",
    "scopes": [
      {"scope_type": "role", "scope_value": "Finance", "operator": "eq"},
      {"scope_type": "leave_duration", "scope_value": "3", "operator": "lte"}
    ],
    "steps": [
      {"step_no": 1, "step_name": "Head of Finance", "approver_type": "role", "approver_value": "Head of Finance"}
    ]
  }
]</code></pre>
                <button type="button" class="btn btn-outline-success btn-sm" onclick="useTemplate()" style="border-radius: 2px; margin-top: 8px;">
                    <i class="bi bi-clipboard"></i> Gunakan Template
                </button>
            </div>

            <!-- Input Section -->
            <div style="margin-bottom: 24px;">
                <h6 style="margin-bottom: 12px; color: rgba(0,0,0,0.85);">
                    <i class="bi bi-code-square"></i> Input JSON
                </h6>
                <textarea id="jsonInput" class="form-control" rows="15" style="font-family: monospace; font-size: 13px; border-radius: 2px;"
                          placeholder="Paste JSON array di sini..."></textarea>
                <div style="margin-top: 8px; display: flex; gap: 8px;">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="validateJson()" style="border-radius: 2px;">
                        <i class="bi bi-check2"></i> Validasi JSON
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="formatJson()" style="border-radius: 2px;">
                        <i class="bi bi-code"></i> Format JSON
                    </button>
                </div>
                <div id="validationResult" style="margin-top: 12px;"></div>
            </div>

            <!-- Reference Section -->
            <div style="margin-bottom: 24px; padding: 16px; background-color: #fafafa; border-radius: 4px;">
                <h6 style="margin-bottom: 12px; color: rgba(0,0,0,0.85);">Referensi Nilai</h6>
                <div class="row">
                    <div class="col-md-4">
                        <strong style="font-size: 13px;">Scope Types:</strong>
                        <ul style="font-size: 12px; margin-top: 4px; color: rgba(0,0,0,0.65);">
                            <?php foreach ($scope_types as $key => $label): ?>
                                <li><code><?php echo $key; ?></code> - <?php echo $label; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <strong style="font-size: 13px;">Operators:</strong>
                        <ul style="font-size: 12px; margin-top: 4px; color: rgba(0,0,0,0.65);">
                            <?php foreach ($operators as $key => $label): ?>
                                <li><code><?php echo $key; ?></code> - <?php echo $label; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <strong style="font-size: 13px;">Approver Types:</strong>
                        <ul style="font-size: 12px; margin-top: 4px; color: rgba(0,0,0,0.65);">
                            <?php foreach ($approver_types as $key => $label): ?>
                                <li><code><?php echo $key; ?></code> - <?php echo $label; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <strong style="font-size: 13px; margin-top: 8px; display: block;">Dynamic Approvers:</strong>
                        <ul style="font-size: 12px; margin-top: 4px; color: rgba(0,0,0,0.65);">
                            <?php foreach ($dynamic_approvers as $key => $label): ?>
                                <li><code><?php echo $key; ?></code> - <?php echo $label; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div style="display: flex; gap: 12px; justify-content: flex-end; padding-top: 16px; border-top: 1px solid #f0f0f0;">
                <a href="<?php echo site_url('admin/approval-routes'); ?>" class="btn btn-secondary" style="border-radius: 2px; height: 32px; padding: 4px 15px; font-size: 14px;">
                    Batal
                </a>
                <button type="button" class="btn btn-primary" onclick="submitBulk()" style="background-color: #1890ff; border-color: #1890ff; border-radius: 2px; height: 32px; padding: 4px 15px; font-size: 14px;">
                    <i class="bi bi-check"></i> Buat Rute
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function useTemplate() {
    var template = document.getElementById('templateJson').textContent;
    document.getElementById('jsonInput').value = template;
}

function validateJson() {
    var input = document.getElementById('jsonInput').value;
    var resultDiv = document.getElementById('validationResult');

    try {
        var data = JSON.parse(input);
        if (!Array.isArray(data)) {
            throw new Error('Input harus berupa array JSON');
        }

        var errors = [];
        data.forEach(function(route, idx) {
            if (!route.route_code) errors.push('Route #' + (idx + 1) + ': route_code wajib diisi');
            if (!route.name) errors.push('Route #' + (idx + 1) + ': name wajib diisi');
            if (!route.steps || route.steps.length === 0) errors.push('Route #' + (idx + 1) + ': minimal harus ada 1 step');
        });

        if (errors.length > 0) {
            resultDiv.innerHTML = '<div class="alert alert-warning" style="padding: 8px 12px; font-size: 13px;"><strong>Peringatan:</strong><ul style="margin: 8px 0 0 0; padding-left: 20px;">' +
                errors.map(e => '<li>' + e + '</li>').join('') + '</ul></div>';
        } else {
            resultDiv.innerHTML = '<div class="alert alert-success" style="padding: 8px 12px; font-size: 13px;"><i class="bi bi-check-circle"></i> JSON valid! ' + data.length + ' rute siap dibuat.</div>';
        }
    } catch (e) {
        resultDiv.innerHTML = '<div class="alert alert-danger" style="padding: 8px 12px; font-size: 13px;"><i class="bi bi-exclamation-circle"></i> Error: ' + e.message + '</div>';
    }
}

function formatJson() {
    var input = document.getElementById('jsonInput').value;
    try {
        var data = JSON.parse(input);
        document.getElementById('jsonInput').value = JSON.stringify(data, null, 2);
    } catch (e) {
        alert('JSON tidak valid: ' + e.message);
    }
}

function submitBulk() {
    var input = document.getElementById('jsonInput').value;

    try {
        var data = JSON.parse(input);
        if (!Array.isArray(data) || data.length === 0) {
            alert('Input harus berupa array JSON dengan minimal 1 rute');
            return;
        }

        document.getElementById('routesJsonInput').value = input;
        document.getElementById('bulkForm').submit();
    } catch (e) {
        alert('JSON tidak valid: ' + e.message);
    }
}
</script>
