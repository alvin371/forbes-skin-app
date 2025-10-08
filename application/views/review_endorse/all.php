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

    /* Sortable Styling - Ant Design-like */
    th.sortable {
        cursor: pointer;
        position: relative;
    }
    th.sortable:hover {
        background-color: #f1f1f1;
    }
    th.sortable i {
        font-size: 0.8em;
        margin-left: 5px;
    }

    /* Filter Box Styles */
    .filter-box {
        position: absolute;
        display: none;
        background: white;
        border: 1px solid #f0f0f0;
        padding: 16px;
        width: 220px;
        box-shadow: 0 3px 6px -4px rgba(0,0,0,0.12), 0 6px 16px 0 rgba(0,0,0,0.08), 0 9px 28px 8px rgba(0,0,0,0.05);
        border-radius: 2px;
    }

    .filter-box label {
        display: block;
        margin-bottom: 8px;
        color: rgba(0, 0, 0, 0.85);
        font-size: 14px;
    }

    .filter-box .form-control {
        margin-bottom: 12px;
    }

    /* Status Badges */
    .badge-primary {
        background-color: #1890ff;
        color: white;
    }
    .badge-success {
        background-color: #52c41a;
        color: white;
    }
    .badge-warning {
        background-color: #faad14;
        color: white;
    }
    .badge-danger {
        background-color: #f5222d;
        color: white;
    }
    .badge-default {
        background-color: #d9d9d9;
        color: rgba(0, 0, 0, 0.65);
    }

    .tippy-box[data-theme~='light'] {
        background-color: #ffffff !important;
        color: #333333 !important;
        box-shadow: 0 4px 14px rgba(0,0,0,0.1) !important;
        border: 1px solid #e5e7eb !important;
    }

    .tippy-box[data-theme~='light'] .tippy-arrow {
        color: #ffffff !important;
    }

    .tippy-box[data-theme~='light'] .tippy-content {
        color: inherit !important;
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
    <?php $this->load->view('review_endorse/menu') ?>
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">ENDORSE REVIEW</h5>

                <a href="#" class="btn btn-primary" id="btn-history">
                    <i class="bi bi-clock-history me-1"></i> History
                </a>
            </div>
            <!-- Floating div -->
            <div id="history-modal" 
                style="display:none; position:fixed; top:10%; right:10%; width:350px; background:white; border:1px solid #ccc; border-radius:8px; padding:0; box-shadow:0 4px 12px rgba(0,0,0,0.2); z-index:9999;">

                <!-- Header modal -->
                <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 15px; border-bottom:1px solid #eee;">
                    <strong style="font-size:16px;">History Status</strong>
                    <button id="close-history" 
                            style="border:none; background:transparent; font-size:18px; font-weight:bold; cursor:pointer; color:#666; line-height:1;">
                        &times;
                    </button>
                </div>

                <div id="history-content" style="max-height:460px; overflow:auto; padding:15px;">
                    <i>Loading...</i>
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
                                    <span style="margin-right: 8px;"><?= $keyword_category ?? 'Username' ?></span>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton" style="position: absolute; background-color: white; min-width: 160px; z-index: 9999; border-radius: 2px; box-shadow: 0 3px 6px -4px rgba(0,0,0,0.12), 0 6px 16px 0 rgba(0,0,0,0.08), 0 9px 28px 8px rgba(0,0,0,0.05); padding: 4px 0; margin-top: 2px; left: 0;">
                                    <?php
                                    $arr = array();
                                    $arr[] = 'Username';
                                    $arr[] = 'PIC';
                                    $arr[] = 'SPV';
                                    foreach ($arr as $k => $val) {
                                        $active = (($keyword_category ?? 'Nama') == $val) ? 'background-color: #e6f7ff; color: #1890ff;' : '';
                                    ?>
                                        <li><a class="dropdown-item" href="<?= base_url() ?>/review_endorse?keyword_category=<?= $val ?><?= !empty($_GET['keyword']) ? '&keyword=' . $_GET['keyword'] : '' ?><?= !empty($_GET['level_filter']) ? '&level_filter=' . $_GET['level_filter'] : '' ?>"
                                                style="padding: 5px 12px; font-size: 14px; line-height: 22px; <?= $active ?>">
                                                <?= $val ?>
                                            </a></li>
                                    <?php }  ?>
                                </ul>
                            </div>
                            <input type="hidden" name="keyword_category" value="<?= $keyword_category ?? 'Nama' ?>">
                            <input type="text" name="keyword" class="form-control" placeholder="Search..." value="<?= isset($_GET['keyword']) ? $_GET['keyword'] : '' ?>"
                                style="border: 1px solid #d9d9d9; box-shadow: none; height: 32px; padding: 4px 11px; margin-right: 10px; border-radius: 2px;">
                            <select name="campaign_filter" class="form-select" style="max-width: 150px; margin-right: 10px;">
                                <option value="">Semua Campaign</option>
                                <?php foreach ($campaigns as $campaign): ?>
                                    <option value="<?= $campaign['id'] ?>" <?= ($campaign_filter ?? '') == $campaign['id'] ? 'selected' : '' ?>>
                                        <?= $campaign['name'] ?>
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
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th class="text-start">#</th>
                            <th class="text-start">Username</th>
                            <th class="text-start">
                                PIC
                                <a href="javascript:void(0);" id="openPICFilter" class="text-decoration-none">
                                    <i class="fas fa-filter pe-2 fs-12"></i>
                                </a>
                            </th>
                            <th class="text-start">Campaign</th>
                            <th class="text-start">
                                SPV
                                <a href="javascript:void(0);" id="openSPVFilter" class="text-decoration-none">
                                    <i class="fas fa-filter pe-2 fs-12"></i>
                                </a>
                            </th>
                            <th class="text-start sortable" data-sort="total_cost">
                                Total Cost <i class="bi bi-arrow-down-up"></i>
                            </th>
                            <th class="text-start">Rencana Upload</th>
                            <th class="text-start">Status</th>
                            <th class="text-start">Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody id="tbody"></tbody>
                </table>
            </div>

            <div id="picFilterBox" class="filter-box" 
                style="display: none; position: absolute; background: white; padding: 10px; border: 1px solid #ddd; z-index: 1000; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                <label>Filter PIC:</label>
                <!-- Select All Checkbox -->
                <div style="margin-bottom: 8px;">
                    <label style="display: flex; align-items: center;">
                        <input type="checkbox" id="selectAllPic" class="me-2">
                        Pilih Semua
                    </label>
                </div>

                <div id="picOptions" style="max-height: 200px; overflow-y: auto; border: 1px solid #f0f0f0; padding: 5px; margin-bottom: 12px;">
                    <!-- Options will be loaded dynamically -->
                </div>
                <button class="btn btn-sm btn-primary w-100" id="applyPicFilter">Terapkan</button>
            </div>

            <div id="spvFilterBox" class="filter-box" 
                style="display: none; position: absolute; background: white; padding: 10px; border: 1px solid #ddd; z-index: 1000; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                <label>Filter SPV:</label>
                <!-- Select All Checkbox -->
                <div style="margin-bottom: 8px;">
                    <label style="display: flex; align-items: center;">
                        <input type="checkbox" id="selectAllSpv" class="me-2">
                        Pilih Semua
                    </label>
                </div>

                <div id="spvOptions" style="max-height: 200px; overflow-y: auto; border: 1px solid #f0f0f0; padding: 5px; margin-bottom: 12px;">
                    <!-- Options will be loaded dynamically -->
                </div>
                <button class="btn btn-sm btn-primary w-100" id="applySpvFilter">Terapkan</button>
            </div>


            <div class="d-flex justify-content-between">
                <div>
                    <?= $pagination ?>
                </div>
                <div>
                    <?php
                    $per_page_options = [10, 20, 50, 100, 500];
                    $limit = $_GET['limit'] ?? 10;
                    if (!in_array($limit, $per_page_options)) {
                        $limit = 10;
                    }
                    ?>

                    <select class="form-control select2" id="limit">
                        <?php foreach ($per_page_options as $option): ?>
                            <option value="<?= $option ?>" <?= ($limit == $option) ? 'selected' : '' ?>>
                                <?= $option ?> / Halaman
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="floating-div">
                <button class="btn mb-2 btn-edit-active dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-gear fs-16"></i> Aksi
                </button>
                <ul class="dropdown-menu text-end" style="padding:0px;background:unset;border:unset">
                    <li>
                        <a class="dropdown-items" href="#!" style="padding:0px">
                            <button type="button" class="btn mb-2 btn-edit-active" onclick="ubah_status()">
                                <i class="bi bi-check-circle fs-16"></i> Ubah Status
                            </button>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-items" href="#!" style="padding:0px">
                            <button type="button" class="btn mb-2 btn-edit-active" onclick="hapus_data()">
                                <i class="bi bi-trash fs-16"></i> Hapus Data
                            </button>
                        </a>
                    </li>

                </ul>
            </div>
        </div>
    </div>
</div>


<input type="hidden" id="id_selected" name="id_selected" form="form-action">

<div class="modal fade bd-example-modal-xl" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true" id="modal-form">
    <div class="modal-dialog modal-xl" id="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="title-form"></h5>
                <a class="close a-link" data-bs-dismiss="modal"><i class="bi bi-x-circle fs-24"></i></a>
            </div>
            <div class="modal-body">
                <div id="load-form"></div>
            </div>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-body p-0">
                <!-- Load content dynamically -->
            </div>
        </div>
    </div>
</div>


<style>
    .modal {
        z-index: 99999;
    }

    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
    }
