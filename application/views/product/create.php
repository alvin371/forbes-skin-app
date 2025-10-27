<div class="form-message"></div>
<form action="<?= base_url() ?>/product/store" method="POST" id="form-modal" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= $data['id'] ?>">
    <input type="hidden" name="dt[is_operational]" value="<?= $_GET['p'] == 'operasional' ? 1 : 0 ?>">
    
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label for="">Gift</label>
                <br>
                <input <?= $data['is_gift'] == 1 ? 'checked' : '' ?> name="dt[is_gift]" type="radio" id="gift-1" value="1"> <label for="gift-1">Ya</label>
                <input <?= $data['is_gift'] == 0 ? 'checked' : '' ?> name="dt[is_gift]" type="radio" id="gift-2" value="0"> <label for="gift-2">Tidak</label>
            </div>
            <div class="form-group">
                <div class="form-check form-switch d-inline-block">
                    <input type="hidden" name="dt[is_varian]" value="0">
                    <input class="form-check-input" type="checkbox" id="varian_switch" name="dt[is_varian]" 
                        value="1" <?= $data['is_varian'] ? 'checked' : '' ?> onchange="toggleVarianForm(this)">
                    <label for="varian_switch">Produk Varian</label>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="form-group">
                <label for="">Nama Produk</label>
                <input type="text" class="form-control" name="dt[name]" value="<?= $data['name'] ?>" required>
            </div>
            
            <div class="form-group">
                <label for="">SKU Induk</label>
                <input type="text" class="form-control" name="dt[sku]" value="<?= $data['sku'] ?>" required>
            </div>
            
            <div class="form-group">
                <label for="">Brand</label>
                <select class="form-control" name="dt[brand]" required>
                    <?php foreach ($brand as $v2): ?>
                        <option value="<?= $v2['code'] ?>" <?= $data['brand'] == $v2['code'] ? 'selected' : '' ?>>
                            <?= $v2['code'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Form Produk Biasa -->
            <div id="regular-product-form" style="display: <?= $data['is_varian'] ? 'none' : 'block' ?>;">
                <div class="form-group">
                    <label for="">Berat (gr)</label>
                    <input type="number" class="form-control" name="dt[weight]"
                        value="<?= $data['weight'] ?>" <?= $data['is_varian'] ? 'disabled' : '' ?> required>
                </div>

                <div class="form-group">
                    <label for="">HPP</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" class="form-control format-price text-end" name="dt[price_buy]"
                            value="<?= $data['price_buy'] > 0 ? number_format($data['price_buy'], 0, ',', '.') : '' ?>"
                            <?= $data['is_varian'] ? 'disabled' : '' ?> required placeholder="0">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <!-- Harga untuk produk biasa -->
            <div id="regular-price-form" style="display: <?= $data['is_varian'] ? 'none' : 'block' ?>;">
                <div class="form-group">
                    <label>Harga</label>
                    <div class="price-input-group mb-2">
                        <label class="price-label">Pelanggan</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" class="form-control format-price text-end" name="dt[price_normal]"
                                value="<?= $data['price_normal'] > 0 ? number_format($data['price_normal'], 0, ',', '.') : '' ?>"
                                <?= $data['is_varian'] ? 'disabled' : '' ?> required placeholder="0">
                        </div>
                    </div>
                    <div class="price-input-group mb-2">
                        <label class="price-label">Reseller</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" class="form-control format-price text-end" name="dt[price_reseller]"
                                value="<?= $data['price_reseller'] > 0 ? number_format($data['price_reseller'], 0, ',', '.') : '' ?>"
                                <?= $data['is_varian'] ? 'disabled' : '' ?> required placeholder="0">
                        </div>
                    </div>
                    <div class="price-input-group">
                        <label class="price-label">Distributor</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" class="form-control format-price text-end" name="dt[price_distributor]"
                                value="<?= $data['price_distributor'] > 0 ? number_format($data['price_distributor'], 0, ',', '.') : '' ?>"
                                <?= $data['is_varian'] ? 'disabled' : '' ?> required placeholder="0">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="">Keterangan</label>
                <textarea class="form-control" name="dt[desc]"><?= $data['desc'] ?></textarea>
            </div>

            <div class="form-group">
                <label for="">Gambar Produk</label>
                <?php if ($data['img']): ?>
                    <div class="mb-2">
                        <img src="<?= base_url() ?>/assets/img/product/<?= $data['img'] . '?token=' . DATE("Ymdhis", strtotime($data['updated_at'])) ?>"
                            alt="Gambar Produk" class="product-image-preview">
                    </div>
                <?php endif; ?>
                <input type="file" class="form-control" name="file" accept="image/png, image/jpeg, image/jpg">
                <small class="form-text text-muted">Format: JPG, PNG, JPEG (Maksimal 2MB)</small>
            </div>
        </div>
    </div>

    <!-- Form Varian -->
    <div id="varian-form" style="display: <?= $data['is_varian'] ? 'block' : 'none' ?>;">
        <div class="col-md-12">
            <div id="varian-container">
                <!-- Variant cards will be added here -->
                <div class="variant-card" id="varian-row-0" data-index="0">
                    <div class="variant-card-header">
                        <h6 class="variant-title">Varian 1</h6>
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeVarianRow(0)">
                            <i class="fa fa-trash"></i> Hapus
                        </button>
                    </div>
                    <div class="variant-card-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Nama Varian <span class="text-danger">*</span></label>
                                <input class="form-control" name="variants[0][name]" placeholder="Contoh: Formeglow Box" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">SKU Varian <span class="text-danger">*</span></label>
                                <input class="form-control" name="variants[0][sku]" placeholder="Contoh: FM-BOX" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Berat (gr) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="variants[0][weight]" placeholder="200" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">HPP <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control format-price text-end" name="variants[0][price_buy]" required placeholder="0">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-2">
                                <label class="form-label fw-bold">Harga Jual <span class="text-danger">*</span></label>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-muted small">Pelanggan</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control format-price text-end" name="variants[0][price_normal]" required placeholder="0">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-muted small">Reseller</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control format-price text-end" name="variants[0][price_reseller]" required placeholder="0">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-muted small">Distributor</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control format-price text-end" name="variants[0][price_distributor]" required placeholder="0">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Gambar Varian</label>
                                <input type="file" class="form-control" name="variant_img_0" accept="image/*">
                                <small class="text-muted">Maksimal 2MB (Format: JPG, PNG, JPEG)</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <button type="button" class="btn btn-outline-primary" onclick="addVarianRow()">
                    <i class="fa fa-plus"></i> Tambah Varian
                </button>
            </div>
        </div>
    </div>
    
    <div class="row mt-3">
        <div class="col-md-12 text-end">
            <button type="submit" class="btn btn-primary btn-send">Simpan Data</button>
        </div>
    </div>
</form>

<script>
    var varianIndex = 1;
    
    function toggleVarianForm(checkbox) {
        const isVarian = checkbox.checked;

        document.getElementById('varian-form').style.display = isVarian ? 'block' : 'none';
        document.getElementById('regular-product-form').style.display = isVarian ? 'none' : 'block';
        document.getElementById('regular-price-form').style.display = isVarian ? 'none' : 'block';

        // Toggle required for regular product inputs
        const regularInputs = document.querySelectorAll('#regular-product-form input, #regular-price-form input');
        regularInputs.forEach(input => {
            input.disabled = isVarian;
            input.required = !isVarian;
        });

        // Toggle required for variant inputs
        const variantInputs = document.querySelectorAll('#varian-form input[required], #varian-form input[type="text"], #varian-form input[type="number"]');
        variantInputs.forEach(input => {
            if (isVarian) {
                input.setAttribute('required', 'required');
            } else {
                input.removeAttribute('required');
            }
        });
    }
    
    function addVarianRow() {
        const container = document.getElementById('varian-container');
        const newCard = document.createElement('div');
        newCard.className = 'variant-card';
        newCard.id = 'varian-row-' + varianIndex;
        newCard.setAttribute('data-index', varianIndex);

        newCard.innerHTML = `
            <div class="variant-card-header">
                <h6 class="variant-title">Varian ${varianIndex + 1}</h6>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeVarianRow(${varianIndex})">
                    <i class="fa fa-trash"></i> Hapus
                </button>
            </div>
            <div class="variant-card-body">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Nama Varian <span class="text-danger">*</span></label>
                        <input class="form-control" name="variants[${varianIndex}][name]" placeholder="Contoh: Formeglow Box" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">SKU Varian <span class="text-danger">*</span></label>
                        <input class="form-control" name="variants[${varianIndex}][sku]" placeholder="Contoh: FM-BOX" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Berat (gr) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="variants[${varianIndex}][weight]" placeholder="200" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">HPP <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" class="form-control format-price text-end" name="variants[${varianIndex}][price_buy]" required placeholder="0">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 mb-2">
                        <label class="form-label fw-bold">Harga Jual <span class="text-danger">*</span></label>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-muted small">Pelanggan</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" class="form-control format-price text-end" name="variants[${varianIndex}][price_normal]" required placeholder="0">
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-muted small">Reseller</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" class="form-control format-price text-end" name="variants[${varianIndex}][price_reseller]" required placeholder="0">
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-muted small">Distributor</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" class="form-control format-price text-end" name="variants[${varianIndex}][price_distributor]" required placeholder="0">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Gambar Varian</label>
                        <input type="file" class="form-control" name="variant_img_${varianIndex}" accept="image/*">
                        <small class="text-muted">Maksimal 2MB (Format: JPG, PNG, JPEG)</small>
                    </div>
                </div>
            </div>
        `;

        container.appendChild(newCard);

        // Inisialisasi format harga untuk card baru
        $(newCard).find('.format-price').each(function() {
            $(this).off('keyup.formatRibuan').on('keyup.formatRibuan', function() {
                formatRibuan(this);
            });
        });

        varianIndex++;
        updateVariantNumbers();
    }
    
    function removeVarianRow(index) {
        const card = document.getElementById('varian-row-' + index);
        if (card) {
            if (confirm('Apakah Anda yakin ingin menghapus varian ini?')) {
                card.remove();

                // Ensure at least one variant exists
                if (document.querySelectorAll('.variant-card').length === 0) {
                    addVarianRow();
                }

                updateVariantNumbers();
            }
        }
    }

    function updateVariantNumbers() {
        const cards = document.querySelectorAll('.variant-card');
        cards.forEach((card, index) => {
            const title = card.querySelector('.variant-title');
            if (title) {
                title.textContent = 'Varian ' + (index + 1);
            }
        });
    }
    
    function formatRibuan(input) {
        let value = $(input).val().replace(/\D/g, '');
        if (!value) {
            $(input).val('');
            return;
        }
        let formatted = new Intl.NumberFormat('id-ID').format(value);
        $(input).val(formatted);
    }

    function inisialisasiFormatHarga() {
        $('.format-price').each(function() {
            // Format nilai awal
            let value = $(this).val().replace(/\D/g, '');
            if (value) {
                $(this).val(new Intl.NumberFormat('id-ID').format(value));
            }
            
            // Event handler untuk input baru
            $(this).off('keyup.formatRibuan').on('keyup.formatRibuan', function() {
                formatRibuan(this);
            });
        });
    }

    $(document).ready(function() {
        console.log('Create product form loaded');
        inisialisasiFormatHarga();

        // Inisialisasi dropdown Bootstrap
        $('.dropdown-toggle').dropdown();

        // Initialize form state based on varian checkbox
        const varianCheckbox = document.getElementById('varian_switch');
        if (varianCheckbox) {
            toggleVarianForm(varianCheckbox);
        }
    });

    $("#form-modal").submit(function(e) {
        e.preventDefault();
        console.log('Form submit triggered');

        var form = $(this);

        // Hapus format ribuan SEBELUM membuat FormData
        $('.format-price').each(function() {
            this.value = this.value.replace(/\./g, '');
        });

        var mydata = new FormData(this);

        if ($('#varian_switch').is(':checked')) {
            mydata.set('dt[is_varian]', '1');
        } else {
            mydata.set('dt[is_varian]', '0');
        }

        $.ajax({
            type: "POST",
            url: form.attr("action"),
            data: mydata,
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function() {
                $(".btn-send").addClass("disabled").html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...').attr('disabled', true);
                form.find(".form-message").slideUp().html("");
            },
            success: function(response, textStatus, xhr) {
                if (response.indexOf("success") != -1) {
                    $(".form-message").hide().html(response).slideDown("fast");
                    setTimeout(function() {
                        window.location.href = "<?= base_url() ?>/product";
                    }, 2000);
                } else {
                    $(".form-message").hide().html(response).slideDown("fast");
                    $(".btn-send").removeClass("disabled").html('Simpan Data').attr('disabled', false);
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                $(".btn-send").removeClass("disabled").html('Simpan Data').attr('disabled', false);
                $(".form-message").hide().html(xhr.responseText).slideDown("fast");
            }
        });
        return false;
    });
</script>

<style>
    /* Form Group Spacing */
    .form-group {
        margin-bottom: 1.25rem;
    }

    .price-input-group {
        margin-bottom: 0.875rem;
    }

    .price-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        font-size: 0.875rem;
        color: #495057;
    }

    .price-input-group .form-control {
        text-align: right;
    }

    /* Unified Form Control Styling */
    .form-control {
        border: 1px solid #d1d5db;
        border-radius: 6px;
        padding: 0.5rem 0.75rem;
        font-size: 0.9375rem;
        line-height: 1.5;
        height: calc(1.5em + 1rem + 2px);
        transition: all 0.2s ease;
        background-color: #ffffff;
    }

    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: 0;
        background-color: #ffffff;
    }

    .form-control::placeholder {
        color: #9ca3af;
    }

    /* Textarea specific styling */
    textarea.form-control {
        height: auto;
        min-height: calc(1.5em + 1rem + 2px);
    }

    /* Label Styling */
    .form-group label {
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.5rem;
        font-size: 0.9375rem;
    }

    input[type="number"]::-webkit-inner-spin-button,
    input[type="number"]::-webkit-outer-spin-button {
        opacity: 1;
    }

    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        line-height: 1.5;
    }

    .text-end {
        text-align: right !important;
    }

    /* Unified Input Group Styling */
    .input-group {
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        border-radius: 6px;
        overflow: hidden;
        transition: all 0.2s ease;
    }

    .input-group:focus-within {
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .input-group-text {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: #ffffff;
        font-weight: 600;
        font-size: 0.875rem;
        min-width: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0.5rem 0.75rem;
        line-height: 1.5;
        border-right: none;
    }

    .input-group .form-control {
        border-left: none;
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
        box-shadow: none;
    }

    .input-group .form-control:focus {
        border-color: #d1d5db;
        box-shadow: none;
    }

    .input-group-sm .input-group-text {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }

    .format-price {
        font-family: 'Courier New', monospace;
        letter-spacing: 0.5px;
    }

    /* Image Preview Styling */
    .product-image-preview {
        max-width: 150px;
        max-height: 150px;
        border-radius: 8px;
        border: 2px solid #e9ecef;
        object-fit: cover;
    }

    /* File Input Styling - Fix Choose File Button Alignment */
    input[type="file"].form-control {
        padding: 0.375rem 0.75rem;
        height: auto;
        line-height: 1.5;
    }

    input[type="file"].form-control::file-selector-button {
        padding: 0.375rem 0.75rem;
        margin-right: 0.75rem;
        background-color: #e9ecef;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        color: #212529;
        font-weight: 400;
        font-size: 1rem;
        line-height: 1.5;
        cursor: pointer;
        transition: all 0.15s ease-in-out;
    }

    input[type="file"].form-control::file-selector-button:hover {
        background-color: #dde0e3;
        border-color: #b8bdc3;
    }

    /* Firefox file input button */
    input[type="file"].form-control::-webkit-file-upload-button {
        padding: 0.375rem 0.75rem;
        margin-right: 0.75rem;
        background-color: #e9ecef;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        color: #212529;
        font-weight: 400;
        font-size: 1rem;
        line-height: 1.5;
        cursor: pointer;
        transition: all 0.15s ease-in-out;
    }

    input[type="file"].form-control::-webkit-file-upload-button:hover {
        background-color: #dde0e3;
        border-color: #b8bdc3;
    }

    /* Variant Card Styling */
    .variant-card {
        background: #ffffff;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .variant-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        border-color: #0d6efd;
    }

    .variant-card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid #e9ecef;
    }

    .variant-title {
        color: #ffffff;
        font-size: 1.1rem;
        font-weight: 600;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .variant-card-header .btn-danger {
        background-color: rgba(220, 53, 69, 0.9);
        border: none;
        transition: all 0.2s;
    }

    .variant-card-header .btn-danger:hover {
        background-color: #dc3545;
        transform: scale(1.05);
    }

    .variant-card-body {
        padding: 25px;
        background: #fafbfc;
    }

    #varian-container {
        margin-top: 20px;
    }

    .form-label {
        font-weight: 500;
        color: #495057;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }

    .form-label .text-danger {
        color: #dc3545;
    }

    .fw-bold {
        font-weight: 700 !important;
        color: #212529;
        font-size: 1rem;
    }

    .text-muted.small {
        font-size: 0.85rem;
        font-weight: 400;
    }

    .btn-outline-primary {
        border-width: 2px;
        font-weight: 600;
        padding: 10px 25px;
        transition: all 0.3s ease;
    }

    .btn-outline-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(13, 110, 253, 0.3);
    }

    /* Improve form control appearance */
    .variant-card-body .form-control {
        border: 1px solid #ced4da;
        border-radius: 6px;
        padding: 0.5rem 0.75rem;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    .variant-card-body .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    /* Row spacing improvements */
    .variant-card-body .row {
        margin-bottom: 0;
    }

    .variant-card-body .mb-3 {
        margin-bottom: 1rem !important;
    }

    .variant-card-body .mb-2 {
        margin-bottom: 0.5rem !important;
    }

    /* File input styling */
    input[type="file"].form-control {
        padding: 0.375rem 0.75rem;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .variant-card-body {
            padding: 15px;
        }

        .variant-card-header {
            padding: 12px 15px;
        }

        .variant-title {
            font-size: 1rem;
        }
    }
</style>