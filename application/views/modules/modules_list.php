<style>
    /* Ant Design-like Table Styling */
    .table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid #f0f0f0;
        border-radius: 2px;
    }

    .table thead th {
        background-color: #fafafa;
        color: rgba(0, 0, 0, 0.85);
        font-weight: 500;
        text-align: left;
        padding: 12px 8px;
        font-size: 14px;
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.3s ease;
    }

    .table tbody td {
        padding: 12px 8px !important;
        font-size: 14px;
        color: rgba(0, 0, 0, 0.65);
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.3s ease;
    }

    .table-hover tbody tr:hover {
        background-color: #fafafa;
    }

    /* Button Styling - Ant Design-like */
    .btn {
        border-radius: 2px;
        padding: 4px 15px;
        font-size: 14px;
        height: 32px;
        line-height: 1.5;
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

    .btn-outline-primary {
        color: #1890ff;
        border-color: #1890ff;
        background: #fff;
    }

    .btn-outline-primary:hover {
        background-color: #1890ff;
        border-color: #1890ff;
        color: #fff;
    }

    .btn-outline-info {
        color: #13c2c2;
        border-color: #13c2c2;
        background: #fff;
    }

    .btn-outline-info:hover {
        background-color: #13c2c2;
        border-color: #13c2c2;
        color: #fff;
    }

    .btn-outline-danger {
        color: #ff4d4f;
        border-color: #ff4d4f;
        background: #fff;
    }

    .btn-outline-danger:hover {
        background-color: #ff4d4f;
        border-color: #ff4d4f;
        color: #fff;
    }

    /* Badge Styling - Ant Design-like */
    .badge {
        font-size: 12px;
        height: 22px;
        padding: 0 8px;
        line-height: 22px;
        border-radius: 2px;
        font-weight: normal;
    }

    /* Form Styling */
    .form-control, .form-select {
        height: 32px;
        padding: 4px 11px;
        font-size: 14px;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
        transition: all 0.3s;
    }

    .form-control:hover, .form-select:hover {
        border-color: #40a9ff;
    }

    .form-control:focus, .form-select:focus {
        border-color: #40a9ff;
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
    }

    /* Card Styling */
    .card {
        border-radius: 2px;
        border: 1px solid #f0f0f0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.09);
    }

    .modal-content {
        border-radius: 6px;
        border: none;
        box-shadow: 0 9px 28px 8px rgba(0, 0, 0, 0.05), 0 6px 16px 0 rgba(0, 0, 0, 0.08), 0 3px 6px -4px rgba(0, 0, 0, 0.12);
    }
</style>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">
                <i class="bi bi-gear me-2"></i>System Modules Management
            </h5>
            <div class="d-flex gap-2">
                <!-- Bulk actions (initially hidden) -->
                <div id="bulkActions" class="d-flex gap-2" style="display: none;">
                    <button type="button" class="btn btn-danger btn-sm" onclick="bulkDelete()">
                        <i class="bi bi-trash me-1"></i>Delete Selected (<span id="selectedCount">0</span>)
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearSelection()">
                        <i class="bi bi-x me-1"></i>Clear Selection
                    </button>
                </div>
                
                <button type="button" class="btn btn-primary" onclick="showCreateModuleModal()">
                    <i class="bi bi-plus me-1"></i>Add Module
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover" id="modulesTable">
                <thead>
                    <tr>
                        <th width="40">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                            </div>
                        </th>
                        <th>Module</th>
                        <th>Controller</th>
                        <th>Parent</th>
                        <th>Order</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Sub-modules</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($modules as $module): ?>
                        <tr>
                            <td>
                                <?php if ($module['children_count'] == 0): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="module_ids[]" 
                                               value="<?= $module['id'] ?>" onchange="updateBulkActions()">
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if ($module['icon']): ?>
                                        <i class="<?= htmlspecialchars($module['icon']) ?> me-2 text-primary"></i>
                                    <?php else: ?>
                                        <i class="bi bi-circle me-2 text-muted"></i>
                                    <?php endif; ?>
                                    <div>
                                        <strong style="color: rgba(0,0,0,0.85);"><?= htmlspecialchars($module['display_name']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($module['name']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($module['controller']): ?>
                                    <code style="background-color: #f6f8fa; padding: 2px 6px; border-radius: 3px; font-size: 12px;">
                                        <?= htmlspecialchars($module['controller']) ?>
                                    </code>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($module['parent_name']): ?>
                                    <span class="badge bg-light text-dark border">
                                        <?= htmlspecialchars($module['parent_name']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-primary">Root</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?= $module['sort_order'] ?></span>
                            </td>
                            <td class="text-center">
                                <?php if ($module['is_active']): ?>
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle me-1"></i>Active
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger">
                                        <i class="bi bi-x-circle me-1"></i>Inactive
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($module['children_count'] > 0): ?>
                                    <span class="badge bg-info">
                                        <i class="bi bi-diagram-3 me-1"></i>
                                        <?= $module['children_count'] ?> modules
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <!-- View/Detail Button -->
                                    <a href="<?= base_url() ?>/modules/detail?id=<?= $module['id'] ?>" 
                                       class="btn btn-outline-primary" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <!-- Edit Button -->
                                    <button type="button" class="btn btn-outline-primary" onclick="editModule(<?= $module['id'] ?>)" title="Edit Module">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <!-- Permissions Button -->
                                    <button type="button" class="btn btn-outline-info" onclick="viewModulePermissions(<?= $module['id'] ?>)" title="View Permissions">
                                        <i class="bi bi-shield-lock"></i>
                                    </button>
                                    <!-- Delete Button (only if no children) -->
                                    <?php if ($module['children_count'] == 0): ?>
                                        <button type="button" class="btn btn-outline-danger" onclick="deleteModule(<?= $module['id'] ?>)" title="Delete Module">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create/Edit Module Modal -->
<div class="modal fade" id="moduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="moduleModalTitle">
                    <i class="bi bi-gear me-2"></i>Add Module
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="moduleForm">
                <div class="modal-body">
                    <input type="hidden" id="moduleId" name="id">
                    
                    <div class="mb-3">
                        <label for="moduleName" class="form-label">Module Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="moduleName" name="name" required>
                        <div class="form-text">Internal module name (lowercase, underscore separated)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="moduleDisplayName" class="form-label">Display Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="moduleDisplayName" name="display_name" required>
                        <div class="form-text">Name shown in the interface</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="moduleController" class="form-label">Controller</label>
                        <input type="text" class="form-control" id="moduleController" name="controller">
                        <div class="form-text">CodeIgniter controller name (leave empty for parent modules)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="moduleIcon" class="form-label">Icon</label>
                        <input type="text" class="form-control" id="moduleIcon" name="icon" placeholder="bi bi-house">
                        <div class="form-text">Bootstrap Icons class (e.g., bi bi-house)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="moduleParent" class="form-label">Parent Module</label>
                        <select class="form-select" id="moduleParent" name="parent_id">
                            <option value="">Root Module</option>
                            <?php foreach ($modules as $module): ?>
                                <option value="<?= $module['id'] ?>"><?= htmlspecialchars($module['display_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="moduleSortOrder" class="form-label">Sort Order</label>
                        <input type="number" class="form-control" id="moduleSortOrder" name="sort_order" value="0">
                        <div class="form-text">Display order (lower numbers appear first)</div>
                    </div>
                    
                    <div class="mb-3" id="moduleStatusDiv" style="display: none;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="moduleIsActive" name="is_active" checked>
                            <label class="form-check-label" for="moduleIsActive">
                                Active Module
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check me-1"></i>Save Module
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Module Permissions Modal -->
<div class="modal fade" id="modulePermissionsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-shield-lock me-2"></i>Module Permissions
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modulePermissionsContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable with enhanced styling
    $('#modulesTable').DataTable({
        pageLength: 25,
        order: [[3, 'asc']],
        columnDefs: [
            { orderable: false, targets: [6] }
        ],
        language: {
            search: "Search modules:",
            lengthMenu: "Show _MENU_ modules per page",
            info: "Showing _START_ to _END_ of _TOTAL_ modules",
            infoEmpty: "No modules found",
            infoFiltered: "(filtered from _MAX_ total modules)"
        }
    });

    // Form submission
    $('#moduleForm').on('submit', function(e) {
        e.preventDefault();
        
        let formData = $(this).serialize();
        let moduleId = $('#moduleId').val();
        let url = moduleId ? '<?= base_url() ?>modules/update_module' : '<?= base_url() ?>modules/create_module';
        
        // Show loading state
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.html('<i class="bi bi-hourglass-split me-1"></i>Saving...').prop('disabled', true);
        
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        $('#moduleModal').modal('hide');
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                    
                    // Reset button
                    submitBtn.html(originalText).prop('disabled', false);
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to save module'
                });
                
                // Reset button
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
});

function showCreateModuleModal() {
    $('#moduleModalTitle').html('<i class="bi bi-plus me-2"></i>Add Module');
    $('#moduleForm')[0].reset();
    $('#moduleId').val('');
    $('#moduleStatusDiv').hide();
    $('#moduleModal').modal('show');
}

function editModule(moduleId) {
    // Find module data from the table
    let row = $('button[onclick="editModule(' + moduleId + ')"]').closest('tr');
    let moduleData = {
        id: moduleId,
        name: row.find('small.text-muted').text(),
        display_name: row.find('strong').text(),
        controller: row.find('code').text() || '',
        icon: '', // Would need to extract from the icon class
        sort_order: row.find('.badge.bg-secondary').text(),
        is_active: row.find('.badge.bg-success').length > 0
    };
    
    $('#moduleModalTitle').html('<i class="bi bi-pencil me-2"></i>Edit Module');
    $('#moduleId').val(moduleData.id);
    $('#moduleName').val(moduleData.name);
    $('#moduleDisplayName').val(moduleData.display_name);
    $('#moduleController').val(moduleData.controller);
    $('#moduleSortOrder').val(moduleData.sort_order);
    $('#moduleIsActive').prop('checked', moduleData.is_active);
    $('#moduleStatusDiv').show();
    
    $('#moduleModal').modal('show');
}

function deleteModule(moduleId) {
    Swal.fire({
        title: 'Delete Module',
        text: 'Are you sure you want to delete this module? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ff4d4f',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '<?= base_url() ?>modules/delete_module',
                type: 'POST',
                data: { id: moduleId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to delete module'
                    });
                }
            });
        }
    });
}

