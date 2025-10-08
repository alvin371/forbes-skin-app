<?php if (!empty($milestone_claim_history)): ?>
    <!-- Results Summary -->
    <div class="px-3 py-2 bg-light border-bottom">
        <?= $notif ?>
        <div class="d-flex justify-content-between align-items-center mt-1">
            <small class="text-muted">
                <i class="bi bi-info-circle me-1"></i>
                Showing milestone claims with point deductions from user accounts
            </small>
            <?php if ($page > 1): ?>
                <small class="text-muted">Page navigation available below</small>
            <?php endif; ?>
        </div>
    </div>

    <!-- Data Table -->
    <div class="table-responsive">
        <table class="table table-hover milestone-claims-table mb-0">
            <thead>
                <tr>
                    <th style="width: 25%; padding: 12px 16px;">
                        <i class="bi bi-trophy me-1" style="color: #faad14;"></i>
                        Milestone Details
                    </th>
                    <th style="width: 15%; padding: 12px 16px;">
                        <i class="bi bi-person me-1" style="color: #1890ff;"></i>
                        Claimed By
                    </th>
                    <th style="width: 15%; padding: 12px 16px;">
                        <i class="bi bi-calendar me-1" style="color: #52c41a;"></i>
                        Request Date
                    </th>
                    <th style="width: 10%; padding: 12px 16px;">
                        <i class="bi bi-star me-1" style="color: #faad14;"></i>
                        Points
                    </th>
                    <th style="width: 15%; padding: 12px 16px; text-align: center;">
                        <i class="bi bi-flag me-1" style="color: #1890ff;"></i>
                        Status
                    </th>
                    <th style="width: 20%; padding: 12px 16px; text-align: center;">
                        <i class="bi bi-gear me-1" style="color: #52c41a;"></i>
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($milestone_claim_history as $index => $claim): ?>
                    <tr class="milestone-claim-row">
                        <!-- Milestone Details -->
                        <td style="padding: 16px;">
                            <div class="d-flex align-items-center">
                                <div class="milestone-icon me-3" style="width: 45px; height: 45px; border-radius: 8px; 
                                     background: linear-gradient(135deg, #fff7e6, #fffbe6); 
                                     border: 2px solid #faad14; display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-trophy-fill" style="color: #faad14; font-size: 1.2rem;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-bold" style="color: rgba(0, 0, 0, 0.85); line-height: 1.3;">
                                        <?= htmlspecialchars($claim['milestone_title']) ?>
                                    </h6>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="badge" style="background-color: #fff7e6; color: #fa8c16; 
                                              border: 1px solid #ffd591; font-size: 0.7rem;">
                                            <?= $claim['milestone_type_display'] ?>
                                        </span>
                                        <small class="text-muted">
                                            Target: <?= number_format($claim['achievement_value']) ?>
                                        </small>
                                    </div>
                                    <?php if (!empty($claim['milestone_description'])): ?>
                                        <small class="text-muted" style="font-size: 0.75rem;">
                                            <?= substr(htmlspecialchars($claim['milestone_description']), 0, 80) ?>
                                            <?= strlen($claim['milestone_description']) > 80 ? '...' : '' ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>

                        <!-- Claimed By -->
                        <td style="padding: 16px;">
                            <div class="d-flex align-items-center">
                                <div class="user-avatar me-3" style="width: 36px; height: 36px; border-radius: 50%; 
                                     background: linear-gradient(135deg, #e6f7ff, #f0f5ff); 
                                     border: 2px solid #91d5ff; display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-person-fill" style="color: #1890ff; font-size: 1rem;"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold" style="color: rgba(0, 0, 0, 0.85); line-height: 1.2;">
                                        <?= htmlspecialchars($claim['claimed_by_name']) ?>
                                    </div>
                                    <small class="text-muted">
                                        @<?= htmlspecialchars($claim['claimed_by_username']) ?>
                                    </small>
                                    <?php if (!empty($claim['position_name'])): ?>
                                        <div>
                                            <small class="badge bg-light text-muted" style="font-size: 0.65rem;">
                                                <?= htmlspecialchars($claim['position_name']) ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>

                        <!-- Request Date -->
                        <td style="padding: 16px;">
                            <div class="text-center">
                                <div class="fw-semibold mb-1" style="color: rgba(0, 0, 0, 0.85);">
                                    <?= date('d M Y', strtotime($claim['achieved_at'])) ?>
                                </div>
                                <small class="text-muted d-block">
                                    <i class="bi bi-clock me-1"></i>
                                    <?= date('H:i:s', strtotime($claim['achieved_at'])) ?>
                                </small>
                                <small class="text-muted" style="font-size: 0.7rem;">
                                    <?php 
                                    $time_diff = time() - strtotime($claim['achieved_at']);
                                    if ($time_diff < 60) {
                                        echo 'just now';
                                    } elseif ($time_diff < 3600) {
                                        echo floor($time_diff/60) . ' min ago';
                                    } elseif ($time_diff < 86400) {
                                        echo floor($time_diff/3600) . ' hr ago';
                                    } else {
                                        echo floor($time_diff/86400) . ' days ago';
                                    }
                                    ?>
                                </small>
                            </div>
                        </td>

                        <!-- Points -->
                        <td style="padding: 16px;">
                            <div class="text-center">
                                <span class="badge d-inline-flex align-items-center" 
                                      style="background-color: #fff7e6; color: #fa8c16; border: 1px solid #ffd591; 
                                             font-size: 0.9rem; padding: 8px 12px; border-radius: 16px; font-weight: 600;">
                                    <i class="bi bi-star-fill me-2"></i>
                                    <?= number_format($claim['reward_points']) ?> pts
                                </span>
                            </div>
                        </td>

                        <!-- Status -->
                        <td style="padding: 16px;">
                            <div class="text-center">
                                <?php 
                                $status = $claim['status'] ?? 'waiting_approval'; // fallback to waiting_approval for new claims
                                if ($status == 'waiting_approval'): ?>
                                    <span class="badge bg-warning d-inline-flex align-items-center" 
                                          style="font-size: 0.8rem; padding: 6px 10px;">
                                        <i class="bi bi-hourglass-split me-1"></i>
                                        Waiting Approval
                                    </span>
                                <?php elseif ($status == 'approved'): ?>
                                    <span class="badge bg-info d-inline-flex align-items-center" 
                                          style="font-size: 0.8rem; padding: 6px 10px;">
                                        <i class="bi bi-clock-fill me-1"></i>
                                        Approved
                                    </span>
                                    <div class="mt-1">
                                        <small class="text-muted" style="font-size: 0.7rem;">
                                            Points deducted
                                        </small>
                                    </div>
                                <?php else: ?>
                                    <span class="badge bg-success d-inline-flex align-items-center" 
                                          style="font-size: 0.8rem; padding: 6px 10px;">
                                        <i class="bi bi-check-circle-fill me-1"></i>
                                        Delivered
                                    </span>
                                    <?php if (!empty($claim['proof_image'])): ?>
                                        <div class="mt-1">
                                            <small class="text-success" style="font-size: 0.7rem;">
                                                <i class="bi bi-image me-1"></i>Proof available
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>

                        <!-- Actions -->
                        <td style="padding: 16px;">
                            <div class="text-center">
                                <?php if ($status == 'waiting_approval'): ?>
                                    <button class="btn btn-success btn-sm me-1" 
                                            data-achievement-id="<?= $claim['id'] ?>"
                                            onclick="approveClaim(<?= $claim['id'] ?>, '<?= addslashes($claim['milestone_title']) ?>', '<?= addslashes($claim['claimed_by_name']) ?>')">
                                        <i class="bi bi-check me-1"></i>Approve
                                    </button>
                                <?php elseif ($status == 'approved'): ?>
                                    <button class="btn btn-primary btn-sm" 
                                            onclick="showDeliveryModal(<?= $claim['id'] ?>, '<?= addslashes($claim['milestone_title']) ?>', '<?= addslashes($claim['claimed_by_name']) ?>')">
                                        <i class="bi bi-truck me-1"></i>Mark Delivered
                                    </button>
                                <?php else: ?>
                                    <?php if (!empty($claim['proof_image'])): ?>
                                        <a href="<?= base_url() ?>assets/uploads/milestone_proofs/<?= $claim['proof_image'] ?>" 
                                           target="_blank" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-image me-1"></i>View Proof
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">No action required</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($page > 1): ?>
        <div class="px-3 py-3 bg-light border-top">
            <div class="d-flex justify-content-center">
                <nav aria-label="Milestone claims pagination">
                    <ul class="pagination pagination-sm mb-0">
                        <?php
                        $current_page = intval($_GET['page'] ?? 1);
                        for ($i = 1; $i <= $page; $i++):
                            $params = $_GET;
                            $params['page'] = $i;
                            $query_string = http_build_query($params);
                        ?>
                            <li class="page-item <?= ($i == $current_page) ? 'active' : '' ?>">
                                <a class="page-link" href="javascript:void(0)" 
                                   onclick="loadClaimableMilestoneDataWithPage(<?= $i ?>)"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        </div>
    <?php endif; ?>

<?php else: ?>
    <!-- Empty State -->
    <div class="text-center py-5">
        <div class="milestone-empty-state">
            <!-- Large Icon -->
            <div class="mb-4" style="margin: 0 auto;">
                <div style="width: 100px; height: 100px; border-radius: 50%; 
                           background: linear-gradient(135deg, #fafafa, #f5f5f5); 
                           border: 3px solid #d9d9d9; display: flex; align-items: center; 
                           justify-content: center; margin: 0 auto;">
                    <i class="bi bi-award" style="font-size: 3rem; color: #d9d9d9;"></i>
                </div>
            </div>

            <!-- Empty State Content -->
            <h6 class="text-muted mb-3" style="color: rgba(0, 0, 0, 0.45);">
                No Milestone Claims Found
            </h6>
            
            <p class="text-muted mb-4" style="max-width: 400px; margin: 0 auto; line-height: 1.5;">
                <?php if (!empty($keyword)): ?>
                    No milestone claims match your search criteria. 
                    <br>Try adjusting your search terms or filters.
                <?php else: ?>
                    No users have claimed milestones yet. 
                    <br>Milestone claims will appear here once users start claiming achievements.
                <?php endif; ?>
            </p>

            <!-- Action Buttons -->
            <div class="d-flex justify-content-center gap-2 mt-4">
                <?php if (!empty($keyword)): ?>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearSearch()">
                        <i class="bi bi-arrow-clockwise me-1"></i>Clear Search
                    </button>
                <?php endif; ?>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="loadClaimableMilestoneData()">
                    <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                </button>
            </div>

            <!-- Help Text -->
            <div class="mt-4 pt-3" style="border-top: 1px solid #f0f0f0;">
                <small class="text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    This page shows the history of all milestone claims across the system. 
                    When users claim milestones, their points are deducted and recorded here.
                </small>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
/* Custom styles for milestone claims table */
.milestone-claims-table thead th {
    background-color: #fafafa;
    border-bottom: 2px solid #f0f0f0;
    font-weight: 600;
    color: rgba(0, 0, 0, 0.85);
    white-space: nowrap;
}

.milestone-claim-row {
    transition: all 0.2s ease;
    border-bottom: 1px solid #f0f0f0;
}

.milestone-claim-row:hover {
    background-color: #f8f9fa;
    transform: translateX(2px);
    box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
}

.milestone-icon {
    transition: transform 0.2s ease;
}

.milestone-claim-row:hover .milestone-icon {
    transform: scale(1.05);
}

.user-avatar {
    transition: transform 0.2s ease;
}

.milestone-claim-row:hover .user-avatar {
    transform: scale(1.05);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .milestone-claims-table th,
    .milestone-claims-table td {
        padding: 8px 12px !important;
        font-size: 0.875rem;
    }
    
    .milestone-icon {
        width: 35px !important;
        height: 35px !important;
    }
    
    .user-avatar {
        width: 28px !important;
        height: 28px !important;
    }
    
    .milestone-icon i {
        font-size: 1rem !important;
    }
}
</style>

<script>
function initializeClaimableTable() {
    // Add any additional table initialization logic here
    console.log('Claimable milestone table initialized');
    
    // Update pagination info if needed
    const rowCount = $('.milestone-claim-row').length;
    $('#pagination-info').text(`Showing ${rowCount} claims`);
}
</script>