<?php if (isset($error_message)): ?>
    <div class="alert alert-warning" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Notice:</strong> <?= $error_message ?>
    </div>
    <div class="text-center py-5">
        <i class="bi bi-gear" style="font-size: 4rem; color: #d9d9d9;"></i>
        <h5 class="mt-3 mb-2" style="color: rgba(0, 0, 0, 0.45);">Setup Required</h5>
        <p style="color: rgba(0, 0, 0, 0.45);">Please run the database setup script to initialize the leaderboard system.</p>
    </div>
<?php elseif (empty($data)): ?>
    <div class="text-center py-5">
        <i class="bi bi-trophy" style="font-size: 4rem; color: #d9d9d9;"></i>
        <h5 class="mt-3 mb-2" style="color: rgba(0, 0, 0, 0.45);">No Data Available</h5>
        <p style="color: rgba(0, 0, 0, 0.45);">No employees have earned points this month yet.</p>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th class="text-center">Rank</th>
                    <th class="text-start">Employee</th>
                    <th class="text-start">Position</th>
                    <th class="text-center">Side Quests</th>
                    <th class="text-center">Quest Points</th>
                    <th class="text-center">Milestone Bonus</th>
                    <th class="text-center">Total Points</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $rank = 1;
                foreach ($data as $user): 
                    $rankClass = '';
                    if ($rank == 1) $rankClass = 'rank-1';
                    elseif ($rank == 2) $rankClass = 'rank-2';
                    elseif ($rank == 3) $rankClass = 'rank-3';
                    else $rankClass = 'rank-other';
                ?>
                    <tr>
                        <td class="text-center">
                            <div class="leaderboard-rank <?= $rankClass ?>">
                                <?php if ($rank <= 3): ?>
                                    <i class="bi bi-trophy-fill"></i>
                                <?php else: ?>
                                    <?= $rank ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="text-start">
                            <div>
                                <strong style="color: rgba(0, 0, 0, 0.85);"><?= $user['full_name'] ?></strong>
                                <?php if (!empty($user['join_date'])): ?>
                                    <br><small class="text-muted">Joined: <?= date('M Y', strtotime($user['join_date'])) ?></small>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="text-start">
                            <span class="badge" style="background-color: #f0f0f0; color: rgba(0, 0, 0, 0.65); border: 1px solid #d9d9d9;">
                                <?= $user['position_name'] ?? 'N/A' ?>
                            </span>
                            <?php if (!empty($user['level_name'])): ?>
                                <br><small class="text-muted"><?= $user['level_name'] ?> Level</small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="fw-bold" style="color: #1890ff; font-size: 16px;">
                                <?= $user['monthly_completed_quests'] ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge" style="background-color: #e6f7ff; color: #1890ff; border: 1px solid #91d5ff; font-size: 14px;">
                                <?= number_format($user['monthly_points_earned']) ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge" style="background-color: #fffbe6; color: #faad14; border: 1px solid #ffe58f; font-size: 14px;">
                                +<?= number_format($user['milestone_bonus_points']) ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="fw-bold" style="color: #52c41a; font-size: 18px;">
                                <?= number_format($user['total_monthly_points']) ?>
                            </span>
                        </td>
                    </tr>
                <?php 
                    $rank++;
                endforeach; 
                ?>
            </tbody>
        </table>
    </div>

    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card" style="background: linear-gradient(135deg, #ffd700, #ffed4a); border: none;">
                <div class="card-body text-center">
                    <i class="bi bi-trophy-fill" style="font-size: 2rem; color: #8b7355;"></i>
                    <h6 class="mt-2 mb-0" style="color: #8b7355;">Current Month</h6>
                    <p class="mb-0" style="color: #8b7355;"><?= date('F Y') ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="text-primary mb-1"><?= count($data) ?></h4>
                    <h6 class="mb-0" style="color: rgba(0, 0, 0, 0.65);">Active Participants</h6>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="text-success mb-1"><?= number_format(array_sum(array_column($data, 'total_monthly_points'))) ?></h4>
                    <h6 class="mb-0" style="color: rgba(0, 0, 0, 0.65);">Total Points This Month</h6>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>