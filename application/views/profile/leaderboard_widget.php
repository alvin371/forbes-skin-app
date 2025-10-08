<!-- Leaderboard Widget -->
<div class="card mb-4" style="background: linear-gradient(135deg, #e6f7ff 0%, #f6ffed 100%); border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
    <div class="card-header" style="background: transparent; border-bottom: 1px solid rgba(24, 144, 255, 0.2);">
        <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">
            <i class="bi bi-trophy-fill me-2" style="color: #faad14;"></i>Leaderboard Saya
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <!-- Monthly Ranking -->
            <div class="col-md-6 mb-3">
                <div class="text-center p-4" style="background: linear-gradient(135deg, #ffd700, #ffed4a); border-radius: 12px; position: relative; overflow: hidden;">
                    <div style="position: absolute; top: -20px; right: -20px; font-size: 5rem; color: rgba(255,255,255,0.2);">
                        <i class="bi bi-calendar-month"></i>
                    </div>
                    <div style="position: relative; z-index: 2;">
                        <div class="d-flex justify-content-center align-items-center mb-2">
                            <?php
                            $monthly_rank = $leaderboard_stats['monthly_rank'] ?? 0;
                            $rank_icon = '';
                            $rank_color = '';
                            
                            if ($monthly_rank == 1) {
                                $rank_icon = 'bi-trophy-fill';
                                $rank_color = '#8b7355';
                            } elseif ($monthly_rank == 2) {
                                $rank_icon = 'bi-award-fill';
                                $rank_color = '#5a5a5a';
                            } elseif ($monthly_rank == 3) {
                                $rank_icon = 'bi-star-fill';
                                $rank_color = '#5a4037';
                            } else {
                                $rank_icon = 'bi-hash';
                                $rank_color = '#8b7355';
                            }
                            ?>
                            <i class="<?= $rank_icon ?> me-2" style="font-size: 1.5rem; color: <?= $rank_color ?>;"></i>
                            <h2 class="mb-0" style="color: <?= $rank_color ?>; font-weight: bold;">#<?= $monthly_rank ?></h2>
                        </div>
                        <h6 class="mb-2" style="color: #8b7355; font-weight: 600;">Ranking Bulan Ini</h6>
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="fw-bold" style="color: #8b7355; font-size: 1.1rem;"><?= number_format($leaderboard_stats['monthly_points_earned'] ?? 0) ?></div>
                                <small style="color: #8b7355;">Poin Quest</small>
                            </div>
                            <div class="col-6">
                                <div class="fw-bold" style="color: #8b7355; font-size: 1.1rem;"><?= $leaderboard_stats['milestone_bonus_points'] ?? 0 ?></div>
                                <small style="color: #8b7355;">Bonus Milestone</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- All-Time Ranking -->
            <div class="col-md-6 mb-3">
                <div class="text-center p-4" style="background: linear-gradient(135deg, #52c41a, #73d13d); border-radius: 12px; position: relative; overflow: hidden;">
                    <div style="position: absolute; top: -20px; right: -20px; font-size: 5rem; color: rgba(255,255,255,0.2);">
                        <i class="bi bi-infinity"></i>
                    </div>
                    <div style="position: relative; z-index: 2;">
                        <div class="d-flex justify-content-center align-items-center mb-2">
                            <?php
                            $alltime_rank = $leaderboard_stats['alltime_rank'] ?? 0;
                            $alltime_icon = '';
                            
                            if ($alltime_rank == 1) {
                                $alltime_icon = 'bi-trophy-fill';
                            } elseif ($alltime_rank == 2) {
                                $alltime_icon = 'bi-award-fill';
                            } elseif ($alltime_rank == 3) {
                                $alltime_icon = 'bi-star-fill';
                            } else {
                                $alltime_icon = 'bi-hash';
                            }
                            ?>
                            <i class="<?= $alltime_icon ?> me-2" style="font-size: 1.5rem; color: white;"></i>
                            <h2 class="mb-0" style="color: white; font-weight: bold;">#<?= $alltime_rank ?></h2>
                        </div>
                        <h6 class="mb-2" style="color: white; font-weight: 600;">Ranking Sepanjang Masa</h6>
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="fw-bold" style="color: white; font-size: 1.1rem;"><?= number_format($leaderboard_stats['total_points_earned'] ?? 0) ?></div>
                                <small style="color: white;">Total Poin</small>
                            </div>
                            <div class="col-6">
                                <div class="fw-bold" style="color: white; font-size: 1.1rem;"><?= $leaderboard_stats['total_completed_quests'] ?? 0 ?></div>
                                <small style="color: white;">Quest Selesai</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Performers Comparison -->
        <?php if (!empty($leaderboard_stats['top_performers'])): ?>
        <div class="mt-4">
            <h6 class="mb-3" style="color: rgba(0, 0, 0, 0.85);">
                <i class="bi bi-people-fill me-2" style="color: #1890ff;"></i>Top Performers Bulan Ini
            </h6>
            <div class="row">
                <?php foreach ($leaderboard_stats['top_performers'] as $index => $performer): ?>
                    <?php
                    $position = $index + 1;
                    $medal_color = '';
                    $bg_gradient = '';
                    
                    switch ($position) {
                        case 1:
                            $medal_color = '#ffd700';
                            $bg_gradient = 'linear-gradient(135deg, #fff7e6, #fffbe6)';
                            break;
                        case 2:
                            $medal_color = '#c0c0c0';
                            $bg_gradient = 'linear-gradient(135deg, #f6f6f6, #fafafa)';
                            break;
                        case 3:
                            $medal_color = '#cd7f32';
                            $bg_gradient = 'linear-gradient(135deg, #fff2e8, #fff7ed)';
                            break;
                    }
                    ?>
                    <div class="col-md-4 mb-2">
                        <div class="p-3 text-center" style="background: <?= $bg_gradient ?>; border-radius: 8px; border: 1px solid rgba(0,0,0,0.1);">
                            <div class="d-flex justify-content-center align-items-center mb-2">
                                <i class="bi bi-trophy-fill me-2" style="color: <?= $medal_color ?>; font-size: 1.2rem;"></i>
                                <span class="fw-bold" style="color: rgba(0, 0, 0, 0.85);">#<?= $position ?></span>
                            </div>
                            <div class="fw-bold mb-1" style="color: rgba(0, 0, 0, 0.85); font-size: 0.9rem;">
                                <?= $performer['full_name'] ?>
                            </div>
                            <span class="badge" style="background-color: #e6f7ff; color: #1890ff; border: 1px solid #91d5ff;">
                                <?= number_format($performer['total_monthly_points']) ?> poin
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Achievement Summary -->
        <div class="mt-4 pt-3" style="border-top: 1px solid rgba(24, 144, 255, 0.2);">
            <div class="row text-center">
                <div class="col-3">
                    <div class="fw-bold text-primary" style="font-size: 1.2rem;"><?= $leaderboard_stats['total_all_time_points'] ?? 0 ?></div>
                    <small class="text-muted">Total Poin</small>
                </div>
                <div class="col-3">
                    <div class="fw-bold text-success" style="font-size: 1.2rem;"><?= $leaderboard_stats['total_completed_quests'] ?? 0 ?></div>
                    <small class="text-muted">Quest Selesai</small>
                </div>
                <div class="col-3">
                    <div class="fw-bold text-warning" style="font-size: 1.2rem;"><?= $leaderboard_stats['monthly_completed_quests'] ?? 0 ?></div>
                    <small class="text-muted">Quest Bulan Ini</small>
                </div>
                <div class="col-3">
                    <div class="fw-bold text-info" style="font-size: 1.2rem;"><?= $leaderboard_stats['milestone_bonus_points'] ?? 0 ?></div>
                    <small class="text-muted">Bonus Milestone</small>
                </div>
            </div>
        </div>
    </div>
</div>