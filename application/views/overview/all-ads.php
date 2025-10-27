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
    $start_date = DATE('Y-m-d', strtotime($today . " -7 days"));
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

$start_date_formatted = DateTime::createFromFormat('Y-m-d', $start_date)->format('d M Y');
$until_date_formatted = DateTime::createFromFormat('Y-m-d', $until_date)->format('d M Y');

$title_ads = $start_date_formatted . ' - ' . $until_date_formatted;


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

$current_tax = $tax[0]['tax'];
?>

<div class="w-100">
    <div class="row align-items-center">
        <?php $this->load->view('overview/menu') ?>
        <div class="col-lg-12 mb-3">
            <div class="row">
                <div class="col-md-12">
                    <h3 class="text-primary fw-500">DASHBOARD OVERVIEW ACNENO SYSTEM</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-12">
            <form action="<?= $url ?>?t=ads" method="GET">
                <div class="row align-items-center">
                    <input type="hidden" name="t" value="ads">
                    <!-- Filter Brand -->
                    <div class="col-md-3">
                        <select class="form-control select2" name="brand" id="brand">
                            <option value="">Brand</option>
                            <?php foreach ($brands as $val) :
                                $text = ($_GET["brand"] == $val["code"]) ? "selected" : "";
                            ?>
                                <option <?= $text ?> value="<?= $val["code"] ?>"><?= $val["code"] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <input type="text" class="form-control" id="tanggal" placeholder="Pilih rentang tanggal...">
                        <input type="hidden" name="start_date" id="start_date" value="<?= $_GET['start_date'] ?? $start_date ?>">
                        <input type="hidden" name="until_date" id="end_date" value="<?= $_GET['until_date'] ?? $until_date ?>">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100 form-control" type="submit"><i class="bi bi-search fs-16"></i> Cari Data</button>
                    </div>
                </div>
            </form>
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

        <div class="row">
            <?php
            $gmv = array_sum(array_column($spend, 'spend_idr'));
            $gmv_after_tax = array_sum(array_column($spend, 'spend_idr_after_tax'));
            $total_spend = array_sum(array_column($pivot, 'total_spend'));
            $total_spend_arr = array_column($pivot, 'total_spend');
            $total_spend_after_tax = array_sum(array_map(function ($spend) use ($current_tax) {
                return $spend + ($spend * $current_tax);
            }, $total_spend_arr));
            $total_omset = array_sum(array_column($pivot, 'result'));
            $total_ratio = array_sum(array_column($pivot, 'ratio'));
            ?>
            <div class="col-md-4 mb-3">
                <div class="card p-36 shadow-sm" style="border-radius: 12px; border: solid 2px #8174A0; height: 15vh; padding: 20px;">
                    <div class="d-flex justify-content-between align-items-center">
                        <!-- Left Section -->
                        <div>
                            <h6 class="text-muted" style="font-size: 0.9rem; font-weight: 600; margin-bottom: 8px;">
                                Total Omset
                            </h6>
                            <h5 style="font-size: 1.6rem; font-weight: 700; margin-bottom: 8px; color: #172b4d;">
                                <?= 'Rp ' . number_format($total_omset, 0, ',', '.') ?>
                            </h5>

                        </div>

                        <!-- Right Section -->
                        <div>
                            <div class="icon-container" style="
                                                                            width: 50px;
                                                                            height: 50px;
                                                                            background-color: #8174A0;
                                                                            border-radius: 50%;
                                                                            display: flex;
                                                                            align-items: center;
                                                                            justify-content: center;
                                                                        ">
                                <i class="bi bi-cash text-white" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="card p-36 shadow-sm" style="border-radius: 12px; border: solid 2px #ff4d6d; height: 15vh; padding: 20px;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted" style="font-size: 0.9rem; font-weight: 600; margin-bottom: 8px;">
                                Total Biaya Iklan + Pajak
                            </h6>
                            <h5 style="font-size: 1.6rem; font-weight: 700; margin-bottom: 8px; color: #172b4d;">
                                <p class="card-text"><?= 'Rp ' . number_format($total_spend, 0, ',', '.') ?></p>
                            </h5>

                        </div>
                        <div>
                            <div class="icon-container" style="
                                                                            width: 50px;
                                                                            height: 50px;
                                                                            background-color: #ff4d6d;
                                                                            border-radius: 50%;
                                                                            display: flex;
                                                                            align-items: center;
                                                                            justify-content: center;
                                                                        ">
                                <i class="bi bi-people text-white" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card p-36 shadow-sm" style="border-radius: 12px; border: solid 2px #62825D; height: 15vh; padding: 20px;">
                    <div class="d-flex justify-content-between align-items-center">
                        <!-- Left Section -->
                        <div>
                            <h6 class="text-muted" style="font-size: 0.9rem; font-weight: 600; margin-bottom: 8px;">
                                Total Ratio
                            </h6>
                            <h5 style="font-size: 1.6rem; font-weight: 700; margin-bottom: 8px; color: #172b4d;">
                                <p class="card-text">
                                    <?php
                                    if ($total_omset != 0) {
                                        $all_spend = $total_spend + $gmv;
                                        $total_ratio = $total_spend / $total_omset * 100;
                                        echo number_format($total_ratio, 2, ',', '.') . '%';
                                    } else {
                                        echo "N/A";
                                    }
                                    ?>
                                </p>
                            </h5>

                        </div>

                        <!-- Right Section -->
                        <div>
                            <div class="icon-container" style="
                                                                            width: 50px;
                                                                            height: 50px;
                                                                            background-color: #62825D;
                                                                            border-radius: 50%;
                                                                            display: flex;
                                                                            align-items: center;
                                                                            justify-content: center;
                                                                        ">
                                <i class="bi bi-wallet2 text-white" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <?php
            $tiktok_spend = array_sum(array_column($pivot, 'tiktok_spend'));
            $meta_spend = array_sum(array_column($pivot, 'meta_spend'));
            $shopee_spend = array_sum(array_column($pivot, 'shopee_spend'));
            $gmv = array_sum(array_column($pivot, 'tiktok_gmv'));
            ?>

            <div class="col-md-3 mb-1">
                <div class="card p-36 shadow-sm" style="border-radius: 12px; border: 2px solid #010101;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="p-2">
                            <h6 class="text-muted" style="font-size: 0.9rem; font-weight: 600; margin-bottom: 8px;">
                                Tiktok Spend
                            </h6>
                            <h3 style="font-size: 1.3rem; font-weight: 700; margin-bottom: 8px; color: #172b4d;">
                                <p class="card-text text-dark"><?= 'Rp ' . number_format($tiktok_spend + $gmv, 0, ',', '.') ?></p>
                            </h3>
                        </div>
                        <div>
                            <i class="icon">
                                <img src="<?= base_url() ?>assets/img/marketplace/3.png" alt="TikTok" class="rounded-circle border" style="width: 40px; height: 40px; border-color: #007bff;">
                            </i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-1">
                <div class="card p-36 shadow-sm" style="border-radius: 12px; border: 2px solid #EE4D2D;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="p-2">
                            <h6 class="text-muted" style="font-size: 0.9rem; font-weight: 600; margin-bottom: 8px;">
                                Shopee Spend
                            </h6>
                            <h3 class="text-dark" style="font-size: 1.3rem; font-weight: 700; margin-bottom: 8px; color: #172b4d;">
                                <?= 'Rp ' . number_format($shopee_spend, 0, ',', '.') ?>
                            </h3>
                        </div>
                        <div>
                            <i class="icon">
                                <img src="<?= base_url() ?>assets/img/marketplace/1.png" alt="Shopee" class="rounded-circle border" style="width: 40px; height: 40px; border-color: #f1582b;">
                            </i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-1">
                <div class="card p-36 shadow-sm" style="border-radius: 12px; border: 2px solid #0064e0;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="p-2">
                            <h6 class="text-muted" style="font-size: 0.9rem; font-weight: 600; margin-bottom: 8px;">
                                Meta Spend
                            </h6>
                            <h3 style="font-size: 1.3rem; font-weight: 700; margin-bottom: 8px; color: #172b4d;">
                                <p class="card-text text-dark"><?= 'Rp ' . number_format($meta_spend, 0, ',', '.') ?></p>
                            </h3>
                        </div>
                        <div>
                            <i class="icon">
                                <img src="<?= base_url() ?>assets/img/marketplace/5.png" alt="Meta" class="rounded-circle border" style="width: 40px; height: 40px; border-color: #4267B2;">
                            </i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-1">
                <div class="card p-36 shadow-sm" style="border-radius: 12px; border: 2px solid #000083;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="p-2">
                            <h6 class="text-muted" style="font-size: 0.9rem; font-weight: 600; margin-bottom: 8px;">
                                Lazada Spend
                            </h6>
                            <h3 class="text-dark" style="font-size: 1.2rem; font-weight: 700; margin-bottom: 8px; color: #172b4d;">
                                <?= 'Rp 0 ' ?>
                            </h3>
                        </div>
                        <i class="icon">
                            <img src="<?= base_url() ?>assets/img/marketplace/2.png" alt="Lazada" class="rounded-circle border" style="width: 40px; height: 40px; border-color: #e2231a;">
                        </i>
                    </div>
                </div>
            </div>
        </div>


        <div class="col-lg-12 mb-3">
            <div class="card h-100 mb-2">
                <h4 class="text-primary fw-500 mb-1">GRAFIK ADS</h4>
                <p class="mb-2"><?= $title_ads ?></p>
                <div class="col-md-12">
                    <div class="d-grid d-md-flex d-lg-flex">
                        <?php
                        $arr = array("Spend", "Omset", "Ratio", "Penjualan", "Avg. Penjualan");
                        foreach ($arr as $k => $v) {
                            $text = ($v === "Spend" || $v === "Omset") ? "checked" : "";
                        ?>
                            <input onclick="updateDisplay()" <?= $text ?> type="checkbox" id="c-<?= $k ?>" class="me-2 c-checkbox" onchange="limitCheckboxSelection()">
                            <label for="c-<?= $k ?>" class="fw-400 me-2"><?= $v ?></label>
                        <?php } ?>
                    </div>
                </div>

                <div id="get-chart">
                    <canvas id="orderChart" width="800" height="300"></canvas>
                    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                    <script>
                        const pivotData = <?php echo json_encode($pivot); ?>;
                        const spend = <?php echo json_encode($spend); ?>;
                        const current_tax = <?php echo json_encode($current_tax); ?>;

                        const checkboxMapping = ["Spend", "Omset", "Ratio", "Penjualan", "Avg. Penjualan"];

                        const ctx = document.getElementById('orderChart').getContext('2d');
                        let chart;

                        function limitCheckboxSelection() {
                            let checkboxes = document.querySelectorAll('.c-checkbox');
                            let checkedCheckboxes = [];

                            checkboxes.forEach(checkbox => {
                                if (checkbox.checked) {
                                    checkedCheckboxes.push(checkbox);
                                }
                            });

                            if (checkedCheckboxes.length > 2) {
                                checkedCheckboxes[0].checked = false;
                            }

                            updateDisplay();
                        }

                        const formatNumber = (value) => {
                            if (value >= 1_000_000_000) {
                                return (value / 1_000_000_000).toFixed(1).replace('.0', '') + 'M';
                            } else if (value >= 1_000_000) {
                                return (value / 1_000_000).toFixed(1).replace('.0', '') + 'JT';
                            }
                            return value.toLocaleString('id-ID');
                        };

                        const updateDisplay = () => {
                            const checkedValues = checkboxMapping.filter((_, index) => document.getElementById(`c-${index}`).checked);

                            const chartData = [];
                            const yAxes = [];

                            let leftPosition = true;

                            const addYAxis = (id, title, callback) => {
                                const position = leftPosition ? 'left' : 'right';
                                yAxes.push({
                                    id: id,
                                    position: position,
                                    title: {
                                        display: true,
                                        text: title
                                    },
                                    ticks: {
                                        callback: callback,
                                        count: 6,
                                    }
                                });
                                leftPosition = !leftPosition;
                            };

                            if (checkedValues.includes("Spend")) {
                                chartData.push({
                                    label: 'Total Spend',
                                    data: pivotData.map(row => row.total_spend || 0),
                                    borderColor: 'rgba(153, 102, 255, 1)',
                                    fill: false,
                                    tension: 0.4,
                                    yAxisID: 'y1'
                                });
                                addYAxis('y1', 'Total Spend (Rp)', formatNumber);
                            }

                            if (checkedValues.includes("Omset")) {
                                chartData.push({
                                    label: 'Total Omset',
                                    data: pivotData.map(row => row.result || 0),
                                    borderColor: 'rgba(255, 206, 86, 1)',
                                    fill: false,
                                    tension: 0.4,
                                    yAxisID: 'y2'
                                });
                                addYAxis('y2', 'Omset (Rp)', formatNumber);
                            }

                            if (checkedValues.includes("Ratio")) {
                                chartData.push({
                                    label: 'Ratio',
                                    data: pivotData.map(row => (row.result !== 0 ? ((row.total_spend || 0) / row.result) * 100 : 0)),
                                    borderColor: 'rgba(0, 128, 0, 1)',
                                    fill: false,
                                    tension: 0.4,
                                    yAxisID: 'y3'
                                });
                                addYAxis('y3', 'Ratio (%)', value => `${value.toFixed(1)}%`);
                            }

                            if (checkedValues.includes("Penjualan")) {
                                chartData.push({
                                    label: 'Total Penjualan',
                                    data: pivotData.map(row => row.purchase_qty || 0),
                                    borderColor: '#FC819E',
                                    fill: false,
                                    tension: 0.4,
                                    yAxisID: 'y4'
                                });
                                addYAxis('y4', 'Total Penjualan (Qty)', formatNumber);
                            }

                            if (checkedValues.includes("Avg. Penjualan")) {
                                chartData.push({
                                    label: 'Avg. Penjualan',
                                    data: pivotData.map(row => row.avg_penjualan || 0),
                                    borderColor: '#608BC1',
                                    fill: false,
                                    tension: 0.4,
                                    yAxisID: 'y5'
                                });
                                addYAxis('y5', 'Avg. Penjualan', formatNumber);
                            }

                            if (chart) {
                                chart.destroy();
                            }
                            chart = new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels: pivotData.map(row => moment(row.date, 'DD-MM-YYYY').format('D MMM')),
                                    datasets: chartData,
                                },
                                options: {
                                    responsive: true,
                                    plugins: {
                                        tooltip: {
                                            mode: 'index',
                                            intersect: false,
                                        },
                                        legend: {
                                            position: 'top',
                                        },
                                    },
                                    scales: {
                                        x: {
                                            title: {
                                                display: true,
                                                text: 'Tanggal',
                                            },
                                            grid: {
                                                display: false // Hilangkan garis grid
                                            },
                                            ticks: {
                                                maxTicksLimit: 8,
                                            },
                                        },
                                        ...Object.fromEntries(yAxes.map(axis => [axis.id, {
                                            ...axis,
                                            grid: {
                                                display: true // Hilangkan garis grid
                                            },
                                            borderColor: 'transparent', // Menghilangkan garis penanda sumbu Y
                                            borderWidth: 0 // Menghilangkan border
                                        }]))
                                    },
                                },
                            });
                        };

                        window.onload = updateDisplay;
                    </script>
                </div>

                <div class="row" id="content-section">
                    <style>
                        table th,
                        table td {
                            padding: 0px;
                            text-align: center;
                        }

                        .summary td {
                            padding: 0px !important;
                        }
                    </style>

                    <div style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-bordered text-center">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <?php foreach ($pivot as $row) {
                                        echo "<th>" . $row['date'] . "</th>";
                                    } ?>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="data-row" data-type="Spend">
                                    <td>
                                        <div style='display: inline-block; padding: 4px; background-color: rgba(153, 102, 255, 1); width: 8px; height: 8px; margin-right: 5px; border-radius: 50%;'></div>
                                        Spend
                                    </td>
                                    <?php foreach ($pivot as $row) {
                                        echo "<td>" . number_format($row['total_spend'], 0, ',', '.') . "</td>";
                                    } ?>
                                </tr>
                                <tr class="data-row" data-type="Omset">
                                    <td>
                                        <div style='display: inline-block; padding: 4px; background-color: rgba(255, 206, 86, 1); width: 8px; height: 8px; margin-right: 5px; border-radius: 50%;'></div>
                                        Omset
                                    </td>
                                    <?php foreach ($pivot as $row) {
                                        echo "<td>" . number_format($row['result'], 0, ',', '.') . "</td>";
                                    } ?>
                                </tr>
                                <tr class="data-row" data-type="Ratio">
                                    <td>
                                        <div style='display: inline-block; padding: 4px; background-color: rgba(0, 128, 0, 1); width: 8px; height: 8px; margin-right: 5px; border-radius: 50%;'></div>
                                        Ratio
                                    </td>
                                    <?php foreach ($pivot as $row) {
                                        echo "<td>" . number_format($row['ratio'], 2, ',', '.') . "%</td>";
                                    } ?>
                                </tr>
                                <tr class="data-row" data-type="Penjualan">
                                    <td>
                                        <div style='display: inline-block; padding: 4px; background-color: #FC819E; width: 8px; height: 8px; margin-right: 5px; border-radius: 50%;'></div>
                                        Penjualan (Qty)
                                    </td>
                                    <?php foreach ($pivot as $row) {
                                        echo "<td>" . number_format($row['purchase_qty'], 0, ',', '.') . "</td>";
                                    } ?>
                                </tr>
                                <tr class="data-row" data-type="Avg. Penjualan">
                                    <td>
                                        <div style='display: inline-block; padding: 4px; background-color: #608BC1; width: 8px; height: 8px; margin-right: 5px; border-radius: 50%;'></div>
                                        Avg. Penjualan
                                    </td>
                                    <?php foreach ($pivot as $row) {
                                        echo "<td>" . number_format($row['avg_penjualan'], 0, ',', '.') . "</td>";
                                    } ?>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="floating-div">
    <button class="btn mb-2 btn-edit-active dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-gear fs-16"></i> Aksi
    </button>
    <ul class="dropdown-menu text-end" style="padding:0px;background:unset;border:unset">
        <li>
            <a class="dropdown-items" href="#" style="padding:0px;" data-bs-toggle="modal" data-bs-target="#pajakModal">
                <button type="button" class="btn mb-2 btn-edit-active">
                    <i class="bi bi-graph-up-arrow  fs-16"></i> Setting Pajak
                </button>
            </a>
        </li>
    </ul>
</div>

<div class="modal fade" id="pajakModal" tabindex="-1" aria-labelledby="pajakModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pajakModalLabel">Setting Pajak</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url() ?>ads/update_pajak" method="POST">
                    <div class="mb-3">
                        <label for="">Pajak saat ini: <?= $tax[0]['tax'] * 100 ?> %</label><br>
                        <label for="pajakInput" class="form-label">Pajak (%)</label>
                        <input type="number" class="form-control" id="pajakInput" name="dt[pajak]" placeholder="Masukkan nilai pajak terbaru">
                        <label for="" class="form-label">Masukkan tanggal mulai pajak</label>
                        <input type="date" class="form-control" name="dt[tanggal_mulai_berlaku]" placeholder="Masukkan tanggal mulai pajak" value="<?= date('Y-m-d') ?>">
                    </div>
                    <button type=" submit" class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>
    </div>
</div>