<?php
if (empty($data)) {
    echo '<tr><td colspan="4" class="text-center text-muted">Tidak ada data</td></tr>';
} else {
    foreach ($data as $key => $value) {
        $num = $start + $key + 1;
?>
        <tr>
            <td><?= $num ?></td>
            <td>
                <strong><?= $value['name'] ?></strong>
            </td>
            <td class="text-center">
                <span class="badge bg-primary"><?= $value['level_order'] ?></span>
            </td>
            <td class="text-end">
                <a href="<?= base_url() ?>/quest_level/detail?id=<?= $value['id'] ?>"
                    class="me-2" style="color: #1890ff; font-size: 14px; text-decoration: none;" title="Detail">
                    <i class="bi bi-eye"></i>
                </a>
                <a href="<?= base_url() ?>/quest_level/edit_page?id=<?= $value['id'] ?>"
                    class="me-2" style="color: #1890ff; font-size: 14px; text-decoration: none;" title="Edit">
                    <i class="bi bi-pencil"></i>
                </a>
                <a href="#!" onclick="removeQuestLevel('<?= $value['id'] ?>')"
                    style="color: #ff4d4f; font-size: 14px;" title="Hapus">
                    <i class="bi bi-trash"></i>
                </a>
            </td>
        </tr>
<?php
    }
}
?>

<script type="text/javascript">
    function confirm_delete(id) {
        $.get("<?= base_url() ?>/quest_level/remove?id=" + id, function(data) {
            $("#popupModal .modal-content").html(data);
            $("#popupModal").modal('show');
        });
    }
</script>