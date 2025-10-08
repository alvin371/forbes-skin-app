<div class="container-fluid py-3">
    <!-- Header -->
    <div class="row align-items-center mb-4">
        <div class="col-lg-8">
            <h3 class="text-primary fw-500">RIWAYAT QUEST</h3>
            <p class="text-muted mb-0">Lihat semua aktivitas quest yang pernah Anda ajukan</p>
        </div>
        <div class="col-lg-4 text-end">
            <a href="<?= base_url() ?>profile" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center border-success">
                <div class="card-body">
                    <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                    <h4 class="text-success mt-2">
                        <?php 
                        $approved = array_filter($submissions, function($s) { return $s['status'] == 'approved'; });
                        echo count($approved);
                        ?>
                    </h4>
                    <small class="text-muted">Quest Disetujui</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-warning">
                <div class="card-body">
                    <i class="bi bi-clock text-warning" style="font-size: 2rem;"></i>
                    <h4 class="text-warning mt-2">
                        <?php 
                        $pending = array_filter($submissions, function($s) { return $s['status'] == 'pending'; });
                        echo count($pending);
                        ?>
                    </h4>
                    <small class="text-muted">Quest Pending</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-danger">
                <div class="card-body">
                    <i class="bi bi-x-circle text-danger" style="font-size: 2rem;"></i>
                    <h4 class="text-danger mt-2">
                        <?php 
                        $denied = array_filter($submissions, function($s) { return $s['status'] == 'denied'; });
                        echo count($denied);
                        ?>
                    </h4>
                    <small class="text-muted">Quest Ditolak</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-info">
                <div class="card-body">
                    <i class="bi bi-star text-info" style="font-size: 2rem;"></i>
                    <h4 class="text-info mt-2"><?= !empty($profile['score']) ? $profile['score'] : 0 ?></h4>
                    <small class="text-muted">Total Poin</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Quest History Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">
                <i class="bi bi-list-ul me-2"></i>Semua Riwayat Quest
            </h5>
        </div>
        <div class="card-body">
            <?php if (!empty($submissions)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="10%">Tipe Quest</th>
                                <th width="25%">Nama Quest</th>
                                <th width="12%">Status</th>
                                <th width="15%">Tanggal Submit</th>
                                <th width="15%">Tanggal Review</th>
                                <th width="12%">Benefit</th>
                                <th width="11%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $index => $submission): ?>
                                <tr>
                                    <td>
                                        <span class="badge <?= $submission['quest_type'] == 'main' ? 'bg-primary' : 'bg-warning' ?>">
                                            <?= $submission['quest_type'] == 'main' ? 'Main Quest' : 'Side Quest' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?= $submission['quest_title'] ?></strong>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        $status_text = '';
                                        $status_icon = '';
                                        switch ($submission['status']) {
                                            case 'pending':
                                                $status_class = 'bg-warning';
                                                $status_text = 'Pending';
                                                $status_icon = 'bi-clock';
                                                break;
                                            case 'approved':
                                                $status_class = 'bg-success';
                                                $status_text = 'Disetujui';
                                                $status_icon = 'bi-check-circle';
                                                break;
                                            case 'denied':
                                                $status_class = 'bg-danger';
                                                $status_text = 'Ditolak';
                                                $status_icon = 'bi-x-circle';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?= $status_class ?>">
                                            <i class="bi <?= $status_icon ?> me-1"></i><?= $status_text ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= date('d M Y', strtotime($submission['submitted_at'])) ?><br>
                                            <?= date('H:i', strtotime($submission['submitted_at'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($submission['approved_at']): ?>
                                            <small class="text-muted">
                                                <?= date('d M Y', strtotime($submission['approved_at'])) ?><br>
                                                <?= date('H:i', strtotime($submission['approved_at'])) ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($submission['benefit_type']): ?>
                                            <span class="badge bg-info">
                                                <i class="bi bi-gift me-1"></i><?= ucfirst($submission['benefit_type']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-info view-details" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#detailModal<?= $index ?>"
                                                title="Lihat Detail">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </td>
                                </tr>

                                <!-- Detail Modal for each submission -->
                                <div class="modal fade" id="detailModal<?= $index ?>" tabindex="-1" aria-labelledby="detailModalLabel<?= $index ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="detailModalLabel<?= $index ?>">
                                                    Detail Quest: <?= $submission['quest_title'] ?>
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <h6 class="text-muted mb-2">Informasi Quest</h6>
                                                        <table class="table table-borderless table-sm">
                                                            <tr>
                                                                <td width="40%"><strong>Tipe:</strong></td>
                                                                <td>
                                                                    <span class="badge <?= $submission['quest_type'] == 'main' ? 'bg-primary' : 'bg-warning' ?>">
                                                                        <?= $submission['quest_type'] == 'main' ? 'Main Quest' : 'Side Quest' ?>
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td><strong>Nama Quest:</strong></td>
                                                                <td><?= $submission['quest_title'] ?></td>
                                                            </tr>
                                                            <tr>
                                                                <td><strong>Status:</strong></td>
                                                                <td>
                                                                    <span class="badge <?= $status_class ?>">
                                                                        <i class="bi <?= $status_icon ?> me-1"></i><?= $status_text ?>
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h6 class="text-muted mb-2">Timeline</h6>
                                                        <table class="table table-borderless table-sm">
                                                            <tr>
                                                                <td width="40%"><strong>Tanggal Submit:</strong></td>
                                                                <td><?= date('d M Y H:i', strtotime($submission['submitted_at'])) ?></td>
                                                            </tr>
                                                            <?php if ($submission['approved_at']): ?>
                                                                <tr>
                                                                    <td><strong>Tanggal Review:</strong></td>
                                                                    <td><?= date('d M Y H:i', strtotime($submission['approved_at'])) ?></td>
                                                                </tr>
                                                            <?php endif; ?>
                                                            <?php if ($submission['approver_name']): ?>
                                                                <tr>
                                                                    <td><strong>Di-review oleh:</strong></td>
                                                                    <td><?= $submission['approver_name'] ?></td>
                                                                </tr>
                                                            <?php endif; ?>
                                                        </table>
                                                    </div>
                                                </div>

                                                <?php if ($submission['benefit_type']): ?>
                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <h6 class="text-muted mb-2">Benefit yang Diterima</h6>
                                                            <div class="alert alert-success">
                                                                <i class="bi bi-gift me-2"></i>
                                                                <strong><?= ucfirst($submission['benefit_type']) ?></strong>
                                                                <?php
                                                                $benefit_desc = '';
                                                                switch ($submission['benefit_type']) {
                                                                    case 'promotion':
                                                                        $benefit_desc = 'Promosi jabatan ke level yang lebih tinggi';
                                                                        break;
                                                                    case 'bonus':
                                                                        $benefit_desc = 'Bonus moneter sebagai reward';
                                                                        break;
                                                                    case 'salary':
                                                                        $benefit_desc = 'Kenaikan gaji';
                                                                        break;
                                                                    case 'leave':
                                                                        $benefit_desc = 'Cuti tambahan berbayar';
                                                                        break;
                                                                    case 'wfa':
                                                                        $benefit_desc = 'Hak kerja dari mana saja (Work From Anywhere)';
                                                                        break;
                                                                }
                                                                ?>
                                                                <p class="mb-0 mt-2"><?= $benefit_desc ?></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($submission['hr_notes']): ?>
                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <h6 class="text-muted mb-2">Catatan HR</h6>
                                                            <div class="alert alert-info">
                                                                <i class="bi bi-info-circle me-2"></i>
                                                                <?= nl2br(htmlspecialchars($submission['hr_notes'])) ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-list-ul" style="font-size: 4rem; color: #ccc;"></i>
                    <h4 class="text-muted mt-3">Belum Ada Riwayat Quest</h4>
                    <p class="text-muted">Anda belum pernah mengajukan quest apapun.</p>
                    <a href="<?= base_url() ?>profile" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> Mulai Apply Quest
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.table th {
    border-top: none;
    font-weight: 600;
    background-color: #f8f9fa;
}

.table td {
    vertical-align: middle;
}

.card {
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.badge {
    font-size: 0.75rem;
}

.modal-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.alert {
    border-radius: 8px;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
}
</style>