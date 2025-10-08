<?php
$k = $start;
if (empty($data)) {
    echo '<tr><td colspan="8" class="text-center">No main quests found.</td></tr>';
} else {
    foreach ($data as $v) {
?>
        <tr>
            <td class="text-center">
                <input type="checkbox" class="form-check-input quest-checkbox" value="<?= $v['id'] ?>" data-quest-title="<?= htmlspecialchars($v['title']) ?>">
            </td>
            <td class="text-start"><?= $k + 1 ?></td>
            <td class="text-start">
                <strong><?= $v['title'] ?></strong>
            </td>
            <td class="text-start">
                <span style="color: rgba(0,0,0,0.65); font-size: 13px;">
                    <?= strlen($v['description']) > 100 ? substr($v['description'], 0, 100) . '...' : $v['description'] ?>
                </span>
            </td>
            <td class="text-center">
                <strong><?= $v['position_name'] ?></strong>
                <span class="badge" style="background-color: #e6f7ff; color: #1890ff; border: 1px solid #91d5ff;">
                    <?= $v['level_name'] ?>
                </span>
            </td>
            <td class="text-start">
                <span style="color: rgba(0,0,0,0.65); font-size: 13px;"><?= $v['creator_name'] ?></span>
            </td>
            <td class="text-start">
                <span style="color: rgba(0,0,0,0.65); font-size: 13px;">
                    <?= date('d M Y', strtotime($v['created_at'])) ?>
                </span>
            </td>
            <td class="text-end">
                <!-- Always show view/detail button -->
                <a href="<?= base_url() ?>quest/main_quest_detail?id=<?= $v['id'] ?>"
                    class="me-2" style="color: #1890ff; font-size: 14px; text-decoration: none;" title="Detail">
                    <i class="bi bi-eye"></i>
                </a>
                
                <!-- Show edit button only if user has edit permission -->
                <?php if (isset($can_edit) && $can_edit): ?>
                    <a href="<?= base_url() ?>quest/main_quest_edit_page?id=<?= $v['id'] ?>"
                        class="me-2" style="color: #1890ff; font-size: 14px; text-decoration: none;" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </a>
                <?php endif; ?>
                
                <!-- Show delete button only if user has delete permission -->
                <?php if (isset($can_delete) && $can_delete): ?>
                    <a href="#!" onclick="removeMainQuest('<?= $v['id'] ?>')"
                        style="color: #ff4d4f; font-size: 14px;" title="Hapus">
                        <i class="bi bi-trash"></i>
                    </a>
                <?php endif; ?>
            </td>
        </tr>
<?php
        $k += 1;
    } // end foreach
} // end if 
?>