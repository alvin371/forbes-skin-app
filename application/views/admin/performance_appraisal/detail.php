<?php
    $template = $template ?? [];
    $item_form = $item_form ?? [
        'id' => null,
        'order_no' => 0,
        'objective' => '',
        'kpi' => '',
        'target_value' => '',
        'unit' => '%',
        'weight' => ''
    ];
    $total_weight = isset($template['total_weight']) ? (float) $template['total_weight'] : 0;
?>

<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09); margin-bottom: 16px;">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px; height: 56px; display: flex; align-items: center; justify-content: space-between;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">Template Details</h3>
        <a href="<?php echo site_url('admin/performance-appraisal'); ?>" class="btn btn-outline-secondary" style="color: rgba(0,0,0,0.65); border-color: #d9d9d9; background: #fff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center;">
            <i class="bi bi-arrow-left"></i> Back
        </a>
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

        <form method="post" action="<?php echo site_url('admin/performance-appraisal/' . $template['id']); ?>">
            <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
            <?php endif; ?>

            <div style="margin-bottom: 16px;">
                <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">Template Name</label>
                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($template['name']); ?>" style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px; width: 100%;">
                <?php if (!empty($errors['name'])): ?>
                    <div style="color: #ff4d4f; font-size: 12px; margin-top: 4px;"><?php echo $errors['name']; ?></div>
                <?php endif; ?>
            </div>

            <div class="row g-2">
                <div class="col-md-4">
                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">Period Year</label>
                        <input type="number" name="period_year" class="form-control" value="<?php echo htmlspecialchars($template['period_year']); ?>" style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">
                        <?php if (!empty($errors['period_year'])): ?>
                            <div style="color: #ff4d4f; font-size: 12px; margin-top: 4px;"><?php echo $errors['period_year']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-8">
                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">Department <span style="font-size: 12px; color: rgba(0,0,0,0.45);">(leave blank or use * for all)</span></label>
                        <input type="text" name="department" class="form-control" value="<?php echo htmlspecialchars($template['department']); ?>" style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="font-size: 14px; color: rgba(0,0,0,0.85);">
                    <input type="checkbox" name="is_active" value="1" <?php echo ((int) $template['is_active'] === 1) ? 'checked' : ''; ?> style="margin-right: 8px;">
                    Activate template
                </label>
                <div style="font-size: 12px; color: rgba(0,0,0,0.45); margin-top: 4px;">Total weight must be 100% before activation.</div>
                <?php if (!empty($errors['is_active'])): ?>
                    <div style="color: #ff4d4f; font-size: 12px; margin-top: 4px;"><?php echo $errors['is_active']; ?></div>
                <?php endif; ?>
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                <div style="font-size: 14px; color: rgba(0,0,0,0.65);">
                    Total weight: <strong><?php echo number_format($total_weight, 2); ?>%</strong>
                </div>
                <button type="submit" class="btn btn-primary" style="background-color: #1890ff; border-color: #1890ff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px;">
                    <i class="bi bi-check-circle"></i> Save Template
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09); margin-bottom: 16px;">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">Template Items</h3>
    </div>
    <div class="card-body" style="padding: 16px;">
        <form method="post" action="<?php echo site_url('admin/performance-appraisal/' . $template['id'] . '/items/reorder'); ?>">
            <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
            <?php endif; ?>
            <div class="table-responsive">
                <table class="table" style="border: 1px solid #f0f0f0; border-collapse: separate; border-spacing: 0;">
                    <thead>
                        <tr style="background-color: #fafafa;">
                            <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Order</th>
                            <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Objective</th>
                            <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">KPI</th>
                            <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Target</th>
                            <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Unit</th>
                            <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Weight</th>
                            <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($template['items'])): ?>
                            <tr>
                                <td colspan="7" style="padding: 24px; text-align: center; font-size: 14px; color: rgba(0,0,0,0.45);">No items yet. Add the first KPI below.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($template['items'] as $item): ?>
                                <tr style="border-bottom: 1px solid #f0f0f0; transition: background-color 0.3s;">
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65); width: 90px;">
                                        <input type="number" name="order_no[<?php echo (int) $item['id']; ?>]" value="<?php echo (int) $item['order_no']; ?>" style="width: 70px; height: 28px; padding: 2px 6px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 13px;">
                                    </td>
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($item['objective']); ?></td>
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($item['kpi']); ?></td>
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($item['target_value']); ?></td>
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($item['unit']); ?></td>
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($item['weight']); ?>%</td>
                                    <td style="padding: 12px 8px; font-size: 14px;">
                                        <button type="button"
                                            class="btn btn-link"
                                            data-item-edit="true"
                                            data-item-id="<?php echo (int) $item['id']; ?>"
                                            data-item-order="<?php echo (int) $item['order_no']; ?>"
                                            data-item-objective="<?php echo htmlspecialchars($item['objective'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-item-kpi="<?php echo htmlspecialchars($item['kpi'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-item-target="<?php echo htmlspecialchars($item['target_value'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-item-unit="<?php echo htmlspecialchars($item['unit'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-item-weight="<?php echo htmlspecialchars($item['weight'], ENT_QUOTES, 'UTF-8'); ?>"
                                            style="color: #1890ff; padding: 0; margin-right: 8px; font-size: 14px; text-decoration: none;">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <form method="post" action="<?php echo site_url('admin/performance-appraisal/items/' . $item['id'] . '/delete'); ?>" style="display:inline;" onsubmit="return confirm('Delete this item?');">
                                            <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                                                <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
                                            <?php endif; ?>
                                            <button type="submit" style="background: none; border: none; padding: 0; cursor: pointer; color: #ff4d4f; font-size: 14px;">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if (!empty($template['items'])): ?>
                <button type="submit" class="btn btn-outline-secondary" style="color: rgba(0,0,0,0.65); border-color: #d9d9d9; background: #fff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px; margin-top: 12px;">
                    <i class="bi bi-arrow-repeat"></i> Save Order
                </button>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09);">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">
            <?php echo $item_form['id'] ? 'Edit Item' : 'Add New Item'; ?>
        </h3>
    </div>
    <div class="card-body" style="padding: 16px;">
        <?php if (!empty($item_errors)): ?>
            <div class="alert alert-danger" style="padding: 8px 15px; border-radius: 2px; font-size: 14px; background-color: #fff2f0; border: 1px solid #ffccc7; color: #ff4d4f; margin-bottom: 16px;">
                <i class="bi bi-exclamation-circle"></i> Please fix the item errors below.
            </div>
        <?php endif; ?>

        <form method="post" id="item-form" action="<?php echo site_url('admin/performance-appraisal/' . $template['id'] . '/items'); ?>" data-action-create="<?php echo site_url('admin/performance-appraisal/' . $template['id'] . '/items'); ?>" data-action-update="<?php echo site_url('admin/performance-appraisal/items/__ID__'); ?>">
            <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
            <?php endif; ?>
            <input type="hidden" name="item_id" id="item-id" value="<?php echo htmlspecialchars($item_form['id']); ?>">

            <div class="row g-2">
                <div class="col-md-2">
                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">Order</label>
                        <input type="number" name="order_no" id="item-order" class="form-control" value="<?php echo htmlspecialchars($item_form['order_no']); ?>" style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">
                    </div>
                </div>
                <div class="col-md-5">
                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">Objective</label>
                        <input type="text" name="objective" id="item-objective" class="form-control" value="<?php echo htmlspecialchars($item_form['objective']); ?>" style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">
                        <?php if (!empty($item_errors['objective'])): ?>
                            <div style="color: #ff4d4f; font-size: 12px; margin-top: 4px;"><?php echo $item_errors['objective']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-5">
                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">KPI</label>
                        <input type="text" name="kpi" id="item-kpi" class="form-control" value="<?php echo htmlspecialchars($item_form['kpi']); ?>" style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">
                        <?php if (!empty($item_errors['kpi'])): ?>
                            <div style="color: #ff4d4f; font-size: 12px; margin-top: 4px;"><?php echo $item_errors['kpi']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row g-2">
                <div class="col-md-3">
                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">Target Value</label>
                        <input type="number" step="0.01" name="target_value" id="item-target" class="form-control" value="<?php echo htmlspecialchars($item_form['target_value']); ?>" style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">
                        <?php if (!empty($item_errors['target_value'])): ?>
                            <div style="color: #ff4d4f; font-size: 12px; margin-top: 4px;"><?php echo $item_errors['target_value']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">Unit</label>
                        <input type="text" name="unit" id="item-unit" class="form-control" value="<?php echo htmlspecialchars($item_form['unit']); ?>" style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">
                        <?php if (!empty($item_errors['unit'])): ?>
                            <div style="color: #ff4d4f; font-size: 12px; margin-top: 4px;"><?php echo $item_errors['unit']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">Weight (%)</label>
                        <input type="number" step="0.01" name="weight" id="item-weight" class="form-control" value="<?php echo htmlspecialchars($item_form['weight']); ?>" style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">
                        <?php if (!empty($item_errors['weight'])): ?>
                            <div style="color: #ff4d4f; font-size: 12px; margin-top: 4px;"><?php echo $item_errors['weight']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div style="margin-top: 8px;">
                <button type="submit" class="btn btn-primary" style="background-color: #1890ff; border-color: #1890ff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px; margin-right: 8px;">
                    <i class="bi bi-check-circle"></i> Save Item
                </button>
                <button type="button" id="item-cancel" class="btn btn-outline-secondary" style="color: rgba(0,0,0,0.65); border-color: #d9d9d9; background: #fff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px;">
                    <i class="bi bi-x-circle"></i> Cancel Edit
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        var form = document.getElementById('item-form');
        if (!form) {
            return;
        }

        var actionCreate = form.getAttribute('data-action-create');
        var actionUpdate = form.getAttribute('data-action-update');
        var idInput = document.getElementById('item-id');
        var orderInput = document.getElementById('item-order');
        var objectiveInput = document.getElementById('item-objective');
        var kpiInput = document.getElementById('item-kpi');
        var targetInput = document.getElementById('item-target');
        var unitInput = document.getElementById('item-unit');
        var weightInput = document.getElementById('item-weight');
        var cancelButton = document.getElementById('item-cancel');

        function resetForm() {
            form.setAttribute('action', actionCreate);
            idInput.value = '';
            orderInput.value = 0;
            objectiveInput.value = '';
            kpiInput.value = '';
            targetInput.value = '';
            unitInput.value = '%';
            weightInput.value = '';
        }

        document.querySelectorAll('[data-item-edit="true"]').forEach(function (button) {
            button.addEventListener('click', function () {
                var itemId = button.getAttribute('data-item-id');
                form.setAttribute('action', actionUpdate.replace('__ID__', itemId));
                idInput.value = itemId;
                orderInput.value = button.getAttribute('data-item-order');
                objectiveInput.value = button.getAttribute('data-item-objective');
                kpiInput.value = button.getAttribute('data-item-kpi');
                targetInput.value = button.getAttribute('data-item-target');
                unitInput.value = button.getAttribute('data-item-unit');
                weightInput.value = button.getAttribute('data-item-weight');
                window.scrollTo({ top: form.offsetTop - 80, behavior: 'smooth' });
            });
        });

        if (cancelButton) {
            cancelButton.addEventListener('click', function () {
                resetForm();
            });
        }
    })();
</script>
