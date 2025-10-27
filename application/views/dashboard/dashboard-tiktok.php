<?php
if ($_GET['start_date'] == "") {
    $start_date = DATE("Y-m-d");
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
                    <h3 class="text-primary fw-600">DASHBOARD TIKTOK ADS ACNENO SYSTEM</h3>
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
                <form action="<?= $url ?>?m=tiktok" method="GET">
                    <input type="hidden" name="m" value="tiktok">
                    <div class="row">
                        <!-- Advertiser Filter -->
                        <div class="col-md-5">
                            <select class="form-control select2" name="ids_advertiser[]" id="advertiser" multiple="multiple">
                                <?php
                                $ids = $this->input->get('ids_advertiser') ?? [];
                                foreach ($advertiser as $val) :
                                    $selected = in_array($val['id'], $ids) ? "selected" : "";
                                ?>
                                    <option <?= $selected ?> value="<?= $val["id"] ?>"><?= $val["title"] ?></option>
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

            <!-- Cards for total values -->
            <div class="row mb-4">
                <style>
                    .card-custom {
                        background: #fff;
                        border-radius: 20px;
                        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                        position: relative;
                        text-align: center;
                        overflow: hidden;
                    }

                    .card-custom::before {
                        content: '';
                        position: absolute;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        border-radius: 20px;
                        padding: 2px;
                        background: linear-gradient(135deg, #f7669b, #a15599);
                        -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
                        -webkit-mask-composite: xor;
                        mask-composite: exclude;
                        pointer-events: none;
                    }

                    .card-custom h5 {
                        font-weight: bold;
                        margin-bottom: 8px;
                        color: #000;
                    }

                    .card-custom .card-text {
                        font-size: 1.2em;
                        font-weight: bold;
                        color: #000;
                    }
                </style>

                <div class="row mt-2">
                    <?php $gmv = array_sum(array_column($spend, 'spend_idr_after_tax'));
                    foreach ($spend as $row): ?>
                        <div class="col-md-3 mb-3">
                            <div class="card p-36 shadow-sm" style="border-radius: 12px; border: none;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <!-- Left Section -->
                                    <div>
                                        <h6 class="text-muted" style="font-size: 0.9rem; font-weight: 600; margin-bottom: 8px;">
                                            Spend GMV <?= htmlspecialchars($row['advertiser_name']) ?>
                                        </h6>
                                        <h3 style="font-size: 1.8rem; font-weight: 700; margin-bottom: 8px; color: #172b4d;">
                                            <?= number_format($row['spend_idr_after_tax']) ?>
                                        </h3>

                                    </div>

                                    <!-- Right Section -->
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
                    <?php endforeach; ?>
                    <div class="col-md-3 mb-3">
                        <div class="card p-36 shadow-sm" style="border-radius: 12px; border: none;">
                            <div class="d-flex justify-content-between align-items-center">
                                <!-- Left Section -->
                                <div>
                                    <h6 class="text-muted" style="font-size: 0.9rem; font-weight: 600; margin-bottom: 8px;">
                                        Total GMV + Pajak
                                    </h6>
                                    <h3 style="font-size: 1.8rem; font-weight: 700; margin-bottom: 8px; color: #172b4d;">
                                        <?= number_format($gmv) ?>
                                    </h3>

                                </div>

                                <!-- Right Section -->
                                <div>
                                    <div class="icon-container" style="
                                                                            width: 50px;
                                                                            height: 50px;
                                                                            background-color: #605678;
                                                                            border-radius: 50%;
                                                                            display: flex;
                                                                            align-items: center;
                                                                            justify-content: center;
                                                                        ">
                                        <i class="bi bi-clipboard-check text-white" style="font-size: 1.5rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
                $gmv = array_sum(array_column($spend, 'spend_idr_after_tax'));
                $spend = array_sum(array_column($ads, 'spend_idr'));
                $spend_after_tax = array_sum(array_column($ads, 'spend_idr_after_tax'));
                $all_spend = $gmv + $spend_after_tax;
                ?>
                <div class="row mt-1">
                    <div class="col-md-3 mb-3">
                        <div class="card p-36 shadow-sm" style="border-radius: 12px; border: none;">
                            <div class="d-flex justify-content-between align-items-center">
                                <!-- Left Section -->
                                <div>
                                    <h6 class="text-muted" style="font-size: 0.9rem; font-weight: 600; margin-bottom: 8px;">
                                        Total Biaya Iklan + Pajak
                                    </h6>
                                    <h3 style="font-size: 1.8rem; font-weight: 700; margin-bottom: 8px; color: #172b4d;">
                                        <?= number_format($spend_after_tax) ?>
                                    </h3>

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
                                        <i class="bi bi-clipboard-plus text-white" style="font-size: 1.5rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card p-36 shadow-sm" style="border-radius: 12px; border: none;">
                            <div class="d-flex justify-content-between align-items-center">
                                <!-- Left Section -->
                                <div>
                                    <h6 class="text-muted" style="font-size: 0.9rem; font-weight: 600; margin-bottom: 8px;">
                                        Total Biaya Seluruhnya
                                    </h6>
                                    <h3 style="font-size: 1.8rem; font-weight: 700; margin-bottom: 8px; color: #172b4d;">
                                        <?= number_format($all_spend) ?>
                                    </h3>

                                </div>

                                <!-- Right Section -->
                                <div>
                                    <div class="icon-container" style="
                                                                            width: 50px;
                                                                            height: 50px;
                                                                            background-color: #CB6040;
                                                                            border-radius: 50%;
                                                                            display: flex;
                                                                            align-items: center;
                                                                            justify-content: center;
                                                                        ">
                                        <i class="bi bi-clipboard-data text-white" style="font-size: 1.5rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


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

                <div class="row mt-3" id="content-section">
                    <?php if (!empty($ads)): ?>
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-striped table-bordered table-hover">
                                <thead class="bg-light text-white">
                                    <tr>
                                        <th>Date</th>
                                        <th>Account</th>
                                        <th class="sortable">Spend IDR</th>
                                        <th class="sortable">Clicks</th>
                                        <th class="sortable">Onsite Add to Cart</th>
                                        <th class="sortable">Total Onsite Add to Cart</th>
                                        <th class="sortable">Onsite Shopping</th>
                                        <th class="sortable">Gross Revenue</th>
                                        <th class="sortable">Frequency</th>
                                        <th class="sortable">Reach</th>
                                    </tr>
                                </thead>

                                <tbody id="table-body">
                                    <?php
                                    $totalSpend = 0;
                                    $totalClicks = 0;
                                    $totalOnsiteAddToCart = 0;
                                    $totalTotalOnsiteAddToCart = 0;
                                    $totalOnsiteShopping = 0;
                                    $totalTotalOnsiteShopping = 0;
                                    $totalFrequency = 0;
                                    $totalReach = 0;
                                    $totalAds = count($ads);

                                    foreach ($ads as $ad):
                                        $totalSpend += $ad['spend_idr'];
                                        $totalSpendAfterTax += $ad['spend_idr_after_tax'];
                                        $totalClicks += $ad['clicks'];
                                        $totalOnsiteAddToCart += $ad['onsite_on_web_cart'];
                                        $totalTotalOnsiteAddToCart += $ad['total_onsite_on_web_cart_value_idr'];
                                        $totalOnsiteShopping += $ad['onsite_shopping'];
                                        $totalTotalOnsiteShopping += $ad['total_onsite_shopping_value_idr'];
                                        $totalFrequency += $ad['frequency'];
                                        $totalReach += $ad['reach'];

                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($ad['date']) ?></td>
                                            <td><?= htmlspecialchars($ad['advertiser_name']) ?></td>
                                            <td><?= 'Rp ' . number_format((float)$ad['spend_idr_after_tax'], 0, ',', '.') ?></td>
                                            <td><?= htmlspecialchars($ad['clicks']) ?></td>
                                            <td><?= htmlspecialchars($ad['onsite_on_web_cart']) ?></td>
                                            <td><?= 'Rp ' . number_format((float)$ad['total_onsite_on_web_cart_value_idr'], 0, ',', '.') ?></td>
                                            <td><?= htmlspecialchars($ad['onsite_shopping']) ?></td>
                                            <td><?= 'Rp ' . number_format((float)$ad['total_onsite_shopping_value_idr'], 0, ',', '.') ?></td>
                                            <td><?= htmlspecialchars($ad['frequency'], 2) ?></td>
                                            <td><?= number_format($ad['reach'], 0, ',', '.') ?></td>
                                        </tr>
                                    <?php
                                    endforeach;
                                    $totalFrequency = $totalAds > 0 ? $totalFrequency / $totalAds : 0;
                                    ?>
                                </tbody>

                                <tfoot style="max-height: 400px; position: sticky; bottom: 0; z-index: 1;">
                                    <tr style=" font-weight: bold; background-color: #f2f2f2;">
                                        <td colspan="2" class="text-center">Grand Total</td>
                                        <td><?= 'Rp ' . number_format($totalSpendAfterTax, 0, ',', '.') ?></td>
                                        <td><?= number_format($totalClicks, 0, ',', '.') ?></td>
                                        <td><?= number_format($totalOnsiteAddToCart, 0, ',', '.') ?></td>
                                        <td><?= 'Rp ' . number_format($totalTotalOnsiteAddToCart, 0, ',', '.') ?></td>
                                        <td><?= number_format($totalOnsiteShopping, 0, ',', '.') ?></td>
                                        <td><?= 'Rp ' . number_format($totalTotalOnsiteShopping, 0, ',', '.') ?></td>
                                        <td><?= number_format($totalFrequency, 2) ?></td>
                                        <td><?= number_format($totalReach, 0, ',', '.') ?></td>
                                    </tr>
                                </tfoot>
                            </table>
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

                                if (cellA.includes('Rp') && cellB.includes('Rp')) {
                                    var numA = parseFloat(cellA.replace(/[^\d]/g, ''));
                                    var numB = parseFloat(cellB.replace(/[^\d]/g, ''));
                                    return currentSortOrder === 'asc' ? numA - numB : numB - numA;
                                }

                                if (!isNaN(cellA) && !isNaN(cellB)) {
                                    return currentSortOrder === 'asc' ?
                                        parseFloat(cellA) - parseFloat(cellB) :
                                        parseFloat(cellB) - parseFloat(cellA);
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


                <style>
                    .metrics {
                        display: flex;
                        justify-content: center;
                        margin-top: -20px;
                        font-family: Arial, sans-serif;
                    }

                    .metrics div {
                        margin: 0 20px;
                        text-align: center;
                    }
                </style>

                <div class="container mt-4">
                    <div class="row">
                        <div class="row mb-2">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Frequency Reach by Week</h5>
                                </div>
                                <div class="card-body" style="height: 50vh;">
                                    <div class="row">
                                        <div class="col-md-1 text-end">
                                            <div class="col " style="margin-top: 14vh; margin-right: -4vw">Impresi</div>
                                            <div class="col fw-bold" style="margin-right: -4vw"><?= number_format($last_week[0]['total_impressions'], 0) ?></div>
                                            <div class="col" style="margin-top: 8vh; margin-right: -4vw">CTR</div>
                                            <div class="col fw-bold" style="margin-right: -4vw"><?= number_format($last_week[0]['ctr_percentage'], 2) ?>%</div>
                                        </div>
                                        <div class="col-md-1 text-end">
                                            <div class="col">
                                                <img src="<?= base_url() ?>assets/img/icon/arrow.png" alt="" style="height: 80px; margin-top: 12vh; margin-right: -2vw">
                                            </div>
                                            <div class="col">
                                                <img src="<?= base_url() ?>assets/img/icon/arrow.png" alt="" style="height: 80px; margin-top: 2vh; margin-right: -2vw">
                                            </div>
                                        </div>
                                        <div class="col-md-1 text-end">
                                            <div class="col " style="margin-top: 10vh; margin-right: 0vw"><?= $last_week[0]['avg_frequency'] ?></div>
                                            <div class="col" style="margin-top: 10vh; margin-right: 0vw"><?= number_format($last_week[0]['total_reach'], 0) ?></div>
                                            <div class="col" style="margin-top: 10vh; margin-right: 0vw"><?= $last_week[0]['total_clicks'] ?></div>
                                        </div>
                                        <div class="col-md-3">
                                            <canvas style="margin-right: -1.3vw" id="comparisonChartReversed" height="300" width="500"></canvas>
                                        </div>
                                        <div class="col-md-3">
                                            <canvas style="margin-left: -1.4vw" id="comparisonChart" height="300" width="500"></canvas>
                                        </div>

                                        <div class="col-md-1 text-start">
                                            <div class="col " style="margin-top: 10vh; margin-left: 0vw"><?= $this_week[0]['avg_frequency'] ?></div>
                                            <div class="col" style="margin-top: 10vh; margin-left: 0vw"><?= number_format($this_week[0]['total_reach'], 0) ?></div>
                                            <div class="col" style="margin-top: 10vh; margin-left: 0vw"><?= $this_week[0]['total_clicks'] ?></div>
                                        </div>

                                        <div class="col-md-1 text-start">
                                            <div class="col">
                                                <img src="<?= base_url() ?>assets/img/icon/arrow.png" alt="" style="height: 80px; margin-top: 12vh; margin-left: -2vw; transform: scaleX(-1);">
                                            </div>
                                            <div class="col">
                                                <img src="<?= base_url() ?>assets/img/icon/arrow.png" alt="" style="height: 80px; margin-top: 2vh; margin-left: -2vw; transform: scaleX(-1);">
                                            </div>
                                        </div>


                                        <div class="col-md-1 text-start">
                                            <div class="col " style="margin-top: 14vh; margin-left: -4vw">Impresi</div>
                                            <div class="col fw-bold" style="margin-left: -4vw"><?= number_format($this_week[0]['total_impressions'], 0) ?></div>
                                            <div class="col" style="margin-top: 8vh; margin-left: -4vw">CTR</div>
                                            <div class="col fw-bold" style="margin-left: -4vw"><?= $this_week[0]['ctr_percentage'] ?>%</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-5">
                                <div class="row">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5>Spend Chart</h5>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="spendChart" width="400" height="150"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5>Gross Revenue Chart</h5>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="onsiteShoppingBarChart" width="400" height="150"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Campaign Data</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive" style="overflow-x: auto;">
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Product</th>
                                                        <th>ATC</th>
                                                        <th>Purchase</th>
                                                        <th>ATC IDR</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($campaign as $campaign): ?>
                                                        <tr style="height: 40px;">
                                                            <td style="padding: 8px; vertical-align: middle; text-align: left !important;">
                                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                                    <img src="<?= $campaign['image']; ?>" alt="Product Image" style="width: 50px; height: 50px; object-fit: cover;">
                                                                    <div>
                                                                        <strong><?= $campaign['keyword_group']; ?></strong><br>
                                                                        Akun: <?= $campaign['advertiser_names']; ?>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td style="padding: 8px; vertical-align: middle;"><?= $campaign['total_atc_qty']; ?></td>
                                                            <td style="padding: 8px; vertical-align: middle;">Rp. <?= number_format($campaign['total_purchase'], 0, ',', '.'); ?></td>
                                                            <td style="padding: 8px; vertical-align: middle;">Rp. <?= number_format($campaign['total_atc_idr'], 0, ',', '.'); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                    </div>
                </div>
            </div>

            <script>
                <?php
                $date = [];
                $spends = [];
                $atc = [];
                $onsiteShopping = [];

                foreach ($pivot as $ad) {
                    $date[] = $ad['date'];
                    $spends[] = (float)$ad['total_spend_idr'];
                    $atc[] = (float)$ad['total_onsite_on_web_cart_value_idr'];
                    $onsiteShopping[] = (float)$ad['total_onsite_shopping_value_idr'];
                }
                ?>

                var labels = <?php echo json_encode($date); ?>;
                var spendData = <?php echo json_encode($spends); ?>;
                var atc = <?php echo json_encode($atc); ?>;
                var onsiteShoppingData = <?php echo json_encode($onsiteShopping); ?>;

                var spendChartInstance;
                var onsiteShoppingBarChartInstance;

                // Spend Chart
                var spendCtx = document.getElementById('spendChart').getContext('2d');
                if (spendChartInstance) {
                    spendChartInstance.destroy();
                }
                spendChartInstance = new Chart(spendCtx, {
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

                // Onsite Shopping Bar Chart
                var onsiteShoppingCtx = document.getElementById('onsiteShoppingBarChart').getContext('2d');
                if (onsiteShoppingBarChartInstance) {
                    onsiteShoppingBarChartInstance.destroy();
                }
                onsiteShoppingBarChartInstance = new Chart(onsiteShoppingCtx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Gross Revenue (IDR)',
                            data: onsiteShoppingData,
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
                                    text: 'Gross Revenue (IDR)'
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
                const ctx1 = document.getElementById('comparisonChart').getContext('2d');
                const ctx2 = document.getElementById('comparisonChartReversed').getContext('2d');

                var thisWeek = <?php echo json_encode($this_week) ?: '[]'; ?>;
                var lastWeek = <?php echo json_encode($last_week) ?: '[]'; ?>;

                var date_range1 = thisWeek[0].date_range;
                var avg_frequency1 = thisWeek[0].avg_frequency;
                var total_reach1 = thisWeek[0].total_reach;
                var total_purchases1 = thisWeek[0].total_purchases;
                var total_impressions1 = thisWeek[0].total_impressions;
                var total_clicks1 = thisWeek[0].total_clicks;
                var ctr_percentage1 = thisWeek[0].ctr_percentage;

                var date_range2 = lastWeek[0].date_range;
                var avg_frequency2 = lastWeek[0].avg_frequency;
                var total_reach2 = lastWeek[0].total_reach;
                var total_purchases2 = lastWeek[0].total_purchases;
                var total_impressions2 = lastWeek[0].total_impressions;
                var total_clicks2 = lastWeek[0].total_clicks;
                var ctr_percentage2 = lastWeek[0].ctr_percentage;

                const maxReach = Math.max(total_reach1, total_reach2) + 15000;

                const commonOptions = {
                    responsive: true,
                    indexAxis: 'y',
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                        },
                        tooltip: {
                            enabled: false,
                        },
                        customLabels: {
                            beforeDraw: (chart) => {
                                const { ctx, data } = chart;
                                const barTitles = ['Frequency', 'Reach', 'Clicks'];
                                const xAxis = chart.scales.x;
                                const yAxis = chart.scales.y;

                                ctx.save();
                                ctx.font = '12px Arial';
                                ctx.fillStyle = 'black';
                                ctx.textAlign = 'center';

                                data.datasets.forEach((dataset) => {
                                    dataset.data.forEach((value, index) => {
                                        const barX = xAxis.getPixelForValue(index);
                                        const barY = yAxis.getPixelForTick(index);

                                        const labelYPosition = barY;

                                        ctx.save();
                                        ctx.translate(barX, labelYPosition);
                                        ctx.rotate(-Math.PI / 2);

                                        ctx.fillText(barTitles[index], 0, 0);

                                        ctx.restore();
                                    });
                                });

                                ctx.restore();
                            },
                        },
                    },
                    scales: {
                        x: {
                            type: 'linear',
                            stacked: true,
                            grid: {
                                display: false,
                            },
                            ticks: {
                                display: false,
                                beginAtZero: true,
                                stepSize: 1000,
                            },
                            max: 80000,
                            barThickness: 40,
                            categoryPercentage: 0.7,
                            maxBarThickness: 50,
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                display: false,
                            },
                            ticks: {
                                display: false,
                            },
                        },
                        y2: {
                            position: 'right',
                            grid: {
                                display: false,
                            },
                            ticks: {
                                display: false,
                                color: 'rgba(255, 0, 0, 1)',
                            },
                        },
                    },
                };

                const data1 = {
                    labels: ['Frequency', 'Reach', 'Clicks'],
                    datasets: [{
                        label: date_range1,
                        data: [
                            parseFloat(avg_frequency1) + 5000.0,
                            parseFloat(total_reach1) + 2000,
                            parseFloat(total_clicks1) + 12000,
                        ],
                        backgroundColor: 'rgba(180, 180, 255, 0.42)',
                        borderColor: 'rgba(20, 20, 66, 0.34)',
                        borderWidth: 1,
                    }],
                };

                const data2 = {
                    labels: ['Frequency', 'Reach', 'Clicks'],
                    datasets: [{
                        label: date_range2,
                        data: [
                            parseFloat(avg_frequency2) + 5000.0,
                            parseFloat(total_reach2) + 2000,
                            parseFloat(total_clicks2) + 12000,
                        ],
                        backgroundColor: 'rgba(255, 0, 0, 0.3)',
                        borderColor: 'rgba(255, 0, 0, 1)',
                        borderWidth: 1,
                    }],
                };

                new Chart(ctx1, {
                    type: 'bar',
                    data: data1,
                    options: {
                        ...commonOptions,
                        scales: {
                            ...commonOptions.scales,
                            x: {
                                ...commonOptions.scales.x,
                                reverse: false,
                                barThickness: 40,
                                maxBarThickness: 50,
                            },
                        },
                    },
                    plugins: [{
                        id: 'customLabels',
                        beforeDraw(chart, args, options) {
                            chart.config.options.plugins.customLabels.beforeDraw(chart);
                        },
                    }],
                });

                new Chart(ctx2, {
                    type: 'bar',
                    data: data2,
                    options: {
                        ...commonOptions,
                        scales: {
                            ...commonOptions.scales,
                            x: {
                                ...commonOptions.scales.x,
                                reverse: true,
                                barThickness: 40,
                                maxBarThickness: 50,
                            },
                        },
                    },
                });

            </script>


        </div>
    </div>
</div>