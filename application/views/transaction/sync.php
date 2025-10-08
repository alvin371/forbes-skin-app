<?php

?>
<div class="form-message"></div>
<form action="<?= base_url() ?>product-3rd/sync-process" method="POST" id="form-modal">
	<input type="hidden" name="id" value="<?= $data['id'] ?>">
	<p class="mb-0">Apakah kamu yakin ingin melakukan sync data produk?</p>

	<?php
	foreach ($store as $k => $v) {

	?>
		<hr class="mb-2">
		<p class="mb-0 fw-600"><?= $k + 1 ?>. <?= $v['opt'] ?></p>
		<p class="mb-0">Marketplace : <?= ucwords(strtolower($v['marketplace'])) ?></p>
		<div class="mb-1 mt-1">
			<button form="form-modal-refresh-<?= $k ?>" type="submit" class="btn btn-edit btn-send-refresh">Refresh Token</button>
			<button form="form-modal-sync-<?= $k ?>" type="submit" class="btn btn-primary btn-send-sync">Sync Data</button>
		</div>
	<?php } ?>

</form>


<?php

$arr = array();
foreach ($store as $k => $v) {
?>
	<form action="<?= base_url() ?>transaction/sync-process?marketplace=<?= $v['marketplace'] ?>&shop_id=<?= $v['id'] ?>&start_date=<?= $_GET['start_date'] ?>&until_date=<?= $_GET['until_date'] ?>" method="POST" id="form-modal-sync-<?= $k ?>"></form>
	<form action="<?= base_url() ?>marketplace-account/refresh-token-process?marketplace=<?= $v['marketplace'] ?>&shop_id=<?= $v['id'] ?>" method="POST" id="form-modal-refresh-<?= $k ?>"></form>

	<script type="text/javascript">
		$("#form-modal-sync-<?= $k ?>").submit(function() {
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
					$(".btn-send-sync")
						.addClass("disabled")
						.html(
							'<div class="loading-ellipsis"><div></div><div></div><div></div><div></div></div>'
						)
						.attr("disabled", true);
					form.find(".form-message").slideUp().html("");
				},
				success: function(response, textStatus, xhr) {
					var str = response;
					console.log(str);
					if (str.indexOf("success") != -1) {
						$(".form-message").hide().html(response).slideDown("fast");
						setTimeout(function() {
							window.location.href = "";
							$(".btn-send-sync")
								.removeClass("disabled")
								.html("Sync Data")
								.attr("disabled", false);
						}, 2500);
					} else {
						$(".form-message").hide().html(response).slideDown("fast");
						$(".btn-send-sync")
							.removeClass("disabled")
							.html("Sync Data")
							.attr("disabled", false);
					}
				},
				error: function(xhr, textStatus, errorThrown) {
					$(".btn-send-sync")
						.removeClass("disabled")
						.html("Sync Data")
						.attr("disabled", false);
					$(".form-message").hide().html(xhr).slideDown("fast");
				},
			});
			return false;
		});
	</script>

	<script type="text/javascript">
		$("#form-modal-refresh-<?= $k ?>").submit(function() {
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
					$(".btn-send-refresh")
						.addClass("disabled")
						.html(
							'<div class="loading-ellipsis"><div></div><div></div><div></div><div></div></div>'
						)
						.attr("disabled", true);
					form.find(".form-message").slideUp().html("");
				},
				success: function(response, textStatus, xhr) {
					var str = response;
					console.log(str);
					if (str.indexOf("success") != -1) {
						$(".form-message").hide().html(response).slideDown("fast");
						setTimeout(function() {
							window.location.href = "";
							$(".btn-send-refresh")
								.removeClass("disabled")
								.html("Refresh Token")
								.attr("disabled", false);
						}, 2500);
					} else {
						$(".form-message").hide().html(response).slideDown("fast");
						$(".btn-send-refresh")
							.removeClass("disabled")
							.html("Refresh Token")
							.attr("disabled", false);
					}
				},
				error: function(xhr, textStatus, errorThrown) {
					$(".btn-send-refresh")
						.removeClass("disabled")
						.html("Refresh Token")
						.attr("disabled", false);
					$(".form-message").hide().html(xhr).slideDown("fast");
				},
			});
			return false;
		});
	</script>

<?php } ?>