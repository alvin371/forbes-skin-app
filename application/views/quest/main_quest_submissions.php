<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">
        <i class="bi bi-trophy me-2"></i>Main Quest Submissions
    </h6>
    <div>
        <a href="<?= base_url() ?>quest" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Quest Management
        </a>
    </div>
</div>

<form action="" class="search-form">
    <div class="row g-2 mb-3">
        <div class="col-md-8">
            <div class="input-group" style="box-shadow: 0 2px 0 rgba(0,0,0,0.02);">
                <input type="text" name="keyword" class="form-control" placeholder="Search by quest title or employee name..." 
                       value="<?= isset($_GET['keyword']) ? $_GET['keyword'] : '' ?>"
                       style="border: 1px solid #d9d9d9; box-shadow: none; height: 32px; padding: 4px 11px; margin-right: 10px; border-radius: 2px;">
                <select name="status_filter" class="form-select me-2" style="border: 1px solid #d9d9d9; height: 32px; max-width: 150px; border-radius: 2px;">
                    <option value="">Semua Status</option>
                    <option value="pending" <?= ($status_filter ?? '') == 'pending' ? 'selected' : '' ?>>🟡 Pending</option>
                    <option value="approved" <?= ($status_filter ?? '') == 'approved' ? 'selected' : '' ?>>✅ Approved</option>
                    <option value="denied" <?= ($status_filter ?? '') == 'denied' ? 'selected' : '' ?>>❌ Denied</option>
                </select>
                <button class="btn btn-primary" type="submit"
                    style="border-radius: 2px; height: 32px; padding: 0 15px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </div>
    </div>

    <?php if (!empty($notif)): ?>
        <div class="alert alert-info" style="display: flex; align-items: center; border-radius: 6px; background-color: #e6f7ff; border: 1px solid #91d5ff;">
            <i class="bi bi-info-circle me-2" style="color: #1890ff;"></i>
            <span style="color: #1890ff;"><?= strip_tags($notif) ?></span>
        </div>
    <?php endif; ?>
</form>

<div class="table-responsive">
    <table class="table table-hover" id="submissions-table" style="margin-bottom: 0;">
        <thead style="background-color: #fafafa; border-bottom: 1px solid #f0f0f0;">
            <tr>
                <th class="text-start" style="padding: 12px 16px; font-weight: 500; color: rgba(0,0,0,0.85); border-bottom: 1px solid #f0f0f0;">#</th>
                <th class="text-start" style="padding: 12px 16px; font-weight: 500; color: rgba(0,0,0,0.85); border-bottom: 1px solid #f0f0f0;">
                    <i class="bi bi-trophy me-1"></i>Quest Details
                </th>
                <th class="text-start" style="padding: 12px 16px; font-weight: 500; color: rgba(0,0,0,0.85); border-bottom: 1px solid #f0f0f0;">
                    <i class="bi bi-person me-1"></i>Employee
                </th>
                <th class="text-center" style="padding: 12px 16px; font-weight: 500; color: rgba(0,0,0,0.85); border-bottom: 1px solid #f0f0f0;">
                    <i class="bi bi-flag me-1"></i>Status
                </th>
                <th class="text-start" style="padding: 12px 16px; font-weight: 500; color: rgba(0,0,0,0.85); border-bottom: 1px solid #f0f0f0;">
                    <i class="bi bi-clock me-1"></i>Submitted
                </th>
                <th class="text-center" style="padding: 12px 16px; font-weight: 500; color: rgba(0,0,0,0.85); border-bottom: 1px solid #f0f0f0;">
                    <i class="bi bi-gift me-1"></i>Benefit
                </th>
                <th class="text-end" style="padding: 12px 16px; font-weight: 500; color: rgba(0,0,0,0.85); border-bottom: 1px solid #f0f0f0;">Action</th>
            </tr>
        </thead>
        <tbody id="submissions-tbody">
            <!-- Content will be loaded via AJAX -->
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-end mt-3">
    <?= $pagination ?>
</div>

