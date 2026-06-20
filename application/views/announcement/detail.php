<?php
$statusStyle = [
    'DRAFT'     => 'background:#f0f0f0;color:#595959;',
    'PUBLISHED' => 'background:#e6f7ff;color:#1890ff;',
    'ARCHIVED'  => 'background:#fff1f0;color:#cf1322;',
];
$priorityStyle = [
    'LOW'    => 'background:#f6ffed;color:#52c41a;',
    'MEDIUM' => 'background:#e6f7ff;color:#1890ff;',
    'HIGH'   => 'background:#fff7e6;color:#fa8c16;',
    'URGENT' => 'background:#fff1f0;color:#cf1322;',
];
$fmt = function ($v) {
    if (empty($v)) return '-';
    $ts = strtotime($v);
    return $ts ? date('d M Y H:i', $ts) : $v;
};
?>
<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0,0,0,0.85);">Detail Announcement</h5>
                <div>
                    <?php if (!empty($permissions['can_edit'])): ?>
                        <a href="<?= base_url() ?>announcement/edit_page/<?= (int) $data['id'] ?>" class="btn btn-primary">
                            <i class="bi bi-pencil me-1"></i> Edit
                        </a>
                    <?php endif; ?>
                    <a href="<?= base_url() ?>announcement" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="d-flex align-items-center gap-2 mb-2">
                <?php if ((int) $data['is_pinned'] === 1): ?>
                    <i class="bi bi-pin-angle-fill" style="color:#faad14;" title="Pinned"></i>
                <?php endif; ?>
                <h4 class="mb-0"><?= htmlspecialchars($data['title']) ?></h4>
            </div>
            <div class="mb-3">
                <span class="badge" style="<?= $statusStyle[$data['status']] ?? '' ?>"><?= ucfirst(strtolower($data['status'])) ?></span>
                <span class="badge" style="<?= $priorityStyle[$data['priority']] ?? '' ?>"><?= ucfirst(strtolower($data['priority'])) ?></span>
            </div>

            <div class="row mb-3">
                <div class="col-md-3"><small class="text-muted d-block">Kategori</small><?= htmlspecialchars($data['category'] ?: '-') ?></div>
                <div class="col-md-3"><small class="text-muted d-block">Subkategori</small><?= htmlspecialchars($data['subcategory'] ?: '-') ?></div>
                <div class="col-md-3"><small class="text-muted d-block">Publikasi Mulai</small><?= $fmt($data['publish_start_at']) ?></div>
                <div class="col-md-3"><small class="text-muted d-block">Publikasi Selesai</small><?= $fmt($data['publish_end_at']) ?></div>
            </div>

            <hr>
            <div class="announcement-content">
                <?= $data['content'] /* stored HTML is sanitized on save (strip script/iframe/style + on* + javascript:) */ ?>
            </div>
        </div>
    </div>
</div>
