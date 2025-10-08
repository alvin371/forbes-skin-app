<div class="form-message"></div>
<form action="<?= base_url() ?>/stock/update" method="POST" id="form-modal" enctype="multipart/form-data">
	<input type="hidden" name="id" value="<?= $data['id'] ?>">
	<div class="col-md-12">
		<label for="">Date</label>
		<input type="datetime-local" class="form-control" name="dt[date]" value="<?= DATE("Y-m-d H:i", strtotime($data['date'])) ?>">
	</div>
	<div class="col-md-12">
		<label for="">Tipe</label>
		<select type="text" class="form-control" name="dt[type]">
			<?php
			$arr = array();
			$arr[] = "In";
			$arr[] = "Out";
			foreach ($arr as $k2 => $v2) {
				$text = '';
				if ($data['type'] == $v2) {
					$text = 'selected';
				}
			?>
				<option <?= $text ?> value="<?= $v2 ?>"><?= $v2 ?></option>
			<?php } ?>
		</select>
	</div>
	<div class="col-md-12">
		<label for="">Produk</label>
		<select type="text" class="form-control select2" name="dt[product]">
			<?php
			foreach ($product as $k2 => $v2) {
				$text = '';
				if ($data['product'] == $v2['id']) {
					$text = 'selected';
				}
			?>
				<option <?= $text ?> value="<?= $v2['id'] ?>"><?= $v2['name'] ?> | <?= $v2['sku'] ?> | <?= $v2['brand'] ?></option>

			<?php } ?>
		</select>
	</div>
	<style>
		.select2-container--open .select2-dropdown {
			z-index: 10000;
		}
	</style>
	<script>
		$('.select2').select2();
	</script>
	<div class="col-md-12">
		<label for="">Qty</label>
		<input type="text" class="form-control" name="dt[qty]" value="<?= abs($data['qty']) ?>">
	</div>
	<div class="col-md-12">
		<label for="">Ket</label>
		<input type="text" class="form-control" name="dt[desc]" value="<?= $data['desc'] ?>">
	</div>
	<div class="col-md-12">
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
	<div class="col-md-12 mt-3">
		<button type="submit" class="btn btn-primary btn-send">Simpan Data</button>
	</div>
</form>
<script type="text/javascript">
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