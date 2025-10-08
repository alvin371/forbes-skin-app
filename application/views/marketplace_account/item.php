<?php
$k = $start;
foreach ($data as $v) {
    if ($v['img']) {
        $v['img'] = base_url() . 'assets/img/marketplace_account/' . $v['img'];
    } else {
        $v['img'] = base_url() . 'assets/img/marketplace_account/default.png';
    }
?>
    <tbody>
        <tr>
            <td class="text-start">
                <p class="mb-1 text-blue fw-700 fs-16">#<?= $k + 1 ?></p>
            </td>
            <td class="req text-start">
                <div class="row">
                    <div class="col-12" style="position:relative">
                        <div class="row">
                            <div class="firstDivImg">
                                <a href="<?= $v['img'] ?>" target="_blank"><img class="divIcon" src="<?= $v['img'] ?>" alt=""></a>
                            </div>
                            <div class="secondDivImg">
                                <p class="mb-1 fw-700 fs-16"><?= $v['shop_name'] ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </td>
            <td class="req text-start">
                <?= !empty($v['shop_code']) ? $v['shop_code'] : '-' ?>
            </td>

            <td class="req text-start"><?= strtoupper($v['opt']) ?></td>
            <td class="text-start">
                <div class="form-check form-switch d-inline-block">
                    <input class="form-check-input" type="checkbox"
                        id="status_switch_<?= $v['id'] ?>"
                        name="status_<?= $v['id'] ?>"
                        <?= ($v['status'] == 'Aktif') ? 'checked' : '' ?>
                        onchange="updateStatus('<?= $v['id'] ?>', this.checked ? 'Aktif' : 'Nonaktif')">
                </div>
            </td>
            <td class="text-end">
                <!-- <a href="#!" onclick="edit('<?= $v['id'] ?>')" class="mt-0 text-blue me-1"><i class="bi bi-pen text-icon"></i></a> -->
                <a href="#!" onclick="remove('<?= $v['id'] ?>')" class="mt-0 text-red"><i class="bi bi-trash text-icon"></i></a>
            </td>
        </tr>
    </tbody>
<?php $k += 1;
} ?>