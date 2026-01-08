<div class="card">
    <div class="card-body">
        <h3>Leave Request Detail</h3>

        <?php if ($this->session->flashdata('message')): ?>
            <div style="margin: 10px 0; color: #0a6b2b;">
                <?php echo $this->session->flashdata('message'); ?>
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
                <th>Status</th>
                <td><?php echo htmlspecialchars($request['status']); ?></td>
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
        </table>

        <div style="margin-top: 12px;">
            <a href="<?php echo site_url('leave'); ?>">Back</a>
        </div>
    </div>
</div>
