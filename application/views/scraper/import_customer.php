<form id="importForm" action="<?= base_url('scraper/import_process') ?>" method="POST">
    <div class="modal-body">
        <input type="hidden" name="start_date" value="<?= $_GET['start_date'] ?? '' ?>">
        <input type="hidden" name="until_date" value="<?= $_GET['until_date'] ?? '' ?>">

        <div class="alert alert-info mt-3">
            <i class="bi bi-info-circle"></i> Data akan diambil langsung dari tabel <code>customer_scrap</code> berdasarkan rentang tanggal:
            <strong><?= $_GET['start_date'] ?? '-' ?></strong> s/d <strong><?= $_GET['until_date'] ?? '-' ?></strong>.
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Proses Import</button>
    </div>
</form>

<div class="form-message mt-2"></div>

<script>
$(document).ready(function() {
    $('#importForm').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            beforeSend: function() {
                form.find('button[type="submit"]').prop('disabled', true).html('<i class="bi bi-arrow-repeat"></i> Memproses...');
            },
            success: function(response) {
                $('#modal-form').modal('hide');
                $('.form-message').html(response);
                $('#customerTable').DataTable().ajax.reload();
            },
            error: function() {
                alert('Terjadi kesalahan saat memproses data');
                form.find('button[type="submit"]').prop('disabled', false).html('Proses Import');
            }
        });
    });
});
</script>
