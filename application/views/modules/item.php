<?php
if (empty($data)) {
    echo '<tr><td colspan="10" class="text-center text-muted">No modules found</td></tr>';
} else {
    foreach ($data as $key => $value) {
        $num = $start + $key + 1;
?>
        <tr>
            <td>
                <?php if (isset($can_delete) && $can_delete && $value['children_count'] == 0): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="module_ids[]" 
                               value="<?= $value['id'] ?>" onchange="updateBulkActions()">
                    </div>
                <?php endif; ?>
            </td>
            <td><?= $num ?></td>
            <td>
                <div class="d-flex align-items-center">
                    <?php if ($value['icon']): ?>
                        <i class="<?= htmlspecialchars($value['icon']) ?> me-2 text-primary"></i>
                    <?php else: ?>
                        <i class="bi bi-circle me-2 text-muted"></i>
                    <?php endif; ?>
                    <div>
                        <strong style="color: rgba(0,0,0,0.85);"><?= htmlspecialchars($value['display_name']) ?></strong><br>
                        <small class="text-muted"><?= htmlspecialchars($value['name']) ?></small>
                    </div>
                </div>
            </td>
            <td>
                <?php if ($value['controller']): ?>
                    <code style="background-color: #f6f8fa; padding: 2px 6px; border-radius: 3px; font-size: 12px;">
                        <?= htmlspecialchars($value['controller']) ?>
                    </code>
                <?php else: ?>
                    <span class="text-muted">-</span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($value['parent_name']): ?>
                    <span class="badge bg-light text-dark border">
                        <?= htmlspecialchars($value['parent_name']) ?>
                    </span>
                <?php else: ?>
                    <span class="badge bg-primary">Root</span>
                <?php endif; ?>
            </td>
            <td class="text-start">
                <span class="badge bg-secondary">
                    <?= $value['sort_order'] ?>
                </span>
            </td>
            <td class="text-center">
                <?php if ($value['children_count'] > 0): ?>
                    <span class="badge bg-info">
                        <i class="bi bi-diagram-3 me-1"></i>
                        <?= $value['children_count'] ?> modules
                    </span>
                <?php else: ?>
                    <span class="text-muted">-</span>
                <?php endif; ?>
            </td>
            <td class="text-center">
                <span class="badge bg-secondary">
                    <i class="bi bi-shield-check me-1"></i>
                    <?= $value['permission_count'] ?> roles
                </span>
            </td>
            <td class="text-start">
                <?php if ($value['is_active']): ?>
                    <span class="badge status-active">
                        <i class="bi bi-check-circle me-1"></i>Active
                    </span>
                <?php else: ?>
                    <span class="badge status-inactive">
                        <i class="bi bi-x-circle me-1"></i>Inactive
                    </span>
                <?php endif; ?>
            </td>
            <td class="text-end">
                <!-- Always show view/detail button -->
                <a href="<?= base_url() ?>/modules/detail?id=<?= $value['id'] ?>"
                    class="me-2" style="color: #1890ff; font-size: 14px; text-decoration: none;" title="Detail">
                    <i class="bi bi-eye"></i>
                </a>
                
                <!-- Show edit button only if user has edit permission -->
                <?php if (isset($can_edit) && $can_edit): ?>
                    <a href="<?= base_url() ?>/modules/edit_page?id=<?= $value['id'] ?>"
                        class="me-2" style="color: #1890ff; font-size: 14px; text-decoration: none;" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </a>
                <?php endif; ?>
                
                <!-- Show delete button only if user has delete permission and module has no children -->
                <?php if (isset($can_delete) && $can_delete && $value['children_count'] == 0): ?>
                    <a href="#!" onclick="removeModule('<?= $value['id'] ?>')"
                        style="color: #ff4d4f; font-size: 14px;" title="Delete">
                        <i class="bi bi-trash"></i>
                    </a>
                <?php endif; ?>
            </td>
        </tr>
<?php
    }
}
?>