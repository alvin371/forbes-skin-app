<!-- Header Section -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">
            <i class="bi bi-award me-2" style="color: #faad14;"></i>
            Claimable Milestone History
        </h6>
        <p class="text-muted mb-0 mt-1">Track all milestone claims and point deductions across the system</p>
    </div>
    <div class="d-flex gap-2">
        <span class="badge bg-warning" style="padding: 8px 12px; font-size: 0.85rem;">
            <i class="bi bi-trophy-fill me-1"></i>
            <?= count($milestone_claim_history) ?> Claims Total
        </span>
        <div class="dropdown">
            <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-funnel me-1"></i>Filter by Status
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#" onclick="filterByStatus('all')">All Claims</a></li>
                <li><a class="dropdown-item" href="#" onclick="filterByStatus('waiting_approval')">Waiting Approval</a></li>
                <li><a class="dropdown-item" href="#" onclick="filterByStatus('approved')">Approved</a></li>
                <li><a class="dropdown-item" href="#" onclick="filterByStatus('delivered')">Delivered</a></li>
            </ul>
        </div>
    </div>
</div>

<!-- Search and Filter Section -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" id="search-form">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small">Search Category</label>
                    <select name="keyword_category" class="form-select">
                        <option value="User" <?= ($keyword_category == 'User') ? 'selected' : '' ?>>User Name</option>
                        <option value="Milestone" <?= ($keyword_category == 'Milestone') ? 'selected' : '' ?>>Milestone Title</option>
                        <option value="Type" <?= ($keyword_category == 'Type') ? 'selected' : '' ?>>Milestone Type</option>
                        <option value="Status" <?= ($keyword_category == 'Status') ? 'selected' : '' ?>>Claim Status</option>
                    </select>
                </div>
                <div class="col-md-7">
                    <label class="form-label small">Search Keyword</label>
                    <input type="text" name="keyword" class="form-control" style="height: 33px;"
                        placeholder="Enter search keyword..."
                        value="<?= isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : '' ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small" style="visibility: hidden;">Action</label>
                    <button type="submit" class="btn btn-primary w-100 d-block">
                        <i class="bi bi-search me-1"></i>Search
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Results Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
            <i class="bi bi-table me-2"></i>Milestone Claim History
        </h6>
        <div id="pagination-info" class="text-muted small"></div>
    </div>
    <div class="card-body p-0">
        <div id="table-container">
            <!-- Table content will be loaded here -->
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Load initial data
        loadClaimableMilestoneData();

        // Handle form submission
        $('#search-form').on('submit', function(e) {
            e.preventDefault();
            loadClaimableMilestoneData();
        });
    });

    function loadClaimableMilestoneData(page = 1) {
        const container = $('#table-container');
        const form = $('#search-form');

        // Show loading state
        container.html(`
        <div class="d-flex justify-content-center align-items-center" style="min-height: 300px;">
            <div class="text-center">
                <div class="spinner-border text-warning" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="text-muted mt-2 mb-0">Loading milestone claims...</p>
            </div>
        </div>
    `);

        // Get form data and add page parameter
        let formData = form.serialize();
        if (page > 1) {
            formData += (formData ? '&' : '') + 'page=' + page;
        }

        // Load data via AJAX
        $.ajax({
            type: 'GET',
            url: '<?= base_url() ?>milestone/claimable_milestone_item?' + formData,
            success: function(html) {
                container.html(html);

                // Initialize any additional scripts if needed
                if (typeof initializeClaimableTable === 'function') {
                    initializeClaimableTable();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading claimable milestone data:', error);
                console.error('XHR:', xhr.responseText);
                container.html(`
                <div class="alert alert-danger m-3">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Error loading data.</strong> Please try again or contact support if the problem persists.
                    <br><small class="text-muted">Error: ${error}</small>
                </div>
            `);
            }
        });
    }

    // Function for pagination links
    function loadClaimableMilestoneDataWithPage(page) {
        loadClaimableMilestoneData(page);
    }

    // Function to clear search
    function clearSearch() {
        // Clear form fields
        $('select[name="keyword_category"]').val('User');
        $('input[name="keyword"]').val('');

        // Reload data
        loadClaimableMilestoneData(1);
    }

    // Function to filter by status
    function filterByStatus(status) {
        $('select[name="keyword_category"]').val('Status');
        if (status === 'all') {
            $('input[name="keyword"]').val('');
        } else {
            $('input[name="keyword"]').val(status);
        }
        
        // Reload data
        loadClaimableMilestoneData(1);
    }

    // Function to approve claim
    function approveClaim(achievementId, milestoneTitle, userName) {
        console.log('Attempting to approve claim:', { achievementId, milestoneTitle, userName });
        
        if (confirm(`Are you sure you want to approve the milestone claim "${milestoneTitle}" for ${userName}?`)) {
            const approveButton = $(`button[data-achievement-id="${achievementId}"]`);
            console.log('Approve button found:', approveButton.length > 0);
            
            $.ajax({
                type: 'POST',
                url: '<?= base_url() ?>milestone/approve_claim',
                data: { achievement_id: achievementId },
                beforeSend: function() {
                    console.log('Sending approval request for achievement ID:', achievementId);
                    approveButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Approving...');
                },
                success: function(response) {
                    console.log('Approval response received:', response);
                    if (response.indexOf('success') !== -1) {
                        alert('Claim approved successfully!');
                        loadClaimableMilestoneData();
                    } else {
                        alert('Error: ' + response.replace(/<[^>]*>/g, ''));
                        approveButton.prop('disabled', false).html('<i class="bi bi-check me-1"></i>Approve');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Approve claim AJAX error:', error);
                    console.error('Status:', status);
                    console.error('Response text:', xhr.responseText);
                    console.error('Response status:', xhr.status);
                    alert('System error occurred. Please try again.');
                    approveButton.prop('disabled', false).html('<i class="bi bi-check me-1"></i>Approve');
                }
            });
        }
    }

    // Function to show delivery modal
    function showDeliveryModal(achievementId, milestoneTitle, userName) {
        $('#delivery-achievement-id').val(achievementId);
        $('#delivery-milestone-title').text(milestoneTitle);
        $('#delivery-user-name').text(userName);
        $('#deliveryModal').modal('show');
    }

    // Handle delivery form submission
    $('#delivery-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        
        $.ajax({
            type: 'POST',
            url: '<?= base_url() ?>milestone/mark_delivered',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#btn-mark-delivered').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Processing...');
            },
            success: function(response) {
                if (response.indexOf('success') !== -1) {
                    alert('Milestone marked as delivered successfully!');
                    $('#deliveryModal').modal('hide');
                    $('#delivery-form')[0].reset();
                    loadClaimableMilestoneData();
                } else {
                    alert('Error: ' + response.replace(/<[^>]*>/g, ''));
                }
                $('#btn-mark-delivered').prop('disabled', false).html('<i class="bi bi-truck me-1"></i>Mark as Delivered');
            },
            error: function() {
                alert('System error occurred. Please try again.');
                $('#btn-mark-delivered').prop('disabled', false).html('<i class="bi bi-truck me-1"></i>Mark as Delivered');
            }
        });
    });
</script>

<!-- Delivery Modal -->
<div class="modal fade" id="deliveryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-truck me-2" style="color: #1890ff;"></i>Mark Milestone as Delivered
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="delivery-form" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        You are marking the milestone "<strong id="delivery-milestone-title"></strong>" as delivered for <strong id="delivery-user-name"></strong>.
                    </div>
                    
                    <input type="hidden" id="delivery-achievement-id" name="achievement_id">
                    
                    <div class="mb-3">
                        <label for="proof_image" class="form-label">
                            <i class="bi bi-image me-1"></i>Proof of Delivery (Optional)
                        </label>
                        <input type="file" class="form-control" name="proof_image" accept="image/*,.pdf">
                        <div class="form-text">Upload an image or PDF as proof of delivery (max 5MB)</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="btn-mark-delivered" class="btn btn-success">
                        <i class="bi bi-truck me-1"></i>Mark as Delivered
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>