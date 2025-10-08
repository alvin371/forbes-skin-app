
<style>
	table tr th{
		font-size:14px!important;
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
		<h3 class="text-primary fw-600">BUAT ORDER</h3>
	</div>
</div>
<div class="form-message"></div>
<form action="<?= base_url() ?>/transaction/store" method="POST" id="form-modal" enctype="multipart/form-data">
	<input type="hidden" name="id" value="<?= $data['id'] ?>">

	<div class="row">
		<div class="col-lg-5">
			<div class="card mb-3">
				<div class="col-md-12">
					<label for="">Tanggal</label>
					<input type="datetime-local" class="form-control" name="dt[date]" value="<?= DATE("Y-m-d H:i") ?>" id="date">
				</div>
				<div class="col-md-12">
					<label for="">Brand</label>
					<select type="text" class="form-control select2" name="dt[brand]" id="brand">
						<?php
						foreach ($brand as $k2 => $v2) {
							$text = '';
							if ($data['brand'] == $v2['code']) {
								$text = 'selected';
							}
						?>
							<option <?= $text ?> value="<?= $v2['code'] ?>"><?= $v2['code'] ?> </option>
						<?php } ?>
					</select>
				</div>
				<div class="col-md-12">
					<label for="">Channel</label>
					<select type="text" class="form-control select2" name="dt[marketplace]" id="marketplace">
						<?php
						foreach ($marketplace as $k2 => $v2) {
							$text = '';
							if ($data['marketplace'] == $v2['name']) {
								$text = 'selected';
							}
						?>
							<option <?= $text ?> value="<?= $v2['name'] ?>"><?= $v2['name'] ?> </option>
						<?php } ?>
					</select>
				</div>
				<div class="col-md-12">
					<label for="">Order ID</label>
					<input type="text" class="form-control" name="dt[order_id]" value="<?= $data['order_id'] ?>" disabled>
				</div>
				<div class="col-md-12">
					<label for="">Ekspedisi</label>
					<select type="text" class="form-control select2" name="dt[shipping]">
						<option value="<?=$data['shipping']?>"><?=$data['shipping']?></option>
						<?php
						foreach ($shipping as $k2 => $v2) {
							$text = '';
							if ($data['shipping'] == $v2['name']) {
								$text = 'selected';
							}
						?>
							<option <?= $text ?> value="<?= $v2['name'] ?>"><?= $v2['name'] ?> </option>
						<?php } ?>
					</select>
				</div>
				<div class="col-md-12">
					<label for="">No Resi</label>
					<input type="text" class="form-control" name="dt[awb_number]" value="<?= $data['awb_number'] ?>">
				</div>
				<div class="col-md-12">
					<label for="">Keterangan</label>
					<input type="text" class="form-control" name="dt[desc]" value="<?= $data['desc'] ?>">
				</div>
			</div>
			<div class="card mb-3">
				<div class="col-md-12">
					<label for="">Tipe Pembayaran</label>
					<select type="text" class="form-control" name="dt[payment_type]">
						<?php
						$arr = array();
						$arr[] = "TF";
						$arr[] = "COD";
						foreach ($arr as $k2 => $v2) {
							$text = '';
							if ($data['paid_status'] == $v2) {
								$text = 'selected';
							}
						?>
							<option <?= $text ?> value="<?= $v2 ?>"><?= $v2 ?></option>
						<?php } ?>
					</select>
				</div>
				<div class="col-md-12">
					<label for="">Bank</label>
					<select type="text" class="form-control" name="dt[bank]">
						<?php
						$arr = array();
						$arr[] = "";
						$arr[] = "BCA";
						$arr[] = "BNI";
						$arr[] = "BRI";
						$arr[] = "MAN";
						foreach ($arr as $k2 => $v2) {
							$text = '';
							if ($data['bank'] == $v2) {
								$text = 'selected';
							}
						?>
							<option <?= $text ?> value="<?= $v2 ?>"><?= $v2 ?></option>
						<?php } ?>
					</select>
				</div>
				<div class="col-md-12">
					<label for="">Status Pembayaran</label>
					<select type="text" class="form-control" name="dt[payment_status]">
						<?php
						$arr_val = array();
						$arr_val[] = "Unpaid";
						$arr_val[] = "Paid";
						$arr = array();
						$arr[] = "Belum Dibayar";
						$arr[] = "Sudah Dibayar";
						foreach ($arr as $k2 => $v2) {
							$text = '';
							if ($data['payment_status'] == $arr_val[$k2]) {
								$text = 'selected';
							}
						?>
							<option <?= $text ?> value="<?= $arr_val[$k2] ?>"><?= $v2 ?></option>
						<?php } ?>
					</select>
				</div>
				<div class="col-md-12">
					<label for="">Tanggal Pembayaran</label>
					<input type="date" class="form-control" name="dt[pay_at]" value="<?= $data['pay_at'] ?>">
				</div>
				<div class="col-md-12">
					<label for="">Tanggal Pengecekan Pembayaran</label>
					<input type="date" class="form-control" name="dt[check_at]" value="<?= $data['check_at'] ?>">
				</div>
				<div class="col-md-12">
					<label for="">Order Status</label>
					<select type="text" class="form-control" name="dt[order_status]">
						<?php
						 $arr_val = array();
						 $arr_val[] = 'UNPAID';
						 $arr_val[] = 'PROCESSED';
						 $arr_val[] = 'READY_TO_SHIP';
						 $arr_val[] = 'SHIPPED';
						 $arr_val[] = 'DELIVERED';
						 $arr_val[] = 'COMPLETED';
						 $arr_val[] = 'CANCELLED';
						 $arr_val[] = 'RETURN';
						 $arr_val[] = 'REFUND';
						 $arr_val[] = 'COMPLETED';

						 $arr = array();
						 $arr[] = 'Order Belum Bayar';
						 $arr[] = 'Order Proses';
						 $arr[] = 'Order Siap Dikemas';
						 $arr[] = 'Pengiriman';
						 $arr[] = 'Diterima';
						 $arr[] = 'Selesai';
						 $arr[] = 'Dibatalkan';
						 $arr[] = 'Return';
						 $arr[] = 'Refund';
						 $arr[] = 'Dicairkan';
						foreach ($arr as $k2 => $v2) {
							$text = '';
							if ($data['order_status'] == $arr_val[$k2]) {
								$text = 'selected';
							}
						?>
							<option <?= $text ?> value="<?= $arr_val[$k2] ?>"><?= $v2 ?></option>
						<?php } ?>
					</select>
				</div>
			</div>
			<div class="card mb-3">
				
				<!-- <div class="col-md-12">
					<label for="">CB/CL</label>
					<input readonly type="text" class="form-control bg-b" value="<?= $data['cb_cl'] ?>">
				</div> -->


				<div class="col-md-12">
					<label for="">Pelanggan</label>
					<input type="hidden" name="existing_customer" value="<?=$data['customer']?>">
					<select type="text" class="form-control select2" name="dt[customer]" id="pelanggan">
					<option value="<?=$data['customer']?>"><?=$data['customer_text']?></option>
					</select>
					<script>

						$(function(){
						$('#pelanggan').select2({
							minimumInputLength: 1,
							allowClear: false,
							placeholder: '',
							minimumResultsForSearch: Infinity, // Disable search box
							ajax: {
								dataType: 'json',
								url: '<?=base_url()?>/ajax/get-customer-list',
								delay: 100,
								data: function(params) {
									return {
										search: params.term
									}
								},
								processResults: function (data, page) {
									return {
										results: data
									};
								},
							},
							language: {
								inputTooShort: function(args) {
									return "Masukkan 1 karakter atau lebih";
								}
							}
						}).on('select2:select', function (evt) {
							var id = $("#pelanggan option:selected").val();
							$.ajax({
								dataType: "json",
								url: '<?= base_url() ?>/ajax/get-customer-detail?id=' + id+'&id_trx=<?=$data['id']?>',
								success: function(html) {
									$("#full_name").val(html.full_name);
									$("#phone").val(html.phone);
									$("#username").val(html.username);
									$("#address").val(html.address);
									$("#province_text").val(html.province_text);
									$("#city_text").val(html.city_text);
									$("#subdistrict_text").val(html.subdistrict_text);
									$("#birth_date").val(html.birth_date);
								}
							});
						});
					});

					</script>

						
						<div class="col-md-12 mt-2">
							<label for="">Pelanggan Baru?</label>
							<br>
							<input type="radio" name="check" value="0" id="check-1" checked > <label for="check-1" style="font-weight:400!important">Tidak</label>
							<input type="radio" name="check" value="1" id="check-2"> <label for="check-2" style="font-weight:400!important">Iya</label>
						</div>
						<div class="col-md-12 mt-2">
							<label for="">Nama Lengkap</label>
							<input type="text" class="form-control" name="dt[customer_text]" value="<?=$data['customer_text']?>" id="full_name">
						</div>
						<div class="col-md-12 mt-2">
							<label for="">No. HP</label>
							<input type="text" class="form-control" name="dt[phone]" value="<?=$data['phone']?>" id="phone">
						</div>
						<div class="col-md-12 mt-2">
							<label for="">User Name</label>
							<input type="text" class="form-control" name="dt[c_username]" value="<?=$data['c_username']?>" id="username">
						</div>
						<div class="col-md-12 mt-2">
							<label for="">Alamat Lengkap</label>
							<textarea type="text" class="form-control" name="dt[address]" id="address" style="min-height:100px"><?=$data['address']?></textarea>
						</div>
						<div class="col-md-12 mt-2">
							<label for="">Provinsi</label>
							<input type="text" class="form-control" name="dt[province_text]" value="<?=$data['province_text']?>" id="province_text">
						</div>
						<div class="col-md-12 mt-2">
							<label for="">Kota</label>
							<input type="text" class="form-control" name="dt[city_text]" value="<?=$data['city_text']?>" id="city_text">
						</div>
						<div class="col-md-12 mt-2">
							<label for="">Kecamatan</label>
							<input type="text" class="form-control" name="dt[subdistrict_text]" value="<?=$data['subdistrict_text']?>" id="subdistrict_text">
						</div>
						<div class="col-md-12 mt-2">
							<label for="">Tgl Lahir</label>
							<input type="date" class="form-control" name="dt[birth_date]" value="<?=$data['birth_date']?>" id="birth_date">
						</div>
				</div>
			</div>
		</div>
		<div class="col-lg-7">
			<div class="card mb-3">
				<div class="col-md-12">
					<label for="">Produk</label>
					<select for="product-form" type="text" class="form-control select2" id="product">
						<option value=""></option>
						<?php
						foreach ($product as $k2 => $v2) {
						?>
							<option <?= $text ?> value="<?= $v2['id'] ?>"><?= $v2['name'] ?> | <?= $v2['sku'] ?> | <?= $v2['brand'] ?></option>
						<?php } ?>
					</select>
				</div>
				<div class="col-md-12">
					<!-- <label for="">Product List</label> -->
					<div class="table-responsive mt-2">
						<table class="table table-striped" id="datatable-1" style="width:100%!important;">
							<thead>
                    			<tr class="bg-blue-2 text-white">
									<th class="text-start">Nama Produk</th>
									<th class="text-end">Harga</th>
									<th class="text-end">Qty</th>
									<th class="text-end">Total Harga</th>
									<th style="min-width:10px!important">
										#
									</th>
								</tr>
							</thead>
							<tbody id="tbody">
							</tbody>
							<?php
							if (empty($json)) {
							?>
								<tr>
									<td colspan="5" id="notif" class="text-start"><span><i>Silahkan pilih produk dahulu!</i></span></td>
								</tr>
							<?php } ?>
							<tbody>
								<tr class="bg-white">
									<th class="text-start bg-white" colspan="3">Grand Total</th>
									<th class="text-end bg-white" id="total"></th>
									<th class="text-end bg-white" style="min-width:10px!important"></th>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<div class="card mb-3">
				<div class="col-md-12">
					<div class="row">
						<div class="col-md-6">
							<label for="">Tipe Diskon</label>
							<select type="text" class="form-control" name="dt[discount_type]" id="discount_type">
								<?php
								$arr = array();
								$arr[] = "Nominal";
								$arr[] = "Persentase";
								foreach ($arr as $k2 => $v2) {
									$text = '';
									if ($data['discount_type'] == $v2) {
										$text = 'selected';
									}
								?>
									<option <?= $text ?> value="<?= $v2 ?>"><?= $v2 ?></option>
								<?php } ?>
							</select>
						</div>
						<div class="col-md-6">
							<label for="">Nominal</label>
							<input type="text" class="form-control" name="dt[discount_nominal]" value="0" id="discount_nominal">
						</div>
					</div>
				</div>
			</div>
			<div class="card mb-3">
				<div class="col-md-12">
					<div class="col-md-12">
						<label for="">Total</label>
						<input readonly type="text" class="form-control bg-b" name="dt[omset_kotor]" value="<?= $data['omset_kotor'] ?>" id="total_1">
					</div>
					<div class="col-md-12">
						<label for="">Diskon</label>
						<input type="text" class="form-control bg-b" name="dt[diskon_penjual]" value="0" id="discount">
					</div>
					<div class="col-md-12">
						<label for="">Ongkir</label>
						<input type="text" class="form-control" name="dt[shipping_price]" value="0" id="shipping_fee">
					</div>
					<div class="col-md-12 d-none">
						<label for="">Packing</label>
						<input type="text" class="form-control" name="dt[packing_price]" value="0" id="packing_price">
					</div>
					<div class="col-md-12">
						<label for="">Biaya Lainnya</label>
						<input type="text" class="form-control" name="dt[other_price]" value="0" id="other_price">
					</div>
					<div class="col-md-12">
						<label for="">Total Bayar</label>
						<input readonly type="text" class="form-control bg-b" name="dt[customer_price]" value="0" id="total_2">
					</div>
					<div class="col-md-12">
						<label for="">Dibayar</label>
						<input type="text" class="form-control" name="dt[dibayar]" value="0" id="dibayar">
					</div>
					<div class="col-md-12">
						<label for="">Kembalian</label>
						<input readonly type="text" class="form-control bg-b" name="dt[kembalian]" value="0" id="kembalian">
					</div>
					<div class="col-md-12">
						<label for="">Return</label>
						<input type="text" class="form-control bg-o" name="dt[return]" value="0" id="return_fee">
					</div>
				</div>
				<button type="submit" class="btn btn-primary btn-send mt-3">Simpan Data</button>
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
						window.location.href = "<?=base_url()?>/transaction";
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
		html += '<tr id="tr-' + index + '">';
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
		html += '<td style="max-width:10px!important;vertical-align: middle;">';
		html += '<a href="#!" onclick="delete_tr(' + index + ')" class="text-danger">-</a>';
		html += '</td>';
		html += '<tr>';
		$('#tbody').append(html);
		index++;
	}


	function delete_tr(id) {
		$('#tr-' + id).html('');
	}
