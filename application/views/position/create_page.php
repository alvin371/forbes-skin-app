<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Tambah Position</h5>
                <a href="<?= base_url() ?>/position" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="form-message"></div>
            <form action="<?= base_url() ?>/position/store" method="POST" id="form-create">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Nama Position <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="dt[name]" 
                               placeholder="Contoh: Frontend Developer" required>
                        <small class="text-muted">Nama posisi yang akan ditampilkan dalam sistem</small>
                    </div>
                    <div class="col-md-6">
                        <label for="level_id" class="form-label">Quest Level</label>
                        <select class="form-control" id="level_id" name="dt[level_id]">
                            <option value="">Pilih Quest Level (Opsional)</option>
                            <?php foreach ($quest_levels as $level): ?>
                                <option value="<?= $level['id'] ?>"><?= $level['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Level untuk kategorisasi visual saja, tidak mempengaruhi workflow</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="department" class="form-label">Department</label>
                        <input type="text" class="form-control" id="department" name="dt[department]"
                               placeholder="Contoh: Engineering, Marketing, QA">
                        <small class="text-muted">Department untuk pengelompokan posisi</small>
                    </div>
                    <div class="col-md-6">
                        <label for="parent_position_id" class="form-label">Parent Position (Reports To)</label>
                        <select class="form-control" id="parent_position_id" name="dt[parent_position_id]">
                            <option value="">No Parent (Root Position)</option>
                            <?php if (isset($all_positions)): ?>
                                <?php foreach ($all_positions as $pos): ?>
                                    <option value="<?= $pos['id'] ?>">
                                        <?= $pos['name'] ?>
                                        <?php if (!empty($pos['department'])): ?>
                                            (<?= $pos['department'] ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <small class="text-muted">Select the position this role reports to in the org chart</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="position_order" class="form-label">Position Order</label>
                        <input type="number" class="form-control" id="position_order" name="dt[position_order]"
                               placeholder="0" min="0" value="0">
                        <small class="text-muted">Urutan posisi dalam level yang sama (0 = pertama)</small>
                    </div>
                    <div class="col-md-6">
                        <!-- Empty column for layout balance -->
                    </div>
                </div>

                <!-- Career Paths Section (Optional) -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card" style="border: 2px solid #e6f7ff; background: #f0f5ff;">
                            <div class="card-header" style="background: linear-gradient(135deg, #1890ff, #096dd9); color: white;">
                                <h5 class="mb-0">
                                    <i class="bi bi-signpost-split"></i> Career Advancement Paths <span style="font-size: 12px; opacity: 0.8;">(Optional)</span>
                                </h5>
                                <small style="opacity: 0.9;">Define possible career progression routes from this position (can be added later)</small>
                            </div>
                            <div class="card-body">
                                <?php
                                // For create page, no existing paths
                                $existing_career_paths = [];
                                include(APPPATH . 'views/position/career_paths_editor.php');
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary btn-send">
                            <i class="bi bi-save me-1"></i> Simpan Data
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
    $("#form-create").submit(function() {
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
                $(".btn-send").addClass("disabled").html('<div class="spinner-border spinner-border-sm text-white me-2" role="status"></div>Menyimpan...').attr('disabled', true);
                form.find(".form-message").slideUp().html("");
            },
            success: function(response, textStatus, xhr) {
                console.log(response);
                if (response.indexOf("success") != -1) {
                    $(".form-message").hide().html(response).slideDown("fast");
                    setTimeout(function() {
                        window.location.href = "<?= base_url() ?>/position";
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