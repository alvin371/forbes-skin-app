<?php
    $submission = $submission ?? [];
?>
<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09);">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px; height: 56px; display: flex; align-items: center; justify-content: space-between;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">Submission Detail</h3>
        <a href="<?php echo site_url('admin/performance-appraisal/submissions'); ?>" class="btn btn-outline-secondary" style="color: rgba(0,0,0,0.65); border-color: #d9d9d9; background: #fff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center;">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
    <div class="card-body" style="padding: 16px;">
        <div class="row g-2" style="margin-bottom: 16px;">
            <div class="col-md-3">
                <div style="font-size: 13px; color: rgba(0,0,0,0.45);">Employee</div>
                <div style="font-size: 14px; color: rgba(0,0,0,0.85); font-weight: 500;">
                    <?php echo htmlspecialchars($submission['employee_name'] ?: $submission['employee_id']); ?>
                </div>
            </div>
            <div class="col-md-3">
                <div style="font-size: 13px; color: rgba(0,0,0,0.45);">Role</div>
                <div style="font-size: 14px; color: rgba(0,0,0,0.85); font-weight: 500;">
                    <?php echo htmlspecialchars($submission['employee_role_name'] ?: 'N/A'); ?>
                </div>
            </div>
            <div class="col-md-3">
                <div style="font-size: 13px; color: rgba(0,0,0,0.45);">Template</div>
                <div style="font-size: 14px; color: rgba(0,0,0,0.85); font-weight: 500;">
                    <?php echo htmlspecialchars($submission['template_name']); ?>
                </div>
            </div>
            <div class="col-md-3">
                <div style="font-size: 13px; color: rgba(0,0,0,0.45);">Total Score</div>
                <div style="font-size: 16px; color: rgba(0,0,0,0.85); font-weight: 600;">
                    <?php echo number_format((float) $submission['total_score'], 2); ?>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table" style="border: 1px solid #f0f0f0; border-collapse: separate; border-spacing: 0;">
                <thead>
                    <tr style="background-color: #fafafa;">
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Order</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Objective</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">KPI</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Target</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Actual</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Weight</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Score Ratio</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Final Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($submission['items'])): ?>
                        <tr>
                            <td colspan="8" style="padding: 24px; text-align: center; font-size: 14px; color: rgba(0,0,0,0.45);">No items available.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($submission['items'] as $item): ?>
                            <tr style="border-bottom: 1px solid #f0f0f0;">
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo (int) $item['order_no']; ?></td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($item['objective']); ?></td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($item['kpi']); ?></td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($item['target_value']); ?></td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($item['actual_value']); ?></td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($item['weight']); ?>%</td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo number_format((float) $item['score_ratio'], 4); ?></td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo number_format((float) $item['final_score'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
