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
<style>
    .select2-container .select2-selection--multiple {
        min-height: 45px;
        /* box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.07) !important; */
        border-radius: 0.5rem !important;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        padding: 6px;
    }

    .select2-container .select2-search--inline .select2-search__field {
        padding-top: 6px !important;
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
        <?php $this->load->view('overview/menu') ?>

        <div class="col-lg-12 mb-1">
            <div class="row">
                <div class="col-md-12">
                    <h3 class="text-primary fw-500">DASHBOARD KOL ACNENO SYSTEM</h3>
                </div>
            </div>
            <?php
            // Mengecek apakah URL yang diakses adalah base_url/dashboard?t=kol
            if ($_SERVER['REQUEST_URI'] == '/dashboard?t=kol') {
                $this->load->view('dashboard/menu');
            }
            ?>
        </div>

        <div class="col-lg-12">

            <form action="<?= $url ?>?t=kol" method="GET">
                <input type="hidden" name="t" value="kol">
                <div class="row">

                    <div class="col-md-12">
                        <?php
                        $arr = array();
                        $arr[] = "Semua Status Endorse";
                        $arr[] = "Review";
                        $arr[] = "Hold";
                        $arr[] = "Acc";
                        $arr[] = "DP";
                        $arr[] = "FP";
                        $arr[] = "Barang Dikirim";
                        $arr[] = "Draft Content";
                        $arr[] = "Posted Content";
                        $arr[] = "Reject";
                        $arr[] = "Problem";

                        foreach ($arr as $k => $val) {
                            $class = "btn-default";
                            $class_2 = "dot";

                            $value = $val;
                            if ($k == 0) {
                                $value = '';
                            }
                            $status = isset($_GET['endorse_status']) ? $_GET['endorse_status'] : '';

                            $statusArray = $status ? explode(',', $status) : [];

                            if (($key = array_search($value, $statusArray)) !== false) {
                                unset($statusArray[$key]);
                                $class = "btn-default-selected";
                                $class_2 = "dot-active";
                            } else {
                                $statusArray[] = $value;
                            }

                            $status = implode(',', $statusArray);

                            if ($k == 0) {
                                $status = '';
                            }

                            if ($k == 0 && $_GET['endorse_status'] == "") {
                                $class_2 = "dot-active";
                                $class = "btn-default-selected";
                            }

                        ?>
                            <a href="<?= $url ?>&endorse_status=<?= $status ?>" class="btn <?= $class ?> mb-2 me-2"><span class="<?= $class_2 ?>"></span> <?= $val ?></a>
                        <?php }  ?>
                        <div class="col-md-12"></div>
                        <?php
                        $arr = array();

                        $arr[] = "Semua Kategori";
                        $arr[] = "Ada Link Upload";
                        $arr[] = "Tidak Ada Link Upload";
                        $arr[] = "FYP";
                        foreach ($arr as $k => $val) {
                            $class = "btn-default";
                            $class_2 = "dot";

                            $value = $val;
                            if ($k == 0) {
                                $value = '';
                            }
                            $value = str_replace('&', '', $value);

                            if ($_GET['category'] == $value) {
                                $class = "btn-default-selected";
                                $class_2 = "dot-active";
                            }
                        ?>
                            <a href="<?= $url_2 ?>&category=<?= $value ?>" class="btn <?= $class ?> mb-2 me-2"><span class="<?= $class_2 ?>"></span> <?= $val ?></a>
                        <?php }  ?>
                        <div class="col-md-12"></div>
                        <?php
                        $arr = array();
                        $arr[] = "Semua Status Pembayaran";
                        $arr[] = "Pengajuan Payment";
                        $arr[] = "DP";
                        $arr[] = "FP";

                        foreach ($arr as $k => $val) {
                            $class = "btn-default";
                            $class_2 = "dot";

                            $value = $val;
                            if ($k == 0) {
                                $value = '';
                            }
                            $status = isset($_GET['status_payment']) ? $_GET['status_payment'] : '';

                            $statusArray = $status ? explode(',', $status) : [];

                            if (($key = array_search($value, $statusArray)) !== false) {
                                unset($statusArray[$key]);
                                $class = "btn-default-selected";
                                $class_2 = "dot-active";
                            } else {
                                $statusArray[] = $value;
                            }

                            $status = implode(',', $statusArray);

                            if ($k == 0) {
                                $status = '';
                            }

                            if ($k == 0 && $_GET['status_payment'] == "") {
                                $class_2 = "dot-active";
                                $class = "btn-default-selected";
                            }
                        ?>
                            <a href="<?= $url ?>&status_payment=<?= $status ?>" class="btn <?= $class ?> mb-2 me-2"><span class="<?= $class_2 ?>"></span> <?= $val ?></a>
                        <?php } ?>
                    </div>

                    <input type="hidden" name="endorse_status" value="<?= $_GET['endorse_status'] ?>">
                    <input type="hidden" name="category" value="<?= $_GET['category'] ?>">
                    <input type="hidden" name="status_data" value="<?= $_GET['status_data'] ?>">
                    <input type="hidden" name="status_payment" value="<?= $_GET['status_payment'] ?>">

                    <div class="col-md-12">
                        <?php
                        $arr = array();

                        $arr[] = "Semua Status";
                        $arr[] = "Aktif";
                        $arr[] = "Tidak Aktif";
                        foreach ($arr as $k => $val) {
                            $class = "btn-default";
                            $class_2 = "dot";

                            $value = $val;
                            if ($k == 0) {
                                $value = '';
                            }
                            $value = str_replace('&', '', $value);

                            if ($_GET['status_data'] == $value) {
                                $class = "btn-default-selected";
                                $class_2 = "dot-active";
                            }
                        ?>
                            <a href="<?= $url_2 ?>&status_data=<?= $value ?>" class="btn <?= $class ?> mb-2 me-2"><span class="<?= $class_2 ?>"></span> <?= $val ?></a>
                        <?php }  ?>
                    </div>

                    <div class="col-md-10">
                        <select class="form-control select2" name="ids_campaign[]" id="campaign" multiple="multiple" data-placeholder="Pilih Campaign...">
                            <?php
                            $ids = $_GET['ids_campaign'];
                            if (empty($ids)) {
                                $ids = array();
                            }
                            foreach ($campaign as $val) :
                                $text = "";
                                if (in_array($val['id'], $ids)) {
                                    $text = "selected";
                                }
                            ?>
                                <option <?= $text ?> value="<?= $val["id"] ?>"><?= $val["title"] ?></option>
                            <?php
                            endforeach; ?>
                        </select>
                    </div>
                    <!-- Additional filters: PIC (per content), Product, Endorsement Category -->
                    <div class="col-md-12 mt-2">
                        <div class="row">
                            <div class="col-md-4">
                                <select class="form-control select2" name="pic[]" id="pic" multiple="multiple" data-placeholder="Pilih PIC...">
                                    <?php
                                    $selected_pics = $_GET['pic'] ?? [];
                                    if (!is_array($selected_pics)) { $selected_pics = [$selected_pics]; }
                                    foreach (($pic_options ?? []) as $opt) :
                                        $val = is_array($opt) ? ($opt['name'] ?? '') : ($opt->name ?? '');
                                        if ($val === '') continue;
                                        $sel = in_array($val, $selected_pics) ? 'selected' : '';
                                    ?>
                                        <option <?= $sel ?> value="<?= htmlspecialchars($val, ENT_QUOTES) ?>"><?= htmlspecialchars($val) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select class="form-control select2" name="product[]" id="product" multiple="multiple" data-placeholder="Pilih Produk...">
                                    <?php
                                    $selected_products = $_GET['product'] ?? [];
                                    if (!is_array($selected_products)) { $selected_products = [$selected_products]; }
                                    foreach (($product_options ?? []) as $opt) :
                                        $pid = is_array($opt) ? ($opt['id'] ?? '') : ($opt->id ?? '');
                                        $pname = is_array($opt) ? ($opt['name'] ?? '') : ($opt->name ?? '');
                                        if ($pid === '' || $pname === '') continue;
                                        $sel = in_array((string)$pid, array_map('strval', $selected_products)) ? 'selected' : '';
                                    ?>
                                        <option <?= $sel ?> value="<?= htmlspecialchars($pid, ENT_QUOTES) ?>"><?= htmlspecialchars($pname) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select class="form-control" name="endorse_category" id="endorse_category" title="Pilih Kategori Endorse">
                                    <?php
                                    $endorse_category = $_GET['endorse_category'] ?? '';
                                    $categories = [
                                        '' => 'Semua Kategori Endorse',
                                        'internal' => 'Internal',
                                        'external' => 'External',
                                    ];
                                    foreach ($categories as $k => $v) {
                                        $sel = ($endorse_category === $k) ? 'selected' : '';
                                        echo '<option ' . $sel . ' value="' . htmlspecialchars($k, ENT_QUOTES) . '">' . htmlspecialchars($v) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <script>
                        $(function(){
                            try { $('#campaign').select2({ placeholder: $('#campaign').data('placeholder') || 'Pilih Campaign...', allowClear: true }); } catch(e) {}
                            try { $('#pic').select2({ placeholder: $('#pic').data('placeholder') || 'Pilih PIC...', allowClear: true }); } catch(e) {}
                            try { $('#product').select2({ placeholder: $('#product').data('placeholder') || 'Pilih Produk...', allowClear: true }); } catch(e) {}
                        });
                    </script>
                    <div class="row">
                        <div class="col-md-5">
                            <div class="row">
                                <div class="col-md-4">
                                    <select class="form-control " name="platform" id="platform">
                                        <option value="">Semua Platform</option>
                                        <?php
                                        $arr = array();
                                        $arr[] = "Instagram";
                                        $arr[] = "Tiktok";
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
                                <div class="col-md-5">
                                    <?php
                                    $arr = [];
                                    $arr[] = "";
                                    $arr[] = "Tanggal Dibuat";
                                    // $arr[] = "Rencana Upload";
                                    $arr[] = "Tanggal Posting";
                                    $arr[] = "Tanggal TF";
                                    ?>
                                    <select class="form-control " name="cat">
                                        <?php foreach ($arr as $k => $v) {
                                            $text = "";
                                            if ($_GET['cat'] == $v) {
                                                $text = "selected";
                                            }
                                        ?>
                                            <option <?= $text ?> value="<?= $v ?>"><?= $v ?></option>
                                        <?php
                                        } ?>
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
                                        page: "endorse"
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
                    <div class="col-lg-6">
                        <!-- <button class="btn btn-edit-active" type="submit"><i class="bi bi-search fs-16"></i> Cari Data</button> -->
                    </div>
                    <div class="col-lg-6 text-end">
                        <!-- <a href="#!" onclick="sync_all('<?= $detail['id'] ?>')" class="btn btn-sync mt-0 ms-1"><i class="bi bi-bootstrap-reboot fs-16"></i> Refresh Semua</a>
                    <a href="#!" onclick="create('<?= $detail['id'] ?>')" class="btn btn-primary mt-0 ms-1"><i class="bi bi-plus-circle-dotted fs-16"></i> Tambah Konten</a> -->
                    </div>

                </div>
            </form>




        </div>



        <h4 class="text-primary fw-500 mt-4">INFLUENCER KOL MARKETING</h4>
        <div class="col-lg-12 mt-3">
            <div class="row">
                <?php
                $sum = array();
                $i = 0;
                $sum[$i]['code'] = "kol-1";
                $sum[$i]['img'] = "bi bi-people";
                $sum[$i]['color_box'] = "#F2994A1A";
                $sum[$i]['color_icon'] = "#f2994a";
                $sum[$i]['title'] = "INFLUENCER";
                $sum[$i]['unit'] = "BCM";
                $i++;
                $sum[$i]['code'] = "kol-2";
                $sum[$i]['img'] = "bi bi-person-video2";
                $sum[$i]['color_box'] = "#F2994A1A";
                $sum[$i]['color_icon'] = "#f2994a";
                $sum[$i]['title'] = "ENDORSE";
                $sum[$i]['unit'] = "BCM";
                $i++;
                $sum[$i]['code'] = "kol-3";
                $sum[$i]['img'] = "bi bi-coin";
                $sum[$i]['color_box'] = "#F2994A1A";
                $sum[$i]['color_icon'] = "#f2994a";
                $sum[$i]['title'] = "COST";
                $sum[$i]['unit'] = "BCM";
                $i++;
                $sum[$i]['code'] = "kol-4";
                $sum[$i]['img'] = "bi bi-eye";
                $sum[$i]['color_box'] = "#F2994A1A";
                $sum[$i]['color_icon'] = "#f2994a";
                $sum[$i]['title'] = "VIEW";
                $sum[$i]['unit'] = "BCM";
                $i++;
                $sum[$i]['code'] = "kol-5";
                $sum[$i]['img'] = "bi bi-heart";
                $sum[$i]['color_box'] = "#F2994A1A";
                $sum[$i]['color_icon'] = "#f2994a";
                $sum[$i]['title'] = "LIKE";
                $sum[$i]['unit'] = "BCM";
                $i++;
                $sum[$i]['code'] = "kol-6";
                $sum[$i]['img'] = "bi bi-chat-quote";
                $sum[$i]['color_box'] = "#F2994A1A";
                $sum[$i]['color_icon'] = "#f2994a";
                $sum[$i]['title'] = "COMMENT";
                $sum[$i]['unit'] = "BCM";
                $i++;
                $sum[$i]['code'] = "kol-7";
                $sum[$i]['img'] = "bi bi-bookmarks";
                $sum[$i]['color_box'] = "#F2994A1A";
                $sum[$i]['color_icon'] = "#f2994a";
                $sum[$i]['title'] = "SAVE & SHARE";
                $sum[$i]['unit'] = "BCM";
                $i++;
                $sum[$i]['code'] = "kol-8";
                $sum[$i]['img'] = "bi bi-cursor";
                $sum[$i]['color_box'] = "#F2994A1A";
                $sum[$i]['color_icon'] = "#f2994a";
                $sum[$i]['title'] = "CPM";
                $sum[$i]['unit'] = "BCM";
                $i++;

                ?>
                <?php foreach ($sum as $k => $v) { ?>
                    <div class="text-start mb-4 col-md-3 col-6">
                        <div class="card h-100">
                            <div class="row">
                                <div class="col-12" style="position:relative">
                                    <div class="row">
                                        <div class="firstDiv">
                                            <div class="firstCircle">
                                                <div class="box-icon mb-2 text-center" style="background-color:<?= $v['color_box'] ?>;">
                                                    <i class="<?= $v['img'] ?>" style="color:<?= $v['color_icon'] ?>"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="secondDiv">
                                            <p class="fw-500 mb-1 text-end"><?= $v['title'] ?></p>
                                            <h4 class="fw-500 mb-1 text-end" id="summary-<?= $v['code'] ?>"><i class="fa fa-circle-o-notch fa-spin"></i></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- <?php if (in_array($v['code'], array('kol-1', 'kol-2'))) { ?>
                        <script>
                            $.ajax({
                                dataType: "json",
                                url: '<?= base_url() ?>ajax/get_summary_v2?site=<?= $site ?>&id=<?= $v['code'] ?>&brand=<?= $_GET['brand'] ?>&type=<?= $type ?>&start_date=<?= $start_date ?>&until_date=<?= $until_date ?>&start_year=<?= $start_year ?>&until_year=<?= $until_year ?>&start_month=<?= $start_month ?>&until_month=<?= $until_month ?>&start_week=<?= $start_week ?>&until_week=<?= $until_week ?>',
                                success: function(html) {
                                    $("#summary-<?= $v['code'] ?>").html(html.html);
                                }
                            });
                        </script>
                    <?php } ?> -->
                <?php } ?>
            </div>
        </div>


        <div class="col-lg-12 mb-3">
            <div class="card summary">
                <h4 class="text-primary fw-500 mb-1">GRAFIK CAMPAIGN</h3>
                    <p class="mb-2"><?= $title_2 ?></p>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="d-grid d-md-flex d-lg-flex">
                                <?php
                                $arr = array();
                                $arr[] = "Daily";
                                $arr[] = "Views";
                                $arr[] = "CPM";
                                $arr[] = "Likes";
                                $arr[] = "Comments";
                                $arr[] = "Share & Save";
                                $arr[] = "Cost";
                                $arr[] = "Jumlah Konten";
                                foreach ($arr as $k => $v) {
                                    $text = "";
                                    if ($checkbox_campaign[$k] == 'true') {
                                        $text = "checked";
                                    }
                                    if (empty($checkbox_campaign)) {
                                        $text = "checked";
                                    }
                                ?>
                                    <input onclick="checkbox2()" <?= $text ?> type="checkbox" id="cc-<?= $k ?>" class="me-2 cc-checkbox"><label for="cc-<?= $k ?>" class="fw-400 me-2"><?= $v ?></label>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <div id="summary-chart"><i class="fa fa-circle-o-notch fa-spin"></i> Memuat data ...</div>
                    <div id="summary-table"><i class="fa fa-circle-o-notch fa-spin"></i> Memuat data ...</div>
                    <script>
                        get_chart();

                        function get_chart() {
                            $.ajax({
                                dataType: "json",
                                url: '<?= base_url() ?>ajax/get-chart-campaign<?= $param ?>&is_dashboard=true',
                                success: function(html) {
                                    $("#summary-chart").html(html.html);
                                    $("#summary-table").html(html.table);

                                    $("#summary-kol-7").html('<i class="fa fa-circle-o-notch fa-spin"></i>');
                                    $("#summary-kol-7").html(html.summary.share);
                                    $("#summary-kol-8").html('<i class="fa fa-circle-o-notch fa-spin"></i>');
                                    $("#summary-kol-8").html(html.summary.cpm);
                                    $("#summary-kol-4").html('<i class="fa fa-circle-o-notch fa-spin"></i>');
                                    $("#summary-kol-4").html(html.summary.view);
                                    $("#summary-kol-5").html('<i class="fa fa-circle-o-notch fa-spin"></i>');
                                    $("#summary-kol-5").html(html.summary.likes);
                                    $("#summary-kol-6").html('<i class="fa fa-circle-o-notch fa-spin"></i>');
                                    $("#summary-kol-6").html(html.summary.comment);
                                    $("#summary-kol-3").html('<i class="fa fa-circle-o-notch fa-spin"></i>');
                                    $("#summary-kol-3").html(html.summary.cost);
                                    $("#summary-kol-1").html('<i class="fa fa-circle-o-notch fa-spin"></i>');
                                    $("#summary-kol-1").html(html.summary.influencer);
                                    $("#summary-kol-2").html('<i class="fa fa-circle-o-notch fa-spin"></i>');
                                    $("#summary-kol-2").html(html.summary.endorse);

                                }
                            });
                        }

                        function checkbox2() {
                            var checkboxStatus = {};
                            var i = 0;
                            $(".cc-checkbox").each(function() {
                                var isChecked = $(this).prop("checked");
                                checkboxStatus[i] = isChecked;
                                i++;
                            });

                            var queryParams = $.param(checkboxStatus);
                            console.log(queryParams);
                            $.ajax({
                                type: "GET",
                                dataType: "json",
                                url: '<?= base_url() ?>ajax/checkbox?type=dashboard_campaign&' + queryParams,
                                success: function(response) {
                                    get_chart();
                                }
                            });
                        }
                    </script>
            </div>
        </div>

        <!-- <style>
        #calendar table tr th {
        font-size: 12px !important;
        max-width:unset!important;
        min-width:unset!important;
    }
    table tr:first-child th:first-child {
        font-size: 12px !important;
        max-width:unset!important;
        min-width:unset!important;
        width:unser!important;
    }
    </style>

    <h4 class="text-primary fw-500 mt-4">ENDORSEMENT CONTENTS CALENDAR</h4>
    <div class="col-lg-12 mt-3">
        <div class="row">
                <div class="text-start mb-4 col-md-12">
                <div class="card h-100">
                <div id="summary-calendar">
                    <i class="fa fa-circle-o-notch fa-spin"></i> Memuat data ...
                    </div>
                    </div>
                </div>
                <script>
                    $.ajax({
                        dataType: "json",
                        url: '<?= base_url() ?>ajax/get_summary?site=<?= $site ?>&id=calendar&brand=<?= $_GET['brand'] ?>&channel=<?= $_GET['channel'] ?>&type=<?= $type ?>&start_date=<?= $start_date ?>&until_date=<?= $until_date ?>&start_year=<?= $start_year ?>&until_year=<?= $until_year ?>&start_month=<?= $start_month ?>&until_month=<?= $until_month ?>&start_week=<?= $start_week ?>&until_week=<?= $until_week ?>',
                        success: function(html) {
                            $("#summary-calendar").html(html.html);
                        }
                    });
                </script>
        </div>
    </div> -->



        <!-- <h4 class="text-primary fw-500 mt-4">LIST CUSTOMER ULANG TAHUN</h4>
    <div class="col-lg-12 mt-3">
        <div class="row">
            <div class="col-lg-3">
                <h5 class="text-primary fw-500 mt-0">HARI INI</h5>
                <div id="birthday-today"><i class="fa fa-spin fa-refresh"></i> Sedang memuat data!</div>
                <script>
                    $.ajax({
                        dataType: "json",
                        url: '<?= base_url() ?>ajax/get-birthday-list?type=today',
                        success: function(html) {
                            $("#birthday-today").html(html.html);
                        }
                    });
                </script>
            </div>
            <div class="col-lg-3">
                <h5 class="text-primary fw-500 mt-0">BESOK</h5>
                <div id="birthday-1"><i class="fa fa-spin fa-refresh"></i> Sedang memuat data!</div>
                <script>
                    $.ajax({
                        dataType: "json",
                        url: '<?= base_url() ?>ajax/get-birthday-list?type=+1',
                        success: function(html) {
                            $("#birthday-1").html(html.html);
                        }
                    });
                </script>
            </div>
            <div class="col-lg-3">
                <h5 class="text-primary fw-500 mt-0">LUSA</h5>
                <div id="birthday-2"><i class="fa fa-spin fa-refresh"></i> Sedang memuat data!</div>
                <script>
                    $.ajax({
                        dataType: "json",
                        url: '<?= base_url() ?>ajax/get-birthday-list?type=+2',
                        success: function(html) {
                            $("#birthday-2").html(html.html);
                        }
                    });
                </script>
            </div>
            <div class="col-lg-3">
                <h5 class="text-primary fw-500 mt-0">YANG AKAN DATANG</h5>
                <div id="birthday-3"><i class="fa fa-spin fa-refresh"></i> Sedang memuat data!</div>
                <script>
                    $.ajax({
                        dataType: "json",
                        url: '<?= base_url() ?>ajax/get-birthday-list?type=+3',
                        success: function(html) {
                            $("#birthday-3").html(html.html);
                        }
                    });
                </script>
            </div>
        </div>
    </div> -->


    </div>
