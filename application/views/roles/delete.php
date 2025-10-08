<div class="modal-header">
    <h5 class="modal-title">
        <i class="bi bi-exclamation-triangle text-warning me-2"></i>Delete Role
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    <div class="text-center py-3">
        <i class="bi bi-shield-exclamation text-danger" style="font-size: 48px;"></i>
        <h6 class="mt-3 mb-2">Are you sure you want to delete this role?</h6>
        <p class="text-muted">This action cannot be undone. All permissions associated with this role will be removed.</p>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
        <i class="bi bi-x me-1"></i>Cancel
    </button>
    <button type="button" class="btn btn-danger" onclick="confirmDelete(<?= $data['id'] ?>)">
        <i class="bi bi-trash me-1"></i>Delete Role
    </button>
</div>

<script>
function confirmDelete(id) {
    $.ajax({
        type: "POST",
        url: "<?= base_url() ?>/roles/delete",
        data: { id: id },
        success: function(response) {
            $('#popupModal').modal('hide');
            
            if (response.indexOf("success") != -1) {
                Swal.fire({
                    icon: 'success',
                    title: 'Deleted!',
                    text: 'Role has been deleted successfully.',
                    timer: 2000,
                    showConfirmButton: false
                });
                
                // Reload the data
                setTimeout(function() {
                    load_data();
                }, 1000);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: response
                });
            }
        },
        error: function() {
            $('#popupModal').modal('hide');
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred while deleting the role.'
            });
        }
    });
}
</script>