<div class="modal-header">
    <h5 class="modal-title">Konfirmasi Hapus</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <div class="text-center">
        <i class="bi bi-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
        <h5 class="mt-3">Apakah Anda yakin?</h5>
        <p class="text-muted">
            Data quest level ini akan dihapus permanen dan tidak dapat dikembalikan.
            <br><strong>Pastikan tidak ada posisi yang menggunakan level ini!</strong>
        </p>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="button" class="btn btn-danger" onclick="delete_data(<?= $data['id'] ?>)">
        <i class="bi bi-trash me-1"></i> Ya, Hapus
    </button>
</div>

<script type="text/javascript">
    function delete_data(id) {
        $.ajax({
            type: "POST",
            url: "<?= base_url() ?>/quest_level/delete",
            data: { id: id },
            beforeSend: function() {
                $(".btn-danger").addClass("disabled").html('<div class="spinner-border spinner-border-sm text-white me-2" role="status"></div>Menghapus...');
            },
            success: function(response) {
                if (response.indexOf("success") != -1) {
                    $("#popupModal").modal('hide');
                    load_data(); // Reload the data
                    // Show success message
                    $(".card-body").prepend('<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                        '<i class="bi bi-check-circle me-2"></i>Data berhasil dihapus!' +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                        '</div>');
                } else {
                    $("#popupModal .modal-body").prepend('<div class="alert alert-danger">' + response + '</div>');
                    $(".btn-danger").removeClass("disabled").html('<i class="bi bi-trash me-1"></i> Ya, Hapus');
                }
            },
            error: function() {
                $(".btn-danger").removeClass("disabled").html('<i class="bi bi-trash me-1"></i> Ya, Hapus');
            }
        });
    }
</script>