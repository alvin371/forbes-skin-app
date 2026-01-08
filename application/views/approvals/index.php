<div class="card">
    <div class="card-body">
        <h3>Pending Leave Approvals</h3>

        <?php if ($this->session->flashdata('message')): ?>
            <div style="margin: 10px 0; color: #0a6b2b;">
                <?php echo $this->session->flashdata('message'); ?>
            </div>
        <?php endif; ?>

        <table border="1" cellpadding="8" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th>Request No</th>
                    <th>Requester</th>
                    <th>Type</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Days</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="7">No pending approvals.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($request['request_no']); ?></td>
                            <td>
                                <?php
                                $requester = trim((string) ($request['requester_name'] ?? ''));
                                if (!empty($request['requester_email'])) {
                                    $requester .= $requester ? ' (' . $request['requester_email'] . ')' : $request['requester_email'];
                                }
                                echo htmlspecialchars($requester === '' ? ('User #' . (int) $request['user_id']) : $requester);
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($request['leave_type_name']); ?></td>
                            <td><?php echo htmlspecialchars($request['start_date']); ?></td>
                            <td><?php echo htmlspecialchars($request['end_date']); ?></td>
                            <td><?php echo (int) $request['days_count']; ?></td>
                            <td>
                                <a href="<?php echo site_url('approvals/leaves/' . $request['id']); ?>">Review</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
