<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09);">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px; height: 56px; display: flex; align-items: center; justify-content: space-between;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">Performance Submissions</h3>
        <a href="<?php echo site_url('admin/performance-appraisal'); ?>" class="btn btn-outline-secondary" style="color: rgba(0,0,0,0.65); border-color: #d9d9d9; background: #fff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center;">
            <i class="bi bi-arrow-left"></i> Back to Templates
        </a>
    </div>
    <div class="card-body" style="padding: 16px;">
        <form method="get" action="<?php echo site_url('admin/performance-appraisal/submissions'); ?>" style="margin-bottom: 16px;">
            <div class="row g-2">
                <div class="col-md-4">
                    <label style="font-size: 13px; color: rgba(0,0,0,0.65); margin-bottom: 4px; display: block;">Template</label>
                    <select name="template_id" class="form-control" style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">
                        <option value="">All Templates</option>
                        <?php foreach ($templates as $template): ?>
                            <option value="<?php echo (int) $template['id']; ?>" <?php echo (!empty($filters['template_id']) && (int) $filters['template_id'] === (int) $template['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($template['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label style="font-size: 13px; color: rgba(0,0,0,0.65); margin-bottom: 4px; display: block;">Period Year</label>
                    <input type="number" name="period_year" class="form-control" value="<?php echo isset($filters['period_year']) ? htmlspecialchars($filters['period_year']) : ''; ?>" style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">
                </div>
                <div class="col-md-3">
                    <label style="font-size: 13px; color: rgba(0,0,0,0.65); margin-bottom: 4px; display: block;">Department</label>
                    <input type="text" name="department" class="form-control" value="<?php echo isset($filters['department']) ? htmlspecialchars($filters['department']) : ''; ?>" style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">
                </div>
                <div class="col-md-2" style="display: flex; align-items: flex-end;">
                    <button type="submit" class="btn btn-primary" style="background-color: #1890ff; border-color: #1890ff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px; width: 100%;">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table" style="border: 1px solid #f0f0f0; border-collapse: separate; border-spacing: 0;">
                <thead>
                    <tr style="background-color: #fafafa;">
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Employee</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Template</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Period</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Total Score</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Status</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($submissions)): ?>
                        <tr>
                            <td colspan="6" style="padding: 24px; text-align: center; font-size: 14px; color: rgba(0,0,0,0.45);">No submissions found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($submissions as $submission): ?>
                            <tr style="border-bottom: 1px solid #f0f0f0; transition: background-color 0.3s;">
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                    <?php echo htmlspecialchars($submission['employee_name'] ?: $submission['employee_id']); ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($submission['template_name']); ?></td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo (int) $submission['period_year']; ?></td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo number_format((float) $submission['total_score'], 2); ?></td>
                                <td style="padding: 12px 8px; font-size: 14px;">
                                    <?php if ($submission['status'] === 'SUBMITTED'): ?>
                                        <span style="background-color: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f; padding: 0 8px; height: 22px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px;">
                                            Submitted
                                        </span>
                                    <?php else: ?>
                                        <span style="background-color: #fff7e6; color: #fa8c16; border: 1px solid #ffd591; padding: 0 8px; height: 22px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px;">
                                            Draft
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px;">
                                    <a href="<?php echo site_url('admin/performance-appraisal/submissions/' . $submission['id']); ?>" style="color: #1890ff; font-size: 14px; text-decoration: none;">
                                        <i class="bi bi-eye"></i> View
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
