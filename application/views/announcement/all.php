<style>
    .announcement-card .card { border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09); margin-bottom: 16px; }
    .announcement-card .card-header { background:#fff; border-bottom:1px solid #f0f0f0; padding:16px; }
    .announcement-card .card-body { padding:16px; }
    .announcement-card .table { width:100%; border:1px solid #f0f0f0; border-radius:2px; border-collapse:separate; border-spacing:0; }
    .announcement-card .table thead th { background:#fafafa; color:rgba(0,0,0,0.85); font-weight:500; text-align:left; padding:12px 8px; font-size:14px; border-bottom:1px solid #f0f0f0; }
    .announcement-card .table tbody td { padding:12px 8px; font-size:14px; color:rgba(0,0,0,0.65); border-bottom:1px solid #f0f0f0; vertical-align: middle; }
    .announcement-card .table-hover tbody tr:hover { background:#fafafa; }
    .announcement-card .form-control, .announcement-card .form-select { height:34px; padding:4px 11px; font-size:14px; border:1px solid #d9d9d9; border-radius:2px; }
    .announcement-card .filter-label { font-size:12px; color:rgba(0,0,0,0.55); margin-bottom:2px; display:block; }
    .ann-badge { font-size:12px; height:22px; padding:0 8px; line-height:22px; border-radius:2px; font-weight:normal; display:inline-block; }
    .ann-pin { color:#faad14; }
    .ann-pagination .btn { margin-right:6px; margin-bottom:6px; }
    .ann-actions a { font-size:15px; text-decoration:none; margin-right:10px; }
</style>

<div class="container-fluid py-3 announcement-card">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0,0,0,0.85);">Announcements</h5>
                <?php if (!empty($permissions['can_create'])): ?>
                    <a href="<?= base_url() ?>announcement/create_page" class="btn btn-primary">
                        <i class="bi bi-plus me-1"></i> Tambah Announcement
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">

            <div class="row g-2 mb-2">
                <div class="col-md-4">
                    <label class="filter-label">Cari</label>
                    <input type="text" id="f-search" class="form-control" placeholder="Judul / konten...">
                </div>
                <div class="col-md-2">
                    <label class="filter-label">Kategori</label>
                    <select id="f-category" class="form-select">
                        <option value="">Semua</option>
                        <?php foreach (array_keys($categories) as $cat): ?>
                            <option value="<?= htmlspecialchars($cat, ENT_QUOTES) ?>"><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="filter-label">Subkategori</label>
                    <select id="f-subcategory" class="form-select">
                        <option value="">Semua</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="filter-label">Status</label>
                    <select id="f-status" class="form-select">
                        <option value="">Semua</option>
                        <?php foreach ($statuses as $st): ?>
                            <option value="<?= $st ?>"><?= ucfirst(strtolower($st)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="filter-label">Prioritas</label>
                    <select id="f-priority" class="form-select">
                        <option value="">Semua</option>
                        <?php foreach ($priorities as $pr): ?>
                            <option value="<?= $pr ?>"><?= ucfirst(strtolower($pr)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-md-2">
                    <label class="filter-label">Pinned</label>
                    <select id="f-pinned" class="form-select">
                        <option value="">Semua</option>
                        <option value="1">Pinned</option>
                        <option value="0">Tidak</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="filter-label">Publish dari</label>
                    <input type="date" id="f-start" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="filter-label">Publish sampai</label>
                    <input type="date" id="f-end" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="filter-label">Urutkan</label>
                    <select id="f-sort" class="form-select">
                        <option value="newest">Terbaru</option>
                        <option value="oldest">Terlama</option>
                        <option value="title">Judul</option>
                        <option value="category">Kategori</option>
                        <option value="priority">Prioritas</option>
                        <option value="publish">Tanggal Publish</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button id="f-reset" class="btn btn-outline-secondary w-100"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</button>
                </div>
            </div>

            <div class="alert alert-info py-2" id="ann-count" style="display:none;"></div>

            <div class="table-responsive">
                <table class="table table-hover" id="announcement-table">
                    <thead>
                        <tr>
                            <th style="width:50px;">#</th>
                            <th>Judul</th>
                            <th>Kategori</th>
                            <th>Status</th>
                            <th>Prioritas</th>
                            <th>Publish</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="announcement-tbody"></tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3 ann-pagination" id="announcement-pagination"></div>
        </div>
    </div>
</div>

<script>
    var ANN_BASE = "<?= base_url() ?>";
    var ANN_CATEGORIES = <?= json_encode($categories) ?>;
    var ANN_CAN_CREATE = <?= !empty($permissions['can_create']) ? 'true' : 'false' ?>;
    var annState = { page: 1 };

    function annEsc(s) {
        return (s === null || s === undefined) ? '' : String(s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function annStatusBadge(s) {
        var map = { DRAFT: 'background:#f0f0f0;color:#595959;', PUBLISHED: 'background:#e6f7ff;color:#1890ff;', ARCHIVED: 'background:#fff1f0;color:#cf1322;' };
        return '<span class="ann-badge" style="' + (map[s] || '') + '">' + annEsc(s ? s.charAt(0) + s.slice(1).toLowerCase() : '') + '</span>';
    }
    function annPriorityBadge(p) {
        var map = { LOW: 'background:#f6ffed;color:#52c41a;', MEDIUM: 'background:#e6f7ff;color:#1890ff;', HIGH: 'background:#fff7e6;color:#fa8c16;', URGENT: 'background:#fff1f0;color:#cf1322;' };
        return '<span class="ann-badge" style="' + (map[p] || '') + '">' + annEsc(p ? p.charAt(0) + p.slice(1).toLowerCase() : '') + '</span>';
    }
    function annDate(d) {
        if (!d) return '-';
        var dt = new Date(d.replace(' ', 'T'));
        if (isNaN(dt.getTime())) return annEsc(d);
        return dt.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function annSyncSubcategories(selected) {
        var cat = $('#f-category').val();
        var $sub = $('#f-subcategory');
        $sub.empty().append('<option value="">Semua</option>');
        if (cat && ANN_CATEGORIES[cat]) {
            ANN_CATEGORIES[cat].forEach(function (sc) {
                $sub.append('<option value="' + annEsc(sc) + '">' + annEsc(sc) + '</option>');
            });
        }
        if (selected) $sub.val(selected);
    }

    function annFilters() {
        return {
            search: $('#f-search').val(),
            category: $('#f-category').val(),
            subcategory: $('#f-subcategory').val(),
            status: $('#f-status').val(),
            priority: $('#f-priority').val(),
            is_pinned: $('#f-pinned').val(),
            publish_start_at: $('#f-start').val(),
            publish_end_at: $('#f-end').val(),
            sort_by: $('#f-sort').val(),
            page: annState.page
        };
    }

    function annRenderRows(res) {
        var rows = res.data || [];
        var tbody = $('#announcement-tbody');
        if (!rows.length) {
            tbody.html('<tr><td colspan="7" class="text-center text-muted py-4"><i class="bi bi-inbox me-1"></i> Tidak ada announcement</td></tr>');
            return;
        }
        var html = '';
        rows.forEach(function (r, i) {
            var num = res.start + i + 1;
            var pin = r.is_pinned ? '<i class="bi bi-pin-angle-fill ann-pin me-1" title="Pinned"></i>' : '';
            var cat = annEsc(r.category || '-') + (r.subcategory ? '<br><small class="text-muted">' + annEsc(r.subcategory) + '</small>' : '');
            var actions = '<a href="' + ANN_BASE + 'announcement/detail/' + r.id + '" style="color:#1890ff;" title="Detail"><i class="bi bi-eye"></i></a>';
            if (res.can && res.can.edit) actions += '<a href="' + ANN_BASE + 'announcement/edit_page/' + r.id + '" style="color:#1890ff;" title="Edit"><i class="bi bi-pencil"></i></a>';
            if (res.can && res.can.delete) actions += '<a href="#!" onclick="annRemove(' + r.id + ');return false;" style="color:#ff4d4f;" title="Hapus"><i class="bi bi-trash"></i></a>';
            html += '<tr>'
                + '<td>' + num + '</td>'
                + '<td>' + pin + annEsc(r.title) + '</td>'
                + '<td>' + cat + '</td>'
                + '<td>' + annStatusBadge(r.status) + '</td>'
                + '<td>' + annPriorityBadge(r.priority) + '</td>'
                + '<td>' + annDate(r.publish_start_at) + ' &ndash; ' + annDate(r.publish_end_at) + '</td>'
                + '<td class="text-end ann-actions">' + actions + '</td>'
                + '</tr>';
        });
        tbody.html(html);
    }

    function annRenderPagination(res) {
        var total = res.totalPages || 0;
        var cur = res.page || 1;
        var box = $('#announcement-pagination');
        if (total <= 1) { box.empty(); return; }
        var h = '';
        h += '<button class="btn btn-outline-secondary btn-sm" ' + (cur <= 1 ? 'disabled' : '') + ' onclick="annGoto(' + (cur - 1) + ')"><i class="bi bi-chevron-left"></i></button>';
        var start = Math.max(1, cur - 2), end = Math.min(total, cur + 2);
        for (var i = start; i <= end; i++) {
            h += '<button class="btn btn-sm ' + (i === cur ? 'btn-primary' : 'btn-outline-secondary') + '" onclick="annGoto(' + i + ')">' + i + '</button>';
        }
        h += '<button class="btn btn-outline-secondary btn-sm" ' + (cur >= total ? 'disabled' : '') + ' onclick="annGoto(' + (cur + 1) + ')"><i class="bi bi-chevron-right"></i></button>';
        box.html(h);
    }

    function annGoto(p) { annState.page = p; annLoad(); }

    function annLoad() {
        $('#announcement-tbody').html('<tr><td colspan="7" class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>');
        $.ajax({
            type: 'GET',
            url: ANN_BASE + 'announcement/item',
            data: annFilters(),
            dataType: 'json',
            success: function (res) {
                if (!res || !res.success) {
                    $('#announcement-tbody').html('<tr><td colspan="7" class="text-center text-danger py-4">Gagal memuat data.</td></tr>');
                    return;
                }
                annRenderRows(res);
                annRenderPagination(res);
                $('#ann-count').show().text(res.total + ' data ditemukan');
            },
            error: function () {
                $('#announcement-tbody').html('<tr><td colspan="7" class="text-center text-danger py-4">Terjadi kesalahan saat memuat data. Coba lagi.</td></tr>');
            }
        });
    }

    function annRemove(id) {
        Swal.fire({
            title: 'Hapus Announcement',
            text: 'Yakin ingin menghapus announcement ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff4d4f',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                type: 'POST',
                url: ANN_BASE + 'announcement/delete',
                data: { id: id },
                success: function (response) {
                    if (response.indexOf('success') !== -1) {
                        Swal.fire('Terhapus!', 'Data berhasil dihapus.', 'success').then(annLoad);
                    } else {
                        Swal.fire('Error!', 'Gagal menghapus data.', 'error');
                    }
                },
                error: function () { Swal.fire('Error!', 'Terjadi kesalahan saat menghapus data.', 'error'); }
            });
        });
    }

    $(document).ready(function () {
        annSyncSubcategories();
        var reload = function () { annState.page = 1; annLoad(); };
        var t;
        $('#f-search').on('keyup', function () { clearTimeout(t); t = setTimeout(reload, 350); });
        $('#f-category').on('change', function () { annSyncSubcategories(); reload(); });
        $('#f-subcategory, #f-status, #f-priority, #f-pinned, #f-start, #f-end, #f-sort').on('change', reload);
        $('#f-reset').on('click', function () {
            $('#f-search,#f-start,#f-end').val('');
            $('#f-category,#f-status,#f-priority,#f-pinned').val('');
            $('#f-sort').val('newest');
            annSyncSubcategories();
            reload();
        });
        annLoad();
    });
</script>
