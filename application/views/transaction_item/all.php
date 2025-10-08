<?php

if ($_GET['start_date'] == "") {
    $start_date = DATE("Y-m-01");
    $start_date = DATE('Y-m-d', strtotime($today . " -30 days"));
} else {
    $start_date = $_GET['start_date'];
}
if ($_GET['until_date'] == "") {
    $until_date = DATE("Y-m-d");
} else {
    $until_date = $_GET['until_date'];
}
?>
<div class="form-message"></div>
<div class="w-100">
    <div class="row align-items-center">
        <div class="col-lg-12 mb-3">
            <h3 class="text-primary fw-600">ORDER ITEM</h3>
        </div>
        <div class="col-lg-12">
            <form action="">
                <div class="row">


                    <div class="col-lg-3 d-none">
                        <div class="input-group">
                            <input type="hidden" name="ids" value="<?= $ids ?>">
                            <input type="hidden" name="order_type" value="<?= $_GET['order_type'] ?>">
                            <input type="hidden" name="pencairan" value="<?= $_GET['pencairan'] ?>">
                            <input type="hidden" name="keyword_category" value="<?= $keyword_category ?>">
                            <input type="hidden" name="order_status" value="<?= $_GET['order_status'] ?>">
                            <input type="text" name="keyword" class="form-control" value="<?= $_GET['keyword'] ?>" style="border-top-left-radius: 0px !important;
                            border-bottom-left-radius: 0px !important;">
                        </div>
                    </div>

                    <div class="col-lg-9 text-lg-end text-start">
                        <!-- <a href="#!" onclick="sync_data('<?= $start_date ?>','<?= $until_date ?>')" class="btn mb-2 btn-edit px-2 mt-0 ms-1"><i class="bi bi-cloud-download fs-16"></i> Sync Data</a>
                        <a href="#!" onclick="download_file()" class="btn mb-2 btn-edit px-2 mt-0 ms-1"><i class="bi bi-download fs-16"></i> Download</a>
                        <a href="#!" onclick="import_data()" class="btn mb-2 btn-edit px-2 mt-0 ms-1"><i class="bi bi-cart2 fs-16"></i> Import Order</a>
                        <a href="#!" onclick="import_pencairan()" class="btn mb-2 btn-edit px-2 mt-0 ms-1"><i class="bi bi-credit-card fs-16"></i> Import Pencairan Dana</a>
                        <a href="#!" onclick="import_resi()" class="btn mb-2 btn-edit px-2 mt-0 ms-1"><i class="bi bi-truck fs-16"></i> Import Resi</a>
                        <a href="#!" onclick="import_customer()" class="btn mb-2 btn-edit px-2 mt-0 ms-1"><i class="bi bi-people fs-16"></i> Import Pelanggan</a>
                        <a href="#!" onclick="add()" class="btn mb-2 btn-edit-active px-2 mt-0 ms-1"><i class="bi bi-plus-circle-dotted fs-16"></i> Buat Data</a> -->
                    </div>
                    <div class="col-lg-12 mb-3">
                        <div class="card">
                            <h3 class="mb-0 text-notif">Filter Data</h3>
                            <hr>

                            <div class="row">
                                <!-- <div class="col-md-1">
                                    <label for="">Brand</label>
                                    <select class="form-control select2" name="brand" id="brand">
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
                                </div> -->
                                <div class="col-md-2">
                                    <label for="">Order ID</label>
                                    <input type="text" name="order_id" value="<?= $_GET['order_id'] ?>" class="form-control">
                                </div>
                                <div class="col-md-2">
                                    <label for="">Product</label>
                                    <select class="form-control select2" name="product">
                                        <option value="">-</option>
                                        <?php
                                        foreach ($product as $val) :
                                            $text = '';
                                            if ($_GET['product'] == $val['id']) {
                                                $text = 'selected';
                                            }
                                        ?>
                                            <option <?= $text ?> value="<?= $val['id'] ?>"><?= $val['opt'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="">Toko</label>
                                    <select class="form-control select2" name="shop_id">
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
                                <div class="col-md-2">
                                    <label for="">Channel</label>
                                    <select class="form-control select2" name="marketplace">
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
                                    <select class="form-control select2" name="ekspedisi">
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
                                    <select class="form-control select2" name="cs">
                                        <option value="">-</option>
                                        <?php
                                        foreach ($cs as $val) :
                                            $text = '';
                                            if ($_GET['cs'] == $val['full_name']) {
                                                $text = 'selected';
                                            }
                                        ?>
                                            <option <?= $text ?> value="<?= $val['code'] ?>"><?= $val['code'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="">Brand</label>
                                    <select class="form-control select2" name="brand">
                                        <option value="">-</option>
                                        <?php
                                        foreach ($brands as $val) :
                                            $text = '';
                                            if ($_GET['brand'] == $val['opt']) {
                                                $text = 'selected';
                                            }
                                        ?>
                                            <option <?= $text ?> value="<?= $val['opt'] ?>"><?= $val['opt'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="">Tipe</label>
                                    <select class="form-control select2" name="type">
                                        <option value="">-</option>
                                        <?php
                                        $arr = array();
                                        $arr[] = "In";
                                        $arr[] = "Out";
                                        foreach ($arr as $val) :
                                            $text = '';
                                            if ($_GET['type'] == $val) {
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
                                    <button style="margin-top:25px" class="btn mb-2 btn-edit-active w-100" type="submit">Cari Data</button>
                                </div>
                                <div class="col-md-2 text-lg-end text-start mb-2">
                                    <a style="margin-top:25px" target="_blank" href="<?= base_url() ?>transaction-item/download<?= $param ?>" class="btn btn-edit w-100"><i class="bi bi-download fs-16"></i> Download</a>
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
                                <a href="<?= base_url() ?>stock/create?start_date=<?= $start_date ?>&until_date=<?= $until_date ?>&brand=<?= $brand ?>&marketplace=<?= $_GET['marketplace'] ?>&cs=<?= $_GET['cs'] ?>&keyword=<?= $_GET['keyword'] ?>" class="btn mb-2 btn-primary w-100 form-control">TAMBAH DATA</a>
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
    </div>

    <div class="col-lg-12 mb-3">
        <div class="checkbox-wrapper-13">
            <input id="c1-13" type="checkbox" value="1" class="checkAll">
            <label for="c1-13">Pilih Semua Data</label>
        </div>
    </div>

    <div class="col-lg-12">
        <div id="tbody">
            <?php $this->load->view('loading', true) ?>
        </div>
    </div>

    <?= $pagination ?>
</div>

<div class="floating-div">
    <button class="btn mb-2 btn-edit-active dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-gear fs-16"></i> Aksi
    </button>
    <ul class="dropdown-menu text-lg-end text-start" style="padding:0px;background:unset;border:unset">
        <li><a class="dropdown-items" href="#!" style="padding:0px">
                <button type="button" class="btn mb-2 btn-edit-active" data-bs-toggle="modal" data-bs-target="#modal-print">
                    <i class="bi bi-printer fs-16"></i> Print Data
                </button>
            </a></li>

        <li><a class="dropdown-items" href="#!" style="padding:0px;">
                <button type="button" class="btn mb-2 btn-edit-active" onclick="barang_diterima()">
                    <i class="bi bi-house-check fs-16"></i> Barang Diterima
                </button>
            </a></li>

        <li><a class="dropdown-items" href="#!" style="padding:0px;">
                <button type="button" class="btn mb-2 btn-edit-active" onclick="refresh_data()">
                    <i class="bi bi-bootstrap-reboot fs-16"></i> Refresh Data
                </button>
            </a></li>

        <li><a class="dropdown-items" href="#!" style="padding:0px;">
                <button type="button" class="btn mb-2 btn-edit-active" onclick="tampilkan_data()">
                    <i class="bi bi-eye fs-16"></i> Tampilkan Data
                </button>
            </a></li>

        <li><a class="dropdown-items" href="#!" style="padding:0px">
                <button type="button" class="btn mb-2 btn-edit-active" onclick="hapus_data()">
                    <i class="bi bi-trash fs-16"></i> Hapus Data
                </button>
            </a></li>

    </ul>

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

<div class="modal fade bd-example-modal-sm" tabindex="-1" varietas="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="modal-print">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Print Data</h5>
                <a class="close a-link" data-bs-dismiss="modal"><i class="bi bi-x-circle fs-24"></i></a>
            </div>
            <div class="modal-body">
                <p>Apakah kamu yakin ingin melakukan print data?</p>
                <form target="_blank" action="<?= base_url() ?>/transaction-item/print-v2" method="POST" id="form-action">
                    <button class="btn mb-2 btn-edit-active" type="submit"><i class="bi bi-printer fs-16"></i> Print Data</button>
                </form>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="id_selected" name="id_selected" form="form-action">

<script>
    var list_id_v2 = '';

    function get_id() {
        list_id_v2 = '';
        var selectedValues = [];
        $('input[name="list_id"]').each(function() {
            if ($(this).is(":checked")) {
                selectedValues.push($(this).val());
                list_id_v2 += $(this).val() + ',';
            } else {
                selectedValues.push('0');
            }
        });
        if (list_id_v2.length > 0) {
            list_id_v2 = list_id_v2.slice(0, -1); 
        }
        $('#id_selected').val(selectedValues.join(','));
    }

    function remove(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Hapus Data');
        $("#load-form").load("<?= base_url() ?>transaction-item/remove?id=" + id);
    }

    function barang_diterima(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Barang Diterima');
        $("#load-form").load("<?= base_url() ?>transaction-item/action?code=barang_diterima&id=" + id);
    }

    function tampilkan_data(id) {
        window.location.href = "<?= base_url() ?>/transaction?ids=" + list_id_v2;
    }

    function hapus_data(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Hapus Data');
        $("#load-form").load("<?= base_url() ?>transaction-item/action?code=hapus_data&id=" + id);
    }

    function refresh_data(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Refresh Data');
        $("#load-form").load("<?= base_url() ?>transaction-item/action?code=refresh_data&id=" + id);
    }

    function set_cs(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Atur CS');
        $("#load-form").load("<?= base_url() ?>transaction-item/set_cs?id=" + id);
    }

    function set_resi(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Atur No Resi');
        $("#load-form").load("<?= base_url() ?>transaction-item/set_resi?id=" + id);
    }

    function set_return(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Atur Return');
        $("#load-form").load("<?= base_url() ?>transaction-item/set_return?id=" + id);
    }


    function refresh(order_id, marketplace) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Refresh');
        $("#load-form").load("<?= base_url() ?>transaction-item/refresh?order_id=" + order_id + '&marketplace=' + marketplace);
    }


    function import_pencairan() {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Import Pencairan Dana');
        $("#load-form").load("<?= base_url() ?>transaction-item/import-pencairan<?= $param ?>");
    }

    function import_data(start_date, until_date) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Import Data');
        $("#load-form").load("<?= base_url() ?>transaction-item/import<?= $param ?>");
    }

    function download_file() {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Download Order');
        $("#load-form").load("<?= base_url() ?>transaction-item/download-file<?= $param ?>");
    }

    function import_resi(start_date, until_date) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Import Resi');
        $("#load-form").load("<?= base_url() ?>transaction-item/import-resi<?= $param ?>");
    }

    function import_customer(start_date, until_date) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Import Pelanggan');
        $("#load-form").load("<?= base_url() ?>transaction-item/import_customer?start_date=" + start_date + "&until_date=" + until_date);
    }

    function sync_data(start_date, until_date) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Sync Data');
        $("#load-form").load("<?= base_url() ?>transaction-item/sync?start_date=" + start_date + "&until_date=" + until_date);
    }
</script>

<script>
    function loadMoreData() {
        $.ajax({
            type: 'GET',
            url: "<?= base_url() ?>transaction-item/item<?= $param ?>",
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