function viewModulePermissions(moduleId) {
    $('#modulePermissionsContent').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
    
    $.ajax({
        url: '<?= base_url() ?>modules/get_module_permissions/' + moduleId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                let html = '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Role</th><th>Level</th><th class="text-center">View</th><th class="text-center">Create</th><th class="text-center">Edit</th><th class="text-center">Delete</th><th class="text-center">Approve</th></tr></thead><tbody>';
                
                response.data.forEach(function(perm) {
                    html += '<tr>';
                    html += '<td>' + perm.role_name + '</td>';
                    html += '<td><span class="badge bg-primary">Level ' + perm.role_level + '</span></td>';
                    html += '<td class="text-center">' + (perm.can_view ? '<i class="bi bi-check-circle text-success"></i>' : '<i class="bi bi-x-circle text-danger"></i>') + '</td>';
                    html += '<td class="text-center">' + (perm.can_create ? '<i class="bi bi-check-circle text-success"></i>' : '<i class="bi bi-x-circle text-danger"></i>') + '</td>';
                    html += '<td class="text-center">' + (perm.can_edit ? '<i class="bi bi-check-circle text-success"></i>' : '<i class="bi bi-x-circle text-danger"></i>') + '</td>';
                    html += '<td class="text-center">' + (perm.can_delete ? '<i class="bi bi-check-circle text-success"></i>' : '<i class="bi bi-x-circle text-danger"></i>') + '</td>';
                    html += '<td class="text-center">' + (perm.can_approve ? '<i class="bi bi-check-circle text-success"></i>' : '<i class="bi bi-x-circle text-danger"></i>') + '</td>';
                    html += '</tr>';
                });
                
                if (response.data.length === 0) {
                    html += '<tr><td colspan="7" class="text-center text-muted">No role permissions found for this module</td></tr>';
                }
                
                html += '</tbody></table></div>';
                $('#modulePermissionsContent').html(html);
            } else {
                $('#modulePermissionsContent').html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>' + response.message + '</div>');
            }
        },
        error: function() {
            $('#modulePermissionsContent').html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Failed to load module permissions</div>');
        }
    });
    
    $('#modulePermissionsModal').modal('show');
}

