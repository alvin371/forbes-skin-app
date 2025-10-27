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
                <div class="col-md-12 mb-2">
                    <h3 class="text-primary fw-600">DASHBOARD LAZADA ADS ACNENO SYSTEM</h3>
                </div>
            </div>
            <div class="col">
                <form action="<?= $url ?>?m=lazada" method="GET">
                    <input type="hidden" name="m" value="lazada">
                    <div class="row">
                        <div class="col-md-5">
                            <select class="form-control select2" name="ids_campaign[]" id="campaign" multiple="multiple">
                                <?php
                                $ids = $this->input->get('ids_campaign') ?? [];
                                foreach ($campaign as $val) :
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

            <div class="row mb-4">
                <?php
                $total_expense = array_sum(array_column($ads, 'expense'));
                $total_broad_orders = array_sum(array_column($ads, 'broad_order'));
                $total_broad_gmv = array_sum(array_column($ads, 'broad_gmv'));
                $total_broad_roas = array_sum(array_column($ads, 'broad_roas'));
                ?>
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

                <div class="col-md-3 mb-3">
                    <div class="card p-36 shadow-sm" style="border-radius: 12px; border: none;">
                        <div class="d-flex justify-content-between align-items-center">
                            <!-- Left Section -->
                            <div>
                                <h6 class="text-muted" style="font-size: 0.9rem; font-weight: 600; margin-bottom: 8px;">
                                    Total Expense
                                </h6>
                                <h3 style="font-size: 1.8rem; font-weight: 700; margin-bottom: 8px; color: #172b4d;">
                                    <?= number_format($total_expense, 0, ',', '.') ?>
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

                <div class="col-md-3 mb-3">
                    <div class="card p-36 shadow-sm" style="border-radius: 12px; border: none;">
                        <div class="d-flex justify-content-between align-items-center">
                            <!-- Left Section -->
                            <div>
                                <h6 class="text-muted" style="font-size: 0.9rem; font-weight: 600; margin-bottom: 8px;">
                                    Total Pembelian
                                </h6>
                                <h3 style="font-size: 1.8rem; font-weight: 700; margin-bottom: 8px; color: #172b4d;">
                                    <?= number_format($total_broad_orders) ?>
                                </h3>

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
                                    <i class="bi bi-wallet2 text-white" style="font-size: 1.5rem;"></i>
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
                                    Total GMV
                                </h6>
                                <h3 style="font-size: 1.8rem; font-weight: 700; margin-bottom: 8px; color: #172b4d;">
                                    <?= number_format($total_broad_gmv) ?>
                                </h3>

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
                                    <i class="bi bi-cash text-white" style="font-size: 1.5rem;"></i>
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
                                    Total ROAS
                                </h6>
                                <h3 style="font-size: 1.8rem; font-weight: 700; margin-bottom: 8px; color: #172b4d;">
                                    <?= number_format($total_broad_roas) ?>
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
                                    <i class="bi bi-activity text-white" style="font-size: 1.5rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row" id="content-section"> <?php if (!empty($ads)): ?> <!-- Jika ada data iklan -->
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;"> <?php if (!empty($ads)): ?> <table class="table table-striped table-bordered table-hover">
                                <thead class="bg-light text-white">
                                    <tr>
                                        <th>Date</th>
                                        <th>Campaign ID</th>
                                        <th>Campaign Name</th>
                                        <th>Impressions</th>
                                        <th>Clicks</th>
                                        <th>CTR (%)</th>
                                        <th>Spend</th>
                                        <th>Store Revenue</th>
                                        <th>Store CVR (%)</th>
                                        <th>Store A2C (%)</th>
                                        <th>Store Orders</th>
                                        <th>Product Unit Sold</th>
                                        <th>Product CVR (%)</th>
                                        <th>Product Orders</th>
                                        <th>Store ROI</th>
                                        <th>CPC</th>
                                        <th>Product Revenue</th>
                                        <th>Store Unit Sold</th>
                                        <th>Product A2C (%)</th>
                                    </tr>
                                </thead>
                                <tbody> <?php foreach ($ads as $ad): ?> <tr>
                                            <td><?= htmlspecialchars($ad['date']) ?></td>
                                            <td><?= htmlspecialchars($ad['campaignId']) ?></td>
                                            <td><?= htmlspecialchars($ad['campaignName']) ?></td>
                                            <td><?= number_format($ad['impressions']) ?></td>
                                            <td><?= number_format($ad['clicks']) ?></td>
                                            <td><?= number_format($ad['ctr'], 2) ?>%</td>
                                            <td><?= 'Rp ' . number_format($ad['spend'], 0, ',', '.') ?></td>
                                            <td><?= 'Rp ' . number_format($ad['storeRevenue'], 0, ',', '.') ?></td>
                                            <td><?= number_format($ad['storeCvr'], 2) ?>%</td>
                                            <td><?= number_format($ad['storeA2c'], 2) ?>%</td>
                                            <td><?= number_format($ad['storeOrders']) ?></td>
                                            <td><?= number_format($ad['productUnitSold']) ?></td>
                                            <td><?= number_format($ad['productCvr'], 2) ?>%</td>
                                            <td><?= number_format($ad['productOrders']) ?></td>
                                            <td><?= number_format($ad['storeRoi'], 2) ?></td>
                                            <td><?= 'Rp ' . number_format($ad['cpc'], 0, ',', '.') ?></td>
                                            <td><?= 'Rp ' . number_format($ad['productRevenue'], 0, ',', '.') ?></td>
                                            <td><?= number_format($ad['storeUnitSold']) ?></td>
                                            <td><?= number_format($ad['productA2c'], 2) ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center">No data available</p>
                <?php endif; ?>
            </div>

            <div class="container mt-4">
                <div class="row">
                    <div class="col-md-6">
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
                                    <h5>Purchase</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="purchaseBarChart" width="400" height="150"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Add to Cart Chart</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="roasDonutChart" width="200" height="400"></canvas>
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
                $purchase = [];

                foreach ($pivot as $ad) {
                    $date[] = $ad['date'];
                    $spends[] = (float)$ad['total_spends'];
                    $atc[] = (float)$ad['total_add_to_cart'];
                    $purchase[] = (float)$ad['total_purchases'];
                }
                ?>

                var labels = <?php echo json_encode($date); ?>;
                var spendData = <?php echo json_encode($spends); ?>;
                var atc = <?php echo json_encode($atc); ?>;
                var purchase = <?php echo json_encode($purchase); ?>;

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

                var ctx = document.getElementById('roasDonutChart').getContext('2d');
                var roasDonutChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Add to Cart Value (IDR)',
                            data: atc,
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
                                        weight: 'bold'
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(tooltipItem) {
                                        return 'Add to Cart Value (IDR): ' + tooltipItem.raw.toFixed(2);
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
                                var text = "Add to Cart Value (IDR)",
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

                var ctx = document.getElementById('purchaseBarChart').getContext('2d');
                var purchaseBarChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Total Purchase',
                            data: purchase,
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
                                    text: 'Total Purchase'
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
                                        weight: 'bold'
                                    }
                                }
                            }
                        }
                    }
                });
            </script>

        </div>
    </div>
</div>