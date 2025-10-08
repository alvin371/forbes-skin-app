<!-- Full Career Paths Page for Employees -->

<!-- Required JavaScript Libraries -->
<script src="https://d3js.org/d3.v7.min.js"></script>
<script src="<?= base_url() ?>assets/js/org-chart-visualization.js"></script>

<style>
    .career-paths-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .career-header {
        background: white;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .career-header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }

    .career-header-info h1 {
        margin: 0 0 8px 0;
        color: #333;
        font-size: 28px;
        font-weight: 600;
    }

    .career-header-subtitle {
        color: #666;
        font-size: 16px;
        margin: 0;
    }

    .career-current-badge {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        text-align: center;
        min-width: 200px;
    }

    .career-current-title {
        font-size: 14px;
        font-weight: 500;
        opacity: 0.9;
        margin: 0 0 4px 0;
    }

    .career-current-position {
        font-size: 18px;
        font-weight: 600;
        margin: 0;
    }

    .career-paths-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 24px;
        margin-bottom: 32px;
    }

    .career-path-card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        border: 2px solid transparent;
        position: relative;
        overflow: hidden;
    }

    .career-path-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #4ecdc4, #44a08d);
    }

    .career-path-card.recommended::before {
        background: linear-gradient(90deg, #ff6b6b, #ee5a52);
    }

    .career-path-card.challenging::before {
        background: linear-gradient(90deg, #ffa726, #fb8c00);
    }

    .career-path-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .career-path-header {
        display: flex;
        justify-content: between;
        align-items: flex-start;
        margin-bottom: 16px;
    }

    .career-path-title {
        flex: 1;
    }

    .career-path-title h3 {
        margin: 0 0 8px 0;
        color: #333;
        font-size: 20px;
        font-weight: 600;
    }

    .career-path-type {
        font-size: 14px;
        padding: 4px 8px;
        border-radius: 4px;
        font-weight: 500;
    }

    .path-type-direct {
        background: #e8f5e8;
        color: #2e7d32;
    }

    .path-type-skip {
        background: #fff3e0;
        color: #f57c00;
    }

    .path-type-lead {
        background: #e3f2fd;
        color: #1976d2;
    }

    .path-type-vertical_technical,
    .path-type-vertical-technical {
        background: #e8f5e8;
        color: #2e7d32;
    }

    .path-type-lateral_management,
    .path-type-lateral-management {
        background: #e3f2fd;
        color: #1976d2;
    }

    .path-type-diagonal_hybrid,
    .path-type-diagonal-hybrid {
        background: #fff3e0;
        color: #f57c00;
    }

    .career-path-timeline {
        display: flex;
        align-items: center;
        margin-bottom: 16px;
        gap: 16px;
    }

    .timeline-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        flex: 1;
        position: relative;
    }

    .timeline-item:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 20px;
        right: -8px;
        width: 16px;
        height: 2px;
        background: #e0e0e0;
    }

    .timeline-position {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .timeline-position.current {
        background: #4caf50;
        color: white;
    }

    .timeline-position.next {
        background: #2196f3;
        color: white;
    }

    .timeline-position.future {
        background: #f5f5f5;
        color: #666;
        border: 2px dashed #ddd;
    }

    .timeline-label {
        font-size: 12px;
        color: #666;
        max-width: 80px;
        line-height: 1.3;
    }

    .career-path-details {
        margin-bottom: 16px;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
        font-size: 14px;
    }

    .detail-label {
        color: #666;
        font-weight: 500;
    }

    .detail-value {
        color: #333;
    }

    .readiness-score {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .score-circle {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 600;
        color: white;
    }

    .score-high {
        background: #4caf50;
    }

    .score-medium {
        background: #ff9800;
    }

    .score-low {
        background: #f44336;
    }

    .career-path-requirements {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 16px;
    }

    .requirements-title {
        font-size: 14px;
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
    }

    .requirements-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .requirements-list li {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 4px;
        font-size: 13px;
        color: #666;
    }

    .requirements-list li:last-child {
        margin-bottom: 0;
    }

    .req-icon {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        color: white;
        flex-shrink: 0;
    }

    .req-met {
        background: #4caf50;
    }

    .req-partial {
        background: #ff9800;
    }

    .req-unmet {
        background: #f44336;
    }

    .career-path-actions {
        display: flex;
        gap: 12px;
    }

    .path-action-btn {
        flex: 1;
        padding: 10px 16px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        text-align: center;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .btn-primary {
        background: #2196f3;
        color: white;
        border: none;
    }

    .btn-primary:hover {
        background: #1976d2;
        color: white;
        text-decoration: none;
    }

    .btn-outline {
        background: transparent;
        color: #666;
        border: 2px solid #e0e0e0;
    }

    .btn-outline:hover {
        background: #f5f5f5;
        color: #333;
        text-decoration: none;
    }

    .recommended-badge {
        position: absolute;
        top: 16px;
        right: 16px;
        background: #ff6b6b;
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
    }

    .selected-badge {
        position: absolute;
        top: 16px;
        right: 16px;
        background: linear-gradient(135deg, #52c41a, #389e0d);
        color: white;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        box-shadow: 0 2px 8px rgba(82, 196, 26, 0.3);
        display: flex;
        align-items: center;
        gap: 6px;
        animation: pulse-badge 2s ease-in-out infinite;
    }

    .selected-badge i {
        font-size: 14px;
    }

    @keyframes pulse-badge {
        0%, 100% {
            box-shadow: 0 2px 8px rgba(82, 196, 26, 0.3);
        }
        50% {
            box-shadow: 0 4px 16px rgba(82, 196, 26, 0.5);
        }
    }

    .career-path-card.selected-path {
        border-color: #52c41a;
        background: linear-gradient(to bottom, #f6ffed 0%, #ffffff 100%);
    }

    .career-path-card.selected-path::before {
        background: linear-gradient(90deg, #52c41a, #389e0d);
        height: 6px;
    }

    .career-path-card.selected-path:hover {
        border-color: #389e0d;
        box-shadow: 0 8px 25px rgba(82, 196, 26, 0.2);
    }

    .path-action-btn.btn-success {
        background: linear-gradient(135deg, #52c41a, #389e0d);
        border: none;
        color: white;
    }

    .path-action-btn.btn-success:hover {
        background: linear-gradient(135deg, #389e0d, #237804);
        transform: none;
        cursor: default;
    }

    .path-action-btn.btn-success.disabled {
        opacity: 0.8;
    }

    .career-insights {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .insights-title {
        font-size: 20px;
        font-weight: 600;
        color: #333;
        margin-bottom: 16px;
    }

    .insight-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .insight-item:last-child {
        border-bottom: none;
    }

    .insight-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }

    .insight-success {
        background: #e8f5e8;
        color: #2e7d32;
    }

    .insight-warning {
        background: #fff3e0;
        color: #f57c00;
    }

    .insight-info {
        background: #e3f2fd;
        color: #1976d2;
    }

    .insight-content {
        flex: 1;
    }

    .insight-title {
        font-size: 14px;
        font-weight: 600;
        color: #333;
        margin: 0 0 4px 0;
    }

    .insight-description {
        font-size: 13px;
        color: #666;
        margin: 0;
    }

    /* Career Tree Visualization Styles */
    .career-tree-section {
        background: white;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 32px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .career-tree-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 20px;
    }

    .career-tree-title h2 {
        margin: 0 0 8px 0;
        color: #333;
        font-size: 24px;
        font-weight: 600;
    }

    .career-tree-subtitle {
        color: #666;
        font-size: 14px;
        margin: 0;
    }

    .career-tree-controls {
        display: flex;
        gap: 16px;
        align-items: center;
        flex-wrap: wrap;
    }

    .view-mode-toggle {
        display: flex;
        background: #f5f5f5;
        border-radius: 8px;
        padding: 4px;
        gap: 2px;
    }

    .view-toggle-btn {
        background: transparent;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        color: #666;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .view-toggle-btn.active {
        background: white;
        color: #2196f3;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .view-toggle-btn:hover {
        color: #1976d2;
    }

    .tree-layout-controls {
        display: flex;
        gap: 4px;
    }

    .tree-layout-btn {
        background: #f5f5f5;
        border: 2px solid transparent;
        color: #666;
        padding: 8px 10px;
        border-radius: 6px;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .tree-layout-btn:hover {
        background: #e9ecef;
        color: #333;
    }

    .tree-layout-btn.active {
        background: #2196f3;
        color: white;
        border-color: #1976d2;
    }

    .career-tree-container {
        background: #f8f9fa;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        height: 900px;
        position: relative;
        overflow: visible;
        margin-bottom: 16px;
    }

    .tree-loading {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 900px;
        flex-direction: column;
        gap: 16px;
        color: #666;
    }

    .loading-text {
        font-size: 16px;
        font-weight: 500;
    }

    .tree-error {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 900px;
        flex-direction: column;
        gap: 16px;
        color: #f44336;
        text-align: center;
    }

    .tree-error i {
        font-size: 64px;
        opacity: 0.6;
    }

    .error-message {
        font-size: 16px;
        margin-bottom: 16px;
        max-width: 400px;
    }

    .tree-legend {
        display: flex;
        justify-content: center;
        gap: 24px;
        flex-wrap: wrap;
        padding: 16px;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 500;
        color: #333;
    }

    .legend-color {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        border: 2px solid white;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    }

    /* Hide cards view initially, show tree view */
    .career-paths-grid {
        display: none;
    }

    .career-paths-grid.active {
        display: grid;
    }

    .career-tree-container.active {
        display: block;
    }

    /* Statistics Cards */
    .stats-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .stat-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        border-left: 4px solid #1890ff;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    }

    .stat-card.purple {
        border-left-color: #722ed1;
    }

    .stat-card.green {
        border-left-color: #52c41a;
    }

    .stat-card.orange {
        border-left-color: #fa8c16;
    }

    .stat-card.pink {
        border-left-color: #eb2f96;
    }

    .stat-value {
        font-size: 32px;
        font-weight: 700;
        color: #262626;
        margin-bottom: 8px;
        line-height: 1;
    }

    .stat-label {
        font-size: 14px;
        color: #8c8c8c;
        font-weight: 500;
    }

    @media (max-width: 768px) {
        .career-paths-grid {
            grid-template-columns: 1fr;
        }

        .career-header-content {
            flex-direction: column;
            text-align: center;
        }

        .career-path-timeline {
            flex-direction: column;
            gap: 8px;
        }

        .timeline-item:not(:last-child)::after {
            display: none;
        }

        .career-tree-header {
            flex-direction: column;
            text-align: center;
            gap: 16px;
        }

        .career-tree-controls {
            justify-content: center;
        }

        .career-tree-container {
            min-height: 400px;
        }

        .tree-legend {
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }

        .stats-container {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="career-paths-container">
    <!-- Header Section -->
    <div class="career-header">
        <div class="career-header-content">
            <div class="career-header-info">
                <h1><i class="fas fa-route text-primary"></i> My Career Paths</h1>
                <p class="career-header-subtitle">Explore your career progression opportunities and advancement paths</p>
            </div>
            <div class="career-current-badge">
                <div class="career-current-title">Current Position</div>
                <div class="career-current-position"><?= $current_position['name'] ?? 'Not Assigned' ?></div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-container" id="statsContainer" style="display: none;">
        <div class="stat-card purple">
            <div class="stat-value" id="totalPositions">0</div>
            <div class="stat-label">Total Positions</div>
        </div>
        <div class="stat-card green">
            <div class="stat-value" id="departmentCount">0</div>
            <div class="stat-label">Departments</div>
        </div>
        <div class="stat-card orange">
            <div class="stat-value" id="hiringCount">0</div>
            <div class="stat-label">Hiring Positions</div>
        </div>
        <div class="stat-card pink">
            <div class="stat-value" id="leadershipCount">0</div>
            <div class="stat-label">Leadership Roles</div>
        </div>
    </div>

    <!-- Career Tree Visualization Section -->
    <div class="career-tree-section">
        <div class="career-tree-header">
            <div class="career-tree-title">
                <h2><i class="fas fa-sitemap text-primary"></i> Career Tree Visualization</h2>
                <p class="career-tree-subtitle">Interactive view of your career progression opportunities</p>
            </div>
            <div class="career-tree-controls">
                <div class="view-mode-toggle">
                    <button class="view-toggle-btn active" data-view="tree">
                        <i class="fas fa-eye"></i> Tree View
                    </button>
                    <button class="view-toggle-btn" data-view="cards">
                        <i class="fas fa-th-large"></i> Cards View
                    </button>
                </div>
                <div class="control-group">
                    <label for="layoutSelect" style="color: #666;">Layout:</label>
                    <select id="layoutSelect" class="form-select form-select-sm" style="width: auto;">
                        <option value="vertical">Vertical Tree</option>
                        <option value="horizontal">Horizontal Tree</option>
                        <option value="radial">Radial Dendrogram</option>
                    </select>
                </div>
                <div class="control-group">
                    <label for="colorModeSelect" style="color: #666;">Color By:</label>
                    <select id="colorModeSelect" class="form-select form-select-sm" style="width: auto;">
                        <option value="department">Department</option>
                        <option value="level">Level</option>
                        <option value="hiring">Hiring Status</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="career-tree-container" id="careerTreeFullContainer">
            <!-- D3.js career tree visualization will be rendered here -->
            <div class="tree-loading">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="loading-text">Loading your career tree visualization...</div>
            </div>
        </div>
    </div>

    <!-- Career Paths Grid -->
    <div class="career-paths-grid">

        <?php
        // Try both detailed and simple career paths
        $paths_to_show = [];
        if (isset($career_paths) && !empty($career_paths)) {
            $paths_to_show = $career_paths;
        } elseif (isset($simple_career_paths) && !empty($simple_career_paths)) {
            $paths_to_show = $simple_career_paths;
        }
        ?>

        <?php if (!empty($paths_to_show)): ?>
            <?php foreach ($paths_to_show as $index => $path): ?>
                <div class="career-path-card <?= $path['priority'] === 'high' ? 'recommended' : ($path['priority'] === 'low' ? 'challenging' : '') ?> <?= !empty($path['is_selected']) ? 'selected-path' : '' ?>" data-path-id="<?= $path['id'] ?>">
                    <?php if (!empty($path['is_selected'])): ?>
                        <div class="selected-badge"><i class="fas fa-check-circle"></i> Your Choice</div>
                    <?php elseif ($path['priority'] === 'high'): ?>
                        <div class="recommended-badge">Recommended</div>
                    <?php endif; ?>

                    <!-- Path Header -->
                    <div class="career-path-header">
                        <div class="career-path-title">
                            <h3><?= $path['target_position'] ?></h3>
                            <span class="career-path-type path-type-<?= $path['progression_type'] ?>">
                                <?= ucfirst(str_replace('_', ' ', $path['progression_type'])) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Timeline Visualization -->
                    <div class="career-path-timeline">
                        <div class="timeline-item">
                            <div class="timeline-position current">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="timeline-label">Current</div>
                        </div>

                        <?php if (count($path['steps']) > 1): ?>
                            <?php foreach (array_slice($path['steps'], 0, -1) as $step): ?>
                                <div class="timeline-item">
                                    <div class="timeline-position next">
                                        <i class="fas fa-arrow-up"></i>
                                    </div>
                                    <div class="timeline-label"><?= $step['short_name'] ?? substr($step['name'], 0, 10) ?>...</div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <div class="timeline-item">
                            <div class="timeline-position future">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="timeline-label">Goal</div>
                        </div>
                    </div>

                    <!-- Path Details -->
                    <div class="career-path-details">
                        <div class="detail-row">
                            <span class="detail-label">Path Type:</span>
                            <span class="detail-value">
                                <?php
                                $type_label = 'Technical Advancement';
                                $type_icon = 'arrow-up';
                                if (strpos($path['progression_type'], 'lateral') !== false) {
                                    $type_label = 'Management Track';
                                    $type_icon = 'arrow-right';
                                } elseif (strpos($path['progression_type'], 'diagonal') !== false) {
                                    $type_label = 'Cross-functional';
                                    $type_icon = 'arrow-up-right';
                                }
                                ?>
                                <i class="bi bi-<?= $type_icon ?>"></i> <?= $type_label ?>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Department:</span>
                            <span class="detail-value"><?= $path['target_department'] ?? 'Current' ?></span>
                        </div>
                        <?php if (!empty($path['has_quest']) && $path['readiness_score'] > 0): ?>
                        <div class="detail-row">
                            <span class="detail-label">Readiness Score:</span>
                            <div class="readiness-score">
                                <div class="score-circle score-<?= $path['readiness_level'] ?>">
                                    <?= $path['readiness_score'] ?>%
                                </div>
                                <span class="detail-value"><?= ucfirst($path['readiness_level']) ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Quest Requirements (Only shown if quest exists) -->
                    <?php if (!empty($path['has_quest']) && !empty($path['requirements_check'])): ?>
                        <div class="career-path-requirements">
                            <div class="requirements-title">
                                <i class="bi bi-list-check"></i> Quest Requirements
                            </div>
                            <ul class="requirements-list">
                                <?php foreach ($path['requirements_check'] as $req): ?>
                                    <li>
                                        <span class="req-icon req-<?= $req['status'] ?>">
                                            <i class="fas fa-<?= $req['status'] === 'met' ? 'check' : ($req['status'] === 'partial' ? 'minus' : 'times') ?>"></i>
                                        </span>
                                        <?= $req['description'] ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php elseif (!empty($path['has_quest'])): ?>
                        <div class="quest-cta" style="background: #f0f5ff; border: 1px solid #d6e4ff; border-radius: 8px; padding: 16px; text-align: center;">
                            <i class="bi bi-info-circle" style="font-size: 24px; color: #1890ff;"></i>
                            <p style="margin: 8px 0 0 0; color: #595959;">Quest requirements will be evaluated when you apply.</p>
                        </div>
                    <?php else: ?>
                        <div class="quest-cta" style="background: #fff7e6; border: 1px solid #ffd591; border-radius: 8px; padding: 16px; text-align: center;">
                            <i class="bi bi-exclamation-triangle" style="font-size: 24px; color: #fa8c16;"></i>
                            <p style="margin: 8px 0 0 0; color: #595959;">No promotion quest available for this path yet.</p>
                        </div>
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <div class="career-path-actions">
                        <?php if (!empty($path['is_selected'])): ?>
                            <button class="path-action-btn btn-success disabled" disabled>
                                <i class="fas fa-check-circle"></i> Selected Path
                            </button>
                        <?php else: ?>
                            <button class="path-action-btn btn-primary choose-path-btn"
                                    data-target-id="<?= $path['id'] ?>"
                                    data-path-name="<?= $path['target_position'] ?>"
                                    data-path-type="<?= $path['progression_type'] ?>">
                                <i class="fas fa-star"></i> Choose This Path
                            </button>
                        <?php endif; ?>

                        <?php if ($path['has_quest']): ?>
                            <a href="<?= base_url() ?>profile/apply_quest?path_id=<?= $path['id'] ?>" class="path-action-btn btn-outline">
                                <i class="fas fa-rocket"></i> Apply for Quest
                            </a>
                        <?php else: ?>
                            <a href="<?= base_url() ?>profile/path_details?id=<?= $path['id'] ?>" class="path-action-btn btn-outline">
                                <i class="fas fa-info-circle"></i> View Details
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="career-path-card">
                <div class="career-path-header">
                    <div class="career-path-title">
                        <h3>No Career Paths Available</h3>
                    </div>
                </div>
                <div class="career-path-details">
                    <p>Contact your manager or HR department to set up your career progression paths.</p>
                </div>
                <div class="career-path-actions">
                    <a href="<?= base_url() ?>contact/hr" class="path-action-btn btn-primary">
                        <i class="fas fa-envelope"></i> Contact HR
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Career Insights -->
    <div class="career-insights">
        <div class="insights-title"><i class="fas fa-lightbulb"></i> Career Insights</div>

        <?php if (isset($career_insights) && !empty($career_insights)): ?>
            <?php foreach ($career_insights as $insight): ?>
                <div class="insight-item">
                    <div class="insight-icon insight-<?= $insight['type'] ?>">
                        <i class="fas fa-<?= $insight['icon'] ?>"></i>
                    </div>
                    <div class="insight-content">
                        <div class="insight-title"><?= $insight['title'] ?></div>
                        <div class="insight-description"><?= $insight['description'] ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="insight-item">
                <div class="insight-icon insight-info">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="insight-content">
                    <div class="insight-title">Build Your Career Profile</div>
                    <div class="insight-description">Complete more quests and update your profile to get personalized career insights.</div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    $(document).ready(function() {
        let careerTreeViz = null;
        let selectedLayout = 'tree';
        let currentView = 'tree';
        let metadata = null;

        // Initialize Organization Chart Visualization
        function initializeCareerTreeFull() {
            // Check if OrgChartVisualization class is available
            if (typeof OrgChartVisualization === 'undefined') {
                console.error('OrgChartVisualization class not found. Make sure org-chart-visualization.js is loaded.');
                showTreeError('Organization chart visualization library not loaded. Please refresh the page.');
                return;
            }

            // Check if container is visible and has dimensions
            const container = $('#careerTreeFullContainer');
            if (!container.is(':visible') || container.width() === 0) {
                console.warn('Container not visible or has zero width, attempting to make visible');
                container.show();
            }

            try {
                // Clear any existing content from the container
                container.empty();

                // Create visualization instance for full page
                careerTreeViz = new OrgChartVisualization('careerTreeFullContainer', {
                    width: container.width() || 1400,
                    height: 900,
                    layout: $('#layoutSelect').val() || 'vertical',
                    colorMode: $('#colorModeSelect').val() || 'department',
                    enableTooltip: true,
                    enableZoom: true,
                    enablePan: true,
                    nodeRadius: 8,
                    fontSize: 13
                });


                loadUserCareerTreeDataFull();

            } catch (error) {
                console.error('Error initializing organization chart:', error);
                showTreeError('Failed to initialize organization chart: ' + error.message);
            }
        }

        // Load user-specific career tree data
        function loadUserCareerTreeDataFull() {
            // Add timeout to prevent infinite loading
            const loadingTimeout = setTimeout(function() {
                console.error('Career tree loading timeout');
                showTreeError('Career tree loading timed out. Please try refreshing the page.');
            }, 15000); // 15 second timeout

            $.ajax({
                url: '<?= base_url() ?>position/get_org_chart_data',
                method: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    showTreeLoading();
                },
                success: function(response) {
                    clearTimeout(loadingTimeout);

                    if (response.success) {
                        try {
                            // Store metadata
                            metadata = response.metadata;

                            // Check if we have valid tree data
                            if (response.data && typeof response.data === 'object' && response.data.name) {
                                // This looks like proper tree data

                                // Try recreating the visualization as the widget does
                                if (careerTreeViz) {
                                    careerTreeViz.destroy();
                                }

                                const container = $('#careerTreeFullContainer');
                                careerTreeViz = new OrgChartVisualization('careerTreeFullContainer', {
                                    width: container.width() || 1400,
                                    height: 900,
                                    layout: $('#layoutSelect').val() || 'vertical',
                                    colorMode: $('#colorModeSelect').val() || 'department',
                                    enableTooltip: true,
                                    enableZoom: true,
                                    enablePan: true,
                                    nodeRadius: 8,
                                    fontSize: 13
                                });

                                // Add small delay before loading data
                                setTimeout(() => {
                                    careerTreeViz.loadData(response.data);
                                    updateStatistics();
                                    $('#statsContainer').fadeIn();
                                    hideTreeLoading();
                                }, 100);
                            } else {
                                console.warn('No valid tree data in response, using fallback mock data');
                                // Fallback to mock data for demonstration
                                const mockData = {
                                    id: 'current-position',
                                    name: 'Current Position',
                                    department: 'Engineering',
                                    level_name: 'Junior',
                                    is_leadership: false,
                                    employee_count: 1,
                                    children: [{
                                        id: 'senior-position',
                                        name: 'Senior Position',
                                        department: 'Engineering',
                                        level_name: 'Senior',
                                        is_leadership: true,
                                        employee_count: 2,
                                        children: [{
                                            id: 'lead-position',
                                            name: 'Lead Position',
                                            department: 'Engineering',
                                            level_name: 'Executive',
                                            is_leadership: true,
                                            employee_count: 1,
                                            children: []
                                        }]
                                    }]
                                };

                                careerTreeViz.loadData(mockData);
                                hideTreeLoading();
                            }
                        } catch (error) {
                            console.error('Error processing career tree data:', error);
                            showTreeError('Error displaying career tree: ' + error.message);
                        }
                    } else {
                        showTreeError(response.message || 'Failed to load career tree data');
                    }
                },
                error: function(xhr, status, error) {
                    clearTimeout(loadingTimeout);
                    console.error('Career tree AJAX error:', error);

                    // Try to load mock data for testing, but show proper error if that fails too
                    try {
                        const mockData = {
                            id: 'current-position',
                            name: 'Current Position',
                            department: 'Engineering',
                            level_name: 'Junior',
                            is_leadership: false,
                            employee_count: 1,
                            children: [{
                                id: 'senior-position',
                                name: 'Senior Position',
                                department: 'Engineering',
                                level_name: 'Senior',
                                is_leadership: true,
                                employee_count: 2,
                                children: []
                            }]
                        };

                        careerTreeViz.loadData(mockData);
                        hideTreeLoading();
                    } catch (renderError) {
                        console.error('Error rendering mock tree:', renderError);
                        if (xhr.status === 401) {
                            showTreeError('Please log in to view your career tree.');
                        } else {
                            showTreeError('Failed to load career tree. Please try refreshing the page.');
                        }
                    }
                }
            });
        }

        // View toggle between tree and cards
        $('.view-toggle-btn').on('click', function(e) {
            e.preventDefault();

            const newView = $(this).data('view');
            if (newView !== currentView) {
                currentView = newView;

                // Update active state
                $('.view-toggle-btn').removeClass('active');
                $(this).addClass('active');

                // Toggle visibility
                if (newView === 'tree') {
                    $('.career-tree-container, .tree-legend').show().addClass('active');
                    $('.career-paths-grid').hide().removeClass('active');
                } else {
                    $('.career-tree-container, .tree-legend').hide().removeClass('active');
                    $('.career-paths-grid').show().addClass('active');
                    // Animate cards when switching to cards view
                    animateCareerCards();
                }
            }
        });

        // Layout dropdown change handler
        $('#layoutSelect').on('change', function() {
            if (careerTreeViz) {
                const newLayout = $(this).val();
                careerTreeViz.setLayout(newLayout);
            }
        });

        // Color mode dropdown change handler
        $('#colorModeSelect').on('change', function() {
            if (careerTreeViz) {
                const newColorMode = $(this).val();
                careerTreeViz.setColorMode(newColorMode);
            }
        });

        // Tree loading state
        function showTreeLoading() {
            $('#careerTreeFullContainer').html(`
            <div class="tree-loading">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="loading-text">Loading your career tree visualization...</div>
            </div>
        `);
        }

        function hideTreeLoading() {
            // Let D3.js CareerTreeVisualization handle its own loading states
            // Only remove our manual loading indicators
            $('#careerTreeFullContainer .tree-loading').remove();
        }

        // Tree error state
        function showTreeError(message) {
            $('#careerTreeFullContainer').html(`
            <div class="tree-error">
                <i class="fas fa-exclamation-triangle"></i>
                <div class="error-message">${message}</div>
                <button class="btn btn-outline-danger btn-sm mt-3" onclick="location.reload()">
                    <i class="fas fa-refresh"></i> Refresh Page
                </button>
            </div>
        `);
        }

        // Animate career path cards
        function animateCareerCards() {
            $('.career-path-card').each(function(index) {
                $(this).css('opacity', '0').css('transform', 'translateY(20px)');
                setTimeout(() => {
                    $(this).animate({
                        opacity: 1,
                        transform: 'translateY(0)'
                    }, 300);
                }, index * 100);
            });
        }

        // Update statistics from metadata
        function updateStatistics() {
            if (!metadata) return;

            $('#totalPositions').text(metadata.total_positions || 0);
            $('#departmentCount').text(metadata.department_count || 0);
            $('#hiringCount').text(metadata.hiring_positions || 0);
            $('#leadershipCount').text(metadata.leadership_positions || 0);
        }

        // Initialize tree on page load - use proper document ready with small delay
        if (currentView === 'tree') {
            setTimeout(() => {
                initializeCareerTreeFull();
            }, 100);
        }

        // Initialize with tree view by default
        $('.career-paths-grid').hide().removeClass('active');
        $('.career-tree-container, .tree-legend').show().addClass('active');

        // Smooth scroll for path actions
        $('.path-action-btn').click(function(e) {
            if ($(this).attr('href').startsWith('#')) {
                e.preventDefault();
                const target = $($(this).attr('href'));
                if (target.length) {
                    $('html, body').animate({
                        scrollTop: target.offset().top - 100
                    }, 300);
                }
            }
        });

        // Handle "Choose This Path" button click - using event delegation
        $(document).on('click', '.choose-path-btn', function(e) {
            e.preventDefault();
            const btn = $(this);
            const targetId = btn.data('target-id');
            const pathName = btn.data('path-name');
            const pathType = btn.data('path-type');
            const card = btn.closest('.career-path-card');

            // Confirmation with SweetAlert2
            Swal.fire({
                title: 'Choose This Career Path?',
                html: `<div style="text-align: left;">
                    <p>You are about to select <strong>${pathName}</strong> as your preferred career goal.</p>
                    <p style="margin-top: 12px; color: #666;">This will:</p>
                    <ul style="color: #666; margin-top: 8px;">
                        <li>Set this position as your career development target</li>
                        <li>Help HR provide personalized guidance</li>
                        <li>Unlock related quest recommendations</li>
                    </ul>
                    <p style="margin-top: 12px; color: #999; font-size: 14px;">You can change your selection anytime.</p>
                </div>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1890ff',
                cancelButtonColor: '#d9d9d9',
                confirmButtonText: '<i class="fas fa-star"></i> Yes, Choose This Path',
                cancelButtonText: 'Cancel',
                customClass: {
                    popup: 'swal2-custom-popup',
                    confirmButton: 'swal2-confirm-custom',
                    cancelButton: 'swal2-cancel-custom'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Disable button and show loading
                    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Selecting...');

                    // Send AJAX request
                    $.ajax({
                        url: '<?= base_url() ?>profile/choose_career_path',
                        type: 'POST',
                        data: {
                            target_position_id: targetId,
                            path_type: pathType
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    title: 'Career Path Selected!',
                                    html: `<p>You have successfully chosen <strong>${pathName}</strong> as your career goal.</p>`,
                                    icon: 'success',
                                    confirmButtonColor: '#52c41a',
                                    confirmButtonText: 'Great!',
                                    timer: 3000
                                }).then(() => {
                                    // Reload page to update UI
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: 'Selection Failed',
                                    text: response.message || 'Unable to save your career path selection.',
                                    icon: 'error',
                                    confirmButtonColor: '#ff4d4f'
                                });
                                btn.prop('disabled', false).html('<i class="fas fa-star"></i> Choose This Path');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Career path selection error:', error);
                            Swal.fire({
                                title: 'Error',
                                text: 'An error occurred while saving your selection. Please try again.',
                                icon: 'error',
                                confirmButtonColor: '#ff4d4f'
                            });
                            btn.prop('disabled', false).html('<i class="fas fa-star"></i> Choose This Path');
                        }
                    });
                }
            });
        });

        // Track career path interactions
        $('.path-action-btn').click(function() {
            const action = $(this).text().trim();
            const pathTitle = $(this).closest('.career-path-card').find('h3').text();

            // Analytics tracking (if implemented)
            if (typeof gtag !== 'undefined') {
                gtag('event', 'career_path_interaction', {
                    'action': action,
                    'path': pathTitle
                });
            }
        });

        // Cleanup on page unload
        $(window).on('beforeunload', function() {
            if (careerTreeViz) {
                careerTreeViz.destroy();
            }
        });
    });
</script>