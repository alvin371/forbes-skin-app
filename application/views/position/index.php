<style>
    /* Select2 Styling */
    .select2-container .select2-selection--single {
        box-sizing: border-box;
        cursor: pointer;
        display: block;
        height: 32px;
        user-select: none;
        -webkit-user-select: none;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: rgba(0, 0, 0, 0.85);
        line-height: 32px;
        padding-left: 11px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 32px;
        position: absolute;
        top: 0px;
        right: 1px;
        width: 20px;
    }

    .select2 {
        height: 32px !important;
        min-width: 100% !important;
        margin-bottom: 8px;
    }

    /* Ant Design-like Table Styling */
    .table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid #f0f0f0;
        border-radius: 2px;
    }

    .table thead th {
        background-color: #fafafa;
        color: rgba(0, 0, 0, 0.85);
        font-weight: 500;
        text-align: left;
        padding: 12px 8px;
        font-size: 14px;
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.3s ease;
    }

    .table tbody td {
        padding: 12px 8px !important;
        font-size: 14px;
        color: rgba(0, 0, 0, 0.65);
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.3s ease;
    }

    .table-hover tbody tr:hover {
        background-color: #fafafa;
    }

    /* Card Styling - Ant Design-like */
    .card {
        border-radius: 2px;
        border: 1px solid #f0f0f0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.09);
        margin-bottom: 16px;
    }

    .card-header {
        background-color: #fff;
        border-bottom: 1px solid #f0f0f0;
        padding: 16px;
        height: 56px;
    }

    .card-body {
        padding: 16px;
    }

    /* Button Styling - Ant Design-like */
    .btn {
        border-radius: 2px;
        padding: 4px 15px;
        font-size: 14px;
        height: 32px;
        line-height: 1.5;
        transition: all 0.3s cubic-bezier(0.645, 0.045, 0.355, 1);
    }

    .btn-primary {
        background-color: #1890ff;
        border-color: #1890ff;
    }

    .btn-primary:hover {
        background-color: #40a9ff;
        border-color: #40a9ff;
    }

    .btn-outline-secondary {
        color: rgba(0, 0, 0, 0.65);
        border-color: #d9d9d9;
        background: #fff;
    }

    .btn-outline-secondary:hover {
        color: #40a9ff;
        border-color: #40a9ff;
    }

    /* Badge Styling - Ant Design-like */
    .badge {
        font-size: 12px;
        height: 22px;
        padding: 0 8px;
        line-height: 22px;
        border-radius: 2px;
        font-weight: normal;
    }

    /* Pagination Styling - Ant Design-like */
    .pagination {
        margin-top: 16px;
        justify-content: flex-end;
    }

    .page-item {
        margin-right: 8px;
    }

    .page-item:last-child {
        margin-right: 0;
    }

    .page-item.active .page-link {
        background-color: #1890ff;
        border-color: #1890ff;
    }

    .page-link {
        min-width: 32px;
        height: 32px;
        line-height: 30px;
        text-align: center;
        color: rgba(0, 0, 0, 0.65);
        border-radius: 2px;
        padding: 0;
        margin: 0;
        border: 1px solid #d9d9d9;
    }

    .page-link:hover {
        color: #40a9ff;
        border-color: #40a9ff;
    }

    /* Search Form Styling - Ant Design-like */
    .search-form {
        margin-bottom: 16px;
    }

    .search-form .input-group {
        border-radius: 2px;
        display: flex;
        justify-content: space-between;
    }

    .form-control {
        height: 32px;
        padding: 4px 11px;
        font-size: 14px;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
        transition: all 0.3s;
    }

    .form-control:hover {
        border-color: #40a9ff;
    }

    .form-control:focus {
        border-color: #40a9ff;
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
    }

    .form-select {
        height: 32px;
        padding: 4px 11px;
        font-size: 14px;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
        transition: all 0.3s;
    }

    .form-select:hover {
        border-color: #40a9ff;
    }

    .form-select:focus {
        border-color: #40a9ff;
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
    }

    /* Alert Styling - Ant Design-like */
    .alert {
        padding: 8px 15px;
        border-radius: 2px;
        font-size: 14px;
    }

    .alert-info {
        background-color: #e6f7ff;
        border-color: #91d5ff;
        color: rgba(0, 0, 0, 0.65);
    }

    /* Tab Styling - Ant Design-like */
    .nav-tabs {
        border-bottom: 1px solid #f0f0f0;
        margin-bottom: 0;
    }

    .nav-tabs .nav-link {
        border-radius: 2px 2px 0 0;
        border: 1px solid transparent;
        padding: 8px 16px;
        color: rgba(0, 0, 0, 0.65);
        background-color: transparent;
        margin-right: 2px;
        font-size: 14px;
        transition: all 0.3s;
    }

    .nav-tabs .nav-link:hover {
        border-color: transparent;
        color: #1890ff;
        background-color: #f0f0f0;
    }

    .nav-tabs .nav-link.active {
        color: #1890ff;
        background-color: #fff;
        border-color: #f0f0f0 #f0f0f0 #fff;
        font-weight: 500;
        border-bottom: 2px solid #1890ff;
    }

    .tab-content {
        background-color: #fff;
        padding: 16px 0 0 0;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .table-responsive {
            border-radius: 2px;
            overflow: hidden;
        }
    }

    /* Additional buttons for Position Management */
    .btn-secondary {
        background-color: #f5f5f5;
        border-color: #d9d9d9;
        color: rgba(0, 0, 0, 0.65);
    }

    .btn-secondary:hover {
        background-color: #e6f7ff;
        border-color: #40a9ff;
        color: #40a9ff;
    }

    /* Bootstrap dropdown fix */
    .dropdown-menu {
        z-index: 9999 !important;
    }

    .dropdown-menu.show {
        display: block !important;
    }
