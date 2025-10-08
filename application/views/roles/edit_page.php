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

    .system-role-warning {
        background-color: #fff7e6;
        border: 1px solid #ffd591;
        color: #ad6800;
        padding: 12px;
        border-radius: 4px;
        margin-bottom: 16px;
    }

    .permission-matrix {
        border: 1px solid #f0f0f0;
        border-radius: 6px;
        overflow: hidden;
    }

    .module-group {
        border-bottom: 1px solid #f0f0f0;
    }

    .module-group:last-child {
        border-bottom: none;
    }

    .module-group-header {
        background: #fafafa;
        padding: 12px 16px;
        cursor: pointer;
        border: none;
        width: 100%;
        text-align: left;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 500;
        transition: background-color 0.2s;
    }

    .module-group-header:hover {
        background: #f0f0f0;
    }

    .module-group-header i {
        transition: transform 0.2s;
    }

    .module-group-header.collapsed i {
        transform: rotate(-90deg);
    }

    .table th {
        font-weight: 600;
        font-size: 0.875rem;
        color: #262626;
        background-color: #fafafa !important;
    }

    .form-check-input:checked {
        background-color: #1890ff;
        border-color: #1890ff;
    }

    .form-check-input:indeterminate {
        background-color: #faad14;
        border-color: #faad14;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='M6 10h8'/%3e%3c/svg%3e");
    }

    .permission-global-controls {
        background: linear-gradient(135deg, #f6f9fc 0%, #eef2f7 100%);
        border: 1px solid #d9d9d9;
    }

    .table-secondary {
        background-color: #f8f9fa !important;
    }

    .text-muted {
        font-size: 0.875rem;
    }

    td .text-muted {
        font-size: 1rem;
        opacity: 0.5;
    }

    .module-info small {
        font-style: italic;
        color: #6c757d;
    }
</style>

<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-pencil-square me-2"></i>Edit Role: <?= htmlspecialchars($data['display_name']) ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (in_array($data['name'], ['super_admin', 'admin', 'employee'])): ?>
                        <div class="system-role-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>System Role:</strong> This is a system role. Some fields may be restricted from editing.
                        </div>
                    <?php endif; ?>

                    <form id="roleForm" onsubmit="submitRole(event)">
                        <input type="hidden" name="id" value="<?= $data['id'] ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Role Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="dt[name]" 
                                           value="<?= htmlspecialchars($data['name']) ?>"
                                           <?= in_array($data['name'], ['super_admin', 'admin', 'employee']) ? 'readonly' : '' ?>
                                           required pattern="[a-z_]+" 
                                           title="Use lowercase letters and underscores only">
                                    <small class="form-text text-muted">Use lowercase with underscores (e.g., senior_developer)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="display_name" class="form-label">Display Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="display_name" name="dt[display_name]" 
                                           value="<?= htmlspecialchars($data['display_name']) ?>"
                                           required placeholder="e.g., Senior Developer">
                                    <small class="form-text text-muted">Human-readable name for display</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="is_active" class="form-label">Status</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="dt[is_active]" 
                                               value="1" <?= $data['is_active'] ? 'checked' : '' ?>
                                               <?= in_array($data['name'], ['super_admin', 'admin', 'employee']) ? 'disabled' : '' ?>>
                                        <label class="form-check-label" for="is_active">
                                            Active
                                        </label>
                                    </div>
                                    <?php if (in_array($data['name'], ['super_admin', 'admin', 'employee'])): ?>
                                        <input type="hidden" name="dt[is_active]" value="<?= $data['is_active'] ?>">
                                    <?php endif; ?>
                                    <small class="form-text text-muted">Only active roles can be assigned to users</small>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="dt[description]" rows="3"
                                      placeholder="Describe the role's responsibilities and scope..."><?= htmlspecialchars($data['description'] ?? '') ?></textarea>
                            <small class="form-text text-muted">Optional description of the role</small>
                        </div>

                        <!-- Permission Matrix Section -->
                        <div class="mt-4">
                            <h6 class="mb-3">
                                <i class="bi bi-shield-lock me-2"></i>Permission Matrix
                            </h6>
                            
                            <!-- Global Controls -->
                            <div class="permission-global-controls mb-3 p-3 bg-light border rounded">
                                <div class="row align-items-center">
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="master-check-all">
                                            <label class="form-check-label fw-bold" for="master-check-all">
                                                Select All Permissions
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <?php
                                        // Get all unique permissions across all modules
                                        $all_permissions = [];
                                        foreach ($module_groups as $group_modules) {
                                            foreach ($group_modules as $module) {
                                                $module_perms = $module_permissions[$module['name']] ?? ['view'];
                                                $all_permissions = array_unique(array_merge($all_permissions, $module_perms));
                                            }
                                        }
                                        
                                        $permission_labels = [
                                            'view' => 'All View',
                                            'create' => 'All Create', 
                                            'edit' => 'All Update',
                                            'delete' => 'All Delete',
                                            'approve' => 'All Approve'
                                        ];
                                        ?>
                                        <div class="d-flex gap-3 flex-wrap">
                                            <?php foreach ($all_permissions as $permission): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input column-check-all" type="checkbox" id="all-<?= $permission ?>" data-permission="<?= $permission ?>">
                                                    <label class="form-check-label" for="all-<?= $permission ?>"><?= $permission_labels[$permission] ?? 'All ' . ucfirst($permission) ?></label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="permission-matrix">
                                <?php foreach ($module_groups as $group_name => $modules): ?>
                                    <?php if (!empty($modules)): ?>
                                        <div class="module-group mb-0">
                                            <div class="module-group-header" data-bs-toggle="collapse" data-bs-target="#group-<?= str_replace(' ', '-', strtolower($group_name)) ?>" aria-expanded="true">
                                                <span><i class="bi bi-chevron-down me-2"></i><?= $group_name ?></span>
                                                <small class="text-muted"><?= count($modules) ?> modules</small>
                                            </div>
                                            <div id="group-<?= str_replace(' ', '-', strtolower($group_name)) ?>" class="collapse show">
                                                <div class="table-responsive">
                                                    <?php
                                                    // Get unique permissions across all modules in this group
                                                    $group_permissions = [];
                                                    foreach ($modules as $module) {
                                                        $module_perms = $module_permissions[$module['name']] ?? ['view'];
                                                        $group_permissions = array_unique(array_merge($group_permissions, $module_perms));
                                                    }
                                                    
                                                    // Define permission labels
                                                    $permission_labels = [
                                                        'view' => 'View',
                                                        'create' => 'Create', 
                                                        'edit' => 'Update',
                                                        'delete' => 'Delete',
                                                        'approve' => 'Approve'
                                                    ];
                                                    
                                                    // Calculate column widths dynamically
                                                    $permission_count = count($group_permissions);
                                                    $module_width = 20;
                                                    $all_width = 18;
                                                    $permission_width = intval((100 - $module_width - $all_width) / $permission_count);
                                                    ?>
                                                    <table class="table table-sm table-bordered mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th width="<?= $module_width ?>%">Module</th>
                                                                <?php foreach ($group_permissions as $permission): ?>
                                                                    <th width="<?= $permission_width ?>%" class="text-center"><?= $permission_labels[$permission] ?? ucfirst($permission) ?></th>
                                                                <?php endforeach; ?>
                                                                <th width="<?= $all_width ?>%" class="text-center">All Permissions</th>
                                                            </tr>
                                                            <tr class="table-secondary">
                                                                <td class="text-center">
                                                                    <small class="text-muted">Group Controls</small>
                                                                </td>
                                                                <?php foreach ($group_permissions as $permission): ?>
                                                                    <td class="text-center">
                                                                        <div class="form-check d-flex justify-content-center">
                                                                            <input class="form-check-input group-check-all" type="checkbox" 
                                                                                   data-group="<?= str_replace(' ', '-', strtolower($group_name)) ?>" 
                                                                                   data-permission="<?= $permission ?>"
                                                                                   title="Select/Deselect all <?= $permission_labels[$permission] ?? ucfirst($permission) ?> permissions in this group">
                                                                        </div>
                                                                    </td>
                                                                <?php endforeach; ?>
                                                                <td class="text-center">
                                                                    <div class="form-check d-flex justify-content-center">
                                                                        <input class="form-check-input group-check-all-permissions" type="checkbox" 
                                                                               data-group="<?= str_replace(' ', '-', strtolower($group_name)) ?>"
                                                                               title="Select/Deselect ALL permissions in this group">
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($modules as $module): ?>
                                                                <?php 
                                                                    $current_perm = $current_permissions[$module['id']] ?? array(
                                                                        'can_view' => 0,
                                                                        'can_create' => 0,
                                                                        'can_edit' => 0,
                                                                        'can_delete' => 0,
                                                                        'can_approve' => 0
                                                                    );
                                                                    
                                                                    // Get available permissions for this specific module
                                                                    $module_available_perms = $module_permissions[$module['name']] ?? ['view'];
                                                                ?>
                                                                <tr data-group="<?= str_replace(' ', '-', strtolower($group_name)) ?>" data-module-id="<?= $module['id'] ?>">
                                                                    <td>
                                                                        <i class="<?= $module['icon'] ?? 'bi-gear' ?> me-2"></i>
                                                                        <?= htmlspecialchars($module['display_name']) ?>
                                                                        <?php if (count($module_available_perms) == 1 && $module_available_perms[0] == 'view'): ?>
                                                                            <small class="text-muted">(Read-only)</small>
                                                                        <?php elseif (in_array('approve', $module_available_perms) && count($module_available_perms) == 2): ?>
                                                                            <small class="text-muted">(Approval workflow)</small>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <?php foreach ($group_permissions as $permission): ?>
                                                                        <td class="text-center">
                                                                            <?php if (in_array($permission, $module_available_perms)): ?>
                                                                                <div class="form-check d-flex justify-content-center">
                                                                                    <input class="form-check-input permission-checkbox" type="checkbox" 
                                                                                           name="permissions[<?= $module['id'] ?>][can_<?= $permission ?>]" 
                                                                                           id="perm_<?= $module['id'] ?>_<?= $permission ?>"
                                                                                           data-group="<?= str_replace(' ', '-', strtolower($group_name)) ?>"
                                                                                           data-module-id="<?= $module['id'] ?>"
                                                                                           data-permission="<?= $permission ?>"
                                                                                           <?= $current_perm['can_' . $permission] ? 'checked' : '' ?>>
                                                                                </div>
                                                                            <?php else: ?>
                                                                                <span class="text-muted" title="Not available for this module">—</span>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                    <?php endforeach; ?>
                                                                    <td class="text-center">
                                                                        <?php if (count($module_available_perms) > 1): ?>
                                                                            <div class="form-check d-flex justify-content-center">
                                                                                <input class="form-check-input module-check-all" type="checkbox" 
                                                                                       data-module-id="<?= $module['id'] ?>"
                                                                                       title="Select/Deselect all permissions for this module">
                                                                            </div>
                                                                        <?php else: ?>
                                                                            <span class="text-muted">—</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="<?= base_url() ?>/roles" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check me-1"></i>Update Role
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function submitRole(event) {
    event.preventDefault();
    
    const form = document.getElementById('roleForm');
    const formData = new FormData(form);
    
    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Updating...';
    submitBtn.disabled = true;
    
    $.ajax({
        url: '<?= base_url() ?>/roles/update',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.indexOf('success') !== -1) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Role updated successfully',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = '<?= base_url() ?>/roles';
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
                text: 'An error occurred while updating the role'
            });
            
            // Reset button
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });
}

