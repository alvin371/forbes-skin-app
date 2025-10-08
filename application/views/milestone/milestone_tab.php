<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Milestone Quest Management</h6>
    <div>
        <a href="<?= base_url() ?>milestone/milestone_create_page" class="btn btn-primary">
            <i class="bi bi-plus me-1"></i> Tambah Milestone
        </a>
    </div>
</div>

<form action="" class="search-form">
    <div class="row g-2 mb-3">
        <div class="col-md-6">
            <div class="input-group" style="box-shadow: 0 2px 0 rgba(0,0,0,0.02);">
                <div class="dropdown" style="margin-right: 10px;">
                    <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton3" data-bs-toggle="dropdown" aria-expanded="false"
                        style="border: 1px solid #d9d9d9; border-radius: 2px; color: rgba(0,0,0,0.65); background-color: #fff; height: 32px; padding: 4px 11px;">
                        <span style="margin-right: 8px;"><?= $keyword_category ?? 'Judul' ?></span>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton3" style="position: absolute; background-color: white; min-width: 160px; z-index: 9999; border-radius: 2px; box-shadow: 0 3px 6px -4px rgba(0,0,0,0.12), 0 6px 16px 0 rgba(0,0,0,0.08), 0 9px 28px 8px rgba(0,0,0,0.05); padding: 4px 0; margin-top: 2px; left: 0;">
                        <?php
                        $arr = array();
                        $arr[] = 'Judul';
                        $arr[] = 'Deskripsi';
                        $arr[] = 'Type';
                        foreach ($arr as $k => $val) {
                            $active = (($keyword_category ?? 'Judul') == $val) ? 'background-color: #e6f7ff; color: #1890ff;' : '';
                        ?>
                            <li><a class="dropdown-item" href="<?= base_url() ?>milestone/milestone_tab?keyword_category=<?= $val ?><?= !empty($_GET['keyword']) ? '&keyword=' . $_GET['keyword'] : '' ?>"
                                    style="padding: 5px 12px; font-size: 14px; line-height: 22px; <?= $active ?>">
                                    <?= $val ?>
                                </a></li>
                        <?php }  ?>
                    </ul>
                </div>
                <input type="hidden" name="keyword_category" value="<?= $keyword_category ?? 'Judul' ?>">
                <input type="text" name="keyword" class="form-control" placeholder="Search..." value="<?= isset($_GET['keyword']) ? $_GET['keyword'] : '' ?>"
                    style="border: 1px solid #d9d9d9; box-shadow: none; height: 32px; padding: 4px 11px; margin-right: 10px; border-radius: 2px;">
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
    <table class="table table-hover" id="milestone-table">
        <thead>
            <tr>
                <th class="text-start">#</th>
                <th class="text-start">Milestone Title</th>
                <th class="text-center">Type</th>
                <th class="text-center">Target Value</th>
                <th class="text-start">Status</th>
                <th class="text-start">Created At</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody id="milestone-tbody">
            <!-- Content will be loaded via AJAX -->
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-end mt-3">
    <?= $pagination ?>
</div>

<script>
// Function to load milestone data
function loadMilestoneData() {
    $.ajax({
        type: 'GET',
        url: "<?= base_url() ?>milestone/milestone_item<?= $param ?? '' ?>",
        beforeSend: function() {
            $('#milestone-tbody').html('<tr><td colspan="7" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>');
        },
        success: function(data) {
            $('#milestone-tbody').html(data);
        },
        error: function(xhr, status, error) {
            console.error('Error loading data:', error);
            $('#milestone-tbody').html('<tr><td colspan="7" class="text-center text-danger">Error loading data. Please try again.</td></tr>');
        }
    });
}

// Initialize dropdowns and load data when called
setTimeout(function() {
    // Initialize Bootstrap dropdowns
    var dropdownElementList = [].slice.call(document.querySelectorAll('#milestone-management-content .dropdown-toggle'));
    var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });
}, 100);

function removeMilestone(id) {
    Swal.fire({
        title: 'Hapus Milestone',
        text: 'Apakah Anda yakin ingin menghapus milestone ini?',
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
                url: "<?= base_url() ?>milestone/milestone_delete",
                data: { id: id },
                success: function(response) {
                    if (response.indexOf("success") != -1) {
                        Swal.fire('Terhapus!', 'Milestone berhasil dihapus.', 'success').then(() => {
                            loadMilestoneData();
                        });
                    } else {
                        Swal.fire('Error!', 'Gagal menghapus milestone.', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Terjadi kesalahan saat menghapus milestone.', 'error');
                }
            });
        }
    });
}
</script>