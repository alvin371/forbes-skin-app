<tbody>
    <?php
    $k = $start;
    foreach ($data as $row) {
        $item = $row['data'];
        $current_balance = $row['balance'];
        $class = "text-red";
        if ($item['type'] == "In") {
            $class = "text-green";
        }
    ?>
        <tr>
            <td class="text-start">
                <p class="mb-1"><?= $k ?></p>
            </td>
            <td class="text-start"><?= $template->date_format_indo_with_time($item['date']) ?></td>
            <td class="text-start"><?= $item['type'] ?><br><?= $item['type_sub'] ?></td>
            <td class="text-start">
                <a target="_blank" href="<?= base_url() ?>/transaction?keyword_category=Order ID&keyword=<?= $item['order_id'] ?>&start_date=<?= date("Y-m-d", strtotime($item['date'])) ?>&until_date=<?= date("Y-m-d", strtotime($item['date'])) ?>">
                    <?= $item['order_id'] ?>
                </a>
            </td>
            <td class="text-start"><?= $item['awb_number'] ?></td>
            <td class="text-end <?= $class ?>"><?= $template->separator_only($item['qty']) ?></td>
            <td class="text-end"><?= $template->separator_only($current_balance) ?></td>
            <td class="text-start"><?= $item['desc'] ?></td>
        </tr>
    <?php 
    $k += 1;
    } ?>
</tbody>

<tfoot>
    <tr class="fw-bold pt-3">
        <td class="text-start">
            <p class="mb-1 text-blue fw-700 fs-16">#</p>
        </td>
        <td class="text-start" colspan="4">Stok Sebelumnya</td>
        <td class="text-end"><?= number_format($initial_balance, 0, ',', '.') ?></td>
        <td class="text-start" colspan="3"></td>
    </tr>
</tfoot>