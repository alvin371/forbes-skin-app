<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Detail Milestone Quest</h5>
                <div>
                    <a href="<?= base_url() ?>milestone/milestone_edit_page?id=<?= $data['id'] ?>" class="btn btn-primary btn-sm me-2">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </a>
                    <a href="<?= base_url() ?>milestone" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- Main Information -->
                <div class="col-md-8">
                    <div class="mb-4">
                        <h3 class="mb-3" style="color: rgba(0, 0, 0, 0.85);"><?= $data['title'] ?></h3>
                        
                        <!-- Status Badge -->
                        <div class="mb-3">
                            <?php if ($data['is_active']): ?>
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle me-1"></i>Active
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary">
                                    <i class="bi bi-pause-circle me-1"></i>Inactive
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($data['is_repeatable']): ?>
                                <span class="badge bg-info ms-2">
                                    <i class="bi bi-arrow-repeat me-1"></i>Repeatable
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Description -->
                        <div class="mb-4">
                            <h6 class="text-muted mb-2">Description</h6>
                            <p style="color: rgba(0, 0, 0, 0.75); line-height: 1.6;"><?= nl2br($data['description']) ?></p>
                        </div>
                        
                        <!-- Milestone Details -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="p-3" style="background-color: #f6ffed; border-radius: 8px; border: 1px solid #b7eb8f;">
                                    <h6 class="mb-2" style="color: #52c41a;">
                                        <i class="bi bi-target me-2"></i>Milestone Type
                                    </h6>
                                    <div class="fw-bold" style="color: rgba(0, 0, 0, 0.85);">
                                        <?php
                                        switch ($data['milestone_type']) {
                                            case 'quest_count':
                                                echo 'Quest Count';
                                                break;
                                            case 'total_points':
                                                echo 'Total Points';
                                                break;
                                            case 'monthly_points':
                                                echo 'Monthly Points';
                                                break;
                                            default:
                                                echo ucfirst(str_replace('_', ' ', $data['milestone_type']));
                                        }
                                        ?>
                                    </div>
                                    <small class="text-muted">
                                        <?php
                                        switch ($data['milestone_type']) {
                                            case 'quest_count':
                                                echo 'Berdasarkan jumlah side quest yang diselesaikan';
                                                break;
                                            case 'total_points':
                                                echo 'Berdasarkan total poin yang dikumpulkan sepanjang waktu';
                                                break;
                                            case 'monthly_points':
                                                echo 'Berdasarkan poin yang dikumpulkan dalam satu bulan';
                                                break;
                                        }
                                        ?>
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3" style="background-color: #e6f7ff; border-radius: 8px; border: 1px solid #91d5ff;">
                                    <h6 class="mb-2" style="color: #1890ff;">
                                        <i class="bi bi-bullseye me-2"></i>Target Value
                                    </h6>
                                    <div class="fw-bold" style="color: rgba(0, 0, 0, 0.85); font-size: 1.5rem;">
                                        <?= number_format($data['target_value']) ?>
                                    </div>
                                    <small class="text-muted">
                                        <?php
                                        switch ($data['milestone_type']) {
                                            case 'quest_count':
                                                echo $data['target_value'] == 1 ? 'quest' : 'quests';
                                                break;
                                            case 'total_points':
                                            case 'monthly_points':
                                                echo 'points';
                                                break;
                                        }
                                        ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Reward Information -->
                        <div class="mb-4">
                            <h6 class="text-muted mb-2">Reward Description</h6>
                            <div class="p-3" style="background-color: #fffbe6; border-radius: 8px; border: 1px solid #ffe58f;">
                                <div class="d-flex align-items-start">
                                    <div class="me-3">
                                        <i class="bi bi-gift" style="font-size: 1.5rem; color: #faad14;"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <p class="mb-0" style="color: rgba(0, 0, 0, 0.75);"><?= nl2br($data['reward_description']) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Creation Info -->
                        <div class="row">
                            <div class="col-md-6">
                                <small class="text-muted">Created by: <strong><?= $data['creator_name'] ?? 'Unknown' ?></strong></small>
                            </div>
                            <div class="col-md-6 text-end">
                                <small class="text-muted">Created: <?= date('d M Y H:i', strtotime($data['created_at'])) ?></small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Animation Image -->
                <div class="col-md-4">
                    <div class="text-center">
                        <?php if (!empty($data['gambar_animasi'])): ?>
                            <div class="mb-3">
                                <h6 class="text-muted mb-3">Animation Image</h6>
                                <div class="milestone-image-display" style="position: relative; display: inline-block;">
                                    <img src="<?= base_url() ?>assets/uploads/milestone_animations/<?= $data['gambar_animasi'] ?>" 
                                         alt="<?= $data['title'] ?>" 
                                         class="img-fluid" 
                                         style="max-width: 200px; max-height: 200px; object-fit: cover; 
                                                border-radius: 12px; border: 3px solid #91d5ff; 
                                                box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                                    <?php if ($data['is_active']): ?>
                                        <div style="position: absolute; top: -8px; right: -8px; 
                                                   background: #52c41a; color: white; 
                                                   border-radius: 50%; width: 32px; height: 32px; 
                                                   display: flex; align-items: center; justify-content: center; 
                                                   box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                                            <i class="bi bi-check"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="mb-3">
                                <h6 class="text-muted mb-3">No Animation Image</h6>
                                <div class="milestone-placeholder" style="width: 150px; height: 150px; margin: 0 auto;
                                           border-radius: 12px; border: 3px solid #d9d9d9; 
                                           background: #fafafa; display: flex; 
                                           align-items: center; justify-content: center;
                                           box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                                    <i class="bi bi-image" style="font-size: 3rem; color: #d9d9d9;"></i>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Quick Stats -->
                        <div class="mt-4">
                            <div class="p-3" style="background-color: #f8f9fa; border-radius: 8px;">
                                <h6 class="mb-3" style="color: rgba(0, 0, 0, 0.85);">Quick Stats</h6>
                                <div class="text-center">
                                    <div class="fw-bold text-primary"><?= number_format($data['target_value']) ?></div>
                                    <small class="text-muted">Target Value</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.milestone-image-display:hover img {
    transform: scale(1.05);
    transition: transform 0.3s ease;
}

.card {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border: none;
    border-radius: 12px;
}

.card-header {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-bottom: 1px solid rgba(0,0,0,0.1);
}
</style>