<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09);">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px; height: 56px; display: flex; align-items: center; justify-content: space-between;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">Bulk Set Leave Quotas</h3>
        <a href="<?php echo site_url('admin/leave-quotas'); ?>" class="btn btn-default" style="background-color: #fff; border-color: #d9d9d9; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center;">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
    <div class="card-body" style="padding: 16px;">
        <?php if ($this->session->flashdata('message')): ?>
            <div class="alert alert-success" style="padding: 8px 15px; border-radius: 2px; font-size: 14px; background-color: #f6ffed; border: 1px solid #b7eb8f; color: #52c41a; margin-bottom: 16px;">
                <i class="bi bi-check-circle"></i> <?php echo $this->session->flashdata('message'); ?>
            </div>
        <?php endif; ?>

        <?php if ($this->session->flashdata('error')): ?>
            <div class="alert alert-danger" style="padding: 8px 15px; border-radius: 2px; font-size: 14px; background-color: #fff2f0; border: 1px solid #ffccc7; color: #ff4d4f; margin-bottom: 16px;">
                <i class="bi bi-exclamation-circle"></i> <?php echo $this->session->flashdata('error'); ?>
            </div>
        <?php endif; ?>

        <div style="background-color: #e6f7ff; border: 1px solid #91d5ff; border-radius: 2px; padding: 12px; margin-bottom: 16px;">
            <div style="color: #1890ff; font-size: 14px;">
                <i class="bi bi-info-circle"></i> <strong>Bulk Set:</strong> Set the same quota for multiple users at once. Select a leave type, enter the quota amount, and choose the users.
            </div>
        </div>

        <form method="post" action="<?php echo site_url('admin/leave-quotas/bulk-set'); ?>">
            <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
            <?php endif; ?>

            <div class="row" style="margin-bottom: 20px;">
                <div class="col-md-6">
                    <label style="display: block; font-size: 12px; font-weight: 500; color: rgba(0,0,0,0.85); margin-bottom: 6px;">
                        Leave Type <span style="color: #ff4d4f;">*</span>
                    </label>
                    <select name="leave_type_id" required style="width: 100%; border: 1px solid #d9d9d9; border-radius: 2px; padding: 8px 11px; font-size: 14px;">
                        <option value="">Select Leave Type</option>
                        <?php foreach ($leave_types as $leaveType): ?>
                            <option value="<?php echo $leaveType['id']; ?>">
                                <?php echo htmlspecialchars($leaveType['name']); ?> (<?php echo htmlspecialchars($leaveType['code']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label style="display: block; font-size: 12px; font-weight: 500; color: rgba(0,0,0,0.85); margin-bottom: 6px;">
                        Total Days <span style="color: #ff4d4f;">*</span>
                    </label>
                    <input type="number" name="total_days" required min="0" step="1" value="12" style="width: 100%; border: 1px solid #d9d9d9; border-radius: 2px; padding: 8px 11px; font-size: 14px;" placeholder="e.g. 12">
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 10px;">
                    <div style="min-width: 200px; flex: 1;">
                        <input id="bulk-user-search" type="text" placeholder="Search name or email" style="width: 100%; border: 1px solid #d9d9d9; border-radius: 2px; padding: 6px 11px; font-size: 14px;">
                    </div>
                    <div style="font-size: 12px; color: rgba(0,0,0,0.45);">Selected: <span id="bulk-selected-count">0</span></div>
                    <button type="button" onclick="selectAllUsers()" style="background: none; border: none; color: #1890ff; font-size: 12px; cursor: pointer;">Select All</button>
                    <button type="button" onclick="selectFilteredUsers()" style="background: none; border: none; color: #1890ff; font-size: 12px; cursor: pointer;">Select Filtered</button>
                    <button type="button" onclick="deselectAllUsers()" style="background: none; border: none; color: #1890ff; font-size: 12px; cursor: pointer;">Deselect All</button>
                </div>

                <div style="border: 1px solid #d9d9d9; border-radius: 2px; max-height: 400px; overflow-y: auto; background: #fff;">
                    <?php if (empty($users)): ?>
                        <div style="padding: 24px; text-align: center; color: rgba(0,0,0,0.45);">No users available.</div>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <label class="bulk-user-row" data-user-name="<?php echo htmlspecialchars($user['full_name']); ?>" data-user-email="<?php echo htmlspecialchars($user['email']); ?>" style="display: flex; align-items: center; padding: 10px 12px; border-bottom: 1px solid #f0f0f0; cursor: pointer; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#fafafa'" onmouseout="this.style.backgroundColor='transparent'">
                                <input type="checkbox" name="user_ids[]" value="<?php echo $user['id']; ?>" style="margin-right: 12px; width: 16px; height: 16px; cursor: pointer;">
                                <div style="flex: 1;">
                                    <div style="font-size: 14px; color: rgba(0,0,0,0.85); font-weight: 500;">
                                        <?php echo htmlspecialchars($user['full_name']); ?>
                                    </div>
                                    <div style="font-size: 12px; color: rgba(0,0,0,0.45);">
                                        <?php echo htmlspecialchars($user['email']); ?>
                                    </div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div style="padding-top: 16px; border-top: 1px solid #f0f0f0;">
                <button type="submit" class="btn btn-primary" style="background-color: #1890ff; border-color: #1890ff; height: 36px; padding: 6px 20px; border-radius: 2px; font-size: 14px; display: inline-flex; align-items: center;">
                    <i class="bi bi-check2"></i> Set Quotas for Selected Users
                </button>
                <a href="<?php echo site_url('admin/leave-quotas'); ?>" class="btn btn-default" style="background-color: #fff; border-color: #d9d9d9; color: rgba(0,0,0,0.65); height: 36px; padding: 6px 20px; border-radius: 2px; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; margin-left: 8px;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function updateSelectedCount() {
    var count = 0;
    document.querySelectorAll('input[name="user_ids[]"]').forEach(function(checkbox) {
        if (checkbox.checked) count++;
    });
    var counter = document.getElementById('bulk-selected-count');
    if (counter) counter.textContent = count;
}

function selectAllUsers() {
    document.querySelectorAll('input[name="user_ids[]"]').forEach(function(checkbox) {
        checkbox.checked = true;
    });
    updateSelectedCount();
}

function deselectAllUsers() {
    document.querySelectorAll('input[name="user_ids[]"]').forEach(function(checkbox) {
        checkbox.checked = false;
    });
    updateSelectedCount();
}

function selectFilteredUsers() {
    document.querySelectorAll('.bulk-user-row').forEach(function(row) {
        if (row.style.display !== 'none') {
            var checkbox = row.querySelector('input[name="user_ids[]"]');
            if (checkbox) checkbox.checked = true;
        }
    });
    updateSelectedCount();
}

function applyBulkFilters() {
    var query = (document.getElementById('bulk-user-search').value || '').toLowerCase().trim();

    document.querySelectorAll('.bulk-user-row').forEach(function(row) {
        var name = (row.getAttribute('data-user-name') || '').toLowerCase();
        var email = (row.getAttribute('data-user-email') || '').toLowerCase();

        var matchesQuery = !query || name.includes(query) || email.includes(query);

        row.style.display = matchesQuery ? 'flex' : 'none';
    });
}

document.querySelectorAll('input[name="user_ids[]"]').forEach(function(checkbox) {
    checkbox.addEventListener('change', updateSelectedCount);
});

document.getElementById('bulk-user-search').addEventListener('input', applyBulkFilters);

updateSelectedCount();
applyBulkFilters();
</script>