</style>

<script>
    /**
     * Loads recruitment data via AJAX
     */
    // Add this to your existing JavaScript
    let activeFilters = {
        pic: [],
        spv: []
    };

    function loadReviewData() {
        const urlParams = new URLSearchParams(window.location.search);
        const sortColumn = urlParams.get('sort') || 'created_at';
        const sortDirection = urlParams.get('dir') || 'desc';
        const page = urlParams.get('page') || 1;
        const limit = urlParams.get('limit') || 10;
        
        let url = "<?= base_url() ?>review_endorse/item<?= $param ?>";
        let queryParams = [];
        
        if (sortColumn) {
            queryParams.push(`sort=${sortColumn}`);
        }
        if (sortDirection) {
            queryParams.push(`dir=${sortDirection}`);
        }
        
        queryParams.push(`page=${page}`);
        queryParams.push(`limit=${limit}`);
        
        if (activeFilters.pic.length > 0) {
            queryParams.push(`pic=${activeFilters.pic.join(',')}`);
        }
        
        if (activeFilters.spv.length > 0) {
            queryParams.push(`spv=${activeFilters.spv.join(',')}`);
        }
        
        if (queryParams.length > 0) {
            url += (url.includes('?') ? '&' : '?') + queryParams.join('&');
        }

        const newUrl = window.location.pathname + '?' + queryParams.join('&');
        window.history.pushState({path: newUrl}, '', newUrl);

        $.ajax({
            type: 'GET',
            url: url,
            dataType: 'json',
            beforeSend: function() {
                if ($('#tbody tr').length === 0) {
                    $('#tbody').html('<tr><td colspan="9" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>');
                }
            },
            success: function(data) {
                $('#tbody').html(data.table_html);
                updateSortingIcons(sortColumn, sortDirection);

                if (data.notif_text) {
                    const $existingAlert = $('.alert.alert-info');
                    
                    if ($existingAlert.length > 0) {
                        $existingAlert.find('span').text(data.notif_text);
                        $existingAlert.show();
                    } else {
                        const notificationHtml = `
                            <div class="alert alert-info" style="display: flex; align-items: center;">
                                <i class="bi bi-info-circle me-2"></i>
                                <span>${data.notif_text}</span>
                            </div>
                        `;
                        $('#tbody').closest('table').before(notificationHtml);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading data:', error);
                $('#tbody').html('<tr><td colspan="9" class="text-center text-danger">Error loading data. Please try again.</td></tr>');
                $('.alert.alert-info').hide();
            }
        });

    }

    function loadPICOptions() {
        $.ajax({
            url: "<?= base_url() ?>review_endorse/get_pic_options",
            method: 'GET',
            success: function(response) {
                let optionsHtml = '';
                response.forEach(item => {
                    optionsHtml += `
                        <div>
                            <label style="display: flex; align-items: center; margin-bottom: 8px;">
                                <input type="checkbox" class="pic-checkbox me-2" value="${item.pic}" 
                                    ${activeFilters.pic.includes(item.pic) ? 'checked' : ''}>
                                ${item.pic}
                            </label>
                        </div>
                    `;
                });
                $('#picOptions').html(optionsHtml);
            }
        });
    }

    function loadSPVOptions() {
        $.ajax({
            url: "<?= base_url() ?>review_endorse/get_spv_options",
            method: 'GET',
            success: function(response) {
                let optionsHtml = '';
                response.forEach(item => {
                    optionsHtml += `
                        <div>
                            <label style="display: flex; align-items: center; margin-bottom: 8px;">
                                <input type="checkbox" class="spv-checkbox me-2" value="${item.spv}" 
                                    ${activeFilters.spv.includes(item.spv) ? 'checked' : ''}>
                                ${item.spv}
                            </label>
                        </div>
                    `;
                });
                $('#spvOptions').html(optionsHtml);
            }
        });
    }

    function showFilterBox(triggerId, boxId) {
        const $trigger = $('#' + triggerId);
        const $box = $('#' + boxId);

        $('.filter-box').hide();

        if ($trigger.length && $box.length) {
            const offset = $trigger.offset();
            const height = $trigger.outerHeight();

            $box.css({
                display: 'block',
                top: offset.top + height - 140 + 'px',
                left: offset.left - 420 + 'px'
            });
        }
        
        if (boxId === 'picFilterBox' && $('#picOptions').children().length === 0) {
            loadPICOptions();
        } else if (boxId === 'spvFilterBox' && $('#spvOptions').children().length === 0) {
            loadSPVOptions();
        }
    }

    $('#limit').on('change', function () {
        let url = new URL(window.location.href);       // Ambil URL sekarang
        url.searchParams.set('limit', $(this).val());  // Ganti parameter limit
        url.searchParams.set('page', 1);               // Optional: reset page ke 1
        window.location.href = url.toString();         // Pindah ke URL baru
    });

    $('#openPICFilter').on('click', function(e) {
        e.stopPropagation();
        showFilterBox('openPICFilter', 'picFilterBox');
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#picFilterBox, #openPICFilter').length) {
            $('#picFilterBox').hide();
        }
    });

    $('#selectAllPic').on('change', function() {
        $('.pic-checkbox').prop('checked', $(this).prop('checked'));
    });

    $('#applyPicFilter').on('click', function() {
        activeFilters.pic = $('.pic-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        loadReviewData();
        $('#picFilterBox').hide();
    });

    $('#openPICFilter').on('dblclick', function(e) {
        e.stopPropagation();
        activeFilters.pic = [];
        loadReviewData();
        $('.pic-checkbox').prop('checked', false);
        $('#selectAllPic').prop('checked', false);
    });

    $('#openSPVFilter').on('click', function(e) {
        e.stopPropagation();
        showFilterBox('openSPVFilter', 'spvFilterBox');
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#spvFilterBox, #openSPVFilter').length) {
            $('#spvFilterBox').hide();
        }
    });

    $('#selectAllSpv').on('change', function() {
        $('.spv-checkbox').prop('checked', $(this).prop('checked'));
    });

    $('#applySpvFilter').on('click', function() {
        activeFilters.spv = $('.spv-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        loadReviewData();
        $('#spvFilterBox').hide();
    });

    $('#openSPVFilter').on('dblclick', function(e) {
        e.stopPropagation();
        activeFilters.spv = [];
        loadReviewData();
        $('.spv-checkbox').prop('checked', false);
        $('#selectAllSpv').prop('checked', false);
    });

    function updateSortingIcons(sortColumn, sortDirection) {
        $('th.sortable i').removeClass('bi-arrow-up bi-arrow-down').addClass('bi-arrow-down-up');
        
        const sortedHeader = $(`th.sortable[data-sort="${sortColumn}"]`);
        if (sortedHeader.length) {
            sortedHeader.find('i')
                .removeClass('bi-arrow-down-up')
                .addClass(sortDirection === 'asc' ? 'bi-arrow-up' : 'bi-arrow-down');
        }
    }

    $(document).ready(function() {
        $(document).on('click', '#btn-history', function(e) {
            e.preventDefault();
            $('#history-modal').show();
            $('#history-content').html('<i>Loading...</i>');

            $.ajax({
                url: '<?= base_url("review_endorse/get_history") ?>',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && response.data.length > 0) {
                        let html = '';
                        
                        response.data.forEach(function(row) {
                            // Tampilkan info parent
                            html += `<p class="fw-600 mb-1">${row.campaign_name || ''} - ${row.nama_creator || ''}</p>`;
                            
                            let logs = [];
                            try {
                                logs = JSON.parse(row.logs);
                            } catch (e) {
                                console.error('Invalid JSON in logs', e);
                            }

                            // Filter hanya yang punya is_review == 1
                            let filteredLogs = logs.filter(logItem => logItem.is_review && logItem.is_review == 1);

                            if (filteredLogs.length > 0) {
                                filteredLogs.forEach(function(logItem, idx) {
                                    html += `<p class="mb-0 fs-13 text-muted">${logItem.created_text} mengubah status menjadi <b>${logItem.status}</b> pada ${logItem.created_at}.</p>`;
                                });
                            } else {
                                html += `<i>Belum tersedia perubahan setelah review.</i>`;
                            }

                            html += '<hr>';
                        });

                        $('#history-content').html(html);
                    } else {
                        $('#history-content').html('<i>Belum ada history.</i>');
                    }
                },
                error: function(xhr, status, error) {
                    $('#history-content').html('<span class="text-danger">Error memuat data.</span>');
                    console.error(error);
                }
            });
        });

        // Tombol tutup
        $(document).on('click', '#close-history', function() {
            $('#history-modal').hide();
        });


        // Tombol tutup
        $(document).on('click', '#close-history', function() {
            $('#history-modal').hide();
        });

        $(document).on('click', '.sortable', function() {
            const column = $(this).data('sort');
            let direction = 'asc';
            
            const urlParams = new URLSearchParams(window.location.search);
            const currentSort = urlParams.get('sort');
            const currentDir = urlParams.get('dir');
            
            if (currentSort === column) {
                direction = currentDir === 'asc' ? 'desc' : 'asc';
            }
            
            urlParams.set('sort', column);
            urlParams.set('dir', direction);
            urlParams.set('page', '1'); 
            
            history.pushState(null, '', '?' + urlParams.toString());
            
            loadReviewData();
        });
        
        $('.sortable').css('cursor', 'pointer').hover(
            function() { $(this).addClass('text-primary'); },
            function() { 
                if (!$(this).hasClass('text-primary')) {
                    $(this).removeClass('text-primary');
                }
            }
        );
        
        updateSortingIcons(
            new URLSearchParams(window.location.search).get('sort') || 'created_at',
            new URLSearchParams(window.location.search).get('dir') || 'desc'
        );

        $(document).on('click', '.clickable-row', function(e) {
            if ($(e.target).closest('.editable-status').length > 0) {
                return;
            }
            
            const id = $(this).data('id');
            openDetailModal(id);
        });

        function openDetailModal(id) {
            $('#detail-endorse-content').html(`
                <div class="d-flex justify-content-center align-items-center" style="height: 200px;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `);

            $('#detailModal').modal('show');

            loadEndorseDetail(id);
        }

        function loadEndorseDetail(id) {
            $.ajax({
                url: '<?= base_url() ?>review_endorse/get_detail_data',
                method: 'GET',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        const data = response.data;
                        let html = `
                            <div class="modal-header">
                                <h5 class="modal-title" id="detailModalLabel">Detail Endorse #${id}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="card mb-3" style="padding-bottom:0px">
                                    <div class="row">
                                        <div class="col-lg-4">
                                            <p class="mb-1 text-black fw-700 fs-16">${data.nama_creator}</p>

                                            <div class="col-lg-12 mt-0 mb-3">
                                                ${data.img_creator_1}
                                                ${data.img_creator_2}
                                            </div>
                                            <p class="mb-1 text-black">PIC : ${data.pic}</p>
                                            <p class="mb-1 text-black">SPV : ${data.spv}</p>
                                            <div class="d-flex align-items-center" style="gap: 5px; margin-top:6px!important;">
                                                <p class="mb-0 br-10 fs-12 text-white" style="background-color:${data.bg}!important; color:${data.clr}!important; padding: 5px 10px;">
                                                    ${data.status_endorse.toUpperCase()}
                                                </p>
                                                ${data.status_payment ? `<p class="mb-0 br-10 fs-12 text-white" style="background-color:${data.bg_payment}!important; color:${data.clr_payment}!important; padding: 5px 10px;">
                                                    ${data.status_payment.toUpperCase()}
                                                </p>` : ''}
                                                ${data.pengajuan_status_payment ? `<p class="mb-0 br-10 fs-12 text-white" style="background-color:${data.bg_payment}!important; color:${data.clr_payment}!important; padding: 5px 10px;">
                                                    ${data.pengajuan_status_payment.toUpperCase()}
                                                </p>` : ''}
                                            </div>
                                        </div>
                                        <div class="col-md-4 mt-lg-5 mt-0">
                                            <p class="mb-1 text-black fw-600">CPM : ${data.cpm_2}</p>
                                            <p class="mb-1 text-black">AVG Interaksi : ${data.avg_interaksi_2}</p>
                                            <p class="mb-1 text-black">AVG View : ${data.avg_view_2}</p>
                                        </div>
                                        <div class="col-lg-4 text-lg-end text-start">
                                            <a href="#!" onclick="remove('${data.id}')" class="btn btn-delete  mt-0 mb-2"><i class="bi bi-trash fs-16"></i> Delete Data</a>
                                            ${data.link_upload ? `<a href="#!" onclick="sync('${data.id}')" class="btn btn-sync ms-1 mt-0 mb-2"><i class="bi bi-bootstrap-reboot fs-16"></i> Refresh</a>` : ''}
                                            <a href="#!" onclick="clone('${data.id}')" class="btn btn-copy ms-1 mt-0 mb-2"><i class="bi bi-copy fs-16"></i> Kloning</a>
                                            <a href="#!" onclick="edit('${data.id}')" class="btn btn-edit  mt-0 ms-1 mb-2"><i class="bi bi-pencil-square fs-16"></i> Edit Data</a>

                                            ${data.link_mou !== "-" && data.status_endorse !== "Review" && data.status_endorse !== 'Hold' && data.status_endorse !== 'Reject' ? `
                                            <a href="#!" onclick="set_payment(${data.id})" class="btn btn-sync mt-0 mb-2">
                                                <i class="bi bi-clipboard2-check fs-16"></i> Ajukan Payment
                                            </a>
                                            <a href="#!" onclick="set_batalkan_payment(${data.id})" class="btn btn-delete mt-0 mb-2">
                                                <i class="bi bi-clipboard2-x fs-16"></i> Batalkan Payment
                                            </a>` : ''}
                                        </div>
                                        <div class="col-lg-12">
                                            <hr>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <p class="mb-1 text-black">Total Cost : ${data.total_cost}</p>
                                                    <p class="mb-1 text-black">Barang Dikirim : ${data.barang_dikirim_at}</p>
                                                    <p class="mb-1 text-black">Rencana Upload : ${data.rencana_at}</p>
                                                    <p class="mb-1 text-black">Tanggal Posting : ${data.posting_at}</p>
                                                    <p class="mb-1 text-black">Status : ${data.status}</p>
                                                    <p class="mb-1">Ket : ${data.desc}</p>
                                                    <p class="mb-1">Ads : ${data.kode_ads}</p>
                                                </div>
                                                <div class="col-md-4">
                                                    <p class="mb-1 text-black fw-600">Views : ${data.views}</p>
                                                    <p class="mb-1 text-black fw-600">CPM : ${data.cpm}</p>
                                                    <p class="mb-1 text-black">Likes : ${data.likes}</p>
                                                    <p class="mb-1 text-black">Comments : ${data.comment}</p>
                                                    <p class="mb-1 text-black">Save & Share : ${data.share_save}</p>
                                                    <p class="mb-1 text-black">Tanggal Dibuat : ${data.created_at}</p>
                                                    <p class="mb-1 text-black">Tanggal Diupdate : ${data.sync_at}</p>
                                                </div>
                                                <div class="col-md-4">
                                                    <p class="mb-1 text-black">Link Brief : <br>${data.link_brief}</p>
                                                    <p class="mb-1 text-black">Link MOU : <br>${data.link_mou}</p>
                                                    <p class="mb-1 text-black">Ket. Payment : <br>${data.keterangan_payment}</p>
                                                    <a href="<?= base_url('endorse/payment_logs?id_campaign=${data.id_campaign}&nama_creator=${encodeURIComponent(data.nama_creator)}') ?>" target="_blank">
                                                        Lihat Logs Payment
                                                    </a>
                                                </div>
                                                <div class="col-lg-12 mt-3">
                                                    <div class="row">
                                                        <div class="col-12 mb-2" style="position:relative">
                                                            <div class="row">
                                                                <div class="firstDivImg">
                                                                    ${data.img}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        $('.modal-body').html(html);
                    } else {

                        $('.modal-body').html(`
                            <div class="modal-header">
                                <h5 class="modal-title" id="detailModalLabel">Error</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-danger">Failed to load data</div>
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error(xhr, status, error);
                    $('.modal-body').html(`
                        <div class="modal-header">
                            <h5 class="modal-title" id="detailModalLabel">Error</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-danger">Failed to load data</div>
                        </div>
                    `);
                }
            });
        }

        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });
        }
        
        $(document).on('click', '.btn-cancel-status', function(e) {
            e.stopPropagation();
            $(this).closest('.edit-mode').addClass('d-none');
            $(this).closest('.editable-status').find('.display-mode').removeClass('d-none');
        });
        
        $(document).on('change', '.editable-status select', function () {
            const cell = $(this).closest('.editable-status');
            const id = cell.data('id');
            const field = cell.data('field');
            const newValue = $(this).val();

            sendStatusUpdate(id, field, newValue, null);

        });

        function sendStatusUpdate(id, field, value, link_testcase = null) {
            const cell = $('.editable-status[data-id="'+id+'"]');
            const editModeElement = cell.find('.edit-mode');
            const displayModeElement = cell.find('.display-mode');
            
            editModeElement.html('<div class="spinner-border spinner-border-sm text-primary" role="status"></div>');

            $.ajax({
                url: '<?= base_url() ?>review_endorse/update_status',
                method: 'POST',
                dataType: 'json',
                data: {
                    id: id,
                    field: field,
                    value: value
                },
                success: function (response) {
                    if (response.status === 'success') {
                        loadReviewData();
                    } else {
                        editModeElement.html(`
                            <select class="form-select form-select-sm status-select" style="height: calc(1.5em + .5rem + 2px);">
                                <option value="Review">Review</option>
                                <option value="Hold">Hold</option>
                                <option value="Acc">Acc</option>
                                <option value="Draft Content">Draft Content</option>
                                <option value="Posted Content">Posted Content</option>
                                <option value="Reject">Reject</option>
                                <option value="Problem">Problem</option>
                            </select>
                        `);
                        alert('Gagal memperbarui status: ' + response.message);
                    }
                },
                error: function () {
                    editModeElement.html(`
                        <select class="form-select form-select-sm status-select" style="height: calc(1.5em + .5rem + 2px);">
                            <option value="Review">Review</option>
                            <option value="Hold">Hold</option>
                            <option value="Acc">Acc</option>
                            <option value="Draft Content">Draft Content</option>
                            <option value="Posted Content">Posted Content</option>
                            <option value="Reject">Reject</option>
                            <option value="Problem">Problem</option>
                        </select>
                    `);
                    alert('Gagal memperbarui status. Silakan coba lagi.');
                }
            });
        }

    });
    loadReviewData();
    function get_id() {
        list_id_v2 = '';
        var selectedValues = [];
        $('input[name="list_id"]').each(function() {
            if ($(this).is(":checked")) {
                selectedValues.push($(this).val());
                list_id_v2 += $(this).val() + ',';
            } else {
                selectedValues.push('0');
            }
        });
        if (list_id_v2.length > 0) {
            list_id_v2 = list_id_v2.slice(0, -1);
        }
        $('#id_selected').val(selectedValues.join(','));
        console.log(list_id_v2);
    }

    function showModal(title, url, isLarge = false) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html(title);

        if (isLarge) {
            $("#modal-dialog").addClass("modal-xl");
        } else {
            $("#modal-dialog").removeClass("modal-xl modal-lg");
        }

        $("#load-form").load(url);
    }

    function ubah_status(id) {
        showModal('Ubah Status', `<?= base_url() ?>/review-endorse/action?code=ubah_status&id=${id}`);
    }
</script>

<style>
    .dropdown-menu {
        z-index: 9999 !important;
    }

    .dropdown-menu.show {
        display: block !important;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
        var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
            return new bootstrap.Dropdown(dropdownToggleEl);
        });
    });
</script>