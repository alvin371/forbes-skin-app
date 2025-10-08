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
<p>Pilih salah satu marketplace di bawah ini!</p>
<a href="<?= base_url() ?>marketplace-account/auth?type=TIKTOK" class="a-none">
	<img style="width:55px;border-radius:10px;" src="<?= base_url() ?>assets/img/marketplace/3.png">
</a>
<a href="<?= base_url() ?>marketplace-account/auth?type=SHOPEE" class="a-none">
	<img style="width:55px;border-radius:10px;" src="<?= base_url() ?>assets/img/marketplace/1.png">
</a>
<a href="<?= base_url() ?>marketplace-account/auth?type=LAZADA" class="a-none">
	<img style="width:55px;border-radius:10px;" src="<?= base_url() ?>assets/img/marketplace/2.png">
</a>
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
						$(".btn-send").removeClass("disabled").html('Masuk ke Toko').attr('disabled', false);
					}, 2500);
				} else {
					$(".form-message").hide().html(response).slideDown("fast");
					$(".btn-send").removeClass("disabled").html('Masuk ke Toko').attr('disabled', false);
				}
			},
			error: function(xhr, textStatus, errorThrown) {
				$(".btn-send").removeClass("disabled").html('Masuk ke Toko').attr('disabled', false);
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