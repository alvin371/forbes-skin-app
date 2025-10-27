<div class="form-message"></div>
<form action="<?= base_url() ?>product/update" method="POST" id="form-modal" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= $data['id'] ?>">
    <input type="hidden" name="dt[is_operational]" value="<?= $data['is_operational'] ?>">
    
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
                    <input type="number" class="form-control regular-input" name="dt[weight]"
                        value="<?= $data['weight'] ?>" required>
                </div>

                <div class="form-group">
                    <label for="">HPP</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" class="form-control format-price text-end regular-input" name="dt[price_buy]"
                            value="<?= $data['price_buy'] > 0 ? number_format($data['price_buy'], 0, ',', '.') : '' ?>"
                            required placeholder="0">
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
                            <input type="text" class="form-control format-price text-end regular-input" name="dt[price_normal]"
                                value="<?= $data['price_normal'] > 0 ? number_format($data['price_normal'], 0, ',', '.') : '' ?>"
                                required placeholder="0">
                        </div>
                    </div>
                    <div class="price-input-group mb-2">
                        <label class="price-label">Reseller</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" class="form-control format-price text-end regular-input" name="dt[price_reseller]"
                                value="<?= $data['price_reseller'] > 0 ? number_format($data['price_reseller'], 0, ',', '.') : '' ?>"
                                required placeholder="0">
                        </div>
                    </div>
                    <div class="price-input-group">
                        <label class="price-label">Distributor</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" class="form-control format-price text-end regular-input" name="dt[price_distributor]"
                                value="<?= $data['price_distributor'] > 0 ? number_format($data['price_distributor'], 0, ',', '.') : '' ?>"
                                required placeholder="0">
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
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="remove_main_image" id="remove_main_image">
                        <label class="form-check-label" for="remove_main_image">Hapus gambar ini</label>
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
                <?php if (!empty($data['variants'])): ?>
                    <?php foreach ($data['variants'] as $index => $variant): ?>
                        <div class="variant-card" id="varian-row-<?= $index ?>" data-index="<?= $index ?>">
                            <div class="variant-card-header">
                                <h6 class="variant-title">Varian <?= $index + 1 ?></h6>
                                <button type="button" class="btn btn-sm btn-danger"
                                    onclick="removeVarianRow(<?= $index ?>, <?= $variant['id'] ?? 'null' ?>)">
                                    <i class="fa fa-trash"></i> Hapus
                                </button>
                            </div>
                            <div class="variant-card-body">
                                <input type="hidden" name="variants[<?= $index ?>][id]" value="<?= $variant['id'] ?>">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Nama Varian <span class="text-danger">*</span></label>
                                        <input class="form-control" name="variants[<?= $index ?>][name]"
                                            value="<?= $variant['name'] ?>" placeholder="Contoh: Formeglow Box" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">SKU Varian <span class="text-danger">*</span></label>
                                        <input class="form-control" name="variants[<?= $index ?>][sku]"
                                            value="<?= $variant['sku'] ?>" placeholder="Contoh: FM-BOX" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Berat (gr) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="variants[<?= $index ?>][weight]"
                                            value="<?= $variant['weight'] ?>" placeholder="200" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">HPP <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" class="form-control format-price text-end"
                                                name="variants[<?= $index ?>][price_buy]"
                                                value="<?= $variant['price_buy'] > 0 ? number_format($variant['price_buy'], 0, ',', '.') : '' ?>"
                                                required placeholder="0">
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
                                            <input type="text" class="form-control format-price text-end"
                                                name="variants[<?= $index ?>][price_normal]"
                                                value="<?= $variant['price_normal'] > 0 ? number_format($variant['price_normal'], 0, ',', '.') : '' ?>"
                                                required placeholder="0">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label text-muted small">Reseller</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" class="form-control format-price text-end"
                                                name="variants[<?= $index ?>][price_reseller]"
                                                value="<?= $variant['price_reseller'] > 0 ? number_format($variant['price_reseller'], 0, ',', '.') : '' ?>"
                                                required placeholder="0">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label text-muted small">Distributor</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" class="form-control format-price text-end"
                                                name="variants[<?= $index ?>][price_distributor]"
                                                value="<?= $variant['price_distributor'] > 0 ? number_format($variant['price_distributor'], 0, ',', '.') : '' ?>"
                                                required placeholder="0">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Gambar Varian</label>
                                        <?php if (!empty($variant['img'])): ?>
                                            <div class="mb-2 d-flex align-items-center gap-2">
                                                <img src="<?= base_url() ?>/assets/img/product/<?= $variant['img'] ?>"
                                                    alt="Gambar Varian" style="max-width: 80px; max-height: 80px; border-radius: 8px; border: 2px solid #e9ecef;">
                                                <input type="hidden" name="variants[<?= $index ?>][existing_img]"
                                                    value="<?= $variant['img'] ?>">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox"
                                                        name="variants[<?= $index ?>][remove_img]"
                                                        id="remove_img_<?= $index ?>">
                                                    <label class="form-check-label" for="remove_img_<?= $index ?>">Hapus gambar ini</label>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" class="form-control" name="variant_img_<?= $index ?>" accept="image/*">
                                        <small class="text-muted">Maksimal 2MB (Format: JPG, PNG, JPEG)</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
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
                <?php endif; ?>
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
            <button type="submit" class="btn btn-primary btn-send">Simpan Perubahan</button>
        </div>
    </div>
</form>

<script>
    var varianIndex = <?= !empty($data['variants']) ? count($data['variants']) : 1 ?>;
    var deletedVariants = [];
    
    function toggleVarianForm(checkbox) {
        const isVarian = checkbox.checked;

        document.getElementById('varian-form').style.display = isVarian ? 'block' : 'none';
        document.getElementById('regular-product-form').style.display = isVarian ? 'none' : 'block';
        document.getElementById('regular-price-form').style.display = isVarian ? 'none' : 'block';

        // Handle regular product inputs
        const regularInputs = document.querySelectorAll('.regular-input');
        regularInputs.forEach(input => {
            if (isVarian) {
                input.setAttribute('data-original-value', input.value);
                input.value = '';
                input.required = false;
            } else {
                const originalValue = input.getAttribute('data-original-value');
                if (originalValue) {
                    input.value = originalValue;
                }
                input.required = true;
            }
        });

        // Handle variant inputs - toggle required attribute based on visibility
        const variantInputs = document.querySelectorAll('#varian-form input[required]');
        variantInputs.forEach(input => {
            input.required = isVarian;
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

        // Initialize format price for new card
        $(newCard).find('.format-price').each(function() {
            $(this).off('keyup.formatRibuan').on('keyup.formatRibuan', function() {
                formatRibuan(this);
            });
            formatRibuan(this);
        });

        varianIndex++;
        updateVariantNumbers();
    }

    function removeVarianRow(index, variantId = null) {
        const card = document.getElementById('varian-row-' + index);
        if (card) {
            if (confirm('Apakah Anda yakin ingin menghapus varian ini?')) {
                if (variantId) {
                    deletedVariants.push(variantId);
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'deleted_variants[]';
                    input.value = variantId;
                    document.getElementById('form-modal').appendChild(input);
                }

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
</script>

<script type="text/javascript">
    function formatRibuan(input) {
        let value = input.value.replace(/\D/g, '');
        if (!value) {
            input.value = '';
            return;
        }
        // Simpan nilai asli di data attribute
        $(input).data('raw-value', value);
        
        // Tampilkan dengan format ribuan
        input.value = parseInt(value).toLocaleString('id-ID');
    }

    function inisialisasiFormatHarga() {
        const selector = '.format-price, input[name*="[price_"], input[name*="[price_buy]"], input[name="dt[price_buy]"], input[name="dt[price_normal]"], input[name="dt[price_reseller]"], input[name="dt[price_distributor]"]';

        $(selector).each(function () {
            $(this).off('keyup.formatRibuan').on('keyup.formatRibuan', function () {
                formatRibuan(this);
            });

            formatRibuan(this);
        });
    }

    $(document).ready(function () {
        inisialisasiFormatHarga();

        // Initialize form based on current variant state
        const isVarian = $('#varian_switch').is(':checked');
        if (!isVarian) {
            // Remove required from variant inputs if not a variant product
            $('#varian-form input[required]').each(function() {
                $(this).prop('required', false);
            });
        } else {
            // Remove required from regular inputs if it's a variant product
            $('.regular-input').each(function() {
                $(this).prop('required', false);
            });
        }
    });

    $("#form-modal").submit(function(e) {
        e.preventDefault();
        console.log('Form submit triggered');

        var form = $(this);
        var formAction = form.attr("action");
        console.log('Form action URL:', formAction);

        // Hapus format ribuan dari SEMUA input harga
        const priceInputs = form.find('.format-price, input[name*="[price"]');
        console.log('Found price inputs:', priceInputs.length);

        priceInputs.each(function () {
            var originalValue = this.value;
            this.value = this.value.replace(/\./g, '').replace(/,/g, '');
            console.log('Cleaned price:', originalValue, '->', this.value);
        });

        var mydata = new FormData(this);

        // Set variant flag explicitly
        if ($('#varian_switch').is(':checked')) {
            mydata.set('dt[is_varian]', '1');
            console.log('Product is variant');
        } else {
            mydata.set('dt[is_varian]', '0');
            console.log('Product is regular');
        }

        console.log('Sending AJAX request...');

        $.ajax({
            type: "POST",
            url: formAction,
            data: mydata,
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function() {
                console.log('AJAX beforeSend');
                $(".btn-send").addClass("disabled").html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...').attr('disabled', true);
                form.find(".form-message").slideUp().html("");
            },
            success: function(response, textStatus, xhr) {
                console.log('AJAX success:', response);
                if (response.indexOf("success") != -1) {
                    $(".form-message").hide().html(response).slideDown("fast");
                    setTimeout(function() {
                        window.location.href = "<?= base_url() ?>product";
                    }, 2000);
                } else {
                    $(".form-message").hide().html(response).slideDown("fast");
                    $(".btn-send").removeClass("disabled").html('Simpan Perubahan').attr('disabled', false);
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
                console.error('Response:', xhr.responseText);
                $(".btn-send").removeClass("disabled").html('Simpan Perubahan').attr('disabled', false);

                var errorMsg = xhr.responseText || 'Terjadi kesalahan pada server. Silakan coba lagi.';
                $(".form-message").hide().html('<div class="alert alert-danger">' + errorMsg + '</div>').slideDown("fast");
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

    /* Image preview styling */
    .variant-card-body img {
        object-fit: cover;
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