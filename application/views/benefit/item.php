<?php
if (empty($data)) {
    echo '<tr><td colspan="4" class="text-center text-muted">Tidak ada data</td></tr>';
} else {
    foreach ($data as $key => $value) {
        $num = $start + $key + 1;
        
        // Benefit type badge color
        $type_color = '';
        switch(strtolower($value['type'])) {
            case 'promotion':
                $type_color = 'bg-primary';
                break;
            case 'bonus':
                $type_color = 'bg-success';
                break;
            case 'salary':
                $type_color = 'bg-warning';
                break;
            case 'leave':
                $type_color = 'bg-info';
                break;
            case 'wfa':
                $type_color = 'bg-dark';
                break;
            default:
                $type_color = 'bg-secondary';
        }
?>
        <tr>
            <td><?= $num ?></td>
            <td>
                <span class="badge <?= $type_color ?>"><?= ucfirst($value['type']) ?></span>
            </td>
            <td><?= $value['description'] ?></td>
            <td class="text-end">
                <a href="<?= base_url() ?>/benefit/detail?id=<?= $value['id'] ?>"
                    class="me-2" style="color: #1890ff; font-size: 14px; text-decoration: none;" title="Detail">
                    <i class="bi bi-eye"></i>
                </a>
                <a href="<?= base_url() ?>/benefit/edit_page?id=<?= $value['id'] ?>"
                    class="me-2" style="color: #1890ff; font-size: 14px; text-decoration: none;" title="Edit">
                    <i class="bi bi-pencil"></i>
                </a>
                <a href="#!" onclick="removeBenefit('<?= $value['id'] ?>')"
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
        $.get("<?= base_url() ?>/benefit/remove?id=" + id, function(data) {
            $("#popupModal .modal-content").html(data);
            $("#popupModal").modal('show');
        });
    }
</script>