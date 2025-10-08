<div class="form-message"></div>
<form action="<?= base_url() ?>/expense/update" method="POST" id="form-modal">
	<input type="hidden" name="id" value="<?= $data['id'] ?>">
	<input type="hidden" name="id_customer" value="<?= $data['customer'] ?>">
	<div class="col-md-12">
		<label>
			<input type="checkbox" id="is_recurring" name="dt[is_recurring]" value="1" <?= ($data['is_recurring'] ?? 0) ? 'checked' : '' ?>>
			Pengeluaran Berulang
		</label>
	</div>
	<div class="col-md-12" id="recurring_type_container" style="display: none;">
		<label for="">Tipe Recurring</label>
		<select class="form-control" name="dt[recurring_type]" id="recurring_type">
			<option value="" <?= empty($data['recurring_type']) ? 'selected' : '' ?>>Pilih Tipe Recurring</option>
			<option value="Harian" <?= (isset($data['recurring_type']) && $data['recurring_type'] == 'Harian') ? 'selected' : '' ?>>Harian</option>
			<option value="Mingguan" <?= (isset($data['recurring_type']) && $data['recurring_type'] == 'Mingguan') ? 'selected' : '' ?>>Mingguan</option>
			<option value="Bulanan" <?= (isset($data['recurring_type']) && $data['recurring_type'] == 'Bulanan') ? 'selected' : '' ?>>Bulanan</option>
			<option value="Tahunan" <?= (isset($data['recurring_type']) && $data['recurring_type'] == 'Tahunan') ? 'selected' : '' ?>>Tahunan</option>
		</select>
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
	<div class="col-md-12">
		<label for="">Kategori</label>
		<select type="text" class="form-control" name="dt[category]">
			<option value=""></option>
			<?php
			foreach ($categories as $k2 => $v2) {
				$text = '';
				if ($data['category'] == $v2['category']) {
					$text = 'selected';
				}
			?>
				<option <?= $text ?> value="<?= $v2['category'] ?>"><?= $v2['category'] ?> </option>
			<?php } ?>
		</select>
	</div>
	<div class="col-md-12" id="recurring_detail_container" style="display: none;">
	</div>
	<div class="col-md-12">
		<label for="">Tanggal</label>
		<input type="date" class="form-control" name="dt[date]" value="<?= DATE('Y-m-d', strtotime($data['date'])) ?>">
	</div>
	<div class="col-md-12">
		<label for="">Brand</label>
		<select type="text" class="form-control" name="dt[brand]">
			<option value=""></option>
			<?php
			foreach ($brands as $k2 => $v2) {
				$text = '';
				if ($data['brand'] == $v2['code']) {
					$text = 'selected';
				}
			?>
				<option <?= $text ?> value="<?= $v2['code'] ?>"><?= $v2['code'] ?> </option>
			<?php } ?>
		</select>
	</div>
	<div class="col-md-12">
		<label for="">Judul</label>
		<input type="text" class="form-control" name="dt[title]" value="<?= $data['title'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">Keterangan</label>
		<input type="text" class="form-control" name="dt[desc]" value="<?= $data['desc'] ?>">
	</div>
	<div class="col-md-12">
		<label for="price">Harga</label>
		<input type="text" class="form-control" name="dt[price]" id="price"
			value="<?= isset($data['price']) ? number_format(abs($data['price']), 0, ',', '.') : '' ?>"
			oninput="formatPrice(this)"
			data-original-value="<?= isset($data['price']) ? abs($data['price']) : '' ?>">
	</div>
	<div class="col-md-12 mt-3">
		<button type="submit" class="btn btn-primary btn-send">Simpan Data</button>
	</div>
</form>
<script type="text/javascript">
	document.addEventListener('DOMContentLoaded', function() {
		const priceInput = document.getElementById('price');
		if (priceInput) {
			const originalValue = priceInput.getAttribute('data-original-value');
			if (originalValue) {
				priceInput.value = new Intl.NumberFormat('id-ID').format(originalValue);
			}
		}
	});

	function formatPrice(input) {
		let cursorPosition = input.selectionStart;
		let value = input.value.replace(/\./g, '');

		if (value === '') {
			input.value = '';
			input.setAttribute('data-original-value', '');
			return;
		}

		if (isNaN(value)) {
			const originalValue = input.getAttribute('data-original-value') || '';
			input.value = originalValue ? new Intl.NumberFormat('id-ID').format(originalValue) : '';
			return;
		}

		input.setAttribute('data-original-value', value);
		input.value = new Intl.NumberFormat('id-ID').format(value);

		let formattedValue = input.value;
		let dotCountBeforeCursor = formattedValue.substring(0, cursorPosition).split('.').length - 1;
		let newCursorPosition = cursorPosition + dotCountBeforeCursor;

		if (newCursorPosition > formattedValue.length) {
			newCursorPosition = formattedValue.length;
		}

		input.setSelectionRange(newCursorPosition, newCursorPosition);
	}
	$("#form-modal").submit(function() {
		var priceInput = document.getElementById('price');
		if (priceInput) {
			priceInput.value = priceInput.getAttribute('data-original-value') || '';
		}

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
<script>
	window.isEditMode = <?= isset($data['id']) ? 'true' : 'false' ?>;
	window.recurringTypeValue = "<?= $data['recurring_type'] ?? '' ?>";
	window.recurringDayValue = "<?= $data['recurring_day'] ?? '' ?>";
	window.recurringDateValue = "<?= $data['recurring_date'] ?? '' ?>";
</script>