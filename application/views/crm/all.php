<style>
    table tr td {
        padding-bottom: 0px !important;
    }

    .select2 {
        box-shadow: unset !important;
    }

    .select2-container .select2-selection--single {
        border-radius: unset !important;
    }
</style>
<div class="form-message"></div>
<div class="w-100">
    <div class="row align-items-center">
        <div class="col-lg-12 mb-3">
            <h3 class="text-primary fw-600">CRM <?= $_GET['brand'] ?></h3>
        </div>
        <div class="col-lg-12">
            <form action="" id="form-search">
                <div class="row">
                    <input type="hidden" name="ids" value="<?= $ids ?>">
                    <input type="hidden" class="form-control" name="brand" value="<?= $_GET['brand'] ?>">
                    <div class="col-lg-3">
                        <!-- <label for="">KEYWORD</label> -->
                        <div class="input-group">
                            <button class="btn btn-outline-secondary-category dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border-top-right-radius: 0px !important;
                            border-bottom-right-radius: 0px !important;"><?= $keyword_category ?></button>
                            <ul class="dropdown-menu">
                                <?php
                                $arr = array();
                                $arr[] = 'Username';
                                $arr[] = 'Order ID';
                                $arr[] = 'Produk';
                                $arr[] = 'Channel';
                                $arr[] = 'Keterangan';
                                $arr[] = 'Gift';
                                $arr[] = 'Progres';
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
                    <div class="col-md-2">
                        <select class="form-control" name="cs">
                            <option value="">-</option>
                            <?php
                            foreach ($cs as $val) :
                                $text = '';
                                if ($_GET['cs'] == $val['code']) {
                                    $text = 'selected';
                                }
                            ?>
                                <option <?= $text ?> value="<?= $val['code'] ?>"><?= $val['code'] ?></option>
                            <?php endforeach; ?>
                        </select>

                    </div>
                    <div class="col-md-1">
                        <select class="form-control" name="cb_cl">
                            <option value="">-</option>
                            <?php
                            $arr = array();
                            $arr[] = "CB";
                            $arr[] = "CL";
                            foreach ($arr as $val) :
                                $text = '';
                                if ($_GET['cb_cl'] == $val) {
                                    $text = 'selected';
                                }
                            ?>
                                <option <?= $text ?> value="<?= $val ?>"><?= $val ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-control" name="sort">
                            <?php
                            $arr = array();
                            $arr[] = "Tanggal Dibuat";
                            $arr[] = "Tanggal Order";
                            $arr[] = "Repeat Order";
                            $arr[] = "FU H+10";
                            $arr[] = "FU H-7";
                            $arr[] = "Tidak Repeat Order";
                            $arr[] = "Tidak Repeat Order H+30";
                            foreach ($arr as $val) :
                                $text = '';
                                if ($_GET['sort'] == $val) {
                                    $text = 'selected';
                                }
                            ?>
                                <option <?= $text ?> value="<?= $val ?>"><?= $val ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-lg-3">
                        <div class="d-flex">
                            <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>" style="border-top-right-radius: 0px !important; border-bottom-right-radius: 0px !important; width:100%;">
                            <input type="date" name="until_date" class="form-control" value="<?= $until_date ?>" style="border-top-left-radius: 0px !important; border-bottom-left-radius: 0px !important; width:100%;">
                        </div>
                    </div>
                    <div class="col-lg-1">
                        <button class="btn btn-edit-active w-100  mb-3" type="submit"><i class="bi bi-search fs-16"></i> Cari</button>
                    </div>
                </div>
            </form>
            <div class="col-lg-12">
                <a href="<?= base_url() ?>crm/create<?= $param ?>" class="btn btn-primary mb-2"><i class="bi bi-plus-circle-dotted fs-16"></i> Tambah Data</a>
                <a href="<?= base_url() ?>scraper" class="btn btn-primary mb-2"><i class="bi bi-bootstrap-reboot fs-16"></i> Scraper Data</a>
                <a target="_blank" href="<?= base_url() ?>crm/download<?= $param ?>" class="btn mb-2 btn-edit px-2 mt-0 ms-1"><i class="bi bi-download fs-16"></i> Download</a>
            </div>
            <?= $notif ?>
        </div>

    </div>
    <?php if ($_GET['brand'] == "POME") { ?>

        <div class="col-lg-12 mb-3">
            <div class="checkbox-wrapper-13">
                <input id="c1-13" type="checkbox" value="1" class="checkAll">
                <label for="c1-13">Pilih Semua Data</label>
            </div>
        </div>

        <div class="col-lg-12">
            <div class="table-responsive" id="table-item">
                <table class="table" id="tbody">
                    <thead>
                        <tr class="bg-blue-2 text-white">
                            <th class="text-start">#</th>
                            <th class="text-start">NAMA</th>
                            <th class="text-start">NO. HP</th>
                            <th class="text-start">TGL LAHIR</th>
                            <th class="text-start">ALAMAT</th>
                            <th class="text-end"><i class="bi bi-gear-fill"></i></th>
                        </tr>
                        <tr class="p-0" id="tbody-loading" style="background:unset!important">
                            <td class="text-start p-0" colspan="6" style="background:unset!important">
                                <div class="mt-3">
                                    <?php $this->load->view('loading', true) ?>
                                </div>
                            </td>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    <?php } else if ($_GET['brand'] == "MG") { ?>
        <style>
            table tr td {
                padding: 10px !important;
                background: unset !important;
            }

            table tr {
                background: #FFF !important;
            }

            /* table tr:first-child th:first-child {
            border-top-left-radius: unset!important;
            border-bottom-left-radius: unset!important;
            padding: 10px 10px!important;
            max-width: unset!important;
            min-width: unset!important;
            width: unset!important;
        }
        table tr:first-child td:first-child {
            border-top-left-radius: unset!important;
            border-bottom-left-radius: unset!important;
            padding: 10px 10px!important;
            max-width: unset!important;
            min-width: unset!important;
            width: unset!important;
            } */
            /* table tr td, table tr th {
            border:1px #dbdbdb solid!important;
        } */
            thead {
                z-index: 3;
            }

            /* th:first-child {
            position: sticky;
            left: 0px;
            background-color: #93bce6;
            z-index: 2;
        }
        td:nth-child(2) {
            position: sticky;
            left: 0px;
            background-color: #4caf50!important;
            z-index: 2;
        }
        th:nth-child(2) {
            position: sticky;
            left: 118px;
            background-color: #93bce6;
            z-index: 1;
        }
        td:nth-child(3) {
            position: sticky;
            left: 118px;
            background-color: #4caf50!important;
            z-index: 1;
        }
        th:nth-child(3) {
            position: sticky;
            left: 236px;
            background-color: #93bce6;
            z-index: 1;
        }
        td:nth-child(4) {
            position: sticky;
            left: 236px;
            background-color: #4caf50!important;
            z-index: 1;
        } */
            table tr:first-child td:first-child {
                border-top-left-radius: 12px;
                border-bottom-left-radius: 12px;
                padding: 16px 10px !important;
                max-width: 50px !important;
                min-width: 50px !important;
                width: 50px !important;
            }
        </style>
        <style>
            #table-item {
                overflow-x: auto;
                overflow-y: hidden;
            }

            #table-item::-webkit-scrollbar {
                height: 10px;
            }

            #table-item::-webkit-scrollbar-thumb {
                background-color: #ccc;
                border-radius: 10px;
            }

            #table-item::-webkit-scrollbar-thumb:hover {
                background-color: #888;
                border-radius: 10px;
            }

            /* #table-item::-webkit-scrollbar-track {
                background-color: #888;
            } */
        </style>

        <div class="col-lg-12 mb-3">
            <div class="checkbox-wrapper-13">
                <input id="c1-13" type="checkbox" value="1" class="checkAll">
                <label for="c1-13">Pilih Semua Data</label>
            </div>
        </div>
        <div class="col-lg-12">
            <div class="table-responsive" id="table-item">
                <table class="table" id="tbody">
                    <thead>
                        <tr class="bg-primary text-white">
                            <th style="min-width:100px">#</th>
                            <!-- <th style="min-width:100px" class="text-start">PENJUALAN</th> -->
                            <th style="min-width:200px!important" class="text-start">KODE</th>
                            <th style="min-width:200px" class="text-start">NOMOR TELEPON</th>
                            <th style="min-width:200px" class="text-start">NAMA LENGKAP</th>
                            <th class="text-start">USERNAME</th>
                            <!-- <th class="text-start">BRAND</th> -->
                            <!-- <th style="min-width:150px!important" class="text-start">CS</th> -->
                            <!-- <th class="text-start">CHANNEL</th> -->
                            <!-- <th class="text-start">TIPE</th> -->
                            <th class="text-start">HISTORY PEMBELIAN</th>
                            <th class="text-start">GROUP</th>
                            <!-- <th class="text-start">TANGGAL LAHIR</th> -->
                            <th class="text-start">TANGGAL ORDER</th>
                            <th class="text-start">JOIN</th>
                            <th class="text-end">MASA<br>JOIN</th>
                            <th class="text-start">WAKTU FU H-7</th>
                            <th class="text-start">BATAS JOIN</th>
                            <th class="text-start">FU H+10<br>PERKEMBANGAN</th>
                            <th class="text-start">REPEAT ORDER</th>
                            <th class="text-start">GIFT</th>
                            <th class="text-start" style="min-width: 250px">TREATMENT CUSTOMER EXPERIENCE</th>
                            <th class="text-start" style="min-width: 250px">RIWAYAT KELUHAN</th>
                            <!-- <th class="text-start" style="min-width: 250px">PERKEMBANGAN KONSUMSI</th> -->
                            <!-- <th class="text-start" style="min-width: 250px">KETERANGAN</th> -->
                            <th class="text-start">ALAMAT</th>
                            <!-- <th class="text-start">PROVINSI</th>
                            <th class="text-start">KOTA</th>
                            <th class="text-start">KECAMATAN</th>
                            <th style="min-width:60px!important" class="text-end">JUMLAH ORDER</th>
                            <th style="min-width:60px!important" class="text-start">ID CHANNEL</th>
                            <th style="min-width:60px!important" class="text-start">CHANNEL</th> -->
                            <th class="text-start">BRAND</th>
                        </tr>
                        <tr class="tr-search">
                            <td>Pencarian</td>
                            <td class="text-start">
                                <input form="form-search" type="text" class="text-start form-table" name="dtf[kd]" value="<?= $dtf['kd'] ?>">
                            </td>
                            <td class="text-start">
                                <input form="form-search" type="text" class="text-start form-table" name="dtf[phone]" value="<?= $dtf['phone'] ?>">
                            </td>
                            <td class="text-start">
                                <input form="form-search" type="text" class="text-start form-table" name="dtf[full_name]" value="<?= $dtf['full_name'] ?>">
                            </td>
                            <td class="text-start">
                                <input form="form-search" type="text" class="text-start form-table" name="dtf[username]" value="<?= $dtf['username'] ?>">
                            </td>
                            <td class="text-start">
                                <input form="form-search" type="text" class="text-start form-table" name="dtf[pesanan]" value="<?= $dtf['pesanan'] ?>">
                            </td>
                            <td class="text-start">
                                <input form="form-search" type="text" class="text-start form-table" name="dtf[grup]" value="<?= $dtf['grup'] ?>">
                            </td>
                            <!-- <td class="text-start">
                                <input form="form-search" type="date" class="text-start form-table" name="dtf[birth_date]" value="<?= $dtf['birth_date'] ?>">
                            </td> -->
                            <td class="text-start">
                                <input form="form-search" type="datetime-local" class="text-start form-table" name="dtf[first_order]" value="<?php if ($dtf['first_order']) {
                                                                                                                                                    echo DATE("Y-m-d H:i", strtotime($dtf['first_order']));
                                                                                                                                                }  ?>">
                            </td>
                            <td class="text-start">
                                <input form="form-search" type="date" class="text-start form-table" name="dtf[join]" value="<?php if ($dtf['join']) {
                                                                                                                                echo DATE("Y-m-d", strtotime($dtf['join']));
                                                                                                                            }  ?>">
                            </td>
                            <td style="max-width:50px!important">
                                <input form="form-search" type="text" class="text-end form-table" name="dtf[masa_join]" value="<?= $dtf['masa_join'] ?>" style="max-width:50px!important">
                            </td>
                            <td class="req text-start">
                                <input form="form-search" type="date" class="text-start form-table" name="dtf[waktu_fu_ro]" id="waktu_fu_ro-<?= $k ?>" value="<?= $dtf['waktu_fu_ro'] ?>">
                            </td>
                            <td class="text-start">
                                <input form="form-search" type="date" class="text-start form-table" name="dtf[batas_join]" id="batas_join-<?= $k ?>" value="<?= $dtf['batas_join'] ?>">
                            </td>
                            <td class="text-start">
                                <input form="form-search" type="date" class="text-start form-table" name="dtf[waktu_fu_perkembangan]" id="waktu_fu_perkembangan-<?= $k ?>" value="<?= $dtf['waktu_fu_perkembangan'] ?>">
                            </td>
                            <td class="text-start">
                                <input form="form-search" type="datetime-local" class="text-start form-table" name="dtf[last_order]" value="<?php if ($dtf['first_order']) {
                                                                                                                                                echo DATE("Y-m-d H:i", strtotime($dtf['last_order']));
                                                                                                                                            }  ?>">
                            </td>
                            <td class="text-start">
                                <input form="form-search" type="text" class="text-start form-table " name="dtf[gift]" style="width:100%;"><?= $dtf['gift'] ?></input>
                            </td>
                            <td class="text-start">
                                <input form="form-search" type="text" class="text-start form-table " name="dtf[treatment]" style="width:100%;"><?= $dtf['treatment'] ?></input>
                            </td>
                            <td class="text-start">
                                <input form="form-search" type="text" class="text-start form-table " name="dtf[riwayat_keluhan]" style="width:100%;"><?= $dtf['riwayat_keluhan'] ?></input>
                            </td>
                            <!-- <td class="text-start">
                                <input form="form-search" type="text" class="text-start form-table" name="dtf[testimoni]" value="<?= $dtf['testimoni'] ?>">
                            </td>
                            <td class="text-start">
                                <input form="form-search" type="text" class="text-start form-table  pencapaian-<?= $k ?>" name="dtf[pencapaian]" style="width:100%;"><?= $dtf['pencapaian'] ?></input>
                            </td> -->
                            <td class="text-start">
                                <input form="form-search" type="text" class="text-start form-table " name="dtf[address]" style="width:100%;"><?= $dtf['address'] ?></input>
                            </td>
                            <!-- <td class="text-start">
                                <input form="form-search" type="text" class="text-start form-table" name="dtf[province_text]" value="<?= $dtf['province_text'] ?>">
                            </td>
                            <td class="text-start">
                                <input form="form-search" type="text" class="text-start form-table" name="dtf[city_text]" value="<?= $dtf['city_text'] ?>">
                            </td>
                            <td class="text-start">
                                <input form="form-search" type="text" class="text-start form-table" name="dtf[subdistrict_text]" value="<?= $dtf['subdistrict_text'] ?>">
                            </td>
                            <td class="text-start">
                                <input form="form-search" type="text" class="text-start form-table" name="dtf[count_order]" value="<?= $dtf['count_order'] ?>">
                            </td>

                            <td class="text-start">
                                <input form="form-search" type="text" class="text-start form-table" name="dtf[id_buyer]" value="<?= $dtf['id_buyer'] ?>">
                            </td>

                            <td class="text-start">
                                <input form="form-search" type="text" class="text-start form-table" name="dtf[marketplace]" value="<?= $dtf['marketplace'] ?>">
                            </td> -->



                            <td class="text-start">
                                <input form="form-search" type="text" class="text-start form-table" name="dtf[brand]" value="<?= $dtf['brand'] ?>">
                            </td>
                        </tr>
                        <tr class="p-0" id="tbody-loading" style="background:unset!important">
                            <td class="text-start p-0" colspan="6" style="background:unset!important">
                                <div class="mt-3">
                                    <?php $this->load->view('loading', true) ?>
                                </div>
                            </td>
                        </tr>
                    </thead>
                </table>
            </div>
            <!-- <div id="msg" class="mt-3"></div> -->
        </div>
    <?php } ?>

    <div class="mt-3">
        <?= $pagination ?>
    </div>

