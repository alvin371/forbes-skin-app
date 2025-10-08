<style>
	.tbody:before {
		line-height:0.1em!important;
		content:".";
		color:white;
		display:block;
	}
	table tr td, table tr th {
		max-width: unset!important;
		min-width: unset!important;
		width: unset!important;
		font-size:14px!important;
	}
	table tr:first-child td:first-child {
		border-top-left-radius: 12px;
		border-bottom-left-radius: 12px;
		padding: 16px 10px!important;
		max-width: unset!important;
		min-width: unset!important;
		width: unset!important;
	}
	table tr:first-child th:first-child {
		border-top-left-radius: 12px;
		border-bottom-left-radius: 12px;
		padding: 16px 10px !important;
		max-width: unset!important;
		min-width: unset!important;
		width: unset!important;
	}
</style>
<div class="form-message"></div>
<form action="<?= base_url() ?>/product/store" method="POST" id="form-modal">
	<input type="hidden" name="id" value="<?= $data['id'] ?>">
	<div class="col-md-12">
		<label for="">Name</label>
		<input type="text" class="form-control" name="dt[name]" value="<?= $data['name'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">SKU</label>
		<input type="text" class="form-control" name="dt[sku]" value="<?= $data['sku'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">Brand</label>
		<select type="text" class="form-control" name="dt[brand]">
			<?php
			foreach ($brand as $k2 => $v2) {
				$text = '';
				if ($data['brand'] == $v2['id']) {
					$text = 'selected';
				}
			?>
				<option <?= $text ?> value="<?= $v2['id'] ?>"><?= $v2['name'] ?></option>
			<?php } ?>
		</select>
	</div>
	<div class="col-md-12">
		<label for="">Weight (gr)</label>
		<input type="text" class="form-control" name="dt[weight]" value="<?= $data['weight'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">Buying Price</label>
		<input type="text" class="form-control" name="dt[price_buy]" value="<?= $data['price_buy'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">Normal Selling Price</label>
		<input type="text" class="form-control" name="dt[price_normal]" value="<?= $data['price_normal'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">Reseller Selling Price</label>
		<input type="text" class="form-control" name="dt[price_reseller]" value="<?= $data['price_reseller'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">Distributor Selling Price</label>
		<input type="text" class="form-control" name="dt[price_distributor]" value="<?= $data['price_distributor'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">Desc</label>
		<input type="text" class="form-control" name="dt[desc]" value="<?= $data['desc'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">Image</label>
		<?php if ($data['img']) { ?>
			<a href="<?= base_url() ?>/assets/img/product/<?= $data['img'] . '?token=' . DATE("Ymdhis", strtotime($data['updated_at'])) ?>" target="_blank"><i>Open Image</i></a>
		<?php } ?>
		<input type="file" class="form-control" name="file" accept="image/png, image/jpeg, image/jpg">
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