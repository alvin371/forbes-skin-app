<div class="form-message"></div>
<form action="<?= base_url() ?>/testimoni/update" method="POST" id="form-modal" enctype="multipart/form-data">
	<input type="hidden" name="id" value="<?= $data['id'] ?>">
	<input type="hidden" name="id_customer" value="<?= $data['customer'] ?>">
	<div class="col-md-12">
		<label for="">Tanggal</label>
		<input type="date" class="form-control" name="dt[date]" value="<?= $data['date'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">Keterangan</label>
		<textarea style="min-height:100px" type="text" class="form-control" name="dt[desc]"><?= ($data['desc']) ?></textarea>
	</div>
	<div class="col-md-12">
		<?php if ($data['brand'] == "MG") { ?>
			<label for="">Gambar</label>
			<?php if ($data['img_before']) { ?>
				<a href="<?= base_url() ?>/assets/img/testimoni/<?= $data['img_before'] . '?token=' . DATE("Ymdhis", strtotime($data['updated_at'])) ?>" target="_blank"><i>Buka Gambar</i></a>
			<?php } ?>
			<input type="file" class="form-control" name="file_before" accept="image/png, image/jpeg, image/jpg">
	</div>
<?php } else { ?>
	<div class="col-md-12">
		<label for="">Gambar Sebelum</label>
		<?php if ($data['img_before']) { ?>
			<a href="<?= base_url() ?>/assets/img/testimoni/<?= $data['img_before'] . '?token=' . DATE("Ymdhis", strtotime($data['updated_at'])) ?>" target="_blank"><i>Buka Gambar</i></a>
		<?php } ?>
		<input type="file" class="form-control" name="file_before" accept="image/png, image/jpeg, image/jpg">
	</div>
	<div class="col-md-12">
		<label for="">Gambar Sesudah</label>
		<?php if ($data['img_after']) { ?>
			<a href="<?= base_url() ?>/assets/img/testimoni/<?= $data['img_after'] . '?token=' . DATE("Ymdhis", strtotime($data['updated_at'])) ?>" target="_blank"><i>Buka Gambar</i></a>
		<?php } ?>
		<input type="file" class="form-control" name="file_after" accept="image/png, image/jpeg, image/jpg">
	</div>
<?php } ?>
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