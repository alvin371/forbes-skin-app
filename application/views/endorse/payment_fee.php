<?php
$url = base_url() . 'payment';
$start_date = !empty($_GET['start_date']) ? $_GET['start_date'] : date("Y-m-01");
$until_date = !empty($_GET['until_date']) ? $_GET['until_date'] : date('Y-m-d');
$endorse_status = $_GET['endorse_status'] ?? '';
$nama_creators = $_GET['nama_creator'] ?? [];
$pics = $_GET['pic'] ?? [];

?>
<?php
$filterParam = $_GET['endorse_status'] ?? null;
$filterApplied = ($filterParam !== null);
$selectedStatuses = ($filterApplied && $filterParam !== '') ? explode(',', $filterParam) : [];

$showPengajuanCols = (!$filterApplied) || in_array('', $selectedStatuses, true) || ($filterParam === '');

$showStatusPaymentCol = in_array('DP', $selectedStatuses, true) || in_array('FP', $selectedStatuses, true);

?>


<style>
    .tippy-box[data-theme~='light'] {
        background-color: #ffffff !important;
        color: #333333 !important;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.1) !important;
        border: 1px solid #e5e7eb !important;
        font-size: 12px !important;
    }

    .tippy-box[data-theme~='light'] .tippy-arrow {
        color: #ffffff !important;
    }

    .tippy-box[data-theme~='light'] .tippy-content {
        color: inherit !important;
    }

    body {
        overflow-x: hidden !important;
    }
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

    .table thead {
        z-index: 10 !important;
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

    .table tfoot.fixed {
        position: sticky;
        bottom: 0;
        background: #fff; /* warna latar supaya tidak tembus baris di bawah */
        z-index: 5;
    }

    .table tfoot.fixed td {
        border-top: 2px solid #333; /* biar kelihatan batasnya */
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

    .badge-status {
        display: inline-block;
        padding: 4px 10px;
        font-size: 12px;
        font-weight: 600;
        border-radius: 6px; /* sedikit rounded */
        color: #fff;
    }

    .badge-dp {
        background-color: #f6a623; /* oranye lembut */
    }
      
    .badge-fp {
        background-color: #27ae60; /* hijau lembut */
    }

    .badge-dpfp {
        background: linear-gradient(90deg, #f6a623, #27ae60); /* oranye→hijau */
    }


    .badge-default {
        background-color: #7f8c8d; /* abu-abu */
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .table-responsive {
            border-radius: 2px;
            overflow: hidden;
        }
    }
    @media (max-width: 576px) {
        .content-body {
            padding: 8px !important;   /* lebih kecil */
        }
    }
    .fp-status {
        background-color: #e6ffe6;
    }

    /* ===== Badge Link (tanpa underline) ===== */
    .badge-link {
        display: inline-block;
        padding: 4px 10px;
        font-size: 12px;
        font-weight: 600;
        border-radius: 999px;
        text-decoration: none !important;  /* no underline */
        color: #fff !important;
        cursor: pointer;
        line-height: 1.2;
    }
    .badge-link:hover { opacity: .9; }

    /* Variasi warna */
    .badge-creator { background: #1890ff; }     /* biru */
    .badge-campaign{ background: #722ed1; }     /* ungu */
    .badge-mou     { background: #27ae60; }     /* hijau */

    /* ===== Responsive Table Scroll di HP ===== */
    @media (max-width: 576px) {
    .table-responsive {
        overflow-x: auto;      /* scroll kiri-kanan */
        overflow-y: auto;      /* scroll atas-bawah */
        max-height: 70vh;      /* biar bisa scroll vertikal */
        -webkit-overflow-scrolling: touch;
    }

    /* Sembunyikan kolom pensil (aksi) */
    th.col-action,
    td.col-action {
        display: none !important;
    }

    /* Opsional: kecilkan padding biar muat */
    .table thead th,
    .table tbody td {
        padding: 10px 8px !important;
        font-size: 13px;
    }
    }

    /* indikator panah sort */
    th.sortable {
        cursor: pointer;
        position: relative;
        white-space: nowrap;
    }
    th.sortable:hover {
        background-color: #f1f1f1;
    }
    th.sortable i {
        font-size: 0.8em;
        margin-left: 5px;
        transition: transform 0.2s;
    }



</style>

<div class="container-fluid py-3">
    <?php $this->load->view('review_endorse/menu') ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">PAYMENT FEE INFLUENCER</h5>
                <div><?= $detail['title'] ?></div>
                <small><?= $detail['desc'] ?></small>
            </div>
            <div id="spend-kol-info" class="ms-3"></div>
        </div>


        <div class="card-body">
            <form action="<?= $url ?>" method="GET" class="mb-3">
                <input type="hidden" name="ids" value="<?= $ids ?>">
                <input type="hidden" name="id_campaign" value="<?= $detail['id'] ?>">
                <input type="hidden" name="endorse_status" value="<?= $endorse_status ?>">

                <div class="mb-3">
                    <?php
                    $arr = ["Pengajuan Payment", "DP", "FP"];

                    $selectedStatuses = isset($_GET['endorse_status']) && $_GET['endorse_status'] !== ''
                        ? explode(',', $_GET['endorse_status'])
                        : [];

                    foreach ($arr as $k => $val) {
                        $class = "btn-default";
                        $class_2 = "dot";
                        $value = ($k == 0) ? '' : $val;

                        if (($value === '' && empty($selectedStatuses)) || in_array($value, $selectedStatuses)) {
                            $class = "btn-default-selected";
                            $class_2 = "dot-active";
                        }

                        if ($value === '') {
                            $newSelected = [];
                        } elseif (in_array($value, $selectedStatuses)) {
                            $newSelected = array_diff($selectedStatuses, [$value]);
                        } else {
                            $newSelected = array_merge($selectedStatuses, [$value]);
                        }

                        $queryString = http_build_query(array_merge($_GET, [
                            'endorse_status' => implode(',', $newSelected)
                        ]));
                        ?>
                        <a href="<?= $url . '?' . $queryString ?>" class="btn <?= $class ?> mb-2 me-2">
                            <span class="<?= $class_2 ?>"></span> <?= $val ?>
                        </a>
                    <?php } ?>
                </div>



                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label>Creator</label>
                        <select class="form-control select2" name="nama_creator[]" multiple="multiple">
                            <?php foreach ($nama_creator as $val) :
                                $selected = in_array($val['nama_creator'], $nama_creators) ? "selected" : "";
                            ?>
                                <option <?= $selected ?> value="<?= $val["nama_creator"] ?>"><?= $val["nama_creator"] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>PIC</label>
                        <select class="form-control select2" name="pic[]" multiple="multiple">
                            <?php foreach ($pic as $val) :
                                $firstName = explode(' ', $val['pic'])[0];
                                $selected = in_array($firstName, $pics) ? "selected" : "";
                            ?>
                                <option <?= $selected ?> value="<?= $firstName ?>"><?= $firstName ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Tanggal TF</label>
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
                                    $('#tanggal').on('apply.daterangepicker', function() {
                                        table.ajax.reload();
                                    });
                                },
                                error: function(xhr, status, error) {
                                    console.error("Error loading filter:", error);
                                }
                            });
                        }
                    </script>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100 form-control">
                            <i class="bi bi-search"></i> Cari Data
                        </button>
                    </div>
                </div>
            </form>

            <?php
            $filterStatuses = isset($_GET['endorse_status']) && $_GET['endorse_status'] !== ''
                ? explode(',', $_GET['endorse_status'])
                : [];

            $showStatusPaymentColumn = in_array('DP', $filterStatuses) && in_array('FP', $filterStatuses);
            ?>


            <?php if (!empty($payment_fee)): ?>
                <div class="table-responsive" style="max-height: 550px;">
                    <table id="payment-table" class="table table-hover table-bordered">
                        <thead class="bg-light">
                            <tr>
                                <th style="width:28px">
                                <div class="checkbox-wrapper-13 d-inline">
                                    <input type="checkbox" id="checkAll" title="Pilih semua">
                                </div>
                                </th>
                                <th>No</th>
                                <th class="col-action">#</th>

                                <!-- CREATOR sortable by timestamp (pakai data-sort-value di <td>) -->
                                <th class="sortable" data-sort-type="number" data-col="nama_creator">
                                Creator
                                <i class="bi bi-arrow-down-up"></i>
                                </th>

                                <th>Campaign</th>
                                <th>No Rekening</th>

                                <?php if ($showPengajuanCols): ?>
                                <!-- ANGKA -->
                                <th class="sortable" data-sort-type="currency" data-col="nominal_pengajuan">
                                    Nominal Pengajuan
                                    <i class="bi bi-arrow-down-up"></i>
                                </th>
                                <th>Status Pengajuan</th>
                                <?php endif; ?>

                                <?php if ($showStatusPaymentCol): ?> <th>Status Payment</th> <?php endif; ?>
                                <!-- ANGKA -->
                                <th class="sortable" data-sort-type="currency" data-col="total_cost">
                                Total Cost
                                <i class="bi bi-arrow-down-up"></i>
                                </th>
                                <th class="sortable" data-sort-type="currency" data-col="nominal_dibayarkan">
                                Berhasil Dibayarkan
                                <i class="bi bi-arrow-down-up"></i>
                                </th>

                                <th>Link MOU</th>
                                <th>Deskripsi</th>
                                <th>Ket Payment</th>
                            </tr>
                            </thead>




                        <tbody id="table-body">
                            <?php foreach ($payment_fee as $index => $payment): ?>
                                <?php
                                $rowKey = $payment['id_campaign'] . '|' . rawurlencode($payment['nama_creator']);
                                if (!empty($payment['endorse_id'])) {
                                    $rowKey .= '|' . (int)$payment['endorse_id']; // <<— tambahkan id_endorse jika ada (non-bundling)
                                }
                                ?>

                                <tr class="<?= ($payment['status_payment'] ?? '') === 'FP' ? 'fp-status' : '' ?>"
                                    data-id_campaign="<?= htmlspecialchars($payment['id_campaign'], ENT_QUOTES) ?>"
                                    data-nama_creator="<?= htmlspecialchars($payment['nama_creator'], ENT_QUOTES) ?>"
                                    data-endorse-id="<?= isset($payment['endorse_id']) ? (int)$payment['endorse_id'] : '' ?>">

                                    <td>
                                        <div class="checkbox-wrapper-13 d-inline">
                                            <input class="checkItem" type="checkbox" value="<?= $rowKey ?>">
                                        </div>
                                    </td>
                                    <td><?= $index + 1 ?></td>
                                    <td class="col-action">
                                        <a href="#!"
                                            onclick="edit('<?= $payment['id_campaign'] ?>','<?= htmlspecialchars($payment['nama_creator'], ENT_QUOTES) ?>','<?= isset($payment['endorse_id']) ? (int)$payment['endorse_id'] : '' ?>')">
                                            <i class="bi bi-pen"></i>
                                        </a>

                                    </td>
                                    <?php
                                        // Ambil timestamp display (bila ada) LEBIH DULU
                                        $ts = $payment['display_updated_at'] ?? null;
                                        $ts_unix = $ts ? strtotime($ts) : 0;
                                    ?>
                                    <td data-sort-value="<?= $ts_unix ?>">
                                        <a class="badge-link badge-creator"
                                            href="<?= base_url() ?>endorse?id_campaign=<?= $payment['id_campaign'] ?>&keyword_category=Nama+Creator&keyword=<?= urlencode($payment['nama_creator']) ?>"
                                            target="_blank" onclick="event.stopPropagation()">
                                            <?= htmlspecialchars($payment['nama_creator']) ?>
                                        </a>
                                        <small class="text-muted d-block mt-1" style="font-size:11px;">
                                            <?= $ts ? date('d M Y H:i', strtotime($ts)) : '-' ?>
                                        </small>
                                    </td>
                                    <td>
                                        <a class="badge-link badge-campaign"
                                            href="<?= base_url('/endorse?id_campaign=' . $payment['id_campaign']) ?>"
                                            target="_blank"
                                            onclick="event.stopPropagation()">
                                            <?= htmlspecialchars($payment['title']) ?>
                                        </a>

                                    </td>
                                    <td>
                                        <?= $payment['no_rekening'] ?: '-' ?><br>
                                        <?= !empty($payment['pemilik_rekening']) ? 'a/n ' . htmlspecialchars($payment['pemilik_rekening']) : 'a/n Belum Tersedia' ?>
                                    </td>

                                    <?php if ($showPengajuanCols): ?>
                                        <td><?= !empty($payment['nominal_pengajuan']) ? number_format($payment['nominal_pengajuan']) : '-' ?></td>
                                        <td><?= !empty($payment['pengajuan_status_payment']) ? htmlspecialchars($payment['pengajuan_status_payment']) : '-' ?></td>
                                    <?php endif; ?>

                                    <?php if ($showStatusPaymentCol): ?>
                                        <td>
                                            <?php
                                                $statusRaw = $payment['status_payment'] ?? '';
                                                $status = trim($statusRaw);
                                                $norm = strtoupper(str_replace(' ', '', $status));
                                                $hasDP = strpos($norm, 'DP') !== false;
                                                $hasFP = strpos($norm, 'FP') !== false;

                                                if ($hasDP && $hasFP)      { $badgeClass = 'badge-dpfp'; }
                                                elseif ($hasDP)            { $badgeClass = 'badge-dp'; }
                                                elseif ($hasFP)            { $badgeClass = 'badge-fp'; }
                                                else                       { $badgeClass = 'badge-default'; }

                                                // kunci hover: kalau bundling, endorse_id bisa kosong/null
                                                $endorseIdForHover = isset($payment['endorse_id']) ? (int)$payment['endorse_id'] : 0;
                                            ?>
                                            <span
                                                class="badge-status <?= $badgeClass ?> js-payment-status"
                                                data-id_campaign="<?= (int)$payment['id_campaign'] ?>"
                                                data-nama_creator="<?= htmlspecialchars($payment['nama_creator'], ENT_QUOTES) ?>"
                                                data-endorse_id="<?= $endorseIdForHover ?>"
                                                data-start_date="<?= $start_date ?>"
                                                data-until_date="<?= $until_date ?>"
                                                data-status_label="<?= htmlspecialchars($status) ?>"
                                                >
                                                <?= $status !== '' ? htmlspecialchars($status) : '-' ?>
                                            </span>
                                        </td>
                                        <?php endif; ?>


                                    <td><?= number_format($payment['total_cost'] ?? 0) ?></td>
                                    <td><?= number_format($payment['nominal_dibayarkan'] ?? 0) ?></td>
                                    <td><a class="badge-link badge-mou"
                                            href="<?= $payment['link_mou'] ?>"
                                            target="_blank"
                                            onclick="event.stopPropagation()">MOU
                                        </a>
                                    </td>
                                    <td style="white-space: normal;word-wrap: break-word;min-width:200px;"><?= isset($payment['desc']) ? htmlspecialchars($payment['desc']) : '-' ?></td>
                                    <td style="white-space: normal;word-wrap: break-word;min-width:200px;"><?= (isset($payment['keterangan_payment']) && trim($payment['keterangan_payment']) !== '') ? htmlspecialchars($payment['keterangan_payment']) : '-' ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>


                        <?php
                        $sum_total_cost   = array_sum(array_column($payment_fee, 'total_cost'));
                        $sum_dibayarkan   = array_sum(array_column($payment_fee, 'nominal_dibayarkan'));
                        $sum_pengajuan    = array_sum(array_map(function($r){
                            return (float)($r['nominal_pengajuan'] ?? 0);
                        }, $payment_fee));
                        ?>

                        <tfoot class="fixed">
                        <tr id="grand-total-row">
                            <td><strong>Total:</strong></td>
                            <?php if ($showPengajuanCols): ?>
                                <td><?= number_format($sum_pengajuan) ?></td>
                                <td></td>
                            <?php endif; ?>

                            <?php if ($showStatusPaymentCol): ?>
                                <td></td>
                            <?php endif; ?>

                            <td><?= number_format($sum_total_cost) ?></td>
                            <td><?= number_format($sum_dibayarkan) ?></td>

                            <td colspan="3"></td>
                        </tr>
                        </tfoot>



                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">No data available</div>
            <?php endif; ?>
        </div>
        <!-- Add this div for the total payment popup -->
        <div id="total-payment-popup" class="alert alert-info mt-3" style="display: none;">
            <strong>Total Payment Summary:</strong>
            <div id="total-payment-details"></div>
        </div>
    </div>
</div>

<input type="hidden" id="id_selected" name="id_selected" form="form-action">

<script>
    function refreshSelected() {
        const ids = Array.from(document.querySelectorAll('.checkItem:checked'))
            .map(el => el.value);
        document.getElementById('id_selected').value = ids.join(',');
    }

    function updateCheckAllState() {
        const $items = $('.checkItem');
        const total = $items.length;
        const checked = $items.filter(':checked').length;
        const master = $('#checkAll').get(0);

        if (!master) return;

        if (total === 0) {
            master.checked = false;
            master.indeterminate = false;
            master.disabled = true;
            return;
        } else {
            master.disabled = false;
        }

        if (checked === 0) {
            master.checked = false;
            master.indeterminate = false;
        } else if (checked === total) {
            master.checked = true;
            master.indeterminate = false;
        } else {
            master.checked = false;
            master.indeterminate = true;
        }
    }

    $(document).on('change', '#checkAll', function () {
        const checked = this.checked;
        $('.checkItem').prop('checked', checked);
        refreshSelected();
        this.indeterminate = false;
    });

    $(document).on('change', '.checkItem', function () {
        refreshSelected();
        updateCheckAllState();
    });

    $(function () {
        updateCheckAllState();
    });
</script>


<div class="dropdown floating-div mb-4">
    <button class="btn btn-primary dropdown-toggle" type="button" id="aksiDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-gear"></i> Aksi
    </button>
    <ul class="dropdown-menu" aria-labelledby="aksiDropdown">
        <li>
        <a class="dropdown-item" href="javascript:void(0)" onclick="bulk_payment()">
            <i class="bi bi-wallet fs-16"></i> Bulk Payment
        </a>
        </li>
        <li>
        <a class="dropdown-item" href="javascript:void(0)" onclick="rollback_payment()">
            <i class="bi bi-arrow-counterclockwise fs-16"></i> Rollback Payment
        </a>
        </li>
    </ul>
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
    $('input[name="list_id"]').change(function() {
        get_id();
    });

    function get_id() {
        list_id_v2 = '';
        var selectedValues = [];
        $('input[name="list_id"]').each(function() {
            if ($(this).is(":checked")) {
                selectedValues.push($(this).val());
                list_id_v2 += $(this).val() + ',';
            } else {
                selectedValues.push('0');
            }
        });
        if (list_id_v2.length > 0) {
            list_id_v2 = list_id_v2.slice(0, -1);
        }
        $('#id_selected').val(selectedValues.join(','));
    }

    function edit(id_campaign, nama_creator, endorse_id = null) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Edit Data');
        $('#modal-form .modal-dialog').removeClass('modal-sm modal-xl').addClass('modal-lg');

        const qs = new URLSearchParams({
            id_campaign: id_campaign,
            nama_creator: nama_creator,
            id: endorse_id || '',
            start_date: $('#start_date').val(),
            until_date: $('#end_date').val(),
            endorse_status: $('input[name="endorse_status"]').val() || (new URL(location).searchParams.get('endorse_status') || '')
        });
        $("#load-form").load("<?= base_url() ?>/payment/edit?" + qs.toString());
    }

    function bulk_payment() {
        const selected = $('#id_selected').val();
        if (!selected) {
            Swal.fire({
                title: 'Pilih data',
                text: 'Centang minimal 1 baris untuk diproses.',
                icon: 'warning',
                showCancelButton: false,
                confirmButtonText: 'OK',
                customClass: {
                    confirmButton: 'btn btn-primary',
                },
                buttonsStyling: false 
            });

            return;
        }
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Bulk Payment');
        $('#modal-form .modal-dialog')
            .removeClass('modal-lg modal-xl')
            .addClass('modal-md');
        const url = "<?= base_url() ?>/payment/action?code=bulk_payment&id_selected=" + encodeURIComponent(selected);
        $("#load-form").load(url);
    }

    function rollback_payment() {
        const selected = $('#id_selected').val();
        if (!selected) {
            Swal.fire({
                title: 'Pilih data',
                text: 'Centang minimal 1 baris untuk di-rollback.',
                icon: 'warning',
                confirmButtonText: 'OK',
                customClass: { confirmButton: 'btn btn-primary' },
                buttonsStyling: false
            });
            return;
        }
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Rollback Payment');
        $('#modal-form .modal-dialog')
            .removeClass('modal-lg modal-xl')
            .addClass('modal-md');
        const url = "<?= base_url() ?>/payment/action?code=rollback_payment&id_selected=" + encodeURIComponent(selected);
        $("#load-form").load(url);
    }

    function showSpendKolPopup(start_date, until_date, brand) {
        const url = `<?= base_url() ?>/dashboard/expense_data?start_date=${start_date}&until_date=${until_date}&brand=${brand}`;

        $('#spend-kol-info').html('<span class="badge-custom badge-loading">Mengambil data...</span>');

        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.spend_kol && response.spend_kol.total_spend_kol) {
                    const spendKol = parseFloat(response.spend_kol.total_spend_kol).toLocaleString('id-ID');
                    $('#spend-kol-info').html(`<span class="badge-custom badge-success-custom">Berhasil Dibayarkan: Rp ${spendKol}</span>`);
                } else {
                    $('#spend-kol-info').html('<span class="badge-custom badge-error-custom">Data Spend KOL tidak ditemukan</span>');
                }
            },
            error: function() {
                $('#spend-kol-info').html('<span class="badge-custom badge-error-custom">Gagal mengambil data dari server</span>');
            }
        });
    }

    showSpendKolPopup('<?= $start_date ?>', '<?= $until_date ?>', '<?= $detail['brand'] ?>');


