<!-- Milestone Widget -->
<div class="card mb-4" style="background: linear-gradient(135deg, #fff7e6 0%, #e6f7ff 100%); border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
    <div class="card-header" style="background: transparent; border-bottom: 1px solid rgba(250, 173, 20, 0.2);">
        <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">
            <i class="bi bi-star-fill me-2" style="color: #faad14;"></i>Milestone Achievements
        </h5>
    </div>
    <div class="card-body">
        <?php if (!empty($milestone_data)): ?>
            <!-- Milestone Achievements Slider -->
            <div class="milestone-slider-container position-relative">
                <!-- Navigation Arrows -->
                <button class="slider-nav slider-nav-prev" id="milestone-prev">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <button class="slider-nav slider-nav-next" id="milestone-next">
                    <i class="bi bi-chevron-right"></i>
                </button>
                
                <!-- Slider Wrapper -->
                <div class="milestone-slider-wrapper" id="milestone-slider">
                    <div class="milestone-slider-track" id="milestone-track">
                        <?php foreach ($milestone_data as $index => $milestone): ?>
                            <?php
                            $status = $milestone['status'];
                            $is_achieved = $status == 'achieved';
                            $is_approved = $status == 'approved';
                            $is_pending = $status == 'pending';
                            $is_achievable = $milestone['is_achievable'] && $status == 'available';
                            $progress_percentage = $milestone['progress_percentage'];
                            
                            // Determine card styling based on new status
                            if ($is_achieved) {
                                $card_bg = 'linear-gradient(135deg, #f6ffed, #d9f7be)';
                                $border_color = '#52c41a';
                                $progress_color = '#52c41a';
                                $badge_style = 'background-color: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f;';
                            } elseif ($is_approved) {
                                $card_bg = 'linear-gradient(135deg, #e6f7ff, #bae7ff)';
                                $border_color = '#1890ff';
                                $progress_color = '#1890ff';
                                $badge_style = 'background-color: #e6f7ff; color: #1890ff; border: 1px solid #91d5ff;';
                            } elseif ($is_pending) {
                                $card_bg = 'linear-gradient(135deg, #fff7e6, #ffe7ba)';
                                $border_color = '#fa8c16';
                                $progress_color = '#fa8c16';
                                $badge_style = 'background-color: #fff7e6; color: #fa8c16; border: 1px solid #ffd591;';
                            } elseif ($is_achievable) {
                                $card_bg = 'linear-gradient(135deg, #fffbe6, #fff7e6)';
                                $border_color = '#faad14';
                                $progress_color = '#faad14';
                                $badge_style = 'background-color: #fffbe6; color: #faad14; border: 1px solid #ffe58f;';
                            } else {
                                $card_bg = 'linear-gradient(135deg, #fafafa, #f5f5f5)';
                                $border_color = '#d9d9d9';
                                $progress_color = '#d9d9d9';
                                $badge_style = 'background-color: #fafafa; color: #8c8c8c; border: 1px solid #d9d9d9;';
                            }
                            
                            // Get milestone type display
                            $type_display = '';
                            switch ($milestone['milestone_type']) {
                                case 'quest_count':
                                    $type_display = 'Quest Count';
                                    break;
                                case 'total_points':
                                    $type_display = 'Total Points';
                                    break;
                                case 'monthly_points':
                                    $type_display = 'Monthly Points';
                                    break;
                            }
                            ?>
                            <div class="milestone-slide">
                                <div class="card h-100 milestone-card-clickable" 
                                     style="background: <?= $card_bg ?>; border: 2px solid <?= $border_color ?>; border-radius: 12px; position: relative; overflow: hidden; cursor: pointer; min-height: 400px;"
                                     data-milestone-id="<?= $milestone['id'] ?>"
                                     data-milestone-title="<?= $milestone['title'] ?>"
                                     data-milestone-description="<?= htmlspecialchars($milestone['description']) ?>"
                                     data-milestone-type="<?= $milestone['milestone_type'] ?>"
                                     data-milestone-target="<?= $milestone['target_value'] ?>"
                                     data-milestone-current="<?= $milestone['current_value'] ?>"
                                     data-milestone-progress="<?= $milestone['progress_percentage'] ?>"
                                     data-milestone-reward-points="<?= $milestone['reward_points'] ?>"
                                     data-milestone-reward-desc="<?= htmlspecialchars($milestone['reward_description']) ?>"
                                     data-milestone-status="<?= $milestone['status'] ?>"
                                     data-milestone-achievable="<?= $is_achievable ? 'true' : 'false' ?>"
                                     data-milestone-image="<?= $milestone['gambar_animasi'] ?? '' ?>"
                                     data-milestone-repeatable="<?= $milestone['is_repeatable'] ? 'true' : 'false' ?>">
                                    <!-- Achievement Status Badge -->
                                    <div style="position: absolute; top: 10px; right: 10px; z-index: 3;">
                                        <?php if ($is_achieved): ?>
                                            <span class="badge" style="<?= $badge_style ?>">
                                                <i class="bi bi-check-circle-fill me-1"></i>Delivered ✓
                                            </span>
                                        <?php elseif ($is_approved): ?>
                                            <span class="badge" style="<?= $badge_style ?>">
                                                <i class="bi bi-clock-fill me-1"></i>Awaiting Delivery
                                            </span>
                                        <?php elseif ($is_pending): ?>
                                            <span class="badge" style="<?= $badge_style ?>">
                                                <i class="bi bi-hourglass-split me-1"></i>Pending Approval
                                            </span>
                                        <?php elseif ($is_achievable): ?>
                                            <span class="badge" style="<?= $badge_style ?>">
                                                <i class="bi bi-exclamation-circle-fill me-1"></i>Ready to Claim!
                                            </span>
                                        <?php else: ?>
                                            <span class="badge" style="<?= $badge_style ?>">
                                                <i class="bi bi-hourglass-split me-1"></i>In Progress
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="card-body d-flex flex-column" style="position: relative; z-index: 2;">
                                        <!-- Animation Image Display -->
                                        <?php if (!empty($milestone['gambar_animasi'])): ?>
                                            <div class="text-center mb-3">
                                                <div class="milestone-image-container" style="position: relative; display: inline-block;">
                                                    <img src="<?= base_url() ?>assets/uploads/milestone_animations/<?= $milestone['gambar_animasi'] ?>" 
                                                         alt="<?= $milestone['title'] ?>" 
                                                         class="milestone-animation-image"
                                                         style="width: 80px; height: 80px; object-fit: cover; border-radius: 12px; 
                                                                border: 3px solid <?= $border_color ?>; 
                                                                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                                                                transition: transform 0.3s ease;">
                                                    <?php if ($is_achieved): ?>
                                                        <div style="position: absolute; top: -8px; right: -8px; 
                                                                   background: <?= $progress_color ?>; color: white; 
                                                                   border-radius: 50%; width: 24px; height: 24px; 
                                                                   display: flex; align-items: center; justify-content: center; 
                                                                   font-size: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                                                            <i class="bi bi-check"></i>
                                                        </div>
                                                    <?php elseif ($is_achievable): ?>
                                                        <div style="position: absolute; top: -8px; right: -8px; 
                                                                   background: <?= $progress_color ?>; color: white; 
                                                                   border-radius: 50%; width: 24px; height: 24px; 
                                                                   display: flex; align-items: center; justify-content: center; 
                                                                   font-size: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                                                                   animation: pulse 1.5s infinite;">
                                                            <i class="bi bi-star-fill"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <!-- Placeholder for milestones without images -->
                                            <div class="text-center mb-3">
                                                <div class="milestone-placeholder" style="width: 80px; height: 80px; 
                                                           border-radius: 12px; border: 3px solid <?= $border_color ?>; 
                                                           background: <?= $card_bg ?>; display: inline-flex; 
                                                           align-items: center; justify-content: center;
                                                           box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                                                    <i class="bi bi-star-fill" style="font-size: 2rem; color: <?= $progress_color ?>;"></i>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Milestone Title and Description -->
                                        <h6 class="card-title mb-2" style="color: rgba(0, 0, 0, 0.85); font-weight: 600;">
                                            <?= $milestone['title'] ?>
                                        </h6>
                                        <p class="card-text text-muted small mb-3 flex-grow-1">
                                            <?= substr($milestone['description'], 0, 80) ?><?= strlen($milestone['description']) > 80 ? '...' : '' ?>
                                        </p>
                                        
                                        <!-- Milestone Type -->
                                        <div class="mb-3">
                                            <span class="badge" style="<?= $badge_style ?> font-size: 0.75rem;">
                                                <?= $type_display ?>
                                            </span>
                                        </div>
                                        
                                        <!-- Progress Circle -->
                                        <div class="text-center mb-3">
                                            <div class="progress-circle" style="position: relative; display: inline-block;">
                                                <svg width="60" height="60" style="transform: rotate(-90deg);">
                                                    <circle cx="30" cy="30" r="25" fill="none" stroke="#f0f0f0" stroke-width="4"></circle>
                                                    <circle cx="30" cy="30" r="25" fill="none" stroke="<?= $progress_color ?>" stroke-width="4"
                                                            stroke-dasharray="<?= 2 * pi() * 25 ?>" 
                                                            stroke-dashoffset="<?= 2 * pi() * 25 * (1 - $progress_percentage / 100) ?>"
                                                            style="transition: stroke-dashoffset 0.5s ease;"></circle>
                                                </svg>
                                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                                                    <div class="fw-bold" style="color: <?= $progress_color ?>; font-size: 0.8rem;">
                                                        <?= round($progress_percentage) ?>%
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Progress Values -->
                                        <div class="text-center mb-3">
                                            <div class="fw-bold" style="color: rgba(0, 0, 0, 0.85); font-size: 0.9rem;">
                                                <?= number_format($milestone['current_value']) ?> / <?= number_format($milestone['target_value']) ?>
                                            </div>
                                            <small class="text-muted">Progress</small>
                                        </div>
                                        
                                        <!-- Reward Information -->
                                        <div class="mb-3 flex-grow-1">
                                            <small class="text-muted">Reward:</small>
                                            <div class="fw-bold text-success" style="font-size: 0.85rem;">
                                                +<?= $milestone['reward_points'] ?> bonus points
                                            </div>
                                            <small class="text-muted">
                                                <?= substr($milestone['reward_description'], 0, 50) ?><?= strlen($milestone['reward_description']) > 50 ? '...' : '' ?>
                                            </small>
                                        </div>
                                        
                                        <!-- Action Button -->
                                        <div class="text-center mt-auto">
                                            <?php if ($is_achieved): ?>
                                                <button class="btn btn-success btn-sm" style="cursor: not-allowed;" disabled>
                                                    <i class="bi bi-check-circle me-1"></i>Delivered ✓
                                                </button>
                                                <?php if (!empty($milestone['proof_image'])): ?>
                                                    <small class="d-block text-muted mt-1">
                                                        <i class="bi bi-image me-1"></i>Proof available
                                                    </small>
                                                <?php endif; ?>
                                            <?php elseif ($is_approved): ?>
                                                <button class="btn btn-info btn-sm" style="cursor: not-allowed;" disabled>
                                                    <i class="bi bi-clock me-1"></i>Awaiting Delivery
                                                </button>
                                                <small class="d-block text-muted mt-1">Points deducted</small>
                                            <?php elseif ($is_pending): ?>
                                                <button class="btn btn-warning btn-sm" style="cursor: not-allowed;" disabled>
                                                    <i class="bi bi-hourglass-split me-1"></i>Pending Approval
                                                </button>
                                                <small class="d-block text-muted mt-1">Please wait for admin approval</small>
                                            <?php elseif ($is_achievable): ?>
                                                <button class="btn btn-warning btn-sm claim-milestone" 
                                                        data-milestone-id="<?= $milestone['id'] ?>"
                                                        data-milestone-title="<?= $milestone['title'] ?>"
                                                        data-reward-points="<?= $milestone['reward_points'] ?>">
                                                    <i class="bi bi-star-fill me-1"></i>Claim Milestone
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-outline-secondary btn-sm" disabled>
                                                    <i class="bi bi-hourglass-split me-1"></i>Keep Going!
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Slide Indicators (Dots) -->
                <div class="milestone-slider-dots" id="milestone-dots">
                    <?php for ($i = 0; $i < count($milestone_data); $i++): ?>
                        <span class="slider-dot <?= $i === 0 ? 'active' : '' ?>" data-slide="<?= $i ?>"></span>
                    <?php endfor; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-star" style="font-size: 4rem; color: #d9d9d9;"></i>
                <h6 class="mt-3 mb-2" style="color: rgba(0, 0, 0, 0.45);">No Milestones Available</h6>
                <p style="color: rgba(0, 0, 0, 0.45);">Milestone achievements will appear here when they are created by HR.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Milestone Claim Modal -->
