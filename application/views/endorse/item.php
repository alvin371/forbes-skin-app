<?php
$k = $start;
function separator_only($angka) {
    $number = doubleval($angka);
    return number_format(round($number), 0, ',', '.');
}

$view = isset($_GET['view']) ? $_GET['view'] : 'card';

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
            to { transform: rotate(360deg); }
        }
    </style>
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="bg-light">
                    <!-- <th data-original-index="<?= $k + 1 ?>"> -->
                    <th width="40">#</th>
                    <th class="sortable">Nama Influencer 
                        <?php if ($sort_column == 'nama_creator'): ?>
                            <i class="bi bi-arrow-<?= $sort_order == 'ASC' ? 'up' : 'down' ?>"></i>
                        <?php else: ?>
                            <i class="bi bi-arrow-down-up"></i>
                        <?php endif; ?>
                    </th>
                    <th class="sortable">PIC 
                        <?php if ($sort_column == 'pic'): ?>
                            <i class="bi bi-arrow-<?= $sort_order == 'ASC' ? 'up' : 'down' ?>"></i>
                        <?php else: ?>
                            <i class="bi bi-arrow-down-up"></i>
                        <?php endif; ?>
                    </th>
                    <th class="sortable">Total Cost 
                        <?php if ($sort_column == 'total_cost'): ?>
                            <i class="bi bi-arrow-<?= $sort_order == 'ASC' ? 'up' : 'down' ?>"></i>
                        <?php else: ?>
                            <i class="bi bi-arrow-down-up"></i>
                        <?php endif; ?>
                    </th>
                    <th class="sortable">Status
                        <?php if ($sort_column == 'status_endorse'): ?>
                            <i class="bi bi-arrow-<?= $sort_order == 'ASC' ? 'up' : 'down' ?>"></i>
                        <?php else: ?>
                            <i class="bi bi-arrow-down-up"></i>
                        <?php endif; ?>
                    </th>
                    <th class="sortable">Tanggal Posting
                        <?php if ($sort_column == 'posted_at'): ?>
                            <i class="bi bi-arrow-<?= $sort_order == 'ASC' ? 'up' : 'down' ?>"></i>
                        <?php else: ?>
                            <i class="bi bi-arrow-down-up"></i>
                        <?php endif; ?>
                    </th>
                    <th class="sortable">Views
                        <?php if ($sort_column == 'views'): ?>
                            <i class="bi bi-arrow-<?= $sort_order == 'ASC' ? 'up' : 'down' ?>"></i>
                        <?php else: ?>
                            <i class="bi bi-arrow-down-up"></i>
                        <?php endif; ?>
                    </th>
                    <th class="sortable">CPM 
                        <?php if ($sort_column == 'cpm'): ?>
                            <i class="bi bi-arrow-<?= $sort_order == 'ASC' ? 'up' : 'down' ?>"></i>
                        <?php else: ?>
                            <i class="bi bi-arrow-down-up"></i>
                        <?php endif; ?>
                    </th>
                    <th class="sortable">Engagement 
                        <?php if ($sort_column == 'likes'): ?>
                            <i class="bi bi-arrow-<?= $sort_order == 'ASC' ? 'up' : 'down' ?>"></i>
                        <?php else: ?>
                            <i class="bi bi-arrow-down-up"></i>
                        <?php endif; ?>
                    </th>
                    <th>Link Upload</i></th>
                    <th>Kode Ads</th>
                    <th>Keterangan</th>
                    <th width="120">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $v): 
                    if ($v['platform'] == "Tiktok") {
                        $v['img'] = base_url() . '/assets/img/icon/icon-tiktok.png';
                    } else if ($v['platform'] == "Instagram") {
                        $v['img'] = base_url() . '/assets/img/icon/icon-ig.png';
                    } else if ($v['platform'] == "Youtube") {
                        $v['img'] = base_url() . '/assets/img/icon/icon-youtube.png';
                    } else if ($v['platform'] == "Facebook") {
                        $v['img'] = base_url() . '/assets/img/icon/icon-fb.png';
                    } else if ($v['platform'] == "Twitter") {
                        $v['img'] = base_url() . '/assets/img/icon/icon-twitter.png';
                    } else {
                        $v['img'] = base_url() . '/assets/img/icon/icon-no.png';
                    }
                    if ($v['nama_creator'] == "") {
                        $v['nama_creator'] = "-";
                    }
                    if ($v['posting_at']) {
                        $v['posting_at'] = DATE("d/m/Y", strtotime($v['posting_at']));
                    } else {
                        $v['posting_at'] = '-';
                    }
                    if ($v['rencana_at']) {
                        $v['rencana_at'] = DATE("d/m/Y", strtotime($v['rencana_at']));
                    } else {
                        $v['rencana_at'] = '-';
                    }
                    if ($v['barang_dikirim_at']) {
                        $v['barang_dikirim_at'] = DATE("d/m/Y", strtotime($v['barang_dikirim_at']));
                    } else {
                        $v['barang_dikirim_at'] = '-';
                    }
                    if ($v['sync_at']) {
                        $v['sync_at'] = DATE("d/m/Y", strtotime($v['sync_at']));
                    } else {
                        $v['sync_at'] = '-';
                    }
                    if ($v['created_at']) {
                        $v['created_at'] = DATE("d/m/Y", strtotime($v['created_at']));
                    } else {
                        $v['created_at'] = '-';
                    }
                    if ($v['link_brief']) {
                        $v['link_brief'] = '<a href="' . $v['link_brief'] . '" target="_blank">' . $v['link_brief'] . '</a>';
                    } else {
                        $v['link_brief'] = '-';
                    }
                    if ($v['link_mou']) {
                        $v['link_mou'] = '<a href="' . $v['link_mou'] . '" target="_blank">' . $v['link_mou'] . '</a>';
                    } else {
                        $v['link_mou'] = '-';
                    }
                    if ($v['link_upload']) {
                        $v['img'] = '<a href="' . $v['link_upload'] . '" target="_blank"><img style="width:40px;border-radius:10px;" class="mt-0" src="' . $v['img'] . '"></a>';
                    } else {
                        $v['img'] = '<img style="width:40px;border-radius:10px; filter: grayscale(100%)!important;" class="mt-0" src="' . $v['img'] . '">';
                    }
                    
                    $bg = '#e6e6e6';
                    $clr = '#000';
                    if (in_array($v['status_endorse'], array('Review'))) {
                        $bg = '#e6e6e6';
                    } else if (in_array($v['status_endorse'], array('Hold'))) {
                        $bg = '#ffe599';
                    } else if (in_array($v['status_endorse'], array('Acc', 'Pengajuan Payment'))) {
                        $bg = '#f6b26b';
                    } else if (in_array($v['status_endorse'], array('Barang Dikirim'))) {
                        $bg = '#ffd0d0';
                    } else if (in_array($v['status_endorse'], array('Draft Content'))) {
                        $bg = '#d4edbc';
                    } else if (in_array($v['status_endorse'], array('Posted Content'))) {
                        $bg = '#7bd3ea';
                    } else if (in_array($v['status_endorse'], array('Reject', 'Problem'))) {
                        $bg = '#ea7b7b';
                    }
            
                    if (in_array($v['status_payment'], array('DP'))) {
                        $bg_payment = '#60bb55';
                        $clr_payment = '#000';
                    } else if (in_array($v['status_payment'], array('FP'))) {
                        $bg_payment = '#1255cc';
                        $clr_payment = '#000';
                    } else if (in_array($v['status_payment'], array('Pengajuan Payment'))) {
                        $bg_payment = '#e6e6e6';
                        $clr_payment = '#000';
                    } else if (in_array($v['pengajuan_status_payment'], array('Pengajuan Payment DP'))) {
                        $bg_payment = '#C2FFC7';
                        $clr_payment = '#000';
                    } else if (in_array($v['pengajuan_status_payment'], array('Pengajuan Payment FP'))) {
                        $bg_payment = '#A5BFCC';
                        $clr_payment = '#000';
                    } else if (in_array($v['status_payment'], array(' '))) {
                        $bg_payment = '#fff';
                        $clr_payment = '#fff';
                    } else if (in_array($v['pengajuan_status_payment'], array(' '))) {
                        $bg_payment = '#fff';
                        $clr_payment = '#fff';
                    }
            
                    if (empty($v['desc'])) {
                        $v['desc'] = '-';
                    }
            
                    $creator = $this->mymodel->selectDataOne('influencer', array('id' => $v['influencer']));
                    $v['type'] = $creator['type'];
                    $v['tipe_kontak'] = $creator['tipe_kontak'];
                    $v['url'] = $creator['url'];
            
                    if ($v['type'] == "Tiktok") {
                        $v['img_creator_1'] = base_url() . '/assets/img/icon/icon-tiktok.png';
                    } else if ($v['type'] == "Instagram") {
                        $v['img_creator_1'] = base_url() . '/assets/img/icon/icon-ig.png';
                    } else if ($v['type'] == "Youtube") {
                        $v['img_creator_1'] = base_url() . '/assets/img/icon/icon-youtube.png';
                    } else if ($v['type'] == "Facebook") {
                        $v['img_creator_1'] = base_url() . '/assets/img/icon/icon-fb.png';
                    } else if ($v['type'] == "Twitter") {
                        $v['img_creator_1'] = base_url() . '/assets/img/icon/icon-twitter.png';
                    } else {
                        $v['img_creator_1'] = base_url() . '/assets/img/icon/icon-no.png';
                    }
            
                    if ($v['url']) {
                        $v['img_creator_1'] = '<a href="' . $v['url'] . '" target="_blank"><img style="width:30px;border-radius:40px;" class="mt-0 me-1" src="' . $v['img_creator_1'] . '"></a>';
                    } else {
                        $v['img_creator_1'] = '<img style="width:30px;border-radius:40px; filter: grayscale(100%)!important;" class="mt-0 me-1" src="' . $v['img_creator_1'] . '">';
                    }
            
                    if ($v['tipe_kontak'] == "WA") {
                        $v['img_creator_2'] = base_url() . '/assets/img/icon/icon-wa.png';
                    } else if ($v['tipe_kontak'] == "IG") {
                        $v['img_creator_2'] = base_url() . '/assets/img/icon/icon-ig.png';
                    } else if ($v['tipe_kontak'] == "Email") {
                        $v['img_creator_2'] = base_url() . '/assets/img/icon/icon-email.png';
                    } else if ($v['tipe_kontak'] == "HP") {
                        $v['img_creator_2'] = base_url() . '/assets/img/icon/icon-phone.png';
                    } else {
                        $v['img_creator_2'] = base_url() . '/assets/img/icon/icon-no.png';
                    }
            
                    if ($v['contact']) {
                        $v['img_creator_2'] = '<a href="' . $v['contact'] . '" target="_blank"><img style="width:30px;border-radius:40px;" class="mt-0" src="' . $v['img_creator_2'] . '"></a>';
                    } else {
                        $v['img_creator_2'] = '<img style="width:30px;border-radius:40px; filter: grayscale(100%)!important;" class="mt-0" src="' . $v['img_creator_2'] . '">';
                    }
                    $creator = $this->mymodel->selectDataOne('influencer', array('id' => $v['influencer']));
                    $v['nama_creator'] = $v['nama_creator'] ?: "-";
                    $v['pic'] = $v['pic'] ?: "-";
                    $v['desc'] = $v['desc'] ?: "-";
                    
                    // Status background color
                    $bg = '#e6e6e6';
                    $clr = '#000';
                    if (in_array($v['status_endorse'], array('Hold'))) {
                        $bg = '#ffe599';
                    } else if (in_array($v['status_endorse'], array('Acc', 'Pengajuan Payment'))) {
                        $bg = '#f6b26b';
                    } else if (in_array($v['status_endorse'], array('Barang Dikirim'))) {
                        $bg = '#ffd0d0';
                    } else if (in_array($v['status_endorse'], array('Draft Content'))) {
                        $bg = '#d4edbc';
                    } else if (in_array($v['status_endorse'], array('Posted Content'))) {
                        $bg = '#7bd3ea';
                    } else if (in_array($v['status_endorse'], array('Reject', 'Problem'))) {
                        $bg = '#ea7b7b';
                    }
                    
                    // Payment status
                    $bg_payment = '#fff';
                    $clr_payment = '#000';
                    if (in_array($v['status_payment'], array('DP'))) {
                        $bg_payment = '#60bb55';
                    } else if (in_array($v['status_payment'], array('FP'))) {
                        $bg_payment = '#8CCDEB';
                    } else if (in_array($v['status_payment'], array('Pengajuan Payment'))) {
                        $bg_payment = '#e6e6e6';
                    } else if (in_array($v['pengajuan_status_payment'], array('Pengajuan Payment DP'))) {
                        $bg_payment = '#C2FFC7';
                    } else if (in_array($v['pengajuan_status_payment'], array('Pengajuan Payment FP'))) {
                        $bg_payment = '#A5BFCC';
                    }
                ?>
                <tr>
                    <!-- <td><?= $k + 1 ?></td> -->
                    <td>
                        <div class="checkbox-wrapper-13 d-inline">
                            <input class="checkItem" style="" type="checkbox" value="<?= $v['id'] ?>" data-id="<?= $k ?>" name="list_id" form="form-action">
                        </div>
                    </td>
                    <td class="text-start">
                        <div>
                            <span class="fw-bold" style="font-size: 16px;"><?= $v['nama_creator'] ?></span>
                            <i class="bi bi-eye text-blue ms-1" id="eye-list-<?= $v['nama_creator'] ?>"></i>
                        </div>

                        <div class="d-flex align-items-center mt-1">
                            <?= $v['img_creator_1'] ?><br>
                            <?= $v['img_creator_2'] ?>
                            <a href="#!" onclick="change_fyp_<?= $k ?>()" id="fyp_<?= $k ?>" class="ms-1">
                                <?php if ($v['is_fyp'] == 1) { ?>
                                    <i class="bi bi-star-fill" style="font-size:30px;color:#ffd250"></i>
                                <?php } else { ?>
                                    <i class="bi bi-star" style="font-size:30px;color:#000"></i>
                                <?php } ?>
                                <script>
                                    function change_fyp_<?= $k ?>() {
                                        $('#fyp_<?= $k ?>').html('<i style="font-size:30px;color:#000" class="fa fa-circle-o-notch fa-spin"></i>');
                                        $.ajax({
                                            dataType: "json",
                                            url: '<?= base_url() ?>ajax/change-fyp?id=<?= $v['id'] ?>',
                                            success: function(html) {
                                                $('#fyp_<?= $k ?>').html(html.html);
                                                <?php $v['code'] =  'mar-5' ?>
                                                $.ajax({
                                                    dataType: "json",
                                                    url: '<?= base_url() ?>ajax/get-summary-campaign<?= $param ?>&id=<?= $v['code'] ?>&id_campaign=<?= $v['id_campaign'] ?>',
                                                    success: function(html) {
                                                        $("#summary-<?= $v['code'] ?>").html(html.html);
                                                    }
                                                });
                                            }
                                        });
                                    }
                                </script>
                            </a>
                        </div>
                    </td>

                    <td><?= $v['pic'] ?></td>
                    <td><?= separator_only($v['total_cost']) ?></td>
                    <td>
                        <!-- Status Endorse -->
                        <div>
                            <span class="badge" style="background-color:<?= $bg ?>; color:<?= $clr ?>">
                                <?= strtoupper(strtolower($v['status_endorse'])) ?>
                            </span>
                        </div>

                        <!-- Status Payment -->
                        <div style="margin-top: 5px;">
                            <?php if (!empty($v['status_payment'])): ?>
                                <span class="badge" style="background-color:<?= $bg_payment ?>; color:<?= $clr_payment ?>">
                                    <?= strtoupper(strtolower($v['status_payment'])) ?>
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($v['pengajuan_status_payment'])): ?>
                                <span class="badge" style="background-color:<?= $bg_payment ?>; color:<?= $clr_payment ?>">
                                    <?= strtoupper(strtolower($v['pengajuan_status_payment'])) ?>
                                </span>
                            <?php endif; ?>

                            <?php if (trim($v['status_payment']) === '' && trim($v['pengajuan_status_payment']) === ''): ?>
                                <span class="badge" style="background-color:<?= $bg_payment ?>; color:<?= $clr_payment ?>">
                                    -
                                </span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td><?= $v['posting_at'] ? $v['posting_at'] : '-' ?></td>
                    <td><?= separator_only($v['views']) ?></td>
                    <td><?= separator_only($v['cpm']) ?></td>
                    <td class="text-start"><?= separator_only($v['engagement']) ?></td>
                    <td>
                        <div class="firstDivImg">
                            <?= $v['img'] ?>
                        </div>
                    </td>
                    
                    <td>
                        <?php if (empty($v['kode_ads'])): ?>
                            -
                        <?php else: ?>
                            <a href="javascript:void(0)" 
                            class="text-decoration-none btn-copy btn btn-sm btn-outline-primary" 
                            data-kode="<?= htmlspecialchars($v['kode_ads']) ?>" 
                            title="Salin Kode">
                                <i class="bi bi-clipboard fs-16"></i>
                            </a>
                        <?php endif; ?>
                    </td>
                            

                    <div class="col-lg-12 mb-3">
                        <input type="hidden" value="<?= $v['is_manual'] ?>" name="is_manual[<?= $k - $start ?>]" form="form-action">
                        <input type="hidden" value="<?= $v['marketplace'] ?>" name="marketplace[<?= $k - $start ?>]" form="form-action">
                        <input type="hidden" value="<?= $v['brand'] ?>" name="brand[<?= $k - $start ?>]" form="form-action">
                        <input type="hidden" value="<?= $v['order_id'] ?>" name="order_id[<?= $k - $start ?>]" form="form-action">
                    </div>
                    <td style="white-space: normal; word-wrap: break-word;">
                        <?= $v['desc'] ?>
                    </td>

                    <td class="text-end">
                        <div class="col-lg-4 text-lg-end text-end">
                            <div class="dropdown">
                                <a href="#" class="text-muted" id="actionDropdown<?= $v['id'] ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-three-dots-vertical fs-16"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionDropdown<?= $v['id'] ?>">
                                    <li>
                                        <a href="#!" class="dropdown-item text-danger" onclick="remove('<?= $v['id'] ?>')">
                                            <i class="bi bi-trash me-2"></i> Delete Data
                                        </a>
                                    </li>
                                    <?php if ($v['status'] == "Aktif" && $v['status_campaign'] == "Aktif" && $v['link_upload'] != "") { ?>
                                    <li>
                                        <a href="#!" class="dropdown-item" onclick="sync('<?= $v['id'] ?>')">
                                            <i class="bi bi-bootstrap-reboot me-2"></i> Refresh
                                        </a>
                                    </li>
                                    <?php } ?>
                                    <li>
                                        <a href="#!" class="dropdown-item" onclick="clone('<?= $v['id'] ?>')">
                                            <i class="bi bi-copy me-2"></i> Kloning
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#!" class="dropdown-item" onclick="edit('<?= $v['id'] ?>')">
                                            <i class="bi bi-pencil-square me-2"></i> Edit Data
                                        </a>
                                    </li>
                                    <?php if ($v['status_endorse'] != "Review" && $v['status_endorse'] != 'Hold' && $v['status_endorse'] != 'Reject' && $v['link_mou'] != '-') { ?>
                                    <li>
                                        <a href="#!" class="dropdown-item" onclick="set_payment(<?= $v['id'] ?>)">
                                            <i class="bi bi-clipboard2-check me-2"></i> Ajukan Payment
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#!" class="dropdown-item text-danger" onclick="set_batalkan_payment(<?= $v['id'] ?>)">
                                            <i class="bi bi-clipboard2-x me-2"></i> Batalkan Payment
                                        </a>
                                    </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </div>
                    </td>

                </tr>
                <?php $k += 1; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
} else {
    // CARD VIEW (default)
    foreach ($data as $v) {
        if ($v['platform'] == "Tiktok") {
            $v['img'] = base_url() . '/assets/img/icon/icon-tiktok.png';
        } else if ($v['platform'] == "Instagram") {
            $v['img'] = base_url() . '/assets/img/icon/icon-ig.png';
        } else if ($v['platform'] == "Youtube") {
            $v['img'] = base_url() . '/assets/img/icon/icon-youtube.png';
        } else if ($v['platform'] == "Facebook") {
            $v['img'] = base_url() . '/assets/img/icon/icon-fb.png';
        } else if ($v['platform'] == "Twitter") {
            $v['img'] = base_url() . '/assets/img/icon/icon-twitter.png';
        } else {
            $v['img'] = base_url() . '/assets/img/icon/icon-no.png';
        }
        if ($v['nama_creator'] == "") {
            $v['nama_creator'] = "-";
        }
        if ($v['posting_at']) {
            $v['posting_at'] = DATE("d/m/Y", strtotime($v['posting_at']));
        } else {
            $v['posting_at'] = '-';
        }
        if ($v['rencana_at']) {
            $v['rencana_at'] = DATE("d/m/Y", strtotime($v['rencana_at']));
        } else {
            $v['rencana_at'] = '-';
        }
        if ($v['barang_dikirim_at']) {
            $v['barang_dikirim_at'] = DATE("d/m/Y H:i", strtotime($v['barang_dikirim_at']));
        } else {
            $v['barang_dikirim_at'] = '-';
        }
        if ($v['sync_at']) {
            $v['sync_at'] = DATE("d/m/Y", strtotime($v['sync_at']));
        } else {
            $v['sync_at'] = '-';
        }
        if ($v['created_at']) {
            $v['created_at'] = DATE("d/m/Y", strtotime($v['created_at']));
        } else {
            $v['created_at'] = '-';
        }
        if ($v['link_brief']) {
            $v['link_brief'] = '<a href="' . $v['link_brief'] . '" target="_blank">' . $v['link_brief'] . '</a>';
        } else {
            $v['link_brief'] = '-';
        }
        if ($v['link_mou']) {
            $v['link_mou'] = '<a href="' . $v['link_mou'] . '" target="_blank">' . $v['link_mou'] . '</a>';
        } else {
            $v['link_mou'] = '-';
        }
        if ($v['link_upload']) {
            $v['img'] = '<a href="' . $v['link_upload'] . '" target="_blank"><img style="width:40px;border-radius:10px;" class="mt-0" src="' . $v['img'] . '"></a>';
        } else {
            $v['img'] = '<img style="width:40px;border-radius:10px; filter: grayscale(100%)!important;" class="mt-0" src="' . $v['img'] . '">';
        }
        
        $bg = '#e6e6e6';
        $clr = '#000';
        if (in_array($v['status_endorse'], array('Review'))) {
            $bg = '#e6e6e6';
        } else if (in_array($v['status_endorse'], array('Hold'))) {
            $bg = '#ffe599';
        } else if (in_array($v['status_endorse'], array('Acc', 'Pengajuan Payment'))) {
            $bg = '#f6b26b';
        } else if (in_array($v['status_endorse'], array('Barang Dikirim'))) {
            $bg = '#ffd0d0';
        } else if (in_array($v['status_endorse'], array('Draft Content'))) {
            $bg = '#d4edbc';
        } else if (in_array($v['status_endorse'], array('Posted Content'))) {
            $bg = '#7bd3ea';
        } else if (in_array($v['status_endorse'], array('Reject'))) {
            $bg = '#ea7b7b';
        }

        if (in_array($v['status_payment'], array('DP'))) {
            $bg_payment = '#60bb55';
            $clr_payment = '#000';
        } else if (in_array($v['status_payment'], array('FP'))) {
            $bg_payment = '#8CCDEB';
            $clr_payment = '#000';
        } else if (in_array($v['status_payment'], array('Pengajuan Payment'))) {
            $bg_payment = '#e6e6e6';
            $clr_payment = '#000';
        } else if (in_array($v['pengajuan_status_payment'], array('Pengajuan Payment DP'))) {
            $bg_payment = '#C2FFC7';
            $clr_payment = '#000';
        } else if (in_array($v['pengajuan_status_payment'], array('Pengajuan Payment FP'))) {
            $bg_payment = '#A5BFCC';
            $clr_payment = '#000';
        } else if (in_array($v['status_payment'], array(' '))) {
            $bg_payment = '#fff';
            $clr_payment = '#fff';
        } else if (in_array($v['pengajuan_status_payment'], array(' '))) {
            $bg_payment = '#fff';
            $clr_payment = '#fff';
        }

        if (empty($v['desc'])) {
            $v['desc'] = '-';
        }

        $creator = $this->mymodel->selectDataOne('influencer', array('id' => $v['influencer']));
        $v['type'] = $creator['type'];
        $v['tipe_kontak'] = $creator['tipe_kontak'];
        $v['url'] = $creator['url'];

        if ($v['type'] == "Tiktok") {
            $v['img_creator_1'] = base_url() . '/assets/img/icon/icon-tiktok.png';
        } else if ($v['type'] == "Instagram") {
            $v['img_creator_1'] = base_url() . '/assets/img/icon/icon-ig.png';
        } else if ($v['type'] == "Youtube") {
            $v['img_creator_1'] = base_url() . '/assets/img/icon/icon-youtube.png';
        } else if ($v['type'] == "Facebook") {
            $v['img_creator_1'] = base_url() . '/assets/img/icon/icon-fb.png';
        } else if ($v['type'] == "Twitter") {
            $v['img_creator_1'] = base_url() . '/assets/img/icon/icon-twitter.png';
        } else {
            $v['img_creator_1'] = base_url() . '/assets/img/icon/icon-no.png';
        }

        if ($v['url']) {
            $v['img_creator_1'] = '<a href="' . $v['url'] . '" target="_blank"><img style="width:40px;border-radius:10px;" class="mt-0" src="' . $v['img_creator_1'] . '"></a>';
        } else {
            $v['img_creator_1'] = '<img style="width:40px;border-radius:10px; filter: grayscale(100%)!important;" class="mt-0" src="' . $v['img_creator_1'] . '">';
        }

        if ($v['tipe_kontak'] == "WA") {
            $v['img_creator_2'] = base_url() . '/assets/img/icon/icon-wa.png';
        } else if ($v['tipe_kontak'] == "IG") {
            $v['img_creator_2'] = base_url() . '/assets/img/icon/icon-ig.png';
        } else if ($v['tipe_kontak'] == "Email") {
            $v['img_creator_2'] = base_url() . '/assets/img/icon/icon-email.png';
        } else if ($v['tipe_kontak'] == "HP") {
            $v['img_creator_2'] = base_url() . '/assets/img/icon/icon-phone.png';
        } else {
            $v['img_creator_2'] = base_url() . '/assets/img/icon/icon-no.png';
        }

        if ($v['contact']) {
            $v['img_creator_2'] = '<a href="' . $v['contact'] . '" target="_blank"><img style="width:40px;border-radius:10px;" class="mt-0" src="' . $v['img_creator_2'] . '"></a>';
        } else {
            $v['img_creator_2'] = '<img style="width:40px;border-radius:10px; filter: grayscale(100%)!important;" class="mt-0" src="' . $v['img_creator_2'] . '">';
        }

        $current_url = $_SERVER['REQUEST_URI'];

        if (strpos($current_url, 'endorse/item-endorse') !== false) {
            $display_k = $k + 1;
        } else {
            $display_k = $k;
        }
    ?>
    <div class="card mb-3" style="padding-bottom:0px">
        <div class="row">
            <!-- Featured Media Section -->
            <?php if (!empty($v['media_attachment'])): ?>
                <div class="col-lg-3 col-md-4 mb-3 mb-lg-0" style="position:relative;">
                    <?php
                    $file_ext = strtolower(pathinfo($v['media_attachment'], PATHINFO_EXTENSION));
                    if (in_array($file_ext, ['jpg', 'jpeg', 'png'])):
                    ?>
                        <a href="<?= base_url('assets/img/endorse/' . $v['media_attachment']) ?>" target="_blank" style="display:block; height:100%; min-height:200px;">
                            <img src="<?= base_url('assets/img/endorse/' . $v['media_attachment']) ?>"
                                 alt="Media"
                                 style="width:100%; height:100%; min-height:200px; object-fit:cover; border-radius:10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        </a>
                    <?php elseif (in_array($file_ext, ['mp4', 'mov', 'avi'])): ?>
                        <a href="<?= base_url('assets/img/endorse/' . $v['media_attachment']) ?>" target="_blank" style="display:block; height:100%; min-height:200px; background:#f0f0f0; border-radius:10px; position:relative;">
                            <div style="display:flex; align-items:center; justify-content:center; height:100%; min-height:200px; flex-direction:column;">
                                <i class="bi bi-play-circle" style="font-size:60px; color:#6c757d; margin-bottom:10px;"></i>
                                <p style="margin:0; color:#6c757d; font-size:14px; font-weight:600;">Click to view video</p>
                            </div>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Creator Info Section -->
            <div class="<?= !empty($v['media_attachment']) ? 'col-lg-3 col-md-4' : 'col-lg-4' ?>">
                <a class="mb-1 text-blue fw-700 fs-16 a-none" href="<?= base_url() ?>endorse/detail?id=<?= $v['id'] ?>">#<?= $display_k ?></a>
                <p class="mb-1 text-black fw-700 fs-16"><?= $v['nama_creator'] ?> <i class="bi bi-eye text-blue" id="eye-card-<?= $v['nama_creator'] ?>"></i></p>

                <div class="col-lg-12 mt-0 mb-3">
                    <?= $v['img_creator_1'] ?>
                    <?= $v['img_creator_2'] ?>
                </div>

                <p class="mb-1 text-black">PIC : <?= $v['pic'] ?></p>

                <?php if (!empty($v['category_kol'])): ?>
                    <p class="mb-1">
                        <span class="badge" style="background-color:#9b59b6; color:white; padding: 4px 8px; font-size: 11px;">
                            <i class="bi bi-tag-fill"></i> <?= htmlspecialchars($v['category_kol']) ?>
                        </span>
                    </p>
                <?php endif; ?>

                <div class="d-flex align-items-center" style="gap: 5px; margin-top:6px!important;">
                    <p class="mb-0 br-10 fs-12 text-white" style="background-color:<?= $bg ?>!important; color:<?= $clr ?>!important; padding: 5px 10px;">
                        <?= strtoupper(strtolower($v['status_endorse'])) ?>
                    </p>
                    <?php if (!empty($v['status_payment'])): ?>
                        <p class="mb-0 br-10 fs-12 text-white" style="background-color:<?= $bg_payment ?>!important; color:<?= $clr_payment ?>!important; padding: 5px 10px;">
                            <?= strtoupper(strtolower($v['status_payment'])) ?>
                        </p>
                    <?php endif; ?>
                    <?php if (!empty($v['pengajuan_status_payment'])): ?>
                        <p class="mb-0 br-10 fs-12 text-white" style="background-color:<?= $bg_payment ?>!important; color:<?= $clr_payment ?>!important; padding: 5px 10px;">
                            <?= strtoupper(strtolower($v['pengajuan_status_payment'])) ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Stats Section -->
            <div class="<?= !empty($v['media_attachment']) ? 'col-lg-3 col-md-4' : 'col-md-4' ?> mt-lg-5 mt-0">
                <p class="mb-1 text-black fw-600">CPM : <?= separator_only($creator['cpm_2']) ?></p>
                <p class="mb-1 text-black">AVG Interaksi : <?= separator_only($creator['avg_interaksi_2']) ?></p>
                <p class="mb-1 text-black">AVG View : <?= separator_only($creator['avg_view_2']) ?></p>
            </div>
            <!-- Action Buttons Section -->
            <div class="<?= !empty($v['media_attachment']) ? 'col-lg-3' : 'col-lg-4' ?> text-lg-end text-start">
                <?php if (!in_array($v['status_payment'], ['DP','FP'], true)) : ?>
                <a href="#!" onclick="remove('<?= $v['id'] ?>')" class="btn btn-delete mt-0 mb-2"><i class="bi bi-trash fs-16"></i> Delete Data</a>
                <?php endif; ?>

                <?php if ($v['status'] == "Aktif" && $v['status_campaign'] == "Aktif" && $v['link_upload'] != "") { ?>
                    <a href="#!" onclick="sync('<?= $v['id'] ?>')" class="btn btn-sync ms-1 mt-0 mb-2"><i class="bi bi-bootstrap-reboot fs-16"></i> Refresh</a>
                <?php } ?>
                <a href="#!" onclick="clone('<?= $v['id'] ?>')" class="btn btn-copy ms-1 mt-0 mb-2"><i class="bi bi-copy fs-16"></i> Kloning</a>
                <a href="#!" onclick="edit('<?= $v['id'] ?>')" class="btn btn-edit  mt-0 ms-1 mb-2"><i class="bi bi-pencil-square fs-16"></i> Edit Data</a>
                <?php if ($v['link_mou'] == '-' && $v['is_generated_mou'] == 0) { ?>
                    <a href="#!" onclick="generate_mou('<?= $v['id'] ?>')" class="btn btn-sync  mt-0 ms-1 mb-2"><i class="bi bi-clipboard2-plus fs-16"></i> Generate MOU</a>
                <?php } ?>
                <a href="#!" onclick="set_payment(<?= $v['id'] ?>)" class="btn btn-sync mt-0 mb-2">
                    <i class="bi bi-clipboard2-check fs-16"></i> Ajukan Payment
                </a>

                <?php if (!empty($v['pengajuan_status_payment'])): ?>
                    <a href="#!" onclick="set_batalkan_payment(<?= $v['id'] ?>)" class="btn btn-delete mt-0 mb-2">
                        <i class="bi bi-clipboard2-x fs-16"></i> Batalkan Payment
                    </a>
                <?php endif; ?>

            </div>
            <div class="col-lg-12">
                <hr>
            </div>
            <div class="col-lg-12">
                <div class="row">
                    <div class="col-md-4">
                        <p class="mb-1 text-black">Total Cost : <?= separator_only($v['total_cost']) ?></p>
                        <p class="mb-1 text-black">
                            Barang Dikirim :
                            <span class="badge <?= $v['barang_dikirim_at'] != '-' ? 'bg-success text-success' : 'bg-secondary' ?>">
                                <?= $v['barang_dikirim_at'] != '-' ? $v['barang_dikirim_at'] : 'Belum Dikirim' ?>
                            </span>
                        </p>
                        <p class="mb-1 text-black">Rencana Upload : <?= $v['rencana_at'] ?></p>
                        <p class="mb-1 text-black">Tanggal Posting : <?= $v['posting_at'] ?></p>
                        <p class="mb-1 text-black">Status : <?= $v['status'] ?></p>
                        <p class="mb-1">Produk : <?= $v['product_text'] ?></p>
                        <p class="mb-1">Ket : <?= $v['desc'] ?></p>
                        <p class="mb-1">Ads : <?= $v['kode_ads'] ?></p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1 text-black fw-600">Views : <?= separator_only($v['views']) ?></p>
                        <p class="mb-1 text-black fw-600">CPM : <?= separator_only($v['cpm']) ?></p>
                        <p class="mb-1 text-black">Likes : <?= separator_only($v['likes']) ?></p>
                        <p class="mb-1 text-black">Comments : <?= separator_only($v['comment']) ?></p>
                        <p class="mb-1 text-black">Save & Share : <?= separator_only($v['share_save']) ?></p>
                        <p class="mb-1 text-black">Tanggal Dibuat : <?= $v['created_at'] ?></p>
                        <p class="mb-1 text-black">Tanggal Diupdate : <?= $v['sync_at'] ?></p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1 text-black">Link Brief : <br><?= $v['link_brief'] ?></p>
                        <p class="mb-1 text-black">
                            Link MOU : <br>
                            <?= $v['link_mou'] ?>
                            <?php if ($v['is_generated_mou'] == 1): ?>
                                <span class="badge bg-success text-success ms-2">MOU Generated</span>
                            <?php endif; ?>
                        </p>


                        <p class="mb-1 text-black">Ket. Payment : <br><?= $v['keterangan_payment'] ?></p>
                        <a href="<?= base_url('endorse/payment_logs?id_campaign=' . $v['id_campaign'] . '&nama_creator=' . urlencode($v['nama_creator'])) ?>"
                            target="_blank">
                            Lihat Logs Payment
                        </a>
                    </div>
                    <div class="col-lg-12 mt-3">
                        <div class="row">
                            <div class="col-12 mb-2" style="position:relative">
                                <div class="row">
                                    <div class="firstDivImg">
                                        <?= $v['img'] ?>
                                    </div>
                                    <div class="secondDivImg" style="margin-top: -10px;margin-left:-20px;">
                                        <a href="#!" onclick="change_fyp_<?= $k ?>()" id="fyp_<?= $k ?>" class="">
                                            <?php if ($v['is_fyp'] == 1) { ?>
                                                <i class="bi bi-star-fill" style="font-size:40px;color:#ffd250"></i>
                                            <?php } else { ?>
                                                <i class="bi bi-star" style="font-size:40px;color:#000"></i>
                                            <?php } ?>
                                            <script>
                                                function change_fyp_<?= $k ?>() {
                                                    $('#fyp_<?= $k ?>').html('<i style="margin-top: 10px;margin-bottom:10px;font-size:40px;color:#000" class="fa fa-circle-o-notch fa-spin"></i>');
                                                    $.ajax({
                                                        dataType: "json",
                                                        url: '<?= base_url() ?>ajax/change-fyp?id=<?= $v['id'] ?>',
                                                        success: function(html) {
                                                            $('#fyp_<?= $k ?>').html(html.html);
                                                            <?php $v['code'] =  'mar-5' ?>
                                                            $.ajax({
                                                                dataType: "json",
                                                                url: '<?= base_url() ?>ajax/get-summary-campaign<?= $param ?>&id=<?= $v['code'] ?>&id_campaign=<?= $v['id_campaign'] ?>',
                                                                success: function(html) {
                                                                    $("#summary-<?= $v['code'] ?>").html(html.html);
                                                                }
                                                            });
                                                        }
                                                    });
                                                }
                                            </script>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12 mb-3">
                            <div class="checkbox-wrapper-13 d-inline">
                                <input class="checkItem" style="" type="checkbox" value="<?= $v['id'] ?>" data-id="<?= $k ?>" name="list_id" form="form-action">
                            </div>
                            <input type="hidden" value="<?= $v['is_manual'] ?>" name="is_manual[<?= $k - $start ?>]" form="form-action">
                            <input type="hidden" value="<?= $v['marketplace'] ?>" name="marketplace[<?= $k - $start ?>]" form="form-action">
                            <input type="hidden" value="<?= $v['brand'] ?>" name="brand[<?= $k - $start ?>]" form="form-action">
                            <input type="hidden" value="<?= $v['order_id'] ?>" name="order_id[<?= $k - $start ?>]" form="form-action">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php $k += 1;
    }
}
?>

