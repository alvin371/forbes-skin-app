<div class="form-message"></div>
<form action="<?= base_url() ?>/customer/store" method="POST" id="form-modal">
	<input type="hidden" name="id" value="<?= $data['id'] ?>">
	<div class="col-md-12">
		<label for="">Full Name</label>
		<input type="text" class="form-control" name="dt[full_name]" value="<?= $data['full_name'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">User Name</label>
		<input type="text" class="form-control" name="dt[username]" value="<?= $data['user_name'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">Phone Number</label>
		<input type="text" class="form-control" name="dt[phone]" value="<?= $data['phone'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">Birth Date</label>
		<input type="date" class="form-control" name="dt[birth_date]" value="<?= $data['birth_date'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">Type</label>
		<select type="text" class="form-control" name="dt[type]">
			<?php
			$arr = array();
			$arr[] = "Pelanggan";
			$arr[] = "Reseller";
			$arr[] = "Distributor";
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
		<label for="">PIC</label>
		<select type="text" class="form-control" name="dt[pic]">
			<?php
			foreach ($pic as $k2 => $v2) {
				$text = '';
				if ($data['pic'] == $v2['id']) {
					$text = 'selected';
				}
			?>
				<option <?= $text ?> value="<?= $v2['id'] ?>"><?= $v2['full_name'] ?></option>
			<?php } ?>
		</select>
	</div>
	<div class="col-md-12">
		<label for="">Tag</label>
		<input type="text" class="form-control" name="dt[tag]" value="<?= $data['tag'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">Province</label>
		<input type="text" class="form-control" name="dt[province_text]" value="<?= $data['province_text'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">City</label>
		<input type="text" class="form-control" name="dt[city_text]" value="<?= $data['city_text'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">Subdistrict</label>
		<input type="text" class="form-control" name="dt[subdistrict_text]" value="<?= $data['subdistrict_text'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">Address</label>
		<input type="text" class="form-control" name="dt[address]" value="<?= $data['address'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">Service Type</label>
		<input type="text" class="form-control" name="dt[service_type]" value="<?= $data['service_type'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">Service Start</label>
		<input type="datetime-local" class="form-control" name="dt[service_str]" value="<?= (DATE("Y-m-d H:i", strtotime($data['service_str']))) ?>">
	</div>
	<div class="col-md-12">
		<label for="">Service Expired</label>
		<input type="datetime-local" class="form-control" name="dt[service_exp]" value="<?= (DATE("Y-m-d H:i", strtotime($data['service_exp']))) ?>">
	</div>
	<div class="col-md-12">
		<label for="">Desc</label>
		<input type="text" class="form-control" name="dt[desc]" value="<?= $data['desc'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">Status</label>
		<select type="text" class="form-control" name="dt[status]">
			<?php
			$arr = array();
			$arr[] = "ENABLE";
			$arr[] = "DISABLE";
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
		<button type="submit" class="btn btn-primary btn-send">STORE DATA</button>
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
						$(".btn-send").removeClass("disabled").html('STORE DATA').attr('disabled', false);
					}, 2500);
				} else {
					$(".form-message").hide().html(response).slideDown("fast");
					$(".btn-send").removeClass("disabled").html('STORE DATA').attr('disabled', false);
				}
			},
			error: function(xhr, textStatus, errorThrown) {
				$(".btn-send").removeClass("disabled").html('STORE DATA').attr('disabled', false);
				$(".form-message").hide().html(xhr).slideDown("fast");
			}
		});
		return false;
	});
</script>