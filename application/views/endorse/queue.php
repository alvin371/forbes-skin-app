<?php
$filter_id_campaign = isset($filter_id_campaign) ? intval($filter_id_campaign) : 0;
$campaigns = isset($campaigns) ? $campaigns : [];
?>

<style>
    .queue-summary { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
    .queue-card { flex: 1 1 160px; padding: 12px 16px; border-radius: 8px; background: #fff; border: 1px solid #e5e7eb; }
    .queue-card .label { font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; }
    .queue-card .value { font-size: 24px; font-weight: 600; margin-top: 4px; }
    .queue-card.pending    .value { color: #6b7280; }
    .queue-card.processing .value { color: #2563eb; }
    .queue-card.completed  .value { color: #059669; }
    .queue-card.failed     .value { color: #dc2626; }
    .queue-status-pill { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 12px; font-weight: 500; }
    .queue-status-pill.pending    { background: #f3f4f6; color: #374151; }
    .queue-status-pill.processing { background: #dbeafe; color: #1e40af; }
    .queue-status-pill.completed  { background: #d1fae5; color: #065f46; }
    .queue-status-pill.failed     { background: #fee2e2; color: #991b1b; }
    .queue-error-msg { font-size: 12px; color: #991b1b; word-break: break-word; max-width: 320px; }
    .queue-link { font-size: 12px; color: #2563eb; text-decoration: none; word-break: break-all; }
    .queue-link:hover { text-decoration: underline; }
    .queue-filters { display: flex; gap: 12px; flex-wrap: wrap; align-items: end; margin-bottom: 12px; }
    .queue-filters .form-group { margin-bottom: 0; }
    #queueTable td { vertical-align: top; }
</style>

<div class="container-fluid pt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">Antrian Refresh Konten</h4>
            <small class="text-muted">Status proses sinkronisasi data sosial media untuk endorse content.</small>
        </div>
        <div>
            <button class="btn btn-outline-danger btn-sm" id="btnRetryFailed" disabled>
                <i class="fa fa-redo"></i> Retry Gagal Terpilih
            </button>
        </div>
    </div>

    <div class="queue-summary">
        <div class="queue-card pending"><div class="label">Menunggu</div><div class="value" id="sum-pending">0</div></div>
        <div class="queue-card processing"><div class="label">Berjalan</div><div class="value" id="sum-processing">0</div></div>
        <div class="queue-card completed"><div class="label">Berhasil</div><div class="value" id="sum-completed">0</div></div>
        <div class="queue-card failed"><div class="label">Gagal</div><div class="value" id="sum-failed">0</div></div>
    </div>

    <div class="queue-filters card p-3">
        <div class="form-group">
            <label class="small">Campaign</label>
            <select id="filter-campaign" class="form-control form-control-sm">
                <option value="0">Semua Campaign</option>
                <?php foreach ($campaigns as $c): ?>
                    <option value="<?= intval($c['id']) ?>" <?= $filter_id_campaign == intval($c['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="small d-block">Status</label>
            <label class="me-2"><input type="checkbox" class="filter-status" value="pending" checked> Menunggu</label>
            <label class="me-2"><input type="checkbox" class="filter-status" value="processing" checked> Berjalan</label>
            <label class="me-2"><input type="checkbox" class="filter-status" value="completed" checked> Berhasil</label>
            <label class="me-2"><input type="checkbox" class="filter-status" value="failed" checked> Gagal</label>
        </div>
        <div class="form-group">
            <label class="small">Rentang</label>
            <select id="filter-since" class="form-control form-control-sm">
                <option value="6">6 jam terakhir</option>
                <option value="24" selected>24 jam terakhir</option>
                <option value="72">3 hari terakhir</option>
                <option value="168">7 hari terakhir</option>
            </select>
        </div>
        <div class="form-group">
            <button id="btnReload" class="btn btn-primary btn-sm"><i class="fa fa-sync"></i> Refresh</button>
        </div>
    </div>

    <div class="card p-3">
        <table id="queueTable" class="table table-hover" style="width:100%">
            <thead>
                <tr>
                    <th style="width:30px"><input type="checkbox" id="checkAll"></th>
                    <th>Campaign</th>
                    <th>Influencer</th>
                    <th>Platform</th>
                    <th>Konten</th>
                    <th>Status</th>
                    <th>Percobaan</th>
                    <th>Mulai</th>
                    <th>Selesai</th>
                    <th>Pesan</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<script>
(function(){
    const baseUrl = '<?= base_url() ?>';
    let pollTimer = null;

    function getStatusFilter() {
        return $('.filter-status:checked').map(function(){ return this.value; }).get();
    }

    function statusPill(s) {
        const labels = {
            pending: 'Menunggu', processing: 'Berjalan',
            completed: 'Berhasil', failed: 'Gagal'
        };
        return '<span class="queue-status-pill ' + s + '">' + (labels[s] || s) + '</span>';
    }

    function escHtml(s) {
        return $('<div>').text(s == null ? '' : String(s)).html();
    }

    function loadData() {
        const params = {
            id_campaign: $('#filter-campaign').val(),
            status:      getStatusFilter(),
            since_hours: $('#filter-since').val(),
            length:      100,
            start:       0
        };

        $.ajax({
            url: baseUrl + 'endorse/queue-data',
            method: 'GET',
            data: params,
            traditional: true,
            dataType: 'json',
            success: function(resp) {
                $('#sum-pending').text(resp.summary.pending || 0);
                $('#sum-processing').text(resp.summary.processing || 0);
                $('#sum-completed').text(resp.summary.completed || 0);
                $('#sum-failed').text(resp.summary.failed || 0);

                const rows = resp.data || [];
                const $tbody = $('#queueTable tbody').empty();
                if (!rows.length) {
                    $tbody.append('<tr><td colspan="10" class="text-center text-muted py-4">Tidak ada data dalam rentang waktu yang dipilih.</td></tr>');
                } else {
                    rows.forEach(function(r) {
                        const checkable = r.status === 'failed';
                        const cb = checkable
                            ? '<input type="checkbox" class="row-check" value="' + r.id + '">'
                            : '';
                        $tbody.append(
                            '<tr>' +
                                '<td>' + cb + '</td>' +
                                '<td>' + escHtml(r.campaign_title || '#' + r.id_campaign) + '</td>' +
                                '<td>' + escHtml(r.influencer_name || '-') + '</td>' +
                                '<td>' + escHtml(r.platform) + '</td>' +
                                '<td><a class="queue-link" target="_blank" href="' + escHtml(r.link_upload) + '">' + escHtml(r.link_upload) + '</a></td>' +
                                '<td>' + statusPill(r.status) + '</td>' +
                                '<td>' + r.attempts + ' / ' + r.max_attempts + '</td>' +
                                '<td><small>' + escHtml(r.started_at || '-') + '</small></td>' +
                                '<td><small>' + escHtml(r.completed_at || '-') + '</small></td>' +
                                '<td><div class="queue-error-msg">' + escHtml(r.error_message || '') + '</div></td>' +
                            '</tr>'
                        );
                    });
                }

                updateRetryButton();

                const stillRunning = (resp.summary.pending + resp.summary.processing) > 0;
                schedulePoll(stillRunning);
            }
        });
    }

    function schedulePoll(active) {
        if (pollTimer) { clearTimeout(pollTimer); pollTimer = null; }
        if (active) {
            pollTimer = setTimeout(loadData, 5000);
        }
    }

    function updateRetryButton() {
        const n = $('.row-check:checked').length;
        $('#btnRetryFailed').prop('disabled', n === 0)
            .text(n > 0 ? 'Retry Gagal Terpilih (' + n + ')' : 'Retry Gagal Terpilih');
    }

    $('#filter-campaign, #filter-since').on('change', loadData);
    $(document).on('change', '.filter-status', loadData);
    $('#btnReload').on('click', loadData);

    $('#checkAll').on('change', function() {
        $('.row-check').prop('checked', this.checked);
        updateRetryButton();
    });
    $(document).on('change', '.row-check', updateRetryButton);

    $('#btnRetryFailed').on('click', function() {
        const ids = $('.row-check:checked').map(function(){ return this.value; }).get();
        if (!ids.length) return;
        const $btn = $(this).prop('disabled', true).text('Memproses...');
        $.ajax({
            url: baseUrl + 'endorse/force-retry',
            method: 'POST',
            data: { ids: ids },
            traditional: true,
            dataType: 'json',
            success: function(resp) {
                alert(resp.msg);
                loadData();
            },
            complete: function() {
                $btn.prop('disabled', false).text('Retry Gagal Terpilih');
            }
        });
    });

    loadData();
})();
</script>
