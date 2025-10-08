<div class="container-fluid">
    <div style="padding: 20px 0;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h6 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Position Management</h6>
            
            <!-- Show create button only if user has create permission -->
            <?php if (isset($can_create) && $can_create): ?>
                <a href="<?= base_url() ?>/position/create_page" class="btn btn-primary">
                    <i class="bi bi-plus me-1"></i> Tambah Data
                </a>
            <?php endif; ?>
        </div>

        <!-- Bulk Actions Bar -->
        <div id="bulk-actions-bar" class="alert alert-primary d-flex justify-content-between align-items-center mb-3 d-none" style="visibility: hidden; opacity: 0; height: 0; overflow: hidden;">
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
                // Hide bulk actions bar while loading using reliable method
                forceHideBulkActions();
                $('#selected-count').text('0');
            },
            success: function(data) {
                $('#position-tbody').html(data);
                // Ensure bulk actions are properly reset after data load with a small delay
                setTimeout(function() {
                    updateBulkActions();
                }, 50);
            },
            error: function(xhr, status, error) {
                console.error('Error loading data:', error);
                $('#position-tbody').html('<tr><td colspan="5" class="text-center text-danger">Error loading data. Please try again.</td></tr>');
            }
        });
    }

    // Initialize the page
    $(document).ready(function() {
        // Force hide bulk actions bar using multiple methods for maximum reliability
        forceHideBulkActions();
        $('#selected-count').text('0');

        // Clear any existing checkbox states
        $('.position-checkbox, #select-all-position').prop('checked', false);

        loadPositionData();

        // Handle pagination clicks
        setupPaginationHandlers();

        // Call updateBulkActions to ensure proper initial state after a short delay
        setTimeout(function() {
            updateBulkActions();
        }, 100);
    });

    // Function to reliably hide bulk actions bar using multiple CSS methods
    function forceHideBulkActions() {
        $('#bulk-actions-bar')
            .hide()
            .addClass('d-none')
            .css({
                'display': 'none !important',
                'visibility': 'hidden',
                'opacity': '0',
                'height': '0',
                'overflow': 'hidden',
                'position': 'absolute',
                'top': '-9999px'
            });
    }

    // Function to reliably show bulk actions bar
    function forceShowBulkActions() {
        $('#bulk-actions-bar')
            .show()
            .removeClass('d-none')
            .css({
                'display': 'flex',
                'visibility': 'visible',
                'opacity': '1',
                'height': 'auto',
                'overflow': 'visible',
                'position': 'relative',
                'top': 'auto'
            });
    }
    
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
            window.history.pushState({ page: page }, '', currentUrl);
            
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
                // Hide bulk actions bar while loading using reliable method
                forceHideBulkActions();
                $('#selected-count').text('0');
            },
            success: function(data) {
                $('#position-tbody').html(data);
                // Ensure bulk actions are properly reset after data load with a small delay
                setTimeout(function() {
                    updateBulkActions();
                }, 50);

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
        
        const url = "<?= base_url() ?>/position/position_management_tab?" + currentParams.toString();
        
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

        // Clear all selections when searching
        clearAllSelections();

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
        forceHideBulkActions();
        $('#selected-count').text('0');
    }

    // Bulk selection management
    function updateBulkActions() {
        const checkedBoxes = $('.position-checkbox:checked');
        const selectedCount = checkedBoxes.length;
        const totalBoxes = $('.position-checkbox').length;

        // Only show bulk actions if there are items selected AND checkboxes exist
        if (selectedCount > 0 && totalBoxes > 0) {
            forceShowBulkActions();
            $('#selected-count').text(selectedCount);
        } else {
            // Force hide the bulk actions bar using reliable method
            forceHideBulkActions();
            $('#selected-count').text('0');
        }

        // Update select all checkbox state
        if (totalBoxes > 0) {
            $('#select-all-position').prop('indeterminate', selectedCount > 0 && selectedCount < totalBoxes);
            $('#select-all-position').prop('checked', selectedCount === totalBoxes);
        } else {
            $('#select-all-position').prop('indeterminate', false);
            $('#select-all-position').prop('checked', false);
        }
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
                    data: { ids: selectedIds },
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
    function confirmDeletePosition(id) {
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
                    data: { id: id },
                    beforeSend: function() {
                        Swal.fire({
                            title: 'Menghapus...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        if (response.indexOf("success") != -1) {
                            Swal.fire('Terhapus!', 'Data berhasil dihapus.', 'success').then(() => {
                                clearAllSelections();
                                loadPositionData();
                            });
                        } else {
                            // Extract error message from response (clean HTML)
                            let errorMsg = 'Gagal menghapus data.';
                            
                            // Remove HTML tags and get clean text
                            let tempDiv = document.createElement('div');
                            tempDiv.innerHTML = response;
                            let cleanText = tempDiv.textContent || tempDiv.innerText || '';
                            
                            if (cleanText.indexOf('sedang digunakan') != -1) {
                                errorMsg = cleanText; // Use the full message with user names
                            } else if (cleanText.indexOf('jalur karir') != -1) {
                                errorMsg = 'Posisi tidak dapat dihapus karena memiliki jalur karir terkait!';
                            } else if (cleanText.indexOf('tidak ditemukan') != -1) {
                                errorMsg = 'Posisi tidak ditemukan!';
                            } else if (cleanText.indexOf('tidak valid') != -1) {
                                errorMsg = 'ID posisi tidak valid!';
                            } else if (cleanText.trim()) {
                                errorMsg = cleanText;
                            }
                            
                            Swal.fire('Error!', errorMsg, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error!', 'Terjadi kesalahan saat menghapus data.', 'error');
                    }
                });
            }
        });
    }

    // Legacy function for compatibility
    function removePosition(id) {
        confirmDeletePosition(id);
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