<div class="form-message"></div>
<form action="<?= base_url() ?>/endorse/action-process" method="POST" id="form-action">
	<input type="hidden" name="id_campaign" value="<?= $id_campaign ?>">
	<input type="hidden" name="code" value="<?= $data['code'] ?>">
	<p><?= $question ?></p>
	<?php if ($data['code'] == "ubah_status") {
		$arr = array();
		$arr[] = "Review";
		$arr[] = "Hold";
		$arr[] = "Acc";
		$arr[] = "Draft Content";
		$arr[] = "Posted Content";
		$arr[] = "Reject";
		$arr[] = "Problem";
	?>
		<select name="status" class="form-control" id="">
			<?php foreach ($arr as $k => $v) { ?>
				<option value="<?= $v ?>"><?= $v ?></option>
			<?php } ?>
		</select>
	<?php } ?>
	<?php if ($data['code'] == "ubah_status_data") {
		$arr = array();
		$arr[] = "Aktif";
		$arr[] = "Tidak Aktif";
	?>
		<select name="status" class="form-control" id="">
			<?php foreach ($arr as $k => $v) { ?>
				<option value="<?= $v ?>"><?= $v ?></option>
			<?php } ?>
		</select>
	<?php } ?>
	<?php if ($data['code'] == "ubah_status_payment") {
		$btn = 'Ajukan Payment';
		$arr = array();
		// $arr[] = "Pengajuan Payment";
		// $arr[] = "DP";
		$arr[] = "FP";
	?>
		<select name="status" class="form-control" id="">
			<?php foreach ($arr as $k => $v) { ?>
				<option value="<?= $v ?>"><?= $v ?></option>
			<?php } ?>
		</select>
	<?php } ?>
	<div class="col-md-12 mt-3">
		<button type="submit" class="btn btn-primary btn-send"><?= $btn ?></button>
	</div>
</form>
<script type="text/javascript">
	$("#form-action").submit(function() {
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
						$(".btn-send").removeClass("disabled").html('<?= $btn ?>').attr('disabled', false);
					}, 2500);
				} else {
					$(".form-message").hide().html(response).slideDown("fast");
					$(".btn-send").removeClass("disabled").html('<?= $btn ?>').attr('disabled', false);
				}
			},
			error: function(xhr, textStatus, errorThrown) {
				$(".btn-send").removeClass("disabled").html('<?= $btn ?>').attr('disabled', false);
				$(".form-message").hide().html(xhr).slideDown("fast");
			}
		});
		return false;
	});
</script>