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
$campaign_type_label = (!empty($detail['is_internal']) && $detail['is_internal'] == 1) ? 'INTERNAL' : 'EXTERNAL';
$campaign_period = '';
if (!empty($detail['start_at']) || !empty($detail['until_at'])) {
    $start = !empty($detail['start_at']) ? DATE('d M Y', strtotime($detail['start_at'])) : '-';
    $until = !empty($detail['until_at']) ? DATE('d M Y', strtotime($detail['until_at'])) : '-';
    $campaign_period = $start . ' - ' . $until;
}
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
                                    $arr[] = "Tanggal Request";
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

                <!-- ===== Filter Optimasi Konten ===== -->
                <div class="row mt-2">
                    <div class="col-md-2">
                        <select class="form-control" name="is_optimization">
                            <?php
                            $opt_flag_arr = array('' => 'Semua Konten', '1' => 'Optimasi', '0' => 'Non-Optimasi');
                            $cur_flag = isset($_GET['is_optimization']) ? $_GET['is_optimization'] : '';
                            foreach ($opt_flag_arr as $val => $label) {
                                $text = ((string) $cur_flag === (string) $val) ? 'selected' : '';
                                echo "<option $text value='$val'>$label</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-control" name="optimization_status">
                            <?php
                            $opt_status_arr = array('' => 'Semua Status Optimasi', 'Not Started' => 'Not Started', 'In Progress' => 'In Progress', 'Completed' => 'Completed');
                            $cur_opt_status = $_GET['optimization_status'] ?? '';
                            foreach ($opt_status_arr as $val => $label) {
                                $text = ($cur_opt_status === $val) ? 'selected' : '';
                                echo "<option $text value='" . htmlspecialchars($val) . "'>$label</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-control" name="media_type">
                            <?php
                            $media_arr = array('' => 'Semua Media', 'photo' => 'Foto', 'video' => 'Video');
                            $cur_media = $_GET['media_type'] ?? '';
                            foreach ($media_arr as $val => $label) {
                                $text = ($cur_media === $val) ? 'selected' : '';
                                echo "<option $text value='$val'>$label</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="request_by" placeholder="Request By" value="<?= htmlspecialchars($_GET['request_by'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <input type="text" class="form-control" name="device" placeholder="Device" value="<?= htmlspecialchars($_GET['device'] ?? '') ?>">
                    </div>
                    <div class="col-md-1 text-end">
                        <a href="#!" onclick="exportOptimization(); return false;" class="btn btn-success btn-sm w-100" title="Export Optimasi (Excel)">
                            <i class="bi bi-file-earmark-excel"></i>
                        </a>
                    </div>
                    <div class="col-md-2 text-end">
                        <a href="#!" id="btn-sync-sheet" onclick="syncOptimizationSheet(); return false;" class="btn btn-primary btn-sm w-100" title="Sync ke Google Sheet">
                            <i class="bi bi-google"></i> Sync ke Sheet
                        </a>
                    </div>
                </div>
                <script>
                    function exportOptimization() {
                        var params = new URLSearchParams(window.location.search);
                        params.set('id_campaign', '<?= $detail['id'] ?>');
                        window.location.href = '<?= base_url() ?>/endorse/export-optimization?' + params.toString();
                    }

                    function syncOptimizationSheet() {
                        var params = new URLSearchParams(window.location.search);
                        params.set('id_campaign', '<?= $detail['id'] ?>');
                        var $btn = $('#btn-sync-sheet');
                        var original = $btn.html();
                        $btn.addClass('disabled').attr('disabled', true).html('<i class="bi bi-arrow-repeat"></i> Sync...');
                        $.ajax({
                            type: 'GET',
                            dataType: 'json',
                            url: '<?= base_url() ?>/endorse/sync-optimization-sheet?' + params.toString(),
                            success: function(res) {
                                alert(res && res.msg ? res.msg : (res && res.status ? 'Sync berhasil.' : 'Sync gagal.'));
                            },
                            error: function(xhr) {
                                alert('Sync gagal: ' + (xhr.responseText || xhr.statusText));
                            },
                            complete: function() {
                                $btn.removeClass('disabled').attr('disabled', false).html(original);
                            }
                        });
                    }
                </script>
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
            <h3 class="text-primary fw-600 mb-1">Filter Daftar Konten</h3>
            <div class="row my-2 g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label mb-1 text-primary fw-600">Periode Filter Konten</label>
                    <input type="text" class="form-control form-control-sm" id="chart_tanggal" placeholder="Pilih rentang tanggal...">
                    <input type="hidden" id="chart_start_date" value="<?= $_GET['start_date'] ?? $start_date ?>">
                    <input type="hidden" id="chart_until_date" value="<?= $_GET['until_date'] ?? $until_date ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1 text-primary fw-600">Top Performer</label>
                    <select class="form-control form-control-sm" id="list_top_performer_period">
                        <option value="">Semua Konten</option>
                        <option value="today" <?= ($_GET['list_top_performer_period'] ?? '') === 'today' ? 'selected' : '' ?>>Today</option>
                        <option value="last_7_days" <?= ($_GET['list_top_performer_period'] ?? '') === 'last_7_days' ? 'selected' : '' ?>>Last 7 Days</option>
                        <option value="last_30_days" <?= ($_GET['list_top_performer_period'] ?? '') === 'last_30_days' ? 'selected' : '' ?>>Last 30 Days</option>
                        <option value="custom" <?= ($_GET['list_top_performer_period'] ?? '') === 'custom' ? 'selected' : '' ?>>Custom</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1 text-primary fw-600">KOL/Influencer</label>
                    <select class="form-control form-control-sm" id="chart_influencer">
                        <option value=""></option>
                        <?php if (!empty($_GET['chart_influencer']) && !empty($_GET['chart_username'])): ?>
                            <option value="<?= htmlspecialchars($_GET['chart_influencer']) ?>" selected><?= htmlspecialchars($_GET['chart_username']) ?></option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1 text-primary fw-600">Filter Views Konten</label>
                    <select class="form-control form-control-sm" id="chart_views_zero">
                        <option value="">Semua Hari</option>
                        <option value="1" <?= ($_GET['list_views_zero'] ?? '') === '1' ? 'selected' : '' ?>>Views = 0</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1 text-primary fw-600">Tanggal Views = 0</label>
                    <input type="text" class="form-control form-control-sm" id="list_views_zero_tanggal" placeholder="Pilih tanggal...">
                    <input type="hidden" id="list_views_zero_date" value="<?= $_GET['list_views_zero_date'] ?? '' ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1 text-primary fw-600">Growth Konten</label>
                    <select class="form-control form-control-sm" id="chart_no_growth">
                        <option value="">Semua Growth</option>
                        <option value="1" <?= ($_GET['list_no_growth'] ?? '') === '1' ? 'selected' : '' ?>>No Growth</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1 text-primary fw-600">Periode No Growth Konten</label>
                    <input type="text" class="form-control form-control-sm" id="chart_no_growth_tanggal" placeholder="Pilih periode no growth...">
                    <input type="hidden" id="chart_no_growth_start_date" value="<?= $_GET['list_growth_start_date'] ?? '' ?>">
                    <input type="hidden" id="chart_no_growth_until_date" value="<?= $_GET['list_growth_until_date'] ?? '' ?>">
                </div>
                <div class="col-md-2">
                    <div class="d-grid gap-1">
                        <button class="btn btn-primary btn-sm" type="button" id="apply_content_filter" onclick="applyChartFilter()">Apply Filter Konten</button>
                        <button class="btn btn-outline-secondary btn-sm" type="button" id="reset_content_filter">Reset Filter Konten</button>
                    </div>
                </div>
            </div>
            <small class="text-muted d-block mb-2">Filter ini hanya untuk daftar konten di bawah teks "data ditemukan!".</small>
        </div>
    </div>
    <div class="col-lg-12 mb-3">
        <div class="card summary">
            <h3 class="text-primary fw-600 mb-1">Grafik Campaign</h3>
            
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
                function buildUrlWithParams(params) {
                    const url = new URL(window.location);
                    Object.keys(params).forEach(k => {
                        if (params[k] === null || params[k] === undefined || params[k] === '') {
                            url.searchParams.delete(k);
                        } else {
                            url.searchParams.set(k, params[k]);
                        }
                    });
                    return url;
                }

                function setUrlParams(params) {
                    const url = buildUrlWithParams(params);
                    history.pushState({}, '', url);
                }

                function getUrlParam(name) {
                    return new URL(window.location).searchParams.get(name);
                }

                function setApplyFilterLoadingState(isLoading) {
                    const $btn = $('#apply_content_filter');
                    if (!$btn.length) return;

                    if (isLoading) {
                        $btn.prop('disabled', true).html('<i class="fa fa-circle-o-notch fa-spin me-1"></i> Applying...');
                    } else {
                        $btn.prop('disabled', false).text('Apply Filter Konten');
                    }
                }

                function getSelectedInfluencerLabel() {
                    const select = document.getElementById('chart_influencer');
                    if (!select) return '';

                    const option = select.options[select.selectedIndex];
                    if (!option) return '';

                    return (option.text || '').trim();
                }
            </script>

            <script>
                function formatChartDateDisplay(dateStr) {
                    return moment(dateStr, 'YYYY-MM-DD').format('DD/MM/YYYY');
                }

                function setMainChartRange(startDate, endDate) {
                    const picker = $('#chart_tanggal').data('daterangepicker');
                    $('#chart_start_date').val(startDate);
                    $('#chart_until_date').val(endDate);
                    if (picker) {
                        picker.setStartDate(moment(startDate, 'YYYY-MM-DD'));
                        picker.setEndDate(moment(endDate, 'YYYY-MM-DD'));
                    }
                    $('#chart_tanggal').val(
                        moment(startDate, 'YYYY-MM-DD').format('DD/MM/YYYY') + ' - ' +
                        moment(endDate, 'YYYY-MM-DD').format('DD/MM/YYYY')
                    );
                }

                function getChartMainRange() {
                    return {
                        start: $('#chart_start_date').val() || moment().subtract(30, 'days').format('YYYY-MM-DD'),
                        end: $('#chart_until_date').val() || moment().format('YYYY-MM-DD')
                    };
                }

                function applyTopPerformerPresetPeriod() {
                    const period = $('#list_top_performer_period').val();
                    const today = moment().format('YYYY-MM-DD');

                    if (period === 'today') {
                        setMainChartRange(today, today);
                    } else if (period === 'last_7_days') {
                        setMainChartRange(moment().subtract(6, 'days').format('YYYY-MM-DD'), today);
                    } else if (period === 'last_30_days') {
                        setMainChartRange(moment().subtract(29, 'days').format('YYYY-MM-DD'), today);
                    }
                }

                function renderChartNoGrowthPeriodText() {
                    const s = $('#chart_no_growth_start_date').val();
                    const e = $('#chart_no_growth_until_date').val();
                    if (s && e) {
                        $('#chart_no_growth_tanggal').val(formatChartDateDisplay(s) + ' - ' + formatChartDateDisplay(e));
                    } else {
                        $('#chart_no_growth_tanggal').val('');
                    }
                }

                function renderViewsZeroDateText() {
                    const d = $('#list_views_zero_date').val();
                    if (d) {
                        $('#list_views_zero_tanggal').val(formatChartDateDisplay(d));
                    } else {
                        $('#list_views_zero_tanggal').val('');
                    }
                }

                function toggleChartNoGrowthPeriodState() {
                    const enabled = $('#chart_no_growth').val() === '1';
                    $('#chart_no_growth_tanggal').prop('disabled', !enabled);
                }

                function toggleViewsZeroDateState() {
                    const enabled = $('#chart_views_zero').val() === '1';
                    $('#list_views_zero_tanggal').prop('disabled', !enabled);
                }

                function applyChartNoGrowthRange(startDate, endDate) {
                    const picker = $('#chart_no_growth_tanggal').data('daterangepicker');
                    $('#chart_no_growth_start_date').val(startDate);
                    $('#chart_no_growth_until_date').val(endDate);
                    if (picker) {
                        picker.setStartDate(moment(startDate, 'YYYY-MM-DD'));
                        picker.setEndDate(moment(endDate, 'YYYY-MM-DD'));
                    }
                    renderChartNoGrowthPeriodText();
                }

                function applyViewsZeroDate(dateValue) {
                    const picker = $('#list_views_zero_tanggal').data('daterangepicker');
                    $('#list_views_zero_date').val(dateValue);
                    if (picker) {
                        picker.setStartDate(moment(dateValue, 'YYYY-MM-DD'));
                        picker.setEndDate(moment(dateValue, 'YYYY-MM-DD'));
                    }
                    renderViewsZeroDateText();
                }

                $(document).ready(function() {
                    $('#chart_influencer').select2({
                        minimumInputLength: 1,
                        allowClear: true,
                        placeholder: 'Cari influencer...',
                        width: '100%',
                        ajax: {
                            dataType: 'json',
                            url: '<?= base_url() ?>/ajax/get-influencer-list',
                            delay: 100,
                            data: function(params) {
                                return {
                                    search: params.term
                                };
                            },
                            processResults: function(data) {
                                return {
                                    results: data
                                };
                            }
                        },
                        language: {
                            inputTooShort: function() {
                                return "Masukkan 1 karakter atau lebih";
                            }
                        }
                    });

                    let urlStart = getUrlParam('chart_start_date');
                    let urlEnd   = getUrlParam('chart_until_date');
                    let phpStart = $('#chart_start_date').val();
                    let phpEnd   = $('#chart_until_date').val();

                    const useStart = urlStart || phpStart || moment().subtract(30, 'days').format('YYYY-MM-DD');
                    const useEnd   = urlEnd   || phpEnd   || moment().format('YYYY-MM-DD');

                    $('#chart_start_date').val(useStart);
                    $('#chart_until_date').val(useEnd);

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
                    setMainChartRange(useStart, useEnd);

                    $('#chart_views_zero').val(getUrlParam('list_views_zero') || $('#chart_views_zero').val() || '');
                    $('#chart_no_growth').val(getUrlParam('list_no_growth') || $('#chart_no_growth').val() || '');
                    const viewsZeroDateFromUrl = getUrlParam('list_views_zero_date') || $('#list_views_zero_date').val() || '';
                    if (viewsZeroDateFromUrl) {
                        $('#list_views_zero_date').val(viewsZeroDateFromUrl);
                    }

                    const noGrowthStartFromUrl = getUrlParam('list_growth_start_date') || $('#chart_no_growth_start_date').val() || '';
                    const noGrowthEndFromUrl = getUrlParam('list_growth_until_date') || $('#chart_no_growth_until_date').val() || '';
                    if (noGrowthStartFromUrl && noGrowthEndFromUrl) {
                        $('#chart_no_growth_start_date').val(noGrowthStartFromUrl);
                        $('#chart_no_growth_until_date').val(noGrowthEndFromUrl);
                    }

                    const chartRange = getChartMainRange();
                    const initialViewsZeroDate = $('#list_views_zero_date').val() || chartRange.end;
                    const initialNoGrowthStart = $('#chart_no_growth_start_date').val() || chartRange.start;
                    const initialNoGrowthEnd = $('#chart_no_growth_until_date').val() || chartRange.end;

                    $('#list_top_performer_period').on('change', function() {
                        const selectedPeriod = $(this).val();
                        if (selectedPeriod && selectedPeriod !== 'custom') {
                            applyTopPerformerPresetPeriod();
                        }
                    });

                    $('#list_views_zero_tanggal').daterangepicker({
                        autoUpdateInput: false,
                        singleDatePicker: true,
                        showDropdowns: true,
                        startDate: moment(initialViewsZeroDate, 'YYYY-MM-DD'),
                        locale: {
                            format: 'DD/MM/YYYY',
                            applyLabel: "Pilih",
                            cancelLabel: "Batal",
                            daysOfWeek: ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"],
                            monthNames: ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"],
                            firstDay: 1
                        }
                    });

                    $('#list_views_zero_tanggal').on('apply.daterangepicker', function(ev, picker) {
                        $('#list_views_zero_date').val(picker.startDate.format('YYYY-MM-DD'));
                        renderViewsZeroDateText();
                    });

                    $('#list_views_zero_tanggal').on('cancel.daterangepicker', function() {
                        $('#list_views_zero_date').val('');
                        renderViewsZeroDateText();
                    });

                    $('#chart_no_growth_tanggal').daterangepicker({
                        autoUpdateInput: false,
                        startDate: moment(initialNoGrowthStart, 'YYYY-MM-DD'),
                        endDate: moment(initialNoGrowthEnd, 'YYYY-MM-DD'),
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

                    $('#chart_no_growth_tanggal').on('apply.daterangepicker', function(ev, picker) {
                        $('#chart_no_growth_start_date').val(picker.startDate.format('YYYY-MM-DD'));
                        $('#chart_no_growth_until_date').val(picker.endDate.format('YYYY-MM-DD'));
                        renderChartNoGrowthPeriodText();
                    });

                    $('#chart_no_growth_tanggal').on('cancel.daterangepicker', function() {
                        $('#chart_no_growth_start_date').val('');
                        $('#chart_no_growth_until_date').val('');
                        renderChartNoGrowthPeriodText();
                    });

                    $('#chart_views_zero').on('change', function() {
                        const enabled = $(this).val() === '1';
                        if (!enabled) {
                            $('#list_views_zero_date').val('');
                        } else if (!$('#list_views_zero_date').val()) {
                            const latestMainRange = getChartMainRange();
                            applyViewsZeroDate(latestMainRange.end);
                        }
                        renderViewsZeroDateText();
                        toggleViewsZeroDateState();
                    });

                    $('#chart_no_growth').on('change', function() {
                        const enabled = $(this).val() === '1';
                        if (!enabled) {
                            $('#chart_no_growth_start_date').val('');
                            $('#chart_no_growth_until_date').val('');
                        } else if (!$('#chart_no_growth_start_date').val() || !$('#chart_no_growth_until_date').val()) {
                            const latestMainRange = getChartMainRange();
                            applyChartNoGrowthRange(latestMainRange.start, latestMainRange.end);
                        }
                        renderChartNoGrowthPeriodText();
                        toggleChartNoGrowthPeriodState();
                    });

                    $('#reset_content_filter').on('click', function() {
                        $('#chart_views_zero').val('');
                        $('#list_views_zero_date').val('');
                        $('#chart_no_growth').val('');
                        $('#chart_no_growth_start_date').val('');
                        $('#chart_no_growth_until_date').val('');
                        renderViewsZeroDateText();
                        renderChartNoGrowthPeriodText();
                        toggleViewsZeroDateState();
                        toggleChartNoGrowthPeriodState();

                        const chartStartDate = $('#chart_start_date').val();
                        const chartUntilDate = $('#chart_until_date').val();
                        const nextUrl = buildUrlWithParams({
                            chart_start_date: chartStartDate,
                            chart_until_date: chartUntilDate,
                            list_top_performer_period: '',
                            chart_influencer: '',
                            chart_username: '',
                            chart_views_zero: '',
                            chart_no_growth: '',
                            chart_no_growth_start_date: '',
                            chart_no_growth_until_date: '',
                            list_views_zero: '',
                            list_views_zero_date: '',
                            list_no_growth: '',
                            list_growth_start_date: '',
                            list_growth_until_date: ''
                        });
                        window.location.href = nextUrl.toString();
                    });

                    if ($('#chart_views_zero').val() === '1' && !$('#list_views_zero_date').val()) {
                        applyViewsZeroDate(chartRange.end);
                    }
                    if ($('#chart_no_growth').val() === '1' && (!$('#chart_no_growth_start_date').val() || !$('#chart_no_growth_until_date').val())) {
                        applyChartNoGrowthRange(chartRange.start, chartRange.end);
                    }
                    if ($('#list_top_performer_period').val() && $('#list_top_performer_period').val() !== 'custom') {
                        applyTopPerformerPresetPeriod();
                    }
                    renderViewsZeroDateText();
                    renderChartNoGrowthPeriodText();
                    toggleViewsZeroDateState();
                    toggleChartNoGrowthPeriodState();

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
                    try {
                        setApplyFilterLoadingState(true);

                        var dateRange = $('#chart_tanggal').val();
                        var dates = dateRange.split(' - ');

                        if (dates.length !== 2) {
                            setApplyFilterLoadingState(false);
                            return;
                        }

                        var startDateParts = dates[0].split('/');
                        var endDateParts = dates[1].split('/');

                        var startDateFormatted = startDateParts[2] + '-' + startDateParts[1] + '-' + startDateParts[0];
                        var endDateFormatted   = endDateParts[2]   + '-' + endDateParts[1]   + '-' + endDateParts[0];

                        setMainChartRange(startDateFormatted, endDateFormatted);

                        localStorage.setItem('chart_start_date', startDateFormatted);
                        localStorage.setItem('chart_until_date', endDateFormatted);

                        var topPerformerPeriod = $('#list_top_performer_period').val() || '';
                        if (topPerformerPeriod && topPerformerPeriod !== 'custom') {
                            applyTopPerformerPresetPeriod();
                            const performerRange = getChartMainRange();
                            startDateFormatted = performerRange.start;
                            endDateFormatted = performerRange.end;
                        }

                        if ($('#chart_no_growth').val() === '1' && (!$('#chart_no_growth_start_date').val() || !$('#chart_no_growth_until_date').val())) {
                            applyChartNoGrowthRange(startDateFormatted, endDateFormatted);
                        }

                        if ($('#chart_views_zero').val() === '1' && !$('#list_views_zero_date').val()) {
                            applyViewsZeroDate(endDateFormatted);
                        }

                        var listViewsZero = $('#chart_views_zero').val() || '';
                        var listViewsZeroDate = $('#list_views_zero_date').val() || '';
                        var listNoGrowth = $('#chart_no_growth').val() || '';
                        var listGrowthStartDate = $('#chart_no_growth_start_date').val() || '';
                        var listGrowthUntilDate = $('#chart_no_growth_until_date').val() || '';

                        if (listViewsZero !== '1') {
                            listViewsZeroDate = '';
                        }
                        if (listNoGrowth !== '1') {
                            listGrowthStartDate = '';
                            listGrowthUntilDate = '';
                        }

                        const nextUrl = buildUrlWithParams({
                            chart_start_date: startDateFormatted,
                            chart_until_date: endDateFormatted,
                            list_top_performer_period: topPerformerPeriod,
                            chart_influencer: $('#chart_influencer').val() || '',
                            chart_username: getSelectedInfluencerLabel(),
                            chart_views_zero: '',
                            chart_no_growth: '',
                            chart_no_growth_start_date: '',
                            chart_no_growth_until_date: '',
                            list_views_zero: listViewsZero,
                            list_views_zero_date: listViewsZeroDate,
                            list_no_growth: listNoGrowth,
                            list_growth_start_date: listGrowthStartDate,
                            list_growth_until_date: listGrowthUntilDate
                        });

                        window.location.assign(nextUrl.toString());
                    } catch (error) {
                        console.error('Failed to apply content filter:', error);
                        setApplyFilterLoadingState(false);
                    }
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
                        chart_until_date: chartUntilDate,
                        chart_views_zero: '',
                        chart_no_growth: '',
                        chart_no_growth_start_date: '',
                        chart_no_growth_until_date: ''
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
    <?php
    $active_content_filters = [];
    if (!empty($_GET['chart_username'])) {
        $active_content_filters[] = 'Influencer: ' . htmlspecialchars($_GET['chart_username']);
    }
    $top_performer_period = $_GET['list_top_performer_period'] ?? '';
    if ($top_performer_period === 'today') {
        $active_content_filters[] = 'Top Performer: Today';
    } else if ($top_performer_period === 'last_7_days') {
        $active_content_filters[] = 'Top Performer: Last 7 Days';
    } else if ($top_performer_period === 'last_30_days') {
        $active_content_filters[] = 'Top Performer: Last 30 Days';
    } else if ($top_performer_period === 'custom') {
        $custom_start = $_GET['chart_start_date'] ?? ($_GET['start_date'] ?? '');
        $custom_until = $_GET['chart_until_date'] ?? ($_GET['until_date'] ?? '');
        if ($custom_start) {
            $custom_start = date('d/m/Y', strtotime($custom_start));
        }
        if ($custom_until) {
            $custom_until = date('d/m/Y', strtotime($custom_until));
        }
        $active_content_filters[] = 'Top Performer: Custom ' . ($custom_start ?: '-') . ' - ' . ($custom_until ?: '-');
    }
    if (($_GET['list_views_zero'] ?? '') === '1') {
        $views_zero_date_text = $_GET['list_views_zero_date'] ?? '';
        if ($views_zero_date_text) {
            $views_zero_date_text = date('d/m/Y', strtotime($views_zero_date_text));
        } else {
            $views_zero_date_text = '-';
        }
        $active_content_filters[] = 'Views = 0 pada ' . $views_zero_date_text;
    }
    if (($_GET['list_no_growth'] ?? '') === '1') {
        $ng_start_text = $_GET['list_growth_start_date'] ?? ($_GET['start_date'] ?? '');
        $ng_end_text = $_GET['list_growth_until_date'] ?? ($_GET['until_date'] ?? '');
        if ($ng_start_text) {
            $ng_start_text = date('d/m/Y', strtotime($ng_start_text));
        }
        if ($ng_end_text) {
            $ng_end_text = date('d/m/Y', strtotime($ng_end_text));
        }
        $active_content_filters[] = 'No Growth periode ' . ($ng_start_text ?: '-') . ' - ' . ($ng_end_text ?: '-');
    }
    ?>

    <div class="col-lg-12 mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 w-100">
            <div>
                <div id="endorse-notif"><?= $notif ?></div>
                <?php if (!empty($active_content_filters)) { ?>
                    <div class="small text-muted mt-1">Filter aktif: <?= implode(' | ', $active_content_filters) ?></div>
                <?php } ?>
            </div>
            <div class="d-flex align-items-center flex-wrap gap-2">
                <button type="button" id="bulk-transfer-trigger" class="btn btn-transfer mt-0" onclick="openBulkTransferModal();">
                    <i class="bi bi-box-arrow-right fs-16"></i> Bulk Transfer
                </button>
                <a href="#!" onclick="sync_all('<?= $detail['id'] ?>')" class="btn btn-sync mt-0">
                    <i class="bi bi-bootstrap-reboot fs-16"></i> Refresh Semua
                </a>
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
        </div>
    </div>



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



    <style>
        .btn-transfer {
            background: #0f172a;
            color: #fff;
            border: none;
        }

        .btn-transfer:hover {
            background: #1e293b;
            color: #fff;
        }

        .transfer-modal .modal-content {
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.15);
        }

        .transfer-modal .modal-header {
            border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
        }

        .transfer-title {
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .transfer-subtitle {
            font-size: 12px;
            color: #64748b;
            margin-top: 2px;
        }

        .transfer-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .transfer-card {
            border: 1px solid #e2e8f0;
            background: #ffffff;
            border-radius: 14px;
            padding: 14px;
        }

        .transfer-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .transfer-card-title {
            font-weight: 600;
            font-size: 14px;
        }

        .transfer-card-meta {
            font-size: 12px;
            color: #64748b;
        }

        .transfer-pill {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 8px;
            background: #f1f5f9;
            color: #0f172a;
            flex-shrink: 0;
            white-space: nowrap;
            border-radius: 20px;
        }

        .transfer-combobox {
            position: relative;
        }

        .transfer-input {
            width: 100%;
            padding: 10px 40px 10px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            background: #fff;
        }

        .transfer-input:focus {
            outline: none;
            border-color: #94a3b8;
            box-shadow: 0 0 0 3px rgba(148, 163, 184, 0.25);
        }

        .transfer-dropdown-btn {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #64748b;
        }

        .transfer-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.18);
            display: none;
            z-index: 1051;
            max-height: 320px;
            overflow: hidden;
        }

        .transfer-combobox.is-open .transfer-dropdown {
            display: block;
        }

        .transfer-tabs {
            display: flex;
            gap: 8px;
            padding: 8px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .transfer-tab {
            flex: 1;
            padding: 8px 10px;
            border: 1px solid #e2e8f0;
            background: #fff;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            color: #334155;
        }

        .transfer-tab.active {
            background: #0f172a;
            color: #fff;
            border-color: #0f172a;
        }

        .transfer-list {
            max-height: 240px;
            overflow: auto;
            padding: 8px;
        }

        .transfer-item {
            width: 100%;
            border: none;
            background: #fff;
            text-align: left;
            padding: 10px 12px;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            transition: all 0.15s ease;
        }

        .transfer-item > div {
            flex: 1;
            min-width: 0;
        }

        .transfer-item:hover {
            background: #f1f5f9;
        }

        .transfer-item-title {
            font-weight: 600;
            font-size: 13px;
            color: #0f172a;
        }

        .transfer-item-meta {
            font-size: 11px;
            color: #64748b;
        }

        .transfer-selected {
            margin-top: 10px;
            padding: 10px 12px;
            border: 1px dashed #cbd5f5;
            border-radius: 10px;
            background: #f8fafc;
            font-size: 12px;
        }

        .transfer-placeholder {
            color: #94a3b8;
        }

        .transfer-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 18px;
        }

        .transfer-message {
            margin-top: 10px;
            font-size: 12px;
            color: #b91c1c;
            display: none;
        }

        .transfer-loading {
            padding: 12px;
            font-size: 12px;
            color: #64748b;
        }

        .transfer-empty {
            padding: 12px;
            font-size: 12px;
            color: #94a3b8;
            text-align: center;
        }

        .bulk-transfer-grid {
            grid-template-columns: minmax(0, 1.2fr) minmax(320px, 0.8fr);
        }

        .transfer-toolbar {
            display: grid;
            grid-template-columns: minmax(0, 1fr) repeat(2, 140px);
            gap: 10px;
            margin-bottom: 12px;
        }

        .transfer-select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 13px;
            background: #fff;
        }

        .transfer-summary-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }

        .transfer-summary-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .transfer-summary-btn {
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #334155;
            border-radius: 999px;
            font-size: 12px;
            padding: 6px 10px;
        }

        .transfer-source-list {
            max-height: 360px;
            overflow: auto;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #fff;
        }

        .transfer-source-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
        }

        .transfer-source-item:last-child {
            border-bottom: none;
        }

        .transfer-source-item.is-selected {
            background: #eff6ff;
        }

        .transfer-source-checkbox {
            margin-top: 2px;
        }

        .transfer-source-body {
            flex: 1;
            min-width: 0;
        }

        .transfer-source-title {
            font-size: 13px;
            font-weight: 600;
            color: #0f172a;
        }

        .transfer-source-meta,
        .transfer-source-desc {
            font-size: 11px;
            color: #64748b;
            word-break: break-word;
        }

        .transfer-source-desc {
            margin-top: 4px;
        }

        .transfer-selected-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-height: 220px;
            overflow: auto;
        }

        .transfer-selected-tag {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 8px;
            padding: 8px 10px;
            border-radius: 10px;
            background: #fff;
            border: 1px solid #dbeafe;
        }

        .transfer-selected-remove {
            border: none;
            background: transparent;
            color: #94a3b8;
            line-height: 1;
        }

        .transfer-selected-remove:hover {
            color: #ef4444;
        }

        .transfer-footer-note {
            margin-top: 10px;
            font-size: 11px;
            color: #64748b;
        }

        .transfer-load-more {
            width: 100%;
            border: none;
            background: #f8fafc;
            color: #334155;
            font-size: 12px;
            font-weight: 600;
            padding: 10px 12px;
            border-top: 1px solid #e2e8f0;
        }

        .transfer-load-more:hover {
            background: #f1f5f9;
        }

        @media (max-width: 768px) {
            .transfer-grid {
                grid-template-columns: 1fr;
            }

            .transfer-toolbar {
                grid-template-columns: 1fr;
            }
        }
    </style>

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

    <div class="modal fade transfer-modal" tabindex="-1" role="dialog" aria-hidden="true" id="transfer-modal">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <div class="transfer-title">Transfer Campaign</div>
                        <div class="transfer-subtitle">Pindahkan item endorse ke campaign lain (internal atau external).</div>
                    </div>
                    <a class="close a-link" data-bs-dismiss="modal"><i class="bi bi-x-circle fs-24"></i></a>
                </div>
                <div class="modal-body">
                    <div class="transfer-grid">
                        <div class="transfer-card">
                            <div class="transfer-card-header">
                                <div class="transfer-card-title">Campaign Saat Ini</div>
                                <span class="transfer-pill" id="transfer-current-type"><?= $campaign_type_label ?></span>
                            </div>
                            <div class="transfer-card-meta" id="transfer-current-title"><?= $detail['title'] ?></div>
                            <div class="transfer-card-meta" id="transfer-current-id">ID #<?= $detail['id'] ?></div>
                            <?php if (!empty($campaign_period)) : ?>
                                <div class="transfer-card-meta"><?= $campaign_period ?></div>
                            <?php endif; ?>
                            <div class="transfer-selected" id="transfer-item-meta">
                                <span class="transfer-placeholder">Pilih item endorse untuk ditransfer.</span>
                            </div>
                        </div>
                        <div class="transfer-card">
                            <div class="transfer-card-header">
                                <div class="transfer-card-title">Target Campaign</div>
                                <span class="transfer-pill" id="transfer-target-type">-</span>
                            </div>
                            <div class="transfer-combobox" id="transfer-combobox">
                                <input type="text" class="transfer-input" id="transfer-search" placeholder="Cari campaign berdasarkan judul, brand, atau ID">
                                <button class="transfer-dropdown-btn" type="button" id="transfer-toggle"><i class="bi bi-chevron-down"></i></button>
                                <div class="transfer-dropdown" id="transfer-dropdown">
                                    <div class="transfer-tabs">
                                        <button type="button" class="transfer-tab" data-transfer-filter="0">External</button>
                                        <button type="button" class="transfer-tab" data-transfer-filter="1">Internal</button>
                                    </div>
                                    <div class="transfer-list" id="transfer-list"></div>
                                </div>
                            </div>
                            <div class="transfer-selected" id="transfer-selected">
                                <span class="transfer-placeholder">Belum ada campaign dipilih.</span>
                            </div>
                            <div class="transfer-message" id="transfer-message"></div>
                        </div>
                    </div>
                    <div class="transfer-actions">
                        <button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-transfer" type="button" id="transfer-submit" disabled>Transfer Sekarang</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade transfer-modal" tabindex="-1" role="dialog" aria-hidden="true" id="bulk-transfer-modal">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <div class="transfer-title">Bulk Transfer Campaign</div>
                        <div class="transfer-subtitle">Cari, filter, lalu pilih beberapa konten untuk dipindahkan ke campaign lain.</div>
                    </div>
                    <a class="close a-link" data-bs-dismiss="modal"><i class="bi bi-x-circle fs-24"></i></a>
                </div>
                <div class="modal-body">
                    <div class="transfer-grid bulk-transfer-grid">
                        <div class="transfer-card">
                            <div class="transfer-card-header">
                                <div class="transfer-card-title">Konten di Campaign Saat Ini</div>
                                <span class="transfer-pill"><?= $campaign_type_label ?></span>
                            </div>
                            <div class="transfer-card-meta"><?= $detail['title'] ?> · ID #<?= $detail['id'] ?></div>
                            <div class="transfer-toolbar mt-3">
                                <input type="text" class="transfer-input" id="bulk-transfer-search" placeholder="Cari creator, platform, task, link, atau keterangan">
                                <select class="transfer-select" id="bulk-transfer-status">
                                    <option value="">Semua Status</option>
                                    <option value="Review">Review</option>
                                    <option value="Hold">Hold</option>
                                    <option value="Acc">Acc</option>
                                    <option value="Draft Content">Draft Content</option>
                                    <option value="Posted Content">Posted Content</option>
                                    <option value="Reject">Reject</option>
                                    <option value="Problem">Problem</option>
                                </select>
                                <select class="transfer-select" id="bulk-transfer-platform">
                                    <option value="">Semua Platform</option>
                                    <option value="Tiktok">Tiktok</option>
                                    <option value="Instagram">Instagram</option>
                                    <option value="Youtube">Youtube</option>
                                    <option value="Shopee">Shopee</option>
                                    <option value="Tokopedia">Tokopedia</option>
                                </select>
                            </div>
                            <div class="transfer-summary-row">
                                <div class="transfer-card-meta" id="bulk-transfer-count">0 konten dipilih</div>
                                <div class="transfer-summary-actions">
                                    <button type="button" class="transfer-summary-btn" id="bulk-transfer-select-visible">Pilih semua hasil</button>
                                    <button type="button" class="transfer-summary-btn" id="bulk-transfer-clear">Hapus pilihan</button>
                                </div>
                            </div>
                            <div class="transfer-source-list" id="bulk-transfer-source-list">
                                <div class="transfer-loading">Memuat konten...</div>
                            </div>
                        </div>
                        <div class="transfer-card">
                            <div class="transfer-card-header">
                                <div class="transfer-card-title">Target Campaign</div>
                                <span class="transfer-pill" id="bulk-transfer-target-type">-</span>
                            </div>
                            <div class="transfer-selected" id="bulk-transfer-selected-items">
                                <span class="transfer-placeholder">Belum ada konten dipilih.</span>
                            </div>
                            <div class="transfer-footer-note">Pilihan tetap tersimpan walau kamu mengganti search atau filter.</div>
                            <div class="transfer-card-header mt-3">
                                <div class="transfer-card-title">Pilih Campaign Tujuan</div>
                            </div>
                            <div class="transfer-combobox" id="bulk-transfer-combobox">
                                <input type="text" class="transfer-input" id="bulk-transfer-campaign-search" placeholder="Cari campaign berdasarkan judul, brand, atau ID">
                                <button class="transfer-dropdown-btn" type="button" id="bulk-transfer-toggle"><i class="bi bi-chevron-down"></i></button>
                                <div class="transfer-dropdown" id="bulk-transfer-dropdown">
                                    <div class="transfer-tabs">
                                        <button type="button" class="transfer-tab" data-bulk-transfer-filter="0">External</button>
                                        <button type="button" class="transfer-tab" data-bulk-transfer-filter="1">Internal</button>
                                    </div>
                                    <div class="transfer-list" id="bulk-transfer-list"></div>
                                </div>
                            </div>
                            <div class="transfer-selected" id="bulk-transfer-selected">
                                <span class="transfer-placeholder">Belum ada campaign dipilih.</span>
                            </div>
                            <div class="transfer-message" id="bulk-transfer-message"></div>
                        </div>
                    </div>
                    <div class="transfer-actions">
                        <button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-transfer" type="button" id="bulk-transfer-submit" disabled>Transfer 0 Konten</button>
                    </div>
                </div>
            </div>
        </div>
    </div>


<input type="hidden" id="id_selected" name="id_selected" form="form-action">

<script src="https://unpkg.com/@popperjs/core@2"></script>
<script src="https://unpkg.com/tippy.js@6"></script>
<script>
    var list_id_v2 = '';
    var transferState = {
        idEndorse: null,
        targetCampaign: null,
        currentType: <?= json_encode((!empty($detail['is_internal']) && $detail['is_internal'] == 1) ? '1' : '0') ?>
    };
    var bulkTransferState = {
        selectedItems: {},
        visibleItems: [],
        page: 1,
        hasMore: false,
        targetCampaign: null,
        currentType: <?= json_encode((!empty($detail['is_internal']) && $detail['is_internal'] == 1) ? '1' : '0') ?>
    };
    var transferConfig = {
        baseUrl: <?= json_encode(base_url()) ?>,
        endorseBaseUrl: <?= json_encode(base_url() . '/endorse') ?>,
        currentCampaignId: <?= json_encode((string) $detail['id']) ?>,
        currentType: <?= json_encode((!empty($detail['is_internal']) && $detail['is_internal'] == 1) ? '1' : '0') ?>
    };

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

    window.create = function(id) {
        showModal('Tambah Konten', `<?= base_url() ?>/endorse/create?id=${id}`, true);
    };

    window.edit = function(id) {
        showModal('Edit Konten', `<?= base_url() ?>/endorse/edit?id=${id}`, true);
    };

    function hapus_data(id) {
        showModal('Hapus Data', `<?= base_url() ?>/endorse/action?code=hapus_data&id=${id}`);
    }

    function ubah_status(id) {
        showModal('Ubah Status Konten', `<?= base_url() ?>/endorse/action?code=ubah_status&id=${id}`);
    }

    function ubah_status_payment(id) {
        showModal('Ubah Status Payment', `<?= base_url() ?>/endorse/action?code=ubah_status_payment&id=${id}`);
    }

    window.set_payment = function(id) {
        showModal('Ajukan Payment', `<?= base_url() ?>/endorse/ajukan_payment?id=${id}`);
    };

    window.generate_mou = function(id) {
        showModal('', `<?= base_url() ?>/endorse/generate_mou?id=${id}`, true); 
        $("#title-form").html('');
    };


    window.set_batalkan_payment = function(id) {
        showModal('Batalkan Payment', `<?= base_url() ?>/endorse/batal_ajukan_payment?id=${id}`);
    };

    function ubah_status_data(id) {
        showModal('Ubah Status Data', `<?= base_url() ?>/endorse/action?code=ubah_status_data&id=${id}`);
    }

    function refresh_data(id) {
        showModal('Refresh Data', `<?= base_url() ?>/endorse/action?code=refresh_data&id_campaign=<?= $detail['id'] ?>`);
    }

    window.remove = function(id) {
        showModal('Hapus Data', `<?= base_url() ?>/endorse/remove?id=${id}`);
    };

    window.sync_all = function(id) {
        if (!confirm('Refresh semua konten aktif di campaign ini? Proses berjalan di latar belakang lewat antrian.')) return;
        $.ajax({
            url: '<?= base_url() ?>endorse/bulk-refresh',
            method: 'POST',
            data: { id_campaign: id },
            dataType: 'json',
            success: function(resp) {
                const queueUrl = '<?= base_url() ?>endorse/queue?id_campaign=' + id;
                if (resp && resp.status) {
                    const msg = resp.msg || ('Antrian dibuat: ' + resp.enqueued + ' baru, ' + resp.skipped_duplicates + ' sudah ada.');
                    if (typeof toastr !== 'undefined') {
                        toastr.success(msg + ' <a href="' + queueUrl + '" class="text-white text-underline"><b>Lihat antrian →</b></a>', '', { timeOut: 7000, escapeHtml: false });
                    } else {
                        if (confirm(msg + '\n\nBuka halaman antrian sekarang?')) window.location.href = queueUrl;
                    }
                } else {
                    const errMsg = (resp && resp.msg) ? resp.msg : 'Gagal membuat antrian refresh.';
                    if (typeof toastr !== 'undefined') toastr.error(errMsg);
                    else alert(errMsg);
                }
            },
            error: function() {
                alert('Gagal menghubungi server.');
            }
        });
    };

    window.sync = function(id) {
        showModal('Refresh Data', `<?= base_url() ?>/endorse/sync?id=${id}`);
    };

    window.clone = function(id) {
        showModal('Kloning Data', `<?= base_url() ?>/endorse/clone?id=${id}`);
    };

    function buildTransferCampaignHtml(items) {
        var html = '';
        items.forEach(function(row) {
            var typeLabel = row.is_internal == 1 ? 'INTERNAL' : 'EXTERNAL';
            var dates = '';
            if (row.start_at || row.until_at) {
                dates = (row.start_at || '-') + ' - ' + (row.until_at || '-');
            }
            html += '<button type="button" class="transfer-item" data-id="' + row.id + '" data-title="' + escapeHtml(row.title) + '" data-type="' + typeLabel + '">' +
                '<div>' +
                '<div class="transfer-item-title">' + escapeHtml(row.title || 'Campaign') + '</div>' +
                '<div class="transfer-item-meta">ID #' + row.id + (row.brand ? ' · ' + escapeHtml(row.brand) : '') + (dates ? ' · ' + escapeHtml(dates) : '') + '</div>' +
                '</div>' +
                '<span class="transfer-pill">' + typeLabel + '</span>' +
                '</button>';
        });

        return html;
    }

    function renderTransferCampaignSelection(state, selectedSelector, targetTypeSelector, submitSelector, submitLabel) {
        if (!state.targetCampaign) {
            $(selectedSelector).html('<span class="transfer-placeholder">Belum ada campaign dipilih.</span>');
            $(targetTypeSelector).text('-');
            if (submitSelector) {
                $(submitSelector).prop('disabled', true).text(submitLabel);
            }
            return;
        }

        $(selectedSelector).html('<div><strong>' + escapeHtml(state.targetCampaign.title) + '</strong></div><div class="transfer-item-meta">ID #' + state.targetCampaign.id + '</div>');
        $(targetTypeSelector).text(state.targetCampaign.type || '-');
        if (submitSelector) {
            $(submitSelector).prop('disabled', false).text(submitLabel);
        }
    }

    function fetchTransferCampaigns(options) {
        var keyword = $(options.searchSelector).val().trim();
        $(options.listSelector).html('<div class="transfer-loading">Memuat campaign...</div>');
        $.ajax({
            url: transferConfig.baseUrl + '/endorse/transfer-campaigns',
            dataType: 'json',
            data: {
                keyword: keyword,
                is_internal: options.state.currentType,
                exclude: transferConfig.currentCampaignId,
                limit: 20
            },
            success: function(res) {
                var items = res && res.data ? res.data : [];
                if (!items.length) {
                    $(options.listSelector).html('<div class="transfer-empty">Campaign tidak ditemukan.</div>');
                    return;
                }
                $(options.listSelector).html(buildTransferCampaignHtml(items));
            },
            error: function() {
                $(options.listSelector).html('<div class="transfer-empty">Gagal memuat campaign.</div>');
            }
        });
    }

    window.openTransferModal = function(idEndorse, creatorName, statusEndorse, platform) {
        transferState.idEndorse = idEndorse;
        transferState.targetCampaign = null;
        transferState.currentType = transferConfig.currentType;
        $("#transfer-selected").html('<span class="transfer-placeholder">Belum ada campaign dipilih.</span>');
        $("#transfer-message").hide().text('');
        $("#transfer-submit").prop('disabled', true).text('Transfer Sekarang');
        $("#transfer-target-type").text('-');
        $("#transfer-search").val('');

        var meta = [creatorName || '-', statusEndorse || '-', platform || '-'].join(' · ');
        $("#transfer-item-meta").text(meta);

        setTransferFilter(transferState.currentType);
        toggleTransferDropdown(true);
        $("#transfer-modal").modal('show');
        fetchTransferCampaigns({
            state: transferState,
            searchSelector: '#transfer-search',
            listSelector: '#transfer-list'
        });
    };

    function setTransferFilter(type) {
        transferState.currentType = String(type);
        $(".transfer-tab").removeClass('active');
        $('.transfer-tab[data-transfer-filter="' + transferState.currentType + '"]').addClass('active');
    }

    function toggleTransferDropdown(forceOpen) {
        var $combo = $("#transfer-combobox");
        if (forceOpen === true) {
            $combo.addClass('is-open');
            return;
        }
        if (forceOpen === false) {
            $combo.removeClass('is-open');
            return;
        }
        $combo.toggleClass('is-open');
    }

    function setBulkTransferFilter(type) {
        bulkTransferState.currentType = String(type);
        $('[data-bulk-transfer-filter]').removeClass('active');
        $('[data-bulk-transfer-filter="' + bulkTransferState.currentType + '"]').addClass('active');
    }

    function toggleBulkTransferDropdown(forceOpen) {
        var $combo = $("#bulk-transfer-combobox");
        if (forceOpen === true) {
            $combo.addClass('is-open');
            return;
        }
        if (forceOpen === false) {
            $combo.removeClass('is-open');
            return;
        }
        $combo.toggleClass('is-open');
    }

    function escapeHtml(text) {
        return String(text || '').replace(/[&<>"']/g, function(match) {
            return ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            })[match];
        });
    }

    function renderBulkTransferSelectedItems() {
        var ids = Object.keys(bulkTransferState.selectedItems);
        $("#bulk-transfer-count").text(ids.length + ' konten dipilih');

        if (!ids.length) {
            $("#bulk-transfer-selected-items").html('<span class="transfer-placeholder">Belum ada konten dipilih.</span>');
            updateBulkTransferSubmitState();
            return;
        }

        var html = '<div class="transfer-selected-list">';
        ids.forEach(function(id) {
            var item = bulkTransferState.selectedItems[id];
            html += '<div class="transfer-selected-tag">' +
                '<div>' +
                '<div><strong>' + escapeHtml(item.nama_creator || '-') + '</strong></div>' +
                '<div class="transfer-item-meta">ID #' + id + ' · ' + escapeHtml(item.platform || '-') + ' · ' + escapeHtml(item.status_endorse || '-')</div>' +
                '</div>' +
                '<button type="button" class="transfer-selected-remove" data-remove-bulk-id="' + id + '"><i class="bi bi-x-lg"></i></button>' +
                '</div>';
        });
        html += '</div>';
        $("#bulk-transfer-selected-items").html(html);
        updateBulkTransferSubmitState();
    }

    function getBulkTransferCount() {
        return Object.keys(bulkTransferState.selectedItems).length;
    }

    function updateBulkTransferSubmitState() {
        var count = getBulkTransferCount();
        var enabled = count > 0 && bulkTransferState.targetCampaign;
        $("#bulk-transfer-submit").prop('disabled', !enabled).text('Transfer ' + count + ' Konten');
    }

    function getBulkTransferSourceParams() {
        return {
            id_campaign: transferConfig.currentCampaignId,
            keyword: $("#bulk-transfer-search").val().trim(),
            status_endorse: $("#bulk-transfer-status").val(),
            platform: $("#bulk-transfer-platform").val(),
            page: bulkTransferState.page,
            limit: 20
        };
    }

    function buildBulkSourceDescription(row) {
        var parts = [];
        if (row.task) {
            parts.push(row.task);
        }
        if (row.link_upload) {
            parts.push(row.link_upload);
        } else if (row.desc) {
            parts.push(row.desc);
        }
        return parts.join(' · ');
    }

    function renderBulkTransferSourceItems(items, appendMode) {
        bulkTransferState.visibleItems = appendMode ? bulkTransferState.visibleItems.concat(items) : items.slice();
        var html = '';

        if (!bulkTransferState.visibleItems.length) {
            html = '<div class="transfer-empty">Konten tidak ditemukan.</div>';
        } else {
            bulkTransferState.visibleItems.forEach(function(row) {
                var checked = !!bulkTransferState.selectedItems[row.id];
                var desc = buildBulkSourceDescription(row);
                html += '<label class="transfer-source-item ' + (checked ? 'is-selected' : '') + '">' +
                    '<input type="checkbox" class="transfer-source-checkbox" data-bulk-id="' + row.id + '"' + (checked ? ' checked' : '') + '>' +
                    '<div class="transfer-source-body">' +
                    '<div class="transfer-source-title">' + escapeHtml(row.nama_creator || '-') + '</div>' +
                    '<div class="transfer-source-meta">ID #' + row.id + ' · ' + escapeHtml(row.platform || '-') + ' · ' + escapeHtml(row.status_endorse || '-') + (row.posting_at ? ' · Posting ' + escapeHtml(row.posting_at) : '') + '</div>' +
                    (desc ? '<div class="transfer-source-desc">' + escapeHtml(desc) + '</div>' : '') +
                    '</div>' +
                    '</label>';
            });
        }

        if (bulkTransferState.hasMore && bulkTransferState.visibleItems.length) {
            html += '<button type="button" class="transfer-load-more" id="bulk-transfer-load-more">Muat lebih banyak</button>';
        }

        $("#bulk-transfer-source-list").html(html);
    }

    function fetchBulkTransferContents(appendMode) {
        if (!appendMode) {
            bulkTransferState.page = 1;
            bulkTransferState.hasMore = false;
            $("#bulk-transfer-source-list").html('<div class="transfer-loading">Memuat konten...</div>');
        } else {
            $("#bulk-transfer-load-more").prop('disabled', true).text('Memuat...');
        }

        $.ajax({
            url: transferConfig.baseUrl + '/endorse/transfer-contents',
            dataType: 'json',
            data: getBulkTransferSourceParams(),
            success: function(res) {
                var items = res && res.data ? res.data : [];
                var meta = res && res.meta ? res.meta : {};
                bulkTransferState.hasMore = !!meta.has_more;
                renderBulkTransferSourceItems(items, appendMode);
            },
            error: function() {
                $("#bulk-transfer-source-list").html('<div class="transfer-empty">Gagal memuat konten.</div>');
            }
        });
    }

    function fetchBulkTransferCampaigns() {
        fetchTransferCampaigns({
            state: bulkTransferState,
            searchSelector: '#bulk-transfer-campaign-search',
            listSelector: '#bulk-transfer-list'
        });
    }

    function openBulkTransferModal() {
        bulkTransferState.selectedItems = {};
        bulkTransferState.visibleItems = [];
        bulkTransferState.page = 1;
        bulkTransferState.hasMore = false;
        bulkTransferState.targetCampaign = null;
        bulkTransferState.currentType = transferConfig.currentType;

        $("#bulk-transfer-search").val('');
        $("#bulk-transfer-status").val('');
        $("#bulk-transfer-platform").val('');
        $("#bulk-transfer-campaign-search").val('');
        $("#bulk-transfer-message").hide().text('');
        renderBulkTransferSelectedItems();
        renderTransferCampaignSelection(bulkTransferState, '#bulk-transfer-selected', '#bulk-transfer-target-type', null, '');
        setBulkTransferFilter(bulkTransferState.currentType);
        toggleBulkTransferDropdown(true);
        $("#bulk-transfer-modal").modal('show');
        fetchBulkTransferContents(false);
        fetchBulkTransferCampaigns();
    }
    window.openBulkTransferModal = openBulkTransferModal;

    $("#bulk-transfer-trigger").on('click', function(e) {
        e.preventDefault();
        openBulkTransferModal();
    });

    $(document).on('click', '.transfer-item', function() {
        var id = $(this).data('id');
        var title = $(this).data('title');
        var type = $(this).data('type');
        if ($(this).closest('#bulk-transfer-list').length) {
            bulkTransferState.targetCampaign = {
                id: id,
                title: title,
                type: type
            };
            renderTransferCampaignSelection(bulkTransferState, '#bulk-transfer-selected', '#bulk-transfer-target-type', null, '');
            $("#bulk-transfer-message").hide().text('');
            updateBulkTransferSubmitState();
            toggleBulkTransferDropdown(false);
            return;
        }

        transferState.targetCampaign = {
            id: id,
            title: title,
            type: type
        };
        renderTransferCampaignSelection(transferState, '#transfer-selected', '#transfer-target-type', '#transfer-submit', 'Transfer Sekarang');
        $("#transfer-message").hide().text('');
        toggleTransferDropdown(false);
    });

    $(document).on('click', '.transfer-tab', function() {
        if ($(this).is('[data-bulk-transfer-filter]')) {
            return;
        }
        var type = $(this).data('transfer-filter');
        setTransferFilter(type);
        fetchTransferCampaigns({
            state: transferState,
            searchSelector: '#transfer-search',
            listSelector: '#transfer-list'
        });
    });

    $(document).on('click', '[data-bulk-transfer-filter]', function() {
        var type = $(this).data('bulk-transfer-filter');
        setBulkTransferFilter(type);
        fetchBulkTransferCampaigns();
    });

    $("#transfer-toggle").on('click', function() {
        toggleTransferDropdown();
    });

    $("#transfer-search").on('focus', function() {
        toggleTransferDropdown(true);
    });

    var transferSearchTimer = null;
    $("#transfer-search").on('input', function() {
        clearTimeout(transferSearchTimer);
        transferSearchTimer = setTimeout(function() {
            fetchTransferCampaigns({
                state: transferState,
                searchSelector: '#transfer-search',
                listSelector: '#transfer-list'
            });
        }, 250);
    });

    $("#bulk-transfer-toggle").on('click', function() {
        toggleBulkTransferDropdown();
    });

    $("#bulk-transfer-campaign-search").on('focus', function() {
        toggleBulkTransferDropdown(true);
    });

    var bulkTransferCampaignSearchTimer = null;
    $("#bulk-transfer-campaign-search").on('input', function() {
        clearTimeout(bulkTransferCampaignSearchTimer);
        bulkTransferCampaignSearchTimer = setTimeout(function() {
            fetchBulkTransferCampaigns();
        }, 250);
    });

    var bulkTransferSourceSearchTimer = null;
    $("#bulk-transfer-search").on('input', function() {
        clearTimeout(bulkTransferSourceSearchTimer);
        bulkTransferSourceSearchTimer = setTimeout(function() {
            fetchBulkTransferContents(false);
        }, 250);
    });

    $("#bulk-transfer-status, #bulk-transfer-platform").on('change', function() {
        fetchBulkTransferContents(false);
    });

    $("#bulk-transfer-select-visible").on('click', function() {
        bulkTransferState.visibleItems.forEach(function(item) {
            bulkTransferState.selectedItems[item.id] = item;
        });
        renderBulkTransferSelectedItems();
        renderBulkTransferSourceItems(bulkTransferState.visibleItems, false);
    });

    $("#bulk-transfer-clear").on('click', function() {
        bulkTransferState.selectedItems = {};
        renderBulkTransferSelectedItems();
        renderBulkTransferSourceItems(bulkTransferState.visibleItems, false);
    });

    $(document).on('change', '[data-bulk-id]', function() {
        var id = $(this).data('bulk-id');
        var item = bulkTransferState.visibleItems.find(function(row) {
            return String(row.id) === String(id);
        });
        if (!item) {
            return;
        }

        if ($(this).is(':checked')) {
            bulkTransferState.selectedItems[id] = item;
        } else {
            delete bulkTransferState.selectedItems[id];
        }

        renderBulkTransferSelectedItems();
        $(this).closest('.transfer-source-item').toggleClass('is-selected', $(this).is(':checked'));
    });

    $(document).on('click', '[data-remove-bulk-id]', function() {
        var id = $(this).data('remove-bulk-id');
        delete bulkTransferState.selectedItems[id];
        renderBulkTransferSelectedItems();
        renderBulkTransferSourceItems(bulkTransferState.visibleItems, false);
    });

    $(document).on('click', '#bulk-transfer-load-more', function() {
        bulkTransferState.page += 1;
        fetchBulkTransferContents(true);
    });

    $(document).on('click', function(e) {
        if ($(e.target).closest('#transfer-combobox').length === 0) {
            toggleTransferDropdown(false);
        }
        if ($(e.target).closest('#bulk-transfer-combobox').length === 0) {
            toggleBulkTransferDropdown(false);
        }
    });

    $("#transfer-submit").on('click', function() {
        if (!transferState.idEndorse || !transferState.targetCampaign || !transferState.targetCampaign.id) {
            return;
        }
        var $btn = $(this);
        $btn.prop('disabled', true).text('Memproses...');
        $("#transfer-message").hide().text('');

        $.ajax({
            type: 'POST',
            url: transferConfig.baseUrl + '/endorse/transfer-process',
            dataType: 'json',
            data: {
                id_endorse: transferState.idEndorse,
                target_campaign: transferState.targetCampaign.id
            },
            success: function(res) {
                if (res && res.status) {
                    var params = new URLSearchParams(window.location.search);
                    params.set('id_campaign', transferState.targetCampaign.id);
                    params.delete('ids');
                    var redirectUrl = transferConfig.endorseBaseUrl + '?' + params.toString();
                    window.location.href = redirectUrl;
                } else {
                    var msg = res && res.message ? res.message : 'Transfer gagal.';
                    $("#transfer-message").text(msg).show();
                    $btn.prop('disabled', false).text('Transfer Sekarang');
                }
            },
            error: function() {
                $("#transfer-message").text('Transfer gagal. Silakan coba lagi.').show();
                $btn.prop('disabled', false).text('Transfer Sekarang');
            }
        });
    });

    $("#bulk-transfer-submit").on('click', function() {
        var ids = Object.keys(bulkTransferState.selectedItems);
        if (!ids.length || !bulkTransferState.targetCampaign || !bulkTransferState.targetCampaign.id) {
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('Memproses...');
        $("#bulk-transfer-message").hide().text('');

        $.ajax({
            type: 'POST',
            url: transferConfig.baseUrl + '/endorse/transfer-bulk-process',
            dataType: 'json',
            data: {
                source_campaign: transferConfig.currentCampaignId,
                target_campaign: bulkTransferState.targetCampaign.id,
                id_endorse: ids
            },
            traditional: true,
            success: function(res) {
                if (res && res.status) {
                    sessionStorage.setItem('endorseTransferSuccess', JSON.stringify({
                        message: res.message || (ids.length + ' konten berhasil ditransfer.'),
                        count: ids.length
                    }));

                    var params = new URLSearchParams(window.location.search);
                    params.set('id_campaign', transferConfig.currentCampaignId);
                    params.delete('ids');
                    window.location.href = transferConfig.endorseBaseUrl + '?' + params.toString();
                } else {
                    var msg = res && res.message ? res.message : 'Transfer bulk gagal.';
                    $("#bulk-transfer-message").text(msg).show();
                    updateBulkTransferSubmitState();
                }
            },
            error: function() {
                $("#bulk-transfer-message").text('Transfer bulk gagal. Silakan coba lagi.').show();
                updateBulkTransferSubmitState();
            }
        });
    });

    function showTransferSuccessBanner() {
        var raw = sessionStorage.getItem('endorseTransferSuccess');
        if (!raw) {
            return;
        }

        sessionStorage.removeItem('endorseTransferSuccess');

        var payload = null;
        try {
            payload = JSON.parse(raw);
        } catch (error) {
            payload = null;
        }

        if (!payload || !payload.message) {
            return;
        }

        $("#endorse-notif").html(
            '<div class="alert alert-success py-2 px-3 mb-2" role="alert">' +
            escapeHtml(payload.message) +
            '</div>' +
            $("#endorse-notif").html()
        );
    }

    window.get_id = function() {
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
    };

    window.tampilkan_data = function() {
        window.location.href = `<?= base_url() ?>/endorse?id_campaign=<?= $detail['id'] ?>&ids=${list_id_v2}`;
    };

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

    $(document).ready(function() {
        showTransferSuccessBanner();
    });
</script>

<script>
    (function() {
        var baseUrl = <?= json_encode(base_url()) ?>;
        var endorseBaseUrl = <?= json_encode(base_url() . '/endorse') ?>;
        var currentCampaignId = <?= json_encode((string) $detail['id']) ?>;
        var currentType = <?= json_encode((!empty($detail['is_internal']) && $detail['is_internal'] == 1) ? '1' : '0') ?>;

        var transferState = {
            idEndorse: null,
            targetCampaign: null,
            currentType: currentType
        };

        var bulkTransferState = {
            selectedItems: {},
            visibleItems: [],
            page: 1,
            hasMore: false,
            targetCampaign: null,
            currentType: currentType
        };

        function showModalFallback(title, url, isLarge) {
            if (!$('#modal-form').length || !$('#load-form').length) {
                window.location.href = url;
                return;
            }

            $("#load-form").html('Loading...');
            $("#modal-form").modal('show');
            $("#title-form").html(title || '');

            if (isLarge) {
                $("#modal-form .modal-dialog").addClass("modal-lg");
            } else {
                $("#modal-form .modal-dialog").removeClass("modal-lg");
            }

            $("#load-form").load(url);
        }

        function escapeHtmlFallback(text) {
            return String(text || '').replace(/[&<>"']/g, function(match) {
                return ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                })[match];
            });
        }

        window.create = function(id) {
            showModalFallback('Tambah Konten', baseUrl + '/endorse/create?id=' + id, true);
        };

        window.edit = function(id) {
            showModalFallback('Edit Konten', baseUrl + '/endorse/edit?id=' + id, true);
        };

        window.remove = function(id) {
            showModalFallback('Hapus Data', baseUrl + '/endorse/remove?id=' + id, false);
        };

        window.sync = function(id) {
            showModalFallback('Refresh Data', baseUrl + '/endorse/sync?id=' + id, false);
        };

        window.clone = function(id) {
            showModalFallback('Kloning Data', baseUrl + '/endorse/clone?id=' + id, false);
        };

        window.set_payment = function(id) {
            showModalFallback('Ajukan Payment', baseUrl + '/endorse/ajukan_payment?id=' + id, false);
        };

        window.set_batalkan_payment = function(id) {
            showModalFallback('Batalkan Payment', baseUrl + '/endorse/batal_ajukan_payment?id=' + id, false);
        };

        window.generate_mou = function(id) {
            showModalFallback('', baseUrl + '/endorse/generate_mou?id=' + id, true);
            $("#title-form").html('');
        };

        window.sync_all = function(id) {
            if (!confirm('Refresh semua konten aktif di campaign ini? Proses berjalan di latar belakang lewat antrian.')) {
                return;
            }

            $.ajax({
                url: baseUrl + 'endorse/bulk-refresh',
                method: 'POST',
                dataType: 'json',
                data: { id_campaign: id },
                success: function(resp) {
                    var queueUrl = baseUrl + 'endorse/queue?id_campaign=' + id;
                    if (resp && resp.status) {
                        var msg = resp.msg || ('Antrian dibuat: ' + resp.enqueued + ' baru, ' + resp.skipped_duplicates + ' sudah ada.');
                        if (typeof toastr !== 'undefined') {
                            toastr.success(msg + ' <a href="' + queueUrl + '" class="text-white text-underline"><b>Lihat antrian →</b></a>', '', {
                                timeOut: 7000,
                                escapeHtml: false
                            });
                        } else if (confirm(msg + '\n\nBuka halaman antrian sekarang?')) {
                            window.location.href = queueUrl;
                        }
                    } else {
                        var errMsg = (resp && resp.msg) ? resp.msg : 'Gagal membuat antrian refresh.';
                        if (typeof toastr !== 'undefined') {
                            toastr.error(errMsg);
                        } else {
                            alert(errMsg);
                        }
                    }
                },
                error: function() {
                    alert('Gagal menghubungi server.');
                }
            });
        };

        window.get_id = function() {
            var selectedValues = [];
            var listIdV2 = '';

            $('input[name="list_id"]').each(function() {
                if ($(this).is(":checked")) {
                    selectedValues.push($(this).val());
                    listIdV2 += $(this).val() + ',';
                } else {
                    selectedValues.push('0');
                }
            });

            if (listIdV2.length > 0) {
                listIdV2 = listIdV2.slice(0, -1);
            }

            window.list_id_v2 = listIdV2;
            $('#id_selected').val(selectedValues.join(','));
        };

        window.tampilkan_data = function() {
            window.location.href = endorseBaseUrl + '?id_campaign=' + currentCampaignId + '&ids=' + (window.list_id_v2 || '');
        };

        function renderTransferCampaignSelection(state, selectedSelector, targetTypeSelector, submitSelector, submitLabel) {
            if (!state.targetCampaign) {
                $(selectedSelector).html('<span class="transfer-placeholder">Belum ada campaign dipilih.</span>');
                $(targetTypeSelector).text('-');
                if (submitSelector) {
                    $(submitSelector).prop('disabled', true).text(submitLabel);
                }
                return;
            }

            $(selectedSelector).html('<div><strong>' + escapeHtmlFallback(state.targetCampaign.title) + '</strong></div><div class="transfer-item-meta">ID #' + state.targetCampaign.id + '</div>');
            $(targetTypeSelector).text(state.targetCampaign.type || '-');
            if (submitSelector) {
                $(submitSelector).prop('disabled', false).text(submitLabel);
            }
        }

        function renderTransferCampaignHtml(items) {
            var html = '';

            items.forEach(function(row) {
                var typeLabel = row.is_internal == 1 ? 'INTERNAL' : 'EXTERNAL';
                var dates = '';

                if (row.start_at || row.until_at) {
                    dates = (row.start_at || '-') + ' - ' + (row.until_at || '-');
                }

                html += '<button type="button" class="transfer-item" data-id="' + row.id + '" data-title="' + escapeHtmlFallback(row.title) + '" data-type="' + typeLabel + '">' +
                    '<div>' +
                    '<div class="transfer-item-title">' + escapeHtmlFallback(row.title || 'Campaign') + '</div>' +
                    '<div class="transfer-item-meta">ID #' + row.id + (row.brand ? ' · ' + escapeHtmlFallback(row.brand) : '') + (dates ? ' · ' + escapeHtmlFallback(dates) : '') + '</div>' +
                    '</div>' +
                    '<span class="transfer-pill">' + typeLabel + '</span>' +
                    '</button>';
            });

            return html;
        }

        function fetchTransferCampaigns(options) {
            var keyword = $(options.searchSelector).val().trim();
            $(options.listSelector).html('<div class="transfer-loading">Memuat campaign...</div>');

            $.ajax({
                url: baseUrl + '/endorse/transfer-campaigns',
                dataType: 'json',
                data: {
                    keyword: keyword,
                    is_internal: options.state.currentType,
                    exclude: currentCampaignId,
                    limit: 20
                },
                success: function(res) {
                    var items = res && res.data ? res.data : [];
                    if (!items.length) {
                        $(options.listSelector).html('<div class="transfer-empty">Campaign tidak ditemukan.</div>');
                        return;
                    }

                    $(options.listSelector).html(renderTransferCampaignHtml(items));
                },
                error: function() {
                    $(options.listSelector).html('<div class="transfer-empty">Gagal memuat campaign.</div>');
                }
            });
        }

        function setTransferFilter(type) {
            transferState.currentType = String(type);
            $(".transfer-tab").removeClass('active');
            $('.transfer-tab[data-transfer-filter="' + transferState.currentType + '"]').addClass('active');
        }

        function setBulkTransferFilter(type) {
            bulkTransferState.currentType = String(type);
            $('[data-bulk-transfer-filter]').removeClass('active');
            $('[data-bulk-transfer-filter="' + bulkTransferState.currentType + '"]').addClass('active');
        }

        function toggleTransferDropdown(forceOpen) {
            var $combo = $("#transfer-combobox");
            if (forceOpen === true) {
                $combo.addClass('is-open');
                return;
            }
            if (forceOpen === false) {
                $combo.removeClass('is-open');
                return;
            }
            $combo.toggleClass('is-open');
        }

        function toggleBulkTransferDropdown(forceOpen) {
            var $combo = $("#bulk-transfer-combobox");
            if (forceOpen === true) {
                $combo.addClass('is-open');
                return;
            }
            if (forceOpen === false) {
                $combo.removeClass('is-open');
                return;
            }
            $combo.toggleClass('is-open');
        }

        function buildBulkSourceDescription(row) {
            var parts = [];
            if (row.task) {
                parts.push(row.task);
            }
            if (row.link_upload) {
                parts.push(row.link_upload);
            } else if (row.desc) {
                parts.push(row.desc);
            }
            return parts.join(' · ');
        }

        function updateBulkTransferSubmitState() {
            var count = Object.keys(bulkTransferState.selectedItems).length;
            var enabled = count > 0 && bulkTransferState.targetCampaign;
            $("#bulk-transfer-submit").prop('disabled', !enabled).text('Transfer ' + count + ' Konten');
        }

        function renderBulkTransferSelectedItems() {
            var ids = Object.keys(bulkTransferState.selectedItems);
            $("#bulk-transfer-count").text(ids.length + ' konten dipilih');

            if (!ids.length) {
                $("#bulk-transfer-selected-items").html('<span class="transfer-placeholder">Belum ada konten dipilih.</span>');
                updateBulkTransferSubmitState();
                return;
            }

            var html = '<div class="transfer-selected-list">';
            ids.forEach(function(id) {
                var item = bulkTransferState.selectedItems[id];
                html += '<div class="transfer-selected-tag">' +
                    '<div>' +
                    '<div><strong>' + escapeHtmlFallback(item.nama_creator || '-') + '</strong></div>' +
                    '<div class="transfer-item-meta">ID #' + id + ' · ' + escapeHtmlFallback(item.platform || '-') + ' · ' + escapeHtmlFallback(item.status_endorse || '-') + '</div>' +
                    '</div>' +
                    '<button type="button" class="transfer-selected-remove" data-remove-bulk-id="' + id + '"><i class="bi bi-x-lg"></i></button>' +
                    '</div>';
            });
            html += '</div>';

            $("#bulk-transfer-selected-items").html(html);
            updateBulkTransferSubmitState();
        }

        function renderBulkTransferSourceItems(items, appendMode) {
            bulkTransferState.visibleItems = appendMode ? bulkTransferState.visibleItems.concat(items) : items.slice();
            var html = '';

            if (!bulkTransferState.visibleItems.length) {
                html = '<div class="transfer-empty">Konten tidak ditemukan.</div>';
            } else {
                bulkTransferState.visibleItems.forEach(function(row) {
                    var checked = !!bulkTransferState.selectedItems[row.id];
                    var desc = buildBulkSourceDescription(row);
                    html += '<label class="transfer-source-item ' + (checked ? 'is-selected' : '') + '">' +
                        '<input type="checkbox" class="transfer-source-checkbox" data-bulk-id="' + row.id + '"' + (checked ? ' checked' : '') + '>' +
                        '<div class="transfer-source-body">' +
                        '<div class="transfer-source-title">' + escapeHtmlFallback(row.nama_creator || '-') + '</div>' +
                        '<div class="transfer-source-meta">ID #' + row.id + ' · ' + escapeHtmlFallback(row.platform || '-') + ' · ' + escapeHtmlFallback(row.status_endorse || '-') + (row.posting_at ? ' · Posting ' + escapeHtmlFallback(row.posting_at) : '') + '</div>' +
                        (desc ? '<div class="transfer-source-desc">' + escapeHtmlFallback(desc) + '</div>' : '') +
                        '</div>' +
                        '</label>';
                });
            }

            if (bulkTransferState.hasMore && bulkTransferState.visibleItems.length) {
                html += '<button type="button" class="transfer-load-more" id="bulk-transfer-load-more">Muat lebih banyak</button>';
            }

            $("#bulk-transfer-source-list").html(html);
        }

        function fetchBulkTransferContents(appendMode) {
            if (!appendMode) {
                bulkTransferState.page = 1;
                bulkTransferState.hasMore = false;
                $("#bulk-transfer-source-list").html('<div class="transfer-loading">Memuat konten...</div>');
            } else {
                $("#bulk-transfer-load-more").prop('disabled', true).text('Memuat...');
            }

            $.ajax({
                url: baseUrl + '/endorse/transfer-contents',
                dataType: 'json',
                data: {
                    id_campaign: currentCampaignId,
                    keyword: $("#bulk-transfer-search").val().trim(),
                    status_endorse: $("#bulk-transfer-status").val(),
                    platform: $("#bulk-transfer-platform").val(),
                    page: bulkTransferState.page,
                    limit: 20
                },
                success: function(res) {
                    var items = res && res.data ? res.data : [];
                    var meta = res && res.meta ? res.meta : {};
                    bulkTransferState.hasMore = !!meta.has_more;
                    renderBulkTransferSourceItems(items, appendMode);
                },
                error: function() {
                    $("#bulk-transfer-source-list").html('<div class="transfer-empty">Gagal memuat konten.</div>');
                }
            });
        }

        function fetchBulkTransferCampaigns() {
            fetchTransferCampaigns({
                state: bulkTransferState,
                searchSelector: '#bulk-transfer-campaign-search',
                listSelector: '#bulk-transfer-list'
            });
        }

        window.openTransferModal = function(idEndorse, creatorName, statusEndorse, platform) {
            transferState.idEndorse = idEndorse;
            transferState.targetCampaign = null;
            transferState.currentType = currentType;
            $("#transfer-selected").html('<span class="transfer-placeholder">Belum ada campaign dipilih.</span>');
            $("#transfer-message").hide().text('');
            $("#transfer-submit").prop('disabled', true).text('Transfer Sekarang');
            $("#transfer-target-type").text('-');
            $("#transfer-search").val('');
            $("#transfer-item-meta").text([creatorName || '-', statusEndorse || '-', platform || '-'].join(' · '));
            setTransferFilter(transferState.currentType);
            toggleTransferDropdown(true);
            $("#transfer-modal").modal('show');

            fetchTransferCampaigns({
                state: transferState,
                searchSelector: '#transfer-search',
                listSelector: '#transfer-list'
            });
        };

        window.openBulkTransferModal = function() {
            bulkTransferState.selectedItems = {};
            bulkTransferState.visibleItems = [];
            bulkTransferState.page = 1;
            bulkTransferState.hasMore = false;
            bulkTransferState.targetCampaign = null;
            bulkTransferState.currentType = currentType;
            $("#bulk-transfer-search").val('');
            $("#bulk-transfer-status").val('');
            $("#bulk-transfer-platform").val('');
            $("#bulk-transfer-campaign-search").val('');
            $("#bulk-transfer-message").hide().text('');
            renderBulkTransferSelectedItems();
            renderTransferCampaignSelection(bulkTransferState, '#bulk-transfer-selected', '#bulk-transfer-target-type', null, '');
            setBulkTransferFilter(bulkTransferState.currentType);
            toggleBulkTransferDropdown(true);
            $("#bulk-transfer-modal").modal('show');
            fetchBulkTransferContents(false);
            fetchBulkTransferCampaigns();
        };

        $(document)
            .off('click.endorseFallback', '.transfer-item')
            .on('click.endorseFallback', '.transfer-item', function() {
                var id = $(this).data('id');
                var title = $(this).data('title');
                var type = $(this).data('type');

                if ($(this).closest('#bulk-transfer-list').length) {
                    bulkTransferState.targetCampaign = { id: id, title: title, type: type };
                    renderTransferCampaignSelection(bulkTransferState, '#bulk-transfer-selected', '#bulk-transfer-target-type', null, '');
                    $("#bulk-transfer-message").hide().text('');
                    updateBulkTransferSubmitState();
                    toggleBulkTransferDropdown(false);
                    return;
                }

                transferState.targetCampaign = { id: id, title: title, type: type };
                renderTransferCampaignSelection(transferState, '#transfer-selected', '#transfer-target-type', '#transfer-submit', 'Transfer Sekarang');
                $("#transfer-message").hide().text('');
                toggleTransferDropdown(false);
            })
            .off('click.endorseFallback', '.transfer-tab')
            .on('click.endorseFallback', '.transfer-tab', function() {
                if ($(this).is('[data-bulk-transfer-filter]')) {
                    return;
                }
                setTransferFilter($(this).data('transfer-filter'));
                fetchTransferCampaigns({
                    state: transferState,
                    searchSelector: '#transfer-search',
                    listSelector: '#transfer-list'
                });
            })
            .off('click.endorseFallback', '[data-bulk-transfer-filter]')
            .on('click.endorseFallback', '[data-bulk-transfer-filter]', function() {
                setBulkTransferFilter($(this).data('bulk-transfer-filter'));
                fetchBulkTransferCampaigns();
            })
            .off('change.endorseFallback', '[data-bulk-id]')
            .on('change.endorseFallback', '[data-bulk-id]', function() {
                var id = $(this).data('bulk-id');
                var item = bulkTransferState.visibleItems.find(function(row) {
                    return String(row.id) === String(id);
                });

                if (!item) {
                    return;
                }

                if ($(this).is(':checked')) {
                    bulkTransferState.selectedItems[id] = item;
                } else {
                    delete bulkTransferState.selectedItems[id];
                }

                renderBulkTransferSelectedItems();
                $(this).closest('.transfer-source-item').toggleClass('is-selected', $(this).is(':checked'));
            })
            .off('click.endorseFallback', '[data-remove-bulk-id]')
            .on('click.endorseFallback', '[data-remove-bulk-id]', function() {
                var id = $(this).data('remove-bulk-id');
                delete bulkTransferState.selectedItems[id];
                renderBulkTransferSelectedItems();
                renderBulkTransferSourceItems(bulkTransferState.visibleItems, false);
            })
            .off('click.endorseFallback', '#bulk-transfer-load-more')
            .on('click.endorseFallback', '#bulk-transfer-load-more', function() {
                bulkTransferState.page += 1;
                fetchBulkTransferContents(true);
            })
            .off('click.endorseFallbackOutside')
            .on('click.endorseFallbackOutside', function(e) {
                if ($(e.target).closest('#transfer-combobox').length === 0) {
                    toggleTransferDropdown(false);
                }
                if ($(e.target).closest('#bulk-transfer-combobox').length === 0) {
                    toggleBulkTransferDropdown(false);
                }
            });

        $("#transfer-toggle").off('click.endorseFallback').on('click.endorseFallback', function() {
            toggleTransferDropdown();
        });
        $("#bulk-transfer-toggle").off('click.endorseFallback').on('click.endorseFallback', function() {
            toggleBulkTransferDropdown();
        });
        $("#transfer-search").off('focus.endorseFallback').on('focus.endorseFallback', function() {
            toggleTransferDropdown(true);
        });
        $("#bulk-transfer-campaign-search").off('focus.endorseFallback').on('focus.endorseFallback', function() {
            toggleBulkTransferDropdown(true);
        });

        var transferSearchTimer = null;
        $("#transfer-search").off('input.endorseFallback').on('input.endorseFallback', function() {
            clearTimeout(transferSearchTimer);
            transferSearchTimer = setTimeout(function() {
                fetchTransferCampaigns({
                    state: transferState,
                    searchSelector: '#transfer-search',
                    listSelector: '#transfer-list'
                });
            }, 250);
        });

        var bulkTransferCampaignTimer = null;
        $("#bulk-transfer-campaign-search").off('input.endorseFallback').on('input.endorseFallback', function() {
            clearTimeout(bulkTransferCampaignTimer);
            bulkTransferCampaignTimer = setTimeout(fetchBulkTransferCampaigns, 250);
        });

        var bulkTransferSourceTimer = null;
        $("#bulk-transfer-search").off('input.endorseFallback').on('input.endorseFallback', function() {
            clearTimeout(bulkTransferSourceTimer);
            bulkTransferSourceTimer = setTimeout(function() {
                fetchBulkTransferContents(false);
            }, 250);
        });

        $("#bulk-transfer-status, #bulk-transfer-platform").off('change.endorseFallback').on('change.endorseFallback', function() {
            fetchBulkTransferContents(false);
        });

        $("#bulk-transfer-trigger").off('click.endorseFallback').on('click.endorseFallback', function(e) {
            e.preventDefault();
            window.openBulkTransferModal();
        });

        $("#bulk-transfer-select-visible").off('click.endorseFallback').on('click.endorseFallback', function() {
            bulkTransferState.visibleItems.forEach(function(item) {
                bulkTransferState.selectedItems[item.id] = item;
            });
            renderBulkTransferSelectedItems();
            renderBulkTransferSourceItems(bulkTransferState.visibleItems, false);
        });

        $("#bulk-transfer-clear").off('click.endorseFallback').on('click.endorseFallback', function() {
            bulkTransferState.selectedItems = {};
            renderBulkTransferSelectedItems();
            renderBulkTransferSourceItems(bulkTransferState.visibleItems, false);
        });

        $("#transfer-submit").off('click.endorseFallback').on('click.endorseFallback', function() {
            if (!transferState.idEndorse || !transferState.targetCampaign || !transferState.targetCampaign.id) {
                return;
            }

            var $btn = $(this);
            $btn.prop('disabled', true).text('Memproses...');
            $("#transfer-message").hide().text('');

            $.ajax({
                type: 'POST',
                url: baseUrl + '/endorse/transfer-process',
                dataType: 'json',
                data: {
                    id_endorse: transferState.idEndorse,
                    target_campaign: transferState.targetCampaign.id
                },
                success: function(res) {
                    if (res && res.status) {
                        var params = new URLSearchParams(window.location.search);
                        params.set('id_campaign', transferState.targetCampaign.id);
                        params.delete('ids');
                        window.location.href = endorseBaseUrl + '?' + params.toString();
                    } else {
                        $("#transfer-message").text((res && res.message) ? res.message : 'Transfer gagal.').show();
                        $btn.prop('disabled', false).text('Transfer Sekarang');
                    }
                },
                error: function() {
                    $("#transfer-message").text('Transfer gagal. Silakan coba lagi.').show();
                    $btn.prop('disabled', false).text('Transfer Sekarang');
                }
            });
        });

        $("#bulk-transfer-submit").off('click.endorseFallback').on('click.endorseFallback', function() {
            var ids = Object.keys(bulkTransferState.selectedItems);
            if (!ids.length || !bulkTransferState.targetCampaign || !bulkTransferState.targetCampaign.id) {
                return;
            }

            var $btn = $(this);
            $btn.prop('disabled', true).text('Memproses...');
            $("#bulk-transfer-message").hide().text('');

            $.ajax({
                type: 'POST',
                url: baseUrl + '/endorse/transfer-bulk-process',
                dataType: 'json',
                traditional: true,
                data: {
                    source_campaign: currentCampaignId,
                    target_campaign: bulkTransferState.targetCampaign.id,
                    id_endorse: ids
                },
                success: function(res) {
                    if (res && res.status) {
                        sessionStorage.setItem('endorseTransferSuccess', JSON.stringify({
                            message: res.message || (ids.length + ' konten berhasil ditransfer.'),
                            count: ids.length
                        }));

                        var params = new URLSearchParams(window.location.search);
                        params.set('id_campaign', currentCampaignId);
                        params.delete('ids');
                        window.location.href = endorseBaseUrl + '?' + params.toString();
                    } else {
                        $("#bulk-transfer-message").text((res && res.message) ? res.message : 'Transfer bulk gagal.').show();
                        updateBulkTransferSubmitState();
                    }
                },
                error: function() {
                    $("#bulk-transfer-message").text('Transfer bulk gagal. Silakan coba lagi.').show();
                    updateBulkTransferSubmitState();
                }
            });
        });

        if (typeof window.list_id_v2 === 'undefined') {
            window.list_id_v2 = '';
        }
    })();
