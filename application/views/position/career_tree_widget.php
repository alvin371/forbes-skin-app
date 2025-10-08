<!-- Hierarchical Clustering Dendrogram Widget -->
<div class="career-tree-widget">
    <div class="career-tree-widget-header">
        <h5>
            <i class="fas fa-sitemap"></i>
            Hierarchical Clustering Dendrogram
        </h5>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-light" id="refreshTreeBtn" title="Refresh Tree">
                <i class="fas fa-sync-alt"></i>
            </button>
            <div class="dropdown">
                <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="treeViewDropdown" data-bs-toggle="dropdown">
                    <i class="fas fa-eye"></i> View
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item tree-layout-option active" href="#" data-layout="tree">
                            <i class="fas fa-sitemap me-2"></i>Tree Layout
                        </a></li>
                    <li><a class="dropdown-item tree-layout-option" href="#" data-layout="cluster">
                            <i class="fas fa-project-diagram me-2"></i>Cluster Layout
                        </a></li>
                    <li><a class="dropdown-item tree-layout-option" href="#" data-layout="radial">
                            <i class="fas fa-sun me-2"></i>Radial Layout
                        </a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="career-tree-widget-content">
        <!-- Dendrogram Visualization Container -->
        <div id="careerTreeContainer" class="career-tree-container">
            <!-- Hierarchical clustering dendrogram will be rendered here -->
        </div>

        <!-- Tree Info Panel -->
        <div id="treeInfoPanel" class="mt-3" style="display: none;">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle"></i>
                        Selected Node Details
                    </h6>
                </div>
                <div class="card-body" id="treeInfoContent">
                    <!-- Selected node details will be displayed here -->
                </div>
            </div>
        </div>
    </div>

    <div class="career-tree-widget-actions">
        <button class="btn btn-outline-secondary btn-sm" id="exportTreeBtn">
            <i class="fas fa-download"></i> Export PNG
        </button>
        <button class="btn btn-outline-success btn-sm" id="exportSVGBtn">
            <i class="fas fa-file-code"></i> Export SVG
        </button>
        <button class="btn btn-outline-info btn-sm" id="exportMetricsBtn">
            <i class="fas fa-chart-bar"></i> Export Metrics
        </button>
        <button class="btn btn-outline-primary btn-sm" id="centerTreeBtn">
            <i class="fas fa-crosshairs"></i> Center View
        </button>
        <?php if ($can_create ?? false): ?>
            <button class="btn btn-primary btn-sm" id="addCareerPathBtn">
                <i class="fas fa-plus"></i> Add Career Path
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Career Tree Statistics -->
<div class="row mt-4" id="treeStatsRow" style="display: none;">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h4 class="text-primary mb-2" id="totalPositionsCount">0</h4>
                <p class="card-text mb-0">Nodes in Dendrogram</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h4 class="text-success mb-2" id="totalClustersCount">0</h4>
                <p class="card-text mb-0">Clusters Generated</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h4 class="text-info mb-2" id="maxDistanceValue">0.000</h4>
                <p class="card-text mb-0">Max Distance</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h4 class="text-warning mb-2" id="avgDistanceValue">0.000</h4>
                <p class="card-text mb-0">Avg Distance</p>
            </div>
        </div>
    </div>
</div>

