<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09);">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px; height: 56px; display: flex; align-items: center;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">Create Performance Template</h3>
    </div>
    <div class="card-body" style="padding: 16px;">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" style="padding: 8px 15px; border-radius: 2px; font-size: 14px; background-color: #fff2f0; border: 1px solid #ffccc7; color: #ff4d4f; margin-bottom: 16px;">
                <i class="bi bi-exclamation-circle"></i> Please fix the errors below.
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo $form_action; ?>">
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
                    Activate template after creation
                </label>
                <div style="font-size: 12px; color: rgba(0,0,0,0.45); margin-top: 4px;">Activation requires item weights to sum to 100.</div>
                <?php if (!empty($errors['is_active'])): ?>
                    <div style="color: #ff4d4f; font-size: 12px; margin-top: 4px;"><?php echo $errors['is_active']; ?></div>
                <?php endif; ?>
                <?php if (!empty($errors['items'])): ?>
                    <div style="color: #ff4d4f; font-size: 12px; margin-top: 4px;"><?php echo $errors['items']; ?></div>
                <?php endif; ?>
            </div>

            <?php
                $item_rows = $item_rows ?? [];
                $item_errors = $item_errors ?? [];
                $total_weight = 0;
                foreach ($item_rows as $row) {
                    if (is_numeric($row['weight'])) {
                        $total_weight += (float) $row['weight'];
                    }
                }
            ?>

            <div style="margin-top: 24px;">
                <h4 style="margin: 0 0 12px; font-size: 15px; font-weight: 500; color: rgba(0,0,0,0.85);">Template Items</h4>
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
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="item-rows">
                            <?php if (empty($item_rows)): ?>
                                <?php $item_rows = [['order_no' => 1, 'objective' => '', 'kpi' => '', 'target_value' => '', 'unit' => '%', 'weight' => '']]; ?>
                            <?php endif; ?>
                            <?php foreach ($item_rows as $index => $row): ?>
                                <tr>
                                    <td style="padding: 8px;">
                                        <input type="number" name="items[<?php echo $index; ?>][order_no]" value="<?php echo htmlspecialchars($row['order_no']); ?>" style="width: 70px; height: 32px; padding: 4px 6px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">
                                    </td>
                                    <td style="padding: 8px;">
                                        <input type="text" name="items[<?php echo $index; ?>][objective]" value="<?php echo htmlspecialchars($row['objective']); ?>" style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px; width: 100%;">
                                        <?php if (!empty($item_errors[$index]['objective'])): ?>
                                            <div style="color: #ff4d4f; font-size: 12px; margin-top: 4px;"><?php echo $item_errors[$index]['objective']; ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 8px;">
                                        <input type="text" name="items[<?php echo $index; ?>][kpi]" value="<?php echo htmlspecialchars($row['kpi']); ?>" style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px; width: 100%;">
                                        <?php if (!empty($item_errors[$index]['kpi'])): ?>
                                            <div style="color: #ff4d4f; font-size: 12px; margin-top: 4px;"><?php echo $item_errors[$index]['kpi']; ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 8px;">
                                        <input type="number" step="0.01" name="items[<?php echo $index; ?>][target_value]" value="<?php echo htmlspecialchars($row['target_value']); ?>" style="width: 100px; height: 32px; padding: 4px 6px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">
                                        <?php if (!empty($item_errors[$index]['target_value'])): ?>
                                            <div style="color: #ff4d4f; font-size: 12px; margin-top: 4px;"><?php echo $item_errors[$index]['target_value']; ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 8px;">
                                        <input type="text" name="items[<?php echo $index; ?>][unit]" value="<?php echo htmlspecialchars($row['unit']); ?>" style="width: 80px; height: 32px; padding: 4px 6px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">
                                        <?php if (!empty($item_errors[$index]['unit'])): ?>
                                            <div style="color: #ff4d4f; font-size: 12px; margin-top: 4px;"><?php echo $item_errors[$index]['unit']; ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 8px;">
                                        <input type="number" step="0.01" name="items[<?php echo $index; ?>][weight]" value="<?php echo htmlspecialchars($row['weight']); ?>" style="width: 90px; height: 32px; padding: 4px 6px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">
                                        <?php if (!empty($item_errors[$index]['weight'])): ?>
                                            <div style="color: #ff4d4f; font-size: 12px; margin-top: 4px;"><?php echo $item_errors[$index]['weight']; ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 8px;">
                                        <button type="button" class="btn btn-link remove-item" style="color: #ff4d4f; padding: 0; font-size: 14px;">Remove</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 12px;">
                    <button type="button" class="btn btn-outline-secondary" id="add-item" style="color: rgba(0,0,0,0.65); border-color: #d9d9d9; background: #fff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px;">
                        <i class="bi bi-plus-circle"></i> Add Item
                    </button>
                    <div style="font-size: 14px; color: rgba(0,0,0,0.65);">
                        Total weight: <strong id="total-weight"><?php echo number_format($total_weight, 2); ?>%</strong>
                    </div>
                </div>
            </div>

            <div style="margin-top: 24px;">
                <button type="submit" class="btn btn-primary" style="background-color: #1890ff; border-color: #1890ff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px; margin-right: 8px;">
                    <i class="bi bi-check-circle"></i> Save
                </button>
                <a href="<?php echo site_url('admin/performance-appraisal'); ?>" class="btn btn-outline-secondary" style="color: rgba(0,0,0,0.65); border-color: #d9d9d9; background: #fff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center;">
                    <i class="bi bi-arrow-left"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        var tableBody = document.getElementById('item-rows');
        var addButton = document.getElementById('add-item');
        var totalWeightEl = document.getElementById('total-weight');

        if (!tableBody || !addButton) {
            return;
        }

        function recalcTotalWeight() {
            var total = 0;
            tableBody.querySelectorAll('input[name$="[weight]"]').forEach(function (input) {
                var value = parseFloat(input.value);
                if (!Number.isNaN(value)) {
                    total += value;
                }
            });
            if (totalWeightEl) {
                totalWeightEl.textContent = total.toFixed(2) + '%';
            }
        }

        function reindexRows() {
            var rows = tableBody.querySelectorAll('tr');
            rows.forEach(function (row, index) {
                row.querySelectorAll('input').forEach(function (input) {
                    input.name = input.name.replace(/items\[\d+\]/, 'items[' + index + ']');
                });
            });
        }

        addButton.addEventListener('click', function () {
            var index = tableBody.querySelectorAll('tr').length;
            var row = document.createElement('tr');
            row.innerHTML =
                '<td style="padding: 8px;">' +
                    '<input type="number" name="items[' + index + '][order_no]" value="' + (index + 1) + '" style="width: 70px; height: 32px; padding: 4px 6px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">' +
                '</td>' +
                '<td style="padding: 8px;">' +
                    '<input type="text" name="items[' + index + '][objective]" value="" style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px; width: 100%;">' +
                '</td>' +
                '<td style="padding: 8px;">' +
                    '<input type="text" name="items[' + index + '][kpi]" value="" style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px; width: 100%;">' +
                '</td>' +
                '<td style="padding: 8px;">' +
                    '<input type="number" step="0.01" name="items[' + index + '][target_value]" value="" style="width: 100px; height: 32px; padding: 4px 6px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">' +
                '</td>' +
                '<td style="padding: 8px;">' +
                    '<input type="text" name="items[' + index + '][unit]" value="%" style="width: 80px; height: 32px; padding: 4px 6px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">' +
                '</td>' +
                '<td style="padding: 8px;">' +
                    '<input type="number" step="0.01" name="items[' + index + '][weight]" value="" style="width: 90px; height: 32px; padding: 4px 6px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">' +
                '</td>' +
                '<td style="padding: 8px;">' +
                    '<button type="button" class="btn btn-link remove-item" style="color: #ff4d4f; padding: 0; font-size: 14px;">Remove</button>' +
                '</td>';
            tableBody.appendChild(row);
        });

        tableBody.addEventListener('click', function (event) {
            if (event.target.classList.contains('remove-item')) {
                var row = event.target.closest('tr');
                if (row) {
                    row.remove();
                    reindexRows();
                    recalcTotalWeight();
                }
            }
        });

        tableBody.addEventListener('input', function (event) {
            if (event.target.name && event.target.name.indexOf('[weight]') !== -1) {
                recalcTotalWeight();
            }
        });
    })();
</script>
