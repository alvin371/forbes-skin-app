<div class="form-message"></div>
<form action="<?= base_url() ?>/transaction/set-cs-process" method="POST" id="form-modal">
	<input type="hidden" name="id" value="<?= $data['id'] ?>">
	<p>Apakah kamu yakin ingin mengubah CS order ini?</p>
	<div class="col-md-12">
		<label for="">CS</label>
		<select type="text" class="form-control select2" name="dt[cs]" id="cs">
			<option value="">-</option>
			<?php
			foreach ($cs as $k2 => $v2) {
				$text = '';
				if ($data['cs'] == $v2['code']) {
					$text = 'selected';
				}
			?>
				<option <?= $text ?> value="<?= $v2['code'] ?>"><?= $v2['code'] ?> </option>
			<?php } ?>
		</select>
	</div>
	<div class="col-md-12 mt-3">
		<button type="submit" class="btn btn-primary btn-send">Simpan Perubahan</button>
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
						// window.location.href = "";
						$("#cs-<?= $data['id'] ?>").html($("#cs").val());
						$(".btn-send").removeClass("disabled").html('Simpan Perubahan').attr('disabled', false);
					}, 2500);
				} else {
					$(".form-message").hide().html(response).slideDown("fast");
					$(".btn-send").removeClass("disabled").html('Simpan Perubahan').attr('disabled', false);
				}
			},
			error: function(xhr, textStatus, errorThrown) {
				$(".btn-send").removeClass("disabled").html('Simpan Perubahan').attr('disabled', false);
				$(".form-message").hide().html(xhr).slideDown("fast");
			}
		});
		return false;
	});
</script>