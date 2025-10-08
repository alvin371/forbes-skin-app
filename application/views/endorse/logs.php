<?php
$chart_title = "";
$site = $_GET['site'];
$customer = $_GET['customer'];
$date = $_GET['date'];

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

// Hitung selisih views dan sort data untuk mendapatkan 5 tertinggi
$data_with_diff = array();
foreach ($data as $k => $v) {
    $v['views_diff'] = $v['views_after'] - $v['views_before'];
    $v['engagement_before'] = $v['comment_before'] + $v['share_save_before'];
    $v['engagement_after'] = $v['comment_after'] + $v['share_save_after'];
    $v['engagement_diff'] = $v['engagement_after'] - $v['engagement_before'];
    $data_with_diff[] = $v;
}

// Sort berdasarkan selisih views tertinggi
usort($data_with_diff, function($a, $b) {
    return $b['views_diff'] - $a['views_diff'];
});

// Ambil 5 tertinggi
$top_5_data = array_slice($data_with_diff, 0, 5);
?>
<style>
    /* Select2 Styling */
    .select2-container .select2-selection--single {
        box-sizing: border-box;
        cursor: pointer;
        display: block;
        height: 32px;
        user-select: none;
        -webkit-user-select: none;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: rgba(0, 0, 0, 0.85);
        line-height: 32px;
        padding-left: 11px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 32px;
        position: absolute;
        top: 0px;
        right: 1px;
        width: 20px;
    }

    .select2 {
        height: 32px !important;
        min-width: 100% !important;
        margin-bottom: 8px;
    }

    /* Ant Design-like Table Styling */
    .table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid #f0f0f0;
        border-radius: 2px;
    }

    .table thead th {
        background-color: #fafafa;
        color: rgba(0, 0, 0, 0.85);
        font-weight: 500;
        text-align: left;
        padding: 12px 8px;
        font-size: 14px;
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.3s ease;
    }

    .table tbody td {
        padding: 12px 8px !important;
        font-size: 14px;
        color: rgba(0, 0, 0, 0.65);
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.3s ease;
    }

    .table-hover tbody tr:hover {
        background-color: #fafafa;
    }

    /* Card Styling - Ant Design-like */
    .card {
        border-radius: 2px;
        border: 1px solid #f0f0f0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.09);
        margin-bottom: 16px;
    }

    .card-header {
        background-color: #fff;
        border-bottom: 1px solid #f0f0f0;
        padding: 16px;
        height: 56px;
    }

    .card-body {
        padding: 16px;
    }

    /* Button Styling - Ant Design-like */
    .btn {
        border-radius: 2px;
        padding: 4px 15px;
        font-size: 14px;
        height: 32px;
        line-height: 1.5;
        transition: all 0.3s cubic-bezier(0.645, 0.045, 0.355, 1);
    }

    .btn-primary {
        background-color: #1890ff;
        border-color: #1890ff;
    }

    .btn-primary:hover {
        background-color: #40a9ff;
        border-color: #40a9ff;
    }

    .btn-outline-secondary {
        color: rgba(0, 0, 0, 0.65);
        border-color: #d9d9d9;
        background: #fff;
    }

    .btn-outline-secondary:hover {
        color: #40a9ff;
        border-color: #40a9ff;
    }

    /* Badge Styling - Ant Design-like */
    .badge {
        font-size: 12px;
        height: 22px;
        padding: 0 8px;
        line-height: 22px;
        border-radius: 2px;
        font-weight: normal;
    }

    /* Pagination Styling - Ant Design-like */
    .pagination {
        margin-top: 16px;
        justify-content: flex-end;
    }

    .page-item {
        margin-right: 8px;
    }

    .page-item:last-child {
        margin-right: 0;
    }

    .page-item.active .page-link {
        background-color: #1890ff;
        border-color: #1890ff;
    }

    .page-link {
        min-width: 32px;
        height: 32px;
        line-height: 30px;
        text-align: center;
        color: rgba(0, 0, 0, 0.65);
        border-radius: 2px;
        padding: 0;
        margin: 0;
        border: 1px solid #d9d9d9;
    }

    .page-link:hover {
        color: #40a9ff;
        border-color: #40a9ff;
    }

    /* Search Form Styling - Ant Design-like */
    .search-form {
        margin-bottom: 16px;
    }

    .search-form .input-group {
        border-radius: 2px;
        display: flex;
        justify-content: space-between;
    }

    .form-control {
        height: 32px;
        padding: 4px 11px;
        font-size: 14px;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
        transition: all 0.3s;
    }

    .form-control:hover {
        border-color: #40a9ff;
    }

    .form-control:focus {
        border-color: #40a9ff;
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
    }

    .form-select {
        height: 32px;
        padding: 4px 11px;
        font-size: 14px;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
        transition: all 0.3s;
    }

    .form-select:hover {
        border-color: #40a9ff;
    }

    .form-select:focus {
        border-color: #40a9ff;
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
    }

    /* Alert Styling - Ant Design-like */
    .alert {
        padding: 8px 15px;
        border-radius: 2px;
        font-size: 14px;
    }

    .alert-info {
        background-color: #e6f7ff;
        border-color: #91d5ff;
        color: rgba(0, 0, 0, 0.65);
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .table-responsive {
            border-radius: 2px;
            overflow: hidden;
        }
    }

    #searchInput, 
    .input-group-text {
        height: 40px; /* samain tinggi */
    }

