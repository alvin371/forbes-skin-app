<?php

if ($customer['brand'] == "POME") {
    $customer['akun_type'] = $customer['marketplace'];
}
?>


<style>
    thead {
        z-index: 3;
    }

    .table-custom th:first-child {
        position: sticky;
        left: 0px;
        background-color: #5e317a;
        z-index: 2;
    }

    .table-custom td:nth-child(2) {
        position: sticky;
        left: 0px;
        background-color: #e5e5e5;
        z-index: 2;
    }

    .table-custom th:nth-child(2) {
        position: sticky;
        left: 100px;
        background-color: #5e317a;
        z-index: 1;
    }

    .table-custom td:nth-child(3) {
        position: sticky;
        left: 100px;
        background-color: #e5e5e5;
        z-index: 1;
    }

    .table-custom th:nth-child(3) {
        position: sticky;
        left: 300px;
        background-color: #5e317a;
        z-index: 1;
    }

    .table-custom td:nth-child(4) {
        position: sticky;
        left: 300px;
        background-color: #e5e5e5;
        z-index: 1;
    }
</style>

<div class="form-message"></div>
<div class="w-100">
    <div class="row align-items-center">
        <div class="col-lg-12 mb-3">
            <h3 class="text-primary fw-600">DETAIL CRM</h3>
        </div>
        <div class="col-md-12 mb-3">
            <div class="card">
                <h5 class="mb-0 text-notif fw-600">DATA CUSTOMER</h5>
                <hr class="mb-3 mt-2">
                <p class="mb-1"><label>Nama :</label> <?= $customer['full_name'] ?></p>
                <p class="mb-1"><label>Username :</label> <?= $customer['username'] ?></p>
                <p class="mb-1"><label>No. HP :</label> <?= $customer['phone'] ?></p>
                <p class="mb-1"><label>Tipe :</label> <?= $customer['akun_type'] ?></p>
                <p class="mb-1"><label>Tgl Lahir :</label> <?= $template->date_format_indo($customer['birth_date']) ?></p>
                <p class="mb-1"><label>Jumlah Transaksi :</label> <?= $customer['count_order'] ?></p>
                <p class="mb-1"><label>Kecamatan :</label> <?= $customer['subdistrict_text'] ?></p>
                <p class="mb-1"><label>Kota :</label> <?= $customer['city_text'] ?></p>
                <p class="mb-1"><label>Provinsi :</label> <?= $customer['province_text'] ?></p>
                <p class="mb-1"><label>Alamat Lengkap :</label> <?= $customer['address'] ?></p>
                <p class="mb-1"><label>CS :</label> <?= $customer['cs'] ?></p>
            </div>
        </div>

        <div class="col-md-12 mb-3">
            <div class="">
                <div class="row">
                    <div class="col-6 d-flex align-items-center">
                        <h5 class="mb-0 text-notif fw-600">DATA PERKEMBANGAN</h5>
                    </div>
                    <div class="col-6 text-end">
                        <a href="#!" onclick="create_2('<?= $_GET['id'] ?>')" class="btn btn-primary mb-2">Tambah Data</a>
                    </div>
                    <?= $notif_2 ?>

                </div>
                <div class="col-lg-12">

                    <div class="table-responsive" id="table-item">
                        <table class="table" id="tbody_2">
                            <thead>
                                <?php if ($customer['brand'] == "POME") { ?>
                                    <tr class="bg-primary text-white">
                                        <th class="text-start" style="min-width:100px">#</th>
                                        <th style="min-width:100px" class="text-start">TANGGAL</th>
                                        <th class="text-start">KETERANGAN</th>
                                        <th style="min-width:100px" class="text-start">GAMBAR SEBELUM</th>
                                        <th style="min-width:100px" class="text-start">GAMBAR SESUDAH</th>
                                        <th class="text-lg-end text-start"><i class="bi bi-gear-fill"></i></th>
                                    </tr>
                                <?php } else { ?>
                                    <tr class="bg-primary text-white">
                                        <th class="text-start" style="min-width:100px">#</th>
                                        <th style="min-width:100px" class="text-start">TANGGAL</th>
                                        <th class="text-start">KETERANGAN</th>
                                        <th style="min-width:100px" class="text-start">GAMBAR</th>
                                        <th class="text-lg-end text-start"><i class="bi bi-gear-fill"></i></th>
                                    </tr>
                                <?php } ?>
                            </thead>
                        </table>
                    </div>
                </div>
                <?= $pagination_2 ?>
            </div>
        </div>
    </div>
