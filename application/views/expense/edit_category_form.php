<div class="container">
    <?php if ($this->session->flashdata('message')): ?>
        <div class="alert alert-success"><?= $this->session->flashdata('message') ?></div>
    <?php endif; ?>
    
    <?php if ($this->session->flashdata('errors')): ?>
        <div class="alert alert-danger"><?= $this->session->flashdata('errors') ?></div>
    <?php endif; ?>
    
    <form action="<?= site_url('expense/save_category') ?>" method="post">
        <div class="mb-3">
            <input type="text" class="form-control" id="category" name="category" placeholder="Tambah Category Baru" required>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
    </form>
    
    <div class="mt-4">
        <h5>List Kategori</h5>
        <div class="d-flex flex-wrap gap-2 mt-3" id="categories-list">
            <?php foreach ($categories as $cat): ?>
                <div class="p-2 bg-primary text-white rounded-2 d-flex align-items-center category-item" data-category="<?= htmlspecialchars($cat->category) ?>">
                    <span class="me-1"><?= htmlspecialchars($cat->category) ?></span>
                    <a href="#" class="text-white lh-1 delete-category" 
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
                $("#load-form").load('<?= site_url('expense/edit_category') ?>');
            },
            error: function(xhr) {
                $("#load-form").html(xhr.responseText);
            }
        });
    });

    // Handle category deletion
    $(document).on('click', '.delete-category', function() {
        var categoryItem = $(this).closest('.category-item');
        var category = categoryItem.data('category');
        
        $.ajax({
            url: '<?= site_url('expense/delete_category/') ?>' + encodeURIComponent(category),
            type: 'GET',
            success: function(response) {
                if (response === 'success') {
                    categoryItem.fadeOut(200, function() {
                        $(this).remove();
                    });
                }
            },
            error: function() {
                console.log('Error deleting category');
            }
        });
    });
});
</script>