</script>
<script>

      

	$(document).ready(function() {

		$("#product").off("select2:select").on("select2:select", function(e) {
			var val = e.params.data.id;
			var product = val;
			var customer = $("#customer").val();
			var id = '<?= $data['id'] ?>';
			$.ajax({
				url: "<?= base_url() ?>/transaction/set-product",
				type: "POST",
				data: {
					id: id,
					product: product,
					customer: customer,
				},
				success: function(response) {
					get_cart();
				}
			});
			$("#product").on("select2:close", function() {
				$(this).val(null).trigger("change");
			});
		});

		function get_cart() {
			$("#notif").hide();
			$.ajax({
				dataType: "json",
				url: '<?= base_url() ?>/transaction/get-cart?id=<?= $data['id'] ?>',
				success: function(html) {
					$("#tbody").html(html.html);
					$("#total").html(html.total);
					$("#total_1").val(html.total);
					get_total_2();
				}
			});
		}
		get_cart();

		$("#customer").off("select2:select").on("select2:select", function(e) {
			var val = e.params.data.id;
			get_customer_div(val);
			get_customer_detail(val);
		});

		$('#i-phone,#i-address,#i-province_text,#i-city_text,#i-subdistrict_text').keyup(function() {
			$('#n-phone').val($('#i-phone').val());
			$('#n-address').val($('#i-address').val());
			$('#n-province_text').val($('#i-province_text').val());
			$('#n-city_text').val($('#i-city_text').val());
			$('#n-subdistrict_text').val($('#i-subdistrict_text').val());
		});

		function get_customer_div(val) {
			val = parseInt(val);
			if (!isNaN(val)) {
				$("#customer-div").hide();
			} else {
				$("#customer-div").show();
			}
		}


		function get_customer_detail(val) {
			$.ajax({
				dataType: "json",
				url: '<?= base_url() ?>/ajax/get-customer-detail?id=' + val,
				success: function(html) {
					$('#n-address').val(html.address);
					$('#n-province_text').val(html.province_text);
					$('#n-city_text').val(html.city_text);
					$('#n-subdistrict_text').val(html.subdistrict_text);
					$('#n-phone').val(html.phone);
				}
			});
		}


		get_customer_div(<?= $data['customer'] ?>);

		$("#marketplace").off("select2:select").on("select2:select", function(e) {
			// get_marketplace_fee();
		});

		$('#discount_type').change(function() {
			get_diskon();
		});
		$('#discount_nominal').keyup(function() {
			get_diskon();
		});
		$('#dibayar').keyup(function() {
			get_kembalian();
		});
		
		function get_diskon(){
			var discount_type = $('#discount_type').val();
			var discount_nominal = parseFloat($('#discount_nominal').val());
			var total_1 = parseFloat($('#total_1').val());
			if(discount_type == "Nominal"){
				$('#discount').val(discount_nominal);
			}else{
				discount_nominal = (total_1 * discount_nominal / 100);
				$('#discount').val(discount_nominal);
			}
			get_total_2();
		}

		function get_kembalian(){
			var total_2 = parseFloat($('#total_2').val());
			var dibayar = parseFloat($('#dibayar').val());
			val = (dibayar - total_2);
				$('#kembalian').val(val);
		}
		

		$('#date').change(function() {
			// get_marketplace_fee();
		});

		$('#discount_code').keyup(function() {
			get_discount();
		});

		function get_discount() {
			var code = $("#discount_code").val();
			var total_1 = $("#total_1").val();
			$.ajax({
				dataType: "json",
				url: '<?= base_url() ?>/transaction/get-discount?id=<?= $data['id'] ?>&code=' + code + '&total=' + total_1,
				success: function(html) {
					$("#discount_type").val(html.type);
					$("#discount_min").val(html.min_nominal);
					$("#discount_nominal").val(html.nominal);
					$("#discount").val(html.html);
					get_total_2();
				}
			});
		}

		function get_marketplace_fee() {
			var id_marketplace = $("#marketplace").val();
			var date = $("#date").val();
			var total_2 = $("#total_2").val();
			$.ajax({
				dataType: "json",
				url: "<?= base_url() ?>/transaction/get-marketplace-fee",
				type: "POST",
				data: {
					id: '<?= $data['id'] ?>',
					id_marketplace: id_marketplace,
					date: date,
					total_2: total_2
				},
				success: function(response) {
					console.log(response);
					$("#marketplace_fee").val(response.html);
				}
			});
			$("#product").on("select2:close", function() {
				$(this).val(null).trigger("change");
			});
		}
	});

	$('#discount').keyup(function() {
		get_total_2();
	});

	$('#shipping_fee').keyup(function() {
		get_total_2();
	});


	$('#packing_price').keyup(function() {
		get_total_2();
	});
	$('#other_price').keyup(function() {
		get_total_2();
	});


	$('#marketplace_fee').keyup(function() {
		get_grand_total();
	});
	$('#return_fee').keyup(function() {
		get_grand_total();
	});
	$('#payment_paid').keyup(function() {
		get_grand_total();
	});

	function get_total_2() {
		var val = parseFloat($('#total_1').val()) - parseFloat($('#discount').val()) + parseFloat($('#shipping_fee').val()) + parseFloat($('#packing_price').val()) + parseFloat($('#other_price').val());
		$("#total_2").val(val);
		get_grand_total();
	}

	function get_grand_total() {
		var val = parseFloat($('#total_2').val()) - parseFloat($('#marketplace_fee').val()) - parseFloat($('#return_fee').val());
	}
</script>