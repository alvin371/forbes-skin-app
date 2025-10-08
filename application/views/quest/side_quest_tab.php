<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Side Quest Management</h6>
    <div>
        <a href="<?= base_url() ?>quest/side_quest_submissions" class="btn btn-outline-secondary me-2">
            <i class="bi bi-list-check me-1"></i> Review Submissions
        </a>
        <a href="<?= base_url() ?>quest/side_quest_create_page" class="btn btn-primary">
            <i class="bi bi-plus me-1"></i> Tambah Data
        </a>
    </div>
</div>

<form action="" class="search-form">
    <div class="row g-2 mb-3">
        <div class="col-md-6">
            <div class="input-group" style="box-shadow: 0 2px 0 rgba(0,0,0,0.02);">
                <div class="dropdown" style="margin-right: 10px;">
                    <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton2" data-bs-toggle="dropdown" aria-expanded="false"
                        style="border: 1px solid #d9d9d9; border-radius: 2px; color: rgba(0,0,0,0.65); background-color: #fff; height: 32px; padding: 4px 11px;">
                        <span style="margin-right: 8px;"><?= $keyword_category ?? 'Judul' ?></span>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton2" style="position: absolute; background-color: white; min-width: 160px; z-index: 9999; border-radius: 2px; box-shadow: 0 3px 6px -4px rgba(0,0,0,0.12), 0 6px 16px 0 rgba(0,0,0,0.08), 0 9px 28px 8px rgba(0,0,0,0.05); padding: 4px 0; margin-top: 2px; left: 0;">
                        <?php
                        $arr = array();
                        $arr[] = 'Judul';
                        $arr[] = 'Deskripsi';
                        $arr[] = 'Creator';
                        foreach ($arr as $k => $val) {
                            $active = (($keyword_category ?? 'Judul') == $val) ? 'background-color: #e6f7ff; color: #1890ff;' : '';
                        ?>
                            <li><a class="dropdown-item" href="<?= base_url() ?>quest/side_quest_tab?keyword_category=<?= $val ?><?= !empty($_GET['keyword']) ? '&keyword=' . $_GET['keyword'] : '' ?>"
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
    <table class="table table-hover" id="side-quest-table">
        <thead>
            <tr>
                <th class="text-start">#</th>
                <th class="text-start">Judul Quest</th>
                <th class="text-start">Deskripsi</th>
                <th class="text-center">Points</th>
                <th class="text-start">Reward</th>
                <th class="text-start">Creator</th>
                <th class="text-start">Created At</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody id="side-quest-tbody">
            <!-- Content will be loaded via AJAX -->
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-end mt-3">
    <?= $pagination ?>
</div>

<script>
// Function to load side quest data
function loadSideQuestData() {
    $.ajax({
        type: 'GET',
        url: "<?= base_url() ?>quest/side_quest_item<?= $param ?? '' ?>",
        beforeSend: function() {
            $('#side-quest-tbody').html('<tr><td colspan="8" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>');
        },
        success: function(data) {
            $('#side-quest-tbody').html(data);
        },
        error: function(xhr, status, error) {
            console.error('Error loading data:', error);
            $('#side-quest-tbody').html('<tr><td colspan="8" class="text-center text-danger">Error loading data. Please try again.</td></tr>');
        }
    });
}

// Initialize dropdowns and load data when called
setTimeout(function() {
    // Initialize Bootstrap dropdowns
    var dropdownElementList = [].slice.call(document.querySelectorAll('#side-quest-content .dropdown-toggle'));
    var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });
}, 100);

function removeSideQuest(id) {
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
                url: "<?= base_url() ?>quest/side_quest_delete",
                data: { id: id },
                success: function(response) {
                    if (response.indexOf("success") != -1) {
                        Swal.fire('Terhapus!', 'Data berhasil dihapus.', 'success').then(() => {
                            loadSideQuestData();
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