// Toggle select all checkboxes
function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('input[name="module_ids[]"]');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    
    updateBulkActions();
}

// Update bulk actions visibility and selected count
function updateBulkActions() {
    const checkboxes = document.querySelectorAll('input[name="module_ids[]"]:checked');
    const selectedCount = checkboxes.length;
    const bulkActions = document.getElementById('bulkActions');
    const selectedCountSpan = document.getElementById('selectedCount');
    
    if (selectedCount > 0) {
        bulkActions.style.display = 'flex';
        selectedCountSpan.textContent = selectedCount;
    } else {
        bulkActions.style.display = 'none';
    }
    
    // Update select all checkbox state
    const allCheckboxes = document.querySelectorAll('input[name="module_ids[]"]');
    const selectAllCheckbox = document.getElementById('selectAll');
    
    if (selectedCount === 0) {
        selectAllCheckbox.indeterminate = false;
        selectAllCheckbox.checked = false;
    } else if (selectedCount === allCheckboxes.length) {
        selectAllCheckbox.indeterminate = false;
        selectAllCheckbox.checked = true;
    } else {
        selectAllCheckbox.indeterminate = true;
    }
}

// Clear all selections
function clearSelection() {
    const checkboxes = document.querySelectorAll('input[name="module_ids[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    
    document.getElementById('selectAll').checked = false;
    document.getElementById('selectAll').indeterminate = false;
    updateBulkActions();
}

// Bulk delete function
function bulkDelete() {
    const checkboxes = document.querySelectorAll('input[name="module_ids[]"]:checked');
    const selectedIds = Array.from(checkboxes).map(cb => cb.value);
    
    if (selectedIds.length === 0) {
        Swal.fire('No Selection', 'Please select modules to delete.', 'warning');
        return;
    }

    Swal.fire({
        title: 'Delete Selected Modules',
        html: `Are you sure you want to delete <strong>${selectedIds.length}</strong> selected module(s)?<br><br>
               <small class="text-muted">Modules with child modules or those used in role permissions cannot be deleted.</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ff4d4f',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Delete Selected',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            Swal.fire({
                title: 'Deleting...',
                text: 'Please wait while we delete the selected modules.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                type: "POST",
                url: "<?= base_url() ?>modules/bulk_delete",
                data: { module_ids: selectedIds },
                success: function(response) {
                    // Clear selections
                    clearSelection();
                    
                    if (response.indexOf("success") != -1) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Bulk Delete Completed',
                            html: response,
                            timer: 4000,
                            showConfirmButton: true
                        }).then(() => {
                            location.reload();
                        });
                    } else if (response.indexOf("warning") != -1) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Bulk Delete Partially Completed',
                            html: response,
                            showConfirmButton: true
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Bulk Delete Failed',
                            html: response,
                            showConfirmButton: true
                        }).then(() => {
                            location.reload();
                        });
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'An error occurred while deleting the modules.', 'error');
                }
            });
        }
    });
}
</script>