<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Edit Milestone Quest</h5>
                <a href="<?= base_url() ?>milestone" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="form-message"></div>
            <form action="<?= base_url() ?>milestone/milestone_update" method="POST" id="form-edit" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $data['id'] ?>">
                
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="title" class="form-label">Milestone Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="dt[title]" 
                               value="<?= $data['title'] ?>" placeholder="Masukkan judul milestone..." required>
                        <small class="text-muted">Nama milestone yang jelas dan menarik</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" name="dt[description]" rows="3" 
                                  placeholder="Jelaskan detail milestone dan kondisi untuk mencapainya..." required><?= $data['description'] ?></textarea>
                        <small class="text-muted">Deskripsi yang jelas tentang milestone ini</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="milestone_type" class="form-label">Milestone Type <span class="text-danger">*</span></label>
                        <select class="form-control" id="milestone_type" name="dt[milestone_type]" required>
                            <option value="">-- Pilih Type --</option>
                            <option value="quest_count" <?= $data['milestone_type'] == 'quest_count' ? 'selected' : '' ?>>Quest Count (Jumlah quest yang diselesaikan)</option>
                            <option value="total_points" <?= $data['milestone_type'] == 'total_points' ? 'selected' : '' ?>>Total Points (Total poin yang dikumpulkan)</option>
                            <option value="monthly_points" <?= $data['milestone_type'] == 'monthly_points' ? 'selected' : '' ?>>Monthly Points (Poin dalam satu bulan)</option>
                        </select>
                        <small class="text-muted">Jenis milestone berdasarkan pencapaian</small>
                    </div>
                    <div class="col-md-6">
                        <label for="target_value" class="form-label">Target Value <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="target_value" name="dt[target_value]" 
                               min="0" value="<?= $data['target_value'] ?>" placeholder="0" required>
                        <small class="text-muted">Nilai target yang harus dicapai</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="gambar_animasi" class="form-label">Animation Image</label>
                        <input type="file" class="form-control" id="gambar_animasi" name="gambar_animasi" 
                               accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                        <small class="text-muted">Upload gambar animasi untuk milestone (opsional). Format: JPG, PNG, GIF, WEBP. Max: 2MB</small>
                        <?php if (!empty($data['gambar_animasi'])): ?>
                            <div class="mt-2">
                                <small class="text-success">Current image: <?= $data['gambar_animasi'] ?></small>
                                <br>
                                <img src="<?= base_url() ?>assets/uploads/milestone_animations/<?= $data['gambar_animasi'] ?>" 
                                     alt="Current Animation" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 2px solid #e6f7ff;">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="reward_description" class="form-label">Reward Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reward_description" name="dt[reward_description]" rows="3" 
                                  placeholder="Jelaskan reward atau manfaat yang akan didapat setelah mencapai milestone ini..." required><?= $data['reward_description'] ?></textarea>
                        <small class="text-muted">Deskripsikan reward menarik yang memotivasi karyawan</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_repeatable" name="dt[is_repeatable]" value="1" <?= $data['is_repeatable'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_repeatable">
                                Repeatable Milestone
                            </label>
                        </div>
                        <small class="text-muted">Centang jika milestone dapat dicapai berulang kali</small>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="dt[is_active]" value="1" <?= $data['is_active'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_active">
                                Active Status
                            </label>
                        </div>
                        <small class="text-muted">Milestone aktif dan dapat dicapai karyawan</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <h6><i class="bi bi-info-circle me-2"></i>Informasi Milestone</h6>
                            <ul class="mb-0">
                                <li><strong>Quest Count:</strong> Berdasarkan jumlah side quest yang berhasil diselesaikan</li>
                                <li><strong>Total Points:</strong> Berdasarkan total poin yang dikumpulkan sepanjang waktu</li>
                                <li><strong>Monthly Points:</strong> Berdasarkan poin yang dikumpulkan dalam satu bulan</li>
                                <li><strong>Automatic Detection:</strong> Sistem akan otomatis mendeteksi pencapaian milestone</li>
                                <li><strong>Image Update:</strong> Upload gambar baru untuk mengganti gambar yang ada</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary btn-send">
                            <i class="bi bi-save me-1"></i> Update Milestone
                        </button>
                        <a href="<?= base_url() ?>milestone" class="btn btn-secondary ms-2">
                            <i class="bi bi-x-circle me-1"></i> Batal
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
    $("#form-edit").submit(function() {
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
                $(".btn-send").addClass("disabled").html('<div class="spinner-border spinner-border-sm text-white me-2" role="status"></div>Updating...').attr('disabled', true);
                form.find(".form-message").slideUp().html("");
            },
            success: function(response, textStatus, xhr) {
                console.log(response);
                if (response.indexOf("success") != -1) {
                    $(".form-message").hide().html(response).slideDown("fast");
                    setTimeout(function() {
                        window.location.href = "<?= base_url() ?>milestone";
                    }, 2000);
                } else {
                    $(".form-message").hide().html(response).slideDown("fast");
                    $(".btn-send").removeClass("disabled").html('<i class="bi bi-save me-1"></i> Update Milestone').attr('disabled', false);
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                $(".btn-send").removeClass("disabled").html('<i class="bi bi-save me-1"></i> Update Milestone').attr('disabled', false);
                $(".form-message").hide().html(xhr).slideDown("fast");
            }
        });
        return false;
    });
</script>