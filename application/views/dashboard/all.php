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

    /* Loading animations */
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

    /* Tab styling */
    .nav-tabs .nav-link {
        border: none;
        border-radius: 0;
        color: #6c757d;
        font-weight: 500;
        padding: 12px 20px;
        margin-right: 5px;
        background-color: #f8f9fa;
        border-bottom: 3px solid transparent;
        transition: all 0.3s ease;
    }

    .nav-tabs .nav-link:hover {
        border-color: transparent;
        background-color: #e9ecef;
        color: #1255cc;
    }

    .nav-tabs .nav-link.active {
        color: #1255cc;
        background-color: #ffffff;
        border-bottom: 3px solid #1255cc;
        border-top: none;
        border-left: none;
        border-right: none;
    }

    .nav-tabs {
        border-bottom: 1px solid #dee2e6;
        margin-bottom: 0;
    }

    .tab-content {
        background-color: #ffffff;
        border: 1px solid #dee2e6;
        border-top: none;
        padding: 20px;
        min-height: 400px;
    }

    /* Tab loading states */
    .tab-pane .skeleton-placeholder {
        border-radius: 4px;
        animation: loading 1.5s ease-in-out infinite;
    }

    .fade {
        transition: opacity 0.3s linear;
    }

    /* Progressive loading animations */
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

    /* Improve loading state visibility */
    .loading-metric {
        opacity: 0.6;
        transition: opacity 0.3s ease;
    }

    .loaded-metric {
        opacity: 1;
        transition: opacity 0.3s ease;
    }

    /* Responsive untuk layar kecil */
    @media (max-width: 576px) {
        .daterangepicker {
            flex-direction: column;
        }

        .daterangepicker .drp-calendar {
            margin: 0 !important;
            margin-bottom: 15px !important;
        }

        .batch-loading-indicator {
            top: 10px;
            right: 10px;
            font-size: 12px;
            padding: 8px 12px;
        }

        .nav-tabs .nav-link {
            padding: 8px 12px;
            font-size: 14px;
        }

        .tab-content {
            padding: 15px;
        }
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
        <div class="col-lg-12 mb-3">

            <div class="row">
                <div class="col-md-12">
                    <h3 class="text-primary fw-500">DASHBOARD ORDER BHSKIN</h3>
                </div>
            </div>
            <?php $this->load->view('dashboard/menu') ?>
        </div>

        <div class="col-lg-12">



            <form action="" class="">
                <div class="row">

                    <div class="col-md-6">
                        <div class="row">
                            <div class="col-md-4">
                                <select class="form-control select2" name="brand" id="brand">
                                    <option value="">Brand</option>
                                    <?php foreach ($brands as $val) :
                                        $text = "";
                                        if ($_GET["brand"] == $val["code"]) {
                                            $text = "selected";
                                        }
                                    ?>
                                        <option <?= $text ?> value="<?= $val["code"] ?>"><?= $val["code"] ?></option>
                                    <?php
                                    endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select class="form-control select2" name="channel">
                                    <option value="">Channel</option>
                                    <?php foreach ($channel_2 as $val) :
                                        $text = "";
                                        if ($_GET["channel"] == $val["name"]) {
                                            $text = "selected";
                                        }
                                    ?>
                                        <option <?= $text ?> value="<?= $val["name"] ?>"><?= $val["name"] ?></option>
                                    <?php
                                    endforeach; ?>
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
            </form>


        </div>

        <!-- Dashboard Tabs -->
        <div class="row mt-4">
            <div class="col-12">
                <ul class="nav nav-tabs" id="dashboardTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="laporan-tab" data-bs-toggle="tab" data-bs-target="#laporan-panel" type="button" role="tab" aria-controls="laporan-panel" aria-selected="true">
                            <i class="bi bi-clipboard-data me-2"></i>Laporan Order
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="grafik-tab" data-bs-toggle="tab" data-bs-target="#grafik-panel" type="button" role="tab" aria-controls="grafik-panel" aria-selected="false">
                            <i class="bi bi-graph-up me-2"></i>Grafik Order & Channel Metrics
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="tab-content mt-3" id="dashboardTabContent">
            <!-- Laporan Order Tab -->
            <div class="tab-pane fade show active" id="laporan-panel" role="tabpanel" aria-labelledby="laporan-tab">
                <h4 class="text-primary fw-500 mb-3">LAPORAN ORDER</h4>
                <div class="laporan-loading-skeleton" style="display: none;">
                    <div class="row">
                        <div class="col-md-3 col-6 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="skeleton-placeholder" style="height: 20px; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); margin-bottom: 10px;"></div>
                                    <div class="skeleton-placeholder" style="height: 30px; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); margin-bottom: 10px;"></div>
                                    <div class="skeleton-placeholder" style="height: 15px; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="skeleton-placeholder" style="height: 20px; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); margin-bottom: 10px;"></div>
                                    <div class="skeleton-placeholder" style="height: 30px; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); margin-bottom: 10px;"></div>
                                    <div class="skeleton-placeholder" style="height: 15px; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="skeleton-placeholder" style="height: 20px; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); margin-bottom: 10px;"></div>
                                    <div class="skeleton-placeholder" style="height: 30px; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); margin-bottom: 10px;"></div>
                                    <div class="skeleton-placeholder" style="height: 15px; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="skeleton-placeholder" style="height: 20px; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); margin-bottom: 10px;"></div>
                                    <div class="skeleton-placeholder" style="height: 30px; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); margin-bottom: 10px;"></div>
                                    <div class="skeleton-placeholder" style="height: 15px; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="owl-carousel owl-theme" id="laporan-carousel">
                    <div class="item">
                        <div class="col-lg-12 mt-3">
                            <div class="row">
                                <?php
                                $sum = array();
                                $i = 0;
                                $user_id = $_SESSION['user']['id'];

                                // Define all available dashboard cards with their permission mappings
                                $available_cards = array(
                                    'order-1' => array(
                                        'code' => 'order-1',
                                        'img' => "bi bi-cart-fill",
                                        'color_box' => "#1155CC1A",
                                        'color_icon' => "#1255cc",
                                        'title' => "Jumlah Order",
                                        'unit' => "BCM",
                                        'permission_module' => 'dashboard_card_jumlah_order'
                                    ),
                                    'order-2' => array(
                                        'code' => 'order-2',
                                        'img' => "bi bi-cart-dash",
                                        'color_box' => "#1155CC1A",
                                        'color_icon' => "#1255cc",
                                        'title' => "Order Belum Di Proses",
                                        'unit' => "BCM",
                                        'permission_module' => 'dashboard_card_order_belum_proses'
                                    ),
                                    'order-23' => array(
                                        'code' => 'order-23',
                                        'img' => "bi bi-bag-dash-fill",
                                        'color_box' => "#1155CC1A",
                                        'color_icon' => "#1255cc",
                                        'title' => "Order Belum Dicairkan",
                                        'unit' => "BCM",
                                        'permission_module' => 'dashboard_card_order_belum_cairkan'
                                    ),
                                    'order-22' => array(
                                        'code' => 'order-22',
                                        'img' => "bi bi-bag-x",
                                        'color_box' => "#1155CC1A",
                                        'color_icon' => "#1255cc",
                                        'title' => "Belum Dicairkan",
                                        'unit' => "BCM",
                                        'permission_module' => 'dashboard_card_belum_cairkan'
                                    ),
                                    'order-5' => array(
                                        'code' => 'order-5',
                                        'img' => "bi bi-arrow-down-circle-fill",
                                        'color_box' => "#60BB551A",
                                        'color_icon' => "#60bb55",
                                        'title' => "Penjualan Kotor",
                                        'unit' => "BCM",
                                        'permission_module' => 'dashboard_card_penjualan_kotor'
                                    ),
                                    'order-6' => array(
                                        'code' => 'order-6',
                                        'img' => "bi bi-percent",
                                        'color_box' => "#60BB551A",
                                        'color_icon' => "#60bb55",
                                        'title' => "Diskon",
                                        'unit' => "BCM",
                                        'permission_module' => 'dashboard_card_diskon'
                                    ),
                                    'order-7' => array(
                                        'code' => 'order-7',
                                        'img' => "bi bi-check-circle-fill",
                                        'color_box' => "#60BB551A",
                                        'color_icon' => "#60bb55",
                                        'title' => "Penjualan Bersih",
                                        'unit' => "BCM",
                                        'permission_module' => 'dashboard_card_penjualan_bersih'
                                    ),
                                    'order-8' => array(
                                        'code' => 'order-8',
                                        'img' => "bi bi-calculator",
                                        'color_box' => "#60BB551A",
                                        'color_icon' => "#60bb55",
                                        'title' => "Laba Bersih",
                                        'unit' => "BCM",
                                        'permission_module' => 'dashboard_card_laba_bersih'
                                    ),
                                    'order-9' => array(
                                        'code' => 'order-9',
                                        'img' => "bi bi-house-exclamation-fill",
                                        'color_box' => "#fdf1f1",
                                        'color_icon' => "#ed7881",
                                        'title' => "Marketplace Fee",
                                        'unit' => "BCM",
                                        'permission_module' => 'dashboard_card_marketplace_fee'
                                    ),
                                    'order-11' => array(
                                        'code' => 'order-11',
                                        'img' => "bi bi-arrow-up-circle-fill",
                                        'color_box' => "#fdf1f1",
                                        'color_icon' => "#ed7881",
                                        'title' => "Pengeluaran",
                                        'unit' => "BCM",
                                        'permission_module' => 'dashboard_card_pengeluaran'
                                    ),
                                    'order-12' => array(
                                        'code' => 'order-12',
                                        'img' => "bi bi-box-seam",
                                        'color_box' => "#fdf1f1",
                                        'color_icon' => "#ed7881",
                                        'title' => "HPP Produk",
                                        'unit' => "BCM",
                                        'permission_module' => 'dashboard_card_hpp_produk'
                                    ),
                                    'order-4' => array(
                                        'code' => 'order-4',
                                        'img' => "bi bi-cart-x",
                                        'color_box' => "#fdf1f1",
                                        'color_icon' => "#ed7881",
                                        'title' => "Order Return",
                                        'unit' => "BCM",
                                        'permission_module' => 'dashboard_card_order_return'
                                    )
                                );

                                // Check permissions for each card and add to $sum if user has access
                                foreach ($available_cards as $card_key => $card_data) {
                                    if ($this->permission->check_permission($user_id, $card_data['permission_module'], 'view')) {
                                        $sum[$i] = $card_data;
                                        $i++;
                                    }
                                }
                                ?>

                                <?php if (empty($sum)): ?>
                                    <div class="col-12">
                                        <div class="card h-100" style="border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                            <div class="card-body text-center py-5">
                                                <i class="bi bi-info-circle text-muted" style="font-size: 3rem;"></i>
                                                <h5 class="text-muted mt-3 mb-2">No Data Available</h5>
                                                <p class="text-muted mb-0">Contact admin for information card access permissions.</p>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>

                                    <?php foreach ($sum as $k => $v) {
                                        $v['title'] = strtoupper($v['title']);
                                    ?>

                                        <div class="text-start mb-4 col-md-3 col-6 loading-metric" data-metric="<?= $v['code'] ?>">
                                            <a href="#!"
                                                <?php if ($v['code'] === 'order-11') { ?>
                                                id="order-11-card"
                                                <?php } elseif ($v['code'] === 'order-9') { ?>
                                                id="order-9-card"
                                                <?php } elseif ($v['code'] === 'order-8') { ?>
                                                id="order-8-card"
                                                <?php } ?>
                                                <?php if ($v['code'] === 'order-12') { ?>
                                                id="order-12-card"
                                                <?php } ?>
                                                <?php if ($v['code'] === 'order-23') { ?>
                                                id="order-23-card"
                                                <?php } ?>
                                                class="a-none text-black">
                                                <div class="card h-100 card-hover" style="border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
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
                                                                    <h6 class="fw-400 mb-0 text-end" id="progress-<?= $v['code'] ?>">
                                                                        <div class="d-flex align-items-center justify-content-end">
                                                                            <div class="spinner-border spinner-border-sm text-secondary me-2" role="status" style="width: 1rem; height: 1rem;">
                                                                                <span class="visually-hidden">Loading...</span>
                                                                            </div>
                                                                            <span class="text-muted small">Loading...</span>
                                                                        </div>
                                                                    </h6>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>

                                        <script>
                                            document.addEventListener('DOMContentLoaded', function() {
                                                const order12Card = document.getElementById('order-12-card');
                                                const order11Card = document.getElementById('order-11-card');
                                                const order8Card = document.getElementById('order-8-card');
                                                const order9Card = document.getElementById('order-9-card');
                                                const order23Card = document.getElementById('order-23-card');

                                                if (order11Card) {
                                                    order11Card.addEventListener('click', function(e) {
                                                        e.preventDefault();
                                                        e.stopPropagation();

                                                        const brandElement = document.getElementById('brand');
                                                        const brand = brandElement ? brandElement.value : '';
                                                        const startDate = '<?= $start_date ?>';
                                                        const untilDate = '<?= $until_date ?>';

                                                        let url = `<?= base_url() ?>dashboard/expense?start_date=${startDate}&until_date=${untilDate}`;
                                                        if (brand) {
                                                            url += `&brand=${brand}`;
                                                        }

                                                        window.location.href = url;
                                                    });
                                                }

                                                if (order8Card) {
                                                    order8Card.addEventListener('click', function(e) {
                                                        e.preventDefault();
                                                        e.stopPropagation();

                                                        const brandElement = document.getElementById('brand');
                                                        const brand = brandElement ? brandElement.value : '';
                                                        const startDate = '<?= $start_date ?>';
                                                        const untilDate = '<?= $until_date ?>';

                                                        let url = `<?= base_url() ?>dashboard/laba_bersih?start_date=${startDate}&until_date=${untilDate}`;
                                                        if (brand) {
                                                            url += `&brand=${brand}`;
                                                        }

                                                        window.location.href = url;
                                                    });
                                                }

                                                if (order9Card) {
                                                    order9Card.addEventListener('click', function(e) {
                                                        e.preventDefault();
                                                        e.stopPropagation();

                                                        const brandElement = document.getElementById('brand');
                                                        const brand = brandElement ? brandElement.value : '';
                                                        const startDate = '<?= $start_date ?>';
                                                        const untilDate = '<?= $until_date ?>';

                                                        let url = `<?= base_url() ?>dashboard/marketplace_fee?start_date=${startDate}&until_date=${untilDate}`;
                                                        if (brand) {
                                                            url += `&brand=${brand}`;
                                                        }

                                                        window.location.href = url;
                                                    });
                                                }

                                                if (order12Card) {
                                                    order12Card.addEventListener('click', function(e) {
                                                        e.preventDefault();
                                                        e.stopPropagation();

                                                        const brandElement = document.getElementById('brand');
                                                        const brand = brandElement ? brandElement.value : '';
                                                        const startDate = '<?= $start_date ?>';
                                                        const untilDate = '<?= $until_date ?>';

                                                        let url = `<?= base_url() ?>dashboard/hpp?start_date=${startDate}&until_date=${untilDate}`;
                                                        if (brand) {
                                                            url += `&brand=${brand}`;
                                                        }

                                                        window.location.href = url;
                                                    });
                                                }

                                                if (order23Card) {
                                                    order23Card.addEventListener('click', function(e) {
                                                        e.preventDefault();
                                                        e.stopPropagation();

                                                        const brandElement = document.getElementById('brand');
                                                        const brand = brandElement ? brandElement.value : '';
                                                        const startDate = '<?= $start_date ?>';
                                                        const untilDate = '<?= $until_date ?>';

                                                        let url = `<?= base_url() ?>transaction?pencairan=Belum Pencairan&start_date=${startDate}&until_date=${untilDate}`;
                                                        if (brand) {
                                                            url += `&brand=${brand}`;
                                                        }

                                                        window.location.href = url;
                                                    });
                                                }
                                            });
                                        </script>



                                        <script>
                                            // Individual AJAX calls are now batched - see batch script at end of carousel
                                            window.firstCarouselMetrics = window.firstCarouselMetrics || [];
                                            window.firstCarouselMetrics.push('<?= $v['code'] ?>');
                                        </script>
                                    <?php } ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Batch AJAX call moved to tab manager - triggered by first tab activation -->

                    <div class="item">

                        <div class="col-lg-12 mt-3">
                            <div class="row">
                                <?php
                                $sum = array();
                                $i = 0;
                                $user_id = $_SESSION['user']['id'];

                                // Define additional dashboard cards with their permission mappings
                                $additional_cards = array(
                                    'order-13' => array(
                                        'code' => 'order-13',
                                        'img' => "bi bi-box-seam-fill",
                                        'color_box' => "#1155CC1A",
                                        'color_icon' => "#1255cc",
                                        'title' => "Nilai Produk",
                                        'unit' => "BCM",
                                        'permission_module' => 'dashboard_card_nilai_produk'
                                    ),
                                    'order-14' => array(
                                        'code' => 'order-14',
                                        'img' => "bi bi-truck",
                                        'color_box' => "#fdf1f1",
                                        'color_icon' => "#ed7881",
                                        'title' => "Ongkir",
                                        'unit' => "BCM",
                                        'permission_module' => 'dashboard_card_ongkir'
                                    ),
                                    'order-16' => array(
                                        'code' => 'order-16',
                                        'img' => "bi bi-cart-x",
                                        'color_box' => "#fdf1f1",
                                        'color_icon' => "#ed7881",
                                        'title' => "Penjualan Return",
                                        'unit' => "BCM",
                                        'permission_module' => 'dashboard_card_penjualan_return'
                                    )
                                );

                                // Check permissions for each additional card and add to $sum if user has access
                                foreach ($additional_cards as $card_key => $card_data) {
                                    if ($this->permission->check_permission($user_id, $card_data['permission_module'], 'view')) {
                                        $sum[$i] = $card_data;
                                        $i++;
                                    }
                                }

                                ?>
                                <?php if (empty($sum)): ?>
                                    <div class="col-12">
                                        <div class="card h-100" style="border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                            <div class="card-body text-center py-5">
                                                <i class="bi bi-info-circle text-muted" style="font-size: 3rem;"></i>
                                                <h5 class="text-muted mt-3 mb-2">No Data Available</h5>
                                                <p class="text-muted mb-0">Contact admin for information card access permissions.</p>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($sum as $k => $v) {
                                        $v['title'] = strtoupper($v['title']);
                                    ?>
                                        <div class="text-start mb-4 col-md-3 col-6 loading-metric" data-metric="<?= $v['code'] ?>">
                                            <a href="#!" class="a-none text-black" onclick="refresh_chart('<?= $v['code'] ?>','<?= $v['title'] ?>')">
                                                <div class="card h-100 card-hover" style="border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
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
                                                                    <h6 class="fw-400 mb-0 text-end" id="progress-<?= $v['code'] ?>">
                                                                        <div class="d-flex align-items-center justify-content-end">
                                                                            <div class="spinner-border spinner-border-sm text-secondary me-2" role="status" style="width: 1rem; height: 1rem;">
                                                                                <span class="visually-hidden">Loading...</span>
                                                                            </div>
                                                                            <span class="text-muted small">Loading...</span>
                                                                        </div>
                                                                    </h6>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                        <script>
                                            // Individual AJAX calls are now batched - see batch script at end of carousel
                                            window.firstCarouselMetrics = window.firstCarouselMetrics || [];
                                            window.firstCarouselMetrics.push('<?= $v['code'] ?>');
                                        </script>
                                    <?php } ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Laporan Order Tab -->

            <!-- Grafik Order & Channel Metrics Tab -->
            <div class="tab-pane fade" id="grafik-panel" role="tabpanel" aria-labelledby="grafik-tab">
                <div class="grafik-loading-skeleton" style="display: none;">
                    <div class="card h-100 summary mb-2">
                        <div class="card-body">
                            <div class="skeleton-placeholder" style="height: 25px; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); margin-bottom: 15px;"></div>
                            <div class="skeleton-placeholder" style="height: 300px; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);"></div>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-12 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="skeleton-placeholder" style="height: 50px; width: 50px; border-radius: 20%; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); margin-bottom: 15px;"></div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="skeleton-placeholder" style="height: 20px; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); margin-bottom: 10px;"></div>
                                            <div class="skeleton-placeholder" style="height: 30px; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);"></div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="skeleton-placeholder" style="height: 20px; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); margin-bottom: 10px;"></div>
                                            <div class="skeleton-placeholder" style="height: 30px; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);"></div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="skeleton-placeholder" style="height: 20px; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); margin-bottom: 10px;"></div>
                                            <div class="skeleton-placeholder" style="height: 30px; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="grafik-content" style="display: none;">
                    <div class="col-lg-12 mt-3 mb-3">
                        <div class="card h-100  summary mb-2">

                            <h4 class="text-primary fw-500 mb-1">GRAFIK ORDER</h4>
                            <p class="mb-2"><?= $title_2 ?></p>
                            <div class="col-md-12">
                                <div class="d-grid d-md-flex d-lg-flex">
                                    <?php
                                    $arr = array();
                                    $arr[] = "Jumlah Order";
                                    $arr[] = "Penjualan Kotor";
                                    $arr[] = "Penjualan Bersih";
                                    $arr[] = "Laba Bersih";
                                    $arr[] = "Pengeluaran";
                                    foreach ($arr as $k => $v) {
                                        $text = "";
                                        if ($checkbox[$k] == 'true') {
                                            $text = "checked";
                                        }
                                        if (empty($checkbox)) {
                                            $text = "checked";
                                        }
                                    ?>
                                        <input onclick="checkbox()" <?= $text ?> type="checkbox" id="c-<?= $k ?>" class="me-2 c-checkbox"><label for="c-<?= $k ?>" class="fw-400 me-2"><?= $v ?></label>
                                    <?php } ?>
                                </div>
                            </div>
                            <div id="get-chart">
                                <div class="d-flex align-items-center justify-content-center p-3">
                                    <div class="spinner-border text-primary me-3" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <span class="text-muted">Memuat data...</span>
                                </div>
                            </div>
                            <div id="get-table">
                            </div>
                            <script>
                                function refresh_chart(code, title) {
                                    $.ajax({
                                        dataType: "json",
                                        url: '<?= base_url() ?>ajax/get-chart?type=<?= $type ?>&brand=<?= $_GET['brand'] ?>&channel=<?= $_GET['channel'] ?>&start_date=<?= $start_date ?>&until_date=<?= $until_date ?>&start_year=<?= $start_year ?>&until_year=<?= $until_year ?>&start_month=<?= $start_month ?>&until_month=<?= $until_month ?>&start_week=<?= $start_week ?>&until_week=<?= $until_week ?>&code=' + code + '&title=' + title,
                                        success: function(html) {
                                            $("#get-chart").html(html.html);
                                            $("#get-table").html(html.table);
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
                                    $.ajax({
                                        type: "GET",
                                        dataType: "json",
                                        url: '<?= base_url() ?>ajax/checkbox?type=dashboard&' + queryParams,
                                        success: function(response) {
                                            refresh_chart('<?= $sum[0]['code'] ?>', '<?= $sum[0]['title'] ?>');
                                        }
                                    });
                                }
                                // Note: chart loading will be triggered by tab activation
                            </script>
                        </div>
                    </div>

                    <!-- Channel Metrics Section -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h4 class="text-primary fw-500 mb-3">CHANNEL METRICS</h4>
                        </div>
                    </div>

                    <?php foreach ($channel as $kk => $vv) {
                        if ($vv['img']) {
                            $vv['img'] = base_url() . '/assets/img/marketplace/' . $vv['img'];
                        } else {
                            $vv['img'] = base_url() . '/assets/img/marketplace/default.png';
                        }

                    ?>

                        <div class="col-lg-12 mt-3 mb-3 card">
                            <div class="row">
                                <div class="col-lg-12 mb-2">
                                    <p class="mb-1 fw-500"><img src="<?= $vv['img'] ?>" alt="" style="width:50px;height:50px;border-radius:20%"></p>
                                </div>
                            </div>
                            <div class="row ">
                                <?php
                                $sum = array();
                                $i = 0;
                                $sum[$i]['code'] = 'order-17';
                                $sum[$i]['img'] = "bi bi-cart-fill";
                                $sum[$i]['color_box'] = "#1155CC1A";
                                $sum[$i]['color_icon'] = "#1255cc";
                                $sum[$i]['title'] = "Omset Bersih";
                                $sum[$i]['unit'] = "BCM";
                                $i++;
                                $sum[$i]['code'] = 'order-18';
                                $sum[$i]['img'] = "bi bi-cart-dash";
                                $sum[$i]['color_box'] = "#1155CC1A";
                                $sum[$i]['color_icon'] = "#1255cc";
                                $sum[$i]['title'] = "Variabel Beban";
                                $sum[$i]['unit'] = "BCM";
                                $i++;
                                $sum[$i]['code'] = 'order-19';
                                $sum[$i]['img'] = "bi bi-credit-card-fill";
                                $sum[$i]['color_box'] = "#1155CC1A";
                                $sum[$i]['color_icon'] = "#1255cc";
                                $sum[$i]['title'] = "Penghasilan Yg Diterima";
                                $sum[$i]['unit'] = "BCM";
                                $i++;
                                // $sum[$i]['code'] = 'order-20';
                                // $sum[$i]['img'] = "bi bi-boxes";
                                // $sum[$i]['color_box'] = "#1155CC1A";
                                // $sum[$i]['color_icon'] = "#1255cc";
                                // $sum[$i]['title'] = "Jumlah Order";
                                // $sum[$i]['unit'] = "BCM";
                                ?>
                                <?php foreach ($sum as $k => $v) {
                                    $v['title'] = strtoupper($v['title']);
                                ?>
                                    <div class="text-start mb-4 col-md-4">
                                        <p class="fw-500 mb-1 text-start"><?= $v['title'] ?></p>
                                        <h4 class="fw-500 mb-1 text-start" id="summary-<?= $v['code'] ?>-<?= $kk ?>">
                                            <div class="d-flex align-items-center justify-content-start">
                                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status" style="width: 1.2rem; height: 1.2rem;">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                <span class="text-muted">Loading...</span>
                                            </div>
                                        </h4>
                                        <h6 class="fw-400 mb-0 text-start" id="progress-<?= $v['code'] ?>-<?= $kk ?>">
                                            <div class="d-flex align-items-center justify-content-start">
                                                <div class="spinner-border spinner-border-sm text-secondary me-2" role="status" style="width: 1rem; height: 1rem;">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                <span class="text-muted small">Loading...</span>
                                            </div>
                                        </h6>
                                        <script>
                                            // Channel metrics will be loaded via tab activation
                                            window.channelMetrics = window.channelMetrics || [];
                                            window.channelMetrics.push({
                                                code: '<?= $v['code'] ?>',
                                                channel: '<?= $vv['name'] ?>',
                                                kk: '<?= $kk ?>'
                                            });
                                        </script>
                                        <?php if ($k == 0) {
                                            $v['title'] = "JUMLAH ORDER";
                                            $v['code'] = "order-20";
                                        ?>
                                            <p class="fw-500 mb-1 mt-2 text-start"><?= $v['title'] ?></p>
                                            <h4 class="fw-500 mb-1 text-start" id="summary-<?= $v['code'] ?>-<?= $kk ?>"><i class="fa fa-circle-o-notch fa-spin"></i></h4>
                                            <h6 class="fw-400 mb-0 text-start" id="progress-<?= $v['code'] ?>-<?= $kk ?>"><i class="fa fa-circle-o-notch fa-spin"></i></h6>
                                            <script>
                                                window.channelMetrics = window.channelMetrics || [];
                                                window.channelMetrics.push({
                                                    code: '<?= $v['code'] ?>',
                                                    channel: '<?= $vv['name'] ?>',
                                                    kk: '<?= $kk ?>'
                                                });
                                            </script>
                                        <?php } ?>
                                        <?php if ($k == 2) {
                                            $v['title'] = "ORDER DICAIRKAN";
                                            $v['code'] = "order-21";
                                        ?>
                                            <p class="fw-500 mb-1 mt-2 text-start"><?= $v['title'] ?></p>
                                            <h4 class="fw-500 mb-1 text-start" id="summary-<?= $v['code'] ?>-<?= $kk ?>"><i class="fa fa-circle-o-notch fa-spin"></i></h4>
                                            <h6 class="fw-400 mb-0 text-start" id="progress-<?= $v['code'] ?>-<?= $kk ?>"><i class="fa fa-circle-o-notch fa-spin"></i></h6>
                                            <script>
                                                window.channelMetrics = window.channelMetrics || [];
                                                window.channelMetrics.push({
                                                    code: '<?= $v['code'] ?>',
                                                    channel: '<?= $vv['name'] ?>',
                                                    kk: '<?= $kk ?>'
                                                });
                                            </script>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>

                    <?php } ?>

                </div>
            </div>
            <!-- End Grafik Order & Channel Metrics Tab -->

        </div>
        <!-- End Tab Content -->


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

                    var requestsToAbort = this.activeRequests.slice(); // Create a copy
                    this.activeRequests = []; // Clear the array first

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

            // Intercept jQuery AJAX to track requests (with safety checks)
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

            // Dashboard Tab Management System
            const dashboardTabManager = {
                loadedTabs: new Set(['laporan-panel']), // First tab loads immediately
                isLoading: new Set(),

                init: function() {
                    // Initialize owl carousel for laporan tab
                    this.initCarousel();

                    // Load first tab content immediately
                    this.loadLaporanTab();

                    // Setup tab event listeners
                    this.setupTabListeners();
                },

                initCarousel: function() {
                    $('#laporan-carousel').owlCarousel({
                        loop: false,
                        margin: 10,
                        responsiveClass: true,
                        responsive: {
                            0: {
                                items: 1,
                                nav: false
                            },
                            600: {
                                items: 1,
                                nav: false
                            },
                            1000: {
                                items: 1,
                                loop: false,
                            }
                        }
                    });
                },

                setupTabListeners: function() {
                    // Tab switch event listener
                    $('#dashboardTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', (e) => {
                        const targetTab = $(e.target).attr('data-bs-target');
                        const tabId = targetTab.replace('#', '');


                        // Load tab content if not already loaded
                        if (!this.loadedTabs.has(tabId) && !this.isLoading.has(tabId)) {
                            this.loadTabContent(tabId);
                        }
                    });
                },

                loadTabContent: function(tabId) {
                    this.isLoading.add(tabId);

                    switch (tabId) {
                        case 'grafik-panel':
                            this.loadGrafikAndChannelTab();
                            break;
                        default:
                            console.log('Unknown tab:', tabId);
                    }
                },

                loadLaporanTab: function() {
                    // Show content and hide skeleton
                    $('#laporan-panel .laporan-loading-skeleton').hide();
                    $('#laporan-carousel').show();

                    // Execute progressive loading for laporan metrics (show each as ready)
                    if (window.firstCarouselMetrics && window.firstCarouselMetrics.length > 0) {
                        this.executeProgressiveLoad(window.firstCarouselMetrics);
                    }

                    this.loadedTabs.add('laporan-panel');
                },

                loadGrafikAndChannelTab: function() {
                    const self = this;

                    // Show loading skeleton
                    $('#grafik-panel .grafik-loading-skeleton').show();
                    $('#grafik-panel .grafik-content').hide();

                    // Load both chart and channel metrics
                    setTimeout(() => {
                        // Load channel metrics first (batch approach)
                        if (window.channelMetrics && window.channelMetrics.length > 0) {
                            this.loadChannelMetrics(window.channelMetrics);
                        }

                        // Show content after brief delay
                        setTimeout(() => {
                            $('#grafik-panel .grafik-loading-skeleton').hide();
                            $('#grafik-panel .grafik-content').show();

                            // Trigger chart loading after content is visible
                            if (typeof refresh_chart === 'function') {
                                refresh_chart('<?= $sum[0]['code'] ?>', '<?= $sum[0]['title'] ?>');
                            }

                            self.loadedTabs.add('grafik-panel');
                            self.isLoading.delete('grafik-panel');
                        }, 800);
                    }, 300);
                },

                loadChannelMetrics: function(metrics) {
                    // Group channel metrics by channel for batch processing
                    const channelGroups = {};
                    metrics.forEach(metric => {
                        if (!channelGroups[metric.channel]) {
                            channelGroups[metric.channel] = [];
                        }
                        channelGroups[metric.channel].push(metric);
                    });

                    // Process each channel's metrics as a batch
                    Object.keys(channelGroups).forEach(channel => {
                        const channelMetrics = channelGroups[channel];
                        const metricIds = channelMetrics.map(m => m.code);
                        const metricIdString = metricIds.join(',');

                        $.ajax({
                            dataType: "json",
                            url: '<?= base_url() ?>ajax/get_summary_batch',
                            data: {
                                metric_ids: metricIdString,
                                channel: channel,
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
                                    // Update each channel metric's display
                                    channelMetrics.forEach(metric => {
                                        const metricData = response.data[metric.code];
                                        if (metricData) {
                                            if (metricData.html !== undefined) {
                                                $("#summary-" + metric.code + "-" + metric.kk).html(metricData.html);
                                            }
                                            if (metricData.progress !== undefined) {
                                                $("#progress-" + metric.code + "-" + metric.kk).html(metricData.progress);
                                            }
                                        }
                                    });
                                } else {
                                    // Fallback to individual calls
                                    channelMetrics.forEach(metric => {
                                        dashboardTabManager.loadIndividualChannelMetric(metric);
                                    });
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('Channel batch AJAX error:', error);
                                // Fallback to individual calls
                                channelMetrics.forEach(metric => {
                                    dashboardTabManager.loadIndividualChannelMetric(metric);
                                });
                            }
                        });
                    });
                },

                loadIndividualChannelMetric: function(metric) {
                    $.ajax({
                        dataType: "json",
                        url: '<?= base_url() ?>ajax/get_summary',
                        data: {
                            channel: metric.channel,
                            id: metric.code,
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
                            $("#summary-" + metric.code + "-" + metric.kk).html(html.html);
                            $("#progress-" + metric.code + "-" + metric.kk).html(html.progress);
                        },
                        error: function(xhr, status, error) {
                            console.error('Error loading individual channel metric:', metric, error);
                        }
                    });
                },

                executeProgressiveLoad: function(metricIds) {
                    const self = this;
                    let completedCount = 0;
                    const totalCount = metricIds.length;

                    // Show progress indicator
                    const loadingIndicator = $('<div class="batch-loading-indicator"><i class="fa fa-spinner fa-spin"></i> Loading metrics: 0/' + totalCount + '</div>');
                    $('body').append(loadingIndicator);


                    // Load each metric individually and immediately show results
                    metricIds.forEach(function(metricId, index) {
                        // Add small stagger to prevent overwhelming the server
                        setTimeout(() => {
                            self.loadIndividualMetricProgressive(metricId, function(success) {
                                completedCount++;

                                // Update progress indicator
                                if (success) {
                                    loadingIndicator.html('<i class="fa fa-spinner fa-spin"></i> Loading metrics: ' + completedCount + '/' + totalCount);
                                }

                                // All metrics completed
                                if (completedCount >= totalCount) {
                                    loadingIndicator.removeClass('error').addClass('success').html('<i class="fa fa-check"></i> All ' + totalCount + ' metrics loaded!');

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
                        url: '<?= base_url() ?>ajax/get_summary',
                        data: {
                            site: '<?= $site ?>',
                            id: metricId,
                            brand: '<?= $_GET['brand'] ?>',
                            channel: '<?= $_GET['channel'] ?>',
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
                            if (html.progress !== undefined) {
                                $("#progress-" + metricId).html(html.progress);
                            }

                            // Add smooth fade-in animation
                            const metricCard = $('[data-metric="' + metricId + '"]');
                            metricCard.removeClass('loading-metric').addClass('loaded-metric metric-loaded');

                            callback(true);
                        },
                        error: function(xhr, status, error) {
                            console.error('Error loading metric:', metricId, error);
                            callback(false);
                        }
                    });
                },

                // Keep batch method as fallback
                executeBatchRequest: function(metricIds) {
                    const self = this;
                    const metricIdString = metricIds.join(',');

                    // Show loading indicator
                    const loadingIndicator = $('<div class="batch-loading-indicator"><i class="fa fa-spinner fa-spin"></i> Loading dashboard metrics (' + metricIds.length + ')...</div>');
                    $('body').append(loadingIndicator);

                    $.ajax({
                        dataType: "json",
                        url: '<?= base_url() ?>ajax/get_summary_batch',
                        data: {
                            metric_ids: metricIdString,
                            site: '<?= $site ?>',
                            brand: '<?= $_GET['brand'] ?>',
                            channel: '<?= $_GET['channel'] ?>',
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
                                    if (metricData.progress !== undefined) {
                                        $("#progress-" + metricId).html(metricData.progress);
                                    }
                                });

                                // Show success indicator with timing info
                                const timing = response.cached ? ' (cached)' : ` (${response.execution_time}s)`;
                                loadingIndicator.removeClass('error').addClass('success').html('<i class="fa fa-check"></i> Loaded ' + response.processed_count + ' metrics' + timing);

                                if (response.errors && Object.keys(response.errors).length > 0) {
                                    console.warn('Some metrics failed to load:', response.errors);
                                    loadingIndicator.html('<i class="fa fa-exclamation-triangle"></i> ' + response.processed_count + ' loaded, ' + Object.keys(response.errors).length + ' failed');
                                }

                                // Hide indicator after 3 seconds to show timing
                                setTimeout(function() {
                                    loadingIndicator.fadeOut(300, function() {
                                        $(this).remove();
                                    });
                                }, 3000);
                            } else {
                                console.error('Batch request failed:', response);
                                loadingIndicator.addClass('error').html('<i class="fa fa-times"></i> Batch failed, using fallback');

                                // Fallback to individual calls if batch fails
                                metricIds.forEach(function(metricId) {
                                    self.loadIndividualMetric(metricId);
                                });

                                setTimeout(function() {
                                    loadingIndicator.fadeOut(300, function() {
                                        $(this).remove();
                                    });
                                }, 3000);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Batch AJAX error:', error);
                            loadingIndicator.addClass('error').html('<i class="fa fa-times"></i> Network error, using fallback');

                            // Fallback to individual calls
                            metricIds.forEach(function(metricId) {
                                self.loadIndividualMetric(metricId);
                            });

                            setTimeout(function() {
                                loadingIndicator.fadeOut(300, function() {
                                    $(this).remove();
                                });
                            }, 3000);
                        }
                    });
                },

                loadIndividualMetric: function(metricId) {
                    $.ajax({
                        dataType: "json",
                        url: '<?= base_url() ?>ajax/get_summary',
                        data: {
                            site: '<?= $site ?>',
                            id: metricId,
                            brand: '<?= $_GET['brand'] ?>',
                            channel: '<?= $_GET['channel'] ?>',
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
                            $("#summary-" + metricId).html(html.html);
                            $("#progress-" + metricId).html(html.progress);
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

                    // Stop any ongoing carousel operations
                    try {
                        $('#laporan-carousel').trigger('stop.owl.carousel');
                    } catch (e) {}

                    // Remove event listeners to prevent memory leaks
                    $('#dashboardTabs button[data-bs-toggle=\"tab\"]').off('shown.bs.tab');

                }
            };

            // Expose dashboardTabManager globally for cleanup
            window.dashboardTabManager = dashboardTabManager;

            // Initialize dashboard tab manager when document is ready
            $(document).ready(function() {
                dashboardTabManager.init();
            });

            document.addEventListener('DOMContentLoaded', function() {
                const formatCurrency = (num) => {
                    if (num === null || num === undefined) return '0';
                    num = Math.round(parseFloat(num));
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                };

                tippy('#order-11-card', {
                    content: '<div class="p-2"><div class="text-center py-2"><i class="fa fa-spinner fa-spin"></i> Memuat data...</div></div>',
                    allowHTML: true,
                    interactive: true,
                    placement: 'right',
                    theme: 'light',
                    maxWidth: 350,
                    onShow(instance) {
                        const brand = document.getElementById('brand')?.value || '';
                        const startDate = '<?= $start_date ?>';
                        const untilDate = '<?= $until_date ?>';

                        $.ajax({
                            url: '<?= base_url() ?>dashboard/expense_data',
                            type: 'GET',
                            data: {
                                start_date: startDate,
                                until_date: untilDate,
                                brand
                            },
                            dataType: 'json',
                            success: function(data) {
                                if (!data || !data.spend_ads || !data.spend_kol || !data.spend_etc) {
                                    instance.setContent('<div class="p-2 text-danger">Data tidak valid.</div>');
                                    return;
                                }

                                // Ambil Net Sales dulu
                                $.ajax({
                                    url: '<?= base_url() ?>dashboard/net_sales_data',
                                    type: 'GET',
                                    data: {
                                        start_date: startDate,
                                        until_date: untilDate,
                                        brand
                                    },
                                    dataType: 'json',
                                    success: function(netSalesData) {
                                        const netSales = parseFloat(netSalesData.net_sales) || 0;

                                        const totalAds = Math.round(parseFloat(data.spend_ads.total_spend_ads)) || 0;
                                        const totalKol = Math.round(parseFloat(data.spend_kol.total_spend_kol)) || 0;
                                        const totalEtc = Math.round(parseFloat(data.spend_etc.total_spend_etc)) || 0;
                                        const totalMarketing = Math.round(parseFloat(data.spend_marketing?.total_spend_marketing)) || 0;
                                        const allSpend = totalAds + totalKol + totalEtc + totalMarketing;

                                        const pct = (val, denom) => (denom > 0 ? ((val / denom) * 100).toFixed(2) : "0.00");

                                        const adsPercentage = pct(totalAds, netSales);
                                        const kolPercentage = pct(totalKol, netSales);
                                        const etcPercentage = pct(totalEtc, netSales);
                                        const marketingPercentage = pct(totalMarketing, netSales);
                                        const totalPercentage = pct(allSpend, netSales);

                                        // ====== (BARU) Render kategori dinamis ======
                                        const catRows = Array.isArray(data.expense_by_category) ? data.expense_by_category : [];
                                        // Batasi 6 teratas agar tooltip tetap ringkas
                                        const MAX_ROWS = 6;
                                        let shown = 0;
                                        let categoryHtml = '';
                                        for (const row of catRows) {
                                            if (shown >= MAX_ROWS) break;
                                            const name = (row.category || 'Lain-lain');
                                            const val = Math.round(parseFloat(row.total_spend) || 0);
                                            const pNet = pct(val, netSales);
                                            categoryHtml += `
                                        <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                                        <span class="text-secondary">${escapeHtml(name)}</span>
                                        <span>
                                            <span class="text-danger">-${formatCurrency(val)}</span>
                                            <span class="text-muted ms-1">(${pNet}%)</span>
                                        </span>
                                        </div>
                                    `;
                                            shown++;
                                        }
                                        if (catRows.length > MAX_ROWS) {
                                            const remaining = catRows.length - MAX_ROWS;
                                            categoryHtml += `<div class="text-muted" style="font-size:12px;">+${remaining} kategori lainnya</div>`;
                                        }
                                        if (!categoryHtml) {
                                            categoryHtml = `<div class="text-muted" style="font-size:12px;">Tidak ada data kategori.</div>`;
                                        }

                                        const tooltipContent = `
                                    <div class="report-card p-2" style="min-width:300px;line-height:1.5;font-size:13px;">
                                        <div class="fw-bold mb-1" style="display:flex;justify-content:space-between;">
                                        <span>Total Pengeluaran</span>
                                        <span>
                                            <span class="text-danger fw-bolder">-${formatCurrency(allSpend)}</span>
                                            <span class="text-muted ms-2">(${totalPercentage}%)</span>
                                        </span>
                                        </div>
                                        <div style="border-top:1px solid #ccc;margin:4px 0;"></div>

                                        <div style="display:flex;justify-content:space-between;margin-bottom:5px;">
                                        <span class="text-secondary fw-bold">Ads</span>
                                        <span>
                                            <span class="text-danger fw-bold">-${formatCurrency(totalAds)}</span>
                                            <span class="text-muted ms-2">(${adsPercentage}%)</span>
                                        </span>
                                        </div>
                                        <div style="display:flex;justify-content:space-between;margin-bottom:5px;">
                                        <span class="text-secondary fw-bold">KOL</span>
                                        <span>
                                            <span class="text-danger fw-bold">-${formatCurrency(totalKol)}</span>
                                            <span class="text-muted ms-2">(${kolPercentage}%)</span>
                                        </span>
                                        </div>

                                        <div style="border-top:1px dashed #ddd;margin:8px 0 6px;"></div>
                                        <div class="fw-bold mb-1">Per Kategori</div>
                                        ${categoryHtml}

                                        <div class="text-end text-muted" style="font-size:11px;margin-top:6px;">
                                        ${startDate} - ${untilDate}
                                        </div>
                                    </div>
                                    `;
                                        instance.setContent(tooltipContent);
                                    },
                                    error: function() {
                                        // Fallback kalau net sales gagal
                                        const totalAds = Math.round(parseFloat(data.spend_ads.total_spend_ads)) || 0;
                                        const totalKol = Math.round(parseFloat(data.spend_kol.total_spend_kol)) || 0;
                                        const totalEtc = Math.round(parseFloat(data.spend_etc.total_spend_etc)) || 0;
                                        const totalMarketing = Math.round(parseFloat(data.spend_marketing?.total_spend_marketing)) || 0;
                                        const allSpend = totalAds + totalKol + totalEtc + totalMarketing;

                                        // Tetap tampilkan kategori walau tanpa persen (karena butuh netSales)
                                        const catRows = Array.isArray(data.expense_by_category) ? data.expense_by_category : [];
                                        const MAX_ROWS = 6;
                                        let shown = 0;
                                        let categoryHtml = '';
                                        for (const row of catRows) {
                                            if (shown >= MAX_ROWS) break;
                                            const name = (row.category || 'Lain-lain');
                                            const val = Math.round(parseFloat(row.total_spend) || 0);
                                            categoryHtml += `
                                        <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                                        <span class="text-secondary">${escapeHtml(name)}</span>
                                        <span class="text-danger">-${formatCurrency(val)}</span>
                                        </div>
                                    `;
                                            shown++;
                                        }
                                        if (catRows.length > MAX_ROWS) {
                                            const remaining = catRows.length - MAX_ROWS;
                                            categoryHtml += `<div class="text-muted" style="font-size:12px;">+${remaining} kategori lainnya</div>`;
                                        }
                                        if (!categoryHtml) {
                                            categoryHtml = `<div class="text-muted" style="font-size:12px;">Tidak ada data kategori.</div>`;
                                        }

                                        const tooltipContent = `
                                    <div class="report-card p-2" style="min-width:300px;line-height:1.5;font-size:13px;">
                                        <div class="fw-bold mb-1" style="display:flex;justify-content:space-between;">
                                        <span>Total Pengeluaran</span>
                                        <span class="text-danger fw-bolder">-${formatCurrency(allSpend)}</span>
                                        </div>
                                        <div style="border-top:1px solid #ccc;margin:4px 0;"></div>

                                        <div style="display:flex;justify-content:space-between;margin-bottom:5px;">
                                        <span class="text-secondary fw-bold">Ads</span>
                                        <span class="text-danger fw-bold">-${formatCurrency(totalAds)}</span>
                                        </div>
                                        <div style="display:flex;justify-content:space-between;margin-bottom:5px;">
                                        <span class="text-secondary fw-bold">KOL</span>
                                        <span class="text-danger fw-bold">-${formatCurrency(totalKol)}</span>
                                        </div>
                                        <div style="display:flex;justify-content:space-between;">
                                        <span class="text-secondary fw-bold">Lain-lain</span>
                                        <span class="text-danger fw-bold">-${formatCurrency(totalEtc)}</span>
                                        </div>

                                        <div style="border-top:1px dashed #ddd;margin:8px 0 6px;"></div>
                                        <div class="fw-bold mb-1">Per Kategori</div>
                                        ${categoryHtml}

                                        <div class="text-muted small mt-2">Persentase kategori tidak tersedia</div>
                                        <div class="text-end text-muted" style="font-size:11px;margin-top:6px;">
                                        ${startDate} - ${untilDate}
                                        </div>
                                    </div>
                                    `;
                                        instance.setContent(tooltipContent);
                                    }
                                });
                            },
                            error: function(xhr, status, error) {
                                console.error('Error:', error);
                                instance.setContent(`
                                <div class="p-2 text-danger">
                                    Gagal memuat data pengeluaran
                                    <div class="small mt-2">${error}</div>
                                </div>
                                `);
                            }
                        });

                        function escapeHtml(s) {
                            return String(s || '').replace(/[&<>"']/g, m => ({
                                '&': '&amp;',
                                '<': '&lt;',
                                '>': '&gt;',
                                '"': '&quot;',
                                "'": '&#39;'
                            } [m]));
                        }
                    }
                });



                tippy('#order-8-card', {
                    content: '<div class="p-2"><div class="text-center py-2"><i class="fa fa-spinner fa-spin"></i> Memuat data...</div></div>',
                    allowHTML: true,
                    interactive: true,
                    placement: 'right',
                    theme: 'light',
                    maxWidth: 350,
                    onShow(instance) {
                        const brand = document.getElementById('brand')?.value || '';
                        const startDate = '<?= $start_date ?>';
                        const untilDate = '<?= $until_date ?>';

                        $.ajax({
                            url: '<?= base_url() ?>dashboard/laba_bersih_data',
                            type: 'GET',
                            data: {
                                start_date: startDate,
                                until_date: untilDate,
                                brand: brand
                            },
                            dataType: 'json',
                            success: function(data) {
                                if (data && data.penjualan_bersih !== undefined && data.hpp !== undefined &&
                                    data.marketplace_fee !== undefined && data.pengeluaran !== undefined) {

                                    const penjualanBersih = Math.round(parseFloat(data.penjualan_bersih)) || 0;
                                    const hpp = Math.round(parseFloat(data.hpp)) || 0;
                                    const marketplaceFee = Math.round(parseFloat(data.marketplace_fee)) || 0;
                                    const pengeluaran = Math.round(parseFloat(data.pengeluaran)) || 0;
                                    const labaBersih = penjualanBersih - hpp - marketplaceFee - pengeluaran;

                                    const tooltipContent = `
                                    <div class="report-card p-2" style="min-width: 300px; line-height: 1.5; font-size: 13px;">
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                            <span class="text-secondary fw-bold">Penjualan Bersih</span>
                                            <span class="text-success fw-bold">${formatCurrency(penjualanBersih)}</span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                            <span class="text-secondary fw-bold">HPP</span>
                                            <span class="text-danger fw-bold">-${formatCurrency(hpp)}</span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                            <span class="text-secondary fw-bold">Marketplace Fee</span>
                                            <span class="text-danger fw-bold">-${formatCurrency(marketplaceFee)}</span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                            <span class="text-secondary fw-bold">Pengeluaran</span>
                                            <span class="text-danger fw-bold">-${formatCurrency(pengeluaran)}</span>
                                        </div>
                                        
                                        <div style="border-top: 1px solid #ccc; margin: 8px 0;"></div>
                                        
                                        <div class="fw-bold mb-1" style="display: flex; justify-content: space-between;">
                                            <span>Total Laba Bersih</span>
                                            <span class="${labaBersih >= 0 ? 'text-success' : 'text-danger'} fw-bolder">
                                                ${labaBersih >= 0 ? '' : '-'}${formatCurrency(Math.abs(labaBersih))}
                                            </span>
                                        </div>
                                        
                                        <div class="text-end text-muted" style="font-size: 11px; margin-top: 6px;">
                                            ${startDate} - ${untilDate}
                                        </div>
                                    </div>
                                `;
                                    instance.setContent(tooltipContent);
                                } else {
                                    throw new Error("Data tidak valid");
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('Error:', error);
                                instance.setContent(`
                                <div class="p-2 text-danger">
                                    Gagal memuat data laba bersih
                                    <div class="small mt-2">${error}</div>
                                </div>
                            `);
                            }
                        });
                    }
                });
            });
        </script>

        <style>
            .tippy-box[data-theme~='light'] {
                background-color: #ffffff !important;
                color: #333333 !important;
                box-shadow: 0 4px 14px rgba(0, 0, 0, 0.1) !important;
                border: 1px solid #e5e7eb !important;
            }

            .tippy-box[data-theme~='light'] .tippy-arrow {
                color: #ffffff !important;
            }

            .tippy-box[data-theme~='light'] .tippy-content {
                color: inherit !important;
            }
        </style>






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
                    <div class="d-flex align-items-center justify-content-center p-3">
                    <div class="spinner-border text-primary me-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span class="text-muted">Memuat data...</span>
                </div>
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


    </div>