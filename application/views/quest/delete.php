<div class="modal-header">
    <h5 class="modal-title">
        <?php if ($type == 'main'): ?>
            Konfirmasi Hapus Main Quest
        <?php else: ?>
            Konfirmasi Hapus Side Quest
        <?php endif; ?>
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <div class="text-center">
        <i class="bi bi-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
        <h5 class="mt-3">Apakah Anda yakin?</h5>
        <p class="text-muted">
            <?php if ($type == 'main'): ?>
                Main quest ini akan dihapus permanen dan tidak dapat dikembalikan.
                <br><strong>Quest yang sudah memiliki submissions tidak dapat dihapus!</strong>
            <?php else: ?>
                Side quest ini akan dihapus permanen dan tidak dapat dikembalikan.
                <br><strong>Quest yang sudah memiliki submissions tidak dapat dihapus!</strong>
            <?php endif; ?>
        </p>
        <div class="alert alert-warning mt-3">
            <i class="bi bi-info-circle me-2"></i>
            <strong>Peringatan:</strong> Pastikan tidak ada employee yang sudah mengerjakan quest ini sebelum menghapus.
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="button" class="btn btn-danger" onclick="delete_data(<?= $data['id'] ?>)">
        <i class="bi bi-trash me-1"></i> Ya, Hapus Quest
    </button>
</div>

<script type="text/javascript">
    function delete_data(id) {
        var deleteUrl = '';
        <?php if ($type == 'main'): ?>
            deleteUrl = "<?= base_url() ?>quest/main_quest_delete";
        <?php else: ?>
            deleteUrl = "<?= base_url() ?>quest/side_quest_delete";
        <?php endif; ?>
        
        $.ajax({
            type: "POST",
            url: deleteUrl,
            data: { id: id },
            beforeSend: function() {
                $(".btn-danger").addClass("disabled").html('<div class="spinner-border spinner-border-sm text-white me-2" role="status"></div>Menghapus...');
            },
            success: function(response) {
                if (response.indexOf("success") != -1) {
                    $("#popupModal").modal('hide');
                    // Reload the appropriate tab data
                    <?php if ($type == 'main'): ?>
                        if (typeof loadMainQuestData === 'function') {
                            loadMainQuestData();
                        }
                    <?php else: ?>
                        if (typeof loadSideQuestData === 'function') {
                            loadSideQuestData();
                        }
                    <?php endif; ?>
                    
                    // Show success message
                    $(".card-body").prepend('<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                        '<i class="bi bi-check-circle me-2"></i><?php echo ($type == "main" ? "Main" : "Side"); ?> quest berhasil dihapus!' +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                        '</div>');
                } else {
                    $("#popupModal .modal-body").prepend('<div class="alert alert-danger">' + response + '</div>');
                    $(".btn-danger").removeClass("disabled").html('<i class="bi bi-trash me-1"></i> Ya, Hapus Quest');
                }
            },
            error: function() {
                $(".btn-danger").removeClass("disabled").html('<i class="bi bi-trash me-1"></i> Ya, Hapus Quest');
            }
        });
    }
</script>