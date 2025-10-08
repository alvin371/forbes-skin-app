<?php
$k = $start;



$day = array(
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu',
    'Sunday' => 'Minggu'
);

$month = array(
    '',
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember'
);

foreach ($data as $v) {
    $v['date'] = $v['created_at'];
    if ($v['date']) {
        $v['date'] = $day[(DATE('l', strtotime($v['date'])))] . ', ' . DATE('d', strtotime($v['date'])) . ' ' . substr($month[intval(DATE('m', strtotime($v['date'])))], 0, 3) . ' ' . DATE('Y', strtotime($v['date'])) . ' ' . DATE('H:i:s', strtotime($v['date']));
    } else {
        $v['date'] = '-';
    }

    $id_parent = $v['id'];
    $marketplace = $v['marketplace'];
    $item = $this->mymodel->selectWithQuery("SELECT * FROM product_variant_3rd WHERE id_parent = '$id_parent' AND marketplace = '$marketplace'
    ORDER BY name ASC");

    if ($v['img']) {
        $v['img'] = base_url() . 'assets/img/product_3rd/' . $v['img'];
    } else {
        $v['img'] = base_url() . 'assets/img/product_3rd/default.png';
    }
?>
    <tbody>
        <tr>
            <td class="text-start">
                <p class="mb-1 text-blue fw-700 fs-16">#<?= $k + 1 ?></p>
            </td>
            <td class="text-start td-breakline">
                <div class="row">
                    <div class="col-12" style="position:relative">
                        <div class="row">
                            <div class="firstDivImg">
                                <a href="<?= $v['img'] ?>" target="_blank"><img class="divIcon" src="<?= $v['img'] ?>" alt=""></a>
                            </div>
                            <div class="secondDivImg">

                                <p class="mb-1 text-black fw-700 fs-16"> <?= $v['name'] ?></p>
                                <p class="mb-1">Dari <span class="fw-700"><?= $v['marketplace'] ?></span> - <?= $v['shop_name'] ?> (<?= $v['date'] ?>)</p>
                                <p class="mb-1">ID : <?= $v['id_product'] ?></p>
                                <p class="mb-1">SKU : <?= $v['sku'] ?></p>
                                <p class="mb-1">Brand : <?= $v['brand'] ?></p>
                                <p class="mb-1">Jumlah Varian : <?= $v['count_varian'] ?></p>
                                <div>
                                    <a href="#!" onclick="edit('<?= $v['id'] ?>')" class="mt-0 text-blue me-1"><i class="bi bi-pen text-icon"></i></a>
                                    <a href="#!" onclick="remove('<?= $v['id'] ?>')" class="mt-0 text-red"><i class="bi bi-trash text-icon"></i></a>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </td>
            <td class="text-start td-breakline">
                <?php foreach ($item as $k2 => $v2) {
                    $v['konfigurasi'] = '';
                    $json = json_decode($v2['json'], true);
                    foreach ($json as $k3 => $v3) {
                        $v['konfigurasi'] .= '<p class="mb-1">- ' . $v3['qty'] . ' ' . $v3['unit'] . ' | ' . $v3['product_text'] . '</p>';
                    }
                    if ($v['konfigurasi'] == "") {
                        $v['konfigurasi'] = '<p class="mb-1 text-red"><i>Konfigurasi belum tersedia!</i></p>';
                    }
                    if (empty($v2['brand'])) {
                        $v2['brand'] = '-';
                    }
                ?>
                    <p class="mb-1 fw-700 fs-16"><?= $v2['name'] ?></p>
                    <p class="mb-1">ID : <?= $v2['id_product'] ?></p>
                    <p class="mb-1">SKU : <?= $v2['sku'] ?></p>
                    <p class="mb-1">Brand : <?= $v2['brand'] ?></p>
                    <?= $v['konfigurasi'] ?>
                    <div>
                        <a href="#!" onclick="remove_item('<?= $v2['id'] ?>')" class="mt-0 text-red"><i class="bi bi-trash text-icon"></i></a>
                    </div>
                    <?php if (($k2 + 1) < count($item)) { ?>
                        ------------------------------------------
                        <br>
                    <?php } ?>
                <?php } ?>
            </td>
        </tr>
    </tbody>
<?php $k += 1;
} ?>