// Permission Matrix Check-All Functionality
document.addEventListener('DOMContentLoaded', function() {
    // Handle collapsible groups
    const collapseElements = document.querySelectorAll('[data-bs-toggle="collapse"]');
    collapseElements.forEach(function(element) {
        element.addEventListener('click', function() {
            const icon = this.querySelector('i');
            if (icon) {
                icon.classList.toggle('bi-chevron-down');
                icon.classList.toggle('bi-chevron-right');
            }
        });
    });

    // Master check-all functionality
    const masterCheckAll = document.getElementById('master-check-all');
    if (masterCheckAll) {
        masterCheckAll.addEventListener('change', function() {
            const checked = this.checked;

            // Update all permission checkboxes
            document.querySelectorAll('.permission-checkbox').forEach(function(checkbox) {
                checkbox.checked = checked;
            });

            // Update all control checkboxes
            document.querySelectorAll('.column-check-all, .group-check-all, .group-check-all-permissions, .module-check-all').forEach(function(checkbox) {
                checkbox.checked = checked;
            });
        });
    }

    // Column check-all functionality (All View, All Create, etc.)
    document.querySelectorAll('.column-check-all').forEach(function(columnCheckbox) {
        columnCheckbox.addEventListener('change', function() {
            const permission = this.dataset.permission;
            const checked = this.checked;

            // Update all permission checkboxes of this type
            document.querySelectorAll(`.permission-checkbox[data-permission="${permission}"]`).forEach(function(checkbox) {
                checkbox.checked = checked;
            });

            // Update group-level controls for this permission type
            document.querySelectorAll(`.group-check-all[data-permission="${permission}"]`).forEach(function(groupCheckbox) {
                updateGroupCheckboxState(groupCheckbox);
            });

            // Update master checkbox state
            updateMasterCheckboxState();
        });
    });

    // Group check-all functionality (per permission type per group)
    document.querySelectorAll('.group-check-all').forEach(function(groupCheckbox) {
        groupCheckbox.addEventListener('change', function() {
            const group = this.dataset.group;
            const permission = this.dataset.permission;
            const checked = this.checked;

            // Update all permission checkboxes of this type in this group
            document.querySelectorAll(`.permission-checkbox[data-group="${group}"][data-permission="${permission}"]`).forEach(function(checkbox) {
                checkbox.checked = checked;
            });

            // Update group all permissions checkbox
            updateGroupAllPermissionsState(group);

            // Update column checkbox state
            updateColumnCheckboxState(permission);

            // Update master checkbox state
            updateMasterCheckboxState();
        });
    });

    // Group check-all permissions functionality (all permissions in a group)
    document.querySelectorAll('.group-check-all-permissions').forEach(function(groupAllCheckbox) {
        groupAllCheckbox.addEventListener('change', function() {
            const group = this.dataset.group;
            const checked = this.checked;

            // Update all permission checkboxes in this group
            document.querySelectorAll(`.permission-checkbox[data-group="${group}"]`).forEach(function(checkbox) {
                checkbox.checked = checked;
            });

            // Update all group control checkboxes for this group
            document.querySelectorAll(`.group-check-all[data-group="${group}"]`).forEach(function(checkbox) {
                checkbox.checked = checked;
            });

            // Update module check-all checkboxes in this group
            document.querySelectorAll(`tr[data-group="${group}"] .module-check-all`).forEach(function(checkbox) {
                checkbox.checked = checked;
            });

            // Update column checkbox states
            ['view', 'create', 'edit', 'delete', 'approve'].forEach(function(permission) {
                updateColumnCheckboxState(permission);
            });

            // Update master checkbox state
            updateMasterCheckboxState();
        });
    });

    // Module check-all functionality (all permissions for a single module)
    document.querySelectorAll('.module-check-all').forEach(function(moduleCheckbox) {
        moduleCheckbox.addEventListener('change', function() {
            const moduleId = this.dataset.moduleId;
            const checked = this.checked;

            // Update all permission checkboxes for this module
            document.querySelectorAll(`.permission-checkbox[data-module-id="${moduleId}"]`).forEach(function(checkbox) {
                checkbox.checked = checked;
            });

            // Update group and column states
            const firstPermissionCheckbox = document.querySelector(`.permission-checkbox[data-module-id="${moduleId}"]`);
            if (firstPermissionCheckbox) {
                const group = firstPermissionCheckbox.dataset.group;

                // Update group controls
                ['view', 'create', 'edit', 'delete', 'approve'].forEach(function(permission) {
                    updateGroupCheckboxState(document.querySelector(`.group-check-all[data-group="${group}"][data-permission="${permission}"]`));
                    updateColumnCheckboxState(permission);
                });

                updateGroupAllPermissionsState(group);
            }

            // Update master checkbox state
            updateMasterCheckboxState();
        });
    });

    // Individual permission checkbox changes
    document.querySelectorAll('.permission-checkbox').forEach(function(permissionCheckbox) {
        permissionCheckbox.addEventListener('change', function() {
            const group = this.dataset.group;
            const permission = this.dataset.permission;
            const moduleId = this.dataset.moduleId;

            // Update module check-all state
            updateModuleCheckboxState(moduleId);

            // Update group check-all state for this permission type
            const groupCheckbox = document.querySelector(`.group-check-all[data-group="${group}"][data-permission="${permission}"]`);
            if (groupCheckbox) {
                updateGroupCheckboxState(groupCheckbox);
            }

            // Update group all permissions state
            updateGroupAllPermissionsState(group);

            // Update column check-all state
            updateColumnCheckboxState(permission);

            // Update master checkbox state
            updateMasterCheckboxState();
        });
    });

    // Helper functions
    function updateMasterCheckboxState() {
        const allPermissionCheckboxes = document.querySelectorAll('.permission-checkbox');
        const checkedPermissionCheckboxes = document.querySelectorAll('.permission-checkbox:checked');

        if (masterCheckAll) {
            if (checkedPermissionCheckboxes.length === 0) {
                masterCheckAll.checked = false;
                masterCheckAll.indeterminate = false;
            } else if (checkedPermissionCheckboxes.length === allPermissionCheckboxes.length) {
                masterCheckAll.checked = true;
                masterCheckAll.indeterminate = false;
            } else {
                masterCheckAll.checked = false;
                masterCheckAll.indeterminate = true;
            }
        }
    }

    function updateColumnCheckboxState(permission) {
        const columnCheckbox = document.querySelector(`.column-check-all[data-permission="${permission}"]`);
        if (!columnCheckbox) return;

        const allPermissionCheckboxes = document.querySelectorAll(`.permission-checkbox[data-permission="${permission}"]`);
        const checkedPermissionCheckboxes = document.querySelectorAll(`.permission-checkbox[data-permission="${permission}"]:checked`);

        if (checkedPermissionCheckboxes.length === 0) {
            columnCheckbox.checked = false;
            columnCheckbox.indeterminate = false;
        } else if (checkedPermissionCheckboxes.length === allPermissionCheckboxes.length) {
            columnCheckbox.checked = true;
            columnCheckbox.indeterminate = false;
        } else {
            columnCheckbox.checked = false;
            columnCheckbox.indeterminate = true;
        }
    }

    function updateGroupCheckboxState(groupCheckbox) {
        if (!groupCheckbox) return;

        const group = groupCheckbox.dataset.group;
        const permission = groupCheckbox.dataset.permission;

        const allPermissionCheckboxes = document.querySelectorAll(`.permission-checkbox[data-group="${group}"][data-permission="${permission}"]`);
        const checkedPermissionCheckboxes = document.querySelectorAll(`.permission-checkbox[data-group="${group}"][data-permission="${permission}"]:checked`);

        if (checkedPermissionCheckboxes.length === 0) {
            groupCheckbox.checked = false;
            groupCheckbox.indeterminate = false;
        } else if (checkedPermissionCheckboxes.length === allPermissionCheckboxes.length) {
            groupCheckbox.checked = true;
            groupCheckbox.indeterminate = false;
        } else {
            groupCheckbox.checked = false;
            groupCheckbox.indeterminate = true;
        }
    }

    function updateGroupAllPermissionsState(group) {
        const groupAllCheckbox = document.querySelector(`.group-check-all-permissions[data-group="${group}"]`);
        if (!groupAllCheckbox) return;

        const allPermissionCheckboxes = document.querySelectorAll(`.permission-checkbox[data-group="${group}"]`);
        const checkedPermissionCheckboxes = document.querySelectorAll(`.permission-checkbox[data-group="${group}"]:checked`);

        if (checkedPermissionCheckboxes.length === 0) {
            groupAllCheckbox.checked = false;
            groupAllCheckbox.indeterminate = false;
        } else if (checkedPermissionCheckboxes.length === allPermissionCheckboxes.length) {
            groupAllCheckbox.checked = true;
            groupAllCheckbox.indeterminate = false;
        } else {
            groupAllCheckbox.checked = false;
            groupAllCheckbox.indeterminate = true;
        }
    }

    function updateModuleCheckboxState(moduleId) {
        const moduleCheckbox = document.querySelector(`.module-check-all[data-module-id="${moduleId}"]`);
        if (!moduleCheckbox) return;

        const allPermissionCheckboxes = document.querySelectorAll(`.permission-checkbox[data-module-id="${moduleId}"]`);
        const checkedPermissionCheckboxes = document.querySelectorAll(`.permission-checkbox[data-module-id="${moduleId}"]:checked`);

        if (checkedPermissionCheckboxes.length === 0) {
            moduleCheckbox.checked = false;
            moduleCheckbox.indeterminate = false;
        } else if (checkedPermissionCheckboxes.length === allPermissionCheckboxes.length) {
            moduleCheckbox.checked = true;
            moduleCheckbox.indeterminate = false;
        } else {
            moduleCheckbox.checked = false;
            moduleCheckbox.indeterminate = true;
        }
    }
});
</script>