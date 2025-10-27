<style>
    .form-group {
        margin-bottom: 16px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: rgba(0, 0, 0, 0.85);
        font-size: 14px;
        font-weight: 500;
    }

    .form-group .form-control,
    .form-group .form-select {
        height: 32px;
        padding: 4px 11px;
        font-size: 14px;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
        transition: all 0.3s;
        width: 100%;
    }

    .form-group textarea.form-control {
        height: auto;
        min-height: 80px;
        padding: 8px 11px;
    }

    .form-group .form-control:hover,
    .form-group .form-select:hover {
        border-color: #40a9ff;
    }

    .form-group .form-control:focus,
    .form-group .form-select:focus {
        border-color: #40a9ff;
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
        outline: none;
    }

    .modal-footer {
        border-top: 1px solid #f0f0f0;
        padding: 16px 24px;
        display: flex;
        justify-content: flex-end;
        gap: 8px;
    }
</style>

<div class="form-message"></div>

<form id="form-add-influencer" method="POST">
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="niche">Category KOL</label>
                <select class="form-select" id="niche" name="niche">
                    <option value="">Pilih Category KOL</option>
                    <?php foreach ($niches as $item): ?>
                        <option value="<?= htmlspecialchars($item['niche']) ?>">
                            <?= htmlspecialchars($item['niche']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="brand">Brand</label>
                <select class="form-select" id="brand" name="brand">
                    <option value="">Pilih Brand</option>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?= htmlspecialchars($brand->code) ?>">
                            <?= htmlspecialchars($brand->code) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="url">URL</label>
                <input type="text" class="form-control" id="url" name="url" placeholder="Masukkan URL profil influencer">
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="contact">Kontak</label>
                <input type="text" class="form-control" id="contact" name="contact" placeholder="Masukkan kontak (WA/Email/dll)">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="pic">PIC</label>
                <select class="form-select" id="pic" name="pic">
                    <option value="">Pilih PIC</option>
                    <?php foreach ($pics as $pic): ?>
                        <option value="<?= htmlspecialchars($pic->full_name) ?>"
                            <?= ($pic->full_name == $_SESSION['user']['full_name']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pic->full_name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="type">Platform</label>
                <select class="form-select" id="type" name="type">
                    <option value="Tiktok" <?= ($platform == 'Tiktok') ? 'selected' : '' ?>>Tiktok</option>
                    <option value="Instagram" <?= ($platform == 'Instagram') ? 'selected' : '' ?>>Instagram</option>
                    <option value="YouTube">YouTube</option>
                </select>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="ratecard">Ratecard</label>
                <input type="text" class="form-control" id="ratecard" name="ratecard" placeholder="Masukkan ratecard">
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="desc">Deskripsi</label>
                <textarea class="form-control" id="desc" name="desc" placeholder="Masukkan deskripsi"></textarea>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <div class="form-check" style="padding: 12px; background-color: #f6ffed; border-left: 3px solid #52c41a; border-radius: 2px;">
                    <input class="form-check-input" type="checkbox" id="auto_fetch" name="auto_fetch" checked>
                    <label class="form-check-label" for="auto_fetch" style="color: rgba(0, 0, 0, 0.85);">
                        <i class="bi bi-lightning-charge-fill text-success me-1"></i>
                        <strong>Ambil data engagement otomatis</strong>
                        <br>
                        <small style="color: rgba(0, 0, 0, 0.65); margin-left: 20px;">
                            Secara otomatis mengambil follower, CPM, avg view, dan ER dari TikTok/Instagram setelah menyimpan data
                        </small>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary btn-submit">
            <i class="bi bi-save me-1"></i> Simpan
        </button>
    </div>
</form>

<script>
$(document).ready(function() {
    // Format ratecard input
    $('#ratecard').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        if (value) {
            $(this).val(parseInt(value).toLocaleString('id-ID'));
        }
    });

    // Handle form submission
    $('#form-add-influencer').on('submit', function(e) {
        e.preventDefault();

        var formData = $(this).serializeArray();
        var data = {};

        // Convert form data to object
        $.each(formData, function(i, field) {
            if (field.name === 'ratecard') {
                // Remove formatting from ratecard
                data[field.name] = field.value.replace(/\./g, '');
            } else if (field.name === 'auto_fetch') {
                // Store checkbox value
                data[field.name] = true;
            } else {
                data[field.name] = field.value;
            }
        });

        // If checkbox is unchecked, it won't be in formData, so check explicitly
        if (!$('#auto_fetch').is(':checked')) {
            data['auto_fetch'] = false;
        }

        $.ajax({
            type: 'POST',
            url: '<?= site_url("influencer_dummy/save") ?>',
            data: data,
            dataType: 'json',
            beforeSend: function() {
                let loadingMsg = 'Menyimpan...';
                if (data.auto_fetch && $('#url').val()) {
                    loadingMsg = 'Menyimpan & mengambil data engagement...';
                }
                $('.btn-submit').prop('disabled', true).html('<div class="spinner-border spinner-border-sm me-1" role="status"><span class="visually-hidden">Loading...</span></div> ' + loadingMsg);
                $('.form-message').slideUp().html('');
            },
            success: function(response) {
                if (response.status === 'success') {
                    // Show success message
                    let successMsg = 'Data berhasil ditambahkan!';
                    if (data.auto_fetch && data.url && response.data.follower > 0) {
                        successMsg += ' Data engagement berhasil diambil!';
                    }
                    $('.form-message').html('<div class="alert alert-success">' + successMsg + '</div>').slideDown();

                    // Call parent function to add row to table
                    if (typeof window.addNewRowToTable === 'function') {
                        // Pass auto_fetch preference to the function
                        response.data.auto_fetch = data.auto_fetch;
                        window.addNewRowToTable(response.data);
                    }

                    // Close modal after short delay
                    setTimeout(function() {
                        $('#modal-form').modal('hide');
                        // Reset form
                        $('#form-add-influencer')[0].reset();
                    }, 1000);
                } else {
                    $('.form-message').html('<div class="alert alert-danger">' + (response.message || 'Gagal menambahkan data') + '</div>').slideDown();
                }
            },
            error: function(xhr, status, error) {
                $('.form-message').html('<div class="alert alert-danger">Terjadi kesalahan: ' + error + '</div>').slideDown();
            },
            complete: function() {
                $('.btn-submit').prop('disabled', false).html('<i class="bi bi-save me-1"></i> Simpan');
            }
        });
    });
});
</script>
