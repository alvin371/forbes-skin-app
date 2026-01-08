<div class="card">
    <div class="card-body">
        <h3>My Leave Requests</h3>

        <?php if ($this->session->flashdata('message')): ?>
            <div style="margin: 10px 0; color: #0a6b2b;">
                <?php echo $this->session->flashdata('message'); ?>
            </div>
        <?php endif; ?>

        <div style="margin-bottom: 12px;">
            <a href="<?php echo site_url('leave/create'); ?>">Submit Leave Request</a>
        </div>

        <table border="1" cellpadding="8" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th>Request No</th>
                    <th>Type</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Days</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="7">No leave requests found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($request['request_no']); ?></td>
                            <td><?php echo htmlspecialchars($request['leave_type_name']); ?></td>
                            <td><?php echo htmlspecialchars($request['start_date']); ?></td>
                            <td><?php echo htmlspecialchars($request['end_date']); ?></td>
                            <td><?php echo (int) $request['days_count']; ?></td>
                            <td><?php echo htmlspecialchars($request['status']); ?></td>
                            <td>
                                <a href="<?php echo site_url('leave/' . $request['id']); ?>">View</a>
                                <?php if ($request['status'] === 'PENDING_APPROVAL'): ?>
                                    <form method="post" action="<?php echo site_url('leave/' . $request['id'] . '/cancel'); ?>" style="display:inline;" onsubmit="return confirm('Cancel this request?');">
                                        <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                                            <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
                                        <?php endif; ?>
                                        <button type="submit">Cancel</button>
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
