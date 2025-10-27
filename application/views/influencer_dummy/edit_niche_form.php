<div class="container">
    <?php if ($this->session->flashdata('message')): ?>
        <div class="alert alert-success"><?= $this->session->flashdata('message') ?></div>
    <?php endif; ?>
    
    <?php if ($this->session->flashdata('errors')): ?>
        <div class="alert alert-danger"><?= $this->session->flashdata('errors') ?></div>
    <?php endif; ?>
    
    <form action="<?= site_url('influencer_dummy/save_niche') ?>" method="post">
        <div class="mb-3">
            <input type="text" class="form-control" id="niche" name="niche" placeholder="Tambah Category KOL Baru" required>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
    </form>
    
    <div class="mt-4">
        <h5>List Category KOL</h5>
        <div class="d-flex flex-wrap gap-2 mt-3" id="niches-list">
            <?php foreach ($niches as $niche): ?>
                <div class="p-2 bg-primary text-white rounded-2 d-flex align-items-center niche-item" data-niche="<?= htmlspecialchars($niche->niche) ?>">
                    <span class="me-1"><?= htmlspecialchars($niche->niche) ?></span>
                    <a href="#" class="text-white lh-1 delete-niche" 
                       style="text-decoration: none; opacity: 0.7;">
                        <i class="bi bi-x-circle" style="font-size: 0.9rem;"></i>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Handle form submission
    $('form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        $.ajax({
            type: form.attr('method'),
            url: form.attr('action'),
            data: form.serialize(),
            success: function(response) {
                $("#load-form").load('<?= site_url('influencer_dummy/edit_niche') ?>');
            },
            error: function(xhr) {
                $("#load-form").html(xhr.responseText);
            }
        });
    });

    // Handle niche deletion
    $(document).on('click', '.delete-niche', function() {
        var nicheItem = $(this).closest('.niche-item');
        var niche = nicheItem.data('niche');
        
        $.ajax({
            url: '<?= site_url('influencer_dummy/delete_niche/') ?>' + encodeURIComponent(niche),
            type: 'GET',
            success: function(response) {
                if (response === 'success') {
                    nicheItem.fadeOut(200, function() {
                        $(this).remove();
                    });
                }
            },
            error: function() {
                console.log('Error deleting niche');
            }
        });
    });
});
</script>