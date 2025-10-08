<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Detail Main Quest</h5>
                <div>
                    <a href="<?= base_url() ?>quest/main_quest_edit_page?id=<?= $data['id'] ?>" class="btn btn-primary me-2">
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
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted mb-1">Required Position</h6>
                    <p class="mb-3">
                        <strong><?= $data['position_name'] ?></strong>
                        <br>
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


                    <h6 class="text-muted mb-1">Created By</h6>
                    <p class="mb-3"><?= $data['creator_name'] ?></p>

                    <h6 class="text-muted mb-1">Created At</h6>
                    <p class="mb-3"><?= date('d M Y H:i', strtotime($data['created_at'])) ?></p>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card" style="background-color: #fafafa; border: 1px dashed #d9d9d9;">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Quest Information</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="text-center">
                                        <h4 class="text-primary">Main Quest</h4>
                                        <small>Quest Type</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-center">
                                        <h4 class="text-success"><?= $data['position_name'] ?></h4>
                                        <small>Required Position (<?= $data['level_name'] ?>)</small>
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
                        <h6><i class="bi bi-info-circle me-2"></i>Informasi Main Quest</h6>
                        <p class="mb-0">
                            Main Quest adalah quest yang memiliki level requirement. Quest ini memerlukan approval dari HR
                            dan hanya dapat dikerjakan oleh karyawan dengan position yang sesuai.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>