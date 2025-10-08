<div class="w-100">
    <div class="row align-items-center">
        <div class="col-lg-12 mb-3">
            <h3 class="text-primary fw-600">META ADS ACCOUNT</h3>
        </div>
        <div class="col-lg-12">
            <form action="">
                <div class="row">
                    <div class="col-lg-4">
                        <div class="input-group">
                            <!-- <button class="btn btn-outline-secondary-category dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border-top-right-radius: 0px !important;
                            border-bottom-right-radius: 0px !important;"><?= $keyword_category ?></button>
                            <ul class="dropdown-menu">
                                <?php
                                $arr = array();
                                $arr[] = 'Nama Group';
                                $arr[] = 'Keterangan';
                                foreach ($arr as $k => $val) {
                                    $class = "btn-default";
                                    if ($_GET['order_status'] == $val) {
                                        $class = "btn-default-selected";
                                    }
                                ?>
                                    <li><a class="dropdown-item" href="<?= $url ?>&keyword_category=<?= $val ?>"><?= $val ?></a></li>
                                <?php }  ?>
                            </ul>
                            <input type="hidden" name="keyword_category" value="<?= $keyword_category ?>"> -->
                            <input type="text" name="keyword" placeholder="Nama Akun" class="form-control" value="<?= $_GET['keyword'] ?>">
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <button class="btn btn-edit-active" type="submit"><i class="bi bi-search fs-16"></i> Cari Data</button>
                    </div>
                    <div class="col-lg-4 text-end">
                        <a href="#!" onclick="create()" class="btn btn-primary px-2 mt-0 ms-1"><i class="bi bi-plus-circle-dotted fs-16"></i> Tambah Data</a>
                    </div>

                </div>

        </div>
        </form>
        <?= $notif ?>
    </div>
</div>
<div class="col-lg-12">
    <div class="table-responsive" id="table-item">
        <table class="table" id="tbody">
            <thead>
                <tr class="bg-blue-2 text-white">
                    <th class="text-start">#</th>
                    <th class="text-start">ID AKUN</th>
                    <th class="text-start">NAMA AKUN</th>
                    <th class="text-start">STATUS</th>
                    <th class="text-end"><i class="bi bi-gear-fill"></i></th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<?= $pagination ?>
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
    function create() {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Create Data');
        $("#load-form").load("<?= base_url() ?>/meta-account/create");
    }

    function remove(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Hapus Data');
        $("#load-form").load("<?= base_url() ?>/meta-account/remove?id=" + id);
    }

    function edit(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Edit Data');
        $("#load-form").load("<?= base_url() ?>/meta-account/edit?id=" + id);
    }
</script>
<script>
    function loadMoreData() {
        $.ajax({
            type: 'GET',
            url: "<?= base_url() ?>/meta-account/item<?= $param ?>",
            success: function(data) {
                $('#tbody').append(data);
                select3();
            },
            error: function(xhr, status, error) {}
        });
    }
    loadMoreData();
</script>