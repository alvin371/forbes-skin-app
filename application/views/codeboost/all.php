<div class="w-100">
<style>
    .select2-container .select2-selection--multiple {
        min-height: 45px;
        border-radius: 0.5rem !important;
        border: 1px solid #ced4da;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        padding: 6px;
        background-color: #f8f9fa;
        color: #212529;
        border-radius: 0.25rem;
        margin-right: 4px;
    }

    .select2-container .select2-search--inline .select2-search__field {
        padding-top: 6px !important;
        font-size: 14px;
    }
</style>
<style>
    .btn-platform {
        min-width: 100px;
    }
    
    .copy-btn {
        padding: 0.15rem 0.3rem;
        font-size: 0.8rem;
    }
    
    .btn-youtube {
        color: #FF0000;
        border-color: #FF0000;
    }
    .btn-instagram {
        color: #E1306C;
        border-color: #E1306C;
    }
    .btn-tiktok {
        color: #000000;
        border-color: #000000;
    }
    .btn-facebook {
        color: #1877F2;
        border-color: #1877F2;
    }
</style>
    <div class="row align-items-center">
        <div class="col-lg-12 mb-3">
            <h3 class="text-primary fw-600">CODEBOOST</h3>
        </div>
        <div class="col-lg-12">
            <form id="filterForm">
                <div class="row">
                    <div class="col-lg-4">
                        <div class="input-with-button">
                            <input type="hidden" name="product_name" id="productNameHidden">

                            <select class="form-control select2" id="productSearch" multiple>
                                <?php
                                $productNames = isset($_GET['product_name']) ? explode(',', $_GET['product_name']) : [];
                                ?>
                                <?php if (!empty($productNames)): ?>
                                    <?php foreach ($productNames as $name): ?>
                                        <option value="<?= htmlspecialchars($name) ?>" selected><?= htmlspecialchars($name) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                                    
                            <script>
                                $(function () {
                                    $('#productSearch').select2({
                                        minimumInputLength: 1,
                                        placeholder: 'Cari produk...',
                                        ajax: {
                                            dataType: 'json',
                                            url: '<?= base_url() ?>/ajax/get-product-list',
                                            delay: 100,
                                            data: function (params) {
                                                return { search: params.term };
                                            },
                                            processResults: function (data) {
                                                return { results: data };
                                            }
                                        },
                                        language: {
                                            inputTooShort: function () {
                                                return "Masukkan 1 karakter atau lebih";
                                            }
                                        }
                                    });
                                });
                            </script>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="d-flex">
                            <input type="text" class="form-control" id="tanggal" placeholder="Pilih rentang tanggal...">
                            <input type="hidden" name="start_date" id="start_date" value="<?= $_GET['start_date'] ?? $start_date ?>">
                            <input type="hidden" name="until_date" id="end_date" value="<?= $_GET['until_date'] ?? $until_date ?>">
                        </div>
                        <script>
                            get_filter();
                            function get_filter() {
                                $.ajax({
                                    dataType: "json",
                                    url: '<?= base_url() ?>/ajax/get-filter',
                                    data: {
                                        start_date: "<?= $_GET['start_date'] ?? $start_date ?>",
                                        until_date: "<?= $_GET['until_date'] ?? $until_date ?>",
                                    },
                                    success: function(response) {
                                        $("#tanggal").after(response.html);
                                        $('#tanggal').on('apply.daterangepicker', function() {
                                            table.ajax.reload();
                                        });
                                    },
                                    error: function(xhr, status, error) {
                                        console.error("Error loading filter:", error);
                                    }
                                });
                            }
                        </script>
                    </div>
                    <div class="col-lg-4">
                    <div class="d-flex justify-content-between">
                        <button class="btn btn-edit-active" type="submit">
                            <i class="bi bi-search fs-16"></i> Cari Data
                        </button>
                        <button id="exportExcel" class="btn btn-success" type="button">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Export Excel
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="col-lg-12">
    <div class="form-message"></div>
    <div class="table-responsive">
        <table id="codeboostTable" class="table table-bordered table-striped" style="width:100%">
            <thead>
                <tr class="bg-blue-2 text-white">
                    <th class="text-center">#</th>
                    <th class="text-start">NAMA KOL</th>
                    <th class="text-center">PRODUK</th>
                    <th class="text-center">TANGGAL POSTING</th>
                    <th class="text-center">VIEWS</th>
                    <th class="text-center">LINK VT</th>
                </tr>
            </thead>
            <tbody>
                <!-- Data akan diisi melalui DataTables -->
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    var table;
    table = $('#codeboostTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "<?= base_url('codeboost/get_data') ?>",
            "type": "GET",
            "data": function(d) {
                d.start_date = $('#start_date').val();
                d.until_date = $('#end_date').val();
                d.product_name = $('#productNameHidden').val();
            }
        },
        "columns": [
            {
                "data": null,
                "render": function(data, type, row, meta) {
                    const nomor = meta.row + meta.settings._iDisplayStart + 1;
                    return '<span class="text-blue fw-600">#' + nomor + '</span>';
                },
                "orderable": false
            },
            { "data": "1" },
            { "data": "2" },
            { 
                "data": "3",
                "render": function(data, type, row) {
                    return '<span data-sort="'+data+'">'+data+'</span>';
                }
            },
            { 
                "data": "4",
                "className": "text-end",
                "render": function(data, type, row) {
                    return '<span data-sort="'+data.replace(/,/g, '')+'">'+data+'</span>';
                }
            },
            { 
                "data": "5",
                "orderable": false,
                "render": function(data, type, row) {
                    return data;
                }
            },
        ],
        "order": [[0, 'desc']],
        "language": {
            "lengthMenu": "Tampilkan _MENU_ data per halaman",
            "zeroRecords": "Tidak ada data yang ditemukan",
            "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            "infoEmpty": "Menampilkan 0 sampai 0 dari 0 data",
            "infoFiltered": "(disaring dari _MAX_ total data)",
            "paginate": {
                "first": '<i class="bi bi-chevron-double-left"></i>',
                "last": '<i class="bi bi-chevron-double-right"></i>',
                "next": '<i class="bi bi-chevron-right"></i>',
                "previous": '<i class="bi bi-chevron-left"></i>'
            }
        },
        "dom": "<'row'<'col-sm-12 col-md-6'><'col-sm-12 col-md-6 d-flex justify-content-end'l>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",

        "lengthMenu": [10, 20, 50, 100, 500],
        "pageLength": <?= $_GET['limit'] ?? 10 ?>,
    });

    $('#filterForm').on('submit', function (e) {
        e.preventDefault();
        const selectedTexts = $('#productSearch').select2('data').map(item => item.text);
        $('#productNameHidden').val(selectedTexts.length ? selectedTexts.join(',') : '');
        table.ajax.reload();
    });

    $(document).on('click', '.copy-btn', function () {
        const btn = $(this);
        const icon = btn.find('i');
        const originalColor = btn.css('color');

        navigator.clipboard.writeText(btn.data('clipboard-text')).then(function () {
            icon.removeClass('bi-clipboard').addClass('bi-check');
            btn.css('color', 'green');

            setTimeout(function () {
                icon.removeClass('bi-check').addClass('bi-clipboard');
                btn.css('color', originalColor);
            }, 1000);
        }).catch(function (err) {
            console.error('Gagal menyalin teks: ', err);
        });
    });

    $('#exportExcel').on('click', function () {
        const selectedTexts = $('#productSearch').select2('data').map(item => item.text);
        $('#productNameHidden').val(selectedTexts.length ? selectedTexts.join(',') : '');

        const params = new URLSearchParams({
            start_date: $('#start_date').val() || '',
            until_date: $('#end_date').val() || '',
            product_name: $('#productNameHidden').val() || '',
            search: ($('#codeboostTable_filter input').val() || '')
        });

        window.open("<?= base_url('codeboost/export') ?>?" + params.toString(), "_blank");
    });



});
</script>