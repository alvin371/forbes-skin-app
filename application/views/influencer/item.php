<?php
$k = $start;

function date_format_indo($date)
{
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
    if ($date) {
        $date = DATE('d', strtotime($date)) . ' ' . $month[intval(DATE('m', strtotime($date)))] . ' ' . DATE('Y', strtotime($date));
    } else {
        $date = '-';
    }
    return $date;
}

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
            to { transform: rotate(360deg); }
        }
    </style>
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="bg-light">
                    <!-- <th data-original-index="<?= $k + 1 ?>"> -->
                    <th width="40">#</th>
                    <th class="sortable">Nama Influencer
                        <?php if ($sort_column == 'username'): ?>
                            <i class="bi bi-arrow-<?= $sort_order == 'ASC' ? 'up' : 'down' ?>"></i>
                        <?php else: ?>
                            <i class="bi bi-arrow-down-up"></i>
                        <?php endif; ?>
                    </th>
                    <th class="sortable">Followers
                        <?php if ($sort_column == 'follower'): ?>
                            <i class="bi bi-arrow-<?= $sort_order == 'ASC' ? 'up' : 'down' ?>"></i>
                        <?php else: ?>
                            <i class="bi bi-arrow-down-up"></i>
                        <?php endif; ?>
                    </th>
                    <th class="sortable">Engagement Internal
                        <?php if ($sort_column == 'engagement_external'): ?>
                            <i class="bi bi-arrow-<?= $sort_order == 'ASC' ? 'up' : 'down' ?>"></i>
                        <?php else: ?>
                            <i class="bi bi-arrow-down-up"></i>
                        <?php endif; ?>
                    </th>
                    <th class="sortable">Engagement External
                        <?php if ($sort_column == 'engagement_internal'): ?>
                            <i class="bi bi-arrow-<?= $sort_order == 'ASC' ? 'up' : 'down' ?>"></i>
                        <?php else: ?>
                            <i class="bi bi-arrow-down-up"></i>
                        <?php endif; ?>
                    </th>
                    <th class="sortable">Ratecard 
                        <?php if ($sort_column == 'cpm'): ?>
                            <i class="bi bi-arrow-<?= $sort_order == 'ASC' ? 'up' : 'down' ?>"></i>
                        <?php else: ?>
                            <i class="bi bi-arrow-down-up"></i>
                        <?php endif; ?>
                    </th>
                    <th>Kontak</i></th>
                    <th width="120">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $v): 
                    if ($v['type'] == "Tiktok") {
                        $v['img'] = base_url() . '/assets/img/icon/icon-tiktok.png';
                    } else if ($v['type'] == "Instagram") {
                        $v['img'] = base_url() . '/assets/img/icon/icon-ig.png';
                    } else if ($v['type'] == "Youtube") {
                        $v['img'] = base_url() . '/assets/img/icon/icon-youtube.png';
                    } else if ($v['type'] == "Facebook") {
                        $v['img'] = base_url() . '/assets/img/icon/icon-fb.png';
                    } else if ($v['type'] == "Twitter") {
                        $v['img'] = base_url() . '/assets/img/icon/icon-twitter.png';
                    } else {
                        $v['img'] = base_url() . '/assets/img/icon/icon-no.png';
                    }
                    if ($v['username'] == "") {
                        $v['username'] = "-";
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
                        $v['sync_at'] = DATE("d/m/Y H:i", strtotime($v['sync_at']));
                    } else {
                        $v['sync_at'] = '-';
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
                    if ($v['url']) {
                        $v['img'] = '<a href="' . $v['url'] . '" target="_blank"><img style="width:40px;border-radius:10px;" class="mt-0" src="' . $v['img'] . '"></a>';
                    } else {
                        $v['img'] = '<img style="width:40px;border-radius:10px; filter: grayscale(100%)!important;" class="mt-0" src="' . $v['img'] . '">';
                    }
                    $bg = '#e6e6e6';
                    $clr = '#000';
                    if (in_array($v['status_reach'], array('Affiliate'))) {
                        $bg = '#e6e6e6';
                        // $clr = '#FFF';
                    } else if (in_array($v['status_reach'], array('Belum Reachout'))) {
                        $bg = '#ffe599';
                        // $clr = '#FFF';
                    } else if (in_array($v['status_reach'], array('Sudah Reachout'))) {
                        $bg = '#f6b26b';
                        // $clr = '#FFF';
                    } else if (in_array($v['status_reach'], array('Off Endorsement'))) {
                        $bg = '#ffd0d0';
                        // $clr = '#FFF';
                    } else if (in_array($v['status_reach'], array('Pernah Kerjasama'))) {
                        $bg = '#d4edbc';
                        // $clr = '#FFF';
                    } else if (in_array($v['status_reach'], array('Repeat Kerjasama'))) {
                        $bg = '#7bd3ea';
                        // $clr = '#FFF';
                    } else if (in_array($v['status_reach'], array('Blacklist / Ghosting'))) {
                        $bg = '#ff8080';
                        // $clr = '#FFF';
                    }
            
                    if ($v['tipe_kontak'] == "WA") {
                        $v['img_2'] = base_url() . '/assets/img/icon/icon-wa.png';
                    } else if ($v['tipe_kontak'] == "IG") {
                        $v['img_2'] = base_url() . '/assets/img/icon/icon-ig.png';
                    } else if ($v['tipe_kontak'] == "Email") {
                        $v['img_2'] = base_url() . '/assets/img/icon/icon-email.png';
                    } else if ($v['tipe_kontak'] == "HP") {
                        $v['img_2'] = base_url() . '/assets/img/icon/icon-phone.png';
                    } else {
                        $v['img_2'] = base_url() . '/assets/img/icon/icon-no.png';
                    }
            
                    if ($v['contact']) {
                        $v['img_2'] = '<a href="' . $v['contact'] . '" target="_blank"><img style="width:40px;border-radius:10px;" class="mt-0" src="' . $v['img_2'] . '"></a>';
                    } else {
                        $v['img_2'] = '<img style="width:40px;border-radius:10px; filter: grayscale(100%)!important;" class="mt-0" src="' . $v['img_2'] . '">';
                    }
            
                    // $arr = array();
                    // $arr[] = "Semua Status";
                    // $arr[] = "1 - Planning";
                    // $arr[] = "2 - Review";
                    // $arr[] = "3 - Ditolak";
                    // $arr[] = "4 - Di Pertimbangkan";
                    // $arr[] = "5 - Acc & Payment";
                    // $arr[] = "6 - Barang Endorse Dikirim";
                    // $arr[] = "7 - Barang Endorse Diterima";
                    // $arr[] = "8 - Draft Content";
                    // $arr[] = "9 - Posted Content";
                    // $arr[] = "Ada Link Upload";
                    // $arr[] = "Tidak Ada Link Upload";
                    if (empty($v['desc'])) {
                        $v['desc'] = '-';
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
                            <div class="fw-bold fs-16"><?= $v['username'] ?></div>
                            <div class="text-muted"><?= $v['pic'] ?></div>
                        </div>
                    </td>


                    <td><?= $this->template->separator_only($v['follower']) ?></td>
                    <td>
                        <p class="mb-1 text-black">Avg Views : <?= $this->template->separator_only($v['avg_view']) ?></p>
                        <p class="mb-1 text-black">CPM : <?= $this->template->separator_only($v['cpm']) ?></p>
                    </td>
                    <td>
                        <p class="mb-1 text-black">Avg Views : <?= $this->template->separator_only($v['avg_view_2']) ?></p>
                        <p class="mb-1 text-black">CPM : <?= $this->template->separator_only($v['cpm_2']) ?></p>
                    </td>
                    <td><?= $this->template->separator_only($v['ratecard']) ?></td>
                    <td>
                        <div class="col-lg-12 mt-0 mb-3">
                            <?= $v['img'] ?>
                            <?= $v['img_2'] ?>
                        </div>
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
                                    <?php if ($v['status_endorse'] != "Review" && $v['status_endorse'] != 'Hold' && $v['status_endorse'] != 'Reject') { ?>
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
    foreach ($data as $v) {
        if ($v['type'] == "Tiktok") {
            $v['img'] = base_url() . '/assets/img/icon/icon-tiktok.png';
        } else if ($v['type'] == "Instagram") {
            $v['img'] = base_url() . '/assets/img/icon/icon-ig.png';
        } else if ($v['type'] == "Youtube") {
            $v['img'] = base_url() . '/assets/img/icon/icon-youtube.png';
        } else if ($v['type'] == "Facebook") {
            $v['img'] = base_url() . '/assets/img/icon/icon-fb.png';
        } else if ($v['type'] == "Twitter") {
            $v['img'] = base_url() . '/assets/img/icon/icon-twitter.png';
        } else {
            $v['img'] = base_url() . '/assets/img/icon/icon-no.png';
        }
        if ($v['username'] == "") {
            $v['username'] = "-";
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
            $v['sync_at'] = DATE("d/m/Y H:i", strtotime($v['sync_at']));
        } else {
            $v['sync_at'] = '-';
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
        if ($v['url']) {
            $v['img'] = '<a href="' . $v['url'] . '" target="_blank"><img style="width:40px;border-radius:10px;" class="mt-0" src="' . $v['img'] . '"></a>';
        } else {
            $v['img'] = '<img style="width:40px;border-radius:10px; filter: grayscale(100%)!important;" class="mt-0" src="' . $v['img'] . '">';
        }
        $bg = '#e6e6e6';
        $clr = '#000';
        if (in_array($v['status_reach'], array('Affiliate'))) {
            $bg = '#e6e6e6';
            // $clr = '#FFF';
        } else if (in_array($v['status_reach'], array('Belum Reachout'))) {
            $bg = '#ffe599';
            // $clr = '#FFF';
        } else if (in_array($v['status_reach'], array('Sudah Reachout'))) {
            $bg = '#f6b26b';
            // $clr = '#FFF';
        } else if (in_array($v['status_reach'], array('Off Endorsement'))) {
            $bg = '#ffd0d0';
            // $clr = '#FFF';
        } else if (in_array($v['status_reach'], array('Pernah Kerjasama'))) {
            $bg = '#d4edbc';
            // $clr = '#FFF';
        } else if (in_array($v['status_reach'], array('Repeat Kerjasama'))) {
            $bg = '#7bd3ea';
            // $clr = '#FFF';
        } else if (in_array($v['status_reach'], array('Blacklist / Ghosting'))) {
            $bg = '#ff8080';
            // $clr = '#FFF';
        }

        if ($v['tipe_kontak'] == "WA") {
            $v['img_2'] = base_url() . '/assets/img/icon/icon-wa.png';
        } else if ($v['tipe_kontak'] == "IG") {
            $v['img_2'] = base_url() . '/assets/img/icon/icon-ig.png';
        } else if ($v['tipe_kontak'] == "Email") {
            $v['img_2'] = base_url() . '/assets/img/icon/icon-email.png';
        } else if ($v['tipe_kontak'] == "HP") {
            $v['img_2'] = base_url() . '/assets/img/icon/icon-phone.png';
        } else {
            $v['img_2'] = base_url() . '/assets/img/icon/icon-no.png';
        }

        if ($v['contact']) {
            $v['img_2'] = '<a href="' . $v['contact'] . '" target="_blank"><img style="width:40px;border-radius:10px;" class="mt-0" src="' . $v['img_2'] . '"></a>';
        } else {
            $v['img_2'] = '<img style="width:40px;border-radius:10px; filter: grayscale(100%)!important;" class="mt-0" src="' . $v['img_2'] . '">';
        }

        // $arr = array();
        // $arr[] = "Semua Status";
        // $arr[] = "1 - Planning";
        // $arr[] = "2 - Review";
        // $arr[] = "3 - Ditolak";
        // $arr[] = "4 - Di Pertimbangkan";
        // $arr[] = "5 - Acc & Payment";
        // $arr[] = "6 - Barang Endorse Dikirim";
        // $arr[] = "7 - Barang Endorse Diterima";
        // $arr[] = "8 - Draft Content";
        // $arr[] = "9 - Posted Content";
        // $arr[] = "Ada Link Upload";
        // $arr[] = "Tidak Ada Link Upload";
        if (empty($v['desc'])) {
            $v['desc'] = '-';
        }
    ?>
        <div class="card mb-3" style="padding-bottom:0px">
            <div class="row">
                <div class="col-lg-7">
                    <p class="mb-1 text-blue fw-700 fs-16 a-none" href="<?= base_url() ?>/endorse/detail?id=<?= $v['id'] ?>">#<?= $k ?></p>
                    <p class="mb-1 text-black fw-700 fs-16"><?= $v['username'] ?></p>
                    <p class="mb-1 text-black">PIC : <?= $v['pic'] ?></p>
                    <p class="mb-3" style="margin-top:6px!important"><span class="br-10 fs-12 text-white" style="background-color:<?= $bg ?>!important;color:<?= $clr ?>!important"><?= strtoupper(strtolower($v['status_reach'])) ?></span></p>
                </div>
                <div class="col-lg-5 text-lg-end text-start">
                    <a href="#!" onclick="remove('<?= $v['id'] ?>')" class="btn btn-delete  mt-0 mb-2"><i class="bi bi-trash fs-16"></i> Delete Data</a>
                    <a href="#!" onclick="sync('<?= $v['id'] ?>')" class="btn btn-sync ms-1 mt-0 mb-2"><i class="bi bi-bootstrap-reboot fs-16"></i> Refresh</a>
                    <a href="<?= base_url() ?>endorse/stats?id_campaign=<?= $campaign['id'] ?>&id_influencer=<?= $v['id'] ?>" class="btn btn-copy ms-1 mt-0 mb-2"><i class="bi bi-person-video2 fs-16"></i> Konten</a>
                    <a href="#!" onclick="edit('<?= $v['id'] ?>')" class="btn btn-edit  mt-0 ms-1 mb-2"><i class="bi bi-pencil-square fs-16"></i> Edit Data</a>
                </div>
                <div class="col-lg-12">
                    <hr class="">
                </div>
                <div class="col-lg-12">
                    <div class="row">
                        <div class="col-md-4">
                            <p class="mb-1 text-black">Brand : <?= $v['brand'] ?></p>
                            <p class="mb-1 text-black">Niche : <?= $v['niche'] ?></p>
                            <p class="mb-1 text-black">Follower : <?= $template->separator_only($v['follower']) ?></p>
                            <p class="mb-1 text-black">ER % : <?= $template->separator_1($v['er']) ?>%</p>
                            <p class="mb-1 text-black">Status : <?= $v['status'] ?></p>
                            <p class="mb-1 text-black">No Rekening : <?= $v['no_rekening'] ?></p>
                            <p class="mb-1 text-black">Tanggal Diupdate : <?= $v['sync_at'] ?></p>
                            <p class="mb-1">Ket : <?= $v['desc'] ?></p>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-1 text-black fw-600">Internal</p>
                            <p class="mb-1 text-black fw-600">Total Cost : <?= $template->separator_only($v['total_cost']) ?></p>
                            <p class="mb-1 text-black fw-600">Views : <?= $template->separator_only($v['view']) ?></p>
                            <p class="mb-1 text-black fw-600">CPM : <?= $template->separator_only($v['cpm']) ?></p>
                            <p class="mb-1 text-black">Likes : <?= $template->separator_only($v['like']) ?></p>
                            <p class="mb-1 text-black">Comments : <?= $template->separator_only($v['comment']) ?></p>
                            <p class="mb-1 text-black">AVG Interaksi : <?= $template->separator_only($v['avg_interaksi']) ?></p>
                            <p class="mb-1 text-black">AVG View : <?= $template->separator_only($v['avg_view']) ?></p>
                            <p class="mb-1 text-black">Frequency : <?= $template->separator_only($v['frequency']) ?></p>
                            <p class="mb-1 text-black">Save & Share : <?= $template->separator_only($v['collect'] + $v['share']) ?></p>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-1 text-black fw-600">Eksternal</p>
                            <p class="mb-1 text-black fw-600">Range RC : <?= $template->separator_only($v['ratecard']) ?></p>
                            <!-- <p class="mb-1 text-black fw-600">Views : <?= $template->separator_only($v['view_2']) ?></p> -->
                            <p class="mb-1 text-black fw-600">CPM : <?= $template->separator_only($v['cpm_2']) ?></p>
                            <!-- <p class="mb-1 text-black">Likes : <?= $template->separator_only($v['like_2']) ?></p> -->
                            <!-- <p class="mb-1 text-black">Comments : <?= $template->separator_only($v['comment_2']) ?></p> -->
                            <p class="mb-1 text-black">AVG Interaksi : <?= $template->separator_only($v['avg_interaksi_2']) ?></p>
                            <p class="mb-1 text-black">AVG View : <?= $template->separator_only($v['avg_view_2']) ?></p>
                            <!-- <p class="mb-1 text-black">Frequency : <?= $template->separator_only($v['frequency_2']) ?></p> -->
                            <!-- <p class="mb-1 text-black">Save & Share : <?= $template->separator_only($v['collect_2'] + $v['share_2']) ?></p> -->
                        </div>
                        <div class="col-lg-12 mt-0 mb-3">
                            <?= $v['img'] ?>
                            <?= $v['img_2'] ?>
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
    

<script>
    $('input[name="list_id"]').change(function() {
        get_id();
    });
</script>