<style>
.tippy-box[data-theme~='light'] {
    background-color: #ffffff !important;
    color: #333333 !important;
    box-shadow: 0 4px 14px rgba(0,0,0,0.1) !important;
    border: 1px solid #e5e7eb !important;
}

.tippy-box[data-theme~='light'] .tippy-arrow {
    color: #ffffff !important;
}

.tippy-box[data-theme~='light'] .tippy-content {
    color: inherit !important;
}
</style>

<script>
    function sortTable(column, order) {
    const urlParams = new URLSearchParams(window.location.search);

    urlParams.set('sort', column);
    urlParams.set('order', order);

    urlParams.delete('page');

    window.location.search = urlParams.toString();
    }

    function loadMoreData() {
    $.ajax({
        type: 'GET',
        url: "<?= base_url() ?>/endorse/item<?= $param ?>",
        success: function(data) {
            $('#tbody-loading').html('');
            $('#tbody').append(data);
        },
        error: function(xhr, status, error) {}
    });
    }
</script>

<script type="text/javascript">
    $('input[name="list_id"]').change(function() {
        get_id();
    });

    $(document).ready(function() {
        $('[id^="eye-card-"]').each(function() {
            const eyeIcon = this;
            const creatorName = eyeIcon.id.replace('eye-card-', '');
            
            tippy(eyeIcon, {
                content: '<div class="p-2"><div class="text-center py-2"><i class="fa fa-spinner fa-spin"></i> Memuat data...</div></div>',
                allowHTML: true,
                interactive: true,
                placement: 'right',
                theme: 'light',
                maxWidth: 350,
                onShow(instance) {
                    $.ajax({
                        url: '<?= base_url() ?>endorse/get_influencer_data',
                        type: 'POST',
                        data: { nama_creator: creatorName },
                        dataType: 'json',
                        success: function(data) {
                            if (data) {
                                const tooltipContent = `
                                    <div class="report-card p-2" style="min-width: 250px; line-height: 1.5; font-size: 13px;">
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                            <span class="text-secondary fw-bold">Total Endorse</span>
                                            <span>${data.endorse_count || 0}</span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                            <span class="text-secondary fw-bold">Avg. Views</span>
                                            <span>${data.avg_views || 0}</span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between;">
                                            <span class="text-secondary fw-bold">CPM</span>
                                            <span>${data.cpm || 0}</span>
                                        </div>
                                    </div>
                                `;
                                instance.setContent(tooltipContent);
                            } else {
                                throw new Error("Data tidak valid");
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error:', error);
                            instance.setContent(`
                                <div class="p-2 text-danger">
                                    Gagal memuat data influencer
                                    <div class="small mt-2">${error}</div>
                                </div>
                            `);
                        }
                    });
                }
            });
        });

        $('[id^="eye-list-"]').each(function() {
            const eyeIcon = this;
            const creatorName = eyeIcon.id.replace('eye-list-', '');
            
            tippy(eyeIcon, {
                content: '<div class="p-2"><div class="text-center py-2"><i class="fa fa-spinner fa-spin"></i> Memuat data...</div></div>',
                allowHTML: true,
                interactive: true,
                placement: 'right',
                theme: 'light',
                maxWidth: 700,
                onShow(instance) {
                    $.ajax({
                        url: '<?= base_url() ?>endorse/get_influencer_data_all',
                        type: 'POST',
                        data: { nama_creator: creatorName },
                        dataType: 'json',
                        success: function(data) {
                            if (data) {
                                const tooltipContent = `
                                    <div class="report-card p-2" style="width: 100%; max-width: 600px; line-height: 1.5; font-size: 13px;">
                                        <div style="display: flex; flex-direction: row; align-items: flex-start; gap: 10px;">
                                            
                                            <!-- Internal Section -->
                                            <div style="flex: 1; min-width: 150px;">
                                                <div class="text-secondary fw-bold" style="margin-bottom: 5px;">Internal (${data.endorse_count || 0})</div>
                                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                                    <span class="text-secondary">Avg. Views</span>
                                                    <span style="white-space: nowrap;">${data.avg_views || 0}</span>
                                                </div>
                                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                                    <span class="text-secondary">CPM</span>
                                                    <span style="white-space: nowrap;">${data.cpm || 0}</span>
                                                </div>
                                                <div style="display: flex; justify-content: space-between;">
                                                    <span class="text-secondary">ER</span>
                                                    <span style="white-space: nowrap;">${data.er || 0}</span>
                                                </div>
                                            </div>

                                            <!-- Eksternal Section -->
                                            <div style="flex: 1; min-width: 150px; border-left: 1px solid #ccc; padding-left: 15px;">
                                                <div class="text-secondary fw-bold" style="margin-bottom: 5px;">Eksternal</div>
                                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                                    <span class="text-secondary">Avg. Views</span>
                                                    <span style="white-space: nowrap;">${data.avg_views_2 || 0}</span>
                                                </div>
                                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                                    <span class="text-secondary">CPM</span>
                                                    <span style="white-space: nowrap;">${data.cpm_2 || 0}</span>
                                                </div>
                                                <div style="display: flex; justify-content: space-between;">
                                                    <span class="text-secondary">ER</span>
                                                    <span style="white-space: nowrap;">${data.er_2 || 0}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    `;



                                instance.setContent(tooltipContent);
                            } else {
                                throw new Error("Data tidak valid");
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error:', error);
                            instance.setContent(`
                                <div class="p-2 text-danger">
                                    Gagal memuat data influencer
                                    <div class="small mt-2">${error}</div>
                                </div>
                            `);
                        }
                    });
                }
            });
        });
    });

    document.querySelectorAll('.btn-copy').forEach(function (button) {
        button.addEventListener('click', function () {
            const kode = this.getAttribute('data-kode');
            const icon = this.querySelector('i');

            if (!kode || !icon) return;

            const originalClass = icon.className;

            icon.className = 'spinner-border spinner-border-sm';

            navigator.clipboard.writeText(kode).then(() => {
                setTimeout(() => {
                    icon.className = 'bi bi-check fs-16 text-success';
                }, 500);

                setTimeout(() => {
                    icon.className = originalClass;
                }, 2000);
            }).catch((err) => {
                console.error('Gagal menyalin:', err);
                icon.className = 'bi bi-x fs-16 text-danger';
                setTimeout(() => {
                    icon.className = originalClass;
                }, 2000);
            });
        });
    });

</script>