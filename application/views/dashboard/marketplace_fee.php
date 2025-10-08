<style>
	tr.clickable-row { cursor: pointer; }
	#laporanTable {
		font-size: 0.9rem;
	}
	#laporanTable th,
	#laporanTable td {
		padding: 0.5rem 0.75rem;
	}
</style>

<div class="container-fluid">
	<div class="d-flex justify-content-between align-items-center mb-4">
		<h2 class="text-primary fw-600">Laporan Marketplace Fee</h2>
	</div>

	<form action="<?= $url ?>" method="GET" class="mb-4">
		<div class="row g-2">
			<div class="col-md-3">
				<select class="form-control select2" name="brand" id="brand">
					<option value="">Brand</option>
					<?php foreach ($brands as $val) :
						$selected = ($selected_brand == $val["code"]) ? "selected" : "";
					?>
						<option <?= $selected ?> value="<?= $val["code"] ?>"><?= $val["code"] ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="col-md-4">
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
						success: function (response) {
							$("#tanggal").after(response.html);
						},
						error: function (xhr, status, error) {
							console.error("Error loading filter:", error);
						}
					});
				}
			</script>
			<div class="col-md-2">
				<button class="btn btn-primary w-100" type="submit">
					<i class="bi bi-search fs-16"></i> Cari Data
				</button>
			</div>
		</div>
	</form>

	<?php if (!empty($marketplace_fee)) :
		$totalOmset = 0;
		$totalFee   = 0;
	?>
		<div class="table-responsive">
			<table id="laporanTable" class="table table-bordered table-striped table-hover align-middle">
				<thead class="table-light">
					<tr>
						<th>Marketplace</th>
						<th>Omset Kotor</th>
						<th>Marketplace Fee</th>
						<th>Persentase Fee</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($marketplace_fee as $v) :
						$marketplace     = is_array($v) ? ($v['marketplace'] ?? '') : ($v->marketplace ?? '');
						$shop_fullname   = is_array($v) ? ($v['shop_fullname'] ?? '') : ($v->shop_fullname ?? '');
						$shop_id         = is_array($v) ? ($v['shop_id'] ?? '') : ($v->shop_id ?? '');
						$omset_kotor     = (float)(is_array($v) ? ($v['omset_kotor'] ?? 0) : ($v->omset_kotor ?? 0));
						$marketplaceFee  = (float)(is_array($v) ? ($v['marketplace_fee'] ?? 0) : ($v->marketplace_fee ?? 0));
						$percentage      = $omset_kotor > 0 ? ($marketplaceFee / $omset_kotor) * 100 : 0;

						$totalOmset += $omset_kotor;
						$totalFee   += $marketplaceFee;

						$mpRow    = $this->mymodel->selectWithQuery("
							SELECT img FROM marketplace
							WHERE name = " . $this->db->escape($marketplace) . " LIMIT 1
						");
						$iconPath = base_url('assets/img/marketplace/') . (!empty($mpRow) && !empty($mpRow[0]['img']) ? $mpRow[0]['img'] : 'default.png');

						$txParams = [
							'keyword_category' => 'Order ID',
							'view'             => 'table',
							'shop_id'          => $shop_id,
							'brand'            => $selected_brand ?? '',
							'start_date'       => $start_date,
							'until_date'       => $until_date,
							'order_status'     => 'ACTIVE'
						];
						$txParams = array_filter($txParams, static fn($x) => $x !== '' && $x !== null);
						$txUrl = base_url('transaction') . '?' . http_build_query($txParams);
					?>
						<tr class="clickable-row" data-href="<?= htmlspecialchars($txUrl, ENT_QUOTES) ?>">
							<td>
								<div class="d-flex align-items-center">
									<img src="<?= $iconPath ?>"
										alt="icon <?= htmlspecialchars($marketplace, ENT_QUOTES) ?>"
										class="me-2 rounded"
										style="width:24px;height:24px;object-fit:cover;">
									<?= htmlspecialchars($shop_fullname, ENT_QUOTES) ?>
								</div>
							</td>
							<td class="text-success fw-bold" data-order="<?= $omset_kotor ?>">
								Rp <?= number_format($omset_kotor, 0, ',', '.') ?>
							</td>
							<td class="text-danger fw-bold" data-order="<?= $marketplaceFee ?>">
								Rp <?= number_format($marketplaceFee, 0, ',', '.') ?>
							</td>
							<td data-order="<?= $percentage ?>">
								<span class="badge bg-warning text-dark"><?= number_format($percentage, 2) ?>%</span>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
				<tfoot class="bg-light">
					<tr>
						<th>Total</th>
						<th>Rp <?= number_format($totalOmset, 0, ',', '.') ?></th>
						<th>Rp <?= number_format($totalFee, 0, ',', '.') ?></th>
						<th><?= number_format($totalOmset > 0 ? ($totalFee / $totalOmset * 100) : 0, 2) ?>%</th>
					</tr>
				</tfoot>
			</table>
		</div>
	<?php else: ?>
		<div class="alert alert-info text-center">
			<i class="bi bi-inbox"></i> Tidak ada data untuk rentang tanggal/brand yang dipilih.
		</div>
	<?php endif; ?>
</div>

<script>
	$(document).ready(function () {
		$('.select2').select2({ theme: 'default', width: '100%' });

		$('form').on('submit', function () {
			const btn = $(this).find('button[type="submit"]');
			btn.html('<i class="bi bi-hourglass-split me-2"></i> Memuat...');
			btn.prop('disabled', true);
		});

		$('#laporanTable').DataTable({
			paging: false,
			lengthChange: false,
			ordering: true,
			searching: true,
			order: [[2, 'desc']],
			dom: '<"d-flex justify-content-between align-items-center mb-2"i f>rt'
		});
	});

	$('#laporanTable tbody').on('click', 'tr.clickable-row', function (e) {
		if ($(e.target).closest('a, button, .btn, input, label').length) return;
		const href = $(this).data('href');
		if (href) {
			window.open(href, '_blank');
		}
	});
</script>
