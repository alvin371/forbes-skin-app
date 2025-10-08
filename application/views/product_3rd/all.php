<div class="w-100">
    <div class="row align-items-center">
        <div class="col-lg-12 mb-3">
            <h3 class="text-primary fw-600">KONFIGURASI PRODUK</h3>
        </div>
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
                                $arr[] = 'Nama Produk';
                                $arr[] = 'SKU Produk';
                                $arr[] = 'Nama Varian';
                                $arr[] = 'SKU Varian';
                                $arr[] = 'Marketplace';
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
                    <div class="col-md-1">
                        <select class="form-control" name="marketplace">
                            <option value="">-</option>
                            <?php
                            $arr = array();
                            $arr[] = "TIKTOK";
                            $arr[] = "SHOPEE";
                            $arr[] = "LAZADA";
                            foreach ($arr as $val) :
                                $text = '';
                                if ($_GET['marketplace'] == $val) {
                                    $text = 'selected';
                                }
                            ?>
                                <option <?= $text ?> value="<?= $val ?>"><?= $val ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-control" name="shop_id">
                            <option value="">-</option>
                            <?php
                            foreach ($store as $val) :
                                $text = '';
                                if ($_GET['shop_id'] == $val['id']) {
                                    $text = 'selected';
                                }
                            ?>
                                <option <?= $text ?> value="<?= $val['id'] ?>"><?= $val['opt'] ?> <?= ucwords(strtolower($val['marketplace'])) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <button class="btn btn-edit-active" type="submit"><i class="bi bi-search fs-16"></i> Cari Data</button>
                    </div>
                    <div class="col-lg-3 text-end">
                        <a href="#!" onclick="sync_data('<?= $start_date ?>','<?= $until_date ?>')" class="btn btn-edit px-2 mt-0 ms-1"><i class="bi bi-cloud-download fs-16"></i> Sync Data</a>
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
                    <th class="text-start">NAMA</th>
                    <th class="text-start">VARIAN</th>
                </tr>
            </thead>
        </table>
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
    function remove(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Hapus Produk');
        $("#load-form").load("<?= base_url() ?>/product-3rd/remove?id=" + id);
    }

    function remove_item(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Hapus Varian');
        $("#load-form").load("<?= base_url() ?>/product-3rd/remove-item?id=" + id);
    }

    function edit(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Konfigurasi Data');
        $("#load-form").load("<?= base_url() ?>/product-3rd/edit?id=" + id);
    }

    function sync_data(start_date, until_date) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Sync Data');
        $("#load-form").load("<?= base_url() ?>/product-3rd/sync?start_date=" + start_date + "&until_date=" + until_date);
    }
</script>
<script>
    function loadMoreData() {
        $.ajax({
            type: 'GET',
            url: "<?= base_url() ?>/product-3rd/item<?= $param ?>",
            success: function(data) {
                $('#tbody-loading').html('');
                $('#tbody').append(data);
                select3();
            },
            error: function(xhr, status, error) {}
        });
    }
    loadMoreData();
</script>