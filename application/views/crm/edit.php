<div class="form-message"></div>
<form action="<?= base_url() ?>/crm/update" method="POST" id="form-modal" enctype="multipart/form-data">
	<input type="hidden" name="id" value="<?= $data['id'] ?>">
	<div class="col-md-12">
		<label for="">CB/CL</label>
		<select type="text" class="form-control" name="dt[cb_cl]">
			<?php
			$arr = array();
			$arr[] = "CB";
			$arr[] = "CL";
			foreach ($arr as $k2 => $v2) {
				$text = '';
				if ($data['cb_cl'] == $v2) {
					$text = 'selected';
				}
			?>
				<option <?= $text ?> value="<?= $v2 ?>"><?= $v2 ?></option>
			<?php } ?>
		</select>
	</div>
	<div class="col-md-12">
		<label for="">Nama Lengkap</label>
		<input type="text" class="form-control" name="dt[full_name]" value="<?= $data['full_name'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">Username</label>
		<input type="text" class="form-control" name="dt[username]" value="<?= $data['user_name'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">No HP</label>
		<input type="text" class="form-control" name="dt[phone]" value="<?= $data['phone'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">Tanggal Lahir</label>
		<input type="date" class="form-control" name="dt[birth_date]" value="<?= $data['birth_date'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">Tipe</label>
		<select type="text" class="form-control" name="dt[marketplace]">
			<?php
			$arr = array();
			foreach ($marketplace as $k2 => $v2) {
				$text = '';
				if ($data['marketplace'] == $v2['name']) {
					$text = 'selected';
				}
			?>
				<option <?= $text ?> value="<?= $v2['name'] ?>"><?= $v2['name'] ?></option>
			<?php } ?>
		</select>
	</div>
	<div class="col-md-12">
		<label for="">CS</label>
		<select type="text" class="form-control" name="dt[cs]">
			<?php
			$arr = array();
			foreach ($cs as $k2 => $v2) {
				$text = '';
				if ($data['cs'] == $v2['full_name']) {
					$text = 'selected';
				}
			?>
				<option <?= $text ?> value="<?= $v2['full_name'] ?>"><?= $v2['full_name'] ?></option>
			<?php } ?>
		</select>
	</div>
	<div class="col-md-12">
		<label for="">Provinsi</label>
		<input type="text" class="form-control" name="dt[province_text]" value="<?= $data['province_text'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">Kota</label>
		<input type="text" class="form-control" name="dt[city_text]" value="<?= $data['city_text'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">Kecamatan</label>
		<input type="text" class="form-control" name="dt[subdistrict_text]" value="<?= $data['subdistrict_text'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">Alamat Lengkap</label>
		<input type="text" class="form-control" name="dt[address]" value="<?= $data['address'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">Keterangan</label>
		<input type="text" class="form-control" name="dt[desc]" value="<?= $data['desc'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">Gift</label>
	</div>
	<!-- <div class="col-md-12">
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
	</div> -->
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
	$(document).ready(function() {
		$('.select2').select2();
	});
</script>