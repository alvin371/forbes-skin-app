<?php
if (empty($data)) {
    echo '<tr><td colspan="9" class="text-center text-muted">Tidak ada data</td></tr>';
} else {
    foreach ($data as $key => $value) {
        $num = $start + $key + 1;
        
        // Question type badge
        $type_colors = [
            'text' => 'badge-info',
            'score' => 'badge-warning',
            'multiple_choice' => 'badge-success'
        ];
        
        $type_color = $type_colors[$value['question_type']] ?? 'badge-secondary';
        
        // Status badge
        $status_color = $value['is_active'] ? 'badge-success' : 'badge-danger';
        $status_label = $value['is_active'] ? 'Active' : 'Inactive';
        
        // Truncate question text if too long
        $question_text = strlen($value['question_text']) > 100 ? 
            substr($value['question_text'], 0, 100) . '...' : 
            $value['question_text'];
?>
        <tr>
            <td><?= $num ?></td>
            <td><span class="badge badge-primary"><?= htmlspecialchars($value['position']) ?></span></td>
            <td><?= htmlspecialchars($value['category_name']) ?></td>
            <td title="<?= htmlspecialchars($value['question_text']) ?>">
                <?= htmlspecialchars($question_text) ?>
            </td>
            <td>
                <span class="badge <?= $type_color ?>">
                    <?= ucfirst(str_replace('_', ' ', $value['question_type'])) ?>
                </span>
            </td>
            <td><?= $value['score_range'] ? htmlspecialchars($value['score_range']) : '-' ?></td>
            <td><?= $value['sort_order'] ?></td>
            <td>
                <span class="badge <?= $status_color ?>">
                    <?= $status_label ?>
                </span>
            </td>
            <td class="text-end">
                <div class="btn-group" role="group">
                    <a href="<?= base_url() ?>interview/edit_question/<?= $value['id'] ?>" 
                       class="btn btn-sm btn-outline-primary" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <button class="btn btn-sm btn-outline-danger" 
                            onclick="deleteQuestion(<?= $value['id'] ?>)" title="Delete">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
<?php
    }
}
?>

<style>
    .badge-primary {
        background-color: #1890ff;
        color: white;
    }
    .badge-success {
        background-color: #52c41a;
        color: white;
    }
    .badge-warning {
        background-color: #faad14;
        color: white;
    }
    .badge-danger {
        background-color: #f5222d;
        color: white;
    }
    .badge-info {
        background-color: #13c2c2;
        color: white;
    }
    .badge-secondary {
        background-color: #d9d9d9;
        color: rgba(0, 0, 0, 0.65);
    }
    
    .btn-outline-primary {
        color: #1890ff;
        border-color: #1890ff;
    }
    
    .btn-outline-primary:hover {
        background-color: #1890ff;
        border-color: #1890ff;
        color: white;
    }
    
    .btn-outline-danger {
        color: #f5222d;
        border-color: #f5222d;
    }
    
    .btn-outline-danger:hover {
        background-color: #f5222d;
        border-color: #f5222d;
        color: white;
    }
</style>