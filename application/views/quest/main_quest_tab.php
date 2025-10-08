<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Main Quest Management</h6>
    <div>
        <a href="<?= base_url() ?>quest/main_quest_submissions" class="btn btn-outline-secondary me-2">
            <i class="bi bi-list-check me-1"></i> Review Submissions
        </a>

        <!-- Show create button only if user has create permission -->
        <?php if (isset($can_create) && $can_create): ?>
            <a href="<?= base_url() ?>quest/main_quest_create_page" class="btn btn-primary">
                <i class="bi bi-plus me-1"></i> Tambah Data
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Bulk Actions Bar -->
<div id="bulk-actions-bar" class="alert alert-primary d-flex justify-content-between align-items-center mb-3" style="display: none !important;">
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

<form action="" class="search-form">
    <div class="row g-2 mb-3">
        <div class="col-md-8">
            <div class="input-group" style="box-shadow: 0 2px 0 rgba(0,0,0,0.02);">
                <div class="dropdown" style="margin-right: 10px;">
                    <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false"
                        style="border: 1px solid #d9d9d9; border-radius: 2px; color: rgba(0,0,0,0.65); background-color: #fff; height: 32px; padding: 4px 11px;">
                        <span style="margin-right: 8px;"><?= $keyword_category ?? 'Judul' ?></span>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton" style="position: absolute; background-color: white; min-width: 160px; z-index: 9999; border-radius: 2px; box-shadow: 0 3px 6px -4px rgba(0,0,0,0.12), 0 6px 16px 0 rgba(0,0,0,0.08), 0 9px 28px 8px rgba(0,0,0,0.05); padding: 4px 0; margin-top: 2px; left: 0;">
                        <?php
                        $arr = array();
                        $arr[] = 'Judul';
                        $arr[] = 'Deskripsi';
                        $arr[] = 'Position';
                        foreach ($arr as $k => $val) {
                            $active = (($keyword_category ?? 'Judul') == $val) ? 'background-color: #e6f7ff; color: #1890ff;' : '';
                        ?>
                            <li><a class="dropdown-item" href="<?= base_url() ?>quest/main_quest_tab?keyword_category=<?= $val ?><?= !empty($_GET['keyword']) ? '&keyword=' . $_GET['keyword'] : '' ?><?= !empty($_GET['level_filter']) ? '&level_filter=' . $_GET['level_filter'] : '' ?>"
                                    style="padding: 5px 12px; font-size: 14px; line-height: 22px; <?= $active ?>">
                                    <?= $val ?>
                                </a></li>
                        <?php }  ?>
                    </ul>
                </div>
                <input type="hidden" name="keyword_category" value="<?= $keyword_category ?? 'Judul' ?>">
                <input type="text" name="keyword" class="form-control" placeholder="Search..." value="<?= isset($_GET['keyword']) ? $_GET['keyword'] : '' ?>"
                    style="border: 1px solid #d9d9d9; box-shadow: none; height: 32px; padding: 4px 11px; margin-right: 10px; border-radius: 2px;">
                <select name="level_filter" class="form-select" style="max-width: 200px; margin-right: 10px;">
                    <option value="">Semua Position</option>
                    <?php foreach ($positions as $position): ?>
                        <option value="<?= $position['id'] ?>" <?= ($level_filter ?? '') == $position['id'] ? 'selected' : '' ?>>
                            <?= $position['name'] ?> (<?= $position['level_name'] ?>)
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
    <table class="table table-hover" id="main-quest-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 40px;">
                    <input type="checkbox" id="select-all-main-quest" class="form-check-input">
                </th>
                <th class="text-start">#</th>
                <th class="text-start">Judul Quest</th>
                <th class="text-start">Deskripsi</th>
                <th class="text-start">Required Position</th>
                <th class="text-start">Creator</th>
                <th class="text-start">Created At</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody id="main-quest-tbody">
            <!-- Content will be loaded via AJAX -->
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-end mt-3">
    <?= $pagination ?>
