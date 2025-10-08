<?php
if ($_GET['start_date'] == "") {
    $start_date = DATE("Y-m-01");
    // 
} else {
    $start_date = $_GET['start_date'];
}
if ($_GET['until_date'] == "") {
    $until_date = DATE("Y-m-d");
} else {
    $until_date = $_GET['until_date'];
}
?>
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
<div class="w-100">
    <div class="row align-items-center">
        <div class="col-lg-12 mb-1">
            <div class="row">
                <div class="col-md-12">
                    <h3 class="text-primary fw-600">DASHBOARD SHOPEE ADS BHSKIN</h3>
                </div>
            </div>

            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const spinner = document.getElementById('loading-spinner');
                    const content = document.getElementById('content-section');

                    spinner.style.display = 'block';
                    content.style.display = 'none';

                    setTimeout(() => {
                        spinner.style.display = 'none';
                        content.style.display = 'block';

                        const labels = <?= json_encode(array_column($ads, 'date')) ?>;
                        const impressions = <?= json_encode(array_column($ads, 'impressions')) ?>;
                        const clicks = <?= json_encode(array_column($ads, 'clicks')) ?>;
                    }, 1000);
                });
            </script>
            <div class="col">
                <form action="<?= $url ?>?m=shopee" method="GET">
                    <input type="hidden" name="m" value="shopee">
                    <div class="row">
                        <!-- Advertiser Filter -->
                        <div class="col-md-5">
                            <select class="form-control select2" name="ids_account[]" id="advertiser" multiple="multiple">
                                <?php
                                $ids = $this->input->get('ids_account') ?? []; // Capture ids_advertiser
                                foreach ($advertiser as $val) :
                                    $selected = in_array($val['id'], $ids) ? "selected" : "";
                                ?>
                                    <option <?= $selected ?> value="<?= $val["id"] ?>"><?= $val["title"] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Filter Content -->
                        <div class="col-md-4">
                            <input type="text" class="form-control" id="tanggal" placeholder="Pilih rentang tanggal...">
                            <input type="hidden" name="start_date" id="start_date" value="<?= $_GET['start_date'] ?? $start_date ?>">
                            <input type="hidden" name="until_date" id="end_date" value="<?= $_GET['until_date'] ?? $until_date ?>">
                        </div>

                        <div class="col-md-2">
                            <button class="btn btn-primary form-control" type="submit"><i class="bi bi-search fs-16"></i> Cari Data</button>
                        </div>
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

                </form>
            </div>

            <div class="row mb-3">
                <?php
                $total_expense = array_sum(array_column($ads, 'expense'));
                $total_expense_after_tax = array_sum(array_column($ads, 'expense_after_tax'));
                $total_broad_orders = array_sum(array_column($ads, 'broad_order'));
                $total_broad_gmv = array_sum(array_column($ads, 'broad_gmv'));
                $total_broad_roas = array_sum(array_column($ads, 'broad_roas'));
                ?>

                <?php
                $today = date('Y-m-d');
                $current_day_data = null;
                foreach ($week as $day_data) {
                    if ($day_data['date'] === $today) {
                        $current_day_data = $day_data;
                        break;
                    }
                }

                if ($current_day_data) {
                    $total_expense_today = isset($current_day_data['total_spend']) ? floatval($current_day_data['total_spend']) : 0;
                    $total_purchase_today = isset($current_day_data['total_purchase']) ? floatval($current_day_data['total_purchase']) : 0;
                    $total_gmv_today = isset($current_day_data['total_gmv']) ? floatval($current_day_data['total_gmv']) : 0;
                    $total_roas_today = isset($current_day_data['total_roas']) ? floatval($current_day_data['total_roas']) : 0;
                    $percentage_change = 0;

                    if ($total_expense_after_tax > 0 && $total_broad_orders > 0 && $total_broad_gmv > 0) {
                        if ($total_expense_today > 0 && $total_purchase_today > 0 && $total_gmv_today > 0) {
                            $percentage_change_spend = (($total_expense_today - $total_expense_after_tax) / $total_expense_after_tax) * 100;
                            $percentage_change_purchase = (($total_purchase_today - $total_broad_orders) / $total_broad_orders) * 100;
                            $percentage_change_gmv = (($total_gmv_today - $total_broad_gmv) / $total_broad_gmv) * 100;
                            $percentage_change_roas = (($total_roas_today - $total_broad_roas) / $total_broad_roas) * 100;

                            $text_class_spend = "text-danger";
                            $text_class_purchase = "text-danger";
                            $text_class_gmv = "text-danger";
                            $text_class_roas = "text-danger";

                            $icon_class_spend = "bi-caret-down-fill text-danger";
                            $icon_class_purchase = "bi-caret-down-fill text-danger";
                            $icon_class_gmv = "bi-caret-down-fill text-danger";
                            $icon_class_roas = "bi-caret-down-fill text-danger";
                        } else {
                            $percentage_change_spend = (($total_expense_after_tax - $total_expense_today) / $total_expense_after_tax) * 100;
                            $percentage_change_purchase = (($total_broad_orders - $total_purchase_today) / $total_broad_orders) * 100;
                            $percentage_change_gmv = (($total_broad_gmv - $total_gmv_today) / $total_broad_gmv) * 100;
                            $percentage_change_roas = (($total_broad_roas - $total_roas_today) / $total_broad_roas) * 100;

                            $text_class_spend = "text-success";
                            $text_class_purchase = "text-success";
                            $text_class_gmv = "text-success";
                            $text_class_roas = "text-success";

                            $icon_class_spend = "bi-caret-up-fill text-success";
                            $icon_class_purchase = "bi-caret-up-fill text-success";
                            $icon_class_gmv = "bi-caret-up-fill text-success";
                            $icon_class_roas = "bi-caret-up-fill text-success";
                        }
                    } else {
                        $text_class_spend = "text-muted";
                        $icon_class_spend = "bi-caret-right-fill text-muted";
                        $percentage_change_spend = 0;

                        $text_class_purchase = "text-muted";
                        $icon_class_purchase = "bi-caret-right-fill text-muted";
                        $percentage_change_purchase = 0;

                        $text_class_gmv = "text-muted";
                        $icon_class_gmv = "bi-caret-right-fill text-muted";
                        $percentage_change_gmv = 0;

                        $text_class_roas = "text-muted";
                        $icon_class_roas = "bi-caret-right-fill text-muted";
                        $percentage_change_roas = 0;
                    }
                } else {
                    $percentage_change = 0;
                    $text_class = "text-muted";
                    $icon_class = "bi-caret-right-fill";
                }

                ?>

                <style>
                    .card {
                        flex: 1;
                        background: #fff;
                        border: none;
                        border-top: 5px solid;
                        border-radius: 8px;
                        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                    }

                    .card.spend {
                        border-color: #8174A0;
                    }

                    .card.gmv {
                        border-color: #62825D;
                    }

                    .card.purchase {
                        border-color: #54473F;
                    }

                    .card.roas {
                        border-color: #257180;
                    }

                    .chart-container {
                        width: 100%;
                        height: 300px;
                    }

                    .icon-container {
                        width: 50px;
                        height: 50px;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }

                    .percentage {
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        font-weight: bold;
                        width: 100%;
                    }

                    .percentage small {
                        text-align: center;
                        margin-right: 5px;
                    }
                </style>

                <div class="col-md-3">
                    <div class="card spend p-36 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <!-- Left Section -->
                            <div>
                                <h6 class="text-muted" style="font-size: 0.9rem; font-weight: 600; margin-bottom: 8px;">
                                    Total Spent
                                </h6>
                                <h3 style="font-size: 1.6rem; font-weight: 700; margin-bottom: 8px; color: #172b4d;">
                                    <?= number_format($total_expense_after_tax) ?>
                                </h3>
                                <div class="d-flex justify-content-start align-items-center">
                                    <small class="fw-bold <?= $text_class_spend ?>"><?= number_format($percentage_change_spend, 2) ?>%</small>
                                    <i class="ms-2 bi <?= $icon_class_spend ?> text-start <?= $text_class ?> fw-bold"></i>
                                </div>
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

                <div class="col-md-3">
                    <div class="card gmv p-36 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <!-- Left Section -->
                            <div>
                                <h6 class="text-muted" style="font-size: 0.9rem; font-weight: 600; margin-bottom: 8px;">
                                    Total GMV
                                </h6>
                                <h3 style="font-size: 1.6rem; font-weight: 700; margin-bottom: 8px; color: #172b4d;">
                                    <p class="card-text"><?= 'Rp ' . number_format($total_broad_gmv) ?></p>
                                </h3>
                                <div class="d-flex justify-content-start align-items-center">
                                    <small class="fw-bold <?= $text_class_gmv ?>"><?= number_format($percentage_change_gmv, 2) ?>%</small>
                                    <i class="ms-2 bi <?= $icon_class_gmv ?> text-start <?= $text_class ?> fw-bold"></i>
                                </div>
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
                <div class="col-md-3">
                    <div class="card purchase p-36 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <!-- Left Section -->
                            <div>
                                <h6 class="text-muted" style="font-size: 0.9rem; font-weight: 600; margin-bottom: 8px;">
                                    Total Pembelian
                                </h6>
                                <h3 style="font-size: 1.6rem; font-weight: 700; margin-bottom: 8px; color: #172b4d;">
                                    <?= number_format($total_broad_orders) ?>
                                </h3>
                                <div class="d-flex justify-content-start align-items-center">
                                    <small class="fw-bold <?= $text_class_purchase ?>"><?= number_format($percentage_change_purchase, 2) ?>%</small>
                                    <i class="ms-2 bi <?= $icon_class_purchase ?> text-start <?= $text_class ?> fw-bold"></i>
                                </div>
                            </div>

                            <!-- Right Section -->
                            <div>
                                <div class="icon-container" style="
                                                                            width: 50px;
                                                                            height: 50px;
                                                                            background-color: #54473F;
                                                                            border-radius: 50%;
                                                                            display: flex;
                                                                            align-items: center;
                                                                            justify-content: center;
                                                                        ">
                                    <i class="bi bi-clipboard-minus text-white" style="font-size: 1.5rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card roas p-36 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <!-- Left Section -->
                            <div>
                                <h6 class="text-muted" style="font-size: 0.9rem; font-weight: 600; margin-bottom: 8px;">
                                    Total ROAS
                                </h6>
                                <h3 style="font-size: 1.6rem; font-weight: 700; margin-bottom: 8px; color: #172b4d;">
                                    <?= number_format($total_broad_roas) ?>
                                </h3>
                                <div class="d-flex justify-content-start align-items-center">
                                    <small class="fw-bold <?= $text_class_roas ?>"><?= number_format($percentage_change_roas, 2) ?>%</small>
                                    <i class="ms-2 bi <?= $icon_class_roas ?> text-start <?= $text_class ?> fw-bold"></i>
                                </div>
                            </div>

                            <!-- Right Section -->
                            <div>
                                <div class="icon-container" style="
                                                                            width: 50px;
                                                                            height: 50px;
                                                                            background-color: #257180;
                                                                            border-radius: 50%;
                                                                            display: flex;
                                                                            align-items: center;
                                                                            justify-content: center;
                                                                        ">
                                    <i class="bi bi-clipboard2-check text-white" style="font-size: 1.5rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row" style="margin-left: 0.1vw; margin-right: 0.1vw; margin-bottom: 3vh">
                <div class="card chart-container" style="border: none;">
                    <canvas id="criteriaChart" width="1200"></canvas>
                </div>
            </div>
            <!-- Loading Spinner
            <div id="loading-spinner" style="display: none; text-align: center; margin: 20px 0;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div> -->

            <!-- Tampilkan data iklan (ads) di bawah ini -->
            <style>
                th.sortable {
                    cursor: pointer;
                }

                th.asc::after {
                    content: " ▲";
                }

                th.desc::after {
                    content: " ▼";
                }
            </style>
            <div class="row" id="content-section">
                <?php if (!empty($ads)): ?>
                    <!-- Jika ada data iklan -->
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <?php if (!empty($ads)): ?>
                            <table class="table table-striped table-bordered table-hover">
                                <thead class="bg-light text-white">
                                    <tr>
                                        <th class="sortable">Date</th>
                                        <th class="sortable">Shop Name</th>
                                        <th class="sortable">Spend</th>
                                        <th class="sortable">Impression</th>
                                        <th class="sortable">Clicks</th>
                                        <th class="sortable">CTR (%)</th>
                                        <th class="sortable">Order</th>
                                        <th class="sortable">Conversions</th>
                                        <th class="sortable">Item Sold</th>
                                        <th class="sortable">GMV</th>
                                        <th class="sortable">ROAS</th>
                                    </tr>
                                </thead>
                                <tbody id="table-body">
                                    <?php
                                    // Initialize totals
                                    $totalImpression = 0;
                                    $totalClicks = 0;
                                    $totalCTR = 0;
                                    $totalOrder = 0;
                                    $totalConversions = 0;
                                    $totalItemSold = 0;
                                    $totalGMV = 0;
                                    $totalRoas = 0;

                                    foreach ($ads as $ad):
                                        // Sum the values for the totals
                                        $totalImpression += $ad['impression'];
                                        $totalClicks += $ad['clicks'];
                                        $totalExpense += $ad['expense'];
                                        $totalExpenseAfterTax += $ad['expense_after_tax'];
                                        $totalCTR += $ad['ctr'];
                                        $totalOrder += $ad['broad_order'];
                                        $totalConversions += $ad['broad_conversions'];
                                        $totalItemSold += $ad['broad_item_sold'];
                                        $totalGMV += $ad['broad_gmv'];
                                        $totalRoas += $ad['broad_roas'];
                                    endforeach;
                                    ?>
                                    <?php foreach ($ads as $ad): ?>
                                        <tr>
                                            <td><?= $ad['date'] ?></td>
                                            <td><?= htmlspecialchars($ad['shop_name']) ?></td>
                                            <td><?= 'Rp ' . number_format($ad['expense_after_tax']) ?></td>
                                            <td><?= number_format($ad['impression']) ?></td>
                                            <td><?= number_format($ad['clicks']) ?></td>
                                            <td><?= number_format($ad['ctr'], 2) ?>%</td>
                                            <td><?= $ad['broad_order'] ?></td>
                                            <td><?= is_numeric($ad['broad_conversions']) ? number_format($ad['broad_conversions'], 2) : '0.00' ?></td>
                                            <td><?= $ad['broad_item_sold'] ?></td>
                                            <td><?= 'Rp ' . number_format($ad['broad_gmv'], 0, ',', '.') ?></td>
                                            <td><?= number_format($ad['broad_roas'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot style="max-height: 400px; position: sticky; bottom: 0; z-index: 1;">
                                    <tr style=" font-weight: bold; background-color: #f2f2f2;">
                                        <td colspan="2" class="text-center">Grand Total</td>
                                        <td><?= 'Rp ' . number_format($totalExpenseAfterTax, 0, ',', '.') ?></td>
                                        <td><?= number_format($totalImpression, 0, ',', '.') ?></td>
                                        <td><?= number_format($totalClicks, 0, ',', '.') ?></td>
                                        <td><?= $totalCTR ?>%</td>
                                        <td><?= number_format($totalOrder, 0, ',', '.') ?></td>
                                        <td><?= $totalConversions ?></td>
                                        <td><?= number_format($totalItemSold, 0, ',', '.') ?></td>
                                        <td><?= 'Rp ' . number_format($totalGMV, 0, ',', '.') ?></td>
                                        <td><?= number_format($totalRoas, 0, ',', '.') ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center">No data available</p>
                <?php endif; ?>
            </div>

            <script>
                $(document).ready(function() {
                    var originalRows = [];
                    var currentSortIndex = null;
                    var currentSortOrder = 'asc';

                    if ($('#table-body tr').length > 0) {
                        originalRows = $('#table-body tr').toArray();
                    }

                    $('th.sortable').on('click', function() {
                        var index = $(this).index();

                        if (currentSortIndex === index) {
                            currentSortOrder = currentSortOrder === 'asc' ? 'desc' : 'asc';
                        } else {
                            currentSortOrder = 'asc';
                            currentSortIndex = index;
                        }

                        var rows = $('#table-body tr').get();

                        rows.sort(function(rowA, rowB) {
                            var cellA = $(rowA).children('td').eq(index).text().trim();
                            var cellB = $(rowB).children('td').eq(index).text().trim();

                            var numA = parseFloat(cellA.replace(/[^\d.]/g, '')) || 0;
                            var numB = parseFloat(cellB.replace(/[^\d.]/g, '')) || 0;

                            if (cellA.includes('Rp') && cellB.includes('Rp')) {
                                var numA = parseFloat(cellA.replace(/[^\d]/g, ''));
                                var numB = parseFloat(cellB.replace(/[^\d]/g, ''));
                                return currentSortOrder === 'asc' ? numA - numB : numB - numA;
                            }

                            if (!isNaN(numA) && !isNaN(numB)) {
                                return currentSortOrder === 'asc' ? numA - numB : numB - numA;
                            }

                            if (Date.parse(cellA) && Date.parse(cellB)) {
                                var dateA = new Date(cellA);
                                var dateB = new Date(cellB);
                                return currentSortOrder === 'asc' ? dateA - dateB : dateB - dateA;
                            }

                            return currentSortOrder === 'asc' ?
                                cellA.localeCompare(cellB) :
                                cellB.localeCompare(cellA);
                        });

                        $.each(rows, function(index, row) {
                            $('#table-body').append(row);
                        });

                        $('th').removeClass('asc desc');
                        $(this).addClass(currentSortOrder === 'asc' ? 'asc' : 'desc');
                    });
                });
            </script>



            <div class="container mt-4">
                <div class="row">
                    <div class="col-md-6">
                        <div class="row">
                            <div class="card" style="border: none;">
                                <div class="ps-2 pt-1">
                                    <h5>Spend Chart</h5>
                                </div>
                                <div class="mt-2">
                                    <canvas id="spendChart" width="400" height="150"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="card" style="border: none;">
                                <div class="ps-2 pt-1">
                                    <h5>Orders</h5>
                                </div>
                                <div class="mt-2">
                                    <canvas id="ordersBarChart" width="400" height="150"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card" style="border: none;">
                            <div class="ps-2 pt-1">
                                <h5>GMV Chart</h5>
                            </div>
                            <div class="">
                                <canvas id="gmvDonutChart" width="200" height="400"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                <?php
                $date = [];
                $spends = [];
                $orders = [];
                $gmv = [];

                foreach ($pivot as $ad) {
                    $date[] = $ad['date'];
                    $spends[] = (float)$ad['total_spends'] + (float)$ad['total_spends'] * 0.1;
                    $orders[] = (float)$ad['total_orders'];
                    $gmv[] = (float)$ad['total_gmv'];
                }
                ?>

                var labels = <?php echo json_encode($date); ?>;
                var spendData = <?php echo json_encode($spends); ?>;
                var orders = <?php echo json_encode($orders); ?>;
                var gmv = <?php echo json_encode($gmv); ?>;

                var ctx = document.getElementById('spendChart').getContext('2d');
                var spendChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Spend IDR',
                            data: spendData,
                            borderColor: 'rgba(75, 192, 192, 1)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            fill: true,
                            lineTension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            x: {
                                title: {
                                    display: true,
                                    text: 'Date'
                                }
                            },
                            y: {
                                ticks: {
                                    stepSize: 100000,
                                    callback: function(value) {
                                        return 'Rp ' + value.toLocaleString();
                                    }
                                },
                                title: {
                                    display: true,
                                    text: 'Spend (IDR)'
                                }
                            }
                        }
                    }
                });

                var ctx = document.getElementById('gmvDonutChart').getContext('2d');
                var gmvDonutChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'GMV (IDR)',
                            data: gmv,
                            backgroundColor: [
                                'rgba(75, 192, 192, 0.6)',
                                'rgba(153, 102, 255, 0.6)',
                                'rgba(255, 159, 64, 0.6)',
                                'rgba(54, 162, 235, 0.6)',
                                'rgba(255, 99, 132, 0.6)',
                                'rgba(255, 205, 86, 0.6)',
                                'rgba(201, 203, 207, 0.6)'
                            ],
                            borderColor: 'rgba(255, 255, 255, 0.7)',
                            borderWidth: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    font: {
                                        size: 14,
                                        weight: 'bold',
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(tooltipItem) {
                                        return 'GMV (IDR): ' + tooltipItem.raw.toFixed(2);
                                    }
                                }
                            },
                            beforeDraw: function(chart) {
                                var width = chart.chart.width,
                                    height = chart.chart.height,
                                    ctx = chart.chart.ctx;
                                ctx.restore();
                                var fontSize = (height / 114).toFixed(2);
                                ctx.font = fontSize + "em sans-serif";
                                ctx.fillStyle = "#333";
                                var text = "GMV (IDR)",
                                    textX = Math.round((width - ctx.measureText(text).width) / 2),
                                    textY = height / 2;
                                ctx.fillText(text, textX, textY);
                                ctx.save();
                            }
                        },
                        cutoutPercentage: 75,
                        rotation: -0.5 * Math.PI,
                        animation: {
                            animateRotate: true,
                            duration: 1500
                        }
                    }
                });

                var ctx = document.getElementById('ordersBarChart').getContext('2d');
                var ordersBarChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Total Orders',
                            data: orders,
                            backgroundColor: 'rgba(54, 162, 235, 0.6)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            x: {
                                title: {
                                    display: true,
                                    text: 'Date'
                                }
                            },
                            y: {
                                title: {
                                    display: true,
                                    text: 'Total Orders'
                                },
                                beginAtZero: true
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    font: {
                                        size: 14,
                                        weight: 'bold',
                                    }
                                }
                            }
                        }
                    }
                });

                let week = <?php echo json_encode($week); ?>;

                const weekLabels = week.map(item => item.date);
                const weekTotalSpend = week.map(item => parseFloat(item.total_spend));
                const weekTotalPurchase = week.map(item => parseInt(item.total_purchase));
                const weekTotalGMV = week.map(item => parseFloat(item.total_gmv));
                const weekROAS = week.map(item => parseFloat(item.total_roas));

                const overview = document.getElementById('criteriaChart').getContext('2d');
                const criteriaChart = new Chart(overview, {
                    type: 'line',
                    data: {
                        labels: weekLabels,
                        datasets: [{
                                label: 'Total Spend',
                                data: weekTotalSpend,
                                borderColor: '#8174A0',
                                backgroundColor: '#A888B5',
                                fill: false,
                            },
                            {
                                label: 'Total Purchase',
                                data: weekTotalPurchase,
                                borderColor: '#62825D',
                                backgroundColor: '#A9C46C',
                                fill: false,
                            },
                            {
                                label: 'Total GMV',
                                data: weekTotalGMV,
                                borderColor: '#54473F',
                                backgroundColor: '#B59F78',
                                fill: false,
                            },
                            {
                                label: 'ROAS',
                                data: weekROAS,
                                borderColor: '#257180',
                                backgroundColor: '#A6CDC6',
                                fill: false,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                                callbacks: {
                                    label: function(context) {
                                        const value = context.raw;
                                        const datasetLabel = context.dataset.label;

                                        if (datasetLabel === 'ROAS') {
                                            return `${datasetLabel}: ${(typeof value === 'number' ? value.toFixed(2) : '0')}%`;
                                        } else if (datasetLabel === 'Total Purchase') {
                                            return `${datasetLabel}: ${value} Qty`;
                                        } else if (datasetLabel === 'Total Spend' || datasetLabel === 'Total GMV') {
                                            return `${datasetLabel}: Rp ` + new Intl.NumberFormat('id-ID', {
                                                useGrouping: true,
                                            }).format(value);
                                        }
                                    },
                                },
                            }
                        },
                        scales: {
                            x: {
                                title: {
                                    display: true,
                                    text: 'Date',
                                },
                                grid: {
                                    display: false,
                                },
                                ticks: {
                                    display: true,
                                }
                            },
                            y: {
                                display: false,
                                title: {
                                    display: false,
                                },
                                ticks: {
                                    display: false,
                                },
                                grid: {
                                    display: false,
                                }
                            }
                        },
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        elements: {
                            line: {
                                borderWidth: 2,
                            },
                            point: {
                                radius: 0,
                            }
                        }
                    }
                });
            </script>

        </div>
    </div>
</div>