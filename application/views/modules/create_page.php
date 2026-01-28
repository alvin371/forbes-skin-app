<style>
    .card {
        border-radius: 2px;
        border: 1px solid #f0f0f0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.09);
    }

    .card-header {
        background-color: #fafafa;
        border-bottom: 1px solid #f0f0f0;
        padding: 16px;
    }

    .card-body {
        padding: 24px;
    }

    .form-control, .form-select, .form-check-input {
        border-radius: 2px;
        border: 1px solid #d9d9d9;
        transition: all 0.3s;
    }

    .form-control:focus, .form-select:focus {
        border-color: #40a9ff;
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
    }

    .btn {
        border-radius: 2px;
        transition: all 0.3s cubic-bezier(0.645, 0.045, 0.355, 1);
    }

    .btn-primary {
        background-color: #1890ff;
        border-color: #1890ff;
    }

    .btn-primary:hover {
        background-color: #40a9ff;
        border-color: #40a9ff;
    }

    .btn-secondary {
        background-color: #f5f5f5;
        border-color: #d9d9d9;
        color: rgba(0, 0, 0, 0.65);
    }

    .btn-secondary:hover {
        background-color: #e6f7ff;
        border-color: #40a9ff;
        color: #40a9ff;
    }

    .icon-preview {
        font-size: 24px;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #d9d9d9;
        border-radius: 6px;
        background-color: #fafafa;
    }

    .permission-checkbox {
        display: inline-flex;
        align-items: center;
        margin-right: 15px;
        margin-bottom: 5px;
    }

    .permission-checkbox .form-check-input {
        margin-right: 5px;
    }

    .section-divider {
        border-top: 1px solid #f0f0f0;
        margin: 20px 0;
        padding-top: 20px;
    }
</style>

