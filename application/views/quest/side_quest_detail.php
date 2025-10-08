<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Detail Side Quest</h5>
                <div>
                    <a href="<?= base_url() ?>quest/side_quest_edit_page?id=<?= $data['id'] ?>" class="btn btn-primary me-2">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </a>
                    <a href="<?= base_url() ?>quest" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h6 class="text-muted mb-1">Judul Quest</h6>
                    <h4 class="mb-3"><?= $data['title'] ?></h4>

                    <h6 class="text-muted mb-1">Deskripsi</h6>
                    <p class="mb-3" style="white-space: pre-wrap;"><?= $data['description'] ?></p>

                    <h6 class="text-muted mb-1">Reward</h6>
                    <div class="alert alert-success mb-3" style="background-color: #f6ffed; border: 1px solid #b7eb8f;">
                        <p class="mb-0" style="white-space: pre-wrap;"><?= $data['reward'] ?? 'No reward description provided' ?></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted mb-1">Quest Type</h6>
                    <p class="mb-3">
                        <span class="badge" style="background-color: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f; font-size: 16px;">
                            Side Quest
                        </span>
                    </p>

                    <h6 class="text-muted mb-1">Quest Points</h6>
                    <p class="mb-3">
                        <span class="badge" style="background-color: #fffbe6; color: #faad14; border: 1px solid #ffe58f; font-size: 18px;">
                            <i class="bi bi-star-fill me-1"></i><?= $data['points'] ?? 0 ?> Points
                        </span>
                    </p>

                    <?php if (!empty($data['gambar_animasi'])): ?>
                    <h6 class="text-muted mb-1">Animation Image</h6>
                    <div class="mb-3">
                        <div class="border rounded p-2 text-center" style="background-color: #fafafa;">
                            <img src="<?= base_url() ?>assets/uploads/side_quest_animations/<?= $data['gambar_animasi'] ?>" 
                                 alt="Quest Animation" 
                                 class="img-fluid rounded" 
                                 style="max-height: 120px; cursor: pointer;"
                                 onclick="window.open(this.src, '_blank')">
                        </div>
                    </div>
                    <?php endif; ?>

                    <h6 class="text-muted mb-1">Created By</h6>
                    <p class="mb-3"><?= $data['creator_name'] ?></p>

                    <h6 class="text-muted mb-1">Created At</h6>
                    <p class="mb-3"><?= date('d M Y H:i', strtotime($data['created_at'])) ?></p>

                    <h6 class="text-muted mb-1">Accessibility</h6>
                    <p class="mb-3">
                        <span class="badge" style="background-color: #e6f7ff; color: #1890ff; border: 1px solid #91d5ff;">
                            All Levels
                        </span>
                    </p>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card" style="background-color: #fafafa; border: 1px dashed #d9d9d9;">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Quest Information</h6>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h4 class="text-success">Side Quest</h4>
                                        <small>Quest Type</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h4 class="text-primary">All Levels</h4>
                                        <small>Accessibility</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h4 class="text-warning"><?= $data['points'] ?? 0 ?> Points</h4>
                                        <small>Quest Reward</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h4 class="text-info">HR Review</h4>
                                        <small>Approval Process</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="alert alert-info">
                        <h6><i class="bi bi-info-circle me-2"></i>Informasi Side Quest</h6>
                        <ul class="mb-0">
                            <li>Side quest terbuka untuk semua karyawan tanpa pembatasan level</li>
                            <li>Penilaian berdasarkan point system dengan 2 komponen:</li>
                            <ul>
                                <li><strong>Notes Point:</strong> Kualitas catatan/dokumentasi</li>
                                <li><strong>Presentation Point:</strong> Kualitas presentasi hasil</li>
                            </ul>
                            <li>Total point akan dihitung dan ditambahkan ke score karyawan setelah approval</li>
                            <li><strong>Quest Points:</strong> <?= $data['points'] ?? 0 ?> poin otomatis diberikan saat quest diselesaikan</li>
                            <li><strong>Milestone:</strong> Quest dapat berkontribusi pada pencapaian milestone</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>