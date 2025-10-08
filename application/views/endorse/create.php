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
</style>
<form action="<?= base_url() ?>/endorse/store" method="POST" id="form-modal">
	<input type="hidden" name="id" value="<?= $data['id'] ?>">
	<input type="hidden" name="dt[id_campaign]" value="<?= $detail['id'] ?>">
	<input type="hidden" name="id_campaign" value="<?= $detail['id'] ?>">
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
				$arr = array("Review", "Hold", "Acc", "Draft Content", "Posted Content", "Reject", "Problem");
				foreach ($arr as $v2) {
					$text = $data['status_endorse'] == $v2 ? 'selected' : '';
					echo "<option $text value='$v2'>$v2</option>";
				}
				?>
			</select>
		</div>

		<div class="col-md-6">
			<label for="">Produk</label>
			<select class="form-control select2" id="product-select" multiple>
				<?php foreach ($product_all as $v): ?>
					<?php
						$selected = in_array($v['id'], array_column($produk, 'id')) ? 'selected' : '';
					?>
					<option <?= $selected ?>
						value="<?= $v['id'] ?>"
						data-product_text="<?= htmlspecialchars($v['name']) ?>">
						<?= htmlspecialchars($v['name']) ?>
					</option>
				<?php endforeach; ?>
			</select>

			<input type="hidden" name="dt[product]" id="product-hidden">
			<input type="hidden" name="dt[product_text]" id="product-text">
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
			<input type="text" class="form-control" name="dt[link_mou]" value="<?= $data['link_mou'] ?>">
		</div>

		<div class="col-md-6">
			<label for="">Link Upload</label>
			<input type="text" class="form-control" name="dt[link_upload]" value="<?= $data['link_upload'] ?>">
		</div>

		<div class="col-md-6">
			<label for="">Kode Ads</label>
			<input type="text" class="form-control" name="dt[kode_ads]" value="<?= $data['kode_ads'] ?>">
		</div>

		<!-- <div class="col-md-6">
			<label for="">Status</label>
			<select type="text" class="form-control" name="dt[status]">
				<?php
				$arr = array("Aktif", "Tidak Aktif");
				foreach ($arr as $v2) {
					$text = $data['status'] == $v2 ? 'selected' : '';
					echo "<option $text value='$v2'>$v2</option>";
				}
				?>
			</select>
		</div> -->


		<div class="col-md-6">
			<label for="">Keterangan Payment</label>
			<input type="text" class="form-control" name="dt[keterangan_payment]" value="<?= $data['keterangan_payment'] ?>">
		</div>

		<div class="col-md-6">
			<label for="">Keterangan</label>
			<input type="text" class="form-control" name="dt[desc]" value="<?= $data['desc'] ?>">
		</div>


		<!-- <div class="col-md-6">
			<label for="">Keterangan Payment</label>
			<textarea class="form-control" name="dt[keterangan_payment]" rows="4" style="width: 100%; resize: vertical; min-height: 100px;"><?= $data['keterangan_payment'] ?></textarea>
		</div> -->

		<div class="col-md-12 mt-3 d-flex justify-content-end">
			<button type="submit" class="btn btn-primary btn-send">Simpan Data</button>
		</div>

	</div>
</form>
<script>
	$('.select2').select2();

	$('#product-select').on('change', function () {
		const selectedOptions = $(this).find(':selected');
		const values = selectedOptions.map(function () { return this.value; }).get().join(',');
		const texts = selectedOptions.map(function () { return $(this).text().trim(); }).get().join(',');

		$('#product-hidden').val(values);
		$('#product-text').val(texts);
	});

	$('#product-select').trigger('change');

	document.getElementById('total_cost_formatted').addEventListener('input', function(e) {
        let value = this.value.replace(/[^0-9]/g, '');
        
        document.getElementById('total_cost').value = value;
        
        if (value.length > 0) {
            value = parseInt(value).toLocaleString('id-ID');
        }
        
        this.value = value;
    });

	$("#form-modal").submit(function() {
		var form = $(this);
		var mydata = new FormData(this);
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
				var str = response;
				console.log(str);
				if (str.indexOf("success") != -1) {
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
				$(".form-message").hide().html(xhr).slideDown("fast");
			}
		});
		return false;
	});
</script>