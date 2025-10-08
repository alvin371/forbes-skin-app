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
<form action="<?= base_url() ?>/product-3rd/update" method="POST" id="form-modal" enctype="multipart/form-data">
	<input type="hidden" name="id" value="<?= $data['id'] ?>">
	<div class="col-md-12">
		<label for="">Nama Produk</label>
		<input readonly type="text" class="form-control" name="dt[name]" value="<?= $data['name'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">SKU</label>
		<input readonly type="text" class="form-control" name="dt[name]" value="<?= $data['sku'] ?>">
	</div>
	<?php foreach($item as $ki=>$vi){ ?>
	<input type="hidden" name="dt_id[<?=$ki?>]" value="<?=$vi['id']?>">
	<input type="hidden" name="dt_sku[<?=$ki?>]" value="<?=$vi['sku']?>">
	<div class="col-md-12">
		<hr>
	</div>
	<div class="col-md-12">
		<label for="">Nama Varian</label>
		<input readonly type="text" class="form-control" name="dt[name]" value="<?= $vi['name'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">SKU</label>
		<input readonly type="text" class="form-control" name="dt[sku]" value="<?= $vi['sku'] ?>">
	</div>
	<div class="col-md-12">
		<label for="">Konfigurasi Produk</label>
		<table class="table" style="width:100%!important;"  id="tbody_item_<?=$ki?>">
			<tr class="bg-blue-2">
				<th class="text-start">Produk</th>
				<th class="text-end" style="">Qty</th>
				<th class="text-start" style="">Satuan</th>
				<th style="">
					<a href="#!" onclick="add_tr_<?=$ki?>()">+</a>
				</th>
			</tr>
				<?php
				$conf = json_decode($vi['json'], true);
				foreach ($conf as $k => $v) { ?>
					<tbody class="tbody" id="tr-<?=$ki?>-<?= $k ?>">
					<tr>
					<td>
							<select class="form-control" data-id="<?= $k ?>" type="text" name="dtt[<?=$vi['id']?>][<?= $k ?>][product]" id="product-<?= $k ?>">
								<?php
								foreach ($product as $k2 => $v2) {
									$text = '';
									if ($v['product'] == $v2['id']) {
										$text = 'selected';
									}
								?>
									<option <?= $text ?> value="<?= $v2['id'] ?>"><?= $v2['sku'] ?> | <?= $v2['name'] ?></option>
								<?php } ?>
							</select>
						</td>
						<td>
							<input class="form-control text-end" data-id="<?= $k ?>" type="text" name="dtt[<?=$vi['id']?>][<?= $k ?>][qty]" id="qty-<?= $k ?>" value="<?= $v['qty'] ?>">
						</td>
						<td>
							<select class="form-control" data-id="<?= $k ?>" type="text" name="dtt[<?=$vi['id']?>][<?= $k ?>][unit]" id="unit-<?= $k ?>">
								<?php
								$arr = array();
								$arr[] = "Pcs";
								$arr[] = "Box";
								foreach ($arr as $k2 => $v2) {
									$text = '';
									if ($v['unit'] == $v2) {
										$text = 'selected';
									}
								?>
									<option <?= $text ?> value="<?= $v2 ?>"><?= $v2 ?></option>
								<?php } ?>
							</select>
						</td>
						<td style="">
							<a href="#!" onclick="delete_tr_<?=$ki?>(<?= $k ?>)" class="text-danger">-</a>
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
	<script>
		var index_<?=$k?> = <?= $k + 1 ?>;
		function add_tr_<?=$ki?>() {
			$("#notif").hide();
			html = '';
			html += '<tbody id="tr-<?=$ki?>-' + index_<?=$k?> + '"><tr>';
			html += '<td>';
			html += '<select class="form-control text-start" name="dtt[<?=$vi['id']?>][' + index_<?=$k?> + '][product]" id="product-' + index_<?=$k?> + '"> ';
			html += '<?=$opt_product?>';
			html += '</select>';
			html += '</td>';
			html += '<td>';
			html += '<input class="form-control text-end" type="text" name="dtt[<?=$vi['id']?>][' + index_<?=$k?> + '][qty]" id="qty-' + index_<?=$k?> + '" value="0"> ';
			html += '</td>';
			html += '<td>';
			html += '<select class="form-control text-start" name="dtt[<?=$vi['id']?>][' + index_<?=$k?> + '][unit]" id="unit-' + index_<?=$k?> + '"> ';
			html += '<option>Pcs</option>';
			html += '<option>Box</option>';
			html += '</select>';
			html += '</td>';
			html += '<td style="vertical-align: middle;">';
			html += '<a href="#!" onclick="delete_tr_<?=$ki?>(' + index_<?=$k?> + ')" class="text-danger">-</a>';
			html += '</td>';
			html += '<tr></tbody>';
			$('#tbody_item_<?=$ki?>').append(html);
			index_<?=$k?>++;
		}


		function delete_tr_<?=$ki?>(id) {
			$('#tr-<?=$ki?>-' + id).html('');
		}
	</script>
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