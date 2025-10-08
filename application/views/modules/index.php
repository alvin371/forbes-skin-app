<div class="row">
    <div class="col-12">
        <div class="box-main">
            <div class="row align-items-center mb-4">
                <div class="col">
                    <h4 class="mb-0">
                        <i class="bi bi-gear me-2"></i>
                        Module Management
                    </h4>
                    <small class="text-muted">Manage system modules and their configuration</small>
                </div>
            </div>

            <!-- Module Management Content -->
            <div id="modules-content">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading modules...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Load initial content
    loadModulesList();

    function loadModulesList() {
        $('#modules-content').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">Loading modules...</p></div>');
        
        $.get('<?= base_url() ?>modules/modules_list')
            .done(function(data) {
                $('#modules-content').html(data);
            })
            .fail(function() {
                $('#modules-content').html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Failed to load modules</div>');
            });
    }
});
</script>