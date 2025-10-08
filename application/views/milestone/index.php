<style>
    /* Ant Design-like styling consistent with quest management */
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

    .select2 {
        height: 32px !important;
        min-width: 100% !important;
        margin-bottom: 8px;
    }

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
</style>

<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Milestone & Leaderboard Management</h5>
            </div>
        </div>
        <div class="card-body">
            <!-- Tab Navigation -->
            <ul class="nav nav-tabs" id="milestoneTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="milestone-management-tab" data-bs-toggle="tab" data-bs-target="#milestone-management" 
                            type="button" role="tab" aria-controls="milestone-management" aria-selected="true">
                        <i class="bi bi-flag me-2"></i>Milestone Management
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="leaderboard-tab" data-bs-toggle="tab" data-bs-target="#leaderboard" 
                            type="button" role="tab" aria-controls="leaderboard" aria-selected="false">
                        <i class="bi bi-trophy me-2"></i>Leaderboards
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="claimable-milestone-tab" data-bs-toggle="tab" data-bs-target="#claimable-milestone" 
                            type="button" role="tab" aria-controls="claimable-milestone" aria-selected="false">
                        <i class="bi bi-award me-2"></i>Claimable Milestone
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="milestoneTabContent">
                <!-- Milestone Management Tab -->
                <div class="tab-pane fade show active" id="milestone-management" role="tabpanel" aria-labelledby="milestone-management-tab">
                    <div id="milestone-management-content">
                        <!-- Content will be loaded here -->
                    </div>
                </div>

                <!-- Leaderboard Tab -->
                <div class="tab-pane fade" id="leaderboard" role="tabpanel" aria-labelledby="leaderboard-tab">
                    <div id="leaderboard-content">
                        <!-- Content will be loaded here -->
                    </div>
                </div>

                <!-- Claimable Milestone Tab -->
                <div class="tab-pane fade" id="claimable-milestone" role="tabpanel" aria-labelledby="claimable-milestone-tab">
                    <div id="claimable-milestone-content">
                        <!-- Content will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Load milestone management content by default
    loadMilestoneManagementContent();

    // Handle tab switching
    $('#milestone-management-tab').on('shown.bs.tab', function() {
        loadMilestoneManagementContent();
    });

    $('#leaderboard-tab').on('shown.bs.tab', function() {
        loadLeaderboardContent();
    });

    $('#claimable-milestone-tab').on('shown.bs.tab', function() {
        loadClaimableMilestoneContent();
    });
});

function loadMilestoneManagementContent() {
    const contentDiv = $('#milestone-management-content');
    contentDiv.html('<div class="d-flex justify-content-center align-items-center" style="min-height: 200px;"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
    
    $.ajax({
        type: 'GET',
        url: '<?= base_url() ?>milestone/milestone_tab',
        success: function(html) {
            contentDiv.html(html);
            // Initialize any scripts in the loaded content
            setTimeout(function() {
                if (typeof loadMilestoneData === 'function') {
                    loadMilestoneData();
                }
            }, 300);
        },
        error: function(xhr, status, error) {
            console.error('Error loading milestone management content:', error);
            contentDiv.html('<div class="alert alert-danger">Error loading content. Please try again.</div>');
        }
    });
}

function loadLeaderboardContent() {
    const contentDiv = $('#leaderboard-content');
    contentDiv.html('<div class="d-flex justify-content-center align-items-center" style="min-height: 200px;"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
    
    $.ajax({
        type: 'GET',
        url: '<?= base_url() ?>milestone/leaderboard_tab',
        success: function(html) {
            contentDiv.html(html);
            // Initialize any scripts in the loaded content
            setTimeout(function() {
                if (typeof loadLeaderboardData === 'function') {
                    loadLeaderboardData();
                }
            }, 300);
        },
        error: function(xhr, status, error) {
            console.error('Error loading leaderboard content:', error);
            contentDiv.html('<div class="alert alert-danger">Error loading content. Please try again.</div>');
        }
    });
}

function loadClaimableMilestoneContent() {
    const contentDiv = $('#claimable-milestone-content');
    contentDiv.html('<div class="d-flex justify-content-center align-items-center" style="min-height: 200px;"><div class="spinner-border text-warning" role="status"><span class="visually-hidden">Loading...</span></div></div>');
    
    $.ajax({
        type: 'GET',
        url: '<?= base_url() ?>milestone/claimable_milestone',
        success: function(html) {
            contentDiv.html(html);
            // Initialize any scripts in the loaded content
            setTimeout(function() {
                if (typeof loadClaimableMilestoneData === 'function') {
                    loadClaimableMilestoneData();
                }
            }, 300);
        },
        error: function(xhr, status, error) {
            console.error('Error loading claimable milestone content:', error);
            contentDiv.html('<div class="alert alert-danger">Error loading content. Please try again.</div>');
        }
    });
}
</script>