</script>
<script>
    const NON_ROW_CLICK_SELECTORS = [
        'a', 'button', 'input', 'select', 'textarea', 'label',
        '.checkItem', '#checkAll', '.dropdown-toggle', '.dropdown-item'
    ].join(',');

    function isSmallScreen() {
        return window.matchMedia('(max-width: 576px)').matches;
    }

    $(document).on('click', '#table-body tr', function(e) {
        if (!isSmallScreen()) return;
        if ($(e.target).closest(NON_ROW_CLICK_SELECTORS).length) return;

        const id_campaign = $(this).data('id_campaign');
        const nama_creator = $(this).data('nama_creator');
        const endorse_id = $(this).attr('data-endorse-id') || ''; // ambil dari atribut

        if (id_campaign && nama_creator) {
            edit(String(id_campaign), String(nama_creator), endorse_id ? String(endorse_id) : null);
        }
    });


    $(document).on('click', '.checkItem, #checkAll', function(e) {
        e.stopPropagation();
    });
</script>

<script>
function adjustGrandTotalColspan() {
    const $table = $('#payment-table');
    const $tfootRow = $table.find('tfoot tr').first();
    if ($tfootRow.length === 0) return;

    const totalVisibleCols = $table.find('thead th:visible').length;

    let remainingCols = 0;
    $tfootRow.find('td').each(function(idx) {
        if (idx === 0) return; 
        const span = parseInt($(this).attr('colspan') || '1', 10);
        remainingCols += isNaN(span) ? 1 : span;
    });

    const newColspan = Math.max(1, totalVisibleCols - remainingCols);
    const $firstTd = $tfootRow.find('td').first();
    $firstTd.attr('colspan', newColspan);
}

