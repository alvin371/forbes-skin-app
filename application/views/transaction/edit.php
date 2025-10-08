<?php
$user = $_SESSION['user'];
?>
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
	<div class="col-lg-11 mb-3">
		<h3 class="text-primary fw-600">EDIT ORDER</h3>
	</div>

	<div class="col-lg-1 mb-3">
		<a href="javascript:void(0)" onclick="confirmDelete('<?= $_GET['id'] ?>')" class="btn btn-danger">Kembali</a>

		<script>
			function confirmDelete(id) {
				Swal.fire({
					title: 'Konfirmasi',
					text: 'Apakah Anda yakin akan menghapus data order?',
					icon: 'warning',
					showCancelButton: true,
					confirmButtonColor: '#3085d6',
					cancelButtonColor: '#d33',
					confirmButtonText: 'Ya, Hapus!',
					cancelButtonText: 'Tidak'
				}).then((result) => {
					if (result.isConfirmed) {
						deleteTransaction(id);
					} else {
						window.location.href = "<?= base_url() ?>transaction";
					}
				});
			}

			function deleteTransaction(id) {
				var form = document.createElement("form");
				form.method = "POST";
				form.action = "<?= base_url() ?>transaction/delete";

				var hiddenField = document.createElement("input");
				hiddenField.type = "hidden";
				hiddenField.name = "id";
				hiddenField.value = id;
				form.appendChild(hiddenField);

				document.body.appendChild(form);
				form.submit();
			}
		</script>


	</div>
</div>
<style>
	.input-with-button {
		position: relative;
		display: flex;
		align-items: center;
	}

	.input-with-button input {
		padding-right: 80px;
		/* Adjust based on button width */
	}

	.input-with-button .btn {
		position: absolute;
		right: 0;
		top: 30%;
		transform: translateY(-30%);
		border-top-left-radius: 0;
		border-bottom-left-radius: 0;
		display: flex;
		align-items: center;
		justify-content: center;
	}
