<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09);">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px; height: 56px; display: flex; align-items: center;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">Attendance Settings</h3>
    </div>
    <div class="card-body" style="padding: 16px;">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" style="padding: 8px 15px; border-radius: 2px; font-size: 14px; background-color: #fff2f0; border: 1px solid #ffccc7; color: #ff4d4f; margin-bottom: 16px;">
                <i class="bi bi-exclamation-circle"></i> Please fix the errors below.
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo site_url('admin/attendance-settings'); ?>">
            <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
            <?php endif; ?>

            <div style="margin-bottom: 16px;">
                <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">Weekend Type</label>
                <select name="weekend_type" class="form-select" style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px; width: 100%;">
                    <option value="SATURDAY_SUNDAY" <?php echo ($settings['weekend_type'] ?? '') === 'SATURDAY_SUNDAY' ? 'selected' : ''; ?>>Saturday & Sunday</option>
                    <option value="SUNDAY_ONLY" <?php echo ($settings['weekend_type'] ?? '') === 'SUNDAY_ONLY' ? 'selected' : ''; ?>>Sunday Only</option>
                </select>
                <?php if (!empty($errors['weekend_type'])): ?>
                    <div style="color: #ff4d4f; font-size: 12px; margin-top: 4px;"><?php echo $errors['weekend_type']; ?></div>
                <?php endif; ?>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 8px; display: block;">Roles Required for Attendance</label>
                <?php if (empty($roles)): ?>
                    <div class="alert alert-danger" style="padding: 8px 15px; border-radius: 2px; font-size: 14px; background-color: #fff2f0; border: 1px solid #ffccc7; color: #ff4d4f;">
                        <i class="bi bi-exclamation-circle"></i> Roles table not found.
                    </div>
                <?php else: ?>
                    <div style="padding: 12px; background-color: #fafafa; border: 1px solid #d9d9d9; border-radius: 2px;">
                        <?php foreach ($roles as $role): ?>
                            <?php $checked = in_array((int) $role['id'], $role_ids ?? array(), true); ?>
                            <label style="display: block; margin-bottom: 8px; font-size: 14px; color: rgba(0,0,0,0.85); cursor: pointer;">
                                <input type="checkbox" name="allowed_role_ids[]" value="<?php echo (int) $role['id']; ?>" <?php echo $checked ? 'checked' : ''; ?> style="margin-right: 8px;">
                                <?php echo htmlspecialchars($role['display_name'] ?: $role['name']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($errors['allowed_role_ids'])): ?>
                    <div style="color: #ff4d4f; font-size: 12px; margin-top: 4px;"><?php echo $errors['allowed_role_ids']; ?></div>
                <?php endif; ?>
            </div>

            <div style="margin-top: 24px;">
                <button type="submit" class="btn btn-primary" style="background-color: #1890ff; border-color: #1890ff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px;">
                    <i class="bi bi-check-circle"></i> Save Settings
                </button>
            </div>
        </form>
    </div>
</div>
