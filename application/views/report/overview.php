<style>
    .custom-card {
        height: 100%;
    }

    tbody:before {
        line-height: 0em;
        content: ".";
        color: white;
        display: block;
    }
    
</style>
<?php
$chart_title = "";
$site = $_GET['site'];
$customer = $_GET['customer'];

if ($_GET['start_date'] == "") {
    $start_date = DATE("Y-m-01");
    
} else {
    $start_date = $_GET['start_date'];
}
if ($_GET['until_date'] == "") {
    $until_date = DATE("Y-m-d");
} else {
    $until_date = $_GET['until_date'];
}

if ($_GET['start_year'] == "") {
    $start_year = DATE("Y");
} else {
    $start_year = $_GET['start_year'];
}

if ($_GET['until_year'] == "") {
    $until_year = DATE("Y");
} else {
    $until_year = $_GET['until_year'];
}

if ($_GET['start_month'] == "") {
    $start_month = "1";
} else {
    $start_month = $_GET['start_month'];
}

if ($_GET['until_month'] == "") {
    $until_month = DATE("m");
} else {
    $until_month = $_GET['until_month'];
}

if ($_GET['start_week'] == "") {
    $start_week = "1";
} else {
    $start_week = $_GET['start_week'];
}

if ($_GET['until_week'] == "") {
    $until_week = DATE("W", strtotime(DATE('Y-m-d')));
} else {
    $until_week = $_GET['until_week'];
}



$type = $_GET['type'];

