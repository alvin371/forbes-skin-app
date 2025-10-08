<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Detail Position</h5>
                <div>
                    <a href="<?= base_url() ?>/position/edit_page?id=<?= $data['id'] ?>" class="btn btn-primary me-2">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </a>
                    <a href="<?= base_url() ?>/position" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-muted mb-1">ID Position</h6>
                    <p class="mb-3"><?= $data['id'] ?></p>

                    <h6 class="text-muted mb-1">Nama Position</h6>
                    <p class="mb-3">
                        <strong><?= $data['name'] ?></strong>
                    </p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-1">Quest Level</h6>
                    <p class="mb-3">
                        <?php
                        $level_color = '';
                        switch($data['level_name']) {
                            case 'Junior':
                                $level_color = 'background-color: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f;';
                                break;
                            case 'Intermediate':
                                $level_color = 'background-color: #fffbe6; color: #faad14; border: 1px solid #ffe58f;';
                                break;
                            case 'Senior':
                                $level_color = 'background-color: #fff2f0; color: #ff4d4f; border: 1px solid #ffccc7;';
                                break;
                            default:
                                $level_color = 'background-color: #f5f5f5; color: #595959; border: 1px solid #d9d9d9;';
                        }
                        ?>
                        <span class="badge" style="<?= $level_color ?> font-size: 14px;">
                            <?= $data['level_name'] ?>
                        </span>
                    </p>
                </div>
            </div>

            <?php if (!empty($employees)): ?>
            <div class="row mt-4">
                <div class="col-md-12">
                    <h6 class="text-muted mb-3">Karyawan dengan Posisi Ini (<?= count($employees) ?> orang)</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Nama Lengkap</th>
                                    <th>Email</th>
                                    <th>Tanggal Bergabung</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employees as $key => $employee): ?>
                                <tr>
                                    <td><?= $key + 1 ?></td>
                                    <td><?= $employee['full_name'] ?? '-' ?></td>
                                    <td><?= $employee['email'] ?? '-' ?></td>
                                    <td><?= $employee['join_date'] ? date('d M Y', strtotime($employee['join_date'])) : '-' ?></td>
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
                        Belum ada karyawan yang memiliki posisi ini.
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>