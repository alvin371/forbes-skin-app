<div class="container-fluid py-3">
    <div class="form-message"></div>
    <form action="<?= base_url() ?>/user/store" method="POST" id="form-create" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= isset($data['id']) ? $data['id'] : '' ?>">
        
        <!-- User Basic Information Card -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">
                        <i class="bi bi-person me-2"></i>Informasi Dasar User
                    </h5>
                    <a href="<?= base_url() ?>/user" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="full_name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="full_name" name="dt[full_name]" value="<?= isset($data['full_name']) ? $data['full_name'] : '' ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="username" name="dt[username]" value="<?= isset($data['username']) ? $data['username'] : '' ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="dt[email]" value="<?= isset($data['email']) ? $data['email'] : '' ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="dt[password]" value="" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-control" id="role" name="dt[role]" required>
                            <option value="">-- Pilih Role --</option>
                            <?php foreach ($role as $v2) {
                                $selected = (isset($data['role']) && $data['role'] == $v2['id']) ? 'selected' : '';
                            ?>
                                <option <?= $selected ?> value="<?= $v2['id'] ?>"><?= $v2['display_name'] ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-control" id="status" name="dt[status]" required>
                            <?php
                            $arr = ["Aktif", "Tidak Aktif"];
                            foreach ($arr as $v2) {
                                $selected = (isset($data['status']) && $data['status'] == $v2) ? 'selected' : '';
                            ?>
                                <option <?= $selected ?> value="<?= $v2 ?>"><?= $v2 ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="position_id" class="form-label">Posisi Jabatan <span class="text-danger">*</span></label>
                        <select class="form-control" id="position_id" name="profile[position_id]" required>
                            <option value="">-- Pilih Posisi --</option>
                            <?php foreach ($positions as $position) { ?>
                                <option value="<?= $position['id'] ?>"><?= $position['name'] ?> (<?= $position['level_name'] ?>)</option>
                            <?php } ?>
                        </select>
                        <div class="invalid-feedback">
                            Posisi jabatan wajib dipilih untuk menentukan akses quest karyawan
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>Posisi menentukan level quest yang dapat diakses karyawan
                        </small>
                    </div>
                    <div class="col-md-6">
                        <label for="desc" class="form-label">Keterangan</label>
                        <input type="text" class="form-control" id="desc" name="dt[desc]" value="<?= isset($data['desc']) ? $data['desc'] : '' ?>">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="file" class="form-label">Foto Profil</label>
                        <input type="file" class="form-control" id="file" name="file" accept="image/png, image/jpeg, image/jpg">
                        <small class="text-muted">Format: JPG, JPEG, PNG. Maksimal 2MB.</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Profile Information Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">
                    <i class="bi bi-person-badge me-2"></i>Profil Lengkap Karyawan
                </h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="join_date" class="form-label">Tanggal Bergabung</label>
                        <input type="date" class="form-control" id="join_date" name="profile[join_date]" value="">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="jenis_kontrak" class="form-label">Jenis Kontrak</label>
                        <select class="form-control" id="jenis_kontrak" name="profile[jenis_kontrak]">
                            <option value="">-- Pilih Jenis Kontrak --</option>
                            <option value="PKWT">PKWT (Kontrak)</option>
                            <option value="PKWTT">PKWTT (Tetap)</option>
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>PKWT: Perjanjian Kerja Waktu Tertentu, PKWTT: Perjanjian Kerja Waktu Tidak Tertentu
                        </small>
                    </div>
                    <div class="col-md-6">
                        <label for="lama_kontrak" class="form-label">Lama Kontrak <span class="text-muted">(untuk PKWT)</span></label>
                        <input type="text" class="form-control" id="lama_kontrak" name="profile[lama_kontrak]" 
                               placeholder="Contoh: 12 bulan, 2 tahun" style="display:none;">
                        <small class="text-muted" id="contract_help" style="display:none;">
                            <i class="bi bi-clock me-1"></i>Masukkan durasi kontrak untuk karyawan PKWT
                        </small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="birth_place_date" class="form-label">Tempat, Tanggal Lahir</label>
                        <input type="text" class="form-control" id="birth_place_date" name="profile[birth_place_date]" placeholder="Jakarta, 15 Januari 1990">
                    </div>
                    <div class="col-md-6">
                        <label for="gender" class="form-label">Jenis Kelamin</label>
                        <select class="form-control" id="gender" name="profile[gender]">
                            <option value="">-- Pilih Jenis Kelamin --</option>
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="religion" class="form-label">Agama</label>
                        <select class="form-control" id="religion" name="profile[religion]">
                            <option value="">-- Pilih Agama --</option>
                            <option value="Islam">Islam</option>
                            <option value="Kristen">Kristen</option>
                            <option value="Katolik">Katolik</option>
                            <option value="Hindu">Hindu</option>
                            <option value="Buddha">Buddha</option>
                            <option value="Konghucu">Konghucu</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="marital_status" class="form-label">Status Pernikahan</label>
                        <select class="form-control" id="marital_status" name="profile[marital_status]">
                            <option value="">-- Pilih Status --</option>
                            <option value="Belum Menikah">Belum Menikah</option>
                            <option value="Menikah">Menikah</option>
                            <option value="Cerai">Cerai</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="phone_number" class="form-label">Nomor Telepon</label>
                        <input type="text" class="form-control" id="phone_number" name="profile[phone_number]" placeholder="+62812345678">
                    </div>
                    <div class="col-md-6">
                        <label for="nik" class="form-label">NIK</label>
                        <input type="text" class="form-control" id="nik" name="profile[nik]" placeholder="3171234567890001">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="full_address" class="form-label">Alamat Lengkap</label>
                        <textarea class="form-control" id="full_address" name="profile[full_address]" rows="3" placeholder="Alamat lengkap sesuai KTP"></textarea>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="bank_name" class="form-label">Nama Bank</label>
                        <input type="text" class="form-control" id="bank_name" name="profile[bank_name]" placeholder="BCA, Mandiri, BNI, dll">
                    </div>
                    <div class="col-md-4">
                        <label for="bank_account_number" class="form-label">Nomor Rekening</label>
                        <input type="text" class="form-control" id="bank_account_number" name="profile[bank_account_number]" placeholder="1234567890">
                    </div>
                    <div class="col-md-4">
                        <label for="account_holder_name" class="form-label">Nama Pemegang Rekening</label>
                        <input type="text" class="form-control" id="account_holder_name" name="profile[account_holder_name]" placeholder="Sesuai dengan rekening bank">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="hobby" class="form-label">Hobi</label>
                        <textarea class="form-control" id="hobby" name="profile[hobby]" rows="2" placeholder="Ceritakan hobi Anda"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label for="hobby_reason" class="form-label">Alasan Hobi</label>
                        <textarea class="form-control" id="hobby_reason" name="profile[hobby_reason]" rows="2" placeholder="Mengapa Anda menyukai hobi tersebut?"></textarea>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="favorite_food_drink" class="form-label">Makanan/Minuman Favorit</label>
                        <textarea class="form-control" id="favorite_food_drink" name="profile[favorite_food_drink]" rows="2" placeholder="Makanan dan minuman favorit"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label for="food_drink_reason" class="form-label">Alasan Favorit</label>
                        <textarea class="form-control" id="food_drink_reason" name="profile[food_drink_reason]" rows="2" placeholder="Mengapa menjadi favorit?"></textarea>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="favorite_color" class="form-label">Warna Favorit</label>
                        <input type="text" class="form-control" id="favorite_color" name="profile[favorite_color]" placeholder="Merah, Biru, Hijau, dll">
                    </div>
                    <div class="col-md-6">
                        <label for="ktp_photo" class="form-label">Foto KTP</label>
                        <input type="file" class="form-control" id="ktp_photo" name="ktp_photo" accept="image/png, image/jpeg, image/jpg">
                        <small class="text-muted">Upload foto KTP. Format: JPG, JPEG, PNG. Maksimal 2MB.</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary btn-send">
                    <i class="bi bi-save me-1"></i> Simpan Data
                </button>
            </div>
        </div>
    </form>
</div>

<script type="text/javascript">
    // Contract type field logic
    $("#jenis_kontrak").on('change', function() {
        var contractType = $(this).val();
        var contractDurationField = $("#lama_kontrak");
        var contractHelp = $("#contract_help");
        
        if (contractType === "PKWT") {
            contractDurationField.show().attr('required', true);
            contractHelp.show();
        } else {
            contractDurationField.hide().removeAttr('required').val('');
            contractHelp.hide();
        }
    });

    // Position field validation
    $("#position_id").on('change', function() {
        var positionValue = $(this).val();
        if (positionValue === "") {
            $(this).addClass('is-invalid');
            $(this).next('.invalid-feedback').show();
        } else {
            $(this).removeClass('is-invalid').addClass('is-valid');
            $(this).next('.invalid-feedback').hide();
        }
    });

    $("#form-create").submit(function() {
        var form = $(this);
        var isValid = true;
        
        // Validate position selection if any profile data is filled
        var hasProfileData = false;
        $('input[name^="profile"], select[name^="profile"], textarea[name^="profile"]').each(function() {
            if ($(this).val() !== '' && $(this).attr('name') !== 'profile[position_id]') {
                hasProfileData = true;
                return false;
            }
        });
        
        // Check for KTP photo upload
        if ($('#ktp_photo')[0].files.length > 0) {
            hasProfileData = true;
        }
        
        if (hasProfileData) {
            var positionId = $('#position_id').val();
            if (positionId === "") {
                $('#position_id').addClass('is-invalid');
                $('#position_id').focus();
                
                // Show user-friendly message
                var alertHtml = '<div class="alert alert-warning alert-dismissible fade show" role="alert">' +
                    '<i class="bi bi-exclamation-triangle me-2"></i>' +
                    '<strong>Perhatian!</strong> Untuk melengkapi profil karyawan, Anda harus memilih posisi jabatan terlebih dahulu. ' +
                    'Posisi jabatan menentukan level quest yang dapat diakses oleh karyawan.' +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                    '</div>';
                    
                $(".form-message").html(alertHtml).slideDown("fast");
                return false;
            }
        }
        
        var mydata = new FormData(this);
        $.ajax({
            type: "POST",
            url: form.attr("action"),
            data: mydata,
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function() {
                $(".btn-send").addClass("disabled").html('<div class="spinner-border spinner-border-sm text-white me-2" role="status"></div>Menyimpan...').attr('disabled', true);
                form.find(".form-message").slideUp().html("");
            },
            success: function(response, textStatus, xhr) {
                console.log(response);
                if (response.indexOf("success") != -1) {
                    $(".form-message").hide().html(response).slideDown("fast");
                    setTimeout(function() {
                        window.location.href = "<?= base_url() ?>/user";
                    }, 2000);
                } else {
                    $(".form-message").hide().html(response).slideDown("fast");
                    $(".btn-send").removeClass("disabled").html('<i class="bi bi-save me-1"></i> Simpan Data').attr('disabled', false);
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                $(".btn-send").removeClass("disabled").html('<i class="bi bi-save me-1"></i> Simpan Data').attr('disabled', false);
                $(".form-message").hide().html(xhr).slideDown("fast");
            }
        });
        return false;
    });
</script>