</script>

<script>
    function getDefaultContentSortColumn() {
        const urlParams = new URLSearchParams(window.location.search);
        const topPerformerPeriod = urlParams.get('list_top_performer_period') || '';
        return topPerformerPeriod ? 'views_growth_period' : 'id';
    }

    function getDefaultContentSortOrder() {
        return 'DESC';
    }

    function loadMoreData() {
        const urlParams = new URLSearchParams(window.location.search);
        const sortColumn = urlParams.get('sort_column') || getDefaultContentSortColumn();
        const sortOrder = urlParams.get('sort_order') || getDefaultContentSortOrder();
        
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
            const currentSortColumn = urlParams.get('sort_column') || getDefaultContentSortColumn();
            const currentSortOrder = (urlParams.get('sort_order') || getDefaultContentSortOrder()).toLowerCase();
            
            const columnName = $(this).text().trim().toLowerCase();
            const columnMap = {
                'nama influencer': 'nama_creator',
                'pic': 'pic',
                'total cost': 'total_cost',
                'status': 'status_endorse',
                'tanggal posting': 'posting_at',
                'views': 'views',
                'cpm': 'cpm',
                'engagement': 'engagement',
                'growth views': 'views_growth_period'
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
            'engagement': 'Engagement',
            'views_growth_period': 'Growth Views'
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