</div>

<div class="col-lg-12">
    <h5 class="mb-0 text-notif fw-600 mb-3">DATA ORDER</h5>
    <form action="">
        <input type="hidden" name="id" value="<?= $customer['id'] ?>">
        <div class="row">

            <div class="col-md-12">
                <?php

                $arr_val = array();
                $arr_val[] = '';
                $arr_val[] = 'ACTIVE';
                $arr_val[] = 'UNPAID';
                // $arr_val[] = 'PENDING';
                $arr_val[] = 'READY_TO_SHIP';
                $arr_val[] = 'PROCESSED';
                $arr_val[] = 'SHIPPED';
                $arr_val[] = 'DELIVERED';
                $arr_val[] = 'COMPLETED';
                $arr_val[] = 'CANCELLED';
                $arr_val[] = 'RETURN';
                $arr_val[] = 'REFUND';

                $arr = array();
                $arr[] = 'Semua Order';
                $arr[] = 'Order Aktif';
                $arr[] = 'Belum Bayar';
                // $arr[] = 'Order Menunggu Diproses';
                $arr[] = 'Menunggu Diproses';
                $arr[] = 'Diproses';
                $arr[] = 'Pengiriman';
                $arr[] = 'Diterima';
                $arr[] = 'Selesai';
                $arr[] = 'Dibatalkan';
                $arr[] = 'Return';
                $arr[] = 'Refund';
                foreach ($arr_val as $k => $val) {
                    $class = "btn-default";
                    $class_2 = "dot";
                    if ($_GET['order_status'] == $val) {
                        $class = "btn-default-selected";
                        $class_2 = "dot-active";
                    }
                ?>
                    <a href="<?= $url_1 ?>&order_status=<?= $val ?>" class="btn mb-2 <?= $class ?> mb-2 me-2"><span class="<?= $class_2 ?>"></span> <?= $arr[$k] ?></a>
                <?php }  ?>
            </div>

            <div class="col-lg-4">
                <div class="input-group">
                    <button class="btn mb-2 btn-outline-secondary-category dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border-top-right-radius: 0px !important;
                            border-bottom-right-radius: 0px !important;"><?= $keyword_category ?></button>
                    <ul class="dropdown-menu">
                        <?php
                        $arr = array();
                        $arr[] = 'Order ID';
                        $arr[] = 'Username';
                        $arr[] = 'Nama Pelanggan';
                        $arr[] = 'Nomor Pelanggan';
                        $arr[] = 'Nomor Resi';
                        foreach ($arr as $k => $val) {
                            $class = "btn-default";
                            if ($_GET['order_status'] == $val) {
                                $class = "btn-default-selected";
                            }
                        ?>
                            <li><a class="dropdown-item" href="<?= $url_2 ?>&keyword_category=<?= $val ?>"><?= $val ?></a></li>
                        <?php }  ?>
                    </ul>
                    <input type="hidden" name="keyword_category" value="<?= $keyword_category ?>">
                    <input type="hidden" name="order_status" value="<?= $_GET['order_status'] ?>">
                    <input type="text" name="keyword" class="form-control" value="<?= $_GET['keyword'] ?>" style="border-top-left-radius: 0px !important;
                            border-bottom-left-radius: 0px !important;">
                </div>
            </div>

            <div class="col-lg-8 text-lg-end text-start">
                <a href="#!" onclick="sync_data('<?= $start_date ?>','<?= $until_date ?>')" class="btn mb-2 btn-edit px-2 mt-0 ms-1"><i class="bi bi-cloud-download fs-16"></i> Sync Data</a>
                <a href="<?= base_url() ?>transaction/download-template<?= $param ?>" class="btn mb-2 btn-edit px-2 mt-0 ms-1"><i class="bi bi-download fs-16"></i> Download</a>
                <a href="#!" onclick="import_data()" class="btn mb-2 btn-edit px-2 mt-0 ms-1"><i class="bi bi-cart2 fs-16"></i> Import Order</a>
                <a href="#!" onclick="import_customer()" class="btn mb-2 btn-edit px-2 mt-0 ms-1"><i class="bi bi-people fs-16"></i> Import Pelanggan</a>
                <a href="<?= base_url() ?>transaction/create" class="btn mb-2 btn-edit-active px-2 mt-0 ms-1"><i class="bi bi-plus-circle-dotted fs-16"></i> Buat Order</a>
            </div>
            <div class="col-lg-12 mb-3">
                <div class="card">
                    <h3 class="mb-0 text-notif">Filter Order</h3>
                    <hr>

                    <div class="row">
                        <div class="col-md-1">
                            <label for="">Brand</label>
                            <select class="form-control" name="brand" id="brand">
                                <option value="">-</option>
                                <?php
                                foreach ($brands as $val) :
                                    $text = '';
                                    if ($_GET['brand'] == $val['code']) {
                                        $text = 'selected';
                                    }
                                ?>
                                    <option <?= $text ?> value="<?= $val['code'] ?>"><?= $val['code'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="">Channel</label>
                            <select class="form-control" name="marketplace">
                                <option value="">-</option>
                                <!-- <option <?php if ($_GET['marketplace'] == 'UNCATEGORIZED') {
                                                    echo 'selected';
                                                } ?> value="UNCATEGORIZED">UNCATEGORIZED</option> -->
                                <?php
                                foreach ($marketplace as $val) :
                                    $text = '';
                                    if ($_GET['marketplace'] == $val['name']) {
                                        $text = 'selected';
                                    }
                                ?>
                                    <option <?= $text ?> value="<?= $val['name'] ?>"><?= $val['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="">Ekspedisi</label>
                            <select class="form-control" name="ekspedisi">
                                <option value="">-</option>
                                <?php
                                foreach ($shipping as $val) :
                                    $text = '';
                                    if ($_GET['ekspedisi'] == $val['name']) {
                                        $text = 'selected';
                                    }
                                ?>
                                    <option <?= $text ?> value="<?= $val['name'] ?>"><?= $val['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="">CS</label>
                            <select class="form-control" name="cs">
                                <option value="">-</option>
                                <?php
                                foreach ($cs as $val) :
                                    $text = '';
                                    if ($_GET['cs'] == $val['full_name']) {
                                        $text = 'selected';
                                    }
                                ?>
                                    <option <?= $text ?> value="<?= $val['full_name'] ?>"><?= $val['full_name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="">Tipe Pembayaran</label>
                            <select class="form-control" name="payment_type">
                                <option value="">-</option>
                                <?php
                                $arr = array();
                                $arr[] = "TF";
                                $arr[] = "COD";
                                foreach ($arr as $val) :
                                    $text = '';
                                    if ($_GET['payment_type'] == $val) {
                                        $text = 'selected';
                                    }
                                ?>
                                    <option <?= $text ?> value="<?= $val ?>"><?= $val ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="">Tanggal Order</label>
                            <div class="d-flex">
                                <input type="text" class="form-control" id="tanggal" placeholder="Pilih rentang tanggal...">
                                <input type="hidden" name="start_date" id="start_date" value="<?= $_GET['start_date'] ?? $start_date ?>">
                                <input type="hidden" name="until_date" id="end_date" value="<?= $_GET['until_date'] ?? $until_date ?>">
                            </div>
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
                                    },
                                    error: function(xhr, status, error) {
                                        console.error("Error loading filter:", error);
                                    }
                                });
                            }
                        </script>

                        <div class="col-md-2 text-lg-end text-start mb-2">
                            <!-- <label for="">&nbsp;</label> -->
                            <button class="btn mb-2 btn-edit-active w-100" type="submit">Cari Data</button>
                        </div>

                    </div>
                </div>
            </div>
            <div class="col-lg-12 d-none">
                <div class="row">
                    <div class="col-md-2">
                        <!-- <label for="">&nbsp;</label> -->
                        <button class="btn mb-2 btn-primary w-100 form-control" type="submit">FILTER DATA</button>
                    </div>
                    <div class="col-md-2">
                        <!-- <label for="">&nbsp;</label> -->
                        <a href="<?= base_url() ?>transaction/create?start_date=<?= $start_date ?>&until_date=<?= $until_date ?>&marketplace=<?= $_GET['marketplace'] ?>&cs=<?= $_GET['cs'] ?>&keyword=<?= $_GET['keyword'] ?>" class="btn mb-2 btn-primary w-100 form-control">TAMBAH DATA</a>
                    </div>
                    <div class="col-md-2">
                        <!-- <label for="">&nbsp;</label> -->
                        <a href="#!" onclick="import_data('<?= $start_date ?>','<?= $until_date ?>')" class="btn mb-2 btn-primary w-100 form-control">IMPORT DATA</a>
                    </div>
                    <div class="col-md-2">
                        <!-- <label for="">&nbsp;</label> -->
                        <a href="#!" onclick="sync_data('<?= $start_date ?>','<?= $until_date ?>')" class="btn mb-2 btn-primary w-100 form-control">SYNC DATA</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <?= $notif ?>
