<div class="form-message"></div>
<style>
	.select2-container .select2-selection--multiple {
		min-height: 45px;
		border-radius: 0.5rem !important;
		border: 1px solid #ced4da;
	}

	.select2-container--default .select2-selection--multiple .select2-selection__choice {
		padding: 6px;
		background-color: #f8f9fa;
		color: #212529;
		border-radius: 0.25rem;
		margin-right: 4px;
	}

	.select2-container .select2-search--inline .select2-search__field {
		padding-top: 6px !important;
		font-size: 14px;
	}

	.produk-row .badge {
		padding: 0.25rem 0.5rem;   /* kecilkan padding */
		line-height: 1.2;          /* biar sama dengan input */
		vertical-align: middle;    /* sejajarkan */
		font-size: 13px;
	}
	.produk-row input[type="number"] {
		height: calc(1.5em + 0.5rem + 2px); /* tinggi standar form-control-sm */
	}
	
	.btn-notif {
		background-color: #28a745;
		border-color: #28a745;
		color: white;
	}
	.btn-notif:hover {
		background-color: #218838;
		border-color: #1e7e34;
	}
	
	.loading-ellipsis {
		display: inline-block;
		position: relative;
		width: 80px;
		height: 20px;
	}
	
	.loading-ellipsis div {
		position: absolute;
		top: 8px;
		width: 8px;
		height: 8px;
		border-radius: 50%;
		background: #fff;
		animation-timing-function: cubic-bezier(0, 1, 1, 0);
	}
	
	.loading-ellipsis div:nth-child(1) {
		left: 8px;
		animation: loading-ellipsis1 0.6s infinite;
	}
	
	.loading-ellipsis div:nth-child(2) {
		left: 8px;
		animation: loading-ellipsis2 0.6s infinite;
	}
	
	.loading-ellipsis div:nth-child(3) {
		left: 32px;
		animation: loading-ellipsis2 0.6s infinite;
	}
	
	.loading-ellipsis div:nth-child(4) {
		left: 56px;
		animation: loading-ellipsis3 0.6s infinite;
	}
	
	@keyframes loading-ellipsis1 {
		0% { transform: scale(0); }
		100% { transform: scale(1); }
	}
	
	@keyframes loading-ellipsis3 {
		0% { transform: scale(1); }
		100% { transform: scale(0); }
	}
	
	@keyframes loading-ellipsis2 {
		0% { transform: translate(0, 0); }
		100% { transform: translate(24px, 0); }
	}
