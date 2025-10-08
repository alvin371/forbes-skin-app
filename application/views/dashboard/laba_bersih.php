<?php
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$until_date = isset($_GET['until_date']) ? $_GET['until_date'] : '';
$selected_brand = isset($_GET['brand']) ? $_GET['brand'] : '';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary fw-600">Laporan Laba Bersih</h2>
    </div>

    <form action="<?= $url ?>" method="GET">
        <div class="row">
            <div class="col-md-3">
                <select class="form-control select2" name="brand" id="brand">
                    <option value="">Brand</option>
                    <?php foreach ($brands as $val) :
                        $selected = ($selected_brand == $val["code"]) ? "selected" : "";
                    ?>
                        <option <?= $selected ?> value="<?= $val["code"] ?>"><?= $val["code"] ?></option>
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

    <div class="row g-4">
        <div class="col-md-8">
            <div class="report-card">
                <?php
                $laba_bersih = (float)$penjualan_bersih - (float)$hpp - (float)$marketplace_fee - (float)$pengeluaran;
                ?>
                <p class="text-secondary fw-bold">
                    Penjualan Bersih
                    <span class="text-success fw-bold float-end">
                        <?= number_format((float)$penjualan_bersih, 0, ',', '.') ?>
                    </span>
                </p>

                <p class="text-secondary fw-bold">
                    HPP
                    <span class="text-danger fw-bold float-end">
                        -<?= number_format((float)$hpp, 0, ',', '.') ?>
                    </span>
                </p>

                <p class="text-secondary fw-bold">
                    Marketplace Fee
                    <span class="text-danger fw-bold float-end">
                        -<?= number_format((float)$marketplace_fee, 0, ',', '.') ?>
                    </span>
                </p>

                <a href="<?= base_url() ?>dashboard/expense?brand=<?= $selected_brand ?>&start_date=<?= $start_date ?>&until_date=<?= $until_date ?>"
                    class="text-secondary fw-bold">
                    Pengeluaran
                    <span class="text-danger fw-bold float-end">
                        -<?= number_format((float)$pengeluaran, 0, ',', '.') ?>
                    </span>
                </a>

                <hr>
                <h4 class="fw-bold">Total Laba Bersih <span class="text-success fw-bolder float-end"><?= number_format($laba_bersih, 0, ',', '.') ?></span></h4>
            </div>
        </div>

    </div>

    <style>
        .report-card {
            background-color: #fff;
            padding: 25px;
            border-radius: 12px;
        }
    </style>
</div>