$(function() {
    adjustGrandTotalColspan();
});
$(window).on('resize', adjustGrandTotalColspan);

$(document).on('click', '.btn-default, .btn-default-selected', function() {
    setTimeout(adjustGrandTotalColspan, 50);
});
</script>



<style>
    .badge-custom {
        display: inline-block;
        padding: 6px 12px;
        font-size: 0.85rem;
        font-weight: 600;
        border-radius: 12px;
        color: #fff;
    }

    .badge-loading {
        background-color: #6c757d; /* abu-abu */
    }

    .badge-success-custom {
        background-color: #1890ff; /* biru */
    }

    .badge-error-custom {
        background-color: #dc3545; /* merah */
    }

</style>

<script>
(function() {
  function getVisibleHeaders($table) {
    return $table.find('thead th:visible'); // semua header visible
  }
  function getVisibleSortableHeaders($table) {
    return $table.find('thead th.sortable:visible'); // hanya yang sortable
  }

  function normalizeCurrency(str) {
    const n = (str || '').toString().replace(/[^0-9\-]/g, '');
    return n.length ? parseInt(n, 10) : 0;
  }

  function getCellSortValue($cell, type) {
    const ds = $cell.data('sortValue');
    if (ds !== undefined) return ds;

    const raw = $cell.text().trim();
    switch (type) {
      case 'number':   return Number(raw.replace(/[^\d\-]/g,'') || 0);
      case 'currency': return normalizeCurrency(raw);
      default:         return raw.toLowerCase();
    }
  }

  function renumberRows($tbody) {
    // Kolom "No" diasumsikan index ke-1
    $tbody.find('tr').each(function(i){
      const $no = $(this).children('td').eq(1);
      if ($no.length) $no.text(i + 1);
    });
  }

  function updateSortIcons($table, clickedTh, order) {
    const $sortable = getVisibleSortableHeaders($table);
    // reset semua ikon ke netral
    $sortable.each(function(){
      const $i = $(this).find('i');
      if ($i.length) $i.attr('class', 'bi bi-arrow-down-up');
    });
    // set ikon aktif hanya pada th yang diklik
    const $icon = $(clickedTh).find('i');
    if ($icon.length) {
      $icon.attr('class', order === 'asc' ? 'bi bi-arrow-up' : 'bi bi-arrow-down');
    }
  }

  $(function(){
    const $table = $('#payment-table');
    if ($table.length === 0) return;

    let currentSortTh = null;
    let currentSortOrder = 'asc';

    $table.on('click', 'thead th.sortable', function(){
      const $allHeaders = getVisibleHeaders($table);           // semua header visible (termasuk non-sortable)
      const overallIndex = $allHeaders.index(this);            // index kolom sebenarnya
      if (overallIndex < 0) return;

      const sortType = ($(this).data('sortType') || 'text').toString();

      if (currentSortTh === this) {
        currentSortOrder = (currentSortOrder === 'asc') ? 'desc' : 'asc';
      } else {
        currentSortTh = this;
        currentSortOrder = 'asc';
      }

      const $tbody = $table.find('tbody');
      const rows = $tbody.find('tr').get();

      rows.sort(function(a, b){
        const $aCells = $(a).children('td:visible');
        const $bCells = $(b).children('td:visible');

        const aVal = getCellSortValue($aCells.eq(overallIndex), sortType);
        const bVal = getCellSortValue($bCells.eq(overallIndex), sortType);

        if (sortType === 'number' || sortType === 'currency') {
          return (currentSortOrder === 'asc') ? (aVal - bVal) : (bVal - aVal);
        } else {
          if (aVal < bVal) return (currentSortOrder === 'asc') ? -1 : 1;
          if (aVal > bVal) return (currentSortOrder === 'asc') ? 1  : -1;
          return 0;
        }
      });

      $.each(rows, function(_, row){ $tbody.append(row); });

      renumberRows($tbody);
      if (typeof updateCheckAllState === 'function') updateCheckAllState();

      updateSortIcons($table, this, currentSortOrder);
    });
  });
})();
</script>


