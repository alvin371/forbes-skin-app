<?php
$k = $start;
foreach ($data as $v) {
?>
<tbody>
    <tr>
    <td class="text-start">
    <p class="mb-1 text-blue fw-700 fs-16">#<?=$k+1?></p>
    </td>
     <td class="text-start td-breakline"><?= $v['name'] ?></td>
     <td class="text-start"><?= $template->date_format_indo($v['date_start']) ?></td>
     <td class="text-start"><?= $template->date_format_indo($v['date_end']) ?></td>
     <td class="text-center"><?= $v['customer'] ?></td>
     <td class="text-start"><?= $v['pic'] ?></td>
     <td class="text-start td-breakline"><?= $v['desc'] ?></td>
        
    <td class="text-end">
        <a href="#!" onclick="edit('<?=$v['id']?>')" class="mt-0 text-blue me-1"><i class="bi bi-pen text-icon"></i></a>
        <a href="#!" onclick="remove('<?=$v['id']?>')" class="mt-0 text-red"><i class="bi bi-trash text-icon"></i></a>
    </td>
    </tr>
</tbody>
<?php $k += 1;} ?>