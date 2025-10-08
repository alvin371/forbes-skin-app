<style>
	/* Ant Design-inspired styling */
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
		top: 0;
		right: 1px;
		width: 20px;
	}
	.select2 {
		height: 32px !important;
		min-width: 100% !important;
		margin-bottom: 8px;
	}

	.table {
		width: 100%;
		border-collapse: separate;
		border-spacing: 0;
		border: 1px solid #f0f0f0;
		border-radius: 2px;
	}
	.table thead th {
		background: #fafafa;
		color: rgba(0, 0, 0, 0.85);
		font-weight: 500;
		text-align: left;
		padding: 12px 8px;
		font-size: 14px;
		border-bottom: 1px solid #f0f0f0;
		transition: background .3s ease;
	}
	.table tbody td {
		padding: 12px 8px !important;
		font-size: 14px;
		color: rgba(0, 0, 0, 0.65);
		border-bottom: 1px solid #f0f0f0;
		transition: background .3s ease;
	}
	.table-hover tbody tr:hover {
		background: #fafafa;
		cursor: pointer;
	}

	.product-row {
		cursor: pointer;
	}
	.product-row:hover {
		background: #fafafa;
	}
	.variant-row {
		background: #fafafa;
		display: none;
	}
	.variant-row.show {
		display: table-row;
	}
	.expand-icon {
		transition: transform .3s ease;
		margin-right: 8px;
		font-size: 12px;
		color: #1890ff;
		cursor: pointer;
		display: inline-block;
	}
	.expand-icon.expanded {
		transform: rotate(90deg);
	}
	.product-row .expand-icon:hover {
		color: #40a9ff;
	}

	.card {
		border-radius: 2px;
		border: 1px solid #f0f0f0;
		box-shadow: 0 2px 8px rgba(0, 0, 0, .09);
		margin-bottom: 16px;
	}
	.card-header {
		background: #fff;
		border-bottom: 1px solid #f0f0f0;
		padding: 16px;
		height: 56px;
	}
	.card-body {
		padding: 16px;
	}

	.status-tabs {
		display: flex;
		gap: 0;
		margin-bottom: 16px;
		border-bottom: 1px solid #f0f0f0;
	}
	.tab-link {
		text-decoration: none;
		color: rgba(0, 0, 0, .65);
		position: relative;
		padding: 8px 16px 12px;
		font-size: 14px;
		font-weight: 400;
		border-bottom: 2px solid transparent;
		transition: all .3s;
	}
	.tab-link:hover {
		color: #1890ff;
	}
	.tab-link.active {
		color: #1890ff;
		font-weight: 500;
		border-bottom-color: #1890ff;
	}

	.btn {
		border-radius: 2px;
		padding: 4px 15px;
		font-size: 14px;
		height: 32px;
		line-height: 1.5;
		transition: all .3s cubic-bezier(.645, .045, .355, 1);
	}
	.btn-primary {
		background: #1890ff;
		border-color: #1890ff;
	}
	.btn-primary:hover {
		background: #40a9ff;
		border-color: #40a9ff;
	}
	.btn-outline-secondary {
		color: rgba(0, 0, 0, .65);
		border-color: #d9d9d9;
		background: #fff;
	}
	.btn-outline-secondary:hover {
		color: #40a9ff;
		border-color: #40a9ff;
	}

	.form-control {
		height: 32px;
		padding: 4px 11px;
		font-size: 14px;
		border: 1px solid #d9d9d9;
		border-radius: 2px;
		transition: all .3s;
	}
	.form-control:hover {
		border-color: #40a9ff;
	}
	.form-control:focus {
		border-color: #40a9ff;
		box-shadow: 0 0 0 2px rgba(24, 144, 255, .2);
	}

	.badge {
		font-size: 12px;
		height: 22px;
		padding: 0 8px;
		line-height: 22px;
		border-radius: 2px;
		font-weight: normal;
	}

	.search-form {
		margin-bottom: 16px;
	}
	.search-form .input-group {
		border-radius: 2px;
		display: flex;
		justify-content: space-between;
	}

	.floating-div {
		position: fixed;
		bottom: 20px;
		right: 20px;
		z-index: 1000;
		padding: 8px;
		border-radius: 2px;
	}
</style>