<!-- Career Path Quick Add Modal -->
<div class="modal fade" id="quickAddCareerPathModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle"></i>
                    Quick Add Career Path
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="quickAddCareerPathForm">
                    <input type="hidden" id="quickSourcePositionId" name="source_position_id">

                    <div class="mb-3">
                        <label for="quickSourcePosition" class="form-label">From Position</label>
                        <input type="text" class="form-control" id="quickSourcePosition" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="quickTargetPosition" class="form-label">To Position</label>
                        <select class="form-select" id="quickTargetPosition" name="target_position_id" required>
                            <option value="">Select target position...</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="quickEstimatedMonths" class="form-label">Estimated Timeline (months)</label>
                        <input type="number" class="form-control" id="quickEstimatedMonths"
                            name="estimated_months" min="1" max="120" placeholder="e.g., 12">
                    </div>

                    <div class="mb-3">
                        <label for="quickRequirements" class="form-label">Requirements</label>
                        <textarea class="form-control" id="quickRequirements" name="requirements"
                            rows="3" placeholder="Enter career progression requirements..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveQuickCareerPath">
                    <i class="fas fa-save"></i> Save Career Path
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Additional styles specific to this widget */
    .career-tree-widget {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .career-tree-widget-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 16px 20px;
        border-radius: 8px 8px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .career-tree-widget-header h5 {
        margin: 0;
        font-weight: 600;
    }

    .career-tree-container {
        position: relative;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        border-radius: 8px;
        overflow: hidden;
        min-height: 500px;
    }

    .tree-layout-option.active {
        background-color: #e9ecef;
        font-weight: 600;
    }

    .tree-layout-option:hover {
        background-color: #f8f9fa;
    }

    #treeInfoPanel .card {
        border: none;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    #treeInfoPanel .card-header {
        background: linear-gradient(90deg, #667eea, #764ba2);
        color: white;
    }

    .career-tree-widget-actions {
        padding: 16px 20px;
        background: #f8f9fa;
        border-radius: 0 0 8px 8px;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .career-tree-widget-actions .btn {
        transition: all 0.2s ease;
    }

    .career-tree-widget-actions .btn:hover {
        transform: translateY(-1px);
    }

    /* Tree statistics cards */
    #treeStatsRow .card {
        border: none;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s ease;
    }

    #treeStatsRow .card:hover {
        transform: translateY(-2px);
    }

    /* Loading state */
    .tree-loading {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 400px;
        flex-direction: column;
        gap: 16px;
    }

    .tree-loading .loading-text {
        color: #666;
        font-size: 14px;
    }

    /* Error state */
    .tree-error {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 400px;
        flex-direction: column;
        gap: 16px;
        color: #dc3545;
    }

    .tree-error i {
        font-size: 48px;
        opacity: 0.5;
    }

    /* D3.js specific styles */
    .career-tree-svg .node {
        cursor: pointer;
    }

    .career-tree-svg .node circle {
        fill: #fff;
        stroke: #1890ff;
        stroke-width: 2px;
        transition: all 0.3s ease;
    }

    .career-tree-svg .node:hover circle {
        stroke: #40a9ff;
        stroke-width: 3px;
    }

    .career-tree-svg .node.selected circle {
        fill: #e6f7ff;
        stroke: #1890ff;
        stroke-width: 3px;
    }

    .career-tree-svg .node text {
        font: 12px sans-serif;
        fill: #333;
    }

    .career-tree-svg .link {
        fill: none;
        stroke: #999;
        stroke-width: 1.5px;
    }

    /* Fallback tree styling */
    .tree-position:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border-color: #1890ff !important;
    }

    .tree-position.selected {
        border-color: #1890ff !important;
        background-color: #e6f7ff !important;
    }

    .tree-position.ceo-level {
        border-color: #722ed1;
        background: linear-gradient(135deg, #f9f0ff 0%, #f0e6ff 100%);
    }

    .tree-position.exec-level {
        border-color: #1890ff;
        background: linear-gradient(135deg, #e6f7ff 0%, #d6efff 100%);
    }

    .tree-position.manager-level {
        border-color: #52c41a;
        background: linear-gradient(135deg, #f6ffed 0%, #e6f7e6 100%);
    }

    .tree-position.specialist-level {
        border-color: #fa8c16;
        background: linear-gradient(135deg, #fff7e6 0%, #ffefd6 100%);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .career-tree-widget-header {
            flex-direction: column;
            gap: 12px;
            text-align: center;
        }

        .career-tree-widget-actions {
            flex-direction: column;
            gap: 8px;
        }

        #treeStatsRow .col-md-3 {
            margin-bottom: 15px;
        }

        .tree-nodes {
            flex-direction: column !important;
        }

        .tree-position {
            min-width: auto !important;
            width: 100%;
        }
    }
</style>

<script>
$(document).ready(function() {
    // Hierarchical Clustering Dendrogram Widget Manager
    window.DendrogramWidget = {
        dendrogram: null,
        clustering: null,
        data: null,
        isInitialized: false,
        selectedLayout: 'tree',

        // Initialize the dendrogram widget
        init: function() {
            console.log('Initializing Hierarchical Clustering Dendrogram Widget...');

            try {
                // Check if required libraries are available
                if (typeof CareerDendrogram === 'undefined') {
                    console.error('CareerDendrogram class not found');
                    this.showError('Required visualization libraries not loaded');
                    return;
                }

                // Create dendrogram instance
                this.dendrogram = new CareerDendrogram('careerTreeContainer', {
                    width: 800,
                    height: 500,
                    enableZoom: true,
                    enableTooltip: true,
                    layout: this.selectedLayout,
                    colorScheme: 'modern',
                    fontSize: 10,
                    nodeRadius: 6
                });

                // Initialize clustering analysis
                if (typeof CareerClustering !== 'undefined') {
                    this.clustering = new CareerClustering({
                        positionOrderWeight: 0.6,
                        departmentWeight: 0.2,
                        levelWeight: 0.2
                    });
                }

                this.isInitialized = true;
                this.setupEventListeners();

                // Load data
                this.loadData();

            } catch (error) {
                console.error('Dendrogram widget initialization failed:', error);
                this.showError('Failed to initialize dendrogram widget: ' + error.message);
            }
        },

        // Setup event listeners
        setupEventListeners: function() {
            // Refresh button
            $('#refreshTreeBtn').on('click', () => {
                this.refresh();
            });

            // Layout switching
            $('.tree-layout-option').on('click', (e) => {
                e.preventDefault();
                const newLayout = $(e.currentTarget).data('layout');
                if (newLayout !== this.selectedLayout) {
                    this.selectedLayout = newLayout;
                    $('.tree-layout-option').removeClass('active');
                    $(e.currentTarget).addClass('active');

                    if (this.dendrogram) {
                        this.dendrogram.setLayout(newLayout);
                    }
                }
            });

            // Export buttons
            $('#exportTreeBtn').on('click', () => {
                if (this.dendrogram) {
                    this.dendrogram.exportAsPNG('dendrogram-widget.png');
                }
            });

            $('#exportSVGBtn').on('click', () => {
                if (this.dendrogram && this.dendrogram.svg) {
                    const svgElement = this.dendrogram.svg.node();
                    const svgData = new XMLSerializer().serializeToString(svgElement);
                    const blob = new Blob([svgData], { type: 'image/svg+xml' });
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = 'dendrogram-widget.svg';
                    link.click();
                    URL.revokeObjectURL(url);
                }
            });

            $('#exportMetricsBtn').on('click', () => {
                this.exportMetrics();
            });

            $('#centerTreeBtn').on('click', () => {
                if (this.dendrogram) {
                    this.dendrogram.centerDendrogram();
                }
            });

            // Node selection event
            document.addEventListener('nodeSelected', (event) => {
                this.handleNodeSelection(event.detail.data);
            });
        },

        // Load dendrogram data
        loadData: function() {
            this.showLoading('Loading dendrogram data...');

            $.ajax({
                url: '<?= base_url() ?>position/get_dendrogram_data',
                method: 'GET',
                dataType: 'json',
                success: (response) => {
                    console.log('Dendrogram widget data loaded:', response);

                    if (response.success) {
                        this.data = response.flat_data || [];

                        // Update statistics
                        this.updateStatistics(response.clustering_metadata);

                        // Load data into dendrogram
                        if (this.dendrogram && this.dendrogram.loadData(this.data)) {
                            console.log('Dendrogram widget rendered successfully');
                            $('#treeStatsRow').show();

                            // Initialize clustering if available
                            if (this.clustering) {
                                try {
                                    this.clustering.loadPositions(this.data);
                                    this.loadClusteringMetrics();
                                } catch (error) {
                                    console.warn('Clustering initialization failed:', error);
                                }
                            }
                        } else {
                            this.showError('Failed to render dendrogram');
                        }
                    } else {
                        this.showError(response.message || 'Failed to load data');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Error loading dendrogram data:', error);
                    this.showError('Network error: Failed to load data');
                }
            });
        },

        // Load clustering metrics
        loadClusteringMetrics: function() {
            $.ajax({
                url: '<?= base_url() ?>position/get_clustering_metrics',
                method: 'GET',
                dataType: 'json',
                success: (response) => {
                    if (response.success) {
                        this.updateClusteringStats(response.metrics);
                    }
                },
                error: (error) => {
                    console.warn('Failed to load clustering metrics:', error);
                }
            });
        },

        // Handle node selection
        handleNodeSelection: function(nodeData) {
            if (!nodeData.isPosition) {
                $('#treeInfoPanel').hide();
                return;
            }

            const position = nodeData.data;

            let html = '<div class="row">';
            html += '<div class="col-md-6">';
            html += `<h6 class="text-primary mb-2">${position.name}</h6>`;
            html += `<p><strong>Department:</strong> ${position.department || 'No Department'}</p>`;
            html += `<p><strong>Level:</strong> ${position.level_name || 'No Level'}</p>`;
            html += `<p><strong>Order:</strong> ${position.position_order || 'Not set'}</p>`;
            html += '</div>';
            html += '<div class="col-md-6">';
            html += '<h6 class="text-success mb-2">Clustering Data</h6>';
            html += `<p><strong>Outgoing Paths:</strong> ${position.outgoing_paths || 0}</p>`;
            html += `<p><strong>Incoming Paths:</strong> ${position.incoming_paths || 0}</p>`;
            html += `<p><strong>Employee Count:</strong> ${position.employee_count || 0}</p>`;
            html += '</div>';
            html += '</div>';

            $('#treeInfoContent').html(html);
            $('#treeInfoPanel').show();
        },

        // Update statistics
        updateStatistics: function(metadata) {
            if (!metadata) return;

            $('#totalPositionsCount').text(metadata.total_positions || 0);
            $('#totalClustersCount').text(metadata.clusters_generated || 0);
            $('#maxDistanceValue').text((metadata.max_distance || 0).toFixed(3));
        },

        // Update clustering statistics
        updateClusteringStats: function(metrics) {
            if (!metrics) return;

            $('#avgDistanceValue').text((metrics.average_distance || 0).toFixed(3));
        },

        // Export metrics
        exportMetrics: function() {
            if (!this.clustering) {
                alert('Clustering data not available');
                return;
            }

            const results = this.clustering.exportResults();
            const dataStr = JSON.stringify(results, null, 2);
            const blob = new Blob([dataStr], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'dendrogram-widget-metrics.json';
            link.click();
            URL.revokeObjectURL(url);
        },

        // Refresh widget
        refresh: function() {
            this.data = null;
            $('#treeInfoPanel').hide();
            $('#treeStatsRow').hide();
            this.loadData();
        },

        // Show loading state
        showLoading: function(message) {
            $('#careerTreeContainer').html(`
                <div class="tree-loading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="loading-text">${message}</div>
                </div>
            `);
        },

        // Show error state
        showError: function(message) {
            $('#careerTreeContainer').html(`
                <div class="tree-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div style="margin: 16px 0; max-width: 600px;">${message}</div>
                    <div class="d-flex gap-2 flex-wrap justify-content-center">
                        <button class="btn btn-outline-danger btn-sm" onclick="window.DendrogramWidget.refresh()">
                            <i class="fas fa-redo"></i> Retry
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload()">
                            <i class="fas fa-refresh"></i> Reload Page
                        </button>
                    </div>
                </div>
            `);
        }
    };

    // Initialize the dendrogram widget
    window.DendrogramWidget.init();

    // Legacy support
    window.CareerTreeManager = window.DendrogramWidget;
    window.initializeCareerTreeIfNeeded = function() {
        window.DendrogramWidget.refresh();
    };
    window.loadCareerTreeData = function() {
        window.DendrogramWidget.refresh();
    };
});
</script>