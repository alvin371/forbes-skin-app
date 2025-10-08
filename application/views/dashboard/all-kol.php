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

    /* Progressive Loading Animations */
    @keyframes loading {
        0% {
            background-position: 200% 0;
        }
        100% {
            background-position: -200% 0;
        }
    }

    .skeleton-placeholder {
        border-radius: 4px;
        animation: loading 1.5s ease-in-out infinite;
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
    }

    .batch-loading-indicator {
        position: fixed;
        top: 20px;
        right: 20px;
        background: #1255cc;
        color: white;
        padding: 10px 15px;
        border-radius: 5px;
        z-index: 9999;
        font-size: 14px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .batch-loading-indicator.success {
        background: #28a745;
    }

    .batch-loading-indicator.error {
        background: #dc3545;
    }

    /* KOL Dashboard sections styling */
    .kol-section {
        margin-bottom: 2rem;
    }

    .campaign-analytics-section {
        margin-top: 3rem;
    }

    /* Progressive loading animations for KOL metrics */
    .metric-loaded {
        animation: metricFadeIn 0.5s ease-in-out;
    }

    @keyframes metricFadeIn {
        from {
            opacity: 0.3;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .loading-metric {
        opacity: 0.6;
        transition: opacity 0.3s ease;
    }

    .loaded-metric {
        opacity: 1;
        transition: opacity 0.3s ease;
    }

    /* KOL specific card styling */
    .kol-metric-card {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }

    .kol-metric-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transform: translateY(-2px);
    }

    /* Responsive adjustments */
    @media (max-width: 576px) {
        .batch-loading-indicator {
            top: 10px;
            right: 10px;
            font-size: 12px;
            padding: 8px 12px;
        }
        
        .kol-section {
            margin-bottom: 1.5rem;
        }
        
        .campaign-analytics-section {
            margin-top: 2rem;
        }
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
        <div class="col-lg-12 mb-1">
            <div class="row">
                <div class="col-md-12">
                    <h3 class="text-primary fw-500">DASHBOARD KOL BHSKIN</h3>
                </div>
            </div>

            <?php $this->load->view('dashboard/menu') ?>
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
                        <select class="form-control select2" name="ids_campaign[]" id="campaign" multiple="multiple">
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
                                <div class="col-md-3">
                                    <?php
                                    $arr = [];
                                    $arr[] = "Daily";
                                    //   $arr[] = "Weekly";
                                    //   $arr[] = "Monthly";
                                    //   $arr[] = "Yearly";
                                    ?>
                                    <select class="form-control " name="type" id="type">
                                        <?php foreach ($arr as $k => $v) {
                                            $text = "";
                                            if ($type == $v) {
                                                $text = "selected";
                                            }
                                        ?>
                                            <option <?= $text ?> value="<?= $v ?>"><?= $v ?></option>
                                        <?php
                                        } ?>
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

        <!-- INFLUENCER KOL MARKETING Section -->
        <div class="kol-section">
            <h4 class="text-primary fw-500 mt-4">INFLUENCER KOL MARKETING</h4>
        
        <!-- Loading Skeleton -->
        <div class="kol-metrics-loading-skeleton" style="display: none;">
            <div class="row">
                <div class="col-md-3 col-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="skeleton-placeholder" style="height: 20px; margin-bottom: 10px;"></div>
                            <div class="skeleton-placeholder" style="height: 30px; margin-bottom: 10px;"></div>
                            <div class="skeleton-placeholder" style="height: 15px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="skeleton-placeholder" style="height: 20px; margin-bottom: 10px;"></div>
                            <div class="skeleton-placeholder" style="height: 30px; margin-bottom: 10px;"></div>
                            <div class="skeleton-placeholder" style="height: 15px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="skeleton-placeholder" style="height: 20px; margin-bottom: 10px;"></div>
                            <div class="skeleton-placeholder" style="height: 30px; margin-bottom: 10px;"></div>
                            <div class="skeleton-placeholder" style="height: 15px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="skeleton-placeholder" style="height: 20px; margin-bottom: 10px;"></div>
                            <div class="skeleton-placeholder" style="height: 30px; margin-bottom: 10px;"></div>
                            <div class="skeleton-placeholder" style="height: 15px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- KOL Metrics Content -->
        <div class="col-lg-12 mt-3" id="kol-metrics-content">
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
                            <div class="text-start mb-4 col-md-3 col-6 loading-metric" data-metric="<?= $v['code'] ?>">
                                <div class="kol-metric-card card h-100">
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
                                                    <h4 class="fw-500 mb-1 text-end" id="summary-<?= $v['code'] ?>">
                                                        <div class="d-flex align-items-center justify-content-end">
                                                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status" style="width: 1.2rem; height: 1.2rem;">
                                                                <span class="visually-hidden">Loading...</span>
                                                            </div>
                                                            <span class="text-muted">Loading...</span>
                                                        </div>
                                                    </h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <script>
                                // Register metric for progressive loading
                                window.kolMetrics = window.kolMetrics || [];
                                window.kolMetrics.push('<?= $v['code'] ?>');
                            </script>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- CAMPAIGN ANALYTICS Section -->
        <div class="campaign-analytics-section">
            <div class="col-lg-12 mb-3">
            <!-- Loading Skeleton -->
            <div class="campaign-analytics-loading-skeleton" style="display: none;">
                <div class="card h-100 summary mb-2">
                    <div class="card-body">
                        <div class="skeleton-placeholder" style="height: 25px; margin-bottom: 15px;"></div>
                        <div class="skeleton-placeholder" style="height: 300px;"></div>
                    </div>
                </div>
            </div>

            <!-- Campaign Analytics Content -->
            <div class="campaign-analytics-content">
                <div class="card summary">
                    <h4 class="text-primary fw-500 mb-1">GRAFIK CAMPAIGN</h4>
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
                    <div id="summary-chart">
                        <div class="d-flex align-items-center justify-content-center p-3">
                            <div class="spinner-border text-primary me-3" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <span class="text-muted">Memuat data...</span>
                        </div>
                    </div>
                    <div id="summary-table">
                        <div class="d-flex align-items-center justify-content-center p-2">
                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <span class="text-muted small">Memuat tabel...</span>
                        </div>
                    </div>
                </div>
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

<script>
// Global AJAX Request Manager for cleanup
window.AjaxRequestManager = window.AjaxRequestManager || {
    activeRequests: [],

    add: function(xhr) {
        if (xhr && this.activeRequests) {
            this.activeRequests.push(xhr);
        }
    },

    remove: function(xhr) {
        if (xhr && this.activeRequests) {
            var index = this.activeRequests.indexOf(xhr);
            if (index > -1) {
                this.activeRequests.splice(index, 1);
            }
        }
    },

    abortAll: function() {
        if (!this.activeRequests) {
            this.activeRequests = [];
            return;
        }

        var requestsToAbort = this.activeRequests.slice();
        this.activeRequests = [];

        requestsToAbort.forEach(function(xhr) {
            try {
                if (xhr && xhr.readyState !== 4) {
                    xhr.abort();
                }
            } catch (e) {
                console.warn('Error aborting request:', e);
            }
        });
    },

    getActiveCount: function() {
        return this.activeRequests ? this.activeRequests.length : 0;
    }
};

// Intercept jQuery AJAX to track requests
if (window.jQuery) {
    $(document).ajaxSend(function(event, xhr, options) {
        if (window.AjaxRequestManager && typeof window.AjaxRequestManager.add === 'function') {
            window.AjaxRequestManager.add(xhr);
        }
    });

    $(document).ajaxComplete(function(event, xhr, options) {
        if (window.AjaxRequestManager && typeof window.AjaxRequestManager.remove === 'function') {
            window.AjaxRequestManager.remove(xhr);
        }
    });

    $(document).ajaxError(function(event, xhr, options) {
        if (window.AjaxRequestManager && typeof window.AjaxRequestManager.remove === 'function') {
            window.AjaxRequestManager.remove(xhr);
        }
    });
}

// KOL Dashboard Management System (No Tabs)
const kolDashboardManager = {
    isLoaded: false,

    init: function() {
        // Load both KOL metrics and campaign analytics immediately
        this.loadKolContent();
        this.loadCampaignContent();
    },

    loadKolContent: function() {
        // Show KOL content immediately
        $('.kol-metrics-loading-skeleton').hide();
        $('#kol-metrics-content').show();

        // Execute progressive loading for KOL metrics
        if (window.kolMetrics && window.kolMetrics.length > 0) {
            this.executeProgressiveLoad(window.kolMetrics);
        }
    },

    loadCampaignContent: function() {
        // Show campaign content immediately and trigger chart loading
        $('.campaign-analytics-loading-skeleton').hide();
        $('.campaign-analytics-content').show();

        // Trigger chart loading with slight delay for better UX
        setTimeout(() => {
            if (typeof get_chart === 'function') {
                get_chart();
            }
        }, 500);
    },

    executeProgressiveLoad: function(metricIds) {
        const self = this;
        let completedCount = 0;
        const totalCount = metricIds.length;

        // Show progress indicator
        const loadingIndicator = $('<div class="batch-loading-indicator"><i class="fa fa-spinner fa-spin"></i> Loading KOL metrics: 0/' + totalCount + '</div>');
        $('body').append(loadingIndicator);

        // Try batch loading first for better performance
        if (metricIds.length > 3) {
            this.tryBatchLoading(metricIds, loadingIndicator, function(success) {
                if (!success) {
                    // Fallback to individual loading
                    self.loadIndividualMetricsWithStagger(metricIds, loadingIndicator);
                }
            });
        } else {
            // Use individual loading for small sets
            this.loadIndividualMetricsWithStagger(metricIds, loadingIndicator);
        }
    },

    tryBatchLoading: function(metricIds, loadingIndicator, fallbackCallback) {
        const self = this;
        const metricIdString = metricIds.join(',');

        loadingIndicator.html('<i class="fa fa-spinner fa-spin"></i> Loading KOL metrics batch...');

        $.ajax({
            dataType: "json",
            url: '<?= base_url() ?>ajax/get_kol_metrics_batch',
            data: {
                metric_ids: metricIdString,
                site: '<?= $site ?>',
                brand: '<?= $_GET['brand'] ?>',
                type: '<?= $type ?>',
                start_date: '<?= $start_date ?>',
                until_date: '<?= $until_date ?>',
                start_year: '<?= $start_year ?>',
                until_year: '<?= $until_year ?>',
                start_month: '<?= $start_month ?>',
                until_month: '<?= $until_month ?>',
                start_week: '<?= $start_week ?>',
                until_week: '<?= $until_week ?>'
            },
            success: function(response) {
                if (response.success && response.data) {
                    // Update each metric's display
                    Object.keys(response.data).forEach(function(metricId) {
                        const metricData = response.data[metricId];
                        if (metricData.html !== undefined) {
                            $("#summary-" + metricId).html(metricData.html);
                        }

                        // Add smooth fade-in animation
                        const metricCard = $('[data-metric="' + metricId + '"]');
                        metricCard.removeClass('loading-metric').addClass('loaded-metric metric-loaded');
                    });

                    // Show success indicator with timing info
                    const timing = response.cached ? ' (cached)' : ` (${response.execution_time}s)`;
                    loadingIndicator.removeClass('error').addClass('success').html('<i class="fa fa-check"></i> Loaded ' + response.processed_count + ' KOL metrics' + timing);

                    if (response.errors && Object.keys(response.errors).length > 0) {
                        console.warn('Some KOL metrics failed to load:', response.errors);
                    }

                    // Hide indicator after success
                    setTimeout(function() {
                        loadingIndicator.fadeOut(300, function() {
                            $(this).remove();
                        });
                    }, 3000);

                    fallbackCallback(true);
                } else {
                    console.error('KOL Batch request failed:', response);
                    loadingIndicator.addClass('error').html('<i class="fa fa-times"></i> Batch failed, using progressive loading');
                    setTimeout(() => fallbackCallback(false), 1000);
                }
            },
            error: function(xhr, status, error) {
                console.error('KOL Batch AJAX error:', error);
                loadingIndicator.addClass('error').html('<i class="fa fa-times"></i> Network error, using progressive loading');
                setTimeout(() => fallbackCallback(false), 1000);
            }
        });
    },

    loadIndividualMetricsWithStagger: function(metricIds, loadingIndicator) {
        const self = this;
        let completedCount = 0;
        const totalCount = metricIds.length;

        // Load each metric individually and immediately show results
        metricIds.forEach(function(metricId, index) {
            // Add small stagger to prevent overwhelming the server
            setTimeout(() => {
                self.loadIndividualMetricProgressive(metricId, function(success) {
                    completedCount++;

                    // Update progress indicator
                    if (success) {
                        loadingIndicator.html('<i class="fa fa-spinner fa-spin"></i> Loading KOL metrics: ' + completedCount + '/' + totalCount);
                    }

                    // All metrics completed
                    if (completedCount >= totalCount) {
                        loadingIndicator.removeClass('error').addClass('success').html('<i class="fa fa-check"></i> All ' + totalCount + ' KOL metrics loaded!');

                        setTimeout(function() {
                            loadingIndicator.fadeOut(300, function() {
                                $(this).remove();
                            });
                        }, 2000);
                    }
                });
            }, index * 50); // 50ms stagger between requests
        });
    },

    loadIndividualMetricProgressive: function(metricId, callback) {
        const startTime = performance.now();

        $.ajax({
            dataType: "json",
            url: '<?= base_url() ?>ajax/get_summary_v2',
            data: {
                site: '<?= $site ?>',
                id: metricId,
                brand: '<?= $_GET['brand'] ?>',
                type: '<?= $type ?>',
                start_date: '<?= $start_date ?>',
                until_date: '<?= $until_date ?>',
                start_year: '<?= $start_year ?>',
                until_year: '<?= $until_year ?>',
                start_month: '<?= $start_month ?>',
                until_month: '<?= $until_month ?>',
                start_week: '<?= $start_week ?>',
                until_week: '<?= $until_week ?>'
            },
            success: function(html) {
                const loadTime = performance.now() - startTime;

                // Immediately update the display
                if (html.html !== undefined) {
                    $("#summary-" + metricId).html(html.html);
                }

                // Add smooth fade-in animation
                const metricCard = $('[data-metric="' + metricId + '"]');
                metricCard.removeClass('loading-metric').addClass('loaded-metric metric-loaded');

                callback(true);
            },
            error: function(xhr, status, error) {
                console.error('Error loading KOL metric:', metricId, error);
                $("#summary-" + metricId).html('<span class="text-danger small">Error</span>');
                callback(false);
            }
        });
    },

    // Cleanup method for redirects
    cleanup: function() {
        // Clear any running timeouts
        if (this.timeouts) {
            this.timeouts.forEach(function(timeout) {
                clearTimeout(timeout);
            });
            this.timeouts = [];
        }

        // Abort any active AJAX requests
        if (window.AjaxRequestManager) {
            window.AjaxRequestManager.abortAll();
        }
    }
};

// Campaign chart functions (optimized for progressive loading)
function get_chart() {
    $.ajax({
        dataType: "json",
        url: '<?= base_url() ?>ajax/get-chart-campaign<?= $param ?>&is_dashboard=true',
        success: function(html) {
            $("#summary-chart").html(html.html);
            $("#summary-table").html(html.table);

            // Update KOL metrics from chart response
            if (html.summary) {
                const updateMetric = (id, value) => {
                    if (value !== undefined) {
                        $("#summary-" + id).html(value);
                    }
                };

                updateMetric("kol-7", html.summary.share);
                updateMetric("kol-8", html.summary.cpm);
                updateMetric("kol-4", html.summary.view);
                updateMetric("kol-5", html.summary.likes);
                updateMetric("kol-6", html.summary.comment);
                updateMetric("kol-3", html.summary.cost);
                updateMetric("kol-1", html.summary.influencer);
                updateMetric("kol-2", html.summary.endorse);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading campaign chart:', error);
            $("#summary-chart").html('<div class="alert alert-warning">Failed to load chart data</div>');
            $("#summary-table").html('<div class="alert alert-warning">Failed to load table data</div>');
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
    $.ajax({
        type: "GET",
        dataType: "json",
        url: '<?= base_url() ?>ajax/checkbox?type=dashboard_campaign&' + queryParams,
        success: function(response) {
            get_chart();
        },
        error: function(xhr, status, error) {
            console.error('Error updating checkbox preferences:', error);
        }
    });
}

// Expose KOL dashboard manager globally for cleanup
window.kolDashboardManager = kolDashboardManager;

// Initialize KOL dashboard manager when document is ready
$(document).ready(function() {
    kolDashboardManager.init();
});
</script>

    </div>