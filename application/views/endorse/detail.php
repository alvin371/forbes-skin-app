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
?>

<?php $v = $detail;
function separator_only($angka)
{
    $number = doubleval($angka);
    return number_format(round($number), 0, ',', '.');
}
function date_format_indo($date)
{
    $month = array(
        '',
        'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    );
    if ($date) {
        $date = DATE('d', strtotime($date)) . ' ' . $month[intval(DATE('m', strtotime($date)))] . ' ' . DATE('Y', strtotime($date));
    } else {
        $date = '-';
    }
    return $date;
}

if ($v['platform'] == "Tiktok") {
    $v['img'] = base_url() . '/assets/img/icon/icon-tiktok.png';
} else if ($v['platform'] == "Instagram") {
    $v['img'] = base_url() . '/assets/img/icon/icon-ig.png';
} else if ($v['platform'] == "Youtube") {
    $v['img'] = base_url() . '/assets/img/icon/icon-youtube.png';
} else if ($v['platform'] == "Facebook") {
    $v['img'] = base_url() . '/assets/img/icon/icon-fb.png';
} else if ($v['platform'] == "Twitter") {
    $v['img'] = base_url() . '/assets/img/icon/icon-twitter.png';
} else {
    $v['img'] = base_url() . '/assets/img/icon/icon-no.png';
}
if ($v['nama_creator'] == "") {
    $v['nama_creator'] = "-";
}
if ($v['posting_at']) {
    $v['posting_at'] = DATE("d/m/Y", strtotime($v['posting_at']));
} else {
    $v['posting_at'] = '-';
}
if ($v['rencana_at']) {
    $v['rencana_at'] = DATE("d/m/Y", strtotime($v['rencana_at']));
} else {
    $v['rencana_at'] = '-';
}
if ($v['barang_dikirim_at']) {
    $v['barang_dikirim_at'] = DATE("d/m/Y", strtotime($v['barang_dikirim_at']));
} else {
    $v['barang_dikirim_at'] = '-';
}
if ($v['sync_at']) {
    $v['sync_at'] = DATE("d/m/Y", strtotime($v['sync_at']));
} else {
    $v['sync_at'] = '-';
}
if ($v['created_at']) {
    $v['created_at'] = DATE("d/m/Y", strtotime($v['created_at']));
} else {
    $v['created_at'] = '-';
}
if ($v['link_brief']) {
    $v['link_brief'] = '<a href="' . $v['link_brief'] . '" target="_blank">' . $v['link_brief'] . '</a>';
} else {
    $v['link_brief'] = '-';
}
if ($v['link_mou']) {
    $v['link_mou'] = '<a href="' . $v['link_mou'] . '" target="_blank">' . $v['link_mou'] . '</a>';
} else {
    $v['link_mou'] = '-';
}
if ($v['link_upload']) {
    $v['img'] = '<a href="' . $v['link_upload'] . '" target="_blank"><img style="width:40px;border-radius:10px;" class="mt-0" src="' . $v['img'] . '"></a>';
} else {
    $v['img'] = '<img style="width:40px;border-radius:10px; filter: grayscale(100%)!important;" class="mt-0" src="' . $v['img'] . '">';
}
$bg = '#000';
$clr = '#FFF';
if (in_array($v['status_endorse'], array('6. Posted Content'))) {
    $bg = '#4caf50';
    $clr = '#FFF';
} else if (in_array($v['status_endorse'], array('3. Acc & Payment'))) {
    $bg = '#3475b5';
    $clr = '#FFF';
}
?>
<div class="w-100">
    <div class="row align-items-center">
        <div class="col-lg-12 mb-3">
            <h3 class="text-primary fw-600">DETAIL ENDORSE</h3>
        </div>
    </div>


    <div class="card mb-3" style="padding-bottom:0px">
        <div class="row">
            <div class="col-lg-8">
                <a class="mb-1 text-blue fw-700 fs-16 a-none" href="<?= base_url() ?>/endorse/detail?id=<?= $v['id'] ?>">#<?= $k + 1 ?></a>
                <p class="mb-1 text-black fw-700 fs-16"><?= $v['nama_creator'] ?></p>
                <p class="mb-1 text-black">PIC : <?= $v['pic'] ?></p>
                <p class="mb-1" style="margin-top:6px!important"><span class="br-10 fs-12 text-white" style="background-color:<?= $bg ?>;color:<?= $clr ?>"><?= strtoupper(strtolower($v['status_endorse'])) ?></span></p>
            </div>
            <div class="col-lg-4 text-end">
                <a href="#!" onclick="remove('<?= $v['id'] ?>')" class="btn btn-delete  mt-0 mb-2"><i class="bi bi-trash fs-16"></i> Delete Data</a>
                <a href="#!" onclick="sync('<?= $v['id'] ?>')" class="btn btn-sync ms-1 mt-0 mb-2"><i class="bi bi-bootstrap-reboot fs-16"></i> Refresh</a>
                <a href="#!" onclick="edit('<?= $v['id'] ?>')" class="btn btn-edit  mt-0 ms-1 mb-2"><i class="bi bi-pencil-square fs-16"></i> Edit Data</a>
            </div>
            <div class="col-lg-12">
                <hr>
            </div>
            <div class="col-lg-12">
                <div class="row">
                    <div class="col-md-4">
                        <p class="mb-1 text-black">Total Cost : <?= separator_only($v['total_cost']) ?></p>
                        <p class="mb-1 text-black">Barang Dikirim : <?= $v['barang_dikirim_at'] ?></p>
                        <p class="mb-1 text-black">Rencana Upload : <?= $v['rencana_at'] ?></p>
                        <p class="mb-1 text-black">Tanggal Posting : <?= $v['posting_at'] ?></p>
                        <p class="mb-1 text-black">Status : <?= $v['status'] ?></p>
                        <p class="mb-1">Produk : <?= $v['product_text'] ?></p>
                        <p class="mb-1">Ket : <?= $v['desc'] ?></p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1 text-black fw-600">Views : <?= separator_only($v['views']) ?></p>
                        <p class="mb-1 text-black fw-600">CPM : <?= separator_only($v['cpm']) ?></p>
                        <p class="mb-1 text-black">Likes : <?= separator_only($v['likes']) ?></p>
                        <p class="mb-1 text-black">Comments : <?= separator_only($v['comment']) ?></p>
                        <p class="mb-1 text-black">Save & Share : <?= separator_only($v['share_save']) ?></p>
                        <p class="mb-1 text-black">Tanggal Dibuat : <?= $v['created_at'] ?></p>
                        <p class="mb-1 text-black">Tanggal Diupdate : <?= $v['sync_at'] ?></p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1 text-black">Link Brief : <br><?= $v['link_brief'] ?></p>
                        <p class="mb-1 text-black">Link MOU : <br><?= $v['link_mou'] ?></p>
                    </div>
                    <div class="col-lg-12 mt-3">


                        <div class="row">
                            <div class="col-12 mb-2" style="position:relative">
                                <div class="row">
                                    <div class="firstDivImg">
                                        <?= $v['img'] ?>
                                    </div>
                                    <div class="secondDivImg" style="margin-top: -10px;margin-left:-20px;">
                                        <a href="#!" onclick="change_fyp_<?= $k ?>()" id="fyp_<?= $k ?>" class="">
                                            <?php if ($v['is_fyp'] == 1) { ?>
                                                <i class="bi bi-star-fill" style="font-size:40px;color:#ffd250"></i>
                                            <?php } else { ?>
                                                <i class="bi bi-star" style="font-size:40px;color:#000"></i>
                                            <?php } ?>
                                            <script>
                                                function change_fyp_<?= $k ?>() {
                                                    $('#fyp_<?= $k ?>').html('<i style="margin-top: 10px;margin-bottom:10px;font-size:40px;color:#000" class="fa fa-circle-o-notch fa-spin"></i>');
                                                    $.ajax({
                                                        dataType: "json",
                                                        url: '<?= base_url() ?>/ajax/change-fyp?id=<?= $v['id'] ?>',
                                                        success: function(html) {
                                                            $('#fyp_<?= $k ?>').html(html.html);
                                                            <?php $v['code'] =  'mar-5' ?>
                                                            $.ajax({
                                                                dataType: "json",
                                                                url: '<?= base_url() ?>/ajax/get-summary-campaign<?= $param ?>&id=<?= $v['code'] ?>&id_campaign=<?= $v['id_campaign'] ?>',
                                                                success: function(html) {
                                                                    $("#summary-<?= $v['code'] ?>").html(html.html);
                                                                }
                                                            });

                                                        }
                                                    });
                                                }
                                            </script>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>



    <div class="row">
        <?php
        $sum = array();
        $i = 0;
        $sum[$i]['code'] = 'mar-3';
        $sum[$i]['img'] = "bi bi-cursor";
        $sum[$i]['color_box'] = "#60BB551A";
        $sum[$i]['color_icon'] = "#60bb55";
        $sum[$i]['title'] = "CPM";
        $sum[$i]['value'] = $v['cpm'];
        $i++;
        $sum[$i]['code'] = 'mar-6';
        $sum[$i]['img'] = "bi bi-eye";
        $sum[$i]['color_box'] = "#60BB551A";
        $sum[$i]['color_icon'] = "#60bb55";
        $sum[$i]['title'] = "VIEWS";
        $sum[$i]['value'] = $v['views'];
        $i++;
        $sum[$i]['code'] = 'mar-7';
        $sum[$i]['img'] = "bi bi-heart";
        $sum[$i]['color_box'] = "#60BB551A";
        $sum[$i]['color_icon'] = "#60bb55";
        $sum[$i]['title'] = "LIKES";
        $sum[$i]['value'] = $v['likes'];
        $i++;
        $sum[$i]['code'] = 'mar-8';
        $sum[$i]['img'] = "bi bi-chat-quote";
        $sum[$i]['color_box'] = "#60BB551A";
        $sum[$i]['color_icon'] = "#60bb55";
        $sum[$i]['title'] = "COMMENTS";
        $sum[$i]['value'] = $v['comment'];
        $i++;
        ?>
        <?php foreach ($sum as $ki => $vi) { ?>
            <div class="text-start mb-4 col-md-3">
                <div class="card">
                    <a class="a-unset" href="">
                        <div class="row">
                            <div class="col-12" style="position:relative">
                                <div class="row">
                                    <div class="firstDiv">
                                        <div class="firstCircle">
                                            <div class="box-icon mb-2 text-center" style="background-color:<?= $vi['color_box'] ?>;">
                                                <i class="<?= $vi['img'] ?>" style="color:<?= $vi['color_icon'] ?>"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="secondDiv">
                                        <p class="fw-500 mb-1 text-end"><?= $vi['title'] ?></p>
                                        <h4 class="fw-500 mb-1 text-end" id="summary-<?= $vi['code'] ?>"><?= $template->separator_only($vi['value']) ?></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        <?php } ?>
    </div>
</div>
<div class="col-lg-12 mb-3">
    <div class="card summary">
        <h3 class="text-primary fw-600 mb-1">Grafik Endorse</h3>
        <p class="mb-2"><?= $title_2 ?></p>
        <form action="<?= $url ?>" method="GET">
            <input type="hidden" name="id" value="<?= $_GET['id'] ?>">
            <div class="row">
                <div class="col-md-2">
                    <div class="row">
                        <div class="col-md-12">
                            <?php
                            $arr = [];
                            $arr[] = "Daily";
                            $arr[] = "Weekly";
                            $arr[] = "Monthly";
                            $arr[] = "Yearly";
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
                    </div>
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control" id="tanggal" placeholder="Pilih rentang tanggal...">
                    <input type="hidden" name="start_date" id="start_date" value="<?= $_GET['start_date'] ?? $start_date ?>">
                    <input type="hidden" name="until_date" id="end_date" value="<?= $_GET['until_date'] ?? $until_date ?>">
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
                    foreach ($arr as $k => $v) {
                        $text = "";
                        if ($checkbox[$k] == 'true') {
                            $text = "checked";
                        }
                        if (empty($checkbox)) {
                            $text = "checked";
                        }
                    ?>
                        <input onclick="checkbox()" <?= $text ?> type="checkbox" id="c-<?= $k ?>" class="me-1 c-checkbox"><label for="c-<?= $k ?>" class="fw-400 me-2"><?= $v ?></label>
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
                    url: '<?= base_url() ?>/ajax/get-chart-endorse?id=<?= $detail['id'] ?>&type=<?= $type ?>&start_date=<?= $start_date ?>&until_date=<?= $until_date ?>&start_year=<?= $start_year ?>&until_year=<?= $until_year ?>&start_month=<?= $start_month ?>&until_month=<?= $until_month ?>&start_week=<?= $start_week ?>&until_week=<?= $until_week ?>',
                    success: function(html) {
                        $("#summary-chart").html(html.html);
                        $("#summary-table").html(html.table);
                    }
                });
            }

            function checkbox() {
                var checkboxStatus = {};
                var i = 0;
                $(".c-checkbox").each(function() {
                    var isChecked = $(this).prop("checked");
                    checkboxStatus[i] = isChecked;
                    i++;
                });

                var queryParams = $.param(checkboxStatus);
                console.log(queryParams);
                $.ajax({
                    type: "GET",
                    dataType: "json",
                    url: '<?= base_url() ?>/ajax/checkbox?' + queryParams,
                    success: function(response) {
                        get_chart();
                    }
                });
            }
        </script>
    </div>
