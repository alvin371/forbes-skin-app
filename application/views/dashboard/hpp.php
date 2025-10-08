<?php
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$until_date = isset($_GET['until_date']) ? $_GET['until_date'] : '';
$selected_brand = isset($_GET['brand']) ? $_GET['brand'] : '';
$selected_jenis_produk = isset($_GET['jenis_produk']) ? $_GET['jenis_produk'] : '';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-1">
        <?php $this->load->view('dashboard/menu_hpp'); ?>
    </div>

    <form action="<?= $url ?>" method="GET">
        <div class="row">
            <div class="col-md-3">
                <select class="form-control select2" name="brand" id="brand">
                    <option value="">Semua Brand</option>
                    <?php foreach ($brands as $val) :
                        $selected = ($selected_brand == $val["code"]) ? "selected" : "";
                    ?>
                        <option <?= $selected ?> value="<?= $val["code"] ?>"><?= $val["code"] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-control select2" name="jenis_produk" id="jenis_produk">
                    <option value="Semua" <?= $selected_jenis_produk == 'Semua' ? 'selected' : '' ?>>Semua Jenis Produk</option>
                    <option value="Produk Jual" <?= $selected_jenis_produk == 'Produk Jual' ? 'selected' : '' ?>>Produk Jual</option>
                    <option value="Produk Operasional" <?= $selected_jenis_produk == 'Produk Operasional' ? 'selected' : '' ?>>Produk Operasional</option>
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

    <!-- Statistic Cards -->
    <div class="row mb-3">
        <?php
        // Calculate totals from HPP data
        $total_hpp = array_sum(array_column($hpp, 'total_hpp'));
        $total_persentase_hpp = array_sum(array_column($hpp, 'persentase_hpp'));
        
        // Calculate profit
        $profit = $net_sales - $total_hpp;
        
        // Calculate percentages (for comparison purposes - you might want to compare with previous period)
        // For now I'll just show the values without comparison
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
            
            .card.net-sales {
                border-color: #4e73df;
            }
            
            .card.hpp {
                border-color: #1cc88a;
            }
            
            .card.profit {
                border-color: #36b9cc;
            }

            .card.table {
                border-color: #fff;
            }
            
            .icon-container {
                width: 50px;
                height: 50px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
            }
        </style>

        <style>
            .sortable {
                cursor: pointer;
                position: relative;
                user-select: none;
            }

            .sortable:hover {
                background-color: #f8f9fa;
            }

            .sortable i {
                font-size: 0.8em;
                margin-left: 5px;
                opacity: 0.5;
            }

            .sortable.asc i, .sortable.desc i {
                opacity: 1;
            }

            .sortable.asc i {
                transform: rotate(180deg);
            }

            #hppTable th {
                white-space: nowrap;
            }
        </style>

        <div class="col-md-4 mt-2">
            <div class="card net-sales p-36 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <!-- Left Section -->
                    <div>
                        <h6 class="text-muted" style="font-size: 0.9rem; font-weight: 600; margin-bottom: 8px;">
                            Penjualan Bersih
                        </h6>
                        <h3 style="font-size: 1.6rem; font-weight: 700; margin-bottom: 8px; color: #172b4d;">
                            Rp <?= number_format($net_sales, 0, ',', '.') ?>
                        </h3>
                        <div class="d-flex justify-content-start align-items-center">
                            <small class="fw-bold text-muted">100%</small>
                            <i class="ms-2 bi bi-caret-right-fill text-start text-muted fw-bold"></i>
                        </div>
                    </div>

                    <!-- Right Section -->
                    <div>
                        <div class="icon-container" style="background-color: #4e73df;">
                            <i class="bi bi-cash-stack text-white" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mt-2">
            <div class="card hpp p-36 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <!-- Left Section -->
                    <div>
                        <h6 class="text-muted" style="font-size: 0.9rem; font-weight: 600; margin-bottom: 8px;">
                            Total HPP
                        </h6>
                        <h3 style="font-size: 1.6rem; font-weight: 700; margin-bottom: 8px; color: #172b4d;">
                            Rp <?= number_format($total_hpp, 0, ',', '.') ?>
                        </h3>
                        <div class="d-flex justify-content-start align-items-center">
                            <small class="fw-bold text-muted">
                                <?= $net_sales > 0 ? number_format(($total_hpp/$net_sales)*100, 2) : 0 ?>%
                            </small>
                            <i class="ms-2 bi bi-caret-right-fill text-start text-muted fw-bold"></i>
                        </div>
                    </div>

                    <!-- Right Section -->
                    <div>
                        <div class="icon-container" style="background-color: #1cc88a;">
                            <i class="bi bi-box-seam text-white" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mt-2">
            <div class="card profit p-36 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <!-- Left Section -->
                    <div>
                        <h6 class="text-muted" style="font-size: 0.9rem; font-weight: 600; margin-bottom: 8px;">
                            Profit
                        </h6>
                        <h3 style="font-size: 1.6rem; font-weight: 700; margin-bottom: 8px; color: #172b4d;">
                            Rp <?= number_format($profit, 0, ',', '.') ?>
                        </h3>
                        <div class="d-flex justify-content-start align-items-center">
                            <small class="fw-bold text-muted">
                                <?= $net_sales > 0 ? number_format(($profit/$net_sales)*100, 2) : '0' ?>%
                            </small>
                            <i class="ms-2 bi bi-caret-right-fill text-start text-muted fw-bold"></i>
                        </div>
                    </div>

                    <!-- Right Section -->
                    <div>
                        <div class="icon-container" style="background-color: #36b9cc;">
                            <i class="bi bi-graph-up text-white" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- HPP Table -->
    <div class="row g-4">
        <div class="col-12">
            <div class="card table">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="hppTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th class="sortable">Produk</th>
                                    <th class="sortable">Qty Out</th>
                                    <th class="sortable">HPP (per unit)</th>
                                    <th class="sortable">Total HPP</th>
                                    <th class="sortable">Persentase HPP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                $grand_total_hpp = 0;
                                foreach($hpp as $item): 
                                    $grand_total_hpp += $item['total_hpp'];
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td data-sort="<?= strtolower($item['name']) ?>">
                                        <?= $item['name'] ?>
                                    </td>
                                    <td data-sort="<?= $item['qty_out_pos'] + $item['qty_out'] ?>"><?= number_format($item['qty_out_pos'] + $item['qty_out'], 0, ',', '.') ?></td>
                                    <td data-sort="<?= $item['price_buy'] ?>"><?= 'Rp ' . number_format($item['price_buy'], 0, ',', '.') ?></td>
                                    <td data-sort="<?= $item['total_hpp'] ?>"><?= 'Rp ' . number_format($item['total_hpp'], 0, ',', '.') ?></td>
                                    <td data-sort="<?= $item['persentase_hpp'] ?>"><?= number_format($item['persentase_hpp'], 2) ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                                <tr class="fw-bold">
                                    <td colspan="4" class="text-end">Grand Total</td>
                                    <td><?= 'Rp ' . number_format($grand_total_hpp, 0, ',', '.') ?></td>
                                    <td><?= number_format($total_persentase_hpp, 2) ?>%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('hppTable');
    const headers = table.querySelectorAll('th.sortable'); // Hanya ambil header yang sortable
    const grandTotalRow = table.querySelector('tr.fw-bold');

    updateRowNumbers();

    headers.forEach(header => {
        header.innerHTML += ' <i class="bi bi-arrow-down-up"></i>';
        header.addEventListener('click', () => {
            sortTable(header);
        });
    });

    function sortTable(header) {
        const columnIndex = Array.from(header.parentNode.children).indexOf(header);
        const rows = Array.from(table.querySelectorAll('tbody tr:not(.fw-bold)'));
        const isAscending = !header.classList.contains('asc');

        headers.forEach(h => {
            h.classList.remove('asc', 'desc');
            h.querySelector('i').className = 'bi bi-arrow-down-up';
        });

        rows.sort((a, b) => {
            let aValue, bValue;

            if (a.children[columnIndex].hasAttribute('data-sort')) {
                aValue = a.children[columnIndex].getAttribute('data-sort');
                bValue = b.children[columnIndex].getAttribute('data-sort');
            } else {
                aValue = a.children[columnIndex].textContent.trim();
                bValue = b.children[columnIndex].textContent.trim();
            }

            if (columnIndex === 1) {
                return isAscending 
                    ? aValue.localeCompare(bValue, 'id', { sensitivity: 'base' })
                    : bValue.localeCompare(aValue, 'id', { sensitivity: 'base' });
            }

            const aNum = parseFloat(aValue.toString().replace(/[^\d.-]/g, ''));
            const bNum = parseFloat(bValue.toString().replace(/[^\d.-]/g, ''));

            if (!isNaN(aNum) && !isNaN(bNum)) {
                return isAscending ? aNum - bNum : bNum - aNum;
            }

            return isAscending 
                ? aValue.toString().localeCompare(bValue.toString())
                : bValue.toString().localeCompare(aValue.toString());
        });

        const tbody = table.querySelector('tbody');
        rows.forEach(row => tbody.insertBefore(row, grandTotalRow));

        updateRowNumbers();

        header.classList.add(isAscending ? 'asc' : 'desc');
        header.querySelector('i').className = isAscending 
            ? 'bi bi-arrow-up' 
            : 'bi bi-arrow-down';
    }

    function updateRowNumbers() {
        const rows = table.querySelectorAll('tbody tr:not(.fw-bold)');
        rows.forEach((row, index) => {
            row.cells[0].textContent = index + 1;
        });
    }
});

</script>