<style>
@keyframes pulse-career-badge {
    0% {
        box-shadow: 0 0 0 0 rgba(82, 196, 26, 0.7);
    }
    50% {
        box-shadow: 0 0 0 8px rgba(82, 196, 26, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(82, 196, 26, 0);
    }
}
</style>

<div class="container-fluid py-3">
    <!-- Profile Navigation Tabs -->
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="profile-tabs">
                <li class="nav-item">
                    <a class="nav-link active" id="profile-tab" data-bs-toggle="tab" href="#profile-content" role="tab">
                        <i class="bi bi-person-circle me-2"></i>Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="milestone-tab" data-bs-toggle="tab" href="#milestone-content" role="tab">
                        <i class="bi bi-trophy me-2"></i>Milestone
                    </a>
                </li>
            </ul>
        </div>

        <!-- Tab Content -->
        <div class="card-body">
            <div class="tab-content" id="profile-tab-content">
                <!-- Profile Tab -->
                <div class="tab-pane fade show active" id="profile-content" role="tabpanel">
                    <!-- User Profile Information Card -->
                    <div class="card mb-4 profile-card-enhanced card-hover-effect">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">
                                    <i class="bi bi-person-circle me-2"></i>Informasi Akun Saya
                                </h5>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#profileEditModal">
                                    <i class="bi bi-pencil me-1"></i> Edit Profil
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-4">
                                    <div class="text-center">
                                        <?php
                                        $img = $user_data['img'];
                                        if ($img == "") {
                                            $img_url = base_url() . '/assets/img/user/default.png';
                                        } else {
                                            $img_url = base_url() . '/assets/img/user/' . $img . '?token=' . DATE("Ymdhis", strtotime($user_data['updated_at']));
                                        }
                                        ?>
                                        <div class="profile-image-container">
                                            <img src="<?= $img_url ?>" class="img-fluid rounded-circle mb-3"
                                                style="width: 150px; height: 150px; object-fit: cover; border: 4px solid #e6f7ff;">
                                        </div>
                                        <h4 class="mb-1"><?= $user_data['full_name'] ?></h4>
                                        <p class="text-muted mb-2"><?= !empty($profile['position_name']) ? $profile['position_name'] : 'Posisi belum ditentukan' ?></p>
                                        <?php if (!empty($profile['level_name'])): ?>
                                            <span class="badge" style="background-color: #e6f7ff; color: #1890ff; border: 1px solid #91d5ff;">
                                                <?= $profile['level_name'] ?> Level
                                            </span>

                                            <!-- Position Career Progress -->
                                            <div class="mt-3">
                                                <small class="text-muted">Career Position Progress</small>

                                                <?php if (!empty($position_career_progress)): ?>
                                                    <div class="career-position-flow mt-2">
                                                        <!-- Current Position Status -->
                                                        <div class="current-position-status">
                                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                                <div class="position-info">
                                                                    <strong style="color: #1890ff; font-size: 13px;">
                                                                        <i class="bi bi-geo-alt-fill me-1"></i>Current Position
                                                                    </strong>
                                                                </div>
                                                                <span class="badge bg-primary px-2 py-1" style="font-size: 10px;">
                                                                    <?= !empty($profile['time_in_role']) ? $profile['time_in_role'] : 'Active' ?>
                                                                </span>
                                                            </div>

                                                            <!-- Available Career Paths -->
                                                            <?php if (!empty($position_career_progress['available_paths'])): ?>
                                                                <div class="available-paths mt-3">
                                                                    <small class="text-muted d-block mb-2">
                                                                        <i class="bi bi-arrow-up-right me-1"></i>Available Career Paths (<?= count($position_career_progress['available_paths']) ?>)
                                                                    </small>

                                                                    <?php foreach ($position_career_progress['available_paths'] as $path): ?>
                                                                        <?php
                                                                        // Check if this is the selected career path
                                                                        $is_selected = !empty($profile['preferred_career_path_id']) &&
                                                                                       $path['target_position_id'] == $profile['preferred_career_path_id'];

                                                                        // Special styling for selected path
                                                                        $card_bg = $is_selected ? '#f6ffed' : '#f8f9fa';
                                                                        $border_color = $is_selected ? '#52c41a' : '#1890ff';
                                                                        ?>
                                                                        <div class="career-path-item mb-2 p-2" style="background: <?= $card_bg ?>; border-radius: 6px; border-left: 3px solid <?= $border_color ?>;">
                                                                            <div class="d-flex align-items-center justify-content-between">
                                                                                <div class="flex-grow-1">
                                                                                    <div class="d-flex align-items-center">
                                                                                        <div class="path-target" style="font-size: 12px; font-weight: 600; color: #333;">
                                                                                            <?= $path['target_position_name'] ?>
                                                                                        </div>
                                                                                        <?php if ($is_selected): ?>
                                                                                            <span class="badge ms-2" style="background: linear-gradient(135deg, #52c41a, #389e0d); color: white; font-size: 9px; animation: pulse-career-badge 2s ease-in-out infinite;">
                                                                                                <i class="bi bi-star-fill me-1"></i>Selected
                                                                                            </span>
                                                                                        <?php endif; ?>
                                                                                    </div>
                                                                                    <div class="path-details" style="font-size: 11px; color: #666;">
                                                                                        <?= $path['target_department'] ?>
                                                                                        <?php if (!empty($path['target_level'])): ?>
                                                                                            • <?= $path['target_level'] ?> Level
                                                                                        <?php endif; ?>
                                                                                    </div>
                                                                                </div>
                                                                            </div>

                                                                            <?php if ($is_selected && !empty($selected_career_path['has_quest'])): ?>
                                                                                <!-- Quest Status for Selected Path -->
                                                                                <div class="quest-status mt-2 p-2" style="background: #f0f5ff; border-radius: 4px; border: 1px solid #91d5ff;">
                                                                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                                                                        <div>
                                                                                            <small class="text-muted d-block" style="font-size: 10px;">
                                                                                                <i class="bi bi-flag me-1"></i>Quest:
                                                                                            </small>
                                                                                            <strong style="font-size: 11px; color: #1890ff;">
                                                                                                <?= $selected_career_path['quest_title'] ?>
                                                                                            </strong>
                                                                                        </div>
                                                                                        <div>
                                                                                            <?php
                                                                                            $status_badge = [
                                                                                                'not_applied' => ['bg' => '#f5f5f5', 'color' => '#8c8c8c', 'text' => 'Not Applied'],
                                                                                                'pending' => ['bg' => '#fffbe6', 'color' => '#faad14', 'text' => 'Pending'],
                                                                                                'approved' => ['bg' => '#f6ffed', 'color' => '#52c41a', 'text' => 'Completed'],
                                                                                                'denied' => ['bg' => '#fff2f0', 'color' => '#ff4d4f', 'text' => 'Denied']
                                                                                            ];
                                                                                            $status = $status_badge[$selected_career_path['quest_status']] ?? $status_badge['not_applied'];
                                                                                            ?>
                                                                                            <span class="badge" style="background: <?= $status['bg'] ?>; color: <?= $status['color'] ?>; font-size: 9px; border: 1px solid <?= $status['color'] ?>33;">
                                                                                                <?= $status['text'] ?>
                                                                                            </span>
                                                                                        </div>
                                                                                    </div>

                                                                                    <?php if ($selected_career_path['quest_status'] == 'not_applied'): ?>
                                                                                        <a href="<?= base_url() ?>quest/main_quest_detail?id=<?= $selected_career_path['quest_id'] ?>"
                                                                                           class="btn btn-outline-primary btn-sm w-100" style="font-size: 10px;">
                                                                                            <i class="bi bi-play me-1"></i>View & Apply for Quest
                                                                                        </a>
                                                                                    <?php elseif ($selected_career_path['quest_status'] == 'pending'): ?>
                                                                                        <small class="d-block text-center" style="font-size: 10px; color: #666;">
                                                                                            <i class="bi bi-hourglass-split me-1"></i>Waiting for HR approval
                                                                                        </small>
                                                                                    <?php elseif ($selected_career_path['quest_status'] == 'approved'): ?>
                                                                                        <small class="d-block text-center" style="font-size: 10px; color: #52c41a;">
                                                                                            <i class="bi bi-check-circle me-1"></i>Quest completed! You can advance to next level
                                                                                        </small>
                                                                                    <?php elseif ($selected_career_path['quest_status'] == 'denied'): ?>
                                                                                        <small class="d-block text-center" style="font-size: 10px; color: #ff4d4f;">
                                                                                            <i class="bi bi-x-circle me-1"></i>Quest denied. Please contact HR for feedback
                                                                                        </small>
                                                                                    <?php endif; ?>
                                                                                </div>
                                                                            <?php elseif ($is_selected): ?>
                                                                                <div class="no-quest mt-2 p-2" style="background: #fffbe6; border-radius: 4px; border: 1px dashed #faad14; text-align: center;">
                                                                                    <small style="font-size: 10px; color: #666;">
                                                                                        <i class="bi bi-info-circle me-1"></i>No quest available yet for this path
                                                                                    </small>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>

                                                    <!-- Quick Action Buttons -->
                                                    <div class="career-actions mt-3 d-flex gap-2">
                                                        <a href="<?= base_url() ?>profile/career_paths" class="btn btn-outline-primary btn-sm flex-fill" style="font-size: 11px;">
                                                            <i class="bi bi-diagram-3 me-1"></i>View Full Paths
                                                        </a>
                                                        <?php if (!empty($position_career_progress['has_ready_paths'])): ?>
                                                            <a href="<?= base_url() ?>profile/apply_main_quest" class="btn btn-primary btn-sm flex-fill" style="font-size: 11px;">
                                                                <i class="bi bi-rocket me-1"></i>Apply for Quest
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="<?= base_url() ?>profile/career_recommendations" class="btn btn-outline-success btn-sm flex-fill" style="font-size: 11px;">
                                                                <i class="bi bi-lightbulb me-1"></i>Get Recommendations
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>

                                                <?php else: ?>
                                                    <!-- No Career Paths Available -->
                                                    <div class="no-career-paths mt-2 p-3" style="background: #f8f9fa; border-radius: 6px; text-align: center;">
                                                        <i class="bi bi-info-circle text-muted" style="font-size: 20px;"></i>
                                                        <div class="mt-2" style="font-size: 12px; color: #666;">
                                                            No career paths available from your current position.
                                                        </div>
                                                        <a href="<?= base_url() ?>profile/career_recommendations" class="btn btn-outline-secondary btn-sm mt-2" style="font-size: 11px;">
                                                            <i class="bi bi-lightbulb me-1"></i>Get Career Guidance
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <h6 class="text-muted mb-1">Email</h6>
                                            <p class="mb-3"><?= $user_data['email'] ?></p>

                                            <h6 class="text-muted mb-1">Username</h6>
                                            <p class="mb-3"><?= $user_data['username'] ?></p>

                                            <h6 class="text-muted mb-1">Role</h6>
                                            <p class="mb-3">
                                                <span class="badge" style="background-color: #e6f7ff; color: #1890ff; border: 1px solid #91d5ff;">
                                                    <?= $user_data['role_text'] ?>
                                                </span>
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <?php if (!empty($profile['phone_number'])): ?>
                                                <h6 class="text-muted mb-1">Nomor Telepon</h6>
                                                <p class="mb-3"><?= $profile['phone_number'] ?></p>
                                            <?php endif; ?>

                                            <?php if (!empty($profile['join_date'])): ?>
                                                <h6 class="text-muted mb-1">Tanggal Bergabung</h6>
                                                <p class="mb-3"><?= date('d M Y', strtotime($profile['join_date'])) ?></p>
                                            <?php endif; ?>

                                            <h6 class="text-muted mb-1">Total Skor Quest</h6>
                                            <p class="mb-3">
                                                <span class="badge bg-success" style="font-size: 1rem;">
                                                    <?= !empty($profile['score']) ? $profile['score'] : 0 ?> Poin
                                                </span>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Quest Statistics -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="text-center p-3 profile-stats-card">
                                                <h4 class="mb-1 text-success"><?= $quest_stats['completed_main_quests'] ?></h4>
                                                <small class="text-muted">Main Quest Selesai</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="text-center p-3 profile-stats-card">
                                                <h4 class="mb-1 text-success"><?= $quest_stats['completed_side_quests'] ?></h4>
                                                <small class="text-muted">Side Quest Selesai</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Main Quests Card -->
                        <div class="col-md-6 mb-4" id="main-quests-grid">
                            <div class="card h-100 card-hover-effect">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">
                                            <i class="bi bi-flag me-2"></i>Main Quests Tersedia
                                        </h5>
                                        <div class="d-flex gap-2">
                                            <?php
                                            $accessible_count = 0;
                                            $locked_count = 0;
                                            $applied_count = 0;

                                            foreach ($main_quests as $quest) {
                                                if ($quest['already_applied'] > 0) {
                                                    $applied_count++;
                                                } elseif ($quest['accessibility_status'] == 'locked') {
                                                    $locked_count++;
                                                } else {
                                                    $accessible_count++;
                                                }
                                            }
                                            ?>
                                            <?php if ($accessible_count > 0): ?>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-unlock me-1"></i><?= $accessible_count ?> Available
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($applied_count > 0): ?>
                                                <span class="badge bg-warning">
                                                    <i class="bi bi-check me-1"></i><?= $applied_count ?> Applied
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($locked_count > 0): ?>
                                                <span class="badge bg-danger">
                                                    <i class="bi bi-lock me-1"></i><?= $locked_count ?> Locked
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($main_quests)): ?>
                                        <div class="row">
                                            <?php foreach ($main_quests as $quest): ?>
                                                <?php
                                                // Determine card styling based on accessibility
                                                $is_accessible = $quest['accessibility_status'] == 'accessible';
                                                $is_applied = $quest['already_applied'] > 0;
                                                $is_locked = $quest['accessibility_status'] == 'locked';
                                                $is_career_path = !empty($quest['is_career_path_quest']) && $quest['is_career_path_quest'] == 1;

                                                if ($is_applied) {
                                                    $card_style = 'border-color: #faad14 !important; background-color: #fffbe6;';
                                                    $card_class = 'border-warning';
                                                } elseif ($is_locked) {
                                                    $card_style = 'border-color: #ff4d4f !important; background-color: #fff2f0; opacity: 0.7;';
                                                    $card_class = 'border-danger';
                                                } elseif ($is_career_path) {
                                                    $card_style = 'border-color: #52c41a !important; background: linear-gradient(to bottom, #f6ffed 0%, #ffffff 100%); border-width: 2px;';
                                                    $card_class = 'border-success';
                                                } else {
                                                    $card_style = 'border-color: #91d5ff !important;';
                                                    $card_class = 'border-info';
                                                }
                                                ?>
                                                <div class="col-12 mb-3 quest-item">
                                                    <div class="card border <?= $card_class ?>" style="<?= $card_style ?>">
                                                        <div class="card-body p-3">
                                                            <div class="d-flex justify-content-between align-items-start">
                                                                <div class="flex-grow-1">
                                                                    <div class="d-flex align-items-center mb-2">
                                                                        <?php if ($is_locked): ?>
                                                                            <i class="bi bi-lock-fill text-danger me-2"></i>
                                                                        <?php elseif ($is_applied): ?>
                                                                            <i class="bi bi-check-circle-fill text-warning me-2"></i>
                                                                        <?php else: ?>
                                                                            <i class="bi bi-star-fill text-primary me-2"></i>
                                                                        <?php endif; ?>
                                                                        <h6 class="card-title mb-0 <?= $is_locked ? 'text-muted' : '' ?>"><?= $quest['title'] ?></h6>

                                                                        <?php if (!empty($quest['is_career_path_quest']) && $quest['is_career_path_quest'] == 1): ?>
                                                                            <span class="badge ms-2" style="background: linear-gradient(135deg, #52c41a, #389e0d); color: white; font-size: 10px; animation: pulse-career-badge 2s ease-in-out infinite;">
                                                                                <i class="bi bi-bullseye me-1"></i>Your Career Path
                                                                            </span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <p class="card-text text-muted small mb-2">
                                                                        <?= substr($quest['description'], 0, 80) ?><?= strlen($quest['description']) > 80 ? '...' : '' ?>
                                                                    </p>
                                                                    <div>
                                                                        <strong class="<?= $is_locked ? 'text-muted' : 'text-primary' ?>"><?= $quest['position_name'] ?></strong>
                                                                        <br>
                                                                        <span class="badge" style="<?= $is_locked ? 'background-color: #f5f5f5; color: #8c8c8c; border: 1px solid #d9d9d9;' : 'background-color: #e6f7ff; color: #1890ff; border: 1px solid #91d5ff;' ?>">
                                                                            <?= $quest['level_name'] ?> Level
                                                                        </span>
                                                                        <?php if ($is_locked): ?>
                                                                            <br>
                                                                            <small class="text-danger mt-1">
                                                                                <i class="bi bi-arrow-up"></i> Level up required to unlock
                                                                            </small>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                                <div class="ms-2">
                                                                    <?php if ($is_applied): ?>
                                                                        <span class="badge bg-warning">
                                                                            <i class="bi bi-check me-1"></i>Sudah Apply
                                                                        </span>
                                                                    <?php elseif ($is_locked): ?>
                                                                        <button class="btn btn-sm btn-outline-secondary" disabled>
                                                                            <i class="bi bi-lock me-1"></i>Locked
                                                                        </button>
                                                                    <?php else: ?>
                                                                        <button class="btn btn-sm btn-primary apply-main-quest"
                                                                            data-quest-id="<?= $quest['id'] ?>"
                                                                            data-quest-title="<?= $quest['title'] ?>">
                                                                            <i class="bi bi-play-fill me-1"></i>Apply
                                                                        </button>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <i class="bi bi-flag" style="font-size: 3rem; color: #ccc;"></i>
                                            <h6 class="text-muted mt-3">Tidak ada main quest tersedia</h6>
                                            <p class="text-muted">Pastikan profil Anda sudah lengkap dengan posisi jabatan</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <nav id="main-quests-pager" class="mt-3"></nav>
                            </div>
                        </div>

                        <!-- Side Quests Card -->
                        <div class="col-md-6 mb-4" id="side-quests-grid">
                            <div class="card h-100 card-hover-effect">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">
                                            <i class="bi bi-star me-2"></i>Side Quests Tersedia
                                        </h5>
                                        <span class="badge bg-warning"><?= count($side_quests) ?> Quest</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($side_quests)): ?>
                                        <div class="row">
                                            <?php foreach ($side_quests as $quest): ?>
                                                <div class="col-12 mb-3 quest-item">
                                                    <div class="card border" style="border-color: #ffd591 !important;">
                                                        <div class="card-body p-3">
                                                            <div class="d-flex justify-content-between align-items-start">
                                                                <div class="flex-grow-1">
                                                                    <h6 class="card-title mb-2"><?= $quest['title'] ?></h6>
                                                                    <p class="card-text text-muted small mb-2">
                                                                        <?= substr($quest['description'], 0, 80) ?><?= strlen($quest['description']) > 80 ? '...' : '' ?>
                                                                    </p>
                                                                    <span class="badge" style="background-color: #fff7e6; color: #fa8c16; border: 1px solid #ffd591;">
                                                                        Terbuka untuk Semua
                                                                    </span>
                                                                </div>
                                                                <div class="ms-2">
                                                                    <?php if ($quest['already_applied'] > 0): ?>
                                                                        <span class="badge bg-info me-2">Applied <?= $quest['already_applied'] ?> times</span>
                                                                    <?php endif; ?>
                                                                    <button class="btn btn-sm btn-warning apply-side-quest"
                                                                        data-quest-id="<?= $quest['id'] ?>"
                                                                        data-quest-title="<?= $quest['title'] ?>">
                                                                        <?= $quest['already_applied'] > 0 ? 'Apply Again' : 'Apply' ?>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <i class="bi bi-star" style="font-size: 3rem; color: #ccc;"></i>
                                            <h6 class="text-muted mt-3">Tidak ada side quest tersedia</h6>
                                            <p class="text-muted">Side quest akan muncul di sini</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <nav id="side-quests-pager" class="mt-3"></nav>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Milestone Tab -->
                <div class="tab-pane fade" id="milestone-content" role="tabpanel">
                    <!-- Leaderboard Section -->
                    <div class="mb-4">
                        <?php $this->load->view('profile/leaderboard_widget'); ?>
                    </div>

                    <!-- Milestone Achievement Section -->
                    <div class="mb-4">
                        <?php $this->load->view('profile/milestone_widget'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Activities & Reviews Tabbed Card -->
    <div class="card card-hover-effect" id="activities-reviews-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <nav class="nav nav-tabs card-header-tabs" id="activity-tabs">
                <a class="nav-link active" data-bs-toggle="tab" href="#recent-activities" role="tab">
                    <i class="bi bi-clock-history me-2"></i>Aktifitas Quest Terbaru
                </a>
                <a class="nav-link" data-bs-toggle="tab" href="#my-reviews" role="tab">
                    <i class="bi bi-collection me-2"></i>My Review
                    <?php if (!empty($my_reviews)): ?>
                        <span class="badge bg-primary ms-1"><?= count($my_reviews) ?></span>
                    <?php endif; ?>
                </a>
            </nav>
            <div class="d-flex gap-2">
                <a href="<?= base_url() ?>profile/reviews" class="btn btn-outline-success btn-sm">
                    <i class="bi bi-collection me-1"></i>Community Reviews
                </a>
                <a href="<?= base_url() ?>profile/quest-history" class="btn btn-outline-primary btn-sm">
                    Lihat Semua
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="tab-content" id="activity-tab-content">
                <!-- Recent Activities Tab -->
                <div class="tab-pane fade show active" id="recent-activities" role="tabpanel">
                    <?php if (!empty($recent_submissions)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover recent-activities-table">
                                <thead>
                                    <tr>
                                        <th>Tipe</th>
                                        <th>Quest</th>
                                        <th>Status</th>
                                        <th>Tanggal Submit</th>
                                        <th>Benefit</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_submissions as $submission): ?>
                                        <tr>
                                            <td>
                                                <span class="badge <?= $submission['quest_type'] == 'main' ? 'bg-primary' : 'bg-warning' ?>">
                                                    <?= ucfirst($submission['quest_type']) ?> Quest
                                                </span>
                                            </td>
                                            <td><?= $submission['quest_title'] ?></td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                $status_text = '';
                                                switch ($submission['status']) {
                                                    case 'pending':
                                                        $status_class = 'bg-warning';
                                                        $status_text = 'Pending';
                                                        break;
                                                    case 'approved':
                                                        $status_class = 'bg-success';
                                                        $status_text = 'Disetujui';
                                                        break;
                                                    case 'denied':
                                                        $status_class = 'bg-danger';
                                                        $status_text = 'Ditolak';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?= $status_class ?>"><?= $status_text ?></span>
                                            </td>
                                            <td><?= date('d M Y H:i', strtotime($submission['submitted_at'])) ?></td>
                                            <td>
                                                <?php if ($submission['benefit_type']): ?>
                                                    <span class="badge bg-info"><?= ucfirst($submission['benefit_type']) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($submission['status'] == 'pending'): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-danger cancel-quest"
                                                        data-quest-type="<?= $submission['quest_type'] ?>"
                                                        data-submission-id="<?= $submission['submission_id'] ?>"
                                                        data-quest-title="<?= htmlspecialchars($submission['quest_title']) ?>"
                                                        title="Batalkan aplikasi quest">
                                                        <i class="bi bi-x-circle me-1"></i>Cancel
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-clock-history" style="font-size: 3rem; color: #ccc;"></i>
                            <h6 class="text-muted mt-3">Belum ada aktifitas quest</h6>
                            <p class="text-muted">Mulai apply quest untuk melihat aktifitas di sini</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- My Reviews Tab -->
                <div class="tab-pane fade" id="my-reviews" role="tabpanel">
                    <?php if (!empty($my_reviews)): ?>
                        <div class="row" id="reviews-grid">
                            <?php foreach ($my_reviews as $review): ?>
                                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                                    <div class="review-card" data-submission-id="<?= $review['id'] ?>" style="cursor: pointer;">
                                        <div class="card h-100 review-item-card">
                                            <div class="card-img-wrapper">
                                                <?php
                                                $image_url = 'https://placehold.co/400x200/f0f0f0/666666?text=No+Image'; // Default placeholder

                                                if (!empty($review['submission_image'])) {
                                                    $upload_path = FCPATH . 'assets/uploads/side_quest_user_images/' . $review['submission_image'];
                                                    if (file_exists($upload_path)) {
                                                        $image_url = base_url() . 'assets/uploads/side_quest_user_images/' . $review['submission_image'];
                                                    }
                                                }
                                                ?>
                                                <img src="<?= $image_url ?>" class="card-img-top" alt="<?= htmlspecialchars($review['submission_title']) ?>"
                                                    onerror="this.src='https://placehold.co/400x200/f0f0f0/666666?text=No+Image'">
                                                <div class="card-img-overlay-bottom">
                                                    <h6 class="text-white mb-1"><?= htmlspecialchars($review['submission_title']) ?></h6>
                                                    <small class="text-white-50"><?= htmlspecialchars($review['quest_title']) ?></small>
                                                </div>
                                                <div class="card-status-badge">
                                                    <?php
                                                    $status_class = '';
                                                    $status_text = '';
                                                    switch ($review['status']) {
                                                        case 'pending':
                                                            $status_class = 'bg-warning';
                                                            $status_text = 'Pending';
                                                            break;
                                                        case 'approved':
                                                            $status_class = 'bg-success';
                                                            $status_text = 'Approved';
                                                            break;
                                                        case 'denied':
                                                            $status_class = 'bg-danger';
                                                            $status_text = 'Denied';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?= $status_class ?>"><?= $status_text ?></span>
                                                </div>
                                            </div>
                                            <div class="card-body p-3">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted"><?= date('d M Y', strtotime($review['submitted_at'])) ?></small>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="bi bi-eye text-primary" title="View Details"></i>
                                                        <?php
                                                        // Check if this is a Film or Book quest - now deletable regardless of status
                                                        $isFilmQuest = strpos(strtolower($review['quest_title']), 'nonton film') !== false;
                                                        $isBookQuest = strpos(strtolower($review['quest_title']), 'baca buku') !== false;
                                                        $isDeletable = ($isFilmQuest || $isBookQuest);
                                                        $isEditable = ($isFilmQuest || $isBookQuest) && ($review['status'] == 'pending' || $review['status'] == 'approved');

                                                        // Debug: uncomment to see values
                                                        echo "<!-- Quest: {$review['quest_title']}, Film: " . ($isFilmQuest ? 'YES' : 'NO') . ", Book: " . ($isBookQuest ? 'YES' : 'NO') . ", Status: {$review['status']}, Editable: " . ($isEditable ? 'YES' : 'NO') . ", Deletable: " . ($isDeletable ? 'YES' : 'NO') . " -->";
                                                        ?>
                                                        <?php if ($isEditable): ?>
                                                            <i class="bi bi-pencil text-warning edit-review"
                                                               title="Edit Review"
                                                               style="cursor: pointer;"
                                                               data-submission-id="<?= $review['id'] ?>"
                                                               data-quest-title="<?= htmlspecialchars($review['quest_title']) ?>"
                                                               data-submission-title="<?= htmlspecialchars($review['submission_title']) ?>"></i>
                                                        <?php endif; ?>
                                                        <?php if ($isDeletable): ?>
                                                            <button class="btn btn-sm btn-outline-danger delete-review"
                                                                style="padding: 2px 6px; font-size: 0.75rem; border-radius: 4px;"
                                                                title="Delete Review"
                                                                data-submission-id="<?= $review['id'] ?>"
                                                                data-quest-title="<?= htmlspecialchars($review['quest_title']) ?>"
                                                                data-submission-title="<?= htmlspecialchars($review['submission_title']) ?>">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-collection" style="font-size: 3rem; color: #ccc;"></i>
                            <h6 class="text-muted mt-3">Belum ada review</h6>
                            <p class="text-muted">Mulai mengerjakan quest Film atau Buku untuk membuat review</p>
                            <div class="mt-3">
                                <small class="text-muted">Review akan muncul setelah Anda mengerjakan quest:</small>
                                <div class="d-flex justify-content-center gap-2 mt-2">
                                    <span class="badge bg-primary">🎬 Nonton Film</span>
                                    <span class="badge bg-success">📚 Baca Buku</span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TinyMCE Rich Text Editor -->
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>

<!-- Profile Edit Modal -->
<div class="modal fade" id="profileEditModal" tabindex="-1" aria-labelledby="profileEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="profileEditModalLabel">Edit Profil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-message"></div>
                <form action="<?= base_url() ?>profile/update_process" method="POST" id="form-profile-edit" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="full_name">Nama Lengkap</label>
                            <input type="text" class="form-control" name="dt[full_name]" value="<?= $user_data['full_name'] ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="username">Username</label>
                            <input type="text" class="form-control" name="dt[username]" value="<?= $user_data['username'] ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" name="dt[email]" value="<?= $user_data['email'] ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="password">Password Baru</label>
                            <input type="password" class="form-control" name="dt[password]" placeholder="Kosongkan jika tidak diubah">
                            <small class="text-muted">Kosongkan jika tidak ingin mengubah password</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="birth_date">Tanggal Lahir</label>
                            <input type="date" class="form-control" name="dt[birth_date]" value="<?= $user_data['birth_date'] ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="file">Foto Profil</label>
                            <input type="file" class="form-control" name="file" accept="image/png, image/jpeg, image/jpg">
                            <?php if ($user_data['img']): ?>
                                <small class="text-muted">Foto saat ini: <a href="<?= $img_url ?>" target="_blank">Lihat foto</a></small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="desc">Keterangan</label>
                            <textarea class="form-control" name="dt[desc]" rows="3"><?= $user_data['desc'] ?></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" form="form-profile-edit" class="btn btn-primary btn-save">Simpan Perubahan</button>
            </div>
        </div>
    </div>
</div>

<!-- Main Quest Apply Modal -->
<div class="modal fade" id="mainQuestModal" tabindex="-1" aria-labelledby="mainQuestModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mainQuestModalLabel">Apply Main Quest</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-message-quest"></div>
                <p>Apakah Anda yakin ingin mengajukan aplikasi untuk quest "<span id="main-quest-title"></span>"?</p>
                <p class="text-muted small">Setelah diajukan, aplikasi Anda akan direview oleh HR dan tidak dapat dibatalkan.</p>
                <form id="form-main-quest">
                    <input type="hidden" id="main-quest-id" name="quest_id">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" form="form-main-quest" class="btn btn-primary btn-apply-main">Apply Quest</button>
            </div>
        </div>
    </div>
</div>

<!-- Side Quest Apply Modal -->
<div class="modal fade" id="sideQuestModal" tabindex="-1" aria-labelledby="sideQuestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sideQuestModalLabel">Apply Side Quest</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-message-side-quest"></div>
                <p>Quest: "<span id="side-quest-title"></span>"</p>
                <form id="form-side-quest" enctype="multipart/form-data" action="javascript:void(0);">
                    <input type="hidden" id="side-quest-id" name="quest_id">

                    <!-- Conditional Fields for Film and Book Quests -->
                    <div id="conditional-fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="submission_title" class="form-label">
                                    <span id="title-label">Judul</span> <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="submission_title" name="submission_title"
                                    placeholder="Masukkan judul film atau buku">
                                <small class="text-muted" id="title-help">Masukkan judul lengkap</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="submission_image" class="form-label">
                                    <span id="image-label">Gambar</span> <small class="text-muted">(opsional)</small>
                                </label>
                                <input type="file" class="form-control" id="submission_image" name="submission_image"
                                    accept="image/*">
                                <small class="text-muted" id="image-help">Upload cover atau gambar terkait (max 2MB)</small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="hasil" class="form-label">Hasil Kerja <span class="text-danger">*</span></label>

                        <!-- Summernote Rich Text Editor -->
                        <div id="hasil-editor"></div>

                        <!-- Hidden field for plain text version -->
                        <input type="hidden" id="hasil" name="hasil">


                        <small class="text-muted" id="hasil-help">
                            <i class="bi bi-info-circle me-1"></i>
                            Jelaskan hasil pekerjaan Anda dengan detail. HR akan mengevaluasi dan memberikan skor berdasarkan kualitas hasil kerja yang Anda submit.
                        </small>

                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x me-1"></i>Batal
                </button>
                <button type="submit" form="form-side-quest" class="btn btn-warning btn-apply-side">
                    <i class="bi bi-send me-1"></i>Submit Hasil Kerja
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Quest Cancel Confirmation Modal -->
<div class="modal fade" id="cancelQuestModal" tabindex="-1" aria-labelledby="cancelQuestModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="border-bottom: 1px solid #f0f0f0;">
                <h5 class="modal-title" id="cancelQuestModalLabel" style="color: rgba(0,0,0,0.85);">
                    <i class="bi bi-exclamation-triangle text-warning me-2"></i>Batalkan Aplikasi Quest
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 24px;">
                <div class="form-message-cancel"></div>
                <div class="alert alert-warning" style="background-color: #fff7e6; border: 1px solid #ffd591; border-radius: 6px;">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Perhatian:</strong> Tindakan ini tidak dapat dibatalkan.
                </div>
                <p style="margin-bottom: 16px;">
                    Apakah Anda yakin ingin membatalkan aplikasi untuk quest:
                </p>
                <div style="background-color: #f8f9fa; padding: 12px; border-radius: 6px; border-left: 4px solid #ff4d4f;">
                    <strong id="cancel-quest-title" style="color: rgba(0,0,0,0.85);"></strong>
                </div>
                <form id="form-cancel-quest">
                    <input type="hidden" id="cancel-submission-id" name="submission_id">
                    <input type="hidden" id="cancel-quest-type" name="quest_type">
                </form>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f0f0f0;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x me-1"></i>Tidak, Kembali
                </button>
                <button type="submit" form="form-cancel-quest" class="btn btn-danger btn-cancel-quest">
                    <i class="bi bi-trash me-1"></i>Ya, Batalkan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Review Detail Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewModalLabel">
                    <i class="bi bi-collection me-2"></i>Review Detail
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- IMAGE (top) -->
                <div class="review-image-container text-center mb-4">
                    <img
                        id="review-modal-image"
                        class="img-fluid rounded"
                        style="max-height: 400px; object-fit: cover; width: 100%;"
                        alt="Review Image" />
                </div>

                <!-- CONTENT (now below image) -->
                <div class="review-content mt-2">
                    <h4 id="review-modal-title" class="mb-1"></h4>
                    <p class="text-muted mb-3" id="review-modal-quest"></p>

                    <div class="review-meta mb-4">
                        <div class="row g-2 align-items-center">
                            <div class="col-auto">
                                <span id="review-modal-status" class="badge"></span>
                            </div>
                            <div class="col">
                                <small class="text-muted">
                                    <i class="bi bi-calendar me-1"></i>
                                    Submitted: <span id="review-modal-date"></span>
                                </small>
                                <span id="review-modal-approved-date" class="d-block mt-1 text-success" style="display:none!important;">
                                    <small>
                                        <i class="bi bi-check-circle me-1"></i>
                                        Approved: <span id="review-modal-approved-date-text"></span>
                                    </small>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="review-text-content">
                        <h6 class="mb-2"><i class="bi bi-chat-text me-2"></i>Review Content:</h6>
                        <div id="review-modal-content"
                            class="review-text p-3 bg-light rounded"
                            style="max-height: 320px; overflow-y: auto; line-height: 1.6;"></div>
                    </div>

                    <div class="mt-4" id="review-modal-hr-notes-section" style="display:none;">
                        <h6 class="mb-2"><i class="bi bi-person-badge me-2"></i>HR Notes:</h6>
                        <div class="alert alert-info" id="review-modal-hr-notes"></div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x me-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Review Modal -->
<div class="modal fade" id="editReviewModal" tabindex="-1" aria-labelledby="editReviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editReviewModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Edit Review
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-message-edit-review"></div>
                <p>Quest: "<span id="edit-quest-title"></span>"</p>
                <form id="form-edit-review" enctype="multipart/form-data" action="javascript:void(0);">
                    <input type="hidden" id="edit-submission-id" name="submission_id">
                    <input type="hidden" id="edit-quest-id" name="quest_id">

                    <!-- Title Field for Film and Book Reviews -->
                    <div id="edit-conditional-fields">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_submission_title" class="form-label">
                                    <span id="edit-title-label">Judul</span> <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="edit_submission_title" name="submission_title"
                                    placeholder="Masukkan judul film atau buku">
                                <small class="text-muted" id="edit-title-help">Masukkan judul lengkap</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_submission_image" class="form-label">
                                    <span id="edit-image-label">Gambar</span> <small class="text-muted">(opsional)</small>
                                </label>
                                <input type="file" class="form-control" id="edit_submission_image" name="submission_image"
                                    accept="image/*">
                                <small class="text-muted" id="edit-image-help">Upload cover atau gambar terkait (max 2MB)</small>

                                <!-- Current image preview -->
                                <div id="current-image-preview" class="mt-2" style="display: none;">
                                    <small class="text-muted">Gambar saat ini:</small>
                                    <div class="mt-1">
                                        <img id="current-image" src="" alt="Current Image"
                                             class="img-thumbnail" style="max-height: 100px; max-width: 100px;">
                                        <small class="d-block text-muted mt-1">Upload file baru untuk mengubah gambar</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_hasil" class="form-label">Hasil Kerja <span class="text-danger">*</span></label>

                        <!-- Rich Text Editor -->
                        <div id="edit-hasil-editor"></div>

                        <!-- Hidden field for plain text version -->
                        <input type="hidden" id="edit_hasil" name="hasil">

                        <small class="text-muted" id="edit-hasil-help">
                            <i class="bi bi-info-circle me-1"></i>
                            Jelaskan hasil pekerjaan Anda dengan detail. HR akan mengevaluasi dan memberikan skor berdasarkan kualitas hasil kerja yang Anda submit.
                        </small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x me-1"></i>Batal
                </button>
                <button type="submit" form="form-edit-review" class="btn btn-primary btn-update-review">
                    <i class="bi bi-check-lg me-1"></i>Update Review
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        const PER_PAGE = 3;
        const FORCE_SHOW_SINGLE_PAGE = false; // set true to always show pager

        function setupPagination(gridSel, pagerSel, perPage, urlKey) {
            const grid = document.querySelector(gridSel);
            if (!grid) {
                console.warn('Grid not found:', gridSel);
                return;
            }

            // ensure pager exists
            let pager = document.querySelector(pagerSel);
            if (!pager) {
                pager = document.createElement('nav');
                pager.id = pagerSel.replace(/^#/, '');
                grid.parentElement.appendChild(pager);
                console.info('Created missing pager:', pagerSel);
            }

            const items = Array.from(grid.querySelectorAll('.quest-item'));
            const total = items.length;
            if (total === 0) {
                pager.innerHTML = '';
                return;
            }

            const pages = Math.max(1, Math.ceil(total / perPage));

            // read initial page from URL
            const params = new URLSearchParams(window.location.search);
            let current = Math.min(Math.max(parseInt(params.get(urlKey) || '1', 10), 1), pages);

            // show a page
            function show(page) {
                const start = (page - 1) * perPage;
                const end = start + perPage;
                items.forEach((el, idx) => {
                    el.style.display = (idx >= start && idx < end) ? '' : 'none';
                });
            }

            // render pager
            function renderPager() {
                // hide pager if only one page and not forcing
                if (pages === 1 && !FORCE_SHOW_SINGLE_PAGE) {
                    pager.innerHTML = '';
                    return;
                }

                const ul = document.createElement('ul');
                ul.className = 'pagination justify-content-center mb-0';

                const addBtn = (label, page, disabled = false, active = false) => {
                    const li = document.createElement('li');
                    li.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
                    const a = document.createElement('a');
                    a.className = 'page-link';
                    a.href = 'javascript:void(0)';
                    a.textContent = label;
                    a.addEventListener('click', () => {
                        if (!disabled && !active) goTo(page);
                    });
                    li.appendChild(a);
                    ul.appendChild(li);
                };

                addBtn('Previous', current - 1, current <= 1);

                // compact page window
                const windowSize = 5;
                let start = Math.max(1, current - Math.floor(windowSize / 2));
                let end = Math.min(pages, start + windowSize - 1);
                start = Math.max(1, Math.min(start, pages - windowSize + 1));

                if (start > 1) addBtn('1', 1, false, current === 1);
                if (start > 2) addBtn('…', current, true, false);

                for (let i = start; i <= end; i++) addBtn(String(i), i, false, i === current);

                if (end < pages - 1) addBtn('…', current, true, false);
                if (end < pages) addBtn(String(pages), pages, false, current === pages);

                addBtn('Next', current + 1, current >= pages);

                pager.innerHTML = '';
                pager.appendChild(ul);
            }

            function goTo(page) {
                current = Math.min(Math.max(page, 1), pages);
                show(current);
                renderPager();

                // update URL without reload
                const url = new URL(window.location.href);
                url.searchParams.set(urlKey, current);
                history.replaceState(null, '', url.toString());
            }

            // initial render
            show(current);
            renderPager();

            // diagnostics
            console.info(`[pager] ${gridSel}: total=${total}, pages=${pages}, current=${current}`);
        }

        // init both pagers
        setupPagination('#main-quests-grid', '#main-quests-pager', PER_PAGE, 'mp');
        setupPagination('#side-quests-grid', '#side-quests-pager', PER_PAGE, 'sp');
        // Profile Edit Form
        $("#form-profile-edit").submit(function() {
            var form = $(this);
            var mydata = new FormData(this);
            $.ajax({
                type: "POST",
                url: form.attr("action"),
                data: mydata,
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    $(".btn-save").addClass("disabled").html('<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...').attr('disabled', true);
                    form.find(".form-message").slideUp().html("");
                },
                success: function(response) {
                    var str = response;
                    if (str.indexOf("success") != -1) {
                        $(".form-message").hide().html(response).slideDown("fast");
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        $(".form-message").hide().html(response).slideDown("fast");
                        $(".btn-save").removeClass("disabled").html('Simpan Perubahan').attr('disabled', false);
                    }
                },
                error: function() {
                    $(".btn-save").removeClass("disabled").html('Simpan Perubahan').attr('disabled', false);
                    $(".form-message").hide().html('<div class="alert alert-danger">Terjadi kesalahan sistem.</div>').slideDown("fast");
                }
            });
            return false;
        });

        // Main Quest Apply
        $('.apply-main-quest').click(function() {
            var questId = $(this).data('quest-id');
            var questTitle = $(this).data('quest-title');

            $('#main-quest-id').val(questId);
            $('#main-quest-title').text(questTitle);
            $('#mainQuestModal').modal('show');
        });

        $("#form-main-quest").submit(function() {
            var form = $(this);
            var mydata = form.serialize();
            $.ajax({
                type: "POST",
                url: "<?= base_url() ?>profile/apply-main-quest",
                data: mydata,
                beforeSend: function() {
                    $(".btn-apply-main").addClass("disabled").html('<span class="spinner-border spinner-border-sm me-2"></span>Mengajukan...').attr('disabled', true);
                    $(".form-message-quest").slideUp().html("");
                },
                success: function(response) {
                    var str = response;
                    if (str.indexOf("success") != -1) {
                        $(".form-message-quest").hide().html(response).slideDown("fast");
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        $(".form-message-quest").hide().html(response).slideDown("fast");
                        $(".btn-apply-main").removeClass("disabled").html('Apply Quest').attr('disabled', false);
                    }
                },
                error: function() {
                    $(".btn-apply-main").removeClass("disabled").html('Apply Quest').attr('disabled', false);
                    $(".form-message-quest").hide().html('<div class="alert alert-danger">Terjadi kesalahan sistem.</div>').slideDown("fast");
                }
            });
            return false;
        });


        // Global TinyMCE editor instance reference
        var tinyMCEInstance = null;

        // Initialize TinyMCE Editor with improved loading and fallback
        function initializeHasilEditor() {
            try {

                // Show loading indicator
                $('#hasil-editor').before('<div id="editor-loading" class="text-center p-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading editor...</span></div><p class="mt-2 text-muted">Loading rich text editor...</p></div>');

                // Destroy existing TinyMCE instance if it exists
                if (tinymce.get('hasil-editor')) {
                    tinymce.remove('#hasil-editor');
                }

                // Check if TinyMCE is available
                if (typeof tinymce === 'undefined') {

                    // Set up event listeners for TinyMCE loading events
                    $(document).one('tinymce:loaded', function() {
                        $('#editor-loading').remove();
                        initializeTinyMCEInstance();
                    });

                    $(document).one('tinymce:failed', function() {
                        $('#editor-loading').remove();
                        setupFallbackTextarea();
                        return false;
                    });

                    // Set a timeout in case the events never fire
                    setTimeout(function() {
                        if (typeof tinymce === 'undefined') {
                            $('#editor-loading').remove();
                            setupFallbackTextarea();
                            $(document).off('tinymce:loaded');
                        }
                    }, 5000);

                    return false;
                } else {
                    // TinyMCE is already loaded, initialize it directly
                    $('#editor-loading').remove();
                    return initializeTinyMCEInstance();
                }
            } catch (error) {
                $('#editor-loading').remove();
                setupFallbackTextarea();
                return false;
            }
        }

        // Function to initialize TinyMCE instance
        function initializeTinyMCEInstance() {
            try {
                // Initialize TinyMCE
                tinymce.init({
                    selector: '#hasil-editor',
                    height: 300,
                    min_height: 300,
                    max_height: 500,
                    menubar: false,
                    branding: false,
                    placeholder: 'Masukkan dokumentasi hasil kerja Anda...\n\n📝 Catatan atau ringkasan pekerjaan\n🎥 Link video dokumentasi\n📁 Link file atau folder hasil kerja\n✨ Penjelasan proses dan hasil yang dicapai\n\nGunakan toolbar di atas untuk memformat teks!',
                    plugins: 'lists link image table code help wordcount',
                    toolbar: 'styles | bold italic underline | fontfamily fontsize | forecolor | alignleft aligncenter alignright | bullist numlist | link image | table | code fullscreen help',
                    font_family_formats: 'Arial=arial,helvetica,sans-serif; Arial Black=arial black,avant garde; Comic Sans MS=comic sans ms,sans-serif; Courier New=courier new,courier; Helvetica=helvetica; Impact=impact,chicago; Tahoma=tahoma,arial,helvetica,sans-serif; Times New Roman=times new roman,times; Verdana=verdana,geneva',
                    font_size_formats: '8pt 9pt 10pt 11pt 12pt 14pt 16pt 18pt 20pt 22pt 24pt 36pt',
                    setup: function(editor) {
                        // Store the editor instance
                        editor.on('init', function() {
                            tinyMCEInstance = editor;

                            // Add visual feedback
                            setTimeout(() => {
                                const editorContainer = $(editor.getContainer());
                                if (editorContainer.length) {
                                    editorContainer.css('box-shadow', '0 0 10px rgba(24, 144, 255, 0.3)');
                                    setTimeout(() => {
                                        editorContainer.css('box-shadow', '');
                                    }, 1000);
                                }

                                // Focus the editor
                                editor.focus();
                            }, 300);
                        });

                        // Sync content to hidden field on change
                        editor.on('change', function() {
                            const htmlContent = editor.getContent();
                            const textContent = $(editor.getContent({
                                format: 'text'
                            })).text();

                            $('#hasil').val(textContent || editor.getContent({
                                format: 'text'
                            }));
                            // hasil-editor already contains the HTML content
                        });
                    },
                    content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; line-height: 1.6; padding: 10px; }',
                    mobile: {
                        menubar: false,
                        toolbar: 'bold italic | bullist numlist | link'
                    }
                }).then(function() {
                    return true;
                }).catch(function(error) {
                    setupFallbackTextarea();
                    return false;
                });

                return true;
            } catch (error) {
                setupFallbackTextarea();
                return false;
            }
        }

        // Function to set up fallback textarea when TinyMCE fails
        function setupFallbackTextarea() {
            const textarea = document.querySelector('#hasil-editor');
            if (textarea) {
                // Show a notification to the user
                $('<div class="alert alert-warning mb-3" id="editor-fallback-notice">' +
                    '<i class="bi bi-exclamation-triangle me-2"></i>' +
                    'Rich text editor could not be loaded. Using simple text editor instead.' +
                    '</div>').insertBefore(textarea);

                // Style the textarea to look better
                textarea.style.display = 'block';
                textarea.style.minHeight = '300px';
                textarea.style.border = '2px solid #1890ff';
                textarea.style.borderRadius = '8px';
                textarea.style.padding = '15px';
                textarea.style.fontSize = '14px';
                textarea.style.fontFamily = 'Arial, sans-serif';
                textarea.placeholder = '📝 Masukkan dokumentasi hasil kerja Anda...\n\n• Catatan atau ringkasan pekerjaan\n• Link video dokumentasi\n• Link file atau folder hasil kerja\n• Penjelasan proses dan hasil yang dicapai\n\n(Simple text editor mode - you can still type here!)';

                // Add event listener to sync with hidden field
                $(textarea).on('input', function() {
                    $('#hasil').val($(this).val());
                });

                // Focus the textarea
                textarea.focus();
            }
        }

        // Side Quest Apply - using event delegation for better reliability
        $(document).on('click', '.apply-side-quest', function(e) {
            e.preventDefault();

            var questId = $(this).data('quest-id');
            var questTitle = $(this).data('quest-title');

            $('#side-quest-id').val(questId);
            $('#side-quest-title').text(questTitle);
            $('#submission_title').val(''); // Clear title field
            $('#submission_image').val(''); // Clear image field

            // Detect quest type and show/hide conditional fields
            var isFilmQuest = questTitle.toLowerCase().includes('nonton film');
            var isBookQuest = questTitle.toLowerCase().includes('baca buku');

            if (isFilmQuest || isBookQuest) {
                // Show conditional fields
                $('#conditional-fields').show();
                $('#submission_title').prop('required', true);

                if (isFilmQuest) {
                    // Configure for film quest
                    $('#title-label').text('Judul Film');
                    $('#title-help').text('Masukkan judul film yang ditonton');
                    $('#image-label').text('Cover/Screenshot Film');
                    $('#image-help').text('Upload cover film atau screenshot (max 2MB)');
                    $('#submission_title').attr('placeholder', 'Contoh: Parasite (2019)');
                } else if (isBookQuest) {
                    // Configure for book quest
                    $('#title-label').text('Judul Buku');
                    $('#title-help').text('Masukkan judul buku dan nama penulis');
                    $('#image-label').text('Cover Buku');
                    $('#image-help').text('Upload foto cover buku (max 2MB)');
                    $('#submission_title').attr('placeholder', 'Contoh: Atomic Habits - James Clear');
                }
            } else {
                // Hide conditional fields for other quests
                $('#conditional-fields').hide();
                $('#submission_title').prop('required', false);
            }

            // Show loading indicator for the editor
            $('#hasil-editor').before('<div id="editor-init-loading" class="text-center p-3 mb-3"><div class="spinner-border text-warning" role="status"><span class="visually-hidden">Preparing editor...</span></div><p class="mt-2 text-muted">Preparing rich text editor...</p></div>');

            // Initialize rich text editor with a slight delay to allow modal to fully render
            setTimeout(() => {
                $('#editor-init-loading').remove();

                // Initialize the editor
                initializeHasilEditor();
            }, 300);

            // Show modal
            $('#sideQuestModal').modal('show');
        });

        $("#form-side-quest").submit(function(e) {
            e.preventDefault(); // Prevent default form submission
            var form = $(this);

            // Extract content from TinyMCE editor - with safer content extraction
            var hasilHtml = '';
            var hasilText = '';

            try {
                if (tinyMCEInstance && tinymce.get('hasil-editor')) {
                    // Get content from TinyMCE
                    hasilHtml = tinymce.get('hasil-editor').getContent();

                    // Get plain text safely
                    try {
                        hasilText = tinymce.get('hasil-editor').getContent({
                            format: 'text'
                        }).trim();
                    } catch (e) {
                        // Fallback: strip tags manually
                        hasilText = $('<div>').html(hasilHtml).text().trim();
                    }
                } else {
                    // Fallback to textarea value
                    hasilText = $('#hasil-editor').val().trim();
                    hasilHtml = hasilText.replace(/\n/g, '<br>');
                }

                // Ensure we have valid content
                if (!hasilText && hasilHtml) {
                    hasilText = $('<div>').html(hasilHtml).text().trim();
                }

            } catch (e) {
                // Emergency fallback
                hasilText = $('#hasil').val() || $('#hasil-editor').val() || 'Content extraction failed';
                hasilHtml = '<p>' + hasilText.replace(/\n/g, '<br>') + '</p>';
            }

            // Validate hasil field - check editor content
            if (!hasilText || hasilText.length < 10) {
                $(".form-message-side-quest").html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Hasil kerja harus diisi minimal 10 karakter!</div>').slideDown("fast");
                return false;
            }

            // Update hidden fields with editor content
            $('#hasil').val(hasilText);

            // Note: hasil_html is already the name of the main textarea, so TinyMCE handles this automatically

            // Additional validation for Film and Book quests
            var questTitle = $('#side-quest-title').text();
            var isFilmQuest = questTitle.toLowerCase().includes('nonton film');
            var isBookQuest = questTitle.toLowerCase().includes('baca buku');

            if ((isFilmQuest || isBookQuest) && !$('#submission_title').val().trim()) {
                $(".form-message-side-quest").html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Judul ' + (isFilmQuest ? 'film' : 'buku') + ' harus diisi!</div>').slideDown("fast");
                return false;
            }

            // Validate file upload if present
            var fileInput = $('#submission_image')[0];
            if (fileInput.files.length > 0) {
                var file = fileInput.files[0];
                var maxSize = 2 * 1024 * 1024; // 2MB in bytes

                if (file.size > maxSize) {
                    $(".form-message-side-quest").html('<div class="alert alert-danger"><i class="bi bi-file-earmark-x me-2"></i>Ukuran file terlalu besar! Maksimal 2MB.</div>').slideDown("fast");
                    return false;
                }

                // Check file type
                var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    $(".form-message-side-quest").html('<div class="alert alert-danger"><i class="bi bi-file-earmark-x me-2"></i>Format file tidak didukung! Gunakan JPG, PNG, atau WEBP.</div>').slideDown("fast");
                    return false;
                }
            }

            // Use FormData for file upload support
            var formData = new FormData();
            formData.append('quest_id', $('#side-quest-id').val());
            formData.append('hasil', hasilText);
            formData.append('hasil_html', hasilHtml);

            if (isFilmQuest || isBookQuest) {
                formData.append('submission_title', $('#submission_title').val());
                if (fileInput.files.length > 0) {
                    formData.append('submission_image', fileInput.files[0]);
                }
            }

            $.ajax({
                type: "POST",
                url: "<?= base_url('profile/apply_side_quest') ?>",
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                beforeSend: function() {
                    $(".btn-apply-side").addClass("disabled").html('<span class="spinner-border spinner-border-sm me-2"></span>Mengirim hasil kerja...').attr('disabled', true);
                    $(".form-message-side-quest").slideUp().html("");

                    // Disable editor during submission - safely check if setMode exists
                    try {
                        if (tinyMCEInstance && tinymce.get('hasil-editor') && typeof tinymce.get('hasil-editor').setMode === 'function') {
                            tinymce.get('hasil-editor').setMode('readonly');
                        } else if (tinyMCEInstance && tinymce.get('hasil-editor')) {
                            // Alternative method to disable editor if setMode is not available
                            tinymce.get('hasil-editor').setContent(tinymce.get('hasil-editor').getContent());
                            tinymce.get('hasil-editor').getBody().setAttribute('contenteditable', false);
                        }
                    } catch (e) {
                        console.log('Error disabling editor:', e);
                    }
                },
                success: function(response) {
                    if (response.status === 'success') {
                        // Show success message
                        $(".form-message-side-quest").hide().html('<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>' + response.message + '</div>').slideDown("fast");

                        // Reload page after delay
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        // Show error message
                        $(".form-message-side-quest").hide().html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>' + (response.message || 'Terjadi kesalahan saat menyimpan data.') + '</div>').slideDown("fast");
                        $(".btn-apply-side").removeClass("disabled").html('<i class="bi bi-send me-1"></i>Submit Hasil Kerja').attr('disabled', false);

                        // Re-enable editor on error
                        if (tinyMCEInstance && tinymce.get('hasil-editor')) {
                            tinymce.get('hasil-editor').setMode('design');
                        }
                    }
                },
                error: function(xhr, status, error) {

                    $(".btn-apply-side").removeClass("disabled").html('<i class="bi bi-send me-1"></i>Submit Hasil Kerja').attr('disabled', false);
                    $(".form-message-side-quest").hide().html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Terjadi kesalahan sistem. Silakan coba lagi.</div>').slideDown("fast");

                    // Re-enable editor on error - safely
                    try {
                        if (tinyMCEInstance && tinymce.get('hasil-editor') && typeof tinymce.get('hasil-editor').setMode === 'function') {
                            tinymce.get('hasil-editor').setMode('design');
                        } else if (tinyMCEInstance && tinymce.get('hasil-editor')) {
                            tinymce.get('hasil-editor').getBody().setAttribute('contenteditable', true);
                        }
                    } catch (e) {
                        console.log('Error re-enabling editor:', e);
                    }
                }
            });
            return false;
        });

        // Modal cleanup when side quest modal is closed
        $('#sideQuestModal').on('hidden.bs.modal', function() {
            // Clear form messages
            $('.form-message-side-quest').html('').hide();

            // Clean up TinyMCE editor
            setTimeout(() => {
                if (tinymce.get('hasil-editor')) {
                    try {
                        tinymce.remove('#hasil-editor');
                    } catch (e) {
                        console.log('TinyMCE cleanup error:', e);
                    }
                }

                // Remove any fallback notices
                $('#editor-fallback-notice').remove();
            }, 200);
        });

        // Quest Cancellation
        $('.cancel-quest').click(function() {
            var questType = $(this).data('quest-type');
            var submissionId = $(this).data('submission-id');
            var questTitle = $(this).data('quest-title');

            $('#cancel-submission-id').val(submissionId);
            $('#cancel-quest-type').val(questType);
            $('#cancel-quest-title').text(questTitle);
            $('#cancelQuestModal').modal('show');
        });

        $("#form-cancel-quest").submit(function() {
            var form = $(this);
            var questType = $('#cancel-quest-type').val();
            var submissionId = $('#cancel-submission-id').val();

            // Determine the correct endpoint based on quest type
            var url = questType === 'main' ?
                "<?= base_url() ?>profile/cancel_main_quest" :
                "<?= base_url() ?>profile/cancel_side_quest";

            $.ajax({
                type: "POST",
                url: url,
                data: {
                    submission_id: submissionId
                },
                beforeSend: function() {
                    $(".btn-cancel-quest").addClass("disabled").html('<span class="spinner-border spinner-border-sm me-2"></span>Membatalkan...').attr('disabled', true);
                    $(".form-message-cancel").slideUp().html("");
                },
                success: function(response) {
                    var str = response;
                    if (str.indexOf("success") != -1) {
                        $(".form-message-cancel").hide().html(response).slideDown("fast");
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        $(".form-message-cancel").hide().html(response).slideDown("fast");
                        $(".btn-cancel-quest").removeClass("disabled").html('<i class="bi bi-trash me-1"></i>Ya, Batalkan').attr('disabled', false);
                    }
                },
                error: function() {
                    $(".btn-cancel-quest").removeClass("disabled").html('<i class="bi bi-trash me-1"></i>Ya, Batalkan').attr('disabled', false);
                    $(".form-message-cancel").hide().html('<div class="alert alert-danger">Terjadi kesalahan sistem.</div>').slideDown("fast");
                }
            });
            return false;
        });

        // Delete Review Handler
        $(document).on('click', '.delete-review', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent card click event

            var submissionId = $(this).data('submission-id');
            var questTitle = $(this).data('quest-title');
            var submissionTitle = $(this).data('submission-title');
            var reviewCard = $(this).closest('.review-card');

            // Show confirmation dialog
            Swal.fire({
                title: 'Hapus Review?',
                html: `
                    <div class="text-start">
                        <p class="mb-2">Apakah Anda yakin ingin menghapus review ini?</p>
                        <div class="bg-light p-3 rounded mb-3">
                            <strong>Quest:</strong> ${questTitle}<br>
                            <strong>Review:</strong> ${submissionTitle}
                        </div>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Perhatian:</strong> Tindakan ini tidak dapat dibatalkan!
                        </div>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-trash me-1"></i>Ya, Hapus',
                cancelButtonText: '<i class="bi bi-x me-1"></i>Batal',
                customClass: {
                    popup: 'text-start'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    Swal.fire({
                        title: 'Menghapus Review...',
                        text: 'Mohon tunggu sebentar',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // AJAX call to delete review
                    $.ajax({
                        url: '<?= base_url() ?>profile/delete_review',
                        method: 'POST',
                        data: {
                            submission_id: submissionId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    title: 'Berhasil!',
                                    text: response.message,
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    // Remove the review card from DOM with animation
                                    reviewCard.fadeOut(300, function() {
                                        $(this).remove();

                                        // Check if no reviews left
                                        if ($('.review-card').length === 0) {
                                            $('#reviews-grid').html(`
                                                <div class="col-12">
                                                    <div class="text-center py-5">
                                                        <i class="bi bi-collection" style="font-size: 3rem; color: #ccc;"></i>
                                                        <h6 class="text-muted mt-3">Belum ada review</h6>
                                                        <p class="text-muted">Mulai mengerjakan quest Film atau Buku untuk membuat review</p>
                                                        <div class="mt-3">
                                                            <small class="text-muted">Review akan muncul setelah Anda mengerjakan quest:</small>
                                                            <div class="d-flex justify-content-center gap-2 mt-2">
                                                                <span class="badge bg-primary">🎬 Nonton Film</span>
                                                                <span class="badge bg-success">📚 Baca Buku</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            `);
                                        }
                                    });
                                });
                            } else {
                                Swal.fire({
                                    title: 'Gagal!',
                                    text: response.message || 'Terjadi kesalahan saat menghapus review',
                                    icon: 'error'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Delete review error:', error);
                            Swal.fire({
                                title: 'Error!',
                                text: 'Terjadi kesalahan koneksi. Silakan coba lagi.',
                                icon: 'error'
                            });
                        }
                    });
                }
            });
        });

        // Review Card Click Handler (updated to prevent conflict with delete button)
        $(document).on('click', '.review-card', function(e) {
            // Don't trigger if delete button was clicked
            if ($(e.target).hasClass('delete-review') || $(e.target).closest('.delete-review').length > 0) {
                return;
            }
            var submissionId = $(this).data('submission-id');

            // Show loading state
            $('#reviewModal .modal-body').html('<div class="text-center py-5"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-3 text-muted">Loading review details...</p></div>');
            $('#reviewModal').modal('show');

            // AJAX call to get full submission details
            $.ajax({
                url: '<?= base_url() ?>profile/get_review_detail',
                method: 'POST',
                data: {
                    submission_id: submissionId
                },
                success: function(response) {
                    try {
                        var data = JSON.parse(response);

                        if (data.error) {
                            $('#reviewModal .modal-body').html('<div class="text-center py-5"><i class="bi bi-exclamation-triangle text-warning" style="font-size: 3rem;"></i><h6 class="text-muted mt-3">Error loading review</h6><p class="text-muted">' + data.error + '</p></div>');
                            return;
                        }

                        // Restore original modal structure
                        var modalHTML =
                            '<div class="review-image-container text-center mb-4">' +
                            '<img id="review-modal-image" class="img-fluid rounded" ' +
                            '     style="max-height:400px; object-fit:cover; width:100%;" alt="Review Image">' +
                            '</div>' +

                            '<div class="review-content mt-2">' +
                            '<h4 id="review-modal-title" class="mb-1"></h4>' +
                            '<p class="text-muted mb-3" id="review-modal-quest"></p>' +

                            '<div class="review-meta mb-4">' +
                            '<div class="row g-2 align-items-center">' +
                            '<div class="col-auto">' +
                            '<span id="review-modal-status" class="badge"></span>' +
                            '</div>' +
                            '<div class="col">' +
                            '<small class="text-muted">' +
                            '<i class="bi bi-calendar me-1"></i>Submitted: <span id="review-modal-date"></span>' +
                            '</small>' +
                            '<span id="review-modal-approved-date" class="d-block mt-1 text-success" style="display: none!important;">' +
                            '<small><i class="bi bi-check-circle me-1"></i>Approved: ' +
                            '<span id="review-modal-approved-date-text"></span></small>' +
                            '</span>' +
                            '</div>' +
                            '</div>' +
                            '</div>' +

                            '<div class="review-text-content">' +
                            '<h6 class="mb-2"><i class="bi bi-chat-text me-2"></i>Review Content:</h6>' +
                            '<div id="review-modal-content" class="review-text p-3 bg-light rounded" ' +
                            '     style="max-height:320px; overflow-y:auto; line-height:1.6;"></div>' +
                            '</div>' +

                            '<div class="mt-4" id="review-modal-hr-notes-section" style="display:none;">' +
                            '<h6 class="mb-2"><i class="bi bi-person-badge me-2"></i>HR Notes:</h6>' +
                            '<div class="alert alert-info" id="review-modal-hr-notes"></div>' +
                            '</div>' +
                            '</div>';


                        $('#reviewModal .modal-body').html(modalHTML);

                        // Populate modal with data
                        $('#review-modal-title').text(data.submission_title || 'No Title');
                        $('#review-modal-quest').text(data.quest_title || '');

                        // Display rich text content if available, otherwise fallback to plain text
                        var content = '';
                        if (data.hasil_html && data.hasil_html.trim() !== '') {
                            content = data.hasil_html;
                            $('#review-modal-content').removeClass('review-text').addClass('review-html-content');
                        } else {
                            content = (data.hasil || 'No content').replace(/\n/g, '<br>');
                            $('#review-modal-content').removeClass('review-html-content').addClass('review-text');
                        }
                        $('#review-modal-content').html(content);
                        $('#review-modal-date').text(data.submitted_at_formatted || '');

                        // Set status badge
                        var statusClass = getStatusClass(data.status);
                        var statusText = getStatusText(data.status);
                        $('#review-modal-status').removeClass().addClass('badge ' + statusClass).text(statusText);

                        // Handle image - use placehold.co as fallback
                        var modalImageSrc = data.submission_image ?
                            '<?= base_url() ?>assets/uploads/side_quest_user_images/' + data.submission_image :
                            'https://placehold.co/800x400/f0f0f0/666666?text=No+Image';

                        $('#review-modal-image').attr('src', modalImageSrc)
                            .attr('onerror', "this.src='https://placehold.co/800x400/f0f0f0/666666?text=No+Image'");
                        $('.review-image-container').show();

                        // Handle approved date
                        if (data.approved_at_formatted && data.status === 'approved') {
                            $('#review-modal-approved-date-text').text(data.approved_at_formatted);
                            $('#review-modal-approved-date').show();
                        }

                        // Handle HR notes
                        if (data.hr_notes && data.hr_notes.trim() !== '') {
                            $('#review-modal-hr-notes').text(data.hr_notes);
                            $('#review-modal-hr-notes-section').show();
                        }

                    } catch (e) {
                        $('#reviewModal .modal-body').html('<div class="text-center py-5"><i class="bi bi-exclamation-triangle text-danger" style="font-size: 3rem;"></i><h6 class="text-muted mt-3">Error parsing data</h6><p class="text-muted">Please try again later.</p></div>');
                    }
                },
                error: function() {
                    $('#reviewModal .modal-body').html('<div class="text-center py-5"><i class="bi bi-wifi-off text-danger" style="font-size: 3rem;"></i><h6 class="text-muted mt-3">Connection error</h6><p class="text-muted">Please check your internet connection and try again.</p></div>');
                }
            });
        });

        // Edit Review Handler
        $(document).on('click', '.edit-review', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent card click event

            var submissionId = $(this).data('submission-id');
            var questTitle = $(this).data('quest-title');
            var submissionTitle = $(this).data('submission-title');

            // Set modal data
            $('#edit-submission-id').val(submissionId);
            $('#edit-quest-title').text(questTitle);

            // Detect quest type and update labels
            var isFilmQuest = questTitle.toLowerCase().includes('nonton film');
            var isBookQuest = questTitle.toLowerCase().includes('baca buku');

            if (isFilmQuest) {
                $('#edit-title-label').text('Judul Film');
                $('#edit-title-help').text('Masukkan judul film lengkap');
                $('#edit-image-label').text('Poster Film');
                $('#edit-image-help').text('Upload poster film (max 2MB)');
            } else if (isBookQuest) {
                $('#edit-title-label').text('Judul Buku');
                $('#edit-title-help').text('Masukkan judul buku lengkap');
                $('#edit-image-label').text('Cover Buku');
                $('#edit-image-help').text('Upload cover buku (max 2MB)');
            }

            // Clear form
            $('.form-message-edit-review').html('').hide();
            $('#edit_submission_title').val('');
            $('#edit_submission_image').val('');
            $('#current-image-preview').hide();

            // Load existing review data
            $.ajax({
                url: '<?= base_url() ?>profile/get_review_detail',
                method: 'POST',
                data: { submission_id: submissionId },
                success: function(response) {
                    try {
                        var data = JSON.parse(response);
                        if (data.error) {
                            $('.form-message-edit-review').html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>' + data.error + '</div>').slideDown();
                            return;
                        }

                        // Populate form fields
                        $('#edit_submission_title').val(data.submission_title || '');

                        // Set up current image preview
                        if (data.submission_image) {
                            var imageSrc = '<?= base_url() ?>assets/uploads/side_quest_user_images/' + data.submission_image;
                            $('#current-image').attr('src', imageSrc);
                            $('#current-image-preview').show();
                        }

                        // Initialize TinyMCE for edit modal
                        setTimeout(() => {
                            initializeTinyMCEForEditModal(data.hasil_html || data.hasil || '');
                        }, 300);

                        // Show warning for approved reviews
                        if (data.status === 'approved') {
                            $('.form-message-edit-review').html(
                                '<div class="alert alert-warning">' +
                                '<i class="bi bi-exclamation-triangle me-2"></i>' +
                                '<strong>Perhatian:</strong> Review ini sudah disetujui. Jika Anda mengedit, status akan dikembalikan ke pending untuk review ulang HR.' +
                                '</div>'
                            ).show();
                        }

                        // Show modal
                        $('#editReviewModal').modal('show');

                    } catch (e) {
                        console.error('Error parsing review data:', e);
                        $('.form-message-edit-review').html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Error loading review data</div>').slideDown();
                    }
                },
                error: function() {
                    $('.form-message-edit-review').html('<div class="alert alert-danger"><i class="bi bi-wifi-off me-2"></i>Connection error. Please try again.</div>').slideDown();
                }
            });
        });

        // Initialize TinyMCE for Edit Modal
        function initializeTinyMCEForEditModal(content) {
            // Clean up any existing instance
            if (tinymce.get('edit-hasil-editor')) {
                try {
                    tinymce.remove('#edit-hasil-editor');
                } catch (e) {
                    console.log('TinyMCE cleanup error:', e);
                }
            }

            // Initialize TinyMCE
            tinymce.init({
                selector: '#edit-hasil-editor',
                height: 300,
                menubar: false,
                plugins: [
                    'advlist autolink lists link image charmap print preview anchor',
                    'searchreplace visualblocks code fullscreen',
                    'insertdatetime media table paste code help wordcount'
                ],
                toolbar: 'undo redo | formatselect | bold italic backcolor | \
                         alignleft aligncenter alignright alignjustify | \
                         bullist numlist outdent indent | link image | help',
                content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
                setup: function (editor) {
                    editor.on('init', function () {
                        editor.setContent(content);
                    });
                },
                init_instance_callback: function(editor) {
                    console.log('TinyMCE Edit Modal initialized');
                }
            });
        }

        // Edit Review Form Submit
        $('#form-edit-review').on('submit', function(e) {
            e.preventDefault();

            var formData = new FormData(this);

            // Get content from TinyMCE
            var tinyMCEEditInstance = tinymce.get('edit-hasil-editor');
            var hasilHtml = '';
            var hasilText = '';

            try {
                if (tinyMCEEditInstance && tinyMCEEditInstance.getContent) {
                    hasilHtml = tinyMCEEditInstance.getContent();

                    // Convert HTML to plain text for validation
                    try {
                        var tempDiv = document.createElement('div');
                        tempDiv.innerHTML = hasilHtml;
                        hasilText = tempDiv.textContent || tempDiv.innerText || '';
                    } catch (e) {
                        hasilText = $('<div>').html(hasilHtml).text().trim();
                    }
                } else {
                    // Fallback to textarea value
                    hasilText = $('#edit-hasil-editor').val().trim();
                    hasilHtml = hasilText.replace(/\n/g, '<br>');
                }

                // Ensure we have valid content
                if (!hasilText && hasilHtml) {
                    hasilText = $('<div>').html(hasilHtml).text().trim();
                }

            } catch (e) {
                // Emergency fallback
                hasilText = $('#edit_hasil').val() || $('#edit-hasil-editor').val() || 'Content extraction failed';
                hasilHtml = '<p>' + hasilText.replace(/\n/g, '<br>') + '</p>';
            }

            // Validate content
            if (!hasilText || hasilText.length < 10) {
                $('.form-message-edit-review').html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Hasil kerja harus diisi minimal 10 karakter!</div>').slideDown();
                return false;
            }

            // Validate title (required for film/book quests)
            if (!$('#edit_submission_title').val().trim()) {
                $('.form-message-edit-review').html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Judul harus diisi!</div>').slideDown();
                return false;
            }

            // Validate file upload if present
            var fileInput = $('#edit_submission_image')[0];
            if (fileInput.files.length > 0) {
                var file = fileInput.files[0];
                var maxSize = 2 * 1024 * 1024; // 2MB in bytes

                if (file.size > maxSize) {
                    $('.form-message-edit-review').html('<div class="alert alert-danger"><i class="bi bi-file-earmark-x me-2"></i>Ukuran file terlalu besar! Maksimal 2MB.</div>').slideDown();
                    return false;
                }

                // Check file type
                var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    $('.form-message-edit-review').html('<div class="alert alert-danger"><i class="bi bi-file-earmark-x me-2"></i>Tipe file tidak didukung! Gunakan JPEG, PNG, GIF, atau WEBP.</div>').slideDown();
                    return false;
                }
            }

            // Add HTML content to form data
            formData.append('hasil_html', hasilHtml);
            formData.set('hasil', hasilText);

            // Submit form
            $.ajax({
                url: '<?= base_url() ?>profile/update_review',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                beforeSend: function() {
                    $('.btn-update-review').addClass('disabled').html('<span class="spinner-border spinner-border-sm me-2"></span>Updating...').attr('disabled', true);
                    $('.form-message-edit-review').slideUp().html('');
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        $('.form-message-edit-review').html('<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>' + (response.message || 'Review berhasil diupdate!') + '</div>').slideDown();

                        setTimeout(function() {
                            $('#editReviewModal').modal('hide');
                            window.location.reload();
                        }, 2000);
                    } else {
                        // Show error message
                        $('.form-message-edit-review').html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>' + (response.message || 'Terjadi kesalahan saat mengupdate review.') + '</div>').slideDown();
                        $('.btn-update-review').removeClass('disabled').html('<i class="bi bi-check-lg me-1"></i>Update Review').attr('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    $('.btn-update-review').removeClass('disabled').html('<i class="bi bi-check-lg me-1"></i>Update Review').attr('disabled', false);
                    $('.form-message-edit-review').html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Terjadi kesalahan sistem. Silakan coba lagi.</div>').slideDown();
                }
            });

            return false;
        });

        // Edit Modal cleanup when closed
        $('#editReviewModal').on('hidden.bs.modal', function() {
            // Clear form messages
            $('.form-message-edit-review').html('').hide();

            // Clean up TinyMCE editor
            setTimeout(() => {
                if (tinymce.get('edit-hasil-editor')) {
                    try {
                        tinymce.remove('#edit-hasil-editor');
                    } catch (e) {
                        console.log('TinyMCE cleanup error:', e);
                    }
                }
            }, 200);
        });

        // Helper functions for status
        function getStatusClass(status) {
            switch (status) {
                case 'pending':
                    return 'bg-warning';
                case 'approved':
                    return 'bg-success';
                case 'denied':
                    return 'bg-danger';
                default:
                    return 'bg-secondary';
            }
        }

        function getStatusText(status) {
            switch (status) {
                case 'pending':
                    return 'Pending';
                case 'approved':
                    return 'Approved';
                case 'denied':
                    return 'Denied';
                default:
                    return 'Unknown';
            }
        }


    });


    // Summernote editor initialized above in sideQuestModal shown event
</script>

<!-- TinyMCE verification and fallback -->
<script>
    // Ensure TinyMCE is loaded properly
    $(document).ready(function() {
        // Check if TinyMCE is loaded
        if (typeof tinymce === 'undefined') {

            // Load TinyMCE from CDN
            $.getScript('https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js')
                .done(function() {
                    // Trigger an event that can be listened for elsewhere
                    $(document).trigger('tinymce:loaded');
                })
                .fail(function(jqxhr, settings, exception) {

                    // Try loading from a different CDN as fallback
                    $.getScript('https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.4.2/tinymce.min.js')
                        .done(function() {
                            $(document).trigger('tinymce:loaded');
                        })
                        .fail(function() {
                            $(document).trigger('tinymce:failed');
                        });
                });
        } else {
            $(document).trigger('tinymce:loaded');
        }
    });
</script>

<!-- Profile Tab Management Script -->
<script>
$(document).ready(function() {
    // Handle tab switching to show/hide Activities & Reviews Card
    $('#profile-tabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).attr("href"); // activated tab

        if (target === '#milestone-content') {
            // Hide Activities & Reviews Card when milestone tab is active
            $('#activities-reviews-card').hide();
        } else {
            // Show Activities & Reviews Card when profile tab is active
            $('#activities-reviews-card').show();
        }
    });

    // Initially hide Activities & Reviews Card if milestone tab is active on page load
    if ($('#milestone-tab').hasClass('active')) {
        $('#activities-reviews-card').hide();
    }
});
</script>

<!-- Profile CSS -->
<link rel="stylesheet" href="<?= base_url() ?>application/views/profile/profile.css">
<link rel="stylesheet" href="<?= base_url() ?>assets/css/profile-rich-editor.css">

<!-- Additional CSS for TinyMCE editor -->
<style>
    /* Editor loading indicators */
    #editor-loading,
    #editor-init-loading {
        background-color: #f8f9fa;
        border-radius: 8px;
        border: 1px dashed #d9d9d9;
        margin-bottom: 15px;
    }

    #editor-fallback-notice {
        border-left: 4px solid #faad14;
    }

    /* Fix form visibility within modal */
    #sideQuestModal .modal-body form {
        display: block !important;
        visibility: visible !important;
    }

    #sideQuestModal .modal-body .mb-3 {
        display: block !important;
        margin-bottom: 1rem !important;
    }

    /* Ensure form elements are visible */
    #sideQuestModal #conditional-fields,
    #sideQuestModal .form-label,
    #sideQuestModal .form-control,
    #sideQuestModal .form-text {
        display: block !important;
        visibility: visible !important;
        padding: 5px !important;
    }

    /* TinyMCE editor styling */
    #hasil-editor {
        visibility: visible !important;
        border-radius: 8px !important;
    }

    /* TinyMCE in modal specific styling */
    .tox-tinymce {
        border: 1px solid #91d5ff !important;
        border-radius: 8px !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
    }

    .tox .tox-toolbar,
    .tox .tox-toolbar__overflow,
    .tox .tox-toolbar__primary {
        background-color: #f8f9fa !important;
        border-bottom: 1px solid #dee2e6 !important;
    }

    .tox .tox-edit-area__iframe {
        background-color: white !important;
    }

    .tox .tox-statusbar {
        border-top: 1px solid #dee2e6 !important;
    }

    /* Fix modal z-index issues for TinyMCE */
    #sideQuestModal {
        z-index: 1055 !important;
    }

    #sideQuestModal .modal-content {
        z-index: 1056 !important;
    }

    .tox-tinymce-aux {
        z-index: 1060 !important;
    }

    /* Improve focus state */
    .tox-edit-focus {
        border-color: #1890ff !important;
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2) !important;
    }

    /* Mobile styling */
    @media (max-width: 767.98px) {
        .tox-toolbar__group {
            flex-wrap: wrap !important;
        }
    }
</style>