<?php
$k = $start;
foreach ($data as $v) {
?>
    <tbody>
        <tr>
            <td class="text-start">
                <p class="text-blue fs-16 fw-700">#<?= $k + 1 ?></p>
            </td>
            <td class="text-start">
                <?= $template->date_format_indo($v['date']) ?>
            </td>
            <td class="req text-start td-breakline">
                <?= nl2br($v['desc']) ?>
            </td>
            <?php if ($brand == "POME") { ?>
                <td class="text-start">
                    <?php if ($v['img_before']) {
                        $v['img_before'] = base_url() . '/assets/img/testimoni/' . $v['img_before'];
                    ?>

                        <p><a href="<?= $v['img_before'] ?>" target="_blank"><i><img src="<?= $v['img_before'] ?>" style="width:150px;height:150px;object-fit:cover;border-radius:10px" alt=""></i></a></p>
                    <?php } else { ?>
                        <p><i>Gambar belum tersedia!</i></p>
                    <?php } ?>
                </td>
                <td class="text-start">
                    <?php if ($v['img_after']) {
                        $v['img_after'] = base_url() . '/assets/img/testimoni/' . $v['img_after'];
                    ?>
                        <p><a href="<?= $v['img_after'] ?>" target="_blank"><i><img src="<?= $v['img_after'] ?>" style="width:150px;height:150px;object-fit:cover;border-radius:10px" alt=""></i></a></p>
                    <?php } else { ?>
                        <p><i>Gambar belum tersedia!</i></p>
                    <?php } ?>
                </td>
            <?php } else { ?>
                <td class="text-start">
                    <?php if ($v['img_before']) {
                        $v['img_before'] = base_url() . '/assets/img/testimoni/' . $v['img_before'];
                    ?>

                        <p><a href="<?= $v['img_before'] ?>" target="_blank"><i><img src="<?= $v['img_before'] ?>" style="width:150px;height:150px;object-fit:cover;border-radius:10px" alt=""></i></a></p>
                    <?php } else { ?>
                        <p><i>Gambar belum tersedia!</i></p>
                    <?php } ?>
                </td>
            <?php } ?>


            <td class="text-end td-action">
                <a href="#!" onclick="edit_2('<?= $v['id'] ?>')" class="mt-0 text-blue me-1"><i class="bi bi-pen text-icon"></i></a>
                <a href="#!" onclick="remove_2('<?= $v['id'] ?>')" class="mt-0 text-red"><i class="bi bi-trash text-icon"></i></a>
            </td>

        </tr>

        </tr>
    </tbody>
<?php $k += 1;
} ?>