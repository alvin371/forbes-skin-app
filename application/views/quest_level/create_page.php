<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Tambah Quest Level</h5>
                <a href="<?= base_url() ?>/quest_level" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="form-message"></div>
            <form action="<?= base_url() ?>/quest_level/store" method="POST" id="form-create">
                <div class="row mb-3">
                    <div class="col-md-8">
                        <label for="name" class="form-label">Nama Level <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="dt[name]" 
                               placeholder="Contoh: Junior, Intermediate, Senior" required>
                        <small class="text-muted">Nama level harus unik dan tidak boleh sama dengan yang sudah ada</small>
                    </div>
                    <div class="col-md-4">
                        <label for="level_order" class="form-label">Urutan Level <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="level_order" name="dt[level_order]" 
                               placeholder="1" min="1" required>
                        <small class="text-muted">Angka yang lebih kecil akan muncul lebih dulu</small>
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
                        window.location.href = "<?= base_url() ?>/quest_level";
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