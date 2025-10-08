<div class="container-fluid py-3">
    <!-- User Basic Information Card -->
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">
                    <i class="bi bi-person me-2"></i>Informasi Dasar User
                </h5>
                <div>
                    <a href="<?= base_url() ?>/user/edit_page?id=<?= $data['id'] ?>" class="btn btn-primary me-2">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </a>
                    <a href="<?= base_url() ?>/user" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-4">
                    <?php if ($data['img']) { ?>
                        <div class="text-center">
                            <img src="<?= base_url() ?>/assets/img/user/<?= $data['img'] . '?token=' . DATE("Ymdhis", strtotime($data['updated_at'])) ?>"
                                class="img-fluid rounded" style="max-height: 200px; object-fit: cover;">
                        </div>
                    <?php } else { ?>
                        <div class="text-center">
                            <img src="<?= base_url() ?>/assets/img/user/default.png"
                                class="img-fluid rounded" style="max-height: 200px; object-fit: cover;">
                        </div>
                    <?php } ?>
                </div>
                <div class="col-md-9">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Nama Lengkap</h6>
                            <p class="mb-3"><?= $data['full_name'] ?></p>

                            <h6 class="text-muted mb-1">Username</h6>
                            <p class="mb-3"><?= $data['username'] ?></p>

                            <h6 class="text-muted mb-1">Email</h6>
                            <p class="mb-3"><?= $data['email'] ?></p>

                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Role</h6>
                            <p class="mb-3">
                                <span class="badge" style="background-color: #e6f7ff; color: #1890ff; border: 1px solid #91d5ff;">
                                    <?= $data['role_text'] ?>
                                </span>
                            </p>

                            <h6 class="text-muted mb-1">Status</h6>
                            <p class="mb-3">
                                <?php if ($data['status'] == 'Aktif'): ?>
                                    <span class="badge" style="background-color: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f;">
                                        <?= $data['status'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge" style="background-color: #fff2f0; color: #ff4d4f; border: 1px solid #ffccc7;">
                                        <?= $data['status'] ?>
                                    </span>
                                <?php endif; ?>
                            </p>

                            <h6 class="text-muted mb-1">Keterangan</h6>
                            <p class="mb-3"><?= $data['desc'] ? $data['desc'] : '-' ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Profile Information Card -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">
                <i class="bi bi-person-badge me-2"></i>Profil Lengkap Karyawan
                <?php if (empty($profile)): ?>
                    <span class="badge bg-warning ms-2">Profil Belum Lengkap</span>
                <?php else: ?>
                    <span class="badge bg-success ms-2">Profil Terisi</span>
                <?php endif; ?>
            </h5>
        </div>
        <div class="card-body">
            <?php if (!empty($profile)): ?>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-1">Posisi Jabatan</h6>
                        <p class="mb-3">
                            <?php if (!empty($profile['position_name'])): ?>
                                <span class="badge" style="background-color: #e6f7ff; color: #1890ff; border: 1px solid #91d5ff;">
                                    <?= $profile['position_name'] ?> (<?= $profile['level_name'] ?>)
                                </span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-1">Tanggal Bergabung</h6>
                        <p class="mb-3"><?= !empty($profile['join_date']) ? date('d M Y', strtotime($profile['join_date'])) : '-' ?></p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-1">Jenis Kontrak</h6>
                        <p class="mb-3">
                            <?php if (!empty($profile['jenis_kontrak'])): ?>
                                <?php if ($profile['jenis_kontrak'] == 'PKWT'): ?>
                                    <span class="badge" style="background-color: #fff7e6; color: #fa8c16; border: 1px solid #ffd591;">
                                        <i class="bi bi-calendar-check me-1"></i>PKWT (Kontrak)
                                    </span>
                                <?php else: ?>
                                    <span class="badge" style="background-color: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f;">
                                        <i class="bi bi-shield-check me-1"></i>PKWTT (Tetap)
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-1">Lama Kontrak</h6>
                        <p class="mb-3">
                            <?php if (!empty($profile['jenis_kontrak']) && $profile['jenis_kontrak'] == 'PKWT' && !empty($profile['lama_kontrak'])): ?>
                                <span class="badge" style="background-color: #fff7e6; color: #fa8c16; border: 1px solid #ffd591;">
                                    <i class="bi bi-clock me-1"></i><?= $profile['lama_kontrak'] ?>
                                </span>
                            <?php elseif (!empty($profile['jenis_kontrak']) && $profile['jenis_kontrak'] == 'PKWTT'): ?>
                                <span class="text-muted">
                                    <i class="bi bi-infinity me-1"></i>Tidak Terbatas
                                </span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-1">Tempat, Tanggal Lahir</h6>
                        <p class="mb-3"><?= !empty($profile['birth_place_date']) ? $profile['birth_place_date'] : '-' ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-1">Jenis Kelamin</h6>
                        <p class="mb-3"><?= !empty($profile['gender']) ? $profile['gender'] : '-' ?></p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-1">Agama</h6>
                        <p class="mb-3"><?= !empty($profile['religion']) ? $profile['religion'] : '-' ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-1">Status Pernikahan</h6>
                        <p class="mb-3"><?= !empty($profile['marital_status']) ? $profile['marital_status'] : '-' ?></p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-1">Nomor Telepon</h6>
                        <p class="mb-3"><?= !empty($profile['phone_number']) ? $profile['phone_number'] : '-' ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-1">NIK</h6>
                        <p class="mb-3"><?= !empty($profile['nik']) ? $profile['nik'] : '-' ?></p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <h6 class="text-muted mb-1">Alamat Lengkap</h6>
                        <p class="mb-3"><?= !empty($profile['full_address']) ? $profile['full_address'] : '-' ?></p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <h6 class="text-muted mb-1">Nama Bank</h6>
                        <p class="mb-3"><?= !empty($profile['bank_name']) ? $profile['bank_name'] : '-' ?></p>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-muted mb-1">Nomor Rekening</h6>
                        <p class="mb-3"><?= !empty($profile['bank_account_number']) ? $profile['bank_account_number'] : '-' ?></p>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-muted mb-1">Nama Pemegang Rekening</h6>
                        <p class="mb-3"><?= !empty($profile['account_holder_name']) ? $profile['account_holder_name'] : '-' ?></p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-1">Hobi</h6>
                        <p class="mb-3"><?= !empty($profile['hobby']) ? $profile['hobby'] : '-' ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-1">Alasan Hobi</h6>
                        <p class="mb-3"><?= !empty($profile['hobby_reason']) ? $profile['hobby_reason'] : '-' ?></p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-1">Makanan/Minuman Favorit</h6>
                        <p class="mb-3"><?= !empty($profile['favorite_food_drink']) ? $profile['favorite_food_drink'] : '-' ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-1">Alasan Favorit</h6>
                        <p class="mb-3"><?= !empty($profile['food_drink_reason']) ? $profile['food_drink_reason'] : '-' ?></p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-1">Warna Favorit</h6>
                        <p class="mb-3"><?= !empty($profile['favorite_color']) ? $profile['favorite_color'] : '-' ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-1">Foto KTP</h6>
                        <p class="mb-3">
                            <?php if (!empty($profile['ktp_photo'])): ?>
                                <a href="<?= base_url() ?>/assets/img/ktp/<?= $profile['ktp_photo'] ?>" target="_blank" class="text-primary">
                                    <i class="bi bi-image me-1"></i> Lihat Foto KTP
                                </a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <?php if (!empty($profile['score'])): ?>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <h6 class="text-muted mb-1">Skor Quest</h6>
                            <p class="mb-3">
                                <span class="badge bg-info"><?= $profile['score'] ?> Poin</span>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="text-center py-4">
                    <i class="bi bi-person-x" style="font-size: 3rem; color: #ccc;"></i>
                    <h6 class="text-muted mt-3">Profil karyawan belum diisi</h6>
                    <p class="text-muted">Klik tombol Edit untuk melengkapi profil karyawan</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- System Information Card -->
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">
                <i class="bi bi-info-circle me-2"></i>Informasi Sistem
            </h6>
        </div>
        <div class="card-body" style="background-color: #fafafa;">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-muted mb-1">Dibuat Oleh</h6>
                    <p class="mb-3"><?= $created_by ? $created_by['full_name'] : '-' ?></p>

                    <h6 class="text-muted mb-1">Tanggal Dibuat</h6>
                    <p class="mb-3"><?= date('d M Y H:i', strtotime($data['created_at'])) ?></p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-1">Diperbarui Oleh</h6>
                    <p class="mb-3"><?= $updated_by ? $updated_by['full_name'] : '-' ?></p>

                    <h6 class="text-muted mb-1">Tanggal Diperbarui</h6>
                    <p class="mb-3"><?= $data['updated_at'] ? date('d M Y H:i', strtotime($data['updated_at'])) : '-' ?></p>
                </div>
            </div>
        </div>
    </div>
</div>