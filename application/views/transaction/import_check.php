<style>
	table tr th {
		font-size: 14px !important;
	}

	tbody:before {
		line-height: 0.5em;
		content: ".";
		color: white;
		display: block;
	}

	/* table tr th{
		background:#FFF!important;
	}
	table tr td{
		background:#FFF!important;
	} */
</style>
<div class="row align-items-center">
	<div class="col-lg-12 mb-3">
		<h3 class="text-primary fw-600">KONFIRMASI IMPORT ORDER</h3>
	</div>
</div>
<div class="form-message"></div>
<form action="<?= base_url() ?>transaction/import-process" method="POST" id="form-modal" enctype="multipart/form-data">
	<?php
	if (count($data) > 0) {
		$count = $template->separator_only(count($data) - 1);
	} else {
		$count = 0;
	}
	if ($count > 1) {
		$count = $count - 1;
	}
	?>
	<p class="mb-1"><label class="text-notif"><?= $count ?> data ditemukan!</label></p>
	<input type="hidden" name="param" value="<?= $param ?>">
	<input type="hidden" name="filepath" value="<?= $filepath ?>">

	<div class="row">
		<div class="col-lg-12">
			<div class="mb-3">

				<div class="table-responsive" id="table-item">
					<table class="table" id="tbody">
						<thead>
							<tr class="bg-blue-2 text-white">
								<?php
								foreach ($header_1 as $k => $v) {
								?>
									<th class="text-start"><?= $v ?></th>
								<?php } ?>
								<?php
								foreach ($header_3 as $k => $v) {
								?>
									<th class="text-start"><?= $v ?></th>
								<?php } ?>
								<?php
								foreach ($header_2 as $k => $v) {
								?>
									<th class="text-start" style="background:#ffff00"><?= $v ?></th>
								<?php } ?>
								<?php
								foreach ($header_2_gift as $k => $v) {
								?>
									<th class="text-start" style="background:#a5d870"><?= $v ?></th>
								<?php } ?>
							</tr>
						</thead>
						<?php foreach ($data as $k2 => $v2) {
							if ($k2 > 0 && $v2['2']) {
								$v2[19] = $template->separator_only($v2[19]);
								$v2[20] = $template->separator_only($v2[20]);
								$v2[21] = $template->separator_only($v2[21]);

								// if ($v2[1]) {
								// 	$v2[21] = "0";
								// }
						?>
								<tbody>
									<tr>
										<?php foreach ($v2 as $k => $v) {
											$class = "text-start";
											if ($k > 22) {
												$class = "text-center";
											}

										?>
											<td class="<?= $class ?>"><?= $v2[$k] ?></td>
										<?php } ?>
									</tr>
								</tbody>
						<?php }
						} ?>
					</table>
				</div>

				<div class="col-md-12">
					<button type="submit" class="btn btn-primary btn-send mt-3">Import Data</button>
				</div>
			</div>
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
						window.location.href = "<?= base_url() ?>/transaction<?= $param ?>";
						$(".btn-send").removeClass("disabled").html('Import Data').attr('disabled', false);
					}, 2500);
				} else {
					$(".form-message").hide().html(response).slideDown("fast");
					$(".btn-send").removeClass("disabled").html('Import Data').attr('disabled', false);
				}
			},
			error: function(xhr, textStatus, errorThrown) {
				$(".btn-send").removeClass("disabled").html('Import Data').attr('disabled', false);
				$(".form-message").hide().html(xhr).slideDown("fast");
			}
		});
		return false;
	});
</script>