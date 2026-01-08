<div class="card">
    <div class="card-body">
        <h3>Leave Approval Detail</h3>

        <?php if ($this->session->flashdata('error')): ?>
            <div style="margin: 10px 0; color: #b71c1c;">
                <?php echo $this->session->flashdata('error'); ?>
            </div>
        <?php endif; ?>

        <table border="1" cellpadding="8" cellspacing="0" width="100%">
            <tr>
                <th>Request No</th>
                <td><?php echo htmlspecialchars($request['request_no']); ?></td>
            </tr>
            <tr>
                <th>Leave Type</th>
                <td><?php echo htmlspecialchars($request['leave_type_name']); ?></td>
            </tr>
            <tr>
                <th>Start Date</th>
                <td><?php echo htmlspecialchars($request['start_date']); ?></td>
            </tr>
            <tr>
                <th>End Date</th>
                <td><?php echo htmlspecialchars($request['end_date']); ?></td>
            </tr>
            <tr>
                <th>Days</th>
                <td><?php echo (int) $request['days_count']; ?></td>
            </tr>
            <tr>
                <th>Reason</th>
                <td><?php echo nl2br(htmlspecialchars($request['reason'])); ?></td>
            </tr>
            <tr>
                <th>Attachment</th>
                <td>
                    <?php if (!empty($request['attachment_path'])): ?>
                        <a href="<?php echo base_url($request['attachment_path']); ?>" target="_blank">View Attachment</a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Status</th>
                <td><?php echo htmlspecialchars($request['status']); ?></td>
            </tr>
        </table>

        <?php if ($approval['action'] === 'PENDING'): ?>
            <div style="margin-top: 16px;">
                <form method="post" action="<?php echo site_url('approvals/leaves/' . $request['id'] . '/approve'); ?>" style="display:inline;">
                    <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                        <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
                    <?php endif; ?>
                    <label>Notes (optional)</label><br>
                    <textarea name="notes" rows="3" cols="40"></textarea><br>
                    <button type="submit">Approve</button>
                </form>

                <form method="post" action="<?php echo site_url('approvals/leaves/' . $request['id'] . '/reject'); ?>" style="display:inline; margin-left: 12px;">
                    <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                        <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
                    <?php endif; ?>
                    <label>Rejection Notes (required)</label><br>
                    <textarea name="notes" rows="3" cols="40" required></textarea><br>
                    <button type="submit">Reject</button>
                </form>
            </div>
        <?php else: ?>
            <div style="margin-top: 16px;">
                <strong>Decision:</strong> <?php echo htmlspecialchars($approval['action']); ?>
            </div>
        <?php endif; ?>

        <div style="margin-top: 12px;">
            <a href="<?php echo site_url('approvals/leaves'); ?>">Back</a>
        </div>
    </div>
</div>
