<?php
$k = $start;
if (empty($data)) {
    echo '<tr><td colspan="7" class="text-center">No milestone quests found.</td></tr>';
} else {
    foreach ($data as $v) {
        // Determine type display
        $type_display = '';
        $type_color = '';
        switch($v['milestone_type']) {
            case 'quest_count':
                $type_display = 'Quest Count';
                $type_color = 'background-color: #e6f7ff; color: #1890ff; border: 1px solid #91d5ff;';
                break;
            case 'total_points':
                $type_display = 'Total Points';
                $type_color = 'background-color: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f;';
                break;
            case 'monthly_points':
                $type_display = 'Monthly Points';
                $type_color = 'background-color: #fff7e6; color: #fa8c16; border: 1px solid #ffd591;';
                break;
        }
        
        // Status display
        $status_display = $v['is_active'] == 1 ? 'Active' : 'Inactive';
        $status_color = $v['is_active'] == 1 ? 'background-color: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f;' : 'background-color: #fff1f0; color: #ff4d4f; border: 1px solid #ffccc7;';
?>
    <tr>
        <td class="text-start"><?= $k + 1 ?></td>
        <td class="text-start">
            <div class="d-flex align-items-center">
                <?php if (!empty($v['gambar_animasi'])): ?>
                    <i class="bi bi-image text-success me-2" title="Has animation image"></i>
                <?php endif; ?>
                <strong><?= $v['title'] ?></strong>
            </div>
        </td>
        <td class="text-center">
            <span class="badge" style="<?= $type_color ?> font-size: 13px;">
                <?= $type_display ?>
            </span>
        </td>
        <td class="text-center">
            <span class="fw-bold" style="color: #1890ff;">
                <?= number_format($v['target_value']) ?>
                <?php if ($v['milestone_type'] == 'quest_count'): ?>
                    <small class="text-muted">quests</small>
                <?php else: ?>
                    <small class="text-muted">points</small>
                <?php endif; ?>
            </span>
        </td>
        <td class="text-start">
            <span class="badge" style="<?= $status_color ?> font-size: 13px;">
                <?= $status_display ?>
            </span>
        </td>
        <td class="text-start">
            <span style="color: rgba(0,0,0,0.65); font-size: 13px;">
                <?= date('d M Y', strtotime($v['created_at'])) ?>
            </span>
        </td>
        <td class="text-end">
            <a href="<?= base_url() ?>milestone/milestone_detail?id=<?= $v['id'] ?>" 
               class="me-2" style="color: #1890ff; font-size: 14px; text-decoration: none;" title="Detail">
                <i class="bi bi-eye"></i>
            </a>
            <a href="<?= base_url() ?>milestone/milestone_edit_page?id=<?= $v['id'] ?>" 
               class="me-2" style="color: #1890ff; font-size: 14px; text-decoration: none;" title="Edit">
                <i class="bi bi-pencil"></i>
            </a>
            <a href="#!" onclick="removeMilestone('<?= $v['id'] ?>')" 
               style="color: #ff4d4f; font-size: 14px;" title="Hapus">
                <i class="bi bi-trash"></i>
            </a>
        </td>
    </tr>
<?php 
    $k += 1;
    } // end foreach
} // end if ?>