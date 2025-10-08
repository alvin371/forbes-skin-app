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

	.card {
		border-radius: 2px;
		border: 1px solid #f0f0f0;
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.09);
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
		transition: all .3s cubic-bezier(.645, .045, .355, 1);
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
		box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
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
				<h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Channel Management</h5>
				<div class="d-flex gap-2">
					<a href="#!" onclick="create()" class="btn btn-primary">
						<i class="bi bi-plus me-1"></i> Tambah Data
					</a>
				</div>
			</div>
		</div>

		<div class="card-body">
			<form action="" method="GET" class="search-form">
				<div class="row g-2">
					<div class="col-md-6">
						<div class="input-group">
							<div class="dropdown" style="margin-right: 10px;">
								<button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"
									style="border: 1px solid #d9d9d9; border-radius: 2px; color: rgba(0,0,0,0.65); background-color: #fff; height: 32px; padding: 4px 11px;">
									<span style="margin-right: 8px;"><?= $keyword_category ?></span>
								</button>
								<ul class="dropdown-menu">
									<?php
										$arr = array('Nama Channel', 'Keterangan');
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
							<input type="text" name="keyword" class="form-control" placeholder="Search..." value="<?= $_GET['keyword'] ?? '' ?>" style="margin-right: 10px;">
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

			<div class="table-responsive" id="table-item">
				<table class="table table-hover" id="tbody">
					<thead>
						<tr>
							<th class="text-start" style="width: 60px;">#</th>
							<th class="text-start">CHANNEL</th>
							<th class="text-start">KONFIGURASI</th>
							<th class="text-start">KETERANGAN</th>
							<th class="text-end" style="width: 120px;"><i class="bi bi-gear-fill"></i></th>
						</tr>
					</thead>
					<!-- tbody akan di-append via AJAX -->
				</table>
			</div>

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
		$("#title-form").html('Create Data');
		$("#load-form").load("<?= base_url() ?>/marketplace/create");
	}

	function remove(id) {
		$("#load-form").html('Loading...');
		$("#modal-form").modal('show');
		$("#modal-dialog").removeClass("modal-xl modal-lg");
		$("#title-form").html('Hapus Data');
		$("#load-form").load("<?= base_url() ?>/marketplace/remove?id=" + id);
	}

	function edit(id) {
		$("#load-form").html('Loading...');
		$("#modal-form").modal('show');
		$("#modal-dialog").addClass("modal-xl");
		$("#title-form").html('Edit Data');
		$("#load-form").load("<?= base_url() ?>/marketplace/edit?id=" + id);
	}
</script>

<script>
	function loadMoreData() {
		$.ajax({
			type: 'GET',
			url: "<?= base_url() ?>/marketplace/item<?= $param ?>",
			beforeSend: function() {
				// bisa tambahkan skeleton/loading kalau perlu
			},
			success: function (data) {
				$('#tbody').append(data);
			},
			error: function (xhr, status, error) {
				console.error('Error load data:', error);
			}
		});
	}
	loadMoreData();

	// Init Bootstrap dropdowns biar kliknya smooth
	$(document).ready(function() {
		var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
		dropdownElementList.map(function(el) { return new bootstrap.Dropdown(el); });
	});
</script>
