<script>
(function () {
    function delAll(p, base){ p.delete(base); p.delete(base+'[]'); }
    function hasActiveFiltersSearch(searchStr){
        const p = new URLSearchParams(searchStr || '');
        const ff = p.getAll('filter_field').length || p.getAll('filter_field[]').length;
        const fv = p.getAll('filter_value').length || p.getAll('filter_value[]').length;
        return (ff > 0 && fv > 0);
    }
    function isHardReload(){
        try {
            const nav = performance.getEntriesByType && performance.getEntriesByType('navigation')[0];
            if (nav) return nav.type === 'reload' || nav.type === 'back_forward';
            return performance.navigation && performance.navigation.type === 1;
        } catch { return false; }
    }
    const SKIP_KEY = 'allowFilteredReloadOnce';
    if (isHardReload() && hasActiveFiltersSearch(location.search) && !sessionStorage.getItem(SKIP_KEY)) {
        var ok = confirm('Filter sedang aktif.\nReset semua filter sebelum memuat ulang?');
        if (ok) {
            const p = new URLSearchParams(location.search);
            delAll(p,'filter_field'); delAll(p,'filter_value'); delAll(p,'filter_operator');
            p.set('page','1');
            try {
                document.open(); document.write(''); document.close();
            } catch(e){}
            location.replace(location.pathname + '?' + p.toString());
            throw new Error('ABORT_INITIAL_LOAD_AFTER_CONFIRM_REDIRECT');
        } else {
            sessionStorage.setItem(SKIP_KEY, '1');
        }
    } else {
        if (sessionStorage.getItem(SKIP_KEY)) sessionStorage.removeItem(SKIP_KEY);
    }
})();
</script>

