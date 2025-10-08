<div class="w-100">
    <div class="row align-items-center">
        <div class="col-lg-12">
            <h3 class="text-primary fw-600">ENDORSE CAMPAIGN</h3>
        </div>
        <?php $this->load->view('endorse_campaign/menu') ?>
        <div class="col-lg-12">
            <form action="">
                <div class="row">
                    <div class="col-lg-4">
                        <div class="input-group">
                            <button class="btn btn-outline-secondary-category dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border-top-right-radius: 0px !important;
                            border-bottom-right-radius: 0px !important;"><?= $keyword_category ?></button>
                            <ul class="dropdown-menu">
                                <?php
                                $arr = array();
                                $arr[] = 'Judul Campaign';
                                $arr[] = 'PIC';
                                $arr[] = 'Brand';
                                $arr[] = 'Keterangan';
                                $arr[] = 'Status';
                                foreach ($arr as $k => $val) {
                                    $class = "btn-default";
                                    if ($_GET['order_status'] == $val) {
                                        $class = "btn-default-selected";
                                    }
                                ?>
                                    <li><a class="dropdown-item" href="<?= $url ?>&keyword_category=<?= $val ?>"><?= $val ?></a></li>
                                <?php }  ?>
                            </ul>
                            <input type="hidden" name="keyword_category" value="<?= $keyword_category ?>">
                            <input type="text" name="keyword" class="form-control" value="<?= $_GET['keyword'] ?>" style="border-top-left-radius: 0px !important;
                            border-bottom-left-radius: 0px !important;">
                        </div>
                    </div>
                    <div class="col-lg-3">
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
                    <div class="col-lg-3">
                        <button class="btn btn-edit-active" type="submit"><i class="bi bi-search fs-16"></i> Cari Data</button>
                    </div>
                    <div class="col-lg-2 text-end">
                        <a href="#!" onclick="create()" class="btn btn-primary px-2 mt-0 ms-1"><i class="bi bi-plus-circle-dotted fs-16"></i> Tambah Data</a>
                    </div>

                </div>

        </div>
        </form>
        <?= $notif ?>
    </div>
</div>
<div class="col-lg-12">
    <div class="table-responsive">
        <div id="tbody">
            <?php $this->load->view('loading', true) ?>
        </div>
    </div>
</div>

<?= $pagination ?>
</div>

<div class="modal fade bd-example-modal-lg" tabindex="-1" varietas="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="modal-form">
    <div class="modal-dialog" id="modal-dialog">
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
    function create() {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#modal-dialog").addClass('modal-lg');
        $("#title-form").html('Tambah Data');
        const urlParams = new URLSearchParams(window.location.search);
        const internalParam = urlParams.get('p') === 'internal' ? '?p=internal' : '';
        
        $("#load-form").load("<?= base_url() ?>endorse-campaign/create" + internalParam);
    }

    function remove(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#modal-dialog").removeClass('modal-sm');
        $("#title-form").html('Hapus Data');
        $("#load-form").load("<?= base_url() ?>/endorse-campaign/remove?id=" + id);
    }

    function edit(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#modal-dialog").addClass('modal-lg');
        $("#title-form").html('Edit Data');
        const urlParams = new URLSearchParams(window.location.search);
        const internalParam = urlParams.get('p') === 'internal' ? '&p=internal' : '';

        $("#load-form").load("<?= base_url() ?>/endorse-campaign/edit?id=" + id + internalParam);
    }

    function showModal(title, url, isLarge = false) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html(title);

        if (isLarge) {
            $("#modal-dialog").addClass("modal-lg");
        } else {
            $("#modal-dialog").removeClass("modal-lg");
        }

        $("#load-form").load(url);
    }
</script>
<script>
    function loadMoreData() {
        $.ajax({
            type: 'GET',
            url: "<?= base_url() ?>/endorse-campaign/item<?= $param ?>",
            success: function(data) {
                $('#tbody').html('');
                $('#tbody').append(data);
                select3();
            },
            error: function(xhr, status, error) {}
        });
    }
    loadMoreData();
</script>