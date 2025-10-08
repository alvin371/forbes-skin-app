<?php
if (empty($data)) {
    echo '<tr><td colspan="5" class="text-center text-muted">Tidak ada data</td></tr>';
} else {
    foreach ($data as $key => $value) {
        $num = $start + $key + 1;

        // Level badge color
        $level_color = '';
        switch ($value['level_name']) {
            case 'Junior':
                $level_color = '#52c41a';
                break;
            case 'Intermediate':
                $level_color = '#faad14';
                break;
            case 'Senior':
                $level_color = '#1890ff';
                break;
            default:
                $level_color = '#d9d9d9';
        }
?>
        <tr>
            <td class="text-center">
                <input type="checkbox" class="form-check-input position-checkbox" value="<?= $value['id'] ?>" data-position-name="<?= htmlspecialchars($value['name']) ?>">
            </td>
            <td><?= $num ?></td>
            <td>
                <strong><?= $value['name'] ?></strong>
            </td>
            <td class="text-start">
                <span class="badge" style="background-color: <?= $level_color ?>; color: 'white'; border: 1px solid <?= $level_color ?>;">
                    <?= $value['level_name'] ?> Level
                </span>
            </td>
            <td class="text-end">
                <!-- Always show view/detail button -->
                <a href="<?= base_url() ?>/position/detail?id=<?= $value['id'] ?>"
                    class="me-2" style="color: #1890ff; font-size: 14px; text-decoration: none;" title="Detail">
                    <i class="bi bi-eye"></i>
                </a>
                
                <!-- Show edit button only if user has edit permission -->
                <?php if (isset($can_edit) && $can_edit): ?>
                    <a href="<?= base_url() ?>/position/edit_page?id=<?= $value['id'] ?>"
                        class="me-2" style="color: #1890ff; font-size: 14px; text-decoration: none;" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </a>
                <?php endif; ?>
                
                <!-- Show delete button only if user has delete permission -->
                <?php if (isset($can_delete) && $can_delete): ?>
                    <a href="#!" onclick="removePosition('<?= $value['id'] ?>')"
                        style="color: #ff4d4f; font-size: 14px;" title="Hapus">
                        <i class="bi bi-trash"></i>
                    </a>
                <?php endif; ?>
            </td>
        </tr>
<?php
    }
}
?>