</style>

<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Position Management</h5>
            </div>
        </div>
        <div class="card-body">
            <!-- Tab Navigation -->
            <ul class="nav nav-tabs" id="positionTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="position-management-tab" data-bs-toggle="tab" data-bs-target="#position-management"
                            type="button" role="tab" aria-controls="position-management" aria-selected="true">
                        <i class="bi bi-briefcase me-2"></i>Position Management
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="career-paths-tab" data-bs-toggle="tab" data-bs-target="#career-paths"
                            type="button" role="tab" aria-controls="career-paths" aria-selected="false">
                        <i class="bi bi-signpost-split me-2"></i>Career Paths
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="org-chart-tab" data-bs-toggle="tab" data-bs-target="#org-chart"
                            type="button" role="tab" aria-controls="org-chart" aria-selected="false">
                        <i class="bi bi-diagram-3 me-2"></i>Organization Chart
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="positionTabContent">
                <!-- Position Management Tab -->
                <div class="tab-pane fade show active" id="position-management" role="tabpanel" aria-labelledby="position-management-tab">
                    <div id="position-management-content">
                        <!-- Content will be loaded here -->
                    </div>
                </div>

                <!-- Career Paths Tab -->
                <div class="tab-pane fade" id="career-paths" role="tabpanel" aria-labelledby="career-paths-tab">
                    <div id="career-paths-content">
                        <!-- Content will be loaded here -->
                    </div>
                </div>

                <!-- Organization Chart Tab -->
                <div class="tab-pane fade" id="org-chart" role="tabpanel" aria-labelledby="org-chart-tab">
                    <div id="org-chart-content">
                        <!-- Content will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .nav-tabs .nav-link {
        border-radius: 0.375rem 0.375rem 0 0;
        border: 1px solid transparent;
        padding: 0.75rem 1rem;
        color: rgba(0, 0, 0, 0.65);
        background-color: #f8f9fa;
        margin-right: 2px;
    }

    .nav-tabs .nav-link:hover {
        border-color: #e9ecef #e9ecef #dee2e6;
        color: #1890ff;
        background-color: #fff;
    }

    .nav-tabs .nav-link.active {
        color: #1890ff;
        background-color: #fff;
        border-color: #dee2e6 #dee2e6 #fff;
        font-weight: 500;
    }

    .tab-content {
        border: 1px solid #dee2e6;
        border-top: none;
        border-radius: 0 0 0.375rem 0.375rem;
        background-color: #fff;
    }
</style>

<script>
$(document).ready(function() {
    // Load position management content by default
    loadPositionManagementContent();

    // Handle tab switching
    $('#position-management-tab').on('shown.bs.tab', function() {
        loadPositionManagementContent();
    });

    $('#career-paths-tab').on('shown.bs.tab', function() {
        loadCareerPathsContent();
    });

    $('#org-chart-tab').on('shown.bs.tab', function() {
        loadOrgChartContent();
    });
});

function loadPositionManagementContent() {
    const contentDiv = $('#position-management-content');
    contentDiv.html('<div class="d-flex justify-content-center align-items-center" style="min-height: 200px;"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
    
    $.ajax({
        type: 'GET',
        url: '<?= base_url() ?>position/position_management_tab',
        success: function(html) {
            contentDiv.html(html);
            // Initialize any scripts in the loaded content
            setTimeout(function() {
                if (typeof loadPositionData === 'function') {
                    loadPositionData();
                }
            }, 300);
        },
        error: function(xhr, status, error) {
            console.error('Error loading position management content:', error);
            contentDiv.html('<div class="alert alert-danger">Error loading content. Please try again.</div>');
        }
    });
}

function loadOrgChartContent() {
    const contentDiv = $('#org-chart-content');

    // Instead of loading via AJAX, redirect to dedicated org chart page
    window.location.href = '<?= base_url() ?>position/org_chart';
}

function loadCareerPathsContent() {
    const contentDiv = $('#career-paths-content');
    contentDiv.html('<div class="d-flex justify-content-center align-items-center" style="min-height: 200px;"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');

    $.ajax({
        type: 'GET',
        url: '<?= base_url() ?>position/career_paths_overview',
        success: function(html) {
            contentDiv.html(html);
        },
        error: function(xhr, status, error) {
            console.error('Error loading career paths content:', error);
            contentDiv.html('<div class="alert alert-danger">Error loading career paths. Please try again.</div>');
        }
    });
}

</script>