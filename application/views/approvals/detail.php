<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09);">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px; height: 56px; display: flex; align-items: center;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">Leave Approval Detail</h3>
    </div>
    <div class="card-body" style="padding: 16px;">
        <?php if ($this->session->flashdata('error')): ?>
            <div class="alert alert-danger" style="padding: 8px 15px; border-radius: 2px; font-size: 14px; background-color: #fff2f0; border: 1px solid #ffccc7; color: #ff4d4f; margin-bottom: 16px;">
                <i class="bi bi-exclamation-circle"></i> <?php echo $this->session->flashdata('error'); ?>
            </div>
        <?php endif; ?>

        <div style="background-color: #fafafa; border: 1px solid #d9d9d9; border-radius: 2px; padding: 16px; margin-bottom: 16px;">
            <div class="row g-3">
                <div class="col-md-6">
                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 12px; color: rgba(0,0,0,0.45); margin-bottom: 4px;">Request No</div>
                        <div style="font-size: 14px; color: rgba(0,0,0,0.85); font-weight: 500;"><?php echo htmlspecialchars($request['request_no']); ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 12px; color: rgba(0,0,0,0.45); margin-bottom: 4px;">Leave Type</div>
                        <div style="font-size: 14px; color: rgba(0,0,0,0.85);"><?php echo htmlspecialchars($request['leave_type_name']); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 12px; color: rgba(0,0,0,0.45); margin-bottom: 4px;">Start Date</div>
                        <div style="font-size: 14px; color: rgba(0,0,0,0.85);"><?php echo htmlspecialchars($request['start_date']); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 12px; color: rgba(0,0,0,0.45); margin-bottom: 4px;">End Date</div>
                        <div style="font-size: 14px; color: rgba(0,0,0,0.85);"><?php echo htmlspecialchars($request['end_date']); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 12px; color: rgba(0,0,0,0.45); margin-bottom: 4px;">Days</div>
                        <div style="font-size: 14px; color: rgba(0,0,0,0.85); font-weight: 500;"><?php echo (int) $request['days_count']; ?></div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 12px; color: rgba(0,0,0,0.45); margin-bottom: 4px;">Status</div>
                        <div>
                            <?php if ($request['status'] === 'PENDING_APPROVAL'): ?>
                                <span style="background-color: #fffbe6; color: #faad14; border: 1px solid #ffe58f; padding: 0 8px; height: 22px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px;">
                                    Pending Approval
                                </span>
                            <?php elseif ($request['status'] === 'APPROVED'): ?>
                                <span style="background-color: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f; padding: 0 8px; height: 22px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px;">
                                    Approved
                                </span>
                            <?php elseif ($request['status'] === 'DENIED'): ?>
                                <span style="background-color: #fff2f0; color: #ff4d4f; border: 1px solid #ffccc7; padding: 0 8px; height: 22px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px;">
                                    Denied
                                </span>
                            <?php else: ?>
                                <span style="background-color: #e6f7ff; color: #1890ff; border: 1px solid #91d5ff; padding: 0 8px; height: 22px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px;">
                                    <?php echo htmlspecialchars($request['status']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 12px; color: rgba(0,0,0,0.45); margin-bottom: 4px;">Reason</div>
                        <div style="font-size: 14px; color: rgba(0,0,0,0.65); line-height: 1.6;"><?php echo nl2br(htmlspecialchars($request['reason'])); ?></div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div>
                        <div style="font-size: 12px; color: rgba(0,0,0,0.45); margin-bottom: 4px;">Attachment</div>
                        <div style="font-size: 14px; color: rgba(0,0,0,0.65);">
                            <?php if (!empty($request['attachment_path'])): ?>
                                <?php
                                    $attachmentPath = $request['attachment_path'];
                                    $isAbsolute = preg_match('/^https?:\\/\\//i', $attachmentPath) === 1;
                                    $attachmentUrl = $isAbsolute ? $attachmentPath : base_url($attachmentPath);
                                    $extension = strtolower(pathinfo(parse_url($attachmentUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                                ?>
                                <?php if (in_array($extension, array('jpg', 'jpeg', 'png', 'gif', 'webp'), true)): ?>
                                    <img src="<?php echo htmlspecialchars($attachmentUrl); ?>" alt="Attachment" style="max-width: 100%; height: auto; border: 1px solid #f0f0f0; border-radius: 2px;">
                                <?php elseif ($extension === 'pdf'): ?>
                                    <iframe src="<?php echo htmlspecialchars($attachmentUrl); ?>" title="Attachment" style="width: 100%; height: 520px; border: 1px solid #f0f0f0; border-radius: 2px;"></iframe>
                                <?php else: ?>
                                    <a href="<?php echo htmlspecialchars($attachmentUrl); ?>" style="color: #1890ff; text-decoration: none;">
                                        <i class="bi bi-paperclip"></i> View Attachment
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: rgba(0,0,0,0.45);">No attachment</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($request['status'] === 'PENDING_APPROVAL'): ?>
            <div style="border: 1px solid #f0f0f0; border-radius: 2px; padding: 16px; margin-bottom: 16px;">
                <div style="font-size: 14px; font-weight: 500; color: rgba(0,0,0,0.85); margin-bottom: 12px;">Approval Actions</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <form method="post" action="<?php echo site_url('approvals/leaves/' . $request['id'] . '/approve'); ?>">
                            <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                                <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
                            <?php endif; ?>
                            <label style="display:block; font-size: 12px; color: rgba(0,0,0,0.45); margin-bottom: 6px;">Notes (optional)</label>
                            <textarea name="notes" rows="3" style="width: 100%; border: 1px solid #d9d9d9; border-radius: 2px; padding: 8px 11px; font-size: 14px; margin-bottom: 8px;"></textarea>
                            <button type="submit" class="btn btn-primary" style="background-color: #52c41a; border-color: #52c41a; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px; display: inline-flex; align-items: center;">
                                <i class="bi bi-check2-circle"></i> Approve
                            </button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <form method="post" action="<?php echo site_url('approvals/leaves/' . $request['id'] . '/reject'); ?>">
                            <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                                <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
                            <?php endif; ?>
                            <label style="display:block; font-size: 12px; color: rgba(0,0,0,0.45); margin-bottom: 6px;">Rejection Notes (required)</label>
                            <textarea name="notes" rows="3" required style="width: 100%; border: 1px solid #d9d9d9; border-radius: 2px; padding: 8px 11px; font-size: 14px; margin-bottom: 8px;"></textarea>
                            <button type="submit" class="btn btn-danger" style="background-color: #ff4d4f; border-color: #ff4d4f; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px; display: inline-flex; align-items: center;">
                                <i class="bi bi-x-circle"></i> Reject
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div>
            <a href="<?php echo site_url('approvals/leaves'); ?>" class="btn btn-outline-secondary" style="color: rgba(0,0,0,0.65); border-color: #d9d9d9; background: #fff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center;">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>
