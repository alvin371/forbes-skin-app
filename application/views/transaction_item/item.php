<?php
$k = $start;


foreach ($data as $v) {

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


   $v['price_total'] = number_format(abs($v['price'] * $v['qty']), 0, '', '.');

   $v['qty'] = number_format(abs($v['qty']), 0, '', '.');

   $v['original_price'] = number_format($v['original_price'], 0, '', '.');
   $v['price'] = number_format($v['price'], 0, '', '.');
   $v['discount'] = number_format($v['discount'], 0, '', '.');
   $v['hpp'] = number_format($v['hpp'], 0, '', '.');

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
   if ($v['type'] == "In") {
      $v['type'] = '<span class="text-success">IN</span>';
   } else {
      $v['type'] = '<span class="text-danger">OUT</span>';
   }
?>
   <div class="card mb-3">
      <div class="row">
         <div class="col-lg-7">
            <input class="d-none" type="text" id="box-order-id-<?= $v['id'] ?>" value="<?= $v['order_id'] ?>">
            <p class="mb-1"><a href="#!" class="a-none text-blue fw-700 fs-16">#<?= $k + 1 ?> | <?= $v['order_id'] ?></a> <a href="#!" onclick="copy('<?= $v['id'] ?>')"><i class="bi bi-copy"></i></a></p>
            <p class="mb-1">Dari <span class="fw-700"><?= $v['marketplace'] ?></span> - <?= $v['shop_name'] ?> (<?= $v['date'] ?>)</p>
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
            <p class="mb-1">Tipe : <b><?= $v['type'] ?></b></p>
            <p class="mb-1">Tipe Sub : <?= $v['type_sub'] ?></p>
            <p class="mb-1">Status Pengiriman : <?= $v['shipping_status'] ?></p>
         </div>
         <div class="col-lg-12">
            <hr>
         </div>
         <div class="col-lg-12">
            <div class="row">
               <div class="col-lg-4 mb-3">
                  <p class="mb-1 fs-16">CS</p>
                  <p class="mb-1 fs-16 fw-700" id="cs-<?= $v['id'] ?>"><?= $v['cs'] ?></p>
                  <p class="mb-1 fs-16">Kurir</p>
                  <div class="box-border mb-3">
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
                  <img style="width:55px;border-radius:10px;" src="<?= $marketplace['img'] ?>">
               </div>
               <div class="col-lg-4 mb-3">
                  <div class="box-border mb-2">
                     <p class="mb-0 mt-3 fs-16">Harga Original : </p>
                     <p class="mb-0 fs-20 fw-700">Rp <?= $v['original_price'] ?></p>
                     <p class="mb-0 mt-3 fs-16">Diskon : </p>
                     <p class="mb-0 fs-20 fw-700">Rp <?= $v['discount'] ?></p>
                     <p class="mb-0 mt-3 fs-16">Harga Jual : </p>
                     <p class="mb-0 fs-20 fw-700">Rp <?= $v['price'] ?></p>
                     <p class="mb-0 mt-3 fs-16">Qty : </p>
                     <p class="mb-0 fs-20 fw-700"><?= abs(intval($v['qty'])) ?></p>
                     <p class="mb-0 mt-3 fs-16">Total Harga : </p>
                     <p class="mb-0 fs-20 fw-700">Rp <?= $v['price_total'] ?></p>

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

               </div>
               <div class="col-lg-4 mb-3">
                  <p class="mb-1 fs-16">Produk</p>
                  <?php
                  $v['sku'] = $v['varian_sku'];
                  if (empty($v['sku'])) {
                     $v['sku'] = $v['product_sku'];
                  }
                  ?>
                  <p class="mb-1 a-none text-blue fw-700 fs-16"><?= abs(intval($v['qty'])) ?> x <?= $v['product_text'] ?> | <?= $v['sku'] ?></p>

               </div>
            </div>
         </div>
         <div class="col-lg-12">
            <hr class="mt-0">
         </div>
         <div class="col-lg-12 pb-2">
            <div class="row">
               <div class="col-lg-12">
                  <a href="<?= base_url() ?>transaction/tracking?id=<?= $v['id_trx'] ?>&order_id=<?= $v['order_id'] ?>&package_number=<?= $v['awb_number'] ?>&marketplace=<?= $v['marketplace'] ?>" target="_blank" class="btn btn-sync  mt-0 ms-1"><i class="bi bi-truck fs-16"></i> Lacak Resi</a>
               </div>
            </div>
         </div>
      </div>
   </div>
<?php $k += 1;
} ?>

<script>
   $('input[name="list_id"]').change(function() {
      get_id();
   });
</script>