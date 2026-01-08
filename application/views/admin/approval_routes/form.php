<div class="card">
    <div class="card-body">
        <h3><?php echo isset($route['id']) && $route['id'] ? 'Edit' : 'Create'; ?> Approval Route</h3>

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
                <label>User</label><br>
                <?php if (!empty($users)): ?>
                    <select name="user_id" required>
                        <option value="">Select user</option>
                        <?php foreach ($users as $user): ?>
                            <?php
                            $label = trim($user['name']);
                            if (!empty($user['email'])) {
                                $label .= $label ? ' (' . $user['email'] . ')' : $user['email'];
                            }
                            if ($label === '') {
                                $label = 'User #' . (int) $user['id'];
                            }
                            ?>
                            <option value="<?php echo (int) $user['id']; ?>" <?php echo ((int) $route['user_id'] === (int) $user['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="number" name="user_id" value="<?php echo htmlspecialchars($route['user_id']); ?>" required>
                <?php endif; ?>
            </div>

            <div style="margin-bottom: 12px;">
                <label>Approver</label><br>
                <?php if (!empty($users)): ?>
                    <select name="approver_id" required>
                        <option value="">Select approver</option>
                        <?php foreach ($users as $user): ?>
                            <?php
                            $label = trim($user['name']);
                            if (!empty($user['email'])) {
                                $label .= $label ? ' (' . $user['email'] . ')' : $user['email'];
                            }
                            if ($label === '') {
                                $label = 'User #' . (int) $user['id'];
                            }
                            ?>
                            <option value="<?php echo (int) $user['id']; ?>" <?php echo ((int) $route['approver_id'] === (int) $user['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="number" name="approver_id" value="<?php echo htmlspecialchars($route['approver_id']); ?>" required>
                <?php endif; ?>
            </div>

            <div style="margin-bottom: 12px;">
                <label>
                    <input type="checkbox" name="is_active" value="1" <?php echo ((int) $route['is_active'] === 1) ? 'checked' : ''; ?>>
                    Active
                </label>
            </div>

            <div>
                <button type="submit">Save</button>
                <a href="<?php echo site_url('admin/approval-routes'); ?>">Back</a>
            </div>
        </form>

        <?php if (empty($users)): ?>
            <div style="margin-top: 12px; color: #8a6d3b;">
                ASSUMPTION: Users table not detected. TODO: confirm user table structure to show user names.
            </div>
        <?php endif; ?>
    </div>
</div>
