<style>
.scan-title {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
}
.scan-shell {
    background: #f4f6fb;
    border: 1px solid #e5e8f0;
    border-radius: 14px;
    padding: 14px;
}
.scan-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(260px, 300px);
    gap: 14px;
}
.scan-layout > div {
    min-width: 0;
}
.scan-card {
    background: #fff;
    border: 1px solid #e5e8f0;
    border-radius: 12px;
    overflow: hidden;
}
.scan-card-body {
    padding: 14px;
}
.scan-panel-head {
    text-align: center;
    padding: 20px 12px 8px;
}
.scan-panel-head h4 {
    margin: 0;
    font-size: 28px;
    font-weight: 800;
    color: #1f2937;
}
.scan-panel-head p {
    margin: 8px 0 0;
    color: #74829a;
    font-size: 14px;
}
.scan-input-wrap {
    margin: 8px auto 16px;
    max-width: 780px;
    border: 1px solid #d8e1f5;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 0 0 4px #ecf2ff;
    padding: 8px;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}
.scan-input-wrap .input-group-text {
    border: 0;
    background: transparent;
    font-size: 18px;
    color: #7b8db6;
}
.scan-input-wrap input {
    border: 0;
    box-shadow: none;
    font-size: 18px;
    color: #344054;
    min-width: 0;
}
.scan-input-wrap .btn {
    border-radius: 10px;
    min-width: 90px;
    font-size: 15px;
    font-weight: 700;
}
.scan-history-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px;
    border-bottom: 1px solid #edf0f6;
}
.scan-history-head h5 {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
    color: #1f2937;
}
.scan-history-table {
    width: 100%;
    margin: 0;
    table-layout: fixed;
}
.scan-history-table th {
    text-transform: uppercase;
    letter-spacing: .05em;
    font-size: 11px;
    color: #8590a6;
    background: #fafbff;
    padding: 10px;
    border-bottom: 1px solid #edf0f6;
}
.scan-history-table td {
    font-size: 14px;
    padding: 10px;
    border-bottom: 1px solid #edf0f6;
    vertical-align: top;
    word-break: break-word;
}
.scan-tag {
    font-size: 11px;
    border-radius: 999px;
    padding: 4px 9px;
    font-weight: 700;
    display: inline-block;
}
.scan-tag.ok {
    background: #dcfce7;
    color: #15803d;
}
.scan-tag.fail {
    background: #fee2e2;
    color: #b91c1c;
}
.scan-right-top {
    border-radius: 14px;
    background: linear-gradient(120deg, #3b82f6, #2f67db);
    color: #fff;
    padding: 16px;
}
.scan-right-top .label {
    opacity: .88;
    font-size: 13px;
}
.scan-right-top .big {
    font-size: 34px;
    font-weight: 800;
    line-height: 1;
}
.scan-right-top .started {
    margin-top: 8px;
    background: rgba(255,255,255,.16);
    border-radius: 8px;
    padding: 6px 10px;
    font-size: 14px;
}
.last-card h6 {
    margin: 0 0 10px;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #8590a6;
}
.last-order {
    font-size: 24px;
    font-weight: 800;
    color: #1f2937;
    line-height: 1.1;
    word-break: break-word;
}
.stat-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.stat-box {
    border: 1px solid #e5e8f0;
    border-radius: 12px;
    background: #fff;
    padding: 12px;
}
.stat-box .num {
    font-size: 28px;
    font-weight: 800;
    line-height: 1;
}
.stat-box.success .num { color: #10b981; }
.stat-box.fail .num { color: #f43f5e; }
.stat-box .txt { color: #7a869d; margin-top: 4px; }
.switch-wrap {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #475569;
}
.switch-wrap input[type="checkbox"] {
    width: 40px;
    height: 22px;
}
.complete-btn {
    width: 100%;
    height: 42px;
    border-radius: 10px;
    font-weight: 700;
}
.mkt-pill {
    display: inline-block;
    background: #111;
    color: #fff;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 700;
    padding: 4px 8px;
}
.confirm-ready-msg {
    font-size: 14px;
    line-height: 1.55;
    color: #1f2937;
    word-break: break-word;
}
.confirm-ready-msg .highlight {
    font-weight: 700;
    color: #0f172a;
}
@media (max-width: 1200px) {
    .scan-layout {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 768px) {
    .scan-panel-head h4 {
        font-size: 22px;
    }
    .scan-input-wrap input {
        font-size: 16px;
    }
    .scan-history-table th,
    .scan-history-table td {
        font-size: 12px;
        padding: 8px;
    }
}
</style>

<div class="w-100">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
        <h3 class="scan-title"><i class="bi bi-upc-scan me-2" style="font-size:24px;color:#3b82f6;"></i>Scan to Ready to Ship</h3>
        <label class="switch-wrap">
            <input type="checkbox" id="autoPrintSwitch">
            <span>Auto-print label</span>
        </label>
    </div>

    <div class="scan-shell">
        <div class="scan-layout">
            <div>
                <div class="scan-card mb-3">
                    <div class="scan-panel-head">
                        <h4>Scan Barcode / ID Transaksi</h4>
                        <p>Scan `transaction_id` (contoh: TRX-12345), `order_id`, atau `awb_number`.</p>
                    </div>
                    <div class="scan-card-body" style="padding-top:0;">
                        <div class="scan-input-wrap">
                            <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                            <input type="text" class="form-control" id="scanInput" placeholder="Scan transaction id / barcode..." autocomplete="off" autofocus>
                            <button type="button" class="btn btn-primary" id="scanSubmitBtn">Enter</button>
                        </div>
                        <div class="small text-muted">
                            Batch filter: <strong><?= !empty($ids_param) ? htmlspecialchars($ids_param) : 'Semua order' ?></strong>
                            <?php if (!empty($batch_total)): ?>
                                (<?= intval($batch_total) ?> order)
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="scan-card">
                    <div class="scan-history-head">
                        <h5><i class="bi bi-clock-history me-1 text-primary"></i>Scan History</h5>
                        <button class="btn btn-sm btn-light border" id="clearHistoryBtn">Clear History</button>
                    </div>
                    <div class="table-responsive" style="max-height:500px;overflow:auto;">
                        <table class="table scan-history-table">
                            <thead>
                                <tr>
                                    <th style="width:110px;">Time</th>
                                    <th>Order Details</th>
                                    <th>Status Update</th>
                                    <th style="width:220px;">Info</th>
                                </tr>
                            </thead>
                            <tbody id="scanHistoryBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div>
                <div class="scan-right-top mb-3">
                    <div class="label">Total Scanned This Session</div>
                    <div class="big" id="totalScannedNum">0</div>
                    <div class="started">Started: <span id="sessionStartText"></span></div>
                </div>

                <div class="scan-card last-card mb-3">
                    <div class="scan-card-body">
                        <h6>Last Scanned Item</h6>
                        <div id="lastScannedWrap" class="text-muted">No item scanned yet.</div>
                    </div>
                </div>

                <div class="stat-grid mb-3">
                    <div class="stat-box success">
                        <div class="num" id="successNum">0</div>
                        <div class="txt">Success</div>
                    </div>
                    <div class="stat-box fail">
                        <div class="num" id="failNum">0</div>
                        <div class="txt">Failed/Error</div>
                    </div>
                </div>

                <button class="btn btn-light border complete-btn" id="completeSessionBtn"><i class="bi bi-check-circle-fill me-1"></i>Complete Session</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmReadyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Siap Dikirim</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="confirmReadyMessage" class="confirm-ready-msg"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="confirmReadyBtn">Ya, Ubah Status</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const baseUrl = "<?= base_url() ?>";
    const submitUrl = `${baseUrl}transaction/scan-ready-to-ship/submit`;
    const batchIds = "<?= htmlspecialchars($ids_param ?? '', ENT_QUOTES) ?>";

    const $input = $('#scanInput');
    const $submitBtn = $('#scanSubmitBtn');
    const $historyBody = $('#scanHistoryBody');
    const $autoPrint = $('#autoPrintSwitch');
    const $confirmBtn = $('#confirmReadyBtn');
    const $confirmReadyMessage = $('#confirmReadyMessage');
    const confirmModalEl = document.getElementById('confirmReadyModal');
    const confirmModal = (confirmModalEl && window.bootstrap) ? new bootstrap.Modal(confirmModalEl) : null;

    const state = {
        total: 0,
        success: 0,
        failed: 0,
        startedAt: new Date(),
        history: [],
        lastItem: null,
        pendingConfirm: null
    };

    function formatTime(d) {
        const dt = (d instanceof Date) ? d : new Date(d);
        return dt.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function esc(v) {
        return String(v == null ? '' : v)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function refreshSummary() {
        $('#totalScannedNum').text(state.total);
        $('#successNum').text(state.success);
        $('#failNum').text(state.failed);
        $('#sessionStartText').text(formatTime(state.startedAt));
    }

    function renderLastItem() {
        const d = state.lastItem;
        if (!d) {
            $('#lastScannedWrap').html('No item scanned yet.');
            return;
        }

        $('#lastScannedWrap').html(`
            <div class="mb-2"><span class="mkt-pill">${esc(d.marketplace || '-')}</span></div>
            <div class="small text-muted">Order ID</div>
            <div class="last-order">#${esc(d.order_id || '-')}</div>
            <div class="small text-muted mt-2">Customer</div>
            <div style="font-weight:700;color:#1f2937;">${esc(d.customer_text || '-')}</div>
            <div class="small text-muted">${esc(d.phone || '-')}</div>
            <hr>
            <div class="small text-muted">Courier</div>
            <div style="font-weight:700;color:#1f2937;"><i class="bi bi-truck me-1"></i>${esc(d.shipping || '-')}</div>
            <div class="small text-muted mt-1">Status: ${esc(d.order_status || '-')}</div>
        `);
    }

    function renderHistory() {
        if (!state.history.length) {
            $historyBody.html(`<tr><td colspan="4" class="text-center text-muted py-4">Belum ada scan.</td></tr>`);
            return;
        }

        const html = state.history.map((item) => {
            if (item.ok) {
                return `
                    <tr>
                        <td>${esc(item.time)}</td>
                        <td>
                            <div style="font-weight:700;">#${esc(item.data.order_id || '-')}</div>
                            <div class="small text-muted">${esc(item.data.customer_text || '-')}</div>
                        </td>
                        <td><span class="scan-tag ok">Updated to READY_TO_SHIP</span></td>
                        <td>
                            <div class="small text-muted">From: ${esc(item.data.previous_status || '-')}</div>
                            <div class="small text-muted">Scan: ${esc(item.scanCode)}</div>
                        </td>
                    </tr>
                `;
            }

            return `
                <tr>
                    <td>${esc(item.time)}</td>
                    <td>
                        <div style="font-weight:700;">#-</div>
                        <div class="small text-muted">${esc(item.scanCode)}</div>
                    </td>
                    <td><span class="scan-tag fail">Failed</span></td>
                    <td><div class="small text-danger">${esc(item.message)}</div></td>
                </tr>
            `;
        }).join('');

        $historyBody.html(html);
    }

    function addHistory(entry) {
        state.history.unshift(entry);
        if (state.history.length > 40) {
            state.history = state.history.slice(0, 40);
        }
        renderHistory();
    }

    function printSingleLabel(data) {
        const win = window.open('', '_blank', 'width=420,height=520');
        if (!win) return;

        const html = `
            <html>
            <head>
                <title>Print Label</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 12px; }
                    .box { border: 2px solid #111; padding: 10px; }
                    .title { font-weight: 800; font-size: 16px; margin-bottom: 8px; }
                    .row { margin-bottom: 5px; font-size: 13px; }
                </style>
            </head>
            <body>
                <div class="box">
                    <div class="title">READY_TO_SHIP</div>
                    <div class="row"><strong>Order:</strong> ${esc(data.order_id || '-')}</div>
                    <div class="row"><strong>AWB:</strong> ${esc(data.awb_number || '-')}</div>
                    <div class="row"><strong>Customer:</strong> ${esc(data.customer_text || '-')}</div>
                    <div class="row"><strong>Courier:</strong> ${esc(data.shipping || '-')}</div>
                </div>
                <script>window.print(); setTimeout(function(){ window.close(); }, 300);<\/script>
            </body>
            </html>
        `;

        win.document.open();
        win.document.write(html);
        win.document.close();
    }

    function buildConfirmationHtml(data) {
        return `
            ID transaksi <span class="highlight">${esc(data.transaction_id_display || ('TRX-' + (data.transaction_id || '-')))}</span>
            dengan nama_penerima <span class="highlight">${esc(data.customer_text || '-')}</span>,
            pesanan <span class="highlight">${esc(data.items_summary || '-')}</span>,
            alamat <span class="highlight">${esc(data.address || '-')}</span>,
            no_hp <span class="highlight">${esc(data.phone || '-')}</span>
            akan diubah statusnya menjadi <span class="highlight">Siap Dikirim</span>.
        `;
    }

    function openConfirmModal(scanCode, data) {
        state.pendingConfirm = {
            scanCode: scanCode,
            data: data || {}
        };

        const html = buildConfirmationHtml(data || {});
        $confirmReadyMessage.html(html);

        if (confirmModal) {
            confirmModal.show();
            return;
        }

        const plain = $confirmReadyMessage.text();
        if (window.confirm(plain)) {
            confirmReadyStatus();
        } else {
            state.pendingConfirm = null;
            $input.focus();
        }
    }

    function confirmReadyStatus() {
        if (!state.pendingConfirm) return;
        const pending = state.pendingConfirm;

        $confirmBtn.prop('disabled', true).text('Memproses...');

        $.ajax({
            type: 'POST',
            url: submitUrl,
            dataType: 'json',
            data: {
                action: 'confirm',
                scan_code: pending.scanCode,
                transaction_id: pending.data.transaction_id || '',
                batch_ids: batchIds
            },
            success: function(resp) {
                const now = formatTime(new Date());
                state.total += 1;

                if (resp && resp.status) {
                    state.success += 1;
                    state.lastItem = resp.data || pending.data || null;
                    addHistory({
                        ok: true,
                        time: now,
                        scanCode: pending.scanCode,
                        data: resp.data || pending.data || {}
                    });

                    if ($autoPrint.is(':checked') && state.lastItem) {
                        printSingleLabel(state.lastItem);
                    }
                } else {
                    state.failed += 1;
                    addHistory({
                        ok: false,
                        time: now,
                        scanCode: pending.scanCode,
                        message: (resp && resp.message) ? resp.message : 'Unknown error'
                    });
                }

                refreshSummary();
                renderLastItem();
            },
            error: function(xhr) {
                const now = formatTime(new Date());
                state.total += 1;
                state.failed += 1;
                addHistory({
                    ok: false,
                    time: now,
                    scanCode: pending.scanCode,
                    message: `HTTP ${xhr.status || ''} - gagal menghubungi server`
                });
                refreshSummary();
            },
            complete: function() {
                state.pendingConfirm = null;
                $confirmBtn.prop('disabled', false).text('Ya, Ubah Status');
                if (confirmModal) {
                    confirmModal.hide();
                }
                $input.val('').focus();
            }
        });
    }

    function submitScan() {
        const scanCode = ($input.val() || '').trim();
        if (!scanCode) return;

        $submitBtn.prop('disabled', true).text('Cek...');

        $.ajax({
            type: 'POST',
            url: submitUrl,
            dataType: 'json',
            data: {
                action: 'preview',
                scan_code: scanCode,
                batch_ids: batchIds
            },
            success: function(resp) {
                if (resp && resp.status) {
                    openConfirmModal(scanCode, resp.data || {});
                } else {
                    const now = formatTime(new Date());
                    state.total += 1;
                    state.failed += 1;
                    addHistory({
                        ok: false,
                        time: now,
                        scanCode: scanCode,
                        message: (resp && resp.message) ? resp.message : 'Unknown error'
                    });
                    refreshSummary();
                }
            },
            error: function(xhr) {
                const now = formatTime(new Date());
                state.total += 1;
                state.failed += 1;
                addHistory({
                    ok: false,
                    time: now,
                    scanCode: scanCode,
                    message: `HTTP ${xhr.status || ''} - gagal menghubungi server`
                });
                refreshSummary();
            },
            complete: function() {
                $submitBtn.prop('disabled', false).text('Enter');
                if (!state.pendingConfirm) {
                    $input.val('').focus();
                }
            }
        });
    }

    $submitBtn.on('click', submitScan);
    $input.on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            submitScan();
        }
    });

    $('#clearHistoryBtn').on('click', function() {
        state.history = [];
        renderHistory();
    });

    $('#completeSessionBtn').on('click', function() {
        window.location.href = `${baseUrl}transaction?order_status=READY_TO_SHIP`;
    });
    $confirmBtn.on('click', confirmReadyStatus);
    if (confirmModalEl) {
        confirmModalEl.addEventListener('hidden.bs.modal', function() {
            state.pendingConfirm = null;
            $input.focus();
        });
    }

    refreshSummary();
    renderLastItem();
    renderHistory();
    $input.focus();
})();
</script>
