<div class="row mb-4">
    <div class="col-md-8">
        <div class="alert alert-info">
            <h6><i class="bi bi-info-circle me-2"></i>Leaderboard Information</h6>
            <ul class="mb-0">
                <li><strong>Monthly Leaderboard:</strong> Shows rankings for the current month, resets every month</li>
                <li><strong>Ongoing Leaderboard:</strong> Shows all-time rankings based on total points earned</li>
                <li><strong>Side Quests Only:</strong> Leaderboard only counts side quest completions and points</li>
                <li><strong>Points:</strong> Includes side quest points + milestone bonus points</li>
                <li><strong>Real-time Updates:</strong> Rankings update when you click "Initialize Stats"</li>
            </ul>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-gear me-2"></i>System Management</h6>
            </div>
            <div class="card-body">
                <button type="button" class="btn btn-outline-primary btn-sm w-100" onclick="initializeStats()">
                    <i class="bi bi-arrow-clockwise me-1"></i>Initialize Stats
                </button>
                <small class="text-muted mt-2 d-block">Run this if leaderboards are empty or after database setup</small>
            </div>
        </div>
    </div>
</div>

<!-- Leaderboard Sub-tabs -->
<ul class="nav nav-pills mb-3" id="leaderboardSubTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="monthly-leaderboard-tab" data-bs-toggle="tab" data-bs-target="#monthly-leaderboard" 
                type="button" role="tab" aria-controls="monthly-leaderboard" aria-selected="true">
            <i class="bi bi-calendar-month me-2"></i>Monthly Leaderboard
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="ongoing-leaderboard-tab" data-bs-toggle="tab" data-bs-target="#ongoing-leaderboard" 
                type="button" role="tab" aria-controls="ongoing-leaderboard" aria-selected="false">
            <i class="bi bi-trophy me-2"></i>All-Time Leaderboard
        </button>
    </li>
</ul>

<!-- Leaderboard Sub-tab Content -->
<div class="tab-content" id="leaderboardSubTabContent">
    <!-- Monthly Leaderboard -->
    <div class="tab-pane fade show active" id="monthly-leaderboard" role="tabpanel" aria-labelledby="monthly-leaderboard-tab">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">
                    <i class="bi bi-calendar-month me-2"></i>Monthly Leaderboard - <?= date('F Y') ?>
                </h6>
            </div>
            <div class="card-body">
                <div id="monthly-leaderboard-content">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Ongoing Leaderboard -->
    <div class="tab-pane fade" id="ongoing-leaderboard" role="tabpanel" aria-labelledby="ongoing-leaderboard-tab">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">
                    <i class="bi bi-trophy me-2"></i>All-Time Leaderboard
                </h6>
            </div>
            <div class="card-body">
                <div id="ongoing-leaderboard-content">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.nav-pills .nav-link {
    border-radius: 2px;
    padding: 8px 16px;
    color: rgba(0, 0, 0, 0.65);
    background-color: transparent;
    margin-right: 8px;
    font-size: 14px;
    transition: all 0.3s;
}

.nav-pills .nav-link:hover {
    color: #1890ff;
    background-color: #f0f0f0;
}

.nav-pills .nav-link.active {
    color: #fff;
    background-color: #1890ff;
    font-weight: 500;
}

.leaderboard-rank {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 16px;
}

.rank-1 {
    background: linear-gradient(135deg, #ffd700, #ffed4a);
    color: #8b7355;
}

.rank-2 {
    background: linear-gradient(135deg, #c0c0c0, #e8e8e8);
    color: #5a5a5a;
}

.rank-3 {
    background: linear-gradient(135deg, #cd7f32, #d4a574);
    color: #5a4037;
}

.rank-other {
    background-color: #f0f0f0;
    color: rgba(0, 0, 0, 0.65);
}
</style>

<script>
function loadLeaderboardData() {
    // Load monthly leaderboard by default
    loadMonthlyLeaderboard();
    
    // Handle sub-tab switching
    $('#monthly-leaderboard-tab').on('shown.bs.tab', function() {
        loadMonthlyLeaderboard();
    });

    $('#ongoing-leaderboard-tab').on('shown.bs.tab', function() {
        loadOngoingLeaderboard();
    });
}

function loadMonthlyLeaderboard() {
    const contentDiv = $('#monthly-leaderboard-content');
    contentDiv.html('<div class="d-flex justify-content-center align-items-center" style="min-height: 200px;"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
    
    $.ajax({
        type: 'GET',
        url: '<?= base_url() ?>milestone/monthly_leaderboard',
        success: function(html) {
            contentDiv.html(html);
        },
        error: function(xhr, status, error) {
            console.error('Error loading monthly leaderboard:', error);
            contentDiv.html('<div class="alert alert-danger">Error loading monthly leaderboard. Please try again.</div>');
        }
    });
}

function loadOngoingLeaderboard() {
    const contentDiv = $('#ongoing-leaderboard-content');
    contentDiv.html('<div class="d-flex justify-content-center align-items-center" style="min-height: 200px;"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
    
    $.ajax({
        type: 'GET',
        url: '<?= base_url() ?>milestone/ongoing_leaderboard',
        success: function(html) {
            contentDiv.html(html);
        },
        error: function(xhr, status, error) {
            console.error('Error loading ongoing leaderboard:', error);
            contentDiv.html('<div class="alert alert-danger">Error loading ongoing leaderboard. Please try again.</div>');
        }
    });
}

function initializeStats() {
    Swal.fire({
        title: 'Initialize Statistics?',
        text: 'This will populate the leaderboard stats from existing side quest data. This is safe to run multiple times.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1890ff',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, initialize!'
    }).then((result) => {
        if (result.isConfirmed) {
            const loadingAlert = Swal.fire({
                title: 'Initializing Stats...',
                text: 'Please wait while we process the data.',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                type: 'POST',
                url: '<?= base_url() ?>milestone/initialize_stats',
                success: function(response) {
                    loadingAlert.close();
                    
                    if (response.indexOf('success') !== -1) {
                        Swal.fire('Success!', response, 'success').then(() => {
                            // Reload both leaderboards
                            loadMonthlyLeaderboard();
                            loadOngoingLeaderboard();
                        });
                    } else if (response.indexOf('warning') !== -1) {
                        Swal.fire('Warning', response, 'warning').then(() => {
                            // Reload both leaderboards
                            loadMonthlyLeaderboard();
                            loadOngoingLeaderboard();
                        });
                    } else {
                        Swal.fire('Error', response, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    loadingAlert.close();
                    console.error('Error initializing stats:', error);
                    Swal.fire('Error', 'Failed to initialize stats. Please try again.', 'error');
                }
            });
        }
    });
}
</script>