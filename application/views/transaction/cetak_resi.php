<?php
$selected_lookup = array();
if (!empty($selected_ids) && is_array($selected_ids)) {
    foreach ($selected_ids as $sid) {
        $selected_lookup[intval($sid)] = true;
    }
}

$status_options = array();
$courier_options = array();
foreach ($orders as $v) {
    $status = trim((string)($v['order_status'] ?? ''));
    $courier = trim((string)($v['shipping'] ?? ''));
    if ($status !== '') {
        $status_options[$status] = $status;
    }
    if ($courier !== '') {
        $courier_options[$courier] = $courier;
    }
}

$initial_selected = json_encode(array_values(array_map('intval', $selected_ids ?? array())));
if (!$initial_selected) {
    $initial_selected = '[]';
}

$per_page = intval($per_page ?? 50);
$per_page_options = $per_page_options ?? array(50, 100, 500);
$current_page = intval($current_page ?? 1);
$total_pages = intval($total_pages ?? 1);
$total_orders = intval($total_orders ?? count($orders));
$start_index = intval($start_index ?? ($total_orders > 0 ? 1 : 0));
$end_index = intval($end_index ?? count($orders));
$start_date = $start_date ?? date('Y-m-01');
$until_date = $until_date ?? date('Y-m-d');

$build_page_url = function ($page) use ($per_page, $start_date, $until_date) {
    return base_url() . 'transaction/cetak-resi?page=' . intval($page) . '&per_page=' . intval($per_page) . '&start_date=' . urlencode($start_date) . '&until_date=' . urlencode($until_date);
};
?>

