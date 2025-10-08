<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Edit Main Quest</h5>
                <a href="<?= base_url() ?>quest" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="form-message"></div>
            <form action="<?= base_url() ?>quest/main_quest_update" method="POST" id="form-edit">
                <input type="hidden" name="id" value="<?= $data['id'] ?>">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="title" class="form-label">Judul Quest <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="dt[title]"
                               value="<?= $data['title'] ?>" required>
                        <small class="text-muted">Judul yang menarik dan jelas untuk quest ini</small>
                    </div>
                    <div class="col-md-6">
                        <label for="required_position_id" class="form-label">Required Position <span class="text-danger">*</span></label>
                        <select class="form-control" id="required_position_id" name="dt[required_position_id]" required>
                            <option value="">Pilih Position</option>
                            <?php foreach ($positions as $position): ?>
                                <option value="<?= $position['id'] ?>" <?= $data['required_position_id'] == $position['id'] ? 'selected' : '' ?>>
                                    <?= $position['name'] ?> (<?= $position['level_name'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Position minimum untuk mengerjakan quest ini</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="description" class="form-label">Deskripsi Quest <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" name="dt[description]" rows="5" required><?= $data['description'] ?></textarea>
                        <small class="text-muted">Berikan deskripsi yang detail dan jelas tentang apa yang harus dikerjakan</small>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary btn-send">
                            <i class="bi bi-save me-1"></i> Update Quest
                        </button>
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
                $(".btn-send").addClass("disabled").html('<div class="spinner-border spinner-border-sm text-white me-2" role="status"></div>Mengupdate...').attr('disabled', true);
                form.find(".form-message").slideUp().html("");
            },
            success: function(response, textStatus, xhr) {
                console.log(response);
                if (response.indexOf("success") != -1) {
                    $(".form-message").hide().html(response).slideDown("fast");
                    setTimeout(function() {
                        window.location.href = "<?= base_url() ?>quest";
                    }, 2000);
                } else {
                    $(".form-message").hide().html(response).slideDown("fast");
                    $(".btn-send").removeClass("disabled").html('<i class="bi bi-save me-1"></i> Update Quest').attr('disabled', false);
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                $(".btn-send").removeClass("disabled").html('<i class="bi bi-save me-1"></i> Update Quest').attr('disabled', false);
                $(".form-message").hide().html(xhr).slideDown("fast");
            }
        });
        return false;
    });
</script>