<div class="container-fluid">
    <?php $this->load->view('operasional/menu') ?>
    <?php $this->load->view('product/menu') ?>

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Product Management (Operasional)</h5>
                <?php if (in_array($user['role'], array('1', '2', '3', '6'))) { ?>
                    <div class="d-flex gap-2">
                        <!-- <a href="#!" onclick="sync_data('<?= $start_date ?>','<?= $until_date ?>')" class="btn btn-outline-secondary">
                            <i class="bi bi-cloud-download me-1"></i> Sync Data
                        </a> -->
                        <a href="#!" onclick="create()" class="btn btn-primary">
                            <i class="bi bi-plus me-1"></i> Tambah Data
                        </a>
                    </div>
                <?php } ?>
            </div>
        </div>

        <div class="card-body">
            <!-- Status Tabs -->
            <div class="status-tabs">
                <a href="<?= base_url('product?p=operasional&status=all') ?>"
                   class="tab-link <?= (!isset($_GET['status']) || $_GET['status'] == 'all' ? 'active' : '') ?>">
                   Semua (<?= $total_all ?>)
                </a>
                <a href="<?= base_url('product?p=operasional&status=active') ?>"
                   class="tab-link <?= (isset($_GET['status']) && $_GET['status'] == 'active' ? 'active' : '') ?>">
                   Aktif (<?= $total_active ?>)
                </a>
                <a href="<?= base_url('product?p=operasional&status=inactive') ?>"
                   class="tab-link <?= (isset($_GET['status']) && $_GET['status'] == 'inactive' ? 'active' : '') ?>">
                   Nonaktif (<?= $total_inactive ?>)
                </a>
            </div>

            <!-- Search Form -->
            <form action="<?= base_url('product') ?>" method="GET" class="search-form">
                <input type="hidden" name="p" value="operasional">
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <div class="dropdown" style="margin-right:10px;">
                                <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"
                                    style="border:1px solid #d9d9d9; border-radius:2px; color:rgba(0,0,0,0.65); background:#fff; height:32px; padding:4px 11px;">
                                    <span style="margin-right:8px;"><?= $keyword_category ?></span>
                                </button>
                                <ul class="dropdown-menu">
                                    <?php
                                    $arr = array('Nama Produk', 'SKU', 'Brand');
                                    foreach ($arr as $val) {
                                        $active = ($keyword_category == $val) ? 'background-color:#e6f7ff; color:#1890ff;' : '';
                                    ?>
                                        <li>
                                            <a class="dropdown-item" href="<?= $url ?>&keyword_category=<?= $val ?>"
                                               style="padding:5px 12px; font-size:14px; <?= $active ?>"><?= $val ?></a>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                            <input type="hidden" name="keyword_category" value="<?= $keyword_category ?>">
                            <input type="text" name="keyword" class="form-control" placeholder="Search..." value="<?= $_GET['keyword'] ?? '' ?>" style="margin-right:10px;">
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Table Section -->
            <div class="table-responsive" id="table-item">
                <table class="table table-hover" id="tbody">
                    <thead>
                        <tr>
                            <th style="width:40px;">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th style="width:60px;">#</th>
                            <th style="width:30px;"></th>
                            <th class="text-start">
                                <div class="d-flex align-items-center">
                                    <span>NAMA</span>
                                    <div class="ms-1 d-flex flex-column" style="line-height:.5;">
                                        <a href="javascript:void(0)" onclick="sortTable('name', 'asc')" style="color:rgba(0,0,0,.65);">
                                            <i class="bi bi-caret-up-fill" style="font-size:10px;"></i>
                                        </a>
                                        <a href="javascript:void(0)" onclick="sortTable('name', 'desc')" style="color:rgba(0,0,0,.65);">
                                            <i class="bi bi-caret-down-fill" style="font-size:10px;"></i>
                                        </a>
                                    </div>
                                </div>
                            </th>
                            <th class="text-start" style="width:150px;">
                                <div class="d-flex align-items-center">
                                    <span>HARGA</span>
                                    <div class="ms-1 d-flex flex-column" style="line-height:.5;">
                                        <a href="javascript:void(0)" onclick="sortTable('price_normal', 'asc')" style="color:rgba(0,0,0,.65);">
                                            <i class="bi bi-caret-up-fill" style="font-size:10px;"></i>
                                        </a>
                                        <a href="javascript:void(0)" onclick="sortTable('price_normal', 'desc')" style="color:rgba(0,0,0,.65);">
                                            <i class="bi bi-caret-down-fill" style="font-size:10px;"></i>
                                        </a>
                                    </div>
                                </div>
                            </th>
                            <th class="text-end" style="width:100px;">
                                <div class="d-flex align-items-center justify-content-end">
                                    <span>STOK</span>
                                    <div class="ms-1 d-flex flex-column" style="line-height:.5;">
                                        <a href="javascript:void(0)" onclick="sortTable('stock', 'asc')" style="color:rgba(0,0,0,.65);">
                                            <i class="bi bi-caret-up-fill" style="font-size:10px;"></i>
                                        </a>
                                        <a href="javascript:void(0)" onclick="sortTable('stock', 'desc')" style="color:rgba(0,0,0,.65);">
                                            <i class="bi bi-caret-down-fill" style="font-size:10px;"></i>
                                        </a>
                                    </div>
                                </div>
                            </th>
                            <?php if (in_array($user['role'], array('1', '2', '3', '6'))) { ?>
                                <th class="text-end" style="width:120px;">Action</th>
                            <?php } ?>
                        </tr>
                        <tr class="p-0" id="tbody-loading" style="background:unset!important">
                            <td class="text-start p-0" colspan="<?= in_array($user['role'], array('1', '2', '3', '6')) ? '8' : '7' ?>" style="background:unset!important">
                                <div class="mt-3">
                                    <?php $this->load->view('loading', true) ?>
                                </div>
                            </td>
                        </tr>
                    </thead>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-end mt-3">
                <?= $pagination ?>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="id_selected" name="id_selected" form="form-action">

<!-- Floating actions -->
<div class="floating-div">
    <button class="btn mb-2 btn-edit-active dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-gear fs-16"></i> Aksi
    </button>
    <ul class="dropdown-menu text-end" style="padding:0;background:unset;border:unset">
        <li>
            <a class="dropdown-items" href="#!" style="padding:0">
                <button type="button" class="btn mb-2 btn-edit-active" onclick="hapus_data()">
                    <i class="bi bi-trash fs-16"></i> Hapus Data
                </button>
            </a>
        </li>
        <li>
            <a class="dropdown-items" href="#!" style="padding:0">
                <button type="button" class="btn mb-2 btn-edit-active" onclick="nonaktifkan()">
                    <i class="bi bi-x-circle fs-16"></i> Nonaktifkan
                </button>
            </a>
        </li>
        <li>
            <a class="dropdown-items" href="#!" style="padding:0">
                <button type="button" class="btn mb-2 btn-edit-active" onclick="aktifkan()">
                    <i class="bi bi-check-circle fs-16"></i> Aktifkan
                </button>
            </a>
        </li>
    </ul>
</div>

<!-- Modal -->
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

<script>
    function create() {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#modal-dialog").addClass("modal-xl");
        $("#title-form").html('Create Data');
        $("#load-form").load("<?= base_url() ?>/product/create?p=operasional");
    }

    function remove(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#modal-dialog").removeClass("modal-xl modal-lg");
        $("#title-form").html('Hapus Data');
        $("#load-form").load("<?= base_url() ?>/product/remove?id=" + id);
    }

    function edit(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#modal-dialog").addClass("modal-xl");
        $("#title-form").html('Edit Data');
        $("#load-form").load("<?= base_url() ?>/product/edit?p=operasional&id=" + id);
    }

    function hapus_data(id) {
        showModal('Hapus Data', `<?= base_url() ?>/product/action?code=hapus_data&id=${id}`);
    }

    function nonaktifkan(id) {
        showModal('Nonaktifkan', `<?= base_url() ?>/product/action?code=nonaktifkan&id=${id}`);
    }

    function aktifkan(id) {
        showModal('Aktifkan', `<?= base_url() ?>/product/action?code=aktifkan&id=${id}`);
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

    // Bulk toggle parent + variants (opsional; sesuaikan endpoint jika dipakai)
    function updateParentStatus(parentId, status) {
        $(`input[name="status_${parentId}"], input.variant-toggle[data-parent-id="${parentId}"]`).prop('disabled', true);

        let variantIds = [];
        $(`input.variant-toggle[data-parent-id="${parentId}"]`).each(function() {
            variantIds.push($(this).attr('id').replace('status_switch_', ''));
        });

        let allIds = [parentId, ...variantIds];

        $.ajax({
            url: "<?= base_url('product/update_status_bulk') ?>",
            type: "POST",
            data: { ids: allIds, status: status },
            dataType: "json",
            success: function(response) {
                if(response.success) {
                    Swal.fire({ position:'top-end', text:'Status produk dan varian berhasil diubah', timer:2000, showConfirmButton:false });
                    $(`input[name="status_${parentId}"], input.variant-toggle[data-parent-id="${parentId}"]`).prop('checked', status === 'Aktif');
                } else {
                    $(`input[name="status_${parentId}"]`).prop('checked', !$(`input[name="status_${parentId}"]`).prop('checked'));
                    Swal.fire({ icon:'error', title:'Gagal', text: response.message || 'Gagal mengubah status' });
                }
            },
            error: function(xhr, status, error) {
                $(`input[name="status_${parentId}"]`).prop('checked', !$(`input[name="status_${parentId}"]`).prop('checked'));
                Swal.fire({ icon:'error', title:'Error', text:'Terjadi kesalahan: ' + error });
            },
            complete: function() {
                $(`input[name="status_${parentId}"], input.variant-toggle[data-parent-id="${parentId}"]`).prop('disabled', false);
            }
        });
    }

    function updateStatus(id, status) {
        $(`input[name="status_${id}"]`).prop('disabled', true);

        $.ajax({
            url: "<?= base_url('product/update_status') ?>",
            type: "POST",
            data: { id: id, status: status },
            dataType: "json",
            success: function(response) {
                if(response.success) {
                    Swal.fire({ position:'top-end', text:'Status berhasil diubah', timer:2000, showConfirmButton:false });
                } else {
                    $(`input[name="status_${id}"]`).prop('checked', !$(`input[name="status_${id}"]`).prop('checked'));
                    Swal.fire({ icon:'error', title:'Gagal', text: response.message || 'Gagal mengubah status' });
                }
            },
            error: function(xhr, status, error) {
                $(`input[name="status_${id}"]`).prop('checked', !$(`input[name="status_${id}"]`).prop('checked'));
                Swal.fire({ icon:'error', title:'Error', text:'Terjadi kesalahan: ' + error });
            },
            complete: function() {
                $(`input[name="status_${id}"]`).prop('disabled', false);
            }
        });
    }

    function get_id() {
        let list_id_v2 = '';
        const selectedValues = [];
        $('input[name="list_id"]').each(function() {
            if ($(this).is(":checked")) {
                selectedValues.push($(this).val());
                list_id_v2 += $(this).val() + ',';
            } else {
                selectedValues.push('0');
            }
        });
        if (list_id_v2.length > 0) list_id_v2 = list_id_v2.slice(0, -1);
        $('#id_selected').val(selectedValues.join(','));
        console.log(list_id_v2);
    }
</script>

<script>
    function sortTable(column, order) {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('sort', column);
        urlParams.set('order', order);
        urlParams.set('p', 'operasional');
        urlParams.delete('page');
        window.location.search = urlParams.toString();
    }

    function loadProductData() {
        $.ajax({
            type: 'GET',
            url: "<?= base_url() ?>/product/item_operasional<?= $param ?>",
            beforeSend: function() { $('#tbody-loading').show(); },
            success: function(data) {
                $('#tbody-loading').hide();
                $('#tbody').append(data);
                initializeProductInteractions();
            },
            error: function(xhr, status, error) {
                console.error('Error loading data:', error);
                $('#tbody-loading').html('<td colspan="8" class="text-center text-danger">Error loading data. Please try again.</td>');
            }
        });
    }

    function initializeProductInteractions() {
        // Expand/collapse variants
        $('.expand-icon').off('click').on('click', function(e) {
            e.stopPropagation();
            const productRow = $(this).closest('.product-row');
            const productId = productRow.data('product-id');
            const variantRows = $(`.variant-row[data-parent-id="${productId}"]`);
            const expandIcon = $(this);

            if (variantRows.length > 0) {
                if (variantRows.is(':visible')) {
                    variantRows.hide();
                    expandIcon.removeClass('expanded');
                } else {
                    variantRows.show();
                    expandIcon.addClass('expanded');
                }
            }
        });

        // Row click toggles variants, except interactive elements
        $('.product-row').off('click').on('click', function(e) {
            if ($(e.target).is('input, button, a, .form-check-input, .form-switch') ||
                $(e.target).closest('input, button, a, .form-check, .form-switch').length) {
                return;
            }
            $(this).find('.expand-icon').trigger('click');
        });

        // Bulk selection
        $('input[name="list_id"]').off('change').on('change', get_id);
        $('#selectAll').off('change').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('input[name="list_id"]').prop('checked', isChecked);
            get_id();
        });
    }

    // Initialize page
    $(document).ready(function() {
        loadProductData();

        // Initialize Bootstrap dropdowns
        var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
        dropdownElementList.map(function(el) { return new bootstrap.Dropdown(el); });
    });

    // Backward compat alias
    function loadMoreData() { loadProductData(); }
</script>