<style>
    .bulk-resi-wrap {
        background: #f4f6fb;
        border-radius: 14px;
        padding: 14px;
        border: 1px solid #e5e8f0;
    }

    .bulk-resi-title {
        font-weight: 700;
        font-size: 35px;
        color: #17223b;
        margin: 0;
    }

    .bulk-resi-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        border: 1px solid #e5e8f0;
        border-radius: 12px;
        padding: 12px 14px;
        background: #fff;
        margin-top: 12px;
    }

    .bulk-resi-select {
        display: flex;
        align-items: center;
        gap: 14px;
        color: #7b879f;
        font-weight: 500;
    }

    .bulk-resi-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .bulk-resi-actions .btn {
        border-radius: 10px;
        font-weight: 600;
        padding: 8px 14px;
    }

    .bulk-resi-actions .btn-primary {
        background: #3b82f6;
        border-color: #3b82f6;
    }

    .bulk-resi-card {
        margin-top: 14px;
        border: 1px solid #e5e8f0;
        border-radius: 12px;
        background: #fff;
        overflow: hidden;
    }

    .bulk-resi-filterbar {
        padding: 14px;
        border-bottom: 1px solid #edf0f6;
        display: grid;
        grid-template-columns: minmax(260px, 1fr) 170px 170px minmax(280px, 360px);
        gap: 10px;
        align-items: start;
    }

    .bulk-resi-filterbar .form-control,
    .bulk-resi-filterbar .form-select {
        border-radius: 10px;
        height: 40px;
        border-color: #dbe1ef;
    }

    .bulk-date-form {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: stretch;
        gap: 8px;
        width: 100%;
    }

    .bulk-date-form .date-input {
        width: 100%;
        min-width: 0;
        height: 40px;
        cursor: pointer;
        background: #fff;
        color: #1f2937;
        font-weight: 600;
    }

    .bulk-date-form .btn {
        border-radius: 10px;
        height: 40px;
        padding: 0 12px;
        font-weight: 600;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }

    .bulk-search-block {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
        min-width: 0;
    }

    .bulk-search-block #bulkSearch {
        width: 100%;
    }

    .bulk-reset-all {
        border-radius: 10px;
        height: 34px;
        min-width: 96px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        white-space: nowrap;
        padding: 0 12px;
    }

    .bulk-resi-table {
        width: 100%;
        margin: 0;
    }

    .bulk-resi-table thead th {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #8a95aa;
        font-weight: 700;
        background: #fafbff;
        border-bottom: 1px solid #edf0f6;
        padding: 11px 10px;
    }

    .bulk-resi-table tbody td {
        padding: 11px 10px;
        border-bottom: 1px solid #edf0f6;
        vertical-align: middle;
        font-size: 14px;
    }

    .bulk-resi-table tbody tr:hover {
        background: #fafcff;
    }

    .bulk-order-id {
        color: #2e70ea;
        font-weight: 700;
        display: block;
    }

    .bulk-date {
        color: #7f8aa0;
        font-size: 12px;
    }

    .mkt-badge {
        display: inline-block;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 700;
        line-height: 1;
        padding: 6px 10px;
        color: #fff;
    }

    .mkt-tiktok {
        background: #111;
    }

    .mkt-shopee {
        background: #ff6d2d;
    }

    .mkt-tokopedia {
        background: #16a34a;
    }

    .mkt-default {
        background: #6b7280;
    }

    .status-badge {
        font-size: 11px;
        font-weight: 700;
        border-radius: 10px;
        padding: 5px 10px;
        display: inline-block;
    }

    .status-ready {
        background: #e6f0ff;
        color: #2f67db;
    }

    .status-unpaid {
        background: #ffe7e7;
        color: #cf4545;
    }

    .status-shipped {
        background: #def7e7;
        color: #2f9155;
    }

    .status-other {
        background: #eef2f7;
        color: #64748b;
    }

    .bulk-resi-footer {
        padding: 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: #6c7892;
        font-size: 14px;
    }

    .bulk-footer-right {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .bulk-per-page-form {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin: 0;
    }

    .bulk-per-page-form label {
        font-size: 13px;
        color: #64748b;
        margin: 0;
        white-space: nowrap;
    }

    .bulk-per-page-form select {
        min-width: 110px;
        border-radius: 8px;
        border-color: #d9e1f0;
    }

    .bulk-pager {
        display: inline-flex;
        gap: 6px;
        align-items: center;
    }

    .bulk-pager .page-btn {
        width: 30px;
        height: 30px;
        border: 1px solid #d9e1f0;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        color: #64748b;
    }

    .bulk-pager a.page-btn {
        text-decoration: none;
    }

    .bulk-pager .page-btn.active {
        background: #edf3ff;
        border-color: #7da9ff;
        color: #2f67db;
        font-weight: 700;
    }

    .bulk-pager .page-btn.disabled {
        color: #a5afbf;
        background: #f8fafc;
        border-color: #e8edf6;
        pointer-events: none;
    }

    .bulk-pager .page-ellipsis {
        color: #94a3b8;
        font-size: 12px;
        padding: 0 2px;
    }

    /* Force daterangepicker to stay above cards/header and keep range visible */
    .daterangepicker {
        z-index: 20000 !important;
        border: 1px solid #dbe1ef !important;
        border-radius: 12px !important;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.16) !important;
    }

    .daterangepicker td.in-range {
        background-color: #eaf2ff !important;
        color: #1f2937 !important;
    }

    .daterangepicker td.start-date,
    .daterangepicker td.end-date,
    .daterangepicker td.active,
    .daterangepicker td.active:hover {
        background-color: #3b82f6 !important;
        color: #fff !important;
    }

    @media (max-width: 991px) {
        .bulk-resi-filterbar {
            grid-template-columns: 1fr;
        }

        .bulk-resi-footer {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
    }
</style>

<div class="w-100">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
        <h3 class="bulk-resi-title"><i class="bi bi-printer me-2" style="font-size:24px;color:#3b82f6;"></i>Cetak Resi</h3>
    </div>

    <div class="bulk-resi-wrap">
        <div class="bulk-resi-toolbar">
            <div class="bulk-resi-select">
                <label class="mb-0 d-flex align-items-center" style="gap:8px;">
                    <input type="checkbox" id="bulkSelectAll" class="form-check-input" style="margin-top:0;">
                    <span>Select All</span>
                </label>
                <span>|</span>
                <span id="bulkSelectedText">0 Selected</span>
            </div>
            <div class="bulk-resi-actions">
                <button type="button" id="btnPrintSelected" class="btn btn-primary"><i class="bi bi-printer me-1"></i>Cetak Resi</button>
                <button type="button" class="btn btn-light border" onclick="alert('Fitur Update Status massal belum diimplementasikan di flow ini.')"><i class="bi bi-arrow-repeat me-1"></i>Update Status</button>
            </div>
        </div>

        <div class="bulk-resi-card">
            <div class="bulk-resi-filterbar">
                <div class="bulk-search-block">
                    <input type="text" id="bulkSearch" class="form-control" placeholder="Search by Order ID, Customer Name...">
                    <a href="<?= base_url() ?>transaction/cetak-resi" class="btn btn-light border bulk-reset-all"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</a>
                </div>
                <select id="bulkStatusFilter" class="form-select">
                    <option value="">All Status</option>
                    <?php foreach ($status_options as $status): ?>
                        <option value="<?= htmlspecialchars($status) ?>"><?= htmlspecialchars($status) ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="bulkCourierFilter" class="form-select">
                    <option value="">All Couriers</option>
                    <?php foreach ($courier_options as $courier): ?>
                        <option value="<?= htmlspecialchars($courier) ?>"><?= htmlspecialchars($courier) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="bulk-date-form">
                    <input type="text" id="bulkDateRange" class="form-control date-input" value="" readonly>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table bulk-resi-table">
                    <thead>
                        <tr>
                            <th style="width:52px;"></th>
                            <th>Order ID / Date</th>
                            <th>Customer</th>
                            <th>Courier</th>
                            <th>Marketplace</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="bulkResiTbody">
                        <?php foreach ($orders as $v): ?>
                            <?php
                            $id = intval($v['id']);
                            $order_id = trim((string)($v['order_id'] ?? '-'));
                            $customer = trim((string)($v['customer_text'] ?? '-'));
                            $phone = trim((string)($v['phone'] ?? '-'));
                            $shipping = trim((string)($v['shipping'] ?? '-'));
                            $status = trim((string)($v['order_status'] ?? '-'));
                            $marketplace = trim((string)($v['marketplace'] ?? '-'));
                            $date_text = !empty($v['date']) ? date('d M Y H:i', strtotime($v['date'])) : '-';

                            $customer_mask = $customer;
                            $len_customer = strlen($customer_mask);
                            if ($len_customer > 2) {
                                $customer_mask = substr($customer_mask, 0, 1) . str_repeat('*', max(3, $len_customer - 2)) . substr($customer_mask, -1);
                            }

                            $phone_mask = $phone;
                            $len_phone = strlen($phone_mask);
                            if ($len_phone > 7) {
                                $phone_mask = substr($phone_mask, 0, 4) . str_repeat('*', max(3, $len_phone - 7)) . substr($phone_mask, -3);
                            }

                            $mkt_class = 'mkt-default';
                            $mkt_lower = strtolower($marketplace);
                            if (strpos($mkt_lower, 'tiktok') !== false) {
                                $mkt_class = 'mkt-tiktok';
                            } elseif (strpos($mkt_lower, 'shopee') !== false) {
                                $mkt_class = 'mkt-shopee';
                            } elseif (strpos($mkt_lower, 'tokopedia') !== false) {
                                $mkt_class = 'mkt-tokopedia';
                            }

                            $status_class = 'status-other';
                            $status_upper = strtoupper($status);
                            if ($status_upper === 'READY_TO_SHIP' || $status_upper === 'PENDING') {
                                $status_class = 'status-ready';
                            } elseif ($status_upper === 'UNPAID') {
                                $status_class = 'status-unpaid';
                            } elseif ($status_upper === 'SHIPPED' || $status_upper === 'DELIVERED' || $status_upper === 'COMPLETED') {
                                $status_class = 'status-shipped';
                            }
                            ?>
                            <tr data-id="<?= $id ?>" data-order="<?= htmlspecialchars(strtolower($order_id)) ?>" data-customer="<?= htmlspecialchars(strtolower($customer)) ?>" data-status="<?= htmlspecialchars($status) ?>" data-courier="<?= htmlspecialchars($shipping) ?>">
                                <td>
                                    <input type="checkbox" class="form-check-input bulk-item-check" value="<?= $id ?>" <?= isset($selected_lookup[$id]) ? 'checked' : '' ?>>
                                </td>
                                <td>
                                    <span class="bulk-order-id">#<?= htmlspecialchars($order_id) ?></span>
                                    <span class="bulk-date"><?= htmlspecialchars($date_text) ?></span>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($customer_mask) ?></strong>
                                    <div class="bulk-date"><?= htmlspecialchars($phone_mask) ?></div>
                                </td>
                                <td>
                                    <i class="bi bi-truck me-1 text-muted"></i><?= htmlspecialchars($shipping) ?>
                                </td>
                                <td>
                                    <span class="mkt-badge <?= $mkt_class ?>"><?= htmlspecialchars($marketplace) ?></span>
                                </td>
                                <td>
                                    <span class="status-badge <?= $status_class ?>"><?= htmlspecialchars($status) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="bulk-resi-footer">
                <?php $base_text = "Showing {$start_index} to {$end_index} of {$total_orders} results"; ?>
                <span id="bulkResultText" data-base-text="<?= htmlspecialchars($base_text, ENT_QUOTES) ?>"><?= htmlspecialchars($base_text) ?></span>
                <div class="bulk-footer-right">
                    <form class="bulk-per-page-form" method="get" action="<?= base_url() ?>transaction/cetak-resi">
                        <input type="hidden" name="page" value="1">
                        <input type="hidden" name="start_date" value="<?= htmlspecialchars($start_date, ENT_QUOTES) ?>">
                        <input type="hidden" name="until_date" value="<?= htmlspecialchars($until_date, ENT_QUOTES) ?>">
                        <label for="bulkPerPage">Per page</label>
                        <select id="bulkPerPage" name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                            <?php foreach ($per_page_options as $opt): ?>
                                <option value="<?= intval($opt) ?>" <?= intval($per_page) === intval($opt) ? 'selected' : '' ?>><?= intval($opt) ?> / page</option>
                            <?php endforeach; ?>
                        </select>
                    </form>

                    <div class="bulk-pager">
                        <?php if ($current_page > 1): ?>
                            <a class="page-btn" href="<?= htmlspecialchars($build_page_url($current_page - 1)) ?>"><i class="bi bi-chevron-left"></i></a>
                        <?php else: ?>
                            <span class="page-btn disabled"><i class="bi bi-chevron-left"></i></span>
                        <?php endif; ?>

                        <?php
                        $window = 2;
                        $page_start = max(1, $current_page - $window);
                        $page_end = min($total_pages, $current_page + $window);
                        if ($page_start > 1) {
                            echo '<a class="page-btn" href="' . htmlspecialchars($build_page_url(1)) . '">1</a>';
                            if ($page_start > 2) {
                                echo '<span class="page-ellipsis">...</span>';
                            }
                        }
                        for ($p = $page_start; $p <= $page_end; $p++):
                        ?>
                            <?php if ($p === $current_page): ?>
                                <span class="page-btn active"><?= $p ?></span>
                            <?php else: ?>
                                <a class="page-btn" href="<?= htmlspecialchars($build_page_url($p)) ?>"><?= $p ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php
                        if ($page_end < $total_pages) {
                            if ($page_end < $total_pages - 1) {
                                echo '<span class="page-ellipsis">...</span>';
                            }
                            echo '<a class="page-btn" href="' . htmlspecialchars($build_page_url($total_pages)) . '">' . intval($total_pages) . '</a>';
                        }
                        ?>

                        <?php if ($current_page < $total_pages): ?>
                            <a class="page-btn" href="<?= htmlspecialchars($build_page_url($current_page + 1)) ?>"><i class="bi bi-chevron-right"></i></a>
                        <?php else: ?>
                            <span class="page-btn disabled"><i class="bi bi-chevron-right"></i></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        const baseUrl = "<?= base_url() ?>";
        const selected = new Set((<?= $initial_selected ?> || []).map(String));

        const tbody = document.getElementById('bulkResiTbody');
        const selectAllEl = document.getElementById('bulkSelectAll');
        const selectedTextEl = document.getElementById('bulkSelectedText');
        const resultTextEl = document.getElementById('bulkResultText');
        const searchEl = document.getElementById('bulkSearch');
        const statusEl = document.getElementById('bulkStatusFilter');
        const courierEl = document.getElementById('bulkCourierFilter');
        const baseResultText = resultTextEl.getAttribute('data-base-text') || resultTextEl.textContent;
        const dateRangeEl = $('#bulkDateRange');
        const startDate = '<?= htmlspecialchars($start_date, ENT_QUOTES) ?>';
        const untilDate = '<?= htmlspecialchars($until_date, ENT_QUOTES) ?>';
        const perPage = <?= intval($per_page) ?>;

        function updateSelectedText() {
            selectedTextEl.textContent = `${selected.size} Selected`;
        }

        function applyFilters() {
            const q = (searchEl.value || '').toLowerCase().trim();
            const status = statusEl.value;
            const courier = courierEl.value;

            const rows = Array.from(tbody.querySelectorAll('tr'));
            let visible = 0;

            rows.forEach((row) => {
                const order = row.getAttribute('data-order') || '';
                const customer = row.getAttribute('data-customer') || '';
                const rowStatus = row.getAttribute('data-status') || '';
                const rowCourier = row.getAttribute('data-courier') || '';

                const matchSearch = q === '' || order.includes(q) || customer.includes(q);
                const matchStatus = status === '' || rowStatus === status;
                const matchCourier = courier === '' || rowCourier === courier;

                const show = matchSearch && matchStatus && matchCourier;
                row.style.display = show ? '' : 'none';
                if (show) {
                    visible++;
                }
            });

            const hasFilter = q !== '' || status !== '' || courier !== '';
            if (hasFilter) {
                resultTextEl.textContent = `Showing ${visible} of ${rows.length} results on this page`;
            } else {
                resultTextEl.textContent = baseResultText;
            }
        }

        function syncChecksFromSelected() {
            const checks = tbody.querySelectorAll('.bulk-item-check');
            checks.forEach((cb) => {
                cb.checked = selected.has(String(cb.value));
            });
            updateSelectedText();
        }

        tbody.addEventListener('change', function(e) {
            if (!e.target.classList.contains('bulk-item-check')) {
                return;
            }

            const id = String(e.target.value);
            if (e.target.checked) {
                selected.add(id);
            } else {
                selected.delete(id);
            }
            updateSelectedText();
        });

        selectAllEl.addEventListener('change', function() {
            const rows = Array.from(tbody.querySelectorAll('tr')).filter(r => r.style.display !== 'none');
            rows.forEach((row) => {
                const cb = row.querySelector('.bulk-item-check');
                if (!cb) return;
                cb.checked = selectAllEl.checked;
                const id = String(cb.value);
                if (selectAllEl.checked) {
                    selected.add(id);
                } else {
                    selected.delete(id);
                }
            });
            updateSelectedText();
        });

        searchEl.addEventListener('input', applyFilters);
        statusEl.addEventListener('change', applyFilters);
        courierEl.addEventListener('change', applyFilters);

        if (typeof dateRangeEl.daterangepicker === 'function' && typeof moment !== 'undefined') {
            let startMoment = moment(startDate, 'YYYY-MM-DD', true);
            let endMoment = moment(untilDate, 'YYYY-MM-DD', true);

            if (!startMoment.isValid()) {
                startMoment = moment().startOf('month');
            }
            if (!endMoment.isValid()) {
                endMoment = moment();
            }
            if (startMoment.isAfter(endMoment)) {
                const tmp = startMoment.clone();
                startMoment = endMoment.clone();
                endMoment = tmp;
            }

            const formatRange = (s, e) => `${s.format('DD MMM YYYY')} - ${e.format('DD MMM YYYY')}`;
            dateRangeEl.val(formatRange(startMoment, endMoment));

            dateRangeEl.daterangepicker({
                startDate: startMoment,
                endDate: endMoment,
                autoUpdateInput: false,
                opens: 'left',
                drops: 'auto',
                parentEl: 'body',
                showDropdowns: true,
                locale: {
                    format: 'DD MMM YYYY',
                    applyLabel: 'Apply',
                    cancelLabel: 'Cancel',
                    daysOfWeek: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
                    monthNames: [
                        'January', 'February', 'March', 'April', 'May', 'June',
                        'July', 'August', 'September', 'October', 'November', 'December'
                    ],
                    firstDay: 1
                },
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                }
            });

            dateRangeEl.on('apply.daterangepicker', function(ev, picker) {
                dateRangeEl.val(formatRange(picker.startDate, picker.endDate));
                const nextStart = picker.startDate.format('YYYY-MM-DD');
                const nextEnd = picker.endDate.format('YYYY-MM-DD');
                window.location.href = `${baseUrl}transaction/cetak-resi?page=1&per_page=${perPage}&start_date=${encodeURIComponent(nextStart)}&until_date=${encodeURIComponent(nextEnd)}`;
            });

            dateRangeEl.on('cancel.daterangepicker', function() {
                dateRangeEl.val(formatRange(startMoment, endMoment));
            });
        }

        document.getElementById('btnPrintSelected').addEventListener('click', function() {
            const ids = Array.from(selected);
            if (!ids.length) {
                alert('Pilih minimal 1 order untuk cetak resi.');
                return;
            }
            window.location.href = `${baseUrl}transaction/cetak-resi/preview?ids=${encodeURIComponent(ids.join(','))}&mode=roll`;
        });

        syncChecksFromSelected();
        applyFilters();
    })();
</script>