</div>

<?= $notif ?>
<div class="col-lg-12">
    <div class="col-lg-12">
        <div id="tbody">
        </div>
    </div>

    <?= $pagination ?>
</div>

<div class="modal fade bd-example-modal-lg" tabindex="-1" varietas="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="modal-form">
    <div class="modal-dialog modal-lg">
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
    function create(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Tambah Data');
        $("#load-form").load("<?= base_url() ?>/endorse/create?id=" + id);
    }

    function remove(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Hapus Data');
        $("#load-form").load("<?= base_url() ?>/endorse/remove?id=" + id);
    }

    function edit(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Edit Data');
        $("#load-form").load("<?= base_url() ?>/endorse/edit?id=" + id);
    }

    function set_payment(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Ajukan Payment');
        $("#load-form").load("<?= base_url() ?>/endorse/ajukan_payment?id=" + id);
    }

    function sync_all(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Refresh Data');
        $("#load-form").load("<?= base_url() ?>/endorse/sync_all?id=" + id);
    }

    function sync(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Refresh Data');
        $("#load-form").load("<?= base_url() ?>/endorse/sync?id=" + id);
    }

    function show_chart(id) {
        $('#chart-' + id).html('<i class="fa fa-circle-o-notch fa-spin"></i> Memuat data ...')
        $.ajax({
            dataType: "json",
            url: '<?= base_url() ?>/ajax/get-chart-endorse?id=' + id + '&type=<?= $type ?>&start_date=<?= $start_date ?>&until_date=<?= $until_date ?>&start_year=<?= $start_year ?>&until_year=<?= $until_year ?>&start_month=<?= $start_month ?>&until_month=<?= $until_month ?>&start_week=<?= $start_week ?>&until_week=<?= $until_week ?>',
            success: function(html) {
                $("#chart-'" + id).html(html.html);
                $("#table-").html(html.table);
            }
        });
    }
</script>