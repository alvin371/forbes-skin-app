
<div class="form-message"></div>
<div class="w-100">
    <div class="row align-items-center">
        <div class="col-lg-12 mb-3">
            <h3 class="text-primary fw-600">CUSTOMER</h3>
        </div>
        <div class="col-lg-12">
            <form action="">
                <div class="row">
                    <div class="col-md-2">
                        <label for="">PENCARIAN</label>
                        <input type="keyword" class="form-control" name="keyword" value="<?= $_GET['keyword'] ?>">
                    </div>
                <div class="col-md-2">
                        <label for="">BRAND</label>
                        <select class="form-control" name="brand" id="brand">
                            <option value="">Semua Brand</option>
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
                        <label for="">MARKETPLACE</label>
                        <select class="form-control" name="marketplace" >
                            <option value="">Semua Ket</option>
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
                        <label for="">CS</label>
                        <select class="form-control" name="cs" >
                            <option value="">Semua CS</option>
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
                        <label for="">TANGGAL AWAL</label>
                        <input type="date" class="form-control" name="start_date" value="<?= $start_date ?>">
                    </div>

                    <div class="col-md-2">
                        <label for="">TANGAL AKHIR</label>
                        <input type="date" class="form-control" name="until_date" value="<?= $until_date ?>">
                    </div>
                    <div class="col-md-2">
                        <!-- <label for="">&nbsp;</label> -->
                        <button class="btn btn-primary w-100 form-control" type="submit">FILTER DATA</button>
                    </div>
                    <div class="col-md-2">
                        <!-- <label for="">&nbsp;</label> -->
                        <a href="<?= base_url() ?>/customer/create?start_date=<?=$start_date?>&until_date=<?=$until_date?>&brand=<?=$brand?>&marketplace=<?=$_GET['marketplace']?>&cs=<?=$_GET['cs']?>&keyword=<?=$_GET['keyword']?>" class="btn btn-primary w-100 form-control">TAMBAH  DATA</a>
                    </div>
                    <!-- <div class="col-md-2">
                        <label for="">&nbsp;</label>
                        <a href="#!" onclick="import_data('<?= $start_date ?>','<?= $until_date ?>')" class="btn btn-primary w-100 form-control">IMPORT DATA</a>

                    </div> -->
                </div>
            </form>
        </div>

    </div>
    <style>
         thead{
            z-index:3;
        }
        th:first-child {
            position: sticky;
            left: 0px;
            background-color: #5e317a;
            z-index: 2;
        }
        td:nth-child(2) {
            position: sticky;
            left: 0px;
            background-color: #e5e5e5;
            z-index: 2;
        }
        th:nth-child(2) {
            position: sticky;
            left: 94px;
            background-color: #5e317a;
            z-index: 1;
        }
        td:nth-child(3) {
            position: sticky;
            left: 94px;
            background-color: #e5e5e5;
            z-index: 1;
        }
        th:nth-child(3) {
            position: sticky;
            left: 240px;
            background-color: #5e317a;
            z-index: 1;
        }
        td:nth-child(4) {
            position: sticky;
            left: 240px;
            background-color: #e5e5e5;
            z-index: 1;
        }
        th:nth-child(4) {
            position: sticky;
            left: 388px;
            background-color: #5e317a;
            z-index: 1;
        }
        td:nth-child(5) {
            position: sticky;
            left: 388px;
            background-color: #e5e5e5;
            z-index: 1;
        }
        th:nth-child(5) {
            position: sticky;
            left: 538px;
            background-color: #5e317a;
            z-index: 1;
        }
        td:nth-child(6) {
            position: sticky;
            left: 538px;
            background-color: #e5e5e5;
            z-index: 1;
        }
    </style>
    <div class="col-lg-12">
            <div class="table-responsive table-fixed scroll-v-none" id="table-item">
                <table class="table table-hover table-striped table-bordered" >
                <thead>
                    <tr class="bg-primary text-white">
                        <th style="min-width:94px!important">#</th>
                        <th style="min-width:150px!important" class="text-start">TANGGAL DIBUAT</th>
                        <th style="min-width:150px!important" class="text-start">CS</th>
                        <th style="min-width:150px!important" class="text-start">KD</th>
                        <th style="min-width:150px!important" class="text-start">FULL NAME</th>
                        <th style="min-width:150px!important" class="text-start">NOMOR TELEPON</th>
                        <th class="text-start">BRAND</th>
                        <th class="text-start">MARKETPLACE</th>
                        <th class="text-start">TIPE</th>
                        <th class="text-start">USERNAME</th>
                        <th class="text-start">TANGGAL LAHIR</th>
                        <th class="text-start">HISTORY PEMBELIAN</th>
                        <th class="text-start">GROUP</th>
                        <th class="text-start">TANGGAL ORDER</th>
                        <th class="text-start">JOIN</th>
                        <th class="text-end">MASA JOIN</th>
                        <th class="text-start">BATAS JOIN</th>
                        <th class="text-start">WAKTU FU REPEAT ORDER H-7</th>
                        <th class="text-start">FU H+10 PERKEMBANGAN</th>
                        <th class="text-start">REPEAT ORDER</th>
                        <th class="text-start">GIFT</th>
                        <th class="text-start">TREATMENT CUSTOMER EXPERIENCE</th>
                        <th class="text-start">RIWAYAT KELUHAN</th>
                        <th class="text-start">PERKEMBANGAN KONSUMSI</th>
                        <th class="text-start">KETERANGAN</th>
                        <th class="text-start">ALAMAT</th>
                        <th class="text-start">PROVINSI</th>
                        <th class="text-start">KOTA</th>
                        <th class="text-start">KECAMATAN</th>
                        <th style="min-width:60px!important" class="text-end">JUMLAH ORDER</th>
                    </tr>
                </thead>
                <tbody id="tbody">
                </tbody>
            </table>
        </div>
        <!-- <div id="msg" class="mt-3"></div> -->
    </div>

    <?=$pagination?>

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
        $("#load-form").load("<?= base_url() ?>/customer/create");
    }

    function edit(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Edit Data');
        $("#load-form").load("<?= base_url() ?>/customer/edit?id=" + id);
    }

    function remove(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Delete Data');
        $("#load-form").load("<?= base_url() ?>/customer/remove?id=" + id);
    }
</script>


<script>
var complete = false;
var offset = 0;
var loading = false;
function loadMoreData() {
    if(!complete){
        if (!loading) {
            loading = true;
            $.ajax({
                type: 'GET',
                url: "<?= base_url() ?>/customer/item?start_date=<?=$start_date?>&until_date=<?=$until_date?>&brand=<?=$brand?>&marketplace=<?=$_GET['marketplace']?>&cs=<?=$_GET['cs']?>&keyword=<?=$_GET['keyword']?>&page=<?=$_GET['page']?>",
                success: function (data) {
                    $('#tbody').append(data);
                    offset += 30;
                    loading = false;
                    if (!data) {
                        complete = true;
                    }
                    select3();
                },
                error: function (xhr, status, error) {
                    loading = false;
                }
            });
        }
    }else{
        $("#msg").html("<i class='fa fa-check'></i> Proses memuat data selesai!");
    }
}
loadMoreData();
</script>
