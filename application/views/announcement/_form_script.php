<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    (function () {
        var REDIRECT = "<?= $redirect ?>";

        function syncSub() {
            var cat = $('#ann-category').val();
            var $sub = $('#ann-subcategory');
            var current = $sub.attr('data-current') || '';
            var prev = $sub.val() || current;
            $sub.empty().append('<option value="">- Pilih Subkategori -</option>');
            if (cat && ANN_FORM_CATEGORIES[cat]) {
                ANN_FORM_CATEGORIES[cat].forEach(function (sc) {
                    var sel = (sc === prev) ? ' selected' : '';
                    $sub.append('<option value="' + sc.replace(/"/g, '&quot;') + '"' + sel + '>' + sc + '</option>');
                });
            }
        }

        function initEditor() {
            if (typeof tinymce === 'undefined') { setTimeout(initEditor, 300); return; }
            tinymce.init({
                selector: '#ann-content',
                height: 360,
                menubar: false,
                branding: false,
                plugins: 'lists link image table code help wordcount',
                toolbar: 'styles | bold italic underline | forecolor | alignleft aligncenter alignright | bullist numlist | link image | table | code fullscreen help'
            });
        }

        $(document).ready(function () {
            syncSub();
            $('#ann-category').on('change', function () {
                $('#ann-subcategory').attr('data-current', '');
                syncSub();
            });
            initEditor();

            $('#form-announcement').on('submit', function () {
                if (typeof tinymce !== 'undefined') { tinymce.triggerSave(); }
                var form = $(this);
                var mydata = new FormData(this);
                $.ajax({
                    type: 'POST',
                    url: form.attr('action'),
                    data: mydata,
                    cache: false,
                    contentType: false,
                    processData: false,
                    beforeSend: function () {
                        $('.btn-send').addClass('disabled').html('<div class="spinner-border spinner-border-sm text-white me-2" role="status"></div>Menyimpan...').attr('disabled', true);
                        form.find('.form-message').slideUp().html('');
                    },
                    success: function (response) {
                        if (response.indexOf('success') !== -1) {
                            $('.form-message').hide().html(response).slideDown('fast');
                            setTimeout(function () { window.location.href = REDIRECT; }, 1500);
                        } else {
                            $('.form-message').hide().html(response).slideDown('fast');
                            $('.btn-send').removeClass('disabled').html('<i class="bi bi-save me-1"></i> Simpan Data').attr('disabled', false);
                        }
                    },
                    error: function (xhr) {
                        $('.btn-send').removeClass('disabled').html('<i class="bi bi-save me-1"></i> Simpan Data').attr('disabled', false);
                        $('.form-message').hide().html(xhr.responseText || 'Terjadi kesalahan.').slideDown('fast');
                    }
                });
                return false;
            });
        });
    })();
</script>
