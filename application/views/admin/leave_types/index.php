<div class="card">
    <div class="card-body">
        <h3>Leave Types</h3>

        <?php if ($this->session->flashdata('message')): ?>
            <div style="margin: 10px 0; color: #0a6b2b;">
                <?php echo $this->session->flashdata('message'); ?>
            </div>
        <?php endif; ?>

        <div style="margin-bottom: 12px;">
            <a href="<?php echo site_url('admin/leave-types/create'); ?>">Create Leave Type</a>
        </div>

        <table border="1" cellpadding="8" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Paid</th>
                    <th>Attachment</th>
                    <th>Max Days</th>
                    <th>Active</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leave_types)): ?>
                    <tr>
                        <td colspan="8">No leave types configured.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($leave_types as $leaveType): ?>
                        <tr>
                            <td><?php echo (int) $leaveType['id']; ?></td>
                            <td><?php echo htmlspecialchars($leaveType['code']); ?></td>
                            <td><?php echo htmlspecialchars($leaveType['name']); ?></td>
                            <td><?php echo ((int) $leaveType['is_paid'] === 1) ? 'Yes' : 'No'; ?></td>
                            <td><?php echo ((int) $leaveType['requires_attachment'] === 1) ? 'Yes' : 'No'; ?></td>
                            <td><?php echo $leaveType['max_days_per_request'] === null ? '-' : (int) $leaveType['max_days_per_request']; ?></td>
                            <td><?php echo ((int) $leaveType['is_active'] === 1) ? 'Yes' : 'No'; ?></td>
                            <td>
                                <a href="<?php echo site_url('admin/leave-types/' . $leaveType['id'] . '/edit'); ?>">Edit</a>
                                <form method="post" action="<?php echo site_url('admin/leave-types/' . $leaveType['id'] . '/delete'); ?>" style="display:inline;" onsubmit="return confirm('Deactivate this leave type?');">
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
    </div>
</div>
