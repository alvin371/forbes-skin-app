<?php

if ($_GET['start_date']) {
    $start_date = $_GET['start_date'];
} else {
    $start_date = date('Y-m-01');
}
if ($_GET['until_date']) {
    $until_date = $_GET['until_date'];
} else {
    $until_date = DATE('Y-m-d');
}
?>
<div class="w-100">
    <div class="row align-items-center">
        <div class="col-lg-12 mb-3">
            <h3 class="text-primary fw-600">CUSTOMER SCRAP</h3>
        </div>
        <div class="col-lg-12">
            <form id="filterForm">
                <div class="row">
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
                        <button class="btn btn-edit-active" type="submit"><i class="bi bi-search fs-16"></i> Cari Data</button>
                    </div>
                    <div class="col-lg-4 text-end">
                        <a href="#!" onclick="import_customer()" class="btn mb-2 btn-edit px-2 mt-0 ms-1"><i class="bi bi-people fs-16"></i> Import Pelanggan</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="col-lg-12 mt-3">
    <div class="form-message"></div>
    <div class="table-responsive">
        <table id="customerTable" class="table table-bordered table-striped" style="width:100%">
            <thead>
                <tr class="bg-blue-2 text-white">
                    <th class="text-center">#</th>
                    <th class="text-start">ORDER ID</th>
                    <th class="text-start">NAMA</th>
                    <th class="text-start" style="max-width: 200px;">ALAMAT</th>
                    <th class="text-start">TELEPON</th>
                    <th class="text-center">TANGGAL</th>
                    <th class="text-start">USERNAME</th>
                    <th class="text-center">STATUS</th>
                </tr>
            </thead>
            <tbody>
                <!-- Data akan diisi melalui DataTables -->
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade bd-example-modal-sm" tabindex="-1" varietas="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="modal-form">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="title-form"></h5>
                <a class="close a-link" data-bs-dismiss="modal"><i class="bi bi-x-circle fs-24"></i></a>
            </div>
            <div class="modal-body">
                <div id="load-form"></div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var table = $('#customerTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "<?= base_url('scraper/get_data') ?>",
            "type": "GET",
            "data": function(d) {
                d.start_date = $('#start_date').val();
                d.until_date = $('#end_date').val();
            }
        },
        "columns": [
            {
                "data": null,
                "render": function(data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                },
                "className": "text-center",
                "orderable": false
            },
            { 
                "data": "1",
                "className": "text-start"
            },
            { 
                "data": "2",
                "className": "text-start"
            },
            { 
                "data": "3",
                "className": "text-start",
                "render": function(data, type, row) {
                    return '<div style="word-wrap: break-word; white-space: normal;">'+data+'</div>';
                }
            },
            { 
                "data": "4",
                "className": "text-start"
            },
            { 
                "data": "5",
                "className": "text-center",
                "render": function(data, type, row) {
                    return '<span data-sort="'+data+'">'+data+'</span>';
                }
            },
            { 
                "data": "6",
                "className": "text-start"
            },
            { 
                "data": "7",
                "className": "text-center",
                "className": "text-center",
                "orderable": false
            }
        ],
        "order": [[0, 'asc']],
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
        "dom": "<'row'<'col-sm-12 col-md-6'i><'col-sm-12 col-md-6 d-flex justify-content-end'l>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-12 col-md-5'><'col-sm-12 col-md-7'p>>",
        "lengthMenu": [10, 20, 50, 100, 500],
        "pageLength": 10
    });

    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });
});
</script>

<script>
    function import_customer() {
        const start_date = $('#start_date').val();
        const until_date = $('#end_date').val();

        $("#load-form").html('Loading...');
        $("#title-form").html('Import Customer');
        $("#modal-form").modal('show');
        $("#load-form").load("<?= base_url() ?>scraper/import?start_date=" + start_date + "&until_date=" + until_date);
    }

</script>

<style>
    #customerTable td {
        vertical-align: top;
    }
    #customerTable td div {
        word-break: break-word;
    }
</style>