</style>
<form action="<?= base_url() ?>/endorse/update" method="POST" id="form-modal" enctype="multipart/form-data">
	<input type="hidden" name="id" value="<?= $data['id'] ?>">
	<input type="hidden" name="id_campaign" value="<?= $data['id_campaign'] ?>">
	<input type="hidden" name="status_endorse_existing" value="<?= $data['status_endorse'] ?>">
	<div class="row">
		<div class="col-md-6">
			<label for="">Status</label>
			<select type="text" class="form-control" name="dt[status]">
				<?php
				$arr = array();
				$arr[] = "Aktif";
				$arr[] = "Tidak Aktif";
				foreach ($arr as $k2 => $v2) {
					$text = '';
					if ($data['status'] == $v2) {
						$text = 'selected';
					}
				?>
					<option <?= $text ?> value="<?= $v2 ?>"><?= $v2 ?></option>
				<?php } ?>
			</select>
		</div>

		<div class="col-md-6">
			<label for="">Status Endorse</label>
			<select type="text" class="form-control" name="dt[status_endorse]">
				<?php
				$arr = array("Review", "ACC", "Pengiriman Produk", "Brief Content", "Draft Content", "Posted Content", "Rejected");
				foreach ($arr as $v2) {
					$text = $data['status_endorse'] == $v2 ? 'selected' : '';
					echo "<option $text value='$v2'>$v2</option>";
				}
				?>
			</select>
		</div>

		<div class="col-md-6">
			<label for="">Produk</label>
			<?php
			$selected_ids = explode(',', $data['product'] ?? '');
			$selected_texts = explode(',', $data['product_text'] ?? '');
			?>
			<select class="form-control select2" id="product-select" multiple>
				<?php foreach ($product_all as $v): ?>
					<?php
						$selected = in_array($v['id'], $selected_ids) ? 'selected' : '';
					?>
					<option <?= $selected ?>
						value="<?= $v['id'] ?>"
						data-product_text="<?= htmlspecialchars($v['name']) ?>">
						<?= htmlspecialchars($v['name']) ?>
					</option>
				<?php endforeach; ?>
			</select>

			<input type="hidden" name="dt[product]" id="product-hidden" value="<?= htmlspecialchars(implode(',', $selected_ids)) ?>">
			<input type="hidden" name="dt[product_text]" id="product-text" value="<?= htmlspecialchars(implode(',', $selected_texts)) ?>">

		</div>

		<div class="col-md-6">
			<label for="">Platform</label>
			<select type="text" class="form-control" name="dt[platform]">
				<?php
				$arr = array("Tiktok", "Instagram", "Twitter", "Youtube");
				foreach ($arr as $v2) {
					$text = $data['platform'] == $v2 ? 'selected' : '';
					echo "<option $text value='$v2'>$v2</option>";
				}
				?>
			</select>
		</div>

		<div class="col-md-6">
			<label for="influencerSearch">Nama Creator</label>
			<div class="input-with-button">
				<input type="hidden" name="existing_influencer" value="<?= $data['influencer'] ?>">
				<select class="form-control select2" name="dt[influencer]" id="influencerSearch">
					<?php if(isset($data['influencer']) && !empty($data['influencer'])): ?>
						<option value="<?= $data['influencer'] ?>" selected><?= $data['nama_creator'] ?></option>
					<?php endif; ?>
				</select>
				<script>
					$(function() {
						$('#influencerSearch').select2({
							minimumInputLength: 1,
							allowClear: false,
							placeholder: 'Cari influencer...',
							minimumResultsForSearch: 1,
							ajax: {
								dataType: 'json',
								url: '<?= base_url() ?>/ajax/get-influencer-list', 
								delay: 100,
								data: function(params) {
									return {
										search: params.term
									};
								},
								processResults: function(data) {
									return {
										results: data
									};
								}
							},
							language: {
								inputTooShort: function() {
									return "Masukkan 1 karakter atau lebih";
								}
							}
						});
					});
				</script>
			</div>
		</div>

		<div class="col-md-6">
			<label for="">PIC</label>
			<select type="text" class="form-control select2" name="dt[pic]">
				<?php
				$selectedPic = !empty($data['pic']) ? $data['pic'] : $user['full_name'];

				foreach ($pic as $v2) {
					$text = $selectedPic == $v2['full_name'] ? 'selected' : '';
					echo "<option $text value='{$v2['full_name']}'>{$v2['full_name']}</option>";
				}
				?>
			</select>
		</div>

		<div class="col-md-6">
			<label for="">Tanggal Rencana Upload</label>
			<input type="date" class="form-control" name="dt[rencana_at]" value="<?= $data['rencana_at'] ?>">
		</div>

		<div class="col-md-6">
			<label for="">Total Cost</label>
			<input type="text" class="form-control" id="total_cost_formatted" value="<?= $data['total_cost'] ?>">
			<input type="hidden" name="dt[total_cost]" id="total_cost" value="<?= $data['total_cost'] ?>">
		</div>

		<div class="col-md-6">
			<label for="">Link Brief</label>
			<input type="text" class="form-control" name="dt[link_brief]" value="<?= $data['link_brief'] ?>">
		</div>

		<div class="col-md-6">
			<label for="">Link MOU</label>
			<div class="input-group">
				<input type="text" class="form-control" name="dt[link_mou]" id="link_mou" value="<?= $data['link_mou'] ?>">
				<button class="btn btn-outline-primary" type="button" id="show-qty-btn">+ Notif Tele</button>
			</div>
		</div>

		<div class="col-md-12 mt-2" id="produk-qty-section" style="display:none;">
			<div class="card shadow-sm" style="background-color: #f8f9fa; border: 1px solid #ddd;">
				<div class="card-body">
					<h5 class="card-title">Detail Pengiriman</h5>
					
					<div class="row mb-3">
						<div class="col-md-6">
							<label for="jenis_pengiriman">Jenis Pengiriman</label>
							<select class="form-control" name="jenis_pengiriman" id="jenis_pengiriman">
								<option value="Endorse" <?= ($data['jenis_pengiriman'] ?? '') == 'Endorse' ? 'selected' : '' ?>>Endorse</option>
								<option value="Affiliate" <?= ($data['jenis_pengiriman'] ?? '') == 'Affiliate' ? 'selected' : '' ?>>Affiliate</option>
							</select>
						</div>
						<div class="col-md-6">
							<label for="detail_pic">Detail PIC</label>
							<input type="text" class="form-control" name="detail_pic" id="detail_pic" 
								value="<?= $data['detail_pic'] ?? '' ?>" placeholder="KOL/PIC">
						</div>
					</div>
					
					<label>Detail Produk</label>
					<div id="product-qty-wrapper"></div>
					
					<!-- Tombol Kirim Notifikasi Terpisah -->
					<div class="mt-3 d-flex justify-content-end">
						<button type="button" class="btn btn-notif me-2" id="btn-send-notif">
							Kirim Notifikasi Pengiriman
						</button>
					</div>
				</div>
			</div>
		</div>

		<div class="col-md-6">
			<label for="">Link Upload</label>
			<input type="text" class="form-control" name="dt[link_upload]" value="<?= $data['link_upload'] ?>">
		</div>

		<div class="col-md-6">
			<label for="">Kode Ads</label>
			<input type="text" class="form-control" name="dt[kode_ads]" value="<?= $data['kode_ads'] ?>">
		</div>

		<div class="col-md-6">
			<label for="">Category KOL</label>
			<select class="form-control" name="dt[category_kol]">
				<option value="">Pilih Category KOL</option>
				<?php
				$current_category = isset($data['category_kol']) ? trim($data['category_kol']) : '';
				foreach ($niche as $v2) {
					$niche_value = trim($v2['niche']);
					$selected = ($current_category === $niche_value) ? 'selected' : '';
					echo "<option $selected value=\"{$niche_value}\">{$niche_value}</option>";
				}
				?>
			</select>
		</div>

		<div class="col-md-6">
			<label for="">Upload Media (Image/Video)</label>
			<input type="file" class="form-control" name="media_attachment" accept="image/*,video/*">
			<small class="form-text text-muted">Format: JPG, PNG, MP4, MOV, AVI (Max: 10MB)</small>
			<?php if (!empty($data['media_attachment'])): ?>
				<div class="mt-2">
					<small>Current file: <strong><?= $data['media_attachment'] ?></strong></small>
					<br>
					<?php
					$file_ext = strtolower(pathinfo($data['media_attachment'], PATHINFO_EXTENSION));
					if (in_array($file_ext, ['jpg', 'jpeg', 'png'])):
					?>
						<img src="<?= base_url('assets/img/endorse/' . $data['media_attachment']) ?>" alt="Media" style="max-width: 200px; margin-top: 5px;" class="img-thumbnail">
					<?php elseif (in_array($file_ext, ['mp4', 'mov', 'avi'])): ?>
						<video controls style="max-width: 200px; margin-top: 5px;">
							<source src="<?= base_url('assets/img/endorse/' . $data['media_attachment']) ?>" type="video/<?= $file_ext ?>">
						</video>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="col-md-6">
			<label for="">Keterangan Payment</label>
			<input type="text" class="form-control" name="dt[keterangan_payment]" value="<?= $data['keterangan_payment'] ?>">
		</div>

		<div class="col-md-6">
			<label for="">Keterangan</label>
			<input type="text" class="form-control" name="dt[desc]" value="<?= $data['desc'] ?>">
		</div>

		<div class="col-md-12 mt-3 d-flex justify-content-end">
			<button type="submit" class="btn btn-primary btn-send">Simpan Data</button>
		</div>
	</div>
	<div class="col-md-12 mb-3 mt-3">
		<p class="fw-600 mb-1">Status Logs</p>
		<?php
		foreach (json_decode($data['logs'], true) as $k2 => $v2) {
		?>
			<p class="mb-0">#<?= $k2 + 1 ?>. <?= $v2['created_text'] ?> mengubah data menjadi <b><?= $v2['status'] || $v2['status_pengiriman'] || $v2['status_mou'] ?></b> pada <?= $v2['created_at'] ?>.</p>
		<?php }
		if (empty(json_decode($data['logs'], true))) { ?>
			<i>Belum tersedia.</i>
		<?php } ?>
	</div>
