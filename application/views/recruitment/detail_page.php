<style>
    :root {
        --primary-color: #3b82f6;
        --secondary-color: #6b7280;
        --success-color: #10b981;
        --warning-color: #f59e0b;
        --danger-color: #ef4444;
        --light-bg: #f8fafc;
        --border-color: #e5e7eb;
        --card-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    }

    body {
        background-color: var(--light-bg);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        line-height: 1.6;
    }

    .container-fluid {
        max-width: 1300px;
        margin: 0 auto;
    }

    .card {
        border: none;
        border-radius: 12px;
        box-shadow: var(--card-shadow);
        margin-bottom: 2rem;
        overflow: hidden;
        background: white;
    }

    .card-header {
        background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
        border-bottom: 1px solid var(--border-color);
        padding: 1.5rem 2rem;
    }

    .card-header h5 {
        font-size: 1.125rem;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
        display: flex;
        align-items: center;
    }

    .card-header h5 i {
        color: var(--primary-color);
        margin-right: 0.75rem;
    }

    .card-body {
        padding: 2rem;
    }

    .info-group {
        margin-bottom: 2rem;
    }

    .info-group:last-child {
        margin-bottom: 0;
    }

    .info-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--secondary-color);
        margin-bottom: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .info-value {
        font-size: 1rem;
        color: #1f2937;
        margin: 0 0 1rem 0;
        line-height: 1.5;
    }

    .contact-info {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .contact-item {
        display: flex;
        align-items: center;
        font-size: 0.95rem;
        color: #374151;
    }

    .contact-item i {
        color: var(--primary-color);
        margin-right: 0.75rem;
        width: 16px;
    }

    .status-badges {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
        align-items: center;
    }

    .badge {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        font-weight: 500;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .bg-info {
        background-color: #dbeafe;
        color: #1e40af;
        border: 1px solid #93c5fd;
    }

    .bg-warning {
        background-color: #fef3c7;
        color: #92400e;
        border: 1px solid #fcd34d;
    }

    .bg-danger {
        background-color: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fca5a5;
    }

    .bg-light {
        background-color: #f3f4f6;
        color: #4b5563;
        border: 1px solid var(--border-color);
    }

    .btn {
        border-radius: 8px;
        font-weight: 500;
        padding: 0.625rem 1.25rem;
        transition: all 0.2s ease;
        border: none;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--primary-color), #2563eb);
        color: white;
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        transform: translateY(-1px);
    }

    .btn-outline-secondary {
        background: white;
        color: #6b7280;
        border: 1px solid var(--border-color);
    }

    .btn-outline-secondary:hover {
        background: #f9fafb;
        color: #374151;
        border-color: #d1d5db;
    }

    .btn-sm {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        border-radius: 6px;
    }

    .btn-outline-primary {
        background: white;
        color: var(--primary-color);
        border: 1px solid var(--primary-color);
    }

    .btn-outline-primary:hover {
        background: var(--primary-color);
        color: white;
        transform: translateY(-1px);
    }

    .demographics {
        color: #4b5563;
        line-height: 1.6;
    }

    .work-preference {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        color: #4b5563;
    }

    .salary {
        font-weight: 600;
        color: var(--success-color);
        font-size: 1.125rem;
    }

    .keyword-tags {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .keyword-tag {
        background: #f0f9ff;
        color: #0369a1;
        padding: 0.375rem 0.75rem;
        border-radius: 16px;
        font-size: 0.875rem;
        font-weight: 500;
        border: 1px solid #bae6fd;
    }

    .hr-notes-section {
        background: linear-gradient(135deg, #fafbfc 0%, #f3f4f6 100%);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 1.5rem;
    }

    .timestamp {
        font-size: 0.875rem;
        color: var(--secondary-color);
        font-weight: 500;
    }

    .section-divider {
        height: 1px;
        background: linear-gradient(90deg, transparent, var(--border-color), transparent);
        margin: 1.5rem 0;
    }

    .empty-state {
        color: #9ca3af;
        font-style: italic;
    }

    @media (max-width: 768px) {
        .card-header {
            padding: 1rem 1.5rem;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .d-flex.justify-content-between {
            flex-direction: column;
            gap: 1rem;
        }
        
        .status-badges {
            justify-content: flex-start;
        }
    }

    .badge-testcase_1 {
        background-color: #d7bdf2;
        color: #4b0082;
    }
    .badge-interview_hr {
        background-color: #ffe7a0;
        color: #7a5900;
    }
    .badge-interview_user {
        background-color: #cde8ff;
        color: #0d6efd;
    }
    .badge-selected {
        background-color: #2e7d32;
        color: white;
    }
    .badge-rejected {
        background-color: #b71c1c;
        color: white;
    }
    .badge-pertimbangan {
        background-color: #f9c998;
        color: #8b4513;
    }
    .badge-default {
        background-color: #e0e0e0;
        color: #666;
    }

</style>

<div class="container-fluid py-4">
    <!-- Application Basic Information Card -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-file-earmark-person"></i>Informasi Dasar Lamaran
                </h5>
                <a href="<?= base_url() ?>/recruitment" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="info-group">
                        <div class="info-label">Posisi Dilamar</div>
                        <div class="info-value fw-semibold"><?= $data['posisi_dilamar'] ?></div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">Nama Lengkap</div>
                        <div class="info-value"><?= $data['nama_lengkap'] ?></div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">Kontak</div>
                        <div class="contact-info">
                            <div class="contact-item">
                                <i class="bi bi-telephone-fill"></i>
                                <span><?= $data['no_handphone'] ?></span>
                            </div>
                            <div class="contact-item">
                                <i class="bi bi-envelope-fill"></i>
                                <span><?= $data['email'] ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">Demografi</div>
                        <div class="demographics">
                            <div>Usia: <?= $data['usia'] ?> tahun</div>
                            <div>Domisili: <?= $data['domisili'] ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="info-group">
                        <div class="info-label">Status Lamaran</div>
                        <div class="status-badges">
                            <?php if ($data['status_recruitment'] == 'interview_hr'): ?>
                                <span class="badge bg-info">
                                    <i class="bi bi-people"></i> Interview HR
                                </span>
                            <?php elseif ($data['status_recruitment'] == 'pending'): ?>
                                <span class="badge bg-warning">
                                    <i class="bi bi-hourglass"></i> Pending
                                </span>
                            <?php elseif ($data['status_recruitment'] == 'testcase_1'): ?>
                                <span class="badge badge-testcase_1">
                                    <i class="bi bi-file-earmark-text"></i> Testcase 1
                                </span>
                                <a href="<?= $data['link_testcase']; ?>" class="badge badge-testcase_1 text-decoration-none" target="_blank">
                                    <i class="bi bi-eye"></i> Lihat Testcase
                                </a>
                            <?php elseif ($data['status_recruitment'] == 'interview_user'): ?>
                                <span class="badge badge-interview_user">
                                    <i class="bi bi-person-check"></i> Interview User
                                </span>
                            <?php elseif ($data['status_recruitment'] == 'selected'): ?>
                                <span class="badge badge-selected">
                                    <i class="bi bi-check-circle"></i> Selected
                                </span>
                            <?php elseif ($data['status_recruitment'] == 'rejected'): ?>
                                <span class="badge badge-rejected">
                                    <i class="bi bi-x-circle"></i> Rejected
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($data['status_approval'] == 'rejected'): ?>
                                <span class="badge bg-danger">
                                    <i class="bi bi-x-circle"></i> Rejected
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">Preferensi Kerja</div>
                        <div class="work-preference">
                            <span><?= $data['is_wfo'] ? '🏢 Work From Office' : '🏠 Work From Home' ?></span>
                            <span>📅 Tanggal Bergabung: <?= date('d M Y', strtotime($data['tanggal_bisa_join'])) ?></span>
                        </div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">Ekspektasi Gaji</div>
                        <div class="salary">Rp <?= $data['ekspektasi_sallary'] ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Professional Background Card -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-briefcase"></i>Pengalaman Profesional
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="info-group">
                        <div class="info-label">Pengalaman Terakhir</div>
                        <div class="info-value"><?= $data['pengalaman_terakhir'] ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-group">
                        <div class="info-label">Perusahaan Terakhir</div>
                        <div class="info-value"><?= $data['perusahaan_terakhir'] ?></div>
                    </div>
                </div>
            </div>
            
            <div class="section-divider"></div>
            
            <div class="info-group">
                <div class="info-label">Gaji & Benefit Terakhir</div>
                <div class="info-value"><?= $data['gaji_benefit_terakhir'] ?></div>
            </div>
        </div>
    </div>

    <!-- Documents & Additional Info Card -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-file-earmark-text"></i>Dokumen & Informasi Tambahan
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="info-group">
                        <div class="info-label">CV/Portofolio</div>
                        <div class="info-value">
                            <?php if ($data['cv_portofolio']): ?>
                                <a href="<?= $data['cv_portofolio'] ?>" target="_blank" class="btn-outline-primary btn-sm">
                                    <i class="bi bi-google me-1"></i> Lihat CV/Portofolio
                                </a>

                            <?php else: ?>
                                <span class="empty-state">Tidak tersedia</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="info-group">
                        <div class="info-label">Dokumen Pendukung</div>
                        <div class="info-value">
                            <?php if ($data['link_drive_dokumen']): ?>
                                <a href="<?= $data['link_drive_dokumen'] ?>" target="_blank" class="btn-outline-primary btn-sm">
                                    <i class="bi bi-google me-1"></i> Lihat Dokumen Pendukung
                                </a>
                            <?php else: ?>
                                <span class="empty-state">Tidak tersedia</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="info-group">
                        <div class="info-label">Sosial Media</div>
                            <div class="d-flex flex-wrap gap-2">

                                <?php
                                function render_social_icon($platform, $icon_class, $input, $expected_domain, $url_prefix) {
                                    if (!$input) return;

                                    $input = trim($input);
                                    $input = ltrim($input, '@/');

                                    if (!preg_match('#^(https?:\/\/)?(www\.)?' . preg_quote($expected_domain, '#') . '#i', $input)) {
                                        $input = $url_prefix . $input;
                                    } elseif (!preg_match('#^https?://#i', $input)) {
                                        $input = 'https://' . $input;
                                    }

                                    echo '<a href="' . htmlspecialchars($input) . '" target="_blank" class="btn btn-outline-primary rounded-circle d-flex align-items-center justify-content-center" style="width:40px; height:40px;" title="' . ucfirst($platform) . '">';
                                    echo '<i class="' . $icon_class . '"></i>';
                                    echo '</a>';
                                }

                                render_social_icon('instagram', 'fab fa-instagram', $data['instagram'] ?? '', 'instagram.com', 'https://instagram.com/');
                                render_social_icon('facebook', 'fab fa-facebook-f', $data['facebook'] ?? '', 'facebook.com', 'https://facebook.com/');
                                render_social_icon('linkedin', 'fab fa-linkedin-in', $data['linkedin'] ?? '', 'linkedin.com', 'https://linkedin.com/in/');
                                render_social_icon('tiktok', 'fab fa-tiktok', $data['tiktok'] ?? '', 'tiktok.com', 'https://tiktok.com/@');
                                ?>
                                
                            </div>

                    </div>

                    
                    <div class="info-group">
                        <div class="info-label">Sumber Info Lowongan</div>
                        <div class="info-value"><?= $data['sumber_info_loker'] ?: '<span class="empty-state">Tidak tersedia</span>' ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Personal Insights Card -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-lightbulb"></i>Pandangan Pribadi
            </h5>
        </div>
        <div class="card-body">
            <div class="info-group">
                <div class="info-label">Alasan Bergabung</div>
                <div class="info-value"><?= $data['alasan_join'] ?></div>
            </div>
            
            <div class="info-group">
                <div class="info-label">Visi Misi</div>
                <div class="info-value"><?= $data['visi_misi'] ?></div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="info-group">
                        <div class="info-label">3 Kata Deskripsi Diri</div>
                        <div class="keyword-tags">
                            <?php $kata = explode(', ', $data['tiga_kata_diri']); ?>
                            <?php foreach ($kata as $k): ?>
                                <span class="keyword-tag"><?= trim($k) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-group">
                        <div class="info-label">Pantun</div>
                        <div class="info-value" style="font-style: italic; line-height: 1.8;"><?= $data['pantun'] ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- HR Notes & System Info Card -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-clipboard-data"></i>Catatan HR & Informasi Sistem
            </h5>
        </div>
        <div class="card-body">
            <div class="hr-notes-section">
                <div class="info-group">
                    <div class="info-label">Catatan HR</div>
                    <div class="info-value"><?= $data['notes_hr'] ?: '<span class="empty-state">Tidak ada catatan</span>' ?></div>
                </div>
                
                <div class="section-divider"></div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-group">
                            <div class="info-label">Dibuat Pada</div>
                            <div class="timestamp"><?= date('d M Y, H:i', strtotime($data['created_at'])) ?> WIB</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-group">
                            <div class="info-label">Diperbarui Pada</div>
                            <div class="timestamp"><?= $data['updated_at'] ? date('d M Y, H:i', strtotime($data['updated_at'])) . ' WIB' : '<span class="empty-state">Belum pernah diperbarui</span>' ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- History Section - Only show if tag is "Already Apply" -->
    <?php if (isset($data['tag']) && $data['tag'] == 'Already Apply'): ?>
        
        <!-- Application History Card -->
        <?php if (!empty($history)): ?>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-clock-history"></i>Riwayat Aplikasi Sebelumnya
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info d-flex align-items-center mb-4">
                    <i class="bi bi-info-circle me-2"></i>
                    Kandidat ini pernah melamar sebelumnya. Berikut adalah riwayat aplikasi dengan nama yang sama.
                </div>
                
                <div class="row">
                    <?php foreach ($history as $index => $hist): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card border-left-primary">
                            <div class="card-body p-3" style="border-left: 4px solid var(--primary-color);">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-1 fw-semibold"><?= $hist['posisi_dilamar'] ?></h6>
                                    <small class="text-muted">#<?= $hist['id'] ?></small>
                                </div>
                                
                                <div class="mb-2">
                                    <div class="status-badges">
                                        <?php if ($hist['status_recruitment'] == 'interview_hr'): ?>
                                            <span class="badge bg-info">
                                                <i class="bi bi-people"></i> Interview HR
                                            </span>
                                        <?php elseif ($hist['status_recruitment'] == 'pending'): ?>
                                            <span class="badge bg-warning">
                                                <i class="bi bi-hourglass"></i> Pending
                                            </span>
                                        <?php elseif ($hist['status_recruitment'] == 'testcase_1'): ?>
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle"></i> Testcase 1
                                            </span>
                                            <a href="<?= $hist['link_testcase']; ?>" class="btn btn-sm btn-primary mt-2" target="_blank">
                                                <i class="bi bi-eye"></i> Lihat Testcase
                                            </a>
                                        <?php elseif ($hist['status_recruitment'] == 'interview_user'): ?>
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle"></i> Interview User
                                            </span>
                                        <?php elseif ($hist['status_recruitment'] == 'selected'): ?>
                                            <span class="badge bg-info">
                                                <i class="bi bi-tools"></i> Selected
                                            </span>
                                        <?php elseif ($hist['status_recruitment'] == 'pertimbangan'): ?>
                                            <span class="badge bg-warning">
                                                <i class="bi bi-question-circle"></i> Pertimbangan
                                            </span>
                                        <?php elseif ($hist['status_recruitment'] == 'rejected'): ?>
                                            <span class="badge bg-danger">
                                                <i class="bi bi-x-circle"></i> Rejected
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if ($hist['status_approval'] == 'rejected'): ?>
                                            <span class="badge bg-danger">
                                                <i class="bi bi-x-circle"></i> Ditolak
                                            </span>
                                        <?php elseif ($hist['status_approval'] == 'approved'): ?>
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle"></i> Diterima
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="small text-muted mb-2">
                                    <div><i class="bi bi-calendar-event me-1"></i>Tanggal Apply: <?= $hist['formatted_created_at'] ?></div>
                                    <?php if ($hist['updated_at']): ?>
                                    <div><i class="bi bi-arrow-repeat me-1"></i>Last Update: <?= $hist['formatted_updated_at'] ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="small">
                                    <div class="text-muted">Ekspektasi Gaji:</div>
                                    <div class="fw-semibold text-success">Rp <?= $hist['ekspektasi_sallary'] ?></div>
                                </div>
                                
                                <?php if ($hist['notes_hr']): ?>
                                <div class="mt-2 p-2 bg-light rounded">
                                    <small class="text-muted d-block">Catatan HR:</small>
                                    <small><?= $hist['notes_hr'] ?></small>
                                </div>
                                <?php endif; ?>
                                
                                <div class="mt-3">
                                    <a href="<?= base_url() ?>recruitment/detail?id=<?= $hist['id'] ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-eye me-1"></i> Lihat Detail
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>