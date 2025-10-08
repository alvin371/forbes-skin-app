<style>
    /* Organization Chart Styling */
    .org-chart-page {
        min-height: 100vh;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        padding: 20px;
    }

    .org-chart-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    .org-chart-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 24px 32px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }

    .org-chart-header h2 {
        margin: 0;
        font-size: 28px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .org-chart-controls {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .control-group {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .control-group label {
        color: white;
        font-weight: 500;
        margin: 0;
        font-size: 14px;
    }

    .org-chart-body {
        padding: 24px;
    }

    .org-chart-container {
        border: 2px solid #e8e8e8;
        border-radius: 8px;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        overflow: hidden;
        position: relative;
        box-shadow: inset 0 2px 8px rgba(0,0,0,0.06);
    }

    .org-chart-svg {
        display: block;
        cursor: grab;
    }

    .org-chart-svg:active {
        cursor: grabbing;
    }

    .org-chart-actions {
        padding: 16px 24px;
        background: #fafafa;
        border-top: 1px solid #e8e8e8;
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
    }

    .action-buttons {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .org-chart-legend {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        padding: 20px 24px;
        background: #f9f9f9;
        border-top: 1px solid #e8e8e8;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: #595959;
    }

    .legend-color {
        width: 24px;
        height: 24px;
        border-radius: 4px;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
        margin-bottom: 4px;
    }

    .stat-label {
        font-size: 14px;
        color: #8c8c8c;
        font-weight: 500;
    }

    /* Custom select styling */
    .custom-select {
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 14px;
        min-width: 140px;
        transition: all 0.3s ease;
    }

    .custom-select:hover {
        background: rgba(255, 255, 255, 0.3);
        border-color: rgba(255, 255, 255, 0.5);
    }

    .custom-select:focus {
        background: rgba(255, 255, 255, 0.4);
        border-color: white;
        outline: none;
        box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
    }

    .custom-select option {
        color: #262626;
        background: white;
    }

    /* Button enhancements */
    .btn-light-custom {
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        transition: all 0.3s ease;
    }

    .btn-light-custom:hover {
        background: rgba(255, 255, 255, 0.3);
        border-color: rgba(255, 255, 255, 0.5);
        color: white;
        transform: translateY(-1px);
    }

    /* Loading state */
    .loading-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 600px;
        gap: 20px;
    }

    .loading-text {
        font-size: 16px;
        color: #8c8c8c;
        font-weight: 500;
    }

    /* Error state */
    .error-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 600px;
        gap: 20px;
        color: #ff4d4f;
    }

    .error-icon {
        font-size: 64px;
        opacity: 0.5;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .org-chart-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .org-chart-controls {
            width: 100%;
        }

        .control-group {
            flex: 1;
        }

        .custom-select {
            width: 100%;
        }

        .org-chart-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .action-buttons {
            flex-direction: column;
        }

        .action-buttons .btn {
            width: 100%;
        }
    }
</style>

<div class="org-chart-page">
    <!-- Statistics -->
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

    <!-- Main Chart Card -->
    <div class="org-chart-card">
        <!-- Header -->
        <div class="org-chart-header">
            <h2>
                <i class="bi bi-diagram-3"></i>
                Organization Chart
            </h2>

            <div class="org-chart-controls">
                <div class="control-group">
                    <label for="layoutSelect">Layout:</label>
                    <select id="layoutSelect" class="custom-select">
                        <option value="vertical">Vertical Tree</option>
                        <option value="horizontal">Horizontal Tree</option>
                        <option value="radial">Radial Dendrogram</option>
                    </select>
                </div>

                <div class="control-group">
                    <label for="colorModeSelect">Color By:</label>
                    <select id="colorModeSelect" class="custom-select">
                        <option value="department">Department</option>
                        <option value="level">Level</option>
                        <option value="hiring">Hiring Status</option>
                    </select>
                </div>

                <button class="btn btn-light-custom btn-sm" id="refreshBtn" title="Refresh Chart">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>

        <!-- Chart Container -->
        <div class="org-chart-body">
            <div id="orgChartContainer" class="org-chart-container">
                <!-- Chart will be rendered here -->
            </div>
        </div>

        <!-- Legend -->
        <div class="org-chart-legend" id="legendContainer">
            <!-- Legend items will be populated based on color mode -->
        </div>

        <!-- Actions -->
        <div class="org-chart-actions">
            <div class="action-buttons">
                <button class="btn btn-outline-primary btn-sm" id="expandAllBtn">
                    <i class="bi bi-arrows-expand"></i> Expand All
                </button>
                <button class="btn btn-outline-secondary btn-sm" id="collapseAllBtn">
                    <i class="bi bi-arrows-collapse"></i> Collapse All
                </button>
                <button class="btn btn-outline-info btn-sm" id="centerBtn">
                    <i class="bi bi-bullseye"></i> Center View
                </button>
            </div>

            <div class="action-buttons">
                <button class="btn btn-outline-success btn-sm" id="exportPngBtn">
                    <i class="bi bi-download"></i> Export PNG
                </button>
                <button class="btn btn-outline-warning btn-sm" id="exportSvgBtn">
                    <i class="bi bi-file-earmark-code"></i> Export SVG
                </button>
                <button class="btn btn-outline-dark btn-sm" id="exportJsonBtn">
                    <i class="bi bi-file-earmark-text"></i> Export JSON
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Include D3.js v7 -->
<script src="https://d3js.org/d3.v7.min.js"></script>

<!-- Include org chart visualization -->
<script src="<?= base_url() ?>assets/js/org-chart-visualization.js"></script>

<script>
$(document).ready(function() {
    let orgChart = null;
    let chartData = null;
    let metadata = null;

    // Initialize the organization chart
    function initOrgChart() {
        showLoading();

        // Create org chart instance
        orgChart = new OrgChartVisualization('orgChartContainer', {
            width: $('#orgChartContainer').width() || 1400,
            height: 900,
            layout: $('#layoutSelect').val(),
            colorMode: $('#colorModeSelect').val(),
            enableZoom: true,
            enableTooltip: true,
            nodeRadius: 8,
            fontSize: 13
        });

        // Load data
        loadChartData();
    }

    // Load chart data from API
    function loadChartData() {
        $.ajax({
            url: '<?= base_url() ?>position/get_org_chart_data',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log('Org chart data loaded:', response);

                if (response.success) {
                    chartData = response.data;
                    metadata = response.metadata;

                    // Render chart
                    if (orgChart.loadData(chartData)) {
                        updateStatistics();
                        updateLegend();
                        $('#statsContainer').fadeIn();
                    } else {
                        showError('Failed to render organization chart');
                    }
                } else {
                    showError(response.message || 'Failed to load chart data');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error Details:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    statusCode: xhr.status
                });

                // Better error messages
                let errorMessage = 'Network error: Unable to load chart data';
                if (xhr.status === 403) {
                    errorMessage = 'Access denied. Please check your permissions.';
                } else if (xhr.status === 404) {
                    errorMessage = 'API endpoint not found. Please contact support.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error. Please check the error logs.';
                } else if (xhr.status === 0) {
                    errorMessage = 'Network connection failed. Please check your internet connection.';
                } else if (xhr.responseText && xhr.responseText.includes('<!DOCTYPE')) {
                    errorMessage = 'Session expired. Please login again.';
                    setTimeout(function() {
                        window.location.href = '<?= base_url() ?>auth/login';
                    }, 2000);
                }

                showError(errorMessage);
            }
        });
    }

    // Update statistics
    function updateStatistics() {
        if (!metadata) return;

        $('#totalPositions').text(metadata.total_positions || 0);
        $('#departmentCount').text(metadata.department_count || 0);
        $('#hiringCount').text(metadata.hiring_positions || 0);
        $('#leadershipCount').text(metadata.leadership_positions || 0);
    }

    // Update legend based on color mode
    function updateLegend() {
        const colorMode = $('#colorModeSelect').val();
        const $legend = $('#legendContainer');
        $legend.empty();

        const legends = {
            department: [
                { color: '#722ed1', label: 'Executive' },
                { color: '#1890ff', label: 'HR Management' },
                { color: '#52c41a', label: 'Marketing' },
                { color: '#fa8c16', label: 'Operations' },
                { color: '#eb2f96', label: 'Finance' },
                { color: '#13c2c2', label: 'IT' },
                { color: '#faad14', label: 'Sales' },
                { color: '#8c8c8c', label: 'Other' }
            ],
            level: [
                { color: '#722ed1', label: 'Senior' },
                { color: '#1890ff', label: 'Intermediate' },
                { color: '#52c41a', label: 'Junior' },
                { color: '#8c8c8c', label: 'Not Assigned' }
            ],
            hiring: [
                { color: '#722ed1', label: 'Leadership' },
                { color: '#52c41a', label: 'Currently Hiring' },
                { color: '#8c8c8c', label: 'Not Hiring' }
            ]
        };

        const items = legends[colorMode] || [];

        items.forEach(item => {
            $legend.append(`
                <div class="legend-item">
                    <div class="legend-color" style="background-color: ${item.color};"></div>
                    <span>${item.label}</span>
                </div>
            `);
        });
    }

    // Event handlers
    $('#layoutSelect').on('change', function() {
        if (orgChart) {
            orgChart.setLayout($(this).val());
        }
    });

    $('#colorModeSelect').on('change', function() {
        if (orgChart) {
            orgChart.setColorMode($(this).val());
            updateLegend();
        }
    });

    $('#refreshBtn').on('click', function() {
        loadChartData();
    });

    $('#expandAllBtn').on('click', function() {
        if (orgChart) {
            orgChart.expandAll();
        }
    });

    $('#collapseAllBtn').on('click', function() {
        if (orgChart) {
            orgChart.collapseAll();
        }
    });

    $('#centerBtn').on('click', function() {
        if (orgChart) {
            orgChart.centerChart();
        }
    });

    $('#exportPngBtn').on('click', function() {
        if (orgChart) {
            orgChart.exportAsPNG('organization-chart.png');
        }
    });

    $('#exportSvgBtn').on('click', function() {
        if (orgChart && orgChart.svg) {
            const svgElement = orgChart.svg.node();
            const svgData = new XMLSerializer().serializeToString(svgElement);
            const blob = new Blob([svgData], { type: 'image/svg+xml' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'organization-chart.svg';
            link.click();
            URL.revokeObjectURL(url);
        }
    });

    $('#exportJsonBtn').on('click', function() {
        if (chartData) {
            const dataStr = JSON.stringify(chartData, null, 2);
            const blob = new Blob([dataStr], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'organization-chart-data.json';
            link.click();
            URL.revokeObjectURL(url);
        }
    });

    // Listen for node click events
    document.addEventListener('nodeClicked', function(event) {
        const nodeData = event.detail.data;
        console.log('Node clicked:', nodeData);

        // You can add custom actions here, e.g., show modal with position details
    });

    // Helper functions
    function showLoading() {
        $('#orgChartContainer').html(`
            <div class="loading-container">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="loading-text">Loading organization chart...</div>
            </div>
        `);
    }

    function showError(message) {
        $('#orgChartContainer').html(`
            <div class="error-container">
                <div class="error-icon">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div style="font-size: 18px; font-weight: 600;">${message}</div>
                <button class="btn btn-outline-danger" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise"></i> Reload Page
                </button>
            </div>
        `);
    }

    // Handle window resize
    let resizeTimer;
    $(window).on('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (orgChart) {
                orgChart.centerChart();
            }
        }, 250);
    });

    // Initialize on page load
    initOrgChart();
});
</script>
