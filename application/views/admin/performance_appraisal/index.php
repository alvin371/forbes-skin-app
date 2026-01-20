<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09);">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px; height: 56px; display: flex; align-items: center; justify-content: space-between;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">Performance Appraisal Templates</h3>
        <div>
            <a href="<?php echo site_url('admin/performance-appraisal/submissions'); ?>" class="btn btn-outline-secondary" style="color: rgba(0,0,0,0.65); border-color: #d9d9d9; background: #fff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; margin-right: 8px;">
                <i class="bi bi-clipboard-check"></i> View Submissions
            </a>
            <a href="<?php echo site_url('admin/performance-appraisal/create'); ?>" class="btn btn-primary" style="background-color: #1890ff; border-color: #1890ff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center;">
                <i class="bi bi-plus"></i> Create Template
            </a>
        </div>
    </div>
    <div class="card-body" style="padding: 16px;">
        <?php if ($this->session->flashdata('message')): ?>
            <div class="alert alert-success" style="padding: 8px 15px; border-radius: 2px; font-size: 14px; background-color: #f6ffed; border: 1px solid #b7eb8f; color: #52c41a; margin-bottom: 16px;">
                <i class="bi bi-check-circle"></i> <?php echo $this->session->flashdata('message'); ?>
            </div>
        <?php endif; ?>
        <?php if ($this->session->flashdata('error')): ?>
            <div class="alert alert-danger" style="padding: 8px 15px; border-radius: 2px; font-size: 14px; background-color: #fff2f0; border: 1px solid #ffccc7; color: #ff4d4f; margin-bottom: 16px;">
                <i class="bi bi-exclamation-circle"></i> <?php echo $this->session->flashdata('error'); ?>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table" style="border: 1px solid #f0f0f0; border-collapse: separate; border-spacing: 0;">
                <thead>
                    <tr style="background-color: #fafafa;">
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Name</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Year</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Role</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Active</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Total Weight</th>
                        <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($templates)): ?>
                        <tr>
                            <td colspan="6" style="padding: 24px; text-align: center; font-size: 14px; color: rgba(0,0,0,0.45);">No templates created yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($templates as $template): ?>
                            <?php
                                $total_weight = $template['total_weight'] !== null ? (float) $template['total_weight'] : 0;
                                $role_display = $template['role_display_name'] ?? null;
                                $role_display = $role_display ?: 'All Roles';
                            ?>
                            <tr style="border-bottom: 1px solid #f0f0f0; transition: background-color 0.3s;">
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($template['name']); ?></td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo (int) $template['period_year']; ?></td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($role_display); ?></td>
                                <td style="padding: 12px 8px; font-size: 14px;">
                                    <?php if ((int) $template['is_active'] === 1): ?>
                                        <span style="background-color: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f; padding: 0 8px; height: 22px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px;">
                                            Active
                                        </span>
                                    <?php else: ?>
                                        <span style="background-color: #fff2f0; color: #ff4d4f; border: 1px solid #ffccc7; padding: 0 8px; height: 22px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px;">
                                            Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                    <?php echo number_format($total_weight, 2); ?>%
                                </td>
                                <td style="padding: 12px 8px; font-size: 14px;">
                                    <a href="<?php echo site_url('admin/performance-appraisal/' . $template['id'] . '/edit'); ?>" style="color: #1890ff; margin-right: 12px; font-size: 16px;" title="View / Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="post" action="<?php echo site_url('admin/performance-appraisal/' . $template['id']); ?>" style="display:inline; margin-right: 12px;">
                                        <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                                            <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
                                        <?php endif; ?>
                                        <input type="hidden" name="name" value="<?php echo htmlspecialchars($template['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="period_year" value="<?php echo (int) $template['period_year']; ?>">
                                        <input type="hidden" name="role_id" value="<?php echo htmlspecialchars($template['role_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="is_active" value="<?php echo (int) $template['is_active'] === 1 ? 0 : 1; ?>">
                                        <button type="submit" style="background: none; border: none; padding: 0; cursor: pointer; color: <?php echo (int) $template['is_active'] === 1 ? '#ff4d4f' : '#52c41a'; ?>; font-size: 16px;" title="<?php echo (int) $template['is_active'] === 1 ? 'Deactivate' : 'Activate'; ?>">
                                            <i class="bi <?php echo (int) $template['is_active'] === 1 ? 'bi-toggle-on' : 'bi-toggle-off'; ?>"></i>
                                        </button>
                                    </form>
                                    <form method="post" action="<?php echo site_url('admin/performance-appraisal/' . $template['id'] . '/delete'); ?>" style="display:inline;" onsubmit="return confirm('Delete this template?');">
                                        <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                                            <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
                                        <?php endif; ?>
                                        <button type="submit" style="background: none; border: none; padding: 0; cursor: pointer; color: #ff4d4f; font-size: 16px;" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
