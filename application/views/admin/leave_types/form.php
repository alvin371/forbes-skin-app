<div class="card">
    <div class="card-body">
        <h3><?php echo isset($leave_type['id']) && $leave_type['id'] ? 'Edit' : 'Create'; ?> Leave Type</h3>

        <?php if (!empty($errors)): ?>
            <div style="margin: 10px 0; color: #b71c1c;">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo $form_action; ?>">
            <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
            <?php endif; ?>

            <div style="margin-bottom: 12px;">
                <label>Code</label><br>
                <input type="text" name="code" value="<?php echo htmlspecialchars($leave_type['code']); ?>" required>
            </div>

            <div style="margin-bottom: 12px;">
                <label>Name</label><br>
                <input type="text" name="name" value="<?php echo htmlspecialchars($leave_type['name']); ?>" required>
            </div>

            <div style="margin-bottom: 12px;">
                <label>Max Days Per Request (optional)</label><br>
                <input type="number" name="max_days_per_request" min="1" value="<?php echo htmlspecialchars($leave_type['max_days_per_request']); ?>">
            </div>

            <div style="margin-bottom: 12px;">
                <label>
                    <input type="checkbox" name="is_paid" value="1" <?php echo ((int) $leave_type['is_paid'] === 1) ? 'checked' : ''; ?>>
                    Paid Leave
                </label>
            </div>

            <div style="margin-bottom: 12px;">
                <label>
                    <input type="checkbox" name="requires_attachment" value="1" <?php echo ((int) $leave_type['requires_attachment'] === 1) ? 'checked' : ''; ?>>
                    Requires Attachment
                </label>
            </div>

            <div style="margin-bottom: 12px;">
                <label>
                    <input type="checkbox" name="is_active" value="1" <?php echo ((int) $leave_type['is_active'] === 1) ? 'checked' : ''; ?>>
                    Active
                </label>
            </div>

            <div>
                <button type="submit">Save</button>
                <a href="<?php echo site_url('admin/leave-types'); ?>">Back</a>
            </div>
        </form>
    </div>
</div>
