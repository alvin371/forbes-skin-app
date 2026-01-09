<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09);">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px; height: 56px; display: flex; align-items: center; justify-content: space-between;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">Leave Approvals</h3>
    </div>
    <div class="card-body" style="padding: 16px;">
        <?php if ($this->session->flashdata('message')): ?>
            <div class="alert alert-success" style="padding: 8px 15px; border-radius: 2px; font-size: 14px; background-color: #f6ffed; border: 1px solid #b7eb8f; color: #52c41a; margin-bottom: 16px;">
                <i class="bi bi-check-circle"></i> <?php echo $this->session->flashdata('message'); ?>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table" style="border: 1px solid #f0f0f0; border-collapse: separate; border-spacing: 0;">
                <thead>
                    <tr style="background-color: #fafafa;">
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Request No</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Requester</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Type</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Start</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">End</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Days</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="7" style="padding: 24px; text-align: center; font-size: 14px; color: rgba(0,0,0,0.45);">No pending approvals.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($requests as $request): ?>
                            <tr style="border-bottom: 1px solid #f0f0f0; transition: background-color 0.3s;">
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($request['request_no']); ?></td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                    <?php
                                    $requester = trim((string) ($request['requester_name'] ?? ''));
                                    if (!empty($request['requester_email'])) {
                                        $requester .= $requester ? ' (' . $request['requester_email'] . ')' : $request['requester_email'];
                                    }
                                    echo htmlspecialchars($requester === '' ? ('User #' . (int) $request['user_id']) : $requester);
                                    ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($request['leave_type_name']); ?></td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($request['start_date']); ?></td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($request['end_date']); ?></td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo (int) $request['days_count']; ?></td>
                                <td style="padding: 12px 8px; font-size: 14px;">
                                    <a href="<?php echo site_url('approvals/leaves/' . $request['id']); ?>" style="color: #1890ff; font-size: 16px;" title="Review">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
