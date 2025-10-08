<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Detail Quest Level</h5>
                <div>
                    <a href="<?= base_url() ?>/quest_level/edit_page?id=<?= $data['id'] ?>" class="btn btn-primary me-2">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </a>
                    <a href="<?= base_url() ?>/quest_level" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-muted mb-1">ID Level</h6>
                    <p class="mb-3"><?= $data['id'] ?></p>

                    <h6 class="text-muted mb-1">Nama Level</h6>
                    <p class="mb-3">
                        <span class="badge" style="background-color: #e6f7ff; color: #1890ff; border: 1px solid #91d5ff; font-size: 14px;">
                            <?= $data['name'] ?>
                        </span>
                    </p>
                </div>
            </div>

            <?php if (!empty($positions)): ?>
            <div class="row mt-4">
                <div class="col-md-12">
                    <h6 class="text-muted mb-3">Posisi yang Menggunakan Level Ini</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Nama Posisi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($positions as $key => $position): ?>
                                <tr>
                                    <td><?= $key + 1 ?></td>
                                    <td><?= $position['name'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Belum ada posisi yang menggunakan level ini.
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>