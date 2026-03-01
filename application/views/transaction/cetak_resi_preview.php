<?php
$total_orders = intval($summary['total_orders'] ?? 0);
$total_items = intval($summary['total_items'] ?? 0);
$total_cod = doubleval($summary['total_cod'] ?? 0);
$product_aggregate = $summary['product_aggregate'] ?? array();
$primary_expedition = $summary['primary_expedition'] ?? '-';
$primary_expedition_count = intval($summary['primary_expedition_count'] ?? 0);
$print_mode = strtolower(trim((string)($print_mode ?? 'roll')));
if (!in_array($print_mode, array('roll', 'page'), true)) {
    $print_mode = 'roll';
}
$is_roll_mode = $print_mode === 'roll';
$ids_param_value = trim((string)($ids_param ?? ''));
$mode_roll_url = base_url('transaction/cetak-resi/preview?ids=' . rawurlencode($ids_param_value) . '&mode=roll');
$mode_page_url = base_url('transaction/cetak-resi/preview?ids=' . rawurlencode($ids_param_value) . '&mode=page');
?>

<style>
    .preview-page-title {
        font-size: 33px;
        font-weight: 700;
        margin: 0;
        color: #1f2937;
    }

    .preview-shell {
        background: #f4f6fb;
        border: 1px solid #e5e8f0;
        border-radius: 14px;
        padding: 14px;
    }

    .preview-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
    }

    .preview-head h4 {
        margin: 0;
        font-weight: 800;
        font-size: 25px;
        color: #1f2937;
    }

    .preview-head p {
        margin: 4px 0 0;
        color: #7c889c;
    }

    .batch-chip {
        background: #dbeafe;
        color: #2f67db;
        font-size: 12px;
        border-radius: 10px;
        padding: 4px 8px;
        font-weight: 700;
        margin-left: 6px;
    }

    .preview-actions {
        display: flex;
        gap: 8px;
    }

    .preview-actions .btn {
        border-radius: 10px;
        font-weight: 700;
        padding: 8px 14px;
    }

    .mode-switch {
        display: inline-flex;
        align-items: center;
        border: 1px solid #cfd8ea;
        border-radius: 10px;
        overflow: hidden;
        background: #fff;
    }

    .mode-switch a {
        padding: 8px 12px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 700;
        color: #4b5563;
        line-height: 1;
        border-right: 1px solid #d9e1ef;
    }

    .mode-switch a:last-child {
        border-right: 0;
    }

    .mode-switch a.active {
        background: #2563eb;
        color: #fff;
    }

    .preview-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 300px;
        gap: 14px;
        margin-top: 12px;
    }

    .preview-left {
        border: 1px solid #e5e8f0;
        border-radius: 12px;
        background: #e9ecf3;
        padding: 10px;
        max-height: 78vh;
        overflow: auto;
    }

    .label-row {
        display: grid;
        grid-template-columns: 28px minmax(0, 430px);
        gap: 8px;
        margin-bottom: 12px;
        align-items: start;
        justify-content: start;
    }

    .label-index {
        color: #8a95aa;
        font-weight: 700;
        text-align: center;
        padding-top: 8px;
    }

    .shipping-label {
        width: 430px;
        height: auto;
        max-width: 100%;
        background: #fff;
        border: 3px solid #101010;
        color: #101010;
        font-family: Arial, Helvetica, sans-serif;
        overflow: hidden;
    }

    .label-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 8px;
        padding: 10px 12px 8px;
    }

    .label-brand {
        font-size: 24px;
        font-weight: 800;
        letter-spacing: 0.2px;
        line-height: 1;
        text-transform: uppercase;
    }

    .label-standard {
        font-size: 14px;
        font-weight: 700;
        line-height: 1;
        margin-top: 7px;
    }

    .barcode-wrap {
        position: relative;
        border-top: 3px solid #101010;
        border-bottom: 3px solid #101010;
        padding: 6px 12px 7px;
    }

    .barcode-main-holder {
        height: 46px;
        margin-right: 62px;
    }

    .barcode-mini-box {
        position: absolute;
        right: 10px;
        top: -16px;
        width: 52px;
        height: 52px;
        border: 3px solid #101010;
        background: #fff;
        padding: 6px;
    }

    .barcode-mini-holder {
        width: 100%;
        height: 100%;
    }

    .barcode-svg {
        width: 100%;
        height: 100%;
        display: block;
    }

    .barcode-text {
        text-align: center;
        font-size: 26px;
        font-weight: 800;
        letter-spacing: 1.2px;
        line-height: 1;
        margin-top: 4px;
    }

    .barcode-subtext {
        text-align: center;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.15;
        margin-top: 3px;
        color: #4b5563;
        letter-spacing: 0.35px;
    }

    .label-table {
        width: 100%;
        border-collapse: collapse !important;
        border-spacing: 0 !important;
        table-layout: fixed;
        display: table !important;
    }

    .label-table tr {
        display: table-row !important;
    }

    .label-table td {
        border: 3px solid #101010 !important;
        font-size: 12px;
        vertical-align: top;
        padding: 0 !important;
        display: table-cell !important;
    }

    .label-table td:first-child {
        width: 36%;
    }

    .label-key {
        padding: 8px 7px !important;
        font-size: 14px;
        line-height: 1.05;
        font-weight: 800;
        text-transform: uppercase;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        text-align: left;
    }

    .label-value {
        padding: 7px 9px !important;
        font-size: 13px;
        line-height: 1.1;
        font-weight: 700;
        overflow: hidden;
        text-align: left;
    }

    .blk {
        background: #101010 !important;
        color: #fff !important;
    }

    .label-table tr.row-highlight td {
        background: #101010 !important;
        color: #fff !important;
    }

    .cell-fit {
        display: block;
        width: 100%;
        height: 100%;
        overflow: hidden;
        word-break: break-word;
        overflow-wrap: anywhere;
        white-space: normal;
    }

    .label-row-date td,
    .label-row-recipient td,
    .label-row-address td,
    .label-row-phone td,
    .label-row-order td,
    .label-row-extra td,
    .label-row-shipping td,
    .label-row-cod td,
    .label-row-sender td {
        height: auto !important;
    }

    .label-row-date td {
        height: 42px !important;
    }

    .label-row-recipient td {
        height: 44px !important;
    }

    .label-row-address td {
        height: 122px !important;
    }

    .label-row-phone td {
        height: 44px !important;
    }

    .label-row-order td {
        height: 108px !important;
    }

    .label-row-extra td,
    .label-row-shipping td,
    .label-row-cod td,
    .label-row-sender td {
        height: 43px !important;
    }

    .order-cell {
        display: flex;
        flex-direction: column;
        width: 100%;
        min-height: 72px;
        gap: 4px;
    }

    .order-qty {
        font-size: 14px;
        line-height: 1;
        font-weight: 800;
    }

    .order-name {
        flex: 1;
        min-height: 0;
        font-size: 13px;
        line-height: 1.05;
        font-weight: 700;
        overflow: hidden;
        word-break: break-word;
        overflow-wrap: anywhere;
    }

    .order-sku {
        font-size: 10px;
        line-height: 1;
        font-weight: 700;
        color: #5f6778;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    .label-note {
        border-top: 3px solid #101010;
        text-align: center;
        padding: 10px 12px;
        min-height: 38px;
        height: auto;
        font-size: 11px;
        line-height: 1.2;
        font-style: italic;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .preview-right {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .summary-card {
        border: 1px solid #e5e8f0;
        border-radius: 12px;
        background: #fff;
        padding: 14px;
    }

    .summary-card h5 {
        font-size: 22px;
        margin: 0 0 10px;
        font-weight: 800;
        color: #1f2937;
    }

    .summary-item {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-top: 1px solid #edf0f6;
        color: #54617b;
    }

    .summary-item:first-of-type {
        border-top: 0;
    }

    .summary-item strong {
        color: #1f2937;
        font-size: 24px;
    }

    .summary-item .cod {
        color: #08a664;
        font-size: 30px;
    }

    .aggregate-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        padding: 8px 0;
        border-top: 1px solid #edf0f6;
    }

    .aggregate-row:first-of-type {
        border-top: 0;
    }

    .aggregate-row small {
        color: #8a95aa;
    }

    .aggregate-qty {
        font-size: 11px;
        font-weight: 800;
        color: #374151;
        background: #eceff5;
        border-radius: 7px;
        padding: 4px 8px;
        white-space: nowrap;
    }

    .tip-box {
        border: 1px solid #bfdbfe;
        border-radius: 10px;
        background: #eff6ff;
        color: #4066a7;
        padding: 10px;
        font-size: 13px;
    }

    .float-aksi {
        position: fixed;
        right: 24px;
        bottom: 24px;
        z-index: 50;
    }

    .float-aksi .btn {
        border-radius: 10px;
        box-shadow: 0 8px 20px rgba(59, 130, 246, .25);
    }

    @media (max-width: 1200px) {
        .preview-layout {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 700px) {
        .shipping-label {
            width: 100%;
            height: auto;
            min-height: 0;
        }

        .label-brand {
            font-size: 22px;
        }

        .label-standard {
            font-size: 14px;
        }

        .barcode-text {
            font-size: 20px;
        }
    }

    @media print {
        @page {
            size: <?= $is_roll_mode ? '100mm auto' : '100mm 150mm' ?>;
            margin: 0;
        }

        body,
        html {
            margin: 0 !important;
            padding: 0 !important;
            background: #fff !important;
        }

        .no-print,
        .preview-page-title,
        .float-aksi,
        .sidebar,
        .offcanvas,
        .menu-dashboard,
        .topbar,
        .navbar {
            display: none !important;
        }

        .preview-shell,
        .preview-layout,
        .preview-left,
        .label-row {
            margin: 0 !important;
            padding: 0 !important;
            border: 0 !important;
            background: #fff !important;
            max-height: none !important;
            overflow: visible !important;
        }

        .label-row {
            display: block;
<?php if ($is_roll_mode): ?>
            page-break-after: auto;
            break-after: auto;
            margin: 0 0 1.2mm 0 !important;
<?php else: ?>
            page-break-after: always;
            break-after: page;
<?php endif; ?>
        }

        .label-row:last-child {
<?php if ($is_roll_mode): ?>
            margin-bottom: 0 !important;
<?php else: ?>
            page-break-after: auto;
            break-after: auto;
<?php endif; ?>
        }

        .label-index {
            display: none;
        }

        .shipping-label {
            width: 100mm !important;
            height: 150mm !important;
            border: 0.4mm solid #101010;
            box-sizing: border-box;
            margin: 0;
        }

        .label-top {
            padding: 1.35mm 1.7mm 1mm;
        }

        .label-brand {
            font-size: 5.2mm;
        }

        .label-standard {
            font-size: 2.8mm;
        }

        .barcode-wrap {
            border-top: 0.4mm solid #101010;
            border-bottom: 0.4mm solid #101010;
            padding: 1.1mm 1.7mm 1.1mm;
        }

        .barcode-main-holder {
            height: 8.2mm;
            margin-right: 15.8mm;
        }

        .barcode-mini-box {
            right: 1.7mm;
            top: -3.8mm;
            width: 12.8mm;
            height: 12.8mm;
            border: 0.4mm solid #101010;
            padding: 0.75mm;
        }

        .barcode-text {
            margin-top: 0.65mm;
            font-size: 3.9mm;
            letter-spacing: 0.24mm;
        }

        .barcode-subtext {
            margin-top: 0.4mm;
            font-size: 2.05mm;
            letter-spacing: 0.1mm;
        }

        .label-table td {
            border: 0.4mm solid #101010 !important;
        }

        .label-key {
            padding: 0.75mm 0.75mm;
            font-size: 2.1mm;
        }

        .label-value {
            padding: 0.7mm 0.85mm;
            font-size: 2.05mm;
        }

        .label-row-date td {
            height: 4.9mm;
        }

        .label-row-recipient td {
            height: 5.3mm;
        }

        .label-row-address td {
            height: 11.2mm;
        }

        .label-row-phone td {
            height: 5.1mm;
        }

        .label-row-order td {
            height: 13.8mm;
        }

        .label-row-extra td,
        .label-row-shipping td,
        .label-row-cod td,
        .label-row-sender td {
            height: 5.1mm;
        }

        .order-qty {
            font-size: 2.1mm;
        }

        .order-name {
            font-size: 2.05mm;
        }

        .order-sku {
            font-size: 1.5mm;
        }

        .label-note {
            border-top: 0.4mm solid #101010;
            height: 5.2mm;
            font-size: 1.55mm;
            line-height: 1.1;
            padding: 0.3mm 0.8mm;
        }
    }
</style>

<div class="w-100">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h3 class="preview-page-title">Bulk Shipping Label Preview</h3>
    </div>

    <div class="preview-shell">
        <div class="preview-head no-print">
            <div>
                <h4>Previewing <?= $total_orders ?> Labels <span class="batch-chip">Batch <?= htmlspecialchars($batch_code) ?></span></h4>
            </div>
            <div class="preview-actions">
                <div class="mode-switch" title="Print mode">
                    <a href="<?= htmlspecialchars($mode_roll_url) ?>" class="<?= $is_roll_mode ? 'active' : '' ?>">Roll</a>
                    <a href="<?= htmlspecialchars($mode_page_url) ?>" class="<?= !$is_roll_mode ? 'active' : '' ?>">Page</a>
                </div>
                <button type="button" id="btnPdf" class="btn btn-light border"><i class="bi bi-download me-1"></i>PDF</button>
                <button type="button" id="btnPrintAll" class="btn btn-primary"><i class="bi bi-printer me-1"></i>Print All Labels</button>
            </div>
        </div>

        <div class="preview-layout">
            <div class="preview-left">
                <?php foreach ($orders as $i => $order): ?>
                    <?php
                    $date_text = !empty($order['date']) ? date('d M Y', strtotime($order['date'])) : '-';
                    $customer = trim((string)($order['customer_text'] ?? '-'));
                    $phone = trim((string)($order['phone'] ?? '-'));
                    $address = trim((string)($order['address'] ?? '-'));
                    $shipping = trim((string)($order['shipping'] ?? '-'));
                    $marketplace = strtoupper(trim((string)($order['marketplace'] ?? 'Marketplace')));
                    $transaction_id_display = trim((string)($order['transaction_id_display'] ?? ('TRX-' . intval($order['id'] ?? 0))));
                    $barcode_ean13 = trim((string)($order['barcode_ean13'] ?? ''));
                    $barcode_code = $barcode_ean13 !== '' ? $barcode_ean13 : $transaction_id_display;
                    $barcode_type = $barcode_ean13 !== '' ? 'ean13' : 'code128';

                    $items = $order['label_items'] ?? array();
                    if (empty($items)) {
                        $items = array(array('qty' => 1, 'name' => '-', 'sku' => ''));
                    }

                    $first_qty = intval($items[0]['qty'] ?? 1);
                    $sku_text = trim((string)($items[0]['sku'] ?? ''));
                    $product_lines = array();
                    foreach ($items as $idx => $it) {
                        if ($idx > 1) {
                            break;
                        }
                        $name = trim((string)($it['name'] ?? '-'));
                        if ($idx === 0) {
                            $product_lines[] = $name;
                        } else {
                            $product_lines[] = '+ ' . $name;
                        }
                    }
                    $product_text = trim(implode("\n", $product_lines));

                    $cod_text = '-';
                    if (strtoupper((string)($order['payment_type'] ?? '')) === 'COD') {
                        $cod_text = 'Rp ' . number_format(doubleval($order['customer_price']), 0, ',', '.');
                    }

                    $extra_text = trim((string)($order['additional_note'] ?? ($order['note'] ?? ($order['catatan'] ?? ''))));
                    $extra_html = $extra_text === '' ? '&nbsp;' : htmlspecialchars($extra_text);
                    ?>
                    <div class="label-row">
                        <div class="label-index"><?= $i + 1 ?></div>
                        <div class="shipping-label">
                            <div class="label-top">
                                <span class="label-brand"><?= htmlspecialchars($marketplace) ?></span>
                                <span class="label-standard">Standard</span>
                            </div>

                            <div class="barcode-wrap">
                                <div class="barcode-main-holder">
                                    <svg class="barcode-svg js-1d-barcode"
                                         data-code="<?= htmlspecialchars($barcode_code, ENT_QUOTES) ?>"
                                         data-barcode-type="<?= htmlspecialchars($barcode_type, ENT_QUOTES) ?>"
                                         aria-hidden="true"></svg>
                                </div>
                                <div class="barcode-mini-box" title="1D barcode">
                                    <div class="barcode-mini-holder">
                                        <svg class="barcode-svg js-1d-barcode js-1d-barcode-mini"
                                             data-code="<?= htmlspecialchars($barcode_code, ENT_QUOTES) ?>"
                                             data-barcode-type="<?= htmlspecialchars($barcode_type, ENT_QUOTES) ?>"
                                             aria-hidden="true"></svg>
                                    </div>
                                </div>
                                <div class="barcode-text"><?= htmlspecialchars($barcode_code) ?></div>
                                <?php if ($barcode_code !== $transaction_id_display): ?>
                                    <div class="barcode-subtext"><?= htmlspecialchars($transaction_id_display) ?></div>
                                <?php endif; ?>
                            </div>

                            <table class="label-table">
                                <tr class="label-row-date">
                                    <td>
                                        <div class="label-key">TANGGAL KIRIM</div>
                                    </td>
                                    <td>
                                        <div class="label-value"><span class="cell-fit auto-fit" data-fit-min="7.2"><?= htmlspecialchars($date_text) ?></span></div>
                                    </td>
                                </tr>
                                <tr class="label-row-recipient row-highlight">
                                    <td class="blk">
                                        <div class="label-key">PENERIMA</div>
                                    </td>
                                    <td class="blk">
                                        <div class="label-value"><span class="cell-fit auto-fit" data-fit-min="7.2"><?= htmlspecialchars($customer) ?></span></div>
                                    </td>
                                </tr>
                                <tr class="label-row-address">
                                    <td>
                                        <div class="label-key">ALAMAT</div>
                                    </td>
                                    <td>
                                        <div class="label-value"><span class="cell-fit auto-fit" data-fit-min="6.8"><?= nl2br(htmlspecialchars($address)) ?></span></div>
                                    </td>
                                </tr>
                                <tr class="label-row-phone">
                                    <td>
                                        <div class="label-key">NO HP</div>
                                    </td>
                                    <td>
                                        <div class="label-value"><span class="cell-fit auto-fit" data-fit-min="7.2"><?= htmlspecialchars($phone) ?></span></div>
                                    </td>
                                </tr>
                                <tr class="label-row-order">
                                    <td>
                                        <div class="label-key">PESANAN</div>
                                    </td>
                                    <td>
                                        <div class="label-value">
                                            <div class="order-cell">
                                                <div class="order-qty"><?= $first_qty ?>x</div>
                                                <div class="order-name auto-fit" data-fit-min="6.6"><?= nl2br(htmlspecialchars($product_text)) ?></div>
                                                <?php if ($sku_text !== ''): ?>
                                                    <div class="order-sku auto-fit" data-fit-min="6.4">SKU: <?= htmlspecialchars($sku_text) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="label-row-extra">
                                    <td>
                                        <div class="label-key">TAMBAHAN</div>
                                    </td>
                                    <td>
                                        <div class="label-value"><span class="cell-fit auto-fit" data-fit-min="6.8"><?= $extra_html ?></span></div>
                                    </td>
                                </tr>
                                <tr class="label-row-shipping">
                                    <td>
                                        <div class="label-key">EKSPEDISI</div>
                                    </td>
                                    <td>
                                        <div class="label-value"><span class="cell-fit auto-fit" data-fit-min="7.2"><?= htmlspecialchars($shipping) ?></span></div>
                                    </td>
                                </tr>
                                <tr class="label-row-cod">
                                    <td>
                                        <div class="label-key">TOTAL COD</div>
                                    </td>
                                    <td>
                                        <div class="label-value"><span class="cell-fit auto-fit" data-fit-min="7.2"><?= htmlspecialchars($cod_text) ?></span></div>
                                    </td>
                                </tr>
                                <tr class="label-row-sender row-highlight">
                                    <td class="blk">
                                        <div class="label-key">PENGIRIM</div>
                                    </td>
                                    <td class="blk">
                                        <div class="label-value"><span class="cell-fit auto-fit" data-fit-min="6.6">PT FORBES TITIK TERANG</span></div>
                                    </td>
                                </tr>
                            </table>

                            <div class="label-note">*Tidak Menerima Komplain, Jika Tidak Menyertakan Video Unboxing*</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="preview-right no-print">
                <div class="summary-card">
                    <h5><i class="bi bi-journal-text me-2 text-primary"></i>Batch Summary</h5>
                    <div class="summary-item"><span>Total Orders</span><strong><?= $total_orders ?></strong></div>
                    <div class="summary-item"><span>Total Items</span><strong><?= $total_items ?></strong></div>
                    <div class="summary-item"><span>Total COD Value</span><strong class="cod">Rp <?= number_format($total_cod, 0, ',', '.') ?></strong></div>
                    <div class="summary-item"><span>Expedition</span><strong style="font-size:17px;"><?= htmlspecialchars($primary_expedition) ?> (<?= $primary_expedition_count ?>)</strong></div>
                </div>

                <div class="summary-card">
                    <h5>Product Aggregate</h5>
                    <?php if (empty($product_aggregate)): ?>
                        <p class="text-muted mb-0">Tidak ada data produk.</p>
                    <?php else: ?>
                        <?php foreach ($product_aggregate as $prod): ?>
                            <div class="aggregate-row">
                                <div>
                                    <div style="font-weight:700;color:#1f2937;"><?= htmlspecialchars($prod['name']) ?></div>
                                    <small>SKU: <?= htmlspecialchars($prod['sku'] ?: '-') ?></small>
                                </div>
                                <span class="aggregate-qty"><?= intval($prod['qty']) ?> pcs</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="tip-box">
                    <i class="bi bi-info-circle me-1"></i>
                    <?php if ($is_roll_mode): ?>
                        Roll mode active: labels print in continuous flow at 100mm width (150mm label blocks).
                    <?php else: ?>
                        Page mode active: labels print one per page at 100mm x 150mm.
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="float-aksi no-print">
    <button type="button" id="btnQuickPrint" class="btn btn-primary"><i class="bi bi-gear-fill me-1"></i>Aksi</button>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
    (function() {
        const baseUrl = "<?= base_url() ?>";
        const idsParam = "<?= htmlspecialchars($ids_param ?? '', ENT_QUOTES) ?>";
        let redirecting = false;
        let printTriggered = false;

        function goScanPage() {
            if (redirecting) return;
            redirecting = true;
            const suffix = idsParam ? `?ids=${encodeURIComponent(idsParam)}` : '';
            window.location.href = `${baseUrl}transaction/scan-ready-to-ship${suffix}`;
        }

        function renderBarcodeFallback(svg, code) {
            if (!svg) {
                return;
            }
            const ns = 'http://www.w3.org/2000/svg';

            while (svg.firstChild) {
                svg.removeChild(svg.firstChild);
            }

            svg.setAttribute('viewBox', '0 0 100 20');
            svg.setAttribute('preserveAspectRatio', 'none');

            const textNode = document.createElementNS(ns, 'text');
            textNode.setAttribute('x', '50');
            textNode.setAttribute('y', '14');
            textNode.setAttribute('text-anchor', 'middle');
            textNode.setAttribute('font-size', '8');
            textNode.setAttribute('font-family', 'Arial, sans-serif');
            textNode.setAttribute('fill', '#101010');
            textNode.textContent = (code || '-').toString();
            svg.appendChild(textNode);
        }

        function renderBarcode(svg) {
            if (!svg) {
                return;
            }

            const code = (svg.dataset.code || '').trim();
            const type = (svg.dataset.barcodeType || 'ean13').toLowerCase();
            const isMini = svg.classList.contains('js-1d-barcode-mini');

            if (typeof window.JsBarcode !== 'function') {
                renderBarcodeFallback(svg, code);
                return;
            }

            const options = {
                lineColor: '#101010',
                background: '#ffffff',
                displayValue: false,
                margin: isMini ? 1 : 2,
                width: isMini ? 1.2 : 1.6,
                height: isMini ? 26 : 38
            };

            try {
                if (type === 'ean13' && /^\d{13}$/.test(code)) {
                    window.JsBarcode(svg, code, Object.assign({}, options, { format: 'EAN13' }));
                } else {
                    window.JsBarcode(svg, code || '-', Object.assign({}, options, { format: 'CODE128' }));
                }
            } catch (err) {
                renderBarcodeFallback(svg, code);
            }
        }

        function renderAllBarcodes() {
            document.querySelectorAll('.js-1d-barcode').forEach(function(svg) {
                renderBarcode(svg);
            });
        }

        function fitOne(el) {
            const min = parseFloat(el.dataset.fitMin || '8');
            const style = window.getComputedStyle(el);
            const defaultSize = parseFloat(el.dataset.fitDefault || style.fontSize || '12');

            el.style.fontSize = `${defaultSize}px`;

            let currentSize = defaultSize;
            while ((el.scrollHeight > el.clientHeight || el.scrollWidth > el.clientWidth) && currentSize > min) {
                currentSize -= 0.25;
                el.style.fontSize = `${currentSize}px`;
            }
        }

        function fitAllText() {
            document.querySelectorAll('.auto-fit').forEach(function(el) {
                fitOne(el);
            });
        }

        let resizeTimer = null;

        function onResize() {
            if (resizeTimer) {
                clearTimeout(resizeTimer);
            }
            resizeTimer = setTimeout(function() {
                renderAllBarcodes();
                fitAllText();
            }, 120);
        }

        function prepareLayout() {
            renderAllBarcodes();
            fitAllText();
        }

        function triggerPrint(withRedirect) {
            printTriggered = !!withRedirect;
            prepareLayout();
            window.print();
            if (withRedirect) {
                setTimeout(function() {
                    if (printTriggered) {
                        goScanPage();
                    }
                }, 1600);
            }
        }

        window.addEventListener('load', prepareLayout);
        window.addEventListener('resize', onResize);
        window.addEventListener('beforeprint', prepareLayout);

        window.addEventListener('afterprint', function() {
            if (printTriggered) {
                printTriggered = false;
                goScanPage();
            }
        });

        document.getElementById('btnPdf')?.addEventListener('click', function() {
            triggerPrint(false);
        });

        document.getElementById('btnPrintAll')?.addEventListener('click', function() {
            triggerPrint(true);
        });

        document.getElementById('btnQuickPrint')?.addEventListener('click', function() {
            triggerPrint(true);
        });
    })();
</script>
