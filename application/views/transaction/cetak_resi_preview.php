<?php
$total_orders = intval($summary['total_orders'] ?? 0);
$total_items = intval($summary['total_items'] ?? 0);
$total_cod = doubleval($summary['total_cod'] ?? 0);
$product_aggregate = $summary['product_aggregate'] ?? array();
$primary_expedition = $summary['primary_expedition'] ?? '-';
$primary_expedition_count = intval($summary['primary_expedition_count'] ?? 0);
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
            size: 140mm 200mm;
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
            page-break-after: always;
            break-after: page;
        }

        .label-row:last-child {
            page-break-after: auto;
            break-after: auto;
        }

        .label-index {
            display: none;
        }

        .shipping-label {
            width: 140mm !important;
            height: 200mm !important;
            border: 0.55mm solid #101010;
            box-sizing: border-box;
            margin: 0;
        }

        .label-top {
            padding: 1.9mm 2.2mm 1.2mm;
        }

        .label-brand {
            font-size: 7.2mm;
        }

        .label-standard {
            font-size: 3.9mm;
        }

        .barcode-wrap {
            border-top: 0.55mm solid #101010;
            border-bottom: 0.55mm solid #101010;
            padding: 1.2mm 2.2mm 1.3mm;
        }

        .barcode-main-holder {
            height: 9.2mm;
            margin-right: 21.5mm;
        }

        .barcode-mini-box {
            right: 2.2mm;
            top: -4.8mm;
            width: 16.8mm;
            height: 16.8mm;
            border: 0.55mm solid #101010;
            padding: 1mm;
        }

        .barcode-text {
            margin-top: 0.8mm;
            font-size: 5.6mm;
            letter-spacing: 0.38mm;
        }

        .label-table td {
            border: 0.55mm solid #101010 !important;
        }

        .label-key {
            padding: 1mm 1mm;
            font-size: 2.95mm;
        }

        .label-value {
            padding: 0.9mm 1.1mm;
            font-size: 2.85mm;
        }

        .label-row-date td {
            height: 6.8mm;
        }

        .label-row-recipient td {
            height: 7.4mm;
        }

        .label-row-address td {
            height: 14.8mm;
        }

        .label-row-phone td {
            height: 7.2mm;
        }

        .label-row-order td {
            height: 18.8mm;
        }

        .label-row-extra td,
        .label-row-shipping td,
        .label-row-cod td,
        .label-row-sender td {
            height: 7mm;
        }

        .order-qty {
            font-size: 2.9mm;
        }

        .order-name {
            font-size: 2.8mm;
        }

        .order-sku {
            font-size: 2.1mm;
        }

        .label-note {
            border-top: 0.55mm solid #101010;
            height: 7.2mm;
            font-size: 2.2mm;
            line-height: 1.1;
            padding: 0.45mm 1mm;
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
                    $barcode_code = 'TRX-' . intval($order['id'] ?? 0);

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
                                    <svg class="barcode-svg js-1d-barcode" data-code="<?= htmlspecialchars($barcode_code, ENT_QUOTES) ?>" aria-hidden="true"></svg>
                                </div>
                                <div class="barcode-mini-box" title="1D barcode">
                                    <div class="barcode-mini-holder">
                                        <svg class="barcode-svg js-1d-barcode js-1d-barcode-mini" data-code="<?= htmlspecialchars($barcode_code, ENT_QUOTES) ?>" aria-hidden="true"></svg>
                                    </div>
                                </div>
                                <div class="barcode-text"><?= htmlspecialchars($barcode_code) ?></div>
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
                    Labels will print in order shown. Ensure you have at least <?= max(1, $total_orders) ?> sheets of 4×6" thermal paper loaded.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="float-aksi no-print">
    <button type="button" id="btnQuickPrint" class="btn btn-primary"><i class="bi bi-gear-fill me-1"></i>Aksi</button>
</div>

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

        function renderPseudoBarcode(svg, code) {
            if (!svg) return;

            const text = (code || '-').toString();
            const ns = 'http://www.w3.org/2000/svg';
            const isMini = svg.classList.contains('js-1d-barcode-mini');
            const bounds = svg.getBoundingClientRect();
            const width = Math.max(32, Math.floor(bounds.width || svg.clientWidth || (isMini ? 64 : 320)));
            const height = Math.max(20, Math.floor(bounds.height || svg.clientHeight || (isMini ? 64 : 74)));
            const quiet = isMini ? 4 : 8;

            const pattern = [2, 1, 2, 1, 3, 1];
            for (let i = 0; i < text.length; i++) {
                const c = text.charCodeAt(i);
                const seed = (c + (i * 17)) % 97;
                pattern.push(1 + (seed % 3));
                pattern.push(1 + ((seed >> 2) % 2));
                pattern.push(2 + ((seed >> 4) % 3));
                pattern.push(1 + ((seed >> 6) % 2));
                pattern.push(1 + ((seed >> 1) % 3));
                pattern.push(1);
            }
            pattern.push(3, 1, 1, 2, 1, 2);

            while (svg.firstChild) {
                svg.removeChild(svg.firstChild);
            }

            svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
            svg.setAttribute('preserveAspectRatio', 'none');

            const totalModules = pattern.reduce((sum, n) => sum + n, 0);
            const drawableWidth = Math.max(1, width - (quiet * 2));
            const moduleWidth = drawableWidth / totalModules;

            let x = quiet;
            let isBar = true;

            for (let i = 0; i < pattern.length; i++) {
                const barWidth = pattern[i] * moduleWidth;
                if (isBar) {
                    const rect = document.createElementNS(ns, 'rect');
                    rect.setAttribute('x', x.toFixed(2));
                    rect.setAttribute('y', '0');
                    rect.setAttribute('width', Math.max(0.9, barWidth).toFixed(2));
                    rect.setAttribute('height', height.toString());
                    rect.setAttribute('fill', '#101010');
                    svg.appendChild(rect);
                }
                x += barWidth;
                isBar = !isBar;
            }
        }

        function renderAllBarcodes() {
            document.querySelectorAll('.js-1d-barcode').forEach(function(svg) {
                renderPseudoBarcode(svg, svg.dataset.code || '-');
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