<!-- Approval Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 8px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
            <div class="modal-header" style="border-bottom: 1px solid #f0f0f0; padding: 20px 24px;">
                <h5 class="modal-title" style="color: rgba(0,0,0,0.85); font-weight: 500;">
                    <i class="bi bi-check-circle text-success me-2"></i>Approve Main Quest Submission
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="font-size: 12px;"></button>
            </div>
            <div class="modal-body" style="padding: 24px;">
                <form id="approval-form">
                    <input type="hidden" id="submission-id" name="id">
                    <div class="mb-3">
                        <label for="hr_notes" class="form-label" style="font-weight: 500; color: rgba(0,0,0,0.85);">HR Notes (Optional)</label>
                        <textarea class="form-control" id="hr_notes" name="hr_notes" rows="3" 
                                  placeholder="Add any notes for the employee..."
                                  style="border: 1px solid #d9d9d9; border-radius: 6px; resize: vertical;"></textarea>
                    </div>
                    <div class="alert" style="background-color: #f6ffed; border: 1px solid #b7eb8f; border-radius: 6px; padding: 12px 16px;">
                        <i class="bi bi-gift me-2" style="color: #52c41a;"></i>
                        <span style="color: #52c41a;">Benefit akan otomatis diberikan sesuai dengan yang telah ditentukan saat quest dibuat.</span>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f0f0f0; padding: 16px 24px;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 6px;">Cancel</button>
                <button type="button" class="btn btn-success" onclick="approveSubmission()" style="border-radius: 6px;">
                    <i class="bi bi-check-circle me-1"></i> Approve
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Denial Modal -->
<div class="modal fade" id="denialModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 8px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
            <div class="modal-header" style="border-bottom: 1px solid #f0f0f0; padding: 20px 24px;">
                <h5 class="modal-title" style="color: rgba(0,0,0,0.85); font-weight: 500;">
                    <i class="bi bi-x-circle text-danger me-2"></i>Deny Main Quest Submission
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="font-size: 12px;"></button>
            </div>
            <div class="modal-body" style="padding: 24px;">
                <form id="denial-form">
                    <input type="hidden" id="denial-submission-id" name="id">
                    <div class="mb-3">
                        <label for="denial_hr_notes" class="form-label" style="font-weight: 500; color: rgba(0,0,0,0.85);">
                            Reason for Denial <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="denial_hr_notes" name="hr_notes" rows="3" 
                                  placeholder="Please provide reason for denial..." required
                                  style="border: 1px solid #d9d9d9; border-radius: 6px; resize: vertical;"></textarea>
                    </div>
                    <div class="alert" style="background-color: #fff2f0; border: 1px solid #ffccc7; border-radius: 6px; padding: 12px 16px;">
                        <i class="bi bi-exclamation-triangle me-2" style="color: #ff4d4f;"></i>
                        <span style="color: #ff4d4f;">Pastikan alasan penolakan jelas dan membantu employee untuk perbaikan.</span>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f0f0f0; padding: 16px 24px;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 6px;">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="denySubmission()" style="border-radius: 6px;">
                    <i class="bi bi-x-circle me-1"></i> Deny
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Function to load submissions data
function loadSubmissionsData() {
    $.ajax({
        type: 'GET',
        url: "<?= base_url() ?>quest/main_quest_submissions_item<?= $param ?? '' ?>",
        beforeSend: function() {
            $('#submissions-tbody').html('<tr><td colspan="7" class="text-center" style="padding: 40px;"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>');
        },
        success: function(data) {
            $('#submissions-tbody').html(data);
        },
        error: function(xhr, status, error) {
            console.error('Error loading data:', error);
            $('#submissions-tbody').html('<tr><td colspan="7" class="text-center" style="padding: 40px;"><div style="color: #ff4d4f;"><i class="bi bi-exclamation-triangle me-2"></i>Error loading data. Please try again.</div></td></tr>');
        }
    });
}

// Initialize on page load
$(document).ready(function() {
    loadSubmissionsData();
});

function showApprovalModal(submissionId) {
    $('#submission-id').val(submissionId);
    $('#hr_notes').val('');
    $('#approvalModal').modal('show');
}

function showDenialModal(submissionId) {
    $('#denial-submission-id').val(submissionId);
    $('#denial_hr_notes').val('');
    $('#denialModal').modal('show');
}

function approveSubmission() {
    const formData = new FormData(document.getElementById('approval-form'));
    
    $.ajax({
        type: 'POST',
        url: '<?= base_url() ?>quest/approve_main_quest_submission',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.indexOf("success") != -1) {
                $('#approvalModal').modal('hide');
                loadSubmissionsData();
                // Show success message
                $('<div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 6px; background-color: #f6ffed; border: 1px solid #b7eb8f; color: #52c41a;">' +
                  '<i class="bi bi-check-circle me-2"></i>Main quest submission berhasil disetujui dengan benefit otomatis!' +
                  '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>')
                  .prependTo('form.search-form').delay(4000).fadeOut();
            } else {
                alert('Error: ' + response);
            }
        },
        error: function() {
            alert('Terjadi kesalahan saat menyetujui submission.');
        }
    });
}

function denySubmission() {
    const formData = new FormData(document.getElementById('denial-form'));
    
    $.ajax({
        type: 'POST',
        url: '<?= base_url() ?>quest/deny_main_quest_submission',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.indexOf("success") != -1) {
                $('#denialModal').modal('hide');
                loadSubmissionsData();
                // Show success message
                $('<div class="alert alert-warning alert-dismissible fade show" role="alert" style="border-radius: 6px; background-color: #fff7e6; border: 1px solid #ffd591; color: #d46b08;">' +
                  '<i class="bi bi-x-circle me-2"></i>Main quest submission berhasil ditolak!' +
                  '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>')
                  .prependTo('form.search-form').delay(4000).fadeOut();
            } else {
                alert('Error: ' + response);
            }
        },
        error: function() {
            alert('Terjadi kesalahan saat menolak submission.');
        }
    });
}
</script>