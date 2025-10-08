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
</style>

<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-pencil-square me-2"></i>Edit Module
                    </h5>
                </div>
                <div class="card-body">
                    <form id="moduleForm" onsubmit="submitModule(event)">
                        <input type="hidden" name="id" value="<?= $data['id'] ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Module Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="dt[name]" required
                                           value="<?= htmlspecialchars($data['name']) ?>"
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
                                           value="<?= htmlspecialchars($data['display_name']) ?>"
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
                                           value="<?= htmlspecialchars($data['controller']) ?>"
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
                                            <option value="<?= $parent['id'] ?>" <?= $data['parent_id'] == $parent['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($parent['display_name']) ?>
                                            </option>
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
                                               value="<?= htmlspecialchars($data['icon']) ?>"
                                               placeholder="bi bi-house" onkeyup="updateIconPreview()">
                                        <div class="input-group-text">
                                            <div class="icon-preview" id="iconPreview">
                                                <?php if ($data['icon']): ?>
                                                    <i class="<?= htmlspecialchars($data['icon']) ?>"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-image"></i>
                                                <?php endif; ?>
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
                                           value="<?= $data['sort_order'] ?>" min="0" max="999">
                                    <small class="form-text text-muted">Display order (lower numbers appear first)</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="is_active" class="form-label">Status</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="dt[is_active]" 
                                               value="1" <?= $data['is_active'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="is_active">
                                            Active Module
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">Only active modules appear in menus</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Module Category</label>
                                    <div class="p-2 bg-light rounded">
                                        <small class="text-muted" id="categoryDisplay">Will be determined automatically based on module name</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Module Info -->
                        <div class="mt-4">
                            <h6 class="mb-3">
                                <i class="bi bi-info-circle me-2"></i>Module Information
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body p-3">
                                            <h6 class="card-title">Current ID</h6>
                                            <p class="mb-0 text-muted"><?= $data['id'] ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body p-3">
                                            <h6 class="card-title">Current Status</h6>
                                            <p class="mb-0">
                                                <?php if ($data['is_active']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Inactive</span>
                                                <?php endif; ?>
                                            </p>
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
                                <i class="bi bi-check me-1"></i>Update Module
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
    
    const form = document.getElementById('moduleForm');
    const formData = new FormData(form);
    
    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Updating...';
    submitBtn.disabled = true;
    
    $.ajax({
        url: '<?= base_url() ?>/modules/update',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.indexOf('success') !== -1) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Module updated successfully',
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
                text: 'An error occurred while updating the module'
            });
            
            // Reset button
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });
}

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

// Update category display based on module name
function updateCategoryDisplay(moduleName) {
    const categories = {
        'System Management': ['dashboard', 'profile', 'modules', 'roles', 'settings'],
        'HR Management': ['quest', 'quest_level', 'position', 'benefit', 'milestone', 'employee'],
        'Marketing': ['marketing', 'overview', 'advertiser', 'ads', 'endorsement', 'influencer', 'campaign', 'calendar', 'payment'],
        'Operations': ['transaction', 'marketplace', 'order', 'crm', 'group_wa', 'stock', 'product', 'operasional', 'discount', 'shipping', 'customer'],
        'Reports & Analytics': ['report', 'expense', 'analytics']
    };
    
    let category = 'System Management'; // Default
    
    for (const [categoryName, moduleList] of Object.entries(categories)) {
        if (moduleList.some(keyword => moduleName.includes(keyword))) {
            category = categoryName;
            break;
        }
    }
    
    document.getElementById('categoryDisplay').textContent = category;
}

// Initialize category display and set up event listeners
document.addEventListener('DOMContentLoaded', function() {
    const currentModuleName = '<?= $data['name'] ?>';
    updateCategoryDisplay(currentModuleName);
    
    // Update category when name changes
    document.getElementById('name').addEventListener('input', function() {
        updateCategoryDisplay(this.value);
    });
});
</script>