<?php
$k = $start;
foreach ($data as $v) {
?>
        <tr>
            <td class="text-start">
                <?= $k + 1 ?>
            </td>
            <td class="text-start">
                <?= $v['full_name'] ?>
            </td>
            <td class="text-start">
                <?= $v['username'] ?>
            </td>
            <td class="text-start">
                <?= $v['email'] ?>
            </td>
            <td class="text-start">
                <span class="badge" style="background-color: #e6f7ff; color: #1890ff; border: 1px solid #91d5ff;">
                    <?= $v['role_text'] ?>
                </span>
            </td>
            <td class="text-start">
                <span style="color: rgba(0,0,0,0.45); font-size: 13px;"><?= $v['desc'] ?></span>
            </td>
            <td class="text-start">
                <?php if ($v['status'] == 'Active'): ?>
                    <span class="badge" style="background-color: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f;">
                        <?= $v['status'] ?>
                    </span>
                <?php else: ?>
                    <span class="badge" style="background-color: #fff2f0; color: #ff4d4f; border: 1px solid #ffccc7;">
                        <?= $v['status'] ?>
                    </span>
                <?php endif; ?>
            </td>
            <td class="text-start">
                <?php if (!empty($v['jenis_kontrak'])): ?>
                    <?php if ($v['jenis_kontrak'] == 'PKWT'): ?>
                        <span class="badge" style="background-color: #fff7e6; color: #fa8c16; border: 1px solid #ffd591;">
                            PKWT
                            <?php if (!empty($v['lama_kontrak'])): ?>
                                <br><small><?= $v['lama_kontrak'] ?></small>
                            <?php endif; ?>
                        </span>
                    <?php else: ?>
                        <span class="badge" style="background-color: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f;">
                            PKWTT
                        </span>
                    <?php endif; ?>
                <?php else: ?>
                    <span style="color: rgba(0,0,0,0.45); font-size: 13px;">-</span>
                <?php endif; ?>
            </td>
            <td class="text-end">
                <a href="<?= base_url() ?>/user/detail?id=<?= $v['id'] ?>" class="me-2" style="color: #1890ff; font-size: 14px;text-decoration:none;" title="Detail">
                    <i class="bi bi-eye"></i>
                </a>
                <a href="<?= base_url() ?>/user/edit_page?id=<?= $v['id'] ?>" class="me-2" style="color: #1890ff; font-size: 14px;text-decoration:none;" title="Edit">
                    <i class="bi bi-pencil"></i>
                </a>
                <a href="#!" onclick="remove('<?= $v['id'] ?>')" style="color: #ff4d4f; font-size: 14px;" title="Hapus">
                    <i class="bi bi-trash"></i>
                </a>
            </td>
        </tr>
<?php $k += 1;
} ?>