</div>



<div class="col-lg-12">

    <div id="tbody">
    </div>
</div>
<?= $pagination ?>

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
    function create_2(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Tambah Data');
        $("#load-form").load("<?= base_url() ?>testimoni/create?brand=<?= $customer['brand'] ?>&id=" + id);
    }

    function remove_2(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Hapus Data');
        $("#load-form").load("<?= base_url() ?>testimoni/remove?id=" + id);
    }

    function edit_2(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Edit Data');
        $("#load-form").load("<?= base_url() ?>testimoni/edit?brand=<?= $customer['brand'] ?>&id=" + id);
    }

    function remove(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Hapus Data');
        $("#load-form").load("<?= base_url() ?>transaction/remove?id=" + id);
    }


    function import_data(start_date, until_date) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Import Data');
        $("#load-form").load("<?= base_url() ?>transaction/import?start_date=" + start_date + "&until_date=" + until_date);
    }

    function sync_data(start_date, until_date) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Sync Data');
        $("#load-form").load("<?= base_url() ?>transaction/sync?start_date=" + start_date + "&until_date=" + until_date);
    }
</script>



<script>
    var complete = false;
    var offset = 0;
    var loading = false;

    function loadMoreData_2() {
        if (!complete) {
            if (!loading) {
                loading = true;
                $.ajax({
                    type: 'GET',
                    url: "<?= base_url() ?>testimoni/item?id_customer=<?= $customer['id'] ?>&brand=<?= $customer['brand'] ?>&p=true",
                    success: function(data) {
                        $('#tbody_2').append(data);
                        offset += 30;
                        loading = false;
                        if (!data) {
                            complete = true;
                        }
                        select3();
                    },
                    error: function(xhr, status, error) {
                        loading = false;
                    }
                });
            }
        } else {
            $("#msg").html("<i class='fa fa-check'></i> Proses memuat data selesai!");
        }
    }
    loadMoreData_2();
</script>


<script>
    var complete = false;
    var offset = 0;
    var loading = false;

    function loadMoreData() {
        if (!complete) {
            if (!loading) {
                loading = true;
                $.ajax({
                    type: 'GET',
                    url: "<?= base_url() ?>transaction/item<?= $param ?>&id_customer=<?= $customer['id'] ?>&p=true",
                    success: function(data) {
                        $('#tbody').append(data);
                        offset += 30;
                        loading = false;
                        if (!data) {
                            complete = true;
                        }
                        select3();
                    },
                    error: function(xhr, status, error) {
                        loading = false;
                    }
                });
            }
        } else {
            $("#msg").html("<i class='fa fa-check'></i> Proses memuat data selesai!");
        }
    }
    loadMoreData();
</script>