<style>
    #agGridWrapper { height: 70vh; position: relative; margin-bottom: 10px; }
    .badge { padding: 2px 6px; border-radius: 10px; font-size: 12px; }
    .bg-green{background:#28a745;color:#fff}
    .bg-red{background:#dc3545;color:#fff}
    .bg-blue{background:#0d6efd;color:#fff}
    .bg-grey{background:#6c757d;color:#fff}
    .hdr-dd {
        display:none; position: absolute; top: 100%; left: 0;
        z-index: 1000; background: #fff; border: 1px solid #ddd;
        padding: 8px; min-width: 240px; max-height: 260px; overflow: auto;
        box-shadow: 0 8px 20px rgba(0,0,0,.12); border-radius: 6px;
    }
    .ag-center-cols-container .ag-cell.ta-center { text-align: center; }

    .cell-date {
        display: inline-grid;
        grid-auto-flow: column;
        align-items: center;
        justify-content: center;
        column-gap: 6px;
    }
    .cell-date .marker {
        width: 8px; height: 8px;
        border-radius: 9999px;
    }
    .cell-date .marker.is-pink  { background: #f99ccaff; } 
    .cell-date .marker.is-empty { background: transparent; } 


    .ag-theme-quartz .ag-header-cell .hdr-filter{
        display:flex;
        align-items:center;
        justify-content:center;  
        gap:0.1rem;
        width:100%;
        text-align:center;
    }
    .ag-theme-quartz .hdr-filter .hdr-title{ font-weight: 500; }

    .ag-theme-quartz .ag-header-cell.hdr-center .ag-header-cell-label{
        justify-content:center;
    }
    .ag-theme-quartz .ag-header-cell.sortable { cursor: pointer; position: relative; }
    .ag-theme-quartz .ag-header-cell.sortable:hover { background-color: #f8f9fa; }
    .ag-theme-quartz .ag-header-cell.sortable i { font-size: 0.8em; margin-left: 2px; }
    .ag-theme-quartz .dropdown-menu {
        position: absolute !important;
        z-index: 1050 !important; 
    }
    .hdr-filter .hdr-title { cursor: pointer; user-select: none; }
    .hdr-dd.open { display:block; }
    .hdr-dd .search { width:80%; margin-bottom:6px; }
    .hdr-dd .actions { display:flex; justify-content: space-between; margin-top:6px; }
    .hdr-dd-portal{
        position: absolute;
        z-index: 1000;
        width: 260px;
        max-height: 340px;
        background: #fff;
        border: 1px solid #e6e6e6;
        border-radius: 10px;
        box-shadow: 0 6px 18px rgba(0,0,0,.12);
        display: flex;
        flex-direction: column;
    }
    .hdr-dd-portal .search{
        margin: 8px !important;
        width: 240px !important;
    }
    .hdr-dd-portal .list{
        padding: 8px;
        overflow: auto;
        flex: 1 1 auto;
    }
    .hdr-dd-portal .actions{
        position: sticky;
        bottom: 0;
        background: #fff;
        padding: 6px 8px;
        border-top: 1px solid #eee;
        display: flex;
        gap: 6px;
        justify-content: flex-end;
    }
    .btn-2xs{
        padding: 2px 8px !important;
        font-size: 12px !important;
        line-height: 1.2 !important;
        border-radius: 6px !important;
    }
    .hdr-dd-portal .list label{
        font-size: 12px;
        cursor: pointer;
    }
    .hdr-dd-portal .list input[type="checkbox"]{
        margin-right: 6px;
    }
    #colChooser {
        padding: 10px;
        background-color: #f8f9fa;
        border-radius: 5px;
        margin-bottom: 15px;
    }
    #colChooser .column-list {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    #colChooser label {
        display: flex;
        align-items: center;
        margin-bottom: 0;
        cursor: pointer;
        padding: 5px 10px;
        background: white;
        border-radius: 4px;
        border: 1px solid #dee2e6;
    }
    #colChooser label:hover {
        background: #e9ecef;
    }
    .filter-badge {
        font-size: 0.6em !important;
        padding: 0.25em 0.4em !important;
    }
    .hdr-filter .btn-filter {
        background: none;
        border: none;
        padding: 0.25rem;
        margin-left: 2px !important;
        opacity: 0.6;
        transition: opacity 0.2s;
    }
    .hdr-filter .btn-filter:hover {
        opacity: 1;
    }
    .hdr-filter.active .btn-filter {
        opacity: 1;
        color: #0d6efd;
    }
    .filter-section {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }

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
<div class="container-fluid py-1">
	<div class="card">
		<div class="card-header">
			<div class="d-flex justify-content-between align-items-center">
				<h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Pengeluaran</h5>
				<a href="#!" onclick="create()" class="btn btn-primary">
					<i class="bi bi-plus me-1"></i> Tambah Data
				</a>
			</div>
		</div>

		<div class="card-body">
			<form action="" class="search-form" onsubmit="return handleExpenseSearch(event)">
				<div class="row g-2 mb-2 align-items-start justify-content-between">
                    <!-- Bagian filter kiri -->
                    <div class="col d-flex flex-wrap gap-2">
                        <div class="col-lg-1">
                            <select class="form-control" name="brand">
                                <option value="">Brand</option>
                                <?php foreach ($brands as $val):
                                    $text = ($_GET["brand"] ?? '') == $val["code"] ? "selected" : "";
                                ?>
                                    <option <?= $text ?> value="<?= $val["code"] ?>"><?= $val["code"] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Keyword + Category -->
                        <div class="col-md-3">
                            <div class="d-flex gap-2">
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <?= $keyword_category ?? 'Judul' ?>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <?php
                                            $arr = ['Judul','Keterangan'];
                                            foreach ($arr as $val):
                                                $active = (($keyword_category ?? 'Judul') == $val) ? 'style="background:#e6f7ff;color:#1890ff;"' : '';
                                        ?>
                                            <li><a class="dropdown-item" <?=$active?> href="<?= $url ?>&keyword_category=<?= $val ?>"><?= $val ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>

                                <input type="hidden" name="keyword_category" value="<?= $keyword_category ?? 'Judul' ?>">
                                <input type="text" name="keyword" class="form-control" placeholder="Cari kata kunci..." value="<?= $_GET['keyword'] ?? '' ?>">
                            </div>
                        </div>

                        <!-- Date range -->
                        <div class="col-md-2">
                            <input type="text" class="form-control" id="tanggal" placeholder="Pilih rentang tanggal...">
                            <input type="hidden" name="start_date" id="start_date" value="<?= $_GET['start_date'] ?? $start_date ?>">
                            <input type="hidden" name="until_date" id="end_date" value="<?= $_GET['until_date'] ?? $until_date ?>">
                        </div>
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

                        <!-- Submit -->
                        <div class="col-md-1 d-grid">
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-search"></i>&nbsp; Cari
                            </button>
                        </div>
                    </div>

                    <!-- Tombol kanan -->
                    <div class="col-md-1 d-grid ms-auto">
                        <button type="button" class="btn btn-outline-secondary" onclick="edit_kategori()">
                            <i class="bi bi-pencil me-1"></i> Kategori
                        </button>
                    </div>
                </div>


				<?php if (!empty($notif)): ?>
					<div class="alert alert-info d-flex align-items-center mt-2">
						<i class="bi bi-info-circle me-2"></i>
						<span><?= strip_tags($notif) ?></span>
					</div>
				<?php endif; ?>
			</form>

			<!-- Tabel / list body -->
			<div class="col-lg-12">
				<div id="tbody"></div>
			</div>
		</div>
	</div>
</div>




<input type="hidden" id="id_selected" name="id_selected" form="form-action">


<div class="floating-div">
    <button class="btn mb-2 btn-edit-active dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-gear fs-16"></i> Aksi
    </button>
    <ul class="dropdown-menu text-end" style="padding:0px;background:unset;border:unset">
        <li><a class="dropdown-items" href="#!" style="padding:0px">
                <button type="button" class="btn mb-2 btn-edit-active" onclick="hapus_data()">
                    <i class="bi bi-trash fs-16"></i> Hapus Data
                </button>
            </a>
        </li>
    </ul>
</div>


</div>

<div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="modal-form">
    <div class="modal-dialog" id="modal-dialog">
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

<script>
    function create() {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Create Data');
        $("#load-form").load("<?= base_url() ?>/expense/create", function() {
            setupRecurringTypeListener();
        });
    }

    function remove(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Hapus Data');
        $("#load-form").load("<?= base_url() ?>/expense/remove?id=" + id);
    }

    function edit(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Edit Data');
        $("#load-form").load("<?= base_url() ?>/expense/edit?id=" + id, function() {
            setupRecurringTypeListener();
        });
    }

    function edit_kategori() {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Edit Kategori');
        $("#load-form").load("<?= base_url() ?>/expense/edit_category");
    }

    function setupRecurringTypeListener() {
        console.log("Setting up recurring type listener...");

        const isRecurringCheckbox = document.getElementById('is_recurring');
        const recurringTypeContainer = document.getElementById('recurring_type_container');
        const recurringDetailContainer = document.getElementById('recurring_detail_container');
        const recurringTypeSelect = document.getElementById('recurring_type');

        if (isRecurringCheckbox && recurringTypeContainer && recurringDetailContainer && recurringTypeSelect) {

            function toggleRecurringFields() {
                if (isRecurringCheckbox.checked) {
                    recurringTypeContainer.style.display = 'block';
                    recurringDetailContainer.style.display = 'block';
                    recurringTypeSelect.dispatchEvent(new Event('change'));
                } else {
                    recurringTypeContainer.style.display = 'none';
                    recurringDetailContainer.style.display = 'none';
                }
            }

            isRecurringCheckbox.addEventListener('change', toggleRecurringFields);

            recurringTypeSelect.addEventListener('change', function() {
                console.log("Recurring type changed to:", recurringTypeSelect.value);
                let detailInput = '';
                switch (recurringTypeSelect.value) {
                    case 'Mingguan':
                        detailInput = `
                        <label for="recurring_day">Pilih Hari</label>
                        <select class="form-control" name="dt[recurring_day]">
                            <option value="Senin">Senin</option>
                            <option value="Selasa">Selasa</option>
                            <option value="Rabu">Rabu</option>
                            <option value="Kamis">Kamis</option>
                            <option value="Jumat">Jumat</option>
                            <option value="Sabtu">Sabtu</option>
                            <option value="Minggu">Minggu</option>
                        </select>
                    `;
                        break;
                    case 'Bulanan':
                        detailInput = `
                        <label for="recurring_date">Pilih Tanggal (1-31)</label>
                        <input type="number" class="form-control" name="dt[recurring_date]" min="1" max="31">
                    `;
                        break;
                    default:
                        detailInput = '';
                }
                recurringDetailContainer.innerHTML = detailInput;

                if (isRecurringCheckbox.checked) {
                    const recurringDay = document.querySelector('select[name="dt[recurring_day]"]');
                    const recurringDate = document.querySelector('input[name="dt[recurring_date]"]');

                    if (recurringDay && window.recurringDayValue) {
                        recurringDay.value = window.recurringDayValue;
                    }
                    if (recurringDate && window.recurringDateValue) {
                        recurringDate.value = window.recurringDateValue;
                    }
                }
            });

            if (window.isEditMode && isRecurringCheckbox.checked) {
                toggleRecurringFields();
                recurringTypeSelect.value = window.recurringTypeValue;
                recurringTypeSelect.dispatchEvent(new Event('change'));
            } else {
                isRecurringCheckbox.dispatchEvent(new Event('change'));
            }
        } else {
            console.error("Elements not found!");
        }
    }
</script>
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

    function hapus_data(id) {
        showModal('Hapus Data', `<?= base_url() ?>/expense/action?code=hapus_data&id=${id}`);
    }

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

    function loadMoreData() {
        $.ajax({
            type: 'GET',
            url: "<?= base_url() ?>/expense/item<?= $param ?>",
            success: function(data) {
                $('#tbody').append(data);
            },
            error: function(xhr, status, error) {}
        });
    }
    loadMoreData();
</script>