</form>

<script type="text/javascript">
	$('.select2').select2();

	$('#product-select').on('change', function () {
		const selectedOptions = $(this).find(':selected');
		const values = selectedOptions.map(function () { return this.value; }).get().join(',');
		const texts = selectedOptions.map(function () { return $(this).text().trim(); }).get().join(',');

		$('#product-hidden').val(values);
		$('#product-text').val(texts);

		if ($('#produk-qty-section').is(':visible')) {
			renderQtyInputs();
		}
	});

	$('#product-select').trigger('change');

	function getKeyFromName(pname) {
		return pname.trim().toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_-]/g, '');
	}

	function renderQtyInputs() {
		const wrapper = $('#product-qty-wrapper');
		const selectedNames = $('#product-select').find(':selected').map(function () {
			return $(this).text().trim();
		}).get();

		// Clear existing inputs
		wrapper.empty();

		selectedNames.forEach((pname) => {
			const key = getKeyFromName(pname);
			const row = `
				<div class="d-flex align-items-center mb-2 produk-row" data-key="${key}">
					<div class="flex-grow-1 d-flex align-items-center">
						<span class="badge bg-primary text-dark fs-13 me-2">${pname}</span>
						<input type="hidden" name="produk[${pname}][nama]" value="${pname}">
					</div>
					<div style="width:100px;">
						<input type="number" class="form-control form-control-sm"
							name="produk[${pname}][qty]" min="0" placeholder="Qty" value="0">
					</div>
				</div>`;
			wrapper.append(row);
		});
	}

	$('#show-qty-btn').on('click', function() {
		$('#produk-qty-section').slideToggle();
		renderQtyInputs();
	});

	// Tombol Kirim Notifikasi dengan AJAX
	$('#btn-send-notif').on('click', function() {
		// Kumpulkan data produk
		const produkData = {};
		let hasQty = false;
		
		$('#product-qty-wrapper .produk-row').each(function() {
			const nama = $(this).find('input[type="hidden"]').val();
			const qty = parseInt($(this).find('input[type="number"]').val()) || 0;
			
			if (nama && qty > 0) {
				produkData[nama] = {
					nama: nama,
					qty: qty
				};
				hasQty = true;
			}
		});

		if (!hasQty) {
			alert('Harap isi quantity produk yang akan dikirim!');
			return;
		}

		// Prepare data untuk AJAX
		const requestData = {
			id: '<?= $data['id'] ?>',
			id_campaign: '<?= $data['id_campaign'] ?>',
			produk_data: JSON.stringify(produkData),
			jenis_pengiriman: $('#jenis_pengiriman').val(),
			detail_pic: $('#detail_pic').val(),
		};

		// Kirim dengan AJAX
		$.ajax({
			type: "POST",
			url: "<?= base_url() ?>/endorse/send_telegram",
			data: requestData,
			beforeSend: function() {
				$('#btn-send-notif').addClass("disabled").html('<div class="loading-ellipsis"><div></div><div></div><div></div><div></div></div>').attr('disabled', true);
				$('.form-message').slideUp().html("");
			},
			success: function(response) {
				// Reset tombol
				$('#btn-send-notif').removeClass("disabled").html('Kirim Notifikasi Pengiriman').attr('disabled', false);
				
				try {
					const result = typeof response === 'string' ? JSON.parse(response) : response;
					
					if (result.success) {
						$('.form-message').hide().html('<div class="alert alert-success">' + result.message + '</div>').slideDown("fast");
						
						// TUTUP SECTION SETELAH SUCCESS
						$('#produk-qty-section').slideUp();
						
						// Auto hide success message after 3 seconds
						setTimeout(function() {
							$('.form-message').slideUp();
						}, 3000);
					} else {
						$('.form-message').hide().html('<div class="alert alert-danger">' + (result.message || 'Terjadi kesalahan!') + '</div>').slideDown("fast");
					}
				} catch (e) {
					// Jika response bukan JSON, tampilkan langsung
					if (response.indexOf("success") !== -1) {
						$('.form-message').hide().html('<div class="alert alert-success">Notifikasi berhasil dikirim!</div>').slideDown("fast");
						
						// TUTUP SECTION SETELAH SUCCESS
						$('#produk-qty-section').slideUp();
						
						setTimeout(function() {
							$('.form-message').slideUp();
						}, 3000);
					} else {
						$('.form-message').hide().html('<div class="alert alert-danger">' + response + '</div>').slideDown("fast");
					}
				}
			},
			error: function(xhr, textStatus, errorThrown) {
				$('#btn-send-notif').removeClass("disabled").html('Kirim Notifikasi Pengiriman').attr('disabled', false);
				$('.form-message').hide().html('<div class="alert alert-danger">Terjadi kesalahan: ' + (xhr.responseText || 'Tidak dapat terhubung ke server') + '</div>').slideDown("fast");
			}
		});
	});

	formatInitialTotalCost();

	document.getElementById('total_cost_formatted').addEventListener('input', function(e) {
		let value = this.value.replace(/[^0-9]/g, '');
		document.getElementById('total_cost').value = value;
		if (value.length > 0) {
			value = parseInt(value).toLocaleString('id-ID');
		}
		this.value = value;
	});

	function formatInitialTotalCost() {
		const input = document.getElementById('total_cost_formatted');
		if (!input) return;
		let rawValue = input.value.replace(/[^0-9]/g, '');
		if (rawValue.length > 0) {
			input.value = parseInt(rawValue).toLocaleString('id-ID');
		}
	}

	$("#form-modal").submit(function() {
		var form = $(this);
		var mydata = new FormData(this);

		for (var pair of mydata.entries()) {
			console.log(pair[0]+ ': ' + pair[1]);
		}

		$.ajax({
			type: "POST",
			url: form.attr("action"),
			data: mydata,
			cache: false,
			contentType: false,
			processData: false,
			beforeSend: function() {
				$(".btn-send").addClass("disabled").html('<div class="loading-ellipsis"><div></div><div></div><div></div><div></div></div>').attr('disabled', true);
				form.find(".form-message").slideUp().html("");
			},
			success: function(response, textStatus, xhr) {
				if (response.indexOf("success") != -1) {
					$(".form-message").hide().html(response).slideDown("fast");
					setTimeout(function() {
						window.location.href = "";
						$(".btn-send").removeClass("disabled").html('Simpan Data').attr('disabled', false);
					}, 2500);
				} else {
					$(".form-message").hide().html(response).slideDown("fast");
					$(".btn-send").removeClass("disabled").html('Simpan Data').attr('disabled', false);
				}
			},
			error: function(xhr, textStatus, errorThrown) {
				$(".btn-send").removeClass("disabled").html('Simpan Data').attr('disabled', false);
				$(".form-message").hide().html(xhr.responseText).slideDown("fast");
			}
		});
		return false;
	});
</script>