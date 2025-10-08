<?php

if ($_GET['start_date'] == "") {
    $start_date = DATE("Y-m-01");
} else {
    $start_date = $_GET['start_date'];
}
if ($_GET['until_date'] == "") {
    $until_date = DATE("Y-m-d");
} else {
    $until_date = $_GET['until_date'];
}
?>
<div class="form-message"></div>
<div class="w-100">
    <div class="row align-items-center">
        <div class="col-lg-12 mb-3">
            <h3 class="text-primary fw-600">TRACK ORDER</h3>
        </div>
    </div>
</div>
<div class="col-lg-12">
    <?php
    $v = $data;

    $marketplace = $v['marketplace'];
    $marketplace = $this->mymodel->selectWithQuery("SELECT img FROM marketplace WHERE name = '$marketplace'");
    $marketplace = $marketplace[0];

    if ($marketplace['img']) {
        $marketplace['img'] = base_url() . '/assets/img/marketplace/' . $marketplace['img'];
    } else {
        $marketplace['img'] = base_url() . '/assets/img/marketplace/default.png';
    }

    $shipping = $v['shipping'];
    $shipping = $this->mymodel->selectWithQuery("SELECT img FROM shipping WHERE name = '$shipping'");
    $shipping = $shipping[0];

    if ($shipping['img']) {
        $shipping['img'] = base_url() . '/assets/img/shipping/' . $shipping['img'];
    } else {
        $shipping['img'] = base_url() . '/assets/img/shipping/default.png';
    }

    $v['customer_price'] = number_format($v['customer_price'], 0, '', '.');
    $v['dana_pencairan'] = number_format($v['dana_pencairan'], 0, '', '.');
    $v['omset_kotor'] = number_format($v['omset_kotor'], 0, '', '.');
    $v['diskon_penjual'] = number_format($v['diskon_penjual'], 0, '', '.');
    $v['omset_bersih'] = number_format($v['omset_bersih'], 0, '', '.');
    $v['marketplace_fee'] = number_format($v['marketplace_fee'], 0, '', '.');
    $v['komisi_afiliasi'] = number_format($v['komisi_afiliasi'], 0, '', '.');
    $v['biaya_lainnya'] = number_format($v['biaya_lainnya'], 0, '', '.');

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
    if ($v['pay_at']) {
        $v['pay_at'] = DATE('d', strtotime($v['pay_at'])) . ' ' . substr($month[intval(DATE('m', strtotime($v['pay_at'])))], 0, 3) . ' ' . DATE('Y', strtotime($v['pay_at']));
    } else {
        $v['pay_at'] = '-';
    }
    if (empty($v['payment_status'])) {
        $v['payment_status'] = '-';
    }
    if ($v['pencairan_at']) {
        $v['pencairan_at'] = DATE('d', strtotime($v['pencairan_at'])) . ' ' . substr($month[intval(DATE('m', strtotime($v['pencairan_at'])))], 0, 3) . ' ' . DATE('Y', strtotime($v['pencairan_at']));
    } else {
        $v['pencairan_at'] = '-';
    }
    if (empty($v['pencairan_status'])) {
        $v['pencairan_status'] = '-';
    }

    if ($v['date']) {
        $v['date'] = $day[(DATE('l', strtotime($v['date'])))] . ', ' . DATE('d', strtotime($v['date'])) . ' ' . substr($month[intval(DATE('m', strtotime($v['date'])))], 0, 3) . ' ' . DATE('Y', strtotime($v['date'])) . ' ' . DATE('H:i:s', strtotime($v['date']));
    } else {
        $v['date'] = '-';
    }

    $payment_class = "bg-red";
    if ($v['payment_status'] == "Paid") {
        $payment_class = "bg-green";
    }
    $settlement_class = "bg-red";
    if ($v['pencairan_status'] == "Settlement") {
        $settlement_class = "bg-green";
    }

    $order_class = "bg-red";

    $reverse_class = "bg-red";

    // if (strpos($data['data']['reverse_status'], 'COMPLETE') !== false) {
    //     $reverse_class = "bg-green";
    // }


    $v['shipping_status'] = '-';

    if ($v['order_status'] == "READY_TO_SHIP") {
        $v['shipping_status'] = "Paket Menunggu Diproses";
        $order_class = "bg-blue";
    } else if ($v['order_status'] == "PENDING") {
        $v['shipping_status'] = "Paket Menunggu Diproses";
        $order_class = "bg-blue";
    } else if ($v['order_status'] == "PROCESSED") {
        $v['shipping_status'] = "Paket Menunggu Diserahkan ke Ekspedisi";
        $order_class = "bg-blue";
    } else if ($v['order_status'] == "SHIPPED") {
        $v['shipping_status'] = "Paket Dalam Proses Pengiriman";
        $order_class = "bg-blue";
    } else if ($v['order_status'] == "DELIVERED") {
        $v['shipping_status'] = "Paket Diterima Customer";
        $order_class = "bg-green";
    } else if ($v['order_status'] == "COMPLETED") {
        $v['shipping_status'] = "Paket Diterima Customer";
        if ($v['dana_pencairan'] > 0 && $v['is_disbursement'] > 0) {
            $v['shipping_status'] = "Sudah Dicairkan";
        }
        $order_class = "bg-green";
    } else if ($v['order_status'] == "CANCELLED") {
        $v['shipping_status'] = "Order Dibatalkan";
    } else if ($v['order_status'] == "IN_CANCELLED") {
        $v['shipping_status'] = "Order Dibatalkan";
    } else if ($v['order_status'] == "UNPAID") {
        $v['shipping_status'] = "Belum Dibayar";
    } else if ($v['order_status'] == "TO_CONFIRM_RECEIVE") {
        $v['shipping_status'] = "Menunggu Diterima Pelanggan";
        $order_class = "bg-blue";
    }

    $cancel_by = '';
    if ($v['order_status'] == "CANCELLED") {
        if ($v['cancel_by']) {
            $cancel_by = ' by ' . ucwords(strtolower($v['cancel_by']));
        }
        if ($v['cancel_status']) {
            $cancel_by .= ' | ' . $v['cancel_status'];
        }
    }

    $return_class = "bg-red";
    if ($v['return_status'] == "ACCEPTED") {
        $return_class = "bg-green";
    }

    if (empty($v['c_username'])) {
        $v['c_username'] = '-';
    }

    if (empty($v['shipping'])) {
        $v['shipping'] = '-';
    }
    if (empty($v['awb_number'])) {
        $v['awb_number'] = '-';
    }

    if (empty($v['customer_text'])) {
        $v['customer_text'] = '-';
    }

    if (empty($v['phone'])) {
        $v['phone'] = '-';
    }
    if (empty($v['shop_name'])) {
        $v['shop_name'] = "Manual";
    }
    if (substr($v['phone'], 0, 1) === "0") {
        $v['phone'] = "62" . substr($v['phone'], 1);
    }


    $url_wa = 'https://api.whatsapp.com/send/?phone=' . $v['phone'] . '&text=Hi ' . $v['customer_text'] . ', apakah pesanan kamu dengan order id ' . $v['order_id'] . ' sudah diterima?';

    if ($v['rts_at']) {
        $v['rts_at'] = DATE('d', strtotime($v['rts_at'])) . ' ' . substr($month[intval(DATE('m', strtotime($v['rts_at'])))], 0, 3) . ' ' . DATE('Y H:i:s', strtotime($v['rts_at']));
    } else {
        $v['rts_at'] = "-";
    }
    ?>
    <div class="card mb-3">
        <div class="row">
            <div class="col-lg-7">
                <input class="d-none" type="text" id="box-order-id-<?= $v['id'] ?>" value="<?= $v['order_id'] ?>">
                <p class="mb-1"><a href="#!" class="a-none text-blue fw-700 fs-16">#<?= $k + 1 ?> | <?= $v['order_id'] ?></a> <a href="#!" onclick="copy('<?= $v['id'] ?>')"><i class="bi bi-copy"></i></a></p>
                <p class="mb-0">Dari <span class="fw-700"><?= $v['marketplace'] ?></span> - <?= $v['shop_name'] ?> (<?= $v['date'] ?>)</p>
                <p class="mb-1">RTS : <span><?= $v['rts_at'] ?></span></p>
                <?php if ($v['reverse_id']) { ?>
                    <p class="mb-1">No Pengajuan <?= $v['reverse_id'] ?></p>
                <?php } ?>
                <p class="mb-1" id="order_status-<?= $v['id'] ?>"><span class="<?= $order_class ?> br-10 fs-12 text-white"><?= $v['order_status'] ?></span></span>
                    <?php if ($v['reverse_status']) { ?>
                        <span class="<?= $reverse_class ?> br-10 fs-12 text-white"><?= $v['reverse_status'] ?></span>
                    <?php } ?>
                </p>

            </div>
            <div class="col-lg-5 text-lg-end text-start">
                <?php



                $arr = array();
                if ($v['order_status'] != 'RETURN') {
                    $arr[0]['icon'] = "icon-1a.png";
                    $arr[0]['class'] = "text-icon";
                    $arr[1]['icon'] = "icon-2a.png";
                    $arr[1]['class'] = "text-icon-2";
                    $arr[2]['icon'] = "icon-3a.png";
                    $arr[2]['class'] = "text-icon-2";
                    $arr[3]['icon'] = "icon-4a.png";
                    $arr[3]['class'] = "text-icon";
                    $arr[4]['icon'] = "icon-6a.png";
                    $arr[4]['class'] = "text-icon";

                    if ($v['order_status'] == "PROCESSED") {
                        $arr[0]['icon'] = "icon-1b.png";
                    } else if ($v['order_status'] == "READY_TO_SHIP") {
                        $arr[0]['icon'] = "icon-1b.png";
                        $arr[1]['icon'] = "icon-2b.png";
                    } else if ($v['order_status'] == "SHIPPED") {
                        $arr[0]['icon'] = "icon-1b.png";
                        $arr[1]['icon'] = "icon-2b.png";
                        $arr[2]['icon'] = "icon-3b.png";
                    } else if ($v['order_status'] == "COMPLETED" || $v['order_status'] == "DELIVERED") {
                        $arr[0]['icon'] = "icon-1b.png";
                        $arr[1]['icon'] = "icon-2b.png";
                        $arr[2]['icon'] = "icon-3b.png";
                        $arr[3]['icon'] = "icon-4b.png";
                        $arr[4]['icon'] = "icon-6b.png";
                    } else if ($v['order_status'] == "CANCELLED") {
                        $arr[0]['icon'] = "icon-1c.png";
                    } else if ($v['order_status'] == "IN_CANCELLED") {
                        $arr[0]['icon'] = "icon-1c.png";
                    } else if ($v['order_status'] == "TO_CONFIRM_RECEIVE") {
                        $arr[0]['icon'] = "icon-1b.png";
                        $arr[1]['icon'] = "icon-2b.png";
                        $arr[2]['icon'] = "icon-3b.png";
                        $arr[3]['icon'] = "icon-4b.png";
                    }
                } else {
                    $arr[0]['icon'] = "icon-5a.png";
                    $arr[0]['class'] = "text-icon-3";
                    $arr[1]['icon'] = "icon-5b.png";
                    $arr[1]['class'] = "text-icon-3";
                    if ($v['return_status'] == "ACCEPTED") {
                        $arr[1]['icon'] = "icon-5b.png";
                        $v['shipping_status'] = "Return Diterima";
                    } else if ($v['return_status'] == "CANCELLED") {
                        $arr[1]['icon'] = "icon-5c.png";
                        $v['shipping_status'] = "Return Ditolak";
                    } else {
                        unset($arr[1]);
                    }
                }
                if (empty($v['cs'])) {
                    $v['cs'] = "-";
                }
                foreach ($arr as $k2 => $v2) {
                ?>
                    <img src="<?= base_url() ?>assets/img/icon/<?= $v2['icon'] ?>" class="ms-3 <?= $v2['class'] ?>" alt="">
                <?php } ?>
                <p class="mb-1">Status Pengiriman : <?= $v['shipping_status'] ?></p>
            </div>
            <div class="col-lg-12">
                <hr>
            </div>
            <div class="col-lg-12">
                <div class="row">
                    <div class="col-lg-4 mb-3">
                        <p class="mb-1 fs-16">Status Order</p>
                        <p class="mb-1 fs-16 fw-700"><?= $v['payment_type'] ?></p>
                        <p class="mb-1 fs-16">Username</p>
                        <p class="mb-1 fs-16 fw-700"><?= $v['c_username'] ?></p>
                        <p class="mb-1 fs-16">No HP</p>
                        <p class="mb-1 fs-16 fw-700"><a href="<?= $url_wa ?>" target="_blank"><?= $v['phone'] ?></a></p>
                        <p class="mb-1 fs-16">Nama Pembeli</p>
                        <p class="mb-2 fs-16 fw-700"><a href="<?= base_url() ?>crm/detail?id=<?= $v['customer'] ?>"><?= $v['customer_text'] ?></a></p>
                        <p class="mb-1 fs-16">CS</p>
                        <p class="mb-1 fs-16 fw-700" id="cs-<?= $v['id'] ?>"><?= $v['cs'] ?></p>
                        <img style="width:55px;border-radius:10px;" src="<?= $marketplace['img'] ?>">
                    </div>
                    <div class="col-lg-4 mb-3">
                        <p class="mb-1 fs-16">Status Bayar & Total Bayar</p>
                        <div class="box-border mb-2">
                            <p class="mb-1 fs-20 fw-700">Rp <?= $v['customer_price'] ?></p>
                            <p class="mb-2 text-white"><span class="<?= $payment_class ?> br-10 fs-12"><?= $v['payment_status'] ?></span> <span class="bg-grey br-10 fs-12"><?= $v['pay_at'] ?></span></p>

                            <p class="mb-0 mt-3 fs-16">Total Dana Pencairan : </p>
                            <p class="mb-1 fs-20 fw-700">Rp <?= $v['dana_pencairan'] ?></p>
                            <p class="mb-2 text-white"><span class="<?= $settlement_class ?> br-10 fs-12"><?= $v['pencairan_status'] ?></span> <span class="bg-grey br-10 fs-12"><?= $v['pencairan_at'] ?></span></p>

                            <a href="#!" data-bs-toggle="modal" data-bs-target="#modal-<?= $k ?>">Buka Detail Pencairan</a>
                            <div class="modal fade" id="modal-<?= $k ?>">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h4 class="modal-title">Detail Pencairan</h4>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-6">
                                                    <p class="mb-1 fs-16 fw-600 fs-16">Omset Kotor</p>
                                                </div>
                                                <div class="col-6 text-lg-end text-start">
                                                    <p class="mb-1 fs-16 fw-600 fs-16">Rp <?= $v['omset_kotor'] ?></p>
                                                </div>
                                                <div class="col-12">
                                                    <hr class="mt-0 mb-3">
                                                </div>
                                                <div class="col-6">
                                                    <p class="mb-1 fs-16 fw-600 fs-16">Diskon & Voucher Penjual</p>
                                                </div>
                                                <div class="col-6 text-lg-end text-start">
                                                    <p class="mb-1 fs-16 fw-600 fs-16">Rp <?= $v['diskon_penjual'] ?></p>
                                                </div>
                                                <div class="col-12">
                                                    <hr class="mt-0 mb-2">
                                                </div>
                                                <div class="col-6">
                                                    <p class="mb-1 fs-16 fw-600 fs-16">Biaya Lainnya</p>
                                                </div>
                                                <div class="col-6 text-lg-end text-start">
                                                    <p class="mb-1 fs-16 fw-600 fs-16">Rp <?= $v['biaya_lainnya'] ?></p>
                                                </div>
                                                <div class="col-12">
                                                    <hr class="mt-0 mb-2">
                                                </div>
                                                <div class="col-6 bg-b pt-1">
                                                    <p class="mb-1 fs-16 fw-600 fs-16">Omset Bersih</p>
                                                </div>
                                                <div class="col-6 bg-b pt-1 text-lg-end text-start">
                                                    <p class="mb-1 fs-16 fw-600 fs-16">Rp <?= $v['omset_bersih'] ?></p>
                                                </div>
                                                <div class="col-12">
                                                    <hr class="mt-0 mb-3">
                                                </div>
                                                <div class="col-6">
                                                    <p class="mb-1 fs-16 fw-600 fs-16">Marketplace Fee</p>
                                                </div>
                                                <div class="col-6 text-lg-end text-start">
                                                    <p class="mb-1 fs-16 fw-600 fs-16">Rp <?= $v['marketplace_fee'] ?></p>
                                                </div>
                                                <div class="col-12">
                                                    <hr class="mt-0 mb-3">
                                                </div>
                                                <div class="col-6">
                                                    <p class="mb-1 fs-16 fw-600 fs-16">Affiliate Fee</p>
                                                </div>
                                                <div class="col-6 text-lg-end text-start">
                                                    <p class="mb-1 fs-16 fw-600 fs-16">Rp <?= $v['komisi_afiliasi'] ?></p>
                                                </div>
                                                <div class="col-12">
                                                    <hr class="mt-0 mb-2">
                                                </div>
                                                <div class="col-6 bg-b pt-1">
                                                    <p class="mb-1 fs-16 fw-600 fs-16">Total Dana Pencairan</p>
                                                </div>
                                                <div class="col-6 bg-b pt-1 text-lg-end text-start">
                                                    <p class="mb-1 fs-16 fw-600 fs-16">Rp <?= $v['dana_pencairan'] ?></p>
                                                </div>
                                                <div class="col-12">
                                                    <hr class="mt-0 mb-3">
                                                </div>
                                            </div>
                                        </div>
                                        <!-- <div class="modal-footer">
                            <button type="button" class="btn mb-2 btn-danger" data-bs-dismiss="modal">Close</button>
                        </div> -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="mb-1 fs-16">Kurir</p>
                        <div class="box-border mb-2">
                            <div class="row">
                                <div class="col-12" style="position:relative">
                                    <div class="row">
                                        <div class="firstDivImg">
                                            <a href="<?= $shipping['img'] ?>" target="_blank"><img class="divIcon" src="<?= $shipping['img'] ?>" alt=""></a>
                                        </div>
                                        <div class="secondDivImg">
                                            <p class="mb-1 fs-16 fw-700"><?= $v['shipping'] ?></p>
                                            <p class="mb-1 fs-16">No Resi : <span id="awb_number-<?= $v['id'] ?>"><?= $v['awb_number'] ?></span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-3">
                        <?php if ($v['is_manual'] == 0) { ?>
                            <p class="mb-1 fs-16">Produk Marketplace (Total <?= $v['pesanan_count'] ?> item)</p>
                            <?php foreach (json_decode($v['pesanan'], true) as $k2 => $v2) {
                                if ($v2['name_parent'] && $v2['name']) {
                                    $v2['item_name'] = $v2['qty'] . 'x ' . str_replace('amp;', '', $v2['name_parent']) . ' | ' . str_replace('amp;', '', $v2['name']);
                                } else {
                                    $v2['item_name'] = $v2['qty'] . 'x ' . str_replace('amp;', '', $v2['name_parent']);
                                }
                                $class = "text-blue";
                                if ($v2['is_empty']) {
                                    $class = "text-red";
                                    $v2['item_name'] .= '<br>ID Product : ' . $v2['id_product_parent'];
                                    $v2['item_name'] .= '<br>ID Varian : ' . $v2['id_product'];
                                }
                            ?>
                                <p class="mb-1 a-none <?= $class ?> fw-700 fs-16"><?= $v2['item_name'] ?></p>
                            <?php  } ?>
                            <hr>
                            <p class="mb-1 fs-16">Produk </p>
                            <?php foreach (json_decode($v['json'], true) as $k2 => $v2) {
                                $v2['item_name'] = $v2['qty'] . 'x ' . str_replace('amp;', '', $v2['product_text']) . ' | ' . str_replace('amp;', '', $v2['sku']);
                            ?>
                                <p class="mb-1 a-none text-blue fw-700 fs-16"><?= $v2['item_name'] ?></p>
                            <?php  } ?>
                        <?php } else { ?>
                            <p class="mb-1 fs-16">Produk (Total <?= $v['pesanan_count'] ?> item)</p>
                            <?php foreach (json_decode($v['pesanan'], true) as $k2 => $v2) {
                                if ($v2['item_name'] != $v2['model_name'] && $v2['model_name'] != '') {
                                    $v2['item_name'] = $v2['qty'] . 'x ' . str_replace('amp;', '', $v2['item_name']) . ' | ' . str_replace('amp;', '', $v2['model_name']);
                                } else {
                                    $v2['item_name'] = $v2['qty'] . 'x ' . str_replace('amp;', '', $v2['item_name']);
                                }
                            ?>
                                <p class="mb-1 a-none text-blue fw-700 fs-16"><?= $v2['item_name'] ?></p>
                            <?php  } ?>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <hr class="mt-0">
            </div>
            <div class="col-lg-12 pb-2">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="checkbox-wrapper-13 d-inline">
                            <input class="checkItem" style="top:10px" type="checkbox" value="<?= $v['id'] ?>" data-id="<?= $k ?>" name="list_id" form="form-action">
                        </div>
                        <input type="hidden" value="<?= $v['is_manual'] ?>" name="is_manual[<?= $k - $start ?>]" form="form-action">
                        <input type="hidden" value="<?= $v['marketplace'] ?>" name="marketplace[<?= $k - $start ?>]" form="form-action">
                        <input type="hidden" value="<?= $v['brand'] ?>" name="brand[<?= $k - $start ?>]" form="form-action">
                        <input type="hidden" value="<?= $v['order_id'] ?>" name="order_id[<?= $k - $start ?>]" form="form-action">
                        <a href="<?= base_url() ?>transaction/print?id=<?= $v['id'] ?>" target="_blank" class="btn mb-2 btn-edit  mt-0 ms-1"><i class="bi bi-printer fs-16"></i> Print</a>
                        <a onclick="set_cs('<?= $v['id'] ?>')" class="btn mb-2 btn-sync  mt-0 ms-1"><i class="bi bi-people fs-16"></i> CS</a>
                        <a onclick="set_resi('<?= $v['id'] ?>')" class="btn mb-2 btn-sync  mt-0 ms-1"><i class="bi bi-truck fs-16"></i> No Resi</a>
                        <a onclick="set_return('<?= $v['id'] ?>')" class="btn mb-2 btn-delete  mt-0 ms-1"><i class="bi bi-backspace fs-16"></i> Return</a>
                    </div>
                    <div class="col-lg-6 text-lg-end text-start">
                        <?php
                        if ($v['is_manual'] == 1) { ?>
                            <a href="#!" onclick="remove('<?= $v['id'] ?>')" class="btn mb-2 btn-delete  mt-0 mb-3"><i class="bi bi-trash fs-16"></i> Hapus Order</a>
                            <a href="<?= base_url() ?>transaction/tracking?id=<?= $v['id'] ?>&order_id=<?= $v['order_id'] ?>&package_number=<?= $v['awb_number'] ?>&marketplace=<?= $v['marketplace'] ?>" target="_blank" class="btn mb-2 btn-sync  mt-0 ms-1 mb-3"><i class="bi bi-truck fs-16"></i> Lacak Resi</a>


                            <a href="<?= base_url() ?>transaction/edit?id=<?= $v['id'] ?>" class="btn mb-2 btn-edit  mt-0 ms-1 mb-3"><i class="bi bi-pencil-square fs-16"></i> Edit Order</a>
                        <?php } else { ?>
                            <?php if ($v['is_manual'] == 0) { ?>
                                <a onclick="refresh('<?= $v['order_id'] ?>','<?= $v['marketplace'] ?>')" class="btn mb-2 btn-sync  mt-0 ms-2 mb-3"><i class="bi bi-newspaper fs-16"></i> Refresh</a>
                            <?php } ?>
                            <a href="<?= base_url() ?>transaction/tracking?id=<?= $v['id'] ?>&order_id=<?= $v['order_id'] ?>&package_number=<?= $v['awb_number'] ?>&marketplace=<?= $v['marketplace'] ?>" target="_blank" class="btn mb-2 btn-sync  mt-0 ms-1 mb-3"><i class="bi bi-truck fs-16"></i> Lacak Resi</a>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card col-lg-12 pb-0">
        <p class="mb-1 a-none fw-700 fs-16">TRACK ORDER</p>
        <hr class="mt-1 mb-3">
        <div id="div-track">
            <div class="mb-3"><i class="fa fa-circle-o-notch fa-spin"></i> Memuat data ... </div>
        </div>
        <script>
            $.ajax({
                dataType: "json",
                url: '<?= base_url() ?>transaction/tracking-ajax<?= $param ?>',
                success: function(html) {
                    $("#div-track").html(html.html);
                }
            });
        </script>
    </div>
</div>
</div>
</div>