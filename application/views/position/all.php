<style>
    /* Select2 Styling */
    .select2-container .select2-selection--single {
        box-sizing: border-box;
        cursor: pointer;
        display: block;
        height: 32px;
        user-select: none;
        -webkit-user-select: none;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: rgba(0, 0, 0, 0.85);
        line-height: 32px;
        padding-left: 11px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 32px;
        position: absolute;
        top: 0px;
        right: 1px;
        width: 20px;
    }

    .select2 {
        height: 32px !important;
        min-width: 100% !important;
        margin-bottom: 8px;
    }

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

    /* Pagination Styling - Ant Design-like */
    .pagination {
        margin-top: 16px;
        justify-content: flex-end;
    }

    .page-item {
        margin-right: 8px;
    }

    .page-item:last-child {
        margin-right: 0;
    }

    .page-item.active .page-link {
        background-color: #1890ff;
        border-color: #1890ff;
    }

    .page-link {
        min-width: 32px;
        height: 32px;
        line-height: 30px;
        text-align: center;
        color: rgba(0, 0, 0, 0.65);
        border-radius: 2px;
        padding: 0;
        margin: 0;
        border: 1px solid #d9d9d9;
    }

    .page-link:hover {
        color: #40a9ff;
        border-color: #40a9ff;
    }

    /* Search Form Styling - Ant Design-like */
    .search-form {
        margin-bottom: 16px;
    }

    .search-form .input-group {
        border-radius: 2px;
        display: flex;
        justify-content: space-between;
    }

    .form-control {
        height: 32px;
        padding: 4px 11px;
        font-size: 14px;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
        transition: all 0.3s;
    }

    .form-control:hover {
        border-color: #40a9ff;
    }

    .form-control:focus {
        border-color: #40a9ff;
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
    }

    .form-select {
        height: 32px;
        padding: 4px 11px;
        font-size: 14px;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
        transition: all 0.3s;
    }

    .form-select:hover {
        border-color: #40a9ff;
    }

    .form-select:focus {
        border-color: #40a9ff;
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
    }

    /* Alert Styling - Ant Design-like */
    .alert {
        padding: 8px 15px;
        border-radius: 2px;
        font-size: 14px;
    }

    .alert-info {
        background-color: #e6f7ff;
        border-color: #91d5ff;
        color: rgba(0, 0, 0, 0.65);
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .table-responsive {
            border-radius: 2px;
            overflow: hidden;
        }
    }
</style>

