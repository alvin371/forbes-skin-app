<div class="card">
    <div class="card-body">
        <h3>Attendance Settings</h3>

        <?php if (!empty($errors)): ?>
            <div style="margin: 10px 0; color: #b00020;">
                Please fix the errors below.
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo site_url('admin/attendance-settings'); ?>">
            <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
            <?php endif; ?>

            <div style="margin-bottom: 12px;">
                <label>Weekend Type</label><br>
                <select name="weekend_type" style="width: 100%;">
                    <option value="SATURDAY_SUNDAY" <?php echo ($settings['weekend_type'] ?? '') === 'SATURDAY_SUNDAY' ? 'selected' : ''; ?>>Saturday & Sunday</option>
                    <option value="SUNDAY_ONLY" <?php echo ($settings['weekend_type'] ?? '') === 'SUNDAY_ONLY' ? 'selected' : ''; ?>>Sunday Only</option>
                </select>
                <?php if (!empty($errors['weekend_type'])): ?>
                    <div style="color: #b00020;"><?php echo $errors['weekend_type']; ?></div>
                <?php endif; ?>
            </div>

            <div style="margin-bottom: 12px;">
                <label>Roles Required for Attendance</label>
                <?php if (empty($roles)): ?>
                    <div style="margin-top: 8px; color: #b00020;">Roles table not found.</div>
                <?php else: ?>
                    <div style="margin-top: 8px;">
                        <?php foreach ($roles as $role): ?>
                            <?php $checked = in_array((int) $role['id'], $role_ids ?? array(), true); ?>
                            <label style="display: block; margin-bottom: 6px;">
                                <input type="checkbox" name="allowed_role_ids[]" value="<?php echo (int) $role['id']; ?>" <?php echo $checked ? 'checked' : ''; ?>>
                                <?php echo htmlspecialchars($role['display_name'] ?: $role['name']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($errors['allowed_role_ids'])): ?>
                    <div style="color: #b00020;"><?php echo $errors['allowed_role_ids']; ?></div>
                <?php endif; ?>
            </div>

            <div style="margin-top: 16px;">
                <button type="submit">Save</button>
            </div>
        </form>
    </div>
</div>
