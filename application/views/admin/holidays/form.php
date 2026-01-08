<div class="card">
    <div class="card-body">
        <h3><?php echo $holiday['id'] ? 'Edit Holiday' : 'Create Holiday'; ?></h3>

        <?php if (!empty($errors)): ?>
            <div style="margin: 10px 0; color: #b00020;">
                Please fix the errors below.
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo $form_action; ?>">
            <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
            <?php endif; ?>

            <div style="margin-bottom: 12px;">
                <label>Date</label><br>
                <input type="date" name="date" value="<?php echo htmlspecialchars($holiday['date']); ?>" style="width: 100%;">
                <?php if (!empty($errors['date'])): ?>
                    <div style="color: #b00020;"><?php echo $errors['date']; ?></div>
                <?php endif; ?>
            </div>

            <div style="margin-bottom: 12px;">
                <label>Name</label><br>
                <input type="text" name="name" value="<?php echo htmlspecialchars($holiday['name']); ?>" style="width: 100%;">
                <?php if (!empty($errors['name'])): ?>
                    <div style="color: #b00020;"><?php echo $errors['name']; ?></div>
                <?php endif; ?>
            </div>

            <div style="margin-bottom: 12px;">
                <label>
                    <input type="checkbox" name="is_active" value="1" <?php echo ((int) $holiday['is_active'] === 1) ? 'checked' : ''; ?>>
                    Active
                </label>
            </div>

            <div style="margin-top: 16px;">
                <button type="submit">Save</button>
                <a href="<?php echo site_url('admin/holidays'); ?>" style="margin-left: 8px;">Cancel</a>
            </div>
        </form>
    </div>
</div>