</style>
<div class="w-100">
    <!-- Top 5 Performance Section -->
    <!-- <div class="col-lg-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Top 5 Content dengan Kenaikan Views Tertinggi</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Campaign</th>
                                <th>Influencer</th>
                                <th>Views Awal</th>
                                <th>Views Akhir</th>
                                <th>Selisih Views</th>
                                <th>% Kenaikan</th>
                                <th>Engagement Awal</th>
                                <th>Engagement Akhir</th>
                                <th>Selisih Engagement</th>
                                <th>Link</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_5_data as $k => $v) {
                                $this->db->select('title');
                                $campaign = $this->mymodel->selectDataOne('endorse_campaign', array('id' => $v['id_campaign']));
                                $this->db->select('username');
                                $influencer = $this->mymodel->selectDataOne('influencer', array('id' => $v['influencer']));
                                
                                $percentage_increase = $v['views_before'] > 0 ? (($v['views_diff'] / $v['views_before']) * 100) : 0;
                                $campaign_link = '<a href="' . base_url() . 'endorse?id_campaign=' . $v['id_campaign'] . '&ids=' . $v['id_endorse'] . '" target="_blank">' . $campaign['title'] . '</a>';
                                $content_link = '<a href="' . $v['link_upload'] . '" target="_blank" class="btn btn-sm btn-outline-primary">View</a>';
                            ?>
                                <tr class="<?= $k == 0 ? 'table-warning' : '' ?>">
                                    <td class="text-center">
                                        <?php if($k == 0): ?>
                                            <span class="badge bg-warning text-dark fs-6">🏆 #<?= $k + 1 ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-success">#<?= $k + 1 ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $campaign_link ?></td>
                                    <td><?= $influencer['username'] ?></td>
                                    <td class="text-end"><?= $this->template->separator_only($v['views_before']) ?></td>
                                    <td class="text-end"><?= $this->template->separator_only($v['views_after']) ?></td>
                                    <td class="text-end">
                                        <span class="badge bg-primary fs-6">+<?= $this->template->separator_only($v['views_diff']) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <span class="text-success fw-bold">+<?= number_format($percentage_increase, 1) ?>%</span>
                                    </td>
                                    <td class="text-end"><?= $this->template->separator_only($v['engagement_before']) ?></td>
                                    <td class="text-end"><?= $this->template->separator_only($v['engagement_after']) ?></td>
                                    <td class="text-end">
                                        <span class="badge <?= $v['engagement_diff'] > 0 ? 'bg-success' : 'bg-danger' ?> fs-6">
                                            <?= $v['engagement_diff'] > 0 ? '+' : '' ?><?= $this->template->separator_only($v['engagement_diff']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center"><?= $content_link ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div> -->
    <!-- Complete Data Table -->
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    Endorse Logs <?= date('d-m-Y', strtotime($_GET['date'])) ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="col-lg-12 mb-3">
                    <form action="<?= $url ?>" method="GET">
                        <input type="hidden" name="ids" value="<?= $ids ?>">
                        <input type="hidden" name="id_campaign" value="<?= $detail['id'] ?>">
                        <input type="hidden" name="date" value="<?= $date ?>">
                        <div class="row">

                            <div class="col-lg-4">
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
                                    border-bottom-left-radius: 0px !important;width:400px!important">
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <button class="btn btn-edit-active" type="submit"><i class="bi bi-search fs-16"></i> Cari Data</button>
                            </div>

                        </div>
                    </form>
                </div>
                
                <div id="customInfo" class="alert alert-info d-flex align-items-center mb-3">
                    <i class="bi bi-info-circle me-2"></i>
                    <span>Menampilkan semua data</span>
                </div>

                <div id="tbody">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="endorse-table">
                            <thead>
                                <tr class="text-white">
                                    <th>#</th>
                                    <th>Aksi</th>
                                    <th class="text-start">Campaign</th>
                                    <th class="text-start">Influencer</th>
                                    <th class="">Cost</th>
                                    <th class="">CPM</th>
                                    <th class="">Views Awal</th>
                                    <th class="">Views Akhir</th>
                                    <th class="">Selisih Views</th>
                                    <th class="">% Kenaikan Views</th> 
                                    <th class="">Engagement Awal</th>
                                    <th class="">Engagement Akhir</th>
                                    <th class="">Selisih Engagement</th>
                                    <th class="text-start">Link Upload</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data_with_diff as $k => $v) {
                                    $this->db->select('title');
                                    $campaign = $this->mymodel->selectDataOne('endorse_campaign', array('id' => $v['id_campaign']));
                                    $this->db->select('username');
                                    $influencer = $this->mymodel->selectDataOne('influencer', array('id' => $v['influencer']));

                                    $v['link_upload'] = '<a href="' . $v['link_upload'] . '" target="_blank">' . $v['link_upload'] . '</a>';
                                    $campaign['title'] = '<a href="' . base_url() . 'endorse?id_campaign=' . $v['id_campaign'] . '&ids=' . $v['id_endorse'] . '" target="_blank">' . $campaign['title'] . '</a>';

                                    $percentage_increase = $v['views_before'] > 0 ? (($v['views_diff'] / $v['views_before']) * 100) : 0;
                                ?>
                                    <tr>
                                        <td><?= $k + 1 ?></td>
                                        <td style="padding-top:12px!important">
                                            <a href="#!" onclick="remove('<?= $v['id'] ?>')" class="mt-0 text-red">
                                                <i class="bi bi-trash text-icon"></i>
                                            </a>
                                        </td>
                                        <td class="text-start"><?= $campaign['title'] ?></td>
                                        <td class="text-start"><?= $influencer['username'] ?></td>
                                        <td class="text-start" data-order="<?= $v['total_cost'] ?>"><?= $this->template->separator_only($v['total_cost']) ?></td>
                                        <td class="text-start" data-order="<?= $v['cpm_after'] ?>"><?= $this->template->separator_only($v['cpm_after']) ?></td>
                                        <td class="text-end" data-order="<?= $v['views_before'] ?>"><?= $this->template->separator_only($v['views_before']) ?></td>
                                        <td class="text-end" data-order="<?= $v['views_after'] ?>"><?= $this->template->separator_only($v['views_after']) ?></td>
                                        <td class="text-end" data-order="<?= $v['views_diff'] ?>">
                                            <span class="<?= $v['views_diff'] > 0 ? 'text-success' : 'text-danger' ?> fw-bold">
                                                <?= $v['views_diff'] > 0 ? '+' : '' ?><?= $this->template->separator_only($v['views_diff']) ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="<?= $percentage_increase >= 0 ? 'text-success fw-bold' : 'text-danger fw-bold' ?>">
                                                <?= $percentage_increase >= 0 ? '+' : '' ?><?= number_format($percentage_increase, 1) ?>%
                                            </span>
                                        </td>
                                        <td class="text-end" data-order="<?= $v['engagement_before'] ?>"><?= $this->template->separator_only($v['engagement_before']) ?></td>
                                        <td class="text-end" data-order="<?= $v['engagement_after'] ?>"><?= $this->template->separator_only($v['engagement_after']) ?></td>
                                        <td class="text-end" data-order="<?= $v['engagement_diff'] ?>">
                                            <span class="<?= $v['engagement_diff'] > 0 ? 'text-success' : 'text-danger' ?> fw-bold">
                                                <?= $v['engagement_diff'] > 0 ? '+' : '' ?><?= $this->template->separator_only($v['engagement_diff']) ?>
                                            </span>
                                        </td>
                                        <td class="text-start"><?= $v['link_upload'] ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>

                    </div>
                </div>
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

</style>

<script>
    function remove(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Hapus Data');
        $("#load-form").load("<?= base_url() ?>endorse/remove_logs?id=" + id);
    }

    $(document).ready(function() {
        var table = $('#endorse-table').DataTable({
            searching: false,
            paging: false,
            ordering: true,
            info: false,       
            lengthChange: false
        });

        $('#searchInput').on('keyup', function () {
            table.search(this.value).draw();
        });

        // ✅ Update custom info
        function updateInfo() {
            var info = table.page.info();
            var total = info.recordsDisplay; // jumlah setelah filter
            var total_all = info.recordsTotal; // total semua
            var notifText = "Menampilkan " + total + " dari " + total_all + " data";
            $("#customInfo span").text(notifText);
        }

        // update pertama kali load
        updateInfo();

        // update setiap kali datatable di-draw
        table.on('draw.dt', function () {
            updateInfo();
        });
    });



    
</script>