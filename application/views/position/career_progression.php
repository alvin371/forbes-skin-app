<!-- Hierarchical Clustering Dendrogram for Career Progression -->
<style>
    /* Dendrogram Container Styles */
    .dendrogram-main-container {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        padding: 0;
        margin: 20px 0;
        box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
        overflow: hidden;
    }

    .dendrogram-header {
        background: rgba(255, 255, 255, 0.1);
        padding: 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
    }

    .dendrogram-header h4 {
        color: white;
        margin: 0;
        font-weight: 600;
        font-size: 24px;
    }

    .dendrogram-subtitle {
        color: rgba(255, 255, 255, 0.8);
        margin: 5px 0 0 0;
        font-size: 14px;
    }

    .dendrogram-visualization {
        background: white;
        min-height: 800px;
        position: relative;
        overflow: hidden;
    }

    .dendrogram-controls {
        background: #f8f9fa;
        padding: 20px;
        border-top: 1px solid #e9ecef;
        display: flex;
        gap: 15px;
        align-items: center;
        flex-wrap: wrap;
    }

    .control-group {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        background: white;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }

    .control-group label {
        font-weight: 500;
        color: #495057;
        margin: 0;
        font-size: 14px;
    }

    /* Clustering Statistics */
    .clustering-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin: 20px 0;
    }

    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        border: 1px solid #e9ecef;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea, #764ba2);
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    }

    .stat-value {
        font-size: 28px;
        font-weight: 700;
        color: #495057;
        margin-bottom: 8px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .stat-label {
        color: #6c757d;
        font-size: 14px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-description {
        color: #adb5bd;
        font-size: 12px;
        margin-top: 4px;
    }

    /* D3.js Dendrogram Specific Styles */
    .dendrogram-svg {
        width: 100%;
        height: 100%;
        background: radial-gradient(circle at center, #fafafa 0%, #f0f0f0 100%);
    }

    .dendrogram-node {
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .dendrogram-node:hover {
        filter: brightness(1.1);
    }

    .dendrogram-node.selected {
        filter: brightness(1.2) drop-shadow(0 0 8px rgba(102, 126, 234, 0.6));
    }

    .dendrogram-link {
        fill: none;
        stroke: #d1d5db;
        stroke-width: 2px;
        transition: all 0.3s ease;
    }

    .dendrogram-link.highlighted {
        stroke: #667eea;
        stroke-width: 3px;
    }

    .dendrogram-text {
        font: 12px 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        fill: #374151;
        pointer-events: none;
    }

    .dendrogram-tooltip {
        position: absolute;
        background: rgba(0, 0, 0, 0.9);
        color: white;
        padding: 12px 16px;
        border-radius: 8px;
        font-size: 12px;
        pointer-events: none;
        z-index: 1000;
        backdrop-filter: blur(4px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }

    /* Layout Control Buttons */
    .layout-controls {
        display: flex;
        gap: 8px;
    }

    .layout-btn {
        padding: 8px 16px;
        border: 1px solid #d1d5db;
        background: white;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 13px;
        font-weight: 500;
    }

    .layout-btn:hover {
        background: #f3f4f6;
        border-color: #9ca3af;
    }

    .layout-btn.active {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }

    /* Loading and Error States */
    .dendrogram-loading {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 400px;
        flex-direction: column;
        gap: 16px;
        color: #6c757d;
    }

    .dendrogram-error {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 400px;
        flex-direction: column;
        gap: 16px;
        color: #dc3545;
        text-align: center;
    }

    .loading-spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #e9ecef;
        border-top: 4px solid #667eea;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .dendrogram-controls {
            flex-direction: column;
            align-items: stretch;
        }

        .control-group {
            justify-content: space-between;
        }

        .clustering-stats {
            grid-template-columns: 1fr;
        }

        .layout-controls {
            width: 100%;
            justify-content: center;
        }
    }

    /* Enhanced Zoom Controls */
    .zoom-controls {
        position: absolute;
        top: 15px;
        right: 15px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        z-index: 100;
    }

    .zoom-btn {
        width: 36px;
        height: 36px;
        border: 1px solid #d1d5db;
        background: rgba(255, 255, 255, 0.9);
        border-radius: 6px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        backdrop-filter: blur(4px);
    }

    .zoom-btn:hover {
        background: rgba(102, 126, 234, 0.1);
        border-color: #667eea;
        color: #667eea;
    }

    /* Path Visualization */
    .career-path-highlight {
        stroke: #f59e0b;
        stroke-width: 4px;
        stroke-dasharray: 8, 4;
        animation: pathFlow 2s linear infinite;
    }

    @keyframes pathFlow {
        0% { stroke-dashoffset: 0; }
        100% { stroke-dashoffset: 24; }
    }

    /* Position Type Colors for Clustering */
    .position-type-ceo {
        fill: #722ed1;
        stroke: #5a1a9c;
    }

    .position-type-executive {
        fill: #1890ff;
        stroke: #0969da;
    }

    .position-type-manager {
        fill: #52c41a;
        stroke: #389e0d;
    }

    .position-type-specialist {
        fill: #fa8c16;
        stroke: #d46b08;
    }

    /* Clustering Distance Indicators */
    .cluster-distance-low {
        opacity: 0.9;
    }

    .cluster-distance-medium {
        opacity: 0.7;
    }

    .cluster-distance-high {
        opacity: 0.5;
    }

    /* Legacy styles kept for backward compatibility */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin: 20px 0;
    }

    /* Position Details Panel */
    .position-details-panel {
        background: white;
        border-radius: 12px;
        padding: 24px;
        margin: 20px 0;
        border: 1px solid #e9ecef;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        display: none;
    }

    .position-details-panel.show {
        display: block;
        animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .position-details-header {
        border-bottom: 2px solid #f0f0f0;
        padding-bottom: 16px;
        margin-bottom: 20px;
    }

    .position-details-title {
        color: #495057;
        font-size: 20px;
        font-weight: 600;
        margin: 0;
    }

    .position-details-subtitle {
        color: #6c757d;
        font-size: 14px;
        margin: 4px 0 0 0;
    }

    /* Career Path Analysis Panel */
    .career-path-analysis {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 12px;
        padding: 20px;
        margin: 20px 0;
    }

    .path-metrics {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 16px;
        margin-top: 16px;
    }

    .path-metric {
        background: white;
        padding: 16px;
        border-radius: 8px;
        text-align: center;
        border: 1px solid rgba(102, 126, 234, 0.2);
    }

    .path-metric-value {
        font-size: 20px;
        font-weight: 600;
        color: #667eea;
        margin-bottom: 4px;
    }

    .path-metric-label {
        font-size: 12px;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Export and Action Controls */
    .export-controls {
        background: white;
        border-radius: 8px;
        padding: 16px;
        margin: 20px 0;
        border: 1px solid #e9ecef;
        display: flex;
        gap: 12px;
        align-items: center;
        justify-content: flex-end;
    }

    .export-btn {
        padding: 8px 16px;
        border: 1px solid #667eea;
        background: white;
        color: #667eea;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .export-btn:hover {
        background: #667eea;
        color: white;
        text-decoration: none;
    }

    .export-btn i {
        font-size: 12px;
    }

    /* Algorithm Information Panel */
    .algorithm-info {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        border: 1px solid rgba(102, 126, 234, 0.2);
        border-radius: 12px;
        padding: 20px;
        margin: 20px 0;
    }

    .algorithm-title {
        color: #495057;
        font-size: 16px;
        font-weight: 600;
        margin: 0 0 12px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .algorithm-description {
        color: #6c757d;
        font-size: 14px;
        line-height: 1.5;
        margin-bottom: 16px;
    }

    .algorithm-features {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
    }

    .algorithm-feature {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: #495057;
    }

    .algorithm-feature i {
        color: #667eea;
        font-size: 12px;
        width: 16px;
    }

</style>

<!-- D3.js Library -->
<script src="https://d3js.org/d3.v7.min.js"></script>

<!-- Dendrogram Visualization Library -->
<script src="<?= base_url() ?>assets/js/career-dendrogram.js"></script>

<!-- Career Clustering Algorithm -->
<script src="<?= base_url() ?>assets/js/career-clustering.js"></script>

<div style="padding: 20px 0;">
    <!-- Algorithm Information Panel -->
    <div class="algorithm-info">
        <h4 class="algorithm-title">
            <i class="fas fa-project-diagram"></i>
            Hierarchical Clustering Dendrogram
        </h4>
        <p class="algorithm-description">
            This visualization uses advanced hierarchical clustering algorithms to create a dendrogram of career progression paths.
            The system analyzes position hierarchy, department similarity, and level progression to build optimal career advancement routes.
        </p>
        <div class="algorithm-features">
            <div class="algorithm-feature">
                <i class="fas fa-check"></i>
                Position order-based clustering (60% weight)
            </div>
            <div class="algorithm-feature">
                <i class="fas fa-check"></i>
                Department similarity analysis (20% weight)
            </div>
            <div class="algorithm-feature">
                <i class="fas fa-check"></i>
                Level progression scoring (20% weight)
            </div>
            <div class="algorithm-feature">
                <i class="fas fa-check"></i>
                Interactive D3.js visualization
            </div>
            <div class="algorithm-feature">
                <i class="fas fa-check"></i>
                Real-time clustering metrics
            </div>
            <div class="algorithm-feature">
                <i class="fas fa-check"></i>
                Export and analysis tools
            </div>
        </div>
    </div>

    <!-- Main Dendrogram Container -->
    <div class="dendrogram-main-container">
        <div class="dendrogram-header">
            <h4>Career Progression Dendrogram</h4>
            <p class="dendrogram-subtitle">Interactive hierarchical clustering visualization based on organizational structure</p>
        </div>

        <div class="dendrogram-visualization" id="dendrogramVisualization">
            <!-- D3.js visualization will be rendered here -->
        </div>

        <div class="dendrogram-controls">
            <div class="control-group">
                <label>Layout:</label>
                <div class="layout-controls">
                    <button class="layout-btn active" data-layout="tree">
                        <i class="fas fa-sitemap"></i> Tree
                    </button>
                    <button class="layout-btn" data-layout="cluster">
                        <i class="fas fa-project-diagram"></i> Cluster
                    </button>
                    <button class="layout-btn" data-layout="radial">
                        <i class="fas fa-sun"></i> Radial
                    </button>
                </div>
            </div>

            <div class="control-group">
                <label>
                    <input type="checkbox" id="showCareerPaths"> Show Career Paths
                </label>
            </div>

            <div class="control-group">
                <label>
                    <input type="checkbox" id="enableDrag"> Enable Drag
                </label>
            </div>

            <div class="control-group">
                <button class="btn btn-sm btn-outline-primary" id="centerDendrogram">
                    <i class="fas fa-crosshairs"></i> Center View
                </button>
            </div>
        </div>
    </div>

    <!-- Clustering Statistics -->
    <div class="clustering-stats">
        <div class="stat-card">
            <div class="stat-value" id="totalPositionsCount">0</div>
            <div class="stat-label">Total Positions</div>
            <div class="stat-description">Nodes in dendrogram</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" id="clustersGeneratedCount">0</div>
            <div class="stat-label">Clusters Generated</div>
            <div class="stat-description">Hierarchical groupings</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" id="maxDistanceValue">0.000</div>
            <div class="stat-label">Max Distance</div>
            <div class="stat-description">Clustering spread</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" id="departmentClustersCount">0</div>
            <div class="stat-label">Department Clusters</div>
            <div class="stat-description">Departmental groupings</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" id="averageDistanceValue">0.000</div>
            <div class="stat-label">Average Distance</div>
            <div class="stat-description">Clustering cohesion</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" id="silhouetteScore">0.000</div>
            <div class="stat-label">Silhouette Score</div>
            <div class="stat-description">Clustering quality</div>
        </div>
    </div>

    <!-- Position Details Panel -->
    <div class="position-details-panel" id="positionDetailsPanel">
        <div class="position-details-header">
            <h5 class="position-details-title" id="positionDetailsTitle">Position Details</h5>
            <p class="position-details-subtitle" id="positionDetailsSubtitle">Click on a position node to view details</p>
        </div>
        <div id="positionDetailsContent">
            <!-- Content will be populated when a position is selected -->
        </div>
    </div>

    <!-- Career Path Analysis Panel -->
    <div class="career-path-analysis" id="careerPathAnalysis" style="display: none;">
        <h5>Career Path Analysis</h5>
        <p id="careerPathDescription">Select a position to analyze possible career progression paths</p>
        <div class="path-metrics" id="pathMetrics">
            <!-- Path metrics will be populated here -->
        </div>
    </div>

    <!-- Export Controls -->
    <div class="export-controls">
        <button class="export-btn" id="exportPNG">
            <i class="fas fa-download"></i> Export as PNG
        </button>
        <button class="export-btn" id="exportSVG">
            <i class="fas fa-file-code"></i> Export as SVG
        </button>
        <button class="export-btn" id="exportMetrics">
            <i class="fas fa-chart-bar"></i> Export Metrics
        </button>
        <button class="export-btn" id="refreshVisualization">
            <i class="fas fa-sync"></i> Refresh Data
        </button>
    </div>

</div>

<!-- JavaScript for Hierarchical Clustering Dendrogram -->
<script>
$(document).ready(function() {
    // Global variables
    let dendrogram = null;
    let clustering = null;
    let selectedPositionId = null;
    let positionsData = [];
    let clusteringData = null;

    // Initialize dendrogram
    initializeDendrogram();

    /**
     * Initialize the dendrogram visualization
     */
    function initializeDendrogram() {
        console.log('Initializing hierarchical clustering dendrogram...');

        // Show loading state
        showLoadingState();

        // Create dendrogram instance
        dendrogram = new CareerDendrogram('dendrogramVisualization', {
            width: 1200,
            height: 800,
            enableZoom: true,
            enableTooltip: true,
            layout: 'tree',
            colorScheme: 'modern'
        });

        // Load data from backend
        loadDendrogramData();

        // Setup event handlers
        setupEventHandlers();
    }

    /**
     * Load dendrogram data from backend
     */
    function loadDendrogramData() {
        $.ajax({
            url: '<?= base_url() ?>position/get_dendrogram_data',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log('Dendrogram data loaded:', response);

                if (response.success) {
                    positionsData = response.flat_data || [];
                    clusteringData = response.data;

                    // Update statistics
                    updateClusteringStatistics(response.clustering_metadata);

                    // Load data into dendrogram
                    if (dendrogram.loadData(positionsData)) {
                        console.log('Dendrogram visualization rendered successfully');
                        hideLoadingState();

                        // Initialize clustering analysis
                        initializeClustering();
                    } else {
                        showErrorState('Failed to render dendrogram visualization');
                    }
                } else {
                    showErrorState(response.message || 'Failed to load dendrogram data');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading dendrogram data:', error);
                showErrorState('Network error: Failed to load dendrogram data');
            }
        });
    }

    /**
     * Initialize clustering analysis
     */
    function initializeClustering() {
        if (!positionsData || positionsData.length === 0) {
            console.warn('No positions data available for clustering');
            return;
        }

        try {
            clustering = new CareerClustering({
                positionOrderWeight: 0.6,
                departmentWeight: 0.2,
                levelWeight: 0.2,
                maxPathLength: 5
            });

            const clusteringResults = clustering.loadPositions(positionsData);
            console.log('Clustering analysis completed:', clusteringResults);

            // Load clustering metrics
            loadClusteringMetrics();

        } catch (error) {
            console.error('Error initializing clustering:', error);
        }
    }

    /**
     * Load clustering metrics
     */
    function loadClusteringMetrics() {
        $.ajax({
            url: '<?= base_url() ?>position/get_clustering_metrics',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    updateClusteringMetrics(response.metrics);
                }
            },
            error: function(error) {
                console.error('Error loading clustering metrics:', error);
            }
        });
    }

    /**
     * Setup event handlers
     */
    function setupEventHandlers() {
        // Layout switching
        $('.layout-btn').click(function() {
            const layout = $(this).data('layout');
            $('.layout-btn').removeClass('active');
            $(this).addClass('active');

            if (dendrogram) {
                dendrogram.setLayout(layout);
            }
        });

        // Career paths toggle
        $('#showCareerPaths').change(function() {
            const showPaths = $(this).is(':checked');
            if (dendrogram) {
                dendrogram.pathsVisible = showPaths;
                // Re-render to show/hide paths
                dendrogram.render();
            }
        });

        // Drag toggle
        $('#enableDrag').change(function() {
            const enableDrag = $(this).is(':checked');
            if (dendrogram) {
                dendrogram.config.enableDrag = enableDrag;
            }
        });

        // Center dendrogram
        $('#centerDendrogram').click(function() {
            if (dendrogram) {
                dendrogram.centerDendrogram();
            }
        });

        // Export functionality
        $('#exportPNG').click(function() {
            if (dendrogram) {
                dendrogram.exportAsPNG('career-dendrogram.png');
            }
        });

        $('#exportSVG').click(function() {
            if (dendrogram && dendrogram.svg) {
                const svgElement = dendrogram.svg.node();
                const svgData = new XMLSerializer().serializeToString(svgElement);
                const blob = new Blob([svgData], { type: 'image/svg+xml' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = 'career-dendrogram.svg';
                link.click();
                URL.revokeObjectURL(url);
            }
        });

        $('#exportMetrics').click(function() {
            exportClusteringMetrics();
        });

        $('#refreshVisualization').click(function() {
            refreshVisualization();
        });

        // Node selection event
        document.addEventListener('nodeSelected', function(event) {
            const nodeData = event.detail.data;
            handleNodeSelection(nodeData);
        });
    }

    /**
     * Handle node selection
     */
    function handleNodeSelection(nodeData) {
        console.log('Node selected:', nodeData);

        if (!nodeData.isPosition) {
            // Hide position details for non-position nodes
            $('#positionDetailsPanel').removeClass('show');
            $('#careerPathAnalysis').hide();
            return;
        }

        selectedPositionId = nodeData.id;

        // Show position details
        showPositionDetails(nodeData);

        // Analyze career paths
        analyzeCareerPaths(nodeData);
    }

    /**
     * Show position details
     */
    function showPositionDetails(nodeData) {
        const position = nodeData.data;

        $('#positionDetailsTitle').text(position.name);
        $('#positionDetailsSubtitle').text(`${position.department} • ${position.level_name}`);

        let html = '<div class="row">';

        // Basic Information
        html += '<div class="col-md-6">';
        html += '<h6><i class="fas fa-info-circle"></i> Position Information</h6>';
        html += '<table class="table table-sm">';
        html += `<tr><td><strong>Department:</strong></td><td>${position.department}</td></tr>`;
        html += `<tr><td><strong>Level:</strong></td><td>${position.level_name}</td></tr>`;
        html += `<tr><td><strong>Position Order:</strong></td><td>${position.position_order}</td></tr>`;
        html += `<tr><td><strong>Leadership:</strong></td><td>${position.is_leadership ? 'Yes' : 'No'}</td></tr>`;
        html += '</table>';
        html += '</div>';

        // Career Path Statistics
        html += '<div class="col-md-6">';
        html += '<h6><i class="fas fa-chart-line"></i> Career Path Statistics</h6>';
        html += '<table class="table table-sm">';
        html += `<tr><td><strong>Outgoing Paths:</strong></td><td>${position.outgoing_paths}</td></tr>`;
        html += `<tr><td><strong>Incoming Paths:</strong></td><td>${position.incoming_paths}</td></tr>`;
        html += `<tr><td><strong>Employee Count:</strong></td><td>${position.employee_count}</td></tr>`;
        html += '</table>';
        html += '</div>';

        html += '</div>';

        // Distance Metrics
        if (nodeData.distance_metrics && nodeData.distance_metrics.length > 0) {
            html += '<div class="mt-3">';
            html += '<h6><i class="fas fa-ruler"></i> Closest Positions (Clustering Distance)</h6>';
            html += '<div class="table-responsive">';
            html += '<table class="table table-sm table-striped">';
            html += '<thead><tr><th>Position</th><th>Distance</th><th>Order Diff</th><th>Dept. Similarity</th></tr></thead>';
            html += '<tbody>';

            nodeData.distance_metrics.forEach(metric => {
                html += '<tr>';
                html += `<td>${metric.target_name}</td>`;
                html += `<td><span class="badge bg-primary">${metric.distance}</span></td>`;
                html += `<td>${metric.order_diff}</td>`;
                html += `<td>${metric.dept_similarity === 0 ? 'Same Dept' : 'Different'}</td>`;
                html += '</tr>';
            });

            html += '</tbody></table>';
            html += '</div>';
            html += '</div>';
        }

        $('#positionDetailsContent').html(html);
        $('#positionDetailsPanel').addClass('show');
    }

    /**
     * Analyze career paths for selected position
     */
    function analyzeCareerPaths(nodeData) {
        if (!selectedPositionId) return;

        $.ajax({
            url: '<?= base_url() ?>position/calculate_career_paths',
            method: 'GET',
            data: {
                source_position_id: selectedPositionId,
                max_paths: 10
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showCareerPathAnalysis(nodeData, response.paths);
                } else {
                    console.error('Error calculating career paths:', response.message);
                }
            },
            error: function(error) {
                console.error('Error loading career paths:', error);
            }
        });
    }

    /**
     * Show career path analysis
     */
    function showCareerPathAnalysis(nodeData, paths) {
        $('#careerPathDescription').text(`Career progression analysis for ${nodeData.data.name}`);

        // Calculate metrics
        const avgViability = paths.length > 0 ?
            (paths.reduce((sum, path) => sum + path.viability_score, 0) / paths.length).toFixed(2) : 0;
        const avgTimeframe = paths.length > 0 ?
            Math.round(paths.reduce((sum, path) => sum + path.estimated_timeframe, 0) / paths.length) : 0;
        const avgDifficulty = paths.length > 0 ?
            (paths.reduce((sum, path) => sum + path.difficulty_level, 0) / paths.length).toFixed(1) : 0;

        let metricsHtml = '';
        metricsHtml += `<div class="path-metric">`;
        metricsHtml += `<div class="path-metric-value">${paths.length}</div>`;
        metricsHtml += `<div class="path-metric-label">Available Paths</div>`;
        metricsHtml += `</div>`;

        metricsHtml += `<div class="path-metric">`;
        metricsHtml += `<div class="path-metric-value">${avgViability}</div>`;
        metricsHtml += `<div class="path-metric-label">Avg Viability</div>`;
        metricsHtml += `</div>`;

        metricsHtml += `<div class="path-metric">`;
        metricsHtml += `<div class="path-metric-value">${avgTimeframe}mo</div>`;
        metricsHtml += `<div class="path-metric-label">Avg Timeframe</div>`;
        metricsHtml += `</div>`;

        metricsHtml += `<div class="path-metric">`;
        metricsHtml += `<div class="path-metric-value">${avgDifficulty}/10</div>`;
        metricsHtml += `<div class="path-metric-label">Avg Difficulty</div>`;
        metricsHtml += `</div>`;

        $('#pathMetrics').html(metricsHtml);

        // Show top paths
        if (paths.length > 0) {
            let pathsHtml = '<div class="mt-3"><h6>Top Career Paths</h6>';
            pathsHtml += '<div class="table-responsive">';
            pathsHtml += '<table class="table table-sm">';
            pathsHtml += '<thead><tr><th>Target Position</th><th>Type</th><th>Viability</th><th>Timeframe</th><th>Difficulty</th></tr></thead>';
            pathsHtml += '<tbody>';

            paths.slice(0, 5).forEach(path => {
                pathsHtml += '<tr>';
                pathsHtml += `<td>${path.target_position_name}</td>`;
                pathsHtml += `<td><span class="badge bg-info">${path.progression_type}</span></td>`;
                pathsHtml += `<td><span class="badge bg-success">${(path.viability_score * 100).toFixed(0)}%</span></td>`;
                pathsHtml += `<td>${path.estimated_timeframe} months</td>`;
                pathsHtml += `<td><span class="badge bg-warning">${path.difficulty_level}/10</span></td>`;
                pathsHtml += '</tr>';
            });

            pathsHtml += '</tbody></table></div></div>';
            $('#pathMetrics').append(pathsHtml);
        }

        $('#careerPathAnalysis').show();
    }

    /**
     * Update clustering statistics
     */
    function updateClusteringStatistics(metadata) {
        if (!metadata) return;

        $('#totalPositionsCount').text(metadata.total_positions || 0);
        $('#clustersGeneratedCount').text(metadata.clusters_generated || 0);
        $('#maxDistanceValue').text((metadata.max_distance || 0).toFixed(3));
        $('#departmentClustersCount').text(metadata.departments ? metadata.departments.length : 0);
    }

    /**
     * Update clustering metrics
     */
    function updateClusteringMetrics(metrics) {
        if (!metrics) return;

        $('#averageDistanceValue').text((metrics.average_distance || 0).toFixed(3));
        $('#silhouetteScore').text((metrics.silhouette_score || 0).toFixed(3));
    }

    /**
     * Export clustering metrics
     */
    function exportClusteringMetrics() {
        if (!clustering) {
            alert('No clustering data available for export');
            return;
        }

        const results = clustering.exportResults();
        const dataStr = JSON.stringify(results, null, 2);
        const blob = new Blob([dataStr], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'clustering-metrics.json';
        link.click();
        URL.revokeObjectURL(url);
    }

    /**
     * Refresh visualization
     */
    function refreshVisualization() {
        showLoadingState();
        selectedPositionId = null;
        $('#positionDetailsPanel').removeClass('show');
        $('#careerPathAnalysis').hide();

        // Reload data
        loadDendrogramData();
    }

    /**
     * Show loading state
     */
    function showLoadingState() {
        $('#dendrogramVisualization').html(`
            <div class="dendrogram-loading">
                <div class="loading-spinner"></div>
                <h6>Loading Hierarchical Clustering Dendrogram...</h6>
                <p>Analyzing position hierarchy and calculating clustering distances</p>
            </div>
        `);
    }

    /**
     * Hide loading state
     */
    function hideLoadingState() {
        // Loading state is automatically hidden when dendrogram renders
    }

    /**
     * Show error state
     */
    function showErrorState(message) {
        $('#dendrogramVisualization').html(`
            <div class="dendrogram-error">
                <i class="fas fa-exclamation-triangle fa-3x"></i>
                <h6>Dendrogram Loading Error</h6>
                <p>${message}</p>
                <button class="btn btn-outline-danger btn-sm" onclick="location.reload()">
                    <i class="fas fa-refresh"></i> Reload Page
                </button>
            </div>
        `);
    }

    // Make functions globally available for debugging
    window.dendrogramInstance = dendrogram;
    window.clusteringInstance = clustering;
    window.refreshDendrogram = refreshVisualization;

    console.log('Hierarchical clustering dendrogram initialization completed');
});
</script>