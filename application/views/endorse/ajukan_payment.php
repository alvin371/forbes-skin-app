<!-- Tab Navigation -->
<ul class="nav nav-tabs mb-3" id="paymentTabs" role="tablist">
	<li class="nav-item" role="presentation">
        <button class="nav-link active" id="shipping-tab" data-bs-toggle="tab" data-bs-target="#shipping" type="button" role="tab">Pengiriman & Link MoU</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="payment-tab" data-bs-toggle="tab" data-bs-target="#payment" type="button" role="tab">Pengajuan Payment</button>
    </li>
</ul>

<!-- Tab Content -->
<div class="tab-content" id="paymentTabsContent">
    <!-- Tab 1: Pengiriman Barang -->
	<div class="tab-pane fade show active" id="shipping" role="tabpanel">
		<form action="<?= base_url('endorse/set_pengiriman_barang') ?>" method="POST" id="form-shipping" class="shipping-form">
			<input type="hidden" name="id" value="<?= $data[0]['id']  ?>">
			<input type="hidden" name="id_campaign" value="<?= $data[0]['id_campaign'] ?>">
			<input type="hidden" name="nama_creator" value="<?= $data[0]['nama_creator'] ?>">

			<div class="col-md-12 mb-3">
				<label for="link_mou_shipping">Link MOU</label>
				<input type="text" class="form-control" name="link_mou" id="link_mou_shipping" value="<?= $data[0]['link_mou'] ?? '' ?>">
			</div>

			<div class="col-md-12 mb-3">
				<label for="jenis_pengiriman">Jenis Pengiriman</label>
				<select class="form-control" name="jenis_pengiriman" id="jenis_pengiriman">
					<option value="Endorse" <?= ($data[0]['jenis_pengiriman'] ?? '') == 'Endorse' ? 'selected' : '' ?>>Endorse</option>
					<option value="Affiliate" <?= ($data[0]['jenis_pengiriman'] ?? '') == 'Affiliate' ? 'selected' : '' ?>>Affiliate</option>
				</select>
			</div>

			<div class="col-md-12 mb-3">
				<label for="detail_pic">Detail PIC</label>
				<input type="text" class="form-control" name="detail_pic" id="detail_pic" 
					value="<?= $data[0]['detail_pic'] ?? '' ?>" placeholder="KOL/PIC">
			</div>

			<!-- Section untuk memilih konten seperti di bundling -->
			<div class="col-md-12 mb-3">
				<label class="mb-2 d-block">Pilih Konten untuk Pengiriman</label>
				<div class="form-check mb-2">
					<input class="form-check-input" type="checkbox" id="select_all_shipping">
					<label class="form-check-label" for="select_all_shipping">Pilih Semua</label>
				</div>
				<div id="shipping_candidates" class="row g-2"></div>
				<input type="hidden" name="shipping_ids" id="shipping_ids" value="">
			</div>

			<div class="col-md-12 mb-5">
				<div class="d-flex justify-content-between align-items-center mb-2">
					<label class="mb-0">Detail Produk</label>
					<button type="button" class="btn btn-sm btn-outline-primary" id="btn-tambah-produk" style="height:30px !important; padding: 4px 6px !important;">
						+ Produk
					</button>
				</div>
				<div id="product-qty-wrapper">
					<!-- Produk akan di-render secara dinamis berdasarkan campaign yang terpilih -->
				</div>
			</div>

			<!-- Tombol Aksi -->
			<div class="col-md-12 mt-3 d-flex gap-2">
				<button type="submit" class="btn btn-primary btn-send-shipping">Update Link MOU</button>
				<button type="button" class="btn btn-success" id="btn-send-notif">
					Kirim Notifikasi Telegram
				</button>
			</div>
		</form>
	</div>

    <!-- Tab 2: Pengajuan Payment -->
    <div class="tab-pane fade" id="payment" role="tabpanel">
        <form action="<?= base_url('endorse/set_pengajuan_payment') ?>" method="POST" id="form-payment" class="payment-form">
            <input type="hidden" name="id" value="<?= $data[0]['id']  ?>">
            <input type="hidden" name="id_campaign" value="<?= $data[0]['id_campaign'] ?>">
            <input type="hidden" name="nama_creator" value="<?= $data[0]['nama_creator'] ?>">
            <input type="hidden" name="status_endorse" value="<?= $data[0]['status_endorse'] ?>">
            <input type="hidden" name="status_payment" value="<?= $data[0]['status_payment'] ?>">

            <div class="col-md-12 mb-2">
                <?php
                $is_bundling_value = isset($data['data'][0]['is_payment_bundling'])
                    ? (int)$data['data'][0]['is_payment_bundling']
                    : (isset($data[0]['is_payment_bundling']) ? (int)$data[0]['is_payment_bundling'] : 1);
                ?>
                <input type="checkbox"
                    class="form-check-input"
                    name="is_payment_bundling"
                    id="is_payment_bundling"
                    value="1"
                    <?= $is_bundling_value == 1 ? 'checked' : 'checked' ?>>
                <label for="is_payment_bundling" class="ms-1">Ajukan Bundling</label>
            </div>

            <div class="col-md-12 mb-2">
                <label for="status_pengajuan_payment">Status Payment</label>
                <select class="form-control" name="status_pengajuan_payment" id="status_pengajuan_payment">
                    <?php
                    $arr = ["", "DP", "FP"];
                    $selected_status = isset($data['data'][0]['status_pengajuan_payment']) && $data['data'][0]['status_pengajuan_payment'] !== ''
                        ? $data['data'][0]['status_pengajuan_payment']
                        : (isset($data[0]['status_pengajuan_payment']) && $data[0]['status_pengajuan_payment'] !== '' ? $data[0]['status_pengajuan_payment'] : 'FP');
                    foreach ($arr as $v2) {
                        $selected = ($selected_status == $v2) ? 'selected' : '';
                        echo "<option value=\"$v2\" $selected>$v2</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="col-md-12 mb-2">
                <label for="nominal_pengajuan_formatted">Nominal Pengajuan Payment</label>
                <?php
                    $nominal_pengajuan_val = isset($data['data'][0]['nominal_pengajuan'])
                        ? (int)$data['data'][0]['nominal_pengajuan']
                        : (isset($data[0]['nominal_pengajuan']) ? (int)$data[0]['nominal_pengajuan'] : 0);
                ?>
                <input type="text" class="form-control" name="nominal_pengajuan_formatted" id="nominal_pengajuan_formatted" value="<?= number_format($nominal_pengajuan_val, 0, ',', '.') ?>" required>
                <input type="hidden" name="nominal_pengajuan" id="nominal_pengajuan" value="<?= $nominal_pengajuan_val ?>">
            </div>

            <div class="col-md-12 mb-2">
                <label for="bank">Bank</label>
                <input type="text" class="form-control" name="bank" id="bank" value="<?= $data[0]['bank'] ?>">
            </div>

            <div class="col-md-12 mb-2">
                <label for="no_rekening">No Rekening</label>
                <input type="text" class="form-control" name="no_rekening" id="no_rekening" value="<?= $data[0]['no_rekening'] ?>">
            </div>

            <div class="col-md-12 mb-2">
                <label for="pemilik_rekening">Pemilik Rekening</label>
                <input type="text" class="form-control" name="pemilik_rekening" id="pemilik_rekening" value="<?= $data[0]['pemilik_rekening'] ?>">
            </div>

            <div class="col-md-12 mt-3" id="bundling_section" style="display:none;">
                <label class="mb-2 d-block">Item yang dibundling</label>
                <div id="bundling_cards" class="row g-2"></div>
                <input type="hidden" name="bundling_ids" id="bundling_ids" value="">
            </div>

            <div class="col-md-12 mb-3">
                <label for="keterangan_payment">Keterangan Payment</label>
                <textarea class="form-control" name="keterangan_payment" id="keterangan_payment" rows="3" style="width: 100%; resize: vertical; min-height: 80px;"><?= $data[0]['keterangan_payment'] ?></textarea>
            </div>

            <div class="col-md-12 mt-3">
                <button type="submit" class="btn btn-primary btn-send-payment">Simpan Data Payment</button>
            </div>
        </form>
    </div>

    
</div>

<style>
    .bundle-card { border: 1px solid #e5e7eb; border-radius: 12px; background:#fff; padding: 12px; position: relative; box-shadow: 0 2px 8px rgba(0,0,0,.04); }
    .bundle-close { position: absolute; top: 6px; right: 10px; cursor: pointer; font-weight: 700; }
    .bundle-meta { font-size: 12px; color:#6b7280; }
    .bundle-title { font-weight: 600; }
    .bundle-badge { font-size: 11px; padding: 2px 8px; border-radius: 999px; background:#f3f4f6; }
    .bundle-card.disabled { opacity: .45; pointer-events: none; }

	.produk-nama[readonly] {
		background-color: #f8f9fa !important;
		border: 1px dashed #dee2e6 !important;
		cursor: not-allowed;
	}

	.btn-hapus-produk:disabled {
		opacity: 0.3;
		cursor: not-allowed;
	}

	.produk-row {
		padding: 8px;
		border: 1px solid #e9ecef;
		border-radius: 6px;
		background: #fff;
		margin-bottom: 8px;
	}

	.badge.bg-primary {
		background-color: #007bff !important;
		color: white !important;
		padding: 4px 8px;
	}

	.btn-hapus-produk:disabled {
		opacity: 0.3;
		cursor: not-allowed;
	}
    
    .shipping-card { 
        border: 2px solid #e5e7eb; 
        border-radius: 8px; 
        background: #f8f9fa; 
        padding: 12px; 
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .shipping-card:hover {
        border: 2px solid #a2cfffff; 
        border-radius: 8px; 
        background: #f8f9fa; 
        padding: 12px; 
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .shipping-card.selected {
        border: 2px solid #a2cfffff; 
        border-radius: 8px; 
        background: #f8f9fa; 
        padding: 12px; 
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .shipping-card.selected .shipping-badge {
        border-color: #a2cfffff;
        background: #a2cfffff;
        color: white;
    }
    
    .produk-row .badge {
        padding: 0.25rem 0.5rem;
        line-height: 1.2;
        vertical-align: middle;
        font-size: 13px;
    }
    .produk-row input[type="number"] {
        height: calc(1.5em + 0.5rem + 2px);
    }
    
    .loading-ellipsis {
        display: inline-block;
        position: relative;
        width: 80px;
        height: 20px;
    }
    
    .loading-ellipsis div {
        position: absolute;
        top: 8px;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #fff;
        animation-timing-function: cubic-bezier(0, 1, 1, 0);
    }
    
    .loading-ellipsis div:nth-child(1) {
        left: 8px;
        animation: loading-ellipsis1 0.6s infinite;
    }
    
    .loading-ellipsis div:nth-child(2) {
        left: 8px;
        animation: loading-ellipsis2 0.6s infinite;
    }
    
    .loading-ellipsis div:nth-child(3) {
        left: 32px;
        animation: loading-ellipsis2 0.6s infinite;
    }
    
    .loading-ellipsis div:nth-child(4) {
        left: 56px;
        animation: loading-ellipsis3 0.6s infinite;
    }
    
    @keyframes loading-ellipsis1 {
        0% { transform: scale(0); }
        100% { transform: scale(1); }
    }
    
    @keyframes loading-ellipsis3 {
        0% { transform: scale(1); }
        100% { transform: scale(0); }
    }
    
    @keyframes loading-ellipsis2 {
        0% { transform: translate(0, 0); }
        100% { transform: translate(24px, 0); }
    }
</style>

<script type="text/javascript">
	// ========== SHIPPING FORM ==========
	(() => {
		const formShipping = document.getElementById('form-shipping');
		if (!formShipping) return;

		const shippingCandidatesWrap = formShipping.querySelector('#shipping_candidates');
		const shippingIdsInput = formShipping.querySelector('#shipping_ids');
		const productQtyWrapper = formShipping.querySelector('#product-qty-wrapper');
		const selectAllCheckbox = formShipping.querySelector('#select_all_shipping');
		const btnTambahProduk = document.getElementById('btn-tambah-produk');

		let availableProducts = [];
		let isInitialLoad = true;
		let productCounter = 0;

		function initializeShippingData() {
			if (isInitialLoad) {
				loadShippingCandidates();
				isInitialLoad = false;
			}
		}

		initializeShippingData();

		document.getElementById('shipping-tab').addEventListener('click', function() {
			loadShippingCandidates();
		});

		btnTambahProduk.addEventListener('click', function() {
			tambahProdukManual();
		});

		if (selectAllCheckbox) {
			selectAllCheckbox.addEventListener('change', function() {
				const cards = shippingCandidatesWrap.querySelectorAll('.shipping-card');
				cards.forEach(card => {
					if (this.checked) {
						card.classList.add('selected');
					} else {
						card.classList.remove('selected');
					}
				});
				updateShippingIds();
				updateProductList();
			});
		}

		function loadShippingCandidates() {
			const idCampaign  = formShipping.querySelector('input[name="id_campaign"]').value;
			const namaCreator = formShipping.querySelector('input[name="nama_creator"]').value;

			$.ajax({
				url: "<?= base_url('endorse/api_bundling_candidates') ?>",
				type: "GET",
				dataType: "json",
				data: { id_campaign: idCampaign, nama_creator: namaCreator },
				beforeSend: () => { 
					shippingCandidatesWrap.innerHTML = '<div class="col-12 text-muted">Memuat kandidat pengiriman...</div>'; 
				},
				success: (res) => {
					if (!res || !Array.isArray(res.data) || res.data.length === 0) {
						shippingCandidatesWrap.innerHTML = '<div class="col-12 text-warning">Tidak ditemukan kandidat pengiriman.</div>';
						shippingIdsInput.value = '';
						return;
					}
					renderShippingCandidates(res.data);
					extractProductsFromCampaigns(res.data);
				},
				error: () => { 
					shippingCandidatesWrap.innerHTML = '<div class="col-12 text-danger">Gagal memuat data pengiriman.</div>'; 
				}
			});
		}

		function renderShippingCandidates(items) {
			shippingCandidatesWrap.innerHTML = '';

			items.forEach((it) => {
				const col = document.createElement('div');
				col.className = 'col-md-6 mb-2';

				const card = document.createElement('div');
				card.className = 'shipping-card';
				card.dataset.shippingId = it.id;
				card.dataset.products = it.product_text || '';
				
				card.innerHTML = `
					<div class="d-flex justify-content-between align-items-start">
						<div class="flex-grow-1">
							<div class="fw-bold">${it.nama_creator || 'Tanpa Nama'}</div>
							<div class="text-muted small">${it.status_endorse || '-'}</div>
							<div class="mt-1 small">${it.product_text || '-'}</div>
						</div>
					</div>
				`;

				card.addEventListener('click', function() {
					this.classList.toggle('selected');
					updateShippingIds();
					updateProductList();
					if (selectAllCheckbox) {
						selectAllCheckbox.checked = false;
					}
				});

				col.appendChild(card);
				shippingCandidatesWrap.appendChild(col);
			});

			updateShippingIds();
		}

		function extractProductsFromCampaigns(campaigns) {
			const allProducts = new Set();
			
			campaigns.forEach(campaign => {
				if (campaign.product_text) {
					const products = campaign.product_text.split(',');
					products.forEach(product => {
						const trimmedProduct = product.trim();
						if (trimmedProduct) {
							allProducts.add(trimmedProduct);
						}
					});
				}
			});
			
			availableProducts = Array.from(allProducts);
			updateProductList();
		}

		function updateProductList() {
			const selectedCards = shippingCandidatesWrap.querySelectorAll('.shipping-card.selected');
			
			if (selectedCards.length === 0) {
				renderProductQtyInputs([]);
			} else {
				const selectedProducts = new Set();
				
				selectedCards.forEach(card => {
					const productText = card.dataset.products;
					if (productText) {
						const products = productText.split(',');
						products.forEach(product => {
							const trimmedProduct = product.trim();
							if (trimmedProduct) {
								selectedProducts.add(trimmedProduct);
							}
						});
					}
				});
				
				renderProductQtyInputs(Array.from(selectedProducts));
			}
		}

		function renderProductQtyInputs(campaignProducts) {
			const existingManualProducts = getExistingManualProducts();
			
			productQtyWrapper.innerHTML = '';

			campaignProducts.forEach((productName) => {
				addProductRow(productName, 0, false); 
			});

			existingManualProducts.forEach(manualProduct => {
				addProductRow(manualProduct.nama, manualProduct.qty, true);
			});

			if (campaignProducts.length === 0 && existingManualProducts.length === 0) {
				productQtyWrapper.innerHTML = '<div class="text-muted">Belum ada produk. Klik "Tambah Produk" untuk menambahkan.</div>';
			}
		}

		function addProductRow(productName = '', qty = 0, isManual = true) {
			const rowId = `produk-${productCounter++}`;
			const row = document.createElement('div');
			row.className = 'd-flex align-items-center mb-2 produk-row';
			row.dataset.isManual = isManual;
			
			if (isManual) {
				row.innerHTML = `
					<div class="d-flex align-items-center gap-2 w-100">
						<!-- Nama Produk -->
						<div class="flex-grow-1">
							<input type="text" 
								class="form-control form-control-sm produk-nama" 
								placeholder="Nama Produk"
								value="${productName}"
								style="height:28px; font-size:13px; padding:2px 6px;">
						</div>

						<!-- Qty -->
						<div style="width:80px;">
							<input type="number" 
								class="form-control form-control-sm produk-qty" 
								min="0" 
								placeholder="Qty"
								value="${qty}"
								style="height:28px; font-size:13px; padding:2px 6px;">
						</div>

						<!-- Tombol hapus -->
						<div style="">
							<button type="button" 
								class="btn btn-outline-danger btn-sm btn-hapus-produk d-flex align-items-center justify-content-center"
								style="height:28px; width:28px; padding:0; font-size:16px; line-height:1; margin-bottom:8px;">
								–
							</button>
						</div>
					</div>
				`;
			} else {
				row.innerHTML = `
					<div class="d-flex align-items-center gap-2 w-100">
						<!-- Nama Produk (badge) -->
						<div class="flex-grow-1">
							<span class="badge bg-primary text-white fs-13" 
								style="padding: 4px 8px; font-size: 12px; display: inline-block; min-width: 80px; text-align: center;">
								${productName}
							</span>
							<input type="hidden" class="produk-nama-hidden" value="${productName}">
						</div>

						<!-- Qty -->
						<div style="width:80px;">
							<input type="number" 
								class="form-control form-control-sm produk-qty" 
								min="0" 
								placeholder="Qty"
								value="${qty}"
								style="height:28px; font-size:13px; padding:2px 6px;">
						</div>

						<!-- Tombol hapus (disabled) -->
						<div style="">
							<button type="button" 
								class="btn btn-outline-secondary btn-sm d-flex align-items-center justify-content-center"
								style="height:28px; width:28px; padding:0; font-size:16px; line-height:1; margin-bottom:8px;" 
								disabled>
								–
							</button>
						</div>
					</div>
				`;
			}
			
			const qtyInput = row.querySelector('.produk-qty');
			qtyInput.addEventListener('input', function() {
				if (this.value < 0) this.value = 0;
			});

			if (isManual) {
				const namaInput = row.querySelector('.produk-nama');
				const btnHapus = row.querySelector('.btn-hapus-produk');
				
				namaInput.addEventListener('input', function() {
					if (!this.value.trim()) {
						this.classList.add('is-invalid');
					} else {
						this.classList.remove('is-invalid');
					}
				});
				
				btnHapus.addEventListener('click', function() {
					row.remove();
					updateProductListDisplay();
				});
			}

			productQtyWrapper.appendChild(row);
			updateProductListDisplay();
		}

		function tambahProdukManual() {
			addProductRow('', 0, true);
		}

		function getExistingManualProducts() {
			const manualProducts = [];
			const rows = productQtyWrapper.querySelectorAll('.produk-row[data-is-manual="true"]');
			
			rows.forEach(row => {
				const namaInput = row.querySelector('.produk-nama');
				const qtyInput = row.querySelector('.produk-qty');
				if (namaInput && qtyInput) {
					manualProducts.push({
						nama: namaInput.value.trim(),
						qty: parseInt(qtyInput.value) || 0
					});
				}
			});
			
			return manualProducts;
		}

		function getAllProductsData() {
			const allProducts = {};
			
			// Ambil produk dari campaign (yang readonly)
			const campaignRows = productQtyWrapper.querySelectorAll('.produk-row[data-is-manual="false"]');
			campaignRows.forEach(row => {
				const hiddenInput = row.querySelector('.produk-nama-hidden');
				const qtyInput = row.querySelector('.produk-qty');
				if (hiddenInput && qtyInput) {
					const nama = hiddenInput.value.trim();
					const qty = parseInt(qtyInput.value) || 0;
					if (nama && qty > 0) {
						allProducts[nama] = {
							nama: nama,
							qty: qty
						};
					}
				}
			});
			
			// Ambil produk manual
			const manualRows = productQtyWrapper.querySelectorAll('.produk-row[data-is-manual="true"]');
			manualRows.forEach(row => {
				const namaInput = row.querySelector('.produk-nama');
				const qtyInput = row.querySelector('.produk-qty');
				if (namaInput && qtyInput) {
					const nama = namaInput.value.trim();
					const qty = parseInt(qtyInput.value) || 0;
					if (nama && qty > 0) {
						allProducts[nama] = {
							nama: nama,
							qty: qty
						};
					}
				}
			});
			
			return allProducts;
		}

		function updateProductListDisplay() {
			const rows = productQtyWrapper.querySelectorAll('.produk-row');
			if (rows.length === 0) {
				productQtyWrapper.innerHTML = '<div class="text-muted">Belum ada produk. Klik "Tambah Produk" untuk menambahkan.</div>';
			}
		}

		function updateShippingIds() {
			const selectedIds = Array.from(shippingCandidatesWrap.querySelectorAll('.shipping-card.selected'))
				.map(card => card.dataset.shippingId);
			shippingIdsInput.value = selectedIds.join(',');
		}

		// Submit form shipping - HANYA UPDATE LINK MOU
		$(formShipping).on('submit', function(e) {
			e.preventDefault();
			
			const selectedShippingIds = shippingIdsInput.value;
			if (!selectedShippingIds) {
				Swal.fire({ 
					title: "Peringatan!", 
					text: "Pilih minimal satu konten untuk diupdate link MOU", 
					icon: "warning" 
				});
				return;
			}

			const linkMou = formShipping.querySelector('#link_mou_shipping').value;
			if (!linkMou) {
				Swal.fire({ 
					title: "Peringatan!", 
					text: "Link MOU harus diisi", 
					icon: "warning" 
				});
				return;
			}

			const formData = new FormData(formShipping);
			submitShippingForm(formShipping, formData);
		});

		function submitShippingForm(form, formData) {
			$.ajax({
				url: form.action,
				type: "POST",
				data: formData,
				contentType: false,
				processData: false,
				beforeSend: () => {
					$(".btn-send-shipping", form).prop("disabled", true).html('<div class="loading-ellipsis"><div></div><div></div><div></div><div></div></div>');
				},
				success: (response) => {
					const ok = (typeof response === 'string' && response.includes("success")) ||
						(typeof response === 'object' && response.success);
					
					if (ok) {
						Swal.fire({ 
							title: "Berhasil!", 
							text: (response.message || "Link MOU berhasil diupdate! Lanjut ke pengajuan payment?"), 
							icon: "success",
							showCancelButton: true,
							confirmButtonText: 'Lanjut ke Payment',
							cancelButtonText: 'Tutup',
							confirmButtonColor: '#3085d6',
							cancelButtonColor: '#d33',
							reverseButtons: true
						}).then((result) => {
							if (result.isConfirmed) {
								const paymentTab = document.getElementById('payment-tab');
								if (paymentTab) {
									const tab = new bootstrap.Tab(paymentTab);
									tab.show();
								}
								loadShippingCandidates();
							} else {
								loadShippingCandidates(); 
								setTimeout(() => {
									window.location.reload(); 
								}, 500);
							}
						});
					} else {
						Swal.fire({ 
							title: "Error!", 
							html: (typeof response === 'string' ? response : (response.message || 'Terjadi kesalahan')), 
							icon: "error" 
						});
					}
					$(".btn-send-shipping", form).prop("disabled", false).text("Update Link MOU");
				},
				error: (xhr) => {
					Swal.fire({ 
						title: "Request Failed!", 
						text: xhr.responseText || "Terjadi kesalahan", 
						icon: "error" 
					});
					$(".btn-send-shipping", form).prop("disabled", false).text("Update Link MOU");
				}
			});
		}

		// Tombol Kirim Notifikasi Telegram
		document.getElementById('btn-send-notif').addEventListener('click', sendShippingNotification);

		function sendShippingNotification() {
			const selectedShippingIds = shippingIdsInput.value;
			if (!selectedShippingIds) {
				Swal.fire({ 
					title: "Peringatan!", 
					text: "Pilih minimal satu konten untuk dikirim", 
					icon: "warning" 
				});
				return;
			}

			const produkData = getAllProductsData();
			
			let hasQty = false;
			Object.values(produkData).forEach(product => {
				if (product.qty > 0) {
					hasQty = true;
				}
			});

			if (!hasQty) {
				Swal.fire({ 
					title: "Peringatan!", 
					text: "Harap isi quantity produk yang akan dikirim!", 
					icon: "warning" 
				});
				return;
			}

			const requestData = {
				nama_creator: formShipping.querySelector('input[name="nama_creator"]').value,
				ids_konten: selectedShippingIds,
				id_campaign: formShipping.querySelector('input[name="id_campaign"]').value,
				produk_data: JSON.stringify(produkData),
				jenis_pengiriman: formShipping.querySelector('#jenis_pengiriman').value,
				detail_pic: formShipping.querySelector('#detail_pic').value
			};

			const btn = document.getElementById('btn-send-notif');
			btn.classList.add("disabled");
			btn.innerHTML = '<div class="loading-ellipsis"><div></div><div></div><div></div><div></div></div>';
			btn.disabled = true;

			$.ajax({
				type: "POST",
				url: "<?= base_url('endorse/send_telegram') ?>",
				data: requestData,
				beforeSend: function() {
					btn.classList.add("disabled");
					btn.innerHTML = '<div class="loading-ellipsis"><div></div><div></div><div></div><div></div></div>';
					btn.disabled = true;
				},
				success: function(response) {
					btn.classList.remove("disabled");
					btn.innerHTML = 'Kirim Notifikasi Telegram';
					btn.disabled = false;
					
					try {
						const result = typeof response === 'string' ? JSON.parse(response) : response;
						
						if (result.success) {
							Swal.fire({ 
								title: "Berhasil!", 
								text: result.message, 
								icon: "success" 
							});
						} else {
							Swal.fire({ 
								title: "Error!", 
								text: result.message || 'Terjadi kesalahan!', 
								icon: "error" 
							});
						}
					} catch (e) {
						if (response.indexOf("success") !== -1) {
							Swal.fire({ 
								title: "Berhasil!", 
								text: "Notifikasi berhasil dikirim!", 
								icon: "success" 
							});
						} else {
							Swal.fire({ 
								title: "Error!", 
								text: response, 
								icon: "error" 
							});
						}
					}
				},
				error: function(xhr) {
					btn.classList.remove("disabled");
					btn.innerHTML = 'Kirim Notifikasi Telegram';
					btn.disabled = false;
					
					Swal.fire({ 
						title: "Error!", 
						text: "Terjadi kesalahan: " + (xhr.responseText || 'Tidak dapat terhubung ke server'), 
						icon: "error" 
					});
				}
			});
		}

		function formatRupiah(n) { 
			try { 
				return parseInt(n||0).toLocaleString('id-ID'); 
			} catch { 
				return n; 
			} 
		}
	})();

	(() => {
		const formPayment = document.getElementById('form-payment');
		if (!formPayment) return;

		const nominalInput      = formPayment.querySelector('#nominal_pengajuan_formatted');
		const nominalHidden     = formPayment.querySelector('#nominal_pengajuan');
		const bundlingCheckbox  = formPayment.querySelector('#is_payment_bundling');
		const bundlingSection   = formPayment.querySelector('#bundling_section');
		const bundlingCardsWrap = formPayment.querySelector('#bundling_cards');
		const bundlingIdsInput  = formPayment.querySelector('#bundling_ids');
		const statusSelect      = formPayment.querySelector('#status_pengajuan_payment');

		const formatRupiah   = (n) => { try { return parseInt(n||0).toLocaleString('id-ID'); } catch { return n; } };
		const unformatRupiah = (s) => (s||'').toString().replace(/[^0-9]/g, '');

		const initialNominalRaw   = nominalHidden.value;
		const initialNominalShown = nominalInput.value;

		nominalInput.addEventListener('input', () => {
			const v = unformatRupiah(nominalInput.value);
			nominalHidden.value = v;
			nominalInput.value  = v ? formatRupiah(v) : '';
		});

		if (statusSelect) {
			statusSelect.addEventListener('change', () => {
				if (bundlingCheckbox?.checked) recalcBundlingTotal();
			});
		}

		if (bundlingCheckbox) {
			bundlingCheckbox.addEventListener('change', () => {
				if (bundlingCheckbox.checked) enableBundlingAndFetch();
				else disableBundlingAndReset();
			});
		}

		if (bundlingCheckbox?.checked) enableBundlingAndFetch();
		else disableBundlingAndReset();

		function enableBundlingAndFetch() {
			bundlingSection.style.display = 'block';
			const idCampaign  = formPayment.querySelector('input[name="id_campaign"]').value;
			const namaCreator = formPayment.querySelector('input[name="nama_creator"]').value;

			$.ajax({
				url: "<?= base_url('endorse/api_bundling_candidates') ?>",
				type: "GET",
				dataType: "json",
				data: { id_campaign: idCampaign, nama_creator: namaCreator },
				beforeSend: () => { bundlingCardsWrap.innerHTML = '<div class="col-12 text-muted">Memuat kandidat bundling...</div>'; },
				success: (res) => {
					if (!res || !Array.isArray(res.data) || res.data.length === 0) {
						bundlingCardsWrap.innerHTML = '<div class="col-12 text-warning">Tidak ditemukan item bundling.</div>';
						bundlingIdsInput.value = '';
						nominalHidden.value = '0';
						nominalInput.value  = formatRupiah(0);
						return;
					}
					renderBundlingCards(res.data);
					recalcBundlingTotal();
				},
				error: () => { bundlingCardsWrap.innerHTML = '<div class="col-12 text-danger">Gagal memuat data bundling.</div>'; }
			});
		}

		function disableBundlingAndReset() {
			bundlingSection.style.display = 'none';
			bundlingCardsWrap.innerHTML = '';
			bundlingIdsInput.value = '';
			nominalHidden.value = initialNominalRaw;
			nominalInput.value  = initialNominalShown;
		}

		function renderBundlingCards(items) {
			bundlingCardsWrap.innerHTML = '';
			const selectedIds = [];

			items.forEach((it) => {
				selectedIds.push(it.id);

				const col  = document.createElement('div');
				const card = document.createElement('div');
				col.className  = 'col-md-6';
				card.className = 'bundle-card';
				card.dataset.bundleId = it.id;
				card.dataset.cost     = it.total_cost || 0;

				const closeBtn = document.createElement('span');
				closeBtn.className = 'bundle-close';
				closeBtn.innerHTML = '&times;';
				closeBtn.title     = 'Keluarkan dari bundling';
				closeBtn.addEventListener('click', () => {
					card.remove();
					const ids = getSelectedBundleIds().filter(x => x !== String(it.id));
					bundlingIdsInput.value = ids.join(',');
					recalcBundlingTotal();
				});

				const title = document.createElement('div');
				title.className = 'bundle-title';
				title.innerHTML = `${it.nama_creator || 'Tanpa Nama'} <span class="bundle-badge" style="margin-left:6px">${it.status_endorse || '-'}</span>`;

				const money = document.createElement('div');
				money.className = 'mt-1';
				money.innerHTML = `<div>Total Cost: <b>Rp ${formatRupiah(it.total_cost)}</b></div>`;

				const desc = document.createElement('div');
				desc.className = 'mt-1';
				desc.style.fontSize = '12px';
				if (it.desc) desc.textContent = it.desc;

				card.append(closeBtn, title, money);
				if (it.desc) card.appendChild(desc);

				col.appendChild(card);
				bundlingCardsWrap.appendChild(col);
			});

			bundlingIdsInput.value = selectedIds.join(',');
		}

		function getSelectedBundleIds() {
			return Array.from(bundlingCardsWrap.querySelectorAll('.bundle-card'))
				.map(el => el.dataset.bundleId.toString());
		}

		function recalcBundlingTotal() {
			let totalCost = 0;
			bundlingCardsWrap.querySelectorAll('.bundle-card').forEach(el => {
				totalCost += parseInt(el.dataset.cost || 0, 10);
			});
			const statusPengajuan = (statusSelect?.value || '').toUpperCase();
			let nominal = totalCost;
			if (statusPengajuan === 'DP') nominal = Math.floor(totalCost * 0.5);

			nominalHidden.value = String(nominal);
			nominalInput.value  = formatRupiah(nominal);
		}

		$(formPayment).on('submit', function(e) {
			e.preventDefault();
			const formData = new FormData(formPayment);
			const statusPayment          = formPayment.querySelector('input[name="status_payment"]').value;
			const statusPengajuanPayment = statusSelect?.value;

			if (statusPayment === 'FP' && statusPengajuanPayment === 'FP') {
				Swal.fire({
					title: 'Peringatan',
					text: "Status Pembayaran sudah FP.",
					icon: 'warning',
					confirmButtonText: 'OK'
				});
				return;
			}

			submitPaymentForm(formPayment, formData);
		});

		function submitPaymentForm(form, formData) {
			$.ajax({
				url: form.action,
				type: "POST",
				data: formData,
				contentType: false,
				processData: false,
				beforeSend: () => {
					$(".btn-send-payment", form).prop("disabled", true).html('<div class="loading-ellipsis"><div></div><div></div><div></div><div></div></div>');
				},
				success: (response) => {
					const ok = (typeof response === 'string' && response.includes("success")) ||
						(typeof response === 'object' && response.success);
					
					if (ok) {
						Swal.fire({ 
							title: "Berhasil!", 
							text: (response.message || "Data payment berhasil disimpan"), 
							icon: "success" 
						}).then(() => { 
							window.location.href = '<?= base_url('endorse?id_campaign=') ?>' + form.querySelector('input[name="id_campaign"]').value; 
						});
					} else {
						Swal.fire({ 
							title: "Error!", 
							html: (typeof response === 'string' ? response : (response.message || 'Terjadi kesalahan')), 
							icon: "error" 
						});
					}
					$(".btn-send-payment", form).prop("disabled", false).text("Simpan Data Payment");
				},
				error: (xhr) => {
					Swal.fire({ 
						title: "Request Failed!", 
						text: xhr.responseText || "Terjadi kesalahan", 
						icon: "error" 
					});
					$(".btn-send-payment", form).prop("disabled", false).text("Simpan Data Payment");
				}
			});
		}
	})();

    
</script>