<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-plus-square me-2"></i>Create New Module
                    </h5>
                </div>
                <div class="card-body">
                    <form id="moduleForm" onsubmit="submitModule(event)">
                        <!-- Basic Information Section -->
                        <h6 class="mb-3 text-muted"><i class="bi bi-info-circle me-2"></i>Basic Information</h6>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Module Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="dt[name]" required
                                           placeholder="e.g., user_management, product_catalog"
                                           pattern="[a-z_]+"
                                           title="Use lowercase letters and underscores only">
                                    <small class="form-text text-muted">Use lowercase with underscores (e.g., user_management)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="display_name" class="form-label">Display Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="display_name" name="dt[display_name]" required
                                           placeholder="e.g., User Management">
                                    <small class="form-text text-muted">Human-readable name for display</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="controller" class="form-label">Controller</label>
                                    <input type="text" class="form-control" id="controller" name="dt[controller]"
                                           placeholder="e.g., User, Product">
                                    <small class="form-text text-muted">CodeIgniter controller name (leave empty for parent modules)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="parent_id" class="form-label">Parent Module</label>
                                    <select class="form-select" id="parent_id" name="dt[parent_id]">
                                        <option value="">Root Module</option>
                                        <?php foreach ($parent_modules as $parent): ?>
                                            <option value="<?= $parent['id'] ?>"><?= htmlspecialchars($parent['display_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-text text-muted">Select parent module to create hierarchy</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="icon" class="form-label">Icon</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="icon" name="dt[icon]"
                                               placeholder="bi bi-house" onkeyup="updateIconPreview()">
                                        <div class="input-group-text">
                                            <div class="icon-preview" id="iconPreview">
                                                <i class="bi bi-image"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Bootstrap Icons class (e.g., bi bi-house, bi bi-gear)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sort_order" class="form-label">Sort Order</label>
                                    <input type="number" class="form-control" id="sort_order" name="dt[sort_order]"
                                           value="0" min="0" max="999">
                                    <small class="form-text text-muted">Display order (lower numbers appear first)</small>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar & Navigation Section -->
                        <div class="section-divider">
                            <h6 class="mb-3 text-muted"><i class="bi bi-layout-sidebar me-2"></i>Sidebar & Navigation</h6>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                                    <select class="form-select" id="category" name="dt[category]" required>
                                        <?php foreach ($module_categories as $key => $label): ?>
                                            <option value="<?= htmlspecialchars($key) ?>" <?= $key === 'System Management' ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-text text-muted">Category for grouping in Roles permission matrix</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="url" class="form-label">Sidebar URL</label>
                                    <input type="text" class="form-control" id="url" name="dt[url]"
                                           placeholder="e.g., dashboard or crm?brand=MG">
                                    <small class="form-text text-muted">URL path for sidebar navigation (relative to base_url)</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Show in Sidebar</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="show_in_sidebar" name="dt[show_in_sidebar]" value="1" checked>
                                        <label class="form-check-label" for="show_in_sidebar">
                                            Display this module in the sidebar menu
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">Uncheck to hide from sidebar (API modules, helper modules)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="is_active" class="form-label">Status</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="dt[is_active]" value="1" checked>
                                        <label class="form-check-label" for="is_active">
                                            Active Module
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">Only active modules can be accessed</small>
                                </div>
                            </div>
                        </div>

                        <!-- Permissions Section -->
                        <div class="section-divider">
                            <h6 class="mb-3 text-muted"><i class="bi bi-shield-check me-2"></i>Available Permissions</h6>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Permission Types <span class="text-danger">*</span></label>
                            <p class="text-muted small mb-2">Select which permission types this module supports. These will appear in the Roles permission matrix.</p>
                            <div class="d-flex flex-wrap">
                                <?php foreach ($permission_types as $key => $label): ?>
                                    <div class="permission-checkbox">
                                        <input class="form-check-input" type="checkbox" id="perm_<?= $key ?>"
                                               name="available_permissions[]" value="<?= $key ?>"
                                               <?= $key === 'view' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="perm_<?= $key ?>"><?= $label ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="form-text text-muted">At minimum, "View" permission is required</small>
                        </div>

                        <!-- Permission Examples -->
                        <div class="mt-4">
                            <h6 class="mb-3">
                                <i class="bi bi-lightbulb me-2"></i>Permission Type Examples
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light mb-2">
                                        <div class="card-body p-3">
                                            <h6 class="card-title mb-1">View Only</h6>
                                            <small class="text-muted">Dashboard, Reports, Analytics pages</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light mb-2">
                                        <div class="card-body p-3">
                                            <h6 class="card-title mb-1">Full CRUD</h6>
                                            <small class="text-muted">User, Product, Transaction management</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light mb-2">
                                        <div class="card-body p-3">
                                            <h6 class="card-title mb-1">View + Approve</h6>
                                            <small class="text-muted">Leave Approvals, Recruitment workflows</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light mb-2">
                                        <div class="card-body p-3">
                                            <h6 class="card-title mb-1">View + Create</h6>
                                            <small class="text-muted">Google Meet, Calendar integrations</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="<?= base_url() ?>/modules" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-plus me-1"></i>Create Module
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function submitModule(event) {
    event.preventDefault();

    // Validate at least one permission is selected
    const permissions = document.querySelectorAll('input[name="available_permissions[]"]:checked');
    if (permissions.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Validation Error',
            text: 'Please select at least one permission type (View is recommended)'
        });
        return;
    }

    const form = document.getElementById('moduleForm');
    const formData = new FormData(form);

    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Creating...';
    submitBtn.disabled = true;

    $.ajax({
        url: '<?= base_url() ?>/modules/store',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.indexOf('success') !== -1) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Module created successfully',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = '<?= base_url() ?>/modules';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: response
                });

                // Reset button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred while creating the module'
            });

            // Reset button
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });
}

// Auto-generate module name from display name
document.getElementById('display_name').addEventListener('input', function() {
    const displayName = this.value;
    const moduleName = displayName.toLowerCase()
                                .replace(/[^a-z0-9\s]/g, '')
                                .replace(/\s+/g, '_');
    document.getElementById('name').value = moduleName;
});

// Update icon preview
function updateIconPreview() {
    const iconInput = document.getElementById('icon');
    const iconPreview = document.getElementById('iconPreview');
    const iconClass = iconInput.value.trim();

    if (iconClass) {
        iconPreview.innerHTML = `<i class="${iconClass}"></i>`;
    } else {
        iconPreview.innerHTML = '<i class="bi bi-image"></i>';
    }
}
</script>
