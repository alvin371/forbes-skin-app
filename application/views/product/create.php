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
                    <input type="text" class="form-control format-price" name="dt[price_buy]" 
                        value="<?= number_format($data['price_buy'], 0, ',', '.') ?>" <?= $data['is_varian'] ? 'disabled' : '' ?> required>
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
                        <input type="text" class="form-control format-price" name="dt[price_normal]" 
                            value="<?= number_format($data['price_normal'], 0, ',', '.') ?>" <?= $data['is_varian'] ? 'disabled' : '' ?> required>
                    </div>
                    <div class="price-input-group mb-2">
                        <label class="price-label">Reseller</label>
                        <input type="text" class="form-control format-price" name="dt[price_reseller]" 
                            value="<?= number_format($data['price_reseller'], 0, ',', '.') ?>" <?= $data['is_varian'] ? 'disabled' : '' ?> required>
                    </div>
                    <div class="price-input-group">
                        <label class="price-label">Distributor</label>
                        <input type="text" class="form-control format-price" name="dt[price_distributor]" 
                            value="<?= number_format($data['price_distributor'], 0, ',', '.') ?>" <?= $data['is_varian'] ? 'disabled' : '' ?> required>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="">Keterangan</label>
                <textarea class="form-control" name="dt[desc]"><?= $data['desc'] ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="">Gambar Produk</label>
                <div class="d-flex align-items-start gap-3 flex-wrap">
                    <?php if ($data['img']): ?>
                        <div>
                            <img src="<?= base_url() ?>/assets/img/product/<?= $data['img'] . '?token=' . DATE("Ymdhis", strtotime($data['updated_at'])) ?>" 
                                alt="Gambar Produk" style="max-width: 150px; max-height: 150px;">
                        </div>
                    <?php endif; ?>

                    <div style="min-width: 200px;">
                        <input type="file" class="form-control" name="file" accept="image/png, image/jpeg, image/jpg">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Varian -->
    <div id="varian-form" style="display: <?= $data['is_varian'] ? 'block' : 'none' ?>;">
        <div class="col-md-12">
            <div class="table-responsive" style="overflow-x: auto;">
                <table class="table table-bordered table-striped" style="min-width: 1000px; white-space: nowrap;">
                    <thead>
                        <tr class="bg-blue-2">
                            <th class="text-center" style="min-width: 200px;">Nama Varian</th>
                            <th class="text-center">SKU Varian</th>
                            <th class="text-center">Berat (gr)</th>
                            <th class="text-center">HPP Varian</th>
                            <th class="text-center">Harga</th>
                            <th class="text-center">Gambar Varian</th>
                            <th class="text-center" style="width: 80px;">
                                <button type="button" class="btn btn-sm btn-primary" onclick="addVarianRow()">
                                    <i class="fa fa-plus"></i> Tambah
                                </button>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="varian-tbody">
                        <tr id="varian-row-0">
                            <td style="min-width: 200px;">
                                <input class="form-control" name="variants[0][name]" required>
                            </td>
                            <td>
                                <input class="form-control" name="variants[0][sku]" required>
                            </td>
                            <td>
                                <input type="number" class="form-control text-end" name="variants[0][weight]" required>
                            </td>
                            <td>
                                <input type="text" class="form-control text-end format-price" name="variants[0][price_buy]" required>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-light w-100 dropdown-toggle text-start text-truncate" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Isi Harga Varian
                                    </button>
                                    <div class="dropdown-menu p-3" style="min-width: 250px;">
                                        <div class="price-input-group mb-2">
                                            <label class="price-label">Pelanggan</label>
                                            <input type="text" class="form-control format-price" name="variants[0][price_normal]" required>
                                        </div>
                                        <div class="price-input-group mb-2">
                                            <label class="price-label">Reseller</label>
                                            <input type="text" class="form-control format-price" name="variants[0][price_reseller]" required>
                                        </div>
                                        <div class="price-input-group">
                                            <label class="price-label">Distributor</label>
                                            <input type="text" class="form-control format-price" name="variants[0][price_distributor]" required>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <input type="file" class="form-control form-control-sm" 
                                    name="variant_img_0" accept="image/*">
                                <small class="text-muted">Max 2MB</small>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-danger" 
                                    onclick="removeVarianRow(0)">
                                    <i class="fa fa-trash"></i> Hapus
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-2 text-muted">
                <small>Geser tabel ke kanan/kiri jika tidak semua kolom terlihat</small>
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
        
        const regularInputs = document.querySelectorAll('#regular-product-form input, #regular-price-form input');
        regularInputs.forEach(input => {
            input.disabled = isVarian;
            input.required = !isVarian;
        });
    }
    
    function addVarianRow() {
        const tbody = document.getElementById('varian-tbody');
        const newRow = document.createElement('tr');
        newRow.id = 'varian-row-' + varianIndex;
        
        newRow.innerHTML = `
            <td style="min-width: 200px;">
                <input class="form-control" name="variants[${varianIndex}][name]" required>
            </td>
            <td>
                <input class="form-control" name="variants[${varianIndex}][sku]" required>
            </td>
            <td>
                <input type="number" class="form-control text-end" name="variants[${varianIndex}][weight]" required>
            </td>
            <td>
                <input type="text" class="form-control text-end format-price" name="variants[${varianIndex}][price_buy]" required>
            </td>
            <td>
                <div class="dropdown">
                    <button class="btn btn-light w-100 dropdown-toggle text-start text-truncate" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Isi Harga Varian
                    </button>
                    <div class="dropdown-menu p-3" style="min-width: 250px;">
                        <div class="price-input-group mb-2">
                            <label class="price-label">Pelanggan</label>
                            <input type="text" class="form-control format-price" name="variants[${varianIndex}][price_normal]" required>
                        </div>
                        <div class="price-input-group mb-2">
                            <label class="price-label">Reseller</label>
                            <input type="text" class="form-control format-price" name="variants[${varianIndex}][price_reseller]" required>
                        </div>
                        <div class="price-input-group">
                            <label class="price-label">Distributor</label>
                            <input type="text" class="form-control format-price" name="variants[${varianIndex}][price_distributor]" required>
                        </div>
                    </div>
                </div>
            </td>
            <td>
                <input type="file" class="form-control form-control-sm" 
                    name="variant_img_${varianIndex}" accept="image/*">
                <small class="text-muted">Max 2MB</small>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger" onclick="removeVarianRow(${varianIndex})">
                    <i class="fa fa-trash"></i> Hapus
                </button>
            </td>
        `;
        
        tbody.appendChild(newRow);
        
        // Inisialisasi format harga untuk row baru
        $(newRow).find('.format-price').each(function() {
            $(this).off('keyup.formatRibuan').on('keyup.formatRibuan', function() {
                formatRibuan(this);
            });
        });
        
        varianIndex++;
    }
    
    function removeVarianRow(index) {
        const row = document.getElementById('varian-row-' + index);
        if (row) {
            if (confirm('Apakah Anda yakin ingin menghapus varian ini?')) {
                row.remove();
                
                if (document.querySelectorAll('#varian-tbody tr').length === 0) {
                    addVarianRow();
                }
            }
        }
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
        inisialisasiFormatHarga();
        
        // Inisialisasi dropdown Bootstrap
        $('.dropdown-toggle').dropdown();
    });

    $("#form-modal").submit(function() {
        var form = $(this);
        var mydata = new FormData(this);
        
        if ($('#varian_switch').is(':checked')) {
            mydata.set('dt[is_varian]', '1');
        } else {
            mydata.set('dt[is_varian]', '0');
        }

        // Hapus format ribuan sebelum submit
        $('.format-price').each(function() {
            this.value = this.value.replace(/\./g, '');
        });

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
    .price-input-group {
        margin-bottom: 10px;
    }
    .price-label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        font-size: 0.875rem;
    }
    .price-input-group .form-control {
        text-align: right;
    }
    .table-responsive {
        border: 1px solid #dee2e6;
        border-radius: 4px;
        margin-bottom: 20px;
    }
    
    .table thead th {
        position: -webkit-sticky;
        position: sticky;
        top: 0;
        z-index: 10;
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
    
    .dropdown-menu {
        z-index: 1050;
    }
    
    .text-end {
        text-align: right !important;
    }
</style>