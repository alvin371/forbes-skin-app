<div class="form-message"></div>
<form action="<?= base_url() ?>/user/update" method="POST" id="form-modal" enctype="multipart/form-data">
	<input type="hidden" name="id" value="<?= $data['id'] ?>">

	<div class="row">
		<div class="col-md-6">
			<label for="">Nama Lengkap</label>
			<input type="text" class="form-control" name="dt[full_name]" value="<?= $data['full_name'] ?>">
		</div>
		<div class="col-md-6">
			<label for="">Username</label>
			<input type="text" class="form-control" name="dt[username]" value="<?= $data['username'] ?>">
		</div>
	</div>

	<div class="row">
		<div class="col-md-6">
			<label for="">Email</label>
			<input type="text" class="form-control" name="dt[email]" value="<?= $data['email'] ?>">
		</div>
		<div class="col-md-6">
			<label for="">Password</label> <i>* Kosongkan jika tidak diubah</i>
			<input type="text" class="form-control" name="dt[password]" value="">
		</div>
	</div>

	<div class="row">
		<div class="col-md-6">
			<label for="">Role</label>
			<select class="form-control" name="dt[role]">
				<?php foreach ($role as $k2 => $v2) {
					$text = ($data['role'] == $v2['id']) ? 'selected' : '';
				?>
					<option <?= $text ?> value="<?= $v2['id'] ?>"><?= $v2['role'] ?></option>
				<?php } ?>
			</select>
		</div>
		<div class="col-md-6">
			<label for="">Tanggal Lahir</label>
			<input type="date" class="form-control" name="dt[birth_date]" value="<?= $data['birth_date'] ?>">
		</div>
	</div>

	<div class="row">
		<div class="col-md-6">
			<label for="">Keterangan</label>
			<input type="text" class="form-control" name="dt[desc]" value="<?= $data['desc'] ?>">
		</div>
		<div class="col-md-6">
			<label for="">Status</label>
			<select class="form-control" name="dt[status]">
				<?php
				$arr = ["Aktif", "Tidak Aktif"];
				foreach ($arr as $v2) {
					$text = ($data['status'] == $v2) ? 'selected' : '';
				?>
					<option <?= $text ?> value="<?= $v2 ?>"><?= $v2 ?></option>
				<?php } ?>
			</select>
		</div>
	</div>

	<div class="row">
		<div class="col-md-12">
			<label for="">Gambar</label><br>
			<?php if ($data['img']) { ?>
				<a href="<?= base_url() ?>/assets/img/user/<?= $data['img'] . '?token=' . DATE("Ymdhis", strtotime($data['updated_at'])) ?>" target="_blank"><i>Buka Gambar</i></a>
			<?php } ?>
			<input type="file" class="form-control" name="file" accept="image/png, image/jpeg, image/jpg">
		</div>
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
				console.log(response);
				if (response.indexOf("success") != -1) {
					$(".form-message").hide().html(response).slideDown("fast");
					setTimeout(function() {
						window.location.href = "";
					}, 2500);
				} else {
					$(".form-message").hide().html(response).slideDown("fast");
				}
				$(".btn-send").removeClass("disabled").html('Simpan Data').attr('disabled', false);
			},
			error: function(xhr, textStatus, errorThrown) {
				$(".btn-send").removeClass("disabled").html('Simpan Data').attr('disabled', false);
				$(".form-message").hide().html(xhr).slideDown("fast");
			}
		});
		return false;
	});
</script>