<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Position Management</h5>

                <!-- Show create button only if user has create permission -->
                <?php if (isset($can_create) && $can_create): ?>
                    <a href="<?= base_url() ?>/position/create_page" class="btn btn-primary">
                        <i class="bi bi-plus me-1"></i> Tambah Data
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <!-- Bulk Actions Bar -->
            <div id="bulk-actions-bar" class="alert alert-primary d-flex justify-content-between align-items-center mb-3" style="display: none;">
                <div>
                    <i class="bi bi-check-square me-2"></i>
                    <span id="selected-count">0</span> item dipilih
                </div>
                <div>
                    <?php if (isset($can_delete) && $can_delete): ?>
                        <button type="button" class="btn btn-danger btn-sm" id="bulk-delete-btn">
                            <i class="bi bi-trash me-1"></i> Hapus Terpilih
                        </button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-outline-secondary btn-sm ms-2" id="clear-selection">
                        <i class="bi bi-x me-1"></i> Batal Pilih
                    </button>
                </div>
            </div>

            <form action="" class="search-form" onsubmit="return handleSearchSubmit(event)">
                <div class="row g-2 mb-3">
                    <div class="col-md-8">
                        <div class="input-group" style="box-shadow: 0 2px 0 rgba(0,0,0,0.02);">
                            <div class="dropdown" style="margin-right: 10px;">
                                <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false"
                                    style="border: 1px solid #d9d9d9; border-radius: 2px; color: rgba(0,0,0,0.65); background-color: #fff; height: 32px; padding: 4px 11px;">
                                    <span style="margin-right: 8px;"><?= $keyword_category ?? 'Nama' ?></span>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton" style="position: absolute; background-color: white; min-width: 160px; z-index: 9999; border-radius: 2px; box-shadow: 0 3px 6px -4px rgba(0,0,0,0.12), 0 6px 16px 0 rgba(0,0,0,0.08), 0 9px 28px 8px rgba(0,0,0,0.05); padding: 4px 0; margin-top: 2px; left: 0;">
                                    <?php
                                    $arr = array();
                                    $arr[] = 'Nama';
                                    $arr[] = 'Level';
                                    foreach ($arr as $k => $val) {
                                        $active = (($keyword_category ?? 'Nama') == $val) ? 'background-color: #e6f7ff; color: #1890ff;' : '';
                                    ?>
                                        <li><a class="dropdown-item" href="<?= base_url() ?>/position?keyword_category=<?= $val ?><?= !empty($_GET['keyword']) ? '&keyword=' . $_GET['keyword'] : '' ?><?= !empty($_GET['level_filter']) ? '&level_filter=' . $_GET['level_filter'] : '' ?>"
                                                style="padding: 5px 12px; font-size: 14px; line-height: 22px; <?= $active ?>">
                                                <?= $val ?>
                                            </a></li>
                                    <?php }  ?>
                                </ul>
                            </div>
                            <input type="hidden" name="keyword_category" value="<?= $keyword_category ?? 'Nama' ?>">
                            <input type="text" name="keyword" class="form-control" placeholder="Search..." value="<?= isset($_GET['keyword']) ? $_GET['keyword'] : '' ?>"
                                style="border: 1px solid #d9d9d9; box-shadow: none; height: 32px; padding: 4px 11px; margin-right: 10px; border-radius: 2px;">
                            <select name="level_filter" class="form-select" style="max-width: 150px; margin-right: 10px;">
                                <option value="">Semua Level</option>
                                <?php foreach ($quest_levels as $level): ?>
                                    <option value="<?= $level['id'] ?>" <?= ($level_filter ?? '') == $level['id'] ? 'selected' : '' ?>>
                                        <?= $level['name'] ?>
                                    </option>
                                <?php endforeach; ?>
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
                <table class="table table-hover" id="position-table">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 40px;">
                                <input type="checkbox" id="select-all-position" class="form-check-input">
                            </th>
                            <th class="text-start">#</th>
                            <th class="text-start">Nama Position</th>
                            <th class="text-start">Level</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="position-tbody">
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
     * Loads position data via AJAX
     */
    function loadPositionData() {
        $.ajax({
            type: 'GET',
            url: "<?= base_url() ?>/position/item<?= $param ?>",
            beforeSend: function() {
                $('#position-tbody').html('<tr><td colspan="5" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>');
            },
            success: function(data) {
                $('#position-tbody').html(data);
                // Ensure bulk actions are properly reset after data load
                updateBulkActions();
            },
            error: function(xhr, status, error) {
                console.error('Error loading data:', error);
                $('#position-tbody').html('<tr><td colspan="5" class="text-center text-danger">Error loading data. Please try again.</td></tr>');
            }
        });
    }

    // Initialize the page
    $(document).ready(function() {
        loadPositionData();

        // Handle pagination clicks
        setupPaginationHandlers();
    });

    /**
     * Set up event handlers for pagination links
     */
    function setupPaginationHandlers() {
        // Use event delegation to handle dynamically generated pagination links
        $(document).on('click', '.btn-pagination, .btn-pagination-active', function(e) {
            e.preventDefault();

            const url = $(this).attr('href');
            if (!url) return;

            // Extract page parameter from URL
            const urlParams = new URLSearchParams(url.split('?')[1]);
            const page = urlParams.get('page') || 1;

            console.log('Pagination clicked, loading page:', page);

            // Update URL without page reload
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('page', page);
            window.history.pushState({
                page: page
            }, '', currentUrl);

            // Load data for the new page
            loadPositionDataWithPage(page);
        });
    }

    /**
     * Load position data for a specific page
     */
    function loadPositionDataWithPage(page) {
        // Build URL with current filters and new page
        const currentParams = new URLSearchParams(window.location.search);
        currentParams.set('page', page);

        const url = "<?= base_url() ?>/position/item?" + currentParams.toString();

        $.ajax({
            type: 'GET',
            url: url,
            beforeSend: function() {
                $('#position-tbody').html('<tr><td colspan="5" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>');
            },
            success: function(data) {
                $('#position-tbody').html(data);
                // Ensure bulk actions are properly reset after data load
                updateBulkActions();

                // Update pagination links with new page
                updatePaginationForPage(page);
            },
            error: function(xhr, status, error) {
                console.error('Error loading data:', error);
                $('#position-tbody').html('<tr><td colspan="5" class="text-center text-danger">Error loading data. Please try again.</td></tr>');
            }
        });
    }

    /**
     * Update pagination links for the current page
     */
    function updatePaginationForPage(page) {
        const currentParams = new URLSearchParams(window.location.search);
        currentParams.set('page', page);

        // For all.php, we need to make a request to get updated pagination
        const url = window.location.pathname + "?" + currentParams.toString();

        // Only update the pagination section
        $.ajax({
            type: 'GET',
            url: url,
            success: function(response) {
                // Extract pagination from response
                const tempDiv = $('<div>').html(response);
                const newPagination = tempDiv.find('.d-flex.justify-content-end.mt-3').html();

                if (newPagination) {
                    $('.d-flex.justify-content-end.mt-3').html(newPagination);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error updating pagination:', error);
            }
        });
    }

    /**
     * Handle search form submission
     */
    function handleSearchSubmit(event) {
        event.preventDefault();

        // Get form data
        const formData = new FormData(event.target);
        const params = new URLSearchParams();

        for (let [key, value] of formData.entries()) {
            if (value.trim()) {
                params.append(key, value);
            }
        }

        // Reset to page 1 for new search
        params.set('page', '1');

        // Update URL
        const newUrl = window.location.pathname + '?' + params.toString();
        window.history.pushState({}, '', newUrl);

        // Load data with new search parameters
        loadPositionDataWithPage(1);

        return false;
    }

    // Function alias for delete functionality (compatibility with existing code)
    function load_data() {
        loadPositionData();
    }

    // Clear all selections and hide bulk actions
    function clearAllSelections() {
        $('.position-checkbox, #select-all-position').prop('checked', false);
        $('#bulk-actions-bar').hide();
        $('#selected-count').text('0');
    }

    // Bulk selection management
    function updateBulkActions() {
        const checkedBoxes = $('.position-checkbox:checked');
        const selectedCount = checkedBoxes.length;

        if (selectedCount > 0) {
            $('#bulk-actions-bar').show();
            $('#selected-count').text(selectedCount);
        } else {
            $('#bulk-actions-bar').hide();
        }

        // Update select all checkbox
        const totalBoxes = $('.position-checkbox').length;
        $('#select-all-position').prop('indeterminate', selectedCount > 0 && selectedCount < totalBoxes);
        $('#select-all-position').prop('checked', selectedCount === totalBoxes && totalBoxes > 0);
    }

    // Select all functionality
    $(document).on('change', '#select-all-position', function() {
        const isChecked = $(this).is(':checked');
        $('.position-checkbox').prop('checked', isChecked);
        updateBulkActions();
    });

    // Individual checkbox change
    $(document).on('change', '.position-checkbox', function() {
        updateBulkActions();
    });

    // Clear selection
    $(document).on('click', '#clear-selection', function() {
        clearAllSelections();
    });

    // Bulk delete functionality
    $(document).on('click', '#bulk-delete-btn', function() {
        const selectedIds = [];
        const selectedNames = [];

        $('.position-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
            selectedNames.push($(this).data('position-name'));
        });

        if (selectedIds.length === 0) {
            Swal.fire('Peringatan!', 'Pilih minimal satu item untuk dihapus.', 'warning');
            return;
        }

        // Create list of selected items for confirmation
        const itemList = selectedNames.slice(0, 5).map(name => `• ${name}`).join('<br>');
        const moreItems = selectedNames.length > 5 ? `<br>• ... dan ${selectedNames.length - 5} item lainnya` : '';

        Swal.fire({
            title: 'Hapus Multiple Data',
            html: `Apakah Anda yakin ingin menghapus <strong>${selectedIds.length}</strong> position?<br><br><div style="text-align: left; font-size: 14px; color: #666;">${itemList}${moreItems}</div>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff4d4f',
            cancelButtonColor: '#6c757d',
            confirmButtonText: `Ya, Hapus ${selectedIds.length} Item`,
            cancelButtonText: 'Batal',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    type: "POST",
                    url: "<?= base_url() ?>/position/bulk_delete",
                    data: {
                        ids: selectedIds
                    },
                    dataType: 'json'
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                const response = result.value;
                if (response && response.success) {
                    let message = `${response.deleted_count} position berhasil dihapus.`;
                    if (response.failed_count > 0) {
                        message += `<br><small class="text-warning">${response.failed_count} item gagal dihapus (mungkin sedang digunakan).</small>`;
                    }

                    Swal.fire({
                        title: 'Berhasil!',
                        html: message,
                        icon: response.failed_count > 0 ? 'warning' : 'success'
                    }).then(() => {
                        // Clear all selections first
                        clearAllSelections();

                        // Then reload data
                        loadPositionData();
                    });
                } else {
                    Swal.fire('Error!', response.message || 'Gagal menghapus data.', 'error');
                }
            }
        }).catch((error) => {
            console.error('Bulk delete error:', error);
            Swal.fire('Error!', 'Terjadi kesalahan saat menghapus data.', 'error');
        });
    });

    // SweetAlert delete confirmation function
    function removePosition(id) {
        Swal.fire({
            title: 'Hapus Data',
            text: 'Apakah Anda yakin ingin menghapus data ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff4d4f',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: "<?= base_url() ?>/position/delete",
                    data: {
                        id: id
                    },
                    success: function(response) {
                        if (response.indexOf("success") != -1) {
                            Swal.fire('Terhapus!', 'Data berhasil dihapus.', 'success').then(() => {
                                clearAllSelections();
                                loadPositionData();
                            });
                        } else {
                            Swal.fire('Error!', 'Gagal menghapus data.', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error!', 'Terjadi kesalahan saat menghapus data.', 'error');
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