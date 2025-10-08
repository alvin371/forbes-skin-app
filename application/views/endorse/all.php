<?php
$chart_title = "";
$site = $_GET['site'];
$customer = $_GET['customer'];

// if ($_GET['start_date'] == "") {
//     $start_date = DATE("Y-m-01");
// } else {
//     $start_date = $_GET['start_date'];
// }
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

// Ambil nilai view saat ini dari URL
$current_view = isset($_GET['view']) ? $_GET['view'] : 'card'; // default ke card jika tidak ada
?>
<div class="w-100">
    <div class="row align-items-center">
        <div class="col-lg-12 mb-3">
            <h3 class="text-primary fw-600">DETAIL CAMPAIGN</h3>
            <h3 class="text-primary fw-600"><?= $detail['title'] ?></h3>
            <p class="mb-0"><?= $detail['desc'] ?></p>
            <p class="mb-0">Status Campaign : <?= $detail['status'] ?></p>
        </div>
        <div class="col-lg-12 mb-3">
            <form action="<?= $url ?>" method="GET">
                <input type="hidden" name="view" value="<?= $current_view ?>">
                <input type="hidden" name="ids" value="<?= $ids ?>">
                <input type="hidden" name="id_campaign" value="<?= $detail['id'] ?>">
                <div class="row">

                    <div class="col-md-12">
                        <?php
                        $arr = array();
                        $arr[] = "Semua Status Endorse";
                        $arr[] = "Review";
                        $arr[] = "Hold";
                        $arr[] = "Acc";
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
                        
                            if ($k == 0 && (!isset($_GET['status_payment']) || $_GET['status_payment'] == "")) {
                                $class_2 = "dot-active";
                                $class = "btn-default-selected";
                            }
                        ?>
                            <a href="<?= $url ?>&status_payment=<?= $status ?>" class="btn <?= $class ?> mb-2 me-2"><span class="<?= $class_2 ?>"></span> <?= $val ?></a>
                        <?php } ?>

                        <div class="col-md-12"></div>
                        <?php
                        $arr = array();

                        $arr[] = "Semua Kategori";
                        $arr[] = "Ada MOU";
                        $arr[] = "Tidak Ada MOU";
                        $arr[] = "FYP";
                        foreach ($arr as $k => $val) {
                            $class = "btn-default";
                            $class_2 = "dot";

                            $value = $val;
                            if ($k == 0) {
                                $value = '';
                            }
                            $value = str_replace('&', '', $value);

                            if ($_GET['status'] == $value) {
                                $class = "btn-default-selected";
                                $class_2 = "dot-active";
                            }
                        ?>
                            <a href="<?= $url_2 ?>&status=<?= $value ?>" class="btn <?= $class ?> mb-2 me-2"><span class="<?= $class_2 ?>"></span> <?= $val ?></a>
                        <?php }  ?>
                    </div>
                    <input type="hidden" name="endorse_status" value="<?= $_GET['endorse_status'] ?>">

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



                    <div class="col-lg-10">
                        <div class="d-flex">
                            <button class="btn btn-outline-secondary-category dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border-top-right-radius: 0px !important;
                            border-bottom-right-radius: 0px !important;min-width:60px!important"><?= $keyword_category ?></button>
                            <ul class="dropdown-menu">
                                <?php
                                $arr = array();
                                $arr[] = 'Nama Creator';
                                $arr[] = 'Link Upload';
                                $arr[] = 'PIC';
                                $arr[] = 'Platform';
                                $arr[] = 'Task';
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
                            <input type="hidden" name="keyword_category" value="<?= $keyword_category ?>">
                            <input type="text" name="keyword" class="form-control me-2" value="<?= $_GET['keyword'] ?>" style="border-top-left-radius: 0px !important;
                            border-bottom-left-radius: 0px !important;width:140px!important">
                            <!-- <a href="#!" onclick="sync_all('<?= $detail['id'] ?>')" class="btn btn-sync mt-0 ms-1"><i class="bi bi-bootstrap-reboot fs-16"></i> Refresh Semua</a> -->

                        </div>
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
                                <div class="col-md-2">
                                    <select class="form-control " name="ads" id="ads">
                                        <option value="">Ads</option>
                                        <?php
                                        $arr = array();
                                        $arr[] = "Iya";
                                        $arr[] = "Tidak";
                                        foreach ($arr as $val) :
                                            $text = "";
                                            if ($_GET["ads"] == $val) {
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
                                    $arr[] = "";
                                    $arr[] = "Tanggal Dibuat";
                                    // $arr[] = "Rencana Upload";
                                    $arr[] = "Tanggal Posting";
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
                            <input type="text" class="form-control form-control-sm" id="tanggal" placeholder="Pilih rentang tanggal...">
                            <input type="hidden" name="start_date" id="start_date" value="<?= $_GET['start_date'] ?? $start_date ?>">
                            <input type="hidden" name="until_date" id="end_date" value="<?= $_GET['until_date'] ?? $until_date ?>">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary btn-sm w-100 form-control form-control-sm" type="submit">
                                <i class="bi bi-search fs-16"></i> Cari Data
                            </button>
                        </div>
                        <script>

                            get_filter();

                            function get_filter() {
                                $.ajax({
                                    dataType: "json",
                                    url: '<?= base_url() ?>/ajax/get-filter?page=endorse',
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
    </div>
    <div class="col-lg-12 mb-3">
        <div class="row">
            <?php
            $sum = array();
            $i = 0;
            $sum[$i]['code'] = 'mar-1';
            $sum[$i]['img'] = "bi bi-coin";
            $sum[$i]['color_box'] = "#60BB551A";
            $sum[$i]['color_icon'] = "#60bb55";
            $sum[$i]['title'] = "TOTAL BUDGET";
            $sum[$i]['unit'] = "BCM";
            $i++;
            $sum[$i]['code'] = 'mar-2';
            $sum[$i]['img'] = "bi bi-arrow-up-right";
            $sum[$i]['color_box'] = "#60BB551A";
            $sum[$i]['color_icon'] = "#60bb55";
            $sum[$i]['title'] = "TOTAL COST";
            $sum[$i]['unit'] = "BCM";
            $i++;
            $sum[$i]['code'] = 'mar-3';
            $sum[$i]['img'] = "bi bi-cursor";
            $sum[$i]['color_box'] = "#60BB551A";
            $sum[$i]['color_icon'] = "#60bb55";
            $sum[$i]['title'] = "CPM";
            $sum[$i]['unit'] = "BCM";
            $i++;
            $sum[$i]['code'] = 'mar-4';
            $sum[$i]['img'] = "bi bi-people";
            $sum[$i]['color_box'] = "#60BB551A";
            $sum[$i]['color_icon'] = "#60bb55";
            $sum[$i]['title'] = "TOTAL INFLUENCER";
            $sum[$i]['unit'] = "BCM";
            $i++;
            $sum[$i]['code'] = 'mar-5';
            $sum[$i]['img'] = "bi bi-person-video2";
            $sum[$i]['color_box'] = "#60BB551A";
            $sum[$i]['color_icon'] = "#60bb55";
            $sum[$i]['title'] = "TOTAL KONTEN";
            $sum[$i]['unit'] = "BCM";
            $i++;
            $sum[$i]['code'] = 'mar-6';
            $sum[$i]['img'] = "bi bi-eye";
            $sum[$i]['color_box'] = "#60BB551A";
            $sum[$i]['color_icon'] = "#60bb55";
            $sum[$i]['title'] = "VIEWS";
            $sum[$i]['unit'] = "BCM";
            $i++;
            $sum[$i]['code'] = 'mar-7';
            $sum[$i]['img'] = "bi bi-heart";
            $sum[$i]['color_box'] = "#60BB551A";
            $sum[$i]['color_icon'] = "#60bb55";
            $sum[$i]['title'] = "ENGAGEMENT";
            $sum[$i]['unit'] = "BCM";
            $i++;
            ?>
            <?php foreach ($sum as $k => $v) { ?>
                <div class="text-start mb-4 col-md-3">
                    <?php if ($v['code'] == "mar-4") { ?>
                        <a href="<?= base_url() ?>endorse/stats<?= $param ?>" class="text-primary">
                            <div class="card">
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
                        </a>
                    <?php } else { ?>
                        <div class="card">
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
                    <?php } ?>
                </div>

                <?php if (in_array($v['code'], array('mar-1'))) { ?>
                    <script>
                        $.ajax({
                            dataType: "json",
                            url: '<?= base_url() ?>ajax/get-summary-campaign<?= $param ?>&id=<?= $v['code'] ?>&id_campaign=<?= $detail['id'] ?>&cat=<?= $_GET['cat'] ?>&endorse_status=<?= $_GET['endorse_status'] ?>&ids=<?= $ids ?>',
                            success: function(html) {
                                $("#summary-<?= $v['code'] ?>").html(html.html);
                            }
                        });
                    </script>
                <?php } ?>


            <?php } ?>
        </div>
    </div>
    <div class="col-lg-12 mb-3">
        <div class="card summary">
            <h3 class="text-primary fw-600 mb-1">Grafik Campaign</h3>
            
            <!-- Filter Tanggal untuk Grafik -->
            <div class="row my-2">
                <div class="col-md-2">
                    <input type="text" class="form-control" style="height: 30px !important;" id="chart_tanggal" placeholder="Pilih rentang tanggal...">
                    <input type="hidden" id="chart_start_date" value="<?= $_GET['start_date'] ?? $start_date ?>">
                    <input type="hidden" id="chart_until_date" value="<?= $_GET['until_date'] ?? $until_date ?>">
                </div>
                <div class="col-md-1">
                    <button class="btn btn-primary" style="height: 30px !important; padding: 0px 0px !important; margin-left: -16px !important;" onclick="applyChartFilter()">
                        <i class="bi bi-search fs-16"></i>
                    </button>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="d-grid d-md-flex d-lg-flex">
                        <?php
                        $arr = ["Daily","Views","CPM","Engagement","Cost","Jumlah Konten"];

                        foreach ($arr as $k => $v) {
                            $text = ($k === 0 || $k === 1) ? "checked" : "";
                            ?>
                            <input onclick="checkbox(<?= $k ?>)" <?= $text ?> type="checkbox" id="c-<?= $k ?>" class="me-2 c-checkbox">
                            <label for="c-<?= $k ?>" class="fw-400 me-2"><?= $v ?></label>
                        <?php } ?>

                    </div>
                </div>
            </div>
            <div id="summary-chart"><i class="fa fa-circle-o-notch fa-spin"></i> Memuat data ...</div>
            <div id="summary-table"><i class="fa fa-circle-o-notch fa-spin"></i> Memuat data ...</div>

            <script>
                function setUrlParams(params) {
                    const url = new URL(window.location);
                    Object.keys(params).forEach(k => {
                    if (params[k] === null || params[k] === undefined || params[k] === '') {
                        url.searchParams.delete(k);
                    } else {
                        url.searchParams.set(k, params[k]);
                    }
                    });
                    history.pushState({}, '', url);
                }

                function getUrlParam(name) {
                    return new URL(window.location).searchParams.get(name);
                }
            </script>

            <script>
                $(document).ready(function() {
                    // 1) Ambil dari URL (pakai key khusus chart agar tidak bentrok dengan filter page lain)
                    let urlStart = getUrlParam('chart_start_date');
                    let urlEnd   = getUrlParam('chart_until_date');

                    // 3) Fallback ke nilai yang sudah kamu siapkan (GET/ default)
                    let phpStart = $('#chart_start_date').val();
                    let phpEnd   = $('#chart_until_date').val(); 

                    const useStart = urlStart || phpStart || moment().subtract(30, 'days').format('YYYY-MM-DD');
                    const useEnd   = urlEnd   || phpEnd   || moment().format('YYYY-MM-DD');

                    // Set hidden inputs
                    $('#chart_start_date').val(useStart);
                    $('#chart_until_date').val(useEnd);

                    // Inisialisasi daterangepicker sesuai nilai di atas
                    const startDateMoment = moment(useStart, 'YYYY-MM-DD');
                    const endDateMoment   = moment(useEnd, 'YYYY-MM-DD');

                    $('#chart_tanggal').daterangepicker({
                        startDate: startDateMoment,
                        endDate: endDateMoment,
                        locale: {
                        format: 'DD/MM/YYYY',
                        separator: " - ",
                        applyLabel: "Pilih",
                        cancelLabel: "Batal",
                        fromLabel: "Dari",
                        toLabel: "Sampai",
                        customRangeLabel: "Custom",
                        daysOfWeek: ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"],
                        monthNames: ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"],
                        firstDay: 1
                        }
                    });

                    // Tampilkan teksnya juga
                    $('#chart_tanggal').val(startDateMoment.format('DD/MM/YYYY') + ' - ' + endDateMoment.format('DD/MM/YYYY'));

                    initializeDefaultCheckboxes();
                    get_chart();
                });


                function initializeDefaultCheckboxes() {
                    var checkboxes = document.querySelectorAll('.c-checkbox');
                    var hasAnyChecked = Array.from(checkboxes).some(cb => cb.checked);
                    
                    if (!hasAnyChecked) {
                        document.getElementById('c-0').checked = true; // Daily
                        document.getElementById('c-1').checked = true; // Views
                        
                        updateCheckboxSession();
                    }
                }

                function updateCheckboxSession() {
                    var checkboxStatus = {};
                    var i = 0;
                    $(".c-checkbox").each(function() {
                        var isChecked = $(this).prop("checked") ? 'true' : 'false';
                        checkboxStatus[i] = isChecked;
                        i++;
                    });

                    checkboxStatus['type'] = 'dashboard_campaign';

                    var queryParams = $.param(checkboxStatus);
                    $.ajax({
                        type: "GET",
                        dataType: "json",
                        url: '<?= base_url() ?>/ajax/checkbox?' + queryParams,
                        success: function(response) {
                           
                        }
                    });
                }
                
                function applyChartFilter() {
                    var dateRange = $('#chart_tanggal').val();
                    var dates = dateRange.split(' - ');
                    
                    if (dates.length === 2) {
                        var startDateParts = dates[0].split('/');
                        var endDateParts = dates[1].split('/');

                        var startDateFormatted = startDateParts[2] + '-' + startDateParts[1] + '-' + startDateParts[0];
                        var endDateFormatted   = endDateParts[2]   + '-' + endDateParts[1]   + '-' + endDateParts[0];

                        // Set ke hidden input
                        $('#chart_start_date').val(startDateFormatted);
                        $('#chart_until_date').val(endDateFormatted);

                        // SIMPAN ke localStorage
                        localStorage.setItem('chart_start_date', startDateFormatted);
                        localStorage.setItem('chart_until_date', endDateFormatted);

                        // SIMPAN ke URL (pakai key khusus chart biar tidak mempengaruhi filter lain)
                        setUrlParams({
                        chart_start_date: startDateFormatted,
                        chart_until_date: endDateFormatted
                        });
                    }

                    get_chart();
                    }


                function get_chart() {
                    var chartStartDate = $('#chart_start_date').val();
                    var chartUntilDate = $('#chart_until_date').val();

                    if (!isValidDate(chartStartDate) || !isValidDate(chartUntilDate)) {
                        console.error('Invalid date detected, using default dates');
                        chartStartDate = '<?= $start_date ?>';
                        chartUntilDate = '<?= $until_date ?>';
                    }

                    // Pastikan URL selalu memuat tanggal chart terkini:
                    setUrlParams({
                        chart_start_date: chartStartDate,
                        chart_until_date: chartUntilDate
                    });

                    var baseUrl = '<?= base_url() ?>/ajax/get-chart-campaign';

                    var urlParams = new URLSearchParams(window.location.search);
                    var params = {};
                    for (let [key, value] of urlParams) params[key] = value;

                    // Pakai tanggal dari hidden (prioritas chart)
                    params['start_date'] = chartStartDate;
                    params['until_date'] = chartUntilDate;

                    var queryString = Object.keys(params).map(function(key) {
                        return encodeURIComponent(key) + '=' + encodeURIComponent(params[key]);
                    }).join('&');

                    var url = baseUrl + (queryString ? '?' + queryString : '');

                    $.ajax({
                        dataType: "json",
                        url: url,
                        success: function(html) {
                        $("#summary-chart").html(html.html);
                        $("#summary-table").html(html.table);
                        $("#summary-mar-3").html('<i class="fa fa-circle-o-notch fa-spin"></i>');
                        $("#summary-mar-3").html(html.summary.cpm);
                        $("#summary-mar-6").html('<i class="fa fa-circle-o-notch fa-spin"></i>');
                        $("#summary-mar-6").html(html.summary.views);
                        $("#summary-mar-7").html('<i class="fa fa-circle-o-notch fa-spin"></i>');
                        $("#summary-mar-7").html(html.summary.engagement);
                        $("#summary-mar-2").html('<i class="fa fa-circle-o-notch fa-sin"></i>');
                        $("#summary-mar-2").html(html.summary.cost);
                        $("#summary-mar-4").html('<i class="fa fa-circle-o-notch fa-spin"></i>');
                        $("#summary-mar-4").html(html.summary.influencer);
                        $("#summary-mar-5").html('<i class="fa fa-circle-o-notch fa-spin"></i>');
                        $("#summary-mar-5").html(html.summary.endorse);
                        },
                        error: function(xhr, status, error) {
                        console.error('Error loading chart:', error);
                        $("#summary-chart").html('<div class="alert alert-danger">Error loading chart data. Please try again.</div>');
                        $("#summary-table").html('');
                        }
                    });
                    }


                function isValidDate(dateString) {
                    if(!/^\d{4}-\d{2}-\d{2}$/.test(dateString)) return false;
                    
                    var parts = dateString.split("-");
                    var year = parseInt(parts[0], 10);
                    var month = parseInt(parts[1], 10);
                    var day = parseInt(parts[2], 10);
                    
                    if(year < 1000 || year > 3000 || month == 0 || month > 12) return false;
                    
                    var monthLength = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
                    
                    if(year % 400 == 0 || (year % 100 != 0 && year % 4 == 0))
                        monthLength[1] = 29;
                    
                    return day > 0 && day <= monthLength[month - 1];
                }
                const CHECKBOX_TYPE = 'null'; 

                function pushCheckboxState() {
                    var checkboxStatus = {};
                    var i = 0;
                    $(".c-checkbox").each(function() {
                    checkboxStatus[i] = $(this).prop("checked");
                    i++;
                    });
                    if (CHECKBOX_TYPE) checkboxStatus['type'] = CHECKBOX_TYPE;

                    $.ajax({
                    type: "GET",
                    dataType: "json",
                    url: '<?= base_url() ?>/ajax/checkbox?' + $.param(checkboxStatus),
                    success: function() {
                        get_chart();
                    }
                    });
                }

                function checkbox(index) {
                    pushCheckboxState();
                }

                $(function() {
                    $("#c-0").prop("checked", true);
                    $("#c-1").prop("checked", true);

                    $(".c-checkbox").each(function(idx) {
                    if (idx !== 0 && idx !== 1) $(this).prop("checked", false);
                    });

                    pushCheckboxState();
                });
            </script>
        </div>
    </div>
    <a href="#!" onclick="create('<?= $detail['id'] ?>')" class="btn btn-primary mt-0 mb-2"><i class="bi bi-plus-circle-dotted fs-16"></i> Tambah Konten</a>

    <tr>
        <td>
            <div class="d-flex justify-content-between align-items-center w-100">
                <span><?= $notif ?></span>
                <div class="dropdown">
                    <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownView" data-bs-toggle="dropdown" aria-expanded="false">
                        Pilih Tampilan
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownView">
                        <?php
                        $current_params = $_GET;
                        
                        $current_params['view'] = 'card';
                        $card_url = 'endorse?' . http_build_query($current_params);
                        
                        $current_params['view'] = 'table';
                        $table_url = 'endorse?' . http_build_query($current_params);
                        ?>
                        <li><a class="dropdown-item" href="<?= $card_url ?>">Tampilan Kartu</a></li>
                        <li><a class="dropdown-item" href="<?= $table_url ?>">Tampilan List</a></li>
                    </ul>
                </div>
            </div>
        </td>
    </tr>



    <div class="col-lg-12 mb-3">
        <div class="checkbox-wrapper-13">
            <input id="c1-13" type="checkbox" value="1" class="checkAll">
            <label for="c1-13">Pilih Semua Data</label>
        </div>
    </div>
    

    <div class="col-lg-12">
        <div class="col-lg-12">
            <div id="tbody">
                <?php $this->load->view('loading', true) ?>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <div>
                <?= $pagination ?>
            </div>
            <div>
                <?php
                $per_page_options = [10, 20, 50, 100, 500];
                $limit = $_GET['limit'] ?? 10;
                if (!in_array($limit, $per_page_options)) {
                    $limit = 10;
                }

                $query_params = $_GET;
                unset($query_params['limit']);
                ?>

                <form method="GET" action="">
                    <?php foreach ($query_params as $key => $value): ?>
                        <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
                    <?php endforeach; ?>

                    <select class="form-control select2" name="limit" id="limit"
                        onchange="this.form.submit()">
                        <?php foreach ($per_page_options as $option): ?>
                            <option value="<?= $option ?>" <?= ($limit == $option) ? 'selected' : '' ?>>
                                <?= $option ?> / Halaman
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>

            </div>
        </div>
    </div>



    <div class="floating-div">
        <button class="btn mb-2 btn-edit-active dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-gear fs-16"></i> Aksi
        </button>
        <ul class="dropdown-menu text-end" style="padding:0px;background:unset;border:unset">


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

            <li><a class="dropdown-items" href="#!" style="padding:0px;">
                    <button type="button" class="btn mb-2 btn-edit-active" onclick="ubah_status()">
                        <i class="bi bi-cursor fs-16"></i> Ubah Status Konten
                    </button>
                </a></li>

            <li><a class="dropdown-items" href="#!" style="padding:0px;">
                    <button type="button" class="btn mb-2 btn-edit-active" onclick="ubah_status_data()">
                        <i class="bi bi-cursor fs-16"></i> Ubah Status Data
                    </button>
                </a></li>

            <li><a class="dropdown-items" href="#!" style="padding:0px;">
                    <button type="button" class="btn mb-2 btn-edit-active" onclick="ubah_status_payment()">
                        <i class="bi bi-cursor fs-16"></i> Ajukan Full Payment
                    </button>
                </a></li>

            <li><a class="dropdown-items" href="#!" style="padding:0px">
                    <button type="button" class="btn mb-2 btn-edit-active" onclick="hapus_data()">
                        <i class="bi bi-trash fs-16"></i> Hapus Data
                    </button>
                </a></li>
        </ul>
    </div>


    <div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="modal-form">
        <div class="modal-dialog" id="modal-dialog">
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

<script src="https://unpkg.com/@popperjs/core@2"></script>
<script src="https://unpkg.com/tippy.js@6"></script>
<script>
    var list_id_v2 = '';

    function showModal(title, url, isLarge = false) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html(title);

        if (isLarge) {
            $("#modal-form .modal-dialog").addClass("modal-lg");
        } else {
            $("#modal-form .modal-dialog").removeClass("modal-lg");
        }

        $("#load-form").load(url);
    }

    function create(id) {
        showModal('Tambah Konten', `<?= base_url() ?>/endorse/create?id=${id}`, true);
    }

    function edit(id) {
        showModal('Edit Konten', `<?= base_url() ?>/endorse/edit?id=${id}`, true);
    }

    function hapus_data(id) {
        showModal('Hapus Data', `<?= base_url() ?>/endorse/action?code=hapus_data&id=${id}`);
    }

    function ubah_status(id) {
        showModal('Ubah Status Konten', `<?= base_url() ?>/endorse/action?code=ubah_status&id=${id}`);
    }

    function ubah_status_payment(id) {
        showModal('Ubah Status Payment', `<?= base_url() ?>/endorse/action?code=ubah_status_payment&id=${id}`);
    }

    function set_payment(id) {
        showModal('Ajukan Payment', `<?= base_url() ?>/endorse/ajukan_payment?id=${id}`);
    }

    function generate_mou(id) {
        showModal('', `<?= base_url() ?>/endorse/generate_mou?id=${id}`, true); 
        $("#title-form").html('');
    }


    function set_batalkan_payment(id) {
        showModal('Batalkan Payment', `<?= base_url() ?>/endorse/batal_ajukan_payment?id=${id}`);
    }

    function ubah_status_data(id) {
        showModal('Ubah Status Data', `<?= base_url() ?>/endorse/action?code=ubah_status_data&id=${id}`);
    }

    function refresh_data(id) {
        showModal('Refresh Data', `<?= base_url() ?>/endorse/action?code=refresh_data&id_campaign=<?= $detail['id'] ?>`);
    }

    function remove(id) {
        showModal('Hapus Data', `<?= base_url() ?>/endorse/remove?id=${id}`);
    }

    function sync_all(id) {
        showModal('Refresh Data', `<?= base_url() ?>/endorse/sync_all?id=${id}`);
    }

    function sync(id) {
        showModal('Refresh Data', `<?= base_url() ?>/endorse/sync?id=${id}`);
    }

    function clone(id) {
        showModal('Kloning Data', `<?= base_url() ?>/endorse/clone?id=${id}`);
    }

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

    function tampilkan_data() {
        window.location.href = `<?= base_url() ?>/endorse?id_campaign=<?= $detail['id'] ?>&ids=${list_id_v2}`;
    }

    function show_chart(id) {
        $('#chart-' + id).html('<i class="fa fa-circle-o-notch fa-spin"></i> Memuat data ...');
        $.ajax({
            dataType: "json",
            url: `<?= base_url() ?>/ajax/get-chart-endorse<?= $param ?>&id=${id}`,
            success: function(response) {
                $(`#chart-${id}`).html(response.html);
                $(`#table-${id}`).html(response.table);
            }
        });
    }
</script>

<script>
    function loadMoreData() {
        const urlParams = new URLSearchParams(window.location.search);
        const sortColumn = urlParams.get('sort_column') || 'id';
        const sortOrder = urlParams.get('sort_order') || 'DESC';
        
        $.ajax({
            type: 'GET',
            url: "<?= base_url() ?>/endorse/item<?= $url_item ?>&sort_column=" + sortColumn + "&sort_order=" + sortOrder,
            success: function(data) {
                $('#tbody').html('').append(data);
                select3();
                initSorting();
                updateSortingIcons(sortColumn, sortOrder);
            },
            error: function(xhr, status, error) {
                console.error("Error loading data:", error);
            }
        });
    }

    function initSorting() {
        $('th.sortable').off('click').on('click', function() {
            const scrollPosition = $(window).scrollTop();
            
            const urlParams = new URLSearchParams(window.location.search);
            const currentSortColumn = urlParams.get('sort_column') || 'id';
            const currentSortOrder = urlParams.get('sort_order') || 'desc';
            
            const columnName = $(this).text().trim().toLowerCase();
            const columnMap = {
                'nama influencer': 'nama_creator',
                'pic': 'pic',
                'total cost': 'total_cost',
                'status': 'status_endorse',
                'tanggal posting': 'posting_at',
                'views': 'views',
                'cpm': 'cpm',
                'engagement': 'engagement'
            };
            
            const clickedColumn = columnMap[columnName] || 'id';
            
            let newSortOrder;
            if (clickedColumn === currentSortColumn) {
                newSortOrder = currentSortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                newSortOrder = 'desc';
            }
            
            loadMoreDataWithSort(clickedColumn, newSortOrder, scrollPosition);
        });
    }

    function loadMoreDataWithSort(sortColumn, sortOrder, scrollPosition) {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('sort_column', sortColumn);
        urlParams.set('sort_order', sortOrder);
        
        history.pushState(null, '', '?' + urlParams.toString());
        
        $('#tbody').html('<tr><td colspan="13" class="text-center"><div class="spinner-border" role="status"></div></td></tr>');
        
        $.ajax({
            type: 'GET',
            url: "<?= base_url() ?>/endorse/item?" + urlParams.toString(),
            success: function(data) {
                $('#tbody').html(data);
                select3();
                initSorting(); 
                updateSortingIcons(sortColumn, sortOrder);
                
                $(window).scrollTop(scrollPosition);
            },
            error: function(xhr, status, error) {
                console.error("Error loading data:", error);
            }
        });
    }

    function updateSortingIcons(sortColumn, sortOrder) {
        $('th.sortable i').removeClass('bi-arrow-up bi-arrow-down').addClass('bi-arrow-down-up');
        
        const columnMap = {
            'nama_creator': 'Nama Influencer',
            'pic': 'PIC',
            'total_cost': 'Total Cost',
            'status_endorse': 'Status',
            'posting_at': 'Tanggal Posting', 
            'views': 'Views',
            'cpm': 'CPM',
            'likes': 'Engagement'
        };
        
        const columnName = columnMap[sortColumn];
        if (columnName) {
            $('th.sortable').each(function() {
                if ($(this).text().trim() === columnName) {
                    $(this).find('i')
                        .removeClass('bi-arrow-down-up')
                        .addClass(sortOrder === 'asc' ? 'bi-arrow-up' : 'bi-arrow-down');
                }
            });
        }
    }

    function updateRowNumbers(table) {
        table.find('tr:gt(0)').each(function(index) {
            $(this).find('td:first').text(index + 1);
        });
    }

    function comparer(index) {
        return function(a, b) {
            let valA = $(a).children('td').eq(index).text().trim();
            let valB = $(b).children('td').eq(index).text().trim();

            if (!valA && !valB) return 0;
            if (!valA) return 1;
            if (!valB) return -1;

            if (index === 3 || index === 6 || index === 7 || index === 8 || index === 9 || index === 10 || index === 11) {
                valA = valA.replace(/\./g, '');
                valB = valB.replace(/\./g, '');
            }

            const numA = parseFloat(valA.replace(/[^\d.-]/g, ''));
            const numB = parseFloat(valB.replace(/[^\d.-]/g, ''));

            if (!isNaN(numA) && !isNaN(numB)) {
                return numA - numB;
            }

            return valA.localeCompare(valB);
        };
    }

    function getCellValue(row, index) {
        return $(row).children('td').eq(index).text() || $(row).children('td').eq(index).find('span').text();
    }

    $(document).ready(function() {
        loadMoreData();
    });

    
    
</script>