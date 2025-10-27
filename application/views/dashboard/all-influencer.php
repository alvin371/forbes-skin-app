<style>
    .owl-theme .owl-dots .owl-dot.active span,
    .owl-theme .owl-dots .owl-dot:hover span {
        background: #1255cc;
    }

    .owl-theme .owl-dots .owl-dot span {
        background: #1155CC1A;
        margin: 3px;
    }

    .owl-theme .owl-nav.disabled+.owl-dots {
        margin-top: 0px;
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

<div class="w-100">
    <div class="row align-items-center">
        <div class="col-lg-12 mb-1">

            <div class="row">
                <div class="col-md-12">
                    <h3 class="text-primary fw-500">DASHBOARD INFLUENCER ACNENO SYSTEM</h3>
                </div>
            </div>
            <?php $this->load->view('dashboard/menu') ?>
        </div>




        <div class="col-lg-12">



            <form action="" class="">
                <input type="hidden" name="t" value="influencer">
                <div class="row">

                    <div class="col-md-6">
                        <div class="row">
                            <div class="col-md-4">
                                <select class="form-control select2" name="campaign" id="brand">
                                    <option value="">Campaign</option>
                                    <?php foreach ($campaign as $val) :
                                        $text = "";
                                        if ($_GET["campaign"] == $val["id"]) {
                                            $text = "selected";
                                        }
                                    ?>
                                        <option <?= $text ?> value="<?= $val["id"] ?>"><?= $val["title"] ?></option>
                                    <?php
                                    endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select class="form-control select2" name="platform">
                                    <option value="">Platform</option>
                                    <?php
                                    $arr = array();
                                    $arr[] = "Tiktok";
                                    $arr[] = "Instagram";
                                    $arr[] = "Twitter";
                                    $arr[] = "Youtube";
                                    foreach ($arr as $val) :
                                        $text = "";
                                        if ($_GET["platform"] == $val) {
                                            $text = "selected";
                                        }
                                    ?>
                                        <option <?= $text ?> value="<?= $val ?>"><?= $val ?></option>
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



        <h4 class="text-primary fw-500 mt-4">INFLUENCER REPORT</h4>
        <div class="col-lg-12 mb-3">
            <div class="card custom-card">
                <h5 class="mb-0">Persebaran Konten</h5>
                <hr class="mb-2 mt-2">
                <div id="influencer-1"><?php $this->load->view('loading_v2', true) ?> </div>
                <script>
                    $.ajax({
                        dataType: "json",
                        url: '<?= base_url() ?>ajax/get-report-influencer?code=1&platform=<?= $_GET['platform'] ?>&campaign=<?= $_GET['campaign'] ?>&type=<?= $type ?>&start_date=<?= $start_date ?>&until_date=<?= $until_date ?>&start_year=<?= $start_year ?>&until_year=<?= $until_year ?>&start_month=<?= $start_month ?>&until_month=<?= $until_month ?>&start_week=<?= $start_week ?>&until_week=<?= $until_week ?>',
                        success: function(html) {
                            $("#influencer-1").html(html.html);
                        }
                    });
                </script>
            </div>
        </div>
    </div>