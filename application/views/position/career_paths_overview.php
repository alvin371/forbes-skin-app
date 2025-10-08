<!-- Career Paths Overview Tab Content -->
<style>
    .career-paths-overview {
        padding: 20px 0;
    }

    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .stat-card {
        background: white;
        border: 1px solid #e8e8e8;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        border-color: #1890ff;
        box-shadow: 0 4px 12px rgba(24, 144, 255, 0.1);
    }

    .stat-value {
        font-size: 32px;
        font-weight: 700;
        color: #1890ff;
        margin-bottom: 8px;
    }

    .stat-label {
        font-size: 14px;
        color: #8c8c8c;
    }

    .stat-card.success .stat-value {
        color: #52c41a;
    }

    .stat-card.warning .stat-value {
        color: #faad14;
    }

    .position-card {
        background: white;
        border: 2px solid #e8e8e8;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 16px;
        transition: all 0.3s ease;
    }

    .position-card:hover {
        border-color: #1890ff;
        box-shadow: 0 4px 12px rgba(24, 144, 255, 0.1);
    }

    .position-card.no-paths {
        border-color: #ffccc7;
        background: #fff2f0;
    }

    .position-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }

    .position-title {
        font-size: 18px;
        font-weight: 600;
        color: #262626;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .position-meta {
        display: flex;
        gap: 12px;
        font-size: 13px;
        color: #8c8c8c;
    }

    .position-meta-item {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .paths-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 12px;
    }

    .path-item {
        background: #f8f9fa;
        border: 1px solid #e8e8e8;
        border-radius: 6px;
        padding: 12px;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: all 0.2s ease;
    }

    .path-item:hover {
        background: #e6f7ff;
        border-color: #1890ff;
    }

    .path-icon {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        flex-shrink: 0;
    }

    .path-icon.vertical {
        background: #e6f7ff;
        color: #1890ff;
    }

    .path-icon.lateral {
        background: #f0f5ff;
        color: #722ed1;
    }

    .path-icon.diagonal {
        background: #fff7e6;
        color: #fa8c16;
    }

    .path-details {
        flex: 1;
    }

    .path-name {
        font-size: 14px;
        font-weight: 600;
        color: #262626;
        margin-bottom: 2px;
    }

    .path-timeline {
        font-size: 12px;
        color: #8c8c8c;
    }

    .no-paths-message {
        text-align: center;
        padding: 20px;
        color: #ff4d4f;
        font-size: 14px;
    }

    .edit-btn {
        background: #1890ff;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .edit-btn:hover {
        background: #096dd9;
        transform: translateY(-1px);
    }

    .filter-section {
        background: white;
        border: 1px solid #e8e8e8;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 20px;
    }

    .filter-controls {
        display: flex;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap;
    }

    .filter-item {
        flex: 1;
        min-width: 200px;
    }

    .filter-label {
        font-size: 13px;
        color: #595959;
        margin-bottom: 4px;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #8c8c8c;
    }

    .empty-state i {
        font-size: 64px;
        color: #d9d9d9;
        margin-bottom: 16px;
    }
</style>