<script>
(function(){
  const cacheLogs = new Map();

  function keyFrom(el) {
    const $el = $(el);
    return [
      $el.data('id_campaign'),
      $el.data('nama_creator'),
      $el.data('endorse_id') || 0,
      $el.data('start_date'),
      $el.data('until_date')
    ].join('|');
  }

  function formatCurrency(n) {
    try { return new Intl.NumberFormat('id-ID').format(Number(n||0)); }
    catch { return n; }
  }

  function renderLogsHTML(items) {
    if (!items || !items.length) {
      return '<div style="padding:6px 2px;">Tidak ada riwayat DP/FP.</div>';
    }
    const rows = items.map(it => {
      const sts = (it.status_payment || '').toUpperCase();
      const date = it.created_at ? it.created_at : '';
      const who  = it.created_by ? ` • <em>${it.created_by}</em>` : '';
      const ket  = (it.keterangan && String(it.keterangan).trim() !== '') ? `<div style="color:#555;">Ket: ${it.keterangan}</div>` : '';
      const nominal = (it.nominal_dibayarkan != null) ? `Rp ${formatCurrency(it.nominal_dibayarkan)}` : '-';
      const badge =
        sts.includes('DP') && sts.includes('FP') ? 'background:linear-gradient(90deg,#f6a623,#27ae60)' :
        sts.includes('DP') ? 'background:#f6a623' :
        sts.includes('FP') ? 'background:#27ae60' : 'background:#7f8c8d';
      return `
        <div style="padding:8px 0;border-bottom:1px dashed #eee;">
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <span style="display:inline-block;padding:2px 8px;border-radius:999px;color:#fff;${badge}">${sts}</span>
            <strong>${nominal}</strong>
            <span style="color:#888;">${date}</span>
          </div>
        </div>
      `;
    }).join('');
    return `
      <div style="min-width:200px;max-width:360px;">
        <div style="font-weight:700;margin-bottom:6px;">Riwayat Payment</div>
        ${rows}
      </div>
    `;
  }

  async function fetchLogs($el) {
    const params = {
      id_campaign: $el.data('id_campaign'),
      nama_creator: $el.data('nama_creator'),
      endorse_id: $el.data('endorse_id') || 0,
      start_date: $el.data('start_date'),
      until_date: $el.data('until_date')
    };
    const url = `<?= base_url() ?>/payment/logs?` + new URLSearchParams(params).toString();
    const res = await $.ajax({ url, method: 'GET', dataType: 'json' });
    return (res && res.data) ? res.data : [];
  }

  function initTippy() {
    tippy('.js-payment-status', {
      theme: 'light',
      allowHTML: true,
      interactive: true,
      delay: [100, 50],
      placement: 'right',
      onShow(instance) {
        const el = instance.reference;
        const k = keyFrom(el);
        if (cacheLogs.has(k)) {
          instance.setContent(renderLogsHTML(cacheLogs.get(k)));
          return;
        }
        instance.setContent('<div style="padding:6px 2px;color:#555;">Memuat riwayat…</div>');
        fetchLogs($(el))
          .then(items => {
            cacheLogs.set(k, items);
            if (instance.state.isDestroyed) return;
            instance.setContent(renderLogsHTML(items));
          })
          .catch(() => {
            if (instance.state.isDestroyed) return;
            instance.setContent('<div style="padding:6px 2px;color:#c00;">Gagal memuat riwayat.</div>');
          });
      }
    });
  }

  $(initTippy);
})();
</script>
