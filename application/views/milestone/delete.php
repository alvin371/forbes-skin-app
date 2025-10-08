<div class="modal-header">
    <h5 class="modal-title">Konfirmasi Hapus</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    <p>Apakah Anda yakin ingin menghapus milestone ini?</p>
    <p class="text-muted">Tindakan ini tidak dapat dibatalkan.</p>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
    <button type="button" class="btn btn-danger" onclick="confirmDelete()">Hapus</button>
</div>

<script>
function confirmDelete() {
    $.ajax({
        type: "POST",
        url: "<?= base_url() ?>milestone/milestone_delete",
        data: { id: '<?= $data['id'] ?>' },
        success: function(response) {
            if (response.indexOf("success") != -1) {
                $('#exampleModal').modal('hide');
                if (typeof loadMilestoneData === 'function') {
                    loadMilestoneData();
                }
                // Show success message
                Swal.fire('Success!', 'Milestone berhasil dihapus.', 'success');
            } else {
                Swal.fire('Error!', 'Gagal menghapus milestone.', 'error');
            }
        },
        error: function() {
            Swal.fire('Error!', 'Terjadi kesalahan saat menghapus milestone.', 'error');
        }
    });
}
</script>