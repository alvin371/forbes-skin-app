<?php
if (empty($data)) {
    echo '<tr><td colspan="7" class="text-center text-muted">No roles found</td></tr>';
} else {
    foreach ($data as $key => $value) {
        $num = $start + $key + 1;

?>
        <tr>
            <td><?= $num ?></td>
            <td>
                <strong><?= htmlspecialchars($value['name']) ?></strong>
            </td>
            <td>
                <span style="color: rgba(0,0,0,0.85);"><?= htmlspecialchars($value['display_name']) ?></span>
            </td>
            <td class="text-center">
                <span class="badge bg-info">
                    <i class="bi bi-people me-1"></i>
                    <?= $value['user_count'] ?> users
                </span>
            </td>
            <td class="text-center">
                <span class="badge bg-secondary">
                    <i class="bi bi-shield-check me-1"></i>
                    <?= $value['permission_count'] ?> modules
                </span>
            </td>
            <td class="text-start">
                <?php if ($value['is_active']): ?>
                    <span class="badge bg-success">
                        <i class="bi bi-check-circle me-1"></i>Active
                    </span>
                <?php else: ?>
                    <span class="badge bg-secondary">
                        <i class="bi bi-x-circle me-1"></i>Inactive
                    </span>
                <?php endif; ?>
            </td>
            <td class="text-end">
                <!-- Always show view/detail button -->
                <a href="<?= base_url() ?>/roles/detail?id=<?= $value['id'] ?>"
                    class="me-2" style="color: #1890ff; font-size: 14px; text-decoration: none;" title="Detail">
                    <i class="bi bi-eye"></i>
                </a>
                
                <!-- Show edit button only if user has edit permission -->
                <?php if (isset($can_edit) && $can_edit): ?>
                    <a href="<?= base_url() ?>/roles/edit_page?id=<?= $value['id'] ?>"
                        class="me-2" style="color: #1890ff; font-size: 14px; text-decoration: none;" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </a>
                <?php endif; ?>
                
                <!-- Show delete button only if user has delete permission and it's not a system role -->
                <?php if (isset($can_delete) && $can_delete && !in_array($value['name'], ['super_admin', 'admin', 'employee'])): ?>
                    <a href="#!" onclick="removeRole('<?= $value['id'] ?>')"
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