if ($_GET['type'] == "Yearly") {
    $chart_title = $start_year . ' - ' . $until_year;
} else if ($_GET['type'] == "Monthly") {
    $chart_title = 'Month ' . $start_month . ' - ' . $until_month . ' ' . $start_year;
} else if ($_GET['type'] == "Weekly") {
    $chart_title = 'Week ' . $start_week . ' - ' . $until_week . ' ' . $start_year;
} else {
    $type = "Daily";
    $chart_title = DATE('d M Y', strtotime($start_date)) . ' - ' . DATE('d M Y', strtotime($until_date));
}
?>
<div class="form-message"></div>
<div class="w-100">
    <div class="row align-items-center">
        <div class="col-lg-12 mb-3">
            <h3 class="text-primary fw-600">REPORT</h3>
        </div>
        <?php $this->load->view('report/menu') ?>
        <div class="col-lg-12 mb-3">

            <form action="">
                <div class="row">
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-md-4">
                                <select class="form-control select2" name="brand" id="brand">
                                    <option value="">Brand</option>
                                    <?php foreach ($brands as $val) :
                                        $text = "";
                                        if ($_GET["brand"] == $val["code"]) {
                                            $text = "selected";
                                        }
                                    ?>
                                        <option <?= $text ?> value="<?= $val["code"] ?>"><?= $val["code"] ?></option>
                                    <?php
                                    endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select class="form-control select2" name="channel">
                                    <option value="">Channel</option>
                                    <?php foreach ($channel_2 as $val) :
                                        $text = "";
                                        if ($_GET["channel"] == $val["name"]) {
                                            $text = "selected";
                                        }
                                    ?>
                                        <option <?= $text ?> value="<?= $val["name"] ?>"><?= $val["name"] ?></option>
                                    <?php
                                    endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control" id="tanggal" placeholder="Pilih rentang tanggal...">
                        <input type="hidden" name="start_date" id="start_date" value="<?= $_GET['start_date'] ?? $start_date ?>">
                        <input type="hidden" name="until_date" id="end_date" value="<?= $_GET['until_date'] ?? $until_date ?>">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100 form-control" type="submit"><i class="bi bi-search fs-16"></i> Cari Data</button>
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
                </div>
            </form>
        </div>
        <div class="col-lg-12">
            <div class="row">
                <!-- <div class="col-lg-12 mb-3">
                    <div class="card custom-card">
                        <h5 class="mb-0">Marketplace Order</h5>
                        <hr class="mb-2 mt-2">
                        <div id="report-1"><?php $this->load->view('loading_v2', true) ?> </div>
                        <script>
                            $.ajax({
                                dataType: "json",
                                url: '<?= base_url() ?>ajax/get-report?code=1&brand=<?= $_GET['brand'] ?>&channel=<?= $_GET['channel'] ?>&type=<?= $type ?>&start_date=<?= $start_date ?>&until_date=<?= $until_date ?>&start_year=<?= $start_year ?>&until_year=<?= $until_year ?>&start_month=<?= $start_month ?>&until_month=<?= $until_month ?>&start_week=<?= $start_week ?>&until_week=<?= $until_week ?>',
                                success: function(html) {
                                    $("#report-1").html(html.html);
                                }
                            });
                        </script>
                    </div>
                </div> -->
                <div class="col-lg-6 mb-3">
                    <div class="card custom-card">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h5 class="mb-0">Stok Produk</h5>

                            <div class="d-flex align-items-center gap-3">
                                <select class="form-select w-auto" name="jenis" id="jenis">
                                    <?php
                                    $currentJenis = $_GET['jenis'] ?? 'produk_jual'; 
                                    $jenisOptions = [
                                        'produk_jual' => 'Produk Jual',
                                        'produk_operasional' => 'Produk Operasional',
                                        '' => 'Semua Produk',
                                    ];

                                    foreach ($jenisOptions as $value => $label) {
                                        $selected = ($currentJenis === $value) ? 'selected' : '';
                                        echo "<option value=\"$value\" $selected>$label</option>";
                                    }
                                    ?>
                                </select>

                                <select class="form-select w-auto" name="status" id="status">
                                    <?php
                                    $currentStatus = $_GET['status'] ?? 'Aktif'; 
                                    $statusOptions = [
                                        'Aktif' => 'Aktif',
                                        'Tidak Aktif' => 'Tidak Aktif',
                                        '' => 'Semua',
                                    ];

                                    foreach ($statusOptions as $value => $label) {
                                        $selected = ($currentStatus === $value) ? 'selected' : '';
                                        echo "<option value=\"$value\" $selected>$label</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <hr class="mb-2 mt-2">
                        <div id="report-11"><?php $this->load->view('loading_v2', true) ?> </div>
                        
                        <script>
                            function loadReportData() {
                                const jenis = $('#jenis').val();
                                const status = $('#status').val();
                                
                                $("#report-11").html(<?= json_encode($this->load->view("loading_v2", true, true)) ?>);
                                
                                $.ajax({
                                    dataType: "json",
                                    url: '<?= base_url() ?>ajax/get-report?code=11b&brand=<?= $_GET['brand'] ?>&channel=<?= $_GET['channel'] ?>&type=<?= $type ?>&start_date=<?= $start_date ?>&until_date=<?= $until_date ?>&jenis=' + jenis + '&status=' + status,
                                    success: function(html) {
                                        $("#report-11").html(html.html);
                                    },
                                    error: function(xhr, status, error) {
                                        $("#report-11").html('<div class="alert alert-danger">Error loading data</div>');
                                    }
                                });
                            }
                            
                            $(document).ready(function() {
                                loadReportData();
                                
                                $('#jenis, #status').change(function() {
                                    loadReportData();
                                });
                            });
                        </script>
                    </div>
                </div>
                <!-- <div class="col-lg-12 mb-3">
                    <div class="card custom-card">
                        <h5 class="mb-0">Produk Marketplace Terlaris</h5>
                        <hr class="mb-2 mt-2">
                        <div id="report-1a"><?php $this->load->view('loading_v2', true) ?> </div>
                        <script>
                            $.ajax({
                                dataType: "json",
                                url: '<?= base_url() ?>ajax/get-report?code=1a&brand=<?= $_GET['brand'] ?>&channel=<?= $_GET['channel'] ?>&type=<?= $type ?>&start_date=<?= $start_date ?>&until_date=<?= $until_date ?>&start_year=<?= $start_year ?>&until_year=<?= $until_year ?>&start_month=<?= $start_month ?>&until_month=<?= $until_month ?>&start_week=<?= $start_week ?>&until_week=<?= $until_week ?>',
                                success: function(html) {
                                    $("#report-1a").html(html.html);
                                }
                            });
                        </script>
                    </div>
                </div> -->
                <!-- <div class="col-lg-6 mb-3">
                    <div class="card custom-card">
                        <h5 class="mb-0">Pelanggan Terbaik</h5>
                        <hr class="mb-2 mt-2">
                        <div id="report-2"><?php $this->load->view('loading_v2', true) ?> </div>
                        <script>
                            $.ajax({
                                dataType: "json",
                                url: '<?= base_url() ?>ajax/get-report?code=2&brand=<?= $_GET['brand'] ?>&channel=<?= $_GET['channel'] ?>&type=<?= $type ?>&start_date=<?= $start_date ?>&until_date=<?= $until_date ?>&start_year=<?= $start_year ?>&until_year=<?= $until_year ?>&start_month=<?= $start_month ?>&until_month=<?= $until_month ?>&start_week=<?= $start_week ?>&until_week=<?= $until_week ?>',
                                success: function(html) {
                                    $("#report-2").html(html.html);
                                }
                            });
                        </script>
                    </div>
                </div> -->
                <div class="col-lg-6 mb-3">
                    <div class="card">
                        <h5 style="margin-top: 9px; margin-bottom: 9px;">Channel Penjualan Terbaik</h5>
                        <hr class="mb-2 mt-2">
                        <div id="report-3"><?php $this->load->view('loading_v2', true) ?> </div>
                        <script>
                            $.ajax({
                                dataType: "json",
                                url: '<?= base_url() ?>ajax/get-report?code=3&brand=<?= $_GET['brand'] ?>&channel=<?= $_GET['channel'] ?>&type=<?= $type ?>&start_date=<?= $start_date ?>&until_date=<?= $until_date ?>&start_year=<?= $start_year ?>&until_year=<?= $until_year ?>&start_month=<?= $start_month ?>&until_month=<?= $until_month ?>&start_week=<?= $start_week ?>&until_week=<?= $until_week ?>',
                                success: function(html) {
                                    $("#report-3").html(html.html);
                                }
                            });
                        </script>
                    </div>
                </div>
                <div class="col-lg-12 mb-3">
                    <div class="card custom-card">
                        <div class="d-flex align-items-center justify-content-between">
                            <h5 class="mb-0">Order Bundling</h5>
                            <div class="d-flex align-items-center gap-2">
                                <!-- opsional tombol reset filter (baris akan muncul semua lagi) -->
                                <button type="button" id="hpp-reset" class="btn btn-sm btn-outline-secondary">Reset Filter</button>
                            </div>
                        </div>
                        <hr class="mb-2 mt-2">
                        <div id="report-hpp"><?php $this->load->view('loading_v2', true) ?></div>
                        <script>
                            function loadReportHPP() {
                                const jenisVal = $('#jenis').val() || ''; // 'produk_jual' / 'produk_operasional' / ''
                                const jenisProduk = (jenisVal === 'produk_jual') ? 'Produk Jual' :
                                                    (jenisVal === 'produk_operasional') ? 'Produk Operasional' : '';

                                $("#report-hpp").html(<?= json_encode($this->load->view("loading_v2", true, true)) ?>);

                                $.ajax({
                                    dataType: "json",
                                    url: '<?= base_url() ?>ajax/get-report',
                                    data: {
                                        code        : 'hpp',
                                        brand       : '<?= $_GET['brand'] ?? '' ?>',
                                        channel     : '<?= $_GET['channel'] ?? '' ?>',
                                        type        : '<?= $type ?>',
                                        start_date  : '<?= $start_date ?>',
                                        until_date  : '<?= $until_date ?>',
                                        start_year  : '<?= $start_year ?>',
                                        until_year  : '<?= $until_year ?>',
                                        start_month : '<?= $start_month ?>',
                                        until_month : '<?= $until_month ?>',
                                        start_week  : '<?= $start_week ?>',
                                        until_week  : '<?= $until_week ?>',
                                        // penting: biar logika server sama dengan halaman HPP bundling versi khusus
                                        jenis_produk: jenisProduk
                                    },
                                    success: function(resp) {
                                        $("#report-hpp").html(resp.html);

                                        // tombol reset filter (dibuat di view atas)
                                        $('#hpp-reset').off('click').on('click', function(){
                                            const $menu = $('#hpp-product-filter');
                                            $menu.find('input.hpp-bundle').prop('checked', true).trigger('change');
                                        });

                                    },
                                    error: function() {
                                        $("#report-hpp").html('<div class="alert alert-danger">Gagal memuat HPP Bundling</div>');
                                    }
                                });
                            }

                            $(document).ready(function () {
                                loadReportHPP();
                                // sinkron sama filter Stok Produk
                                $('#jenis, #status').on('change', loadReportHPP);
                            });
                        </script>
                    </div>
                </div>

                <!-- <div class="col-lg-6 mb-3">
                    <div class="card custom-card">
                        <h5 class="mb-0">Lokasi Pelanggan Terpopuler</h5>
                        <hr class="mb-2 mt-2">
                        <div id="report-4"><?php $this->load->view('loading_v2', true) ?> </div>
                        <script>
                            $.ajax({
                                dataType: "json",
                                url: '<?= base_url() ?>ajax/get-report?code=4&brand=<?= $_GET['brand'] ?>&channel=<?= $_GET['channel'] ?>&type=<?= $type ?>&start_date=<?= $start_date ?>&until_date=<?= $until_date ?>&start_year=<?= $start_year ?>&until_year=<?= $until_year ?>&start_month=<?= $start_month ?>&until_month=<?= $until_month ?>&start_week=<?= $start_week ?>&until_week=<?= $until_week ?>',
                                success: function(html) {
                                    $("#report-4").html(html.html);
                                }
                            });
                        </script>
                    </div>
                </div> -->
                <!-- <div class="col-lg-6 mb-3">
                    <div class="card custom-card">
                        <h5 class="mb-0">Budget CRM <?= $_GET['brand'] ?> <?= DATE("Y", strtotime($start_date)) ?></h5>
                        <hr class="mb-2 mt-2">
                        <div id="report-5"><?php $this->load->view('loading_v2', true) ?> </div>
                        <script>
                            $.ajax({
                                dataType: "json",
                                url: '<?= base_url() ?>ajax/get-report?code=5&brand=<?= $_GET['brand'] ?>&channel=<?= $_GET['channel'] ?>&type=<?= $type ?>&start_date=<?= $start_date ?>&until_date=<?= $until_date ?>&start_year=<?= $start_year ?>&until_year=<?= $until_year ?>&start_month=<?= $start_month ?>&until_month=<?= $until_month ?>&start_week=<?= $start_week ?>&until_week=<?= $until_week ?>',
                                success: function(html) {
                                    $("#report-5").html(html.html);
                                }
                            });
                        </script>
                    </div>
                </div> -->
                <!-- <div class="col-lg-12 mb-3">
                    <div class="card custom-card">
                        <h5 class="mb-0">Endorse Report <?= $_GET['brand'] ?> <?= DATE("Y", strtotime($start_date)) ?></h5>
                        <hr class="mb-2 mt-2">
                        <div id="report-6"><?php $this->load->view('loading_v2', true) ?> </div>
                        <script>
                            $.ajax({
                                dataType: "json",
                                url: '<?= base_url() ?>ajax/get-report?code=6&brand=<?= $_GET['brand'] ?>&channel=<?= $_GET['channel'] ?>&type=<?= $type ?>&start_date=<?= $start_date ?>&until_date=<?= $until_date ?>&start_year=<?= $start_year ?>&until_year=<?= $until_year ?>&start_month=<?= $start_month ?>&until_month=<?= $until_month ?>&start_week=<?= $start_week ?>&until_week=<?= $until_week ?>',
                                success: function(html) {
                                    $("#report-6").html(html.html);
                                }
                            });
                        </script>
                    </div>
                </div> -->
                <!-- <div class="col-lg-12 mb-3">
                    <div class="card custom-card">
                        <h5 class="mb-0">KOL Report <?= $_GET['brand'] ?> <?= DATE("Y", strtotime($start_date)) ?></h5>
                        <hr class="mb-2 mt-2">
                        <div id="report-7"><?php $this->load->view('loading_v2', true) ?> </div>
                        <script>
                            $.ajax({
                                dataType: "json",
                                url: '<?= base_url() ?>ajax/get-report?code=7&brand=<?= $_GET['brand'] ?>&channel=<?= $_GET['channel'] ?>&type=<?= $type ?>&start_date=<?= $start_date ?>&until_date=<?= $until_date ?>&start_year=<?= $start_year ?>&until_year=<?= $until_year ?>&start_month=<?= $start_month ?>&until_month=<?= $until_month ?>&start_week=<?= $start_week ?>&until_week=<?= $until_week ?>',
                                success: function(html) {
                                    $("#report-7").html(html.html);
                                }
                            });
                        </script>
                    </div>
                </div> -->
            </div>
        </div>
    </div>
</div>