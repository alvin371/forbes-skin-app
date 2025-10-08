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
<div class="w-100">
    <div class="row align-items-center">
        <div class="col-lg-12 mb-3">
            <h3 class="text-primary fw-600">PAYMENT LOGS</h3>
            <h3 class="text-primary fw-600"><?= $_GET['date'] ?></h3>
        </div>
        <div class="col-lg-12 mb-0">
        </div>
    </div>

    <div class="col-lg-12">
        <div class="col-lg-12">
            <!-- <div id="tbody">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered" id="datatable-full">
                        <thead>
                            <tr class="bg-primary">
                                <th>#</th>
                                <th>Aksi</th>
                                <th class="text-start">Campaign</th>
                                <th class="text-start">Influencer</th>
                                <th class="">Status Payment</th>
                                <th class="">Cost</th>
                                <th class="">Nominal Dibayarkan</th>
                                <th class="">Tanggal</th>
                                <th class="">Link Bukti TF</th>
                                <th class="text-start">PIC</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $k => $v) { ?>
                                <tr>
                                    <td><?= $k + 1 ?></td>
                                    <td style="padding-top:12px!important">
                                        <a href="#!" onclick="remove('<?= $v['id'] ?>')" class="mt-0 text-red">
                                            <i class="bi bi-trash text-icon"></i>
                                        </a>
                                    </td>
                                    <td class="text-start"><?= $v['code'] ?></td>
                                    <td class="text-start"><?= $v['nama_influencer'] ?></td>
                                    <td class="text-start"><?= $v['status_payment'] ?></td>
                                    <td class="text-start"><?= number_format($v['total_cost'], 0, ',', '.') ?></td>
                                    <td class="text-start"><?= number_format($v['nominal_dibayarkan'], 0, ',', '.') ?></td>
                                    <td class="text-start"><?= date('d M Y', strtotime($v['created_at'])) ?></td>
                                    <td class="text-start">
                                        <?php if (!empty($v['bukti_tf'])) { ?>
                                            <a href="<?= base_url('uploads/bukti_tf/' . $v['bukti_tf']) ?>" target="_blank">Lihat Bukti</a>
                                        <?php } else { ?>
                                            Tidak Ada Bukti
                                        <?php } ?>
                                    </td>
                                    <td class="text-start">
                                        <a href="<?= $v['link_tele'] ?>" target="_blank"><?= $v['link_tele'] ?></a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div> -->

            <div class="col-md-12 mb-3 mt-0">
                <hr>
                <?php if (!empty($data)) { ?>
                    <?php foreach ($data as $index => $log) { ?>
                        <p class="mb-0" style="font-size:16px">
                            #<?= $index + 1 ?> <br>
                            <b><?= $log['code'] ?> </b>mengubah status menjadi <?= $log['status_payment']  ?> <br>
                            Nominal Dibayarkan: <b>Rp <?= number_format($log['nominal_dibayarkan'], 0, ',', '.') ?></b> <br>
                            <?php if (!empty($log['link_tele'])) { ?>
                                <a href="<?= $log['link_tele'] ?>" target="_blank">Bukti Transfer</a>
                            <?php } ?>
                            <hr>
                        </p>
                    <?php } ?>
                <?php } else { ?>
                    <i>Belum ada logs.</i>
                <?php } ?>
            </div>
        </div>
    </div>
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
    function remove(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Hapus Data');
        $("#load-form").load("<?= base_url() ?>endorse/remove_logs?id=" + id);
    }
</script>