<div class="career-paths-overview">
    <!-- Statistics Cards -->
    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-value"><?= $stats['total_positions'] ?></div>
            <div class="stat-label">Total Positions</div>
        </div>
        <div class="stat-card success">
            <div class="stat-value"><?= $stats['positions_with_paths'] ?></div>
            <div class="stat-label">With Career Paths</div>
        </div>
        <div class="stat-card warning">
            <div class="stat-value"><?= $stats['positions_without_paths'] ?></div>
            <div class="stat-label">Without Paths</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $stats['coverage_percentage'] ?>%</div>
            <div class="stat-label">Coverage</div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <div class="filter-controls">
            <div class="filter-item">
                <div class="filter-label">Department</div>
                <select class="form-select" id="filterDepartment">
                    <option value="">All Departments</option>
                    <?php
                    $departments = array_unique(array_column($positions, 'department'));
                    sort($departments);
                    foreach ($departments as $dept):
                        if (!empty($dept)):
                    ?>
                        <option value="<?= $dept ?>"><?= $dept ?></option>
                    <?php
                        endif;
                    endforeach;
                    ?>
                </select>
            </div>
            <div class="filter-item">
                <div class="filter-label">Status</div>
                <select class="form-select" id="filterStatus">
                    <option value="">All Positions</option>
                    <option value="with-paths">With Career Paths</option>
                    <option value="no-paths">Without Career Paths</option>
                </select>
            </div>
            <div class="filter-item">
                <div class="filter-label">Search</div>
                <input type="text" class="form-control" id="searchPosition" placeholder="Search position name...">
            </div>
        </div>
    </div>

    <!-- Positions List -->
    <div id="positionsList">
        <?php if (empty($positions)): ?>
            <div class="empty-state">
                <i class="bi bi-briefcase"></i>
                <p>No positions found. Create positions to define career paths.</p>
            </div>
        <?php else: ?>
            <?php foreach ($positions as $position): ?>
                <div class="position-card <?= !$position['has_paths'] ? 'no-paths' : '' ?>"
                     data-department="<?= htmlspecialchars($position['department'] ?? '') ?>"
                     data-has-paths="<?= $position['has_paths'] ? 'yes' : 'no' ?>"
                     data-name="<?= htmlspecialchars($position['name']) ?>">

                    <div class="position-header">
                        <div class="position-title">
                            <i class="bi bi-briefcase"></i>
                            <?= htmlspecialchars($position['name']) ?>
                            <?php if (!$position['has_paths']): ?>
                                <span class="badge bg-danger" style="font-size: 11px;">No Paths</span>
                            <?php endif; ?>
                        </div>
                        <a href="<?= base_url() ?>position/edit_page?id=<?= $position['id'] ?>" class="edit-btn">
                            <i class="bi bi-pencil"></i> Edit Paths
                        </a>
                    </div>

                    <div class="position-meta">
                        <?php if (!empty($position['department'])): ?>
                            <div class="position-meta-item">
                                <i class="bi bi-building"></i>
                                <?= htmlspecialchars($position['department']) ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($position['level_name'])): ?>
                            <div class="position-meta-item">
                                <i class="bi bi-bar-chart-steps"></i>
                                <?= htmlspecialchars($position['level_name']) ?>
                            </div>
                        <?php endif; ?>
                        <div class="position-meta-item">
                            <i class="bi bi-people"></i>
                            <?= $position['employee_count'] ?> employees
                        </div>
                    </div>

                    <?php if ($position['has_paths']): ?>
                        <div class="paths-container mt-3">
                            <?php foreach ($position['paths'] as $path): ?>
                                <?php
                                $pathTypeClass = 'vertical';
                                $pathIcon = 'arrow-up';

                                if (strpos($path['path_type'], 'lateral') !== false) {
                                    $pathTypeClass = 'lateral';
                                    $pathIcon = 'arrow-right';
                                } elseif (strpos($path['path_type'], 'diagonal') !== false) {
                                    $pathTypeClass = 'diagonal';
                                    $pathIcon = 'arrow-up-right';
                                }
                                ?>
                                <div class="path-item">
                                    <div class="path-icon <?= $pathTypeClass ?>">
                                        <i class="bi bi-<?= $pathIcon ?>"></i>
                                    </div>
                                    <div class="path-details">
                                        <div class="path-name"><?= htmlspecialchars($path['position_name']) ?></div>
                                        <div class="path-timeline">
                                            <?php
                                            $pathTypeLabel = 'Vertical';
                                            if (strpos($path['path_type'], 'lateral') !== false) {
                                                $pathTypeLabel = 'Lateral';
                                            } elseif (strpos($path['path_type'], 'diagonal') !== false) {
                                                $pathTypeLabel = 'Diagonal';
                                            }
                                            ?>
                                            <i class="bi bi-tag"></i> <?= $pathTypeLabel ?> Advancement
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-paths-message">
                            <i class="bi bi-exclamation-triangle"></i> No career paths defined for this position
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    // Filter functionality
    function filterPositions() {
        const department = $('#filterDepartment').val().toLowerCase();
        const status = $('#filterStatus').val();
        const search = $('#searchPosition').val().toLowerCase();

        $('.position-card').each(function() {
            const card = $(this);
            const cardDept = card.data('department').toString().toLowerCase();
            const hasPaths = card.data('has-paths');
            const cardName = card.data('name').toString().toLowerCase();

            let showDept = !department || cardDept.includes(department);
            let showStatus = !status ||
                             (status === 'with-paths' && hasPaths === 'yes') ||
                             (status === 'no-paths' && hasPaths === 'no');
            let showSearch = !search || cardName.includes(search);

            if (showDept && showStatus && showSearch) {
                card.show();
            } else {
                card.hide();
            }
        });
    }

    $('#filterDepartment, #filterStatus').on('change', filterPositions);
    $('#searchPosition').on('keyup', filterPositions);
});
</script>
