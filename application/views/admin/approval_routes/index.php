<div class="card">
    <div class="card-body">
        <h3>Approval Routes</h3>

        <?php if ($this->session->flashdata('message')): ?>
            <div style="margin: 10px 0; color: #0a6b2b;">
                <?php echo $this->session->flashdata('message'); ?>
            </div>
        <?php endif; ?>

        <div style="margin-bottom: 12px;">
            <a href="<?php echo site_url('admin/approval-routes/create'); ?>">Create Approval Route</a>
        </div>

        <?php
        $userMap = array();
        if (!empty($users)) {
            foreach ($users as $user) {
                $label = trim($user['name']);
                if (!empty($user['email'])) {
                    $label .= $label ? ' (' . $user['email'] . ')' : $user['email'];
                }
                $userMap[(int) $user['id']] = $label === '' ? ('User #' . (int) $user['id']) : $label;
            }
        }
        ?>

        <table border="1" cellpadding="8" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Approver</th>
                    <th>Active</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($routes)): ?>
                    <tr>
                        <td colspan="5">No approval routes configured.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($routes as $route): ?>
                        <tr>
                            <td><?php echo (int) $route['id']; ?></td>
                            <td><?php echo isset($userMap[(int) $route['user_id']]) ? htmlspecialchars($userMap[(int) $route['user_id']]) : ('User #' . (int) $route['user_id']); ?></td>
                            <td><?php echo isset($userMap[(int) $route['approver_id']]) ? htmlspecialchars($userMap[(int) $route['approver_id']]) : ('User #' . (int) $route['approver_id']); ?></td>
                            <td><?php echo ((int) $route['is_active'] === 1) ? 'Yes' : 'No'; ?></td>
                            <td>
                                <a href="<?php echo site_url('admin/approval-routes/' . $route['id'] . '/edit'); ?>">Edit</a>
                                <form method="post" action="<?php echo site_url('admin/approval-routes/' . $route['id'] . '/delete'); ?>" style="display:inline;" onsubmit="return confirm('Deactivate this approval route?');">
                                    <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                                        <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
                                    <?php endif; ?>
                                    <button type="submit">Deactivate</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if (empty($users)): ?>
            <div style="margin-top: 12px; color: #8a6d3b;">
                ASSUMPTION: Users table not detected. TODO: confirm user table structure to show user names.
            </div>
        <?php endif; ?>
    </div>
</div>
