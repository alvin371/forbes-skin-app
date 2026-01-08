<div class="card">
    <div class="card-body">
        <h3>Office Configuration</h3>

        <?php if ($this->session->flashdata('message')): ?>
            <div style="margin: 10px 0; color: #0a6b2b;">
                <?php echo $this->session->flashdata('message'); ?>
            </div>
        <?php endif; ?>

        <div style="margin-bottom: 12px;">
            <a href="<?php echo site_url('admin/offices/create'); ?>">Create Office</a>
        </div>

        <table border="1" cellpadding="8" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Lat</th>
                    <th>Lng</th>
                    <th>Radius (m)</th>
                    <th>Min Accuracy (m)</th>
                    <th>Active</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($offices)): ?>
                    <tr>
                        <td colspan="8">No offices configured.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($offices as $office): ?>
                        <tr>
                            <td><?php echo (int) $office['id']; ?></td>
                            <td><?php echo htmlspecialchars($office['name']); ?></td>
                            <td><?php echo htmlspecialchars($office['lat']); ?></td>
                            <td><?php echo htmlspecialchars($office['lng']); ?></td>
                            <td><?php echo htmlspecialchars($office['radius_m']); ?></td>
                            <td><?php echo htmlspecialchars($office['min_accuracy_m']); ?></td>
                            <td><?php echo ((int) $office['is_active'] === 1) ? 'Yes' : 'No'; ?></td>
                            <td>
                                <a href="<?php echo site_url('admin/offices/' . $office['id'] . '/edit'); ?>">Edit</a>
                                <form method="post" action="<?php echo site_url('admin/offices/' . $office['id'] . '/delete'); ?>" style="display:inline;" onsubmit="return confirm('Delete this office?');">
                                    <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                                        <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
                                    <?php endif; ?>
                                    <button type="submit">Delete</button>
                                </form>
                                <?php if ((int) $office['is_active'] !== 1): ?>
                                    <form method="post" action="<?php echo site_url('admin/offices/' . $office['id'] . '/activate'); ?>" style="display:inline;">
                                        <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                                            <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
                                        <?php endif; ?>
                                        <button type="submit">Set Active</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