</style>
<div class="form-message"></div>
<form action="<?= base_url() ?>/transaction/update" method="POST" id="form-modal" enctype="multipart/form-data">
	<input type="hidden" name="id" value="<?= $data['id'] ?>">
	<input type="hidden" name="dt[order_id]" value="<?= $data['order_id'] ?>">

	<div class="row">
		<div class="col-lg-5">
			<div class="card mb-3">
				<label for="customerSearch" class="form-label">Nama Pemesan</label>
				<div class="input-with-button">
					<input type="hidden" name="existing_customer" value="<?= $data['customer'] ?>">
					<select type="text" class="form-control" name="dt[customer]" id="pelanggan">
						<option value="<?= $data['customer'] ?>"><?= $data['customer_text'] ?></option>
					</select>
					<script>
						$(function() {
							$('#pelanggan').select2({
								minimumInputLength: 1,
								allowClear: false,
								placeholder: '',
								minimumResultsForSearch: Infinity,
								ajax: {
									dataType: 'json',
									url: '<?= base_url() ?>/ajax/get-customer-list',
									delay: 100,
									data: function(params) {
										return {
											search: params.term
										};
									},
									processResults: function(data) {
										return {
											results: data
										};
									}
								},
								language: {
									inputTooShort: function() {
										return "Masukkan 1 karakter atau lebih";
									}
								}
							}).on('select2:select', function(evt) {
								var id = $(this).val();
								$.ajax({
									dataType: "json",
									url: '<?= base_url() ?>/ajax/get-customer-detail?id=' + id + '&id_trx=<?= $data['id'] ?>',
									success: function(html) {
										$("#full_name").val(html.full_name);
										$("#phone").val(html.phone);
										$("#address").val(html.address);
										$("#province_text").val(html.province_text);
										$("#city_text").val(html.city_text);
										$("#subdistrict_text").val(html.subdistrict_text);

										function displayField(fieldId, value) {
											if (value && value !== "0") {
												$(fieldId).text(value).parent().show();
											} else {
												$(fieldId).parent().hide();
											}
										}

										displayField("#display_name", html.full_name);
										displayField("#display_phone", html.phone);
										displayField("#display_address", html.address);
										displayField("#display_province", html.province_text);
										displayField("#display_city", html.city_text);
										displayField("#display_subdistrict", html.subdistrict_text);

										$("#customerData").show();
										$('#pelanggan').parent().hide();
										$('label[for="customerSearch"]').hide();
									}
								});
							});
						});

						function showSelectOption() {
							$('#pelanggan').parent().show();
							$('label[for="customerSearch"]').show();
						}
					</script>

					<button type="button" class="btn btn-outline-secondary me-2" style="height: 30px; font-size: 12px; background-color: #fff;" data-bs-toggle="modal" data-bs-target="#customerModal">+ Customer</button>
				</div>


				<div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalLabel" aria-hidden="true">
					<div class="modal-dialog modal-lg">
						<div class="modal-content">
							<div class="modal-header">
								<h5 class="modal-title" id="customerModalLabel">Input Customer</h5>
							</div>
							<div class="col-md-12 p-3">
								<div class="row">
									<div class="col-md-6 mt-2">
										<label for="">Nama Lengkap</label>
										<input type="text" class="form-control" name="dt[customer_text]" value="<?= !empty($data['customer_text']) ? $data['customer_text'] : '' ?>" id="full_name">
									</div>
									<div class="col-md-6 mt-2">
										<label for="">No. HP</label>
										<input type="text" class="form-control" name="dt[phone]" value="<?= (!empty($data['phone']) && $data['phone'] != '0') ? $data['phone'] : '' ?>" id="phone">
									</div>
								</div>

								<div class="row">
									<div class="col-md-4 mt-2">
										<label for="">Kecamatan</label>
										<input type="text" class="form-control" name="dt[subdistrict_text]" value="<?= (!empty($data['subdistrict_text']) && $data['subdistrict_text'] != '0') ? $data['subdistrict_text'] : '' ?>" id="subdistrict_text">
									</div>
									<div class="col-md-4 mt-2">
										<label for="">Kota</label>
										<input type="text" class="form-control" name="dt[city_text]" value="<?= (!empty($data['city_text']) && $data['city_text'] != '0') ? $data['city_text'] : '' ?>" id="city_text">
									</div>
									<div class="col-md-4 mt-2">
										<label for="">Provinsi</label>
										<input type="text" class="form-control" name="dt[province_text]" value="<?= (!empty($data['province_text']) && $data['province_text'] != '0') ? $data['province_text'] : '' ?>" id="province_text">
									</div>
								</div>
								<div class="row">
									<div class="col-md-12 mt-2">
										<label for="">Alamat Lengkap</label>
										<textarea class="form-control" name="dt[address]" id="address" style="min-height:100px"><?= (!empty($data['address']) && $data['address'] != '0') ? $data['address'] : '' ?></textarea>
									</div>
								</div>
								<button type="button" class="btn btn-primary" id="saveCustomer">Simpan</button>
							</div>

						</div>
					</div>
				</div>

				<div class="row" id="customerData" style="display: none;">
					<div class=" col-md-11">
						<div class="mt-3">
							<h4 class="fw-bold">Nama Pemesan</h4>
							<p><span id="display_name"></span></p>
							<p><span id="display_phone"></span></p>
							<p><span id="display_address"></span></p>
							<p><span id="display_province"></span></p>
							<p><span id="display_city"></span></p>
							<p><span id="display_subdistrict"></span></p>
							<p><span id="display_category"></span></p>
						</div>
					</div>
					<div class="col-md-1 my-3">
						<a href="#" onclick="showSelectOption()">Edit</a>
					</div>
				</div>

				<div class=" col-md-12">
					<label for="">Kategori</label>
					<select type="text" class="form-control select2" name="dt[c_type]" id="kategori">
						<option value=""></option>
						<?php
						$arr = array();
						$arr[] = "Pelanggan";
						$arr[] = "Reseller";
						$arr[] = "Distributor";
						$arr[] = "Affiliate";
						$arr[] = "Endorse";
						$arr[] = "Free";

						if ($data['c_type']) {
							if (!in_array($data['c_type'], $arr)) {
								$arr[] = $data['c_type'];
							}
						}

						foreach ($arr as $k2 => $v2) {
							$text = '';
							if ($data['c_type'] == $v2) {
								$text = 'selected';
							}
							elseif (empty($data['c_type']) && $v2 == "Pelanggan") {
								$text = 'selected';
							}
						?>
							<option <?= $text ?> value="<?= $v2 ?>"><?= $v2 ?></option>
						<?php } ?>
					</select>
				</div>
				<div class="col-md-12">
					<label for="">Tgl Order</label>
					<input type="datetime-local" class="form-control" name="dt[date]" value="<?= DATE("Y-m-d H:i", strtotime($data['date'])) ?>" id="date">
				</div>
				<div class="col-md-12">
					<label for="">Order Status</label>
					<select type="text" class="form-control" name="dt[order_status]" id="orderStatus">
						<?php
						$arr_val = array();
						// $arr_val[] = 'UNPAID';
						// $arr_val[] = 'READY_TO_SHIP';
						$arr_val[] = 'PROCESSED';
						$arr_val[] = 'SHIPPED';
						// $arr_val[] = 'DELIVERED';
						$arr_val[] = 'COMPLETED';
						$arr_val[] = 'CANCELLED';
						$arr_val[] = 'RETURN';
						// $arr_val[] = 'REFUND';
						// $arr_val[] = 'COMPLETED2';

						$arr = array();
						// $arr[] = 'Order Belum Bayar';
						// $arr[] = 'Order Siap Dikemas';
						$arr[] = 'Order Proses';
						$arr[] = 'Pengiriman';
						// $arr[] = 'Diterima';
						$arr[] = 'Selesai';
						$arr[] = 'Dibatalkan';
						$arr[] = 'Return';
						// $arr[] = 'Refund';
						// $arr[] = 'Dicairkan';

						foreach ($arr as $k2 => $v2) {
							$text = '';
							if ($data['is_disbursement'] != 1) {
								if (isset($data['order_status']) && $data['order_status'] == $arr_val[$k2]) {
									$text = 'selected';
								}
							} else {
								$text = 'selected';
							}
						?>
							<option <?= $text ?> value="<?= $arr_val[$k2] ?>"><?= $v2 ?></option>
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
					<!-- <input type="text" class="form-control" name="dt[cs]" id="cs" value="<?= isset($data['cs']) ? $data['cs'] : '' ?>" /> -->
				</div>
				<div class="col-md-12">
					<label for="">Note</label>
					<textarea class="form-control" name="dt[desc]" rows="6"><?= $data['desc'] ?></textarea>
				</div>

				<script>
					document.getElementById('saveCustomer').addEventListener('click', function(event) {
						event.preventDefault();

						// Kosongkan nilai tampilan sebelum diperbarui
						document.getElementById('display_name').textContent = '';
						document.getElementById('display_phone').textContent = '';
						document.getElementById('display_address').textContent = '';
						document.getElementById('display_province').textContent = '';
						document.getElementById('display_city').textContent = '';
						document.getElementById('display_subdistrict').textContent = '';
						document.getElementById('display_category').textContent = '';

						// Ambil data dari form
						var dt = {
							full_name: document.getElementById('full_name').value,
							phone: document.getElementById('phone').value,
							address: document.getElementById('address').value,
							province: document.getElementById('province_text').value,
							city: document.getElementById('city_text').value,
							subdistrict: document.getElementById('subdistrict_text').value,
						};

						// Pastikan elemen category ada sebelum mengambil nilainya
						var categoryElement = document.getElementById('category');
						if (categoryElement) {
							dt.category = categoryElement.value;
							document.getElementById('display_category').textContent = dt.category;
						}


						// Tampilkan data yang baru
						document.getElementById('display_name').textContent = dt.full_name;
						document.getElementById('display_phone').textContent = dt.phone;
						document.getElementById('display_address').textContent = dt.address;
						document.getElementById('display_province').textContent = dt.province;
						document.getElementById('display_city').textContent = dt.city;
						document.getElementById('display_subdistrict').textContent = dt.subdistrict;

						// Tampilkan section untuk menampilkan data
						document.getElementById('customerData').style.display = 'block';

						// Tutup modal dengan metode yang benar
						var modalElement = document.getElementById('customerModal');
						var modalInstance = bootstrap.Modal.getInstance(modalElement);
						if (modalInstance) {
							modalInstance.hide();
						}

						// Sembunyikan elemen lain tanpa jQuery
						var pelangganElement = document.getElementById('pelanggan');
						if (pelangganElement) {
							pelangganElement.parentElement.style.display = 'none';
						}

						var customerSearchLabel = document.querySelector('label[for="customerSearch"]');
						if (customerSearchLabel) {
							customerSearchLabel.style.display = 'none';
						}
					});
				</script>
			</div>
		</div>
		<div class="col-lg-7">
			<div class="card mb-3">
				<div class="col-md-12">
					<label for="">Produk</label>
					<select for="product-form" type="text" class="form-control select2" id="select2-product">
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
						<div class="d-flex gap-2 mb-2 ms-2">
							<button type="button" class="btn btn-outline-success btn-sm d-flex align-items-center justify-content-center" style="height: 30px;" data-bs-toggle="modal" data-bs-target="#diskonModal">+ Diskon</button>
							<button type="button" class="btn btn-outline-success btn-sm d-flex align-items-center justify-content-center" style="height: 30px;" data-bs-toggle="modal" data-bs-target="#ongkirModal">+ Ongkir</button>
						</div>
						<div class="p-2">
							<div class="col-md-12">
								<input type="hidden" id="total_1_input" name="dt[omset_kotor]" value="<?= $data['omset_kotor'] ?>">
								<div class="d-flex justify-content-between align-items-center">
									<label for="">Subtotal</label>
									<p id="total_1_text"></p>
								</div>
							</div>

							<div class="col-md-12 d-none">
								<input type="hidden" id="discount" name="dt[diskon_penjual]" value="<?= $data['diskon_penjual'] ?>">
								<div class="d-flex justify-content-between align-items-center">
									<label for="">Diskon</label>
									<p class="text-danger" id="discount_text"><?= $data['diskon_penjual'] ?></p>
								</div>
							</div>

							<div class="col-md-12 d-none">
								<div class="d-flex justify-content-between align-items-center">
									<label for="">Ongkir</label>
									<p id="shipping_fee_text"><?= $data['ongkir'] ?></p>
								</div>
							</div>
							<hr>
							<div class="col-md-12">
								<input type="hidden" id="total_2_input" name="dt[customer_price]" value="<?= $data['customer_price'] ?>">
								<div class="d-flex justify-content-between align-items-center">
									<h5 class="fw-bold">Total Bayar</h5>
									<h5 id="total_2_text"><?= $data['customer_price'] ?></h5>
								</div>
							</div>
							<hr>

							<div class="mb-3 mt-4">
								<div class="row">
									<div class="col-md-9 d-flex align-items-center">
										<h5 class="fw-bold mb-0">Pengiriman</h5>
									</div>
									<div class="col-md-3 d-flex align-items-center justify-content-end"> <!-- justify-content-end untuk mepet ke kanan -->
										<button type="button" class="btn btn-outline-secondary d-flex align-items-center justify-content-center" style="height: 30px; width: auto; font-size: 12px; background-color: #fff;" data-bs-toggle="modal" data-bs-target="#pengirimanModal">
											+ Pengiriman
										</button>
									</div>
								</div>
								<!-- Data Pengiriman -->
								<div class="row" id="pengirimanData" style="display: <?= (!empty($data['rts_at']) || !empty($data['shipping']) || !empty($data['awb_number']) ? 'block' : 'none') ?>;">
									<div class="col-md-11">
										<div class="mt-3">
											<p><span id="display_rts_at"><?= !empty($data['rts_at']) ? htmlspecialchars($data['rts_at']) : '' ?></span></p>
											<p><span id="display_shipping"><?= !empty($data['shipping']) ? htmlspecialchars($data['shipping']) : '' ?></span></p>
											<p><span id="display_awb_number"><?= !empty($data['awb_number']) ? htmlspecialchars($data['awb_number']) : '' ?></span></p>
										</div>
									</div>
									<div class="col-md-1 my-3">
										<a href="#" id="edit_pengiriman" onclick="editPengiriman()">Edit</a>
									</div>
								</div>
							</div>

							<script>
								function editPengiriman() {
									event.preventDefault();

									// Ambil data dari tampilan
									var rtsAt = document.getElementById('display_rts_at').textContent;
									var shipping = document.getElementById('display_shipping').textContent;
									var awbNumber = document.getElementById('display_awb_number').textContent;

									// Masukkan data ke dalam form
									document.getElementById('rts_at').value = rtsAt;
									document.getElementById('shipping').value = shipping;
									document.getElementById('awb_number').value = awbNumber;

									// Buka modal kembali
									var modalElement = document.getElementById('pengirimanModal');
									var modalInstance = new bootstrap.Modal(modalElement);
									modalInstance.show();
								}
							</script>

							<!-- <div class="col-md-12 d-flex justify-content-between align-items-center">
								<label for="">Dibayar</label>
								<p id="dibayar_text"><?= $data['dibayar'] ?></p>
							</div>
							<div class="col-md-12 d-none d-flex justify-content-between align-items-center">
								<label for="">Kembalian</label>
								<p class="bg-b" id="kembalian_text"><?= $data['kembalian'] ?></p>
							</div>
							<div class="col-md-12 d-flex justify-content-between align-items-center">
								<label for="">Return</label>
								<p class="bg-o" id="return_fee_text"><?= $data['return'] ?></p>
							</div> -->
						</div>

					</div>
				</div>

				<script>
					document.addEventListener("DOMContentLoaded", function() {
					let discountInput = document.getElementById("discount_nominal");
					let discountContainer = document.getElementById("discount_text").closest(".col-md-12");
					let discountText = document.getElementById("discount_text");

					let shippingInput = document.getElementById("ongkir");
					let shippingContainer = document.getElementById("shipping_fee_text").closest(".col-md-12");
					let shippingText = document.getElementById("shipping_fee_text");

					// Format awal jika ada nilai
					if (discountText.textContent.trim() !== "" && discountText.textContent.trim() !== "0") {
						discountText.textContent = formatRibuan(discountText.textContent.trim());
						discountContainer.classList.remove("d-none");
					}

					if (shippingText.textContent.trim() !== "" && shippingText.textContent.trim() !== "0") {
						shippingText.textContent = formatRibuan(shippingText.textContent.trim());
						shippingContainer.classList.remove("d-none");
					}

					discountInput.addEventListener("input", function() {
						const value = this.value.replace(/[^0-9]/g, ''); // Hanya ambil angka
						discountText.textContent = value ? formatRibuan(value) : "0";
						discountContainer.classList.remove("d-none");
					});

					shippingInput.addEventListener("input", function() {
						const value = this.value.replace(/[^0-9]/g, ''); // Hanya ambil angka
						shippingText.textContent = value ? formatRibuan(value) : "0";
						shippingContainer.classList.remove("d-none");
					});

					// Helper function to format number with thousand separators
					function formatRibuan(number) {
						if (!number || number === "0") return '0';
						// Pastikan number adalah string dan hanya berisi angka
						const numStr = number.toString().replace(/[^0-9]/g, '');
						return numStr.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
					}
				});
				</script>

				<div class="modal fade" id="pengirimanModal" tabindex="-1" aria-labelledby="pengirimanModalLabel" aria-hidden="true">
					<div class="modal-dialog modal-md">
						<div class="modal-content">
							<div class="modal-header">
								<h5 class="modal-title" id="pengirimanModalLabel">Input Pengiriman</h5>
							</div>
							<div class="col-md-12 p-3">
								<div class="row">
									<div class="col-md-12 mt-2">
										<label for="">Ekspedisi</label>
										<select class="form-control select2" name="dt[shipping]" id="shipping">
											<option value="<?= $data['shipping'] ?>"><?= $data['shipping'] ?></option>
											<?php foreach ($shipping as $v2) { ?>
												<option value="<?= $v2['name'] ?>" <?= $data['shipping'] == $v2['name'] ? 'selected' : '' ?>><?= $v2['name'] ?></option>
											<?php } ?>
										</select>
									</div>
									<div class="col-md-12 mt-2">
										<label for="">No Resi</label>
										<input type="text" class="form-control" name="dt[awb_number]" id="awb_number" value="<?= $data['awb_number'] ?>">
									</div>
									<div class="col-md-12 mt-2">
										<label for="">Tgl RTS</label>
										<input type="datetime-local" class="form-control" name="dt[rts_at]" id="rts_at" value="<?= $data['rts_at'] ?>">
									</div>
								</div>
								<button type="button" class="btn btn-primary mt-3" id="savePengiriman">Simpan</button>
							</div>
						</div>
					</div>
				</div>

				<script>
					document.getElementById('savePengiriman').addEventListener('click', function(event) {
						event.preventDefault();

						document.getElementById('display_rts_at').textContent = '';
						document.getElementById('display_shipping').textContent = '';
						document.getElementById('display_awb_number').textContent = '';

						var dt = {
							rts_at: document.getElementById('rts_at').value,
							shipping: document.getElementById('shipping').value,
							awb_number: document.getElementById('awb_number').value
						};

						document.getElementById('display_rts_at').textContent = dt.rts_at;
						document.getElementById('display_shipping').textContent = dt.shipping;
						document.getElementById('display_awb_number').textContent = dt.awb_number;

						document.getElementById('pengirimanData').style.display = 'block';

						var modalElement = document.getElementById('pengirimanModal');
						var modalInstance = bootstrap.Modal.getInstance(modalElement);
						if (modalInstance) {
							modalInstance.hide();
						}
					});
				</script>

				<div class="modal fade" id="diskonModal" tabindex="-1" aria-labelledby="diskonModalLabel" aria-hidden="true">
					<div class="modal-dialog modal-md">
						<div class="modal-content">
							<div class="modal-header">
								<h5 class="modal-title" id="diskonModalLabel">Tambah Diskon</h5>
							</div>
							<div class="col-md-12 p-3">
								<div class="row">
									<div class="col-md-12">
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
									<div class="col-md-12">
										<label for="">Nominal</label>
										<input type="text" class="form-control" name="dt[discount_nominal]" value="<?= $data['discount_nominal'] ?>" id="discount_nominal">
									</div>
									<div class="col-md-1 text-end">
										<button type="button" class="btn btn-primary" id="saveDiskon" data-bs-dismiss="modal">Simpan</button>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="modal fade" id="ongkirModal" tabindex="-1" aria-labelledby="ongkirModalLabel" aria-hidden="true">
					<div class="modal-dialog modal-md">
						<div class="modal-content">
							<div class="modal-header">
								<h5 class="modal-title" id="ongkirModalLabel">Tambah Ongkir</h5>
							</div>
							<div class="col-md-12 p-3">
								<div class="col-md-12">
									<label for="">Ongkir</label>
									<input type="text" class="form-control" value="<?= $data['ongkir'] ?>" id="ongkir">
									<input type="hidden" name="dt[ongkir]" value="<?= $data['ongkir'] ?>" id="ongkir_input">
								</div>
								<div class="col-md-1">
									<button type="button" class="btn btn-primary" id="saveOngkir" data-bs-dismiss="modal">Simpan</button>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="card mb-3" id="pembayaran">
				<h4 class="fw-bold">Pembayaran</h4>
				<div class="col-md-12" id="tipe_pembayaran">
					<label for="">Tipe Pembayaran</label>
					<select type="text" class="form-control" name="dt[payment_type]" id="payment_type" onchange="updatePaymentStatus()">
						<?php
						$arr = ["", "TF", "COD"];
						foreach ($arr as $v2) {
							$text = ($data['payment_type'] == $v2) ? 'selected' : '';
						?>
							<option <?= $text ?> value="<?= $v2 ?>"><?= $v2 ?></option>
						<?php } ?>
					</select>
				</div>
				<div class="col-md-12" id="status_pembayaran">
					<label for="">Status Pembayaran</label>
					<select type="text" class="form-control" name="dt[payment_status]" id="payment_status" onchange="updateVisibility()">
						<?php
						$arr_val = ["Paid", "Unpaid"];
						$arr = ["Sudah Dibayar", "Belum Dibayar"];

						foreach ($arr as $k2 => $v2) {
							$text = ($selectedStatus == $arr_val[$k2]) ? 'selected' : '';
						?>
							<option <?= $text ?> value="<?= $arr_val[$k2] ?>"><?= $v2 ?></option>
						<?php } ?>
					</select>
				</div>

				<div id="payment_details" style="display: block;">
					<div class="col-md-12" id="tanggal_pembayaran">
						<label for="">Tanggal Pembayaran</label>
						<input type="date" class="form-control" name="dt[pay_at]" id="pay_at"
							value="<?= $data['pay_at'] ?>">
					</div>
					<div class="col-md-12" id="bank">
						<label for="">Bank</label>
						<select type="text" class="form-control" name="dt[bank]">
							<?php
							$arr = ["", "BCA", "BNI", "BRI", "MAN"];
							foreach ($arr as $v2) {
								$text = ($data['bank'] == $v2) ? 'selected' : '';
							?>
								<option <?= $text ?> value="<?= $v2 ?>"><?= $v2 ?></option>
							<?php } ?>
						</select>
					</div>
					<div class="col-md-12" id="nominal_tf">
						<label for="">Nominal TF</label>
						<input type="text" class="form-control" name="dt[dibayar]" value="<?= $data['dibayar'] ?>" id="dibayar">
					</div>
				</div>
			</div>

			<div class="col-lg-1 mb-3">
				<button type="submit" class="btn btn-primary btn-send mt-3">Simpan</button>
			</div>

			<script>
				document.addEventListener("DOMContentLoaded", function() {
					document.getElementById("payment_type").value = "TF";
					updateVisibility(); // Panggil ini untuk inisialisasi tampilan
				});

				function updatePaymentStatus() {
					var paymentType = document.getElementById("payment_type");
					var paymentStatus = document.getElementById("payment_status");
					updateVisibility();
				}

				function updateVisibility() {
					let paymentStatus = document.getElementById("payment_status");
					let paymentDetails = document.getElementById("payment_details");

					if (paymentStatus.value === "Paid") {
						paymentDetails.style.display = "block";
					} else {
						paymentDetails.style.display = "none";
						document.getElementById("pay_at").value = "";
					}
				}

				document.getElementById("payment_status").addEventListener("change", updateVisibility);
			</script>

		</div>
	</div>
</form>

<script type="text/javascript">
	$("#form-modal").submit(function() {
		var form = $(this);
		var mydata = new FormData(this);
		console.log(mydata);
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
				if (str.indexOf("success") != -1) {
					$(".form-message").hide().html(response).slideDown("fast");
					setTimeout(function() {
						window.location.href = "<?= base_url() ?>transaction";
						$(".btn-send").removeClass("disabled").html('Simpan').attr('disabled', false);
					}, 2500);
				} else {
					$(".form-message").hide().html(response).slideDown("fast");
					$(".btn-send").removeClass("disabled").html('Simpan').attr('disabled', false);
				}
			},
			error: function(xhr, textStatus, errorThrown) {
				$(".btn-send").removeClass("disabled").html('Simpan').attr('disabled', false);
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
		const currentStatus = "<?= $data['order_status'] == 'UNPAID' ? '' : $data['order_status'] ?>";

		if (!currentStatus) {
			$("#orderStatus").val("SHIPPED");
		}

		$("#select2-product").off("select2:select").on("select2:select", function(e) {
			var val = e.params.data.id;
			var product = val;
			var customer = $("#kategori").val();
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
			
			$("#select2-product").on("select2:close", function() {
				$(this).val(null).trigger("change");
			});
		});

		$("#kategori").on("change", function() {
			var customer = $(this).val();
			var id = '<?= $data['id'] ?>';
			var product = $("#select2-product").val();

			if (product) {
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
			}
		});

		function get_cart() {
			$("#notif").hide();
			$.ajax({
				dataType: "json",
				url: '<?= base_url() ?>/transaction/get-cart?id=<?= $data['id'] ?>',
				success: function(html) {
					$("#tbody").html(html.html);

					$("#total").html(formatRibuan(html.total));
					$("#total_1_input").val(html.total);
					$("#total_1_text").text(formatRibuan(html.total));

					get_total_2();
				}
			});
		}

		// Helper function to format number with thousand separators
		function formatRibuan(number) {
			if (number === null || number === undefined) return '0';
			return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
		}

		// If you need to parse the formatted number back to original
		function parseRibuan(formattedNumber) {
			if (!formattedNumber) return 0;
			return parseInt(formattedNumber.replace(/\./g, ''), 10);
		}

		get_cart();

		$("#marketplace").off("select2:select").on("select2:select", function(e) {
			// get_marketplace_fee();
		});

		$('#discount_type').change(function() {
			get_diskon();
		});

		$('#discount_nominal').keyup(function() {
			var inputVal = $(this).val().replace(/\D/g, '');
			if (inputVal !== "") {
				$(this).val(formatRibuan(inputVal));
			} else {
				$(this).val("");
			}
			get_diskon();
		});

		function formatRibuan(angka) {
			return angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
		}


		function hapusFormatRibuan(angka) {
			return angka.replace(/\./g, '');
		}

		$('#dibayar').keyup(function() {
			var dibayar = parseFloat($(this).val()) || 0;
			$('#dibayar_text').text(dibayar);
			get_kembalian();
		});

		function get_diskon() {
			var discount_type = $('#discount_type').val();
			var discountFormatted = $('#discount_nominal').val();
			var discount_nominal = parseFloat(discountFormatted.replace(/[^0-9]/g, "")) || 0;

			var total_1 = parseFloat($('#total_1_input').val()) || 0;

			if (discount_type == "Nominal") {
				$('#discount').val(discount_nominal);
				$('#discount_text').text(formatRibuan(discount_nominal));
			} else {
				var persenDiskon = (total_1 * discount_nominal / 100) || 0;
				$('#discount_text').text(persenDiskon);
				$('#discount').val(persenDiskon);
			}

			get_total_2();
		}

		$('#ongkir').keyup(function() {
			var ongkir = $(this).val().replace(/\D/g, '');
			if (ongkir !== "") {
				$(this).val(formatRibuan(ongkir));
				$('#shipping_fee_text').text(formatRibuan(ongkir));
				$('#ongkir_input').val(ongkir);
				get_total_2();
			} else {
				$(this).val("");
				$('#shipping_fee_text').text("0");
			}
		});

		function get_kembalian() {
			var total_2 = parseFloat($('#total_2_text').text());
			var dibayar = parseFloat($('#dibayar_text').text());
			var val = (dibayar - total_2);
			$("#kembalian_text").text(val);
		}

		$('#discount_code').keyup(function() {
			get_discount();
		});

		// function get_diskon() {
		// 	var discount_type = $('#discount_type').val();
		// 	var discount_nominal = parseFloat($('#discount_nominal').val());
		// 	var total_1 = parseFloat($('#total_1_text').text());
		// 	if (discount_type == "Nominal") {
		// 		$('#discount_text').text(discount_nominal);
		// 		$('#discount').val(discount_nominal);
		// 	} else {
		// 		discount_nominal = (total_1 * discount_nominal / 100);
		// 		$('#discount_text').text(discount_nominal);
		// 		$('#discount').val(discount_nominal);
		// 	}
		// 	get_total_2();
		// }

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
					$("#marketplace_fee").val(response.html);
				}
			});
			$("#select2-product").on("select2:close", function() {
				$(this).val(null).trigger("change");
			});
		}

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

		// // Observe changes in #total_1_text and call get_total_2()
		// const total1Observer = new MutationObserver(() => {
		// 	get_total_2();
		// });

		// total1Observer.observe(document.getElementById('total_1_text'), {
		// 	childList: true,
		// 	subtree: true,
		// 	characterData: true
		// });

		const total1Text = document.getElementById('total_1_text');
		const total1Input = document.getElementById('total_1_input');

		function updateTotal1Input() {
			total1Input.value = hapusFormatRibuan(total1Text.textContent);
		}

		const total1Observer = new MutationObserver(() => {
			updateTotal1Input();
			get_total_2();
		});

		total1Observer.observe(total1Text, {
			childList: true,
			subtree: true,
			characterData: true
		});

		updateTotal1Input();


		var is_free = false;

		function get_total_2() {
			check_is_free();
			if (is_free) {
				var val = 0;
			} else {
				var total_1 = parseFloat($('#total_1_input').val()) || 0;
				var discount = parseFloat($('#discount').val()) || 0;
				var shipping_fee = parseFloat($('#ongkir_input').val()) || 0;
				var packing_price = parseFloat($('#packing_price').val()) || 0;
				var other_price = parseFloat($('#other_price').val()) || 0;

				var val = total_1 - discount + shipping_fee + packing_price + other_price;

				var formattedTotal2 = formatRibuan(val);

			}
			$("#total_2_text").text(formattedTotal2);
			$("#total_2_input").val(val);
			get_kembalian();
		}


		$('#kebutuhan').change(function() {
			get_total_2();
		});

		check_is_free();
		get_total_2();

		$('#return_fee').keyup(function() {
			var return_fee = parseFloat($(this).val()) || 0;
			$('#return_fee_text').text(return_fee);
		});

		function check_is_free() {
			var val = $("#kebutuhan").val();
			is_free = val ? true : false;
		}

		function togglePembayaranByKategori() {
		const v = ($('#kategori').val() || '').toString().trim().toLowerCase();
		const shouldHide = ['affiliate', 'free', 'endorse'].includes(v);

		if (shouldHide) {
			$('#pembayaran').slideUp(150);
			$('#payment_details').hide();
		} else {
			$('#pembayaran').slideDown(150);
			$('#payment_details').show();
		}
		}

		togglePembayaranByKategori();

		$('#kategori').on('change', togglePembayaranByKategori);
	});
</script>