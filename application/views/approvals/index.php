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
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Status</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="8" style="padding: 24px; text-align: center; font-size: 14px; color: rgba(0,0,0,0.45);">No pending approvals.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($requests as $request): ?>
                            <?php
                            $displayName = trim((string) ($request['requester_name'] ?? ''));
                            $username = trim((string) ($request['requester_username'] ?? ''));
                            $email = trim((string) ($request['requester_email'] ?? ''));
                            $roleText = trim((string) ($request['requester_role_text'] ?? ''));
                            $position = trim((string) ($request['requester_position'] ?? ''));
                            $primaryLabel = $displayName !== '' ? $displayName : ($username !== '' ? $username : ('User #' . (int) $request['user_id']));

                            $status = strtoupper((string) ($request['status'] ?? ''));
                            $statusLabel = ucwords(strtolower(str_replace('_', ' ', $status)));
                            $statusStyles = array(
                                'PENDING_APPROVAL' => array('bg' => '#fffbe6', 'color' => '#faad14', 'border' => '#ffe58f'),
                                'APPROVED' => array('bg' => '#f6ffed', 'color' => '#52c41a', 'border' => '#b7eb8f'),
                                'REJECTED' => array('bg' => '#fff2f0', 'color' => '#ff4d4f', 'border' => '#ffccc7'),
                                'DENIED' => array('bg' => '#fff2f0', 'color' => '#ff4d4f', 'border' => '#ffccc7'),
                            );
                            $statusStyle = isset($statusStyles[$status]) ? $statusStyles[$status] : array('bg' => '#e6f7ff', 'color' => '#1890ff', 'border' => '#91d5ff');
                            ?>
                            <tr style="border-bottom: 1px solid #f0f0f0; transition: background-color 0.3s;">
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($request['request_no']); ?></td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                    <div style="font-weight: 500; color: rgba(0,0,0,0.85);"><?php echo htmlspecialchars($primaryLabel); ?></div>
                                    <?php if ($username !== '' || $email !== '' || $roleText !== '' || $position !== ''): ?>
                                        <div style="font-size: 12px; color: rgba(0,0,0,0.45); line-height: 1.5;">
                                            <?php if ($username !== ''): ?>
                                                <div>@<?php echo htmlspecialchars($username); ?></div>
                                            <?php endif; ?>
                                            <?php if ($email !== ''): ?>
                                                <div><?php echo htmlspecialchars($email); ?></div>
                                            <?php endif; ?>
                                            <?php if ($roleText !== '' || $position !== ''): ?>
                                                <div><?php echo htmlspecialchars(trim($roleText . ($roleText !== '' && $position !== '' ? ' • ' : '') . $position)); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($request['leave_type_name']); ?></td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($request['start_date']); ?></td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($request['end_date']); ?></td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo (int) $request['days_count']; ?></td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                    <span style="background-color: <?php echo $statusStyle['bg']; ?>; color: <?php echo $statusStyle['color']; ?>; border: 1px solid <?php echo $statusStyle['border']; ?>; padding: 0 8px; height: 22px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px;">
                                        <?php echo htmlspecialchars($statusLabel); ?>
                                    </span>
                                </td>
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
