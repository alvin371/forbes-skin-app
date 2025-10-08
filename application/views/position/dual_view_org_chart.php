<!-- Include D3.js v7 -->
<script src="https://d3js.org/d3.v7.min.js"></script>

<!-- Include org chart visualization -->
<script src="<?= base_url() ?>assets/js/org-chart-visualization.js"></script>

<style>
    /* Page Container */
    .dual-view-page {
        min-height: 100vh;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        padding: 20px;
    }

    /* View Toggle */
    .view-toggle-container {
        background: white;
        border-radius: 12px;
        padding: 16px 24px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }

    .view-toggle {
        display: inline-flex;
        background: #f5f5f5;
        border-radius: 8px;
        padding: 4px;
        gap: 4px;
    }

    .view-toggle-btn {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        background: transparent;
        cursor: pointer;
        font-weight: 500;
        font-size: 14px;
        color: #666;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .view-toggle-btn.active {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .view-toggle-btn:hover:not(.active) {
        background: #e0e0e0;
        color: #333;
    }

    /* Mode Info Badge */
    .mode-info {
        background: #e3f2fd;
        color: #1976d2;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .mode-info.career-mode {
        background: #f3e5f5;
        color: #7b1fa2;
    }

    /* Chart Card */
    .chart-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        margin-bottom: 20px;
    }

    .chart-container {
        position: relative;
        height: 900px;
        overflow: hidden;
    }

    /* Career Path Panel */
    .career-path-panel {
        position: fixed;
        right: -450px;
        top: 0;
        width: 450px;
        height: 100vh;
        background: white;
        box-shadow: -4px 0 20px rgba(0, 0, 0, 0.15);
        transition: right 0.3s ease;
        z-index: 1000;
        overflow-y: auto;
    }

    .career-path-panel.open {
        right: 0;
    }

    .panel-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 24px;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .close-panel {
        position: absolute;
        top: 20px;
        right: 20px;
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 24px;
        line-height: 1;
        transition: all 0.3s;
    }

    .close-panel:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: rotate(90deg);
    }

    .panel-title {
        font-size: 20px;
        font-weight: 600;
        margin: 0 0 8px 0;
    }

    .panel-subtitle {
        font-size: 14px;
        opacity: 0.9;
        margin: 0;
    }

    .panel-content {
        padding: 24px;
    }

    .path-option {
        background: #f8f9fa;
        border-left: 4px solid #52c41a;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        transition: all 0.3s;
    }

    .path-option:hover {
        background: #f0f0f0;
        transform: translateX(4px);
    }

    .path-option.lateral {
        border-left-color: #1890ff;
    }

    .path-option.diagonal {
        border-left-color: #fa8c16;
    }

    .path-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 12px;
    }

    .path-title {
        font-size: 18px;
        font-weight: 600;
        color: #262626;
        margin: 0 0 4px 0;
    }

    .path-type-badge {
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 500;
        white-space: nowrap;
    }

    .path-type-badge.vertical {
        background: #f6ffed;
        color: #52c41a;
        border: 1px solid #b7eb8f;
    }

    .path-type-badge.lateral {
        background: #e6f7ff;
        color: #1890ff;
        border: 1px solid #91d5ff;
    }

    .path-type-badge.diagonal {
        background: #fff7e6;
        color: #fa8c16;
        border: 1px solid #ffd591;
    }

    .path-timeline {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #8c8c8c;
        font-size: 14px;
        margin-bottom: 16px;
    }

    .requirements-section {
        margin-top: 16px;
    }

    .requirements-title {
        font-size: 14px;
        font-weight: 600;
        color: #262626;
        margin-bottom: 12px;
    }

    .requirement-item {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        margin-bottom: 8px;
        font-size: 13px;
        color: #595959;
    }

    .requirement-icon {
        color: #52c41a;
        margin-top: 2px;
    }

    /* Loading State */
    .loading-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 900px;
        gap: 20px;
    }

    .loading-text {
        font-size: 16px;
        color: #8c8c8c;
        font-weight: 500;
    }

    /* Legend */
    .chart-legend {
        padding: 20px 24px;
        background: #fafafa;
        border-top: 1px solid #e8e8e8;
        display: flex;
        gap: 24px;
        flex-wrap: wrap;
        align-items: center;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: #595959;
    }

    .legend-line {
        width: 40px;
        height: 3px;
        border-radius: 2px;
    }

    .legend-line.solid {
        background: #8c8c8c;
    }

    .legend-line.dashed-green {
        background: linear-gradient(90deg, #52c41a 50%, transparent 50%);
        background-size: 8px 3px;
    }

    .legend-line.dashed-blue {
        background: linear-gradient(90deg, #1890ff 50%, transparent 50%);
        background-size: 8px 3px;
    }

    .legend-line.dashed-orange {
        background: linear-gradient(90deg, #fa8c16 50%, transparent 50%);
        background-size: 8px 3px;
    }

    /* Empty State */
    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 900px;
        color: #8c8c8c;
        text-align: center;
    }

    .empty-state i {
        font-size: 64px;
        margin-bottom: 16px;
        opacity: 0.5;
    }

    .empty-state h3 {
        margin: 0 0 8px 0;
        color: #262626;
    }

    .empty-state p {
        margin: 0;
        max-width: 400px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .career-path-panel {
            width: 100%;
            right: -100%;
        }

        .view-toggle-container {
            flex-direction: column;
            align-items: stretch;
        }

        .view-toggle {
            width: 100%;
        }

        .view-toggle-btn {
            flex: 1;
            justify-content: center;
        }
    }
</style>

<div class="dual-view-page">
    <!-- View Toggle Header -->
    <div class="view-toggle-container">
        <div class="view-toggle">
            <button class="view-toggle-btn active" data-mode="reporting">
                <i class="bi bi-diagram-3"></i>
                <span>Reporting Structure</span>
            </button>
            <button class="view-toggle-btn" data-mode="career_paths">
                <i class="bi bi-signpost-split"></i>
                <span>Career Paths</span>
            </button>
        </div>

        <div class="mode-info" id="modeInfo">
            <i class="bi bi-info-circle"></i>
            <span>Shows who reports to whom (solid lines)</span>
        </div>
    </div>

    <!-- Main Chart -->
    <div class="chart-card">
        <div id="chartContainer" class="chart-container">
            <!-- Chart will be rendered here -->
            <div class="loading-container">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="loading-text">Loading organization chart...</div>
            </div>
        </div>

        <!-- Legend -->
        <div class="chart-legend" id="chartLegend">
            <!-- Will be populated based on mode -->
        </div>
    </div>
</div>

<!-- Career Path Details Panel -->
<div id="careerPathPanel" class="career-path-panel">
    <div class="panel-header">
        <button class="close-panel" onclick="closeCareerPanel()">&times;</button>
        <h3 class="panel-title" id="panelPositionName">Position Name</h3>
        <p class="panel-subtitle" id="panelPositionLevel">Level</p>
    </div>
    <div class="panel-content" id="panelContent">
        <!-- Content will be dynamically loaded -->
    </div>
</div>

<script>
$(document).ready(function() {
    let orgChart = null;
    let currentMode = 'reporting';
    let chartData = null;

    // Initialize
    function init() {
        orgChart = new OrgChartVisualization('chartContainer', {
            width: $('#chartContainer').width() || 1400,
            height: 900,
            layout: 'vertical',
            colorMode: 'department',
            enableZoom: true,
            enableTooltip: true,
            nodeRadius: 8,
            fontSize: 13
        });

        loadData(currentMode);
    }

    // Toggle between modes
    $('.view-toggle-btn').on('click', function() {
        const newMode = $(this).data('mode');
        if (newMode !== currentMode) {
            currentMode = newMode;
            $('.view-toggle-btn').removeClass('active');
            $(this).addClass('active');
            updateModeInfo();
            loadData(newMode);
        }
    });

    // Load data from API
    function loadData(mode) {
        showLoading();

        $.ajax({
            url: '<?= base_url() ?>position/get_dual_view_data?mode=' + mode,
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log('Dual view data loaded:', response);

                if (response.success) {
                    chartData = response;

                    if (mode === 'career_paths') {
                        renderCareerPathMode(response.data);
                    } else {
                        renderReportingMode(response.data);
                    }

                    updateLegend(mode);
                } else {
                    showError(response.message || 'Failed to load data');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                showError('Failed to load chart data. Please try refreshing the page.');
            }
        });
    }

    // Render reporting structure mode
    function renderReportingMode(data) {
        if (orgChart && data) {
            orgChart.loadData(data);
            hideLoading();
        }
    }

    // Render career path mode
    function renderCareerPathMode(data) {
        // For career path mode, we'll modify the visualization to show career paths
        // This will be implemented in the next phase of org-chart-visualization.js
        // For now, use the reporting structure as base
        if (orgChart && data) {
            // TODO: Enhance org-chart-visualization.js to support career path links
            orgChart.loadData(data);
            hideLoading();

            // Show message that career path visualization is being rendered
            console.log('Career path mode - showing paths:', data.links);
        }
    }

    // Update mode info badge
    function updateModeInfo() {
        const $info = $('#modeInfo');
        if (currentMode === 'career_paths') {
            $info.addClass('career-mode');
            $info.html('<i class="bi bi-signpost-split"></i><span>Shows career advancement options (dashed lines)</span>');
        } else {
            $info.removeClass('career-mode');
            $info.html('<i class="bi bi-diagram-3"></i><span>Shows who reports to whom (solid lines)</span>');
        }
    }

    // Update legend based on mode
    function updateLegend(mode) {
        const $legend = $('#chartLegend');
        let html = '';

        if (mode === 'career_paths') {
            html = `
                <div class="legend-item">
                    <div class="legend-line dashed-green"></div>
                    <span>Vertical Technical (Same role, higher level)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-line dashed-blue"></div>
                    <span>Lateral Management (Management track)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-line dashed-orange"></div>
                    <span>Diagonal Hybrid (Different role + level up)</span>
                </div>
            `;
        } else {
            html = `
                <div class="legend-item">
                    <div class="legend-line solid"></div>
                    <span>Reports To (Organizational Hierarchy)</span>
                </div>
                <div class="legend-item">
                    <div style="width: 16px; height: 16px; background: #722ed1; border-radius: 50%;"></div>
                    <span>Has Career Paths Available</span>
                </div>
            `;
        }

        $legend.html(html);
    }

    // Show loading state
    function showLoading() {
        $('#chartContainer').html(`
            <div class="loading-container">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="loading-text">Loading ${currentMode === 'career_paths' ? 'career paths' : 'organization chart'}...</div>
            </div>
        `);
    }

    // Hide loading state
    function hideLoading() {
        $('.loading-container').remove();
    }

    // Show error state
    function showError(message) {
        $('#chartContainer').html(`
            <div class="empty-state">
                <i class="bi bi-exclamation-triangle"></i>
                <h3>Error Loading Chart</h3>
                <p>${message}</p>
                <button class="btn btn-primary mt-3" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise"></i> Retry
                </button>
            </div>
        `);
    }

    // Show career path details (when clicking a node in career path mode)
    window.showCareerPathDetails = function(positionId) {
        if (currentMode !== 'career_paths') return;

        $.ajax({
            url: '<?= base_url() ?>position/get_career_path_details?position_id=' + positionId,
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.paths && response.paths.length > 0) {
                    renderCareerPanel(response);
                    $('#careerPathPanel').addClass('open');
                } else {
                    console.log('No career paths available for this position');
                }
            }
        });
    };

    // Render career path panel content
    function renderCareerPanel(data) {
        $('#panelPositionName').text(data.position.name);
        $('#panelPositionLevel').text(data.position.level_name + ' Level');

        let html = '<h5 style="margin-bottom: 20px; color: #262626;">Career Advancement Options</h5>';

        data.paths.forEach((path, index) => {
            const pathTypeClass = path.path_type.includes('vertical') ? 'vertical' :
                                 path.path_type.includes('lateral') ? 'lateral' : 'diagonal';
            const pathTypeLabel = path.path_type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());

            html += `
                <div class="path-option ${pathTypeClass}">
                    <div class="path-header">
                        <h6 class="path-title">Option ${index + 1}: ${path.position_name}</h6>
                        <span class="path-type-badge ${pathTypeClass}">${pathTypeLabel}</span>
                    </div>
                    <div class="path-timeline">
                        <i class="bi bi-clock"></i>
                        <span>Timeline: ${path.estimated_timeline || 'N/A'}</span>
                    </div>
            `;

            if (path.requirements) {
                html += '<div class="requirements-section"><div class="requirements-title">Requirements</div>';

                if (path.requirements.experience_years) {
                    html += `<div class="requirement-item"><i class="bi bi-check-circle requirement-icon"></i><span>${path.requirements.experience_years}+ years of experience</span></div>`;
                }

                if (path.requirements.completed_projects) {
                    html += `<div class="requirement-item"><i class="bi bi-check-circle requirement-icon"></i><span>${path.requirements.completed_projects} completed projects</span></div>`;
                }

                if (path.requirements.skills && path.requirements.skills.length > 0) {
                    path.requirements.skills.forEach(skill => {
                        html += `<div class="requirement-item"><i class="bi bi-check-circle requirement-icon"></i><span>${skill}</span></div>`;
                    });
                }

                if (path.requirements.certifications && path.requirements.certifications.length > 0) {
                    path.requirements.certifications.forEach(cert => {
                        html += `<div class="requirement-item"><i class="bi bi-award requirement-icon"></i><span>${cert}</span></div>`;
                    });
                }

                html += '</div>';
            }

            html += '</div>';
        });

        $('#panelContent').html(html);
    }

    // Close career path panel
    window.closeCareerPanel = function() {
        $('#careerPathPanel').removeClass('open');
    };

    // Initialize on page load
    setTimeout(init, 100);
});
</script>
