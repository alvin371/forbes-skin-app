<div class="card">
    <div class="card-body">
        <h3>Holidays</h3>
        <div style="margin-bottom: 12px;">
            <a href="<?php echo site_url('admin/holidays/create'); ?>">Create Holiday</a>
        </div>

        <?php if (empty($holidays)): ?>
            <div>No holidays configured.</div>
        <?php else: ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th style="width: 160px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($holidays as $holiday): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($holiday['date']); ?></td>
                            <td><?php echo htmlspecialchars($holiday['name']); ?></td>
                            <td><?php echo ((int) $holiday['is_active'] === 1) ? 'Active' : 'Inactive'; ?></td>
                            <td>
                                <a href="<?php echo site_url('admin/holidays/' . $holiday['id'] . '/edit'); ?>">Edit</a>
                                <form method="post" action="<?php echo site_url('admin/holidays/' . $holiday['id'] . '/delete'); ?>" style="display:inline;" onsubmit="return confirm('Delete this holiday?');">
                                    <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                                        <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
                                    <?php endif; ?>
                                    <button type="submit" style="background: none; border: none; color: #b00020; padding: 0; margin-left: 8px;">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
