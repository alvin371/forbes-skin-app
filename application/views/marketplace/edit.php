<style>
	.tbody:before {
		line-height: 0.1em !important;
		content: ".";
		color: white;
		display: block;
	}

	table tr td,
	table tr th {
		max-width: unset !important;
		min-width: unset !important;
		width: unset !important;
		font-size: 14px !important;
	}

	table tr:first-child td:first-child {
		border-top-left-radius: 12px;
		border-bottom-left-radius: 12px;
		padding: 16px 10px !important;
		max-width: unset !important;
		min-width: unset !important;
		width: unset !important;
	}

	table tr:first-child th:first-child {
		border-top-left-radius: 12px;
		border-bottom-left-radius: 12px;
		padding: 16px 10px !important;
		max-width: unset !important;
		min-width: unset !important;
		width: unset !important;
	}
</style>
<div class="form-message"></div>
<form action="<?= base_url() ?>/marketplace/update" method="POST" id="form-modal" enctype="multipart/form-data">
	<input type="hidden" name="id" value="<?= $data['id'] ?>">
	<div class="col-md-12">
		<label for="">Nama</label>
		<input type="text" class="form-control" name="dt[name]" value="<?= $data['name'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">Konfigurasi Biaya</label>
		<table class="table" style="width:100%!important;" id="tbody-item">
			<tr class="bg-blue-2">
				<th class="text-start" style="">Start Date</th>
				<th class="text-start">Type</th>
				<th class="text-end">Fee</th>
				<th style="">
					<a href="#!" onclick="add_tr()">+</a>
				</th>
			</tr>

			<?php
			$conf = json_decode($data['configuration'], true);
			foreach ($conf as $k => $v) { ?>
				<tbody class="tbody" id="tr-<?= $k ?>">
					<tr>
						<td>
							<input class="form-control" data-id="<?= $k ?>" type="date" name="dtt[<?= $k ?>][date]" id="date-<?= $k ?>" value="<?= $v['date'] ?>">
						</td>
						<td>
							<select class="form-control" data-id="<?= $k ?>" type="text" name="dtt[<?= $k ?>][type]" id="type-<?= $k ?>">
								<?php
								$arr = array();
								$arr[] = "Nominal";
								$arr[] = "Persentase";
								foreach ($arr as $k2 => $v2) {
									$text = '';
									if ($v['type'] == $v2) {
										$text = 'selected';
									}
								?>
									<option <?= $text ?> value="<?= $v2 ?>"><?= $v2 ?></option>
								<?php } ?>
							</select>
						</td>
						<td>
							<input class="form-control text-end" data-id="<?= $k ?>" type="text" name="dtt[<?= $k ?>][fee]" id="fee-<?= $k ?>" value="<?= $v['fee'] ?>">
						</td>
						<td style="">
							<a href="#!" onclick="delete_tr(<?= $k ?>)" class="text-danger">-</a>
						</td>
					</tr>
				</tbody>
			<?php } ?>
			<?php if (empty($conf)) { ?>
				<tr id="notif">
					<td colspan="4" class="text-start text-red"><span><i>Konfigurasi belum tersedia!</i></span></td>
				</tr>
			<?php } ?>
		</table>
	</div>
	<div class="col-md-12">
		<label for="">Keterangan</label>
		<input type="text" class="form-control" name="dt[desc]" value="<?= $data['desc'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">Gambar</label>
		<?php if ($data['img']) { ?>
			<a href="<?= base_url() ?>/assets/img/marketplace/<?= $data['img'] . '?token=' . DATE("Ymdhis", strtotime($data['updated_at'])) ?>" target="_blank"><i>Buka Gambar</i></a>
		<?php } ?>
		<input type="file" class="form-control" name="file" accept="image/png, image/jpeg, image/jpg">
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

	var index = <?= $k + 1 ?>;

	function add_tr() {
		$("#notif").hide();
		html = '';
		html += '<tbody class="tbody" id="tr-' + index + '"><tr>';
		html += '<td>';
		html += '<input class="form-control text-start" type="date" name="dtt[' + index + '][date]" id="start_date-' + index + '" value="<?= DATE("Y-m-d") ?>"> ';
		html += '</td>';
		html += '<td>';
		html += '<select class="form-control text-start" name="dtt[' + index + '][type]" id="type-' + index + '"> ';
		html += '<option>Nominal</option>';
		html += '<option>Persentase</option>';
		html += '</select>';
		html += '</td>';
		html += '<td>';
		html += '<input class="form-control text-end" type="text" name="dtt[' + index + '][fee]" id="fee-' + index + '" value="0"> ';
		html += '</td>';
		html += '<td style=";vertical-align: middle;">';
		html += '<a href="#!" onclick="delete_tr(' + index + ')" class="text-danger">-</a>';
		html += '</td>';
		html += '<tr></tbody>';
		$('#tbody-item').append(html);
		index++;
	}


	function delete_tr(id) {
		$('#tr-' + id).html('');
	}
</script>