</div>



<div class="floating-div">
    <button class="btn mb-2 btn-edit-active dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-gear fs-16"></i> Aksi
    </button>
    <ul class="dropdown-menu text-end" style="padding:0px;background:unset;border:unset">


        <!-- <li><a class="dropdown-items" href="#!" style="padding:0px;">
                            <button type="button" class="btn mb-2 btn-edit-active" onclick="refresh_data()">
                            <i class="bi bi-bootstrap-reboot fs-16"></i> Refresh Data
                            </button>
                            </a></li> -->

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

    function tampilkan_data(id) {
        window.location.href = "<?= base_url() ?>/crm?&brand=<?= $_GET['brand'] ?>&ids=" + list_id_v2;
    }

    function hapus_data(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Hapus Data');
        $("#load-form").load("<?= base_url() ?>crm/action?code=hapus_data&id=" + id);
    }
    // function refresh_data(id) {
    //     $("#load-form").html('Loading...');
    //     $("#modal-form").modal('show');
    //     $("#title-form").html('Refresh Data');
    //     $("#load-form").load("<?= base_url() ?>crm/action?code=refresh_data&id=" + id);
    // }
    function create() {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Create Data');
        $("#load-form").load("<?= base_url() ?>crm/create");
    }

    function edit(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Edit Data');
        $("#load-form").load("<?= base_url() ?>crm/edit?id=" + id);
    }

    function refresh(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Refresh Data');
        $("#load-form").load("<?= base_url() ?>crm/update-customer-order?id=" + id);
    }

    function remove(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Hapus Data');
        $("#load-form").load("<?= base_url() ?>crm/remove?id=" + id);
    }

    function fu(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('FU H+10 Perkembangan');
        $("#load-form").load("<?= base_url() ?>crm/fu?id=" + id);
    }

    function fu_2(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('FU H-7');
        $("#load-form").load("<?= base_url() ?>crm/fu_2?id=" + id);
    }
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
                    url: "<?= base_url() ?>crm/item<?= $param ?>",
                    success: function(data) {
                        $('#tbody-loading').html('');
                        $('#tbody').append(data);
                        offset += 30;
                        loading = false;
                        if (!data) {
                            complete = true;
                        }
                        select_5();
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