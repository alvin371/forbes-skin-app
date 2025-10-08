<!-- Career Path Widget for Employee Profile Page -->
<style>
    .career-path-widget {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        padding: 24px;
        color: white;
        margin-bottom: 20px;
        position: relative;
        overflow: hidden;
    }

    .career-path-widget::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100px;
        height: 100px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        z-index: 1;
    }

    .career-widget-content {
        position: relative;
        z-index: 2;
    }

    .career-current-position {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
    }

    .career-position-icon {
        width: 50px;
        height: 50px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 16px;
        font-size: 20px;
    }

    .career-position-info h4 {
        margin: 0 0 4px 0;
        font-size: 18px;
        font-weight: 600;
    }

    .career-position-meta {
        font-size: 14px;
        opacity: 0.9;
    }

    .career-paths-preview {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        padding: 16px;
        margin-top: 16px;
    }

    .career-paths-title {
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 12px;
        opacity: 0.9;
    }

    .career-path-option {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .career-path-option:last-child {
        border-bottom: none;
    }

    .career-path-target {
        font-size: 14px;
        font-weight: 500;
    }

    .career-path-type {
        font-size: 12px;
        opacity: 0.8;
    }

    .career-readiness-badge {
        background: rgba(76, 175, 80, 0.3);
        border: 1px solid rgba(76, 175, 80, 0.5);
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 500;
    }

    .career-readiness-badge.medium {
        background: rgba(255, 193, 7, 0.3);
        border-color: rgba(255, 193, 7, 0.5);
    }

    .career-readiness-badge.low {
        background: rgba(244, 67, 54, 0.3);
        border-color: rgba(244, 67, 54, 0.5);
    }

    .career-widget-actions {
        display: flex;
        gap: 12px;
        margin-top: 16px;
    }

    .career-action-btn {
        flex: 1;
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
        text-decoration: none;
    }

    .career-action-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: translateY(-1px);
        color: white;
        text-decoration: none;
    }

    .career-quest-notification {
        background: #ff6b6b;
        color: white;
        padding: 12px;
        border-radius: 6px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .career-quest-notification.available {
        background: #4ecdc4;
    }

    .career-progress-bar {
        background: rgba(255, 255, 255, 0.2);
        height: 6px;
        border-radius: 3px;
        margin-top: 12px;
        overflow: hidden;
    }

    .career-progress-fill {
        background: linear-gradient(90deg, #4ecdc4, #44a08d);
        height: 100%;
        border-radius: 3px;
        transition: width 0.5s ease;
    }

    /* Career Tree Styles */
    .career-tree-section {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        padding: 16px;
        margin-top: 12px;
        backdrop-filter: blur(10px);
    }

    .career-tree-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }

    .tree-layout-controls {
        display: flex;
        gap: 4px;
    }

    .tree-layout-btn {
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        padding: 6px 10px;
        border-radius: 6px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .tree-layout-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: translateY(-1px);
    }

    .tree-layout-btn.active {
        background: rgba(255, 255, 255, 0.4);
        border-color: rgba(255, 255, 255, 0.5);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .career-tree-container {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 8px;
        min-height: 400px;
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* Tree Loading State */
    .tree-loading {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 400px;
        flex-direction: column;
        gap: 16px;
        color: white;
    }

    .loading-text {
        font-size: 14px;
        opacity: 0.9;
    }

    /* Tree Error State */
    .tree-error {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 400px;
        flex-direction: column;
        gap: 16px;
        color: white;
        text-align: center;
    }

    .tree-error i {
        font-size: 48px;
        opacity: 0.6;
    }

    .error-message {
        font-size: 14px;
        margin-bottom: 8px;
        max-width: 300px;
    }

    .tree-error .btn {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.3);
        color: white;
    }

    .tree-error .btn:hover {
        background: rgba(255, 255, 255, 0.2);
        border-color: rgba(255, 255, 255, 0.4);
        color: white;
    }

    /* Responsive Tree Styles */
    @media (max-width: 768px) {
        .career-tree-header {
            flex-direction: column;
            gap: 8px;
            text-align: center;
        }

        .career-tree-container {
            min-height: 300px;
        }

        .tree-layout-btn {
            padding: 8px 12px;
            font-size: 13px;
        }
    }
</style>

<div class="career-path-widget">
    <div class="career-widget-content">
        <!-- Current Position -->
        <div class="career-current-position">
            <div class="career-position-icon">
                <i class="fas fa-laptop-code"></i>
            </div>
            <div class="career-position-info">
                <h4><?= $user_position['position_name'] ?? 'Not Assigned' ?></h4>
                <div class="career-position-meta">
                    <?= $user_position['department'] ?? 'N/A' ?> • <?= $user_position['level_name'] ?? 'N/A' ?>
                    <?php if (isset($user_position['time_in_role'])): ?>
                        • <?= $user_position['time_in_role'] ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quest Notifications -->
        <?php if (isset($promotion_quests) && !empty($promotion_quests)): ?>
            <div class="career-quest-notification available">
                <i class="fas fa-trophy"></i>
                <span>Promotion quest available! Ready to advance your career?</span>
            </div>
        <?php endif; ?>

        <?php if (isset($pending_quest)): ?>
            <div class="career-quest-notification">
                <i class="fas fa-clock"></i>
                <span>Quest in progress: "<?= $pending_quest['title'] ?>"</span>
            </div>
        <?php endif; ?>

        <!-- Career Tree Visualization -->
        <div class="career-tree-section">
            <div class="career-tree-header">
                <div class="career-paths-title">
                    <i class="fas fa-sitemap"></i> Your Career Tree
                </div>
                <div class="tree-layout-controls">
                    <button class="tree-layout-btn active" data-layout="tree" title="Hierarchical Tree">
                        <i class="fas fa-sitemap"></i>
                    </button>
                    <button class="tree-layout-btn" data-layout="radial" title="Radial Tree">
                        <i class="fas fa-sun"></i>
                    </button>
                </div>
            </div>

            <div id="careerTreeContainer" class="career-tree-container">
                <!-- D3.js career tree visualization will be rendered here -->
                <div class="tree-loading">
                    <div class="spinner-border text-light" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="loading-text">Loading your career tree...</div>
                </div>
            </div>
        </div>

        <!-- Overall Career Progress -->
        <?php if (isset($career_progress)): ?>
            <div class="career-progress-bar">
                <div class="career-progress-fill" style="width: <?= $career_progress['percentage'] ?? 50 ?>%"></div>
            </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="career-widget-actions">
            <a href="<?= base_url() ?>profile/career_paths" class="career-action-btn">
                <i class="fas fa-map"></i> View All Paths
            </a>
            <?php if (isset($promotion_quests) && !empty($promotion_quests)): ?>
                <a href="<?= base_url() ?>profile/apply_quest" class="career-action-btn">
                    <i class="fas fa-rocket"></i> Apply for Quest
                </a>
            <?php else: ?>
                <a href="<?= base_url() ?>profile/career_recommendations" class="career-action-btn">
                    <i class="fas fa-lightbulb"></i> Get Recommendations
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        let careerTreeViz = null;
        let selectedLayout = 'tree';

        // Animate progress bar on load
        setTimeout(function() {
            if ($('.career-progress-fill').length > 0) {
                $('.career-progress-fill').css('width', $('.career-progress-fill').attr('style').match(/(\d+)%/)[0]);
            }
        }, 500);

        // Initialize Career Tree Visualization
        function initializeCareerTree() {
            // Check if CareerTreeVisualization class is available
            if (typeof CareerTreeVisualization === 'undefined') {
                console.warn('CareerTreeVisualization class not found. Tree visualization disabled.');
                showTreeError('Career tree visualization library not loaded.');
                return;
            }

            try {
                // Create visualization instance with widget-specific config
                careerTreeViz = new CareerTreeVisualization('careerTreeContainer', {
                    width: 800,
                    height: 400,
                    layout: selectedLayout,
                    enableTooltip: true,
                    enableZoom: true,
                    enablePan: true,
                    nodeRadius: 8,
                    fontSize: 11,
                    nodeColors: {
                        junior: '#52c41a',
                        intermediate: '#1890ff',
                        senior: '#722ed1',
                        executive: '#fa8c16',
                        leadership: '#eb2f96',
                        current: '#4ecdc4'
                    }
                });
                loadUserCareerTreeData();

            } catch (error) {
                console.error('Error initializing career tree:', error);
                showTreeError('Failed to initialize career tree: ' + error.message);
            }
        }

        // Load user-specific career tree data
        function loadUserCareerTreeData() {
            $.ajax({
                url: '<?= base_url() ?>position/get_user_career_tree_data',
                method: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    showTreeLoading();
                },
                success: function(response) {
                    if (response.success && response.data) {
                        try {
                            // Load data into visualization
                            careerTreeViz.loadData(response.data);

                            // Hide loading state - D3.js will render the tree
                            hideTreeLoading();

                        } catch (error) {
                            console.error('Error rendering career tree:', error);
                            showTreeError('Error displaying career tree: ' + error.message);
                        }
                    } else {
                        showTreeError(response.message || 'Failed to load career tree data');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Career tree AJAX error:', error);
                    if (xhr.status === 401) {
                        showTreeError('Please log in to view your career tree.');
                    } else {
                        showTreeError('Failed to load career tree. Please try refreshing the page.');
                    }
                }
            });
        }

        // Tree layout control buttons
        $('.tree-layout-btn').on('click', function(e) {
            e.preventDefault();

            const newLayout = $(this).data('layout');
            if (newLayout !== selectedLayout && careerTreeViz) {
                selectedLayout = newLayout;

                // Update active state
                $('.tree-layout-btn').removeClass('active');
                $(this).addClass('active');

                // Recreate visualization with new layout
                careerTreeViz.destroy();

                careerTreeViz = new CareerTreeVisualization('careerTreeContainer', {
                    width: 800,
                    height: 400,
                    layout: selectedLayout,
                    enableTooltip: true,
                    enableZoom: true,
                    enablePan: true,
                    nodeRadius: 8,
                    fontSize: 11
                });

                // Reload data
                loadUserCareerTreeData();
            }
        });

        // Tree loading state
        function showTreeLoading() {
            $('#careerTreeContainer').html(`
            <div class="tree-loading">
                <div class="spinner-border text-light" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="loading-text">Loading your career tree...</div>
            </div>
        `);
        }

        function hideTreeLoading() {
            $('#careerTreeContainer .tree-loading').fadeOut();
        }

        // Tree error state
        function showTreeError(message) {
            $('#careerTreeContainer').html(`
            <div class="tree-error">
                <i class="fas fa-exclamation-triangle"></i>
                <div class="error-message">${message}</div>
                <button class="btn btn-outline-light btn-sm mt-2" onclick="location.reload()">
                    <i class="fas fa-refresh"></i> Refresh
                </button>
            </div>
        `);
        }

        // Initialize tree when page loads
        setTimeout(initializeCareerTree, 1000);

        // Cleanup on page unload
        $(window).on('beforeunload', function() {
            if (careerTreeViz) {
                careerTreeViz.destroy();
            }
        });
    });
</script>