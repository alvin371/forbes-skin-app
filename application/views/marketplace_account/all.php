<style>
	/* Ant Design-inspired styling (selaras dengan product) */
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
		color: rgba(0,0,0,.85);
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

	.card {
		border-radius: 2px;
		border: 1px solid #f0f0f0;
		box-shadow: 0 2px 8px rgba(0,0,0,.09);
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

	/* Status tabs ala Ant Design */
	.status-tabs {
		display: flex;
		gap: 0;
		margin-bottom: 16px;
		border-bottom: 1px solid #f0f0f0;
	}
	.tab-link {
		text-decoration: none;
		color: rgba(0,0,0,.65);
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

	.search-form {
		margin-bottom: 16px;
	}
	.search-form .input-group {
		border-radius: 2px;
		display: flex;
		justify-content: space-between;
	}

	.btn {
		border-radius: 2px;
		padding: 4px 15px;
		font-size: 14px;
		height: 32px;
		line-height: 1.5;
		transition: all .3s cubic-bezier(.645,.045,.355,1);
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
		color: rgba(0,0,0,.65);
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
		box-shadow: 0 0 0 2px rgba(24,144,255,.2);
	}

	.table {
		width: 100%;
		border-collapse: separate;
		border-spacing: 0;
		border: 1px solid #f0f0f0;
		border-radius: 2px;
	}
	.table thead th {
		background-color: #fafafa;
		color: rgba(0,0,0,.85);
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
		color: rgba(0,0,0,.65);
		border-bottom: 1px solid #f0f0f0;
		transition: background .3s ease;
	}
	.table-hover tbody tr:hover {
		background-color: #fafafa;
		cursor: pointer;
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

	<div class="card">
		<div class="card-header">
			<div class="d-flex justify-content-between align-items-center">
				<h5 class="mb-0" style="color: rgba(0,0,0,.85);">Toko Management</h5>
				<div class="d-flex gap-2">
					<a href="#!" onclick="create()" class="btn btn-primary">
						<i class="bi bi-plus me-1"></i> Tambah Toko
					</a>
				</div>
			</div>
		</div>

		<div class="card-body">
			<!-- Status Tabs -->
			<div class="status-tabs">
				<a href="<?= base_url('marketplace-account?status=active') ?>"
				   class="tab-link <?= (!isset($_GET['status']) || $_GET['status'] == 'active' ? 'active' : '') ?>">
				   Aktif (<?= $total_active ?>)
				</a>
				<a href="<?= base_url('marketplace-account?status=inactive') ?>"
				   class="tab-link <?= (isset($_GET['status']) && $_GET['status'] == 'inactive' ? 'active' : '') ?>">
				   Nonaktif (<?= $total_inactive ?>)
				</a>
			</div>

			<!-- Search Form -->
			<form action="" method="GET" class="search-form">
				<div class="row g-2">
					<div class="col-md-6">
						<div class="input-group">
							<div class="dropdown" style="margin-right: 10px;">
								<button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"
									style="border: 1px solid #d9d9d9; border-radius: 2px; color: rgba(0,0,0,.65); background:#fff; height:32px; padding:4px 11px;">
									<span style="margin-right: 8px;"><?= $keyword_category ?></span>
								</button>
								<ul class="dropdown-menu">
									<?php
										$arr = array('Nama Toko', 'ID Toko');
										foreach ($arr as $val) {
											$active = ($keyword_category == $val) ? 'background-color:#e6f7ff;color:#1890ff;' : '';
									?>
										<li>
											<a class="dropdown-item" href="<?= $url ?>&keyword_category=<?= $val ?>" style="padding:5px 12px; font-size:14px; <?= $active ?>">
												<?= $val ?>
											</a>
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
                <?php if (!empty($notif)): ?>
                    <div class="alert alert-info" style="display: flex; align-items: center;">
                        <i class="bi bi-info-circle me-2"></i>
                        <span><?= strip_tags($notif) ?></span>
                    </div>
                <?php endif; ?>
			</form>

			<!-- Table -->
			<div class="table-responsive">
				<div class="table-responsive" id="table-item">
                    <table class="table" id="tbody">
                        <thead>
                            <tr class="text-white">
                                <th class="text-start">#</th>
                                <th class="text-start">NAMA TOKO</th>
                                <th class="text-start">ID TOKO</th>
                                <th class="text-start">CHANNEL</th>
                                <th class="text-start">STATUS</th>
                                <th class="text-end"><i class="bi bi-gear-fill"></i></th>
                            </tr>
                        </thead>
                    </table>
                </div>
			</div>

			<!-- Pagination -->
			<div class="d-flex justify-content-end mt-3">
				<?= $pagination ?>
			</div>
		</div>
	</div>
</div>

<!-- Modal -->
<div class="modal fade bd-example-modal-xl" tabindex="-1" role="dialog" aria-labelledby="modalTitle" aria-hidden="true" id="modal-form">
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
		$("#title-form").html('Tambah Toko');
		$("#load-form").load("<?= base_url() ?>/marketplace-account/create");
	}

	function remove(id) {
		$("#load-form").html('Loading...');
		$("#modal-form").modal('show');
		$("#modal-dialog").removeClass("modal-xl modal-lg");
		$("#title-form").html('Hapus Toko');
		$("#load-form").load("<?= base_url() ?>/marketplace-account/remove?id=" + id);
	}

	function edit(id) {
		$("#load-form").html('Loading...');
		$("#modal-form").modal('show');
		$("#modal-dialog").addClass("modal-xl");
		$("#title-form").html('Edit Toko');
		$("#load-form").load("<?= base_url() ?>/marketplace-account/edit?id=" + id);
	}

	function updateStatus(id, status) {
		$(`input[name="status_${id}"]`).prop('disabled', true);
		$.ajax({
			url: "<?= base_url('marketplace-account/update_status') ?>",
			type: "POST",
			data: { id, status },
			dataType: "json",
			success: function(resp) {
				if (resp.success) {
					loadMoreData();
				} else {
					const prevStatus = "<?= $v['status'] ?? '' ?>";
					$(`#${prevStatus == 'Aktif' ? 'active' : 'inactive'}_${id}`).prop('checked', true);
					Swal.fire({ icon:'error', title:'Gagal', text: resp.message || 'Gagal mengubah status' });
				}
			},
			error: function(xhr, status, error) {
				const prevStatus = "<?= $v['status'] ?? '' ?>";
				$(`#${prevStatus == 'Aktif' ? 'active' : 'inactive'}_${id}`).prop('checked', true);
				Swal.fire({ icon:'error', title:'Error', text:'Terjadi kesalahan: ' + error });
			},
			complete: function() {
				$(`input[name="status_${id}"]`).prop('disabled', false);
			}
		});
	}

	function loadMoreData() {
        $.ajax({
            type: 'GET',
            url: "<?= base_url() ?>/marketplace-account/item<?= $param ?>",
            success: function(data) {
                $('#tbody').html('<thead>' + $('#tbody thead').html() + '</thead>'); // Keep the header
                $('#tbody').append(data);
                select3();
            },
            error: function(xhr, status, error) {}
        });
    }
	loadMoreData();

	// Init Bootstrap dropdowns
	$(document).ready(function() {
		var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
		dropdownElementList.map(function(el) { return new bootstrap.Dropdown(el); });
	});
</script>