<div class="modal fade" id="milestoneClaimModal" tabindex="-1" aria-labelledby="milestoneClaimModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="milestoneClaimModalLabel">
                    <i class="bi bi-star-fill me-2" style="color: #faad14;"></i>Claim Milestone
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-message-milestone"></div>
                <div class="text-center mb-3">
                    <div class="milestone-celebration" style="font-size: 4rem; color: #faad14;">
                        <i class="bi bi-trophy-fill"></i>
                    </div>
                </div>
                <h6 class="text-center mb-3">Selamat! Anda telah mencapai milestone:</h6>
                <div class="text-center">
                    <div class="fw-bold mb-2" id="milestone-claim-title" style="color: rgba(0, 0, 0, 0.85); font-size: 1.1rem;"></div>
                    <div class="alert alert-warning">
                        <i class="bi bi-gift me-2"></i>
                        Anda akan mendapat <strong id="milestone-reward-points">0</strong> bonus poin!
                    </div>
                </div>
                <form id="form-milestone-claim">
                    <input type="hidden" id="milestone-claim-id" name="milestone_id">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" form="form-milestone-claim" class="btn btn-warning btn-claim-milestone">
                    <i class="bi bi-star-fill me-1"></i>Claim Milestone
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Milestone Detail Modal -->
<div class="modal fade" id="milestoneDetailModal" tabindex="-1" aria-labelledby="milestoneDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="milestoneDetailModalLabel">
                    <i class="bi bi-star-fill me-2" style="color: #faad14;"></i>
                    <span id="detail-milestone-title">Milestone Details</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Left Column - Image and Progress -->
                    <div class="col-md-5">
                        <!-- Milestone Image -->
                        <div class="text-center mb-4">
                            <div id="detail-milestone-image-container" class="milestone-detail-image-container">
                                <!-- Image will be inserted here -->
                            </div>
                        </div>
                        
                        <!-- Progress Circle -->
                        <div class="text-center mb-4">
                            <div class="progress-circle-large" style="position: relative; display: inline-block;">
                                <svg width="120" height="120" style="transform: rotate(-90deg);">
                                    <circle cx="60" cy="60" r="50" fill="none" stroke="#f0f0f0" stroke-width="8"></circle>
                                    <circle id="detail-progress-circle" cx="60" cy="60" r="50" fill="none" stroke="#52c41a" stroke-width="8"
                                            stroke-dasharray="314.16" stroke-dashoffset="314.16"
                                            style="transition: stroke-dashoffset 0.8s ease;"></circle>
                                </svg>
                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
                                    <div id="detail-progress-percentage" class="fw-bold" style="color: #52c41a; font-size: 1.5rem;">0%</div>
                                    <small class="text-muted">Complete</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Status Badge -->
                        <div class="text-center">
                            <span id="detail-status-badge" class="badge bg-secondary">Status</span>
                        </div>
                    </div>
                    
                    <!-- Right Column - Details -->
                    <div class="col-md-7">
                        <!-- Description -->
                        <div class="mb-4">
                            <h6 class="text-muted mb-2">Description</h6>
                            <p id="detail-milestone-description" class="text-muted">Milestone description will appear here...</p>
                        </div>
                        
                        <!-- Type and Target -->
                        <div class="row mb-4">
                            <div class="col-6">
                                <div class="p-3" style="background-color: #e6f7ff; border-radius: 8px; border: 1px solid #91d5ff;">
                                    <h6 class="mb-1" style="color: #1890ff;">
                                        <i class="bi bi-target me-1"></i>Type
                                    </h6>
                                    <div id="detail-milestone-type" class="fw-bold" style="color: rgba(0, 0, 0, 0.85);">Quest Count</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3" style="background-color: #f6ffed; border-radius: 8px; border: 1px solid #b7eb8f;">
                                    <h6 class="mb-1" style="color: #52c41a;">
                                        <i class="bi bi-bullseye me-1"></i>Target
                                    </h6>
                                    <div id="detail-milestone-target" class="fw-bold" style="color: rgba(0, 0, 0, 0.85);">0</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Progress Values -->
                        <div class="mb-4">
                            <div class="p-3" style="background-color: #fff7e6; border-radius: 8px; border: 1px solid #ffd591;">
                                <h6 class="mb-2" style="color: #fa8c16;">
                                    <i class="bi bi-graph-up me-1"></i>Progress
                                </h6>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span id="detail-current-value" class="fw-bold" style="font-size: 1.2rem; color: rgba(0, 0, 0, 0.85);">0</span>
                                        <span class="text-muted">/ </span>
                                        <span id="detail-target-value" class="text-muted">0</span>
                                    </div>
                                    <div>
                                        <span id="detail-progress-percent" class="badge bg-warning">0%</span>
                                    </div>
                                </div>
                                <div class="progress mt-2" style="height: 8px;">
                                    <div id="detail-progress-bar" class="progress-bar bg-warning" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Reward Information -->
                        <div class="mb-4">
                            <div class="p-3" style="background-color: #fffbe6; border-radius: 8px; border: 1px solid #ffe58f;">
                                <h6 class="mb-2" style="color: #faad14;">
                                    <i class="bi bi-gift me-1"></i>Reward
                                </h6>
                                <div class="d-flex align-items-start">
                                    <div class="me-3">
                                        <span id="detail-reward-points" class="badge bg-warning fw-bold" style="font-size: 1rem;">0 Points</span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <p id="detail-reward-description" class="mb-0 small text-muted">Reward description will appear here...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Repeatable Info -->
                        <div id="detail-repeatable-info" class="mb-3" style="display: none;">
                            <div class="alert alert-info">
                                <i class="bi bi-arrow-repeat me-2"></i>
                                <strong>Repeatable Milestone:</strong> This milestone can be achieved multiple times.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button id="detail-claim-button" type="button" class="btn btn-warning" style="display: none;">
                    <i class="bi bi-star-fill me-1"></i>Claim Milestone
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.milestone-celebration {
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-10px);
    }
    60% {
        transform: translateY(-5px);
    }
}

