<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09);">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px; height: 56px; display: flex; align-items: center;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);"><?php echo isset($leave_type['id']) && $leave_type['id'] ? 'Edit' : 'Create'; ?> Leave Type</h3>
    </div>
    <div class="card-body" style="padding: 16px;">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" style="padding: 8px 15px; border-radius: 2px; font-size: 14px; background-color: #fff2f0; border: 1px solid #ffccc7; color: #ff4d4f; margin-bottom: 16px;">
                <i class="bi bi-exclamation-circle"></i>
                <?php foreach ($errors as $error): ?>
                    <div style="margin-top: 4px;"><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo $form_action; ?>">
            <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
            <?php endif; ?>

            <div class="row g-2">
                <div class="col-md-6">
                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">
                            Code <span style="color: #ff4d4f;">*</span>
                        </label>
                        <input type="text" name="code" class="form-control" value="<?php echo htmlspecialchars($leave_type['code']); ?>" required style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">
                    </div>
                </div>
                <div class="col-md-6">
                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">
                            Name <span style="color: #ff4d4f;">*</span>
                        </label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($leave_type['name']); ?>" required style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">
                    Max Days Per Request <span style="font-size: 12px; color: rgba(0,0,0,0.45);">(optional)</span>
                </label>
                <input type="number" name="max_days_per_request" min="1" class="form-control" value="<?php echo htmlspecialchars($leave_type['max_days_per_request']); ?>" style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px; width: 100%;">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">
                    Default Quota for New Users <span style="font-size: 12px; color: rgba(0,0,0,0.45);">(optional)</span>
                </label>
                <input type="number" name="default_quota_days" min="0" class="form-control" value="<?php echo htmlspecialchars($leave_type['default_quota_days'] ?? ''); ?>" placeholder="Leave blank to skip" style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px; width: 100%;">
                <div style="margin-top: 6px; font-size: 12px; color: rgba(0,0,0,0.45);">Applied automatically when a new user is created.</div>
            </div>

            <div style="margin-bottom: 16px; padding: 12px; background-color: #fafafa; border: 1px solid #d9d9d9; border-radius: 2px;">
                <div style="margin-bottom: 8px;">
                    <label style="font-size: 14px; color: rgba(0,0,0,0.85); cursor: pointer;">
                        <input type="checkbox" name="is_paid" value="1" <?php echo ((int) $leave_type['is_paid'] === 1) ? 'checked' : ''; ?> style="margin-right: 8px;">
                        Paid Leave
                    </label>
                </div>
                <div style="margin-bottom: 8px;">
                    <label style="font-size: 14px; color: rgba(0,0,0,0.85); cursor: pointer;">
                        <input type="checkbox" name="requires_attachment" value="1" <?php echo ((int) $leave_type['requires_attachment'] === 1) ? 'checked' : ''; ?> style="margin-right: 8px;">
                        Requires Attachment
                    </label>
                </div>
                <div>
                    <label style="font-size: 14px; color: rgba(0,0,0,0.85); cursor: pointer;">
                        <input type="checkbox" name="is_active" value="1" <?php echo ((int) $leave_type['is_active'] === 1) ? 'checked' : ''; ?> style="margin-right: 8px;">
                        Active
                    </label>
                </div>
            </div>

            <div style="margin-top: 24px;">
                <button type="submit" class="btn btn-primary" style="background-color: #1890ff; border-color: #1890ff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px; margin-right: 8px;">
                    <i class="bi bi-check-circle"></i> Save
                </button>
                <a href="<?php echo site_url('admin/leave-types'); ?>" class="btn btn-outline-secondary" style="color: rgba(0,0,0,0.65); border-color: #d9d9d9; background: #fff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center;">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </form>
    </div>
</div>
