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

    /* Card Styling - Ant Design-like */
    .card {
        border-radius: 2px;
        border: 1px solid #f0f0f0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.09);
        margin-bottom: 16px;
    }

    .card-header {
        background-color: #fff;
        border-bottom: 1px solid #f0f0f0;
        padding: 16px;
        height: 56px;
    }

    .card-body {
        padding: 16px;
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

    .btn-outline-secondary {
        color: rgba(0, 0, 0, 0.65);
        border-color: #d9d9d9;
        background: #fff;
    }

    .btn-outline-secondary:hover {
        color: #40a9ff;
        border-color: #40a9ff;
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

    /* Status Badge Colors */
    .status-active { background-color: #52c41a; color: white; }
    .status-inactive { background-color: #ff4d4f; color: white; }
</style>

<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">
                    <i class="bi bi-gear me-2"></i>Module Management
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
                    
                    <!-- Show create button only if user has create permission -->
                    <?php if (isset($can_create) && $can_create): ?>
                        <a href="<?= base_url() ?>/modules/create_page" class="btn btn-primary">
                            <i class="bi bi-plus me-1"></i> Create Module
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form action="" class="search-form">
                <div class="row g-2 mb-3">
                    <div class="col-md-8">
                        <div class="input-group" style="box-shadow: 0 2px 0 rgba(0,0,0,0.02);">
                            <div class="dropdown" style="margin-right: 10px;">
                                <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false"
                                    style="border: 1px solid #d9d9d9; border-radius: 2px; color: rgba(0,0,0,0.65); background-color: #fff; height: 32px; padding: 4px 11px;">
                                    <span style="margin-right: 8px;"><?= $keyword_category ?? 'Name' ?></span>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton" style="position: absolute; background-color: white; min-width: 160px; z-index: 9999; border-radius: 2px; box-shadow: 0 3px 6px -4px rgba(0,0,0,0.12), 0 6px 16px 0 rgba(0,0,0,0.08), 0 9px 28px 8px rgba(0,0,0,0.05); padding: 4px 0; margin-top: 2px; left: 0;">
                                    <?php
                                    $arr = array();
                                    $arr[] = 'Name';
                                    $arr[] = 'Controller';
                                    foreach ($arr as $k => $val) {
                                        $active = (($keyword_category ?? 'Name') == $val) ? 'background-color: #e6f7ff; color: #1890ff;' : '';
                                    ?>
                                        <li><a class="dropdown-item" href="<?= base_url() ?>/modules?keyword_category=<?= $val ?><?= !empty($_GET['keyword']) ? '&keyword=' . $_GET['keyword'] : '' ?><?= !empty($_GET['status_filter']) ? '&status_filter=' . $_GET['status_filter'] : '' ?>"
                                                style="padding: 5px 12px; font-size: 14px; line-height: 22px; <?= $active ?>">
                                                <?= $val ?>
                                            </a></li>
                                    <?php }  ?>
                                </ul>
                            </div>
                            <input type="hidden" name="keyword_category" value="<?= $keyword_category ?? 'Name' ?>">
                            <input type="text" name="keyword" class="form-control" placeholder="Search..." value="<?= isset($_GET['keyword']) ? $_GET['keyword'] : '' ?>"
                                style="border: 1px solid #d9d9d9; box-shadow: none; height: 32px; padding: 4px 11px; margin-right: 10px; border-radius: 2px;">
                            <select name="status_filter" class="form-select" style="max-width: 150px; margin-right: 10px;">
                                <option value="">All Status</option>
                                <option value="1" <?= ($status_filter ?? '') == '1' ? 'selected' : '' ?>>Active</option>
                                <option value="0" <?= ($status_filter ?? '') === '0' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                            <button class="btn btn-primary" type="submit"
                                style="border-radius: 2px; height: 32px; padding: 0 15px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <?php if (!empty($notif)): ?>
                    <div class="alert alert-info" style="display: flex; align-items: center;">
                        <i class="bi bi-info-circle me-2"></i>
                        <span><?= strip_tags($notif) ?></span>
                    </div>
                <?php endif; ?>
            </form>

            <div class="table-responsive">
                <table class="table table-hover" id="modules-table">
                    <thead>
                        <tr>
                            <th class="text-start" width="40">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                </div>
                            </th>
                            <th class="text-start">#</th>
                            <th class="text-start">Module</th>
                            <th class="text-start">Controller</th>
                            <th class="text-start">Parent</th>
                            <th class="text-start">Order</th>
                            <th class="text-start">Children</th>
                            <th class="text-start">Permissions</th>
                            <th class="text-start">Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="modules-tbody">
                        <!-- Content will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <?= $pagination ?>
            </div>
        </div>
    </div>
</div>

<script>
    /**
     * Loads module data via AJAX
     */
    function loadModuleData() {
        $.ajax({
            type: 'GET',
            url: "<?= base_url() ?>/modules/item<?= $param ?>",
            beforeSend: function() {
                $('#modules-tbody').html('<tr><td colspan="10" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>');
            },
            success: function(data) {
                $('#modules-tbody').html(data);
            },
            error: function(xhr, status, error) {
                console.error('Error loading data:', error);
                $('#modules-tbody').html('<tr><td colspan="10" class="text-center text-danger">Error loading data. Please try again.</td></tr>');
            }
        });
    }

    // Initialize the page
    $(document).ready(function() {
        loadModuleData();
    });

    // Function alias for delete functionality (compatibility with existing code)
    function load_data() {
        loadModuleData();
    }

    // SweetAlert delete confirmation function
    function removeModule(id) {
        Swal.fire({
            title: 'Delete Module',
            text: 'Are you sure you want to delete this module?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff4d4f',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: "<?= base_url() ?>/modules/delete",
                    data: { id: id },
                    success: function(response) {
                        if (response.indexOf("success") != -1) {
                            Swal.fire('Deleted!', 'Module has been deleted.', 'success').then(() => {
                                loadModuleData();
                            });
                        } else {
                            Swal.fire('Error!', 'Failed to delete module.', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error!', 'An error occurred while deleting the module.', 'error');
                    }
                });
            }
        });
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
                    url: "<?= base_url() ?>/modules/bulk_delete",
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
                                loadModuleData();
                            });
                        } else if (response.indexOf("warning") != -1) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Bulk Delete Partially Completed',
                                html: response,
                                showConfirmButton: true
                            }).then(() => {
                                loadModuleData();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Bulk Delete Failed',
                                html: response,
                                showConfirmButton: true
                            }).then(() => {
                                loadModuleData();
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

<style>
    /* Bootstrap dropdown fix */
    .dropdown-menu {
        z-index: 9999 !important;
    }

    /* Ensure dropdown is visible */
    .dropdown-menu.show {
        display: block !important;
    }
</style>

<script>
    // Initialize Bootstrap dropdowns
    document.addEventListener('DOMContentLoaded', function() {
        // Force initialize Bootstrap dropdowns
        var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
        var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
            return new bootstrap.Dropdown(dropdownToggleEl);
        });
    });
</script>

<!-- Modal for delete confirmation -->
<div class="modal fade" id="popupModal" tabindex="-1" aria-labelledby="popupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <!-- Content will be loaded via AJAX -->
        </div>
    </div>
</div>