.progress-circle:hover {
    transform: scale(1.05);
    transition: transform 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    transition: transform 0.3s ease;
}

.milestone-card-clickable {
    transition: all 0.3s ease;
}

.milestone-card-clickable:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
}

.milestone-detail-image-container img {
    max-width: 150px;
    max-height: 150px;
    object-fit: cover;
    border-radius: 12px;
    border: 3px solid #91d5ff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.milestone-detail-placeholder {
    width: 150px;
    height: 150px;
    border-radius: 12px;
    border: 3px solid #d9d9d9;
    background: #fafafa;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.progress-circle-large:hover {
    transform: scale(1.05);
    transition: transform 0.3s ease;
}

.milestone-animation-image:hover {
    transform: scale(1.1);
}

@keyframes pulse {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.1);
        opacity: 0.8;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

/* Milestone Slider Styles */
.milestone-slider-container {
    padding: 20px 0;
    overflow: hidden;
}

.milestone-slider-wrapper {
    overflow: hidden;
    margin: 0 60px;
    border-radius: 12px;
}

.milestone-slider-track {
    display: flex;
    transition: transform 0.4s ease-in-out;
    width: fit-content;
}

.milestone-slide {
    flex: 0 0 auto;
    width: 320px;
    margin-right: 20px;
}

