<?php
$k = $start;
foreach ($data as $v) {
    if ($v['last_order']) {
        $datetime1 = DATE("Y-m-d", strtotime($v['last_order']));
        $datetime2 = DATE("Y-m-d");
        $timestamp1 = strtotime($datetime1);
        $timestamp2 = strtotime($datetime2);
        $interval_seconds = abs($timestamp2 - $timestamp1);
        $interval_days = floor($interval_seconds / (60 * 60 * 24));
        if ($interval_days > 0) {
            $order_status = "Transaksi terakhir $interval_days yang lalu.";
        } else {
            $order_status = "Transaksi terakhir hari ini.";
        }
    } else {
        $order_status = "Belum memiliki transaksi!";
    }
    $day = array(
        '',
        'Senin',
        'Selasa',
        'Rabu',
        'Kamis',
        'Jumat',
        'Sabtu',
        'Minggu',
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
?>
    <?php if ($_GET['brand'] == "POME") {

        if ($v['birth_date']) {
            $v['birth_date'] = DATE('d', strtotime($v['birth_date'])) . ' ' . substr($month[intval(DATE('m', strtotime($v['birth_date'])))], 0, 3) . ' ' . DATE('Y', strtotime($v['birth_date']));
        } else {
            $v['birth_date'] = '-';
        }
        $bg_2 = '#ed7881';
        if (empty($v['phone']) || strpos($v['phone'], '*') === false) {
            $bg_2 = '#60bb55';
        }
        if (substr($v['phone'], 0, 1) === "0") {
            $v['phone'] = "62" . substr($v['phone'], 1);
        }
        if ($v['desc'] == '') {
            $v['desc'] = '-';
        }
        if ($v['cs'] == '') {
            $v['cs'] = '-';
        }
        if ($v['gift'] == '') {
            $v['gift'] = '-';
        }
        if ($v['address'] == '') {
            $v['address'] = '-';
        }

        $text = '';
        foreach (json_decode($v['gift'], true) as $k2 => $v2) {
            $text .= $template->date_format_indo($v2['date']) . ' : ' . $v2['title'] . '<br>';
        }
        if (empty($text)) {
            $v['gift'] = '-';
        } else {
            $v['gift'] = $text;
        }

        $text = '';
        foreach (json_decode($v['testimoni'], true) as $k2 => $v2) {
            $text .= $template->date_format_indo($v2['date']) . ' : ' . $v2['desc'] . '<br>';
        }
        if (empty($text)) {
            $v['testimoni'] = '-';
        } else {
            $v['testimoni'] = $text;
        }
        if (empty($v['full_name'])) {
            $v['full_name'] = '-';
        }
        if ($v['cb_cl'] != "CL") {
            $v['cb_cl'] = "CB";
        }

        foreach (json_decode($v['pesanan'], true) as $kk => $vv) {
            $p = $vv['json'];
            $pesanan .= '<div style="text-align:left!important;padding-top:6px">';
            $pesanan .= '<a target="_blank" href="' . base_url() . '/transaction?keyword_category=Order ID&keyword=' . $vv['order_id'] . '&start_date=' . DATE("Y-m-d", strtotime($vv['date'])) . '&until_date=' . DATE("Y-m-d", strtotime($vv['date'])) . '">' . $vv['order_id'] . '</a>';
            $pesanan .= '<p class="mb-1 mt-1">Tanggal : ' . DATE("d/m/Y", strtotime($vv['date'])) . '</p>';
            $pesanan .= '<p class="mb-1 mt-1">Status : ' . $vv['order_status'] . '</p>';
            foreach ($vv['data'] as $kkk => $vvv) {
                $pesanan .= '<p class="mb-1">' . $vvv['qty'] . ' x ' . $vvv['item_name'] . '</p>';
            }
            $pesanan .= '<br>';
            $pesanan .= '</div>';
        }
        if (empty(json_decode($v['pesanan'], true))) {
            $pesanan .= '<div style="text-align:left!important;padding-top:6px"><p class="text-danger">Belum ada!</p></div>';
        }

        if ($v['first_order']) {
            $v['first_order'] = DATE("Y-m-d H:i", strtotime($v['first_order']));
        }

        if ($v['last_order']) {
            $v['last_order'] = DATE("Y-m-d H:i", strtotime($v['last_order']));
        } else {
            $v['last_order'] = '-';
        }

    ?>
        <tbody>
            <tr>
                <td class="text-start br-b-0">
                    <p class="fw-700 fs-16 text-blue mb-1">#<?= $k + 1 ?></p>

                    <div class="checkbox-wrapper-13 d-inline">
                        <input class="checkItem" style="top:0px" type="checkbox" value="<?= $v['id'] ?>" data-id="<?= $k ?>" name="list_id" form="form-action">
                    </div>
                    <input type="hidden" value="<?= $v['is_manual'] ?>" name="is_manual[<?= $k ?>]" form="form-action">
                    <input type="hidden" value="<?= $v['marketplace'] ?>" name="marketplace[<?= $k ?>]" form="form-action">
                    <input type="hidden" value="<?= $v['brand'] ?>" name="brand[<?= $k ?>]" form="form-action">
                    <input type="hidden" value="<?= $v['order_id'] ?>" name="order_id[<?= $k ?>]" form="form-action">

                </td>
                <td class="text-start">
                    <div class="row">
                        <div class="firstDivImg">
                            <div class="divCircle" style="background:<?= $bg_2 ?>">
                                <div class="centeredElement fw-700 fs-16">
                                    <?= strtoupper($v['full_name'][0]) . '' . strtoupper($v['full_name'][1]) ?>
                                </div>
                            </div>
                        </div>
                        <div class="secondDivImg pe-5">
                            <a class="mb-1 fw-700 fs-16 a-none" href="<?= base_url() ?>crm/detail?id=<?= $v['id'] ?>&brand=<?= $v['brand'] ?>"><?= $v['full_name'] ?></a>
                            <p class="mb-1">CB/CL : <?= $v['cb_cl'] ?></p>
                            <p class="mb-1">Tipe : <?= $v['marketplace'] ?></p>
                            <p class="mb-1">Username : <?= $v['username'] ?></p>
                            <p class="mb-1">No. HP : <?= $v['phone'] ?></p>
                            <p class="mb-1">CS : <?= $v['cs'] ?></p>
                        </div>
                    </div>
                </td>
                <td class="text-start">
                    <a href="https://wa.me/<?= $v['phone'] ?>" target="_blank"><?= $v['phone'] ?></a>
                </td>
                <td class="text-start">
                    <?= $v['birth_date'] ?>
                </td>
                <td class="text-start td-breakline" style="max-width:180px">
                    <p class="mb-1 td-breakline"><?= $v['address'] ?></p>
                </td>
                <td class="text-end br-b-0">
                    <a href="#!" onclick="edit('<?= $v['id'] ?>')" class="mt-0 text-blue me-1"><i class="bi bi-pen text-icon"></i></a>
                    <a href="#!" onclick="remove('<?= $v['id'] ?>')" class="mt-0 text-red"><i class="bi bi-trash text-icon"></i></a>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="text-start br-t-0 pb-3">
                    <p class="mb-1"><b>Keterangan :</b></p>
                    <p class="mb-1"><?= $v['desc'] ?></p>
                    <p class="mb-1"><b>Gift :</b></p>
                    <p class="mb-1"><?= $v['gift'] ?></p>
                    <p class="mb-1"><b>Progres :</b></p>
                    <p class="mb-1"><?= $v['testimoni'] ?></p>
                    <p class="mb-1"><b>Created at : </b></p>
                    <p class="mb-1"><?= $v['created_at'] ?></p>
                    <p class="mb-1"><b>Order Pertama : </b></p>
                    <p class="mb-1"><?= $v['first_order'] ?></p>
                    <p class="mb-1"><b>Repeat Order : </b></p>
                    <p class="mb-1"><?= $v['last_order'] ?></p>
                    <p class="mb-1"><b>Total Order : </b></p>
                    <p class="mb-1"><?= $v['count_order'] ?></p>
                    <p class="mb-1"><b>ID : </b></p>
                    <p class="mb-1"><a href="#!" onclick="refresh('<?= $v['id'] ?>')"><?= $v['id_buyer'] ?></a></p>
                </td>
                <td colspan="4">
                    <p class="mb-1 text-start"><b>History Order :</b></p>
                    <?= $pesanan ?>
                </td>
            </tr>
        </tbody>
    <?php } else if ($_GET['brand'] == "MG") {
        $class = "";
        if ($v['pencapaian'] == "Berhasil Hamil") {
            $class = "bg-success";
        }
        $pesanan = '';
        foreach (json_decode($v['pesanan'], true) as $kk => $vv) {
            $p = $vv['json'];
            $pesanan .= '<div style="text-align:left!important;padding-top:6px">';
            $pesanan .= '<a target="_blank" href="' . base_url() . '/transaction?keyword_category=Order ID&keyword=' . $vv['order_id'] . '&start_date=' . DATE("Y-m-d", strtotime($vv['date'])) . '&until_date=' . DATE("Y-m-d", strtotime($vv['date'])) . '">' . $vv['order_id'] . '</a>';
            $pesanan .= '<p class="mb-1 mt-1">Tanggal : ' . DATE("d/m/Y", strtotime($vv['date'])) . '</p>';
            $pesanan .= '<p class="mb-1 mt-1">Status : ' . $vv['order_status'] . '</p>';
            foreach ($vv['data'] as $kkk => $vvv) {
                $pesanan .= '<p class="mb-1">' . $vvv['qty'] . ' x ' . $vvv['item_name'] . '</p>';
            }
            $pesanan .= '<br>';
            $pesanan .= '</div>';
        }
        if (empty(json_decode($v['pesanan'], true))) {
            $pesanan .= '<div style="text-align:left!important;padding-top:6px"><p class="text-danger">Belum ada!</p></div>';
        }

        $gift = '';
        foreach (json_decode($v['gift'], true) as $kk => $vv) {
            $p = $vv['json'];
            $gift .= '<div style="text-align:left!important;padding-top:2px">';
            $gift .= '<p class="mb-1 mt-0">Tanggal : ' . DATE("d/m/Y", strtotime($vv['date'])) . '</p>';
            $gift .= '<p class="mb-2">' . $vv['title'] . '</p>';
            $gift .= '</div>';
        }
        if (empty(json_decode($v['gift'], true))) {
            $gift .= '<div style="text-align:left!important;padding-top:6px"><p class="text-danger">Belum ada!</p></div>';
        }


        $testimoni = '';
        foreach (json_decode($v['testimoni'], true) as $kk => $vv) {
            $p = $vv['json'];
            $testimoni .= '<div style="text-align:left!important;padding-top:2px">';
            $testimoni .= '<p class="mb-1 mt-0">Tanggal : ' . DATE("d/m/Y", strtotime($vv['date'])) . '</p>';
            $testimoni .= '<p class="mb-2">' . $vv['desc'] . '</p>';
            $testimoni .= '</div>';
        }
        if (empty(json_decode($v['testimoni'], true))) {
            $testimoni .= '<div style="text-align:left!important;padding-top:6px"><p class="text-danger">Belum ada!</p></div>';
        }

        if ($v['first_order']) {
            $v['first_order'] = DATE("Y-m-d H:i", strtotime($v['first_order']));
        }

        if ($v['last_order']) {
            $v['last_order'] = DATE("Y-m-d H:i", strtotime($v['last_order']));
        } else {
            $v['last_order'] = '';
        }


    ?>
        <tbody>
            <style>
                .select2 {
                    margin-top: -12px !important;
                    margin-bottom: 0px !important;
                }

                .select2-container--default .select2-selection--single {
                    background-color: unset;
                    border: 1px solid #aaa;
                    border-radius: 4px;
                }

                .select2-container .select2-selection--single {
                    box-sizing: border-box;
                    cursor: pointer;
                    display: block;
                    height: unset !important;
                    user-select: none;
                    -webkit-user-select: none;
                    border: unset;
                    border-radius: unset;
                }

                td input {
                    border-bottom: 1px solid grey !important;
                }

                td select {
                    margin-top: 4px !important;
                    border-bottom: 1px solid grey !important;
                    padding-left: 0px;
                    text-align: left !important;
                }

                td .select2 {
                    margin-top: -6px !important;
                    height: 30px !important;
                    border-bottom: 1px solid grey !important;
                }

                td .select2-container--default .select2-selection--single .select2-selection__rendered {
                    line-height: 30px !important;
                }

                td .select2-container--default .select2-selection--single .select2-selection__arrow {
                    height: 30px !important;
                }

                td textarea {
                    min-width: 300px !important;
                    border-bottom: 1px solid grey !important;
                }
            </style>
            <tr class="<?= $class ?>" id="tr-<?= $k ?>">
                <form class="d-none" action="<?= base_url() ?>/crm/update-item" method="POST" id="form-modal-<?= $k ?>" enctype="multipart/form-data">
                </form>
                <td class="text-start" style="border-top-left-radius:15px">
                    <p class="fw-700 text-center fs-16 text-blue mb-1">#<?= $k + 1 ?></p>
                    <div class="col-lg-12 text-center mb-2">
                        <div class="checkbox-wrapper-13 d-inline">
                            <input class="checkItem" style="top:0px" type="checkbox" value="<?= $v['id'] ?>" data-id="<?= $k ?>" name="list_id" form="form-action">
                        </div>
                        <input type="hidden" value="<?= $v['is_manual'] ?>" name="is_manual[<?= $k ?>]" form="form-action">
                        <input type="hidden" value="<?= $v['marketplace'] ?>" name="marketplace[<?= $k ?>]" form="form-action">
                        <input type="hidden" value="<?= $v['brand'] ?>" name="brand[<?= $k ?>]" form="form-action">
                        <input type="hidden" value="<?= $v['order_id'] ?>" name="order_id[<?= $k ?>]" form="form-action">

                    </div>
                    <input form="form-modal-<?= $k ?>" type="hidden" name="id" value="<?= $v['id'] ?>">
                </td>
                <!-- <td class="req text-start" id="n-order-month-<?= $k ?>"><?= $v['order_month'] ?></td> -->
                <td class="req text-start">
                    <select form="form-modal-<?= $k ?>" type="text" class="form-table-select2 so-<?= $k ?>" name="dt[kd]">
                        <option value=""></option>
                        <?php
                        $arr = array();

                        $arr[] = 'Masuk Grup';
                        $arr[] = 'Kecantikan';
                        $arr[] = 'Haid';
                        $arr[] = 'No Respon';
                        $arr[] = 'Keluar Grup';
                        $arr[] = 'Non Promil';
                        $arr[] = 'No Tidak Bisa';
                        $arr[] = 'Promil';
                        $arr[] = '1 Box';
                        $arr[] = 'Kesehatan';
                        $arr[] = 'Paket COD';
                        $arr[] = 'Busui';
                        $arr[] = 'Hamil';
                        $arr[] = 'Retur';
                        foreach ($arr as $k2 => $v2) {
                            $text = '';
                            if ($v['kd'] == $v2) {
                                $text = 'selected';
                            }
                        ?>
                            <option <?= $text ?> value="<?= $v2 ?>"><?= $v2 ?></option>
                        <?php } ?>
                    </select>
                    <p class="mt-3 mb-0"><b>Created at : </b></p>
                    <p class="mb-1"><?= $v['created_at'] ?></p>
                    <p class="mb-1"><b>Order Pertama : </b></p>
                    <p class="mb-1"><?= $v['first_order'] ?></p>
                    <p class="mb-1"><b>Repeat Order : </b></p>
                    <p class="mb-1"><?= $v['last_order'] ?></p>
                    <p class="mb-1"><b>Total Order : </b></p>
                    <p class="mb-1"><?= $v['count_order'] ?></p>
                    <p class="mt-1 mb-0"><b>ID : </b></p>
                    <p class="mt-0"><a href="#!" onclick="refresh('<?= $v['id'] ?>')"><?= $v['id_buyer'] ?></a></p>
                </td>
                <td>
                    <input form="form-modal-<?= $k ?>" type="text" class="text-start form-table io-<?= $k ?>" name="dt[phone]" value="<?= $v['phone'] ?>">
                </td>
                <td>
                    <input form="form-modal-<?= $k ?>" type="text" class="text-start form-table io-<?= $k ?>" name="dt[full_name]" value="<?= $v['full_name'] ?>">
                </td>
                <td>
                    <input form="form-modal-<?= $k ?>" type="text" class="text-start form-table io-<?= $k ?>" name="dt[username]" value="<?= $v['username'] ?>">
                </td>
                <!-- <td class="req text-start">
        <select form="form-modal-<?= $k ?>" type="text" class="form-table-select2 so-<?= $k ?>" name="dt[akun_type]" >
        <?php
        $arr = array();
        $arr[] = "Pelanggan";
        $arr[] = "Reseller";
        $arr[] = "Distributor";
        foreach ($arr as $k2 => $v2) {
            $text = '';
            if ($v['akun_type'] == $v2) {
                $text = 'selected';
            }
        ?>
                <option <?= $text ?> value="<?= $v2 ?>"><?= $v2 ?></option>
            <?php } ?>
        </select>
    </td>    -->
                <td class="req">
                    <?= $pesanan ?>
                </td>

                <td class="req text-start">
                    <select form="form-modal-<?= $k ?>" type="text" class="form-table-select2 so-<?= $k ?>" name="dt[grup]">
                        <option value=""></option>
                        <?php
                        foreach ($group_wa as $k2 => $v2) {
                            $text = '';
                            if ($v['grup'] == $v2['name']) {
                                $text = 'selected';
                            }
                        ?>
                            <option <?= $text ?> value="<?= $v2['name'] ?>"><?= $v2['name'] ?> </option>
                        <?php } ?>
                    </select>
                </td>
                <!-- <td>
                    <input form="form-modal-<?= $k ?>" type="date" class="text-start form-table so-<?= $k ?>" name="dt[birth_date]" value="<?= $v['birth_date'] ?>">
                </td> -->
                <td>
                    <input form="form-modal-<?= $k ?>" type="datetime-local" class="text-start form-table so-<?= $k ?>" name="dt[first_order]" value="<?= $v['first_order'] ?>">
                </td>
                <td>
                    <input form="form-modal-<?= $k ?>" type="date" class="text-start form-table so-<?= $k ?>" name="dt[join]" value="<?php if ($v['join']) {
                                                                                                                                            echo DATE("Y-m-d", strtotime($v['join']));
                                                                                                                                        }  ?>">
                </td>
                <td style="max-width:50px!important">
                    <input form="form-modal-<?= $k ?>" type="text" class="text-end form-table io-<?= $k ?>" name="dt[masa_join]" value="<?= $v['masa_join'] ?>" style="max-width:50px!important">
                </td>
                <td class="req text-start">
                    <input form="form-modal-<?= $k ?>" type="date" class="text-start form-table so-<?= $k ?>" name="dt[waktu_fu_ro]" id="waktu_fu_ro-<?= $k ?>" value="<?= $v['waktu_fu_ro'] ?>">
                </td>
                <td>
                    <input form="form-modal-<?= $k ?>" type="date" class="text-start form-table so-<?= $k ?>" name="dt[batas_join]" id="batas_join-<?= $k ?>" value="<?= $v['batas_join'] ?>">
                </td>
                <td class="text-start">
                    <input form="form-modal-<?= $k ?>" type="date" class="text-start form-table so-<?= $k ?>" name="dt[waktu_fu_perkembangan]" id="waktu_fu_perkembangan-<?= $k ?>" value="<?= $v['waktu_fu_perkembangan'] ?>">
                </td>
                <td>
                    <input form="form-modal-<?= $k ?>" type="datetime-local" class="text-start form-table so-<?= $k ?>" name="dt[last_order]" value="<?= $v['last_order'] ?>">
                </td>

                <td class="req">
                    <?= $gift ?>
                </td>
                <td class="req">
                    <textarea form="form-modal-<?= $k ?>" type="text" class="text-start form-table to-<?= $k ?>" name="dt[treatment]" style="width:100%;height:100px"><?= $v['treatment'] ?></textarea>
                </td>
                <td class="req">
                    <textarea form="form-modal-<?= $k ?>" type="text" class="text-start form-table to-<?= $k ?>" name="dt[riwayat_keluhan]" style="width:100%;height:100px"><?= $v['riwayat_keluhan'] ?></textarea>
                </td>
                <!-- <td class="req">
        <textarea form="form-modal-<?= $k ?>" type="text" class="text-start form-table to-<?= $k ?>" name="dt[perkembangan]" style="width:100%;height:100px"><?= $v['perkembangan'] ?></textarea>
    </td> -->
                <!-- <td class="req">
                    <?= $testimoni ?>
                </td>
                <td class="req">
                    <textarea form="form-modal-<?= $k ?>" type="text" class="text-start form-table to-<?= $k ?> pencapaian-<?= $k ?>" name="dt[pencapaian]" style="width:100%;height:100px"><?= $v['pencapaian'] ?></textarea>
                </td> -->
                <td class="req">
                    <textarea form="form-modal-<?= $k ?>" type="text" class="text-start form-table to-<?= $k ?>" name="dt[address]" style="width:100%;height:100px"><?= $v['address'] ?></textarea>
                </td>
                <!-- <td>
                    <input form="form-modal-<?= $k ?>" type="text" class="text-start form-table io-<?= $k ?>" name="dt[province_text]" value="<?= $v['province_text'] ?>">
                </td>
                <td>
                    <input form="form-modal-<?= $k ?>" type="text" class="text-start form-table io-<?= $k ?>" name="dt[city_text]" value="<?= $v['city_text'] ?>">
                </td>
                <td>
                    <input form="form-modal-<?= $k ?>" type="text" class="text-start form-table io-<?= $k ?>" name="dt[subdistrict_text]" value="<?= $v['subdistrict_text'] ?>">
                </td>
                <td class="req text-end"><?= $v['count_order'] ?></td>
                <td class="req text-end"><?= $v['id_buyer'] ?></td>


                <?php if ($v['is_manual'] == 1) { ?>
                    <td class="req text-start">
                        <select form="form-modal-<?= $k ?>" type="text" class="form-table-select2 so-<?= $k ?>" name="dt[marketplace]">
                            <?php
                            foreach ($marketplace as $k2 => $v2) {
                                $text = '';
                                if ($v['marketplace'] == $v2['name']) {
                                    $text = 'selected';
                                }
                            ?>
                                <option <?= $text ?> value="<?= $v2['name'] ?>"><?= $v2['name'] ?> </option>
                            <?php } ?>
                        </select>
                    </td>
                <?php } else { ?>
                    <td class="req text-start"><?= $v['marketplace'] ?></td>
                <?php } ?> -->


                <td class="req text-start" style="border-top-right-radius:15px">
                    <select form="form-modal-<?= $k ?>" type="text" class="form-table-select2 so-<?= $k ?>" name="dt[brand]">
                        <?php
                        foreach ($brands as $k2 => $v2) {
                            $text = '';
                            if ($v['brand'] == $v2['code']) {
                                $text = 'selected';
                            }
                        ?>
                            <option <?= $text ?> value="<?= $v2['code'] ?>"><?= $v2['code'] ?> </option>
                        <?php } ?>
                    </select>
                </td>

                <script>
                    $(document).ready(function() {

                        $(".pencapaian-<?= $k ?>").keyup(function(event) {
                            if ($(this).val() == "Berhasil Hamil") {
                                $("#tr-<?= $k ?>").addClass("bg-success");
                            } else {
                                $("#tr-<?= $k ?>").removeClass("bg-success");
                            }
                        });

                        $(".to-<?= $k ?>").change(function() {
                            var form = $("#form-modal-<?= $k ?>");
                            var mydata = new FormData(form[0]);
                            var column = $(this).attr("name");
                            var value = $(this).val();
                            submit_form_<?= $k ?>(form, mydata, column, value);
                        });
                        $(".co-<?= $k ?>").change(function() {
                            var form = $("#form-modal-<?= $k ?>");
                            var mydata = new FormData(form[0]);
                            var column = $(this).attr("name");
                            var value = $(this).val();
                            submit_form_<?= $k ?>(form, mydata, column, value);
                        });
                        $(".so-<?= $k ?>").change(function() {
                            var form = $("#form-modal-<?= $k ?>");
                            var mydata = new FormData(form[0]);
                            var column = $(this).attr("name");
                            var value = $(this).val();
                            submit_form_<?= $k ?>(form, mydata, column, value);
                        });
                        $(".io-<?= $k ?>").keypress(function(event) {
                            if (event.which === 13) {
                                event.preventDefault();
                                var form = $("#form-modal-<?= $k ?>");
                                var mydata = new FormData(form[0]);
                                var column = $(this).attr("name");
                                var value = $(this).val();
                                submit_form_<?= $k ?>(form, mydata, column, value);
                            }
                        });

                        function submit_form_<?= $k ?>(form, mydata, column, value) {
                            mydata.append("column", column);
                            mydata.append("value", value);
                            if (column == 'dt[join]') {
                                $('#btn-fu-<?= $k ?>').html('FU H+10 #1');
                            }
                            $.ajax({
                                type: "POST",
                                dataType: "json",
                                url: form.attr("action"),
                                data: mydata,
                                cache: false,
                                contentType: false,
                                processData: false,
                                beforeSend: function() {
                                    $(".btn-send").addClass("disabled").html('<div class="loading-ellipsis"><div></div><div></div><div></div><div></div></div>').attr('disabled', true);
                                    form.find(".form-message").slideUp().html("");
                                },
                                success: function(response, textStatus, xhr) {
                                    if (response.status) {
                                        if (column == "dt[first_order]") {
                                            $('#n-order-month-<?= $k ?>').html(response.order_month);
                                        }
                                        if (response.batas_join != '') {
                                            $('#batas_join-<?= $k ?>').val(response.batas_join);
                                        }
                                        if (response.waktu_fu_ro != '') {
                                            $('#waktu_fu_ro-<?= $k ?>').val(response.waktu_fu_ro);
                                        }
                                        if (response.waktu_fu_perkembangan != '') {
                                            $('#waktu_fu_perkembangan-<?= $k ?>').val(response.waktu_fu_perkembangan);
                                        }
                                        $(".form-message").hide().html(response.msg).slideDown("fast");
                                        setTimeout(function() {
                                            // window.location.href = "";
                                            $(".btn-send").removeClass("disabled").html('SAVE CHANGES').attr('disabled', false);
                                        }, 2500);
                                    } else {
                                        $(".form-message").hide().html(response.msg).slideDown("fast");
                                        $(".btn-send").removeClass("disabled").html('SAVE CHANGES').attr('disabled', false);
                                    }
                                },
                                error: function(xhr, textStatus, errorThrown) {
                                    $(".btn-send").removeClass("disabled").html('SAVE CHANGES').attr('disabled', false);
                                    $(".form-message").hide().html(xhr).slideDown("fast");
                                }
                            });
                            return false;
                        }
                    });
                </script>
            </tr>
            <tr style="background:#FFF!important">
                <td colspan="28" style="text-align:left!important;background:#fbfbfb!important;border-top-left-radius: 0px;
    border-top-right-radius: 0px;">

                    <a href="<?= base_url() ?>crm/detail?id=<?= $v['id'] ?>&brand=<?= $v['brand'] ?>" class="btn btn-edit mb-1 ms-0">Detail</a>
                    <?php
                    $today = DATE("Y-m-d");
                    $btn_1 = 'btn-sync';
                    if ($v['waktu_fu_perkembangan'] != '' && $v['waktu_fu_perkembangan'] <= $today) {
                        $btn_1 = 'btn-sync-2';
                    }
                    $btn_2 = 'btn-orange';
                    if ($v['waktu_fu_ro'] != '' && $v['waktu_fu_ro'] <= $today) {
                        $btn_2 = 'btn-orange-2';
                    }
                    ?>
                    <a href="#!" onclick="fu('<?= $v['id'] ?>')" id="btn-fu-<?= $k ?>" class="btn <?= $btn_1 ?> mb-1  ms-1">FU H+10 #<?= $v['count_fu'] + 1 ?></a>
                    <a href="#!" onclick="fu_2('<?= $v['id'] ?>')" class="btn <?= $btn_2 ?> mb-1  ms-1">FU H-7</a>
                    <a href="#!" onclick="remove('<?= $v['id'] ?>')" class="btn btn-delete  ms-1 mb-1">Hapus</a>

                </td>
            </tr>
        </tbody>
    <?php } ?>
<?php $k += 1;
} ?>

<script>
    $('input[name="list_id"]').change(function() {
        get_id();
    });
</script>