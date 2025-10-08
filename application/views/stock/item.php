<?php
$k = $start;

// Check view parameter - default to card if not set
$view = isset($_GET['view']) ? $_GET['view'] : 'card';
$sort_column = isset($_GET['sort_column']) ? $_GET['sort_column'] : 'id';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';

if ($view == 'table') {
    // TABLE VIEW
?>
    <style>
        th.sortable {
            cursor: pointer;
            position: relative;
        }

        th.sortable:hover {
            background-color: #f1f1f1;
        }

        th.sortable i {
            font-size: 0.8em;
            margin-left: 5px;
        }

        .table-responsive {
            transition: all 0.3s ease;
        }

        .spinner-border {
            display: inline-block;
            width: 2rem;
            height: 2rem;
            vertical-align: text-bottom;
            border: 0.25em solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: spinner-border 0.75s linear infinite;
        }

        @keyframes spinner-border {
            to {
                transform: rotate(360deg);
            }
        }

        .marketplace-icon {
            width: 24px;
            height: 24px;
            border-radius: 4px;
            margin-right: 8px;
            object-fit: contain;
        }
    </style>
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="bg-light">
                <tr>
                    <th width="40">

                    </th>
                    <th class="sortable">Order
                        <?php if ($sort_column == 'order_id'): ?>
                            <i class="bi bi-arrow-<?= $sort_order == 'ASC' ? 'up' : 'down' ?>"></i>
                        <?php else: ?>
                            <i class="bi bi-arrow-down-up"></i>
                        <?php endif; ?>
                    </th>
                    <th class="sortable">Produk
                        <?php if ($sort_column == 'product_text'): ?>
                            <i class="bi bi-arrow-<?= $sort_order == 'ASC' ? 'up' : 'down' ?>"></i>
                        <?php else: ?>
                            <i class="bi bi-arrow-down-up"></i>
                        <?php endif; ?>
                    </th>
                    <th class="sortable">Tipe
                        <?php if ($sort_column == 'type'): ?>
                            <i class="bi bi-arrow-<?= $sort_order == 'ASC' ? 'up' : 'down' ?>"></i>
                        <?php else: ?>
                            <i class="bi bi-arrow-down-up"></i>
                        <?php endif; ?>
                    </th>
                    <th class="sortable">Qty
                        <?php if ($sort_column == 'qty'): ?>
                            <i class="bi bi-arrow-<?= $sort_order == 'ASC' ? 'up' : 'down' ?>"></i>
                        <?php else: ?>
                            <i class="bi bi-arrow-down-up"></i>
                        <?php endif; ?>
                    </th>
                    <th class="sortable">Harga
                        <?php if ($sort_column == 'price'): ?>
                            <i class="bi bi-arrow-<?= $sort_order == 'ASC' ? 'up' : 'down' ?>"></i>
                        <?php else: ?>
                            <i class="bi bi-arrow-down-up"></i>
                        <?php endif; ?>
                    </th>
                    <th>Status Pengiriman</th>
                    <th class="sortable">Tanggal
                        <?php if ($sort_column == 'date'): ?>
                            <i class="bi bi-arrow-<?= $sort_order == 'ASC' ? 'up' : 'down' ?>"></i>
                        <?php else: ?>
                            <i class="bi bi-arrow-down-up"></i>
                        <?php endif; ?>
                    </th>
                    <th width="120">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($data as $v) {
                    $marketplace = $v['marketplace'];
                    $marketplace_data = $this->mymodel->selectWithQuery("SELECT img FROM marketplace WHERE name = '$marketplace'");
                    $marketplace_data = $marketplace_data[0];

                    if ($marketplace_data['img']) {
                        $marketplace_icon = base_url() . '/assets/img/marketplace/' . $marketplace_data['img'];
                    } else {
                        $marketplace_icon = base_url() . '/assets/img/marketplace/default.png';
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
                    $v['hpp_total'] = number_format(abs($v['hpp'] * $v['qty']), 0, '', '.');
                    $v['qty'] = number_format(abs($v['qty']), 0, '', '.');
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

                    if ($v['date']) {
                        $v['date'] = $day[(DATE('l', strtotime($v['date'])))] . ', ' . DATE('d', strtotime($v['date'])) . ' ' . substr($month[intval(DATE('m', strtotime($v['date'])))], 0, 3) . ' ' . DATE('Y', strtotime($v['date'])) . ' ' . DATE('H:i:s', strtotime($v['date']));
                    } else {
                        $v['date'] = '-';
                    }

                    $v['shipping_status'] = '-';
                    $shipping_status_class = '';

                    if ($v['order_status'] == "READY_TO_SHIP") {
                        $v['shipping_status'] = "Menunggu Diproses";
                        $shipping_status_class = 'text-info';
                    } else if ($v['order_status'] == "PENDING") {
                        $v['shipping_status'] = "Menunggu Diproses";
                        $shipping_status_class = 'text-info';
                    } else if ($v['order_status'] == "PROCESSED") {
                        $v['shipping_status'] = "Menunggu Ekspedisi";
                        $shipping_status_class = 'text-info';
                    } else if ($v['order_status'] == "SHIPPED") {
                        $v['shipping_status'] = "Dalam Pengiriman";
                        $shipping_status_class = 'text-primary';
                    } else if ($v['order_status'] == "DELIVERED") {
                        $v['shipping_status'] = "Diterima";
                        $shipping_status_class = 'text-success';
                    } else if ($v['order_status'] == "COMPLETED") {
                        $v['shipping_status'] = "Selesai";
                        if ($v['dana_pencairan'] > 0 && $v['is_disbursement'] > 0) {
                            $v['shipping_status'] = "Sudah Dicairkan";
                        }
                        $shipping_status_class = 'text-success';
                    } else if ($v['order_status'] == "CANCELLED") {
                        $v['shipping_status'] = "Dibatalkan";
                        $shipping_status_class = 'text-danger';
                    } else if ($v['order_status'] == "IN_CANCELLED") {
                        $v['shipping_status'] = "Dibatalkan";
                        $shipping_status_class = 'text-danger';
                    } else if ($v['order_status'] == "UNPAID") {
                        $v['shipping_status'] = "Belum Dibayar";
                        $shipping_status_class = 'text-warning';
                    } else if ($v['order_status'] == "TO_CONFIRM_RECEIVE") {
                        $v['shipping_status'] = "Menunggu Diterima";
                        $shipping_status_class = 'text-info';
                    }

                    $v['type'] = $v['type'];
                    if ($v['type'] == "In") {
                        $v['type'] = '<span class="text-success">IN</span>';
                    } else if ($v['type'] == "Ongoing") {
                        $v['type'] = '<span class="text-secondary">ONGOING</span>';
                    } else {
                        $v['type'] = '<span class="text-danger">OUT</span>';
                    }
                ?>
                    <tr>
                        <td>
                            <div class="checkbox-wrapper-13">
                                <input class="checkItem" type="checkbox" value="<?= $v['id'] ?>" data-id="<?= $k ?>" name="list_id" form="form-action">
                            </div>
                        </td>
                        <td>
                            <input class="d-none" type="text" id="box-order-id-<?= $v['id'] ?>" value="<?= $v['order_id'] ?>">
                            <div class="d-flex align-items-center">
                                <img src="<?= $marketplace_icon ?>" alt="<?= $v['marketplace'] ?>" class="marketplace-icon" title="<?= $v['marketplace'] ?>">
                                <div>
                                    <div class="d-flex align-items-center">
                                        <div class="fw-bold me-2"><?= $v['order_id'] ?></div>
                                        <a href="#!" onclick="copy('<?= $v['id'] ?>')" class="text-muted" title="Salin Order ID">
                                            <i class="bi bi-copy"></i>
                                        </a>
                                    </div>
                                    <?php if (!empty($v['awb_number']) && $v['awb_number'] != '-'): ?>
                                        <div class="text-muted small mt-1">
                                            Resi: <?= $v['awb_number'] ?>
                                            <?php if ($v['type_sub'] == "POS" && $v['type'] != "In"): ?>
                                                <a href="<?= base_url() ?>transaction/tracking?id=<?= $v['id_trx'] ?>&order_id=<?= $v['order_id'] ?>&package_number=<?= $v['awb_number'] ?>&marketplace=<?= $v['marketplace'] ?>" target="_blank" title="Lacak Resi">
                                                    <i class="bi bi-box-arrow-up-right ms-1" style="font-size: 0.7rem;"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="fw-bold"><?= $v['product_text'] ?></div>
                            <small class="text-muted"><?= $v['sku'] ?></small>
                        </td>
                        <td><?= $v['type'] ?></td>
                        <td><strong><?= $v['qty'] ?></strong></td>
                        <td>
                            <div class="text-success">Total: Rp <?= $v['price_total'] ?></div>
                            <small class="text-muted">HPP: Rp <?= $v['hpp_total'] ?></small>
                        </td>
                        <td class="<?= $shipping_status_class ?>">
                            <?= $v['shipping_status'] ?>
                        </td>
                        <td>
                            <small><?= $v['date'] ?></small>
                        </td>
                        <td class="text-end">
                            <div class="dropdown d-inline">
                                <a href="#" class="text-muted" id="actionDropdown<?= $v['id'] ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-three-dots-vertical fs-16"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionDropdown<?= $v['id'] ?>">
                                    <?php if ($v['type_sub'] == "POS" && $v['type'] == "In") { ?>
                                        <li>
                                            <a href="#!" class="dropdown-item text-danger" onclick="remove('<?= $v['id'] ?>','<?= $v['product'] ?>','<?= $v['type_sub'] ?>','<?= $v['type'] ?>','<?= $v['id_trx'] ?>')">
                                                <i class="bi bi-trash me-2"></i> Hapus
                                            </a>
                                        </li>
                                    <?php } else if ($v['type_sub'] == "POS") { ?>
                                        <li>
                                            <a href="<?= base_url() ?>transaction/tracking?id=<?= $v['id_trx'] ?>&order_id=<?= $v['order_id'] ?>&package_number=<?= $v['awb_number'] ?>&marketplace=<?= $v['marketplace'] ?>" target="_blank" class="dropdown-item">
                                                <i class="bi bi-truck me-2"></i> Lacak Resi
                                            </a>
                                        </li>
                                    <?php } else { ?>
                                        <li>
                                            <a href="#!" class="dropdown-item" onclick="edit('<?= $v['id'] ?>')">
                                                <i class="bi bi-pencil me-2"></i> Edit
                                            </a>
                                        </li>
                                        <li>
                                            <a href="#!" class="dropdown-item text-danger" onclick="remove('<?= $v['id'] ?>','<?= $v['product'] ?>','<?= $v['type_sub'] ?>','<?= $v['type'] ?>','<?= $v['id_trx'] ?>')">
                                                <i class="bi bi-trash me-2"></i> Hapus
                                            </a>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php $k += 1;
                } ?>
            </tbody>
        </table>
    </div>
<?php
} else {
    // CARD VIEW - Notion Style Design
?>
    <div class="row" id="stock-cards-grid">
        <?php
        foreach ($data as $v) {

            $marketplace = $v['marketplace'];
            $marketplace_data = $this->mymodel->selectWithQuery("SELECT img FROM marketplace WHERE name = '$marketplace'");
            $marketplace_data = $marketplace_data[0];

            if ($marketplace_data['img']) {
                $marketplace_icon = base_url() . '/assets/img/marketplace/' . $marketplace_data['img'];
            } else {
                $marketplace_icon = base_url() . '/assets/img/marketplace/default.png';
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
            $v['hpp_total'] = number_format(abs($v['hpp'] * $v['qty']), 0, '', '.');

            $v['qty'] = number_format(abs($v['qty']), 0, '', '.');
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

            if ($v['order_status'] == "") {
                $v['order_status'] = "COMPLETED";
                $order_class = "bg-green";
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
            // Prepare stock type with proper styling
            $type_badge = '';
            $type_class = '';
            if ($v['type'] == "In") {
                $type_badge = 'IN';
                $type_class = 'bg-success';
            } else if ($v['type'] == "Ongoing") {
                $type_badge = 'ONGOING';
                $type_class = 'bg-secondary';
            } else {
                $type_badge = 'OUT';
                $type_class = 'bg-danger';
            }

            // Prepare shipping status styling
            $shipping_status_class = '';
            if ($v['order_status'] == "READY_TO_SHIP" || $v['order_status'] == "PENDING") {
                $shipping_status_class = 'text-info';
            } else if ($v['order_status'] == "PROCESSED") {
                $shipping_status_class = 'text-info';
            } else if ($v['order_status'] == "SHIPPED") {
                $shipping_status_class = 'text-primary';
            } else if ($v['order_status'] == "DELIVERED" || $v['order_status'] == "COMPLETED") {
                $shipping_status_class = 'text-success';
            } else if ($v['order_status'] == "CANCELLED" || $v['order_status'] == "IN_CANCELLED") {
                $shipping_status_class = 'text-danger';
            } else if ($v['order_status'] == "UNPAID") {
                $shipping_status_class = 'text-warning';
            } else if ($v['order_status'] == "TO_CONFIRM_RECEIVE") {
                $shipping_status_class = 'text-info';
            }
        ?>
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4 stock-item">
                <div class="stock-card" style="cursor: pointer;">
                    <div class="card h-100 stock-item-card">
                        <!-- Card Header with Image Placeholder -->
                        <div class="card-img-wrapper">
                            <img src="https://placehold.co/400x200/f0f0f0/666666?text=<?= urlencode($v['product_text']) ?>"
                                class="card-img-top" alt="<?= htmlspecialchars($v['product_text']) ?>"
                                onerror="this.src='https://placehold.co/400x200/f0f0f0/666666?text=Product+Image'">
                            <div class="card-img-overlay-bottom">
                                <h6 class="text-white mb-1"><?= htmlspecialchars($v['product_text']) ?></h6>
                                <small class="text-white-50"><?= htmlspecialchars($v['sku']) ?></small>
                            </div>
                            <div class="card-status-badge">
                                <span class="badge <?= $type_class ?>"><?= $type_badge ?></span>
                            </div>
                            <div class="card-marketplace-icon">
                                <img src="<?= $marketplace_icon ?>" alt="<?= $v['marketplace'] ?>"
                                    class="marketplace-icon" title="<?= $v['marketplace'] ?>">
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="card-body p-3">
                            <!-- Order Information -->
                            <div class="order-info mb-3">
                                <input class="d-none" type="text" id="box-order-id-<?= $v['id'] ?>" value="<?= $v['order_id'] ?>">
                                <h6 class="card-title mb-2 text-dark fw-medium">
                                    Order #<?= htmlspecialchars($v['order_id']) ?>
                                    <a href="#!" onclick="copy('<?= $v['id'] ?>')" class="text-muted ms-1" title="Copy Order ID">
                                        <i class="bi bi-copy"></i>
                                    </a>
                                </h6>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="text-muted">
                                        <i class="bi bi-shop me-1"></i>
                                        <?= htmlspecialchars($v['shop_name'] ?: 'Manual') ?>
                                    </small>
                                    <small class="text-muted">
                                        <i class="bi bi-calendar me-1"></i>
                                        <?= $v['date'] ?>
                                    </small>
                                </div>
                                <?php if (!empty($v['order_status'])): ?>
                                <?php
                                // Define order status badge styling
                                $order_status_class = 'bg-secondary';
                                $order_status_text = htmlspecialchars($v['order_status']);

                                switch($v['order_status']) {
                                    case 'COMPLETED':
                                        $order_status_class = 'bg-success';
                                        break;
                                    case 'DELIVERED':
                                        $order_status_class = 'bg-success';
                                        break;
                                    case 'SHIPPED':
                                        $order_status_class = 'bg-primary';
                                        break;
                                    case 'PROCESSED':
                                        $order_status_class = 'bg-info';
                                        break;
                                    case 'READY_TO_SHIP':
                                        $order_status_class = 'bg-info';
                                        break;
                                    case 'PENDING':
                                        $order_status_class = 'bg-warning';
                                        break;
                                    case 'UNPAID':
                                        $order_status_class = 'bg-warning';
                                        break;
                                    case 'CANCELLED':
                                    case 'IN_CANCELLED':
                                        $order_status_class = 'bg-danger';
                                        break;
                                    case 'TO_CONFIRM_RECEIVE':
                                        $order_status_class = 'bg-info';
                                        break;
                                    default:
                                        $order_status_class = 'bg-secondary';
                                        break;
                                }
                                ?>
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Status: <span class="badge <?= $order_status_class ?> ms-1"><?= $order_status_text ?></span>
                                    </small>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Product & Pricing Info -->
                            <div class="product-info mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-medium text-dark">Quantity:</span>
                                    <span class="badge bg-primary"><?= $v['qty'] ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">Harga:</span>
                                    <span class="text-primary fw-medium">Rp <?= $v['price'] ?></span>
                                </div>
                                <?php if ($v['type_sub'] == "POS" && !empty($v['hpp'])): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted">HPP:</span>
                                        <span class="text-secondary">Rp <?= $v['hpp'] ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">Total Price:</span>
                                    <span class="text-success fw-medium">Rp <?= $v['price_total'] ?></span>
                                </div>
                                <?php if ($v['type_sub'] == "POS" && !empty($v['hpp_total'])): ?>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">HPP Total:</span>
                                        <span class="text-secondary">Rp <?= $v['hpp_total'] ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Shipping Info (for POS items) -->
                            <?php if ($v['type_sub'] == "POS" && !empty($v['awb_number']) && $v['awb_number'] != '-'): ?>
                                <div class="shipping-info mb-3">
                                    <div class="d-flex align-items-center">
                                        <img src="<?= $shipping['img'] ?>" alt="<?= $v['shipping'] ?>"
                                            class="shipping-icon me-2" style="width: 20px; height: 20px; object-fit: contain;">
                                        <div class="flex-grow-1">
                                            <small class="text-muted d-block">AWB: <?= $v['awb_number'] ?></small>
                                            <small class="<?= $shipping_status_class ?>"><?= $v['shipping_status'] ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Status & Actions -->
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input checkItem" type="checkbox"
                                        value="<?= $v['id'] ?>" data-id="<?= $k ?>" name="list_id" form="form-action">
                                </div>
                                <div class="action-buttons">
                                    <?php if ($v['type_sub'] == "POS" && $v['type'] == "In"): ?>
                                        <button class="btn btn-sm btn-outline-danger"
                                            data-id="<?= html_escape($v['id']) ?>"
                                            data-product="<?= html_escape($v['product']) ?>"
                                            data-type-sub="<?= html_escape($v['type_sub']) ?>"
                                            data-type="<?= html_escape($v['type']) ?>"
                                            data-id-trx="<?= html_escape($v['id_trx']) ?>"
                                            onclick="handleRemove(this)" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php elseif ($v['type_sub'] == "POS"): ?>
                                        <a href="<?= base_url() ?>transaction/tracking?id=<?= $v['id_trx'] ?>&order_id=<?= $v['order_id'] ?>&package_number=<?= $v['awb_number'] ?>&marketplace=<?= $v['marketplace'] ?>"
                                            target="_blank" class="btn btn-sm btn-outline-primary" title="Lacak Resi">
                                            <i class="bi bi-truck me-1"></i>Lacak Resi
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-outline-primary me-1"
                                            onclick="edit('<?= $v['id'] ?>')" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger"
                                            data-id="<?= html_escape($v['id']) ?>"
                                            data-product="<?= html_escape($v['product']) ?>"
                                            data-type-sub="<?= html_escape($v['type_sub']) ?>"
                                            data-type="<?= html_escape($v['type']) ?>"
                                            data-id-trx="<?= html_escape($v['id_trx']) ?>"
                                            onclick="handleRemove(this)" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php $k += 1;
        }
        ?>
    </div> <!-- Close row for card grid -->
<?php
}
?>

<script>
    $('input[name="list_id"]').change(function() {
        get_id();
    });

    function handleRemove(el) {
        const d = el.dataset;
        remove(d.id, d.product, d.typeSub, d.type, d.idTrx);
    }
</script>

<!-- Notion-Style Stock Cards CSS -->
<style>
    /* Stock Card Grid and Layout */
    #stock-cards-grid {
        margin: 0 -8px;
    }

    .stock-item {
        padding: 0 8px;
    }

    .stock-item-card {
        transition: all 0.3s ease;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .stock-item-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        border-color: #1890ff;
    }

    /* Card Image Section */
    .card-img-wrapper {
        position: relative;
        height: 200px;
        overflow: hidden;
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    }

    .card-img-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .stock-item-card:hover .card-img-wrapper img {
        transform: scale(1.05);
    }

    .card-img-overlay-bottom {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
        padding: 16px;
        color: white;
    }

    .card-img-overlay-bottom h6 {
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 4px;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
    }

    .card-img-overlay-bottom small {
        font-size: 0.75rem;
        opacity: 0.9;
    }

    /* Status Badge */
    .card-status-badge {
        position: absolute;
        top: 12px;
        right: 12px;
        z-index: 2;
    }

    .card-status-badge .badge {
        font-size: 0.7rem;
        font-weight: 600;
        padding: 4px 8px;
        border-radius: 12px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    /* Marketplace Icon */
    .card-marketplace-icon {
        position: absolute;
        top: 12px;
        left: 12px;
        z-index: 2;
    }

    .marketplace-icon {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        background: white;
        padding: 4px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        object-fit: contain;
    }

    /* Card Body Content */
    .card-body {
        padding: 16px;
    }

    .order-info {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: 6px;
        padding: 12px;
        border-left: 3px solid #1890ff;
        margin-bottom: 16px;
    }

    .order-info .card-title {
        color: #1890ff;
        font-size: 1rem;
        margin-bottom: 8px;
    }

    .order-info .card-title a {
        text-decoration: none;
        transition: color 0.2s ease;
    }

    .order-info .card-title a:hover {
        color: #40a9ff;
    }

    /* Product Info Section */
    .product-info {
        background: #fafafa;
        border-radius: 6px;
        padding: 12px;
        border: 1px solid #f0f0f0;
    }

    .product-info .fw-medium {
        font-weight: 600;
    }

    /* Shipping Info Section */
    .shipping-info {
        background: linear-gradient(135deg, #e6f7ff, #f0f5ff);
        border-radius: 6px;
        padding: 12px;
        border-left: 3px solid #1890ff;
    }

    .shipping-icon {
        border-radius: 4px;
        background: white;
        padding: 2px;
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 4px;
    }

    .action-buttons .btn-sm {
        border-radius: 4px;
        font-size: 0.75rem;
        transition: all 0.2s ease;
    }

    .action-buttons .btn-outline-primary {
        border-color: #1890ff;
        color: #1890ff;
    }

    .action-buttons .btn-outline-primary:hover {
        background-color: #1890ff;
        border-color: #1890ff;
        color: white;
        transform: translateY(-1px);
    }

    .action-buttons .btn-outline-danger {
        border-color: #ff4d4f;
        color: #ff4d4f;
    }

    .action-buttons .btn-outline-danger:hover {
        background-color: #ff4d4f;
        border-color: #ff4d4f;
        color: white;
        transform: translateY(-1px);
    }

    /* Checkbox Styling */
    .form-check-input {
        border-radius: 4px;
        border: 2px solid #d9d9d9;
        transition: all 0.2s ease;
    }

    .form-check-input:checked {
        background-color: #1890ff;
        border-color: #1890ff;
    }

    .form-check-input:focus {
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
    }

    /* Badge Enhancements */
    .badge {
        font-weight: 600;
        border-radius: 12px;
        padding: 4px 8px;
    }

    .bg-success {
        background-color: #52c41a !important;
    }

    .bg-danger {
        color: white !important;
        background-color: #ff4d4f !important;
    }

    .bg-secondary {
        background-color: #8c8c8c !important;
    }

    .bg-primary {
        background-color: #1890ff !important;
    }

    /* Text Color Enhancements */
    .text-success {
        color: #52c41a !important;
        font-weight: 600;
    }

    .text-danger {
        color: #ff4d4f !important;
    }

    .text-info {
        color: #1890ff !important;
    }

    .text-warning {
        color: #faad14 !important;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .card-img-wrapper {
            height: 180px;
        }

        .order-info {
            text-align: center;
        }

        .action-buttons {
            justify-content: center;
            margin-top: 8px;
        }

        .d-flex.justify-content-between {
            flex-direction: column;
            gap: 12px;
        }
    }

    @media (max-width: 576px) {
        #stock-cards-grid {
            margin: 0 -4px;
        }

        .stock-item {
            padding: 0 4px;
        }

        .card-img-wrapper {
            height: 160px;
        }
    }

    /* Loading and Empty States */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #8c8c8c;
    }

    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        color: #d9d9d9;
    }

    /* Copy Function Feedback */
    .copied-feedback {
        position: fixed;
        top: 20px;
        right: 20px;
        background: #52c41a;
        color: white;
        padding: 8px 16px;
        border-radius: 4px;
        z-index: 9999;
        animation: slideInOut 2s ease-in-out;
    }

    @keyframes slideInOut {
        0% {
            transform: translateX(100%);
            opacity: 0;
        }

        20% {
            transform: translateX(0);
            opacity: 1;
        }

        80% {
            transform: translateX(0);
            opacity: 1;
        }

        100% {
            transform: translateX(100%);
            opacity: 0;
        }
    }
</style>