.milestone-slide:last-child {
    margin-right: 0;
}

/* Navigation Arrows */
.slider-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255, 255, 255, 0.9);
    border: 2px solid #1890ff;
    color: #1890ff;
    border-radius: 50%;
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    cursor: pointer;
    transition: all 0.3s ease;
    z-index: 10;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.slider-nav:hover {
    background: #1890ff;
    color: white;
    transform: translateY(-50%) scale(1.1);
    box-shadow: 0 6px 20px rgba(24, 144, 255, 0.3);
}

.slider-nav:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: translateY(-50%);
}

.slider-nav:disabled:hover {
    background: rgba(255, 255, 255, 0.9);
    color: #1890ff;
    transform: translateY(-50%);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.slider-nav-prev {
    left: 10px;
}

.slider-nav-next {
    right: 10px;
}

/* Slider Dots */
.milestone-slider-dots {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 20px;
    padding: 0 20px;
}

.slider-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #d9d9d9;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.slider-dot:hover {
    background: #91d5ff;
    transform: scale(1.2);
}

.slider-dot.active {
    background: #1890ff;
    border-color: #91d5ff;
    transform: scale(1.3);
    box-shadow: 0 2px 8px rgba(24, 144, 255, 0.3);
}

/* Responsive Design */
@media (max-width: 768px) {
    .milestone-slider-wrapper {
        margin: 0 50px;
    }
    
    .milestone-slide {
        width: 280px;
        margin-right: 15px;
    }
    
    .slider-nav {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
    
    .slider-nav-prev {
        left: 5px;
    }
    
    .slider-nav-next {
        right: 5px;
    }
}

@media (max-width: 576px) {
    .milestone-slider-wrapper {
        margin: 0 45px;
    }
    
    .milestone-slide {
        width: 260px;
        margin-right: 10px;
    }
    
    .slider-nav {
        width: 35px;
        height: 35px;
        font-size: 0.9rem;
    }
    
    .milestone-slider-dots {
        gap: 6px;
    }
    
    .slider-dot {
        width: 10px;
        height: 10px;
    }
}

/* Auto-scroll effect for single slide */
@media (min-width: 992px) {
    .milestone-slider-wrapper.single-slide {
        margin: 0 auto;
        max-width: 340px;
    }
    
    .milestone-slider-wrapper.single-slide .milestone-slide {
        width: 100%;
        margin-right: 0;
    }
}
</style>

<script>
$(document).ready(function() {
    // Milestone Slider Functionality
    const milestoneSlider = {
        currentSlide: 0,
        totalSlides: $('.milestone-slide').length,
        slideWidth: 320, // Base slide width
        slideGap: 20,    // Gap between slides
        
        init: function() {
            if (this.totalSlides === 0) return;
            
            this.updateSlideWidth();
            this.updateNavigation();
            this.attachEvents();
            this.handleResponsive();
            
            // Auto-play for demonstration (optional)
            // this.startAutoPlay();
        },
        
        updateSlideWidth: function() {
            // Update slide width based on screen size
            if (window.innerWidth <= 576) {
                this.slideWidth = 260;
                this.slideGap = 10;
            } else if (window.innerWidth <= 768) {
                this.slideWidth = 280;
                this.slideGap = 15;
            } else {
                this.slideWidth = 320;
                this.slideGap = 20;
            }
        },
        
        moveToSlide: function(slideIndex) {
            if (slideIndex < 0 || slideIndex >= this.totalSlides) return;
            
            this.currentSlide = slideIndex;
            const offset = -(slideIndex * (this.slideWidth + this.slideGap));
            
            $('#milestone-track').css('transform', `translateX(${offset}px)`);
            this.updateDots();
            this.updateNavigation();
        },
        
        nextSlide: function() {
            if (this.currentSlide < this.totalSlides - 1) {
                this.moveToSlide(this.currentSlide + 1);
            }
        },
        
        prevSlide: function() {
            if (this.currentSlide > 0) {
                this.moveToSlide(this.currentSlide - 1);
            }
        },
        
        updateDots: function() {
            $('.slider-dot').removeClass('active');
            $(`.slider-dot[data-slide="${this.currentSlide}"]`).addClass('active');
        },
        
        updateNavigation: function() {
            // Update previous button
            if (this.currentSlide === 0) {
                $('#milestone-prev').prop('disabled', true);
            } else {
                $('#milestone-prev').prop('disabled', false);
            }
            
            // Update next button
            if (this.currentSlide >= this.totalSlides - 1) {
                $('#milestone-next').prop('disabled', true);
            } else {
                $('#milestone-next').prop('disabled', false);
            }
        },
        
        attachEvents: function() {
            const self = this;
            
            // Navigation arrows
            $('#milestone-prev').on('click', function() {
                self.prevSlide();
            });
            
            $('#milestone-next').on('click', function() {
                self.nextSlide();
            });
            
            // Dot navigation
            $('.slider-dot').on('click', function() {
                const slideIndex = parseInt($(this).data('slide'));
                self.moveToSlide(slideIndex);
            });
            
            // Keyboard navigation
            $(document).on('keydown', function(e) {
                if ($('#milestoneDetailModal').hasClass('show') || $('#milestoneClaimModal').hasClass('show')) {
                    return; // Don't interfere with modals
                }
                
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    self.prevSlide();
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    self.nextSlide();
                }
            });
            
            // Touch/swipe support for mobile
            let startX = 0;
            let currentX = 0;
            let isDragging = false;
            
            $('#milestone-slider').on('touchstart', function(e) {
                startX = e.originalEvent.touches[0].clientX;
                isDragging = true;
            });
            
            $('#milestone-slider').on('touchmove', function(e) {
                if (!isDragging) return;
                currentX = e.originalEvent.touches[0].clientX;
                e.preventDefault();
            });
            
            $('#milestone-slider').on('touchend', function(e) {
                if (!isDragging) return;
                isDragging = false;
                
                const diffX = startX - currentX;
                const threshold = 50; // Minimum distance for swipe
                
                if (Math.abs(diffX) > threshold) {
                    if (diffX > 0) {
                        self.nextSlide(); // Swipe left - go to next
                    } else {
                        self.prevSlide(); // Swipe right - go to previous
                    }
                }
            });
        },
        
        handleResponsive: function() {
            const self = this;
            
            // Handle window resize
            $(window).on('resize', function() {
                self.updateSlideWidth();
                self.moveToSlide(self.currentSlide); // Recalculate position
                
                // Handle single slide layout
                if (self.totalSlides === 1) {
                    $('#milestone-slider').addClass('single-slide');
                    $('.slider-nav, .milestone-slider-dots').hide();
                } else {
                    $('#milestone-slider').removeClass('single-slide');
                    $('.slider-nav, .milestone-slider-dots').show();
                }
            }).trigger('resize');
        },
        
        startAutoPlay: function(interval = 5000) {
            const self = this;
            
            setInterval(function() {
                // Only auto-play if not hovering and not at the end
                if (!$('.milestone-slider-container:hover').length) {
                    if (self.currentSlide >= self.totalSlides - 1) {
                        self.moveToSlide(0); // Reset to beginning
                    } else {
                        self.nextSlide();
                    }
                }
            }, interval);
        }
    };
    
    // Initialize the slider
    milestoneSlider.init();
    
    // Milestone Card Click - Show Detail Modal
    $('.milestone-card-clickable').click(function(e) {
        // Prevent modal from opening if claim button was clicked
        if ($(e.target).closest('.claim-milestone').length) {
            return;
        }
        
        var card = $(this);
        var milestoneId = card.data('milestone-id');
        var title = card.data('milestone-title');
        var description = card.data('milestone-description');
        var type = card.data('milestone-type');
        var target = card.data('milestone-target');
        var current = card.data('milestone-current');
        var progress = card.data('milestone-progress');
        var rewardPoints = card.data('milestone-reward-points');
        var rewardDesc = card.data('milestone-reward-desc');
        var status = card.data('milestone-status');
        var achievable = card.data('milestone-achievable') === 'true';
        var image = card.data('milestone-image');
        var repeatable = card.data('milestone-repeatable') === 'true';
        
        // Populate modal with data
        $('#detail-milestone-title').text(title);
        $('#detail-milestone-description').text(description);
        
        // Set milestone type display
        var typeDisplay = '';
        switch (type) {
            case 'quest_count':
                typeDisplay = 'Quest Count';
                break;
            case 'total_points':
                typeDisplay = 'Total Points';
                break;
            case 'monthly_points':
                typeDisplay = 'Monthly Points';
                break;
        }
        $('#detail-milestone-type').text(typeDisplay);
        
        // Set values
        $('#detail-milestone-target').text(target.toLocaleString());
        $('#detail-current-value').text(current.toLocaleString());
        $('#detail-target-value').text(target.toLocaleString());
        $('#detail-progress-percent').text(Math.round(progress) + '%');
        $('#detail-reward-points').text(rewardPoints.toLocaleString() + ' Points');
        $('#detail-reward-description').text(rewardDesc);
        
        // Set progress percentage
        $('#detail-progress-percentage').text(Math.round(progress) + '%');
        $('#detail-progress-bar').css('width', progress + '%');
        
        // Set progress circle
        var circumference = 2 * Math.PI * 50; // radius = 50
        var offset = circumference - (progress / 100) * circumference;
        $('#detail-progress-circle').css('stroke-dashoffset', offset);
        
        // Set colors based on status
        var progressColor = '#d9d9d9';
        if (status === 'achieved') {
            progressColor = '#52c41a';
        } else if (achievable) {
            progressColor = '#faad14';
        } else {
            progressColor = '#1890ff';
        }
        $('#detail-progress-circle').attr('stroke', progressColor);
        $('#detail-progress-percentage').css('color', progressColor);
        
        // Set status badge
        if (status === 'achieved') {
            $('#detail-status-badge').removeClass().addClass('badge bg-success').html('<i class="bi bi-check-circle-fill me-1"></i>Delivered ✓');
        } else if (status === 'approved') {
            $('#detail-status-badge').removeClass().addClass('badge bg-info').html('<i class="bi bi-clock-fill me-1"></i>Awaiting Delivery');
        } else if (status === 'pending') {
            $('#detail-status-badge').removeClass().addClass('badge bg-warning').html('<i class="bi bi-hourglass-split me-1"></i>Pending Approval');
        } else if (achievable) {
            $('#detail-status-badge').removeClass().addClass('badge bg-warning').html('<i class="bi bi-star-fill me-1"></i>Ready to Claim');
        } else {
            $('#detail-status-badge').removeClass().addClass('badge bg-primary').html('<i class="bi bi-hourglass-split me-1"></i>In Progress');
        }
        
        // Handle image display
        var imageContainer = $('#detail-milestone-image-container');
        if (image) {
            imageContainer.html('<img src="<?= base_url() ?>assets/uploads/milestone_animations/' + image + '" alt="' + title + '">');
        } else {
            imageContainer.html('<div class="milestone-detail-placeholder"><i class="bi bi-star-fill" style="font-size: 3rem; color: ' + progressColor + ';"></i></div>');
        }
        
        // Show/hide repeatable info
        if (repeatable) {
            $('#detail-repeatable-info').show();
        } else {
            $('#detail-repeatable-info').hide();
        }
        
        // Show/hide claim button - only show for achievable milestones that haven't been claimed yet
        if (achievable && status === 'available') {
            $('#detail-claim-button').show().data('milestone-id', milestoneId).data('milestone-title', title).data('reward-points', rewardPoints);
        } else {
            $('#detail-claim-button').hide();
        }
        
        // Show modal
        $('#milestoneDetailModal').modal('show');
    });
    
    // Detail Modal Claim Button
    $('#detail-claim-button').click(function() {
        var milestoneId = $(this).data('milestone-id');
        var milestoneTitle = $(this).data('milestone-title');
        var rewardPoints = $(this).data('reward-points');
        
        // Close detail modal and open claim modal
        $('#milestoneDetailModal').modal('hide');
        
        // Set claim modal data
        $('#milestone-claim-id').val(milestoneId);
        $('#milestone-claim-title').text(milestoneTitle);
        $('#milestone-reward-points').text(rewardPoints);
        $('#milestoneClaimModal').modal('show');
    });

    // Milestone Claim
    $('.claim-milestone').click(function(e) {
        e.stopPropagation(); // Prevent card click event
        var milestoneId = $(this).data('milestone-id');
        var milestoneTitle = $(this).data('milestone-title');
        var rewardPoints = $(this).data('reward-points');

        $('#milestone-claim-id').val(milestoneId);
        $('#milestone-claim-title').text(milestoneTitle);
        $('#milestone-reward-points').text(rewardPoints);
        $('#milestoneClaimModal').modal('show');
    });

    $("#form-milestone-claim").submit(function() {
        var form = $(this);
        var mydata = form.serialize();
        $.ajax({
            type: "POST",
            url: "<?= base_url() ?>profile/claim-milestone",
            data: mydata,
            beforeSend: function() {
                $(".btn-claim-milestone").addClass("disabled").html('<span class="spinner-border spinner-border-sm me-2"></span>Claiming...').attr('disabled', true);
                $(".form-message-milestone").slideUp().html("");
            },
            success: function(response) {
                var str = response;
                if (str.indexOf("success") != -1) {
                    $(".form-message-milestone").hide().html(response).slideDown("fast");
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    $(".form-message-milestone").hide().html(response).slideDown("fast");
                    $(".btn-claim-milestone").removeClass("disabled").html('<i class="bi bi-star-fill me-1"></i>Claim Milestone').attr('disabled', false);
                }
            },
            error: function() {
                $(".btn-claim-milestone").removeClass("disabled").html('<i class="bi bi-star-fill me-1"></i>Claim Milestone').attr('disabled', false);
                $(".form-message-milestone").hide().html('<div class="alert alert-danger">Terjadi kesalahan sistem.</div>').slideDown("fast");
            }
        });
        return false;
    });
});
</script>