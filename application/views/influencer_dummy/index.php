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
        color: white;
    }

    .btn-primary:hover {
        background-color: #40a9ff;
        border-color: #40a9ff;
        color: white;
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

    /* Custom Table Styles for Influencer Listing */
    #influencerTable thead th {
        background-color: #fafafa;
        color: rgba(0, 0, 0, 0.85);
        font-weight: 500;
    }

    #influencerTable tr td:first-child {
        border-left: 1px solid #f0f0f0 !important;
    }

    #influencerTable tr td:last-child {
        border-right: 1px solid #f0f0f0 !important;
    }

    #influencerTable tr td {
        border-top: 1px solid #f0f0f0 !important;
        border-bottom: 1px solid #f0f0f0 !important;
    }

    #influencerTable tr.active td:first-child {
        border-left: 1px solid #b7eb8f !important;
    }

    #influencerTable tr.active td:last-child {
        border-right: 1px solid #b7eb8f !important;
    }

    #influencerTable tr.active td {
        border-top: 1px solid #b7eb8f !important;
        border-bottom: 1px solid #b7eb8f !important;
        background-color: #f6ffed !important; 
    }

    #influencerTable tr.deactive td:first-child {
        border-left: 1px solid #ffa39e !important;
    }

    #influencerTable tr.deactive td:last-child {
        border-right: 1px solid #ffa39e !important;
    }

    #influencerTable tr.deactive td {
        border-top: 1px solid #ffa39e !important;
        border-bottom: 1px solid #ffa39e !important;
        background-color: #fff1f0 !important; 
    }

    .text-wrapper {
        display: inline-block;
        max-width: 200px;
        word-wrap: break-word;
        white-space: normal;
    }

    .editable {
        cursor: pointer;
        position: relative;
        padding: 8px;
        min-height: 40px;
    }

    .editable .view-mode {
        display: block;
        padding: 6px 8px;
    }

    .editable .edit-mode {
        display: none;
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: white;
        z-index: 10;
        color: #495057;
    }

    .editable.editing .view-mode {
        display: none;
    }

    .editable.editing .edit-mode {
        display: block;
    }

    .editable-input,
    .editable-select {
        width: 100%;
        height: 100%;
        padding: 6px 8px;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
        box-sizing: border-box;
    }

    .editable-input:focus,
    .editable-select:focus {
        border-color: #40a9ff;
        outline: 0;
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.25);
    }

    .new-row .editable-input,
    .new-row .editable-select {
        background-color: #f8f9fa;
    }

    .engagement-cell {
        position: relative;
        min-height: 120px;
    }

    .engagement-loading {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 20;
    }

    .engagement-loading::after {
        content: "";
        display: block;
        width: 30px;
        height: 30px;
        border: 3px solid #ddd;
        border-radius: 50%;
        border-top-color: #1890ff;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }

    tr.new-row {
        background-color: #f6ffed !important;
        transition: background-color 3s ease;
    }

    tr.new-row td {
        border-left: 2px solid #52c41a !important;
        border-right: 2px solid #52c41a !important;
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

    /* Floating Action Button */
    .floating-div {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 1000;
    }

    .floating-div .dropdown-menu {
        min-width: 160px;
        border-radius: 2px;
        box-shadow: 0 3px 6px -4px rgba(0,0,0,0.12), 0 6px 16px 0 rgba(0,0,0,0.08), 0 9px 28px 8px rgba(0,0,0,0.05);
        border: 1px solid #f0f0f0;
    }

    .floating-div .dropdown-items {
        display: block;
        padding: 5px 12px;
        color: rgba(0, 0, 0, 0.65);
        transition: all 0.3s;
    }

    .floating-div .dropdown-items:hover {
        background-color: #f5f5f5;
        color: rgba(0, 0, 0, 0.85);
    }

    /* Modal Styles */
    .modal-content {
        border-radius: 2px;
        border: 1px solid #f0f0f0;
        box-shadow: 0 3px 6px -4px rgba(0,0,0,0.12), 0 6px 16px 0 rgba(0,0,0,0.08), 0 9px 28px 8px rgba(0,0,0,0.05);
    }

    .modal-header {
        border-bottom: 1px solid #f0f0f0;
        padding: 16px 24px;
    }

    .modal-title {
        color: rgba(0, 0, 0, 0.85);
        font-weight: 500;
    }

    .modal-body {
        padding: 24px;
    }

    table.dataTable thead .sorting:before,
    table.dataTable thead .sorting:after,
    table.dataTable thead .sorting_asc:before,
    table.dataTable thead .sorting_desc:before {
        display: none !important;
    }

    /* Tambahkan ikon dari Bootstrap Icons */
    table.dataTable thead .sorting::after {
        font-family: "bootstrap-icons";
        content: "\f0dc"; /* bi-arrow-down-up */
        float: right;
    }

    table.dataTable thead .sorting_asc::after {
        font-family: "bootstrap-icons";
        content: "\f148"; /* bi-arrow-up */
        float: right;
    }

    table.dataTable thead .sorting_desc::after {
        font-family: "bootstrap-icons";
        content: "\f13f"; /* bi-arrow-down */
        float: right;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .table-responsive {
            border-radius: 2px;
            overflow: hidden;
        }
        
        .floating-div {
            bottom: 16px;
            right: 16px;
        }
    }

    /* DataTable Custom Controls Styling */
    .datatable-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 5px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .datatable-controls .left-controls {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .datatable-controls .right-controls {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    /* Hide default DataTable controls */
    .dataTables_info,
    .dataTables_length {
        display: none !important;
    }

    /* Custom info and length controls */
    .custom-datatable-info {
        font-size: 14px;
        color: rgba(0, 0, 0, 0.65);
        margin: 0;
    }

    .custom-datatable-length {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: rgba(0, 0, 0, 0.65);
    }

    .custom-datatable-length select {
        height: 32px;
        padding: 4px 8px;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
        font-size: 14px;
        min-width: 60px;
        background: white;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .datatable-controls {
            flex-direction: column;
            gap: 12px;
            align-items: flex-start;
        }
        
        .datatable-controls .left-controls,
        .datatable-controls .right-controls {
            width: 100%;
            justify-content: space-between;
        }
    }
</style>

<div class="container-fluid py-3">
    <?php $this->load->view('influencer_dummy/menu') ?>

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-6" style="color: rgba(0, 0, 0, 0.85);">INFLUENCER LISTING - <?= strtoupper($page) ?? 'TIKTOK' ?></h5>
                <button id="addRow" class="btn btn-primary">
                    <i class="bi bi-plus me-1"></i> Tambah Baru
                </button>
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
                                    $arr = array('Username', 'Platform', 'URL', 'Deskripsi');
                                    foreach ($arr as $val) {
                                        $active = (($keyword_category ?? 'Username') == $val) ? 'background-color: #e6f7ff; color: #1890ff;' : '';
                                    ?>
                                        <li><a class="dropdown-item" href="<?= base_url() ?>/influencer_dummy?keyword_category=<?= $val ?><?= !empty($_GET['keyword']) ? '&keyword=' . $_GET['keyword'] : '' ?><?= !empty($_GET['start_date']) ? '&start_date=' . $_GET['start_date'] : '' ?><?= !empty($_GET['until_date']) ? '&until_date=' . $_GET['until_date'] : '' ?>"
                                                style="padding: 5px 12px; font-size: 14px; line-height: 22px; <?= $active ?>">
                                                <?= $val ?>
                                            </a></li>
                                    <?php } ?>
                                </ul>
                            </div>
                            <input type="hidden" name="keyword_category" value="<?= $keyword_category ?? 'Username' ?>">
                            <input type="text" name="keyword" class="form-control" placeholder="Search..." value="<?= isset($_GET['keyword']) ? $_GET['keyword'] : '' ?>"
                                style="border: 1px solid #d9d9d9; box-shadow: none; height: 32px; padding: 4px 11px; margin-right: 10px; border-radius: 2px;">
                            
                            <div class="d-flex" style="margin-right: 10px;">
                                <input type="text" class="form-control" id="tanggal" placeholder="Pilih rentang tanggal...">
                                <input type="hidden" name="start_date" id="start_date" value="<?= $_GET['start_date'] ?? $start_date ?>">
                                <input type="hidden" name="until_date" id="end_date" value="<?= $_GET['until_date'] ?? $until_date ?>">
                                <script>
                                    get_filter();
                                    function get_filter() {
                                        $.ajax({
                                            dataType: "json",
                                            url: '<?= base_url() ?>/ajax/get-filter',
                                            data: {
                                                start_date: "<?= $_GET['start_date'] ?? $start_date ?>",
                                                until_date: "<?= $_GET['until_date'] ?? $until_date ?>",
                                            },
                                            success: function(response) {
                                                $("#tanggal").after(response.html);
                                                $('#tanggal').on('apply.daterangepicker', function() {
                                                    table.ajax.reload();
                                                });
                                            },
                                            error: function(xhr, status, error) {
                                                console.error("Error loading filter:", error);
                                            }
                                        });
                                    }
                                </script>
                            </div>
                            
                            <button class="btn btn-primary" type="submit"
                                style="border-radius: 2px; height: 32px; padding: 0 15px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex justify-content-end">
                        <button type="button" class="btn btn-outline-secondary me-2" onclick="edit_niche()">
                            <i class="bi bi-pencil me-1"></i> Edit Niche
                        </button>
                        <button id="resetFilters" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filter
                        </button>
                    </div>
                </div>
                <?php if (!empty($notif)): ?>
                    <div class="alert alert-info" style="display: flex; align-items: center;">
                        <i class="bi bi-info-circle me-2"></i>
                        <span><?= strip_tags($notif) ?></span>
                    </div>
                <?php endif; ?>
            </form>

            <div class="datatable-controls">
                <div class="left-controls">
                    <div class="checkbox-wrapper-13">
                        <input id="c1-13" type="checkbox" value="1" class="checkAll">
                        <label for="c1-13">Pilih Semua Data</label>
                    </div>
                </div>
                <div class="right-controls">
                    <div class="custom-datatable-length">
                        <span>Tampilkan</span>
                        <select id="customLength">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span>data</span>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table id="influencerTable" class="table table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th class="text-start">#</th>
                            <th class="text-start">
                                Niche
                                <a href="javascript:void(0);" id="openNicheFilter" class="text-decoration-none">
                                    <i class="fas fa-filter pe-2 fs-12"></i>
                                </a>
                            </th>
                            <th class="text-start">URL</th>
                            <th class="text-start">Brand</th>
                            <th class="text-start">
                                PIC
                                <a href="javascript:void(0);" id="openPICFilter" class="text-decoration-none">
                                    <i class="fas fa-filter pe-2 fs-12"></i>
                                </a>
                            </th>
                            <th class="text-start">Kontak</th>
                            <th class="text-start">Platform</th>
                            <th class="text-start">
                                Engagement
                                <a href="javascript:void(0);" id="openEngagementFilter" class="text-decoration-none">
                                    <i class="fas fa-filter pe-3 fs-12"></i>
                                </a>
                            </th>
                            <th class="text-start">Ratecard</th>
                            <th class="text-start">Deskripsi</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($influencers as $inf): ?>
                        <tr data-id="<?= $inf->id ?>" class="<?= ($inf->is_generated == 1 ? 'active' : '') . ($inf->status == 'Nonaktif' ? ' deactive' : '') ?>">
                            <td>
                                <div class="checkbox-wrapper-13 d-inline">
                                    <input class="checkItem" type="checkbox" value="<?= $inf->id ?>" data-id="<?= $inf->id ?>" name="list_id" form="form-action">
                                </div>
                            </td>
                            <td class="editable select" data-field="niche">
                                <span class="view-mode"><?= htmlspecialchars($inf->niche) ?></span>
                                <div class="edit-mode">
                                    <select class="editable-select">
                                        <?php foreach ($niches as $item): ?>
                                            <option value="<?= htmlspecialchars($item['niche']) ?>" <?= $inf->niche == $item['niche'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($item['niche']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </td>

                            <?php
                                $full_url = $inf->url;
                                $display_text = $inf->url;
                                if (preg_match('/@[\w.\-]+/', $inf->url, $matches)) {
                                    $display_text = $matches[0];
                                } else {
                                    $parsed = parse_url($inf->url, PHP_URL_PATH);
                                    $segments = explode('/', rtrim($parsed, '/'));
                                    $last_segment = end($segments);
                                    if (!empty($last_segment)) {
                                        $display_text = '@' . $last_segment;
                                    }
                                }
                            ?>
                            <td class="editable" data-field="url" data-follower="<?= $inf->follower ?: 0 ?>">
                                <a class="view-mode" href="<?= htmlspecialchars($full_url) ?>" target="_blank"><?= htmlspecialchars($display_text) ?></a>
                                <div class="edit-mode">
                                    <input type="text" class="editable-input" value="<?= htmlspecialchars($inf->url) ?>">
                                </div>
                                <span class="loading"></span>
                                <div class="engagement-followers">
                                    <p class="mb-1 text-black"><?= $inf->follower ? $this->template->separator_only($inf->follower, '0', '.', '.') : '0' ?></p>
                                </div>
                            </td>
                          
                            <td class="editable select" data-field="brand">
                                <span class="view-mode"><?= htmlspecialchars($inf->brand) ?></span>
                                <div class="edit-mode">
                                    <select class="editable-select">
                                        <?php foreach ($brands as $brand): ?>
                                            <option value="<?= $brand->code ?>" <?= $inf->brand == $brand->code ? 'selected' : '' ?>>
                                                <?= $brand->code ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </td>
                            
                            <td class="editable select" data-field="pic">
                                <span class="view-mode"><?= htmlspecialchars($inf->pic) ?></span>
                                <div class="edit-mode">
                                    <select class="editable-select">
                                        <?php foreach ($pics as $pic): ?>
                                            <option value="<?= $pic->full_name ?>" <?= ($inf->pic == $pic->full_name || $inf->pic == $_SESSION['user']['full_name']) ? 'selected' : '' ?>>
                                                <?= $pic->full_name ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </td>

                            <?php
                                $contact_url = $inf->contact;
                                if (strpos($contact_url, 'http') !== 0) {
                                    $contact_url = 'https://' . ltrim($contact_url, '/');
                                }
                            ?>
                            <td class="editable" data-field="contact">
                                <a class="view-mode" href="<?= htmlspecialchars($contact_url) ?>" target="_blank"><?= htmlspecialchars($inf->contact) ?></a>
                                <div class="edit-mode">
                                    <input type="text" class="editable-input" value="<?= htmlspecialchars($inf->contact) ?>">
                                </div>
                            </td>

                            <td class="editable select" data-field="type">
                                <span class="view-mode"><?= htmlspecialchars($inf->type) ?></span>
                                <div class="edit-mode">
                                    <select class="editable-select">
                                        <option value="Tiktok" <?= $inf->type == 'Tiktok' ? 'selected' : '' ?>>Tiktok</option>
                                        <option value="Instagram" <?= $inf->type == 'Instagram' ? 'selected' : '' ?>>Instagram</option>
                                        <option value="YouTube" <?= $inf->type == 'YouTube' ? 'selected' : '' ?>>YouTube</option>
                                    </select>
                                </div>
                            </td>

                            <td class="text-start engagement-cell">
                                <div class="engagement-content">
                                    <p class="mb-1 text-black fw-bold">CPM : <?= $this->template->separator_only($inf->cpm_2, '0', '.', '.') ?></p>
                                    <p class="mb-1 text-black fw-bold">Avg View : <?= $this->template->separator_only($inf->avg_view_2, '2', '.', ',') ?></p>
                                    <p class="mb-1 text-black">ER : <?= number_format($inf->er, '2', ',', '.') ?></p>
                                </div>
                            </td>

                            <td class="editable" data-field="ratecard" data-ratecard="<?= $inf->ratecard ?>">
                                <span class="view-mode"><?= htmlspecialchars(number_format($inf->ratecard, '0', ',', '.')) ?></span>
                                <div class="edit-mode">
                                    <input type="text" class="editable-input ratecard-input" value="<?= htmlspecialchars($inf->ratecard) ?>">
                                </div>
                            </td>
                            
                            <td class="editable" data-field="desc">
                                <span class="view-mode">
                                    <span class="text-wrapper"><?= htmlspecialchars($inf->desc) ?></span>
                                </span>
                                <div class="edit-mode">
                                    <input type="text" class="editable-input desc-input" value="<?= $inf->desc ?>">
                                </div>
                            </td>

                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="border-0 bg-transparent p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item delete-row" href="#">
                                                <i class="fas fa-trash text-danger me-2"></i> Hapus
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item generate-row" href="#">
                                                <i class="fas fa-random text-primary me-2"></i> Generate
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="engagementFilterBox" class="filter-box">
                <label for="engagementType">Filter Berdasarkan:</label>
                <select id="engagementType" class="form-control mb-2">
                    <option value="cpm">CPM</option>
                    <option value="avg_view">Avg View</option>
                    <option value="er">ER</option>
                </select>

                <label>Min</label>
                <input type="number" id="minView" class="form-control mb-2">
                <label>Max</label>
                <input type="number" id="maxView" class="form-control mb-2">
                <button class="btn btn-sm btn-primary w-100" id="applyViewFilter">Terapkan</button>
            </div>

            <div id="picFilterBox" class="filter-box">
                <label>Filter PIC:</label>
                <!-- Select All Checkbox -->
                <div style="margin-bottom: 8px;">
                    <label style="display: flex; align-items: center;">
                        <input type="checkbox" id="selectAllPic" class="me-2">
                        Pilih Semua
                    </label>
                </div>

                <div id="picOptions" style="max-height: 200px; overflow-y: auto; border: 1px solid #f0f0f0; padding: 5px; margin-bottom: 12px;">
                    <?php foreach ($filter_pic as $item): ?>
                        <div>
                            <label style="display: flex; align-items: center; margin-bottom: 8px;">
                                <input type="checkbox" class="pic-checkbox me-2" value="<?= htmlspecialchars($item['pic']) ?>">
                                <?= htmlspecialchars($item['pic']) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="btn btn-sm btn-primary w-100" id="applyPicFilter">Terapkan</button>
            </div>

            <div id="nicheFilterBox" class="filter-box">
                <label>Filter Niche:</label>
                <!-- Select All Checkbox -->
                <div style="margin-bottom: 8px;">
                    <label style="display: flex; align-items: center;">
                        <input type="checkbox" id="selectAllNiche" class="me-2">
                        Pilih Semua
                    </label>
                </div>

                <div id="nicheOptions" style="max-height: 200px; overflow-y: auto; border: 1px solid #f0f0f0; padding: 5px; margin-bottom: 12px;">
                    <?php foreach ($filter_niche as $item): ?>
                        <div>
                            <label style="display: flex; align-items: center; margin-bottom: 8px;">
                                <input type="checkbox" class="niche-checkbox me-2" value="<?= htmlspecialchars($item['niche']) ?>">
                                <?= htmlspecialchars($item['niche']) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="btn btn-sm btn-primary w-100" id="applyNicheFilter">Terapkan</button>
            </div>

        </div>
    </div>
</div>

<div class="floating-div">
    <div class="dropdown">
        <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-gear me-1"></i> Aksi
        </button>
        <ul class="dropdown-menu">
            <li>
                <a class="dropdown-item" href="#!" onclick="refresh_data()">
                    <i class="bi bi-bootstrap-reboot me-2"></i> Refresh Data
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="#!" onclick="generate_data()">
                    <i class="bi bi-eye me-2"></i> Generate Data
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="#!" onclick="hapus_data()">
                    <i class="bi bi-trash me-2"></i> Hapus Data
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="#!" onclick="nonaktifkan_data()">
                    <i class="bi bi-slash-circle me-2"></i> Nonaktifkan Data
                </a>
            </li>
        </ul>
    </div>
</div>

<div class="modal fade" id="modal-form" tabindex="-1" role="dialog" aria-labelledby="modalFormLabel" aria-hidden="true">
    <div class="modal-dialog" id="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="title-form"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="load-form"></div>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="id_selected" name="id_selected" form="form-action">

<script>
    function showModal(title, url, isLarge = false) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html(title);

        if (isLarge) {
            $("#modal-form .modal-dialog").addClass("modal-lg");
        } else {
            $("#modal-form .modal-dialog").removeClass("modal-lg");
        }

        $("#load-form").load(url);
    }

    function edit_niche() {
        showModal('Edit Niche', '<?= site_url('influencer_dummy/edit_niche') ?>');
    }
    function hapus_data(id) {
        showModal('Hapus Data', `<?= base_url() ?>/influencer-dummy/action?code=hapus_data&id=${id}`);
    }

    function generate_data(id) {
        showModal('Generate Influencer', `<?= base_url() ?>/influencer-dummy/action?code=generate_data&id=${id}`);
    }

    function nonaktifkan_data(id) {
        showModal('Nonaktifkan Data', `<?= base_url() ?>/influencer-dummy/action?code=nonaktifkan_data&id=${id}`);
    }

    function edit_niche() {
        showModal('Edit Niche', `<?= base_url() ?>/influencer-dummy/edit_niche`);
    }

    $('input[name="list_id"]').change(function() {
        get_id();
    });

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
    }
</script>
<script>
$(document).ready(function() {
    var table = $('#influencerTable').DataTable({
        responsive: true,
        orderFixed: [[0, 'desc']],
        info: false,
        lengthChange: false,
        dom: 'rt<"bottom"p>', 
        drawCallback: function(settings) {
            updateCheckboxes();
        },
        columnDefs: [
            { responsivePriority: 1, targets: 1 }, // Username
            { responsivePriority: 2, targets: -1 }, // Aksi
            { responsivePriority: 3, targets: 0 }, // Niche
            { orderable: false, targets: [0, 5, 9] },
            {
                targets: 8, // Kolom Rate Card
                type: 'num',
                render: function(data, type, row, meta) {
                    if (type === 'sort' || type === 'order') {
                        const cell = table.cell(meta.row, meta.col).node();
                        return $(cell).data('ratecard') || 0;
                    }
                    return data;
                }
            },
            {
                targets: 2, // Kolom URL
                type: 'num',
                render: function(data, type, row, meta) {
                    if (type === 'sort' || type === 'order') {
                        const cell = table.cell(meta.row, meta.col).node();
                        return $(cell).data('follower') || 0;
                    }
                    return data;
                }
            }
        ],
        language: {
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            lengthMenu: "Tampilkan _MENU_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            zeroRecords: "Tidak ada data yang cocok",
            infoFiltered: "(disaring dari _MAX_ total data)",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Berikutnya",
                previous: "Sebelumnya"
            },
            emptyTable: "Tidak ada data tersedia"
        },
        initComplete: function () {
            updateSortIcons();
        }
    });

    function updateCustomInfo() {
        var info = table.page.info();
        var infoText = `Menampilkan ${info.start + 1} sampai ${info.end} dari ${info.recordsTotal} data`;
        if (info.recordsFiltered !== info.recordsTotal) {
            infoText += ` (disaring dari ${info.recordsTotal} total data)`;
        }
        $('#customInfo').text(infoText);
    }

    window.refresh_data = function(id = null) {
        let listId = id ? id : $('#id_selected').val();

        if (!listId) {
            Swal.fire('Error', 'Tidak ada ID yang dipilih', 'error');
            return;
        }

        let cleanIds = listId
            .split(',')
            .map(item => item.trim())
            .filter(item => item !== '0' && item !== '');

        console.log('Refreshing data for ListId:', cleanIds);

        if (cleanIds.length === 0) {
            Swal.fire('Error', 'Tidak ada ID valid', 'error');
            return;
        }

        cleanIds.forEach(singleId => {
            syncInfluencerData(singleId);
        });
    };



    function updateCheckboxes() {
        var allChecked = true;
        var anyChecked = false;

        $('.checkItem').each(function() {
            if ($(this).is(':checked')) {
                anyChecked = true;
            } else {
                allChecked = false;
            }
        });

        $('.checkAll').prop('checked', allChecked && anyChecked);
        $('.checkAll').prop('indeterminate', anyChecked && !allChecked);
    }

    $('#customLength').on('change', function() {
        var length = $(this).val();
        table.page.len(length).draw();
    });

    $('.checkAll').on('change', function() {
        var isChecked = $(this).prop('checked');
        $('.checkItem:visible').prop('checked', isChecked);
        get_id(); 
    });

    $(document).on('change', '.checkItem', function() {
        updateCheckboxes();
        get_id(); 
    });

    updateCustomInfo();
    updateCheckboxes();

    function updateSortIcons() {
        $('#influencerTable thead th').each(function () {
            const $th = $(this);
            $th.find('i.bi').remove();

            if ($th.hasClass('sorting_disabled')) {
                return; 
            }

            if ($th.hasClass('sorting_asc')) {
                $th.append('<i class="bi bi-arrow-up" style="font-size: 0.8em; margin-left: 5px;"></i>');
            } else if ($th.hasClass('sorting_desc')) {
                $th.append('<i class="bi bi-arrow-down" style="font-size: 0.8em; margin-left: 5px;"></i>');
            } else {
                $th.append('<i class="bi bi-arrow-down-up" style="font-size: 0.8em; margin-left: 5px;"></i>');
            }
        });
    }

    table.on('order.dt', function () {
        updateSortIcons();
    });

    function showFilterBox(triggerId, boxId) {
        const $trigger = $('#' + triggerId);
        const $box = $('#' + boxId);

        $('.filter-box').hide();

        if ($trigger.length && $box.length) {
            const offset = $trigger.offset();
            const height = $trigger.outerHeight();

            $box.css({
                display: 'block',
                top: offset.top + height - 100 + 'px',
                left: offset.left - 400 + 'px'
            });
        }
    }

    // PIC - Select All
    $('#selectAllPic').on('change', function() {
        $('.pic-checkbox').prop('checked', this.checked);
    });

    // Niche - Select All
    $('#selectAllNiche').on('change', function() {
        $('.niche-checkbox').prop('checked', this.checked);
    });

    $('.pic-checkbox').on('change', function() {
        $('#selectAllPic').prop('checked', $('.pic-checkbox:checked').length === $('.pic-checkbox').length);
    });

    $('.niche-checkbox').on('change', function() {
        $('#selectAllNiche').prop('checked', $('.niche-checkbox:checked').length === $('.niche-checkbox').length);
    });


    $('#openEngagementFilter').on('click', function (e) {
        e.stopPropagation();
        showFilterBox('openEngagementFilter', 'engagementFilterBox');
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('#engagementFilterBox, #openEngagementFilter').length) {
            $('#engagementFilterBox').hide();
        }
    });

    let activeFilters = {
        engagement: null,
        pic: [],
        niche: []
    };

    $.fn.dataTable.ext.search.push(function(settings, searchData, dataIndex) {
        const rowNode = table.row(dataIndex).node();
        
        if ($(rowNode).hasClass('new-row')) {
            return true;
        }

        if (activeFilters.engagement) {
            const type = activeFilters.engagement.type;
            const min = activeFilters.engagement.min;
            const max = activeFilters.engagement.max;
            
            let text = '';
            switch (type) {
                case 'avg_view':
                    text = $(rowNode).find('.engagement-cell p:contains("Avg View")').text();
                    break;
                case 'cpm':
                    text = $(rowNode).find('.engagement-cell p:contains("CPM")').text();
                    break;
                case 'er':
                    text = $(rowNode).find('.engagement-cell p:contains("ER")').text();
                    break;
            }

            const value = parseFloat(
                text.replace(/[^0-9.,]/g, '').replace(/\./g, '').replace(',', '.')
            );

            if (isNaN(value)) return false;
            if (value < min || value > max) return false;
        }

        if (activeFilters.pic.length > 0) {
            const picValue = $(rowNode).find('td').eq(4).find('.view-mode').text().trim();
            if (!activeFilters.pic.includes(picValue)) return false;
        }

        if (activeFilters.niche.length > 0) {
            const nicheValue = $(rowNode).find('td').eq(1).find('.view-mode').text().trim();
            if (!activeFilters.niche.includes(nicheValue)) return false;
        }

        return true;
    });

    $('#applyViewFilter').on('click', function() {
        activeFilters.engagement = {
            type: $('#engagementType').val(),
            min: parseFloat($('#minView').val()) || 0,
            max: parseFloat($('#maxView').val()) || Infinity
        };
        table.draw();
        $('#engagementFilterBox').hide();
    });

    $('#applyPicFilter').on('click', function() {
        activeFilters.pic = $('.pic-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        table.draw(); 
        $('#picFilterBox').hide();
    });

    $('#applyNicheFilter').on('click', function() {
        activeFilters.niche = $('.niche-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        table.draw();
        $('#nicheFilterBox').hide();
    });

    setTimeout(function() {
        $('tr.new-row').removeClass('new-row');
    }, 5000);

    let selectedPICs = []; 

    $('#openPICFilter').on('click', function (e) {
        e.stopPropagation();
        showFilterBox('openPICFilter', 'picFilterBox');
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('#picFilterBox, #openPICFilter').length) {
            $('#picFilterBox').hide();
        }
    });

    let selectedNiches = [];    

    $('#openNicheFilter').on('click', function (e) {
        e.stopPropagation();
        showFilterBox('openNicheFilter', 'nicheFilterBox');
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.filter-box, #openEngagementFilter, #openPICFilter, #openNicheFilter').length) {
            $('.filter-box').hide();
        }
    });

    const niches = <?= json_encode($niches) ?>;

    $('#addRow').click(function() {
        let type = '<?= $page ?>';
        type = type.charAt(0).toUpperCase() + type.slice(1);
        
        $.post('<?= site_url("influencer_dummy/save") ?>', { type: type }, function(res) {
            if (res.status === 'success') {
                location.reload();
            } else {
                Swal.fire('Error', 'Gagal menambahkan data baru', 'error');
            }
        }, 'json');
    });



    $('#influencerTable tbody').on('click', '.editable:not(.editing)', function() {
        const cell = $(this);
        cell.addClass('editing');
        const input = cell.find('.edit-mode :input').first();
        input.focus();
        
        if (input.is('select')) {
        input.trigger('focus');
        } else {
        input.select();
        }
    });

    $('#influencerTable tbody').on('blur', '.editable .edit-mode :input', function() {
        const cell = $(this).closest('.editable');
        saveCell(cell);
    }).on('keypress', '.editable .edit-mode :input', function(e) {
        if (e.which === 13) {
        const cell = $(this).closest('.editable');
        saveCell(cell);
        return false;
        }
    });

    function formatNumber(n) {
        n = parseFloat(n);
        if (isNaN(n)) return '0';
        n = Math.round(n); 
        return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    function unformatNumber(n) {
        return n.replace(/\./g, '').replace(',', '.'); 
    }


    $('#influencerTable tbody').on('input', '.ratecard-input', function() {
        const caret = this.selectionStart;
        const unformatted = unformatNumber(this.value);
        this.value = formatNumber(unformatted);
        this.setSelectionRange(caret, caret);
    });

    function syncInfluencerData(id) {
        const row = $(`tr[data-id="${id}"]`);
        const urlCell = row.find('td[data-field="url"]');
        const input = urlCell.find('.editable-input');
        const engagementCell = row.find('.engagement-cell');
        const engagementContent = engagementCell.find('.engagement-content');
        const engagementFollower = engagementCell.find('.engagement-follower');
        
        engagementContent.hide();
        engagementCell.append('<div class="engagement-loading"></div>');
        input.prop('disabled', true).addClass('loading');

        $.ajax({
            url: '<?= site_url("influencer_dummy/sync_external_process") ?>',
            type: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    urlCell.attr('data-follower', response.data.follower || 0);
                    
                    engagementContent.html(`
                        <p class="mb-1 text-black fw-bold">CPM : ${formatNumber(response.data.cpm)}</p>
                        <p class="mb-1 text-black fw-bold">Avg View : ${formatNumber(response.data.avg_view)}</p>
                        <p class="mb-1 text-black">ER : ${formatNumber(response.data.er)}</p>
                    `);

                    engagementFollower.text(response.data.follower?.toLocaleString('id-ID') || '0');
                    const usernameFollower = row.find('td[data-field="url"] .engagement-followers p');
                    usernameFollower.text(`${response.data.follower?.toLocaleString('id-ID') || '0'}`);

                    if (response.data.ratecard) {
                        const ratecardCell = row.find('td[data-field="ratecard"] .view-mode');
                        ratecardCell.text(formatNumber(response.data.ratecard.toString()));
                    }

                    if (response.data.username) {
                        const usernameCell = row.find('td[data-field="username"] .view-mode');
                        usernameCell.text(response.data.username);
                    }
                }
            },
            complete: function() {
                engagementCell.find('.engagement-loading').remove();
                engagementContent.show();
                input.prop('disabled', false).removeClass('loading');
                table.row(row).invalidate().draw();
            }
        });
    }


    $('#influencerTable tbody').on('click', '.sync-url', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        syncInfluencerData(id);
    });

    function formatDecimal(val) {
        if (val === null || val === undefined || isNaN(val)) return 'N/A';
        return parseFloat(val).toFixed(2);
    }

    function saveCell(cell) {
        const field = cell.data('field');
        const input = cell.find('.edit-mode :input');
        let value = input.is('select') ? input.find('option:selected').val() : input.val();

        if (field === 'ratecard') {
        value = unformatNumber(value);
        input.val(formatNumber(value));
        }

        const row = cell.closest('tr');
        const id = row.data('id');

        cell.find('.view-mode').text(field === 'ratecard' ? formatNumber(value) : value);

        if (id === 0 || id === "0") {
        cell.removeClass('editing');
        return;
        }

        $.post('<?= site_url("influencer_dummy/update_field") ?>', {
            id: id,
            field: field,
            value: value
        }, function(res) {
            if (res.status === 'success') {
                cell.removeClass('editing');

                if ((field === 'url' || field === 'ratecard') && value) {
                    syncInfluencerData(id);
                }
            } else {
                if (!Swal.isVisible()) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menyimpan',
                        text: res.message || 'Gagal menyimpan perubahan.'
                    });
                }
            }
        }, 'json');
    }

    // Hapus data
    $('#influencerTable tbody').on('click', '.delete-row', function() {
        const row = $(this).closest('tr');
        const id = row.data('id');

        Swal.fire({
            title: 'Hapus Data',
            text: "Apakah Anda yakin ingin menghapus data ini?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Hapus Data',
            cancelButtonText: 'Batal',
            reverseButtons: true,
            customClass: {
                confirmButton: 'btn btn-primary me-2',
                cancelButton: 'btn btn-secondary'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('<?= site_url("influencer_dummy/delete/") ?>' + id, {}, function(res) {
                    if (res.status === 'success') {
                        table.row(row).remove().draw();
                    } else {
                        Swal.fire('Gagal', 'Gagal menghapus data.', 'error');
                    }
                }, 'json');
            }
        });
    });


    // Generate data
    $('#influencerTable tbody').on('click', '.generate-row', function() {
        const row = $(this).closest('tr');
        const id = row.data('id');
        
        $.post('<?= site_url("influencer_dummy/generate/") ?>' + id, {}, function(res) {
            if (res.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Data berhasil digenerate.',
                    showConfirmButton: true,
                    customClass: {
                        confirmButton: 'btn btn-primary me-2',
                    }
                });
            } else if (res.status === 'duplicate') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Duplikat',
                    text: 'Data sudah ada di tabel influencer.',
                    customClass: {
                        confirmButton: 'btn btn-primary me-2',
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: 'Gagal generate data.'
                });
            }
        }, 'json');
    });

    $('#resetFilters').click(function() {
        activeFilters = {
            engagement: null,
            pic: [],
            niche: []
        };
        
        $('#minView, #maxView').val('');
        $('.pic-checkbox, .niche-checkbox').prop('checked', false);
        
        table.draw();
        
        $('tr.new-row').removeClass('new-row');
    });

});
</script>