</div>

<script>
// Function to load main quest data
function loadMainQuestData() {
    $.ajax({
        type: 'GET',
        url: "<?= base_url() ?>quest/main_quest_item<?= $param ?? '' ?>",
        beforeSend: function() {
            $('#main-quest-tbody').html('<tr><td colspan="8" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>');
        },
        success: function(data) {
            $('#main-quest-tbody').html(data);
            // Ensure bulk actions are properly reset after data load
            updateBulkActions();
        },
        error: function(xhr, status, error) {
            console.error('Error loading data:', error);
            $('#main-quest-tbody').html('<tr><td colspan="8" class="text-center text-danger">Error loading data. Please try again.</td></tr>');
        }
    });
}

// Initialize dropdowns and load data when called
setTimeout(function() {
    // Initialize Bootstrap dropdowns
    var dropdownElementList = [].slice.call(document.querySelectorAll('#main-quest-content .dropdown-toggle'));
    var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });
}, 100);

// Clear all selections and hide bulk actions
function clearAllSelections() {
    $('.quest-checkbox, #select-all-main-quest').prop('checked', false);
    $('#bulk-actions-bar').hide();
    $('#selected-count').text('0');
}

// Bulk selection management
function updateBulkActions() {
    const checkedBoxes = $('.quest-checkbox:checked');
    const selectedCount = checkedBoxes.length;

    if (selectedCount > 0) {
        $('#bulk-actions-bar').show();
        $('#selected-count').text(selectedCount);
    } else {
        $('#bulk-actions-bar').hide();
    }

    // Update select all checkbox
    const totalBoxes = $('.quest-checkbox').length;
    $('#select-all-main-quest').prop('indeterminate', selectedCount > 0 && selectedCount < totalBoxes);
    $('#select-all-main-quest').prop('checked', selectedCount === totalBoxes && totalBoxes > 0);
}

// Select all functionality
$(document).on('change', '#select-all-main-quest', function() {
    const isChecked = $(this).is(':checked');
    $('.quest-checkbox').prop('checked', isChecked);
    updateBulkActions();
});

// Individual checkbox change
$(document).on('change', '.quest-checkbox', function() {
    updateBulkActions();
});

// Clear selection
$(document).on('click', '#clear-selection', function() {
    clearAllSelections();
});

// Bulk delete functionality
$(document).on('click', '#bulk-delete-btn', function() {
    const selectedIds = [];
    const selectedTitles = [];

    $('.quest-checkbox:checked').each(function() {
        selectedIds.push($(this).val());
        selectedTitles.push($(this).data('quest-title'));
    });

    if (selectedIds.length === 0) {
        Swal.fire('Peringatan!', 'Pilih minimal satu item untuk dihapus.', 'warning');
        return;
    }

    // Create list of selected items for confirmation
    const itemList = selectedTitles.slice(0, 5).map(title => `• ${title}`).join('<br>');
    const moreItems = selectedTitles.length > 5 ? `<br>• ... dan ${selectedTitles.length - 5} item lainnya` : '';

    Swal.fire({
        title: 'Hapus Multiple Data',
        html: `Apakah Anda yakin ingin menghapus <strong>${selectedIds.length}</strong> main quest?<br><br><div style="text-align: left; font-size: 14px; color: #666;">${itemList}${moreItems}</div>`,
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
                url: "<?= base_url() ?>quest/main_quest_bulk_delete",
                data: { ids: selectedIds },
                dataType: 'json'
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            const response = result.value;
            if (response && response.success) {
                let message = `${response.deleted_count} main quest berhasil dihapus.`;
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
                    loadMainQuestData();
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

function removeMainQuest(id) {
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
                url: "<?= base_url() ?>quest/main_quest_delete",
                data: { id: id },
                success: function(response) {
                    if (response.indexOf("success") != -1) {
                        Swal.fire('Terhapus!', 'Data berhasil dihapus.', 'success').then(() => {
                            clearAllSelections();
                            loadMainQuestData();
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