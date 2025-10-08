<div class="modal-header">
    <h5 class="modal-title">
        <i class="bi bi-exclamation-triangle text-warning me-2"></i>Delete Module
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    <div class="alert alert-warning" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Warning!</strong> This action cannot be undone.
    </div>
    <p>Are you sure you want to delete this module?</p>
    <p class="text-muted mb-0">
        <small>This will permanently remove the module from the system. Make sure no roles are using this module before deletion.</small>
    </p>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
        <i class="bi bi-x me-1"></i>Cancel
    </button>
    <button type="button" class="btn btn-danger" onclick="confirmDelete()">
        <i class="bi bi-trash me-1"></i>Delete Module
    </button>
</div>

<script>
function confirmDelete() {
    const moduleId = '<?= $data['id'] ?>';
    
    // Close modal first
    $('#popupModal').modal('hide');
    
    // Show loading state with SweetAlert
    Swal.fire({
        title: 'Deleting...',
        text: 'Please wait while we delete the module.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    $.ajax({
        type: "POST",
        url: "<?= base_url() ?>/modules/delete",
        data: { id: moduleId },
        success: function(response) {
            if (response.indexOf("success") != -1) {
                Swal.fire({
                    icon: 'success',
                    title: 'Deleted!',
                    text: 'Module has been deleted successfully.',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    // Reload the data table
                    if (typeof loadModuleData === 'function') {
                        loadModuleData();
                    } else if (typeof load_data === 'function') {
                        load_data();
                    } else {
                        window.location.reload();
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    html: response
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred while deleting the module. Please try again.'
            });
        }
    });
}
</script>